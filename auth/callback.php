<?php
if (!isset($_GET['code'])) die('No code returned');

require '../vendor/autoload.php';
require_once '../classes.php'; // Include your classes here

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
	header("Location: ../index.html?error=" . $error_message);
	exit;
}

// Start session
session_start();



// Check if user exists in the database
include '../db_connection.php'; // Include your database connection file

$DBuser = new User();
// Check if the user already exists in the database
$stmt = $conn->prepare("SELECT * FROM user WHERE LAU_email = ?");
$stmt->bind_param("s", $user['mail']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // User exists, update their profile
    $DBuser->updateProfile($user);
    $DBuser->getUserInfo($user['mail']); // Ensure the object is populated
} else {
    // User does not exist, insert into database
    $DBuser->createUser($user);
    $DBuser->getUserInfo($user['mail']); // Populate the object after creation
}

// Store user information in session
$_SESSION['user'] = [
    'email' => $DBuser->email,
    'role' => $DBuser->role,
    'firstName' => $DBuser->firstName,
    'lastName' => $DBuser->lastName,
    'clubs' => $DBuser->club
];

// Redirect to home page
header('Location: ../home.php');
exit;
$stmt->close();
$conn->close();

?>