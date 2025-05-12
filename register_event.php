<?php
session_start();
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'lems';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['event_id']) || !isset($_POST['action'])) {
        echo 'invalid_request';
        exit;
    }

    $event_id = intval($_POST['event_id']);
    $action = $_POST['action']; // either 'register' or 'cancel'

    if (!isset($_SESSION['user']['email'])) {
        echo 'no_session';
        exit;
    }

    $lau_email = $_SESSION['user']['email'];
    $timestamp = date('Y-m-d H:i:s');

    if ($action === 'register') {
        // Check if already registered
        $check_stmt = $conn->prepare("SELECT 1 FROM registration WHERE LAU_EMAIL = ? AND EVENTID = ?");
        $check_stmt->bind_param("si", $lau_email, $event_id);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            echo 'already_registered';
            exit;
        }
        $check_stmt->close();

        // Get current ticket number
        $count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registration WHERE EVENTID = ?");
        $count_stmt->bind_param("i", $event_id);
        $count_stmt->execute();
        $count_stmt->bind_result($current_count);
        $count_stmt->fetch();
        $count_stmt->close();

        $new_ticket_nb = $current_count + 1;

        // Insert registration
        $insert_stmt = $conn->prepare("INSERT INTO registration (LAU_EMAIL, EVENTID, TICKET_NB, BOOKED_AT) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("siis", $lau_email, $event_id, $new_ticket_nb, $timestamp);

        if ($insert_stmt->execute()) {
            echo 'registered';
        } else {
            echo 'db_error';
        }

        $insert_stmt->close();
    } elseif ($action === 'cancel') {
        // Cancel registration
        $delete_stmt = $conn->prepare("DELETE FROM registration WHERE LAU_EMAIL = ? AND EVENTID = ?");
        $delete_stmt->bind_param("si", $lau_email, $event_id);

        if ($delete_stmt->execute()) {
            echo 'cancelled';
        } else {
            echo 'db_error';
        }

        $delete_stmt->close();
    } else {
        echo 'invalid_action';
    }
} else {
    echo 'invalid_request';
}
?>