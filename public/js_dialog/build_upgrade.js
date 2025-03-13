// 다이얼로그
ns_dialog.dialogs.build_upgrade = new nsDialogSet('build_upgrade', 'dialog_building', 'size-large');
ns_dialog.dialogs.build_upgrade.cacheContents = function()
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);
    this.cont_obj.target_image = new nsObject('.target_image', this.obj);
    this.cont_obj.target_description = new nsObject('.target_description', this.obj);
    this.cont_obj.target_level_description = new nsObject('.target_level_description', this.obj);
    this.cont_obj.content_build_time = new nsObject('.content_build_time', this.obj);

    this.cont_obj.tbody = new nsObject('.content_table tbody', this.obj);
}

ns_dialog.dialogs.build_upgrade.draw = function (_e)
{
    this.hero_select_data = {};
    let m_buil_pk = this.data.m_buil_pk,
        castle_pk = this.data.castle_pk;
    let level = ns_cs.d[this.data.castle_type]?.[castle_pk]?.level ?? 0;
    this.data.level = level;

    let m = ns_cs.m.buil[m_buil_pk];
    let m_cond = ns_cs.m.cond[m.level[level+1].m_cond_pk];

    let title = ns_i18n.t(`build_title_${m_buil_pk}`), is_upgrade = ns_util.math(level).gt(0);
    title += ' Lv.' + (level + 1);
    title += (! is_upgrade) ? ` ${ns_i18n.t('construction')}` : ` ${ns_i18n.t('upgrade')}`;

    this.cont_obj.content_title.text(title);

    this.hero_select_data.nosel_title = title;
    this.hero_select_data.nosel_desc = m.description_detail;
    this.hero_select_data.data_time = m_cond.build_time;
    this.hero_select_data.limit_stat_type = m_cond.cmd_hero_stat_type ? code_set.hero_stat_type[m_cond.cmd_hero_stat_type] : code_set.hero_stat_type['L'];
    this.hero_select_data.limit_stat_value = m_cond.cmd_hero_stat_value ? m_cond.cmd_hero_stat_value : 1;

    this.cont_obj.target_image.addCss(`building_${m_buil_pk}`);
    this.cont_obj.target_description.text(ns_i18n.t(`build_description_detail_${m_buil_pk}`)); // m.description_detail
    this.cont_obj.target_level_description.text(m['level'][(level+1)].variation_description);
    this.cont_obj.content_build_time.html(ns_util.getCostsTime(m_cond.build_time));

    // 즉시건설 버튼
    let need_qbig = ns_util.getNeedQbig(m_cond.build_time);

    let div = document.createElement('div');
    div.setAttribute('class', 'text_qbig_amount');
    div.innerHTML = need_qbig;

    ns_button.buttons.build_upgrade_submit_now.obj.html(ns_i18n.t('construction_immediately_cash', [div.outerHTML]));
}

ns_dialog.dialogs.build_upgrade.erase = function ()
{
    this.cont_obj.target_image.removeCss(`building_${this.data.m_buil_pk}`);
}

ns_dialog.dialogs.build_upgrade.callback = function(_hero_pk)
{
    let dialog = ns_dialog.dialogs.build_upgrade;
    let data = dialog.data;

    let castle_pk = data.castle_pk;
    if (! castle_pk) {
        castle_pk = ns_cs.getEmptyTile(ns_cs.m.buil[data.m_buil_pk]['type']);
    }

    let post_data = {};
    post_data['m_buil_pk'] = data.m_buil_pk;
    post_data['hero_pk'] = _hero_pk;
    post_data['castle_pk'] = castle_pk;
    post_data['castle_type'] = data.castle_type;
    post_data['action'] = (data.level === 0) ? 'build' : 'upgrade';

    ns_xhr.post(`/api/build`, post_data, (_data, _status) => {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_dialog.closeAll();


        let dialog_name = 'build_' + ns_cs.m.buil[data.m_buil_pk].alias;
        ns_dialog.setDataOpen(dialog_name, { castle_pk: data.castle_pk, castle_type: data.castle_type });

        ns_dialog.setDataOpen('counsel', { type: 'action', counsel_type: 'const', hero_pk: post_data['hero_pk'], cost: _data.build_time, except_dialog: [dialog_name] });
    }, { useProgress: true });
};

ns_dialog.dialogs.build_upgrade.buildNow = function (_e)
{
    let dialog = ns_dialog.dialogs.build_upgrade;
    let dlg_name = 'build_' + ns_cs.m.buil[dialog.data.m_buil_pk].alias;
    let build_dialog = ns_dialog.dialogs[dlg_name];

    let castle_pk = dialog.data.castle_pk;
    if (! castle_pk) {
        castle_pk = ns_cs.getEmptyTile(ns_cs.m.buil[dialog.data.m_buil_pk]['type']);
    }
    let post_data = {};
    post_data['m_buil_pk'] = dialog.data.m_buil_pk;
    post_data['castle_pk'] = castle_pk;
    ns_xhr.post('/api/build/now', post_data, function (_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];
        ns_dialog.close('build_construct');
        ns_dialog.close('build_upgrade');
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_construction_immediately_finish'));
        if (build_dialog.visible) {
            build_dialog.draw();
        }
    }, { useProgress: true });
}

ns_dialog.dialogs.build_upgrade.timerHandler = function ()
{
    let dialog = ns_dialog.dialogs.build_upgrade;
    let timer_id = 'build_upgrade_real';
    ns_timer.timers[timer_id] = new nsTimerSet(dialog.timerHandlerReal, 1000, true);
    ns_timer.timers[timer_id].init();
    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_upgrade.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_upgrade;

    let m = ns_cs.m.buil[dialog.data.m_buil_pk];
    let m_cond = ns_cs.m.cond[m.level[dialog.data.level+1].m_cond_pk];

    dialog.cont_obj.tbody.empty();
    ns_check_condition.drawList(m_cond.m_cond_pk, dialog.data.castle_pk, dialog.cont_obj.tbody);

    ns_button.buttons.build_upgrade_submit_now.setDisable();

    if (ns_check_condition.checkAll(m_cond.m_cond_pk, 3)) {
        ns_button.buttons.build_upgrade_submit.setEnable();
        ns_button.buttons.build_upgrade_submit_auto.setEnable();
        if (ns_util.math(ns_cs.d.cash.qbig.v).gte(ns_util.getNeedQbig(m_cond.build_time))) {
            ns_button.buttons.build_upgrade_submit_now.setEnable();
        }
    } else {
        ns_button.buttons.build_upgrade_submit.setDisable();
        ns_button.buttons.build_upgrade_submit_auto.setDisable();
    }
}

/* buttons */
ns_button.buttons.build_upgrade_close = new nsButtonSet('build_upgrade_close', 'button_back', 'build_upgrade', { base_class: ns_button.buttons.common_close });
ns_button.buttons.build_upgrade_sub_close = new nsButtonSet('build_upgrade_sub_close', 'button_full', 'build_upgrade', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.build_upgrade_close_all = new nsButtonSet('build_upgrade_close_all', 'button_close_all', 'build_upgrade', { base_class:ns_button.buttons.common_close_all });

ns_button.buttons.build_upgrade_submit = new nsButtonSet('build_upgrade_submit', 'button_default', 'build_upgrade');
ns_button.buttons.build_upgrade_submit.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_upgrade;
    let action = 'const';

    dialog.hero_select_data.type = action;
    dialog.hero_select_data.sort_stat_type = code_set['hero_stat_type'][ns_cs.m.cmd[action].sort_hero_stat_type];
    dialog.hero_select_data.selector_use = true;
    dialog.hero_select_data.do_callback = dialog.callback;
    dialog.hero_select_data.m_buil_pk = dialog.data.m_buil_pk;
    dialog.hero_select_data.castle_pk = dialog.data.castle_pk;
    dialog.hero_select_data.castle_type = dialog.data.castle_type;

    // 대기열 체크
    let is_used_ext_queue = false;
    let build_curr_count = 0;
    let build_max_count = 3;
    for (let [k, d] of Object.entries(ns_cs.d.time)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        if (typeof d.description != 'undefined' && String(d.description).substring(7) === 'queue' && d.status === 'P') {
            is_used_ext_queue = true;
        }
        if (d.queue_type === 'C' && (['U', 'D'].includes(d.queue_action))) {
            build_curr_count++;
        }
    }
    let max = is_used_ext_queue ? build_max_count : 1;

    if (build_curr_count < max) {
        if (ns_util.math(dialog.data.m_buil_pk).eq(200100) && ns_cs.d.bdic[1].level === 4) {
            // 업그레이드 하려는 건물이 대전이고 레벨이 4라면
            // let is_beginner_truce = false;
            let is_beginner_truce = Object.values(ns_cs.d.time).some(o => o?.queue_pk && ns_util.math(o?.queue_pk).eq(500015) && o.queue_action === 'B' && o.queue_type === 'D');
            if (is_beginner_truce === true) {
                ns_dialog.setDataOpen('confirm', {
                    text: ns_i18n.t('msg_end_beginner_truce_warning'),
                    okFunc: function ()
                    {
                        ns_dialog.setDataOpen('hero_select', dialog.hero_select_data);
                    }, noFunc: () => {}, evt: _e });
            } else {
                ns_dialog.setDataOpen('hero_select', dialog.hero_select_data);
            }
        } else {
            ns_dialog.setDataOpen('hero_select', dialog.hero_select_data);
        }
    } else {
        if (is_used_ext_queue) {
            // 건설 제한 초과 메시지
            ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_construction_max_queue_error', [max]) });
        } else {
            // 건설은 기본적으로 1개가 가능하며<br />"건설허가서" 아이템을 사용하여 건설을<br />동시에 3개까지 진행할 수 있습니다.<br /><br />"건설허가서" 를 사용하시겠습니까?
            ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_construction_queue_item_confirm'), okFunc: () => {
                    ns_dialog.setDataOpen('item_use', { m_item_pk: 500102 });
                }
            });
        }
    }
};

ns_button.buttons.build_upgrade_submit_auto = new nsButtonSet('build_upgrade_submit_auto', 'button_default', 'build_upgrade');
ns_button.buttons.build_upgrade_submit_auto.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_upgrade;
    dialog.hero_select_data.auto = true;
    ns_button.buttons.build_upgrade_submit.mouseUp();
};

ns_button.buttons.build_upgrade_submit_now = new nsButtonSet('build_upgrade_submit_now', 'button_default', 'build_upgrade');
ns_button.buttons.build_upgrade_submit_now.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_upgrade;
    dialog.hero_select_data = {};
    dialog.hero_select_data.qbig = true;

    if (dialog.data.m_buil_pk === 200100 && ns_cs.d.bdic[1].level === 4) {
        // 업그레이드 하려는 건물이 대전이고 레벨이 4라면
        // let is_beginner_truce = false;
        let is_beginner_truce = Object.values(ns_cs.d.time).some(o => o?.queue_pk && ns_util.math(o?.queue_pk).eq(500015) && o.queue_action === 'B' && o.queue_type === 'D');
        if (is_beginner_truce === true) {
            ns_dialog.setDataOpen('confirm', {
                text: ns_i18n.t('msg_end_beginner_truce_warning'),
                okFunc: function ()
                {
                    dialog.buildNow();
                }, noFunc: () => {}, evt: _e });
        }
    } else {
        dialog.buildNow();
    }
};





