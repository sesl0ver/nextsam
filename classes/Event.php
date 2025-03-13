<?php

class Event
{
    protected Pg $PgGame;

    public function __construct(Pg $_PgGame)
    {
        $this->PgGame = $_PgGame;
    }

    public function getTrigger ($_type): bool
    {
        $this->PgGame->query("SELECT $_type FROM event_trigger WHERE event_pk = 1");
        return ($this->PgGame->fetchOne() === 't') ?? false;
    }

    public function updateTrigger ($_type, $_value): bool
    {
        $this->PgGame->query("UPDATE event_trigger SET $_type = $_value WHERE event_pk = 1 RETURNING {$_type}");
        return ($this->PgGame->fetchOne() === 't') ?? false;
    }

    // my_event 존재 여부 체크
    public function checkMyEvent ($_lord_pk): void
    {
        $this->PgGame->query('SELECT count(lord_pk) FROM my_event WHERE lord_pk = $1', [$_lord_pk]);
        if ($this->PgGame->fetchOne() < 1) {
            $this->PgGame->query('INSERT INTO my_event (lord_pk) values ($1)', [$_lord_pk]);
        }
    }

    public function checkAccessRewardEvent ($_lord_pk): bool
    {
        global $_M;
        $today = date('Y-m-d');
        $hours = (INT)date('H');
        $trigger = false;
        if ($today >= $_M['ACCESS_REWARD']['start_date'] && $today <= $_M['ACCESS_REWARD']['end_date']) { // 이벤트 기간 내
            foreach ($_M['ACCESS_REWARD']['event_time'] as $value) {
                // 이미 받은 보상인지 체크
                $this->PgGame->query('SELECT count(lord_pk) FROM my_event WHERE lord_pk = $1 AND access_reward_date = $2 AND access_reward_hour >= $3 AND access_reward_hour <= $4', [$_lord_pk, $today, $value['start_time'], $value['end_time']]);
                if ($this->PgGame->fetchOne() < 1 && $hours >= $value['start_time'] && $hours < $value['end_time']) {
                    $trigger = true;
                }
            }
        }
        return $trigger;
    }

    public function updateAccessRewardEvent ($_lord_pk): void
    {
        $today = date('Y-m-d');
        $hours = (INT)date('H');
        $this->PgGame->query('UPDATE my_event SET access_reward_date = $2, access_reward_hour = $3 WHERE lord_pk = $1', [$_lord_pk, $today, $hours]);
    }

    public function checkTreasureEvent ($items): bool
    {
        global $_M;
        $trigger = true;
        if ($trigger === true) {
            foreach ($_M['TREASURE_EVENT']['material_item'] as $material_pk) {
                if (! isset($items[$material_pk])) {
                    $trigger = false;
                } else {
                    if ($items[$material_pk] < 1) {
                        $trigger = false;
                    }
                }
            }
        }
        return $trigger;
    }
}