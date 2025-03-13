// 다이얼로그
ns_dialog.dialogs.counter_job_list = new nsDialogSet('counter_job_list', 'dialog_full', 'size-full');
ns_dialog.dialogs.counter_job_list.cacheContents = function(_recursive)
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);
    this.cont_obj.content_title.text(ns_i18n.t('counter_job_title')); // 전체 작업 및 부대 현황

    this.cont_obj.content_counter_job_tab = new nsObject('.content_counter_job_tab', this.obj);
    this.cont_obj.content_counter_troop_tab = new nsObject('.content_counter_troop_tab', this.obj);

    this.cont_obj.wrap = {
        C: new nsObject('.content_construction', this.obj),
        T: new nsObject('.content_technique', this.obj),
        H: new nsObject('.content_explorer', this.obj),
        A: new nsObject('.content_army', this.obj),
        F: new nsObject('.content_fortification', this.obj)
    };

    this.cont_obj.counter = {
        C: this.cont_obj.wrap.C.find('span:first-child'),
        T: this.cont_obj.wrap.T.find('span:first-child'),
        H: this.cont_obj.wrap.H.find('span:first-child'),
        A: this.cont_obj.wrap.A.find('span:first-child'),
        F: this.cont_obj.wrap.F.find('span:first-child')
    };

    this.cont_obj.list = {
        C: this.cont_obj.wrap.C.find('.content_list'),
        T: this.cont_obj.wrap.T.find('.content_list'),
        H: this.cont_obj.wrap.H.find('.content_list'),
        A: this.cont_obj.wrap.A.find('.content_list'),
        F: this.cont_obj.wrap.F.find('.content_list')
    };

    this.cont_obj.troop_wrap = {
        X: new nsObject('.content_our_troop', this.obj),
        Y: new nsObject('.content_enemy_troop', this.obj),
    };

    this.cont_obj.troop_counter = {
        X: this.cont_obj.troop_wrap.X.find('span:first-child'),
        Y: this.cont_obj.troop_wrap.Y.find('span:first-child'),
    };

    this.cont_obj.troop_list = {
        X: this.cont_obj.troop_wrap.X.find('.content_list'),
        Y: this.cont_obj.troop_wrap.Y.find('.content_list'),
    };


    this.cont_obj.job_list_box_skeleton = new nsObject('#job_list_box_skeleton');

    ns_cs.flag['new_time'] = false;
}

ns_dialog.dialogs.counter_job_list.draw = function()
{
    this.tick_data = {};
    this.troop_tick_data = {};
    ns_button.toggleGroupSingle(ns_button.buttons.counter_job_tab);
    this.drawTab();
}

ns_dialog.dialogs.counter_job_list.drawTab = function(_type)
{
    let dialog = ns_dialog.dialogs.counter_job_list;

    dialog.cont_obj.content_counter_job_tab.hide();
    dialog.cont_obj.content_counter_troop_tab.hide();

    dialog.cont_obj['content_' + ns_button.toggleGroupValue('counter_list')[0]].show();

    // tab processing
    let tab = ns_button.toggleGroupValue('counter_list')[0].split('_');
    if (tab[1] === 'job') {
        dialog.drawJobList();
    } else if (tab[1] === 'troop') {
        dialog.drawTroopList();
    }
}

ns_dialog.dialogs.counter_job_list.drawJobList = function()
{
    let dialog = ns_dialog.dialogs.counter_job_list;

    // clear
    dialog.buttonClear();
    for (let d of Object.values(dialog.cont_obj.counter)) {
        d.text(0);
    }
    for (let d of Object.values(dialog.cont_obj.list)) {
        d.empty();
    }

    dialog.tick_data = {};
    dialog.tick_count = {
        C: 0,
        T: 0,
        H: 0,
        A: 0,
        F: 0
    };

    // set
    for (let [k, d] of Object.entries(ns_cs.d.time)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        let type = null;
        if (['E', 'I'].includes(d.queue_type)) {
            type = 'H';
        } else if (d.queue_type === 'M') {
            type = 'A';
        } else if (['C', 'T', 'A', 'F'].includes(d.queue_type)) {
            type = d.queue_type;
        }

        // class 이기 때문에 데이터만 찾아야함.
        if (type) {
            dialog.tick_data[k] = d;
            dialog.tick_count[type]++;

            let box = dialog.cont_obj.job_list_box_skeleton.clone();
            box.addCss(`content_pk_${k}`);

            box.find('.arrow > span').text(ns_timer.convertDescription(type, d.description));
            let speed_button = document.createElement('span');
            if (type !== 'H') {
                speed_button.setAttribute('id', `ns_button_counter_speed_up_${k}`);
                speed_button.innerText = ns_i18n.t('encourage');
            } else {
                speed_button.setAttribute('id', `none_speed_up_${k}`);
            }
            box.find('.ns_panel_flex_right').append(speed_button);

            dialog.cont_obj.list[type].append(box);
            dialog.tick_data[k].timeleft_obj = dialog.cont_obj.list[type].find(`.content_pk_${k} > .ns_panel_flex_right span:nth-child(1)`);

            if (type !== 'H') {
                ns_button.buttons[`counter_speed_up_${k}`] = new nsButtonSet(`counter_speed_up_${k}`, 'button_small_1', 'counter_job_list', { base_class: ns_button.buttons.counter_speed_up });
                dialog.buttons.push(ns_button.buttons[`counter_speed_up_${k}`]);
            }
        }

    }

    for (let [k, d] of Object.entries(dialog.cont_obj.list)) {
        if (dialog.tick_count[k] === 0) {
            let message_div = document.createElement('div');
            message_div.innerText = codeset.t('none_counter_description', k); // TODO 'H' 타입 확인 필요.;
            d.append(message_div);
        } else {
            dialog.cont_obj.counter[k].text(dialog.tick_count[k]);
        }
    }
}

ns_dialog.dialogs.counter_job_list.drawTroopList = function()
{
    let dialog = ns_dialog.dialogs.counter_job_list;

    // clear
    dialog.buttonClear();
    for (let d of Object.values(dialog.cont_obj.troop_counter)) {
        d.text(0);
    }
    for (let d of Object.values(dialog.cont_obj.troop_list)) {
        d.empty();
    }

    dialog.troop_tick_data = {};
    dialog.troop_tick_count = { X:0, Y:0 };

    // set
    for (let [k, d] of Object.entries(ns_cs.d.time)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        if (d.posi_pk !== ns_engine.game_data.cpp) {
            continue;
        }

        let type, code;
        if (d.queue_type === 'H' || d.queue_type === 'Y') {
            type = 'Y';
        } else if (d.queue_type === 'X') {
            type = d.queue_type;
        }

        if (type) {
            dialog.troop_tick_data[k] = d;
            dialog.troop_tick_count[type]++;

            let box = dialog.cont_obj.job_list_box_skeleton.clone();
            box.addCss(`content_pk_${k}`);

            let description = ns_text.convertTroopDescription(d.description);
            box.find('.arrow > span').setAttribute('id', `ns_button_counter_troop_view_${d.queue_pk}`).text(description);
            let speed_button = null;
            if (type === 'X') {
                code = ns_text.convertCode(d.description);
                // 회군 및 취소인 경우에만
                if (['return', 'withdraw'].includes(code.status)) {
                    speed_button = document.createElement('span');
                    speed_button.setAttribute('id', `ns_button_counter_speed_up_${k}`);
                    speed_button.innerText = ns_i18n.t('encourage'); // 독려
                    box.find('.ns_panel_flex_right').append(speed_button);
                }
            }

            dialog.cont_obj.troop_list[type].append(box);
            dialog.troop_tick_data[k].timeleft_obj = dialog.cont_obj.troop_list[type].find(`.content_pk_${k} > .ns_panel_flex_right span:nth-child(1)`);

            if (speed_button) {
                ns_button.buttons[`counter_speed_up_${k}`] = new nsButtonSet(`counter_speed_up_${k}`, 'button_small_1', 'counter_job_list', { base_class: ns_button.buttons.counter_speed_up });
                dialog.buttons.push(ns_button.buttons[`counter_speed_up_${k}`]);
            }

            let button_id = `counter_troop_view_${d.queue_pk}`;
            ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_text', 'counter_job_list')
            ns_button.buttons[button_id].mouseUp = function ()
            {
                ns_dialog.setDataOpen('troop_view', { type: 'view', troo_pk: d.queue_pk });
            }
            dialog.buttons.push(ns_button.buttons[button_id]);
        }

    }

    for (let [k, d] of Object.entries(dialog.cont_obj.troop_list)) {
        if (dialog.troop_tick_count[k] === 0) {
            let message_div = document.createElement('div');
            message_div.innerText = codeset.t('none_counter_description', k);
            d.append(message_div);
        } else {
            dialog.cont_obj.troop_counter[k].text(dialog.troop_tick_count[k]);
        }
    }
}

ns_dialog.dialogs.counter_job_list.timerHandler = function(_recursive)
{
    let timer_id = `${this.tag_id}_real`;
    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.counter_job_list.timerHandlerProc, 500, true); // qbw_timer_class();
    ns_timer.timers[timer_id].init();
    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.counter_job_list.timerHandlerProc = function()
{
    let dialog = ns_dialog.dialogs.counter_job_list;
    if (ns_cs.flag['new_time']) {
        ns_cs.flag['new_time'] = false;
        dialog.drawTab();
        return;
    }

    for (let [k, d] of Object.entries(dialog.tick_data)) {
        let z = d.end_dt_ut - ns_timer.now();
        d.timeleft_obj.text((z < 1) ? ns_i18n.t('timer_progress') : ns_i18n.t('time_left', [ns_util.getCostsTime(z)]));
    }

    for (let [k, d] of Object.entries(dialog.troop_tick_data)) {
        let z = d.end_dt_ut - ns_timer.now();
        d.timeleft_obj.text((z < 1) ? ns_i18n.t('timer_progress') : ns_i18n.t('time_left', [ns_util.getCostsTime(z)]));
    }
}

ns_button.buttons.counter_job_list_close = new nsButtonSet('counter_job_list_close', 'button_back', 'counter_job_list', { base_class: ns_button.buttons.common_close });
ns_button.buttons.counter_job_list_sub_close = new nsButtonSet('counter_job_list_sub_close', 'button_full', 'counter_job_list', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.counter_job_list_close_all = new nsButtonSet('counter_job_list_close_all', 'button_close_all', 'counter_job_list', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.counter_job_tab = new nsButtonSet('counter_job_tab', 'button_tab', 'counter_job_list', { toggle_group: 'counter_list' });
ns_button.buttons.counter_job_tab.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.counter_job_list;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    ns_dialog.dialogs.counter_job_list.drawTab();
}
ns_button.buttons.counter_troop_tab = new nsButtonSet('counter_troop_tab', 'button_tab', 'counter_job_list',  { base_class: ns_button.buttons.counter_job_tab, toggle_group: 'counter_list' });

ns_button.buttons.alert_toggle_ally = new nsButtonSet('alert_toggle_ally', 'button_alert_toggle', 'counter_job_list',  { base_class: ns_button.buttons.counter_job_tab });
ns_button.buttons.alert_toggle_ally.mouseUp = function()
{
    let dialog = ns_dialog.dialogs.counter_job_list;
    let type = this.tag_id.split('_').pop();

    let post_data = {};
    post_data[`alert_effect_${type}`] = this.clicked ? 'Y' : 'N';

    ns_xhr.post('/api/setting/update', post_data, { useProgress: true });
}
ns_button.buttons.alert_toggle_enemy = new nsButtonSet('alert_toggle_enemy', 'button_alert_toggle', 'counter_job_list',  { base_class: ns_button.buttons.alert_toggle_ally });