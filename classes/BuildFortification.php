<?php

class BuildFortification
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Timer $Timer;

    public function __construct(Session $_Session, Pg $_PgGame, Timer $_Timer)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->Timer = $_Timer;
    }

    function set($_buil_pk, $_m_fort_pk, $_build_number, $_build_time): false|int
    {
        $r = $this->PgGame->query("INSERT INTO build_fortification
(buil_pk, m_fort_pk, status, priority, build_number, description, regist_dt, start_dt, build_time, build_time_reduce, end_dt)
VALUES ($1, $2, 'P', 1, $3, '-', now(), now(), $4, 0, now() + interval '$_build_time second')", [$_buil_pk, $_m_fort_pk, $_build_number, $_build_time]);
        if (!$r) {
            return false;
        }

        $queue_pk = $this->PgGame->currSeq('build_fortification_buil_fort_pk_seq');
        $this->PgGame->query('UPDATE build SET status = $2, concurr_curr = concurr_curr + 1, last_update_dt = now() WHERE buil_pk = $1', [$_buil_pk, 'P']);

        return $queue_pk;
    }

    function finish($_queue_pk): false|array
    {
        $this->PgGame->query('SELECT buil_pk, m_fort_pk, build_number, start_dt FROM build_fortification WHERE buil_fort_pk = $1', [$_queue_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $r = &$this->PgGame->row;
        $this->PgGame->query('UPDATE build_fortification SET status = $2 WHERE buil_fort_pk = $1', [$_queue_pk, 'F']);
        $this->PgGame->query('UPDATE build SET concurr_curr = concurr_curr - 1, last_update_dt = now() WHERE buil_pk = $1 AND concurr_curr > 0', [$r['buil_pk']]);
        $this->PgGame->query('UPDATE build SET status = $2 WHERE buil_pk = $1 AND concurr_curr = 0', [$r['buil_pk'], 'I']);

        return ['m_fort_pk' => $r['m_fort_pk'], 'build_number' => $r['build_number'], 'buil_pk' => $r['buil_pk'], 'start_dt' => $r['start_dt']];
    }

    function cancel($_queue_pk): false|array
    {
        $this->PgGame->query('SELECT buil_pk, m_fort_pk, build_number FROM build_fortification WHERE buil_fort_pk = $1', [$_queue_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $r = &$this->PgGame->row;

        $this->PgGame->query('UPDATE build_fortification SET status = $2 WHERE buil_fort_pk = $1', [$_queue_pk, 'C']);
        $this->PgGame->query('UPDATE build SET concurr_curr = concurr_curr-1, last_update_dt = now() WHERE buil_pk = $1 AND concurr_curr > 0', [$r['buil_pk']]);
        $this->PgGame->query('UPDATE build SET status = $2 WHERE buil_pk = $1 AND concurr_curr = 0', [$r['buil_pk'], 'I']);

        return ['m_fort_pk' => $r['m_fort_pk'], 'build_number' => $r['build_number'], 'buil_pk' => $r['buil_pk'], 'queue_pk' => $_queue_pk];
    }


}