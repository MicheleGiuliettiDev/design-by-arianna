<?php
/**
 * The toolbar template file.
 *
 * @since 2.0
 * @package fusion-library
 */

?>
<script type="text/template" id="fusion-app-repeater-fields">
	<?php
	$preferences        = ( Fusion_App() && Fusion_App()->preferences ) ? Fusion_App()->preferences->get_preferences() : [];
	$descriptions_class = '';
	$descriptions_css   = '';

	if ( isset( $preferences['descriptions'] ) && 'show' === $preferences['descriptions'] ) {
		$descriptions_class = ' active';
		$descriptions_css   = ' style="display: block;"';
	}
	?>
	<div class="repeater-field">
	<#
	var repeaterIndex = 'undefined' !== typeof rowId ? rowId : 0,
	param           = field,
	option_value    = 'undefined' !== value ? value : param.value,
	hidden          = 'undefined' !== typeof param.hidden ? ' hidden' : '',
	childDependency = 'undefined' !== typeof param.child_dependency ? ' has-child-dependency' : '',
	optionId        = 'undefined' !== typeof param.param_name ? param.param_name : param.id,
	optionTitle     = 'undefined' !== typeof param.heading ? param.heading : param.label,
	context         = 'undefined' !== typeof context ? context : '',
	hasResponsive   = 'undefined' !== typeof param.responsive ? true : false,
	responsiveState = 'undefined' !== typeof param.responsive ? ' has-responsive responsive-state-' + param.responsive.state + ' fusion-' + param.responsive.state : '',
	responsiveIcons = {
		'large': 'desktop',
		'medium': 'tablet',
		'small': 'mobile'
	};

	if ( param.type == 'select' || param.type == 'multiple_select' || param.type == 'radio_button_set' || param.type == 'checkbox_button_set' || param.type == 'filter' || param.type === 'ajax_select' ) {
		option_value = 'undefined' === typeof option_value || '' === option_value ? param.default : option_value;
	} else if ( 'undefined' === typeof option_value && 'undefined' !== typeof param.value ) {
		option_value = param.value;
	}
	#>
	<li data-option-id="{{ optionId }}" data-type="{{ context }}" class="fusion-builder-option {{ param.type }}{{responsiveState}}{{ hidden }}{{ childDependency }}" style="display:inline-block" >
		<div class="option-details">
			<div class="option-details-inner">
				<# if ( 'undefined' !== typeof optionTitle ) { #>
					<h3>{{ optionTitle }}</h3>
					<ul class="fusion-panel-options">
						<# if ( 'undefined' !== typeof param.description ) { #>
							<li> <a href="JavaScript:void(0);" class="fusion-panel-description<?php echo esc_attr( $descriptions_class ); ?>"><i class="fusiona-question-circle" aria-hidden="true"></i></a> <span class="fusion-elements-option-tooltip fusion-tooltip-description">{{ fusionBuilderText.fusion_panel_desciption_toggle }}</span></li>
						<# } #>
						<# if ( hasResponsive ) { #>
							<li class="fusion-responsive-panel"><a class="option-has-responsive" href="JavaScript:void(0);" aria-label="{{ fusionBuilderText.fusion_panel_responsive_toggle }}"><i class="fusiona-{{responsiveIcons[param.responsive.state]}}" aria-hidden="true"></i></a><span class="fusion-elements-option-tooltip fusion-tooltip-preview">{{ fusionBuilderText.fusion_panel_responsive_toggle }}</span>
								<ul class="fusion-responsive-options">
									<li><a href="JavaScript:void(0);" data-indicator="desktop"><i class="fusiona-desktop" aria-hidden="true"></i></a></li>
									<li><a href="JavaScript:void(0);" data-indicator="tablet"><i class="fusiona-tablet" aria-hidden="true"></i></a></li>
									<li><a href="JavaScript:void(0);" data-indicator="mobile"><i class="fusiona-mobile" aria-hidden="true"></i></a></li>
								</ul>
							</li>
						<# } #>
					</ul>
				<# }; #>
			</div>
			<# if ( 'undefined' !== typeof param.description ) { #>
				<p class="description"<?php echo $descriptions_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>{{{ param.description }}}</p>
			<# }; #>
		</div>

		<div class="option-field fusion-builder-option-container">
			<?php
			$fields = [
				'textfield',
				'colorpickeralpha',
				'select',
				'upload_object',
				'uploadfile',
				'textarea',
				'radio_button_set',
				'multiple_select',
				'ajax_select',
				'iconpicker',
				'repeater',
				'mailchimp_map',
				'hubspot_map',
				'hubspot_consent_map',
				'range',
			];

			// Redux on left, template on right.
			$field_replacement = [
				'text'            => 'textfield',
				'media'           => 'upload_object',
				'upload'          => 'upload_object',
				'radio-buttonset' => 'radio_button_set',
			];

			foreach ( $field_replacement as $redux => $option ) {
				$fields[] = [
					$redux,
					FUSION_LIBRARY_PATH . '/inc/fusion-app/templates/options/' . str_replace( '_', '-', $option ) . '.php',
				];
			}
			?>
			<?php
				$fields = apply_filters( 'fusion_builder_repeater_fields', $fields );
			?>
			<?php foreach ( $fields as $field_type ) : ?>
				<?php if ( is_array( $field_type ) && ! empty( $field_type ) ) : ?>
					<# if ( '<?php echo esc_attr( $field_type[0] ); ?>' === param.type ) { #>
					<?php include wp_normalize_path( $field_type[1] ); ?>
				<# }; #>
				<?php else : ?>
					<# if ( '<?php echo esc_attr( $field_type ); ?>' === param.type ) { #>
					<?php include FUSION_LIBRARY_PATH . '/inc/fusion-app/templates/options/' . str_replace( '_', '-', $field_type ) . '.php'; ?>
					<# } #>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</li>
	</div>
</script>
