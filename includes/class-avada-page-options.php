<?php
/**
 * Adds Page Options import / export feature.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      5.3
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Adds Page Options import / export feature.
 */
class Avada_Page_Options {

	/**
	 * WP Filesystem object.
	 *
	 * @access private
	 * @since 5.3
	 * @var object
	 */
	private $wp_filesystem;

	/**
	 * Page Options Export directory path.
	 *
	 * @access private
	 * @since 5.3
	 * @var string
	 */
	private $po_dir_path;

	/**
	 * Page Options Export URL.
	 *
	 * @access private
	 * @since 5.3
	 * @var string
	 */
	private $po_dir_url;

	/**
	 * The class constructor.
	 *
	 * @access public
	 * @since 5.3
	 */
	public function __construct() {

		$this->wp_filesystem = Fusion_Helper::init_filesystem();

		$upload_dir        = wp_upload_dir();
		$this->po_dir_path = wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) . 'fusion-page-options-export/' );
		$this->po_dir_url  = trailingslashit( $upload_dir['baseurl'] ) . 'fusion-page-options-export/';

		add_filter( 'avada_metabox_tabs', [ $this, 'add_options_tab' ], 10, 2 );
		add_action( 'init', [ $this, 'export_options' ] );
		add_action( 'wp_ajax_fusion_page_options_import', [ $this, 'ajax_import_options' ] );
		add_action( 'wp_ajax_fusion_page_options_save', [ $this, 'ajax_save_options_dataset' ] );
		add_action( 'wp_ajax_fusion_page_options_delete', [ $this, 'ajax_delete_options_dataset' ] );
		add_action( 'wp_ajax_fusion_page_options_import_saved', [ $this, 'ajax_import_options_saved' ] );

		// Live Builder.
		if ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) {
			add_filter( 'fusion_app_preview_data', [ $this, 'add_preview_data' ], 10 );
		}

		// Back-end search query for select fields.
		add_action( 'wp_ajax_fusion_search_query', [ $this, 'search_query' ] );

		// If we have URL param then run migration.
		if ( isset( $_GET['force-migrate-po'] ) && $_GET['force-migrate-po'] ) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			add_action( 'wp', [ $this, 'trigger_migration' ] );
		}

		// Add code field filters.
		add_filter( 'fusion_google_analytics', [ $this, 'add_po_tracking_code' ], 15 );
		add_filter( 'avada_space_head', [ $this, 'add_po_space_head_close' ], 15 );
		add_filter( 'awb_space_body_open', [ $this, 'add_po_space_body_open' ], 15 );
		add_filter( 'awb_space_body_close', [ $this, 'add_po_space_body_close' ], 15 );
	}

	/**
	 * Return the search query.
	 *
	 * @access public
	 * @since 6.2.0
	 * @return array|void
	 */
	public function search_query() {
		$req_method  = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$req         = $GLOBALS[ '_' . $req_method ];
		$return_data = [
			'results' => [],
			'labels'  => [],
		];

		// Check nonce.
		if ( isset( $req['fusion_load_nonce'] ) ) {
			check_ajax_referer( 'fusion_load_nonce', 'fusion_load_nonce' );
		} else {
			check_ajax_referer( 'fusion-page-options-nonce', 'fusion_po_nonce' );
		}

		$params    = isset( $req['params'] ) ? $req['params'] : [];
		$use_slugs = ( isset( $params['use_slugs'] ) && true === filter_var( $params['use_slugs'], FILTER_VALIDATE_BOOLEAN ) ) ? true : false;

		// Do search query.
		if ( isset( $req['search'] ) ) {
			$search = trim( sanitize_text_field( wp_unslash( $req['search'] ) ) );

			// Search for all terms of all taxonomies.
			if ( isset( $params['all_terms'] ) ) {
				$taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );

				foreach ( $taxonomies  as $taxonomy ) {
					$terms = get_terms(
						[
							'taxonomy'   => $taxonomy->name,
							'hide_empty' => false,
							'name__like' => $search,
						]
					);

					foreach ( $terms as $term ) {
						$return_data['results'][] = [
							'id'   => $use_slugs ? urldecode( $taxonomy->name ) . '|' . urldecode( $term->slug ) : urldecode( $taxonomy->name ) . '|' . $term->term_id,
							'text' => $taxonomy->labels->name . ': ' . $term->name,
						];
					}
				}
			} else {

				// Terms search.
				if ( isset( $params['taxonomy'] ) ) {
					$terms = get_terms(
						[
							'taxonomy'   => $params['taxonomy'],
							'hide_empty' => false,
							'name__like' => $search,
						]
					);
					foreach ( $terms as $term ) {
						$return_data['results'][] = [
							'id'   => $use_slugs ? urldecode( $term->slug ) : $term->term_id,
							'text' => $term->name,
						];
					}
				}

				// Post types search.
				if ( isset( $params['post_type'] ) ) {
					$args           = [
						's'         => $search, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
						'post_type' => $params['post_type']['name'],
					];
					$search_results = fusion_cached_query( $args );
					if ( $search_results->have_posts() ) {
						global $post;
						while ( $search_results->have_posts() ) {
							$search_results->the_post();
							$return_data['results'][] = [
								'id'   => esc_attr( $post->ID ),
								'text' => esc_html( get_the_title( $post->ID ) ),
							];
						}
					}
				}
			}
		}

		// Get labels.
		if ( isset( $req['labels'] ) ) {
			$labels = $req['labels']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

			// Search for all terms of all taxonomies.
			if ( isset( $params['all_terms'] ) ) {
				foreach ( $labels as $key => $label_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					$taxonomy_and_term = explode( '|', $label_id );

					$taxonomy = get_taxonomy( $taxonomy_and_term[0] );

					$term = $use_slugs ? get_term_by( 'slug', $taxonomy_and_term[1], $taxonomy_and_term[0] ) : get_term( $taxonomy_and_term[1], $taxonomy_and_term[0] );

					if ( ! is_object( $term ) ) {
						continue;
					}

					$return_data['labels'][] = [
						'id'   => $label_id,
						'text' => $taxonomy->labels->name . ': ' . $term->name,
					];
				}
			}

			// Terms search.
			if ( isset( $params['taxonomy'] ) ) {
				foreach ( $labels as $key => $label_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					if ( $use_slugs ) {
						$term = get_term_by( 'slug', $label_id, $params['taxonomy'] );
						if ( ! is_object( $term ) ) {
							continue;
						}

						$return_data['labels'][] = [
							'id'   => $label_id,
							'text' => $term->name,
						];
					} else {
						$return_data['labels'][] = [
							'id'   => $label_id,
							'text' => get_term( $label_id, $params['taxonomy'] )->name,
						];
					}
				}
			}
			// Post types search.
			if ( isset( $params['post_type'] ) ) {
				foreach ( $labels as $key => $label_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					$return_data['labels'][] = [
						'id'   => $label_id,
						'text' => get_the_title( $label_id ),
					];
				}
			}
		}

		echo wp_json_encode( $return_data );
		wp_die();
	}

	/**
	 * Adds Page Options Tab
	 *
	 * @access public
	 * @since 5.3
	 * @param array  $tabs      The requested tabs.
	 * @param string $post_type Post type.
	 * @return array
	 */
	public function add_options_tab( $tabs, $post_type ) {

		$tab_key  = 'avada_page_options';
		$tab_name = esc_html__( 'Import / Export', 'Avada' );

		$tabs['requested_tabs'][]       = $tab_key;
		$tabs['tabs_names'][ $tab_key ] = $tab_name;
		$tabs['tabs_path'][ $tab_key ]  = Avada::$template_dir_path . '/includes/metaboxes/tabs/tab_' . $tab_key . '.php';

		return $tabs;
	}

	/**
	 * AJAX callback function. Used to export Page Options.
	 *
	 * @access public
	 * @since 5.3
	 * @return void
	 */
	public function export_options() {

		if ( ! isset( $_GET['action'] ) || 'download-avada-po' !== $_GET['action'] ) { // phpcs:ignore WordPress.Security
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) ) ) {
			wp_die();
		}

		$post_id = 0;
		if ( isset( $_GET['post_id'] ) ) {
			$post_id = absint( $_GET['post_id'] );
		}

		header( 'Content-Description: File Transfer' );
		header( 'Content-type: application/txt' );
		header( 'Content-Disposition: attachment; filename="avada-options-' . $post_id . '-' . date( 'd-m-Y' ) . '.json"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );

		echo wp_json_encode( $this->get_avada_post_custom_fields( $post_id ) );
		wp_die();
	}

	/**
	 * Gets all Avada's custom fields for specified post.
	 *
	 * @access private
	 * @since 5.3
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function get_avada_post_custom_fields( $post_id ) {
		return fusion_data()->post_meta( $post_id )->get_all_meta();
	}

	/**
	 * AJAX callback function. Used to import Page Options.
	 *
	 * @access public
	 * @since 5.3
	 * @return void
	 */
	public function ajax_import_options() {

		check_ajax_referer( 'fusion-page-options-nonce', 'fusion_po_nonce' );
		$response = [];

		if ( ! isset( $_FILES['po_file_upload']['name'] ) || ! isset( $_FILES['po_file_upload']['tmp_name'] ) ) {
			wp_die();
		}

		$file_type       = wp_check_filetype_and_ext( $_FILES['po_file_upload']['tmp_name'], $_FILES['po_file_upload']['name'] );
		$proper_filename = $file_type['proper_filename'] ? $file_type['proper_filename'] : $_FILES['po_file_upload']['tmp_name'];

		if ( 'json' !== $file_type['ext'] ) {
			wp_die();
		}

		$content_json = $this->wp_filesystem->get_contents( $proper_filename );

		$custom_fields = json_decode( $content_json, true );
		if ( $custom_fields ) {
			$response['custom_fields'] = $custom_fields;
		}

		echo wp_json_encode( $response );
		wp_die();
	}

	/**
	 * AJAX callback function. Used to save Page Options.
	 *
	 * @access public
	 * @since 5.9.2
	 * @return void
	 */
	public function ajax_save_options_dataset() {

		check_ajax_referer( 'fusion-page-options-nonce', 'fusion_po_nonce' );

		$post_id       = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
		$options_title = isset( $_GET['options_title'] ) ? sanitize_text_field( wp_unslash( $_GET['options_title'] ) ) : '';

		if ( isset( $_GET['custom_fields'] ) ) {
			$custom_fields = $_GET['custom_fields']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		} else {
			$custom_fields = $this->get_avada_post_custom_fields( $post_id );
		}

		$new_item = $this->insert_options_dataset( $options_title, $custom_fields );

		echo wp_json_encode(
			[
				'saved_po_dataset_id'    => $new_item['id'],
				'saved_po_dataset_title' => $new_item['title'],
				'saved_po_data'          => $new_item['data'],
			]
		);
		wp_die();
	}

	/**
	 * AJAX callback function. Used to delete Page Options.
	 *
	 * @access public
	 * @since 5.9.2
	 * @return void
	 */
	public function ajax_delete_options_dataset() {

		check_ajax_referer( 'fusion-page-options-nonce', 'fusion_po_nonce' );

		$saved_po_dataset_id = 0;
		if ( isset( $_GET['saved_po_dataset_id'] ) ) {
			$saved_po_dataset_id = sanitize_text_field( wp_unslash( $_GET['saved_po_dataset_id'] ) );
		}

		$this->delete_options_dataset( $saved_po_dataset_id );
		wp_die();
	}

	/**
	 * Creates new post with custom fields.
	 *
	 * @access private
	 * @since 5.9.2
	 * @param string $options_title Name of the options to be saved.
	 * @param array  $custom_fields Array of custom fields to be saved.
	 * @return array                Returns details of the saved dataset: ['id' =>'','title'=>'','data'=>[]].
	 */
	private function insert_options_dataset( $options_title = '', $custom_fields = [] ) {
		$all_options = get_option( 'avada_page_options', [] );

		if ( empty( $options_title ) ) {
			/* translators: Number. */
			$options_title = sprintf( __( 'Custom page options %d ', 'Avada' ), count( $all_options ) + 1 );
		}

		$id       = md5( $options_title . wp_json_encode( $custom_fields ) );
		$new_item = [
			'id'    => $id,
			'title' => $options_title,
			'data'  => $custom_fields,
		];

		$all_options[] = $new_item;
		update_option( 'avada_page_options', $all_options );
		return $new_item;
	}

	/**
	 * Deletes previously saved options-dataset.
	 *
	 * @access private
	 * @since 5.9.2
	 * @param string $id ID of options-dataset which needs to be deleted.
	 * @return void
	 */
	private function delete_options_dataset( $id = '' ) {
		$all_options = get_option( 'avada_page_options', [] );
		foreach ( $all_options as $key => $option ) {
			if ( isset( $option['id'] ) && $id === $option['id'] ) {
				unset( $all_options[ $key ] );
				update_option( 'avada_page_options', $all_options );
				return;
			}
		}
	}

	/**
	 * AJAX callback function. Used to import Page Options from previously saved set.
	 *
	 * @access public
	 * @since 5.3
	 * @return void
	 */
	public function ajax_import_options_saved() {

		check_ajax_referer( 'fusion-page-options-nonce', 'fusion_po_nonce' );

		$saved_po_dataset_id = '';
		if ( isset( $_GET['saved_po_dataset_id'] ) ) {
			$saved_po_dataset_id = sanitize_text_field( wp_unslash( $_GET['saved_po_dataset_id'] ) );
		}

		$custom_fields = $this->get_options_by_id( $saved_po_dataset_id );

		echo wp_json_encode(
			[
				'custom_fields' => $custom_fields,
			]
		);
		wp_die();

	}

	/**
	 * Gets page-options by page-options ID.
	 *
	 * @access public
	 * @since 5.9.2
	 * @param string $id The page-options ID.
	 * @return array
	 */
	public function get_options_by_id( $id = '' ) {
		$all_options = get_option( 'avada_page_options', [] );
		foreach ( $all_options as $option ) {
			if ( isset( $option['id'] ) && $id === $option['id'] && isset( $option['data'] ) ) {
				return $option['data'];
			}
		}
		return [];
	}

	/**
	 * Add necessary data for PO export / import.
	 *
	 * @access public
	 * @since 6.1
	 * @param  array $data The data already added.
	 * @return array $data The data with panel data added.
	 */
	public function add_preview_data( $data ) {
		$data['savedPageOptions'] = get_option( 'avada_page_options', [] );
		return $data;
	}

	/**
	 * Trigger migration manually from old to new.
	 *
	 * @access public
	 * @since 6.2.2
	 * @return void.
	 */
	public function trigger_migration() {
		if ( current_user_can( 'edit_published_pages' ) && class_exists( 'Fusion_Deprecate_Pyre_PO' ) ) {
			new Fusion_Deprecate_Pyre_PO();
		}
	}

	/**
	 * Add Po tracking code.
	 *
	 * @access public
	 * @since 7.11.14
	 * @param string $code The tracking code.
	 * @return string
	 */
	public function add_po_tracking_code( $code ) {	
		global $post;

		$po_code = isset( $post->ID ) ? fusion_data()->post_meta( $post->ID )->get( 'tracking_code' ) : '';

		return $code . $po_code;
	}

	/**
	 * Add Po before </head> code.
	 *
	 * @access public
	 * @since 7.11.14
	 * @param string $code The before </head> code.
	 * @return string
	 */
	public function add_po_space_head_close( $code ) {	
		global $post;

		$po_code = isset( $post->ID ) ? fusion_data()->post_meta( $post->ID )->get( 'space_head_close' ) : '';

		return $code . $po_code;
	}
	
	/**
	 * Add Po after <body> code.
	 *
	 * @access public
	 * @since 7.11.14
	 * @param string $code The tracking code.
	 * @return string
	 */
	public function add_po_space_body_open( $code ) {	
		global $post;

		$po_code = isset( $post->ID ) ? fusion_data()->post_meta( $post->ID )->get( 'space_body_open' ) : '';

		return $code . $po_code;
	}
	
	/**
	 * Add Po before </head> code.
	 *
	 * @access public
	 * @since 7.11.14
	 * @param string $code The tracking code.
	 * @return string
	 */
	public function add_po_space_body_close( $code ) {	
		global $post;

		$po_code = isset( $post->ID ) ? fusion_data()->post_meta( $post->ID )->get( 'space_body_close' ) : '';

		return $code . $po_code;
	}	
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
