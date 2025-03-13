<?php

class Gm
{
    protected Pg $PgGm;
    protected Pg $PgGame;
    protected Pg $PgCommon;

    protected function classPgGm(): void
    {
        if (! isset($this->PgGm)) {
            $this->PgGm = new Pg('GM');
        }
    }

    protected function classPgGame(): void
    {
        if (! isset($this->PgGame)) {
            // 1. 선택된 서버가 있는지 확인
            if (isset($_SESSION['selected_server']['server_pk'])) {
                $this->selectPgGame($_SESSION['selected_server']['server_pk']);
                $this->PgGame = new Pg('SELECT');
            }
        }
    }

    protected function classPgCommon(): void
    {
        if (! isset($this->PgCommon)) {
            $this->PgCommon = new Pg('COMMON');
        }
    }

    public function selectPgGame ($_server_pk): void
    {
        $this->classPgGm();
        $this->PgGm->query('SELECT server_pk, server_name, db_ip, db_port, db_account, db_password, log_db_ip, log_db_port, log_db_account, log_db_password FROM server WHERE server_pk = $1', [$_server_pk]);
        $this->PgGm->fetch();

        define('SELECT_PGSQL_IP', $this->PgGm->row['db_ip']);
        define('SELECT_PGSQL_PORT', $this->PgGm->row['db_port']);
        define('SELECT_PGSQL_DB', 'qbegame');
        define('SELECT_PGSQL_USER', $this->PgGm->row['db_account']);
        define('SELECT_PGSQL_PASS', $this->PgGm->row['db_password']);
        define('SELECT_PGSQL_PERSISTENT', true);

        define('SELECT_LOG_PGSQL_IP', $this->PgGm->row['log_db_ip']);
        define('SELECT_LOG_PGSQL_PORT', $this->PgGm->row['log_db_port']);
        define('SELECT_LOG_PGSQL_DB', 'qbelog');
        define('SELECT_LOG_PGSQL_USER', $this->PgGm->row['log_db_account']);
        define('SELECT_LOG_PGSQL_PASS', $this->PgGm->row['log_db_password']);
        define('SELECT_LOG_PGSQL_PERSISTENT', true);
    }

    public function checkGMPermission($needPermissionList): false|string
    {
        $isAllow = false;
        foreach($_SESSION['gm_permit'] as $k => $v) {
            if ($v === true && in_array($k, $needPermissionList)) {
                $isAllow = !$isAllow ? true : $isAllow;
            }
        }
        if (!$isAllow) {
            // 권한이 없음
            $p_word_list = ['NOTICE' => '공지', 'BLOCK' => '제재', 'LOG' => '로그 조회', 'CHEAT' => '치트', 'EDIT' => '에디팅', 'SMONITOR' => '서버 모니터링', 'SCOMMAND' => '서버 명령', 'ENQUINARY' => '일반조회'];
            foreach($needPermissionList as &$v) {
                $v = $p_word_list[$v];
            }
            return implode(" 또는 ", $needPermissionList);
        } else {
            // 권한 있음
            return false;
        }
    }

    public function setLeftMenuElement ($_text = null, $_view = null, $_permission = null, $_parent_index = null, $_need_server_pk = false, $_need_lord_pk = false, $_need_posi_pk = false) :stdClass
    {
        $obj = new stdClass();
        $obj->text = $_text;
        $obj->view = ($_text === null) ? null : $_view;
        $obj->parentIdx = $_parent_index;
        $obj->needServPK = $_need_server_pk;
        $obj->needLordPK = $_need_lord_pk;
        $obj->needPosiPK = $_need_posi_pk;
        $obj->permission = $_permission;
        return $obj;
    }

    public function getLeftMenu ($_server_list_select_tag, $selected_lord, $selected_terr): array
    {
        $left_menu = [];

        $left_menu[] = $this->setLeftMenuElement();
        $left_menu[] = $this->setLeftMenuElement('일반', null, '#ENQUINARY#NOTICE#SMONITOR#PGGM', 0);
        $left_menu[] = $this->setLeftMenuElement('GM 로그', 'gm_log', '#SMONITOR', 1);
        $left_menu[] = $this->setLeftMenuElement('GM 서신 발송', null, '#NOTICE', 1);

        $parent_index_1 =  count($left_menu) - 1;

        $left_menu[] = $this->setLeftMenuElement('발신함', 'send_gm_letter_list', '#NOTICE', $parent_index_1);
        $left_menu[] = $this->setLeftMenuElement('작성', 'send_gm_letter_form', '#NOTICE', $parent_index_1);
        $left_menu[] = $this->setLeftMenuElement('군주 검색', 'user_search', '#ENQUINARY#PGGM', 1);
        $left_menu[] = $this->setLeftMenuElement('서버 목록', 'serv_info_list', '#SMONITOR', 1);
        // $left_menu[] = $this->setLeftMenuElement('문의 조회', 'counsel_customer', '#SMONITOR', 1);
        // $left_menu[] = $this->setLeftMenuElement('상단 공지', 'top_notice_list', '#SMONITOR', 1);
        $left_menu[] = $this->setLeftMenuElement('이벤트 아이템 지급', 'gm_event_item', '#SMONITOR', 1);
        $left_menu[] = $this->setLeftMenuElement('일괄 군주 검색', 'lord_search', '#SMONITOR', 1);
        $left_menu[] = $this->setLeftMenuElement('채팅 공지', 'serv_info_chat_notice', '#NOTICE#EDIT', 1);
        $left_menu[] = $this->setLeftMenuElement('쿠폰 설정', 'serv_info_coupon', '#PGGM#EDIT', 1);

        // $left_menu[] = $this->setLeftMenuElement('내부 쿠폰 현황 조회', 'coupon_info_main', '#SMONITOR', 1);
        // $left_menu[] = $this->setLeftMenuElement('외부 쿠폰 현황 조회', 'coupon_info_cross', '#SMONITOR', 1);

        $left_menu[] = $this->setLeftMenuElement("&nbsp;{$_server_list_select_tag}&nbsp; 서버 정보", null, '#ENQUINARY#CHEAT#LOG#SMONITOR#SCOMMAND#BLOCK#NOTICE#EDIT#PGGM', 0);

        $parent_index_2 =  count($left_menu) - 1;

        $left_menu[] = $this->setLeftMenuElement('동접 로그', 'serv_detail_info_ccu', '#SMONITOR', $parent_index_2, true);
        $left_menu[] = $this->setLeftMenuElement('서버 통계', 'serv_detail_info_statistics', '#SMONITOR', $parent_index_2, true);
        // $left_menu[] = $this->setLeftMenuElement('일괄 외교서신', 'serv_all_letter', '#SMONITOR', $parent_index_2, true);
        // $left_menu[] = $this->setLeftMenuElement('이벤트 일정 관리', 'serv_event_manage', '#SMONITOR', $parent_index_2, true);

        // $left_menu[] = $this->setLeftMenuElement('스케쥴 동작 상태', 'serv_detail_info_schedule', '#SMONITOR', $parent_index_2, true);
        $left_menu[] = $this->setLeftMenuElement('지역/주 정보 현황', 'serv_detail_info_preference', '#SMONITOR', $parent_index_2, true);
        $left_menu[] = $this->setLeftMenuElement('영지 오류 체크', 'serv_info_position_checker', '#SMONITOR', $parent_index_2, true);
        // $left_menu[] = $this->setLeftMenuElement('통합 공지', 'serv_detail_info_integrate_notice', '#SMONITOR', $parent_index_2, true);
        $left_menu[] = $this->setLeftMenuElement('큐빅 지급/회수', 'serv_info_qbig_modify', '#EDIT#PGGM', $parent_index_2, true);
        $left_menu[] = $this->setLeftMenuElement('로그 검색', null, 	'#LOG#PGGM', $parent_index_2, true);

        $parent_index_3 =  count($left_menu) - 1;

        $left_menu[] = $this->setLeftMenuElement('큐빅 구매 로그', 'serv_info_qbig_pack', '#LOG#PGGM', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('큐빅 사용 조회', 'serv_detail_info_log_search_qbig', '#LOG#PGGM', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('건설 로그', 'serv_detail_info_log_search_build', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('기술 로그', 'serv_detail_info_log_search_tech', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('방어시설 로그', 'serv_detail_info_log_search_fortification', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('창고 로그', 'serv_detail_info_log_search_storage', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('건물 배속 로그', 'serv_detail_info_log_search_assign', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('생산 증감 로그', 'serv_detail_info_log_search_production','#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('자원 증감 로그', 'serv_detail_info_log_search_resource', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('영빈관 입찰 로그', 'serv_detail_info_log_search_receptionhall', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('무역장 거래 로그', 'serv_detail_info_log_search_tradedept', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('행정부 로그', 'serv_detail_info_log_search_administration', '#LOG', $parent_index_3, true);
        // $left_menu[] = $this->setLeftMenuElement('의료원 로그', 'serv_detail_info_log_search_medical', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('시장 로그', 'serv_detail_info_log_search_market', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('영웅 로그', null, '#LOG', $parent_index_3, true); // 31

        $parent_index_4 =  count($left_menu) - 1;

        $left_menu[] = $this->setLeftMenuElement('영웅 일반 로그', 'serv_detail_info_log_search_hero', '#LOG', $parent_index_4, true);
        $left_menu[] = $this->setLeftMenuElement('영웅 상태 로그', 'serv_detail_info_log_search_hero_command', '#LOG', $parent_index_4, true);
        $left_menu[] = $this->setLeftMenuElement('영웅 강화 로그', 'serv_detail_info_log_search_hero_enchant', '#LOG', $parent_index_4, true);
        $left_menu[] = $this->setLeftMenuElement('영웅 탐색 로그', 'serv_detail_info_log_search_encounter', '#LOG', $parent_index_4, true);
        $left_menu[] = $this->setLeftMenuElement('영웅 초빙 로그', 'serv_detail_info_log_search_invitation', '#LOG', $parent_index_4, true);
        // $left_menu[] = $this->setLeftMenuElement('영웅 거래 로그', 'serv_detail_info_log_search_hero_trade', '#LOG', $parent_index_4, true);
        $left_menu[] = $this->setLeftMenuElement('영웅 경험치 로그', 'serv_detail_info_log_search_hero_exp', '#LOG', $parent_index_4, true);
        $left_menu[] = $this->setLeftMenuElement('영웅 기술 로그', 'serv_detail_info_log_search_hero_skill', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('영웅 기술 발동 로그', 'serv_detail_info_log_search_hero_skill_active', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('아이템 로그', 'serv_detail_info_log_search_item', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('버프 로그', 'serv_detail_info_log_search_buff', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('부대 관련 로그', null, '#LOG', $parent_index_3, true); // 31

        $parent_index_10 =  count($left_menu) - 1;

        $left_menu[] = $this->setLeftMenuElement('병력 로그', 'serv_detail_info_log_search_army', '#LOG', $parent_index_10, true);
        $left_menu[] = $this->setLeftMenuElement('부대 이동 로그', 'serv_detail_info_log_search_troop', '#LOG', $parent_index_10, true);
        $left_menu[] = $this->setLeftMenuElement('전투 로그', 'serv_detail_info_log_search_battle', '#LOG', $parent_index_10, true);
        // $left_menu[] = $this->setLeftMenuElement('농성 로그', 'serv_detail_info_log_search_sit', '#LOG', $parent_index_10, true);
        // $left_menu[] = $this->setLeftMenuElement('요충지 로그', 'serv_detail_info_log_search_point', '#LOG', $parent_index_10, true);
        // $left_menu[] = $this->setLeftMenuElement('섬멸전 로그', 'serv_detail_info_log_search_raid', '#LOG', $parent_index_10, true);
        $left_menu[] = $this->setLeftMenuElement('점령 포인트 로그', 'serv_detail_info_log_search_occupation', '#LOG', $parent_index_10, true);
        $left_menu[] = $this->setLeftMenuElement('황건적 토벌 로그', 'serv_detail_info_log_search_suppress', '#LOG', $parent_index_10, true);

        $left_menu[] = $this->setLeftMenuElement('동맹 로그', 'serv_detail_info_log_search_alliance', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('조합 로그', 'serv_detail_info_log_search_combi', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('영지 관련 로그', 'serv_detail_info_log_search_territory', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('명칭 변경 로그', 'serv_detail_info_log_search_change_name', '#LOG', $parent_index_3, true);
        // $left_menu[] = $this->setLeftMenuElement('급여 지급 로그', 'serv_detail_info_log_search_salary', '#LOG', $parent_index_3, true);
        // $left_menu[] = $this->setLeftMenuElement('임시 로그', 'serv_detail_info_log_search_temp', '#LOG', $parent_index_3, true);
        // $left_menu[] = $this->setLeftMenuElement('이벤트 로그', 'serv_detail_info_log_search_etc', '#LOG', $parent_index_3, true);
        // $left_menu[] = $this->setLeftMenuElement('친구 선물 로그 조회', 'serv_detail_info_log_friend_gift', '#LOG', $parent_index_3, true);
        $left_menu[] = $this->setLeftMenuElement('접속 로그 조회', 'serv_detail_info_log_search_login', '#LOG#PGGM', $parent_index_3, true);
        // $left_menu[] = $this->setLeftMenuElement('출석 이벤트 로그 조회', 'serv_detail_info_log_attendance_event', '#LOG', $parent_index_3, true);

        $left_menu[] = $this->setLeftMenuElement('서버 점검', 'serv_info_inspect', '#SCOMMAND', $parent_index_2, true);
        // $left_menu[] = $this->setLeftMenuElement('슈퍼 유저', 'serv_info_superlogin', '#BLOCK', $parent_index_2, true);
        $left_menu[] = $this->setLeftMenuElement('유저제재', null, 	'#BLOCK#SCOMMAND', $parent_index_2, true);

        $parent_index_5 =  count($left_menu) - 1;

        $left_menu[] = $this->setLeftMenuElement('유저킥', 'serv_info_user_kick', '#BLOCK', $parent_index_5, true);
        // $left_menu[] = $this->setLeftMenuElement('전체 유저킥', 'serv_info_all_user_kick', '#SCOMMAND', $parent_index_5, true);
        $left_menu[] = $this->setLeftMenuElement('유저 차단', 'serv_info_user_block', '#BLOCK', $parent_index_5, true);
        $left_menu[] = $this->setLeftMenuElement('유저 차단 해제', 'serv_info_user_block_clear', '#BLOCK', $parent_index_5, true);
        $left_menu[] = $this->setLeftMenuElement('채팅 차단', 'serv_info_chat_block', '#BLOCK', $parent_index_5, true);
        $left_menu[] = $this->setLeftMenuElement('채팅 차단 해제', 'serv_info_chat_block_clear', '#BLOCK', $parent_index_5, true);
        $left_menu[] = $this->setLeftMenuElement('영웅 검색', 'serv_info_hero_info', '#EDIT', $parent_index_2, true);
        $left_menu[] = $this->setLeftMenuElement('장수 지급', 'serv_info_hero_give', '#EDIT', $parent_index_2, true);
        // $left_menu[] = $this->setLeftMenuElement('응모권 이벤트 TOP 100', 'serv_info_enter_event', '#ENQUINARY', $parent_index_2, true);
        $left_menu[] = $this->setLeftMenuElement('치트툴', 'goto_cheat_tool', '#CHEAT', $parent_index_2, true);


        $left_menu[] = $this->setLeftMenuElement("[&nbsp;{$selected_lord}&nbsp;] 군주 정보", null, '#ENQUINARY#EDIT#PGGM', 0, true, true);

        $parent_index_6 =  count($left_menu) - 1;

        $left_menu[] = $this->setLeftMenuElement('기본 정보', 'lord_info', '#ENQUINARY#EDIT#PGGM', $parent_index_6, true, true);
        $left_menu[] = $this->setLeftMenuElement('영지 정보', 'territory_info', '#ENQUINARY#EDIT', $parent_index_6, true, true);
        $left_menu[] = $this->setLeftMenuElement('영지 버프 정보', 'territory_buff_info', '#ENQUINARY#EDIT', $parent_index_6, true, true);
        $left_menu[] = $this->setLeftMenuElement('보유 영웅 기술', 'lord_own_skill', '#ENQUINARY', $parent_index_6, true, true);
        $left_menu[] = $this->setLeftMenuElement('보유 아이템', 'lord_own_item', '#ENQUINARY', $parent_index_6, true, true);
        $left_menu[] = $this->setLeftMenuElement('보유 영웅', null, 	'#ENQUINARY', $parent_index_6, true, true);

        $parent_index_7 =  count($left_menu) - 1;

        $left_menu[] = $this->setLeftMenuElement('등용 중 영웅', 'lord_own_hero_appointed', '#ENQUINARY', $parent_index_7, true, true);
        $left_menu[] = $this->setLeftMenuElement('등용 대기 중 영웅', 'lord_own_hero_guest', '#ENQUINARY', $parent_index_7, true, true);
        $left_menu[] = $this->setLeftMenuElement('영입 대기 중 영웅', 'lord_own_hero_visit', '#ENQUINARY', $parent_index_7, true, true);
        // $left_menu[] = $this->setLeftMenuElement('판매 중 영웅', 'lord_trade_hero', '#ENQUINARY', $parent_index_7, true, true);
        $left_menu[] = $this->setLeftMenuElement('퀘스트', null, 	'#ENQUINARY', $parent_index_6, true, true);

        $parent_index_8 =  count($left_menu) - 1;

        $left_menu[] = $this->setLeftMenuElement('퀘스트 현황', 'lord_own_quest', '#ENQUINARY', $parent_index_8, true, true);
        $left_menu[] = $this->setLeftMenuElement('진행 중인 퀘스트', 'lord_own_quest_progress', '#ENQUINARY', $parent_index_8, true, true);
        $left_menu[] = $this->setLeftMenuElement('보상 미완료 퀘스트', 'lord_own_quest_not_reward', '#ENQUINARY', $parent_index_8, true, true);
        $left_menu[] = $this->setLeftMenuElement('보상 완료 퀘스트', 'lord_own_quest_rewarded', '#ENQUINARY', $parent_index_8, true, true);
        // $left_menu[] = $this->setLeftMenuElement('친구  퀘스트 로그', 'lord_own_quest_friend', '#ENQUINARY', $parent_index_8, true, true);
        // $left_menu[] = $this->setLeftMenuElement('친구 초대 현황', 'lord_own_friend', '#ENQUINARY', $parent_index_6, true, true);
        $left_menu[] = $this->setLeftMenuElement('외교서신 형황', 'lord_own_letter', '#ENQUINARY', $parent_index_6, true, true);
        $left_menu[] = $this->setLeftMenuElement('보고서 현황', 'lord_own_report', '#ENQUINARY', $parent_index_6, true, true);
        $left_menu[] = $this->setLeftMenuElement('동맹 현황', 'lord_own_alliance', '#ENQUINARY', $parent_index_6, true, true);
        $left_menu[] = $this->setLeftMenuElement('출석 이벤트 현황', 'attendance_event', '#ENQUINARY', $parent_index_6, true, true);
        // $left_menu[] = $this->setLeftMenuElement('섬멸전 현황', 'lord_own_raid_list', '#ENQUINARY', $parent_index_6, true, true);

        $left_menu[] = $this->setLeftMenuElement("[&nbsp;$selected_terr&nbsp;] 영지 정보", null, '#ENQUINARY#EDIT', 0, true, true);

        $parent_index_9 =  count($left_menu) - 1;

        $left_menu[] = $this->setLeftMenuElement('기본 정보', 'terr_info', 		'#ENQUINARY#EDIT', $parent_index_9, true, true, true);
        $left_menu[] = $this->setLeftMenuElement('외부 자원지', 'terr_outer_occupied', '#ENQUINARY', $parent_index_9, true, true, true);
        $left_menu[] = $this->setLeftMenuElement('보유 병력', 'terr_own_army', '#ENQUINARY', $parent_index_9, true, true, true);
        $left_menu[] = $this->setLeftMenuElement('보유 건물', 'terr_own_building', '#ENQUINARY', $parent_index_9, true, true, true);
        $left_menu[] = $this->setLeftMenuElement('태학 기술 현황', 'terr_own_technique', '#ENQUINARY', $parent_index_9, true, true, true);
        $left_menu[] = $this->setLeftMenuElement('소속 영웅', 'terr_own_hero', '#ENQUINARY', $parent_index_9, true, true, true);
        $left_menu[] = $this->setLeftMenuElement('소속 부대 주둔 현황', 'terr_camp_army', '#ENQUINARY', $parent_index_9, true, true, true);
        $left_menu[] = $this->setLeftMenuElement('동맹 부대 주둔 현황', 'terr_camp_ally_army', '#ENQUINARY', $parent_index_9, true, true, true);
        $left_menu[] = $this->setLeftMenuElement('영지 공격 현황', 'terr_troop_condition', '#ENQUINARY', $parent_index_9, true, true, true);

        return $left_menu;
    }

    public function drawLeftMenu ($_left_menu, $_index, $_server = false, $_lord = false, $_posi = false): string
    {
        $str = '';
        $menu_element = &$_left_menu[$_index];

        if ($menu_element->permission != null) {
            $is_allow = false;
            $menu_permission_arr = preg_split('/#{1}/', $menu_element->permission, -1, PREG_SPLIT_NO_EMPTY);
            foreach($menu_permission_arr as $v) {
                if (array_key_exists($v, $_SESSION['gm_permit']) && $_SESSION['gm_permit'][$v]) {
                    $is_allow = true;
                    break;
                }
            }
            if (! $is_allow) {
                return '';
            }
        }

        if ($_posi === true && ($_lord === false || $_server === false)) {
            $serv = false;
            $lord = false;
        }

        if ($_lord === true && $_server === false) {
            $_server = false;
        }

        if ($_posi === false && $menu_element->needPosiPK === true) {
            return '';
        }

        if ($_lord === false && $menu_element->needLordPK === true) {
            return '';
        }

        if ($_server === false && $menu_element->needServPK === true) {
            return '';
        }

        if (is_object($_left_menu[$_index]))
        {
            // 지금 요소를 부모로 가지는 모든 요소들을 모음
            $len = count($_left_menu);
            $children = [];
            $children_count = 0;
            for ($i = 0; $i < $len; $i++) {
                // 현재 요소를 제외하고 나머지 요소 중 현재 요소를 idx를 부모로 가지는 요소인 경우 해당 키를 모아놓음
                if ($i != $_index && $_left_menu[$i]->parentIdx == $_index) {
                    // 권한 검사
                    $is_allow = false;
                    $menu_permission_arr = preg_split('/#{1}/', $_left_menu[$i]->permission, -1, PREG_SPLIT_NO_EMPTY);
                    foreach($menu_permission_arr as $v) {
                        if (array_key_exists($v, $_SESSION['gm_permit']) && $_SESSION['gm_permit'][$v]) {
                            $is_allow = true;
                            break;
                        }
                    }
                    if ($is_allow === true) {
                        $children[$children_count++] = $i;
                    }
                }
            }
            // 지금 요소의 글자 써주기
            if (is_string($menu_element->text)) {
                if ($children_count > 0) {
                    // 자식을 가진 가지인 경우 링크는 없고 대신 클래스를 줘서 자스에서 클릭시 마다 li 요소들을 보였다 안보였다 하게 만든다.
                    $rootchild = ($menu_element->parentIdx == 0) ? " rootchild" : "";
                    $str .= "<div class='menu_header{$rootchild}'>" . $menu_element->text . "</div>\n";
                } else {
                    // 자식이 없는 잎 인 경우 링크를 걸어줌
                    if (isset($menu_element->view) && is_string($menu_element->view)) {
                        $str .= "<div><a href='?view={$menu_element->view}'>" . $menu_element->text . "</a></div>\n";
                    } else {
                        $str .= "<div>" . $menu_element->text . "</div>\n";
                    }
                }
            }

            // 자식 요소가 존재한다면 다시 UL 시작
            if ($children_count > 0) {
                $str .= "<ul>\n";
                $len = count($_left_menu);
                $i = 0;
                while($i < $len) {
                    if ($i != $_index && $_left_menu[$i]->parentIdx == $_index) {
                        $child_str = $this->drawLeftMenu($_left_menu, $i, $_server, $_lord, $_posi);
                        if (strlen($child_str) > 0) {
                            $str .= "<li>\n" . $child_str . "</li>\n";
                        }
                    }
                    $i += 1;
                }
                $str .= "</ul>\n";
            }
        }
        return $str;
    }

    public function gmLogDescription ($obj)
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['item', 'hero_base', 'hero_skill']);
        $str = $obj;
        $obj = unserialize($obj);

        if ($obj['action'] == 'login')
        {
            $str = 'GM툴로 로그인';
        }
        else if ($obj['action'] == 'logout')
        {
            $str = 'GM툴에서 로그아웃';
        }
        else if ($obj['action'] == 'change_pw')
        {
            $str = '비밀번호 변경';
        }
        else if ($obj['action'] == 'user_kick')
        {
            $str = '유저킥 실행 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명  : ' . $obj['lord']['lord_name'];
        }
        else if ($obj['action'] == 'superlogin')
        {
            $str = '슈퍼유저 로그인 실행 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'];
        }
        else if ($obj['action'] == 'user_block')
        {
            $str = '유저 블럭 실행 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . ' / 사유 : ' . $obj['lord']['block_cause'];
        }
        else if ($obj['action'] == 'user_block_clear')
        {
            $str = '유저 블럭 해제 실행 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . ' / 사유 : ' . $obj['lord']['block_clear_cause'];
        }
        else if ($obj['action'] == 'gm_del_item')
        {
            if (is_array($obj['lord']['decr_item_list']))
            {
                $item_str = '[';
                $cnt = 0;
                foreach($obj['lord']['decr_item_list'] as $k => $v)
                {
                    if ($cnt == 0)
                    {
                        $item_str .= ' ' . $_M['ITEM'][$v['m_item_pk']]['title'] . ' ' . $v['item_count'] . '개';
                        $cnt++;
                    } else {
                        $item_str .= ' , ' . $_M['ITEM'][$v['m_item_pk']]['title'] . ' ' . $v['item_count'] . '개';
                    }
                }
                $item_str .= ' ]';
            }

            $str = '유저 소지 아이템 회수 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . ' / 회수한 아이템 : ' . $item_str . ' / 회수 사유 : ' . $obj['lord']['cause'];
        }
        else if ($obj['action'] == 'gm_give_item')
        {
            $item_str = '[';
            if (is_array($obj['lord']['incr_item_list']))
            {
                foreach($obj['lord']['incr_item_list'] as $k => $v)
                {
                    if ($k == 0)
                    {
                        $item_str .= ' ' . $_M['ITEM'][$v['m_item_pk']]['title'] . ' ' . $v['item_count'] . '개';
                    } else {
                        $item_str .= ' , ' . $_M['ITEM'][$v['m_item_pk']]['title'] . ' ' . $v['item_count'] . '개 ';
                    }
                }
            }
            $item_str .= ' ]';
            $str = '유저 아이템 지급 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . ' / 지급한 아이템 : ' . $item_str . ' / 지급 사유 : ' . $obj['lord']['cause'];
        }
        else if ($obj['action'] == 'gm_del_skill')
        {
            $skill_str = '[';
            if (is_array($obj['lord']['decr_skill_list']))
            {
                $cnt = 0;
                foreach($obj['lord']['decr_skill_list'] as $k => $v)
                {
                    if ($cnt == 0)
                    {
                        $skill_str .= ' ' . $_M['HERO_SKILL'][$v['m_hero_skil_pk']]['title'] . ' Lv.' . $_M['HERO_SKILL'][$v['m_hero_skil_pk']]['rare'] . ' ' . $v['skill_count'] . '개';
                        $cnt++;
                    } else {
                        $skill_str .= ' , ' . $_M['HERO_SKILL'][$v['m_hero_skil_pk']]['title'] . ' Lv.' . $_M['HERO_SKILL'][$v['m_hero_skil_pk']]['rare'] . ' ' . $v['skill_count'] . '개';
                    }
                }
            }
            $skill_str .= ' ]';

            $str = '유저 소지 스킬 회수 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . ' / 회수한 스킬 : ' . $skill_str . ' / 회수 사유 : ' . $obj['lord']['cause'];
        }
        else if ($obj['action'] == 'gm_give_skill')
        {
            $skill_str = '[';
            if (is_array($obj['lord']['incr_skill_list']))
            {
                foreach($obj['lord']['incr_skill_list'] as $k => $v)
                {
                    if ($k == 0)
                    {
                        $skill_str .= ' ' . $_M['HERO_SKILL'][$v['m_hero_skil_pk']]['title'] . ' Lv.' . $_M['HERO_SKILL'][$v['m_hero_skil_pk']]['rare'] . ' ' . $v['skill_cnt'] . '개';
                    } else {
                        $skill_str .= ' , ' . $_M['HERO_SKILL'][$v['m_hero_skil_pk']]['title'] . ' Lv.' . $_M['HERO_SKILL'][$v['m_hero_skil_pk']]['rare'] . ' ' . $v['skill_cnt'] . '개 ';
                    }
                }
            }
            $skill_str .= ' ]';
            $str = '유저 스킬 지급 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . ' / 지급한 스킬 : ' . $skill_str . ' / 지급 사유 : ' . $obj['lord']['cause'];
        }
        else if ($obj['action'] == 'notice')
        {
            $serv_name = '';
            $notice_when = '예약 실행';

            if ($obj['notice_server'] == 'all')
            {
                $serv_name = '전체';
            } else {
                $Db = new Pg('GM');
                $Db->query('SELECT server_name FROM server WHERE server_pk = $1', [$obj['notice_server']]);
                $serv_name = $Db->fetchOne();
            }

            if ($obj['notice_type'] == 'now')
            {
                $notice_when = '즉시 실행';
            }

            $str = '서버 공지 실행 / 선택된 서버 : ' . $serv_name . ' / 공지 형태 : ' . $notice_when . ' / 공지 메시지 : ' . htmlspecialchars($obj['notice_msg']);
        }
        else if ($obj['action'] == 'change_lord_name')
        {
            $str = '군주명 변경 실행 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 변경된 군주명  : ' . $obj['lord']['lord_name'] . ' , 이전 군주명 : ' . $obj['prev_name'] . ' / 변경 사유 : ' . $obj['cause'];
        }
        else if ($obj['action'] == 'change_member_id')
        {
            $str = '군주명 변경 실행 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 변경된 이메일  : ' . $obj['member_id'] . ' , 이전 이메일 : ' . $obj['prev_id'] . ' / 변경 사유 : ' . $obj['cause'];
        }
        else if ($obj['action'] == 'change_flag_name')
        {
            $str = '깃발명 변경 실행 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명  : ' . $obj['lord']['lord_name'] . ' / 변경된 깃발명 : ' . $obj['changed_name'] . ' , 이전 깃발명 : ' . $obj['prev_name'] . ' / 변경 사유 : ' . $obj['cause'];
        }
        else if ($obj['action'] == 'change_terr_name')
        {
            $str = '영지명 변경 실행 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명  : ' . $obj['lord']['lord_name'] . ' / 변경된 영지명 : ' . $obj['terr']['title'] . ' , 이전 군주명 : ' . $obj['prev_name'] . ' / 변경 사유 : ' . $obj['cause'];
        }
        else if ($obj['action'] == 'gm_modify_qbig')
        {
            $act = ($obj['modify_action'] == 'incr') ? '지급' : '차감';
            $str = "큐빅 {$act} / 선택된 서버 : " . $obj['selected_server']['server_name'] . ' / 대상 군주명  : ' . $obj['lord']['lord_name'] . ' / 수량 : ' . $obj['amount'] . ' / 사유 : ' . $obj['cause'];
        }
        else if ($obj['action'] == 'gm_give_hero')
        {
            $str = '장수 지급 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . ' / 지급 영웅 : ' . $_M['HERO_BASE'][$obj['m_hero_base_pk']]['name'] . ' Lv. ' . $obj['level'] . ' / 사유 : ' . $obj['cause'];
        }
        else if ($obj['action'] == 'do_inspect')
        {
            $state = '';
            if ($obj['do_action'] == 'on') $state = '점검 설정';
            else if ($obj['do_action'] == 'off') $state = '점검 해제';
            else if ($obj['do_action'] == 'state') $state = '확인';
            $str = '서버 점검 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 상태 : ' . $state;
        }
        else if ($obj['action'] == 'all_user_kick')
        {
            $str = "전체 유저킥 실행 / 선택된 서버 : " . $obj['selected_server']['server_name'];
        }
        else if ($obj['action'] == 'inspect_allow_ip')
        {
            $state = ($obj['do_action'] == 'add') ? '추가' : '삭제';
            $str = "점검 중 접근 허용 아이피 {$state} / 선택된 서버 : " . $obj['selected_server']['server_name'] . ' / IP : ' . $obj['ip'];
        }
        else if ($obj['action'] == 'send_gm_letter')
        {
            $tstr = '';
            $cnt = 0;

            if($obj['receiver_list'])
            {
                foreach($obj['receiver_list'] as $k => $v)
                {
                    if ($cnt > 0)
                    {
                        $tstr .= ' , ';
                    }
                    foreach($_SESSION['server_list'] as $key => $value)
                    {
                        if ($k == $value['server_pk'])
                        {
                            $tstr .= $value['server_name'];
                        }
                    }
                    $tstr .= '[';
                    foreach($v as $key => $value)
                    {
                        if ($key > 0)
                        {
                            $tstr .= ',';
                        }
                        $tstr .= " {$value['lord_name']} ";
                    }
                    $tstr .= ']';
                    $cnt++;
                }
                $str = "GM 서신 발송 / 대상 : {$tstr} / 제목 : " . $obj['letter_body']['title'] . ' / 내용 : ' . htmlspecialchars($obj['letter_body']['content']);
                //$str = "GM 서신 발송 / 대상 : {$tstr} ".'<br /><br /><div style="padding:0.4em;background-color:#e0e0e0;border:2px solid #ccc;"><div style="height:18px;line-height:18px;">제목 : '.$obj['letter_body']['title'].'</div><hr /><pre>' . htmlspecialchars($obj['letter_body']['content']) . '</pre></div>';
            }
        }
        else if ($obj['action'] == 'change_quest_state')
        {
            $str = "퀘스트 상태 변경 / 선택된 서버 : " . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . ' / 상태 변경 : [ ' . $obj['now_state'] . ' ] 에서 [ ' . $obj['changed_state'] . '] 로 / 퀘스트명 : ' . $obj['selected_quest_title'] . ' / 사유  : ' . $obj['cause'];
        }
        else if ($obj['action'] == 'delete_qbig_pack')
        {
            $str = "큐빅팩 삭제 / 선택된 서버 : " . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . ' / 큐빅팩 타입 : ' . $obj['deleted_qbig_pack_info']['pack_type'] . ' / 등록번호 : ' . $obj['deleted_qbig_pack_info']['bill_chargeno'] . ' / 사유 : ' . $obj['cause'];
        }
        else if ($obj['action'] == 'change_lord_intro')
        {
            $str = "군주 인사말 변경 / 선택된 서버 : " . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . '<br /><br /><div style="padding:0.4em;background-color:#e0e0e0;border:2px solid #ccc;"><div style="height:18px;line-height:18px;">이전 군주 인사말</div><hr /><pre>' . $obj['prev_lord_intro'] . '</pre></div><br /><div style="padding:0.4em;background-color:#e0e0e0;border:2px solid #ccc;"><div style="height:18px;line-height:18px;">변경된 군주 인사말</div><hr /><pre>' . $obj['changed_lord_intro'] . '</pre></div>';
        }
        else if ($obj['action'] == 'change_alli_intro')
        {
            $str = "동맹 인사말 변경 / 선택된 서버 : " . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . '<br /><br /><div style="padding:0.4em;background-color:#e0e0e0;border:2px solid #ccc;"><div style="height:18px;line-height:18px;">이전 동맹 인사말</div><hr /><pre>' . $obj['prev_alli_intro'] . '</pre></div><br /><div style="padding:0.4em;background-color:#e0e0e0;border:2px solid #ccc;"><div style="height:18px;line-height:18px;">변경된 동맹 인사말</div><hr /><pre>' . $obj['changed_alli_intro'] . '</pre></div>';
        }
        else if ($obj['action'] == 'change_trade_gold')
        {
            $str = "황금 창고 보유량 수정 / 선택된 서버 : " . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . ' , 이전 황금 보유량 : ' . $obj['prev_trade_gold'] . ' , 수정된 황금 보유량 : ' . $obj['changed_trade_gold'];
        }
        else if ($obj['action'] == 'change_point_coin')
        {
            $str = "요충지코인 수정 / 선택된 서버 : " . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . ' , 이전 코인 보유량 : ' . $obj['prev_point_coin'] . ' , 수정된 코인 보유량 : ' . $obj['changed_point_coin'];
        }
        else if ($obj['action'] == 'change_hero_loyalty')
        {
            $str = "충성도 수정 / 선택된 서버 : " . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . ' , 대상 영웅 pk : ' . $obj['hero_pk'] . ' , 이전 충성도 : ' . $obj['prev_hero_loyalty'] . ' , 수정된 충성도 : ' . $obj['changed_hero_loyalty'];
        }
        else if ($obj['action'] == 'gm_equip_skill')
        {
            $str = '영웅 기술 장착 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . ' / 장착 스킬 : ' . $_M['HERO_SKILL'][$obj['m_hero_skil_pk']]['title'] . ' Lv.' . $_M['HERO_SKILL'][$obj['m_hero_skil_pk']]['rare'] . ' / 회수 사유 : ' . $obj['cause'];
        }
        else if ($obj['action'] == 'gm_unequip_skill')
        {
            $str = '영웅 기술 해제 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . ' / 해제 스킬 : ' . $_M['HERO_SKILL'][$obj['m_hero_skil_pk']]['title'] . ' Lv.' . $_M['HERO_SKILL'][$obj['m_hero_skil_pk']]['rare'] . ' / 회수 사유 : ' . $obj['cause'];
        }
        else if ($obj['action'] == 'attendance_event')
        {
            $str = '출석 이벤트 치트 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord']['lord_name'] . ' / 치트타입 : ' . $obj['type'];
        }
        else if ($obj['action'] == 'qbigpack_refund')
        {
            $str = '큐빅패키지 환불처리 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord_name'] . ' / 환불처리한 큐빅팩 번호 : ' . $obj['bill_chargeno'];
        }
        else if ($obj['action'] == 'qbigpack_refund_cancel')
        {
            $str = '큐빅패키지 환불취소처리 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주명 : ' . $obj['lord_name'] . ' / 환불취소한 큐빅팩 번호 : ' . $obj['bill_chargeno'];
        }
        else if ($obj['action'] == 'reward_letter')
        {
            $str = '일괄 서신 발송 / 선택된 서버 : ' . $obj['selected_server']['server_name'] . ' / 대상 군주 : ';
            if ($obj['mode'] === 'select_lord') {
                $str .= $obj['selected_lord'];
            } else if ($obj['mode'] === 'roamer_only') {
                $str .= '방랑 군주 대상';
            } else if ($obj['mode'] === 'roamer_exce') {
                $str .= '방랑 군주 제외';
            } else {
                $str .= '전체 군주 대상';
            }
            $str .= ' / 제목 : '.$obj['title'].' / 내용 : '.$obj['content'].' / 총 '.$obj['count'].'명의 군주에게 발송함. / 보상 목록 :';

            if ($obj['reward_list'] !== '') {
                Global $_M;
                $reward_list_all = explode('|', $obj['reward_list']);
                foreach ($reward_list_all AS $reward_list) {
                    $reward_type = explode('=', $reward_list);
                    $reward_data = explode(';', $reward_type[1]);
                    foreach ($reward_data AS $reward_info) {
                        $reward = explode(':', $reward_info);
                        switch ($reward_type[0]) {
                            case 'i':
                                $str .= ' ' . $_M['ITEM'][$reward[0]]['title'] . ' ' . $reward[1] . '개, ';
                                break;
                            default:
                                break;
                        }
                    }
                }
            }
        }
        return $str;
    }

    public function getViewData ($_view): array|null
    {
        $data = [];
        if ($_view == 'lord_info') {
            $data = $this->getViewLordData();
        } else if ($_view == 'territory_info') {
            $data = $this->getViewTerritoryData();
        } else if ($_view == 'territory_buff_info') {
            $data = $this->getViewTerritoryBuffData();
        }

        return (COUNT($data) < 1) ? null : $data;
    }

    public function getViewLordData (): array
    {
        $this->classPgGame();
        $this->classPgCommon();

        $data = [];

        $lord_pk = $_SESSION['selected_lord']['lord_pk'];
        // 큐빅 정보
        $this->PgGame->query('SELECT 100 cash_regist, cash remain_cash, use_cash as desc_spent FROM lord WHERE lord_pk = $1', [$lord_pk]);
        $this->PgGame->fetch();
        $data['qbig_row'] = $this->PgGame->row;

        $this->PgGame->query('SELECT sum(b.qbig_buy) AS buy_total, sum(b.qbig_bonus) AS bonus FROM m_qbig_pack b, qbig_pack a, lord c WHERE a.store_type = b.store_type AND a.pack_type = b.pack_type AND a.lord_pk = $1', [$lord_pk]);
        $this->PgGame->fetch();
        $data['qbig_row']['incr_cash'] = $this->PgGame->row['buy_total'] ?? 0;
        $data['qbig_row']['bonus_cash'] = $this->PgGame->row['bonus'] ?? 0;

        // 군주 정보
        $this->PgGame->query('SELECT lord.lord_pic, lord_web.web_id, lord_web.web_channel, date_part(\'epoch\', lord.regist_dt)::integer as regist_dt, 
                lord.status, lord.level, date_part(\'epoch\', lord.last_login_dt)::integer as last_login_dt, lord.position_cnt, lord.power, lord.alli_pk, lord.cash,
                lord.main_posi_pk, lord.is_logon, lord.lord_intro, lord.alli_intro,	lord.point_coin, lord.num_slot_guest_hero, lord.m_offi_pk, lord.platform, lord.udid, 
                lord.device_id, date_part(\'epoch\', lord.withdraw_dt)::integer as withdraw_dt FROM lord, lord_web WHERE lord.lord_pk = lord_web.lord_pk AND lord.lord_pk = $1', [$lord_pk]);
        $this->PgGame->fetch();
        $data['lord_info'] = $this->PgGame->row;
        $data['office_cnt'] = ($data['lord_info']['m_offi_pk'] - 110120 <= 0) ? 0 : $data['lord_info']['m_offi_pk'] - 110120;

        // 계정 정보
        $this->PgCommon->query('SELECT access_by, member_pk, device_pk, uid FROM account WHERE account_pk = $1', [$data['lord_info']['web_id']]);
        $this->PgCommon->fetch();
        $data['acco_info'] = $this->PgCommon->row;

        // 동맹 정보
        $this->PgGame->query('SELECT title FROM alliance WHERE alli_pk = $1', [$data['lord_info']['alli_pk']]);
        $data['alliance_title'] = $this->PgGame->fetchOne() ?? '-';

        // 생성시 채널 정보
        if (isset($data['acco_info']['device_pk'])) {
            $this->PgCommon->query('SELECT platform FROM device WHERE device_pk = $1', [$data['acco_info']['device_pk']]);
            $this->PgCommon->fetch();
            $data['device_info'] = $this->PgCommon->row;
        }

        // 맴버 계정 여부
        if (isset($data['acco_info']['member_pk'])) {
            $this->PgCommon->query('SELECT id, mailling, date_part(\'epoch\', regist_dt)::integer as regist_dt FROM ns_member WHERE member_pk = $1', [$data['acco_info']['member_pk']]);
            $this->PgCommon->fetch();
            $data['member_info'] = $this->PgCommon->row;
        }

        // 최종 큐빅 구매 정보 (최종이므로 LIMIT 1)
        $this->PgGame->query('SELECT q.buy_qbig, q.store_type, date_part(\'epoch\', q.buy_dt)::integer FROM lord l, qbig_pack q WHERE l.lord_pk = q.lord_pk AND l.lord_pk = $1 ORDER BY q.buy_dt DESC LIMIT 1', [$_SESSION['selected_lord']['lord_pk']]);
        $this->PgGame->fetch();
        $data['qbig_info'] = $this->PgGame->row;

        // 병력 정보
        $data['lord_own_army_list'] = [];
        $this->PgGame->query('SELECT army.posi_pk, worker, infantry, pikeman, scout, spearman, armed_infantry, archer, horseman, armed_horseman, transporter, bowman, battering_ram, catapult, adv_catapult, \'영지 주둔\' as note FROM army, position WHERE army.posi_pk = position.posi_pk AND position.lord_pk = $1', [$_SESSION['selected_lord']['lord_pk']]);
        while($this->PgGame->fetch()) {
            $data['lord_own_army_list'][] = $this->PgGame->row;
        }
        $this->PgGame->query('SELECT src_posi_pk as posi_pk, army_worker as worker, army_infantry as infantry, army_pikeman as pikeman, army_scout as scout, army_spearman as spearman, army_armed_infantry as armed_infantry, army_archer as archer, army_horseman as horseman, army_armed_horseman as armed_horseman, army_transporter as transporter, army_bowman as bowman, army_battering_ram as battering_ram, army_catapult as catapult, army_adv_catapult as adv_catapult, to_position as note FROM troop WHERE src_lord_pk = $1', [$_SESSION['selected_lord']['lord_pk']]);
        while($this->PgGame->fetch()) {
            $data['lord_own_army_list'][] = $this->PgGame->row;
        }

        return $data;
    }

    public function getViewTerritoryData () : array
    {
        $this->classPgGame();

        $data = [];
        $this->PgGame->query('SELECT t.posi_pk, p.state, t.title, t.loyalty, t.tax_rate, t.flag FROM territory as t INNER join position as p ON p.lord_pk = $1 AND p.posi_pk = t.posi_pk AND p.type = $2', [$_SESSION['selected_lord']['lord_pk'], 'T']);
        $this->PgGame->fetchAll();
        $data['territory_rows'] = $this->PgGame->rows;

        return $data;
    }

    public function getViewTerritoryBuffData () : array
    {
        $this->classPgGame();

        $data = [];

        $this->PgGame->query('SELECT  p.posi_pk, b.terr_item_buff_pk, b.m_item_pk, b.buff_time, t.time_pk, t.description, t.build_time,
        COALESCE(date_part(\'epoch\', t.start_dt)::integer, 0) as start_dt, COALESCE(date_part(\'epoch\', t.end_dt)::integer, 0) as end_dt from
	        position as p inner join territory_item_buff as b on p.posi_pk = b.posi_pk  left outer join timer as t on
	        t.posi_pk = b.posi_pk and t.queue_pk = b.terr_item_buff_pk and t.queue_type = $2 where p.lord_pk = $1 AND p.type = $3', [$_SESSION['selected_lord']['lord_pk'], 'B', 'T']);
        $this->PgGame->fetchAll();
        $data['buff_rows'] = $this->PgGame->rows;

        return $data;
    }
}