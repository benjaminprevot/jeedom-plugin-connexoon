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
                    'commands'  => self::deviceCommands($deviceType, $device['definition']['commands']),
                    'states'    => self::deviceStates($deviceType, $device['states'])
                );
            }

            return $devices;

            return array_map('Somfy::mapDevices', json_decode($response, true));
        }

        $errorFormat = 'Impossible de charger les objets pour la gateway %s : IP = %s - HTTP code = %s - Response = %s - Error = %s';

        $errorMessage = sprintf($errorFormat, $pin, $ip, $httpCode, $response, $error);

        throw new Exception($errorMessage);
    }

    public static function execute($pin, $ip, $token, $deviceUrl, $command) {
        $json = json_encode(array(
            'label' => $command,
            'actions' => array(
                array(
                    'commands' => array(
                        array( 'name' => $command )
                    ),
                    'deviceURL' => $deviceUrl
                )
            )
        ), JSON_UNESCAPED_SLASHES);

        $ch = curl_init("https://$pin.local:8443/enduser-mobile-web/1/enduserAPI/exec/apply");
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/overkiz-root-ca-2048.crt');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer $token", 'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RESOLVE, array("$pin.local:8443:$ip"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $error = curl_error($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode !== 200) {
            $errorFormat = 'Impossible d\'exécuter la command pour la gateway %s : command = %s, IP = %s - HTTP code = %s - Response = %s - Error = %s';

            $errorMessage = sprintf($errorFormat, $pin, $command, $ip, $httpCode, $response, $error);

            throw new Exception($errorMessage);
        }
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
                return array_intersect([ 'close', 'open', 'stop' ], $commands);
            default:
                return [];
        }
    }

    private static function mapCommand($command) {
        return $command['commandName'];
    }

    private static function deviceStates($deviceType, $deviceStates) {
        switch ($deviceType) {
            case self::$roller_shutter:
                $states = array_filter($deviceStates, 'Somfy::filterState');

                return array_map('Somfy::mapState', $states);
            default:
                return [];
        }
    }

    private static function filterState($state) {
        return $state['name'] === 'core:ClosureState';
    }

    private static function mapState($state) {
        return array(
            'type'  => self::translateStateType($state['type']),
            'name'  => self::translateStateName($state['name']),
            'value' => $state['value']
        );
    }

    private static function translateStateType($stateType) {
        if ($stateType === 1) {
            return 'percent';
        }

        throw new Exception('Unknown state type: ' . $stateType);
    }

    private static function translateStateName($stateName) {
        if ($stateName === 'core:ClosureState') {
            return 'closure';
        }

        throw new Exception('Unknown state name: ' . $stateName);
    }

}
