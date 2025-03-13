<?php
/*
 * TODO 전투는 일단 컨버전만 해놓은 상태. 동작 확인 필수! 난잡한 코드도 많다.
 */
class Battle
{
    protected Session $Session;
    protected Pg $PgGame;
    protected HeroSkill $HeroSkill;
    protected Troop $Troop;
    protected Report $Report;
    protected Resource $Resource;
    protected GoldPop $GoldPop;
    protected FigureReCalc $FigureReCalc;
    protected Army $Army;
    protected Medical $Medical;
    protected Fortification $Fortification;
    protected Log $Log;


// $FortificationValley

    public function __construct(Session $_Session, Pg $_PgGame, Troop $_Troop)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->Troop = $_Troop;
    }

    function classLog(): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    function classArmy(): void
    {
        if (! isset($this->Army)) {
            $this->classResource();
            $this->classGoldPop();
            $this->Army = new Army($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classMedical(): void
    {
        if (! isset($this->Medical)) {
            $this->Medical = new Medical($this->Session, $this->PgGame);
        }
    }

    function classFortification(): void
    {
        if (! isset($this->Fortification)) {
            $this->Fortification = new Fortification($this->Session, $this->PgGame);
        }
    }

    function classHeroSkill(): void
    {
        if (! isset($this->HeroSkill)) {
            $this->HeroSkill = new HeroSkill($this->Session, $this->PgGame);
        }
    }

    function classReport(): void
    {
        if (! isset($this->Report)) {
            $this->Report = new Report($this->Session, $this->PgGame);
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

    // 진영 조정
    function positionAdjust(&$_position, $_army): void // 매개변수는 모두 reference type
    {
        // row loop
        for ($i = 0, $i_l = COUNT($_position); $i < $i_l; $i++) {
            $line =& $_position[$i]; // row 선택
            $line_unit_remain = 0; // row 전체의 잔존 병력
            // col loop
            for ($j = 0, $j_l = COUNT($line); $j < $j_l; $j++) {
                $c = $line[$j]; // col 선택
                if (! isset($_army[$c])) {
                    unset($line[$j]); // col 제거
                    continue;
                }
                $d = $_army[$c]; // col 병과의 병력정보 선택

                // unit 리셋
                if ($d['unit_remain'] == 0) {
                    unset($line[$j]); // col 제거
                } else {
                    $line_unit_remain += $d['unit_remain']; // col 잔존 병력을 row 잔존 병력에 합산
                }
            } // end for

            // line 리셋
            if ($line_unit_remain == 0) {
                unset($_position[$i]); // 빈 row 제거
            } else {
                $line = array_merge($line); // col 이 있을 경우 adjust
            }
        }

        // 잔존병력 없음
        if (!$_position || COUNT($_position) == 0) {
            $_position = false; // 제거!
        } else {
            $_position = array_merge($_position); // row 가 있을 경우 adjust
        }
        // return $_position;
    }

    // 병력셋팅
    function addUnit($_battle_type, &$_army, $_unit_type, $_unit_count): bool // 매개변수는 모두 reference type
    {
        global $NsGlobal, $_M;
        $NsGlobal->requireMasterData(['building', 'fortification', 'army']);

        if ($_unit_count <= 0) {
            return false;
        }

        if (in_array($_unit_type, ['trap', 'abatis', 'tower'])) {
            if ($_battle_type != 'territory' && $_battle_type != 'valley') {
                return false;
            }
            $m =& $_M['FORT_C'][$_unit_type];
        } else if ($_unit_type == 'wall') {
            if ($_battle_type != 'territory') {
                return false;
            }

            // 성벽 마스터 데이터
            $m =& $_M['CODESET']['CASTLE_WALL']['SPEC'];
            $_unit_count = $_M['BUIL'][PK_BUILDING_WALL]['level'][$_unit_count]['variation_1'];
        } else {
            $m =& $_M['ARMY_C'][$_unit_type];
        }

        $_army[$_unit_type]['attack'] = $m['spec_attack'];
        $_army[$_unit_type]['defence'] = $m['spec_defence'];
        $_army[$_unit_type]['energy'] = $m['spec_energy'];
        $_army[$_unit_type]['unit_amount'] = $_unit_count;
        $_army[$_unit_type]['unit_dead_last_turn'] = 0;
        $_army[$_unit_type]['unit_remain'] = $_unit_count;
        $_army[$_unit_type]['unit_injury'] = 0;

        return true;
    }

    // 전투
    function doBattleMaxTurn($_battle_type, &$_att, &$_att_data, &$_def, &$_def_data, &$z_content_battle, $_att_battle_skil_arr = null, $_def_battle_skil_arr = null, $_relation = null): array // 매개변수는 모두 reference type
    {
        global $NsGlobal, $_M, $turn_description;
        $NsGlobal->requireMasterData(['hero_skill']);

        // 초기화
        $turn_description = '';  // 턴 설명
        $turn_count = 1; // 진행 턴
        $att_success = false; // 공격 성공 여부
        $def_success = false; // 방어 성공 여부

        // 기존 소스 유지를 위해서.

        $att =& $_att;
        $att_data =& $_att_data;
        $def =& $_def;
        $def_data =& $_def_data;

        // 전투전처리
        $z_content_battle['scene'][0]['att_pos'] = $_att;
        $z_content_battle['scene'][0]['def_pos'] = $_def;
        $unit_data = $this->getUnitSceneData($att_data);
        $z_content_battle['scene'][0]['att_unit'] = $unit_data['unit'];
        $z_content_battle['scene'][0]['att_unit_sum'] = $unit_data['unit_sum'];
        $unit_data = $this->getUnitSceneData($def_data);
        $z_content_battle['scene'][0]['def_unit'] = $unit_data['unit'];
        $z_content_battle['scene'][0]['def_unit_sum'] = $unit_data['unit_sum'];
        $z_content_battle['scene'][0]['battle_unit'] = [];

        //  섬멸전은 5턴
        $max_turn = ($_battle_type == 'raid') ? RAID_MAX_TURN : BATTLE_MAX_TURN;

        $this->classHeroSkill();

        for (; $turn_count <= $max_turn; $turn_count++) {
            // unit_dead_last_turn 리셋
            foreach ($att_data AS $k => $v) {
                if (isset($v)) {
                    $att_data[$k]['unit_dead_last_turn'] = 0;
                }
            }

            foreach ($def_data AS $k => $v) {
                if (isset($v)) {
                    $def_data[$k]['unit_dead_last_turn'] = 0;
                }
            }

            // 이번 합에서의 자리를 잡고
            $z_content_battle['scene'][$turn_count]['att_pos'] = $_att;
            $z_content_battle['scene'][$turn_count]['def_pos'] = $_def;

            // 공격턴을 위해 방어측 포지션 백업
            $backup_def = $def;

            // 전투 스킬 발동
            $att_m_hero_skil_pk = null;
            $def_m_hero_skil_pk = null;

            $turn_description .= "<h2> $turn_count 턴</h2>";

            $turn_description .= "<h3 class=\"attack\"> 공격측 턴</h3>";
            $turn_description .= "<div class=\"attack\">";

            // 공격측
            $att_m_hero_skil_arr = [];
            if (is_array($_att_battle_skil_arr) && count($_att_battle_skil_arr) > 0) {
                $att_m_hero_skil_arr = $this->HeroSkill->getBattleHeroSkill($_att_battle_skil_arr);
                if (isset($att_m_hero_skil_arr)) {
                    $att_m_hero_skil_pk = $att_m_hero_skil_arr['m_pk'];
                    $turn_description .= "<div class=\"skill\"> 공격측 스킬\n". $_M['HERO_SKILL'][$att_m_hero_skil_pk]['title'] . $_M['HERO_SKILL'][$att_m_hero_skil_pk]['rare'] . " 발동</div>";
                }
            }

            // 방어측
            $def_m_hero_skil_arr = [];
            if (is_array($_def_battle_skil_arr) && count($_def_battle_skil_arr) > 0) {
                $def_m_hero_skil_arr = $this->HeroSkill->getBattleHeroSkill($_def_battle_skil_arr);
                if (isset($def_m_hero_skil_arr)) {
                    $def_m_hero_skil_pk = $def_m_hero_skil_arr['m_pk'];
                    $turn_description .= "<div class=\"skill\"> 방어측 스킬\n". $_M['HERO_SKILL'][$def_m_hero_skil_pk]['title'] . $_M['HERO_SKILL'][$def_m_hero_skil_pk]['rare'] . " 발동</div>";
                }
            }

            $att_def_skill = [];
            if ($att_m_hero_skil_pk) {
                if ($_M['HERO_SKILL'][$att_m_hero_skil_pk]['battle_type'] != 'A') {
                    foreach($att_data AS $k => $v) {
                        $att_def_skill[] = $k;
                    }
                }
            }
            // echo '========================================================================================<br />turn_count:'.$turn_count.'<br />========================================================================================<br />';

            $att_retval = $this->doBattle($att, $att_data, $def, $def_data, $att_m_hero_skil_pk, $def_m_hero_skil_pk);
            $turn_description .= "\n</div>";

            $turn_description .= "<h3 class=\"defence\"> 방어측 턴</h3>";
            $turn_description .= "<div class=\"defence\">";

            $def_def_skill = [];
            if ($def_m_hero_skil_pk) {
                if ($_M['HERO_SKILL'][$def_m_hero_skil_pk]['battle_type'] != 'A') {
                    foreach($def_data AS $k => $v) {
                        if ($_M['HERO_SKILL'][$def_m_hero_skil_pk]['battle_type'] == 'D') {
                            if ($k != 'trap' && $k != 'abatis' && $k != 'tower' && $k != 'wall') {
                                $def_def_skill[] = $k;
                            }
                        } else {
                            $def_def_skill[] = $k;
                        }
                    }
                }
            }

            $def_retval = $this->doBattle($backup_def, $def_data, $att, $att_data, $def_m_hero_skil_pk, $att_m_hero_skil_pk);
            $turn_description .= "\n</div>";

            // 합 결과
            $unit_data = $this->getUnitSceneData($att_data);
            $z_content_battle['scene'][$turn_count]['att_unit'] = $unit_data['unit'];
            $z_content_battle['scene'][$turn_count]['att_unit_sum'] = $unit_data['unit_sum'];
            $z_content_battle['scene'][$turn_count]['att_battle_skill']['pk'] = $att_m_hero_skil_arr['m_pk'] ?? null;
            $z_content_battle['scene'][$turn_count]['att_battle_skill']['hero_pk'] = (isset($att_m_hero_skil_arr['hero_pk'])) ? $this->Troop->getHeroMasterDataPK($att_m_hero_skil_arr['hero_pk']) : null;
            $unit_data = $this->getUnitSceneData($def_data);
            $z_content_battle['scene'][$turn_count]['def_unit'] = $unit_data['unit'];
            $z_content_battle['scene'][$turn_count]['def_unit_sum'] = $unit_data['unit_sum'];
            $z_content_battle['scene'][$turn_count]['def_battle_skill']['pk'] = $def_m_hero_skil_arr['m_pk'] ?? null;
            $z_content_battle['scene'][$turn_count]['def_battle_skill']['hero_pk'] = (isset($def_m_hero_skil_arr['hero_pk'])) ? $this->Troop->getHeroMasterDataPK($def_m_hero_skil_arr['hero_pk']) : null;

            $z_content_battle['scene'][$turn_count]['battle_unit'] = ['att' => $att_retval, 'def' => $def_retval];
            $z_content_battle['scene'][$turn_count]['battle_skill_unit'] = ['att' => $att_def_skill, 'def' => $def_def_skill];

            // 만족시 승/패 가르기 위해 루틴 out
            //  - 공통 : 10합
            //  - 영지 : 성벽 무너지면
            //  - 자원지 : 공/방측 한 곳 이상에서 전체 병력 상실 시
            if ($_battle_type == 'territory' && $def_data['wall']['unit_remain'] <= 0) {
                break;
            } else {
                if (!$att || !$def) {
                    break;
                }
            }
        }

        // 전투후처리

        // 영지전 결과
        if ($_battle_type == 'territory') {
            // 성벽 무너짐 체크

            if ($def_data['wall']['unit_remain'] > 0) { // 성벽 잔존
                $def_success = true;
            } else { // 성벽은 무너졌고
                // 잔존 병력 체크 후 승리 처리
                if ($att) {
                    $att_success = true;
                } else {
                    // 성벽은 파괴 하였으나 전멸 당했을 경우 방어측 승리 (영지의 경우만)
                    $def_success = true;
                }
            }
        } else {
            // 자원지전 결과
            if ($def) {
                $def_success = true; // 방어측 병력 잔존시 무조건 승리
            } else {
                if ($att) {
                    // 방어측 병력 잔존 0, 공격측 병력 잔존시 승!
                    $att_success = true;
                }
            }
        }

        $turn_description .= "\n\n";

        // 전투 결과
        $turn_description .= "<span style='color:black;'>> 공격측 승? ($att_success)</span> \n";
        $turn_description .= "<span style='color:black;'>> 방어측 승? ($def_success)</span> \n\n";

        $turn_count = min($turn_count, BATTLE_MAX_TURN);

        // echo $turn_description;
        return [$turn_description, $turn_count, $att_success, $def_success];
    }

    // 전투 - TODO 제대로 동작하는지 의문...
    function doBattle(&$_att, &$_att_data, &$_def, &$_def_data, $_att_battle_skil_pk = null, $_def_battle_skil_pk = null): array // 매개변수는 모두 reference type
    {
        global $NsGlobal, $_M, $turn_description;
        $NsGlobal->requireMasterData(['hero_skill', 'fortification', 'army']);

        $battle_unit = [];
        $remain_damage = 0;
        $att_m_hero_skil_pk = 0;
        $def_m_hero_skil_pk = 0;
        $ene_m_hero_skil_pk = 0;

        // 전투 스킬 발동
        // 공격
        if ($_att_battle_skil_pk > 0) {
            if ($_M['HERO_SKILL'][$_att_battle_skil_pk]['battle_type'] == 'A') {
                $att_m_hero_skil_pk = $_att_battle_skil_pk;
            }
        }

        // 방어
        if ($_def_battle_skil_pk > 0) {
            if ($_M['HERO_SKILL'][$_def_battle_skil_pk]['battle_type'] == 'D') {
                $def_m_hero_skil_pk = $_def_battle_skil_pk;
            } else if ($_M['HERO_SKILL'][$_def_battle_skil_pk]['battle_type'] == 'E') {
                $ene_m_hero_skil_pk = $_def_battle_skil_pk;
            }
        }

        // row loop
        for ($i = 0, $i_l = COUNT($_att); $i < $i_l; $i++) {
            // 대상이 전멸한 경우
            if (COUNT($_def) == 0) {
                //$this->positionAdjust(&$_def, &$_def_data);
                continue;
            }
            $line =& $_att[$i]; // row 선택

            // col loop
            for ($j = 0, $j_l = COUNT($line); $j < $j_l; $j++) {
                // 대상이 전멸한 경우
                if (COUNT($_def) == 0) {
                    //$this->positionAdjust(&$_def, &$_def_data);
                    continue;
                }


                $c = $line[$j]; // col 선택
                $a =& $_att_data[$c]; // col 병과의 병력정보 선택
                if (! isset($a)) {
                    continue;
                }
                // 공격력이 없는 병과는 bypass (ex 성벽)
                if ($a['attack'] == 0) {
                    continue;
                }

                // 공격 병과 마스터 정보
                if ($c == 'trap' || $c == 'abatis' || $c == 'tower') {
                    $m =& $_M['FORT_C'][$c];
                } else {
                    $m =& $_M['ARMY_C'][$c];
                }

                // 몇 칸 공격? 사정거리와 현재 라인위치로 계산
                //  - 공격불가시 skip
                //  - spec_target_range 가 2이면
                //      * ##
                //    210|012 -> 0,1 공격 가능이고 range는 2
                //     *# #
                //    210|012 -> 0 공격 가능하고 range는 1
                //  => range는 0,0 기준으로 매겨진다.
                $range = $m['spec_target_range']-$i;
                if ($range <= 0) {
                    continue;
                }

                $battle_unit[] = $c;

                $attack_range = $m['spec_attack_range'];

                // 공격 목표
                $target = [];
                $bonus_mult_attack = false;
                $bonus_mult_attack_unit = [];

                // 동시공격 가능 범위 (range 자체가 제약되기 때문에 spec_attack_range는 별도로 동작토록 한다.)
                for ($p = 0; $p < $range && $p < $m['spec_attack_range']; $p++) {
                    $bonus_attack = false;
                    // 목표물과의 거리와 효율
                    $distance = $p;
                    $attack_efficiency = $distance+$i;
                    if ($attack_range > 1 && isset($multi_attack)) {
                        if (! isset($multi_attack_line[$p])) {
                            continue;
                        }

                        $bonus_mult_attack = true;
                        $bonus_mult_attack_unit[$p] = $multi_attack_line[$p];
                    }

                    if ($m['code'] == 'catapult' || $m['code'] == 'archer' || $m['code'] == 'bowman') {
                        $distance = $range - $p - 1;

                        /////여기서 for돌면서 공격가능한것 있나 찾아보기
                        $new_distance = $distance;
                        $col = 0;
                        for($aa = $new_distance; $aa > 0; $aa--) {
                            $d_c = NULL;
                            // 병과 없음
                            if (! isset($_def[$aa][$col])) {
                                $new_distance--;
                                continue;
                            }
                            $d_c = $_def[$aa][$col];
                            $d =& $_def_data[$d_c];
                            if (! isset($d)) {
                                continue;
                            }
                            if ($d['unit_remain'] == 0) {
                                $col++;
                            }
                            break;
                        }

                        $distance = $new_distance;
                        $attack_efficiency = $distance+$i;
                    }

                    //$turn_description .= $new_distance .'<br />';

                    /*if ($bonus_attack && $bonus_distance)
                    {
                        if ($bonus_distance != $p)
                        {
                            continue;
                        }
                    }*/
                    /*
                     * 공격 대상을 row로 정의
                     *  - col은 0으로 고정되어 있음.
                     */
                    $target[$distance] = $attack_efficiency;
                    //$turn_description .= "{$range} {$m['spec_attack_range']}\n";
                }

                // 공격 거리효율
                $e = explode(',', $m['spec_attack_efficiency']);

                $turn_description .= "\n{$m['title']} $range 칸 공격\n";
                $multi_attack = false;
                $multi_attack_line = [];
                $multi_attack_end = false;

                foreach ($target AS $t => $t_efficiency) {
                    $col_cnt = 0;
                    $d_c = null;
                    if (isset($_def[$t])) {
                        foreach ($_def[$t] AS $v) {
                            $d_c = $_def[$t][$col_cnt];
                            if (! isset($d_c)) {
                                break; // 병과 없음
                            }

                            $d =& $_def_data[$d_c];
                            if (! isset($d)) {
                                continue;
                            }

                            if ($d['unit_remain'] <= 0) {
                                $col_cnt++;
                                continue;
                            }
                            break;
                        }
                    }

                    // 병과 없음
                    if (!$d_c) {
                        continue;
                    }

                    $d =& $_def_data[$d_c];
                    if (! isset($d)) {
                        continue;
                    }

                    $turn_description .= "공격 위치: {$t} {$col_cnt}\n";
                    // 방어 병과 마스터 정보
                    if ($d_c == 'trap' || $d_c == 'abatis' || $d_c == 'tower') {
                        $d_m =& $_M['FORT_C'][$d_c];
                    } else if ($d_c == 'wall') {
                        $d_m =& $_M['CODESET']['CASTLE_WALL']['SPEC'];
                    } else {
                        $d_m =& $_M['ARMY_C'][$d_c];
                    }

                    $turn_description .= " 공격 : {$t} 거리의 {$d_c} 병과를 {$e[$t_efficiency]}% 효율로 때림\n";

                    $spec_attack = intval($a['attack'] * ($e[$t_efficiency] * 0.01));
                    //echo '<br>병과:'.$m['code'];
                    //echo '<br>원공격력:'.$spec_attack;
                    //echo '<br>발동스킬:'.$att_m_hero_skil_pk;
                    // 공격 스킬 발동
                    if ($att_m_hero_skil_pk) {
                        //if ($m['code'] != 'trap' && $m['code'] != 'abatis' && $m['code'] != 'tower' && $m['code'] != 'wall')
                        $spec_attack += $this->HeroSkill->setBattleAttackSkill($att_m_hero_skil_pk, $spec_attack);
                    }
                    //echo '<br>추가공격력:'.$spec_attack;

                    $spec_defence = $d['defence'];
                    // 방어 스킬 발동
                    //echo '<br>'.$d_c;
                    //echo '<br>원래값:'.$spec_defence;
                    if ($def_m_hero_skil_pk) {
                        if ($d_c != 'trap' && $d_c != 'abatis' && $d_c != 'tower' && $d_c != 'wall') {
                            $spec_defence += $this->HeroSkill->setBattleAttackSkill($def_m_hero_skil_pk, $spec_defence);
                        }
                    }
                    //echo '<br>변경된값:'.$spec_defence;

                    $spec_energy = $d['energy'];

                    // 공격 병력 수
                    $unit_curr = $a['unit_dead_last_turn']+$a['unit_remain'];
                    if ($bonus_attack && $remain_unit_curr > 0 && $attack_range == 1) {
                        $unit_curr = $remain_unit_curr;
                    } else if ($multi_attack_end && $bonus_mult_attack && $bonus_mult_attack_unit[$t] > 0 && $attack_range > 1) {
                        $unit_curr = $bonus_mult_attack_unit[$t];
                    }

                    //$turn_description .= "{$multi_attack_end} && {$bonus_mult_attack} && {$bonus_mult_attack_unit[$t]} && {$attack_range}";

                    // 방어 병력 수
                    $unit_curr_def = $d['unit_remain'];
                    if ($unit_curr_def == 0) {
                        continue;
                    }

                    // 데미지
                    $damage = $unit_curr * $spec_attack / $spec_defence;
                    // echo '<br>원데미지:'.$damage;
                    if ($ene_m_hero_skil_pk) {
                        $damage -= $this->HeroSkill->setBattleAttackSkill($ene_m_hero_skil_pk, $damage);
                        // echo '<br>스킬 적용 데미지:'.$damage;
                    }
                    $turn_description .= "공격인원 {$unit_curr}, {$spec_attack}의 공격력으로 공격\n";

                    $weak = null;
                    $damage_type = null;

                    // 취약병과 - 방어측에 병과가 공격측 병과에 취약한지 판단
                    if (strpos(','. $d_m['weaker_type'], $c) > 0) {
                        $weak = '매우취약병과를 공격';
                        $damage *= BATTLE_DAMAGE_PLUS_WEAKER;
                        $damage_type = BATTLE_DAMAGE_PLUS_WEAKER;
                    } else if (strpos(','. $d_m['weak_type'], $c) > 0) {
                        $weak = '취약병과를 공격';
                        $damage *= BATTLE_DAMAGE_PLUS_WEAK;
                        $damage_type = BATTLE_DAMAGE_PLUS_WEAK;
                    } else {
                        // 취약병과 - 공격측 병과가 방어측 병과에 취약한지 판단
                        if (strpos(','. $m['weaker_type'], $d_c) > 0) {
                            $weak = '매우취약병과에서 공격';
                            $damage *= BATTLE_DAMAGE_MINUS_WEAKER_R;
                            $damage_type = BATTLE_DAMAGE_MINUS_WEAKER_R;
                        } else if (strpos(','. $m['weak_type'], $d_c) > 0) {
                            $weak = '취약병과에서 공격';
                            $damage *= BATTLE_DAMAGE_MINUS_WEAK_R;
                            $damage_type = BATTLE_DAMAGE_MINUS_WEAK_R;
                        }
                    }

                    if ($weak == null) {
                        $weak = '일반병과공격';
                        $damage *= BATTLE_DAMAGE_NOWEAK;
                        $damage_type = BATTLE_DAMAGE_NOWEAK;
                    }

                    // 잔존 병력 수 - 소수점 이하 버림
                    $bonus_attack = false;
                    $remain_unit_curr = 0;
                    $unit_remain = floor(($d['unit_remain']*$spec_energy-$damage)/$spec_energy);
                    if ($unit_remain < 0) {
                        $unit_dead_real = $d['unit_remain']+($unit_remain*-1);

                        // 데미지에 의한 사망 병력 수가 병력의 5배 보다 클 경우. (5배에서 2배로 변경)
                        if (abs($unit_remain)/$unit_curr_def >= 2) {
                            $bonus_attack = true;
                            $remain_damage = $damage - floor($d['unit_remain']*$spec_energy);
                            $remain_unit_curr = floor($remain_damage*$spec_defence/$spec_attack/$damage_type);
                            $turn_description .= $remain_unit_curr .'<br/>';
                        }

                        $unit_remain = 0;
                    } else {
                        $unit_dead_real = $d['unit_remain'] - $unit_remain;
                    }

                    // 사망 병력 수
                    $unit_dead = $d['unit_remain'] - $unit_remain;

                    $turn_description .= " 결과 : ({$weak}) {$unit_curr_def} 중 {$unit_dead} 사망(orig $unit_dead_real) {$unit_remain} 잔존\n";

                    // 저장
                    $d['unit_remain'] = $unit_remain;
                    $d['unit_dead_last_turn'] += $unit_dead;

                    // 잔존 병력이 없으면 positionAdjust
                    if ($unit_remain <= 0) {
                        //$def =
                        //$this->positionAdjust(&$_def, &$_def_data);
                    }

                    // 추가 공격? - 반드시 방어측이 남아 있어야 한다.
                    if ($bonus_attack && COUNT($_def) > 0 && !$multi_attack) {
                        $turn_description .= " +++++ one more attack\n";
                        $multi_attack = true;
                        $multi_attack_line[$t] = $remain_unit_curr;
                        $j--;
                    }
                } // end foreach
                $multi_attack_end = true;
            } // end for - col
        } // end for - row

        //$this->positionAdjust(&$_def, &$_def_data);
        $this->positionAdjust($_def, $_def_data);

        // echo $turn_description;

        return $battle_unit;
    }

    // 병력정보에서 scene 데이터만 추출
    function getUnitSceneData($_unitArr): array
    {
        $z_arr = [];
        $z_arr['unit_sum'] = 0;
        // 병력 합계
        foreach ($_unitArr AS $k => $v) {
            if (! $v) {
                continue;
            }
            $z_arr['unit'][$k] = ['remain' => $v['unit_remain'], 'dead' => $v['unit_dead_last_turn'], 'mil_aptitude' => $v['max_values'] ?? 0];
            if ($k == 'wall' || $k == 'trap' || $k == 'abatis' || $k == 'tower') {
                $v['unit_remain'] = $v['unit_remain'] / 100;
            }
            $z_arr['unit_sum'] += (INT)$v['unit_remain'];
        }
        return $z_arr;
    }

    // 전투결과
    // 부대 피해반영
    // 영지 피해반영

    // 공격측 결과
    // 방어측 결과

    function setDamageDstTroopFromLordValley($_troo_pk, $_damage_army, $_damage_reso): void
    {
        // TODO 이건 뭐야?
    }

    // 일기토
    function setBattleManToMan($_att_hero_arr, $_att_pois_pk, $_def_hero_arr, $_def_posi_pk)
    {
        // 1. 영웅 선발
        $att_hero = $this->getHerosMaxMilForce($_att_hero_arr, $_att_pois_pk); // 공격측
        $def_hero= $this->getHerosMaxMilForce($_def_hero_arr, $_def_posi_pk); // 방어측

        // 1-2. m_hero_pk 추가 하기
        if (isset($att_hero)) {
            foreach ($att_hero AS $k => $v) {
                if ($v['hero_pk'] == $_att_hero_arr['captain_hero_pk']) {
                    $att_hero[$k]['m_hero_pk'] = $_att_hero_arr['captain_m_hero_pk'];
                } else if ($v['hero_pk'] == $_att_hero_arr['director_hero_pk']) {
                    $att_hero[$k]['m_hero_pk'] = $_att_hero_arr['director_m_hero_pk'];
                } else if ($v['hero_pk'] == $_att_hero_arr['staff_hero_pk']) {
                    $att_hero[$k]['m_hero_pk'] = $_att_hero_arr['staff_m_hero_pk'];
                }
            }
        }

        if (isset($def_hero)) {
            foreach ($def_hero AS $k => $v) {
                if ($v['hero_pk'] == $_def_hero_arr['captain_hero_pk']) {
                    $def_hero[$k]['m_hero_pk'] = $_def_hero_arr['captain_m_hero_pk'];
                } else if ($v['hero_pk'] == $_def_hero_arr['director_hero_pk']) {
                    $def_hero[$k]['m_hero_pk'] = $_def_hero_arr['director_m_hero_pk'];
                } else if ($v['hero_pk'] == $_def_hero_arr['staff_hero_pk']) {
                    $def_hero[$k]['m_hero_pk'] = $_def_hero_arr['staff_m_hero_pk'];
                }
            }
        }

        if (!$att_hero && !$def_hero) {
            // 무승부
            return ['result' => BATTLE_MANTOMAN_TIE, 'battle_turn' => 0];
        } else if (!$att_hero || !$def_hero) {
            $result = BATTLE_MANTOMAN_DEFENCE_WIN; // TODO 따로 추가함. 확인 필요.
            if (! $att_hero) {
                $result = BATTLE_MANTOMAN_DEFENCE_WIN;
            } else if (! $def_hero) {
                $result = BATTLE_MANTOMAN_ATTACK_WIN;
            }

            return ['result' => $result, 'battle_turn' => 0, 'att_hero' => $att_hero, 'def_hero' => $def_hero];
        }

        $att_max_cnt = COUNT($att_hero);
        $def_max_cnt = COUNT($def_hero);
        $att_cnt = 0;
        $def_cnt = 0;
        // 첫번째 영웅
        $att_MTM_hero[$att_cnt] = $att_hero[$att_cnt];
        $def_MTM_hero[$def_cnt] = $def_hero[$def_cnt];

        // 2. 추가 영웅 출전
        $att_mil_force = $att_MTM_hero[$att_cnt]['mil_force'];
        $def_mil_force = $def_MTM_hero[$def_cnt]['mil_force'];

        $condition = true;
        while($condition) {
            if (abs($att_mil_force - $def_mil_force) >= 40) {
                if ($att_mil_force < $def_mil_force) {
                    // 공격측 추가 영웅
                    $att_cnt++;
                    if ($att_cnt < $att_max_cnt) {
                        $att_MTM_hero[$att_cnt] = $att_hero[$att_cnt];
                        $att_mil_force += $att_MTM_hero[$att_cnt]['mil_force'];
                    } else {
                        $condition = false;
                    }
                } else {
                    // 방어측 추가 영웅
                    $def_cnt++;
                    if ($def_cnt < $def_max_cnt) {
                        $def_MTM_hero[$def_cnt] = $def_hero[$def_cnt];
                        $def_mil_force += $def_MTM_hero[$def_cnt]['mil_force'];
                    } else {
                        $condition = false;
                    }
                }
            } else {
                $condition = false;
            }
        }

        // 3. 일기토 진행
        $battle_mantoman_info = [];
        $att_max_cnt = COUNT($att_MTM_hero) - 1;
        $def_max_cnt = COUNT($def_MTM_hero) - 1;
        $att_cnt = 0;
        $def_cnt = 0;
        $att_passible = true;
        $def_passible = true;
        $battle_mantoman_turn = 0;
        $def_lose = false;
        $att_lose = false;

        // 보고서
        /*$z_content_battle = Array();
        $z_content_battle['scene'][0]['att_hero'] = $att_MTM_hero;
        $z_content_battle['scene'][0]['def_hero'] = $def_MTM_hero;*/

        for($i = 0; $i < BATTLE_MANTOMAN_MAX_TURN; $i++) {
            // 공격 가능한지 검사
            if ($att_cnt > $att_max_cnt) {
                $att_cnt = 0;
            }

            if ($def_cnt > $def_max_cnt) {
                $def_cnt = 0;
            }

            $start_att_cnt = $att_cnt;
            $att_passible = true;
            $def_passible = true;

            while($att_passible) {
                //echo '$att_MTM_hero:'.$att_MTM_hero[$att_cnt]['energy'].'<br>';
                if ($att_MTM_hero[$att_cnt]['energy'] < BATTLE_MANTOMAN_LIMIT_ENERGY) {
                    $att_cnt++;
                    if ($att_cnt > $att_max_cnt) {
                        $att_cnt = 0;
                    }
                } else {
                    $att_passible = false;
                }

                if ($start_att_cnt == $att_cnt && $att_passible) {
                    $att_passible = false;
                    $att_lose = true;
                    $i = BATTLE_MANTOMAN_MAX_TURN;
                }
            }

            $start_def_cnt = $def_cnt;
            while($def_passible) {
                if ($def_MTM_hero[$def_cnt]['energy'] < BATTLE_MANTOMAN_LIMIT_ENERGY) {
                    $def_cnt++;
                    if ($def_cnt > $def_max_cnt) {
                        $def_cnt = 0;
                    }
                } else {
                    $def_passible = false;
                }

                if ($start_def_cnt == $def_cnt && $def_passible) {
                    $def_passible = false;
                    $def_lose = true;
                    $i = BATTLE_MANTOMAN_MAX_TURN;
                }
            }

            // 일기토 종료
            if ($i == BATTLE_MANTOMAN_MAX_TURN) {
                continue;
            }

            // 공격
            $att_critical = rand(1, 100) > BATTLE_MANTOMAN_CRITICAL_RATE ? 1 : 2;
            $att_miss = rand(1, 100) == BATTLE_MANTOMAN_MISS_RATE ? 0 : 1;
            $att_damage = $att_MTM_hero[$att_cnt]['mil_force']/$def_MTM_hero[$def_cnt]['mil_force'] * 8 * (rand(8, 15) * 0.1) * $att_critical * $att_miss;

            $def_critical = rand(1, 100) > BATTLE_MANTOMAN_CRITICAL_RATE ? 1 : 2;
            $def_miss = rand(1, 100) == BATTLE_MANTOMAN_MISS_RATE ? 0 : 1;
            $def_damage = $def_MTM_hero[$def_cnt]['mil_force']/$att_MTM_hero[$att_max_cnt]['mil_force'] * 8 * (rand(8, 15) * 0.1) * $def_critical * $def_miss;

            // 생명력 감소
            $att_MTM_hero[$att_cnt]['energy'] -= $def_damage;
            $def_MTM_hero[$def_cnt]['energy'] -= $att_damage;

            // 전투 시뮬레이터
            $battle_mantoman_info[$i]['att'] = ['att_hero' => $att_MTM_hero[$att_cnt], 'critical' => $att_critical, 'miss' => $att_miss, 'damage' => $def_damage];
            $battle_mantoman_info[$i]['def'] = ['def_hero' => $def_MTM_hero[$def_cnt], 'critical' => $def_critical, 'miss' => $def_miss, 'damage' => $att_damage];

            // 보고서
            //$z_content_battle['scene'][$i]['att_hero'] = $battle_mantoman_info[$i]['att']['att_hero'];

            $battle_mantoman_turn = $i + 1;

            if ($battle_mantoman_turn == BATTLE_MANTOMAN_MAX_TURN) {
                if ($att_MTM_hero[$att_cnt]['energy'] < BATTLE_MANTOMAN_LIMIT_ENERGY) {
                    $att_lose = true;
                }
                if ($def_MTM_hero[$def_cnt]['energy'] < BATTLE_MANTOMAN_LIMIT_ENERGY) {
                    $def_lose = true;
                }
            }
            $att_cnt++;
            $def_cnt++;
        }

        // 전투 결과와 전투에 참여한 영웅 상태리턴
        if ($def_lose == $att_lose) {
            // 무승부
            $result = BATTLE_MANTOMAN_TIE;
        } else {
            $result = BATTLE_MANTOMAN_DEFENCE_WIN; // TODO 따로 추가함. 차후 작업 시 정리가 필요할듯
            if ($def_lose){
                $result = BATTLE_MANTOMAN_ATTACK_WIN;
            } else if ($att_lose) {
                $result = BATTLE_MANTOMAN_DEFENCE_WIN;
            }
        }

        return ['result' => $result, 'battle_turn' => $battle_mantoman_turn, 'att_hero' => $att_MTM_hero, 'def_hero' => $def_MTM_hero, 'battle_mantoman_info' => $battle_mantoman_info];
    }

    // 무력 가장 높은 영웅 선발
    function getHerosMaxMilForce($_hero_arr, $_posi_pk): array
    {
        $_hero_arr['captain_hero_pk'] = $_hero_arr['captain_hero_pk'] ? $_hero_arr['captain_hero_pk'] : 0;
        $_hero_arr['director_hero_pk'] = $_hero_arr['director_hero_pk'] ? $_hero_arr['director_hero_pk'] : 0;
        $_hero_arr['staff_hero_pk'] = $_hero_arr['staff_hero_pk'] ? $_hero_arr['staff_hero_pk'] : 0;

        $this->PgGame->query('SELECT assign_hero_pk FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, PK_BUILDING_CITYHALL]);
        $hero_pk = $this->PgGame->fetchOne();
        if (! $hero_pk) {
            $hero_pk = 0;
        }

        $this->PgGame->query("SELECT hero_pk, mil_force FROM my_hero WHERE hero_pk IN ({$_hero_arr['captain_hero_pk']}, {$_hero_arr['director_hero_pk']}, {$_hero_arr['staff_hero_pk']})
AND mil_force >= $1 AND hero_pk != $2 ORDER BY mil_force DESC", [BATTLE_MANTOMAN_LIMIT_STAT, $hero_pk]);

        $hero_info = [];
        while($this->PgGame->fetch()) {
            $hero_info[] = ['hero_pk' => $this->PgGame->row['hero_pk'], 'mil_force' => $this->PgGame->row['mil_force'], 'energy' => 100];
        }

        return $hero_info;

    }


    // 군주부대, 군주영지, NPC영지에 전투피해 적용
    function doApplyDamage($_type, $_unitArr, $_posi_pk = null, $_lord_pk = null, $_troo_pk = null, &$_troop = null, &$_result = null, $_ally_troop = null, $_raid_troop_pk = null): false|array
    {
        if (!$_type || !$_unitArr || (!$_posi_pk && !$_troo_pk && !$_troop['valley_posi_pk']) || ($_type == 'raid')) {
            Debug::debugMessage('error', 'not enough arguments');
            return false;
        }

        global $_M_ARMY_C, $_M_FORT_C, $NsGlobal;
        $NsGlobal->requireMasterData(['army', 'fortification']);

        $this->classLog();
        $this->classArmy();
        $this->classMedical();
        $this->classFortification();
        $this->classFigureReCalc();

        // 타입에 따른 컬럼 prefix 선택
        if ($_type == 'territory_npc') {
            $army_key_prefix = 'army_';
            $fort_key_prefix = 'fort_';
        } else if ($_type == 'territory_lord') {
            $fort_key_prefix = '';
            $army_key_prefix = '';
        } else if ($_type == 'troop') {
            $army_key_prefix = 'army_';
            $fort_key_prefix = '';
        } else if ($_type == 'point_npc') {
            $army_key_prefix = 'army_';
            $fort_key_prefix = '';
        } else if ($_type == 'raid_npc') {
            $army_key_prefix = 'army_';
            $fort_key_prefix = '';
        } else if ($_type == 'npc') {
            // npc 는 타입 처리를 안하고 있었음. 오류를 남기지 않기위해 20230707 송누리
            return false;
        } else {
            Debug::debugMessage('error', 'unsupported type');
            return false;
        }

        $army_damage_arr = [];
        $fort_damage_arr = [];

        $army_origin_arr = [];
        $army_shift_arr = [];

        // 사망병력 추출
        foreach ($_unitArr AS $k => $v) {
            if ($k == 'wall' || ! $v) {
                continue;
            }
            $dead = $v['unit_amount']-$v['unit_remain'];

            if ($dead > 0) {
                if ($k == 'trap' || $k == 'abatis' || $k == 'tower') {
                    $fort_damage_arr[$k] = $dead;
                } else {
                    $army_damage_arr[$k] = $dead;
                }
            }

            if ($k != 'trap' && $k != 'abatis' && $k != 'tower') {
                // 부대의 경우 재계산을 위해서 캐싱
                if ($_type == 'troop') {
                    $army_origin_arr[$k] = $v['unit_amount'];
                    $army_shift_arr[$k] = $v['unit_remain'];
                }
            }
        }

        $army_medical_apply_bind_arr = [];
        $army_damage_apply_bind_arr = [];
        $fort_damage_apply_bind_arr = [];

        // army 감소
        foreach ($army_damage_arr AS $k => $v) {
            $army_medical_apply_bind_arr[$k] = $v;
            $army_damage_apply_bind_arr[$army_key_prefix. $k] = $v;
        }

        // fort 감소
        foreach ($fort_damage_arr AS $k => $v) {
            $fort_damage_apply_bind_arr[$fort_key_prefix. $k] = $v;
        }

        $injury_army = false;
        $abandon_army = false;

        // 사망병력 적용
        if ($_type == 'territory_npc') {
            $bind_str = '';
            foreach ($army_damage_apply_bind_arr AS $k => $v) {
                $bind_str .= sprintf(', %s = %s - %d', $k, $k, $v);
            }

            foreach ($fort_damage_apply_bind_arr AS $k => $v) {
                $bind_str .= sprintf(', %s = %s - %d', $k, $k, $v);
            }

            // yn_need_increase가 Y로 발생하는 시점에서만 last_update_dt 를 갱신한다.
            $this->PgGame->query('SELECT yn_need_increase FROM position_npc WHERE posi_pk = $1',  [$_posi_pk]);
            // 기존 값이 N 이면 처리
            if ($this->PgGame->fetchOne() == 'N') {
                $bind_str .= ', last_update_dt = now()';
            }

            $this->PgGame->query('UPDATE position_npc SET yn_need_increase = $1'. $bind_str. ' WHERE posi_pk = $2', ['Y', $_posi_pk]);
        } else if ($_type == 'point_npc') {
            $bind_str = '';
            foreach ($army_damage_apply_bind_arr AS $k => $v) {
                $bind_str .= sprintf(', %s = %s - %d', $k, $k, $v);
            }

            foreach ($fort_damage_apply_bind_arr AS $k => $v) {
                $bind_str .= sprintf(', %s = %s - %d', $k, $k, $v);
            }

            $this->PgGame->query('UPDATE position_point SET status = $1'. $bind_str. ' WHERE posi_pk = $2', ['N', $_posi_pk]);
        } else if ($_type == 'raid_npc') {
            $bind_str = '';

            foreach ($army_damage_apply_bind_arr AS $k => $v) {
                $bind_str .= sprintf(', %s = %s - %d', $k, $k, $v);
            }

            foreach ($fort_damage_apply_bind_arr AS $k => $v) {
                $bind_str .= sprintf(', %s = %s - %d', $k, $k, $v);
            }

            $this->PgGame->query('UPDATE raid_troop SET last_up_dt = now()'. $bind_str. ' WHERE raid_troo_pk = $1', [$_troo_pk]);
        } else if ($_type == 'territory_lord') {
            // 병력
            $bind_str = '';
            $bind_strCurr = '';
            $arr_ally_bind = [];
            $log_description = '';

            // 내 병력정보 정보
            $this->PgGame->query('SELECT * FROM army WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $my_army_info = $this->PgGame->row;

            // 동맹 병력 정보
            $ally_army_arr = [];
            if (COUNT($_ally_troop) > 0) {
                foreach ($_ally_troop AS $k1 => $v1) {
                    foreach ($v1 AS $k2 => $v2) {
                        $ally_army_arr[$k1]['troo_pk'] = $v1['troo_pk'];
                        $ally_army_arr[$k1]['posi_pk'] = $v1['src_posi_pk'];
                        if (str_starts_with($k2, 'army_')) {
                            $ally_army_arr[$k1][substr($k2, 5)] = $v2;
                        }
                    }
                }
            }

            // 동맹군 처리
            foreach ($army_damage_apply_bind_arr AS $k => $v) {
                if ($my_army_info[$k] < $v && COUNT($_ally_troop) > 0) {
                    // 내꺼 차감
                    $desc_value = 0;
                    $bind_str .= sprintf(', %s = %s - %d', $k, $k, $my_army_info[$k]);
                    $bind_strCurr .= ', ' . $k;
                    $log_description .= "{$_M_ARMY_C[$k]['m_army_pk']}[$my_army_info[$k]];";

                    $desc_value += $my_army_info[$k];
                    // 부상병
                    $army_medical_apply_bind_arr[$k] = $my_army_info[$k];

                    // 동맹군 차감
                    foreach ($ally_army_arr AS $k1 => $v1) {
                        if ($v1[$k] > 0) {
                            $remain = $v - $desc_value;
                            $arr_ally_bind[$k1]['troo_pk'] = $v1['troo_pk'];
                            $arr_ally_bind[$k1]['posi_pk'] = $v1['posi_pk'];
                            if ($v1[$k] > $remain) {
                                $arr_ally_bind[$k1]['bindsrt'] .= sprintf(', army_%s = army_%s - %d', $k, $k, $remain);
                                $arr_ally_bind[$k1]['bindStrCurr'] .= ', army_' . $k;
                                // 부상병
                                $arr_ally_bind[$k1]['armyMedicalApplyBindArr'][$k] = $remain;
                                $arr_ally_bind[$k1]['log_ally_description'] .= "{$_M_ARMY_C[$k]['m_army_pk']}[$remain];";
                                break;
                            } else {
                                $arr_ally_bind[$k1]['bindsrt'] .= sprintf(', army_%s = 0', $k);
                                $arr_ally_bind[$k1]['bindStrCurr'] .= ', army_' . $k;
                                $desc_value += $v1[$k];
                                // 부상병
                                $arr_ally_bind[$k1]['armyMedicalApplyBindArr'][$k] = $v1[$k];
                                $arr_ally_bind[$k1]['log_ally_description'] .= "{$_M_ARMY_C[$k]['m_army_pk']}[$v1[$k]];";
                            }
                        }
                    }
                } else {
                    $bind_str .= sprintf(', %s = %s - %d', $k, $k, $v);
                    $bind_strCurr .= ', ' . $k;
                    $log_description .= "{$_M_ARMY_C[$k]['m_army_pk']}[$v];";
                }
            }

            // 내 부대 update
            $this->PgGame->query('SELECT last_update_dt ' . $bind_strCurr . ' FROM army WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $curr_row = $this->PgGame->row;
            $this->PgGame->query('UPDATE army SET last_update_dt = now() '. $bind_str. ' WHERE posi_pk = $1', [$_posi_pk]);
            // 로그
            if ($log_description !== '') {
                $log_current = '';
                if ($curr_row) {
                    foreach($curr_row AS $k => $v) {
                        if (isset($_M_ARMY_C[$k])) {
                            $log_current .= "{$_M_ARMY_C[$k]['m_army_pk']}[$v];";
                        }
                    }
                }
                $this->Log->setArmy(null, $_posi_pk, 'decrease_battle', "curr[$log_current];update[$log_description];");
            }

            // 부상병 처리
            $abandon_army = false;
            $injury_army = $this->Medical->setInjuryArmy($_posi_pk, $army_medical_apply_bind_arr, $abandon_army);

            // 동맹 부대 update
            $alli_injury_army = [];
            foreach($arr_ally_bind AS $k => $v) {
                $this->PgGame->query('SELECT troo_pk' . $v['bindStrCurr'] . ' FROM troop WHERE troo_pk = $1', [$v['troo_pk']]);
                $this->PgGame->fetch();
                $curr_row = $this->PgGame->row;
                $this->PgGame->query('UPDATE troop SET status = $2 '. $v['bindsrt']. ' WHERE troo_pk = $1', [$v['troo_pk'], 'C']);
                // 로그
                if ($v['log_ally_description'] !== '') {
                    $log_current = '';
                    if ($curr_row) {
                        foreach($curr_row AS $k2 => $v2) {
                            if (isset($_M_ARMY_C[$k2])) {
                                $log_current .= "{$_M_ARMY_C[$k2]['m_army_pk']}[$v2];";
                            }
                        }
                    }
                    $this->Log->setArmy(null, $v['posi_pk'], 'decrease_battle_ally', 'curr['.$log_current . '];update['.$v['log_ally_description'].'];');
                }

                // 부상병 처리
                if (isset($v['armyMedicalApplyBindArr'])) {
                    $ally_abandon_army = false;
                    $ret = $this->Medical->setInjuryArmy($v['posi_pk'], $v['armyMedicalApplyBindArr'], $ally_abandon_army);
                    if ($ret) {
                        foreach($injury_army AS $k1 => $v1) {
                            if ($ret[$k1]) {
                                $injury_army[$k1] += $ret[$k1];
                            }
                        }

                        foreach($ret AS $k2 => $v2) {
                            $alli_injury_army[$k2] = $v2;
                        }

                        if ($alli_injury_army) {
                            $alli_posi = $this->Troop->getPositionInfo($v['posi_pk']);
                            $alli_lord_pk = $alli_posi['lord_pk'];
                            $alli_posi_name = $this->Troop->getPositionName($v['posi_pk']);

                            $my_posi_name = $this->Troop->getPositionName($_posi_pk, null, true);

                            $this->injuryReport($alli_lord_pk, $_posi_pk, $my_posi_name, $v['posi_pk'], $alli_posi_name, $alli_injury_army, []);
                        }
                    }
                }

                // 회군
                $this->PgGame->query('SELECT troo_pk FROM troop WHERE troo_pk = $1 AND army_worker = 0 AND army_infantry = 0 AND army_pikeman = 0 AND army_scout = 0 AND army_spearman = 0 AND army_armed_infantry = 0 AND army_archer = 0 AND army_horseman = 0 AND army_armed_horseman = 0 AND army_transporter = 0 AND army_bowman = 0 AND army_battering_ram = 0 AND army_catapult = 0 AND army_adv_catapult = 0', Array($v['troo_pk']));
                $troo_pk = $this->PgGame->fetchOne();
                if (isset($troo_pk)) {
                    $this->Troop->setStatusRecall($troo_pk);
                }
            }

            // 갱신
            $this->Army->get($_posi_pk, null, $_lord_pk);

            // 방어시설
            $bind_str = '';
            $log_fort_description = '';
            $this->PgGame->query('SELECT trap, abatis, tower FROM fortification WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $curr_row = $this->PgGame->row;
            foreach ($fort_damage_apply_bind_arr AS $k => $v) {
                $bind_str .= sprintf(', %s = %s - %d', $k, $k, $v);
                $log_fort_description.= "{$_M_FORT_C[$k]['m_fort_pk']}[$v]";
            }

            // 설치 공간 update
            $this->PgGame->query('UPDATE fortification SET last_update_dt = now() '. $bind_str. ' WHERE posi_pk = $1', [$_posi_pk]);
            $this->FigureReCalc->wallFort($_posi_pk);

            // 로그
            if ($log_fort_description !== '') {
                $log_current = '';
                if ($curr_row) {
                    foreach($curr_row AS $k => $v) {
                        if (isset($_M_FORT_C[$k])) {
                            $log_current .= "{$_M_FORT_C[$k]['m_fort_pk']}[$v];";
                        }
                    }
                }
                $this->Log->setFortification(null, $_posi_pk, 'decrease_battle_fort', "curr[$log_current];update[$log_description];");
            }

            // 갱신
            $this->Fortification->get($_posi_pk, null, $_lord_pk);
            //echo "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++$ret-$_posi_pk+$_lord_pk+";
        } else if ($_type == 'troop') {
            $origin_army_pop = $this->Troop->getArmyPop($army_origin_arr);
            $shift_army_pop = $this->Troop->getArmyPop($army_shift_arr, $_posi_pk);

            $_result = ['capacity' => $shift_army_pop['capacity'], 'halfdamage' => false];

            // 환산병력기준 50% 이상 피해여부
            if ($origin_army_pop['need_population']/2 >= $shift_army_pop['need_population']) {
                $_result['halfdamage'] = true;
            }

            // triptime 재계산
            $hero_pk = null;
            if ($_troop['captain_hero_pk']) {
                $hero_pk = $_troop['captain_hero_pk'];
            }

            $triptime = $this->Troop->getMoveTime($_posi_pk, $_troop['cmd_type'], $_troop['distance'], $shift_army_pop, $hero_pk);
            //$triptime = $Troop->getMoveTime($_posi_pk, $_troop['cmd_type'], $_troop['distance'], $shift_army_pop);

            $bind_str = '';

            // 자원유실 처리
            $need_capacity = $_troop['reso_gold']+$_troop['reso_food']+$_troop['reso_horse']+$_troop['reso_lumber']+$_troop['reso_iron'];
            if ($need_capacity > 0 && $_result['capacity'] < $need_capacity) {
                $decrease_order = ['gold', 'iron', 'lumber', 'horse', 'food'];
                $decrease_value = ['gold' => 0, 'iron' => 0, 'lumber' => 0, 'horse' => 0, 'food' => 0];

                foreach ($decrease_order AS $v) {
                    $this_value = $_troop['reso_'. $v];

                    // 남은 공간이 없을 때 전체 유실
                    if ($_result['capacity'] <= 0) {
                        $decrease_value[$v] = $this_value;
                    } else if ($_result['capacity'] >= $this_value) {
                        // 남은 공간이 더 많을 때
                        $_result['capacity'] -= $this_value;
                    } else {
                        // 남은 공간이 부족할 때
                        $decrease_value[$v] = $this_value - $_result['capacity'];
                        $_result['capacity'] = 0;
                    }
                    $bind_str .= sprintf(', reso_%s = reso_%s - %d', $v, $v, $decrease_value[$v]);
                }
            }

            foreach ($army_damage_apply_bind_arr AS $k => $v) {
                $bind_str .= sprintf(', %s = %s - %d', $k, $k, $v);
            }

            $this->PgGame->query('UPDATE troop SET triptime = $1, hour_food = $2 '. $bind_str. ' WHERE troo_pk = $3', [intval($triptime), intval($shift_army_pop['need_food']), $_troo_pk]);
            // 부상병 처리
            $abandon_army = false;
            $injury_army = $this->Medical->setInjuryArmy($_posi_pk, $army_medical_apply_bind_arr, $abandon_army);

            /*foreach ($fort_damage_apply_bind_arr AS $k => $v) {
                $bind_str .= sprintf(', %s = %s - %d', $k, $k, $v);
            }*/
        }

        // 방어시설
        if ($_troop && isset($_troop['valley_posi_pk'])) {
            $bind_str = '';
            $log_army_desc = '';
            $this->PgGame->query('SELECT trap, abatis, tower FROM fortification_valley WHERE posi_pk = $1', [$_troop['valley_posi_pk']]);
            $this->PgGame->fetch();
            $curr_row = $this->PgGame->row;
            foreach ($fort_damage_apply_bind_arr AS $k => $v) {
                $bind_str .= sprintf(', %s = %s - %d', $k, $k, $v);
                $log_army_desc .= $k . '[curr['.$curr_row[$k] . '];update['.$v.'];];';
                // 설치 공간 update
            }

            if ($bind_str !== '') {
                $this->PgGame->query('UPDATE fortification_valley SET last_update_dt = now() '. $bind_str. ' WHERE posi_pk = $1', [$_troop['valley_posi_pk']]);
            }

            // 로그
            if ($log_army_desc) {
                $this->Log->setFortification(null, $_troop['valley_posi_pk'], 'decr_fort_battle', $log_army_desc);
            }
        }

        return ['injury_army' => $injury_army, 'dead_army' => $army_damage_arr, 'abandon_army' => $abandon_army];
    }


    // 부상병 리포트
    function injuryReport($_lord_pk, $_battle_posi_pk, $_battle_position_name, $_army_posi_pk, $_army_position_name, $_injury_army, $_injury_heroes): true
    {
        $this->classReport();
        // 보고서
        $z_content = [];

        $z_content['injury_army'] = $_injury_army;
        $z_content['injury_heroes'] = $_injury_heroes;
        $z_content['battle_position_name'] = $_battle_position_name;
        $z_content['army_position_name'] = $_army_position_name;

        // from & to
        $z_from = ['posi_pk' => $_army_posi_pk, 'posi_name' => $_army_position_name];
        $z_to = ['posi_pk' => $_battle_posi_pk, 'posi_name' => $_battle_position_name];

        // title & summary
        $this->Report->setReport($_lord_pk, 'misc', 'injury_army_trans', $z_from, $z_to, '', '', json_encode($z_content));

        return true;
    }

    // 이벤트 보상 버프 외교서신
    function eventBuffLetter($_lord_pk, $_buff_pk): true
    {
        global $Letter;

        $letter = [];
        $letter['type'] = 'S';
        $letter['title'] = '[이벤트] 용감한 형제들 이벤트 버프가 적용되었습니다.';
        $buff_text = '';
        if ($_buff_pk == 500505) {
            $buff_text = '훈련 시간 20% 단축';
        } else if ($_buff_pk == 500506) {
            $buff_text = '모든 자원 생산량 15% 증가';
        } else if ($_buff_pk == 500507) {
            $buff_text = '사망자 치료 10% 추가';
        }

        $letter['content'] = <<<EOF
용감한 형제들이여~

황건적의 습격을 막아주어 나라와 백성의 평안을 되찾을 수 있었소!

용감한 형제에게 황건적 습격 승리의 보상으로

지금부터 2시간 동안  “{$buff_text}” 버프가 적용되니 오늘도 건투하시오!
EOF;

        $Letter->sendLetter(ADMIN_LORD_PK, [$_lord_pk], $letter, true, 'Y');

        return true;
    }
}