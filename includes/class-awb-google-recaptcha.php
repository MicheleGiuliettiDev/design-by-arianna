<?php
/**
 * Handles Google recaptcha in Avada.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      7.11.6
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Implements the AWB Google reCAPTCHA object.
 */
final class AWB_Google_Recaptcha {

	/**
	 * The class instance.
	 *
	 * @since 7.11.6
	 * @static
	 * @access private
	 * @var null|object
	 */
	private static $instance = null;

	/**
	 * The class instance.
	 *
	 * @since 7.11.6
	 * @access private
	 * @var int
	 */
	private $counter = 0;

	/**
	 * The class constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->counter = 1;

		if ( ! is_user_logged_in() && fusion_library()->get_option( 'recaptcha_comment_form' ) ) {
			add_action( 'comment_form_after_fields', [ $this, 'render_comment_form_recaptcha' ] );

			add_action( 'pre_comment_on_post', [ $this, 'check_recaptcha_comment_form' ] );
		}
	}

	/**
	 * Render reCAPTCHA HTML on comment forms.
	 *
	 * @access public
	 * @since 7.11.6
	 * @return void
	 */
	public function render_comment_form_recaptcha() {

		$this->render_field(
			[
				'counter'       => $this->counter,
				'element'       => 'comments',
				'wrapper_class' => 'form-creator-recaptcha',
			]
		);

		$recaptcha_error = ( isset( $_GET['recaptcha_error'] ) && '' !== $_GET['recaptcha_error'] ) ? sanitize_text_field( wp_unslash( $_GET['recaptcha_error'] ) ) : '';  // phpcs:ignore WordPress.Security.NonceVerification
		$type            = ( isset( $_GET['type'] ) && '' !== $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';  // phpcs:ignore WordPress.Security.NonceVerification	
		if ( $recaptcha_error && $type ) {
			echo do_shortcode( '[fusion_alert margin_top="20px" type="' . esc_attr( strip_shortcodes( $type ) ) . '"]' . esc_html( strip_shortcodes( $recaptcha_error ) ) . '[/fusion_alert]' );
		}
	}

	/**
	 * Check reCAPTCHA on comment forms.
	 *
	 * @since 7.11.6
	 * @access private
	 * @param string $post_id current post id.
	 * @return void
	 */
	public function check_recaptcha_comment_form( $post_id ) {
		if ( ! isset( $_POST['g-recaptcha-response'] ) || empty( $_POST['g-recaptcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect(
				add_query_arg(
					[
						'type'            => 'error',
						'recaptcha_error' => __( 'Sorry, reCAPTCHA could not verify that you are a human. Please try again.', 'fusion-builder' ),
					],
					esc_url( get_permalink( $post_id ) )
				)
			);
			exit;
		}
		if ( isset( $_POST['g-recaptcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			if ( fusion_library()->get_option( 'recaptcha_public' ) && fusion_library()->get_option( 'recaptcha_private' ) ) {
				$response = $this->verify();

				if ( is_array( $response ) && $response['has_error'] && $response['message'] ) {
					wp_safe_redirect(
						add_query_arg(
							[
								'type'            => 'error',
								'recaptcha_error' => $response['message'],
							],
							esc_url( get_permalink( $post_id ) )
						)
					);
					exit;
				}
			} else {
				wp_safe_redirect(
					add_query_arg(
						[
							'type'            => 'error',
							'recaptcha_error' => esc_html__( 'reCAPTCHA configuration error. Please check the Global Options settings and your reCAPTCHA account settings.', 'fusion-builder' ),
						],
						esc_url( get_permalink( $post_id ) )
					)
				);
				exit;
			}
		}
	}   

	/**
	 * Render reCAPTCHA field HTML.
	 *
	 * @access public
	 * @since 7.11.6
	 * @param array $args params.
	 * @return void
	 */
	public function render_field( $args = [] ) {
		$defaults = [
			'color_theme'    => fusion_library()->get_option( 'recaptcha_color_scheme' ),
			'badge_position' => fusion_library()->get_option( 'recaptcha_badge_position' ),
			'tab_index'      => '',
			'counter'        => $this->counter,
			'element'        => 'form',
			'wrapper_class'  => 'form-creator-recaptcha',
		];
		$args     = wp_parse_args( $args, $defaults );
		?>
		<?php if ( fusion_library()->get_option( 'recaptcha_public' ) && fusion_library()->get_option( 'recaptcha_private' ) ) : ?>
			<?php if ( 'v2' === fusion_library()->get_option( 'recaptcha_version' ) ) : ?>
				<div class="<?php echo esc_attr( $args['wrapper_class'] ); ?>">
					<div
						id="g-recaptcha-id-<?php echo esc_attr( $args['element'] . '-' . $args['counter'] ); ?>"
						class="awb-recaptcha-v2 fusion-<?php echo esc_attr( $args['element'] ); ?>-recaptcha-v2"
						data-theme="<?php echo esc_attr( $args['color_theme'] ); ?>"
						data-sitekey="<?php echo esc_attr( fusion_library()->get_option( 'recaptcha_public' ) ); ?>"
						data-tabindex="<?php echo esc_attr( $args['tab_index'] ); ?>">
					</div>
				</div>
			<?php else : ?>
				<?php $hide_badge_class = 'hide' === $args['badge_position'] ? ' fusion-form-hide-recaptcha-badge' : ''; ?>
				<?php if ( 'hide' !== $args['badge_position'] ) { ?>
					<div class="<?php echo esc_attr( $args['wrapper_class'] ); ?>">
				<?php } ?>
						<div
							id="g-recaptcha-id-<?php echo esc_attr( $args['element'] . '-' . $args['counter'] ); ?>"
							class="fusion-<?php echo esc_attr( $args['element'] ); ?>-recaptcha-v3 recaptcha-container <?php echo esc_attr( $hide_badge_class ); ?>"
							data-sitekey="<?php echo esc_attr( fusion_library()->get_option( 'recaptcha_public' ) ); ?>"
							data-badge="<?php echo esc_attr( $args['badge_position'] ); ?>">
						</div>
						<input
							type="hidden"
							name="fusion-<?php echo esc_attr( $args['element'] ); ?>-recaptcha-response"
							class="g-recaptcha-response"
							id="fusion-<?php echo esc_attr( $args['element'] ); ?>-recaptcha-response-<?php echo esc_attr( $args['counter'] ); ?>"
							value="">
				<?php if ( 'hide' !== $args['badge_position'] ) { ?>
					</div>
				<?php } ?>
			<?php endif; ?>
		<?php elseif ( current_user_can( 'manage_options' ) ) : ?>
				<div class="fusion-builder-placeholder"><?php echo esc_html__( 'reCAPTCHA configuration error. Please check the Global Options settings and your reCAPTCHA account settings.', 'fusion-builder' ); ?></div>
		<?php endif; ?>
		<?php

		if ( 1 === $this->counter ) {
			$this->enqueue_scripts();
		}

		$this->counter++;
	}   

	/**
	 * Sets the necessary scripts.
	 *
	 * @access public
	 * @since 7.11.6
	 * @return void
	 */
	public function enqueue_scripts() {

		// Add reCAPTCHA script.
		$fusion_settings = awb_get_fusion_settings();

		if ( fusion_library()->get_option( 'recaptcha_public' ) && fusion_library()->get_option( 'recaptcha_private' ) && ! function_exists( 'recaptcha_get_html' ) && ! class_exists( 'ReCaptcha' ) ) {
			$recaptcha_script_uri = 'https://www.google.com/recaptcha/api.js?render=explicit&hl=' . get_locale() . '&onload=fusionOnloadCallback';
			if ( 'v2' === fusion_library()->get_option( 'recaptcha_version' ) ) {
				$recaptcha_script_uri = 'https://www.google.com/recaptcha/api.js?hl=' . get_locale();
			}
			wp_enqueue_script( 'recaptcha-api', $recaptcha_script_uri, [], Avada::get_theme_version(), false );

			// Inline JS to render reCaptcha.
			add_action( 'wp_footer', [ $this, 'recaptcha_callback' ], 99 );
		}
	}

	/**
	 * Generate reCAPTCHA callback.
	 *
	 * @access public
	 * @since 7.11.6
	 * @return void
	 */
	public function recaptcha_callback() {
		?>
		<script type='text/javascript'>
			<?php if ( 'v2' === fusion_library()->get_option( 'recaptcha_version' ) ) { ?>
			jQuery( window ).on( 'load', function() {
				var reCaptchaID;
				jQuery.each( jQuery( '.awb-recaptcha-v2' ), function( index, reCaptcha ) { // eslint-disable-line no-unused-vars
					reCaptchaID = jQuery( this ).attr( 'id' );
					grecaptcha.render( reCaptchaID, {
						sitekey: jQuery( this ).data( 'sitekey' ),
						type: jQuery( this ).data( 'type' ),
						theme: jQuery( this ).data( 'theme' )
					} );
				} );
			});
		<?php } else { ?>
			var active_captcha = [];

			var fusionOnloadCallback = function () {
				grecaptcha.ready( function () {
					jQuery( '.g-recaptcha-response' ).each( function () {
						var $el        = jQuery( this ),
							$container = $el.parent().find( 'div.recaptcha-container' ),
							id         = $container.attr( 'id' ),
							renderId;

						if ( 0 === $container.length || 'undefined' !== typeof active_captcha[ id ] || ( 1 === jQuery( '.fusion-modal' ).find( $container ).length && $container.closest( '.fusion-modal' ).is( ':hidden' ) ) ) {
							return;
						}

						renderId = grecaptcha.render(
							id,
							{
								sitekey: $container.data( 'sitekey' ),
								badge: $container.data( 'badge' ),
								size: 'invisible'
							}
						);

						active_captcha[ id ] = renderId;

						grecaptcha.execute( renderId, { action: 'contact_form' } ).then( function ( token ) {
							$el.val( token );
						});
					});
				});
			};
			<?php } ?>
		</script>
		<?php
	}   

	/**
	 * Verify reCAPTCHA.
	 *
	 * @access public
	 * @since 7.11.6
	 * @return array
	 */
	public static function verify() {
		$response = [
			'has_error' => false,
			'message'   => '',
		];

		require_once FUSION_LIBRARY_PATH . '/inc/recaptcha/src/autoload.php';
		// We use a wrapper class to avoid fatal errors due to syntax differences on PHP 5.2.
		require_once FUSION_LIBRARY_PATH . '/inc/recaptcha/class-fusion-recaptcha.php';     

		// Instantiate recaptcha.
		$re_captcha_wrapper = new Fusion_ReCaptcha( fusion_library()->get_option( 'recaptcha_private' ) );
		$re_captcha         = $re_captcha_wrapper->recaptcha;
		if ( $re_captcha && isset( $_POST['g-recaptcha-response'] ) && ! empty( $_POST['g-recaptcha-response'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification
			$re_captcha_response = null;
			// Was there a reCAPTCHA response.
			$post_recaptcha_response = ( isset( $_POST['g-recaptcha-response'] ) ) ? trim( wp_unslash( $_POST['g-recaptcha-response'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification

			$server_remote_addr = ( isset( $_SERVER['REMOTE_ADDR'] ) ) ? trim( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification

			if ( 'v2' === fusion_library()->get_option( 'recaptcha_version' ) ) {
				$re_captcha_response = $re_captcha->verify( $post_recaptcha_response, $server_remote_addr );
			} else {
				$site_url            = get_option( 'siteurl' );
				$url_parts           = wp_parse_url( $site_url );
				$site_url            = isset( $url_parts['host'] ) ? $url_parts['host'] : $site_url;
				$re_captcha_response = $re_captcha->setExpectedHostname( apply_filters( 'avada_recaptcha_hostname', $site_url ) )->setExpectedAction( 'contact_form' )->setScoreThreshold( fusion_library()->get_option( 'recaptcha_score' ) )->verify( $post_recaptcha_response, $server_remote_addr );
			}
			// Check the reCAPTCHA response.
			if ( null === $re_captcha_response || ! $re_captcha_response->isSuccess() ) {
				$response    = [
					'has_error' => true,
					'message'   => __( 'Sorry, reCAPTCHA could not verify that you are a human. Please try again.', 'fusion-builder' ),
				];
				$error_codes = [];
				if ( null !== $re_captcha_response ) {
					$error_codes = $re_captcha_response->getErrorCodes();
				}
				if ( empty( $error_codes ) || in_array( 'score-threshold-not-met', $error_codes, true ) ) {
					$response = [
						'has_error' => true,
						'message'   => __( 'Sorry, reCAPTCHA could not verify that you are a human. Please try again.', 'fusion-builder' ),
					];
				}
			}
		} else {
			$response = [
				'has_error' => true,
				'message'   => __( 'Sorry, reCAPTCHA could not verify that you are a human. Please try again.', 'fusion-builder' ),
			];
		}
		return $response;
	}   

	/**
	 * Returns a single instance of the object (singleton).
	 *
	 * @since 7.11.6
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new AWB_Google_Recaptcha();
		}
		return self::$instance;
	}
}
