<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit();
}
if ($_SESSION['user']['user_role'] != 'admin' && $_SESSION['user']['user_role'] != 'organizer') {
    header("Location: home.php");
    exit();
}

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'lems';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$eventID = $_GET['event'] ?? null;
if(!$eventID) {
    die("No event selected.");
}

// Check if the event exists and is in the past
$stmt = $conn->prepare("SELECT * FROM event WHERE EVENTID = ? AND START_TIME > NOW()");
$stmt->bind_param("i", $eventID);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if(isset($_POST['event_name'])) {
        if(trim($_POST['event_name']) != trim($event['EVENT_NAME']) || 
           trim($_POST['event_description']) != trim($event['EVENT_DESCRIPTION']) || 
           date('Y-m-d H:i:s', strtotime($_POST['start_time'])) != $event['START_TIME'] || 
           date('Y-m-d H:i:s', strtotime($_POST['end_time'])) != $event['END_TIME'] || 
           (int)$_POST['location'] != (int)$event['LOCATIONID'] || 
           (int)$_POST['capacity'] != (int)$event['CAPACITY']) {
        $event_name = $_POST['event_name'];
        $event_description = $_POST['event_description'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $location = $_POST['location'];
        $capacity = $_POST['capacity'];

        // Update event details in the database
        $stmtUpdate = $conn->prepare("UPDATE event SET EVENT_NAME=?, EVENT_DESCRIPTION=?, START_TIME=?, END_TIME=?, LOCATIONID=?, CAPACITY=? WHERE EVENTID=?");
        $stmtUpdate->bind_param("sssssii", $event_name, $event_description, $start_time, $end_time, $location, $capacity, $eventID);
        if($stmtUpdate->execute()) {
            header("Location: event_details.php?event=$eventID");
            exit();
        } else {
            echo "Error updating event: " . $conn->error;
        }
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
    <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f5f7fa;
      margin: 0;
      padding: 0;
    }

    .event-details-container {
      max-width: 900px;
      background: white;
      margin: 40px auto;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    .event-details-container img {
      width: 100%;
      max-height: 400px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 20px;
    }

    .event-details h1 {
      font-size: 32px;
      margin-bottom: 10px;
      color: #333;
    }

    .event-info {
      margin-top: 20px;
    }

    .event-info p {
      font-size: 18px;
      margin: 10px 0;
      color: #555;
    }

    .event-info p span {
      font-weight: bold;
      color: #333;
    }

    .btn-reserve {
      margin-top: 30px;
      padding: 12px 24px;
      font-size: 18px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s ease;
      color: white;
    }

    .btn-reserve.register {
      background-color: #28a745;
    }

    .btn-reserve.register:hover {
      background-color: #1e7e34;
    }

    .btn-reserve.cancel {
      background-color: #dc3545;
    }

    .btn-reserve.cancel:hover {
      background-color: #c82333;
    }

    @media (max-width: 600px) {
      .event-details-container {
        padding: 20px;
      }
      .event-details h1 {
        font-size: 26px;
      }
      .event-info p {
        font-size: 16px;
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
        ?>
        <a href="profile.php">Profile</a>
        <a href="auth/logout.php" style="color: red">Log Out</a>
        </nav>
        <div class="profile-dropdown">
            <img
                src="https://img.icons8.com/ios-filled/24/ffffff/user.png"
                alt="User Icon"
                class="profile-icon"
                onclick="toggleDropdown()"
            />
            <div id="dropdown-menu" class="dropdown-menu">
                <a href="profile.php" class="dropdown-item profile-link">Profile</a>
                <a href="auth/logout.php" class="dropdown-item logout-link" style="color: red">Log Out</a>
            </div>
        </div>
    </header>
<div style="max-width: 900px; margin: 30px auto 0; text-align: left;">
  <a href="browse.php" style="display: inline-block; background-color: #28a745; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 16px; transition: background-color 0.3s;">
    ← Back to Browse Events
  </a>
</div>


<main>
  <div class="event-details-container">
    <div class="event-details">
      <form method="POST" action="cancel_event_logic.php?event=<?php echo $eventID; ?>">
        <h1>Edit Event</h1>
        <img src="<?php echo htmlspecialchars($event['IMAGE_URL']); ?>" alt="Event Image">

        <div class="event-info">
          <div class="form-group">
            <label for="event-name">📝 Event Name:</label>
            <input type="text" id="event-name" name="event_name" value="<?php echo htmlspecialchars($event['EVENT_NAME']); ?>" required>
          </div>

          <div class="form-group">
            <label for="event-description">📝 Description:</label>
            <textarea id="event-description" name="event_description" required><?php echo htmlspecialchars($event['EVENT_DESCRIPTION']); ?></textarea>
          </div>

          <div class="form-group">
            <label for="start-time">🗓️ Start Time:</label>
            <input type="datetime-local" id="start-time" name="start_time" value="<?php echo date('Y-m-d\TH:i', strtotime($event['START_TIME'])); ?>" required>
          </div>

          <div class="form-group">
            <label for="end-time">⏰ End Time:</label>
            <input type="datetime-local" id="end-time" name="end_time" value="<?php echo date('Y-m-d\TH:i', strtotime($event['END_TIME'])); ?>" required>
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
                  $selected = ($row['LOCATIONID'] == $event['LOCATIONID']) ? 'selected' : '';
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
            <input type="number" id="capacity" name="capacity" value="<?php echo htmlspecialchars($event['CAPACITY']); ?>" required>
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