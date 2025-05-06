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
$eventsmanager->getAllEvents("future", "approved", $_SESSION['user']['clubs'][0]);
$events = $eventsmanager->events;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Past Events</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<header class="navbar">
      <a class="logo" href="home.php" style="text-decoration: none">
        <img src="logo.png" alt="LEMS Logo" />
        <span>LEMS</span>
      </a>
      <nav class="nav-links">
        <a href="browse.php">Browse Events</a>
        <a href="Recommended.html">Recommended</a>
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

    <h1>Upcoming Events</h1>

<div class="event-grid">
<?php if (count($events) > 0): ?>
  <?php foreach ($events as $event): ?>
    <div class="event-card" onclick="window.location.href='event.php?event=<?php echo $event->eventID; ?>'">
      <div class="event-image">
        <?php if (!empty($event->createdBy)): ?><span class="category-tag"><?php echo htmlspecialchars($event->createdBy); ?></span><?php endif; ?>
        <img src="<?php echo htmlspecialchars($event->imageURL); ?>" alt="<?php echo htmlspecialchars($event->eventID); ?> image">
      </div>
      <div class="event-content">
        <h3><?php echo htmlspecialchars($event->title); ?></h3>
        <?php $date = date('Y-m-d', strtotime($event->startTime)); $time = date('H:i:s', strtotime($event->startTime)); ?>
        <div class="event-detail"><span class="icon">📅</span><span><?php echo $date; ?></span></div>
        <div class="event-detail"><span class="icon">⏰</span><span><?php echo $time; ?></span></div>
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
        <div class="progress-bar">
          <?php
          $filled_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registration WHERE EVENTID = ?");
          $filled_stmt->bind_param("i", $event->eventID);
          $filled_stmt->execute();
          $filled_stmt->bind_result($filled);
          $filled_stmt->fetch();
          $filled_stmt->close();
          $total = $event->capacity ?? 1;
          $percentFilled = ($filled / $total) * 100;
          ?>
          <div class="progress" style="width: <?php echo $percentFilled; ?>%;"></div>
        </div>
        <p class="spots-filled"><?php echo $filled; ?> / <?php echo $total; ?> spots filled</p>
        <!-- edit events button -->
        <a href="edit_event.php?event=<?php echo $event->eventID; ?>" class="btn">Edit Event</a>
        <!-- delete event button -->
        <a href="cancel_event_logic.php?event=<?php echo $event->eventID; ?>" class="btn">Delete Event</a>
        </div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <p>No events found.</p>
<?php endif; ?>
</div>
    <script src="script.js"></script>
</body>
</html>