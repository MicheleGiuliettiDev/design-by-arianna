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
 * Handle migrations for Avada 7.11.7
 *
 * @since 7.11.7
 */
class Avada_Upgrade_7117 extends Avada_Upgrade_Abstract {

	/**
	 * The version.
	 *
	 * @access protected
	 * @since 7.11.7
	 * @var string
	 */
	protected $version = '7.11.7';

	/**
	 * An array of all available languages.
	 *
	 * @static
	 * @access private
	 * @since 7.11.7
	 * @var array
	 */
	private static $available_languages = [];

	/**
	 * The actual migration process.
	 *
	 * @access protected
	 * @since 7.11.7
	 * @return void
	 */
	protected function migration_process() {
		$this->protect_forms_upload_folder();
	}

	/**
	 * Add index file to forms upload folder.
	 *
	 * @since 7.11.7
	 * @access protected
	 */
	protected function protect_forms_upload_folder() {
		$upload_dir   = wp_upload_dir();
		$form_uploads = $upload_dir['basedir'] . '/fusion-forms';
		
		if ( file_exists( $form_uploads ) ) {
			$index_file = @fopen( $form_uploads . '/index.html', 'wb' );
			if ( $index_file ) {
				fclose( $index_file );
			}
		}
	}
}
