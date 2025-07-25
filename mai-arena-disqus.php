<?php

/**
 * Plugin Name:      Mai Arena Disqus
 * Plugin URI:       https://bizbudding.com
 * Description:      Enables Disqus compatibility for sites imported from Arena's Tempest CMS.
 * Version:          0.3.0
 * Requires Plugins: disqus-conditional-load
 *
 * Author:           BizBudding
 * Author URI:       https://bizbudding.com
 */

namespace Mai\Arena\Disqus;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Require classes.
require_once __DIR__ . '/classes/class-config.php';

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
