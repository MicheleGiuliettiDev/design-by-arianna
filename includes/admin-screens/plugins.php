<?php
/**
 * Plugins Admin page.
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

if ( ! function_exists( 'get_plugins' ) ) {
	require_once wp_normalize_path( ABSPATH . 'wp-admin/includes/plugin.php' );
}
$plugins                         = Avada_TGM_Plugin_Activation::$instance->plugins; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
$installed_plugins               = get_plugins();
$wp_api_plugins                  = get_site_transient( 'fusion_wordpress_org_plugins' );
$required_and_recommened_plugins = avada_get_required_and_recommened_plugins();

if ( ! function_exists( 'plugins_api' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // For plugins_api.
}
if ( ! $wp_api_plugins ) {
	$wp_org_plugins = [
		'pwa'                 => 'pwa/pwa.php',
		'woocommerce'         => 'woocommerce/woocommerce.php',
		'the-events-calendar' => 'the-events-calendar/the-events-calendar.php',
		'wordpress-seo'       => 'wordpress-seo/wp-seo.php',
		'leadin'              => 'leadin/leadin.php',
		'bbpress'             => 'bbpress/bbpress.php',
	];
	$wp_api_plugins = [];
	foreach ( $wp_org_plugins as $slug => $path ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$wp_api_plugins[ $slug ] = (array) plugins_api(
			'plugin_information',
			[
				'slug' => $slug,
			]
		);
		unset( $wp_api_plugins[ $slug ]['contributors'] );
		unset( $wp_api_plugins[ $slug ]['sections'] );
	}
	set_site_transient( 'fusion_wordpress_org_plugins', $wp_api_plugins, 15 * MINUTE_IN_SECONDS );
}
?>
<?php self::get_admin_screens_header( 'plugins' ); ?>
	<?php add_thickbox(); ?>

	<section class="avada-db-card avada-db-card-first avada-db-plugins-start">
		<h1 class="avada-db-demos-heading"><?php esc_html_e( 'Manage Bundled, Premium & Recommended Plugins', 'Avada' ); ?></h1>
		<p>
			<?php
			printf(
				/* translators: The "Product Registration" link. */
				__( 'Avada Core and Avada Builder are required plugins for the Avada Website Builder. Avada Custom Branding, Convert Plus, ACF Pro, Slider Revolution & Layer Slider are premium plugins that can be installed once your <a href="%s">product is registered</a>.', 'Avada' ), // phpcs:ignore WordPress.Security.EscapeOutput
				esc_url( admin_url( 'admin.php?page=avada' ) )
			);
			?>
		</p>

		<div class="avada-db-card-notice">
			<i class="fusiona-info-circle"></i>
			<p class="avada-db-card-notice-heading">
				<?php esc_html_e( 'Before updating premium plugins, please ensure Avada is on the latest version. The recommended plugins below offer design integration with Avada. You can manage the plugins from this tab.', 'Avada' ); ?>
			</p>
		</div>
	</section>
	<?php if ( ! Avada()->registration->should_show( 'plugins' ) ) : ?>
		<div class="avada-db-card avada-db-notice">
			<h2><?php esc_html_e( 'Premium Plugins Can Only Be Installed And Updated With Valid Product Registration', 'Avada' ); ?></h2>
			<?php /* translators: "Product Registration" link. */ ?>
			<p><?php printf( esc_html__( 'Please visit the %s page and enter a purchase code to install or update the premium plugins: Avada Core, Avada Builder, Avada Custom Branding, Convert Plus, ACF Pro, Slider Revolution & Layer Slider.', 'Avada' ), '<a href="' . esc_url_raw( admin_url( 'admin.php?page=avada#avada-db-registration' ) ) . '">' . esc_attr__( 'Product Registration', 'Avada' ) . '</a>' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( empty( $plugins ) ) : ?>
		<section class="avada-db-card avada-db-notice">
			<h2><?php esc_html_e( 'The Plugin Server Could Not Be Reached', 'Avada' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %1$s = Status text & link. %2$s: Plugin Installation text & link. %3$s: Support Dashboard text & link. */
					esc_attr__( 'Please check on the %1$s page if wp_remote_get() is working. For more information you can check our documentation of the %2$s. If the issue persists, you can also get the plugins through our alternate method directly from the %3$s.', 'Avada' ),
					'<a href="' . esc_url_raw( admin_url( 'admin.php?page=avada-status' ) ) . '" target="_blank">' . esc_attr__( 'Status', 'Avada' ) . '</a>',
					'<a href="https://avada.com/documentation/plugin-installation-and-maintenance/" target="_blank">' . esc_attr__( 'Plugin Installation', 'Avada' ) . '</a>',
					'<a href="https://my.avada.com/licenses/" target="_blank">' . esc_attr__( 'Support Dashboard', 'Avada' ) . '</a>'
				);
				?>
			</p>
		</section>
	<?php endif; ?>

	<section id="avada-install-plugins" class="avada-db-plugins-themes avada-install-plugins avada-db-card">
		<div class="feature-section theme-browser rendered">

			<?php foreach ( $plugins as $plugin_args ) : ?>
				<?php
				if ( ! isset( $plugin_args['AuthorURI'] ) ) {
					$plugin_args['AuthorURI'] = '#';
				}
				if ( ! isset( $plugin_args['Author'] ) ) {
					$plugin_args['Author'] = '';
				}
				if ( ! array_key_exists( $plugin_args['slug'], $required_and_recommened_plugins ) ) {
					continue;
				}

				$class         = '';
				$plugin_status = '';
				$file_path     = $plugin_args['file_path'];
				$plugin_action = $this->plugin_link( $plugin_args );

				// We have a repo plugin.
				if ( ! $plugin_args['version'] ) {
					$plugin_args['version'] = Avada_TGM_Plugin_Activation::$instance->does_plugin_have_update( $plugin_args['slug'] );
				}

				if ( fusion_is_plugin_activated( $file_path ) ) {
					$plugin_status = 'active';
					$class         = 'active';
				}

				if ( isset( $plugin_action['update'] ) && $plugin_action['update'] ) {
					$class .= ' update';
				}

				$required_premium = '';
				?>
				<div class="fusion-admin-box">
					<div class="theme <?php echo esc_attr( $class ); ?>">
						<div class="theme-wrapper">
							<div class="theme-screenshot">
								<img src="<?php echo esc_url( $plugin_args['image'] ); ?>" alt="<?php esc_attr( $plugin_args['plugin_name'] ); ?>" />
							</div>
							<?php if ( isset( $plugin_action['update'] ) && $plugin_action['update'] ) : ?>
								<div class="update-message notice inline notice-warning notice-alt">
									<?php /* translators: Version number. */ ?>
									<p><?php printf( esc_html__( 'New version available: %s', 'Avada' ), esc_html( $plugin_args['version'] ) ); ?></p>
								</div>
							<?php endif; ?>
							<h3 class="theme-name">
								<?php if ( 'active' === $plugin_status ) : ?>
									<?php /* translators: plugin name. */ ?>
									<span><?php printf( esc_html__( 'Active: %s', 'Avada' ), esc_html( $plugin_args['plugin_name'] ) ); ?></span>
								<?php else : ?>
									<?php echo esc_html( $plugin_args['plugin_name'] ); ?>
								<?php endif; ?>
								<div class="plugin-info">
									<?php if ( isset( $installed_plugins[ $plugin_args['file_path'] ] ) ) : ?>
										<?php /* translators: %1$s: Plugin version. %2$s: Author URL. %3$s: Author Name. */ ?>
										<?php printf( __( 'v%1$s | <a href="%2$s" target="_blank">%3$s</a>', 'Avada' ), esc_html( $installed_plugins[ $plugin_args['file_path'] ]['Version'] ), esc_url_raw( $installed_plugins[ $plugin_args['file_path'] ]['AuthorURI'] ), esc_html( $installed_plugins[ $plugin_args['file_path'] ]['Author'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<?php elseif ( 'fusion-builder' === $plugin_args['slug'] || 'fusion-core' === $plugin_args['slug'] ) : ?>
										<?php /* translators: Version number. */ ?>
										<?php printf( esc_html__( 'Available Version: %s', 'Avada' ), esc_html( $plugin_args['version'] ) ); ?>
									<?php else : ?>
										<?php
										$version = ( isset( $plugin_args['version'] ) ) ? $plugin_args['version'] : false;
										$version = ( isset( $wp_api_plugins[ $plugin_args['slug'] ] ) && isset( $wp_api_plugins[ $plugin_args['slug'] ]['version'] ) ) ? $wp_api_plugins[ $plugin_args['slug'] ]['version'] : $version;
										$author  = ( $plugin_args['Author'] && $plugin_args['AuthorURI'] ) ? "<a href='{$plugin_args['AuthorURI']}' target='_blank'>{$plugin_args['Author']}</a>" : false;
										$author  = ( isset( $wp_api_plugins[ $plugin_args['slug'] ] ) && isset( $wp_api_plugins[ $plugin_args['slug'] ]['author'] ) ) ? $wp_api_plugins[ $plugin_args['slug'] ]['author'] : $author;
										?>
										<?php if ( $version && $author ) : ?>
											<?php echo ( is_rtl() ) ? "$author | v$version" : "v$version | $author"; // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<?php endif; ?>
									<?php endif; ?>
								</div>
							</h3>
							<div class="theme-actions">
								<?php foreach ( $plugin_action as $action ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride ?>
									<?php
									// Sanitization is already taken care of in Avada_Admin class.
									// No need to re-sanitize it...
									echo $action; // phpcs:ignore WordPress.Security.EscapeOutput
									?>
								<?php endforeach; ?>
							</div>
							<?php if ( $plugin_args['required'] ) : ?>
								<?php $required_premium = ' plugin-required-premium'; ?>
								<div class="plugin-required">
									<?php esc_html_e( 'Required', 'Avada' ); ?>
								</div>
							<?php endif; ?>

							<?php if ( $plugin_args['premium'] ) : ?>
								<div class="plugin-premium<?php echo esc_attr( $required_premium ); ?>">
									<?php esc_html_e( 'Premium', 'Avada' ); ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div id="dialog-plugin-confirm" title="<?php esc_attr_e( 'Error ', 'Avada' ); ?>"></div>
	</section>
<?php $this->get_admin_screens_footer(); ?>
