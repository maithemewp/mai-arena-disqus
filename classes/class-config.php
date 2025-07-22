<?php

namespace Mai\Arena\Disqus;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The Mai Arena Config class.
 *
 * @version 0.1.0
 */
class Config {
	/**
	 * Args.
	 *
	 * @since 0.1.0
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function __construct( $args = [] ) {
		$this->args = wp_parse_args( $args, [
			'shortname'  => null,
			'post_types' => [ 'post' ],
			'meta_key'   => 'dsq_thread_identifier',
		] );

		// Bail if shortname is not set.
		if ( ! $this->args['shortname'] ) {
			return;
		}

		$this->init();
	}

	/**
	 * Initialize the class.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_head',   [ $this, 'set_config' ], 5 );
		add_action( 'save_post', [ $this, 'save_post' ] );
	}

	/**
	 * Set Disqus config.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function set_config() {
		// Bail if not single post, page, or custom post type.
		if ( ! is_singular( $this->args['post_types'] ) ) {
			return;
		}

		// Get Disqus script identifier.
		$post_id    = get_the_ID();
		$identifier = get_post_meta( $post_id, $this->args['meta_key'], true );
		$identifier = $identifier ? $identifier : $this->get_identifier( $post_id );

		// Bail if no identifier is found.
		if ( ! $identifier ) {
			return;
		}

		// Get URL and title.
		$url   = get_the_permalink();
		$title = get_the_title();

		// Add Disqus config to head.
		printf( '<script>var disqus_config = function() { this.page.identifier = "%s";this.page.url = "%s";this.page.title = "%s";};</script>',
			$identifier,
			$url,
			$title
		);
	}

	/**
	 * Create and save a Disqus script identifier to post meta.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function save_post( $post_id ) {
		// Bail if it's a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Bail if it's not a post type.
		if ( ! in_array( get_post_type( $post_id ), $this->args['post_types'] ) ) {
			return;
		}

		// Bail if it's not a publish post.
		if ( 'publish' !== get_post_status( $post_id ) ) {
			return;
		}

		// Get key.
		$key = 'dsq_thread_identifier';

		// Bail if it already has an identifier.
		if ( get_post_meta( $post_id, $key, true ) ) {
			return;
		}

		// Get identifier.
		$identifier = $this->get_identifier( $post_id );

		// Save identifier.
		update_post_meta( $post_id, $key, $identifier );
	}

	/**
	 * Get the Disqus script identifier.
	 *
	 * Disqus plugin does it differently.
	 * We're hashing the guid to be safer
	 * since Arena was doing their own thing anyway.
	 *
	 * @link https://github.com/disqus/disqus-wordpress-plugin/blob/master/disqus/public/class-disqus-public.php
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string
	 */
	public function get_identifier( $post_id ) {
		return md5( get_the_guid( $post_id ) );
	}

	/**
	 * Get the Disqus title.
	 * This was taken from the Disqus plugin.
	 *
	 * @link https://github.com/disqus/disqus-wordpress-plugin/blob/master/disqus/public/class-disqus-public.php
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string
	 */
	public function get_title( $post_id ) {
		$title = get_the_title( $post_id );
		$title = strip_tags( $title, '<b><u><i><h1><h2><h3><code><blockquote><br><hr>' );

		return $title;
	}
}