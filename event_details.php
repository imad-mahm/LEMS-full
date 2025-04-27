<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: index.html");
  exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lems";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$eventId = isset($_GET['event']) ? intval($_GET['event']) : 0;
if ($eventId <= 0) {
  echo "Invalid event.";
  exit();
}

// Fetch event details
$eventStmt = $conn->prepare("SELECT e.*, l.CAMPUS, l.BUILDING, l.ROOM FROM event e JOIN location l ON e.LOCATIONID = l.LOCATIONID WHERE e.EVENTID = ?");
$eventStmt->bind_param("i", $eventId);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();
$event = $eventResult->fetch_assoc();
$eventStmt->close();

if (!$event) {
  echo "Event not found.";
  exit();
}

// Fetch clubs
$clubsResult = $conn->query("SELECT c.CLUB_NAME FROM club c JOIN event_club ec ON c.ID = ec.CLUBID WHERE ec.EVENTID = $eventId");
$clubs = [];
while ($row = $clubsResult->fetch_assoc()) {
  $clubs[] = $row['CLUB_NAME'];
}

// Fetch tags
$tagsResult = $conn->query("SELECT TAG FROM event_tags WHERE EVENTID = $eventId");
$tags = [];
while ($row = $tagsResult->fetch_assoc()) {
  $tags[] = $row['TAG'];
}

// Fetch reviews
$reviewStmt = $conn->prepare("SELECT * FROM feedback WHERE EVENTID = ?");
$reviewStmt->bind_param("i", $eventId);
$reviewStmt->execute();
$reviewsResult = $reviewStmt->get_result();
$reviews = $reviewsResult->fetch_all(MYSQLI_ASSOC);
$reviewStmt->close();

$conn->close();
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
    .details-section, .reviews-section, .submit-review-section { margin-bottom: 40px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .section-title { font-size: 28px; color: #28a745; margin-bottom: 20px; }
    .star { color: gold; }
    textarea { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; margin-top: 10px; }
    .btn-submit, .btn-back { background: #28a745; color: #fff; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; margin-top: 10px; cursor: pointer; display: inline-block; text-align: center; text-decoration: none; }
    .btn-submit:hover, .btn-back:hover { background: #1e7e34; }
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
    <p><strong>Name:</strong> <?php echo htmlspecialchars($event['EVENT_NAME']); ?></p>
    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($event['EVENT_DESCRIPTION'])); ?></p>
    <p><strong>Duration:</strong> <?php echo htmlspecialchars($event['DURATION']); ?> minutes</p>
    <p><strong>Date:</strong> <?php echo date('Y-m-d', strtotime($event['START_TIME'])); ?></p>
    <p><strong>Time:</strong> <?php echo date('H:i', strtotime($event['START_TIME'])); ?> to <?php echo date('H:i', strtotime($event['END_TIME'])); ?></p>
    <p><strong>Location:</strong> <?php echo htmlspecialchars($event['CAMPUS'] . ", " . $event['BUILDING'] . ", " . $event['ROOM']); ?></p>
    <p><strong>Capacity:</strong> <?php echo htmlspecialchars($event['CAPACITY']); ?> attendees</p>
    <p><strong>Organizing Clubs:</strong> <?php echo implode(", ", $clubs); ?></p>
    <p><strong>Tags:</strong> <?php echo implode(", ", $tags); ?></p>
  </div>

  <div class="reviews-section">
    <h1 class="section-title">Reviews</h1>
    <?php if (count($reviews) > 0): ?>
      <?php foreach ($reviews as $review): ?>
        <div style="margin-bottom: 20px;">
          <p><strong>Rating:</strong> <?php echo str_repeat("<span class='star'>&#9733;</span>", intval($review['RATING'])); ?> (<?php echo htmlspecialchars($review['RATING']); ?>/5)</p>
          <p><strong>Comment:</strong> <?php echo nl2br(htmlspecialchars($review['CONTENT'])); ?></p>
          <hr>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No reviews yet.</p>
    <?php endif; ?>
  </div>

  <div class="submit-review-section">
    <h1 class="section-title">Submit Your Review</h1>
    <form method="POST" action="submit_review.php">
      <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
      <label>Rating (0-5):</label><br>
      <input type="number" step="0.5" min="0" max="5" name="rating" required><br><br>
      <label>Comment:</label><br>
      <textarea name="content" rows="4" required></textarea><br>
      <button type="submit" class="btn-submit">Submit Review</button>
    </form>
  </div>
</main>

<footer class="footer">
  <p>© 2025 LEMS. All rights reserved.</p>
</footer>

</body>
</html>
