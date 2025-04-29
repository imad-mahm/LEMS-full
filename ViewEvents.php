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

// Fetch past events created by the user
$stmt = $conn->prepare("SELECT e.*
                        FROM Event e
                        JOIN Event_Club ec ON e.eventID = ec.eventID
                        JOIN Committee c ON ec.clubID = c.clubID
                        WHERE (c.president = ?
                        OR c.secretary = ?
                        OR c.treasurer = ?)
                        AND e.start_time < NOW()
                        AND e.state = 'approved'
                        GROUP BY e.eventID
                        ORDER BY e.start_time DESC;
                        "); 
$stmt->bind_param("sss", $_SESSION['user']['mail'],$_SESSION['user']['mail'],$_SESSION['user']['mail']);
$stmt->execute();
$result = $stmt->get_result();
$pastEvents = $result->fetch_all(MYSQLI_ASSOC);
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
         //look fo ruser in database
          $conn = new mysqli('localhost', 'root', '', 'lems');
          if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
          }
          $userEmail = $_SESSION['user']['mail'];
          $sql = "SELECT user_role FROM user WHERE LAU_email = '$userEmail'";
          $result = $conn->query($sql);
          if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $_SESSION['user']['user_role'] = $row['user_role'];
          }
          if ($_SESSION['user']['user_role'] == 'organizer' || $_SESSION['user']['user_role'] == 'admin') {
            echo '<a href="organizer_dashboard.php">Organizer Dashboard</a>';
          }
          if ($_SESSION['user']['user_role'] == 'admin') {
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

    <div class="sidebar">
        <h2>Organizer Dashboard</h2>
        <ul>
            <li><a href="CreateEvent.php">Create Event</a></li>
            <li><a href="ViewEvents.php">View Upcoming Events</a></li>
            <li><a href="ViewFeedback.php">View Feedback</a></li>
            <li><a href="ManageClub.php">Manage Club</a></li>
            <li><a href="SendNotifications.php">Send Notifications</a></li>
        </ul>
    </div>

    <h1>Upcoming Events</h2>

<div class="event-grid">
<?php if (count($pastEvents) > 0): ?>
  <?php foreach ($pastEvents as $event): ?>
    <div class="event-card" onclick="window.location.href='event.php?event=<?php echo $event['EVENTID']; ?>'">
      <div class="event-image">
        <?php if (!empty($event['CLUB_NAMES'])): ?><span class="category-tag"><?php echo htmlspecialchars($event['CLUB_NAMES']); ?></span><?php endif; ?>
        <img src="<?php echo htmlspecialchars($event['IMAGE_URL']); ?>" alt="<?php echo htmlspecialchars($event['EVENTID']); ?> image">
      </div>
      <div class="event-content">
        <h3><?php echo htmlspecialchars($event['EVENT_NAME']); ?></h3>
        <?php $date = date('Y-m-d', strtotime($event['START_TIME'])); $time = date('H:i:s', strtotime($event['START_TIME'])); ?>
        <div class="event-detail"><span class="icon">📅</span><span><?php echo $date; ?></span></div>
        <div class="event-detail"><span class="icon">⏰</span><span><?php echo $time; ?></span></div>
        <div class="event-detail">
          <span class="icon">📍</span>
          <?php
          $locationId = $event['LOCATIONID'];
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
          $filled_stmt->bind_param("i", $event['EVENTID']);
          $filled_stmt->execute();
          $filled_stmt->bind_result($filled);
          $filled_stmt->fetch();
          $filled_stmt->close();
          $total = $event['CAPACITY'] ?? 1;
          $percentFilled = ($filled / $total) * 100;
          ?>
          <div class="progress" style="width: <?php echo $percentFilled; ?>%;"></div>
        </div>
        <p class="spots-filled"><?php echo $filled; ?> / <?php echo $total; ?> spots filled</p>
        <!-- edit events button -->
        <a href="edit_event.php?event=<?php echo $event['EVENTID']; ?>" class="btn">Edit Event</a>
        <!-- delete event button -->
        <a href="cancel_event_logic.php?event=<?php echo $event['EVENTID']; ?>" class="btn">Delete Event</a>
        </div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <p>No events found.</p>
<?php endif; ?>
</div>
    <script src="script.js"></script>
</body>
<?php
$stmt->close();
$conn->close();
?>