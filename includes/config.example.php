<?php
// Database Configuration
$db_host = 'localhost';
$db_name = 'your_database_name';
$db_user = 'your_database_user';
$db_pass = 'your_database_password';

// MoMo Payment Configuration
$config = [
    'partnerCode' => 'YOUR_PARTNER_CODE',
    'accessKey' => 'YOUR_ACCESS_KEY',
    'secretKey' => 'YOUR_SECRET_KEY',
    'endpoint' => 'https://test-payment.momo.vn/v2/gateway/api/create',
    'ipnUrl' => 'YOUR_IPN_URL',
    'redirectUrl' => 'YOUR_REDIRECT_URL'
];

// Google OAuth Configuration
$google_client_id = 'YOUR_GOOGLE_CLIENT_ID';
$google_client_secret = 'YOUR_GOOGLE_CLIENT_SECRET';
$google_redirect_uri = 'YOUR_REDIRECT_URI'; 