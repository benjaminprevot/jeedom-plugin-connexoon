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

header('Location: ' . benjaminprevotConnexoon::buildUrl(
    'https://accounts.somfy.com/oauth/oauth/v2/auth',
    array(
        'response_type' => 'code',
        'client_id' => $consumer_key,
        'redirect_uri' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/index.php?v=d&plugin=' . Plugin::ID . '&modal=callback',
        'state' => $state,
        'grant_type' => 'authorization_code'
    )
));
?>
