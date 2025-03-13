<?php

class Lord
{
    public Session $Session;
    public Pg $PgGame;

    protected Quest $Quest;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    public function classQuest(): void
    {
        if (!isset($this->Quest)) {
            $this->Quest = new Quest($this->Session, $this->PgGame);
        }
    }

    function classLog(): void
    {
        if (!isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    public function getLogin($_lord_pk): int
    {
        $this->PgGame->query('SELECT ' . LORD_SESSION_COLUMN . ' FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $r = $this->PgGame->fetch();
        if ($r === false) {
            return -1; // user_id: Not Found
        }
        if ($this->PgGame->row['withdraw_dt'] != null) {
            return -2;
        }
        if (!$this->Session->setLogin($this->PgGame->row)) {
            return -3;
        }
        return 1;
    }

    function get($_lord_pk = null): bool
    {
        // 자기정보 요청시 반드시 로그인 상태여야 함.
        if ($_lord_pk == null && !$this->Session->is_login) {
            return false;
        }

        if ($_lord_pk) {
            $this->PgGame->query('SELECT ' . LORD_SESSION_COLUMN . ' FROM lord WHERE lord_pk = $1', [$_lord_pk]);
            $this->PgGame->fetch();
            $sq_data = &$this->PgGame->row;
        } else {
            $sq_data = &$this->Session->lord;
        }

        $this->Session->sqAppend('LORD', $sq_data);
        return true;
    }

    public function getLordInfo($_lord_pk): array|bool
    {
        $this->PgGame->query('SELECT '. LORD_SESSION_COLUMN. ' FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $this->PgGame->fetch();
        return $this->PgGame->row ?? false;
    }

    public function getLordName ($_lord_pk): string
    {
        $this->PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        return $this->PgGame->fetchOne();
    }

    public function getAlliPk ($_lord_pk): int | null
    {
        $this->PgGame->query('SELECT alli_pk FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        return $this->PgGame->fetchOne();
    }

    function getRank($_lord_pk = null): void
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }
        $this->PgGame->query('SELECT main_rank FROM lord_point WHERE lord_pk = $1', [$_lord_pk]);
        $this->PgGame->fetch();
        $sq_data = &$this->PgGame->row;
        $this->Session->sqAppend('LORD', $sq_data);
    }

    function getMainPosiPk ($_lord_pk): string
    {
        $this->PgGame->query('SELECT main_posi_pk FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        return $this->PgGame->fetchOne() ?? '';
    }

    /*function getGameOption($_lord_pk = null)
    {
        if (!$_lord_pk)
            $_lord_pk = $this->Session->lord['lord_pk'];

        $query_params = Array($_lord_pk);
        $this->PgGame->query('SELECT game_option FROM lord WHERE lord_pk = $1', $query_params);
        $this->PgGame->fetch();
        $sqData = &$this->PgGame->row;
        $this->Session->sqAppend('LORD', $sqData);
    }*/

    function getUnreadCnt($_lord_pk = null): void
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }
        $this->PgGame->query('SELECT '. LORD_UNREAD_COLUMN. ' FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $this->PgGame->fetch();
        $this->Session->sqAppend('LORD', $this->PgGame->row);
    }

    function increasePower($_lord_pk, $_incr_power, $_posi_pk = null): true
    {
        $this->PgGame->query('UPDATE lord SET power = power + $1 WHERE lord_pk = $2', [$_incr_power, $_lord_pk]);
        $this->Session->setLoginReload();

        // territory 테이블별 power 업데이트
        if (is_string($_posi_pk) && strlen($_posi_pk) > 0 && preg_match('/^\d{1,3}x\d{1,3}$/', $_posi_pk)) {
            $this->PgGame->query('SELECT count(posi_pk) FROM territory WHERE posi_pk = $1', [$_posi_pk]);
            if ($this->PgGame->fetchOne() > 0) {
                $this->PgGame->query('UPDATE territory SET power = power + $1 WHERE posi_pk = $2', [$_incr_power, $_posi_pk]);
            }
        }

        if($this->Session->lord['power'] >= 50) {
            // 퀘스트 체크
            $this->classQuest();
            $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'lord_power', 'power' => $this->Session->lord['power']]);
        }

        return true;
    }

    function decreasePower($_lord_pk, $_desc_power, $_posi_pk = null): true
    {
        $this->PgGame->query('UPDATE lord SET power = power - $1 WHERE lord_pk = $2', [$_desc_power, $_lord_pk]);
        if (is_string($_posi_pk) && strlen($_posi_pk) > 0 && preg_match('/^\d{1,3}x\d{1,3}$/', $_posi_pk)) {
            $this->PgGame->query('SELECT count(posi_pk) FROM territory WHERE posi_pk = $1', [$_posi_pk]);
            if ($this->PgGame->fetchOne() > 0) {
                $this->PgGame->query('UPDATE territory SET power = power - $1 WHERE posi_pk = $2', [$_desc_power, $_posi_pk]);
            }
        }
        $this->Session->setLoginReload();
        return true;
    }

    function updateAlliancePK($_lord_pk, $_alli_pk): true
    {
        $this->PgGame->query('UPDATE lord SET alli_pk = $1 WHERE lord_pk = $2', [$_alli_pk, $_lord_pk]);
        if ($this->Session->lord['lord_pk'] == $_lord_pk) {
            $this->Session->setLoginReload();
        } else {
            // TODO 2중 Push를 보내고 있어 일단 주석처리. 차후 문제시 확인 필요. 20230725 송누리
            // $this->Session->sqAppend('UPDATE_ALLIANCE', ['alli_pk' => $_alli_pk], null, $_lord_pk);
        }
        return true;
    }

    function increasePosition($_lord_pk): true
    {
        $this->PgGame->query('UPDATE lord SET position_cnt = position_cnt + 1 WHERE lord_pk = $1', [$_lord_pk]);
        return true;
    }

    function decreasePosition($_lord_pk): true
    {
        $this->PgGame->query('UPDATE lord SET position_cnt = position_cnt - 1 WHERE lord_pk = $1', [$_lord_pk]);
        $this->PgGame->query('SELECT position_cnt FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        if ($this->PgGame->fetchOne() < 0) {
            $this->PgGame->query('UPDATE lord SET position_cnt = 0 WHERE lord_pk = $1', [$_lord_pk]);
        }
        return true;
    }

    // 메인 영지 변경
    function updateMainPosiPk($_lord_pk, $_posi_pk)
    {
        $this->PgGame->query('SELECT main_posi_pk FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $main_posi_pk = $this->PgGame->fetchOne();

        if ($main_posi_pk == $_posi_pk)	{ // 메인영지 점령 당함
            // 다른 확장영지 검색
            $this->PgGame->query('SELECT t2.posi_pk FROM position AS t1, territory AS t2 WHERE t1.posi_pk = t2.posi_pk AND t1.lord_pk = $1 AND t1.posi_pk != $2 ORDER BY t2.title, t2.posi_pk LIMIT 1', [$_lord_pk, $_posi_pk]);
            $main_posi_pk = $this->PgGame->fetchOne();
            if ($main_posi_pk) {
                $this->PgGame->query('UPDATE lord SET main_posi_pk = $2 WHERE lord_pk = $1', [$_lord_pk, $main_posi_pk]);
            } else {
                // 확장영지가 없음. 방랑영주가 됨.
                $this->PgGame->query('UPDATE lord SET main_posi_pk = null, power = power + 500 WHERE lord_pk = $1', [$_lord_pk]);
                $main_posi_pk = null;
            }
        }

        $this->PgGame->query('UPDATE lord SET position_cnt = position_cnt - 1 WHERE lord_pk = $1', [$_lord_pk]);
        $this->PgGame->query('SELECT position_cnt FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        if ($this->PgGame->fetchOne() < 0) {
            $this->PgGame->query('UPDATE lord SET position_cnt = 0 WHERE lord_pk = $1', [$_lord_pk]);
        }

        if ($main_posi_pk != null) {
            $this->Session->sqAppend('PUSH', ['CHANGE_MAIN_POSI_PK' => true], null, $_lord_pk);
        }

        return $main_posi_pk;
    }

    // 점령코인 지급
    function setIncreaseCoin($_lord_pk, $_coin, $_reason): false
    {
        $this->PgGame->query('SELECT point_coin FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $my_coin = $this->PgGame->fetchOne();
        if ($my_coin + $_coin > 30000) {
            $reward_coin = 30000;
        } else {
            $reward_coin = $my_coin + $_coin;
        }

        $r = $this->PgGame->query('UPDATE lord SET point_coin = $2 WHERE lord_pk = $1', [$_lord_pk, $reward_coin]);
        if (!$r || $this->PgGame->getAffectedRows() != 1) {
            return false;
        }

        $this->classLog();
        $this->Log->setPoint($_lord_pk, null, 'reward_coin_suppress', null, 'reason:'.$_reason.'prev:'.$my_coin.'update:'.$_coin);

        return false;
    }

    // 군주 공/수/병 포인트 가져오기
    function getLordPoint($_lord_pk = null): array
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }
        $this->PgGame->query('SELECT attack_point, defence_point, army_point FROM lord_point WHERE lord_pk = $1', [$_lord_pk]);
        $this->PgGame->fetch();
        $ret = &$this->PgGame->row;
        return ['army_point' => $ret['army_point'], 'attack_point' => $ret['attack_point'], 'defence_point' => $ret['defence_point']];
    }

    function checkPackage ($_lord_pk, $_type, $_pk, $_value): void
    {
        global $NsGlobal, $_M;
        $NsGlobal->requireMasterData(['package']);

        // 종료된 패키지가 있는지 체크
        $this->checkPackageDate($_lord_pk);

        // 패키지 구매 창
        $m_package = array_filter($_M['PACKAGE'], function ($a) use ($_type, $_pk, $_value) {
            return $a['target_type'] == $_type && $a['target_pk'] == $_pk && $a['target_value'] == $_value;
        });
        if (count($m_package) > 0) {
            $m_package_pk = array_key_first($m_package);
            // 이미 받은 패키지인지 확인
            $this->PgGame->query('SELECT count(my_package_pk) FROM my_package WHERE lord_pk = $1 and m_package_pk = $2', [$_lord_pk, $m_package_pk]);
            if ($this->PgGame->fetchOne() < 1) {
                // 패키지 등록
                $this->PgGame->query("INSERT INTO my_package (lord_pk, m_package_pk, end_date) VALUES ($1, $2, now() + interval '{$m_package[$m_package_pk]['time_limit']} second')", [$_lord_pk, $m_package_pk]);

                // Push 보내기
                $this->Session->sqAppend('PUSH', ['PACKAGE_NOTICE' => ['m_package_pk' => $m_package_pk]], null, $_lord_pk);
            }
        }
    }

    function checkPackageDate ($_lord_pk): void
    {
        // 이미 날짜가 지난 상품인 경우 강제 매진 처리
        $this->PgGame->query('UPDATE my_package SET sold_out = 1 WHERE lord_pk = $1 and end_date < now()', [$_lord_pk]);
    }

    function refreshOccupationPoint ($_lord_pk): array|bool
    {
        $this->classLog();
        $now_time = Useful::microTimeFloat();

        $this->PgGame->query('SELECT posi_pk, lord_pk, type, level, current_point, now() as now, date_part(\'epoch\', update_point_dt) as update_point_dt FROM position WHERE lord_pk = $1 AND current_point > 0', [$_lord_pk]);
        $this->PgGame->fetchAll();

        global $_M;
        $NsGlobal = NsGlobal::getInstance();
        $NsGlobal->requireMasterData(['productivity_valley']);

        $log_data = [];
        $position_query = [];
        $update_point = 0;
        foreach ($this->PgGame->rows as $row) {
            $need_point = bcdiv(bcmul(bcsub($now_time, $row['update_point_dt']), $_M['PROD_VALL'][$row['type']][$row['level']]['occupation_point']), 3600, 2);
            if ($need_point > $row['current_point']) {
                $need_point = $row['current_point'];
            }
            $log_data[$row['lord_pk']][] = ['posi_pk' => $row['posi_pk'], 'earn_point' => $need_point];
            $position_query[] = "($need_point, '{$row['now']}', '{$row['posi_pk']}')";
            $update_point += $need_point;
        }

        // 1차 로그
        foreach ($log_data as $__lord_pk => $_logs) {
            foreach ($_logs as $_log) {
                $this->Log->setOccupationPoint($__lord_pk, null, 'self_earn', $_log['posi_pk'], $_log['earn_point']);
            }
        }

        if (count($position_query) > 0) {
            $position_query_string = implode(',', $position_query);
            $this->PgGame->query("UPDATE position AS t SET current_point = current_point - c.point, update_point_dt = c.now::timestamptz FROM (VALUES $position_query_string) as c(point, now, posi_pk) WHERE c.posi_pk = t.posi_pk and t.level BETWEEN 1 AND 3");
            $this->PgGame->query("UPDATE position AS t SET current_point = current_point - c.point, update_point_dt = c.now::timestamptz FROM (VALUES $position_query_string) as c(point, now, posi_pk) WHERE c.posi_pk = t.posi_pk and t.level BETWEEN 4 AND 6");
            $this->PgGame->query("UPDATE position AS t SET current_point = current_point - c.point, update_point_dt = c.now::timestamptz FROM (VALUES $position_query_string) as c(point, now, posi_pk) WHERE c.posi_pk = t.posi_pk and t.level BETWEEN 7 AND 9");
            $this->PgGame->query("UPDATE position AS t SET current_point = current_point - c.point, update_point_dt = c.now::timestamptz FROM (VALUES $position_query_string) as c(point, now, posi_pk) WHERE c.posi_pk = t.posi_pk and t.level = 10");
        }
        $this->PgGame->query('UPDATE occupation_point SET point = point + $2, update_dt = now() WHERE lord_pk = $1 RETURNING point, date_part(\'epoch\', update_dt + interval \'5 minutes\')::integer as limit_dt', [$_lord_pk, $update_point]);
        $this->PgGame->fetch();
        $this->Log->setOccupationPoint($_lord_pk, null, 'update_point', null, $this->PgGame->row['point']);
        return $this->PgGame->row;
    }
}