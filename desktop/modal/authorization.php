<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

Config::unsetAccessToken();
Config::unsetRefreshToken();
Config::unsetTokenExists();

$state = hash("sha256", rand());
Config::setConsumerState($state);

$location = HttpRequest::get('https://accounts.somfy.com/oauth/oauth/v2/auth')
    ->param('response_type', 'code')
    ->param('client_id', Config::getConsumerKey())
    ->param('redirect_uri', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/index.php?v=d&plugin=' . Plugin::ID . '&modal=callback')
    ->param('state', $state)
    ->param('grant_type', 'authorization_code')
    ->buildUrl();

header('Location: ' . $location);
?>
