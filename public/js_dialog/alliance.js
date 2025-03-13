ns_dialog.dialogs.alliance = new nsDialogSet('alliance', 'dialog_full', 'size-full');
ns_dialog.dialogs.alliance.tab_list = ['make', 'join', 'info', 'manage'];
ns_dialog.dialogs.alliance.sub_tab_list = ['info', 'member_list', 'war_list'];
ns_dialog.dialogs.alliance.current_page = 1;
ns_dialog.dialogs.alliance.total_page = null;

ns_dialog.dialogs.alliance.cacheContents = function()
{
    // 메인 탭
    this.cont_obj.tab_make_wrap = new nsObject('.tab_make_wrap', this.obj);
    this.cont_obj.tab_join_wrap = new nsObject('.tab_join_wrap', this.obj);
    this.cont_obj.tab_info_wrap = new nsObject('.tab_info_wrap', this.obj);
    this.cont_obj.tab_manage_wrap = new nsObject('.tab_manage_wrap', this.obj);

    // 서브 탭
    this.cont_obj.alliance_sub_tab = new nsObject('.alliance_sub_tab', this.obj);
    this.cont_obj.sub_tab_info_wrap = new nsObject('.sub_tab_info_wrap', this.obj);
    this.cont_obj.sub_tab_member_list_wrap = new nsObject('.sub_tab_member_list_wrap', this.obj);
    this.cont_obj.sub_tab_war_list_wrap = new nsObject('.sub_tab_war_list_wrap', this.obj);

    this.cont_obj.alliance_title_input = new nsObject('input[name=alliance_title]', this.obj);
    this.cont_obj.alliance_search_title_input = new nsObject('input[name=alliance_search_title]', this.obj);

    this.cont_obj.search_list_tbody = new nsObject('.search_list_tbody', this.obj);
    this.cont_obj.member_list_tbody = new nsObject('.member_list_tbody', this.obj);
    this.cont_obj.war_list_tbody = new nsObject('.war_list_tbody', this.obj);

};

ns_dialog.dialogs.alliance.draw = function()
{
    ns_button.buttons.alliance_tab_join.obj.hide();
    ns_button.buttons.alliance_tab_make.obj.hide();
    ns_button.buttons.alliance_tab_info.obj.hide();
    ns_button.buttons.alliance_tab_manage.obj.hide();

    if (! this.visible) {
        if (ns_cs.d.lord?.alli_pk?.v && ns_cs.d.lord.alli_pk.v !== 0) {
            this.cont_obj.alliance_sub_tab.show();
            ns_button.toggleGroupSingle(ns_button.buttons.alliance_tab_info);
            ns_button.buttons.alliance_tab_info.obj.show();
            ns_button.buttons.alliance_tab_manage.obj.show();
        } else {
            this.cont_obj.tab_make_wrap.show();
            this.cont_obj.alliance_sub_tab.hide();
            ns_button.toggleGroupSingle(ns_button.buttons.alliance_tab_join);
            ns_button.buttons.alliance_tab_join.obj.show();
            ns_button.buttons.alliance_tab_make.obj.show();
        }
        ns_button.toggleGroupSingle(ns_button.buttons.alliance_sub_tab_info);
        ns_dialog.dialogs.alliance.drawContents();
    }
}

ns_dialog.dialogs.alliance.drawContents = function()
{
    let dialog = ns_dialog.dialogs.alliance;
    let tab = ns_button.toggleGroupValue('alliance_tab')[0].split('_tab_').pop();
    let sub_tab = ns_button.toggleGroupValue('alliance_sub_tab')[0].split('_sub_tab_').pop();

    for (let _tab of ns_dialog.dialogs.alliance.tab_list) {
        this.cont_obj[`tab_${_tab}_wrap`].hide();
    }
    dialog.cont_obj[`tab_${tab}_wrap`].show();

    if (ns_cs.d.lord?.alli_pk?.v && ns_cs.d.lord.alli_pk.v !== 0) {
        if (tab === 'info') {
            for (let _sub_tab of ns_dialog.dialogs.alliance.sub_tab_list) {
                dialog.cont_obj[`sub_tab_${_sub_tab}_wrap`].hide();
            }
            dialog.cont_obj[`sub_tab_${sub_tab}_wrap`].show();
            if (sub_tab === 'info') {
                dialog.drawAllianceInfo();
            } else if (sub_tab === 'member_list') {
                dialog.drawMemberList();
            } else if (sub_tab === 'war_list') {
                this.drawWarList();
            }
        } else if (tab === 'manage') {
            this.drawButtonList();
        }
    } else {
        if (tab === 'join') {
            dialog.cont_obj.alliance_search_title_input.value('');
            dialog.drawAllianceList();
        } else if (tab === 'make') {
            dialog.cont_obj.alliance_title_input.value('');
        }
    }
};

ns_dialog.dialogs.alliance.drawAllianceList = function()
{
    let dialog = ns_dialog.dialogs.alliance;
    let alliance_search_title = dialog.cont_obj.alliance_search_title_input.value();
    let post_data = {};
    post_data['action'] = 'search_default';
    post_data['alliance_title'] = alliance_search_title;
    post_data['page_type'] = 'alliance_active';
    ns_xhr.post('/api/alliance/searchAlliance', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.cont_obj.search_list_tbody.empty();

        if (Object.keys(_data.list).length < 1) { // 리스트가 없을 때
            let tr = document.createElement('tr');
            let col1 = document.createElement('td');
            col1.colSpan = 8;
            let col1_span = document.createElement('span');
            col1_span.innerHTML = ns_i18n.t('not_found_alliance_name');
            col1.appendChild(col1_span);
            tr.appendChild(col1);
            dialog.cont_obj.search_list_tbody.append(tr);
        } else {
            for (let [k, d] of Object.entries(_data.list)) {
                let tr = document.createElement('tr');

                let col = document.createElement('td');
                col.innerHTML = (!d.rank || ns_util.math(d.rank).eq(0)) ? '-' : d.rank;

                let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
                button.setAttribute('id', `ns_button_alliance_join_${d.alli_pk}`);
                col.append(button);

                tr.appendChild(col);

                col = document.createElement('td');
                col.innerHTML = d.title;
                tr.appendChild(col);

                col = document.createElement('td');
                col.innerHTML = d.lord_name;
                tr.appendChild(col);

                col = document.createElement('td');
                col.innerHTML = d.now_member_count + '/' + d.max_member_count;
                tr.appendChild(col);

                col = document.createElement('td');
                col.innerHTML = ns_util.numberFormat(d.power);
                tr.appendChild(col);

                col = document.createElement('td');
                col.innerHTML = ns_util.numberFormat(d.attack_point);
                tr.appendChild(col);

                col = document.createElement('td');
                col.innerHTML = ns_util.numberFormat(d.defence_point);
                tr.appendChild(col);

                dialog.cont_obj.search_list_tbody.append(tr);

                let button_id = `alliance_join_${d.alli_pk}`;
                ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'alliance');
                ns_button.buttons[button_id].mouseUp = function ()
                {
                    let post_data = {};
                    post_data['alli_pk'] = d.alli_pk;
                    ns_xhr.post('/api/alliance/otherInfo', post_data, function(__data, __status)
                    {
                        if(! ns_xhr.returnCheck(__data)) {
                            return;
                        }
                        __data = __data['ns_xhr_return']['add_data'];
                        ns_dialog.setDataOpen('alliance_other_info', __data);
                    });
                }
                dialog.buttons.push(ns_button.buttons[button_id]);
            }
        }
    });
}

ns_dialog.dialogs.alliance.drawAllianceInfo = function()
{
    let dialog = ns_dialog.dialogs.alliance;
    let post_data = {};
    post_data['type'] = 'info';
    ns_xhr.post('/api/alliance/info', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.cont_obj.sub_tab_info_wrap.find('.info_title').text(_data.title);
        dialog.cont_obj.sub_tab_info_wrap.find('.info_lord_name').text(_data.lord_name);
        dialog.cont_obj.sub_tab_info_wrap.find('.info_rank').text(_data?.rank ?? '-');
        dialog.cont_obj.sub_tab_info_wrap.find('.info_member_count').text(_data.now_member_count);
        dialog.cont_obj.sub_tab_info_wrap.find('.info_member_max').text(_data.max_member_count);
        dialog.cont_obj.sub_tab_info_wrap.find('.info_power').text(ns_util.numberFormat(_data.power));
        dialog.cont_obj.sub_tab_info_wrap.find('.info_attack_point').text(ns_util.numberFormat(_data.attack_point));
        dialog.cont_obj.sub_tab_info_wrap.find('.info_defence_point').text(ns_util.numberFormat(_data.defence_point));

        if (! _data?.notice || _data.notice === '') {
            dialog.cont_obj.sub_tab_info_wrap.find('.info_alliance_notice').removeCss('text_align_left');
        } else {
            dialog.cont_obj.sub_tab_info_wrap.find('.info_alliance_notice').addCss('text_align_left');
        }
        if (! _data?.introduce || _data.introduce === '') {
            dialog.cont_obj.sub_tab_info_wrap.find('.info_alliance_introduce').removeCss('text_align_left');
        } else {
            dialog.cont_obj.sub_tab_info_wrap.find('.info_alliance_introduce').addCss('text_align_left');
        }

        dialog.cont_obj.sub_tab_info_wrap.find('.info_alliance_notice').html(_data.notice);
        dialog.cont_obj.sub_tab_info_wrap.find('.info_alliance_introduce').html(_data.introduce);
    });
}

ns_dialog.dialogs.alliance.drawMemberList = function()
{
    let dialog = ns_dialog.dialogs.alliance;
    let post_data = {};
    post_data['order_type'] = 'level';
    post_data['page_type'] = 'alliance_active';
    ns_xhr.post('/api/alliance/memberList', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.cont_obj.member_list_tbody.empty();
        for (let [k, d] of Object.entries(_data.list)) {
            let tr = document.createElement('tr');
            let col = document.createElement('td');
            col.innerHTML = codeset.t('ally_grade', d.level);

            let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
            button.setAttribute('id', `ns_button_alliance_lord_${d.lord_pk}`);
            col.append(button);

            tr.appendChild(col);

            col = document.createElement('td');
            col.innerHTML = d.lord_name;
            tr.appendChild(col);

            col = document.createElement('td');
            col.innerHTML = d?.rank ?? '-';
            tr.appendChild(col);

            col = document.createElement('td');
            col.innerHTML = ns_util.numberFormat(d.power);
            tr.appendChild(col);

            col = document.createElement('td');
            col.innerHTML = d.lord_level;
            tr.appendChild(col);

            col = document.createElement('td');
            col.innerHTML = d.main_posi_pk;
            tr.appendChild(col);

            dialog.cont_obj.member_list_tbody.append(tr);

            let button_id = `alliance_lord_${d.lord_pk}`;
            ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'alliance');
            ns_button.buttons[button_id].mouseUp = function ()
            {
                ns_dialog.setDataOpen('lord_info', d);
            }
            dialog.buttons.push(ns_button.buttons[button_id]);
        }
    });
};

ns_dialog.dialogs.alliance.drawWarList = function(_e)
{
    let dialog = ns_dialog.dialogs.alliance;

    ns_xhr.post('/api/alliance/warReport', {}, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.cont_obj.war_list_tbody.empty();
        for (let [k, d] of Object.entries(_data.list)) {
            let tr = document.createElement('tr');
            let col = document.createElement('td');
            col.innerHTML = codeset.t('battle_type', d.type);
            tr.appendChild(col);

            col = document.createElement('td');
            col.innerHTML = d.title;
            tr.appendChild(col);

            col = document.createElement('td');
            col.innerHTML = ns_timer.getDateTimeString(d.regist_dt, false, true, true);
            tr.appendChild(col);

            dialog.cont_obj.war_list_tbody.append(tr);
        }
    });
};

ns_dialog.dialogs.alliance.drawButtonList = function()
{
    ns_button.buttons.alliance_manage_invite.setDisable();
    ns_button.buttons.alliance_manage_join_access.setDisable();
    ns_button.buttons.alliance_manage_grade.setDisable();
    ns_button.buttons.alliance_manage_introduce.setDisable();
    ns_button.buttons.alliance_manage_diplomacy.setDisable();
    ns_button.buttons.alliance_manage_letter.setDisable();
    ns_button.buttons.alliance_manage_resignation.setDisable();
    ns_button.buttons.alliance_manage_drop_out.setDisable();
    ns_button.buttons.alliance_manage_close_down.setDisable();
    ns_button.buttons.alliance_manage_member_increase_item.setDisable();

    ns_xhr.post('/api/alliance/level', {}, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];
        if (!_data || ns_util.math(_data).lt(1)) {
            return;
        }

        if (ns_util.math(_data).lt(5)) {
            ns_button.buttons.alliance_manage_invite.setEnable();
            ns_button.buttons.alliance_manage_join_access.setEnable();
        }

        if (ns_util.math(_data).lt(4)) {
            ns_button.buttons.alliance_manage_grade.setEnable();
        }

        if (ns_util.math(_data).eq(1)) {
            ns_button.buttons.alliance_manage_introduce.setEnable();
            ns_button.buttons.alliance_manage_close_down.setEnable();
            ns_button.buttons.alliance_manage_member_increase_item.setEnable();
        }

        if (ns_util.math(_data).lt(3)) {
            ns_button.buttons.alliance_manage_diplomacy.setEnable()
            ns_button.buttons.alliance_manage_letter.setEnable();
        }

        if (ns_util.math(_data).gt(1) && ns_util.math(_data).lt(5)) {
            ns_button.buttons.alliance_manage_resignation.setEnable();
        }

        if (ns_util.math(_data).eq(5)) {
            ns_button.buttons.alliance_manage_drop_out.setEnable();
        }
    });
};

ns_dialog.dialogs.alliance.useAllianceItem = function(_item_pk)
{
    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_alliance_oath_confirm'), // 동맹의 서약 아이템 1개를 사용하여<br />최대 동맹 회원을 10명 늘리수 있습니다.<br />(최대 100명까지 가능)<br /><br />동맹의 서약을 하시겠습니까?
        okFunc: () =>
        {
            let post_data = {};
            post_data['action'] = 'use_item';
            post_data['item_pk'] = _item_pk;
            ns_xhr.post('/api/item/use', post_data, function(_data, _status)
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];

                ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_oath_result', [_data.member_count])); // 동맹의 서약을 통해 최대 동맹수가<br />증가하였습니다.<br /><br />현재 최대 가능 동맹수 :
            }, { useProgress: true });
        }
    });
};

ns_dialog.dialogs.alliance.unread_alliance_cnt = function()
{

};

ns_dialog.dialogs.alliance.updateAlliance = function(__data)
{
    // 보고 있다면 닫아주기
    let update_notice = false;
    if (ns_dialog.dialogs.alliance.visible) {
        ns_dialog.close('alliance');
        update_notice = true;
    }
    ns_xhr.post('/api/alliance/updateAlliance', {}, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        if (_data?.alli_pk && _data.alli_pk !== 0) {
        } else {
            ns_cs.d.ally = {};
        }
        if (ns_dialog.dialogs.chat.visible) {
            ns_dialog.dialogs.chat.redraw = true;
            ns_dialog.dialogs.chat.draw();
        }
        if (update_notice) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_information_refresh')); // 군주님의 동맹 정보가 갱신되었습니다.
        }
    }, { useProgress: true });
}

/* ************************************************** */
ns_button.buttons.alliance_close = new nsButtonSet('alliance_close', 'button_back', 'alliance', { base_class: ns_button.buttons.common_close });
ns_button.buttons.alliance_sub_close = new nsButtonSet('alliance_sub_close', 'button_full', 'alliance', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.alliance_close_all = new nsButtonSet('alliance_close_all', 'button_close_all', 'alliance', { base_class: ns_button.buttons.common_close_all });
// ns_button.buttons.game_help_Alliance = new nsButtonSet('game_help_Alliance', 'button_dlg_help', 'alliance', {base_class:ns_button.buttons.buil_help});

ns_button.buttons.alliance_tab_make = new nsButtonSet('alliance_tab_make', 'button_tab', 'alliance', {toggle_group:'alliance_tab'});
ns_button.buttons.alliance_tab_make.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.alliance;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    ns_dialog.dialogs.alliance.drawContents();
};
ns_button.buttons.alliance_tab_join = new nsButtonSet('alliance_tab_join', 'button_tab', 'alliance', { toggle_group:'alliance_tab', base_class:ns_button.buttons.alliance_tab_make });
ns_button.buttons.alliance_tab_info = new nsButtonSet('alliance_tab_info', 'button_tab', 'alliance', { toggle_group:'alliance_tab', base_class:ns_button.buttons.alliance_tab_make });
ns_button.buttons.alliance_tab_manage = new nsButtonSet('alliance_tab_manage', 'button_tab', 'alliance', { toggle_group:'alliance_tab', base_class:ns_button.buttons.alliance_tab_make });

ns_button.buttons.alliance_sub_tab_info = new nsButtonSet('alliance_sub_tab_info', 'button_tab_sub', 'alliance', {toggle_group:'alliance_sub_tab'});
ns_button.buttons.alliance_sub_tab_info.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.alliance;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    ns_dialog.dialogs.alliance.drawContents();
};
ns_button.buttons.alliance_sub_tab_member_list = new nsButtonSet('alliance_sub_tab_member_list', 'button_tab_sub', 'alliance', {toggle_group:'alliance_sub_tab', base_class:ns_button.buttons.alliance_sub_tab_info});
ns_button.buttons.alliance_sub_tab_war_list = new nsButtonSet('alliance_sub_tab_war_list', 'button_tab_sub', 'alliance', {toggle_group:'alliance_sub_tab', base_class:ns_button.buttons.alliance_sub_tab_info});



ns_button.buttons.alliance_search = new nsButtonSet('alliance_search', 'button_default', 'alliance');
ns_button.buttons.alliance_search.mouseUp = function (_e)
{
    let dialog = ns_dialog.dialogs.alliance;
    dialog.drawAllianceList();
};

ns_button.buttons.alliance_make = new nsButtonSet('alliance_make', 'button_default', 'alliance');
ns_button.buttons.alliance_make.mouseUp = function (_e)
{
    let dialog = ns_dialog.dialogs.alliance;
    let alliance_title = dialog.cont_obj.alliance_title_input.value();

    if (alliance_title.replace(/\s/gi, '').length < 1) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_plz_input_alliance_name')); // 동맹명을 입력해 주십시오.
        return false;
    }

    let post_data = {};
    post_data['in_cast_pk'] = ns_cs.getCastlePk('I', '200800'); // 대사관
    post_data['title'] = alliance_title;
    ns_xhr.post('/api/alliance/make', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.draw();
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_create_result', [_data])); // [******] 동맹이 창설 되었습니다.
    }, { useProgress: true });
}

ns_button.buttons.alliance_manage_invite = new nsButtonSet('alliance_manage_invite', 'button_default', 'alliance');
ns_button.buttons.alliance_manage_invite.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('alliance_invite');
};

ns_button.buttons.alliance_manage_join_access = new nsButtonSet('alliance_manage_join_access', 'button_default', 'alliance');
ns_button.buttons.alliance_manage_join_access.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('alliance_join_access');
};

ns_button.buttons.alliance_manage_grade = new nsButtonSet('alliance_manage_grade', 'button_default', 'alliance');
ns_button.buttons.alliance_manage_grade.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('alliance_manage_grade');
};

ns_button.buttons.alliance_manage_introduce = new nsButtonSet('alliance_manage_introduce', 'button_default', 'alliance');
ns_button.buttons.alliance_manage_introduce.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('alliance_introduce_change');
};

ns_button.buttons.alliance_manage_diplomacy = new nsButtonSet('alliance_manage_diplomacy', 'button_default', 'alliance');
ns_button.buttons.alliance_manage_diplomacy.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('alliance_diplomacy');
};

ns_button.buttons.alliance_manage_letter = new nsButtonSet('alliance_manage_letter', 'button_default', 'alliance');
ns_button.buttons.alliance_manage_letter.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('alliance_letter');
};

ns_button.buttons.alliance_manage_resignation = new nsButtonSet('alliance_manage_resignation', 'button_default', 'alliance');
ns_button.buttons.alliance_manage_resignation.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.alliance;
    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_alliance_resign_confirm'), // 현재 직책을 버리고 동맹원이 되시겠습니까?
        okFunc: () => {
            ns_xhr.post('/api/alliance/resignation', { action:'resignation' }, function(_data, _status)
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];

                ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_resign_result', [codeset.t('ally_grade', _data)])); // 직책을 사직 하였습니다.<br />동맹원으로 활동합니다.

                ns_world.lud_max = 0;
                dialog.drawButtonList();
            });
        }
    }, { useProgress: true });
};

ns_button.buttons.alliance_manage_drop_out= new nsButtonSet('alliance_manage_drop_out', 'button_default', 'alliance');
ns_button.buttons.alliance_manage_drop_out.mouseUp = function(_e)
{
    let prev_alli_pk = ns_cs.d.lord.alli_pk.v;
    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_alliance_dropout_confirm'), // 동맹을 탈퇴하시겠습니까?
        okFunc: () =>
        {
            ns_xhr.post('/api/alliance/dropOut', {}, function(_data, _status)
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];

                ns_dialog.closeAll();

                ns_chat.allianceNotice('alliance_dropout', {username: _data.lord_name});
                ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_dropout_result')); // 동맹에서 탈퇴 하셨습니다.

                ns_chat.receiveMessage()

                ns_world.lud_max = 0;
            }, { useProgress: true });
        }
    });
};
ns_button.buttons.alliance_manage_close_down = new nsButtonSet('alliance_manage_close_down', 'button_default', 'alliance');
ns_button.buttons.alliance_manage_close_down.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_alliance_close_confirm'), // 동맹을 폐쇄 하시겠습니까?
        okFunc: () => {
            ns_xhr.post('/api/alliance/closeDown', {}, function(_data, _status)
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];

                ns_dialog.close('alliance');
                ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_close_result')); // 동맹이 폐쇄 되었습니다.

                ns_world.lud_max = 0;
            }, { useProgress: true });
        }
    });

};
ns_button.buttons.alliance_manage_member_increase_item = new nsButtonSet('alliance_manage_member_increase_item', 'button_default', 'alliance');
ns_button.buttons.alliance_manage_member_increase_item.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.alliance;
    let item_pk = 500018; // 동맹서약 아이템의 pk 번호.
    let item_cnt = (! ns_cs.d.item[item_pk]) ? 0 : ns_cs.d.item[item_pk].item_cnt;
    if (item_cnt > 0) {
        dialog.useAllianceItem(item_pk);
    } else {
        let _confirm_text = ns_i18n.t('msg_need_buy_item', [ ns_i18n.t(`item_title_${item_pk}`), `<span class="content_item_qbig_amount">${ns_util.numberFormat(ns_cs.m.item[item_pk].price)}</span>`, `<span class="content_item_qbig_amount">${ns_util.numberFormat(ns_cs.d.cash.qbig.v)}</span>`]); // [$1] 아이템이 부족합니다.<br /></br />부족한 아이템을 구매 하시겠습니까?<br /></br />$1 $2<br />보유: $3
        ns_dialog.setDataOpen('confirm', { text: _confirm_text,
            okFunc: () => {
                let post_data = {};
                post_data['item_pk'] = item_pk;
                post_data['count'] = 1;
                ns_xhr.post('/api/item/buy', post_data, function(_data, _status)
                {
                    if(! ns_xhr.returnCheck(_data)) {
                        return;
                    }
                    _data = _data['ns_xhr_return']['add_data'];

                    dialog.useAllianceItem(item_pk);
                }, { useProgress: true });
            }
        });
    }
};

// 타동맹 정보
ns_dialog.dialogs.alliance_other_info = new nsDialogSet('alliance_other_info', 'dialog_full', 'size-full', { do_close_all: false });

ns_dialog.dialogs.alliance_other_info.cacheContents = function()
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);
    this.cont_obj.info_table = new nsObject('table', this.obj);
    this.cont_obj.alliance_diplomacy_wrap = new nsObject('.alliance_diplomacy_wrap', this.obj);
};

ns_dialog.dialogs.alliance_other_info.draw = function()
{
    this.cont_obj.content_title.text(this.data.title);
    this.cont_obj.info_table.find('.info_lord_name').text(this.data.lord_name);
    this.cont_obj.info_table.find('.info_rank').text(this.data.rank ?? '-');
    this.cont_obj.info_table.find('.info_now_member_count').text(ns_util.numberFormat(this.data.now_member_count));
    this.cont_obj.info_table.find('.info_max_member_count').text(ns_util.numberFormat(this.data.max_member_count));
    this.cont_obj.info_table.find('.info_power').text(ns_util.numberFormat(this.data.power));
    this.cont_obj.info_table.find('.info_attack_point').text(ns_util.numberFormat(this.data.attack_point));
    this.cont_obj.info_table.find('.info_defence_point').text(ns_util.numberFormat(this.data.defence_point));
    this.cont_obj.info_table.find('.info_alliance_introduce').html(ns_util.forbiddenWordCheck(this.data.introduce));

    // 버튼 설정
    this.cont_obj.alliance_diplomacy_wrap.hide();

    ns_button.buttons.alliance_join_request.setEnable();
    if (ns_cs.d.lord.alli_pk.v) {
        ns_button.buttons.alliance_join_request.setDisable();

        // 버튼 노출 조건 1. 동맹 가입 중, 2. 다른 동맹일 경우, 3. 외교 가능한 동맹 등급
        this.drawButtonList(this.data.diplomacy);
        if (! ns_util.math(ns_cs.d.lord.alli_pk.v).eq(this.data.alli_pk)) {
            ns_xhr.post('/api/alliance/level', {}, (_data, _status) =>
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];
                if (ns_util.math(_data).lt(3)) {
                    this.cont_obj.alliance_diplomacy_wrap.show();
                }
            });
        }
    }
}

ns_dialog.dialogs.alliance_other_info.drawButtonList = function(_type)
{
    switch(_type) {
        case 'F':
            ns_button.buttons.alliance_diplomacy_friendship.obj.hide();
            ns_button.buttons.alliance_diplomacy_neutrality.obj.show();
            ns_button.buttons.alliance_diplomacy_hostile.obj.show();
            ns_button.buttons.alliance_diplomacy_delete.obj.show();
            break;
        case 'H':
            ns_button.buttons.alliance_diplomacy_friendship.obj.show();
            ns_button.buttons.alliance_diplomacy_neutrality.obj.show();
            ns_button.buttons.alliance_diplomacy_hostile.obj.hide();
            ns_button.buttons.alliance_diplomacy_delete.obj.show();
            break;
        case 'N':
            ns_button.buttons.alliance_diplomacy_friendship.obj.show();
            ns_button.buttons.alliance_diplomacy_neutrality.obj.hide();
            ns_button.buttons.alliance_diplomacy_hostile.obj.show();
            ns_button.buttons.alliance_diplomacy_delete.obj.show();
            break;
        default:
            ns_button.buttons.alliance_diplomacy_friendship.obj.show();
            ns_button.buttons.alliance_diplomacy_neutrality.obj.show();
            ns_button.buttons.alliance_diplomacy_hostile.obj.show();
            ns_button.buttons.alliance_diplomacy_delete.obj.hide();
            break;
    }
};

ns_dialog.dialogs.alliance_other_info.relationChange = function(_type)
{
    let dialog = ns_dialog.dialogs.alliance_other_info;
    let post_data = {};
    post_data['alli_pk'] = dialog.data.alli_pk;
    post_data['relation_type'] = _type;
    ns_xhr.post('/api/alliance/allianceRelation', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        if (_data === 'D') {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_diplomacy_delete')); // 해당 동맹과의 관계를 삭제 하였습니다.
        } else {

            ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_diplomacy_result', [codeset.t('relation', _data)])); // '해당 동맹과 {{1}}관계로 설정 되었습니다.'
        }

        // TODO 채팅 업데이트 후 작업 필요.
        // qbw_chat.alliance_update_request();
        // qbw_chat.alliance_update_request(dlg.data.alli_pk);

        dialog.drawButtonList(_data);
        if (ns_dialog.dialogs.alliance_diplomacy.visible) {
            ns_dialog.dialogs.alliance_diplomacy.draw();
        }
    }, { useProgress: true });
};

/* ************************************************** */

ns_button.buttons.alliance_other_info_close = new nsButtonSet('alliance_other_info_close', 'button_back', 'alliance_other_info', {base_class:ns_button.buttons.common_close});
ns_button.buttons.alliance_other_info_sub_close = new nsButtonSet('alliance_other_info_sub_close', 'button_full', 'alliance_other_info', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.alliance_other_info_close_all = new nsButtonSet('alliance_other_info_close_all', 'button_close_all', 'alliance_other_info', {base_class:ns_button.buttons.common_close_all});

/* ************************* */

ns_button.buttons.alliance_join_request = new nsButtonSet('alliance_join_request', 'button_default', 'alliance_other_info');
ns_button.buttons.alliance_join_request.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.alliance_other_info;
    let post_data = {};
    post_data['alli_pk'] = dialog.data.alli_pk;
    ns_xhr.post('/api/alliance/joinRequest', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_button.buttons.alliance_other_info_close.mouseUp();

        if (_data?.alliance_name) {
            ns_chat.allianceNotice('alliance_join', {username: ns_cs.d.lord.lord_name.v});
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_join_result', [_data.alliance_name])); // [******] 동맹에 가입되었습니다.
        } else {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_join_wait')); // 동맹의 승인을 기다립니다. 승인시 보고서로 알려드립니다.
        }

    }, { useProgress: true });
};

ns_button.buttons.alliance_diplomacy_friendship = new nsButtonSet('alliance_diplomacy_friendship', 'button_default', 'alliance_other_info');
ns_button.buttons.alliance_diplomacy_friendship.mouseUp = function (_e)
{
    let type = this.tag_id.split('_').pop().charAt(0).toUpperCase();
    ns_dialog.dialogs.alliance_other_info.relationChange(type);
};
ns_button.buttons.alliance_diplomacy_neutrality = new nsButtonSet('alliance_diplomacy_neutrality', 'button_default', 'alliance_other_info', { base_class: ns_button.buttons.alliance_diplomacy_friendship });
ns_button.buttons.alliance_diplomacy_hostile = new nsButtonSet('alliance_diplomacy_hostile', 'button_default', 'alliance_other_info', { base_class: ns_button.buttons.alliance_diplomacy_friendship });
ns_button.buttons.alliance_diplomacy_delete = new nsButtonSet('alliance_diplomacy_delete', 'button_default', 'alliance_other_info', { base_class: ns_button.buttons.alliance_diplomacy_friendship });

// 동맹 가입 승인
ns_dialog.dialogs.alliance_join_access = new nsDialogSet('alliance_join_access', 'dialog_full', 'size-full', {do_close_all:false});
ns_dialog.dialogs.alliance_join_access.cacheContents = function()
{
    this.cont_obj.join_access_list_tbody = new nsObject('.join_access_list_tbody', this.obj);
};

ns_dialog.dialogs.alliance_join_access.draw = function()
{
    this.cont_obj.join_access_list_tbody.empty();
    ns_xhr.post('/api/alliance/joinList', { page_type:'alliance_active' }, (_data, _status) =>
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        let tbody = document.createElement('tbody');

        if (_data.list.length < 1) { // 리스트가 없을 때
            let tr = document.createElement('tr');
            let col1 = document.createElement('td');
            col1.colSpan = 6;
            let col1_span = document.createElement('span');
            col1_span.innerHTML = ns_i18n.t('msg_alliance_not_exist_request_lord');
            col1.appendChild(col1_span);
            tr.appendChild(col1);
            tbody.appendChild(tr);
        } else {
            for (let [k, d] of Object.entries(_data.list)){
                let tr = document.createElement('tr');
                let col1 = document.createElement('td');
                col1.innerHTML = d.rank ? d.rank : '-';
                tr.appendChild(col1);
                let col2 = document.createElement('td');
                col2.innerHTML = d.lord_name;
                tr.appendChild(col2);
                let col3 = document.createElement('td');
                col3.innerHTML = d.power;
                tr.appendChild(col3);
                let col4 = document.createElement('td');
                col4.innerHTML = d.level;
                tr.appendChild(col4);
                let col5 = document.createElement('td');
                col5.innerHTML = ns_timer.getDateTimeString(d.join_dt, false, true, true);
                tr.appendChild(col5);
                let col6 = document.createElement('td');

                let ok_button = document.createElement('span');
                ok_button.setAttribute('id', `ns_button_alliance_join_access_ok_${d.lord_pk}`);
                ok_button.innerHTML = ns_i18n.t('approval'); // 승인
                let no_button = document.createElement('span');
                no_button.setAttribute('id', `ns_button_alliance_join_access_no_${d.lord_pk}`);
                no_button.innerHTML = ns_i18n.t('refuse'); // 거절

                col6.appendChild(ok_button);
                col6.appendChild(no_button);
                tr.appendChild(col6);

                this.cont_obj.join_access_list_tbody.append(tr);

                let ok_button_id = `alliance_join_access_ok_${d.lord_pk}`;
                ns_button.buttons[ok_button_id] = new nsButtonSet(ok_button_id, 'button_small_1', 'alliance_join_access');
                ns_button.buttons[ok_button_id].mouseUp = () =>
                {
                    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_alliance_join_confirm_yes', [d.lord_name]),
                        okFunc: () =>
                        {
                            let post_data = {};
                            post_data['lord_pk'] = d.lord_pk;
                            ns_xhr.post('/api/alliance/joinAccess', post_data, (_data, _status) =>
                            {
                                if(! ns_xhr.returnCheck(_data)) {
                                    return;
                                }
                                _data = _data['ns_xhr_return']['add_data'];
                                ns_chat.allianceNotice('alliance_join', {username: d.lord_name});
                                ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_join_request_ok', [d.lord_name])); // 님의 가입 신청이 승인되었습니다.
                                this.draw();
                            }, { useProgress: true });
                        }
                    });
                }

                let no_button_id = `alliance_join_access_no_${d.lord_pk}`;
                ns_button.buttons[no_button_id] = new nsButtonSet(no_button_id, 'button_small_2', 'alliance_join_access');
                ns_button.buttons[no_button_id].mouseUp = () =>
                {
                    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_alliance_join_confirm_no', [d.lord_name]),
                        okFunc: () =>
                        {
                            let post_data = {};
                            post_data['lord_pk'] = d.lord_pk;
                            ns_xhr.post('/api/alliance/joinRefuse', post_data, (_data, _status) =>
                            {
                                if(! ns_xhr.returnCheck(_data)) {
                                    return;
                                }
                                _data = _data['ns_xhr_return']['add_data'];
                                // qbw_chat.alliance_update_request();
                                this.draw();
                            }, { useProgress: true })
                        }
                    });
                }
                this.buttons.push(ns_button.buttons[ok_button_id]);
                this.buttons.push(ns_button.buttons[no_button_id]);
            }
        }
    });
};

/* ************************************************** */

ns_button.buttons.alliance_join_access_close = new nsButtonSet('alliance_join_access_close', 'button_back', 'alliance_join_access', {base_class:ns_button.buttons.common_close});
ns_button.buttons.alliance_join_access_sub_close = new nsButtonSet('alliance_join_access_sub_close', 'button_full', 'alliance_join_access', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.alliance_join_access_close_all = new nsButtonSet('alliance_join_access_close_all', 'button_close_all', 'alliance_join_access', {base_class:ns_button.buttons.common_close_all});


// 동맹 초대
ns_dialog.dialogs.alliance_invite = new nsDialogSet('alliance_invite', 'dialog_full', 'size-full', { do_close_all: false });
ns_dialog.dialogs.alliance_invite.cacheContents = function()
{
    this.cont_obj.lord_name_search = new nsObject('.lord_name_search', this.obj);
    this.cont_obj.search_lord_list_tbody = new nsObject('.search_lord_list_tbody', this.obj);
};

ns_dialog.dialogs.alliance_invite.draw = function()
{
    if (!this.visible) {
        this.cont_obj.lord_name_search.value('');
        this.cont_obj.search_lord_list_tbody.empty();
    }
};

ns_dialog.dialogs.alliance_invite.drawSearchList = function(_e)
{
    let dialog = ns_dialog.dialogs.alliance_invite;
    let lord_name_search = dialog.cont_obj.lord_name_search.value();

    dialog.cont_obj.search_lord_list_tbody.empty();
    ns_xhr.post('/api/alliance/searchLord', { lord_name: lord_name_search }, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];
        for (let [k, d] of Object.entries(_data.list)) {
            let tr = document.createElement('tr');
            let col1 = document.createElement('td');
            col1.innerHTML = d.rank ? d.rank : '-';

            let button = document.createElement('span');
            button.setAttribute('id', `ns_button_alliance_invite_${d.lord_pk}`);
            col1.appendChild(button);

            tr.appendChild(col1);
            let col2 = document.createElement('td');
            col2.innerHTML = d.lord_name;
            tr.appendChild(col2);
            let col3 = document.createElement('td');
            col3.innerHTML = d.power;
            tr.appendChild(col3);
            let col4 = document.createElement('td');
            col4.innerHTML = d.level;
            tr.appendChild(col4);

            dialog.cont_obj.search_lord_list_tbody.append(tr);

            let button_id = `alliance_invite_${d.lord_pk}`;
            ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'alliance_invite');
            ns_button.buttons[button_id].mouseUp = function ()
            {
                ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_alliance_invite_confirm', [d.lord_name]), // '님을 동맹에 초대합니다.',
                    okFunc: function()
                    {
                        ns_xhr.post('/api/alliance/invite', { lord_pk: d.lord_pk }, function(_data, _status)
                        {
                            if(! ns_xhr.returnCheck(_data)) {
                                return;
                            }
                            _data = _data['ns_xhr_return']['add_data'];

                            // qbw_chat.alliance_update_request();
                            ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_invite_result', [d.lord_name])); // 님을 동맹에 초대 하였습니다.
                        }, { useProgress: true });
                    }
                });
            }
            dialog.buttons.push(ns_button.buttons[button_id]);
        }
    });
};

/* ************************************************** */

ns_button.buttons.alliance_invite_close = new nsButtonSet('alliance_invite_close', 'button_back', 'alliance_invite', {base_class:ns_button.buttons.common_close});
ns_button.buttons.alliance_invite_sub_close = new nsButtonSet('alliance_invite_sub_close', 'button_full', 'alliance_invite', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.alliance_invite_close_all = new nsButtonSet('alliance_invite_close_all', 'button_close_all', 'alliance_invite', {base_class:ns_button.buttons.common_close_all});

ns_button.buttons.alliance_search_lord_name = new nsButtonSet('alliance_search_lord_name', 'button_default', 'alliance_invite');
ns_button.buttons.alliance_search_lord_name.mouseUp = function (_e)
{
    let dialog = ns_dialog.dialogs.alliance_invite;
    let lord_name_search = dialog.cont_obj.lord_name_search.value();
    if (lord_name_search.replace(/\s/gi, '').length < 1) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_plz_input_lord_name')); // 군주명을 입력해 주세요.
        return;
    }
    dialog.drawSearchList();
};

// 직책 관리
ns_dialog.dialogs.alliance_manage_grade = new nsDialogSet('alliance_manage_grade', 'dialog_full', 'size-full', {do_close_all:false});
ns_dialog.dialogs.alliance_manage_grade.select_lord = null;

ns_dialog.dialogs.alliance_manage_grade.cacheContents = function()
{
    this.cont_obj.grade_manage_list_tbody = new nsObject('.grade_manage_list_tbody', this.obj);
};

ns_dialog.dialogs.alliance_manage_grade.draw = function()
{
    let post_data = {};
    post_data['order_type'] = 'level';
    post_data['page_type'] = 'alliance_active';
    this.cont_obj.grade_manage_list_tbody.empty();
    ns_xhr.post('/api/alliance/memberList', post_data, (_data, _status) =>
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        for (let [k, d] of Object.entries(_data.list)) {
            let tr = document.createElement('tr');
            let col1 = document.createElement('td');
            let button = document.createElement('span');
            button.setAttribute('id', `ns_button_alliance_manage_grade_${d.lord_pk}`);
            button.innerHTML = codeset.t('ally_grade', d.level);
            col1.appendChild(button);
            tr.appendChild(col1);

            let col2 = document.createElement('td');
            col2.innerHTML = d.lord_name;
            tr.appendChild(col2);
            let col3 = document.createElement('td');
            col3.innerHTML = d.rank ? d.rank : '-';
            tr.appendChild(col3);
            let col4 = document.createElement('td');
            col4.innerHTML = d.power;
            tr.appendChild(col4);
            let col5 = document.createElement('td');
            col5.innerHTML = d.lord_level;
            tr.appendChild(col5);

            this.cont_obj.grade_manage_list_tbody.append(tr);

            let button_id = `alliance_manage_grade_${d.lord_pk}`;
            ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_inner_column', 'alliance_manage_grade');
            ns_button.buttons[button_id].mouseUp = function ()
            {
                ns_select_box.set('alliance_change_level', d.level);
                ns_dialog.setDataOpen('select_box', {select_box_id: 'alliance_change_level', button_id: `alliance_manage_grade_${d.lord_pk}`});

                ns_dialog.dialogs.alliance_manage_grade.select_lord = d;
            }
            this.buttons.push(ns_button.buttons[button_id]);
        }
    });
}

ns_dialog.dialogs.alliance_manage_grade.changeGrade = function(_level)
{
    let dialog = ns_dialog.dialogs.alliance_manage_grade;
    let lord_info = dialog.select_lord;
    if (! lord_info) {
        return;
    }
    if (ns_util.math(_level).eq(lord_info.level)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_grade_change_fail_same')); // 동일한 등급으로는 변경 할 수 없습니다.
        return ;
    }

    ns_xhr.post('/api/alliance/levelChange', { lord_pk: lord_info.lord_pk, level: _level }, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_chat.allianceNotice('alliance_level_change', {username: _data.lord_name, level: _data.level});
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_grade_change_result', [codeset.t('ally_grade', _data.level)])); // '직책이 {{1}}(으)로 변경되었습니다.'
        // ns_chat.allianceEvent('join', ns_cs.d.lord.alli_pk.v, d.lord_pk);
        dialog.draw();
    }, { useProgress: true });
};

/* ************************************************** */

ns_button.buttons.alliance_manage_grade_close = new nsButtonSet('alliance_manage_grade_close', 'button_back', 'alliance_manage_grade', {base_class:ns_button.buttons.common_close});
ns_button.buttons.alliance_manage_grade_sub_close = new nsButtonSet('alliance_manage_grade_sub_close', 'button_full', 'alliance_manage_grade', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.alliance_manage_grade_close_all = new nsButtonSet('alliance_manage_grade_close_all', 'button_close_all', 'alliance_manage_grade', {base_class:ns_button.buttons.common_close_all});

// 동맹 외교
ns_dialog.dialogs.alliance_diplomacy = new nsDialogSet('alliance_diplomacy', 'dialog_full', 'size-full', { do_close_all: false });

ns_dialog.dialogs.alliance_diplomacy.cacheContents = function()
{
    this.cont_obj.diplomacy_list_tbody = new nsObject('.diplomacy_list_tbody', this.obj);
};

ns_dialog.dialogs.alliance_diplomacy.draw = function()
{
    this.cont_obj.diplomacy_list_tbody.empty();
    ns_xhr.post('/api/alliance/relationList', {}, (_data, _status) =>
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        for (let [k, d] of Object.entries(_data.list)) {
            let tr = document.createElement('tr');
            let col1 = document.createElement('td');
            col1.innerHTML = d.rank ? d.rank : '-';
            let button = document.createElement('span');
            button.setAttribute('id', `ns_button_alliance_diplomacy_${d.alli_pk}`);
            col1.appendChild(button);
            tr.appendChild(col1);
            let col2 = document.createElement('td');
            col2.innerHTML = d.title;
            tr.appendChild(col2);
            let col3 = document.createElement('td');
            col3.innerHTML = d.lord_name;
            tr.appendChild(col3);
            let col4 = document.createElement('td');
            col4.innerHTML = ns_util.numberFormat(d.power);
            tr.appendChild(col4);
            let col5 = document.createElement('td');
            col5.innerHTML = d.now_member_count + '/' + d.max_member_count;
            tr.appendChild(col5);

            this.cont_obj.diplomacy_list_tbody.append(tr);

            let button_id = `alliance_diplomacy_${d.alli_pk}`;
            ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'alliance_diplomacy');
            ns_button.buttons[button_id].mouseUp = function ()
            {
                ns_dialog.setDataOpen('alliance_other_info', d);
            }
        }
    });
}

/* ************************************************** */

ns_button.buttons.alliance_diplomacy_close = new nsButtonSet('alliance_diplomacy_close', 'button_back', 'alliance_diplomacy', {base_class:ns_button.buttons.common_close});
ns_button.buttons.alliance_diplomacy_sub_close = new nsButtonSet('alliance_diplomacy_sub_close', 'button_full', 'alliance_diplomacy', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.alliance_diplomacy_close_all = new nsButtonSet('alliance_diplomacy_close_all', 'button_close_all', 'alliance_diplomacy', {base_class:ns_button.buttons.common_close_all});

// 동맹 서신
ns_dialog.dialogs.alliance_letter = new nsDialogSet('alliance_letter', 'dialog_full', 'size-full', { do_close_all: false });

ns_dialog.dialogs.alliance_letter.cacheContents = function()
{
    this.cont_obj.alliance_letter_title = new nsObject('.alliance_letter_title', this.obj);
    this.cont_obj.alliance_letter_content = new nsObject('.alliance_letter_content', this.obj);

};

ns_dialog.dialogs.alliance_letter.draw = function()
{
    this.cont_obj.alliance_letter_title.value('');
    this.cont_obj.alliance_letter_content.value('');
};

/* ************************************************** */

ns_button.buttons.alliance_letter_close = new nsButtonSet('alliance_letter_close', 'button_back', 'alliance_letter', {base_class:ns_button.buttons.common_close});
ns_button.buttons.alliance_letter_sub_close = new nsButtonSet('alliance_letter_sub_close', 'button_full', 'alliance_letter', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.alliance_letter_close_all = new nsButtonSet('alliance_letter_close_all', 'button_close_all', 'alliance_letter', {base_class:ns_button.buttons.common_close_all});

ns_button.buttons.alliance_letter_send = new nsButtonSet('alliance_letter_send', 'button_default', 'alliance_letter');
ns_button.buttons.alliance_letter_send.mouseUp = function (_e)
{
    let dialog = ns_dialog.dialogs.alliance_letter;

    let _alli_letter_title = dialog.cont_obj.alliance_letter_title.value();
    let _alli_letter_content = dialog.cont_obj.alliance_letter_content.value();

    if (_alli_letter_title.length > 25) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_max_count_alliance_letter_subject', [_alli_letter_title.length])); // 제목은 한글 기준 25자를 초과할 수 없습니다.<br/>사용한 글자수 :
        return;
    } else if (_alli_letter_title.length < 1) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_empty_title_error')); // 제목을 입력해주십시오.
        return;
    }

    if (_alli_letter_content.length > 500) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_max_count_alliance_letter_content', [_alli_letter_content.length])); // 내용은 한글 기준 500자를 초과할 수 없습니다.<br/>사용한 글자수 :
        return;
    } else if (_alli_letter_content.length < 1) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_empty_content_error')); // 내용을 입력해주십시오.
        return;
    }

    ns_xhr.post('/api/alliance/sendAllianceLetter', { title: _alli_letter_title, content: _alli_letter_content }, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_dialog.close('alliance_letter');
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_letter_send_ok')); // 동맹 서신이 발송되었습니다.
    }, { useProgress: true });
}

// 동맹 정보 수정
ns_dialog.dialogs.alliance_introduce_change = new nsDialogSet('alliance_introduce_change', 'dialog_full', 'size-full', { do_close_all: false });

ns_dialog.dialogs.alliance_introduce_change.cacheContents = function()
{
    this.cont_obj.alliance_title = new nsObject('.alliance_title', this.obj);
    this.cont_obj.alliance_introduce = new nsObject('.alliance_introduce', this.obj);
    this.cont_obj.alliance_notice = new nsObject('.alliance_notice', this.obj);
};

ns_dialog.dialogs.alliance_introduce_change.draw = function()
{
    this.cont_obj.alliance_title.value('');
    this.cont_obj.alliance_introduce.value('');
    this.cont_obj.alliance_notice.value('');
    this.drawAllianceInfo();
};

ns_dialog.dialogs.alliance_introduce_change.drawAllianceInfo = function()
{
    let dialog = ns_dialog.dialogs.alliance_introduce_change;
    ns_xhr.post('/api/alliance/info', { type:'change' }, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.cont_obj.alliance_title.value(_data.title);
        dialog.cont_obj.alliance_introduce.value(ns_util.forbiddenWordCheck(_data.introduce));
        dialog.cont_obj.alliance_notice.value(ns_util.forbiddenWordCheck(_data.notice));
    });
};

/* ************************************************** */

ns_button.buttons.alliance_introduce_change_close = new nsButtonSet('alliance_introduce_change_close', 'button_back', 'alliance_introduce_change', {base_class:ns_button.buttons.common_close});
ns_button.buttons.alliance_introduce_change_sub_close = new nsButtonSet('alliance_introduce_change_sub_close', 'button_full', 'alliance_introduce_change', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.alliance_introduce_change_close_all = new nsButtonSet('alliance_introduce_change_close_all', 'button_close_all', 'alliance_introduce_change', {base_class:ns_button.buttons.common_close_all});

ns_button.buttons.alliance_introduce_change_send = new nsButtonSet('alliance_introduce_change_send', 'button_default', 'alliance_introduce_change');
ns_button.buttons.alliance_introduce_change_send.mouseUp = function (_e)
{
    let dialog = ns_dialog.dialogs.alliance_introduce_change;

    let alli_title = dialog.cont_obj.alliance_title.value();
    let intro = dialog.cont_obj.alliance_introduce.value();
    let notice = dialog.cont_obj.alliance_notice.value();

    if (alli_title.length < 1) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_plz_input_alliance_name')); // 동맹명을 입력해 주십시오.
        return;
    } else if (alli_title.length < 2) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_min_count_alliance_name')); // 동맹명은 최소 2글자를 사용해야합니다.
        return;
    } else if (alli_title.length > 6) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_max_count_alliance_name')); // 동맹명은 최대 6글자까지 사용할 수 있습니다.
        return;
    }

    if (intro.length > 200) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_max_count_alliance_intro', [intro.length])); // 동맹 소개는 200자 이내로 작성해야 합니다.<br/>사용한 글자수 :
        return false;
    }

    if (notice.length > 500) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_max_count_alliance_notice', [notice.length])); // 동맹 공지는 500자 이내로 작성해야 합니다.<br/>사용한 글자수 :
        return false;
    }

    ns_xhr.post('/api/alliance/changeInfo', { title: alli_title, introduce: intro, notice: notice }, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_dialog.setDataOpen('message', ns_i18n.t('msg_update_finish')); // 변경이 완료되었습니다.
        ns_dialog.close('alliance_introduce_change');
    }, { useProgress: true });
};

