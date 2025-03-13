<?php
class Cash
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Item $Item;
    protected Quest $Quest;
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

    function classQuest(): void
    {
        if (! isset($this->Quest)) {
            $this->Quest = new Quest($this->Session, $this->PgGame);
        }
    }

    function classItem(): void
    {
        if (! isset($this->Item)) {
            $this->Item = new Item($this->Session, $this->PgGame);
        }
    }

    function get($_lord_pk, $_sq_append = true): int
    {
        $this->PgGame->query('SELECT cash FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $cash = $this->PgGame->fetchOne();
        if ($_sq_append) {
            $this->Session->sqAppend('CASH', ['qbig' => $cash], null, $_lord_pk);
        }
        return $cash;
    }

    function decreaseCash($_lord_pk, $_price, $_reason = '')
    {
        if ($_price < 0) {
            return false;
        }
        $this->PgGame->query('SELECT cash, bill_cash, level FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $this->PgGame->fetch();
        $cash = $this->PgGame->row['cash'];
        $bill_cash = $this->PgGame->row['bill_cash'];
        $level = $this->PgGame->row['level'];
        $desc_bill_cash = $_price;
        if ($bill_cash - $desc_bill_cash < 0) {
            $desc_bill_cash = $bill_cash;
        }

        if ($cash < $_price || ($cash-$_price) < 0) {
            return false;
        } else {
            // 캐쉬 차감
            $this->PgGame->query('UPDATE lord SET cash = cash - $2, bill_cash = bill_cash - $3, use_cash = use_cash + $2 WHERE lord_pk = $1 AND cash >= $2', [$_lord_pk, $_price, $desc_bill_cash]);
            if ($this->PgGame->getAffectedRows() == 0) {
                return false;
            }
            $this->Session->sqAppend('CASH', ['qbig' => $cash - $_price]);

            if ($_reason != 'speedup now') {
                $this->classQuest();
                $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'buy_item', 'm_ques_pk' => 600724]);
            }

            // 큐빅 소모 퀘스트
            $this->classQuest();
            $this->Quest->countCheckQuest($_lord_pk, 'EVENT_QBIG_USE', ['value' => $_price]);

            // Log
            $after = $this->get($_lord_pk, false);
            $this->classLog();
            $this->Log->setQbig($_lord_pk, null, 'desc_cash', $cash, $_price, $after, $desc_bill_cash, $_reason, $level);
        }

        return $_price;
    }

    function increaseCash($_lord_pk, $_cash, $_reason = ''): bool
    {
        if ($_cash < 0) {
            return false;
        }
        $this->PgGame->query('SELECT cash, level FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $this->PgGame->fetch();
        $prev = $this->PgGame->row['cash'];
        $level = $this->PgGame->row['level'];
        $bill_cash = 0;
        if ($_reason == 'charge' || $_reason == 'refund_cancel') {
            $bill_cash = $_cash;
        }

        if ($_reason == 'refund_cancel') {
            $this->PgGame->query('UPDATE lord SET cash = cash + $2, bill_cash = bill_cash + $3, use_cash = use_cash - $2 WHERE lord_pk = $1', [$_lord_pk, $_cash, $bill_cash]);
        } else {
            $this->PgGame->query('UPDATE lord SET cash = cash + $2, bill_cash = bill_cash + $3 WHERE lord_pk = $1', [$_lord_pk, $_cash, $bill_cash]);
        }

        if ($this->PgGame->getAffectedRows() != 1) {
            return false;
        }

        //Log
        $this->classLog();
        $after = $this->get($_lord_pk);
        $this->Log->setQbig($_lord_pk, null, 'incr_cash', $prev, $_cash, $after, $bill_cash, $_reason, $level);

        return true;
    }

    function chargeCash($_store_type, $_product_id, $_bill_charge_no, $_lord_pk)
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['qbig_pack']);

        $m = $_M['QBIG_PACK'][$_store_type][$_product_id];
        if (!$m) {
            $NsGlobal->setErrorMessage('Error Occurred. [14001]'); // 상품 정보를 찾을 수 없습니다.
            return false;
        }

        if (!$this->PgGame->query('INSERT INTO qbig_pack (lord_pk, store_type, pack_type, buy_qbig, bill_chargeno) VALUES ($1, $2, $3, $4, $5)', [$_lord_pk, $_store_type, $m['pack_type'], $m['qbig_total'], $_bill_charge_no])) {
            $NsGlobal->setErrorMessage('Error Occurred. [14002]'); // 큐빅 지급 중 오류가 생겼습니다.(1)
            return false;
        }

        $ret = $this->increaseCash($_lord_pk, $m['qbig_total'], 'charge');
        if (!$ret) {
            $NsGlobal->setErrorMessage('Error Occurred. [14003]'); // 큐빅 지급 중 오류가 생겼습니다.(2)
            return false;
        }

        // 이벤트 아이템지급
        if ($m['m_item_pk']) {
            $this->classItem();
            $ret = $this->Item->BuyItem($_lord_pk, $m['m_item_pk'], $m['item_cnt'], 'buy_qbig_pack_event');
            if (!$ret) {
                $NsGlobal->setErrorMessage('Error Occurred. [14004]'); // 큐빅 지급 중 오류가 생겼습니다.(3)
                return false;
            }
        }

        // $this->classQuest();
        // $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'buy_qbig']);

        return $m['qbig_total'];
    }

    function chargeQbig($_qbig, $_store_type, $_prod_id, $_bill_charge_no, $_lord_pk = null)
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['qbig_pack']);
        if (! $_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }

        $m = $_M['QBIG_PACK'][$_store_type][$_prod_id];
        if (!$m) {
            $NsGlobal->setErrorMessage('Error Occurred. [14005]'); // 상품 정보를 찾을 수 없습니다.
            return false;
        }
        if (!$this->PgGame->query('INSERT INTO qbig_pack (lord_pk, store_type, pack_type, buy_qbig, bill_chargeno) VALUES ($1, $2, $3, $4, $5)', [$_lord_pk, $_store_type, $m['pack_type'], $_qbig, $_bill_charge_no])) {
            $NsGlobal->setErrorMessage('Error Occurred. [14006]'); // 큐빅 지급 중 오류가 생겼습니다. (qbig_pack 지급 오류)
            return false;
        }

        $ret = $this->increaseCash($_lord_pk, $_qbig, 'charge');
        if (!$ret) {
            $NsGlobal->setErrorMessage('Error Occurred. [14007]'); // 큐빅 지급 중 오류가 생겼습니다. (qbig 충전 오류)
            return false;
        }

        // 이벤트 아이템지급
        if ($m['m_item_pk']) {
            $this->classItem();
            $ret = $this->Item->BuyItem($_lord_pk, $m['m_item_pk'], $m['item_cnt'], 'buy_qbig_pack_event');
            if (!$ret)
            {
                $NsGlobal->setErrorMessage('Error Occurred. [14008]'); // 큐빅 지급 중 오류가 생겼습니다. (이벤트 아이템 지급 오류)
                return false;
            }
        }

        $this->classQuest();
        $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'buy_qbig']);

        return $_qbig;
    }
}