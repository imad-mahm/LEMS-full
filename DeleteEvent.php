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
