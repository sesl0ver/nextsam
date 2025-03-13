// 다이얼로그
ns_dialog.dialogs.build_Embassy = new nsDialogSet('build_Embassy', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });
ns_dialog.dialogs.build_Embassy.sorted = null;
ns_dialog.dialogs.build_Embassy.current_camp_status = null;
ns_dialog.dialogs.build_Embassy.buttons = [];

ns_dialog.dialogs.build_Embassy.cacheContents = function (_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.cacheContents.call(this, true);
    }

    this.cont_obj.content_camp = new nsObject('.content_camp', this.obj);
    this.cont_obj.camp_army_list_warp = new nsObject('.camp_army_list_warp', this.obj);
}

ns_dialog.dialogs.build_Embassy.draw = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.draw.call(this, true);
    }

    this.drawList();
}

ns_dialog.dialogs.build_Embassy.drawList = function ()
{
    let dialog = ns_dialog.dialogs.build_Embassy;
    let arr = dialog.tag_id.split('_');
    let alias = arr.pop();
    let m = ns_cs.m.buil[alias];
    let bd_c = m.type === 'I' ? ns_cs.d.bdic : ns_cs.d.bdoc;
    let d = bd_c[dialog.data.castle_pk];

    if (ns_util.math(d.level).lt(1)) {
        ns_button.buttons.build_Embassy_camp_open.setDisable();
    } else {
        ns_button.buttons.build_Embassy_camp_open.setEnable();
    }

    ns_xhr.post('/api/embassy/list', {}, dialog.drawListPost);
}

ns_dialog.dialogs.build_Embassy.drawListPost = function (_data)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.build_Embassy;

    dialog.timerHandlerReal();

    // dialog.curr_camp_cnt = 0;
    // dialog.camp_army_list  = [];

    dialog.buttonClear();
    dialog.buttons = [];
    dialog.sorted = [];
    dialog.cont_obj.camp_army_list_warp.empty();

    if (_data.length < 1) {
        // 리스트가 없을 때
        let tr = document.createElement('tr');
        let col1 = document.createElement('td');
        col1.colSpan = 5;
        let col1_span = document.createElement('span');
        col1_span.innerHTML = ns_i18n.t('no_support_troop_description');
        col1.appendChild(col1_span);

        tr.appendChild(col1);

        dialog.cont_obj.camp_army_list_warp.append(tr);
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

        for (let d of Object.values(dialog.sorted)) {
            // 리스트에 저장 - TODO 할 필요가 있나?
            // dialog.camp_army_list.push(d);

            let tr = document.createElement('tr');
            // tr.setAttribute('id', `ns_button_Embassy_troop_view_${d.troo_pk}`);

            let withdrawal_dt, arrival_dt, action;
            if(d.status === 'C') {
                withdrawal_dt = ns_timer.getDateTimeString(d.withdrawal_dt, true, true, true);
                arrival_dt = '-';
                action = ns_i18n.t('troop_deploying'); // 주둔 중
            } else {
                withdrawal_dt = '-';
                arrival_dt = ns_timer.getDateTimeString(d.arrival_dt, true, true, true)
                action = ns_i18n.t('troop_moving'); // 이동중
                action += ` (${(d.cmd_type === 'R') ? ns_i18n.t('troop_supporting') : ns_i18n.t('troop_transporting')})`; // (지원) or (수송)
            }

            let col1 = document.createElement('td');
            let col1_span = document.createElement('span');
            col1_span.innerHTML = d.captain_desc;
            col1.appendChild(col1_span);

            let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
            button.setAttribute('id', `ns_button_Embassy_troop_view_${d.troo_pk}`);
            col1.append(button);

            let col2 = document.createElement('td');
            let col2_span = document.createElement('span');
            col2_span.innerHTML = ns_text.convertPositionName(d.from_position);
            col2.appendChild(col2_span);

            let col3 = document.createElement('td');
            let col3_span = document.createElement('span');
            col3_span.innerHTML = arrival_dt;
            col3.appendChild(col3_span);

            let col4 = document.createElement('td');
            let col4_span = document.createElement('span');
            col4_span.innerHTML = withdrawal_dt;
            col4.appendChild(col4_span);

            let col5 = document.createElement('td');
            let col5_span = document.createElement('span');
            col5_span.innerHTML = action;
            col5.appendChild(col5_span);

            tr.appendChild(col1);
            tr.appendChild(col2);
            tr.appendChild(col3);
            tr.appendChild(col4);
            tr.appendChild(col5);

            dialog.cont_obj.camp_army_list_warp.append(tr);

            ns_button.buttons[`Embassy_troop_view_${d.troo_pk}`] = new nsButtonSet(`Embassy_troop_view_${d.troo_pk}`, 'button_table', 'build_Embassy');
            ns_button.buttons[`Embassy_troop_view_${d.troo_pk}`].mouseUp = function ()
            {
                ns_dialog.setDataOpen('troop_view', { type: 'camp', troo_pk: d.troo_pk });
            }
            dialog.buttons.push(ns_button.buttons[`Embassy_troop_view_${d.troo_pk}`]);
        }
    }
}

ns_dialog.dialogs.build_Embassy.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }
    let timer_id = this.tag_id + '_real';
    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_Embassy.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();
    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_Embassy.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_Embassy;
    let arr = dialog.tag_id.split('_');
    let alias = arr.pop();
    let bd_c = ns_cs.m.buil[alias].type === 'I' ? ns_cs.d.bdic : ns_cs.d.bdoc;
    let d = bd_c[dialog.data.castle_pk];
    if (ns_util.math(d.level).lte(0)) {
        return;
    }
    let content_camp = (ns_cs.d.terr.yn_alliance_camp.v === 'N') ? ns_i18n.t('embassy_camp_disabled') : ns_i18n.t('embassy_camp_enabled');
    dialog.cont_obj.content.find('.content_camp')?.text(content_camp);
    if (ns_util.math(d.level).lt(1)) {
        ns_button.buttons.build_Embassy_camp_open.obj.hide();
    } else {
        ns_button.buttons.build_Embassy_camp_open.setEnable();
        ns_button.buttons.build_Embassy_camp_open.obj.show().text((ns_cs.d.terr.yn_alliance_camp.v === 'N') ? ns_i18n.t('embassy_camp_disable') : ns_i18n.t('embassy_camp_enable'));
    }
}

ns_button.buttons.build_Embassy_close = new nsButtonSet('build_Embassy_close', 'button_back', 'build_Embassy', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_Embassy_sub_close = new nsButtonSet('build_Embassy_sub_close', 'button_full', 'build_Embassy', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.build_Embassy_close_all = new nsButtonSet('build_Embassy_close_all', 'button_close_all', 'build_Embassy', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_Embassy = new nsButtonSet('build_desc_Embassy', 'button_text_style_desc', 'build_Embassy', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_move_Embassy = new nsButtonSet('build_move_Embassy', 'button_middle_2', 'build_Embassy', { base_class: ns_button.buttons.build_move });
ns_button.buttons.build_cons_Embassy = new nsButtonSet('build_cons_Embassy', 'button_multi', 'build_Embassy', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_Embassy = new nsButtonSet('build_upgrade_Embassy', 'button_hero_action', 'build_Embassy', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_assign_Embassy = new nsButtonSet('build_assign_Embassy', 'button_full', 'build_Embassy', { base_class: ns_button.buttons.build_assign });
ns_button.buttons.build_no_assign_Embassy = new nsButtonSet('build_no_assign_Embassy', 'button_full', 'build_Embassy', { base_class: ns_button.buttons.build_assign });

ns_button.buttons.build_prev_Embassy = new nsButtonSet('build_prev_Embassy', 'button_multi_prev', 'build_Embassy', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_Embassy = new nsButtonSet('build_next_Embassy', 'button_multi_next', 'build_Embassy', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_Embassy = new nsButtonSet('build_speedup_Embassy', 'button_encourage', 'build_Embassy', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_Embassy = new nsButtonSet('build_cancel_Embassy', 'button_build', 'build_Embassy', { base_class: ns_button.buttons.build_cancel });


ns_button.buttons.build_Embassy_camp_open = new nsButtonSet('build_Embassy_camp_open', 'button_middle_2', 'build_Embassy');
ns_button.buttons.build_Embassy_camp_open.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Embassy;
    let arr = dialog.tag_id.split('_');
    let alias = arr.pop();
    let m = ns_cs.m.buil[alias];
    let bd_c = m.type === 'I' ? ns_cs.d.bdic : ns_cs.d.bdoc;
    let d = bd_c[dialog.data.castle_pk];

    if (ns_util.math(d.level).lt(1)) {
        return;
    }

    ns_xhr.post('/api/embassy/camp', {}, (_data) =>
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];
        dialog.timerHandlerReal();
        ns_dialog.setDataOpen('message', _data.message);
    }, { useProgress: true });
}