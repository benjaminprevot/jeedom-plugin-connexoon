<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../core/class/benjaminprevotConnexoon.class.php';

function benjaminprevotConnexoon_install() {
}

function benjaminprevotConnexoon_update() {
  $eqLogics = eqLogic::byType('benjaminprevotConnexoon');

  foreach ($eqLogics as $eqLogic) {
    if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{8}-[0-9a-f]{8}-[0-9a-f]{8}$/', $eqLogic->getLogicalId()) === 1) {
        $eqLogic->remove();
    }
  }
}

function benjaminprevotConnexoon_remove() {
}
