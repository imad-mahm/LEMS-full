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

$eventId = isset($_GET['event']) ? intval($_GET['event']) : 0;
if ($eventId <= 0) {
    echo "Invalid event.";
    exit();
}

$event = new Event();
$event->getDetails($eventId);

if (!$event) {
    echo "Event not found.";
    exit();
}

// Fetch feedback using FeedbackManager
$feedbackManager = new FeedbackManager();
$reviews = $feedbackManager->getFeedbackFor($eventId);

// Get AI summary if there are reviews
$aiSummary = "";
if (count($reviews) > 0) {
    $aiSummary = $feedbackManager->summarizeReviews($reviews);
}
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
    <style>
        /* Event Reviews Page Styles - Matching Site Theme */
        /* Container */
        .container {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem;
        }

        /* Section Headings */
        .container > h1,
        .reviews-section h2 {
        text-align: center;
        font-size: 2rem;
        color: #047857;
        margin-bottom: 1.5rem;
        }

        /* Event Details Card */
        .event-details {
        background-color: #ffffff;
        border-radius: 0.5rem;
        padding: 2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        }
        .event-details h2 {
        margin-top: 0;
        font-size: 1.75rem;
        color: #065f46;
        }
        .event-details p {
        margin: 0.75rem 0;
        line-height: 1.6;
        color: #1f2937;
        }
        .event-details p strong {
        color: #065f46;
        }

        /* Reviews Section */
        .reviews-section {
        padding-bottom: 2rem;
        }

        /* Individual Review Cards */
        .reviews-section .review {
        background-color: #ffffff;
        border-left: 4px solid #047857;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }
        .reviews-section .review p {
        margin: 0.5rem 0;
        color: #1f2937;
        }
        .reviews-section .review p strong {
        color: #065f46;
        }

        /* Rating Badge */
        .reviews-section .review .rating {
        display: inline-block;
        background-color: #047857;
        color: #ffffff;
        border-radius: 0.375rem;
        padding: 0 0.5rem;
        font-weight: 600;
        margin-left: 0.5rem;
        }

        /* Timestamp */
        .reviews-section .review .timestamp {
        display: block;
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 0.25rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 600px) {
        .container { padding: 1rem; }
        .event-details, .reviews-section .review { padding: 1rem; }
        .container > h1,
        .reviews-section h2 {
            font-size: 1.5rem;
        }
        .event-details h2 {
            font-size: 1.5rem;
        }
        }
        /* AI Summary Section */
        .ai-summary-section {
        background-color: #ecfdf5;
        border-left: 4px solid #047857;
        border-radius: 0.5rem;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }

        .ai-summary-title {
        margin: 0 0 1rem;
        font-size: 1.75rem;
        color: #047857;
        }

        .ai-summary-section p {
        margin: 0;
        line-height: 1.6;
        color: #1f2937;
        }

        /* Responsive */
        @media (max-width: 600px) {
        .ai-summary-section {
            padding: 1rem;
        }
        .ai-summary-title {
            font-size: 1.5rem;
        }
        }


    </style>
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
		$event->getDetails($eventId);
		?>
		<h2><?php echo $event->title; ?></h2>
		<p><strong>Date:</strong> <?php echo $event->startTime; ?></p>
		<p><strong>Location:</strong> <?php echo $event->location; ?></p>
		<p><strong>Description:</strong> <?php echo $event->description; ?></p>
	</div>
    <?php if (count($reviews) > 0): ?>
  <div class="ai-summary-section">
    <h2 class="ai-summary-title">AI Summary of Reviews</h2>
    <p><?php echo nl2br(htmlspecialchars($aiSummary)); ?></p>
  </div>
  <?php endif; ?>
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
