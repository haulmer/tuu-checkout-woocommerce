<?php

namespace WoocommercePlugin\helpers;

class RutValidator
{
    public static function validate($rut)
    {
        // Remove any non-digit characters except 'k' or 'K'
        $rut = preg_replace('/[^0-9kK]/', '', $rut);
// Check if the RUT has a valid length (8-9 digits plus verification digit)
        if (strlen($rut) < 8 || strlen($rut) > 9) {
            return false;
        }

        // Separate the RUT and verification digit
        $rutNumber = substr($rut, 0, -1);
        $verificationDigit = strtoupper(substr($rut, -1));
// Calculate the verification digit
        $sum = 0;
        $multiplier = 2;
        for ($i = strlen($rutNumber) - 1; $i >= 0; $i--) {
            $sum += $rutNumber[$i] * $multiplier;
            $multiplier = $multiplier == 7 ? 2 : $multiplier + 1;
        }
        $modulo = $sum % 11;
        $verificationDigitExpected = 11 - $modulo;
        if ($verificationDigitExpected == 10) {
            $verificationDigitExpected = 'K';
        } elseif ($verificationDigitExpected == 11) {
            $verificationDigitExpected = '0';
        }

        // Compare the calculated verification digit with the provided one
        return $verificationDigit === (string)$verificationDigitExpected;
    }
}
