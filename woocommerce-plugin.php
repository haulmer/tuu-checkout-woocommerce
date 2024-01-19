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
// require_once 'classes/WC_Plugin_Gateway.php';


/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */

add_action('plugins_loaded', 'WoocommercePlugin\plugin_init_gateway_class');
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
