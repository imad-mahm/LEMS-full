<?php
require 'vendor/autoload.php';
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use OpenAI\Client;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
// use Google_Client;
// use Google_Service_Gmail;

class User {
	public $email;
	public $role;
	public $firstName;
	public $lastName;
	public $club = [];
	public $prefs = [];
	
	public function createUser($param) {
		include 'db_connection.php';
		$userRole = "user";
		$mail = $param['mail'] ?? $param['userPrincipalName'] ?? $param['preferred_username'];

		$stmt = $conn->prepare("INSERT INTO user (LAU_EMAIL, USER_ROLE, FIRST_NAME, LAST_NAME) VALUES (?, ?, ?, ?)");
		$stmt->bind_param("ssss", $mail, $userRole, $param['givenName'], $param['surName']);
		$stmt->execute();
		$stmt->close();
	}
	public function getUserInfo($email) {
		//function to fetch data from DB
		include 'db_connection.php';
		$stmt = $conn->prepare("SELECT * FROM user WHERE LAU_email = ?");
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0){
			$userData = $result->fetch_assoc();
			$this->firstName = $userData['FIRST_NAME'];
			$this->lastName = $userData['LAST_NAME'];
			$this->email = $userData['LAU_EMAIL'];
			$this->role = $userData['USER_ROLE'];
			if($this->role == "organizer" || $this->role == "admin"){
				$stmt3 = $conn->prepare("SELECT CLUBID FROM `committee` WHERE PRESIDENT = ? OR TREASURER = ? OR SECRETARY = ?");
				$stmt3->bind_param("sss", $this->email, $this->email, $this->email);
				$stmt3->execute();
				$result3 = $stmt3->get_result();
				if($result3->num_rows > 0){
					while ($row = $result3->fetch_assoc()) {
						$this->club[] = $row['CLUBID'];
						echo "<script> alert(you are committee member of club " . $row['CLUBID'] . ")</script>";
					}
				}
				$stmt3->close();
			}
		}
		else{
			return false;
		}
		$stmt2 = $conn->prepare("SELECT CU.CLUBID FROM CLUB_USER CU JOIN user U ON CU.LAU_EMAIL = U.LAU_EMAIL WHERE U.LAU_EMAIL = ?");
		$stmt2->bind_param("s", $email);
		$stmt2->execute();
		$result2 = $stmt2->get_result();
		while ($row = $result2->fetch_assoc()) {
			$this->club[] = $row['CLUBID'];
		}
		$stmt->close();
		$stmt2->close();
		return true;
	}
	public function updateProfile($param) {
		//function to update data in DB
		include 'db_connection.php';
		$stmt = $conn->prepare("UPDATE user SET FIRST_NAME = ?, LAST_NAME = ? WHERE LAU_EMAIL = ?");
		$stmt->bind_param("sss", $param['givenName'], $param['surName'], $this->email);
		$stmt->execute();
		$stmt->close();
	}

	public function updatePrefs($prefArr) {
		include "db_connection.php";
		$stmt = $conn->prepare("INSERT INTO USER_PREFERENCES(LAU_EMAIL, PREFERENCE) VALUES (?,?)");
		foreach($prefArr as $pref){
			$stmt->bind_param("ss", $this->email, $pref);
			$stmt->execute();
		}
	}

	public function getUserPreferences() {
		include 'db_connection.php';
		$stmt = $conn->prepare("SELECT PREFERENCE FROM USER_PREFERENCES WHERE LAU_EMAIL = ?");
		$stmt->bind_param("s", $this->email);
		$stmt->execute();
		$result = $stmt->get_result();

		$preferences = [];
		while ($row = $result->fetch_assoc()) {
			$preferences[] = $row['PREFERENCE'];
		}

		$stmt->close();
		$this->pref = $preferences;
		return $preferences;
	}

	public function viewPastEvents() {
		include 'db_connection.php';
		$stmt = $conn->prepare("SELECT * FROM `event` WHERE START_TIME < current_timestamp() AND eventid IN (SELECT eventid FROM user_attended_events WHERE user_email = ?)");
		$stmt->bind_param("s", $this->email);
		$stmt->execute();
		$result = $stmt->get_result();
		$stmt->close();
		if($result->num_rows > 0){
			return $result->fetch_all(MYSQLI_ASSOC);
		} else {
			return false;
		}
	}
	public function joinClub($clubID) {
		include 'db_connection.php';
		$stmt = $conn->prepare("INSERT INTO club_user (CLUBID, LAU_email) VALUES (?, ?)");
		$stmt->bind_param("is", $clubID, $this->email);
		$stmt->execute();
		$stmt->close();
	}
	public function submitFeedback($eventID, $rating, $comment) {
		include 'db_connection.php';
		$stmt = $conn->prepare("INSERT INTO `feedback` (LAU_EMAIL, EVENTID, RATING, CONTENT) VALUES (?, ?, ?, ?)");
		$stmt->bind_param("siis", $this->email, $eventID, $rating, $comment);
		$stmt->execute();
		$stmt->close();

	}
	public function approveEvent($eventID) {
		include 'db_connection.php';
		if($this->role != "admin"){
			return false;
		}
		$stmt = $conn->prepare("UPDATE `event` SET STATE = 'approved' WHERE eventid = ?");
		$stmt->bind_param("i", $eventID);
		$stmt->execute();
		$stmt->close();
		return true;
	}
	public function rejectEvent() {
		include 'db_connection.php';
		if($this->role != "admin"){
			return false;
		}
		$stmt = $conn->prepare("UPDATE `event` SET STATE = 'rejected' WHERE eventid = ?");
		$stmt->bind_param("i", $eventID);
		$stmt->execute();
		$stmt->close();
		return true;
	}
	public function manageUserRole($userEmail, $role) {
		include 'db_connection.php';
		if($this->role != "admin"){
			return false;
		}
		$stmt = $conn->prepare("UPDATE `user` SET role = ? WHERE LAU_EMAIL = ?");
		$stmt->bind_param("ss", $role, $userID);
		$stmt->execute();
		$stmt->close();
		return true;
	}
	public function deletUser($userEmail) {
		include 'db_connection.php';
		if($this->role != "admin"){
			return false;
		}
		$stmt = $conn->prepare("DELETE FROM `user` WHERE LAU_EMAIL = ?");
		$stmt->bind_param("s", $userEmail);
		$stmt->execute();
		$stmt->close();
		return true;
	}
	public function createClub($club) {
		include 'db_connection.php';
		if($this->role != "admin"){
			return false;
		}
		$stmt = $conn->prepare("INSERT INTO `club` (CLUB_NAME, CLUB_DESCRIPTION, CLUB_EMAIL) VALUES (?, ?, ?)");
		$stmt->bind_param("sss", $club['name'], $club['decription'], $club['email']);
		$stmt->execute();
		$stmt->close();
		return true;
	}
	public function createEvent($event) {
		include 'db_connection.php';
		if($this->role != "admin" && $this->role != "organizer"){
			return false;
		}
		$startTime = new DateTime($event['startTime']);
		$endTime = clone $startTime;
		$endTime->modify("+{$event['duration']} minutes");
		$stmt = $conn->prepare("INSERT INTO `event` (EVENT_NAME, EVENT_DESCRIPTION, DURATION, START_TIME, END_TIME, LOCATIONID, IMAGE_URL, CAPACITY) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bind_param(
			"sssssisi",
			$event['title'],
			$event['description'],
			$event['duration'],
			$startTime->format('Y-m-d H:i:s'),
			$endTime->format('Y-m-d H:i:s'),
			$event['locationID'],
			$event['imageURL'],
			$event['capacity'],
		);
		$stmt->execute();
		$stmt->close();
		return true;
	}
}

class EventManager {
	public $eventIDs = [];
	public $events = [];
	public function getAllEvents($time = "future", $state = "approved", $clubID = null) {
		include 'db_connection.php';

		// Build the base query
		$query = "SELECT EVENTID FROM `event` e ";
		if($state = "all"){
			$query .= "WHERE 1=1";
		} else {
			$query .= "WHERE STATE = ?";
		}
		if ($time == "past") {
			$query .= " AND END_TIME < current_timestamp()";
		} else if ($time == "future") {
			$query .= " AND END_TIME > current_timestamp()";
		}

		// Add club filter if provided
		if ($clubID !== null) {
			$query .= "AND EVENTID IN (SELECT EVENTID FROM `event_club` WHERE CLUBID = ?)";
		}

		// Prepare the statement
		$stmt = $conn->prepare($query);

		// Bind parameters
		if ($clubID !== null) {
			if ($state == "all") {
				$stmt->bind_param("i", $clubID);
			} else {
				$stmt->bind_param("si", $state, $clubID);
			}
		} else {
			if ($state != "all") {
				$stmt->bind_param("s", $state);
			}
		}

		// Execute the query
		$stmt->execute();
		$result = $stmt->get_result();

		// Fetch all events
		$events = [];
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$event = new Event();
				$event->getDetails($row['EVENTID']);
				$this->events[] = $event;
				$this->eventIDs[] = $row['EVENTID'];
			}
		}

		// Close the statement and return the events
		$stmt->close();
		return $events;
	}
}

class Event {
	public $eventID;
	public $title;
	public $description;
	public $duration;
	public $location;
	public $startTime;
	public $endTime;
	public $state;
	public $createdBy= [];
	public $capacity;
	public $tags = [];
	public $imageURL;
	public $filledSeats = 0;
	public $feedbacks = [];
	/*public function __construct($eventID, $title, $description, $duration, $startTime, $endTime, $location, $state, $capacity, $imageURL) {
		$this->eventID = $eventID;
		$this->title = $title;
		$this->description = $description;
		$this->duration = $duration;
		$this->startTime = $startTime;
		$this->endTime = $endTime;
		$this->location = $location;
		$this->state = $state;
		$this->capacity = $capacity;
		$this->imageURL = $imageURL;
	}*/
	public function getDetails($eventID) {
		//function to fetch data from DB
		include 'db_connection.php';
		$stmt = $conn->prepare("SELECT * FROM `event` WHERE eventid = ?");
		$stmt->bind_param("i", $eventID);
		$stmt->execute();
		$result = $stmt->get_result();
		$eventData = $result->fetch_assoc();
		$this->eventID = $eventData['EVENTID'];
		$this->title = $eventData['EVENT_NAME'];
		$this->description = $eventData['EVENT_DESCRIPTION'];
		$this->duration = $eventData['DURATION'];
		$this->startTime = $eventData['START_TIME'];
		$this->endTime = $eventData['END_TIME'];
		$this->location = $eventData['LOCATIONID'];
		$this->state = $eventData['STATE'];
		$this->capacity = $eventData['CAPACITY'];
		$this->imageURL = $eventData['IMAGE_URL'];
		//get club id from event_club
		$stmt2 = $conn->prepare("SELECT CLUBID FROM `event_club` WHERE EVENTID = ?");
		$stmt2->bind_param("i", $eventID);
		$stmt2->execute();
		$result2 = $stmt2->get_result();
		if($result2->num_rows > 0){
			while ($row = $result2->fetch_assoc()) {
				$this->createdBy[] = $row['CLUBID'];
			}
		}
		//get tags from event_tags
		$stmt3 = $conn->prepare("SELECT TAG FROM `event_tags` WHERE EVENTID = ?");
		$stmt3->bind_param("i", $eventID);
		$stmt3->execute();
		$result3 = $stmt3->get_result();
		if($result3->num_rows > 0){
			while ($row = $result3->fetch_assoc()) {
				$this->tags[] = $row['TAG'];
			}
		}
		//get filled seats from registration
		$stmt4 = $conn->prepare("SELECT MAX(TICKET_NB) as filledSeats FROM `registration` WHERE EVENTID = ?");
		$stmt4->bind_param("i", $eventID);
		$stmt4->execute();
		$result4 = $stmt4->get_result();
		if($result4->num_rows > 0){
			$this->filledSeats = $result4->fetch_assoc()['filledSeats'];
		}
		$stmt->close();
		$stmt2->close();
		$stmt3->close();
		$stmt4->close();
	}
	public function cancelEvent() {
		include 'db_connection.php';
		if($this->state != "approved"){
			return false;
		}
		$stmt = $conn->prepare("UPDATE `event` SET STATE = 'cancelled' WHERE eventid = ?");
		$stmt->bind_param("i", $this->eventID);
		$stmt->execute();
		$stmt->close();
		return true;
	}
	public function updateEvent() {
		include 'db_connection.php';
		if($this->state != "approved" && $this->state != "pending"){
			return false;
		}
		$startTime = new DateTime($this->startTime);
		$endTime = clone $startTime; // Clone startTime to avoid modifying it
		$endTime->modify("+{$this->duration} minutes");
		$stmt = $conn->prepare("UPDATE `event` SET EVENT_NAME = ?, EVENT_DESCRIPTION = ?, DURATION = ?, START_TIME = ?, END_TIME = ?, LOCATIONID = ?, IMAGE_URL = ?, CAPACITY = ? WHERE eventid = ?");
		$stmt->bind_param(
			"ssissisii",
			$this->title,
			$this->description,
			$this->duration,
			$startTime,
			$endTime,
			$this->locationID,
			$this->imageURL,
			$this->capacity,
			$this->eventID
		);
		$stmt->execute();
		$stmt->close();
		return true;
	}
	public function getFeedback() {
		//use feedback manager to get feedbacks
		$feedbackManager = new FeedbackManager();
		$this->feedbacks = $feedbackManager->getFeedbackFor($this->eventID);
	}
}

class FeedbackManager {
	public function getFeedbackFor($eventID) {
		include 'db_connection.php';
		$stmt = $conn->prepare("SELECT * FROM `feedback` WHERE eventid = ?");
		$stmt->bind_param("i", $eventID);
		$stmt->execute();
		$result = $stmt->get_result();

		$feedbacks = [];
		while ($row = $result->fetch_assoc()) {
			$feedback = new Feedback(
				$row['FEEDBACKID'],
				$row['LAU_EMAIL'],
				$row['EVENTID'],
				$row['RATING'],
				$row['CONTENT'],
				$row['ADDED_AT']
			);
			$feedbacks[] = $feedback;
		}

		$stmt->close();
		return $feedbacks;
	}

	public function getAverageRating($eventID) {
		include 'db_connection.php';
		$stmt = $conn->prepare("SELECT AVG(RATING) as average FROM `feedback` WHERE eventid = ? GROUP BY eventid");
		$stmt->bind_param("i", $eventID);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0){
			return $result->fetch_assoc()['average'];
		} else {
			return false;
		}
		$stmt->close();
	}

	public function summarizeReviews($reviews) {
		$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
		$dotenv->load();
	
		// Initialize OpenAI client
		$client = OpenAI::client($_ENV['OPENAI_API_KEY']);
	
		// Prepare the reviews text
		$reviewsText = "";
		foreach ($reviews as $review) {
			$reviewsText .= "Rating: " . $review->rating . "/5\n";
			$reviewsText .= "Review: " . $review->comment . "\n\n";
		}
	
		// Create the prompt for OpenAI
		$prompt = "You are an expert event reviewer and writer. You will receive a list of reviews written by attendees of an event. Your task is to summarize these reviews into one well-written, balanced, and engaging summary that reflects the overall sentiment, highlights common themes, praises, and criticisms, and reads as if written by a professional reviewer.
				Instructions:
				-Combine all major points from the reviews into one cohesive review.
				-Reflect the overall tone (positive, negative, or mixed).
				-Include commonly mentioned strengths and weaknesses.
				-Don't make up any new information or opinions.
				-Use natural, fluent language suitable for publication on a website or report. 
				The reviews are: " . $reviewsText . " Limit the summary to 50 words.";
	
		try {
			// Call OpenAI API
			$response = $client->chat()->create([
				'model' => 'gpt-4o',
				'messages' => [
					['role' => 'system', 'content' => 'You are a helpful assistant that summarizes event reviews.'],
					['role' => 'user', 'content' => $prompt]
				],
				'temperature' => 0.7,
				'max_tokens' => 100
			]);
	
			return $response->choices[0]->message->content;
		} catch (Exception $e) {
			return "Unable to generate summary at this time.";
		}
	}
}

class Feedback {
	public $feedbackID;
	public $user;
	public $event;
	public $rating;
	public $comment;
	public $timestamp;
	//has no methods except constructor (no point in creating them)
	public function __construct($feedbackID, $user, $event, $rating, $comment, $timestamp) {
		$this->feedbackID = $feedbackID;
		$this->user = $user;
		$this->event = $event;
		$this->rating = $rating;
		$this->comment = $comment;
		$this->timestamp = $timestamp;
	}
}

class Registration {
	public $ticketNB = [];
	public $user;
	public $event;
	public $registrationDate;
	public $state;
	public function getRegistrationInfo($userEmail, $eventID) {
		include 'db_connection.php';
		$stmt = $conn->prepare("SELECT * FROM `registration` WHERE LAU_EMAIL = ? AND EVENTID = ?");
		$stmt->bind_param("si", $userEmail, $eventID);
		$stmt->execute();
		$result = $stmt->get_result();
	
		if($result->num_rows > 0){
			$this->user = $userEmail;
			$this->event = $eventID;
			$this->ticketNB = [];
			
			while ($row = $result->fetch_assoc()) {
				$this->ticketNB[] = $row['TICKET_NB'];
				$this->registrationDate = $row['REGISTRATION_DATE']; // Last one will overwrite
				$this->state = $row['STATE']; // Last one will overwrite
			}
			$stmt->close();
			return true;
		} else {
			$stmt->close();
			return false;
		}
	}
	
	public function registerUser($userEmail, $eventID) {
		include 'db_connection.php';    
		// Step 1: Count existing registrations for the event
		$countStmt = $conn->prepare("SELECT COUNT(*) as count FROM registration WHERE EVENTID = ? GROUP BY EVENTID");
		$countStmt->bind_param("i", $eventID);
		$countStmt->execute();
		$result = $countStmt->get_result();
		$row = $result->fetch_assoc();
		$ticketNb = $row['count'] + 1;
		$countStmt->close();
	
		// Step 2: Insert new registration
		$stmt = $conn->prepare("INSERT INTO `registration` (LAU_EMAIL, EVENTID, TICKET_NB) VALUES (?, ?, ?)");
		$stmt->bind_param("sii", $userEmail, $eventID, $ticketNb);
		$stmt->execute();
		$stmt->close();
	}
	
	public function cancelRegistration() {
		include 'db_connection.php';
		$stmt = $conn->prepare("DELETE FROM `registration` WHERE LAU_EMAIL = ? AND EVENTID = ? ");
		$stmt->bind_param("si", $this->user, $this->event);
		$stmt->execute();
		$stmt->close();
	}
}

class Notification {
	public $notificationID;
	public $recipients = [];
	public $subject;
	public $message;
	public $timestamp;
	public $sender;
	public function __construct($recipients, $subject, $message) {
		$this->recipients = $recipients;
		$this->subject = $subject;
		$this->message = $message;
		$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
		$dotenv->load();
		$this->sender = $_ENV['SMTP_USER']; // Your Gmail address
	}
	public function sendNotification() {
		// Load .env
		$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
		$dotenv->load();
	
		$client = new Google_Client();
		$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
		$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
		$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']); // Only needed during initial auth
		$client->setAccessType('offline');
		$client->setPrompt('consent');
		$client->addScope(Google_Service_Gmail::MAIL_GOOGLE_COM);
	
		// Load stored refresh token
		$tokenData = json_decode(file_get_contents(__DIR__ . '/gmail-refresh-token.json'), true);
		$client->refreshToken($tokenData['refresh_token']);
	
		$accessToken = $client->getAccessToken()['access_token'];
	
		// Use EsmtpTransport with OAuth2
		$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
		$dotenv->load();
		$smtpUser = $_ENV['SMTP_USER']; // Your Gmail address
		$transport = new EsmtpTransport('smtp.gmail.com', 587);
		$transport->setUsername($smtpUser);
		$transport->setPassword($accessToken);
	
		$mailer = new Mailer($transport);
	
		$email = (new Email())
			->from($this->sender)
			->to(...$this->recipients)
			->subject($this->subject)
			->text($this->message);
	
		try {
			$mailer->send($email);
			if ($mailer) {
				error_log('Email sent successfully.');
				return true;
			} else {
				error_log('Email sending failed.');
				return false;
			}
			
		} catch (\Exception $e) {
			error_log('Failed to send email: ' . $e->getMessage());
			return $e->getMessage();    
		}
	}
}

?>