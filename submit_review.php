<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "lems";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $eventId = intval($_POST['event_id']);
    $rating = floatval($_POST['rating']);
    $content = trim($_POST['content']);
    $lau_email = $_POST['user'];

    $stmt = $conn->prepare("INSERT INTO feedback (RATING, CONTENT, LAU_EMAIL, EVENTID) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("dssi", $rating, $content, $lau_email, $eventId);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header("Location: event_details.php?event=" . $eventId);
    exit();
}
?>