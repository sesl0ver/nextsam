
ns_dialog.dialogs.counsel = new nsDialogSet('counsel', 'dialog_counsel', 'size-counsel', { do_close_all: false, do_content_scroll: false });
ns_dialog.dialogs.counsel.m_coun_pk = null;
ns_dialog.dialogs.counsel.card_pk = null;
ns_dialog.dialogs.counsel.connect_status = false;
ns_dialog.dialogs.counsel.encounter_data = [];
ns_dialog.dialogs.counsel.suppress_status = false;
ns_dialog.dialogs.counsel.alliane_invit_status = false;
ns_dialog.dialogs.counsel.sorted = null;

ns_dialog.dialogs.counsel.cacheContents = function()
{
    this.cont_obj.counsel_wrap = new nsObject('.counsel_wrap', this.obj);
    this.cont_obj.counsel_bottom_wrap = new nsObject('.counsel_bottom_wrap', this.obj);
}

ns_dialog.dialogs.counsel.draw = function()
{
    this.m_coun_pk = null;
    if (! this.visible && ! this.first_open) {
        let except_dialog = ['counsel', 'main_event', 'attendance_event'];
        if (this.data?.except_dialog) {
            except_dialog = [...except_dialog, ...this.data?.except_dialog];
        }
        ns_dialog.closeAll(except_dialog);
    }

    if (this.data.type === 'connect') {
        if (this.connect_status) {
            this.open_cancel = true;
            return;
        }
        if (this.connectCheck()) {
            this.drawConnect();
        } else {
            this.open_cancel = true;
        }
    } else {
        if (ns_cs.d.lord['setting']['counsel_action'] === 'N') {
            this.open_cancel = true;
        } else {
            this.drawAction();
        }
    }
}

ns_dialog.dialogs.counsel.connectCheck = function()
{
    let dialog = ns_dialog.dialogs.counsel;

    dialog.sorted = [];
    dialog.m_coun_pk = null;

    for (let d of Object.values(ns_cs.m.coun_conn)) {
        dialog.sorted.push(d);
    }

    if (dialog.sorted.length > 1) {
        dialog.sorted = ns_util.arraySort(dialog.sorted, -1, 'order_no');
    }

    for (let d of dialog.sorted) {
        if (ns_util.math(d.m_coun_conn_pk).eq(600001)) {
            if (ns_dialog.dialogs.quest.unread_cnt > 0) {
                dialog.m_coun_pk = d.m_coun_conn_pk;
                break;
            }
        } else if (ns_util.math(d.m_coun_conn_pk).eq(600002)) {
            if (ns_cs.d.ques[600101] && ns_cs.d.ques[600101].status === 'P') {
                dialog.m_coun_pk = d.m_coun_conn_pk;
                break;
            }
        } else if (ns_cs.getCastlePk('I', 200300) !== false && d['type'] === 'encounter') {
            if (! dialog.encounter_data[0]?.status || dialog.encounter_data[0]?.type === 'none') {
                if (ns_util.math(d.m_coun_conn_pk).eq(600003)) {
                    dialog.m_coun_pk = d.m_coun_conn_pk;
                    break;
                }
            } else if (dialog.encounter_data[0].status === 'F' && dialog.encounter_data[0].type !== 'none') {
                if (ns_util.math(d.m_coun_conn_pk).eq(600004) && dialog.encounter_data[0].type === 'hero') {
                    dialog.m_coun_pk = d.m_coun_conn_pk;
                    break;
                } else if (ns_util.math(d.m_coun_conn_pk).eq(600005) && dialog.encounter_data[0].type === 'item') {
                    dialog.m_coun_pk = d.m_coun_conn_pk;
                    break;
                } else if (ns_util.math(d.m_coun_conn_pk).eq(600006) && ['gold', 'food', 'horse', 'lumber', 'iron'].includes(dialog.encounter_data[0].type)) {
                    dialog.m_coun_pk = d.m_coun_conn_pk;
                    break;
                }
            }
        } else if (d['type'] === 'assign') {
            let castle_pk = ns_cs.getCastlePk('I', d['m_buil_pk']);
            if (ns_cs.d.bdic[castle_pk] && ! ns_cs.d.bdic[castle_pk].assign_hero_pk) {
                dialog.m_coun_pk = d.m_coun_conn_pk;
                break;
            }
        } else if (ns_util.math(d.m_coun_conn_pk).eq(600012)) {
            if (dialog.suppress_status === 'N') {
                dialog.m_coun_pk = d.m_coun_conn_pk;
                break;
            }
        } else if (ns_util.math(d.m_coun_conn_pk).eq(600013)) {
            if (ns_cs.d.lord.unread_report_cnt.v > 0) {
                dialog.m_coun_pk = d.m_coun_conn_pk;
                break;
            }
        } else if (ns_util.math(d.m_coun_conn_pk).eq(600014)) {
            if (ns_cs.d.lord.unread_letter_cnt.v > 0) {
                dialog.m_coun_pk = d.m_coun_conn_pk;
                break;
            }
        } else if (ns_util.math(d.m_coun_conn_pk).eq(600015)) {
            if (dialog.alliane_invit_status) {
                dialog.m_coun_pk = d.m_coun_conn_pk;
                break;
            }
        }
    }
    return !!dialog.m_coun_pk;
}

ns_dialog.dialogs.counsel.drawCard = function(rare, level, name, card_pk)
{
    let dialog = ns_dialog.dialogs.counsel;
    let m = (dialog.data.type === 'action') ? ns_cs.m.coun_acti[dialog.m_coun_pk] : ns_cs.m.coun_conn[dialog.m_coun_pk];
    dialog.cont_obj.counsel_bottom_wrap.find('.counsel_bottom_description').text(m.bottom_description);

    let card_warp = dialog.cont_obj.counsel_wrap.find('.hero_card');
    dialog.data.prev_rare = rare;
    dialog.data.prev_card_pk = card_pk;
    card_warp.find('.hero_card_frame').addCss(`hero_rare${rare}`);
    card_warp.find('.hero_card_pic_small').addCss(`card_face_${card_pk}`);
    card_warp.find('.hero_card_name').text(name);
    card_warp.find('.hero_card_level').text(level);
}

ns_dialog.dialogs.counsel.drawAction = function()
{
    let dialog = ns_dialog.dialogs.counsel;

    let d = ns_cs.d.hero[dialog.data.hero_pk];
    let m_hero = ns_cs.m.hero[d.m_hero_pk];
    let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];

    let rare = m_hero_base.rare_type;
    let level = d.level;
    let name = m_hero_base.name;
    dialog.card_pk = m_hero.m_hero_base_pk;
    let yn_lord = d.yn_lord;

    for (let d of Object.values(ns_cs.m.coun_acti)) {
        if (d.yn_lord === yn_lord && d.type === dialog.data.counsel_type) {
            dialog.m_coun_pk = d.m_coun_acti_pk;
            break;
        }
    }

    let m = ns_cs.m.coun_acti[dialog.m_coun_pk];
    let description = m.description;
    let cost_time = null;
    let invite_name = null;
    if (dialog.data.counsel_type === 'invit') {
        m_hero = ns_cs.m.hero[dialog.data.cost];
        invite_name = ns_cs.m.hero_base[m_hero.m_hero_base_pk].name
    } else {
        cost_time = ns_util.getCostsTime(dialog.data.cost);
    }

    if (m.type === 'techn') {
        description = description.replace(/timecost/, cost_time).replace(/code/, ns_i18n.t(`tech_title_${ns_cs.m.tech[dialog.data.code].m_tech_pk}`));
    } else if (m.type === 'invit') {
        description = description.replace(/timecost/, cost_time).replace(/hero/, invite_name);
    } else {
        description = description.replace(/timecost/, cost_time);
    }

    dialog.cont_obj.counsel_wrap.find('.counsel_description').html(description);

    dialog.drawCard(rare, level, name, dialog.card_pk);
}

ns_dialog.dialogs.counsel.drawConnect = function()
{
    let dialog = ns_dialog.dialogs.counsel;
    let hero_arr = [];

    for (let [k, d] of Object.entries(ns_cs.d.hero)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        if (d.yn_lord === 'Y') {
            continue;
        }
        hero_arr.push(d);
    }
    let rare = null, level = null, name = null, card_pk = null;
    let d = ns_util.shuffle(hero_arr);
    if (d.length < 1) {
        rare = 7;
        level = 1;
        name = '초선';
        dialog.card_pk = 120006;
    } else {
        d = d[0];
        let m_hero = ns_cs.m.hero[d.m_hero_pk];
        let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];
        rare = m_hero_base.rare_type;
        level = d.level;
        name = m_hero_base.name;
        dialog.card_pk = m_hero.m_hero_base_pk;
    }
    let m = ns_cs.m.coun_conn[dialog.m_coun_pk];
    let description = m.description;

    if (ns_util.math(dialog.m_coun_pk).eq(600004)) {
        description = description.replace(/find_hero/, dialog.encounter_data[0].find_hero).replace(/hero/, dialog.encounter_data[0].cmd_hero);
    } else if (ns_util.math(dialog.m_coun_pk).eq(600005)) {
        description = description.replace(/item/, ns_cs.m.item[dialog.encounter_data[0].value].title);
    } else if (ns_util.math(dialog.m_coun_pk).eq(600006)) {
        description = description.replace(/reso/, ns_i18n.t(`resource_${dialog.encounter_data[0].type}`));
    }
    dialog.cont_obj.counsel_wrap.find('.counsel_description').html(description);

    dialog.drawCard(rare, level, name, dialog.card_pk);

    dialog.connect_status = true;
}

ns_dialog.dialogs.counsel.erase = function ()
{
    let card_warp = this.cont_obj.counsel_wrap.find('.hero_card');
    card_warp.find('.hero_card_frame').removeCss(`hero_rare${this.data.prev_rare}`);
    card_warp.find('.hero_card_pic_small').removeCss(`card_face_${this.data.prev_card_pk}`);
    this.cont_obj.counsel_wrap.find('.hero_card_pic_small').removeCss(`card_face_${this.card_pk}`);
    this.card_pk = null;
    this.data = null;
    if (ns_engine.game_data.first_popup_package !== null) {
        if (Object.keys(ns_engine.game_data.package_data).length > 0) {
            // 게임 최초 접속시 1회 띄워주기 위해
            ns_dialog.setDataOpen('package_popup', { m_package_pk: ns_engine.game_data.first_popup_package });
        }
    }
}

/* ************************************************** */

ns_button.buttons.counsel_ok = new nsButtonSet('counsel_ok', 'button_middle_2', 'counsel');
ns_button.buttons.counsel_ok.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.counsel;

    if (dialog.data.type === 'action') {
        let type = ns_cs.m.coun_acti[dialog.m_coun_pk].dlg_type;
        ns_dialog.open('counter_job_list');
        ns_button.buttons[`counter_${type}_tab`].mouseUp();
    } else {
        let m = ns_cs.m.coun_conn[dialog.m_coun_pk];
        if (! m) {
            ns_dialog.close('counsel');
        }
        let dialog_id = m.move_dlg.replace('buil_', 'build_');
        if (m.move_dlg === dialog_id) {
            ns_dialog.open(dialog_id);
        } else {
            let target = dialog_id.replace('build_', '');
            let m = ns_cs.m.buil[target];
            ns_dialog.setDataOpen(dialog_id, {
                castle_type: (m.type === 'O') ? 'bdoc' : 'bdic',
                castle_pk: ns_cs.getCastlePk(m.type, m.m_buil_pk)
            });
        }
        if (m.button_id) {
            ns_button.buttons[m.button_id].mouseUp();
        }
    }

    ns_dialog.close('counsel');
}

ns_button.buttons.counsel_close = new nsButtonSet('counsel_close', 'button_middle_2', 'counsel', { base_class: ns_button.buttons.common_close });
ns_button.buttons.counsel_sub_close = new nsButtonSet('counsel_sub_close', 'button_full', 'counsel', { base_class: ns_button.buttons.common_sub_close });
