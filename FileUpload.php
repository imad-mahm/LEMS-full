<?php
require 'vendor/autoload.php';
include "db_connection.php";
require_once "classes.php";
session_start();
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$OPENAI_API_KEY = $_ENV['OPENAI_API_KEY'];
$ASSISTANT_ID   = $_ENV['ASSISTANT_ID'];

function apiRequestJson(string $method, string $url, array $payload = []): array {
    global $OPENAI_API_KEY;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$OPENAI_API_KEY}",
        "OpenAI-Beta: assistants=v2",
        "Content-Type: application/json",
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if (!empty($payload)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$httpCode, $response];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate upload
    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        die("❌ File upload failed");
    }
    $tmpPath  = $_FILES['pdf_file']['tmp_name'];
    $filename = basename($_FILES['pdf_file']['name']);

    // 2. Upload file to OpenAI
    $ch = curl_init('https://api.openai.com/v1/files');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$OPENAI_API_KEY}",
        "OpenAI-Beta: assistants=v2",
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'file'    => new CURLFile($tmpPath, 'application/pdf', $filename),
        'purpose' => 'assistants'
    ]);
    $uploadResp = curl_exec($ch);
    curl_close($ch);

    $fileData = json_decode($uploadResp, true);
    if (empty($fileData['id'])) {
        die("❌ File upload failed: {$uploadResp}");
    }
    $fileId = $fileData['id'];

    // 3. Create a new thread
    list($status, $threadRaw) = apiRequestJson('POST', 'https://api.openai.com/v1/threads', []);
    if ($status !== 200 && $status !== 201) {
        die("❌ Thread creation failed: {$threadRaw}");
    }
    $thread   = json_decode($threadRaw, true);
    $threadId = $thread['id'] ?? null;
    if (!$threadId) {
        die("❌ Thread creation failed: {$threadRaw}");
    }

    // 4. Send transcription request
    list($status, $msgRaw) = apiRequestJson(
        'POST',
        "https://api.openai.com/v1/threads/{$threadId}/messages",
        [
            'role'        => 'user',
            'content'     => 'Please transcribe this PDF file.',
            'attachments' => [[
                'file_id' => $fileId,
                'tools'   => [['type' => 'file_search']]
            ]]
        ]
    );
    if ($status !== 200 && $status !== 201) {
        die("❌ Message post failed: {$msgRaw}");
    }

    // 5. Kick off the transcription run
    list($status, $runRaw) = apiRequestJson(
        'POST',
        "https://api.openai.com/v1/threads/{$threadId}/runs",
        ['assistant_id' => $ASSISTANT_ID]
    );
    if ($status !== 200 && $status !== 201) {
        die("❌ Assistant run failed: {$runRaw}");
    }
    $run   = json_decode($runRaw, true);
    $runId = $run['id'] ?? null;
    if (!$runId) {
        die("❌ Assistant run failed: {$runRaw}");
    }

    // 6. Poll until transcription completes
    $start   = time();
    $timeout = 5 * 60;
    while (true) {
        sleep(2);
        list($httpCode, $checkRaw) = apiRequestJson(
            'GET',
            "https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}"
        );
        if ($httpCode !== 200) {
            die("❌ Polling error: {$checkRaw}");
        }
        $check = json_decode($checkRaw, true);
        $state = $check['status'] ?? '';
        if (in_array($state, ['queued','in_progress'], true)) {
            if (time() - $start > $timeout) {
                die("❌ Polling timed out");
            }
            continue;
        }
        if ($state === 'completed') break;
        if ($state === 'failed') {
            $err = $check['last_error']['message'] ?? $checkRaw;
            die("❌ Assistant run failed: {$err}");
        }
        die("❌ Unexpected run status '{$state}': {$checkRaw}");
    }

    // 7. Ask for assumed preferences
    list($status, $prefsMsgRaw) = apiRequestJson(
        'POST',
        "https://api.openai.com/v1/threads/{$threadId}/messages",
        [
            'role'    => 'user',
            'content' => "Based on the transcription above, please provide a list of preferences for the user in the format similar to 'web programming, Computer Science, Psychology, etc...'.The preferences will be used later to decide what items to recommend for a user. Give preferences that cover as much variety as possible (even if 1 course is an unrelated elective, include it). do not use quotaions or new lines or brackets. Return nothing but the preferences as an array"
        ]
    );
    if ($status !== 200 && $status !== 201) {
        die("❌ Preferences post failed: {$prefsMsgRaw}");
    }

    // 8. Kick off the preferences run
    list($status, $prefsRunRaw) = apiRequestJson(
        'POST',
        "https://api.openai.com/v1/threads/{$threadId}/runs",
        ['assistant_id' => $ASSISTANT_ID]
    );
    if ($status !== 200 && $status !== 201) {
        die("❌ Preferences run failed: {$prefsRunRaw}");
    }
    $prefsRun   = json_decode($prefsRunRaw, true);
    $prefsRunId = $prefsRun['id'] ?? null;
    if (!$prefsRunId) {
        die("❌ Preferences run failed: {$prefsRunRaw}");
    }

    // 9. Poll until preferences completes
    while (true) {
        sleep(2);
        list($httpCode, $checkRaw) = apiRequestJson(
            'GET',
            "https://api.openai.com/v1/threads/{$threadId}/runs/{$prefsRunId}"
        );
        if ($httpCode !== 200) {
            die("❌ Preferences polling error: {$checkRaw}");
        }
        $check = json_decode($checkRaw, true);
        $state = $check['status'] ?? '';
        if (in_array($state, ['queued','in_progress'], true)) continue;
        if ($state === 'completed') break;
        die("❌ Preferences run failed: {$checkRaw}");
    }

    // 10. Fetch and display assumed preferences
    list($status, $allPrefsRaw) = apiRequestJson(
        'GET',
        "https://api.openai.com/v1/threads/{$threadId}/messages"
    );
    $allMsgs = json_decode($allPrefsRaw, true);
    $user = new User();
    $user->getUserInfo($_SESSION['user']['email']);
    foreach ($allMsgs['data'] as $msg) {
        if ($msg['role'] === 'assistant' && ($msg['run_id'] ?? '') === $prefsRunId) {
            foreach ($msg['content'] as $part) {
                $prefs = explode(', ', $part['text']['value']);
                $user->updatePrefs($prefs);
                $_SESSION['user']['preferences'] = $prefs;
            }
            break;
        }
    }
    header("Location: Recommended.php");
    exit;
}
?>