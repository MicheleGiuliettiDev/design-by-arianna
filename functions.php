<?php

/**
 * Child theme bootstrap.
 * Keep this file lean; load product-editor from /inc.
 */

// Do not allow direct access.
if (! defined('ABSPATH')) {
  exit;
}

if (! defined('AVADA_VERSION')) {
  define('AVADA_VERSION', '7.12.2');
}
if (! defined('AVADA_MIN_PHP_VER_REQUIRED')) {
  define('AVADA_MIN_PHP_VER_REQUIRED', '5.6');
}
if (! defined('AVADA_MIN_WP_VER_REQUIRED')) {
  define('AVADA_MIN_WP_VER_REQUIRED', '4.9');
}
if (! defined('AVADA_DEV_MODE')) {
  define('AVADA_DEV_MODE', false);
}

/**
 * Load the Product Editor module (all hooks live inside).
 */
require_once get_theme_file_path('inc/product-editor.php');

/**
 * Compatibility check.
 * (Keep Avada bootstrap lines that are not part of the product editor.)
 */
if (version_compare($GLOBALS['wp_version'], AVADA_MIN_WP_VER_REQUIRED, '<') || version_compare(PHP_VERSION, AVADA_MIN_PHP_VER_REQUIRED, '<')) {
  require_once get_template_directory() . '/includes/bootstrap-compat.php';
  return;
}

/**
 * Bootstrap the parent theme.
 */
require_once get_template_directory() . '/includes/bootstrap.php';

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
// 1. Aggiungi i campi sul prodotto singolo
add_action( 'woocommerce_before_add_to_cart_button', 'campo_personalizzazione_prodotto' );
function campo_personalizzazione_prodotto() {
    ?>
    <div class="custom-product-options">
        <p>
            <label>
                <input type="checkbox" id="personalizza_prodotto" name="personalizza_prodotto" value="yes">
                I would like to customize this product (+10€)
            </label>
        </p>
        <p id="campo_testo_personalizzazione" style="display:none;">
            <label for="testo_personalizzato">Text to write:</label><br>
            <input type="text" name="testo_personalizzato" id="testo_personalizzato" value="">
        </p>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const checkbox = document.getElementById("personalizza_prodotto");
            const campoTesto = document.getElementById("campo_testo_personalizzazione");
            const inputTesto = document.getElementById("testo_personalizzato");

            checkbox.addEventListener("change", function() {
                campoTesto.style.display = this.checked ? "block" : "none";
                inputTesto.required = this.checked; // rende obbligatorio solo se checkbox selezionata
            });
        });
    </script>
    <?php
}

// 2. Valida il campo testo se la checkbox è selezionata
add_filter( 'woocommerce_add_to_cart_validation', 'valida_testo_personalizzazione', 10, 3 );
function valida_testo_personalizzazione( $passed, $product_id, $quantity ) {
    if ( isset($_POST['personalizza_prodotto']) && $_POST['personalizza_prodotto'] === 'yes' ) {
        if ( empty($_POST['testo_personalizzato']) ) {
            wc_add_notice( 'Per favore inserisci il testo per la personalizzazione.', 'error' );
            return false;
        }
    }
    return $passed;
}

// 3. Salva il valore nel carrello
add_filter( 'woocommerce_add_cart_item_data', 'salva_personalizzazione_carrello', 10, 2 );
function salva_personalizzazione_carrello( $cart_item_data, $product_id ) {
    if( isset($_POST['personalizza_prodotto']) && $_POST['personalizza_prodotto'] === 'yes' ) {
        $cart_item_data['personalizza_prodotto'] = 'yes';
        $cart_item_data['sovrapprezzo_personalizzazione'] = 10; // +10€
        if ( ! empty($_POST['testo_personalizzato']) ) {
            $cart_item_data['testo_personalizzato'] = sanitize_text_field($_POST['testo_personalizzato']);
        }
    }
    return $cart_item_data;
}

// 4. Aggiorna il prezzo nel carrello
add_action( 'woocommerce_before_calculate_totals', 'aggiungi_sovrapprezzo_personalizzazione', 10, 1 );
function aggiungi_sovrapprezzo_personalizzazione( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

    foreach ( $cart->get_cart() as $cart_item ) {
        if ( isset($cart_item['sovrapprezzo_personalizzazione']) ) {
            $prezzo_originale = $cart_item['data']->get_price();
            $nuovo_prezzo = $prezzo_originale + $cart_item['sovrapprezzo_personalizzazione'];
            $cart_item['data']->set_price( $nuovo_prezzo );
        }
    }
}

// 5. Mostra i dati nel carrello/checkout
add_filter( 'woocommerce_get_item_data', 'mostra_personalizzazione_carrello', 10, 2 );
function mostra_personalizzazione_carrello( $item_data, $cart_item ) {
    if ( isset($cart_item['personalizza_prodotto']) && $cart_item['personalizza_prodotto'] === 'yes' ) {
        $item_data[] = array(
            'name' => 'Personalizzazione',
            'value' => ! empty($cart_item['testo_personalizzato']) ? $cart_item['testo_personalizzato'] : 'Nessun testo inserito'
        );
        $item_data[] = array(
            'name' => 'Sovrapprezzo',
            'value' => '+10 €'
        );
    }
    return $item_data;
}

// 6. Salva i dati nell’ordine
add_action( 'woocommerce_checkout_create_order_line_item', 'salva_personalizzazione_ordine', 10, 4 );
function salva_personalizzazione_ordine( $item, $cart_item_key, $values, $order ) {
    if ( isset($values['personalizza_prodotto']) && $values['personalizza_prodotto'] === 'yes' ) {
        $item->add_meta_data( 'Personalizzazione', $values['testo_personalizzato'] );
        $item->add_meta_data( 'Sovrapprezzo', '+10 €' );
    }
}