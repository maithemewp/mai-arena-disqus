<?php

/**
 * Plugin Name:      Mai Arena Disqus
 * Plugin URI:       https://bizbudding.com
 * Description:      Enables Disqus compatibility for sites imported from Arena's Tempest CMS.
 * Version:          0.1.0
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
require_once __DIR__ . '/classes/class-settings.php';

// Instantiate settings.
new Settings();

add_action( 'plugins_loaded', __NAMESPACE__ . '\init' );
/**
 * Initialize the config.
 *
 * @since 0.1.0
 *
 * @return void
 */
function init() {
	// Get shortname from settings.
	$settings  = new Settings();
	$shortname = $settings->get_shortname();

	// Bail if no shortname is set.
	if ( ! $shortname ) {
		return;
	}

	// Instantiate Disqus.
	new Config( [
		'shortname'  => $shortname,
		'post_types' => [ 'post' ],
	] );
}
