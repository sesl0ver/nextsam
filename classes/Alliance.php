<?php

class Alliance
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Lord $Lord;
    protected Hero $Hero;
    protected Timer $Timer;
    protected Quest $Quest;
    protected Letter $Letter;
    protected i18n $i18n;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->i18n = i18n::getInstance();
    }

    protected function classLord (): void
    {
        if (! isset($this->Lord)) {
            $this->Lord = new Lord($this->Session, $this->PgGame);
        }
    }

    protected function classHero (): void
    {
        if (! isset($this->Hero)) {
            $this->Hero = new Hero($this->Session, $this->PgGame);
        }
    }

    protected function classQuest (): void
    {
        if (! isset($this->Quest)) {
            $this->Quest = new Quest($this->Session, $this->PgGame);
        }
    }

    protected function classLetter (): void
    {
        if (! isset($this->Letter)) {
            $this->Letter = new Letter($this->Session, $this->PgGame);
        }
    }

    protected function classTimer (): void
    {
        if (! isset($this->Timer)) {
            $this->Timer = new Timer($this->Session, $this->PgGame);
        }
    }

    protected function classLog (): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    function getRelation($_lord_pk = null): void
    {
        // TODO 사용여부 확인 필요.
        $this->PgGame->query('SELECT rel_alli_pk, rel_type FROM alliance_relation WHERE alli_pk = $1', [$this->getAlliancePK($_lord_pk)]);

        $alliance_relations = [];
        while ($this->PgGame->fetch()) {
            $alliance_relations[$this->PgGame->row['rel_alli_pk']] = $this->PgGame->row['rel_type'];
        }
        $this->Session->sqAppend('ALLY_RELA', $alliance_relations);
    }

    function get($_lord_pk = null): true
    {
        $this->PgGame->query('SELECT a.title, b.level FROM alliance a, alliance_member b WHERE a.alli_pk = $1 AND b.lord_pk = $2', [$this->getAlliancePK($_lord_pk), $_lord_pk]);
        $this->PgGame->fetchAll();
        if ($this->Session->lord['lord_pk'] == $_lord_pk) {
            $this->Session->sqAppend('ALLI', $this->PgGame->rows);
        } else {
            $this->Session->sqAppend('PUSH', ['ALLIANCE_TITLE_UPDATE' => true], null, $_lord_pk);
        }
        return true;
    }

    // 동맹 PK 찾기
    function getAlliancePK($_lord_pk): int
    {
        $this->PgGame->query('SELECT alli_pk FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        return $this->PgGame->fetchOne() ?? 0;
    }

    // 군주 동맹pk update
    function updateAlliancePk(): void
    {
        $this->Session->setLoginReload();
    }

    // 동맹 창설
    function make($_lord_pk, $_posi_pk, $_in_cast_pk, $_title): bool
    {
        global $NsGlobal;
        if ($this->Session->lord['alli_pk']) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_already_join_alliance'));
            return false;
        }

        $level = $this->getEmbassyLevel($_posi_pk, $_in_cast_pk);
        if ($level < 2) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_plz_construct_embassy_level_2')); // 2레벨 이상의 대사관에서만 창설이 가능합니다.
            return false;
        }

        $this->PgGame->query('SELECT alli_pk FROM alliance WHERE title_lower = lower($1)', [$_title]);
        if ($this->PgGame->fetch()) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_name_same')); // 동일한 동맹명이 존재합니다.
            return false;
        }

        $ret = Useful::forbiddenWord($_title);
        if (!$ret['ret']) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_name_unavailable_1'), [$ret['str']]); // '입력하신 동맹명의 ['..']은(는) 사용할 수 없습니다.'
            return false;
        }

        // 예약어 검사
        if(!Useful::reservedWord($_title)) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_name_unavailable_2', [$_title])); // '입력하신 동맹명은 사용할 수 없습니다.['..']'
            return false;
        }

        // 군주의 공격, 수비 포인트
        $this->PgGame->query('SELECT attack_point, defence_point FROM lord_point WHERE lord_pk = $1', [$_lord_pk]);
        $this->PgGame->fetch();
        $lord_point = $this->PgGame->row;

        // 트랜잭션
        try {
            $this->PgGame->begin();

            global $_NS_SQ_REFRESH_FLAG;
            $_NS_SQ_REFRESH_FLAG = true;

            // 동맹 정보
            $this->PgGame->query('INSERT INTO alliance (title, master_lord_pk, lord_name, now_member_count, max_member_count, attack_point, defence_point, power, introduce, notice, regist_dt, title_lower) 
VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, now(), lower($11))', [$_title, $_lord_pk, $this->Session->lord['lord_name'], 0, 20, $lord_point['attack_point'], $lord_point['defence_point'], $this->Session->lord['power'], null, null, $_title]);

            $alli_pk = $this->PgGame->currSeq('alliance_alli_pk_seq');
            if (!$alli_pk) {
                throw new Exception($this->i18n->t('ally_create_fail')); // '동맹 창설 실패'
            }

            // 랭킹 정보
            $this->PgGame->query('INSERT INTO ranking_alliance (alli_pk, title, power_rank, power, attack_point_rank, attack_point, defence_point_rank, defence_point) 
VALUES ($1, $2, null, $3, 0, $4, 0, $5)', [$alli_pk, $_title, $this->Session->lord['power'], $lord_point['attack_point'], $lord_point['defence_point']]);

            // 동맹원 추가
            $r = $this->addAllianceMember($_lord_pk, $alli_pk, 1);
            if (!$r) {
                throw new Exception($this->i18n->t('ally_join_fail')); // '동맹 가입 실패'
            }

            $this->PgGame->commit();
        } catch (Exception $e) {
            // 실패, sq 무시
            $this->PgGame->rollback();
            throw new ErrorHandler('error', $e->getMessage(), true);
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        // 조인 리스트 삭제
        $this->deleteJoinList($_lord_pk);


        $this->get($_lord_pk);

        // position 정보 업데이트
        $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE lord_pk = $1 AND type = $2', [$_lord_pk, 'T']);

        //Log
        $this->classLog();
        $this->classLord();
        $lord_name = $this->Lord->getLordName($_lord_pk);
        $this->Log->setBuildingAlliance($_lord_pk, $_posi_pk, 'alliance_make', "alli_pk[$alli_pk];title[$_title];admin_pk[$_lord_pk];admin_name[$lord_name];");


        // 퀘스트 체크
        $this->classQuest();
        $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'alliance', 'type' => 'create']);

        return true;
    }

    // 동맹원 추가
    function addAllianceMember($_lord_pk, $_alli_pk, $_level): bool
    {
        global $NsGlobal;
        if ($this->getAlliancePK($_lord_pk)) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_already_join_alliance')); // '이미 동맹에 가입되어 있습니다.'
            return false;
        }

        // 최대값 검사
        if (!$this->joinPossibleNumber($_alli_pk)) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_full_member_join_fail')); // '동맹 가입 최대 인원수가 넘어서 가입할 수 없습니다.'
            return false;
        }

        $r = $this->PgGame->query('INSERT INTO alliance_member (lord_pk, alli_pk, level, regist_dt) VALUES ($1, $2, $3, now())', [$_lord_pk, $_alli_pk, $_level]);
        if (!$r || $this->PgGame->getAffectedRows() == 0) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'alliance_member table insert failed;lord_pk['.$_lord_pk.'];alli_pk['.$_alli_pk.'];');
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_add_member_error', ['1002']));
            return false;
        }

        $r = $this->PgGame->query('UPDATE alliance SET now_member_count = now_member_count + 1 WHERE alli_pk = $1', [$_alli_pk]);
        if (!$r || $this->PgGame->getAffectedRows() == 0) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'alliance table now_member_count update failed;lord_pk['.$_lord_pk.'];alli_pk['.$_alli_pk.'];');
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_add_member_error', ['1003']));
            return false;
        }

        // 로드 정보 업데이트
        $this->classLord();
        $this->Lord->updateAlliancePK($_lord_pk, $_alli_pk);
        $this->get($_lord_pk);

        // position 정보 업데이트
        $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE lord_pk = $1 AND type = $2', [$_lord_pk, 'T']);

        // 가입 승인
        $this->Session->sqAppend('PUSH', ['ALLIANCE_JOIN_RECEIVE' => true], null, $_lord_pk);

        // 퀘스트 체크
        $this->classQuest();
        $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'alliance', 'type' => 'join']);

        // 동맹 채팅 접근을 위한 Push 발송
        $this->Session->sqAppend('UPDATE_ALLIANCE', ['alli_pk' => $_alli_pk], null, $_lord_pk);

        //Log
        $this->classLog();
        $this->classLord();
        $lord_name = $this->Lord->getLordName($_lord_pk);
        $this->Log->setBuildingAlliance($_lord_pk, null, 'add_member', "alli_pk[$_alli_pk];lord_pk[$_lord_pk];lord_name[$lord_name];admin_pk[{$this->Session->lord['lord_pk']}];admin_name[{$this->Session->lord['lord_name']}];");

        // 점령선포 해제
        $str = '';

        // 우호 동맹 점령 선포 해제
        $this->PgGame->query('SELECT rel_alli_pk FROM alliance_relation WHERE alli_pk = $1 AND rel_type = $2', [$_alli_pk, 'F']);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        if ($rows) {
            foreach($rows AS $v) {
                $str .= ', ' . 	$v['rel_alli_pk'];
            }
        }

        $this->PgGame->query('SELECT lord_pk FROM alliance_member WHERE alli_pk IN ('.$_alli_pk . $str .') AND lord_pk != $1', [$_lord_pk]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;

        // 동맹원 점령 선포 해제
        if ($rows) {
            $str = '';
            $str_lord = '';
            foreach($rows AS $v) {
                $this->PgGame->query('SELECT posi_pk, lord_pk FROM position WHERE lord_pk = $1 AND type = $2', [$v['lord_pk'], 'T']);
                $this->PgGame->fetchAll();
                $posi_rows = $this->PgGame->rows;
                if ($posi_rows) {
                    foreach($posi_rows AS $v1) {
                        $str .= '\'' .$v1['posi_pk'] . '\',';
                        $str_lord .= $v1['lord_pk'] . ',';
                    }
                }
            }

            if ($str) {
                $str = substr($str, 0, -1);
            }
            if ($str_lord) {
                $str_lord = substr($str_lord, 0, -1);
            }

            // 내가 점령선포 한것 삭제
            $this->PgGame->query('SELECT att_posi_pk, def_posi_pk, att_time_pk, def_time_pk, att_lord_pk, def_lord_pk FROM occupation_inform WHERE att_lord_pk = $1 AND def_lord_pk IN (' . $str_lord .')', [$_lord_pk]);
            $this->PgGame->fetchAll();
            $rows = $this->PgGame->rows;

            if ($rows) {
                $this->classTimer();
                $str = '';
                foreach ($rows AS $v) {
                    // 타이머 취소
                    $this->Timer->cancel($v['att_time_pk'], $v['att_lord_pk']);
                    $this->Timer->cancel($v['def_time_pk'], $v['def_lord_pk']);
                    $str .= ', \'' .$v1['posi_pk'] . '\'';
                }
            }

            // position update
            $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE (lord_pk = $1 AND type = $2) OR (posi_pk IN (\'0x0\''. $str .'))', [$_lord_pk, 'T']);

            // 전쟁 선포 삭제
            $this->PgGame->query('DELETE FROM occupation_inform WHERE att_lord_pk = $1 AND def_lord_pk IN (' . $str_lord .')', [$_lord_pk]);

            // 내가 동맹원에게 점령선포 당한것
            $this->PgGame->query('SELECT att_posi_pk, def_posi_pk, att_time_pk, def_time_pk, att_lord_pk, def_lord_pk FROM occupation_inform WHERE def_lord_pk = $1 AND att_lord_pk IN (' . $str_lord .')', [$_lord_pk]);
            $this->PgGame->fetchAll();
            $rows = $this->PgGame->rows;

            if ($rows) {
                $this->classTimer();
                $str = '';
                foreach ($rows AS $v) {
                    // 타이머 취소
                    $this->Timer->cancel($v['att_time_pk'], $v['att_lord_pk']);
                    $this->Timer->cancel($v['def_time_pk'], $v['def_lord_pk']);
                    $str .= ', \'' .$v1['posi_pk'] . '\'';
                }
            }

            // position update
            $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE (lord_pk = $1 AND type = $2) OR (posi_pk IN (\'0x0\''. $str .'))', [$_lord_pk, 'T']);

            // 전쟁 선포 삭제
            $this->PgGame->query('DELETE FROM occupation_inform WHERE def_lord_pk = $1 AND att_lord_pk IN (' . $str_lord .')', [$_lord_pk]);
        }

        return true;
    }

    // 동맹 초대할 군주검색
    function getLordList($_lord_pk, $_lord_name, $_page_num, $_page_type = null): false|array
    {
        global $NsGlobal;
        // 군주단위로 검사
        $level = $this->getAllEmbassyLevel($_lord_pk);
        if ($level < 1) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_plz_construct_embassy_invite'));
            return false;
        }

        $member_level = $this->getAllianceMemberLevel($_lord_pk);
        if ($member_level == 5) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_invite_fail_grade'));
            return false;
        }

        $lord_list = [];
        $page_cnt = ($_page_type == 'alliance_active') ? ALLIANCE_ACTIVE_PAGE_NUM : DEFAULT_PAGE_NUM;
        $offset_num = (($_page_num - 1) * $page_cnt);

        $this->PgGame->query("SELECT b.main_rank as rank, a.lord_pk, a.lord_name, a.power, b.attack_point, b.defence_point, a.level, a.position_cnt
FROM lord a, lord_point b WHERE a.lord_name like '%{$_lord_name}%' AND a.lord_pk = b.lord_pk AND a.lord_pk <> $3
ORDER BY rank, a.lord_pk DESC LIMIT $1 OFFSET $2", [$page_cnt, $offset_num, $_lord_pk]);
        while($this->PgGame->fetch()) {
            $lord_list['lord_pk_' . $this->PgGame->row['lord_pk']] = $this->PgGame->row;
        }

        if (COUNT($lord_list) <= 0) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_lord_search_fail')); // 입력하신 군주명은 존재하지 않습니다.<br />검색어를 확인해 주시기 바랍니다.
            return false;
        }

        return $lord_list;
    }

    // 동맹 초대할 군주검색 총갯수
    function getLordListTotalCount($_lord_name, $_lord_pk): int
    {
        $this->PgGame->query("SELECT COUNT(a.lord_pk) AS cnt FROM lord a, lord_point b WHERE a.lord_name like '%{$_lord_name}%' AND a.lord_pk = b.lord_pk AND a.lord_pk <> $1", [$_lord_pk]);
        return $this->PgGame->fetchOne();
    }

    // 특정 position 대사관 레벨
    function getEmbassyLevel($_posi_pk, $_in_cast_pk): int
    {
        $this->PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND in_castle_pk = $2', [$_posi_pk, $_in_cast_pk]);
        return $this->PgGame->fetchOne() ?? 0;
    }

    // 해당 군주 전체 대사관 최고 레벨
    function getAllEmbassyLevel($_lord_pk): int
    {
        $this->PgGame->query('SELECT MAX(level) FROM building_in_castle WHERE posi_pk IN (SELECT posi_pk FROM position WHERE lord_pk = $1) AND m_buil_pk = $2', [$_lord_pk, PK_BUILDING_EMBASSY]);
        return $this->PgGame->fetchOne() ?? 0;
    }

    // 특정 동맹원 동맹레벨
    function getAllianceMemberLevel($_lord_pk): int
    {
        try {
            $this->PgGame->query('SELECT level FROM alliance_member WHERE lord_pk = $1', [$_lord_pk]);
            return $this->PgGame->fetchOne();
        } catch (Throwable $e) {
            return 0;  // 동맹레벨을 찾을 수 없을땐 0을 return;
        }
    }

    // 가입 가능한지 여부 검사
    function joinPossibleNumber($_alli_pk): bool
    {
        $this->PgGame->query('SELECT now_member_count, max_member_count FROM alliance WHERE alli_pk = $1', [$_alli_pk]);
        $this->PgGame->fetch();
        $alliance = $this->PgGame->row;
        return !($alliance['max_member_count'] <= $alliance['now_member_count']);
    }

    // 동맹원 초대
    function inviteAllianceMember($_lord_pk): bool|array
    {
        global $NsGlobal;
        if ($this->getAlliancePK($_lord_pk)) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_already_join_alliance'));
            return false;
        }

        if ($this->getAllEmbassyLevel($_lord_pk) < 1) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_plz_construct_embassy_invite'));
            return false;
        }

        $alli_pk = $this->Session->lord['alli_pk'];
        $this->PgGame->query('SELECT alli_pk, lord_pk, join_type FROM alliance_join_list WHERE alli_pk = $1 AND lord_pk = $2', [$alli_pk, $_lord_pk]);
        if ($this->PgGame->fetch()) {
            if ($this->PgGame->row['alli_pk'] == $alli_pk) {
                // 이미 해당 동맹에 가입 신청했을 경우
                if ($this->PgGame->row['join_type'] == 'R') {
                    return $this->joinAccess($_lord_pk, $alli_pk); // $ret = $this->addAllianceMember($_lord_pk, $alli_pk, 5);
                } else {
                    $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_already_invite_lord')); // '이미 초대한 군주입니다.'
                    return false;
                }
            } else if ($this->PgGame->row['alli_pk']) {
                $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_already_join_request_other')); // '이미 다른 동맹에 가입 신청이 되어 있습니다.'
                return false;
            }
        } else {
            $this->PgGame->query('INSERT INTO alliance_join_list (alli_pk, lord_pk, join_type, join_dt) values ($1, $2, $3, now())', [$alli_pk, $_lord_pk, 'I']);

            // 서신 발송
            $ret = $this->allianceInfo($alli_pk);
            $title = $this->i18n->t('letter_alliance_invite_subject', [$ret['title']]); // '[시스템] '. $ret['title'] .' 동맹에서 귀하를 초대하였습니다.'
            // 수락, 수락 하시면 해당 동맹에 가입이 완료됩니다.
            // 거절 하시면 초대가 취소됩니다.
            // 수락에 내부링크 추가. 수락 선택시 동맹 가입 완료 팝업
            // 거절에 내부링크 추가. 거절 선택시 동맹 가입 거부 팝업
            $this->sendLetter($alli_pk, [$_lord_pk], ['title' => $title, 'content' => $this->i18n->t('letter_alliance_invite_content', [$ret['title']]), 'type' => 'S']);
        }

        // 주요활동 저장
        $this->PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $lord_name = $this->PgGame->fetchOne();

        $title = $this->i18n->t('msg_alliance_join_log', [$this->Session->lord['lord_name'], $lord_name]); // {{1}} 님이 {{2}}님을 동맹에 초대 하였습니다.
        $this->saveAllianceActivity($alli_pk, $title, 'G');

        $this->classLog();
        $this->classLord();
        $lord_name = $this->Lord->getLordName($_lord_pk);
        $this->Log->setBuildingAlliance($_lord_pk, null, 'invite_member', "alli_pk[$alli_pk];lord_pk[{$_lord_pk}];lord_name[{$lord_name}];admin_pk[{$this->Session->lord['lord_pk']}];admin_name[{$this->Session->lord['lord_name']}];");

        return true;
    }

    // 내동맹 정보
    function myAllianceInfo($_alli_pk): bool|array
    {
        $this->PgGame->query('SELECT power_rank as rank, a.title, lord_name, now_member_count, max_member_count, a.power, a.attack_point, a.defence_point, introduce, notice  FROM alliance as a LEFT OUTER JOIN ranking_alliance as b ON b.alli_pk = a.alli_pk WHERE a.alli_pk = $1', [$_alli_pk]);
        $this->PgGame->fetch();
        return $this->PgGame->row;
    }

    // 타동맹 정보
    function allianceInfo($_alli_pk): bool|array
    {
        $this->PgGame->query('SELECT a.alli_pk, power_rank as rank, a.title, lord_name, now_member_count, max_member_count, a.power, a.attack_point, a.defence_point, introduce, notice FROM alliance as a LEFT OUTER JOIN ranking_alliance as b ON b.alli_pk = a.alli_pk WHERE a.alli_pk = $1', [$_alli_pk]);
        $this->PgGame->fetch();
        return $this->PgGame->row;
    }

    // 동맹 관계 정보
    function getAllianceDiplomacy($_alli_pk, $_rel_alli_pk)
    {
        $this->PgGame->query('SELECT rel_type FROM alliance_relation WHERE alli_pk = $1 AND rel_alli_pk = $2', [$_alli_pk, $_rel_alli_pk]);
        return $this->PgGame->fetchOne();
    }

    // 동맹 관계 리스트
    function allianceRelationInfo($_alli_pk): array
    {
        $this->PgGame->query('SELECT a.alli_pk, power_rank as rank, a.title, lord_name, now_member_count, max_member_count, a.power, a.attack_point, a.defence_point, introduce, notice FROM alliance as a LEFT OUTER JOIN ranking_alliance as b ON b.alli_pk = a.alli_pk WHERE a.alli_pk IN (SELECT rel_alli_pk FROM alliance_relation WHERE alli_pk = $1 ORDER BY regist_dt)', [$_alli_pk]);
        $alliance_list = [];
        while($this->PgGame->fetch()) {
            $alliance_list['alliance_' . $this->PgGame->row['alli_pk']] = $this->PgGame->row;
        }
        return $alliance_list;
    }

    // 동맹 관계 정보 총 갯수
    function getAllianceRelationInfoTotalCount($_alli_pk, $_rel_type): int
    {
        $this->PgGame->query('SELECT COUNT(alli_pk) FROM alliance WHERE alli_pk IN (SELECT rel_alli_pk FROM alliance_relation WHERE alli_pk = $1 AND rel_type = $2)', [$_alli_pk, $_rel_type]);
        return $this->PgGame->fetchOne();
    }

    // 동맹원 목록
    function getAllianceMembers($_alli_pk, $_lord_pk, $_order_type, $_page_num, $_page_type = null): array
    {
        $_order_type = match ($_order_type) {
            'rank' => 'c.main_rank',
            'power' => 'b.power DESC',
            'attack_point' => 'c.attack_point DESC',
            'defence_point' => 'c.defence_point DESC',
            'lord_level' => 'b.level DESC',
            'position_cnt' => 'b.position_cnt DESC',
            default => 'a.level',
        };

        $page_cnt = ($_page_type == 'alliance_active') ? ALLIANCE_ACTIVE_PAGE_NUM : DEFAULT_PAGE_NUM;
        $offset_num = (($_page_num - 1) * $page_cnt);

        $this->PgGame->query('SELECT level FROM alliance_member WHERE lord_pk = $1', [$_lord_pk]);
        $_level = $this->PgGame->fetchOne();

        $this->PgGame->query("SELECT b.lord_pk, b.main_posi_pk, a.level, b.lord_hero_pk, b.lord_name, c.main_rank as rank, b.power, c.attack_point, c.defence_point, b.level as lord_level, b.is_logon, b.alli_intro
FROM alliance_member a, lord b, lord_point c 
WHERE a.alli_pk = $1 And a.lord_pk = b.lord_pk AND b.lord_pk = c.lord_pk 	
ORDER BY {$_order_type}, a.regist_dt LIMIT $2 OFFSET $3", [$_alli_pk, $page_cnt, $offset_num]);

        $alliance_list = [];
        while($this->PgGame->fetch()) {
            $alliance_list['alliance_' . $this->PgGame->row['lord_pk']] = $this->PgGame->row;
            if ($_level > 4) {
                $alliance_list['alliance_' . $this->PgGame->row['lord_pk']]['is_logon'] = '-';
            }
        }
        return $alliance_list;
    }

    function getMemberList ($_alli_pk): array
    {
        $this->PgGame->query('SELECT l.lord_pk, l.level, l.lord_name, l.main_posi_pk FROM alliance AS a LEFT JOIN lord AS l ON l.alli_pk = a.alli_pk WHERE a.alli_pk = $1', [$_alli_pk]);
        $this->PgGame->fetchAll();
        return $this->PgGame->rows;
    }

    // 동맹원 군주 정보찾기
    function getLordHeroInfo($_hero_pk): bool|array
    {
        $this->classHero();
        return $this->Hero->getMyHeroInfo($_hero_pk);
    }

    // 현재 동맹원수
    function getMemberCount($_alli_pk): int
    {
        $this->PgGame->query('SELECT now_member_count FROM alliance WHERE alli_pk = $1', [$_alli_pk]);
        return $this->PgGame->fetchOne();
    }

    // 최대 동맹원수
    function getMaxMemberCount($_alli_pk): int
    {
        $this->PgGame->query('SELECT max_member_count FROM alliance WHERE alli_pk = $1', [$_alli_pk]);
        return $this->PgGame->fetchOne();
    }

    // 동맹 전쟁 현황
    function getWarHistoryList($_alli_pk): array
    {
        $this->PgGame->query('SELECT a.alli_war_hist_pk, a.repo_pk, a.type, a.title, b.title as alliance_title, date_part(\'epoch\', a.regist_dt)::integer as regist_dt
FROM alliance_war_history a LEFT OUTER JOIN alliance b ON a.adve_alli_pk = b.alli_pk
WHERE a.alli_pk = $1 ORDER BY a.regist_dt DESC LIMIT 500', [$_alli_pk]);

        $alliance_list = [];
        while($this->PgGame->fetch()) {
            $alliance_list['alliance_' . $this->PgGame->row['alli_war_hist_pk']] = $this->PgGame->row;
        }
        return $alliance_list;
    }

    // 동맹 전쟁 현황 총갯수
    function getAllianceWarHistoryTotalCount($_alli_pk): int
    {
        $this->PgGame->query('SELECT COUNT(alli_war_hist_pk) AS cnt FROM alliance_war_history WHERE alli_pk = $1', [$_alli_pk]);
        return $this->PgGame->fetchOne();
    }

    // 리포트 정보 저장(repo_pk, 내 동맹PK, 내 lord name, 타입(공격, 방어), 상대방 lord_name, 발생좌표, 상대방 alli_pk)
    function setAllianceWarHistory($_repo_pk, $_alli_pk, $_lord_name, $_type, $_adve_lord_name, $_posi_pk, $_adve_alli_pk): true
    {
        $this->PgGame->query('SELECT type FROM position where posi_pk = $1', [$_posi_pk]);
        if ($this->PgGame->fetchOne() == 'P') {
            return true;
        }
        $title = sprintf('%s %s %s (%s)', $_lord_name, ($_type == 'A') ? '공격' : '방어', $_adve_lord_name, $_posi_pk);
        $this->PgGame->query('INSERT INTO alliance_war_history (repo_pk, alli_pk, type, adve_alli_pk, title, regist_dt)
VALUES ($1, $2, $3, $4, $5, now())', [$_repo_pk, $_alli_pk, $_type, $_adve_alli_pk, $title]);

        return true;
    }

    // 주요 활동
    function getAllianceHistory($_alli_pk, $_page_num): array
    {
        $offset_num = (($_page_num - 1) * DEFAULT_PAGE_NUM);
        $this->PgGame->query('SELECT alli_hist_pk, title, type, date_part(\'epoch\', regist_dt)::integer as regist_dt FROM alliance_history WHERE alli_pk = $1 ORDER BY alli_hist_pk DESC LIMIT $2 OFFSET $3', [$_alli_pk, DEFAULT_PAGE_NUM, $offset_num]);
        $alliance_list = [];
        while($this->PgGame->fetch()) {
            $alliance_list['alliance_' . $this->PgGame->row['alli_hist_pk']] = $this->PgGame->row;
        }
        return $alliance_list;
    }

    // 주요활동 총 갯수
    function getAllianceHistoryTotalCount($_alli_pk): int
    {
        $this->PgGame->query('SELECT COUNT(alli_hist_pk) AS cnt FROM alliance_history WHERE alli_pk = $1', [$_alli_pk]);
        return $this->PgGame->fetchOne();
    }

    // 동맹 랭킹
    function getAllianceRanking($_type, $_page_num): array
    {
        $offset_num = (($_page_num - 1) * DEFAULT_PAGE_NUM);
        $order_type = $_type . '_rank';

        //타입이 R인것 찾기
        $this->PgGame->query("SELECT a.alli_pk, a.{$order_type} as rank, b.title, b.lord_name, b.now_member_count, b.max_member_count, 
a.power, a.attack_point, a.defence_point 
FROM ranking_alliance a, alliance b 
WHERE a.alli_pk = b.alli_pk 
ORDER BY {$order_type} LIMIT $1 OFFSET $2", [DEFAULT_PAGE_NUM, $offset_num]);

        $alliance_list = [];
        while($this->PgGame->fetch()) {
            $alliance_list['alliance_' . $this->PgGame->row['alli_pk']] = $this->PgGame->row;
        }
        return $alliance_list;
    }

    // 동맹 랭킹 총 갯수
    function getAllianceRankingTotalCount(): int
    {
        $this->PgGame->query('SELECT COUNT(alli_pk) AS cnt FROM ranking_alliance');
        return $this->PgGame->fetchOne();
    }

    // 가입 승인 리스트
    function getJoinList(): array
    {
        //타입이 R인것 찾기
        $this->PgGame->query('SELECT b.lord_pk, c.main_rank as rank, b.lord_name, b.power, c.attack_point, c.defence_point, b.level, b.position_cnt, date_part(\'epoch\', a.join_dt)::integer as join_dt FROM alliance_join_list a, lord b, lord_point c WHERE a.alli_pk = $1 AND a.join_type = $2 AND a.lord_pk = b.lord_pk AND b.lord_pk = c.lord_pk ORDER BY regist_dt', [$this->Session->lord['alli_pk'], 'R']);

        $alliance_list = [];
        while($this->PgGame->fetch()) {
            $alliance_list['alliance_' . $this->PgGame->row['lord_pk']] = $this->PgGame->row;
        }
        return $alliance_list;
    }

    // 가입 승인 리스트 총갯수
    function getJoinListTotalCount($alli_pk): int
    {
        $this->PgGame->query('SELECT COUNT(b.lord_pk) as cnt FROM alliance_join_list a, lord b, lord_point c WHERE a.alli_pk = $1 AND a.join_type = $2 AND a.lord_pk = b.lord_pk AND b.lord_pk = c.lord_pk', [$alli_pk, 'R']);
        return $this->PgGame->fetchOne();
    }

    // 가입 승인
    function joinAccess($_lord_pk, $_alli_pk): bool|array
    {
        $ret = $this->addAllianceMember($_lord_pk, $_alli_pk, 5);
        if (!$ret) {
            return false;
        }

        $this->deleteJoinList($_lord_pk);

        // 서신 발송
        $ret = $this->allianceInfo($_alli_pk);

        $this->sendLetter($_alli_pk, [$_lord_pk], [
            'title' => $this->i18n->t('letter_alliance_join_subject', [$ret['title']]), // '[시스템] {{1}} 동맹에 가입이 되셨습니다.',
            'content' => $this->i18n->t('letter_alliance_join_content', [$ret['title']]), // {{1}} 동맹에 가입이 되셨습니다. 귀하는 동맹에 큰 도움이 될 것입니다.',
            'type' => 'S'
        ]);

        // 주요활동 저장
        $this->classLord();
        $lord_name = $this->Lord->getLordName($_lord_pk);
        $this->saveAllianceActivity($_alli_pk, $this->i18n->t('msg_alliance_join_member_log', [$lord_name]), 'G');

        $this->classLog();
        $this->Log->setBuildingAlliance($_lord_pk, null, 'join_accept', "alli_pk[$_alli_pk];lord_pk[{$_lord_pk}];lord_name[{$lord_name}];admin_pk[{$this->Session->lord['lord_pk']}];admin_name[{$this->Session->lord['lord_name']}];");

        return $ret;
    }

    // 가입 요청 리스트 삭제
    function deleteJoinList($_lord_pk): void
    {
        $this->PgGame->query('DELETE FROM alliance_join_list WHERE lord_pk = $1', [$_lord_pk]);
    }

    // 특정 동맹 가입 요청 리스트 삭제
    function refuseJoin($_lord_pk, $_alli_pk): void
    {
        $this->PgGame->query('DELETE FROM alliance_join_list WHERE lord_pk = $1 AND alli_pk = $2', [$_lord_pk, $_alli_pk]);

        $this->classLord();
        $this->classLog();
        $lord_name = $this->Lord->getLordName($_lord_pk);
        $this->Log->setBuildingAlliance($_lord_pk, null, 'join_refuse', "alli_pk[$_alli_pk];lord_pk[{$_lord_pk}];lord_name[{$lord_name}];admin_pk[{$this->Session->lord['lord_pk']}];admin_name[{$this->Session->lord['lord_name']}];");

    }

    // 동맹원 직책 변경
    function changeMemberLevel($_my_lord_pk, $_lord_pk, $_level): bool
    {
        global $NsGlobal;
        $alli_pk = $this->getAlliancePK($_my_lord_pk);
        if ($alli_pk != $this->getAlliancePK($_lord_pk)) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_grade_change_other_error'));
            return false;
        }

        if ($_my_lord_pk == $_lord_pk) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_grade_change_my_error')); // '본인의 직책은 변경할 수 없습니다.'
            return false;
        }

        $my_level = $this->getAllianceMemberLevel($_my_lord_pk);
        $level = $this->getAllianceMemberLevel($_lord_pk);
        if ($my_level >= $_level) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_grade_change_same_grade_error')); // '자신보다 낮은 직책의 동맹원의 직책만 변경할 수 있습니다.'
            return false;
        }
        if ($level == $my_level) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_grade_change_same_grade_error'));
            return false;
        }

        if ($my_level > $level) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_grade_change_high_grade_error')); // 자신보다 높은 직책의 동맹원 직책은 변경 할 수 없습니다.
            return false;
        }

        if ($my_level > 3) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_grade_change_inspection_error')); // 동맹 계급이 감찰이상만 다른 동맹원의 직책을 변경할 수 있습니다.
            return false;
        }

        /* TODO 중복 예외처리 코드
         * if ($my_level > $level) {
            $NsGlobal->setErrorMessage('자신보다 낮은 직책의 동맹원만 직책을 변경할 수 있습니다.');
            return false;
        }*/

        $this->PgGame->query('UPDATE alliance_member SET level = $1 WHERE lord_pk = $2', [$_level, $_lord_pk]);

        // 주요활동 저장
        $level_name = match ($_level) {
            1 => $this->i18n->t('alliance_captain'),
            2 => $this->i18n->t('alliance_vice_captain'),
            3 => $this->i18n->t('alliance_inspection'),
            4 => $this->i18n->t('alliance_executive'),
            default => $this->i18n->t('alliance_member')
        };
        $this->PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $lord_name = $this->PgGame->fetchOne();


        $log_text = $this->i18n->t('msg_alliance_grade_change_log', [$this->Session->lord['lord_name'], $lord_name, $level_name]); // // {{1}} 님이 {{2}} 님의 직책을 {{3}} (으)로 변경 하였습니다.
        $this->saveAllianceActivity($alli_pk, $log_text, 'G');
        $this->get($_lord_pk);

        //Log
        $this->classLog();
        $this->classLord();
        $lord_name = $this->Lord->getLordName($_lord_pk);
        $this->Log->setBuildingAlliance($_lord_pk, null, 'change_level', "alli_pk[$alli_pk];lord_pk[$_lord_pk];lord_name[$lord_name];admin_pk[{$this->Session->lord['lord_pk']}];admin_name[{$this->Session->lord['lord_name']}];");

        /* TODO 채팅 PUSH
         * global $Chat;
        if (!$Chat)
        {
            require_once_classes(Array('CChat'));
            $Chat = new CChat();
        }

        $Chat->send_announce_system_to_alli_channel($lord_name."님의 직책이 ".$level_name."으로 변경되셨습니다.", $alli_pk);*/

        return true;
    }

    // 동맹 양도
    function masterTransfer($_master_lord_pk, $_lord_pk, $_alli_pk): string|false
    {
        global $NsGlobal;
        if ($this->getAlliancePK($_master_lord_pk) != $this->getAlliancePK($_lord_pk)) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_grade_change_other_error'));
            return false;
        }

        if ($_master_lord_pk == $_lord_pk) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_cede_fail_my_error'));
            return false;
        }

        $this->PgGame->query('SELECT master_lord_pk FROM alliance WHERE alli_pk = $1', [$_alli_pk]);
        if ($this->PgGame->fetchOne() != $_master_lord_pk) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_cede_fail_master_error'));
            return false;
        }

        if ($this->getAllEmbassyLevel($_lord_pk) < 2) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_cede_embassy_level_2'));
            return false;
        }

        $this->classLord();
        $new_lord_name = $this->Lord->getLordName($_lord_pk);
        try {
            $this->PgGame->begin();

            $r = $this->PgGame->query('UPDATE alliance SET master_lord_pk = $2, lord_name = $3 WHERE alli_pk = $1', [$_alli_pk, $_lord_pk, $new_lord_name]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                throw new Exception($this->i18n->t('ally_cede_fail'));
            }

            $r = $this->PgGame->query('UPDATE alliance_member SET level = 5 WHERE lord_pk = $1', [$_master_lord_pk]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                throw new Exception($this->i18n->t('ally_cede_fail'));
            }

            $r = $this->PgGame->query('UPDATE alliance_member SET level = 1 WHERE lord_pk = $1', [$_lord_pk]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                throw new Exception($this->i18n->t('ally_cede_fail'));
            }

            $this->PgGame->commit();
        } catch (Exception $e) {
            $this->PgGame->rollback();

            // 에러 메시지 추가
            $NsGlobal->setErrorMessage($e->getMessage());

            // dubug_mesg남기기
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';alli_pk['.$_alli_pk.'];');

            return false;
        }

        // {{1}} 님이 {{2}} 님에게 맹주를 양도 하였습니다.
        $this->saveAllianceActivity($_alli_pk, $this->i18n->t('msg_alliance_cede_log', [$this->Session->lord['lord_name'], $new_lord_name]), 'R'); // {{1}} 님이 {{2}} 님에게 맹주를 양도 하였습니다.

        $this->get($_lord_pk);
        $this->get($_master_lord_pk);

        $this->classLog();
        $this->Log->setBuildingAlliance($_lord_pk, null, 'transfer_master', "alli_pk[$_alli_pk];lord_pk[$_lord_pk];lord_name[$new_lord_name];admin_pk[{$this->Session->lord['lord_pk']}];admin_name[{$this->Session->lord['lord_name']}];");

        /* TODO 채팅 PUSH
         * global $Chat;
        if (!$Chat)
        {
            require_once_classes(Array('CChat'));
            $Chat = new CChat();
        }

        $Chat->send_announce_system_to_alli_channel($title, $_alli_pk);*/

        return $new_lord_name;
    }

    // 주요 활동 저장
    function saveAllianceActivity($_alli_pk, $_title, $_type): void
    {
        $this->PgGame->query('INSERT INTO alliance_history (alli_pk, title, type, regist_dt) values ($1, $2, $3, now())', [$_alli_pk, $_title, $_type]);
    }

    // 동맹원 제명
    function memberExpulsion($_my_lord_pk, $_lord_pk, $_alli_pk): string|false
    {
        global $NsGlobal;
        if ($_alli_pk != $this->getAlliancePK($_lord_pk)) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_grade_change_other_error'));
            return false;
        }

        if ($_my_lord_pk == $_lord_pk) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_expulsion_fail_my_error')); // 본인을 제명 할 수 없습니다.
            return false;
        }

        $my_level = $this->getAllianceMemberLevel($_my_lord_pk);
        if ($my_level > 3) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_expulsion_fail_inspection_error')); // 동맹 계급이 감찰이상만 다른 동맹원을 제명 할 수 없습니다.
            return false;
        }


        if ($this->getAllianceMemberLevel($_lord_pk) < 5) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_expulsion_fail_master_error'));
            return false;
        }

        // 다른 군주가  공격 부대 출발 했을 경우
        $this->PgGame->query('SELECT dst_posi_pk FROM troop WHERE src_lord_pk <> $5 AND dst_lord_pk = $1 AND status IN ($2, $3) AND cmd_type = $4', [$_lord_pk, 'M', 'B', 'A', NPC_TROOP_LORD_PK]);
        if ($this->PgGame->fetch()) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_expulsion_fail_troop_error')); // 공격 중인 부대가 있을 때는 제명이 불가능 합니다.
            return false;
        }

        $this->PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $lord_name = $this->PgGame->fetchOne();

        try {
            $this->PgGame->begin();
            $this->PgGame->query('DELETE FROM alliance_member WHERE lord_pk = $1', [$_lord_pk]);

            $this->PgGame->query('UPDATE alliance SET now_member_count = now_member_count - 1 WHERE alli_pk = $1', [$_alli_pk]);
            $this->PgGame->commit();
        } catch (Throwable $e) {
            $this->PgGame->rollback();
            return false;
        }

        // 로드 정보 업데이트
        $this->classLord();
        $this->Lord->updateAlliancePK($_lord_pk, null);
        $this->get($_lord_pk);

        // 주요활동 저장
        $this->saveAllianceActivity($_alli_pk, $this->i18n->t('msg_alliance_expulsion_log', [$this->Session->lord['lord_name'], $lord_name]), 'R'); // {{1}} 님이 {{2}}님을 동맹에서 제명 하였습니다.

        // position 정보 업데이트
        $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE lord_pk = $1 AND type = $2', [$_lord_pk, 'T']);

        $this->Session->sqAppend('PUSH', ['ALLIANCE_RELATION_DELETE' => true], null, $_lord_pk);

        //Log
        $this->classLog();
        $lord_name = $this->Lord->getLordName($_lord_pk);
        $this->Log->setBuildingAlliance($_lord_pk, null, 'member_expulsion', "alli_pk[$_alli_pk];lord_pk[$_lord_pk];lord_name[$lord_name];admin_pk[{$this->Session->lord['lord_pk']}];admin_name[{$this->Session->lord['lord_name']}];");	// 동맹 PK : 제명한 사람

        // 동맹 채팅 처리
        $this->Session->sqAppend('UPDATE_ALLIANCE', ['alli_pk' => 0], null, $_lord_pk);
        $_alli_title = $this->getAllianceTitle($_alli_pk);

        $this->sendLetter($_alli_pk, [$_lord_pk], [
            'title' => $this->i18n->t('letter_alliance_expulsion_subject', [$_alli_title]),
            'content' => $this->i18n->t('letter_alliance_expulsion_content', [$_alli_title]),
            'type' => 'S'
        ]);

        return $lord_name;
    }

    // 직책 사직
    function memberResignation($_lord_pk, $_alli_pk): false|int
    {
        global $NsGlobal;
        $my_level = $this->getAllianceMemberLevel($_lord_pk);
        if ($my_level == 1) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_resign_fail_master_error')); // '맹주는 직책을 사직할 수 없습니다.'
            return false;
        }

        $this->PgGame->query('UPDATE alliance_member SET level = 5 WHERE lord_pk = $1', [$_lord_pk]);

        // 주요활동 저장
        $this->saveAllianceActivity($_alli_pk,  $this->i18n->t('msg_alliance_resign_log', [$this->Session->lord['lord_name']]), 'R');
        $this->get($_lord_pk);

        /* TODO 채팅 PUSH
         * global $Chat;
        if (!$Chat)
        {
            require_once_classes(Array('CChat'));
            $Chat = new CChat();
        }

        if ($my_level == 2)
        {
            $level_name = '부맹주';
        } else if ($my_level == 3) {
            $level_name = '감찰';
        } else if ($my_level == 4) {
            $level_name = '임원';
        }
        $Chat->send_announce_system_to_alli_channel($this->Session->lord['lord_name']."님이 ".$level_name." 직책에서 사직하셨습니다.", $_alli_pk);*/

        $this->classLog();
        $this->classLord();
        $lord_name = $this->Lord->getLordName($_lord_pk);
        $this->Log->setBuildingAlliance($_lord_pk, null, 'member_resignation', "alli_pk[$_alli_pk];lord_pk[$_lord_pk];lord_name[$lord_name];");

        return $my_level;
    }

    // 동맹 탈퇴
    function memberDropout($_lord_pk, $_alli_pk, $_posi_pk): bool
    {
        global $NsGlobal;
        $my_level = $this->getAllianceMemberLevel($_lord_pk);
        if ($my_level < 5) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_dropout_member_error')); // '탈퇴는 동맹원만 가능합니다.<br />직책을 사임해야 탈퇴가 가능합니다.'
            return false;
        }

        // 다른 군주에 지원군을 보냈을 경우
        $this->PgGame->query('SELECT dst_posi_pk FROM troop WHERE dst_lord_pk <> $5 AND src_lord_pk = $1 AND status IN ($2, $3) AND cmd_type = $4', [$_lord_pk, 'C', 'M', 'R', NPC_TROOP_LORD_PK]);
        if ($this->PgGame->fetch()) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_dropout_support_error')); // '지원 중인 부대가 있을 때는<br />탈퇴가 불가능 합니다.'
            return false;
        }

        // 다른 군주가  공격 부대 출발 했을 경우
        $this->PgGame->query('SELECT dst_posi_pk FROM troop WHERE src_lord_pk <> $5 AND dst_lord_pk = $1 AND status IN ($2, $3) AND cmd_type = $4', [$_lord_pk, 'M', 'B', 'A', NPC_TROOP_LORD_PK]);
        if ($this->PgGame->fetch()) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_dropout_troop_error')); // '공격 중인 부대가 있을 때는<br />탈퇴가 불가능 합니다.'
            return false;
        }

        try {
            $this->PgGame->begin();

            $this->PgGame->query('DELETE FROM alliance_member WHERE lord_pk = $1', [$_lord_pk]);
            $this->PgGame->query('UPDATE alliance SET now_member_count = now_member_count - 1 WHERE alli_pk = $1', [$_alli_pk]);

            $this->PgGame->commit();
        } catch (Throwable $e) {
            $this->PgGame->rollback();
            return false;
        }

        // 로드 정보 업데이트
        $this->classLord();
        $this->Lord->updateAlliancePK($_lord_pk, null);
        $this->get($_lord_pk);

        // 주요활동 저장
        $this->saveAllianceActivity($_alli_pk, $this->i18n->t('msg_alliance_dropout_log'), 'R'); // {{1}} 님이 동맹에서 탈퇴 하였습니다.

        // position 정보 업데이트
        $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE lord_pk = $1 AND type = $2', [$_lord_pk, 'T']);

        $this->Session->sqAppend('PUSH', ['ALLIANCE_RELATION_DELETE' => true], null, $_lord_pk);

        //Log
        $this->classLog();
        $lord_name = $this->Lord->getLordName($_lord_pk);
        $this->Log->setBuildingAlliance($_lord_pk, null, 'member_dropout', "alli_pk[$_alli_pk];lord_pk[$_lord_pk];lord_name[$lord_name];admin_pk[{$this->Session->lord['lord_pk']}];admin_name[{$this->Session->lord['lord_name']}];");

        // 동맹 채팅 처리
        $this->Session->sqAppend('UPDATE_ALLIANCE', ['alli_pk' => 0], null, $_lord_pk, $_posi_pk);

        return true;
    }

    // 동맹 폐쇄
    function memberCloseDown($_alli_pk, $_lord_pk): bool
    {
        global $NsGlobal;
        $my_level = $this->getAllianceMemberLevel($_lord_pk);
        if ($my_level != 1)  {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_close_master_error')); // 맹주만 폐쇄가 가능합니다.
            return false;
        }

        if ($this->getMemberCount($_alli_pk) > 1) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_close_member_error')); // 동맹원이 없을 경우에만 폐쇄가 가능합니다.
            return false;
        }

        // 다른 군주가  공격 부대 출발 했을 경우
        $this->PgGame->query('SELECT dst_posi_pk FROM troop WHERE src_lord_pk <> $5 AND dst_lord_pk = $1 AND status IN ($2, $3) AND cmd_type = $4', [$this->Session->lord['lord_pk'], 'M', 'B', 'A', NPC_TROOP_LORD_PK]);
        if ($this->PgGame->fetch()) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_close_troop_error')); // 공격 중인 부대가 있을 때는 폐쇄가 불가능 합니다.
            return false;
        }

        try {
            $this->PgGame->begin();
            $this->PgGame->query('DELETE FROM alliance_history WHERE alli_pk = $1', [$_alli_pk]);
            $this->PgGame->query('DELETE FROM alliance_join_list WHERE alli_pk = $1', [$_alli_pk]);
            $this->PgGame->query('DELETE FROM alliance_relation WHERE alli_pk = $1', [$_alli_pk]);
            $this->PgGame->query('DELETE FROM alliance_relation WHERE rel_alli_pk = $1', [$_alli_pk]);
            $this->PgGame->query('DELETE FROM alliance_war_history WHERE alli_pk = $1', [$_alli_pk]);
            $this->PgGame->query('DELETE FROM alliance_member WHERE alli_pk = $1', [$_alli_pk]);
            $this->PgGame->query('DELETE FROM alliance WHERE alli_pk = $1', [$_alli_pk]);
            $this->PgGame->commit();
        } catch (Throwable $e) {
            $this->PgGame->rollback();
            $NsGlobal->setErrorMessage($this->i18n->t('msg_error_alliance_close', [$e->getMessage()])); // 동맹 폐쇄 중 오류가 발생했습니다.
            return false;
        }

        // 로드 정보 업데이트
        $this->classLord();
        $this->Lord->updateAlliancePK($_lord_pk, null);
        $this->get($_lord_pk);

        // position 정보 업데이트
        $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE lord_pk = $1 AND type = $2', [$_lord_pk, 'T']);

        $this->Session->sqAppend('PUSH', ['ALLIANCE_RELATION_DELETE' => true], null, $_lord_pk);

        //Log
        $this->classLog();
        $this->Log->setBuildingAlliance($_lord_pk, null, 'alliance_close', "alli_pk[$_alli_pk];admin_pk[{$this->Session->lord['lord_pk']}];admin_name[{$this->Session->lord['lord_name']}];");

        $this->Session->sqAppend('UPDATE_ALLIANCE', ['alli_pk' => 0], null, $_lord_pk);

        /*global $Chat;
        if (!$Chat)
        {
            require_once_classes(Array('CChat'));
            $Chat = new CChat();
        }

        // 채팅 : 동맹 채널 나가기
        $Chat->left_alli_channel($_alli_pk, $_lord_pk);*/

        return true;
    }

    // 동맹 가입 신청
    function joinRequest($_alli_pk, $_lord_pk): bool|array
    {
        global $NsGlobal;
        if ($this->getAllEmbassyLevel($_lord_pk) < 1) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_plz_construct_embassy_join')); // 대사관이 있어야 동맹에 가입 가능합니다.
            return false;
        }

        // 내가 가입하려는 동맹에서 초대한 것이 있는지 검사
        $this->PgGame->query('SELECT alli_pk FROM alliance_join_list WHERE alli_pk = $1 AND lord_pk = $2 AND join_type = $3', [$_alli_pk, $_lord_pk, 'I']);
        if($this->PgGame->fetch()) {
            $ret = $this->addAllianceMember($_lord_pk, $_alli_pk, 5);
            if (!$ret) {
                return false;
            }

            // 초대및 가입 신청 한것 삭제
            $this->deleteJoinList($_lord_pk);

            $ret = $this->allianceInfo($_alli_pk);
            return ['alliance_name' => $ret['title']];
        }

        // 초대한 것이 없을경우 내가 가입한것 검사
        $this->classLord();
        $this->PgGame->query('SELECT alli_pk FROM alliance_join_list WHERE alli_pk = $1 AND lord_pk = $2 AND join_type = $3', [$_alli_pk, $_lord_pk, 'R']);
        if (!$this->PgGame->fetch()) {
            $this->PgGame->query('INSERT INTO alliance_join_list (alli_pk, lord_pk, join_type, join_dt) values ($1, $2, $3, now())', [$_alli_pk, $_lord_pk, 'R']);

            // 서신 발송
            $ret = $this->allianceInfo($_alli_pk);
            $this->sendLetter($_alli_pk, [$_lord_pk], [
                'title' => $this->i18n->t('letter_alliance_request_subject', [$ret['title']]),
                'content' => $this->i18n->t('letter_alliance_request_content', [$ret['title']]),
                'type' => 'S'
            ]);

            // 가입 승인을 할 수 있는 level 4 이하 (1~4)의 군주들에게 가입 신청 왔다고 서신 발송
            $this->PgGame->query('SELECT lord_pk FROM alliance_member WHERE alli_pk = $1 AND level < 5', [$_alli_pk]);
            if ($this->PgGame->fetchAll()) {
                if (count($this->PgGame->rows) > 0) {
                    $executive = $this->PgGame->rows;
                    $lord_name = $this->Lord->getLordName($_lord_pk);
                    foreach($executive as $v) {
                        $this->sendLetter($_alli_pk, $v, [
                            'title' => $this->i18n->t('letter_alliance_request_member_subject', [$lord_name, $ret['title']]),
                            'content' => $this->i18n->t('letter_alliance_request_member_content', [$lord_name]),
                            'type' => 'S'
                        ]);
                    }
                }
            }
        } else {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_already_join')); // 이미 가입 신청을 한 동맹입니다.
            return false;
        }

        //Log
        $this->classLog();
        $lord_name = $this->Lord->getLordName($_lord_pk);
        $this->Log->setBuildingAlliance($_lord_pk, null, 'join_request', "alli_pk[$_alli_pk];lord_pk[{$_lord_pk}];lord_name[{$lord_name}];");

        return true;
    }

    // 서신 발송 TODO alli_pk 왜 있냐?
    function sendLetter($_alli_pk, $_lord_pk, $_arr_letter): void
    {
        $this->classLetter();
        $this->Letter->sendLetter(2, $_lord_pk, $_arr_letter);
    }

    // 동맹 소개 변경
    function changeInfo($_alli_pk, $_lord_pk, $_title, $_introduce, $_notice): bool
    {
        global $NsGlobal;
        $my_level = $this->getAllianceMemberLevel($_lord_pk);
        if ($my_level > 2) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_change_fail_master_error')); // 맹주와 부맹주만 변경 가능합니다.
            return false;
        }

        $this->PgGame->query('SELECT alli_pk FROM alliance WHERE title_lower = lower($1) AND alli_pk <> $2', [$_title, $_alli_pk]);
        if ($this->PgGame->fetch()) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_name_same')); // 동일한 동맹명이 존재합니다.
            return false;
        }

        // 금지어 검사
        $ret = Useful::forbiddenWord($_title);
        if (!$ret['ret']) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_name_unavailable_1', [$ret['str']])); // '입력하신 동맹명의 ['..']은(는) 사용할 수 없습니다.'
            return false;
        }

        // 예약어 검사
        if(!Useful::reservedWord($_title)) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_name_unavailable_2', [$_title])); // '입력하신 동맹명은 사용할 수 없습니다. ['.$_title.']'
            return false;
        }

        $this->PgGame->query('UPDATE alliance SET title = $2, introduce = $3, notice = $4, title_lower = lower($5) WHERE alli_pk = $1', [$_alli_pk, $_title, $_introduce, $_notice, $_title]);

        // 주요활동 저장
        $this->saveAllianceActivity($_alli_pk, $this->i18n->t('msg_alliance_intro_change'), 'G'); // '{{1}} 님이 동맹소개를 변경 하였습니다.'

        // 동맹원 전체에게 notification
        $query_param = [$_alli_pk];
        $this->PgGame->query('SELECT lord_pk FROM alliance_member WHERE alli_pk = $1', $query_param);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        foreach ($rows AS $row)
        {
            $this->get($row['lord_pk']);
        }

        return true;
    }

    // 동맹 가입하기 페이지 최초 리스트
    function getAllianceDefault(): array
    {
        $this->PgGame->query('SELECT alli_pk, main_rank as rank, title, lord_name, now_member_count, max_member_count, power, attack_point, defence_point
FROM alliance WHERE now_member_count < max_member_count AND now_member_count != max_member_count ORDER BY power DESC, now_member_count DESC, alli_pk DESC LIMIT 30');
        $alliance_list = [];
        $alli_pk_string = '';
        while($this->PgGame->fetch()) {
            if ($alli_pk_string != '') {
                $alli_pk_string .= ', ';
            }
            $alliance_list['alli_pk_' . $this->PgGame->row['alli_pk']] = $this->PgGame->row;
            $alliance_list['alli_pk_' . $this->PgGame->row['alli_pk']]['join'] = false;
            $alli_pk_string .= $this->PgGame->row['alli_pk'];
        }
        if ($alli_pk_string != '') {
            $this->PgGame->query('SELECT alli_pk, join_type FROM alliance_join_list WHERE lord_pk = $1 AND alli_pk IN ('.$alli_pk_string.')', [$this->Session->lord['lord_pk']]);
            while($this->PgGame->fetch()) {
                $alliance_list['alli_pk_' . $this->PgGame->row['alli_pk']]['join'] = true;
                $alliance_list['alli_pk_' . $this->PgGame->row['alli_pk']]['join_type'] = $this->PgGame->row['join_type'];
            }
        }
        return $alliance_list;
    }

    // 동맹 검색하기
    function getAllianceList($_title): array
    {
        $alliance_list = [];

        $this->PgGame->query("SELECT alli_pk, main_rank as rank, title, lord_name, now_member_count, max_member_count, power, attack_point, defence_point
FROM alliance WHERE title like '%{$_title}%'
ORDER BY rank, alli_pk DESC LIMIT 30");

        $alli_pk_string = '';
        while($this->PgGame->fetch()) {
            if ($alli_pk_string != '') {
                $alli_pk_string .= ', ';
            }
            $alliance_list['alli_pk_' . $this->PgGame->row['alli_pk']] = $this->PgGame->row;
            $alliance_list['alli_pk_' . $this->PgGame->row['alli_pk']]['join'] = false;
            $alli_pk_string .= $this->PgGame->row['alli_pk'];
        }
        if ($alli_pk_string != '') {
            $this->PgGame->query('SELECT alli_pk, join_type FROM alliance_join_list WHERE lord_pk = $1 AND alli_pk IN ('.$alli_pk_string.')', [$this->Session->lord['lord_pk']]);
            while($this->PgGame->fetch())
            {
                $alliance_list['alli_pk_' . $this->PgGame->row['alli_pk']]['join'] = true;
                $alliance_list['alli_pk_' . $this->PgGame->row['alli_pk']]['join_type'] = $this->PgGame->row['join_type'];
            }
        }
        return $alliance_list;
    }

    function getAllianceListTotalCount($_title, $_offset_num, $_type = null)
    {
        // TODO 이거 왜  type 이랑 offset 사용 안하는지 알아보자.
        $order_type = $_type . '_rank';
        if ($_type == null) {
            $this->PgGame->query("SELECT COUNT(alli_pk) FROM alliance WHERE title like '%{$_title}%'");
        } else {
            $this->PgGame->query("SELECT COUNT(a.alli_pk) FROM ranking_alliance a, alliance b WHERE a.title like '%{$_title}%' AND a.alli_pk = b.alli_pk");
        }
        return $this->PgGame->fetchOne();
    }

    // 동맹 관계맺기
    function setAllianceRelation($_lord_pk, $_my_alli_pk, $_alli_pk, $_type): false|string
    {
        // 1. 군주레벨이 1~2 아니면 에러
        // 2. 기존 타입과 같으면 에러
        // 3. 관계 맺은 시간 체크(15분이내면 에러)
        // $_type => 우호:F, 적대:H, 중립:N, 삭제:D
        global $NsGlobal;
        if ($_my_alli_pk == $_alli_pk) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_relation_same_error'));
            return false;
        }

        $my_level = $this->getAllianceMemberLevel($_lord_pk);
        if ($my_level > 2) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_relation_master_error'));
            return false;
        }

        $_type = strtoupper($_type);

        $this->PgGame->query('SELECT rel_type, date_part(\'epoch\', regist_dt)::integer as update_dt FROM alliance_relation WHERE alli_pk = $1 AND rel_alli_pk = $2', [$_my_alli_pk, $_alli_pk]);
        if ($this->PgGame->fetch()) {
            // 기존에 관계 맺고 있던 것들...
            if ($this->PgGame->row['rel_type'] == $_type) {
                $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_relation_already_error')); //
                return false;
            }

            if(time() - $this->PgGame->row['update_dt'] < 900 ) {
                $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_relation_delay_error')); // 상대동맹과 마지막 외교 설정한 시간이 15분 지나야 새로운 외교관계를 설정할 수 있습니다.
                return false;
            }

            if ($_type == 'D') {
                $this->PgGame->query('DELETE FROM alliance_relation WHERE alli_pk = $1 AND rel_alli_pk = $2', [$_my_alli_pk, $_alli_pk]);
            } else {
                $this->PgGame->query('UPDATE alliance_relation SET rel_type = $3, regist_dt = now() WHERE alli_pk = $1 AND rel_alli_pk = $2', [$_my_alli_pk, $_alli_pk, $_type]);
            }
        } else {
            if ($_type != 'D') {
                // 삽입, 새로운 관계 설정한 거임.
                $this->PgGame->query('INSERT INTO alliance_relation (alli_pk, rel_alli_pk, rel_type, regist_dt) values ($1, $2, $3, now())', [$_my_alli_pk, $_alli_pk, $_type]);
            } else {
                $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_relation_other_error')); // 외교 관계가 아닙니다.
                return false;
            }
        }

        $my_alliance_title = $this->getAllianceTitle($_my_alli_pk);
        $other_alliance_title = $this->getAllianceTitle($_alli_pk);

        // TODO 컬러타입 안써도 되나?
        $color_type = 'G';
        if ($_type == 'H') {
            $my_title = $this->i18n->t('msg_alliance_relation_hostile_log_1', [$other_alliance_title]);
            $other_title = $this->i18n->t('msg_alliance_relation_hostile_log_2', [$my_alliance_title]);
            $color_type = 'R';
        } else if ($_type == 'F') {
            $my_title = $this->i18n->t('msg_alliance_relation_friendship_log_1', [$other_alliance_title]);
            $other_title = $this->i18n->t('msg_alliance_relation_friendship_log_2', [$my_alliance_title]);
        } else if ($_type == 'N') {
            $my_title = $this->i18n->t('msg_alliance_relation_neutrality_log_1', [$other_alliance_title]);
            $other_title = $this->i18n->t('msg_alliance_relation_neutrality_log_2', [$my_alliance_title]);
        } else {
            $my_title = $this->i18n->t('msg_alliance_relation_clear_log_1', [$other_alliance_title]);
            $other_title = $this->i18n->t('msg_alliance_relation_clear_log_2', [$my_alliance_title]);
        }

        $this->saveAllianceActivity($_my_alli_pk, $my_title, 'R');
        $this->saveAllianceActivity($_alli_pk, $other_title, 'R');

        // 상태 정보 갱신
        $this->PgGame->query('SELECT lord_pk FROM alliance_member WHERE alli_pk = $1', [$_my_alli_pk]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        foreach ($rows AS $row) {
            if ($_lord_pk == $row['lord_pk']) {
                $this->getRelation($_lord_pk);
            } else {
                $this->Session->sqAppend('PUSH', ['ALLIANCE_RELATION_UPDATE' => true], null, $row['lord_pk']);
            }
        }

        // position 정보 업데이트
        $this->PgGame->query('UPDATE position c SET last_update_dt = now() FROM alliance_relation a, alliance_member b WHERE a.alli_pk = $1 AND a.rel_alli_pk = b.alli_pk AND b.lord_pk = c.lord_pk AND c.type = $2', [$_my_alli_pk, 'T']);

        // 채팅으로 메시지 보내기
        /* 채팅 Push
         * global $Chat;
        if (!$Chat)
        {
            require_once_classes(Array('CChat'));
            $Chat = new CChat();
        }

        if ($_type == 'H')
        {
            $Chat->send_alert_system_to_all_channel($my_alliance_title." 동맹이 ".$other_alliance_title." 동맹을 적대시 합니다.");

            $Chat->send_alert_system_to_alli_channel("본 동맹이 ".$other_alliance_title." 동맹을 적대시 합니다.", $_my_alli_pk); // 내 동맹
            $Chat->send_alert_system_to_alli_channel($my_alliance_title." 동맹이 본 동맹을 적대시 합니다.", $_alli_pk); // 상대 동맹
        } else if ($_type == 'F') {
            $Chat->send_announce_system_to_alli_channel("본 동맹이 ".$other_alliance_title." 동맹을 우호시 합니다.", $_my_alli_pk); // 내 동맹
            $Chat->send_announce_system_to_alli_channel($my_alliance_title." 동맹이 본 동맹을 우호시 합니다.", $_alli_pk); // 내 동맹
        } else if ($_type == 'N') {
            $Chat->send_announce_system_to_alli_channel("본 동맹이 ".$other_alliance_title." 동맹과 중립을 유지합니다.", $_my_alli_pk); // 내 동맹
            $Chat->send_announce_system_to_alli_channel($my_alliance_title." 동맹이 본 동맹과 중립을 유지합니다.", $_alli_pk); // 내 동맹
        }*/
        return $_type;
    }

    // 동맹서신
    function sendAllianceLetter($_lord_pk, $_alli_pk, $_title, $_content): bool
    {
        global $NsGlobal;
        $my_level = $this->getAllianceMemberLevel($_lord_pk);
        if ($my_level > 2) {
            $NsGlobal->setErrorMessage($this->i18n->t('msg_alliance_letter_master_error')); // 맹주와 부맹주만 동맹 서신을 보낼 수 있습니다.
            return false;
        }

        // 서신 발송
        $member_list = [];
        $this->PgGame->query('SELECT lord_pk FROM alliance_member WHERE alli_pk = $1', [$_alli_pk]);
        while($this->PgGame->fetch()) {
            if ($_lord_pk != $this->PgGame->row['lord_pk']) {
                $member_list[] = $this->PgGame->row['lord_pk'];
            }
        }

        $this->classLetter();
        $this->Letter->sendLetter($_lord_pk, $member_list, ['title' => $_title, 'content' => $_content, 'type' => 'N'], false, 'Y');

        //주요활동 저장
        $this->saveAllianceActivity($_alli_pk, $this->i18n->t('msg_alliance_letter_send_ok'), 'G');

        return true;
    }

    // 동맹 이름
    function getAllianceTitle($_alli_pk): string
    {
        $this->PgGame->query('SELECT title FROM alliance WHERE alli_pk = $1', [$_alli_pk]);
        $alli_title = $this->PgGame->fetchOne();
        return $alli_title ?? '';
    }

    // 동맹 위치
    function getAlliancePosition($_lord_pk, $_alli_pk, $_posi_pk = null): void
    {
        $this->PgGame->query('select b.posi_pk from alliance_member a, position b where a.alli_pk = $1 AND a.lord_pk = b.lord_pk AND b.lord_pk != $4 AND a.type = $3 AND b.type = $2', [$_alli_pk, 'T', 'Y', $_lord_pk]);
        $this->PgGame->fetchAll();
        $this->Session->sqAppend('ALLI', $this->PgGame->rows, null, $_lord_pk, $_posi_pk);
    }
}