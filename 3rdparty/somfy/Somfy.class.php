<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');

if (!isConnect('admin')) {
    throw new Exception(__('401 - Accès non autorisé', __FILE__));
}

class Somfy {

    public static function apiVersion($pin, $ip) {
        $ch = curl_init("https://$pin.local:8443/enduser-mobile-web/1/enduserAPI/apiVersion");
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/../../3rdparty/somfy/overkiz-root-ca-2048.crt');
        curl_setopt($ch, CURLOPT_RESOLVE, array("$pin.local:8443:$ip"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $error = curl_error($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode == 200) {
            return json_decode($response, true)['protocolVersion'];
        }

        $errorFormat = 'Impossible de charger les données pour la gateway %s : IP = %s - HTTP code = %s - Response = %s - Error = %s';

        $logMessage = sprintf($errorFormat, $pin, $ip, $httpCode, $response, $error);
        $errorMessage = sprintf(__($errorFormat, __FILE__), $pin, $ip, $httpCode, $response, $error);

        log::add('benjaminprevotConnexoon', 'error', $logMessage);

        throw new Exception($errorMessage);
    }

    public static function devices($pin, $ip, $token) {
        $ch = curl_init("https://$pin.local:8443/enduser-mobile-web/1/enduserAPI/setup/devices");
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/../../3rdparty/somfy/overkiz-root-ca-2048.crt');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer $token"));
        curl_setopt($ch, CURLOPT_RESOLVE, array("$pin.local:8443:$ip"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $error = curl_error($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        log::add('benjaminprevotConnexoon', 'error', $httpCode);

        if ($httpCode == 200) {
            $devices = [];

            foreach (json_decode($response, true) as $device) {
                $devices[] = array(
                    'deviceURL' => $device['deviceURL'],
                    'name'      => $device['label']
                );
            }

            return $devices;

            return array_map('Somfy::mapDevices', json_decode($response, true));
        }

        $errorFormat = 'Impossible de charger les objets pour la gateway %s : IP = %s - HTTP code = %s - Response = %s - Error = %s';

        $logMessage = sprintf($errorFormat, $pin, $ip, $httpCode, $response, $error);
        $errorMessage = sprintf(__($errorFormat, __FILE__), $pin, $ip, $httpCode, $response, $error);

        log::add('benjaminprevotConnexoon', 'error', $logMessage);

        throw new Exception($errorMessage);
    }

}