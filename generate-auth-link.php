<?php
require 'vendor/autoload.php';

$client = new Google_Client();
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$id = $_ENV['GOOGLE_CLIENT_ID'];
$secret = $_ENV['GOOGLE_CLIENT_SECRET'];
$redirectUri = $_ENV['GOOGLE_REDIRECT_URI'];
echo "Client ID: $id\n";
echo "Client Secret: $secret\n";
echo "Redirect URI: $redirectUri\n";
$client->setClientId($id);
$client->setClientSecret($secret);
$client->setRedirectUri($redirectUri); // Or your domain
$client->setAccessType('offline');
$client->setPrompt('consent');
$client->addScope('https://mail.google.com/');

$authUrl = $client->createAuthUrl();
echo "<a href='$authUrl'>Click here to authorize Gmail</a>";
?>