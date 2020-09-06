<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

Config::unset('access_token');
Config::unset('refresh_token');
Config::unset('token_exists');

$state = hash("sha256", rand());
Config::set('consumer_state', $state);

$consumer_key = Config::get('consumer_key');

$location = HttpRequest::get()
    ->url('https://accounts.somfy.com/oauth/oauth/v2/auth')
    ->param('response_type', 'code')
    ->param('client_id', $consumer_key)
    ->param('redirect_uri', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/index.php?v=d&plugin=' . Plugin::ID . '&modal=callback')
    ->param('state', $state)
    ->param('grant_type', 'authorization_code')
    ->buildUrl();

header('Location: ' . $location);
?>
