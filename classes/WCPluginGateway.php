<?php

namespace WoocommercePlugin\classes;

use WC_Order;
use WoocommercePlugin\helpers\RutValidator;
use Swipe\lib\Transaction;

/**
 * Esta clase es la encargada de crear el gateway de pago
 *
 *  @autor Fabian Pacheco
 */

class WCPluginGateway extends \WC_Payment_Gateway
{
    public $token_service;
    public $token_secret;
    public $environment;
    public $notify_url;
    public $rut_comercio;

    public $icon_dir;


    public function __construct()
    {
        $this->icon_dir = plugin_dir_url(__FILE__) . '../assets/images/Logo-tuu-azul.png';

        $this->id = 'wcplugingateway';
        $this->icon = apply_filters('woocommerce_gateway_icon', $this->icon_dir);
        $this->has_fields = false;
        $this->method_title = 'TUU Checkout Pago Online';
        $this->method_description = 'Recibe pagos con tarjeta en tu tienda con la pasarela de pagos más conveniente.';
        $this->notify_url = WC()->api_request_url('WCPluginGateway');

        $this->supports = array(
            'products'
        );

        $this->init_form_fields();

        $this->init_settings();

        $this->title = "TUU Checkout";
        $this->description = "Paga con tarjetas de débito, crédito y prepago.";

        $this->environment = $this->get_option('ambiente');

        $this->rut_comercio = $this->get_option('rut');

        $this->enabled = $this->get_option('enabled');

        if ($this->rut_comercio != "") {
            $validator = new RutValidator();

            if ($validator->validate($this->rut_comercio)) {
                add_action(
                    'woocommerce_update_options_payment_gateways_' . $this->id,
                    array($this, 'process_admin_options')
                );
            } else {
                add_action('admin_notices', array($this, 'showRutErrorMessage'));
            }
        }

        add_filter('woocommerce_gateway_icon', array($this, 'setIcon'), 10, 2);

        add_action('woocommerce_receipt_' . $this->id, array($this, 'receiptPage'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));


        add_action("woocommerce_checkout_order_review", array($this, "checkoutOrder"), 10);
        add_action("woocommerce_thankyou", array($this, "thankyouPageCallback"), 10);
    }

    public function setIcon($icon, $id = null)
    {
        if ($id === null || $id === $this->id) {
            $icon = '<img src="' . $this->icon_dir . '" alt="TUU Checkout" width="200" height="100" 
            style="display: block; margin: 0 auto; vertical-align: baseline;" />';
        }
        return $icon;
    }

    /**
     * Opcciones de configuracion del plugin
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
        $order = new WC_Order($order_id);

        WC()->session->set('order_id', $order_id);

        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    public function receiptPage($order_id)
    {

        $url_res = $this->generateTransactionForm($order_id);

        if (isset($_GET['x_result']) and $_GET['x_result'] == 'failed') {
            $order_id = $_GET['x_reference'] ?? null;
            $order = new WC_Order($order_id);
            $order->update_status('cancelled', __('Pago cancelado', 'woocommerce'));
            WC()->cart->empty_cart();
            $order->add_order_note(
                __(
                    'Pago cancelado',
                    'woocommerce'
                )
            );

            echo "<h1 class='woocommerce-error'>El pago ha sido cancelado</h1>";
            echo "<script>
                setTimeout(function(){
                    window.location.href = '" . get_permalink(wc_get_page_id('shop')) . "';
                }, 5000); // Redirige después de 5 segundos
            </script>";
        } else {
            echo '<p>' . __('Gracias! - Tu orden ahora está pendiente de pago. 
        Deberías ser redirigido automáticamente a Web pay en 5 segundos.') . '</p>';

            $url_payment = get_post_meta($order_id, '_url_payment', true);

            echo '<p>Si no eres redirigido automáticamente, haz click en el siguiente botón:</p>';
            echo '<a href="' . $url_payment . '" class="button alt" id="submit_payment_form">'
                . __('Pagar', 'woocommerce') . '</a>';

            echo "<script>
                setTimeout(function(){
                    window.location.href = '" . $url_payment . "';
                }, 5000); // Redirige después de 5 segundos
            </script>";
        }
    }

    public function getSecretKeys($rut)
    {
        $url = $_ENV["URL_SK"] . $rut;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            curl_close($ch);
            return 'Error en cURL: ' . curl_error($ch);
        } elseif ($httpCode != 200) {
            curl_close($ch);
            return array('error' => true, 'message' => 'Error en la petición: ' . $httpCode);
        }

        curl_close($ch);

        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return 'Error decodificando JSON: ' . json_last_error_msg();
        }

        return $decodedResponse;
    }



    public function generateTransactionForm($order_id)
    {

        $order = new WC_Order($order_id);

        /*
         * Este es el token que representará la transaccion.
         */
        $token_tienda = (bin2hex(random_bytes(30)));

        $token_tienda_db = get_post_meta($order_id, "_token_tienda", true);
        if (is_null($token_tienda_db) || $token_tienda_db == "") {
            add_post_meta($order_id, '_token_tienda', $token_tienda, true);
        } else {
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


        $secret_keys = $this->getSecretKeys($this->rut_comercio);

        if (isset($secret_keys['error']) and $secret_keys['error'] == true) {
            header('Refresh: 5; URL=' . get_home_url() . '/');
            wp_die("Error al obtener claves secretas, comuniquese con el administrador del sitio");
        }

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
            "x_url_callback" => $this->notify_url,
            "x_url_cancel" => $order->get_checkout_payment_url(true) . "&",
            "x_url_complete" => $this->get_return_url($order) . "&",
            "secret" => $_ENV['SECRET'],
            "dte_type" => 48
        );

        $transaction = new Transaction();
        $transaction->environment =  $this->environment;
        $transaction->setToken($this->token_secret);
        $res = $transaction->initTransaction($new_data);

        $apiBaseUrl = $_ENV["URL_INTENT"];

        if (preg_match('/^' . preg_quote($apiBaseUrl, '/') . '([a-zA-Z0-9]{24})$/', $res, $matches)) {
            $identifier = $matches[1];
            $res = $apiBaseUrl . $identifier;
        } else {
            header('Refresh: 5; URL=' . get_home_url() . '/');
            wp_die("Error al obtener link de pago, comuniquese con el administrador del sitio");
        }


        add_post_meta($order_id, '_url_payment', $res, true);
        return $res;
    }

    public function showRutErrorMessage()
    {
        $message = "El rut ingresado no es válido, por favor ingrese un rut válido";
        echo "<div class='error is-dismissible'><p>$message</p></div>";
    }

    public function checkoutOrder()
    {
        $order_id = WC()->session->get('order_id');
        $order = new WC_Order($order_id);
        $user_id = $order->get_user_id();
        $orders = wc_get_orders(array(
            'limit' => -1,
            'customer_id' => $user_id,
            'status' => 'pending'
        ));
        $ordenes_pendientes = array();
        foreach ($orders as $order) {
            $res_url = $this->generateTransactionForm($order->get_id());
            $ordenes_pendientes[] = array(
                'order_id' => $order->get_id(),
                'url' => $res_url
            );
        }
        if (!empty($ordenes_pendientes)) {
            $respuesta = "<p>Ya tienes ordenes pendientes de pago, 
            si deseas pagar o cancelar una haz click en la orden correspondiente: ";

            foreach ($ordenes_pendientes as $orden) {
                $respuesta .= "<a href='" . $orden['url'] . "'>Orden: " . $orden['order_id'] . "</a> ";
            }
            $respuesta .= "</p>";
            wc_add_notice($respuesta, 'notice');
        }
    }

    public function thankyouPageCallback()
    {
        if (isset($_GET['x_result']) and $_GET['x_result'] == 'completed') {
            $order_id = $_GET['x_reference'] ?? null;
            $order = new WC_Order($order_id);
            $order->update_status('completed', __('Pago completado', 'woocommerce'));
            $order->payment_complete();
            $order->add_order_note(
                __(
                    'Pago completado',
                    'woocommerce'
                )
            );
            wc_add_notice(__('Pago completado', 'woocommerce'), 'success');
            WC()->cart->empty_cart();
            $url_home = get_home_url();
            echo "<script>
                setTimeout(function(){
                    window.location.href = '" . $url_home . "';
                }, 7000); // Redirige después de 7 segundos
            </script>";
        }
    }
}
