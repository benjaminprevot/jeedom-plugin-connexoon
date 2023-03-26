<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');

if (!isConnect('admin')) {
    throw new Exception(__('401 - Accès non autorisé', __FILE__));
}

class Overkiz {

    public static function login($email, $password) {
        $ch = curl_init("https://ha101-1.overkiz.com/enduser-mobile-web/enduserAPI/login");
        curl_setopt($ch, CURLOPT_COOKIEFILE, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type' => 'application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('userId' => $email, 'userPassword' => $password));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $error = curl_error($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cookies = curl_getinfo($ch, CURLINFO_COOKIELIST);

        curl_close($ch);

        if ($httpCode == 200) {
            foreach ($cookies as $cookie) {
                $explode = explode("\t", $cookie);

                if ($explode[5] == 'JSESSIONID') {
                    return $explode[6];
                }
            }

            log::add('benjaminprevotConnexoon', 'error', 'Aucun cookie JSESSIONID trouvé');

            throw new Exception('Aucun cookie JSESSIONID trouvé');
        }

        $errorFormat = 'Erreur de connexion : Email = %s - HTTP code = %s - Response = %s - Error = %s';

        $logMessage = sprintf($errorFormat, $email, $httpCode, $response, $error);
        $errorMessage = sprintf(__($errorFormat, __FILE__), $email, $httpCode, $response, $error);

        log::add('benjaminprevotConnexoon', 'error', $logMessage);

        throw new Exception($errorMessage);
    }

    public static function generateToken($pin, $jsessionid) {
        $ch = curl_init("https://ha101-1.overkiz.com/enduser-mobile-web/enduserAPI/config/$pin/local/tokens/generate");
        curl_setopt($ch, CURLOPT_COOKIE, "JSESSIONID=$jsessionid");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $error = curl_error($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode == 200) {
            return json_decode($response, true)['token'];
        }

        $errorFormat = 'Erreur de génération : Pin = %s - JSESSIONID = %s - HTTP code = %s - Response = %s - Error = %s';

        $logMessage = sprintf($errorFormat, $pin, $jsessionid, $httpCode, $response, $error);
        $errorMessage = sprintf(__($errorFormat, __FILE__), $pin, $jsessionid, $httpCode, $response, $error);

        log::add('benjaminprevotConnexoon', 'error', $logMessage);

        throw new Exception($errorMessage);
    }

    public static function activateToken($pin, $jsessionid, $token) {
        $ch = curl_init("https://ha101-1.overkiz.com/enduser-mobile-web/enduserAPI/config/$pin/local/tokens");
        curl_setopt($ch, CURLOPT_COOKIE, "JSESSIONID=$jsessionid");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
            'label' => 'Jeedom',
            'token' => $token,
            'scope' => 'devmode'
        )));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $error = curl_error($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode == 200) {
            return;
        }

        $errorFormat = 'Erreur d\'activation : Pin = %s - JSESSIONID = %s - HTTP code = %s - Response = %s - Error = %s';

        $logMessage = sprintf($errorFormat, $pin, $jsessionid, $httpCode, $response, $error);
        $errorMessage = sprintf(__($errorFormat, __FILE__), $pin, $jsessionid, $httpCode, $response, $error);

        log::add('benjaminprevotConnexoon', 'error', $logMessage);

        throw new Exception($errorMessage);
    }

}
