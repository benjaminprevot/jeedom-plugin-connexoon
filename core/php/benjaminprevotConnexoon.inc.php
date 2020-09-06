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
}

/**
 * Configuration
 */
class Config
{
  public static function get($key) {
    return config::byKey($key, Plugin::ID);
  }

  public static function set($key, $value) {
    config::save($key, $value, Plugin::ID);
  }

  public static function unset($key) {
    config::remove($key, Plugin::ID);
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
}

class HttpRequest
{
  const METHOD_GET = 'GET';

  const METHOD_POST = 'POST';

  const RESPONSE_JSON_ARRAY = 'JSON_ARRAY';

  const RESPONSE_STRING = 'STRING';

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

  public function send($responseType)
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

    if ($responseType == self::RESPONSE_JSON_ARRAY)
    {
      return new HttpResponse($code, json_decode($content, true));
    }
    else
    {
      return new HttpResponse($code, $content);
    }
  }
}
