<?php
class Somfy {

    public static $roller_shutter = 'ROLLER_SHUTTER';

    public static $not_managed = 'NOT_MANAGED';

    public static function apiVersion($pin, $ip) {
        $ch = curl_init("https://$pin.local:8443/enduser-mobile-web/1/enduserAPI/apiVersion");
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/overkiz-root-ca-2048.crt');
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

        $errorFormat = 'Impossible de charger la version pour la gateway %s : IP = %s - HTTP code = %s - Response = %s - Error = %s';

        $errorMessage = sprintf($errorFormat, $pin, $ip, $httpCode, $response, $error);

        throw new Exception($errorMessage);
    }

    public static function devices($pin, $ip, $token) {
        $ch = curl_init("https://$pin.local:8443/enduser-mobile-web/1/enduserAPI/setup/devices");
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/overkiz-root-ca-2048.crt');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer $token"));
        curl_setopt($ch, CURLOPT_RESOLVE, array("$pin.local:8443:$ip"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $error = curl_error($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode == 200) {
            $devices = [];

            foreach (json_decode($response, true) as $device) {
                $deviceType = self::deviceTypeMapping($device['controllableName']);

                $devices[] = array(
                    'deviceURL' => $device['deviceURL'],
                    'name'      => $device['label'],
                    'enabled'   => $device['enabled'],
                    'type'      => $deviceType,
                    'commands'  => self::deviceCommands($deviceType, $device['definition']['commands'])
                );
            }

            return $devices;

            return array_map('Somfy::mapDevices', json_decode($response, true));
        }

        $errorFormat = 'Impossible de charger les objets pour la gateway %s : IP = %s - HTTP code = %s - Response = %s - Error = %s';

        $errorMessage = sprintf($errorFormat, $pin, $ip, $httpCode, $response, $error);

        throw new Exception($errorMessage);
    }

    private static function deviceTypeMapping($controllableName) {
        switch ($controllableName) {
            case 'io:RollerShutterGenericIOComponent':
            case 'io:RollerShutterWithLowSpeedManagementIOComponent':
                return self::$roller_shutter;
            default:
                return self::$not_managed;
        }
    }

    private static function deviceCommands($deviceType, $deviceCommands) {
        $commands = array_map('Somfy::mapCommand', $deviceCommands);

        switch ($deviceType) {
            case self::$roller_shutter:
                return array_intersect([ 'open', 'close', 'stop' ], $commands);
            default:
                return [];
        }
    }

    private static function mapCommand($command) {
        return $command['commandName'];
    }

}
