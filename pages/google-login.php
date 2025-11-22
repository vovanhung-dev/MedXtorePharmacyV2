<?php
require_once __DIR__ . '/../config/google-config.php';

// Khởi tạo đối tượng Google Client
$client = getGoogleClient();

// Tạo URL đăng nhập Google và chuyển hướng
$authUrl = $client->createAuthUrl();
header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit;
