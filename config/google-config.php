<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Cấu hình Google OAuth từ biến môi trường
$googleClientID = $_ENV['GOOGLE_CLIENT_ID'];
$googleClientSecret = $_ENV['GOOGLE_CLIENT_SECRET'];
$googleRedirectURL = $_ENV['GOOGLE_REDIRECT_URL'];

// Khởi tạo Google Client
function getGoogleClient() {
    global $googleClientID, $googleClientSecret, $googleRedirectURL;

    $client = new Google_Client();
    $client->setClientId($googleClientID);
    $client->setClientSecret($googleClientSecret);
    $client->setRedirectUri($googleRedirectURL);
    $client->addScope("email");
    $client->addScope("profile");

    return $client;
}
