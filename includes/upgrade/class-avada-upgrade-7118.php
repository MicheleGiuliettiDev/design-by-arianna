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
 * Handle migrations for Avada 7.11.8
 *
 * @since 7.11.8
 */
class Avada_Upgrade_7118 extends Avada_Upgrade_Abstract {

	/**
	 * The version.
	 *
	 * @access protected
	 * @since 7.11.8
	 * @var string
	 */
	protected $version = '7.11.8';

	/**
	 * An array of all available languages.
	 *
	 * @static
	 * @access private
	 * @since 7.11.8
	 * @var array
	 */
	private static $available_languages = [];

	/**
	 * The actual migration process.
	 *
	 * @access protected
	 * @since 7.11.8
	 * @return void
	 */
	protected function migration_process() {
		$this->set_role_manager_capabilities();
	}

	/**
	 * Add index file to forms upload folder.
	 *
	 * @since 7.11.8
	 * @access protected
	 * @return void
	 */
	protected function set_role_manager_capabilities() {
		$builder_settings = get_option( 'fusion_builder_settings', [] );

		if ( isset( $builder_settings['capabilities'] ) ) {
			$new_capabilities = $this->get_default_role_manager_capabilities();
			$old_capabilities = $builder_settings['capabilities'];
			$post_types       = [
				'fusion_tb_layout',
				'fusion_tb_section',
				'awb_off_canvas',
				'fusion_icons',
				'fusion_form',
				'slide',
				'avada_library',
				'avada_portfolio',
				'avada_faq',
				'post',
				'page',
			];
			$cap_matcher      = [
				'dashboard_menu' => 'dashboard_access',
				'avada_builder'  => 'backed_builder_edit',
				'avada_live'     => 'live_builder_edit',
				'page_options'   => 'page_options',
				'submissions'    => 'submissions_access',
			];
		
			foreach ( $old_capabilities as $role => $caps ) {
				foreach ( $caps as  $cap ) {
					if ( 'global_options' === $cap ) {
						$new_capabilities[ $role ][ 'awb_' . $cap ]['dashboard_access'] = 'on';
					} elseif ( 'global_elements' === $cap ) {
						$new_capabilities[ $role ]['avada_library'][ $cap ] = 'on';
					} else {
						$base_cap  = str_replace( $post_types, '', $cap );
						$base_cap  = str_replace( '__', '_page_', $base_cap );
						$post_type = str_replace( $base_cap, '', $cap );
						$base_cap  = ltrim( $base_cap, '_' );
		
						if ( 'submissions' === $base_cap ) {
							$user_role = get_role( $role );
		
							if ( isset( $user_role->capabilities['moderate_comments'] ) && $user_role->capabilities['moderate_comments'] ) {
								$new_capabilities[ $role ][ $post_type ][ $cap_matcher[ $base_cap ] ] = 'on';
							}
						} else {
							$new_capabilities[ $role ][ $post_type ][ $cap_matcher[ $base_cap ] ] = 'on';
						}
					}
				}
			}
		
			unset( $builder_settings['capabilities'] );
			$builder_settings['role_manager_caps'] = $new_capabilities;
		
			update_option( 'fusion_builder_settings', $builder_settings );
		}
	}

	/**
	 * Get default role manager capabilities.
	 *
	 * @since 3.11.8
	 * @access private
	 * @return array
	 */
	private function get_default_role_manager_capabilities() {
		$default_role_capabilities = $this->get_default_role_capabilities();

		$editor                      = $default_role_capabilities;
		$editor['fusion_tb_layout']  = $this->get_capability_choices( [ 'on' ] );
		$editor['fusion_tb_section'] = $this->get_capability_choices( [ 'on', 'on', 'on', 'on' ] );
		$editor['fusion_form']       = $this->get_capability_choices( [ 'on', 'on', 'on', 'on', 'on' ] );
		$editor['avada_library']     = $this->get_capability_choices( [ 'on', 'on', 'on', 'on', '', 'on' ] );

		$capabilities = [
			'editor'      => $editor,
			'author'      => $default_role_capabilities,
			'contributor' => $default_role_capabilities,
			'default'     => $default_role_capabilities,
		];

		return $capabilities;
	}

	/**
	 * Get capabilities for a default role.
	 *
	 * @since 3.11.8
	 * @access private
	 * @return array
	 */
	private function get_default_role_capabilities() {
		$capabilities = [
			'awb_global_options' => $this->get_capability_choices( [ 'off' ] ),
			'awb_prebuilts'      => $this->get_capability_choices( [ 'off' ] ),
			'awb_studio'         => $this->get_capability_choices( [ 'off' ] ),
			'fusion_tb_layout'   => $this->get_capability_choices( [ 'off' ] ),
			'fusion_tb_section'  => $this->get_capability_choices( [ 'off', 'off', 'off', 'off' ] ),
			'awb_off_canvas'     => $this->get_capability_choices( [ 'on', 'on', 'on', 'on' ] ),
			'fusion_icons'       => $this->get_capability_choices( [ 'on' ] ),
			'fusion_form'        => $this->get_capability_choices( [ 'on', 'on', 'on', 'on', 'off' ] ),
			'slide'              => $this->get_capability_choices( [ 'on', '', '', 'on' ] ),
			'avada_library'      => $this->get_capability_choices( [ 'on', 'on', 'on', 'on', '', 'off' ] ),
			'avada_portfolio'    => $this->get_capability_choices( [ 'on', 'on', 'on', 'on' ] ),
			'avada_faq'          => $this->get_capability_choices( [ 'on', 'on', 'on', 'on' ] ),
			'post'               => $this->get_capability_choices( [ '', 'on', 'on', 'on' ] ),
			'page'               => $this->get_capability_choices( [ '', 'on', 'on', 'on' ] ),
			'product'            => $this->get_capability_choices( [ '', 'on', 'on', 'on' ] ),
		];

		return $capabilities;
	}

	/**
	 * Get capability choices.
	 *
	 * @since 3.11.8
	 * @access private
	 * @param array $selection The selection "mask" of choices.
	 * @return array
	 */
	private function get_capability_choices( $selection ) {
		$available_choices = [ 'dashboard_access', 'backed_builder_edit', 'live_builder_edit', 'page_options', 'submissions_access', 'global_elements' ];
		$selected_choices  = [];

		foreach ( $selection as $index => $value ) {
			if ( '' !== $value ) {
				$selected_choices[ $available_choices[ $index ] ] = $value;
			}
		}

		return $selected_choices;
	}   
}
