/*
 * TODO ALLY_RELA 추가 확인 필요.
 */

// 군주 정보
ns_cs.d.lord = new nsCsSet();
ns_cs.d.lord.render = function(_key)
{
    if (this.first_render) {
        this.s.lord_info = new nsObject('.main_top_wrap .main_top_sub_wrap:nth-child(1)');
        this.s.lord_face = new nsObject('.main_top_lord_face .lord_face');
        this.s.lord_name = new nsObject('.main_top_lord_info .lord_name');
        // this.s.lord_level = new nsObject('.main_top_lord_info .lord_level');
        this.s.lord_power = new nsObject('.main_top_lord_info .lord_power');
    }

    switch (_key) {
        case 'level':
            // this.s.lord_level.text(`Lv.${this[_key].v}`);
            this.s.lord_face.setAttribute('data-level', this[_key].v);
            break;
        case 'power':
            this.s.lord_power.text(ns_util.numberFormat(this[_key].v));
            break;
        case 'lord_pic':
            this.s.lord_face.addCss('lord_face_' + this[_key].v);
            break;
        case 'unread_letter_desc':
        case 'unread_report_desc':
            if (ns_dialog.dialogs.report.visible) {
                ns_dialog.dialogs.report.drawReportUnread();
            }
            break;
        case 'unread_report_cnt':
        case 'unread_letter_cnt':
            ns_dialog.dialogs.report.drawMainUnread();
            break;
        case 'unread_hero_cnt':
        case 'unread_guest_cnt':
        // case 'unread_overrank_cnt':
            ns_dialog.dialogs.hero_manage.drawHeroUnread();
            break;
        case 'unread_quest_cnt':
            ns_dialog.dialogs.quest.unreadQuestCount(this[_key].v);
            break;
        case 'unread_alliance_cnt':
            // qbw_dlg.dlgs.alliance.unread_alliance_cnt(parseInt(this[_key].v));
            break;
        case 'unread_alliance_gift_cnt':
            // qbw_dlg.dlgs.alliance.unread_alliance_gift_cnt(parseInt(this[_key].v));
            break;
        case 'lord_name':
            this.s.lord_name.text(this[_key].v);
            break;
        case 'unread_item_last_up_dt':
            /*console.log('음');
            ns_engine.game_data.unread_item_last_up_dt = this[_key]?.v ?? ns_timer.now();
            ns_button.buttons.main_my_item.obj.addCss('main_flag_new');*/
            ns_dialog.dialogs.my_item.checkNew();
            break;
        case 'new_item_update':
            window.localStorage.setItem(`unread_item_${this[_key].v}`, 'true');
            ns_dialog.dialogs.my_item.checkNew();
            break;
        case 'lord_name_up_dt':
            /*if (parseInt(this[_key].v) > 0)
            {
                this.s.lord_name_flag.hide();
            } else {
                this.s.lord_name_flag.show();
            }*/
            break;
        case 'new_skill_update':
            /*if (this[_key].v)
            {
                $('#main_hero_skill > .cont_cnt_hero_skill').show();
                qbw_dlg.dlgs.hero_skill_manage.draw_new_icon(this[_key].v);
            }*/
            break;
        case 'pn_setup':
            /*if (this[_key].v == 'Y')
            {
                qbw_pn.setEnable();
            }*/
            break;
        case 'setting':
            this.applySetting();
            break;
        default:
            // console.log('사용안함', _key, this[_key].v);
            break;
    }
}

ns_cs.d.lord.applySetting = function()
{
    let setting = ns_cs.d.lord['setting'];

    // Sound
    ns_sound.volume.bgm = Number(setting['volume_bgm']) * 0.01;
    ns_sound.volume.effect = Number(setting['volume_effect']) * 0.01;
    ns_sound.muted.bgm = setting['sound_bgm'] === 'N';
    ns_sound.muted.effect = setting['sound_effect'] === 'N';

    // Building Title
    ns_castle.setTileTitle(setting['building_title'] === 'Y');

    // Alert Effect
    if (setting['alert_effect_ally'] === 'Y') {
        ns_button.buttons.alert_toggle_ally.unsetClicked();
    } else {
        ns_button.buttons.alert_toggle_ally.setClicked();
    }
    if (setting['alert_effect_enemy'] === 'Y') {
        ns_button.buttons.alert_toggle_enemy.unsetClicked();
    } else {
        ns_button.buttons.alert_toggle_enemy.setClicked();
    }
}

// 자원 정보
ns_cs.d.reso = new nsCsSet();
ns_cs.d.reso.render = function(_key)
{
    if (this.first_render) {
        this.s.main_top_reso_food_curr = new nsObject('.main_top_reso_food_curr');
        this.s.main_top_reso_horse_curr = new nsObject('.main_top_reso_horse_curr');
        this.s.main_top_reso_lumber_curr = new nsObject('.main_top_reso_lumber_curr');
        this.s.main_top_reso_iron_curr = new nsObject('.main_top_reso_iron_curr');
    }

    if (['food_curr', 'horse_curr', 'lumber_curr', 'iron_curr'].includes(_key)) {
        let _curr ;
        if (this[_key].v == null) {
            _curr = 0
        } else {
            if (ns_util.math(this[_key].v).gt(999999)) {
                _curr = ns_util.numberSymbol(this[_key].v);
            } else {
                _curr = ns_util.numberFormat(this[_key].v);
            }
        }
        this.s[`main_top_reso_${_key}`].text(_curr);
    }
}

ns_cs.d.prod = new nsCsSet();
ns_cs.d.prod.render = function(_key)
{
    // console.log(_key, this[_key].v);
    // empty?
}

// 영지 정보
ns_cs.d.terr = new nsCsSet();
ns_cs.d.terr.render = function(_key)
{
    if (this.first_render) {
        this.s.terrtory_manage = new nsObject('.main_top_wrap .main_top_sub_wrap:nth-child(2)');
        this.s.qbig_buy = new nsObject('.main_top_wrap .main_top_sub_wrap:nth-child(3)');

        // this.s.terrtory_title = new nsObject('.main_top_terr_title');
        // this.s.terrtory_position = new nsObject('.main_top_posi_pk');

        this.s.main_top_gold_curr = new nsObject('.main_top_gold_curr');
        this.s.main_top_population_idle = new nsObject('.main_top_population_idle');
    }

    switch (_key) {
        case 'loyalty':
        case 'tax_rate':
            break;
        case 'gold_curr':
            let gold_curr ;
            if (this[_key].v == null) {
                gold_curr = 0
            } else {
                if (ns_util.math(this[_key].v).gt(999999)) {
                    gold_curr = ns_util.numberSymbol(this[_key].v);
                } else {
                    gold_curr = ns_util.numberFormat(this[_key].v);
                }
            }
            this.s.main_top_gold_curr.text(gold_curr);
            break;
        case 'title':
            if (ns_world.objs.world_map) {
                // 월드맵 타일이 있는 경우 이름 즉시 적용. (영지명 변경)
                let _map_tile = ns_world.objs.world_map.find(`#ns_world_xy_${ns_cs.d.lord.main_posi_pk.v}`);
                if (_map_tile.element) {
                    _map_tile.find('.tile_title').text(this[_key].v);
                }
            }
            // this.s.terrtory_title.text(this[_key].v);
            break;
        case 'position':
            // this.s.terrtory_position.text(this[_key].v);
            break;
        case 'wall_director_hero_pk':
            // ns_cs.d.buil_new_data[ns_cs.m.buil['CastleWall'].m_buil_pk] = true;
            // ns_cs.d.bdic.renderPost();
            break;
        case 'wall_staff_hero_pk':
            // ns_cs.d.buil_new_data[ns_cs.m.buil['CastleWall'].m_buil_pk] = true;
            // ns_cs.d.bdic.renderPost();
            break;
        case 'population_curr':
        case 'population_labor_force':
            if (this['population_curr'] && this['population_labor_force']) {
                let _pop_curr = this['population_curr'].v - this['population_labor_force'].v;
                ns_cs.d.terr.set('population_idle', _pop_curr);
                this.s.main_top_population_idle.text(ns_util.numberFormat(_pop_curr));
            }
            break;
    }
}

ns_cs.d.bdic = new nsCsSet();
ns_cs.d.bdic.init_rander = true;
ns_cs.d.bdic.tile = {};
ns_cs.d.bdic.render = function(_key)
{
    if (ns_util.isNumeric(_key)) {
        if (this[_key].status === 'NULL') {
            delete this[_key];
        }
        if (this.tile[_key]) { // 이미 타일이 존재 하는 경우
            this.tile[_key]._element.removeAttribute('class');
            delete this.tile[_key]._element.dataset.pk;
        }
        this.tile[_key] = new nsTile(document.querySelector(`#ns_bdic_${_key}`), 'in', _key);
    }
};

ns_cs.d.bdic.renderPost = function()
{
    // 빈 타일 그리기
    for (let _i of Array.from({length: 33}, (_, i) => i + 1)) {
        if (! this.tile[_i]) {
            this.tile[_i] = new nsTile(document.querySelector(`#ns_bdic_${_i}`), 'in', _i);
        }
    }
    this.init_rander = false;
    // 감춰놓았던 업그레이드 독려 중단 버튼 다시 보이도록
    // show_buil_btns(this[_key].m_buil_pk);
}

ns_cs.d.bdoc = new nsCsSet();
ns_cs.d.bdoc.init_rander = true;
ns_cs.d.bdoc.tile = {};
ns_cs.d.bdoc.render = function(_key)
{
    if (ns_util.isNumeric(_key)) {
        if (this[_key].status === 'NULL') {
            delete this[_key];
        }
        if (this.tile[_key]) { // 이미 타일이 존재 하는 경우
            this.tile[_key]._element.removeAttribute('class');
            delete this.tile[_key]._element.dataset.pk;
        }
        this.tile[_key] = new nsTile(document.querySelector(`#ns_bdoc_${_key}`), 'out', _key);
    }
};

ns_cs.d.bdoc.renderPost = function()
{
    // 타일 설정
    for (let _i of Array.from({length: 33}, (_, i) => i + 1)) {
        if (! this.tile[_i]) {
            this.tile[_i] = new nsTile(document.querySelector(`#ns_bdoc_${_i}`), 'out', _i);
        }
    }
    this.init_rander = false;

    // 감춰놓았던 업그레이드 독려 중단 버튼 다시 보이도록
    // show_buil_btns(this[_key].m_buil_pk);
}

ns_cs.d.time = new nsCsSet();
ns_cs.d.time.render = function(_key)
{
    ns_cs.flag['new_time'] = true;

    // 훈련소 연계
    if (this[_key].queue_type === 'A' && this[_key].status === 'F') {
        // qbw_sound.play('buildforce_comp', { bgmReduce:true });
    } else if (this[_key].queue_type === 'M' && this[_key].status === 'F') {
        // qbw_sound.play('buff', {bgmReduce:true});
    } else if (this[_key].queue_type === 'T' && this[_key].status === 'F') {
        // qbw_sound.play('research_comp', { bgmReduce:true });
        // let dlg = qbw_dlg.dlgs.buil_Technique;
        // dlg.draw_remote();
    } else if (this[_key].queue_type === 'F' && this[_key].status === 'F') {
        // qbw_sound.play('construct_comp', { bgmReduce:true });
    } else if (this[_key].queue_type === 'I' && this[_key].status === 'F') {
        // qbw_sound.play('search_comp', { bgmReduce:true });
        // var dlg = qbw_dlg.dlgs.buil_ReceptionHall;
        // dlg.draw_tab();
    } else if (this[_key].queue_type === 'C' && this[_key].status === 'F') {
        // qbw_sound.play('construct_comp', { bgmReduce:true });
    } else if (this[_key].queue_type === 'X' && this[_key].status === 'F') {
        // qbw_sound.play('construct_comp', { bgmReduce:true });/
        // var dlg = qbw_dlg.dlgs.buil_Military;
        /*if (dlg.visible) {
            dlg.draw_tab();
        }*/
    } else if (this[_key].queue_type === 'E' && this[_key].status === 'F') {
        /*qbw_sound.play('search_comp', { bgmReduce:true });
        var dlg = qbw_dlg.dlgs.buil_ReceptionHall;
        if (dlg.visible) {
            dlg.draw_tab();
        }*/
    } else if (this[_key].queue_type === 'P' && this[_key].status === 'F') {
        // qbw_sound.play('search_comp', {bgmReduce:true});
        // var dlg = qbw_dlg.dlgs.hero_manage;/
        /*if (dlg.visible) {
            dlg.draw_list();
        }*/
    } else if (this[_key].queue_type === 'W' && this[_key].status === 'F') {
        // var dlg = qbw_dlg.dlgs.world_fort;
        // dlg.draw_remote();
    }

    if (this[_key].status === 'F' && this[_key].queue_action === 'Y' && this[_key].queue_type === 'X') {
        // qbw_sound.play('move_comp', {bgmReduce:true});
    }

    if (['F', 'C'].includes(this[_key].status)) {
        delete this[_key];
    }
}

ns_cs.d.time.renderPost = function()
{
    let job_count = 0;
    let troop_count = 0;
    let troop_y_count = 0;
    // let target = $('#qbw_btn_main_counter_troop_list');

    for (let [k,d] of Object.entries(ns_cs.d.time)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }

        if (ns_engine.game_data.cpp && d.posi_pk !== ns_engine.game_data.cpp) {
            continue;
        }

        // TODO 타이머 타입 극혐이다. 필수로 단어 형태로 변경해라.
        switch (d.queue_type) {
            case 'E':
            case 'I':
                job_count++;
                break;
            case 'C':
            case 'T':
            case 'A':
            case 'M':
            case 'F':
                job_count++;
                break;
            case 'X':
            case 'Y':
                troop_count++;
                if (d.queue_type === 'Y') {
                    troop_y_count++;
                }
                break;
        }
    }

    if (ns_util.math(job_count).gt(0)) {
        ns_button.buttons.main_counter_job_list.obj.element.dataset.count = String(job_count);
        ns_button.buttons.main_counter_job_list.obj.addCss('main_flag_new');
    } else {
        delete ns_button.buttons.main_counter_job_list.obj.element.dataset.count;
        ns_button.buttons.main_counter_job_list.obj.removeCss('main_flag_new');
    }

    if (ns_util.math(troop_count).gt(0)) {
        ns_button.buttons.main_counter_troop_list.obj.element.dataset.count = String(troop_count);
        ns_button.buttons.main_counter_troop_list.obj.addCss('main_flag_new');
    } else {
        delete ns_button.buttons.main_counter_troop_list.obj.element.dataset.count;
        ns_button.buttons.main_counter_troop_list.obj.removeCss('main_flag_new');
    }

    /*if (troop_y_count <= 0) {
        clearInterval(ns_engine.interval.attack_warning_id);
        ns_engine.interval.attack_warning_id = null;
        // target.css('opacity', '1');
    }*/
};

ns_cs.d.tech = new nsCsSet();
ns_cs.d.tech.render = function(_key)
{

}

ns_cs.d.lord_tech = new nsCsSet();
ns_cs.d.lord_tech.render = function(_key)
{

}

ns_cs.d.hero = new nsCsSet();
ns_cs.d.hero.render = function(_key)
{
    if (this[_key].status === 'NULL') {
        delete this[_key];
    }
    if (this[_key]) {
        ns_hero.deckReload();
        if (ns_dialog.dialogs.hero_manage.visible) {
            ns_dialog.dialogs.hero_manage.drawList();
        }
        if (ns_dialog.dialogs.hero_card.visible && ns_dialog.dialogs.hero_card?.main_card && ns_util.math(ns_dialog.dialogs.hero_card.main_card.data.hero_pk).eq(_key)) {
            ns_dialog.dialogs.hero_card.main_card.update(this[_key]);
        }
    }
};

ns_cs.d.army = new nsCsSet();
ns_cs.d.army.render = function(_key)
{
    /*if (qbw_dlg.dlgs.troop_order.visible && _key !== 'posi_pk') {
        // let id = 'qbw_army_' + _key;
        // let dlg = qbw_dlg.dlgs.troop_order;
        //dlg.s[id].html((this[_key].v == null)? 0 : this[_key].v);
    }*/
}

ns_cs.d.army_medi = new nsCsSet();
ns_cs.d.army_medi.render = function(_key)
{
}

ns_cs.d.fort = new nsCsSet();
ns_cs.d.fort.render = function(_key)
{

}

ns_cs.d.item = new nsCsSet();
ns_cs.d.item.render = function(_key)
{
    if (! ns_util.isNumeric(_key)) {
        return;
    }
    // 이벤트 체크용
    if (ns_dialog.dialogs.treasure_event.materials.includes(Number(_key))) {
        if (ns_dialog.dialogs.treasure_event.checkMaterial()) {
            ns_button.buttons.banner_treasure_event.obj.addCss('main_flag_new');
        } else {
            ns_button.buttons.banner_treasure_event.obj.removeCss('main_flag_new');
        }
    }
    if (this[_key].v === null) {
        delete this[_key];
    }
    let dialog = ns_dialog.dialogs.my_item;
    try {
        let m = ns_cs.m.item[_key], tab;
        if (m.display_type === 'P') {
            tab = 'production';
        } else if (m.display_type === 'S') {
            tab = 'speedup';
        } else if (m.display_type === 'L') {
            tab = 'lord';
        } else if (m.display_type === 'D') {
            tab = 'special';
        } else if (m.display_type === 'B') {
            tab = 'package';
        } else if (m.display_type === 'H') {
            tab = 'hero';
        }

        dialog.tab_first_clicked[tab] = false;

        if (this[_key] && this[_key].item_cnt != null && ns_dialog.dialogs.my_item.visible) {
            dialog.drawList(dialog.current_tab, dialog.current_type);
        }
    } catch (e) {
        console.error(_key, this[_key], ns_cs.m.item[_key]);
    }
}

ns_cs.d.item_buy = new nsCsSet();
ns_cs.d.item_buy.render = function(_key)
{
}

ns_cs.d.cash = new nsCsSet();
ns_cs.d.cash.render = function(_key)
{
    let v = (this[_key].v == null) ? 0 : ns_util.numberFormat(this[_key].v);
    document.querySelector(`.main_top_${_key}`).innerText = v;
    document.querySelectorAll(`.ns_${_key}`).forEach((o, k) => {
        o.innerText = v;
    });
}

ns_cs.d.ques = new nsCsSet();
ns_cs.d.ques.render = function(_key)
{
    if (this[_key].v === null) {
        delete this[_key];
    }
}

ns_cs.d.ques.renderPost = function ()
{
    // 클리어한 임무에 대한 배지 표기
    let complete_count = Object.entries(ns_cs.d.ques).filter(d => ns_util.isNumeric(d[0]) && d[1].status === 'C').length;
    if (complete_count > 0) {
        ns_button.buttons.main_quest.obj.element.dataset.count = String(complete_count);
        ns_button.buttons.main_quest.obj.addCss('main_flag_new');
    } else {
        delete ns_button.buttons.main_quest.obj.element.dataset.count;
        ns_button.buttons.main_quest.obj.removeCss('main_flag_new');
    }
    //퀘스트가 완료 됬을시 퀘스트 뷰 갱신
    ns_quest.drawButtons();
}

ns_cs.d.npc_supp = new nsCsSet();
ns_cs.d.npc_supp.render = function(_key)
{

}

ns_cs.d.push = new nsCsSet();
ns_cs.d.push.render = function(_key)
{
    switch (_key) {
        case 'ARMY_POINT':
            if (ns_dialog.dialogs.lord_info.visible) {
                ns_dialog.dialogs.lord_info.cont_obj.lord_info_table.find('.lord_info_army_point').text(ns_util.numberFormat(this[_key].v));
            }
            break;
        case 'TROOP_INFO':
            let dialog = ns_dialog.dialogs.build_CastleWall;
            dialog.fighting_spirit = this[_key].fightingSpirit;
            dialog.alli_army = this[_key].alli_army;
            dialog.total_army = this[_key].total_army;
            break;
        case 'QUEST_COMPLETE':
            if (ns_cs.d.ques[this[_key].v]) {
                ns_cs.d.ques[this[_key].v].status = 'C';
                let dialog = ns_dialog.dialogs.quest;
                if (dialog.visible === true) {
                    dialog.drawTab();
                }

                ns_cs.d.ques.renderPost();
            }
            break;
        case 'QUEST_PROGRESS':
            if (ns_cs.d.ques[this[_key].v]) {
                ns_cs.d.ques[this[_key].v].status = 'P';
                let dialog = ns_dialog.dialogs.quest;
                if (dialog.visible === true) {
                    dialog.drawTab();
                }

                ns_cs.d.ques.renderPost();
            }
            break;
        case 'TRUCE_UPDATE':
            if (ns_world.objs.world_map) {
                // 월드맵 타일이 있는 경우 (영지 보호)
                let _map_tile = ns_world.objs.world_map.find(`#ns_world_xy_${ns_cs.d.lord.main_posi_pk.v}`);
                if (_map_tile.element) {
                    if (this[_key].status === 'P') {
                        _map_tile.addCss('world_truce');
                    } else {
                        _map_tile.removeCss('world_truce');
                    }
                }
            }
            break;
        case 'TRADE_COMPLETE_DELIVERY':
        case 'TRADE_LIST_UPDATE':
            if (ns_dialog.dialogs.build_TradeDept.visible) {
                ns_dialog.dialogs.build_TradeDept.drawTab();
            }
            break;
        case 'COUNSEL_DATA':
            ns_dialog.dialogs.counsel.encounter_data.push({status:this[_key].status, type:this[_key].encounter_type, value:this[_key].encounter_value, cmd_hero:this[_key].cmd_hero, find_hero:this[_key].find_hero });
            break;
        case 'COUNSEL_DATA_SUPPRESS':
            ns_dialog.dialogs.counsel.suppress_status = this[_key].status;
            break;
        case 'HAPPY_NEW_YEAR_EVENT':
            // qbw_dlg.dlgs.main_event.happy_new_year_event = true;
            break;
        case 'ALLIANCE':
            ns_engine.game_data.alliance_status = true;
            // qbw_btn.btns.main_alliance.obj.show();
            // qbw_btn.btns.main_letter.obj.hide();
            break;
        case 'TOAST':
            ns_toast.add(this[_key]);
            break;
        case 'PACKAGE_NOTICE':
            ns_cs.getPackage(this[_key].m_package_pk);
            break;
        case 'PACKAGE_LIST':
            ns_cs.drawPackageButton(this[_key].list, this[_key]?.m_package_pk, this[_key].first_popup);
            break;
        case 'POINT_SERVER_CHECK':
            ns_engine.game_data.point_server_check = this[_key].v;
            break;
        case 'RAID_WARNING':
            // qbw_dlg.dlgs.raid_list.set_raid_warning(this[_key].v);
            break;
        case 'NPC_POINT':
            /*if (this[_key].v === true) {
                ns_button.buttons.main_npc_point.obj.show();
            }*/
            break;
        case 'TIME_EVENT_START': // TODO 오타있었음. 나중에 찾아서 수정해라.
            // qbw_btn.btns.main_time_buff_event.obj.css({'visibility':'visible'});
            break;
        case 'GIFT_EVENT':
            ns_button.buttons.main_time_event.event_info = this[_key]['event_info'];
            if (this[_key]['event_info'] === 'Y') {
                ns_button.buttons.main_time_event.obj.show();
                ns_button.buttons.main_time_event.obj.setBlink();
            } else {
                ns_button.buttons.main_time_event.obj.hide();
            }
            break;
        case 'LP_REQUEST':
            ns_engine.lpRequest();
            break;
        case 'PLAY_SOUND':
            ns_sound.play(String(this[_key].v).toLowerCase());
            break;
        case 'PLAY_EFFECT':
            if (this[_key].type === 'construction') {
                switch (this[_key].castle_type) {
                    case 'I': ns_cs.d.bdic.tile[this[_key].castle_pk].buildComplete(); break;
                    case 'O': ns_cs.d.bdoc.tile[this[_key].castle_pk].buildComplete(); break;
                }
            } else if (this[_key].type === 'battle') {
                let [x, y] = this[_key].posi_pk.split('x');
                let _coords_key = `${x}x${y}`;
                if (ns_world.coords.has(_coords_key)) {
                    ns_world.coords.get(_coords_key)?.battleEffect();
                }
            }
            break;
        case 'PRESET':
            ns_dialog.dialogs.troop_order_preset.preset_list = [];
            for (let o of Object.values(this[_key])) {
                if (typeof o === 'object') {
                    ns_dialog.dialogs.troop_order_preset.preset_list.push(o);
                }
            }
            ns_dialog.dialogs.troop_order.drawShortcut();
            ns_dialog.dialogs.troop_order_preset.drawPresetList();
            break;
        default:
            console.log('push', _key, this[_key]);
            break;
    }
}

ns_cs.d.ally_rela = new nsCsSet();

ns_cs.d.alli = new nsCsSet();
ns_cs.d.alli.render = function(_key)
{
}

ns_cs.d.troop = new nsCsSet();
ns_cs.d.troop.render = function(_key)
{
    if (! ns_util.isNumeric(_key)) {
        return;
    }
    if (this[_key].v === null) {
        ns_world.lineRemove(_key);
        delete this[_key];
    }
};

ns_cs.d.troop.renderPost = function()
{
    ns_world.drawMoveTroop();
};

ns_cs.d.queue = new nsCsSet();
ns_cs.d.queue.render = function(_key)
{
    if (this[_key].v === null) {
        delete this[_key];
    }
}

ns_cs.d.queue.renderPost = function()
{
};

ns_cs.d.event = new nsCsSet();
ns_cs.d.event.render = function(_key)
{
    if (this[_key].v === null) {
        delete this[_key];
    }
}

ns_cs.d.event.renderPost = function()
{
    ns_dialog.dialogs.main_event.checkDot();
    if (ns_cs.d.event?.['occupation_point_enable']?.v !== true) {
        ns_button.buttons.main_occupation_point.obj.hide();
    } else {
        ns_button.buttons.main_occupation_point.obj.show();
    }
};


ns_cs.d.pickup = new nsCsSet();
ns_cs.d.pickup.render = function(_key)
{
    if (this[_key].v === null) {
        delete this[_key];
    }
}

ns_cs.d.pickup.renderPost = function()
{
};