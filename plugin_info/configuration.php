<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
?>
<form class="form-horizontal">
    <fieldset>
        <?php
		if (version_compare(PHP_VERSION, '7.0') < 0) {
			echo '<div class="alert alert-danger">{{Attention votre version de PHP (' . PHP_VERSION . ') est trop veille, il faut au minimum PHP 7.0.}}</div>';
		}
		?>
    </fieldset>
</form>
