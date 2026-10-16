<?php

namespace Mai\Arena\Disqus;

use WP_CLI;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class CLI {

	/**
	 * Pre-seed dsq_thread_id post meta by matching Disqus thread identifiers.
	 *
	 * Fetches all threads from the Disqus forum, matches them to WordPress posts
	 * by the dsq_thread_identifier meta key, and saves the Disqus thread ID as
	 * dsq_thread_id post meta so the Disqus plugin import maps comments correctly.
	 *
	 * ## OPTIONS
	 *
	 * [--api-key=<key>]
	 * : Disqus API public key. Falls back to the disqus_public_key option.
	 *
	 * [--dry-run]
	 * : Show what would be updated without making changes.
	 *
	 * ## EXAMPLES
	 *
	 *     wp mai-arena-disqus preseed
	 *     wp mai-arena-disqus preseed --api-key=YOUR_KEY
	 *     wp mai-arena-disqus preseed --dry-run
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function preseed( $args, $assoc_args ) {
		$dry_run   = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$api_key   = \WP_CLI\Utils\get_flag_value( $assoc_args, 'api-key', get_option( 'disqus_public_key' ) );
		$shortname = get_option( 'disqus_forum_url' );

		if ( ! $shortname ) {
			WP_CLI::error( 'Disqus forum shortname not found. Is the Disqus plugin configured?' );
		}

		if ( ! $api_key ) {
			WP_CLI::error( 'Disqus API key not found. Pass --api-key=YOUR_KEY or set the disqus_public_key option.' );
		}

		if ( $dry_run ) {
			WP_CLI::log( '--- DRY RUN (no changes will be made) ---' );
		}

		// Step 1: Get all WordPress posts with dsq_thread_identifier meta.
		WP_CLI::log( 'Fetching WordPress posts with dsq_thread_identifier meta...' );

		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = 'dsq_thread_identifier'",
			OBJECT
		);

		if ( empty( $rows ) ) {
			WP_CLI::error( 'No posts found with dsq_thread_identifier meta.' );
		}

		// Build identifier → post_id lookup.
		$identifier_to_post = [];
		foreach ( $rows as $row ) {
			$identifier_to_post[ $row->meta_value ] = (int) $row->post_id;
		}

		WP_CLI::log( sprintf( 'Found %d posts with dsq_thread_identifier.', count( $identifier_to_post ) ) );

		// Step 2: Fetch all threads from Disqus API and match.
		WP_CLI::log( 'Fetching threads from Disqus API...' );

		$matched = 0;
		$cursor  = '';
		$page    = 0;

		do {
			$page++;
			$url = add_query_arg( [
				'forum'   => $shortname,
				'api_key' => $api_key,
				'limit'   => 100,
				'cursor'  => $cursor,
			], 'https://disqus.com/api/3.0/threads/list.json' );

			$response = wp_remote_get( $url, [ 'timeout' => 30 ] );

			if ( is_wp_error( $response ) ) {
				WP_CLI::error( 'API request failed: ' . $response->get_error_message() );
			}

			$code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $code ) {
				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				$msg  = isset( $body['response'] ) ? $body['response'] : "HTTP $code";
				WP_CLI::error( 'Disqus API error: ' . $msg );
			}

			$body     = json_decode( wp_remote_retrieve_body( $response ), true );
			$threads  = isset( $body['response'] ) ? $body['response'] : [];
			$has_next = ! empty( $body['cursor']['hasNext'] );
			$cursor   = isset( $body['cursor']['next'] ) ? $body['cursor']['next'] : '';

			foreach ( $threads as $thread ) {
				$thread_id   = $thread['id'];
				$identifiers = isset( $thread['identifiers'] ) ? $thread['identifiers'] : [];

				foreach ( $identifiers as $identifier ) {
					if ( isset( $identifier_to_post[ $identifier ] ) ) {
						$post_id = $identifier_to_post[ $identifier ];

						if ( $dry_run ) {
							WP_CLI::log( sprintf( '  Would map thread %s → post %d', $thread_id, $post_id ) );
						} else {
							update_post_meta( $post_id, 'dsq_thread_id', $thread_id );
						}

						$matched++;
						unset( $identifier_to_post[ $identifier ] );
						break;
					}
				}
			}

			// Stop early if all identifiers are matched.
			if ( empty( $identifier_to_post ) ) {
				WP_CLI::log( 'All identifiers matched. Stopping early.' );
				break;
			}

			if ( $page % 10 === 0 ) {
				WP_CLI::log( sprintf( '  Processed %d pages, %d matched so far...', $page, $matched ) );
			}

		} while ( $has_next && ! empty( $threads ) );

		// Report results.
		$unmatched = count( $identifier_to_post );

		WP_CLI::log( '' );
		WP_CLI::log( sprintf( 'Processed %d API pages.', $page ) );
		WP_CLI::success( sprintf(
			'%s %d posts. %d unmatched.',
			$dry_run ? 'Would update' : 'Updated',
			$matched,
			$unmatched
		) );

		if ( $unmatched > 0 && $unmatched <= 20 ) {
			WP_CLI::log( 'Unmatched post identifiers:' );
			foreach ( $identifier_to_post as $ident => $post_id ) {
				WP_CLI::log( sprintf( '  Post %d: %s', $post_id, $ident ) );
			}
		} elseif ( $unmatched > 20 ) {
			WP_CLI::log( sprintf(
				'%d posts had no matching Disqus thread (these posts may never have had comments).',
				$unmatched
			) );
		}
	}
}
