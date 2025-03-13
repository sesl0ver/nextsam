<?php
// TODO 영웅 거래 기능 사용안함.
class HeroTrade
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Hero $Hero;
    protected GoldPop $GoldPop;
    protected Report $Report;
    protected Log $Log;

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

    function classGoldPop(): void
    {
        if (! isset($this->GoldPop)) {
            $this->GoldPop = new GoldPop($this->Session, $this->PgGame);
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

    function initBidCount($_lord_pk): void
    {
        $this->PgGame->query('SELECT date_part(\'epoch\', trade_bid_init_dt)::integer FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $last_up_dt = $this->PgGame->fetchOne();

        $last_monday = (date('w') == 1) ? date('Y-m-d', time()) : date('Y-m-d', strtotime('last Monday'));

        $this->PgGame->query("SELECT date_part('epoch', $last_monday)::integer"); // 'SELECT \''. $last_monday .'\'::abstime::integer'
        $last_monday = $this->PgGame->fetchOne();

        if ($last_up_dt < $last_monday) {
            $this->PgGame->query('UPDATE lord SET trade_bid_cnt = 0, trade_bid_init_dt = now() WHERE lord_pk = $1', [$_lord_pk]);

            $this->classLog();
            $this->Log->setHeroTrade($_lord_pk, null, 'init_trade_bid_cnt');
        }
    }

    function getTradePossibleHeroCount($_lord_pk): int
    {
        $this->PgGame->query('SELECT count(a.hero_pk) FROM my_hero a, hero b WHERE a.lord_pk = $1 AND a.status = $2 AND a.hero_pk = b.hero_pk AND b.yn_trade = $3', [$_lord_pk, 'G', 'N']);
        return $this->PgGame->fetchOne();
    }

    function getTradePossibleHero($_lord_pk, $_page_num, $_order, $_order_type): array
    {
        $page_num = (INT)$_page_num;
        $page_num = ($page_num < 1) ? 1 : $page_num;
        $_order = preg_replace('/[^\w]/', '', strtolower($_order));

        $offset_start = ($page_num - 1) * HERO_TRADE_SELL_LIST_COUNT;
        $limit = HERO_TRADE_SELL_LIST_COUNT;

        if ($_order == 'name') {
            $_order_by = "t4.name::BYTEA {$_order_type}, t2.rare_type ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC";
        } else if ($_order == 'rare') {
            $_order_by = "t2.rare_type {$_order_type}, t4.name ASC, t2.level DESC, t1.leadership DESC, t1.m_offi_pk ASC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC";
        } else if ($_order == 'level') {
            $_order_by = "t2.level {$_order_type}, t4.name ASC, t2.rare_type DESC, t1.leadership DESC, t1.m_offi_pk ASC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC";
        } else if ($_order == 'leadership') {
            $_order_by = "t1.leadership {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC";
        } else if ($_order == 'mil_force') {
            $_order_by = "t1.mil_force {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.intellect DESC, t1.politics DESC, t1.charm DESC";
        } else if ($_order == 'intellect') {
            $_order_by = "t1.intellect {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.politics DESC, t1.charm DESC";
        } else if ($_order == 'politics') {
            $_order_by = "t1.politics {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.charm DESC";
        } else if ($_order == 'charm') {
            $_order_by = "t1.charm {$_order_type}, t2.rare_type DESC, t4.name ASC, t2.level DESC, t1.m_offi_pk ASC, t1.leadership DESC, t1.mil_force DESC, t1.intellect DESC, t1.politics DESC";
        } else {
            $_order_by = '';
        }

        // TODO abstime 변경 필요.
        $this->PgGame->query("SELECT
 t1.hero_pk, t4.name, t1.m_offi_pk, t1.status, t1.status_cmd, t1.yn_lord, t1.posi_pk, t1.leadership, t1.mil_force, t1.intellect, t1.politics, t1.charm,
 t2.m_hero_pk, t2.status AS m_status, t2.level, t2.rare_type, t2.enchant, t2.loyalty, t2.hp, 
 t2.leadership_basic, t2.leadership_enchant, t2.leadership_plusstat, t2.leadership_skill,
 t2.mil_force_basic, t2.mil_force_enchant, t2.mil_force_plusstat,  t2.mil_force_skill,
 t2.intellect_basic, t2.intellect_enchant, t2.intellect_plusstat, t2.intellect_skill,
 t2.politics_basic, t2.politics_enchant, t2.politics_plusstat,  t2.politics_skill,
 t2.charm_basic, t2.charm_enchant, t2.charm_plusstat, t2.charm_skill, 
 t2.m_hero_skil_pk_1, t2.m_hero_skil_pk_2, t2.m_hero_skil_pk_3, t2.m_hero_skil_pk_4,
 date_part('epoch', t1.timedjob_dt)::integer AS timedjob_dt_ut, t1.cmd_type, t2.skill_exp,
 t5.slot_pk1, t5.m_hero_skil_pk1, t5.main_slot_pk1, t5.slot_pk2, t5.m_hero_skil_pk2, t5.main_slot_pk2,
 t5.slot_pk3, t5.m_hero_skil_pk3, t5.main_slot_pk3, t5.slot_pk4, t5.m_hero_skil_pk4, t5.main_slot_pk4,
 t5.slot_pk5, t5.m_hero_skil_pk5, t5.main_slot_pk5, t5.slot_pk6, t5.m_hero_skil_pk6, t5.main_slot_pk6,
 t1.group_type, t1.group_order
FROM my_hero AS t1, hero AS t2, m_hero AS t3, m_hero_base AS t4, getmyheroesskillslot({$_lord_pk}) AS t5
WHERE t1.hero_pk = t2.hero_pk AND t2.m_hero_pk = t3.m_hero_pk AND t3.m_hero_base_pk = t4.m_hero_base_pk AND t1.hero_pk = t5.hero_pk 
AND t1.lord_pk = $1 AND t1.status = $2 AND t1.status_cmd = $3 AND t2.yn_trade = $4
ORDER BY {$_order_by}
LIMIT {$limit}
OFFSET {$offset_start}", [$_lord_pk, 'G', 'I', 'N']);
        $this->PgGame->fetchAll();
        $heroes = $this->PgGame->rows;

        return (is_array($heroes)) ? $heroes : [];
    }

    function sellHeroRegist($_lord_pk, $_hero_pk, $_min_value, $_max_value, $_sale_period, $_password, $_sale_ip): bool
    {
        global $NsGlobal;
        // 내영웅인지, 가능한 상태인지 확인
        $this->PgGame->query('SELECT a.lord_pk FROM my_hero a, hero b WHERE a.hero_pk = $1 AND a.hero_pk = b.hero_pk AND a.status = $2 AND b.yn_trade = $3', [$_hero_pk, 'G', 'N']);
        $lord_pk = $this->PgGame->fetchOne();
        if (!$lord_pk) {
            $NsGlobal->setErrorMessage('판매가능한 영웅이 아닙니다.');
            return false;
        }

        if ($_lord_pk != $lord_pk) {
            $NsGlobal->setErrorMessage('해당 영웅을 소유하고 있지 않습니다.');
            return false;
        }

        $this->PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $lord_name = $this->PgGame->fetchOne();

        if (!$_max_value) {
            $_max_value = 900000000;
        }

        $r = $this->PgGame->query("INSERT INTO hero_trade (lord_pk, hero_pk, lord_name, min_value, max_value, now_value, sale_period, password,
regist_dt, hero_trad_bid_pk, m_hero_pk, m_hero_base_pk, hero_name, rare_type, level, enchant,
leadership, mil_force, intellect, politics, charm,
m_hero_skil_pk_1, m_hero_skil_pk_2, m_hero_skil_pk_3, m_hero_skil_pk_4, m_hero_skil_pk_5, m_hero_skil_pk_6,
leadership_basic, leadership_enchant, leadership_plusstat, leadership_skill,
mil_force_basic, mil_force_enchant, mil_force_plusstat,  mil_force_skill,
intellect_basic, intellect_enchant, intellect_plusstat, intellect_skill,
politics_basic, politics_enchant, politics_plusstat,  politics_skill,
charm_basic, charm_enchant, charm_plusstat, charm_skill, loyalty, skill_exp, sell_ip)
SELECT {$_lord_pk}, {$_hero_pk}, '{$lord_name}', {$_min_value}, {$_max_value}, 0, {$_sale_period}, '{$_password}',
now(), null, t3.m_hero_pk, t4.m_hero_base_pk, t4.name, t2.rare_type, t2.level, t2.enchant,
t1.leadership, t1.mil_force, t1.intellect, t1.politics, t1.charm,
t5.m_hero_skil_pk1, t5.m_hero_skil_pk2, t5.m_hero_skil_pk3, t5.m_hero_skil_pk4, t5.m_hero_skil_pk5, t5.m_hero_skil_pk6,
t2.leadership_basic, t2.leadership_enchant, t2.leadership_plusstat, t2.leadership_skill,
t2.mil_force_basic, t2.mil_force_enchant, t2.mil_force_plusstat,  t2.mil_force_skill,
t2.intellect_basic, t2.intellect_enchant, t2.intellect_plusstat, t2.intellect_skill,
t2.politics_basic, t2.politics_enchant, t2.politics_plusstat,  t2.politics_skill,
t2.charm_basic, t2.charm_enchant, t2.charm_plusstat, t2.charm_skill, t2.loyalty, t2.skill_exp, '{$_sale_ip}'
FROM my_hero t1, hero t2, m_hero t3, m_hero_base t4, getmyheroskillslot({$_hero_pk}) AS t5
WHERE t1.hero_pk = $1 AND t1.hero_pk = t2.hero_pk AND t2.m_hero_pk = t3.m_hero_pk
AND t3.m_hero_base_pk = t4.m_hero_base_pk;", [$_hero_pk]);
        if (!$r) {
            $NsGlobal->setErrorMessage('영웅판매 등록에 실패 하였습니다.');
            return false;
        }

        $hero_trad_pk = $this->PgGame->currSeq('hero_trade_hero_trad_pk_seq');

        $this->PgGame->query('UPDATE my_hero SET status = $2 WHERE hero_pk = $1', [$_hero_pk, 'B']);

        $this->classLog();
        $hero_trade_info = $this->getHeroTradeInfo($hero_trad_pk, 'N');
        $desc = 'enchant:'.$hero_trade_info['enchant'].';leadership:'.$hero_trade_info['leadership'].';mil_force:'.$hero_trade_info['mil_force'].';intellect:'.$hero_trade_info['intellect'].';politics:'.$hero_trade_info['politics'].';charm:'.$hero_trade_info['charm'].';m_hero_skil_pk_1:'.$hero_trade_info['m_hero_skil_pk_1'].';m_hero_skil_pk_2:'.$hero_trade_info['m_hero_skil_pk_2'].';m_hero_skil_pk_3:'.$hero_trade_info['m_hero_skil_pk_3'].';m_hero_skil_pk_4:'.$hero_trade_info['m_hero_skil_pk_4'].';m_hero_skil_pk_5:'.$hero_trade_info['m_hero_skil_pk_5'].';m_hero_skil_pk_6:'.$hero_trade_info['m_hero_skil_pk_6'].';'; // skill_exp:'.$hero_trade_info['skill_exp']
        $this->Log->setHeroTrade($_lord_pk, null, 'sell_hero_regist', $_hero_pk, $hero_trade_info['hero_name'], $hero_trade_info['level'], $_min_value, $_max_value, null, null, $hero_trad_pk, null, $_sale_period, $_password, $desc);

        return true;
    }

    function getTradeListTotalCount($_type, $_lord_pk, $_rare = null, $_level = null, $_name_type = null, $_name = null)
    {
        if ($_type == 'all') {
            // TODO 쿼리문 좀 정리하자.
            if ($_rare) {
                $_rare = $_rare < 3 ? ' AND rare_type = \'' . $_rare .'\'': ' AND rare_type >= \'' . $_rare .'\'';
            }
            $_level = $_level ? ' AND level = ' . $_level : '';
            $_name_type = $_name ? ' AND ' . $_name_type . '_name = \'' . $_name .'\'': '';
            $this->PgGame->query('SELECT COUNT(hero_trad_pk) FROM hero_trade WHERE trade_complete = $2 AND yn_sale = $1 AND end_dt > now() ' . $_rare . $_level . $_name_type, ['Y', 'N']);
        } else if ($_type == 'bid') {
            $this->PgGame->query('SELECT COUNT(hero_trad_bid_pk) FROM hero_trade_bid WHERE lord_pk = $1', [$_lord_pk]);
        } else if ($_type == 'sale') {
            $this->PgGame->query('SELECT COUNT(hero_trad_pk) FROM hero_trade WHERE lord_pk = $1 AND trade_complete = $2', [$_lord_pk, 'N']);
        }

        return $this->PgGame->fetchOne();
    }

    function getTradeList($_lord_pk, $_page_num, $_order, $_order_type, $_page_cnt, $_rare = null, $_level = null, $_name_type = null, $_name = null): array
    {
        $page = (INT)$_page_num;
        $page = ($page < 1 || !is_int($page)) ? 1 : $page;
        $_order = preg_replace('/[^\w]/', '', strtolower($_order));

        $_page_cnt = ($_page_cnt == null) ? HERO_TRADE_LIST_COUNT : $_page_cnt;
        $offset_start = ($page - 1) * $_page_cnt;
        $limit = $_page_cnt;

        if ($_rare) {
            $_rare = $_rare < 3 ? ' AND rare_type = \'' . $_rare .'\'': ' AND rare_type >= \'' . $_rare .'\'';
        }
        $_level = $_level ? ' AND level = ' . $_level : '';
        $_name_type = $_name ? ' AND ' . $_name_type . '_name = \'' . $_name .'\'': '';

        $order_by = '';
        if ($_order == 'name') {
            $order_by = "hero_name::BYTEA {$_order_type}, rare_type ASC, level DESC, enchant DESC, leadership DESC, mil_force DESC, intellect DESC, politics DESC, charm DESC";
        } else if ($_order == 'rare') {
            $order_by = "rare_type {$_order_type},  hero_name ASC, level DESC, enchant DESC, leadership DESC, mil_force DESC, intellect DESC, politics DESC, charm DESC";
        } else if ($_order == 'level') {
            $order_by = "level {$_order_type}, rare_type DESC, hero_name ASC, enchant DESC, leadership DESC, mil_force DESC, intellect DESC, politics DESC, charm DESC";
        } else if ($_order == 'enchant') {
            $order_by = "enchant {$_order_type}, rare_type DESC, hero_name ASC, level DESC, leadership DESC, mil_force DESC, intellect DESC, politics DESC, charm DESC";
        } else if ($_order == 'leadership') {
            $order_by = "leadership {$_order_type}, rare_type DESC, hero_name ASC, level DESC, enchant DESC, mil_force DESC, intellect DESC, politics DESC, charm DESC";
        } else if ($_order == 'mil_force') {
            $order_by = "mil_force {$_order_type}, rare_type DESC, hero_name ASC, level DESC, enchant DESC, leadership DESC, intellect DESC, politics DESC, charm DESC";
        } else if ($_order == 'intellect') {
            $order_by = "intellect {$_order_type}, rare_type DESC, hero_name ASC, level DESC, enchant DESC, leadership DESC, mil_force DESC, politics DESC, charm DESC";
        } else if ($_order == 'politics') {
            $order_by = "politics {$_order_type}, rare_type DESC, hero_name ASC, level DESC, enchant DESC, leadership DESC, mil_force DESC, intellect DESC, charm DESC";
        } else if ($_order == 'charm') {
            $order_by = "charm {$_order_type}, rare_type DESC, hero_name ASC, level DESC, enchant DESC, leadership DESC, mil_force DESC, intellect DESC, politics DESC";
        } else if ($_order == 'end_dt') {
            $order_by = "end_dt {$_order_type}, rare_type DESC, hero_name ASC, level DESC, enchant DESC, leadership DESC, mil_force DESC, intellect DESC, politics DESC, charm DESC";
        } else if ($_order == 'now_value') {
            $order_by = "now_value {$_order_type}, rare_type DESC, hero_name ASC, level DESC, enchant DESC, leadership DESC, mil_force DESC, intellect DESC, politics DESC, charm DESC";
        } else if ($_order == 'max_value') {
            $order_by = "max_value {$_order_type}, rare_type DESC, hero_name ASC, level DESC, enchant DESC, leadership DESC, mil_force DESC, intellect DESC, politics DESC, charm DESC";
        }

        $this->PgGame->query("SELECT
hero_trad_pk, lord_pk, hero_pk, lord_name, min_value, max_value, now_value, sale_period, password, regist_dt, date_part('epoch', end_dt)::integer AS end_dt, hero_trad_bid_pk,
m_hero_pk, m_hero_base_pk, hero_name, rare_type, level, enchant, 
leadership, mil_force, intellect, politics, charm,
leadership_basic, leadership_enchant, leadership_plusstat, leadership_skill,
mil_force_basic, mil_force_enchant, mil_force_plusstat,  mil_force_skill,
intellect_basic, intellect_enchant, intellect_plusstat, intellect_skill,
politics_basic, politics_enchant, politics_plusstat,  politics_skill,
charm_basic, charm_enchant, charm_plusstat, charm_skill, 
m_hero_skil_pk_1, m_hero_skil_pk_2, m_hero_skil_pk_3, m_hero_skil_pk_4, m_hero_skil_pk_5, m_hero_skil_pk_6,
loyalty, skill_exp, trade_complete
FROM hero_trade WHERE trade_complete = 'N' AND yn_sale = 'Y' AND end_dt > now() {$_rare} {$_level} {$_name_type}
ORDER BY {$order_by}
LIMIT {$limit}
OFFSET {$offset_start}");
        $this->PgGame->fetchAll();
        $heroes = $this->PgGame->rows;

        foreach($heroes AS $k => $v) {
            if ($v['password']) {
                $heroes[$k]['password'] = 'Y';
            }
        }

        return (is_array($heroes)) ? $heroes : [];
    }

    function getMyTradeBidList($_lord_pk, $_page_num, $_order, $_order_type, $_page_cnt): array
    {
        $page = (INT)$_page_num;
        $page = ($page < 1) ? 1 : $page;
        $_order = preg_replace('/[^\w]/', '', strtolower($_order));

        $_page_cnt = ($_page_cnt == null) ? HERO_TRADE_LIST_COUNT : $_page_cnt;
        $offset_start = ($page - 1) * $_page_cnt;
        $limit = $_page_cnt;

        $order_by = '';
        if ($_order == 'name') {
            $order_by = "t2.hero_name::BYTEA {$_order_type}, t2.rare_type ASC, t2.level DESC, t2.enchant DESC, t2.leadership DESC, t2.mil_force DESC, t2.intellect DESC, t2.politics DESC, t2.charm DESC";
        } else if ($_order == 'rare') {
            $order_by = "t2.rare_type {$_order_type}, t2.hero_name ASC, t2.level DESC, t2.enchant DESC, t2.leadership DESC, t2.mil_force DESC, t2.intellect DESC, t2.politics DESC, t2.charm DESC";
        } else if ($_order == 'level') {
            $order_by = "t2.level {$_order_type}, t2.rare_type DESC, t2.hero_name ASC, t2.enchant DESC, t2.leadership DESC, t2.mil_force DESC, t2.intellect DESC, t2.politics DESC, t2.charm DESC";
        } else if ($_order == 'enchant') {
            $order_by = "t2.enchant {$_order_type}, t2.rare_type DESC, t2.hero_name ASC, t2.level DESC, t2.leadership DESC, t2.mil_force DESC, t2.intellect DESC, t2.politics DESC, t2.charm DESC";
        } else if ($_order == 'leadership') {
            $order_by = "t2.leadership {$_order_type}, t2.rare_type DESC, t2.hero_name ASC, t2.level DESC, t2.enchant DESC, t2.mil_force DESC, t2.intellect DESC, t2.politics DESC, t2.charm DESC";
        } else if ($_order == 'mil_force') {
            $order_by = "t2.mil_force {$_order_type}, t2.rare_type DESC, t2.hero_name ASC, t2.level DESC, t2.enchant DESC, t2.leadership DESC, t2.intellect DESC, t2.politics DESC, t2.charm DESC";
        } else if ($_order == 'intellect') {
            $order_by = "t2.intellect {$_order_type}, t2.rare_type DESC, t2.hero_name ASC, t2.level DESC, t2.enchant DESC, t2.leadership DESC, t2.mil_force DESC, t2.politics DESC, t2.charm DESC";
        } else if ($_order == 'politics') {
            $order_by = "t2.politics {$_order_type}, t2.rare_type DESC, t2.hero_name ASC, t2.level DESC, t2.enchant DESC, t2.leadership DESC, t2.mil_force DESC, t2.intellect DESC, t2.charm DESC";
        } else if ($_order == 'charm') {
            $order_by = "t2.charm {$_order_type}, t2.rare_type DESC, t2.hero_name ASC, t2.level DESC, t2.enchant DESC, t2.leadership DESC, t2.mil_force DESC, t2.intellect DESC, t2.politics DESC";
        }

        $this->PgGame->query("SELECT
t2.hero_trad_pk, t2.hero_pk, t2.lord_pk, t2.lord_name, t2.min_value, t2.max_value, t2.now_value, t1.bid_value, t2.sale_period, t2.password, 
t2.regist_dt, date_part('epoch', t2.end_dt)::integer AS end_dt, t2.hero_trad_bid_pk,
t2.m_hero_pk, t2.m_hero_base_pk, t2.hero_name, t2.rare_type, t2.level, t2.enchant, 
t2.leadership, t2.mil_force, t2.intellect, t2.politics, t2.charm,
t2.leadership_basic, t2.leadership_enchant, t2.leadership_plusstat, t2.leadership_skill,
t2.mil_force_basic, t2.mil_force_enchant, t2.mil_force_plusstat, t2.mil_force_skill,
t2.intellect_basic, t2.intellect_enchant, t2.intellect_plusstat, t2.intellect_skill,
t2.politics_basic, t2.politics_enchant, t2.politics_plusstat, t2.politics_skill,
t2.charm_basic, t2.charm_enchant, t2.charm_plusstat, t2.charm_skill, 
t2.m_hero_skil_pk_1, t2.m_hero_skil_pk_2, t2.m_hero_skil_pk_3, t2.m_hero_skil_pk_4, t2.m_hero_skil_pk_5, t2.m_hero_skil_pk_6,
t2.loyalty, t2.skill_exp, t2.trade_complete
FROM hero_trade_bid t1, hero_trade t2 
WHERE t1.lord_pk = $1 AND t1.hero_trad_pk = t2.hero_trad_pk 
ORDER BY {$order_by}
LIMIT {$limit}
OFFSET {$offset_start}", [$_lord_pk]);
        $this->PgGame->fetchAll();
        $heroes = $this->PgGame->rows;

        foreach($heroes AS $k => $v) {
            if ($v['password']) {
                $heroes[$k]['password'] = 'Y';
            }
        }
        return (is_array($heroes)) ? $heroes : [];
    }

    function getMyTradeSellList($_lord_pk, $_page_num, $_order, $_order_type, $_page_cnt): array
    {
        $page = (INT)$_page_num;
        $page = ($page < 1) ? 1 : $page;
        $order = preg_replace('/[^\w]/', '', strtolower($_order));

        $_page_cnt = ($_page_cnt == null) ? HERO_TRADE_LIST_COUNT : $_page_cnt;
        $offset_start = ($page - 1) * $_page_cnt;
        $limit = $_page_cnt;

        $order_by = '';
        if ($_order == 'name') {
            $order_by = "hero_name::BYTEA {$_order_type}, rare_type ASC, level DESC, enchant DESC, leadership DESC, mil_force DESC, intellect DESC, politics DESC, charm DESC";
        } else if ($_order == 'rare') {
            $order_by = "rare_type {$_order_type},  hero_name ASC, level DESC, enchant DESC, leadership DESC, mil_force DESC, intellect DESC, politics DESC, charm DESC";
        } else if ($_order == 'level') {
            $order_by = "level {$_order_type}, rare_type DESC, hero_name ASC, enchant DESC, leadership DESC, mil_force DESC, intellect DESC, politics DESC, charm DESC";
        } else if ($_order == 'enchant') {
            $order_by = "enchant {$_order_type}, rare_type DESC, hero_name ASC, level DESC, leadership DESC, mil_force DESC, intellect DESC, politics DESC, charm DESC";
        } else if ($_order == 'leadership') {
            $order_by = "leadership {$_order_type}, rare_type DESC, hero_name ASC, level DESC, enchant DESC, mil_force DESC, intellect DESC, politics DESC, charm DESC";
        } else if ($_order == 'mil_force') {
            $order_by = "mil_force {$_order_type}, rare_type DESC, hero_name ASC, level DESC, enchant DESC, leadership DESC, intellect DESC, politics DESC, charm DESC";
        } else if ($_order == 'intellect') {
            $order_by = "intellect {$_order_type}, rare_type DESC, hero_name ASC, level DESC, enchant DESC, leadership DESC, mil_force DESC, politics DESC, charm DESC";
        } else if ($_order == 'politics') {
            $order_by = "politics {$_order_type}, rare_type DESC, hero_name ASC, level DESC, enchant DESC, leadership DESC, mil_force DESC, intellect DESC, charm DESC";
        } else if ($_order == 'charm') {
            $order_by = "charm {$_order_type}, rare_type DESC, hero_name ASC, level DESC, enchant DESC, leadership DESC, mil_force DESC, intellect DESC, politics DESC";
        }

        $this->PgGame->query("SELECT
hero_trad_pk, hero_pk, lord_pk, lord_name, min_value, max_value, now_value, sale_period, password, regist_dt, date_part('epoch', end_dt)::integer AS end_dt, hero_trad_bid_pk,
m_hero_pk, m_hero_base_pk, hero_name, rare_type, level, enchant, 
leadership, mil_force, intellect, politics, charm,
leadership_basic, leadership_enchant, leadership_plusstat, leadership_skill,
mil_force_basic, mil_force_enchant, mil_force_plusstat,  mil_force_skill,
intellect_basic, intellect_enchant, intellect_plusstat, intellect_skill,
politics_basic, politics_enchant, politics_plusstat,  politics_skill,
charm_basic, charm_enchant, charm_plusstat, charm_skill, 
m_hero_skil_pk_1, m_hero_skil_pk_2, m_hero_skil_pk_3, m_hero_skil_pk_4, m_hero_skil_pk_5, m_hero_skil_pk_6,
loyalty, skill_exp, trade_complete, yn_sale
FROM hero_trade WHERE lord_pk = $1 AND trade_complete = 'N'
ORDER BY {$order_by}
LIMIT {$limit}
OFFSET {$offset_start}", [$_lord_pk]);
        $this->PgGame->fetchAll();
        $heroes = $this->PgGame->rows;

        foreach($heroes AS $k => $v) {
            if ($v['password']) {
                $heroes[$k]['password'] = 'Y';
            }
        }

        return (is_array($heroes)) ? $heroes : [];
    }

    function getTradeBidCount($_lord_pk): int
    {
        $this->initBidCount($_lord_pk);
        $this->PgGame->query('SELECT trade_bid_cnt FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        return $this->PgGame->fetchOne();
    }

    function getMyBidInfo($_lord_pk, $_hero_trad_pk): int
    {
        $this->PgGame->query('SELECT bid_value FROM hero_trade_bid WHERE lord_pk = $1 AND hero_trad_pk = $2', [$_lord_pk, $_hero_trad_pk]);

        return $this->PgGame->fetchOne();
    }

    function getHeroTradeInfo($_hero_trad_pk, $_yn_tran = null): bool|array
    {
        $z = '';
        if ($_yn_tran != 'N') {
            $z =  ' FOR UPDATE';
        }

        $this->PgGame->query('SELECT hero_trad_bid_pk, lord_pk, lord_name, hero_pk, now_value, max_value, min_value, sale_period, password, hero_name, level, m_hero_pk, sell_ip, end_dt, date_part(\'epoch\', end_dt)::integer as end_dt_ut, yn_sale, trade_complete, enchant, leadership, mil_force, intellect, politics, charm, m_hero_skil_pk_1, m_hero_skil_pk_2, m_hero_skil_pk_3, m_hero_skil_pk_4, m_hero_skil_pk_5, m_hero_skil_pk_6, skill_exp FROM hero_trade WHERE hero_trad_pk = $1' . $z, [$_hero_trad_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        if ($row['password']) {
            $row['password'] = 'Y';
        }
        return $row;
    }

    function setHeroTradeBid($_lord_pk, $_hero_trad_pk, $_bid_value, $_trade_info, $_prev_value, $_posi_pk = null): bool
    {
        global $NsGlobal;
        $type = null;
        // 황금체크는 완료됐음 - 즉시 구매인지 입찰인지 확인
        if ($_bid_value >= $_trade_info['max_value']) {
            // 즉시 구매
            $ret = $this->setHeroTradeBidSuccess($_lord_pk, $_hero_trad_pk, $_bid_value, $_trade_info);
            if (!$ret) {
                $NsGlobal->setErrorMessage('영웅 구매에 실패 하였습니다.');
                return false;
            }
            $type = 'buy_now';
        } else { // 입찰 참여
            // 영웅 입찰 정보 업데이트(입찰자)
            if ($_prev_value) {
                $r = $this->PgGame->query('UPDATE hero_trade_bid SET bid_value = $2, update_dt = now() WHERE lord_pk = $3 AND hero_trad_pk = $1', [$_hero_trad_pk, $_bid_value, $_lord_pk]);
                if (!$r) {
                    $NsGlobal->setErrorMessage('영웅 입찰에 실패 하였습니다.');
                    return false;
                }

                $this->PgGame->query('SELECT hero_trad_bid_pk FROM hero_trade_bid WHERE lord_pk = $1 AND hero_trad_pk = $2', [$_lord_pk, $_hero_trad_pk]);
                $hero_trad_bid_pk = $this->PgGame->fetchOne();
            } else {
                $r = $this->PgGame->query('INSERT INTO hero_trade_bid (lord_pk, hero_trad_pk, bid_value, update_dt) VALUES ($3, $1, $2, now())', [$_hero_trad_pk, $_bid_value, $_lord_pk]);
                if (!$r) {
                    $NsGlobal->setErrorMessage('영웅 입찰에 실패 하였습니다.');
                    return false;
                }
                $hero_trad_bid_pk = $this->PgGame->currSeq('hero_trade_bid_hero_trad_bid_pk_seq');
            }

            //hero_trade 데이터 업데이트
            $r =$this->PgGame->query('UPDATE hero_trade SET now_value = $2, hero_trad_bid_pk = $3 WHERE hero_trad_pk = $1', [$_hero_trad_pk, $_bid_value, $hero_trad_bid_pk]);
            if (!$r) {
                $NsGlobal->setErrorMessage('영웅 입찰에 실패 하였습니다.');
                return false;
            }
        }

        if (!$_prev_value) {
            $this->PgGame->query('UPDATE lord SET trade_bid_cnt = trade_bid_cnt + 1 WHERE lord_pk = $1', [$_lord_pk]);
            if (!$type) {
                $type = 'bid';
            }
        } else {
            if (!$type) {
                $type = 'add_bid';
            }
        }

        $this->classLog();
        $desc = 'enchant:'.$_trade_info['enchant'].';leadership:'.$_trade_info['leadership'].';mil_force:'.$_trade_info['mil_force'].';intellect:'.$_trade_info['intellect'].';politics:'.$_trade_info['politics'].';charm:'.$_trade_info['charm'].';m_hero_skil_pk_1:'.$_trade_info['m_hero_skil_pk_1'].';m_hero_skil_pk_2:'.$_trade_info['m_hero_skil_pk_2'].';m_hero_skil_pk_3:'.$_trade_info['m_hero_skil_pk_3'].';m_hero_skil_pk_4:'.$_trade_info['m_hero_skil_pk_4'].';m_hero_skil_pk_5:'.$_trade_info['m_hero_skil_pk_5'].';m_hero_skil_pk_6:'.$_trade_info['m_hero_skil_pk_6'].';skill_exp:'.$_trade_info['skill_exp'];
        $this->Log->setHeroTrade($_lord_pk, $_posi_pk, $type, $_trade_info['hero_pk'], $_trade_info['hero_name'], $_trade_info['level'], $_trade_info['min_value'], $_trade_info['max_value'], $_bid_value, null, $_hero_trad_pk, $hero_trad_bid_pk, $_trade_info['sale_period'], $_trade_info['password'], $desc, $_trade_info['lord_pk']);

        return true;
    }

    function setHeroTradeBidSuccess($_lord_pk, $_hero_trad_pk, $_bid_value, $_trade_info): bool
    {
        // 영웅 소유주 변경
        // 영웅 상태 변경('B' -> 'V') 기간은 얼마나 할것 인가?
        // 판매자에 금액 지급(수수료 제외하고..)
        // hero_trade_bid 데이터 삭제
        // 보고서 보내기
        // hero_trade.trade_complete update
        // 영웅 소유주 변경 및 영웅 상태 변경
        $interval = 60 * 60 * 24 * 30;
        $r = $this->PgGame->query("UPDATE my_hero SET lord_pk = $1, status = $3, timedjob_dt = now() + interval '$interval second' WHERE hero_pk = $2", [$_lord_pk, $_trade_info['hero_pk'], 'V']);
        if (!$r) {
            return false;
        }
        // 영웅 거래 상태 변경
        $r =$this->PgGame->query('UPDATE hero SET yn_trade = $2, is_post_up = $3, yn_re_guest = $4 WHERE hero_pk = $1', [$_trade_info['hero_pk'], 'Y', 'N', 'N']);
        if (!$r) {
            return false;
        }

        // 판매자 수수료
        $commission = $this->getCommission($_trade_info['sale_period'], $_bid_value);
        $sold_value = $_bid_value - $commission;
        // 판매자 금액 지급
        $r = $this->setHeroTradeGold($_trade_info['lord_pk'], $sold_value, $_hero_trad_pk);
        if (!$r) {
            return false;
        }

        // 내 입찰 기록 삭제
        $this->PgGame->query('DELETE FROM hero_trade_bid WHERE lord_pk = $1 AND hero_trad_pk = $2', [$_lord_pk, $_hero_trad_pk]);

        // hero_trade  판매 완료 상태 변경
        $r = $this->PgGame->query('UPDATE hero_trade SET trade_complete = $2, now_value = $3 WHERE hero_trad_pk = $1', [$_hero_trad_pk, 'Y', $_bid_value]);
        if (!$r) {
            return false;
        }

        // 시세 등록
        $this->PgGame->query('UPDATE m_hero SET trade_total_count = trade_total_count + 1, trade_total_value = trade_total_value + $2 WHERE m_hero_pk = $1', [$_trade_info['m_hero_pk'], $_bid_value]);

        // 보고서
        $this->classReport();

        $z_content = [];

        // 판매자
        $z_content['min_value'] = $_trade_info['min_value'];
        $z_content['max_value'] = $_trade_info['max_value'];
        $z_content['sold_value'] = $_bid_value;
        $z_content['commission'] = $commission;
        $hero_name = $_trade_info['hero_name'] . ' (Lv.'.$_trade_info['level'] .')';
        $z_content['result'] = '화면 우측 영웅거래 하단의 황금 출금 버튼을 클릭하면 판매 대금 인출이 가능합니다.';
        $z_content['hero'] = ['m_pk' => $_trade_info['m_hero_pk']];
        $z_content['success'] = 'suc';

        // from & to
        $z_from = ['posi_name' => $_trade_info['lord_name']];
        $z_to = ['lord_name' => $hero_name, 'posi_name' => $hero_name];

        // title & summary
        $z_title = '';
        $z_summary = $hero_name;

        $this->Report->setReport($_trade_info['lord_pk'], 'misc', 'hero_trade_sale_success', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));

        // 구매자
        $z_content['min_value'] = $_trade_info['min_value'];
        $z_content['max_value'] = $_trade_info['max_value'];
        $z_content['sold_value'] = $_bid_value;
        $z_content['result'] = '화면 우측의 영웅관리 메뉴를 통해서 낙찰 영웅을 등용할 수 있습니다.';
        $z_content['hero'] = ['m_pk' => $_trade_info['m_hero_pk']];
        $z_content['success'] = 'success';
        // from & to
        $this->PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $z_from = ['posi_name' => $this->PgGame->fetchOne()];
        $z_to = ['lord_name' => $hero_name, 'posi_name' => $hero_name];

        // title & summary - 위와 같음

        $this->Report->setReport($_lord_pk, 'misc', 'hero_trade_bid_success', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));

        $this->classLog();
        $desc = 'enchant:'.$_trade_info['enchant'].';leadership:'.$_trade_info['leadership'].';mil_force:'.$_trade_info['mil_force'].';intellect:'.$_trade_info['intellect'].';politics:'.$_trade_info['politics'].';charm:'.$_trade_info['charm'].';m_hero_skil_pk_1:'.$_trade_info['m_hero_skil_pk_1'].';m_hero_skil_pk_2:'.$_trade_info['m_hero_skil_pk_2'].';m_hero_skil_pk_3:'.$_trade_info['m_hero_skil_pk_3'].';m_hero_skil_pk_4:'.$_trade_info['m_hero_skil_pk_4'].';m_hero_skil_pk_5:'.$_trade_info['m_hero_skil_pk_5'].';m_hero_skil_pk_6:'.$_trade_info['m_hero_skil_pk_6'].';skill_exp:'.$_trade_info['skill_exp'];
        $this->Log->setHeroTrade($_lord_pk, null, 'bid_success', $_trade_info['hero_pk'], $_trade_info['hero_name'], $_trade_info['level'], $_trade_info['min_value'], $_trade_info['max_value'], $_bid_value, $commission, $_hero_trad_pk, $_trade_info['hero_trad_bid_pk'], $_trade_info['sale_period'], $_trade_info['password'], $desc, $_trade_info['lord_pk']);

        return true;
    }

    function setHeroTradeBidFailure($_lord_pk, $_hero_trad_pk, $_yn_sale): void
    {
        // 입찰 금액 환불
        // 구매횟수 차감
        // 보고서 보내기
        // 입찰 금액
        $this->PgGame->query('SELECT bid_value FROM hero_trade_bid WHERE lord_pk = $1 AND hero_trad_pk = $2', [$_lord_pk, $_hero_trad_pk]);
        $_gold = $this->PgGame->fetchOne();

        // 입찰 금액 환불
        $this->setHeroTradeGold($_lord_pk, $_gold, $_hero_trad_pk);

        // 구매 횟수 차감
        $query_params = [$_lord_pk];
        $this->PgGame->query('SELECT trade_bid_cnt FROM lord WHERE lord_pk = $1', $query_params);
        if ($this->PgGame->fetchOne() > 0) {
            $this->PgGame->query('UPDATE lord SET trade_bid_cnt = trade_bid_cnt - 1 WHERE lord_pk = $1', $query_params);
        }

        // 구매자
        $_trade_info = $this->getHeroTradeInfo($_hero_trad_pk);
        $hero_name = $_trade_info['hero_name'] . ' (Lv.'.$_trade_info['level'] .')';

        $z_content['min_value'] = $_trade_info['min_value'];
        $z_content['max_value'] = $_trade_info['max_value'];
        $z_content['my_bid_value'] = $_gold;
        $z_content['sold_value'] = ($_yn_sale == 'N') ? '-' : $_trade_info['now_value'];
        $z_content['result'] = ' 입찰 참여금 ' . $_gold . ' 이 반납 되었습니다.';
        $z_content['hero'] = ['m_pk' => $_trade_info['m_hero_pk']];
        $z_content['success'] = 'fail';
        // from & to
        $this->PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $z_from = ['posi_name' => $this->PgGame->fetchOne()];
        $z_to = ['lord_name' => $hero_name, 'posi_name' => $hero_name];

        // title & summary
        $z_title = '';
        $z_summary = $hero_name;

        $this->classReport();
        $this->Report->setReport($_lord_pk, 'misc', 'hero_trade_bid_fail', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));

        // Log
        $this->classLog();
        $desc = 'enchant:'.$_trade_info['enchant'].';leadership:'.$_trade_info['leadership'].';mil_force:'.$_trade_info['mil_force'].';intellect:'.$_trade_info['intellect'].';politics:'.$_trade_info['politics'].';charm:'.$_trade_info['charm'].';m_hero_skil_pk_1:'.$_trade_info['m_hero_skil_pk_1'].';m_hero_skil_pk_2:'.$_trade_info['m_hero_skil_pk_2'].';m_hero_skil_pk_3:'.$_trade_info['m_hero_skil_pk_3'].';m_hero_skil_pk_4:'.$_trade_info['m_hero_skil_pk_4'].';m_hero_skil_pk_5:'.$_trade_info['m_hero_skil_pk_5'].';m_hero_skil_pk_6:'.$_trade_info['m_hero_skil_pk_6'].';skill_exp:'.$_trade_info['skill_exp'];
        $this->Log->setHeroTrade($_lord_pk, null, 'bid_failure', $_trade_info['hero_pk'], $_trade_info['hero_name'], $_trade_info['level'], $_trade_info['min_value'], $_trade_info['max_value'], $_gold, null, $_hero_trad_pk, null, $_trade_info['sale_period'], $_trade_info['password'], $desc);
    }

    function setHeroTradeSellFailure($_hero_trad_pk, $_trade_info): void
    {
        $interval = 60 * 60 * 24 * 30;
        $r = $this->PgGame->query("UPDATE my_hero SET status = $2, timedjob_dt = now() + interval '$interval second' WHERE hero_pk = $1", [$_trade_info['hero_pk'], 'V']);

        $this->PgGame->query('UPDATE hero SET yn_re_guest = $2 WHERE hero_pk = $1', [$_trade_info['hero_pk'], 'Y']);

        // hero_trade  판매 완료 상태 변경
        $r = $this->PgGame->query('UPDATE hero_trade SET trade_complete = $2 WHERE hero_trad_pk = $1', [$_hero_trad_pk, 'Y']);

        // 보고서
        $this->classReport();
        $_trade_info = $this->getHeroTradeInfo($_hero_trad_pk);
        $hero_name = $_trade_info['hero_name'] . ' (Lv.'.$_trade_info['level'] .')';

        $z_content = [];
        // 판매자
        $z_content['min_value'] = $_trade_info['min_value'];
        $z_content['max_value'] = $_trade_info['max_value'];
        $z_content['sold_value'] = '-';
        $z_content['bid_value'] = '-'; // $_trade_info['now_value'] TODO yn_sale을 받지 않길래 일단 빼둠
        //$z_content['result'] = '영웅 거래 하단의 황금 출금 버튼을 클릭하면 판매 대금 인출이 가능합니다.';
        $z_content['hero'] = ['m_pk' => $_trade_info['m_hero_pk']];
        $z_content['success'] = 'fai';
        // from & to
        $z_from = ['posi_name' => $_trade_info['lord_name']];
        $z_to = ['lord_name' => $hero_name, 'posi_name' => $hero_name];

        // title & summary
        $z_title = '';
        $z_summary = $hero_name;

        $this->Report->setReport($_trade_info['lord_pk'], 'misc', 'hero_trade_sale_fail', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));

        // Log
        $this->classLog();
        $desc = 'enchant:'.$_trade_info['enchant'].';leadership:'.$_trade_info['leadership'].';mil_force:'.$_trade_info['mil_force'].';intellect:'.$_trade_info['intellect'].';politics:'.$_trade_info['politics'].';charm:'.$_trade_info['charm'].';m_hero_skil_pk_1:'.$_trade_info['m_hero_skil_pk_1'].';m_hero_skil_pk_2:'.$_trade_info['m_hero_skil_pk_2'].';m_hero_skil_pk_3:'.$_trade_info['m_hero_skil_pk_3'].';m_hero_skil_pk_4:'.$_trade_info['m_hero_skil_pk_4'].';m_hero_skil_pk_5:'.$_trade_info['m_hero_skil_pk_5'].';m_hero_skil_pk_6:'.$_trade_info['m_hero_skil_pk_6'].';skill_exp:'.$_trade_info['skill_exp'];
        $this->Log->setHeroTrade($_trade_info['lord_pk'], null, 'sell_failure', $_trade_info['hero_pk'], $_trade_info['hero_name'], $_trade_info['level'], $_trade_info['min_value'], $_trade_info['max_value'], $_trade_info['now_value'], null, $_hero_trad_pk, null, $_trade_info['sale_period'], $_trade_info['password'], $desc, $_trade_info['lord_pk']);
    }

    function getCommission($_sale_period, $_bid_value): int|float
    {
        return match ($_sale_period) {
            6 => ceil($_bid_value * 0.03),
            24 => ceil($_bid_value * 0.05),
            72 => ceil($_bid_value * 0.1),
            default => 0
        };
    }

    function getHeroTradeNowValue($_hero_trad_pk): int
    {
        $this->PgGame->query('SELECT now_value FROM hero_trade WHERE hero_trad_pk = $1', [$_hero_trad_pk]);
        return $this->PgGame->fetchOne();
    }

    function getHeroTradeMyBidValue($_lord_pk, $_hero_trad_pk): int
    {
        $this->PgGame->query('SELECT bid_value FROM hero_trade_bid WHERE lord_pk = $1 AND hero_trad_pk = $2', [$_lord_pk, $_hero_trad_pk]);
        return $this->PgGame->fetchOne();
    }

    function initHeroBidCount($_lord_pk): array
    {
        $left_bid_cnt = $this->getTradeBidCount($_lord_pk);
        if (!$left_bid_cnt) {
            return ['err' => '아이템을 사용해도 효과가 없습니다.'];
        }
        $r = $this->PgGame->query('UPDATE lord SET trade_bid_cnt = 0 WHERE lord_pk = $1', [$_lord_pk]);
        if (!$r) {
            return ['err' => '아이템 사용 실패'];
        }

        // Log
        $this->classLog();
        $this->Log->setHeroTrade($_lord_pk, null, 'init_hero_bid_count_item');

        return ['trade_bid_cnt' => 0, 'm_item_pk' => 500155];
    }

    function setHeroTradeGold($_lord_pk, $_gold, $_hero_trad_pk): bool
    {
        // 골드 넣기 전에 확인
        $this->PgGame->query('SELECT lord_pk FROM hero_trade_gold WHERE lord_pk = $1 FOR UPDATE', [$_lord_pk]);
        $lord_pk = $this->PgGame->fetchOne();

        $prev_gold = 0;
        if ($lord_pk) {
            // 존재할 때
            $this->PgGame->query('SELECT gold FROM hero_trade_gold WHERE lord_pk = $1 GROUP BY gold', [$lord_pk]);
            $prev_gold = $this->PgGame->fetchOne();
            $_gold = $prev_gold + $_gold;
            if ($_gold > 900000000) {
                $_gold = 900000000;
            }
            $r = $this->PgGame->query('UPDATE hero_trade_gold SET gold = $2 WHERE lord_pk = $1', [$lord_pk, $_gold]);
        } else {
            // 존재하지 않을 때
            if ($_gold > 900000000) {
                $_gold = 900000000;
            }
            $r = $this->PgGame->query('INSERT INTO hero_trade_gold (lord_pk, gold) VALUES ($1, $2)', [$_lord_pk, $_gold]);
        }

        if (!$r) {
            return false;
        }

        // Log
        $this->classLog();
        $this->Log->setHeroTrade($_lord_pk, null, 'incr_gold', null, null, null, null, null, $_gold, null, $_hero_trad_pk, null, null, null, 'before:'.$prev_gold);

        return true;
    }

    function decrHeroTradeGold($_lord_pk, $_gold): bool
    {
        // 골드 받기전에 확인
        $this->PgGame->query('SELECT lord_pk FROM hero_trade_gold WHERE lord_pk = $1 FOR UPDATE', [$_lord_pk]);
        $lord_pk = $this->PgGame->fetchOne();
        if ($lord_pk) {
            $this->PgGame->query('UPDATE hero_trade_gold SET gold = gold - $2 WHERE lord_pk = $1', [$lord_pk, $_gold]);
            // Log
            $this->classLog();
            $this->Log->setHeroTrade($_lord_pk, null, 'decr_gold', null, null, null, null, null, $_gold);
        } else {
            return false;
        }
        return true;
    }

    function setHeroTradeSellCancel($_lord_pk, $_hero_pk, $_hero_trad_pk, $_hero_trade_info): bool
    {
        $interval = 60 * 60 * 24 * 30;
        $r = $this->PgGame->query("UPDATE my_hero SET status = $3, timedjob_dt = now() + interval '$interval second' WHERE lord_pk = $1 AND hero_pk = $2", [$_lord_pk, $_hero_pk, 'V']);
        if (!$r) {
            return false;
        }

        $r = $this->PgGame->query('UPDATE hero SET yn_re_guest = $2 WHERE hero_pk = $1', [$_hero_pk, 'Y']);
        if (!$r) {
            return false;
        }

        $r = $this->PgGame->query('DELETE FROM hero_trade WHERE hero_trad_pk = $1 AND lord_pk = $2', [$_hero_trad_pk, $_lord_pk]);
        if (!$r) {
            return false;
        }

        // Log
        $this->classLog();
        $desc = 'enchant:'.$_hero_trade_info['enchant'].';leadership:'.$_hero_trade_info['leadership'].';mil_force:'.$_hero_trade_info['mil_force'].';intellect:'.$_hero_trade_info['intellect'].';politics:'.$_hero_trade_info['politics'].';charm:'.$_hero_trade_info['charm'].';m_hero_skil_pk_1:'.$_hero_trade_info['m_hero_skil_pk_1'].';m_hero_skil_pk_2:'.$_hero_trade_info['m_hero_skil_pk_2'].';m_hero_skil_pk_3:'.$_hero_trade_info['m_hero_skil_pk_3'].';m_hero_skil_pk_4:'.$_hero_trade_info['m_hero_skil_pk_4'].';m_hero_skil_pk_5:'.$_hero_trade_info['m_hero_skil_pk_5'].';m_hero_skil_pk_6:'.$_hero_trade_info['m_hero_skil_pk_6'].';skill_exp:'.$_hero_trade_info['skill_exp'];
        $this->Log->setHeroTrade($_lord_pk, null, 'sell_hero_cancel', $_hero_pk, $_hero_trade_info['hero_name'], $_hero_trade_info['level'], $_hero_trade_info['min_value'], $_hero_trade_info['max_value'], $_hero_trade_info['now_value'], null, $_hero_trad_pk, null, $_hero_trade_info['sale_period'], $_hero_trade_info['password'], $desc);

        return true;
    }
}