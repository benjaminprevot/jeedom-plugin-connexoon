<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

benjaminprevotConnexoon::removeConfig('access_token');
benjaminprevotConnexoon::removeConfig('refresh_token');
benjaminprevotConnexoon::removeConfig('token_exists');

$state = hash("sha256", rand());
benjaminprevotConnexoon::setConfig('consumer_state', $state);

$consumer_key = benjaminprevotConnexoon::getConfig('consumer_key');

header('Location: ' . benjaminprevotConnexoon::buildUrl(
    'https://accounts.somfy.com/oauth/oauth/v2/auth',
    array(
        'response_type' => 'code',
        'client_id' => $consumer_key,
        'redirect_uri' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/index.php?v=d&plugin=' . benjaminprevotConnexoon::ID . '&modal=callback',
        'state' => $state,
        'grant_type' => 'authorization_code'
    )
));
?>
