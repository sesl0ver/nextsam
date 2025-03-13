<?php
// TODO Insert 쿼리를 단일 쿼리로 변경해야할 필요가 있어 보임.
class Log
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Pg $PgLog;
    protected Hero $Hero;
    protected int $server_index = SERVER_INDEX;
    protected mixed $web_channel;
    protected bool $conn_lock = false;
    protected array $log_data = [];

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->PgLog = new Pg('LOG');

        $this->web_channel = $this->Session->web_channel;
    }

    function __destruct()
    {
        if (isset($this->log_data)) {
            if (!$this->conn_lock) {
                $this->PgLog = new Pg('LOG');
            }
            foreach($this->log_data AS $k => $v) {
                call_user_func_array([$this, $v['function']], [$v['param']]);
            }
            unset($this->log_data);
        }
    }

    protected function classHero(): void
    {
        if (! isset($this->Hero)) {
            $this->Hero = new Hero($this->Session, $this->PgGame);
        }
    }

    protected function getPosiPkToLordPk ($_posi_pk): mixed
    {
        $this->PgGame->query('SELECT lord_pk FROM position WHERE posi_pk = $1', [$_posi_pk]);
        return $this->PgGame->fetchOne();
    }

    protected function getWebId ($_lord_pk): mixed
    {
        $this->PgGame->query('SELECT web_id FROM lord_web WHERE lord_pk = $1', [$_lord_pk]);
        return $this->PgGame->fetchOne();
    }

    function setHeroData($_row): void
    {
        $query_params = [$_row['hero_pk'], $_row['m_hero_pk'], $_row['status'], $_row['level'], $_row['rare_type'], $_row['enchant'], $_row['loyalty'], $_row['hp'], $_row['leadership_basic'], $_row['leadership_enchant'], $_row['leadership_plusstat'], $_row['mil_force_basic'], $_row['mil_force_enchant'], $_row['mil_force_plusstat'], $_row['intellect_basic'], $_row['intellect_enchant'], $_row['intellect_plusstat'], $_row['politics_basic'], $_row['politics_enchant'], $_row['politics_plusstat'], $_row['charm_basic'], $_row['charm_enchant'], $_row['charm_plusstat'],$_row['yn_trade'], $_row['allow_trade_cnt'], $_row['spend_trade_cnt'], $_row['create_reason'], $_row['create_dt'], $_row['skill_exp'], $_row['leadership_skill'], $_row['mil_force_skill'], $_row['intellect_skill'], $_row['politics_skill'], $_row['charm_skill']];
        $this->log_data[] = ['function' => 'setHeroDataLog', 'param' => $query_params];
    }

    function setHeroDataLog($_params): void
    {
        /*$this->PgGame->query('INSERT INTO data_hero (
hero_pk, m_hero_pk, status, level, rare_type, enchant, loyalty, hp,
leadership_basic, leadership_enchant, leadership_plusstat,
mil_force_basic, mil_force_enchant, mil_force_plusstat,
intellect_basic, intellect_enchant, intellect_plusstat,
politics_basic, politics_enchant, politics_plusstat,
charm_basic, charm_enchant, charm_plusstat,
yn_trade, allow_trade_cnt, spend_trade_cnt, create_reason, create_dt, skill_exp,
leadership_skill, mil_force_skill, intellect_skill, politics_skill, charm_skill
) VALUES (
$1, $2, $3, $4, $5, $6, $7, $8,
$9, $10, $11,
$12, $13, $14,
$15, $16, $17,
$18, $19, $20,
$21, $22, $23,
$24, $25, $26, $27, $28, $29,
$30, $31, $32, $33, $34
)', $_params);*/ // 실행 안함. TODO 왜!? 굳이 영웅을 일일히 기록 할 필요 없다 이건가?
    }

    function setTimerData($_row): void
    {
        $query_params = [$_row['time_pk'], $_row['posi_pk'], $_row['status'], $_row['queue_type'], $_row['queue_pk'], $_row['queue_action'], $_row['description'], $_row['regist_dt'], $_row['start_dt'], $_row['build_time'], $_row['build_time_reduce'], $_row['end_dt'], $_row['in_cast_pk'], $_row['out_cast_pk']];
        $this->log_data[] = ['function' => 'setTimerDataLog', 'param' => $query_params];
    }

    function setTimerDataLog($_params): void
    {
        /*this->PgGame->query('INSERT INTO data_timer (
time_pk, posi_pk, status, queue_type, queue_pk, queue_action, description,
regist_dt, start_dt, build_time, build_time_reduce, end_dt,
in_cast_pk, out_cast_pk
) VALUES (
$1, $2, $3, $4, $5, $6, $7,
$8, $9, $10, $11, $12,
$13, $14
)', $_params);*/ // 실행 안함. TODO 마찬가지로 일일히 기록 하기 뭐하다 이건가?
    }

    function setArmy($_lord_pk, $_posi_pk, $_type, $_description, $_buil_army_pk = null, $_buil_pk = null, $_m_army_pk = null, $_regist_dt = null, $_start_dt = null, $_build_time = null, $_build_time_reduce = null, $_build_number = null, $_time_pk = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_buil_army_pk, $_buil_pk, $_m_army_pk, $_regist_dt, $_start_dt, $_build_time, $_build_time_reduce, $_build_number, $_description, $_time_pk];
        $this->log_data[] = ['function' => 'setArmyLog', 'param' => $query_params];
    }

    function setArmyLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_army (log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, buil_army_pk, buil_pk, m_army_pk, regist_dt, start_dt, build_time, build_time_reduce, build_number, army_info, time_pk)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16 )', $_params);
    }

    function setFortification($_lord_pk, $_posi_pk, $_type, $_description, $_buil_fort_pk = null, $_buil_pk = null, $_m_fort_pk = null, $_regist_dt = null, $_start_dt = null, $_build_time = null, $_build_time_reduce = null, $_build_number = null, $_time_pk = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_buil_fort_pk, $_buil_pk, $_m_fort_pk, $_regist_dt, $_start_dt, $_build_time, $_build_time_reduce, $_build_number, $_description, $_time_pk];
        $this->log_data[] = ['function' => 'setFortificationLog', 'param' => $query_params];
    }

    function setFortificationLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_fortification (log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, buil_fort_pk, buil_pk, m_fort_pk, regist_dt, start_dt, build_time, build_time_reduce, build_number, fort_info, time_pk)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16)', $_params);
    }

    function setConstruction($_lord_pk, $_posi_pk, $_type, $_description = null, $_buil_cons_pk = null, $_buil_pk = null, $_hero_pk = null, $_m_buil_pk = null, $_regist_dt = null, $_start_dt = null, $_build_time = null, $_build_time_reduce = null, $_position_type = null, $_position = null, $_current_level = null, $_time_pk = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);

        $this->classHero();
        $m_hero_pk = ($_hero_pk) ? $this->Hero->getMasterHeroPk($_hero_pk) : null;

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_buil_cons_pk, $_buil_pk, $_hero_pk, $m_hero_pk, $_m_buil_pk, $_regist_dt, $_start_dt, $_build_time, $_build_time_reduce, $_position_type, $_position, $_current_level, $_description, $_time_pk];
        $this->log_data[] = ['function' => 'setConstructionLog', 'param' => $query_params];
    }

    function setConstructionLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_construction ( log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, buil_cons_pk, buil_pk, hero_pk, m_hero_pk, m_buil_pk, regist_dt, start_dt, build_time, build_time_reduce, position_type, position, current_level, buil_info, time_pk)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20)', $_params);
    }

    function setTechnique($_lord_pk, $_posi_pk, $_type, $_description = null, $_buil_tech_pk = null, $_buil_pk = null, $_hero_pk = null, $_m_tech_pk = null, $_regist_dt = null, $_start_dt = null, $_build_time = null, $_build_time_reduce = null, $_current_level = null, $_time_pk = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);
        $this->classHero();
        $m_hero_pk = ($_hero_pk) ? $this->Hero->getMasterHeroPk($_hero_pk) : null;

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_buil_tech_pk, $_buil_pk, $_hero_pk, $m_hero_pk, $_m_tech_pk, $_regist_dt, $_start_dt, $_build_time, $_build_time_reduce, $_current_level, $_description, $_time_pk];
        $this->log_data[] = ['function' => 'setTechniqueLog', 'param' => $query_params];
    }

    function setTechniqueLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_Technique (log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, buil_tech_pk, buil_pk, hero_pk, m_hero_pk, m_tech_pk, regist_dt, start_dt, build_time, build_time_reduce, current_level, tech_info, time_pk)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6,$7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18)', $_params);
    }

    function setHeroCommand($_lord_pk, $_posi_pk, $_type, $_hero_pk, $_status, $_status_cmd, $_cmd_type = null, $_description = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);
        $this->classHero();
        $m_hero_pk = ($_hero_pk) ? $this->Hero->getMasterHeroPk($_hero_pk) : null;

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_hero_pk, $m_hero_pk, $_status, $_status_cmd, $_cmd_type, $_description];
        $this->log_data[] = ['function' => 'setHeroCommandLog', 'param' => $query_params];
    }

    function setHeroCommandLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_hero_command (log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, hero_pk, m_hero_pk, status, status_cmd, cmd_type, description)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)', $_params);
    }

    function setHero($_lord_pk, $_posi_pk, $_type, $_hero_pk, $_status, $_status_cmd, $_cmd_type = null, $_description = null, $_m_hero_pk = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);
        $this->classHero();
        $m_hero_pk = ($_hero_pk && !$_m_hero_pk) ? $this->Hero->getMasterHeroPk($_hero_pk) : $_m_hero_pk;

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_hero_pk, $m_hero_pk, $_status, $_status_cmd, $_cmd_type, $_description];
        $this->log_data[] = ['function' => 'setHeroLog', 'param' => $query_params];
    }

    function setHeroLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_hero (log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, hero_pk, m_hero_pk, status, status_cmd, cmd_type, description)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6,$7, $8, $9, $10, $11, $12)', $_params);
    }

    function setHeroEnchant($_lord_pk, $_posi_pk, $_type, $_hero_pk, $_status, $_status_cmd, $_cmd_type = null, $_enchant_cnt = null, $_m_item_pk = null, $_description = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);
        $this->classHero();
        $m_hero_pk = ($_hero_pk) ? $this->Hero->getMasterHeroPk($_hero_pk) : null;

        $_m_item_pk = $_m_item_pk == 'null' ? null : $_m_item_pk;

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_hero_pk, $m_hero_pk, $_status, $_status_cmd, $_cmd_type, $_enchant_cnt, $_m_item_pk, $_description];
        $this->log_data[] = ['function' => 'setHeroEnchantLog', 'param' => $query_params];
    }

    function setHeroEnchantLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_hero_enchant (log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, hero_pk, m_hero_pk, status, status_cmd, cmd_type, enchant_cnt, m_item_pk, description)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14)', $_params);
    }

    function setHeroEncounter($_lord_pk, $_posi_pk, $_type, $_hero_enco_pk = null, $_hero_pk = null, $_start_dt = null, $_end_dt = null, $_encounter_type = null, $_encounter_value = null, $_invitation_cnt = null, $_yn_invited = null, $_time_pk = null, $_distance_type = null, $_m_item_pk = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);
        $this->classHero();
        $m_hero_pk = ($_hero_pk) ? $this->Hero->getMasterHeroPk($_hero_pk) : null;

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_hero_enco_pk, $_hero_pk, $m_hero_pk, $_start_dt, $_end_dt, $_encounter_type, $_encounter_value, $_invitation_cnt, $_yn_invited, $_time_pk, $_distance_type, (!$_m_item_pk) ? 0 : $_m_item_pk];
        $this->log_data[] = ['function' => 'setHeroEncounterLog', 'param' => $query_params];
    }

    function setHeroEncounterLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_hero_encounter (log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, hero_enco_pk, hero_pk, m_hero_pk, start_dt, end_dt, encounter_type, encounter_value, invitation_cnt, yn_invited, time_pk, distance_type, m_item_pk)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18)', $_params);
    }

    function setHeroInvitation($_lord_pk, $_posi_pk, $_type, $_hero_pk = null, $_hero_invi_pk = null, $_hero_enco_pk = null, $_start_dt = null, $_end_dt = null, $_send_gold = null, $_result_value = null, $_result_status = null, $_time_pk = null, $_invit_cnt = null, $_enco_hero_pk = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);
        $this->classHero();
        $m_hero_pk = ($_hero_pk) ? $this->Hero->getMasterHeroPk($_hero_pk) : null;

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_hero_pk, $m_hero_pk, $_hero_invi_pk, $_hero_enco_pk, $_start_dt, $_end_dt, $_send_gold, $_result_value, $_result_status, $_time_pk, $_invit_cnt, $_enco_hero_pk];
        $this->log_data[] = ['function' => 'setHeroInvitationLog', 'param' => $query_params];
    }

    function setHeroInvitationLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_hero_invitation (log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, hero_pk, m_hero_pk, hero_invi_pk, hero_enco_pk, start_dt, end_dt, send_gold, result_value, result_status, time_pk, invit_cnt, enco_hero_pk)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18)', $_params);
    }

    function setBuildingAssign($_lord_pk, $_posi_pk, $_type, $_hero_pk = null, $_m_buil_pk = null, $_in_castle_pk = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);
        $this->classHero();
        $m_hero_pk = ($_hero_pk) ? $this->Hero->getMasterHeroPk($_hero_pk) : null;

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_hero_pk, $m_hero_pk, $_m_buil_pk, $_in_castle_pk];
        $this->log_data[] = ['function' => 'setBuildingAssignLog', 'param' => $query_params];
    }

    function setBuildingAssignLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_building_assign (log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, hero_pk, m_hero_pk, m_buil_pk, in_castle_pk)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10)', $_params);
    }

    function setBuildingMedical($_lord_pk, $_posi_pk, $_type, $_hero_pk = null, $_gold = null, $_description = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);
        $this->classHero();
        $m_hero_pk = ($_hero_pk) ? $this->Hero->getMasterHeroPk($_hero_pk) : null;

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_hero_pk, $m_hero_pk, $_gold, $_description];
        $this->log_data[] = ['function' => 'setBuildingMedicalLog', 'param' => $query_params];
    }

    function setBuildingMedicalLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_building_medical (log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, hero_pk, m_hero_pk, gold, description)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10)', $_params);
    }

    function setBuildingReceptionhall($_lord_pk, $_posi_pk, $_type, $_hero_free_pk = null, $_gold = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);
        $this->classHero();
        $m_hero_pk = ($_hero_free_pk) ? $this->Hero->getFreeMasterHeroPk($_hero_free_pk) : null;

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_hero_free_pk, $m_hero_pk, $_gold];
        $this->log_data[] = ['function' => 'setBuildingReceptionhallLog', 'param' => $query_params];
    }

    function setBuildingReceptionhallLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_building_receptionhall (log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, hero_pk, m_hero_pk, gold)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9)', $_params);
    }

    function setBuildingAdministration($_lord_pk, $_posi_pk, $_type, $_description = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_description];
        $this->log_data[] = ['function' => 'setBuildingAdministrationLog', 'param' => $query_params];
    }

    function setBuildingAdministrationLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_building_administration(log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, description)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7)', $_params);
    }

    function setBuildingStorage($_lord_pk, $_posi_pk, $_type, $_rate = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_rate];
        $this->log_data[] = ['function' => 'setBuildingStorageLog', 'param' => $query_params];
    }

    function setBuildingStorageLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_building_storage(log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, rate)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7)', $_params);
    }

    function setBuildingTradedept($_lord_pk, $_posi_pk, $_type, $_reso_type = null, $_amount = null, $_unit_price = null, $_deal_amount = null, $_description = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);
        $_reso_type = match (true) {
            ($_reso_type == 'F') => 'food',
            ($_reso_type == 'H') => 'horse',
            ($_reso_type == 'L') => 'lumber',
            ($_reso_type == 'I') => 'iron',
            default => $_reso_type
        };
        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_reso_type, $_amount, $_unit_price, $_deal_amount, $_description];
        $this->log_data[] = ['function' => 'setBuildingTradedeptLog', 'param' => $query_params];
    }

    function setBuildingTradedeptLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_building_tradedept(log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, reso_type, amount, unit_price, deal_amount, description)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)', $_params);
    }

    function setBuildingAlliance($_lord_pk, $_posi_pk, $_type, $_description = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_description];
        $this->log_data[] = ['function' => 'setBuildingAllianceLog', 'param' => $query_params];
    }

    function setBuildingAllianceLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_building_alliance(log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, description)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7)', $_params);
    }

    function setBuildingMarket($_lord_pk, $_posi_pk, $_m_item_pk, $_reso_type, $_reso_amount, $_buy_type, $_buy_amount, $_qbig = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $this->web_channel, $this->server_index, $_m_item_pk, $_reso_type, $_reso_amount, $_qbig, $_buy_type, $_buy_amount];
        $this->log_data[] = ['function' => 'setBuildingMarketLog', 'param' => $query_params];
    }

    function setBuildingMarketLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_building_market(log_date, lord_pk, web_id, posi_pk, web_channel, server_index, m_item_pk, reso_type, reso_amount, qbig, buy_type, buy_amount)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)', $_params);
    }


    function setChangeName($_lord_pk, $_posi_pk, $_type, $_description = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_description];
        $this->log_data[] = ['function' => 'setChangeNameLog', 'param' => $query_params];
    }

    function setChangeNameLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_change_name(log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, description)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7)', $_params);
    }

    function setHeroSalary($_lord_pk, $_posi_pk, $_type, $_gold = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_gold];
        $this->log_data[] = ['function' => 'setHeroSalaryLog', 'param' => $query_params];
    }

    function setHeroSalaryLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_hero_salary(log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, gold)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7)', $_params);
    }

    function setTerritory($_lord_pk, $_posi_pk, $_type, $_description = null, $_in_castle_info = null, $_out_castle_info = null, $_army_info = null, $_fort_info = null, $_reso_info = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_description, $_in_castle_info, $_out_castle_info, $_army_info, $_fort_info, $_reso_info];
        $this->log_data[] = ['function' => 'setTerritoryLog', 'param' => $query_params];
    }

    function setTerritoryLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_territory(log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, description, in_castle_info, out_castle_info, army_info, fort_info, reso_info)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)', $_params);
    }

    function setQbig($_lord_pk, $_posi_pk, $_type, $_before_cash, $_price, $_after_cash, $_bill_qbig, $_reason, $_level = 0): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_before_cash, $_price, $_after_cash, $_bill_qbig, $_reason, $_level];
        $this->log_data[] = ['function' => 'setQbigLog', 'param' => $query_params];
    }

    function setQbigLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_qbig(log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, before_cash, price, after_cash, bill_qbig, reason, level)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)', $_params);
    }

    function setSuppress($_lord_pk, $_posi_pk, $_type, $_supp_pk = null, $_target_level = null, $_target_cnt = null, $_suppress_cnt = null, $_suppress_dt = null, $_target_posi_pk_1 = null, $_target_posi_pk_2 = null, $_target_posi_pk_3 = null, $_army_type_1 = null, $_army_type_2 = null, $_army_type_3 = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $web_id = $this->getWebId($_lord_pk);

        $query_params = [$_lord_pk, $web_id, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_supp_pk, $_target_level, $_target_cnt, $_suppress_cnt, $_suppress_dt, $_target_posi_pk_1, $_target_posi_pk_2, $_target_posi_pk_3, $_army_type_1, $_army_type_2, $_army_type_3];
        $this->log_data[] = ['function' => 'setSuppressLog', 'param' => $query_params];
    }

    function setSuppressLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_suppress(log_date, lord_pk, web_id, posi_pk, type, web_channel, server_index, supp_pk, target_level, target_cnt, suppress_cnt, suppress_dt, target_posi_pk_1, target_posi_pk_2, target_posi_pk_3, army_type_1, army_type_2, army_type_3)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17)', $_params);
    }

    function setTroop($_lord_pk, $_posi_pk, $_type, $_dst_lord_pk, $_dst_lord_name, $_dst_posi_pk, $_dst_posi_name, $_hero_info, $_army_info, $_reso_info, $_troo_pk): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;

        $query_params = [$_lord_pk, $_posi_pk, $_type, $_dst_lord_pk, $_dst_lord_name, $_dst_posi_pk, $_dst_posi_name, $_hero_info, $_army_info, $_reso_info, $this->server_index, $this->web_channel, $_troo_pk];
        $this->log_data[] = ['function' => 'setTroopLog', 'param' => $query_params];
    }

    function setTroopLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_troop (log_date, lord_pk, posi_pk, type, dst_lord_pk, dst_lord_name, dst_posi_pk, dst_posi_name, hero_info, army_info, reso_info, server_index, web_channel, troo_pk)
                                         VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13)', $_params);
    }

    function setBattle($_lord_pk, $_posi_pk, $_type, $_dst_lord_pk, $_dst_lord_name, $_dst_posi_pk, $_dst_posi_name, $_title, $_summary, $_content_json, $_battle_type, $_battle_result, $_occupation, $_plunder, $_troo_pk): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;

        $query_params = [$_lord_pk, $_posi_pk, $_type, $_dst_lord_pk, $_dst_lord_name, $_dst_posi_pk, $_dst_posi_name, $_title, $_summary, $_content_json, $_battle_type, $_battle_result, $_occupation, $_plunder, $this->server_index, $this->web_channel, $_troo_pk];
        $this->log_data[] = ['function' => 'setBattleLog', 'param' => $query_params];
    }

    function setBattleLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_battle (log_date, lord_pk, posi_pk, type, dst_lord_pk, dst_lord_name, dst_posi_pk, dst_posi_name, title, summary, content_json, battle_type, battle_result, occupation, plunder, server_index, web_channel, troo_pk)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17)', $_params);
    }

    function setEtc($_lord_pk, $_posi_pk, $_type, $_description = null): void
    {
        $query_params = [$_lord_pk, $_posi_pk, $_type, $_description, $this->server_index, $this->web_channel];
        $this->log_data[] = ['function' => 'setEtcLog', 'param' => $query_params];
    }

    function setEtcLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_etc (log_date, lord_pk, posi_pk, type, description, server_index, web_channel)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6)', $_params);
    }

    function setResource($_lord_pk, $_posi_pk, $_type, $_amount, $_prev = null, $_after = null, $_gold_storage = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $amount = 'before['.$_prev.'];update['.$_amount.'];after['.$_after.'];';
        if ($_gold_storage) {
            $amount .= 'storage['.$_gold_storage.'];';
        }

        $query_params = [$_lord_pk, $_posi_pk, $_type, $amount, $this->server_index, $this->web_channel];
        $this->log_data[] = ['function' => 'setResourceLog', 'param' => $query_params];
    }

    function setResourceLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_resource (log_date, lord_pk, posi_pk, type, amount, server_index, web_channel)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6)', $_params);
    }

    function setAuth($_tk, $_ip): void
    {
        $query_params = [$_tk, $_ip];
        $this->log_data[] = ['function' => 'setAuthLog', 'param' => $query_params];
    }

    function setAuthLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_auth (log_date, tk, ip) VALUES (now(), $1, $2)', $_params);
    }

    function setProduction($_lord_pk, $_posi_pk, $_value): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;

        $query_params = [$_lord_pk, $_posi_pk, $_value, $this->server_index, $this->web_channel];
        $this->log_data[] = ['function' => 'setProductionLog', 'param' => $query_params];
    }

    function setProductionLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_production (log_date, lord_pk, posi_pk, value, server_index, web_channel)
                                        VALUES (now(), $1, $2, $3, $4, $5)', $_params);
    }

    function setHeroSkillExp($_lord_pk, $_posi_pk, $_hero_pk, $_type, $_prev_exp, $_exp, $m_hero_pk = null): void
    {
        $query_params = [$_lord_pk, $_posi_pk, $_hero_pk, $_type, $_prev_exp, $_exp, $m_hero_pk, $this->web_channel, $this->server_index];
        $this->log_data[] = ['function' => 'setHeroSkillExpLog', 'param' => $query_params];
    }

    function setHeroSkillExpLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_hero_skill_exp (log_date, lord_pk, posi_pk, hero_pk, type, prev_exp, exp, m_hero_pk, web_channel, server_index)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9)', $_params);
    }

    function setHeroSkill($_lord_pk, $_posi_pk, $_hero_pk, $_type, $_m_hero_skil_pk, $_m_item_pk = null, $_slot_pk = null, $_prev_cnt = null, $_description = null, $m_hero_pk = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $query_params = [$_lord_pk, $_posi_pk, $_hero_pk, $_type, $_m_hero_skil_pk, $_m_item_pk, $_slot_pk, $_prev_cnt, $_description, $m_hero_pk, $this->web_channel, $this->server_index];
        $this->log_data[] = ['function' => 'setHeroSkillLog', 'param' => $query_params];
    }

    function setHeroSkillLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_hero_skill (log_date, lord_pk, posi_pk, hero_pk, type, m_hero_skil_pk, m_item_pk, slot_pk, prev_cnt, description, m_hero_pk, web_channel, server_index)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)', $_params);
    }

    function setHeroSkillActive($_lord_pk, $_posi_pk, $_hero_pk, $_type, $_rand_value, $_skil_value, $_description = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $query_params = [$_lord_pk, $_posi_pk, $_hero_pk, $_type, $_rand_value, $_skil_value, $_description, $this->web_channel, $this->server_index];
        $this->log_data[] = ['function' => 'setHeroSkillActiveLog', 'param' => $query_params];
    }

    function setHeroSkillActiveLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_hero_skill_active (log_date, lord_pk, posi_pk, hero_pk, type, rand_value, skil_value, description, web_channel, server_index)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9)', $_params);
    }

    function setTemp($_lord_pk, $_posi_pk, $_type, $_col1 = null, $_col2 = null, $_col3 = null, $_col4 = null, $_col5 = null, $_col6 = null, $_col7 = null, $_col8 = null, $_col9 = null, $_col10 = null, $_col11 = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $query_params = [$_lord_pk, $_posi_pk, $_type, $_col1, $_col2, $_col3, $_col4, $_col5, $_col6, $_col7, $_col8, $_col9, $_col10, $_col11, $this->web_channel, $this->server_index];
        $this->log_data[] = ['function' => 'setTempLog', 'param' => $query_params];
    }

    function setTempLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_temp (log_date, lord_pk, posi_pk, type, col1, col2, col3, col4, col5, col6, col7, col8, col9, col10, col11, web_channel, server_index)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16)', $_params);
    }

    function setBuff($_lord_pk, $_posi_pk, $_item_buff_pk, $_m_item_pk, $_type, $_buff_time = 0): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $query_params = [$_lord_pk, $_posi_pk, $_item_buff_pk, $_m_item_pk, $_type, $_buff_time];
        $this->log_data[] = ['function' => 'setBuffLog', 'param' => $query_params];
    }

    function setBuffLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_buff (log_date, lord_pk, posi_pk, buff_pk, m_item_pk, type, buff_time)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6)', $_params);
    }

    function setHeroCombi($_lord_pk, $_posi_pk, $hero_pk, $m_hero_pk, $combi_type, $sn, $type, $consume_gold, $description): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $query_params = [$_lord_pk, $_posi_pk, $hero_pk, $m_hero_pk, $combi_type, $sn, $type, $consume_gold, $description, $this->web_channel, $this->server_index];
        $this->log_data[] = ['function' => 'setHeroCombiLog', 'param' => $query_params];
    }

    function setHeroCombiLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_hero_combi (log_date, lord_pk, posi_pk, hero_pk, m_hero_pk, combi_type, serial_num, used_type, consume_gold, description, web_channel, server_index)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)', $_params);
    }

    function setHeroTrade($_lord_pk, $_posi_pk, $_type, $_hero_pk = null, $_hero_name = null, $_hero_level = null, $_min_value = null, $_max_value = null, $_gold = null, $_commission = null, $_hero_trad_pk = null, $_hero_trad_bid_pk = null, $_sale_period = null, $_password = null, $_description = null, $_sale_lord_pk = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        $query_params = [$_lord_pk, $_posi_pk, $_type, $_hero_pk, $_hero_name, $_hero_level, $_min_value, $_max_value, $_gold, $_commission, $_hero_trad_pk, $_hero_trad_bid_pk, $_sale_period, $_password, $_description, $_sale_lord_pk, $this->web_channel, $this->server_index];
        $this->log_data[] = ['function' => 'setHeroTradeLog', 'param' => $query_params];
    }

    function setHeroTradeLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_hero_trade (log_date, lord_pk, posi_pk, type, hero_pk, hero_name, hero_level, min_value, max_value, gold, commission, hero_trad_pk, hero_trad_bid_pk, sale_period, password, description, sale_lord_pk, web_channel, server_index)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18)', $_params);
    }

    function setItem($_lord_pk, $_posi_pk, $_type, $_acquire_type, $_m_item_pk, $_price = null, $_quantity = null, $_description = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;
        if ($_m_item_pk && !$_price) {
            global $_M, $NsGlobal;
            $NsGlobal->requireMasterData(['item']);
            $_price = $_M['ITEM'][$_m_item_pk]['price'];
        }
        $query_params = [$_lord_pk, $_posi_pk, $_type, $_acquire_type, $_m_item_pk, $_price, $_quantity, $_description, $this->server_index, $this->web_channel];
        $this->log_data[] = ['function' => 'setItemLog', 'param' => $query_params];
    }

    function setItemLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_item (log_date, lord_pk, posi_pk, type, acquire_type, m_item_pk, price, quantity, description, server_index, web_channel)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7, $8, $9, $10)', $_params);
    }

    function setFriendGift($gift_pk, $from_acco_pk, $to_acco_pk, $m_item_pk, $item_cnt, $from_name, $last_up_dt, $type): void
    {
        $query_params = [$gift_pk, $from_acco_pk, $to_acco_pk, $m_item_pk, $item_cnt, $from_name, $last_up_dt, $type];
        $this->log_data[] = ['function' => 'setFriendGiftLog', 'param' => $query_params];
    }

    function setFriendGiftLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_friend_gift (gift_pk, from_acco_pk, to_acco_pk, m_item_pk, item_cnt, from_name, regist_dt, description)
                                        VALUES ($1, $2, $3, $4, $5, $6, $7, $8)', $_params);
    }

    function setPoint($_lord_pk, $_posi_pk, $_type, $_point_posi_pk, $_description = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->getPosiPkToLordPk($_posi_pk) : $_lord_pk;

        $query_params = [$_lord_pk, $_posi_pk, $_type, $this->web_channel, $this->server_index, $_description, $_point_posi_pk];
        $this->log_data[] = ['function' => 'setPointLog', 'param' => $query_params];
    }

    function setPointLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_point(log_date, lord_pk, posi_pk, type, web_channel, server_index, description, point_posi_pk)
                                        VALUES (now(), $1, $2, $3, $4, $5, $6, $7)', $_params);
    }

    function setRaidBattle($_lord_pk, $_posi_pk, $_type, $_description = null): void
    {
        $query_params = [$_lord_pk, $_posi_pk, $_type, $_description, $this->server_index, $this->web_channel];
        $this->log_data[] = ['function' => 'setRaidBattleLog', 'param' => $query_params];
    }

    function setRaidBattleLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_raid_battle (log_date, lord_pk, posi_pk, type, description, server_index, web_channel) values (now(), $1, $2, $3, $4, $5, $6)', $_params);
    }

    function setOccupationPoint($_lord_pk, $_posi_pk, $_type, $_target_posi_pk, $_target_point, $_ranking = 0): void
    {
        $query_params = [$_lord_pk, $_posi_pk, $_type, $_target_posi_pk, $_target_point, $_ranking, $this->web_channel, $this->server_index];
        $this->log_data[] = ['function' => 'setOccupationPointLog', 'param' => $query_params];
    }

    function setOccupationPointLog($_params): void
    {
        $this->PgLog->query('INSERT INTO log_occupation_point (log_date, lord_pk, posi_pk, type, target_posi_pk, target_point, ranking, web_channel, server_index) values (now(), $1, $2, $3, $4, $5, $6, $7, $8)', $_params);
    }
}