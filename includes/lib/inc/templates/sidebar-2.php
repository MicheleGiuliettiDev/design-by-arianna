<?php
/**
 * Sidebar-2 template.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Fusion-Library
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}
$sidebar_classes = apply_filters( 'awb_aside_2_tag_class', '' );
$sticky_sidebar  = false !== strpos( $sidebar_classes, 'fusion-sticky-sidebar' );
?>
<aside id="sidebar-2" class="<?php echo esc_attr( $sidebar_classes ); ?>" style="<?php echo esc_attr( apply_filters( 'awb_aside_2_tag_style', '' ) ); ?>" data="<?php echo esc_attr( apply_filters( 'awb_aside_2_tag_data', '' ) ); ?>">
	<?php if ( $sticky_sidebar ) : ?>
		<div class="fusion-sidebar-inner-content">
	<?php endif; ?>
		<?php if ( 'right' === AWB_Widget_Framework()->sidebars['position'] ) : ?>
			<?php echo avada_display_sidenav( fusion_library()->get_page_id() ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php do_action( 'awb_tec_single_events_meta' ); ?>
		<?php endif; ?>

		<?php if ( isset( AWB_Widget_Framework()->sidebars['sidebar_2'] ) && AWB_Widget_Framework()->sidebars['sidebar_2'] ) : ?>
			<?php generated_dynamic_sidebar( AWB_Widget_Framework()->sidebars['sidebar_2'] ); ?>
		<?php endif; ?>
	<?php if ( $sticky_sidebar ) : ?>
		</div>
	<?php endif; ?>
</aside>
