<?php

class Queue
{
    protected Session $Session;
    protected Pg $PgGame;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    function get(): void
    {
        $this->PgGame->query('SELECT buil_army_pk as queue_pk, m_army_pk as master_pk, \'army\' as queue_type, build_time, build_time_reduce, date_part(\'epoch\', start_dt)::integer as start_dt, date_part(\'epoch\', end_dt)::integer as end_dt FROM build_army WHERE buil_pk IN (SELECT build.buil_pk FROM build WHERE posi_pk = $1 AND type = \'A\') UNION
                                         SELECT buil_cons_pk as queue_pk, m_buil_pk as master_pk, \'construction\' as queue_type, build_time, build_time_reduce, date_part(\'epoch\', start_dt)::integer as start_dt, date_part(\'epoch\', end_dt)::integer as end_dt FROM build_construction WHERE buil_pk IN (SELECT build.buil_pk FROM build WHERE posi_pk = $1 AND type = \'C\') UNION
                                         SELECT buil_fort_pk as queue_pk, m_fort_pk as master_pk, \'fortification\' as queue_type, build_time, build_time_reduce, date_part(\'epoch\', start_dt)::integer as start_dt, date_part(\'epoch\', end_dt)::integer as end_dt FROM build_fortification WHERE buil_pk IN (SELECT build.buil_pk FROM build WHERE posi_pk = $1 AND type = \'F\') UNION
                                         SELECT buil_fort_vall_pk as queue_pk, m_fort_pk as master_pk, \'fortification_valley\' as queue_type, build_time, build_time_reduce, date_part(\'epoch\', start_dt)::integer as start_dt, date_part(\'epoch\', end_dt)::integer as end_dt FROM build_fortification_valley WHERE buil_pk IN (SELECT build.buil_pk FROM build WHERE posi_pk = $1 AND type = \'W\') UNION
                                         SELECT buil_medi_pk as queue_pk, m_army_pk as master_pk, \'medical\' as queue_type, build_time, build_time_reduce, date_part(\'epoch\', start_dt)::integer as start_dt, date_part(\'epoch\', end_dt)::integer as end_dt FROM build_medical WHERE buil_pk IN (SELECT build.buil_pk FROM build WHERE posi_pk = $1 AND type = \'M\') UNION
                                         SELECT buil_tech_pk as queue_pk, m_tech_pk as master_pk, \'technique\' as queue_type, build_time, build_time_reduce, date_part(\'epoch\', start_dt)::integer as start_dt, date_part(\'epoch\', end_dt)::integer as end_dt FROM build_technique WHERE buil_pk IN (SELECT build.buil_pk FROM build WHERE posi_pk = $1 AND type = \'T\')', [$this->Session->lord['main_posi_pk']]);
        $this->PgGame->fetchAll();

        $data = [];
        foreach ($this->PgGame->rows as $row) {
            $data[$row['queue_pk']] = $row;
        }
        $this->Session->sqAppend('QUEUE', $data);
    }

    function getData ($_type, $_queue_pk): array
    {
        if ($_type === 'army') {
            $this->PgGame->query('SELECT buil_army_pk as queue_pk, m_army_pk as master_pk, \'army\' as queue_type, build_time, build_time_reduce, date_part(\'epoch\', start_dt)::integer as start_dt, date_part(\'epoch\', end_dt)::integer as end_dt FROM build_army WHERE buil_army_pk = $1', [$_queue_pk]);
        } else if ($_type === 'construction') {
            $this->PgGame->query('SELECT buil_cons_pk as queue_pk, m_buil_pk as master_pk, \'construction\' as queue_type, build_time, build_time_reduce, date_part(\'epoch\', start_dt)::integer as start_dt, date_part(\'epoch\', end_dt)::integer as end_dt FROM build_construction WHERE buil_cons_pk = $1', [$_queue_pk]);
        } else if ($_type === 'fortification') {
            $this->PgGame->query('SELECT buil_fort_pk as queue_pk, m_fort_pk as master_pk, \'fortification\' as queue_type, build_time, build_time_reduce, date_part(\'epoch\', start_dt)::integer as start_dt, date_part(\'epoch\', end_dt)::integer as end_dt FROM build_fortification WHERE buil_fort_pk = $1', [$_queue_pk]);
        } else if ($_type === 'fortification_valley') {
            $this->PgGame->query('SELECT buil_fort_vall_pk as queue_pk, m_fort_pk as master_pk, \'fortification_valley\' as queue_type, build_time, build_time_reduce, date_part(\'epoch\', start_dt)::integer as start_dt, date_part(\'epoch\', end_dt)::integer as end_dt FROM build_fortification_valley WHERE buil_fort_vall_pk = $1', [$_queue_pk]);
        } else if ($_type === 'medical') {
            $this->PgGame->query('SELECT buil_medi_pk as queue_pk, m_army_pk as master_pk, \'medical\' as queue_type, build_time, build_time_reduce, date_part(\'epoch\', start_dt)::integer as start_dt, date_part(\'epoch\', end_dt)::integer as end_dt FROM build_medical WHERE buil_medi_pk = $1', [$_queue_pk]);
        } else if ($_type === 'technique') {
            $this->PgGame->query('SELECT buil_tech_pk as queue_pk, m_tech_pk as master_pk, \'technique\' as queue_type, build_time, build_time_reduce, date_part(\'epoch\', start_dt)::integer as start_dt, date_part(\'epoch\', end_dt)::integer as end_dt FROM build_technique WHERE buil_tech_pk = $1', [$_queue_pk]);
        }
        $this->PgGame->fetch();
        return $this->PgGame->row ?? [];
    }
}