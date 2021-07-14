<?php
/**
 * Plugin Name: Ghop
 * Plugin URI: https://ghop.es
 * Description: Add-ons for the website.
 * Version: 1.1.0
 * Author: juagonala
 * Author URI: https://juagonala.com/
 * Requires PHP: 5.4
 * Requires at least: 4.4
 * Tested up to: 5.8
 * Text Domain: ghop
 * Domain Path: /languages
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Ghop
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Ghop.
 */
class Ghop {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->define_constants();
		$this->init_hooks();
	}

	/**
	 * Define constants.
	 *
	 * @since 1.0.0
	 */
	private function define_constants() {
		$this->define( 'GHOP_VERSION', '1.1.0' );
		$this->define( 'GHOP_PATH', plugin_dir_path( __FILE__ ) );
		$this->define( 'GHOP_URL', plugin_dir_url( __FILE__ ) );
		$this->define( 'GHOP_BASENAME', plugin_basename( __FILE__ ) );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $name  The constant name.
	 * @param string|bool $value The constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_ghop_open_door', array( $this, 'ajax_open_door' ) );
	}

	/**
	 * Init plugin.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Load text domain.
		load_plugin_textdomain( 'ghop', false, dirname( GHOP_BASENAME ) . '/languages' );
	}

	/**
	 * Enqueues the frontend scripts.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'ghop-scripts', GHOP_URL . '/assets/js/scripts.js', array( 'jquery' ), GHOP_VERSION, true );
		wp_localize_script(
			'ghop-scripts',
			'ghop_scripts_params',
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'ghop-open-door' ),
				'button_text' => __( 'Opening&hellip;', 'ghop' ),
			)
		);
	}

	/**
	 * AJAX request for opening the shop door.
	 *
	 * @since 1.0.0
	 */
	public function ajax_open_door() {
		check_ajax_referer( 'ghop-open-door', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Log in first.', 'ghop' ) ) );
		}

		$current_user = wp_get_current_user();

		if ( in_array( 'contributor', $current_user->roles, true ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: contact email */
						__( 'We have detected a suspicious situation. Write to us at %s for more details. ', 'ghop' ),
						'<a href="mailto:hola@ghop.es">hola@ghop.es</a>'
					),
				)
			);
		}

		$current_time = time(); // UNIX timestamp.

		// The data to submit to the shop's server.
		$data = array(
			'username' => $current_user->user_login,
			'hora'     => wp_date( 'Y-m-d H:i:s', $current_time ),
			'datetime' => $current_time,
		);

		$response = wp_remote_post(
			'http://ghoptienda.ddns.net:8080/acceso',
			array(
				'httpversion' => '1.1',
				'sslverify'   => false,
				'timeout'     => 60,
				'body'        => wp_json_encode( $data ),
				'headers'     => array(
					'Content-Type' => 'application/json',
				),
			)
		);

		$status_code = wp_remote_retrieve_response_code( $response );

		// Invalid API request.
		if ( 200 > $status_code || $status_code >= 300 ) {
			wp_send_json_error( array( 'message' => __( 'An unexpected error occurred. Please, contact us for assistance.', 'ghop' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Shop opened. Welcome!', 'ghop' ) ) );
	}
}

new Ghop();
