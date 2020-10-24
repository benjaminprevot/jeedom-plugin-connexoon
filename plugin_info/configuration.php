<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../core/class/benjaminprevotConnexoon.class.php';

include_file('core', 'authentification', 'php');

if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}

$configurations = ConnexoonConfig::getConfigurations();

function fieldset($configuration, $host, $callbackUrl)
{
    return "
<fieldset class=\"panel configuration\" data-configuration=\"${configuration}\" data-configuration_callback_url=\"${callbackUrl}\">
    <legend>${host}</legend>
    <div class=\"form-group\">
        <label class=\"col-sm-2 control-label\">{{Callback URL}}</label>
        <div class=\"col-sm-10\">
            <input type=\"text\" class=\"configKey form-control\" data-l1key=\"${configuration}_callback_url\" value=\"${callbackUrl}\" readonly />
        </div>
    </div>
    <div class=\"form-group\">
        <label class=\"col-sm-2 control-label\">{{Consumer Key}}</label>
        <div class=\"col-sm-10\">
            <input type=\"text\" class=\"configKey form-control input--updatable\" data-l1key=\"${configuration}_consumer_key\" placeholder=\"{{Consumer Key}}\" readonly />
        </div>
    </div>
    <div class=\"form-group\">
        <label class=\"col-sm-2 control-label\">{{Consumer Secret}}</label>
        <div class=\"col-sm-10\">
            <input type=\"text\" class=\"configKey form-control input--updatable\" data-l1key=\"${configuration}_consumer_secret\" placeholder=\"{{Consumer Secret}}\" readonly />
        </div>
    </div>
    <div class=\"form-group token\">
        <label class=\"col-sm-2 control-label\">{{Token exists}}</label>
        <div class=\"col-sm-3\">
            <input type=\"hidden\" class=\"configKey form-control token__value\" data-l1key=\"${configuration}_token_exists\" value=\"false\" readonly />
            <span class=\"glyphicon glyphicon-remove token__status token__status--absent\" aria-hidden=\"true\"></span>
            <span class=\"glyphicon glyphicon-ok token__status token__status--present\" aria-hidden=\"true\"></span>
        </div>
    </div>
    <div class=\"form-group enabled\">
        <div class=\"col-sm-12\">
            <input type=\"hidden\" class=\"configKey form-control enabled__value\" data-l1key=\"${configuration}_enabled\" value=\"true\" readonly />
            <a class=\"btn btn-danger enabled__action enabled__action--delete\"><i class=\"fa fa-times\"></i> {{Supprimer cette configuration}}</a>
            <a class=\"btn btn-success enabled__action enabled__action--restore\"><i class=\"fa fa-check\"></i> {{Restaurer cette configuration}}</a>
        </div>
    </div>
</fieldset>";
}
?>
<form class="form-horizontal connexoon__configurations">
    <fieldset class="group__add">
        <div class="form-group">
            <div class="col-lg-2">
                <a class="btn btn-success button__add"><i class="fa fa-plus"></i> {{Ajouter la configuration courante}}</a>
            </div>
        </div>
    </fieldset>
    <?php foreach ($configurations as $configuration): ?>
        <?php
        $callbackUrl = ConnexoonConfig::get("${configuration}_callback_url");
        $host = preg_replace('#/index\.php.*$#', '', $callbackUrl);

        echo fieldset($configuration, $host, $callbackUrl);
        ?>
    <?php endforeach; ?>
    <fieldset>
        <div class="form-group">
            <div class="col-lg-2">
                <a class="btn btn-warning button__sync"><i class="fa fa-refresh"></i> {{Synchroniser mes équipements}}</a>
            </div>
        </div>
    </fieldset>
</form>
<script>
    (function(window, $) {
        var host = window.location.href.replace(/\/index\.php.*$/, '');
        var callbackUrl = host + '/index.php?v=d&plugin=<?php echo Connexoon::ID ?>&modal=callback';

        var $form = $('.connexoon__configurations');
        var $fieldsets = $form.find('fieldset[data-configuration]');
        var $currentFieldset = $fieldsets.filter('[data-configuration_callback_url="' + callbackUrl + '"]');
        var $buttonAdd = $form.find('.button__add');
        var $buttonSync = $form.find('.button__sync');
        var $alert = $('#div_alert');

        // TO BE REMOVED
        $('#configuration--callback_url').value(callbackUrl);

        function setAddMode() {
            $currentFieldset
                .addClass('configuration--current')
                .find('.input--updatable')
                .prop('readonly', false);
            
            $form.prepend($currentFieldset)
                .addClass('connexoon__configurations--add');
        }

        function buildFieldset(configuration, host, callbackUrl) {
            return $('<?php echo str_replace("\n", "\\\n", fieldset('$configuration', '$host', '$callbackUrl')); ?>'
                    .replaceAll('$configuration', configuration)
                    .replaceAll('$host', host)
                    .replaceAll('$callbackUrl', callbackUrl));
        }

        if ($currentFieldset.length == 1) {
            setAddMode();
        }

        $form.on('click', '.enabled .enabled__action', function() {
            var enabled = $(this).hasClass('enabled__action--restore');

            $(this).prevAll('.enabled__value').val(enabled ? 'true' : 'false');

            $(this).parents('.configuration')[enabled ? 'removeClass' : 'addClass']('configuration--disabled');
        });

        $buttonAdd.on('click', function() {
            $currentFieldset = buildFieldset('' + new Date().getTime(), host, callbackUrl);

            setAddMode();
        });

        $buttonSync.on('click', function () {
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
                success: function (data) {
                    if (data.state == 'ok') {
                        $alert.showAlert({message: '{{Synchronisation réussie}}', level: 'success'});
                    } else {
                        $alert.showAlert({message: data.result, level: 'danger'});
                    }
                }
            });
        });

        window.benjaminprevotConnexoon_postSaveConfiguration = function() {
            if ($currentFieldset.filter(':not(.configuration--disabled)').find('.token__value').val() == 'false') {
                window.open('index.php?v=d&plugin=benjaminprevotConnexoon&modal=authorization&configuration=' + $currentFieldset.data('configuration'), '{{Authorization}}', 'directories=no,menubar=no,status=no,location=no,fullscreen=yes');
            }
        }
    })(window, $);
</script>
<style>
    .connexoon__configurations.connexoon__configurations--add .group__add {
        display: none;
    }

    .connexoon__configurations .configuration {
        border: 1px solid var(--txt-color);
        margin-bottom: var(--lineheight);
        padding: var(--lineheight);
    }

    .connexoon__configurations .configuration.configuration--disabled .form-group:not(.enabled) {
        display: none;
    }

    .connexoon__configurations .configuration legend {
        border: none;
        display: inline;
        padding: 0 var(--lineheight);
        width: auto;
    }

    .connexoon__configurations .configuration legend::first-letter {
        text-transform: lowercase;
    }

    .connexoon__configurations .token .token__status {
        display: none;
    }

    .connexoon__configurations .token .token__value[value='false'] ~ .token__status.token__status--absent,
    .connexoon__configurations .token .token__value[value='true'] ~ .token__status.token__status--present {
        display: inline-block;
    }

    .connexoon__configurations .enabled .enabled__action {
        display: none;
    }

    .connexoon__configurations .enabled .enabled__value[value='false'] ~ .enabled__action.enabled__action--restore,
    .connexoon__configurations .enabled .enabled__value:not([value='false']) ~ .enabled__action.enabled__action--delete {
        display: inline-block;
    }
</style>
