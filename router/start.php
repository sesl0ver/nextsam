<?php
global $app, $Render, $i18n;

$app->post('/api/start/session', $Render->wrap(function (array $params) use ($Render, $i18n) {
    global $NsGlobal, $_M;
    $PgGame = new Pg('DEFAULT');
    $PgCommon = new Pg('COMMON');

    $_add_data = [];

    // 언저 설정에 따른 세션 저장
    $_SESSION['lang'] = $params['lang'] ?? CONF_I18N_DEFAULT_LANGUAGE;

    if (! isset($params['uuid'])) {
        $Render->nsXhrReturn('failed', $i18n->t('msg_not_found_uuid_retry_please'));
        exit;
    }
    $platform = (CONF_ONLY_PLATFORM_MODE !== true) ? 'TEST' : $params['platform'];
    $Auth = new Auth($PgCommon, $params['uuid'], $params['lc'], $params['token'], $platform);
    $Auth->getAccount();
    if ($Auth->need_membership !== false) {
        return $Render->nsXhrReturn('failed', $i18n->t('msg_app_retry_please'));
    }

    $account_id = $Auth->account_info['account_pk'];
    $web_uid_index = $Auth->id;
    $web_channel = $Auth->lc;

    $PgGame->query('SELECT lord_pk FROM lord_web WHERE web_id = $1 AND web_channel = $2', [$account_id, $web_channel]);
    $lord_pk = null;
    if ($PgGame->fetch()) {
        $lord_pk = $PgGame->row['lord_pk'];
        $PgGame->query('UPDATE lord_web SET web_uidindex = $3 WHERE web_id = $1 AND web_channel = $2', [$account_id, $web_channel, $web_uid_index]);
    } else {
        // 일단 lord_web 에 등록
        $PgGame->query('INSERT INTO lord_web (web_id, web_channel, web_uidindex) VALUES ($1, $2, $3)', [$account_id, $web_channel, $web_uid_index]);
    }

    if (! $lord_pk) {
        return $Render->nsXhrReturn('success', null, ['state' => 'intro']); // 군주 생성 전 인트로 재생
    }
    $Session = new Session(true, true);

    // 유저 블럭 확인 (어차피 블럭시 session 을 없애기 때문에 시작시만 체크해도 됨.)
    if (! isset($params['lc']) || $params['lc'] != 0) {
        $PgGame->query('SELECT is_user_blocked, is_chat_blocked, blocked_cause, date_part(\'epoch\', user_block_end_dt)::integer as user_block_end_dt, date_part(\'epoch\', chat_block_end_dt)::integer as chat_block_end_dt FROM lord WHERE lord_pk = $1', [$lord_pk]);
        $PgGame->fetch();
        // 유저 차단 상태라면
        if ($PgGame->row['is_user_blocked'] === 'Y') {
            // limit 시간 확인
            if ($PgGame->row['user_block_end_dt'] >= time()) {
                $_add_data['state'] = 'block';
                $dt = new DateTime("now", new DateTimeZone('Asia/Seoul'));
                $dt->setTimestamp($PgGame->row['user_block_end_dt']);
                $limit_date = $dt->format('Y년 m월 d일 H시 i분');
                $_add_data['message'] = $i18n->t('msg_user_block_notice') . "<br /><br /><p>{$PgGame->row['blocked_cause']}</p><br />$limit_date";
                return $Render->nsXhrReturn('success', null, $_add_data);
            } else {
                // 블럭시간이 지났으므로 블록 해제 처리
                $PgGame->query('UPDATE lord SET is_user_blocked = $2, user_block_start_dt = NULL, user_block_end_dt = NULL, blocked_cause = NULL WHERE lord_pk = $1', [$lord_pk, 'N']);
            }
        }
        // 채팅 차단 상태라면
        if ($PgGame->row['is_chat_blocked'] === 'Y') {
            // 블럭시간이 지났으므로 블록 해제 처리
            if ($PgGame->row['chat_block_end_dt'] < time()) {
                $PgGame->query('UPDATE lord SET is_chat_blocked = $2, chat_block_start_dt = NULL, chat_block_end_dt = NULL WHERE lord_pk = $1', [$lord_pk, 'N']);
            }
        }
    }

    // 로그인 처리 시작
    $Lord = new Lord($Session, $PgGame);
    $result = $Lord->getLogin($lord_pk);
    if ($result !== 1) {
        if ($result == -2) { // 탈퇴한 계정
            throw new ErrorHandler('error', $i18n->t('msg_withdraw_account'));
        } else {
            throw new ErrorHandler('error', "Login failed. (err$result)");
        }
    }

    // 로그인 기록
    $login_ip = $Session->getRealClientIp();
    $PgGame->query('INSERT INTO lord_login (lord_pk, refer, login_ip, login_agent, login_sid, platform, uuid, udid, device_id) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)',
        [$Session->lord['lord_pk'], '', $login_ip, substr($_SERVER['HTTP_USER_AGENT'],0, 500), $Session->getSid(), $platform, $params['uuid'], '', '']);

    $PgGame->query('UPDATE lord SET last_login_dt = now(), platform = $2, uuid = $3, udid = $4, device_id = $5 WHERE lord_pk = $1 RETURNING date_part(\'epoch\', last_login_dt)::integer AS last_login_dt',
        [$Session->lord['lord_pk'], $platform, $params['uuid'], '', '']);

    $Session->setLoginReload();

    if (!$Session->lord['main_posi_pk']) {
        // 방랑 군주 시에 돌려줄 응답들
        $_add_data['state'] = 'roamer';

        $query_params = [$Session->lord['lord_pk']];

        // 세션 정리하는 작업을 하지 못하도록 last_lp_dt를 20분 뒤로 업데이트해둠. 유저는 20분 이내에 영지 생성을 시도해야함
        $PgGame->query('UPDATE lord SET last_lp_dt = now() + \'+ 20 minutes\' WHERE lord_pk = $1', $query_params);

        $PgGame->query('SELECT COUNT(my_item_pk) AS kind, SUM(item_cnt) AS total_count, SUM(item_cnt * price) AS value FROM my_item, m_item WHERE my_item.item_pk = m_item.m_item_pk AND lord_pk = $1', $query_params);
        $PgGame->fetch();
        $_add_data['lord_item'] = $PgGame->row;

        $sql = 'SELECT cash, roamer_inactive, power FROM lord WHERE lord_pk = $1';
        $PgGame->query($sql, $query_params);
        $PgGame->fetch();
        $_add_data['lord_cash'] = $PgGame->row['cash'];
        $_add_data['is_inactive'] = $PgGame->row['roamer_inactive'];
        $_add_data['lord_power'] = $PgGame->row['lord_power'] ?? 0;

        $sql = 'SELECT m_hero_pk FROM hero, my_hero WHERE hero.hero_pk = my_hero.hero_pk AND my_hero.lord_pk = $1 ORDER BY rare_type DESC, level DESC';
        $PgGame->query($sql, $query_params);
        $PgGame->fetchAll();
        $_add_data['hero_list'] = $PgGame->rows;

        $PgGame->query('SELECT agriculture, stock_farming, lumbering, mining, storage, construction, astronomy, paper, medicine, smelting, casting, machinery, mil_fencing, mil_shield, mil_spear, mil_horse, mil_science, fortification, compass, logistics, informatics, mil_archery, mil_formation, mil_siege FROM lord_technique WHERE lord_pk = $1', $query_params);
        $PgGame->fetch();
        $_add_data['lord_tech'] = $PgGame->row;

        $PgGame->query('SELECT roamer_last_my_territory, date_part(\'epoch\', roamer_aggression_dt)::integer as roamer_aggression_dt, roamer_aggression_lord FROM lord WHERE lord_pk = $1', $query_params);
        $PgGame->fetch();
        $_add_data['aggression'] = $PgGame->row;
        $_add_data['aggression']['roamer_aggression_dt'] = date('Y.m.d (H:i)', $_add_data['aggression']['roamer_aggression_dt']);

        $_add_data['cfg'] = ['sid' => $Session->getSid()];

        return $Render->nsXhrReturn('success', null, $_add_data);
    }

    // 배치 작업
    $NsGlobal->startBatch($Session);

    $posi_pk = (isset($params['cpp'])) ? $params['cpp'] : $Session->lord['main_posi_pk'];
    $result = $Session->sqInit($posi_pk, $params['change'] ?? false);
    if (! $result) {
        return $Render->nsXhrReturn('error', null, 'Login failed. (err2)');
    } else {
        // 동일 sid 처리를 위해 update
        $PgGame->query('UPDATE lord SET last_lp_dt = now(), is_logon = $1, last_sid = null WHERE last_sid = $2', ['N', $Session->getSid()]);
        $PgGame->query('UPDATE lord SET last_lp_dt = now(), is_logon = $1, last_sid = $2 WHERE lord_pk = $3', ['Y', $Session->getSid(), $Session->lord['lord_pk']]);
    }
    $Session->setCurrentPosition($posi_pk);

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Bdic = new Bdic($Session, $PgGame, $Resource, $GoldPop);
    $Bdoc = new Bdoc($Session, $PgGame, $Resource, $GoldPop);

    $Bdic->getPositions($posi_pk);
    $Bdoc->getPositions($posi_pk);

    $Troop = new Troop($Session, $PgGame);
    $Troop->getNpcSuppressToSQ($Session->lord['lord_pk']);

    $Lord->get();
    $Lord->getRank();
    $Lord->getUnreadCnt();

    $Territory = new Territory($Session, $PgGame);
    $Territory->get($posi_pk);

    $Resource->get($posi_pk);
    $GoldPop->get($posi_pk);

    $Timer = new Timer($Session, $PgGame);
    $Timer->get($posi_pk);

    $Technique = new Technique($Session, $PgGame, $Resource, $GoldPop);
    $Technique->get($posi_pk);

    $Army = new Army($Session, $PgGame, $Resource, $GoldPop);
    $Army->get($posi_pk);

    $Medical = new Medical($Session, $PgGame);
    $Medical->getInjuryArmy($posi_pk);

    $Hero = new Hero($Session, $PgGame);
    $heroes = $Hero->getMyAppoHeroes($Session->lord['lord_pk'], $posi_pk);
    $Session->sqAppend('HERO', $heroes);
    $Hero->getPickup();

    $Production = new Production($Session, $PgGame);
    $Production->get($posi_pk);

    $Fortification = new Fortification($Session, $PgGame);
    $Fortification->get($posi_pk);

    $Troop = new Troop($Session, $PgGame);
    $Troop->getTroopInfo($posi_pk);
    $Troop->getMoveTroops(false);

    $Item = new Item($Session, $PgGame);
    $Item->get($Session->lord['lord_pk']);
    $Item->getBuy($Session->lord['lord_pk']);

    // 이벤트 아이템 지급
    $Event = new Event($PgGame);
    $Letter = new Letter($Session, $PgGame);
    if ($Event->checkAccessRewardEvent($Session->lord['lord_pk'])) {
        $Item->BuyItem($Session->lord['lord_pk'], $_M['ACCESS_REWARD']['reward_item'], $_M['ACCESS_REWARD']['reward_count']);
        $Event->updateAccessRewardEvent($Session->lord['lord_pk']);

        $letter = [];
        $letter['type'] = 'S';
        $letter['title'] = $i18n->t('letter_access_reward_event_title'); // 접속 보상 이벤트 아이템 지급 알림
        $letter['content'] = $i18n->t('letter_access_reward_event_content', [$_M['ITEM'][$_M['ACCESS_REWARD']['reward_item']]['title']]);
        // 축하합니다! 이벤트에 참여해주셔서 아래의 보상을 지급해드립니다.<br />* 지급된 아이템은 보물창고에서 확인 가능합니다.<br /><br />보상: {{1}}

        $Letter->sendLetter(ADMIN_LORD_PK, [$Session->lord['lord_pk']], $letter, true, 'Y');
    }

    $Cash = new Cash($Session, $PgGame);
    $Cash->get($Session->lord['lord_pk']);

    $Quest = new Quest($Session, $PgGame);
    $Quest->get($Session->lord['lord_pk'], $account_id);

    $Alliance = new Alliance($Session, $PgGame);
    $Alliance->get($Session->lord['lord_pk']);
    $Alliance->getRelation($Session->lord['lord_pk']);

    $Letter->getUnreadCount($Session->lord['lord_pk']);

    $Report = new Report($Session, $PgGame);
    $Report->getUnreadCount($Session->lord['lord_pk']);

    // 진행 중인 Queue 데이터
    $Queue = new Queue($Session, $PgGame);
    $Queue->get();
    $Quest->countCheckQuest($Session->lord['lord_pk'], 'EVENT_LOGIN', ['value' => 1]);

    // 요충지가 적용 중인 서버인지 체크하기위해
    if (CONF_NPC_POINT_ENABLE) {
        $Session->sqAppend('PUSH', ['POINT_SERVER_CHECK' => true], null, $Session->lord['lord_pk'], $posi_pk);
    }

    // $Lord->getGameOption();
    $PgGame->query('SELECT sound_bgm, sound_effect, volume_bgm, volume_effect, counsel_action, counsel_connect, building_title, alert_effect_ally, alert_effect_enemy FROM game_option WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $PgGame->fetch();
    $Session->sqAppend('LORD', ['setting' => $PgGame->row]);

    // 진언창에 필요한 데이터(탐색)
    $PgGame->query('SELECT status, encounter_type, encounter_value, cmd_hero_pk FROM hero_encounter WHERE posi_pk = $1', [$posi_pk]);
    $PgGame->fetch();
    $encounter_data = $PgGame->row;
    if ($encounter_data && $encounter_data['encounter_type'] == 'hero') {
        $PgGame->query('select c.name from hero a, m_hero b, m_hero_base c where hero_pk = $1 and a.m_hero_pk = b.m_hero_pk and b.m_hero_base_pk = c.m_hero_base_pk', [$encounter_data['cmd_hero_pk']]);
        $encounter_data['cmd_hero'] = $PgGame->fetchOne();

        $PgGame->query('select c.name from hero a, m_hero b, m_hero_base c where hero_pk = $1 and a.m_hero_pk = b.m_hero_pk and b.m_hero_base_pk = c.m_hero_base_pk', [$encounter_data['encounter_value']]);
        $encounter_data['find_hero'] = $PgGame->fetchOne();
    }
    $Session->sqAppend('PUSH', ['COUNSEL_DATA' => $encounter_data], null, $Session->lord['lord_pk'], $posi_pk);

    // 진언창(토벌령)
    $PgGame->query('SELECT last_supp_pk FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $last_supp_pk = $PgGame->fetchOne();
    $PgGame->query('SELECT status FROM suppress WHERE supp_pk = $1', [$last_supp_pk]);
    $PgGame->fetch();
    $Session->sqAppend('PUSH', ['COUNSEL_DATA_SUPPRESS' => $PgGame->row], null, $Session->lord['lord_pk'], $posi_pk);

    // 동맹 정보
    $PgGame->query('select b.posi_pk from alliance_member a, position b where a.alli_pk = $1 AND a.lord_pk = b.lord_pk AND b.lord_pk != $4 AND a.type = $3 AND b.type = $2', [$Session->lord['alli_pk'], 'T', 'Y', $Session->lord['lord_pk']]);
    $PgGame->fetchAll();
    $Session->sqAppend('ALLI', $PgGame->rows);

    // 군주의 마지막 한정판매 정보를 알아옴
    $Lord->checkPackageDate($Session->lord['lord_pk']);
    $PgGame->query('SELECT m_package_pk, buy_count, date_part(\'epoch\', create_date) as create_date, date_part(\'epoch\', end_date) as end_date FROM my_package WHERE lord_pk = $1 and sold_out = 0 ORDER BY end_date', [$Session->lord['lord_pk']]);
    $PgGame->fetchAll();
    $list = [];
    foreach ($PgGame->rows as $row) {
        $list[$row['m_package_pk']] = $row;
    }
    $Session->sqAppend('PUSH', ['PACKAGE_LIST' => ['first_popup' => true, 'list' => $list]], null, $Session->lord['lord_pk']);

    // 이벤트 정보
    $Event->checkMyEvent($Session->lord['lord_pk']);
    $PgGame->query('SELECT time_buff_count, attendance_cnt, date_part(\'epoch\', last_attendance_dt)::integer as last_attendance_dt FROM my_event WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $PgGame->fetch();
    $Session->sqAppend('EVENT', [
        ...$PgGame->row,
        'time_buff_max' => $_M['TIME_BUFF']['max_count'],
        'time_buff_end' => strtotime($_M['TIME_BUFF']['end_date']),
        'treasure_end' => strtotime($_M['TREASURE_EVENT']['end_date']),
        'occupation_point_enable' => CONF_OCCUPATION_POINT_ENABLE
    ], null, $Session->lord['lord_pk']);

    // 시작할때 일단 이동 중인 모든 부대 정보를 가져옴
    // $move_troop_all = $Troop->getMoveTroopAll($Session->lord['lord_pk']);
    // $Session->sqAppend('MOVE_TROOP_ALL', $move_troop_all, null, $Session->lord['lord_pk']);

    $_add_data['state'] = 'game_start';
    $_add_data['cfg'] = [
        'game_server_name' => GAME_SERVER_NAME,
        'platform' => $platform,
        'max_num_slot_guest_hero' => NUM_SLOT_GUEST_HERO,
        'sid' => $Session->getSid(),
        'curr_posi_pk' => $posi_pk ?? null,
        'cmd_url_prefix' => THIS_SERVER_URL,
        'lp_url' => CONF_URL_LP
    ];

    $now = Useful::nowServerTime($PgGame);
    $Session->sqAppend('REDUCE', ['sTime' => $now]);
    $Session->sqAppend('PUSH', ['NPC_POINT' => CONF_NPC_POINT_ENABLE], null, $Session->lord['lord_pk']);

    $Letter->getUnreadCount($Session->lord['lord_pk']);

    if (CONF_ALLIANCE) {
        $Session->sqAppend('PUSH', ['ALLIANCE' => true], null, $Session->lord['lord_pk'], $posi_pk);
    }

    // 채팅서버를 위한 redis 토큰 기록
    $Chat = new Chat($Session, $PgGame);
    $Chat->setChatSession($Session->lord);

    // 1회성 토큰으로 사용되므로 게임 시작 이후 salt key 제거
    $PgCommon->query('UPDATE account SET salt_key = null WHERE uid = $1', [$params['uuid']]);

    return $Render->nsXhrReturn('success', null, $_add_data);
}));


$app->post('/api/start/lordCreate', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['uuid']);
    $Session = new Session(true, true);
    $PgGame = new Pg('DEFAULT');
    $PgCommon = new Pg('COMMON');

    $lord_card = $params['lord_card'];
    $set_area = 10; // TODO random?

    // 선택한 군주 카드 확인
    if (!isset($lord_card) || Decimal::set($lord_card)->lt(1) || Decimal::set($lord_card)->gt(5)) {
        throw new ErrorHandler('error', $i18n->t('msg_please_choose_lord'));
    }

    $platform = (CONF_ONLY_PLATFORM_MODE !== true) ? 'TEST' : $params['platform'];
    $Auth = new Auth($PgCommon, $params['uuid'], $params['lc'], $params['token'], $platform);
    $Auth->getAccount();
    if ($Auth->need_membership) {
        return $Render->nsXhrReturn('error', $i18n->t('msg_app_retry_please'));
    }

    $account_id = $Auth->account_info['account_pk'];
    $web_channel = $Auth->lc;

    // 계정 id와 channel이 이미 lord_web 테이블에 등록된 경우(이미 가입한 유저) 멈추어야함
    $PgGame->query('SELECT count(t2.lord_pk) FROM lord_web t1, lord t2 WHERE t1.lord_pk = t2.lord_pk AND t1.web_id = $1 AND t1.web_channel = $2', [$account_id, $web_channel]);
    $check = $PgGame->fetchOne();
    if ($check > 0) {
        return $Render->nsXhrReturn('error', 'Error Occurred. [37001]'); // 이미 군주 등록을 마친 계정
    }
    $lord_name = 'Lord'. Useful::uniqId(6);
    $title = $i18n->t('new_territory');

    $PgGame->query('SELECT * FROM signuplord($1, $2, $3)', [$lord_name, $lord_card, substr($_SERVER['HTTP_USER_AGENT'],0, 500)]);
    $lord_pk = $PgGame->fetchOne();
    if (!$lord_pk) {
        return $Render->nsXhrReturn('error', 'Error Occurred. [37002]'); // 군주 등록에 실패
    }

    $PgGame->query('select posi_stat_pk from m_preference');
    $posi_stat_pk = $PgGame->fetchOne();

    // 영지 프로시저 호출
    $PgGame->query('select * from getposition($1, $2, $3)', [$posi_stat_pk, $lord_pk, $set_area]);
    $posi_pk = $PgGame->fetchOne();

    if (! $posi_pk) {
        $PgGame->query('select * from getposition($1, $2, $3)', [$posi_stat_pk, $lord_pk, $set_area]);
        $posi_pk = $PgGame->fetchOne();
        if (!$posi_pk) {
            $query_params = [$lord_pk];
            $PgGame->query('DELETE FROM lord_point WHERE lord_pk = $1', $query_params);
            $PgGame->query('DELETE FROM lord_technique WHERE lord_pk = $1', $query_params);
            $PgGame->query('DELETE FROM lord WHERE lord_pk = $1', $query_params);
            return $Render->nsXhrReturn('error', 'Registration of Lord failed.', $posi_pk); // 군주 등록에 실패
        }
    }
    $PgGame->query('select * from signupterritory($1, $2, $3, $4)', [$lord_pk, $posi_pk, $title, iconv_substr($lord_name, 0, 4, 'utf-8')]);
    $r = $PgGame->fetchOne();
    if(! $r) {
        return $Render->nsXhrReturn('error', 'Error Occurred. [37003]'); // 군주 등록에 실패
    }

    $Lord = new Lord($Session, $PgGame);
    $result = $Lord->getLogin($lord_pk);

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
    $Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);

    $FigureReCalc->createTerritory($posi_pk);

    // 군주 지급
    $Hero = new Hero($Session, $PgGame);
    $hero_pk = $Hero->getNewLord($Session->lord['lord_pic']);
    $Hero->setMyHeroCreate($hero_pk, $Session->lord['lord_pk'], 'A', $posi_pk, 110109, 'Y');

    $Hero->setCommand($posi_pk, $hero_pk, 'A');

    $Bdic = new Bdic($Session, $PgGame, $Resource, $GoldPop);
    $Bdic->heroAssign($posi_pk, 1, $hero_pk, true);

    $user_ip_addr = $Session->getRealClientIp();
    if ($params['lc'] == 3 && array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
        $user_ip_addr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $user_ip_addr = trim($user_ip_addr[0]);
        $user_ip_addr = (!$user_ip_addr) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $user_ip_addr;
    }

    $PgGame->query('UPDATE lord SET lord_hero_pk = $1, refer = $3, regist_ip = $4 WHERE lord_pk = $2', [$hero_pk, $lord_pk, $Session->getREFER(), $user_ip_addr]);

    $Session->setLoginReload();

    // global $NsGlobal; TODO 필요있나?
    // $_not_m_hero_base_list = $NsGlobal->exceptHeroBase();
    for ($i = 110110; $i <= 110114; $i++) {
        $hero_pk = $Hero->getNewHero('REGIST', null, null, null, null, null, null, 'regist');
        $Hero->setMyHeroCreate($hero_pk, $Session->lord['lord_pk'], 'A', $posi_pk, $i);
    }

    // lord_web 테이블에 등록 (web_id , channel)
    $PgGame->query('UPDATE lord_web SET lord_pk = $3 WHERE web_id = $1 AND web_channel = $2', [$account_id, $web_channel, $lord_pk]);

    $PgGame->query('UPDATE lord SET num_appoint_hero = ( SELECT COUNT(*) FROM my_hero WHERE lord_pk = $1 AND status = $2 ) WHERE lord_pk = $1', [$lord_pk, 'A']);

    // 초보자 보호모드
    $Territory = new Territory($Session, $PgGame);
    $Territory->setTruceStatus($posi_pk, 'B', 500105);

    // 이벤트 테이블 추가
    $PgGame->query('INSERT INTO my_event (lord_pk) values ($1)', [$Session->lord['lord_pk']]);

    // game_option - TODO 의미 있나?
    $pn_setup = 'N';
    if (stripos($_SERVER['HTTP_USER_AGENT'], 'android') !== false) {
        $pn_setup = 'Y';
    }
    // 2013-12-10 : ktlee : iOS도 무조건 수집으로 변경
    $pn_setup = 'Y';
    $PgGame->query('INSERT INTO game_option (lord_pk, pn_setup) VALUES ($1, $2)', [$lord_pk, $pn_setup]);

    // 특정 기간에만 지급되는 이벤트 아이템
    /*if (strtotime("+9 hours") < strtotime('2019-11-11 00:00:00')) {
        $Item->BuyItem($lord_pk, 500797, 1, 'regist_gift', false);
        $Item->BuyItem($lord_pk, 500798, 1, 'regist_gift', false);
        $Item->BuyItem($lord_pk, 500799, 1, 'regist_gift', false);
    }*/

    return $Render->nsXhrReturn('success');
}));
