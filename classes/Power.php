<?php

class Power
{
    public Session $Session;
    public Pg $PgGame;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    // 건물 영향력
    function getBuildingPower($_posi_pk): int
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['building']);
        $power_values = 0;
        $this->PgGame->query('SELECT m_buil_pk, level FROM building_in_castle WHERE posi_pk = $1', [$_posi_pk]);
        while($this->PgGame->fetch()) {
            $power_values += $_M['BUIL'][$this->PgGame->row['m_buil_pk']]['level'][$this->PgGame->row['level']]['power'];
        }
        $this->PgGame->query('SELECT m_buil_pk, level FROM building_out_castle WHERE posi_pk = $1', [$_posi_pk]);
        while($this->PgGame->fetch()) {
            $power_values += $_M['BUIL'][$this->PgGame->row['m_buil_pk']]['level'][$this->PgGame->row['level']]['power'];
        }
        return $power_values;
    }

    // 기술 영향력
    function getTechniquePower($_posi_pk): int
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['technique']);
        $this->PgGame->query('SELECT agriculture, stock_farming, lumbering, mining, storage, construction, astronomy, paper, medicine, smelting, casting, machinery, mil_fencing, mil_shield, mil_spear, mil_horse, mil_science, fortification, compass, logistics, informatics, mil_archery, mil_formation, mil_siege FROM technique WHERE posi_pk = $1', [$_posi_pk]);
        if (!$this->PgGame->fetch()) {
            return 0;
        }
        $power_values = 0;
        foreach ($this->PgGame->row AS $k => $v) {
            $m_tech_pk = $_M['TECH_C'][$k]['m_tech_pk'];
            if ($v > 0) {
                $power_values += $_M['TECH'][$m_tech_pk]['level'][$v]['power'];
            }
        }
        return $power_values;
    }

    // 군주태학기술 영향력
    function getLordTechniquePower($_lord_pk)
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['technique']);

        $this->PgGame->query('SELECT agriculture, stock_farming, lumbering, mining, storage, construction, astronomy, paper, medicine, smelting, casting, machinery, mil_fencing, mil_shield, mil_spear, mil_horse, mil_science, fortification, compass, logistics, informatics, mil_archery, mil_formation, mil_siege FROM lord_technique WHERE lord_pk = $1', [$_lord_pk]);
        if (!$this->PgGame->fetch()) {
            return 0;
        }
        $power_values = 0;
        foreach ($this->PgGame->row AS $k => $v) {
            if ($v > 0) {
                $m_tech_pk = $_M['TECH_C'][$k]['m_tech_pk'];
                $power_values += $_M['TECH'][$m_tech_pk]['level'][$v]['power'];
            }
        }
        return $power_values;
    }

    // 영웅 영향력
    function getHeroPower($_lord_pk): int
    {
        global $_M;
        $this->PgGame->query('SELECT level FROM hero WHERE hero_pk IN (SELECT hero_pk FROM my_hero WHERE lord_pk = $1 AND status = $2) AND create_reason != $3', [$_lord_pk, 'A', 'regist']);
        $power_values = 0;
        while($this->PgGame->fetch()) {
            $power_values += $_M['HERO_APPOINT_POWER'][$this->PgGame->row['level']]['total_power'];
        }
        return $power_values;
    }

    // 영지 영향력
    function getTerritoryPower($_lord_pk): int
    {
        $this->PgGame->query('SELECT COUNT(posi_pk) FROM position WHERE lord_pk = $1 AND type = $2', [$_lord_pk, 'T']);
        return $this->PgGame->fetchOne() * 500;
    }

    // 자원지 영향력
    function getVallyPower($_lord_pk): int
    {
        $this->PgGame->query('SELECT COUNT(posi_pk) FROM position WHERE lord_pk = $1 AND type NOT IN ($2, $3)', [$_lord_pk, 'T', 'P']);
        return $this->PgGame->fetchOne() * 50;
    }

    // 퀘스트 영향력
    function getQuestPower($_lord_pk): int
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);
        $power = 0;
        $power_quest_list = [];
        $power_quest_info = [];
        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['power'] > 0) {
                $power_quest_list[] = $k;
                $power_quest_info[$k] = $v['power'];
            }
        }
        if (COUNT($power_quest_list)) {
            $str = implode(',', $power_quest_list);
            $this->PgGame->query("SELECT m_ques_pk FROM my_quest WHERE lord_pk = $1 AND m_ques_pk IN ({$str}) AND status = $2 AND reward_status = $3", [$_lord_pk, 'C', 'Y']);
            $this->PgGame->fetchAll();
            $rows = $this->PgGame->rows;
            if ($rows) {
                foreach($rows AS $v) {
                    foreach($power_quest_info AS $k1 => $v1) {
                        if ($v['m_ques_pk'] == $k1) {
                            $power += $v1;
                        }
                    }
                }
            }
        }
        return $power;
    }
}