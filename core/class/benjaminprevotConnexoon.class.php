<?php
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class benjaminprevotConnexoon extends eqLogic {

    /**
     * HTTP utility functions.
     */
    private static function saveToken($json) {
        if (isset($json['access_token']) && isset($json['refresh_token'])) {
            Config::set('access_token', $json['access_token']);
            Config::set('refresh_token', $json['refresh_token']);
            Config::set('token_exists', 'true');
        } else {
            Config::set('token_exists', 'false');
        }
    }

  public static function refreshToken() {
    $json = Somfy::refreshToken();

    self::saveToken($json);
  }

  public static function getAndSaveToken($code, $state) {
    Logger::debug('Getting access token');

    $json = HttpRequest::get('https://accounts.somfy.com/oauth/oauth/v2/token')
        ->param('client_id', Config::get('consumer_key'))
        ->param('client_secret', Config::get('consumer_secret'))
        ->param('grant_type', 'authorization_code')
        ->param('code', $code)
        ->param('redirect_uri', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/index.php?v=d&plugin=' . Plugin::ID . '&modal=callback')
        ->param('state', $state)
        ->send(HttpRequest::RESPONSE_JSON_ARRAY);

    self::saveToken($json);
  }

  public static function callApi($url, $limit = 5) {
    Logger::debug('Calling API: ' . $url . ' (' . $limit . ')');

    if ($limit < 1) {
      return json_decode("{}", true);
    }

    $json = HttpRequest::get($url)
        ->header('Authorization', 'Bearer ' . Config::get('access_token'))
        ->header('Content-Type', 'application/json')
        ->send(HttpRequest::RESPONSE_JSON_ARRAY);

    if (isset($json['fault'])
            && isset($json['fault']['detail'])
            && isset($json['fault']['detail']['errorcode'])
            && $json['fault']['detail']['errorcode'] == 'keymanagement.service.access_token_expired') {
        self::refreshToken();

        return self::callApi($url, $limit - 1);
    }

    return $json;
  }

    public static function sync() {
        Logger::debug('Refreshing devices');

        $sites = self::getSites();

        foreach ($sites as $site) {
            if (isset($site['id'])) {
                $devices = self::getDevices($site['id']);

                $capabilityNameFunc = function($capability) {
                    return $capability['name'];
                };

                foreach ($devices as $device) {
                    if (in_array('roller_shutter', $device['categories'])) {
                        $logicalId = $device['id'];

                        Logger::debug('Synching ' . $logicalId);

                        $benjaminprevotConnexoon = benjaminprevotConnexoon::byLogicalId($logicalId, Plugin::ID);

                        if (!is_object($benjaminprevotConnexoon)) {
                            $benjaminprevotConnexoon = new benjaminprevotConnexoon();
                        }

                        $benjaminprevotConnexoon->setConfiguration('type', 'roller_shutter');
                        $benjaminprevotConnexoon->setConfiguration('actions', implode('|', array_map($capabilityNameFunc, $device['capabilities'])));
                        $benjaminprevotConnexoon->setLogicalId($logicalId);
                        $benjaminprevotConnexoon->setName($device['name']);
                        $benjaminprevotConnexoon->setEqType_name(Plugin::ID);
                        $benjaminprevotConnexoon->setIsVisible(1);
                        $benjaminprevotConnexoon->setIsEnable($device['available'] == 'true' ? 1 : 0);
                        $benjaminprevotConnexoon->save();
                        $benjaminprevotConnexoon->refresh();
                    }
                }
            }
        }
    }

    public static function cron5() {
        self::sync();
    }

    public static function cron30() {
        self::refreshToken();
    }

    public static function getSites() {
        Logger::debug('Getting sites list');

        return self::callApi('https://api.somfy.com/api/v1/site');
    }

    public static function getDevices($siteId) {
        Logger::debug('Getting devices list for site ' . $siteId);

        return self::callApi('https://api.somfy.com/api/v1/site/' . $siteId . '/device');
    }

    private function addCommand($logicalId, $name, $genericType, $type = 'action', $subType = 'other', $unite = null) {
        $cmd = $this->getCmd(null, $logicalId);
        
        if (!is_object($cmd)) {
            $cmd = new benjaminprevotConnexoonCmd();
            $cmd->setLogicalId($logicalId);
        }

        $cmd->setName(__($name, __FILE__));
        $cmd->setGeneric_type($genericType);
        $cmd->setType($type);
        $cmd->setSubType($subType);
        $cmd->setUnite($unite);
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();
    }

  public function action($action) {
    return HttpRequest::post('https://api.somfy.com/api/v1/device/' . $this->getLogicalId() . '/exec')
        ->header('Authorization', 'Bearer ' . Config::get('access_token'))
        ->header('Content-Type', 'application/json')
        ->content('{ "name": "' . $action . '", "parameters": [] }')
        ->send(HttpRequest::RESPONSE_JSON_ARRAY);
  }

    public function refresh() {
        $device = self::callApi('https://api.somfy.com/api/v1/device/' . $this->getLogicalId());

        foreach ($device['states'] as $state) {
            if ($state['name'] == 'position') {
                $this->checkAndUpdateCmd('position', max(0, $state['value']));

                break;
            }
        }

        $this->refreshWidget();
    }

    public function postSave() {
        // Action
        $actions = explode('|', $this->getConfiguration('actions', ''));

        if (in_array('open', $actions)) {
            $this->addCommand('open', 'Ouvrir', 'ROLLER_OPEN');
        }

        if (in_array('close', $actions)) {
            $this->addCommand('close', 'Fermer', 'ROLLER_CLOSE');
        }

        if (in_array('identify', $actions)) {
            $this->addCommand('identify', 'Identifier', 'ROLLER_IDENTIFY');
        }

        if (in_array('stop', $actions)) {
            $this->addCommand('stop', 'Stop', 'ROLLER_STOP');
        }

        $this->addCommand('refresh', 'Rafraîchir', 'ROLLER_REFRESH');

        // Info
        $this->addCommand('position', 'Position', 'ROLLER_POSITION', 'info', 'numeric', '%');
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

        foreach ($this->getCmd('info') as $cmd) {
            $replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
        }

        foreach ($this->getCmd('action') as $cmd) {
            $replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
            $replace['#' . $cmd->getLogicalId() . '_hide#'] = $cmd->getIsVisible() ? '' : 'display:none;';
        }
        
        return template_replace($replace, getTemplate('core', $version, 'eqLogic', Plugin::ID));
    }

}

class benjaminprevotConnexoonCmd extends cmd {

    public function execute($_options = array()) {
        if ($this->getType() == '') {
            return;
        }

        $eqLogic = $this->getEqLogic();
        $action= $this->getLogicalId();

        if ($this->getType() == 'action') {
            if ($action == 'refresh') {
                $eqLogic->refresh();
            } else {
                $eqLogic->action($action);
            }
        }
    }

}
