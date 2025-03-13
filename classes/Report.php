<?php

class Report
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Push $Push;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    public function classPush (): void
    {
        if (! isset($this->Push)) {
            $this->Push = new Push($this->Session, $this->PgGame);
        }
    }

    public function init ($_lord_pk): bool
    {
        $interval = REPORT_DELETE_PERIOD;
        $this->PgGame->query("DELETE FROM report WHERE lord_pk = $1 AND send_dt <= now() - interval '$interval second'", [$_lord_pk]);
        // 동맹 전쟁현황 관련 처리 (battle_r tab의 3일 경과 분 삭제)
        $this->PgGame->query("DELETE FROM report WHERE lord_pk = $1 AND tab_type = $2 AND send_dt <= now() - interval '$interval second'", [$_lord_pk, 'battle_r']);
        $this->setUnreadCnt($_lord_pk);
        return true;
    }

    public function setUnreadCnt($_lord_pk): void
    {
        $this->PgGame->query('SELECT COUNT(repo_pk) FROM report WHERE lord_pk = $1 AND yn_read = $2 AND tab_type != $3', [$_lord_pk, 'N', 'battle_r']);
        $cnt = $this->PgGame->fetchOne();
        // unread cnt 처리용
        $this->PgGame->query('UPDATE lord SET unread_report_cnt = $1, unread_report_last_up_dt = now() WHERE lord_pk = $2', [$cnt, $_lord_pk]);
        // 변경된 값을 다시 불러옴
        $this->PgGame->query('SELECT COUNT(repo_pk) FROM report WHERE lord_pk = $1 AND yn_read = $2 AND tab_type != $3', [$_lord_pk, 'N', 'battle_r']);
        $cnt = $this->PgGame->fetchOne();

        $this->getUnreadCount($_lord_pk);
        // LP 입력
        $this->Session->sqAppend('LORD', ['unread_report_cnt' => $cnt], null, $_lord_pk);
    }

    protected function posiPkToLordName($_posi_pk): string
    {
        $lord_name = ' ';
        $this->PgGame->query('SELECT lord_name, level FROM lord WHERE lord_pk = (SELECT lord_pk FROM position WHERE posi_pk = $1)', [$_posi_pk]);
        if ($this->PgGame->fetch()) {
            $r =& $this->PgGame->row;
            $lord_name = $r['lord_name']. ' Lv.'. $r['level'];
        }
        return $lord_name;
    }

    // 신규 보고서
    function setReport($_lord_pk, $_tab_type, $_report_type, $_from, $_to, $_title, $_summary, $_content_json, $_content_battle_json = null, $_sound_type = 'report_arrival'): int
    {
        // TODO - lord_name 추출은 다양한 환경에 대응토록 추출 방식 변경 필요
        if (! is_array($_from)) {
            $_from = [];
        }
        if (! is_array($_to)) {
            $_to = [];
        }

        // from의 lord_name
        if (! isset($_from['lord_name']) && isset($_from['posi_pk'])) {
            $_from['lord_name'] = $this->posiPkToLordName($_from['posi_pk']);
        }

        // to의 lord_name
        if (! isset($_to['lord_name']) && isset($_to['posi_pk'])) {
            $_to['lord_name'] = $this->posiPkToLordName($_to['posi_pk']);
        }

        $to_posi_pk = ! isset($_to['posi_pk']) ? null : $_to['posi_pk'];
        $to_posi_name = ! isset($_to['posi_name']) ? null : $_to['posi_name'];

        $_from_posi_pk = ! isset($_from['posi_pk']) ? null : $_from['posi_pk'];
        $_from_posi_name = ! isset($_from['posi_name']) ? null : $_from['posi_name'];

        $this->PgGame->query('INSERT INTO report (lord_pk, tab_type, report_type, from_posi_pk, from_posi_name, from_lord_name,
 to_posi_pk, to_posi_name, to_lord_name, title, summary, content_json, content_battle_json, send_dt)
VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, now())',
            [$_lord_pk, $_tab_type, $_report_type, $_from_posi_pk, $_from_posi_name, $_from['lord_name'] ?? '', $to_posi_pk,
                $to_posi_name, $_to['lord_name'] ?? '', $_title, $_summary, $_content_json, $_content_battle_json]);

        $repo_pk = $this->PgGame->currSeq('report_repo_pk_seq');
        $this->setUnreadCnt($_lord_pk);

        // $this->Session->sqAppend('PUSH', ['PLAY_SOUND' => $_sound_type], null, $_lord_pk);

        // Push
        $this->classPush();

        if ($_tab_type == 'scout' && $_report_type == 'enemy_march') {
            $this->Push->send('detect', '', $_lord_pk, $to_posi_pk);
        } else if ($_tab_type == 'battle' && $_report_type == 'battle_attack') {
            $this->Push->send('attack', '', $_lord_pk, $to_posi_pk);
        } else if ($_tab_type == 'battle' && $_report_type == 'battle_defence') {
            $this->Push->send('defence', '', $_lord_pk, $to_posi_pk);
        } else if ($_tab_type == 'scout' && $_report_type == 'scout_success'){
            $this->Push->send('scout', '', $_lord_pk, $to_posi_pk);
        } else if ($_tab_type == 'scout' && $_report_type == 'scout_failure') {
            $this->Push->send('scout', '', $_lord_pk, $to_posi_pk);
        } else if ($_tab_type == 'scout' && $_report_type == 'scout_find') {
            $this->Push->send('scout2', '', $_lord_pk, $to_posi_pk);
        }

        return $repo_pk;
    }

    // 총 갯수 얻기
    function getReportTotalCount($_lord_pk, $_type): int
    {
        $this->PgGame->query('SELECT COUNT(repo_pk) AS cnt FROM report WHERE lord_pk = $1 AND tab_type = $2', [$_lord_pk, $_type]);
        return $this->PgGame->fetchOne();
    }

    // 리스트
    function getReportList($_lord_pk, $_type, $_page_num = 1): array
    {
        $offset_num = (($_page_num - 1) * REPORT_LETTER_PAGE_NUM);
        $this->PgGame->query('SELECT repo_pk, report_type, from_lord_name, from_posi_name, to_lord_name, to_posi_name, yn_read, title, date_part(\'epoch\', send_dt)::integer as send_dt FROM report WHERE lord_pk = $1 AND tab_type = $2 ORDER BY repo_pk DESC LIMIT $4 OFFSET $3', [$_lord_pk, $_type, $offset_num, REPORT_LETTER_PAGE_NUM]);
        $this->PgGame->fetchAll();
        return (!$this->PgGame->rows || !count($this->PgGame->rows)) ? [] : $this->PgGame->rows;
    }

    // 조회
    function getReport($_repo_pk): bool|array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_skill']);

        $this->PgGame->query('SELECT lord_pk, from_posi_name, from_lord_name, to_posi_name, to_lord_name, title, summary, content_json, content_battle_json, date_part(\'epoch\', send_dt)::integer as send_dt, yn_read, report_type, yn_activity FROM report WHERE repo_pk = $1', [$_repo_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;

        $row['att_battle_skill'] = [];
        $row['def_battle_skill'] = [];

        if ($row['content_battle_json']) {
            $data = json_decode($row['content_battle_json'], true);

            // 사전 스킬 체크
            if ($data['hero_battle']) {
                if ($data['hero_battle']['att']) {
                    if ($data['hero_battle']['att']['before_battle_skill']) {
                        if ($data['hero_battle']['att']['before_battle_skill']['pk']) {
                            $pk = $data['hero_battle']['att']['before_battle_skill']['pk'];
                            $skill_name = $_M['HERO_SKILL'][$pk]['title'].' '.'Lv.'.$_M['HERO_SKILL'][$pk]['rare'];
                            $row['att_battle_skill'][] = ['hero_type' => $this->getSkillUsedType($pk), 'skill_name' => $skill_name];
                        }
                    }
                }
                if ($data['hero_battle']['def']) {
                    if ($data['hero_battle']['def']['before_battle_skill']) {
                        if ($data['hero_battle']['def']['before_battle_skill']['pk']) {
                            $pk = $data['hero_battle']['def']['before_battle_skill']['pk'];
                            $skill_name = $_M['HERO_SKILL'][$pk]['title'].' '.'Lv.'.$_M['HERO_SKILL'][$pk]['rare'];
                            $row['def_battle_skill'][] = ['hero_type' => $this->getSkillUsedType($pk), 'skill_name' => $skill_name];
                        }
                    }
                }
            }
            // 전투 스킬 체크
            if ($data['scene']) {
                $att_check_array = [];
                $def_check_array = [];
                foreach($data['scene'] as $v) {
                    if (isset($v['att_battle_skill'])) {
                        if ($v['att_battle_skill']['pk']) {
                            $pk = $v['att_battle_skill']['pk'];
                            $skill_name = $_M['HERO_SKILL'][$pk]['title'].' '.'Lv.'.$_M['HERO_SKILL'][$pk]['rare'];
                            if (!in_array($skill_name, $att_check_array)) {
                                $att_check_array[] = $skill_name;
                                $row['att_battle_skill'][] = ['hero_type' => $this->getSkillUsedType($pk), 'skill_name' => $skill_name];
                            }
                        }
                    }
                    if (isset($v['def_battle_skill'])) {
                        if ($v['def_battle_skill']['pk']) {
                            $pk = $v['def_battle_skill']['pk'];
                            $skill_name = $_M['HERO_SKILL'][$pk]['title'].' '.'Lv.'.$_M['HERO_SKILL'][$pk]['rare'];
                            if (!in_array($skill_name, $def_check_array)) {
                                $def_check_array[] = $skill_name;
                                $row['def_battle_skill'][] = ['hero_type' => $this->getSkillUsedType($pk), 'skill_name' => $skill_name];
                            }
                        }
                    }
                }
            }
        }

        if ($row) {
            $send_activity = 'N';
            if ($row['yn_read'] == 'N' && $this->Session->lord['lord_pk'] == $row['lord_pk']) {
                $this->setRead([$_repo_pk], $row['lord_pk']);
                if (isset($row['to_posi_pk']) && $row['to_posi_pk'] !== '999x999' && $row['report_type'] === "battle_defence") {
                    $send_activity = 'Y';
                }
            }

            $row['summary'] = nl2br($row['summary']);
            $row['content_json'] = (isset($row['content_json'])) ? json_decode(str_replace("\n","", trim($row['content_json']))) : null;
            $row['content_battle_json'] = (isset($row['content_battle_json'])) ? json_decode(str_replace("\n","", trim($row['content_battle_json']))) : null;
            $row['send_activity'] = $send_activity;
            $row['server_name'] = GAME_SERVER_NAME;

            return $row;
        } else {
            throw new ErrorHandler('error', 'Error Occurred. [24001]'); // 찾을 수 없는 보고서
        }
    }

    // 조회 - 전투애니메이팅
    function getReportBattleJson($_repo_pk): array
    {
        $this->PgGame->query('SELECT content_json, content_battle_json FROM report WHERE repo_pk = $1', [$_repo_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;

        if (isset($row['content_battle_json'])) {
            return $row;
        } else {
            throw new ErrorHandler('error', 'Error Occurred. [24002]'); // 찾을 수 없는 보고서
        }
    }

    // 읽음표시
    function setRead($_repo_pk_array, $_lord_pk = null): true
    {
        $this->PgGame->query('UPDATE report SET yn_read = $1, recv_dt = now() WHERE repo_pk = ANY($2) AND yn_read = $3 AND lord_pk = $4', ['Y', '{'. implode(',', $_repo_pk_array). '}', 'N', $_lord_pk]);
        if ($_lord_pk !== null) {
            $this->setUnreadCnt($_lord_pk);
        }
        return true;
    }

    // 삭제
    function removeReport($_repo_pk_array): true
    {
        $this->PgGame->query('UPDATE report SET tab_type = $1 WHERE repo_pk = ANY($2) AND tab_type = $3', ['battle_r', '{'. implode(',', $_repo_pk_array). '}', 'battle']);
        $this->PgGame->query('DELETE FROM report WHERE repo_pk = ANY($2) AND tab_type != $1', ['battle_r', '{'. implode(',', $_repo_pk_array). '}']);
        $this->setUnreadCnt($this->Session->lord['lord_pk']);
        return true;
    }

    // 월드맵 - 관련보고 보기
    function getReportToPosition($to_posi_pk, $_lord_pk = false): array
    {
        if (! $_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }
        $this->PgGame->query('SELECT repo_pk, title, from_posi_pk, from_posi_name, date_part(\'epoch\', send_dt)::integer as send_dt, yn_read, report_type FROM report WHERE lord_pk = $1 AND to_posi_pk = $2 AND (tab_type =\'scout\' OR tab_type = \'battle\') AND report_type != \'enemy_march\' AND report_type != \'scout_find\' ORDER BY send_dt DESC', [$_lord_pk, $to_posi_pk]);
        $this->PgGame->fetchAll();
        return (! $this->PgGame->rows) ? [] : $this->PgGame->rows;
    }

    // 읽지 않은 보고서 종류별로 갯수 가져오기
    function getUnreadCount($_lord_pk): void
    {
        $this->PgGame->query('SELECT tab_type, count(repo_pk) as count FROM report WHERE lord_pk = $1 AND yn_read = $2 AND tab_type <> $3 GROUP BY tab_type', [$_lord_pk, 'N', 'battle_r']);
        $this->PgGame->fetchAll();
        $result = [];
        $report_type_arr = ['scout', 'battle', 'move', 'misc', 'recall'];
        foreach($this->PgGame->rows as $row) {
            $result[$row['tab_type']] = $row['count'];
        }
        foreach($report_type_arr as $tab_type) {
            if (! isset($result[$tab_type])) {
                $result[$tab_type] = 0;
            }
        }
        $this->Session->sqAppend('LORD', ['unread_report_desc' => $result], null, $_lord_pk);
    }

    // 전투 스킬 사용자 알아오기 (보고서에 사용하기 위해 추가함)
    function getSkillUsedType($_m_hero_skill_pk): false|string
    {
        $type = false;
        switch ($_m_hero_skill_pk) {
            case 157101 : // 금강
            case 157102 : // 금강
            case 157103 : // 금강
            case 157104 : // 금강
            case 157105 : // 금강
            case 157106 : // 금강
            case 157107 : // 금강
            case 157201 : // 철벽
            case 157202 : // 철벽
            case 157203 : // 철벽
            case 157204 : // 철벽
            case 157205 : // 철벽
            case 157206 : // 철벽
            case 157207 : // 철벽
            case 157208 : // 철벽
                $type = 'captain';
                break;
            case 156901 : // 용장
            case 156902 : // 용장
            case 156903 : // 용장
            case 156904 : // 용장
            case 156905 : // 용장
            case 156906 : // 용장
            case 156907 : // 용장
            case 157001 : // 패왕
            case 157002 : // 패왕
            case 157003 : // 패왕
            case 157004 : // 패왕
            case 157005 : // 패왕
            case 157006 : // 패왕
            case 157007 : // 패왕
            case 157008 : // 패왕
                $type = 'director';
                break;
            case 157301   : // 화계
            case 157302   : // 화계
            case 157303   : // 화계
            case 157304   : // 화계
            case 157305   : // 화계
            case 157306   : // 화계
            case 157307   : // 화계
            case 157401   : // 고무
            case 157402   : // 고무
            case 157403   : // 고무
            case 157404   : // 고무
            case 157405   : // 고무
            case 157406   : // 고무
            case 157407   : // 고무
            case 157501   : // 침착
            case 157502   : // 침착
            case 157503   : // 침착
            case 157504   : // 침착
            case 157505   : // 침착
            case 157506   : // 침착
            case 157507   : // 침착
            case 157601   : // 위풍
            case 157602   : // 위풍
            case 157603   : // 위풍
            case 157604   : // 위풍
            case 157605   : // 위풍
            case 157606   : // 위풍
            case 157607   : // 위풍
            case 157701   : // 통찰
            case 157702   : // 통찰
            case 157703   : // 통찰
            case 157704   : // 통찰
            case 157705   : // 통찰
            case 157706   : // 통찰
            case 157707   : // 통찰
            case 157801   : // 간파
            case 157802   : // 간파
            case 157803   : // 간파
            case 157804   : // 간파
            case 157805   : // 간파
            case 157806   : // 간파
            case 157807   : // 간파
                $type = 'staff';
                break;
        }
        return $type;
    }
}