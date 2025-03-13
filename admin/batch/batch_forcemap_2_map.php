<?php
// TODO 세력도 삭제로 사용안함.
/*set_time_limit(3600);

require_once('../../inc/config.php');
require_once_classes(Array('CSession', 'CPgsql', 'CPower', 'CBrush', 'CCanvas', 'CForceMap', 'CForceArea'));

$start = microtime_float();

if (!is_dir(CONF_FORCEMAP)) { mkdir(CONF_FORCEMAP, 0777, true); }
$ver = (!$_GET['ver']) ? $argv[1] : $_GET['ver'];
if (!$ver) { echo "need version # first argument : version\n"; exit(1); }
define('CONF_FORCEMAP_PATH', CONF_FORCEMAP.$ver.'/');
if (!is_dir(CONF_FORCEMAP_PATH)) { mkdir(CONF_FORCEMAP_PATH, 0777, true); }
if (!is_dir(CONF_FORCEMAP_PATH.'map_all/')) { mkdir(CONF_FORCEMAP_PATH.'map_all/', 0777, true); }
if (!is_dir(CONF_FORCEMAP_PATH.'map_alli/')) { mkdir(CONF_FORCEMAP_PATH.'map_alli/', 0777, true); }
if (!is_dir(CONF_FORCEMAP_PATH.'area_map_all/')) { mkdir(CONF_FORCEMAP_PATH.'area_map_all/', 0777, true); }
if (!is_dir(CONF_FORCEMAP_PATH.'area_map_alli/')) { mkdir(CONF_FORCEMAP_PATH.'area_map_alli/', 0777, true); }
if (!is_dir(CONF_FORCEMAP_PATH.'area_map_base/')) { mkdir(CONF_FORCEMAP_PATH.'area_map_base/', 0777, true); }
if (!is_dir(CONF_FORCEMAP_PATH.'area_map_no_alli/')) { mkdir(CONF_FORCEMAP_PATH.'area_map_no_alli/', 0777, true); }

$Db = new CPgsql('DEF');

$fm = new CForceMap($Db, new CCanvas());
//$fa = new CForceArea($Db, $fm->getToRela(), $fm->getColorSet(), $fm->getBorderColorSet());

$fm->drawEachForce();
$fm->drawEachAlliForce();
$fm->drawNoRelaForce();
$fm->drawTerritoryMoveStateBg();
//$fa->drawEachArea(1, 729);

$end = microtime_float();

echo 'MAP MAKE : ' . ($end-$start) . ' SEC'."\n";*/
