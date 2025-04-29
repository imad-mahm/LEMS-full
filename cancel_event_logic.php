<?php
// filepath: c:\xampp\htdocs\LEMS\cancel_event_logic.php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit();
}

if ($_SESSION['user']['user_role'] != 'organizer' && $_SESSION['user']['user_role'] != 'admin') {
    header("Location: home.php");
    exit();
}

if (!isset($_GET['event'])) {
    header("Location: ViewEvents.php");
    exit();
}

$eventId = intval($_GET['event']);

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'lems';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the event exists and is approved
$stmt = $conn->prepare("SELECT * FROM Event WHERE EVENTID = ? AND STATE = 'approved'");
$stmt->bind_param("i", $eventId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Event not found or not in approved state
    $stmt->close();
    $conn->close();
    header("Location: ViewEvents.php?error=not+found");
    exit();
}

// Update the event state to "cancelled"
$updateStmt = $conn->prepare("UPDATE Event SET STATE = 'cancelled' WHERE EVENTID = ?");
$updateStmt->bind_param("i", $eventId);

if ($updateStmt->execute()) {
    $updateStmt->close();
    $conn->close();
    header("Location: ViewEvents.php?success=true");
    exit();
} else {
    $updateStmt->close();
    $conn->close();
    header("Location: ViewEvents.php?error=FailedToCancelEvent");
    exit();
}
?>