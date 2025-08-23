<?php
/**
 * Upgrades Handler.
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
 * Handle migrations for Avada 7.10
 *
 * @since 7.10.0
 */
class Avada_Upgrade_7100 extends Avada_Upgrade_Abstract {

	/**
	 * The version.
	 *
	 * @access protected
	 * @since 7.10.0
	 * @var string
	 */
	protected $version = '7.10.0';

	/**
	 * An array of all available languages.
	 *
	 * @static
	 * @access private
	 * @since 7.10
	 * @var array
	 */
	private static $available_languages = [];

	/**
	 * The actual migration process.
	 *
	 * @access protected
	 * @since 7.10
	 * @return void
	 */
	protected function migration_process() {
		$available_languages       = Fusion_Multilingual::get_available_languages();
		self::$available_languages = ( ! empty( $available_languages ) ) ? $available_languages : [ '' ];

		$this->migrate_options();
		$this->send_site_data_if_agreed();
	}

	/**
	 * Migrate options.
	 *
	 * @since 7.10.0
	 * @access protected
	 */
	protected function migrate_options() {
		$available_langs = self::$available_languages;

		$options = get_option( $this->option_name, [] );
		$options = $this->migrate_read_more( $options );

		update_option( $this->option_name, $options );

		foreach ( $available_langs as $language ) {

			// Skip langs that are already done.
			if ( '' === $language ) {
				continue;
			}

			$options = get_option( $this->option_name . '_' . $language, [] );
			$options = $this->migrate_read_more( $options );

			update_option( $this->option_name . '_' . $language, $options );
		}
	}

	/**
	 * Sets link hover color option.
	 *
	 * @access private
	 * @since 7.10.0
	 * @param array $options The Global Options array.
	 * @return array         The updated Global Options array.
	 */
	private function migrate_read_more( $options ) {
		if ( isset( $options['excerpt_read_more_symbol'] ) ) {
			$options['excerpt_read_more_symbol'] = ' ' . $options['excerpt_read_more_symbol'];
		}

		return $options;
	}
}
