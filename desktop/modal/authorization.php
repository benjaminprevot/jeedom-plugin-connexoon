<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

Config::unsetAccessToken();
Config::unsetRefreshToken();
Config::unsetTokenExists();

header('Location: ' . Somfy::getAuthUrl());
?>
