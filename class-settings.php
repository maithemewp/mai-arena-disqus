<?php

namespace Mai\Arena\Disqus;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Settings page class.
 *
 * @version 0.1.0
 */
class Settings {
	/**
	 * Option name.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	protected $option_name = 'mai_arena_disqus';

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Add settings page to admin menu.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			'Mai Arena Disqus Settings',
			'Mai Arena Disqus',
			'manage_options',
			'mai-arena-disqus',
			[ $this, 'settings_page' ]
		);
	}

	/**
	 * Register settings.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'mai_arena_disqus_settings',
			$this->option_name,
			[ $this, 'sanitize_settings' ]
		);

		add_settings_section(
			'mai_arena_disqus_general',
			'General Settings',
			[ $this, 'settings_section_callback' ],
			'mai-arena-disqus'
		);

		add_settings_field(
			'shortname',
			'Disqus Shortname',
			[ $this, 'shortname_field' ],
			'mai-arena-disqus',
			'mai_arena_disqus_general'
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @since 0.1.0
	 *
	 * @param array $input The input array.
	 *
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$sanitized = [];

		if ( isset( $input['shortname'] ) ) {
			$sanitized['shortname'] = sanitize_text_field( $input['shortname'] );
		}

		return $sanitized;
	}

	/**
	 * Settings section callback.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function settings_section_callback() {
		echo '<p>Configure your Disqus settings below.</p>';
	}

	/**
	 * Shortname field.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function shortname_field() {
		$options   = get_option( $this->option_name, [] );
		$shortname = isset( $options['shortname'] ) ? $options['shortname'] : '';
		?>
		<input
			type="text"
			id="shortname"
			name="<?php echo esc_attr( $this->option_name ); ?>[shortname]"
			value="<?php echo esc_attr( $shortname ); ?>"
			class="regular-text"
		/>
		<p class="description">
			Enter your Disqus shortname. You can find this in your Disqus admin panel.
		</p>
		<?php
	}

	/**
	 * Settings page HTML.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'mai_arena_disqus_settings' );
				do_settings_sections( 'mai-arena-disqus' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Get shortname from settings.
	 *
	 * @since 0.1.0
	 *
	 * @return string|null
	 */
	public function get_shortname() {
		$options = get_option( $this->option_name, [] );
		return isset( $options['shortname'] ) ? $options['shortname'] : null;
	}
}