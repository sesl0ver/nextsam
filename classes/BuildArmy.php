<?php

class BuildArmy
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Timer $Timer;
    protected Army $Army;
    protected Effect $Effect;
    protected FigureReCalc $FigureReCalc;
    protected GoldPop $GoldPop;
    protected Resource $Resource;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame, Timer $_Timer)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->Timer = $_Timer;
    }

    function classArmy(): void
    {
        if (! isset($this->Army)) {
            $this->classResource();
            $this->classGoldPop();
            $this->Army = new Army($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classEffect(): void
    {
        if (! isset($this->Effect)) {
            $this->classResource();
            $this->classGoldPop();
            $this->classFigureReCalc();
            $this->Effect = new Effect($this->Session, $this->PgGame, $this->Resource, $this->GoldPop, $this->FigureReCalc);
        }
    }

    function classGoldPop(): void
    {
        if (! isset($this->GoldPop)) {
            $this->GoldPop = new GoldPop($this->Session, $this->PgGame);
        }
    }

    function classResource(): void
    {
        if (! isset($this->Resource)) {
            $this->Resource = new Resource($this->Session, $this->PgGame);
        }
    }

    function classFigureReCalc(): void
    {
        if (! isset($this->FigureReCalc)) {
            $this->classResource();
            $this->classGoldPop();
            $this->FigureReCalc = new FigureReCalc($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classTimer(): void
    {
        if (! isset($this->Timer)) {
            $this->Timer = new Timer($this->Session, $this->PgGame);
        }
    }

    function classLog(): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    function set($_buil_pk, $_m_army_pk, $_build_number, $_build_time, $_build_status): false|int
    {
        $r = $this->PgGame->query("INSERT INTO build_army
(buil_pk, m_army_pk, status, priority, build_number, description, regist_dt, start_dt, build_time, build_time_reduce, end_dt)
VALUES (\$1, \$2, \$5, 1, \$3, '-', now(), now(), \$4, 0, now() + interval '$_build_time second')", [$_buil_pk, $_m_army_pk, $_build_number, $_build_time, $_build_status]);
        if (!$r) {
            return false;
        }
        $queue_pk = $this->PgGame->currSeq('build_army_buil_army_pk_seq');
        if($_build_status == 'P') {
            $this->PgGame->query('UPDATE build SET status = $2, concurr_curr = concurr_curr+1, last_update_dt = now() WHERE buil_pk = $1', [$_buil_pk, 'P']);
        } else {
            $this->PgGame->query('UPDATE build SET queue_curr = queue_curr + 1, last_update_dt = now() WHERE buil_pk = $1', [$_buil_pk]);
        }
        return $queue_pk;
    }

    function finish($_queue_pk): false|array
    {
        $this->PgGame->query('SELECT buil_pk, m_army_pk, build_number, start_dt FROM build_army WHERE buil_army_pk = $1', [$_queue_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $r = &$this->PgGame->row;

        $this->PgGame->query('UPDATE build_army SET status = $2 WHERE buil_army_pk = $1', [$_queue_pk, 'F']);
        $this->PgGame->query('UPDATE build SET concurr_curr = concurr_curr - 1, last_update_dt = now() WHERE buil_pk = $1 AND concurr_curr > 0', [$r['buil_pk']]);
        $this->PgGame->query('UPDATE build SET status = $2 WHERE buil_pk = $1 AND concurr_curr = 0 AND queue_curr = 0', [$r['buil_pk'], 'I']);

        return ['m_army_pk' => $r['m_army_pk'], 'build_number' => $r['build_number'], 'buil_pk' => $r['buil_pk'], 'start_dt' => $r['start_dt']];
    }

    function cancel($_queue_pk): false|array
    {
        $this->PgGame->query('SELECT buil_pk, m_army_pk, build_number FROM build_army WHERE buil_army_pk = $1', [$_queue_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $r = &$this->PgGame->row;

        $this->PgGame->query('UPDATE build_army SET status = $2 WHERE buil_army_pk = $1', [$_queue_pk, 'C']);
        $this->PgGame->query('UPDATE build SET concurr_curr = concurr_curr - 1, last_update_dt = now() WHERE buil_pk = $1 AND concurr_curr > 0', [$r['buil_pk']]);
        $this->PgGame->query('UPDATE build SET status = $2 WHERE buil_pk = $1 AND concurr_curr = 0 AND queue_curr = 0', [$r['buil_pk'], 'I']);

        return ['m_army_pk' => $r['m_army_pk'], 'build_number' => $r['build_number'], 'buil_pk' => $r['buil_pk'], 'queue_pk' => $_queue_pk];
    }

    function getQueueCurrent($_posi_pk, $_in_cast_pk): array
    {
        $queue = [];
        $this->PgGame->query('SELECT buil_army_pk, m_army_pk, build_number FROM build_army
WHERE buil_pk = (SELECT buil_pk FROM build WHERE posi_pk = $1 AND in_cast_pk = $2) AND status = $3 ORDER BY buil_army_pk', [$_posi_pk, $_in_cast_pk, 'I']);
        while ($this->PgGame->fetch()) {
            $r = $this->PgGame->row;
            $queue[$r['buil_army_pk']] = $r;
        }
        return $queue;
    }

    function queue($_buil_pk, $_posi_pk): void
    {
        $this->classResource();
        $this->classGoldPop();
        $this->classFigureReCalc();
        $this->classEffect();
        $this->classArmy();

        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army', 'condition']);

        $this->PgGame->query('SELECT buil_army_pk, m_army_pk, build_time, build_number FROM build_army WHERE buil_pk = $1 AND status = $2 order by buil_army_pk limit 1', [$_buil_pk, 'I']);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;

        if(isset($row['buil_army_pk'])) {
            $build_time = $row['build_time'];

            $this->PgGame->query('UPDATE build SET status = $2, concurr_curr = concurr_curr + 1, queue_curr = queue_curr - 1, last_update_dt = now() WHERE buil_pk = $1', [$_buil_pk, 'P']);
            $this->PgGame->query('SELECT in_cast_pk FROM build WHERE buil_pk = $1', [$_buil_pk]);
            $in_cast_pk = $this->PgGame->fetchOne();

            $this->PgGame->query("UPDATE build_army set status = $3, build_time = $2, start_dt = now(), end_dt = now() + interval '$build_time second' WHERE buil_army_pk = $1", [$row['buil_army_pk'], $build_time, 'P']);

            $description = $_M['ARMY'][$row['m_army_pk']]['title'] . ' (' . $row['build_number'] . ')';

            $this->Timer->set($_posi_pk, 'A', $row['buil_army_pk'], 'U', $description, $build_time, $in_cast_pk);
            $this->Army->get($_posi_pk);

            //Log
            $this->classLog();
            $time_pk = $this->PgGame->currSeq('timer_time_pk_seq');
            $log_description = "{$row['m_army_pk']}[{$row['build_number']}];";
            $this->Log->setArmy($this->Session->lord['lord_pk'], $_posi_pk, 'training_queue', $log_description, $row['buil_army_pk'], $_buil_pk, $row['m_army_pk'], null, null, $row['build_time'], null, $row['build_number'], $time_pk);
        }
    }

    function queueCancel($_queue_pk): false|array
    {
        $this->PgGame->query('SELECT buil_pk, m_army_pk, build_number FROM build_army WHERE buil_army_pk = $1', [$_queue_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $r = &$this->PgGame->row;
        $this->PgGame->query('UPDATE build_army SET status = $2 WHERE buil_army_pk = $1', [$_queue_pk, 'C']);
        $this->PgGame->query('UPDATE build SET queue_curr = queue_curr - 1, last_update_dt = now() WHERE buil_pk = $1 AND queue_curr > 0', [$r['buil_pk']]);

        return ['m_army_pk' => $r['m_army_pk'], 'build_number' => $r['build_number'], 'buil_pk' => $r['buil_pk']];
    }

    function getCurrent($_posi_pk, $_in_cast_pk): array
    {
        // TODO abstime 변경 필요.
        $this->PgGame->query('SELECT buil_army_pk, status, m_army_pk, priority, build_number, build_time, build_time_reduce, date_part(\'epoch\', end_dt)::integer AS end_dt
FROM build_army WHERE buil_pk = (SELECT buil_pk FROM build WHERE posi_pk = $1 AND in_cast_pk = $2) AND status IN ($3, $4)
ORDER BY priority, buil_army_pk', [$_posi_pk, $_in_cast_pk, 'P', 'I']);

        $current = ['concurr' => ['count' => 0], 'queue' => ['count' => 0]];
        while ($this->PgGame->fetch()) {
            $r = $this->PgGame->row;
            if ($r['status'] == 'P') {
                $current['concurr'][$r['buil_army_pk']] = $r;
                $current['concurr']['end_dt'] = $r['end_dt'];
                $current['concurr']['count']++;
            } else {
                $current['queue'][$r['buil_army_pk']] = $r;
                $current['queue']['count']++;
            }
        }
        return $current;
    }
}