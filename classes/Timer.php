<?php

class Timer
{
    protected Session $Session;
    protected Pg $PgGame;
    protected int $time_pk;


    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    public function getTimePk (): int|bool
    {
        return $this->time_pk ?? false;
    }

    function classLog(): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    function getRecord($_time_pk): bool|array
    {
        $this->PgGame->query('SELECT posi_pk, status, queue_type, queue_pk, queue_action, in_cast_pk, out_cast_pk, build_time FROM timer WHERE time_pk = $1', [$_time_pk]);
        return ($this->PgGame->fetch()) ? $this->PgGame->row : false;
    }

    function get($_posi_pk): void
    {
        $time_list = [];
        $this->PgGame->query('SELECT time_pk, posi_pk, status, queue_type, queue_pk, queue_action, description, date_part(\'epoch\', start_dt)::integer as start_dt_ut, build_time, build_time_reduce, date_part(\'epoch\', end_dt)::integer as end_dt_ut, in_cast_pk, out_cast_pk FROM timer WHERE posi_pk = $1 AND status = $2', [$_posi_pk, 'P']);
        while($this->PgGame->fetch()) {
            // TODO 데이터 간략화 진행했으니 차후 확인 필요.
            $time_list[$this->PgGame->row['time_pk']] = $this->PgGame->row;
            $time_list[$this->PgGame->row['time_pk']]['queue_pk'] = (INT)$this->PgGame->row['queue_pk'];
            $time_list[$this->PgGame->row['time_pk']]['build_time_reduce'] = (INT)$this->PgGame->row['build_time_reduce'];
            $time_list[$this->PgGame->row['time_pk']]['start_dt_ut'] = (INT)$this->PgGame->row['start_dt_ut'];
            $time_list[$this->PgGame->row['time_pk']]['build_time'] = (INT)$this->PgGame->row['build_time'];
            $time_list[$this->PgGame->row['time_pk']]['end_dt_ut'] = (INT)$this->PgGame->row['end_dt_ut'];
            $time_list[$this->PgGame->row['time_pk']]['posi_pk'] = trim($this->PgGame->row['posi_pk']);
        }
        $this->Session->sqAppend('TIME', $time_list, null, $this->Session->lord['lord_pk'], $_posi_pk);
    }

    // 타이머 등록
    function set($_posi_pk, $_queue_type, $_queue_pk, $_queue_action, $_description, $_build_time, $_in_cast_pk = null, $_lord_pk = null, $_out_cast_pk = null): bool
    {
        // 타이머의 시간 고정
        $desc = explode(':', $_description);
        if ($_queue_type == 'B' && $desc[0] == 500522) { // TODO 성문 개방 효과?
            return true;
        }

        // 등록
        try {
            $this->PgGame->query("INSERT INTO timer (posi_pk, status, queue_type, queue_pk, queue_action, description, regist_dt, start_dt, build_time, build_time_reduce, end_dt, callback, in_cast_pk, out_cast_pk) VALUES ($1, $2, $3, $4, $5, $6, now(), now(), $7, 0, now() + interval '$_build_time second', $8, $9, $10)", [$_posi_pk, 'P', $_queue_type, $_queue_pk, $_queue_action, $_description, $_build_time, CONF_TIMER_CALLBACK, $_in_cast_pk, $_out_cast_pk]);
            // 추가된 SEQ 추출
            $this->time_pk = $this->PgGame->currSeq('timer_time_pk_seq');
            if (! $this->time_pk) {
                return false;
            }
            if (! $_lord_pk) {
                $_lord_pk = $this->Session->lord['lord_pk'];
            }
            $time_list = [];
            $this->PgGame->query('SELECT time_pk, posi_pk, status, queue_type, queue_pk, queue_action, description, date_part(\'epoch\', start_dt)::integer as start_dt_ut, build_time, build_time_reduce, date_part(\'epoch\', end_dt)::integer as end_dt_ut, in_cast_pk, out_cast_pk FROM timer WHERE time_pk = $1', [$this->time_pk]);
            $this->PgGame->fetch();
            // TODO 데이터 간략화 진행했으니 차후 확인 필요. 중복코드 처리도 확인.
            $time_list[$this->PgGame->row['time_pk']] = $this->PgGame->row;
            $time_list[$this->PgGame->row['time_pk']]['queue_pk'] = (INT)$this->PgGame->row['queue_pk'];
            $time_list[$this->PgGame->row['time_pk']]['build_time_reduce'] = (INT)$this->PgGame->row['build_time_reduce'];
            $time_list[$this->PgGame->row['time_pk']]['start_dt_ut'] = (INT)$this->PgGame->row['start_dt_ut'];
            $time_list[$this->PgGame->row['time_pk']]['build_time'] = (INT)$this->PgGame->row['build_time'];
            $time_list[$this->PgGame->row['time_pk']]['end_dt_ut'] = (INT)$this->PgGame->row['end_dt_ut'];
            $time_list[$this->PgGame->row['time_pk']]['posi_pk'] = trim($this->PgGame->row['posi_pk']);
            $this->Session->sqAppend('TIME', $time_list, null, $_lord_pk, $_posi_pk);
        } catch (Throwable $error) {
            Debug::debugLogging($error);
            return false;
        }
        return true;
    }

    function update($_time_pk, $_lord_pk): bool
    {
        $time_list = [];
        $this->PgGame->query('SELECT time_pk, queue_pk, posi_pk, build_time_reduce, date_part(\'epoch\', end_dt)::integer as end_dt_ut FROM timer WHERE time_pk = $1', [$_time_pk]);
        try {
            $this->PgGame->fetch();
            $time_list[$this->PgGame->row['time_pk']]['queue_pk'] = (INT)$this->PgGame->row['queue_pk'];
            $time_list[$this->PgGame->row['time_pk']]['build_time_reduce'] = (INT)$this->PgGame->row['build_time_reduce'];
            $time_list[$this->PgGame->row['time_pk']]['end_dt_ut'] = (INT)$this->PgGame->row['end_dt_ut'];
            $this->Session->sqAppend('TIME', $time_list, null, $_lord_pk, $this->PgGame->row['posi_pk']);
            return true;
        } catch (Throwable $e) {
            Debug::debugLogging($e);
            return false;
        }
    }

    // 타이머 del
    function del($_time_pk, $_lord_pk): bool
    {
        $time_list = [];
        $this->PgGame->query('SELECT time_pk, posi_pk, status FROM timer WHERE time_pk = $1', [$_time_pk]);
        if ($this->PgGame->fetch()) {
            $time_list[$this->PgGame->row['time_pk']]['status'] = $this->PgGame->row['status'];
            $this->Session->sqAppend('TIME', $time_list, null, $_lord_pk, $this->PgGame->row['posi_pk']);
            // 로그 남기고 삭제
            $this->PgGame->query('SELECT time_pk, posi_pk, status, queue_type, queue_pk, queue_action, description, regist_dt, start_dt, build_time, build_time_reduce, end_dt, in_cast_pk, out_cast_pk FROM timer WHERE time_pk = $1', [$_time_pk]);
            $this->PgGame->fetch();
            $row = $this->PgGame->row;
            $this->classLog();
            $this->Log->setTimerData($row);
            $this->PgGame->query('DELETE FROM timer WHERE time_pk = $1', [$_time_pk]);
            return true;
        } else {
            return false;
        }
    }

    // 타이머 update
    function increaseEndTime($_time_pk, $_build_time): bool
    {
        // update
        $this->PgGame->query("UPDATE timer SET build_time = build_time + $2, end_dt = end_dt + interval '$_build_time second' WHERE time_pk = $1", [$_time_pk, $_build_time]);
        return $this->update($_time_pk, $this->Session->lord['lord_pk']);
    }

    function finish($_time_pk): false|array
    {
        // cancel 이랑 다른것은 status를 C가 아니라 F로 업데이트 하는 것 뿐 2010-02-04 01:10
        $this->PgGame->query('SELECT status, queue_type, queue_pk, queue_action, description, start_dt, end_dt, build_time FROM timer WHERE time_pk = $1', [$_time_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $row = $this->PgGame->row;
        if ($row['status'] != 'P') {
            // 진행중이 아니면
            return false;
        }
        $this->PgGame->query('UPDATE timer SET status = $2 WHERE time_pk = $1', [$_time_pk, 'F']);
        $this->del($_time_pk, $this->Session->lord['lord_pk']);
        return [
            'queue_type' => $row['queue_type'],
            'queue_pk' => $row['queue_pk'],
            'queue_action' => $row['queue_action'],
            'description' => $row['description'],
            'start_dt' => $row['start_dt'],
            'end_dt' => $row['end_dt'],
            'build_time' => $row['build_time']
        ];
    }

    function cancel($_time_pk, $_lord_pk = null): false|array
    {
        // finish랑 코드가 같으니 통합하는 방향을 확인하도록
        $this->PgGame->query('SELECT status, queue_type, queue_pk, queue_action, description, start_dt, end_dt, build_time FROM timer WHERE time_pk = $1', [$_time_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $row = $this->PgGame->row;
        if ($row['status'] != 'P') {
            // 진행중이 아니면
            return false;
        }
        $this->PgGame->query('UPDATE timer SET status = $2 WHERE time_pk = $1', [$_time_pk, 'C']);
        $this->del($_time_pk, $_lord_pk ?? $this->Session->lord['lord_pk']);
        return [
            'queue_type' => $row['queue_type'],
            'queue_pk' => $row['queue_pk'],
            'queue_action' => $row['queue_action'],
            'description' => $row['description'],
            'start_dt' => $row['start_dt'],
            'end_dt' => $row['end_dt'],
            'build_time' => $row['build_time']
        ];
    }

    // 스피드 업
    function speedup($_time_pk, $_speed_time, $_m_item_pk = null): bool
    {
        if (! $_speed_time || $_speed_time < 0) {
            $_speed_time = 0;
        }
        $this->PgGame->query('SELECT queue_type, queue_pk, posi_pk, build_time, build_time_reduce FROM timer WHERE time_pk = $1', [$_time_pk]);
        if (! $this->PgGame->fetch()) {
            return false;
        }
        $timer_row = $this->PgGame->row;
        $log_posi_pk = $timer_row['posi_pk'];

        $add_query_table = '';
        $add_query_where = '';
        switch ($timer_row['queue_type']) {
            case 'T': // 연구
                $add_query_table = 'build_technique';
                $add_query_where = 'buil_tech_pk';
                break;
            case 'C': // 건설
                $add_query_table = 'build_construction';
                $add_query_where = 'buil_cons_pk';
                break;
            case 'A': // 훈련
                $add_query_table = 'build_army';
                $add_query_where = 'buil_army_pk';
                break;
            case 'F': // 함정
                $add_query_table = 'build_fortification';
                $add_query_where = 'buil_fort_pk';
                break;
            case 'M': // 치료
                $add_query_table = 'build_medical';
                $add_query_where = 'buil_medi_pk';
                break;
            case 'W': // 외부 함정
                $add_query_table = 'build_fortification_valley';
                $add_query_where = 'buil_fort_vall_pk';
                break;
        }
        if ($add_query_table !== '') {
            // TODO 쿼리문 코드 변경으로 동작 확인 필수!
            $ret = $this->PgGame->query("UPDATE {$add_query_table} SET build_time_reduce = build_time_reduce + $1,
                           end_dt = to_timestamp((date_part('epoch', start_dt)::integer + build_time - build_time_reduce - $_speed_time))
                       WHERE {$add_query_where} = $2", [$_speed_time, $timer_row['queue_pk']]);
            if (!$ret || $this->PgGame->getAffectedRows() == 0) {
                return false;
            }
        }

        /* M = 영웅 부상 삭제로 주석처리
         * $end_dt = '(end_dt::abstime::integer-'. $_speed_time. ')::abstime' ;
        $query_params = Array($timer['queue_pk']);
        $ret = $this->Db->query('UPDATE medical_hero SET end_dt = '. $end_dt .' WHERE medi_hero_pk = $1', $query_params);
        if (!$ret || $this->Db->getAffectedRows() == 0) {
            return false;
        }*/

        // 가속
        $interval = $timer_row['build_time'] - $timer_row['build_time_reduce'] - $_speed_time;
        $result = $this->PgGame->query("UPDATE timer SET build_time_reduce = build_time_reduce + $1, end_dt = start_dt + Interval '$interval second' WHERE time_pk = $2", [$_speed_time, $_time_pk]);
        if (!$result || $this->PgGame->getAffectedRows() == 0) {
            return false;
        }
        $result = $this->update($_time_pk, $this->Session->lord['lord_pk']);
        if (!$result) {
            return false;
        }
        //단축아이템을 사용했을 경우에만 기록
        if($_m_item_pk) {
            $this->classLog();
            $this->Log->setItem($this->Session->lord['lord_pk'], $log_posi_pk, 'speedup_time', null, $_m_item_pk, null, null, 'speedup_time:'.$_speed_time.';');
        }
        return true;
    }

    function checkTimer ($_posi_pk, $_queue_types, $_in_castle_pk = null): int
    {
        $add_query_string = '';
        if (isset($_in_castle_pk)) {
            $add_query_string = " AND in_cast_pk = $_in_castle_pk";
        }
        $_queue_types = array_map(function ($type) { return "'$type'"; }, $_queue_types);
        $queue_type_string = implode(',', $_queue_types);
        $this->PgGame->query("SELECT count(time_pk) FROM timer WHERE posi_pk = $1 AND queue_type IN ($queue_type_string)" . $add_query_string, [$_posi_pk]);
        return $this->PgGame->fetchOne() ?? 0;
    }
}