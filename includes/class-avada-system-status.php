<?php
/**
 * Various helper methods for Avada's System Status page.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      5.6
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Various helper methods for Avada.
 *
 * @since 5.6
 */
class Avada_System_Status {

	/**
	 * The class constructor
	 *
	 * @access public
	 */
	public function __construct() {

		// Check update server API status.
		add_action( 'wp_ajax_fusion_check_api_status', [ $this, 'check_api_status' ] );

		// Re-create Avada Forms DB tables.
		add_action( 'wp_ajax_fusion_create_forms_tables', [ $this, 'create_forms_tables' ] );

		// Copy multisite global options.
		add_action( 'wp_ajax_awb_copy_multisite_global_options', [ $this, 'copy_multisite_global_options' ] );      
	}

	/**
	 * AJAX callback method, used to check various APIs status.
	 *
	 * @access public
	 */
	public function check_api_status() {

		if ( ! isset( $_GET['api_type'] ) || ! check_ajax_referer( 'fusion_system_status_nonce', 'nonce', false ) ) {
			echo wp_json_encode(
				[
					'code'         => 200,
					'message'      => __( 'API type missing.', 'Avada' ),
					'api_response' => '',
				]
			);
			die();
		}

		$envato_string = '';
		$api_type      = trim( sanitize_text_field( wp_unslash( $_GET['api_type'] ) ) );
		$api_response  = [];
		$response      = [
			'code'         => 200,
			'message'      => __( 'Tested API is working properly.', 'Avada' ),
			'api_response' => '',
		];

		if ( 'tf_updates' === $api_type ) {
			$api_response     = $this->check_tf_updates_status();
			$response['code'] = (int) trim( wp_remote_retrieve_response_code( $api_response ) );
		}

		if ( 'envato' === $api_type ) {
			$api_response = $this->check_envato_status( true );

			if ( is_wp_error( $api_response ) ) {
				$response['code'] = (int) trim( $api_response->get_error_code() );
				$envato_string    = str_replace( [ 'Unauthorized', 'Forbidden' ], '<br />Invalid Token', $api_response->get_error_message() );
			} elseif ( isset( $api_response['headers_data'] ) ) {
				$envato_string       = $api_response['headers_data'];
				$response['message'] = $response['message'] . ' ' . $envato_string;
			}
		}

		// Serialize whole array for easier debugging.
		$response['api_response'] = esc_textarea( maybe_serialize( $api_response ) );
		if ( 401 === $response['code'] ) {
			/* translators: HTTP response code */
			$response['message'] = sprintf( __( 'Server responded with unauthorized response code: %1$s. %2$s', 'Avada' ), $response['code'], $envato_string );
		} elseif ( 3 === (int) ( $response['code'] / 100 ) ) {
			/* translators: HTTP response code */
			$response['message'] = sprintf( __( 'Server responded with redirection response code: %1$s. %2$s', 'Avada' ), $response['code'], $envato_string );
		} elseif ( 4 === (int) ( $response['code'] / 100 ) ) {
			/* translators: HTTP response code */
			$response['message'] = sprintf( __( 'Error occured while checking API status. Response code: %1$s. %2$s', 'Avada' ), $response['code'], $envato_string );
		} elseif ( 5 === (int) ( $response['code'] / 100 ) ) {
			/* translators: HTTP response code */
			$response['message'] = sprintf( __( 'Internal server error occured while checking API status. Response code: %1$s. %2$s', 'Avada' ), $response['code'], $envato_string );
		} elseif ( 200 !== $response['code'] ) {
			/* translators: HTTP response code */
			$response['message'] = sprintf( __( 'Something went wrong while checking API status. Response code: %1$s. %2$s', 'Avada' ), $response['code'], $envato_string );
		}

		echo wp_json_encode( $response );
		die();
	}

	/**
	 * Helper method, pings ThemeFusion server.
	 *
	 * @access private
	 * @return array wp_remote_get response.
	 */
	private function check_tf_updates_status() {
		return wp_remote_get( Fusion_Patcher_Client::$remote_patches_uri );
	}

	/**
	 * Helper method, pings Envato server.
	 *
	 * @access private
	 * @param bool $headers_data Set to true if response headers should be provided.
	 * @return mixed array|WP_Error Depending on server response.
	 */
	private function check_envato_status( $headers_data = false ) {
		return Avada()->registration->envato_api()->request( 'https://api.envato.com/v2/market/buyer/download?item_id=2833226', [ 'headers_data' => $headers_data ] );
	}

	/**
	 * Ajax callback for creating Avada Forms database tables.
	 *
	 * @access public
	 */
	public function create_forms_tables() {

		$response = [ 'message' => __( 'Creating database tables failed.' ) ];

		if ( ! check_ajax_referer( 'fusion_system_status_nonce', 'nonce', false ) ) {
			$response = [ 'message' => __( 'Security check failed.' ) ];
		}

		// Fusion Builder is active and Forms are enabled.
		if ( class_exists( 'Fusion_Form_Builder' ) && Fusion_Form_Builder::is_enabled() ) {

			// Include Form Installer.
			if ( ! class_exists( 'Fusion_Form_DB_Install' ) ) {
				include_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-form-db-install.php';
			}

			$fusion_form_db_install = new Fusion_Form_DB_Install();
			$fusion_form_db_install->create_tables();

			$response = [ 'message' => __( 'Database tables are created successfully.' ) ];
		}

		echo wp_json_encode( $response );
		die();
	}


	/**
	 * Copy Avada Global Options from main site to all sites across the multisite install.
	 *
	 * @since 7.11.12
	 * @access public
	 * @return void
	 */
	public function copy_multisite_global_options() {
		$response = [ 'message' => __( 'This is not a multisite.' ) ];

		if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'fusion_system_status_nonce', 'nonce', false ) ) {
			$response = [ 'message' => __( 'Security check failed.' ) ];
		}       

		if ( is_multisite() ) {
			$main_site_id      = get_main_site_id();
			$main_site_options = get_blog_option( $main_site_id, 'fusion_options', [] );
			$sites             = get_sites();

			foreach ( $sites as $site ) {
				$site_id = $site->blog_id;
				if ( $site_id !== $main_site_id ) {
					update_blog_option( $site->blog_id, 'fusion_options', $main_site_options );
				}
			}
			
			$response = [ 'message' => __( 'Global options were successfully copied across the network.' ) ];
		}

		echo wp_json_encode( $response );
		die();      
	}
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
