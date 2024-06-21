<?php

if (!function_exists('encryptData')) {
    function encryptData($data, $key) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . base64_encode($iv));
    }
}

if (!function_exists('decryptData')) {
    function decryptData($data, $key) {
        $decoded_data = base64_decode($data);
        if ($decoded_data === false) {
            return null;
        }
        $parts = explode('::', $decoded_data, 2);
        if (count($parts) === 2) {
            $encrypted_data = $parts[0];
            $iv = base64_decode($parts[1]);
            if ($iv !== false) {
                $iv = substr($iv, 0, openssl_cipher_iv_length('aes-256-cbc')); 
                $decrypted = openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
                return $decrypted !== false ? $decrypted : null;
            }
        }
        return null;
    }
}
?>
