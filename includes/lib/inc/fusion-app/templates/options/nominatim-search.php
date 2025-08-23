<?php
/**
 * Underscore.js template.
 *
 * @since 2.0
 * @package fusion-library
 */

?>
<#
var inputPlaceholder = 'undefined' !== typeof FusionApp ? fusionBuilderText.search : '',
	fieldId          = 'undefined' === typeof param.param_name ? param.id : param.param_name;
	dataLat = 'undefined' !== param.target_fields ? param.target_fields[0] : '';
	dataLon = 'undefined' !== param.target_fields ? param.target_fields[1] : '';
#>
<div class="fusion-nominatim-selector">
	<input id="{{ fieldId }}" name="{{ fieldId }}" type="text" class="regular-text fusion-builder-nominatim-field" value="{{ option_value }}" placeholder="{{ inputPlaceholder }}" data-lat="{{{ dataLat }}}" data-lon="{{{ dataLon }}}" />

	<# if ( 'undefined' !== typeof FusionApp ) { #>
		<a class='button-nominatim-selector fusion-builder-nominatim-button'><span class="fusiona-search"></span></a>
	<# } else { #>
		<input type='button' class='button-nominatim-selector fusion-builder-nominatim-button' value='{{ fusionBuilderText.search }}'/>
	<# } #>
</div>
