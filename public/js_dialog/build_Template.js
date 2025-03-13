// 다이얼로그
ns_dialog.dialogs.build_Template = new nsDialogSet('build_Template', 'dialog_building', 'size-large');
ns_dialog.dialogs.build_Template.cacheContents = function ()
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);
    this.cont_obj.content_level = new nsObject('.content_level', this.obj);

    this.cont_obj.content_build_desc_wrap = new nsObject('.content_build_desc_wrap', this.obj);
    this.cont_obj.content_build_desc = new nsObject('.content_build_desc', this.obj);
    this.cont_obj.content_build_desc_sm = new nsObject('.content_build_desc_sm', this.obj);

    this.cont_obj.content_build_desc_def = new nsObject('.content_build_desc_def', this.obj);
    this.cont_obj.content_build_desc_footnote = new nsObject('.content_build_desc_footnote', this.obj);

    this.cont_obj.content_upgrading = new nsObject('.content_upgrading', this.obj);
    this.cont_obj.content_build_left_time = new nsObject('.content_build_left_time', this.obj);

    this.cont_obj.content_card_empty = new nsObject('.content_card_empty', this.obj);

    this.cont_obj.content_page_curr = new nsObject('.content_page_curr', this.obj);
    this.cont_obj.content_page_total = new nsObject('.content_page_total', this.obj);

    this.cont_obj.content_build_desc_vr = new nsObject('.content_build_desc_vr', this.obj);

    this.cont_obj.content_card = new nsObject('.content_card', this.obj);
    this.cont_obj.content_hero_detail = new nsObject('.content_hero_detail', this.obj);

    this.cont_obj.content_card_sm_empty = new nsObject('.content_card_sm_empty', this.obj);
    this.cont_obj.content_card_sm = new nsObject('.content_card_sm', this.obj);
    this.cont_obj.content_hero_sm = new nsObject('.content_hero_sm', this.obj);

    this.cont_obj.content_build_desc_assign = new nsObject('.content_build_desc_assign', this.obj);
    this.cont_obj.content_build_desc_assign_sm = new nsObject('.content_build_desc_assign_sm', this.obj);
    this.cont_obj.content_build_desc_assign_footnote = new nsObject('.content_build_desc_assign_footnote', this.obj);
    this.cont_obj.content_applied_capa = new nsObject('.content_applied_capa', this.obj);
    this.cont_obj.content_applied_skill = new nsObject('.content_applied_skill', this.obj);
    this.cont_obj.content_applied_capa_sm = new nsObject('.content_applied_capa_sm', this.obj);
    this.cont_obj.content_applied_skill_sm = new nsObject('.content_applied_skill_sm', this.obj);

    this.cont_obj.content_build_level_desc = new nsObject('.content_build_level_desc', this.obj);

    this.cont_obj.content_build_cons = new nsObject('.content_build_cons', this.obj);
    this.cont_obj.content_build_construct_hero = new nsObject('.content_build_construct_hero', this.obj);

    this.cont_obj.content_build_upgrade = new nsObject('.content_build_upgrade', this.obj);
    this.cont_obj.content_build_cons_multi = new nsObject('.content_build_cons_multi', this.obj);
    this.cont_obj.content_build_max_level = new nsObject('.content_build_max_level', this.obj);

    this.cont_obj.content_upgrading.hide();

    this.cont_obj.content_build_desc.hide();
    this.cont_obj.content_card_empty.hide();
    this.cont_obj.content_card.hide();

    this.cont_obj.content_build_desc_sm.hide();
    this.cont_obj.content_card_sm_empty.hide();
    this.cont_obj.content_card_sm.hide();

    let z_arr = this.tag_id.split('_');
    let alias = z_arr[1];
    let m = ns_cs.m.buil[alias];
    if (m.yn_hero_assign === 'Y') {
        this.cont_obj.content_build_desc_vr.show();
        let description_assign = ns_i18n.t(`build_description_assign_${m.m_buil_pk}`);
        this.cont_obj.content_build_desc_assign.html(description_assign);
        this.cont_obj.content_build_desc_assign_sm.html(description_assign);
        this.cont_obj.content_build_desc_assign_footnote.html(ns_i18n.t('build_hero_assign_effect_notice'));
    }
}

ns_dialog.dialogs.build_Template.draw = function()
{
    let z_arr = this.tag_id.split('_');
    let alias = z_arr[1];
    let m = ns_cs.m.buil[alias];
    if (! this.data) {
        this.data = {};
        this.data.castle_pk = ns_cs.getCastlePk(m.type, m.m_buil_pk);
    }

    this.__lud_ms = null;

    // 멀티 건물간 이동이 아닐 경우만 (해당 건물을 새로 열 경우)
    if (! this.visible) {
        this.cont_obj.content_build_desc.show();

        if (m.yn_hero_assign === 'Y') {
            this.cont_obj.content_build_desc_sm.hide();
        } else {
            this.cont_obj.content_build_desc_wrap.show();
        }

        if (ns_button.buttons['build_desc_' + alias]) {
            //ns_button.buttons['build_desc_' + alias].obj.text('▲ 설명숨김');
            ns_button.buttons['build_desc_' + alias].setClicked();
        }
    }
}

ns_dialog.dialogs.build_Template.timerHandler = function()
{
    let tag_id = this.tag_id;
    ns_timer.timers[tag_id] = new nsTimerSet(function() { ns_dialog.dialogs[tag_id].timerHandlerProc(tag_id) }, 200, true);
    ns_timer.timers[tag_id].init();
    return ns_timer.timers[tag_id];
}

ns_dialog.dialogs.build_Template.timerHandlerProc = function(_tag_id)
{
    let dialog = ns_dialog.dialogs[_tag_id];
    let _castle_pk = dialog.data.castle_pk;

    let z_arr = dialog.tag_id.split('_');
    let alias = z_arr[1];
    let m = ns_cs.m.buil[alias];

    let bd_c = m.type === 'I' ? ns_cs.d.bdic : ns_cs.d.bdoc;
    let d = bd_c[_castle_pk];
    // 건물 정보를 찾을 수 없음 - 최초 건설 취소나 철거 등
    if (!d) {
        dialog.close();
        return;
    }

    // 남은 시간 표시
    let time_pk  = ns_cs.getTimerPk('C', null, m.type, _castle_pk);
    if (time_pk) {
        ns_cs.getBuildingLeftTime(dialog.cont_obj.content_build_left_time, d.status, time_pk);
        // 독려, 취소 버튼 처리
        if (d.status !== 'N')        {
            if (ns_util.math(ns_cs.d.time[time_pk].end_dt_ut).minus(ns_timer.now()).lte(5)) {
                ns_button.buttons['build_speedup_' + alias].setDisable();
                // ns_button.buttons['build_cancel_' + alias].setDisable();
            } else {
                ns_button.buttons['build_speedup_' + alias].setEnable();
                if (ns_timer.checkFreeSpeedup(time_pk)) {
                    ns_button.buttons['build_speedup_' + alias].obj.text(ns_i18n.t('immediately_complete'));
                } else {
                    ns_button.buttons['build_speedup_' + alias].obj.text(ns_i18n.t('encourage'));
                }
                // ns_button.buttons['build_cancel_' + alias].setEnable();
            }
        }
    }

    let max_count = ns_cs.getBuildLimitCount(m.m_buil_pk);
    let current_count = ns_cs.getBuildList(m.m_buil_pk, true);
    if (ns_button.buttons['build_cons_' + alias]) {
        if (max_count <= current_count) {
            ns_button.buttons['build_cons_' + alias].setDisable();
        } else if (m.yn_duplication === 'Y') {
            ns_button.buttons['build_cons_' + alias].setEnable();
        }
    }

    // lud_ms 로 갱신 타이밍 조절
    if (d.__lud_ms <= dialog.__lud_ms && dialog.__lud_ms != null) {
        return;
    } else {
        dialog.__lud_ms = d.__lud_ms;
    }
    this.current_page = ns_cs.getBuildNumber(m.type, _castle_pk);
    this.total_page = current_count;

    dialog.cont_obj.content_title.text(ns_i18n.t(`build_title_${m.m_buil_pk}`) + ' ');

    dialog.cont_obj.content_level.text('Lv.' + d.level);

    if (d.status === 'U') {
        dialog.cont_obj.content_upgrading.text((parseInt(d.level) < 1) ? ' 건설 중' : ' 업그레이드 중').show();
    } else {
        dialog.cont_obj.content_upgrading.hide();
    }

    if (dialog.cont_obj.content_page_curr.element) {
        dialog.cont_obj.content_page_curr.text(this.current_page);
    }

    this.match_count = max_count;
    if (dialog.cont_obj.content_page_total.element) {
        dialog.cont_obj.content_page_total.text(this.total_page);
    }

    dialog.cont_obj.content_build_desc_def.html(ns_i18n.t(`build_description_${m.m_buil_pk}`)); // m.description
    dialog.cont_obj.content_build_desc_footnote.html(ns_i18n.t(`build_description_footnote_${m.m_buil_pk}`)); // m.description_footnote

    let build_level_desc_prefix = '', status_desc_prefix = '';
    if (m.yn_duplication === 'Y' && max_count > dialog.match_count) {
        build_level_desc_prefix = m.title + ' ' + (max_count - dialog.match_count) + '개 건설 가능 / ';
    }

    if (ns_util.math(d.m_buil_pk).eq(200800)) {
        status_desc_prefix = '<span class="content_camp"></span> / ';
    } else if (ns_util.math(d.m_buil_pk).eq(200400)) { // 군사령부 라면
        status_desc_prefix = '<span class="content_gate"></span> / ';
    }

    if (d.level) {
        dialog.cont_obj.content_build_level_desc.html(build_level_desc_prefix + status_desc_prefix + m.level[d.level].variation_description);
    } else {
        dialog.cont_obj.content_build_level_desc.html(build_level_desc_prefix + ns_i18n.t('under_construction'));
    }

    dialog.cont_obj.content_build_cons.hide();
    dialog.cont_obj.content_build_upgrade.hide();
    dialog.cont_obj.content_build_cons_multi.hide();
    dialog.cont_obj.content_build_max_level.hide();

    if (m.yn_duplication === 'Y') {
        // TODO 더 이상 추가 건설 할 수 없을 경우 버튼 비활성화 필요.
        dialog.cont_obj.content_build_cons_multi.show();
    }

    // 건설 버튼 제어
    if (d.status === 'N') {
        if (d.level < m.max_level) {
            dialog.cont_obj.content_build_upgrade.show();
        } else {
            dialog.cont_obj.content_build_max_level.show();
        }
    } else {
        dialog.cont_obj.content_build_cons.show();
        // 영웅 정보
        if (time_pk) {
            dialog.cont_obj.content_build_construct_hero.html(ns_timer.convertDescription(ns_cs.d.time[time_pk].queue_type, ns_cs.d.time[time_pk].description));
        }
    }

    if (m.yn_hero_assign === 'Y') {
        if (d.assign_hero_pk && d.assign_hero_pk > 0) {
            dialog.cont_obj.content_hero_detail.empty();
            dialog.cont_obj.content_hero_detail.append(ns_hero.cardDetailDraw(d.assign_hero_pk, 'N'));

            dialog.cont_obj.content_hero_sm.empty();
            dialog.cont_obj.content_hero_sm.append(ns_hero.cardSmDraw(d.assign_hero_pk));

            dialog.cont_obj.content_applied_capa.text('-');
            dialog.cont_obj.content_applied_skill.text('-');
            dialog.cont_obj.content_applied_capa_sm.text('-');
            dialog.cont_obj.content_applied_skill_sm.text('-');

            let effects = ns_hero.getEffect(d.assign_hero_pk, null, 'assign', m.m_buil_pk);
            if (effects.capa) {
                dialog.cont_obj.content_applied_capa.html(effects.capa);
                dialog.cont_obj.content_applied_capa_sm.html(effects.capa);
            }
            if (effects.skill) {
                dialog.cont_obj.content_applied_skill.html(effects.skill);
                dialog.cont_obj.content_applied_skill_sm.html(effects.skill);
            }

            dialog.cont_obj.content_card_empty.hide();
            dialog.cont_obj.content_card_sm_empty.hide();
            dialog.cont_obj.content_card.show();
            dialog.cont_obj.content_card_sm.show();
        } else {
            dialog.cont_obj.content_applied_capa.html('-');
            dialog.cont_obj.content_applied_capa_sm.html('-');
            dialog.cont_obj.content_applied_skill.html('-');
            dialog.cont_obj.content_applied_skill_sm.html('-');

            dialog.cont_obj.content_card_empty.show();
            dialog.cont_obj.content_card_sm_empty.show();
            dialog.cont_obj.content_card.hide();
            dialog.cont_obj.content_card_sm.hide();
        }
    }
}

ns_button.buttons.build_Template_close = new nsButtonSet('build_Template_close', 'button_back', 'build_Template', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_Template_sub_close = new nsButtonSet('build_Template_sub_close', 'button_full', 'build_Template', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.build_Template_closeAll = new nsButtonSet('build_Template_closeAll', 'btn_close_all', 'build_Template', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_Template = new nsButtonSet('build_desc_Template', 'button_text_style_desc', 'build_Template', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_move_Template = new nsButtonSet('build_move_Template', 'button_middle_2', 'build_Template', { base_class: ns_button.buttons.build_move });
ns_button.buttons.build_assign_Template = new nsButtonSet('build_assign_Template', 'button_empty', 'build_Template', { base_class: ns_button.buttons.build_assign });
ns_button.buttons.build_cons_Template = new nsButtonSet('build_cons_Template', 'button_multi', 'build_Template', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_Template = new nsButtonSet('build_upgrade_Template', 'button_hero_action', 'build_Template', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_prev_Template = new nsButtonSet('build_prev_Template', 'button_multi_prev', 'build_Template', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_Template = new nsButtonSet('build_next_Template', 'button_multi_next', 'build_Template', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_Template = new nsButtonSet('build_speedup_Template', 'button_small_1', 'build_Template', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_Template = new nsButtonSet('build_cancel_Template', 'button_build', 'build_Template', { base_class: ns_button.buttons.build_cancel });