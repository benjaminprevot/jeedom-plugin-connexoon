<?php
namespace Somfy {

    use Exception;

    class Device {
        public const ROLLER_SHUTTER = 'ROLLER_SHUTTER';
        public const NOT_MANAGED = 'NOT_MANAGED';
    }

    class Api {

        private $pin;

        private $ip;

        private $token;

        public function __construct($pin, $ip, $token = '') {
            $this->pin = $pin;
            $this->ip = $ip;
            $this->token = $token;
        }

        public function hasToken() {
            return !is_null($this->token) && !empty($this->token);
        }

        private function curl($method, $endpoint) {
            $ch = curl_init("https://$this->pin.local:8443/enduser-mobile-web/1/enduserAPI$endpoint");

            curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/overkiz-root-ca-2048.crt');
            curl_setopt($ch, CURLOPT_RESOLVE, array("$this->pin.local:8443:$this->ip"));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $body = curl_exec($ch);
            $error = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            return new \Somfy\Api\Response($code, $body, $error);
        }

        public function version() {
            $response = $this->curl('GET', '/apiVersion');

            if ($response->code() === 200) {
                return json_decode($response->body(), true)['protocolVersion'];
            }

            $errorFormat = 'Impossible de charger la version pour la gateway %s : IP = %s - HTTP code = %s - Response = %s - Error = %s';

            $errorMessage = sprintf($errorFormat, $this->pin, $this->ip, $response->code(), $response->body(), $response->error());

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
                    $deviceType = self::deviceTypeMapping($device['definition']['uiClass']);

                    $devices[] = array(
                        'deviceURL' => $device['deviceURL'],
                        'name'      => $device['label'],
                        'enabled'   => $device['enabled'],
                        'type'      => $deviceType,
                        'commands'  => self::deviceCommands($deviceType, $device['definition']['commands']),
                        'states'    => self::deviceStates($deviceType, $device['states']) + array(self::state(
                            'debug',
                            'string',
                            json_encode($device, JSON_PRETTY_PRINT)
                        ))
                    );
                }

                return $devices;
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

        private static function deviceTypeMapping($uiClass) {
            switch ($uiClass) {
                case 'RollerShutter':
                    return \Somfy\Device::ROLLER_SHUTTER;
                default:
                    return \Somfy\Device::NOT_MANAGED;
            }
        }

        private static function deviceCommands($deviceType, $deviceCommands) {
            $commands = array_map('Somfy\Api::mapCommand', $deviceCommands);

            switch ($deviceType) {
                case \Somfy\Device::ROLLER_SHUTTER:
                    return array_intersect([ 'close', 'identify', 'open', 'stop' ], $commands);
                default:
                    return [];
            }
        }

        private static function mapCommand($command) {
            return $command['commandName'];
        }

        private static function deviceStates($deviceType, $deviceStates) {
            switch ($deviceType) {
                case \Somfy\Device::ROLLER_SHUTTER:
                    $states = array_filter($deviceStates, 'Somfy\Api::filterState');

                    return array_map('Somfy\Api::mapState', $states);
                default:
                    return [];
            }
        }

        private static function filterState($state) {
            return in_array($state['name'], array('core:ClosureState', 'core:MovingState'));
        }

        private static function mapState($state) {
            return self::state(
                self::translateStateName($state['name']),
                self::translateStateType($state['type']),
                $state['value']
            );
        }

        private static function state($name, $type, $value) {
            return array(
                'name'  => $name,
                'type'  => $type,
                'value' => $value
            );
        }

        private static function translateStateType($stateType) {
            switch ($stateType) {
                case 1: return 'percent';
                case 6:  return 'boolean';
            }

            throw new Exception('Unknown state type: ' . $stateType);
        }

        private static function translateStateName($stateName) {
            switch ($stateName) {
                case 'core:ClosureState': return 'closure';
                case 'core:MovingState':  return 'moving';
            }

            throw new Exception('Unknown state name: ' . $stateName);
        }

        public static function registerEventListener($pin, $ip, $token) {
            $ch = curl_init("https://$pin.local:8443/enduser-mobile-web/1/enduserAPI/events/register");
            curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/overkiz-root-ca-2048.crt');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer $token", 'Content-Length: 0'));
            curl_setopt($ch, CURLOPT_RESOLVE, array("$pin.local:8443:$ip"));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $error = curl_error($ch);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($httpCode !== 200) {
                $errorFormat = 'Impossible d\'enregistrer l\'event listener pour la gateway %s : IP = %s - HTTP code = %s - Response = %s - Error = %s';

                $errorMessage = sprintf($errorFormat, $pin, $ip, $httpCode, $response, $error);

                throw new Exception($errorMessage);
            }

            $json = json_decode($response, true);

            return $json['id'];
        }

        public static function fetchEvents($pin, $ip, $token, $listenerId) {
            $ch = curl_init("https://$pin.local:8443/enduser-mobile-web/1/enduserAPI/events/$listenerId/fetch");
            curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/overkiz-root-ca-2048.crt');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer $token", 'Content-Length: 0'));
            curl_setopt($ch, CURLOPT_RESOLVE, array("$pin.local:8443:$ip"));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $error = curl_error($ch);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($httpCode !== 200) {
                $errorFormat = 'Impossible de lire les événements pour la gateway %s : IP = %s - HTTP code = %s - Response = %s - Error = %s - Listener = %s';

                $errorMessage = sprintf($errorFormat, $pin, $ip, $httpCode, $response, $error, $listenerId);

                throw new Exception($errorMessage);
            }

            $events = array();

            foreach (json_decode($response, true) as $event) {
                $deviceUrl = $event['deviceURL'];

                if (empty($deviceUrl)) {
                    continue;
                }

                $events[] = array(
                    'deviceURL' => $deviceUrl,
                    'states'    => self::eventStates($event['deviceStates'])
                );
            }

            return array_filter($events, 'Somfy\Api::hasAtLeastOneState');
        }

        private static function eventStates($deviceStates) {
            $states = array_filter($deviceStates, 'Somfy\Api::filterState');

            return array_map('Somfy\Api::mapState', $states);
        }

        private static function hasAtLeastOneState($event) {
            return !empty($event['states']);
        }

    }

}

namespace Somfy\Api {

    class Response {

        private $code;

        private $body;

        private $error;

        public function __construct($code, $body, $error) {
            $this->code = $code;
            $this->body = $body;
            $this->error = $error;
        }

        public function code() {
            return $this->code;
        }

        public function body() {
            return $this->body;
        }

        public function error() {
            return $this->error;
        }

    }

}
