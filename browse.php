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

// Extra Filters
if (!empty($search)) {
  $searchQuery .= " AND (E.EVENT_NAME LIKE ? OR C.CLUB_NAME LIKE ?)";
  $searchParam = '%' . $search . '%';
  $params[] = $searchParam;
  $params[] = $searchParam;
}

if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
  $searchQuery .= " AND E.START_TIME >= ?";
  $params[] = $_GET['from_date'] . " 00:00:00";
}

if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
  $searchQuery .= " AND E.START_TIME <= ?";
  $params[] = $_GET['to_date'] . " 23:59:59";
}

if (isset($_GET['filter_club']) && !empty($_GET['filter_club'])) {
  $searchQuery .= " AND EC.CLUBID = ?";
  $params[] = $_GET['filter_club'];
}

if (!empty($_GET['filter_tags'])) {
  $placeholders = str_repeat('?,', count($_GET['filter_tags']) - 1) . '?';
  $searchQuery .= " AND E.EVENTID IN (SELECT EVENTID FROM event_tags WHERE TAG IN ($placeholders))";
  foreach ($_GET['filter_tags'] as $tag) {
    $params[] = $tag;
  }
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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LEMS Event Manager</title>
<link rel="stylesheet" href="home.css">
<link rel="stylesheet" href="browse.css">
<script src="script.js"></script>
<style>
/* Slide-in Filter Panel */
#filter-popup { position: fixed; top: 0; right: -400px; width: 320px; height: 100%; background: #f9f9f9; box-shadow: -2px 0 8px rgba(0,0,0,0.2); transition: right 0.3s ease; z-index: 1000; overflow-y: auto; padding: 20px; border-top-left-radius: 12px; border-bottom-left-radius: 12px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
#filter-popup.open { right: 0; }
.filter-content { padding: 10px; }
fieldset { border: none; margin-bottom: 20px; }
legend { font-weight: bold; color: #28a745; font-size: 1.2em; margin-bottom: 10px; }
#filter-popup label { display: block; color: #333; font-weight: 500; margin-bottom: 6px; }
#filter-popup select, #filter-popup input[type="date"] { width: 100%; padding: 8px; margin-top: 4px; margin-bottom: 10px; border-radius: 6px; border: 1px solid #ccc; }
#filter-popup button { background-color: #28a745; color: white; border: none; padding: 10px 20px; margin-top: 10px; border-radius: 8px; cursor: pointer; font-weight: bold; transition: background-color 0.3s; }
#filter-popup button:hover { background-color: #1e7e34; }
.btn-reserve { color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; transition: background-color 0.3s ease; }
.btn-reserve.register { background-color: #28a745; }
.btn-reserve.register:hover:not(:disabled) { background-color: #1e7e34; }
.btn-reserve.cancel { background-color: #dc3545; }
.btn-reserve.cancel:hover:not(:disabled) { background-color: #c82333; }
.btn-reserve:disabled { background-color: gray; cursor: not-allowed; opacity: 0.7; }

</style>
</head>
<body>

<header class="navbar">
  <a class="logo" href="home.php" style="text-decoration: none;"><img src="logo.png" alt="LEMS Logo"><span>LEMS</span></a>
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

<script>
function toggleFilterPopup() {
  document.getElementById('filter-popup').classList.toggle('open');
}
</script>

<main>
<section class="events-section">
<div class="section-header">
  <h2><span class="calendar-icon">📅</span> Upcoming Events</h2>
  <div class="search-tools">
    <form method="GET" action="browse.php" style="width: 100%; display: flex; align-items: center;">
      <input type="text" name="search" placeholder="Search events by title or club..." value="<?php echo htmlspecialchars($search); ?>">
      <button type="submit" style="display:none;">Search</button>
    </form>
    <button class="filter-btn" onclick="toggleFilterPopup()">⚙️ Filter</button>
  </div>
</div>

<div id="filter-popup" class="filter-popup">
  <div class="filter-content">
    <h3>Filter Events</h3>
    <form method="GET" action="browse.php">
      <fieldset>
        <legend>Filter by Date</legend>
        <label>From:<input type="date" name="from_date"></label><br><br>
        <label>To:<input type="date" name="to_date"></label>
      </fieldset>
      <fieldset>
        <legend>Filter by Tags</legend>
        <?php
        $tagsResult = $conn->query("SELECT DISTINCT TAG FROM event_tags ORDER BY TAG ASC");
        while ($tag = $tagsResult->fetch_assoc()) {
          echo '<label><input type="checkbox" name="filter_tags[]" value="'.htmlspecialchars($tag['TAG']).'"> '.htmlspecialchars($tag['TAG']).'</label><br>';
        }
        ?>
      </fieldset>
      <fieldset>
        <legend>Filter by Club</legend>
        <select name="filter_club">
          <option value="">-- Select Club --</option>
          <?php
          $clubsResult = $conn->query("SELECT ID, CLUB_NAME FROM club ORDER BY CLUB_NAME ASC");
          while ($club = $clubsResult->fetch_assoc()) {
            echo '<option value="'.$club['ID'].'">'.htmlspecialchars($club['CLUB_NAME']).'</option>';
          }
          ?>
        </select>
      </fieldset>
      <button type="submit" style="margin-right: 10px;">Apply Filters</button>
      <button type="button" onclick="toggleFilterPopup()">Close</button>
    </form>
  </div>
</div>

<div class="event-grid">
<?php if (count($events) > 0): ?>
  <?php foreach ($events as $event): ?>
    <div class="event-card" onclick="window.location.href='event.php?event=<?php echo $event['EVENTID']; ?>'">
      <div class="event-image">
        <?php if (!empty($event['CLUB_NAMES'])): ?><span class="category-tag"><?php echo htmlspecialchars($event['CLUB_NAMES']); ?></span><?php endif; ?>
        <img src="<?php echo htmlspecialchars($event['IMAGE_URL']); ?>" alt="<?php echo htmlspecialchars($event['EVENTID']); ?> image">
      </div>
      <div class="event-content">
        <h3><?php echo htmlspecialchars($event['EVENT_NAME']); ?></h3>
        <?php $date = date('Y-m-d', strtotime($event['START_TIME'])); $time = date('H:i:s', strtotime($event['START_TIME'])); ?>
        <div class="event-detail"><span class="icon">📅</span><span><?php echo $date; ?></span></div>
        <div class="event-detail"><span class="icon">⏰</span><span><?php echo $time; ?></span></div>
        <div class="event-detail">
          <span class="icon">📍</span>
          <?php
          $locationId = $event['LOCATIONID'];
          $stmtLocation = $conn->prepare("SELECT * FROM `location` WHERE LOCATIONID = ?");
          $stmtLocation->bind_param("i", $locationId);
          $stmtLocation->execute();
          $locationResult = $stmtLocation->get_result();
          $location = $locationResult->fetch_assoc();
          $stmtLocation->close();
          ?>
          <span><?php echo htmlspecialchars($location['CAMPUS']) . ", " . htmlspecialchars($location['BUILDING']) . ", " . htmlspecialchars($location['ROOM']); ?></span>
        </div>
        <div class="progress-bar">
          <?php
          $filled_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registration WHERE EVENTID = ?");
          $filled_stmt->bind_param("i", $event['EVENTID']);
          $filled_stmt->execute();
          $filled_stmt->bind_result($filled);
          $filled_stmt->fetch();
          $filled_stmt->close();
          $total = $event['CAPACITY'] ?? 1;
          $percentFilled = ($filled / $total) * 100;
          ?>
          <div class="progress" style="width: <?php echo $percentFilled; ?>%;"></div>
        </div>
        <p class="spots-filled"><?php echo $filled; ?> / <?php echo $total; ?> spots filled</p>
        <?php
        $alreadyRegistered = false;
        if (isset($_SESSION['user']['mail'])) {
          $lau_email = $_SESSION['user']['mail'];
          $check_stmt = $conn->prepare("SELECT 1 FROM registration WHERE LAU_EMAIL = ? AND EVENTID = ?");
          $check_stmt->bind_param("si", $lau_email, $event['EVENTID']);
          $check_stmt->execute();
          $check_stmt->store_result();
          if ($check_stmt->num_rows > 0) {
              $alreadyRegistered = true;
          }
          $check_stmt->close();
        }
        ?>
        <?php if ($alreadyRegistered): ?>
          <button class="btn-reserve cancel" data-eventid="<?php echo $event['EVENTID']; ?>">Cancel Registration</button>
        <?php else: ?>
          <button class="btn-reserve register" data-eventid="<?php echo $event['EVENTID']; ?>">Register In Person</button>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <p>No events found.</p>
<?php endif; ?>
</div>
</section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const buttons = document.querySelectorAll('.btn-reserve');
  buttons.forEach(button => {
    button.addEventListener('click', function(event) {
      event.stopPropagation();
      const eventId = button.getAttribute('data-eventid');
      let action = button.innerText.trim() === 'Register In Person' ? 'register' : 'cancel';
      fetch('register_event.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'event_id=' + encodeURIComponent(eventId) + '&action=' + encodeURIComponent(action)
      })
      .then(response => response.text())
      .then(data => {
        data = data.trim();
        const eventCard = button.closest('.event-card');
        const spotsText = eventCard.querySelector('.spots-filled');
        const progressBar = eventCard.querySelector('.progress');
        if (data === 'registered') {
          button.innerText = 'Cancel Registration';
          button.classList.remove('register');
          button.classList.add('cancel');
          if (spotsText && progressBar) {
            let parts = spotsText.innerText.split('/');
            let currentFilled = parseInt(parts[0].trim()) + 1;
            let totalSpots = parseInt(parts[1].trim());
            spotsText.innerText = `${currentFilled} / ${totalSpots} spots filled`;
            progressBar.style.width = `${(currentFilled/totalSpots)*100}%`;
          }
        } else if (data === 'cancelled') {
          button.innerText = 'Register In Person';
          button.classList.remove('cancel');
          button.classList.add('register');
          if (spotsText && progressBar) {
            let parts = spotsText.innerText.split('/');
            let currentFilled = Math.max(0, parseInt(parts[0].trim()) - 1);
            let totalSpots = parseInt(parts[1].trim());
            spotsText.innerText = `${currentFilled} / ${totalSpots} spots filled`;
            progressBar.style.width = `${(currentFilled/totalSpots)*100}%`;
          }
        } else {
          alert('Failed: ' + data);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred.');
      });
    });
  });
});
</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
