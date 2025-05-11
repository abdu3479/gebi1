<?php
/**
 * Telebirr Configuration for Werabe Land Lease System
 * Replace placeholders with real credentials from telebirr@ethiotelecom.et
 */
return [
    'app_id' => 'TEST_APP_ID', // Replace with real app_id
    'app_key' => 'TEST_APP_KEY', // Replace with real app_key
    'public_key' => '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCqGKukO1De7zhZj6+H0qtjTkVxwTCpvKe4eCZ0
FPqri0cb2JZfXJ/DgYSF6vUpwmJG8wVQZKjeGcjDOL5UlsuusFncCzWBQ7RKNUSesmQRMSGkVb1/
3j+skZ6UtW+5u09lHNsj6tQ51s1SPrCBkedbNf0Tp0GbMJDyR4e9T04ZZwIDAQAB
-----END PUBLIC KEY-----', // Replace with real public_key
    'short_code' => 'TEST_SHORT_CODE', // Replace with real short_code
    'api_url' => 'https://api.ethiotelecom.et/telebirr/v1/payment',
    'notify_url' => 'http://localhost/gebi1/payments/telebirr_notify.php',
    'return_url' => 'http://localhost/gebi1/payments/return.php',
    'receive_name' => 'Werabe Land Lease',
    'timeout_express' => '30'
];
?>