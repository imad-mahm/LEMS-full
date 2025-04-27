<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['user_role'] != 'admin') {
    header("Location: index.html");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lems";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['event_id'])) {
    $eventId = intval($_POST['event_id']);

    // First delete all registrations related to the event
    $conn->query("DELETE FROM registration WHERE EVENTID = $eventId");

    // Delete any associated clubs (optional if needed)
    $conn->query("DELETE FROM event_club WHERE EVENTID = $eventId");

    // Delete any associated tags (optional if needed)
    $conn->query("DELETE FROM event_tags WHERE EVENTID = $eventId");

    // Finally delete the event
    $conn->query("DELETE FROM event WHERE EVENTID = $eventId");

    header("Location: AdminDashboard.php");
    exit();
} else {
    echo "Invalid request.";
}
?>
