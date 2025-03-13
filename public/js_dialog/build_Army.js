// 다이얼로그
ns_dialog.dialogs.build_Army = new nsDialogSet('build_Army', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });
ns_dialog.dialogs.build_Army.sorted = [];
ns_dialog.dialogs.build_Army.queue_conut = 0;
ns_dialog.dialogs.build_Army.build_army = false;

ns_dialog.dialogs.build_Army.cacheContents = function (_recursive) {
    if (this.base_class && !_recursive) {
        this.base_class.cacheContents.call(this, true);
    }

    //추가적인것
    this.cont_obj.develop_list_skeleton = new nsObject('#develop_list_skeleton');
    this.cont_obj.ns_army_cache_wrap = new nsObject('.ns_army_cache_wrap', this.obj);

    this.cont_obj.content_queue = new nsObject('.content_queue', this.obj);

    this.cont_obj.content_concurr_body = new nsObject('.content_concurr_body', this.obj);
    this.cont_obj.content_concurr_idle = new nsObject('.content_concurr_idle', this.obj);
    this.cont_obj.content_concurr_title = new nsObject('.content_concurr_title', this.obj);
    this.cont_obj.content_concurr_time = new nsObject('.content_concurr_time', this.obj);

    ns_cs.flag['new_time'] = false;

    // 리스트 그리는 것은 한번만
    this.drawList();
}

ns_dialog.dialogs.build_Army.draw = function (_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.draw.call(this, true);
    }

    this.draw_remote_data = null;
    this.cont_obj.content_queue.hide(); // Queue 사용안함.

    this.drawRemote();

    this.timerHandlerButtonCondition();
}

ns_dialog.dialogs.build_Army.erase = function (_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.erase.call(this, true);
    }
}

ns_dialog.dialogs.build_Army.drawList = function ()
{
    let dialog = ns_dialog.dialogs.build_Army;

    // 목록 리스트 캐싱
    dialog.sorted = [];

    for (let [k, d] of Object.entries(ns_cs.m.army)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        dialog.sorted.push(d);
    }

    dialog.sorted = ns_util.arraySort(dialog.sorted, -1, 'm_army_pk');

    // 목록 그리기
    this.cont_obj.ns_army_cache_wrap.empty();

    for (let [k, d] of Object.entries(dialog.sorted)) {
        let skeleton = dialog.cont_obj.develop_list_skeleton.clone();
        skeleton.find('.develop_list_title').text(ns_i18n.t(`army_title_${d.m_army_pk}`));
        skeleton.find('.develop_list_desc').addCss(d.code);
        skeleton.find('.develop_list_desc').text(0);

        skeleton.find('.develop_list_image').setAttribute('id', `ns_button_army_list_information_${d.code}`);
        skeleton.find('.develop_list_image').addCss(`army_image_${d.code}`);

        skeleton.find('.develop_list_submit').setAttribute('id', `ns_button_army_list_${d.code}`);
         skeleton.find('.develop_list_submit').text(ns_i18n.t('training')); // 훈련

        skeleton.find('.develop_list_cancel').setAttribute('id', `ns_button_army_list_disperse_${d.code}`);
        skeleton.find('.develop_list_cancel').text(ns_i18n.t('disperse')); // 해산

        this.cont_obj.ns_army_cache_wrap.append(skeleton);

        ns_button.buttons[`army_list_${d.code}`] = new nsButtonSet(`army_list_${d.code}`, 'button_small_1', 'build_Army',{ base_class: ns_button.buttons.build_Army_prod });
        ns_button.buttons[`army_list_information_${d.code}`] = new nsButtonSet(`army_list_information_${d.code}`, 'button_empty', 'build_Army', { base_class: ns_button.buttons.build_Army_information });
        ns_button.buttons[`army_list_disperse_${d.code}`] = new nsButtonSet(`army_list_disperse_${d.code}`, 'button_small_2', 'build_Army', { base_class: ns_button.buttons.build_Army_disperse });
    }
}

ns_dialog.dialogs.build_Army.drawRemote = function()
{
    let dialog = ns_dialog.dialogs.build_Army;
    let castle_pk = dialog.data.castle_pk;
    // 큐리스트 그리기 전 초기화
    dialog.queue_conut = 0;
    dialog.build_army = false;

    this.cont_obj.content_queue.empty();

    try {
        let d = ns_cs.d['bdic'][castle_pk];
        if ((typeof d != 'object' || ! d.m_buil_pk && ! d.level) || d.m_buil_pk !== 200500) {
            return;
        }
        dialog.cont_obj.content_concurr_body.hide();
        dialog.cont_obj.content_concurr_idle.show();

        let queue_max = (!d?.level) ? 0 : ns_cs.m.buil['200500']['level'][d.level]['variation_1'] - 1;
        let build_army_pk = null, time_pk = null, description = null;
        dialog.draw_remote_data = null;

        for (let [k, d] of Object.entries(ns_cs.d.time)) {
            if (! ns_util.isNumeric(k) || d.queue_type !== 'A' || d.in_cast_pk !== castle_pk) {
                continue;
            }
            build_army_pk = d.queue_pk;
            time_pk = k;
            description = d.description;
        }
        this.data.time_pk = null;
        if (! build_army_pk) {
            return;
        }
        this.data.time_pk = time_pk;
        dialog.draw_remote_data = ns_util.toInteger(ns_cs.d.time[time_pk].end_dt_ut);
        if (! dialog.draw_remote_data) {
            return;
        }
        dialog.recruit_timer_end_dt_ut = ns_util.toInteger(ns_cs.d.time[time_pk].end_dt_ut);
        dialog.build_army = true;

        dialog.cont_obj.content_concurr_title.html(ns_timer.convertDescription('A', description));
    } catch (e) {
        console.error(e);
    }

    dialog.cont_obj.content_concurr_body.show();
    dialog.cont_obj.content_concurr_idle.hide();
}

ns_dialog.dialogs.build_Army.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = `${this.tag_id}_real`;
    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_Army.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_Army.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_Army;

    if (ns_cs.flag['new_time']) {
        ns_cs.flag['new_time'] = false;
        dialog.draw();
        return;
    }

    // 현재 보유 중인 병과별 병력 수
    for (let [k, d] of Object.entries(ns_cs.m.army)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        dialog.cont_obj.ns_army_cache_wrap.find(`.${d.code}`).text(ns_util.numberFormat(ns_cs.d.army[d.code].v));
    }

    // 현재 개발 중인 부분 시간 계산
    if (dialog.draw_remote_data) {
        let left = dialog.draw_remote_data - ns_timer.now();
        dialog.cont_obj.content_concurr_time.text((left <= 0) ? ns_i18n.t('in_progress') : ns_util.getCostsTime(left));
    }

    dialog.timerHandlerButtonCondition();
}

ns_dialog.dialogs.build_Army.timerHandlerButtonCondition = function()
{
    let dialog = ns_dialog.dialogs.build_Army;

    // 버튼 처리
    for (let [k, d] of Object.entries(dialog.sorted)) {
        if (ns_button.buttons[`army_list_${d.code}`]) {
            if (ns_check_condition.checkAll(d.m_cond_pk, dialog.data.castle_pk)) {
                ns_button.buttons[`army_list_${d.code}`].setEnable();
            } else {
                ns_button.buttons[`army_list_${d.code}`].setDisable();
            }
        }
        if (ns_button.buttons[`army_list_disperse_${d.code}`]) {
            if (ns_cs.d.army[d.code].v > 0) {
                ns_button.buttons[`army_list_disperse_${d.code}`].setEnable();
            } else {
                ns_button.buttons[`army_list_disperse_${d.code}`].setDisable();
            }
        }
    }
}

ns_button.buttons.build_Army_close = new nsButtonSet('build_Army_close', 'button_back', 'build_Army', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_Army_sub_close = new nsButtonSet('build_Army_sub_close', 'button_full', 'build_Army', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.build_Army_close_all = new nsButtonSet('build_Army_close_all', 'button_close_all', 'build_Army', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_Army = new nsButtonSet('build_desc_Army', 'button_text_style_desc', 'build_Army', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_move_Army = new nsButtonSet('build_move_Army', 'button_middle_2', 'build_Army', { base_class: ns_button.buttons.build_move });
ns_button.buttons.build_cons_Army = new nsButtonSet('build_cons_Army', 'button_multi', 'build_Army', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_Army = new nsButtonSet('build_upgrade_Army', 'button_hero_action', 'build_Army', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_assign_Army = new nsButtonSet('build_assign_Army', 'button_full', 'build_Army', { base_class: ns_button.buttons.build_assign });
ns_button.buttons.build_no_assign_Army = new nsButtonSet('build_no_assign_Army', 'button_full', 'build_Army', { base_class: ns_button.buttons.build_assign });

ns_button.buttons.build_prev_Army = new nsButtonSet('build_prev_Army', 'button_multi_prev', 'build_Army', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_Army = new nsButtonSet('build_next_Army', 'button_multi_next', 'build_Army', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_Army = new nsButtonSet('build_speedup_Army', 'button_encourage', 'build_Army', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_Army = new nsButtonSet('build_cancel_Army', 'button_build', 'build_Army', { base_class: ns_button.buttons.build_cancel });


ns_button.buttons.concurr_army_speedup = new nsButtonSet('concurr_army_speedup', 'button_small_1', 'build_Army');
ns_button.buttons.concurr_army_speedup.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Army;
    let position_type = (dialog.data.castle_type === 'bdic') ? 'I' : 'O';
    ns_dialog.setDataOpen('item_quick_use', { type: 'speedup', queue_type:'A', position_type: position_type, in_cast_pk: dialog.data.castle_pk, time_pk: dialog.data.time_pk });
};

ns_button.buttons.build_Army_prod = new nsButtonSet('build_Army_prod', 'button_empty', 'build_Army');
ns_button.buttons.build_Army_prod.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Army;
    let code = _e.target.id.split('_army_list_').pop();
    ns_dialog.setDataOpen('development', { title: 'army', action: 'upgrade', type: code, m_pk: ns_cs.m.army[code].m_army_pk, castle_pk: dialog.data.castle_pk });
}
ns_button.buttons.build_Army_prod.mouseEnter = function(_e)
{
    if (this.enable) {
        return;
    }
    let code = _e.target.id.split('_army_list_').pop();
    ns_timer.timers[`infomation_effect_${code}`] = new nsTimerSet(function ()
    {
        if (ns_timer.timers[`infomation_effect_${code}`]._loop_run_count > 4) {
            ns_button.buttons[`army_list_information_${code}`].obj.removeCss('information_effect');
            ns_timer.timers[`infomation_effect_${code}`].clear();
            return;
        }
        ns_button.buttons[`army_list_information_${code}`].obj.addCss('information_effect');
    }, 1000, false, 5);
    ns_timer.timers[`infomation_effect_${code}`].init();
}

ns_button.buttons.build_Army_prod.mouseLeave = function(_e)
{
    if (this.enable) {
        return;
    }
    let code = _e.target.id.split('_army_list_').pop();
    if (ns_timer.timers[`infomation_effect_${code}`]) {
        if (ns_timer.timers[`infomation_effect_${code}`]._loop_run_count < 1) {
            ns_button.buttons[`army_list_information_${code}`].obj.removeCss('information_effect');
            ns_timer.timers[`infomation_effect_${code}`].clear();
        }
    }
}

ns_button.buttons.build_Army_information = new nsButtonSet('build_Army_information', 'button_empty', 'build_Army');
ns_button.buttons.build_Army_information.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Army;
    let code = _e.target.id.split('_information_').pop();
    ns_dialog.setDataOpen('information', { title: 'army', type: code, m_pk: ns_cs.m.army[code].m_army_pk, castle_pk: dialog.data.castle_pk });
}

ns_button.buttons.build_Army_disperse = new nsButtonSet('build_Army_disperse', 'button_empty', 'build_Army');
ns_button.buttons.build_Army_disperse.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Army;
    let code = _e.target.id.split('_disperse_').pop();
    ns_dialog.setDataOpen('disperse', { title: 'army', type: code, m_pk: ns_cs.m.army[code].m_army_pk, castle_pk: dialog.data.castle_pk });
}