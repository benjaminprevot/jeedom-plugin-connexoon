<?php

/* This file is part of Jeedom.
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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../core/class/benjaminprevotConnexoon.class.php';

function benjaminprevotConnexoon_install()
{ }

function benjaminprevotConnexoon_update()
{
  $updated = false;

  $configuration = explode(' ', microtime())[1];

  $updated = updateConfiguration($configuration, 'access_token') || $updated;
  $updated = updateConfiguration($configuration, 'callback_url') || $updated;
  $updated = updateConfiguration($configuration, 'consumer_key') || $updated;
  $updated = updateConfiguration($configuration, 'consumer_secret') || $updated;
  $updated = updateConfiguration($configuration, 'refresh_token') || $updated;
  $updated = updateConfiguration($configuration, 'token_exists') || $updated;

  if ($updated)
  {
    ConnexoonConfig::addConfiguration($configuration);

    ConnexoonLogger::info("[Install] Configuration ${configuration} created");
  }
}

function benjaminprevotConnexoon_remove()
{ }

function updateConfiguration($configuration, $key)
{
  if (ConnexoonConfig::get($key))
  {
    ConnexoonConfig::set("${configuration}_${key}", ConnexoonConfig::get($key));
    ConnexoonConfig::unset($key);

    return true;
  }

  return false;
}
