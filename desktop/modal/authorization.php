<?php
if (!isConnect('admin'))
{
    throw new Exception('{{401 - Accès non autorisé}}');
}

if (!isset($_GET['configuration']))
{
    throw new Exception('{{400 - Paramètre configuration manquant}}');
}

require_once __DIR__  . '/../../core/class/benjaminprevotConnexoon.class.php';

$configuration = $_GET['configuration'];

ConnexoonConfig::addConfiguration($configuration);

header('Location: ' . Somfy::getAuthUrl($configuration));
?>
