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
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>View Club Profile</title>
	<link rel="stylesheet" href="style.css">
	<link rel="stylesheet" href="home.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
	<link rel="stylesheet" href="feedback.css">
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
<main class="container">
	<h1>View Club Profile</h1>
	<?php
	$clubID = $_SESSION['user']['clubs'][0];
	$stmt = $conn->prepare("SELECT * FROM club WHERE id = ?");
	$stmt->bind_param("i", $clubID);
	$stmt->execute();
	$result = $stmt->get_result();
	$club = $result->fetch_assoc();
	?>
	<div class="club-profile">
		<h2><?php echo $club['CLUB_NAME']; ?></h2>
		<p><strong>Description:</strong> <?php echo $club['CLUB_DESCRIPTION']; ?></p>
		<p><strong>Contact:</strong> <?php echo $club['CLUB_EMAIL']; ?></p>
		<p><strong>Committee:</strong></p>
		<ul>
			<?php
			$stmtCommittee = $conn->prepare("SELECT * FROM committee WHERE clubid = ?");
			$stmtCommittee->bind_param("i", $clubID);
			$stmtCommittee->execute();
			$resultCommittee = $stmtCommittee->get_result()->fetch_assoc();
			echo "<li>" . htmlspecialchars($resultCommittee['PRESIDENT']) . " (President)</li>";
			echo "<li>" . htmlspecialchars($resultCommittee['TREASURER']) . " (Treasurer)</li>";
			echo "<li>" . htmlspecialchars($resultCommittee['SECRETARY']) . " (Secretary)</li>";
			?>
		</ul>
		<p><strong>Members:</strong></p>
		<ul>
			<?php
			$stmtMembers = $conn->prepare("SELECT * FROM club_user WHERE clubid = ?");
			$stmtMembers->bind_param("i", $clubID);
			$stmtMembers->execute();
			$resultMembers = $stmtMembers->get_result();
			while ($member = $resultMembers->fetch_assoc()) {
				echo "<li>" . htmlspecialchars($member['LAU_EMAIL']) . "</li>";
			}
			?>
		</ul>
	</div>
	<div class="club-actions">
		<a href="edit_club.php?clubID=<?php echo $clubID; ?>" class="btn">Edit Club</a>
	</div>
</main>
<script src="script.js"></script>
</body>