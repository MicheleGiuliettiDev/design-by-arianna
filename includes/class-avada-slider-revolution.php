<?php
/**
 * Handles Slider Revolution relevant aspects.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      6.0
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Handles Slider Revolution relevant aspects.
 */
class Avada_Slider_Revolution {

	/**
	 * Constructor.
	 *
	 * @access  public
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'add_custom_slider_styles' ] );
		add_action( 'admin_init', [ $this, 'disable_slider_revolution_notice' ] );

		// Live editor Element rendering.
		add_action( 'awb_before_ajax_shortcode_render', [ $this, 'set_sr_globals_loaded_by_editor' ] );
		add_action( 'awb_after_ajax_shortcode_render', [ $this, 'reset_sr_globals_loaded_by_editor' ] );

		add_filter( 'revslider_get_slider_wrapper_div', [ $this, 'open_slider_wrapper' ] );
		add_filter( 'revslider_close_slider_wrapper_div', [ $this, 'close_slider_wrapper' ] );

		add_action( 'revslider_add_slider_to_stage_post', [ $this, 'maybe_close_slider_wrapper' ], 10, 2 );
	}

	/**
	 * Set the loaded_by_editor var of the $SR_GLOBALS.
	 *
	 * @access public
	 * @since 7.11.10
	 * @param string $shortcode The name of the rendered shortcode.
	 * @return void
	 */
	public function set_sr_globals_loaded_by_editor( $shortcode ) {
		if ( false !== strpos( $shortcode, 'rev_slider' ) ) { 
			global $SR_GLOBALS;
			$SR_GLOBALS['loaded_by_editor'] = true;
		}
	}

	/**
	 * Reet the loaded_by_editor var of the $SR_GLOBALS.
	 *
	 * @access public
	 * @since 7.11.10
	 * @param string $shortcode The name of the rendered shortcode.
	 * @return void
	 */
	public function reset_sr_globals_loaded_by_editor( $shortcode ) {
		if ( false !== strpos( $shortcode, 'rev_slider' ) ) { 
			global $SR_GLOBALS;
			$SR_GLOBALS['loaded_by_editor'] = false;
		}
	}

	/**
	 * Add custom slider revolution styles.
	 *
	 * @access public
	 * @since 6.0
	 * @return void
	 */
	public function add_custom_slider_styles() {
		global $wpdb; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		if ( defined( 'RS_REVISION' ) && get_option( 'avada_revslider_version' ) !== RS_REVISION ) {
			$table_name = $wpdb->prefix . 'revslider_css';

			$old_styles = [ '.avada_huge_white_text', '.avada_huge_black_text', '.avada_big_black_text', '.avada_big_white_text', '.avada_big_black_text_center', '.avada_med_green_text', '.avada_small_gray_text', '.avada_small_white_text', '.avada_block_black', '.avada_block_green', '.avada_block_white', '.avada_block_white_trans' ];

			foreach ( $old_styles as $handle ) {
				$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$table_name,
					[
						'handle' => '.tp-caption' . $handle,
					]
				);
			}

			$styles = [
				'.tp-caption.avada_huge_white_text'       => '{"position":"absolute","color":"#ffffff","font-size":"130px","line-height":"45px","font-family":"museoslab500regular"}',
				'.tp-caption.avada_huge_black_text'       => '{"position":"absolute","color":"#000000","font-size":"130px","line-height":"45px","font-family":"museoslab500regular"}',
				'.tp-caption.avada_big_black_text'        => '{"position":"absolute","color":"#333333","font-size":"42px","line-height":"45px","font-family":"museoslab500regular"}',
				'.tp-caption.avada_big_white_text'        => '{"position":"absolute","color":"#fff","font-size":"42px","line-height":"45px","font-family":"museoslab500regular"}',
				'.tp-caption.avada_big_black_text_center' => '{"position":"absolute","color":"#333333","font-size":"38px","line-height":"45px","font-family":"museoslab500regular","text-align":"center"}',
				'.tp-caption.avada_med_green_text'        => '{"position":"absolute","color":"#65bc7b","font-size":"24px","line-height":"24px","font-family":"PTSansRegular, Arial, Helvetica, sans-serif"}',
				'.tp-caption.avada_small_gray_text'       => '{"position":"absolute","color":"#747474","font-size":"13px","line-height":"20px","font-family":"PTSansRegular, Arial, Helvetica, sans-serif"}',
				'.tp-caption.avada_small_white_text'      => '{"position":"absolute","color":"#fff","font-size":"13px","line-height":"20px","font-family":"PTSansRegular, Arial, Helvetica, sans-serif","text-shadow":"0px 2px 5px rgba(0, 0, 0, 0.5)","font-weight":"700"}',
				'.tp-caption.avada_block_black'           => '{"position":"absolute","color":"#65bc7b","text-shadow":"none","font-size":"22px","line-height":"34px","padding":["1px", "10px", "0px", "10px"],"margin":"0px","border-width":"0px","border-style":"none","background-color":"#000","font-family":"PTSansRegular, Arial, Helvetica, sans-serif"}',
				'.tp-caption.avada_block_green'           => '{"position":"absolute","color":"#000","text-shadow":"none","font-size":"22px","line-height":"34px","padding":["1px", "10px", "0px", "10px"],"margin":"0px","border-width":"0px","border-style":"none","background-color":"#65bc7b","font-family":"PTSansRegular, Arial, Helvetica, sans-serif"}',
				'.tp-caption.avada_block_white'           => '{"position":"absolute","color":"#fff","text-shadow":"none","font-size":"22px","line-height":"34px","padding":["1px", "10px", "0px", "10px"],"margin":"0px","border-width":"0px","border-style":"none","background-color":"#000","font-family":"PTSansRegular, Arial, Helvetica, sans-serif"}',
				'.tp-caption.avada_block_white_trans'     => '{"position":"absolute","color":"#fff","text-shadow":"none","font-size":"22px","line-height":"34px","padding":["1px", "10px", "0px", "10px"],"margin":"0px","border-width":"0px","border-style":"none","background-color":"rgba(0, 0, 0, 0.6)","font-family":"PTSansRegular, Arial, Helvetica, sans-serif"}',
			];

			foreach ( $styles as $handle => $params ) {
				$query_id = md5( maybe_serialize( $params ) );
				$test     = wp_cache_get( $query_id, 'avada_revslider_styles' );
				if ( false === $test ) {
					$test = $wpdb->get_var( $wpdb->prepare( "SELECT handle FROM {$wpdb->prefix}revslider_css WHERE handle = %s", $handle ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					wp_cache_set( $query_id, $test, 'avada_revslider_styles' );
				}

				if ( $test != $handle ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					$wpdb->replace( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$table_name,
						[
							'handle'   => $handle,
							'params'   => $params,
							'settings' => '{"hover":"false","type":"text","version":"custom","translated":"5"}',
						],
						[
							'%s',
							'%s',
							'%s',
						]
					);
				}
			}
			update_option( 'avada_revslider_version', RS_REVISION );
		}
	}

	/**
	 * Disable the slider notice.
	 *
	 * @access public
	 * @since 6.0
	 * @return void
	 */
	public function disable_slider_revolution_notice() {
		update_option( 'revslider-valid-notice', 'false' );
	}

	/**
	 * Adds the slider wrapper open part to the slider markup.
	 *
	 * @static
	 * @access public
	 * @since 6.0
	 * @param string $slider_markup The slider markup.
	 * @return string The wrapped slider markup.
	 */
	public function open_slider_wrapper( $slider_markup ) {
		return '<div class="fusion-slider-revolution rev_slider_wrapper">' . $slider_markup;
	}

	/**
	 * Adds the slider wrapper close part to the slider markup.
	 *
	 * @static
	 * @access public
	 * @since 6.0
	 * @param string $slider_markup The slider markup.
	 * @return string The wrapped slider markup.
	 */
	public function close_slider_wrapper( $slider_markup ) {
		return $slider_markup . '</div>';
	}

	/**
	 * If no slides can be found an exception is thrown, thus close_slider_wrapper() is never reached and
	 * this function needs to close the wrapping div.
	 *
	 * @access public
	 * @since 8.0
	 * @param string          $slider_id            The slider ID.
	 * @param RevSliderOutput $slider_output_object The Slider Revolution output class.
	 * @return void
	 */
	public function maybe_close_slider_wrapper( $slider_id, $slider_output_object ) {
		if ( ! $slider_output_object->rs_module_closed ) {
			echo '</div>';
		}
	}

	/**
	 * Get the slider ID by alias.
	 *
	 * @static
	 * @access public
	 * @since 6.0
	 * @param string $alias The slider name.
	 * @return int The slider ID.
	 */
	public static function get_slider_id_by_alias( $alias ) {
		$slider_id = '';
		if ( class_exists( 'RevSliderSlider' ) ) {
			$slider_object = new RevSliderSlider();
			if ( method_exists( 'RevSliderSlider', 'check_alias' ) ) {
				if ( $slider_object->check_alias( $alias ) ) {
					$slider_object->init_by_alias( $alias );
					$slider_id = $slider_object->get_id();
				}
			} else { // Slider Revolution below 6.0.
				if ( $slider_object->isAliasExistsInDB( $alias ) ) {
					$slider_object->initByAlias( $alias );
					$slider_id = $slider_object->getID();
				}
			}
		}

		return $slider_id;

	}

}
