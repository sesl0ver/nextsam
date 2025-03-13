// 다이얼로그
ns_dialog.dialogs.hero_card = new nsDialogSet('hero_card', 'dialog_trans', 'size-card');
ns_dialog.dialogs.hero_card.main_card = null;
ns_dialog.dialogs.hero_card.card_list = [];
ns_dialog.dialogs.hero_card.hide_button = false;
ns_dialog.dialogs.hero_card.cacheContents = function()
{
    this.cont_obj.card_slot = new nsObject('.card_slot', this.obj);
}

ns_dialog.dialogs.hero_card.draw = function (_e)
{
    try {
        if (! ns_util.isNumeric(this.data)) {
            this.main_card = new nsCard(this.data?.hero_pk, { data: this.data });
        } else {
            this.main_card = new nsCard(this.data, { data: ns_cs.d.hero[this.data] });
        }

        if (this.hide_button) {
            this.main_card.disableFlipping();
            ns_button.buttons.hero_card_prev.obj.hide();
            ns_button.buttons.hero_card_next.obj.hide();

            ns_button.buttons.hero_card_flipping.obj.hide();
            ns_button.buttons.hero_card_tobe_guest.obj.hide();
            ns_button.buttons.hero_card_group.obj.hide();
        } else {
            this.main_card.enableFlipping();
            ns_button.buttons.hero_card_prev.obj.show();
            ns_button.buttons.hero_card_next.obj.show();

            ns_button.buttons.hero_card_flipping.obj.show();
            ns_button.buttons.hero_card_tobe_guest.obj.show();
            ns_button.buttons.hero_card_group.obj.show();
        }

        this.cont_obj.card_slot.empty().append(this.main_card.getCard());
    } catch (e) {
        console.error(e);
        ns_dialog.close(this.tag_id);
    }
}

ns_dialog.dialogs.hero_card.erase = function ()
{
    this.data = null;
    this.hide_button = false;
    this.main_card = null;
}

ns_dialog.dialogs.hero_card.getHeroData = function ()
{
    let dialog = ns_dialog.dialogs.hero_card;
    if (! dialog.main_card) {
        return (ns_util.isNumeric(dialog.data)) ? ns_cs.d.hero[dialog.data] : dialog.data;
    } else {
        return dialog.main_card.getData();
    }
}

ns_dialog.dialogs.hero_card.forRedraw = function (_data, _status) {
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.hero_card;
    if (_data?.['type'] === 'dismiss') {
        delete ns_cs.d.hero[dialog.data.hero_pk]; // 해임 후 영웅데이터에서 제외 TODO 필요한가?
    }
    ns_hero.deckReload();
    if (ns_dialog.dialogs.hero_manage.visible) {
        ns_dialog.dialogs.hero_manage.drawList();
    }
    if (ns_dialog.dialogs.hero_manage_combination.visible) {
        ns_dialog.dialogs.hero_manage_combination.drawTab();
    }
    if (_data?.hero_info) {
        dialog.main_card.update(_data?.hero_info);
    } else {
        dialog.close();
    }
}

/* button */
ns_button.buttons.hero_card_sub_close = new nsButtonSet('hero_card_sub_close', 'button_full', 'hero_card');
ns_button.buttons.hero_card_sub_close.mouseUp = function (_e)
{
    let dialog = ns_dialog.dialogs.hero_card;
    if (dialog.main_card.isBlocking() === true) {
        return;
    }
    dialog.main_card.erase();
    ns_dialog.close('hero_card');
}

ns_button.buttons.hero_card_flipping = new nsButtonSet('hero_card_flipping', 'button_card_sub', 'hero_card');
ns_button.buttons.hero_card_flipping.mouseUp = function (_e)
{
    let dialog = ns_dialog.dialogs.hero_card;
    if (dialog.main_card.isBlocking() === true) {
        return;
    }
    dialog.main_card.flipping();
}

ns_button.buttons.hero_card_use_again = new nsButtonSet('hero_card_use_again', 'button_card_sub', 'hero_card');
ns_button.buttons.hero_card_use_again.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_card;

    // clearTimeout(dialog.setTimer); // TODO 음?

    ns_dialog.close('hero_card');
    // ns_button.buttons.item_use_ok.mouseUp(_e);
}

ns_button.buttons.hero_card_tobe_guest = new nsButtonSet('hero_card_tobe_guest', 'button_card_sub', 'hero_card');
ns_button.buttons.hero_card_tobe_guest.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_card;
    let d = dialog.getHeroData();
    if (! d) {
        dialog.close();
        return;
    }

    let post_data = {};
    post_data['hero_pk'] = d.hero_pk;

    ns_xhr.post('/api/heroManage/tobeGuest', post_data, (_data, _status) => {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_hero.deckReload();
        if (ns_dialog.dialogs.hero_manage.visible) {
            ns_dialog.dialogs.hero_manage.drawList();
        }
        if (ns_dialog.dialogs.hero_manage_combination.visible) {
            ns_dialog.dialogs.hero_manage_combination.drawTab();
        }
        ns_dialog.close('hero_card');
    }, { useProgress: true });
}

ns_button.buttons.hero_card_tobe_appoint = new nsButtonSet('hero_card_tobe_appoint', 'button_card_sub', 'hero_card');
ns_button.buttons.hero_card_tobe_appoint.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_card;
    let d = dialog.getHeroData();
    if (! d) {
        dialog.close();
        return;
    }

    if (ns_hero.checkSameHero(ns_cs.m.hero[d.m_hero_pk].m_hero_base_pk)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_same_own_hero')); // 동일한 영웅을 이미 보유 중입니다.
        return;
    }

    let limit_time = ns_util.math(d.last_dismiss_dt).plus(86400).number;
    if (ns_util.math(limit_time).gt(ns_timer.now())) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_left_time_hero_appoint', [ns_util.getCostsTime(ns_util.math(limit_time).minus(ns_timer.now()).number)]));
        return;
    }

    let post_data = { };
    post_data['action'] = 'tobe_appoint';
    post_data['hero_pk'] = d.hero_pk;

    const callbackFunction = function (_m_offi_pk)
    {
        post_data['m_offi_pk'] = _m_offi_pk;
        ns_xhr.post('/api/heroManage/tobeAppoint', post_data, dialog.forRedraw, { useProgress: true });
    }

    ns_dialog.setDataOpen('hero_officer', { m_offi_pk: null, callbackFunc: callbackFunction });
}

ns_button.buttons.hero_card_tobe_abandon = new nsButtonSet('hero_card_tobe_abandon', 'button_card_sub', 'hero_card');
ns_button.buttons.hero_card_tobe_abandon.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_card;
    let d = dialog.getHeroData();
    if (! d) {
        dialog.close();
        return;
    }

    let post_data = {};
    post_data['action'] = 'tobe_abandon';
    post_data['hero_pk'] = d.hero_pk;

    const okFunction = function()
    {
        ns_xhr.post('/api/heroManage/tobeAbandon', post_data, dialog.forRedraw, { useProgress: true });
    }

    // 방출한 영웅은 사라지며<br />언제 다시 영입할 수 있을지<br />기약할 수 없습니다.<br /><br /><br />방출 하시겠습니까?
    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_hero_abandon_confirm'), okFunc: okFunction });
}

ns_button.buttons.hero_card_enchant = new nsButtonSet('hero_card_enchant', 'button_card_sub', 'hero_card');
ns_button.buttons.hero_card_enchant.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_card;
    let d = dialog.getHeroData();
    if (! d) {
        dialog.close();
        return;
    }

    ns_dialog.setDataOpen('card_enchant', d);
}

ns_button.buttons.hero_card_skill = new nsButtonSet('hero_card_skill', 'button_card_sub', 'hero_card');
ns_button.buttons.hero_card_skill.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_card;
    let d = dialog.getHeroData();
    if (! d) {
        dialog.close();
        return;
    }

    ns_dialog.setDataOpen('hero_skill_manage', d);
}

ns_button.buttons.hero_card_group = new nsButtonSet('hero_card_group', 'button_card_sub', 'hero_card');
ns_button.buttons.hero_card_group.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_card;
    let d = dialog.getHeroData();
    if (! d) {
        dialog.close();
        return;
    }

    ns_dialog.setDataOpen('card_group', d);
}

ns_button.buttons.hero_card_prev = new nsButtonSet('hero_card_prev', 'button_card_prev', 'hero_card', { play_effect: false });
ns_button.buttons.hero_card_prev.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_card;

    let _prev;
    let list = (dialog.card_list.length > 0) ? dialog.card_list : ns_hero.current_sorted_array;
    let _current = list.findIndex(k => ns_util.math(k?.hero_pk ?? k).eq(dialog.data.hero_pk ?? dialog.data));
    if (_current - 1 < 0) {
        _prev = list.at(-1);
    } else {
        _prev = list[_current - 1];
    }
    ns_sound.play('page');
    dialog.data = _prev;
    dialog.draw();
}

ns_button.buttons.hero_card_next = new nsButtonSet('hero_card_next', 'button_card_next', 'hero_card', 'hero_card', { play_effect: false });
ns_button.buttons.hero_card_next.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_card;

    let _next;
    let list = (dialog.card_list.length > 0) ? dialog.card_list : ns_hero.current_sorted_array;
    let _current = list.findIndex(k => ns_util.math(k?.hero_pk ?? k).eq(dialog.data.hero_pk ?? dialog.data));
    if (_current + 1 >= list.length) {
        _next = list.at(0);
    } else {
        _next = list[_current + 1];
    }
    ns_sound.play('page');
    dialog.data = _next;
    dialog.draw();
}

ns_dialog.dialogs.card_prize = new nsDialogSet('card_prize', 'dialog_pop', 'size-small', { do_close_all: false });

ns_dialog.dialogs.card_prize.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);

    this.cont_obj.employment_fee = new nsObject('.employment_fee', this.obj);
    this.cont_obj.max_prize = new nsObject('.max_prize', this.obj);
}

ns_dialog.dialogs.card_prize.draw = function()
{
    ns_button.buttons.card_prize_value.obj.text(0); // 초기화

    let max_prize = ns_util.math(ns_cs.m.offi[this.data.m_offi_pk].employment_fee).mul(5).integer;
    this.cont_obj.employment_fee.text(ns_util.numberFormat(ns_cs.m.offi[this.data.m_offi_pk].employment_fee));
    this.cont_obj.max_prize.text(max_prize);
}

ns_dialog.dialogs.card_prize.returnValue = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];
    if(_data) {
        if(_data.type !== 'prize') {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_hero_prize_remain_time', [ns_util.getCostsTime(ns_util.math(14400).minus(_data.remain_dt).integer)])); // 다음 포상은<br /><strong>{{1}}</strong> 후 가능합니다.
        } else {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_hero_prize_complete', [_data.loyalty])); // 포상을 하여 영웅의 충성도가 <br /><br />{{1}} 만큼 상승하였습니다. (최대 99까지)
            ns_dialog.setData('hero_card', _data.hero_info);
            ns_dialog.dialogs.hero_card.main_card.update(_data.hero_info); // 카드 다시 그리기
            ns_dialog.dialogs.hero_manage.drawList(); // 영웅 리스트 다시 그리기
            ns_dialog.close('card_prize');
        }
    }

}

/* ************************************************** */

ns_button.buttons.card_prize_close = new nsButtonSet('card_prize_close', 'button_pop_close', 'card_prize', { base_class:ns_button.buttons.common_close });
ns_button.buttons.card_prize_sub_close = new nsButtonSet('card_prize_sub_close', 'button_full', 'card_prize', { base_class: ns_button.buttons.common_sub_close });

/* ************************* */

ns_button.buttons.card_prize_value = new nsButtonSet('card_prize_value', 'button_input', 'card_prize');
ns_button.buttons.card_prize_value.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.card_prize;
    let max_prize = ns_util.math(ns_cs.m.offi[dialog.data.m_offi_pk].employment_fee).mul(5).integer;
    ns_dialog.setDataOpen('keypad', { max: max_prize, min: 0,
        callback: function(data){
            ns_button.buttons.card_prize_value.obj.text(data);
        }
    });
}

ns_button.buttons.card_prize_updown = new nsButtonSet('card_prize_updown', 'button_arrow_updown', 'card_prize');
ns_button.buttons.card_prize_updown.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.card_prize;
    let card_prize_value = ns_button.buttons.card_prize_value.obj;
    let max_prize = ns_util.math(ns_cs.m.offi[dialog.data.m_offi_pk].employment_fee).mul(5).integer;

    if (! ns_util.isNumeric(card_prize_value.text()) || ns_util.math(card_prize_value.text()).lt(max_prize)) {
        card_prize_value.text(max_prize);
    } else {
        card_prize_value.text(0);
    }
}

ns_button.buttons.card_prize_submit = new nsButtonSet('card_prize_submit', 'button_default', 'card_prize');
ns_button.buttons.card_prize_submit.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.card_prize;
    let gold = ns_util.toInteger(ns_button.buttons.card_prize_value.obj.text());
    let max_prize = ns_util.math(ns_cs.m.offi[dialog.data.m_offi_pk].employment_fee).mul(5).integer;

    if(ns_util.math(gold).eq(0)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_hero_prize_gold_empty')); // 포상금을 입력해주세요.
        ns_button.buttons.card_prize_value.obj.text(0);
        return;
    } else if (ns_util.math(gold).gt(max_prize)){
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_hero_prize_gold_max')); // 포상할 수 있는 최대 금액보다 큽니다.
        return;
    }

    let post_data = {};
    post_data['action'] = 'prize';
    post_data['hero_pk'] = dialog.data.hero_pk;
    post_data['gold'] = gold;

    ns_xhr.post('/api/heroDetail/prize', post_data, dialog.returnValue, { useProgress: true });
}

/* ************************************************** */

ns_dialog.dialogs.card_enchant = new nsDialogSet('card_enchant', 'dialog_pop', 'size-medium', { do_close_all: false });
ns_dialog.dialogs.card_enchant.hero_pk = null;
ns_dialog.dialogs.card_enchant.select_item_pk = null;

ns_dialog.dialogs.card_enchant.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);

    this.cont_obj.enchant_count = new nsObject('.enchant_count', this.obj);
    this.cont_obj.enchant_per = new nsObject('.enchant_per', this.obj);
    // this.cont_obj.enchant_hero_lost_per = new nsObject('.enchant_hero_lost_per', this.obj);

    this.cont_obj.enchant_item = new nsObject('.enchant_item', this.obj);
    this.cont_obj.enchant_item_have = new nsObject('.enchant_item_have', this.obj);
    this.cont_obj.enchant_gold = new nsObject('.enchant_gold', this.obj);
    this.cont_obj.enchant_gold_have = new nsObject('.enchant_gold_have', this.obj);

    // this.cont_obj.build_time = new nsObject('.build_time', this.obj);
    this.cont_obj.selected_item = new nsObject('.selected_item', this.obj);
    this.cont_obj.enchant_caution_msg = new nsObject('#enchant_caution_msg', this.obj);
}

ns_dialog.dialogs.card_enchant.draw = function()
{
    if (! this.visible) {
        this.select_item_pk = null;
    }

    let data = this.data;
    this.cont_obj.enchant_caution_msg.html(ns_i18n.t('hero_enhance_description')); // 아이템을 사용하지 않고 강화 성공할 경우<br>임의의 능력치(+3) 중 한 개가 상승하게 됩니다.

    this.hero_pk = data.hero_pk;
    let enchant = data.enchant;
    let enchant_cnt = ns_util.math(enchant).plus(1).number;

    this.cont_obj.enchant_count.text(enchant);

    if (ns_util.math(enchant).lt(10)) {
        let plus_rate = 0;
        for (let [k, d]  of Object.entries(ns_cs.d.time)) {
            if (! ns_util.isNumeric(k)) {
                continue;
            }
            // 이벤트 아이템 적용 (일단 보류)
            if (typeof d.description !== 'undefined' && (String(d.description).substring(7) === 'event_enchant' || String(d.description).substring(7) === 'special_enchant') && d.status === 'P') {
                if (d.posi_pk !== ns_engine.game_data.cpp) {
                    continue;
                }
                plus_rate += 10;
            }
        }
        //plus_rate
        let enchant_rate = `${this.enchantSuccessPercent(enchant_cnt)+plus_rate}%`;

        let span = document.createElement('span');
        span.innerText = enchant_rate;
        this.cont_obj.enchant_per.empty().append(span);
        if (ns_util.math(plus_rate).gt(0)) {
            span = document.createElement('span');
            span.classList.add('text_gray');
            span.innerText = `(+${plus_rate}%)`;
            this.cont_obj.enchant_per.append(span);
        }
        // this.cont_obj.enchant_hero_lost_per.text('0%');
        this.cont_obj.enchant_item.text(this.enchantNeedItem(enchant_cnt, data.rare_type));
        this.cont_obj.enchant_gold.text(ns_util.numberFormat(this.enchantNeedPrice(enchant_cnt, data.rare_type)));
        this.cont_obj.enchant_gold_have.text(ns_util.numberFormat(ns_cs.getTerritoryInfo('gold_curr')));
        ns_button.buttons.card_enchant_submit.setEnable();
    } else {
        this.cont_obj.enchant_per.text('-');
        // this.cont_obj.enchant_hero_lost_per.text('-');
        this.cont_obj.enchant_item.text('-');
        this.cont_obj.enchant_gold.text('-');
        this.cont_obj.enchant_gold_have.text('-');
        ns_button.buttons.card_enchant_submit.setDisable();
    }

    ns_button.buttons.card_enchant_select_item.obj.text(ns_i18n.t('choose')); // 선택

    this.cont_obj.enchant_item_have.text(ns_cs.d.item['500085'] ? ns_util.numberFormat(ns_cs.d.item['500085'].item_cnt) : 0);
}

ns_dialog.dialogs.card_enchant.enchantSuccessPercent = function(enchant_cnt)
{
    let enchant_per = 0;
    switch(enchant_cnt) {
        case 1  : enchant_per = 90;		break;
        case 2  : enchant_per = 80;		break;
        case 3  : enchant_per = 70;		break;
        case 4  : enchant_per = 50;		break;
        case 5  : enchant_per = 45;		break;
        case 6  : enchant_per = 40;		break;
        case 7  : enchant_per = 20;		break;
        case 8  : enchant_per = 15;		break;
        case 9  : enchant_per = 10;		break;
        case 10 : enchant_per = 5;		break;
    }
    return enchant_per;
}

ns_dialog.dialogs.card_enchant.enchant_lost_percent = function(enchant_cnt)
{
    let enchant_per = 0;
    switch(enchant_cnt) {
        case 6  : enchant_per = 60;		break;
        case 7  : enchant_per = 80;		break;
        case 8  : enchant_per = 85;		break;
        case 9  : enchant_per = 90;		break;
        case 10 : enchant_per = 95;		break;
    }
    return enchant_per;
}

ns_dialog.dialogs.card_enchant.enchantNeedItem = function(enchant_cnt, rare_type)
{
    let enchant_item = 1;
    rare_type = parseInt(rare_type);

    switch(rare_type) {
        case 1  :
            switch(enchant_cnt) {
                case 10 :  enchant_item = 2; break;
            }
            break;
        case 2  :
            switch(enchant_cnt) {
                case 9 :  enchant_item = 2; break;
                case 10 :  enchant_item = 2; break;
            }
            break;
        case 3  :
            switch(enchant_cnt) {
                case 8 :  enchant_item = 2; break;
                case 9 :  enchant_item = 2; break;
                case 10 :  enchant_item = 2; break;
            }
            break;
        case 4  :
            switch(enchant_cnt) {
                case 7 :  enchant_item = 2; break;
                case 8 :  enchant_item = 2; break;
                case 9 :  enchant_item = 2; break;
                case 10 :  enchant_item = 2; break;
            }
            break;
        case 5  :
            switch(enchant_cnt) {
                case 6 :  enchant_item = 2; break;
                case 7 :  enchant_item = 2; break;
                case 8 :  enchant_item = 2; break;
                case 9 :  enchant_item = 2; break;
                case 10 :  enchant_item = 3; break;
            }
            break;
        case 6  :
            switch(enchant_cnt) {
                case 5 :  enchant_item = 2; break;
                case 6 :  enchant_item = 2; break;
                case 7 :  enchant_item = 2; break;
                case 8 :  enchant_item = 2; break;
                case 9 :  enchant_item = 3; break;
                case 10 :  enchant_item = 3; break;
            }
            break;
        case 7  :
            switch(enchant_cnt) {
                case 4 :  enchant_item = 2; break;
                case 5 :  enchant_item = 2; break;
                case 6 :  enchant_item = 2; break;
                case 7 :  enchant_item = 2; break;
                case 8 :  enchant_item = 3; break;
                case 9 :  enchant_item = 3; break;
                case 10 :  enchant_item = 3; break;
            }
            break;
    }
    return enchant_item;
}

ns_dialog.dialogs.card_enchant.enchantNeedPrice = function(enchant_cnt, rare_type)
{
    let enchant_price = 0;
    rare_type = parseInt(rare_type);
    switch(rare_type) {
        case 1 : enchant_price = 4900; break;
        case 2 : enchant_price = 5600; break;
        case 3 : enchant_price = 6300; break;
        case 4 : enchant_price = 7000; break;
        case 5 : enchant_price = 8400; break;
        case 6 : enchant_price = 9800; break;
        case 7 : enchant_price = 11200; break;
    }
    return ns_util.math(enchant_price).mul(enchant_cnt).integer;
}

ns_dialog.dialogs.card_enchant.enchant_cost_time = function(enchant_cnt)
{
    let enchant_cost_time = 0;
    switch(enchant_cnt) {
        case 1  : enchant_cost_time = 10; break;
        case 2  : enchant_cost_time = 10; break;
        case 3  : enchant_cost_time = 10; break;
        case 4  : enchant_cost_time = 10; break;
        case 5  : enchant_cost_time = 10; break;
        case 6  : enchant_cost_time = 10; break;
        case 7  : enchant_cost_time = 10; break;
        case 8  : enchant_cost_time = 10; break;
        case 9  : enchant_cost_time = 10; break;
        case 10 : enchant_cost_time = 10; break;
    }
    return enchant_cost_time;
}

/*
ns_dialog.dialogs.card_enchant.timerHandler = function(_recursive)
{
	if (this.base_class && !_recursive)
	{
		let ret = this.base_class.timerHandler.call(this, true);
		this.timerHandle_p = ret;
	}

	let timerId = this.tagId + '_real';

	qbw_timer.timers[timerId] = new qbw_timer_class(ns_dialog.dialogs.card_enchant.timerHandler_proc_real, 9999, 500, true);
	qbw_timer.timers[timerId].init();

	return qbw_timer.timers[timerId];
}

ns_dialog.dialogs.card_enchant.timerHandler_proc_real = function()
{
	let dialog = ns_dialog.dialogs.card_enchant;
	let d = false;
	if (typeof qbw_cs.d.hero[dlg.hero_pk] == 'object')
	{
		d = qbw_cs.d.hero[dlg.hero_pk];
	} else if (typeof dlg.data == 'object') {
		d = dlg.data;
	} else {
		return false;
	}

	let enchant_cnt = parseInt(d.enchant) + 1;
	let cond_pass = true;

	if (parseInt(d.enchant, 10) >= 10)
	{
		dlg.s.cont_build_time.html(qbw_cs.text.coststime + qbw_cs.text.hero_detail_enchant_coststime);
	} else {
		dlg.s.cont_build_time.html(qbw_cs.text.coststime + ' : ' + qbw_util_getCostsTime(dlg.enchant_cost_time(enchant_cnt)));
	}
}*/
/* ************************************************** */

ns_button.buttons.card_enchant_close = new nsButtonSet('card_enchant_close', 'button_pop_close', 'card_enchant', { base_class: ns_button.buttons.common_close });
ns_button.buttons.card_enchant_sub_close = new nsButtonSet('card_enchant_sub_close', 'button_full', 'card_enchant', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.card_enchant_submit = new nsButtonSet('card_enchant_submit', 'button_pop_normal_2', 'card_enchant');
ns_button.buttons.card_enchant_submit.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.card_enchant;
    let d = null;

    // ns_dialog.dialogs.card.s.cont_enchant_result.hide();
    // ns_dialog.dialogs.card.s.cont_enchant_result_value.hide();

    if (ns_cs.d.hero[dialog.hero_pk]) {
        d = ns_cs.d.hero[dialog.hero_pk];
    } else if (dialog.data) {
        d = dialog.data;
    } else {
        return;
    }

    let post_data = {};
    post_data['hero_pk'] = d.hero_pk;
    post_data['item_pk'] = dialog.select_item_pk;

    ns_xhr.post('/api/heroDetail/enchant', post_data, function (_data, _status) {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];
        ns_dialog.close('card_enchant');

        let result_message = '';

        if (_data?.result === true) {
            // dialog.prev_lud = 0;
            ns_dialog.dialogs.hero_card.main_card.update(_data.hero_info);
            // TODO 이건 그냥 sqAppend로 처리하는게 맞지 않나? 확인 필요.
            /*if (ns_cs.d.hero[_data.hero_info.hero_pk]) {
                ns_cs.d.hero[_data.hero_info.hero_pk] = _data.hero_info; // 영웅 데이터 업데이트
            }*/
            if (ns_dialog.dialogs.hero_manage.visible) {
                ns_dialog.dialogs.hero_manage.drawList();
            }
            result_message = ns_i18n.t('msg_hero_enhance_success'); // 강화에 성공하였습니다.<br />다음 능력치가 증가합니다.

            // 임시
            for (let key in _data.enchant_stat ){
                if ( ns_util.isNumeric(_data.enchant_stat[key]) && ns_util.toInteger(_data.enchant_stat[key]) !== 0) {
                    result_message += `<br />${code_set.hero_enchant[key]} : ${_data.enchant_stat[key]}`;
                }
            }
        } else {
            result_message = ns_i18n.t('msg_hero_enhance_failed'); // 강화에 실패하였습니다.
        }
        ns_dialog.setDataOpen('message', result_message);
    }, { useProgress: true });
}

ns_button.buttons.card_enchant_init = new nsButtonSet('card_enchant_init', 'button_pop_normal', 'card_enchant');
ns_button.buttons.card_enchant_init.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.card_enchant;
    let d = null;

    // ns_dialog.dialogs.card.s.cont_enchant_result.hide();
    // ns_dialog.dialogs.card.s.cont_enchant_result_value.hide();

    if (ns_cs.d.hero[dialog.hero_pk]) {
        d = ns_cs.d.hero[dialog.hero_pk];
    } else if (dialog.data) {
        d = dialog.data;
    } else {
        return;
    }

    // 강화를 한번도 하지 않는 영웅은 할 수 없음
    if (d.enchant && ns_util.math(d.enchant).lt(1)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_hero_enchant_init_item_error')); // [강화 초기화] 아이템은 강화를 한번도 하지<br/>않은 영웅 카드에는 사용하실 수 없습니다.
        return;
    }

    if (d.status_cmd && d.status_cmd !== 'I') {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_hero_enchant_init_status_error')); // 강화 초기화" 아이템은 영웅이 대기 중<br/>상태 일때 사용하실 수 있습니다.
        return;
    }

    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_hero_enchant_init_confirm'), // 강화를 초기화하면 해당 영웅의<br/>강화횟수가 0이 되지만 강화로 인해<br/>상승된 능력치는 모두 초기화됩니다.<br/><br/>"강화 초기화"아이템을 사용하시겠습니까?
        okFunc: () =>
        {
            ns_dialog.setDataOpen('item_quick_use', { type: 'enchant_init',
                callbackFunc: () =>
                {
                    let post_data = {};
                    post_data['hero_pk'] = d.hero_pk;
                    ns_xhr.post('/api/heroDetail/enchantInit', post_data, function(_data, _status)
                    {
                        if(! ns_xhr.returnCheck(_data)) {
                            return;
                        }
                        _data = _data['ns_xhr_return']['add_data'];

                        ns_dialog.setDataOpen('message', ns_i18n.t('msg_hero_enchant_init_complete')); // 영웅의 강화수치가 모두 초기화되었습니다.
                        ns_dialog.dialogs.hero_card.main_card.update(_data.hero_info);
                        if (ns_dialog.dialogs.hero_manage.visible) {
                            ns_dialog.dialogs.hero_manage.drawList();
                        }
                        ns_dialog.close('card_enchant');
                    });
                }
            }, { useProgress: true });
        }
    });

}

ns_button.buttons.card_enchant_select_item = new nsButtonSet('card_enchant_select_item', 'button_select_box', 'card_enchant');
ns_button.buttons.card_enchant_select_item.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.card_enchant;
    ns_dialog.setDataOpen('item_quick_use', { type:'enchant',
        callbackFunc: (_m_item_pk) =>
        {
            dialog.select_item_pk = _m_item_pk;
            ns_button.buttons.card_enchant_select_item.obj.text(ns_cs.m.item[_m_item_pk].title);
            dialog.cont_obj.enchant_caution_msg.html(ns_cs.m.item[_m_item_pk].description_quickuse);
        }
    });
}


/* ************************************************** */

ns_dialog.dialogs.card_group = new nsDialogSet('card_group', 'dialog_pop', 'size-medium', { do_close_all: false });
ns_dialog.dialogs.card_group.current_tab = null;
ns_dialog.dialogs.card_group.current_tab_assign = null;
ns_dialog.dialogs.card_group.current_tab_cmd = null;
ns_dialog.dialogs.card_group.sorted = null;
ns_dialog.dialogs.card_group.buttons = [];

ns_dialog.dialogs.card_group.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);

    this.cont_obj.card_group_tab_battle = new nsObject('.card_group_tab_battle', this.obj);
    this.cont_obj.card_group_tab_affair = new nsObject('.card_group_tab_affair', this.obj);

    this.cont_obj.tbody = new nsObject('tbody', this.obj);
}

ns_dialog.dialogs.card_group.draw = function()
{
    if (! this.visible) {
        ns_button.toggleGroupSingle(ns_button.buttons.card_group_tab_battle);
        ns_button.toggleGroupSingle(ns_button.buttons.card_group_tab_assign_captain);
        ns_button.toggleGroupSingle(ns_button.buttons.card_group_tab_cmd_encounter);
        this.current_tab = 'battle';
        this.current_tab_assign = 'captain';
        this.current_tab_cmd = 'encounter';
    }
    this.drawTab();
}

ns_dialog.dialogs.card_group.drawTab = function()
{
    let dialog = ns_dialog.dialogs.card_group;
    let group_type = null, tab_sub;
    let hide_tab = (dialog.current_tab === 'battle') ? 'affair' : 'battle';
    dialog.cont_obj['card_group_tab_' + hide_tab].hide();
    dialog.cont_obj['card_group_tab_' + dialog.current_tab].show();

    tab_sub = (dialog.current_tab === 'battle') ? dialog.current_tab_assign : dialog.current_tab_cmd;
    group_type = this.getGroupTypeCode(tab_sub);

    let post_data = {};
    post_data['group_type'] = group_type;
    ns_xhr.post('/api/heroDetail/getHeroGroup', post_data, this.drawRemote);
}

ns_dialog.dialogs.card_group.drawRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.card_group;

    let arr = ns_button.toggleGroupValue('card_group_tab')[0].split('_tab_');
    let tab = arr.pop();
    let group_type = null, tab_sub;

    tab_sub = (dialog.current_tab === 'battle') ? dialog.current_tab_assign : dialog.current_tab_cmd;
    group_type = dialog.getGroupTypeCode(tab_sub);

    // 목록 리스트 캐싱
    dialog.cont_obj.tbody.empty();
    for (let i of Array.from({length: 9}, (_, i) => i + 1)) {
        try {
            let d = _data[i] ?? null;
            let tr = document.createElement('tr');
            let m_hero, m_hero_base, hero_name = '-', hero_posi = '-'
            if (d) {
                m_hero = ns_cs.m.hero[d['m_hero_pk']];
                m_hero_base = ns_cs.m.hero_base[ns_cs.m.hero[d['m_hero_pk']]['m_hero_base_pk']];
                hero_name = m_hero_base['name'] + ' Lv.' + m_hero['level'];
                hero_posi = (!d['title']) ? '-' : d['title'];
            }

            let col1 = document.createElement('td');
            col1.innerHTML = dialog.getGroupTypeName(tab_sub) + ' ' + i;

            let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
            button.setAttribute('id', `ns_button_hero_card_group_${i}`);
            col1.append(button);

            let col2 = document.createElement('td');
            col2.innerHTML = hero_name;

            let col3 = document.createElement('td');
            col3.innerHTML = hero_posi;

            tr.appendChild(col1);
            tr.appendChild(col2);
            tr.appendChild(col3);

            dialog.cont_obj.tbody.append(tr);

            ns_button.buttons[`hero_card_group_${i}`] = new nsButtonSet(`hero_card_group_${i}`, 'button_table', 'card_group');
            ns_button.buttons[`hero_card_group_${i}`].mouseUp = function ()
            {
                if (! d) {
                    ns_dialog.dialogs.card_group.setHeroGroup(group_type, i);
                } else {
                    ns_dialog.dialogs.card_group.unsetHeroGroup(d.hero_pk);
                }
            }
        } catch (e) {
            console.error(e);
        }
    }
};

ns_dialog.dialogs.card_group.setHeroGroup = function(group_type, group_order)
{
    let dialog = ns_dialog.dialogs.card_group;

    let post_data = { };
    post_data['hero_pk'] = dialog.data.hero_pk;
    post_data['group_type'] = group_type;
    post_data['group_order'] = group_order;

    ns_xhr.post('/api/heroDetail/setHeroGroup', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.drawTab();
        ns_dialog.dialogs.hero_card.main_card.update(_data.hero_info);
        ns_dialog.dialogs.hero_manage.drawList();
    }, { useProgress: true });
};

ns_dialog.dialogs.card_group.unsetHeroGroup = function(hero_pk, e)
{
    let dialog = ns_dialog.dialogs.card_group;
    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_hero_group_unset_confirm'), // 해당 그룹에 지정된 영웅을 해제하시겠습니까?
        okFunc:function()
        {
            let post_data = {};
            post_data['hero_pk'] = hero_pk;
            ns_xhr.post('/api/heroDetail/unsetHeroGroup', post_data, function(_data, _status)
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];

                dialog.drawTab();
                ns_dialog.dialogs.hero_card.main_card.update(_data.hero_info);
                ns_dialog.dialogs.hero_manage.drawList();
            });
        }
    }, { useProgress: true });
};

ns_dialog.dialogs.card_group.getGroupType = function(typename)
{
    typename = typename || 'C';
    if (typename === 'C') {
        return 'captain';
    } else if (typename === 'D') {
        return 'director';
    } else if (typename === 'S') {
        return 'staff';
    } else if (typename === 'E') {
        return 'encounter';
    } else if (typename === 'B') {
        return 'build';
    } else if (typename === 'T') {
        return 'technique';
    } else {
        return false;
    }
}

ns_dialog.dialogs.card_group.getGroupCodeToName = function(code)
{
    code = code || 'C';
    let dialog = ns_dialog.dialogs.card_group;
    return dialog.getGroupTypeName(dialog.getGroupType(code));
}

ns_dialog.dialogs.card_group.getGroupTypeName = function(tab_name)
{
    tab_name = tab_name || 'captain';
    if (tab_name === 'captain') {
        return code_set.troop_hero.captain; // 주장
    } else if (tab_name === 'director') {
        return code_set.troop_hero.director; // 부장
    } else if (tab_name === 'staff') {
        return code_set.troop_hero.staff; // 참모
    } else if (tab_name === 'encounter') {
        return code_set.hero_cmd_type.Encou; // 탐색
    } else if (tab_name === 'build') {
        return code_set.hero_cmd_type.Const; // 건설
    } else if (tab_name === 'technique') {
        return ns_i18n.t('technique'); // 기술
    } else {
        return code_set.troop_hero.captain; // 주장
    }
}

ns_dialog.dialogs.card_group.getGroupTypeCode = function(tab_name)
{
    tab_name = tab_name || 'captain';
    if (tab_name === 'captain') {
        return 'C';
    } else if (tab_name === 'director') {
        return 'D';
    } else if (tab_name === 'staff') {
        return 'S';
    } else if (tab_name === 'encounter') {
        return 'E';
    } else if (tab_name === 'build') {
        return 'B';
    } else if (tab_name === 'technique') {
        return 'T';
    } else {
        return 'C';
    }
}
/* ************************************************** */

ns_button.buttons.card_group_close = new nsButtonSet('card_group_close', 'button_pop_close', 'card_group', {base_class:ns_button.buttons.common_close});
ns_button.buttons.card_group_sub_close = new nsButtonSet('card_group_sub_close', 'button_full', 'card_group', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.card_group_tab_battle = new nsButtonSet('card_group_tab_battle', 'button_tab', 'card_group', {toggle_group:'card_group_tab'});
ns_button.buttons.card_group_tab_battle.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.card_group;
    dialog.current_tab = this.tag_id.split('_tab_').pop();

    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.drawTab();
}
ns_button.buttons.card_group_tab_affair = new nsButtonSet('card_group_tab_affair', 'button_tab', 'card_group', {base_class:ns_button.buttons.card_group_tab_battle, toggle_group:'card_group_tab'});

ns_button.buttons.card_group_tab_assign_captain = new nsButtonSet('card_group_tab_assign_captain', 'button_tab_sub', 'card_group', {toggle_group:'card_group_tab_assign'});
ns_button.buttons.card_group_tab_assign_captain.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.card_group;
    dialog.current_tab_assign = this.tag_id.split('_tab_assign_').pop();

    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.drawTab();
}
ns_button.buttons.card_group_tab_assign_director = new nsButtonSet('card_group_tab_assign_director', 'button_tab_sub', 'card_group', {base_class:ns_button.buttons.card_group_tab_assign_captain, toggle_group:'card_group_tab_assign'});
ns_button.buttons.card_group_tab_assign_staff = new nsButtonSet('card_group_tab_assign_staff', 'button_tab_sub', 'card_group', {base_class:ns_button.buttons.card_group_tab_assign_captain, toggle_group:'card_group_tab_assign'});

ns_button.buttons.card_group_tab_cmd_encounter = new nsButtonSet('card_group_tab_cmd_encounter', 'button_tab_sub', 'card_group', { toggle_group: 'card_group_tab_cmd'});
ns_button.buttons.card_group_tab_cmd_encounter.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.card_group;
    dialog.current_tab_cmd = this.tag_id.split('_tab_cmd_').pop();

    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.drawTab();
}
ns_button.buttons.card_group_tab_cmd_build = new nsButtonSet('card_group_tab_cmd_build', 'button_tab_sub', 'card_group', { base_class: ns_button.buttons.card_group_tab_cmd_encounter, toggle_group: 'card_group_tab_cmd' });
ns_button.buttons.card_group_tab_cmd_technique = new nsButtonSet('card_group_tab_cmd_technique', 'button_tab_sub', 'card_group', { base_class: ns_button.buttons.card_group_tab_cmd_encounter, toggle_group: 'card_group_tab_cmd' });

