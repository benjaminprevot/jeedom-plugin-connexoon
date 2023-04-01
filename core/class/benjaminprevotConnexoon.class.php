<?php
require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/../../3rdparty/somfy/Somfy.class.php';

class benjaminprevotConnexoon extends eqLogic {

    public static function cron() {
        self::syncDevices();
    }

    private static function syncDevices() {
        $token = config::byKey('somfy::token', __CLASS__);
        $pin = config::byKey('somfy::pin', __CLASS__);
        $ip = config::byKey('somfy::ip', __CLASS__);

        if (empty($token)) {
            return;
        }

        $devices = Somfy::devices($pin, $ip, $token);

        foreach ($devices as $device) {
            $logicalId = $device['deviceURL'];

            $eqLogic = self::byLogicalId($logicalId, __CLASS__);

            if (!is_object($eqLogic)) {
                $eqLogic = new benjaminprevotConnexoon();
                $eqLogic->setLogicalId($logicalId);
                $eqLogic->setName($device['name']);
                $eqLogic->setEqType_name(__CLASS__);
            }

            $eqLogic->save();
            $eqLogic->refresh();
        }
    }

}

class benjaminprevotConnexoonCmd extends cmd {

    public function execute($options = array()) {
    }

}
