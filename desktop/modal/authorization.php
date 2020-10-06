<?php
if (!isConnect('admin'))
{
    throw new Exception('{{401 - Accès non autorisé}}');
}

if (!isset($_GET['callbackUrl']))
{
    throw new Exception('{{400 - Paramètre callbackUrl manquant}}');
}

require_once __DIR__  . '/../../core/class/benjaminprevotConnexoon.class.php';

ConnexoonConfig::unsetAccessToken();
ConnexoonConfig::unsetRefreshToken();
ConnexoonConfig::unsetTokenExists();
ConnexoonConfig::set('callback_url', $_GET['callbackUrl']);

header('Location: ' . Somfy::getAuthUrl());
?>
