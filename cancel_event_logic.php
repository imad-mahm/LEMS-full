<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit();
}
if ($_SESSION['user']['role'] != 'admin' && $_SESSION['user']['role'] != 'organizer') {
    header("Location: home.php");
    exit();
}
include "db_connection.php";
require_once "classes.php";

if (!isset($_GET['event'])) {
    header("Location: ViewEvents.php");
    exit();
}

$eventId = intval($_GET['event']);  

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