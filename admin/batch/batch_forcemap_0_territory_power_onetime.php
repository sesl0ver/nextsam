<?php
// TODO 세력도 삭제로 사용안함.
/*set_time_limit(300);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');
$Power = new Power($Session, $PgGame);

// 지금 파일 실행 순서
// 1> PHP 클래스 파일들 업데이트
// 2> territory 에 power 컬럼 add
// 3> 이후 이거 실행

$start = time();

$PgGame->query('SELECT posi_pk FROM territory');
$PgGame->fetchAll();
$terr = $PgGame->rows;

if (count($terr) > 0) {
	foreach($terr as $v) {
		$t = $Power->getBuildingPower($v['posi_pk']) + $Power->getTechniquePower($v['posi_pk']);
		$PgGame->query('UPDATE territory SET power = $1 WHERE posi_pk = $2', [$t, $v['posi_pk']]);
	}
}

echo "EXECUTE TIME # " . (time() - $start) . " sec";*/
