<?php

class HeroCombination
{
    public Session $Session;
    public Pg $PgGame;
    protected Hero $Hero;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    function classHero(): void
    {
        if (! isset($this->Hero)) {
            $this->Hero = new Hero($this->Session, $this->PgGame);
        }
    }

    // hero_pk를 구분자로 합친 문자열을 다시 배열로 분리함
    public function splitSelectedCard($_selected_hero_str, $delim = ':'): array|false
    {
        if (!is_string($_selected_hero_str) || strlen($_selected_hero_str) < 1) {
            global $NsGlobal;
            $NsGlobal->setErrorMessage('Error Occurred. [18001]'); // 조합에 실패
            return false;
        }
        return explode($delim, $_selected_hero_str);
    }

    // 주어진 hero_pk 들에게서 레벨, 레어 등의 정보를 얻어낸다.
    // false를 반환하는 경우는 올바르지 않은 hero_pk를 주었을때이다. (진행할 필요 없음)
    public function getCombinationHeroInfo($hero_pk_arr): false|array
    {
        if (!is_array($hero_pk_arr) || !$this->Session->lord['lord_pk']) {
            return false;
        }

        $hero_arr_str = implode(',', $hero_pk_arr);
        $this->PgGame->query("SELECT a.hero_pk, a.m_hero_pk, b.yn_lord, b.status, b.status_cmd, b.m_offi_pk
FROM hero a, my_hero b WHERE a.hero_pk = b.hero_pk AND b.lord_pk = $1 AND b.hero_pk in ({$hero_arr_str})", [$this->Session->lord['lord_pk']]);
        if ($this->PgGame->fetchAll() != count($hero_pk_arr)) {
            return false;
        }

        $r = [];
        for($i = 0, $cnt = count($this->PgGame->rows); $i < $cnt; $i++) {
            $t = $this->getHeroInfo($this->PgGame->rows[$i]);
            if (!$t) {
                return false;
            } else {
                $r[$this->PgGame->rows[$i]['hero_pk']] = $t;
            }
        }
        return $r;
    }

    public function getCombinationHeroInfoWithLoad($hero_pk_arr): false|array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero']);

        if (!is_array($hero_pk_arr)) {
            $NsGlobal->setErrorMessage('Error Occurred. [18002]'); // 조합에 실패하였습니다. 다시 시도해 주시기 바랍니다.
            return false;
        }

        if (in_array($this->Session->lord['lord_hero_pk'], $hero_pk_arr)) {
            $NsGlobal->setErrorMessage('Error Occurred. [18003]'); // 군주 카드는 조합 대상으로 선택할 수 없습니다.
            return false;
        }

        $hero_pk_arr[] = $this->Session->lord['lord_hero_pk'];
        $t = $this->getCombinationHeroInfo($hero_pk_arr);
        if ($t === false) {
            $NsGlobal->setErrorMessage('Error Occurred. [18004]'); // 올바르지 않은 조합 대상 영웅이 선택되었습니다.
            return false;
        }

        $r = ['lord' => [], 'mate' => []];
        foreach($t as $hero_pk => $hero_info) {
            $this->PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$hero_pk]);
            $m_hero_pk = $this->PgGame->fetchOne();
            if ($_M['HERO'][$m_hero_pk]['over_type'] == 'Y') {
                $NsGlobal->setErrorMessage('Error Occurred. [18005]'); // 오버랭크 영웅은 조합이 불가능 합니다.
                return false;
            }
            if ($_M['HERO_BASE'][$_M['HERO'][$m_hero_pk]['m_hero_base_pk']]['yn_modifier'] == 'Y') {
                $NsGlobal->setErrorMessage('Error Occurred. [18006]'); // 진노 영웅은 조합이 불가능 합니다.
                return false;
            }

            if ($this->Session->lord['lord_hero_pk'] == $hero_pk && $hero_info['yn_lord'] == 'Y') {
                $r['lord'][$hero_pk] = $hero_info;
            } else {
                if ($hero_info['yn_lord'] == 'Y') {
                    $NsGlobal->setErrorMessage('Error Occurred. [18007]'); // 군주 카드는 조합 대상 카드로 사용할 수 없습니다.
                    return false;
                } else if ($hero_info['status'] != 'G') {
                    $NsGlobal->setErrorMessage('Error Occurred. [18008]'); // 조합 대상 카드는 반드시 등용 대기 중 이어야합니다.
                    return false;
                }
                $r['mate'][$hero_pk] = $hero_info;
            }
        }

        if (count($r['mate']) != 4) {
            $NsGlobal->setErrorMessage('Error Occurred. [18009]'); // 조합 대상 영웅은 반드시 4명의 영웅이 선택되어야합니다.
            return false;
        } else if (count($r['lord']) != 1) {
            $NsGlobal->setErrorMessage('Error Occurred. [18010]'); // 조합을 위해 군주 정보를 얻는 도중 문제가 발생하였습니다.
            return false;
        }
        return $r;
    }

    // 여기서 얻어야될 최소의 정보들
    // m_hero.level, m_hero_base.rare, m_hero_base.forces, m_hero_base.type
    public function getHeroInfo($row): false|array
    {
        if (!is_array($row) || !array_key_exists('hero_pk', $row) || !array_key_exists('m_hero_pk', $row) || !is_numeric($row['hero_pk'])) {
            return false;
        }

        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero', 'hero_base']);
        if (!isset($_M['HERO'][$row['m_hero_pk']])) {
            return false;
        }
        if (!isset($_M['HERO_BASE'][$_M['HERO'][$row['m_hero_pk']]['m_hero_base_pk']])) {
            return false;
        }

        $m_hero = $_M['HERO'][$row['m_hero_pk']];
        $m_hero_base = $_M['HERO_BASE'][$_M['HERO'][$row['m_hero_pk']]['m_hero_base_pk']];

        return [
            'hero_pk' => $row['hero_pk'],
            'm_hero_pk' => $row['m_hero_pk'],
            'm_hero_base_pk' => $m_hero_base['m_hero_base_pk'],
            'm_offi_pk' => $row['m_offi_pk'],
            'level' => $m_hero['level'],
            'rare' => $m_hero_base['rare_type'],
            'forces' => $m_hero_base['forces'],
            'type' => $m_hero_base['type'],
            'yn_lord' => $row['yn_lord'],
            'status' => $row['status'],
            'status_cmd' => $row['status_cmd']
        ];
    }

    public function isSameField($fieldName, $arr): bool
    {
        if (!is_array($arr) || count($arr) < 2) {
            return false;
        }

        $tmp = null;
        foreach($arr as $v) {
            if (!is_array($v) || !array_key_exists($fieldName, $v)) {
                return false;
            }
            if (!$tmp) {
                $tmp = $v[$fieldName];
            } else {
                if ($tmp != $v[$fieldName]) {
                    return false;
                }
            }
        }
        return true;
    }

    // 같은 레벨인지?
    public function isSameLevel($hero_info_arr): bool
    {
        return $this->isSameField('level', $hero_info_arr);
    }

    // 같은 레어도인지?
    public function isSameRare($hero_info_arr): bool
    {
        return $this->isSameField('rare', $hero_info_arr);
    }

    // 같은 진영인지?
    public function isSameForce($hero_info_arr): bool
    {
        return $this->isSameField('forces', $hero_info_arr);
    }

    // 같은 유형인지?
    public function isSameType($hero_info_arr): bool
    {
        return $this->isSameField('type', $hero_info_arr);
    }

    // 같은 이름의 영웅인지?
    public function isSameHeroBase($hero_info_arr): bool
    {
        return $this->isSameField('m_hero_base_pk', $hero_info_arr);
    }

    // 군주 카드와 같은 세력인지?
    public function isSameForceWithLord($hero_info_arr, $lord_hero_info_arr): bool
    {
        return $this->isSameForce(array_merge($hero_info_arr, $lord_hero_info_arr));
    }

    public function getFirstHeroField($fieldName, $hero_info_arr): mixed
    {
        if ($this->isSameField($fieldName, $hero_info_arr)) {
            $r = reset($hero_info_arr);
            return $r[$fieldName];
        }
        return null;
    }

    // 모두 동일한 레벨일때 그 레벨 가져오기
    public function getFirstHeroLevel($hero_info_arr): mixed
    {
        return $this->getFirstHeroField('level', $hero_info_arr);
    }

    // 모두 동일한 레어일때 그 레어 가져오기
    public function getFirstHeroRare($hero_info_arr): mixed
    {
        return $this->getFirstHeroField('rare', $hero_info_arr);
    }

    // 모두 동일한 세력일때 그 세력 가져오기
    public function getFirstHeroForce($hero_info_arr): mixed
    {
        return $this->getFirstHeroField('forces', $hero_info_arr);
    }

    // 모두 동일한 영웅 이름일때 그 이름 가져오기
    public function getFirstHeroBase($hero_info_arr): mixed
    {
        return $this->getFirstHeroField('m_hero_base_pk', $hero_info_arr);
    }


    // 영웅들의 평균 레어 값 가져오기
    public function getHeroRareAverage($hero_info_arr): float|false
    {
        if (!is_array($hero_info_arr) || count($hero_info_arr) < 2) {
            return false;
        }
        $total = 0;
        foreach($hero_info_arr as $v) {
            if (!is_array($v) || !array_key_exists('rare', $v) || !is_numeric($v['rare'])) {
                return false;
            }
            $total += intval($v['rare']);
        }
        return round($total/count($hero_info_arr), 2);
    }

    // 영웅들의 레벨 스코어 값 가져오기
    public function getHeroLevelScore($hero_info_arr): float|object|false|int
    {
        if (!is_array($hero_info_arr) || count($hero_info_arr) < 2) {
            return false;
        }

        $score = 0;
        foreach($hero_info_arr as $v) {
            if (!is_array($v) || !array_key_exists('level', $v) || !is_numeric($v['level']) || !array_key_exists('rare', $v) || !is_numeric($v['rare'])) {
                return false;
            }
            // 개별 조합 점수 = 레어도² + (레어도 x 레벨)
            $score += pow(intval($v['rare']), 2) + (intval($v['rare']) * intval($v['level']));
        }
        return $score;
    }

    public function getApplyCombinationAttr($hero_info_arr): false|array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero', 'hero_base', 'hero_combination_rare', 'hero_combination_level']);

        $r = [];
        $avr_rare = $this->getHeroRareAverage($hero_info_arr); // 레어 평균
        $rate_arr = $_M['HERO_COMBINATION_RARE']["{$avr_rare}"];
        $range_prev = 1;
        $range_random_key = rand(1, 1000); // 천분율
        $range_select = null;
        foreach($rate_arr AS $rate) {
            if ($rate['rate'] == 0) {
                continue;
            }
            $next = $range_prev + $rate['rate'];
            if ($range_random_key >= $range_prev && $range_random_key <= $next) {
                $range_select = $rate['rare_type'];
                break;
            }
            $range_prev = $next;
        }

        $r['rare'] = $range_select; // 선택된 레어도

        $lv_score = $this->getHeroLevelScore($hero_info_arr); // 레벨 스코어

        $score_arr = $_M['HERO_COMBINATION_LEVEL']["{$r['rare']}"];

        $range_key = $lv_score;
        $range_select = 0;

        foreach($score_arr AS $score) {
            // 레벨 10까지 가면 모든 수보다 높으므로 10레벨을 지급함
            if ($range_key < $score['score'] || $score['level'] == 10) {
                $range_select = $score['level'];
                break;
            }
        }

        $r['level'] = $range_select; // 선택된 레벨

        // $r['yn_new_gacha'] = $this->applyNewGachaponCheck($hero_info_arr); // TODO 흠? 왜 주석?

        return (!$r['rare'] || !$r['level']) ? false : $r;
    }

    public function applyLevelTable($table_type, $avr_level): false|int
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_combi_rate']);
        $table = $_M['HERO_COMBI_RATE']['LEVEL'];

        $plus_level = 0;
        if (isset($table[$table_type])) {
            $table = $table[$table_type];
            $v = rand(1, 100000);
            $prev = 0;
            ksort($table);
            foreach($table as $plus => $now) {
                if ($v <= ($now + $prev)) {
                    $plus_level += intval($plus);
                    break;
                } else {
                    $prev = ($now + $prev);
                }
            }
        } else {
            $NsGlobal->setErrorMessage('Error Occurred. [18011]'); // 일반 조합 확률 테이블을 찾을 수 없습니다.
            return false;
        }
        return ($avr_level + $plus_level > 10) ? 10 : ($avr_level + $plus_level);
    }

    public function applyNewGachaponCheck($hero_info_arr): string
    {
        // 조합하려는 영웅이 모두 신규영웅인지 확인 (m.hero_base.yn_new_gacha == 'Y'). 2012.11.13
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_base']);

        $NewGachaponCheckCnt = 0;
        foreach($hero_info_arr as $v) {
            if($_M['HERO_BASE'][$v['m_hero_base_pk']]['yn_new_gacha'] == 'Y') {
                $NewGachaponCheckCnt++;
            }
        }
        return $NewGachaponCheckCnt == 4 ? 'Y' : 'N';
    }

    public function applyRareTable($table_type, $avr_rare): int
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_combi_rate']);
        $table = $_M['HERO_COMBI_RATE']['RARE'];

        $plus_rare = 0;
        if (isset($table[$table_type][$avr_rare])) {
            if (rand(1, 100000) <= $table[$table_type][$avr_rare]) {
                $plus_rare += 1;
            }
        }
        return ($avr_rare + $plus_rare > 7) ? 7 : $avr_rare + $plus_rare;
    }

    public function isLordCard($m_hero_base_pk): bool
    {
        return in_array($m_hero_base_pk, [120000, 120001, 120002, 120003, 120004]);
    }

    public function calcScore($star_card_info, $mate_card_info_arr): false|int
    {
        $card_total_score = $this->getTotalCardScore($mate_card_info_arr);
        if (!$card_total_score) {
            return false;
        }
        $force_total_score = $this->getTotalForce($star_card_info, $mate_card_info_arr);
        $star_card_hero_pk = key($star_card_info);
        if ($star_card_hero_pk == $this->Session->lord['lord_hero_pk']) {
            // 군주 특수 조합
            return (($force_total_score * 2) + $card_total_score);
        } else {
            // 일반 특수 조합
            $type_total_score = $this->getTotalTypeScore($star_card_info, $mate_card_info_arr);
            return ($force_total_score + $type_total_score + $card_total_score);
        }
    }

    public function getForceScore($force_star, $force_mate): false|int
    {
        global $_M;

        $force_star = strtoupper($force_star);
        $force_mate = strtoupper($force_mate);

        if(!array_key_exists($force_star, $_M['FORCE_RELATION'])) {
            return false;
        }
        if(!array_key_exists($force_mate, $_M['FORCE_RELATION'])) {
            return false;
        }

        if($force_star == $force_mate) {
            return HERO_COMBI_FORCE_RELATION_SAME;
        } else if(in_array($force_star, $_M['FORCE_RELATION'][$force_mate]['GOOD'])) {
            return HERO_COMBI_FORCE_RELATION_GOOD;
        } else if(in_array($force_star, $_M['FORCE_RELATION'][$force_mate]['BAD'])) {
            return HERO_COMBI_FORCE_RELATION_BAD;
        } else {
            return HERO_COMBI_FORCE_RELATION_OTHER;
        }
    }

    public function getTotalForce($star_card_info, $mate_card_info_arr): false|int
    {
        $total = 0;
        $star_card_info = reset($star_card_info);
        foreach($mate_card_info_arr as $mate_card_info){
            $score = $this->getForceScore($star_card_info['forces'], $mate_card_info['forces']);
            if($score === false) {
                global $NsGlobal;
                $NsGlobal->setErrorMessage('Error Occurred. [18012]'); // 카드 점수를 가져올 수 없습니다.
                return false;
            }
            $total += $score;
        }
        return $total;
    }

    public function getNeedGold($avr_rare, $avr_level): float|false|int
    {
        // (평균 레어도 * 1000) + (평균레벨 * 500)
        if($avr_rare < 1 || $avr_rare > 7 || $avr_level < 1 || $avr_level > 10) {
            return false;
        }
        return ($avr_rare * 1000) + ($avr_level * 500);
    }

    public function getCardScore($_rare, $_level): mixed
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_combi_score']);

        $table = $_M['HERO_COMBI_SCORE']['CARD'];

        if(isset($table[$_rare]) || isset($table[$_rare][$_level])) {
            return $table[$_rare][$_level];
        }
        else {
            $NsGlobal->setErrorMessage("Error Occurred. [18013]"); // 카드 점수를 가져올 수 없습니다.
            return false;
        }
    }

    public function getTotalCardScore($hero_info_arr): mixed
    {
        $total = 0;
        foreach ($hero_info_arr as $hero_info){
            $score = $this->getCardScore($hero_info['rare'], $hero_info['level']);
            if($score === false) {
                return false;
            }
            $total += $score;
        }
        return $total;
    }

    public function getCombinationHeroInfoWithStar($mate_hero_pk_arr, $star_hero_pk): false|array
    {
        global $_M, $NsGlobal;
        if (!is_array($mate_hero_pk_arr) || in_array($star_hero_pk, $mate_hero_pk_arr)) {
            $NsGlobal->setErrorMessage('Error Occurred. [18014]'); // 올바르지 않은 조합 대상 영웅이 선택되었습니다.
            return false;
        }
        $NsGlobal->requireMasterData(['hero']);
        $mate_hero_pk_arr[] = $star_hero_pk;
        $t = $this->getCombinationHeroInfo($mate_hero_pk_arr);
        if ($t === false) {
            $NsGlobal->setErrorMessage('Error Occurred. [18015]'); // 올바르지 않은 조합 대상 영웅이 선택되었습니다.
            return false;
        }

        $r = ['star' => [], 'mate' => []];
        foreach($t as $hero_pk => $hero_info) {
            $this->PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$hero_pk]);
            $m_hero_pk = $this->PgGame->fetchOne();
            if ($_M['HERO'][$m_hero_pk]['over_type'] == 'Y') {
                $NsGlobal->setErrorMessage('Error Occurred. [18016]'); // 오버랭크 영웅은 조합이 불가능 합니다.
                return false;
            }
            /*if ($_M['HERO_BASE'][$_M['HERO'][$m_hero_pk]['m_hero_base_pk']]['yn_modifier'] == 'Y')
            {
                $NsGlobal->setErrorMessage('진노 영웅은 조합이 불가능 합니다.');
                return false;
            }*/

            if ($star_hero_pk == $hero_pk) {
                if ($hero_info['status'] != 'G' && ($hero_info['status'] == 'A' && $hero_info['status_cmd'] != 'I')) {
                    $NsGlobal->setErrorMessage('Error Occurred. [18017]'); // 메인 영웅은 등용 대기 또는 대기 상태여야합니다.
                    return false;
                } else if ($hero_info['level'] == 10) {
                    $NsGlobal->setErrorMessage('Error Occurred. [18018]'); // 영웅 레벨이 10인 경우 메인 영웅으로 사용할 수 없습니다.
                    return false;
                }
                $r['star'][$hero_pk] = $hero_info;
            } else {
                if ($hero_info['yn_lord'] == 'Y') {
                    $NsGlobal->setErrorMessage('Error Occurred. [18019]'); // 군주 카드는 조합 대상 카드로 사용할 수 없습니다.
                    return false;
                } else if ($hero_info['status'] != 'G') {
                    $NsGlobal->setErrorMessage('Error Occurred. [18020]'); // 조합 대상 카드는 반드시 등용 대기 중 이어야합니다.
                    return false;
                }
                $r['mate'][$hero_pk] = $hero_info;
            }
        }

        if (count($r['mate']) != 4) {
            $NsGlobal->setErrorMessage('Error Occurred. [18021]'); // 조합 대상 영웅은 반드시 4명의 영웅이 선택되어야합니다.
            return false;
        } else if (count($r['star']) != 1) {
            $NsGlobal->setErrorMessage('Error Occurred. [18022]'); // 조합을 위해 영웅 정보를 얻는 도중 문제가 발생하였습니다.
            return false;
        }
        return $r;
    }

    public function getTotalTypeScore($star_card_info, $mate_card_info_arr): int
    {
        $total = 0;
        $star_card_info = reset($star_card_info);
        foreach($mate_card_info_arr as $mate_card_info) {
            if ($star_card_info['type'] == $mate_card_info['type'])
                $total += 6;
        }
        return $total;
    }

    public function getCommonSuccessScore($_rare, $_level)
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_combi_score']);

        $table = $_M['HERO_COMBI_SCORE']['COMMON'];

        if (!isset($table[$_rare]) || !isset($table[$_rare][$_level])) {
            $NsGlobal->setErrorMessage("Error Occurred. [18023]"); // 성공 포인트를 가져올 수 없습니다.
            return false;
        }
        else {
            return $table[$_rare][$_level];
        }
    }

    public function getLordSuccessScore($_level)
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_combi_score']);

        ksort($_M['HERO_COMBI_SCORE']['SPECIAL']);
        $table = $_M['HERO_COMBI_SCORE']['SPECIAL'];
        $target = 7;

        if (!isset($table[$target]) || !isset($table[$target][$_level])) {
            $NsGlobal->setErrorMessage('Error Occurred. [18024]'); // "성공 포인트를 가져올 수 없습니다.".var_export($table, true)
            return false;
        } else {
            return $table[$target][$_level];
        }
    }

    public function isSpecialCombinationSuccess($star_hero_pk, $mate_hero_pk_arr): false|array
    {
        $hero_pk_arr = $this->splitSelectedCard($mate_hero_pk_arr);
        if (!$hero_pk_arr) {
            return false;
        }
        $r = $this->getCombinationHeroInfoWithStar($hero_pk_arr, $star_hero_pk);
        if (!$r) {
            return false;
        }
        $star_hero_info = reset($r['star']);
        $score = $this->calcScore($r['star'], $r['mate']);
        if ($star_hero_info['yn_lord'] == 'Y' && $star_hero_info['hero_pk'] == $this->Session->lord['lord_hero_pk']) {
            // 군주가 메인
            $success_score = $this->getLordSuccessScore($star_hero_info['level']);
            if (!$success_score) {
                return false;
            }
            $spc_combi_type = 'lord';
        } else {
            // 군주가 메인이 아님
            $success_score = $this->getCommonSuccessScore($star_hero_info['rare'], $star_hero_info['level']);
            if (!$success_score) {
                return false;
            }
            $spc_combi_type = 'common';
        }

        return [
            'is_success' => $score >= $success_score,
            'score' => $score,
            'success_score' => $success_score,
            'combi_type' => $spc_combi_type,
            'star_hero' => $star_hero_info,
            'mate_hero_arr' => $r['mate']
        ];
    }

    public function getNextLevel($m_hero_pk): mixed
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero', 'hero_base']);

        if (!isset($_M['HERO'][$m_hero_pk]) || !isset($_M['HERO_BASE'][$_M['HERO'][$m_hero_pk]['m_hero_base_pk']])) {
            $NsGlobal->setErrorMessage('Error Occurred. [18025]'); // 발급하기 위한 영웅 정보를 찾을 수 없습니다.
            return false;
        }
        $m_hero = $_M['HERO'][$m_hero_pk];
        $r = false;

        $t = reset($_M['HERO']);
        do {
            if ($t['m_hero_base_pk'] == $m_hero['m_hero_base_pk'] && $t['level'] == ($m_hero['level'] + 1)) {
                $r = $t;
            }
        } while(($t = next($_M['HERO'])) !== false);

        if ($r === false) {
            $NsGlobal->setErrorMessage('Error Occurred. [18026]'); // 발급하기 위한 영웅 정보를 찾을 수 없습니다.
            return false;
        }

        return $r;
    }

    public function doHeroLevelUp($hero_info): bool
    {
        global $NsGlobal;
        // 필요한 것 // hero_pk , m_hero_pk
        if (!isset($hero_info['hero_pk']) || !isset($hero_info['m_hero_pk'])) {
            $NsGlobal->setErrorMessage('Error Occurred. [18027]'); // 올바르지 않은 영웅 정보입니다.
            return false;
        }

        $next_level = $this->getNextLevel($hero_info['m_hero_pk']);
        if (!$next_level) {
            $NsGlobal->setErrorMessage('Error Occurred. [18028]'); // 조합이 불가능합니다.
            return false;
        }

        $this->PgGame->query('UPDATE hero SET m_hero_pk = $1, level = $2, leadership_basic = $3, mil_force_basic = $4, intellect_basic = $5,
                politics_basic = $6, charm_basic = $7 WHERE hero_pk = $8', [$next_level['m_hero_pk'], $next_level['level'], $next_level['leadership'], $next_level['mil_force'],
            $next_level['intellect'], $next_level['politics'], $next_level['charm'], $hero_info['hero_pk']]);

        if ($this->PgGame->getAffectedRows() != 1) {
            $NsGlobal->setErrorMessage('Error Occurred. [18029]'); // 영웅 정보를 업데이트하는 도중 문제가 발생하였습니다.
            return false;
        } else {
            return true;
        }
    }

    function getCollectionCombinationLevel($_acquired_type, $_hero_info_arr): int
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_collection_combi_acquired_level']);

        // 평균레벨
        $avg_level  = 0;
        foreach($_hero_info_arr AS $v) {
            $avg_level += $v['level'];
        }

        $avg_level = floor ($avg_level / COUNT($_hero_info_arr));
        $avg_level = max($avg_level, 1);
        $avg_level = min($avg_level, 10);

        $range_arr = $_M['HERO_COLL_COMB_ACQU_LEVE'][$_acquired_type][$avg_level];
        $range_prev = 1;
        $range_select = null;

        $range_random_key = rand(1,1000000); // 백만

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
        return (INT)$range_select;
    }

    // 신규 특수 조합 (모바일) 14.02.25
    function getSpecialCombinationStarHero($_star_hero_pk): bool|array
    {
        if (!$_star_hero_pk) {
            return false;
        }
        $ret = $this->PgGame->query('SELECT t1.hero_pk, t1.status_cmd, t1.leadership, t1.mil_force, t1.intellect, t1.politics, t1.charm, t1.m_offi_pk, t1.yn_lord, t2.m_hero_pk, t2.level, t2.rare_type,
       t2.level, t2.hero_exp, t2.special_combi_cnt, t3.acquire_exp, t3.need_exp, t4.forces, t4.m_hero_base_pk
FROM my_hero AS t1, hero AS t2, m_hero AS t3, m_hero_base AS t4
WHERE t1.hero_pk = t2.hero_pk AND t2.m_hero_pk = t3.m_hero_pk AND t3.m_hero_base_pk = t4.m_hero_base_pk AND t1.lord_pk = $2 AND t2.hero_pk = $1', [$_star_hero_pk, $this->Session->lord['lord_pk']]);
        if (!$ret) {
            return false;
        }
        $this->PgGame->fetch();
        return $this->PgGame->row;
    }

    function getSpecialCombinationMaterialHero($_hero_pk_arr): false|array
    {
        if (!$_hero_pk_arr) {
            return false;
        }

        $_hero_pk_str = implode(',', $_hero_pk_arr);
        $ret = $this->PgGame->query("SELECT
	t1.hero_pk, t1.status_cmd, t1.leadership, t1.mil_force, t1.intellect, t1.politics, t1.charm,
	t2.m_hero_pk, t2.level, t2.rare_type, t2.level,
 	t2.hero_exp, t2.special_combi_cnt, t3.acquire_exp, t3.need_exp, t4.forces, t4.m_hero_base_pk
FROM my_hero AS t1, hero AS t2, m_hero AS t3, m_hero_base AS t4
WHERE t1.hero_pk = t2.hero_pk AND t2.m_hero_pk = t3.m_hero_pk AND t3.m_hero_base_pk = t4.m_hero_base_pk AND
    t1.lord_pk = $1 AND t2.hero_pk IN ({$_hero_pk_str})", [$this->Session->lord['lord_pk']]);
        if (!$ret) {
            return false;
        }

        $this->PgGame->fetchAll();
        return $this->PgGame->rows;
    }

    function getSpecialCombinationTotalAcquireExp($_star_hero, $_mate_hero_arr): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_exp']);

        $total_exp = 0;
        foreach($_mate_hero_arr AS $v) {
            $x = match ($this->getSpecialCombinationRelationship($_star_hero['forces'], $v['forces'])) {
                'E' => 0.9,
                'F' => 2.0, // 1.1
                default => 1.0
            };
            $x = ($_star_hero['m_hero_base_pk'] == $v['m_hero_base_pk']) ? 3.0 : $x;
            $total_exp = $total_exp + floor($_M['HERO_EXP'][$v['rare_type']][$v['level']]['acquire_exp'] * $x);
        }
        // 재료 경험치
        $material_exp = $total_exp;

        // 1.5배 보너스 경험치 획득 여부 결정
        $yn_bonus = 'N';
        $bonus_prob = $this->getBonusExperience($_star_hero['rare_type'], count($_mate_hero_arr));

        // 보너스 확률
        if ($bonus_prob > 0) {
            $bonus_chance = rand(1, 10000);
            if ($bonus_chance <= $bonus_prob) {
                $total_exp = $total_exp * 1.5;
                $yn_bonus = 'Y';
            }
        }

        $total_exp = floor($total_exp);
        $material_exp = floor($material_exp);
        return [
            'total_exp' => $total_exp, // 총경험치
            'material_exp' => $material_exp, // 재료경험치
            'bonus_exp' => $total_exp - $material_exp, // 보너스 경험치
            'yn_bonus' => $yn_bonus, // 보너스 경험치 여부
        ];
    }

    function getSpecialCombinationRelationship($_my, $_other): string
    {
        // 우호 : F 적대 : E 없음 : N, default = NN
        return match ($_my) {
            'UB', 'WS', 'SK' => match ($_other) {
                'SK', 'WS', 'UB' => 'F',
                'DT', 'PC', 'JJ' => 'E',
                default => 'N',
            },
            'JJ' => match ($_other) {
                'UB', 'SK', 'WS', 'PC' => 'E',
                'JJ', 'DT' => 'F',
                default => 'N',
            },
            'DT' => match ($_other) {
                'UB', 'SK', 'WS' => 'E',
                'JJ', 'DT', 'PC' => 'F',
                default => 'N',
            },
            'PC' => match ($_other) {
                'UB', 'JJ', 'SK', 'WS' => 'E',
                default => 'F',
            },
            'NN' => match ($_other) {
                'UB', 'JJ', 'SK', 'WS', 'DT' => 'N',
                default => 'F',
            },
            default => 'N',
        };
    }

    function getSpecialCombinationInfo($_level, $_rare_type): false|array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['need_resource']);
        $data = $_M['NEED_RESO']['combination'][$_rare_type][$_level];
        if (! $data) {
            return false;
        }
        $result = [];
        if (isset($data['need_item']) && $data['need_item'] != 'none') {
            $item = explode(':', $data['need_item']);
            $result['m_item_pk'] = $item[0];
            $result['cnt'] = $item[1];
        }
        if ($data['need_gold'] > 0) {
            $result['gold'] = $data['need_gold'];
        }
        return $result;
    }

    // 메인 영웅 레어도에 따른  보너스 경험치 획득 확률
    function getBonusExperience($_rare_type, $_mate_hero_cnt): int
    {
        // 확률은 10000분율
        return match ($_rare_type) {
            1 => match ($_mate_hero_cnt) {
                1 => 100,
                2 => 420,
                3 => 740,
                4 => 1100,
                5 => 1400,
                6 => 1700,
                7 => 2000,
                8 => 2400,
                9 => 2700,
                10 => 3000,
                default => 0,
            },
            2 => match ($_mate_hero_cnt) {
                2 => 100,
                3 => 440,
                4 => 780,
                5 => 1100,
                6 => 1500,
                7 => 1800,
                8 => 2100,
                9 => 2500,
                10 => 2800,
                default => 0,
            },
            3 => match ($_mate_hero_cnt) {
                3 => 100,
                4 => 460,
                5 => 780,
                6 => 1100,
                7 => 1500,
                8 => 1900,
                9 => 2200,
                10 => 2600,
                default => 0,
            },
            4 => match ($_mate_hero_cnt) {
                4 => 100,
                5 => 450,
                6 => 800,
                7 => 1200,
                8 => 1500,
                9 => 1900,
                10 => 2200,
                default => 0,
            },
            5 => match ($_mate_hero_cnt) {
                5 => 100,
                6 => 440,
                7 => 780,
                8 => 1100,
                9 => 1500,
                10 => 1800,
                default => 0,
            },
            6 => match ($_mate_hero_cnt) {
                6 => 100,
                7 => 430,
                8 => 750,
                9 => 1100,
                10 => 1400,
                default => 0,
            },
            7 => match ($_mate_hero_cnt) {
                7 => 100,
                8 => 400,
                9 => 700,
                10 => 1000,
                default => 0,
            },
            default => 0,
        };
    }

    // 특수 조합 진행
    function doSpecialCombination($_star_hero, $_mate_hero_arr, $_yn_incapacity = false): false|array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero', 'hero_exp']);

        if (!isset($_M['HERO'][$_star_hero['m_hero_pk']])) {
            return false;
        }

        $prev_exp = (INT)$_star_hero['hero_exp']; // 조합 전 경험치;
        $total_acqu_exp = $this->getSpecialCombinationTotalAcquireExp($_star_hero, $_mate_hero_arr); // 조합시 상승하는 경험치

        $rise_exp = $total_acqu_exp['total_exp'];

        // 레벨업에 필요한 정보를 정리하여 뽑아옴
        $star_m_hero_arr = [];
        $star_m_hero_arr[0] = false;

        foreach($_M['HERO'] AS $v) {
            if ($v['m_hero_base_pk'] == $_star_hero['m_hero_base_pk']) {
                $star_m_hero_arr[$v['level']] = $v;
            }
        }

        if (!is_array($star_m_hero_arr)) {
            return false;
        }

        // 정리한 데이터를 기준으로 다음 레벨 및 경험치 구해오기
        $next_exp = $rise_exp + $prev_exp; // 조합 전 경험치와 조합 재료의 경험치의 합
        $m_hero_pk = $_star_hero['m_hero_pk'];

        $main_rare = $_star_hero['yn_lord'] == 'Y' ? '0' : $_star_hero['rare_type'];

        $m_hero_exp = [];
        for($i = 1; $i < count($star_m_hero_arr); $i++) {
            $m_hero_exp = $_M['HERO_EXP'][$main_rare][$i];
            $d = $star_m_hero_arr[$i];
            if ($_star_hero['level'] <= $i) {
                $m_hero_pk = $d['m_hero_pk'];
                if ($next_exp < $m_hero_exp['need_exp']) {
                    break;
                }
                // 총 경험치에서 현재 레벨의 필요 경험치를 뺌
                $next_exp = $next_exp - $m_hero_exp['need_exp'];
            }
        }
        $hero_info = $_M['HERO'][$m_hero_pk];
        $total_acqu_exp['accu_exp'] = $next_exp; // 누적경험치
        $total_acqu_exp['next_exp'] = $m_hero_exp['need_exp'] - $next_exp; // 다음 레벨까지 필요한 경험치

        // 무능화된 영웅이라면
        if ($_yn_incapacity) {
            // 모든 기본 능력치를 1로
            $hero_info['leadership'] = 1;
            $hero_info['mil_force'] = 1;
            $hero_info['intellect'] = 1;
            $hero_info['politics'] = 1;
            $hero_info['charm'] = 1;
        }

        $ret = $this->PgGame->query('UPDATE hero SET m_hero_pk = $1, level = $2, leadership_basic = $3, mil_force_basic = $4, intellect_basic = $5,
                politics_basic = $6, charm_basic = $7, hero_exp = $9, special_combi_cnt = special_combi_cnt + 1 WHERE  hero_pk = $8', [
            $hero_info['m_hero_pk'],
            $hero_info['level'],
            $hero_info['leadership'],
            $hero_info['mil_force'],
            $hero_info['intellect'],
            $hero_info['politics'],
            $hero_info['charm'],
            $_star_hero['hero_pk'],
            $next_exp
        ]);
        if (!$ret) {
            return false;
        }

        // 최신 정보 받아오기
        $this->classHero();
        $ret = $this->Hero->getFreeHeroInfo($_star_hero['hero_pk']);
        if (!$ret) {
            return false;
        }

        $hero_info['hero_pk'] = $_star_hero['hero_pk']; // 결과 표시를 위해 영웅 pk 정보를 넣어 줌.
        $hero_info['hero_exp'] = $ret['hero_exp']; // 결과표시를 위해 현재 누적 경험치 정보를 넣어 줌.
        $hero_info['special_combi_cnt'] = $ret['special_combi_cnt'];
        $hero_info['rare_type'] = $_star_hero['rare_type']; // 특수 조합 메인 영웅 갱신을 위해 추가

        $hero_info['leadership'] = $ret['leadership'];
        $hero_info['mil_force'] = $ret['mil_force'];
        $hero_info['intellect'] = $ret['intellect'];
        $hero_info['politics'] = $ret['politics'];
        $hero_info['charm']  = $ret['charm'];

        return [
            'before' => $_star_hero, // 조합 전 영웅 정보
            'after' => $hero_info, // 조합 후 영웅 정보
            'exp_info' => $total_acqu_exp // 경험치 종합 정보
        ];
    }
}