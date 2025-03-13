// 다이얼로그
ns_dialog.dialogs.build_CastleWall = new nsDialogSet('build_CastleWall', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });
ns_dialog.dialogs.build_CastleWall.current_tab = null;
ns_dialog.dialogs.build_CastleWall.install_timer_end_dt_ut = null;
ns_dialog.dialogs.build_CastleWall.sorted = [];
ns_dialog.dialogs.build_CastleWall.fighting_spirit = 0;
ns_dialog.dialogs.build_CastleWall.alli_army = 0;
ns_dialog.dialogs.build_CastleWall.total_army = 0;
ns_dialog.dialogs.build_CastleWall.current_toggle = false;

ns_dialog.dialogs.build_CastleWall.cacheContents = function(_recursive)
{
    ns_cs.m.buil['CastleWall'].yn_hero_assign = 'N'; // 성벽만 배속 형태가 다르기 때문 덮어씌우기
    if (this.base_class && !_recursive) {
        this.base_class.cacheContents.call(this, true);
    }

    this.cont_obj.build_CastleWall_tab_assign = new nsObject('.build_CastleWall_tab_assign', this.obj);
    this.cont_obj.build_CastleWall_tab_fortification = new nsObject('.build_CastleWall_tab_fortification', this.obj);

    this.cont_obj.content_troop_fighting_spirit = new nsObject('.content_troop_fighting_spirit', this.obj);
    this.cont_obj.content_troop_total_army = new nsObject('.content_troop_total_army', this.obj);
    this.cont_obj.content_troop_my_army = new nsObject('.content_troop_my_army', this.obj);
    this.cont_obj.content_troop_alli_army = new nsObject('.content_troop_alli_army', this.obj);

    this.cont_obj.captain_card_wrap = new nsObject('.captain_card_wrap', this.obj);
    this.cont_obj.director_card_wrap = new nsObject('.director_card_wrap', this.obj);
    this.cont_obj.staff_card_wrap = new nsObject('.staff_card_wrap', this.obj);

    let m = ns_cs.m.buil.CastleWall;

    this.cont_obj.captain_card_wrap.find('.build_description_assign').text(ns_i18n.t(`build_description_assign_${m.m_buil_pk}`));
    this.cont_obj.captain_card_wrap.find('.build_description_assign_footnote').html(ns_i18n.t('build_hero_assign_effect_notice'));

    this.cont_obj.director_card_wrap.find('.build_description_assign').text(ns_i18n.t('build_castle_wall_assign_director'));
    this.cont_obj.director_card_wrap.find('.build_description_assign_footnote').html(ns_i18n.t('build_hero_assign_effect_notice'));

    this.cont_obj.staff_card_wrap.find('.build_description_assign').text(ns_i18n.t('build_castle_wall_assign_staff'));
    this.cont_obj.staff_card_wrap.find('.build_description_assign_footnote').html(ns_i18n.t('build_hero_assign_effect_notice'));

    // 방어시설 설치
    this.cont_obj.content_concurr_body = new nsObject('.content_concurr_body', this.obj);
    this.cont_obj.content_concurr_idle = new nsObject('.content_concurr_idle', this.obj);

    this.cont_obj.content_concurr_title = new nsObject('.content_concurr_title', this.obj);
    this.cont_obj.content_concurr_time = new nsObject('.content_concurr_time', this.obj);

    // this.cont_obj.cont_queue_list = new nsObject('.cont_queue_list'); // TODO 일단 Queue 는 사용하지 않음. 차후 확인 필요.
    // this.cont_obj.cont_queue = this.cont_obj.find('.cont_queue');
    // this.cont_obj.cont_queue_list_skel = $('#skeleton_cont_queue_list');

    // 한번만 그려줌
    this.cont_obj.fortification_cache_wrap = new nsObject('.fortification_cache_wrap', this.obj);
    this.cont_obj.develop_list_skeleton = new nsObject('#develop_list_skeleton');
    this.drawList();

    ns_cs.flag['new_time'] = false;
}

ns_dialog.dialogs.build_CastleWall.draw = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.draw.call(this, true);
    }

    let hero_pk = null;

    // 주장
    hero_pk = ns_cs.d.bdic[2].assign_hero_pk ?? 0;
    if (hero_pk && hero_pk > 0) {
        this.cont_obj.captain_card_wrap.find('.content_hero_detail').empty().append(ns_hero.cardDetailDraw(hero_pk, 'N'));

        let effects = ns_hero.getEffect(hero_pk, null, 'assign', ns_cs.m.buil['CastleWall'].m_buil_pk);
        this.cont_obj.captain_card_wrap.find('.content_applied_capa').html((! effects?.capa) ? '-' : effects.capa);
        this.cont_obj.captain_card_wrap.find('.content_applied_skill').html((! effects?.skill) ? '-' : effects.skill);

        this.cont_obj.captain_card_wrap.find('.content_card_empty').hide();
        this.cont_obj.captain_card_wrap.find('.content_card').show();
    } else {
        this.cont_obj.captain_card_wrap.find('.content_applied_capa').text('-');
        this.cont_obj.captain_card_wrap.find('.content_applied_skill').text('-');

        this.cont_obj.captain_card_wrap.find('.content_card_empty').show();
        this.cont_obj.captain_card_wrap.find('.content_card').hide();
    }

    // 부장
    hero_pk = ns_cs.getTerritoryInfo('wall_director_hero_pk');
    if (hero_pk && hero_pk > 0) {
        this.cont_obj.director_card_wrap.find('.content_hero_detail').empty().append(ns_hero.cardDetailDraw(hero_pk, 'N'));

        let effects = ns_hero.getEffect(hero_pk, null, 'assign_wall', 'director');
        this.cont_obj.director_card_wrap.find('.content_applied_capa').html((! effects?.capa) ? '-' : effects.capa);
        this.cont_obj.director_card_wrap.find('.content_applied_skill').html((! effects?.skill) ? '-' : effects.skill);

        this.cont_obj.director_card_wrap.find('.content_card_empty').hide();
        this.cont_obj.director_card_wrap.find('.content_card').show();
    } else {
        this.cont_obj.director_card_wrap.find('.content_applied_capa').text('-');
        this.cont_obj.director_card_wrap.find('.content_applied_skill').text('-');

        this.cont_obj.director_card_wrap.find('.content_card_empty').show();
        this.cont_obj.director_card_wrap.find('.content_card').hide();
    }

    // 참모
    hero_pk = ns_cs.getTerritoryInfo('wall_staff_hero_pk');
    if (hero_pk && hero_pk > 0) {
        this.cont_obj.staff_card_wrap.find('.content_hero_detail').empty().append(ns_hero.cardDetailDraw(hero_pk, 'N'));

        let effects = ns_hero.getEffect(hero_pk, null, 'assign_wall', 'staff');
        this.cont_obj.staff_card_wrap.find('.content_applied_capa').html((! effects?.capa) ? '-' : effects.capa);
        this.cont_obj.staff_card_wrap.find('.content_applied_skill').html((! effects?.skill) ? '-' : effects.skill);

        this.cont_obj.staff_card_wrap.find('.content_card_empty').hide();
        this.cont_obj.staff_card_wrap.find('.content_card').show();
    } else {
        this.cont_obj.staff_card_wrap.find('.content_applied_capa').text('-');
        this.cont_obj.staff_card_wrap.find('.content_applied_skill').text('-');

        this.cont_obj.staff_card_wrap.find('.content_card_empty').show();
        this.cont_obj.staff_card_wrap.find('.content_card').hide();
    }

    ns_button.toggleGroupSingle(ns_button.buttons.build_CastleWall_tab_assign);
    this.drawTab();
}

ns_dialog.dialogs.build_CastleWall.drawList = function()
{
    let dialog = ns_dialog.dialogs.build_CastleWall;

    // 목록 리스트 캐싱
    dialog.sorted = [];
    for (let [k, d] of Object.entries(ns_cs.m.fort)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        dialog.sorted.push(d);
    }

    if (dialog.sorted.length > 1) {
        dialog.sorted = ns_util.arraySort(dialog.sorted, -1, 'm_fort_pk');
    }

    // 목록 그리기
    dialog.cont_obj.fortification_cache_wrap.empty();
    for (let d of dialog.sorted) {
        let skeleton = dialog.cont_obj.develop_list_skeleton.clone();

        skeleton.find('.develop_list_title').text(d.title);
        skeleton.find('.develop_list_desc').addCss(d.code);
        skeleton.find('.develop_list_desc').text(0);

        skeleton.find('.develop_list_image').setAttribute('id', `ns_button_fort_list_information_${d.code}`);
        skeleton.find('.develop_list_image').addCss(`fort_image_${d.code}`);

        skeleton.find('.develop_list_submit').setAttribute('id', `ns_button_fort_list_${d.code}`);
        skeleton.find('.develop_list_submit').text(ns_i18n.t('set_up'));

        skeleton.find('.develop_list_cancel').setAttribute('id', `ns_button_fort_list_disperse_${d.code}`);
        skeleton.find('.develop_list_cancel').text(ns_i18n.t('dismantle'));

        dialog.cont_obj.fortification_cache_wrap.append(skeleton);

        ns_button.buttons[`fort_list_${d.code}`] = new nsButtonSet(`fort_list_${d.code}`, 'button_small_1', 'build_CastleWall', { base_class: ns_button.buttons.fort_list_installation });
        ns_button.buttons[`fort_list_information_${d.code}`] = new nsButtonSet(`fort_list_information_${d.code}`, 'button_empty', 'build_CastleWall', { base_class: ns_button.buttons.build_CastleWall_fort_information });
        ns_button.buttons[`fort_list_disperse_${d.code}`] = new nsButtonSet(`fort_list_disperse_${d.code}`, 'button_small_2', 'build_CastleWall', { base_class: ns_button.buttons.fort_list_disperse });
    }
}

ns_dialog.dialogs.build_CastleWall.drawTab = function()
{
    let dialog = ns_dialog.dialogs.build_CastleWall;

    dialog.current_tab = ns_button.toggleGroupValue('build_CastleWall_tab')[0];

    let arr = dialog.current_tab.split('_');
    let tab = arr.pop();

    for (let _tab of ['assign', 'fortification']) {
        if (tab === _tab) {
            dialog.cont_obj[`build_CastleWall_tab_${_tab}`].show();
        } else {
            dialog.cont_obj[`build_CastleWall_tab_${_tab}`].hide();
        }
    }

    dialog.drawRemote();
}

ns_dialog.dialogs.build_CastleWall.drawRemote = function(status)
{
    let dialog = ns_dialog.dialogs.build_CastleWall;

    try {
        let d = ns_cs.d['bdic'][dialog.data.castle_pk];
    } catch (e) {
        return;
    }

    dialog.current_tab = ns_button.toggleGroupValue('build_CastleWall_tab')[0];

    let arr = dialog.current_tab.split('_');
    let tab = arr.pop();

    if (tab === 'assign') { // 배속 설정
        let post_data = { };
        post_data['in_cast_pk'] = dialog.data.castle_pk;
        ns_xhr.post('/api/fort/current', post_data, (_data) => {
            if(! ns_xhr.returnCheck(_data)) {
                return;
            }
            _data = _data['ns_xhr_return']['add_data'];
            this.cont_obj.content_troop_fighting_spirit.text(ns_util.numberFormat(_data.army.fightingSpirit));
            this.cont_obj.content_troop_total_army.text(ns_util.numberFormat(_data.army.total_army));
            this.cont_obj.content_troop_my_army.text(ns_util.numberFormat(_data.army.my_army));
            this.cont_obj.content_troop_alli_army.text(ns_util.numberFormat(_data.army.alli));
        });
    } else if (tab === 'fortification') { // 방어시설
        dialog.buil_fort = false;
        dialog.cont_obj.content_concurr_body.hide();
        dialog.cont_obj.content_concurr_idle.show();

        let buil_fort_pk = null, time_pk = null, z = null;
        dialog.draw_remote_data = null;

        // TODO 굳이 for로 찾아야하나;; 일단 처리..
        for (let [k, d] of Object.entries(ns_cs.d.time)) {
            if (! ns_util.isNumeric(k) || d.queue_type !== 'F') {
                continue;
            }
            buil_fort_pk = d.queue_pk;
            time_pk = k;
            z = d.description.split(' ');
        }

        if (! buil_fort_pk) {
            return;
        }

        try {
            dialog.draw_remote_data = ns_util.toInteger(ns_cs.d.time[time_pk].end_dt_ut);
            dialog.install_timer_end_dt_ut = ns_util.toInteger(ns_cs.d.time[time_pk].end_dt_ut);
            dialog.buil_fort = true;

            this.cont_obj.content_concurr_title.text(z[0] + ' ' + z[1]);

            dialog.cont_obj.content_concurr_body.show();
            dialog.cont_obj.content_concurr_idle.hide();
        } catch (e) {
            console.error(e);
        }
    }
}

ns_dialog.dialogs.build_CastleWall.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }
    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_CastleWall.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_CastleWall.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_CastleWall;

    if (ns_cs.flag['new_time']) {
        ns_cs.flag['new_time'] = false;
        dialog.drawTab();
        return;
    }

    // 현재 보유 중인 병과별 병력 수
    for (let [k, d] of Object.entries(ns_cs.m.fort)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        dialog.cont_obj.fortification_cache_wrap.find(`.${d.code}`).text(ns_cs.d.fort[d.code].v);
    }

    // 현재 개발 중인 부분 시간 계산
    if (dialog.draw_remote_data) {
        let left = dialog.draw_remote_data - ns_timer.now();
        dialog.cont_obj.content_concurr_time.text((left <= 0) ? ns_i18n.t('in_progress') : ns_util.getCostsTime(left));
    }

    dialog.timerHandlerButtonCondition();
}

ns_dialog.dialogs.build_CastleWall.timerHandlerButtonCondition = function()
{
    let dialog = ns_dialog.dialogs.build_CastleWall;
    // 버튼 처리
    for (let d of dialog.sorted) {
        if (ns_button.buttons[`fort_list_${d.code}`]) {
            if (ns_check_condition.checkAll(d.m_cond_pk, dialog.data.castle_pk)) {
                ns_button.buttons[`fort_list_${d.code}`].setEnable();
            } else {
                ns_button.buttons[`fort_list_${d.code}`].setDisable();
            }
        }
        if (ns_button.buttons[`fort_list_disperse_${d.code}`]) {
            if (ns_cs.d.fort[d.code].v > 0) {
                ns_button.buttons[`fort_list_disperse_${d.code}`].setEnable();
            } else {
                ns_button.buttons[`fort_list_disperse_${d.code}`].setDisable();
            }
        }
    }
}

ns_button.buttons.build_CastleWall_close = new nsButtonSet('build_CastleWall_close', 'button_back', 'build_CastleWall', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_CastleWall_sub_close = new nsButtonSet('build_CastleWall_sub_close', 'button_full', 'build_CastleWall', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.build_CastleWall_close_all = new nsButtonSet('build_CastleWall_close_all', 'button_close_all', 'build_CastleWall', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_CastleWall = new nsButtonSet('build_desc_CastleWall', 'button_text_style_desc', 'build_CastleWall', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_cons_CastleWall = new nsButtonSet('build_cons_CastleWall', 'button_multi', 'build_CastleWall', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_CastleWall = new nsButtonSet('build_upgrade_CastleWall', 'button_hero_action', 'build_CastleWall', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_prev_CastleWall = new nsButtonSet('build_prev_CastleWall', 'button_multi_prev', 'build_CastleWall', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_CastleWall = new nsButtonSet('build_next_CastleWall', 'button_multi_next', 'build_CastleWall', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_CastleWall = new nsButtonSet('build_speedup_CastleWall', 'button_encourage', 'build_CastleWall', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_CastleWall = new nsButtonSet('build_cancel_CastleWall', 'button_build', 'build_CastleWall', { base_class: ns_button.buttons.build_cancel });

ns_button.buttons.build_CastleWall_assign_captain = new nsButtonSet('build_CastleWall_assign_captain', 'button_full', 'build_CastleWall');
ns_button.buttons.build_CastleWall_assign_captain.mouseUp = function(_e)
{
    let arr = this.tag_id.split('_');
    let dialog = ns_dialog.dialogs.build_CastleWall;
    let m = ns_cs.m.buil.CastleWall;
    let castle_pk = dialog.data.castle_pk;

    let d = ns_cs.d.bdic[castle_pk];

    let type_data = arr.pop();
    let sort_type = 'leadership';
    let prev_hero_pk = null;

    let hero_sel_data = {};
    hero_sel_data.type = (type_data === 'captain') ? 'assign' : 'assign_wall';
    hero_sel_data.type_data = (type_data === 'captain') ? m.m_buil_pk : type_data;
    hero_sel_data.nosel_desc = (type_data === 'captain') ? ns_i18n.t(`build_description_${m.m_buil_pk}`) : ns_i18n.t(`build_castle_wall_assign_${type_data}`);
    prev_hero_pk = (type_data === 'captain') ? d.assign_hero_pk : ns_cs.getTerritoryInfo('wall_' + type_data + '_hero_pk');
    if (type_data !== 'captain') {
        sort_type = (type_data === 'director') ? 'mil_force' : 'intellect';
    }

    hero_sel_data.nosel_title = '성벽 ' + code_set.troop_hero[type_data];

    if (prev_hero_pk) {
        hero_sel_data.prev_hero_pk = prev_hero_pk;
        hero_sel_data.prev_hero_undo = m.yn_hero_assign_required !== 'Y';
        hero_sel_data.selector_use = false;
    } else {
        hero_sel_data.selector_use = true;
    }

    hero_sel_data.sort_stat_type = sort_type;
    hero_sel_data.limit_stat_type = sort_type;
    hero_sel_data.limit_stat_value = 1;

    let post_data = {};
    post_data['castle_pk'] = castle_pk;
    if (type_data !== 'captain') {
        post_data['position'] = type_data;
    }

    hero_sel_data.do_callback = function(_data)
    {
        let api_url = (type_data === 'captain') ? 'assign' : 'assignWall';
        post_data['hero_pk'] = _data;
        ns_xhr.post(`/api/hero/${api_url}`, post_data, (_data) => {
            if(! ns_xhr.returnCheck(_data)) {
                return;
            }
            dialog.draw();
        }, { useProgress: true });
    };

    hero_sel_data.undo_callback = function(_data)
    {
        let api_url = (type_data === 'captain') ? 'unAssign' : 'unAssignWall';
        ns_xhr.post(`/api/hero/${api_url}`, post_data, (_data) => {
            if(! ns_xhr.returnCheck(_data)) {
                return;
            }
            dialog.draw();
        }, { useProgress: true });
    };

    ns_dialog.setDataOpen('hero_select', hero_sel_data);
}

ns_button.buttons.build_CastleWall_assign_director = new nsButtonSet('build_CastleWall_assign_director', 'button_full', 'build_CastleWall', {base_class:ns_button.buttons.build_CastleWall_assign_captain});
ns_button.buttons.build_CastleWall_assign_staff = new nsButtonSet('build_CastleWall_assign_staff', 'button_full', 'build_CastleWall', {base_class:ns_button.buttons.build_CastleWall_assign_captain});

ns_button.buttons.build_CastleWall_no_assign_captain = new nsButtonSet('build_CastleWall_no_assign_captain', 'button_hero_no_assign_L', 'build_CastleWall', { base_class: ns_button.buttons.build_CastleWall_assign_captain });
ns_button.buttons.build_CastleWall_no_assign_director = new nsButtonSet('build_CastleWall_no_assign_director', 'button_hero_no_assign_M', 'build_CastleWall', { base_class: ns_button.buttons.build_CastleWall_assign_captain });
ns_button.buttons.build_CastleWall_no_assign_staff = new nsButtonSet('build_CastleWall_no_assign_staff', 'button_hero_no_assign_I', 'build_CastleWall', { base_class: ns_button.buttons.build_CastleWall_assign_captain });

ns_button.buttons.build_CastleWall_tab_assign = new nsButtonSet('build_CastleWall_tab_assign', 'button_tab', 'build_CastleWall', { toggle_group: 'build_CastleWall_tab' });
ns_button.buttons.build_CastleWall_tab_assign.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_CastleWall;

    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.drawTab();
}
ns_button.buttons.build_CastleWall_tab_fortification = new nsButtonSet('build_CastleWall_tab_fortification', 'button_tab', 'build_CastleWall', { base_class: ns_button.buttons.build_CastleWall_tab_assign, toggle_group: 'build_CastleWall_tab' });

ns_button.buttons.concurr_speedup_build_CastleWall = new nsButtonSet('concurr_speedup_build_CastleWall', 'button_small_1', 'build_CastleWall', {base_class:ns_button.buttons.concurr_speedup});
// ns_button.buttons.concurr_cancel_build_CastleWall = new nsButtonSet('concurr_cancel_build_CastleWall', 'btn_queue_cancel', 'build_CastleWall', {base_class:ns_button.buttons.concurr_cancel});

ns_button.buttons.build_CastleWall_fort_information = new nsButtonSet('build_CastleWall_fort_information', 'button_empty', 'build_CastleWall');
ns_button.buttons.build_CastleWall_fort_information.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_CastleWall;
    let arr = this.tag_id.split('_');
    let code = arr[arr.length - 1];

    let pk = ns_cs.m.fort[code].m_fort_pk;

    ns_dialog.setDataOpen('information', { title: 'fort', type: code, m_pk: pk, castle_pk: dialog.data?.castle_pk ?? 0 });
}

ns_button.buttons.fort_list_installation = new nsButtonSet('fort_list_installation', 'button_empty', 'build_CastleWall');
ns_button.buttons.fort_list_installation.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_CastleWall;
    let code = _e.target.id.split('_fort_list_').pop();
    let pk = ns_cs.m.fort[code].m_fort_pk;

    ns_dialog.setDataOpen('build_CastleWall_fort_installation', { type: code, m_pk: pk, castle_pk: dialog.data.castle_pk });
}
ns_button.buttons.fort_list_installation.mouseEnter = function(_e)
{
    let code = _e.target.id.split('_fort_list_').pop();
    if (this.enable || ns_timer.timers[`infomation_effect_${code}`]) {
        return;
    }
    ns_timer.timers[`infomation_effect_${code}`] = new nsTimerSet(function ()
    {
        if (ns_timer.timers[`infomation_effect_${code}`]._loop_run_count > 4) {
            ns_button.buttons[`fort_list_information_${code}`].obj.removeCss('information_effect');
            ns_timer.timers[`infomation_effect_${code}`].clear();
            return;
        }
        ns_button.buttons[`fort_list_information_${code}`].obj.addCss('information_effect');
    }, 1000, false, 5);
    ns_timer.timers[`infomation_effect_${code}`].init();
}

ns_button.buttons.fort_list_installation.mouseLeave = function(_e)
{
    let code = _e.target.id.split('_fort_list_').pop();
    if (this.enable || !ns_timer.timers[`infomation_effect_${code}`]) {
        return;
    }
    if (ns_timer.timers[`infomation_effect_${code}`]._loop_run_count < 1) {
        ns_button.buttons[`fort_list_information_${code}`].obj.removeCss('information_effect');
        ns_timer.timers[`infomation_effect_${code}`].clear();
    }
}

ns_button.buttons.fort_list_disperse = new nsButtonSet('fort_list_disperse', 'button_empty', 'build_CastleWall');
ns_button.buttons.fort_list_disperse.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_CastleWall;
    let arr = this.tag_id.split('_');
    let code = arr[arr.length - 1];
    let pk = ns_cs.m.fort[code].m_fort_pk;

    ns_dialog.setDataOpen('build_CastleWall_fort_disperse', { type: code, m_pk: pk, castle_pk: dialog.data.castle_pk });
}

ns_dialog.dialogs.build_CastleWall_fort_installation = new nsDialogSet('build_CastleWall_fort_installation', 'dialog_building', 'size-large', { do_close_all: false });
ns_dialog.dialogs.build_CastleWall_fort_installation.devel_amount = 0;
ns_dialog.dialogs.build_CastleWall_fort_installation.condition = {};

ns_dialog.dialogs.build_CastleWall_fort_installation.cacheContents = function()
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);
    this.cont_obj.develop_image = new nsObject('.develop_image', this.obj);
    this.cont_obj.develop_description = new nsObject('.develop_description', this.obj);

    this.cont_obj.content_build_time = new nsObject('.content_build_time', this.obj);

    this.cont_obj.table_fort_installation = new nsObject('.table_fort_installation', this.obj);

    this.cont_obj.develop_current = new nsObject('.develop_current', this.obj);
    this.cont_obj.develop_max = new nsObject('.develop_max', this.obj);

    this.cont_obj.fort_installation_amount = new nsObject('.amount_field', this.obj);
    this.cont_obj.fort_installation_amount_slider =  new nsObject('input[name="develop_slider"]', this.obj);
    this.cont_obj.fort_installation_amount_slider.setEvent('input', (_e) => {
        this.cont_obj.fort_installation_amount.element.value = _e.target.value;

        this.installFortBuildNumber();
    });

    this.cont_obj.fort_installation_amount.setEvent('input', (_e) =>{

        let regexp = /[^\d.]|\.(?=.*\.)/g;
        let current_value = _e.target.value;

        let minimum_value = this.cont_obj.fort_installation_amount_slider.element.min,
            maximum_value = this.cont_obj.fort_installation_amount_slider.element.max;

        current_value = (! ns_util.isNumeric(current_value)) ? minimum_value : current_value;
        current_value = current_value.replace(regexp, "");

        let number_value = ns_util.math(current_value).integer;

        number_value = Math.min(Math.max(number_value, minimum_value), maximum_value);

        this.cont_obj.fort_installation_amount_slider.value(number_value);
        this.cont_obj.fort_installation_amount.value(number_value);

        this.installFortBuildNumber();
    });

}

ns_dialog.dialogs.build_CastleWall_fort_installation.draw = function()
{
    let data = this.data;
    let m = ns_cs.m.fort[data.m_pk];
    this.condition = ns_cs.m.cond[m.m_cond_pk];

    this.devel_amount = this.installMax();

    this.cont_obj.content_title.text(ns_i18n.t('set_up_trap', [m.title]));
    this.cont_obj.develop_image.addCss('fort_image_' + this.data.type);
    this.cont_obj.develop_description.html(m.description_detail);

    this.cont_obj.content_build_time.text("");

    this.cont_obj.develop_current.text(ns_util.numberFormat(ns_cs.d.fort[m.code].v));
    this.cont_obj.fort_installation_amount.value(0);
    this.cont_obj.fort_installation_amount_slider.value(0);
    this.cont_obj.develop_max.text(ns_util.numberFormat(this.devel_amount));

    ns_button.buttons.fort_installation_amount_max.obj.text(this.devel_amount);
    this.cont_obj.fort_installation_amount_slider.element.min = 0;
    this.cont_obj.fort_installation_amount_slider.element.max = this.devel_amount;

    let tbody = this.cont_obj.table_fort_installation;
    tbody.empty();

    let cond = this.condition;
    if (! ns_check_condition.drawList(cond.m_cond_pk, this.data.castle_pk)) {
        ns_button.buttons.fort_installation_submit.setDisable();
    } else {
        ns_button.buttons.fort_installation_submit.setEnable();
    }
    ns_check_condition.drawList(cond.m_cond_pk, this.data.castle_pk, tbody, true);
}

ns_dialog.dialogs.build_CastleWall_fort_installation.installMax = function()
{
    let dialog = ns_dialog.dialogs.build_CastleWall_fort_installation;
    let m = ns_cs.m.fort[dialog.data.m_pk];
    let cond = ns_cs.m.cond[m.m_cond_pk];
    let install_max = ns_util.math(ns_cs.d.terr.wall_vacancy_max.v).minus(ns_cs.d.terr.wall_vacancy_curr.v).div(m.need_vacancy).number;

    for (let _type of ['food', 'horse', 'lumber', 'iron', 'gold']) {
        if (cond[`build_${_type}`]) {
            let max = ns_util.math((_type ==='gold') ? ns_cs.getTerritoryInfo('gold_curr') : ns_cs.getResourceInfo(`${_type}_curr`)).div(cond[`build_${_type}`]).number;
            if (ns_util.math(max).lt(install_max)) {
                install_max = max;
            }
        }
    }

    return ns_util.toInteger(install_max);
}

ns_dialog.dialogs.build_CastleWall_fort_installation.installFortBuildNumber = function()
{
    let dialog = ns_dialog.dialogs.build_CastleWall_fort_installation;
    let m = ns_cs.m.fort[dialog.data.m_pk];
    let cond = ns_cs.m.cond[m.m_cond_pk];
    let install_max = dialog.installMax();
    let build_number = ns_util.toInteger(dialog.cont_obj.fort_installation_amount.value());

    // 1 보다 작거나 숫자가 아닐때는 리턴
    if(! ns_util.isNumeric(build_number) || ns_util.math(build_number).lt(1)) {
        dialog.cont_obj.fort_installation_amount.value(0);
        dialog.cont_obj.fort_installation_amount_slider.value(0);
    }

    if(ns_util.math(install_max).lt(build_number)) {
        build_number = install_max;
        dialog.cont_obj.fort_installation_amount.value(install_max);
        dialog.cont_obj.fort_installation_amount_slider.value(install_max);
    }

    //소요시간
    if (ns_util.math(build_number).eq(0)) {
        dialog.cont_obj.content_build_time.empty();
    } else {
        dialog.cont_obj.content_build_time.text(ns_util.getCostsTime(ns_util.math(cond.build_time).mul(build_number).integer));
    }

    if(ns_util.math(build_number).lte(1)) {
        build_number = 1;
    }

    //조건 검사
    for (let _target of ['build_food', 'build_horse', 'build_lumber', 'build_iron', 'build_gold', 'build_vacancy']) {
        let _type = (_target !== 'build_vacancy') ? _target : 'need_vacancy';
        if (dialog.cont_obj.table_fort_installation.find(`.develop_${_target}`).element) {
            dialog.cont_obj.table_fort_installation.find(`.develop_${_target}`).text(ns_util.math(cond[_type]).mul(build_number).number_format);
        }
    }
}

ns_dialog.dialogs.build_CastleWall_fort_installation.erase = function()
{
    this.cont_obj.develop_image.removeCss('fort_image_' + this.data.type);
}

ns_dialog.dialogs.build_CastleWall_fort_installation.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_CastleWall_fort_installation.timerHandlerReal, 1000, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_CastleWall_fort_installation.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_CastleWall_fort_installation;

    // 현재 자원
    for (let _type of ['food_curr', 'food_curr', 'horse_curr', 'lumber_curr', 'iron_curr']) {
        let current = (_type === 'gold_curr') ? ns_cs.getTerritoryInfo(_type) : ns_cs.getResourceInfo(_type);
        if (dialog.cont_obj.table_fort_installation.find(`.ns_resource_${_type}`).element) {
            dialog.cont_obj.table_fort_installation.find(`.ns_resource_${_type}`).text(ns_util.numberFormat(ns_util.toInteger(current)))
        }
    }
}

/* ************************************************** */

ns_button.buttons.build_CastleWall_fort_installation_close = new nsButtonSet('build_CastleWall_fort_installation_close', 'button_back', 'build_CastleWall_fort_installation', { base_class: ns_button.buttons.common_close });
ns_button.buttons.build_CastleWall_fort_installation_sub_close = new nsButtonSet('build_CastleWall_fort_installation_sub_close', 'button_full', 'build_CastleWall_fort_installation', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.build_CastleWall_fort_installation_close_all = new nsButtonSet('build_CastleWall_fort_installation_close_all', 'button_close_all', 'build_CastleWall_fort_installation', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.fort_installation_amount_max = new nsButtonSet('fort_installation_amount_max', 'button_middle_2', 'build_CastleWall_fort_installation');
ns_button.buttons.fort_installation_amount_max.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_CastleWall_fort_installation;
    let maximum_value = dialog.cont_obj.fort_installation_amount_slider.element.max;
    let current_value = dialog.cont_obj.fort_installation_amount_slider.element.value;

    if ( current_value !== maximum_value ) {
        current_value = maximum_value;
    }
    else{
        current_value = 0;
    }

    dialog.cont_obj.fort_installation_amount.value(current_value);
    dialog.cont_obj.fort_installation_amount_slider.value(current_value);

    dialog.installFortBuildNumber();
}

ns_button.buttons.fort_installation_amount_decrease = new nsButtonSet('fort_installation_amount_decrease', 'button_decrease', 'build_CastleWall_fort_installation');
ns_button.buttons.fort_installation_amount_decrease.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_CastleWall_fort_installation;
    let current_value = Number(dialog.cont_obj.fort_installation_amount.value()),
        minimum_value = dialog.cont_obj.fort_installation_amount_slider.element.min;

    current_value = Math.max(--current_value, minimum_value);

    dialog.cont_obj.fort_installation_amount.value(current_value);
    dialog.cont_obj.fort_installation_amount_slider.value(current_value);
    dialog.installFortBuildNumber();
}

ns_button.buttons.fort_installation_amount_increase = new nsButtonSet('fort_installation_amount_increase', 'button_increase', 'build_CastleWall_fort_installation');
ns_button.buttons.fort_installation_amount_increase.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_CastleWall_fort_installation;
    let current_value = Number(dialog.cont_obj.fort_installation_amount.value()),
        maximum_value = dialog.cont_obj.fort_installation_amount_slider.element.max;

    current_value = Math.min(++current_value, maximum_value);

    dialog.cont_obj.fort_installation_amount.value(current_value);
    dialog.cont_obj.fort_installation_amount_slider.value(current_value);
    dialog.installFortBuildNumber();
}

ns_button.buttons.fort_installation_submit = new nsButtonSet('fort_installation_submit', 'button_special', 'build_CastleWall_fort_installation');
ns_button.buttons.fort_installation_submit.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_CastleWall_fort_installation;
    let data = dialog.data;

    let installation_number = ns_util.toInteger(dialog.cont_obj.fort_installation_amount.value());
    if(ns_util.math(installation_number).lte(0)) {
        return;
    }

    let post_data = {};
    post_data['in_cast_pk'] = data.castle_pk;
    post_data['code'] = data.type;
    post_data['build_number'] = installation_number;

    ns_xhr.post('/api/fort/upgrade', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        ns_dialog.close('build_CastleWall_fort_installation');
    }, { useProgress: true });
}


/*******************************************************************/
ns_dialog.dialogs.build_CastleWall_fort_disperse = new nsDialogSet('build_CastleWall_fort_disperse', 'dialog_building', 'size-large', { do_close_all: false });
ns_dialog.dialogs.build_CastleWall_fort_disperse.disperse_amount = 0;

ns_dialog.dialogs.build_CastleWall_fort_disperse.cacheContents = function ()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);

    this.cont_obj.content_disperse_table = new nsObject('.content_disperse_table', this.obj);

    // this.cont_obj.cont_disperse_food = this.cont_obj.find('.cont_disperse_food');
    // this.cont_obj.cont_disperse_horse = this.cont_obj.find('.cont_disperse_horse');
    // this.cont_obj.cont_disperse_lumber = this.cont_obj.find('.cont_disperse_lumber');
    // this.cont_obj.cont_disperse_iron = this.cont_obj.find('.cont_disperse_iron');
    // this.cont_obj.cont_disperse_gold = this.cont_obj.find('.cont_disperse_gold');
    // this.cont_obj.cont_disperse_vacancy = this.cont_obj.find('.cont_disperse_vacancy');
//
    // this.cont_obj.qbw_terr_gold_curr = this.cont_obj.find('.qbw_terr_gold_curr');
    // this.cont_obj.cont_disperse_population = this.cont_obj.find('.cont_disperse_population');
    // // this.cont_obj.qbw_terr_population_idle = this.cont_obj.find('.qbw_terr_population_idle');
    // this.cont_obj.qbw_terr_wall_vacancy_curr = this.cont_obj.find('.qbw_terr_wall_vacancy_curr');
//
    // this.cont_obj.qbw_reso_food_curr = this.cont_obj.find('.qbw_reso_food_curr');
    // this.cont_obj.qbw_reso_horse_curr = this.cont_obj.find('.qbw_reso_horse_curr');
    // this.cont_obj.qbw_reso_lumber_curr = this.cont_obj.find('.qbw_reso_lumber_curr');
    // this.cont_obj.qbw_reso_iron_curr = this.cont_obj.find('.qbw_reso_iron_curr');
//
    // this.cont_obj.cont_fort_curr = this.cont_obj.find('.cont_fort_curr');
    // this.cont_obj.cont_fort_disperse_value = this.cont_obj.find('#qbw_btn_fort_disperse_value');

    let self = this;

    this.cont_obj.disperse_amount = new nsObject('.amount_field', this.obj);
    this.cont_obj.disperse_amount_slider = new nsObject('input[name="develop_slider"]', this.obj);

    this.cont_obj.disperse_amount_slider.setEvent('input', function(_e){

        let current_value = _e.target.value;

        self.cont_obj.disperse_amount.value(current_value);

        self.disperseFortBuildNumber();
    });

    this.cont_obj.disperse_amount.setEvent('input', function(_e){
        let regExp = /[^0-9]/g;
        let current_value = _e.target.value;

        if ( current_value === undefined || current_value === "" || isNaN(current_value)) { return ;}

        let number_value = current_value.replace(regExp, "");
        let maximum_value = Number(self.cont_obj.disperse_amount_slider.element.max),
            minimum_value = Number(self.cont_obj.disperse_amount_slider.element.min);

        number_value = Math.min(Math.max(minimum_value, current_value), maximum_value);

        self.cont_obj.disperse_amount.value(number_value);
        self.cont_obj.disperse_amount_slider.value(number_value);

        self.disperseFortBuildNumber();
    });
}

ns_dialog.dialogs.build_CastleWall_fort_disperse.draw = function()
{
    let data = this.data;
    let m = ns_cs.m.fort[data.m_pk];
    let cond = ns_cs.m.cond[m.m_cond_pk];

     // 보유
    this.disperse_amount = ns_cs.d.fort[m.code].v;

    this.cont_obj.content_pop_title.text(ns_i18n.t('dismantle_trap', [m.title]));

    for (let _type of ['food', 'horse', 'lumber', 'iron', 'gold']) {
        this.cont_obj.content_disperse_table.find(`.content_disperse_${_type}`).text(ns_util.numberFormat(cond[`demolish_${_type}`] ? cond[`demolish_${_type}`] : 0));
    }
    this.cont_obj.content_disperse_table.find('.content_disperse_vacancy').text(ns_util.numberFormat(m.need_vacancy));

    this.cont_obj.disperse_amount.value(0);
    this.cont_obj.disperse_amount_slider.element.min = 0;
    this.cont_obj.disperse_amount_slider.value(0)
    this.cont_obj.disperse_amount_slider.element.max = this.disperse_amount;

    ns_button.buttons.fort_disperse_max_amount.obj.text(this.disperse_amount);
}

ns_dialog.dialogs.build_CastleWall_fort_disperse.disperseFortBuildNumber = function()
{
    let dialog = ns_dialog.dialogs.build_CastleWall_fort_disperse;
    let m = ns_cs.m.fort[dialog.data.m_pk];
    let cond = ns_cs.m.cond[m.m_cond_pk];
    let disperse_max = ns_cs.d.fort[m.code].v;
    let disperse_number = dialog.cont_obj.disperse_amount.value();

    // 1 보다 작거나 숫자가 아닐때는 리턴
    if(! ns_util.isNumeric(disperse_number) || ns_util.math(disperse_number).lt(1)) {
        disperse_number = 0;
    }

    if(ns_util.math(disperse_max).lt(disperse_number)) {
        disperse_number = disperse_max;
    }

    dialog.cont_obj.disperse_amount.value(disperse_number);
    dialog.cont_obj.disperse_amount_slider.value(disperse_number);

    //조건 검사
    for (let _type of ['food', 'horse', 'lumber', 'iron', 'gold']) {
        dialog.cont_obj.content_disperse_table.find(`.content_disperse_${_type}`).text(ns_util.math(cond[`demolish_${_type}`] ? cond[`demolish_${_type}`] : 0).mul(disperse_number).number_format);
    }
    dialog.cont_obj.content_disperse_table.find('.content_disperse_vacancy').text(ns_util.math(m.need_vacancy).mul(disperse_number).number_format);
}

ns_dialog.dialogs.build_CastleWall_fort_disperse.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_CastleWall_fort_disperse.timerHandlerReal, 1000, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_CastleWall_fort_disperse.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_CastleWall_fort_disperse;

    for (let _type of ['food_curr', 'horse_curr', 'lumber_curr', 'iron_curr']) {
        dialog.cont_obj.content_disperse_table.find(`.ns_resource_${_type}`).text(ns_util.numberFormat(ns_util.toInteger(ns_cs.getResourceInfo(_type))));
    }
    for (let _type of ['gold_curr', 'wall_vacancy_curr']) {
        dialog.cont_obj.content_disperse_table.find(`.ns_territory_${_type}`).text(ns_util.numberFormat(ns_util.toInteger(ns_cs.getTerritoryInfo(_type))));
    }
}

/* ************************************************** */

ns_button.buttons.build_CastleWall_fort_disperse_close = new nsButtonSet('build_CastleWall_fort_disperse_close', 'button_back', 'build_CastleWall_fort_disperse', { base_class: ns_button.buttons.common_close });
ns_button.buttons.build_CastleWall_fort_disperse_sub_close = new nsButtonSet('build_CastleWall_fort_disperse_sub_close', 'button_full', 'build_CastleWall_fort_disperse', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.fort_disperse_submit = new nsButtonSet('fort_disperse_submit', 'button_default', 'build_CastleWall_fort_disperse');
ns_button.buttons.fort_disperse_submit.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_CastleWall_fort_disperse;
    let data = dialog.data;

    let disperse_number = dialog.cont_obj.disperse_amount.value();

    if(ns_util.math(disperse_number).lte(0)) {
        return;
    }

    let post_data = {};
    post_data['code'] = data.type;
    post_data['disperse_number'] = parseInt(disperse_number);

    ns_xhr.post('/api/fort/disperse', post_data, function (_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }

        ns_dialog.dialogs.build_CastleWall.drawTab();

        dialog.close('build_CastleWall_fort_disperse');
    }, { useProgress: true });
}

ns_button.buttons.fort_disperse_max_amount = new nsButtonSet('fort_disperse_max_amount', 'button_middle_2', 'build_CastleWall_fort_disperse');
ns_button.buttons.fort_disperse_max_amount.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_CastleWall_fort_disperse;
    let disperse_value = dialog.cont_obj.disperse_amount;
    let disperse_value_slider = dialog.cont_obj.disperse_amount_slider;

    if (ns_util.math(disperse_value.value()).eq(ns_cs.d.fort[dialog.data.type].v)) {
        disperse_value.value(0);
        disperse_value_slider.value(0);
    } else {
        disperse_value.value(ns_cs.d.fort[dialog.data.type].v);
        disperse_value_slider.value(ns_cs.d.fort[dialog.data.type].v);
    }

    dialog.disperseFortBuildNumber();
}

ns_button.buttons.castle_fort_disperse_decrease = new nsButtonSet('castle_fort_disperse_decrease', 'button_decrease', 'build_CastleWall_fort_disperse');
ns_button.buttons.castle_fort_disperse_decrease.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_CastleWall_fort_disperse;
    let current_value = Number(dialog.cont_obj.disperse_amount.value()),
        minimum_value = Number(dialog.cont_obj.disperse_amount_slider.element.min);

    current_value = Math.max(--current_value, minimum_value);

    dialog.cont_obj.disperse_amount.value(current_value);
    dialog.cont_obj.disperse_amount_slider.value(current_value);

    dialog.disperseFortBuildNumber();
}

ns_button.buttons.castle_fort_disperse_increase = new nsButtonSet('castle_fort_disperse_increase', 'button_increase', 'build_CastleWall_fort_disperse');
ns_button.buttons.castle_fort_disperse_increase.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_CastleWall_fort_disperse;
    let current_value = Number(dialog.cont_obj.disperse_amount.value()),
        maximum_value = Number(dialog.cont_obj.disperse_amount_slider.element.max);

    current_value = Math.min(++current_value, maximum_value);

    dialog.cont_obj.disperse_amount.value(current_value);
    dialog.cont_obj.disperse_amount_slider.value(current_value);

    dialog.disperseFortBuildNumber();
}