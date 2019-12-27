<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

config::remove('access_token', 'benjaminprevotConnexoon');
config::remove('refresh_token', 'benjaminprevotConnexoon');
config::remove('token_exists', 'benjaminprevotConnexoon');

$state = hash("sha256", rand());
config::save('consumer_state', $state, 'benjaminprevotConnexoon');

$consumer_key = config::byKey('consumer_key', 'benjaminprevotConnexoon');

header('Location: ' . benjaminprevotConnexoon::buildUrl(
    'https://accounts.somfy.com/oauth/oauth/v2/auth',
    array(
        'response_type' => 'code',
        'client_id' => $consumer_key,
        'redirect_uri' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/index.php?v=d&plugin=benjaminprevotConnexoon&modal=callback',
        'state' => $state,
        'grant_type' => 'authorization_code'
    )
));
?>
