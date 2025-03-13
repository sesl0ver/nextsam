// 다이얼로그
ns_dialog.dialogs.build_TradeDept = new nsDialogSet('build_TradeDept', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });
ns_dialog.dialogs.build_TradeDept.current_tab = null;
ns_dialog.dialogs.build_TradeDept.bid_min = 0.1;
ns_dialog.dialogs.build_TradeDept.offer_min = 0.1;
ns_dialog.dialogs.build_TradeDept.opentick = null;

ns_dialog.dialogs.build_TradeDept.cacheContents = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.cacheContents.call(this, true);
    }

    this.cont_obj.content_resource_type_offer = new nsObject('.content_resource_type_offer', this.obj);
    this.cont_obj.content_resource_type_bid = new nsObject('.content_resource_type_bid', this.obj);

    this.cont_obj.content_trade_table = new nsObject('table', this.obj);
}

ns_dialog.dialogs.build_TradeDept.draw = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.draw.call(this, true);
    }

    ns_button.toggleGroupSingle(ns_button.buttons.build_TradeDept_tab_food);
    this.drawTab();
}

ns_dialog.dialogs.build_TradeDept.drawTab = function()
{
    let dialog = ns_dialog.dialogs.build_TradeDept;
    let tab = ns_button.toggleGroupValue('build_TradeDept_tab')[0].split('_').pop();
    dialog.current_tab = tab;

    dialog.cont_obj.content_resource_type_offer.text(ns_i18n.t(`resource_${tab}`));
    dialog.cont_obj.content_resource_type_bid.text(ns_i18n.t(`resource_${tab}`));

    let d = ns_cs.d.bdic[dialog.data.castle_pk];

    if (ns_util.math(d.level).lt(1)) {
        ns_button.buttons.build_TradeDept_on_sell.setDisable();
        ns_button.buttons.build_TradeDept_on_buy.setDisable();
        ns_button.buttons.trad_build_TradeDept_delivery.setDisable();
        ns_button.buttons.trad_build_TradeDept_order.setDisable();
    } else {
        ns_button.buttons.build_TradeDept_on_sell.setEnable();
        ns_button.buttons.build_TradeDept_on_buy.setEnable();
        ns_button.buttons.trad_build_TradeDept_delivery.setEnable();
        ns_button.buttons.trad_build_TradeDept_order.setEnable();
    }

    // 초기화
    for (let i = 1 ; i <= 5; i++) {
        dialog.cont_obj.content_trade_table.find(`.content_resource_amount_offer${i}`).text('-');
        dialog.cont_obj.content_trade_table.find(`.content_resource_amount_bid${i}`).text('-');
        dialog.cont_obj.content_trade_table.find(`.content_unit_price_offer${i}`).text('-');
        dialog.cont_obj.content_trade_table.find(`.content_unit_price_bid${i}`).text('-');
    }

    let post_data = {};
    post_data['type'] = dialog.convertCode(tab);

    ns_xhr.post('/api/tradeDept/list', post_data, dialog.drawPrice);
}

ns_dialog.dialogs.build_TradeDept.drawPrice = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.build_TradeDept;

    let offer_list = [];
    let bid_list = [];

    for (let d of Object.values(_data)) {
        if (d.trade_type === 'B') {
            bid_list.push(d);
        } else {
            offer_list.push(d);
        }
    }

    // 구매 주문 리스트
    let bid_min = null;
    for (let [k, d] of Object.entries(bid_list)) {
        let i = ns_util.math(k).plus(1).number;
        dialog.cont_obj.content_trade_table.find(`.content_resource_amount_bid${i}`).text(ns_util.numberFormat(d.reso_amount));
        dialog.cont_obj.content_trade_table.find(`.content_unit_price_bid${i}`).text(ns_util.numberFormat(d.unit_price));
        if (! bid_min) {
            bid_min = d.unit_price;
        }
    }

    // 최소 구매 가격 저장
    dialog.bid_min = (bid_list.length < 1) ? 0.1 : bid_list[0].unit_price;

    // 판매 주문 리스트
    let offer_min = null;
    for (let [k, d] of Object.entries(offer_list)) {
        let i = ns_util.math(k).plus(1).number;
        dialog.cont_obj.content_trade_table.find(`.content_resource_amount_offer${i}`).text(ns_util.numberFormat(d.reso_amount));
        dialog.cont_obj.content_trade_table.find(`.content_unit_price_offer${i}`).text(ns_util.numberFormat(d.unit_price));
        if (! offer_min) {
            offer_min = d.unit_price;
        }
    }

    // 최소 판매 가격 저장
    dialog.offer_min = (offer_list.length < 1) ? 0.1 : offer_list[0].unit_price;
}

ns_dialog.dialogs.build_TradeDept.convertCode = function(char)
{
    // 자원 코드를 원래 문자열로 변환하거나 문자열로된 자원명을 자원 코드로 변환
    if (char === 'F') {
        return 'food';
    } else if (char === 'H') {
        return 'horse';
    } else if (char === 'L') {
        return 'lumber';
    } else if (char === 'I') {
        return 'iron';
    } else if (char.length > 1) {
        return String(char).substring(0, 1).toUpperCase();
    }
}

ns_dialog.dialogs.build_TradeDept.inputIntValue = function(_trade_type)
{
    let resource_amount = ns_button.buttons[`trade_${_trade_type}_resource_amount`].obj;

    // 정수만을 입력받는 수량을 위한 핸들러
    if (String(resource_amount.text()[0]) === '0' && resource_amount.text().length > 1) {
        resource_amount.text(ns_util.toInteger(resource_amount.text()));
    } else if (! ns_util.isNumeric(resource_amount.text()) && String(ns_util.toInteger(resource_amount.text(), 10)) !== resource_amount.text() ) {
        resource_amount.text(ns_util.toInteger(resource_amount.text()));
    } else if (resource_amount.text().length === 0 || !ns_util.isNumeric(resource_amount.text())) {
        resource_amount.text(0);
    }

    ns_dialog.dialogs.build_TradeDept.recalculate(_trade_type);
}

ns_dialog.dialogs.build_TradeDept.recalculate = function(_trade_type)
{
    let dialog = ns_dialog.dialogs['build_TradeDept_' + _trade_type];

    let resource_amount = ns_button.buttons[`trade_${_trade_type}_resource_amount`].obj;
    let unit_price = ns_button.buttons[`trade_${_trade_type}_unit_price`].obj;
    let resource_curr = ns_cs.getResourceInfo(dialog.data.resource_type + '_curr');
    let gold_curr = ns_cs.getTerritoryInfo('gold_curr');
    let total_gold = ns_util.math(resource_amount.text()).mul(unit_price.text()).toFixed(1);

    if (_trade_type === 'bid') {
        if (ns_util.math(total_gold).plus(Math.ceil(ns_util.math(total_gold).mul(0.1).number)).gt(gold_curr)) {
            // 총 구매금액 + 수수료가 현재 가진 금 보다 많다면 강제로 수량 조정
            resource_amount.text(Math.floor(ns_util.math(gold_curr).minus(Math.ceil(ns_util.math(gold_curr).mul(0.1).number)).div(unit_price.text()).number));
            total_gold = ns_util.math(resource_amount.text()).mul(unit_price.text()).number;
        }
        if (ns_util.math(unit_price.text()).gt(999999)) {
            unit_price.text(999999);
            total_gold = ns_util.math(resource_amount.text()).mul(unit_price.text()).number;
        }
        if (resource_amount.text().length > 8) {
            resource_amount.text('99999999');
            total_gold = ns_util.math(resource_amount.text()).mul(unit_price.text()).number;
        }

        if (ns_util.math(unit_price.text()).gt(gold_curr)) {
            // 단가가 현재 가진 금보다 많으면 현재 가진 금으로 강제 조정
            resource_amount.text(1);
            unit_price.text(gold_curr);
            total_gold = ns_util.math(resource_amount.text()).mul(unit_price.text()).number;
        }

        dialog.cont_obj.trade_bid_total_price.text(Math.ceil(total_gold));
        dialog.cont_obj.trade_bid_commission_price.text(Math.ceil(ns_util.math(total_gold).mul(0.1).number));
    } else if (_trade_type === 'offer') {

        if (ns_util.math(resource_amount.text()).gt(resource_curr)) {
            // 판매하려는 자원의 양이 현재 가진 자원의 양보다 많다면 자원 수량을 강제 고정
            resource_amount.text(resource_curr);
            total_gold = ns_util.math(resource_amount.text()).mul(unit_price.text()).number;
        }

        if (ns_util.math(unit_price.text()).gt(999999)) {
            // 판매 단가가 백만 자리 이상이면 최대로 강제 조정
            unit_price.text(999999);
            total_gold = ns_util.math(resource_amount.text()).mul(unit_price.text()).number;
        }

        if (ns_util.math(resource_amount.text()).gt(99999999)) {
            resource_amount.text('99999999');
            total_gold = ns_util.math(resource_amount.text()).mul(unit_price.text()).number;
        }

        if (ns_util.math(Math.ceil(ns_util.math(total_gold).mul(0.1).number)).gt(gold_curr)) {
            // 수수료가 현재 가진 금보다 많다면 자원량을 최대로 강제 조정
            resource_amount.text(Math.floor((ns_util.math(gold_curr).mul(10).number).div(unit_price.text())));
            total_gold = ns_util.math(resource_amount.text()).mul(unit_price.text()).number;
        }

        dialog.cont_obj.trade_offer_total_price.text(Math.ceil(total_gold));
        dialog.cont_obj.trade_offer_commission_price.text(Math.ceil(ns_util.math(total_gold).mul(0.1).number));
    }
}

ns_dialog.dialogs.build_TradeDept.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_TradeDept.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_TradeDept.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_TradeDept;
    let d = ns_cs.d.bdic[dialog.data.castle_pk];

    // 건설 중일때 버튼 처리
    if(dialog.prev_level !== d.level) {
        dialog.prev_level = d.level;
    }

    if (dialog.prev_level == null || dialog.prev_level < 1) {
        ns_button.buttons.build_TradeDept_on_buy.setDisable();
        ns_button.buttons.build_TradeDept_on_sell.setDisable();
    } else {
        ns_button.buttons.build_TradeDept_on_buy.setEnable();
        ns_button.buttons.build_TradeDept_on_sell.setEnable();
    }

    // 시세표 불러오기
    let ut = ns_timer.now();
    if (ut - dialog.opentick >= 30) {
        dialog.opentick = ut;
        dialog.drawTab();
    }
}


ns_button.buttons.build_TradeDept_close = new nsButtonSet('build_TradeDept_close', 'button_back', 'build_TradeDept', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_TradeDept_sub_close = new nsButtonSet('build_TradeDept_sub_close', 'button_full', 'build_TradeDept', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.build_TradeDept_close_all = new nsButtonSet('build_TradeDept_close_all', 'button_close_all', 'build_TradeDept', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_TradeDept = new nsButtonSet('build_desc_TradeDept', 'button_text_style_desc', 'build_TradeDept', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_move_TradeDept = new nsButtonSet('build_move_TradeDept', 'button_middle_2', 'build_TradeDept', { base_class: ns_button.buttons.build_move });
ns_button.buttons.build_cons_TradeDept = new nsButtonSet('build_cons_TradeDept', 'button_multi', 'build_TradeDept', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_TradeDept = new nsButtonSet('build_upgrade_TradeDept', 'button_hero_action', 'build_TradeDept', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_prev_TradeDept = new nsButtonSet('build_prev_TradeDept', 'button_multi_prev', 'build_TradeDept', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_TradeDept = new nsButtonSet('build_next_TradeDept', 'button_multi_next', 'build_TradeDept', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_TradeDept = new nsButtonSet('build_speedup_TradeDept', 'button_encourage', 'build_TradeDept', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_TradeDept = new nsButtonSet('build_cancel_TradeDept', 'button_build', 'build_TradeDept', { base_class: ns_button.buttons.build_cancel });

ns_button.buttons.trad_build_TradeDept_order = new nsButtonSet('trad_build_TradeDept_order', 'button_middle_2', 'build_TradeDept', {base_class:ns_button.buttons.common_open_dialog_word5});
ns_button.buttons.trad_build_TradeDept_delivery = new nsButtonSet('trad_build_TradeDept_delivery', 'button_middle_2', 'build_TradeDept', {base_class:ns_button.buttons.common_open_dialog_word5});


ns_button.buttons.build_TradeDept_tab_food = new nsButtonSet('build_TradeDept_tab_food', 'button_tab', 'build_TradeDept', { toggle_group: 'build_TradeDept_tab' });
ns_button.buttons.build_TradeDept_tab_food.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_TradeDept;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.drawTab();
}
ns_button.buttons.build_TradeDept_tab_horse = new nsButtonSet('build_TradeDept_tab_horse', 'button_tab', 'build_TradeDept', { base_class: ns_button.buttons.build_TradeDept_tab_food, toggle_group: 'build_TradeDept_tab' });
ns_button.buttons.build_TradeDept_tab_lumber = new nsButtonSet('build_TradeDept_tab_lumber', 'button_tab', 'build_TradeDept', { base_class: ns_button.buttons.build_TradeDept_tab_food, toggle_group: 'build_TradeDept_tab' });
ns_button.buttons.build_TradeDept_tab_iron = new nsButtonSet('build_TradeDept_tab_iron', 'button_tab', 'build_TradeDept', { base_class: ns_button.buttons.build_TradeDept_tab_food, toggle_group: 'build_TradeDept_tab' });


ns_button.buttons.build_TradeDept_on_buy = new nsButtonSet('build_TradeDept_on_buy', 'button_small_2', 'build_TradeDept');
ns_button.buttons.build_TradeDept_on_buy.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_TradeDept;
    let d = ns_cs.d.bdic[dialog.data.castle_pk];
    if (ns_util.math(d.level).lt(1)) {
        return false;
    }
    ns_dialog.setDataOpen('build_TradeDept_bid', { resource_type: dialog.current_tab });
}
ns_button.buttons.build_TradeDept_on_sell = new nsButtonSet('build_TradeDept_on_sell', 'button_small_2', 'build_TradeDept');
ns_button.buttons.build_TradeDept_on_sell.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_TradeDept;
    let d = ns_cs.d.bdic[dialog.data.castle_pk];
    if (ns_util.math(d.level).lt(1)) {
        return false;
    }
    ns_dialog.setDataOpen('build_TradeDept_offer', { resource_type: dialog.current_tab });
}
/*******************************************************************/
ns_dialog.dialogs.build_TradeDept_bid = new nsDialogSet('build_TradeDept_bid', 'dialog_building', 'size-large', { do_close_all: false });
ns_dialog.dialogs.build_TradeDept_bid.current_resource_type = null;

ns_dialog.dialogs.build_TradeDept_bid.cacheContents = function()
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);

    this.cont_obj.trade_resource_curr = new nsObject('.trade_resource_curr', this.obj);
    this.cont_obj.trade_gold_curr = new nsObject('.trade_gold_curr', this.obj);

    this.cont_obj.trade_bid_total_price = new nsObject('.trade_bid_total_price', this.obj);
    this.cont_obj.trade_bid_commission_price = new nsObject('.trade_bid_commission_price', this.obj);
}

ns_dialog.dialogs.build_TradeDept_bid.draw = function()
{
    let data = this.data;
    let resource = ns_cs.getResourceInfo(data.resource_type + '_curr');
    let gold = ns_cs.getTerritoryInfo('gold_curr');

    // 초기화
    ns_button.buttons.trade_bid_resource_amount.obj.text(0);
    ns_button.buttons.trade_bid_unit_price.obj.text(ns_dialog.dialogs.build_TradeDept.bid_min);
    this.cont_obj.trade_bid_total_price.text(0);
    this.cont_obj.trade_bid_commission_price.text(0);

    this.cont_obj.content_title.text(ns_i18n.t('resource_purchase_order', [ns_i18n.t(`resource_${data.resource_type}`)]));

    if (this.current_resource_type) {
        this.cont_obj.trade_resource_curr.removeCss(`resource_${this.current_resource_type}`);
    }

    this.cont_obj.trade_resource_curr.addCss(`resource_${data.resource_type}`).html('&nbsp;' + ns_i18n.t(`resource_${data.resource_type}`) + '&nbsp;' + ns_util.numberFormat(resource));
    this.current_resource_type = data.resource_type;

    this.cont_obj.trade_gold_curr.html('&nbsp;' + ns_i18n.t('resource_gold') + '&nbsp;' + ns_util.numberFormat(gold));
}

ns_dialog.dialogs.build_TradeDept_bid.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_TradeDept_bid.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_TradeDept_bid.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_TradeDept_bid;
    let d = ns_cs.d.bdic[ns_dialog.dialogs.build_TradeDept.data.castle_pk];

    if (! d) {
        // 건물 정보를 찾을 수 없음 - 최초 건설 취소나 철거 등
        ns_dialog.closeAll();
        return;
    }

    // 자원 갱신 부분?
    dialog.cont_obj.trade_resource_curr.html('&nbsp;' + ns_i18n.t(`resource_${dialog.data.resource_type}`) + '&nbsp;' + ns_util.numberFormat(ns_cs.getResourceInfo(dialog.data.resource_type + '_curr')));
    dialog.cont_obj.trade_gold_curr.html('&nbsp;' + ns_i18n.t('resource_gold') + '&nbsp;' + ns_util.numberFormat(ns_cs.getTerritoryInfo('gold_curr')));
}

/* ************************************************** */

ns_button.buttons.build_TradeDept_bid_close = new nsButtonSet('build_TradeDept_bid_close', 'button_back', 'build_TradeDept_bid', { base_class: ns_button.buttons.common_close });
ns_button.buttons.build_TradeDept_bid_sub_close = new nsButtonSet('build_TradeDept_bid_sub_close', 'button_full', 'build_TradeDept_bid', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.build_TradeDept_bid_close_all = new nsButtonSet('build_TradeDept_bid_close_all', 'button_close_all', 'build_TradeDept_bid', { base_class: ns_button.buttons.common_close_all });


ns_button.buttons.build_TradeDept_bid = new nsButtonSet('build_TradeDept_bid', 'button_special', 'build_TradeDept_bid');
ns_button.buttons.build_TradeDept_bid.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_TradeDept_bid;
    let resource_amount = ns_button.buttons.trade_bid_resource_amount.obj;
    let unit_price = ns_button.buttons.trade_bid_unit_price.obj;
    let total_price = dialog.cont_obj.trade_bid_total_price;
    let commission_price = dialog.cont_obj.trade_bid_commission_price;

    if (ns_util.math(resource_amount.text()).lt(1)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_trade_buy_need_amount')); // 구매수량을 입력해주세요.
        return;
    }

    if (ns_util.math(unit_price.text()).lte(0)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_trade_buy_need_price')); // 구매단가를 입력해주세요.
        return;
    }

    if (ns_util.math(unit_price.text()).lte(0)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_trade_buy_need_price')); // 구매단가를 입력해주세요.
        return;
    }

    if (ns_util.math(total_price.text()).plus(commission_price.text()).gt(ns_cs.getTerritoryInfo('gold_curr'))) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_trade_buy_not enough_price')); // 보유 황금이 부족합니다.
        return;
    }


    let post_data = { };
    post_data['action'] = 'bid';
    post_data['type'] = ns_dialog.dialogs.build_TradeDept.convertCode(dialog.data.resource_type);
    post_data['bid_amount'] = ns_util.toInteger(resource_amount.text());
    post_data['unit_price'] = parseFloat(unit_price.text());

    ns_xhr.post('/api/tradeDept/bid', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }

        ns_dialog.setDataOpen('message', ns_i18n.t('msg_trade_buy_complete')); // 구매주문이 완료되었습니다.

        ns_dialog.dialogs.build_TradeDept.drawTab();
        ns_dialog.close('build_TradeDept_bid');
    }, { useProgress: true });
}

ns_button.buttons.trade_bid_updown = new nsButtonSet('trade_bid_updown', 'button_arrow_updown', 'build_TradeDept_bid');
ns_button.buttons.trade_bid_updown.mouseUp = function(_e)
{
    let gold_curr = ns_cs.getTerritoryInfo('gold_curr');
    let resource_amount = ns_button.buttons.trade_bid_resource_amount.obj;
    let unit_price = ns_button.buttons.trade_bid_unit_price.obj;

    if (ns_util.math(resource_amount.text()).eq(Math.floor((gold_curr - Math.ceil(gold_curr * 0.1)) / parseFloat(unit_price.text())))) {
        resource_amount.text(0);
    } else {
        if (ns_util.math(unit_price.text()).eq(0)){
            // 단가 입력하지 않았을 경우 수량은 현재 금으로
            resource_amount.text(gold_curr);
        } else if (ns_util.math(unit_price.text()).gt(0)) {
            // 최대 수량 계산 // ( 현재금 - (현재금 * 0.01) ) / 단가  // 계산이 어긋남?
            resource_amount.text(Math.floor((gold_curr - Math.ceil(gold_curr * 0.1)) / parseFloat(unit_price.text())));
        } else {
            resource_amount.text(0);
        }
    }
    ns_dialog.dialogs.build_TradeDept.inputIntValue('bid');
}

ns_button.buttons.trade_bid_resource_amount = new nsButtonSet('trade_bid_resource_amount', 'button_input', 'build_TradeDept_bid');
ns_button.buttons.trade_bid_resource_amount.mouseUp = function(_e)
{
    let gold_curr = ns_cs.getTerritoryInfo('gold_curr');
    let unit_price = ns_util.toFloat(ns_button.buttons.trade_bid_unit_price.obj.text());

    ns_dialog.setDataOpen('keypad', { max: ns_util.math(gold_curr).minus(Math.ceil(ns_util.math(gold_curr).mul(0.1).number)).div(unit_price).number, min: 0,
        current: this.obj.text(),
        callback: function(data)
        {
            ns_button.buttons.trade_bid_resource_amount.obj.text(data);
            ns_dialog.dialogs.build_TradeDept.inputIntValue('bid');
        }
    });
}

ns_button.buttons.trade_bid_unit_price = new nsButtonSet('trade_bid_unit_price', 'button_input', 'build_TradeDept_bid');
ns_button.buttons.trade_bid_unit_price.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('keypad', { max: 999999, min: 0.1, current: ns_button.buttons.trade_bid_unit_price.obj.text(), float_status: true,
        callback: function(data)
        {
            ns_button.buttons.trade_bid_unit_price.obj.text(data);
            ns_dialog.dialogs.build_TradeDept.inputIntValue('bid');
        }
    });
}

ns_button.buttons.trade_bid_unit_price_updown = new nsButtonSet('trade_bid_unit_price_updown', 'button_arrow_updown', 'build_TradeDept_bid', {base_class: ns_button.buttons.trade_bid_unit_price});

/*******************************************************************/
ns_dialog.dialogs.build_TradeDept_offer = new nsDialogSet('build_TradeDept_offer', 'dialog_building', 'size-large', { do_close_all: false });
ns_dialog.dialogs.build_TradeDept_offer.current_resource_type = null;

ns_dialog.dialogs.build_TradeDept_offer.cacheContents = function()
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);

    this.cont_obj.trade_resource_curr = new nsObject('.trade_resource_curr', this.obj);
    this.cont_obj.trade_gold_curr = new nsObject('.trade_gold_curr', this.obj);

    this.cont_obj.trade_offer_total_price = new nsObject('.trade_offer_total_price', this.obj);
    this.cont_obj.trade_offer_commission_price = new nsObject('.trade_offer_commission_price', this.obj);
}

ns_dialog.dialogs.build_TradeDept_offer.draw = function()
{
    let dialog = ns_dialog.dialogs.build_TradeDept_offer;
    let data = dialog.data;
    let resource = ns_cs.getResourceInfo(data.reso_type + '_curr');
    let gold = ns_cs.getTerritoryInfo('gold_curr');

    // 초기화
    ns_button.buttons.trade_offer_resource_amount.obj.text(0);
    ns_button.buttons.trade_offer_unit_price.obj.text(ns_dialog.dialogs.build_TradeDept.offer_min);
    this.cont_obj.trade_offer_total_price.text(0);
    this.cont_obj.trade_offer_commission_price.text(0);

    this.cont_obj.content_title.text(ns_i18n.t('resource_sales_order', [ns_i18n.t(`resource_${data.resource_type}`)]));

    if (this.current_resource_type) {
        this.cont_obj.trade_resource_curr.removeCss(`resource_${this.current_resource_type}`);
    }

    this.cont_obj.trade_resource_curr.addCss(`resource_${data.resource_type}`).html('&nbsp;' + ns_i18n.t(`resource_${data.resource_type}`) + '&nbsp;' + ns_util.numberFormat(resource));
    this.current_resource_type = data.resource_type;

    this.cont_obj.trade_gold_curr.html('&nbsp;' + ns_i18n.t('resource_gold') + '&nbsp;' + ns_util.numberFormat(gold));
}

ns_dialog.dialogs.build_TradeDept_offer.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_TradeDept_offer.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_TradeDept_offer.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_TradeDept_offer;
    let d = ns_cs.d.bdic[ns_dialog.dialogs.build_TradeDept.data.castle_pk];

    if (!d) {
        // 건물 정보를 찾을 수 없음 - 최초 건설 취소나 철거 등
        ns_dialog.closeAll();
        return;
    }

    // 자원 갱신 부분?
    dialog.cont_obj.trade_resource_curr.html('&nbsp;' + ns_i18n.t(`resource_${dialog.data.resource_type}`) + '&nbsp;' + ns_util.numberFormat(ns_cs.getResourceInfo(dialog.data.resource_type + '_curr')));
    dialog.cont_obj.trade_gold_curr.html('&nbsp;' + ns_i18n.t('resource_gold') + '&nbsp;' + ns_util.numberFormat(ns_cs.getTerritoryInfo('gold_curr')));
}

/* ************************************************** */

ns_button.buttons.build_TradeDept_offer_close = new nsButtonSet('build_TradeDept_offer_close', 'button_back', 'build_TradeDept_offer', {base_class:ns_button.buttons.common_close});
ns_button.buttons.build_TradeDept_offer_sub_close = new nsButtonSet('build_TradeDept_offer_sub_close', 'button_full', 'build_TradeDept_offer', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.build_TradeDept_offer_close_all = new nsButtonSet('build_TradeDept_offer_close_all', 'button_close_all', 'build_TradeDept_offer', {base_class:ns_button.buttons.common_close_all});

ns_button.buttons.build_TradeDept_offer = new nsButtonSet('build_TradeDept_offer', 'button_special', 'build_TradeDept_offer');
ns_button.buttons.build_TradeDept_offer.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_TradeDept_offer;

    if (ns_util.math(ns_button.buttons.trade_offer_resource_amount.obj.text()).lt(1)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_trade_sell_need_amount')); // 판매수량을 입력해주세요.
        return;
    }

    if (ns_util.math(ns_button.buttons.trade_offer_unit_price.obj.text()).lte(0)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_trade_sell_need_price')); // 판매단가를 입력해주세요.
        return;
    }

    if (ns_util.math(dialog.cont_obj.trade_offer_commission_price.text()).gt(ns_cs.getTerritoryInfo('gold_curr'))) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_trade_buy_not enough_price')); // 보유 황금이 부족합니다.
        return;
    }

    let post_data = { };
    post_data['type'] = dialog.data.resource_type;
    post_data['offer_amount'] = ns_util.toInteger(ns_button.buttons.trade_offer_resource_amount.obj.text());
    post_data['unit_price'] = ns_util.toFloat(ns_button.buttons.trade_offer_unit_price.obj.text());

    ns_xhr.post('/api/tradeDept/offer', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }

        ns_dialog.setDataOpen('message', ns_i18n.t('msg_trade_sell_complete')); // 판매주문이 완료되었습니다.

        ns_dialog.dialogs.build_TradeDept.drawTab();
        ns_dialog.close('build_TradeDept_offer');
    }, { useProgress: true });
}

ns_button.buttons.trade_offer_updown = new nsButtonSet('trade_offer_updown', 'button_arrow_updown', 'build_TradeDept_offer');
ns_button.buttons.trade_offer_updown.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_TradeDept_offer;
    let resource_amount = ns_button.buttons.trade_offer_resource_amount.obj;
    let resource_curr = ns_cs.getResourceInfo(dialog.data.resource_type + '_curr');

    if (ns_util.math(resource_amount.text()).gt(0)) {
        resource_amount.text(0);
    } else {
        resource_amount.text(resource_curr);
    }
    ns_dialog.dialogs.build_TradeDept.inputIntValue('offer');
}

ns_button.buttons.trade_offer_resource_amount = new nsButtonSet('trade_offer_resource_amount', 'button_input', 'build_TradeDept_offer');
ns_button.buttons.trade_offer_resource_amount.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_TradeDept_offer;
    let gold_curr = ns_cs.getTerritoryInfo('gold_curr');
    let resource_amount = ns_button.buttons.trade_offer_resource_amount.obj;
    let unit_price = ns_button.buttons.trade_offer_unit_price.obj;

    ns_dialog.setDataOpen('keypad', { max: ns_cs.getResourceInfo(dialog.data.reso_type + '_curr'), min:0, current: this.obj.text(),
        callback: function(data){
            resource_amount.text(data);
            ns_dialog.dialogs.build_TradeDept.inputIntValue('offer');
        }
    });
}

ns_button.buttons.trade_offer_unit_price = new nsButtonSet('trade_offer_unit_price', 'button_input', 'build_TradeDept_offer');
ns_button.buttons.trade_offer_unit_price.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('keypad', { max: 999999, min: 0, current: ns_button.buttons.trade_offer_unit_price.obj.text(), float_status:true,
        callback: function(data){
            ns_button.buttons.trade_offer_unit_price.obj.text(data);
            ns_dialog.dialogs.build_TradeDept.inputIntValue('offer');
        }
    });
}


ns_button.buttons.trade_offer_per_price_updown = new nsButtonSet('trade_offer_per_price_updown', 'button_arrow_updown', 'build_TradeDept_offer',{base_class: ns_button.buttons.trade_offer_unit_price});

/*******************************************************************/
ns_dialog.dialogs.build_TradeDept_order = new nsDialogSet('build_TradeDept_order', 'dialog_building', 'size-large', { do_close_all: false });

ns_dialog.dialogs.build_TradeDept_order.cacheContents = function()
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);
    this.cont_obj.content_title.text(ns_i18n.t('order_status')); // 주문 현황
    this.cont_obj.tbody = new nsObject('table tbody', this.obj);
}

ns_dialog.dialogs.build_TradeDept_order.draw = function()
{
    this.drawList();
}

ns_dialog.dialogs.build_TradeDept_order.drawList = function()
{
    let dialog = ns_dialog.dialogs.build_TradeDept_order;

    let post_data = {};
    post_data['action'] = 'order_list';
    post_data['type'] = 'order';
    ns_xhr.post('/api/tradeDept/orderList', post_data, dialog.drawRemote);
}

ns_dialog.dialogs.build_TradeDept_order.drawRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.build_TradeDept_order;

    dialog.cont_obj.tbody.empty();

    for (let d of Object.values(_data)) {
        let v_trade_type = d.trade_type === 'B' ? 'bid' : 'offer';

        let tr = document.createElement('tr');
        let columns = [];

        let col = document.createElement('td');
        let span = document.createElement('span');
        let resource_type = ns_dialog.dialogs.build_TradeDept.convertCode(d.reso_type);
        span.innerHTML = ns_i18n.t(`resource_${resource_type}`);
        col.appendChild(span);


        let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
        button.setAttribute('id', `ns_button_build_TradeDept_order_${d.bid_pk}`);
        col.append(button);

        columns.push(col);

        col = document.createElement('td');
        span = document.createElement('span');
        span.innerHTML = ns_util.numberFormat(d[ v_trade_type + '_amount']);
        col.appendChild(span);
        columns.push(col);

        col = document.createElement('td');
        span = document.createElement('span');
        span.innerHTML = ns_util.numberFormat(d.unit_price);
        col.appendChild(span);
        columns.push(col);

        col = document.createElement('td');
        span = document.createElement('span');
        span.innerHTML = ns_util.math(d[v_trade_type + '_amount']).minus(d.deal_amount).number_format;
        col.appendChild(span);
        columns.push(col);

        col = document.createElement('td');
        span = document.createElement('span');
        span.innerHTML = d.trade_type ==='B' ? ns_i18n.t('purchase') : ns_i18n.t('sale');
        col.appendChild(span);
        columns.push(col);

        for (let _column of columns) {
            tr.appendChild(_column);
        }

        dialog.cont_obj.tbody.append(tr);

        let button_id = `build_TradeDept_order_${d.bid_pk}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'build_TradeDept_order');
        ns_button.buttons[button_id].mouseUp = function ()
        {
            let confirm_message = (d.trade_type === 'B') ? ns_i18n.t('msg_trade_buy_cancel_confirm') : ns_i18n.t('msg_trade_sell_cancel_confirm');

            ns_dialog.setDataOpen('confirm', { text: confirm_message,
                okFunc: () =>
                {
                    let api_url = (d.trade_type === 'B') ? '/api/tradeDept/cancelBid' : '/api/tradeDept/cancelOffer';
                    let post_data = {};
                    if (d.trade_type === 'B') {
                        post_data['bid_pk'] = d.bid_pk;
                    } else {
                        post_data['offe_pk'] = d.offe_pk;
                    }

                    ns_xhr.post(api_url, post_data, function(__data, __status)
                    {
                        if(! ns_xhr.returnCheck(__data)) {
                            return;
                        }
                        __data = __data['ns_xhr_return']['add_data'];

                        ns_dialog.setDataOpen('message', ns_i18n.t('msg_trade_buy_cancel')); // 구매를 취소하였습니다.

                        // 시세표 업데이트
                        ns_dialog.dialogs.build_TradeDept.drawTab();

                        // 주문현황 업데이트
                        dialog.drawList();
                    }, { useProgress: true });
                }
            });
        }
        dialog.buttons.push(ns_button.buttons[button_id]);
    }
}

/* ************************************************** */
ns_button.buttons.build_TradeDept_order_close = new nsButtonSet('build_TradeDept_order_close', 'button_back', 'build_TradeDept_order', { base_class:ns_button.buttons.common_close });
ns_button.buttons.build_TradeDept_order_sub_close = new nsButtonSet('build_TradeDept_order_sub_close', 'button_full', 'build_TradeDept_order', { base_class:ns_button.buttons.common_sub_close });
ns_button.buttons.build_TradeDept_order_close_all = new nsButtonSet('build_TradeDept_order_close_all', 'button_close_all', 'build_TradeDept_order', { base_class:ns_button.buttons.common_close_all });

/*******************************************************************/
ns_dialog.dialogs.build_TradeDept_delivery = new nsDialogSet('build_TradeDept_delivery', 'dialog_building', 'size-large', { do_close_all: false });
ns_dialog.dialogs.build_TradeDept_delivery.delivery_list = [];
ns_dialog.dialogs.build_TradeDept_delivery.opentick = 0;
ns_dialog.dialogs.build_TradeDept_delivery.reload_state = false;

ns_dialog.dialogs.build_TradeDept_delivery.cacheContents = function()
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);
    this.cont_obj.content_title.text(ns_i18n.t('delivery_status')); // 배송 현황
    this.cont_obj.tbody = new nsObject('table tbody', this.obj);
}

ns_dialog.dialogs.build_TradeDept_delivery.draw = function()
{
    this.drawList();
}

ns_dialog.dialogs.build_TradeDept_delivery.drawList = function()
{
    let dialog = ns_dialog.dialogs.build_TradeDept_delivery;
    let post_data = {};
    post_data['type'] = 'delivery';
    ns_xhr.post('/api/tradeDept/deliveryList', post_data, dialog.drawRemote);
}

ns_dialog.dialogs.build_TradeDept_delivery.drawRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.build_TradeDept_delivery;

    dialog.cont_obj.tbody.empty();
    dialog.delivery_list = [];

    for (let d of Object.values(_data)) {
        dialog.delivery_list.push(d);
        let v_trade_type = d.trade_type ==='B' ? 'bid' : 'offer';
        let reso_type = ns_dialog.dialogs.build_TradeDept.convertCode(d.reso_type);

        let columns = [];
        let tr = document.createElement('tr');

        let col = document.createElement('td');
        let span = document.createElement('span');
        span.innerHTML = ns_i18n.t(`resource_${reso_type}`);
        col.appendChild(span);

        let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
        button.setAttribute('id', `ns_button_build_TradeDept_delivery_${d.deli_pk}`);
        col.append(button);

        columns.push(col);

        col = document.createElement('td');
        span = document.createElement('span');
        span.innerHTML = ns_util.numberFormat(d.deal_amount);
        col.appendChild(span);
        columns.push(col);

        col = document.createElement('td');
        span = document.createElement('span');
        span.innerHTML = ns_util.numberFormat(d.unit_price);
        col.appendChild(span);
        columns.push(col);

        col = document.createElement('td');
        span = document.createElement('span');
        span.setAttribute('class', 'delivery_left_time_' + d.deli_pk);
        span.innerHTML = ns_util.getCostsTime(ns_util.math(d.end_dt).minus(ns_timer.now()).number);
        col.appendChild(span);
        columns.push(col);

        col = document.createElement('td');
        span = document.createElement('span');
        span.setAttribute('class', 'button_small_1');
        span.innerText = '독려';
        col.appendChild(span);
        columns.push(col);

        for (let _column of columns) {
            tr.appendChild(_column);
        }

        dialog.cont_obj.tbody.append(tr);

        let button_id = `build_TradeDept_delivery_${d.deli_pk}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'build_TradeDept_delivery');
        ns_button.buttons[button_id].mouseUp = function ()
        {
            // TODO for문으로 안찾아도 될텐데...
            let time_pk = null;
            for (let [k, d] of Object.entries(ns_cs.d.time)) {
                if (! ns_util.isNumeric(k)) {
                    continue;
                }
                if (d.queue_type === 'S' && ns_util.math(d.description.split(':')[1]).eq(d.queue_pk)) {
                    time_pk = k;
                    break;
                }
            }

            ns_dialog.setDataOpen('item_quick_use', { type: 'trade_speedup', time_pk: time_pk });
        }
        dialog.buttons.push(ns_button.buttons[button_id]);
    }
}

ns_dialog.dialogs.build_TradeDept_delivery.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_TradeDept_delivery.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_TradeDept_delivery.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_TradeDept_delivery;

    // 남은 시간 갱신
    for (let d of dialog.delivery_list) {
        let remain_time = ns_util.math(d.end_dt).minus(ns_timer.now()).number;
        let message = (ns_util.math(remain_time).gt(0)) ? ns_util.getCostsTime(remain_time) : ns_i18n.t('timer_progress');
        dialog.cont_obj.tbody.find('span.delivery_left_time_' + d.deli_pk).text(message);
    }

    // 무역장 리스트 갱신을 위해 - TODO 더 좋은 방법이 없나?
    if(dialog.reload_state) {
        // 일단은 1.5초 후에 리스트를 갱신하도록 함. (임시)
        setTimeout(() => {
            dialog.drawList();
        }, 1500);
        dialog.reload_state = false;
    }
}

/* ************************************************** */
ns_button.buttons.build_TradeDept_delivery_close = new nsButtonSet('build_TradeDept_delivery_close', 'button_back', 'build_TradeDept_delivery', {base_class:ns_button.buttons.common_close});
ns_button.buttons.build_TradeDept_delivery_sub_close = new nsButtonSet('build_TradeDept_delivery_sub_close', 'button_full', 'build_TradeDept_delivery', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.build_TradeDept_delivery_close_all = new nsButtonSet('build_TradeDept_delivery_close_all', 'button_close_all', 'build_TradeDept_delivery', {base_class:ns_button.buttons.common_close_all});