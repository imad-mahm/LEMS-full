<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';
require_once 'classes.php';

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
  <title>Event Details | LEMS</title>
  <link rel="stylesheet" href="home.css">
  <link rel="stylesheet" href="browse.css">
  <style>
.container { padding: 40px; max-width: 900px; margin: auto; }
    .details-section, .reviews-section, .submit-review-section, .ai-summary-section { margin-bottom: 40px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .section-title { font-size: 28px; color: #28a745; margin-bottom: 20px; }
    .star { color: gold; }
    textarea { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; margin-top: 10px; }
    .btn-submit, .btn-back { background: #28a745; color: #fff; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; margin-top: 10px; cursor: pointer; display: inline-block; text-align: center; text-decoration: none; }
    .btn-submit:hover, .btn-back:hover { background: #1e7e34; }
    .ai-summary-section { background: #f8f9fa; border-left: 4px solid #28a745; }
    .ai-summary-title { color: #28a745; font-size: 20px; margin-bottom: 10px; }
  </style>
</head>
<body>

<header class="navbar">
  <a class="logo" href="home.php" style="text-decoration: none;"><img src="logo.png" alt="LEMS Logo"><span>LEMS</span></a>
  <nav class="nav-links">
    <a href="browse.php">Browse Events</a>
    <a href="Recommended.php">Recommended</a>
  </nav>
</header>

<main class="container">
  <a href="manage.php" class="btn-back" style="margin-bottom:5px">⬅ Go Back</a>

  <div class="details-section">
    <h1 class="section-title">Event Details</h1>
    <p><strong>Name:</strong> <?php echo htmlspecialchars($event->title); ?></p>
    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($event->description)); ?></p>
    <p><strong>Duration:</strong> <?php echo htmlspecialchars($event->duration); ?> minutes</p>
    <p><strong>Date:</strong> <?php echo date('Y-m-d', strtotime($event->startTime)); ?></p>
    <p><strong>Time:</strong> <?php echo date('H:i', strtotime($event->startTime)); ?> to <?php echo date('H:i', strtotime($event->endTime)); ?></p>
 
    <?php
    // Fetch location details from the database
      $locationId = intval($event->location);
      $locationQuery = $conn->prepare("SELECT CAMPUS, BUILDING, ROOM FROM location WHERE locationid = ?");
      $locationQuery->bind_param("i", $locationId);
      $locationQuery->execute();
      $locationResult = $locationQuery->get_result();

      if ($locationResult->num_rows > 0) {
        $locationData = $locationResult->fetch_assoc();
      }

      $locationQuery->close();
    ?>
    <p><strong>Location:</strong> <?php echo htmlspecialchars($locationData['CAMPUS'] . ", " . $locationData['BUILDING'] . ", " . $locationData['ROOM']); ?></p>
    <p><strong>Capacity:</strong> <?php echo htmlspecialchars($event->capacity) ?> seats</p>
  </div>

  <?php if (count($reviews) > 0): ?>
  <div class="ai-summary-section">
    <h2 class="ai-summary-title">AI Summary of Reviews</h2>
    <p><?php echo nl2br(htmlspecialchars($aiSummary)); ?></p>
  </div>
  <?php endif; ?>

  <div class="reviews-section">
    <h1 class="section-title">Reviews</h1>
    <?php if (count($reviews) > 0): ?>
      <?php foreach ($reviews as $review): ?>
        <div style="margin-bottom: 20px;">
        <p><strong>User:</strong> <?php echo htmlspecialchars($review->user); ?></p>
          <p><strong>Rating:</strong> 
          <?php 
          $rating = floatval($review->rating);
          $fullStars = floor($rating);
          $emptyStars = 5 - $fullStars;

          echo str_repeat("<span class='star'>&#9733;</span>", $fullStars); // Full stars ★
          echo str_repeat("<span class='star'>&#9734;</span>", $emptyStars); // Empty stars ☆
          ?> 
          (<?php echo htmlspecialchars($review->rating); ?>/5)
</p>

          <p><strong>Comment:</strong> <?php echo nl2br(htmlspecialchars($review->comment)); ?></p>
          <p><strong>Date:</strong> <?php echo htmlspecialchars($review->timestamp); ?></p>          <hr>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No reviews yet.</p>
    <?php endif; ?>
  </div>
</main>

<footer class="footer">
  <p>© 2025 LEMS. All rights reserved.</p>
</footer>

</body>
</html>

<?php 
$conn->close();
?>