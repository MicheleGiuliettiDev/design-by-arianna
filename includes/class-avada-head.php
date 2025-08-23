<?php
/**
 * Tweaks for the <head> of the document.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      3.8
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Tweaks for the <head> of the document.
 */
class Avada_Head {

	/**
	 * Constructor.
	 *
	 * @access  public
	 */
	public function __construct() {
		/**
		 * WIP
		add_action( 'wp_head', array( $this, 'x_ua_meta' ), 1 );
		add_action( 'wp_head', array( $this, 'the_meta' ) );
		 */

		add_filter( 'language_attributes', [ $this, 'add_opengraph_doctype' ] );

		add_filter( 'document_title_separator', [ $this, 'document_title_separator' ] );

		add_filter( 'theme_color_meta', [ $this, 'theme_color' ] );

		add_action( 'wp_head', [ $this, 'insert_favicons' ], 2 );
		add_action( 'admin_head', [ $this, 'insert_favicons' ], 2 );
		add_action( 'login_head', [ $this, 'insert_favicons' ], 2 );

		add_filter( 'pre_get_document_title', [ $this, 'adjust_title_tag' ] );
		add_action( 'wp_head', [ $this, 'insert_og_meta' ], 5 );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );

		add_action( 'wp_head', [ $this, 'set_user_agent' ], 1000 );
		add_action( 'wp_head', [ $this, 'preload_fonts' ] );

		add_action( 'avada_before_body_content', [ $this, 'add_space_before_body' ], 0 );

		// wp_body_open function introduced in WP 5.2.
		if ( function_exists( 'wp_body_open' ) ) {
			add_action( 'avada_before_body_content', 'wp_body_open' );
		}

		/**
		 * WIP
		add_filter( 'wpseo_metadesc', array( $this, 'yoast_metadesc_helper' ) );
		*/

	}

	/**
	 * Add the space after body open field contents.
	 *
	 * @since 7.11.8
	 * @access public
	 * @return void.
	 */
	public function add_space_before_body() {
		/**
		 * Echo the scripts added to the "before <body>" field in Global Options.
		 * The 'space_body_open' setting is not sanitized.
		 * In order to be able to take advantage of this,
		 * a user would have to gain access to the database
		 * in which case this is the least of your worries.
		 */
		echo apply_filters( 'awb_space_body_open', Avada()->settings->get( 'space_body_open' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Adding the Open Graph in the Language Attributes
	 *
	 * @access public
	 * @param  string $output The output we want to process/filter.
	 * @return string The altered doctype
	 */
	public function add_opengraph_doctype( $output ) {
		if ( Avada()->settings->get( 'status_opengraph' ) ) {
			return $output . ' prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb#"';
		}
		return $output;
	}

	/**
	 * Set the user agent data attribute on the HTML tag.
	 *
	 * @access public
	 * @since 6.0
	 * @return void
	 */
	public function set_user_agent() {
		?>
		<script type="text/javascript">
			var doc = document.documentElement;
			doc.setAttribute( 'data-useragent', navigator.userAgent );
		</script>
		<?php
	}

	/**
	 * Preloads font files.
	 *
	 * @static
	 * @access public
	 * @since 7.2
	 * @return void
	 */
	public function preload_fonts() {
		$fusion_settings = awb_get_fusion_settings();

		$preload_fonts = $fusion_settings->get( 'preload_fonts' );
		$tags          = '';

		if ( 'icon_fonts' === $preload_fonts || 'all' === $preload_fonts ) {
			// Icomoon.
			$font_url = FUSION_LIBRARY_URL . '/assets/fonts/icomoon';
			$font_url = set_url_scheme( $font_url ) . '/awb-icons.woff';

			$tags .= '<link rel="preload" href="' . $font_url . '" as="font" type="font/woff" crossorigin>';

			// Font Awesome.
			$tags .= ( 'local' === $fusion_settings->get( 'gfonts_load_method' ) && true === Fusion_Font_Awesome::is_fa_pro_enabled() && ! ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) ) ? fusion_library()->fa->get_local_subsets_tags() : fusion_library()->fa->get_subsets_tags();

			// Custom Icons.
			$tags .= fusion_get_custom_icons_preload_tags();
		}

		if ( 'google_fonts' === $preload_fonts || 'all' === $preload_fonts ) {
			// Google fonts.
			$google_fonts = Avada_Google_Fonts::get_instance();
			$tags        .= $google_fonts->get_preload_tags();
		}

		echo $tags; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Adjust the title tag, in case a custom title is set in Page Options.
	 *
	 * @access public
	 * @since 7.11.6
	 * @return string
	 */
	public function adjust_title_tag( $title ) {
		$custom_title = $this->get_custom_title();

		$title = $custom_title ? $custom_title : $title;

		return $title;
	}

	/**
	 * Get custom title as set in Page Options.
	 *
	 * @access public
	 * @since 7.11.6
	 * @return string
	 */ 
	public function get_custom_title() {
		$post  = get_queried_object();
		$title = '';

		if ( isset( $post->ID ) ) {
			$title = fusion_get_page_option( 'seo_title', $post->ID );
			$title = $title ? $this->replace_variables( $title ) : '';
			$title = apply_filters( 'awb_seo_title', esc_html( $title ) );
		}

		return $title;
	}

	/**
	 * Replace placeholder variables with values.
	 *
	 * @access public
	 * @since 7.11.6
	 * string $string The string to replace variables in.
	 * @return string
	 */ 
	public function replace_variables( $string ) {
		$string = str_replace( [ '[site_title]', '[site_tagline]', '[post_title]', '[separator]', '"', "'" ], [ get_bloginfo( 'name' ), get_bloginfo( 'description' ), get_the_title(), Avada()->settings->get( 'meta_tags_separator' ), '&quot;', '&#39;' ], $string );

		return $string;
	}   

	/**
	 * Get open graph locale.
	 *
	 * @access public
	 * @since 7.11.6
	 * @return string
	 */ 
	public function get_og_locale() {
		$locale = get_locale();

		$adjust_locales = [
			'ca' => 'ca_ES',
			'en' => 'en_US',
			'el' => 'el_GR',
			'et' => 'et_EE',
			'ja' => 'ja_JP',
			'sq' => 'sq_AL',
			'uk' => 'uk_UA',
			'vi' => 'vi_VN',
			'zh' => 'zh_CN',
		];

		$locale = isset( $adjust_locales[ $locale ] ) ? $adjust_locales[ $locale ] : $locale;
		$locale = 2 === strlen( $locale ) ? strtolower( $locale ) . '_' . strtoupper( $locale ) : $locale;

		return apply_filters( 'awb_og_meta_locale', $locale );
	}   
	
	/**
	 * Get open graph type.
	 *
	 * @access public
	 * @since 7.11.6
	 * @return string
	 */ 
	public function get_og_type() {
		$type = 'article';

		if ( is_front_page() ) {
			$type = 'website';
		} elseif ( is_author() ) {
			$type = 'profile';
		}

		return apply_filters( 'awb_og_meta_type', $type );
	}   

	/**
	 * Get open graph title.
	 *
	 * @access public
	 * @since 7.11.6
	 * @return string
	 */ 
	public function get_og_title() {
		$custom_title = $this->get_custom_title();

		if ( $custom_title ) {
			return $custom_title;
		}

		$title = strip_tags( str_replace( [ '"', "'" ], [ '&quot;', '&#39;' ], wp_title( '', false ) ) );

		if ( is_home() && ! is_front_page() ) {
			$title = get_the_title( get_option( 'page_for_posts' ) );
		} elseif ( is_home() ) {
			$title = get_bloginfo( 'name' ) . ' - ' . get_bloginfo( 'description' );
		} elseif ( is_author() ) {
			$user = get_user_by( 'ID', get_queried_object_id() );
			
			if ( isset( $user->display_name ) ) {
				/* Translators: 1: Author name; 2: Site name. */
				$title = sprintf( __( '%1$s, Author at %2$s', 'Avada' ), $user->display_name, get_bloginfo( 'name' ) );
			} else {
				/* Translators: Site name. */
				$title = sprintf( __( ' Authored by %s', 'Avada' ), get_bloginfo( 'name' ) );
			}
		}

		return apply_filters( 'awb_og_meta_title', $title );
	}

	/**
	 * Get open graph description.
	 *
	 * @access public
	 * @since 7.11.6
	 * @return string
	 */ 
	public function get_og_description() {
		$post        = get_queried_object();
		$description = '';

		if ( isset( $post->ID ) ) {
			$description = fusion_get_page_option( 'meta_description', $post->ID );
			
			if ( $description ) {
				$description = $this->replace_variables( $description );
			} else {
				$description = isset( $post->post_content ) ? Avada()->blog->get_content_stripped_and_excerpted( 55, $post->post_content ) : '';
			}
		}

		return apply_filters( 'awb_og_meta_description', $description );
	}

	/**
	 * Get open graph image.
	 *
	 * @access public
	 * @since 7.11.6
	 * @return array The image array consisting of url, width, height and mime type.
	 */ 
	public function get_og_image() {
		$post     = get_queried_object();
		$image_id = '';
		$image    = [];

		if ( isset( $post->ID ) ) {
			$post_image = fusion_get_page_option( 'meta_og_image', $post->ID );
			$image_id   = isset( $post_image['id'] ) ? $post_image['id'] : $image_id;
		}

		if ( ! $image_id && has_post_thumbnail() ) {
			$image_id = get_post_thumbnail_id();
		}

		if ( $image_id ) {
			$image           = wp_get_attachment_image_src( $image_id, 'full' );
			$image['url']    = $image[0];
			$image['width']  = $image[1];
			$image['height'] = $image[2];
			$image['type']   = get_post_mime_type( $image_id );   
		} elseif ( Avada()->settings->get( 'logo' ) ) {
			$image = Avada()->settings->get( 'logo' );

			// Handling of GO that haven't been saved.
			if ( isset( $image['url'] ) && ! isset( $image['id'] ) ) {
				$image = $image['url'];
			}

			if ( is_string( $image ) ) {
				$image = [
					'url'    => $image,
					'width'  => '115',
					'height' => '25',
					'type'   => 'image/png',
				];
			} else {
				$image['type']   = get_post_mime_type( $image['id'] );
				$image['width']  = isset( $image['width'] ) ? $image['width'] : '';
				$image['height'] = isset( $image['height'] ) ? $image['height'] : '';
			}
		}

		return apply_filters( 'awb_og_meta_image', $image );
	}

	/**
	 * Get site GMT offset.
	 *
	 * @access public
	 * @since 7.11.6
	 * @return string
	 */ 
	public function get_og_site_gmt_offset() {
		$offset    = (float) get_option( 'gmt_offset' );
		$hours     = (int) $offset;
		$minutes   = ( $offset - $hours );
		$sign      = ( $offset < 0 ) ? '-' : '+';
		$abs_hours = abs( $hours );
		$abs_mins  = abs( $minutes * 60 );
		$offset    = sprintf( '%s%02d:%02d', $sign, $abs_hours, $abs_mins );

		return apply_filters( 'awb_og_gmt_offset', $offset );
	}

	/**
	 * Get open graph author.
	 *
	 * @access public
	 * @since 7.11.6
	 * @return string
	 */ 
	public function get_og_author() {
		$post   = get_queried_object();
		$author = '';

		if ( isset( $post->post_author ) ) {
			$user   = get_user_by( 'ID', $post->post_author );
			$author = isset( $user->display_name ) ? $user->display_name : $author;
		}

		return apply_filters( 'awb_og_meta_author', $author );
	}   

	/**
	 * Avada extra OpenGraph tags.
	 * These are added to the <head> of the page using the 'wp_head' action.
	 *
	 * @access public
	 * @return void
	 */
	public function insert_og_meta() {

		// Early exit if we don't need to continue any further.
		if ( ! Avada()->settings->get( 'status_opengraph' ) ) {
			return;
		}

		$locale      = $this->get_og_locale();
		$type        = $this->get_og_type();
		$title       = $this->get_og_title();
		$description = $this->get_og_description();
		$image       = $this->get_og_image();
		$author      = $this->get_og_author();
		?>
		<?php if ( $description ) : ?>
			<meta name="description" content="<?php echo esc_attr( $description ); ?>"/>
		<?php endif; ?>		
		<meta property="og:locale" content="<?php echo esc_attr( $locale ); ?>"/>
		<meta property="og:type" content="<?php echo esc_attr( $type ); ?>"/>
		<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"/>
		<meta property="og:title" content="<?php echo esc_attr( $title ); ?>"/>
		<?php if ( $description ) : ?>
		<meta property="og:description" content="<?php echo esc_attr( $description ); ?>"/>
		<?php endif; ?>
		<meta property="og:url" content="<?php echo esc_url_raw( get_permalink() ); ?>"/>
		<?php if ( 'article' === $type ) : ?>
			<?php
			$post   = get_queried_object();
			$offset = $this->get_og_site_gmt_offset();
			?>
			<?php if ( isset( $post->post_date_gmt ) && isset( $post->post_type ) && 'post' === $post->post_type ) : ?>
		<meta property="article:published_time" content="<?php echo esc_attr( str_replace( ' ', 'T', $post->post_date_gmt ) . $offset ); ?>"/>
		<?php endif; ?>
			<?php if ( isset( $post->post_date_gmt ) && isset( $post->post_modified_gmt ) && $post->post_date_gmt !== $post->post_modified_gmt ) : ?>
		<meta property="article:modified_time" content="<?php echo esc_attr( str_replace( ' ', 'T', $post->post_modified_gmt ) . $offset ); ?>"/>
		<?php endif; ?>
			<?php if ( isset( $post->post_type ) && 'post' === $post->post_type && '' !== $author ) : ?>
			<meta name="author" content="<?php echo esc_attr( $author ); ?>"/>
		<?php endif; ?>
		<?php endif; ?>
		<?php if ( ! empty( $image ) ) : ?>
		<meta property="og:image" content="<?php echo esc_url_raw( $image['url'] ); ?>"/>
		<meta property="og:image:width" content="<?php echo esc_attr( $image['width'] ); ?>"/>
		<meta property="og:image:height" content="<?php echo esc_attr( $image['height'] ); ?>"/>
		<meta property="og:image:type" content="<?php echo esc_attr( $image['type'] ); ?>"/>
		<?php endif; ?>
		<?php
	}

	/**
	 * Add X-UA-Compatible meta when needed.
	 *
	 * @access  public
	 */
	public function x_ua_meta() {
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && ( false !== strpos( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ), 'MSIE' ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			echo '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />';
		}
	}

	/**
	 * Set the document title separator.
	 *
	 * @access  public
	 */
	public function document_title_separator() {
		return '-';
	}

	/**
	 * Avada favicon as set in global options.
	 * These are added to the <head> of the page using the 'wp_head' action.
	 *
	 * @access  public
	 * @since   4.0
	 * @return  void
	 */
	public function insert_favicons() {
		if ( '' !== Avada()->settings->get( 'fav_icon', 'url' ) || '' !== Avada()->settings->get( 'fav_icon_apple_touch', 'url' ) || '' !== Avada()->settings->get( 'fav_icon_android', 'url' ) || '' !== Avada()->settings->get( 'fav_icon_edge', 'url' ) ) {
			remove_action( 'admin_head', 'wp_site_icon' );
			remove_action( 'wp_head', 'wp_site_icon', 99 );
			remove_action( 'login_head', 'wp_site_icon', 99 );
		}

		?>
		<?php if ( '' !== Avada()->settings->get( 'fav_icon', 'url' ) ) : ?>
			<link rel="shortcut icon" href="<?php echo esc_url( Avada()->settings->get( 'fav_icon', 'url' ) ); ?>" type="image/x-icon" />
		<?php endif; ?>

		<?php if ( '' !== Avada()->settings->get( 'fav_icon_apple_touch', 'url' ) ) : ?>
			<!-- Apple Touch Icon -->
			<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( Avada()->settings->get( 'fav_icon_apple_touch', 'url' ) ); ?>">
		<?php endif; ?>

		<?php if ( '' !== Avada()->settings->get( 'fav_icon_android', 'url' ) ) : ?>
			<!-- Android Icon -->
			<link rel="icon" sizes="192x192" href="<?php echo esc_url( Avada()->settings->get( 'fav_icon_android', 'url' ) ); ?>">
		<?php endif; ?>

		<?php if ( '' !== Avada()->settings->get( 'fav_icon_edge', 'url' ) ) : ?>
			<!-- MS Edge Icon -->
			<meta name="msapplication-TileImage" content="<?php echo esc_url( Avada()->settings->get( 'fav_icon_edge', 'url' ) ); ?>">
		<?php endif; ?>
		<?php

	}

	/**
	 * Fixes YOAST SEO plugin issues.
	 *
	 * @access public
	 * @since 5.0.3
	 * @param string $metadesc The description.
	 * @return string
	 */
	public function yoast_metadesc_helper( $metadesc ) {
		if ( '' === $metadesc ) {
			global $post;

			$metadesc = Avada()->blog->get_content_stripped_and_excerpted( 55, $post->post_content );
		}

		return $metadesc;
	}

	/**
	 * Echoes the viewport.
	 *
	 * @access public
	 * @since 5.1.0
	 * @return void
	 */
	public function the_viewport() {

		$is_ipad = (bool) ( isset( $_SERVER['HTTP_USER_AGENT'] ) && false !== strpos( $_SERVER['HTTP_USER_AGENT'], 'iPad' ) ); // phpcs:ignore WordPress.Security

		$viewport = '';
		if ( fusion_get_option( 'responsive' ) && $is_ipad ) {
			$viewport .= '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />';
		} elseif ( fusion_get_option( 'responsive' ) ) {
			if ( Avada()->settings->get( 'mobile_zoom' ) ) {
				$viewport .= '<meta name="viewport" content="width=device-width, initial-scale=1" />';
			} else {
				$viewport .= '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />';
			}
		}

		$viewport = apply_filters( 'avada_viewport_meta', $viewport );

		echo $viewport; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Prints the theme-color meta.
	 *
	 * @access public
	 * @since 5.8
	 * @param string $theme_color The theme-color we want to use.
	 * @return string
	 */
	public function theme_color( $theme_color ) {

		// Exit early if PWA is not enabled.
		$pwa_enabled = Fusion_Settings::get_instance()->get( 'pwa_enable' );
		if ( true === $pwa_enabled || '1' !== $pwa_enabled ) {
			$settings    = Fusion_Settings::get_instance();
			$theme_color = $settings->get( 'pwa_theme_color' );
			return Fusion_Color::new_color( $theme_color )->get_new( 'alpha', 1 )->to_css( 'hex' );
		}
		return $theme_color;
	}
}
