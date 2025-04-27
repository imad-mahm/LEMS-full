<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: index.html");
  exit();
}
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "lems"; 

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchQuery = '';
$params = [];

if (!empty($search)) {
  $searchQuery = " AND (E.EVENT_NAME LIKE ? OR C.CLUB_NAME LIKE ?)";
  $searchParam = '%' . $search . '%';
  $params[] = $searchParam;
  $params[] = $searchParam;
}

$sql = "SELECT E.*, GROUP_CONCAT(C.CLUB_NAME SEPARATOR ', ') AS CLUB_NAMES 
    FROM `event` AS E
    LEFT JOIN `event_club` AS EC ON E.EVENTID = EC.EVENTID
    LEFT JOIN `club` AS C ON EC.CLUBID = C.ID
    WHERE E.START_TIME > CURRENT_TIMESTAMP() AND STATE = 'approved' $searchQuery
    GROUP BY E.EVENTID
    ORDER BY E.START_TIME ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
  $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LEMS Event Manager</title>
  <link rel="stylesheet" href="browse.css" />
  <script src="browse.js"></script>
  </head>
  <body>
  <header class="navbar">
    <a class="logo" href="home.html" style="text-decoration: none">
    <img src="logo.png" alt="LEMS Logo" />
    <span>LEMS</span>
    </a>
    <nav class="nav-links">
    <a href="browse.html">Browse Events</a>
    <a href="Recommended.html">Recommended</a>
    <div class="profile-dropdown">
      <img
      src="https://img.icons8.com/ios-filled/24/ffffff/user.png"
      alt="User Icon"
      class="profile-icon"
      onclick="toggleDropdown()"
      />
      <div id="dropdown-menu" class="dropdown-menu">
      <a href="profile.html" class="dropdown-item profile-link">Profile</a>
      <a href="index.html" class="dropdown-item logout-link" style="color: red">Log Out</a>
      </div>
    </div>
    </nav>
  </header>

  <main>
    <section class="events-section">
    <div class="section-header">
      <h2><span class="calendar-icon">📅</span> Upcoming Events</h2>
      <div class="search-tools">
      <form method="GET" action="browse.php" style="width: 100%; display: flex; align-items: center;">
        <input
        type="text"
        name="search"
        placeholder="Search events by title or club..."
        value="<?php echo htmlspecialchars($search); ?>"
        />
        <button type="submit" style="display:none;">🔍</button>
      </form>
      <button class="filter-btn" onclick="toggleFilterPopup()">⚙️ Filter</button>
      <div id="filter-popup" class="filter-popup" style="display: none;">
        <form method="GET" action="browse.php" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
          <h3>Filter Events</h3>
          <fieldset>
        <legend>Filter by Time</legend>
        <label>
          <input type="radio" name="filter_time" value="Today" />
          Today
        </label>
        <label>
          <input type="radio" name="filter_time" value="This Week" />
          This Week
        </label>
        <label>
          <input type="radio" name="filter_time" value="This Month" />
          This Month
        </label>
          </fieldset>
          <fieldset>
        <legend>Filter by Tags</legend>
        <label>
          <input type="checkbox" name="filter_tags[]" value="AI" />
          AI
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Innovation" />
          Innovation
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Technology" />
          Technology
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Environment" />
          Environment
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Green Energy" />
          Green Energy
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Sustainability" />
          Sustainability
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Business" />
          Business
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Entrepreneurship" />
          Entrepreneurship
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Pitch" />
          Pitch
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Concert" />
          Concert
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Festival" />
          Festival
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Music" />
          Music
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Art" />
          Art
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Culture" />
          Culture
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Exhibition" />
          Exhibition
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Career" />
          Career
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Job" />
          Job
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Recruitment" />
          Recruitment
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Bootcamp" />
          Bootcamp
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Data Science" />
          Data Science
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Machine Learning" />
          Machine Learning
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Fitness" />
          Fitness
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Health" />
          Health
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Wellness" />
          Wellness
        </label>
        <label>
          <input type="checkbox" name="filter_tags[]" value="Coding" />
          Coding
        </label>
          </fieldset>
          <button type="submit">Apply Filters</button>
          <button type="button" onclick="toggleFilterPopup()">Close</button>
        </form>
      </div>
      <style>
        #filter-popup {
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          z-index: 1000;
          background: rgba(255, 255, 255, 0.9);
          width: 90%;
          max-width: 600px;
          max-height: 80%;
          overflow-y: auto;
          border-radius: 8px;
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
          padding: 20px;
        }
        #filter-popup form {
          z-index: 1001;
        }
        #filter-popup fieldset {
          margin-bottom: 15px;
        }
        #filter-popup legend {
          font-weight: bold;
        }
        #filter-popup label {
          display: block;
          margin-bottom: 5px;
        }
      </style>
      <script>
        function toggleFilterPopup() {
          const popup = document.getElementById('filter-popup');
          popup.style.display = popup.style.display === 'none' ? 'block' : 'none';
        }
      </script>
      </div>
    </div>
    <div class="event-grid">
    <?php if (count($events) > 0): ?>
      <?php foreach ($events as $event): ?>
      <div
      class="event-card"
      onclick="window.location.href='event.html?event=<?php echo $event['EVENTID']; ?>'"
      >
      <div class="event-image">
        <?php if (!empty($event['CLUB_NAMES'])): ?>
        <span class="category-tag"><?php echo htmlspecialchars($event['CLUB_NAMES']); ?></span>
        <?php endif; ?>
        <img src="<?php echo htmlspecialchars($event['IMAGE_URL']); ?>" alt="<?php echo htmlspecialchars($event['EVENTID']); ?> image" />
      </div>
      <div class="event-content">
        <h3><?php echo htmlspecialchars($event['EVENT_NAME']); ?></h3>
        <?php 
        $date = date('Y-m-d', strtotime($event['START_TIME']));
        $time = date('H:i:s', strtotime($event['START_TIME']));
        ?>
        <div class="event-detail">
        <span class="icon">📅</span>
        <span><?php echo $date; ?></span>
        </div>
        <div class="event-detail">
        <span class="icon">⏰</span>
        <span><?php echo ($time ?? "TBA"); ?></span>
        </div>
        <div class="event-detail">
        <span class="icon">📍</span>
        <?php
        $locationId = $event['LOCATIONID'];
        $stmtLocation = $conn->prepare("SELECT * FROM `location` WHERE LOCATIONID = ?");
        $stmtLocation->bind_param("i", $locationId);
        $stmtLocation->execute();
        $resultLocation = $stmtLocation->get_result();
        $location = $resultLocation->fetch_assoc();
        $stmtLocation->close();
        ?>
        <span><?php echo htmlspecialchars($location['CAMPUS']) .", ". htmlspecialchars($location['BUILDING']) .", ". htmlspecialchars($location['ROOM']); ?></span>
        </div>
        <div class="progress-bar">
        <?php
        $filled = $event['FILLED_SPOTS'] ?? 0;
        $total = $event['CAPACITY'] ?? 1;
        $percentFilled = ($filled / $total) * 100;
        ?>
        <div class="progress" style="width: <?php echo $percentFilled; ?>%;"></div>
        </div>
        <p class="spots-filled"><?php echo $filled; ?> / <?php echo $total; ?> spots filled</p>
        <button class="btn-reserve">Reserve In Person</button>
      </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No events found</p>
      <span>Try adjusting your search criteria</span>
    <?php endif; ?>
    </div>
    </section>
  </main>
  </body>
</html>

<?php
$stmt->close();
$conn->close();
?>
