<?php

class HeroSkill
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Lord $Lord;
    protected Hero $Hero;
    protected Troop $Troop;
    protected Item $Item;
    protected Report $Report;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    function classLord(): void
    {
        if (! isset($this->Lord)) {
            $this->Lord = new Lord($this->Session, $this->PgGame);
        }
    }

    function classHero(): void
    {
        if (! isset($this->Hero)) {
            $this->Hero = new Hero($this->Session, $this->PgGame);
        }
    }

    function classTroop(): void
    {
        if (! isset($this->Troop)) {
            $this->Troop = new Troop($this->Session, $this->PgGame);
        }
    }

    function classItem(): void
    {
        if (! isset($this->Item)) {
            $this->Item = new Item($this->Session, $this->PgGame);
        }
    }

    function classReport(): void
    {
        if (! isset($this->Report)) {
            $this->Report = new Report($this->Session, $this->PgGame);
        }
    }

    function classLog(): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    function getHeroSkillCount($_lord_pk, $_type = null, $_rare = null): int
    {
        $z1 = (isset($_type) || isset($_rare)) ? ', m_hero_skill b' : '';
        $z2 = (isset($_type) || isset($_rare)) ? 'AND a.m_hero_skil_pk = b.m_hero_skil_pk ' : '';
        $z2 .= (isset($_type)) ? "AND type = '$_type'" : ((isset($_rare)) ? "AND b.rare = $_rare" : '');
        $this->PgGame->query("SELECT count(a.my_hero_skil_pk) FROM my_hero_skill a {$z1} WHERE a.lord_pk = $1 {$z2}", [$_lord_pk]);
        return $this->PgGame->fetchOne();
    }

    function getHeroSkillInfo($_lord_pk, $_open_type, $_tab_type = null, $_order_type = 'ordno', $_order_by = 'desc'): array
    {
        $z3 = '';
        $z2 = '';
        if ($_open_type == 'skill_combination') {
            // $limit = HERO_SKILL_COMBINATION_LIST_NUM;
            if ((INT)$_order_type > 0) {
                $z2 = ' AND b.rare = ' . $_order_type;
            }
        } else {
            // $limit = HERO_SKILL_LIST_NUM;
            if ($_order_type == 'rare') {
                $z3 = 'b.rare ' . $_order_by . ',';
            } else if ($_order_type == 'title'){
                $z3 = 'b.title asc,';
            }
        }
        $z3 .= ' b.ordno ASC';
        // $offset_num = (($_page_num - 1) * $limit);
        if (isset($_tab_type)) {
            $z2 = ' AND type = \'' . $_tab_type . '\'';
        }

        $this->PgGame->query("SELECT a.my_hero_skil_pk, a.m_hero_skil_pk, a.skill_cnt FROM my_hero_skill a, m_hero_skill b
                                        WHERE a.lord_pk = $1 AND a.m_hero_skil_pk = b.m_hero_skil_pk {$z2} ORDER BY {$z3}", [$_lord_pk]);
        $this->PgGame->fetchAll();
        return $this->PgGame->rows;
    }

    function getHeroSkillAll ($_lord_pk = null): array
    {
        $_lord_pk = (! isset($_lord_pk)) ? $this->Session->lord['lord_pk'] : $_lord_pk;
        $this->PgGame->query("SELECT a.my_hero_skil_pk, a.m_hero_skil_pk, a.skill_cnt FROM my_hero_skill a, m_hero_skill b WHERE a.lord_pk = $1 AND a.m_hero_skil_pk = b.m_hero_skil_pk ORDER BY a.m_hero_skil_pk", [$_lord_pk]);
        $cnt = $this->PgGame->fetchAll();
        return ($cnt < 1) ? [] : $this->PgGame->rows;
    }

    function getRandomHeroSkill($_rare, $_skill_type, $_arr_m_skil_pk = null, $yn_lord_skill = 'N'): int|false
    {
        $_skill_type = (! $_skill_type) ? 'H' : $_skill_type;
        $z = '';
        if (is_array($_arr_m_skil_pk) && COUNT($_arr_m_skil_pk) > 0) {
            for ($i = 0; $i < count($_arr_m_skil_pk); $i++) {
                $z .= ' AND m_hero_skil_pk <> ' .  $_arr_m_skil_pk[$i];
            }
        }
        // left_count > 0
        $this->PgGame->query("SELECT m_hero_skil_pk FROM m_hero_skill WHERE rare = $1 $z AND yn_lord_skill = $2 AND skill_type = $3 ORDER BY random() LIMIT 1 FOR UPDATE", [$_rare, $yn_lord_skill, $_skill_type]);
        $m_hero_skil_pk = $this->PgGame->fetchOne();
        if (!$m_hero_skil_pk) {
            return false;
        }
        if (is_array($_arr_m_skil_pk)) {
            if (in_array($m_hero_skil_pk, $_arr_m_skil_pk)) {
                return false;
            }
        }
        return $m_hero_skil_pk;
    }

    function setHeroSkillRare($_type): int|string|null
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_acquired_skill']);

        // 레어 등급 결정
        $range_arr = &$_M['HERO_ACQUIRED_SKILL'][$_type];
        $range_prev = 1;
        $range_select = null;
        $range_random_key = rand(1, 100000); // 10만

        foreach ($range_arr as $k => $v) {
            if ($v['recalc_rate'] == 0) {
                continue;
            }
            $next = $range_prev + $v['recalc_rate'];
            if ($range_random_key >= $range_prev && $range_random_key <= $next) {
                // 결정된 등급
                $range_select = $k;
                break;
            }
            $range_prev = $next;
        }
        return $range_select;
    }

    function setHeroSkillRegist($_lord_pk, $_m_hero_skil_pk, $_type, $_my_hero_skil_box_pk = null): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_skill']);

        // 유효한 스킬 박스 인지 확인
        if ($_my_hero_skil_box_pk) {
            $this->PgGame->query('SELECT * FROM my_hero_skill_box WHERE my_hero_skil_box_pk = $1 AND lord_pk = $2', [$_my_hero_skil_box_pk, $_lord_pk]);
            $this->PgGame->fetch();
            $row = $this->PgGame->row;
            if (!$row || $row['yn_use'] == 'Y') {
                $NsGlobal->setErrorMessage('유효한 박스가 아닙니다.');
                return false;
            }

            $valid_value = false;
            for($i = 1; $i <= 7; $i++) {
                if ($row['m_hero_skil_pk' .$i] > 0) {
                    if ($row['m_hero_skil_pk' .$i] == $_m_hero_skil_pk) {
                        $valid_value = true;
                    }
                }
            }

            if (!$valid_value) {
                $NsGlobal->setErrorMessage('Error Occurred. [19001]'); // 유효한  영웅 기술이  아닙니다.
                return false;
            }

            $this->PgGame->query('UPDATE my_hero_skill_box SET yn_use = $1 WHERE my_hero_skil_box_pk = $2 AND lord_pk = $3', ['Y', $_my_hero_skil_box_pk, $_lord_pk]);

            // 스킬 마스터 데이터 남은 갯수 업데이트
            $this->PgGame->query('SELECT m_hero_skil_pk1, m_hero_skil_pk2, m_hero_skil_pk3, m_hero_skil_pk4, m_hero_skil_pk5, m_hero_skil_pk6, m_hero_skil_pk7 FROM my_hero_skill_box WHERE my_hero_skil_box_pk = $1', [$_my_hero_skil_box_pk]);
            if ($this->PgGame->fetch()) {
                $row = $this->PgGame->row;
                for($i = 1; $i <= 7; $i++) {
                    if ($row['m_hero_skil_pk' .$i] > 0 && $row['m_hero_skil_pk' .$i] != $_m_hero_skil_pk) {
                        $this->PgGame->query('UPDATE m_hero_skill SET left_count = left_count + 1 WHERE m_hero_skil_pk = $1', [$row['m_hero_skil_pk' .$i]]);
                    }
                }
            }
        }

        // 로그에 사용하기 위해 이전개수 알아 오기
        $this->PgGame->query('SELECT skill_cnt FROM my_hero_skill WHERE lord_pk = $1 AND m_hero_skil_pk = $2', [$_lord_pk, $_m_hero_skil_pk]);
        $remain_cnt = $this->PgGame->fetchOne();

        $this->PgGame->query('SELECT COUNT(lord_pk) FROM my_hero_skill WHERE lord_pk = $1 AND m_hero_skil_pk = $2', [$_lord_pk, $_m_hero_skil_pk]);
        $cnt = $this->PgGame->fetchOne();

        $prev_cnt = 0;
        if ($cnt) {
            $r = $this->PgGame->query('UPDATE my_hero_skill set skill_cnt = skill_cnt + 1 WHERE lord_pk = $1 AND m_hero_skil_pk = $2', [$_lord_pk, $_m_hero_skil_pk]);
        } else {
            // 이전 개수
            $this->PgGame->query('SELECT skill_cnt FROM my_hero_skill  WHERE lord_pk = $1 AND m_hero_skil_pk = $2', [$_lord_pk, $_m_hero_skil_pk]);
            $prev_cnt = $this->PgGame->fetchOne();
            $r = $this->PgGame->query('INSERT INTO my_hero_skill (lord_pk, m_hero_skil_pk, skill_cnt) VALUES ($1, $2, $3)', [$_lord_pk, $_m_hero_skil_pk, 1]);
        }

        // LP 입력
        $this->Session->sqAppend('LORD', ['new_skill_update' => $_M['HERO_SKILL'][$_m_hero_skil_pk]['type']], null, $_lord_pk);

        // 로그
        $description = '';
        if ($_my_hero_skil_box_pk) {
            $description .= 'my_hero_skil_box_pk['.$_my_hero_skil_box_pk.'];';
        }
        $remain_cnt = (! $remain_cnt) ? 0 : $remain_cnt;
        $description .= 'remain['.$remain_cnt.' -> '.($remain_cnt+1).'];';

        if ($r) {
            // 스킬 지급 완료 지급 로그
            $this->classLog();
            $this->Log->setHeroSkill($_lord_pk, null, null, 'SkillRegist[' . $_type .']', $_m_hero_skil_pk, null, null, $prev_cnt, $description);

        } else {
            $NsGlobal->setErrorMessage('Error Occurred. [19002]'); // 기술 등록에 실패했습니다. 다시 시도해 주세요.
            return false;
        }
        return true;
    }

    function setHeroSkillBoxListRegist($_lord_pk, $_arr_hero_skill_list, $_m_item_pk): false|int
    {
        $z1 = '';
        $z2 = '';
        for($i = 0; $i < COUNT($_arr_hero_skill_list); $i++) {
            $z1 .= ', m_hero_skil_pk' . ($i + 1);
            $z2 .= ', ' . $_arr_hero_skill_list[$i];
        }
        $r = $this->PgGame->query("INSERT INTO my_hero_skill_box (lord_pk, m_item_pk $z1, yn_use, regist_dt) VALUES ($1, $2 $z2, 'N', now())", [$_lord_pk, $_m_item_pk]);
        if (!$r) {
            return false;
        }
        $my_hero_skil_box_pk = $this->PgGame->currSeq('my_hero_skill_box_my_hero_skil_box_pk_seq');

        // Log
        $this->classLog();
        $this->Log->setHeroSkill($_lord_pk, null, null, 'SkillBoxListRegist', null, $_m_item_pk, null, null, "my_hero_skil_box_pk[$my_hero_skil_box_pk];");

        return $my_hero_skil_box_pk;
    }

    function setHeroSkillExp($_lord_pk, $_posi_pk, $_hero_pk, $_exp, $_type, $_notice = true): mixed
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_skill_exp']);

        if ($_exp <= 0 || !$_hero_pk || preg_match('/^[\d]+$/', $_hero_pk) < 1) {
            return false;
        }

        $curr_posi_pk = $_posi_pk;
        $this->PgGame->query('SELECT posi_pk FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        $real_posi_pk = $this->PgGame->fetchOne();

        $this->PgGame->query('SELECT skill_exp, m_hero_pk, level FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        $prev_exp = $row['skill_exp'];

        if ($prev_exp >= $_M['HERO_SKILL_EXP'][6]['exp']) {
            return false;
        }

        $r = $this->PgGame->query('UPDATE hero SET skill_exp = skill_exp + $2 WHERE hero_pk = $1', [$_hero_pk, $_exp]);
        if ($r) {
            $prev_slot_cnt = $this->getHeroSkillOpenSlotCount($prev_exp);
            $curr_slot_cnt = $this->getHeroSkillOpenSlotCount($prev_exp + $_exp);
            if ($prev_slot_cnt != $curr_slot_cnt) {
                // 슬롯 오픈 보고서
                $this->classReport();
                $this->classTroop();
                $z_content = [];

                // 영웅정보
                $NsGlobal->requireMasterData(['hero', 'hero_base']);

                $m_hero_base_pk = $_M['HERO'][$row['m_hero_pk']]['m_hero_base_pk'];
                $hero_name = $_M['HERO_BASE'][$m_hero_base_pk]['name']. ' (Lv.'. $row['level']. ')';

                $this->classLord();
                // from & to
                if (! $_posi_pk) {
                    $_posi_pk = $real_posi_pk;
                }
                $lord_name = $this->Lord->getLordName($_lord_pk);
                $z_from = [
                    'posi_pk' => $_posi_pk,
                    'lord_name' => $lord_name,
                    'posi_name' => $this->Troop->getPositionName($_posi_pk),
                ];
                $z_to = [
                    'posi_pk' => $curr_posi_pk,
                    'lord_name' => $lord_name,
                    'posi_name' => $hero_name
                ];

                $z_content['prev_slot_cnt'] = $prev_slot_cnt;
                $z_content['curr_slot_cnt'] = $curr_slot_cnt;
                $z_content['m_hero_pk'] = $row['m_hero_pk'];

                // title & summary
                $z_summary = $hero_name;

                $this->Report->setReport($_lord_pk, 'misc', 'hero_skill_slot_expand', $z_from, $z_to, '', $z_summary, json_encode($z_content));
            }

            // 경험치 증가 로그(이전 경험치, 증가치)
            $this->classLog();
            $this->Log->setHeroSkillExp($_lord_pk, $_posi_pk, $_hero_pk, $_type, $prev_exp, $_exp, $row['m_hero_pk']);

            if ($_notice && $curr_posi_pk == $real_posi_pk) {
                $this->classHero();
                $hero_status = [$_hero_pk => $this->Hero->getMyHeroInfo($_hero_pk)];
                $this->Session->sqAppend('HERO', $hero_status, null, $_lord_pk, $_posi_pk);
            }
        } else {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'skill exp update failed;hero_pk[' . $_hero_pk . '];exp['.$_exp.'];');
            return false;
        }

        return $real_posi_pk;
    }

    function updateAssignSkillExp($_lord_pk): true
    {
        $this->PgGame->query('SELECT hero_pk, posi_pk, date_part(\'epoch\', last_exp_update_dt)::integer as update_dt FROM my_hero WHERE lord_pk = $1 AND status = $2 AND status_cmd = $2', [$_lord_pk, 'A']);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        foreach($rows AS $v) {
            if ($v['update_dt']) {
                $assign_hour = (INT)((time() - $v['update_dt']) / HERO_SKILL_ACQUIRE_TIME);
                $exp = $assign_hour * HERO_SKILL_ACQUIRE_ASSIGN;
                if ($exp > 0) {
                    $r = $this->setHeroSkillExp($_lord_pk, $v['posi_pk'], $v['hero_pk'], $exp, 'Assign');
                    if ($r) {
                        $this->PgGame->query('UPDATE my_hero SET last_exp_update_dt = last_exp_update_dt + interval \'' . $assign_hour . ' hours\' WHERE  hero_pk = $1', [$v['hero_pk']]);
                    }
                }
            } else {
                $this->PgGame->query('UPDATE my_hero SET last_exp_update_dt = now() WHERE  hero_pk = $1', [$v['hero_pk']]);
            }
        }
        return true;
    }

    function updateHeroBattleSkillExp($_lord_pk, $_posi_pk, $_arr_hero_pk): void
    {
        for($i = 0; $i < COUNT($_arr_hero_pk); $i++) {
            $this->setHeroSkillExp($_lord_pk, $_posi_pk, $_arr_hero_pk[$i]['hero_pk'], HERO_SKILL_ACQUIRE_HERO_BATTLE, 'HeroBattle');
        }
    }

    function updateCampSkillExp($_lord_pk): true
    {
        $this->PgGame->query('SELECT hero_pk, posi_pk, date_part(\'epoch\', last_exp_update_dt)::integer as update_dt FROM my_hero WHERE lord_pk = $1 AND status = $2 AND status_cmd = $3 AND cmd_type = $4', [$_lord_pk, 'A', 'C', 'Camp']);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        foreach($rows AS $v) {
            $assign_hour = (INT)((time() - $v['update_dt']) / HERO_SKILL_ACQUIRE_TIME);
            $exp = $assign_hour * HERO_SKILL_ACQUIRE_ASSIGN;
            if ($exp > 0) {
                $r = $this->setHeroSkillExp($_lord_pk, $v['posi_pk'], $v['hero_pk'], $exp, 'Camp');
                if ($r) {
                    $this->PgGame->query('UPDATE my_hero SET last_exp_update_dt = last_exp_update_dt + interval \'' . $assign_hour . 'hours\' WHERE  hero_pk = $1', [$v['hero_pk']]);
                }
            }
        }
        return true;
    }

    function updateCmdCompleteSkillExp($_lord_pk, $_posi_pk, $_hero_pk, $_cmd_type, $_buil_time): void
    {
        if ($_cmd_type == 'Const') {
            $this->updateConstructionCompleteSkillExp($_lord_pk, $_posi_pk, $_hero_pk, $_buil_time);
        } else if ($_cmd_type == 'Techn') {
            $this->updateTechniqueCompleteSkillExp($_lord_pk, $_posi_pk, $_hero_pk, $_buil_time);
        } else if ($_cmd_type == 'Encou') {
            $this->updateEncounterCompleteSkillExp($_lord_pk, $_posi_pk, $_hero_pk, $_buil_time);
        }
    }

    function updateTechniqueCompleteSkillExp($_lord_pk, $_posi_pk, $_hero_pk, $_buil_time): void
    {
        $exp = (INT)($_buil_time / HERO_SKILL_ACQUIRE_TIME) * HERO_SKILL_ACQUIRE_TECHNIQUE_COMPLETE;
        $this->setHeroSkillExp($_lord_pk, $_posi_pk, $_hero_pk, $exp, 'TechniqueComplete', false);
    }

    function updateConstructionCompleteSkillExp($_lord_pk, $_posi_pk, $_hero_pk, $_buil_time): void
    {
        $exp = (INT)($_buil_time / HERO_SKILL_ACQUIRE_TIME) * HERO_SKILL_ACQUIRE_CONSTRUCTION_COMPLETE;
        // Debug::debugLogging("$_lord_pk, $_posi_pk, $_hero_pk, $exp");
        $this->setHeroSkillExp($_lord_pk, $_posi_pk, $_hero_pk, $exp, 'ConstructionComplete', false);
    }

    function updateEncounterCompleteSkillExp($_lord_pk, $_posi_pk, $_hero_pk, $_buil_time): void
    {
        $exp = (INT)($_buil_time / HERO_SKILL_ACQUIRE_TIME) * HERO_SKILL_ACQUIRE_ENCOUNTER_COMPLETE;
        $this->setHeroSkillExp($_lord_pk, $_posi_pk, $_hero_pk, $exp, 'EncounterComplete', false);
    }

    function updateBattleSkillExp($_lord_pk, $_posi_pk, $army_point, $_hero_pk): float|int
    {
        if ($_lord_pk == NPC_TROOP_LORD_PK) {
            return 0;
        }
        $exp = (INT)($army_point / HERO_SKILL_ACQUIRE_BATTLE_POPULATION) * HERO_SKILL_ACQUIRE_BATTLE;
        if ($exp > 0) {
            $exp = min($exp, HERO_SKILL_BATTLE_MAX);
            for($i = 0; $i < COUNT($_hero_pk); $i++) {
                if ($_hero_pk[$i] > 0) {
                    $this->setHeroSkillExp($_lord_pk, $_posi_pk, $_hero_pk[$i], $exp, 'Battle');
                }
            }
        }
        return $exp;
    }

    function updateSalarySkillExp($_lord_pk): void
    {
        $this->PgGame->query('SELECT hero_pk, posi_pk FROM my_hero WHERE lord_pk = $1 AND status = $2', [$_lord_pk, 'A']);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        foreach($rows AS $v) {
            $this->setHeroSkillExp($_lord_pk, $v['posi_pk'], $v['hero_pk'], HERO_SKILL_ACQUIRE_SALARY, 'Salary');
        }
    }

    function getMyHeroSkillBoxList($_lord_pk): false|array
    {
        $this->PgGame->query('SELECT * FROM my_hero_skill_box WHERE lord_pk = $1 AND yn_use = $2 LIMIT 1', [$_lord_pk, 'N']);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $skill_list = [];
        for ($i = 0; $i < 7; $i++) {
            $skill_list[$i] = $this->PgGame->row['m_hero_skil_pk' . ($i + 1)];
        }
        return ['my_hero_skil_box_pk' => $this->PgGame->row['my_hero_skil_box_pk'], 'm_item_pk' => $this->PgGame->row['m_item_pk'], 'skill_list' => $skill_list];
    }

    function setHeroSkillEquip($_lord_pk, $_hero_pk, $_my_hero_skil_pk, $_curr_posi_pk = null)
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['hero_skill']);
        // 등용, 대기 상태 확인
        $this->PgGame->query('SELECT status, status_cmd FROM my_hero WHERE hero_pk = $1 AND lord_pk = $2', [$_hero_pk, $_lord_pk]);
        $this->PgGame->fetch();
        if ($this->PgGame->row['status'] != 'A' || $this->PgGame->row['status_cmd'] != 'I') {
            $NsGlobal->setErrorMessage($i18n->t('hero_not_equip_skill_status')); // 해당 영웅은 기술을 장착 할 수 없는 상태 입니다.
            return false;
        }

        // 유효한 스킬인지 검사
        $ret = $this->getMyHeroSkill($_my_hero_skil_pk, $_lord_pk);
        if (!$ret || $ret['skill_cnt'] < 1) {
            $NsGlobal->setErrorMessage('Error Occurred. [19004]'); // 소지하지 않은 영웅 기술 입니다.
            return false;
        }

        $m = $_M['HERO_SKILL'][$ret['m_hero_skil_pk']];

        // 슬롯에 스킬 장착 가능한지 확인
        $this->classHero();
        $hero_info = $this->Hero->getMyHeroInfo($_hero_pk);
        $open_slot_cnt = $this->getHeroSkillOpenSlotCount($hero_info['skill_exp']);
        $need_slot_cnt = $m['use_slot_count'];
        if ($open_slot_cnt < $need_slot_cnt) {
            $NsGlobal->setErrorMessage($i18n->t('msg_hero_skill_need_slot')); // 필요 슬롯이 부족합니다.
            return false;
        }

        // 레어도가 8인 기술은 명장 기술 이므로
        if ($m['rare'] > 7) {
            $rare_type = $this->Hero->getHeroRare($_hero_pk);
            // 장착 가능한 레어도인지 확인
            if ($rare_type < 6) {
                $NsGlobal->setErrorMessage($i18n->t('msg_hero_skill_need_slot_rare')); // 장착 가능한 레어도가 아닙니다.
                return false;
            }
        }

        // 필요한 만큰 연속된 슬롯이 있는지 확인
        $possible_cnt = 0;
        $possible_equip = false;
        $equip_slot = [];
        for ($i = 1; $i <= $open_slot_cnt; $i++) {
            if (!$hero_info['m_hero_skil_pk' . $i]) {
                $equip_slot[$possible_cnt] = $i;
                $possible_cnt++;
                if ($need_slot_cnt == $possible_cnt) {
                    $possible_equip = true;
                    $i = $open_slot_cnt + 1;
                }
            } else {
                $possible_cnt = 0;
            }
        }

        if (!$possible_equip) {
            $NsGlobal->setErrorMessage($i18n->t('msg_hero_skill_consecutive_slot')); // 필요 슬롯 개수 만큼 연속된 장착 가능 슬롯이 필요합니다.
            return false;
        }

        // 전투 스킬 이미 장착한것 있는지 검사
        if ($_M['HERO_SKILL'][$ret['m_hero_skil_pk']]['type'] == 'B') {
            $this->PgGame->query('SELECT COUNT(a.hero_pk) FROM my_hero_skill_slot a, m_hero_skill b WHERE a.hero_pk = $1 AND a.m_hero_skil_pk = b.m_hero_skil_pk AND type = $2', [$_hero_pk, 'B']);
            if ($this->PgGame->fetchOne() > 0) {
                $NsGlobal->setErrorMessage($i18n->t('msg_hero_skill_already_equip')); // 이미 전투 기술을 장착하였습니다.<br /><br />전투 기술은 한 개만 장착 가능합니다.
                return false;
            }
        }

        // 군주 스킬 장착 했는지 검사
        if ($_M['HERO_SKILL'][$ret['m_hero_skil_pk']]['yn_lord_skill'] == 'Y') {
            if ($this->getEquipLordHeroSkill($_hero_pk)) {
                $NsGlobal->setErrorMessage($i18n->t('msg_lord_skill_already_equip')); // 이미 군주 기술을 장착하였습니다.<br /><br />군주 기술은 한 개만 장착 가능합니다.
                return false;
            }
        }

        for ($i = $equip_slot[0]; $i < $equip_slot[0] + COUNT($equip_slot); $i++) {
            $this->PgGame->query('INSERT INTO my_hero_skill_slot (hero_pk, slot_pk, m_hero_skil_pk, main_slot_pk) VALUES ($1, $2, $3, $4)', [$_hero_pk, $i, $ret['m_hero_skil_pk'], $equip_slot[0]]);
        }

        $skill_cnt = 0;
        if ($ret['skill_cnt'] > 1) {
            $this->PgGame->query('UPDATE my_hero_skill set skill_cnt = skill_cnt - 1 WHERE my_hero_skil_pk = $1', [$_my_hero_skil_pk]);
            $skill_cnt = (INT)$ret['skill_cnt'] - 1;
        } else {
            $this->PgGame->query('DELETE FROM my_hero_skill WHERE my_hero_skil_pk = $1', [$_my_hero_skil_pk]);
        }

        // 스탯 변경이 있을 경우
        if ($_M['HERO_SKILL'][$ret['m_hero_skil_pk']]['type'] == 'D') {
            $this->setHeroSkillPlusStat($_hero_pk, $ret['m_hero_skil_pk']);
        }

        $hero_status = [$_hero_pk => $this->Hero->getMyHeroInfo($_hero_pk)];
        if ($_curr_posi_pk) {
            $this->Session->sqAppend('HERO', $hero_status, null, $_lord_pk, $_curr_posi_pk);
        }

        $this->PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        $m_hero_pk = $this->PgGame->fetchOne();

        // Log
        $description = 'remain['.$ret['skill_cnt'].' -> '.$skill_cnt.'];';
        $this->classLog();
        $this->Log->setHeroSkill($_lord_pk, $_curr_posi_pk, $_hero_pk, 'SkillEquip', $ret['m_hero_skil_pk'], null, $equip_slot[0], $ret['skill_cnt'], $description, $m_hero_pk);

        return $hero_status[$_hero_pk];
    }

    function setHeroSkillPlusStat($_hero_pk, $_m_hero_skil_pk, $_lord_pk = null): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_skill']);

        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }

        $this->PgGame->query('SELECT leadership_skill, mil_force_skill, intellect_skill, politics_skill, charm_skill FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;

        $this->PgGame->query('UPDATE hero SET leadership_skill = leadership_skill + $2, mil_force_skill = mil_force_skill + $3, intellect_skill = intellect_skill + $4, politics_skill = politics_skill + $5, charm_skill = charm_skill + $6 WHERE hero_pk = $1',
            [$_hero_pk, $_M['HERO_SKILL'][$_m_hero_skil_pk]['leadership'], $_M['HERO_SKILL'][$_m_hero_skil_pk]['mil_force'], $_M['HERO_SKILL'][$_m_hero_skil_pk]['intellect'], $_M['HERO_SKILL'][$_m_hero_skil_pk]['politics'], $_M['HERO_SKILL'][$_m_hero_skil_pk]['charm']]);

        $this->PgGame->query('SELECT m_offi_pk FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        $m_off_pk = $this->PgGame->fetchOne();

        $this->classHero();
        $this->Hero->setNewStat($_hero_pk, $m_off_pk);

        $this->PgGame->query('SELECT leadership_skill, mil_force_skill, intellect_skill, politics_skill, charm_skill, m_hero_pk FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        $row1 = $this->PgGame->row;

        $description = 'prev_stat['.$row['leadership_skill'].':'.$row['mil_force_skill'].':'.$row['intellect_skill'].':'.$row['politics_skill'].':'.$row['charm_skill'].'];';
        $description .= 'plus_stat['.$_M['HERO_SKILL'][$_m_hero_skil_pk]['leadership'].':'.$_M['HERO_SKILL'][$_m_hero_skil_pk]['mil_force'].':'.$_M['HERO_SKILL'][$_m_hero_skil_pk]['intellect'].':'.$_M['HERO_SKILL'][$_m_hero_skil_pk]['politics'].':'.$_M['HERO_SKILL'][$_m_hero_skil_pk]['charm'].'];';
        $description .= 'after_stat['.$row1['leadership_skill'].':'.$row1['mil_force_skill'].':'.$row1['intellect_skill'].':'.$row1['politics_skill'].':'.$row1['charm_skill'].'];';

        $m_hero_pk = $row1['m_hero_pk'];

        // Log
        $this->classLog();
        $this->Log->setHeroSkill($_lord_pk, null, $_hero_pk, 'SkillPlusStat', $_m_hero_skil_pk, null, null, null, $description, $m_hero_pk);
    }

    function setHeroSkillMinusStat($_hero_pk, $_m_hero_skil_pk, $_lord_pk = null): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_skill']);

        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }

        $this->PgGame->query('SELECT leadership_skill, mil_force_skill, intellect_skill, politics_skill, charm_skill FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;

        $this->PgGame->query('UPDATE hero SET leadership_skill = leadership_skill - $2, mil_force_skill = mil_force_skill - $3, intellect_skill = intellect_skill - $4, politics_skill = politics_skill - $5, charm_skill = charm_skill - $6 WHERE hero_pk = $1',
            [$_hero_pk, $_M['HERO_SKILL'][$_m_hero_skil_pk]['leadership'], $_M['HERO_SKILL'][$_m_hero_skil_pk]['mil_force'], $_M['HERO_SKILL'][$_m_hero_skil_pk]['intellect'], $_M['HERO_SKILL'][$_m_hero_skil_pk]['politics'], $_M['HERO_SKILL'][$_m_hero_skil_pk]['charm']]);

        $this->PgGame->query('SELECT m_offi_pk FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        $m_off_pk = $this->PgGame->fetchOne();

        $this->classHero();
        $this->Hero->setNewStat($_hero_pk, $m_off_pk);

        $this->PgGame->query('SELECT leadership_skill, mil_force_skill, intellect_skill, politics_skill, charm_skill, m_hero_pk FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        $row1 = $this->PgGame->row;

        $description = 'prev_stat['.$row['leadership_skill'].':'.$row['mil_force_skill'].':'.$row['intellect_skill'].':'.$row['politics_skill'].':'.$row['charm_skill'].'];';
        $description .= 'minus_stat['.$_M['HERO_SKILL'][$_m_hero_skil_pk]['leadership'].':'.$_M['HERO_SKILL'][$_m_hero_skil_pk]['mil_force'].':'.$_M['HERO_SKILL'][$_m_hero_skil_pk]['intellect'].':'.$_M['HERO_SKILL'][$_m_hero_skil_pk]['politics'].':'.$_M['HERO_SKILL'][$_m_hero_skil_pk]['charm'].'];';
        $description .= 'after_stat['.$row1['leadership_skill'].':'.$row1['mil_force_skill'].':'.$row1['intellect_skill'].':'.$row1['politics_skill'].':'.$row1['charm_skill'].'];';

        $m_hero_pk = $row1['m_hero_pk'];

        // Log
        $this->classLog();
        $this->Log->setHeroSkill($_lord_pk, null, $_hero_pk, 'SkillMinusStat', $_m_hero_skil_pk, null, null, null, $description, $m_hero_pk);
    }

    function getMyHeroSkill($_my_hero_skil_pk, $_lord_pk): bool|array
    {
        $this->PgGame->query('SELECT m_hero_skil_pk, skill_cnt FROM my_hero_skill WHERE my_hero_skil_pk = $1 AND lord_pk = $2', [$_my_hero_skil_pk, $_lord_pk]);
        $this->PgGame->fetch();
        return $this->PgGame->row;
    }

    function getHeroSkillOpenSlotCount($_skill_exp): int
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_skill_exp']);

        $open_slot_cnt = 0;
        foreach($_M['HERO_SKILL_EXP'] AS $v) {
            if ($_skill_exp < $v['exp']) {
                return $open_slot_cnt;
            }
            $open_slot_cnt++;
        }
        return $open_slot_cnt;
    }

    function setDeleteEquipSkill($_lord_pk, $_posi_pk, $_hero_pk, $_slot_pk): array|false
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['hero_skill']);

        // 등용, 대기 상태 확인
        $this->PgGame->query('SELECT status, status_cmd FROM my_hero WHERE hero_pk = $1 AND lord_pk = $2', [$_hero_pk, $_lord_pk]);
        $this->PgGame->fetch();
        if ($this->PgGame->row['status'] != 'A' || $this->PgGame->row['status_cmd'] != 'I') {
            $NsGlobal->setErrorMessage($i18n->t('msg_skill_remove_idle_hero')); // 해당 영웅은 기술을 삭제 할 수 없는 상태 입니다.<br /><br />등용 중 대기인 상태에서만 삭제가 가능합니다. Error Occurred. [19005]
            return false;
        }

        // 삭제 가능한지 검사.
        $this->PgGame->query('SELECT main_slot_pk, m_hero_skil_pk FROM my_hero_skill_slot WHERE hero_pk = $1 AND slot_pk = $2', [$_hero_pk, $_slot_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        $main_slot_pk = $row['main_slot_pk'];
        if (!$main_slot_pk) {
            $NsGlobal->setErrorMessage('Error Occurred. [19006]'); // 삭제할 기술이 존재하지 않습니다.
            return false;
        }

        // 스킬 삭제
        $this->PgGame->query('DELETE FROM my_hero_skill_slot WHERE hero_pk = $1 AND main_slot_pk = $2', [$_hero_pk, $main_slot_pk]);


        // 스탯 변경이 있을 경우
        if ($_M['HERO_SKILL'][$row['m_hero_skil_pk']]['type'] == 'D') {
            $this->setHeroSkillMinusStat($_hero_pk, $row['m_hero_skil_pk']);
        }

        $this->classHero();
        $hero_status = [$_hero_pk => $this->Hero->getMyHeroInfo($_hero_pk)];
        if ($_posi_pk) {
            $this->Session->sqAppend('HERO', $hero_status, null, $_lord_pk, $_posi_pk);
        }

        $this->PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        $m_hero_pk = $this->PgGame->fetchOne();

        // Log
        $this->classLog();
        $this->Log->setHeroSkill($_lord_pk, $_posi_pk, $_hero_pk, 'DeleteEquipSkill', $row['m_hero_skil_pk'], null, $_slot_pk, null, null, $m_hero_pk);

        return $hero_status[$_hero_pk];
    }

    function setUnEquipSkill($_lord_pk, $_posi_pk, $_hero_pk, $_slot_pk): array|false
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['hero_skill']);

        // 등용, 대기 상태 확인
        $this->PgGame->query('SELECT status, status_cmd FROM my_hero WHERE hero_pk = $1 AND lord_pk = $2', [$_hero_pk, $_lord_pk]);
        $this->PgGame->fetch();
        if ($this->PgGame->row['status'] != 'A' || $this->PgGame->row['status_cmd'] != 'I') {
            $NsGlobal->setErrorMessage($i18n->t('msg_skill_unequip_idle_hero')); // 해당 영웅은 기술을 해제 할 수 없는 상태 입니다.<br /><br />등용 대기 중 대기인 상태에서만 해제가 가능합니다. Error Occurred. [19007]
            return false;
        }

        $this->PgGame->query('SELECT main_slot_pk, m_hero_skil_pk FROM my_hero_skill_slot WHERE hero_pk = $1 AND slot_pk = $2', [$_hero_pk, $_slot_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        if (!$row) {
            $NsGlobal->setErrorMessage('Error Occurred. [19008]'); // 해제할 기술이 존재하지 않습니다.
            return false;
        }

        try {
            $this->PgGame->begin();
            global $_NS_SQ_REFRESH_FLAG, $i18n;
            $_NS_SQ_REFRESH_FLAG = true;

            // 아이템 사용
            $this->classItem();
            $ret = $this->Item->useItem($_posi_pk, $_lord_pk, HERO_SKILL_UNEQUIP, 1, ['_use_type' => 'UnequipSkill', '_yn_quest' => true]);
            if(!$ret) {
                $NsGlobal->setErrorMessage($i18n->t('msg_need_item', [$i18n->t('item_title_' . HERO_SKILL_UNEQUIP)]));
                throw new Exception();
            }

            // 스킬 장착 해제
            $this->PgGame->query('DELETE FROM my_hero_skill_slot WHERE hero_pk = $1 AND main_slot_pk = $2', [$_hero_pk, $row['main_slot_pk']]);

            // 스탯 변경이 있을 경우
            if ($_M['HERO_SKILL'][$row['m_hero_skil_pk']]['type'] == 'D') {
                $this->setHeroSkillMinusStat($_hero_pk, $row['m_hero_skil_pk']);
            }

            $this->classHero();
            $hero_status = [$_hero_pk => $this->Hero->getMyHeroInfo($_hero_pk)];
            if ($_posi_pk) {
                $this->Session->sqAppend('HERO', $hero_status, null, $_lord_pk, $_posi_pk);
            }

            // 스킬 리스트에 추가
            $r = $this->setHeroSkillRegist($_lord_pk, $row['m_hero_skil_pk'], 'unequip');
            if (!$r) {
                $NsGlobal->setErrorMessage('Error Occurred. [19009]'); // 기술 등록에 실패했습니다.
                throw new Exception();
            }
            $this->PgGame->commit();
        } catch (Exception $e) {
            // 실패, sq 무시
            $this->PgGame->rollback();

            //dubug_mesg남기기
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';lord_pk['.$_lord_pk.'];hero_pk['.$_hero_pk.'];');

            return false;
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        // Log
        $this->PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        $m_hero_pk = $this->PgGame->fetchOne();
        $this->classLog();
        $this->Log->setHeroSkill($_lord_pk, $_posi_pk, $_hero_pk, 'UnequipSkill', $row['m_hero_skil_pk'], HERO_SKILL_UNEQUIP, $_slot_pk, null, null, $m_hero_pk);

        return $hero_status[$_hero_pk];
    }

    function getBattleHeroSkill($_battle_skil_arr, $_exercise_type = 'P'): mixed
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_skill', 'hero_skill_cmd_rate']);

        $m_hero_skil_pk_arr = [];
        $i = 0;

        if (is_array($_battle_skil_arr)) {
            foreach($_battle_skil_arr AS $v) {
                if (isset($_M['HERO_SKILL'][$v['m_hero_skil_pk']]) && $_M['HERO_SKILL'][$v['m_hero_skil_pk']]['exercise_type'] == $_exercise_type) {
                    // 랜덤값
                    $rand_value = rand(1, 100000);

                    // 추가 발동 확률
                    $this->PgGame->query('SELECT COUNT(hero_pk) FROM my_hero WHERE hero_pk = $1', [$v['hero_pk']]);
                    if ($this->PgGame->fetchOne()) {
                        $this->PgGame->query('SELECT ' . $_M['HERO_SKILL'][$v['m_hero_skil_pk']]['stat_type'] . ' FROM my_hero WHERE hero_pk = $1', [$v['hero_pk']]);
                        $status = $this->PgGame->fetchOne();
                        if ($status) {
                            $stat_step = floor($status / 10);
                            $rand_value -= $_M['HERO_SKILL_CMD_RATE'][$_M['HERO_SKILL'][$v['m_hero_skil_pk']]['m_hero_skil_cmd_rate_pk']][$stat_step];
                        }
                    }

                    if ($_M['HERO_SKILL'][$v['m_hero_skil_pk']]['exericised_rate'] >= $rand_value) {
                        $m_hero_skil_pk_arr[$i]['m_pk'] = $v['m_hero_skil_pk'];
                        $m_hero_skil_pk_arr[$i]['hero_pk'] = $v['hero_pk'];
                        $i++;
                    }
                }
            }
        }
        shuffle($m_hero_skil_pk_arr);
        return (COUNT($m_hero_skil_pk_arr) > 0) ? $m_hero_skil_pk_arr[0] : null;
    }

    function setBattleAttackSkill($_m_hero_skil_pk, $_spec_attack): float
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_skill']);
        return $_spec_attack * $_M['HERO_SKILL'][$_m_hero_skil_pk]['effect_value'] * 0.01;
    }

    function setBattleBeforeSkill($_m_hero_skil_pk, $_att_army_data, $_def_army_data, $_wall_open = true): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_skill']);
        $effect_type = $_M['HERO_SKILL'][$_m_hero_skil_pk]['effect_type'];
        if ($effect_type == 'battle_army_decrease') {
            // 적부대 모든 병과 피해
            foreach($_def_army_data AS $k => $v) {
                if ($_wall_open || $k == 'trap' || $k === 'abatis' || $k == 'tower' || $k == 'wall') {
                    $dead_army = $this->setBattleAttackSkill($_m_hero_skil_pk, $v['unit_remain']);
                    $_def_army_data[$k]['unit_remain'] = $v['unit_remain'] - floor($dead_army);
                }
            }
        } else {
            foreach($_att_army_data AS $k => $v) {
                if ($k != 'trap' && $k != 'abatis' && $k != 'tower' && $k != 'wall') {
                    if ($effect_type == 'battle_attack_defance_increase') { // 공격력, 방어력 증가
                        $_att_army_data[$k]['attack'] += $this->setBattleAttackSkill($_m_hero_skil_pk, $v['attack']);
                        $_att_army_data[$k]['defence'] += $this->setBattleAttackSkill($_m_hero_skil_pk, $v['defence']);
                    } else if ($effect_type == 'battle_defence_increase') { // 방어력 증가
                        $_att_army_data[$k]['defence'] += $this->setBattleAttackSkill($_m_hero_skil_pk, $v['defence']);
                    } else if ($effect_type == 'battle_attack_increase') { // 공격력 증가
                        $_att_army_data[$k]['attack'] += $this->setBattleAttackSkill($_m_hero_skil_pk, $v['attack']);
                    }
                }
            }
        }
        return [$_att_army_data, $_def_army_data];
    }

    function setNPCHeroBattleSkill($_posi_info, $dst_troop): false|array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_skill']);

        $_arr_battle_skill = [];
        $_arr_captain_battle_skill = [];
        $_arr_director_battle_skill = [];
        $_arr_staff_battle_skill = [];
        $captain_cnt = 0;
        $director_cnt = 0;
        $staff_cnt = 0;

        if ($_posi_info['type'] == 'P') {
            $this->PgGame->query('SELECT c.rare_type FROM hero a, m_hero b, m_hero_base c WHERE a.hero_pk =$1 AND a.m_hero_pk = b.m_hero_pk AND b.m_hero_base_pk = c.m_hero_base_pk', [$dst_troop['captain_hero_pk']]);
            $captain_rare_type = $this->PgGame->fetchOne();

            $this->PgGame->query('SELECT c.rare_type FROM hero a, m_hero b, m_hero_base c WHERE a.hero_pk =$1 AND a.m_hero_pk = b.m_hero_pk AND b.m_hero_base_pk = c.m_hero_base_pk', [$dst_troop['director_hero_pk']]);
            $director_rare_type = $this->PgGame->fetchOne();

            $this->PgGame->query('SELECT c.rare_type FROM hero a, m_hero b, m_hero_base c WHERE a.hero_pk =$1 AND a.m_hero_pk = b.m_hero_pk AND b.m_hero_base_pk = c.m_hero_base_pk', [$dst_troop['staff_hero_pk']]);
            $staff_rare_type = $this->PgGame->fetchOne();

            foreach($_M['HERO_SKILL'] AS $k => $v) {
                if ($v['type'] == 'B') {
                    if ($captain_rare_type == $v['rare'] && $v['m_cmd_pk'] == PK_CMD_TROOP_CAPTAIN) {
                        $_arr_captain_battle_skill[$captain_cnt] = $k;
                        $captain_cnt++;
                    }
                    if ($director_rare_type == $v['rare'] && $v['m_cmd_pk'] == PK_CMD_TROOP_DIRECTOR) {
                        $_arr_director_battle_skill[$director_cnt] = $k;
                        $director_cnt++;
                    }
                    if ($staff_rare_type == $v['rare'] && $v['m_cmd_pk'] == PK_CMD_TROOP_STAFF) {
                        $_arr_staff_battle_skill[$staff_cnt] = $k;
                        $staff_cnt++;
                    }
                }
            }
        } else {
            if ($_posi_info['level'] < 5) {
                return false;
            }

            if (isset($dst_troop['captain_hero_pk'])) {
                $this->PgGame->query('SELECT c.rare_type FROM hero a, m_hero b, m_hero_base c WHERE a.hero_pk =$1 AND a.m_hero_pk = b.m_hero_pk AND b.m_hero_base_pk = c.m_hero_base_pk', [$dst_troop['captain_hero_pk']]);
                $captain_rare_type = $this->PgGame->fetchOne();
                foreach($_M['HERO_SKILL'] AS $k => $v) {
                    if ($v['type'] == 'B' && $v['m_cmd_pk'] == PK_CMD_TROOP_CAPTAIN && $v['rare'] == $captain_rare_type) {
                        $_arr_captain_battle_skill[$captain_cnt] = $k;
                        $captain_cnt++;
                    }
                }
            }

            if (isset($dst_troop['director_hero_pk'])) {
                $this->PgGame->query('SELECT c.rare_type FROM hero a, m_hero b, m_hero_base c WHERE a.hero_pk =$1 AND a.m_hero_pk = b.m_hero_pk AND b.m_hero_base_pk = c.m_hero_base_pk', [$dst_troop['director_hero_pk']]);
                $director_rare_type = $this->PgGame->fetchOne();
                foreach($_M['HERO_SKILL'] AS $k => $v) {
                    if ($v['type'] == 'B' && $v['m_cmd_pk'] == PK_CMD_TROOP_DIRECTOR && $v['rare'] == $director_rare_type) {
                        $_arr_director_battle_skill[$director_cnt] = $k;
                        $director_cnt++;
                    }
                }
            }

            if (isset($dst_troop['staff_hero_pk'])) {
                $this->PgGame->query('SELECT c.rare_type FROM hero a, m_hero b, m_hero_base c WHERE a.hero_pk =$1 AND a.m_hero_pk = b.m_hero_pk AND b.m_hero_base_pk = c.m_hero_base_pk', [$dst_troop['staff_hero_pk']]);
                $staff_rare_type = $this->PgGame->fetchOne();
                foreach($_M['HERO_SKILL'] AS $k => $v) {
                    if ($v['type'] == 'B' && $v['m_cmd_pk'] == PK_CMD_TROOP_STAFF && $v['rare'] == $staff_rare_type) {
                        $m_hero_skil_pk = substr($v['m_hero_skil_pk'], 0, 4);
                        if ($_posi_info['level'] >= 5 && ($m_hero_skil_pk == 1574 || $m_hero_skil_pk == 1577)) {
                            $_arr_staff_battle_skill[$staff_cnt] = $k;
                        } else if ($_posi_info['level'] >= 6 && $m_hero_skil_pk == 1575) {
                            $_arr_staff_battle_skill[$staff_cnt] = $k;
                        } else if ($_posi_info['level'] >= 7 && $m_hero_skil_pk == 1576) {
                            $_arr_staff_battle_skill[$staff_cnt] = $k;
                        } else if ($_posi_info['level'] >= 8 && $m_hero_skil_pk == 1578) {
                            $_arr_staff_battle_skill[$staff_cnt] = $k;
                        } else if ($_posi_info['level'] > 8 && $m_hero_skil_pk == 1573) {
                            $_arr_staff_battle_skill[$staff_cnt] = $k;
                        }


                        /* TODO 이건 원래 코드. 오류 손봐야함. 테스트 바람.
                         * if ($_arr_battle_skill[$i]['m_hero_skil_pk']) {
                            $_arr_staff_battle_skill[$staff_cnt] = $k;
                        }*/
                        // TODO 이게 맞나;;
                        if (isset($_arr_staff_battle_skill[$staff_cnt]['m_hero_skil_pk'])) {
                            $_arr_staff_battle_skill[$staff_cnt] = $k;
                        }
                        $staff_cnt++;
                    }
                }
            }
        }

        if (count($_arr_captain_battle_skill) > 0) {
            shuffle($_arr_captain_battle_skill);
            $_arr_battle_skill[0]['hero_pk'] = $dst_troop['captain_hero_pk'];
            $_arr_battle_skill[0]['m_hero_skil_pk'] = $_arr_captain_battle_skill[0];
        }

        if (count($_arr_director_battle_skill) > 0) {
            shuffle($_arr_director_battle_skill);
            $_arr_battle_skill[1]['hero_pk'] = $dst_troop['director_hero_pk'];
            $_arr_battle_skill[1]['m_hero_skil_pk'] = $_arr_director_battle_skill[0];
        }

        if (count($_arr_staff_battle_skill) > 0) {
            shuffle($_arr_staff_battle_skill);
            $_arr_battle_skill[2]['hero_pk'] = $dst_troop['staff_hero_pk'];
            $_arr_battle_skill[2]['m_hero_skil_pk'] = $_arr_staff_battle_skill[0];
        }

        return $_arr_battle_skill;
    }

    function getEquipLordHeroSkill($_hero_pk): bool
    {
        $this->PgGame->query('SELECT COUNT(a.hero_pk) FROM my_hero_skill_slot a, m_hero_skill b WHERE a.hero_pk = $1 AND a.m_hero_skil_pk = b.m_hero_skil_pk AND b.yn_lord_skill = $2', [$_hero_pk, 'Y']);
        return $this->PgGame->fetchOne() > 0;
    }

    // 조합시 스킬 삭제를 위해 추가.
    function setDeleteHeroSkill($_lord_pk, $_m_hero_skil_pk, $_skill_cnt): bool
    {
        // my_hero_skil_pk 가져오기
        $this->PgGame->query('SELECT my_hero_skil_pk FROM my_hero_skill WHERE lord_pk = $1 AND m_hero_skil_pk = $2', [$_lord_pk, $_m_hero_skil_pk]);
        $my_hero_skil_pk = $this->PgGame->fetchOne();
        $my_hero_skill = $this->getMyHeroSkill($my_hero_skil_pk, $_lord_pk);

        // 자신이 가진 스킬보다 재료 개수가 많을 때
        if ($my_hero_skill['skill_cnt'] < $_skill_cnt) {
            return false;
        }

        // 스킬 제거
        if (($my_hero_skill['skill_cnt'] - $_skill_cnt) < 1) {
            $ret = $this->PgGame->query('DELETE FROM my_hero_skill WHERE my_hero_skil_pk = $1', [$my_hero_skil_pk]);
        } else {
            $ret = $this->PgGame->query('UPDATE my_hero_skill set skill_cnt = skill_cnt - $2 WHERE my_hero_skil_pk = $1', [$my_hero_skil_pk, $_skill_cnt]);
        }
        if (!$ret) {
            return false;
        }

        // 스킬 제거 로그
        $this->classLog();
        $this->Log->setHeroSkill($_lord_pk, null, null, 'DeleteCombiSkill', $_m_hero_skil_pk, null, null, null, 'material['.$_skill_cnt.'];remain['.$my_hero_skill['skill_cnt'].' -> '.($my_hero_skill['skill_cnt']-$_skill_cnt).']');

        return true;
    }

    // 기술 조합 관련
    function checkMySkill($_skill_list, $_selected_skill): false|array
    {
        $this->PgGame->query("SELECT my_hero_skil_pk, m_hero_skil_pk, skill_cnt FROM my_hero_skill WHERE lord_pk = $1 AND m_hero_skil_pk IN ({$_selected_skill})", [$this->Session->lord['lord_pk']]);
        $this->PgGame->fetchAll();
        $my_skill = $this->PgGame->rows;

        // 소유중인 스킬 정리 및 가격 체크.
        $my_skill_array = [];
        foreach($my_skill AS $v) {
            if (!isset($my_skill_array[$v['m_hero_skil_pk']])) {
                $my_skill_array[$v['m_hero_skil_pk']] = $v['skill_cnt'];
            }
        }

        $skill_array = [];
        foreach($_skill_list AS $v) {
            if (! isset($skill_array[$v])) {
                $skill_array[$v] = 1;
            } else {
                $skill_array[$v] += 1;
            }
        }

        // 수량 체크
        foreach ($skill_array AS $k => $v) {
            // 자신이 가지고 있지 않은 스킬이라면 false
            if (!$my_skill_array[$k]) {
                return false;
            }

            // 자신이 가진 스킬보다 재료수가 많다면 false
            if ($v > $my_skill_array[$k]) {
                return false;
            }
        }
        return $skill_array;
    }

    function getBonusPoint(): int|float
    {
        // 확률 테이블
        $acquired = [];
        $acquired[] = ['point' => 0, 'rate' => 1339];
        $acquired[] = ['point' => 0.1, 'rate' => 2000];
        $acquired[] = ['point' => 0.2, 'rate' => 3000];
        $acquired[] = ['point' => 0.3, 'rate' => 2000];
        $acquired[] = ['point' => 0.4, 'rate' => 1000];
        $acquired[] = ['point' => 0.5, 'rate' => 500];
        $acquired[] = ['point' => 0.6, 'rate' => 100];
        $acquired[] = ['point' => 0.7, 'rate' => 50];
        $acquired[] = ['point' => 0.8, 'rate' => 10];
        $acquired[] = ['point' => 0.9, 'rate' => 1];

        $range_prev = 1;
        $range_random_key = rand(1, 10000); // 만분율
        $range_select = 0;

        foreach($acquired AS $rate) {
            if ($rate['rate'] == 0) {
                continue;
            }
            $next = $range_prev + $rate['rate'];
            if ($range_random_key >= $range_prev && $range_random_key <= $next) {
                $range_select = $rate['point'];
                break;
            }
            $range_prev = $next;
        }
        return $range_select;
    }

    function getCombinationSkill($_rare): int
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_skill']);

        if (!$_rare) {
            return false;
        }
        $m_skil_pk = false;
        $m_skil_array = [];

        // 레어도와 타입에 맞춰 스킬 정리
        foreach($_M['HERO_SKILL'] AS $v) {
            if ($v['rare'] == $_rare && $v['yn_lord_skill'] != 'Y') {
                $m_skil_array[] = $v;
            }
        }

        // 정리한 스킬을 셔플
        shuffle($m_skil_array);
        if ($m_skil_array[0]['m_hero_skil_pk']) {
            $m_skil_pk = $m_skil_array[0]['m_hero_skil_pk']; // 첫 배열값을 선택
        }
        return $m_skil_pk;
    }
}