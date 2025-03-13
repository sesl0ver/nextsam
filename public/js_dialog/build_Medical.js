// 다이얼로그
ns_dialog.dialogs.build_Medical = new nsDialogSet('build_Medical', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });
ns_dialog.dialogs.build_Medical.injured = {};
ns_dialog.dialogs.build_Medical.sorted = null;

ns_dialog.dialogs.build_Medical.cacheContents = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.cacheContents.call(this, true);
    }

    //추가적인것
    this.cont_obj.content_concurr_body = new nsObject('.content_concurr_body', this.obj);
    this.cont_obj.content_concurr_idle = new nsObject('.content_concurr_idle', this.obj);

    this.cont_obj.content_concurr_title = new nsObject('.content_concurr_title', this.obj);
    this.cont_obj.content_concurr_time = new nsObject('.content_concurr_time', this.obj);

    // this.s.cont_queue = this.cont_obj.find('.cont_queue'); // QUEUE 리스트는 사용안함.
    // this.s.cont_queue_list_skel = $('#skeleton_cont_queue_list');

    ns_cs.flag['new_time'] = false;

    // 한번만 그려줌
    this.cont_obj.medical_army_cache_wrap = new nsObject('.medical_army_cache_wrap', this.obj);
    this.cont_obj.develop_list_skeleton = new nsObject('#develop_list_skeleton');
    this.drawList();
}

ns_dialog.dialogs.build_Medical.draw = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.draw.call(this, true);
    }

    // this.s.cont_queue.hide();
    //ns_button.buttons['queue_switch_medical'].setDisable();

    this.draw_remote_data = null;
    ns_xhr.post('/api/medical/current', { in_cast_pk: this.data.castle_pk }, (_data, _status) => {
        if (!ns_xhr.returnCheck(_data)) {
            return;
        }
        this.drawRemote();
    });
}

ns_dialog.dialogs.build_Medical.drawList = function()
{
    let dialog = ns_dialog.dialogs.build_Medical;

    // 목록 리스트 캐싱
    dialog.sorted = [];
    for (let [k, d] of Object.entries(ns_cs.m.army)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        dialog.sorted.push(d);
    }

    if (dialog.sorted.length > 1) {
        dialog.sorted = ns_util.arraySort(dialog.sorted, -1, 'm_army_pk');
    }

    // 목록 그리기
    dialog.cont_obj.medical_army_cache_wrap.empty();

    for (let d of dialog.sorted) {
        let skeleton = dialog.cont_obj.develop_list_skeleton.clone();
        skeleton.find('.develop_list_title').text(ns_i18n.t(`army_title_${d.m_army_pk}`));
        skeleton.find('.develop_list_desc').addCss(d.code);
        skeleton.find('.develop_list_desc').text(0);

        skeleton.find('.develop_list_image').setAttribute('id', `ns_button_medical_list_information_${d.code}`);
        skeleton.find('.develop_list_image').addCss(`army_image_${d.code}`);

        skeleton.find('.develop_list_submit').setAttribute('id', `ns_button_medical_list_${d.code}`);
        skeleton.find('.develop_list_submit').text(ns_i18n.t('treatment')); // 치료

        skeleton.find('.develop_list_cancel').setAttribute('id', `ns_button_medical_list_disperse_${d.code}`);
        skeleton.find('.develop_list_cancel').text(ns_i18n.t('disperse')); // 해산

        dialog.cont_obj.medical_army_cache_wrap.append(skeleton);

        ns_button.buttons[`medical_list_${d.code}`] = new nsButtonSet(`medical_list_${d.code}`, 'button_small_1', 'build_Medical', { base_class: ns_button.buttons.build_Medical_army_treatment });
        ns_button.buttons[`medical_list_information_${d.code}`] = new nsButtonSet(`medical_list_information_${d.code}`, 'button_empty', 'build_Medical', { base_class: ns_button.buttons.build_Medical_information });
        ns_button.buttons[`medical_list_disperse_${d.code}`] = new nsButtonSet(`medical_list_disperse_${d.code}`, 'button_small_2', 'build_Medical', { base_class: ns_button.buttons.build_Medical_army_disperse });
    }
}

ns_dialog.dialogs.build_Medical.drawRemote = function()
{
    let dialog = ns_dialog.dialogs.build_Medical;

    // 큐리스트 사용안함
    // dlg.s.cont_queue.empty();
    //ns_button.buttons['queue_switch_army'].setDisable();

    let d = ns_cs.d['bdic'][dialog.data.castle_pk];
    if ((typeof d != 'object' || !d.m_buil_pk && !d.level) || !ns_util.math(d.m_buil_pk).eq(200700)) {
        return;
    }

    dialog.queue_cnt = 0;
    dialog.cont_obj.content_concurr_body.hide();
    dialog.cont_obj.content_concurr_idle.show();

    // 대기열
    // let queue_max = (! d.level) ? 0 : ns_util.math(d.level).minus(1).integer;

    let buil_medi_pk = null, time_pk = null, description = null;
    dialog.draw_remote_data = null;

    for (let [k, d] of Object.entries(ns_cs.d.time)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        if (d.queue_type === 'M') {
            buil_medi_pk = d.queue_pk;
            time_pk = k;
            description = d.description;
            break;
        }
    }

    if (! buil_medi_pk) {
        return;
    }

    dialog.data.time_pk = time_pk;

    dialog.draw_remote_data = ns_util.toInteger(ns_cs.d.time[time_pk].end_dt_ut);

    if (! dialog.draw_remote_data) {
        return;
    }

    dialog.recruit_timer_end_dt_ut = parseInt(ns_cs.d.time[time_pk].end_dt_ut);

    dialog.cont_obj.content_concurr_title.html(ns_timer.convertDescription('M', description));

    dialog.cont_obj.content_concurr_body.show();
    dialog.cont_obj.content_concurr_idle.hide();
    dialog.timerHandlerReal();
}

ns_dialog.dialogs.build_Medical.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_Medical.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_Medical.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_Medical;

    if (ns_cs.flag['new_time']) {
        ns_cs.flag['new_time'] = false;
        dialog.draw();
        return;
    }

    // 현재 보유 중인 병과별 부상병 수
    for (let d of dialog.sorted) {
        dialog.cont_obj.medical_army_cache_wrap.find(`.${d.code}`).text(ns_util.numberFormat(ns_cs.d.army_medi[d.code].v));
    }

    // 현재 개발 중인 부분 시간 계산
    if (dialog.draw_remote_data) {
        let left = dialog.draw_remote_data - ns_timer.now();
        if (left <= 0) {
            dialog.cont_obj.content_concurr_time.text(ns_i18n.t('in_progress'));
        } else {
            dialog.cont_obj.content_concurr_time.text(ns_util.getCostsTime(left));
        }
    }

    dialog.timerHandlerButtonCondition();
}

ns_dialog.dialogs.build_Medical.timerHandlerButtonCondition = function()
{
    let dialog = ns_dialog.dialogs.build_Medical;
    for (let d of dialog.sorted) {
        // 버튼 처리
        if (ns_button.buttons['medical_list_' + d.code]) {
            if (ns_check_condition.checkAll(d.m_medi_cond_pk, dialog.data.castle_pk)) {
                if (ns_cs.d.army_medi[d.code].v > 0) {
                    ns_button.buttons['medical_list_' + d.code].setEnable();
                } else {
                    ns_button.buttons['medical_list_' + d.code].setDisable();
                }
            } else {
                ns_button.buttons['medical_list_' + d.code].setDisable();
            }

            if (ns_cs.d.army_medi[d.code].v > 0) {
                ns_button.buttons['medical_list_disperse_' + d.code].setEnable();
            } else {
                ns_button.buttons['medical_list_disperse_' + d.code].setDisable();
            }
        }
    }
}

ns_button.buttons.build_Medical_close = new nsButtonSet('build_Medical_close', 'button_back', 'build_Medical', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_Medical_sub_close = new nsButtonSet('build_Medical_sub_close', 'button_full', 'build_Medical', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.build_Medical_close_all = new nsButtonSet('build_Medical_close_all', 'button_close_all', 'build_Medical', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_Medical = new nsButtonSet('build_desc_Medical', 'button_text_style_desc', 'build_Medical', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_move_Medical = new nsButtonSet('build_move_Medical', 'button_middle_2', 'build_Medical', { base_class: ns_button.buttons.build_move });
ns_button.buttons.build_cons_Medical = new nsButtonSet('build_cons_Medical', 'button_multi', 'build_Medical', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_Medical = new nsButtonSet('build_upgrade_Medical', 'button_hero_action', 'build_Medical', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_assign_Medical = new nsButtonSet('build_assign_Medical', 'button_full', 'build_Medical', { base_class: ns_button.buttons.build_assign });
ns_button.buttons.build_no_assign_Medical = new nsButtonSet('build_no_assign_Medical', 'button_full', 'build_Medical', { base_class: ns_button.buttons.build_assign });

ns_button.buttons.build_prev_Medical = new nsButtonSet('build_prev_Medical', 'button_multi_prev', 'build_Medical', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_Medical = new nsButtonSet('build_next_Medical', 'button_multi_next', 'build_Medical', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_Medical = new nsButtonSet('build_speedup_Medical', 'button_encourage', 'build_Medical', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_Medical = new nsButtonSet('build_cancel_Medical', 'button_build', 'build_Medical', { base_class: ns_button.buttons.build_cancel });

ns_button.buttons.concurr_medical_speedup = new nsButtonSet('concurr_medical_speedup', 'button_small_1', 'build_Medical');
ns_button.buttons.concurr_medical_speedup.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Medical;
    let castle_type = (dialog.data?.castle_type === 'bdic') ? 'I' : "O";
    ns_dialog.setDataOpen('item_quick_use', { type: 'speedup', queue_type: 'M', position_type: castle_type, in_cast_pk: dialog.data.castle_pk, time_pk: dialog.data.time_pk });
};

// ns_button.buttons.concurr_cancel_build_Medical = new nsButtonSet('concurr_cancel_build_Medical', 'btn_queue_cancel', 'build_Medical', { base_class: ns_button.buttons.concurr_cancel });

ns_button.buttons.build_Medical_army_treatment = new nsButtonSet('build_Medical_army_treatment', null, 'build_Medical');
ns_button.buttons.build_Medical_army_treatment.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Medical;
    let code = _e.target.id.split('_medical_list_').pop();
    let pk = ns_cs.m.army[code].m_army_pk;

    ns_dialog.setDataOpen('development', { title: 'medi', action: 'treatment', type: code, m_pk: pk, castle_pk: dialog.data.castle_pk });
}
ns_button.buttons.build_Medical_army_treatment.mouseEnter = function(_e)
{
    if (this.enable) {
        return;
    }
    let code = _e.target.id.split('_medical_list_').pop();
    ns_timer.timers[`infomation_effect_${code}`] = new nsTimerSet(function ()
    {
        if (ns_timer.timers[`infomation_effect_${code}`]._loop_run_count > 4) {
            ns_button.buttons[`medical_list_information_${code}`].obj.removeCss('information_effect');
            ns_timer.timers[`infomation_effect_${code}`].clear();
            return;
        }
        ns_button.buttons[`medical_list_information_${code}`].obj.addCss('information_effect');
    }, 1000, false, 5);
    ns_timer.timers[`infomation_effect_${code}`].init();
}

ns_button.buttons.build_Medical_army_treatment.mouseLeave = function(_e)
{
    if (this.enable) {
        return;
    }
    let code = _e.target.id.split('_medical_list_').pop();
    if (ns_timer.timers[`infomation_effect_${code}`]) {
        if (ns_timer.timers[`infomation_effect_${code}`]._loop_run_count < 1) {
            ns_button.buttons[`medical_list_information_${code}`].obj.removeCss('information_effect');
            ns_timer.timers[`infomation_effect_${code}`].clear();
        }
    }
}

ns_button.buttons.build_Medical_information = new nsButtonSet('build_Medical_information', null, 'build_Medical');
ns_button.buttons.build_Medical_information.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Medical;
    let code = _e.target.id.split('_information_').pop();
    let pk = ns_cs.m.army[code].m_army_pk;

    ns_dialog.setDataOpen('information', { title: 'medi', type: code, m_pk: pk, castle_pk: dialog.data.castle_pk });
}

ns_button.buttons.build_Medical_army_disperse = new nsButtonSet('build_Medical_army_disperse', null, 'build_Medical');
ns_button.buttons.build_Medical_army_disperse.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Medical;
    let code = _e.target.id.split('_disperse_').pop();
    let pk = ns_cs.m.army[code].m_army_pk;

    ns_dialog.setDataOpen('disperse', { title: 'medi', type:code, m_pk:pk, castle_pk: dialog.data.castle_pk });
}