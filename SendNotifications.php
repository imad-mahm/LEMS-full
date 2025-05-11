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
include 'classes.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Send Notifications</title>
	<link rel="stylesheet" href="style.css">
	<link rel="stylesheet" href="home.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
	<style>
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f9f9f9;
  margin: 0;
  padding: 0;
}

.content {
  margin-left: 250px; /* leave room for sidebar */
  padding: 40px;
  background-color: #ffffff;
  border-radius: 10px;
  max-width: 800px;
  box-shadow: 0 0 10px rgba(0,0,0,0.05);
}

.sidebar {
  position: fixed;
  top: 80px;
  left: 0;
  width: 220px;
  height: calc(100% - 80px);
  background-color: #ffffff;
  border-right: 1px solid #ddd;
  padding: 20px;
}

.sidebar h2 {
  font-size: 20px;
  margin-bottom: 20px;
  color: #006644;
}

.sidebar ul {
  list-style: none;
  padding: 0;
}

.sidebar ul li {
  margin-bottom: 15px;
}

.sidebar ul li a {
  text-decoration: none;
  color: #4b0082;
  font-weight: 500;
}

.sidebar ul li a:hover {
  color: #006644;
  text-decoration: underline;
}

form label {
  display: block;
  font-weight: bold;
  margin-top: 20px;
  margin-bottom: 5px;
}

form input[type="text"],
form select,
form textarea	{
  width: 100%;
  padding: 10px;
  font-size: 16px;
  border: 1px solid #ccc;
  border-radius: 8px;
  box-sizing: border-box;
}

form textarea {
  resize: vertical;
}

form input[type="submit"] {
  margin-top: 20px;
  padding: 10px 20px;
  background-color: #006644;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
  transition: background-color 0.2s ease-in-out;
}

form input[type="submit"]:hover {
  background-color: #004d33;
}

p {
  margin-top: 20px;
  color: green;
  font-weight: 600;
}
</style>
	
	</head>
<body>
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
	<br>
	<div class="content">
		<form method="POST" action="SendNotifications.php">
			<?php
				$eventManager = new EventManager();
				$eventManager->getAllEvents("future", "approved", $_SESSION['user']['clubs'][0]);
				$events = $eventManager->events;
			?>
			<label for="event_id">Select Event:</label>
			<select name="event_id" id="event_id" required>
				<option value="" disabled selected>Select an event</option>
				<?php
				if (empty($events)) {
					echo "<option disabled>No events found</option>";
				} else {
					foreach ($events as $event) {
						echo "<option value='{$event->eventID}'>{$event->title}</option>";
					}
				}
				?>
			</select>
			<br><br>
			<label for="notification-title">Notification Title:</label><br>
			<input type="text" name="notification-title" id="notification-title" required><br><br>
			<label for="notification">Notification Message:</label><br>
			<textarea name="notification" id="notification" rows="4" cols="50" required></textarea><br><br>
			<input type="submit" name="send_notification" value="Send Notification">
		</form>

		<script>
		document.getElementById('event_id').addEventListener('change', function() {
			const selectedOption = this.options[this.selectedIndex];
			const notificationTitleInput = document.getElementById('notification-title');
			notificationTitleInput.value = `Notification about ${selectedOption.text}`;
		});
		</script>

		<?php
		if (isset($_POST['send_notification'])) {
			$eventId = $_POST['event_id'];
			$notificationMessage = $_POST['notification'];
			$notificationTitle = $_POST['notification-title'];
			//find the club of the event that matches the club of the user
			$event = new Event();
			$event->getDetails($eventId);
			$club = $_SESSION['user']['clubs'][0];
			//get the clubs email
			$clubmailstmt = $conn->prepare("SELECT club_email FROM club WHERE ID = ?");
			$clubmailstmt->bind_param("i", $club);
			$clubmailstmt->execute();
			$result = $clubmailstmt->get_result();
			$club_email = $result->fetch_assoc()['club_email'];
			$clubmailstmt->close();
			//get all users who registered for the event
			$stmt = $conn->prepare("SELECT user.LAU_email FROM user INNER JOIN registration ON user.LAU_email = registration.LAU_email WHERE registration.eventID = ?");
			$stmt->bind_param("i", $eventId);
			$stmt->execute();
			$result = $stmt->get_result();
			$usermails = [];
			while ($row = $result->fetch_assoc()) {
				$usermails[] = $row['LAU_email'];
			}
			$stmt->close();
			$usermails[] = "imad.mahmoud@lau.edu";

			// Send notification to all users who registered for the event
			$subject = 'Notification';
			$message = 'This is a test notification';
			
			$noti = new Notification($usermails	, $notificationTitle, $notificationMessage);			
			$error=$noti->sendNotification();
			if ($error === true) {
				echo "<p>Notification sent successfully!</p>";
			} else {
				echo "<p>Failed to send notification. " . $error . "</p>";
			}
		}
		?>