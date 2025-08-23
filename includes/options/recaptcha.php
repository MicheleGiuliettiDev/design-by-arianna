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
function avada_options_section_recaptcha( $sections ) {

	$contact_page_callback = [
		[
			'where'     => 'postMeta',
			'condition' => '_wp_page_template',
			'operator'  => '===',
			'value'     => 'contact.php',
		],
	];
	$sections['recaptcha'] = [
		'label'    => esc_html__( 'Google reCaptcha', 'Avada' ),
		'id'       => 'recaptcha_section',
		'priority' => 22,
		'is_panel' => true,
		'icon'     => 'el-icon-puzzle',
		'alt_icon' => 'fusiona-avada-form-element',
		'fields'   => [
			'recaptcha_version'        => [
				'label'           => esc_html__( 'reCAPTCHA Version', 'Avada' ),
				'description'     => esc_html__( 'Set the reCAPTCHA version you want to use and make sure your keys below match the set version.', 'Avada' ),
				'id'              => 'recaptcha_version',
				'default'         => 'v3',
				'type'            => 'radio-buttonset',
				'choices'         => [
					'v2' => esc_html__( 'V2', 'Avada' ),
					'v3' => esc_html__( 'V3', 'Avada' ),
				],
				'update_callback' => $contact_page_callback,
			],
			'recaptcha_public'         => [
				'label'       => esc_html__( 'reCAPTCHA Site Key', 'Avada' ),
				/* translators: "our docs" link. */
				'description' => sprintf( esc_html__( 'Follow the steps in %s to get the site key.', 'Avada' ), '<a href="https://avada.com/documentation/how-to-set-up-google-recaptcha/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'our docs', 'Avada' ) . '</a>' ),
				'id'          => 'recaptcha_public',
				'default'     => '',
				'type'        => 'text',
				// This option doesn't require updating the preview.
				'transport'   => 'postMessage',
			],
			'recaptcha_private'        => [
				'label'       => esc_html__( 'reCAPTCHA Secret Key', 'Avada' ),
				/* translators: "our docs" link. */
				'description' => sprintf( esc_html__( 'Follow the steps in %s to get the secret key.', 'Avada' ), '<a href="https://avada.com/documentation/how-to-set-up-google-recaptcha/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'our docs', 'Avada' ) . '</a>' ),
				'id'          => 'recaptcha_private',
				'default'     => '',
				'type'        => 'text',
				// This option doesn't require updating the preview.
				'transport'   => 'postMessage',
			],
			'recaptcha_color_scheme'   => [
				'label'           => esc_html__( 'reCAPTCHA Color Scheme', 'Avada' ),
				'description'     => esc_html__( 'Controls the reCAPTCHA color scheme.', 'Avada' ),
				'id'              => 'recaptcha_color_scheme',
				'default'         => 'light',
				'type'            => 'radio-buttonset',
				'choices'         => [
					'light' => esc_html__( 'Light', 'Avada' ),
					'dark'  => esc_html__( 'Dark', 'Avada' ),
				],
				'required'        => [
					[
						'setting'  => 'recaptcha_version',
						'operator' => '==',
						'value'    => 'v2',
					],
				],
				'update_callback' => $contact_page_callback,
			],
			'recaptcha_score'          => [
				'label'       => esc_html__( 'reCAPTCHA Security Score', 'Avada' ),
				'description' => esc_html__( 'Set a threshold score that must be met by the reCAPTCHA response. The higher the score the harder it becomes for bots, but also false positives increase.', 'Avada' ),
				'id'          => 'recaptcha_score',
				'default'     => '0.5',
				'type'        => 'slider',
				'choices'     => [
					'min'  => '0.1',
					'max'  => '1',
					'step' => '0.1',
				],
				'required'    => [
					[
						'setting'  => 'recaptcha_version',
						'operator' => '==',
						'value'    => 'v3',
					],
				],
				// This option doesn't require updating the preview.
				'transport'   => 'postMessage',
			],
			'recaptcha_badge_position' => [
				'label'           => esc_html__( 'reCAPTCHA Badge Position', 'Avada' ),
				'description'     => __( 'Set where and if the reCAPTCHA badge should be displayed. <strong>NOTE:</strong> Google\'s Terms and Privacy information needs to be displayed on the contact form.', 'Avada' ),
				'id'              => 'recaptcha_badge_position',
				'default'         => 'inline',
				'type'            => 'radio-buttonset',
				'choices'         => [
					'inline'      => esc_html__( 'Inline', 'Avada' ),
					'bottomleft'  => esc_html__( 'Bottom Left', 'Avada' ),
					'bottomright' => esc_html__( 'Bottom Right', 'Avada' ),
					'hide'        => esc_html__( 'Hide', 'Avada' ),
				],
				'required'        => [
					[
						'setting'  => 'recaptcha_version',
						'operator' => '==',
						'value'    => 'v3',
					],
				],
				'update_callback' => $contact_page_callback,
			],
			'recaptcha_login_form'     => [
				'label'       => esc_html__( 'reCAPTCHA For User Elements', 'Avada' ),
				'description' => esc_html__( 'Turn on to add reCAPTCHA to the user login, user lost password and user registration forms.', 'Avada' ),
				'id'          => 'recaptcha_login_form',
				'default'     => '0',
				'type'        => 'switch',
			],
			'recaptcha_comment_form'   => [
				'label'       => esc_html__( 'reCAPTCHA For Comments', 'Avada' ),
				'description' => esc_html__( 'Turn on to add reCAPTCHA to comment forms.', 'Avada' ),
				'id'          => 'recaptcha_comment_form',
				'default'     => '0',
				'type'        => 'switch',
			],
			
			
		],
	];

	return $sections;

}
