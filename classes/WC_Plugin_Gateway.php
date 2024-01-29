<?php

namespace WoocommercePlugin\classes;

use WC_Order;
use WoocommercePlugin\classes\Logger;
use WoocommercePlugin\helpers\RutValidator;

use Swipe\lib\Request;
use Swipe\lib\Response;
use Swipe\lib\Transaction;


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
    public $notify_url;
    public $rut_comercio;

    /**
     * Class constructor, more about it in Step 3
     */
    public function __construct()
    {

        $this->id = 'pluginid'; // id del plugin
        // $this->icon = ''; // url del icono(si hubiera)
        $this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/../assets/images/Logo-tuu-azul.svg';
        $this->has_fields = false; // si necesita campos de pago
        $this->method_title = 'TUU Checkout Pago Online';
        $this->method_description = 'Recibe pagos con tarjeta en tu tienda con la pasarela de pagos más conveniente.'; // will be displayed on the options page
        $this->notify_url = WC()->api_request_url('WC_Plugin_Gateway'); // esta es la url que se llama cuando se hace el pago, pero no se usa, o si?

        // gateways can support subscriptions, refunds, saved payment methods,
        // but we have simple payments
        $this->supports = array(
            'products'
        );

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // $this->title = $this->get_option('title');
        // $this->description = $this->get_option('description');
        $this->title = "TUU Checkout";
        $this->description = "Paga con tarjetas de débito, crédito y prepago.";
        
        $this->environment = $this->get_option('ambiente');

        $this->rut_comercio = $this->get_option('rut');

        $this->enabled = $this->get_option('enabled');

        $validator = new RutValidator();
        //validate rut, if true call update action to save admin options
        if ($validator->validate($this->rut_comercio)) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));  
        }else{
            // show error message in admin
            add_action('admin_notices', array($this, 'show_rut_error_message'));
        }


        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        // This action hook saves the settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'webhook'));
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
                'title'       => 'Titulo',
                'value'       => 'TUU Checkout',
                'type'        => 'text',
                'default'     => 'TUU Checkout',
                "custom_attributes" => array("readonly" => "readonly")
            ),
            'description' => array(
                'title'       => 'Descripción',
                'type'        => 'textarea',
                'value'       => 'Paga con tarjetas de débito, crédito y prepago.',
                'default'     => 'Paga con tarjetas de débito, crédito y prepago.',
                "custom_attributes" => array("readonly" => "readonly")

            ),
            'redirect' => array(
                'title' => __(''),
                'type' => 'hidden',
                'label' => __('Si / No'),
                'default' => 'yes'
            ),
            'rut' => array(
                'title' => __('Rut Comercio', 'woocommerce'),
                'type' => 'text',
                'description' => 'El rut es necesario para poder emitir las keys de acceso a los servicios de pago',
                'label' => __('Rut de la tienda', 'woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'placeholder' => '12345678-9'
            ),
        );
    }

    /*
		 * Funcion necesaria para hacer el pago(crea el boton de pago)
		 */
    public function process_payment($order_id)
    {
        Logger::log("iniciando el proceso de pago", "info");
        $order = new WC_Order($order_id);

        // Mark as on-hold (we're awaiting the cheque)
        // $order->update_status('on-hold', __('Awaiting cheque payment', 'woocommerce'));

        // Remove cart
        // $woocommerce->cart->empty_cart();

        // Return thankyou redirect
        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }
    

    public function receipt_page($order_id)
    {
        $sufijo = "[RECEIPT]";
        $DOBLEVALIDACION = $this->get_option('doblevalidacion');
        $order = new WC_Order($order_id);
        if ($DOBLEVALIDACION === "yes") {
            error_log("Doble Validación Activada / " . $order->status);
            if ($order->status === 'processing' || $order->status === 'completed') {
                Logger::log("ORDEN YA PAGADA (" . $order->get_status() . ") EXISTENTE " . $order_id);
                // Por solicitud muestro página de fracaso.
                //$this->paginaError($order_id);
                return false;
            }
        } else {
            Logger::log("Doble Validación Desactivada / " . $order->get_status());
        }

        echo '<p>' . __('Gracias! - Tu orden ahora está pendiente de pago. Deberías ser redirigido automáticamente a Web pay.') . '</p>';
        echo $this->generate_transaction_form($order_id);
    }

    public function get_secret_keys($rut) {
        $url = $_ENV["URL_SK"] . $rut;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json')); // Asegurar que se espera un JSON
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Obtener el código de respuesta HTTP
    
        if (curl_errno($ch)) {
            // Manejo del error de cURL
            curl_close($ch);
            return 'Error en cURL: ' . curl_error($ch);
        } else if ($httpCode != 200) {
            // Manejo de otros códigos HTTP que no sean 200 OK
            curl_close($ch);
            return 'Error HTTP: ' . $httpCode;
        }
    
        curl_close($ch);
    
        $decodedResponse = json_decode($response, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Error al decodificar JSON
            return 'Error decodificando JSON: ' . json_last_error_msg();
        }
    
        return $decodedResponse; // Retorna la respuesta decodificada
    }
    
    

    public function generate_transaction_form($order_id)
    {
        $SUFIJO = "[WEBPAY - FORM]";

        $order = new WC_Order($order_id);

        /*
         * Este es el token que representará la transaccion.
         */
        $token_tienda = (bin2hex(random_bytes(30)));

        /*
         * Agregamos el id de sesion la OC.
         * Esto permitira que validemos el pago mas tarde
         * Este valor no cambiara para la OC si est que ya está Creado
         *
         */
        $token_tienda_db = get_post_meta($order_id, "_token_tienda", true);
        Logger::log($token_tienda_db);
        if (is_null($token_tienda_db) || $token_tienda_db == "") {
            Logger::log("No existe TOKEN, lo agrego");
            add_post_meta($order_id, '_token_tienda', $token_tienda, true);
        } else {
            Logger::log("Existe session");
            $token_tienda = $token_tienda_db;
        }

        $monto = round($order->get_total());
        $email = $order->get_billing_email();
        $shop_country = $order->get_billing_country();

        $nombre_customer = $order->get_billing_first_name();
        $apellido = $order->get_billing_last_name();
        $telefono = $order->get_billing_phone();
        $nombreSitio = get_bloginfo('name');

        $line_items = $order->get_items();
        $cadenaProductos = '';
        foreach ($line_items as $item) {
            $nombre_producto = $item->get_name();
            $cantidad = $item->get_quantity();
            $cadenaProductos .= $nombre_producto . ' (Cantidad: ' . $cantidad . '), ';
        }
        $cadenaProductos = rtrim($cadenaProductos, ', ');
        
        // get input by custom payment fields



        $request = new Request();
        //$request->account_id = $this->token_service;
        //$request->amount = round($monto);

        /*$request->currency = get_woocommerce_currency();
        $request->reference = $order_id;
        $request->customer_email =  $email;
        $request->url_complete = $this->notify_url.'?complete';
        $request->url_cancel = $this->notify_url;
        $request->url_callback =  $this->notify_url.'?callback';
        $request->shop_name = "Tienda Yrvin";
        $request->shop_country = !empty($shop_country) ? $shop_country : 'CL';
        $request->session_id = date('Ymdhis').rand(0, 9).rand(0, 9).rand(0, 9);*/

        //echo $this->notify_url;
        //exit;

        $data = array(
            "x_account_id" => $this->token_service,
            "x_amount" => round($monto),
            "x_currency" => get_woocommerce_currency(),
            "x_customer_billing_address1" => "",
            "x_customer_billing_address2" => "",
            "x_customer_billing_city" => "",
            "x_customer_billing_company" => "",
            "x_customer_billing_country" => "",
            "x_customer_billing_first_name" => "",
            "x_customer_billing_last_name" => "",
            "x_customer_billing_phone" => "",
            "x_customer_billing_state" => "",
            "x_customer_billing_zip" => "",
            "x_customer_email" => $email,
            "x_customer_first_name" => $nombre_customer,
            "x_customer_last_name" => $apellido,
            "x_customer_phone" => $telefono,
            "x_customer_shipping_address1" => "",
            "x_customer_shipping_address2" => "",
            "x_customer_shipping_city" => "",
            "x_customer_shipping_company" => "",
            "x_customer_shipping_country" => "",
            "x_customer_shipping_first_name" => "",
            "x_customer_shipping_last_name" => "",
            "x_customer_shipping_phone" => "",
            "x_customer_shipping_state" => "",
            "x_customer_shipping_zip" => "",
            "x_description" => $cadenaProductos,
            "x_invoice" => "",
            "x_reference" => $order_id,
            "x_shop_country" => !empty($shop_country) ? $shop_country : 'CL',
            "x_shop_name" => $nombreSitio,
            "x_test" => "true",
            "x_transaction_type" => "",
            "x_url_callback" => $this->notify_url,
            "x_url_cancel" => $this->notify_url,
            "x_url_complete" => $this->notify_url
        );

        $secret_keys = $this->get_secret_keys($this->rut_comercio);

        $this->token_secret = $secret_keys['secret_key'];
        $this->token_service = $secret_keys['account_id'];

        $new_data = array(
            "platform" => "woocommerce",
            "paymentMethod" => "webpay",
            "x_account_id" => $this->token_service,
            "x_amount" => round($monto),
            "x_currency" => get_woocommerce_currency(),
            "x_customer_email" => $email,
            "x_customer_first_name" => $nombre_customer,
            "x_customer_last_name" => $apellido,
            "x_customer_phone" => $telefono,
            "x_description" => $cadenaProductos,
            "x_reference" => $order_id,
            "x_shop_country" => !empty($shop_country) ? $shop_country : 'CL',
            "x_shop_name" => $nombreSitio,
            "x_url_callback" => $_ENV['URL_CALLBACK'],
            "x_url_cancel" => $this->notify_url,
            "x_url_complete" => $this->notify_url,
            "secret" => $_ENV['SECRET'],
            "dte_type" => 48
        );

        logger::log("Datos enviados a Swipe: " . json_encode($new_data));
        error_log("rut comercio: " . $this->rut_comercio);
        

        $transaction = new Transaction();
        $transaction->environment =  $this->environment;
        $transaction->setToken($this->token_secret);
        $res = $transaction->initTransaction($new_data);
        error_log("Respuesta de Swipe: " . json_encode($res));
        // set time out to 3 seconds before redirect to $response
        sleep(3);
        wp_redirect($res, 301);
    }

    public function show_rut_error_message()
    {
        $message = "El rut ingresado no es válido, por favor ingrese un rut válido";
        echo '<div class="error is-dismisible"><p>$message</p></div>';
    }
}
