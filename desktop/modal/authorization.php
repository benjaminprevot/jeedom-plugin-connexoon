<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

config::remove('access_token', 'connexoon');
config::remove('refresh_token', 'connexoon');
config::remove('token_exists', 'connexoon');

$state = hash("sha256", rand());
config::save('consumer_state', $state, 'connexoon');

$consumer_key = config::byKey('consumer_key', 'connexoon');

header('Location: ' . connexoon::getUrl(
    'https://accounts.somfy.com/oauth/oauth/v2/auth',
    array(
        'response_type' => 'code',
        'client_id' => $consumer_key,
        'redirect_uri' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/index.php?v=d&plugin=connexoon&modal=callback',
        'state' => $state,
        'grant_type' => 'authorization_code'
    )
));
?>
