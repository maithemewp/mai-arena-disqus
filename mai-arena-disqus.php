<?php

/**
 * Plugin Name:      Mai Arena Disqus
 * Plugin URI:       https://bizbudding.com
 * Description:      Enables Disqus compatibility for sites imported from Arena's Tempest CMS.
 * Version:          0.4.0
 * Requires PHP:     8.1
 * Requires Plugins: disqus-conditional-load
 *
 * Author:           BizBudding
 * Author URI:       https://bizbudding.com
 */

namespace Mai\Arena\Disqus;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Include vendor libraries.
require_once __DIR__ . '/vendor/autoload.php';

// Require classes.
require_once __DIR__ . '/classes/class-config.php';

// Register WP-CLI command.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/classes/class-cli.php';
	\WP_CLI::add_command( 'mai-arena-disqus', __NAMESPACE__ . '\CLI' );
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\init' );
/**
 * Initialize the config.
 *
 * @since 0.1.0
 *
 * @return void
 */
function init() {
	$shortname = get_option( 'disqus_forum_url' );

	// Bail if no shortname is set.
	if ( ! $shortname ) {
		return;
	}

	// Default args.
	$args = [
		'shortname'  => $shortname,
		'post_types' => [ 'post' ],
	];

	// Allow filtering of args.
	$args = apply_filters( 'mai_arena_disqus_args', $args );

	// Instantiate Disqus.
	new Config( $args );
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\updater' );
/**
 * Setup the updater.
 *
 * Sites only see an update when a new tag exists on GitHub.
 *
 * @since 0.4.0
 *
 * @return void
 */
function updater() {
	// Bail if plugin updater is not loaded.
	if ( ! class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
		return;
	}

	// Setup the updater.
	$updater = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker( 'https://github.com/maithemewp/mai-arena-disqus/', __FILE__, 'mai-arena-disqus' );

	// Maybe set github api token.
	if ( defined( 'MAI_GITHUB_API_TOKEN' ) ) {
		$updater->setAuthentication( MAI_GITHUB_API_TOKEN );
	}

	// Add icons for Dashboard > Updates screen.
	if ( function_exists( 'mai_get_updater_icons' ) && $icons = mai_get_updater_icons() ) {
		$updater->addResultFilter(
			function ( $info ) use ( $icons ) {
				$info->icons = $icons;
				return $info;
			}
		);
	}
}
