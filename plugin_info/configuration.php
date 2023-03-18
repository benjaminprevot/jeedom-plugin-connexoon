<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

include_file('core', 'authentification', 'php');

if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>
<div class="alert alert-danger" role="alert">Le plugin est en cours de réécriture suite aux changements imposés par Somfy.</div>
