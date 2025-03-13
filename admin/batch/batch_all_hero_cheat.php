<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/constant.php';
require_once __DIR__ . '/../../config/master_data.php';
require_once __DIR__ . '/../../vendor/autoload.php';

global $_M;

$NsGlobal = NsGlobal::getInstance();
$NsGlobal->requireMasterData(['hero_base']);

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');
$Hero = new Hero($Session, $PgGame);

$lord_pk = 27; // 치트할 군주의 PK 번호

foreach ($_M['HERO_BASE'] as $row) {
    if ($row['type'] === 'K') {
        continue;
    }
    $hero_pk = $Hero->getNewHero('FREE', 1, $row['rare_type'], $row['m_hero_base_pk'], null, null, null, 'gm_hero_give', 'N', 'N', 'N');
    $Hero->setMyHeroCreate($hero_pk, $lord_pk, 'V', null, null, 'N', 'gm_hero_give');
}