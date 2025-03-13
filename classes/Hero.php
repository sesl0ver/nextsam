<?php

class Hero
{
    protected Session $Session;
    protected Pg $PgGame;

    protected i18n $i18n;
    protected Bdic $Bdic;
    protected Effect $Effect;
    protected FigureReCalc $FigureReCalc;

    protected GoldPop $GoldPop;
    protected Timer $Timer;
    protected HeroSkill $HeroSkill;
    protected Lord $Lord;
    protected Quest $Quest;
    protected Report $Report;
    protected Troop $Troop;
    protected Resource $Resource;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->i18n = i18n::getInstance();
    }

    function classTimer(): void
    {
        if (! isset($this->Timer)) {
            $this->Timer = new Timer($this->Session, $this->PgGame);
        }
    }

    function classBdic(): void
    {
        if (! isset($this->Bdic)) {
            $this->classResource();
            $this->classGoldPop();
            $this->Bdic = new Bdic($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classLog(): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    function classLord(): void
    {
        if (! isset($this->Lord)) {
            $this->Lord = new Lord($this->Session, $this->PgGame);
        }
    }

    function classReport(): void
    {
        if (! isset($this->Report)) {
            $this->Report = new Report($this->Session, $this->PgGame);
        }
    }

    function classHeroSkill(): void
    {
        if (! isset($this->HeroSkill)) {
            $this->HeroSkill = new HeroSkill($this->Session, $this->PgGame);
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
            $this->classGoldPop();
            $this->classResource();
            $this->FigureReCalc = new FigureReCalc($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classEffect(): void
    {
        if (! isset($this->Effect)) {
            $this->classGoldPop();
            $this->classResource();
            $this->classFigureReCalc();
            $this->Effect = new Effect($this->Session, $this->PgGame, $this->Resource, $this->GoldPop, $this->FigureReCalc);
        }
    }

    function classQuest(): void
    {
        if (! isset($this->Quest)) {
            $this->Quest = new Quest($this->Session, $this->PgGame);
        }
    }

    function classTroop(): void
    {
        if (! isset($this->Troop)) {
            $this->Troop = new Troop($this->Session, $this->PgGame);
        }
    }

    function getMasterHeroPk ($_hero_pk): mixed
    {
        $this->PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        return $this->PgGame->fetchOne();
    }

    function getFreeMasterHeroPk ($_hero_free_pk): mixed
    {
        $this->PgGame->query('SELECT hero.m_hero_pk FROM hero_free left join hero on hero_free.hero_pk = hero.hero_pk WHERE hero_free.hero_free_pk = $1', [$_hero_free_pk]);
        return $this->PgGame->fetchOne();
    }

    function getRandomLevel($_acquired_type, $_lord_level): int|string|null
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_acquired_level']);

        if ($_acquired_type == 'RECEPTIONHALL') {
            $_acquired_type = 'FREE';
        }
        $range_arr = $_M['HERO_ACQUIRED_LEVEL'][$_acquired_type];
        $range_prev = 1;
        $add_rate = 0;
        switch($_acquired_type) {
            case 'FREE':
                $add_rate = 1.659;
                break;
            case 'ENCOUNT_SPECIAL':
            case 'ENCOUNT_ITEM':
            case 'ENCOUNT':
                $add_rate = 11.428;
                break;
        }
        if (! Decimal::set($add_rate)->eq(0)) {
            $range_arr[$_lord_level]['rate'] = Decimal::set($range_arr[$_lord_level]['rate'])->plus($add_rate)->getValue();
            $range_arr[$_lord_level]['recalc_rate'] = Decimal::set($add_rate)->mul(10000)->plus($range_arr[$_lord_level]['recalc_rate'])->getValue();
        }
        $range_random_key = rand(1, 1000000); // 백만
        $range_select = null;
        foreach ($range_arr as $k => $v) {
            if ($v['recalc_rate'] == 0) {
                continue;
            }
            $next = $range_prev + $v['recalc_rate'];
            if ($range_random_key >= $range_prev && $range_random_key <= $next) {
                $range_select = $k;
                break;
            }
            $range_prev = $next;
        }
        return $range_select;
    }

    function getRandomRare($_level, $_acquired_type = 'NORMAL'): int|string|null
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_acquired_rare']);

        $range_arr = &$_M['HERO_ACQUIRED_RARE'][$_acquired_type][$_level];
        $range_prev = 1;
        $range_select = null;
        $range_random_key = rand(1, 100000000); // 일억
        foreach ($range_arr as $k => $v) {
            if ($v['recalc_rate'] == 0) {
                continue;
            }
            $next = Decimal::set($range_prev)->plus($v['recalc_rate'])->getValue();
            if (Decimal::set($range_random_key)->gte($range_prev) && Decimal::set($range_random_key)->lte($next)) {
                $range_select = $k;
                break;
            }
            $range_prev = $next;
        }
        if(($_acquired_type == 'GACHAPON_WOMAN') && $range_select == 2) {
            $range_select = 1;
        }
        return $range_select;
    }

    function getRandomPlusStat(): int|string|null
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_acquired_plusstat']);
        $range_arr = &$_M['HERO_ACQUIRED_PLUSSTAT'];
        $range_prev = 1;
        $range_select = null;
        $range_random_key = rand(1, 100000); // 십만
        foreach ($range_arr as $k => $v) {
            if ($v['recalc_rate'] == 0) {
                continue;
            }
            $next = Decimal::set($range_prev)->plus($v['recalc_rate'])->getValue();
            if (Decimal::set($range_random_key)->gte($range_prev) && Decimal::set($range_random_key)->lte($next)) {
                $range_select = $k;
                break;
            }
            $range_prev = $next;
        }

        return $range_select;
    }

    // 사용안함
    /*function getRandomSkill($_level)
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_acquired_skill']);
        $range_arr = &$_M['HERO_ACQUIRED_SKILL'][$_level];
        $range_prev = 1;
        $range_select = null;
        $range_random_key = rand(1, 100000); // 십만
        foreach ($range_arr as $v) {
            if ($v['recalc_rate'] == 0) {
                continue;
            }
            $next = Decimal::set($range_prev)->plus($v['recalc_rate'])->getValue();
            if (Decimal::set($range_random_key)->gte($range_prev) && Decimal::set($range_random_key)->lte($next)) {
                $range_select = $v['skill_count'];
                break;
            }
            $range_prev = $next;
        }
        return $range_select;
    }*/

    function getRandomEnchantPlusStat(): int|string|null
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_acquired_enchant_plusstat']);
        $range_arr = &$_M['HERO_ACQUIRED_ENCHANT_PLUSSTAT'];
        $range_prev = 1;
        $range_select = null;
        $range_random_key = rand(1, 10000000); // 천만
        foreach ($range_arr as $k => $v) {
            if ($v['recalc_rate'] == 0) {
                continue;
            }
            $next = Decimal::set($range_prev)->plus($v['recalc_rate'])->getValue();
            if (Decimal::set($range_random_key)->gte($range_prev) && Decimal::set($range_random_key)->lte($next)) {
                $range_select = $k;
                break;
            }
            $range_prev = $next;
        }
        return $range_select;
    }

    function getRandomHero($_level, $_rare, $_m_hero_base_pk, $_forces) // , $_over_type = 'N', $_new_gachan = 'N', $_yn_modifier = 'N'
    {
        global $_M, $NsGlobal, $_not_m_hero_base_list;
        $NsGlobal->requireMasterData(['hero', 'hero_base']);

        $m_hero = $_M['HERO']; // a
        $m_hero_base = $_M['HERO_BASE']; // b
        if (isset($_m_hero_base_pk)) {
            $m_hero = array_filter($m_hero, function ($m) use ($_m_hero_base_pk) {
                return $m['m_hero_base_pk'] == $_m_hero_base_pk;
            });
        }

        if (isset($_level)) {
            $m_hero = array_filter($m_hero, function ($m) use ($_level) {
                return $m['level'] == $_level;
            });
        }

        // base_pk를 지정했다면 진영은 무시함.
        if (! isset($_m_hero_base_pk) && isset($_forces)) {
            $m_hero_base = array_filter($m_hero_base, function ($m) use ($_forces) {
                return $m['forces'] == $_forces;
            });
        }

        // base_pk를 지정했다면 레어도는 무시함.
        if (! isset($_m_hero_base_pk) && isset($_rare)) {
            $m_hero_base = array_filter($m_hero_base, function ($m) use ($_rare) {
                return $m['rare_type'] == $_rare;
            });
        }

        if (is_array($_not_m_hero_base_list) && count($_not_m_hero_base_list) > 0) {
            $m_hero_base = array_filter($m_hero_base, function ($m) use ($_not_m_hero_base_list) {
                return ! in_array($m['m_hero_base_pk'], $_not_m_hero_base_list);
            });
        }

        // TODO 이전에 있던 new_gacha, modifier, over_rank 는 제외되었으므로 차후 필요시 코드 처리가 필요함.

        // 정리된 마스터데이터 기반으로 후보 영웅을 추려냄.
        $m_hero_base_pks = array_map(function ($m) { return $m['m_hero_base_pk']; }, $m_hero_base);
        $m_hero = array_filter($m_hero, function ($m) use ($m_hero_base_pks) {
            return in_array($m['m_hero_base_pk'], $m_hero_base_pks);
        });

        // 개수 제한
        /*$this->PgGame->query('UPDATE m_hero SET left_count = left_count - 1 WHERE m_hero_pk = $1', [$m_hero['m_hero_pk']]);
        if ($this->PgGame->getAffectedRows() != 1) {
            return false;
        }*/

        shuffle($m_hero); // 섞어주고
        $m_hero = array_shift($m_hero); // 첫번째 영웅

        if (is_array($_not_m_hero_base_list)) {
            if (in_array($m_hero['m_hero_base_pk'], $_not_m_hero_base_list)) {
                return false;
            } else {
                $_not_m_hero_base_list[] = $m_hero['m_hero_base_pk'];
            }
        }

        return $m_hero['m_hero_pk'] ?? false;
    }

    function setFreeHeroCreate($_m_hero_pk, $_plusstat, $_skill, $_create_reason = '-'): false|int
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero', 'hero_base', 'hero_acquired_plusstat', 'hero_acquired_skill', 'hero_skill_exp']);

        $m_hero = $_M['HERO'][$_m_hero_pk];
        if (! isset($m_hero)) {
            return false;
        }
        $m_hero_base = $_M['HERO_BASE'][$m_hero['m_hero_base_pk']];
        if (! isset($m_hero_base)) {
            return false;
        }
        $stat_types = ['leadership', 'mil_force', 'intellect', 'politics', 'charm'];
        $stat_arr = [];
        foreach ($stat_types as $type) {
            $stat_arr[$type] = $m_hero[$type];
        }
        $skill_arr = [];
        for ($i = 1; $i < 9; $i++) {
            $skill_arr["m_hero_skil_pk_$i"] = $m_hero_base["m_hero_skil_pk_$i"];
        }
        $level = $m_hero['level'];
        $rare_type = $m_hero_base['rare_type'];
        $over_type = $m_hero_base['over_type'];

        // 추가 능력치 처리
        $plus_stat_arr = ['leadership' => 0, 'mil_force' => 0, 'intellect' => 0, 'politics' => 0, 'charm' => 0];
        $m = &$_M['HERO_ACQUIRED_PLUSSTAT'][$_plusstat];
        $d = ['leadership', 'mil_force', 'intellect', 'politics', 'charm'];
        shuffle($d);
        if ($m['plus1']) {
            $plus_stat_arr[$d[0]] = $m['plus1'];
        }
        if ($m['plus2']) {
            $plus_stat_arr[$d[1]] = $m['plus2'];
        }
        if ($m['plus3']) {
            $plus_stat_arr[$d[2]] = $m['plus3'];
        }
        // 기술 선택
        $d = [];
        for ($i = 0, $i_l = COUNT($skill_arr); $i < $i_l; $i++) {
            $k = 'm_hero_skil_pk_'. ($i + 1);
            if ($skill_arr[$k]) {
                $d[] = $k;
            }
        }
        $possible_skill_count = COUNT($d);
        shuffle($d);
        $tech_arr = ['m_hero_skil_pk_1' => null, 'm_hero_skil_pk_2' => null, 'm_hero_skil_pk_3' => null, 'm_hero_skil_pk_4' => null];
        for ($i = 0; $i < $_skill && $i < $possible_skill_count; $i++) {
            $tech_arr['m_hero_skil_pk_'. ($i+1)] = $skill_arr[$d[$i]];
        }
        if ($over_type == 'Y') {
            $yn_trade = 'Y';
            $skill_exp = $_M['HERO_SKILL_EXP'][6]['exp'];
        } else {
            $yn_trade = 'N';
            $skill_exp = 0;
        }
        // 일단 재야 영웅으로 등록
        $query_params = [
            $_m_hero_pk, 'N', $level, $rare_type, 0, 70, 100,
            $stat_arr['leadership'], 0, $plus_stat_arr['leadership'],
            $stat_arr['mil_force'], 0, $plus_stat_arr['mil_force'],
            $stat_arr['intellect'], 0, $plus_stat_arr['intellect'],
            $stat_arr['politics'], 0, $plus_stat_arr['politics'],
            $stat_arr['charm'], 0, $plus_stat_arr['charm'],
            $tech_arr['m_hero_skil_pk_1'], $tech_arr['m_hero_skil_pk_2'], $tech_arr['m_hero_skil_pk_3'], $tech_arr['m_hero_skil_pk_4'],
            $yn_trade, 0, 0, $_create_reason, $skill_exp
        ];
        $this->PgGame->query('INSERT INTO hero
(m_hero_pk, status, level, rare_type, enchant, loyalty, hp,
 leadership_basic, leadership_enchant, leadership_plusstat,
 mil_force_basic, mil_force_enchant, mil_force_plusstat,
 intellect_basic, intellect_enchant, intellect_plusstat,
 politics_basic, politics_enchant, politics_plusstat,
 charm_basic, charm_enchant, charm_plusstat,
 m_hero_skil_pk_1, m_hero_skil_pk_2, m_hero_skil_pk_3, m_hero_skil_pk_4,
 yn_trade, allow_trade_cnt, spend_trade_cnt, create_reason, skill_exp, create_dt
) VALUES (
 $1, $2, $3, $4, $5, $6, $7,
 $8, $9, $10,
 $11, $12, $13,
 $14, $15, $16,
 $17, $18, $19,
 $20, $21, $22,
 $23, $24, $25, $26,
 $27, $28, $29, $30, $31, now()
)', $query_params);

        return $this->PgGame->currSeq('hero_hero_pk_seq');
    }

    // 20230901 신규 영웅 뽑기 (10연차 등에 사용)
    function getRandomHeroPickup($rare, $level = 1, $_options = []): int
    {
        // TODO 일단 오버랭크, 진노(컬렉션), 가챠 영웅은 제외 시킴. 차후 필요시 필터 추가하여 처리
        global $_M, $NsGlobal;

        // 옵션 처리
        try {
            if (! isset($rare)) { // 레어도는 무조건 지정 해주어야함.
                throw new Error('Not Found New Hero Rare Type.');
            }
            // 마스터데이터 참조
            $NsGlobal->requireMasterData(['hero', 'hero_base']);
            if (isset($_options['m_hero_base_pk'])) { // 이미 지정 했다면 레어도 무시
                $m_hero_base_pk = $_options['m_hero_base_pk'];
            } else { // 지정된 base_pk가 없다면 m_hero_base_pk 찾기
                // 군주 영웅은 제외.
                $m_hero_base = array_filter($_M['HERO_BASE'], function ($m) use ($NsGlobal) {
                    return ! in_array($m['m_hero_base_pk'], $NsGlobal->getExceptHeroBase());
                });

                // 지정된 레어도 필터링 (필수)
                $m_hero_base = array_filter($m_hero_base, function ($m) use ($rare) {
                    return $m['rare_type'] == $rare;
                });

                if (isset($_options['forces'])) { // 지정된 진영이 있다면
                    $m_hero_base = array_filter($m_hero_base, function ($m) use ($_options) {
                        return $m['forces'] == $_options['forces'];
                    });
                }

                shuffle($m_hero_base); // 무작위로 섞어서
                $select_hero_base = array_shift($m_hero_base); // 첫번째 영웅 선택
                $m_hero_base_pk = $select_hero_base['m_hero_base_pk'];
            }

        } catch (Throwable $e) {
            throw new ErrorHandler('error', $e->getMessage(), true);
        }

        // 선택된 base_pk를 가지고 가져오기
        $m_hero = array_filter($_M['HERO'], function ($m) use ($m_hero_base_pk, $level) {
            return $m['m_hero_base_pk'] == $m_hero_base_pk && $m['level'] == $level;
        });

        shuffle($m_hero);

        return array_shift($m_hero)['m_hero_pk'];
    }

    // 20230901 여러 영웅 등록 (10연차 등에 사용)
    public function createFreeHeroMultiple(array $_m_hero_pks, $_create_reason = '-'): array|string
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero', 'hero_base', 'hero_acquired_plusstat', 'hero_acquired_skill', 'hero_skill_exp', 'officer']);

        $query_data = [];
        foreach ($_m_hero_pks as $m_hero_pk) {
            $m_hero = $_M['HERO'][$m_hero_pk];
            $m_hero_base = $_M['HERO_BASE'][$m_hero['m_hero_base_pk']];

            // 랜덤 보너스 능력치
            $m_plus_stat = &$_M['HERO_ACQUIRED_PLUSSTAT'][$this->getRandomPlusstat()];
            $plus_stat_arr = ['leadership' => 0, 'mil_force' => 0, 'intellect' => 0, 'politics' => 0, 'charm' => 0];
            $d = ['leadership', 'mil_force', 'intellect', 'politics', 'charm'];
            shuffle($d);
            if ($m_plus_stat['plus1']) {
                $plus_stat_arr[$d[0]] = $m_plus_stat['plus1'];
            }
            if ($m_plus_stat['plus2']) {
                $plus_stat_arr[$d[1]] = $m_plus_stat['plus2'];
            }
            if ($m_plus_stat['plus3']) {
                $plus_stat_arr[$d[2]] = $m_plus_stat['plus3'];
            }

            // 오버타입에 따라 다름.
            $yn_trade = ($m_hero_base['over_type'] === 'Y') ? 'Y' : 'N';
            $skill_exp = ($m_hero_base['over_type'] === 'Y') ? $_M['HERO_SKILL_EXP'][6]['exp'] : 0;

            // hero 테이블
            $query_data[] = [$m_hero_pk, 'N', $m_hero['level'], $m_hero_base['rare_type'], 0, 70, 100,
                $m_hero['leadership'], 0, $plus_stat_arr['leadership'],
                $m_hero['mil_force'], 0, $plus_stat_arr['mil_force'],
                $m_hero['intellect'], 0, $plus_stat_arr['intellect'],
                $m_hero['politics'], 0, $plus_stat_arr['politics'],
                $m_hero['charm'], 0, $plus_stat_arr['charm'],
                $m_hero_base['m_hero_skil_pk_1'], $m_hero_base['m_hero_skil_pk_2'], $m_hero_base['m_hero_skil_pk_3'], $m_hero_base['m_hero_skil_pk_4'],
                $yn_trade, 0, 0, $_create_reason, $skill_exp];

            // my_hero 테이블
        }

        $i = 0;
        $params_data = [];
        $values_strings = [];
        foreach ($query_data as $data) {
            $values_data = [];
            foreach ($data as $v) {
                if ($v == '') {
                    $values_data[] = 'NULL';
                } else {
                    $i++;
                    $values_data[] = '$'.$i;
                    $params_data[] = $v;
                }

            }
            $values_data[] = 'now()';
            $values_strings[] = '(' . join(',', $values_data) . ')';
        }

        $values_string = join(',', $values_strings);

        $query_string = "INSERT INTO hero (m_hero_pk, status, level, rare_type, enchant, loyalty, hp,
 leadership_basic, leadership_enchant, leadership_plusstat, mil_force_basic, mil_force_enchant, mil_force_plusstat, intellect_basic, intellect_enchant, intellect_plusstat, politics_basic, politics_enchant, politics_plusstat, charm_basic, charm_enchant, charm_plusstat,
 m_hero_skil_pk_1, m_hero_skil_pk_2, m_hero_skil_pk_3, m_hero_skil_pk_4,
 yn_trade, allow_trade_cnt, spend_trade_cnt, create_reason, skill_exp, create_dt) VALUES $values_string RETURNING hero_pk, rare_type";

        $this->PgGame->query($query_string, $params_data);
        $this->PgGame->fetchAll();

        return $this->PgGame->rows;
    }

    public function createMyHeroMultiple (array $_hero_pks, string $_status, $_options = []): array
    {
        // TODO 관직을 지정하여 뽑을일은 없으니 m_offi_pk 는 skip
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['officer']);

        // 옵션 값 설정
        $lord_pk = $_options['lord_pk'] ?? $this->Session->lord['lord_pk'];
        $pickup_type = $_options['pickup_type'] ?? '';
        $type = $_options['type'] ?? '';
        $log_pickup_count = $_options['log_pickup_count'] ?? [];
        $log_pickup_pity = $_options['log_pickup_pity'] ?? [];
        $log_qbig_use = $_options['log_qbig_use'] ?? 0;

        // 능력 계산
        $hero_pks_string = join(',', $_hero_pks);
        $this->PgGame->query("SELECT leadership_basic, leadership_enchant, leadership_plusstat, leadership_skill,
                                                mil_force_basic, mil_force_enchant, mil_force_plusstat, mil_force_skill,
                                                intellect_basic, intellect_enchant, intellect_plusstat, intellect_skill,
                                                politics_basic, politics_enchant, politics_plusstat, politics_skill,
                                                charm_basic, charm_enchant, charm_plusstat, charm_skill, hero_pk, m_hero_pk, level FROM hero WHERE hero_pk IN ($hero_pks_string)");
        $this->PgGame->fetchAll();

        $i = 1;
        $params_data = [];
        $values_strings = [];
        $interval = 60 * 60 * 24 * 30;
        $pickup_heroes = [];
        foreach ($this->PgGame->rows as $row) {
            $values_data = [];
            $values_data[] = '$' . $i++;
            $values_data[] = '$' . $i++;
            $values_data[] = '$' . $i++;
            $values_data[] = "'I'";
            $values_data[] = "'N'";
            $values_data[] = "'N'";
            $values_data[] = "'N'";
            $values_data[] = '$' . $i++;
            $values_data[] = '$' . $i++;
            $values_data[] = '$' . $i++;
            $values_data[] = '$' . $i++;
            $values_data[] = '$' . $i++;
            $values_data[] = 'now()';
            $values_data[] = 'now()';
            $values_data[] = 'now()';
            $values_data[] = 'now()';
            $values_data[] = "now() + interval '$interval second'";

            $params_data[] = $row['hero_pk'];
            $params_data[] = $lord_pk;
            $params_data[] = $_status;
            $params_data[] = $row['leadership_basic'] + $row['leadership_enchant'] + $row['leadership_plusstat'] + $row['leadership_skill'];
            $params_data[] = $row['mil_force_basic'] + $row['mil_force_enchant'] + $row['mil_force_plusstat'] + $row['mil_force_skill'];
            $params_data[] = $row['intellect_basic'] + $row['intellect_enchant'] + $row['intellect_plusstat'] + $row['intellect_skill'];
            $params_data[] = $row['politics_basic'] + $row['politics_enchant'] + $row['politics_plusstat'] + $row['politics_skill'];
            $params_data[] = $row['charm_basic'] + $row['charm_enchant'] + $row['charm_plusstat'] + $row['charm_skill'];

            $values_strings[] = '(' . join(',', $values_data) . ')';
            $pickup_heroes[$row['hero_pk']] = $row;
        }

        $values_string = join(',', $values_strings);

        $query_string = "INSERT INTO my_hero (hero_pk, lord_pk, status, status_cmd, cmd_type, acquired_type, yn_lord,
leadership, mil_force, intellect, politics, charm, acquired_dt, last_appoint_dt, last_dismiss_dt, last_salary_dt, timedjob_dt) VALUES $values_string
RETURNING hero_pk, leadership, mil_force, intellect, politics, charm";

        $this->PgGame->query($query_string, $params_data);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;

        $this->classLog();

        $i = 0;
        foreach ($rows as $row) {
            $_log_pickup_count = $log_pickup_count[$i] ?? 0;
            $_log_pickup_pity = $log_pickup_pity[$i] ?? 'none';
            $log_description = "hero_pickup[pickup_type[$pickup_type],type[$type],pickup_count[$_log_pickup_count],pickup_pity[$_log_pickup_pity],log_qbig_use[$log_qbig_use]];";
            $this->Log->setHero($lord_pk, null, 'Regist', $row['hero_pk'], $_status, 'I', 'None', $log_description, $pickup_heroes[$row['hero_pk']]['m_hero_pk']);
            $pickup_heroes[$row['hero_pk']]['leadership'] = $row['leadership'];
            $pickup_heroes[$row['hero_pk']]['mil_force'] = $row['mil_force'];
            $pickup_heroes[$row['hero_pk']]['intellect'] = $row['intellect'];
            $pickup_heroes[$row['hero_pk']]['politics'] = $row['politics'];
            $pickup_heroes[$row['hero_pk']]['charm'] = $row['charm'];
            $i++;
        }

        return $pickup_heroes;
    }

    function getFreeHeroInfo($_hero_pk): bool|array
    {
        global $NsGlobal;
        $NsGlobal->requireMasterData(['hero', 'hero_base']);

        $this->PgGame->query('SELECT hero_pk, m_hero_pk, level, rare_type, enchant,
 leadership_basic, leadership_enchant, leadership_plusstat, leadership_skill,
 mil_force_basic, mil_force_enchant, mil_force_plusstat, mil_force_skill,
 intellect_basic, intellect_enchant, intellect_plusstat, intellect_skill,
 politics_basic, politics_enchant, politics_plusstat, politics_skill,
 charm_basic, charm_enchant, charm_plusstat, charm_skill,
 m_hero_skil_pk_1, m_hero_skil_pk_2, m_hero_skil_pk_3, m_hero_skil_pk_4, yn_trade,
 hero_exp, special_combi_cnt
FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }

        $this->PgGame->row['leadership'] = $this->PgGame->row['leadership_basic'] + $this->PgGame->row['leadership_enchant'] + $this->PgGame->row['leadership_plusstat'] + $this->PgGame->row['leadership_skill'];
        $this->PgGame->row['mil_force'] = $this->PgGame->row['mil_force_basic'] + $this->PgGame->row['mil_force_enchant'] + $this->PgGame->row['mil_force_plusstat'] + $this->PgGame->row['mil_force_skill'];
        $this->PgGame->row['intellect'] = $this->PgGame->row['intellect_basic'] + $this->PgGame->row['intellect_enchant'] + $this->PgGame->row['intellect_plusstat'] + $this->PgGame->row['intellect_skill'];
        $this->PgGame->row['politics'] = $this->PgGame->row['politics_basic'] + $this->PgGame->row['politics_enchant'] + $this->PgGame->row['politics_plusstat'] + $this->PgGame->row['politics_skill'];
        $this->PgGame->row['charm'] = $this->PgGame->row['charm_basic'] + $this->PgGame->row['charm_enchant'] + $this->PgGame->row['charm_plusstat'] + $this->PgGame->row['charm_skill'];

        return $this->PgGame->row;
    }

    // $_posi_pk 와 $_m_offi_pk, $_yn_lord 는 유저 가입시에만 사용하도록 한다. 자동 등용은 이것 뿐임.
    function setMyHeroCreate($_hero_pk, $_lord_pk, $_status, $_posi_pk = null, $_m_offi_pk = null, $_yn_lord = 'N', $get_type = ''): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['officer']);

        // 능력 계산
        $this->PgGame->query('SELECT leadership_basic+leadership_enchant+leadership_plusstat+leadership_skill AS leadership,
                                                mil_force_basic+mil_force_enchant+mil_force_plusstat+mil_force_skill AS mil_force,
                                                intellect_basic+intellect_enchant+intellect_plusstat+intellect_skill AS intellect,
                                                politics_basic+politics_enchant+politics_plusstat+politics_skill AS politics,
                                                charm_basic+charm_enchant+charm_plusstat+charm_skill AS charm FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $stat = $this->PgGame->row;
        if ($_m_offi_pk) {
            $m = &$_M['OFFI'][$_m_offi_pk];
            $stat['leadership'] += $m['stat_plus_leadership'];
            $stat['mil_force'] += $m['stat_plus_mil_force'];
            $stat['intellect'] += $m['stat_plus_intellect'];
            $stat['politics'] += $m['stat_plus_politics'];
            $stat['charm'] += $m['stat_plus_charm'];
            $_status = 'A';
        }

        $interval = 60 * 60 * 24 * 30;
        $this->PgGame->query("INSERT INTO my_hero
(hero_pk, lord_pk, m_offi_pk, posi_pk, status, status_cmd, cmd_type, acquired_type, yn_lord,
 leadership, mil_force, intellect, politics, charm, acquired_dt, last_appoint_dt, last_dismiss_dt, last_salary_dt, timedjob_dt
) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, now(), now(), now(), now(), now() + interval '$interval second')", [$_hero_pk, $_lord_pk, $_m_offi_pk, $_posi_pk, $_status, 'I', 'N', 'N', $_yn_lord,
            $stat['leadership'], $stat['mil_force'], $stat['intellect'], $stat['politics'], $stat['charm']]);
        if ($this->PgGame->getAffectedRows() > 0) {
            $add_query_string = ($_yn_lord == 'Y') ? ', loyalty = 100' : '';
            // 영웅기본 상태를 할당으로 변경
            $this->PgGame->query('UPDATE hero SET status= $1'. $add_query_string. ' WHERE hero_pk = $2', ['Y', $_hero_pk]);
            // 비영입 상태 영웅 갱신 - TODO 영웅 status도 단축어가 아닌 단어형태로 변경하자. 단축어 형태는 확장성이 너무 구림.
            if ($_status == 'C' || $_status == 'S' || $_status == 'V') {
                $this->setUnreadHeroCnt($_lord_pk);
                // $this->setUnreadOverrankHeroCnt($_lord_pk);
            }
            if ($_yn_lord == 'N') {
                $hero_info = $this->getMyHeroInfo($_hero_pk);
                // TODO 채팅 알림은 사용하지 않으므로 번역 제외
                $get_type_arr = [
                    'item' => '영웅 즉시 영입 아이템을 사용하여 ',
                    'adv_item' => '우수 영웅 즉시 영입 아이템을 사용하여 ',
                    'special' => '특수 영웅 즉시 영입 아이템을 사용하여',
                    'encounter' => '초빙을 통하여 ',
                    'bid' => '재야 영웅 입찰을 통하여 ',
                    'gach_500385' => '지휘 영웅 즉시 영입 아이템을 사용하여 ',
                    'gach_500386' => '용맹 영웅 즉시 영입 아이템을 사용하여 ',
                    'gach_500387' => '책략 영웅 즉시 영입 아이템을 사용하여 ',
                    'gach_500388' => '내정 영웅 즉시 영입 아이템을 사용하여 ',
                    'gach_500389' => '매혹 영웅 즉시 영입 아이템을 사용하여 ',
                    'gach_500390' => '최강 영웅 즉시 영입 아이템을 사용하여 ',
                    'powerful_excel' => '강화된 우수 영웅 즉시 영입 아이템을 사용하여 ',
                    'powerful_normal' => '강화된 영웅 즉시 영입 아이템을 사용하여 ',
                    'gach_500532' => '신규 최강 영웅 즉시 영입 아이템을 사용하여 ',
                    'plunder_item' => '황건적 포로 영웅 영입 아이템을 사용하여 ',
                    'special_excel' => '특별 영웅 즉시 영입 아이템을 사용하여',
                    'rare_box_5' => '5성 영웅 즉시 영입 아이템을 사용하여 ',
                    'rare_box_6' => '6성 영웅 즉시 영입 아이템을 사용하여 ',
                    'rare_box_7' => '7성 영웅 즉시 영입 아이템을 사용하여 ',
                    'suppress_item' => '토벌령 포로 영웅 영입 아이템을 사용하여 ',
                    'overrank' => '우수 오버랭크영웅 아이템을 사용하여 ',
                    'luxury_overrank' => '고급 오버랭크영웅 아이템을 사용하여 ',
                    'rare_overrank' => '희귀 오버랭크영웅 아이템을 사용하여 ',
                    'gach_500740' => '매혹영웅 아이템을 사용하여 ',
                    // 	'combination' => '조합을 통하여 ',
                ];
                //  // 레어 5 이상 또는 레벨 7 이상
                if (($hero_info['rare_type'] > 4) && strlen($get_type) > 0 && in_array($get_type, $get_type_arr)) {
                    $cancel = false;
                    // 오버랭크 상자는 레어 6 이상만 나오도록
                    if ($_M['HERO'][$hero_info['m_hero_pk']]['over_type'] == 'Y' && ($hero_info['rare_type'] < 6)) {
                        $cancel = true;
                    }
                    if (! $cancel) {
                        $NsGlobal->requireMasterData(['hero', 'hero_base']);
                        /*$this->PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', Array($this->Session->lord['lord_pk']));
                        $lord_name = $this->PgGame->fetchOne();*/
                        $lord_name = $this->Session->lord['lord_name']; // 어차피 당사자가 뽑는거라면 세션으로 처리하는게 낫다.
                        $howto = $get_type_arr[$get_type];
                        $push_message = "{$lord_name}님이 {$howto}Lv.{$hero_info['level']} {$_M['HERO_BASE'][$_M['HERO'][$hero_info['m_hero_pk']]['m_hero_base_pk']]['name']} 영웅 카드를 획득하였습니다. 축하합니다.";
                        // TODO 차후 채팅 구현 후 Push 구현까지 마친다음에 테스트 후 처리.
                        // $Chat->send_announce_system_about_hero($push_message);
                    }
                }
            }

            $this->classLog();
            $this->Log->setHero($_lord_pk, $_posi_pk, 'Regist', $_hero_pk, $_status, 'I', 'None', $get_type);
            return true;
        } else {
            return false;
        }
    }

    // status, status_cmd, cmd_type, timedjob_dt_ut, m_offi_pk 등만 구함 => 명령을 내리거나 배속, 강화 등 상태 변화 액션을 취할 때
    function getMyHeroStatus($_hero_pk): bool|array
    {
        $this->PgGame->query('SELECT t1.hero_pk, t1.m_offi_pk, t1.status, t1.status_cmd, t2.m_hero_pk, t2.enchant, t2.loyalty, t2.hp, 
 date_part(\'epoch\', t1.timedjob_dt)::integer AS timedjob_dt_ut, date_part(\'epoch\', t1.last_dismiss_dt)::integer AS last_dismiss_dt_ut, t1.cmd_type FROM my_hero AS t1, hero AS t2 WHERE t1.hero_pk = t2.hero_pk AND t1.hero_pk = $1', [$_hero_pk]);

        return (! $this->PgGame->fetch()) ? false : $this->PgGame->row;
    }

    function getMyHeroCapacity($_hero_pk): bool|array
    {
        $this->PgGame->query('SELECT t1.leadership, t1.mil_force, t1.intellect, t1.politics, t1.charm,
 t2.m_hero_skil_pk_1, t2.m_hero_skil_pk_2, t2.m_hero_skil_pk_3, t2.m_hero_skil_pk_4
FROM my_hero t1, hero t2 WHERE t1.hero_pk = t2.hero_pk AND t1.hero_pk = $1', [$_hero_pk]);
        if ($this->PgGame->fetch()) {
            return $this->PgGame->row;
        } else {
            return false;
        }
    }

    // getMyHeroes 에서와 동일한 수준에 정보가 필요. 일단은 신규 정보를 제공하는 측면에서 접근 하기 때문 , 아니면 업데이트
    function getMyHeroInfo($_hero_pk): bool|array
    {
        $this->PgGame->query("SELECT
 t1.hero_pk, t1.m_offi_pk, t1.posi_pk, t1.status, t1.status_cmd, t1.yn_lord, t1.leadership, t1.mil_force, t1.intellect, t1.politics, t1.charm,
 t2.m_hero_pk, t2.status AS m_status, t2.level, t2.rare_type, t2.enchant, t2.loyalty, t2.hp, t2.yn_trade,
 t2.leadership_basic, t2.leadership_enchant, t2.leadership_plusstat, t2.leadership_skill,
 t2.mil_force_basic, t2.mil_force_enchant, t2.mil_force_plusstat,  t2.mil_force_skill,
 t2.intellect_basic, t2.intellect_enchant, t2.intellect_plusstat, t2.intellect_skill,
 t2.politics_basic, t2.politics_enchant, t2.politics_plusstat,  t2.politics_skill,
 t2.charm_basic, t2.charm_enchant, t2.charm_plusstat, t2.charm_skill,
 t2.m_hero_skil_pk_1, t2.m_hero_skil_pk_2, t2.m_hero_skil_pk_3, t2.m_hero_skil_pk_4,
 date_part('epoch', t1.timedjob_dt)::integer AS timedjob_dt_ut,
 date_part('epoch', t1.last_dismiss_dt)::integer AS last_dismiss_dt_ut,
 t1.cmd_type,
 t2.skill_exp,
 t2.hero_exp, t2.special_combi_cnt,
 t3.slot_pk1, t3.m_hero_skil_pk1, t3.main_slot_pk1, t3.slot_pk2, t3.m_hero_skil_pk2, t3.main_slot_pk2,
 t3.slot_pk3, t3.m_hero_skil_pk3, t3.main_slot_pk3, t3.slot_pk4, t3.m_hero_skil_pk4, t3.main_slot_pk4,
 t3.slot_pk5, t3.m_hero_skil_pk5, t3.main_slot_pk5, t3.slot_pk6, t3.m_hero_skil_pk6, t3.main_slot_pk6,
 t1.group_type, t1.group_order
FROM my_hero AS t1, hero AS t2, getmyheroskillslot({$_hero_pk}) AS t3
WHERE t1.hero_pk = t2.hero_pk AND t1.hero_pk = $1", [$_hero_pk]);

        if ($this->PgGame->fetch()) {
            return $this->PgGame->row;
        } else {
            return false;
        }
    }

    function getMyHeroStatusText($hero_pk, $posi_pk, $status_cmd, $cmd_type): string
    {
        global $_M, $NsGlobal, $i18n;

        // TODO 텍스트 코드를 Locale 쪽으로 빼야함.
        $hero_status_cmd = [
            'I' => $i18n->t('standby'), // 대기
            'A' => $i18n->t('assign'), // 배속
            'C' => $i18n->t('command'), // 명령
            'T' => $i18n->t('injury'), // 부상
            'P' => $i18n->t('enhance') // 강화
        ];
        // TODO 차후 단어형태로 변경필요.
        $hero_cmd_type = [
            'None' => $i18n->t('none'), // 없음
            'Const' => $i18n->t('construction'), // '건설',
            'Encou' => $i18n->t('exploration'), // '탐색',
            'Invit' => $i18n->t('invitation'), // '초빙',
            'Techn' => $i18n->t('development'), // '개발',
            'Scout' => $i18n->t('reconnaissance'), // '정찰',
            'Trans' => $i18n->t('transport'), // '수송',
            'Reinf' => $i18n->t('support'), // '지원',
            'Attac' => $i18n->t('attack'), // '공격',
            'Preva' => $i18n->t('defense'), // '보급',
            'Camp' => $i18n->t('deployed'), // '주둔',
            'Recal' => $i18n->t('withdrawal'),// '회군'
        ];


        /*$hero_status_cmd = ['I' => 'Wait', 'A' => 'Assign', 'C' => 'Command', 'T' => 'Injury', 'P' => 'Enhance'];
        $hero_cmd_type = ['None' => 'None', 'Const' => 'Construct', 'Encou' => 'Search', 'Invit' => 'Invite', 'Techn' => 'Technique',
            'Scout' => 'Scout', 'Trans' => 'Transport', 'Reinf' => 'Reinforce', 'Attac' => 'Attack', 'Preva' => 'Supply',
            'Camp' => 'Camping', 'Recal' => 'Retreat'];*/

        $NsGlobal->requireMasterData(['building']);
        $status_text = $hero_status_cmd[$status_cmd];
        if ($status_cmd == 'A') {
            // 배속 중
            $this->PgGame->query('SELECT m_buil_pk FROM building_in_castle WHERE posi_pk = $1 AND assign_hero_pk = $2', [$posi_pk, $hero_pk]);
            $m_buil_pk = $this->PgGame->fetchOne();
            if ($m_buil_pk > 0) {
                $status_text .= " ({$this->i18n->t('build_title_' . $m_buil_pk)})";
            } else {
                $status_text .= " ({$this->i18n->t('build_title_201600')})"; // 건물 pk가 없으면 현재로서는 성벽 뿐.
            }
        } else if ($status_cmd == 'C') {
            // 명령 중
            if ($cmd_type == 'Const') {
                // 건설 중
                $this->PgGame->query('SELECT m_buil_pk FROM building_in_castle WHERE posi_pk = $1 AND buil_hero_pk = $2', [$posi_pk, $hero_pk]);
                $m_buil_pk = $this->PgGame->fetchOne();
                if ($m_buil_pk > 0) {
                    $status_text .= " ({$this->i18n->t('build_title_' . $m_buil_pk)})";
                } else {
                    $this->PgGame->query('SELECT m_buil_pk FROM building_out_castle WHERE posi_pk = $1 AND buil_hero_pk = $2', [$posi_pk, $hero_pk]);
                    $m_buil_pk = $this->PgGame->fetchOne();
                    if ($m_buil_pk > 0) {
                        $status_text .= " ({$this->i18n->t('build_title_' . $m_buil_pk)})";
                    }
                }
            } else {
                // 그 외
                $status_text .= " ({$hero_cmd_type[$cmd_type]})";
            }
        }
        return $status_text;
    }

    function getMyHeroList($_lord_pk, $_status_arr, $page, $order, $order_type, $list_num = null, $_for_combine = false, $_for_over_rank = false, $_posi_pk = null, $_no_over_rank = false): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero']);
        if (! $_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }
        $_add_query_string = "";
        if ($_for_combine) {
            $_add_query_string = " AND (t1.status = ANY ($2) OR (t1.status = 'A' AND t1.status_cmd = 'I') AND t2.level <= 19)";
        } else {
            $_add_query_string = " AND t1.status = ANY ($2)";
        }
        if ($_for_over_rank) {
            $_add_query_string.= " AND t3.over_type = 'Y' ";
        }
        if ($_no_over_rank) {
            $_add_query_string.= " AND t3.over_type = 'N' ";
        }
        if ($_posi_pk) {
            $_add_query_string.= " AND t1.posi_pk = '{$_posi_pk}' ";
        }

        $page = (INT)$page;
        $page = ($page < 1) ? 1 : $page;
        $order = preg_replace('/[^\w]/', '', strtolower($order));

        $list_num = ($list_num == null) ? HERO_LIST_PAGE_NUM : $list_num;
        $offset_start = ($page - 1) * $list_num;
        $limit = $list_num;

        if ($order == 'name') {
            $order_by = "t4.name::BYTEA {$order_type}, t2.rare_type ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($order == 'level') {
            $order_by = "t2.level {$order_type}, t2.rare_type ASC, t4.name::BYTEA ASC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($order == 'offi') {
            $order_type = ($order_type == 'DESC') ? 'ASC' : 'DESC';
            $order_by = "t1.m_offi_pk {$order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($order == 'leadership') {
            $order_by = "t1.leadership {$order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($order == 'mil_force') {
            $order_by = "t1.mil_force {$order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($order == 'intellect') {
            $order_by = "t1.intellect {$order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($order == 'politics') {
            $order_by = "t1.politics {$order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($order == 'charm') {
            $order_by = "t1.charm {$order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t2.hero_pk ASC";
        } else if ($order == 'loyalty') {
            $order_by = "t2.loyalty {$order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t2.hero_pk ASC";
        } else if ($order == 'posi_pk') {
            $order_by = "t1.posi_pk {$order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t2.hero_pk ASC";
        } else if ($order == 'infantry') {
            // 참고 URL http://stackoverflow.com/questions/1309624/simulating-mysqls-order-by-field-in-postgresql
            $order_type = ($order_type == 'DESC') ? 'ASC' : 'DESC';
            $order_by = "CASE WHEN t4.mil_aptitude_infantry = 'S' THEN 1 WHEN t4.mil_aptitude_infantry = 'A' THEN 2 WHEN t4.mil_aptitude_infantry = 'B' THEN 3 WHEN t4.mil_aptitude_infantry = 'C' THEN 4 WHEN t4.mil_aptitude_infantry = 'D' THEN 5 WHEN t4.mil_aptitude_infantry = 'E' THEN 6 ELSE 7 END {$order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($order == 'pikeman') {
            $order_type = ($order_type == 'DESC') ? 'ASC' : 'DESC';
            $order_by = "CASE WHEN t4.mil_aptitude_pikeman = 'S' THEN 1 WHEN t4.mil_aptitude_pikeman = 'A' THEN 2 WHEN t4.mil_aptitude_pikeman = 'B' THEN 3 WHEN t4.mil_aptitude_pikeman = 'C' THEN 4 WHEN t4.mil_aptitude_pikeman = 'D' THEN 5 WHEN t4.mil_aptitude_pikeman = 'E' THEN 6 ELSE 7 END {$order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($order == 'spearman') {
            $order_type = ($order_type == 'DESC') ? 'ASC' : 'DESC';
            $order_by = "CASE WHEN t4.mil_aptitude_spearman = 'S' THEN 1 WHEN t4.mil_aptitude_spearman = 'A' THEN 2 WHEN t4.mil_aptitude_spearman = 'B' THEN 3 WHEN t4.mil_aptitude_spearman = 'C' THEN 4 WHEN t4.mil_aptitude_spearman = 'D' THEN 5 WHEN t4.mil_aptitude_spearman = 'E' THEN 6 ELSE 7 END {$order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($order == 'archer') {
            $order_type = ($order_type == 'DESC') ? 'ASC' : 'DESC';
            $order_by = "CASE WHEN t4.mil_aptitude_archer = 'S' THEN 1 WHEN t4.mil_aptitude_archer = 'A' THEN 2 WHEN t4.mil_aptitude_archer = 'B' THEN 3 WHEN t4.mil_aptitude_archer = 'C' THEN 4 WHEN t4.mil_aptitude_archer = 'D' THEN 5 WHEN t4.mil_aptitude_archer = 'E' THEN 6 ELSE 7 END {$order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($order == 'horseman') {
            $order_type = ($order_type == 'DESC') ? 'ASC' : 'DESC';
            $order_by = "CASE WHEN t4.mil_aptitude_horseman = 'S' THEN 1 WHEN t4.mil_aptitude_horseman = 'A' THEN 2 WHEN t4.mil_aptitude_horseman = 'B' THEN 3 WHEN t4.mil_aptitude_horseman = 'C' THEN 4 WHEN t4.mil_aptitude_horseman = 'D' THEN 5 WHEN t4.mil_aptitude_horseman = 'E' THEN 6 ELSE 7 END {$order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($order == 'siege') {
            $order_type = ($order_type == 'DESC') ? 'ASC' : 'DESC';
            $order_by = "CASE WHEN t4.mil_aptitude_siege = 'S' THEN 1 WHEN t4.mil_aptitude_siege = 'A' THEN 2 WHEN t4.mil_aptitude_siege = 'B' THEN 3 WHEN t4.mil_aptitude_siege = 'C' THEN 4 WHEN t4.mil_aptitude_siege = 'D' THEN 5 WHEN t4.mil_aptitude_siege = 'E' THEN 6 ELSE 7 END {$order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else {
            $order_by = "t2.rare_type {$order_type}, t2.level DESC, t4.name ASC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        }

        $this->PgGame->query("SELECT
 t1.hero_pk, t4.name, t1.m_offi_pk, t1.status, t1.status_cmd, t1.yn_lord, t1.posi_pk, t1.leadership, t1.mil_force, t1.intellect, t1.politics, t1.charm,
 t2.m_hero_pk, t2.status AS m_status, t2.level, t2.rare_type, t2.enchant, t2.loyalty, t2.hp, t2.yn_trade, 
 t2.leadership_basic, t2.leadership_enchant, t2.leadership_plusstat, t2.leadership_skill,
 t2.mil_force_basic, t2.mil_force_enchant, t2.mil_force_plusstat,  t2.mil_force_skill,
 t2.intellect_basic, t2.intellect_enchant, t2.intellect_plusstat, t2.intellect_skill,
 t2.politics_basic, t2.politics_enchant, t2.politics_plusstat,  t2.politics_skill,
 t2.charm_basic, t2.charm_enchant, t2.charm_plusstat, t2.charm_skill, t3.over_type,
 t2.m_hero_skil_pk_1, t2.m_hero_skil_pk_2, t2.m_hero_skil_pk_3, t2.m_hero_skil_pk_4,
 date_part('epoch', t1.timedjob_dt)::integer AS timedjob_dt_ut,
 date_part('epoch', t1.last_dismiss_dt)::integer AS last_dismiss_dt, 
 t1.cmd_type,
 t2.skill_exp,
 t2.hero_exp, t2.special_combi_cnt, t3.acquire_exp, t3.need_exp, t4.forces,
 t1.group_type, t1.group_order, t2.yn_trade, t4.yn_new_gacha, t4.yn_modifier
FROM my_hero AS t1, hero AS t2, m_hero AS t3, m_hero_base AS t4
WHERE t1.hero_pk = t2.hero_pk AND t2.m_hero_pk = t3.m_hero_pk AND t3.m_hero_base_pk = t4.m_hero_base_pk AND t1.lord_pk = $1{$_add_query_string}
ORDER BY {$order_by}
LIMIT {$limit}
OFFSET {$offset_start}", [$_lord_pk, '{'. implode(',', $_status_arr). '}']);
        $this->PgGame->fetchAll();
        $heroes = $this->PgGame->rows;
        // skill 정보
        $hero_pks = [];
        foreach($heroes AS $v) {
            if ($v['hero_pk'] && $v['hero_pk'] > 0)
                $hero_pks[] = $v['hero_pk'];
        }
        if (count($hero_pks) > 0) {
            $over_rank_end_dic = [];
            $this->PgGame->query('SELECT * FROM getmyheroesskillslot_tmp(ARRAY['.implode(',', $hero_pks).'])');
            $this->PgGame->fetchAll();
            $rows = $this->PgGame->rows;
            foreach ($rows AS $v) {
                foreach ($heroes AS $k1 => $v1) {
                    if ($v1['hero_pk'] == $v['hero_pk']) {
                        for ($i = 1; $i <= 6; $i++) {
                            $heroes[$k1]['slot_pk'.$i] = $v['slot_pk'.$i];
                            $heroes[$k1]['m_hero_skil_pk'.$i] = $v['m_hero_skil_pk'.$i];
                            $heroes[$k1]['main_slot_pk'.$i] = $v['main_slot_pk'.$i];
                        }

                        // 오버랭크 시간 표기를 위해 추가
                        if ($_M['HERO'][$v1['m_hero_pk']]['over_type'] == 'Y') {
                            $this->PgGame->query('SELECT date_part(\'epoch\', end_dt) as end_dt FROM timer WHERE queue_pk = $1 AND queue_type = $2 AND status = $3', [$v['hero_pk'], 'R', 'P']);
                            $over_dt = $this->PgGame->fetchOne();
                            if ($over_dt) {
                                $over_rank_end_dic[$v['hero_pk']] = $v['hero_pk']; // TODO ?? 담기만 하고 안쓴지 않나?
                                $heroes[$k1]['overrank_end_dt'] = $over_dt;
                            }
                        }
                    }
                }
            }
        }
        return (is_array($heroes)) ? $heroes : [];
    }

    function getMyAppointHeroList($lord_pk, $page = 1, $order = 'rare', $order_type = 'desc', $list_num = null): array
    {
        $my_heroes = $this->getMyHeroList($lord_pk, ['A'], $page, $order, $order_type, $list_num);
        $posi_title_dic = [];
        foreach($my_heroes as &$v) {
            $v['status_text'] = $this->getMyHeroStatusText($v['hero_pk'], $v['posi_pk'], $v['status_cmd'], $v['cmd_type']);
            if (!isset($posi_title_dic[$v['posi_pk']])) {
                $this->PgGame->query('SELECT title FROM territory WHERE posi_pk = $1', [$v['posi_pk']]);
                $v['territory_title'] = $posi_title_dic[$v['posi_pk']] = $this->PgGame->fetchOne();
            } else {
                $v['territory_title'] = $posi_title_dic[$v['posi_pk']];
            }
            // $my_heroes[$k]['status_text'].="\n<".$v['territory_title'].'>';
        }
        return $my_heroes;
    }

    function getMyTerritoryHeroList($lord_pk, $page, $order, $order_type, $list_num, $_posi_pk): array
    {
        $page = (! $page || $page < 1) ? 1 : $page;
        $order = (! $order) ? 'rare' : $order;
        $order_type = (! $order_type) ? 'desc' : $order_type;
        $list_num = (! $list_num) ? null : $list_num;
        $my_heroes = $this->getMyHeroList($lord_pk, ['A'], $page, $order, $order_type, $list_num, false, false, $_posi_pk);
        $posi_title_dic = [];
        foreach($my_heroes as &$v) {
            $v['status_text'] = $this->getMyHeroStatusText($v['hero_pk'], $v['posi_pk'], $v['status_cmd'], $v['cmd_type']);
            if (!isset($posi_title_dic[$v['posi_pk']])) {
                $this->PgGame->query('SELECT title FROM territory WHERE posi_pk = $1', [$v['posi_pk']]);
                $v['territory_title'] = $posi_title_dic[$v['posi_pk']] = $this->PgGame->fetchOne();
            } else {
                $v['territory_title'] = $posi_title_dic[$v['posi_pk']];
            }
            // $my_heroes[$k]['status_text'].="\n<".$v['territory_title'].">";
        }
        return $my_heroes;
    }

    function getMyVisitHeroList($lord_pk, $page = 1, $order = 'rare', $order_type = 'desc', $list_num = null): array
    {
        return $this->getMyHeroList($lord_pk, ['C', 'S', 'V'], $page, $order, $order_type, $list_num);
    }

    function getMyGuestHeroList($lord_pk, $page = 1, $order = 'rare', $order_type = 'desc', $list_num = null): array
    {
        return $this->getMyHeroList($lord_pk, ['G'], $page, $order, $order_type, $list_num);
    }

    function getMyAllHeroList($lord_pk, $page = 1, $order = 'rare', $order_type = 'desc', $list_num = null): array
    {
        $my_heroes = $this->getMyHeroList($lord_pk, ['A', 'C', 'S', 'V', 'G'], $page, $order, $order_type, $list_num);
        $posi_title_dic = [];
        foreach($my_heroes as &$v) {
            if ($v['status'] == 'A') {
                $v['status_text'] = $this->getMyHeroStatusText($v['hero_pk'], $v['posi_pk'], $v['status_cmd'], $v['cmd_type']);
                if (!isset($posi_title_dic[$v['posi_pk']])) {
                    $this->PgGame->query('SELECT title FROM territory WHERE posi_pk = $1', [$v['posi_pk']]);
                    $v['territory_title'] = $posi_title_dic[$v['posi_pk']] = $this->PgGame->fetchOne();
                } else {
                    $v['territory_title'] = $posi_title_dic[$v['posi_pk']];
                }
                // $my_heroes[$k]['status_text'].="\n<".$v['territory_title'].'>';
            }
        }
        return $my_heroes;
    }

    function getMyOverRankHeroList($lord_pk, $page = 1, $order = 'rare', $order_type = 'desc', $list_num = null): array
    {
        $my_heroes = $this->getMyHeroList($lord_pk, ['A', 'C', 'S', 'V', 'G'], $page, $order, $order_type, $list_num, false, true);
        $posi_title_dic = [];
        foreach($my_heroes as &$v) {
            $v['status_text'] = $this->getMyHeroStatusText($v['hero_pk'], $v['posi_pk'], $v['status_cmd'], $v['cmd_type']);
            if (!isset($posi_title_dic[$v['posi_pk']])) {
                $this->PgGame->query('SELECT title FROM territory WHERE posi_pk = $1', [$v['posi_pk']]);
                $v['territory_title'] = $posi_title_dic[$v['posi_pk']] = $this->PgGame->fetchOne();
            } else {
                $v['territory_title'] = $posi_title_dic[$v['posi_pk']];
            }
            // $my_heroes[$k]['status_text'].="\n<".$v['territory_title'].'>';
        }
        return $my_heroes;
    }

    function getMyCommonCombiAvailHeroList($lord_pk, $page = 1, $order = 'rare', $order_type = 'desc', $list_num = null, $_combi_type = 'common'): array
    {
        if ($_combi_type == 'special') {
            $list = $this->getMyHeroList($lord_pk, ['G'], $page, $order, $order_type, $list_num);
        } else {
            $list = $this->getMyHeroList($lord_pk, ['G'], $page, $order, $order_type, $list_num, false, false, null, true);
        }
        return $list;
    }

    function getMySpecialCombiAvailHeroList($lord_pk, $page = 1, $order = 'rare', $order_type = 'desc', $list_num = null): array
    {
        $my_heroes = $this->getMyHeroList($lord_pk, ['G'], $page, $order, $order_type, $list_num, true);
        // TODO 중복코드 좀 정리해야 할듯?
        $posi_title_dic = [];
        foreach($my_heroes as &$v) {
            if ($v['status'] == 'A') {
                $v['status_text'] = $this->getMyHeroStatusText($v['hero_pk'], $v['posi_pk'], $v['status_cmd'], $v['cmd_type']);
                if (!isset($posi_title_dic[$v['posi_pk']])) {
                    $this->PgGame->query('SELECT title FROM territory WHERE posi_pk = $1', [$v['posi_pk']]);
                    $v['territory_title'] = $posi_title_dic[$v['posi_pk']] = $this->PgGame->fetchOne();
                } else {
                    $v['territory_title'] = $posi_title_dic[$v['posi_pk']];
                }
                // $my_heroes[$k]['status_text'].="\n<".$v['territory_title'].'>';
            }
        }
        return $my_heroes;
    }

    function getMyHeroListCount($_lord_pk, $_status_arr, $_for_combine = false, $_no_over_rank = false): int
    {
        if ($_for_combine) {
            $_add_query_string = " AND (t1.status = ANY ($2) OR (t1.status = 'A' AND t1.status_cmd = 'I') AND t2.level <= 19)";
        } else {
            $_add_query_string = " AND t1.status = ANY ($2)";
        }
        if ($_no_over_rank) {
            $_add_query_string .= " AND t3.over_type = 'N'";
        }
        $this->PgGame->query('SELECT count(t1.hero_pk) FROM my_hero AS t1, hero AS t2, m_hero AS t3 WHERE t1.hero_pk = t2.hero_pk AND t2.m_hero_pk = t3.m_hero_pk AND t1.lord_pk = $1'.$_add_query_string, [$_lord_pk, '{'. implode(',', $_status_arr). '}']);
        return $this->PgGame->fetchOne();
    }

    function getCollectionHeroListCount(): int
    {
        $this->PgGame->query('SELECT COUNT(m_hero_comb_coll_pk) FROM m_hero_collection_combi WHERE open_type = $1', ['Y']);
        return $this->PgGame->fetchOne();
    }

    function getCollectionHeroList($page = 1, $order = 'rare', $order_type = 'asc', $list_num = null): array
    {
        $order_by = '';
        if ($order == 'rare') {
            $order_by = 'b.rare_type ' . $order_type . ', b.name asc';
        } else if ($order == 'name') {
            $order_by = 'b.name ' . $order_type . ', b.rare_type asc';
        }
        $offset_start = ($page - 1) * $list_num;

        $this->PgGame->query("SELECT a.m_hero_comb_coll_pk, a.m_hero_pk, b.name, b.rare_type, a.m_hero_base_pk 
FROM m_hero_collection_combi a, m_hero_base b
WHERE a.m_hero_base_pk = b.m_hero_base_pk
AND a.open_type = $1 
ORDER BY {$order_by}, b.m_hero_base_pk
LIMIT {$list_num} OFFSET {$offset_start}", ['Y']);
        $this->PgGame->fetchAll();

        $hero_list = $this->PgGame->rows;
        foreach ($hero_list AS $k => $v) {
            $hero_list[$k]['meterial_cnt'] = COUNT($this->getCollectionHeroMaterialInfo($v['m_hero_comb_coll_pk']));
        }
        return $hero_list;
    }

    function getCollectionHeroMaterialInfo($_m_hero_comb_coll_pk): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_collection_combi']);
        $material_pk = '';
        for ($i = 1; $i <= $_M['HERO_COLL_COMB'][$_m_hero_comb_coll_pk]['material_count']; $i++) {
            $material_pk .= $_M['HERO_COLL_COMB'][$_m_hero_comb_coll_pk]['material_' . $i] . ',';
        }
        $material_pk = substr($material_pk, 0, strlen($material_pk) - 1);
        if (! $material_pk) {
            $material_pk = 0;
        }

        // 등용 대기중인 영웅 정보
        $this->PgGame->query("select b.m_hero_pk, c.m_hero_base_pk, c.level, a.hero_pk
from my_hero a, hero b, m_hero c
where a.lord_pk = $1
AND a.status = 'G'
AND a.hero_pk = b.hero_pk
AND b.m_hero_pk = c.m_hero_pk
AND c.m_hero_base_pk IN ({$material_pk})
ORDER BY c.level", [$this->Session->lord['lord_pk']]);
        $this->PgGame->fetchAll();
        $rows= $this->PgGame->rows;
        $hero_material_list = [];
        $use_hero_list = [];
        for ($i = 1; $i <= $_M['HERO_COLL_COMB'][$_m_hero_comb_coll_pk]['material_count']; $i++) {
            $material_pk = $_M['HERO_COLL_COMB'][$_m_hero_comb_coll_pk]['material_' . $i];
            foreach($rows AS $v) {
                if ($material_pk == $v['m_hero_base_pk'] && !in_array($v['hero_pk'], $use_hero_list)) {
                    $hero_material_list[$i] = $v;
                    $use_hero_list[] = $v['hero_pk'];
                    break;
                }
            }
        }
        return $hero_material_list;
    }

    function getCollectionTopMaterialCnt($_m_hero_comb_coll_pk): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_collection_combi']);
        $m_hero_comb = $_M['HERO_COLL_COMB'][$_m_hero_comb_coll_pk];
        $material_arr = [];
        $use_material_pk = [];
        foreach($_M['HERO_COLL_COMB'] AS $v) {
            for ($i = 1; $i <= $v['material_count']; $i++) {
                if ($v['material_' . $i] == $m_hero_comb['m_hero_base_pk'] && !in_array($v['material_' . $i], $use_material_pk)) {
                    $material_arr[$v['m_hero_base_pk']] = COUNT($this->getCollectionHeroMaterialInfo($v['m_hero_comb_coll_pk']));
                    $use_material_pk[] = $v['m_hero_base_pk'];
                }
            }
        }
        return $material_arr;
    }

    function getCollectionBottomMaterialCnt($_m_hero_comb_coll_pk): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_base', 'hero_collection_combi']);
        $m_hero_comb = $_M['HERO_COLL_COMB'][$_m_hero_comb_coll_pk];
        $material_arr = [];
        $use_material_pk = [];

        for ($i = 1; $i <= $m_hero_comb['material_count']; $i++) {
            $material_pk = $m_hero_comb['material_' . $i];
            if ($_M['HERO_BASE'][$material_pk]['rare_type'] != 1) {
                if (!in_array($material_pk, $use_material_pk)) {
                    foreach($_M['HERO_COLL_COMB'] as $k => $v) {
                        if ($material_pk == $v['m_hero_base_pk'] && $v['material_count'] > 0) {
                            $material_arr[$material_pk] = COUNT($this->getCollectionHeroMaterialInfo($k));
                            $use_material_pk[] = $material_pk;
                            break;
                        }
                    }
                }
            }
        }
        return $material_arr;
    }

    function getMyTerritoryHeroListCount($_lord_pk, $_status_arr, $_posi_pk, $_for_combine = false)
    {
        if ($_for_combine) {
            $add_sql = " AND posi_pk = $3 AND (t1.status = ANY ($2) OR (t1.status = 'A' AND t1.status_cmd = 'I') AND t2.level < 10)";
        } else {
            $add_sql = " AND posi_pk = $3 AND t1.status = ANY ($2)";
        }
        $this->PgGame->query('SELECT count(t1.hero_pk) FROM my_hero AS t1, hero AS t2 WHERE t1.hero_pk = t2.hero_pk AND t1.lord_pk = $1'.$add_sql, [$_lord_pk, '{'. implode(',', $_status_arr). '}', $_posi_pk]);
        return $this->PgGame->fetchOne();
    }

    function getMyOverRankHeroListCount($_lord_pk, $_status_arr): int
    {
        $this->PgGame->query('SELECT count(t1.hero_pk) FROM my_hero AS t1, hero AS t2, m_hero AS t3 WHERE t1.hero_pk = t2.hero_pk AND t2.m_hero_pk = t3.m_hero_pk AND t1.lord_pk = $1 AND t1.status = ANY ($2) AND t3.over_type = $3', [$_lord_pk, '{'. implode(',', $_status_arr). '}', 'Y']);
        return $this->PgGame->fetchOne();
    }

    function getMyAppoHeroesMHeroPk($_lord_pk): array
    {
        // 영웅 관리창에서 중복 영웅 확인하는 용도로 쓰임
        $list = [];
        $this->PgGame->query('SELECT hero.m_hero_pk FROM hero, my_hero WHERE hero.hero_pk = my_hero.hero_pk AND my_hero.status = $1 AND my_hero.lord_pk = $2', ['A', $_lord_pk]);
        while($this->PgGame->fetch()) {
            $list[] = $this->PgGame->row['m_hero_pk'];
        }
        return $list;
    }

    function getMyHeroes($_lord_pk, $_status_arr, $_posi_pk): array
    {
        $add_cond = '';
        $add_order_by = '';
        if ($_posi_pk) {
            $add_cond = ' AND t1.posi_pk = $3  ';
            $query_params = [$_lord_pk, '{'. implode(',', $_status_arr). '}', $_posi_pk];
        } else {
            $query_params = [$_lord_pk, '{'. implode(',', $_status_arr). '}'];
            if ($_status_arr[0] == 'G') {
                $add_order_by = ' ORDER BY t2.level DESC, t2.rare_type DESC';
            } else {
                $add_order_by = ' ORDER BY t1.acquired_dt DESC';
            }
        }

        $this->PgGame->query("SELECT
 t1.hero_pk, t1.m_offi_pk, t1.status, t1.status_cmd, t1.yn_lord, t1.posi_pk, t1.leadership, t1.mil_force, t1.intellect, t1.politics, t1.charm,
 t2.m_hero_pk, t2.status AS m_status, t2.level, t2.rare_type, t2.enchant, t2.loyalty, t2.hp, 
 t2.leadership_basic, t2.leadership_enchant, t2.leadership_plusstat, t2.leadership_skill,
 t2.mil_force_basic, t2.mil_force_enchant, t2.mil_force_plusstat,  t2.mil_force_skill,
 t2.intellect_basic, t2.intellect_enchant, t2.intellect_plusstat, t2.intellect_skill,
 t2.politics_basic, t2.politics_enchant, t2.politics_plusstat,  t2.politics_skill,
 t2.charm_basic, t2.charm_enchant, t2.charm_plusstat, t2.charm_skill, 
 date_part('epoch', t1.timedjob_dt)::integer AS timedjob_dt_ut,
 t1.cmd_type,
 t2.skill_exp,
 t1.group_type, t1.group_order, t2.yn_trade
FROM my_hero AS t1, hero AS t2
WHERE t1.hero_pk = t2.hero_pk AND t1.lord_pk = $1 AND t1.status = ANY ($2) {$add_cond}
{$add_order_by}", $query_params);

        $heroes = [];
        while ($this->PgGame->fetch()) {
            $heroes[$this->PgGame->row['hero_pk']] = $this->PgGame->row;
        }

        // skill 정보
        $hero_pks = [];
        foreach($heroes AS $v) {
            if ($v['hero_pk'] && $v['hero_pk'] > 0) {
                $hero_pks[] = $v['hero_pk'];
            }
        }

        if (count($hero_pks) > 0) {
            $hero_pks = implode(',', $hero_pks);
            $this->PgGame->query('SELECT * FROM getmyheroesskillslot_tmp(ARRAY['.$hero_pks.'])');
            $this->PgGame->fetchAll();
            $rows = $this->PgGame->rows;
            foreach ($rows AS $v) {
                for ($i = 1; $i <= 6; $i++) {
                    $heroes[$v['hero_pk']]['slot_pk'.$i] = $v['slot_pk'.$i];
                    $heroes[$v['hero_pk']]['m_hero_skil_pk'.$i] = $v['m_hero_skil_pk'.$i];
                    $heroes[$v['hero_pk']]['main_slot_pk'.$i] = $v['main_slot_pk'.$i];
                }
            }
        }
        return $heroes;
    }

    function getMyAppoHeroes($_lord_pk, $_posi_pk): array
    {
        return $this->getMyHeroes($_lord_pk, ['A'], $_posi_pk);
    }

    function getMyVisitHeros($_lord_pk): array
    {
        return $this->getMyHeroes($_lord_pk, ['C', 'S', 'V'], null);
    }

    function getMyGuestHeros($_lord_pk): array
    {
        return $this->getMyHeroes($_lord_pk, ['G'], null);
    }

    function getMyNotAppoHeroes($_lord_pk): array
    {
        // Capture(포획), Surrender(투항), Visit(방문)
        $heroes_1 = $this->getMyHeroes($_lord_pk, ['C', 'S', 'V'], null);
        // Guest(영입)
        $heroes_2 = $this->getMyHeroes($_lord_pk, ['G'], null);
        return array_merge($heroes_1, $heroes_2);
    }

    function getMyIdleHeroListCount($_lord_pk): int
    {
        $this->PgGame->query("SELECT count(t1.hero_pk)
FROM my_hero AS t1, hero AS t2, m_hero AS t3, m_hero_base AS t4
WHERE t1.hero_pk = t2.hero_pk AND t2.m_hero_pk = t3.m_hero_pk AND t3.m_hero_base_pk = t4.m_hero_base_pk AND
 t1.lord_pk = $1 AND t1.status = 'A' AND t1.status_cmd = 'I'", [$_lord_pk]);
        return $this->PgGame->fetchOne();
    }

    function getMyIdleHeroes($_lord_pk, $_page_num, $_list_num, $_order, $_order_type): array
    {
        $page = (INT)$_page_num;
        $page = ($page < 1) ? 1 : $page;

        $list_num = ($_list_num == null) ? HERO_LIST_PAGE_NUM : $_list_num;
        $offset_start = ($page - 1) * $list_num;
        $limit = $list_num;

        if ($_order == 'name') {
            $order_by = "t4.name::BYTEA {$_order_type}, t2.rare_type ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($_order == 'level') {
            $order_by = "t2.level {$_order_type}, t2.rare_type ASC, t4.name::BYTEA ASC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($_order == 'offi') {
            $_order_type = ($_order_type == 'DESC') ? 'ASC' : 'DESC';
            $order_by = "t1.m_offi_pk {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($_order == 'leadership') {
            $order_by = "t1.leadership {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($_order == 'mil_force') {
            $order_by = "t1.mil_force {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($_order == 'intellect') {
            $order_by = "t1.intellect {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($_order == 'politics') {
            $order_by = "t1.politics {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($_order == 'charm') {
            $order_by = "t1.charm {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t2.hero_pk ASC";
        } else if ($_order == 'loyalty') {
            $order_by = "t2.loyalty {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t2.hero_pk ASC";
        } else if ($_order == 'posi_pk') {
            $order_by = "t1.posi_pk {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t2.hero_pk ASC";
        } else if ($_order == 'infantry') {
            // 참고 URL http://stackoverflow.com/questions/1309624/simulating-mysqls-order-by-field-in-postgresql
            $_order_type = ($_order_type == 'DESC') ? 'ASC' : 'DESC';
            $order_by = "CASE WHEN t4.mil_aptitude_infantry = 'S' THEN 1 WHEN t4.mil_aptitude_infantry = 'A' THEN 2 WHEN t4.mil_aptitude_infantry = 'B' THEN 3 WHEN t4.mil_aptitude_infantry = 'C' THEN 4 WHEN t4.mil_aptitude_infantry = 'D' THEN 5 WHEN t4.mil_aptitude_infantry = 'E' THEN 6 ELSE 7 END {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($_order == 'pikeman') {
            $_order_type = ($_order_type == 'DESC') ? 'ASC' : 'DESC';
            $order_by = "CASE WHEN t4.mil_aptitude_pikeman = 'S' THEN 1 WHEN t4.mil_aptitude_pikeman = 'A' THEN 2 WHEN t4.mil_aptitude_pikeman = 'B' THEN 3 WHEN t4.mil_aptitude_pikeman = 'C' THEN 4 WHEN t4.mil_aptitude_pikeman = 'D' THEN 5 WHEN t4.mil_aptitude_pikeman = 'E' THEN 6 ELSE 7 END {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($_order == 'spearman') {
            $_order_type = ($_order_type == 'DESC') ? 'ASC' : 'DESC';
            $order_by = "CASE WHEN t4.mil_aptitude_spearman = 'S' THEN 1 WHEN t4.mil_aptitude_spearman = 'A' THEN 2 WHEN t4.mil_aptitude_spearman = 'B' THEN 3 WHEN t4.mil_aptitude_spearman = 'C' THEN 4 WHEN t4.mil_aptitude_spearman = 'D' THEN 5 WHEN t4.mil_aptitude_spearman = 'E' THEN 6 ELSE 7 END {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($_order == 'archer') {
            $_order_type = ($_order_type == 'DESC') ? 'ASC' : 'DESC';
            $order_by = "CASE WHEN t4.mil_aptitude_archer = 'S' THEN 1 WHEN t4.mil_aptitude_archer = 'A' THEN 2 WHEN t4.mil_aptitude_archer = 'B' THEN 3 WHEN t4.mil_aptitude_archer = 'C' THEN 4 WHEN t4.mil_aptitude_archer = 'D' THEN 5 WHEN t4.mil_aptitude_archer = 'E' THEN 6 ELSE 7 END {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($_order == 'horseman') {
            $_order_type = ($_order_type == 'DESC') ? 'ASC' : 'DESC';
            $order_by = "CASE WHEN t4.mil_aptitude_horseman = 'S' THEN 1 WHEN t4.mil_aptitude_horseman = 'A' THEN 2 WHEN t4.mil_aptitude_horseman = 'B' THEN 3 WHEN t4.mil_aptitude_horseman = 'C' THEN 4 WHEN t4.mil_aptitude_horseman = 'D' THEN 5 WHEN t4.mil_aptitude_horseman = 'E' THEN 6 ELSE 7 END {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else if ($_order == 'siege') {
            $_order_type = ($_order_type == 'DESC') ? 'ASC' : 'DESC';
            $order_by = "CASE WHEN t4.mil_aptitude_siege = 'S' THEN 1 WHEN t4.mil_aptitude_siege = 'A' THEN 2 WHEN t4.mil_aptitude_siege = 'B' THEN 3 WHEN t4.mil_aptitude_siege = 'C' THEN 4 WHEN t4.mil_aptitude_siege = 'D' THEN 5 WHEN t4.mil_aptitude_siege = 'E' THEN 6 ELSE 7 END {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        } else {
            $order_by = "t2.rare_type {$_order_type}, t2.level DESC, t4.name ASC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC, t2.hero_pk ASC";
        }

        $this->PgGame->query("SELECT
 t1.hero_pk, t4.name, t1.m_offi_pk, t1.status, t1.status_cmd, t1.yn_lord, t1.posi_pk, t1.leadership, t1.mil_force, t1.intellect, t1.politics, t1.charm,
 t2.m_hero_pk, t2.status AS m_status, t2.level, t2.rare_type, t2.enchant, t2.loyalty, t2.hp, t2.yn_trade, 
 t2.leadership_basic, t2.leadership_enchant, t2.leadership_plusstat, t2.leadership_skill,
 t2.mil_force_basic, t2.mil_force_enchant, t2.mil_force_plusstat,  t2.mil_force_skill,
 t2.intellect_basic, t2.intellect_enchant, t2.intellect_plusstat, t2.intellect_skill,
 t2.politics_basic, t2.politics_enchant, t2.politics_plusstat,  t2.politics_skill,
 t2.charm_basic, t2.charm_enchant, t2.charm_plusstat, t2.charm_skill, 
 t2.m_hero_skil_pk_1, t2.m_hero_skil_pk_2, t2.m_hero_skil_pk_3, t2.m_hero_skil_pk_4,
 date_part('epoch', t1.timedjob_dt)::integer AS timedjob_dt_ut, date_part('epoch', t1.last_dismiss_dt)::integer AS last_dismiss_dt_ut,
 t1.cmd_type, t2.skill_exp, t2.hero_exp, t2.special_combi_cnt, t3.acquire_exp, t3.need_exp, t4.forces,
 t1.group_type, t1.group_order, t2.yn_trade, t4.yn_new_gacha, t4.yn_modifier
FROM
 my_hero AS t1, hero AS t2, m_hero AS t3, m_hero_base AS t4
WHERE
 t1.hero_pk = t2.hero_pk AND t2.m_hero_pk = t3.m_hero_pk AND t3.m_hero_base_pk = t4.m_hero_base_pk AND
 t1.lord_pk = $1 AND t1.status = 'A' AND t1.status_cmd = 'I'
ORDER BY {$order_by}
LIMIT {$limit}
OFFSET {$offset_start}", [$_lord_pk]);

        $heroes = [];
        while ($this->PgGame->fetch()) {
            $heroes[] = $this->PgGame->row;
        }
        // skill 정보
        $hero_pks = [];
        if ($heroes) {
            foreach($heroes AS $k => $v) {
                if ($v['status'] == 'A') {
                    $heroes[$k]['status_text'] = $this->getMyHeroStatusText($v['hero_pk'], $v['posi_pk'], $v['status_cmd'], $v['cmd_type']);
                }
                if ($v['hero_pk'] && $v['hero_pk'] > 0) {
                    $hero_pks[] = $v['hero_pk'];
                }
            }

            if (count($hero_pks) > 0)
            {
                $this->PgGame->query('SELECT * FROM getmyheroesskillslot_tmp(ARRAY['.implode(',', $hero_pks).'])');
                $this->PgGame->fetchAll();
                $rows = $this->PgGame->rows;
                foreach ($rows AS $k => $v) {
                    foreach ($heroes AS $k1 => $v1) {
                        if ($v1['hero_pk'] == $v['hero_pk']) {
                            for ($i = 1; $i <= 6; $i++) {
                                $heroes[$k1]['slot_pk'.$i] = $v['slot_pk'.$i];
                                $heroes[$k1]['m_hero_skil_pk'.$i] = $v['m_hero_skil_pk'.$i];
                                $heroes[$k1]['main_slot_pk'.$i] = $v['main_slot_pk'.$i];
                            }
                        }
                    }
                }
            }
        }
        $posi_title_dic = [];
        foreach($heroes as $k => &$v) {
            if ($v['status'] == 'A') {
                $v['status_text'] = $this->getMyHeroStatusText($v['hero_pk'], $v['posi_pk'], $v['status_cmd'], $v['cmd_type']);
                if (! isset($posi_title_dic[$v['posi_pk']])) {
                    $this->PgGame->query('SELECT title FROM territory WHERE posi_pk = $1', [$v['posi_pk']]);
                    $v['territory_title'] = $posi_title_dic[$v['posi_pk']] = $this->PgGame->fetchOne();
                } else {
                    $v['territory_title'] = $posi_title_dic[$v['posi_pk']];
                }
                $heroes[$k]['status_text'].="\n<{$v['territory_title']}>";
            }
        }
        return $heroes;
    }

    function getMyGuestHeroes($_lord_pk): array
    {
        $this->PgGame->query("SELECT
 t1.hero_pk, t1.status_cmd, t1.leadership, t1.mil_force, t1.intellect, t1.politics, t1.charm,
 t2.m_hero_pk, t2.level, t2.rare_type
FROM my_hero AS t1, hero AS t2
WHERE t1.hero_pk = t2.hero_pk AND t1.lord_pk = $1 AND t1.status = 'G'
ORDER BY t2.level DESC, t2.rare_type DESC", [$_lord_pk]);
        $heroes = [];
        while ($this->PgGame->fetch()) {
            $heroes[$this->PgGame->row['hero_pk']] = $this->PgGame->row;
        }
        return $heroes;
    }

    function getNewHero($_acquired_type, $_sel_level = null, $_sel_rare = null, $_m_hero_base_pk = null, $_lord_level = null, $_forces = null, $_null = null, $_create_reason = '-', $_over_type = 'N', $_new_gachapon = 'N', $_yn_modifier = 'N'): false|int
    {
        if($_acquired_type == 'NEW_GACHAPON') {
            $_new_gachapon = 'Y';
        }
        /*if($_create_reason == 'collect_combi') {
            $_yn_modifier = 'Y';
        }*/
        $i = 0;
        $sel_m_hero_pk = null;

        // 추가능력치 타입, 영웅기술 타입
        $sel_plusstat = $this->getRandomPlusstat();
        //$sel_skill= $this->getRandomSkill($sel_level);
        $sel_skill = null;

        while ($sel_m_hero_pk === null) {
            $sel_level = (! isset($_sel_level)) ? $this->getRandomLevel($_acquired_type, $_lord_level) : $_sel_level;
            $acquired_type = match ($_acquired_type)
            {
                'ENCOUNT_ITEM', 'ITEM_EXCEL' => 'EXCEL',
                'ENCOUNT_SPECIAL', 'ITEM_SPECIAL' => 'SPECIAL',
                'ITEM_OVERRANK', 'ITEM_LUXURY_OVER', 'ITEM_RARE_OVER', 'GACHAPON_WOMAN', 'POWERFUL_EXCEL', 'POWERFUL_NORMAL',
                'NEW_GACHAPON', 'UNIQUE_ITEM', 'SUPPRESS_ITEM', 'PLUNDER_ITEM' => $_acquired_type,
                default => 'NORMAL'
            };

            $sel_rare = (! isset($_sel_rare)) ? $this->getRandomRare($sel_level, $acquired_type) : $_sel_rare;

            // 영웅 선택
            $sel_m_hero_pk = $this->getRandomHero($sel_level, $sel_rare, $_m_hero_base_pk, $_forces); // , $_over_type, $_new_gachapon, $_yn_modifier

            $i++;
            // fail_over 10번
            if ($i > 10) {
                $sel_m_hero_pk = false;
            }
        }
        // 영웅 선택 실패
        if (! $sel_m_hero_pk) {
            // 에러 로그가 필요하다면 로깅
            Debug::debugLogging("FAIL 10 count getRandomHero(). sel_level[$_sel_level], sel_rare[$_sel_rare], m_hero_base_pk[$_m_hero_base_pk], forces[$_forces]");
        }

        // 영웅 등록
        return $this->setFreeHeroCreate($sel_m_hero_pk, $sel_plusstat, $sel_skill, $_create_reason);
    }

    function getNewHeroForces($_forces): false|int
    {
        // 사용안함. 예전에 아이템이 있었는데 현재 없어짐.
        return $this->getNewHero('QUEST', null, null, null, $this->Session->lord['level'], $_forces);
    }

    function getNewLord($_lord_type, $_level = 2, $_create_reason = 'regist'): false|int
    {
        $lord_m_hero_pk = null;
        switch ($_lord_type) {
            case 1:
                $lord_m_hero_base_pk = 120002; // TODO 이거 안쓰는거 아닌가?
                $lord_m_hero_pk = (PK_LORD_liubei - 2 + $_level);
                break;
            case 2:
                $lord_m_hero_base_pk = 120000;
                $lord_m_hero_pk = (PK_LORD_caocao - 2 + $_level);
                break;
            case 3:
                $lord_m_hero_base_pk = 120001;
                $lord_m_hero_pk = (PK_LORD_sunquan - 2 + $_level);
                break;
            case 4:
                $lord_m_hero_base_pk = 120003;
                $lord_m_hero_pk = (PK_LORD_yuanshao - 2 + $_level);
                break;
            case 5:
                $lord_m_hero_base_pk = 120004;
                $lord_m_hero_pk = (PK_LORD_dongzhuo - 2 + $_level);
                break;
        }

        if (! $lord_m_hero_pk) {
            return false;
        }

        // 레벨, 레어도 선택
        $sel_level = 2;
        $sel_rare = '7'; // 사용안함, 120003, 120004 는 레어도 6임.

        // 추가능력치 타입, 영웅기술 타입
        $sel_plusstat = $this->getRandomPlusstat();
        //$sel_skill= $this->getRandomSkill($sel_level);
        $sel_skill = null;

        // 영웅 선택
        $sel_m_hero_pk = $lord_m_hero_pk;

        // 영웅 등록
        return $this->setFreeHeroCreate($sel_m_hero_pk, $sel_plusstat, $sel_skill, $_create_reason);
    }

    function setNewStat($_hero_pk, $_m_offi_pk = null): void
    {
        $this->PgGame->query('UPDATE my_hero
SET leadership = hero.leadership, mil_force = hero.mil_force, intellect = hero.intellect, politics = hero.politics, charm = hero.charm
FROM (SELECT
  (CASE WHEN leadership_basic+leadership_enchant+leadership_plusstat+leadership_skill < 0 THEN 0 ELSE leadership_basic+leadership_enchant+leadership_plusstat+leadership_skill END) AS leadership,
  (CASE WHEN mil_force_basic+mil_force_enchant+mil_force_plusstat+mil_force_skill < 0 THEN 0 ELSE mil_force_basic+mil_force_enchant+mil_force_plusstat+mil_force_skill END) AS mil_force,
  (CASE WHEN intellect_basic+intellect_enchant+intellect_plusstat+intellect_skill < 0 THEN 0 ELSE intellect_basic+intellect_enchant+intellect_plusstat+intellect_skill END) AS intellect,
  (CASE WHEN politics_basic+politics_enchant+politics_plusstat+politics_skill < 0 THEN 0 ELSE politics_basic+politics_enchant+politics_plusstat+politics_skill END) AS politics,
  (CASE WHEN charm_basic+charm_enchant+charm_plusstat+charm_skill < 0 THEN 0 ELSE charm_basic+charm_enchant+charm_plusstat+charm_skill END) AS charm  
 FROM hero WHERE hero_pk = $1) AS hero
WHERE hero_pk = $1', [$_hero_pk]);

        if ($_m_offi_pk) {
            $this->PgGame->query('UPDATE my_hero
SET leadership = leadership + officer.stat_plus_leadership, mil_force = mil_force + officer.stat_plus_mil_force, intellect = intellect + officer.stat_plus_intellect, politics = politics + officer.stat_plus_politics, charm = charm + officer.stat_plus_charm
FROM
(SELECT stat_plus_leadership, stat_plus_mil_force, stat_plus_intellect, stat_plus_politics, stat_plus_charm
 FROM m_officer WHERE m_offi_pk = $2) AS officer
WHERE hero_pk = $1', [$_hero_pk, $_m_offi_pk]);

            if (isset($this->Session->lord['lord_pk'])) {
                // 퀘스트 체크
                $this->classQuest();
                $this->Quest->conditionCheckQuest($this->Session->lord['lord_pk'], ['quest_type' => 'hero', 'hero_type' => 'appoint', 'hero_pk' => $_hero_pk]);
            }
        }
    }

    function setGuest($_hero_pk): bool
    {
        global $i18n;

        // status 가 C, S, V 만 가능
        $this->PgGame->query('SELECT status, status_cmd FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        if (!$this->PgGame->fetch()) {
            return false; // 영웅을 찾을 수 없음
        }

        if (! in_array($this->PgGame->row['status'], ['C', 'S', 'V'])) {
            throw new ErrorHandler('error', $i18n->t('msg_not_possible_recruit_hero')); // 해당 영웅을 영입 할 수 없는 상태 입니다. Error Occurred. [17001]
        }

        // lord.num_slot_guest_hero 넘치나 검사
        $this->PgGame->query('SELECT COUNT(hero_pk) AS cnt FROM my_hero WHERE lord_pk = $1 AND status = $2', [$this->Session->lord['lord_pk'], 'G']);
        $guest_cnt = $this->PgGame->fetchOne();
        if ($guest_cnt >= $this->Session->lord['num_slot_guest_hero']) {
            throw new ErrorHandler('error', $i18n->t('msg_lack_hero_guest_slot')); // 영입 영웅 관리에 빈 슬롯이 없어 영입이 불가능 합니다.
        }

        // 재등용 하는 영웅인지 체크해야함.
        $this->PgGame->query('SELECT yn_re_guest FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        $yn_re_guest = $this->PgGame->fetchOne();

        // status 를 G로 변경하고 status_cmd 를 I 로...
        $interval = 60 * 60 * 25;
        if ($yn_re_guest != 'Y') {
            $sql = "UPDATE my_hero SET status = $1, status_cmd = $2, last_appoint_dt = now() - interval '$interval second', last_dismiss_dt = now() - interval '$interval second' WHERE hero_pk = $3";
        } else {
            $sql = "UPDATE my_hero SET status = $1, status_cmd = $2, last_appoint_dt = now() - interval '$interval second' WHERE hero_pk = $3";
        }
        $r = $this->PgGame->query($sql, ['G', 'I', $_hero_pk]);
        if (!$r && $this->PgGame->getAffectedRows() != 1) {
            throw new ErrorHandler('error', 'Error Occurred. [17003]'); // 영웅 상태 변경 실패
        }
        // 오버랭크 영웅인지 확인하고 맞을경우 타이머 등록
        $this->PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        $m_hero_pk = $this->PgGame->fetchOne();

        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero']);

        $over_hero_time = '';
        if ($_M['HERO'][$m_hero_pk]['over_type'] == 'Y') {
            $this->classTimer();
            $duration = $_M['HERO'][$m_hero_pk]['over_hero_duration'] * 60 * 60;
            $over_hero_time = '최초 영입 시간:'.date('Y-m-d H:i:s', time()); // TODO 텍스트 코드 처리 필요.
            $r = $this->Timer->set($this->Session->lord['main_posi_pk'], 'R', $_hero_pk, 'R', $_hero_pk, $duration);
            if (!$r) {
                throw new ErrorHandler('error', 'Error Occurred. [17004]'); // 영웅 영입 실패
            }
        }

        $loyalty = 70; // status = 'V'
        if ($yn_re_guest != 'Y') {
            // 최초 충성도
            $this->PgGame->query('SELECT status, status_cmd FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
            $this->PgGame->fetch();
            if (in_array($this->PgGame->row['status'], ['C', 'S'])) {
                $loyalty = 60;
            }

            $r = $this->PgGame->query('UPDATE hero SET loyalty = $2 WHERE hero_pk = $1', [$_hero_pk, $loyalty]);
            if (!$r && $this->PgGame->getAffectedRows() != 1) {
                throw new ErrorHandler('error', 'Error Occurred. [17005]'); // 영웅 영입 실패
            }
        }

        $this->setUnreadHeroCnt($this->Session->lord['lord_pk']);
        // $this->setUnreadOverrankHeroCnt($this->Session->lord['lord_pk']);

        // Log
        $this->classLog();
        $this->Log->setHero($this->Session->lord['lord_pk'], $this->Session->getPosipk(), 'Guest', $_hero_pk, 'G', 'I', 'None');

        return true;
    }

    // TODO 2중 try catch 확인 바람.
    function setAppoint($_hero_pk, $_m_offi_pk, $_posi_pk): bool
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['hero', 'hero_base', 'officer']);

        // m_hero_pk 알아오기
        $this->PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        $m_hero_pk = $this->PgGame->fetchOne();

        // 이미 관직에 영웅이 있는지 체크
        $this->PgGame->query('SELECT hero_pk FROM my_hero WHERE m_offi_pk = $1 AND lord_pk = $2', [$_m_offi_pk, $this->Session->lord['lord_pk']]);
        if ($this->PgGame->FetchAll() > 0) {
            throw new ErrorHandler('error', 'Error Occurred. [17006]'); // 이미 타영웅이 임명받은 관직입니다.
        }

        // status 가 G, status_cmd 가 I 인 상태에서만 가능
        $query_params = [$_hero_pk];
        $this->PgGame->query('SELECT status, status_cmd, date_part(\'epoch\', last_dismiss_dt)::integer AS last_dismiss_dt_ut FROM my_hero WHERE hero_pk = $1', $query_params);
        if (! $this->PgGame->fetch()) {
            throw new ErrorHandler('error', 'Error Occurred. [17007]'); // 해당 영웅을 찾을 수 없습니다.
        }
        if ($this->PgGame->row['status'] != 'G' || $this->PgGame->row['status_cmd'] != 'I') {
            throw new ErrorHandler('error', 'Error Occurred. [17008]'); // 해당 영웅을 등용 할 수 없는 상태 입니다.
        }

        // 재등용 쿨타임
        $z = $this->PgGame->row['last_dismiss_dt_ut']+(60*60*24) - time();
        if ($z > 0) {
            throw new ErrorHandler('error', $i18n->t('msg_hero_dismiss_remain', [Useful::readableTime($z)])); // 해임 후 재등용 가능 시간까지 {{1}} 남았습니다.
        }
        // 해임 후 재등용 가능 시간까지 {{1}} 남았습니다.

        // 동일한 이름을 가진 영웅 찾기
        $appo_heroes_m_hero_pk = $this->getMyAppoHeroesMHeroPk($this->Session->lord['lord_pk']);
        $m_hero_base = $_M['HERO_BASE'][$_M['HERO'][$m_hero_pk]['m_hero_base_pk']];
        foreach ($appo_heroes_m_hero_pk as $v) {
            if ($_M['HERO_BASE'][$_M['HERO'][$v]['m_hero_base_pk']]['name'] == $m_hero_base['name']) {
                if ($_M['HERO_BASE'][$_M['HERO'][$v]['m_hero_base_pk']]['yn_modifier'] == 'N' && $m_hero_base['over_type'] == 'Y') {
                    throw new ErrorHandler('error', $i18n->t('msg_same_hero_not_appoint')); // 동일한 영웅은 1명만 등용할 수 있습니다.
                } else if ($_M['HERO_BASE'][$_M['HERO'][$v]['m_hero_base_pk']]['over_type'] == 'Y' && $m_hero_base['yn_modifier'] == 'N') {
                    throw new ErrorHandler('error', $i18n->t('msg_same_hero_not_appoint')); // 동일한 영웅은 1명만 등용할 수 있습니다.
                } else if ($_M['HERO_BASE'][$_M['HERO'][$v]['m_hero_base_pk']]['over_type'] == $m_hero_base['over_type'] && $_M['HERO_BASE'][$_M['HERO'][$v]['m_hero_base_pk']]['yn_modifier'] == $m_hero_base['yn_modifier']) {
                    throw new ErrorHandler('error', $i18n->t('msg_same_hero_not_appoint')); // 동일한 영웅은 1명만 등용할 수 있습니다.
                }
            }
        }

        // 등용가능 관직 여부
        if ($this->Session->lord['level'] < $_M['OFFI'][$_m_offi_pk]['active_level']) {
            throw new ErrorHandler('error', 'Error Occurred. [17009]'); // 제수가 허용되지 않은 관직 입니다.
        }

        // 유효한 매관매위 확인
        if ($_M['OFFI'][$_m_offi_pk]['active_level'] < 1) {
            $this->PgGame->query('SELECT m_offi_pk FROM lord WHERE lord_pk = $1', [$this->Session->lord['lord_pk']]);
            $m_offi_pk = $this->PgGame->fetchOne();
            if ($m_offi_pk < $_m_offi_pk) {
                throw new ErrorHandler('error', 'Error Occurred. [17010]'); // 제수가 허용되지 않은 관직 입니다.
            }
        }

        try {
            $this->PgGame->begin();
            // status 를 A로 last_appoint_dt 를 현재로 m_offi_pk 와 posi_pk 를 셋팅
            $this->PgGame->query('UPDATE my_hero SET status = $1, status_cmd = $2, last_appoint_dt = now(), last_salary_dt = now(), m_offi_pk = $3, posi_pk = $4 WHERE hero_pk = $5', ['A', 'I', $_m_offi_pk, $_posi_pk, $_hero_pk]);

            // UPDATE 에서 실패할 경우 sql error 그대로 가는데 못가게 해야함.
            if ($this->PgGame->getAffectedRows() != 1) {
                throw new Exception('Error Occurred. [17011]'); // my_hero update failed
            }

            $r = $this->PgGame->query('UPDATE lord SET num_appoint_hero = ( SELECT COUNT(*) FROM my_hero WHERE lord_pk = $1 AND status = $2 ) WHERE lord_pk = $1', [$this->Session->lord['lord_pk'], 'A']);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                throw new Exception('Error Occurred. [17012]'); // lord update failed
            }

            $this->PgGame->commit();
        } catch (Exception $e){
            // 실패, sq 무시
            $this->PgGame->rollback();
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$_posi_pk.'];hero_pk['.$_hero_pk.']');
            throw new ErrorHandler('error', 'Error Occurred. [17013]'); // 관직 제수 중 오류가 발생했습니다.
        }

        // 능력치 재계산
        $this->setNewStat($_hero_pk, $_m_offi_pk);

        // 영향력 추가
        $this->PgGame->query('SELECT level, create_reason FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        $level = $this->PgGame->row['level'];

        if ($this->PgGame->row['create_reason'] != 'regist') {
            $this->classLord();
            $this->Lord->increasePower($this->Session->lord['lord_pk'], $_M['HERO_APPOINT_POWER'][$level]['total_power']);
        }

        $this->setUnreadHeroCnt($this->Session->lord['lord_pk']);
        // $this->setUnreadOverrankHeroCnt($this->Session->lord['lord_pk']);

        // Log
        $this->classLog();
        $log_description = "$_hero_pk:[m_hero_pk:$m_hero_pk,m_offi_pk:$_m_offi_pk,desc:now];";
        $this->Log->setHero($this->Session->lord['lord_pk'], $this->Session->getPosiPk(), 'Appoint', $_hero_pk, 'A', 'I', 'None', $log_description);

        return true;
    }

    function setChangeOfficer($_hero_pk, $_m_offi_pk, $_posi_pk): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['officer']);

        if (!$_M['OFFI'][$_m_offi_pk] || $_M['OFFI'][$_m_offi_pk]['active_level'] > $this->Session->lord['level']) {
            throw new ErrorHandler('error', 'Error Occurred. [17014]'); // 해당 관직의 임명 권한이 없습니다.
        }

        //매관매직 인지 체크
        if (in_array($_m_offi_pk, [110121, 110122, 110123, 110124, 110125, 110126, 110127, 110128, 110129, 110130])) {
            //매관매직이라면 군주가 권한이 있는지 검사.
            $this->PgGame->query('SELECT m_offi_pk FROM lord WHERE lord_pk=$2 AND m_offi_pk >= $1', [$_m_offi_pk, $this->Session->lord['lord_pk']]);
            if($this->PgGame->FetchAll() == 0) {
                throw new ErrorHandler('error', 'Error Occurred. [17015]'); // 해당 관직의 임명 권한이 없습니다.
            }
        }

        // status 가 G, status_cmd 가 I 인 상태에서만 가능
        $this->PgGame->query('SELECT status, status_cmd, cmd_type, date_part(\'epoch\', last_dismiss_dt)::integer AS last_dismiss_dt_ut, m_offi_pk FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        if (! $this->PgGame->row) {
            // 영웅을 찾을 수 없음
            throw new ErrorHandler('error', 'Error Occurred. [17016]'); // 해당 영웅을 찾을 수 없습니다.
        }
        $row = $this->PgGame->row;
        if ($this->PgGame->row['status'] != 'A' || $this->PgGame->row['status_cmd'] != 'I') {
            // 관직 교체 가능한 상태가 아님
            throw new ErrorHandler('error', 'Error Occurred. [17017]'); // 해당 영웅의 관직을 교체 할 수 없는 상태 입니다.
        }
        if ($this->PgGame->row['m_offi_pk'] == $_m_offi_pk) {
            // 동일한 관직
            throw new ErrorHandler('error', 'Error Occurred. [17018]'); // 교체하려는 관직과 동일한 관직입니다.
        }
        $this->PgGame->query('UPDATE my_hero SET m_offi_pk = $1 WHERE hero_pk = $2', [$_m_offi_pk, $_hero_pk]);

        // UPDATE 에서 실패할 경우 sql error 그대로 가는데 못가게 해야함.
        if ($this->PgGame->getAffectedRows() != 1) {
            // 관직 제수 실패
            throw new ErrorHandler('error', 'Error Occurred. [17019]'); // 관직 제수 중 오류가 발생했습니다.
        }

        // 능력치 재계산
        $this->setNewStat($_hero_pk, $_m_offi_pk);
        $this->Session->sqAppend('HERO', [$_hero_pk => $this->getMyHeroInfo($_hero_pk)], null, $this->Session->lord['lord_pk'], $_posi_pk);

        // Log
        $this->classLog();
        $log_description = $_hero_pk.':[m_hero_pk:'.$this->getMasterHeroPk($_hero_pk).',m_offi_pk:'.$_m_offi_pk.',desc:now];';
        $this->Log->setHero($this->Session->lord['lord_pk'], $this->Session->getPosiPk(), 'Officer', $_hero_pk, $row['status'], $row['status_cmd'], $row['cmd_type'], $log_description);

        return true;
    }

    // TODO 2중 try catch 확인 바람.
    function setSwapOfficer($_hero_pk, $_chan_hero_pk, $_m_offi_pk, $_posi_pk): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['officer']);

        if(!$_M['OFFI'][$_m_offi_pk] || $_M['OFFI'][$_m_offi_pk]['active_level'] > $this->Session->lord['level']) {
            throw new ErrorHandler('error', 'Error Occurred. [17020]'); // 해당 관직의 임명 권한이 없습니다.
        }

        //매관매직 인지 체크
        if(in_array($_m_offi_pk, [110121, 110122, 110123, 110124, 110125, 110126, 110127, 110128, 110129, 110130])) {
            //매관매직이라면 군주가 권한이 있는지 검사.
            $this->PgGame->query('SELECT m_offi_pk FROM lord WHERE lord_pk = $2 AND m_offi_pk >= $1', [$_m_offi_pk, $this->Session->lord['lord_pk']]);
            $offi_num = $this->PgGame->FetchAll();
            if($offi_num == 0) {
                throw new ErrorHandler('error', 'Error Occurred. [17021]'); // 해당 관직의 임명 권한이 없습니다.
            }
        }

        $this->PgGame->query('SELECT hero_pk, status, status_cmd, cmd_type, m_offi_pk, date_part(\'epoch\', last_dismiss_dt)::integer AS last_dismiss_dt_ut, m_offi_pk, posi_pk FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        $hero_row = $this->PgGame->row;

        $this->PgGame->query('SELECT hero_pk, status, status_cmd, cmd_type, m_offi_pk, date_part(\'epoch\', last_dismiss_dt)::integer AS last_dismiss_dt_ut, m_offi_pk, posi_pk FROM my_hero WHERE hero_pk = $1', [$_chan_hero_pk]);
        $this->PgGame->fetch();
        $chan_hero_row = $this->PgGame->row;

        if (! $hero_row || ! $chan_hero_row) {
            // 영웅을 찾을 수 없음
            throw new ErrorHandler('error', 'Error Occurred. [17022]'); // 해당 영웅을 찾을 수 없습니다.
        }

        $this->PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$hero_row['hero_pk']]);
        $m_hero_pk = $this->PgGame->fetchOne();
        $this->PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$chan_hero_row['hero_pk']]);
        $chan_m_hero_pk = $this->PgGame->fetchOne();

        if ($hero_row['status'] != 'A' || $hero_row['status_cmd'] != 'I' || $chan_hero_row['status'] != 'A' || $chan_hero_row['status_cmd'] != 'I') {
            // 관직 교체 가능한 상태가 아님
            throw new ErrorHandler('error', 'Error Occurred. [17023]'); // 해당 영웅의 관직을 교체 할 수 없는 상태 입니다.
        }

        if ($hero_row['m_offi_pk'] == $_m_offi_pk) {
            throw new ErrorHandler('error', 'Error Occurred. [17024]'); // 이미 해당 관직에 등용되어 있습니다.
        }

        try {
            $this->PgGame->begin();
            $this->PgGame->query('UPDATE my_hero SET m_offi_pk = $1 WHERE hero_pk = $2', [NULL, $hero_row['hero_pk']]); // 유니크 설정 때문에 일단 NULL값 입력
            if ($this->PgGame->getAffectedRows() != 1) {
                throw new Exception('Error Occurred. [17025]'); // 관직 교체에 실패해였습니다.
            }
            $this->PgGame->query('UPDATE my_hero SET m_offi_pk = $1 WHERE hero_pk = $2', [$hero_row['m_offi_pk'], $chan_hero_row['hero_pk']]);
            if ($this->PgGame->getAffectedRows() != 1) {
                throw new Exception('Error Occurred. [17026]'); // 관직 교체에 실패해였습니다.
            }
            $this->PgGame->query('UPDATE my_hero SET m_offi_pk = $1 WHERE hero_pk = $2', [$chan_hero_row['m_offi_pk'], $hero_row['hero_pk']]);
            if ($this->PgGame->getAffectedRows() != 1) {
                throw new Exception('Error Occurred. [17027]'); // 관직 교체에 실패해였습니다.
            }
            $this->PgGame->commit();
        } catch (Exception $e) {
            $this->PgGame->rollback();
            // 관직 제수 실패
            throw new ErrorHandler('error', 'Error Occurred. [17028]'); // 관직 교체에 실패해였습니다.
        }

        // 능력치 재계산
        $this->setNewStat($_chan_hero_pk, $hero_row['m_offi_pk']);
        $this->setNewStat($_hero_pk, $chan_hero_row['m_offi_pk']);

        $this->Session->sqAppend('HERO', [$_chan_hero_pk => $this->getMyHeroInfo($_chan_hero_pk)], null, $this->Session->lord['lord_pk'], $chan_hero_row['posi_pk']);
        $this->Session->sqAppend('HERO', [$_hero_pk => $this->getMyHeroInfo($_hero_pk)], null, $this->Session->lord['lord_pk'], $hero_row['posi_pk']);


        // Log
        $this->classLog();
        $this->PgGame->query('SELECT status, status_cmd, cmd_type FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        $log_description = $_chan_hero_pk.':[m_hero_pk:'.$chan_m_hero_pk.',m_offi_pk:'.$hero_row['m_offi_pk'].',desc:change];';
        $this->Log->setHero($this->Session->lord['lord_pk'], $this->Session->getPosiPk(), 'Officer', $_chan_hero_pk, $row['status'], $row['status_cmd'], $row['cmd_type'], $log_description);
        $log_description = $_hero_pk.':[m_hero_pk:'.$m_hero_pk.',m_offi_pk:'.$chan_hero_row['m_offi_pk'].',desc:now];';
        $this->Log->setHero($this->Session->lord['lord_pk'], $this->Session->getPosiPk(), 'Officer', $_hero_pk, $row['status'], $row['status_cmd'], $row['cmd_type'], $log_description);

        return true;
    }

    // TODO 2중 try catch 확인 바람.
    function setAbandon($_hero_pk, $_abandon_type = null): bool
    {
        // status 가 G, status_cmd 가 I 인 상태에서만 가능
        $this->PgGame->query('SELECT status, status_cmd FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        if (! $this->PgGame->fetch()) {
            // 영웅을 찾을 수 없음 TODO 필요시 에러로그
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, $_hero_pk);
            throw new ErrorHandler('error', 'Error Occurred. [17029]'); // 해당 영웅이 존재하지 않습니다.
        }
        if ($this->PgGame->row['status'] != 'G' || $this->PgGame->row['status_cmd'] != 'I') {
            // 방출 가능한 상태가 아님
            throw new ErrorHandler('error', 'Error Occurred. [17030]'); // 해당 영웅을 방출 할 수 없는 상태 입니다.
        }

        try {
            // $this->PgGame->begin();

            // 오버랭크 영웅 타이머 삭제
            $this->PgGame->query('SELECT time_pk, (date_part(\'epoch\', end_dt)::integer - date_part(\'epoch\', start_dt))::integer as reduce_time FROM timer WHERE status = $1 AND queue_status = $2 AND queue_type = $3 AND queue_pk = $4', ['P', 'W','R', $_hero_pk]);
            if ($this->PgGame->fetch()) {
                $this->classTimer();
                // TODO 가속으로 지워버리는게 맞나?
                $r = $this->Timer->speedup($this->PgGame->row['time_pk'], $this->PgGame->row['reduce_time']);
                if (! $r) {
                    throw new Exception('Error Occurred. [17031]'); // 영웅 방출 실패 하였습니다. 다시 시도해 주시기 바랍니다.
                }
            }
            // 로그를 위해 데이터를 먼저 가져옴
            $this->PgGame->query('SELECT status, status_cmd, cmd_type FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
            $this->PgGame->fetch();
            $my_hero_row = $this->PgGame->row;

            $this->PgGame->query('SELECT * FROM hero WHERE hero_pk = $1', [$_hero_pk]);
            $this->PgGame->fetch();
            $hero_row = $this->PgGame->row;

            // 삭제
            $this->PgGame->query('DELETE FROM my_hero_skill_slot WHERE hero_pk = $1', [$_hero_pk]);
            $r = $this->PgGame->query('DELETE FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'Abandon fail['.$_hero_pk.'];');
                throw new Exception('Error Occurred. [17032]'); // 영웅 방출 실패 하였습니다. 다시 시도해 주시기 바랍니다.
            }
            $r = $this->PgGame->query('DELETE FROM hero WHERE hero_pk = $1', [$_hero_pk]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'Abandon fail['.$_hero_pk.'];');
                throw new Exception('Error Occurred. [17033]'); // 영웅 방출 실패 하였습니다. 다시 시도해 주시기 바랍니다.
            }

            // $this->PgGame->commit();
        } catch (Exception $e) {
            // $this->PgGame->rollback();
            // 관직 제수 실패
            throw new ErrorHandler('error', 'Error Occurred. [17034]'); // 영웅 방출 실패 하였습니다. 다시 시도해 주시기 바랍니다.
        }

        $this->setUnreadHeroCnt($this->Session->lord['lord_pk']);
        // $this->setUnreadOverrankHeroCnt($this->Session->lord['lord_pk']);

        // Log
        $this->classLog();
        $this->Log->setHero($this->Session->lord['lord_pk'], $this->Session->getPosiPk(), 'Abandon', $_hero_pk, $my_hero_row['status'], $my_hero_row['status_cmd'], $my_hero_row['cmd_type'], $_abandon_type, $hero_row['m_hero_pk']);
        $this->Log->setHeroData($hero_row);

        return true;
    }

    // TODO 2중 try catch 확인 바람.
    function setDismiss ($_hero_pk): bool
    {
        global $i18n;
        // status 가 A, status_cmd 가 I 인 상태에서만 가능
        $this->PgGame->query('SELECT m_offi_pk, status, status_cmd, yn_lord FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        $status_row = $this->PgGame->row;
        if (!$status_row) {
            // 영웅을 찾을 수 없음
            throw new ErrorHandler('error', 'Error Occurred. [17035]'); // 영웅을 찾을 수 없음.
        }

        if ($status_row['status'] != 'A' || $status_row['status_cmd'] != 'I' || $status_row['yn_lord'] == 'Y') {
            // 군주카드 해임 불가
            throw new ErrorHandler('error', $i18n->t('msg_hero_cannot_dismiss')); // '해당 영웅을 해임 할 수 없는 상태 입니다.' Error Occurred. [17036]
        }

        // lord.num_slot_guest_hero 넘치나 검사
        $this->PgGame->query('SELECT COUNT(hero_pk) AS cnt FROM my_hero WHERE lord_pk = $1 AND status = $2', [$this->Session->lord['lord_pk'], 'G']);
        $guest_cnt = $this->PgGame->fetchOne();
        if ($guest_cnt >= $this->Session->lord['num_slot_guest_hero']) {
            // 빈 슬롯 없음
            throw new ErrorHandler('error', $i18n->t('msg_lack_hero_guest_slot_dismiss')); // 영입 영웅 관리에 빈 슬롯이 없어 해임이 불가능 합니다.Error Occurred. [17037]
        }

        try {
            $this->PgGame->begin();
            // 상태변경 - status 를 G로 last_dismiss_dt 를 현재로 m_offi_pk 와 posi_pk 를 NULL 로...
            $this->PgGame->query('UPDATE my_hero SET status = $1, last_dismiss_dt = now(), m_offi_pk = $2, posi_pk = $3 WHERE hero_pk = $4', ['G', null, null, $_hero_pk]);

            // 해임시 충성도 10 감소
            $this->PgGame->query('UPDATE hero SET loyalty = loyalty - 10 WHERE hero_pk = $1', [$_hero_pk]);
            $this->PgGame->query('UPDATE hero SET loyalty = 0 WHERE hero_pk = $1 AND loyalty < 0', [$_hero_pk]); // 충성도 최소는 0

            $this->PgGame->query('UPDATE lord SET num_appoint_hero = ( SELECT COUNT(*) FROM my_hero WHERE lord_pk = $1 AND status = $2 ) WHERE lord_pk = $1', [$this->Session->lord['lord_pk'], 'A']);

            // 능력치 재계산
            $this->setNewStat($_hero_pk, $status_row['m_offi_pk']); // TODO $_m_offi_pk 어딨냐?

            $this->PgGame->commit();
        } catch (Throwable $e) {
            $this->PgGame->rollback();
            throw new ErrorHandler('error', 'Error Occurred. [17038]'); // 영웅 해임 실패
        }

        // 카드덱에서 제거하기
        $this->Session->sqAppend('HERO', [$_hero_pk => ['status' => 'NULL']]);

        // 영향력 감소
        $this->PgGame->query('SELECT level, create_reason FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        $level = $this->PgGame->row['level'];

        if ($this->PgGame->row['create_reason'] != 'regist') {
            global $_M;
            $this->classLord();
            $this->Lord->decreasePower($this->Session->lord['lord_pk'], $_M['HERO_APPOINT_POWER'][$level]['total_power']);
        }

        // Log
        $this->classLog();
        $this->Log->setHero($this->Session->lord['lord_pk'], $this->Session->getPosiPk(), 'Dismiss', $_hero_pk, 'G', null);
        return true;
    }

    function setCommand($_posi_pk, $_hero_pk, $_status_cmd, $_cmd_type = 'None', $_notice = true, $force = false): bool
    {
        // status 가 A, status_cmd 가 I 인 상태에서만 가능
        $this->PgGame->query('SELECT t1.lord_pk, t1.posi_pk, t1.status, t1.status_cmd, t1.cmd_type, t2.loyalty FROM my_hero t1, hero t2 WHERE t1.hero_pk = t2.hero_pk AND t1.hero_pk = $1', [$_hero_pk]);
        if (! $this->PgGame->fetch()) {
            throw new ErrorHandler('error', 'Error Occurred. [17039]'); // 영웅을 찾을 수 없음
        }
        $row = $this->PgGame->row;
        if ($row['posi_pk'] != $_posi_pk) {
            // 명령 가능한 상태가 아님
            throw new ErrorHandler('error', 'Error Occurred. [17040]'); // 해당 영웅은 현재 영지의 영웅이 아니므로 명령을 수행 할 수 없습니다.
        }

        if (($row['status'] != 'A' || $row['status_cmd'] != 'I') && !$force) {
            // 명령 가능한 상태가 아님
            throw new ErrorHandler('error', 'Error Occurred. [17041]'); // 해당 영웅은 현재 명령을 수행 할 수 없습니다.
        }

        // 태업 체크
        if ($row['loyalty'] == 0 && !$force) {
            // 태업으로 명령 불가
            throw new ErrorHandler('error', 'Error Occurred. [17042]'); // 해당 영웅은 현재 태업 중 입니다. (영웅의 충성도가 0이면 추가 명령을 내릴 수 없습니다)
        }

        // 상태변경
        $this->PgGame->query('UPDATE my_hero SET status_cmd = $1, cmd_type= $2, last_exp_update_dt = now() WHERE hero_pk = $3', [$_status_cmd, $_cmd_type, $_hero_pk]);

        if ($_notice) {
            $this->Session->sqAppend('HERO', [$_hero_pk => $this->getMyHeroInfo($_hero_pk)], null, $row['lord_pk'], $row['posi_pk']);
        }

        // Log
        $this->classLog();
        $this->Log->setHeroCommand($row['lord_pk'], $row['posi_pk'], 'Command', $_hero_pk, $row['status'], $_status_cmd, $_cmd_type, $row['status_cmd'].'_'.$row['cmd_type']);

        return true;
    }

    function setCommandCmdType($_hero_pk, $_cmd_type, $_notice = true): bool
    {
        $this->PgGame->query('SELECT lord_pk, posi_pk FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        $heroData = $this->PgGame->row;

        // 상태변경
        $this->PgGame->query('UPDATE my_hero SET cmd_type = $1, last_exp_update_dt = now() WHERE hero_pk = $2', [$_cmd_type, $_hero_pk]);
        if ($_notice) {
            $this->Session->sqAppend('HERO', [$_hero_pk => ['cmd_type' => $_cmd_type]], null, $heroData['lord_pk'], $heroData['posi_pk']);
        }
        return true;
    }

    function unsetCommand($_hero_pk, $_notice = true, $_build_time = null): bool
    {
        // status 가 A, status_cmd 가 I 가 아닌 상태에서만 가능
        $this->PgGame->query('SELECT t1.lord_pk, t1.posi_pk, t1.status, t1.status_cmd, t1.cmd_type, t2.loyalty, date_part(\'epoch\', t1.last_exp_update_dt)::integer as update_dt FROM my_hero t1, hero t2 WHERE t1.hero_pk = t2.hero_pk AND t1.hero_pk = $1', [$_hero_pk]);
        if (!$this->PgGame->fetch()) {
            // 영웅을 찾을 수 없음
            throw new ErrorHandler('error', 'Error Occurred. [17043]'); // 영웅을 찾을 수 없음
        }
        $row = $this->PgGame->row;

        if ($row['status'] != 'A' || $row['status_cmd'] == 'I') {
            throw new ErrorHandler('error', 'Error Occurred. [17044]'); // 해당 영웅의 명령해제가 불가능 합니다.
        }

        // 태업 체크
        // 여기서 할 필요 있나? 괜히 복잡해 질듯 (setCommand 로 만족하는게...)

        // skill_exp update
        $this->classHeroSkill();
        $this->HeroSkill->updateCmdCompleteSkillExp($row['lord_pk'], $row['posi_pk'], $_hero_pk, $row['cmd_type'], $_build_time);

        // 상태변경
        $this->PgGame->query('UPDATE my_hero SET status_cmd = $1, cmd_type = $3, last_exp_update_dt = null WHERE hero_pk = $2', ['I', $_hero_pk, 'None']);
        if ($_notice) {
            $this->Session->sqAppend('HERO', [$_hero_pk => $this->getMyHeroInfo($_hero_pk)], null, $row['lord_pk'], $row['posi_pk']);
        }

        // Log
        $this->classLog();
        $this->Log->setHeroCommand($row['lord_pk'], $row['posi_pk'], 'UnCommand', $_hero_pk, $row['status'], 'I', 'None', $row['status_cmd'].'_'.$row['cmd_type']);

        return true;
    }

    function getHeroFreeOpenCnt($_posi_pk): int
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['building']);

        $this->PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, PK_BUILDING_RECEPTIONHALL]);
        $level = $this->PgGame->fetchOne();

        return $_M['BUIL'][PK_BUILDING_RECEPTIONHALL]['level'][$level]['variation_1'];
    }

    // TODO 2중 try catch 확인 바람.
    function setHeroPrize($_hero_pk, $_gold): int
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['officer']);

        $this->PgGame->query('SELECT m_offi_pk, yn_lord, posi_pk, lord_pk FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        $m_offi_pk = $this->PgGame->row['m_offi_pk'];
        $yn_lord = $this->PgGame->row['yn_lord'];
        $_lord_pk = $this->PgGame->row['lord_pk'];
        $_posi_pk = $this->PgGame->row['posi_pk'];

        // 군주는 포상 불가
        if ($yn_lord == 'Y') {
            return 0;
        }

        $employment_fee = $_M['OFFI'][$m_offi_pk]['employment_fee'];
        $loyalty = 0;

        if($_gold < $employment_fee) {
            $loyalty = 1;
        } else if($_gold >= $employment_fee && $_gold < ($employment_fee * 2)) {
            $loyalty = rand(2, 3);
        } else if($_gold >= ($employment_fee * 2) && $_gold < ($employment_fee * 3)) {
            $loyalty = rand(4, 5);
        } else if($_gold >= ($employment_fee * 3) && $_gold < ($employment_fee * 4)) {
            $loyalty = rand(6, 7);
        } else if($_gold >= ($employment_fee * 4)) {
            $loyalty = rand(8, 20);
        }

        // 트랜잭션
        try {
            $this->PgGame->begin();
            $r = $this->PgGame->query('UPDATE hero SET loyalty = loyalty + $1 WHERE hero_pk = $2', [$loyalty, $_hero_pk]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                throw new Exception('loyalty update failed');
            }

            // 포상을 통한 충성도는 최대 99까지
            $this->PgGame->query('UPDATE hero SET loyalty = 99 WHERE hero_pk = $1 AND loyalty > 99', [$_hero_pk]);

            //쿨타임 적용해야 함
            $r = $this->PgGame->query('UPDATE my_hero SET last_prize_dt = now() WHERE hero_pk = $1', [$_hero_pk]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                throw new Exception('cooltime update failed');
            }
            $this->PgGame->commit();
        } catch (Exception $e){
            // 실패, sq 무시
            $this->PgGame->rollback();
            throw new ErrorHandler('error', 'Error Occurred. [17045]'); // 영웅 포상시 에러가 발생했습니다.
            //dubug_mesg남기기
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$_posi_pk.'];hero_pk['.$_hero_pk.']');
        }

        $hero_status = $this->getMyHeroStatus($_hero_pk);
        $this->Session->sqAppend('HERO', [$_hero_pk => $hero_status], null, $_lord_pk, $_posi_pk);

        $this->classLog();
        $this->Log->setHero($this->Session->lord['lord_pk'], $this->Session->getPosiPk(), 'Prize', $_hero_pk, $hero_status['status'], $hero_status['status_cmd'], $hero_status['cmd_type'], 'gold['.$_gold.'];loyalty['.$loyalty.'];');

        return $loyalty;
    }

    function setHeroEnchant($_hero_pk, $_enchant_cnt, $_m_item_pk): array
    {
        global $_M, $NsGlobal;

        // status 가 A, status_cmd 가 I 인 상태에서만 가능
        $this->PgGame->query('SELECT status, status_cmd, cmd_type, lord_pk, posi_pk FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        $status_row = $this->PgGame->row;
        if (!$status_row) {
            // 영웅을 찾을 수 없음
            throw new ErrorHandler('error', 'Error Occurred. [17046]'); // 영웅을 찾을 수 없음
        }

        if ($this->PgGame->row['status'] != 'A' || $this->PgGame->row['status_cmd'] != 'I') {
            // 명령 가능한 상태가 아님
            throw new ErrorHandler('error', 'Error Occurred. [17047]'); // 해당 영웅은 현재 명령을 수행할 수 없습니다.
        }

        $_lord_pk = $this->PgGame->row['lord_pk'];
        $_posi_pk = $this->PgGame->row['posi_pk'];

        // 성공 확률
        $success_per = $_M['HERO_ACQUIRED_ENCHANT_COST'][$_enchant_cnt]['success_per'];
        $range_random_key = rand(1, 1000);

        // 이펙트 적용
        $this->classEffect();
        $result_effect = $this->Effect->getEffectedValue($_posi_pk, ['enchant_rate_increase'], $success_per);
        $success_per = $result_effect['value'];

        // 성공
        if($range_random_key <= $success_per) {
            // 추가능력치 타입 - 2010-10-25, ktlee, 강화방식 변경으로 deprecated
            //$plus_stat = $this->getRandomEnchantPlusStat();

            $query_loyalty = '';
            $add_query_string = 'enchant = enchant + 1, ';
            if ($_m_item_pk == 500383) {
                $plus_stat_arr = ['leadership' => 3, 'mil_force' => 3, 'intellect' => 3, 'politics' => 3, 'charm' => 3];
                $this->PgGame->query('UPDATE lord SET lord_enchant = lord_enchant + 1 WHERE lord_pk = $1', [$this->Session->lord['lord_pk']]);
                $this->Session->setLoginReload();
            } else {
                // 추가 능력치 처리
                $plus_stat_arr = ['leadership' => 0, 'mil_force' => 0, 'intellect' => 0, 'politics' => 0, 'charm' => 0];
                $m = ['plus1' => 3, 'plus2' => 0, 'plus3' => 0];  // 2,3번은 필요 없는게 아닌지...

                $except_stat = null;
                $d = array_keys($plus_stat_arr);
                switch ($_m_item_pk) {
                    case 500001: // 전국책
                        $except_stat = 'politics';
                        break;
                    case 500002: // 태평요술서
                        $except_stat = 'charm';
                        break;
                    case 500003: // 손자병법
                        $except_stat = 'leadership';
                        break;
                    case 500004: // 의천검
                        $except_stat = 'mil_force';
                        break;
                    case 500005: // 맹덕신서
                        $except_stat = 'intellect';
                        break;
                }

                if ($except_stat !== null) {
                    $plus_stat_arr[$except_stat] = $m['plus1'];
                    $key = array_search($except_stat, $d, true);
                    if ($key !== false) {
                        unset($d[$key]);
                    }
                } else {
                    shuffle($d);
                    $plus_stat_arr[$d[0]] = $m['plus1'];
                }
                /*if ($m['plus2']) {
                    $plus_stat_arr[$d[1]] = $m['plus2'];
                }
                if ($m['plus3']) {
                    $plus_stat_arr[$d[2]] = $m['plus3'];
                }*/

                //8번이상 강화 성공시 충성도 100
                if ($_enchant_cnt + 1 >= 8) {
                    $query_loyalty = ', loyalty = 100 ';
                }
            }

            $query_params = [$_hero_pk, $plus_stat_arr['leadership'], $plus_stat_arr['mil_force'], $plus_stat_arr['intellect'], $plus_stat_arr['politics'], $plus_stat_arr['charm']];
            $this->PgGame->query("UPDATE hero SET {$add_query_string} leadership_enchant = leadership_enchant + $2, mil_force_enchant = mil_force_enchant + $3,
intellect_enchant = intellect_enchant + $4, politics_enchant = politics_enchant + $5, charm_enchant = charm_enchant + $6 {$query_loyalty}
WHERE hero_pk = $1", $query_params);

            // 추가 스탯만 플러스 setNewStat 은 호출하지 않음 TODO 괜찮은가?
            $this->PgGame->query('UPDATE my_hero SET 
                   leadership=leadership + $2, mil_force = mil_force + $3, intellect=intellect+$4, politics=politics+$5, charm=charm+$6 WHERE hero_pk = $1', $query_params);


            $hero_status = [$_hero_pk => $this->getMyHeroInfo($_hero_pk)];
            if ($this->Session->getPosiPk() == $_posi_pk) {
                $this->Session->sqAppend('HERO', $hero_status, null, $this->Session->lord['lord_pk'], $_posi_pk);
            }
            $hero_info = $this->getMyHeroInfo($_hero_pk);

            /* TODO 강화성공 채팅 Push
            $this->PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', Array($this->Session->lord['lord_pk']));
            $lord_name = $this->PgGame->fetchOne();

            $NsGlobal->requireMasterData(['hero', 'hero_base']);

             * if ($hero_info['enchant'] > 4) {
                require_once_classes(Array('CChat'));
                $Chat = new CChat();
                $Chat->send_announce_system_about_hero($lord_name.'님이 Lv.'.$hero_info['level'].' '.($_M['HERO_BASE'][$_M['HERO'][$hero_info['m_hero_pk']]['m_hero_base_pk']]['name']).' 영웅 카드의 강화를 성공하였습니다. 축하합니다.');
            }*/

            // 퀘스트 체크
            $this->classQuest();
            $this->Quest->conditionCheckQuest($this->Session->lord['lord_pk'], ['quest_type' => 'hero', 'hero_type' => 'enchant', 'hero_pk' => $_hero_pk]);

            /* TODO 보고서는 안보내고 있었네?
            $this->PgGame->query('SELECT posi_pk FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
            $hero_exist_posi_pk = $this->PgGame->fetchOne();

            $this->PgGame->query('SELECT title FROM territory WHERE posi_pk = $1', [$hero_exist_posi_pk]);
            $hero_exist_posi_name = $this->PgGame->fetchOne();

            $this->classReport();
            if ($_m_item_pk == 500383) {
                $z_title = '군주 각성 결과 보고';
            } else {
                $z_title = '영웅 강화 결과 보고';
            }

            $hero_name = ($_M['HERO_BASE'][$_M['HERO'][$hero_info['m_hero_pk']]['m_hero_base_pk']]['name']).' Lv.'.$hero_info['level'];

            $z_from = Array('posi_pk' => $hero_exist_posi_pk, 'posi_name' => $hero_exist_posi_name . ' (' . $hero_exist_posi_pk . ')', 'lord_name' => $lord_name);
            $z_to = Array('lord_name' => $hero_name, 'posi_name' => $hero_name);

            if ($_m_item_pk == 500383) {
                $z_summary = $hero_name.' 각성에 성공하였습니다.';
            } else {
                $z_summary = $hero_name.' 강화에 성공하였습니다.';
            }

            $z_content = Array();
            $z_content['hero_pk'] = $_hero_pk;
            $z_content['m_hero_pk'] = $hero_info['m_hero_pk'];

            if ($_m_item_pk == 500383)
            {
                $z_content['enchant_count'] = $_enchant_cnt + 1;
                $z_content['lord_enchant'] = 'Y';
            } else {
                $z_content['enchant_count'] = $hero_info['enchant'];
                $z_content['plusstat'] = $plus_stat_arr;
            }

            $this->Report->setReport($this->Session->lord['lord_pk'], 'misc', 'hero_enchant_suc', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));*/

            /*if ($_enchant_cnt + 1 == 8)
            {
                // TODO - $_lord_pk 검증 필요.
                $Quest->conditionCheckQuest($this->Session->lord['lord_pk'], Array('quest_type' => 'hero', 'hero_type' => 'enchant_suc_8'));
            }*/

            // 로그가 안남는 문제를 해결하기 위해
            $_m_item_pk = (!$_m_item_pk) ? 0 : $_m_item_pk;

            $this->classLog();
            $description = 'leadership:'.$plus_stat_arr['leadership'].',mil_force:'.$plus_stat_arr['mil_force'].',intellect:'.$plus_stat_arr['intellect'].',politics:'.$plus_stat_arr['politics'].',charm:'.$plus_stat_arr['charm'];
            $this->Log->setHeroEnchant($this->Session->lord['lord_pk'], $_posi_pk, 'Success', $_hero_pk, $hero_status[$_hero_pk]['status'], $hero_status[$_hero_pk]['status_cmd'], $hero_status[$_hero_pk]['cmd_type'], $_enchant_cnt + 1, $_m_item_pk, $description, $hero_info['m_hero_pk']);

            return ['result' => true, 'hero_info' => $hero_info, 'enchant_stat' => $plus_stat_arr];
        } else {
            //실패
            // 강화 실패 막아둠 , 설명은 남아있음 , 나중에 다시 살릴 수도 있음 , 다시 살릴때 실패 확률 조정 필요 , 원래 값은 10이 아니고 5
            /*if($_enchant_cnt > 10) {
                $range_random_key = rand(1, 100);
                $lost_hero_per = $_M['HERO_ACQUIRED_ENCHANT_COST'][$_enchant_cnt + 1]['lost_hero_per'];

                if($range_random_key <= $lost_hero_per) {
                    //상실
                    $ret = $this->setEnchantLostHero($_hero_pk);
                    $lost_hero = true;
                }
            }*/

            $hero_status = [$_hero_pk => $this->getMyHeroStatus($_hero_pk)];
            if ($this->Session->getPosiPk() == $_posi_pk) {
                $this->Session->sqAppend('HERO', $hero_status, null, $this->Session->lord['lord_pk'], $_posi_pk);
            }
            $hero_info = $this->getMyHeroInfo($_hero_pk);

            /* TODO 보고서는 안보내고 있었음. 코드 삭제도 고려해보자.
             * $this->PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', Array($this->Session->lord['lord_pk']));
            $lord_name = $this->PgGame->fetchOne();

            require_once_caches(Array('hero', 'hero_base'));

            $this->Db->query('SELECT posi_pk FROM my_hero WHERE hero_pk = $1', Array($_hero_pk));
            $hero_exist_posi_pk = $this->Db->fetchOne();

            $this->Db->query('SELECT title FROM territory WHERE posi_pk = $1', Array($hero_exist_posi_pk));
            $hero_exist_posi_name = $this->Db->fetchOne();

            $this->getReportClass();
            if ($_m_item_pk == 500383) {
                $z_title = '군주 각성 결과 보고';
            } else {
                $z_title = '영웅 강화 결과 보고';
            }

            $hero_name = ($_M['HERO_BASE'][$_M['HERO'][$hero_info['m_hero_pk']]['m_hero_base_pk']]['name']).' Lv.'.$hero_info['level'];

            if ($_m_item_pk == 500383) {
                $z_summary = $hero_name.' 각성에 실패하였습니다.';
            } else {
                $z_summary = $hero_name.' 강화에 실패하였습니다.';
            }

            $z_from = Array('posi_pk' => $hero_exist_posi_pk, 'posi_name' => $hero_exist_posi_name . ' (' . $hero_exist_posi_pk . ')', 'lord_name' => $lord_name);
            $z_to = Array('lord_name' => $hero_name, 'posi_name' => $hero_name);

            $z_content = Array();
            $z_content['hero_pk'] = $_hero_pk;
            $z_content['m_hero_pk'] = $hero_info['m_hero_pk'];

            if ($_m_item_pk == 500383) {
                $z_content['enchant_count'] = $_enchant_cnt;
                $z_content['lord_enchant'] = 'Y';
            } else {
                $z_content['enchant_count'] = $hero_info['enchant'];
            }

            $this->Report->setReport($this->Session->lord['lord_pk'], 'misc', 'hero_enchant_fal', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));*/

            // 로그가 안남는 문제를 해결하기 위해
            $_m_item_pk = $_m_item_pk ?? 0;

            // TODO 강화는 따로 처리
            $this->classLog();
            $description = '';
            $this->Log->setHeroEnchant($this->Session->lord['lord_pk'], $_posi_pk, 'Failure', $_hero_pk, $hero_status[$_hero_pk]['status'], $hero_status[$_hero_pk]['status_cmd'], $hero_status[$_hero_pk]['cmd_type'], $_enchant_cnt, $_m_item_pk, $description, $hero_info['m_hero_pk']);

            return ['result' => false];
        }
    }

    // TODO 나중에 다시 만드는 한이 있더라도 나중에 사용안하는 함수를 확인하여 모두 제거해줄 필요가 있어보임.
    /*function setEnchantLostHero($_hero_pk): bool
    {
        // status 가 G, status_cmd 가 I 인 상태에서만 가능
        $this->PgGame->query('SELECT status, status_cmd, lord_pk, posi_pk FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        if (!$this->PgGame->fetch()) {
            throw new ErrorHandler('error', '영웅을 찾을 수 없음.');
        }
        $row = $this->PgGame->row;

        // 삭제
        $this->PgGame->query('DELETE FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);

        // 카드덱에서 제거하기
        if ($this->PgGame->getAffectedRows() == 1) {
            $this->Session->sqAppend('HERO', [$_hero_pk => ['status' => 'NULL']], null, $row['lord_pk'], $row['posi_pk']);
        }
        return true;
    }*/

    function getEnchantNeedItem(int $_enchant_cnt, string $_rare_type): int
    {
        $need_item = 1;
        $_enchant_cnt = $_enchant_cnt + 1;
        if ($_rare_type == '1' && $_enchant_cnt == 10) {
            $need_item = 2;
        } else if ($_rare_type == '2' && in_array($_enchant_cnt, [9, 10])) {
            $need_item = 2;
        } else if ($_rare_type == '3' && in_array($_enchant_cnt, [8, 9, 10])) {
            $need_item = 2;
        } else if ($_rare_type == '4' && in_array($_enchant_cnt, [7, 8, 9, 10])) {
            $need_item = 2;
        } else if ($_rare_type == '5' && in_array($_enchant_cnt, [6, 7, 8, 9, 10])) {
            $need_item = 2;
        } else if ($_rare_type == '6' && in_array($_enchant_cnt, [5, 6, 7, 8, 9, 10])) {
            $need_item = 2;
        } else if ($_rare_type == '7' && in_array($_enchant_cnt, [4, 5, 6, 7, 8, 9, 10])) {
            $need_item = 2;
        }
        return $need_item;
    }

    function getEnchantNeedPrice($_enchant_cnt, $_rare_type): int
    {
        $need_price = 0;
        $_enchant_cnt = $_enchant_cnt + 1;
        switch($_rare_type) {
            case '1' : $need_price = 4900; break;
            case '2' : $need_price = 5600; break;
            case '3' : $need_price = 6300; break;
            case '4' : $need_price = 7000; break;
            case '5' : $need_price = 8400; break;
            case '6' : $need_price = 9800; break;
            case '7' : $need_price = 11200; break;
        }
        return (INT)($need_price * $_enchant_cnt);
    }

    function setTerritory($_hero_pk, $_posi_pk): void
    {
        $this->PgGame->query('SELECT lord_pk, posi_pk FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        $lord_pk = $this->PgGame->row['lord_pk'];
        $origin_posi_pk = $this->PgGame->row['posi_pk'];

        // 영웅 이동
        $query_params = [$_posi_pk, $_hero_pk];
        $this->PgGame->query('UPDATE my_hero SET posi_pk = $1 WHERE hero_pk = $2', $query_params);
        $this->PgGame->query('UPDATE lord SET main_posi_pk = $1 WHERE lord_hero_pk = $2', $query_params);
        if ($this->PgGame->getAffectedRows()) {
            $this->Session->setLoginReload();
        }

        // 영웅정보 push
        // src (카드덱에서 제거)
        $this->Session->sqAppend('HERO', [$_hero_pk => ['status' => 'NULL']], null, $lord_pk, $origin_posi_pk);

        // dst (카드덱에 추가)
        $this->Session->sqAppend('HERO', [$_hero_pk => $this->getMyHeroInfo($_hero_pk)], null, $lord_pk, $_posi_pk);
    }

    function setUnreadHeroCnt($_lord_pk): void
    {
        $this->PgGame->query('SELECT COUNT(hero_pk) FROM my_hero WHERE lord_pk = $1 AND status = $2', [$_lord_pk, 'G']);
        $this->Session->sqAppend('LORD', ['unread_guest_cnt' => $this->PgGame->fetchOne()], null, $_lord_pk);

        $this->PgGame->query('SELECT COUNT(hero_pk) FROM my_hero WHERE lord_pk = $1 AND status = ANY($2)', [$_lord_pk, '{'. implode(',', ['C','S','V']). '}']);
        $cnt = $this->PgGame->fetchOne();
        $this->PgGame->query('UPDATE lord SET unread_hero_cnt = $1, unread_hero_last_up_dt = now() WHERE lord_pk = $2', [$cnt, $_lord_pk]);
        $this->Session->sqAppend('LORD', ['unread_hero_cnt' => $cnt], null, $_lord_pk);
    }

    /*function setUnreadGuestHeroCnt($_lord_pk): void
    {
        $this->PgGame->query('SELECT COUNT(hero_pk) FROM my_hero WHERE lord_pk = $1 AND status = $2', [$_lord_pk, 'G']);
        $this->Session->sqAppend('LORD', ['unread_guest_cnt' => $this->PgGame->fetchOne()], null, $_lord_pk);
    }*/

    /*function setUnreadOverRankHeroCnt($_lord_pk): void
    {
        $this->PgGame->query('SELECT COUNT(t1.hero_pk) FROM my_hero t1, hero t2, m_hero t3
                         WHERE t1.lord_pk = $1 AND t1.hero_pk = t2.hero_pk AND t2.m_hero_pk = t3.m_hero_pk AND
                               t1.status IN ($2, $3, $4) AND t3.over_type = $5', [$_lord_pk, 'C', 'S', 'V', 'Y']);
        $cnt = $this->PgGame->fetchOne();

        // LP 입력
        $this->Session->sqAppend('LORD', ['unread_overrank_cnt' => $cnt], null, $_lord_pk);
    }*/

    function cancelEncounter($_posi_pk): true
    {
        $this->PgGame->query('SELECT time_pk, cmd_hero_pk FROM hero_encounter WHERE posi_pk = $1 AND status = $2', [$_posi_pk, 'P']);
        $this->PgGame->fetch();
        $time_pk = $this->PgGame->row['time_pk'];
        $cmd_hero_pk = $this->PgGame->row['cmd_hero_pk'];

        $this->PgGame->query('DELETE FROM hero_encounter WHERE posi_pk = $1 AND status = $2', [$_posi_pk, 'P']);
        if (! $time_pk) {
            return true;
        }
        // Timer 취소
        $this->classTimer();
        $this->Timer->cancel($time_pk);

        // 영웅 명령해제
        if ($cmd_hero_pk) {
            $this->unsetCommand($cmd_hero_pk);
        }
        return true;
    }

    function cancelInvitation($_posi_pk): true
    {
        $this->PgGame->query('SELECT time_pk, queue_pk FROM timer WHERE posi_pk = $1 AND queue_type = $2 AND status = $3', [$_posi_pk, 'I', 'P']);
        $this->PgGame->fetch();
        $time_pk = $this->PgGame->row['time_pk'];
        $queue_pk = $this->PgGame->row['queue_pk'];

        $this->PgGame->query('SELECT hero_invi_pk, cmd_hero_pk FROM hero_invitation WHERE hero_invi_pk = $1 AND status = $2 ORDER BY hero_invi_pk DESC', [$queue_pk, 'P']);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;

        // TODO 트랜잭션 필요한지 확인해보자.
        if ($row['hero_invi_pk']) {
            $this->PgGame->query('DELETE FROM hero_invitation WHERE hero_invi_pk = $1 AND status = $2', [$row['hero_invi_pk'], 'P']);
            $this->PgGame->query('UPDATE hero_encounter SET invitation_cnt = invitation_cnt - 1 WHERE hero_enco_pk = $1', [$queue_pk]);
            if ($time_pk) {
                // Timer 도 취소
                $this->classTimer();
                $this->Timer->cancel($time_pk);
            }

            // 영웅 명령해제
            if ($row['cmd_hero_pk']) {
                $this->unsetCommand($row['cmd_hero_pk']);
            }
        }

        return true;
    }

    function cancelBid($_posi_pk): void
    {
        $this->PgGame->query('SELECT hero_free_pk FROM hero_free_bid WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;

        foreach($rows AS $v) {
            $this->PgGame->query('UPDATE hero_free SET bid_cnt = bid_cnt - 1 WHERE hero_free_pk = $1', [$v['hero_free_pk']]);
        }

        $this->PgGame->query('DELETE FROM hero_free_bid WHERE posi_pk = $1', [$_posi_pk]);
    }

    // 전체 영웅 해임
    function setDismissAllHero($_lord_pk): true
    {
        // 상태변경 - status 를 G로 last_dismiss_dt 를 현재로 m_offi_pk 와 posi_pk 를 NULL 로...
        // 수정 101021 재현 -> 방랑 영주 상태일때 관직 박탈은 아니라고 함
        $this->PgGame->query('UPDATE my_hero SET status = $1, posi_pk = $2 WHERE lord_pk = $3', ['G', null, $_lord_pk]);
        return true;
    }

    // 영지 상실로 인해 영웅들 영지 옮기기
    function setMoveHero($_posi_pk, $_main_posi_pk): true
    {
        //'Recal'인 것을 제외한 나머지 영웅 상태를 'I'로...
        $this->PgGame->query('SELECT hero_pk, status_cmd FROM my_hero WHERE posi_pk = $1 AND cmd_type != $2', [$_posi_pk, 'Recal']);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;

        $this->classBdic();
        foreach($rows AS $v) {
            if ($v['status_cmd'] == 'A') {
                $this->PgGame->query('SELECT in_castle_pk FROM building_in_castle WHERE posi_pk = $1 And assign_hero_pk = $2', [$_posi_pk, $v['hero_pk']]);
                $in_cast_pk = $this->PgGame->fetchOne();
                $this->Bdic->heroUnAssign($_posi_pk, $in_cast_pk);
            }

            if ($v['status_cmd'] != 'I' && $v['status_cmd'] != 'T') {
                $this->unsetCommand($v['hero_pk']);
            }
        }

        // 메인 posi_pk로 변경
        $this->PgGame->query('UPDATE my_hero SET posi_pk = $2 WHERE posi_pk = $1', [$_posi_pk, $_main_posi_pk]);
        return true;
    }

    // 통솔이 가장 높은 영웅
    function getHeroMaxLeadership($_posi_pk): int|null
    {
        $this->PgGame->query('SELECT hero_pk FROM my_hero WHERE posi_pk = $1 AND status = $2 AND status_cmd = $3 ORDER BY leadership DESC LIMIT 1', [$_posi_pk, 'A', 'I']);
        return $this->PgGame->fetchOne();
    }

    // 영웅 레어도
    function getHeroRare($_hero_pk): string
    {
        $this->PgGame->query('SELECT rare_type FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        return $this->PgGame->fetchOne();
    }

    // 충성도 감소
    function setMyHeroLoyalty($_lord_pk, $_posi_pk, $_hero_pk, $_desc_loyalty): true
    {
        $this->PgGame->query('SELECT yn_lord FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        if ($this->PgGame->fetchOne() == 'Y') {
            return true;
        }
        $this->PgGame->query('UPDATE hero SET loyalty = loyalty - $1 WHERE hero_pk = $2', [$_desc_loyalty, $_hero_pk]);
        $hero_data[$_hero_pk] = $this->getMyHeroStatus($_hero_pk);
        if ($hero_data[$_hero_pk] && $hero_data[$_hero_pk]['loyalty'] < 0) {
            $this->PgGame->query('UPDATE hero SET loyalty = $1 WHERE hero_pk = $2', [0, $_hero_pk]);
            $hero_data[$_hero_pk]['loyalty'] = 0;
        }
        $this->Session->sqAppend('HERO', $hero_data, null, $_lord_pk, $_posi_pk);
        return true;
    }

    // 강화 수치, 강화 횟수 초기화
    function initMyHeroEnchant($_hero_pk, $_lord_pk, $_posi_pk, $type = null): bool
    {
        // 전체 수치 초기화
        $this->PgGame->query('SELECT yn_lord FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        if ($this->PgGame->fetchOne() == 'Y') {
            $enchant_stat = $this->Session->lord['lord_enchant'] * 3;
            $sql = "UPDATE hero SET enchant = 0, leadership_enchant = {$enchant_stat}, mil_force_enchant = {$enchant_stat},
	intellect_enchant = {$enchant_stat}, politics_enchant = {$enchant_stat}, charm_enchant = {$enchant_stat}
WHERE hero_pk = $1";
        } else {
            $sql = 'UPDATE hero SET enchant = 0, leadership_enchant = 0, mil_force_enchant = 0,
	intellect_enchant = 0, politics_enchant = 0, charm_enchant = 0 WHERE hero_pk = $1';
        }
        $this->PgGame->query($sql, [$_hero_pk]);
        if ($this->PgGame->getAffectedRows() == 1) {
            $this->PgGame->query('SELECT m_offi_pk FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
            $_m_offi_pk = $this->PgGame->fetchOne();
            $this->setNewStat($_hero_pk, $_m_offi_pk);
            $this->Session->sqAppend('HERO', [$_hero_pk => $this->getMyHeroInfo($_hero_pk)], null, $_lord_pk, $_posi_pk);
            return true;
        }
        return false;
    }

    // 그룹별 영웅 돌려주기
    function getHeroGroupByType($_group_type, $_lord_pk): array
    {
        $result = [];
        $this->PgGame->query('SELECT t2.hero_pk, t2.posi_pk, t1.m_hero_pk, t3.title, t2.group_type, t2.group_order
FROM hero t1, my_hero t2 LEFT OUTER JOIN territory t3 ON t2.posi_pk = t3.posi_pk
WHERE t2.lord_pk = $1 AND t1.hero_pk = t2.hero_pk AND t2.group_type = $2 AND t2.group_order > 0
ORDER BY group_order LIMIT 9', [$_lord_pk, $_group_type]);
        $this->PgGame->fetchAll();
        if (count($this->PgGame->rows) > 0) {
            foreach($this->PgGame->rows as $row) {
                $result[$row['group_order']] = $row;
            }
        }
        return $result;
    }

    // 선택한 영웅의 그룹과 순서 정하기
    function setHeroGroup($_hero_pk, $_lord_pk, $_group_type = null, $_group_order = null): int
    {
        global $NsGlobal;
        if ($_group_type != null) {
            // 이미 그룹에 있는지
            /*$this->PgGame->query('SELECT group_type FROM my_hero WHERE lord_pk = $1 AND hero_pk = $2', [$_lord_pk, $_hero_pk]);
            $this->PgGame->fetch();
            if ($this->PgGame->row['group_type'] != null && $this->PgGame->row['group_type'] !== $_group_type) {
                $NsGlobal->setErrorMessage('이미 다른 그룹에 지정된 영웅입니다.<br />그룹 해제를 먼저 진행한 후 그룹을 지정해주세요.');
                return 3;
            }*/

            $this->PgGame->query('SELECT hero_pk FROM my_hero WHERE lord_pk = $1 AND group_type = $2 AND group_order = $3', [$_lord_pk, $_group_type, $_group_order]);
            if ($this->PgGame->fetch()) {
                $NsGlobal->setErrorMessage('Error Occurred. [17059]');
                return 2;
            }
        }

        $this->PgGame->query('UPDATE my_hero SET group_type = $1, group_order = $2 WHERE lord_pk = $3 AND hero_pk = $4', [$_group_type, $_group_order, $_lord_pk, $_hero_pk]);
        if ($this->PgGame->getAffectedRows() == 1) {
            $NsGlobal->setErrorMessage('Error Occurred. [17060]');
        }
        return 0;
    }

    // 선택한 영웅의 그룹을 해제하기
    function unsetHeroGroup($_hero_pk, $_lord_pk): int
    {
        return $this->setHeroGroup($_hero_pk, $_lord_pk);
    }

    // 자신이 현재 등용 중인 모든 영웅들 불러오기.
    function getMyAppoQueuHeroList($_lord_pk): array
    {
        return $this->getMyHeroes($_lord_pk, ['A'], null);
    }

    // 가챠폰 이벤트에 사용될 함수
    function getHeroPK($_m_hero_base_pk, $level): false|int|string
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero']);
        foreach($_M['HERO'] AS $k => $v) {
            if ($v['m_hero_base_pk'] == $_m_hero_base_pk && $v['level'] == $level) {
                return $k;
            }
        }
        return false;
    }

    function getCollectionTotalCount($_rare, $_m_item_pk): int
    {
        $query_params = [$_m_item_pk, 'N'];
        $add_query_string = '';
        if ($_rare) {
            $add_query_string = ' AND b.rare_type = $3';
            $query_params[] = $_rare;
        }
        $this->PgGame->query("SELECT COUNT(a.m_hero_base_pk) FROM m_hero_collection_combi_item a, m_hero_base b
WHERE a.m_item_pk = $1 AND b.yn_new_gacha = $2
AND a.m_hero_base_pk = b.m_hero_base_pk{$add_query_string}", $query_params);
        return $this->PgGame->fetchOne();
    }

    function getGachaponEventTotalCount($_rare): int
    {
        if ($_rare) {
            $this->PgGame->query('SELECT COUNT(m_hero_pk) FROM gachapon_event WHERE orderno = $1', [$_rare]);
        } else {
            $this->PgGame->query('SELECT COUNT(m_hero_pk) FROM gachapon_event');
        }
        return $this->PgGame->fetchOne();
    }

    function getGachaponEventBuyCount(): int
    {
        $this->PgGame->query('SELECT gachapon_buy_cnt FROM lord WHERE lord_pk = $1', [$this->Session->lord['lord_pk']]);
        return $this->PgGame->fetchOne();
    }

    function getGachaponEventCardTotal(): int
    {
        $this->PgGame->query('SELECT (sum(gach_event_default_count) - sum(gach_event_buy_count)) as cnt FROM gachapon_event');
        return $this->PgGame->fetchOne();
    }

    function getGachaponEventCardMax(): int
    {
        $this->PgGame->query('SELECT sum(gach_event_default_count) as max FROM gachapon_event');
        return $this->PgGame->fetchOne();
    }

    function getGachaponHero($_m_item_pk): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_base', 'gachapon']);
        $sel_level = $this->getRandomLevel('GACHAPON', $this->Session->lord['level']);
        $sel_rare = $this->getRandomRare($sel_level, 'GACHAPON');
        // 아이템에 따른 해당 영웅 뽑기.
        $gachapon_hero_arr = [];
        // 레어도가 같은 영웅 들을 정리하고
        foreach($_M['GACH'][$_m_item_pk] as $k => $v) {
            if ($_M['HERO_BASE'][$k]['rare_type'] == $sel_rare) {
                $gachapon_hero_arr[] = $k;
            }
        }
        // 정리한 영웅을 셔플
        shuffle($gachapon_hero_arr);
        // 셔플한 영웅들 중 첫 영웅을 선택함
        return ['m_hero_base_pk' => $gachapon_hero_arr[0], 'level' => $sel_level, 'rare' => $sel_rare];
    }

    // TODO 이거 바로 위 함수와 타입만 다르고 같은 코드 아닌가?
    function getGachaponWomanHero($_m_item_pk): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_base', 'gachapon']);
        $sel_level = $this->getRandomLevel('GACHAPON_WOMAN', $this->Session->lord['level']);
        $sel_rare = $this->getRandomRare($sel_level, 'GACHAPON_WOMAN');
        // 아이템에 따른 해당 영웅 뽑기.
        $gachapon_hero_arr = [];
        // 레어도가 같은 영웅 들을 정리하고
        foreach($_M['GACH'][$_m_item_pk] as $k => $v) {
            if ($_M['HERO_BASE'][$k]['rare_type'] == $sel_rare) {
                $gachapon_hero_arr[] = $k;
            }
        }
        // 정리한 영웅을 셔플
        shuffle($gachapon_hero_arr);
        // 셔플한 영웅들 중 첫 영웅을 선택함
        return ['m_hero_base_pk' => $gachapon_hero_arr[0], 'level' => $sel_level, 'rare' => $sel_rare];
    }

    // TODO 이거 바로 위 함수와 타입만 다르고 같은 코드 아닌가?
    function getNewCollectHero($_m_item_pk, $_acquired_type): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_base', 'hero_collection_combi_item']);
        $sel_level = $this->getRandomLevel($_acquired_type, $this->Session->lord['level']);
        $sel_rare = $this->getRandomRare($sel_level, $_acquired_type);
        // 아이템에 따른 해당 영웅 뽑기.
        $gachapon_hero_arr = [];
        // 레어도가 같은 영웅 들을 정리하고
        foreach($_M['HERO_COLL_COMB_ITEM'][$_m_item_pk] as $k => $v) {
            if ($_M['HERO_BASE'][$k]['rare_type'] == $sel_rare) {
                if ($_M['HERO_BASE'][$k]['yn_new_gacha'] == 'N' && $_M['HERO_BASE'][$k]['yn_modifier'] == 'N') {
                    $gachapon_hero_arr[] = $k;
                }
            }
        }
        // 정리한 영웅을 셔플
        shuffle($gachapon_hero_arr);
        // 셔플한 영웅들 중 첫 영웅을 선택함
        return ['m_hero_base_pk' => $gachapon_hero_arr[0], 'level' => $sel_level, 'rare' => $sel_rare];
    }

    function getGachaponEvent($_m_item_pk): false|array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero', 'hero_base']);

        // TODO return false 처리 필요. 스케쥴 처리 필요.

        // 이벤트 아이템이 아닐 경우.
        if ($_m_item_pk != 500390) {
            throw new ErrorHandler('error', 'Error Occurred. [17048]'); // 잘못된 아이템입니다.
        }

        $gachapon_event_cnt = $this->getGachaponEventCardTotal(); // 남은수량
        $gachapon_event_max = $this->getGachaponEventCardMax(); // 총수량

        // 총 수량이 허용치 보다 적거나 같은때 부터 7성 판매 시작.
        if ($gachapon_event_cnt < 1) {
            throw new ErrorHandler('error', 'Error Occurred. [17049]'); // 상품이 매진 되었습니다.
        } else {
            // 7성 판매 제한 수량
            $this->PgGame->query('SELECT gachapon_buy_limit_cnt FROM m_preference');
            $gachapon_buy_limit_cnt = $this->PgGame->fetchOne();

            // 6성 영웅이 몇장 팔렸는지 체크
            $this->PgGame->query('SELECT sum(gach_event_buy_count) as cnt FROM gachapon_event WHERE orderno = $1', [6]);
            $check_sell = $this->PgGame->fetchOne();

            $check_max = $gachapon_event_max - $gachapon_event_cnt; // 판매된 갯수

            $sql = 'SELECT m_hero_pk FROM gachapon_event WHERE gach_event_default_count > gach_event_buy_count';

            $sql_7star = ' )';
            if ($check_max >= $gachapon_buy_limit_cnt) {
                // 7성 조건에 포함시키기
                $sql_7star = ' OR orderno > 6 )';
            }

            if ($check_max < 100 && $check_sell < 1) {
                $sql2 = ' AND ( orderno < 7';
            } else if ($check_max >= 100 && $check_max < 200 && $check_sell < 2) {
                $sql2 = ' AND ( orderno < 7';
            } else if ($check_max >= 200 && $check_max < 300 && $check_sell < 3) {
                $sql2 = ' AND ( orderno < 7';
            } else if ($check_max >= 300 && $check_max < $gachapon_buy_limit_cnt) {
                $sql2 = ' AND ( orderno < 7';
            } else if ($check_max < $gachapon_buy_limit_cnt) {
                $sql2 = ' AND ( orderno < 6';
            } else {
                $sql2 = ' AND ( orderno < 6';
            }
        }
        $result = $this->PgGame->query("{$sql}{$sql2}{$sql_7star} ORDER BY RANDOM() LIMIT 1 FOR UPDATE");
        if (! $result) {
            throw new ErrorHandler('error', 'Error Occurred. [17050]'); // 처리 중 오류가 발생
        } else {
            $m_hero_pk = $this->PgGame->fetchOne();
            // 해당 영웅 판매 갯수 증가 (= 남은 수량 차감)
            $ret = $this->PgGame->query('UPDATE gachapon_event SET gach_event_buy_count = gach_event_buy_count + 1 WHERE m_hero_pk = $1 AND gach_event_default_count > gach_event_buy_count', [$m_hero_pk]);
            if (!$ret || $this->PgGame->getAffectedRows() == 0) {
                throw new ErrorHandler('error', 'Error Occurred. [17051]');
            }

            // 7성의 경우 당첨 군주명을 표기해 주기위하여 업데이트
            if ($_M['HERO_BASE'][$_M['HERO'][$m_hero_pk]['m_hero_base_pk']]['rare_type'] > 6) {
                $result = $this->PgGame->query('UPDATE gachapon_event SET lord_name = $1 WHERE m_hero_pk = $2', [$this->Session->lord['lord_name'], $m_hero_pk]);
                if (! $result) {
                    throw new ErrorHandler('error', 'Error Occurred. [17052]'); // 처리 중 오류가 발생
                }
            }

            // 군주 판매 횟수 증가
            $result = $this->PgGame->query('UPDATE lord SET gachapon_buy_cnt = gachapon_buy_cnt + $1 WHERE lord_pk = $2', [1, $this->Session->lord['lord_pk']]);
            if (! $result) {
                throw new ErrorHandler('error', 'Error Occurred. [17053]'); // 처리 중 오류가 발생
            }

            return [
                'm_hero_base_pk' => $_M['HERO'][$m_hero_pk]['m_hero_base_pk'],
                'level' => $_M['HERO'][$m_hero_pk]['level'],
                'rare' => $_M['HERO_BASE'][$_M['HERO'][$m_hero_pk]['m_hero_base_pk']]['rare_type']
            ];
        }
    }

    function getPointHeroPK($_hero_pk_arr, $_type, $_level): bool|array
    {
        $z = '';
        if (COUNT($_hero_pk_arr) > 0) {
            $z = 'AND d.m_hero_base_pk NOT IN ('. implode(",", $_hero_pk_arr) .')';
        }
        // 영웅 뽑기
        $this->PgGame->query("SELECT a.hero_pk, d.m_hero_base_pk
FROM m_npc_hero as a, hero as b, m_hero as c, m_hero_base as d
WHERE a.type = $1 AND a.level = $2 AND a.hero_pk = b.hero_pk AND b.m_hero_pk = c.m_hero_pk AND c.m_hero_base_pk = d.m_hero_base_pk
$z ORDER BY random() LIMIT 1", [$_type, $_level]);
        $this->PgGame->fetch();
        return $this->PgGame->row;
    }

    // 신규 가챠폰 이벤트에 사용될 함수
    function getNewHeroPK($_m_hero_base_pk, $level): false|int|string
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero']);
        foreach($_M['HERO'] AS $k => $v) {
            if ($v['m_hero_base_pk'] == $_m_hero_base_pk && $v['level'] == $level) {
                return $k;
            }
        }
        return false;
    }

    function getNewGachaponEventTotalCount($_rare): int
    {
        if ($_rare) {
            $this->PgGame->query('SELECT COUNT(m_hero_pk) FROM new_gachapon_event WHERE orderno = $1', [$_rare]);
        } else {
            $this->PgGame->query('SELECT COUNT(m_hero_pk) FROM new_gachapon_event');
        }
        return $this->PgGame->fetchOne();
    }

    function getNewGachaponEventBuyCount(): int
    {
        $this->PgGame->query('SELECT new_gachapon_buy_cnt FROM lord WHERE lord_pk = $1', [$this->Session->lord['lord_pk']]);
        return $this->PgGame->fetchOne();
    }

    function getNewGachaponEventCardTotal(): int
    {
        $this->PgGame->query('SELECT (sum(gach_event_default_count) - sum(gach_event_buy_count)) as cnt FROM new_gachapon_event');
        return $this->PgGame->fetchOne();
    }

    function getNewGachaponEventCardMax(): int
    {
        $this->PgGame->query('SELECT sum(gach_event_default_count) as max FROM new_gachapon_event');
        return $this->PgGame->fetchOne();
    }

    // TODO 위쪽에 동일한 코드가 있을텐데... 확인 후 통합 하는 함수 구상해라.
    function getNewGachaponHero($_m_item_pk): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_base', 'gachapon']);
        $sel_level = $this->getRandomLevel('GACHAPON', $this->Session->lord['level']);
        $sel_rare = $this->getRandomRare($sel_level, 'GACHAPON');
        // 아이템에 따른 해당 영웅 뽑기.
        $gachapon_hero_arr = [];
        // 레어도가 같은 영웅 들을 정리하고
        foreach($_M['GACH'][$_m_item_pk] as $k => $v) {
            if ($_M['HERO_BASE'][$k]['rare_type'] == $sel_rare) {
                $gachapon_hero_arr[] = $k;
            }
        }
        // 정리한 영웅을 셔플
        shuffle($gachapon_hero_arr);
        // 셔플한 영웅들 중 첫 영웅을 선택함
        return ['m_hero_base_pk' => $gachapon_hero_arr[0], 'level' => $sel_level, 'rare' => $sel_rare];
    }

    // TODO 위에 동일한 함수가 있을것이야....
    function getNewGachaponEvent($_m_item_pk): array|false
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['hero', 'hero_base']);

        // TODO return false 처리 필요. 스케쥴 처리 필요.

        // 이벤트 아이템이 아닐 경우.
        if ($_m_item_pk != 500532) {
            throw new ErrorHandler('error', 'Error Occurred. [17054]'); // 잘못된 아이템입니다.
        }

        $gachapon_event_cnt = $this->getNewGachaponEventCardTotal(); // 남은수량
        $gachapon_event_max = $this->getNewGachaponEventCardMax(); // 총수량

        // 총 수량이 허용치 보다 적거나 같은때 부터 7성 판매 시작.
        if ($gachapon_event_cnt < 1) {
            throw new ErrorHandler('error', $i18n->t('msg_sold_out')); // 매진 되었습니다.
        } else {
            // 영웅 뽑기
            // 7성 판매 제한 수량
            $this->PgGame->query('SELECT new_gachapon_buy_limit_cnt FROM m_preference');
            $new_gachapon_buy_limit_cnt = $this->PgGame->fetchOne();

            // 6성 영웅이 몇장 팔렸는지 체크
            $this->PgGame->query('SELECT sum(gach_event_buy_count) as cnt FROM new_gachapon_event WHERE orderno = $1', [6]);
            $check_sell = $this->PgGame->fetchOne();  // 6성 판매 개수

            $check_max = $gachapon_event_max - $gachapon_event_cnt; // 총 판매 갯수

            $sql = 'SELECT m_hero_pk FROM new_gachapon_event WHERE gach_event_default_count > gach_event_buy_count';
            $sql_7star = ' )';
            if ($check_max >= $new_gachapon_buy_limit_cnt) {
                // 7성 조건에 포함시키기
                $sql_7star = ' OR orderno > 6 )';
            }
            if ($check_max < 100 && $check_sell < 1) {
                $sql2 = ' AND ( orderno < 7';
            } else if ($check_max >= 100 && $check_max < 200 && $check_sell < 2) {
                $sql2 = ' AND ( orderno < 7';
            } else if ($check_max >= 200 && $check_max < 300 && $check_sell < 3) {
                $sql2 = ' AND ( orderno < 7';
            } else if ($check_max >= 300 && $check_max < $new_gachapon_buy_limit_cnt) {
                $sql2 = ' AND ( orderno < 7';
            } else if ($check_max < $new_gachapon_buy_limit_cnt) {
                $sql2 = ' AND ( orderno < 6';
            } else {
                $sql2 = ' AND ( orderno < 6';
            }
            $sql .= $sql2.$sql_7star;
            //--------
            $sql .= ' ORDER BY RANDOM() LIMIT 1 FOR UPDATE';
        }
        $result = $this->PgGame->query($sql);
        if (!$result) {
            throw new ErrorHandler('error', 'Error Occurred. [17055]'); // 처리 중 오류가 발생
        } else {
            $m_hero_pk = $this->PgGame->fetchOne();
            // 해당 영웅 판매 갯수 증가 (= 남은 수량 차감)
            $result = $this->PgGame->query('UPDATE new_gachapon_event SET gach_event_buy_count = gach_event_buy_count + 1 WHERE m_hero_pk = $1 AND gach_event_default_count > gach_event_buy_count', [$m_hero_pk]);
            if (!$result) {
                throw new ErrorHandler('error', 'Error Occurred. [17056]'); // 처리 중 오류가 발생
            }

            // 7성의 경우 당첨 군주명을 표기해 주기위하여 업데이트
            if ($_M['HERO_BASE'][$_M['HERO'][$m_hero_pk]['m_hero_base_pk']]['rare_type'] > 6) {
                $result = $this->PgGame->query('UPDATE new_gachapon_event SET lord_name = $1 WHERE m_hero_pk = $2', [$this->Session->lord['lord_name'], $m_hero_pk]);
                if (!$result) {
                    throw new ErrorHandler('error', 'Error Occurred. [17057]'); // 처리 중 오류가 발생
                }
            }

            // 군주 판매 횟수 증가
            $result = $this->PgGame->query('UPDATE lord SET new_gachapon_buy_cnt = new_gachapon_buy_cnt + $1 WHERE lord_pk = $2', [1, $this->Session->lord['lord_pk']]);
            if (!$result) {
                throw new ErrorHandler('error', 'Error Occurred. [17058]'); // 처리 중 오류가 발생
            }

            return ['m_hero_base_pk' => $_M['HERO'][$m_hero_pk]['m_hero_base_pk'], 'level' => $_M['HERO'][$m_hero_pk]['level'], 'rare' => $_M['HERO_BASE'][$_M['HERO'][$m_hero_pk]['m_hero_base_pk']]['rare_type']];
        }
    }

    // TODO 이것도 동일한 함수가...
    function getNewPointHeroPK($_hero_pk_arr, $_type, $_level): bool|array
    {
        $z = '';
        if (COUNT($_hero_pk_arr) > 0) {
            $z = 'AND d.m_hero_base_pk NOT IN ('. implode(",", $_hero_pk_arr) .')';
        }
        $this->PgGame->query("SELECT a.hero_pk, d.m_hero_base_pk
FROM m_npc_hero a, hero b, m_hero c, m_hero_base d
WHERE a.type = $1 AND a.level = $2 AND a.hero_pk = b.hero_pk AND b.m_hero_pk = c.m_hero_pk AND c.m_hero_base_pk = d.m_hero_base_pk
{$z} ORDER BY random() LIMIT 1", [$_type, $_level]);
        $this->PgGame->fetch();
        return $this->PgGame->row;
    }

    public function checkStrikeHeroes ($_lord_pk): void
    {
        $this->PgGame->query('SELECT t1.hero_pk, t2.m_hero_pk, t2.level FROM my_hero t1, hero t2 WHERE t1.hero_pk = t2.hero_pk AND t1.lord_pk = $1 AND t1.yn_strike_notify = $2 AND t2.loyalty = $3', [$_lord_pk, 'Y', 0]);
        $strike_heroes = [];
        while ($this->PgGame->fetch()) {
            $r =& $this->PgGame->row;
            $strike_heroes[] =  ['pk' => $r['hero_pk'], 'm_pk' => $r['m_hero_pk']];
        }
        $strike_heroes_count = COUNT($strike_heroes);
        if ($strike_heroes_count > 0) {
            // 보고서
            $z_content = [];
            $z_content['amount'] = $strike_heroes_count;
            $z_content['strike_heroes'] = $strike_heroes;

            // from & to
            $this->classTroop();
            $z_from = ['posi_pk' => $this->Session->lord['main_posi_pk'], 'posi_name' => $this->Troop->getPositionName($this->Session->lord['main_posi_pk'])];
            $z_to = ['lord_name' => $z_content['amount']];

            // title & summary
            $z_summary = $z_content['amount'];

            $this->classReport();
            $this->Report->setReport($_lord_pk, 'misc', 'hero_strike', $z_from, $z_to, '', $z_summary, json_encode($z_content));

            // yn_strike_notify 셋
            $this->PgGame->query('UPDATE my_hero SET yn_strike_notify = $1 FROM hero WHERE my_hero.hero_pk = hero.hero_pk AND my_hero.lord_pk = $2 AND my_hero.yn_strike_notify = $3 AND hero.loyalty = $4', ['N', $_lord_pk, 'Y', 0]);
        }
    }

    public function getPickup (): void
    {
        $this->PgGame->query('SELECT pickup_type, pickup_count FROM my_pickup WHERE lord_pk = $1', [$this->Session->lord['lord_pk']]);
        $this->PgGame->fetchAll();
        $rows = [];
        foreach($this->PgGame->rows as $row) {
            $rows[$row['pickup_type']] = (INT)$row['pickup_count'];
        }
        $this->Session->sqAppend('PICKUP', $rows);
    }

    public function getPickupCount ($_pickup_type): int
    {
        $this->PgGame->query('SELECT pickup_count FROM my_pickup WHERE lord_pk = $1 AND pickup_type = $2', [$this->Session->lord['lord_pk'], $_pickup_type]);
        if ($this->PgGame->fetch()) {
            $this->Session->sqAppend('PICKUP', [$_pickup_type => (INT)$this->PgGame->row['pickup_count'] ?? 0]);
            return (INT)$this->PgGame->row['pickup_count'];
        } else {
            return 0;
        }
    }

    public function updatePickupCount ($_pickup_type, $_pickup_count): void
    {
        $this->PgGame->query('INSERT INTO my_pickup (lord_pk, pickup_type, pickup_count) VALUES ($1, $2, $3) ON CONFLICT (lord_pk, pickup_type) DO UPDATE SET pickup_count = $3', [$this->Session->lord['lord_pk'], $_pickup_type, $_pickup_count]);
        $this->Session->sqAppend('PICKUP', [$_pickup_type => (INT)$_pickup_count]);
    }

    public function initPickupCount ($_pickup_type): void
    {
        $this->PgGame->query('UPDATE my_pickup SET pickup_count = 0 WHERE lord_pk = $1 AND pickup_type = $2', [$this->Session->lord['lord_pk'], $_pickup_type]);
    }

    // 영웅 설명
    function getHeroDesc($_hero_pk): null|string
    {
        $this->PgGame->query('SELECT m_hero_pk, level FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        if (!$this->PgGame->fetch()) {
            return null;
        }
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero', 'hero_base']);
        $m_hero_base_pk = $_M['HERO'][$this->PgGame->row['m_hero_pk']]['m_hero_base_pk'];
        return "{$_M['HERO_BASE'][$m_hero_base_pk]['name']}:{$this->PgGame->row['level']}";
    }
}