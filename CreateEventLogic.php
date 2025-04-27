<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit();
}
if($_SESSION['user']['user_role'] != 'admin' && $_SESSION['user']['user_role'] != 'organizer'){
    header("Location: home.php");
    exit();
}

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'lems';

$conn =new mysqli($host, $username, $password, $database);
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
        $location = $_POST['event_location'];
        $event_capacity = $_POST['event_capacity'];

        $stmt = $conn->prepare('INSERT INTO `EVENT`(EVENT_NAME, EVENT_DESCRIPTION, DURATION, START_TIME, END_TIME, LOCATIONID, IMAGE_URL, CAPACITY)  VALUES (?,?,?,?,?,?,?,?)');
        $start_date_time = $event_date . ' ' . $event_time;

        $event_time = new DateTime($event_time);
        $interval = new DateInterval('PT' . $event_duration . 'M');
        $event_time->add($interval);
        $endTime = $event_time->format('H:i:s');

        $end_date_time = $event_date .' '. $endTime;

        $stmt->bind_param('ssssssss', $event_name, $event_description, $event_duration, $start_date_time, $end_date_time, $location, $imageUrl, $event_capacity);
        $stmt->execute();
    } else {
        echo "Error uploading file.";
    }
} else {
    echo "No file uploaded or upload error.";
}

header("Location: CreateEvent.php");
?>