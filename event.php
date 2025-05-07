<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit();
}
include "db_connection.php";
require_once "classes.php";

if (!isset($_GET['event'])) {
    die("No event selected.");
}

$eventId = intval($_GET['event']);

// Fetch event details
$event = new Event();
$event->getDetails($eventId);
if (!$event->title) {
    die("Event not found.");
}

// Fetch location details
$stmtLocation = $conn->prepare("SELECT * FROM location WHERE LOCATIONID = ?");
$stmtLocation->bind_param("i", $event->location);
$stmtLocation->execute();
$resultLocation = $stmtLocation->get_result();
$location = $resultLocation->fetch_assoc();
if (!$location) {
    die("Location not found. $event->location , location: ");
}

// Check if user is already registered
$registration = new Registration();
$alreadyRegistered = $registration->getRegistrationInfo($_SESSION['user']['email'], $eventId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($event->title); ?> - Event Details</title>
 
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f5f7fa;
      margin: 0;
      padding: 0;
    }

    .event-details-container {
      max-width: 900px;
      background: white;
      margin: 40px auto;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    .event-details-container img {
      width: 100%;
      max-height: 400px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 20px;
    }

    .event-details h1 {
      font-size: 32px;
      margin-bottom: 10px;
      color: #333;
    }

    .event-info {
      margin-top: 20px;
    }

    .event-info p {
      font-size: 18px;
      margin: 10px 0;
      color: #555;
    }

    .event-info p span {
      font-weight: bold;
      color: #333;
    }

    .btn-reserve {
      margin-top: 30px;
      padding: 12px 24px;
      font-size: 18px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s ease;
      color: white;
    }

    .btn-reserve.register {
      background-color: #28a745;
    }

    .btn-reserve.register:hover {
      background-color: #1e7e34;
    }

    .btn-reserve.cancel {
      background-color: #dc3545;
    }

    .btn-reserve.cancel:hover {
      background-color: #c82333;
    }

    @media (max-width: 600px) {
      .event-details-container {
        padding: 20px;
      }
      .event-details h1 {
        font-size: 26px;
      }
      .event-info p {
        font-size: 16px;
      }
    }
  </style>
</head>
<body>
<div style="max-width: 900px; margin: 30px auto 0; text-align: left;">
  <a href="browse.php" style="display: inline-block; background-color: #28a745; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 16px; transition: background-color 0.3s;">
    ← Back to Browse Events
  </a>
</div>


<main>
  <div class="event-details-container">
    <div class="event-details">
      <h1><?php echo htmlspecialchars($event->title); ?></h1>
      <img src="<?php echo htmlspecialchars($event->imageURL); ?>" alt="Event Image">

      <div class="event-info">
        <p><span>📝 Description:</span> <?php echo nl2br(htmlspecialchars($event->description)); ?></p>
        <p><span>🗓️ Start Time:</span> <?php echo htmlspecialchars($event->startTime); ?></p>
        <p><span>⏰ End Time:</span> <?php echo htmlspecialchars($event->endTime); ?></p>
        <p><span>📍 Location:</span> <?php echo htmlspecialchars($location['CAMPUS']) . ", " . htmlspecialchars($location['BUILDING']) . ", " . htmlspecialchars($location['ROOM']); ?></p>
        <p><span>👥 Capacity:</span> <?php echo htmlspecialchars($event->capacity); ?> people</p>
      </div>

      <div style="text-align:center;">
        <?php if ($alreadyRegistered): ?>
          <button class="btn-reserve cancel" data-eventid="<?php echo $eventId; ?>">Cancel Registration</button>
        <?php else: ?>
          <button class="btn-reserve register" data-eventid="<?php echo $eventId; ?>">Register In Person</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const button = document.querySelector('.btn-reserve');

    button.addEventListener('click', function(event) {
        event.stopPropagation();
        const eventId = button.getAttribute('data-eventid');
        let action = button.innerText.includes('Cancel') ? 'cancel' : 'register';

        fetch('register_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'event_id=' + encodeURIComponent(eventId) + '&action=' + encodeURIComponent(action)
        })
        .then(response => response.text())
        .then(data => {
            data = data.trim();
            if (data === 'registered') {
                button.innerText = 'Cancel Registration';
                button.classList.remove('register');
                button.classList.add('cancel');
            } else if (data === 'cancelled') {
                button.innerText = 'Register In Person';
                button.classList.remove('cancel');
                button.classList.add('register');
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
</script>
</body>
</html>

<?php
$conn->close();
$stmtLocation->close();
?>
