<?php
/**
 * RevSlider template.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      5.1
 */

// Live editor Post Options rendering.
if ( wp_doing_ajax() ) {
	global $SR_GLOBALS;
	$SR_GLOBALS['loaded_by_editor'] = true;
}

if ( function_exists( 'add_revslider' ) ) {
	add_revslider( $name );
} elseif ( function_exists( 'rev_slider_shortcode' ) ) {
	echo do_shortcode( '[rev_slider alias="' . $name . '" /]' );
}

if ( wp_doing_ajax() ) {
	$SR_GLOBALS['loaded_by_editor'] = false;
}
