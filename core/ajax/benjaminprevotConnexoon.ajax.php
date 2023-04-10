<?php
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    require_once __DIR__ . '/../../3rdparty/somfy/Overkiz.class.php';
    require_once __DIR__ . '/../../3rdparty/somfy/Somfy.class.php';

    ajax::init();

    if (init('action') == 'generate-token') {
        $pin = config::byKey('somfy::pin', 'benjaminprevotConnexoon');
        $email = config::byKey('somfy::email', 'benjaminprevotConnexoon');
        $password = config::byKey('somfy::password', 'benjaminprevotConnexoon');

        config::remove('somfy::password', 'benjaminprevotConnexoon');

        try {
            $jsessionid = Overkiz::login($email, $password);

            $token = Overkiz::generateToken($pin, $jsessionid);

            Overkiz::activateToken($pin, $jsessionid, $token);

            $password = config::save('somfy::token', $token, 'benjaminprevotConnexoon');

            ajax::success();
        } catch (Exception $e) {
            ajax::error($e->getMessage());
        }
    }

    if (init('action') == 'reset') {
        config::remove('somfy::token', 'benjaminprevotConnexoon');

        ajax::success();
    }

    if (init('action') == 'test') {
        try {
            $version = Somfy::apiVersion(init('pin'), init('ip'));

            ajax::success($version);
        } catch (Exception $e) {
            ajax::error($e->getMessage());
        }
    }

    if (init('action') == 'create-deamon') {
        try {
            benjaminprevotConnexoon::createDeamon();

            ajax::success();
        } catch (Exception $e) {
            ajax::error($e->getMessage());
        }
    }

    if (init('action') == 'start-deamon') {
        try {
            benjaminprevotConnexoon::startDeamon();

            ajax::success();
        } catch (Exception $e) {
            ajax::error($e->getMessage());
        }
    }

    if (init('action') == 'stop-deamon') {
        try {
            benjaminprevotConnexoon::stopDeamon();

            ajax::success();
        } catch (Exception $e) {
            ajax::error($e->getMessage());
        }
    }

    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
}
catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
