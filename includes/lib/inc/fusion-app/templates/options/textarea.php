<?php
/**
 * Underscore.js template.
 *
 * @since 2.0
 * @package fusion-library
 */

?>
<textarea
	name="{{ param.param_name }}"
	id="{{ param.param_name }}"
	cols="20"
	rows="5"
	<# if ( param.css_class ) { #>
	class="{{ param.css_class }}"
	<# } #>
	<# if ( param.placeholder ) { #>
		data-placeholder="{{ param.value }}"
	<# } #>
	<# if ( param.max ) { #>
		maxlength="{{ param.max }}"
	<# } #>
	<# if ( param.range ) { #>
		data-range="{{ param.range }}"
	<# } #>		
>{{ option_value }}</textarea>
<# if ( param.range ) { #>
	<span class="awb-counter"></span>
<# } #>	
