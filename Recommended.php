<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit();
}

// Load environment and OpenAI client
require 'vendor/autoload.php';
require_once "classes.php";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$client = OpenAI::client($_ENV['OPENAI_API_KEY']);

// Retrieve user preferences
$user = new User();
$user->getUserInfo($_SESSION['user']['email']);
$user->getUserPreferences();
$userPreferences = $user->pref;

// Fetch all events
require_once "classes.php";
include "db_connection.php";
$eventManager = new EventManager();
$eventManager->getAllEvents();
$events = $eventManager->events;

// Rate events using OpenAI
$recommendedEvents = [];
if (!empty($events) && !empty($userPreferences)) {
    $Prefs = 'Preferences: ' . implode(', ', $userPreferences) . "\n\n";
    foreach ($events as $event) {
        $Tags = 'Tags: ' . implode(', ', $event->tags) . "\n\n";
        $prompt = "Your task is to rate an event on how likely it is for the user to enjoy it on a scale of 1 to 10. ";
        $prompt .= "If the score is over 7, respond with the word 'Yes'; otherwise, respond with the word 'No'. ";
        $prompt .= "Do not include any more text than that. Be brutal in the scoring.\n";
        $prompt .= $Prefs . $Tags;

        try {
            $response = $client->chat()->create([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant that rates events.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.7,
                'max_tokens' => 1
            ]);
            $answer = trim($response->choices[0]->message->content);
            if (strcasecmp($answer, 'Yes') === 0) {
                $recommendedEvents[] = $event;
            }
        } catch (\Exception $e) {
            error_log('OpenAI API error: ' . $e->getMessage());
        }
    }
}
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
        <a class="logo" href="home.php">
            <img src="logo.png" alt="LEMS Logo" />
            <span>LEMS</span>
        </a>
        <nav class="nav-links">
            <a href="browse.php">Browse Events</a>
            <a href="recommended.php">Recommended</a>
            <div class="profile-dropdown">
                <img src="https://img.icons8.com/ios-filled/24/ffffff/user.png" alt="User Icon" class="profile-icon" onclick="toggleDropdown()" />
                <div id="dropdown-menu" class="dropdown-menu">
                    <a href="profile.html" class="dropdown-item">Profile</a>
                    <a href="index.html" class="dropdown-item" style="color:red;">Log Out</a>
                </div>
            </div>
        </nav>
    </header>
    <main class="container">
        <h1>Recommended Events</h1>
        <p class="subtext">Personalized event suggestions based on your academic interests</p>

        <?php if (!empty($recommendedEvents)): ?>
            <section id="recommendations">
                <h2>Recommended Events Based on Your Transcript</h2>
                <div class="event-grid">
                <?php foreach ($recommendedEvents as $event): ?>
                  <div class="event-card" onclick="window.location.href='event.php?event=<?php echo $event->eventID; ?>'">
                    <div class="event-image">
                      <?php if (!empty($event->createdBy)): ?><span class="category-tag">
                        <?php 
                          $stmtClub = $conn->prepare("SELECT CLUB_NAME FROM club WHERE ID = ?");
                          $stmtClub->bind_param("i", $event->createdBy[0]);
                          $stmtClub->execute();
                          $clubResult = $stmtClub->get_result();
                          $club = $clubResult->fetch_assoc();
                          $stmtClub->close();
                          echo htmlspecialchars($club['CLUB_NAME']);
                        ?>
                      </span><?php endif; ?>
                      <img src="<?php echo htmlspecialchars($event->imageURL); ?>" alt="<?php echo htmlspecialchars($event->eventID); ?> image">
                    </div>
                    <div class="event-content">
                      <h3><?php echo htmlspecialchars($event->title); ?></h3>
                      <?php $date = date('Y-m-d', strtotime($event->startTime)); $time = date('H:i:s', strtotime($event->startTime)); ?>
                      <div class="event-detail"><span class="icon">📅</span><span><?php echo $date; ?></span></div>
                      <div class="event-detail"><span class="icon">⏰</span><span><?php echo $time; ?></span></div>
                      <div class="event-detail">
                        <span class="icon">📍</span>
                        <?php
                        $locationId = $event->location;
                        $stmtLocation = $conn->prepare("SELECT * FROM location WHERE LOCATIONID = ?");
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
                        $filled_stmt->bind_param("i", $event->eventID);
                        $filled_stmt->execute();
                        $filled_stmt->bind_result($filled);
                        $filled_stmt->fetch();
                        $filled_stmt->close();
                        $total = $event->capacity ?? 1;
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
                        $check_stmt->bind_param("si", $lau_email, $event->eventID);
                        $check_stmt->execute();
                        $check_stmt->store_result();
                        if ($check_stmt->num_rows > 0) {
                            $alreadyRegistered = true;
                        }
                        $check_stmt->close();
                      }
                      ?>
                      <?php if ($alreadyRegistered): ?>
                        <button class="btn-reserve cancel" data-eventid="<?php echo $event->eventID; ?>">Cancel Registration</button>
                      <?php else: ?>
                        <button class="btn-reserve register" data-eventid="<?php echo $event->eventID; ?>">Register In Person</button>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
                </div>
            </section>
          <?php else: ?>
            <div id="upload-card" class="card">
                <h2>Academic Profile</h2>
                <p>Upload and manage your transcript for personalized recommendations</p>
                <form action="FileUpload.php" method="post" enctype="multipart/form-data">
                    <div class="custom-file">
                        <label for="pdf_file" class="file-box">
                            <img src="https://img.icons8.com/ios-filled/24/065f46/file--v1.png" alt="File Icon" class="file-icon" />
                            <span id="file-label">Choose a PDF file</span>
                        </label>
                        <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" />
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-success">Upload Transcript</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </main>
    <footer class="footer">
        <p>© 2025 LEMS. All rights reserved.</p>
    </footer>

    <script>
        function toggleDropdown() {
            const menu = document.getElementById('dropdown-menu');
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }
        window.onclick = function(e) {
            if (!e.target.matches('.profile-icon')) {
                const menu = document.getElementById('dropdown-menu');
                if (menu.style.display === 'block') menu.style.display = 'none';
            }
        }
        const transcriptInput = document.getElementById('pdf_file');
        const fileLabel = document.getElementById('file-label');
        transcriptInput.addEventListener('change', () => {
            fileLabel.textContent = transcriptInput.files[0]?.name || 'Choose a PDF file';
        });
    </script>
</body>
</html>