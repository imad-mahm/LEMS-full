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

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'lems';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == 0) {
    $targetDir = "uploads/"; // folder where images will go
    $fileName = basename($_FILES["event_image"]["name"]);
    $targetFilePath = $targetDir . $fileName;

    // Move the uploaded file
    if (move_uploaded_file($_FILES["event_image"]["tmp_name"], $targetFilePath)) {
        $imageUrl = $targetFilePath;

        $event_name = $_POST['event_name'];
        $event_description = $_POST['event_description'];
        $event_date = $_POST['event_date'];
        $event_time = $_POST['event_time'];
        $event_duration = $_POST['event_duration'];
        $location = $_POST['location'];
        $event_capacity = $_POST['event_capacity'];
        $club_id = $_POST['club']; // Get the club ID from the form

        // Insert into the Event table
        $stmt = $conn->prepare('INSERT INTO `EVENT` (EVENT_NAME, EVENT_DESCRIPTION, DURATION, START_TIME, END_TIME, LOCATIONID, IMAGE_URL, CAPACITY) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $start_date_time = $event_date . ' ' . $event_time;

        $event_time = new DateTime($event_time);
        $interval = new DateInterval('PT' . $event_duration . 'M');
        $event_time->add($interval);
        $endTime = $event_time->format('H:i:s');

        $end_date_time = $event_date . ' ' . $endTime;

        $stmt->bind_param('sssssisi', $event_name, $event_description, $event_duration, $start_date_time, $end_date_time, $location, $imageUrl, $event_capacity);
        if ($stmt->execute()) {
            // Get the last inserted event ID
            $event_id = $stmt->insert_id;

            // Insert into the Event_Club table
            $stmtEventClub = $conn->prepare('INSERT INTO `EVENT_CLUB` (EVENTID, CLUBID, EXCLUSIVE) VALUES (?, ?, False)');
            $stmtEventClub->bind_param('ii', $event_id, $club_id);

            if ($stmtEventClub->execute()) {
                $stmtEventClub->close();
            } else {
                echo "Error inserting into Event_Club table.";
            }
        } else {
            echo "Error inserting into Event table.";
        }

        $stmt->close();
    } else {
        echo "Error uploading file.";
    }
} else {
    echo "No file uploaded or upload error.";
}

$conn->close();
header("Location: CreateEvent.php");
?>