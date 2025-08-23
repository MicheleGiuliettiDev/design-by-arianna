<?php
/**
 * Underscore.js template.
 *
 * @since 2.0
 * @package fusion-library
 */

?>
<#
var saveType = 'undefined' === typeof param.save_type ? 'id' : param.save_type;
if ( option_value ) {
	if ( ( 'url' === param.save_type && option_value.startsWith( 'http' ) ) || option_value.startsWith( 'http' ) ) {
		const images = option_value.split( ',' );

		setTimeout(() => {
			_.each( images, function( image ) {
				image = image.split('|');
				const url = image[0];
				const id = image[1];
				let image_html   = '<div class="fusion-multi-image" data-image-id="' + id + '">';
				image_html      += '<img src="' + url + '"/>';
				image_html      += '<span class="fusion-multi-image-remove dashicons dashicons-no-alt"></span>';
				image_html      += '</div>';
				jQuery( '.fusion-multiple-image-container' ).append( image_html );
			})
		}, 300);
	} else {
		image_ids = option_value.split( ',' );

		let config = window.fusionAppConfig;
		if ( window.fusionBuilderConfig ) {
			config = window.fusionBuilderConfig;
		}

		if ( '' !== image_ids && 'object' === typeof image_ids && 'undefined' !== typeof config ) {
			jQuery.ajax( {
				type: 'POST',
				url: config.ajaxurl,
				data: {
					action: 'fusion_builder_get_image_url',
					fusion_load_nonce: config.fusion_load_nonce,
					fusion_image_ids: image_ids
				}
			} )
			.done( function( data ) {
				var dataObj;
				dataObj = JSON.parse( data );
				_.each( dataObj.images, function( image ) {
					jQuery( '.fusion-multiple-image-container' ).append( image );
				} );
			} );
		}

	}
}
#>
<div class="fusion-multiple-upload-images">
	<input
		type="hidden"
		name="{{ param.param_name }}"
		id="{{ param.param_name }}"
		class="fusion-multi-image-input"
		value="{{ option_value }}"
	/>
	<input
		type='button'
		class='button button-upload fusion-builder-upload-button fusion-builder-upload-button-upload-images fusion-builder-upload-button-multiple-upload'
		value='{{ fusionBuilderText.select_images }}'
		data-type="image"
		data-title="{{ fusionBuilderText.select_images }}"
		data-id="fusion-multiple-images"
		data-element="{{ param.element }}"
		data-save-type="{{ param.save_type }}"
	/>
	<div class="fusion-multiple-image-container"></div>
</div>
