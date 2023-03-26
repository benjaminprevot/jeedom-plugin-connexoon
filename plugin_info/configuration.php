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
                <label class="col-md-4 control-label">{{IP Connexoon}}</label>
                <div class="col-md-4">
                    <input class="form-control configKey" id="connexoon-ip" data-l1key="somfy::ip" placeholder="{{Par ex. 192.168.1.10}}">
                </div>
            </div>
            <div class="form-group">
                <label class="col-md-4 control-label">{{Code PIN}}</label>
                <div class="col-md-4">
                    <input class="form-control configKey" id="connexoon-pin" data-l1key="somfy::pin" placeholder="{{Par ex. 1234-5678-9012}}">
                </div>
                <div class="col-md-4">
                    <a class="btn btn-info" id="connexoon-btn-test">Tester</a>
                </div>
            </div>
            <div class="form-group">
                <label class="col-md-4 control-label">{{E-mail}}</label>
                <div class="col-md-4">
                    <input class="form-control configKey" data-l1key="somfy::email" placeholder="{{E-mail de votre compte Somfy}}">
                </div>
            </div>
            <div class="form-group">
                <label class="col-md-4 control-label">{{Mot de passe}}</label>
                <div class="col-md-4">
                    <input type="password" class="form-control configKey" data-l1key="somfy::password">
                </div>
            </div>
        </fieldset>
    </form>
    <script>
        (function(window) {
            window.benjaminprevotConnexoon_postSaveConfiguration = function() {
                $.ajax({
                    type: 'POST',
                    url: "plugins/benjaminprevotConnexoon/core/ajax/benjaminprevotConnexoon.ajax.php",
                    data: { action: "generate-token" },
                    dataType: 'json',
                    error: function (request, status, error) {
                        handleAjaxError(request, status, error);
                    },
                    success: function (data) {
                        if (data.state == 'ok') {
                            $('#div_alert').showAlert({message: '{{Configuration terminée}} - ' + data.result, level: 'success'});
                        } else {
                            $('#div_alert').showAlert({message: data.result, level: 'danger'});
                        }
                    }
                });
            };

            $('#connexoon-btn-test').on('click', function() {
                $.ajax({
                    type: 'POST',
                    url: 'plugins/benjaminprevotConnexoon/core/ajax/benjaminprevotConnexoon.ajax.php',
                    data: { action: 'test', pin: $('#connexoon-pin').val(), ip: $('#connexoon-ip').val() },
                    dataType: 'json',
                    error: function (request, status, error) {
                        handleAjaxError(request, status, error);
                    },
                    success: function (data) {
                        if (data.state == 'ok') {
                            $('#div_alert').showAlert({message: '{{Test de connexion à la box réussi}} - {{Version : }}' + data.result, level: 'success'});
                        } else {
                            $('#div_alert').showAlert({message: data.result, level: 'danger'});
                        }
                    }
                });
            });
        })(window);
    </script>
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
                    <button type="button" class="btn btn-danger"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span> {{Supprimer le token}}</button>
                </div>
            </div>
        </fieldset>
    </form>
<?php endif; ?>
