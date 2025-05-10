<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit();
}
if ($_SESSION['user']['role'] != 'organizer' && $_SESSION['user']['role'] != 'admin') {
    header("Location: home.php");
    exit();
}

include 'db_connection.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Create Event | LEMS</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha384-k6RqeWeci5ZR/Lv4MR0sA0FfDOM8d7j3z5l5e5e5e5e5e5e5e5e5e5e5e5e5" crossorigin="anonymous" />
        <link rel="stylesheet" href="CreateEvent.css" />
    </head>
    <body>
    <script>
  document.addEventListener('DOMContentLoaded', function () {
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

    // Also make toggleDropdown available globally
    window.toggleDropdown = toggleDropdown;
  });
</script>

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
        <h1>Create Event</h1>
        <p>Fill in the details below to create a new event</p>
        <form id="event-form" method="POST" action="CreateEventLogic.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="event-name">Event Name:</label>
                <input type="text" id="event-name" name="event_name" required />
            </div>
            <div class="form-group">
                <label for="event-description">Description:</label>
                <textarea id="event-description" name="event_description" required></textarea>
            </div>
            <div class="form-group">
                <label for="event-date">Date:</label>
                <input type="date" id="event-date" name="event_date" required />
            </div>
            <div class="form-group">
                <label for="event-time">Time:</label>
                <input type="time" id="event-time" name="event_time" required />
            </div>
            <div class="form-group">
                <label for="event-duration">Duration (in minutes):</label>
                <input type="number" id="event-duration" name="event_duration" required />
            </div>
            <div class="form-group">
            <label for="location">📍 Location:</label>
            <select id="location" name="location" required>
                <?php
                  // Fetch all locations from the database
                  $stmtLocations = $conn->prepare("SELECT * FROM location");
                  if ($stmtLocations) {
                      $stmtLocations->execute();
                      $resultLocations = $stmtLocations->get_result();
                      while ($row = $resultLocations->fetch_assoc()) {
                          $locationOption = htmlspecialchars($row['CAMPUS'] . ' - ' . $row['BUILDING'] . ' - ' . $row['ROOM']);
                          echo "<option value='" . htmlspecialchars($row['LOCATIONID']) . "'>$locationOption</option>";
                      }
                      $stmtLocations->close();
                  } else {
                      echo "<option disabled>Error fetching locations</option>";
                  }
                ?>
              </select>
            </div>
            <div class="form-group">
                <label for="club">🏢 Club:</label>
                <select id="club" name="club" required>
                    <?php
                    // Fetch all clubs from the database
                    $stmtClubs = $conn->prepare("SELECT * FROM club where ID = ?");
                    $stmtClubs->bind_param("i", $_SESSION['user']['clubs'][0]);
                    if ($stmtClubs) {
                        $stmtClubs->execute();
                        $resultClubs = $stmtClubs->get_result();
                        while ($row = $resultClubs->fetch_assoc()) {
                            $clubOption = htmlspecialchars($row['CLUB_NAME']);
                            echo "<option value='" . htmlspecialchars($row['ID']) . "'>$clubOption</option>";
                        }
                        $stmtClubs->close();
                    } else {
                        echo "<option disabled>Error fetching clubs</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="event-capacity">Capacity:</label>
                <input type="number" id="event-capacity" name="event_capacity" required />
            </div>
            <div class="form-group">
                <label for="event-image">Upload Image:</label>
                <input type="file" id="event-image" name="event_image" accept=".jpg, .jpeg, .png, .gif" required />
            </div>
            <button type="submit">Create Event</button>
        </form>
        </main>
        <footer class="footer">
        <p>&copy; 2025 LEMS. All rights reserved.</p>
        </footer>
        <script>
            function toggleDropdown() {
                var dropdown = document.getElementById("dropdown-menu");
                dropdown.classList.toggle("show");
            }
            window.onclick = function(event) {
                if (!event.target.matches('.profile-icon')) {
                    var dropdowns = document.getElementsByClassName("dropdown-menu");
                    for (var i = 0; i < dropdowns.length; i++) {
                        var openDropdown = dropdowns[i];
                        if (openDropdown.classList.contains('show')) {
                            openDropdown.classList.remove('show');
                        }
                    }
                }
            }
        </script>
    </body>
</html>


