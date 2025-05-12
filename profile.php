<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.html');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Your Profile | LEMS</title>
    <link rel="stylesheet" href="profile.css" />
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
      <h1>Your Profile</h1>
      <p>Manage your account and preferences</p>

      <div class="main-grid">
        <section class="left-panel">
          <div class="card">
            <h2>Account Information</h2>
            <?php
              $first_name = $_SESSION['user']['firstName'];
              $last_name = $_SESSION['user']['lastName'];
              $email = $_SESSION['user']['email'];
              $role = $_SESSION['user']['role'];
            ?>

            <div class="account-header">
              <div class="avatar"><?php echo strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); ?></div>
              <div class="info">
              <p class="name"><?php echo $first_name . ' ' . $last_name; ?></p>
              <p class="email"><?php echo $email; ?></p>
              <span class="role"><?php echo strtoupper($role); ?></span>
              </div>
            </div>

            <div class="form-actions">
              <a href="manage.php" class="btn-outline">Manage Events</a>
              <button
                class="btn-danger"
                type="button"
                onclick="window.location.href = 'index.html'"
              >
                Sign Out
              </button>
            </div>
          </div>

          <?php
          if (isset($_SESSION['user']['preferences'][0]) && $_SESSION['user']['preferences'][0] !== null) {
              echo '<div class="card">';
              echo '<h2>Your Preferences</h2>';
              echo '<ul class="preferences-list">';
              foreach ($_SESSION['user']['preferences'] as $preference) {
                  echo '<li>' . htmlspecialchars($preference) . '</li>';
              }
              echo '</ul>';
              echo '</div>';
          } else {
              echo '<div class="card">';
              echo '<h2>Academic Profile</h2>';
              echo '<p>Upload and manage your transcript for personalized recommendations</p>';
              echo '<div class="custom-file">';
              echo '<label for="transcript" class="file-box">';
              echo '<img src="https://img.icons8.com/ios-filled/24/065f46/file--v1.png" alt="File Icon" class="file-icon" />';
              echo '<span id="file-label">Choose a PDF file</span>';
              echo '</label>';
              echo '<input type="file" id="transcript" accept=".pdf" />';
              echo '</div>';
              echo '<div class="form-actions">';
              echo '<button class="btn-success">Upload Transcript</button>';
              echo '</div>';
              echo '</div>';
          }
          ?>

        </section>

        <aside class="right-panel">
          <div class="card">
            <h3>Notification Settings</h3>
            <p>
              Notification preferences are not available in the demo version.
            </p>
            <button class="btn-outline">Manage Notifications</button>
          </div>
          <div class="card">
            <h3>Privacy Settings</h3>
            <p>Privacy settings are not available in the demo version.</p>
            <button class="btn-outline">Manage Privacy</button>
          </div>
          <div class="card">
            <h3>Account Statistics</h3>
            <p><strong>Academic profile status</strong>: Incomplete</p>
            <p>Total Events: <strong>0</strong></p>
            <p>Reviews: <strong>0</strong></p>
          </div>
        </aside>
      </div>
    </main>

    <footer class="footer">
      <p>Â© 2025 LEMS. All rights reserved.</p>
    </footer>

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
    <script>
      const transcriptInput = document.getElementById("transcript");
      const fileLabel = document.getElementById("file-label");

      transcriptInput.addEventListener("change", () => {
        if (transcriptInput.files.length > 0) {
          fileLabel.textContent = transcriptInput.files[0].name;
        } else {
          fileLabel.textContent = "Choose a PDF file";
        }
      });
    </script>
  </body>
</html>
