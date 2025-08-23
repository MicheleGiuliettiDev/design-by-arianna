<?php
/**
 * Form Submissions Metabox options.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    fusion-builder
 * @subpackage forms
 */

/**
 * Form Submissions page settings
 *
 * @param array $sections An array of our sections.
 * @return array
 */
function avada_page_options_tab_form_steps( $sections ) {
	$fusion_settings = awb_get_fusion_settings();

	$sections['form_steps'] = [
		'label'    => esc_html__( 'Step Progress', 'Avada' ),
		'alt_icon' => 'fusiona-form-steps',
		'id'       => 'form_steps',
		'fields'   => [
			'step_notice'                 => [
				'type'        => 'custom',
				'label'       => '',
				/* translators: Documentation post link. */
				'description' => '<div class="fusion-redux-important-notice">' . sprintf( __( '<strong>IMPORTANT NOTE:</strong> Insert the special Form Step element to split up your form into steps.  For each step you should add a submit button which will be used to progress to the next step. For more information check out our <a href="%s" target="_blank">multi-step form post</a>.', 'Avada' ), 'https://avada.com/documentation/how-to-make-a-multi-step-form-with-avada-forms/' ) . '</div>',
				'id'          => 'step_notice',
			],

			'steps_nav'                   => [
				'type'        => 'select',
				'label'       => esc_attr__( 'Form Steps Navigation', 'Avada' ),
				'description' => esc_html__( 'Select main steps navigation style.', 'Avada' ),
				'id'          => 'steps_nav',
				'choices'     => [
					'none'         => esc_attr__( 'None', 'Avada' ),
					'timeline'     => esc_attr__( 'Timeline', 'Avada' ),
					'progress_bar' => esc_attr__( 'Progress Bar', 'Avada' ),
				],
				'default'     => 'none',
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'step_type'                   => [
				'type'        => 'radio-buttonset',
				'label'       => esc_html__( 'Step Type', 'Avada' ),
				'description' => esc_html__( 'Make a selection for form input fields labels position.', 'Avada' ),
				'id'          => 'step_type',
				'default'     => 'above',
				'choices'     => [
					'above' => esc_html__( 'Above', 'Avada' ),
					'below' => esc_html__( 'Below', 'Avada' ),
				],
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'none',
						'comparison' => '!=',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],
			'steps_wrapper_margin'        => [
				'type'        => 'dimensions',
				'label'       => esc_html__( 'Steps Wrapper Margin', 'Avada' ),
				'description' => esc_html__( 'Enter values including any valid CSS unit, ex: 10px or 10%.', 'Avada' ),
				'id'          => 'steps_wrapper_margin',
				'value'       => [
					'steps_margin_top'    => '',
					'steps_margin_right'  => '',
					'steps_margin_bottom' => '',
					'steps_margin_left'   => '',
				],
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'none',
						'comparison' => '!=',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'steps_bg_color'              => [
				'type'          => 'color-alpha',
				'label'         => esc_attr__( 'Steps Background Color', 'Avada' ),
				'description'   => esc_html__( 'Controls the background color of the step.', 'Avada' ),
				'id'            => 'steps_bg_color',
				'default'       => 'var(--awb-color5)',
				'dependency'    => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
				],
				'transport'     => 'postMessage',
				'states'        => [
					'active'    => [
						'default' => 'var(--awb-color4)',
						'label'   => __( 'Active', 'Avada' ),
						'id'      => 'steps_bg_color_active',
						'preview' => [
							'selector' => '.awb-above-form, .awb-below-form',
							'type'     => 'class',
							'toggle'   => 'awb-above-form-preview',
						],
					],
					'completed' => [
						'default' => 'var(--awb-color4)',
						'label'   => __( 'Completed', 'Avada' ),
						'id'      => 'steps_bg_color_completed',
						'preview' => [
							'selector' => '.awb-above-form, .awb-below-form',
							'type'     => 'class',
							'toggle'   => 'awb-above-form-preview',
						],
					],
				],
				'events'        => [ 'fusion-rerender-form-steps' ],
				'connect-state' => [ 'steps_bor_color', 'steps_sep_type', 'steps_sep_color', 'step_icon_color', 'step_icon_bg_color', 'step_icon_bor_color', 'steps_title_color' ],
			],

			'steps_padding'               => [
				'type'        => 'dimensions',
				'label'       => esc_attr__( 'Steps Padding', 'Avada' ),
				'description' => esc_html__( 'Enter values including any valid CSS unit, ex: 10px or 10%.', 'Avada' ),
				'id'          => 'steps_padding',
				'value'       => [
					'step_padding_top'    => '',
					'step_padding_right'  => '',
					'step_padding_bottom' => '',
					'step_padding_left'   => '',
				],
				'transport'   => 'postMessage',
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
				],
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'steps_border_radius'         => [
				'type'        => 'dimensions',
				'label'       => esc_attr__( 'Steps Border Radius', 'Avada' ),
				'description' => esc_html__( 'Enter values including any valid CSS unit, ex: 10px or 10%.', 'Avada' ),
				'id'          => 'steps_border_radius',
				'value'       => [
					'steps_bor_top_left'     => '',
					'steps_bor_top_right'    => '',
					'steps_bor_bottom_right' => '',
					'steps_bor_bottom_left'  => '',
				],
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'steps_bor_width'             => [
				'type'        => 'slider',
				'label'       => esc_attr__( 'Steps Border Width', 'Avada' ),
				'description' => esc_html__( 'Controls the border width of the step.', 'Avada' ),
				'id'          => 'steps_bor_width',
				'default'     => '0',
				'choices'     => [
					'step' => 1,
					'min'  => 0,
					'max'  => 15,
				],
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'steps_bor_type'              => [
				'type'        => 'select',
				'label'       => esc_attr__( 'Steps Border Style', 'Avada' ),
				'description' => esc_html__( 'Controls the border style of the step.', 'Avada' ),
				'id'          => 'steps_bor_type',
				'choices'     => [
					'solid'  => esc_attr__( 'Solid', 'Avada' ),
					'dashed' => esc_attr__( 'Dashed', 'Avada' ),
					'dotted' => esc_attr__( 'Dotted', 'Avada' ),
					'double' => esc_attr__( 'Double', 'Avada' ),
				],
				'default'     => 'solid',
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_bor_width',
						'value'      => '0',
						'comparison' => '!=',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'steps_bor_color'             => [
				'type'          => 'color-alpha',
				'label'         => esc_attr__( 'Steps Border Color', 'Avada' ),
				'description'   => esc_attr__( 'Controls the border color of the step.', 'Avada' ),
				'id'            => 'steps_bor_color',
				'default'       => '',
				'dependency'    => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_bor_width',
						'value'      => '0',
						'comparison' => '!=',
					],
				],
				'states'        => [
					'active'    => [
						'label'   => __( 'Active', 'Avada' ),
						'id'      => 'steps_bor_color_active',
						'preview' => [
							'selector' => '.awb-above-form, .awb-below-form',
							'type'     => 'class',
							'toggle'   => 'awb-above-form-preview',
						],
					],
					'completed' => [
						'label'   => __( 'Completed', 'Avada' ),
						'id'      => 'steps_bor_color_completed',
						'preview' => [
							'selector' => '.awb-above-form, .awb-below-form',
							'type'     => 'class',
							'toggle'   => 'awb-above-form-preview',
						],
					],
				],
				'transport'     => 'postMessage',
				'events'        => [ 'fusion-rerender-form-steps' ],
				'connect-state' => [ 'steps_bg_color', 'steps_sep_type', 'steps_sep_color', 'step_icon_color', 'step_icon_bg_color', 'step_icon_bor_color', 'steps_title_color' ],
			],


			'steps_spacing'               => [
				'type'        => 'select',
				'label'       => esc_attr__( 'Step Justification', 'Avada' ),
				'description' => esc_html__( 'Controls the spacing justification around the steps.', 'Avada' ),
				'id'          => 'steps_spacing',
				'choices'     => [
					'around'  => esc_attr__( 'Space Around', 'Avada' ),
					'between' => esc_attr__( 'Space Between', 'Avada' ),
					'left'    => esc_attr__( 'Left Aligned', 'Avada' ),
					'right'   => esc_attr__( 'Right Aligned', 'Avada' ),
				],
				'default'     => 'around',
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'between_steps_size'          => [
				'type'        => 'slider',
				'label'       => esc_attr__( 'Between Steps Space Size', 'Avada' ),
				'description' => esc_html__( 'Controls the internal spacing between the steps in relation with the external space.', 'Avada' ),
				'id'          => 'between_steps_size',
				'default'     => '3',
				'choices'     => [
					'step' => 0.1,
					'min'  => 0.1,
					'max'  => 10,
				],
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_spacing',
						'value'      => 'between',
						'comparison' => '!=',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'steps_sep_width'             => [
				'type'        => 'slider',
				'label'       => esc_attr__( 'Between Steps Separator Width', 'Avada' ),
				'description' => esc_html__( 'Select the width of the separator.', 'Avada' ),
				'id'          => 'steps_sep_width',
				'default'     => '3',
				'choices'     => [
					'step' => 1,
					'min'  => 0,
					'max'  => 15,
				],
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'steps_sep_type'              => [
				'type'          => 'select',
				'label'         => esc_attr__( 'Step Separator', 'Avada' ),
				'description'   => esc_html__( 'Select the separator style between the steps.', 'Avada' ),
				'id'            => 'steps_sep_type',
				'choices'       => [
					'none'   => esc_attr__( 'None', 'Avada' ),
					'solid'  => esc_attr__( 'Solid', 'Avada' ),
					'dashed' => esc_attr__( 'Dashed', 'Avada' ),
					'dotted' => esc_attr__( 'Dotted', 'Avada' ),
					'double' => esc_attr__( 'Double', 'Avada' ),
				],
				'default'       => 'dashed',
				'dependency'    => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_sep_width',
						'value'      => '0',
						'comparison' => '!=',
					],
				],
				'states'        => [
					'completed' => [
						'label'   => __( 'Completed', 'Avada' ),
						'id'      => 'steps_sep_type_completed',
						'default' => 'solid',
						'preview' => [
							'selector' => '.awb-above-form, .awb-below-form',
							'type'     => 'class',
							'toggle'   => 'awb-above-form-preview',
						],
					],
				],
				'connect-state' => [ 'steps_bg_color', 'steps_bor_color', 'steps_sep_color', 'step_icon_color', 'step_icon_bg_color', 'step_icon_bor_color', 'steps_title_color' ],
				'transport'     => 'postMessage',
				'events'        => [ 'fusion-rerender-form-steps' ],
			],
			'steps_sep_color'             => [
				'type'          => 'color-alpha',
				'label'         => esc_attr__( 'Between Steps Separator Color', 'Avada' ),
				'description'   => esc_attr__( 'Select the color of the separator.', 'Avada' ),
				'id'            => 'steps_sep_color',
				'default'       => 'var(--awb-color5)',
				'dependency'    => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_sep_width',
						'value'      => '0',
						'comparison' => '!=',
					],
				],
				'states'        => [
					'completed' => [
						'label'   => __( 'Completed', 'Avada' ),
						'id'      => 'steps_sep_color_completed',
						'default' => 'var(--awb-color4)',
						'preview' => [
							'selector' => '.awb-above-form, .awb-below-form',
							'type'     => 'class',
							'toggle'   => 'awb-above-form-preview',
						],
					],
				],
				'connect-state' => [ 'steps_bg_color', 'steps_bor_color', 'steps_sep_type', 'step_icon_color', 'step_icon_bg_color', 'step_icon_bor_color', 'steps_title_color' ],
				'transport'     => 'postMessage',
				'events'        => [ 'fusion-rerender-form-steps' ],
			],

			'step_sep_margin'             => [
				'type'        => 'dimensions',
				'label'       => esc_attr__( 'Step Separator Margin', 'Avada' ),
				'description' => esc_attr__( 'Enter values including any valid CSS unit, ex: 10px or 10%.', 'Avada' ),
				'id'          => 'step_sep_margin',
				'value'       => [
					'step_sep_margin_left'  => '',
					'step_sep_margin_right' => '',
				],
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_sep_type',
						'value'      => 'none',
						'comparison' => '!=',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'steps_number_icon'           => [
				'type'        => 'radio-buttonset',
				'label'       => esc_attr__( 'Steps Icons/Numbers', 'Avada' ),
				'description' => esc_html__( 'Whether to show number or icons in the steps.', 'Avada' ),
				'id'          => 'steps_number_icon',
				'choices'     => [
					'none'   => esc_attr__( 'None', 'Avada' ),
					'icon'   => esc_attr__( 'Icons', 'Avada' ),
					'number' => esc_attr__( 'Numbers', 'Avada' ),
				],
				'default'     => 'icon',
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],
			'step_icon_color'             => [
				'type'          => 'color-alpha',
				'label'         => esc_attr__( 'Icon/Number Color', 'Avada' ),
				'description'   => esc_html__( 'Controls the color of the icons or the numbers.', 'Avada' ),
				'id'            => 'step_icon_color',
				'dependency'    => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_number_icon',
						'value'      => 'none',
						'comparison' => '!=',
					],
				],
				'transport'     => 'postMessage',
				'states'        => [
					'active'    => [
						'label'   => __( 'Active', 'Avada' ),
						'id'      => 'step_icon_color_active',
						'preview' => [
							'selector' => '.awb-above-form, .awb-below-form',
							'type'     => 'class',
							'toggle'   => 'awb-above-form-preview',
						],
					],
					'completed' => [
						'label'   => __( 'Completed', 'Avada' ),
						'id'      => 'step_icon_color_completed',
						'preview' => [
							'selector' => '.awb-above-form, .awb-below-form',
							'type'     => 'class',
							'toggle'   => 'awb-above-form-preview',
						],
					],
				],
				'connect-state' => [ 'steps_bg_color', 'steps_bor_color', 'steps_sep_type', 'steps_sep_color', 'step_icon_bg_color', 'step_icon_bor_color', 'steps_title_color' ],
				'events'        => [ 'fusion-rerender-form-steps' ],
			],

			'step_icon_size'              => [
				'type'        => 'slider',
				'label'       => esc_attr__( 'Icon Size', 'Avada' ),
				'description' => esc_html__( 'Controls the size of the icon. In pixels.', 'Avada' ),
				'id'          => 'step_icon_size',
				'transport'   => 'postMessage',
				'default'     => 1,
				'choices'     => [
					'step' => 1,
					'min'  => 1,
					'max'  => 40,
				],
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_number_icon',
						'value'      => 'none',
						'comparison' => '!=',
					],
				],
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'step_icon_bg'                => [
				'type'        => 'radio-buttonset',
				'label'       => esc_attr__( 'Icon/Number Background', 'Avada' ),
				'description' => esc_html__( 'Turn on to display a background behind the icon.', 'Avada' ),
				'id'          => 'step_icon_bg',
				'choices'     => [
					'yes' => esc_attr__( 'Yes', 'Avada' ),
					'no'  => esc_attr__( 'No', 'Avada' ),
				],
				'default'     => 'no',
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_number_icon',
						'value'      => 'none',
						'comparison' => '!=',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'step_icon_bg_color'          => [
				'type'          => 'color-alpha',
				'label'         => esc_attr__( 'Icon/Number Background Color', 'Avada' ),
				'description'   => esc_html__( 'Controls the background color of the icons or the numbers.', 'Avada' ),
				'id'            => 'step_icon_bg_color',
				'dependency'    => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_number_icon',
						'value'      => 'none',
						'comparison' => '!=',
					],
					[
						'field'      => 'step_icon_bg',
						'value'      => 'yes',
						'comparison' => '==',
					],
				],
				'transport'     => 'postMessage',
				'states'        => [
					'active'    => [
						'label'   => __( 'Active', 'Avada' ),
						'id'      => 'step_icon_bg_color_active',
						'preview' => [
							'selector' => '.awb-above-form, .awb-below-form',
							'type'     => 'class',
							'toggle'   => 'awb-above-form-preview',
						],
					],
					'completed' => [
						'label'   => __( 'Completed', 'Avada' ),
						'id'      => 'step_icon_bg_color_completed',
						'preview' => [
							'selector' => '.awb-above-form, .awb-below-form',
							'type'     => 'class',
							'toggle'   => 'awb-above-form-preview',
						],
					],
				],
				'connect-state' => [ 'steps_bg_color', 'steps_bor_color', 'steps_sep_type', 'steps_sep_color', 'step_icon_color', 'step_icon_bor_color', 'steps_title_color' ],
				'events'        => [ 'fusion-rerender-form-steps' ],
			],

			'step_icon_padding'           => [
				'type'        => 'slider',
				'label'       => esc_attr__( 'Icon/Number Padding', 'Avada' ),
				'description' => esc_html__( 'Controls the size of the icon padding. In pixels.', 'Avada' ),
				'id'          => 'step_icon_padding',
				'default'     => 1,
				'choices'     => [
					'step' => 1,
					'min'  => 1,
					'max'  => 40,
				],
				'transport'   => 'postMessage',
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_number_icon',
						'value'      => 'none',
						'comparison' => '!=',
					],
					[
						'field'      => 'step_icon_bg',
						'value'      => 'yes',
						'comparison' => '==',
					],
				],
				'events'      => [ 'fusion-rerender-form-steps' ],
			],
			'step_icon_border_radius'     => [
				'type'        => 'dimensions',
				'label'       => esc_attr__( 'Icon/Number Border Radius', 'Avada' ),
				'description' => esc_html__( 'Enter values including any valid CSS unit, ex: 10px or 10%.', 'Avada' ),
				'id'          => 'step_icon_border_radius',
				'value'       => [
					'step_icon_bor_top_left'     => '',
					'step_icon_bor_top_right'    => '',
					'step_icon_bor_bottom_right' => '',
					'step_icon_bor_bottom_left'  => '',
				],
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_number_icon',
						'value'      => 'none',
						'comparison' => '!=',
					],
					[
						'field'      => 'step_icon_bg',
						'value'      => 'yes',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'step_icon_bor_width'         => [
				'type'        => 'slider',
				'label'       => esc_attr__( 'Icon Border Width', 'Avada' ),
				'description' => esc_html__( 'Enter values including any valid CSS unit, ex: 10px or 10%.', 'Avada' ),
				'id'          => 'step_icon_bor_width',
				'default'     => '0',
				'choices'     => [
					'step' => 1,
					'min'  => 0,
					'max'  => 15,
				],
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_number_icon',
						'value'      => 'none',
						'comparison' => '!=',
					],
					[
						'field'      => 'step_icon_bg',
						'value'      => 'yes',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],
			'step_icon_bor_type'          => [
				'type'        => 'select',
				'label'       => esc_attr__( 'Icon Border Style', 'Avada' ),
				'description' => esc_html__( 'Controls the border style of the icon.', 'Avada' ),
				'id'          => 'step_icon_bor_type',
				'choices'     => [
					'solid'  => esc_attr__( 'Solid', 'Avada' ),
					'dashed' => esc_attr__( 'Dashed', 'Avada' ),
					'dotted' => esc_attr__( 'Dotted', 'Avada' ),
					'double' => esc_attr__( 'Double', 'Avada' ),
				],
				'default'     => 'solid',
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_number_icon',
						'value'      => 'none',
						'comparison' => '!=',
					],
					[
						'field'      => 'step_icon_bg',
						'value'      => 'yes',
						'comparison' => '==',
					],
					[
						'field'      => 'step_icon_bor_width',
						'value'      => '0',
						'comparison' => '!=',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],
			'step_icon_bor_color'         => [
				'type'          => 'color-alpha',
				'label'         => esc_attr__( 'Icon Border Color', 'Avada' ),
				'description'   => esc_attr__( 'Controls the icon border color.', 'Avada' ),
				'id'            => 'step_icon_bor_color',
				'default'       => 'var(--primary_color)',
				'dependency'    => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'step_icon_bor_width',
						'value'      => '0',
						'comparison' => '!=',
					],
					[
						'field'      => 'steps_number_icon',
						'value'      => 'none',
						'comparison' => '!=',
					],
					[
						'field'      => 'step_icon_bg',
						'value'      => 'yes',
						'comparison' => '==',
					],
				],
				'states'        => [
					'active'    => [
						'label'   => __( 'Active', 'Avada' ),
						'id'      => 'step_icon_bor_color_active',
						'preview' => [
							'selector' => '.awb-above-form, .awb-below-form',
							'type'     => 'class',
							'toggle'   => 'awb-above-form-preview',
						],
					],
					'completed' => [
						'label'   => __( 'Completed', 'Avada' ),
						'id'      => 'step_icon_bor_color_completed',
						'preview' => [
							'selector' => '.awb-above-form, .awb-below-form',
							'type'     => 'class',
							'toggle'   => 'awb-above-form-preview',
						],
					],
				],
				'connect-state' => [ 'steps_bg_color', 'steps_bor_color', 'steps_sep_type', 'steps_sep_color', 'step_icon_color', 'step_icon_bg_color', 'steps_title_color' ],
				'transport'     => 'postMessage',
				'events'        => [ 'fusion-rerender-form-steps' ],
			],


			'steps_title'                 => [
				'type'        => 'radio-buttonset',
				'label'       => esc_attr__( 'Steps Title', 'Avada' ),
				'description' => esc_html__( 'Whether to display title or not. If set to no, step title will still be heard by users using screen readers (for accessibility).', 'Avada' ),
				'id'          => 'steps_title',
				'choices'     => [
					'yes' => esc_attr__( 'Yes', 'Avada' ),
					'no'  => esc_attr__( 'No', 'Avada' ),
				],
				'default'     => 'yes',
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'steps_title_position'        => [
				'type'        => 'select',
				'label'       => esc_attr__( 'Steps Title Position', 'Avada' ),
				'description' => esc_html__( 'Select the step title position in relation with the icon/number.', 'Avada' ),
				'id'          => 'steps_title_position',
				'choices'     => [
					'after'  => esc_attr__( 'After', 'Avada' ),
					'before' => esc_attr__( 'Before', 'Avada' ),
					'above'  => esc_attr__( 'Above', 'Avada' ),
					'below'  => esc_attr__( 'Below', 'Avada' ),
				],
				'default'     => 'after',
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_number_icon',
						'value'      => 'none',
						'comparison' => '!=',
					],
					[
						'field'      => 'steps_title',
						'value'      => 'yes',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'step_icon_title_gap'         => [
				'type'        => 'text',
				'label'       => esc_html__( 'Gap Between Icon and Title', 'Avada' ),
				'description' => esc_html__( 'Enter values including any valid CSS unit, ex: 10px or 10%.', 'Avada' ),
				'id'          => 'step_icon_title_gap',
				'default'     => '',
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_number_icon',
						'value'      => 'none',
						'comparison' => '!=',
					],
					[
						'field'      => 'steps_title',
						'value'      => 'yes',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'step_typo'                   => [
				'id'          => 'step_typo',
				'label'       => esc_html__( 'Step Title Typography', 'Avada' ),
				'description' => esc_html__( 'Controls the typography of the title. Leave empty for the global font family.', 'Avada' ),
				'type'        => 'typography',
				'choices'     => [
					'font-family'    => true,
					'font-size'      => true,
					'font-weight'    => true,
					'line-height'    => true,
					'letter-spacing' => true,
					'text-transform' => true,
				],
				'default'     => [
					'font-family'    => '',
					'font-size'      => '',
					'font-weight'    => '',
					'line-height'    => '',
					'letter-spacing' => '',
					'text-transform' => '',
				],
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_title',
						'value'      => 'yes',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
				'global'      => true,
				'backup_font' => false,
			],

			'steps_title_color'           => [
				'type'          => 'color-alpha',
				'label'         => esc_attr__( 'Steps Title Color', 'Avada' ),
				'description'   => esc_html__( 'Controls the color of the step title font.', 'Avada' ),
				'id'            => 'steps_title_color',
				'default'       => 'var(--awb-color1)',
				'dependency'    => [
					[
						'field'      => 'steps_nav',
						'value'      => 'timeline',
						'comparison' => '==',
					],
					[
						'field'      => 'steps_title',
						'value'      => 'yes',
						'comparison' => '==',
					],
				],
				'states'        => [
					'active'    => [
						'label'   => __( 'Active', 'Avada' ),
						'default' => 'var(--awb-color8)',
						'id'      => 'steps_title_color_active',
						'preview' => [
							'selector' => '.awb-above-form, .awb-below-form',
							'type'     => 'class',
							'toggle'   => 'awb-above-form-preview',
						],
					],
					'completed' => [
						'label'   => __( 'Completed', 'Avada' ),
						'default' => 'var(--awb-color8)',
						'id'      => 'steps_title_color_completed',
						'preview' => [
							'selector' => '.awb-above-form, .awb-below-form',
							'type'     => 'class',
							'toggle'   => 'awb-above-form-preview',
						],
					],
				],
				'connect-state' => [ 'steps_bg_color', 'steps_bor_color', 'steps_sep_type', 'steps_sep_color', 'step_icon_color', 'step_icon_bg_color', 'step_icon_bor_color' ],
				'transport'     => 'postMessage',
				'events'        => [ 'fusion-rerender-form-steps' ],
			],

			// Progress Bar.

			'step_pb_percentage'          => [
				'type'        => 'radio-buttonset',
				'label'       => esc_attr__( 'Progress Text', 'Avada' ),
				'description' => esc_attr__( 'Select if you want the filled area percentage value to be shown.', 'Avada' ),
				'id'          => 'step_pb_percentage',
				'default'     => 'percentages',
				'choices'     => [
					'none'        => esc_attr__( 'None', 'Avada' ),
					'percentages' => esc_attr__( 'Percentages', 'Avada' ),
				],
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'progress_bar',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'step_pb_alignment'           => [
				'type'        => 'radio-buttonset',
				'label'       => esc_attr__( 'Text Align', 'Avada' ),
				'description' => esc_attr__( 'Choose the alignment of the text.', 'Avada' ),
				'id'          => 'step_pb_alignment',
				'choices'     => [
					''       => esc_attr__( 'Text Flow', 'Avada' ),
					'left'   => esc_attr__( 'Left', 'Avada' ),
					'center' => esc_attr__( 'Center', 'Avada' ),
					'right'  => esc_attr__( 'Right', 'Avada' ),
				],
				'default'     => '',
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'progress_bar',
						'comparison' => '==',
					],
					[
						'field'      => 'step_pb_percentage',
						'value'      => 'percentages',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'step_pb_typo'                => [
				'type'        => 'typography',
				'label'       => esc_attr__( 'Progress Bar Typography', 'Avada' ),
				'description' => esc_html__( 'Controls the text typography.', 'Avada' ),
				'id'          => 'step_pb_typo',
				'choices'     => [
					'font-family'    => true,
					'font-size'      => true,
					'line-height'    => true,
					'letter-spacing' => true,
					'text-transform' => true,
				],
				'default'     => [
					'font-family'    => '',
					'variant'        => '',
					'font-size'      => '',
					'line-height'    => '',
					'letter-spacing' => '',
					'text-transform' => '',
				],
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'progress_bar',
						'comparison' => '==',
					],
					[
						'field'      => 'step_pb_percentage',
						'value'      => 'percentages',
						'comparison' => '==',
					],
				],
				'global'      => true,
				'backup_font' => false,
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'step_pb_typo_color'          => [
				'type'        => 'color-alpha',
				'label'       => esc_attr__( 'Progress Bar Title Color', 'Avada' ),
				'description' => esc_html__( 'Controls the color of the progress bar title font. Default is inherited from global progress bar value.', 'Avada' ),
				'id'          => 'step_pb_typo_color',
				'default'     => $fusion_settings->get( 'progressbar_text_color' ),
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'progress_bar',
						'comparison' => '==',
					],
					[
						'field'      => 'step_pb_percentage',
						'value'      => 'percentages',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'step_pb_striped'             => [
				'type'        => 'radio-buttonset',
				'label'       => esc_html__( 'Progress Bar Striped Filling', 'Avada' ),
				'description' => esc_html__( 'Choose to get the filled area striped.', 'Avada' ),
				'id'          => 'step_pb_striped',
				'default'     => 'no',
				'choices'     => [
					'yes' => esc_html__( 'Yes', 'Avada' ),
					'no'  => esc_html__( 'No', 'Avada' ),
				],
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'progress_bar',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'step_pb_animated_stripes'    => [
				'type'        => 'radio-buttonset',
				'label'       => esc_attr__( 'Progress Bar Animated Stripes', 'Avada' ),
				'description' => esc_attr__( 'Choose to get the the stripes animated.', 'Avada' ),
				'id'          => 'step_pb_animated_stripes',
				'choices'     => [
					'yes' => esc_attr__( 'Yes', 'Avada' ),
					'no'  => esc_attr__( 'No', 'Avada' ),
				],
				'default'     => 'no',
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'progress_bar',
						'comparison' => '==',
					],
					[
						'field'      => 'step_pb_striped',
						'value'      => 'yes',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'step_pb_dimension'           => [
				'type'        => 'text',
				'label'       => esc_attr__( 'Progress Bar Height', 'Avada' ),
				'description' => esc_attr__( 'Insert a height for the progress bar. Enter value including any valid CSS unit, ex: 10px. Default value taken from Progress Bar element global setting.', 'Avada' ),
				'id'          => 'step_pb_dimension',
				'default'     => '',
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'progress_bar',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'step_pb_filled_color'        => [
				'type'        => 'color-alpha',
				'label'       => esc_attr__( 'Progress Bar Filled Color', 'Avada' ),
				'description' => esc_attr__( 'Controls the color of the filled in area. Default value taken from Progress Bar element global setting.', 'Avada' ),
				'id'          => 'step_pb_filled_color',
				'value'       => '',
				'default'     => $fusion_settings->get( 'progressbar_filled_color' ),
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'progress_bar',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'step_pb_unfilled_color'      => [
				'type'        => 'color-alpha',
				'label'       => esc_attr__( 'Progress Bar Unfilled Color', 'Avada' ),
				'description' => esc_attr__( 'Controls the color of the unfilled in area. Default value taken from Progress Bar element global setting.', 'Avada' ),
				'id'          => 'step_pb_unfilled_color',
				'value'       => '',
				'default'     => $fusion_settings->get( 'progressbar_unfilled_color' ),
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'progress_bar',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],
			'step_pb_border_radius'       => [
				'type'             => 'dimensions',
				'remove_from_atts' => true,
				'label'            => esc_attr__( 'Progress Bar Border Radius', 'Avada' ),
				'description'      => esc_attr__( 'Enter values including any valid CSS unit, ex: 10px or 10%.', 'Avada' ),
				'id'               => 'step_pb_border_radius',
				'value'            => [
					'step_pb_bor_top_left'     => '',
					'step_pb_bor_top_right'    => '',
					'step_pb_bor_bottom_right' => '',
					'step_pb_bor_bottom_left'  => '',
				],
				'dependency'       => [
					[
						'field'      => 'steps_nav',
						'value'      => 'progress_bar',
						'comparison' => '==',
					],
				],
				'transport'        => 'postMessage',
				'events'           => [ 'fusion-rerender-form-steps' ],
			],

			'step_pb_filled_border_size'  => [
				'type'        => 'slider',
				'label'       => esc_attr__( 'Progress Bar Filled Border Size', 'Avada' ),
				'description' => esc_attr__( 'Enter values including any valid CSS unit, ex: 10px or 10%. Default value taken from Progress Bar element global setting.', 'Avada' ),
				'id'          => 'step_pb_filled_border_size',
				'choices'     => [
					'step' => 1,
					'min'  => 0,
					'max'  => 20,
				],
				'default'     => $fusion_settings->get( 'progressbar_filled_border_size' ),
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'progress_bar',
						'comparison' => '==',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],

			'step_pb_filled_border_color' => [
				'type'        => 'color-alpha',
				'label'       => esc_attr__( 'Progress Bar Filled Border Color', 'Avada' ),
				'description' => esc_attr__( 'Controls the border color of the filled in area. Default value taken from Progress Bar element global setting.', 'Avada' ),
				'id'          => 'step_pb_filled_border_color',
				'default'     => $fusion_settings->get( 'progressbar_filled_border_color' ),
				'dependency'  => [
					[
						'field'      => 'steps_nav',
						'value'      => 'progress_bar',
						'comparison' => '==',
					],
					[
						'field'      => 'step_pb_filled_border_size',
						'value'      => '0',
						'comparison' => '!=',
					],
				],
				'transport'   => 'postMessage',
				'events'      => [ 'fusion-rerender-form-steps' ],
			],
		],
	];
		return $sections;
}
