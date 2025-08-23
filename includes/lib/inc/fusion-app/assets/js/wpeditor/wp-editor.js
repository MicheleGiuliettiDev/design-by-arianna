/*
 *	JavaScript Wordpress editor
 *	Author: 		Ante Primorac
 *	Author URI: 	http://anteprimorac.from.hr
 *	Version: 		1.1
 *	License:
 *		Copyright (c) 2013 Ante Primorac
 *		Permission is hereby granted, free of charge, to any person obtaining a copy
 *		of this software and associated documentation files (the "Software"), to deal
 *		in the Software without restriction, including without limitation the rights
 *		to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *		copies of the Software, and to permit persons to whom the Software is
 *		furnished to do so, subject to the following conditions:
 *
 *		The above copyright notice and this permission notice shall be included in
 *		all copies or substantial portions of the Software.
 *
 *		THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *		IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *		FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *		AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *		LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *		OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *		THE SOFTWARE.
 */

;(function( $, window ) {
	$.fn.wp_editor = function( content, textareaID, allowGenerator ) {
		var configVar = 'undefined' === typeof fusionAppConfig ? fusionBuilderConfig : fusionAppConfig,
			default_options = {
			'mode': 'html',
			'mceInit': {
				"theme": "modern",
				"skin": "lightgray",
				"language": "en",
				"min_height": 200,
				"formats": {
					"alignleft": [
						{
							"selector": "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li",
							"styles": {"textAlign":"left"},
							"deep": false,
							"remove": "none"
						},
						{
							"selector": "img,table,dl.wp-caption",
							"classes": ["alignleft"],
							"deep":false,
							"remove":"none"
						}
					],
					"aligncenter": [
						{
							"selector": "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li",
							"styles": {"textAlign":"center"},
							"deep": false,
							"remove": "none"
						},
						{
							"selector": "img,table,dl.wp-caption",
							"classes": ["aligncenter"],
							"deep": false,
							"remove": "none"
						}
					],
					"alignright": [
						{
							"selector": "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li",
							"styles": {"textAlign":"right"},
							"deep": false,
							"remove": "none"
						},
						{
							"selector": "img,table,dl.wp-caption",
							"classes": ["alignright"],
							"deep": false,
							"remove": "none"
						}
					],
					"strikethrough": {"inline":"del","deep":true,"split":true}
				},
				"relative_urls": false,
				"remove_script_host": false,
				"convert_urls": false,
				"browser_spellcheck": true,
				"fix_list_elements": true,
				"entities": "38,amp,60,lt,62,gt",
				"entity_encoding": "raw",
				"keep_styles": false,
				"paste_webkit_styles": "font-weight font-style color",
				"preview_styles": "font-family font-size font-weight font-style text-decoration text-transform",
				"wpeditimage_disable_captions": false,
				"wpeditimage_html5_captions": false,
				"plugins": "charmap,hr,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpeditimage,wpgallery,wplink,wpdialogs,wpview,image",
				"content_css": configVar.includes_url + "css/dashicons.css?ver=3.9," + configVar.includes_url + "js/mediaelement/mediaelementplayer.min.css?ver=3.9," + configVar.includes_url + "js/mediaelement/wp-mediaelement.css?ver=3.9," + configVar.includes_url + "js/tinymce/skins/wordpress/wp-content.css?ver=3.9",
				"selector": '#fusion_builder_editor',
				"resize": "vertical",
				"menubar": false,
				"wpautop": true,
				"indent": false,
				"toolbar1": "bold,italic,strikethrough,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv",
				"toolbar2": "formatselect,underline,alignjustify,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help",
				"toolbar3": "",
				"toolbar4": "",
				"tabfocus_elements": ":prev,:next"
			}
		};

		if ( tinyMCEPreInit.mceInit['fusion_builder_editor'] ) {
			default_options.mceInit = tinyMCEPreInit.mceInit['fusion_builder_editor'];
		}

		if ( 'undefined' !== typeof awbPalette && 'undefined' !== typeof awbPalette.data ) {
			const defaultColors = [ '000000', 'Black', '993300', 'Burnt orange', '333300', 'Dark olive', '003300', 'Dark green', '003366', 'Dark azure', '000080', 'Navy Blue', '333399', 'Indigo', '333333', 'Very dark gray', '800000', 'Maroon', 'FF6600', 'Orange', '808000', 'Olive', '008000', 'Green', '008080', 'Teal', '0000FF', 'Blue', '666699', 'Grayish blue', '808080', 'Gray', 'FF0000', 'Red', 'FF9900', 'Amber', '99CC00', 'Yellow green', '339966', 'Sea green', '33CCCC', 'Turquoise', '3366FF', 'Royal blue', '800080', 'Purple', '999999', 'Medium gray', 'FF00FF', 'Magenta', 'FFFF00', 'Yellow', '00FF00', 'Lime', '00FFFF', 'Cyan', '00CCFF', 'Sky blue', '993366', 'Red violet', 'FFFFFF', 'White' ];
			let colors = [];
			for ( const colorSlug in awbPalette.data ) {
				const colorObj = awbPalette.getColorObject( colorSlug );

				if ( colorObj && colorObj.color && colorObj.label ) {
					colors.push( colorObj.color.replace( '#', '' ), colorObj.label );
				}
			}

			default_options.mceInit.textcolor_map  = colors.concat( defaultColors );
			default_options.mceInit.textcolor_cols = Object.keys( awbPalette.data ).length;
		}

		var options = $.extend({}, default_options, tinyMCEPreInit.mceInit.content);
		//var model = $('#dialog_form').dialog('option', 'referencedView').model;

		return this.each(function() {
			if( !$(this).is('textarea') )
				console.warn('Element must be a textarea');
			else {
				var current_id = $(this).attr('id');
				var tmc_settings = $.extend( {}, options.mceInit, { selector : "#" + current_id } );

				options.mode = 'tmce';

				tinyMCEPreInit.mceInit[current_id] = tmc_settings;

				$(this).addClass('wp-editor-area').show();
				var self = this;
				if( $(this).closest('.wp-editor-wrap').length ) {
					var parent_el = $(this).closest('.wp-editor-wrap').parent();
					$(this).closest('.wp-editor-wrap').before($(this).clone());
					$(this).closest('.wp-editor-wrap').remove();
					self = parent_el.find('textarea[id="' + current_id + '"]');
				}

				// Add min height to editor field
				if( $(this).data( 'element' ) && 'fusion_text' === $(this).data( 'element' ) ) {
					$(this).css( 'min-height', '500px' );
				} else {
					$(this).css( 'min-height', '200px' );
				}

				var wrap = $('<div id="wp-' + current_id + '-wrap" class="wp-core-ui wp-editor-wrap ' + options.mode + '-active" />'),
					editor_tools = $('<div id="wp-' + current_id + '-editor-tools" class="wp-editor-tools hide-if-no-js" />'),
					editor_tabs = $('<div class="wp-editor-tabs" />'),
					switch_editor_html = $('<a id="' + current_id + '-html" class="wp-switch-editor switch-html" data-wp-editor-id="' + current_id + '">Text</a>'),
					switch_editor_tmce = $('<a id="' + current_id + '-tmce" class="wp-switch-editor switch-tmce" data-wp-editor-id="' + current_id + '">Visual</a>'),
					switch_editor_input = $( '<input type="text" id="awb-switch-editor-focus" style="pointer-events: none; position: absolute; height: 0px; width: 0px; padding: 0px !important; margin: 0px; border: none;"/>' ),
					media_buttons = $('<div id="wp-' + current_id + '-media-buttons" class="wp-media-buttons" />'),
					insert_media_button = $('<a href="#" id="insert-media-button" class="button insert-media add_media" data-editor="' + current_id + '" title="Add Media"><span class="wp-media-buttons-icon"></span> Add Media</a>'),
					insert_gravity_button = $('<a href="#" id="add_gform" class="button gform_media_link" data-editor="' + current_id + '" title="Add Gravity Form">Add Gravity Form</a>'),
					editor_container = $('<div id="wp-' + current_id + '-editor-container" class="wp-editor-container" />'),
					content_css = /*Object.prototype.hasOwnProperty.call(tinyMCEPreInit.mceInit[current_id], 'content_css') ? tinyMCEPreInit.mceInit[current_id]['content_css'].split(',') :*/ false;

				insert_media_button.appendTo(media_buttons);
				if ( jQuery( '#gform_shortcode_ui-css' ).length ) {
					insert_gravity_button.appendTo(media_buttons);
				}
				media_buttons.appendTo(editor_tools);

				switch_editor_html.appendTo(editor_tabs);
				switch_editor_tmce.appendTo(editor_tabs);
				editor_tabs.appendTo(editor_tools);

				editor_tools.appendTo(wrap);
				editor_container.appendTo(wrap);

				const newTextarea = $(self).clone().addClass('wp-editor-area');
				editor_container.append( newTextarea );

				if( content_css != false )
					$.each( content_css, function() {
						if( ! $('link[href="' + this + '"]').length )
							$(self).before('<link rel="stylesheet" type="text/css" href="' + this + '">');
					} );

				$(self).before('<link rel="stylesheet" id="editor-buttons-css" href="' + configVar.includes_url + 'css/editor.css" type="text/css" media="all">');

				$(self).before(wrap);
				wrap.before( switch_editor_input );
				$(self).remove();

				jQuery( document ).trigger( 'fusionButtons', [ current_id ] );

				new QTags(current_id);

				QTags._buttonsInit();

				// Exit if element has been removed in the mean time.
				if ( 0 === $( '#' + current_id ).length ) {
					return;
				}

				switchEditors.go(current_id, options.mode);

				// Content paste to editor text mode.
				wrap.find( '#' + current_id ).on( 'paste', function( event ) {
					const cursorPos = jQuery( event.currentTarget ).prop( 'selectionStart' ),
						content = jQuery( event.currentTarget ).val();
						plainPasteContent = getPlainContent( event.originalEvent.clipboardData.getData( 'text' ) );

					if ( plainPasteContent ) {
						jQuery( event.currentTarget ).val( content.slice( 0, cursorPos ) + plainPasteContent + content.slice( cursorPos ) );

						return false;
					}
				} );

				// Content paste to editor mce mode.
				window.tinyMCE.get( current_id ).on( 'PastePreProcess', function( data ) {
					const plainContent = getPlainContent( data.content );

					data.content = plainContent ? plainContent : data.content;
				} )	;

				// Handles the content to be pasted.
				function getPlainContent( content ) {
					try {
						const pasteContent = JSON.parse( _.unescape( content ) );

						if ( 'object' === typeof pasteContent && 'undefined' !== typeof pasteContent.type && 'fusion_builder_container' !== typeof pasteContent.type && 'fusion_builder_column' !== typeof pasteContent.type && 'undefined' !== typeof pasteContent.content ) {
							return pasteContent.content;
						}
					} catch ( objError ) {
						return '';
					}			
				}

				// Remove the mce_SELRES_start class from the contents, otherwise WP will always scroll to the editor.
				window.tinyMCE.get( current_id ).on( 'init', function( e ) {
					e.target.targetElm.value = e.target.targetElm.value.replace( 'mce_SELRES_start', '' );
					e.target.$( '.mce_SELRES_start' ).removeClass( 'mce_SELRES_start' );
				} );

				// On click on the mode buttons, make sure no scroll happens and that the placeholder content isn't wiped out.
				$(wrap).on( 'click', '.wp-editor-tabs a', function( event ) {
					const anchor = $( event.currentTarget ),
						focusInput = anchor.closest( '.wp-editor-wrap' ).prev();

					focusInput.addClass( 'awb-had-focus' ).focus();

					focusInput.on( 'blur', function() {
						setTimeout( function() {
							focusInput.focus();
							focusInput.unbind( 'blur' );
							
						}, 0 );
					} );
				} );

				$(wrap).on( 'click', '.insert-media', function( event ) {
					var elem = $( event.currentTarget ),
						editor = elem.data('editor'),
						isTextBlock = elem.closest( '.fusion_text' ).length,
						options = {
							frame:	'post',
							state:	'insert',
							title:	wp.media.view.l10n.addMedia,
							multiple: true
						};

					event.preventDefault();

					elem.blur();

					if ( elem.hasClass( 'gallery' ) ) {
						options.state = 'gallery';
						options.title = wp.media.view.l10n.createGalleryTitle;
					}

					wp.media.editor.open( editor, options );

					// Hide insert from URL.
					$('.media-menu a:contains(Insert from URL)').hide();
					$( '.media-menu a:first-child' ).trigger( 'click' );

					if ( isTextBlock ) {
						$('.media-menu a:contains(Insert from URL)').show();
					}
				});
			}

			// Hide shortcode generator html button
			if ( true !== allowGenerator || true === FusionPageBuilderApp.shortcodeGenerator ) {
				$(wrap).find( '#qt_element_content_fusion_shortcodes_text_mode' ).remove();
				$(wrap).find( '#qt_fusion_builder_content_main_fusion_shortcodes_text_mode' ).remove();
			}

		} );

	}

})( jQuery, window )
