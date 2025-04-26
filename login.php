<?php
$client_id = '33c1718a-480b-4372-810a-a01ce454f3d0';
$redirect_uri = 'http://localhost/LEMS/auth/callback.php';
$scope = 'openid email profile User.Read';
$authorize_url = "https://login.microsoftonline.com/common/oauth2/v2.0/authorize?" . http_build_query([
    'client_id' => $client_id,
    'response_type' => 'code',
    'redirect_uri' => $redirect_uri,
    'response_mode' => 'query',
    'scope' => $scope,
    'state' => bin2hex(random_bytes(16))
]);

header("Location: $authorize_url");
exit;
?>