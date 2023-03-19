<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

if (version_compare(PHP_VERSION, '7.0') < 0) {
    echo '<div class="alert alert-danger">{{Attention votre version de PHP (' . PHP_VERSION . ') est trop veille, il faut au minimum PHP 7.0.}}</div>';
}

$token = config::byKey('somfy::token', 'benjaminprevotConnexoon');
?>
<?php if (empty($token)): ?>
    <form class="form-horizontal">
        <fieldset>
            <div class="form-group">
                <label class="col-sm-3 control-label">{{E-mail}}</label>
                <div class="col-sm-7">
                    <input class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">{{Mot de passe}}</label>
                <div class="col-sm-7">
                    <input type="password" class="form-control">
                </div>
            </div>
        </fieldset>
    </form>
<?php else: ?>
    <form class="form-horizontal">
        <fieldset>
            <div class="form-group">
                <label class="col-sm-3 control-label">{{Statut}}</label>
                <div class="col-sm-7">
                    <span class="label label-success">{{Configuration terminée}}</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">{{Action}}</label>
                <div class="col-sm-7">
                    <button type="button" class="btn btn-danger btn-sm"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span> {{Supprimer le token}}</button>
                </div>
            </div>
        </fieldset>
    </form>
<?php endif; ?>
