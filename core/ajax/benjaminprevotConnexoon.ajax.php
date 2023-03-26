<?php
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    ajax::init();

    if (init('action') == 'generate-token') {
        ajax::error('Work in progress');
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
