<?php
/**
 * Various helper methods to send data to avada.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      7.10.0
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Various helper methods for Avada.
 *
 * @since 7.10.0
 */
class AWB_Site_Data {

	/**
	 * The one, true instance of this object.
	 *
	 * @static
	 * @access private
	 * @since 7.5
	 * @var object
	 */
	private static $instance;

	/**
	 * The array with server info.
	 *
	 * @access public
	 * @var array
	 */
	public $server_info = [];

	/**
	 * The array with plugins info.
	 *
	 * @access public
	 * @var array
	 */
	public $plugins_data = [];
	
	/**
	 * The class constructor
	 *
	 * @access public
	 */
	public function __construct() {

		// Add the notices.
		add_action( 'current_screen', [ $this, 'display_data_notice' ], 1 );

		add_action( 'wp_ajax_fusion_send_site_data', [ $this, 'send_data' ] );

		// Handle sending the data via ajax.
		add_action( 'wp_ajax_fusion_dismiss_data_notice', [ $this, 'dismiss_data_notice' ] );

		// Check if the needs to send again.
		add_action( 'admin_init', [ $this, 'maybe_send_data' ] );
	}

	/**
	 * Send data.
	 *
	 * @access public
	 * @param bool $bypass Should bypass sending JSON response or not.
	 */
	public function send_data( $bypass = '' ) {
		// Send data to Avada sever.
		$has_error               = false;
		$status                  = [];
		$fusion_builder_settings = get_option( 'fusion_builder_settings', [] );
		$site_data               = $this->get_site_data();
		$option_data             = [
			'status' => 'sent',
			'date'   => time(),
		];

		if ( ! is_array( $site_data ) && ! isset( $site_data['purchase_code'] ) ) {
			$has_error = true;
		}

		$args = [
			'method'     => 'POST',
			'timeout'    => 60,
			'user-agent' => 'fusion-site-data',
			'body'       => $site_data,
		];

		// Make API call and send data.
		$response = wp_remote_post( FUSION_UPDATES_URL . '/wp-json/avada-api/site-data/', $args );

		if ( is_wp_error( $response ) ) {
			$has_error = true;
		}
		$code = wp_remote_retrieve_response_code( $response );

		if ( 399 < $code && 501 > $code ) {
			$has_error = true;
		}

		// Set status.
		$status['status'] = true === $has_error ? 'error' : 'success';

		// Update option. 
		update_option( 'awb_site_data_status', $option_data );

		// Set AWB settings.
		$fusion_builder_settings['site_data_consent'] = 'on';
		update_option( 'fusion_builder_settings', $fusion_builder_settings );

		
		if ( empty( $bypass ) ) {
			wp_send_json( $status );
		}
	}

	/**
	 * Check if data needs sending again.
	 *
	 * @access private
	 */
	public function maybe_send_data() {
		$fusion_builder_settings = get_option( 'fusion_builder_settings', [] );
		$data_status             = get_option( 'awb_site_data_status', [] );
		$should_send             = false;
		if ( ! isset( $data_status['date'] ) ) {
			$should_send = true;
		} elseif ( isset( $data_status['date'] ) && $data_status['date'] < strtotime( '-30 days' ) ) {
			$should_send = true;
		}

		if ( isset( $fusion_builder_settings['site_data_consent'] ) && 'on' === $fusion_builder_settings['site_data_consent'] && $should_send ) {
			$this->send_data( true );
		}
	}

	/**
	 * Helper method to collect server info.
	 *
	 * @access private
	 */
	public function set_server_info() {
		global $wpdb, $wp_version;
		$this->server_info = [
			'php_version'        => $this->get_php_version(),
			'wp_version'         => $wp_version,
			'server'             => isset( $_SERVER['SERVER_SOFTWARE'] ) ? esc_html( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) ) : esc_html__( 'Unknown', 'Avada' ),
			'max_execution_time' => ini_get( 'max_execution_time' ),
			'db_version'         => esc_html( $wpdb->db_version() ),
			'max_upload_size'    => esc_attr( size_format( wp_max_upload_size() ) ),
			'language'           => esc_html( get_locale() ),
			'memory_limit'       => $this->get_memory_limit(),
		];
	}

	/**
	 * Helper method to return server info array.
	 *
	 * @access public
	 * @return string array.
	 */
	public function get_server_info() {
		return $this->server_info; 
	}

	/**
	 * Helper method to get memory limit.
	 *
	 * @access public
	 * @return string php memory.
	 */
	public function get_memory_limit() {
		
		// Get memory limit.
		$memory = ini_get( 'memory_limit' );

		// If we can't get it, fallback to WP_MEMORY_LIMIT.
		if ( ! $memory || -1 === $memory ) {
			$memory = wp_convert_hr_to_bytes( WP_MEMORY_LIMIT );
		}
		
		// Make sure the value is properly formatted in bytes.
		if ( ! is_numeric( $memory ) ) {
			$memory = wp_convert_hr_to_bytes( $memory );
		}
		return esc_html( size_format( $memory ) );    
	}

	/**
	 * Helper method to get PHP version.
	 * 
	 * @access public
	 * @return string php version.
	 */
	public function get_php_version() {
		$php_version = null;
		if ( defined( 'PHP_VERSION' ) ) {
			$php_version = PHP_VERSION;
		} elseif ( function_exists( 'phpversion' ) ) {
			$php_version = phpversion();
		}
		return $php_version;
	}
	
	/**
	 * Method to set plugin data.
	 * 
	 * @access public
	 */
	public function set_plugins() {
		$active_plugins = (array) get_option( 'active_plugins', [] );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', [] ) ) );
		}

		// Set total active plugins count.
		$this->plugins_data['count'] = count( $active_plugins );

		// Keep only 25 plugins data.
		$active_plugins = array_slice( $active_plugins, 0, 25 );
		foreach ( $active_plugins as $plugin_file ) {
			$plugin_data    = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
			$dirname        = dirname( $plugin_file );
			$plugin_details = [];
			if ( ! empty( $plugin_data['Name'] ) ) {
				$plugin_details['name']   = esc_html( $plugin_data['Name'] );
				$plugin_details['author'] = preg_replace( '#<a.*?>([^>]*)</a>#i', '$1', $plugin_data['AuthorName'] );
			}

			// Set the plugin data.
			$this->plugins_data['plugins_data'][] = $plugin_details;
		}
	}

	/**
	 * Method to get plugin data.
	 * 
	 * @access public
	 * @return array an array of plugins data
	 */
	public function get_plugins() {
		return $this->plugins_data;
	}

	/**
	 * Get the details about layouts, avada modules.
	 *
	 * @access public
	 * @return array an array of layouts data
	 */ 
	public function get_layouts() {
		if ( ! class_exists( 'Fusion_Template_Builder' ) ) {
			return [];
		}
		$layout_data   = [];
		$other_layouts = [];

		// Layouts object.
		$layouts               = Fusion_Template_Builder()->get_registered_layouts();
		$layout_data['global'] = $this->get_sections( $layouts[0] );

		// Exclude global layout.
		unset( $layouts[0] );
		
		// Keep 25 layouts only.
		$layouts = array_slice( $layouts, 0, 25 );

		foreach ( $layouts as $layout ) {
			$other_layouts[] = [
				'sections'   => $this->get_sections( $layout ),
				'conditions' => array_slice( $layout['data']['conditions'], 0, 5 ),
			];
		}
		$layout_data['others'] = $other_layouts;
		
		// Global and other layouts data.
		return $layout_data;
	}

	/**
	 * Get layout sections set.
	 *
	 * @param array $layout array with data.
	 * @access public
	 * @return array
	 */
	public function get_sections( $layout = [] ) {
		$layout_details = [
			'header'         => isset( $layout['data']['template_terms']['header'] ) && $layout['data']['template_terms']['header'] ? true : false,
			'page_title_bar' => isset( $layout['data']['template_terms']['page_title_bar'] ) && $layout['data']['template_terms']['page_title_bar'] ? true : false,
			'content'        => isset( $layout['data']['template_terms']['content'] ) && $layout['data']['template_terms']['content'] ? true : false,
			'footer'         => isset( $layout['data']['template_terms']['footer'] ) && $layout['data']['template_terms']['footer'] ? true : false,
		];

		// returns Sections assigned for a layout.
		return $layout_details;
	}

	/**
	 * Get the details previous versions.
	 *
	 * @access public
	 * @return array
	 */
	public function get_previous_versions() {
		$previous_version        = get_option( 'avada_previous_version', false );
		$previous_versions_array = [];
		$previous_versions       = false;

		if ( $previous_version && is_array( $previous_version ) ) {
			foreach ( $previous_version as $key => $value ) {
				if ( ! $value ) {
					unset( $previous_version[ $key ] );
				}
			}

			$previous_versions_array = $previous_version;
			$previous_versions       = array_slice( $previous_version, -3, 3, true );
		}
		return $previous_versions;
	}

	/**
	 * Get the details about layouts, avada modules.
	 *
	 * @access public
	 *  @return array
	 */
	public function get_imported_websites() {
		$imported_data     = get_option( 'fusion_import_data', [] );
		$imported_websites = [];
		if ( ! function_exists( 'avada_get_demo_import_stages' ) ) {
			include_once Avada::$template_dir_path . '/includes/avada-functions.php';
		}
		$import_stages = avada_get_demo_import_stages();

		foreach ( $imported_data as $stage => $imported_demos ) {
			foreach ( $imported_demos as $imported_demo ) {
				if ( ! in_array( $imported_demo, $imported_websites, true ) ) {
					$imported_websites[] = $imported_demo;
				}
			}
		}

		// Return imported sites array.
		return $imported_websites;
	}

	/**
	 * Get the features enabled.
	 *
	 * @access public
	 *  @return array
	 */
	public function get_features() {
		global $fusion_settings;
		$features = [
			'studio'          => class_exists( 'AWB_Studio' ) && AWB_Studio::is_studio_enabled() ? true : false,
			'off_canvass'     => class_exists( 'AWB_Off_Canvas' ) && AWB_Off_Canvas::is_enabled() && $this->has_posts( 'awb_off_canvas' ) ? true : false,
			'forms'           => class_exists( 'Fusion_Form_Builder' ) && Fusion_Form_Builder::is_enabled() && $this->has_posts( 'fusion_form' ) ? true : false,
			'custom_icons'    => $this->has_posts( 'fusion_icons' ) ? true : false,
			'avada_slider'    => $fusion_settings->get( 'status_fusion_slider' ) && $this->has_posts( 'slide' ) ? true : false,
			'avada_portfolio' => $fusion_settings->get( 'status_fusion_portfolio' ) && $this->has_posts( 'avada_portfolio' ) ? true : false,
			'avada_faqs'      => $fusion_settings->get( 'status_fusion_faqs' ) && $this->has_posts( 'avada_faq' ) ? true : false,
			'widgets'         => $fusion_settings->get( 'status_widget_areas' ) ? true : false,
		];

		// Return set of features enabled if they have data.
		return $features;
	}

	/**
	 * Get the features enabled.
	 *
	 * @access public
	 * @param string $post_type the post type to query.
	 * @return array
	 */
	public function has_posts( $post_type ) {
		$posts     = new WP_Query(
			[
				'post_type'      => $post_type,
				'posts_per_page' => 1,
				'post_status'    => 'publish',
			] 
		);
		$has_posts = 0 < $posts->found_posts ? 1 : 0;
		return $has_posts;
	}

	/**
	 * Get the elements enabled.
	 *
	 * @access public
	 *  @return array
	 */
	public function get_elements() {
		global $all_fusion_builder_elements;
		if ( ! isset( $all_fusion_builder_elements ) ) {
			return '';
		}
		$builder_settings = get_option( 'fusion_builder_settings', [] );
		$i                = 0;
		$plugin_elements  = [
			'fusion_featured_products_slider' => [
				'name'      => esc_html__( 'Woo Featured', 'Avada' ),
				'shortcode' => 'fusion_featured_products_slider',
				'class'     => ( class_exists( 'WooCommerce' ) ) ? '' : 'hidden',
			],
			'fusion_products_slider'          => [
				'name'      => esc_html__( 'Woo Carousel', 'Avada' ),
				'shortcode' => 'fusion_products_slider',
				'class'     => ( class_exists( 'WooCommerce' ) ) ? '' : 'hidden',
			],
			'fusion_woo_shortcodes'           => [
				'name'      => esc_html__( 'Woo Shortcodes', 'Avada' ),
				'shortcode' => 'fusion_woo_shortcodes',
				'class'     => ( class_exists( 'WooCommerce' ) ) ? '' : 'hidden',
			],
			'layerslider'                     => [
				'name'      => esc_html__( 'Layer Slider', 'Avada' ),
				'shortcode' => 'layerslider',
				'class'     => ( defined( 'LS_PLUGIN_BASE' ) ) ? '' : 'hidden',
			],
			'rev_slider'                      => [
				'name'      => esc_html__( 'Slider Revolution', 'Avada' ),
				'shortcode' => 'rev_slider',
				'class'     => ( defined( 'RS_PLUGIN_PATH' ) ) ? '' : 'hidden',
			],
			'fusion_events'                   => [
				'name'      => esc_html__( 'Events', 'Avada' ),
				'shortcode' => 'fusion_events',
				'class'     => ( class_exists( 'Tribe__Events__Main' ) ) ? '' : 'hidden',
			],
			'fusion_fontawesome'              => [
				'name'      => esc_html__( 'Icon', 'Avada' ),
				'shortcode' => 'fusion_fontawesome',
			],
			'fusion_fusionslider'             => [
				'name'      => esc_html__( 'Avada Slider', 'Avada' ),
				'shortcode' => 'fusion_fusionslider',
			],
		];

		$all_fusion_builder_elements = array_merge( $all_fusion_builder_elements, apply_filters( 'fusion_builder_plugin_elements', $plugin_elements ) );

		usort( $all_fusion_builder_elements, 'fusion_element_sort' );
		$fusion_elements = [];
		$form_elements   = [];
		$layout_elements = [];

		// Loop through elements to get the active ones.
		foreach ( $all_fusion_builder_elements as $module ) :
			if ( empty( $module['hide_from_builder'] ) ) {
				$i++;
				
				// Form Components.
				if ( ! empty( $module['form_component'] ) ) {
					$form_elements[ $i ] = $module;
					continue;
				}

				// Layout Componnents.
				if ( ! empty( $module['component'] ) ) {
					$layout_elements[ $i ] = $module;
					continue;
				}

				// Check if the element is active.
				if ( isset( $builder_settings['fusion_elements'] ) && is_array( $builder_settings['fusion_elements'] ) && in_array( $module['shortcode'], $builder_settings['fusion_elements'] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					$fusion_elements['fusion_elements'][ $module['shortcode'] ] = $module['name'];
				}           
			}
		endforeach;
		$module = '';

		// Layout elements output.
		foreach ( $layout_elements as $i => $module ) :
			if ( isset( $builder_settings['fusion_elements'] ) && is_array( $builder_settings['fusion_elements'] ) && in_array( $module['shortcode'], $builder_settings['fusion_elements'] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				$fusion_elements['components'][ $module['shortcode'] ] = $module['name'];
			}
		endforeach;

		// Form elements output.
		foreach ( $form_elements as $i => $module ) :
			if ( isset( $builder_settings['fusion_elements'] ) && is_array( $builder_settings['fusion_elements'] ) && in_array( $module['shortcode'], $builder_settings['fusion_elements'] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				$fusion_elements['form'][ $module['shortcode'] ] = $module['name'];
			}
		endforeach;

		// Return the active elements.
		return $fusion_elements;
	}

	/**
	 * Get the details about layouts, avada modules.
	 *
	 * @access public
	 * @return array
	 */
	public function get_site_data() {

		$this->set_server_info();
		$this->set_plugins();

		$site_data = [
			'environment'         => $this->get_server_info(),
			'layouts'             => $this->get_layouts(),
			'plugins'             => $this->get_plugins(),
			'is_setup_wizard_run' => get_option( 'avada_setup_wizard_done', false ),
			'previous_versions'   => $this->get_previous_versions(),
			'imported_websites'   => $this->get_imported_websites(),
			'features'            => $this->get_features(),
			'elements'            => $this->get_elements(),
			'child_theme'         => is_child_theme(),
			'staging'             => 0,
			'purchase_code'       => Avada()->registration->is_registered() ? Avada()->registration->get_purchase_code() : false,
		]; 
		return $site_data;
	}

	/**
	 * Display data notice.
	 *
	 * @access public
	 * @since 7.10.0
	 * @return void
	 */
	public function display_data_notice() {
		$message  = '';
		$message  = sprintf( '<h2>%s</h2>', esc_html__( 'Help Us Improve Avada', 'Avada' ) );
		$message .= sprintf( '<p>%1$s <a href="%2$s" target="_blank">%3$s</a></p>', esc_html__( 'Become a contribution hero by opting in to share some non-sensitive usage data with us, that will help us to improve the product.', 'Avada' ), esc_url( 'https://avada.com/documentation/share-usage-data/' ), esc_html__( 'Learn more.', 'Avada' ) );
		$message .= sprintf( '<p><a class="button button-primary avada-send-data" href="#">%s</a></p>', esc_html__( 'Send Data', 'Avada' ) );
		$message .= '<div class="avada-data-response">';
		$message .= sprintf( '<span class="data-success-response">%s</span>', esc_html__( 'Thank you, the data has been sent successfully.', 'Avada' ) );
		$message .= sprintf( '<span class="data-error-response">%s</span>', esc_html__( 'There was an error trying to send the data. Please try again.', 'Avada' ) );
		$message .= '</div>';

		$show_notice = false;
		$data_status = get_option( 'awb_site_data_status', [] );
		if ( isset( $data_status['status'] ) && 'show_notice' === $data_status['status'] ) {
			$show_notice = true;
		}
		if ( class_exists( 'Fusion_Admin_Notice' ) && $show_notice && Avada()->registration->is_registered() ) {
			new Fusion_Admin_Notice(
				'avada-data-notice',
				$message,
				is_super_admin(),
				'info',
				true,
				'awb_option',
				'awb_site_data_notice',
				[
					'avada_page_avada-prebuilt-websites',
					'avada_page_avada-plugins',
					'avada_page_avada-patcher',
					'toplevel_page_avada',
					'avada_page_avada-studio',
					'avada_page_avada-layouts',
				]
			);
		}
	}
	
	/**
	 * Dismiss data notice.
	 *
	 * @access public
	 * @since 7.10.0
	 * @return void
	 */
	public function dismiss_data_notice() {

		check_ajax_referer( 'fusion_admin_notice', 'nonce' );
		if ( ! empty( $_POST ) && isset( $_POST['data'] ) ) {
			$option                  = '';
			$fusion_builder_settings = get_option( 'fusion_builder_settings', [] );
			if ( isset( $_POST['data']['dismissOption'] ) ) {
				$option = sanitize_text_field( wp_unslash( $_POST['data']['dismissOption'] ) );
			} elseif ( isset( $_POST['data']['dismiss-option'] ) ) {
				$option = sanitize_text_field( wp_unslash( $_POST['data']['dismiss-option'] ) );
			}

			$type = '';
			if ( isset( $_POST['data']['dismissType'] ) ) {
				$type = sanitize_text_field( wp_unslash( $_POST['data']['dismissType'] ) );
			} elseif ( isset( $_POST['data']['dismiss-type'] ) ) {
				$type = sanitize_text_field( wp_unslash( $_POST['data']['dismiss-type'] ) );
			}
			// On dismiss disable data notice.
			if ( 'awb_site_data_notice' === $option && 'awb_option' === $type ) {
				update_option(
					'awb_site_data_status',
					[
						'status' => 'dismissed',
						'date'   => '',
					] 
				);
				$fusion_builder_settings['site_data_consent'] = 'off';
				update_option( 'fusion_builder_settings', $fusion_builder_settings );
			}
		}
		wp_die();
	}
	/**
	 * Creates or returns an instance of this class.
	 *
	 * @static
	 * @access public
	 * @since 7.10
	 */
	public static function get_instance() {

		// If an instance hasn't been created and set to $instance create an instance and set it to $instance.
		if ( null === self::$instance ) {
			self::$instance = new AWB_Site_Data();
		}
		return self::$instance;
	}
}
/**
 * Instantiates the user site data class.
 * Make sure the class is properly set-up.
 *
 * @since object 7.10
 * @return object AWB_Site_Data
 */
function AWB_User_Site_Data() { // phpcs:ignore WordPress.NamingConventions
	return AWB_Site_Data::get_instance();
}
AWB_User_Site_Data();
/* Omit closing PHP tag to avoid "Headers already sent" issues. */
