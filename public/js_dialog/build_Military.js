// 다이얼로그
ns_dialog.dialogs.build_Military = new nsDialogSet('build_Military', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });
ns_dialog.dialogs.build_Military.buttons = [];
ns_dialog.dialogs.build_Military.sorted = [];
ns_dialog.dialogs.build_Military.ally_camp_army_list = null;
ns_dialog.dialogs.build_Military.army_total = 0;
ns_dialog.dialogs.build_Military.army_ally_total = 0;

ns_dialog.dialogs.build_Military.cacheContents = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.cacheContents.call(this, true);
    }

    this.cont_obj.build_Military_tab_move_troops = new nsObject('.build_Military_tab_move_troops', this.obj);
    this.cont_obj.build_Military_tab_enemy_march_troops = new nsObject('.build_Military_tab_enemy_march_troops', this.obj);
    this.cont_obj.build_Military_tab_camp_army = new nsObject('.build_Military_tab_camp_army', this.obj);
    this.cont_obj.build_Military_tab_own_army = new nsObject('.build_Military_tab_own_army', this.obj);

    this.cont_obj.ns_army_total = new nsObject('.ns_army_total', this.obj);
    this.cont_obj.ns_army_my_total = new nsObject('.ns_army_my_total', this.obj);
    this.cont_obj.ns_army_ally_total = new nsObject('.ns_army_ally_total', this.obj);
}

ns_dialog.dialogs.build_Military.draw = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.draw.call(this, true);
    }

    // 탭 및 그리기
    ns_button.toggleGroupSingle(ns_button.buttons.build_Military_tab_move_troops);
    this.buttonClear();
    this.drawTab();
}

ns_dialog.dialogs.build_Military.drawTab = function(_event)
{
    let dialog = ns_dialog.dialogs.build_Military;
    let tab = ns_button.toggleGroupValue('build_Military_tab')[0];

    let api = { move_troops: 'myMoveTroops', enemy_march_troops: 'enemyMarchTroops', own_army: 'allyCampArmy', camp_army: 'myCampTroops' }
    let callback = { move_troops: dialog.drawMoveTroops, enemy_march_troops: dialog.drawEnemyMarchTroops, own_army: dialog.drawAllyCampArmy, camp_army: dialog.drawMyCampArmy }
    for (let _tab of ['move_troops', 'enemy_march_troops', 'own_army', 'camp_army']) {
        if (tab === `build_Military_tab_${_tab}`) {
            dialog.cont_obj[`build_Military_tab_${_tab}`].show();
            ns_xhr.post(`/api/military/${api[_tab]}`, {}, callback[_tab]);
        } else {
            dialog.cont_obj[`build_Military_tab_${_tab}`].hide();
        }
    }
}

ns_dialog.dialogs.build_Military.statusGate = function()
{
    let dialog = ns_dialog.dialogs.build_Military;

    ns_button.buttons.build_Military_gate.obj.text((ns_cs.d.terr.status_gate.v === 'O') ? ns_i18n.t('gate_close') : ns_i18n.t('gate_open'));
    if (dialog.cont_obj.content.find('.content_gate')?.element) {
        dialog.cont_obj.content.find('.content_gate').text((ns_cs.d.terr.status_gate.v === 'O') ? ns_i18n.t('military_gate_open_description') : ns_i18n.t('military_gate_close_description'));
    }
}

ns_dialog.dialogs.build_Military.drawMoveTroops = function (_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.build_Military;

    dialog.sorted = [];

    let tbody = dialog.cont_obj.build_Military_tab_move_troops.find('tbody');
    tbody.empty();

    if (_data.length < 1) {
        // 리스트가 없을 때
        let tr = document.createElement('tr');
        let col1 = document.createElement('td');
        col1.colSpan = 5;
        let col1_span = document.createElement('span');
        col1_span.innerHTML = codeset.t('none_counter_description', 'X');
        col1.appendChild(col1_span);

        tr.appendChild(col1);

        tbody.append(tr);
    } else {
        for (let [k, d] of Object.entries(_data)) {
            if (! ns_util.isNumeric(k)) {
                continue;
            }
            dialog.sorted.push(d);
        }

        dialog.buttonClear();
        for (let d of dialog.sorted) {
            let tr = document.createElement('tr');

            let col1 = document.createElement('td');
            let col1_span = document.createElement('span');
            col1_span.innerHTML = code_set.troop_cmd_type[d.cmd_type] + '<br />(' + code_set.troop_status[d.status] + ')';
            col1.appendChild(col1_span);

            let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
            button.setAttribute('id', `ns_button_Military_troop_view_${d.troo_pk}`);
            col1.append(button);

            let col2 = document.createElement('td');
            let col2_span = document.createElement('span');
            col2_span.innerHTML = ! d?.captain_desc ? '-' : d.captain_desc;
            col2.appendChild(col2_span);

            let col3 = document.createElement('td');
            let col3_span = document.createElement('span');
            col3_span.innerHTML = ns_text.convertPositionName(d.from_position);
            col3.appendChild(col3_span);

            let col4 = document.createElement('td');
            let col4_span = document.createElement('span');
            col4_span.innerHTML = ns_text.convertPositionName(d.to_position);
            col4.appendChild(col4_span);

            let col5 = document.createElement('td');
            let col5_span = document.createElement('span');
            col5_span.classList.add('content_remain_dt');
            col5_span.classList.add(`pk_${d.troo_pk}`);
            col5_span.innerHTML = ns_util.getCostsTime(ns_util.math(d.arrival_dt).minus(ns_timer.now()).number);
            col5.appendChild(col5_span);

            tr.appendChild(col1);
            tr.appendChild(col2);
            tr.appendChild(col3);
            tr.appendChild(col4);
            tr.appendChild(col5);

            tbody.append(tr);

            let button_id = `Military_troop_view_${d.troo_pk}`;
            ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'build_Military')
            ns_button.buttons[button_id].mouseUp = function ()
            {
                ns_dialog.setDataOpen('troop_view', { type: 'move', troo_pk: d.troo_pk });
            }
            dialog.buttons.push(ns_button.buttons[button_id]);
        }
    }
}

ns_dialog.dialogs.build_Military.drawEnemyMarchTroops = function (_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];


    let dialog = ns_dialog.dialogs.build_Military;

    dialog.sorted = [];
    // dialog.camp_army_list  = [];

    let tbody = dialog.cont_obj.build_Military_tab_enemy_march_troops.find('tbody');
    tbody.empty();

    if (_data.length < 1) {
        // 리스트가 없을 때
        let tr = document.createElement('tr');
        let col1 = document.createElement('td');
        col1.colSpan = 5;
        let col1_span = document.createElement('span');
        col1_span.innerHTML = codeset.t('none_counter_description', 'Y');
        col1.appendChild(col1_span);

        tr.appendChild(col1);

        tbody.append(tr);
    } else {
        for (let [k, d] of Object.entries(_data)) {
            if (! ns_util.isNumeric(k)) {
                continue;
            }
            dialog.sorted.push(d);
        }

        if (dialog.sorted.length > 1) {
            dialog.sorted = ns_util.arraySort(dialog.sorted, -1, 'k');
        }

        dialog.buttonClear();
        for (let [k, d] of Object.entries(dialog.sorted)) {
            // dlg.camp_army_list.push(d);

            let tr = document.createElement('tr');

            let col1 = document.createElement('td');
            let col1_span = document.createElement('span');
            col1_span.innerHTML = d.lord_name
            col1.appendChild(col1_span);

            let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
            button.setAttribute('id', `ns_button_Military_troop_view_${d.troo_pk}`);
            col1.append(button);

            let col2 = document.createElement('td');
            let col2_span = document.createElement('span');
            col2_span.innerHTML = ! d?.captain_desc ? '-' : d.captain_desc;
            col2.appendChild(col2_span);

            let col3 = document.createElement('td');
            let col3_span = document.createElement('span');
            col3_span.innerHTML = ns_text.convertPositionName(d.from_position);
            col3.appendChild(col3_span);

            let col4 = document.createElement('td');
            let col4_span = document.createElement('span');
            col4_span.innerHTML = ns_text.convertPositionName(d.to_position);
            col4.appendChild(col4_span);

            let col5 = document.createElement('td');
            let col5_span = document.createElement('span');
            col5_span.classList.add('content_remain_dt');
            col5_span.classList.add(`pk_${d.troo_pk}`);

            col5_span.innerHTML = ns_util.getCostsTime(ns_util.math(d.arrival_dt).minus(ns_timer.now()).number);
            col5.appendChild(col5_span);

            tr.appendChild(col1);
            tr.appendChild(col2);
            tr.appendChild(col3);
            tr.appendChild(col4);
            tr.appendChild(col5);

            tbody.append(tr);

            let button_id = `Military_troop_view_${d.troo_pk}`;
            ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'build_Military')
            ns_button.buttons[button_id].mouseUp = function ()
            {
                ns_dialog.setDataOpen('troop_view', { type: 'enemy', troo_pk: d.troo_pk });
            }
            dialog.buttons.push(ns_button.buttons[button_id]);
        }
    }
}

ns_dialog.dialogs.build_Military.drawAllyCampArmy = function (_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.build_Military;

    dialog.statusGate();
    dialog.ally_camp_army_list = _data;
}

ns_dialog.dialogs.build_Military.drawMyCampArmy = function (_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.build_Military;

    dialog.statusGate();

    dialog.sorted = [];
    // dialog.camp_army_list  = [];

    let tbody = dialog.cont_obj.build_Military_tab_camp_army.find('tbody');
    tbody.empty();
    if (_data.length < 1) {
        // 리스트가 없을 때
        let tr = document.createElement('tr');
        let col1 = document.createElement('td');
        col1.colSpan = 4;
        let col1_span = document.createElement('span');
        col1_span.innerHTML = ns_i18n.t('no_outside_troop_description'); // 외부에 주둔 중인 부대가 없습니다.
        col1.appendChild(col1_span);

        tr.appendChild(col1);

        tbody.append(tr);
    } else {
        for (let [k, d] of Object.entries(_data)) {
            if (! ns_util.isNumeric(k)) {
                continue;
            }
            dialog.sorted.push(d);
        }

        /*if (dialog.sorted.length > 1) {
            dialog.sorted = ns_util.arraySort(dialog.sorted, -1, 'k');
        }*/

        dialog.buttonClear();
        for (let d of dialog.sorted) {
            // 리스트에 저장
            // dlg.camp_army_list.push(d);

            let tr = document.createElement('tr');

            let col1 = document.createElement('td');
            let col1_span = document.createElement('span');
            col1_span.innerHTML = ns_text.convertPositionName(d.to_position);
            col1.appendChild(col1_span);

            let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
            button.setAttribute('id', `ns_button_Military_troop_view_${d.troo_pk}`);
            col1.append(button);

            let col2 = document.createElement('td');
            let col2_span = document.createElement('span');
            col2_span.innerHTML = d.captain_desc;
            col2.appendChild(col2_span);

            let col3 = document.createElement('td');
            let col3_span = document.createElement('span');
            col3_span.innerHTML = ns_timer.getDateTimeString(d.withdrawal_dt, true, true, true);
            col3.appendChild(col3_span);

            let col4 = document.createElement('td');
            let col4_span = document.createElement('span');
            col4_span.innerHTML = ns_i18n.t('military_distance_description', [d.distance]);
            col4.appendChild(col4_span);

            tr.appendChild(col1);
            tr.appendChild(col2);
            tr.appendChild(col3);
            tr.appendChild(col4);

            tbody.append(tr);

            let button_id = `Military_troop_view_${d.troo_pk}`;
            ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'build_Military')
            ns_button.buttons[button_id].mouseUp = function ()
            {
                ns_dialog.setDataOpen('troop_view', { type: 'view', troo_pk: d.troo_pk });
            }
            dialog.buttons.push(ns_button.buttons[button_id]);
        }
    }
}

ns_dialog.dialogs.build_Military.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_Military.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_Military.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_Military;
    let arr = dialog.tag_id.split('_');
    let alias = arr.pop();
    let m = ns_cs.m.buil[alias];
    let bd_c = m.type === 'I' ? ns_cs.d.bdic : ns_cs.d.bdoc;
    let d = bd_c[dialog.data.castle_pk];

    if (ns_util.math(d.level).lt(1)) {
        ns_button.buttons.build_Military_gate.setDisable();
        // ns_button.buttons.build_Military_troop_order.setDisable();
    } else {
        ns_button.buttons.build_Military_gate.setEnable();
        // ns_button.buttons.build_Military_troop_order.setEnable();
    }

    let tab = ns_button.toggleGroupValue('build_Military_tab')[0];

    dialog.statusGate();

    // 보유 병력 현황 탭을 보고 있을 때 갱신
    if (tab === 'build_Military_tab_move_troops') {
        for (let [k, d] of Object.entries(dialog.sorted)) {
            dialog.cont_obj.build_Military_tab_move_troops.find(`.content_remain_dt.pk_${d.troo_pk}`).text(ns_util.getCostsTime(ns_util.math(d.arrival_dt).minus(ns_timer.now()).integer));
        }
    } else if (tab === 'build_Military_tab_enemy_march_troops') {
        for (let [k, d] of Object.entries(dialog.sorted)) {
            dialog.cont_obj.build_Military_tab_enemy_march_troops.find(`.content_remain_dt.pk_${d.troo_pk}`).text(ns_util.getCostsTime(ns_util.math(d.arrival_dt).minus(ns_timer.now()).integer));
        }
    } else if (tab === 'build_Military_tab_own_army') {
        // 초기화
        dialog.army_total = 0;
        dialog.army_ally_total = 0;

        for (let [k, d] of Object.entries(ns_cs.m.army)) {
            if (! ns_util.isNumeric(k)) {
                continue;
            }
            let title = dialog.cont_obj.build_Military_tab_own_army.find(`.ns_army_title_${d.code}`);
            if (title.element) {
                title.text(ns_i18n.t(`army_title_${d.m_army_pk}`));
            }
            let army = dialog.cont_obj.build_Military_tab_own_army.find(`.ns_army_${d.code}`);
            if (army.element) {
                army.text(ns_util.numberFormat(ns_cs.d.army[d.code].v));
                dialog.army_total += ns_util.toInteger(ns_cs.d.army[d.code].v);
            }

            if (dialog.ally_camp_army_list) {
                // 동맹 병력
                if (! ns_util.isNumeric(dialog.ally_camp_army_list[d.code])) {
                    dialog.ally_camp_army_list[d.code] = 0;
                }
                let ally = dialog.cont_obj.build_Military_tab_own_army.find(`.ns_ally_${d.code}`);
                ally.text(ns_util.numberFormat(dialog.ally_camp_army_list[d.code]));
                dialog.army_ally_total += ns_util.toInteger(dialog.ally_camp_army_list[d.code]);
            }
        }

        dialog.cont_obj.ns_army_total.text(ns_util.math(dialog.army_total).plus(dialog.army_ally_total).number_format);
        dialog.cont_obj.ns_army_my_total.text(ns_util.numberFormat(dialog.army_total));
        dialog.cont_obj.ns_army_ally_total.text(ns_util.numberFormat(dialog.army_ally_total));
    }
}

ns_dialog.dialogs.build_Military.erase = function ()
{
    this.sorted = [];
    this.data = null;
}

ns_button.buttons.build_Military_close = new nsButtonSet('build_Military_close', 'button_back', 'build_Military', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_Military_sub_close = new nsButtonSet('build_Military_sub_close', 'button_full', 'build_Military', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.build_Military_close_all = new nsButtonSet('build_Military_close_all', 'button_close_all', 'build_Military', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_Military = new nsButtonSet('build_desc_Military', 'button_text_style_desc', 'build_Military', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_move_Military = new nsButtonSet('build_move_Military', 'button_middle_2', 'build_Military', { base_class: ns_button.buttons.build_move });
ns_button.buttons.build_cons_Military = new nsButtonSet('build_cons_Military', 'button_multi', 'build_Military', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_Military = new nsButtonSet('build_upgrade_Military', 'button_hero_action', 'build_Military', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_assign_Military = new nsButtonSet('build_assign_Military', 'button_full', 'build_Military', { base_class: ns_button.buttons.build_assign });
ns_button.buttons.build_no_assign_Military = new nsButtonSet('build_no_assign_Military', 'button_full', 'build_Military', { base_class: ns_button.buttons.build_assign });

ns_button.buttons.build_prev_Military = new nsButtonSet('build_prev_Military', 'button_multi_prev', 'build_Military', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_Military = new nsButtonSet('build_next_Military', 'button_multi_next', 'build_Military', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_Military = new nsButtonSet('build_speedup_Military', 'button_encourage', 'build_Military', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_Military = new nsButtonSet('build_cancel_Military', 'button_build', 'build_Military', { base_class: ns_button.buttons.build_cancel });

ns_button.buttons.build_Military_gate = new nsButtonSet('build_Military_gate', 'button_middle_2', 'build_Military');
ns_button.buttons.build_Military_gate.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Military;
    let arr = dialog.tag_id.split('_');
    let alias = arr.pop();
    let m = ns_cs.m.buil[alias];
    let bd_c = m.type === 'I' ? ns_cs.d.bdic : ns_cs.d.bdoc;
    let d = bd_c[dialog.data.castle_pk];

    if (ns_util.math(d.level).lt(1)) {
        return false;
    }

    ns_xhr.post('/api/military/gate', {}, function (_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.statusGate();

        ns_dialog.setDataOpen('message', _data);
    }, { useProgress: true });
}

/*ns_button.buttons.build_Military_troop_order = new nsButtonSet('build_Military_troop_order', 'button_middle_2', 'build_Military');
ns_button.buttons.build_Military_troop_order.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Military;
    let arr = dialog.tag_id.split('_');
    let alias = arr.pop();
    let m = ns_cs.m.buil[alias];
    let bd_c = m.type === 'I' ? ns_cs.d.bdic : ns_cs.d.bdoc;
    let d = bd_c[dialog.data.castle_pk];

    if (ns_util.math(d.level).lt(1)) {
        return false;
    }
    ns_dialog.setDataOpen('troop_order', { cmd_type: null });
}*/

ns_button.buttons.build_Military_tab_move_troops = new nsButtonSet('build_Military_tab_move_troops', 'button_tab', 'build_Military', { toggle_group: 'build_Military_tab' });
ns_button.buttons.build_Military_tab_move_troops.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Military;

    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.drawTab();
}
ns_button.buttons.build_Military_tab_enemy_march_troops = new nsButtonSet('build_Military_tab_enemy_march_troops', 'button_tab', 'build_Military', { base_class: ns_button.buttons.build_Military_tab_move_troops, toggle_group: 'build_Military_tab' });
ns_button.buttons.build_Military_tab_own_army = new nsButtonSet('build_Military_tab_own_army', 'button_tab', 'build_Military', { base_class: ns_button.buttons.build_Military_tab_move_troops, toggle_group: 'build_Military_tab' });
ns_button.buttons.build_Military_tab_camp_army = new nsButtonSet('build_Military_tab_camp_army', 'button_tab', 'build_Military', { base_class: ns_button.buttons.build_Military_tab_move_troops, toggle_group: 'build_Military_tab' });