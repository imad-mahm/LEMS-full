<?php
session_start(); // if you need sessions

// Connect to DB
$conn = new mysqli('localhost', 'root', '', 'lems');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the event ID and new state from POST
$eventId = $_POST['event_id'];
$newState = $_POST['new_state'];

// Update the event state
$stmt = $conn->prepare("UPDATE EVENT SET `STATE` = ? WHERE EVENTID = ?");
$stmt->bind_param("si", $newState, $eventId);

if ($stmt->execute()) {
    header("Location: AdminDashboard.php");
} else {
    echo "Error updating event: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
