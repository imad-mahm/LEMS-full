<?php
if (!isset($_GET['code'])) die('No code returned');

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$client_id = $_ENV['Application_ID'];
// Change this line - use 'common' instead of the specific tenant ID
$token_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

$post_fields = [
    'client_id' => $client_id,
    'scope' => 'openid email profile User.Read',
    'code' => $_GET['code'],
    'redirect_uri' => 'http://localhost/LEMS/auth/callback.php',
    'grant_type' => 'authorization_code',
    'client_secret' => $_ENV['Secret_Value'],
];

$ch = curl_init($token_url);
echo "Token URL: $token_url <br>"; // For debugging
echo "cURL handle created: " . (is_resource($ch) ? 'Yes' : 'No') . "\n";
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
$response = curl_exec($ch);
echo "Response:" . $response."\n"; // For debugging
curl_close($ch);

$token_data = json_decode($response, true);
$access_token = $token_data['access_token'] ?? null;

if (!$access_token) {
    // For debugging
    echo "Token response: ";
    print_r($token_data);
    die('Failed to get access token');
}

// Get user info
$user_info_url = 'https://graph.microsoft.com/v1.0/me';
$headers = ["Authorization: Bearer $access_token"];
$ch = curl_init($user_info_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$user_response = curl_exec($ch);
curl_close($ch);

$user = json_decode($user_response, true);
if(!str_contains($user['mail'], "lau.edu") && !str_contains($user['mail'], "lau.edu.lb")){ 
    $error_message = urlencode("You are not authorized to access this page.");
    header("Location: index.html?error=" . $error_message);
    exit;
}

// Start session
session_start();

// Store complete user object
$_SESSION['user'] = $user;
echo $user;

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'lems';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user exists in the database
$stmt = $conn->prepare("SELECT * FROM user WHERE LAU_email = ?");
$stmt->bind_param("s", $_SESSION['user']['mail']);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0) {
    // User exists, update their information
    $stmt = $conn->prepare("UPDATE user SET LAU_EMAIL = ?, FIRST_NAME = ?, LAST_NAME = ? WHERE LAU_EMAIL = ?");
    //split display name into first and last name
    $display_name = $_SESSION['user']['displayName'];
    $display_name = explode(' ', $display_name, 2);
    $first_name = $display_name[0];
    $last_name = isset($display_name[1]) ? $display_name[1] : '';
    $stmt->bind_param("ssss", $_SESSION['user']['mail'], $first_name, $last_name, $_SESSION['user']['mail']);
    $stmt->execute();
} else {
    // User does not exist, insert into database
    $stmt = $conn->prepare("INSERT INTO user (LAU_EMAIL, USER_ROLE, FIRST_NAME, LAST_NAME) VALUES (?, 'user', ?, ?)");
    //split display name into first and last name
    $display_name = $_SESSION['user']['displayName'];
    $display_name = explode(' ', $display_name, 2);
    $first_name = $display_name[0];
    $last_name = isset($display_name[1]) ? $display_name[1] : '';
    $stmt->bind_param("sss", $_SESSION['user']['mail'], $first_name, $last_name);
    $stmt->execute();
}
// Redirect to home page
header('Location: ../home.php');
exit;
$stmt->close();
$conn->close();

?>