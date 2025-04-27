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

$userEmail = $_SESSION['user']['mail'];

// Update past events to completed
$conn->query("UPDATE event SET STATE = 'completed' WHERE END_TIME < NOW() AND STATE = 'approved'");

// Upcoming Events
$upcomingStmt = $conn->prepare("SELECT e.EVENTID, e.EVENT_NAME, e.IMAGE_URL, e.START_TIME, e.END_TIME
        FROM event e
        JOIN registration r ON e.EVENTID = r.EVENTID
        WHERE r.LAU_EMAIL = ? AND e.STATE = 'approved'
        ORDER BY e.START_TIME ASC");
$upcomingStmt->bind_param("s", $userEmail);
$upcomingStmt->execute();
$upcomingResult = $upcomingStmt->get_result();
$upcomingEvents = $upcomingResult->fetch_all(MYSQLI_ASSOC);
$upcomingStmt->close();

// Past Events
$pastStmt = $conn->prepare("SELECT e.EVENTID, e.EVENT_NAME, e.IMAGE_URL, e.START_TIME, e.END_TIME,
  (SELECT COUNT(*) FROM registration r WHERE r.EVENTID = e.EVENTID AND r.LAU_EMAIL = ?) AS registered
  FROM event e
  WHERE e.STATE = 'completed'
  ORDER BY e.START_TIME DESC");
$pastStmt->bind_param("s", $userEmail);
$pastStmt->execute();
$pastResult = $pastStmt->get_result();
$pastEvents = $pastResult->fetch_all(MYSQLI_ASSOC);
$pastStmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage My Events | LEMS</title>
  <link rel="stylesheet" href="home.css">
  <link rel="stylesheet" href="browse.css">
  <style>
    .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
    .tab { background-color: #f2f2f2; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; transition: background 0.3s; color: #004990; }
    .tab.active { background-color: #28a745; color: white; }
    .my-events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
    .event-card { background: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); overflow: hidden; transition: transform 0.2s; }
    .event-card:hover { transform: translateY(-5px); }
    .event-card img { width: 100%; height: 180px; object-fit: cover; }
    .event-content { padding: 15px; }
    .btn-view { display: inline-block; background-color: #28a745; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; transition: background-color 0.3s; margin-top: 10px; }
    .btn-view:hover { background-color: #1e7e34; }
    .no-events { text-align: center; margin-top: 50px; color: #555; }
    .footer { padding: 20px; background: #f2f2f2; text-align: center; margin-top: 40px; }
  </style>
</head>
<body>

<header class="navbar">
  <a class="logo" href="home.php" style="text-decoration: none;"><img src="logo.png" alt="LEMS Logo"><span>LEMS</span></a>
  <nav class="nav-links">
    <a href="browse.php">Browse Events</a>
    <a href="Recommended.php">Recommended</a>
    <div class="profile-dropdown">
      <img src="https://img.icons8.com/ios-filled/24/ffffff/user.png" alt="User Icon" class="profile-icon" onclick="toggleDropdown()">
      <div id="dropdown-menu" class="dropdown-menu">
        <a href="profile.php" class="dropdown-item profile-link">Profile</a>
        <a href="auth/logout.php" class="dropdown-item logout-link" style="color: red;">Log Out</a>
      </div>
    </div>
  </nav>
</header>

<script>
function toggleDropdown() {
  document.getElementById("dropdown-menu").classList.toggle("show");
}
function switchTab(tab) {
  document.getElementById('upcoming').style.display = tab === 'upcoming' ? 'grid' : 'none';
  document.getElementById('past').style.display = tab === 'past' ? 'grid' : 'none';
  document.getElementById('tab-upcoming').classList.toggle('active', tab === 'upcoming');
  document.getElementById('tab-past').classList.toggle('active', tab === 'past');
}
</script>

<main class="container" style="padding: 40px;">
  <h1 style="font-size: 32px; margin-bottom: 10px;">My Events</h1>
  <p style="color: #666; margin-bottom: 30px;">Manage your event reservations and view your event history.</p>

  <div class="tabs">
    <button class="tab active" id="tab-upcoming" onclick="switchTab('upcoming')">Upcoming Events (<?php echo count($upcomingEvents); ?>)</button>
    <button class="tab" id="tab-past" onclick="switchTab('past')">Past Events (<?php echo count($pastEvents); ?>)</button>
  </div>

  <div id="upcoming" class="my-events-grid">
    <?php if (count($upcomingEvents) > 0): ?>
      <?php foreach ($upcomingEvents as $event): ?>
        <div class="event-card">
          <div class="event-image">
            <img src="<?php echo htmlspecialchars($event['IMAGE_URL']); ?>" alt="Event Image">
          </div>
          <div class="event-content">
            <h3><?php echo htmlspecialchars($event['EVENT_NAME']); ?></h3>
            <p><strong>Date:</strong> <?php echo date('Y-m-d', strtotime($event['START_TIME'])); ?></p>
            <p><strong>Time:</strong> <?php echo date('H:i', strtotime($event['START_TIME'])); ?></p>
            <a href="event.php?event=<?php echo $event['EVENTID']; ?>" class="btn-view">View Event</a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="no-events">
        <h2>No Upcoming Events</h2>
        <p>Browse events and register to upcoming ones!</p>
        <a href="browse.php" class="btn-view">Browse Events</a>
      </div>
    <?php endif; ?>
  </div>

  <div id="past" class="my-events-grid" style="display: none;">
    <?php if (count($pastEvents) > 0): ?>
      <?php foreach ($pastEvents as $event): ?>
        <div class="event-card">
          <div class="event-image">
            <img src="<?php echo htmlspecialchars($event['IMAGE_URL']); ?>" alt="Event Image">
          </div>
          <div class="event-content">
            <h3><?php echo htmlspecialchars($event['EVENT_NAME']); ?></h3>
            <p><strong>Date:</strong> <?php echo date('Y-m-d', strtotime($event['START_TIME'])); ?></p>
            <p><strong>Time:</strong> <?php echo date('H:i', strtotime($event['START_TIME'])); ?></p>
            <a href="event_details.php?event=<?php echo $event['EVENTID']; ?>" class="btn-view">View Details & Reviews</a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="no-events">
        <h2>No Past Events</h2>
      </div>
    <?php endif; ?>
  </div>

</main>

<footer class="footer">
  <p>© 2025 LEMS. All rights reserved.</p>
</footer>

</body>
</html>
