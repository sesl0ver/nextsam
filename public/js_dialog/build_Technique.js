// 다이얼로그
ns_dialog.dialogs.build_Technique = new nsDialogSet('build_Technique', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });
ns_dialog.dialogs.build_Technique.socket_no = 1;
ns_dialog.dialogs.build_Technique.total_page = 0;
ns_dialog.dialogs.build_Technique.current_page = null;
ns_dialog.dialogs.build_Technique.sorted = null;

ns_dialog.dialogs.build_Technique.cacheContents = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.cacheContents.call(this, true);
    }

    //추가적인것
    this.cont_obj.content_concurr_body = new nsObject('.content_concurr_body', this.obj);
    this.cont_obj.content_concurr_idle = new nsObject('.content_concurr_idle', this.obj);

    this.cont_obj.content_concurr_title = new nsObject('.content_concurr_title', this.obj);
    this.cont_obj.content_concurr_time = new nsObject('.content_concurr_time', this.obj);

    // 리스트 그리는 것은 한번만
    this.cont_obj.technique_cache_wrap = new nsObject('.technique_cache_wrap', this.obj);
    this.cont_obj.develop_list_skeleton = new nsObject('#develop_list_skeleton');
    this.drawList();
}

ns_dialog.dialogs.build_Technique.draw = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.draw.call(this, true);
    }

    this.timerHandlerButtonCondition();

    this.draw_remote_data = null;
    this.drawRemote();
}

ns_dialog.dialogs.build_Technique.drawList = function()
{
    let dialog = ns_dialog.dialogs.build_Technique;

    // 목록 리스트 캐싱
    dialog.sorted = [];
    for (let [k, d] of Object.entries(ns_cs.m.tech)) {
        if (!ns_util.isNumeric(k)) {
            continue;
        }
        dialog.sorted.push(d);
    }

    if (dialog.sorted.length > 1) {
        dialog.sorted = ns_util.arraySort(dialog.sorted, 1, 'orderno') ;
    }

    // 목록 그리기
    dialog.cont_obj.technique_cache_wrap.empty();
    for (let d of dialog.sorted) {
        let skeleton = dialog.cont_obj.develop_list_skeleton.clone();
        skeleton.find('.develop_list_title').text(ns_i18n.t(`tech_title_${d.m_tech_pk}`));
        skeleton.find('.develop_list_desc').addCss(d.code);
        // skeleton.find('.develop_list_desc').html('Lv.<span class="technique_lv_' + d.code + '">0</span> / Ct.<span class="technique_ct_' + d.code + '">0</span>', true);
        skeleton.find('.develop_list_desc').html('Lv.<span class="technique_lv_' + d.code + '">0</span>', true);

        skeleton.find('.develop_list_image').setAttribute('id', `ns_button_technique_list_information_${d.code}`);
        skeleton.find('.develop_list_image').addCss(`technique_image_${d.code}`);

        skeleton.find('.develop_list_submit').setAttribute('id', `ns_button_technique_list_${d.code}`);
        skeleton.find('.develop_list_submit').text(ns_i18n.t('development')); // 개발

        skeleton.find('.develop_list_cancel').remove();

        dialog.cont_obj.technique_cache_wrap.append(skeleton);

        ns_button.buttons[`technique_list_${d.code}`] = new nsButtonSet(`technique_list_${d.code}`, 'button_small_1', 'build_Technique', { base_class: ns_button.buttons.build_Technique_devel });
        ns_button.buttons[`technique_list_information_${d.code}`] = new nsButtonSet(`technique_list_information_${d.code}`, 'button_empty', 'build_Technique', { base_class: ns_button.buttons.build_Technique_information });
    }
}

ns_dialog.dialogs.build_Technique.drawRemote = function()
{
    let dialog = ns_dialog.dialogs.build_Technique;

    /*if (dialog.first_open) {
        return false;
    }*/

    dialog.cont_obj.content_concurr_body.hide();
    dialog.cont_obj.content_concurr_idle.hide();

    try {
        let d = ns_cs.d['bdic'][dialog.data.castle_pk];
        if ((typeof d != 'object' || !d.m_buil_pk && !d.level) || !ns_util.math(d.m_buil_pk).eq(200600)) {
            return;
        }
        dialog.buil_tech = false;
        dialog.cont_obj.content_concurr_idle.show();
        let queue_max = (! d?.level) ? 0 : ns_util.math(ns_cs.m.buil['200600']['level'][d.level]['variation_1']).minus(1).integer;

        let buil_tech_pk = null, time_pk = null, z = null;
        dialog.draw_remote_data = null;

        let time = Object.entries(ns_cs.d.time).filter(o => ns_util.isNumeric(o[0]) && o[1].queue_type === 'T').map(o => o[1]);
        if (time.length < 1) {
            return;
        }
        time_pk = time[0].time_pk;
        buil_tech_pk = time[0].queue_pk;
        z = time[0].description.split(' ');

        this.data.time_pk = time_pk;

        dialog.draw_remote_data = ns_util.toInteger(ns_cs.d.time[time_pk].end_dt_ut);
        ns_button.buttons.counter_speedup_build_Technique.obj.text(ns_i18n.t('encourage'));

        dialog.recruit_timer_end_dt_ut = ns_cs.d.time[time_pk].end_dt_ut;
        dialog.buil_tech = true;

        dialog.cont_obj.content_concurr_title.text(z[0] + ' ' + z[1]);

        dialog.cont_obj.content_concurr_body.show();
        dialog.cont_obj.content_concurr_idle.hide();
    } catch (e) {
        console.error(e);
    }
}

ns_dialog.dialogs.build_Technique.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_Technique.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_Technique.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_Technique;
    if (ns_cs.flag['new_time']) {
        ns_cs.flag['new_time'] = false;
        dialog.drawRemote();
        return;
    }

    // 현재 개발 중인 부분 시간 계산
    if (dialog.draw_remote_data) {
        let left = dialog.draw_remote_data - ns_timer.now();
        dialog.cont_obj.content_concurr_time.html((ns_util.math(left).lte(0)) ? ns_i18n.t('timer_progress') : ns_util.getCostsTime(left));
        if (ns_util.math(left).lt(ns_engine.cfg.free_speedup_time) && ns_util.math(left).gt(5)) {
            ns_button.buttons.counter_speedup_build_Technique.setEnable();
            ns_button.buttons.counter_speedup_build_Technique.obj.text(ns_i18n.t('immediately_complete'));
        } else if (ns_util.math(left).lte(5)) {
            ns_button.buttons.counter_speedup_build_Technique.setDisable();
        } else {
            ns_button.buttons.counter_speedup_build_Technique.setEnable();
            ns_button.buttons.counter_speedup_build_Technique.obj.text(ns_i18n.t('encourage'));
        }
    }

    dialog.timerHandlerButtonCondition();
}

ns_dialog.dialogs.build_Technique.timerHandlerButtonCondition = function()
{
    let dialog = ns_dialog.dialogs.build_Technique;
    let lord_tech = ns_cs.d.lord_tech;
    let tech = ns_cs.d.tech;

    for (let d of dialog.sorted) {
        if (ns_button.buttons['technique_list_' + d.code]) {
            let button = ns_button.buttons['technique_list_' + d.code];
            if (ns_util.math(ns_cs.d.tech[d.code].v).plus(1).gt(d.max_level)) {
                button.setDisable(); // 최고 렙 달성
            } else {
                let z = ns_cs.m.tech[d.code]['level'][ns_util.math(ns_cs.d.tech[d.code].v).plus(1).number].m_cond_pk;
                if (ns_check_condition.checkAll(z, dialog.data.castle_pk)) {
                    button.setEnable();
                } else {
                    button.setDisable();
                }

            }

            // 현재 개발 중인 부분 시간 계산
            if (dialog.draw_remote_data) {
                let left = dialog.draw_remote_data - ns_timer.now();
                if (left > 0) {
                    button.setDisable();
                }
            }

            // Lv, Ct
            dialog.cont_obj.technique_cache_wrap.find('.technique_lv_' + d.code).text(tech[d.code].v);
            // dialog.cont_obj.technique_cache_wrap.find('.technique_ct_' + d.code).text(lord_tech[d.code].v);
        }
    }
}

ns_button.buttons.build_Technique_close = new nsButtonSet('build_Technique_close', 'button_back', 'build_Technique', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_Technique_sub_close = new nsButtonSet('build_Technique_sub_close', 'button_full', 'build_Technique', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.build_Technique_close_all = new nsButtonSet('build_Technique_close_all', 'button_close_all', 'build_Technique', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_Technique = new nsButtonSet('build_desc_Technique', 'button_text_style_desc', 'build_Technique', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_move_Technique = new nsButtonSet('build_move_Technique', 'button_middle_2', 'build_Technique', { base_class: ns_button.buttons.build_move });
ns_button.buttons.build_cons_Technique = new nsButtonSet('build_cons_Technique', 'button_multi', 'build_Technique', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_Technique = new nsButtonSet('build_upgrade_Technique', 'button_hero_action', 'build_Technique', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_assign_Technique = new nsButtonSet('build_assign_Technique', 'button_full', 'build_Technique', { base_class: ns_button.buttons.build_assign });
ns_button.buttons.build_no_assign_Technique = new nsButtonSet('build_no_assign_Technique', 'button_full', 'build_Technique', { base_class: ns_button.buttons.build_assign });

ns_button.buttons.build_prev_Technique = new nsButtonSet('build_prev_Technique', 'button_multi_prev', 'build_Technique', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_Technique = new nsButtonSet('build_next_Technique', 'button_multi_next', 'build_Technique', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_Technique = new nsButtonSet('build_speedup_Technique', 'button_encourage', 'build_Technique', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_Technique = new nsButtonSet('build_cancel_Technique', 'button_build', 'build_Technique', { base_class: ns_button.buttons.build_cancel });

ns_button.buttons.counter_speedup_build_Technique = new nsButtonSet('concurr_speedup_build_Technique', 'button_small_1', 'build_Technique', { base_class: ns_button.buttons.concurr_speedup });
// ns_button.buttons.concurr_cancel_build_Technique = new nsButtonSet('concurr_cancel_build_Technique', 'btn_queue_cancel', 'build_Technique', {base_class:ns_button.buttons.concurr_cancel});

ns_button.buttons.build_Technique_devel = new nsButtonSet('build_Technique_devel', 'btn_empty', 'build_Technique');
ns_button.buttons.build_Technique_devel.mouseUp = function(_e)
{
    let code = _e.target.id.split('_list_').pop();
    ns_dialog.setDataOpen('tech_upgrade', { code: code });
}
ns_button.buttons.build_Technique_devel.mouseEnter = function(_e)
{
    let code = _e.target.id.split('_list_').pop();
    if (this.enable || ns_timer.timers[`infomation_effect_${code}`]) {
        return;
    }
    ns_timer.timers[`infomation_effect_${code}`] = new nsTimerSet(function ()
    {
        if (ns_timer.timers[`infomation_effect_${code}`]._loop_run_count > 4) {
            ns_button.buttons[`technique_list_information_${code}`].obj.removeCss('information_effect');
            ns_timer.timers[`infomation_effect_${code}`].clear();
            return;
        }
        ns_button.buttons[`technique_list_information_${code}`].obj.addCss('information_effect');
    }, 1000, false, 5);
    ns_timer.timers[`infomation_effect_${code}`].init();
}

ns_button.buttons.build_Technique_devel.mouseLeave = function(_e)
{
    let code = _e.target.id.split('_list_').pop();
    if (this.enable || !ns_timer.timers[`infomation_effect_${code}`]) {
        return;
    }
    if (ns_timer.timers[`infomation_effect_${code}`]._loop_run_count < 1) {
        ns_button.buttons[`technique_list_information_${code}`].obj.removeCss('information_effect');
        ns_timer.timers[`infomation_effect_${code}`].clear();
    }
}

ns_button.buttons.build_Technique_information = new nsButtonSet('build_Technique_information', 'btn_empty', 'build_Technique');
ns_button.buttons.build_Technique_information.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Technique;
    let code = _e.target.id.split('_information_').pop();
    let pk = ns_cs.m.tech[code].m_tech_pk;

    ns_dialog.setDataOpen('tech_information', { title: 'tech', type: code, m_pk: pk, castle_pk: dialog.data.castle_pk });
}

// 연구 업그레이드
ns_dialog.dialogs.tech_upgrade = new nsDialogSet('tech_upgrade', 'dialog_building', 'size-large', { do_close_all: false });
ns_dialog.dialogs.tech_upgrade.hero_select_data = {};

ns_dialog.dialogs.tech_upgrade.cacheContents = function()
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);

    this.cont_obj.item_image = new nsObject('.item_image', this.obj);
    this.cont_obj.item_description = new nsObject('.item_description', this.obj);

    this.cont_obj.target_level_description = new nsObject('.target_level_description', this.obj);
    this.cont_obj.content_build_time = new nsObject('.content_build_time', this.obj);

    this.cont_obj.tbody = new nsObject('table tbody', this.obj);

}

ns_dialog.dialogs.tech_upgrade.draw = function()
{
    this.hero_select_data = {};

    let code = this.data.code;
    let level = (ns_cs.d.tech?.[code].v) ? ns_util.toInteger(ns_cs.d.tech[code].v) : 0;
    let m = ns_cs.m.tech[code];
    let m_cond = ns_cs.m.cond[m.level[level+1].m_cond_pk];

    this.data.level = level;

    let message = ns_i18n.t('upgrade_progress_technique', [ns_i18n.t(`tech_title_${m.m_tech_pk}`), (level+1)]);

    this.cont_obj.content_title.text(message);

    this.hero_select_data.nosel_title = message;
    this.hero_select_data.nosel_desc = m.description;
    this.hero_select_data.data_time = m_cond.build_time;
    this.hero_select_data.limit_stat_type = m_cond.cmd_hero_stat_type ? code_set.hero_stat_type[m_cond.cmd_hero_stat_type] : 'leadership';
    this.hero_select_data.limit_stat_value = m_cond.cmd_hero_stat_value ? m_cond.cmd_hero_stat_value : 1;

    this.cont_obj.item_image.addCss(`technique_image_${code}`);
    this.cont_obj.item_description.html(m.description + '<br />' + m.description_detail);
    this.cont_obj.target_level_description.html(m['level'][(level+1)].description_effect);

    this.cont_obj.content_build_time.text(ns_util.getCostsTime(m_cond.build_time));

    // 즉시연구 버튼
    let need_qbig = ns_util.getNeedQbig(m_cond.build_time);
    let div = document.createElement('div');
    div.classList.add('text_qbig_amount');
    // div.setAttribute('style', 'color:#fff;background-position-y:1px;');
    div.innerHTML = need_qbig;

    ns_button.buttons.tech_upgrade_submit_now.obj.html(ns_i18n.t('technique_immediately_cash', [div.outerHTML]));
}

ns_dialog.dialogs.tech_upgrade.callback = function(_data)
{
    let dialog = ns_dialog.dialogs.tech_upgrade;

    let post_data = {};
    post_data['code'] = dialog.data.code;
    post_data['hero_pk'] = _data;
    post_data['in_cast_pk'] = ns_dialog.dialogs.build_Technique.data.castle_pk;

    ns_xhr.post('/api/technique/upgrade', post_data, function (_data, _status) {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_dialog.close('tech_upgrade');

        // counsel
        ns_dialog.setDataOpen('counsel', { type: 'action', counsel_type: 'techn', hero_pk: post_data['hero_pk'], code: post_data['code'], cost: _data.build_time });

        // 리스트 갱신
        if (ns_dialog.dialogs.build_Technique.visible) {
            ns_dialog.dialogs.build_Technique.drawRemote();
        }
    }, { useProgress: true });
};

ns_dialog.dialogs.tech_upgrade.techNow = function()
{
    let dialog = ns_dialog.dialogs.tech_upgrade;

    let post_data = {};
    post_data['code'] = dialog.data.code;
    post_data['in_cast_pk'] = ns_dialog.dialogs.build_Technique.data.castle_pk;

    ns_xhr.post('/api/technique/now', post_data, function (_data, _status) {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_dialog.close('tech_upgrade');
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_technique_immediately_finish'));

        // 리스트 갱신
        if (ns_dialog.dialogs.build_Technique.visible) {
            ns_dialog.dialogs.build_Technique.drawRemote();
        }
    }, { useProgress: true });
};

ns_dialog.dialogs.tech_upgrade.timerHandler = function ()
{
    let dialog = ns_dialog.dialogs.tech_upgrade;
    let timer_id = 'build_upgrade_real';
    ns_timer.timers[timer_id] = new nsTimerSet(dialog.timerHandlerReal, 1000, true);
    ns_timer.timers[timer_id].init();
    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.tech_upgrade.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.tech_upgrade;

    let m = ns_cs.m.tech[dialog.data.code];
    let m_cond = ns_cs.m.cond[m.level[dialog.data.level+1].m_cond_pk];

    // condition
    dialog.cont_obj.tbody.empty();
    ns_check_condition.drawList(m_cond.m_cond_pk, ns_dialog.dialogs.build_Technique.data.castle_pk, dialog.cont_obj.tbody, true);

    ns_button.buttons.tech_upgrade_submit_now.setDisable();
    if (ns_check_condition.checkAll(m_cond.m_cond_pk, ns_cs.getCastlePk('I', '200600'))) {
        ns_button.buttons.tech_upgrade_submit.setEnable();
        ns_button.buttons.tech_upgrade_submit_auto.setEnable();
        if (ns_util.math(ns_cs.d.cash.qbig.v).gte(ns_util.getNeedQbig(m_cond.build_time))) {
            ns_button.buttons.tech_upgrade_submit_now.setEnable();
        }
    } else {
        ns_button.buttons.tech_upgrade_submit_now.setDisable();
        ns_button.buttons.tech_upgrade_submit_now.setDisable();
    }
}

/* ************************************************** */

ns_button.buttons.tech_upgrade_close = new nsButtonSet('tech_upgrade_close', 'button_back', 'tech_upgrade', { base_class: ns_button.buttons.common_close });
ns_button.buttons.tech_upgrade_sub_close = new nsButtonSet('tech_upgrade_sub_close', 'button_full', 'tech_upgrade', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.tech_upgrade_close_all = new nsButtonSet('tech_upgrade_close_all', 'button_closeAll', 'tech_upgrade', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.tech_upgrade_submit = new nsButtonSet('tech_upgrade_submit', 'button_special', 'tech_upgrade');
ns_button.buttons.tech_upgrade_submit.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.tech_upgrade;
    let action = 'techn';

    dialog.hero_select_data.type = action;
    dialog.hero_select_data.sort_stat_type = code_set.hero_stat_type[ns_cs.m.cmd[action].sort_hero_stat_type];
    dialog.hero_select_data.selector_use = true;
    dialog.hero_select_data.do_callback = ns_dialog.dialogs.tech_upgrade.callback;

    ns_dialog.setDataOpen('hero_select', dialog.hero_select_data);
};

ns_button.buttons.tech_upgrade_submit_auto = new nsButtonSet('tech_upgrade_submit_auto', 'button_special', 'tech_upgrade');
ns_button.buttons.tech_upgrade_submit_auto.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.tech_upgrade;

    dialog.hero_select_data.auto = true;
    ns_button.buttons.tech_upgrade_submit.mouseUp();
};


ns_button.buttons.tech_upgrade_submit_now = new nsButtonSet('tech_upgrade_submit_now', 'button_special', 'tech_upgrade');
ns_button.buttons.tech_upgrade_submit_now.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.tech_upgrade;

    dialog.techNow();
};
