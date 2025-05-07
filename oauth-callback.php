<?php
require 'vendor/autoload.php'; // Make sure Google Client Library is available

$client = new Google_Client();
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$id = $_ENV['GOOGLE_CLIENT_ID'];
$secret = $_ENV['GOOGLE_CLIENT_SECRET'];
$redirectUri = $_ENV['GOOGLE_REDIRECT_URI'];
$client->setClientId($id);
$client->setClientSecret($secret);
$client->setRedirectUri($redirectUri); // Or your domain
$client->setAccessType('offline');
$client->setPrompt('consent');
$client->addScope('https://mail.google.com/');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        echo "Error fetching token: " . $token['error_description'];
        exit;
    }

    // Check if refresh token is available
    if (!isset($token['refresh_token'])) {
        echo "No refresh token received. Try revoking app access from your Google Account and authorize again.";
        exit;
    }

    // Save refresh token and other data
    file_put_contents('gmail-refresh-token.json', json_encode($token));
    echo "OAuth success! Refresh token saved. You can now send emails using Gmail OAuth.";
} else {
    echo "No 'code' found in URL. Did you access this page via Google's redirect?";
}
?>