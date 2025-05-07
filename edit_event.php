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

$eventID = $_GET['event'] ?? null;
if(!$eventID) {
    die("No event selected.");
}

// Check if the event exists and is in the past
$event = new Event();
$event->getDetails($eventID);
if (!$event->title) {
    die("Event not found.");
}

if(isset($_POST['event_name'])) {
        if(trim($_POST['event_name']) != trim($event->title) || 
           trim($_POST['event_description']) != trim($event->description) || 
           date('Y-m-d H:i:s', strtotime($_POST['start_time'])) != $event->startTime || 
           date('Y-m-d H:i:s', strtotime($_POST['end_time'])) != $event->endTime || 
           (int)$_POST['location'] != (int)$event->location || 
           (int)$_POST['capacity'] != (int)$event->capacity) {
        $event->title = $_POST['event_name'];
        $event->description = $_POST['event_description'];
        $event->startTime = $_POST['start_time'];
        $event->endTime = $_POST['end_time'];
        $event->location = $_POST['location'];
        $event->capacity = $_POST['capacity'];
        
        // Update event details in the database
        $event->updateEvent();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Event</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="script.js"></script>
    <style>
      body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f0f2f5;
        margin: 0;
        padding: 0;
      }

      .event-details-container {
        max-width: 900px;
        background: #ffffff;
        margin: 40px auto;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      }

      .event-details h1 {
        font-size: 36px;
        margin-bottom: 30px;
        color: #2c3e50;
        text-align: center;
      }

      .event-details-container img {
        width: 100%;
        max-height: 350px;
        object-fit: cover;
        border-radius: 12px;
        margin-bottom: 30px;
      }

      .form-group {
        margin-bottom: 20px;
      }

      .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #34495e;
      }

      .form-group input,
      .form-group textarea,
      .form-group select {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #dcdfe3;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s ease;
        background-color: #fafafa;
      }

      .form-group input:focus,
      .form-group textarea:focus,
      .form-group select:focus {
        border-color: #28a745;
        outline: none;
        background-color: #ffffff;
      }

      .form-group textarea {
        resize: vertical;
        height: 120px;
      }

      .btn {
        padding: 14px 28px;
        font-size: 18px;
        font-weight: bold;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-block;
      }

      .btn.submit-changes {
        background-color: #007bff;
        color: #fff;
      }

      .btn.submit-changes:hover {
        background-color: #0056b3;
      }

      .btn-back {
        display: inline-block;
        background-color: #6c757d;
        color: white;
        padding: 12px 22px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: bold;
        font-size: 16px;
        transition: background-color 0.3s ease;
        margin: 20px 0;
      }

      .btn-back:hover {
        background-color: #5a6268;
      }

      @media (max-width: 600px) {
        .event-details-container {
          padding: 20px;
        }

        .event-details h1 {
          font-size: 28px;
        }

        .btn, .btn-back {
          width: 100%;
          text-align: center;
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
  <div style="max-width: 900px; margin: 30px auto 0; text-align: left;">
    <a href="browse.php" class="btn-back">← Back to Browse Events</a>
  </div>


    <main>
      <div class="event-details-container">
        <div class="event-details">
          <form method="POST" action="cancel_event_logic.php?event=<?php echo $eventID; ?>">
            <h1>Edit Event</h1>
            <img src="<?php echo htmlspecialchars($event->imageURL); ?>" alt="Event Image">

            <div class="event-info">
              <div class="form-group">
                <label for="event-name">📝 Event Name:</label>
                <input type="text" id="event-name" name="event_name" value="<?php echo htmlspecialchars($event->title); ?>" required>
              </div>

              <div class="form-group">
                <label for="event-description">📝 Description:</label>
                <textarea id="event-description" name="event_description" required><?php echo htmlspecialchars($event->description); ?></textarea>
              </div>

              <div class="form-group">
                <label for="start-time">🗓️ Start Time:</label>
                <input type="datetime-local" id="start-time" name="start_time" value="<?php echo date('Y-m-d\TH:i', strtotime($event->startTime)); ?>" required>
              </div>

              <div class="form-group">
                <label for="end-time">⏰ End Time:</label>
                <input type="datetime-local" id="end-time" name="end_time" value="<?php echo date('Y-m-d\TH:i', strtotime($event->endTime)); ?>" required>
              </div>

              <div class="form-group">
                <label for="location">📍 Location:</label>
              <select id="location" name="location" required>
                  <?php
                    // Fetch all locations from the database
                    $stmtLocations = $conn->prepare("SELECT * FROM location");
                    $stmtLocations->execute();
                    $resultLocations = $stmtLocations->get_result();

                    while ($row = $resultLocations->fetch_assoc()):
                      $locationOption = htmlspecialchars($row['CAMPUS'] . ' - ' . $row['BUILDING'] . ' - ' . $row['ROOM']);
                      $selected = ($row['LOCATIONID'] == $event->location) ? 'selected' : '';
                  ?>
                    <option value="<?php echo htmlspecialchars($row['LOCATIONID']); ?>" <?php echo $selected; ?>>
                      <?php echo $locationOption; ?>
                    </option>
                  <?php endwhile; ?>
                  <?php $stmtLocations->close(); ?>
                </select>
              </div>

              <div class="form-group">
                <label for="capacity">👥 Capacity:</label>
                <input type="number" id="capacity" name="capacity" value="<?php echo htmlspecialchars($event->capacity); ?>" required>
              </div>
            </div>

            <div style="text-align:center;">
              <button type="submit" class="btn submit-changes">Submit Changes</button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </body>
</html>