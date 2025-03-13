<?php
set_time_limit(200);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');
$i18n = i18n::getInstance();
$Letter = new Letter($Session, $PgGame);

$PgGame->query("SELECT lord_pk FROM lord WHERE last_login_dt + interval '15 days' < now() 
AND lord_pk NOT IN (SELECT lord_pk FROM qbig_pack WHERE lord_pk = lord.lord_pk GROUP BY lord_pk)
AND is_logon = 'N' AND yn_roamer_letter != 'Y' AND main_posi_pk is not null");
$PgGame->fetchAll();
$row = $PgGame->rows;

foreach($row AS $k => $v) {
	$letter = [];
	$letter['type'] = 'S';
	$letter['title'] = $i18n->t('letter_dormant_user_subject'); // 휴면계정 처리 안내
	$letter['content'] = $i18n->t('letter_dormant_user_content');

	$Letter->sendLetter(ADMIN_LORD_PK, [$v['lord_pk']], $letter, true, 'Y');
	$PgGame->query('UPDATE lord SET yn_roamer_letter = $1 WHERE lord_pk = $2', ['Y', $v['lord_pk']]);
}