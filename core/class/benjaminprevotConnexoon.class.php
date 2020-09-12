<?php
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class benjaminprevotConnexoon extends eqLogic
{

  public static function sync()
  {
    Logger::debug('Refreshing devices');

    $sites = Somfy::getSites();

    foreach ($sites as $site)
    {
      if (isset($site['id']))
      {
        $devices = Somfy::getDevices($site['id']);

        $capabilityNameFunc = function($capability)
        {
          return $capability['name'];
        };

        foreach ($devices as $device)
        {
          if (in_array('roller_shutter', $device['categories']))
          {
            $logicalId = $device['id'];

            Logger::debug('Sync ' . $logicalId);

            $benjaminprevotConnexoon = self::byLogicalId($logicalId, Plugin::ID);

            if (!is_object($benjaminprevotConnexoon))
            {
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

  public static function cron5()
  {
    self::sync();
  }

  public static function cron30()
  {
    Somfy::refreshToken();
  }

  private function addCommand($logicalId, $name, $genericType, $type = 'action', $subType = 'other', $unite = null)
  {
    $cmd = $this->getCmd(null, $logicalId);
    
    if (!is_object($cmd))
    {
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

  public function refresh()
  {
    $device = Somfy::getDevice($this->getLogicalId());

    foreach ($device['states'] as $state)
    {
      if ($state['name'] == 'position')
      {
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

    if (in_array('open', $actions))
    {
      $this->addCommand('open', 'Ouvrir', 'ROLLER_OPEN');
    }

    if (in_array('close', $actions))
    {
      $this->addCommand('close', 'Fermer', 'ROLLER_CLOSE');
    }

    if (in_array('identify', $actions))
    {
      $this->addCommand('identify', 'Identifier', 'ROLLER_IDENTIFY');
    }

    if (in_array('stop', $actions))
    {
      $this->addCommand('stop', 'Stop', 'ROLLER_STOP');
    }

    $this->addCommand('refresh', 'Rafraîchir', 'ROLLER_REFRESH');

    // Info
    $this->addCommand('position', 'Position', 'ROLLER_POSITION', 'info', 'numeric', '%');
  }

  public function toHtml($_version = 'dashboard')
  {
    $replace = $this->preToHtml($_version);
    if (!is_array($replace))
    {
      return $replace;
    }

    $version = jeedom::versionAlias($_version);
    if ($this->getDisplay('hideOn' . $version) == 1)
    {
      return '';
    }

    foreach ($this->getCmd('info') as $cmd)
    {
      $replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
      $replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
    }

    foreach ($this->getCmd('action') as $cmd)
    {
      $replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
      $replace['#' . $cmd->getLogicalId() . '_hide#'] = $cmd->getIsVisible() ? '' : 'display:none;';
    }
    
    return template_replace($replace, getTemplate('core', $version, 'eqLogic', Plugin::ID));
  }

}

class benjaminprevotConnexoonCmd extends cmd
{

  public function execute($_options = array())
  {
    if ($this->getType() == '')
    {
        return;
    }

    $eqLogic = $this->getEqLogic();
    $action= $this->getLogicalId();

    if ($this->getType() == 'action')
    {
      if ($action == 'refresh')
      {
        $eqLogic->refresh();
      }
      else
      {
        $eqLogic->action($action);
      }
    }
  }

}
