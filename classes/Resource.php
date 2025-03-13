<?php

class Resource
{
    public Session $Session;
    public Pg $PgGame;
    public Log $Log;

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

    function get($_posi_pk, $_lord_pk = null): true
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }

        $this->PgGame->query("SELECT food_curr, food_max, food_production, food_spend, horse_curr, horse_max, horse_production, lumber_curr, lumber_max, lumber_production, 
iron_curr, iron_max, iron_production, last_update_dt FROM GetResourceDetail('$_posi_pk')");
        $this->PgGame->fetch();

        $resource_info = [
            'food_curr' => (INT)$this->PgGame->row['food_curr'], 'food_max' => (INT)$this->PgGame->row['food_max'], 'food_production' => (INT)$this->PgGame->row['food_production'],
            'food_spend' => $this->PgGame->row['food_spend'], 'horse_curr' => (INT)$this->PgGame->row['horse_curr'], 'horse_max' => (INT)$this->PgGame->row['horse_max'],
            'horse_production' => (INT)$this->PgGame->row['horse_production'], 'lumber_curr' => (INT)$this->PgGame->row['lumber_curr'],
            'lumber_max' => (INT)$this->PgGame->row['lumber_max'], 'lumber_production' => (INT)$this->PgGame->row['lumber_production'],
            'iron_curr' => (INT)$this->PgGame->row['iron_curr'], 'iron_max' => (INT)$this->PgGame->row['iron_max'],
            'iron_production' => (INT)$this->PgGame->row['iron_production'], 'last_update_dt' => $this->PgGame->row['last_update_dt']
        ];

        if ($_lord_pk) {
            $this->Session->sqAppend('RESO', $resource_info, null, $_lord_pk, $_posi_pk);
        }
        return true;
    }

    function updateResource($_posi_pk, $_food, $_horse, $_lumber, $_iron): array
    {
        $this->PgGame->query('SELECT food, horse, lumber, iron FROM UpdateResource(\''.$_posi_pk.'\', '.$_food.', '.$_horse.', '.$_lumber.', '.$_iron.')');
        $this->PgGame->query('SELECT food, horse, lumber, iron FROM GetCurrentResource(\''.$_posi_pk.'\')');
        $this->PgGame->fetch();
        return [
            'food_curr' => (INT)$this->PgGame->row['food'],
            'horse_curr' => (INT)$this->PgGame->row['horse'],
            'lumber_curr' => (INT)$this->PgGame->row['lumber'],
            'iron_curr' => (INT)$this->PgGame->row['iron']
        ];
    }

    function save($_posi_pk, $_lord_pk = null): true
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }
        if ($_lord_pk) {
            $this->Session->sqAppend('RESO', $this->updateResource($_posi_pk, 0, 0, 0, 0), null, $_lord_pk, $_posi_pk);
        }
        return true;
    }

    function increase($_posi_pk, $_resource_dic, $_lord_pk = null, $_type = null): true
    {
        if (! $_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }
        if (empty($_resource_dic['food'])) $_resource_dic['food'] = 0;
        if (empty($_resource_dic['horse'])) $_resource_dic['horse'] = 0;
        if (empty($_resource_dic['lumber'])) $_resource_dic['lumber'] = 0;
        if (empty($_resource_dic['iron'])) $_resource_dic['iron'] = 0;

        $this->PgGame->query('SELECT food, horse, lumber, iron FROM GetCurrentResource(\''.$_posi_pk.'\')');
        $this->PgGame->fetch();
        $prev_row = $this->PgGame->row;

        $incr_resource = $this->updateResource($_posi_pk, $_resource_dic['food'], $_resource_dic['horse'], $_resource_dic['lumber'], $_resource_dic['iron']);

        if ($_lord_pk) {
            $this->Session->sqAppend('RESO', $incr_resource, null, $_lord_pk, $_posi_pk);
        }

        $this->classLog();
        $prev = 'food['.$prev_row['food'].'];horse['.$prev_row['horse'].'];lumber['.$prev_row['lumber'].'];iron['.$prev_row['iron'].'];';
        $after = 'food['.$incr_resource['food_curr'].'];horse['.$incr_resource['horse_curr'].'];lumber['.$incr_resource['lumber_curr'].'];iron['.$incr_resource['iron_curr'].'];';
        $this->Log->setResource($_lord_pk, $_posi_pk, 'incr_reso_' . $_type, "food[{$_resource_dic['food']}];horse[{$_resource_dic['horse']}];lumber[{$_resource_dic['lumber']}];iron[{$_resource_dic['iron']}];", $prev, $after);

        return true;
    }

    function decrease($_posi_pk, $_resourceDic, $_lord_pk = null, $_type = null): bool
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }

        if (empty($_resourceDic['food'])) $_resourceDic['food'] = 0;
        if (empty($_resourceDic['horse'])) $_resourceDic['horse'] = 0;
        if (empty($_resourceDic['lumber'])) $_resourceDic['lumber'] = 0;
        if (empty($_resourceDic['iron'])) $_resourceDic['iron'] = 0;

        if ($_resourceDic['food'] < 0 || $_resourceDic['horse'] < 0 || $_resourceDic['lumber'] < 0 || $_resourceDic['iron'] < 0) {
            return false;
        }

        $this->PgGame->query('SELECT food, horse, lumber, iron FROM GetCurrentResource(\''.$_posi_pk.'\')');
        $this->PgGame->fetch();
        $prev_row = $this->PgGame->row;

        if ($this->PgGame->row['food'] < $_resourceDic['food'] || $this->PgGame->row['horse'] < $_resourceDic['horse'] ||
            $this->PgGame->row['lumber'] < $_resourceDic['lumber'] || $this->PgGame->row['iron'] < $_resourceDic['iron']) {
            return false;
        } else {
            $result = $this->updateResource($_posi_pk, -$_resourceDic['food'], -$_resourceDic['horse'], -$_resourceDic['lumber'], -$_resourceDic['iron']);
            if (! $result) {
                return false;
            }
            if ($_lord_pk) {
                $this->Session->sqAppend('RESO', $result, null, $_lord_pk, $_posi_pk);
            }

            $this->classLog();
            $prev = 'food['.$prev_row['food'].'];horse['.$prev_row['horse'].'];lumber['.$prev_row['lumber'].'];iron['.$prev_row['iron'].'];';
            $after = 'food['.$result['food_curr'].'];horse['.$result['horse_curr'].'];lumber['.$result['lumber_curr'].'];iron['.$result['iron_curr'].'];';
            $this->Log->setResource($_lord_pk, $_posi_pk, 'decr_reso_' . $_type, "food[{$_resourceDic['food']}];horse[{$_resourceDic['horse']}];lumber[{$_resourceDic['lumber']}];iron[{$_resourceDic['iron']}];", $prev, $after);

            return true;
        }
    }
}