<?php

class GoldPop
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    function classLog(): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    function calcGoldPopulation($_posi_pk): array
    {
        $this->PgGame->query('SELECT floor(intCurrentPopulation) as population_curr, floor(fltCurrentGold) as gold_curr FROM CorrectPopulation(\''.$_posi_pk.'\', \'U\')');
        $this->PgGame->fetch();
        $population_curr = $this->PgGame->row['population_curr'];
        $gold_curr = $this->PgGame->row['gold_curr'];
        if ($gold_curr > RESOURCE_LIMIT_GOLD) { // TODO 골드 최대치 상수로 빼는게 나을 듯?
            $gold_curr = RESOURCE_LIMIT_GOLD;
        }
        $this->PgGame->query('SELECT population_trend FROM territory WHERE posi_pk = $1', [$_posi_pk]);
        $population_trend = $this->PgGame->fetchOne();
        $this->PgGame->query('SELECT gold_production FROM gold WHERE posi_pk = $1', [$_posi_pk]);
        $gold_production = $this->PgGame->fetchOne();
        return [
            'gold_curr' => (INT)$gold_curr,
            'gold_production' => (INT)$gold_production,
            'population_curr' => (INT)$population_curr,
            'population_trend' => $population_trend
        ];
    }

    function get($_posi_pk, $_lord_pk = null): array
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }
        $gold_population = $this->calcGoldPopulation($_posi_pk);
        if ($_lord_pk) {
            $this->Session->sqAppend('GOLDPOP', $gold_population, null, $_lord_pk, $_posi_pk);
        }
        return $gold_population;
    }

    function save($_posi_pk, $_lord_pk = null): true
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }
        $gold_population = $this->calcGoldPopulation($_posi_pk);
        if ($_lord_pk) {
            $this->Session->sqAppend('GOLDPOP', $gold_population, null, $_lord_pk, $_posi_pk);
        }
        return true;
    }

    function increaseGold($_posi_pk, $_gold, $_lord_pk = null, $_type = null): bool
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }
        $this->PgGame->query('SELECT gold_curr FROM gold WHERE posi_pk = $1', [$_posi_pk]);
        $prev_gold = $this->PgGame->fetchOne();
        $this->PgGame->query('SELECT fltCurrentGold FROM IncGold(\''.$_posi_pk.'\', '.$_gold.')');
        $gold_curr = $this->PgGame->fetchOne();
        if ($gold_curr > RESOURCE_LIMIT_GOLD) {
            $gold_curr = RESOURCE_LIMIT_GOLD;
        }
        $this->PgGame->query('SELECT gold_production FROM gold WHERE posi_pk = $1', [$_posi_pk]);
        $gold_production = $this->PgGame->fetchOne();
        if (!$gold_curr || !$gold_production) {
            return false;
        }
        // 무역장 상대군주에게 보낼 자원(황금)값이 자신에게 와서 예외처리
        if (isset($_lord_pk)) {
            $this->Session->sqAppend('GOLDPOP', ['gold_curr' => (INT)$gold_curr, 'gold_production' => (INT)$gold_production], null, $_lord_pk, $_posi_pk);
        }
        $gold_storage = null;
        if ($_type == 'hero_trad_gold_get') {
            $this->PgGame->query('SELECT gold FROM hero_trade_gold WHERE lord_pk = $1', [$_lord_pk]);
            $gold_storage = $this->PgGame->fetchOne();
        }
        $this->classLog();
        $this->Log->setResource($_lord_pk, $_posi_pk, 'incr_gold_' . $_type, $_gold, $prev_gold, $gold_curr, $gold_storage);
        return true;
    }

    function decreaseGold($_posi_pk, $_gold, $_lord_pk = null, $_type = null): bool
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }
        if ($_gold < 0) {
            return false;
        }
        $this->PgGame->query('SELECT floor(fltCurrentGold) FROM GetCurrentGold(\''.$_posi_pk.'\')');
        $gold_curr = $this->PgGame->fetchOne();
        $prev_gold = $gold_curr;
        $this->PgGame->query('SELECT gold_production FROM gold WHERE posi_pk = $1', [$_posi_pk]);
        $gold_production = $this->PgGame->fetchOne();
        if ($this->PgGame->getNumRows()== 0) {
            return false;
        }
        if (!$gold_curr || !$gold_production) {
            return false;
        }
        if(($gold_curr - $_gold) < 0) {
            return false;
        } else {
            $this->PgGame->query('SELECT fltCurrentGold FROM DecGold(\''.$_posi_pk.'\', '.$_gold.')');
            $gold_curr = $this->PgGame->fetchOne();
            if ($gold_curr > RESOURCE_LIMIT_GOLD) {
                $gold_curr = RESOURCE_LIMIT_GOLD;
            }
            if ($_lord_pk) {
                $this->Session->sqAppend('GOLDPOP', ['gold_curr' => (INT)$gold_curr, 'gold_production' => (INT)$gold_production], null, $_lord_pk, $_posi_pk);
            }
        }
        $gold_storage = null;
        if ($_type == 'hero_trad_gold_get') {
            $this->PgGame->query('SELECT gold FROM hero_trade_gold WHERE lord_pk = $1', [$_lord_pk]);
            $gold_storage = $this->PgGame->fetchOne();
        }
        $this->classLog();
        $this->Log->setResource($_lord_pk, $_posi_pk, 'decr_gold_' . $_type, $_gold, $prev_gold, $gold_curr, $gold_storage);
        return true;
    }

    function increasePopulation($_posi_pk, $_pop, $_lord_pk = null): bool
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }
        $this->PgGame->query('SELECT intCurrentPopulation FROM incpopulation(\''.$_posi_pk.'\', '.$_pop.')');
        $population_curr = $this->PgGame->fetchOne();
        $this->PgGame->query('SELECT population_trend FROM territory WHERE posi_pk = $1', [$_posi_pk]);
        $population_trend = $this->PgGame->fetchOne();
        if (!$population_curr || !$population_trend) {
            return false;
        }
        if ($_lord_pk) {
            $this->Session->sqAppend('GOLDPOP', ['population_curr' => (INT)$population_curr, 'population_trend' => $population_trend], null, $_lord_pk, $_posi_pk);
        }
        return true;
    }

    function decreasePopulation($_posi_pk, $_pop, $_lord_pk = null): bool
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }
        $this->PgGame->query('SELECT intCurrentPopulation FROM DecPopulation(\''.$_posi_pk.'\', '.$_pop.')');
        $population_curr = $this->PgGame->fetchOne();
        $this->PgGame->query('SELECT population_trend FROM territory WHERE posi_pk = $1', [$_posi_pk]);
        $population_trend = $this->PgGame->fetchOne();
        if ((!$population_curr && !is_numeric($population_curr)) || !is_numeric($population_curr) || !$population_trend) {
            return false;
        }
        if ($_lord_pk) {
            $this->Session->sqAppend('GOLDPOP', ['population_curr' => (INT)$population_curr, 'population_trend' => $population_trend], null, $_lord_pk, $_posi_pk);
        }
        return true;
    }
}