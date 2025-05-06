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
	<div class="sidebar">
		<h2>Send Notification</h2>
		<ul>
			<li><a href="CreateEvent.php">Create Event</a></li>
			<li><a href="ViewEvents.php">View Upcoming Events</a></li>
			<li><a href="ViewFeedback.php">View Feedback</a></li>
			<li><a href="ManageClub.php">Manage Club</a></li>
			<li><a href="SendNotifications.php">Send Notifications</a></li>
		</ul>
	</div>

	<div class="content">
		<form method="POST" action="SendNotifications.php">
			<?php
				$eventManager = new EventManager();
				$eventManager->getAllEvents(/*"past", "approved", $_SESSION['user']['clubs'][0]*/);
				$events = $eventManager->events; 
				echo "<option value=''>Select an event</option>";
				echo "events found: " . count($events) . "<br>";
			?>
			<label for="event_id">Select Event:</label>
			<select name="event_id" id="event_id" required>
				<?php
				if (empty($events)) {
					echo "<option disabled>No events found</option>";
				} else {
					// Loop through the events and create an option for each
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

			// Send notification to all users who registered for the event
			$to = 'imad.mahmoud@lau.edu';
			$subject = 'Notification';
			$message = 'This is a test notification';
			$headers = 'From: imad_mahm@outlook.com' . "\r\n" .
					   'X-Mailer: PHP/' . phpversion();
			
			mail($to, $subject, $message, $headers);
			

			if ($result) {
				echo "<p>Notification sent successfully!</p>";
			} else {
				echo "<p>Failed to send notification.</p>";
			}
		}
		?>