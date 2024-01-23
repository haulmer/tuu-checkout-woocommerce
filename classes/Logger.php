<?php

/**
 * Esta clase es un logger para el plugin
 * 
 * @autor Fabian Pacheco
 */

namespace WoocommercePlugin\classes;

class Logger {

    public static function log($message, $level = 'info') {
        $logger = new \WC_Logger();
        $logger->log($level, $message);
        error_log($message);
    }
    
}