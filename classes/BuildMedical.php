<?php

class BuildMedical
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Timer $Timer;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame, Timer $_Timer)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->Timer = $_Timer;
    }

    function classLog(): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    function set($_buil_pk, $_m_army_pk, $_build_number, $_build_time, $_build_status): false|int
    {
        $r = $this->PgGame->query("INSERT INTO build_medical
(buil_pk, m_army_pk, status, priority, build_number, description, regist_dt, start_dt, build_time, build_time_reduce, end_dt)
VALUES
($1, $2, $5, 1, $3, '-', now(), now(), $4, 0, now() + interval '$_build_time second')", [$_buil_pk, $_m_army_pk, $_build_number, $_build_time, $_build_status]);
        if (!$r) {
            return false;
        }
        $queue_pk = $this->PgGame->currSeq('build_medical_buil_medi_pk_seq');
        if($_build_status == 'P') {
            $this->PgGame->query('UPDATE build SET status = \'P\', concurr_curr = concurr_curr+1, last_update_dt = now() WHERE buil_pk = $1', [$_buil_pk]);
        } else {
            $this->PgGame->query('UPDATE build SET queue_curr = queue_curr+1, last_update_dt = now() WHERE buil_pk = $1', [$_buil_pk]);
        }
        return $queue_pk;
    }

    // 부상병 치료 완료 (신규)
    function finish($_queue_pk): false|array
    {
        $this->PgGame->query('SELECT buil_pk, m_army_pk, build_number, start_dt FROM build_medical WHERE buil_medi_pk = $1', [$_queue_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $r = &$this->PgGame->row;
        $this->PgGame->query('UPDATE build_medical SET status = $2 WHERE buil_medi_pk = $1', [$_queue_pk, 'F']);
        $this->PgGame->query('UPDATE build SET concurr_curr = concurr_curr-1, last_update_dt = now() WHERE buil_pk = $1 AND concurr_curr > 0', [$r['buil_pk']]);
        $this->PgGame->query('UPDATE build SET status = $2 WHERE buil_pk = $1 AND concurr_curr = 0 AND queue_curr = 0', [$r['buil_pk'], 'I']);

        return ['m_army_pk' => $r['m_army_pk'], 'build_number' => $r['build_number'], 'buil_pk' => $r['buil_pk'], 'start_dt' => $r['start_dt']];
    }

    // 부상병 치료 취소 (신규)
    function cancel($_queue_pk): false|array
    {
        $this->PgGame->query('SELECT buil_pk, m_army_pk, build_number FROM build_medical WHERE buil_medi_pk = $1', [$_queue_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $r = &$this->PgGame->row;

        $this->PgGame->query('UPDATE build_medical SET status = $2 WHERE buil_medi_pk = $1', [$_queue_pk, 'C']);
        $this->PgGame->query('UPDATE build SET concurr_curr = concurr_curr-1, last_update_dt = now() WHERE buil_pk = $1 AND concurr_curr > 0', [$r['buil_pk']]);
        $this->PgGame->query('UPDATE build SET status = $2 WHERE buil_pk = $1 AND concurr_curr = 0 AND queue_curr = 0', [$r['buil_pk'], 'I']);

        return ['m_army_pk' => $r['m_army_pk'], 'build_number' => $r['build_number'], 'buil_pk' => $r['buil_pk'], 'queue_pk' => $_queue_pk];
    }

    function queue($_buil_pk, $_posi_pk): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army', 'condition']);

        $this->PgGame->query('SELECT buil_medi_pk, m_army_pk, build_time, build_number FROM build_medical WHERE buil_pk = $1 AND status = $2 order by buil_medi_pk limit 1', [$_buil_pk, 'I']);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;

        if(isset($row['buil_medi_pk'])) {
            $build_time = $row['build_time'];
            $this->PgGame->query('UPDATE build SET status = $2, concurr_curr = concurr_curr + 1, queue_curr = queue_curr - 1, last_update_dt = now() WHERE buil_pk = $1', [$_buil_pk, 'P']);

            $this->PgGame->query('SELECT in_cast_pk FROM build WHERE buil_pk = $1', [$_buil_pk]);
            $in_cast_pk = $this->PgGame->FetchOne();

            $this->PgGame->query("UPDATE build_medical set status = 'P', build_time = $2, start_dt = now(), end_dt = now() + interval '$build_time second' WHERE buil_medi_pk = $1", [$row['buil_medi_pk'], $build_time]);

            $description = $_M['ARMY'][$row['m_army_pk']]['title'] . ' (' . $row['build_number'] . ')';
            $this->Timer->set($_posi_pk, 'M', $row['buil_medi_pk'], 'U', $description, $build_time, $in_cast_pk);

            //Log
            $this->classLog();
            $time_pk = $this->PgGame->currSeq('timer_time_pk_seq');
            $this->Log->setArmy($this->Session->lord['lord_pk'], $_posi_pk, 'medical_queue', $_M['ARMY'][$row['m_army_pk']]['title'], $row['buil_medi_pk'], $_buil_pk, $row['m_army_pk'], null, null, $row['build_time'], null, $row['build_number'], $time_pk);
        }
    }

    function queueCancel($_queue_pk): false|array
    {
        $this->PgGame->query('SELECT buil_pk, m_army_pk, build_number FROM build_medical WHERE buil_medi_pk = $1', [$_queue_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }

        $r = &$this->PgGame->row;

        $this->PgGame->query('UPDATE build_medical SET status = $2 WHERE buil_medi_pk = $1', [$_queue_pk, 'C']);
        $this->PgGame->query('UPDATE build SET queue_curr = queue_curr - 1, last_update_dt = now() WHERE buil_pk = $1 AND queue_curr > 0', [$r['buil_pk']]);

        return ['m_army_pk' => $r['m_army_pk'], 'build_number' => $r['build_number'], 'buil_pk' => $r['buil_pk']];

    }

    function getCurrent($_posi_pk, $_in_cast_pk): array
    {
        // TODO abstime 변경 필요.
        $this->PgGame->query('SELECT buil_medi_pk, status, m_army_pk, priority, build_number, build_time, build_time_reduce, date_part(\'epoch\', end_dt)::integer AS end_dt
FROM build_medical WHERE buil_pk = (SELECT buil_pk FROM build WHERE posi_pk = $1 AND in_cast_pk = $2) AND status IN ($3, $4)
ORDER BY priority, buil_medi_pk', [$_posi_pk, $_in_cast_pk, 'P', 'I']);

        $current = ['concurr' => ['count' => 0], 'queue' => ['count' => 0]];

        while ($this->PgGame->fetch()) {
            $r = $this->PgGame->row;
            if ($r['status'] == 'P') {
                $current['concurr'][$r['buil_medi_pk']] = $r;
                $current['concurr']['end_dt'] = $r['end_dt'];
                $current['concurr']['count']++;
            } else {
                $current['queue'][$r['buil_medi_pk']] = $r;
                $current['queue']['count']++;
            }
        }
        return $current;
    }

    function getQueueCurrent($_posi_pk, $_in_cast_pk): array
    {
        $queue = [];
        $this->PgGame->query('SELECT buil_medi_pk, m_army_pk, build_number FROM build_medical
WHERE buil_pk = (SELECT buil_pk FROM build WHERE posi_pk = $1 AND in_cast_pk = $2) AND status = $3 ORDER BY buil_medi_pk', [$_posi_pk, $_in_cast_pk, 'I']);
        while ($this->PgGame->fetch()) {
            $r = $this->PgGame->row;
            $queue[$r['buil_medi_pk']] = $r;
        }
        return $queue;
    }
}