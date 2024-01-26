<?php

namespace WoocommercePlugin;


/*
  Plugin Name: Woocommerce payment gateway for Swipe
  Description: Plugin de pago para Woocommerce
  Version:     1.0.1
  Author:      Fabian Pacheco
 */

include_once 'vendor/autoload.php';

use WC_Order;

use WoocommercePlugin\classes\WC_Plugin_Gateway;

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();


/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */

add_action('wp_loaded', 'WoocommercePlugin\plugin_init_gateway_class');
function plugin_init_gateway_class()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class WC_Plugin_Gateway_Chile extends WC_Plugin_Gateway
    {
    }
}

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */

function plugin_add_gateway_class($gateways)
{
    $gateways[] = 'WoocommercePlugin\WC_Plugin_Gateway_Chile'; // your class name is here
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'WoocommercePlugin\plugin_add_gateway_class');

/*
* This action create a page with the shortcode [woocommerce_checkout]
*/

function crear_nueva_pagina_checkout()
{
    // Verificar si la página de checkout ya existe
    $pagina_existente = get_page_by_path('checkout-plugin-page');

    if (!$pagina_existente) {
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $nueva_pagina = array(
                'post_title' => 'Checkout Plugin Page',
                'post_name' => 'checkout-plugin-page',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_slug' => 'checkout-plugin-page',
                'post_author' => 1
            );

            $nueva_pagina_id = wp_insert_post($nueva_pagina);

            if ($nueva_pagina_id && !is_wp_error($nueva_pagina_id)) {
                $contenido = '[woocommerce_checkout]';
                update_post_meta($nueva_pagina_id, '_wp_page_template', 'default');
                wp_update_post(array(
                    'ID' => $nueva_pagina_id,
                    'post_content' => $contenido,
                ));

                // Añadir una opción o un log para indicar que la página se creó con éxito
                update_option('checkout_plugin_page_created', true);
                error_log('Página de checkout creada exitosamente.');
                cambiar_pagina_checkout($nueva_pagina_id);
            } else {
                // Puedes agregar un log o mostrar un mensaje de error si la página no se creó correctamente
                update_option('checkout_plugin_page_created', false);
                error_log('Error al crear la página de checkout.');
            }
        } else {
            error_log('Woocommerce no está instalado.');
        }
    } else {
        // La página ya existe, no es necesario crearla nuevamente
        update_option('checkout_plugin_page_exist', true);
        error_log('La página de checkout ya existe.');
    }
}

// Función para cambiar la página de checkout de WooCommerce
function cambiar_pagina_checkout($nueva_pagina_id)
{
    // Obtén las opciones de WooCommerce
    $woocommerce_options = get_option('woocommerce');

    // Actualiza la opción de la página de checkout
    $woocommerce_options['checkout_page_id'] = $nueva_pagina_id;

    // Guarda las opciones actualizadas
    update_option('woocommerce', $woocommerce_options);
}

// Agregar una acción para mostrar un mensaje en la interfaz de administración después de la activación del plugin
function mostrar_mensaje_activacion_plugin()
{
    if (get_option('checkout_plugin_page_exist')) {
        echo '<div class="notice notice-warning is-dismissible">
        <p>La página de checkout ya existe.</p>
    </div>';
    update_option('checkout_plugin_page_exist', false);
    } else {
        if (get_option('checkout_plugin_page_created') == true) {
            echo '<div class="notice notice-success is-dismissible">
                    <p>Página de checkout creada exitosamente.</p>
                </div>';
                update_option('checkout_plugin_page_exist', true);
        }else{
            echo '<div></div>';
        }
    }
    // Elimina la opción después de mostrar el mensaje
    delete_option('checkout_plugin_page_created');
}

add_action('admin_notices', 'WoocommercePlugin\mostrar_mensaje_activacion_plugin');

register_activation_hook(__FILE__, 'WoocommercePlugin\crear_nueva_pagina_checkout');
