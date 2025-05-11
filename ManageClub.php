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
	<style>
		/* Container */
		.container {
		max-width: 800px;
		margin: 0 auto;
		padding: 2rem;
		}

		/* Page Title */
		.container > h1 {
		text-align: center;
		font-size: 2rem;
		color: #047857;
		margin-bottom: 1.5rem;
		font-weight: 600;
		}

		/* Club Profile Card */
		article.club-profile {
		background-color: #ffffff;
		border-radius: 1rem;
		padding: 2rem;
		box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
		transition: transform 0.3s ease, box-shadow 0.3s ease;
		margin-bottom: 2rem;
		}
		article.club-profile:hover {
		transform: translateY(-4px);
		box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
		}

		/* Club Name */
		.club-profile-header .club-name {
		margin: 0 0 1rem;
		font-size: 2rem;
		font-weight: 700;
		color: #065f46;
		position: relative;
		}
		.club-profile-header .club-name::after {
		content: "";
		display: block;
		width: 60px;
		height: 4px;
		background-color: #047857;
		border-radius: 2px;
		margin-top: 0.5rem;
		}

		/* Details */
		.club-details p {
		margin: 1rem 0;
		line-height: 1.6;
		color: #1f2937;
		}
		.club-details p strong {
		color: #065f46;
		}
		.club-details a {
		color: #047857;
		text-decoration: none;
		font-weight: 600;
		transition: color 0.3s;
		}
		.club-details a:hover {
		color: #065f46;
		}

		/* Section Titles */
		.section-title {
		font-size: 1.25rem;
		color: #047857;
		margin-bottom: 0.75rem;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		font-weight: 600;
		}

		/* Committee & Member Lists */
		.committee-list,
		.member-list {
		list-style: none;
		padding: 0;
		margin: 0 0 1.5rem;
		display: flex;
		flex-wrap: wrap;
		gap: 0.5rem;
		}

		/* Committee Badges */
		.committee-list li {
		background-color: #ecfdf5;
		color: #047857;
		border-radius: 9999px;
		padding: 0.5rem 1rem;
		font-size: 0.875rem;
		transition: background-color 0.3s ease;
		}
		.committee-list li:hover {
		background-color: #d1fae5;
		}

		/* Member Badges */
		.member-list li {
		background-color: #f0fdf4;
		color: #065f46;
		border-radius: 0.5rem;
		padding: 0.5rem 1rem;
		font-size: 0.875rem;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
		transition: background-color 0.3s ease;
		}
		.member-list li:hover {
		background-color: #dcfce7;
		}

		/* Role Label */
		.role {
		margin-left: 0.5rem;
		font-style: italic;
		color: #4b5563;
		font-size: 0.875rem;
		}

		/* Responsive */
		@media (max-width: 600px) {
		.container {
			padding: 1rem;
		}
		.container > h1 {
			font-size: 1.5rem;
		}
		article.club-profile {
			padding: 1.5rem;
		}
		.club-profile-header .club-name {
			font-size: 1.75rem;
		}
		.section-title {
			font-size: 1.1rem;
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
    $club = $stmt->get_result()->fetch_assoc();

    $stmtCommittee = $conn->prepare("SELECT * FROM committee WHERE clubid = ?");
    $stmtCommittee->bind_param("i", $clubID);
    $stmtCommittee->execute();
    $committee = $stmtCommittee->get_result()->fetch_assoc();

    $stmtMembers = $conn->prepare("SELECT * FROM club_user WHERE clubid = ?");
    $stmtMembers->bind_param("i", $clubID);
    $stmtMembers->execute();
    $members = $stmtMembers->get_result();
  ?>

  <article class="club-profile">
    <header class="club-profile-header">
      <h2 class="club-name"><?php echo htmlspecialchars($club['CLUB_NAME']); ?></h2>
    </header>

    <div class="club-details">
      <p><strong>Description:</strong> <?php echo htmlspecialchars($club['CLUB_DESCRIPTION']); ?></p>
      <p>
        <strong>Contact:</strong>
        <a href="mailto:<?php echo htmlspecialchars($club['CLUB_EMAIL']); ?>">
          <?php echo htmlspecialchars($club['CLUB_EMAIL']); ?>
        </a>
      </p>
    </div>

    <section class="committee-section">
      <h3 class="section-title">Committee</h3>
      <ul class="committee-list">
        <li>
          <span class="member-name"><?php echo htmlspecialchars($committee['PRESIDENT']); ?></span>
          <span class="role">(President)</span>
        </li>
        <li>
          <span class="member-name"><?php echo htmlspecialchars($committee['TREASURER']); ?></span>
          <span class="role">(Treasurer)</span>
        </li>
        <li>
          <span class="member-name"><?php echo htmlspecialchars($committee['SECRETARY']); ?></span>
          <span class="role">(Secretary)</span>
        </li>
      </ul>
    </section>

    <section class="members-section">
      <h3 class="section-title">Members</h3>
      <ul class="member-list">
        <?php while ($member = $members->fetch_assoc()): ?>
          <li><?php echo htmlspecialchars($member['LAU_EMAIL']); ?></li>
        <?php endwhile; ?>
      </ul>
    </section>
  </article>
</main>

<script src="script.js"></script>
</body>