<?php
session_start();
if (isset($_SESSION['user'])) {
    if($_SESSION['user']['user_role'] != 'admin' && $_SESSION['user']['user_role'] != 'organizer'){
        header("Location: home.php");
        exit();
    }
}
else if(!isset($_SESSION['user'])) {
    header("Location: index.html");
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
// Create a dashboard for the organizer to create events, view past created events, and view feedback for the events they created, and manage their club and send notifications to the members of their club.
// The dashboard should have a sidebar with the following options:
// 1. Create Event
// 2. View Past Events
// 3. View Feedback
// 4. Manage Club
// 5. Send Notifications
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Dashboard</title>
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

    <div class="main-content">
        <h1>Welcome, <?php echo $_SESSION['user']['displayName']; ?></h1>
        <p>Select an option from the sidebar to get started.</p>
    </div>

    <script src="script.js"></script>
</body>
</html>