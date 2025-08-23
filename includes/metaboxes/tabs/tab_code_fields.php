<?php
/**
 * Post Metabox options.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 */

/**
 * Post page settings
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function avada_page_options_tab_code_fields( $sections ) {
	$post_type = get_post_type();

	$sections['code_fields'] = [
		'label'    => esc_html__( 'Code Fields', 'Avada' ),
		'id'       => 'code_fields_section',
		'priority' => 27,
		'icon'     => 'el-icon-file-edit',
		'alt_icon' => 'fusiona-file-code-o',
		'fields'   => []
	];

	if ( 'awb_off_canvas' !== $post_type ) {
		$sections['code_fields']['fields']['tracking_code'] = [
			'label'       => esc_html__( 'Tracking Code', 'Avada' ),
			'description' => esc_html__( 'Paste your tracking code here. This will be added into the header template of your theme. Place code inside &lt;script&gt; tags.', 'Avada' ),
			'id'          => 'tracking_code',
			'default'     => '',
			'type'        => 'code',
			'choices'     => [
				'language' => 'html',
				'height'   => 450,
				'theme'    => 'chrome',
				'minLines' => 40,
				'maxLines' => 50,
			],
		];

		$sections['code_fields']['fields']['space_head_close'] = [
			'label'       => esc_html__( 'Space Before &lt;/head&gt;', 'Avada' ),
			'description' => esc_html__( 'Only accepts JavaScript code wrapped with &lt;script&gt; tags and HTML markup that is valid inside the &lt;head&gt; tag.', 'Avada' ),
			'id'          => 'space_head_close',
			'default'     => '',
			'type'        => 'code',
			'choices'     => [
				'language' => 'html',
				'height'   => 450,
				'theme'    => 'chrome',
				'minLines' => 40,
				'maxLines' => 50,
			],
		];

		$sections['code_fields']['fields']['space_body_open'] = [
			'label'       => esc_html__( 'Space After &lt;body&gt;', 'Avada' ),
			'description' => esc_html__( 'Only accepts JavaScript code, wrapped with &lt;script&gt; tags and valid HTML markup inside the &lt;body&gt; tag.', 'Avada' ),
			'id'          => 'space_body_open',
			'default'     => '',
			'type'        => 'code',
			'choices'     => [
				'language' => 'html',
				'height'   => 450,
				'theme'    => 'chrome',
				'minLines' => 40,
				'maxLines' => 50,
			],
		];
	}

	$sections['code_fields']['fields']['space_body_close'] = [
		'label'       => 'awb_off_canvas' === $post_type ? esc_html__( 'Custom Code', 'Avada' ) :  esc_html__( 'Space Before &lt;/body&gt;', 'Avada' ),
		'description' => esc_html__( 'Only accepts JavaScript code, wrapped with &lt;script&gt; tags and valid HTML markup inside the &lt;body&gt; tag.', 'Avada' ),
		'id'          => 'space_body_close',
		'default'     => '',
		'type'        => 'code',
		'choices'     => [
			'language' => 'html',
			'height'   => 450,
			'theme'    => 'chrome',
			'minLines' => 40,
			'maxLines' => 50,
		],
	];

	return $sections;
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
