<?php
/**
 * Default Events Template
 * This file is the basic wrapper template for all the views if 'Default Events Template'
 * is selected in Events -> Settings -> Template -> Events Template.
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/default-template.php
 *
 * @package TribeEventsCalendar
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

get_header();
?>
<section id="content" style="<?php esc_attr_e( apply_filters( 'awb_content_tag_style', '' ) ); ?>">
	<div id="tribe-events-pg-template">
		<?php if ( ( function_exists( 'Fusion_Template_Builder' ) && Fusion_Template_Builder()->get_override( 'content' ) ) ) : ?>
			<?php tribe_get_view( 'single-event' ); ?>
		<?php else: ?>
			<?php tribe_events_before_html(); ?>
			<?php tribe_get_view( 'single-event' ); ?>
			<?php tribe_events_after_html(); ?>
		<?php endif; ?>	
	</div> <!-- #tribe-events-pg-template -->
</section>
<?php do_action( 'avada_after_content' ); ?>
<?php
get_footer();
