<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.html");
    exit;
}
if(!str_contains($_SESSION['user']['mail'], "lau.edu") && !str_contains($_SESSION['user']['mail'], "lau.edu.lb")){ 
    session_destroy();
    $error_message = urlencode("You are not authorized to access this page.");
    header("Location: index.html?error=" . $error_message);
    exit;
  } 
  
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LEMS Dashboard</title>
    <link rel="stylesheet" href="home.css" />
  </head>
  <body>
    <script>
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

    <section class="hero">
      <h1>Welcome to LEMS,
        <?php
          echo "Welcome, " . htmlspecialchars($_SESSION['user']['displayName']);
        ?>
      </h1>
      <p>
        LEMS helps university students discover events that match their academic
        interests, powered by AI recommendations based on your transcript.
      </p>
      <div class="hero-buttons">
        <a href="browse.html" class="btn primary">Browse Events</a>
        <a href="Recommended.html" class="btn secondary">Recommended Events</a>
      </div>
    </section>

    <section class="how-it-works">
      <h2>How It Works</h2>
      <div class="steps">
        <div>
          <img src="https://img.icons8.com/ios-filled/50/000000/document.png" />
          <h3>Upload Transcript</h3>
          <p>
            Submit your academic transcript to help our AI understand your
            interests
          </p>
        </div>
        <div>
          <img src="https://img.icons8.com/ios-filled/50/000000/star.png" />
          <h3>Get Recommendations</h3>
          <p>
            Receive personalized event suggestions based on your academic
            profile
          </p>
        </div>
        <div>
          <img src="https://img.icons8.com/ios-filled/50/000000/calendar.png" />
          <h3>Reserve Your Spot</h3>
          <p>
            Easily sign up for events that interest you and manage your schedule
          </p>
        </div>
        <div>
          <img src="https://img.icons8.com/ios-filled/50/000000/feedback.png" />
          <h3>Share Feedback</h3>
          <p>
            Review events you've attended and help others make informed
            decisions
          </p>
        </div>
      </div>
    </section>

    <section class="ai-recommendations">
      <div class="text">
        <h2>AI-Powered Recommendations</h2>
        <p>
          Our platform uses advanced AI to analyze your academic history and
          match you with events that enhance your university experience and
          complement your studies.
        </p>
        <ul>
          <li>✔ Tailored to your academic interests</li>
          <li>✔ Refine suggestions with AI chat</li>
          <li>✔ Discover events you might have missed</li>
        </ul>
      </div>
      <div class="image">
        <img
          src="data:image/jpeg;base64,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"
          alt="AI Recommendations"
        />
      </div>
    </section>

    <footer class="footer">
      <p>© 2025 LEMS. All rights reserved.</p>
    </footer>
  </body>
</html>
