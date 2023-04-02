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

            $eqLogic->setIsEnable((int) $device['enabled']);
            $eqLogic->save();
            $eqLogic->refresh();

            foreach ($device['actions'] as $action) {
                $cmd = $eqLogic->getCmd(null, $action);

                if (!is_object($cmd)) {
                    $cmd = new benjaminprevotConnexoonCmd();
                    $cmd->setLogicalId($action);
                }

                $cmd->setName($action);
                //$cmd->setGeneric_type($genericType);
                $cmd->setType('action');
                $cmd->setSubType('other');
                $cmd->setUnite(null);
                $cmd->setEqLogic_id($eqLogic->getId());

                $cmd->save();
            }
        }
    }

}

class benjaminprevotConnexoonCmd extends cmd {

    public function execute($options = array()) {
    }

}
