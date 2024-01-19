<?php

/**
 * Esta clase es un logger para el plugin
 * 
 * @autor Fabian Pacheco
 */

namespace WoocommercePlugin\classes;

class Logger {

    public static function log($message, $level = 'info') {
        $log = new \WC_Logger();
        $log->log($level, $message);
    }
    
}