<?php
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    require_once dirname(__FILE__) . '/../../3rdparty/somfy/Overkiz.class.php';

    ajax::init();

    if (init('action') == 'generate-token') {
        $pin = config::byKey('somfy::pin', 'benjaminprevotConnexoon');
        $email = config::byKey('somfy::email', 'benjaminprevotConnexoon');
        $password = config::byKey('somfy::password', 'benjaminprevotConnexoon');

        try {
            $jsessionid = Overkiz::login($email, $password);

            Overkiz::generateToken($pin, $jsessionid);

            ajax::success();
        } catch (Exception $e) {
            ajax::error($e->getMessage());
        }
    }

    if (init('action') == 'test') {
        try {
            $version = benjaminprevotConnexoon::testHost(init('pin'), init('ip'));

            ajax::success($version);
        } catch (Exception $e) {
            ajax::error($e->getMessage());
        }
    }

    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
}
catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
