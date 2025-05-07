<?php
// AdminDashboard.php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit();
}
if ($_SESSION['user']['role'] != 'admin') {
    header("Location: home.php");
    exit();
}

include "db_connection.php";
require_once "classes.php";

$eventManager = new EventManager();
$eventManager->getAllEvents("all", "all");
$events = $eventManager->events;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | LEMS</title>
<link rel="stylesheet" href="browse.css">
<link rel="stylesheet" href="home.css">
<style>
.btn-delete {
  background-color: #c82333;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
  font-size: 14px;
  transition: background-color 0.3s ease, transform 0.2s ease;
}
.btn-delete:hover {
  background-color: #a71d2a;
  transform: scale(1.05);
}
</style>
</head>
<body>

<header class="navbar">
  <a class="logo" href="home.php" style="text-decoration: none;"><img src="logo.png" alt="LEMS Logo"><span>LEMS</span></a>
  <nav class="nav-links">
    <a href="browse.php">Browse Events</a>
    <a href="Recommended.php">Recommended</a>
    <a href="organizer_dashboard.php">Organizer Dashboard</a>
    <a href="AdminDashboard.php">Admin Dashboard</a>
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
</script>

<main>
  <div class="event-grid">
    <?php if (count($events) > 0): ?>
      <?php foreach ($events as $event): ?>
        <div class="event-card">
          <div class="event-image">
            <?php if (!empty($event->createdBy)): ?>
              <?php foreach ($event->createdBy as $clubId): ?>
                <?php
                //get club names
                $CLUBstmt = $conn->prepare("SELECT CLUB_NAME FROM `club` WHERE ID = ?");
                $CLUBstmt->bind_param("i", $clubId);
                $CLUBstmt->execute();
                $result = $CLUBstmt->get_result();
                $club = $result->fetch_assoc();
                ?>
                <span class="category-tag"><?php echo htmlspecialchars($club['CLUB_NAME']); ?></span>
              <?php endforeach; ?>
            <?php endif; ?>
            <img src="<?php echo htmlspecialchars($event->imageURL); ?>" alt="Event Image">
          </div>
          <div class="event-content">
            <h3><?php echo htmlspecialchars($event->title); ?></h3>
            <div class="event-detail"><span class="icon">📅</span><span><?php echo date('Y-m-d', strtotime($event->startTime)); ?></span></div>
            <div class="event-detail"><span class="icon">⏰</span><span><?php echo date('H:i:s', strtotime($event->startTime)); ?></span></div>
            <div class="event-detail"><span class="icon">📍</span>
              <?php
              $locationId = $event->location;
              $stmtLocation = $conn->prepare("SELECT * FROM `location` WHERE LOCATIONID = ?");
              $stmtLocation->bind_param("i", $locationId);
              $stmtLocation->execute();
              $resultLocation = $stmtLocation->get_result();
              $location = $resultLocation->fetch_assoc();
              $stmtLocation->close();
              ?>
              <span><?php echo htmlspecialchars($location['CAMPUS']) . ", " . htmlspecialchars($location['BUILDING']) . ", " . htmlspecialchars($location['ROOM']); ?></span>
            </div>
            <div class="progress-bar">
              <?php
              $filled = $event->filledSeats;
              $total = $event->capacity ?? 1;
              $percentFilled = ($filled / $total) * 100;
              ?>
              <div class="progress" style="width: <?php echo $percentFilled; ?>%;"></div>
            </div>
            <p class="spots-filled"><?php echo $filled; ?> / <?php echo $total; ?> spots filled</p>

            <?php if ($event->state == 'pending'): ?>
              <form method="POST" action="UpdateEventState.php" style="display:inline;">
                <input type="hidden" name="event_id" value="<?php echo $event->eventID ?>">
                <input type="hidden" name="new_state" value="approved">
                <button type="submit" class="btn-reserve" style="margin-top: 5px;">Approve</button>
              </form>
              <form method="POST" action="UpdateEventState.php" style="display:inline;">
                <input type="hidden" name="event_id" value="<?php echo $event->eventID; ?>">
                <input type="hidden" name="new_state" value="rejected">
                <button type="submit" class="btn-reserve" style="margin-top: 5px; background-color: red;">Reject</button>
              </form>
            <?php elseif ($event->state == 'approved' || $event->state == 'completed' || true): ?>
              <form method="POST" action="DeleteEvent.php" onsubmit="return confirm('Are you sure you want to permanently delete this event?');" style="display:inline;">
                <input type="hidden" name="event_id" value="<?php echo $event->eventID; ?>">
                <button type="submit" class="btn-delete" style="margin-top: 5px;">🗑️ Delete</button>
              </form>
            <?php endif; ?>

          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No events to manage.</p>
    <?php endif; ?>
  </div>
</main>

</body>
</html>

<?php
$CLUBstmt->close();
$conn->close();
?>