<?php

namespace WoocommercePlugin\classes;

use WC_Order;

/** 
 * Esta clase es la encargada de crear el gateway de pago
 * 
 *  @autor Fabian Pacheco
 */

class WC_Plugin_Gateway extends \WC_Payment_Gateway
{
    public $token_service;
    public $token_secret;
    public $environment;

    /**
     * Class constructor, more about it in Step 3
     */
    public function __construct()
    {

        $this->id = 'pluginid'; // id del plugin
        $this->icon = ''; // url del icono(si hubiera)
        $this->has_fields = true; // si necesita campos de pago
        $this->method_title = 'Fosi Payment Gateway';
        $this->method_description = 'Payment plugin gateway for Woocommerce'; // will be displayed on the options page

        // gateways can support subscriptions, refunds, saved payment methods,
        // but we have simple payments
        $this->supports = array(
            'products'
        );

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $this->environment = $this->get_option('ambiente');

        $this->token_service = $this->get_option('token_service');
        $this->token_secret = $this->get_option('token_secret');


        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        // This action hook saves the settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // We need custom JavaScript to obtain a token
        // add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        // You can also register a webhook here
        // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
    }

    /**
     * Plugin options, we deal with it in Step 3 too
     */
    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable plugin Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'yes'
            ),
            'ambiente' => array(
                'title' => __('Ambiente', 'woocommerce'),
                'type' => 'select',
                'label' => __('Habilita el modo de pruebas', 'woocommerce'),
                'default' => 'PRODUCCION',
                'options' => array(
                    'PRODUCCION' => 'Producción',
                    'DESARROLLO' => 'Desarrollo',
                )
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'Woocommerce plugin Gateway',
                'default'     => ' ',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'mensaje que se muestra en la pagina de pago',
                'default'     => 'Paga con tarjetas de crédito, débito y prepago a través de Webpay Plus',
            ),
            'token_service' => array(
                'title' => "ID de Cuenta",
                'type' => 'text',
                'description' => "Ingresa el Account ID de Swipe",
                'default' => "",
            ),
            'token_secret' => array(
                'title' => "Llave Secreta",
                'type' => 'text',
                'description' => "Ingresa la Secret Key de Swipe",
                'default' => "",
            ),
            'redirect' => array(
                'title' => __(''),
                'type' => 'hidden',
                'label' => __('Si / No'),
                'default' => 'yes'
            )
        );
    }

    /*
		 * Funcion necesaria para hacer el pago(crea el boton de pago)
		 */
    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);

        // Mark as on-hold (we're awaiting the cheque)
        $order->update_status('on-hold', __('Awaiting cheque payment', 'woocommerce'));


        // Remove cart
        $woocommerce->cart->empty_cart();

        // Return thankyou redirect
        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    /**
     * You will need it if you want your custom credit card form, Step 4 is about it
     */
    public function payment_fields()
    {
    }

    /*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
    public function payment_scripts()
    {
    }

    /*
 		 * Fields validation, more in Step 5
		 */
    public function validate_fields()
    {
    }
}
