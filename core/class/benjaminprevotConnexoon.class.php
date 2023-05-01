<?php
require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/../../3rdparty/somfy/Somfy.php';

class benjaminprevotConnexoon extends eqLogic {

    public static function cron() {
        self::syncDevices();
    }

    public static function deamon_info() {
        $return = array(
            'log' => __CLASS__,
            'state' => 'nok',
            'launchable' => 'nok'
        );

        $token = config::byKey('somfy::token', __CLASS__);

        if (!empty($token)) {
            $return['launchable'] = 'ok';
        }

        $cron = cron::byClassAndFunction(__CLASS__, 'fetchEvents');

        if (is_object($cron) && $cron->running()) {
            $return['state'] = 'ok';
        }

        return $return;
    }

    public static function deamon_start() {
        self::deamon_stop();

        $daemonInfo = self::deamon_info();

        if ($daemonInfo['launchable'] !== 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }

        log::add(__CLASS__, 'info', 'Lancement démon');

        $pin = config::byKey('somfy::pin', __CLASS__);
        $ip = config::byKey('somfy::ip', __CLASS__);
        $token = config::byKey('somfy::token', __CLASS__);

        $listenerId = \Somfy\Api::registerEventListener($pin, $ip, $token);

        config::save('somfy::listenerId', $listenerId, __CLASS__);

        $cron = new cron();
        $cron->setClass(__CLASS__);
        $cron->setFunction('fetchEvents');
        $cron->setEnable(1);
        $cron->setDeamon(1);
        $cron->setSchedule('* * * * *');
        $cron->save();
        $cron->run();

        return $cron->running();
    }

    public static function deamon_stop() {
        $cron = cron::byClassAndFunction(__CLASS__, 'fetchEvents');

        if (!is_object($cron)) {
            return;
        }

        $cron->stop();
        $cron->remove();

        config::remove('somfy::listenerId', __CLASS__);
    }

    public static function createDaemon() {
        $cron = cron::byClassAndFunction(__CLASS__, 'fetchEvents');

        if (is_object($cron)) {
            log::add(__CLASS__, 'info', 'Cron already exists with ID ' . $cron->getId() . '. Trying to recreate it.');

            if ($cron->running()) {
                $cron->stop();
            }

            $cron->remove();
        }

        $pin = config::byKey('somfy::pin', __CLASS__);
        $ip = config::byKey('somfy::ip', __CLASS__);
        $token = config::byKey('somfy::token', __CLASS__);

        $listenerId = \Somfy\Api::registerEventListener($pin, $ip, $token);

        config::save('somfy::listenerId', $listenerId, __CLASS__);

        $cron = new cron();
        $cron->setClass(__CLASS__);
        $cron->setFunction('fetchEvents');
        $cron->setEnable(1);
        $cron->setDeamon(1);
        $cron->setSchedule('* * * * *');
        $cron->save();

        self::startDaemon();
    }

    public static function startDaemon() {
        $cron = cron::byClassAndFunction(__CLASS__, 'fetchEvents');

        if (!is_object($cron)) {
            log::add(__CLASS__, 'warning', 'Trying to start non-existing cron.');

            throw new Exception('Trying to start non-existing cron.');
        }

        $cron->run();
    }

    public static function stopDaemon() {
        $cron = cron::byClassAndFunction(__CLASS__, 'fetchEvents');

        if (!is_object($cron)) {
            log::add(__CLASS__, 'warning', 'Trying to stop non-existing cron.');

            throw new Exception('Trying to stop non-existing cron.');
        }

        $cron->halt();
    }

    public static function fetchEvents() {
        $pin = config::byKey('somfy::pin', __CLASS__);
        $ip = config::byKey('somfy::ip', __CLASS__);
        $token = config::byKey('somfy::token', __CLASS__);
        $listenerId = config::byKey('somfy::listenerId', __CLASS__);

        $events = \Somfy\Api::fetchEvents($pin, $ip, $token, $listenerId);

        foreach ($events as $event) {
            $logicalId = $event['deviceURL'];
            $states = $event['states'];

            $eqLogic = self::byLogicalId($logicalId, __CLASS__);

            foreach ($states as $state) {
                $eqLogic->checkAndUpdateCmd($state['name'], $state['value']);
            }

            $eqLogic->refreshWidget();
        }
    }

    private static function syncDevices() {
        $pin = config::byKey('somfy::pin', __CLASS__);
        $ip = config::byKey('somfy::ip', __CLASS__);
        $token = config::byKey('somfy::token', __CLASS__);

        if (empty($token)) {
            return;
        }

        $devices = \Somfy\Api::devices($pin, $ip, $token);

        foreach ($devices as $device) {
            self::saveEqlogic($device);
        }
    }

    private static function saveEqlogic($device) {
        $logicalId = $device['deviceURL'];

        $eqLogic = self::byLogicalId($logicalId, __CLASS__);

        if (!is_object($eqLogic)) {
            $eqLogic = new benjaminprevotConnexoon();
            $eqLogic->setLogicalId($logicalId);
            $eqLogic->setEqType_name(__CLASS__);
            $eqLogic->setIsEnable((int) $device['enabled']);
            $eqLogic->setIsVisible(1);
        }

        $eqLogic->setName($device['name']);
        $eqLogic->setConfiguration('template', self::mapTemplateName($device));

        $eqLogic->save();
        $eqLogic->refresh();

        foreach ($device['commands'] as $command) {
            self::saveCommand($eqLogic, $device, $command);
        }

        foreach ($device['states'] as $state) {
            self::saveState($eqLogic, $device, $state);
        }

        $eqLogic->refreshWidget();
    }

    private static function mapTemplateName($device) {
        switch ($device['type']) {
            case \Somfy\Device::ROLLER_SHUTTER:
                return 'roller_shutter';
            default:
                return 'generic';
        }
    }

    private static function saveCommand($eqLogic, $device, $command) {
        $cmd = $eqLogic->getCmd(null, $command);

        if (!is_object($cmd)) {
            $cmd = new benjaminprevotConnexoonCmd();
            $cmd->setLogicalId($command);
        }

        $cmd->setName($command);
        $cmd->setGeneric_type(self::commandGenericTypeMapping($device['type'], $command));
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setUnite(null);
        $cmd->setEqLogic_id($eqLogic->getId());

        $cmd->save();
    }

    private static function saveState($eqLogic, $device, $state) {
        $cmd = $eqLogic->getCmd(null, $state['name']);

        if (!is_object($cmd)) {
            $cmd = new benjaminprevotConnexoonCmd();
            $cmd->setLogicalId($state['name']);
        }

        $cmd->setName($state['name']);
        $cmd->setGeneric_type(self::stateGenericTypeMapping($device['type'], $state));
        $cmd->setType('info');
        $cmd->setSubType(self::stateSubTypeMapping($state['type']));
        $cmd->setUnite(self::stateUnitMapping($state['type']));
        $cmd->setEqLogic_id($eqLogic->getId());

        $cmd->save();

        $eqLogic->checkAndUpdateCmd($state['name'], $state['value']);
    }

    private static function commandGenericTypeMapping($deviceType, $command) {
        if ($deviceType === \Somfy\Device::ROLLER_SHUTTER) {
            switch ($command) {
                case 'close': return 'FLAP_DOWN';
                case 'open':  return 'FLAP_UP';
                case 'stop':  return 'FLAP_STOP';
            }
        }

        return null;
    }

    private static function stateGenericTypeMapping($deviceType, $state) {
        if ($deviceType === \Somfy\Device::ROLLER_SHUTTER) {
            switch ($state['name']) {
                case 'closure': return 'FLAP_STATE';
            }
        }

        return null;
    }

    private static function stateSubTypeMapping($stateType) {
        switch ($stateType) {
            case 'boolean': return 'binary';
            case 'percent': return 'numeric';
        }

        return null;
    }

    private static function stateUnitMapping($stateType) {
        switch ($stateType) {
            case 'percent': return '%';
        }

        return null;
    }

    public function toHtml($_version = 'dashboard') {
        $replace = $this->preToHtml($_version);

        if (!is_array($replace)) {
            return $replace;
        }

        $version = jeedom::versionAlias($_version);

        if ($this->getDisplay('hideOn' . $version) == 1) {
            return '';
        }

        foreach ($this->getCmd('action') as $cmd) {
            $replace['#action_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
        }

        foreach ($this->getCmd('info') as $cmd) {
            $replace['#info_' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
        }

        return template_replace($replace, getTemplate('core', $version, $this->getConfiguration('template'), __CLASS__));
    }

}

class benjaminprevotConnexoonCmd extends cmd {

    public function execute($options = array()) {
        if ($this->getType() == '') {
            return;
        }

        $eqLogic = $this->getEqLogic();
        $action = $this->getLogicalId();

        $pin = config::byKey('somfy::pin', 'benjaminprevotConnexoon');
        $ip = config::byKey('somfy::ip', 'benjaminprevotConnexoon');
        $token = config::byKey('somfy::token', 'benjaminprevotConnexoon');

        if ($this->getType() == 'action') {
            \Somfy\Api::execute($pin, $ip, $token, $eqLogic->getLogicalId(), $action);
        }
    }

}
