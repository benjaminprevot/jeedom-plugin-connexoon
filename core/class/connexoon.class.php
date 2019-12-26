<?php
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class connexoon extends eqLogic
{
    /**
     * Logging methods.
     */
    private static function log($level, $message)
    {
        log::add('connexoon', $level, $message);
    }

    public static function logDebug($message)
    {
        self::log('debug', $message);
    }

    public static function logInfo($message)
    {
        self::log('info', $message);
    }

    public static function logWarn($message)
    {
        self::log('warning', $message);
    }

    public static function logError($message)
    {
        self::log('error', $message);
    }

    /**
     * Configuration methods.
     */
    public static function getConfig($key)
    {
        return config::byKey($key, 'connexoon');
    }

    public static function setConfig($key, $value)
    {
        config::save($key, $value, 'connexoon');
    }

    private static function httpPost($url, $params = array(), $headers = array())
    {
        $requestUrl = self::getUrl($url, $params);

        self::logDebug('Calling "' . $requestUrl . '"');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::getUrl($url, $params));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        self::logDebug('Response code: ' . $httpCode);
        self::logDebug('Response body: ' . $response);

        if ($httpCode == 200) {
            return $response;
        }

        return false;
    }

    private static function getJson($url, $params = array(), $headers = array())
    {
        return json_decode(self::httpPost($url, $params, $headers), true);
    }

    private static function saveToken($json)
    {
        if (isset($json['access_token']) && isset($json['refresh_token']))
        {
            self::setConfig('access_token', $json['access_token']);
            self::setConfig('refresh_token', $json['refresh_token']);
            self::setConfig('token_exists', 'true');
        }
        else
        {
            self::setConfig('token_exists', 'false');
        }
    }

    public static function callApi($url, $limit = 5)
    {
        self::logDebug('Calling API: ' . $url . ' (' . $limit . ')');

        if ($limit < 1) {
            return json_decode("{}", true);
        }

        $json = self::getJson(
            $url,
            array(),
            array(
                'Authorization: Bearer ' . self::getConfig('access_token'),
                'Content-Type: application/json'
            )
        );

        if (isset($json['fault'])
            && isset($json['fault']['detail'])
            && isset($json['fault']['detail']['errorcode'])
            && $json['fault']['detail']['errorcode'] == 'keymanagement.service.access_token_expired')
        {
            self::refreshToken();
        }

        return $json;
    }

    public static function sync()
    {
        self::logDebug('Refreshing devices');

        $sites = self::getSites();

        foreach ($sites as $site)
        {
            if (isset($site['id']))
            {
                $devices = self::getDevices($site['id']);

                $capabilityNameFunc = function($capability) {
                    return $capability['name'];
                };

                foreach ($devices as $device)
                {
                    if (in_array('roller_shutter', $device['categories']))
                    {
                        $logicalId = $device['id'];

                        self::logDebug('Synching ' . $logicalId);

                        $connexoon = connexoon::byLogicalId($logicalId, 'connexoon');

                        if (!is_object($connexoon))
                        {
                            $connexoon = new connexoon();
                        }

                        $connexoon->setConfiguration('type', 'roller_shutter');
                        $connexoon->setConfiguration('actions', implode('|', array_map($capabilityNameFunc, $device['capabilities'])));
                        $connexoon->setLogicalId($logicalId);
                        $connexoon->setName($device['name']);
                        $connexoon->setEqType_name('connexoon');
                        $connexoon->setIsVisible(1);
                        $connexoon->setIsEnable($device['available'] == 'true' ? 1 : 0);
                        $connexoon->save();
                        $connexoon->refresh();
                    }
                }
            }
        }
    }

    public static function cron5()
    {
        self::sync();
    }

    public static function cron30()
    {
        self::refreshToken();
    }

    public static function refreshToken()
    {
        self::logDebug('Refreshing token');

        $json = self::getJson(
            'https://accounts.somfy.com/oauth/oauth/v2/token',
            array(
                'client_id' => self::getConfig('consumer_key'),
                'client_secret' => self::getConfig('consumer_secret'),
                'grant_type' => 'refresh_token',
                'refresh_token' => self::getConfig('refresh_token')
            ));

        self::saveToken($json);
    }

    public static function getUrl($url, $params = array())
    {
        return $url . '?' . http_build_query($params);
    }

    public static function getAndSaveToken($code, $state)
    {
        self::logDebug('Getting access token');

        $consumer_key = self::getConfig('consumer_key');
        $consumer_secret = self::getConfig('consumer_secret');

        $json = self::getJson(
            'https://accounts.somfy.com/oauth/oauth/v2/token',
            array(
                'client_id' => $consumer_key,
                'client_secret' => $consumer_secret,
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/index.php?v=d&plugin=connexoon&modal=callback',
                'state' => $state
            )
        );

        self::saveToken($json);
    }

    public static function getSites()
    {
        self::logDebug('Getting sites list');

        return self::callApi('https://api.somfy.com/api/v1/site');
    }

    public static function getDevices($siteId)
    {
        self::logDebug('Getting devices list for site ' . $siteId);

        return self::callApi('https://api.somfy.com/api/v1/site/' . $siteId . '/device');
    }

    private function addCommand($logicalId, $name, $genericType, $type = 'action', $subType = 'other', $unite = null)
    {
        $cmd = $this->getCmd(null, $logicalId);
        
        if (!is_object($cmd)) {
            $cmd = new connexoonCmd();
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

    public function postSave()
    {
        // Action
        $actions = explode('|', $this->getConfiguration('actions', ''));

        if (in_array('open', $actions)) {
            $this->addCommand('open', 'Open', 'ROLLER_OPEN');
        }

        if (in_array('close', $actions)) {
            $this->addCommand('close', 'Close', 'ROLLER_CLOSE');
        }

        if (in_array('identify', $actions)) {
            $this->addCommand('identify', 'Identify', 'ROLLER_IDENTIFY');
        }

        if (in_array('stop', $actions)) {
            $this->addCommand('stop', 'Stop', 'ROLLER_STOP');
        }

        $this->addCommand('refresh', 'Refresh', 'ROLLER_REFRESH');

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
        
        return template_replace($replace, getTemplate('core', $version, 'eqLogic', 'connexoon'));
    }
}

class connexoonCmd extends cmd
{
    private function deviceAction($eqLogic, $action)
    {
        $url = 'https://api.somfy.com/api/v1/device/' . $eqLogic->getLogicalId() . '/exec';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . connexoon::getConfig('access_token'),
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{ "name": "' . $action . '", "parameters": [] }' ); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            return $response;
        }

        connexoon::logError('Error while calling "' . $url . '": ' . $httpCode);

        return false;
    }

    public function execute($_options = array())
    {
        if ($this->getType() == '') {
            return;
        }

        $eqLogic = $this->getEqLogic();
        $action= $this->getLogicalId();

        if ($this->getType() == 'action') {
            if ($action == 'refresh') {
                $eqLogic->refresh();
            } else {
                $this->deviceAction($eqLogic, $action);
            }
        }
    }
}
