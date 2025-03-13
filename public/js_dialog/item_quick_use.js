// 다이얼로그
ns_dialog.dialogs.item_quick_use = new nsDialogSet('item_quick_use', 'dialog_pop', 'size-large');
ns_dialog.dialogs.item_quick_use.buttons = [];

ns_dialog.dialogs.item_quick_use.cacheContents = function (e)
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.content_item_use_progress = new nsObject('.content_item_use_progress', this.obj);
    this.cont_obj.content_item_quick_use_list_wrap = new nsObject('.content_item_quick_use_list_wrap', this.obj);
    this.cont_obj.content_progress_target_icon = new nsObject('.progress_target_icon', this.obj);
    this.cont_obj.content_progress_bar = new nsObject('.wrapper_progress_bar', this.obj);
    this.cont_obj.content_remain_text = new nsObject('.remain_time_text', this.obj);

    this.cont_obj.item_quick_use_list_skeleton = new nsObject('#item_quick_use_list_skeleton');
}

ns_dialog.dialogs.item_quick_use.draw = function()
{
    if (this.data.type === 'speedup' || this.data.type === 'recall_speedup'){
        this.cont_obj.content_item_use_progress.show();
    }
    else {
        this.cont_obj.content_item_use_progress.hide();
    }

    if (this.data?.time_pk && this.data?.queue_type !== 'X') {
        let left_dt = ns_util.math(ns_cs.d.time[this.data.time_pk].end_dt_ut).minus(ns_timer.now()).integer;
        let queue_type = ns_cs.d.time[this.data.time_pk].queue_type;
        if (left_dt < ns_engine.cfg.free_speedup_time && ['C', 'T'].includes(queue_type)) {
            ns_dialog.close('item_quick_use');
            return;
        }

        if (ns_cs.d.queue[ns_cs.d.time[this.data.time_pk].queue_pk]) {
            let m_pk = ns_cs.d.queue[ns_cs.d.time[this.data.time_pk].queue_pk].master_pk;
            let class_type = `SRC_${m_pk}`;
            this.cont_obj.content_progress_target_icon.addCss(class_type);
        }
    }

    if (this.data?.queue_type && ['C', 'T'].includes(this.data.queue_type)) {
        let left_dt = this.getRemainTime();
        if (left_dt < ns_engine.cfg.free_speedup_time) {
            ns_dialog.close('item_quick_use');
            return;
        }
    }

    if (this.data?.queue_type){
        let icon_str = '';
        switch (this.data.queue_type) {
            case 'C':
                let const_data = ( this.data?.in_cast_pk ) ? ns_cs.d.bdic[this.data.in_cast_pk] : ns_cs.d.bdoc[this.data.in_cast_pk];
                icon_str = `SRC_${const_data.m_buil_pk}_${Math.max(3, Math.floor(const_data.level/3))}`;
                break;
            case 'W': // 외부성벽
                icon_str = `SRC_${ ns_cs.d.queue[ns_cs.d.time[this.data.time_pk].queue_pk].master_pk}`;
                break;
            case 'M':
            case 'F':
            case 'T':
            case 'A':
                let time_pk = ns_cs.getTimerPk(this.data.queue_type, null, this.data.position_type, this.data.in_cast_pk);
                icon_str = `SRC_${ ns_cs.d.queue[ns_cs.d.time[time_pk].queue_pk].time_pk}`;
                break;
            case 'X':
                icon_str = 'SRC_withdraw';
                break;
        }
        if (icon_str !== '') {
            this.cont_obj.content_progress_target_icon.addCss(icon_str);
        }
    }

    this.cont_obj.content_pop_title.text(ns_i18n.t('choose_item'));

    let _remain_time_text = ns_util.getCostsTime(this.getRemainTime());
    this.cont_obj.content_remain_text.text(_remain_time_text);

    this.drawList();
}

ns_dialog.dialogs.item_quick_use.erase = function ()
{
    // 독려 아이콘 초기화
    if ( this.cont_obj.content_progress_target_icon.element.classList.length >= 2 ) {
        let remove_class_arr = [];

        for(let _name of this.cont_obj.content_progress_target_icon.element.classList ){
            if (_name.includes('SRC_')) {
                this.cont_obj.content_progress_target_icon.element.classList.remove(_name);
            }
        }
    }

    this.cont_obj.content_progress_bar.element.style.width = `100%`; // 초기화
}

ns_dialog.dialogs.item_quick_use.drawList = function()
{
    let dialog = ns_dialog.dialogs.item_quick_use;
    let use_type = dialog.data?.type, list_cnt = 0;

    if (dialog.data?.time_pk) {
        let time_pk = dialog.data.time_pk;
        switch (ns_cs.d.time[time_pk].queue_type) {
            case 'A':
            case 'W':
            case 'M':
                use_type = 'speedup';
                break;
            case 'F':
                use_type = 'fort_speedup';
                break
            case 'X':
                use_type = 'recall_speedup';
                break;
        }
    }

    this.cont_obj.content_item_quick_use_list_wrap.empty();

    // 정렬을 위해 데이터 생성
    let item_sorted_array = [];
    for (let [k, d] of Object.entries(ns_cs.m.item)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        item_sorted_array.push({
            m_item_pk: d.m_item_pk,
            title: ns_i18n.t(`item_title_${d.m_item_pk}`),
            price: d.price,
            description_quickuse: d.description_quickuse,
            use_type: d.use_type,
            orderno: d.orderno
        });
    }
    item_sorted_array = ns_util.arraySort(item_sorted_array, -1, 'orderno');

    for (let v of item_sorted_array) {
        if(v.use_type === use_type) {
            let skeleton = this.cont_obj.item_quick_use_list_skeleton.clone();
            let d = ns_cs.d.item[v.m_item_pk];

            skeleton.addCss('item_list_' + v.m_item_pk);
            skeleton.find('.use_item_image').addCss('item_image_' + v.m_item_pk);

            let item_cnt = (d && d.item_pk === v.m_item_pk) ? d.item_cnt : 0;
            skeleton.find('.use_item_title').text(ns_i18n.t(`item_title_${v.m_item_pk}`));
            skeleton.find('.use_item_amount').text(item_cnt);

            skeleton.find('.use_item_qbig_amount').text((v.price > 0) ? v.price : this.getNeedQbig());
            skeleton.find('.quick_use_desc').text(v.description_quickuse);

            skeleton.setAttribute('id', `ns_button_item_quick_use_selected_${v.m_item_pk}`);

            this.cont_obj.content_item_quick_use_list_wrap.append(skeleton);

            ns_button.buttons[`item_quick_use_selected_${v.m_item_pk}`] = new nsButtonSet(`item_quick_use_selected_${v.m_item_pk}`, null, 'item_quick_use', { base_class: ns_button.buttons.item_quick_use_selected });
        }
    }
}



ns_dialog.dialogs.item_quick_use.getRemainTime = function()
{
    try {
        let dialog = ns_dialog.dialogs.item_quick_use;
        let data = dialog.data;
        let remain_time = 0;
        let time_pk = (! data?.time_pk) ? ns_cs.getTimerPk(data?.queue_type, data?.queue_pk, data?.position_type, data?.in_cast_pk) : data?.time_pk;
        if(time_pk) {
            remain_time = Math.max(ns_util.math(ns_cs.d.time[time_pk].end_dt_ut).minus(ns_timer.now()).integer, 0);
        }
        return remain_time;
    } catch (e) {
        return 0;
    }
}

ns_dialog.dialogs.item_quick_use.getNeedQbig = function ()
{
    let dialog = ns_dialog.dialogs.item_quick_use;
    let remain_time = 0;

    if (! dialog.data?.time_pk) {
        remain_time = dialog.getRemainTime();
    } else	{
        remain_time = ns_util.math(ns_cs.d.time[dialog.data.time_pk].end_dt_ut).minus(ns_timer.now()).integer;
    }

    return ns_util.getNeedQbig(remain_time);
}

ns_dialog.dialogs.item_quick_use.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';
    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.item_quick_use.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.item_quick_use.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.item_quick_use;

    if ( dialog.data?.type === 'speedup' || dialog.data?.type === 'recall_speedup' ) {
        let _remain_time = dialog.getRemainTime();
        if (_remain_time <= 5) { // 5초 이하로 남았을 경우, 종료하는 로직
            ns_dialog.close('item_quick_use');
            return;
        }

        // 즉시완료가 가능한 상황인 경우 메세지 출력 후 바로 닫기.
        if (dialog.data?.queue_type && ['C', 'T'].includes(dialog.data.queue_type)) {
            let left_dt = dialog.getRemainTime();
            if (left_dt < ns_engine.cfg.free_speedup_time) {
                ns_dialog.close('item_quick_use');
                ns_dialog.setDataOpen('message', ns_i18n.t('msg_free_speedup_ok')); // 건설시간이 5분 미만으로 남았을 경우<br /><br />독려버튼을 누르시면<br /><br />아이템 사용없이 건설이 즉시 완료됩니다.
                return;
            }
        }

        let _time_pk = (dialog.data.time_pk) ? dialog.data.time_pk : ns_cs.getTimerPk(dialog.data.queue_type, null, dialog.data.position_type, dialog.data.in_cast_pk);
        if (!_time_pk) {
            ns_dialog.close('item_quick_use');
            return;
        }

        let _total_time = ns_cs.d.time[_time_pk].build_time;
        let _rate = ns_util.math(_total_time - _remain_time).div(_total_time).mul(100).toFixed(1);

        dialog.cont_obj.content_remain_text.text(ns_util.getCostsTime(_remain_time));
        dialog.cont_obj.content_progress_bar.element.style.width = `${_rate}%`;

        dialog.cont_obj.content_item_quick_use_list_wrap.find('.use_item_qbig_amount').text(dialog.getNeedQbig());
    }
}

// 버튼
ns_button.buttons.item_quick_use_close = new nsButtonSet('item_quick_use_close', 'button_pop_close', 'item_use', { base_class: ns_button.buttons.common_close });
ns_button.buttons.item_quick_use_sub_close = new nsButtonSet('item_quick_use_sub_close', 'button_full', 'item_use', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.item_quick_use_selected = new nsButtonSet('item_quick_use_selected',  'button_default', 'item_use');
ns_button.buttons.item_quick_use_selected.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.item_quick_use;
    let item_quick_use_data = dialog.data;
    let arr = this.tag_id.split('_');
    let code = arr.pop();
    let d = ns_cs.d.item[code];
    let m = ns_cs.m.item[code];
    let item_cnt = (d) ? d.item_cnt : 0;

    if (m.yn_sell === 'N' && item_cnt <= 0 && m.type !== 'C' && m.type !== 'S') {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_quick_use_empty_item', [ns_i18n.t(`item_title_${code}`)])); // {{1}} 아이템을 보유하고 있지 않습니다.
        return;
    }
    if(item_cnt > 0) {
        try {
            const okFuncUseItem = function()
            {
                let post_data = null;
                if (['speedup', 'army_speedup', 'medical_speedup', 'fort_speedup', 'trade_speedup', 'medical_speedup', 'recall_speedup'].includes(m.use_type)) {
                    post_data = {};
                    post_data['action'] = 'use_item';
                    post_data['m_item_pk'] = code;
                    if (! item_quick_use_data.time_pk) {
                        post_data['type'] = item_quick_use_data.queue_type;
                        post_data['position_type'] = item_quick_use_data.position_type;
                        post_data['in_cast_pk'] = item_quick_use_data.in_cast_pk;
                        post_data['medi_hero_pk'] = item_quick_use_data.medi_hero_pk;
                        post_data['troo_pk'] = item_quick_use_data.troo_pk;
                    } else {
                        post_data['time_pk'] = item_quick_use_data.time_pk;
                    }

                    ns_xhr.post('/api/speedup', post_data, function (_data, _status)
                    {
                        if(! ns_xhr.returnCheck(_data)) {
                            return;
                        }
                        _data = _data['ns_xhr_return']['add_data'];
                        ns_dialog.close('item_quick_use');
                        if (item_quick_use_data.queue_type === 'M') {
                            // qbw_dlg.dlgs.buil_Medical.draw_remote();
                        } else if (item_quick_use_data.queue_type === 'X') {
                            ns_dialog.close('troop_view');
                        } else if (m.use_type === 'trade_speedup') {
                            ns_dialog.dialogs.build_TradeDept_delivery.reload_state = true;
                        }
                    }, { useProgress: true });
                } else if (['enchant', 'invitation', 'demolish', 'troop_order', 'encounter', 'enchant_init', 'enchant_jewel', 'ann'].includes(m.use_type)) {
                    if (item_quick_use_data?.callbackFunc && typeof item_quick_use_data.callbackFunc === 'function') {
                        if (m.use_type !== 'troop_order' && m.use_type !== 'ann') {
                            // $('#wrap_trans').show();
                        }
                        item_quick_use_data.callbackFunc(code);
                    }
                    ns_dialog.close('item_quick_use');
                } else {
                    post_data = {};
                    post_data['action'] = 'use_item';
                    post_data['item_pk'] = code;

                    ns_xhr.post('/api/item/use', post_data, function (_data)
                    {
                        if (_data['ns_xhr_return']['code'] === 'ERR' && _data['ns_xhr_return']['add_data'] !== null) {
                            let remain_dt = _data['ns_xhr_return']['add_data'];
                            if (ns_util.math(code).eq(500023)) {
                                ns_dialog.setDataOpen('message', ns_i18n.t('msg_manifesto_remain_time_error', [ns_util.getCostsTime(3600 - remain_dt)]));
                            }
                            return;
                        }
                        if(! ns_xhr.returnCheck(_data)) {
                            return;
                        }
                        _data = _data['ns_xhr_return']['add_data'];

                        //시장 상품목록 갱신 아이템
                        if (ns_util.math(code).eq(500016)) {
                            if (ns_dialog.dialogs.build_Market.visible) {
                                ns_dialog.dialogs.build_Market.drawList();
                            }
                        } else if (ns_util.math(code).eq(500155)) {
                            // 영웅 거래 초기화
                            // ns_dialog.dialogs.hero_trade.obj.find('.cont_bid_count').html('0/30');
                        }

                        ns_dialog.close('item_quick_use');
                    }, { useProgress: true });
                }
            };

            ns_dialog.close('item_quick_use');
            let message_text = '';
            /*if (ns_util.math(code).eq(500084)) {
                message_text = system_text.message.troop_order_item;
            }*/
            if (ns_util.math(code).eq(500165)) {
                if (ns_util.math(ns_cs.d.terr.population_curr.v).plus(2000).gt(ns_cs.d.terr.population_max.v)) {
                    message_text += ns_i18n.t('msg_limit_population_alert', [ns_util.numberFormat(ns_cs.d.terr.population_max.v)]) + '<br /><br />';
                }
            }
            message_text += ns_i18n.t('msg_use_item', [1]); // 아이템을 사용하시겠습니까?

            ns_dialog.setDataOpen('confirm', { text: message_text, okFunc: okFuncUseItem,
                noFunc: function ()
                {
                    ns_dialog.setDataOpen('item_quick_use', item_quick_use_data);
                }
            });
        } catch (e) {
            console.log(e);
        }
    } else {
        if(ns_util.math(ns_cs.d.cash.qbig.v).lte(0)) {
            try {
                ns_dialog.setDataOpen('confirm', { text : ns_i18n.t('msg_buy_qbig_confirm'), // 큐빅이 부족합니다.<br /><br />큐빅을 구매하시겠습니까?
                    okFunc : function()
                    {
                        ns_engine.buyQbig();
                    }
                });
            } catch (e) {
                console.error(e);
            }
            return;
        }
        if(ns_util.math(ns_cs.m.item[code].price).eq(0) && ns_util.math(ns_cs.d.cash.qbig.v).lt(dialog.getNeedQbig())) {
            try {
                ns_dialog.setDataOpen('confirm', { text : ns_i18n.t('msg_buy_qbig_confirm'), // 큐빅이 부족합니다.<br /><br />큐빅을 구매하시겠습니까?
                    okFunc : function()
                    {
                        ns_engine.buyQbig();
                    }
                });
            } catch (e) {
                console.error(e);
            }
            return;
        }
        try {
            const noFuncBuyItem = function()
            {
                ns_dialog.setDataOpen('item_quick_use', item_quick_use_data);
            }

            const okFuncBuyItem = function()
            {
                if (ns_util.math(ns_cs.d.cash.qbig.v).lt(ns_cs.m.item[code].price)) {
                    ns_dialog.setDataOpen('confirm', { text : ns_i18n.t('msg_buy_qbig_confirm'), // 큐빅이 부족합니다.<br /><br />큐빅을 구매하시겠습니까?
                        okFunc : function ()
                        {
                            ns_engine.buyQbig();
                        },
                        noFunc : noFuncBuyItem
                    });
                    ns_dialog.close('item_quick_use');
                } else {
                    let m = ns_cs.m.item[code];
                    if (['speedup', 'army_speedup', 'medical_speedup', 'fort_speedup', 'trade_speedup', 'medical_speedup', 'recall_speedup'].includes(m.use_type)) {
                        let post_data = {};
                        post_data['action'] = 'buy_use_item';
                        post_data['m_item_pk'] = code;
                        if (! item_quick_use_data.time_pk) {
                            post_data['type'] = item_quick_use_data.queue_type;
                            post_data['position_type'] = item_quick_use_data.position_type;
                            post_data['in_cast_pk'] = item_quick_use_data.in_cast_pk;
                            post_data['medi_hero_pk'] = item_quick_use_data.medi_hero_pk;
                            post_data['troo_pk'] = item_quick_use_data.troo_pk;
                        } else {
                            post_data['time_pk'] = item_quick_use_data.time_pk;
                        }

                        ns_xhr.post('/api/speedup', post_data, function(_data, _status)
                        {
                            if(! ns_xhr.returnCheck(_data)) {
                                return;
                            }
                            _data = _data['ns_xhr_return']['add_data'];
                            ns_dialog.close('item_quick_use');
                            if (item_quick_use_data.queue_type === 'M') {
                                ns_dialog.dialogs.build_Medical.drawRemote();
                            } else if (item_quick_use_data.queue_type === 'X') {
                                ns_dialog.close('troop_view');
                            } else if (m.use_type === 'trade_speedup') {
                                ns_dialog.dialogs.build_TradeDept_delivery.reload_state = true;
                            }
                        }, { useProgress: true });
                    } else if (['enchant', 'invitation', 'demolish', 'troop_order', 'encounter', 'enchant_init', 'enchant_jewel', 'enchant_jewel', 'ann'].includes(m.use_type)) {
                        let post_data = {};
                        post_data['item_pk'] = code;
                        post_data['count'] = 1;
                        ns_xhr.post('/api/item/buy', post_data, function(_data)
                        {
                            if(! ns_xhr.returnCheck(_data)) {
                                return;
                            }
                            _data = _data['ns_xhr_return']['add_data'];

                            let m = ns_cs.m.item[_data.item_pk];
                            if (m.use_type !== 'enchant_init') {
                                let _text = ns_i18n.t('msg_item_buy_with_qbig', [
                                    ns_i18n.t(`item_title_${_data.item_pk}`),
                                    _data.item_count,
                                    ns_util.numberFormat(_data.cash)
                                ]);
                                ns_dialog.setDataOpen('message',  _text);
                            }


                            if (item_quick_use_data?.callbackFunc && typeof item_quick_use_data.callbackFunc === 'function') {
                                item_quick_use_data.callbackFunc(code);
                            }

                            ns_dialog.close('item_quick_use');
                        }, { useProgress: true });
                    } else {  // 상품 목록 갱신 처리 추가
                        let post_data = {};
                        post_data['action'] = 'buy_use_item';
                        post_data['item_pk'] = code;
                        ns_xhr.post('/api/item/use', post_data, function(_data)
                        {
                            if (_data['ns_xhr_return']['code'] === 'ERR' && _data['ns_xhr_return']['add_data'] !== null) {
                                let remain_dt = _data['ns_xhr_return']['add_data'];
                                if (ns_util.math(code).eq(500023)) {
                                    ns_dialog.setDataOpen('message', ns_i18n.t('msg_manifesto_remain_time_error', [ns_util.getCostsTime(3600 - remain_dt)]));
                                }
                                return;
                            }
                            if(! ns_xhr.returnCheck(_data)) {
                                return;
                            }
                            _data = _data['ns_xhr_return']['add_data'];

                            //시장 상품목록 갱신 아이템
                            if (ns_util.math(code).eq(500016)) {
                                if (ns_dialog.dialogs.build_Market.visible) {
                                    ns_dialog.dialogs.build_Market.drawList();
                                }
                            }

                            if (['population', 'gold', 'food', 'horse', 'lumber', 'iron'].includes(m.use_type)) {
                                // TODO if (qbw_dlg.dlgs.terr_info.visible)
                                // TODO   qbw_dlg.dlgs.terr_info.draw_buff();
                            }

                            ns_dialog.close('item_quick_use');
                        }, { useProgress: true });
                    }
                }
            };

            if (['500078', '500081', '500082'].includes(code)) {
                let need_qbig = dialog.getNeedQbig();
                if (ns_util.math(need_qbig).lte(0)) {
                    ns_dialog.setDataOpen('message', ns_i18n.t('msg_quick_use_no_effect')); // 사용해도 효과가 없습니다.
                } else {
                    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_item_quick_use_need_qbig', [`<span class="content_item_qbig_amount">${need_qbig}</span>`]), okFunc: okFuncBuyItem, noFunc: noFuncBuyItem });
                }
            } else {
                let _confirm_text = '';
                if (ns_util.math(code).eq(500165)) {
                    if (ns_util.math(ns_cs.d.terr.population_curr.v).plus(2000).gt(ns_cs.d.terr.population_max.v)) {
                        _confirm_text += ns_i18n.t('msg_limit_population_alert', [ns_util.numberFormat(ns_cs.d.terr.population_max.v)]) + '<br /><br />';
                    }
                }
                _confirm_text += ns_i18n.t('msg_need_buy_item', [ns_i18n.t(`item_title_${code}`), `<span class="content_item_qbig_amount">${ns_util.numberFormat(ns_cs.m.item[code].price)}</span>`, `<span class="content_item_qbig_amount">${ns_util.numberFormat(ns_cs.d.cash.qbig.v)}</span>`]);
                ns_dialog.setDataOpen('confirm', { text: _confirm_text, okFunc: okFuncBuyItem, noFunc: noFuncBuyItem });
            }
            ns_dialog.close('item_quick_use');
        } catch (e) {
            console.error(e);
        }
    }
}