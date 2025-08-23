<?php
/**
 * ToTop template.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

$is_builder = ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) || ( function_exists( 'fusion_is_builder_frame' ) && fusion_is_builder_frame() );

if ( 'off' !== Avada()->settings->get( 'status_totop' ) || $is_builder ) {
	$to_top_position       = explode( '_', Avada()->settings->get( 'totop_position' ) );
	$to_top_position       = isset( $to_top_position[1] ) ? 'to-top-' . $to_top_position[0] . ' to-top-' . $to_top_position[1] : 'to-top-' . $to_top_position[0];
	$totop_scroll_progress = Avada()->settings->get( 'totop_scroll_progress' ) && false !== strpos( Avada()->settings->get( 'totop_position' ), 'floating' ) ? ' awb-to-top-scroll-progress' : '';
	?>
	<section class="to-top-container <?php echo esc_attr( $to_top_position ); ?><?php echo esc_attr( $totop_scroll_progress ); ?>" aria-labelledby="awb-to-top-label">
		<a href="#" id="toTop" class="fusion-top-top-link">
			<span id="awb-to-top-label" class="screen-reader-text"><?php esc_html_e( 'Go to Top', 'Avada' ); ?></span>

			<?php if ( ( Avada()->settings->get( 'totop_scroll_progress' ) && false !== strpos( Avada()->settings->get( 'totop_position' ), 'floating' ) ) || $is_builder ) : ?>
			<svg class="awb-to-top-progress" xmlns="http://www.w3.org/2000/svg" width="48.4" height="48.4">
				<rect class="awb-scale" style="stroke:<?php echo esc_attr( Fusion_Helper::fusion_auto_calculate_accent_color( Avada()->settings->get( 'totop_background' ) ) ); ?>;" width="44" height="44" x="2" y="2"></rect>
				<rect class="awb-progress" width="44" height="44" x="2" y="2"></rect>
			</svg>
			<?php endif; ?>
		</a>
	</section>
	<?php
}
