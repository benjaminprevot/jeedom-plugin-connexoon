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
$isConfigured = !empty($token);
$disabledIfConfigured = $isConfigured ? 'disabled' : '';
$hiddenIfConfigured = $isConfigured ? 'style="display:none"' : '';
$displayedIfConfigured = $isConfigured ? '' : 'style="display:none"';
?>
<form class="form-horizontal">
    <fieldset <?= $disabledIfConfigured ?>>
        <?php if ($isConfigured): ?>
            <div class="alert alert-success" role="alert">{{Configuration terminée}}</div>
        <?php endif ?>
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
    <fieldset>
        <div class="form-group">
            <div class="col-md-4 col-md-offset-4">
                <a class="btn btn-info" id="connexoon-btn-test" <?= $hiddenIfConfigured ?>>Tester</a>
                <a class="btn btn-danger" id="connexoon-btn-reset" <?= $displayedIfConfigured ?>>Reset</a>
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
                        $('div.pluginDisplayCard[data-plugin_id="benjaminprevotConnexoon"]').click();
                    } else {
                        $('#div_alert').showAlert({message: data.result, level: 'danger'});
                    }
                }
            });
        };

        $('#connexoon-btn-reset').on('click', function() {
            $.ajax({
                type: 'POST',
                url: 'plugins/benjaminprevotConnexoon/core/ajax/benjaminprevotConnexoon.ajax.php',
                data: { action: 'reset' },
                dataType: 'json',
                error: function (request, status, error) {
                    handleAjaxError(request, status, error);
                },
                success: function (data) {
                    if (data.state == 'ok') {
                        $('#div_alert').showAlert({message: '{{Reset}}', level: 'success'});
                    } else {
                        $('#div_alert').showAlert({message: data.result, level: 'danger'});
                    }
                }
            });
        });

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
