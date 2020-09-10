<?php
/**
 * This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__  . '/../../../../core/php/core.inc.php';

/**
 * Plugin
 */
class Plugin
{
  const ID = 'benjaminprevotConnexoon';
}

/**
 * Logger
 */

class Logger
{
  private static function log($level, $message)
  {
    log::add(Plugin::ID, $level, $message);
  }

  public static function debug($message)
  {
    self::log('debug', $message);
  }

  public static function info($message)
  {
    self::log('info', $message);
  }

  public static function warning($message)
  {
    self::log('warning', $message);
  }

  public static function error($message)
  {
    self::log('error', $message);
  }
}

/**
 * Configuration
 */
class Config
{
  const ACCESS_TOKEN_KEY = 'access_token';

  const CONSUMER_KEY = 'consumer_key';

  const CONSUMER_STATE = 'consumer_state';

  const REFRESH_TOKEN = 'refresh_token';
  
  const TOKEN_EXISTS = 'token_exists';

  public static function get($key) {
    return config::byKey($key, Plugin::ID);
  }

  public static function set($key, $value) {
    config::save($key, $value, Plugin::ID);
  }

  public static function unset($key) {
    config::remove($key, Plugin::ID);
  }

  public static function getAccessToken()
  {
    return self::get(self::ACCESS_TOKEN_KEY);
  }

  public static function setAccessToken($accessToken)
  {
    return self::set(self::ACCESS_TOKEN_KEY, $accessToken);
  }

  public static function unsetAccessToken()
  {
    return self::unset(self::ACCESS_TOKEN_KEY);
  }

  public static function unsetRefreshToken()
  {
    return self::unset(self::REFRESH_TOKEN);
  }

  public static function unsetTokenExists()
  {
    return self::unset(self::TOKEN_EXISTS);
  }

  public static function setConsumerState($consumerState)
  {
    return self::set(self::CONSUMER_STATE, $consumerState);
  }

  public static function getConsumerKey()
  {
    return self::get(self::CONSUMER_KEY);
  }
}

/**
 * HTTP
 */
class HttpResponse
{
  private $_code;

  private $_content;

  public function __construct($code, $content)
  {
    $this->_code = $code;
    $this->_content = $content;
  }

  public function getCode()
  {
    return $this->_code;
  }

  public function getContent()
  {
    return $this->_content;
  }

  public function getContentAsJsonArray()
  {
    return json_decode($this->_content, true);
  }
}

class HttpRequest
{
  const METHOD_GET = 'GET';

  const METHOD_POST = 'POST';

  public static function get($url)
  {
    $request = new HttpRequest(self::METHOD_GET);
    $request->_url = $url;

    return $request;
  }

  public static function post($url)
  {
    $request = new HttpRequest(self::METHOD_POST);
    $request->_url = $url;

    return $request;
  }

  private $_method;

  private $_url;

  private $_params;

  private $_headers;

  private $_content;

  private function __construct($method)
  {
    $this->_method = $method;
    $this->_params = array();
    $this->_headers = array();
    $this->_content = '';
  }

  private function header_map($key, $value)
  {
    return $key . ': ' . $value;
  }

  public function param($key, $value)
  {
    $this->_params[$key] = $value;

    return $this;
  }

  public function header($key, $value)
  {
    $this->_headers[$key] = $value;

    return $this;
  }

  public function content($content)
  {
    $this->_content = $content;

    return $this;
  }

  public function buildUrl()
  {
    return $this->_url . (count($this->_params) == 0 ? '' : '?') . http_build_query($this->_params);
  }

  public function send()
  {
    // Build url with query parameters if exist
    $url = $this->buildUrl();

    // Build headers array
    $headers = array_map(array($this, 'header_map'), array_keys($this->_headers), $this->_headers);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($this->_method === self::METHOD_POST) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_content);
    }
    
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content = curl_exec($ch);

    Logger::debug('HTTP - ' . $this->_method . ' - ' . $url . ' - ' . $code);

    curl_close($ch);

    return new HttpResponse($code, $content);
  }
}

/**
 * Somfy
 */
class Somfy
{
  private static function saveToken($response)
  {
    $code = $response->getCode();

    if ($code == 200)
    {
      $json = $response->getContentAsJsonArray();

      if (isset($json['access_token']) && isset($json['refresh_token']))
      {
        Config::setAccessToken($json['access_token']);
        Config::set('refresh_token', $json['refresh_token']);
        Config::set('token_exists', 'true');

        Logger::debug('[Somfy] Token saved');
      }
      else
      {
        Config::set('token_exists', 'false');

        Logger::error('[Somfy] Incorrect token format: ' . print_r($json, true));
      }
    }
    else
    {
      Logger::error('[Somfy] An error occured while refreshing token - HTTP code: ' . $code);
    }
  }

  public static function getToken($code, $state)
  {
    Logger::debug('[Somfy] Get token');

    $response = HttpRequest::get('https://accounts.somfy.com/oauth/oauth/v2/token')
        ->param('client_id', Config::get('consumer_key'))
        ->param('client_secret', Config::get('consumer_secret'))
        ->param('grant_type', 'authorization_code')
        ->param('code', $code)
        ->param('redirect_uri', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/index.php?v=d&plugin=' . Plugin::ID . '&modal=callback')
        ->param('state', $state)
        ->send();

    self::saveToken($response);
  }

  public static function refreshToken()
  {
    Logger::debug('[Somfy] Refresh token');

    $response = HttpRequest::get('https://accounts.somfy.com/oauth/oauth/v2/token')
        ->param('client_id', Config::get('consumer_key'))
        ->param('client_secret', Config::get('consumer_secret'))
        ->param('refresh_token', Config::get('refresh_token'))
        ->param('grant_type', 'refresh_token')
        ->send();
    
    self::saveToken($response);
  }

  public static function getAuthUrl()
  {
    $state = hash("sha256", rand());
    Config::setConsumerState($state);
    
    return HttpRequest::get('https://accounts.somfy.com/oauth/oauth/v2/auth')
        ->param('response_type', 'code')
        ->param('client_id', Config::getConsumerKey())
        ->param('redirect_uri', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/index.php?v=d&plugin=' . Plugin::ID . '&modal=callback')
        ->param('state', $state)
        ->param('grant_type', 'authorization_code')
        ->buildUrl();
  }

  private static function api($url, $content = '', $limit = 5)
  {
    Logger::debug('[Somfy] Call ' . $url . ' - try ' . $limit);

    if ($limit < 1) {
      Logger::error('[Somfy] Number of tries exceeded');

      return false;
    }

    $response = HttpRequest::get($url)
        ->header('Authorization', 'Bearer ' . Config::get('access_token'))
        ->header('Content-Type', 'application/json')
        ->content($content)
        ->send();
    
    $code = $response->getCode();
    $json = $response->getContentAsJsonArray();

    if ($code > 299)
    {
      Logger::warning('[Somfy] Code received ' . $code . ' - retry');

      return self::api($url, $content, $limit - 1);
    }
    elseif (isset($json['fault'])
      && isset($json['fault']['detail'])
      && isset($json['fault']['detail']['errorcode'])
      && $json['fault']['detail']['errorcode'] == 'keymanagement.service.access_token_expired')
    {
      Logger::info('[Somfy] Refresh token and retry');

      self::refreshToken();

      return self::api($url, $content, $limit - 1);
    }

    return $json;
  }

  public static function getSites()
  {
    Logger::debug('Getting sites list');

    return self::api('https://api.somfy.com/api/v1/site');
  }

  public static function getDevices($siteId)
  {
    Logger::debug('Getting devices list for site ' . $siteId);

    return self::api('https://api.somfy.com/api/v1/site/' . $siteId . '/device');
  }

  public static function getDevice($deviceId)
  {
    return self::api('https://api.somfy.com/api/v1/device/' . $deviceId);
  }

  public static function action($deviceId, $action)
  {
    return self::api('https://api.somfy.com/api/v1/device/' . $deviceId . '/exec', '{ "name": "' . $action . '", "parameters": [] }');
  }
}
