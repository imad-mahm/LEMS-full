<?php
session_start();
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] != 'admin' && $_SESSION['user']['role'] != 'organizer') {
        header("Location: home.php");
        exit();
    }
} else {
    header("Location: index.html");
    exit();
}
include 'db_connection.php';
require_once 'classes.php';
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
    <style>
        .dashboard-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: calc(100vh - 80px);
            background-color: #f7fafc;
        }

        .sidebar {
            background: white;
            padding: 2rem;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        .sidebar h2 {
            color: #065f46;
            font-size: 1.5rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e6f4f1;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li {
            margin-bottom: 0.5rem;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #374151;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar a:hover {
            background-color: #e6f4f1;
            color: #065f46;
            transform: translateX(5px);
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            padding: 2rem;
            background: linear-gradient(to bottom, #e6f4f1, #ffffff);
            min-height: 100%;
        }

        .welcome-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .welcome-section h1 {
            color: #065f46;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .welcome-section p {
            color: #374151;
            font-size: 1.1rem;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: #065f46;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: #374151;
            font-size: 1.5rem;
            font-weight: 600;
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

    <div class="main-content">
        <h1>Welcome, <?php echo $_SESSION['user']['firstName'] . " [" .$_SESSION['user']['role'] . "]"; ?></h1>
        <p>Select an option to get started.</p>
    </div>
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
    <script src="script.js"></script>
</body>
</html>