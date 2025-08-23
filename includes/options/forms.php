<?php
/**
 * Avada Options.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      4.0.0
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Contact
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function avada_options_section_forms( $sections ) {

	$contact_page_callback = [
		[
			'where'     => 'postMeta',
			'condition' => '_wp_page_template',
			'operator'  => '===',
			'value'     => 'contact.php',
		],
	];

	$sections['forms'] = [
		'label'    => esc_html__( 'Forms', 'Avada' ),
		'id'       => 'forms',
		'priority' => 21,
		'is_panel' => true,
		'icon'     => 'el-icon-envelope',
		'alt_icon' => 'fusiona-avada-form-element',
		'fields'   => [
			'forms_styling_section' => [
				'label'       => esc_html__( 'Forms Styling', 'Avada' ),
				'description' => '',
				'id'          => 'forms_styling_section',
				'type'        => 'sub-section',
				'fields'      => [
					'forms_styling_important_note_info' => [
						'label'       => '',
						'description' => '<div class="fusion-redux-important-notice">' . __( '<strong>IMPORTANT NOTE:</strong> The options on this tab apply to all forms throughout the site, including Avada Forms and the 3rd party plugins that Avada has design integration with.', 'Avada' ) . '</div>',
						'id'          => 'forms_styling_important_note_info',
						'type'        => 'custom',
					],
					'form_input_height'                 => [
						'label'       => esc_html__( 'Form Input and Select Height', 'Avada' ),
						'description' => esc_html__( 'Controls the height of all search, form input and select fields.', 'Avada' ),
						'id'          => 'form_input_height',
						'default'     => '50px',
						'type'        => 'dimension',
						'choices'     => [ 'px' ],
						'css_vars'    => [
							[
								'name' => '--form_input_height',
							],
							[
								'name'     => '--form_input_height-main-menu-search-width',
								'callback' => [
									'conditional_return_value',
									[
										'value_pattern' => [ 'calc(250px + 1.43 * $)', '250px' ],
										'conditions'    => [
											[ 'form_input_height', '>', '35' ],
										],
									],
								],
							],
						],
					],
					'form_text_size'                    => [
						'label'       => esc_html__( 'Form Font Size', 'Avada' ),
						'description' => esc_html__( 'Controls the size of the form text.', 'Avada' ),
						'id'          => 'form_text_size',
						'default'     => '16px',
						'type'        => 'dimension',
						'css_vars'    => [
							[
								'name' => '--form_text_size',
								'po'   => false,
							],
						],
					],
					'form_bg_color'                     => [
						'label'       => esc_html__( 'Form Field Background Color', 'Avada' ),
						'description' => esc_html__( 'Controls the background color of form fields.', 'Avada' ),
						'id'          => 'form_bg_color',
						'default'     => 'var(--awb-color1)',
						'type'        => 'color-alpha',
						'css_vars'    => [
							[
								'name'     => '--form_bg_color',
								'callback' => [ 'sanitize_color' ],
								'po'       => false,
							],
						],
					],
					'form_text_color'                   => [
						'label'       => esc_html__( 'Form Text Color', 'Avada' ),
						'description' => esc_html__( 'Controls the color of the form text.', 'Avada' ),
						'id'          => 'form_text_color',
						'default'     => 'var(--awb-color8)',
						'type'        => 'color-alpha',
						'css_vars'    => [
							[
								'name'     => '--form_text_color',
								'callback' => [ 'sanitize_color' ],
								'po'       => false,
							],
							[
								'name'     => '--form_text_color-35a',
								'callback' => [ 'color_alpha_set', '0.35' ],
								'po'       => false,
							],
						],
					],
					'form_border_width'                 => [
						'label'       => esc_html__( 'Form Border Size', 'Avada' ),
						'description' => esc_html__( 'Controls the border size of the form fields.', 'Avada' ),
						'id'          => 'form_border_width',
						'choices'     => [
							'top'    => true,
							'bottom' => true,
							'left'   => true,
							'right'  => true,
						],
						'default'     => [
							'top'    => '1px',
							'bottom' => '1px',
							'left'   => '1px',
							'right'  => '1px',
						],
						'type'        => 'spacing',
						'css_vars'    => [
							[
								'name'   => '--form_border_width-top',
								'choice' => 'top',
								'po'     => false,
							],
							[
								'name'   => '--form_border_width-bottom',
								'choice' => 'bottom',
								'po'     => false,
							],
							[
								'name'   => '--form_border_width-left',
								'choice' => 'left',
								'po'     => false,
							],
							[
								'name'   => '--form_border_width-right',
								'choice' => 'right',
								'po'     => false,
							],
						],
					],
					'form_border_color'                 => [
						'label'           => esc_html__( 'Form Border Color', 'Avada' ),
						'description'     => esc_html__( 'Controls the border color of the form fields.', 'Avada' ),
						'id'              => 'form_border_color',
						'default'         => 'var(--awb-color3)',
						'type'            => 'color-alpha',
						'soft_dependency' => true,
						'css_vars'        => [
							[
								'name'     => '--form_border_color',
								'callback' => [ 'sanitize_color' ],
								'po'       => false,
							],
						],
					],
					'form_focus_border_color'           => [
						'label'           => esc_html__( 'Form Border Color On Focus', 'Avada' ),
						'description'     => esc_html__( 'Controls the border color of the form fields when they have focus.', 'Avada' ),
						'id'              => 'form_focus_border_color',
						'default'         => 'var(--awb-color4)',
						'type'            => 'color-alpha',
						'soft_dependency' => true,
						'css_vars'        => [
							[
								'name'     => '--form_focus_border_color',
								'callback' => [ 'sanitize_color' ],
								'po'       => false,
							],
							[
								'name'     => '--form_focus_border_color-5a',
								'callback' => [ 'color_alpha_set', '0.5' ],
								'po'       => false,
							],
						],
					],
					'form_border_radius'                => [
						'label'       => esc_html__( 'Form Border Radius', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the border radius of the form fields. Also works, if border size is set to 0.', 'fusion-builder' ),
						'id'          => 'form_border_radius',
						'default'     => '6',
						'type'        => 'slider',
						'choices'     => [
							'min'  => '0',
							'max'  => '50',
							'step' => '1',
						],
						'css_vars'    => [
							[
								'name'          => '--form_border_radius',
								'value_pattern' => '$px',
								'po'            => false,
							],
						],
					],
					'form_views_counting'               => [
						'label'       => esc_html__( 'Form Views Counting', 'Avada' ),
						'description' => esc_html__( 'Select which types of users will increase the form views on visit.', 'Avada' ),
						'id'          => 'form_views_counting',
						'default'     => 'all',
						'type'        => 'select',
						'choices'     => [
							'all'        => esc_html__( 'All', 'Avada' ),
							'logged_out' => esc_html__( 'Logged Out', 'Avada' ),
							'non_admins' => esc_html__( 'Non-Admins', 'Avada' ),
						],
					],
				],
			],
			'hubspot_section'       => [
				'label'       => esc_html__( 'HubSpot', 'Avada' ),
				'description' => '',
				'id'          => 'hubspot_section',
				'type'        => 'sub-section',
				'fields'      => [
					'hubspot_api'         => [
						'label'       => esc_html__( 'HubSpot API', 'Avada' ),
						'description' => esc_html__( 'Select a method to connect to your HubSpot account.', 'Avada' ),
						'id'          => 'hubspot_api',
						'default'     => 'off',
						'type'        => 'radio-buttonset',
						'choices'     => [
							'auth' => esc_html__( 'OAuth', 'Avada' ),
							'key'  => esc_html__( 'API Key', 'Avada' ),
							'off'  => esc_html__( 'Off', 'Avada' ),
						],
						'transport'   => 'postMessage',
					],
					'hubspot_key'         => [
						'label'       => esc_html__( 'HubSpot API Key', 'Avada' ),
						/* translators: "our docs" link. */
						'description' => sprintf( esc_html__( 'Follow the steps in %s to access your API key.', 'Avada' ), '<a href="https://knowledge.hubspot.com/integrations/how-do-i-get-my-hubspot-api-key" target="_blank" rel="noopener noreferrer">' . esc_html__( 'HubSpot docs', 'Avada' ) . '</a>' ),
						'id'          => 'hubspot_key',
						'default'     => '',
						'type'        => 'text',
						'required'    => [
							[
								'setting'  => 'hubspot_api',
								'operator' => '==',
								'value'    => 'key',
							],
						],
						// This option doesn't require updating the preview.
						'transport'   => 'postMessage',
					],
					'hubspot_oauth'       => [
						'label'       => '',
						'description' => ( class_exists( 'Fusion_Hubspot' ) ? Fusion_Hubspot()->maybe_render_button() : '' ),
						'id'          => 'hubspot_oauth',
						'type'        => 'custom',
						'required'    => [
							[
								'setting'  => 'hubspot_api',
								'operator' => '==',
								'value'    => 'auth',
							],
						],
					],
					'reset_hubspot_cache' => [
						'label'         => esc_html__( 'Reset HubSpot Properties', 'Avada' ),
						'description'   => esc_html__( 'Resets all HubSpot properties data.', 'Avada' ),
						'id'            => 'reset_hubspot_cache',
						'default'       => '',
						'type'          => 'raw',
						'content'       => '<a class="button button-secondary" href="#" onclick="fusionResetHubSpotCache(event);" target="_self" >' . esc_html__( 'Reset HubSpot Cache', 'Avada' ) . '</a><span class="spinner fusion-spinner"></span>',
						'full_width'    => false,
						'transport'     => 'postMessage', // No need to refresh the page.
						'hide_on_front' => true,
						'required'      => [
							[
								'setting'  => 'hubspot_api',
								'operator' => '!=',
								'value'    => 'off',
							],
						],
					],
				],
			],
			'mailchimp_section'     => [
				'label'       => esc_html__( 'Mailchimp', 'Avada' ),
				'description' => '',
				'id'          => 'mailchimp_section',
				'type'        => 'sub-section',
				'fields'      => [
					'mailchimp_api'         => [
						'label'       => esc_html__( 'Mailchimp API', 'Avada' ),
						'description' => esc_html__( 'Select a method to connect to your Mailchimp account.', 'Avada' ),
						'id'          => 'mailchimp_api',
						'default'     => 'off',
						'type'        => 'radio-buttonset',
						'choices'     => [
							'auth' => esc_html__( 'OAuth', 'Avada' ),
							'key'  => esc_html__( 'API Key', 'Avada' ),
							'off'  => esc_html__( 'Off', 'Avada' ),
						],
						'transport'   => 'postMessage',
					],
					'mailchimp_key'         => [
						'label'       => esc_html__( 'Mailchimp API Key', 'Avada' ),
						/* translators: "our docs" link. */
						'description' => sprintf( esc_html__( 'Follow the steps in %s to access your API key.', 'Avada' ), '<a href="https://mailchimp.com/help/about-api-keys/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Mailchimp docs', 'Avada' ) . '</a>' ),
						'id'          => 'mailchimp_key',
						'default'     => '',
						'type'        => 'text',
						'required'    => [
							[
								'setting'  => 'mailchimp_api',
								'operator' => '==',
								'value'    => 'key',
							],
						],
						// This option doesn't require updating the preview.
						'transport'   => 'postMessage',
					],
					'mailchimp_oauth'       => [
						'label'       => '',
						'description' => ( class_exists( 'Fusion_Mailchimp' ) ? Fusion_Mailchimp()->maybe_render_button() : '' ),
						'id'          => 'mailchimp_oauth',
						'type'        => 'custom',
						'required'    => [
							[
								'setting'  => 'mailchimp_api',
								'operator' => '==',
								'value'    => 'auth',
							],
						],
					],
					'reset_mailchimp_cache' => [
						'label'         => esc_html__( 'Reset Mailchimp Lists and Fields', 'Avada' ),
						'description'   => esc_html__( 'Resets all Mailchimp lists and fields data.', 'Avada' ),
						'id'            => 'reset_mailchimp_cache',
						'default'       => '',
						'type'          => 'raw',
						'content'       => '<a class="button button-secondary" href="#" onclick="fusionResetMailchimpCache(event);" target="_self" >' . esc_html__( 'Reset Mailchimp Cache', 'Avada' ) . '</a><span class="spinner fusion-spinner"></span>',
						'full_width'    => false,
						'transport'     => 'postMessage', // No need to refresh the page.
						'hide_on_front' => true,
						'required'      => [
							[
								'setting'  => 'mailchimp_api',
								'operator' => '!=',
								'value'    => 'off',
							],
						],
					],
				],
			],
		],
	];

	return $sections;

}
