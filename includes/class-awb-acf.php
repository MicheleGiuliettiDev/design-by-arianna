<?php
/**
 * Handles ACF customizations.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      7.11.10
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Handles ACF customizations-
 */
class AWB_ACF {

	/**
	 * The one, true instance of this object.
	 *
	 * @static
	 * @access private
	 * @since 7.11.10
	 * @var object
	 */
	private static $instance;   

	/**
	 * The constructor.
	 *
	 * @access public
	 * @since 7.11.10
	 * @return void
	 */
	public function __construct() {
		add_filter( 'acf/fields/icon_picker/tabs', [ $this, 'add_icon_picker_tab' ] );
		add_action( 'acf/fields/icon_picker/tab/avada_icon', [ $this, 'add_avada_icon_field' ] );
		add_action( 'acf/input/admin_enqueue_scripts', [ $this, 'add_avada_icon_script_styles' ] );
	}

	/**
	 * Adds an additional tab to the icon picker field tabs.
	 *
	 * @access public
	 * @since 7.11.10
	 * @param array $tabs The icon picker field tabs.
	 * @return array The updated tabs
	 */
	public function add_icon_picker_tab( $tabs ) {
		$tabs['avada_icon'] = esc_html__( 'Avada Icon', 'Avada' );
	
		return $tabs;
	}

	/**
	 * Adds content to the additional tab of the icon picker field.
	 *
	 * @access public
	 * @since 7.11.10
	 * @param array $field The icon picker field.
	 * @return void
	 */
	public function add_avada_icon_field( $field ) {
		echo '<div class="acf-icon-picker-avada-icons">';
		acf_text_input(
			[
				'class' => 'acf-avada_icon',
				'value' => 'avada_icon' === $field['value']['type'] ? $field['value']['value'] : '',
			]
		);
	
		// Helper Text.
		?>
		<p class="description"><?php esc_html_e( 'Enter the slug of the Avada icon you want to display.', 'Avada' ); ?></p>
		<?php
		echo '</div>';
	}
	
	/**
	 * Adds the inline script for the Avada icon tab data handling.
	 *
	 * @access public
	 * @since 7.11.10
	 * @return void
	 */
	public function add_avada_icon_script_styles() {
		wp_add_inline_script(
			'acf-input',
			'
			acf.addAction( "ready", function() {
				const iconPickers = acf.getFields( acf.findFields( { type: "icon_picker" } ) );

				jQuery.each( iconPickers, function( index, value ) {
					const self = value;
					value.$el.find( ".acf-avada_icon" ).on( "input", function( event ) {
						const currentValue = event.target.value;
						self.updateTypeAndValue( "avada_icon", currentValue );
					} );
				} );
			} );
		' 
		);

		wp_add_inline_style(
			'acf-input',
			'
			.acf-icon-picker-avada-icon-tabs {
				display: flex;
				background-color: #f9f9f9;
				padding: 12px;
				border: 1px solid #8c8f94;
			}
			.acf-icon-picker-avada-icons { width: 100%; }
		' 
		);
	}

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @static
	 * @access public
	 * @since 7.11.10
	 * @return object AWB_ACF
	 */
	public static function get_instance() {

		// If an instance hasn't been created and set to $instance create an instance and set it to $instance.
		if ( null === self::$instance ) {
			self::$instance = new AWB_ACF();
		}
		return self::$instance;
	}
}

/**
 * Instantiates the AWB_ACF class.
 * Make sure the class is properly set-up.
 *
 * @since object 7.7
 * @return object AWB_ACF
 */
function AWB_ACF() { // phpcs:ignore WordPress.NamingConventions
	return AWB_ACF::get_instance();
}
