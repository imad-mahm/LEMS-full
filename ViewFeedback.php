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

$eventsmanager = new EventManager();
$eventsmanager->getAllEvents("past", "finished", $_SESSION['user']['clubs'][0]);
$events = $eventsmanager->events;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Feedback</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="feedback.css">
</head>
<body>
<header class="navbar">
    <a class="logo" href="home.php" style="text-decoration: none">
        <img src="logo.png" alt="LEMS Logo" />
        <span>LEMS</span>
    </a>
    <nav class="nav-links">
        <a href="browse.php">Browse Events</a>
        <a href="Recommended.php">Recommended</a>
        <?php
          //check user role
          if ($_SESSION['user']['role'] == 'organizer' || $_SESSION['user']['role'] == 'admin') {
            echo '<a href="organizer_dashboard.php">Organizer Dashboard</a>';
          }
          if ($_SESSION['user']['role'] == 'admin') {
            echo '<a href="AdminDashboard.php">Admin Dashboard</a>';
          }
        ?>
        <div class="profile-dropdown">
        <img
            src="https://img.icons8.com/ios-filled/24/ffffff/user.png"
            alt="User Icon"
            class="profile-icon"
            onclick="toggleDropdown()"
        />
        <div id="dropdown-menu" class="dropdown-menu">
            <a href="profile.php" class="dropdown-item profile-link"
            >Profile</a
            >
            <a
            href="auth/logout.php"
            class="dropdown-item logout-link"
            style="color: red"
            >Log Out</a
            >
        </div>
        </div>
    </nav>
</header>
<main class="container">
    <h1>Past Events</h1>
    <div class="feedback-container">
        <?php if (count($events) > 0): ?>
            <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <div class="event-image">
                        <?php if (!empty($event->createdBy)): ?><span class="category-tag"><?php echo htmlspecialchars($event->createdBy[0]); ?></span><?php endif; ?>
                        <img src="<?php echo htmlspecialchars($event->imageURL); ?>" alt="<?php echo htmlspecialchars($event->eventID); ?> image">
                    </div>
                    <h2><?php echo htmlspecialchars($event->title); ?></h2>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($event->startTime); ?></p>
                    <div class="event-detail">
                    <span class="icon">📍</span>
                    <?php
                    $locationId = $event->location;
                    $stmtLocation = $conn->prepare("SELECT * FROM `location` WHERE LOCATIONID = ?");
                    $stmtLocation->bind_param("i", $locationId);
                    $stmtLocation->execute();
                    $locationResult = $stmtLocation->get_result();
                    $location = $locationResult->fetch_assoc();
                    $stmtLocation->close();
                    ?>
                    <span><?php echo htmlspecialchars($location['CAMPUS']) . ", " . htmlspecialchars($location['BUILDING']) . ", " . htmlspecialchars($location['ROOM']); ?></span>
                    </div>
                    <a href="ViewReviews.php?event=<?php echo $event->eventID; ?>" class="view-feedback-button btn ">View Feedback</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No past events found.</p>
        <?php endif; ?>
    </div>
</main>
<script src="script.js"></script>
<script src="feedback.js"></script>
</body>