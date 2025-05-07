<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit();
}
if ($_SESSION['user']['role'] != 'admin') {
    header("Location: home.php");
    exit();
}
include "db_connection.php";
require_once "classes.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['event_id'])) {
    $eventId = intval($_POST['event_id']);

    // Check database connection
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Debug: Log the event ID
    error_log("Attempting to delete event with ID: $eventId");

    // First delete all registrations related to the event
    if (!$conn->query("DELETE FROM registration WHERE EVENTID = $eventId")) {
        error_log("Error deleting registrations: " . $conn->error);
    }

    // Delete any associated clubs (optional if needed)
    if (!$conn->query("DELETE FROM event_club WHERE EVENTID = $eventId")) {
        error_log("Error deleting event_club: " . $conn->error);
    }

    // Delete any associated tags (optional if needed)
    if (!$conn->query("DELETE FROM event_tags WHERE EVENTID = $eventId")) {
        error_log("Error deleting event_tags: " . $conn->error);
    }

    // Finally delete the event
    if (!$conn->query("DELETE FROM event WHERE EVENTID = $eventId")) {
        error_log("Error deleting event: " . $conn->error);
    }

    // Debug: Confirm deletion
    if ($conn->affected_rows > 0) {
        error_log("Event with ID $eventId deleted successfully.");
    } else {
        error_log("No rows affected. Event with ID $eventId may not exist.");
    }

    header("Location: AdminDashboard.php");
    exit();
} else {
    echo "Invalid request.";
}
?>
