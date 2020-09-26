<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

require_once __DIR__  . '/../../core/class/benjaminprevotConnexoon.class.php';

ConnexoonConfig::unsetAccessToken();
ConnexoonConfig::unsetRefreshToken();
ConnexoonConfig::unsetTokenExists();

header('Location: ' . Somfy::getAuthUrl());
?>
