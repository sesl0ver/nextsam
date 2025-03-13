<?php

class BuildTechnique
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Hero $Hero;

    public function __construct(Session $_Session, Pg $_PgGame, Hero $_Hero)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->Hero = $_Hero;
    }

    protected function classHero (): void
    {
        if (! isset($this->Hero)) {
            $this->Hero = new Hero($this->Session, $this->PgGame);
        }
    }

    function set($_buil_pk, $_cmd_hero_pk, $_m_tech_pk, $_current_level, $_build_time): false|int
    {
        $r = $this->PgGame->query("INSERT INTO build_technique (buil_pk, cmd_hero_pk, m_tech_pk, status, priority, current_level, description, regist_dt, start_dt, build_time, build_time_reduce, end_dt)
VALUES ($1, $2, $3, 'P', 1, $4, '-', now(), now(), $5, 0, now() + interval '$_build_time second')", [$_buil_pk, $_cmd_hero_pk, $_m_tech_pk, $_current_level, $_build_time]);
        if (!$r) {
            return false;
        }

        $queue_pk = $this->PgGame->currSeq('build_technique_buil_tech_pk_seq');
        $this->PgGame->query('UPDATE build SET status = $2, concurr_curr = concurr_curr+1, last_update_dt = now() WHERE buil_pk = $1', [$_buil_pk, 'P']);

        return $queue_pk;
    }

    function finish($_queue_pk): false|array
    {
        $this->PgGame->query('SELECT buil_pk, m_tech_pk, current_level, cmd_hero_pk, start_dt, build_time FROM build_technique WHERE buil_tech_pk = $1', [$_queue_pk]);
        if (!$this->PgGame->fetch()) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '영웅 해제 못함;build_technique의 buil_tech_pk찾지 못함');
            return false;
        }
        $r = $this->PgGame->row;

        $this->PgGame->query('UPDATE build_technique SET status = $2 WHERE buil_tech_pk = $1', [$_queue_pk, 'F']);
        $this->PgGame->query('UPDATE build SET concurr_curr = concurr_curr - 1, last_update_dt = now() WHERE buil_pk = $1 AND concurr_curr > 0', [$r['buil_pk']]);
        $this->PgGame->query('UPDATE build SET status = $2 WHERE buil_pk = $1 AND concurr_curr = 0', [$r['buil_pk'], 'I']);

        if ($r['cmd_hero_pk']) {
            $this->classHero();
            $this->Hero->unsetCommand($r['cmd_hero_pk'], true, $r['build_time']);
        }

        return ['m_tech_pk' => $r['m_tech_pk'], 'current_level' => $r['current_level'], 'hero_pk' => $r['cmd_hero_pk'], 'start_dt' => $r['start_dt']];
    }

    function cancel($_queue_pk): false|array
    {
        $this->PgGame->query('SELECT buil_pk, m_tech_pk, current_level, cmd_hero_pk, buil_tech_pk FROM build_technique WHERE buil_tech_pk = $1', [$_queue_pk]);
        if (!$this->PgGame->fetch()) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '영웅 해제 못함;build_technique의 buil_tech_pk찾지 못함;queue_pk['.$_queue_pk.'];');
            return false;
        }

        $r = $this->PgGame->row;

        $this->PgGame->query('UPDATE build_technique SET status = $2 WHERE buil_tech_pk = $1', [$_queue_pk, 'C']);
        $this->PgGame->query('UPDATE build SET concurr_curr = concurr_curr-1, last_update_dt = now() WHERE buil_pk = $1 AND concurr_curr > 0', [$r['buil_pk']]);
        $this->PgGame->query('UPDATE build SET status = $2 WHERE buil_pk = $1 AND concurr_curr = 0', [$r['buil_pk'], 'I']);

        $this->classHero();
        $this->Hero->unsetCommand($r['cmd_hero_pk']);

        return ['m_tech_pk' => $r['m_tech_pk'], 'current_level' => $r['current_level'], 'hero_pk' => $r['cmd_hero_pk'], 'queue_pk' => $r['buil_tech_pk']];
    }
}