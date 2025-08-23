<?php
/**
 * Modifications for WooCommerce.
 *
 * @author     ThemeFusion
 * @link       https://avada.com
 * @package Fusion-Library
 * @subpackage Core
 * @since 3.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Library class for shared WooCommerce functionality.
 *
 * @since 3.2
 */
class Fusion_WooCommerce {

	/**
	 * Used to check if we're on a product page using a layout.
	 *
	 * @since 8.0
	 * @access protected
	 * @var bool
	 */
	protected $is_product_layout = false;       

	/**
	 * Used to check if we're on a shop page using a layout.
	 *
	 * @since 8.0
	 * @access protected
	 * @var bool
	 */
	protected $is_shop_layout = false;  

	/**
	 * Used to check if we're on a cart page using a layout.
	 *
	 * @since 8.0
	 * @access protected
	 * @var bool
	 */
	protected $is_cart_layout = false;  

	/**
	 * Used to check if we're on a checkout page using a layout.
	 *
	 * @since 3.3.0
	 * @access protected
	 * @var bool
	 */
	protected $is_checkout_layout = false;

	/**
	 * Stores cart products IDs.
	 *
	 * @since 3.3.0
	 * @access protected
	 * @var null|array
	 */
	protected $cart_products_ids = null;

	/**
	 * Constructor.
	 *
	 * @since 3.2
	 * @access public
	 */
	public function __construct() {

		// Runs after we know of layout section overrides.
		add_action( 'wp', [ $this, 'wp' ], 20 );

		add_action( 'woocommerce_before_single_product_summary', [ $this, 'before_single_product_summary_open' ], 5 );
		add_action( 'woocommerce_before_single_product_summary', [ $this, 'before_single_product_summary_close' ], 30 );

		add_filter( 'woocommerce_single_product_image_gallery_classes', [ $this, 'single_product_image_gallery_classes' ], 10 );
		add_filter( 'woocommerce_single_product_image_thumbnail_html', [ $this, 'single_product_image_thumbnail_html' ], 10, 2 );

		add_filter( 'woocommerce_single_product_carousel_options', [ $this, 'single_product_carousel_options' ], 10 );

		add_filter( 'wc_get_template', [ $this, 'filter_woo_templates' ], 10, 5 );
	}   

	/**
	 * WP hook calls to delay.
	 *
	 * @access public
	 * @since 3.2
	 * @return void
	 */
	public function wp() {
		global $post, $avada_woocommerce;

		if ( is_object( $avada_woocommerce ) && class_exists( 'WooCommerce' ) && isset( $post->post_content ) ) {
			$this->is_product_layout  = ( function_exists( 'Fusion_Template_Builder' ) && Fusion_Template_Builder()->get_override( 'content' ) && is_product() ) || ( fusion_is_preview_frame() && 'fusion_tb_section' === get_post_type() && has_term( 'content', 'fusion_tb_category' ) );
			$this->is_shop_layout     = ! $avada_woocommerce->is_wc_shop_loop_enabled() && ! empty( $post->post_content );
			$this->is_cart_layout     = false === strpos( $post->post_content, '[woocommerce_cart]' ) && false !== strpos( $post->post_content, 'fusion_tb_woo_' ) ? true : false;
			$this->is_checkout_layout = false === strpos( $post->post_content, '[woocommerce_checkout]' ) && false !== strpos( $post->post_content, 'fusion_tb_woo_checkout_' ) ? true : false;
		}

		if ( $this->is_product_layout ) {
			$this->init_single_product();
		}

		// If WPML is used, and we are on a checkout page on a secondary languages that uses layout elements.
		if ( $this->is_checkout_layout ) {
			$checkout_page_id            = wc_get_page_id( 'checkout' );
			$translated_checkout_page_id = apply_filters( 'wpml_object_id', $checkout_page_id, 'page', true );
			if ( $checkout_page_id !== $translated_checkout_page_id && is_page( $translated_checkout_page_id ) ) {
				add_filter( 'woocommerce_is_checkout', '__return_true' );
			}
		}
	}

	/**
	 * Checks for overrides and sets status.
	 *
	 * @static
	 * @access public
	 * @since 3.2
	 */
	public function init_single_product() {
		wp_dequeue_style( 'photoswipe-default-skin' );

		do_action( 'avada_builder_single_product' );

		// Modify body classes if needed.
		add_filter( 'fusion_add_woo_horizontal_tabs_body_class', '__return_false', 99 );
	}

	/**
	 * Change the template path to bypass Avada regular templates.
	 *
	 * @access public
	 * @since 3.2
	 * @param string $template Located template path.
	 * @param string $template_name Template name.
	 * @param array  $args Template arguments.
	 * @param string $template_path Template path.
	 * @param string $default_path Default path.
	 * @return string
	 */
	public function filter_woo_templates( $template, $template_name, $args, $template_path, $default_path ) {
		$notice_templates = [
			'notices/error.php'   => 'block-notices/error.php',
			'notices/notice.php'  => 'block-notices/notice.php',
			'notices/success.php' => 'block-notices/success.php',           
		];

		// Make sure the new block notices templates are used.
		if ( isset( $notice_templates[ $template_name ] ) && file_exists( $template ) ) {
			$template = str_replace( $template_name, $notice_templates[ $template_name ], $template );
		}

		if ( class_exists( 'Avada' ) ) {
			$avada_path = Avada::$template_dir_path . '/woocommerce/';
			$woo_path   = wp_normalize_path( WC()->plugin_path() . '/templates/' );
			$template   = wp_normalize_path( $template );

			if ( $this->is_product_layout && is_product() 
				|| $this->is_shop_layout && is_shop()
				|| $this->is_cart_layout && is_cart()
				|| $this->is_checkout_layout && is_checkout()
				|| ( function_exists( 'FusionBuilder' ) && FusionBuilder()->post_card_data['is_rendering'] )
			) {
				$new_template = str_replace( $avada_path, $woo_path, $template );
			} else {
				$new_template = str_replace( $woo_path, $avada_path, $template );
			}

			// Ensure template file actually exists.
			if ( file_exists( $new_template ) ) {
				return $new_template;
			}
		}

		// No alternative found, return usual.
		return $template;
	}

	/**
	 * Get is_product_layout flag.
	 *
	 * @access public
	 * @since 3.2
	 */
	public function is_product_layout() {
		return $this->is_product_layout;
	}   

	/**
	 * Get is_shop_layout flag.
	 *
	 * @access public
	 * @since 8.0
	 * @return bool
	 */
	public function is_shop_layout() {
		return $this->is_shop_layout;
	}

	/**
	 * Get is_cart_layout flag.
	 *
	 * @access public
	 * @since 8.0
	 * @return bool
	 */
	public function is_cart_layout() {
		return $this->is_cart_layout;
	}   

	/**
	 * Get is_checkout_layout flag.
	 *
	 * @access public
	 * @since 3.3
	 * @return bool
	 */
	public function is_checkout_layout() {
		return $this->is_checkout_layout;
	}

	/**
	 * Add wrapping container opening for single product image gallery.
	 *
	 * @since 5.1
	 * @access public
	 * @return void
	 */
	public function before_single_product_summary_open() {
		include FUSION_LIBRARY_PATH . '/inc/templates/wc-before-single-product-summary-open.php';
	}

	/**
	 * Add wrapping container closing for single product image gallery.
	 *
	 * @since 5.1
	 * @access public
	 * @return void
	 */
	public function before_single_product_summary_close() {
		include FUSION_LIBRARY_PATH . '/inc/templates/wc-before-single-product-summary-close.php';
	}

	/**
	 * Filters single product page image gallery classes.
	 *
	 * @since 3.2
	 * @access public
	 * @param array $classes Holds the single product image gallery classes.
	 * @return array The altered classes.
	 */
	public function single_product_image_gallery_classes( $classes ) {
		if ( 'avada' === apply_filters( 'avada_woocommerce_product_images_layout', 'avada' ) ) {
			$classes[] = 'avada-product-gallery';
		}

		return $classes;

	}

	/**
	 * Filters single product image thumbnail html.
	 *
	 * @since 3.2
	 * @access public
	 * @param string $html Holds the single product image thumbnail html.
	 * @param number $attachment_id The attachment id for single product image.
	 * @return array The altered html markup.
	 */
	public function single_product_image_thumbnail_html( $html, $attachment_id ) {
		global $post, $product;

		if ( 'avada' !== apply_filters( 'avada_woocommerce_product_images_layout', 'avada' ) ) {
			return $html;
		}

		// Early exit if attachment is missing or we don't have a product.
		if ( ! $attachment_id || ! is_object( $product ) ) {
			return $html;
		}

		$attachment_count = count( $product->get_gallery_image_ids() );
		$full_size_image  = wp_get_attachment_image_src( $attachment_id, 'full' );
		$attachment_data  = fusion_library()->images->get_attachment_data( $attachment_id, 'none' );

		$gallery = '[]';
		if ( $attachment_count > 0 ) {
			$gallery = '[product-gallery]';
		}

		$html = str_replace( '</div>', '<a class="avada-product-gallery-lightbox-trigger" href="' . esc_url( $full_size_image[0] ) . '" data-rel="iLightbox' . $gallery . '" alt="' . $attachment_data['alt'] . '" data-title="' . $attachment_data['title_attribute'] . '" title="' . $attachment_data['title_attribute'] . '" data-caption="' . $attachment_data['caption_attribute'] . '"></a></div>', $html );

		return $html;
	}

	/**
	 * Filters single product page image flexslider options.
	 *
	 * @since 3.2
	 * @access public
	 * @param array $flexslider_options Holds the default options for setting up the flexslider object.
	 * @return array The altered flexslider options.
	 */
	public function single_product_carousel_options( $flexslider_options ) {
		global $post;

		$flexslider_options['directionNav'] = true;
		$flexslider_options['prevText']     = '<i class="awb-icon-angle-left"></i>';
		$flexslider_options['nextText']     = '<i class="awb-icon-angle-right"></i>';

		$product = wc_get_product( $post );

		if ( is_object( $product ) ) {

			$attachment_ids = $product->get_gallery_image_ids();

			if ( 'avada' === apply_filters( 'avada_woocommerce_product_images_layout', 'avada' ) && 0 < count( $attachment_ids ) ) {
				$flexslider_options['animationLoop'] = true;
				$flexslider_options['smoothHeight']  = true;
			}
		}

		return $flexslider_options;
	}

	/**
	 * Calculates products discount in %.
	 *
	 * @access public
	 * @since 3.2
	 * @param object $product The product object.
	 * @param string $discount_type Should be discount be calced as percentage or value.
	 * @return string
	 */
	public function calc_product_discount( $product, $discount_type = 'percent' ) {
		if ( $product->is_type( 'variable' ) ) {
			$temp_discount = 0;
			$discount      = 0;
			$prices        = $product->get_variation_prices();
			$discount_str  = '';

			foreach ( $prices['price'] as $key => $price ) {
				if ( $prices['regular_price'][ $key ] !== $price ) {

					if ( 'percent' === $discount_type ) {
						$temp_discount = round( 100 - ( $prices['sale_price'][ $key ] / $prices['regular_price'][ $key ] * 100 ) );
					} else {
						$temp_discount = $prices['regular_price'][ $key ] - $prices['sale_price'][ $key ];
					}

					if ( $temp_discount > $discount ) {
						$discount = $temp_discount;
					}
				}
			}
		} else {
			$regular_price = (float) $product->get_regular_price();
			$sale_price    = (float) $product->get_sale_price();

			if ( 'percent' === $discount_type ) {
				$discount = 0.0 !== $regular_price ? round( ( 1 - $sale_price / $regular_price ) * 100 ) : 0;
			} else {
				$discount = $regular_price - $sale_price;
			}
		}

		// Percent.
		if ( 'percent' === $discount_type ) {
			$discount_str = $discount . '%';
		} elseif ( 'right' === get_option( 'woocommerce_currency_pos' ) ) { // Amount.
			$discount_str = $discount . get_woocommerce_currency_symbol();
		} elseif ( 'right_space' === get_option( 'woocommerce_currency_pos' ) ) {
			$discount_str = $discount . ' ' . get_woocommerce_currency_symbol();
		} elseif ( 'left' === get_option( 'woocommerce_currency_pos' ) ) {
			$discount_str = get_woocommerce_currency_symbol() . $discount;
		} elseif ( 'left_space' === get_option( 'woocommerce_currency_pos' ) ) {
			$discount_str = get_woocommerce_currency_symbol() . ' ' . $discount;
		}

		return $discount_str;
	}

	/**
	 * Get cart products ids as an array.
	 *
	 * @since 3.3
	 * @return array
	 */
	public function get_cart_products_ids() {

		if ( ! class_exists( 'WooCommerce' ) ) {
			return [];
		}

		if ( null === $this->cart_products_ids ) {
			$this->cart_products_ids = [];
			$wc_cart_items           = is_object( WC()->cart ) ? WC()->cart->get_cart() : [];

			if ( ! empty( $wc_cart_items ) ) {
				foreach ( $wc_cart_items as $cart ) {
					$this->cart_products_ids[] = $cart['product_id'];
				}
			}
		}

		return $this->cart_products_ids;
	}

	/**
	 * Checks if product is in cart or not.
	 *
	 * @since 3.3
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function is_product_in_cart( $product_id ) {

		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		if ( ! $product_id ) {
			$product_id = get_the_ID();
		}

		if ( null === $this->cart_products_ids ) {
			$this->cart_products_ids = $this->get_cart_products_ids();
		}

		return in_array( $product_id, $this->cart_products_ids ); // phpcs:ignore WordPress.PHP.StrictInArray
	}

	/**
	 * Removes ordering post clauses.
	 *
	 * @since 3.5
	 * @param string $orderby The order by method.
	 * @param string $order The order method.
	 * @return void
	 */
	public function remove_post_clauses( $orderby, $order ) {
		if ( function_exists( 'WC' ) ) {
			switch ( $orderby ) {
				case 'price':
					$callback = 'DESC' === $order ? 'order_by_price_desc_post_clauses' : 'order_by_price_asc_post_clauses';
					remove_filter( 'posts_clauses', [ WC()->query, $callback ] );
					break;
				case 'popularity':
					remove_filter( 'posts_clauses', [ WC()->query, 'order_by_popularity_post_clauses' ] );
					break;
				case 'rating':
					remove_filter( 'posts_clauses', [ WC()->query, 'order_by_rating_post_clauses' ] );
					break;
			}
		}
	}
}
