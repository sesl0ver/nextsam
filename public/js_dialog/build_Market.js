// 다이얼로그
ns_dialog.dialogs.build_Market = new nsDialogSet('build_Market', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });
ns_dialog.dialogs.build_Market.prev_level = null;
ns_dialog.dialogs.build_Market.prev_hour = null;
ns_dialog.dialogs.build_Market.focued = null;
ns_dialog.dialogs.build_Market.init_left_time = null;
ns_dialog.dialogs.build_Market.market_data = [];

ns_dialog.dialogs.build_Market.cacheContents = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.cacheContents.call(this, true);
    }

    this.cont_obj.market_list_warp = new nsObject('.market_list_warp', this.obj);
    this.cont_obj.market_left_time = new nsObject('.market_left_time', this.obj);

    this.cont_obj.item_box_skeleton = new nsObject('#item_box_skeleton');
}

ns_dialog.dialogs.build_Market.draw = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.draw.call(this, true);
    }

    this.prev_level = ns_cs.d.bdic[this.data.castle_pk]['level'];

    let now = ns_timer.now();
    let now_date = new Date();
    now_date.setTime(parseInt(String(now) + '000'));
    this.prev_hour = now_date.getHours();
    this.init_left_time = new Date();
    this.init_left_time.setTime(parseInt(String(now) + '000'));
    this.init_left_time.setHours((this.init_left_time.getHours() + 1), 0, 0);

    this.drawList();

    this.last_lud = null;
}

ns_dialog.dialogs.build_Market.drawList = function()
{
    let dialog = ns_dialog.dialogs.build_Market;
    if (ns_util.math(ns_cs.d.bdic[dialog.data.castle_pk].level).eq(1) && ns_cs.d.bdic[dialog.data.castle_pk].status === 'U') {
        return;
    }
    let post_data = {};
    post_data['in_cast_pk'] = dialog.data.castle_pk;

    ns_xhr.post('/api/market/list', post_data, dialog.drawRemote);
}

ns_dialog.dialogs.build_Market.drawRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];
    if (! _data) {
        return;
    }

    let dialog = ns_dialog.dialogs.build_Market;
    dialog.cont_obj.market_list_warp.empty();

    for (let d of Object.values(_data)) {
        let box = dialog.drawItemBox(d);
        box.setAttribute('id', `ns_button_build_market_buy_${d.sale_pk}`);
        dialog.cont_obj.market_list_warp.append(box);
        ns_button.buttons[`build_market_buy_${d.sale_pk}`] = new nsButtonSet(`build_market_buy_${d.sale_pk}`, null, 'build_Market');
        ns_button.buttons[`build_market_buy_${d.sale_pk}`].mouseUp = function ()
        {
            ns_dialog.setDataOpen('build_Market_buy', d);
        }
        dialog.buttons.push(ns_button.buttons[`build_market_buy_${d.sale_pk}`]);
    }
}

ns_dialog.dialogs.build_Market.drawItemBox = function (_data)
{
    try {
        let dialog = ns_dialog.dialogs.build_Market;
        let box = dialog.cont_obj.item_box_skeleton.clone();
        if (_data) {
            let item_image = box.find('.item_image');
            let title;
            if (! _data?.m_item_pk) {
                title = ns_i18n.t(`resource_${_data['sale_type']}`) + ' ' + ns_util.numberFormat(_data.sale_amount);
                item_image.addCss(`resource_image_${_data['sale_type']}`);
            } else {
                let m = ns_cs.m.item[_data.m_item_pk];
                item_image.addCss(`item_image_${_data.m_item_pk}`);
                title = m.title;
            }
            box.find('.item_title').text(title);
            box.find('.item_count').text(ns_util.numberFormat(_data.pay_reso_amount));
            box.find('.item_count').addCss(`resource_${_data.pay_reso_type}`);
            if (_data.pay_type === 'Q') {
                box.find('.qbig_amount').text(ns_util.numberFormat(_data.pay_cash_amount));
            } else {
                box.find('.item_price').remove();
            }

        } else {
            box.text('');
            box.addCss('empty');
        }
        return box;
    } catch (e) {
        console.error(e);
    }
}

ns_dialog.dialogs.build_Market.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_Market.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_Market.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_Market;
    let d = ns_cs.d.bdic[dialog.data.castle_pk];

    // 건물이 없다면, 철거라면
    if (!d) {
        ns_dialog.close('build_Market');
        return;
    }

    // 레벨 검사
    if(dialog.prev_level !== d.level) {
        if (dialog.prev_level === 0 || dialog.prev_level > d.level){
            dialog.drawList();
        } else {
            let post_data = {};
            post_data['in_cast_pk'] = dialog.data.castle_pk;
            ns_xhr.post('/api/market/levelUpgrade', post_data, function(_data, _status){
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                dialog.drawList();
            }, { useProgress: true });
        }
        dialog.prev_level = d.level;
    }

    let now = ns_timer.now();
    let now_date = new Date();
    now_date.setTime(parseInt(String(now) + '000'));
    let now_hours = now_date.getHours();

    if(dialog.prev_hour !== now_hours) {
        dialog.init_left_time = null;
        dialog.prev_hour = now_hours;

        dialog.drawList();

        dialog.init_left_time = new Date();
        dialog.init_left_time.setTime(parseInt(String(now) + '000'));
        dialog.init_left_time.setHours(dialog.init_left_time.getHours() + 1, 0, 0);
    }

    if (dialog.prev_level === 0) {
        dialog.init_left_time = null;

        dialog.cont_obj.market_left_time.text('').hide();

        ns_button.buttons.build_Market_renew.setDisable();
    } else {
        if (dialog.init_left_time != null) {
            let left_second = dialog.init_left_time.getTime() - now_date.getTime();
            left_second = Math.floor(( left_second % 3600000 ) / 1000);

            let left_minute = Math.floor(left_second / 60);
            left_second = left_second % 60;

            dialog.cont_obj.market_left_time.show();
            dialog.cont_obj.market_left_time.text(ns_i18n.t('market_left_time_description', [left_minute, left_second]));
        } else {
            dialog.init_left_time = new Date();
            dialog.init_left_time.setTime(parseInt(String(now) + '000'));
            dialog.init_left_time.setHours(dialog.init_left_time.getHours() + 1, 0, 0);
        }
        ns_button.buttons.build_Market_renew.setEnable();
    }
}

ns_button.buttons.build_Market_close = new nsButtonSet('build_Market_close', 'button_back', 'build_Market', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_Market_sub_close = new nsButtonSet('build_Market_sub_close', 'button_full', 'build_Market', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.build_Market_close_all = new nsButtonSet('build_Market_close_all', 'button_close_all', 'build_Market', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_Market = new nsButtonSet('build_desc_Market', 'button_text_style_desc', 'build_Market', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_move_Market = new nsButtonSet('build_move_Market', 'button_middle_2', 'build_Market', { base_class: ns_button.buttons.build_move });
ns_button.buttons.build_cons_Market = new nsButtonSet('build_cons_Market', 'button_multi', 'build_Market', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_Market = new nsButtonSet('build_upgrade_Market', 'button_hero_action', 'build_Market', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_prev_Market = new nsButtonSet('build_prev_Market', 'button_multi_prev', 'build_Market', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_Market = new nsButtonSet('build_next_Market', 'button_multi_next', 'build_Market', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_Market = new nsButtonSet('build_speedup_Market', 'button_encourage', 'build_Market', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_Market = new nsButtonSet('build_cancel_Market', 'button_build', 'build_Market', { base_class: ns_button.buttons.build_cancel });

ns_button.buttons.build_Market_renew = new nsButtonSet('build_Market_renew', 'button_middle_2', 'build_Market');
ns_button.buttons.build_Market_renew.mouseUp = function(_e)
{
    // 갱신 까지 남은 시간이 5초 이하인 경우 메시지창 띄우고 멈추기
    let now_time = new Date();
    now_time.setTime(parseInt(String(ns_timer.now()) + '000'));
    let left_second = ns_dialog.dialogs.build_Market.init_left_time.getTime() - now_time.getTime();
    left_second = Math.floor(( left_second % 3600000 ) / 1000);
    if (left_second <= 5) { // 갱신까지 남은 시간이 5초 이하면
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_limit_item_use_time_warning')); // 갱신까지 남은 시간이 5초 이하인 경우 사용할 수 없습니다.
    } else {
        ns_dialog.setDataOpen('item_quick_use', { type: 'market' });
    }
}

/*******************************************************************/
ns_dialog.dialogs.build_Market_buy = new nsDialogSet('build_Market_buy', 'dialog_pop', 'size-medium', { do_content_scroll: false, do_close_all: false });

ns_dialog.dialogs.build_Market_buy.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);

    this.cont_obj.item_image = new nsObject('.item_image', this.obj);
    this.cont_obj.item_description = new nsObject('.item_description', this.obj);

    this.cont_obj.table = new nsObject('table', this.obj);
    this.cont_obj.item_amount = new nsObject('.item_amount', this.obj);
    this.cont_obj.shop_price = new nsObject('.shop_price', this.obj);
    this.cont_obj.market_resource_price = new nsObject('.market_resource_price', this.obj);
    this.cont_obj.market_qbig_price = new nsObject('.market_qbig_price', this.obj);
}

ns_dialog.dialogs.build_Market_buy.draw = function()
{
    if (! this.data) {
        return;
    }
    let data = this.data, title = '', pop_title = '';

    this.cont_obj.table.removeCss('show_shop');

    if (data.sale_type === 'cashitem') {
        let m_item_pk = data.m_item_pk;
        let m = ns_cs.m.item[m_item_pk];

        if (m.yn_sell === 'Y') {
            this.cont_obj.table.addCss('show_shop');
            this.cont_obj.shop_price.text(m.price);
        }

        this.cont_obj.item_image.addCss(`item_image_${m_item_pk}`);
        this.cont_obj.item_description.html(m.description_detail);

        pop_title = m.title;

        let item_cnt = ns_cs.d.item[m_item_pk] ? ns_cs.d.item[m_item_pk].item_cnt : 0;
        this.cont_obj.item_amount.text(item_cnt);
        if (item_cnt > 0) {
            this.cont_obj.item_amount.removeCss('text_condition_no');
        } else {
            this.cont_obj.item_amount.addCss('text_condition_no');
        }
    } else {
        this.cont_obj.item_image.addCss(`resource_image_${data.sale_type}`);
        this.cont_obj.item_description.html(ns_i18n.t('market_special_item_description'));

        pop_title = ns_i18n.t(`resource_${data.sale_type}`) + ' ' + ns_util.numberFormat(data.sale_amount);

        if (data.sale_type === 'gold') {
            title = ns_util.numberFormat(ns_cs.d.terr.gold_curr.v);
        } else {
            title = ns_util.numberFormat(ns_cs.d.reso[data.sale_type + '_curr'].v);
        }
        this.cont_obj.item_amount.text(title);
        this.cont_obj.item_amount.addCss(`resource_${data.sale_type}`);
    }

    this.cont_obj.content_pop_title.text(pop_title);

    this.cont_obj.market_resource_price.addCss('resource_' + data.pay_reso_type);
    this.cont_obj.market_resource_price.text(ns_util.numberFormat(data.pay_reso_amount));

    if (data.pay_type === 'Q') {
        this.cont_obj.market_qbig_price.show().text(ns_util.numberFormat(data.pay_cash_amount));
    } else {
        this.cont_obj.market_qbig_price.hide();
    }
}

ns_dialog.dialogs.build_Market_buy.erase = function ()
{
    let data = this.data;

    if (data?.m_item_pk) {
        this.cont_obj.item_image.removeCss(`item_image_${data.m_item_pk}`);
    } else {
        this.cont_obj.item_image.removeCss(`resource_image_${data.sale_type}`);
        this.cont_obj.item_amount.removeCss(`resource_${data.sale_type}`);
    }
    this.cont_obj.market_resource_price.removeCss('resource_' + data.pay_reso_type);

    this.data = null;
}

/* ************************************************** */
ns_button.buttons.build_Market_buy_close = new nsButtonSet('build_Market_buy_close', 'button_pop_close', 'build_Market_buy', {base_class:ns_button.buttons.common_close});
ns_button.buttons.build_Market_buy_sub_close = new nsButtonSet('build_Market_buy_sub_close', 'button_full', 'build_Market_buy', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.market_buy = new nsButtonSet('market_buy', 'button_default', 'build_Market_buy');
ns_button.buttons.market_buy.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Market_buy;
    if ( !dialog.data) {
        ns_dialog.close('build_Market_buy');
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_market_empty_item')); // 없는 상품입니다.
        return;
    }
    let data = dialog.data;
    let sale_pk = data.sale_pk;

    if (data.pay_type === 'Q') {
        if (ns_util.math(ns_cs.d.cash.qbig.v).lt(data['pay_cash_amount'])) {
            ns_dialog.setDataOpen('confirm', { text : ns_i18n.t('msg_buy_qbig_confirm'), // 큐빅이 부족합니다.<br /><br />큐빅을 구매하시겠습니까?
                okFunc : () =>
                {
                    ns_engine.buyQbig();
                }
            });
            return;
        }
    }

    if (data['pay_reso_type'] !== 'gold') {
        if (ns_util.math(ns_cs.getResourceInfo(`${data['pay_reso_type']}_curr`)).lt(data['pay_reso_amount'])) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_market_need_resource', [ns_i18n.t(`resource_${data['pay_reso_type']}`)])); // 이(가) 부족합니다.
            return;
        }
    } else {
        if (ns_util.math(ns_cs.d.terr.gold_curr.v).lt(data['pay_reso_amount'])) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_resource_gold_lack')); // 황금이 부족합니다.
            return;
        }
    }

    let post_data = { };
    post_data['in_cast_pk'] = ns_dialog.dialogs.build_Market.data.castle_pk;
    post_data['sale_pk'] = sale_pk;

    ns_xhr.post('/api/market/buy', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_dialog.setDataOpen('message', ns_i18n.t('msg_market_buy_complete')); // 상품구매가 완료되었습니다.

        ns_dialog.dialogs.build_Market.drawList(); // 시장 상품리스트 다시 그리기

        ns_dialog.close('build_Market_buy'); // 구매창 닫기
    }, { useProgress: true });
}