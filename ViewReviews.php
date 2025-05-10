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

$eventID = $_GET['event'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews</title>
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
<div class="container">
	<h1>Event Reviews</h1>
	<div class="event-details">
		<?php
		$event = new Event();
		$event->getDetails($eventID);
		?>
		<h2><?php echo $event->title; ?></h2>
		<p><strong>Date:</strong> <?php echo $event->startTime; ?></p>
		<p><strong>Location:</strong> <?php echo $event->location; ?></p>
		<p><strong>Description:</strong> <?php echo $event->description; ?></p>
	</div>
	<div class="reviews-section">
		<h2>User Reviews</h2>
		<?php
		$event->getFeedback();
		$feedbacks = $event->feedbacks;
		foreach ($feedbacks as $feedback) {
			echo "<div class='review'>";
			echo "<p><strong>" . htmlspecialchars($feedback->user) . ":</strong></p>";
			echo "<p>" . htmlspecialchars($feedback->comment) . "</p>";
			echo "<p><strong>Rating:</strong> " . htmlspecialchars($feedback->rating) . "</p>";
			echo "<p><strong>Date:</strong> " . htmlspecialchars($feedback->timestamp) . "</p>";
			echo "</div>";
		}
		?>
	</div>
</div>
<script src="script.js"></script>
<script src="feedback.js"></script>
</body>
</html>
