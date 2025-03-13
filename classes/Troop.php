<?php

class Troop
{
    protected Session $Session;
    protected Pg $PgGame;
    protected RedisCache $Redis;

    protected Army $Army;
    protected Alliance $Alliance;
    protected Hero $Hero;
    protected Battle $Battle;
    protected Effect $Effect;
    protected Medical $Medical;
    protected Timer $Timer;
    protected Resource $Resource;
    protected GoldPop $GoldPop;
    protected Production $Production;
    protected FigureReCalc $FigureReCalc;
    protected Bdic $Bdic;
    protected Quest $Quest;
    protected Lord $Lord;
    protected Power $Power;
    protected Letter $Letter;
    protected Item $Item;
    protected Report $Report;
    protected Push $Push;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;

        $this->classHero();
        $this->classTimer();
        $this->classProduction();
    }

    function classRedis(): void
    {
        if (!isset($this->Redis)) {
            $this->Redis = new RedisCache();
        }
    }

    function classLog(): void
    {
        if (!isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    function classMedical(): void
    {
        if (!isset($this->Medical)) {
            $this->Medical = new Medical($this->Session, $this->PgGame);
        }
    }

    function classArmy(): void
    {
        if (!isset($this->Army)) {
            $this->classResource();
            $this->classGoldPop();
            $this->Army = new Army($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classBattle(): void
    {
        if (!isset($this->Battle)) {
            $this->Battle = new Battle($this->Session, $this->PgGame);
        }
    }

    function classHero(): void
    {
        if (!isset($this->Hero)) {
            $this->Hero = new Hero($this->Session, $this->PgGame);
        }
    }

    function classAlliance(): void
    {
        if (!isset($this->Alliance)) {
            $this->Alliance = new Alliance($this->Session, $this->PgGame);
        }
    }

    function classTimer(): void
    {
        if (!isset($this->Timer)) {
            $this->Timer = new Timer($this->Session, $this->PgGame);
        }
    }

    function classResource(): void
    {
        if (!isset($this->Resource)) {
            $this->Resource = new Resource($this->Session, $this->PgGame);
        }
    }

    function classGoldPop(): void
    {
        if (!isset($this->GoldPop)) {
            $this->GoldPop = new GoldPop($this->Session, $this->PgGame);
        }
    }

    function classProduction(): void
    {
        if (!isset($this->Production)) {
            $this->Production = new Production($this->Session, $this->PgGame);
        }
    }

    function classPower(): void
    {
        if (!isset($this->Power)) {
            $this->Power = new Power($this->Session, $this->PgGame);
        }
    }

    function classFigureReCalc(): void
    {
        if (!isset($this->FigureReCalc)) {
            $this->classResource();
            $this->classGoldPop();
            $this->FigureReCalc = new FigureReCalc($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classBdic(): void
    {
        if (!isset($this->Bdic)) {
            $this->classResource();
            $this->classGoldPop();
            $this->Bdic = new Bdic($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classQuest(): void
    {
        if (!isset($this->Quest)) {
            $this->Quest = new Quest($this->Session, $this->PgGame);
        }
    }

    function classLord(): void
    {
        if (!isset($this->Lord)) {
            $this->Lord = new Lord($this->Session, $this->PgGame);
        }
    }

    function classLetter(): void
    {
        if (!isset($this->Letter)) {
            $this->Letter = new Letter($this->Session, $this->PgGame);
        }
    }

    function classItem(): void
    {
        if (!isset($this->Item)) {
            $this->Item = new Item($this->Session, $this->PgGame);
        }
    }

    function classReport(): void
    {
        if (!isset($this->Report)) {
            $this->Report = new Report($this->Session, $this->PgGame);
        }
    }

    function classEffect(): void
    {
        $this->classResource();
        $this->classGoldPop();
        $this->classFigureReCalc();

        if (!isset($this->Effect)) {
            $this->Effect = new Effect($this->Session, $this->PgGame, $this->Resource, $this->GoldPop, $this->FigureReCalc);
        }
    }

    function classPush(): void
    {
        if (!isset($this->Push)) {
            $this->Push = new Push($this->Session, $this->PgGame);
        }
    }

    // 내영지 부대 수
    function getMyTroopsCnt($_posi_pk): int
    {
        $this->PgGame->query('SELECT COUNT(troo_pk) AS cnt FROM troop WHERE src_posi_pk = $1', [$_posi_pk]);
        return $this->PgGame->fetchOne();
    }

    // 내영지 부대 이동현황
    function getMyMoveTroops($_posi_pk): array
    {
        $this->PgGame->query('SELECT t1.troo_pk, t1.status, t1.cmd_type, t1.captain_desc, t1.from_position, t1.to_position, date_part(\'epoch\', t1.arrival_dt)::integer as arrival_dt, t2.m_hero_pk FROM troop t1, hero t2 WHERE t1.src_posi_pk = $1 AND t1.status = ANY ($2) AND t1.captain_hero_pk = t2.hero_pk ORDER BY t1.troo_pk DESC', [$_posi_pk, '{' . implode(',', ['M', 'R', 'W']) . '}']); // 출정, 회군(Recall), 출정취소(Withdrawal) - 타이머 싱크 문제로 'B'는 제외
        $this->PgGame->fetchAll();
        return (!$this->PgGame->rows || !count($this->PgGame->rows)) ? [] : $this->PgGame->rows;
    }

    // 지정영지로 적 부대 이동현황 (회군은 제외)
    function getEnemyMarchTroops($_posi_pk): array
    {
        $this->PgGame->query('SELECT t1.troo_pk, (SELECT type FROM position WHERE posi_pk = dst_posi_pk) AS dst_posi_type,
(SELECT lord_name FROM lord WHERE lord_pk = src_lord_pk) AS lord_name, (SELECT title FROM alliance WHERE alli_pk = src_alli_pk) AS alli_name, 
t1.status, t1.cmd_type, t1.troop_desc, t1.captain_desc, t1.from_position, t1.to_position, date_part(\'epoch\', t1.arrival_dt)::integer as arrival_dt, t2.m_hero_pk
FROM troop t1, hero t2
WHERE (t1.dst_posi_pk = $1 OR t1.dst_posi_pk IN (SELECT valley_posi_pk FROM territory_valley WHERE posi_pk = $1))
AND t1.status = ANY ($2) AND t1.cmd_type = ANY ($3)
AND t2.hero_pk = t1.captain_hero_pk
ORDER BY t1.troo_pk DESC', [$_posi_pk, '{' . implode(',', ['M']) . '}', '{' . implode(',', ['A']) . '}']); // 타이머 싱크 문제로 'B'는 제외. A = 공격
        $this->PgGame->fetchAll();
        $list = (!$this->PgGame->rows || !count($this->PgGame->rows)) ? [] : $this->PgGame->rows;
        foreach ($list as $k => $v) {
            if ($v['dst_posi_type'] == 'P') {
                $list[$k]['from_position'] = '적 부대'; // TODO 텍스트 코드 처리 필요.
                $list[$k]['troop_desc'] = '적의 공격부대';
            }
        }
        return $list;
    }

    // 내영지 부대 주둔현황
    function getMyCampTroops($_posi_pk): array
    {
        $this->PgGame->query('SELECT t1.troo_pk, t1.status, t1.cmd_type, t1.to_position, t1.captain_desc, t1.hour_food, date_part(\'epoch\', t1.withdrawal_dt)::integer as withdrawal_dt, t1.distance, t2.m_hero_pk
                            FROM troop t1, hero t2 WHERE t1.src_posi_pk = $1 AND t1.status = $2 AND t1.captain_hero_pk = t2.hero_pk ORDER BY t1.troo_pk DESC', [$_posi_pk, 'C']);
        $this->PgGame->fetchAll();
        return (!$this->PgGame->rows || !count($this->PgGame->rows)) ? [] : $this->PgGame->rows;
    }

    // 내부대 주둔현황
    function getMyCampTroopsDstPosi($_posi_pk, $_src_lord_pk = null): array
    {
        if (!$_src_lord_pk) {
            $_src_lord_pk = $this->Session->lord['lord_pk'];
        }
        $this->PgGame->query('SELECT t1.troo_pk, t1.status, t1.cmd_type, t1.to_position, t1.captain_desc, t1.hour_food, date_part(\'epoch\', t1.withdrawal_dt)::integer as withdrawal_dt, t1.distance, t2.m_hero_pk FROM troop t1, hero t2 WHERE t1.src_lord_pk = $1 AND t1.dst_posi_pk = $2 AND t1.status = $3 AND t1.captain_hero_pk = t2.hero_pk ORDER BY t1.troo_pk DESC', [$_src_lord_pk, $_posi_pk, 'C']);
        $this->PgGame->fetchAll();
        return (!$this->PgGame->rows || !count($this->PgGame->rows)) ? [] : $this->PgGame->rows;
    }

    // 지정영지에 내 부대(전체영지) 주둔여부 (리턴 값은 주둔부대 수)
    function getMyCampTroopsExist($_posi_pk, $_src_lord_pk = null): int
    {
        if (!$_src_lord_pk) {
            $_src_lord_pk = $this->Session->lord['lord_pk'];
        }
        $this->PgGame->query('SELECT COUNT(troo_pk) AS cnt FROM troop WHERE src_lord_pk = $1 AND dst_posi_pk = $2 AND status = $3 LIMIT 1', [$_src_lord_pk, $_posi_pk, 'C']);
        return $this->PgGame->fetchOne();
    }

    // 지정영지에 동맹군 주둔부대 수 (주둔만!)
    function getAllyCampTroopsCnt($_posi_pk)
    {
        $this->PgGame->query('SELECT COUNT(troo_pk) AS cnt FROM troop WHERE dst_posi_pk = $1 AND status = $2', [$_posi_pk, 'C']);
        return $this->PgGame->fetchOne();
    }

    // 지정영지에 동맹군 현황 (수송중, 지원중, 주둔중) - 자신의 부대는 제외 (수송중 또는 지원중만 해당되기는 하지만,) - 대사관
    function getAllyCampTroops($_posi_pk): array
    {
        $this->PgGame->query('SELECT troo_pk, (SELECT lord_name FROM lord WHERE lord_pk = src_lord_pk) AS lord_name, status, cmd_type, captain_desc, troop_desc, from_position, date_part(\'epoch\', withdrawal_dt)::integer as withdrawal_dt, date_part(\'epoch\', arrival_dt)::integer as arrival_dt FROM troop WHERE dst_posi_pk = $1 AND status = ANY ($2) AND cmd_type = ANY ($3) AND src_lord_pk <> dst_lord_pk ORDER BY troo_pk DESC',
            [$_posi_pk, '{' . implode(',', ['M', 'C']) . '}', '{' . implode(',', ['T', 'R']) . '}']);
        $this->PgGame->fetchAll();
        return $this->PgGame->rows;
    }

    // 내 주둔부대 현황
    function getMyCampTroopList($_posi_pk): array
    {
        $this->PgGame->query('SELECT troo_pk, captain_desc, from_position, date_part(\'epoch\', withdrawal_dt)::integer as withdrawal_dt FROM troop WHERE dst_posi_pk = $1 AND src_lord_pk = $2 AND status = $3 ORDER BY troo_pk DESC', [$_posi_pk, $this->Session->lord['lord_pk'], 'C']);
        $this->PgGame->fetchAll();
        return $this->PgGame->rows;
    }

    // 내 영지 동맹군 병력현황 (병과별 summary)
    function getAllyCampArmy($_posi_pk): array
    {
        $this->PgGame->query('SELECT 
 sum(army_worker) as worker, sum(army_infantry) as infantry, sum(army_pikeman) as pikeman,
 sum(army_scout) as scout, sum(army_spearman) as spearman, sum(army_armed_infantry) as armed_infantry,
 sum(army_archer) as archer, sum(army_horseman) as horseman, sum(army_armed_horseman) as armed_horseman,
 sum(army_transporter) as transporter, sum(army_bowman) as bowman, sum(army_battering_ram) as battering_ram,
 sum(army_catapult) as catapult, sum(army_adv_catapult) as adv_catapult FROM troop WHERE dst_posi_pk = $1 AND status = $2', [$_posi_pk, 'C']);
        $this->PgGame->fetch();
        return $this->PgGame->row;
    }

    // 지정 위치 주둔 병력현황 (병과별 summary)
    function getCampArmy($_posi_pk): array
    {
        $this->PgGame->query('SELECT 
 sum(army_worker) as army_worker, sum(army_infantry) as army_infantry, sum(army_pikeman) as army_pikeman,
 sum(army_scout) as army_scout, sum(army_spearman) as army_spearman, sum(army_armed_infantry) as army_armed_infantry,
 sum(army_archer) as army_archer, sum(army_horseman) as army_horseman, sum(army_armed_horseman) as army_armed_horseman,
 sum(army_transporter) as army_transporter, sum(army_bowman) as army_bowman, sum(army_battering_ram) as army_battering_ram,
 sum(army_catapult) as army_catapult, sum(army_adv_catapult) as army_adv_catapult FROM troop WHERE dst_posi_pk = $1 AND status = $2', [$_posi_pk, 'C']);
        $this->PgGame->fetch();
        return $this->PgGame->row;
    }

    // 황건적 습격부대 생성 (기초 자료를 생성하여 "메인 부대생성 함수" 호출)
    function marchNpcTroop($_troop_quest_npc_attack, $_lord_pk = null, $_type = 'attack', $_event_npc_array = null): void
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
            $_alli_pk = $this->Session->lord['alli_pk'];
            $_main_posi_pk = $this->Session->lord['main_posi_pk'];
            $_level = $this->Session->lord['level'];
        } else {
            $this->PgGame->query('SELECT main_posi_pk, level FROM lord WHERE lord_pk = $1', [$_lord_pk]);
            $this->PgGame->fetch();
            $_main_posi_pk = $this->PgGame->row['main_posi_pk'];
            $_level = $this->PgGame->row['level'];
            $_alli_pk = null;
        }
        if (!$_main_posi_pk) {
            throw new ErrorHandler('error', '메인 영지 없음. '.$_lord_pk, true);
        }

        // 대상 군주의 보호모드 여부 - 습격부대는 항상 발생.
        /*$this->PgGame->query('SELECT status_truce FROM territory WHERE posi_pk = $1', Array($_main_posi_pk));
        $status_truce = $this->PgGame->fetchOne();
        if ($status_truce == 'Y') {
            throw new ErrorHandler('error', '군주가 보호모드 중임.', 'lord_pk:'.$_lord_pk);
        }*/

        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['npc_troop', 'npc_hero']);

        $m_npc_troo =& $_M['NPC_TROO'][$_type][$_troop_quest_npc_attack];
        $m_npc_hero =& $_M['NPC_HERO']['attack'][$_level];
        if ($_event_npc_array != null) {
            // NPC 이동시간 지정시 지정 시간 입력.
            $m_npc_troo['move_time'] = $_event_npc_array['move_time'];
        }

        // 영웅 섞기
        shuffle($m_npc_hero);

        $base = [];
        $base['src_lord_pk'] = NPC_TROOP_LORD_PK;
        $base['dst_lord_pk'] = $_lord_pk;
        $base['src_posi_pk'] = NPC_TROOP_POSI_PK;
        $base['dst_posi_pk'] = $_main_posi_pk;
        $base['src_alli_pk'] = null;
        $base['dst_alli_pk'] = $_alli_pk;
        $base['from_position'] = '999x999:황건적 습격부대:'.$_level;
        $base['to_position'] = $this->getPositionName($_main_posi_pk);
        $base['distance'] = 0; // 계산하기
        $base['triptime'] = $m_npc_troo['move_time'];
        $base['camptime'] = 0;
        $base['round_food'] = 0; // 없음
        $base['round_gold'] = 0; // 없음
        $base['presence_food'] = 0; // 없음
        $base['hour_food'] = 0; // 없음
        $base['fighting_spirit'] = 0; // TODO - ?
        $base['use_item_pk'] = null;
        $base['troop_type'] = 'N';
        $base['troop_desc'] = '황건적 습격부대';
        $base['troop_quest_npc_attack'] = $_troop_quest_npc_attack;

        $hero = [];
        $hero['captain_hero_pk'] = $m_npc_hero[0];
        $hero['captain_desc'] = $this->getHeroDesc($m_npc_hero[0]);
        $hero['director_hero_pk'] = null;
        $hero['director_desc'] = null;
        $hero['staff_hero_pk'] = null;
        $hero['staff_desc'] = null;

        $reso = [];
        $reso['gold'] = 0;
        $reso['food'] = 0;
        $reso['horse'] = 0;
        $reso['lumber'] = 0;
        $reso['iron'] = 0;

        $army = [];
        $army['worker'] = $m_npc_troo['worker'];
        $army['infantry'] = $m_npc_troo['infantry'];
        $army['pikeman'] = $m_npc_troo['pikeman'];
        $army['scout'] = $m_npc_troo['scout'];
        $army['spearman'] = $m_npc_troo['spearman'];
        $army['armed_infantry'] = $m_npc_troo['armed_infantry'];
        $army['archer'] = $m_npc_troo['archer'];
        $army['horseman'] = $m_npc_troo['horseman'];
        $army['armed_horseman'] = $m_npc_troo['armed_horseman'];
        $army['transporter'] = $m_npc_troo['transporter'];
        $army['bowman'] = $m_npc_troo['bowman'];
        $army['battering_ram'] = $m_npc_troo['battering_ram'];
        $army['catapult'] = $m_npc_troo['catapult'];
        $army['adv_catapult'] = $m_npc_troo['adv_catapult'];

        $status = 'M';
        $cmd_type = 'A';
        $move_time = $base['triptime'] + $base['camptime'];
        $troop_info = $this->marchTroop($status, $cmd_type, $base, $hero, $reso, $army, $move_time);
        if ($troop_info) {
            if ($_type == 'dispatch') {
                // 데일리 황건적 습격 퀘스트라면 습격 부대 추가
                /*$this->PgGame->query('SELECT lord_pk FROM my_event WHERE lord_pk = $1',  Array($this->Session->lord['lord_pk']));
                if ($this->PgGame->fetchOne()) {
                    $this->PgGame->query('UPDATE my_event SET npc_troo_pk = $1, last_event_dt = now()  WHERE lord_pk = $2', Array($troop_info['troo_pk'], $this->Session->lord['lord_pk']));
                } else {
                    $this->PgGame->query('INSERT INTO my_event (lord_pk, npc_troo_pk) VALUES ($2, $1)', Array($troop_info['troo_pk'], $this->Session->lord['lord_pk']));
                }*/
                $this->PgGame->query('INSERT INTO my_event_npc_troop (lord_pk, npc_troo_pk, m_ques_pk, yn_quest_reward, buff_pk) VALUES ($2, $1, $3, $4, $5)',
                    [$troop_info['troo_pk'], $this->Session->lord['lord_pk'], $_event_npc_array['m_ques_pk'], $_event_npc_array['yn_reward'], $_event_npc_array['buff_pk']]);
            }

            $time_pks = $this->setTimer($troop_info['troo_pk'], $cmd_type, $base['src_lord_pk'], $base['dst_lord_pk'], $base['src_posi_pk'], $base['dst_posi_pk'], ['status' => $status, 'cmd_type' => $cmd_type, 'from_position' => $base['from_position'], 'to_position' => $base['to_position']], $move_time, ['hero' => $hero, 'army' => $army]);
            $this->setTimePk($troop_info['troo_pk'], $time_pks['src_time_pk'], $time_pks['dst_time_pk']);

            // 로그 기록
            $this->classLog();
            $this->Log->setTroop($base['src_lord_pk'], $base['src_posi_pk'], 'marchTroop', $base['dst_lord_pk'], null, $base['dst_posi_pk'], $base['to_position'], json_encode($hero), json_encode($army), json_encode($reso), $troop_info['troo_pk']);
        }
    }

    // 메인 부대생성 함수
    function marchTroop($_status, $_cmd_type, $_base, $_hero, $_reso, $_army, $_move_time, $raid_troo_pk = 0): false|array
    {
        $query_params = [
            $_base['src_lord_pk'],
            $_base['dst_lord_pk'],
            $_base['src_posi_pk'],
            $_base['dst_posi_pk'],
            $_base['src_alli_pk'],
            $_base['dst_alli_pk'],

            $_status,
            $_cmd_type,

            $_base['from_position'],
            $_base['to_position'],

            $_base['distance'],
            $_base['camptime'],
            $_base['round_food'],
            $_base['presence_food'],

            $_base['fighting_spirit'],
            $_base['use_item_pk'],

            $_base['troop_type'],
            $_base['troop_desc'],
            $_base['troop_quest_npc_attack'],

            $_hero['captain_hero_pk'],
            $_hero['captain_desc'],
            $_hero['director_hero_pk'],
            $_hero['director_desc'],
            $_hero['staff_hero_pk'],
            $_hero['staff_desc'],

            $_reso['gold'] ?? 0,
            $_reso['food'] ?? 0,
            $_reso['horse'] ?? 0,
            $_reso['lumber'] ?? 0,
            $_reso['iron'] ?? 0,

            $_army['worker'] ?? 0,
            $_army['infantry'] ?? 0,
            $_army['pikeman'] ?? 0,
            $_army['scout'] ?? 0,
            $_army['spearman'] ?? 0,
            $_army['armed_infantry'] ?? 0,
            $_army['archer'] ?? 0,
            $_army['horseman'] ?? 0,
            $_army['armed_horseman'] ?? 0,
            $_army['transporter'] ?? 0,
            $_army['bowman'] ?? 0,
            $_army['battering_ram'] ?? 0,
            $_army['catapult'] ?? 0,
            $_army['adv_catapult'] ?? 0,

            $_move_time,

            $_base['hour_food'],
            $_base['triptime'],
            $_base['round_gold'],
            $raid_troo_pk
        ];

        $this->PgGame->query("INSERT INTO troop
(src_lord_pk, dst_lord_pk, src_posi_pk, dst_posi_pk, src_alli_pk, dst_alli_pk, status, cmd_type,
 from_position, to_position,
 distance, camptime, round_food, presence_food, fighting_spirit, use_item_pk,
 troop_type, troop_desc, troop_quest_npc_attack,
 captain_hero_pk, captain_desc, director_hero_pk, director_desc, staff_hero_pk, staff_desc,
 reso_gold, reso_food, reso_horse, reso_lumber, reso_iron,
 army_worker, army_infantry, army_pikeman, army_scout, army_spearman,
 army_armed_infantry, army_archer, army_horseman, army_armed_horseman, army_transporter,
 army_bowman, army_battering_ram, army_catapult, army_adv_catapult,
 regist_dt, start_dt, move_time, arrival_dt,
 hour_food, triptime, round_gold, raid_troo_pk) VALUES (
 $1, $2, $3, $4, $5, $6, $7, $8,
 $9, $10,
 $11, $12, $13, $14, $15, $16,
 $17, $18, $19,
 $20, $21, $22, $23, $24,  $25,
 $26, $27, $28, $29, $30,
 $31, $32, $33, $34, $35,
 $36, $37, $38, $39, $40,
 $41, $42, $43, $44,
 now(), now(), $45, now() + interval '$_move_time second',
 $46, $47, $48, $49
) RETURNING start_dt, src_posi_pk, dst_posi_pk, status, cmd_type, arrival_dt, date_part('epoch', arrival_dt) as arrival_dt_ut, triptime, camptime, move_time, troo_pk, captain_hero_pk, director_hero_pk, staff_hero_pk", $query_params);

        if (!$this->PgGame->fetch()) {
            return false;
        }
        $troop_info = $this->PgGame->row;

        // 즐겨찾기 최근 목적지 저장
        if ($_base['src_lord_pk'] != $_base['dst_lord_pk']) {
            // 기존에 있는 위치면 삭제하고 insert
            $this->PgGame->query('SELECT posi_favo_pk FROM position_favorite WHERE lord_pk = $1 AND posi_pk = $2 AND type = $3', [$_base['src_lord_pk'], $_base['dst_posi_pk'], 'R']);
            if ($this->PgGame->fetchOne()) {
                $this->PgGame->query('DELETE FROM position_favorite WHERE lord_pk = $1 AND posi_pk = $2 AND type = $3', [$_base['src_lord_pk'], $_base['dst_posi_pk'], 'R']);
            }
            $this->PgGame->query('INSERT INTO position_favorite (lord_pk, posi_pk, memo, regist_dt, type) VALUES ($1, $2, $3, now(), $4)', [$_base['src_lord_pk'], $_base['dst_posi_pk'], $_cmd_type, 'R']);
        }

        // PUSH - dst (대사관을 위해서 수송과 지원만)
        if (in_array($_cmd_type, ['T', 'R'])) {
            if ($_base['src_lord_pk'] != $_base['dst_lord_pk'] && $_base['dst_lord_pk'] != NPC_TROOP_LORD_PK) {
                $this->Session->sqAppend('PUSH', ['EMBASSY_CAMP_TROOP' => true], null, $_base['dst_lord_pk'], $_base['dst_posi_pk']);
            }
        }
        return $troop_info;
    }

    // 부대 정보 추출
    function getTroop($_troo_pk): bool|array
    {
        $this->PgGame->query('SELECT troo_pk, src_lord_pk, dst_lord_pk, src_posi_pk, dst_posi_pk, src_alli_pk, dst_alli_pk, status, cmd_type,
 from_position, to_position, distance, triptime, camptime,
 round_food, round_gold, presence_food, hour_food, fighting_spirit, use_item_pk, captain_hero_pk,
 director_hero_pk, staff_hero_pk, reso_gold, reso_food, reso_horse, reso_lumber, reso_iron,
 army_worker, army_infantry, army_pikeman, army_scout, army_spearman, army_armed_infantry,
 army_archer, army_horseman, army_armed_horseman, army_transporter, army_bowman,
 army_battering_ram, army_catapult, army_adv_catapult, src_time_pk, dst_time_pk,
 date_part(\'epoch\', now())::integer-date_part(\'epoch\', start_dt)::integer AS elapsed_time,
 captain_desc, director_desc, staff_desc, withdrawal_dt, withdrawal_auto, troop_type, troop_quest_npc_attack,
 raid_troo_pk
FROM troop
WHERE troo_pk = $1', [$_troo_pk]);
        return ($this->PgGame->fetch()) ? $this->PgGame->row : false;
    }

    // 부대 삭제
    function removeTroop($_troo_pk): void
    {
        $this->removeMoveTroop($_troo_pk);
        $this->PgGame->query('DELETE FROM troop WHERE troo_pk = $1', [$_troo_pk]);
    }

    // 타이머 등록 - TODO 이거 코드 정리 한번 필요 할 것 같다. 중간 중간 변경되면서 너무 막 추가한 느낌.
    function setTimer($_troo_pk, $_cmd_type, $_src_lord_pk, $_dst_lord_pk, $_src_posi_pk, $_dst_posi_pk, $_desc_arr, $_time, $_troop_desc_arr = null): array
    {
        // table의 queue_action이 'Y' 일때만 부대 도착을 처리한다. - 타이머는 공/방 측 두개일 수 있기 때문에 구분하여 처리하며 NPC 쪽은 공/방 모두 타이머가 없다.
        $time_pks = [];
        $queue_action = 'Y';
        $time_pks['src_time_pk'] = null;
        $time_pks['dst_time_pk'] = null;

        // src 측 타이머 (부대 이동 현황) TODO 텍스트 코드 처리 필요.
        if ($_src_lord_pk && $_src_lord_pk != NPC_TROOP_LORD_PK) {
            /*if (($_cmd_type == 'R' || $_cmd_type == 'T') && (isset($_desc_arr['dst_type']) && $_desc_arr['dst_type'] == 'T')) {
                $desc = $this->getTimerDesc($_desc_arr['status'], $_desc_arr['cmd_type'], '목적지:' . $_desc_arr['to_position']);
            } else {
                $desc = $this->getTimerDesc($_desc_arr['status'], $_desc_arr['cmd_type'], $_desc_arr['to_position']);
                if (isset($_desc_arr['use_item']) && $_desc_arr['use_item_pk'] == 500084) {
                    $desc .= ' - 황제';
                }
            }*/
            // src 측 타이머 도착지 상태, 명령, 목적지(문자열), 아이템
            $src_description = $this->setTimerDescription($_desc_arr['status'], $_desc_arr['cmd_type'], $_desc_arr['to_position']);
            $this->Timer->set($_src_posi_pk, 'X', $_troo_pk, $queue_action, $src_description, $_time, null, $_src_lord_pk);
            $queue_action = 'N';
            $time_pks['src_time_pk'] = $this->Timer->getTimePk();
        }

        // dst 측 타이머 (공격 때만 등록됨) (적 이동 현황)
        if ($_cmd_type == 'A' && ($queue_action == 'Y' || $_dst_lord_pk && $_dst_lord_pk != NPC_TROOP_LORD_PK && $_src_lord_pk != $_dst_lord_pk)) {
            // $desc = $this->getTimerDesc($_desc_arr['status'], $_desc_arr['cmd_type'], $_desc_arr['from_position']);
            $dst_description = $this->setTimerDescription($_desc_arr['status'], $_desc_arr['cmd_type'], $_desc_arr['from_position']);
            $this->PgGame->query('SELECT type FROM position WHERE posi_pk = $1', [$_dst_posi_pk]);
            $type = $this->PgGame->fetchOne();
            if ($type == 'T') {
                $new_dst_posi_pk = $_dst_posi_pk;
            } else if ($type == 'P') {
                $this->PgGame->query('SELECT src_posi_pk FROM troop WHERE dst_posi_pk = $1 AND status = $2', [$_dst_posi_pk, 'C']);
                $new_dst_posi_pk = $this->PgGame->fetchOne();
                // $desc = $this->getTimerDesc($_desc_arr['status'], $_desc_arr['cmd_type'], '타군주');
                $dst_description = $this->setTimerDescription($_desc_arr['status'], $_desc_arr['cmd_type'], 'other_lord');
            } else {
                $this->PgGame->query('SELECT posi_pk FROM territory_valley WHERE valley_posi_pk = $1', [$_dst_posi_pk]);
                $new_dst_posi_pk = $this->PgGame->fetchOne();
            }

            $this->Timer->set($new_dst_posi_pk, 'Y', $_troo_pk, $queue_action, $dst_description, $_time, null, $_dst_lord_pk);
            $queue_action = 'N';
            $time_pks['dst_time_pk'] = $this->Timer->getTimePk();

            // 보고서 - 공격해오는 적 정보
            $z_content = [];
            if ($type == 'P') {
                $_desc_arr['from_position'] = '타 군주';
            }
            $z_content['from_position'] = $_desc_arr['from_position'];
            $z_content['to_position'] = $_desc_arr['to_position'];

            // hero
            /*$z_content['hero']['captain'] = $_troopDescArr['hero']['captain_desc'];
            if ($_troopDescArr['hero']['director_desc']) {
                $z_content['hero']['director'] = $_troopDescArr['hero']['director_desc'];
            }
            if ($_troopDescArr['hero']['staff_desc']) {
                $z_content['hero']['staff'] = $_troopDescArr['hero']['staff_desc'];
            }*/
            $z_content['hero'][] = ['pk' => $_troop_desc_arr['hero']['captain_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($_troop_desc_arr['hero']['captain_hero_pk'])];
            if ($_troop_desc_arr['hero']['director_hero_pk']) {
                $z_content['hero'][] = ['pk' => $_troop_desc_arr['hero']['director_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($_troop_desc_arr['hero']['director_hero_pk'])];
            }
            if ($_troop_desc_arr['hero']['staff_hero_pk']) {
                $z_content['hero'][] = ['pk' => $_troop_desc_arr['hero']['staff_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($_troop_desc_arr['hero']['staff_hero_pk'])];
            }

            // army
            $z_content['army'] = $this->getNumberToTextDesc($_troop_desc_arr['army']);

            $army_scale = 0;
            foreach ($_troop_desc_arr['army'] as $v) {
                $army_scale += $v;
            }

            $rArr = $this->getNumberToTextDesc([$army_scale]);
            $z_content['army_scale'] = $rArr[0];

            // from & to
            $z_from = ['posi_pk' => $_src_posi_pk, 'posi_name' => $_desc_arr['from_position']];
            $z_to = ['posi_pk' => $_dst_posi_pk, 'posi_name' => $_desc_arr['to_position']];

            $z_title = '';
            $z_summary = $_desc_arr['to_position'];

            $this->classReport();
            $repo_pk = $this->Report->setReport($_dst_lord_pk, 'scout', 'enemy_march', $z_to, $z_from, $z_title, $z_summary, json_encode($z_content), null, 'Alert'); // 이전 사운드 : enemymarch
        }
        if (($_cmd_type == 'R' || $_cmd_type == 'T') && ($queue_action == 'N' || $_dst_lord_pk && $_dst_lord_pk != NPC_TROOP_LORD_PK && $_desc_arr['dst_type'] == 'T')) {
            // $desc = $this->getTimerDesc($_desc_arr['status'], $_desc_arr['cmd_type'], '출발지:' . $_desc_arr['from_position']);
            $description = $this->setTimerDescription($_desc_arr['status'], $_desc_arr['cmd_type'], $_desc_arr['from_position']);
            $this->PgGame->query('SELECT type FROM position WHERE posi_pk = $1', [$_dst_posi_pk]);
            if ($this->PgGame->fetchOne() == 'T') {
                $this->Timer->set($_dst_posi_pk, 'X', $_troo_pk, 'N', $description, $_time, null, $_dst_lord_pk);
                $time_pks['dst_time_pk'] = $this->Timer->getTimePk();
            }
        }
        return $time_pks;
    }

    function getTimerDesc($_status, $_cmd_type, $_position_name): string
    {
        // 차후 다국어 문제가 있을 수 있으니
        // $_M['CODESET']['TROOP_STATUS'] = ['M' => '출정', 'B' => '전투', 'C' => '주둔', 'R' => '회군', 'W' => '취소'];
        // $_M['CODESET']['TROOP_CMD_TYPE'] = ['T' => '수송', 'R' => '지원', 'P' => '보급', 'S' => '정찰', 'A' => '공격'];

        global $_M;
        if ($_status == 'R' || $_status == 'W') {
            $desc = sprintf('%s %s', $_M['CODESET']['TROOP_STATUS'][$_status], $_position_name);
        } else {
            $desc = sprintf('%s(%s) %s', $_M['CODESET']['TROOP_CMD_TYPE'][$_cmd_type], $_M['CODESET']['TROOP_STATUS'][$_status], $_position_name);
        }
        return $desc;
    }

    function setTimerDescription($_status, $_cmd_type, $_position_title = '', $_m_item_pk = 0): string
    {
        // TODO 차후 실제 이 단어에 맞춰 코드셋을 업데이트 해주자. 한글자 단어로 사용하는건 미친짓이야ㅠ
        $_status_desc = match($_status) {
            'M' => 'dispatch', // 출정
            'B' => 'battle', // 전투
            'C' => 'camp', // 주둔
            'R' => 'return', // 회군 (원래는 recall 인데 return 쪽이 더 어울린다.
            'W' => 'withdraw', // 철수(취소)
            default => $_status // 없는 경우...?
        };
        $_cmd_type_desc = match ($_cmd_type) {
            'T' => 'transport', // 수송
            'R' => 'support', // 지원
            'P' => 'supply', // 보급
            'S' => 'scout', // 정찰
            'A' => 'attack', // 공격
            default => $_cmd_type
        };

        // 아이템:상태:명령:좌표:목적지:레벨
        return "$_m_item_pk:$_status_desc:$_cmd_type_desc:$_position_title";
    }

    function cancelTimer($_time_pk, $_lord_pk = null): void
    {
        $this->Timer->cancel($_time_pk, $_lord_pk);
    }

    // 부대 레코드에 타이머Pk 등록
    function setTimePk($_troo_pk, $_src_time_pk = null, $_dst_time_pk = null): void
    {
        $this->PgGame->query('UPDATE troop SET src_time_pk = $1, dst_time_pk = $2 WHERE troo_pk = $3', [$_src_time_pk, $_dst_time_pk, $_troo_pk]);
    }

    // 액션 - 출정 취소
    function setStatusWithdrawal($_troo_pk, $row = null, $force_withdrawal = false): true
    {
        if (!$row) {
            $row = $this->getTroop($_troo_pk);
        }
        if (!$row) {
            throw new ErrorHandler('error', '부대 정보를 찾을 수 없습니다.');
        } else if ($row['status'] != 'M' && !$force_withdrawal) {
            throw new ErrorHandler('error', '출정 중인 상태만 취소 할 수 있습니다.');
        }

        // src_time_pk 있으면 삭제
        if ($row['src_time_pk']) {
            $this->cancelTimer($row['src_time_pk']);
        }

        // dst_time_pk 있으면 삭제
        if ($row['dst_time_pk']) {
            if ($row['dst_lord_pk'] && $row['dst_lord_pk'] != NPC_TROOP_LORD_PK) {
                $this->cancelTimer($row['dst_time_pk'], $row['dst_lord_pk']);
            } else {
                $this->cancelTimer($row['dst_time_pk']);
            }
        }

        // 복귀시간(elapsed_time) = 현재시간 - 출발시간
        $move_time = $row['elapsed_time'];
        // 복귀시간이 실제 이동 시간보다 긴경우 이동시간 만큼만 적용.
        if ($move_time > $row['triptime'] || $force_withdrawal) {
            $move_time = $row['triptime'];
        }

        // 영웅 명령 효과
        if ($row['captain_hero_pk']) {
            $this->classEffect();
            $capacities = $this->Effect->getHeroCapacityEffects($row['captain_hero_pk']);
            $applies = $this->Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_CAPTAIN, $capacities);
            $ret = $this->Effect->getEffectedValue($row['src_posi_pk'], ['troop_recall_time_decrease'], $move_time, $applies['all']);
            $move_time = (($move_time * 0.1) > $ret['value']) ? intval($move_time * 0.1) : intval($ret['value']);
        }

        if ($row['src_time_pk']) {
            // 회군 타이머 등록
            $time_pks = $this->setTimer($_troo_pk, $row['cmd_type'], $row['src_lord_pk'], null, $row['src_posi_pk'], null, ['status' => 'W', 'cmd_type' => $row['cmd_type'], 'from_position' => $row['from_position'], 'to_position' => $row['to_position']], $move_time);

            // 관련 부대정보 변경
            $this->PgGame->query("UPDATE troop SET status = $1, src_time_pk = $2, dst_time_pk = $3, start_dt = now(), move_time = $4, from_position = $6, to_position = $7, move_time_reduce = 0, arrival_dt = now() + interval '$move_time second' WHERE troo_pk = $5", ['W', $time_pks['src_time_pk'], $time_pks['dst_time_pk'], $move_time, $_troo_pk, $row['to_position'], $row['from_position']]);

            // $this->setTroopMove($_troo_pk);
            $this->getMoveTroop($_troo_pk);

            // 영웅 상태 변경
            $this->classHero();
            $this->Hero->setCommandCmdType($row['captain_hero_pk'], 'Recal');
            if ($row['director_hero_pk']) {
                $this->Hero->setCommandCmdType($row['director_hero_pk'], 'Recal');
            }
            if ($row['staff_hero_pk']) {
                $this->Hero->setCommandCmdType($row['staff_hero_pk'], 'Recal');
            }
            // dst (대사관을 위해서 수송과 지원만) - PUSH
            if ($row['cmd_type'] == 'T' || $row['cmd_type'] == 'R') {
                if ($row['src_lord_pk'] != $row['dst_lord_pk'] && $row['dst_lord_pk'] != NPC_TROOP_LORD_PK) {
                    $this->Session->sqAppend('PUSH', ['EMBASSY_CAMP_TROOP' => true], null, $row['dst_lord_pk'], $row['dst_posi_pk']);
                }
            }
            // $this->Session->sqAppend('MOVE_TROOP_UPDATE', $this->getTroop($_troo_pk), null, $row['src_lord_pk'], $row['src_posi_pk']);
        } else {
            // src_time_pk 가 없을 경우는 NPC 밖에 없으나 일단 예외는 처리!
            $this->removeTroop($_troo_pk);
        }

        // Log
        $hero = ['captain_hero_pk' => $row['captain_hero_pk'], 'director_hero_pk' => $row['director_hero_pk'], 'staff_hero_pk' => $row['staff_hero_pk']];
        $army = [
            'army_worker' => $row['army_worker'], 'army_infantry' => $row['army_infantry'], 'army_pikeman' => $row['army_pikeman'],
            'army_scout' => $row['army_scout'], 'army_spearman' => $row['army_spearman'], 'army_armed_infantry' => $row['army_armed_infantry'],
            'army_archer' => $row['army_archer'], 'army_horseman' => $row['army_horseman'], 'army_armed_horseman' => $row['army_armed_horseman'],
            'army_transporter' => $row['army_transporter'], 'army_bowman' => $row['army_bowman'], 'army_battering_ram' => $row['army_battering_ram'],
            'army_catapult' => $row['army_catapult'], 'army_adv_catapult' => $row['army_adv_catapult']
        ];
        $resource = ['round_food' => $row['round_food'], 'round_gold' => $row['round_gold'], 'presence_food' => $row['presence_food'], 'hour_food' => $row['hour_food']];
        $this->classLog();
        $this->Log->setTroop($row['src_lord_pk'], $row['src_posi_pk'], 'StatusWithdrawal', $row['dst_lord_pk'], null, $row['dst_posi_pk'], $row['to_position'], json_encode($hero), json_encode($army), json_encode($resource), $_troo_pk);

        return true;
    }

    // 상태변경 - 주둔
    function setStatusCamp($_troo_pk, $row = null): bool
    {
        if (!$row) {
            $row = $this->getTroop($_troo_pk);
        }
        global $NsGlobal;

        // 목적지 주둔가능 여부 확인
        $this->PgGame->query('SELECT yn_alliance_camp FROM territory WHERE posi_pk = $1', [$row['dst_posi_pk']]);
        if ($this->PgGame->fetchOne() == 'N') {
            $NsGlobal->setErrorMessage('주둔 불허용 상태');
            return false;
        }

        $this->PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$row['dst_posi_pk'], PK_BUILDING_EMBASSY]);
        $level = $this->PgGame->fetchOne();
        if (!$level) {
            $NsGlobal->setErrorMessage('대사관 없음');
            return false;
        }

        if ($this->getAllyCampTroopsCnt($row['dst_posi_pk']) >= $level) {
            $NsGlobal->setErrorMessage('주둔 부대 수 한계 초과');
            return false;
        }

        // 상태를 주둔으로 변경
        $this->PgGame->query('UPDATE troop SET src_time_pk = NULL, dst_time_pk = NULL, status = $1, withdrawal_dt = (now() + interval \'7 days\') WHERE troo_pk = $2', ['C', $_troo_pk]);

        // 영웅 상태 변경
        $this->classHero();
        $this->Hero->setCommandCmdType($row['captain_hero_pk'], 'Camp');
        if ($row['director_hero_pk']) {
            $this->Hero->setCommandCmdType($row['director_hero_pk'], 'Camp');
        }
        if ($row['staff_hero_pk']) {
            $this->Hero->setCommandCmdType($row['staff_hero_pk'], 'Camp');
        }

        // PUSH - src
        // $this->Session->sqAppend('PUSH', ['MY_CAMP_TROOP' => true], null, $row['src_lord_pk'], $row['src_posi_pk']);
        // $this->Session->sqAppend('MOVE_TROOP_END', ['troo_pk' => $_troo_pk], null, $row['src_lord_pk'], $row['src_posi_pk']);
        $this->removeMoveTroop($_troo_pk);

        // PUSH - dst
        if ($row['src_lord_pk'] != $row['dst_lord_pk'] && $row['dst_lord_pk'] != NPC_TROOP_LORD_PK) {
            $this->Session->sqAppend('PUSH', ['EMBASSY_CAMP_TROOP' => true], null, $row['dst_lord_pk'], $row['dst_posi_pk']);
        }

        return true;
    }

    // 상태변경 - 주둔 (외부자원지)
    function setStatusCampValley($_troo_pk, $row = null): true
    {
        if (!$row) {
            $row = $this->getTroop($_troo_pk);
        }

        // 상태를 주둔으로 변경 Todo 주둔은 무조건 7일이 맞는건지?
        $this->PgGame->query('UPDATE troop SET src_time_pk = NULL, dst_time_pk = NULL, status = $1, withdrawal_dt = (now() + interval \'7 days\') WHERE troo_pk = $2', ['C', $_troo_pk]);

        // 영웅 상태 변경
        $this->classHero();
        $this->Hero->setCommandCmdType($row['captain_hero_pk'], 'Camp');
        if ($row['director_hero_pk']) {
            $this->Hero->setCommandCmdType($row['director_hero_pk'], 'Camp');
        }
        if ($row['staff_hero_pk']) {
            $this->Hero->setCommandCmdType($row['staff_hero_pk'], 'Camp');
        }

        // PUSH - src
        $this->removeMoveTroop($_troo_pk);
        // $this->Session->sqAppend('PUSH', ['MY_CAMP_TROOP' => true], null, $row['src_lord_pk'], $row['src_posi_pk']);
        // $this->Session->sqAppend('MOVE_TROOP_END', ['troo_pk' => $_troo_pk], null, $row['src_lord_pk'], $row['src_posi_pk']);
        $this->Production->get($row['src_posi_pk']);

        return true;
    }

    // 주둔군 철수 (포기) reason : 철수, 통솔영웅 부재, 주둔한계 초과
    function setStatusRecall($_troo_pk, $row = null, $_withdrawal_auto = null, $force_recall = false): bool
    {
        if (! $row) {
            $row = $this->getTroop($_troo_pk);
        }

        global $NsGlobal;

        if (!$row) {
            $NsGlobal->setErrorMessage('부대 정보를 찾을 수 없습니다.');
            return false;
        } else if (($row['status'] == 'W' || $row['status'] == 'R') && !$force_recall) {
            $NsGlobal->setErrorMessage('이미 취소 또는 회군 중 입니다.');
            return false;
        }

        // src_time_pk 있으면 삭제
        if ($row['src_time_pk'] && $force_recall) {
            $this->cancelTimer($row['src_time_pk']);
        }

        $move_time = $row['triptime'];

        // 영웅 명령 효과
        if ($row['captain_hero_pk']) {
            $this->classEffect();
            $capacities = $this->Effect->getHeroCapacityEffects($row['captain_hero_pk']);
            $applies = $this->Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_CAPTAIN, $capacities);
            $ret = $this->Effect->getEffectedValue($row['src_posi_pk'], ['troop_recall_time_decrease'], $move_time, $applies['all']);
            if (($move_time * 0.1) > $ret['value']) {
                $move_time = intval($move_time * 0.1);
            } else {
                $move_time = intval($ret['value']);
            }
        }

        // 회군 타이머 등록
        $time_pks = $this->setTimer($_troo_pk, $row['cmd_type'], $row['src_lord_pk'], null, $row['src_posi_pk'], null, ['status' => 'R', 'cmd_type' => $row['cmd_type'], 'from_position' => $row['from_position'], 'to_position' => $row['to_position']], $move_time);

        // 관련 부대정보 변경
        $this->PgGame->query("UPDATE troop SET status = $1, src_time_pk = $2, dst_time_pk = $3, start_dt = now(), move_time = $4, move_time_reduce = 0, from_position = $7, to_position = $8, arrival_dt = now() + interval '$move_time second', withdrawal_auto = $6 WHERE troo_pk = $5", ['R', $time_pks['src_time_pk'], $time_pks['dst_time_pk'], $move_time, $_troo_pk, $_withdrawal_auto, $row['to_position'], $row['from_position']]);
        // $this->setTroopMove($_troo_pk);
        $this->getMoveTroop($_troo_pk);

        // 영웅 상태 변경
        $this->Hero->setCommandCmdType($row['captain_hero_pk'], 'Recal');
        if ($row['director_hero_pk']) {
            $this->Hero->setCommandCmdType($row['director_hero_pk'], 'Recal');
        }
        if ($row['staff_hero_pk']) {
            $this->Hero->setCommandCmdType($row['staff_hero_pk'], 'Recal');
        }

        // src PUSH - 회군의 경우 정상적인 철수가 아니라(기존 status 가 C)도 경우에 수가 많으니 그냥 noti 들어감.
        // $this->Session->sqAppend('PUSH', ['MY_CAMP_TROOP' => true], null, $row['src_lord_pk'], $row['src_posi_pk']);
        // $this->Session->sqAppend('MOVE_TROOP_UPDATE', $this->getTroop($_troo_pk), null, $row['src_lord_pk'], $row['src_posi_pk']);

        // dst PUSH
        if ($row['src_lord_pk'] != $row['dst_lord_pk'] && $row['dst_lord_pk'] != NPC_TROOP_LORD_PK) {
            $this->Session->sqAppend('PUSH', ['EMBASSY_CAMP_TROOP' => true], null, $row['dst_lord_pk'], $row['dst_posi_pk']);
        }

        // Log
        $str = 'src_time_pk:[' . $row['src_time_pk'] . '];force_recall[' . $force_recall . '];withdrawal_auto:[' . $_withdrawal_auto . '];';
        $hero = ['captain_hero_pk' => $row['captain_hero_pk'], 'director_hero_pk' => $row['director_hero_pk'], 'staff_hero_pk' => $row['staff_hero_pk']];
        $army = ['army_worker' => $row['army_worker'], 'army_infantry' => $row['army_infantry'], 'army_pikeman' => $row['army_pikeman'],
            'army_scout' => $row['army_scout'], 'army_spearman' => $row['army_spearman'], 'army_armed_infantry' => $row['army_armed_infantry'],
            'army_archer' => $row['army_archer'], 'army_horseman' => $row['army_horseman'], 'army_armed_horseman' => $row['army_armed_horseman'],
            'army_transporter' => $row['army_transporter'], 'army_bowman' => $row['army_bowman'], 'army_battering_ram' => $row['army_battering_ram'],
            'army_catapult' => $row['army_catapult'], 'army_adv_catapult' => $row['army_adv_catapult']];
        $resource = ['round_food' => $row['round_food'], 'round_gold' => $row['round_gold'], 'presence_food' => $row['presence_food'], 'hour_food' => $row['hour_food'], 'timer_info' => $str];
        $this->classLog();
        $this->Log->setTroop($row['src_lord_pk'], $row['src_posi_pk'], 'StatusRecall', $row['dst_lord_pk'], null, $row['dst_posi_pk'], $row['to_position'], json_encode($hero), json_encode($army), json_encode($resource), $_troo_pk);

        return true;
    }

    // 부대정보 - 군주의 자원지
    function getDstTroopFromLordValley($_dst_posi_pk): array|bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['fortification']);

        $troop = false;

        $this->PgGame->query('SELECT * FROM troop WHERE dst_posi_pk = $1 AND status = $2', [$_dst_posi_pk, 'C']);
        $result1 = $this->PgGame->fetch();
        if ($result1) {
            $troop = $this->PgGame->row;
        }

        //fortification
        $this->PgGame->query('SELECT * FROM fortification_valley WHERE posi_pk = $1', [$_dst_posi_pk]);
        $result2 = $this->PgGame->fetch();
        if ($result2) {
            $r = $this->PgGame->row;
            foreach ($_M['FORT_C'] as $k => $v) {
                $troop['fort_' . $k] = $r[$k];
            }
            $troop['valley_posi_pk'] = $r['posi_pk'];
        }

        if (!$result1 && !$result2) {
            return false;
        }
        $troop['wall_open'] = true;
        return $troop;
    }

    // 부대정보 - 군주의 영지 (동맹군 주둔부대정보 포함?)
    function getDstTroopFromLordTerritory($_dst_posi_pk): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army', 'fortification']);

        $this->classGoldPop();
        $this->classResource();

        $troop = [];

        // goldpop
        $ret = $this->GoldPop->get($_dst_posi_pk, '99999999');
        $troop['reso_gold'] = $ret['gold_curr'];

        // resource
        $this->Resource->save($_dst_posi_pk, '99999999');
        $this->PgGame->query('SELECT food_curr, horse_curr, lumber_curr, iron_curr FROM resource WHERE posi_pk = $1', [$_dst_posi_pk]);
        $this->PgGame->fetch();
        $r = $this->PgGame->row;

        $troop['reso_food'] = $r['food_curr'];
        $troop['reso_horse'] = $r['horse_curr'];
        $troop['reso_lumber'] = $r['lumber_curr'];
        $troop['reso_iron'] = $r['iron_curr'];

        //army
        $this->PgGame->query('SELECT * FROM army WHERE posi_pk = $1', [$_dst_posi_pk]);
        $this->PgGame->fetch();
        $r = $this->PgGame->row;

        foreach ($_M['ARMY_C'] as $k => $v) {
            $troop['army_' . $k] = $r[$k];
        }

        //fortification
        $this->PgGame->query('SELECT * FROM fortification WHERE posi_pk = $1', [$_dst_posi_pk]);
        $this->PgGame->fetch();
        $r = $this->PgGame->row;

        foreach ($_M['FORT_C'] as $k => $v) {
            $troop['fort_' . $k] = $r[$k];
        }

        //hero && wall
        $this->PgGame->query('SELECT assign_hero_pk, level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_dst_posi_pk, PK_BUILDING_WALL]);
        $this->PgGame->fetch();
        $troop['captain_hero_pk'] = $this->PgGame->row['assign_hero_pk'];
        $troop['wall_level'] = $this->PgGame->row['level'];

        $this->PgGame->query('SELECT wall_director_hero_pk, wall_staff_hero_pk, status_gate, storage_max FROM territory WHERE posi_pk = $1', [$_dst_posi_pk]);
        $this->PgGame->fetch();

        $troop['director_hero_pk'] = $this->PgGame->row['wall_director_hero_pk'];
        $troop['staff_hero_pk'] = $this->PgGame->row['wall_staff_hero_pk'];

        $troop['wall_open'] = $this->PgGame->row['status_gate'] == 'O';

        $troop['storage_max'] = $this->PgGame->row['storage_max'];

        //$this->PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1', Array($_dst_posi_pk));
        //$troop['wall_level'] = $this->PgGame->fetchOne();

        $military_hero_status = false; // TODO 왜 안쓰냐?

        // 군주 영지의 영웅 부상처리 관련
        $troop['src_posi_pk'] = $_dst_posi_pk;

        if (!$troop['captain_hero_pk']) {
            $this->PgGame->query('SELECT assign_hero_pk FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_dst_posi_pk, PK_BUILDING_MILITARY]);
            $troop['captain_hero_pk'] = $this->PgGame->fetchOne();
            if (!$troop['captain_hero_pk']) {
                $this->classHero();
                $troop['captain_hero_pk'] = $this->Hero->getHeroMaxLeadership($_dst_posi_pk);
            }
            if (!$troop['captain_hero_pk']) {
                $this->PgGame->query('SELECT assign_hero_pk FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_dst_posi_pk, PK_BUILDING_CITYHALL]);
                $troop['captain_hero_pk'] = $this->PgGame->fetchOne();
            }
        }

        $troop['captain_desc'] = $this->getHeroDesc($troop['captain_hero_pk']);

        if (isset($troop['director_hero_pk']) && $troop['director_hero_pk']) {
            $troop['director_desc'] = $this->getHeroDesc($troop['director_hero_pk']);
        }

        if (isset($troop['staff_hero_pk']) && $troop['staff_hero_pk']) {
            $troop['staff_desc'] = $this->getHeroDesc($troop['staff_hero_pk']);
        }

        // 동맹군 주둔부대, 기본 병력에 추가
        $z_arr = $this->getAllyCampArmy($_dst_posi_pk);

        foreach ($_M['ARMY_C'] as $k => $v) {
            $troop['army_' . $k] += $z_arr[$k];
        }

        return $troop;
    }

    // 부대정보 - 황건적 거점 (토벌)
    function getDstTroopFromNpcSuppress($_src_lord_pk, $_dst_posi_pk): false|array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['npc_troop']);

        $this->PgGame->query('SELECT t1.supp_pk, t1.target_level, t2.hero_pk, t2.army_type_1, t2.army_type_2, t2.army_type_3 FROM suppress t1, suppress_position t2 WHERE t1.supp_pk = t2.supp_pk AND t1.lord_pk = $1 AND t2.posi_pk = $2', [$_src_lord_pk, $_dst_posi_pk]);
        $this->PgGame->fetch();
        $r =& $this->PgGame->row;
        if (!$r) {
            return false;
        }

        $m_npc_troo =& $_M['NPC_TROO']['suppress'][$r['target_level']];

        // 가상부대 생성
        $troop['captain_hero_pk'] = $r['hero_pk'];

        $troop['army_' . $r['army_type_1']] = $m_npc_troo[$r['army_type_1']];

        if ($r['army_type_2']) {
            $troop['army_' . $r['army_type_2']] = $m_npc_troo[$r['army_type_2']];
        }

        if ($r['army_type_3']) {
            $troop['army_' . $r['army_type_1']] = $m_npc_troo[$r['army_type_3']];
        }

        $troop['captain_desc'] = $this->getHeroDesc($troop['captain_hero_pk']);
        $troop['wall_open'] = true;

        return $troop;
    }

    // 부대정보 - 황건적 거점 (이벤트 토벌령)
    function getDstTroopFromNpcSuppressEvent($_src_lord_pk, $_dst_posi_pk): false|array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['npc_troop']);

        // 현재 황건적 등급 알아오기
        $this->PgGame->query('SELECT event_supp_level FROM my_event WHERE lord_pk = $1', [$_src_lord_pk]);
        $target_level = $this->PgGame->fetchOne();

        $this->PgGame->query('SELECT t1.supp_pk, t1.target_level, t2.hero_pk, t2.army_type_1, t2.army_type_2, t2.army_type_3 FROM suppress t1, suppress_position t2 WHERE t1.supp_pk = t2.supp_pk AND t1.lord_pk = $1 AND t2.posi_pk = $2', [$_src_lord_pk, $_dst_posi_pk]);
        $this->PgGame->fetch();
        $r =& $this->PgGame->row;

        if (!$r) {
            return false;
        }

        $m_npc_troo =& $_M['NPC_TROO']['assemble'][$target_level];

        // 가상부대 생성
        $troop['captain_hero_pk'] = $r['hero_pk'];

        $troop['army_' . $r['army_type_1']] = $m_npc_troo[$r['army_type_1']];

        if ($r['army_type_2']) {
            $troop['army_' . $r['army_type_2']] = $m_npc_troo[$r['army_type_2']];
        }

        if ($r['army_type_3']) {
            $troop['army_' . $r['army_type_1']] = $m_npc_troo[$r['army_type_3']];
        }

        $troop['captain_desc'] = $this->getHeroDesc($troop['captain_hero_pk']);
        $troop['wall_open'] = true;

        return $troop;
    }

    // 부대정보 - 황건적 자원지
    function getDstTroopFromNpcValley($_dst_posi_pk): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['npc_troop', 'npc_hero']);

        $this->PgGame->query('SELECT type, level FROM position WHERE posi_pk = $1', [$_dst_posi_pk]);
        $this->PgGame->fetch();
        $r =& $this->PgGame->row;

        if ($r['type'] == 'E') {
            $r['type'] = 'R'; // E타입 평지는 농경지 영웅 사용
        } else if ($r['type'] == 'A') {
            $r['type'] = 'L'; // A타입 평지는 저수지 영웅 사용
        }

        // 가상부대 생성
        $troop['captain_hero_pk'] = $_M['NPC_HERO']['resource_' . $r['type']][$r['level']][0];

        foreach ($_M['NPC_TROO']['resource'][$r['level']] as $k => $v) {
            if ($k != 'move_time') {
                $troop['army_' . $k] = $v;
            }
        }

        $troop['captain_desc'] = $this->getHeroDesc($troop['captain_hero_pk']);
        $troop['wall_open'] = true;

        return $troop;
    }

    // 부대정보 - 황건적 요충지
    function getDstTroopFromNpcPoint($_dst_posi_pk): bool|array
    {
        $this->PgGame->query('SELECT captain_hero_pk, captain_desc, director_hero_pk, director_desc, staff_hero_pk, staff_desc, 
army_worker, army_infantry, army_pikeman, army_scout, army_spearman, army_armed_infantry,
army_archer, army_horseman, army_armed_horseman, army_transporter,
army_bowman, army_battering_ram, army_catapult, army_adv_catapult,
npc_bonus, type
FROM position_point WHERE posi_pk = $1', [$_dst_posi_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }

        $troop = $this->PgGame->row;
        $troop['wall_open'] = true;

        return $troop;
    }

    // 부대정보 - 황건적 영지
    function getDstTroopFromNpcTerritory($_dst_posi_pk): bool|array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['npc_territory']);

        // 가상부대 생성
        $this->PgGame->query('SELECT t1.posi_pk, t2.level, loyalty, captain_hero_pk, director_hero_pk, staff_hero_pk, reso_gold, reso_food, reso_horse, reso_lumber, reso_iron, army_worker, army_infantry, army_pikeman, army_scout, army_spearman, army_armed_infantry, army_archer, army_horseman, army_armed_horseman, army_transporter, army_bowman, army_battering_ram, army_catapult, army_adv_catapult, fort_trap, fort_abatis, fort_tower, yn_need_increase, date_part(\'epoch\', now())::integer - date_part(\'epoch\', t1.last_update_dt)::integer AS elapsed_time FROM position_npc t1, position t2 WHERE t1.posi_pk = t2.posi_pk AND t1.posi_pk = $1', [$_dst_posi_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $r =& $this->PgGame->row;

        // 황건적 영지 자원,병력,방어시설 증가 로직
        if ($r['yn_need_increase'] == 'Y' && $r['elapsed_time'] >= POSITION_NPC_INCREASE_TICK) {
            $m =& $_M['NPC_TERR'][$r['level']];
            $tick = floor($r['elapsed_time'] / POSITION_NPC_INCREASE_TICK);
            $next_yn_need_increase = false;
            $z_cnt = 2;
            $z = '';
            $z_arr = [$_dst_posi_pk];

            foreach ($r as $k => $v) {
                $type = substr($k, 0, 5);
                if ($type == 'reso_' || $type == 'army_' || $type == 'fort_') {
                    $max = $m[$k];
                    if ($v < $max) {
                        // 증가 가능량
                        $inc = $tick * $m[$k . '_inc'];

                        if ($v + $inc < $max) {
                            $thistime_inc = $inc;
                            $next_yn_need_increase = true;
                        } else {
                            $thistime_inc = $max - $v;
                        }

                        $r[$k] += $thistime_inc;

                        if ($z != '') {
                            $z .= ', ';
                        }
                        $z .= "$k = $k + \$$z_cnt";
                        $z_arr[] = $thistime_inc;
                        $z_cnt++;
                    }
                }
            }

            // 마지막 증가 처리
            if ($z != '') {
                $z .= ', ';
            }
            if (!$next_yn_need_increase) {
                $z .= "last_update_dt = now(), yn_need_increase = \$$z_cnt";
                $z_arr[] = 'N';
            } else {
                $tick_second = $tick * POSITION_NPC_INCREASE_TICK;
                $z .= "last_update_dt = last_update_dt + interval '$tick_second second'";
            }
            if ($z != '') {
                $this->PgGame->query("UPDATE position_npc SET {$z} WHERE posi_pk = $1", $z_arr);
            }
        }

        // 황건적 영지 레벨별 wall_open 구분
        if ($_M['NPC_TERR'][$r['level']]['status_gate'] == 'O') {
            $r['wall_open'] = true;
        } else {
            $r['wall_open'] = false;
        }

        $r['wall_level'] = $r['level'];

        // 영웅 정보 추가
        $ret = $r;

        $ret['captain_desc'] = $this->getHeroDesc($ret['captain_hero_pk']);
        $ret['director_desc'] = $this->getHeroDesc($ret['director_hero_pk']);
        $ret['staff_desc'] = $this->getHeroDesc($ret['staff_hero_pk']);

        return $ret;
    }

    // 좌표와의 관계 - _lord 는 lord_pk 와 alli_pk 를 포함한 군주정보 배열이다. src 에 해당하는 군주 정보임.
    function getPositionRelation($_dst_posi_pk, $_lord = null): array
    {
        if (!$_lord) {
            $_lord = $this->Session->lord;
        }

        $dst_posi = [];
        $dst_posi['alli_pk'] = null;
        $dst_posi['lord_name'] = '';
        $dst_posi['lord_level'] = null;
        $dst_posi['lord_position_cnt'] = null;
        $dst_posi['lord_name_withLevel'] = '';
        $dst_posi['my_camp_troop'] = 'N';
        $dst_posi['truce_type'] = '';
        $dst_posi['my_troo_pk'] = null;
        $dst_posi['power'] = null;

        $position = $this->getPositionInfo($_dst_posi_pk);

        $dst_posi['lord_pk'] = $position['lord_pk'];
        $dst_posi['name'] = $this->getPositionName($_dst_posi_pk, $position, false);
        $dst_posi['type'] = $position['type'];
        $dst_posi['level'] = $position['level'];

        if ($position['lord_pk'] == NPC_TROOP_LORD_PK) {
            $dst_posi['lord_name'] = '황건적';
            $dst_posi['lord_name_withLevel'] = '황건적';
            $dst_posi['relation'] = 'NPC';
            $dst_posi['truce'] = 'N';
        } else { // 타 군주
            // 중립체크
            if ($position['type'] == 'T') { // 영지
                $this->PgGame->query('SELECT status_truce, truce_type, posi_pk  FROM territory WHERE posi_pk = $1', [$_dst_posi_pk]);
                $this->PgGame->fetch();
                $dst_posi['truce'] = $this->PgGame->row['status_truce'];
                $dst_posi['truce_type'] = $this->PgGame->row['truce_type'];
                $dst_posi['posi_pk'] = $this->PgGame->row['posi_pk'];
            } else if ($position['type'] == 'P') {
                $dst_posi['truce'] = 'N';
            } else { // 자원지
                $this->PgGame->query('SELECT status_truce, truce_type, posi_pk FROM territory WHERE posi_pk = (SELECT posi_pk FROM territory_valley WHERE valley_posi_pk = $1)', [$_dst_posi_pk]);
                $this->PgGame->fetch();
                $dst_posi['truce'] = $this->PgGame->row['status_truce'];
                $dst_posi['truce_type'] = $this->PgGame->row['truce_type'];
                $dst_posi['posi_pk'] = $this->PgGame->row['posi_pk'];
            }

            // 내소유?
            if ($position['lord_pk'] == $_lord['lord_pk']) {
                $dst_posi['relation'] = 'MIME';

                // 내소유데 영지가 부대가 있는지 찾아봐야 함.
                if ($dst_posi['type'] != 'T') {
                    $dst_posi['my_camp_troop'] = 'Y';
                    $dst_posi['my_troo_pk'] = $this->getDestinationTroopPK($_dst_posi_pk);
                }
            } else {
                // 소유주의 동맹 정보 추출
                $this->PgGame->query('SELECT alli_pk, lord_name, level, power, position_cnt FROM lord WHERE lord_pk = $1', [$position['lord_pk']]);
                $this->PgGame->fetch();
                $alli_pk = $this->PgGame->row['alli_pk'];
                $lord_name = $this->PgGame->row['lord_name'];

                $dst_posi['power'] = $this->PgGame->row['power'];
                $dst_posi['alli_pk'] = $alli_pk;
                $dst_posi['lord_name'] = $lord_name;
                $dst_posi['lord_level'] = $this->PgGame->row['level'];
                $dst_posi['lord_position_cnt'] = $this->PgGame->row['position_cnt'];
                $dst_posi['lord_name_withLevel'] = $lord_name . ' Lv.' . $this->PgGame->row['level'];

                // 동맹 여부 체크
                $this->PgGame->query('SELECT alli_pk FROM alliance_member WHERE alli_pk = $1 AND lord_pk = $2 AND type = $3', [$_lord['lord_pk'], $position['lord_pk'], 'Y']);
                if ($this->PgGame->fetchOne()) {
                    $dst_posi['alli_status'] = true;
                }

                if (!$alli_pk) {
                    $dst_posi['relation'] = 'LORD';
                } else if ($alli_pk == $_lord['alli_pk']) {
                    $dst_posi['relation'] = 'ALLY';
                } else {
                    $this->PgGame->query('SELECT rel_type FROM alliance_relation WHERE alli_pk = $1 AND rel_alli_pk = $2', [$_lord['alli_pk'], $alli_pk]);
                    if ($this->PgGame->getNumRows() == 1) {
                        $dst_posi['relation'] = 'ALLY_' . $this->PgGame->fetchOne(); // 관계 설정 되어 있음.
                    } else {
                        $dst_posi['relation'] = 'LORD'; // 관계 없는 동맹
                    }
                }

                // 주둔군 체크 (타군주의 경우 동맹/우호 영지에만 주둔 가능)
                if ($dst_posi['type'] == 'T' && ($dst_posi['relation'] == 'ALLY' || $dst_posi['relation'] == 'ALLY_F')) {
                    if ($this->getMyCampTroopsExist($_dst_posi_pk)) {
                        $dst_posi['my_troo_pk'] = 1;
                        $dst_posi['my_camp_troop'] = 'Y';
                    }
                }
            }
        }

        return $dst_posi;
    }

    // 좌표의 소유 및 유형 정보
    function getPositionInfo($_posi_pk): bool|array
    {
        $this->PgGame->query('SELECT lord_pk, type, level FROM position WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;

        if (!$row['lord_pk']) {
            $row['lord_pk'] = NPC_TROOP_LORD_PK;
        }

        if ($row['type'] == 'D') {
            $this->PgGame->query('SELECT t2.target_level FROM suppress_position t1, suppress t2 WHERE t1.supp_pk = t2.supp_pk AND t1.posi_pk = $1', [$_posi_pk]);
            $row['level'] = $this->PgGame->fetchOne();
        }

        return $row;
    }

    // 좌표 명칭
    function getPositionName($_posi_pk, $_position_info = null, $_with_posi_pk = true): string
    {
        return $this->getPositionTitle($_posi_pk, $_position_info);

        $name = '';
        $r = null;
        if (!$_info_arr) {
            $this->PgGame->query('SELECT type, level FROM position WHERE posi_pk = $1', [$_posi_pk]);
            if ($this->PgGame->fetch()) {
                $r =& $this->PgGame->row;
            }
        } else {
            $r =& $_info_arr;
        }

        // TODO 텍스트 코드로 빼야함.
        if ($r) {
            if ($r['type'] == 'D') {
                $today = date('Y-m-d 00:00:00', time());
                $this->PgGame->query('SELECT count(posi_pk) FROM suppress, suppress_position WHERE suppress.supp_pk = suppress_position.supp_pk AND lord_pk = $1 AND posi_pk = $2 AND regist_dt > $3', [$this->Session->lord['lord_pk'], $_posi_pk, $today]);
                $is_d = $this->PgGame->fetchOne();
                if ($is_d > 0) {
                    $this->PgGame->query('SELECT suppress.target_level FROM suppress, suppress_position WHERE suppress.supp_pk = suppress_position.supp_pk AND lord_pk = $1 AND posi_pk = $2 AND regist_dt > $3', [$this->Session->lord['lord_pk'], $_posi_pk, $today]);
                    $r['level'] = $this->PgGame->fetchOne();

                    // 여기에서도 가지고 오지 못했다면 군주의 레벨을 타겟 레벨로
                    if (!$r['level']) {
                        $r['level'] = $this->Session->lord['level'];
                    }
                    $name = '황건적 거점 Lv.' . $r['level'];
                } else {
                    $name = '불모지';
                }
            } else if ($r['type'] == 'E' || $r['type'] == 'A') {
                $name = '평지';
            } else if ($r['type'] == 'F') {
                $name = '산림 Lv.' . $r['level'];
            } else if ($r['type'] == 'R') {
                $name = '농경지 Lv.' . $r['level'];
            } else if ($r['type'] == 'G') {
                $name = '초원 Lv.' . $r['level'];
            } else if ($r['type'] == 'L') {
                $name = '저수지 Lv.' . $r['level'];
            } else if ($r['type'] == 'M') {
                $name = '광산 Lv.' . $r['level'];
            } else if ($r['type'] == 'N') {
                $name = '황건적 Lv.' . $r['level'];
            } else if ($r['type'] == 'T') {
                $this->PgGame->query('SELECT title FROM territory WHERE posi_pk = $1', [$_posi_pk]);
                $name = $this->PgGame->fetchOne();
            } else if ($r['type'] == 'P') {
                $name = '요충지 Lv.' . $r['level'];
            }
        }

        if ($_with_posi_pk) {
            $name .= ' (' . $_posi_pk . ')';
        }

        // 황건적 거점 Lv.5 (333x333) - D 불모지
        // 평지 (333x333) - E
        // 황건적 Lv.5 (333x333) N
        // 산림 Lv.5 (333x333) - F, R, G, L, M
        // 조조땅 (333x333) - T
        return $name;
    }

    function getPositionTitle ($_posi_pk, $_position_info = null): string
    {
        // TODO 타이머 description 개선을 위하 추가
        if (! isset($_position_info)) {
            $this->PgGame->query('SELECT type, level FROM position WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $_position_info = $this->PgGame->row;
        }

        // system_text.valley = { R:'Farm', L:'Reservoir', M:'Mine', F:'Forest', G:'Valley', N:'Yellow Turban', T:'Territory', A:'Field', E:'Field', D:'Wasteland', NPC_SUPP:'Yellow Turban Hideout', P:'Central Area', NPC_SUPP_EVENT:'Yellow Turban Assembly Point' };
        $type = '';
        $level = (isset($_position_info['level'])) ? $_position_info['level'] : 0;
        if (isset($_position_info)) {
            // 차후에 텍스트 1문자가 아닌 코드형태로 변경해야함.
            $type = match ($_position_info['type']) {
                'D' => 'wasteland', // 황건적 or 불모지
                'E', 'A' => 'field', // 평지
                'F' => 'forest', // 산림
                'R' => 'farm', // 농경지
                'G' => 'grassland', // 초원
                'L' => 'reservoir', // 저수지
                'M' => 'mine', // 광잔
                'N' => 'yellow_turban', // 황건적
                'T' => 'territory', // 영지
                'P' => 'strategic_point', // 요충지
                default => $_position_info['type']
            };

            // 불모지에 경우 황건적 거점이 있을 수 있으므로
            if ($type == 'wasteland') {
                $today = date('Y-m-d 00:00:00', time());
                $this->PgGame->query('SELECT suppress.target_level FROM suppress, suppress_position WHERE suppress.supp_pk = suppress_position.supp_pk AND lord_pk = $1 AND posi_pk = $2 AND regist_dt > $3', [$this->Session->lord['lord_pk'], $_posi_pk, $today]);
                $level = $this->PgGame->fetchOne() ?? $this->Session->lord['level']; // 여기에서도 가지고 오지 못했다면 군주의 레벨을 타겟 레벨로
                $type = 'yellow_turban'; // 황건적 거점 타입으로 덮어씀
            } else if ($type == 'territory') {
                $this->PgGame->query('SELECT title FROM territory WHERE posi_pk = $1', [$_posi_pk]);
                $type = $this->PgGame->fetchOne(); // 영지명을 타입으로
            }
        }

        // 좌표:타입:레벨
        return "$_posi_pk:$type:$level";
    }

    // 지정된 자원지 좌표의 소유 영지 정보 - TODO 2중 try catch 확인 필요.
    function getValleyOwnTerritoryName($valley_posi_pk): mixed
    {
        try {
            $this->PgGame->query('SELECT posi_pk FROM territory_valley WHERE valley_posi_pk = $1', [$valley_posi_pk]);
            if (!$this->PgGame->fetch()) {
                throw new Exception('해당 자원지의 소속 영지 정보를 찾을 수 없습니다.');
            }
            $posi_pk = $this->PgGame->row['posi_pk'];
            return $this->getPositionName($posi_pk);
        } catch (Exception $e) {
            throw new ErrorHandler('error', $e->getMessage(), true);
        }
    }

    // 영웅 설명
    function getHeroDesc($_hero_pk, $_with_level = true): null|string
    {
        $this->PgGame->query('SELECT m_hero_pk, level FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        if (!$this->PgGame->fetch()) {
            return null;
        }

        $m_hero_pk = $this->PgGame->row['m_hero_pk'];
        $level = $this->PgGame->row['level'];

        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero', 'hero_base']);
        $desc = $_M['HERO_BASE'][$_M['HERO'][$m_hero_pk]['m_hero_base_pk']]['name'];

        if ($_with_level) {
            $desc .= ' (Lv.' . $level . ')';
        }
        return $desc;
    }

    // 영웅 m_hero_pk
    function getHeroMasterDataPK($_hero_pk): int|null
    {
        if (! $_hero_pk) {
            return null;
        }
        $this->PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        return $this->PgGame->fetchOne();
    }

    // 병력들의 기타 능력 조회
    function getArmyPop($_army_arr, $_posi_pk = null): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army']);

        $arr = ['need_population' => 0, 'need_food' => 0, 'capacity' => 0, 'troop_speed' => 3000, 'population' => 0];

        if (is_array($_army_arr)) {
            foreach ($_army_arr as $k => $v) {
                if (!$v || $v < 0) {
                    continue;
                }
                $arr['population'] += $v;
                $arr['need_population'] += $_M['ARMY_C'][$k]['need_population'] * $v;
                $arr['need_food'] += $_M['ARMY_C'][$k]['need_food'] * $v;
                $arr['capacity'] += $_M['ARMY_C'][$k]['spec_capacity'] * $v;
                if ($_M['ARMY_C'][$k]['spec_speed'] < $arr['troop_speed']) {
                    $arr['troop_speed'] = $_M['ARMY_C'][$k]['spec_speed'];
                }
            }
        }

        if ($_posi_pk) {
            $this->classEffect();
            $this->Effect->initEffects();
            $ret = $this->Effect->getEffectedValue($_posi_pk, ['troop_transport_increase'], $arr['capacity']);
            $arr['capacity'] = $ret['value'];
        }

        return $arr;
    }

    // 거리 계산
    function getDistance($_src_posi_pk, $_dst_posi_pk): float
    {
        $distance = 0.0;
        $src = explode(DELIMITER_WORLD_COORDS, $_src_posi_pk);
        $dst = explode(DELIMITER_WORLD_COORDS, $_dst_posi_pk);
        $abs_x = abs($src[0] - $dst[0]);
        $abs_y = abs($src[1] - $dst[1]);
        $min_value = min($abs_x, $abs_y) * 1.2;
        $abs_value = abs($abs_x - $abs_y) * 1;
        return $min_value + $abs_value;
    }

    // 이동시간 (출정또는회군 준비시간을 포함한 편도 이동시간)
    function getMoveTime($_posi_pk, $_cmd_type, $_distance, $_army_pop, $_hero_pk = null, $_select_item_pk = null, $_alli_status = false): float|int
    {
        if ($_select_item_pk) {
            $_army_pop['troop_speed'] -= $_army_pop['troop_speed'] * 0.33;
        }

        $move_time = ceil($_distance / $_army_pop['troop_speed'] * 100000);
        $move_time += $this->getReadyForBattle($_cmd_type, $_army_pop['need_population']);

        // 단축 효과 적용
        $this->classEffect();
        $this->Effect->initEffects();
        $effect_types = ['troop_time_decrease'];
        $capacities = [];
        if ($_hero_pk) {
            $capacities = $this->Effect->getHeroCapacityEffects($_hero_pk);
        }
        $applies = $this->Effect->getHeroAppliedCommandEffects(PK_CMD_TRANS, $capacities);
        $ret = $this->Effect->getEffectedValue($_posi_pk, $effect_types, $move_time, $applies['all']);
        // 동맹 단축효과 적용
        $alli_effect = 0;
        if (($_cmd_type == 'R' || $_cmd_type == 'T') && $_alli_status) {
            $alli_effect = $move_time * 0.2;
        }

        if (($move_time * 0.1) > $ret['value']) {
            $move_time = $move_time * 0.1;
        } else {
            $next_move_time = $ret['value'] - $alli_effect;
            if (($move_time * 0.1) > $next_move_time) {
                $move_time = $move_time * 0.1;
            } else {
                $move_time = $next_move_time;
            }
        }

        if ($_cmd_type == 'R' || $_cmd_type == 'T' || $_cmd_type == 'P') {
            $move_time = (int)($move_time / 2);
        }

        return $move_time;
    }

    // 전투 준비 시간
    function getReadyForBattle($_cmd_type, $_pop): int
    {
        global $_M;
        $ready_for_battle = 0;
        foreach ($_M['CODESET']['READY_FOR_BATTLE'][$_cmd_type] as $k => $v) {
            $ready_for_battle = $v;
            if ($_pop <= $k) {
                break;
            }
        }
        return $ready_for_battle;
    }

    // 왕복식량 및 명령별 비상식량 (매개변수는 변도 이동시간) TODO $_dst_posi 왜 받냐?
    function getNeedFood($_cmd_type, $_move_time, $_camp_time, $_hour_food, $_army_arr, $_dst_posi): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army']);

        $round_food = 0;
        foreach ($_army_arr as $k => $v) {
            if (!$v || $v < 0) {
                continue;
            }
            $round_food += $_M['ARMY_C'][$k]['need_food'] * (($_move_time * 2 + $_camp_time) / 3600) * $v;
        }

        if ($round_food < 0) {
            $round_food = 1;
        }
        $presence_food = intval($_hour_food / 2); // hour_food 가 비상식량 일때는 7일치임.
        return [$round_food, $presence_food];
    }

    // 목적지의 부대의 troo_pk
    function getDestinationTroopPK($_dst_posi_pk)
    {
        $this->PgGame->query('SELECT troo_pk FROM troop WHERE dst_posi_pk = $1 AND status = $2', [$_dst_posi_pk, 'C']);
        return $this->PgGame->fetchOne();
    }

    // 야간 실드 여부
    function getNightShield(): int
    {
        $now_hour = date('G');
        return ($now_hour >= 2 && $now_hour < 9) ? 2 : 1;
    }

    // 점령지 상실 (철수, 자동철수, 전투패배)
    function lossOwnership($_lord_pk, $_posi_pk): bool
    {
        $this->PgGame->query('SELECT type FROM position WHERE posi_pk = $1', [$_posi_pk]);
        $type = $this->PgGame->fetchOne();

        return match ($type) {
            'N', 'T' => false,
            default => $this->lossOwnershipValley($_lord_pk, $_posi_pk),
        };
    }

    // 군주의 자원지 상실 (철수, 자동철수, 전투패배)
    function lossOwnershipValley($_lord_pk, $_valley_posi_pk): bool
    {
        $this->classResource();
        $this->classFigureReCalc();
        $this->classBdic();
        $this->classQuest();
        $this->classLord();

        $this->PgGame->query('SELECT a.posi_pk, b.lord_pk, b.type, b.level, b.current_point, date_part(\'epoch\', b.update_point_dt) as update_point_dt FROM territory_valley a, position b WHERE a.valley_posi_pk = b.posi_pk AND a.valley_posi_pk = $1', [$_valley_posi_pk]);
        if ($this->PgGame->fetch()) {
            $already_posi_pk = $this->PgGame->row['posi_pk'];
            $already_lord_pk = $this->PgGame->row['lord_pk'];
            $type = $this->PgGame->row['type'];
            $level = $this->PgGame->row['level'];
            $current_point = $this->PgGame->row['current_point'];
            $update_point_dt = $this->PgGame->row['update_point_dt'];

            if ($already_lord_pk != $_lord_pk) {
                Debug::debugMessage('ERROR', '비소유 자원지를 상실 하려함.');
                return false;
            }
        } else {
            Debug::debugMessage('ERROR', '자원지에 소유주 자체가 없음.');
            return false;
        }

        global $NsGlobal, $_M;
        $NsGlobal->requireMasterData(['productivity_valley']);

        $now_time = Useful::microTimeFloat();
        $_need_point = 0;
        if ($current_point > 0) {
            $_need_point = bcdiv(bcmul(bcsub($now_time, $update_point_dt), $_M['PROD_VALL'][$type][$level]['occupation_point']), 3600, 2);
            if ($current_point < $_need_point) {
                $_need_point = $current_point;
            }
            if ($_need_point > 0) {
                $this->PgGame->query('INSERT INTO occupation_point as op (lord_pk, point) VALUES($1, $2) ON CONFLICT (lord_pk) DO UPDATE SET point = op.point + $2', [$already_lord_pk, $_need_point]);
            }
        }

        $this->PgGame->query('UPDATE position SET lord_pk = NULL, last_update_dt = now(), current_point = current_point - $2, update_point_dt = now() WHERE posi_pk = $1', [$_valley_posi_pk, $_need_point]);

        // 생산 중인 방어시설이 있다면 모두 제거
        $this->PgGame->query('SELECT t1.buil_pk, t1.concurr_curr FROM build t1, territory_valley t2 WHERE t1.posi_pk = t2.posi_pk AND t2.valley_posi_pk = $1 AND t1.type = $2', [$_valley_posi_pk, 'W']);
        $this->PgGame->fetch();

        if ($this->PgGame->row) {
            $buil_pk = $this->PgGame->row['buil_pk'];
            $concurr_curr = $this->PgGame->row['concurr_curr'];
            // 방어시설 설치관련 정보 가져옴
            $this->PgGame->query('SELECT buil_fort_vall_pk FROM build_fortification_valley WHERE buil_pk = $1 AND status = $2', [$buil_pk, 'P']);
            $buil_fort_vall_pk = $this->PgGame->fetchOne();

            if (isset($buil_fort_vall_pk)) {
                // build fortification 정리
                $this->PgGame->query('DELETE FROM build_fortification_valley WHERE buil_fort_vall_pk = $1', [$buil_fort_vall_pk]);
                // 타이머 정리
                $this->PgGame->query('DELETE FROM timer WHERE queue_pk = $1 AND queue_type = $2', [$buil_fort_vall_pk, 'W']);
                // 마지막 으로 build 의 curr 값 정리 (방어시설은 한번에 10개를 관리하고 각 자원지마다 하나씩만 건설 가능하므로 -1)
                if ($concurr_curr > 1) {
                    $this->PgGame->query('UPDATE build SET concurr_curr = concurr_curr - 1 WHERE buil_pk = $1', [$buil_pk]);
                } else {
                    $this->PgGame->query('UPDATE build SET concurr_curr = 0, status = $2 WHERE buil_pk = $1', [$buil_pk, 'I']);
                }
            }
        }

        // Delete
        $this->PgGame->query('DELETE FROM territory_valley WHERE posi_pk = $1 AND valley_posi_pk = $2', [$already_posi_pk, $_valley_posi_pk]);

        // 자원지 방어시설 초기화
        $this->PgGame->query('UPDATE fortification_valley SET trap = 0, abatis = 0, tower = 0 WHERE posi_pk = $1', [$_valley_posi_pk]);

        // 소유주의 production_valley 감산
        $this->FigureReCalc->dispatcher($already_posi_pk, 'VALLEY_UPDATE', $_valley_posi_pk);

        // 영향력 감소
        $this->Lord->decreasePower($_lord_pk, 50);

        // position_area, position_region table ru_curr update
        if ($type == 'E') {
            $this->PgGame->query('SELECT posi_area_pk, posi_regi_pk FROM position_area WHERE posi_area_pk = (SELECT posi_area_pk FROM position WHERE posi_pk = $1)', [$_valley_posi_pk]);
            $this->PgGame->fetch();
            $row = $this->PgGame->row;
            // area
            $this->PgGame->query('UPDATE position_area SET ru_curr = ru_curr - 1 WHERE posi_area_pk = $1', [$row['posi_area_pk']]);
            // region
            $this->PgGame->query('UPDATE position_region SET ru_curr = ru_curr - 1 WHERE posi_regi_pk = $1', [$row['posi_regi_pk']]);
            // state
            $this->PgGame->query('SELECT posi_stat_pk FROM position_region WHERE posi_regi_pk = $1', [$row['posi_regi_pk']]);
            $this->PgGame->query('UPDATE position_state SET ru_curr = ru_curr - 1 WHERE posi_stat_pk = $1', [$this->PgGame->fetchOne()]);
        }

        $this->Session->sqAppend('WORLD', ['posi_pk' => $_valley_posi_pk, 'color' => null], null, $_lord_pk);

        // Log
        $this->classLog();
        $this->Log->setTerritory($_lord_pk, $already_posi_pk, 'loss_valley', 'valley_posi_pk[' . $_valley_posi_pk . '];decrease_power[50];');

        return true;
    }

    // 군주의 자원지 획득 (전투승리)
    function acquiredOwnershipValley($_lord_pk, $_posi_pk, $_valley_posi_pk, $_dst_lord_pk): array
    {
        $this->classResource();
        $this->classFigureReCalc();
        $this->classBdic();
        $this->classQuest();
        $this->classLord();

        $this->PgGame->query('SELECT a.posi_pk, b.lord_pk FROM territory_valley a, position b WHERE a.valley_posi_pk = b.posi_pk AND a.valley_posi_pk = $1', [$_valley_posi_pk]);
        if ($this->PgGame->fetch()) {
            Debug::debugMessage('ERROR', 'somebody has ownership.');
            return ['ret' => false];
        } else {
            $this->PgGame->query('SELECT type, level FROM position WHERE posi_pk = $1', [$_valley_posi_pk]);
            $this->PgGame->fetch();
            $type = $this->PgGame->row['type'];
            $valley_level = $this->PgGame->row['level'];
            if ($type == 'N' || $type == 'T' || $type == 'D' || $type == 'P') {
                Debug::debugMessage('ERROR', '자원지가 아닙니다.');
                return ['ret' => false];
            }
        }

        // 행정부 레벨에 따른 외부 자원지 소지갯수 검사
        if (!$this->Bdic->administrationVariationCheck($_posi_pk)) {
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, '보유 가능한 자원지 갯수를 초과하였습니다.');
            return ['ret' => false, 'valley_cnt_not' => true];
        }

        // Insert
        $this->PgGame->query('INSERT INTO territory_valley VALUES($1, $2, now())', [$_posi_pk, $_valley_posi_pk]);
        $this->PgGame->query('UPDATE position SET lord_pk = $1, last_update_dt = now(), update_point_dt = now() WHERE posi_pk = $2', [$_lord_pk, $_valley_posi_pk]);
        $this->FigureReCalc->dispatcher($_posi_pk, 'VALLEY_UPDATE', $_valley_posi_pk);

        // 퀘스트 처리
        if ($type == 'A' || $type == 'E') {
            if ($type == 'E') {
                // position_area, position_region table ru_curr update
                $this->PgGame->query('SELECT posi_area_pk, posi_regi_pk FROM position_area WHERE posi_area_pk = (SELECT posi_area_pk FROM position WHERE posi_pk = $1)', [$_valley_posi_pk]);
                $this->PgGame->fetch();
                $row = $this->PgGame->row;

                $this->PgGame->query('UPDATE position_area SET ru_curr = ru_curr + 1 WHERE posi_area_pk = $1', [$row['posi_area_pk']]);

                $this->PgGame->query('UPDATE position_region SET ru_curr = ru_curr + 1 WHERE posi_regi_pk = $1', [$row['posi_regi_pk']]);

                // state
                $this->PgGame->query('SELECT posi_stat_pk FROM position_region WHERE posi_regi_pk = $1', [$row['posi_regi_pk']]);
                $this->PgGame->query('UPDATE position_state SET ru_curr = ru_curr + 1 WHERE posi_stat_pk = $1', [$this->PgGame->fetchOne()]);
            }

            $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'battle', 'type' => 'occupation_empty']);
        } else {
            // NPC가 소유 중 이였을 때만
            if (!$_dst_lord_pk || $_dst_lord_pk == NPC_TROOP_LORD_PK) {
                $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'battle', 'type' => 'occupation_valley', 'level' => $valley_level]);
            }
        }

        // 영향력 추가
        $this->Lord->increasePower($_lord_pk, 50);

        // TODO - 자원지의 경우 자원 확보 퀘스트 체크하도록
        $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'territory', 'posi_pk' => $_posi_pk]);

        $this->Session->sqAppend('WORLD', ['posi_pk' => $_valley_posi_pk, 'color' => 'my'], null, $_lord_pk);

        // Log
        $this->classLog();
        $this->Log->setTerritory($_lord_pk, $_posi_pk, 'acquired_valley', 'valley_posi_pk[' . $_valley_posi_pk . '];increase_power[50];dst_lord_pk[' . $_dst_lord_pk . '];');

        return ['ret' => true];
    }

    // 군주의 요충지 점령
    function acquiredOwnershipPoint($_lord_pk, $_posi_pk, $_dst_lord_pk, $_src_posi_pk): array
    {
        $this->classResource();
        $this->classFigureReCalc();
        $this->classBdic();
        $this->classQuest();
        $this->classLord();
        $this->classLog();

        // 1. NPC인지 확인하고, 최초 점령한것인지 확인 - 최초 점령일 경우 보너스 포인트(2000)
        // 2. 요충지 정보 업데이트(lord_pk, occu_bonus)
        // 3. 점령 효과 재계산(이전 점령하고 있던 군주와 새롭게 점령한 군주)
        // 4. position update(깃발 갱신이 필요함)
        // NPC인지 확인하고, 최초 점령한것인지 확인 - 최초 점령일 경우 보너스 포인트(1000)
        $this->PgGame->query('SELECT lord_pk, occu_bonus FROM position_point WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        if ($row['lord_pk'] == 1 && $row['occu_bonus'] == 'N') {
            // 이전 포인트
            $this->PgGame->query('SELECT occu_point, bonus_point FROM ranking_point WHERE posi_pk = $1 AND lord_pk = $2', [$_posi_pk, $_lord_pk]);
            $this->PgGame->fetch();
            // 내 랭킹 점수 업데이트
            if ($this->PgGame->row) {
                $prev_point = $this->PgGame->row['occu_point'] + $this->PgGame->row['bonus_point'];
                $this->PgGame->query('UPDATE ranking_point SET bonus_point = bonus_point + $1, occu_dt = now() WHERE posi_pk = $2 AND lord_pk = $3', [2000, $_posi_pk, $_lord_pk]);
                if ($this->PgGame->getAffectedRows() != 1) {
                    Debug::debugMessage('ERROR', '요충지 최초 점령 포인트 획득 실패;lord_pk['.$_lord_pk.'];posi_pk['.$_posi_pk.'];occu_point[2000];');
                } else {
                    // LOG
                    $this->Log->setPoint($_lord_pk, $_posi_pk, 'occu_bonus', $_posi_pk, 'prev:[' . $prev_point . '];change:[2000];after:[' . ($prev_point + 2000) . '];');
                }
            } else {
                $prev_point = 0;
                $this->PgGame->query('INSERT INTO ranking_point (posi_pk, lord_pk, occu_dt, last_tick_up_dt, bouns_point) VALUES ($2, $3, now(), now(), $1)', [2000, $_posi_pk, $_lord_pk]);
                if ($this->PgGame->getAffectedRows() != 1) {
                    Debug::debugMessage('ERROR', '요충지 최초 점령 포인트 획득 실패;lord_pk['.$_lord_pk.'];posi_pk['.$_posi_pk.'];occu_point[2000];');
                } else {
                    // LOG
                    $this->Log->setPoint($_lord_pk, $_posi_pk, 'occu_bonus', $_posi_pk, 'prev:[' . $prev_point . '];change:[2000];after:[' . ($prev_point + 2000) . '];');
                }
            }
        }

        // 요충지 업데이트 (lord_pk, occu_bonus)
        $this->PgGame->query('SELECT lord_pk FROM ranking_point WHERE posi_pk = $1 AND lord_pk = $2', [$_posi_pk, $_lord_pk]);
        if (!$this->PgGame->fetch()) {
            $this->PgGame->query('INSERT INTO ranking_point (posi_pk, lord_pk, occu_dt, last_tick_up_dt) VALUES ($1, $2, now(), now())', [$_posi_pk, $_lord_pk]);
        } else {
            $this->PgGame->query('UPDATE ranking_point SET occu_dt = now(), last_tick_up_dt = now() WHERE posi_pk = $1 AND lord_pk = $2', [$_posi_pk, $_lord_pk]);
        }

        $this->PgGame->query('UPDATE position_point SET lord_pk = $1, occu_bonus = $2, prev_lord_pk = $3, last_occu_up_dt = now(), occu_dt = now() WHERE posi_pk = $4', [$_lord_pk, 'Y', $_dst_lord_pk, $_posi_pk]);

        // 대륙맵 정보 업데이트
        $this->PgGame->query('UPDATE position SET lord_pk = $1, last_update_dt = now(), update_point_dt = now() WHERE posi_pk = $2', [$_lord_pk, $_posi_pk]);

        // 점령 효과 추가
        $this->classItem();
        $this->Item->useBuffItem($_src_posi_pk, POSITION_POINT_EFFECT_ITEM);

        // LOG
        $this->Log->setPoint($_lord_pk, $_posi_pk, 'point_acquired', $_posi_pk);

        return ['ret' => true];
    }

    // 군주의 요충지 상실 (철수, 자동철수, 전투패배)
    function lossOwnershipPoint($_lord_pk, $_point_posi_pk, $_self_recall = 'N'): true
    {
        // 점령시간 계산
        $this->PgGame->query('SELECT date_part(\'epoch\', last_tick_up_dt)::integer as last_up_dt, date_part(\'epoch\', now())::integer as now_dt, date_part(\'epoch\', last_occu_up_dt)::integer as last_occu_up_dt, date_part(\'epoch\', occu_dt)::integer as occu_dt FROM ranking_point WHERE posi_pk = $1 AND lord_pk = $2', [$_point_posi_pk, $_lord_pk]);
        $this->PgGame->fetch();
        $now_dt = $this->PgGame->row['now_dt'];
        $last_up_dt = $this->PgGame->row['last_up_dt'];
        $occu_up_dt_time = $now_dt - $this->PgGame->row['last_occu_up_dt'];
        $occu_dt = $now_dt - $this->PgGame->row['occu_dt'];

        $occu_point = 0;
        if ($this->PgGame->row['last_occu_up_dt'] > 0 && $occu_up_dt_time >= POINT_OCCUPATION_BONUS_TIME_24) {
            if ($occu_dt >= POINT_OCCUPATION_BONUS_TIME_96) {
                $occu_point = 24000;
            } else if ($occu_dt >= POINT_OCCUPATION_BONUS_TIME_72) {
                $occu_point = 12000;
            } else if ($occu_dt >= POINT_OCCUPATION_BONUS_TIME_48) {
                $occu_point = 6000;
            } else if ($occu_dt >= POINT_OCCUPATION_BONUS_TIME_24) {
                $occu_point = 3000;
            }
        }

        $this->classLog();

        $point = floor(($now_dt - $last_up_dt) / 60);

        if ($point > 0) {
            if ($point > 10) {
                $point = 10;
            }
            $point = $point + $occu_point;

            // 이전 포인트
            $this->PgGame->query('SELECT occu_point, bonus_point FROM ranking_point WHERE posi_pk = $1 AND lord_pk = $2', [$_point_posi_pk, $_lord_pk]);
            $this->PgGame->fetch();
            $prev_point = $this->PgGame->row['occu_point'] + $this->PgGame->row['bonus_point'];

            $this->PgGame->query('UPDATE ranking_point SET occu_point = occu_point + $1, last_occu_up_dt = null WHERE posi_pk = $2 AND lord_pk = $3', [$point, $_point_posi_pk, $_lord_pk]);

            $this->Log->setPoint($_lord_pk, $_point_posi_pk, 'occu_point', $_point_posi_pk, 'prev:[' . $prev_point . '];change:[' . $point . '];after:[' . ($prev_point + $point) . '];');
        }

        $this->PgGame->query('UPDATE ranking_point SET last_occu_up_dt = null, occu_dt = null WHERE posi_pk = $1 AND lord_pk = $2', [$_point_posi_pk, $_lord_pk]);

        if ($_self_recall == 'Y') {
            $this->PgGame->query('UPDATE ranking_point SET self_recall_dt = now() WHERE posi_pk = $1 AND lord_pk = $2', [$_point_posi_pk, $_lord_pk]);
        }

        // 점령 효과제거
        $this->classItem();
        $this->PgGame->query('SELECT posi_pk FROM position WHERE lord_pk = $1 AND type = $2', [$_lord_pk, 'T']);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        foreach ($rows as $v) {
            $this->classTimer();
            // queue_pk 찾기
            $this->PgGame->query('SELECT terr_item_buff_pk FROM territory_item_buff WHERE posi_pk = $1 AND m_item_pk = $2', [$v['posi_pk'], POSITION_POINT_EFFECT_ITEM]);
            $queue_pk = $this->PgGame->fetchOne();
            if (isset($queue_pk)) {
                // time_pk 찾기
                $this->PgGame->query('SELECT time_pk FROM timer WHERE posi_pk = $1 AND status = $2 AND queue_type = $3 AND queue_pk = $4', [$v['posi_pk'], 'P', 'B', $queue_pk]);
                $this->Timer->speedup($this->PgGame->fetchOne(), POSITION_POINT_OCCU_DURATION);
            }
        }

        // LOG
        $this->Log->setPoint($_lord_pk, $_point_posi_pk, 'point_loss', $_point_posi_pk);

        return true;
    }

    // 요충지 부대 생성
    function setNpcPoint($_posi_pk): void
    {
        // 부대 생성
        $this->PgGame->query('SELECT a.level, b.type FROM m_point a, position_point b WHERE a.m_posi_pk = $1 AND a.m_posi_pk = b.posi_pk', [$_posi_pk]);
        $this->PgGame->fetch();
        $posi_info = $this->PgGame->row;

        $this->PgGame->query('SELECT worker, infantry, pikeman, spearman, scout, archer, horseman, transporter, armed_infantry, armed_horseman, bowman, battering_ram, catapult, adv_catapult FROM m_point_npc_troop WHERE level = $1 AND type = $2', [$posi_info['level'], $posi_info['type']]);
        $this->PgGame->fetch();
        $army_info = $this->PgGame->row;

        $this->PgGame->query('UPDATE position_point SET
lord_pk = $2,
army_worker = $3, army_infantry = $4, army_pikeman = $5, army_scout = $6, army_spearman = $7, army_armed_infantry = $8,
army_archer = $9, army_horseman = $10, army_armed_horseman = $11, army_transporter = $12,
army_bowman = $13, army_battering_ram = $14, army_catapult = $15, army_adv_catapult = $16,
prev_lord_pk = $17
WHERE posi_pk = $1', [$_posi_pk, 1,
            $army_info['worker'], $army_info['infantry'], $army_info['pikeman'], $army_info['scout'],
            $army_info['spearman'], $army_info['armed_infantry'], $army_info['archer'], $army_info['horseman'],
            $army_info['armed_horseman'], $army_info['transporter'], $army_info['bowman'], $army_info['battering_ram'],
            $army_info['catapult'], $army_info['adv_catapult'], 1]);

        // 요충지는 불모지이므로 점령치 포인트를 갱신해줄 필요가 없음
        $this->PgGame->query('UPDATE position SET lord_pk = null, last_update_dt = now() WHERE posi_pk = $1', [$_posi_pk]);
    }

    // 토벌령 발생 처리
    function setNpcSuppress($_lord_pk, $_level, $_posi_pk): bool
    {
        // 발생가능여부 검사
        $this->PgGame->query('SELECT level, last_supp_pk, date_part(\'epoch\', last_suppress_dt)::integer as last_suppress_dt FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $this->PgGame->fetch();
        $last = $this->PgGame->row;

        // 군주레벨 제한
        if ($last['level'] < 2) {
            return false;
        }

        $midnight = mktime(0, 0, 0);
        if ($last['last_suppress_dt'] >= $midnight && $last['last_supp_pk']) {
            // already set the suppress data
            return false;
        }

        $this->PgGame->query('UPDATE suppress_position SET status = $1 WHERE supp_pk = $2 AND status = $3', ['C', $last['last_supp_pk'], 'N']);

        // 마스터데이터
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['npc_troop', 'npc_hero']);
        $m_npc_troo =& $_M['NPC_TROO']['suppress'][$_level];
        $m_npc_hero =& $_M['NPC_HERO']['suppress'][$_level];

        // 영웅 섞기
        shuffle($m_npc_hero);

        // 병과 섞기
        $army_type = [];
        foreach ($m_npc_troo as $k => $v) {
            if ($k != 'move_time' && $v > 0) {
                $army_type[] = $k;
            }
        }
        shuffle($army_type);

        // 토벌 좌표 수
        $target_cnt = rand(1, 3);
        $loop_cnt = 0;

        // get near disable cell
        $disable_cells = [];
        for ($i = 0; $i < $target_cnt; $i++) {
            $z_idx = rand(1, 8);
            $z_adjust = rand(7, 14);
            if (!$z_idx) {
                $z_idx = 1;
            }
            if (!$z_adjust) {
                $z_adjust = 7;
            }

            $this->PgGame->query('SELECT getsuppressdisablecell($1, $2, $3)', [$_posi_pk, $z_idx, $z_adjust]);
            $target_posi_pk = $this->PgGame->fetchOne();
            if (!$target_posi_pk || in_array($target_posi_pk, $disable_cells)) {
                $i--;
            } else {
                $disable_cells[] = $target_posi_pk;
            }
            $loop_cnt++;
            if ($loop_cnt > 10) {
                break;
            }
        }

        // disable cell 못찾았을 경우에 개수가 달라짐.
        $target_cnt = COUNT($disable_cells);

        // suppress 입력
        $this->PgGame->query('INSERT INTO suppress (lord_pk, target_level, target_cnt) VALUES ($1, $2, $3)', [$_lord_pk, $_level, $target_cnt]);
        $supp_pk = $this->PgGame->currSeq('suppress_supp_pk_seq');

        // suppress_position 입력
        $suppress_info = [];
        for ($i = 0; $i < $target_cnt; $i++) {
            $army_type_1 = null;
            $army_type_2 = null;
            $army_type_3 = null;

            if ($target_cnt == 3) {
                $army_type_1 = array_pop($army_type);
            } else if ($target_cnt == 2) {
                $army_type_1 = array_pop($army_type);
                $army_type_2 = array_pop($army_type);
            } else { // 1
                $army_type_1 = array_pop($army_type);
                $army_type_2 = array_pop($army_type);
                $army_type_3 = array_pop($army_type);
            }

            // 임시 땜빵
            if (!$army_type_1) {
                $army_type_1 = 'worker';
            }

            $this->PgGame->query('INSERT INTO suppress_position (supp_pk, posi_pk, hero_pk, army_type_1, army_type_2, army_type_3) VALUES ($1, $2, $3, $4, $5, $6)', [$supp_pk, $disable_cells[$i], $m_npc_hero[$i], $army_type_1, $army_type_2, $army_type_3]);

            $suppress_info[$i]['posi_pk'] = $disable_cells[$i];
            $suppress_info[$i]['army_type'] = $army_type_1 . ';' . $army_type_2 . ';' . $army_type_3;
        }

        // lord 갱신
        $this->PgGame->query('UPDATE lord SET last_supp_pk = $1, last_suppress_dt = now() WHERE lord_pk = $2', [$supp_pk, $_lord_pk]);

        // position 갱신
        $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE posi_pk = ANY($1)', ['{' . implode(',', $disable_cells) . '}']);

        // 외교서신
        $letter = [];
        $letter['type'] = 'S';
        $letter['title'] = '황건적을 토벌하라';
        // $letter['content'] = '<div style="float:left;width:150px;height:158px;"><div class="stamp"></div></div><div style="float:left;width:395px;height:158px;"><div class="suppress_content">';
        $letter['content'] = '<div>';

        $npcOffset = implode(', ', $disable_cells);

        // TODO 텍스트 코드로 빼야함.
        if (count($disable_cells) == 1) {
            $letter['content'] .= '짐이 이르노라.';
        } else if (count($disable_cells) == 2) {
            $letter['content'] .= '참으로 통탄할 노릇이구나.';
        } else if (count($disable_cells) == 3) {
            $letter['content'] .= '오호, 통재라.';
        }

        $z_today = date('YmdHis');
        if ($z_today >= 20110907170000 && $z_today <= 20110913235959) {
            $letter['content'] .= <<< EOF

황건의 무리들이 난을 일으킨지 한해가 흘러갔거늘 아직도 잔당들이 짐의 백성들을 유린하고 있으니 짐은 그저 멀리서 통탄할 따름이다.
그대는 속히 군을 이끌고 나가 황건의 잔당들을 치고 나라와 백성들을 평안케 하라.

그대가 공적을 세운다면 백성을 굽어 살피는
그대에게 포상을 내리겠다.
 
기간 : 9월 7일 17시00분 ~ 9월 13일 23시59분까지 
포상 : 한가위 보따리

황건적 잔당의 좌표 : {$npcOffset}</div>
EOF;
        } else {
            $letter['content'] .= <<< EOF

황건의 무리들이 난을 일으킨지 한해가 흘러갔거늘 아직도 잔당들이 짐의 백성들을 유린하고 있으니 짐은 그저 멀리서 통탄할 따름이다.
그대는 속히 군을 이끌고 나가 황건의 잔당들을 치고 나라와 백성들을 평안케 하라.

황건적 잔당의 좌표 : {$npcOffset}</div>
EOF;
        }

        $this->classLetter();
        $this->Letter->sendLetter(EMPEROR_LORD_PK, [$_lord_pk], $letter, true, 'Y');

        //Log
        $this->classLog();
        $this->Log->setSuppress($_lord_pk, $_posi_pk, 'setNpcSuppress', $supp_pk, $_level, $target_cnt, 0, null, $suppress_info[0]['posi_pk'] ?? null, $suppress_info[1]['posi_pk'] ?? null, $suppress_info[2]['posi_pk'] ?? null, $suppress_info[0]['army_type'] ?? null, $suppress_info[1]['army_type'] ?? null, $suppress_info[2]['army_type'] ?? null);

        return true;
    }

    // 황건적 토벌!
    function doNpcSuppress($_lord_pk, $_posi_pk): array|null
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['item']);

        // suppress 체크
        $this->PgGame->query('SELECT supp_pk, target_level, target_cnt, suppress_cnt FROM suppress WHERE supp_pk = (SELECT last_supp_pk FROM lord WHERE lord_pk = $1)', [$_lord_pk]);

        if ($this->PgGame->fetch()) {
            $r = $this->PgGame->row;
            //restrict suppress wave
            $this->PgGame->query('UPDATE suppress_position SET status = $1, suppress_dt = now() WHERE supp_pk = $2 AND posi_pk = $3 AND status = $4', ['Y', $r['supp_pk'], $_posi_pk, 'N']);
            if ($this->PgGame->getAffectedRows() != 1) {
                return null;
            }

            // 로그 저장 데이터
            $this->PgGame->query('SELECT army_type_1, army_type_2, army_type_3 FROM suppress_position WHERE supp_pk = $1 AND posi_pk = $2', [$r['supp_pk'], $_posi_pk]);
            $this->PgGame->fetch();
            $suppress_info = $this->PgGame->row;

            // position 업데이트 (world update)
            $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE posi_pk = $1', [$_posi_pk]);

            // suppress 갱신
            if ($r['target_cnt'] == $r['suppress_cnt'] + 1) {
                $add_update_query = ", status = 'Y', suppress_dt = now()";

                // 외교서신 및 아이템 지급
                $this->classLetter();

                $letter = [];
                $letter['type'] = 'S';
                $letter['title'] = '황제폐하로 부터 하달된 황건적 토벌을 완료하였습니다!';
                $letter['content'] = '대륙에 나타난 모든 황건적 거점을 토벌 하였습니다!';

                // 추가 보상
                $z_today = date('YmdHis'); // 추가 보상은 특정일에만 지급되어야함

                //if (($z_today >= 20140129000000 && $z_today <= 20140204235959) || ($z_today >= 20140121000000 && $z_today <= 20140121175959)) // 14.1.29 0시 ~ 14.2.4 24시 전까지
                {
                    $reward_items = [];
                    /*
                     * 추가 보상은 $reward_items[] = Array('m_iwtem_pk' => 아이템PK, 'item_cnt' => 수량); 식으로 추가해주면 됨.
                     */

                    $reward_items[] = ['m_item_pk' => 500166, 'item_cnt' => 1];
                    // $reward_items[] = Array('m_item_pk' => 500787, 'item_cnt' => 1); // 15년 2월 이벤트 - 3/11 까지

                    // 교환권 이벤트 9/3 ~ 10/1
                    //$reward_items[] = Array('m_item_pk' => 500220, 'item_cnt' => 3);

                    /* if (($z_today >= 20140131000000 && $z_today <= 20140131235959) || ($z_today >= 20140121140000 && $z_today <= 20140121155959)) // 14.1.31 0시 ~ 14.1.31 24시 전까지
                    {
                        $reward_items[] = Array('m_item_pk' => 500128, 'item_cnt' => 1); // 큐빅팩 (100) 31일 단 하루 동안만 지급
                    } */

                    // 추가 보상이 있다면
                    if (count($reward_items) > 0) {
                        // 외교서신에 내용 추가
                        $letter['content'] .= '<br /><br />토벌령 보상으로 아래 아이템이 보물창고에 지급되었습니다. <br />';

                        $this->classItem();

                        $i = 0;
                        foreach ($reward_items as $k => $v) {
                            // 아이템 지급
                            $this->Item->BuyItem($_lord_pk, $v['m_item_pk'], $v['item_cnt'], 'troop');

                            $letter['content'] .= '<br />- ';

                            // 외교서신에 내용 추가
                            $letter['content'] .= $_M['ITEM'][$v['m_item_pk']]['title'] . 'x' . $v['item_cnt'];

                            $i++;
                        }

                        $letter['content'] .= '<br /><br />감사합니다.';
                    }
                }

                $this->Letter->sendLetter(ADMIN_LORD_PK, [$_lord_pk], $letter, true, 'Y');

                // 토벌령 이벤트 퀘스트 14/10/2 ~
                //$this->getQuestClass();
                //$this->Quest->conditionCheckQuest($_lord_pk, Array('quest_type' => 'daily_dispatch', 'm_ques_pk' => 600106));

                //Log
                $this->PgGame->query('DELETE FROM suppress_position WHERE supp_pk = $1', [$r['supp_pk']]);
                $this->PgGame->query('DELETE FROM suppress WHERE supp_pk = $1', [$r['supp_pk']]);
                $this->classLog();
                $suppress_dt = 'now()';
                $this->Log->setSuppress($_lord_pk, $_posi_pk, 'complete', $r['supp_pk'], $r['target_level'], $r['target_cnt'], $r['suppress_cnt'] + 1, $suppress_dt, $_posi_pk, null, null, $suppress_info['army_type_1'], $suppress_info['army_type_2'], $suppress_info['army_type_3']);
            } else {
                $add_update_query = null;

                /*$this->getLetterClass();

                $letter = Array();
                $letter['type'] = 'S';
                $letter['title'] = '황제폐하로 부터 하달된 황건적 토벌에 성공하였습니다!';
                $letter['content'] = '황제폐하로부터 특별 하사품이 도착했습니다.<br /><br />받은 하사품 : 승자의 전리품';

                $this->getItemClass();
                $this->Item->BuyItem($_lord_pk, 500404, 1, 'troop');

                $this->Letter->sendLetter(ADMINI_LORD_PK, Array($_lord_pk), $letter, true, 'Y');*/

                $this->classLog();
                $this->Log->setSuppress($_lord_pk, $_posi_pk, 'success', $r['supp_pk'], $r['target_level'], $r['target_cnt'], $r['suppress_cnt'] + 1, 'now()', $_posi_pk, null, null, $suppress_info['army_type_1'], $suppress_info['army_type_2'], $suppress_info['army_type_3']);
            }

            $this->PgGame->query('UPDATE suppress SET suppress_cnt = suppress_cnt + 1 ' . $add_update_query . ' WHERE supp_pk = $1', [$r['supp_pk']]);

            // 토벌좌표 갱신
            $this->getNpcSuppressToSQ($_lord_pk);

            // reward
            $ret = $this->setBattleReward('suppress', $r['target_level'], $_lord_pk);
            if ($ret['item_pk']) {
                $z_content['outcome']['reward'] = $ret;
            }

            // 교환권 이벤트 추가보상 지급.
            //$this->Item->BuyItem($_lord_pk, 500220, 1, 'troop');
            // 추석 선물 보따리 지급.
            //$this->Item->BuyItem($_lord_pk, 500514, 1, 'troop');

            $this->classQuest();
            $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'battle', 'type' => 'suppress']);
            // $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'battle', 'type' => 'attack_suppress_npc_event']);
            $this->Quest->countCheckQuest($_lord_pk, 'EVENT_SUPPRESS', ['value' => 1]);

            // 토벌령 승리시 섬멸전 체크
            // $ret['raid'] = $this->setRaidTroop(RAID_SUPPRESS_RATE, $_lord_pk, 1, 'suppress');
        } else {
            $ret = null;
        }

        return $ret;
    }

    // 황건적 이벤트 토벌령!
    function doNpcSuppressEvent($_lord_pk, $_posi_pk): null
    {
        global $_M, $NsGlobal; // TODO 마스터데이터를 안쓰는데?
        $NsGlobal->requireMasterData(['item']);

        // suppress 체크
        $this->PgGame->query('SELECT supp_pk, target_level, target_cnt, suppress_cnt FROM suppress WHERE supp_pk = (SELECT event_supp_pk FROM my_event WHERE lord_pk = $1)', [$_lord_pk]);

        if ($this->PgGame->fetch()) {
            $r = $this->PgGame->row;
            //restrict suppress wave
            $this->PgGame->query('UPDATE suppress_position SET status = $1, suppress_dt = now() WHERE supp_pk = $2 AND posi_pk = $3 AND status = $4', ['Y', $r['supp_pk'], $_posi_pk, 'N']);
            if ($this->PgGame->getAffectedRows() != 1) {
                return null;
            }

            // 로그 저장 데이터
            $this->PgGame->query('SELECT army_type_1, army_type_2, army_type_3 FROM suppress_position WHERE supp_pk = $1 AND posi_pk = $2', [$r['supp_pk'], $_posi_pk]);
            $this->PgGame->fetch();
            $suppress_info = $this->PgGame->row;

            // position 업데이트 (world update)
            $this->PgGame->query('UPDATE position SET last_update_dt = now(), type = $2 WHERE posi_pk = $1', [$_posi_pk, 'D']);
            if ($this->PgGame->getAffectedRows() != 1) {
                return null;
            }

            // suppress 갱신
            if ($r['target_cnt'] == $r['suppress_cnt'] + 1) {
                $add_update_query = ", status = 'Y', suppress_dt = now()";

                //Log
                $query_params = [$r['supp_pk']];
                $this->PgGame->query('DELETE FROM suppress_position WHERE supp_pk = $1', $query_params);
                $this->PgGame->query('DELETE FROM suppress WHERE supp_pk = $1', $query_params);
                $this->classLog();
                $suppress_dt = 'now()';
                $this->Log->setSuppress($_lord_pk, $_posi_pk, 'event_complete', $r['supp_pk'], $r['target_level'], $r['target_cnt'], $r['suppress_cnt'] + 1, $suppress_dt, null, null, null, $suppress_info['army_type_1'], $suppress_info['army_type_2'], $suppress_info['army_type_3']);

                //이벤트 완료 처리
                /*$query_params = Array($this->Session->lord['lord_pk'], 'Y');
                $this->PgGame->query('UPDATE my_event SET event_supp_success = $2 WHERE lord_pk = $1', $query_params);
                if ($this->PgGame->getAffectedRows() != 1)
                {
                    return null;
                }*/
            } else {
                $add_update_query = null;

                $this->classLog();
                $this->Log->setSuppress($_lord_pk, $_posi_pk, 'event_success', $r['supp_pk'], $r['target_level'], $r['target_cnt'], $r['suppress_cnt'] + 1, null, null, null, null, $suppress_info['army_type_1'], $suppress_info['army_type_2'], $suppress_info['army_type_3']);
            }

            /*
             * 외교서신
             */

            $letter = [];
            $letter['type'] = 'S';
            $letter['title'] = '황건적에게 납치된 산타클로스 구출에 성공하였습니다.';
            $letter['content'] = <<<EOF
황건적에게 납치된 산타클로스 구출에 성공하였습니다.

EOF;
            $this->classLetter();
            $this->Letter->sendLetter(ADMIN_LORD_PK, [$_lord_pk], $letter, true, 'Y');

            $this->PgGame->query('UPDATE suppress SET suppress_cnt = suppress_cnt+1 ' . $add_update_query . ' WHERE supp_pk = $1', [$r['supp_pk']]);

            // 토벌좌표 갱신
            $this->getNpcSuppressEventToSQ($_lord_pk);

            // reward
            $ret = $this->setBattleReward('assemble', $r['target_level'], $_lord_pk);
            if ($ret['item_pk']) {
                $z_content['outcome']['reward'] = $ret; // TODO 얜 뭐야...
            }
        } else {
            $ret = null;
        }

        return $ret;
    }

    // 토벌령 Get
    function getNpcSuppress($_lord_pk): false|array
    {
        // 토벌령 조회
        $this->PgGame->query('SELECT last_supp_pk, date_part(\'epoch\', last_suppress_dt)::integer as last_suppress_dt FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $this->PgGame->fetch();
        $last = $this->PgGame->row;

        if (!$last['last_supp_pk']) {
            // cant not found the suppress data
            return false;
        }

        // 마스터데이터
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['npc_troop']);

        // 기초정보 추출
        $this->PgGame->query('SELECT target_level FROM suppress WHERE supp_pk = $1', [$last['last_supp_pk']]);
        $target_level = $this->PgGame->fetchOne();

        if (!$target_level) {
            // cant not found the suppress data
            return false;
        }

        // 부대 기초 데이터 선택
        $m_npc_troo =& $_M['NPC_TROO']['suppress'][$target_level]; // TODO 왜 안쓰지? 이러면 위에 마스터데이터도 필요 없는거 아닌가?

        // 상세정보 추출
        $this->PgGame->query('SELECT posi_pk, hero_pk FROM suppress_position WHERE supp_pk = $1 AND status = $2', [$last['last_supp_pk'], 'N']);

        $suppress_position = [];

        while ($this->PgGame->fetch()) {
            $r =& $this->PgGame->row;

            // 병력수 추출
            /*
            $r['army_type_1_cnt'] = $m_npc_troo[$r['army_type_1']];

            if ($r['army_type_2'])
                $r['army_type_2_cnt'] = $m_npc_troo[$r['army_type_2']];

            if ($r['army_type_3'])
                $r['army_type_3_cnt'] = $m_npc_troo[$r['army_type_3']];
            */

            $suppress_position[$r['posi_pk']] = $r['hero_pk'];
        }

        return (COUNT($suppress_position) < 1) ? false : $suppress_position;
    }

    // 토벌령 Get to SQ
    function getNpcSuppressToSQ($_lord_pk): void
    {
        $this->Session->sqAppend('NPC_SUPP', $this->getNpcSuppress($_lord_pk));
    }

    // 전투보상
    function setBattleReward($_type, $_level, $_lord_pk): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['npc_reward', 'item']);

        $range_arr = $_M['NPC_REWARD'][$_type][$_level];
        $range_prev = 1;
        $range_select = null;
        $range_select_cnt = 1;

        $range_random_key = rand(1, 1000000); // 백만

        foreach ($range_arr as $k => $v) {
            if ($v['recalc_rate'] == 0) {
                continue;
            }
            $next = $range_prev + $v['recalc_rate'];
            if ($range_random_key >= $range_prev && $range_random_key <= $next) {
                $range_select = $k;
                $range_select_cnt = $v['cnt'];
                break;
            }
            $range_prev = $next;
        }

        // NPC 이벤트 보상 증가 이벤트
        if (CONF_EVENT_NPC_REWARD_ENABLE) {
            $range_select_cnt = $range_select_cnt * CONF_EVENT_NPC_REWARD_VALUE;
        }

        $ret_arr = ['item_pk' => null, 'item_desc' => null];

        if ($range_select && $range_select != 'none') {
            $ret_arr['item_pk'] = $range_select;
            $ret_arr['item_cnt'] = $range_select_cnt;
            $ret_arr['item_desc'] = $_M['ITEM'][$range_select]['title'] . 'x' . $range_select_cnt;
            if (CONF_EVENT_NPC_REWARD_ENABLE) {
                $ret_arr['double_event'] = CONF_EVENT_NPC_REWARD_ENABLE;
            }
            $this->classItem();
            $ret = $this->Item->BuyItem($_lord_pk, $range_select, $range_select_cnt, 'troop');
            if (!$ret) {
                Debug::debugMessage('ERROR', 'not reward item' . ';lord_pk['.$_lord_pk.'];item_count:['.$range_select_cnt.'];m_item_pk['.$range_select.'];');
            }
        } else {
            Debug::debugMessage('ERROR', 'not range_select' . ';lord_pk['.$_lord_pk.'];item_count:['.$range_select_cnt.'];m_item_pk['.$range_select.'];');
        }

        return $ret_arr;
    }

    // 수치 문자로 변환
    function getNumberToTextDesc($_Arr): array
    {
        global $i18n;
        $new_arr = [];
        foreach ($_Arr as $k => $v) {
            $new_arr[$k] = match (true) {
                (!$v < 10) => $i18n->t('less_10'), // '몇',
                (!$v < 100) => $i18n->t('less_100'), // '수 십',
                (!$v < 1000) => $i18n->t('less_1000'), // '수 백',
                (!$v < 10000) => $i18n->t('less_10000'), // '수 천',
                (!$v < 100000) => $i18n->t('less_100000'), // '수 만',
                (!$v < 1000000) => $i18n->t('less_1000000'), // '수 십만',
                (!$v < 10000000) => $i18n->t('less_10000000'), // '수 백만',
                (!$v < 100000000) => $i18n->t('less_100000000'), // '수 천만',
                (!$v < 1000000000) => $i18n->t('less_1000000000'), // '수 억',
                default => ''
            };
        }
        return $new_arr;
    }

    // 지원한 영지가 점령당했을 때 주둔군 복귀
    function RecallReinfArmy($_posi_pk, $_main_posi_pk): true
    {
        $this->PgGame->query('SELECT troo_pk FROM troop WHERE dst_posi_pk = $1 AND status = $2 AND cmd_type = $3', [$_posi_pk, 'C', 'R']);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        foreach ($rows as $v) {
            // 동맹국 주둔부대의 데이터 업데이트는 없어도 됨.
            //$query_params = Array($_posi_pk, 'C', 'R', TROOP_OCCUPATION_TRIPTIME, $_main_posi_pk);
            //$this->PgGame->query('UPDATE troop SET dst_posi_pk = $5, triptime = $4 WHERE dst_posi_pk = $1 AND status = $2 AND cmd_type = $3', $query_params);
            $this->setStatusRecall($v['troo_pk']);
        }
        return true;
    }

    // 영지 점령당했을 경우 전체 자원지 상실
    function lossOwnershipAllValley($_lord_pk, $_posi_pk): true
    {
        $this->PgGame->query('SELECT valley_posi_pk FROM territory_valley WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        // 자원지 상실
        foreach ($rows as $v) {
            $this->lossOwnershipValley($_lord_pk, $v['valley_posi_pk']);
        }
        return true;
    }

    // 모든 부대 삭제
    function removeAllTroop($_lord_pk): void
    {
        $this->PgGame->query('SELECT troo_pk, src_time_pk FROM troop WHERE src_lord_pk = $1', [$_lord_pk]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;

        // 타이머 취소
        foreach ($rows as $v) {
            $this->cancelTimer($v['src_time_pk'], $_lord_pk);
            $this->removeMoveTroop($v['troo_pk']);
        }

        // 부대 삭제
        $this->PgGame->query('DELETE FROM troop WHERE src_lord_pk = $1', [$_lord_pk]);
    }

    // 이동중인 부대(출정, 회군, 취소) 모든 부대 취소
    function setAllTroopStatusWithdrawal($_posi_pk, $_main_posi_pk): true
    {
        // 현재 진행중인 부대를 찾아 타이머 취소
        $this->PgGame->query('SELECT troo_pk, src_time_pk FROM troop WHERE src_posi_pk = $1 AND src_time_pk is not null', [$_posi_pk]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;

        //$query_params = Array($_main_posi_pk, $_posi_pk, TROOP_OCCUPATION_TRIPTIME);
        //$this->PgGame->query('UPDATE troop SET src_posi_pk = $1, triptime = $3 WHERE src_posi_pk = $2 AND src_time_pk is not null', $query_params);
        $this->PgGame->query('UPDATE troop SET src_posi_pk = $1, triptime = $3, from_position = $4 WHERE src_posi_pk = $2 AND src_time_pk is not null', [$_main_posi_pk, $_posi_pk, TROOP_OCCUPATION_TRIPTIME, $this->getPositionName($_main_posi_pk)]);

        foreach ($rows as $v) {
            $this->setStatusWithdrawal($v['troo_pk'], null, true);
        }

        return true;
    }

    // 주둔중인 모든 부대 회군처리
    function setAllTroopStatusRecall($_posi_pk, $_main_posi_pk): true
    {
        $this->PgGame->query('SELECT troo_pk, src_time_pk, dst_time_pk FROM troop WHERE src_posi_pk = $1 AND status = $2', [$_posi_pk, 'C']);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        $this->PgGame->query('UPDATE troop SET src_posi_pk = $1, triptime = $2 WHERE src_posi_pk = $3 AND status = $4', [$_main_posi_pk, TROOP_OCCUPATION_TRIPTIME, $_posi_pk, 'C']);
        foreach ($rows as $v) {
            // 회군
            $this->setStatusRecall($v['troo_pk'], null, null, true);
        }
        return true;
    }

    // 영웅 없는 외부자원지 공격
    function setAttackVally($_troo_pk, $row = null): true
    {
        if (!$row) {
            $row = $this->getTroop($_troo_pk);
        }

        $z_content = [];
        $z_content['battle_info'] = [];
        $z_content['battle_info']['def_type'] = 'valley';
        $z_content['outcome'] = [];
        $z_content['outcome']['winner'] = 'att';
        $z_content['outcome_unit'] = [];
        $z_content['outcome_unit']['att'] = false;
        $z_content['outcome_unit']['def'] = false;

        // 방어측
        $this->lossOwnershipValley($row['dst_lord_pk'], $row['dst_posi_pk']);

        // 공격측
        $ret = $this->acquiredOwnershipValley($row['src_lord_pk'], $row['src_posi_pk'], $row['dst_posi_pk'], $row['dst_lord_pk']);
        if ($ret['ret']) { // 주둔
            $z_content['outcome']['acquiredpwnership'] = true;
            $this->setStatusCampValley($_troo_pk, $row);
        } else {
            $z_content['outcome']['acquiredpwnership'] = false;
            $z_content['outcome']['valley_cnt_not'] = true;
            $this->setStatusRecall($_troo_pk, $row);
        }

        $this->PgGame->query('SELECT lord_name, level FROM lord WHERE lord_pk = $1', [$row['src_lord_pk']]);
        $this->PgGame->fetch();
        $src_lord_name_withLevel = $this->PgGame->row['lord_name'] . ' Lv.' . $this->PgGame->row['level'];

        $this->PgGame->query('SELECT lord_name, level FROM lord WHERE lord_pk = $1', [$row['dst_lord_pk']]);
        $this->PgGame->fetch();
        $dst_lord_name_withLevel = $this->PgGame->row['lord_name'] . ' Lv.' . $this->PgGame->row['level'];

        // 보고서 - 공격측
        $z_content['from_position'] = $row['from_position'];
        $z_content['to_position'] = $row['to_position'];

        // from & to
        $z_from = ['posi_pk' => $row['src_posi_pk'], 'posi_name' => $row['from_position'], 'lord_name' => $src_lord_name_withLevel];
        $z_to = ['posi_pk' => $row['dst_posi_pk'], 'posi_name' => $row['to_position'], 'lord_name' => $dst_lord_name_withLevel];

        $this->classReport();
        $this->classLog();

        $repo_pk = $this->Report->setReport($row['src_lord_pk'], 'battle', 'battle_attack_victory', $z_from, $z_to, '', '', json_encode($z_content), null, 'battlewin');

        // Log
        $this->Log->setBattle($row['src_lord_pk'], $row['src_posi_pk'], 'battle_attack', $row['dst_lord_pk'], null, $row['dst_posi_pk'], $row['to_position'], 'battle_attack_victory', '', json_encode($z_content), $row['troop_type'], '승리', $z_content['outcome']['acquiredpwnership'], null, $_troo_pk);

        // 보고서 - 방어측
        $z_content['from_position'] = $row['to_position'];
        $z_content['to_position'] = $row['from_position'];

        // from & to
        $z_from = ['posi_pk' => $row['src_posi_pk'], 'posi_name' => $row['from_position'], 'lord_name' => $src_lord_name_withLevel];
        $z_to = ['posi_pk' => $row['dst_posi_pk'], 'posi_name' => $row['to_position'], 'lord_name' => $dst_lord_name_withLevel];

        $repo_pk = $this->Report->setReport($row['dst_lord_pk'], 'battle', 'battle_defence_defeat', $z_to, $z_from, '', '', json_encode($z_content), null, 'battlelose');

        // Log
        $this->classLog();
        $this->Log->setBattle($row['dst_lord_pk'], $row['dst_posi_pk'], 'battle_defence', $row['src_lord_pk'], null, $row['src_posi_pk'], $row['from_position'], 'battle_defence_defeat', '', json_encode($z_content), $row['troop_type'], '패배', $z_content['outcome']['acquiredpwnership'], null, $_troo_pk);

        return true;
    }

    // 수송
    function setTransport($_troo_pk, $row, $dst_posi, $lord_name = null): void
    {
        // 출정 : 영웅(소모용) + 병력(capacity) + 자원 + 황금
        // 저장 : 자원 + 황금 (대상영지)
        // 복귀 : 영웅 + 병력
        //  - territory 의 resource 와 goldpop 서버에 increase
        if ($dst_posi['type'] == 'T') { // territory
            if ($row['reso_gold']) {
                $this->classGoldPop();
                $r = $this->GoldPop->increaseGold($row['dst_posi_pk'], $row['reso_gold'], $dst_posi['lord_pk'], 'troop_T');
                if (!$r) {
                    Debug::debugMessage('ERROR', '수송 황금 지급 오류');
                }
            }
            if ($row['reso_food'] || $row['reso_horse'] || $row['reso_lumber'] || $row['reso_iron']) {
                $reso = [];
                $reso['food'] = $row['reso_food'];
                $reso['horse'] = $row['reso_horse'];
                $reso['lumber'] = $row['reso_lumber'];
                $reso['iron'] = $row['reso_iron'];

                $this->classResource();
                $r = $this->Resource->increase($row['dst_posi_pk'], $reso, $dst_posi['lord_pk'], 'troop_T');
                if (!$r) {
                    Debug::debugMessage('ERROR', '수송 황금 지급 오류');
                }
            }

            // 보고서
            $z_content = [];

            // hero
            $z_content['hero'][] = ['pk' => $row['captain_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($row['captain_hero_pk'])];
            if ($row['director_desc']) {
                $z_content['hero'][] = ['pk' => $row['director_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($row['director_hero_pk'])];
            }
            if ($row['staff_desc']) {
                $z_content['hero'][] = ['pk' => $row['staff_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($row['staff_hero_pk'])];
            }
            // army
            foreach ($row as $k => $v) {
                if (str_starts_with($k, 'army_')) {
                    $z_content['army'][substr($k, 5)] = $v;
                }
            }

            // reso
            $z_content['reso']['gold'] = $row['reso_gold'];
            $z_content['reso']['food'] = $row['reso_food'];
            $z_content['reso']['horse'] = $row['reso_horse'];
            $z_content['reso']['lumber'] = $row['reso_lumber'];
            $z_content['reso']['iron'] = $row['reso_iron'];

            // recall time
            $z_content['recall']['script'] = '수송부대는 <span class="cont_recall_move_time"></span> 뒤인 <span class="cont_recall_end_dt"></span> 에 출발지로 복귀 예정입니다.';
            $z_content['recall']['move_time'] = $row['triptime'];
            $z_content['recall']['end_dt'] = time() + $row['triptime'];

            // from & to
            $z_from = ['posi_pk' => $row['src_posi_pk'], 'posi_name' => $row['from_position']];
            $z_to = ['posi_pk' => $row['dst_posi_pk'], 'posi_name' => $row['to_position']];

            // title & summary
            $this->classReport();
            $repo_pk = $this->Report->setReport($row['src_lord_pk'], 'move', 'trans_finish', $z_from, $z_to, '', '', json_encode($z_content));
            /* TODO 개인 동맹 제거로 주석 처리
             * if ($dst_posi['relation'] != 'MIME')
            {
                $query_params = Array($row['dst_alli_pk'], $row['src_lord_pk'], 'Y');
                // $this->PgGame->query('SELECT memb_lord_pk FROM alliance_member WHERE lord_pk = $1 AND memb_lord_pk = $2 AND type = $3', $query_params);
                $this->PgGame->query('SELECT alli_pk FROM alliance_member WHERE alli_pk = $1 AND lord_pk = $2 AND type = $3', $query_params);
                if ($this->PgGame->fetchOne())
                {
                    $z2_title = '동맹군 수송부대 도착 보고';
                    $z2_summary = $lord_name . ' 군주님의 동맹군 수송부대가 도착하여 자원을 전달 하였습니다.';
                } else {
                    $z2_title = '타 군주 수송부대 도착 보고';
                    $z2_summary = $lord_name . ' 군주님의 수송부대가 도착하여 자원을 전달 하였습니다.';
                }

                $z_content['hero'] = null;
                $z_content['army'] = null;
                $z_content['recall'] = null;

                $repo_pk = $this->Report->setReport($row['dst_lord_pk'], 'move', 'trans_finish', $z_from, $z_to, $z2_title, $z2_summary, json_encode($z_content));
            }*/
            $this->PgGame->query('UPDATE troop SET reso_gold = 0, reso_food = 0, reso_horse = 0, reso_lumber = 0, reso_iron = 0 WHERE troo_pk = $1', [$_troo_pk]);

            // 퀘스트
            $this->classQuest();
            $this->Quest->conditionCheckQuest($row['src_lord_pk'], ['quest_type' => 'battle', 'type' => 'transportation']);

            // 동맹 전투
            /* TODO 개인 동맹 제거로 주석 처리
             * if ($row['src_lord_pk'] != $row['dst_lord_pk']) {
                $this->classAlliance();
                $this->Alliance->setAllianceWarHistory($row['src_lord_pk'], $repo_pk, $lord_name, 'T', $dst_posi['lord_name'], $row['dst_posi_pk']);
            }*/
        }

        $this->setStatusRecall($_troo_pk);
    }

    // 보급
    function setPrevalence($_troo_pk, $row, $lord_name = null, $dst_lord_name = null): void
    {
        // 출정 : 영웅(소모용) + 병력(capacity) + 식량
        // 저장 : 식량 (reso_food)
        // 복귀 : 영웅 + 병력
        // - presence_food - withdrawal_dt 업데이트.
        //    내 자원지 주둔군은 항상 1부대 이지만 동맹 영지는 다중 부대 주둔 가능.
        //   다중 부대가 주둔하고 있으면 각 부대의 hour_food 로 분배 %를 나누어서 자원을 배분한다.
        // - troop 의 경우만 가능
        $troops = [];
        $total_hour_food = 0;

        // 보고서
        $z_content = [];
        $z_content['camp_troop'] = [];

        $this->PgGame->query('SELECT troo_pk, captain_hero_pk, captain_desc, director_hero_pk, director_desc, staff_hero_pk, staff_desc, army_worker, army_infantry, army_pikeman, army_scout, army_spearman, army_armed_infantry, army_archer, army_horseman, army_armed_horseman, army_transporter, army_bowman, army_battering_ram, army_catapult, army_adv_catapult, hour_food, date_part(\'epoch\', withdrawal_dt)::integer as withdrawal_dt, date_part(\'epoch\', now())::integer as now FROM troop WHERE src_lord_pk = $1 AND dst_posi_pk = $2 AND status = $3 ORDER BY troo_pk DESC', [$row['src_lord_pk'], $row['dst_posi_pk'], 'C']);
        while ($this->PgGame->fetch()) {
            $r =& $this->PgGame->row;
            $k = $r['troo_pk'];
            $troops[$k] = $r;
            $total_hour_food += $r['hour_food'];
        }

        // 각 부대의 hour_food 로 분배 %를 나누어서 자원을 배분
        foreach ($troops as $k => $v) {
            $food = $row['reso_food'] * ($v['hour_food'] / $total_hour_food);
            $increase_second = $food / $v['hour_food'] * TROOP_WITHDRAWAL_TIME; // hour_food 가 비상식량 일때는 7일치임.
            $increase_second = intval($increase_second);

            // 증가된 값이 현재보다 1일 이후 일대, withdrawal_notify 리셋
            $z = '';
            if ($v['withdrawal_dt'] + $increase_second > $v['now'] + 86400) {
                $z = 'withdrawal_notify = \'N\', ';
            }

            $z_content['camp_troop'][$v['troo_pk']]['hour_food'] = $v['hour_food'];

            // hero
            $z_content['camp_troop'][$v['troo_pk']]['hero'][] = ['pk' => $v['captain_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($v['captain_hero_pk'])];

            if ($v['director_desc']) {
                $z_content['camp_troop'][$v['troo_pk']]['hero'][] = ['pk' => $v['director_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($v['director_hero_pk'])];
            }
            if ($v['staff_desc']) {
                $z_content['camp_troop'][$v['troo_pk']]['hero'][] = ['pk' => $v['staff_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($v['staff_hero_pk'])];
            }

            // army
            foreach ($v as $k2 => $v2) {
                if (str_starts_with($k2, 'army_')) {
                    $z_content['camp_troop'][$v['troo_pk']]['army'][substr($k2, 5)] = $v2;
                }
            }

            // increase withdrawal_dt
            $z_content['camp_troop'][$v['troo_pk']]['withdrawal']['increase_second'] = $increase_second;
            $z_content['camp_troop'][$v['troo_pk']]['withdrawal']['withdrawal_dt'] = $v['withdrawal_dt'] + $increase_second;

            $this->PgGame->query('UPDATE troop SET ' . $z . ' withdrawal_dt = withdrawal_dt + interval \'' . $increase_second . ' seconds\' WHERE troo_pk = $1', [$k]);
        }

        // hero
        $z_content['hero'][] = ['pk' => $row['captain_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($row['captain_hero_pk'])];
        if ($row['director_desc']) {
            $z_content['hero'][] = ['pk' => $row['director_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($row['director_hero_pk'])];
        }
        if ($row['staff_desc']) {
            $z_content['hero'][] = ['pk' => $row['staff_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($row['staff_hero_pk'])];
        }

        // army
        foreach ($row as $k => $v) {
            if (str_starts_with($k, 'army_')) {
                $z_content['army'][substr($k, 5)] = $v;
            }
        }

        // reso
        $z_content['reso']['gold'] = $row['reso_gold'];
        $z_content['reso']['food'] = $row['reso_food'];
        $z_content['reso']['horse'] = $row['reso_horse'];
        $z_content['reso']['lumber'] = $row['reso_lumber'];
        $z_content['reso']['iron'] = $row['reso_iron'];

        // recall time
        $z_content['recall']['move_time'] = $row['triptime'];
        $z_content['recall']['end_dt'] = time() + $row['triptime'];

        // from & to
        $z_from = ['posi_pk' => $row['src_posi_pk'], 'posi_name' => $row['from_position']];
        $z_to = ['posi_pk' => $row['dst_posi_pk'], 'posi_name' => $row['to_position']];

        // title & summary
        $this->classReport();
        $repo_pk = $this->Report->setReport($row['src_lord_pk'], 'move', 'preva_finish', $z_from, $z_to, '', '', json_encode($z_content));

        $this->PgGame->query('UPDATE troop SET reso_food = 0 WHERE troo_pk = $1', [$_troo_pk]);

        // 퀘스트
        $this->classQuest();
        $this->Quest->conditionCheckQuest($row['src_lord_pk'], ['quest_type' => 'battle', 'type' => 'prevalence']);

        // 동맹 전투
        /*if ($row['src_lord_pk'] != $row['dst_lord_pk']) {
            $this->classAlliance();
            $this->Alliance->setAllianceWarHistory($row['src_lord_pk'], $repo_pk, $lord_name, 'P', $dst_lord_name, $row['dst_posi_pk']);
        }*/

        $this->setStatusRecall($_troo_pk, $row);
    }

    // 보급량 체크
    function checkPrevalence($_troo_pk, $_reso_food): bool
    {
        // 무리한 보급이 이루어지는지 체크하기 위해 추가.
        $total_hour_food = 0;

        $this->PgGame->query('SELECT troo_pk, hour_food, reso_food, date_part(\'epoch\', withdrawal_dt)::integer as withdrawal_dt FROM troop WHERE dst_posi_pk = $1 AND status = $2 ORDER BY troo_pk DESC', [$_troo_pk, 'C']);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        foreach ($rows as $row) {
            $total_hour_food += $row['hour_food'];
        }
        foreach ($rows as $row) {
            $food = $_reso_food * ($row['hour_food'] / $total_hour_food);
            $increase_second = $food / $row['hour_food'] * TROOP_WITHDRAWAL_TIME; // hour_food 가 비상식량 일때는 7일치임.
            $increase_second = intval($increase_second);
            $check_date = date('Y-m-d H:i:s', $row['withdrawal_dt'] + $increase_second);
            if ($increase_second < 0 || $check_date < date('Y-m-d H:i:s') || $check_date > date('Y-m-d H:i:s', strtotime("+1 years", time()))) {
                return false;
            }
        }

        return true;
    }

    // 지원
    function setReinforce($_troo_pk, $row, $dst_posi, $lord_name = null): void
    {
        // 출정 : 영웅 + 병력 + 자원 + 황금
        // 저장 (액션 후 조건에 따라 부대는 삭제)
        //  - 내 영지 : 전체 - 영웅의 소속 영지 변경
        //  - 내 동맹 : 전체 - "자원 + 황금"은 영지로, "영웅 + 병력"은 주둔
        //  - 내 자원지 : "병력 + 자원 + 황금"은 기존 주둔부대에 attach, "영웅"도 남은 슬롯만큼 attach 후 초과시 돌아옴.
        //                (기존 주둔부대의 영지로 영웅의 소속 영지 변경)
        // 복귀 : 내 자원지의 경우만 영웅이 남을 경우
        //  - 내 영지의(territory) resource 와 goldpop 서버에 increase, CArmy::returnArmy, 영웅 명령해제 -> 소속 영지 변경
        //  - 내 동맹의(territory) resource 와 goldpop 서버에 increase, 부대는 setStatusCamp
        //  - 내 자원지의(troop) reso_*, army_*, *_hero_*, 영웅 소속 영지 변경
        $this->classArmy();
        $this->classHero();
        $this->classQuest();
        $this->classReport();
        $this->classGoldPop();
        $this->classResource();

        // 보고서
        $z_content = [];

        // hero
        $z_content['hero'][] = ['pk' => $row['captain_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($row['captain_hero_pk']), 'type' => 'captain'];
        if ($row['director_hero_pk']) {
            $z_content['hero'][] = ['pk' => $row['director_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($row['director_hero_pk']), 'type' => 'director'];
        }
        if ($row['staff_hero_pk']) {
            $z_content['hero'][] = ['pk' => $row['staff_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($row['staff_hero_pk']), 'type' => 'staff'];
        }

        // army
        foreach ($row as $k => $v) {
            if (str_starts_with($k, 'army_')) {
                $z_content['army'][substr($k, 5)] = $v;
            }
        }

        // reso
        $z_content['reso']['gold'] = $row['reso_gold'];
        $z_content['reso']['food'] = $row['reso_food'];
        $z_content['reso']['horse'] = $row['reso_horse'];
        $z_content['reso']['lumber'] = $row['reso_lumber'];
        $z_content['reso']['iron'] = $row['reso_iron'];

        // from & to
        $z_from = ['posi_pk' => $row['src_posi_pk'], 'posi_name' => $row['from_position']];
        $z_to = ['posi_pk' => $row['dst_posi_pk'], 'posi_name' => $row['to_position']];

        $report_type = '';
        $z_summary_array = [];
        // 영지
        if ($dst_posi['type'] == 'T') {
            // "자원+황금"
            if ($row['reso_gold']) {
                $r = $this->GoldPop->increaseGold($row['dst_posi_pk'], $row['reso_gold'], $dst_posi['lord_pk'], 'troop_R');
                if (!$r) {
                    Debug::debugMessage('ERROR', '수송 황금 지급 오류');
                }
            }

            if ($row['reso_food'] || $row['reso_horse'] || $row['reso_lumber'] || $row['reso_iron']) {
                $reso = [];
                $reso['food'] = $row['reso_food'];
                $reso['horse'] = $row['reso_horse'];
                $reso['lumber'] = $row['reso_lumber'];
                $reso['iron'] = $row['reso_iron'];
                $r = $this->Resource->increase($row['dst_posi_pk'], $reso, $dst_posi['lord_pk'], 'troop_R');
                if (!$r) {
                    Debug::debugMessage('ERROR', '수송 황금 지급 오류');
                }
            }

            // 내영지
            if ($dst_posi['relation'] == 'MIME') {
                // "병력"
                $army_arr = [];
                foreach ($row as $k => $v) {
                    if (str_starts_with($k, 'army_')) {
                        $army_arr[substr($k, 5)] = $v;
                    }
                }
                $this->Army->returnArmy($row['dst_posi_pk'], $army_arr);
                $this->Army->get($row['dst_posi_pk'], null, $dst_posi['lord_pk']);

                // "영웅"
                if ($row['captain_hero_pk']) {
                    $ret = $this->Hero->unsetCommand($row['captain_hero_pk']);
                    if (!$ret) {
                        Debug::debugMessage('ERROR', '내영지 지원 영웅(주장) Uncomamnd 오류');
                    } else {
                        $this->Hero->setTerritory($row['captain_hero_pk'], $row['dst_posi_pk']);
                    }
                }

                if ($row['director_hero_pk']) {
                    $ret = $this->Hero->unsetCommand($row['director_hero_pk']);
                    if (!$ret) {
                        Debug::debugMessage('ERROR', '내영지 지원 영웅(부장) Uncomamnd 오류');
                    } else {
                        $this->Hero->setTerritory($row['director_hero_pk'], $row['dst_posi_pk']);
                    }
                }

                if ($row['staff_hero_pk']) {
                    $ret = $this->Hero->unsetCommand($row['staff_hero_pk']);
                    if (!$ret) {
                        Debug::debugMessage('ERROR', '내영지 지원 영웅(참모) Uncomamnd 오류');
                    } else {
                        $this->Hero->setTerritory($row['staff_hero_pk'], $row['dst_posi_pk']);
                    }
                }

                $report_type = 'reinforce_finish_1';

                // 퀘스트
                $this->Quest->conditionCheckQuest($row['src_lord_pk'], ['quest_type' => 'battle', 'type' => 'reinforce']);

                // 부대삭제
                $this->removeTroop($_troo_pk);
            } else {
                // 동맹영지

                // 지원을 통해 수송된 자원 리셋
                $this->PgGame->query('UPDATE troop SET reso_gold = 0, reso_food = 0, reso_horse = 0, reso_lumber = 0, reso_iron = 0 WHERE troo_pk = $1', [$_troo_pk]);

                // 주둔
                if ($this->setStatusCamp($_troo_pk, $row)) {
                    // 주둔성공
                    $report_type = 'reinforce_finish_2';

                    // ally_troop_arrival 처리
                    $z_content['recall']['script'] = '주둔부대는 추가 보급이 없다면 일주일 후인 <span class="cont_recall_withdrawal_dt"></span> 에 복귀 예정 입니다.';
                    $z_content['recall']['withdrawal_dt'] = time() + TROOP_WITHDRAWAL_TIME;

                    // title & summary
                    /* TODO 개인동맹 제거로 주석처리
                    $query_params = Array($row['dst_lord_pk'], $row['src_lord_pk'], 'Y');
                    $this->PgGame->query('SELECT memb_lord_pk FROM alliance_member WHERE lord_pk = $1 AND memb_lord_pk = $2 AND type = $3', $query_params);
                    if ($this->PgGame->fetchOne())
                    {
                        $z2_title = '동맹 지원군의 도착 보고';
                        $z2_summary = $lord_name . ' 군주님의 동맹 지원군이 도착하여 주둔을 시작했습니다.';
                    } else {
                        $z2_title = '타 군주 지원군의 도착 보고';
                        $z2_summary = $lord_name . ' 군주님의 지원군이 도착하여 주둔을 시작했습니다.';
                    }*/

                    $repo_pk = $this->Report->setReport($row['dst_lord_pk'], 'move', 'ally_troop_arrival', $z_from, $z_to, '', '', json_encode($z_content));

                    // 퀘스트
                    $this->Quest->conditionCheckQuest($row['src_lord_pk'], ['quest_type' => 'alliance', 'type' => 'reinforce']);

                    // 동맹 전투
                    /* TODO 개인동맹 제거로 주석처리
                     * if ($row['src_lord_pk'] != $row['dst_lord_pk'])
                    {
                        $this->getAllianceClass();
                        $this->Alliance->setAllianceWarHistory($row['src_lord_pk'], $repo_pk, $lord_name, 'R', $dst_posi['lord_name'], $row['dst_posi_pk']);
                    }*/
                } else {
                    // 주둔실패
                    $report_type = 'reinforce_finish_3';

                    $z_content['recall']['script'] = '주둔 불가로 <span class="cont_recall_move_time"></span> 뒤인 <span class="cont_recall_end_dt"></span> 에 출발지로 복귀 합니다.';
                    $z_content['recall']['move_time'] = $row['triptime'];
                    $z_content['recall']['end_dt'] = time() + $row['triptime'];

                    // 회군
                    $this->setStatusRecall($_troo_pk, $row);
                }
            }
        } else {
            // TODO : 내자원지

            // 기존 주둔부대
            $this->PgGame->query('SELECT troo_pk, src_posi_pk, captain_hero_pk, captain_desc, director_hero_pk, director_desc, staff_hero_pk, staff_desc, hour_food, date_part(\'epoch\', withdrawal_dt)::integer as withdrawal_dt, army_worker, army_infantry, army_pikeman, army_scout, army_spearman, army_armed_infantry, army_archer, army_horseman, army_armed_horseman, army_transporter, army_bowman, army_battering_ram, army_catapult, army_adv_catapult FROM troop WHERE dst_posi_pk = $1 AND status = $2 ORDER BY troo_pk DESC LIMIT 1', [$row['dst_posi_pk'], 'C']);
            if (!$this->PgGame->fetch()) {
                // 주둔
                if ($this->setStatusCampValley($_troo_pk, $row)) {
                    // 주둔성공
                    $report_type = 'reinforce_finish_2';

                    // ally_troop_arrival 처리
                    $z_content['recall']['script'] = '주둔부대는 추가 보급이 없다면 일주일 후인 <span class="cont_recall_withdrawal_dt"></span> 에 복귀 예정 입니다.';
                    $z_content['recall']['withdrawal_dt'] = time() + TROOP_WITHDRAWAL_TIME;
                } else {
                    // 주둔실패
                    $report_type = 'reinforce_finish_3';

                    $z_content['recall']['script'] = '주둔 불가로 <span class="cont_recall_move_time"></span> 뒤인 <span class="cont_recall_end_dt"></span> 에 출발지로 복귀 합니다.';
                    $z_content['recall']['move_time'] = $row['triptime'];
                    $z_content['recall']['end_dt'] = time() + $row['triptime'];
                }
            } else {
                $exist_troop = $this->PgGame->row;

                // 주둔부대에 주장이 빈 경우는 없어, 지원부대의 주장은 반드시 회군함.
                $new_camp_heroes = [];
                $new_recall_heroes = ['주장'];

                // 부장 대체
                if (!$exist_troop['director_hero_pk'] && $row['director_hero_pk']) {
                    $exist_troop['director_hero_pk'] = $row['director_hero_pk'];
                    $exist_troop['director_desc'] = $row['director_desc'];

                    $row['director_hero_pk'] = null;
                    $row['director_desc'] = null;

                    // 소속영지 변경
                    $this->Hero->setTerritory($exist_troop['director_hero_pk'], $exist_troop['src_posi_pk']);

                    // 영웅상태 변경
                    $this->Hero->setCommandCmdType($exist_troop['director_hero_pk'], 'Camp');

                    $new_camp_heroes[] = '부장';
                } else {
                    if ($row['director_hero_pk']) {
                        $new_recall_heroes[] = '부장';
                    }
                }

                // 참모 대체
                if (!$exist_troop['staff_hero_pk'] && $row['staff_hero_pk']) {
                    $exist_troop['staff_hero_pk'] = $row['staff_hero_pk'];
                    $exist_troop['staff_desc'] = $row['staff_desc'];

                    $row['staff_hero_pk'] = null;
                    $row['staff_desc'] = null;

                    // 소속영지 변경
                    $this->Hero->setTerritory($exist_troop['staff_hero_pk'], $exist_troop['src_posi_pk']);

                    // 영웅상태 변경
                    $this->Hero->setCommandCmdType($exist_troop['staff_hero_pk'], 'Camp');

                    $new_camp_heroes[] = '참모';
                } else {
                    if ($row['staff_hero_pk']) {
                        $new_recall_heroes[] = '참모';
                    }
                }

                $camp_result = true;

                // 요충지 최대 병력 제한
                global $_M, $NsGlobal;
                $NsGlobal->requireMasterData(['army']);

                // 주둔 부대
                $army_arr = [];
                foreach ($_M['ARMY_C'] as $k => $v) {
                    $army_arr[$k] = $exist_troop['army_' . $k];
                }

                $army_pop = $this->getArmyPop($army_arr);
                $camp_army = $army_pop['population'];

                // 지원 부대
                $this->PgGame->query('SELECT army_worker, army_infantry, army_pikeman, army_scout, army_spearman, army_armed_infantry, army_archer, army_horseman, army_armed_horseman, army_transporter, army_bowman, army_battering_ram, army_catapult, army_adv_catapult FROM troop WHERE troo_pk = $1', [$_troo_pk]);
                $this->PgGame->fetch();
                $troo_info = $this->PgGame->row;
                $army_arr = [];
                foreach ($_M['ARMY_C'] as $k => $v) {
                    $army_arr[$k] = $troo_info['army_' . $k];
                }
                $army_pop = $this->getArmyPop($army_arr);
                if ($camp_army + $army_pop['population'] > TROOP_ARMY_LIMIT) {
                    // 영웅은 지원가능 - 지원부대 남은 영웅 회군
                    $this->PgGame->query('UPDATE troop SET director_hero_pk = $2, director_desc = $3, staff_hero_pk = $4, staff_desc = $5 WHERE troo_pk = $1', [$exist_troop['troo_pk'], $exist_troop['director_hero_pk'], $exist_troop['director_desc'], $exist_troop['staff_hero_pk'], $exist_troop['staff_desc']]);
                    $this->PgGame->query('UPDATE troop SET director_hero_pk = $1, director_desc = $2, staff_hero_pk = $3, staff_desc = $4 WHERE troo_pk = $5', [$row['director_hero_pk'], $row['director_desc'], $row['staff_hero_pk'], $row['staff_desc'], $_troo_pk]);

                    // 병력 추가 주둔 불가
                    $report_type = 'reinforce_finish_4';

                    $camp_result = false;
                }

                // 주둔시간 재계산
                if ($camp_result) {
                    // 소모식량 기준치
                    $new_hour_food = $exist_troop['hour_food'] + $row['hour_food'];

                    // 기존 부대의 남은 주둔식량
                    $curr_presence_food = ($exist_troop['withdrawal_dt'] - time()) / TROOP_WITHDRAWAL_TIME * $exist_troop['hour_food'];
                    $curr_presence_food = intval($curr_presence_food);

                    // 주둔식량 기준치
                    $new_presence_food = $curr_presence_food + $row['presence_food'];

                    // 재계산된 주둔가능 시간
                    $increase_second = $new_presence_food / $new_hour_food * TROOP_WITHDRAWAL_TIME;

                    // 주둔부대에 "영웅 + 병력 + 자원 + 황금" attach
                    $query_params = [$row['reso_gold'], $row['reso_food'], $row['reso_horse'], $row['reso_lumber'], $row['reso_iron'], $exist_troop['troo_pk'],
                        $row['army_worker'], $row['army_infantry'], $row['army_pikeman'], $row['army_scout'], $row['army_spearman'],
                        $row['army_armed_infantry'], $row['army_archer'], $row['army_horseman'], $row['army_armed_horseman'], $row['army_transporter'], $row['army_bowman'],
                        $row['army_battering_ram'], $row['army_catapult'], $row['army_adv_catapult'],
                        $exist_troop['director_hero_pk'], $exist_troop['director_desc'], $exist_troop['staff_hero_pk'], $exist_troop['staff_desc'],
                        $new_hour_food];

                    if ($dst_posi['type'] != 'P') {
                        $sql = <<< EOF
UPDATE troop SET
 reso_gold = reso_gold+$1, reso_food = reso_food+$2, reso_horse = reso_horse+$3, reso_lumber = reso_lumber+$4, reso_iron = reso_iron+$5, 
 army_worker = army_worker+$7,army_infantry = army_infantry+$8,army_pikeman = army_pikeman+$9,army_scout = army_scout+$10, army_spearman = army_spearman+$11,
 army_armed_infantry = army_armed_infantry+$12, army_archer = army_archer+$13, army_horseman = army_horseman+$14, army_armed_horseman = army_armed_horseman+$15, army_transporter = army_transporter+$16, army_bowman = army_bowman+$17,
 army_battering_ram = army_battering_ram+$18, army_catapult = army_catapult+$19, army_adv_catapult = army_adv_catapult+$20,
 director_hero_pk = $21, director_desc = $22, staff_hero_pk = $23, staff_desc = $24, hour_food = $25, withdrawal_dt = now() + interval '{$increase_second} seconds'
WHERE troo_pk = $6

EOF;
                    } else {
                        $sql = <<< EOF
UPDATE troop SET
 reso_gold = reso_gold+$1, reso_food = reso_food+$2, reso_horse = reso_horse+$3, reso_lumber = reso_lumber+$4, reso_iron = reso_iron+$5, 
 army_worker = army_worker+$7,army_infantry = army_infantry+$8,army_pikeman = army_pikeman+$9,army_scout = army_scout+$10, army_spearman = army_spearman+$11,
 army_armed_infantry = army_armed_infantry+$12, army_archer = army_archer+$13, army_horseman = army_horseman+$14, army_armed_horseman = army_armed_horseman+$15, army_transporter = army_transporter+$16, army_bowman = army_bowman+$17,
 army_battering_ram = army_battering_ram+$18, army_catapult = army_catapult+$19, army_adv_catapult = army_adv_catapult+$20,
 director_hero_pk = $21, director_desc = $22, staff_hero_pk = $23, staff_desc = $24, hour_food = $25
WHERE troo_pk = $6
	
EOF;
                    }

                    $this->PgGame->query($sql, $query_params);

                    // triptime 재계산.
                    $this->PgGame->query('SELECT cmd_type, distance, captain_hero_pk, dst_posi_pk, src_posi_pk, army_worker, army_infantry, army_pikeman, army_scout, army_spearman, army_armed_infantry, army_archer, army_horseman, army_armed_horseman, army_transporter, army_bowman, army_battering_ram, army_catapult, army_adv_catapult FROM troop WHERE troo_pk = $1', [$exist_troop['troo_pk']]);
                    $this->PgGame->fetch();
                    $triptime_troo_info = $this->PgGame->row;

                    $triptime_army_arr = [];
                    foreach ($_M['ARMY_C'] as $k => $v) {
                        $triptime_army_arr[$k] = $triptime_troo_info['army_' . $k];
                    }

                    $triptime_armyPop = $this->getArmyPop($triptime_army_arr);

                    $troop_triptime = $this->getMoveTime($triptime_troo_info['src_posi_pk'], $triptime_troo_info['cmd_type'], $triptime_troo_info['distance'], $triptime_armyPop, $triptime_troo_info['captain_hero_pk']);
                    $troop_triptime = floor($troop_triptime); // 소수 점일때 에러나는 문제를 해결하기 위해 내림

                    $this->PgGame->query('UPDATE troop SET triptime = $2, move_time = $2 WHERE troo_pk = $1', [$exist_troop['troo_pk'], $troop_triptime]);

                    // 지원부대 남은 영웅 회군
                    $this->PgGame->query('UPDATE troop SET
 reso_gold = 0, reso_food = 0, reso_horse = 0, reso_lumber = 0, reso_iron = 0, 
 army_worker = 0,army_infantry = 0,army_pikeman = 0,army_scout = 0, army_spearman = 0,
 army_armed_infantry = 0, army_archer = 0, army_horseman = 0, army_armed_horseman = 0, army_transporter = 0, army_bowman = 0,
 army_battering_ram = 0, army_catapult = 0, army_adv_catapult = 0,
 director_hero_pk = $1, director_desc = $2, staff_hero_pk = $3, staff_desc = $4
WHERE troo_pk = $5', [$row['director_hero_pk'], $row['director_desc'], $row['staff_hero_pk'], $row['staff_desc'], $_troo_pk]);

                    // 자원 전달 후 영웅 편입
                    $report_type = 'reinforce_finish_5';
                }

                $z_summary_array[] = implode('/', $new_recall_heroes);

                // 주둔이 불가능한 영웅은 병력이 없는 상태이므로 복귀 시간 재계산 - 병력은 모두 0 으로
                $triptime_army_arr = [];
                foreach ($_M['ARMY_C'] as $k => $v) {
                    $triptime_army_arr[$k] = 0;
                }

                $triptime_armyPop = $this->getArmyPop($triptime_army_arr);

                $troop_triptime = $this->getMoveTime($triptime_troo_info['src_posi_pk'], 'R', $triptime_troo_info['distance'], $triptime_armyPop, $triptime_troo_info['captain_hero_pk']);
                $row['triptime'] = floor($troop_triptime); // 소수 점일때 에러나는 문제를 해결하기 위해 내림

                $z_content['recall']['script'] = '추가주둔이 불가능한 ' . implode('/', $new_recall_heroes) . ' 영웅은 <span class="cont_recall_move_time"></span> 뒤인 <span class="cont_recall_end_dt"></span> 에 출발지로 복귀 예정입니다.';
                $z_content['recall']['move_time'] = $row['triptime'];
                $z_content['recall']['end_dt'] = time() + $row['triptime'];

                $this->setStatusRecall($_troo_pk, $row);
            }

            // 퀘스트
            $this->classQuest();
            $this->Quest->conditionCheckQuest($row['src_lord_pk'], ['quest_type' => 'battle', 'type' => 'valley_reinforce']);
        }

        // title & summary
        $z_title = '';
        $z_summary = implode(':', $z_summary_array);
        $this->Report->setReport($row['src_lord_pk'], 'move', $report_type, $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));

    }

    // 정찰
    function setScout($_troo_pk, $row, $dst_posi): void
    {
        // 출정 : 영웅 + 정찰병
        // 저장 : 없음
        // 복귀 : 영웅 + 잔여 정찰병
        // - troop 의 경우 즉시 첩보전 (자원지)
        // - territory 의 경우 가상 troop 생성 후 첩보전 (영지)
        // - npc 의 경우 가상 troop 생성 후 첩보전 (거점/자원지/영지)
        $this->classEffect();
        $this->classQuest();
        $this->classReport();
        global $_M;

        // 정찰타입 (실제로는 아래에서 결정)
        $scout_type = 'valley';

        // 부대정보 추출
        if ($dst_posi['relation'] == 'NPC') {
            if ($dst_posi['type'] == 'D') { // 황건 토벌지역
                // 이벤트 인지 확인
                /*$query_params = Array($row['dst_posi_pk'], 'N');
                $this->PgGame->query('SELECT t1.supp_pk FROM suppress_position t1, my_event t2 WHERE t1.supp_pk = t2.event_supp_pk AND t1.posi_pk = $1 AND t2.event_supp_success = $2', $query_params);
                $supp_pk = $this->PgGame->fetchOne();

                if ($supp_pk) {
                    $dst_troop = $this->getDstTroopFromNpcSuppressEvent($row['src_lord_pk'], $row['dst_posi_pk']);
                } else {
                    $dst_troop = $this->getDstTroopFromNpcSuppress($row['src_lord_pk'], $row['dst_posi_pk']);
                }*/
                $dst_troop = $this->getDstTroopFromNpcSuppress($row['src_lord_pk'], $row['dst_posi_pk']);
            } else if ($dst_posi['type'] == 'N') { // 황건 성
                $dst_troop = $this->getDstTroopFromNpcTerritory($row['dst_posi_pk']);
                $scout_type = 'territory_npc';
            } else if ($dst_posi['type'] == 'P') { // 요충지
                $dst_troop = $this->getDstTroopFromNpcPoint($row['dst_posi_pk']);
            } else { // 황건 자원지
                $dst_troop = $this->getDstTroopFromNpcValley($row['dst_posi_pk']);
            }
        } else {
            if ($dst_posi['type'] == 'T') { // 타 군주 영지
                $dst_troop = $this->getDstTroopFromLordTerritory($row['dst_posi_pk']);
                $scout_type = 'territory_lord';
            } else { // 타 군주 자원지
                $dst_troop = $this->getDstTroopFromLordValley($row['dst_posi_pk']);
            }
        }

        if ($scout_type == 'valley') {
            if (!$dst_troop) {
                $dst_troop = [];
                $dst_troop['army_scout'] = 0;
                $dst_troop['reso_gold'] = 0;
                $dst_troop['reso_food'] = 0;
                $dst_troop['reso_horse'] = 0;
                $dst_troop['reso_lumber'] = 0;
                $dst_troop['reso_iron'] = 0;
                $dst_troop['captain_desc'] = '';
                $dst_troop['director_desc'] = '';
                $dst_troop['staff_desc'] = '';
            }
        } else if (!$dst_troop) {
            $this->setStatusRecall($_troo_pk, $row);
            Debug::debugMessage('ERROR', '대상 정보를 생성할 수 없습니다.');
            // echo "[OK]"; TODO OK 찍을 필요가 있나?
            exit;
        }

        // 공격측 첩보력
        $intelligence_attack = $row['army_scout'];

        // 공격측 영웅 명령 효과(태학포함)
        $capacities = $this->Effect->getHeroCapacityEffects($row['captain_hero_pk']);
        $applies = $this->Effect->getHeroAppliedCommandEffects(PK_CMD_SCOUT, $capacities);
        $ret = $this->Effect->getEffectedValue($row['src_posi_pk'], ['intelligence_increase', 'intelligence_increase_attack'], $intelligence_attack, $applies['all']);
        $intelligence_attack = $ret['value'];
        // 영웅 스킬 효과
        $skill_info = [];
        $skill_info['hero_pk'] = $this->getHeroMasterDataPK($row['captain_hero_pk']);
        $skill_info['m_hero_skil_pk'] = $ret['effected_values']['m_hero_skil_pk'] ?? null;

        // 방어측 첩보력
        $intelligence_defence = $dst_troop['army_scout'] ?? 0;

        // 방어측 영웅 대사관배속 효과(태학포함)
        if ($scout_type == 'territory_lord') {
            $ret = $this->Effect->getEffectedValue($row['dst_posi_pk'], ['intelligence_increase', 'intelligence_increase_defence'], $intelligence_defence);
            $intelligence_defence = $ret['value'];
        } // valley = $intelligence_defence 그대로 사용

        // 정찰 등급 선택 테이블
        $scout_value = $intelligence_attack - $intelligence_defence;
        $scout_level_table = match (true) {
            ($scout_value >= 200) => 6,
            ($scout_value >= 150) => 5,
            ($scout_value >= 100) => 4,
            ($scout_value >= 50) => 3,
            ($scout_value >= 0) => 2,
            default => 1
        };

        // 확률 테이블 돌리기
        if ($scout_type == 'territory_lord' || $scout_type == 'territory_npc') {
            $range_arr = &$_M['CODESET']['SCOUT_LEVEL_TABLE']['territory'][$scout_level_table];
        } else {
            $range_arr = &$_M['CODESET']['SCOUT_LEVEL_TABLE'][$scout_type][$scout_level_table];
        }
        $range_prev = 1;
        $range_select = 0; // scout_level

        $range_random_key = rand(1, 100); // 백

        foreach ($range_arr as $k => $v) {
            if ($v == 0) {
                continue;
            }
            $next = $range_prev + $v;
            if ($range_random_key >= $range_prev && $range_random_key <= $next) {
                $range_select = $k;
                break;
            }
            $range_prev = $next;
        }

        //$range_select = 0;

        // 정찰 보고서
        $z_content = [];
        $z_content['intelligence'] = ['attack' => $intelligence_attack, 'defence' => $intelligence_defence];
        $z_content['scout_type'] = $scout_type;
        $z_content['scout_value'] = $scout_value;
        $z_content['scout_level_table'] = $scout_level_table;
        $z_content['scout_level'] = $range_select;
        $z_content['hero_skill'] = $skill_info;
        $z_content['type'] = $dst_posi['type'];

        // 정찰 결과
        if ($range_select == 0) { // 정찰 실패
            // 피해
            $scout_dead = min($dst_troop['army_scout'], $row['army_scout']);

            // 피해반영
            if ($scout_dead > 0) {
                $this->PgGame->query('UPDATE troop SET army_scout = army_scout - $1 WHERE troo_pk = $2', [$scout_dead, $_troo_pk]);
            }

            $z_content['scout_amount'] = $row['army_scout'];
            $z_content['scout_dead'] = $scout_dead;

            // from & to
            $z_from = ['posi_pk' => $row['src_posi_pk'], 'posi_name' => $row['from_position']];
            $z_to = ['posi_pk' => $row['dst_posi_pk'], 'posi_name' => $row['to_position']];

            // title & summary

            // 정찰측
            $z_content['report_type'] = 'attack';
            $z_title = '';
            $z_summary = '';
            $this->Report->setReport($row['src_lord_pk'], 'scout', 'scout_failure', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));
            $this->Session->sqAppend('PUSH', ['TOAST' => [
                'type' => 'scout',
                'result' => 'failure',
                'posi_pk' => $row['dst_posi_pk']
            ]], null, $row['src_lord_pk']);

            if ($row['src_lord_pk'] == NPC_TROOP_LORD_PK) {
                $alli_pk = null;
                $lord_name = '황건적';
                $lord_name_withLevel = '황건적';
            } else {
                $this->PgGame->query('SELECT alli_pk, lord_name, level FROM lord WHERE lord_pk = $1', [$row['src_lord_pk']]);
                $this->PgGame->fetch();
                $alli_pk = $this->PgGame->row['alli_pk'];
                $lord_name = $this->PgGame->row['lord_name'];
                $lord_level = $this->PgGame->row['level'];
                $lord_name_withLevel = $lord_name . ' Lv.' . $this->PgGame->row['level'];
            }

            if ($row['dst_lord_pk'] != NPC_TROOP_LORD_PK) {
                // 방어측
                $z_content['report_type'] = 'defence';
                $z_from['posi_name'] = '타 군주'; // TODO 텍스트 코드로 변환 필요
                $repo_pk = $this->Report->setReport($row['dst_lord_pk'], 'scout', 'scout_find', $z_to, $z_from, $z_title, $z_summary, json_encode($z_content));

                // 동맹 전투
                /*if ($row['src_lord_pk'] != $row['dst_lord_pk']) {
                    $this->getAllianceClass();
                    $this->Alliance->setAllianceWarHistory($row['dst_lord_pk'], $repo_pk, $dst_posi['lord_name'], 'S_D', $lord_name, $row['src_posi_pk']);
                }*/
            }

            $this->classLog();
            $this->Log->setBattle($row['src_lord_pk'], $row['src_posi_pk'], 'scout', $dst_posi['lord_pk'], $lord_name_withLevel, $row['dst_posi_pk'], $row['from_position'], 'scout_find', $z_summary, json_encode($z_content), $row['troop_type'], '실패', '', '', $_troo_pk);
        } else { // 정찰 성공
            if ($scout_type == 'valley') {
                // 황건적 자원지 정찰
                if ($dst_posi['relation'] == 'NPC') {
                    $this->Quest->conditionCheckQuest($row['src_lord_pk'], ['quest_type' => 'battle', 'type' => 'scout_vally_npc']);
                }
                // 방어시설
                if ($range_select >= 1) {
                    $z_arr = [];
                    foreach ($dst_troop as $k => $v) {
                        if (str_starts_with($k, 'fort_')) {
                            $z_arr[substr($k, 5)] = $v;
                        }
                    }
                    $z_content['fort'] = $z_arr;
                }

                // 성문 상태
                $z_content['yn_valley'] = true;
            } else {
                if ($dst_posi['relation'] == 'NPC') { // 황건적 영지 정찰
                    $this->Quest->conditionCheckQuest($row['src_lord_pk'], ['quest_type' => 'battle', 'type' => 'scout_territory_npc']);
                    $z_content['yn_npc'] = true;
                } else { // 군주 영지 정찰
                    $this->Quest->conditionCheckQuest($row['src_lord_pk'], ['quest_type' => 'battle', 'type' => 'scout_territory_lord']);
                    $z_content['yn_npc'] = false;
                }

                // 방어시설
                if ($range_select >= 1) {
                    $zArr = [];
                    foreach ($dst_troop as $k => $v) {
                        if (str_starts_with($k, 'fort_')) {
                            $zArr[substr($k, 5)] = $v;
                        }
                    }

                    $z_content['fort'] = $zArr;
                    $z_content['wall_level'] = $dst_troop['wall_level'];
                }

                // 보유 자원량
                if ($range_select >= 2) {
                    $z_content['reso']['gold'] = $dst_troop['reso_gold'];
                    $z_content['reso']['food'] = $dst_troop['reso_food'];
                    $z_content['reso']['horse'] = $dst_troop['reso_horse'];
                    $z_content['reso']['lumber'] = $dst_troop['reso_lumber'];
                    $z_content['reso']['iron'] = $dst_troop['reso_iron'];
                }
            }

            // 병력 규모
            if ($range_select >= 3) {
                $army_scale = 0;
                foreach ($dst_troop as $k => $v) {
                    if (str_starts_with($k, 'army_')) {
                        $army_scale += $v;
                    }
                }
                $z_content['army_scale'] = $army_scale;
            }

            // 병과별 병력 수
            if ($range_select >= 4) {
                $z_arr = [];
                foreach ($dst_troop as $k => $v) {
                    if (str_starts_with($k, 'army_')) {
                        $z_arr[substr($k, 5)] = $v;
                    }
                }
                $z_content['army'] = $z_arr;
            }

            // 영웅 목록
            if ($range_select >= 5) {
                if ($scout_type == 'territory_lord') {
                    // dst_posi_pk 소속 영웅 추출
                    $this->PgGame->query('SELECT t1.hero_pk, t2.m_hero_pk, t2.level FROM my_hero AS t1, hero AS t2 WHERE t1.hero_pk = t2.hero_pk AND t1.lord_pk = $1 AND t1.posi_pk = $2 AND t1.status = $3', [$row['dst_lord_pk'], $row['dst_posi_pk'], 'A']);
                    while ($this->PgGame->fetch()) {
                        $r =& $this->PgGame->row;
                        $z_content['hero'][] = ['pk' => $r['hero_pk'], 'm_pk' => $r['m_hero_pk'], 'level' => $r['level']];
                    }
                    // 성문 상태
                    $z_content['wall_open'] = $dst_troop['wall_open'];
                } else {
                    $z_content['hero'][] = ['pk' => $dst_troop['captain_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($dst_troop['captain_hero_pk'])];
                    if (isset($dst_troop['director_desc'])) {
                        $z_content['hero'][] = ['pk' => $dst_troop['director_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($dst_troop['director_hero_pk'])];
                    }
                    if (isset($dst_troop['staff_desc'])) {
                        $z_content['hero'][] = ['pk' => $dst_troop['staff_hero_pk'], 'm_pk' => $this->getHeroMasterDataPK($dst_troop['staff_hero_pk'])];
                    }
                }
            }

            // from & to
            $z_from = ['posi_pk' => $row['src_posi_pk'], 'posi_name' => $row['from_position']];
            $z_to = ['posi_pk' => $row['dst_posi_pk'], 'posi_name' => $row['to_position']];

            // title & summary TODO 텍스트 코드 처리 필요.
            $z_title = '';
            $z_summary = $row['to_position'] . ':' . $range_select;
            $repo_pk = $this->Report->setReport($row['src_lord_pk'], 'scout', 'scout_success', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));
            $this->Session->sqAppend('PUSH', ['TOAST' => [
                'type' => 'scout',
                'result' => 'success',
                'posi_pk' => $row['dst_posi_pk']
            ]], null, $row['src_lord_pk']);

            if ($row['src_lord_pk'] == NPC_TROOP_LORD_PK) {
                $alli_pk = null;
                $lord_name = '황건적';
                $lord_name_withLevel = '황건적';
            } else {
                $this->PgGame->query('SELECT alli_pk, lord_name, level FROM lord WHERE lord_pk = $1', [$row['src_lord_pk']]);
                $this->PgGame->fetch();
                $alli_pk = $this->PgGame->row['alli_pk'];
                $lord_name = $this->PgGame->row['lord_name'];
                $lord_level = $this->PgGame->row['level'];
                $lord_name_withLevel = $lord_name . ' Lv.' . $this->PgGame->row['level'];

                // 동맹 전투
                /*if ($row['src_lord_pk'] != $row['dst_lord_pk'] && $scout_type == 'territory_lord') {
                    $this->classAlliance();
                    $this->Alliance->setAllianceWarHistory($row['src_lord_pk'], $repo_pk, $lord_name, 'S_A', $dst_posi['lord_name'], $row['dst_posi_pk']);
                }*/
            }

            $this->classLog();
            $this->Log->setBattle($row['src_lord_pk'], $row['src_posi_pk'], 'scout', $dst_posi['lord_pk'], $lord_name_withLevel, $row['src_posi_pk'], $row['from_position'], 'scout_success', $z_summary, json_encode($z_content), $row['troop_type'], '성공', '', '', $_troo_pk);
        }

        $this->setStatusRecall($_troo_pk, $row);
    }

    function possibleBattle($_troo_pk, $row, $dst_troop): bool
    {
        $this->classEffect();
        $ret = $this->Effect->getEffectedValue($row['dst_posi_pk'], ['non_battle_increase'], 1);
        $rand = rand(1, 100);
        $skill_value = $ret['effected_values']['hero_skill'];
        if ($rand < $skill_value) {
            $this->setStatusRecall($_troo_pk, $row);

            // 스킬 발동됨 - 어떤 영웅인지 찾기
            $this->PgGame->query('SELECT assign_hero_pk FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$row['dst_posi_pk'], PK_BUILDING_WALL]);
            $assign_hero_pk = $this->PgGame->fetchOne();

            $this->classLog();
            $this->Log->setHeroSkillActive($row['dst_lord_pk'], $row['dst_posi_pk'], $assign_hero_pk, 'none_battle', $rand, $ret['value'] - 1, $_troo_pk);

            // 보고서
            $z_content = [];

            // from & to
            $z_from = ['posi_pk' => $row['src_posi_pk'], 'posi_name' => $row['from_position']];
            $z_to = ['posi_pk' => $row['dst_posi_pk'], 'posi_name' => $row['to_position']];

            // 영웅
            $captain_m_hero_pk = null;
            $director_m_hero_pk = null;
            $staff_m_hero_pk = null;
            if ($row['captain_hero_pk']) {
                $captain_m_hero_pk = $this->getHeroMasterDataPK($row['captain_hero_pk']);
            }
            if ($row['director_hero_pk']) {
                $director_m_hero_pk = $this->getHeroMasterDataPK($row['director_hero_pk']);
            }
            if ($row['staff_hero_pk']) {
                $staff_m_hero_pk = $this->getHeroMasterDataPK($row['staff_hero_pk']);
            }

            $att_hero_arr = ['captain_hero_pk' => $row['captain_hero_pk'], 'captain_m_hero_pk' => $captain_m_hero_pk, 'captain_desc' => $row['captain_desc'], 'director_hero_pk' => $row['director_hero_pk'], 'director_desc' => $row['director_desc'], 'director_m_hero_pk' => $director_m_hero_pk, 'staff_hero_pk' => $row['staff_hero_pk'], 'staff_m_hero_pk' => $staff_m_hero_pk, 'staff_desc' => $row['staff_desc']];

            $captain_m_hero_pk = null;
            $director_m_hero_pk = null;
            $staff_m_hero_pk = null;
            if ($dst_troop['captain_hero_pk']) {
                $captain_m_hero_pk = $this->getHeroMasterDataPK($dst_troop['captain_hero_pk']);
            }
            if ($dst_troop['director_hero_pk']) {
                $director_m_hero_pk = $this->getHeroMasterDataPK($dst_troop['director_hero_pk']);
            }
            if ($dst_troop['staff_hero_pk']) {
                $staff_m_hero_pk = $this->getHeroMasterDataPK($dst_troop['staff_hero_pk']);
            }

            $def_hero_arr = ['captain_hero_pk' => $dst_troop['captain_hero_pk'], 'captain_m_hero_pk' => $captain_m_hero_pk, 'captain_desc' => $dst_troop['captain_desc'], 'director_hero_pk' => $dst_troop['director_hero_pk'], 'director_desc' => $dst_troop['director_desc'], 'director_m_hero_pk' => $director_m_hero_pk, 'staff_hero_pk' => $dst_troop['staff_hero_pk'], 'staff_m_hero_pk' => $staff_m_hero_pk, 'staff_desc' => $dst_troop['staff_desc']];

            $z_content['outcome_hero']['att'] = $att_hero_arr;
            $z_content['outcome_hero']['def'] = $def_hero_arr;

            if ($row['src_lord_pk'] != NPC_TROOP_LORD_PK) {
                $this->classReport();
                $this->Report->setReport($row['src_lord_pk'], 'battle', 'battle_none', $z_from, $z_to, '', '', json_encode($z_content));
            }

            if ($row['dst_lord_pk'] != NPC_TROOP_LORD_PK) {
                $this->classReport();
                $this->Report->setReport($row['dst_lord_pk'], 'battle', 'battle_none', $z_to, $z_from, '', '', json_encode($z_content));
            }
            return false;
        }
        return true;
    }

    function heroBattleResult($man_to_man_ret, &$_row, &$_hero_arr, $_troo_pk): void
    {
        foreach ($man_to_man_ret as $v) {
            if ($_row['captain_hero_pk'] == $v['hero_pk']) {
                if ($v['energy'] < 25) {
                    $_row['captain_hero_pk'] = null;
                    $this->PgGame->query('UPDATE troop SET captain_hero_pk = null, captain_desc = null WHERE troo_pk = $1', [$_troo_pk]);
                }
                $_hero_arr['captain_hero_battle'] = true;
                $_hero_arr['captain_hero_energy'] = $v['energy'];
            } else if ($_row['director_hero_pk'] == $v['hero_pk']) {
                if ($v['energy'] < 25) {
                    $_row['director_hero_pk'] = null;
                    $this->PgGame->query('UPDATE troop SET director_hero_pk = null, director_desc = null WHERE troo_pk = $1', [$_troo_pk]);
                }
                $_hero_arr['director_hero_battle'] = true;
                $_hero_arr['director_hero_energy'] = $v['energy'];
            } else {
                if ($v['energy'] < 25) {
                    $_row['staff_hero_pk'] = null;
                    $this->PgGame->query('UPDATE troop SET staff_hero_pk = null, staff_desc = null WHERE troo_pk = $1', [$_troo_pk]);
                }
                $_hero_arr['staff_hero_battle'] = true;
                $_hero_arr['staff_hero_energy'] = $v['energy'];
            }
        }
    }

    function getHeroLeadership($_hero_pk, $_type): mixed
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['troop']);
        $this->PgGame->query('SELECT leadership FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
        $leadership = $this->PgGame->fetchOne();
        $curr_leadership = 0;
        $result = 0;
        foreach ($_M['TROOP'][$_type . '_LEAD_POPULATION'] as $k => $v) {
            if ($curr_leadership < $k) {
                $curr_leadership = $k;
            }
            if ($leadership < $k) {
                $result = $v;
                break;
            }
        }
        if ($result == 0) {
            $result = $_M['TROOP'][$_type . '_LEAD_POPULATION'][$curr_leadership];
        }
        return $result;
    }

    function getArmyPopulation($_army): int
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army']);
        // 환산병력수
        $army_arr = [];
        if (isset($_M['ARMY_C'])) {
            foreach ($_M['ARMY_C'] as $k => $v) {
                $army_arr[$k] = $_army['army_' . $k];
            }
        }
        $army_pop = $this->getArmyPop($army_arr);
        return $army_pop['population'];
    }

    // 윤리도
    function getMorals($_src_lord_pk, $_dst_lord_pk, $_relation, $_type): float|int
    {
        $this->PgGame->query('SELECT power FROM lord WHERE lord_pk = $1', [$_src_lord_pk]);
        $att_power = $this->PgGame->fetchOne();
        $this->PgGame->query('SELECT power FROM lord WHERE lord_pk = $1', [$_dst_lord_pk]);
        $def_power = $this->PgGame->fetchOne();
        $power_value = 10;
        $morals = 100;
        if ($_relation != 'NPC' && $_type != 'P') {
            if ($def_power < LORD_MIN_POWER) {
                $def_power = LORD_MIN_POWER;
            }
            if ($def_power && $att_power && $_relation != 'ALLY_H') {
                $morals_values = intval(($att_power / $def_power) * 1000) / 1000; // 소수점 3자리 까지 남기기위해
                $morals = 100 - (($morals_values * $power_value) - 10);
            }
            if ($morals < 60) {
                $morals = 60;
            } else if ($morals > 100) {
                $morals = 100;
            }
        }
        return $morals;
    }

    // 임시로 추가해준 부분
    function getPowers($_src_lord_pk, $_dst_lord_pk, $_relation): array
    {
        $this->PgGame->query('SELECT power FROM lord WHERE lord_pk = $1', [$_src_lord_pk]);
        $att_power = $this->PgGame->fetchOne();
        $def_power = 0;
        if ($_relation != 'NPC') {
            $this->PgGame->query('SELECT power FROM lord WHERE lord_pk = $1', [$_dst_lord_pk]);
            $def_power = $this->PgGame->fetchOne();
        }
        return ['att_power' => $att_power, 'def_power' => $def_power];
    }

    // 부대 통솔 한계 초과로 인한 하향 값 - TODO return 값이 없는 경우가 있어서 코드를 약간 수정했는데 동작 확인 바람.
    function getFightingSpiritDown($_lead_limit_population)
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['troop']);
        $s = 0;
        foreach ($_M['TROOP']['FIGHTING_SPIRIT_DOWN'] as $k => $v) {
            if ($_lead_limit_population < $k) {
                $s = $v;
                break;
            }
        }
        return ($_lead_limit_population <= 0) ? 0 : $s;
    }

    // 부대사기 - TODO OLD가 붙은거 보니 안쓰는 코드구만. 만약을 위해 정리만 해둠.
    function getFightingSpiritOLD($_captain_hero_pk, $_posi_pk, $_fightingSpirit, $_figth_spirit_up, $_figth_spirit_down): int
    {
        // 공격측 영웅 명령 효과(태학포함)
        $this->classEffect();
        $capacities = $this->Effect->getHeroCapacityEffects($_captain_hero_pk);
        $applies = $this->Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_CAPTAIN, $capacities);
        $ret = $this->Effect->getEffectedValue($_posi_pk, ['troop_fighting_spirit_increase'], $_fightingSpirit, $applies['all']);
        $fightingSpirit = $ret['value'];
        if ($_figth_spirit_up) {
            $_figth_spirit_up *= $fightingSpirit;
            $fightingSpirit += $_figth_spirit_up;
        }
        if ($_figth_spirit_down) {
            $_figth_spirit_down *= $fightingSpirit;
            $fightingSpirit -= $_figth_spirit_down;
        }
        if ($fightingSpirit > 110) {
            $fightingSpirit = 110;
        } else if ($fightingSpirit < 1) {
            $fightingSpirit = 1;
        }
        return $fightingSpirit;
    }

    function getFightingSpirit($_posi_pk): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['troop']);

        // 부대 정보
        $troop_info = $this->getDstTroopFromLordTerritory($_posi_pk);

        // 부대사기
        $this->PgGame->query('SELECT leadership FROM my_hero WHERE hero_pk = $1', [$troop_info['captain_hero_pk']]);
        $leadership = $this->PgGame->fetchOne();
        $att_lead_population = 0;
        foreach ($_M['TROOP']['DEFENCE_LEAD_POPULATION'] as $k => $v) {
            if ($leadership < $k) {
                $att_lead_population = $v;
                break;
            }
        }
        $this->classEffect();

        // 통솔력 증가
        $capacities = $this->Effect->getHeroCapacityEffects($troop_info['captain_hero_pk']);
        $applies = $this->Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_CAPTAIN, $capacities);
        $r = $this->Effect->getEffectedValue($troop_info['src_posi_pk'], ['troop_leadership_increase'], $att_lead_population, $applies['all']);
        $leadership_attack = $r['value'];
        // $def_hero_arr['leadership'] = $leadership_attack; // TODO 얘 안쓰고 있는데...

        // 병력수
        $army_arr = [];
        foreach ($_M['ARMY_C'] as $k => $v) {
            $army_arr[$k] = $troop_info['army_' . $k];
        }

        $army_pop = $this->getArmyPop($army_arr);

        // 부대 사기
        $fighting_spirit_down = 0;
        // 윤리도
        $morals = 100;
        // 통솔한계효과
        $att_lead_limit_population = $army_pop['population'] - $leadership_attack;

        foreach ($_M['TROOP']['FIGHTING_DEFENCE_SPIRIT_DOWN'] as $k => $v) {
            if ($att_lead_limit_population <= 0) {
                break;
            } else if ($att_lead_limit_population <= $k) {
                $fighting_spirit_down = $v;
                break;
            }
        }

        //부대사기
        $fighting_spirit = $morals - $fighting_spirit_down;
        //영웅 명령 효과(태학포함)
        $capacities = $this->Effect->getHeroCapacityEffects($troop_info['captain_hero_pk']);
        $applies = $this->Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_CAPTAIN, $capacities);
        $r = $this->Effect->getEffectedValue($troop_info['src_posi_pk'], ['troop_fighting_spirit_increase'], $fighting_spirit, $applies['all']);

        return ['fightingSpirit' => $r['value'], 'armyPop' => $army_pop]; // TODO 일단 호환성을 위해 그냥두지만 차후에 변수명 변경해주자.
    }

    // TODO NEW 이긴 한데 원래 코드에서 안쓰던 놈이긴 하더라 - 차후엔 안쓰는 함수는 죄다 정리 해야 할듯
    function getFightingSpiritNEW($_posi_pk): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['troop']);

        // 부대 정보
        $troop_info = $this->getDstTroopFromLordTerritory($_posi_pk);

        // 부대사기
        $this->PgGame->query('SELECT leadership FROM my_hero WHERE hero_pk = $1', [$troop_info['captain_hero_pk']]);
        $leadership = $this->PgGame->fetchOne();

        foreach ($_M['TROOP']['DEFENCE_LEAD_POPULATION'] as $k => $v) {
            if ($leadership < $k) {
                $att_lead_population = $v;
                break;
            }
        }

        $this->classEffect();

        // 통솔력 증가
        $applies = [];
        $capacities = $this->Effect->getHeroCapacityEffects($troop_info['captain_hero_pk']);
        $applies = $this->Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_CAPTAIN, $capacities);
        $r = $this->Effect->getEffectedValue($troop_info['src_posi_pk'], ['troop_leadership_increase'], $att_lead_population, $applies['all']);
        $leadership_attack = $r['value'];
        $def_hero_arr['leadership'] = $leadership_attack;

        // 병력수
        $army_arr = [];
        foreach ($_M['ARMY_C'] as $k => $v) {
            $army_arr[$k] = $troop_info['army_' . $k];
        }

        $army_pop = $this->getArmyPop($army_arr);

        // 부대 사기
        $fighting_spirit_down = 0;
        // 윤리도
        $morals = 100;
        // 통솔한계효과
        $att_lead_limit_population = $army_pop['population'] - $leadership_attack;
        foreach ($_M['TROOP']['FIGHTING_DEFENCE_SPIRIT_DOWN'] as $k => $v) {
            if ($att_lead_limit_population <= 0) {
                break;
            } else if ($att_lead_limit_population <= $k) {
                $fighting_spirit_down = $v;
                break;
            }
        }

        //부대사기
        $fighting_spirit = $morals - $fighting_spirit_down;
        //영웅 명령 효과(태학포함)
        $capacities = $this->Effect->getHeroCapacityEffects($troop_info['captain_hero_pk']);
        $applies = $this->Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_CAPTAIN, $capacities);
        $r = $this->Effect->getEffectedValue($troop_info['src_posi_pk'], ['troop_fighting_spirit_increase'], $fighting_spirit, $applies['all']);

        return ['fightingSpirit' => $r['value'], 'armyPop' => $army_pop]; // TODO 일단 호환성을 위해 그냥두지만 차후에 변수명 변경해주자.
    }

    // 병과별 기본 공격력
    function getArmyCategory($_row): array
    {
        global $_M;

        $army_category = ['infantry' => [], 'spearman' => [], 'pikeman' => [], 'archer' => [], 'horseman' => [], 'siege' => []];
        if (isset($_row['captain_hero_pk'])) {
            $m_hero_pk = $this->getHeroMasterDataPK($_row['captain_hero_pk']);
            foreach ($army_category as $k => $v) {
                $army_category[$k][] = $_M['CODESET']['ATTACK_INCREASE'][$_M['HERO_BASE'][$_M['HERO'][$m_hero_pk]['m_hero_base_pk']]['mil_aptitude_' . $k]];
            }
        }

        if (isset($_row['director_hero_pk'])) {
            $m_hero_pk = $this->getHeroMasterDataPK($_row['director_hero_pk']);
            foreach ($army_category as $k => $v) {
                $army_category[$k][] = $_M['CODESET']['ATTACK_INCREASE'][$_M['HERO_BASE'][$_M['HERO'][$m_hero_pk]['m_hero_base_pk']]['mil_aptitude_' . $k]];
            }
        }

        if (isset($_row['staff_hero_pk'])) {
            $m_hero_pk = $this->getHeroMasterDataPK($_row['staff_hero_pk']);
            foreach ($army_category as $k => $v) {
                $army_category[$k][] = $_M['CODESET']['ATTACK_INCREASE'][$_M['HERO_BASE'][$_M['HERO'][$m_hero_pk]['m_hero_base_pk']]['mil_aptitude_' . $k]];
            }
        }

        // 각 병과별 최대치
        foreach ($army_category as $k => $v) {
            $army_category[$k]['max_values'] = (count($v) > 0) ? max($v) : 0;
        }
        return $army_category;
    }

    function getHeroEffect($_director_hero_pk, $_staff_hero_pk, $_posi_pk): array
    {
        $this->classEffect();
        $att_applies = [];
        $def_applies = [];

        $attack_effect = null;
        if ($_director_hero_pk) {
            $att_capacities = $this->Effect->getHeroCapacityEffects($_director_hero_pk);
            $att_applies = $this->Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_DIRECTOR, $att_capacities);
            $ret = $this->Effect->getEffectedValue($_posi_pk, ['army_attack_increase'], 1, $att_applies['assign']);
            $attack_effect = $ret['effected_values']['hero_assign'];
        }
        $defence_effect = null;
        if ($_staff_hero_pk) {
            $def_capacities = $this->Effect->getHeroCapacityEffects($_staff_hero_pk);
            $def_applies = $this->Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_STAFF, $def_capacities);
            $ret = $this->Effect->getEffectedValue($_posi_pk, ['army_defence_increase'], 1, $def_applies['assign']);
            $defence_effect = $ret['effected_values']['hero_assign'];
        }
        return [$attack_effect, $defence_effect, $att_applies, $def_applies];
    }

    // 부대 효과 적용
    function getArmyEffect(&$_army, $_fighting_spirit, $_army_category, $_posi_pk, $_type = null, $att_applies = null, $def_applies = null, $_troop = null): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army', 'fortification']);

        $this->classEffect();
        $att_effect_types = [];
        $def_effect_types = [];

        foreach ($_army as $k => $v) {
            // 공격력
            $effect_types = [];
            if ($k != 'wall') {
                if (isset($_M['ARMY_C'][$k]['attack_effect_type'])) {
                    $effect_type = explode(',', $_M['ARMY_C'][$k]['attack_effect_type']);
                    for ($i = 0; $i < COUNT($effect_type); $i++) {
                        $effect_types[] = $effect_type[$i];
                        if (!in_array($effect_type[$i], $att_effect_types)) {
                            $att_effect_types[] = $effect_type[$i];
                        }
                    }
                }

                if (isset($_M['FORT_C'][$k]['attack_effect_type'])) {
                    $effect_type = explode(',', $_M['FORT_C'][$k]['attack_effect_type']);
                    for ($i = 0; $i < COUNT($effect_type); $i++) {
                        $effect_types[] = $effect_type[$i];
                        if (!in_array($effect_type[$i], $att_effect_types)) {
                            $att_effect_types[] = $effect_type[$i];
                        }
                    }
                }
            }

            // 병과적성
            $_army[$k]['max_values'] = (isset($_M['ARMY_C'][$k])) ? $_army_category[$_M['ARMY_C'][$k]['category_code']]['max_values'] : 0;
            // 영웅의 병과 적성치 최대 적용
            if ($k == 'trap' || $k == 'abatis' || $k == 'tower' || $k == 'wall') {
                $attack_incr = 0;
            } else {
                $attack_incr = $v['attack'] * ($_army_category[$_M['ARMY_C'][$k]['category_code']]['max_values'] * 0.01);
            }

            $ret = $this->Effect->getEffectedValue($_posi_pk, $effect_types, $v['attack'], $att_applies['all'] ?? []);
            $_army[$k]['attack'] = ($ret['value'] + $attack_incr) * $_fighting_spirit;

            // 방어력
            $effect_types = [];
            if ($k != 'wall') {
                if (isset($_M['ARMY_C'][$k]['defence_effect_type'])) {
                    $effect_type = explode(',', $_M['ARMY_C'][$k]['defence_effect_type']);
                    for ($i = 0; $i < COUNT($effect_type); $i++) {
                        $effect_types[] = $effect_type[$i];
                        if (!in_array($effect_type[$i], $def_effect_types)) {
                            $def_effect_types[] = $effect_type[$i];
                        }
                    }
                }

                if (isset($_M['FORT_C'][$k]['defence_effect_type'])) {
                    $effect_type = explode(',', $_M['FORT_C'][$k]['defence_effect_type']);
                    for ($i = 0; $i < COUNT($effect_type); $i++) {
                        $effect_types[] = $effect_type[$i];
                        if (!in_array($effect_type[$i], $def_effect_types)) {
                            $def_effect_types[] = $effect_type[$i];
                        }
                    }
                }
            }

            // 영웅의 병과 적성치 최대 적용
            if ($k == 'trap' || $k == 'abatis' || $k == 'tower' || $k == 'wall') {
                $defence_incr = 0;
                if ($k == 'wall') {
                    $effect_types[] = 'fort_defence_increase';
                    if (isset($_troop['director_hero_pk'])) {
                        $effect_types[] = 'wall_defence_increase';
                        if (!in_array('wall_defence_increase', $att_effect_types)) {
                            $att_effect_types[] = 'wall_defence_increase';
                        }
                    }
                }

                if ($_troop['staff_hero_pk'] && ($k == 'trap' || $k == 'abatis' || $k == 'tower')) {
                    if (!in_array('fort_defence_increase', $def_effect_types)) {
                        $def_effect_types[] = 'fort_defence_increase';
                    }
                }
            } else {
                $defence_incr = $v['defence'] * ($_army_category[$_M['ARMY_C'][$k]['category_code']]['max_values'] * 0.01);
            }

            if ($k == 'wall') {
                if ($_troop['director_hero_pk']) {
                    $capacities = $this->Effect->getHeroCapacityEffects($_troop['director_hero_pk']);
                    $wall_applies = $this->Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_DIRECTOR, $capacities);
                    $ret = $this->Effect->getEffectedValue($_posi_pk, $effect_types, $v['defence'], $wall_applies['all']);
                } else {
                    $ret = $this->Effect->getEffectedValue($_posi_pk, $effect_types, $v['defence']);
                }
            } else {
                $ret = $this->Effect->getEffectedValue($_posi_pk, $effect_types, $v['defence'], $def_applies['all'] ?? []);
            }

            if ($_type == 'defence') {
                /* 야간 방어 효과 제외 19.09.10
                 * if (!$_POST['night_shield'] && isset($_GET['night_shield'])) {
                    $_POST['night_shield'] = true;
                }
                if ($_POST['night_shield']) {
                    $_army[$k]['defence'] = (($ret['value'] + $defence_incr) * $_fightingSpirit) * 2;
                } else {
                    $_army[$k]['defence'] = (($ret['value'] + $defence_incr) * $_fightingSpirit) * $this->getNightShield();
                }*/
                $_army[$k]['defence'] = (($ret['value'] + $defence_incr) * $_fighting_spirit);
            } else {
                $_army[$k]['defence'] = (($ret['value'] + $defence_incr) * $_fighting_spirit);
            }

            // 생명력
            if ($k != 'trap' && $k != 'abatis' && $k != 'tower' && $k != 'wall') {
                if (!in_array('army_energy_increase', $def_effect_types)) {
                    $def_effect_types[] = 'army_energy_increase';
                }
                $ret = $this->Effect->getEffectedValue($_posi_pk, ['army_energy_increase'], $v['energy'], $def_applies['all'] ?? []);
                $_army[$k]['energy'] = $ret['value'];
            }
        }

        return [$att_effect_types, $def_effect_types];
    }

    // 아이템 효과
    function getItemEffect($_posi_pk): array
    {
        $this->classEffect();
        $item_effect = [];

        $effect_types = ['troop_leadership_increase'];
        $ret = $this->Effect->getEffectedValue($_posi_pk, $effect_types, 1);
        $item_effect['leadership'] = $ret['effected_values']['item'];

        $effect_types = ['army_attack_increase'];
        $ret = $this->Effect->getEffectedValue($_posi_pk, $effect_types, 1);
        $item_effect['attack'] = $ret['effected_values']['item'];

        $effect_types = ['army_defence_increase'];
        $ret = $this->Effect->getEffectedValue($_posi_pk, $effect_types, 1);
        $item_effect['defence'] = $ret['effected_values']['item'];

        $effect_types = ['army_energy_increase'];
        $ret = $this->Effect->getEffectedValue($_posi_pk, $effect_types, 1);
        $item_effect['energy'] = $ret['effected_values']['item'];

        return $item_effect;
    }

    // 영웅 스킬 효과
    function getHeroSkillEffect($_posi_pk, $_captain_hero_pk, $_director_hero_pk, $_staff_hero_pk, $_hero_arr, $_att_effect_types, $_def_effect_types): array
    {
        $this->classEffect();
        $skill_info = [];

        if ($_captain_hero_pk) {
            $skill_info['captain']['hero_pk'] = $_hero_arr['captain_m_hero_pk'];
            $att_capacities = $this->Effect->getHeroCapacityEffects($_captain_hero_pk);
            $att_applies = $this->Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_CAPTAIN, $att_capacities);
            $ret = $this->Effect->getEffectedValue($_posi_pk, ['troop_leadership_increase', 'troop_fighting_spirit_increase'], 1, $att_applies['skill']);
            $skill_info['captain']['m_hero_skil_pk'] = $ret['effected_values']['m_hero_skil_pk'] ?? null;
        }

        if ($_director_hero_pk) {
            $skill_info['director']['hero_pk'] = $_hero_arr['director_m_hero_pk'];
            $att_capacities = $this->Effect->getHeroCapacityEffects($_director_hero_pk);
            $att_applies = $this->Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_DIRECTOR, $att_capacities);
            $ret = $this->Effect->getEffectedValue($_posi_pk, $_att_effect_types, 1, $att_applies['skill']);
            $skill_info['director']['m_hero_skil_pk'] = $ret['effected_values']['m_hero_skil_pk'] ?? null;
        }

        if ($_staff_hero_pk) {
            $skill_info['staff']['hero_pk'] = $_hero_arr['staff_m_hero_pk'];
            $att_capacities = $this->Effect->getHeroCapacityEffects($_staff_hero_pk);
            $att_applies = $this->Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_STAFF, $att_capacities);
            $ret = $this->Effect->getEffectedValue($_posi_pk, $_def_effect_types, 1, $att_applies['skill']);
            $skill_info['staff']['m_hero_skil_pk'] = $ret['effected_values']['m_hero_skil_pk'] ?? null;
        }

        return $skill_info;
    }

    // TODO 실제로는 안쓰던데...
    function armyConvertedAmount($_army_arr, $battle_type, $_army): float|int
    {
        $this->classBattle();

        $army_converted_amount = 0;
        foreach ($_army_arr as $k => $v) {
            $army_exist = str_starts_with($k, 'army_');
            if ($army_exist || str_starts_with($k, 'fort_')) {
                if ($v > 0) {
                    $unit_type = substr($k, 5);
                    $this->Battle->addUnit($battle_type, $_army, $unit_type, $v);
                    if ($army_exist && isset($_M['ARMY_C'][$unit_type])) {
                        $army_converted_amount += $_M['ARMY_C'][$unit_type]['need_population'] * $v;
                    }
                }
            }
        }
        return $army_converted_amount;
    }

    function setHeroBattleResult(&$_troop_info, $_hero_battle_info, $_troo_pk, $_type = null, $_battle_type = null): true
    {
        // 영웅 부상 없어짐
        return true;
        /*$this->getMedicalClass();
        if ($_hero_battle_info)
        {
            foreach($_hero_battle_info AS $k => $v)
            {
                if ($v['energy'] >= 25 && $v['energy'] < 50)
                {
                    // 경상
                    $this->Medical->setInjuryHero($_troop_info['src_posi_pk'], $v['hero_pk'], 'W', $_troop_info['src_lord_pk']);
                } else if ($v['energy'] >= 10 && $v['energy'] < 25) {
                    // 중상
                    $this->Medical->setInjuryHero($_troop_info['src_posi_pk'], $v['hero_pk'], 'E', $_troop_info['src_lord_pk']);
                } else if ($v['energy'] < 10) {
                    // 치명상
                    $this->Medical->setInjuryHero($_troop_info['src_posi_pk'], $v['hero_pk'], 'F', $_troop_info['src_lord_pk']);
                }

                if ($_type == 'att')
                {
                    if (floor($v['energy']) < 50)
                    {
                        $query_param = Array($_troo_pk);
                        if ($v['hero_pk'] == $_troop_info['captain_hero_pk'])
                        {
                            $this->PgGame->query('UPDATE troop SET captain_hero_pk = null, captain_desc = null WHERE troo_pk = $1', $query_param);
                            $_troop_info['captain_hero_pk'] = null;
                            $_troop_info['captain_desc'] = null;
                        } else if ($v['hero_pk'] == $_troop_info['director_hero_pk']) {
                            $this->PgGame->query('UPDATE troop SET director_hero_pk = null, director_desc = null WHERE troo_pk = $1', $query_param);
                            $_troop_info['director_hero_pk'] = null;
                            $_troop_info['director_desc'] = null;
                        } else if ($v['hero_pk'] == $_troop_info['staff_hero_pk']) {
                            $this->PgGame->query('UPDATE troop SET staff_hero_pk = null, staff_desc = null WHERE troo_pk = $1', $query_param);
                            $_troop_info['staff_hero_pk'] = null;
                            $_troop_info['staff_desc'] = null;
                        }
                    }
                } else if ($_type == 'def' && $_battle_type = 'valley') {
                    if (floor($v['energy']) < 50)
                    {
                        $query_param = Array($_troo_pk);
                        if ($v['hero_pk'] == $_troop_info['captain_hero_pk'])
                        {
                            $this->PgGame->query('UPDATE troop SET captain_hero_pk = null, captain_desc = null WHERE troo_pk = $1', $query_param);
                            $_troop_info['captain_hero_pk'] = null;
                            $_troop_info['captain_desc'] = null;
                        } else if ($v['hero_pk'] == $_troop_info['director_hero_pk']) {
                            $this->PgGame->query('UPDATE troop SET director_hero_pk = null, director_desc = null WHERE troo_pk = $1', $query_param);
                            $_troop_info['director_hero_pk'] = null;
                            $_troop_info['director_desc'] = null;
                        } else if ($v['hero_pk'] == $_troop_info['staff_hero_pk']) {
                            $this->PgGame->query('UPDATE troop SET staff_hero_pk = null, staff_desc = null WHERE troo_pk = $1', $query_param);
                            $_troop_info['staff_hero_pk'] = null;
                            $_troop_info['staff_desc'] = null;
                        }
                    }
                }
            }
        }

        $remain_captain = true;
        // 주장 재 선출 없을 경우
        if ($_type == 'att')
        {
            if (!$_troop_info['captain_hero_pk'])
            {
                if ($_troop_info['director_hero_pk'])
                {
                    $_troop_info['captain_hero_pk'] = $_troop_info['director_hero_pk'];
                    $_troop_info['captain_desc'] = $_troop_info['director_desc'];

                    $query_param = Array($_troo_pk, $_troop_info['director_hero_pk'], $_troop_info['director_desc']);
                    $this->PgGame->query('UPDATE troop SET captain_hero_pk = $2, captain_desc = $3, director_hero_pk = null, director_desc = null WHERE troo_pk = $1', $query_param);

                    $_troop_info['director_hero_pk'] = null;
                    $_troop_info['director_desc'] = null;
                } else if ($_troop_info['staff_hero_pk']) {
                    $_troop_info['captain_hero_pk'] = $_troop_info['staff_hero_pk'];
                    $_troop_info['captain_desc'] = $_troop_info['staff_desc'];

                    $query_param = Array($_troo_pk, $_troop_info['staff_hero_pk'], $_troop_info['staff_desc']);
                    $this->PgGame->query('UPDATE troop SET captain_hero_pk = $2, captain_desc = $3, staff_hero_pk = null, staff_desc = null WHERE troo_pk = $1', $query_param);

                    $_troop_info['staff_hero_pk'] = null;
                    $_troop_info['staff_desc'] = null;
                } else {
                    $remain_captain = false;
                }
            }
        } else if ($_type == 'def' && $_battle_type = 'valley') {
            if (!$_troop_info['captain_hero_pk'])
            {
                if ($_troop_info['director_hero_pk'])
                {
                    $_troop_info['captain_hero_pk'] = $_troop_info['director_hero_pk'];
                    $_troop_info['captain_desc'] = $_troop_info['director_desc'];

                    $query_param = Array($_troo_pk, $_troop_info['director_hero_pk'], $_troop_info['director_desc']);
                    $this->PgGame->query('UPDATE troop SET captain_hero_pk = $2, captain_desc = $3, director_hero_pk = null, director_desc = null WHERE troo_pk = $1', $query_param);

                    $_troop_info['director_hero_pk'] = null;
                    $_troop_info['director_desc'] = null;
                } else if ($_troop_info['staff_hero_pk']) {
                    $_troop_info['captain_hero_pk'] = $_troop_info['staff_hero_pk'];
                    $_troop_info['captain_desc'] = $_troop_info['staff_desc'];

                    $query_param = Array($_troo_pk, $_troop_info['staff_hero_pk'], $_troop_info['staff_desc']);
                    $this->PgGame->query('UPDATE troop SET captain_hero_pk = $2, captain_desc = $3, staff_hero_pk = null, staff_desc = null WHERE troo_pk = $1', $query_param);

                    $_troop_info['staff_hero_pk'] = null;
                    $_troop_info['staff_desc'] = null;
                } else {
                    $remain_captain = false;
                }
            }
        }

        return $remain_captain;*/
    }

    // 전투 시 참여한 경상을 입은 영웅을 전투 종료 후 부대에서 제외 처리
    function checkInjuryHero($_troo_pk, &$_row): void
    {
        $this->PgGame->query('SELECT status_cmd FROM my_hero WHERE hero_pk = $1', [$_row['captain_hero_pk']]);
        if ($this->PgGame->fetchOne() == 'T') {
            $this->PgGame->query('UPDATE troop SET captain_hero_pk = null, captain_desc = null WHERE troo_pk = $1', [$_troo_pk]);
            $_row['captain_hero_pk'] = null;
            $_row['captain_desc'] = null;
        }

        $this->PgGame->query('SELECT status_cmd FROM my_hero WHERE hero_pk = $1', [$_row['director_hero_pk']]);
        if ($this->PgGame->fetchOne() == 'T') {
            $this->PgGame->query('UPDATE troop SET director_hero_pk = null, director_desc = null WHERE troo_pk = $1', [$_troo_pk]);
            $_row['director_hero_pk'] = null;
            $_row['director_desc'] = null;
        }

        $this->PgGame->query('SELECT status_cmd FROM my_hero WHERE hero_pk = $1', [$_row['staff_hero_pk']]);
        if ($this->PgGame->fetchOne() == 'T') {
            $this->PgGame->query('UPDATE troop SET staff_hero_pk = null, staff_desc = null WHERE troo_pk = $1', [$_troo_pk]);
            $_row['staff_hero_pk'] = null;
            $_row['staff_desc'] = null;
        }
    }


    // 이벤트용 전투 포인트 처리
    function checkEventPoint($_src_lord_pk, $_dst_lord_pk): bool
    {
        if (!$_src_lord_pk || !$_dst_lord_pk) {
            return false;
        }

        // 공격측 군주 등급 알아오기
        $this->PgGame->query('SELECT level FROM lord WHERE lord_pk = $1', [$_src_lord_pk]);
        $_src_lord_level = $this->PgGame->fetchOne();

        // 방어측 군주 등급 알아오기
        $this->PgGame->query('SELECT level FROM lord WHERE lord_pk = $1', [$_dst_lord_pk]);
        $_dst_lord_level = $this->PgGame->fetchOne();

        // 둘 중 하나라도 5등급 이하라면 false
        if ($_src_lord_level < 5 || $_dst_lord_level < 5) {
            return false;
        }

        // 둘중 하나라도 7등급 이하라면 체크
        if ($_src_lord_level < 7 || $_dst_lord_level < 7) {
            // 5~6등급이 서로 같지 않으면 false
            if ($_src_lord_level != $_dst_lord_level) {
                return false;
            }
        }
        return true;
    }

    function setEventPoint($_type, $_incr_point, $_lord_pk): bool
    {
        if (!$_type) {
            Debug::debugMessage('ERROR', '이벤트 전투 포인트 획득 실패. type 없음;lord_pk['.$_lord_pk.'];event_point['.$incr_point.'];');
            return false;
        }

        if (!$_lord_pk) {
            Debug::debugMessage('ERROR', '이벤트 전투 포인트 획득 실패. lord_pk 없음;lord_pk['.$_lord_pk.'];event_point['.$incr_point.'];');
            return false;
        }

        $_incr_point = (!$_incr_point) ? 0 : $_incr_point;

        $this->PgGame->query('SELECT count(lord_pk) FROM my_event WHERE lord_pk = $1', [$_lord_pk]);
        $ret = $this->PgGame->fetchOne();

        if ($ret < 1) {
            $ret = $this->PgGame->query("INSERT INTO my_event (lord_pk, event_{$_type}_point) VALUES ($2, $1)", [$_incr_point, $_lord_pk]);
        } else {
            $ret = $this->PgGame->query("UPDATE my_event SET event_{$_type}_point = event_{$_type}_point + $1 WHERE lord_pk = $2", [$_incr_point, $_lord_pk]);
        }
        if (!$ret) {
            Debug::debugMessage('ERROR', '이벤트 전투 포인트 획득 실패.;lord_pk['.$row['src_lord_pk'].'];event_point['.$incr_point.'];');
            return false;
        }

        // 로그 기록
        // $this->classLog();
        // $this->Log->setEtc($_lord_pk, null, 'battle_event', 'lord_pk:' . $_lord_pk . ';type:' . $_type . ';incr_point:' . $_incr_point);
        return true;
    }

    function getEventPointTotalCount($_type): mixed
    {
        if ($_type == 5) {
            $sql = ' AND t2.level = 5';
        } elseif ($_type == 6) {
            $sql = ' AND t2.level = 6';
        } elseif ($_type == 7) {
            $sql = ' AND t2.level > 6';
        } else {
            $sql = ' AND t2.level > 4';
        }

        // 총 갯수 구하기
        $this->PgGame->query("SELECT count(t1.lord_pk) FROM my_event t1, lord t2 WHERE t1.lord_pk = t2.lord_pk AND (t1.event_att_point + t1.event_def_point) > 0{$sql}");
        return $this->PgGame->fetchOne();
    }

    // 이벤트 랭킹
    function getEventPointRanking($_total_count, $_page, $_type): array
    {
        $event_ranking = [];
        $max_list_count = 15; // 한페이지 랭킹 수

        $total_page = 0;
        $page_num = $_page;
        if ($_total_count > 0) {
            // 응답 - 총 페이지 수
            $total_page = (int)($_total_count / $max_list_count);
            $total_page += ($_total_count % $max_list_count > 0) ? 1 : 0;

            // 페이지 번호 확인
            if ($page_num < 1) {
                $page_num = 1;
            } else if ($page_num > $total_page) {
                $page_num = $total_page;
            }

            // 오프셋 구하기
            $offset_num = (($page_num - 1) * $max_list_count);

            if ($_type == 5) {
                $type_sql = ' AND t2.level = 5';
            } elseif ($_type == 6) {
                $type_sql = ' AND t2.level = 6';
            } elseif ($_type == 7) {
                $type_sql = ' AND t2.level > 6';
            } else {
                $type_sql = ' AND t2.level > 4';
            }

            $this->PgGame->query("SELECT t1.lord_pk, t2.lord_name, t2.level, t1.event_att_point,  t1.event_def_point, (t1.event_att_point + t1.event_def_point) as total_point
FROM my_event t1, lord t2
WHERE t1.lord_pk = t2.lord_pk AND (t1.event_att_point + t1.event_def_point) > 0{$type_sql}
ORDER BY total_point DESC, t1.event_att_point DESC, t1.event_def_point DESC LIMIT $1 OFFSET $2", [$max_list_count, $offset_num]);
            $this->PgGame->fetchAll();
            $event_ranking = $this->PgGame->rows;

            $rank = 1 + $offset_num;

            foreach ($event_ranking as $k => $v) {
                $event_ranking[$k]['rank'] = $rank;
                $rank++;
            }
        }

        return ['total_page' => $total_page, 'page' => $page_num, 'ranking' => $event_ranking];
    }

    // 이벤트 토벌령 발생 처리
    function setNpcEventSuppress($_lord_pk, $_level, $_posi_pk): bool
    {
        // 발생처리가 가능하면 연속 처리 검사
        $this->PgGame->query('SELECT event_supp_success FROM my_event WHERE lord_pk = $1', [$_lord_pk]);
        $event_success = $this->PgGame->fetchOne();

        // 이벤트 결과가 존재하지 않으면 my_event가 없는 것이므로 insert
        if (!$event_success) {
            $ret = $this->PgGame->query('INSERT INTO my_event (lord_pk) VALUES ($1)', [$_lord_pk]);
            if (!$ret) {
                return false;
            }
        }

        // 발생가능여부 검사
        $this->PgGame->query('SELECT t1.level, t2.event_supp_pk, t2.event_supp_level, date_part(\'epoch\', t2.event_suppress_dt)::integer as event_suppress_dt FROM lord t1, my_event t2 WHERE t1.lord_pk = $1 AND t1.lord_pk = t2.lord_pk', [$_lord_pk]);
        $this->PgGame->fetch();
        $last = $this->PgGame->row;

        // 군주레벨 제한
        if ($last['level'] < 2) {
            return false;
        }

        $midnight = mktime(0, 0, 0);

        if ($last['event_suppress_dt'] >= $midnight && $last['event_supp_pk']) {
            // already set the suppress data
            return false;
        }

        $this->PgGame->query('UPDATE my_event set event_supp_level = $2, event_supp_success = $3 WHERE lord_pk = $1', [$_lord_pk, $_level, 'N']);
        $last['event_supp_level'] = $_level;

        if ($last['event_supp_pk']) {
            $this->PgGame->query('UPDATE suppress_position SET status = $1 WHERE supp_pk = $2 AND status = $3', ['C', $last['event_supp_pk'], 'N']);

            global $_M, $NsGlobal;
            $NsGlobal->requireMasterData(['npc_troop', 'npc_hero']);

            $m_npc_troo =& $_M['NPC_TROO']['suppress'][$_level];
            $m_npc_hero =& $_M['NPC_HERO']['suppress'][$_level];

            // 영웅 섞기
            shuffle($m_npc_hero);

            // 병과 섞기
            $army_type = [];
            foreach ($m_npc_troo as $k => $v) {
                if ($k != 'move_time' && $v > 0) {
                    $army_type[] = $k;
                }
            }
            shuffle($army_type);

            // 집결지 좌표 수
            $target_cnt = 5;
            $supp_position = $this->getNpcSuppress($_lord_pk);
            $supp_posi_arr = [];
            foreach ($supp_position as $k => $v) {
                $supp_posi_arr[] = $k;
            }
            $disable_cells = [];

            for ($i = 0; $i < $target_cnt; $i++) {
                $z_idx = rand(1, 8);
                $z_adjust = rand(7, 14);

                $query_params = [$_posi_pk, $z_idx, $z_adjust];
                $this->PgGame->query('SELECT getsuppressdisablecell($1, $2, $3)', $query_params);
                $target_posi_pk = $this->PgGame->fetchOne();

                if (!$target_posi_pk || in_array($target_posi_pk, $disable_cells) || in_array($target_posi_pk, $supp_posi_arr)) {
                    $i--;
                } else {
                    $disable_cells[] = $target_posi_pk;
                }

                $loop_cnt++;

                if (count($disable_cells) > 4) {
                    break;
                }
            }

            // disable cell 못찾았을 경우에 개수가 달라짐.
            $target_cnt = COUNT($disable_cells);

            // suppress 입력
            $this->PgGame->query('INSERT INTO suppress (lord_pk, target_level, target_cnt) VALUES ($1, $2, $3)', [$_lord_pk, $last['event_supp_level'], $target_cnt]);

            $supp_pk = $this->PgGame->currSeq('suppress_supp_pk_seq');

            // suppress_position 입력
            $suppress_info = [];
            for ($i = 0; $i < $target_cnt; $i++) {
                $army_type_1 = null;
                $army_type_2 = null;
                $army_type_3 = null;

                if ($target_cnt == 3) {
                    $army_type_1 = array_pop($army_type);
                } else if ($target_cnt == 2) {
                    $army_type_1 = array_pop($army_type);
                    $army_type_2 = array_pop($army_type);
                } else { // 1
                    $army_type_1 = array_pop($army_type);
                    $army_type_2 = array_pop($army_type);
                    $army_type_3 = array_pop($army_type);
                }

                // 임시 땜빵
                if (!$army_type_1) {
                    $army_type_1 = 'worker';
                }

                $this->PgGame->query('INSERT INTO suppress_position (supp_pk, posi_pk, hero_pk, army_type_1, army_type_2, army_type_3) VALUES ($1, $2, $3, $4, $5, $6)', [$supp_pk, $disable_cells[$i], $m_npc_hero[$i], $army_type_1, $army_type_2, $army_type_3]);

                $suppress_info[$i]['posi_pk'] = $disable_cells[$i];
                $suppress_info[$i]['army_type'] = $army_type_1 . ';' . $army_type_2 . ';' . $army_type_3;
            }

            // my_event 갱신
            $this->PgGame->query('UPDATE my_event SET event_supp_pk = $1, event_suppress_dt = now(), event_supp_success = $3 WHERE lord_pk = $2', [$supp_pk, $_lord_pk, 'N']);

            // position 갱신
            $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE posi_pk = ANY($1)', ['{' . implode(',', $disable_cells) . '}']);

            // 외교서신
            $this->classLetter();

            $letter = [];
            $letter['type'] = 'S';
            // $letter['title'] = '['.$last['event_supp_level'].'회]황건적 집결지를 찾았습니다!!';
            $letter['title'] = '황건적에게 납치된 산타클로스를 구출하라!!';
            // $letter['content'] = '<div style="float:left;width:150px;height:158px;"><div class="stamp"></div></div><div style="float:left;width:395px;height:158px;"><div class="suppress_content">';

            $npcOffset = implode(', ', $disable_cells);

            $letter['content'] = <<<EOF
황건적 집결지에 산타클로스가 납치되었다는 첩보를 입수 하였습니다.

속히 군대를 소집하여 황건적 집결지를 파괴하고 산타클로스를 구출하여 크리스마스 카드를 획득하세요.

황건적 집결지 좌표 : {$npcOffset}
EOF;

            $this->Letter->sendLetter(EMPEROR_LORD_PK, [$_lord_pk], $letter, true, 'Y');

            //Log
            $this->classLog();
            $this->Log->setSuppress($_lord_pk, $_posi_pk, 'setNpcEventSuppress', $supp_pk, $last['event_supp_level'], $target_cnt, 0, null, $suppress_info[0]['posi_pk'], $suppress_info[1]['posi_pk'], $suppress_info[2]['posi_pk'], $suppress_info[0]['army_type'], $suppress_info[1]['army_type'], $suppress_info[2]['army_type']);

            return true;
        }
    }

    // 토벌령 Get
    function getNpcSuppressEvent($_lord_pk): false|array
    {
        // 이벤트 토벌령 조회
        $this->PgGame->query('SELECT t2.event_supp_pk, t2.event_supp_level, date_part(\'epoch\', t2.event_suppress_dt)::integer as event_suppress_dt FROM lord t1, my_event t2 WHERE t1.lord_pk = $1 AND t1.lord_pk = t2.lord_pk', [$_lord_pk]);
        $this->PgGame->fetch();
        $last = $this->PgGame->row;
        if (!$last['event_supp_pk']) {
            // cant not found the suppress data
            return false;
        }

        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['npc_troop']);

        // 기초정보 추출
        $this->PgGame->query('SELECT target_level FROM suppress WHERE supp_pk = $1', [$last['event_supp_pk']]);
        $target_level = $this->PgGame->fetchOne();

        if (!$target_level) {
            // cant not found the suppress data
            return false;
        }

        // 부대 기초 데이터 선택
        $m_npc_troo =& $_M['NPC_TROO']['assemble'][$target_level];

        // 상세정보 추출
        $this->PgGame->query('SELECT posi_pk, hero_pk FROM suppress_position WHERE supp_pk = $1 AND status = $2', [$last['event_supp_pk'], 'N']);

        $suppress_position = false;

        while ($this->PgGame->fetch()) {
            $r =& $this->PgGame->row;
            $suppress_position[$r['posi_pk']] = $r['hero_pk'];
        }

        return $suppress_position;
    }

    // 토벌령 Get to SQ
    function getNpcSuppressEventToSQ($_lord_pk): void
    {
        $this->Session->sqAppend('NPC_SUPP_EVENT', $this->getNpcSuppressEvent($_lord_pk));
    }

    function getTroopInfo($_posi_pk): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army']);
        $info = $this->getFightingSpirit($_posi_pk);

        // 총병력
        $ret['total_army'] = $info['armyPop']['population'];
        // 부대사기
        $ret['fightingSpirit'] = $info['fightingSpirit'];
        // 지원군
        $ally_army = 0;
        $z_arr = $this->getAllyCampArmy($_posi_pk);
        if ($z_arr) {
            if (is_array($_M['ARMY_C'])) {
                foreach ($_M['ARMY_C'] AS $k => $v) {
                    $ally_army += $z_arr[$k];
                }
            }
        }

        $ret['alli_army'] = $ally_army;

        $this->Session->sqAppend('PUSH', ['TROOP_INFO' => $ret], null, $this->Session->lord['lord_pk'], $_posi_pk);
    }

    // 7일이 지난 최근 목적지 삭제
    function favoriteInit($_lord_pk): true
    {
        $interval = FAVORITE_DELETE_PERIOD;
        $this->PgGame->query("DELETE FROM position_favorite WHERE lord_pk = $1 AND type = $2 AND regist_dt <= now() - interval '$interval second'", [$_lord_pk, 'R']);
        return true;
    }

    // 황건적 섬멸전 체크
    function checkRaidNpcTroop($_raid_troo_pk, $_lord_pk): bool
    {
        global $NsGlobal;

        // 이미 출병 중인 병력이 있는지 체크
        $this->PgGame->query('SELECT count(troo_pk) FROM troop WHERE raid_troo_pk = $1 AND src_lord_pk = $2', [$_raid_troo_pk, $_lord_pk]);
        $cnt = $this->PgGame->fetchOne();
        if ($cnt > 0) {
            $NsGlobal->setErrorMessage('하나의 요새에 하나의 부대만 출병 가능합니다.');
            return false;
        }

        // 그 외의 상황 체크
        $this->PgGame->query('SELECT
	raid_troo_pk, status, date_part(\'epoch\', end_dt)::integer as end_dt,
	(army_worker + army_infantry + army_pikeman + army_scout
+ army_spearman + army_armed_infantry + army_archer + army_horseman
+ army_armed_horseman + army_transporter + army_bowman
+ army_battering_ram + army_catapult + army_adv_catapult) AS remain_army,
(SELECT date_part(\'epoch\', last_up_dt)::integer + 1800 FROM raid_point WHERE lord_pk = $2 AND raid_troo_pk = $1) AS cooltime
FROM raid_troop WHERE raid_troo_pk = $1', [$_raid_troo_pk, $_lord_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;

        if (!$row || !$row['raid_troo_pk']) {
            $NsGlobal->setErrorMessage('황건적 요새의 정보를 찾을 수 없습니다.');
            return false;
        }

        if ($row['cooltime'] && ($row['cooltime'] - time()) > 0) {
            $NsGlobal->setErrorMessage('전투 쿨 타임이 남아 있습니다.');
            return false;
        }

        if ($row['status'] == 'C' || $row['remain_army'] < 1)
        {
            $NsGlobal->setErrorMessage('이미 섬멸된 황건적 요새입니다.');
            return false;
        }

        if (time() >= $row['end_dt']) {
            $NsGlobal->setErrorMessage('이미 공격 가능 시간이 종료된 요새입니다.');
            return false;
        }

        return true;
    }

    // 황건적 섬멸전 발생
    function setRaidTroop($_rate, $_lord_pk, $_target_level, $_log_type = ''): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['npc_hero', 'npc_troop']);

        // 군주 정보를 알아옴
        $this->PgGame->query('SELECT lord_pk, level FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $this->PgGame->fetch();
        $lord = $this->PgGame->row;

        $range_random_key = rand(1, 1000); // 천분율

        if ($range_random_key <= $_rate) { // 성공 확률
            $m_npc_hero =& $_M['NPC_HERO']['suppress'][$lord['level']];

            // 요새 레벨
            $target_level = $_target_level;

            // 영웅 섞기
            shuffle($m_npc_hero);

            // 병력 입력
            $army = [];
            $total_army = 0;
            foreach($_M['NPC_TROO']['annihiler'][$target_level] AS $k => $v) {
                if ($k != 'move_time') {
                    $army[$k] = $v;
                    $total_army = $total_army + $v; // 총 합
                }
            }

            $ret = $this->PgGame->query("INSERT INTO
	raid_troop
	(lord_pk, target_level, captain_hero_pk, director_hero_pk, staff_hero_pk, total_army,
	army_worker, army_infantry, army_pikeman, army_scout, army_spearman, army_armed_infantry,
	army_archer, army_horseman, army_armed_horseman, army_transporter, army_bowman,
	army_battering_ram, army_catapult, army_adv_catapult, end_dt)
VALUES
	($1, $2, $3, $4, $5, $6,
	$7, $8, $9, $10, $11, $12,
	$13, $14, $15, $16,	$17,
	$18, $19, $20, now() + interval '14400 second')", [$_lord_pk, $target_level, $m_npc_hero[0], $m_npc_hero[1], $m_npc_hero[2], $total_army,
                $army['worker'], $army['infantry'], $army['pikeman'], $army['scout'], $army['spearman'], $army['armed_infantry'],
                $army['archer'], $army['horseman'], $army['armed_horseman'], $army['transporter'], $army['bowman'],
                $army['battering_ram'], $army['catapult'], $army['adv_catapult']]);

            $raid_troo_pk = $this->PgGame->currSeq('raid_troop_raid_troo_pk_seq');

            if (!$ret) {
                return false;
            }

            // 섬멸전 발생 push 알림
            $this->classPush();

            // 나와 모든 동맹 군주에게
            $this->PgGame->query('SELECT lord_pk FROM alliance_member WHERE alli_pk = $1 AND type = $2', [$_lord_pk, 'Y']); // TODO 개인 동맹 정보를 일반 동맹으로 변경 필요. alli_pk
            $this->PgGame->fetchAll();
            $alli_arr = $this->PgGame->rows;
            foreach($alli_arr AS $v) {
                $ret = $this->Session->sqAppend('PUSH', ['RAID_WARNING' => true], null, $v['lord_pk']);
                $this->Push->send('raid', '', $v['lord_pk']);
            }

            // 푸시 및 아이콘 알림
            $this->Push->send('raid', '', $_lord_pk);
            $this->Session->sqAppend('PUSH', ['RAID_WARNING' => true], null, $_lord_pk);
            // 액션 메시지
            $this->Session->sqAppend('PUSH', ['ACTION_DLG' => ['type' =>'raid']], null, $_lord_pk);

            // 로그 기록
            $this->classLog();
            $this->Log->setRaidBattle($_lord_pk, null, 'discovery', json_encode(['raid_troo_pk' => $raid_troo_pk, 'rate' => $_rate, 'random_key' => $range_random_key, 'target_level' => $target_level, 'discovery_type' => $_log_type]));

            return true;
        } else {
            return false;
        }
    }

    // 섬멸전 리스트 총 갯수
    function getRaidListCount($_lord_pk, $_type = 'progress')
    {
        if ($_type == 'conclusion') {
            $add_sql = ' AND date_part(\'epoch\', (t1.end_dt)::integer <= date_part(\'epoch\', now())::integer AND date_part(\'epoch\', now())::integer - date_part(\'epoch\', t1.end_dt)::integer < 259200 OR t1.status = \'C\')';
        } else {
            $add_sql = ' AND date_part(\'epoch\', t1.end_dt)::integer > date_part(\'epoch\', now())::integer AND t1.status != \'C\'';
        }

        // TODO 개인 동맹 정보를 일반 동맹으로 변경 필요. alli_pk
        $this->PgGame->query("SELECT count(t1.raid_troo_pk) FROM raid_troop t1, lord t2
WHERE t1.lord_pk = t2.lord_pk AND (t1.lord_pk = $1 OR t1.lord_pk IN (SELECT lord_pk FROM alliance_member WHERE alli_pk = $1 AND type = $2)) {$add_sql}", [$_lord_pk, 'Y']);
        return $this->PgGame->fetchOne();
    }

    // 섬멸전 리스트
    function getRaidList($_lord_pk, $page = 1, $order = 'rare', $order_type = 'desc', $list_num = null, $_type = 'progress'): array
    {
        if ($_type == 'conclusion') {
            $add_sql = ' AND date_part(\'epoch\', (t1.end_dt)::integer <= date_part(\'epoch\', now())::integer AND date_part(\'epoch\', now())::integer - date_part(\'epoch\', t1.end_dt)::integer < 259200 OR t1.status = \'C\')';
        } else{
            $add_sql = ' AND date_part(\'epoch\', t1.end_dt)::integer > date_part(\'epoch\', now())::integer AND t1.status != \'C\'';
        }

        $page = (INT)$page;
        $page = ($page < 1 || !is_int($page)) ? 1 : $page;
        $order = preg_replace('/[^\w]/', '', strtolower($order));

        $list_num = ($list_num == null) ? RAID_LIST_PAGE_NUM : $list_num;
        $offset_start = ($page - 1) * $list_num;
        $limit = $list_num;

        if ($order == 'regist_dt') {
            $order_by = "t1.regist_dt {$order_type}";
        } else if ($order == 'remain_army') {
            $order_by = "remain_army {$order_type}";
        } else if ($order == 'target_level') {
            $order_by = "t1.target_level {$order_type}";
        } else {
            $order_by = "t1.last_up_dt {$order_type}";
        }

        if ($_type != 'conclusion') {
            // 진행 중 탭에서는 자기 목록을 최우선
            $order_by = 'rank, '.$order_by;
        }

        $this->PgGame->query("SELECT
	t1.raid_troo_pk, t1.lord_pk, t2.lord_name,
	t1.target_level, t1.total_army,
	(t1.army_worker + t1.army_infantry + t1.army_pikeman + t1.army_scout
+ t1.army_spearman + t1.army_armed_infantry + t1.army_archer + t1.army_horseman
+ t1.army_armed_horseman + t1.army_transporter + t1.army_bowman
+ t1.army_battering_ram + t1.army_catapult + t1.army_adv_catapult) AS remain_army,
	t1.status, date_part('epoch', t1.regist_dt)::integer as regist_dt, date_part('epoch', t1.end_dt)::integer as end_dt,
	t1.captain_hero_pk, t1.director_hero_pk, t1.staff_hero_pk,
	CASE WHEN t1.lord_pk = $1 THEN 1
		 ELSE 2
	END AS rank,
	(SELECT date_part('epoch', last_up_dt)::integer + 1800 FROM raid_point WHERE lord_pk = $1 AND raid_troo_pk = t1.raid_troo_pk) AS cooltime,
	(SELECT t4.attack_count FROM raid_point t4 WHERE t4.raid_troo_pk = t1.raid_troo_pk AND t4.lord_pk = $1) AS attack_count
FROM
	raid_troop t1,
	lord t2
WHERE
	t1.lord_pk = t2.lord_pk AND
	(t1.lord_pk = $1 OR t1.lord_pk IN (SELECT lord_pk FROM alliance_member WHERE alli_pk = $1 AND type = $2))
	{$add_sql}
ORDER BY
	{$order_by}
LIMIT
	{$limit}
OFFSET
	{$offset_start}", [$_lord_pk, 'Y']); // TODO lord_pk => alli_pk 개인동맹 제거로 인해 변경 필요.
        $this->PgGame->FetchAll();
        $ret = $this->PgGame->rows;

        foreach($ret AS $k => $v) {
            $ret[$k]['captain_m_hero_pk'] = $this->getHeroMasterDataPK($v['captain_hero_pk']);
            $ret[$k]['director_m_hero_pk'] = $this->getHeroMasterDataPK($v['director_hero_pk']);
            $ret[$k]['staff_m_hero_pk'] = $this->getHeroMasterDataPK($v['staff_hero_pk']);

            // 해당 요새의 랭킹 및 보상 정보 받아오기
            $ranking = $this->getRewardRanking($v['raid_troo_pk'], $v['lord_pk']);
            $ret[$k]['ranking'] = $ranking;

            // troo_pk가 존재한다면 공격 중
            $ret[$k]['attack'] = 'N';

            // 공격 기록이 없으면 0
            if (!$v['attack_count']) {
                $ret[$k]['attack_count'] = 0;
            }

            // 해당 요새로 출병 중인 자신의 부대가 있는지 체크
            $this->PgGame->query('SELECT troo_pk FROM troop WHERE raid_troo_pk = $1 AND src_lord_pk = $2', [$v['raid_troo_pk'], $v['lord_pk']]);
            $t = $this->PgGame->FetchOne();
            if ($t) {
                $ret[$k]['attack'] = 'Y';
            }

            // 해당 요새에 요청된 도움이 있는지 체크
            $ret[$k]['yn_request'] = false;
            if ($v['lord_pk'] != $this->Session->lord['lord_pk']) {
                if ($this->checkRaidRequest($v['raid_troo_pk'], $v['lord_pk'], $this->Session->lord['lord_pk'])) {
                    $t = $this->getRaidRequest($v['raid_troo_pk'], $v['lord_pk'], $this->Session->lord['lord_pk']);
                    // status가 P라면 진행 중
                    if ($t['status'] == 'P') {
                        $ret[$k]['yn_request'] = true;
                    }
                }
            }
        }
        return (is_array($ret)) ? $ret : [];
    }

    // 섬멸전 NPC 부대 정보
    function getRaidNpcTroop($_raid_troo_pk): bool|array
    {
        $this->PgGame->query('SELECT
	raid_troo_pk, target_level ,army_worker, army_infantry, army_pikeman, army_scout
	,army_spearman, army_armed_infantry, army_archer, army_horseman ,army_armed_horseman, army_transporter, army_bowman
	,army_battering_ram, army_catapult, army_adv_catapult ,captain_hero_pk, director_hero_pk, staff_hero_pk
FROM raid_troop WHERE raid_troo_pk = $1', [$_raid_troo_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        if (! $row) {
            return false;
        }
        $row['fort_trap'] = 0;
        $row['fort_abatis'] = 0;
        $row['fort_tower'] = 0;
        $row['level'] = $row['target_level'];
        $row['wall_open'] = true;
        $row['wall_level'] = $row['target_level'];
        $row['captain_desc'] = $this->getHeroDesc($row['captain_hero_pk']);
        $row['director_desc'] = $this->getHeroDesc($row['director_hero_pk']);
        $row['staff_desc'] = $this->getHeroDesc($row['staff_hero_pk']);
        return $row;
    }

    // 섬멸전 클리어 시
    function clearRaidTroop($_raid_troo_pk, $_lord_pk): bool
    {
        // 이미 클리어 처리된 요새인지 체크
        $this->PgGame->query('SELECT t1.status, t1.lord_pk, t2.lord_name, t1.target_level FROM raid_troop t1, lord t2 WHERE t1.raid_troo_pk = $1 AND t1.lord_pk = t2.lord_pk', [$_raid_troo_pk]);
        $this->PgGame->fetch();
        $raid = $this->PgGame->row;

        if (!$raid || $raid['status'] == 'C')
            return false;

        // 클리어 처리
        $this->PgGame->query('UPDATE raid_troop SET status = $1, last_up_dt = now() WHERE raid_troo_pk = $2', ['C', $_raid_troo_pk]);

        // 랭크에 따른 보상 지급
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['npc_ann_reward', 'item']);

        // 포인트가 1점 이상인 상위 10명을 뽑아 옴
        $this->PgGame->query('SELECT t1.lord_pk, t2.lord_name FROM raid_point t1, lord t2 WHERE raid_troo_pk = $1 AND attack_point > 0 AND t1.lord_pk = t2.lord_pk ORDER BY t1.attack_point DESC, t1.last_up_dt LIMIT 10', [$_raid_troo_pk]);
        $this->PgGame->FetchAll();
        $ret = $this->PgGame->rows;

        // 섬멸자 군주명 알아오기
        $lord_name = '-';
        if ($_lord_pk != $raid['lord_pk']) {
            $this->PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$_lord_pk]);
            $lord_name = $this->PgGame->fetchOne();
        } else {
            $lord_name = $raid['lord_name'];  // 발견자와 섬멸자 pk가 같으면
        }

        $reward_lord_pk_arr = []; // 보상 처리에 필요한 군주 정보
        $reward_lord_pk_arr['discovery'] = ['lord_pk' => $raid['lord_pk'], 'lord_name' => $raid['lord_name']]; // 발견자
        $reward_lord_pk_arr['annihiler'] = ['lord_pk' => $_lord_pk, 'lord_name' => $lord_name]; // 섬멸자
        $reward_lord_pk_arr['helper1'] = false;
        $reward_lord_pk_arr['helper2'] = false;

        // 순위에 따른 처리
        foreach($ret AS $k => $v) {
            if ($reward_lord_pk_arr['discovery'] && $reward_lord_pk_arr['discovery']['lord_pk'] == $v['lord_pk']) {
                continue; // 발견자는 기여도에서 제외.
            } else if ($reward_lord_pk_arr['annihiler'] && $reward_lord_pk_arr['annihiler']['lord_pk'] == $v['lord_pk']) {
                continue; // 섬멸자는 기여도에서 제외
            }
            // 발견자와 섬멸자를 제외하고 기여도가 높은 순으로 입력
            if (!$reward_lord_pk_arr['helper1']) {
                $reward_lord_pk_arr['helper1'] = ['lord_pk' => $v['lord_pk'], 'lord_name' => $v['lord_name']];
            }
            else if (!$reward_lord_pk_arr['helper2']) {
                $reward_lord_pk_arr['helper2'] = ['lord_pk' => $v['lord_pk'], 'lord_name' => $v['lord_name']];
            }
        }

        // 섬멸자 업데이트
        $this->PgGame->query('UPDATE raid_point SET yn_annihiler = $3 WHERE raid_troo_pk = $1 AND lord_pk = $2', [$_raid_troo_pk, $_lord_pk, 'Y']);

        $this->classItem();
        $this->classLetter();

        $m_reward = $_M['NPC_ANN_REWARD'][$raid['target_level']]; // 보상 마스터 데이터

        // 외교서신
        $letter = [];
        $letter['type'] = 'S';
        $letter['title'] = '황건적 요새 섬멸에 성공하였습니다.';
        $letter['content'] = '축하드립니다!!<br /><br />'.$raid['lord_name'].'님께서 발견하신 Lv.'.$raid['target_level'].'황건적 요새를 섬멸하여 아래와 같이 보상이 지급되었습니다.<br /><br />';

        $add_content = '<table class="qbwtbl_Common2"><thead><tr><th>구분</th><th>보상</th><th>군주</th><tr></thead><tbody>';
        $table_title = ['discovery' => '발견', 'annihiler' => '섬멸', 'helper1' => '기여1', 'helper2' => '기여2'];

        // 보상 지급 및 외교서신 작성
        foreach($reward_lord_pk_arr AS $k => $v) {
            $add_content.= '<tr>';
            // 군주pk가 없다면 패스
            if (!$v) {
                $add_content.= '<td>'.$table_title[$k].'</td><td>-</td><td>-</td>';
            } else {
                $add_content.= '<td>'.$table_title[$k].'</td><td>'.$_M['ITEM'][$m_reward[$k]['reward_item_pk']]['title'].'</td><td>'.$v['lord_name'].'</td>';
                // 아이템 지급
                $this->Item->BuyItem($v['lord_pk'], $m_reward[$k]['reward_item_pk'], $m_reward[$k]['cnt'], 'raid_reward');
            }
            $add_content.= '</tr>';
        }
        $letter['content'].= $add_content.'</tbody></table>';

        // 외교서신을 보내기 위해 다시 한번
        foreach($reward_lord_pk_arr AS $k => $v) {
            // 발견자와 섬멸자가 같다면 섬멸자 외교서신은 보내지 않음
            if ($k == 'annihiler' && $raid['lord_pk'] == $v['lord_pk']) {
                continue;
            }
            if($v['lord_pk']) {
                $this->Letter->sendLetter(ADMIN_LORD_PK, [$v['lord_pk']], $letter, true, 'Y');
            }
        }

        // 로그 기록
        $this->classLog();
        $this->Log->setRaidBattle($_lord_pk, null, 'clear', json_encode(Array('raid_troo_pk' => $_raid_troo_pk, 'target_level' => $raid['target_level'], 'reward_lord_pk_arr' => $reward_lord_pk_arr)));

        // 레벨에 따른 후속 요새 추가
        /*if ($raid['target_level'] < 6) {
            $this->setRaidTroop(1000, $raid['lord_pk'], ($raid['target_level']+1), 'level_up'); // 레벨업 시 100% 확률 발생
        }*/

        return true;
    }

    // 섬멸전 랭크 입력
    function setRaidRanking($_raid_troo_pk, $_lord_pk, $_point, $_att_success): true
    {
        // 공격 실패 일때 포인트가 100보다 적으면 포인트를 0 점으로
        if (!$_att_success && (INT)$_point < 100) {
            $_point = 0;

            // 포인트가 0점이면 외교서신 보내 줌 - TODO 텍스트 처리...
            $this->classLetter();
            $letter = [];
            $letter['type'] = 'S';
            $letter['title'] = '황건적요새 전투 참여';
            $letter['content'] = '적을 공격했지만 적군에 비해 너무나 부족한 아군은 적에게 유효한 타격을 입히지 못했습니다.<br /><br />합계 기준으로 100명 이상의 황건적을 죽인 군주만 보상을 받을 수 있으므로 해당 황건요새의 기록을 확인하시고 부족하다면 재공격을 하셔야 할 수도 있습니다.<br /><br />공격 병력은 소속 영지로 회군 중입니다.';

            $this->Letter->sendLetter(ADMIN_LORD_PK, [$_lord_pk], $letter, true, 'Y');
        }

        // 랭크 데이터가 존재하는지 확인
        $this->PgGame->query('SELECT raid_troo_pk FROM raid_point WHERE raid_troo_pk = $1 AND lord_pk = $2', [$_raid_troo_pk, $_lord_pk]);
        $this->PgGame->fetch();
        $ret = $this->PgGame->row;

        if (!$ret['raid_troo_pk']) { // 존재하지 않으면 insert
            $ret = $this->PgGame->query('INSERT INTO raid_point (raid_troo_pk, lord_pk, attack_point, attack_count, regist_dt, last_up_dt) VALUES ($1, $2, $3, 1, now(), now())', [$_raid_troo_pk, $_lord_pk, $_point]);
        } else { // 존재하면 update
            $ret = $this->PgGame->query('UPDATE raid_point SET attack_point = attack_point + $3, attack_count = attack_count + 1, last_up_dt = now() WHERE raid_troo_pk = $1 AND lord_pk = $2', [$_raid_troo_pk, $_lord_pk, $_point]);
        }

        // 로그 기록
        $this->classLog();
        $this->Log->setRaidBattle($_lord_pk, null, 'set_point', json_encode(['raid_troo_pk' => $_raid_troo_pk, 'point' => $_point, 'att_success' => $_att_success]));

        return true;
    }

    // 섬멸전 랭크 리스트
    function getRaidRanking($_raid_troo_pk): array
    {
        $this->PgGame->query('SELECT Rank() over (Partition by t1.raid_troo_pk Order by t1.attack_point DESC) as rank, t2.lord_name, t1.attack_point, t1.attack_count
FROM raid_point t1, lord t2 WHERE t1.raid_troo_pk = $1 AND t1.lord_pk = t2.lord_pk
ORDER BY t1.attack_point DESC, t1.last_up_dt LIMIT 10', [$_raid_troo_pk]);
        $this->PgGame->FetchAll();
        return $this->PgGame->rows ?? [];
    }

    // 섬멸전 보상 랭킹 알아오기
    function getRewardRanking($_raid_troo_pk, $_lord_pk): array
    {
        // 섬멸전 정보 가져오기
        $this->PgGame->query('SELECT t1.status, t1.lord_pk, t2.lord_name, t1.target_level FROM raid_troop t1, lord t2 WHERE t1.raid_troo_pk = $1 AND t1.lord_pk = t2.lord_pk', [$_raid_troo_pk]);
        $this->PgGame->fetch();
        $raid = $this->PgGame->row;

        // 포인트가 1점 이상인 상위 10명을 뽑아 옴
        $this->PgGame->query('SELECT t1.lord_pk, t2.lord_name, t1.yn_annihiler FROM raid_point t1, lord t2
WHERE raid_troo_pk = $1 AND attack_point > 0 AND t1.lord_pk = t2.lord_pk ORDER BY t1.attack_point DESC, t1.last_up_dt LIMIT 10', [$_raid_troo_pk]);
        $this->PgGame->FetchAll();
        $ret = $this->PgGame->rows;

        $reward_lord_pk_arr = []; // 보상 처리에 필요한 군주 정보
        $reward_lord_pk_arr['discovery'] = ['lord_pk' => $raid['lord_pk'], 'lord_name' => $raid['lord_name']]; // 발견자
        $reward_lord_pk_arr['annihiler'] = false; // 섬멸자
        $reward_lord_pk_arr['helper1'] = false;
        $reward_lord_pk_arr['helper2'] = false;

        // 순위에 따른 처리
        foreach($ret AS $v) {
            if ($v['yn_annihiler'] == 'Y') {
                $reward_lord_pk_arr['annihiler'] = ['lord_pk' => $v['lord_pk'], 'lord_name' => $v['lord_name']];
            }

            if ($reward_lord_pk_arr['discovery'] && $reward_lord_pk_arr['discovery']['lord_pk'] == $v['lord_pk']) {
                continue; // 발견자는 기여도에서 제외.
            }
            else if ($reward_lord_pk_arr['annihiler'] && $reward_lord_pk_arr['annihiler']['lord_pk'] == $v['lord_pk']) {
                continue; // 섬멸자는 기여도에서 제외
            }

            // 발견자와 섬멸자를 제외하고 기여도가 높은 순으로 입력
            if (!$reward_lord_pk_arr['helper1']) {
                $reward_lord_pk_arr['helper1'] = ['lord_pk' => $v['lord_pk'], 'lord_name' => $v['lord_name']];
            } else if (!$reward_lord_pk_arr['helper2']) {
                $reward_lord_pk_arr['helper2'] = ['lord_pk' => $v['lord_pk'], 'lord_name' => $v['lord_name']];
            }
        }

        return $reward_lord_pk_arr;
    }

    // 섬멸전 도움 요청
    function setRaidRequest($_raid_troo_pk, $_from_lord_pk, $_to_lord_pk, $_type = 'band'): bool
    {
        global $NsGlobal;
        // 이미 보낸 군주인가 체크
        $ret = $this->checkRaidRequest($_raid_troo_pk, $_from_lord_pk, $_to_lord_pk);
        if ($ret) {
            $NsGlobal->setErrorMessage('이미 도움을 요청한 군주입니다.');
            return false;
        }

        $ret = $this->PgGame->query('INSERT INTO raid_request (raid_troo_pk, from_lord_pk, to_lord_pk, type, status) VALUES ($1, $2, $3, $4, $5)', [$_raid_troo_pk, $_from_lord_pk, $_to_lord_pk, $_type, 'P']);

        if (!$ret) {
            // 에러 남기는 곳
            $NsGlobal->setErrorMessage('오류가 발생했습니다.');
            return false;
        }

        // 로그 기록
        $this->classLog();
        $this->Log->setRaidBattle($_from_lord_pk, null, 'request', json_encode(['raid_troo_pk' => $_raid_troo_pk, 'from_lord_pk' => $_from_lord_pk, 'to_lord_pk' => $_to_lord_pk, 'type' => $_type, 'status' => 'P']));

        return true;
    }

    // 섬멸전 도움 요청 체크
    function checkRaidRequest($_raid_troo_pk, $_from_lord_pk, $_to_lord_pk): bool
    {
        if (!$_raid_troo_pk || !$_from_lord_pk || !$_to_lord_pk) {
            return false;
        }

        // 이미 보낸 군주인가 체크
        $this->PgGame->query('SELECT raid_troo_pk FROM raid_request WHERE raid_troo_pk = $1 AND from_lord_pk = $2 AND to_lord_pk = $3', [$_raid_troo_pk, $_from_lord_pk, $_to_lord_pk]);
        $raid_troo_pk = $this->PgGame->fetchOne();

        return (bool)$raid_troo_pk;
    }

    // 섬멸전 도움 요청 정보 가져오기
    function getRaidRequest($_raid_troo_pk, $_from_lord_pk, $_to_lord_pk): bool|array
    {
        // 이미 보낸 군주인가 체크
        $this->PgGame->query('SELECT raid_troo_pk, status, regist_dt, last_up_dt FROM raid_request WHERE raid_troo_pk = $1 AND from_lord_pk = $2 AND to_lord_pk = $3', [$_raid_troo_pk, $_from_lord_pk, $_to_lord_pk]);
        $this->PgGame->fetch();
        return $this->PgGame->row;

    }

    // 섬멸전 요청에 의한 아이템 지원 받기
    function getRaidRequestItem($_raid_troo_pk, $_from_lord_pk, $_to_lord_pk): bool
    {
        global $NsGlobal;
        $ret = $this->getRaidRequest($_raid_troo_pk, $_from_lord_pk, $_to_lord_pk);
        if ($ret['status'] != 'P') {
            // 에러 남기는 곳
            $NsGlobal->setErrorMessage('이미 지원받은 아이템입니다.');
            return false;
        }

        $m_item_pk = 500732;
        $item_cnt = 1;

        // 아이템 지급
        $this->classItem();
        $ret = $this->Item->BuyItem($_to_lord_pk, $m_item_pk, $item_cnt, 'raid_request');
        if (!$ret) {
            // 에러 남기는 곳
            $NsGlobal->setErrorMessage('오류가 발생했습니다.');
            return false;
        }

        // 지급 후 상태 업데이트
        $ret = $this->PgGame->query('UPDATE raid_request SET status = $4, last_up_dt = now() WHERE raid_troo_pk = $1 AND from_lord_pk = $2 AND to_lord_pk = $3', [$_raid_troo_pk, $_from_lord_pk, $_to_lord_pk, 'E']);
        if (!$ret) {
            // 에러 남기는 곳
            $NsGlobal->setErrorMessage('오류가 발생했습니다.');
            return false;
        }

        // 로그 기록
        $this->classLog();
        $this->Log->setRaidBattle($_from_lord_pk, null, 'request_item', json_encode(['raid_troo_pk' => $_raid_troo_pk, 'from_lord_pk' => $_from_lord_pk, 'to_lord_pk' => $_to_lord_pk, 'm_item_pk' => $m_item_pk, 'item_cnt' => $item_cnt, 'status' => 'E']));

        return true;
    }

    // 모든 영지의 이동 중인 부대 정보
    function getMoveTroopAll($_lord_pk = null): array
    {
        if (! $_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }

        $this->PgGame->query('SELECT troo_pk, src_lord_pk, dst_lord_pk, src_posi_pk, dst_posi_pk, src_alli_pk, dst_alli_pk, status, cmd_type,
 from_position, to_position, distance, triptime,
 round_food, round_gold, presence_food, hour_food, fighting_spirit, use_item_pk, captain_hero_pk,
 director_hero_pk, staff_hero_pk, reso_gold, reso_food, reso_horse, reso_lumber, reso_iron,
 army_worker, army_infantry, army_pikeman, army_scout, army_spearman, army_armed_infantry,
 army_archer, army_horseman, army_armed_horseman, army_transporter, army_bowman,
 army_battering_ram, army_catapult, army_adv_catapult, src_time_pk, dst_time_pk,
 date_part(\'epoch\', now())::integer - date_part(\'epoch\', start_dt)::integer AS elapsed_time,
 captain_desc, director_desc, staff_desc, withdrawal_dt, withdrawal_auto, troop_type, troop_quest_npc_attack,
 raid_troo_pk FROM troop WHERE (src_lord_pk = $1 OR dst_lord_pk = $2) AND status IN ($3, $4, $5) AND distance > 0', [$_lord_pk, $_lord_pk, 'M', 'R', 'W']);
        $this->PgGame->fetchAll();
        return $this->PgGame->rows;
    }

    /*public function setTroopMove ($_troo_pk): void
    {
        $this->classRedis();
        $this->PgGame->query('SELECT t.troo_pk, t.start_dt, t.src_posi_pk, t.dst_posi_pk, t.status, t.cmd_type, t.arrival_dt,
                        date_part(\'epoch\', t.arrival_dt) as arrival_dt_ut, t.triptime, t.camptime, t.move_time, t.captain_hero_pk, t.director_hero_pk, t.staff_hero_pk,
	                    t.src_lord_pk, src.lord_name as src_name, t.src_alli_pk, src.level as src_level,
	                    t.dst_lord_pk, dst.lord_name as dst_name, t.dst_alli_pk, dst.level as dst_level
                    FROM troop as t	inner join lord as src on t.src_lord_pk = src.lord_pk inner join lord as dst on t.dst_lord_pk = dst.lord_pk
                    WHERE troo_pk = $1', [$_troo_pk]);
        $this->PgGame->fetch();

        // $this->Redis->hSet('world:troop:move', $_troo_pk, json_encode($this->PgGame->row));
    }*/

    public function checkOccupationPoint ($_posi_pk)
    {

    }

    public function getMoveTroops ($_from_ally = true): void
    {
        $this->PgGame->query('SELECT t.troo_pk, t.start_dt, t.src_posi_pk, t.dst_posi_pk, t.status, t.cmd_type, t.arrival_dt,
                        date_part(\'epoch\', t.arrival_dt) as arrival_dt_ut, t.triptime, t.camptime, t.move_time, t.captain_hero_pk, t.director_hero_pk, t.staff_hero_pk,
	                    t.src_lord_pk, src.lord_name as src_name, t.src_alli_pk, src.level as src_level,
	                    t.dst_lord_pk, dst.lord_name as dst_name, t.dst_alli_pk, dst.level as dst_level
                    FROM troop as t	inner join lord as src on t.src_lord_pk = src.lord_pk inner join lord as dst on t.dst_lord_pk = dst.lord_pk
                    WHERE src.lord_pk = $1 OR dst.lord_pk = $1 OR src_alli_pk = $2 OR dst_alli_pk = $2', [$this->Session->lord['lord_pk'], $this->Session->lord['alli_pk']]);
        $this->PgGame->fetchAll();
        $list = [];
        foreach ($this->PgGame->rows as $row) {
            // 나를 향한 정찰은 제외하기
            if ($row['dst_lord_pk'] === $this->Session->lord['lord_pk'] && $row['cmd_type'] == 'S') {
                continue;
            }
            $list[$row['troo_pk']] = $row;
        }
        $this->Session->sqAppend('TROOP', $list);
        if ($_from_ally) {
            $this->appendAllyMoveTroop($this->Session->lord['lord_pk'], $this->Session->lord['alli_pk'], $list);
        }
    }

    public function getMoveTroop ($_troo_pk): void
    {
        $this->PgGame->query('SELECT t.troo_pk, t.start_dt, t.src_posi_pk, t.dst_posi_pk, t.status, t.cmd_type, t.arrival_dt,
                        date_part(\'epoch\', t.arrival_dt) as arrival_dt_ut, t.triptime, t.camptime, t.move_time, t.captain_hero_pk, t.director_hero_pk, t.staff_hero_pk,
	                    t.src_lord_pk, src.lord_name as src_name, t.src_alli_pk, src.level as src_level,
	                    t.dst_lord_pk, dst.lord_name as dst_name, t.dst_alli_pk, dst.level as dst_level
                    FROM troop as t	inner join lord as src on t.src_lord_pk = src.lord_pk inner join lord as dst on t.dst_lord_pk = dst.lord_pk
                    WHERE t.troo_pk = $1', [$_troo_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        $list = [];
        $list[$row['troo_pk']] = $row;

        $this->Session->sqAppend('TROOP', $list, null, $row['src_lord_pk']);
        $this->appendAllyMoveTroop($row['src_lord_pk'], $row['src_alli_pk'], $list);
        if ($row['cmd_type'] != 'S' && $row['src_alli_pk'] != $row['dst_alli_pk']) {
            // 정찰이 아니라면 방어자에게도 Push
            $this->Session->sqAppend('TROOP', $list, null, $row['dst_lord_pk']);
            $this->appendAllyMoveTroop($row['dst_lord_pk'], $row['dst_alli_pk'], $list);
        }
    }

    public function removeMoveTroop ($_troo_pk): void
    {
        $this->PgGame->query('SELECT t.troo_pk, t.start_dt, t.src_posi_pk, t.dst_posi_pk, t.status, t.cmd_type, t.arrival_dt,
                        date_part(\'epoch\', t.arrival_dt) as arrival_dt_ut, t.triptime, t.camptime, t.move_time, t.captain_hero_pk, t.director_hero_pk, t.staff_hero_pk,
	                    t.src_lord_pk, src.lord_name as src_name, t.src_alli_pk, src.level as src_level,
	                    t.dst_lord_pk, dst.lord_name as dst_name, t.dst_alli_pk, dst.level as dst_level
                    FROM troop as t	inner join lord as src on t.src_lord_pk = src.lord_pk inner join lord as dst on t.dst_lord_pk = dst.lord_pk
                    WHERE t.troo_pk = $1', [$_troo_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        $this->Session->sqAppend('TROOP', [$_troo_pk => null], null, $row['src_lord_pk']);
        $this->appendAllyMoveTroop($row['src_lord_pk'], $row['src_alli_pk'], [$_troo_pk => null]);
        if ($row['cmd_type'] != 'S' && $row['src_alli_pk'] != $row['dst_alli_pk']) {
            // 정찰이 아니라면 방어자에게도 Push
            $this->Session->sqAppend('TROOP', [$_troo_pk => null], null, $row['dst_lord_pk']);
            $this->appendAllyMoveTroop($row['dst_lord_pk'], $row['dst_alli_pk'], [$_troo_pk => null]);
        }
    }

    public function appendAllyMoveTroop ($_lord_pk, $_alli_pk, $_list): void
    {
        if (! $_alli_pk || $_alli_pk < 1) {
            return;
        }
        $this->PgGame->query('SELECT lord_pk FROM alliance_member WHERE lord_pk != $1 AND alli_pk = $2', [$_lord_pk, $_alli_pk]);
        $this->PgGame->fetchAll();
        foreach ($this->PgGame->rows as $row) {
            $this->Session->sqAppend('TROOP', $_list, null, $row['lord_pk']);
        }
    }
}