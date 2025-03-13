<?php // 매시 1분에 batch_hero_free_bidoff 에서 실행해줌.
set_time_limit(180);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/constant.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$NsGlobal = NsGlobal::getInstance();

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');
$Hero = new Hero($Session, $PgGame);
$i18n = new i18n();

$PgGame->query('TRUNCATE hero_free_bid, hero_free');

$lord_level = 10; // 군주 레벨 제한이 없어짐에 따라 디폴트 설정

global $_not_m_hero_base_list;
for ($posi_stat_pk = 1; $posi_stat_pk <= 9; $posi_stat_pk++) {
	// for ($lord_level = 1; $lord_level <= 10 ; $lord_level++)
	{
		for ($count = 1; $count <= 30; $count++) {  // 30 명으로 수정
            $_not_m_hero_base_list = [120000, 120001, 120002, 120003, 120004];
			$hero_pk = $Hero->getNewHero('RECEPTIONHALL', null, null, null, $lord_level, null, null, 'free');

            $PgGame->query('INSERT INTO hero_free ( posi_stat_pk, lord_level, hero_pk, regist_dt ) VALUES ( $1, $2, $3, now() )', [$posi_stat_pk, $lord_level, $hero_pk]);

			// 0.1초
			usleep(100000);
		}
	}
}
$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['hero_free_random']);
