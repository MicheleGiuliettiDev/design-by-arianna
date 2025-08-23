<?php
/**
 * Handles upgrades.
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

/**
 * Handle upgrades.
 */
class Avada_Upgrade {

	/**
	 * Instance.
	 *
	 * @static
	 * @access public
	 * @var null|object
	 */
	public static $instance = null;

	/**
	 * The theme version as stored in the db.
	 *
	 * @access private
	 * @var string
	 */
	private $database_theme_version;

	/**
	 * An array of previous versions.
	 *
	 * @access private
	 * @var array
	 */
	private $previous_theme_versions;

	/**
	 * The previouis version.
	 *
	 * @access private
	 * @var string
	 */
	private $previous_theme_version;

	/**
	 * The current version.
	 *
	 * @access private
	 * @var string
	 */
	private $current_theme_version;

	/**
	 * An array of all avada options.
	 *
	 * @access private
	 * @var array
	 */
	private $avada_options;

	/**
	 * The current User object.
	 *
	 * @access private
	 * @var object
	 */
	private $current_user;

	/**
	 * An array of all the already upgraded options.
	 *
	 * @access private
	 * @var array
	 */
	private static $upgraded_options = [];

	/**
	 * Constructor.
	 *
	 * @access private
	 */
	protected function __construct() {

		$this->previous_theme_versions = get_option( 'avada_previous_version', [] );
		// Previous version only really needed, because through the upgrade loop, the database_theme_version will be altered.
		$this->previous_theme_version = $this->get_previous_theme_version();
		$this->database_theme_version = get_option( 'avada_version', false );
		$this->database_theme_version = Avada_Helper::normalize_version( $this->database_theme_version );
		$this->current_theme_version  = Avada::get_theme_version();
		$this->current_theme_version  = Avada_Helper::normalize_version( $this->current_theme_version );

		// Check through all options names that were available for Global Options in databse.
		$theme_options = get_option( Avada::get_option_name(), get_option( 'avada_theme_options', get_option( 'Avada_options', false ) ) );

		// If no old version is in database and there are no saved options,
		// this is a new install, nothing to do, but to copy version to db.
		if ( false === $this->database_theme_version && ! $theme_options ) {
			$this->fresh_installation();
			return;

			// If on front-end and user intervention necessary, do not continue.
		} elseif ( ! is_admin() && version_compare( $this->database_theme_version, '5.0.1', '<' ) ) {
			return;
		}

		// Each version is defined as an array( 'Version', 'Force-Instantiation' ).
		$versions = [
			'385'   => [ '3.8.5', false ],
			'387'   => [ '3.8.7', false ],
			'390'   => [ '3.9.0', false ],
			'392'   => [ '3.9.2', false ],
			'400'   => [ '4.0.0', true ],
			'402'   => [ '4.0.2', false ],
			'403'   => [ '4.0.3', false ],
			'500'   => [ '5.0.0', true ],
			'503'   => [ '5.0.3', false ],
			'510'   => [ '5.1.0', false ],
			'516'   => [ '5.1.6', false ],
			'520'   => [ '5.2.0', false ],
			'521'   => [ '5.2.1', false ],
			'530'   => [ '5.3.0', false ],
			'540'   => [ '5.4.0', false ],
			'541'   => [ '5.4.1', false ],
			'542'   => [ '5.4.2', false ],
			'550'   => [ '5.5.0', false ],
			'551'   => [ '5.5.1', false ],
			'552'   => [ '5.5.2', false ],
			'560'   => [ '5.6.0', false ],
			'561'   => [ '5.6.1', false ],
			'562'   => [ '5.6.2', false ],
			'570'   => [ '5.7.0', false ],
			'571'   => [ '5.7.1', false ],
			'572'   => [ '5.7.2', false ],
			'580'   => [ '5.8.0', false ],
			'581'   => [ '5.8.1', false ],
			'582'   => [ '5.8.2', false ],
			'590'   => [ '5.9.0', false ],
			'591'   => [ '5.9.1', false ],
			'592'   => [ '5.9.2', false ],
			'600'   => [ '6.0.0', false ],
			'601'   => [ '6.0.1', false ],
			'602'   => [ '6.0.2', false ],
			'603'   => [ '6.0.3', false ],
			'610'   => [ '6.1.0', false ],
			'611'   => [ '6.1.1', false ],
			'612'   => [ '6.1.2', false ],
			'620'   => [ '6.2.0', false ],
			'621'   => [ '6.2.1', false ],
			'622'   => [ '6.2.2', false ],
			'623'   => [ '6.2.3', false ],
			'700'   => [ '7.0.0', false ],
			'701'   => [ '7.0.1', false ],
			'702'   => [ '7.0.2', false ],
			'710'   => [ '7.1.0', false ],
			'711'   => [ '7.1.1', false ],
			'712'   => [ '7.1.2', false ],
			'720'   => [ '7.2.0', false ],
			'721'   => [ '7.2.1', false ],
			'730'   => [ '7.3.0', false ],
			'731'   => [ '7.3.1', false ],
			'740'   => [ '7.4.0', false ],
			'741'   => [ '7.4.1', false ],
			'750'   => [ '7.5.0', false ],
			'760'   => [ '7.6.0', false ],
			'761'   => [ '7.6.1', false ],
			'762'   => [ '7.6.2', false ],
			'770'   => [ '7.7.0', false ],
			'771'   => [ '7.7.1', false ],
			'780'   => [ '7.8.0', false ],
			'781'   => [ '7.8.1', false ],
			'782'   => [ '7.8.2', false ],
			'790'   => [ '7.9.0', false ],
			'791'   => [ '7.9.1', false ],
			'792'   => [ '7.9.2', false ],
			'7100'  => [ '7.10.0', false ],
			'7101'  => [ '7.10.1', false ],
			'7110'  => [ '7.11.0', false ],
			'7111'  => [ '7.11.1', false ],
			'7112'  => [ '7.11.2', false ],
			'7113'  => [ '7.11.3', false ],
			'7114'  => [ '7.11.4', false ],
			'7115'  => [ '7.11.5', false ],
			'7116'  => [ '7.11.6', false ],
			'7117'  => [ '7.11.7', false ],
			'7118'  => [ '7.11.8', false ],
			'7119'  => [ '7.11.9', false ],
			'71110' => [ '7.11.10', false ],
			'71111' => [ '7.11.11', false ],
			'71112' => [ '7.11.12', false ],
			'71113' => [ '7.11.13', false ],
			'71114' => [ '7.11.14', false ],
			'71115' => [ '7.11.15', false ],
			'7120'  => [ '7.12.0', false ],
			'7121'  => [ '7.12.1', false ],
			'7122'  => [ '7.12.2', false ],
		];

		$upgraded = false;
		foreach ( $versions as $key => $version ) {

			$classname = 'Avada_Upgrade_' . $key;

			if ( $this->database_theme_version && version_compare( $this->database_theme_version, $version[0], '<' ) ) {
				$upgraded = true;
				// Instantiate the class if migration is needed.
				if ( class_exists( $classname ) ) {
					new $classname();
				}
			} elseif ( true === $version[1] ) {
				// Instantiate the class if force-instantiation is set to true.
				if ( class_exists( $classname ) ) {
					new $classname( true );
				}
			}
		}

		// Manual migration rerun.
		if ( is_admin() && current_user_can( 'switch_themes' ) && isset( $_GET['migrate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$classname = 'Avada_Upgrade_' . str_replace( '.', '', Avada_Helper::normalize_version( sanitize_text_field( wp_unslash( $_GET['migrate'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification

			if ( class_exists( $classname ) ) {
				new $classname( true );
			}
		}

		if ( true === $upgraded ) {
			// Reset all Fusion caches.
			if ( ! class_exists( 'Fusion_Cache' ) ) {
				include_once Avada::$template_dir_path . '/includes/lib/inc/class-fusion-cache.php';
			}

			$fusion_cache = new Fusion_Cache();
			$fusion_cache->reset_all_caches();
		}

		/**
		 * Don't do anything when in the Customizer.
		 */
		global $wp_customize;
		if ( $wp_customize ) {
			return;
		}

		add_action( 'init', [ $this, 'update_installation' ] );

		// The priority of this is best to be more than 10, because TGM plugin addon register the update hooks at priority 10.
		if ( $upgraded ) {
			add_action( 'init', [ $this, 'update_avada_needed_plugins' ], 20 );
		}
	}

	/**
	 * Make sure there's only 1 instance of this class running.
	 *
	 * @static
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Avada_Upgrade();
		}
		return self::$instance;
	}

	/**
	 * Get the previous theme version from database.
	 *
	 * @access public
	 * @return string The previous theme version.
	 */
	public function get_previous_theme_version() {
		if ( is_array( $this->previous_theme_versions ) && ! empty( $this->previous_theme_versions ) ) {
			$this->previous_theme_version = end( $this->previous_theme_versions );
			reset( $this->previous_theme_versions );
		} else {
			$this->previous_theme_version = $this->previous_theme_versions;
		}

		// Make sure the theme version has 3 digits.
		return Avada_Helper::normalize_version( $this->previous_theme_version );
	}

	/**
	 * Actions to run on a fresh installation.
	 */
	public function fresh_installation() {
		update_option( 'avada_version', $this->current_theme_version );
		set_transient( 'awb_fresh_install', 'fresh' );
		$this->clear_update_theme_transient();
	}

	/**
	 * Clear update_themes transient after update. Fix for #5048.
	 */
	public function clear_update_theme_transient() {
		delete_site_transient( 'update_themes' );
		delete_transient( 'avada_premium_plugins_info' );
		delete_site_transient( 'avada_premium_plugins_info' );
		Avada::$bundled_plugins = [];
	}

	/**
	 * Actions to run on an update installation.
	 *
	 * @param  bool $skip400 Skips the migration to 4.0 if set to true.
	 */
	public function update_installation( $skip400 = false ) {
		global $current_user;

		$this->current_user = $current_user;

		$this->debug();

		if ( ! is_null( $this->current_theme_version ) && ! is_null( $this->database_theme_version ) && version_compare( $this->current_theme_version, $this->database_theme_version, '>' ) ) {
			// Delete the update notice dismiss flag, so that the flag is reset.
			if ( ! $skip400 ) {
				delete_user_meta( $this->current_user->ID, 'avada_pre_385_notice' );
				delete_user_meta( $this->current_user->ID, 'avada_update_notice' );

				// Delete the TGMPA update notice dismiss flag, so that the flag is reset.
				delete_user_meta( $this->current_user->ID, 'tgmpa_dismissed_notice_tgmpa' );
			}

			$this->update_version();

		}

		// Hook the dismiss notice functionality.
		if ( ! $skip400 ) {
			add_action( 'admin_init', [ $this, 'notices_action' ] );
		}
	}

	/**
	 * Update the avada version in the database and reset flags.
	 */
	public function update_version() {
		if ( version_compare( $this->current_theme_version, $this->database_theme_version, '>' ) ) {
			// Update the stored theme versions.
			update_option( 'avada_version', $this->current_theme_version );
			$this->clear_update_theme_transient();

			if ( $this->previous_theme_versions ) {
				if ( is_array( $this->previous_theme_versions ) ) {
					$versions_array   = $this->previous_theme_versions;
					$versions_array[] = $this->database_theme_version;
				} else {
					$versions_array = [
						$this->previous_theme_versions,
					];
				}
			} else {
				$versions_array = [
					$this->database_theme_version,
				];
			}

			update_option( 'avada_previous_version', $versions_array );
		}
	}

	/**
	 * Action to take when user clicks on notices button.
	 */
	public function notices_action() {
		// Set update notice dismissal, so that the notice is no longer shown.
		if ( isset( $_GET['avada_update_notice'] ) && sanitize_key( wp_unslash( $_GET['avada_update_notice'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			add_user_meta( $this->current_user->ID, 'avada_update_notice', '1', true );
		}
	}

	/**
	 * Debug helper.
	 *
	 * @param  string $setting The setting we're changing.
	 * @param  string $old_value The old value.
	 * @param  string $new_value The new value.
	 */
	private static function upgraded_options_row( $setting = '', $old_value = '', $new_value = '' ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $old_value !== $new_value && '' != $setting ) { // phpcs:ignore WordPress.PHP.StrictComparisons
			self::$upgraded_options[ $setting ] = [
				'old' => $old_value,
				'new' => $new_value,
			];
		}
	}

	/**
	 * Clears the twitter widget transients of the "old" style twitter widgets.
	 *
	 * @since 4.0.0
	 *
	 * @retun void
	 */
	protected static function clear_twitter_widget_transients() {
		global $wpdb;
		$tweet_transients = $wpdb->get_results( "SELECT option_name AS name, option_value AS value FROM $wpdb->options WHERE option_name LIKE '_transient_list_tweets_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		foreach ( $tweet_transients as $tweet_transient ) {
			delete_transient( str_replace( '_transient_', '', $tweet_transient->name ) );
		}
	}

	/**
	 * Debug helper.
	 *
	 * @param bool $debug_mode Turn debug on/off.
	 */
	private function debug( $debug_mode = false ) {
		if ( $debug_mode ) {
			global $current_user;

			delete_user_meta( $current_user->ID, 'avada_update_notice' );
			delete_option( 'avada_version' );
			update_option( 'avada_version', '5.1' );
			delete_option( 'avada_previous_version' );
			delete_option( Avada::get_option_name() );

			var_dump( 'Current Version: ' . Avada::$version ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
			var_dump( 'DB Version: ' . get_option( 'avada_version', false ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
			var_dump( 'Previous Version: ' . get_option( 'avada_previous_version', [] ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
			var_dump( 'Update Notice: ' . get_user_meta( $current_user->ID, 'avada_update_notice', true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
		}
	}

	/**
	 * Force the WordPress to do an auto-update again, if the fusion-builder or
	 * core plugins are set for auto-update. To auto-update their version.
	 *
	 * This function is called after the Theme was updated.
	 *
	 * @return void
	 */
	public function update_avada_needed_plugins() {
		// Check if either builder or core plugins are set to auto-update.
		$auto_updates = get_option( 'auto_update_plugins' );
		if ( ! is_array( $auto_updates ) ) {
			return;
		}
		$found_avada_auto_update = preg_grep( '/fusion-core\.php|fusion-builder\.php/', $auto_updates );
		if ( ! is_array( $found_avada_auto_update ) || ! count( $found_avada_auto_update ) ) {
			return;
		}

		// Refresh plugin update info.
		delete_site_transient( 'update_plugins' );
		delete_site_transient( 'avada_premium_plugins_info' );
		// Needed to be deleted because 'wp_version_check' won't run until at least 1 min passed since last run.
		delete_site_transient( 'update_core' );
		// Update the available newest versions of plugins. Not 100% sure if is needed here, but better be safe.
		wp_update_plugins();

		wp_schedule_single_event( time() + 3, 'wp_version_check' ); // phpcs:ignore WPThemeReview.PluginTerritory
	}
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
