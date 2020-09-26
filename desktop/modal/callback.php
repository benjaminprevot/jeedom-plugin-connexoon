<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

if (!isset($_GET['code']) || !isset($_GET['state'])) {
    throw new Exception('{{400 - Paramètre manquant}}');
}

require_once __DIR__  . '/../../core/class/benjaminprevotConnexoon.class.php';

$code = $_GET['code'];
$state = $_GET['state'];

Somfy::getToken($code, $state);
?>
<script>window.close();</script>
