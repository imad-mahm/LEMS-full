<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit();
}
if($_SESSION['user']['user_role'] != 'admin'){
    header("Location: home.php");
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

$sql = "SELECT E.*, GROUP_CONCAT(C.CLUB_NAME SEPARATOR ', ') AS CLUB_NAMES 
    FROM `event` AS E
    LEFT JOIN `event_club` AS EC ON E.EVENTID = EC.EVENTID
    LEFT JOIN `club` AS C ON EC.CLUBID = C.ID
    WHERE STATE = 'pending'
    GROUP BY E.EVENTID
    ORDER BY E.START_TIME ASC";

$stmt = $conn->prepare($sql);

$stmt->execute();
$result = $stmt->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Create Event | LEMS</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha384-k6RqeWeci5ZR/Lv4MR0sA0FfDOM8d7j3z5l5e5c5e5e5e5e5e5e5e5e5e5e5e5" crossorigin="anonymous" />
        <link rel="stylesheet" href="browse.css" />
    </head>
    <body>
    <script>
  document.addEventListener('DOMContentLoaded', function () {
    function toggleDropdown() {
      const menu = document.getElementById("dropdown-menu");
      menu.style.display = menu.style.display === "block" ? "none" : "block";
    }

    window.onclick = function (e) {
      if (!e.target.matches(".profile-icon")) {
        const menu = document.getElementById("dropdown-menu");
        if (menu && menu.style.display === "block") {
          menu.style.display = "none";
        }
      }
    };

    // Also make toggleDropdown available globally
    window.toggleDropdown = toggleDropdown;
  });
</script>

    <header class="navbar">
      <a class="logo" href="home.html" style="text-decoration: none">
        <img src="logo.png" alt="LEMS Logo" />
        <span>LEMS</span>
      </a>
      <nav class="nav-links">
        <a href="browse.html">Browse Events</a>
        <a href="Recommended.html">Recommended</a>
        <?php
         //look for user in database
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
            echo '<a href="CreateEvent.php">Create Event</a>';
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
            <a href="profile.html" class="dropdown-item profile-link"
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
    <main>
    <div class="event-grid">
    <?php if (count($events) > 0): ?>
      <?php foreach ($events as $event): ?>
      <div
      class="event-card"
      >
      <div class="event-image">
        <?php if (!empty($event['CLUB_NAMES'])): ?>
        <span class="category-tag"><?php echo htmlspecialchars($event['CLUB_NAMES']); ?></span>
        <?php endif; ?>
        <img src="<?php echo htmlspecialchars($event['IMAGE_URL']); ?>" alt="<?php echo htmlspecialchars($event['EVENTID']); ?> image" />
      </div>
      <div class="event-content">
        <h3><?php echo htmlspecialchars($event['EVENT_NAME']); ?></h3>
        <?php 
        $date = date('Y-m-d', strtotime($event['START_TIME']));
        $time = date('H:i:s', strtotime($event['START_TIME']));
        ?>
        <div class="event-detail">
        <span class="icon">📅</span>
        <span><?php echo $date; ?></span>
        </div>
        <div class="event-detail">
        <span class="icon">⏰</span>
        <span><?php echo ($time ?? "TBA"); ?></span>
        </div>
        <div class="event-detail">
        <span class="icon">📍</span>
        <?php
        $locationId = $event['LOCATIONID'];
        $stmtLocation = $conn->prepare("SELECT * FROM `location` WHERE LOCATIONID = ?");
        $stmtLocation->bind_param("i", $locationId);
        $stmtLocation->execute();
        $resultLocation = $stmtLocation->get_result();
        $location = $resultLocation->fetch_assoc();
        $stmtLocation->close();
        ?>
        <span><?php echo htmlspecialchars($location['CAMPUS']) .", ". htmlspecialchars($location['BUILDING']) .", ". htmlspecialchars($location['ROOM']); ?></span>
        </div>
        <div class="progress-bar">
        <?php
        $filled = $event['FILLED_SPOTS'] ?? 0;
        $total = $event['CAPACITY'] ?? 1;
        $percentFilled = ($filled / $total) * 100;
        ?>
        <div class="progress" style="width: <?php echo $percentFilled; ?>%;"></div>
        </div>
        <p class="spots-filled"><?php echo $filled; ?> / <?php echo $total; ?> spots filled</p>
        <form method="POST" action="UpdateEventState.php">
    <input type="hidden" name="event_id" value="<?php echo $event['EVENTID'] ?>"> <!-- pass the Event ID -->
    <input type="hidden" name="new_state" value="approved"> <!-- tell PHP what new state you want -->
    <button type="submit" class="btn-reserve" style="margin-top: 5px;">
        Approve
    </button>
</form>
        <form method="POST" action="UpdateEventState.php">
    <input type="hidden" name="event_id" value="<?php echo $event['EVENTID'] ?>"> <!-- pass the Event ID -->
    <input type="hidden" name="new_state" value="rejected"> <!-- tell PHP what new state you want -->
    <button type="submit" class="btn-reserve" style="margin-top: 5px; background-color: red;">
        Reject
    </button>
</form>
      </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No events to manage</p>
    <?php endif; ?>
        </main>