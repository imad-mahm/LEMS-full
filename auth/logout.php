<?php
session_start();
session_destroy();

// Redirect to Microsoft logout + your post-logout landing page
$logout_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/logout?' . http_build_query([
    'post_logout_redirect_uri' => 'http://localhost/LEMS/login.php'  // or wherever you want to redirect after logout
]);

header('Location: ' . $logout_url);
exit;
?>
