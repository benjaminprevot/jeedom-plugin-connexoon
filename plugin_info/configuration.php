<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../core/class/benjaminprevotConnexoon.class.php';

include_file('core', 'authentification', 'php');

if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>
<form class="form-horizontal">
    <fieldset>
        <div class="form-group">
            <label class="col-sm-2 control-label">{{Callback URL}}</label>
            <div class="col-sm-10">
                <input type="text" class="configKey form-control" value="<?php echo Somfy::getRedirectUri(); ?>" readonly />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">{{Consumer Key}}</label>
            <div class="col-sm-10">
                <input type="text" class="configKey form-control" id="text-connexoon-consumer-key" data-l1key="consumer_key" placeholder="{{Consumer Key}}" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">{{Consumer Secret}}</label>
            <div class="col-sm-10">
                <input type="text" class="configKey form-control" id="text-connexoon-consumer-secret" data-l1key="consumer_secret" placeholder="{{Consumer Secret}}" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">{{Token exists}}</label>
            <div class="col-sm-3">
                <?php if (config::byKey('token_exists', 'benjaminprevotConnexoon', 'false') === 'false'): ?>
                    <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                <?php else: ?>
                    <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label">{{Synchroniser}}</label>
            <div class="col-lg-2">
                <a class="btn btn-warning" id="bt_syncConnexoon"><i class='fa fa-refresh'></i> {{Synchroniser mes équipements}}</a>
            </div>
        </div>
    </fieldset>
</form>
<script>
    $('#bt_savePluginConfig').on('click', function(e) {
        window.open('index.php?v=d&plugin=benjaminprevotConnexoon&modal=authorization', '{{Authorization}}', 'directories=no,menubar=no,status=no,location=no,fullscreen=yes');
    });

    $('#bt_syncConnexoon').on('click', function () {
        $.ajax({
            type: "POST",
            url: "plugins/benjaminprevotConnexoon/core/ajax/benjaminprevotConnexoon.ajax.php",
            data: {
                action: "sync",
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
                if (data.state != 'ok') {
                    $('#div_alert').showAlert({message: data.result, level: 'danger'});
                    return;
                }
                $('#div_alert').showAlert({message: '{{Synchronisation réussie}}', level: 'success'});
            }
        });
    });
</script>
