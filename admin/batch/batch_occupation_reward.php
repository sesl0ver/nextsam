<?php
set_time_limit(600);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/constant.php';
require_once __DIR__ . '/../../vendor/autoload.php';
$i18n = i18n::getInstance();
require_once __DIR__ . '/../../config/master_data.php';

if (CONF_OCCUPATION_POINT_ENABLE !== true) {
    exit();
}

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');
$Event = new Event($PgGame);

if ($Event->getTrigger('occupation_point')) {
    exit();
}

$Redis = new RedisCache();

$NsGlobal = NsGlobal::getInstance();
$NsGlobal->requireMasterData(['occupation_reward']);

global $_M;
$m = $_M['OCCUPATION_REWARD']['alliance'];


// 동맹 랭킹 보상 지급
$rankings = $Redis->zRange('ranking:alliance:occupation_point', 0, 100);
$rankings = array_map(function ($a) { return json_decode($a, true); }, $rankings);

$Alliance = new Alliance($Session, $PgGame);
$Letter = new Letter($Session, $PgGame);
$Item = new Item($Session, $PgGame);
$Log = new Log($Session, $PgGame);

$last_rank = 0;
ksort($m);
foreach ($rankings as $rank) {
    foreach ($m as $_) {
        if ($_['rank'] > $rank['point_rank']) {
            break;
        }
        $last_rank = $_['rank'];
    }
    // 보상 정리
    $m_reward = array_map(function ($_m_reward) {
        $a = explode(':', $_m_reward);
        return ['m_item_pk' => $a[0], 'item_count' => $a[1]];
    }, explode(',',  $m[$last_rank]['reward_item']));

    // 동맹원 리스트 찾아오기
    $alliance_members = $Alliance->getMemberList($rank['alli_pk']);
    if (count($alliance_members) > 0) {
        // 보상 지급
        foreach ($alliance_members as $member) {
            $Item->giveItem($member['lord_pk'], $m_reward, $member['main_posi_pk'], 'occupation_reward');
        }

        $reward_text = array_map(function ($_m_reward) use ($i18n) {
            return $i18n->t("item_title_".$_m_reward['m_item_pk']) . 'x' . $_m_reward['item_count'];
        }, $m_reward);
        $reward_text = join(', ', $reward_text)."\n";

        // 서신 보내기
        $letter = [];
        $letter['type'] = 'S';
        $letter['title'] = $i18n->t('letter_occupation_rank_subject');

        $letter['content'] = <<< EOF
{$i18n->t('letter_occupation_rank_content', [$rank['point_rank']])}

＊{$i18n->t('reward_item')}
$reward_text
	
EOF;

        $Letter->sendLetterNew(ADMIN_LORD_PK, $alliance_members, $letter);
    }
}


$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['occupation_reward']);