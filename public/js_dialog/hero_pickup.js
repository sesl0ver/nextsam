ns_dialog.dialogs.hero_pickup = new nsDialogSet('hero_pickup', 'dialog_full', 'size-full');
ns_dialog.dialogs.hero_pickup.pickup_type = null;
ns_dialog.dialogs.hero_pickup.max_index = 0;
ns_dialog.dialogs.hero_pickup.current_index = null;

ns_dialog.dialogs.hero_pickup.cacheContents = function()
{
    this.cont_obj.hero_pickup_list = new nsObject('.hero_pickup_list', this.obj);
    this.cont_obj.test_card = new nsObject('.test_card', this.obj);

    this.cont_obj.hero_pickup_rule_wrap = new nsObject('.hero_pickup_rule_wrap', this.obj);

    this.cont_obj.hero_ticket_500075 = new nsObject('.hero_ticket_500075', this.obj);
    this.cont_obj.hero_ticket_500103 = new nsObject('.hero_ticket_500103', this.obj);

    this.cont_obj.skeleton_pickup_area_wrap = new nsObject('#skeleton_pickup_area_wrap');
}

ns_dialog.dialogs.hero_pickup.draw = function()
{
    this.drawPickup();
}

ns_dialog.dialogs.hero_pickup.erase = function ()
{
    this.data = null;
}

ns_dialog.dialogs.hero_pickup.drawPickup = function()
{
    let dialog = ns_dialog.dialogs.hero_pickup;
    let m_pick = ns_cs.m.pick;

    this.cont_obj.hero_pickup_list.empty();
    this.buttonClear();
    for (let m of Object.values(m_pick)) {
        let _item_cnt = ns_cs.d.item[m.m_item_pk]?.item_cnt ?? 0;

        let wrap = this.cont_obj.skeleton_pickup_area_wrap.clone();
        wrap.addCss(`pickup_type_${m.pickup_type}`).addCss('pickup_type');
        if (! m.pity_hero) {
            wrap.find('.pickup_remain_time').remove();
            wrap.find('.pickup_remain_count').remove();
            wrap.addCss('normal_hero');
        } else {
            let p = JSON.parse(m['pity_hero'].toString());
            wrap.addCss(`hero_${p[0]}`);
        }

        let button_id = `pickup_preview_${m.pickup_type}`;
        wrap.find('.button_pickup_preview').setAttribute('id', `ns_button_${button_id}`);

        let single_button = new nsObject(document.createElement('span'));
        single_button.setAttribute('id', `ns_button_single_pickup_${m.pickup_type}`);

        let single_text = new nsObject(document.createElement('span'));
        single_text.text(ns_i18n.t('hero_pickup_one')); // 모집 1회

        /*let single_ticket = new nsObject(document.createElement('span'));
        single_ticket.addCss('ticket_wrap');

        let single_ticket_icon = new nsObject(document.createElement('span'));
        single_ticket_icon.addCss(`ticket_icon_${m.pickup_type}`);

        let single_ticket_count = new nsObject(document.createElement('span'));
        let single_ticket_span = document.createElement('span');
        single_ticket_span.classList.add('current_count');
        single_ticket_count.addCss('ticket_count').append(single_ticket_span).html(`/<span>1</span>`, true);

        single_ticket.append(single_ticket_icon).append(single_ticket_count);
        single_button.append(single_text).append(single_ticket);*/
        single_button.append(single_text);

        let multiple_button = new nsObject(document.createElement('span'));
        multiple_button.setAttribute('id', `ns_button_multiple_pickup_${m.pickup_type}`);

        let multiple_text = new nsObject(document.createElement('span'));
        multiple_text.html(ns_i18n.t('hero_pickup_ten')); // 모집 10회

        /*let multiple_ticket = new nsObject(document.createElement('span'));
        multiple_ticket.addCss('ticket_wrap');

        let multiple_ticket_icon = new nsObject(document.createElement('span'));
        multiple_ticket_icon.addCss(`ticket_icon_${m.pickup_type}`);

        let multiple_ticket_count = new nsObject(document.createElement('span'));
        let multiple_ticket_span = document.createElement('span');
        multiple_ticket_span.classList.add('current_count');
        multiple_ticket_count.addCss('ticket_count').append(multiple_ticket_span).html(`/<span>10</span>`, true);

        multiple_ticket.append(multiple_ticket_icon).append(multiple_ticket_count);
        multiple_button.append(multiple_text).append(multiple_ticket);*/
        multiple_button.append(multiple_text);

        wrap.find('.pickup_buttons').append(single_button).append(multiple_button);

        this.cont_obj.hero_pickup_list.append(wrap);

        // preview 버튼
        ns_button.buttons[button_id] = new nsButtonSet(button_id, null, 'hero_pickup');
        ns_button.buttons[button_id].mouseUp = function ()
        {
            let pickup_type = (ns_util.math(m.pickup_type).gte(2)) ? 2 : 1;
            ns_dialog.setDataOpen('hero_pickup_preview', { pickup_type: pickup_type });
        }
        this.buttons.push(ns_button.buttons[button_id]);

        // 버튼 정의
        for (let button_id of [`single_pickup_${m.pickup_type}`, `multiple_pickup_${m.pickup_type}`]) {
            ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_pickup_submit', 'hero_pickup');
            ns_button.buttons[button_id].mouseUp = () =>
            {
                let type = button_id.split('_')[0];
                let count = (type === 'single') ? 1 : 10;
                let confirm_text = ns_i18n.t('msg_hero_pickup_confirm', [count]); // {{1}}회 모집을 진행 하시겠습니까?
                if (ns_util.math(_item_cnt).lt(count)) {
                    let _price = ns_util.math(m.need_qbig).mul(count).number;
                    confirm_text += `<br /><br />${ns_i18n.t('msg_hero_pickup_use_qbig_confirm', [_price])}`; // 티켓이 부족하여 <span class="content_item_qbig_amount">{{1}}</span>을 소비합니다.
                }
                ns_dialog.setDataOpen('confirm', { text: confirm_text,
                    okFunc: () =>
                    {
                        this.pickupRequest(m.pickup_type, button_id.split('_')[0]);
                    }
                });
            }
            dialog.buttons.push(ns_button.buttons[button_id]);
        }
    }

    // coming soon 임의 작업( 하위항목 hide 처리 )
    let wrap = this.cont_obj.skeleton_pickup_area_wrap.clone();
    wrap.addCss('pickup_type').addCss('none');
    wrap.find('.button_pickup_preview').remove();
    wrap.find('.pickup_buttons').remove();
    wrap.find('.pickup_remain_time').remove();
    wrap.find('.pickup_remain_count').remove();
    this.cont_obj.hero_pickup_list.append(wrap);
}

ns_dialog.dialogs.hero_pickup.pickupRequest = function (_pickup_type, _type)
{
    let m_pickup = ns_cs.m.pick[_pickup_type];
    if (! m_pickup) {
        return;
    }
    let need_item_use = !!m_pickup.m_item_pk;
    let need_qbig_use = !ns_util.math(m_pickup.need_qbig).eq(0);
    if (!need_item_use && !need_qbig_use) {
        return; // 둘 다 사용하지 않는 경우는 없으므로
    }
    let pickup_count = (_type === 'multiple') ? 10 : 1;
    let need_item_count = (_type === 'multiple') ? ns_util.math(m_pickup.item_count).mul(pickup_count).number : Number(m_pickup.item_count); // 10회 뽑기에 경우 * 10
    let need_qbig = ns_util.math(m_pickup.need_qbig).mul(pickup_count).number;

    if (need_item_use) {
        let item_count = ns_cs.d.item[m_pickup.m_item_pk]?.item_cnt ?? 0;
        // 아이템이 부족한데 큐빅으로 구매가 불가능한 경우
        if (ns_util.math(item_count).lt(need_item_count)) {
            if (! need_qbig_use) {
                ns_dialog.setDataOpen('message', ns_i18n.t('msg_hero_pickup_need_ticket')); // 영웅 모집에 필요한 아이템이 부족합니다.
                return;
            }
        } else {
            need_qbig_use = false; // 아이템이 충분하면 큐빅은 사용하지 않음.
        }
    }
    if (need_qbig_use) { // 큐빅 사용이 필요한 경우
        let qbig = ns_cs.d.cash['qbig']?.v ?? 0;
        if (ns_util.math(qbig).lt(need_qbig)) {
            ns_dialog.setDataOpen('confirm', { text : ns_i18n.t('msg_buy_qbig_confirm'), // 큐빅이 부족합니다.<br /><br />큐빅을 구매하시겠습니까?
                okFunc : () => {
                    ns_engine.buyQbig();
                }
            });
            return;
        }
    }

    ns_xhr.post('/api/hero/pickup', { type: _type, pickup_type: _pickup_type }, function (_data, _status) {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_dialog.setDataOpen('hero_pickup_card', { pickup_list: Object.values(_data.heroes) });
        ns_dialog.close('hero_pickup');
    }, { useProgress: true });
}

ns_dialog.dialogs.hero_pickup.timerHandler = function()
{
    let timer_id = this.tag_id;

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.hero_pickup.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.hero_pickup.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.hero_pickup;

    let today = ns_util.getDateFormatMs(Date.now(), 'YYYY-MM-DD HH:mm:ss', true);

    let m_pick = ns_cs.m.pick;
    for (let m of Object.values(m_pick)) {
        let now_time = ns_util.getTimestamp(today);
        let pickup = dialog.cont_obj.hero_pickup_list.find(`.pickup_type_${m.pickup_type}`);
        if (m.start_date !== null) {
            let start_time = ns_util.getTimestamp(`${m.start_date} 00:00:00`);
            let end_time = ns_util.getTimestamp(`${m.end_date} 00:00:00`);
            if (ns_util.math(start_time).gt(now_time) || ns_util.math(end_time).lte(now_time)) {
                pickup.hide();
            } else {
                pickup.show();
            }
        }
        if (m['pity_hero']) {
            let remain = pickup.find('.pickup_remain_count .remain_description');
            if (remain.element) {
                let remain_count = ns_util.math(m['pity_limit']).minus(ns_cs.d.pickup?.[m.pickup_type]?.v ?? 0).number;
                remain.text(ns_i18n.t('hero_pickup_remain_count_description', [remain_count]));
                let remain_time = pickup.find('.pickup_remain_time span');
                let end_time = ns_util.getTimestamp(`${m.end_date} 00:00:00`);
                let remain_seconds = ns_util.math(end_time).minus(now_time).number;
                remain_time.text(ns_util.getCostsTime(remain_seconds, 'event_end'));
            }
        }
    }

    dialog.cont_obj.hero_ticket_500075.text(ns_cs.d.item?.[500075]?.item_cnt ?? 0);
    dialog.cont_obj.hero_ticket_500103.text(ns_cs.d.item?.[500103]?.item_cnt ?? 0);
}

/* ************************************************** */

ns_button.buttons.hero_pickup_close = new nsButtonSet('hero_pickup_close', 'button_back', 'hero_pickup', { base_class: ns_button.buttons.common_close });
ns_button.buttons.hero_pickup_sub_close = new nsButtonSet('hero_pickup_sub_close', 'button_full', 'hero_pickup', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.hero_pickup_close_all = new nsButtonSet('hero_pickup_close_all', 'button_close_all', 'hero_pickup', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.pickup_tooltip = new nsButtonSet('pickup_tooltip', null, 'hero_pickup');
ns_button.buttons.pickup_tooltip.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.hero_pickup;
    if (dialog.cont_obj.hero_pickup_rule_wrap.hasCss('hide')) {
        dialog.scroll_handle.initScroll();
        dialog.scroll_handle.pause();
        dialog.cont_obj.hero_pickup_rule_wrap.show();
    } else {
        dialog.scroll_handle.resume();
        dialog.cont_obj.hero_pickup_rule_wrap.hide();
    }
}
ns_button.buttons.pickup_tooltip_close = new nsButtonSet('pickup_tooltip_close', 'button_tooltip_close', 'hero_pickup', { base_class: ns_button.buttons.pickup_tooltip });

ns_button.buttons.pickup_item_buy_500075 = new nsButtonSet('pickup_item_buy_500075', 'button_charge_ticket_1', 'hero_pickup');
ns_button.buttons.pickup_item_buy_500075.mouseUp = function ()
{
    ns_dialog.setDataOpen('item', { tab: 'hero', m_item_pk: 500075 });
}

ns_button.buttons.pickup_item_buy_500103 = new nsButtonSet('pickup_item_buy_500103', 'button_charge_ticket_2', 'hero_pickup');
ns_button.buttons.pickup_item_buy_500103.mouseUp = function ()
{
    ns_dialog.setDataOpen('item', { tab: 'hero', m_item_pk: 500103 });
}

ns_button.buttons.pickup_qbig_buy = new nsButtonSet('pickup_qbig_buy', 'button_empty', 'hero_pickup');
ns_button.buttons.pickup_qbig_buy.mouseUp = function ()
{
    ns_engine.buyQbig();
}

// 픽업 카드 애니메이션 다이어로그
ns_dialog.dialogs.hero_pickup_card = new nsDialogSet('hero_pickup_card', 'dialog_trans', 'size-card');
ns_dialog.dialogs.hero_pickup_card.main_card = null;
ns_dialog.dialogs.hero_pickup_card.cacheContents = function()
{
    this.cont_obj.card_slot = new nsObject('.card_slot', this.obj);
}

ns_dialog.dialogs.hero_pickup_card.draw = function()
{
    if (! this.data?.pickup_list || this.data.pickup_list.length < 1) {
        ns_dialog.close('hero_pickup_card');
        return;
    }
    this.data.pickup_list_backup = [...this.data.pickup_list]; // 결과창으로 넘겨주기 위해
    this.nextPickup();
}

ns_dialog.dialogs.hero_pickup_card.nextPickup = function ()
{
    if (this.main_card && this.main_card.checkPickupAnimation()) {
        return;
    }
    if (this.main_card && this.main_card.checkPickupCover()) {
        this.main_card.openPickup();
        return;
    }
    if (this.data.pickup_list.length < 1) {
        this.pickupResult();
        return;
    }
    let info = this.data.pickup_list.shift();
    this.main_card = new nsCard(info.hero_pk, { data: info, pickup_mode: true });
    this.cont_obj.card_slot.empty().append(this.main_card.getCard());
}

ns_dialog.dialogs.hero_pickup_card.pickupResult = function ()
{
    if (this.main_card && this.main_card.checkPickupAnimation()) {
        return;
    }
    ns_dialog.setDataOpen('hero_pickup_result', { pickup_list: this.data.pickup_list_backup });
    ns_dialog.close('hero_pickup_card');
}

ns_dialog.dialogs.hero_pickup_card.erase = function ()
{
    this.data = null
    this.main_card = null;
}

/* ************************************************** */
ns_button.buttons.hero_pickup_card_sub_close = new nsButtonSet('hero_pickup_card_sub_close', 'button_full', 'hero_pickup_card', { sound_mute: true });
ns_button.buttons.hero_pickup_card_sub_close.mouseUp = function ()
{
    ns_dialog.dialogs.hero_pickup_card.nextPickup();
}

ns_button.buttons.hero_pickup_card_skip = new nsButtonSet('hero_pickup_card_skip', 'button_big_1', 'hero_pickup_card');
ns_button.buttons.hero_pickup_card_skip.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.hero_pickup_card;
    if (dialog.main_card && dialog.main_card.checkPickupAnimation()) {
        return;
    }

    let _check = function ()
    {
        do {
            let _next_card = dialog.data.pickup_list.shift();
            if (! _next_card) {
                break;
            }
            if (ns_util.math(ns_hero.getRareType(_next_card.m_hero_pk)).gte(5)) {
                new Promise((resolve, reject) => {
                    dialog.main_card = new nsCard(_next_card.hero_pk, { data: _next_card, pickup_mode: true });
                    dialog.cont_obj.card_slot.empty().append(dialog.main_card.getCard());
                    resolve();
                }).then(() => {
                    dialog.main_card?.openPickup();
                });
                break;
            }
        } while (dialog.data.pickup_list.length > 0);
        if (dialog.data.pickup_list.length < 1) {
            dialog.pickupResult();
        }
    }

    if (ns_util.math(ns_hero.getRareType(dialog.main_card.data.m_hero_pk)).gte(6)) {
        if (dialog.main_card && dialog.main_card.checkPickupCover()) {
            dialog.main_card?.openPickup();
        } else {
            _check();
        }
    } else {
        _check();
    }
}

// 픽업 결과창
ns_dialog.dialogs.hero_pickup_result = new nsDialogSet('hero_pickup_result', 'dialog_full', 'size-full');
ns_dialog.dialogs.hero_pickup_result.m_package_pk = null;

ns_dialog.dialogs.hero_pickup_result.cacheContents = function ()
{
    this.cont_obj.hero_pickup_result_wrap = new nsObject('.hero_pickup_result_wrap', this.obj);
}

ns_dialog.dialogs.hero_pickup_result.draw = function ()
{
    this.drawPickupHero();
}

ns_dialog.dialogs.hero_pickup_result.erase = function ()
{
    this.data = null;
    if (this.m_package_pk) {
        ns_dialog.setDataOpen('package_popup', { m_package_pk: this.m_package_pk }); // 즉시 보여주기
        this.m_package_pk = null;
    }
}

ns_dialog.dialogs.hero_pickup_result.drawPickupHero = function (_data)
{
    let dialog = ns_dialog.dialogs.hero_pickup_result;

    dialog.cont_obj.hero_pickup_result_wrap.empty();
    try {
        let delay = 1000;
        for (let pickup of this.data.pickup_list) {
            let card = ns_hero.cardDraw(pickup.hero_pk, 'N', false, pickup, false, false, true);
            let rare_type = card.dataSet('rare_type');
            card.dataSet('delay', delay);
            delay = delay + 500;
            card.find('.hero_card_effect').addCss('frame_effect').addCss(`start`);
            card.setAttribute('id', `ns_button_hero_pickup_result_open_${pickup.hero_pk}`);
            dialog.cont_obj.hero_pickup_result_wrap.append(card);

            let button_id = `hero_pickup_result_open_${pickup.hero_pk}`;
            ns_button.buttons[button_id] = new nsButtonSet(button_id, null, 'hero_manage');
            ns_button.buttons[button_id].mouseUp = function ()
            {
                ns_dialog.dialogs.hero_card.hide_button = true;
                ns_dialog.setDataOpen('hero_card', pickup);
            }
            dialog.buttons.push(ns_button.buttons[button_id]);
            // 등장 이펙트
            card.setEvent('animationend', (_e) =>
            {
                if (_e.animationName === 'frame_start_y') {
                    card.find('.hero_card_effect').removeCss('start');
                    if (ns_util.math(rare_type).gte(4)) {
                        card.find('.hero_card_effect').addCss(`rare${rare_type}`);
                    }
                }
            });
        }
    } catch (e) {
        console.error(e);
    }
}

ns_button.buttons.hero_pickup_result_close = new nsButtonSet('hero_pickup_result_close', 'button_back', 'hero_pickup_result', { base_class: ns_button.buttons.common_close });
ns_button.buttons.hero_pickup_result_sub_close = new nsButtonSet('hero_pickup_result_sub_close', 'button_full', 'hero_pickup_result', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.hero_pickup_result_close_all = new nsButtonSet('hero_pickup_result_close_all', 'button_close_all', 'hero_pickup_result', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.hero_pickup_more = new nsButtonSet('hero_pickup_more', 'button_default', 'hero_pickup_result');
ns_button.buttons.hero_pickup_more.mouseUp = function ()
{
    ns_dialog.close('hero_pickup_result');
    ns_dialog.open('hero_pickup');
}


// 픽업 미리보기
ns_dialog.dialogs.hero_pickup_preview = new nsDialogSet('hero_pickup_preview', 'dialog_full', 'size-large');
ns_dialog.dialogs.hero_pickup_preview.previous_rare_type = null;
ns_dialog.dialogs.hero_pickup_preview.current_pickup_type = 1;

ns_dialog.dialogs.hero_pickup_preview.cacheContents = function ()
{
    for (let i of [1,2,3,4,5,6,7]) {
        this.cont_obj[`preview_list_${i}`] = new nsObject(`.preview_list.rare_${i}`, this.obj);
        this.cont_obj[`hero_acquired_rate_1_${i}`] = new nsObject(`.hero_acquired_rate_1_${i}`, this.obj);
        this.cont_obj[`hero_acquired_rate_2_${i}`] = new nsObject(`.hero_acquired_rate_2_${i}`, this.obj);
    }

    for (let m of Object.values(ns_cs.m.hero_base)) {
        let button_id = `preview_hero_${m.m_hero_base_pk}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, null, 'hero_pickup_preview', { base_class: ns_button.buttons.hero_pickup_preview_hero });
    }
}

ns_dialog.dialogs.hero_pickup_preview.draw = function ()
{
    this.current_pickup_type = this.data.pickup_type;
    ns_button.toggleGroupSingle(ns_button.buttons[`preview_type_${this.data.pickup_type}`]);
    ns_button.toggleGroupSingle(ns_button.buttons[`preview_rare_${this.data.pickup_type}`]);
    this.showList(this.current_pickup_type); // 서브탭은 일단 메인탭을 따라감.
}

ns_dialog.dialogs.hero_pickup_preview.showList = function (_rare_type)
{
    if (this?.previous_rare_type) {
        this.cont_obj[`preview_list_${this.previous_rare_type}`].hide();
        this.cont_obj[`hero_acquired_rate_${this.current_pickup_type}_${this.previous_rare_type}`].hide();
    }
    if (ns_util.math(this.current_pickup_type).eq(2)) { // 우수 영웅 모집
        ns_button.buttons.preview_rare_1.setDisable();
        ns_button.buttons.preview_rare_6.setEnable();
        ns_button.buttons.preview_rare_7.setDisable();
    } else { // 일반 영웅 모집
        ns_button.buttons.preview_rare_1.setEnable();
        ns_button.buttons.preview_rare_6.setDisable();
        ns_button.buttons.preview_rare_7.setDisable();
    }

    this.cont_obj[`preview_list_${_rare_type}`].show();
    this.cont_obj[`hero_acquired_rate_${this.current_pickup_type}_${_rare_type}`].show();
    this.previous_rare_type = _rare_type;
}

ns_dialog.dialogs.hero_pickup_preview.erase = function ()
{
    this.data = null;
    this.cont_obj[`hero_acquired_rate_${this.current_pickup_type}_${this.previous_rare_type}`].hide();
    this.current_pickup_type = 1;
}

ns_button.buttons.hero_pickup_preview_close = new nsButtonSet('hero_pickup_preview_close', 'button_back', 'hero_pickup_preview', { base_class: ns_button.buttons.common_close });
ns_button.buttons.hero_pickup_preview_sub_close = new nsButtonSet('hero_pickup_preview_sub_close', 'button_full', 'hero_pickup_preview', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.hero_pickup_preview_close_all = new nsButtonSet('hero_pickup_preview_close_all', 'button_close_all', 'hero_pickup_preview', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.preview_type_1 = new nsButtonSet('preview_type_1', 'button_tab', 'hero_pickup_preview', { toggle_group: 'hero_pickup_preview_tab' });
ns_button.buttons.preview_type_1.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.hero_pickup_preview;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.cont_obj[`hero_acquired_rate_${dialog.current_pickup_type}_${dialog.previous_rare_type}`].hide();
    dialog.current_pickup_type = this.tag_id.split('_').pop();
    let rare_type = dialog.current_pickup_type; // 대메뉴 에서는 레어타입도 따라감
    ns_button.toggleGroupSingle(ns_button.buttons[`preview_rare_${rare_type}`]);
    dialog.showList(rare_type);
}
ns_button.buttons.preview_type_2 = new nsButtonSet('preview_type_2', 'button_tab', 'hero_pickup_preview', { base_class: ns_button.buttons.preview_type_1, toggle_group: 'hero_pickup_preview_tab' });

ns_button.buttons.preview_rare_1 = new nsButtonSet('preview_rare_1', 'button_tab_sub', 'hero_pickup_preview', { toggle_group: 'hero_pickup_preview_tab_sub' });
ns_button.buttons.preview_rare_1.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.hero_pickup_preview;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    let rare_type = this.tag_id.split('_').pop();
    dialog.showList(rare_type);
}
ns_button.buttons.preview_rare_2 = new nsButtonSet('preview_rare_2', 'button_tab_sub', 'hero_pickup_preview', { base_class: ns_button.buttons.preview_rare_1, toggle_group: 'hero_pickup_preview_tab_sub' });
ns_button.buttons.preview_rare_3 = new nsButtonSet('preview_rare_3', 'button_tab_sub', 'hero_pickup_preview', { base_class: ns_button.buttons.preview_rare_1, toggle_group: 'hero_pickup_preview_tab_sub' });
ns_button.buttons.preview_rare_4 = new nsButtonSet('preview_rare_4', 'button_tab_sub', 'hero_pickup_preview', { base_class: ns_button.buttons.preview_rare_1, toggle_group: 'hero_pickup_preview_tab_sub' });
ns_button.buttons.preview_rare_5 = new nsButtonSet('preview_rare_5', 'button_tab_sub', 'hero_pickup_preview', { base_class: ns_button.buttons.preview_rare_1, toggle_group: 'hero_pickup_preview_tab_sub' });
ns_button.buttons.preview_rare_6 = new nsButtonSet('preview_rare_6', 'button_tab_sub', 'hero_pickup_preview', { base_class: ns_button.buttons.preview_rare_1, toggle_group: 'hero_pickup_preview_tab_sub' });
ns_button.buttons.preview_rare_7 = new nsButtonSet('preview_rare_7', 'button_tab_sub', 'hero_pickup_preview', { base_class: ns_button.buttons.preview_rare_1, toggle_group: 'hero_pickup_preview_tab_sub' });

ns_button.buttons.hero_pickup_preview_hero = new nsButtonSet('hero_pickup_preview_hero', null, 'hero_pickup_preview');
ns_button.buttons.hero_pickup_preview_hero.mouseUp = function(_e)
{
    let base_pk = this.tag_id.split('_').pop();
    ns_dialog.dialogs.hero_card.hide_button = true;
    ns_dialog.setDataOpen('hero_card', ns_hero.dummyData(base_pk));
}

ns_button.buttons.hero_pickup_preview_top = new nsButtonSet('hero_pickup_preview_top', null, 'hero_pickup_preview');
ns_button.buttons.hero_pickup_preview_top.mouseUp = function(_e)
{
    ns_dialog.dialogs.hero_pickup_preview.scroll_handle.initScrollTo('top');
}

ns_button.buttons.hero_pickup_preview_bottom = new nsButtonSet('hero_pickup_preview_bottom', null, 'hero_pickup_preview');
ns_button.buttons.hero_pickup_preview_bottom.mouseUp = function(_e)
{
    ns_dialog.dialogs.hero_pickup_preview.scroll_handle.initScrollTo('bottom');
}