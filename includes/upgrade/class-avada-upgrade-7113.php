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
 * Handle migrations for Avada 7.11.3
 *
 * @since 7.11
 */
class Avada_Upgrade_7113 extends Avada_Upgrade_Abstract {

	/**
	 * The version.
	 *
	 * @access protected
	 * @since 7.11.3
	 * @var string
	 */
	protected $version = '7.11.3';

	/**
	 * An array of all available languages.
	 *
	 * @static
	 * @access private
	 * @since 7.11.3
	 * @var array
	 */
	private static $available_languages = [];

	/**
	 * The actual migration process.
	 *
	 * @access protected
	 * @since 7.11.3
	 * @return void
	 */
	protected function migration_process() {
		$available_languages       = Fusion_Multilingual::get_available_languages();
		self::$available_languages = ( ! empty( $available_languages ) ) ? $available_languages : [ '' ];

		$this->migrate_options();
	}

	/**
	 * Migrate options.
	 *
	 * @since 7.11.3
	 * @access protected
	 */
	protected function migrate_options() {
		$available_langs = self::$available_languages;

		$options = get_option( $this->option_name, [] );
		$options = $this->set_testimonial_options( $options );

		update_option( $this->option_name, $options );

		foreach ( $available_langs as $language ) {

			// Skip langs that are already done.
			if ( '' === $language ) {
				continue;
			}

			$options = get_option( $this->option_name . '_' . $language, [] );
			$options = $this->set_testimonial_options( $options );

			update_option( $this->option_name . '_' . $language, $options );
		}
	}

	/**
	 * Sets link hover color option as default totop hover.
	 *
	 * @access private
	 * @since 7.11.3
	 * @param array $options The Global Options array.
	 * @return array         The updated Global Options array.
	 */
	private function set_testimonial_options( $options ) {
		if ( isset( $options['testimonial_text_color'] ) ) {
			$options['testimonial_name_company_color'] = $options['testimonial_text_color'];
		}

		return $options;
	}
}
