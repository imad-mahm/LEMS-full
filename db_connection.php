<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database configuration
$host = $_ENV['DB_DOMAIN']; // Database host
$dbname = $_ENV['DB_Name'];    // Database name
$username = $_ENV['DB_Username'];  // Database username
$password = "";  // Database password (default is empty for XAMPP)

// Create a connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>