// 다이얼로그
ns_dialog.dialogs.item_use = new nsDialogSet('item_use', 'dialog_pop', 'size-small');

ns_dialog.dialogs.item_use.cacheContents = function (e)
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.item_image = new nsObject('.item_image', this.obj);
    this.cont_obj.item_description = new nsObject('.item_description', this.obj);
    this.cont_obj.item_amount = new nsObject('.item_amount', this.obj);
    this.cont_obj.qbig_amount = new nsObject('.qbig_amount', this.obj);

    this.cont_obj.item_use_amount = new nsObject('.item_use_amount', this.obj);
    this.cont_obj.item_use_slider_wrap = new nsObject('.item_use_slider_wrap', this.obj);

    this.cont_obj.item_use_slider = new nsObject('input[name=item_use_slider]', this.obj);
    this.cont_obj.item_use_slider.setEvent('input', (_e) => {
        this.cont_obj.item_use_amount.text(_e.target.value);
    });
}

ns_dialog.dialogs.item_use.draw = function()
{
    let m_item_pk = this.data.m_item_pk;
    let m = ns_cs.m.item[m_item_pk];

    this.cont_obj.content_pop_title.text(m.title);
    this.cont_obj.item_image.addCss(`item_image_${m_item_pk}`);
    let description_detail = m.description_detail;
    if (m.use_type === 'package' && m.supply_amount !== '') {
        description_detail = description_detail.replace(/\{\{item\}\}/g, ns_util.convertPackageDescription(m_item_pk));
    }
    this.cont_obj.item_description.html(description_detail);

    let item_cnt = ns_cs.d.item[m_item_pk] ? ns_cs.d.item[m_item_pk].item_cnt : 0;
    this.cont_obj.item_amount.text(item_cnt);
    this.cont_obj.qbig_amount.text(m.price);

    let max_cnt = item_cnt;
    if (ns_util.math(max_cnt).gt(10)) {
        max_cnt = 10; // 한번에 최대 10개만 사용 가능하도록 제한.
    }

    this.cont_obj.item_use_slider.setAttribute('min', 1);
    this.cont_obj.item_use_slider.setAttribute('max', max_cnt);
    this.cont_obj.item_use_slider.value(1);
    this.cont_obj.item_use_amount.text(1);

    if (m.yn_myitem_use === 'Y') {
        ns_button.buttons.item_use_ok.setEnable();
        this.cont_obj.item_use_slider_wrap.show();
    } else {
        ns_button.buttons.item_use_ok.setDisable();
        this.cont_obj.item_use_slider_wrap.hide();
    }

    if (m.yn_use_duplication_item !== 'Y') {
        this.cont_obj.item_use_slider_wrap.hide();
    }

    if (ns_util.math(item_cnt).lte(1)) {
        // 아이템이 1개만 소지하고 있는 경우 슬라이더 숨김. TODO 추가적으로 1개씩만 사용가능한 아이템에 경우 슬라이더 숨겨야함.
        this.cont_obj.item_use_slider_wrap.hide();
    }
}

ns_dialog.dialogs.item_use.erase = function ()
{
    let m_item_pk = this.data.m_item_pk;
    this.cont_obj.item_image.removeCss(`item_image_${m_item_pk}`);
}

ns_dialog.dialogs.item_use.useItemResult = function (_data)
{
    let dialog = ns_dialog.dialogs.item_use;

    if (_data?.m_item_pk && ns_util.math(_data.m_item_pk).eq(500061)) {
        // 매직 큐브
        ns_dialog.closeAll();
        ns_dialog.setDataOpen('magic_cube', { autostart: true });
    } else if (_data?.m_item_pk && (ns_util.math(_data.m_item_pk).eq(500017) || ns_util.math(_data.m_item_pk).eq(500122) || ns_util.math(_data.m_item_pk).eq(500123) || ns_util.math(_data.m_item_pk).eq(500133))) {
        // 월드맵 처리
        ns_engine.game_data.cpp = _data.move_posi_pk;
        ns_world.current_posi_pk = _data.move_posi_pk;

        // 영지전환
        // ns_cs.d.terr.s.terrtory_position?.text(_data.move_posi_pk);
        ns_dialog.closeAll();
        if (ns_engine.game_data.curr_view !== 'world') {
            ns_engine.toggleWorld();
        } else {
            const [x, y] = _data.move_posi_pk.split('x');
            ns_world.setPosition(x, y);
        }

        // 영지 이동 알림
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_item_use_apply', [ns_i18n.t(`item_title_${_data.m_item_pk}`)]));
    } else if (_data?.m_item_pk && ns_util.math(_data.m_item_pk).eq(500015)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_item_use_peace'));

        // 월드맵 변경된값 적용
        ns_engine.cfg.world_tick = 1;
        ns_timer.worldReloadTick();
    } else if (_data?.m_item_pk && ns_util.math(_data.m_item_pk).eq(500096)) {
        ns_dialog.open('lord_info_card_change');
    } else if (_data?.m_item_pk && ns_util.math(_data.m_item_pk).eq(500014)) {
        ns_dialog.open('lord_info_lord_name_change');
    } else if (_data?.package_type) {
        // 패키지 아이템 처리
        if (['army', 'item', 'skill_pocket'].includes(_data.package_type)) {
            // 병력지원, 아이템, 기술 주머니
            ns_dialog.setDataOpen('message', _data);
        } else if (_data.package_type === 'hero') {
            // TODO 영웅 획득 연출 처리 필요함. - 일단 주석처리 후 메세지로 대응
            // ns_dialog.dialogs.card.effect_draw = true;
            // ns_dialog.setDataOpen('card', { d: _data.hero, 'pick': _data.pick });
            let hero_base = ns_cs.m.hero_base[ns_cs.m.hero[_data.hero.m_hero_pk].m_hero_base_pk];
            ns_dialog.setDataOpen('message', 'Lv.' + _data.hero.level + ' ' + hero_base.name);
        } else if (_data.package_type === 'skill_box') {
            // 기술 상자
            ns_dialog.setDataOpen('hero_skill_box_list', { my_hero_skil_box_pk: _data.my_hero_skil_box_pk, m_item_pk: _data.m_item_pk, skill_list: _data.skill_list });
        } else if (_data.package_type === 'coupon') {
            // 쿠폰아이템 사용
            ns_dialog.setDataOpen('message', '쿠폰번호가 외교서신으로 발송되었습니다.');
        }
    } else {
        if (! _data?.m_item_pk) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_item_use_ok')); // 아이템을 사용하였습니다.
        } else {
            ns_dialog.setDataOpen('message', { m_item_pk: _data.m_item_pk, item_cnt: _data.item_cnt });
        }
    }
    if (dialog.visible) {
        ns_dialog.close('item_use');
    }
    if (ns_dialog.dialogs.resource_manage.visible) {
        ns_dialog.dialogs.resource_manage.drawTab(ns_dialog.dialogs.resource_manage.current_tab);
    }
}

// 버튼
ns_button.buttons.item_use_close = new nsButtonSet('item_use_close', 'button_pop_close', 'item_use', { base_class: ns_button.buttons.common_close });
ns_button.buttons.item_use_sub_close = new nsButtonSet('item_use_sub_close', 'button_full', 'item_use', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.item_use_ok = new nsButtonSet('item_use_ok',  'button_pop_normal', 'item_use');
ns_button.buttons.item_use_ok.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.item_use;
    let item_use_data = dialog.data;
    let m_item_pk = item_use_data.m_item_pk;
    let d = ns_cs.d.item[m_item_pk], item_cnt = 0;
    let m = ns_cs.m.item[m_item_pk];
    let item_use_amount = dialog.cont_obj.item_use_slider.value();

    // 버튼이 disable 된 상태에서도 사용되어 추가.
    if (this.enable === false || m.yn_myitem_use !== 'Y') {
        return;
    }

    item_cnt = (d) ? d.item_cnt : 0;
    if (m.yn_use_duplication_item !== 'Y') {
        // 중복 사용 불가인 경우 강제로 1개 사용.
        dialog.cont_obj.item_use_slider.value(1);
        item_use_amount = 1;
    }

    if (item_cnt > 0) {
        let use_message = ns_i18n.t('msg_use_item', [item_use_amount]);

        if (m.use_type === 'reso') {
            let amount = m.supply_amount.split(':');
            let resource_amount = ns_util.math(amount[1]).mul(item_use_amount).number;
            let max_resource = ns_util.toInteger(ns_cs.d.reso[amount[0] + '_max'].v);
            let curr_resource = ns_util.toInteger(ns_cs.d.reso[amount[0] + '_curr'].v);
            let result_resource = ns_util.math(curr_resource).plus(resource_amount).integer;
            let loss_resource = ns_util.math(result_resource).minus(max_resource).integer;

            if (ns_util.math(max_resource).lte(curr_resource)) {
                ns_dialog.setDataOpen('message', ns_i18n.t('msg_max_resource_alert', [ns_i18n.t(`resource_${amount[0]}`)])); // 이미 {{1}} 최대치를 보유 중 입니다.
                return;
            }

            if (loss_resource > 0) {
                use_message = ns_i18n.t('msg_item_detail_max_num_slot', [ns_i18n.t(`resource_${amount[0]}`), loss_resource.toString()]); // 아이템을 사용하시면<br />{{1}} 최대 보유량을 초과하여<br />자원 손실이 발생 할 수 있습니다.<br /><br /><span class="text_red">손실량: {{2}}</span><br />
            }
        } else if (m.use_type === 'hero' || m.use_type === 'gachapon') {
            ns_dialog.closeAll(); // 다른창 일괄 닫기
            ns_dialog.open('hero_pickup');
            return;
        }

        if (ns_util.math(m_item_pk).eq(500163)) {
            ns_button.buttons.hero_manage_officer_deal.mouseUp();
        } else if (ns_util.math(m_item_pk).eq(500019)) {
            ns_button.buttons.hero_manage_hero_slot.mouseUp();
        } else if (ns_util.math(m_item_pk).eq(500096)) {
            ns_dialog.close('item_use');
            ns_dialog.open('lord_info_card_change');
            return;
        } else if (ns_util.math(m_item_pk).eq(500014)) {
            dialog.trans_status = true;
            ns_dialog.open('lord_info_lord_name_change');
            return;
        } else if (ns_util.math(m_item_pk).eq(500061)) {
            ns_dialog.setDataOpen('confirm', {
                text: use_message, okFunc: function(){
                    ns_dialog.closeAll();
                    ns_dialog.setDataOpen('magic_cube', { autostart: true });
                }
            });
            return;
        } else if (ns_util.math(m_item_pk).eq(500122)) {
            ns_dialog.open('terr_move_item_state');
            return;
        } else if (ns_util.math(m_item_pk).eq(500133)) {
            let post_data = {
                action : 'use_item',
                item_pk : 500133,
                item_cnt: item_use_amount,
                state : ns_engine.game_data.cpp
            };
            ns_xhr.post('/api/item/use', post_data, function (_data, _status) {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];
                ns_dialog.dialogs.item_use.useItemResult(_data);
            }, { useProgress: true });
            return;
        } else if (ns_util.math(m_item_pk).eq(500020)) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_unavailable_item'));
            return;
        }

        try {
            if (m.type === 'Z') {
                use_message = ns_i18n.t('msg_use_item_need_qbig', [m.title, m.price]); // {{1}} 아이템을 사용하시겠습니까?<br />사용을 위해서는 {{2}}큐빅이 필요합니다.
            }

            const okItemUse = function ()
            {
                if (m.type === 'Z') {
                    // 큐빅 체크
                    if (ns_util.math(ns_cs.d.cash.qbig.v).lte(0) || ns_util.math(ns_cs.d.cash.qbig.v).lt(m.price)) {
                        // 부족하면 큐빅 구매
                        ns_dialog.setDataOpen('confirm', { text : ns_i18n.t('msg_buy_qbig_confirm'), // 큐빅이 부족합니다.<br /><br />큐빅을 구매하시겠습니까?
                            okFunc : function()
                            {
                                ns_engine.buyQbig();
                            }
                        });
                        return false;
                    }
                }

                let post_data = { action : 'use_item', item_pk : m_item_pk, use_type : 'item_use', item_cnt: item_use_amount };
                ns_xhr.post('/api/item/use', post_data, function(_data) {
                    if (_data['ns_xhr_return']['code'] === 'ERR' && _data['ns_xhr_return']['add_data'] !== null) {
                        if (ns_util.math(m_item_pk).eq(500015)) {
                            // <br />평화서약은<br /><strong>$1</strong>후에 사용 가능합니다.
                            ns_dialog.setDataOpen('message', ns_i18n.t('msg_item_use_peace_remain', [ns_util.getCostsTime(ns_util.math(86400).minus(_data['ns_xhr_return']['add_data']).integer)]));
                        } else if (ns_util.math(m_item_pk).eq(500023)) {
                            ns_dialog.setDataOpen('message', ns_i18n.t('msg_manifesto_remain_time_error', [ns_util.getCostsTime(ns_util.math(3600).minus(_data['ns_xhr_return']['add_data']).integer)]));
                        } else if (m.use_type === 'skill_box') {
                            _data = _data['ns_xhr_return']['add_data'];
                            if (_data['key_pk']) {
                                // [$1] 아이템이 없으면 [$2] 아이템을 열 수 없습니다. [$1] 아이템은 아이템샵 > 패키지 탭에서<br />구매 가능합니다.
                                ns_dialog.setDataOpen('message', ns_i18n.t('msg_item_use_need_key_item', [ns_cs.m.item[_data['key_pk']].title, ns_cs.m.item[_data.m_item_pk].title]));
                            } else {
                                try {
                                    ns_dialog.setDataOpen('confirm', {
                                        text : ns_i18n.t('msg_hero_skill_not_used_box'), // 기술상자를 열면 받을 수 있는 기술이<br />3개 나오고 이 중 하나만 받을 수 있습니다.<br /><br />군주님께서는 이미 열었으나<br />받을 기술을 선택하지 않은 상자가 있습니다.<br />미선택 기술상자를 여시겠습니까?
                                        okFunc : function ()
                                        {
                                            ns_dialog.setDataOpen('hero_skill_box_list', {
                                                my_hero_skil_box_pk: _data.my_hero_skil_box_pk,
                                                m_item_pk: _data.m_item_pk,
                                                skill_list: _data.skill_list
                                            });
                                        },
                                        noFunc : function ()
                                        {
                                            dialog.close();
                                        }
                                    });
                                } catch (e) {
                                    console.error(e);
                                }
                            }
                        }
                        return;
                    }

                    if(! ns_xhr.returnCheck(_data)) {
                        return;
                    }
                    _data = _data['ns_xhr_return']['add_data'];

                    if (typeof item_use_data.callback === "function") {
                        item_use_data.callback(_data);
                    } else {
                        ns_dialog.dialogs.item_use.useItemResult(_data);
                    }
                }, { useProgress: true });
            };

            ns_dialog.setDataOpen('confirm', { text: use_message, okFunc: okItemUse });
        } catch (e) {
            console.error(e);
        } finally {
            ns_dialog.close('item_use');
        }
    } else {
        if (ns_util.math(ns_cs.d.cash.qbig.v).gt(0) && ns_util.math(ns_cs.d.cash.qbig.v).gte(m.price)) {
            let buy_use_message;
            if (ns_util.math(m_item_pk).eq(500019)) {
                // 영입영웅슬롯 확장은 최대 $1까지 가능합니다.<br/><br/>현재 $2 / 최대 $3<br/><br/>아이템을 구매 후 사용하시겠습니까?
                buy_use_message =  ns_i18n.t('msg_hero_slot_max_error', [ns_engine.cfg.max_num_slot_guest_hero, ns_cs.d.lord.num_slot_guest_hero.v, ns_engine.cfg.max_num_slot_guest_hero]);
            } else if (ns_util.math(m_item_pk).eq(500102)) {
                buy_use_message = ns_i18n.t('msg_construction_max_queue'); // 건설은 기본적으로 1개가 가능하며<br/>"건설 허가서" 아이템을 사용하여 건설을<br/>동시에 3개까지 진행할 수 있습니다.<br/><br/>"건설허가서" 를 구매 후 사용하시겠습니까?
            } else {
                buy_use_message = ns_i18n.t('msg_need_buy_item', [m.title, `<span class="content_item_qbig_amount">${ns_util.numberFormat(m.price)}</span>`, `<span class="content_item_qbig_amount">${ns_util.numberFormat(ns_cs.d.cash.qbig.v)}</span>`])
            }
            try {
                dialog.trans_status = true;
                ns_dialog.setDataOpen('confirm', {
                    text : buy_use_message,
                    okFunc : function ()
                    {
                        if (ns_util.math(m_item_pk).eq(500013) || ns_util.math(m_item_pk).eq(500014) || ns_util.math(m_item_pk).eq(500096)) {
                            // 군주명 변경, 깃발명 변경, 군주 카드 변경
                            let post_data = { };
                            post_data['item_pk'] = m_item_pk;
                            post_data['count'] = 1;
                            ns_xhr.post('/api/item/buy', post_data, function (__data)
                            {
                                if(! ns_xhr.returnCheck(__data)) {
                                    return;
                                }
                                ns_dialog.dialogs.item_use.useItemResult({ m_item_pk: m_item_pk });
                            });
                            return;
                        }

                        let post_data = { action:'buy_use_item', item_pk: m_item_pk, item_cnt: item_use_amount};
                        ns_xhr.post('/api/item/use', post_data, function(__data) {
                            if(! ns_xhr.returnCheck(__data)) {
                                return;
                            }
                            __data = __data['ns_xhr_return']['add_data'];
                            if (! __data?.m_item_pk) {
                                __data['m_item_pk'] = m_item_pk;
                            }
                            if (typeof item_use_data.callback === "function") {
                                item_use_data.callback(__data);
                            } else {
                                ns_dialog.dialogs.item_use.useItemResult(__data);
                            }
                        }, { useProgress: true });
                    }
                });
            } catch (e) {
                console.error(e);
            }
        } else {
            try {
                if (ns_cs.m.item[m_item_pk].yn_sell !== 'Y') {
                    ns_dialog.setDataOpen('message', ns_i18n.t('msg_unavailable_purchase_item')); // 구매 할 수 없는 상품입니다.
                } else {
                    ns_dialog.setDataOpen('confirm', {
                        text : ns_i18n.t('msg_buy_qbig_confirm'), // 큐빅이 부족합니다.<br /><br />큐빅을 구매하시겠습니까?
                        okFunc : function ()
                        {
                            ns_engine.buyQbig();
                        }
                    });
                }
            } catch (e) {
                console.error(e);
            }
        }
    }

    if (ns_dialog.dialogs.item_use.visible) {
        ns_dialog.close('item_use');
    }
}

ns_button.buttons.item_use_amount_decrease = new nsButtonSet('item_use_amount_decrease', 'button_decrease', 'item_use');
ns_button.buttons.item_use_amount_decrease.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.item_use;

    let current_value = ns_util.math(dialog.cont_obj.item_use_slider.value()).integer,
        minimum_value = ns_util.math(dialog.cont_obj.item_use_slider.element.min).integer;

    current_value = Math.max(--current_value, minimum_value);

    dialog.cont_obj.item_use_slider.value(current_value);
    dialog.cont_obj.item_use_amount.text(current_value);
}

ns_button.buttons.item_use_amount_increase = new nsButtonSet('item_use_amount_increase', 'button_increase', 'item_use');
ns_button.buttons.item_use_amount_increase.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.item_use;

    let current_value = ns_util.math(dialog.cont_obj.item_use_slider.value()).integer,
        maximum_value = ns_util.math(dialog.cont_obj.item_use_slider.element.max).integer;

    current_value = Math.min(++current_value, maximum_value);

    dialog.cont_obj.item_use_slider.value(current_value);
    dialog.cont_obj.item_use_amount.text(current_value);
}