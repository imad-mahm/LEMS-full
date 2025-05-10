<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit();
}
$flag = false;
if ($_SESSION['user']['preferences'][0] !== null) {
    global $flag;
    $flag = true;
}

include 'db_connection.php';
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Recommended | LEMS</title>
    <link rel="stylesheet" href="recommended.css" />
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
            <a href="profile.html" class="dropdown-item profile-link"
              >Profile</a
            >
            <a
              href="index.html"
              class="dropdown-item logout-link"
              style="color: red"
              >Log Out</a
            >
          </div>
        </div>
      </nav>
    </header>
    <main class="container">
      <h1>Recommended Events</h1>
      <p class="subtext">
        Personalized event suggestions based on your academic interests
      </p>
      <div id="upload-card" class="card">
        <h2>Academic Profile</h2>
        <p>
          Upload and manage your transcript for personalized recommendations
        </p>
        <form action="FileUpload.php" method="post" enctype="multipart/form-data">
          <div class="custom-file">
            <label for="pdf_file" class="file-box">
              <img
            src="https://img.icons8.com/ios-filled/24/065f46/file--v1.png"
            alt="File Icon"
            class="file-icon"
              />
              <span id="file-label">Choose a PDF file</span>
            </label>
            <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" />
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-success">
              Upload Transcript
            </button>
          </div>
        </form>
      </div>
      <section id="recommendations" style="display: none">
        <h2 style="padding: 0 2rem">
          Recommended Events Based on Your Transcript
        </h2>
        <div class="event-grid"></div>
      </section>
    </main>
    <footer class="footer">
      <p>© 2025 LEMS. All rights reserved.</p>
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
      function showRecommendations() {
          document.getElementById("upload-card").style.display = "none";
          document.getElementById("recommendations").style.display = "block";
      }
      const transcriptInput = document.getElementById("pdf_file");
      const fileLabel = document.getElementById("file-label");

      transcriptInput.addEventListener("change", () => {
        if (transcriptInput.files.length > 0) {
          fileLabel.textContent = transcriptInput.files[0].name;
        } else {
          fileLabel.textContent = "Choose a PDF file";
        }
      });
      <?php 
      if ($flag == true) {
        echo "showRecommendations();";
      }
      ?>
    </script>
  </body>
</html>
