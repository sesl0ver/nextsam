<?php
set_time_limit(600);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/constant.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/master_data.php';

// 이벤트가 꺼져있다면 실행 금지.
if (! CONF_NPC_POINT_ENABLE) {
    exit();
}

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');

$Hero = new Hero($Session, $PgGame);
$Item = new Item($Session, $PgGame);
$Troop = new Troop($Session, $PgGame);
$Report = new Report($Session, $PgGame);
$Letter = new Letter($Session, $PgGame);
$Log = new Log($Session, $PgGame);

$NsGlobal = NsGlobal::getInstance();
$NsGlobal->requireMasterData(['point_reward', 'point_reward_item', 'hero', 'hero_base', 'hero_skill_exp', 'item']);

try {
    $PgGame->begin();
    // 점령 보너스 포인트 (매일 00시50분)
    $PgGame->query('SELECT t2.posi_pk, t2.lord_pk, t2.occu_point + t2.bonus_point AS sum_point, 
  CASE WHEN date_part(\'epoch\', now())::integer - date_part(\'epoch\',t2.occu_dt)::integer between 86400 and 172799 THEN 3000
    WHEN date_part(\'epoch\', now())::integer - date_part(\'epoch\',t2.occu_dt)::integer between 172800 and 259199 THEN 6000
    WHEN date_part(\'epoch\', now())::integer - date_part(\'epoch\',t2.occu_dt)::integer between 259200 and 345599 THEN 12000
    WHEN date_part(\'epoch\', now())::integer - date_part(\'epoch\',t2.occu_dt)::integer >= 345600 THEN 24000
    ELSE 0
  END AS occo_bonus_point
FROM position_point t1, ranking_point t2
WHERE t1.lord_pk > 1 
AND t1.posi_pk = t2.posi_pk');
    $PgGame->fetchAll();
    $rows = $PgGame->rows;
    foreach($rows AS $k => $v) {
        if ($v['occo_bonus_point'] > 0) {
            $r = $PgGame->query('UPDATE ranking_point SET bonus_point = bonus_point + $1, last_occu_up_dt = now() WHERE lord_pk = $2 AND posi_pk = $3', [$v['occo_bonus_point'], $v['lord_pk'], $v['posi_pk']]);
            if ($r) {
                $Log->setPoint($v['lord_pk'], null, 'occo_bonus_point', $v['posi_pk'], 'prev:['.$v['sum_point'].'];change:['.$v['occo_bonus_point'].'];after:['.($v['sum_point'] + $v['occo_bonus_point']).'];');
            }
        }
    }

    if (date('w') != 2 && CONF_POINT_BATTLE_ALWAYS_POSSIBLE != 'Y')
        exit;

// 마지막 요충지 점령 유저 보너스 포인트
    $PgGame->query('UPDATE ranking_point SET occu_point = occu_point + 2000  FROM position_point t1
WHERE t1.lord_pk > 1 AND t1.posi_pk = ranking_point.posi_pk AND t1.lord_pk = ranking_point.lord_pk');
// 지난주 랭킹 초기화
    $PgGame->query('TRUNCATE TABLE ranking_point_last_week');


// 보상 처리
    $PgGame->query('SELECT lord_pk, sum(occu_point + bonus_point) as point, min(occu_dt) as regist_dt FROM ranking_point
WHERE (occu_point + bonus_point) > 0 GROUP BY lord_pk ORDER BY point DESC, regist_dt');
    $PgGame->fetchAll();
    $lord_list = $PgGame->rows;
    global $_M;
    if ($lord_list) {
        $rank = 1;
        foreach ($lord_list AS $k => $v) {
            $PgGame->query('INSERT INTO ranking_point_last_week (lord_pk, rank) VALUES ($1, $2)', [$v['lord_pk'], $rank]);

            $letter = [];
            $letter['type'] = 'S';
            $letter['title'] = '요충지확보에 기여한 공을 인정받아 보상이 지급되었습니다.';
            //$content = '요충지 공략에 기여한 공을 인정받아 '.$rank.'위 보상을 아래와 같이 받았습니다.';
            $content = <<< EOF
축하 드립니다.
요충지 공략에 기여한 공을 인정받아 아래와 같은 보상을 받았습니다.

보상은 서버 전체의 점령 포인트 랭킹에 따라 차등 지급되며 별도 보상이 없더라도
점령 코인을 모으면 제작 퀘스트를 통해 군주를 각성시킬 수 있습니다.
EOF;

            /*if (isset($_M['POIN_REWA'][$rank]['hero'])) {
                // 보고서 발송
                $z_content = [];
                $hero_str = null;
                global $_not_m_hero_base_list;
                $_not_m_hero_base_list = [];

                for ($i = 0; $i < $_M['POIN_REWA'][$rank]['count']; $i++) {
                    $hero_pk = $Hero->getNewHero($_M['POIN_REWA'][$rank]['hero'], null, $_M['POIN_REWA'][$rank]['rare'], null, null, null, null, 'point_reward', 'Y');

                    // 영웅 지급
                    $r = $Hero->setMyHeroCreate($hero_pk, $v['lord_pk'], 'V', null, null, 'N', 'point_reward');
                    if (!$r) {
                        // TODO 오류 로그?
                        // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'hero regist fail. lord_pk['.$v['lord_pk'].']; hero_pk['.$hero_pk.'];');
                    } else {
                        $m_hero_pk = $Troop->getHeroMasterDataPK($hero_pk);
                        $Log->setPoint($v['lord_pk'], null, 'reward_hero', null, 'hero_pk:['.$hero_pk .'];m_hero_pk:['.$m_hero_pk.'];');
                        $z_content['hero'][$i] = ['pk' => $hero_pk, 'm_pk' => $m_hero_pk];
                        if ($i == 0) {
                            $hero_str = $Troop->getHeroDesc($hero_pk);
                        } else {
                            $hero_str .= ', ' . $Troop->getHeroDesc($hero_pk);
                        }
                        $_not_m_hero_base_list[] = $m_hero_pk;
                    }
                }

                // 영웅정보
                // from & to
                $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$v['lord_pk']]);
                $z_from = ['posi_pk' => '-', 'posi_name' => '-'];
                $z_to = ['posi_pk' => $v['lord_pk'], 'posi_name' => $PgGame->fetchOne()];

                // title & summary
                $z_summary = $hero_str;

                $Report->setReport($v['lord_pk'], 'misc', 'over_rank_hero', $z_from, $z_to, '', $z_summary, json_encode($z_content));

                $z = <<< EOF

■ 오버랭크 영웅(영입대기 상태로 추가)
  {$hero_str}

EOF;

                $content = $content . $z;
            }*/

            if (isset($_M['POIN_REWA'][$rank]['army'])) {
                $type = rand(1, 3);
                $item_arr = array_values($_M['POIN_REWA_ITEM']['army'][$_M['POIN_REWA'][$rank]['army'] . '_' . $type]);

                $r = $Item->BuyItem($v['lord_pk'], $item_arr[0]['m_item_pk'], $item_arr[0]['item_cnt'], 'point_reward_army');
                if (!$r) {
                    // TODO 오류 로그?
                    // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'point reward fail. lord_pk['.$v['lord_pk'].']; m_item_pk['.$item_arr[0]['m_item_pk'].'];');
                } else {
                    $Log->setPoint($v['lord_pk'], null, 'reward_army', null, 'item:['.$item_arr[0]['m_item_pk'].'];count:['.$item_arr[0]['item_cnt'].'];');
                }

                $z = <<< EOF
■ 병력(패키지 아이템으로 보물창고에 지급)    
  
EOF;

                $content = $content . $z;
            }

            if (isset($_M['POIN_REWA'][$rank]['skill'])) {
                $range_arr = [];
                $range_arr = $_M['POIN_REWA_ITEM']['skill'][$_M['POIN_REWA'][$rank]['skill']];

                $range_prev = 1;
                $next = 0;
                $range_random_key = rand(1,1000); // 천

                $range_select = null;
                foreach ($range_arr as $k3 => $v3) {
                    $next = $range_prev + $v3['reward_rate'];
                    if ($range_random_key >= $range_prev && $range_random_key <= $next) {
                        $range_select = $k3;
                        break;
                    }
                    $range_prev = $next;
                }

                $r = $Item->BuyItem($v['lord_pk'], $range_select, $range_arr[$range_select]['item_cnt'], 'point_reward_skill');
                if (!$r) {
                    // TODO 오류 로그?
                    // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'point reward fail. lord_pk['.$v['lord_pk'].']; m_item_pk['.$range_select.'];');
                } else {
                    $Log->setPoint($v['lord_pk'], null, 'reward_skill', $v['posi_pk'], 'item:['.$range_select.'];count:['.$range_arr[$range_select]['item_cnt'].'];');
                }

                $z = <<< EOF
■ 영웅기술(패키지 아이템으로 보물창고에 지급)
  {$_M['ITEM'][$range_select]['title']}
  
EOF;

                $content = $content . $z;
            }

            if (isset($_M['POIN_REWA'][$rank]['item']) && $_M['POIN_REWA'][$rank]['item'] == 'Y') {
                $range_arr = [];
                $range_arr = $_M['POIN_REWA_ITEM']['item']['A'];

                $range_prev = 1;
                $next = 0;
                $range_random_key = rand(1,1000); // 천

                $range_select = null;
                foreach ($range_arr as $k3 => $v3) {
                    $next = $range_prev + $v3['reward_rate'];
                    if ($range_random_key >= $range_prev && $range_random_key <= $next) {
                        $range_select = $k3;
                        break;
                    }
                    $range_prev = $next;
                }

                $r = $Item->BuyItem($v['lord_pk'], $range_select, $range_arr[$range_select]['item_cnt'], 'point_reward_item');
                if (!$r) {
                    // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'point reward fail. lord_pk['.$v['lord_pk'].']; m_item_pk['.$range_select.'];');
                } else {
                    $Log->setPoint($v['lord_pk'], null, 'reward_item', $v['posi_pk'], 'item:['.$range_select.'];count:['.$range_arr[$range_select]['item_cnt'].'];');
                }

                $z = <<< EOF
■ 아이템(보물창고에 지급)
  {$_M['ITEM'][$range_select]['title']} X {$range_arr[$range_select]['item_cnt']}개
  
EOF;

                $content = $content . $z;
            }


            if (isset($_M['POIN_REWA'][$rank]['reso']) && $_M['POIN_REWA'][$rank]['reso'] == 'Y') {
                $range_arr = [];
                foreach($_M['POIN_REWA_ITEM']['reso']['A'] AS $k2 => $v2) {
                    $r = $Item->BuyItem($v['lord_pk'], $v2['m_item_pk'], $v2['item_cnt'], 'point_reward_reso');
                    if (!$r) {
                        // TODO 오류 로그?
                        // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'point reward fail. lord_pk['.$v['lord_pk'].']; m_item_pk['.$v2['m_item_pk'].'];');
                    } else {
                        $Log->setPoint($v['lord_pk'], null, 'reward_reso', $v['posi_pk'], 'item:['.$v2['m_item_pk'].'];count:['.$v2['item_cnt'].'];');
                    }
                }

                $z = <<< EOF
■ 자원(패키지 아이템으로 보물창고에 지급)
  식량 1,000,000	우마 1,000,000	목재 1,000,000	철강 1,000,000	황금 1,000,000
  
EOF;

                $content = $content . $z;
            }

            $rank++;

            // 점령 코인 지급
            $point_coin = floor($v['point'] / 10);
            if ($point_coin > 30000) {
                $point_coin = 30000;
            }
            $PgGame->query('SELECT point_coin FROM lord WHERE lord_pk = $1', [$v['lord_pk']]);
            $my_coin = $PgGame->fetchOne() + $point_coin;
            if ($my_coin > 30000) {
                $my_coin = 30000;
            }

            $z = <<< EOF
◆ 획득 점령 포인트 : {$v['point']}
◆ 획득 점령 코인 : {$point_coin}
◆ 총 보유 점령 코인 : {$my_coin}개
  
EOF;

            $content = $content . $z;

            $letter['content'] = $content;

            $Letter->sendLetter(ADMIN_LORD_PK, [$v['lord_pk']], $letter, true, 'Y');

            // 점령 코인 지급 - 서신을 위해 위로 이동
            $point_coin = floor($v['point'] / 10);
            if ($point_coin > 30000) {
                $point_coin = 30000;
            }

            $PgGame->query('SELECT point_coin FROM lord WHERE lord_pk = $1', [$v['lord_pk']]);
            $my_coin = $PgGame->fetchOne() + $point_coin;
            if ($my_coin > 30000) {
                $my_coin = 30000;
            }

            $r = $PgGame->query('UPDATE lord SET point_coin = $2 WHERE lord_pk = $1', [$v['lord_pk'], $my_coin]);
            if (!$r || $PgGame->getAffectedRows() != 1) {
                // TODO 오류 로그?
                // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'exchange coin fail;lord_pk:['.$v['lord_pk'].'];point['.$v['point'].'];');
            } else {
                $Log->setPoint($v['lord_pk'], null, 'reward_coin', null, $point_coin);
            }
        }
    }

    $PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['point_reward']);

    $PgGame->commit();
} catch (Throwable $e) {
    $PgGame->rollback();
    print_r($e);
}