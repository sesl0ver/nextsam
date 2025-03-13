ns_dialog.dialogs.world_detail = new nsDialogSet('world_detail', 'dialog_pop', 'size-small', { do_content_scroll: false, do_close_all: false });
ns_dialog.dialogs.world_detail.new_territory = false;
ns_dialog.dialogs.world_detail.my_troo_pk = null;
ns_dialog.dialogs.world_detail.my_camp_troop = null;
ns_dialog.dialogs.world_detail.territory_info = null;

ns_dialog.dialogs.world_detail.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);

    this.cont_obj.assign_hero_area = new nsObject('.assign_hero_area', this.obj);
    this.cont_obj.production_area = new nsObject('.production_area', this.obj);
    this.cont_obj.occupation_area = new nsObject('.occupation_area', this.obj);

    this.cont_obj.distance = new nsObject('.distance', this.obj);
    this.cont_obj.state = new nsObject('.state', this.obj);

    this.cont_obj.lord_name = new nsObject('.lord_name', this.obj);
    this.cont_obj.power = new nsObject('.power', this.obj);
    this.cont_obj.level = new nsObject('.level', this.obj);

    this.cont_obj.npc_point_detail_wrap = new nsObject('.npc_point_detail_wrap', this.obj);
    this.cont_obj.occupation_date = new nsObject('.occupation_date', this.obj);
}

ns_dialog.dialogs.world_detail.draw = function()
{
    let coords = this.data.coords;
    let coords_title = '';
    if (coords._lord_name && coords._type === 'T') {
        coords_title = `${coords._title} (${coords._posi_pk})`;
    } else if (coords._type === 'D') {
        coords_title = (! ns_cs.d.npc_supp[coords._posi_pk]) ? codeset.t('valley', coords._type) : codeset.t('valley', 'NPC_SUPP');
        coords_title = `${coords_title} (${coords._posi_pk})`;
    } else {
        coords_title = `${codeset.t('valley', coords._type)} ${ns_i18n.t('level_word', [coords._level])} (${coords._posi_pk})`;
    }
    this.cont_obj.content_pop_title.text(coords_title);

    if (coords._type === 'T' || coords._type === 'N' || coords._type === 'P') {
        this.cont_obj.assign_hero_area.show();
        this.cont_obj.production_area.hide();
    } else {
        this.cont_obj.production_area.show();
        this.cont_obj.assign_hero_area.hide();
    }

    if (coords._type === 'P') {
        this.cont_obj.npc_point_detail_wrap.show();
    } else {
        this.cont_obj.npc_point_detail_wrap.hide();
    }
    this.cont_obj.occupation_area.hide();

    // 군주명 초기화
    this.cont_obj.lord_name.text('-');

    // 거리구하기
    this.cont_obj.distance.text(ns_world.distanceValue(ns_engine.game_data.cpp, coords._posi_pk) + ns_i18n.t('distance_unit'));
    // 지역
    this.cont_obj.state.text(coords._state);

    // 생산량 구하기
    let production_desc = '';
    if (['F', 'G', 'L', 'M', 'R'].includes(coords._type)) {
        let m = ns_cs.m.prod_vall[coords._type][coords._level];
        for (let _type of ['food', 'horse', 'lumber', 'iron']) {
            if (ns_util.math(m[_type]).gt(0)) {
                if (production_desc !== '') {
                    production_desc += '<br />';
                }
                production_desc += `<span class='resource_${_type}'></span> +${ns_util.numberFormat(m[_type])}/h`;
            }
        }
    } else {
        // ['A', 'E'].includes(coords._type) "영지 개척 가능" 이었으나 군주성이 1개로 고정되면서 비워둠
        production_desc = '-';
    }
    this.cont_obj.production_area.find('.production_description').html(production_desc);

    if (['F', 'G', 'L', 'M', 'R', 'A', 'E'].includes(coords._type)) {
        let point_text = ns_util.numberFormat(coords._current_point);
        this.cont_obj.occupation_area.find('.remain_occupation_point').html(point_text + 'P');
        this.cont_obj.occupation_area.find('.hours_point').html(`+${ns_cs.m.prod_vall[coords._type][coords._level].occupation_point}P/h`);
    }
    this.cont_obj.occupation_area.show();

    ns_button.buttons.world_detail_lord_info.setDisable();
    ns_button.buttons.world_detail_favorite.setDisable();

    // ns_button.buttons.world_detail_new.obj.hide();
    ns_button.buttons.world_detail_enter.obj.hide();
    // ns_button.buttons.world_detail_occupation_battle.obj.hide();
    ns_button.buttons.world_detail_attack.obj.hide();
    ns_button.buttons.world_detail_scout.obj.hide();

    ns_button.buttons.world_detail_camp_troop.obj.hide();
    ns_button.buttons.world_detail_valley_give_up.obj.hide();
    ns_button.buttons.world_detail_terr_change.obj.hide();
    ns_button.buttons.world_detail_transport.obj.hide();
    ns_button.buttons.world_detail_reinforce.obj.hide();
    ns_button.buttons.world_detail_supply.obj.hide();
    ns_button.buttons.world_detail_fort.obj.hide();

    let post_data = {};
    post_data['target_posi_pk'] = coords._posi_pk;
    ns_xhr.post('/api/world/detail', post_data, this.drawRemote);
}

ns_dialog.dialogs.world_detail.erase = function()
{
    ns_dialog.close('world_favorite_add');
    this.new_territory = false;
    this.my_troo_pk = null;
    this.my_camp_troop = null;
    this.territory_info = null;
    this.data = null;
}

ns_dialog.dialogs.world_detail.drawRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.world_detail;
    let coords = dialog.data.coords;

    dialog.territory_info = _data;

    let coords_title = '';
    if (_data?.lord_detail?.lord_name  && _data.dst_posi_info.type === 'T') {
        coords_title = ns_text.convertPositionName(_data.dst_posi_info.name);
    } else if (_data.dst_posi_info.type === 'D') {
        coords_title = (! ns_cs.d.npc_supp[coords._posi_pk]) ? codeset.t('valley', coords._type) : codeset.t('valley', 'NPC_SUPP');
        coords_title = `${coords_title} (${coords._posi_pk})`;
    } else {
        coords_title = `${ns_i18n.t('level_word', [_data.dst_posi_info.level])} ${codeset.t('valley', coords._type)} (${coords._posi_pk})`;
    }
    dialog.cont_obj.content_pop_title.text(coords_title);

    dialog.cont_obj.occupation_date.text('-');
    if (_data.dst_posi_info.type === 'P') {
        let occupation_date = (_data?.occupation_date) ? ns_util.getDateFormat(_data.occupation_date, 'YYYY-MM-DD hh:mm:ss') : '-';
        dialog.cont_obj.occupation_date.text(occupation_date);
    }

    // 성주
    let assign_hero_name = '-';
    if (_data?.terr_hero) {
        assign_hero_name = ns_hero.getNameWithLevel(_data.terr_hero.hero_pk, _data.terr_hero.m_hero_pk, _data.terr_hero.level);
    }
    dialog.cont_obj.assign_hero_area.find('.assign_hero_name').text(assign_hero_name);

    // 군주 정보
    if (_data?.lord_detail) {
        if ( _data.dst_posi_info.relation === 'NPC' ) { // 황건적 관련된 위치라면
            dialog.cont_obj.lord_name.text(ns_i18n.t('yellow_turban'));
            dialog.cont_obj.power.text('-');
            dialog.cont_obj.level.text('-');
        } else { // 일반 유저와 관련된 위치라면
            let d = _data.lord_detail;
            dialog.cont_obj.lord_name.text(d.lord_name);
            dialog.cont_obj.power.text(ns_util.numberFormat(d.power));
            dialog.cont_obj.level.text(`Lv.${d.level}`);
            ns_button.buttons.world_detail_lord_info.setEnable();
        }
    } else if (_data.dst_posi_info.type === 'P') {
        if (_data.dst_posi_info.relation === 'NPC' ) { // 황건적 관련된 위치라면
            dialog.cont_obj.lord_name.text(ns_i18n.t('world_detail_npc_deploying')); // NPC 주둔 중
        } else {
            if (_data.dst_posi_info.my_camp_troop === 'Y') {
                dialog.cont_obj.lord_name.text(ns_i18n.t('world_detail_occupation'));
            } else {
                dialog.cont_obj.lord_name.text(ns_i18n.t('world_detail_other_occupation'));
            }
        }
        dialog.cont_obj.power.text('-');
        dialog.cont_obj.level.text('-');
    }

    // 버튼제어
    dialog.setButtonControl(_data);
}

ns_dialog.dialogs.world_detail.setButtonControl = function(_data)
{
    let dialog = ns_dialog.dialogs.world_detail;
    let coords = dialog.data.coords;

    // 버튼제어
    if (_data?.dst_posi_info) {
        let d = _data.dst_posi_info;
        ns_button.buttons.world_detail_favorite.setEnable(); // 즐겨찾기 버튼

        // 영지건설, 영지관리
        if (d.relation === 'MIME' && ['A', 'E'].includes(d.type)) {
            dialog.my_troo_pk = d.my_troo_pk;
        } else if (d.relation === 'MIME' && d.type === 'T') {
            ns_button.buttons.world_detail_enter.obj.show();
        }

        // 부대관리
        if (d.my_camp_troop === 'Y') {
            if (d.my_troo_pk) {
                dialog.my_troo_pk = d.my_troo_pk;
                dialog.my_camp_troop = d.my_camp_troop;
                ns_button.buttons.world_detail_camp_troop.obj.show();
            } else {
                ns_button.buttons.world_detail_valley_give_up.obj.show();
            }
        }

        // 보급
        if (d.my_camp_troop === 'Y' && d.my_troo_pk) {
            ns_button.buttons.world_detail_supply.obj.show();
        }

        // 수송
        if (d.type === 'T' && d.relation === 'MIME' && coords._posi_pk !== ns_engine.game_data.cpp) {
            ns_button.buttons.world_detail_transport.obj.show();
        }

        // 지원, 방어시설 - 내 영지/자원지
        if (d.relation === 'MIME') {
            if (coords._posi_pk !== ns_engine.game_data.cpp) {
                ns_button.buttons.world_detail_reinforce.obj.show();
            }
            if (['F', 'G', 'L', 'M', 'R'].includes(coords._type) && coords._posi_pk !== ns_engine.game_data.cpp) {
                ns_button.buttons.world_detail_fort.obj.show();
            }
        }

        // 다른 영지 수송, 지원
        if (d.type === 'T' && d.relation === 'ALLY') {
            // ns_button.buttons.world_detail_supply.obj.show();
            ns_button.buttons.world_detail_transport.obj.show();
            ns_button.buttons.world_detail_reinforce.obj.show();
        }

        // 공격, 정찰 - 보호모드가 아니어야함.
        if (d.truce === 'N' && (d.relation === 'NPC' || d.relation === 'LORD')) {
            ns_button.buttons.world_detail_attack.obj.show();
            ns_button.buttons.world_detail_scout.obj.show();
        }
    }
}

ns_dialog.dialogs.world_detail.timerHandler = function (_recursive) {
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';
    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.world_detail.timerHandlerReal, 1000, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.world_detail.timerHandlerReal = function () {
    let dialog = ns_dialog.dialogs.world_detail;
    let coords = dialog.data.coords;
    if (!coords._lord_pk || !['F', 'G', 'L', 'M', 'R', 'A', 'E'].includes(coords._type)) {
        return;
    }
    let _point = ns_util.math(ns_timer.now()).minus(coords._update_point_dt).mul(ns_cs.m.prod_vall[coords._type][coords._level].occupation_point).div(3600).toFixed(2);
    _point = ns_util.math(coords._current_point).minus(_point).number;
    if (ns_util.math(_point).lte(0)) {
        _point = 0;
    }
    dialog.cont_obj.occupation_area.show().find('.remain_occupation_point').html(_point + 'P');
};

/* ********** */
ns_button.buttons.world_detail_close = new nsButtonSet('world_detail_close', 'button_pop_close', 'world_detail', { base_class: ns_button.buttons.common_close });
ns_button.buttons.world_detail_sub_close = new nsButtonSet('world_detail_sub_close', 'button_full', 'world_detail', { base_class: ns_button.buttons.common_sub_close });

/* ************************************************** */
ns_button.buttons.world_detail_lord_info = new nsButtonSet('world_detail_lord_info', 'button_text', 'world_detail');
ns_button.buttons.world_detail_lord_info.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_detail;
    let coords = dialog.data.coords;
    let d = dialog.territory_info.dst_posi_info;
    if (d.type === 'D' || d.type === 'P') {
        return;
    }
    switch (d.relation) {
        case 'NPC':
            return;
        case 'MIME':
            if (coords?._lord_pk) {
                ns_dialog.setDataOpen('lord_info', { lord_pk: coords._lord_pk, lord_name: coords._lord_name });
            }
            break;
        default:
            ns_dialog.setDataOpen('lord_info', { lord_pk: coords._lord_pk, lord_name: coords._lord_name });
            break;
    }
    ns_dialog.close('world_detail');
};

ns_button.buttons.world_detail_favorite = new nsButtonSet('world_detail_favorite', 'button_favorite', 'world_detail');
ns_button.buttons.world_detail_favorite.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_detail;
    ns_dialog.setDataOpen('world_favorite_add', { coords: dialog.data.coords });
}

ns_button.buttons.world_detail_new = new nsButtonSet('world_detail_new', 'button_pop_normal', 'world_detail');
ns_button.buttons.world_detail_new.mouseUp = function(_e)
{
    ns_dialog.open('message', '준비 중 입니다.');
    return;
    // TODO 확장영지는 사용하지 않음. 다만 차후 요새라는 새로운 영지타입이 추가될 수 있음. 그때 같이 사용하도록...
    let dialog = ns_dialog.dialogs.world_detail;
    let coords = dialog.data.coords;
    if (dialog.new_territory) {
        ns_dialog.setDataOpen('confirm', { text: '', // system_text.message.founding
            okFunc:() => {
                let post_data = {};
                post_data['target_posi_pk'] = coords._posi_pk;
                ns_xhr.post('/api/position/founding', post_data, function(_data, _status)
                {
                    if(! ns_xhr.returnCheck(_data)) {
                        return;
                    }
                    _data = _data['ns_xhr_return']['add_data'];

                    ns_dialog.close('world_detail');
                    ns_dialog.setDataOpen('message', ''); // system_text.message.founding_complete

                    // 대륙갱신(view 중일때 즉시 갱신, 외성/내성 view 중일때 갱신 타이밍 조절 - 자동)
                    ns_engine.cfg.world_tick = 1;
                    ns_timer.worldReloadTick();
                });
            }
        });
    } else {
        ns_dialog.open('new_territory');
    }
}

ns_button.buttons.world_detail_attack = new nsButtonSet('world_detail_attack', 'button_pop_normal', 'world_detail');
ns_button.buttons.world_detail_attack.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_detail;
    let cmd_type = this.tag_id.split('_').pop();

    ns_dialog.setDataOpen('troop_order', { coords: dialog.data.coords, cmd_type: cmd_type });
    ns_dialog.close('world_detail');
}
ns_button.buttons.world_detail_scout = new nsButtonSet('world_detail_scout', 'button_pop_normal', 'world_detail', { base_class: ns_button.buttons.world_detail_attack });
ns_button.buttons.world_detail_transport = new nsButtonSet('world_detail_transport', 'button_pop_normal', 'world_detail', { base_class: ns_button.buttons.world_detail_attack });
ns_button.buttons.world_detail_reinforce = new nsButtonSet('world_detail_reinforce', 'button_pop_normal', 'world_detail', { base_class: ns_button.buttons.world_detail_attack });
ns_button.buttons.world_detail_supply= new nsButtonSet('world_detail_supply', 'button_pop_normal', 'world_detail', { base_class: ns_button.buttons.world_detail_attack });

ns_button.buttons.world_detail_enter = new nsButtonSet('world_detail_enter', 'button_pop_normal', 'world_detail');
ns_button.buttons.world_detail_enter.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_detail;
    let coords = dialog.data.coords;
    let territory_info = dialog.territory_info;

    if (coords._posi_pk === ns_engine.game_data.cpp) {
        ns_engine.toggleWorld();
    } else if (territory_info.dst_posi_info.relation === 'MIME' && territory_info.dst_posi_info.type === 'T') {
        ns_dialog.open('message', '준비 중 입니다.');
        // TODO 영지가 하나로 고정되면서 영지 변경은 사용하지 않게됨.
        //  차후 확인하여 버튼 비활성화등의 처리가 필요.
        // ns_engine.changeTerritory(coords._posi_pk);
        // ns_world.current_posi_pk = coords._posi_pk;
        // ns_world.goto_map = true;
        // ns_world.init();
    }
}

ns_button.buttons.world_detail_camp_troop = new nsButtonSet('world_detail_camp_troop', 'button_pop_normal', 'world_detail');
ns_button.buttons.world_detail_camp_troop.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_detail;
    let coords = dialog.data.coords;
    if (dialog.my_troo_pk) {
        if (dialog.my_camp_troop === 'Y') {
            ns_dialog.setDataOpen('world_troop_camp_list', { target_posi_pk: coords._posi_pk });
        } else {
            ns_dialog.setDataOpen('troop_view', { type:'camp', 'troo_pk': dialog.my_troo_pk});
        }
    } else {
        ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_deployed_troop_information_none') }); // 주둔부대 정보가 없습니다.
    }
}

ns_button.buttons.world_detail_valley_give_up = new nsButtonSet('world_detail_valley_give_up', 'button_pop_normal', 'world_detail');
ns_button.buttons.world_detail_valley_give_up.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_give_up_confirm'),
        okFunc: () => {
            let dialog = ns_dialog.dialogs.world_detail;
            let coords = dialog.data.coords;
            let post_data = {};
            post_data['target_posi_pk'] = coords._posi_pk;
            ns_xhr.post('/api/position/giveUp', post_data, function(_data, _status)
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];

                ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_give_up_complete') });
                ns_dialog.close('world_detail');

                // 월드맵 변경된값 적용
                ns_engine.cfg.world_tick = 1;
                ns_timer.worldReloadTick();
            }, { useProgress: true });
        }
    });
}

ns_button.buttons.world_detail_terr_change = new nsButtonSet('world_detail_terr_change', 'button_pop_normal', 'world_detail');

ns_button.buttons.world_detail_fort = new nsButtonSet('world_detail_fort', 'button_pop_normal', 'world_detail');
ns_button.buttons.world_detail_fort.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_detail;
    let coords = dialog.data.coords;

    ns_dialog.setDataOpen('world_fort', { posi_pk: coords._posi_pk });
}

/* ************************************************** * /
ns_dialog.dialogs.new_territory = new nsDialogSet('new_territory', 'dialog_pop', 'pop', {do_content_scroll:false, do_close_all:false});

ns_dialog.dialogs.new_territory.cache_contents = function()
{
    this.s.cont_pop_title = this.obj.find('.cont_pop_title');

    this.s.cont_my_camp_troop = this.obj.find('.cont_my_camp_troop');
    this.s.cont_position_cnt = this.obj.find('.cont_position_cnt');
    this.s.cont_my_item_cnt = this.obj.find('.cont_my_item_cnt');
    this.s.cont_my_food = this.obj.find('.cont_my_food');
    this.s.cont_my_horse = this.obj.find('.cont_my_horse');
    this.s.cont_my_lumber = this.obj.find('.cont_my_lumber');
    this.s.cont_my_iron = this.obj.find('.cont_my_iron');
    this.s.cont_my_gold = this.obj.find('.cont_my_gold');

    this.s.cont_pop_title.text('영지개척');

    let cond = ns_cs.m.cond[100000];

    this.obj.find('.cont_need_item').text(ns_cs.m.item[cond.m_item_pk].title);
    this.obj.find('.cont_need_food').text(qbw_util_numberFormat(cond.build_food));
    this.obj.find('.cont_need_horse').text(qbw_util_numberFormat(cond.build_horse));
    this.obj.find('.cont_need_lumber').text(qbw_util_numberFormat(cond.build_lumber));
    this.obj.find('.cont_need_iron').text(qbw_util_numberFormat(cond.build_iron));
    this.obj.find('.cont_need_gold').text(qbw_util_numberFormat(cond.build_gold));
}

ns_dialog.dialogs.new_territory.draw = function()
{
    let cond = ns_cs.m.cond[100000];

    // 주둔부대
    let troop_status = ns_dialog.dialogs.world_detail.my_troo_pk > 0 ? '주둔':'없음';
    this.s.cont_my_camp_troop.text(troop_status);
    if (ns_dialog.dialogs.world_detail.my_troo_pk)
    {
        this.s.cont_my_camp_troop.removeClass('qbw_text_condtion_no');
    } else {
        this.s.cont_my_camp_troop.addClass('qbw_text_condtion_no');
    }
    // 영지 슬롯
    this.s.cont_position_cnt.text(ns_cs.getPositionCount() - parseInt(ns_cs.d.lord.position_cnt.v));
    if (ns_cs.getPositionCount() - parseInt(ns_cs.d.lord.position_cnt.v) >= 1)
    {
        this.s.cont_position_cnt.removeClass('qbw_text_condtion_no');
    } else {
        this.s.cont_position_cnt.addClass('qbw_text_condtion_no');
    }
    // 태수의 인장
    this.s.cont_my_item_cnt	.text(ns_cs.d.item[cond.m_item_pk] ? ns_cs.d.item[cond.m_item_pk].item_cnt : 0);
    if (ns_cs.d.item[cond.m_item_pk])
    {
        this.s.cont_my_item_cnt.removeClass('qbw_text_condtion_no');
    } else {
        this.s.cont_my_item_cnt.addClass('qbw_text_condtion_no');
    }
    // 자원
    this.s.cont_my_food.text(qbw_util_numberFormat(ns_cs.getResourceInfo('food_curr')));
    if (cond.build_food <= ns_cs.getResourceInfo('food_curr'))
    {
        this.s.cont_my_food.removeClass('qbw_text_condtion_no');
    } else {
        this.s.cont_my_food.addClass('qbw_text_condtion_no');
    }
    this.s.cont_my_horse.text(qbw_util_numberFormat(ns_cs.getResourceInfo('horse_curr')));
    if (cond.build_horse <= ns_cs.getResourceInfo('horse_curr'))
    {
        this.s.cont_my_horse.removeClass('qbw_text_condtion_no');
    } else {
        this.s.cont_my_horse.addClass('qbw_text_condtion_no');
    }
    this.s.cont_my_lumber.text(qbw_util_numberFormat(ns_cs.getResourceInfo('lumber_curr')));
    if (cond.build_lumber <= ns_cs.getResourceInfo('lumber_curr'))
    {
        this.s.cont_my_lumber.removeClass('qbw_text_condtion_no');
    } else {
        this.s.cont_my_lumber.addClass('qbw_text_condtion_no');
    }
    this.s.cont_my_iron.text(qbw_util_numberFormat(ns_cs.getResourceInfo('iron_curr')));
    if (cond.build_iron <= ns_cs.getResourceInfo('iron_curr'))
    {
        this.s.cont_my_iron.removeClass('qbw_text_condtion_no');
    } else {
        this.s.cont_my_iron.addClass('qbw_text_condtion_no');
    }
    this.s.cont_my_gold.text(qbw_util_numberFormat(ns_cs.getTerritoryInfo('gold_curr')));
    if (cond.build_gold <= ns_cs.getTerritoryInfo('gold_curr'))
    {
        this.s.cont_my_gold.removeClass('qbw_text_condtion_no');
    } else {
        this.s.cont_my_gold.addClass('qbw_text_condtion_no');
    }

    $('#wrap_trans').show();

    this.customShow();

    this.contentRefresh();
}

ns_dialog.dialogs.new_territory.undraw = function()
{
    $('#wrap_trans').hide();
    this.customHide();
}

/* ************************* * /
ns_button.buttons.new_territory_close = new nsButtonSet('new_territory_close', 'button_pop_close', 'new_territory', {base_class:ns_button.buttons.common_close});
*/
/* ************************************************** */
ns_dialog.dialogs.world_favorite = new nsDialogSet('world_favorite', 'dialog_pop', 'size-small', { do_close_all: false });

ns_dialog.dialogs.world_favorite.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.content_pop_title.text(ns_i18n.t('shortcut'));

    this.cont_obj.current_count = new nsObject('.current_count', this.obj);
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.top_button_area = new nsObject('.top_button_area', this.obj);
    this.cont_obj.tbody = new nsObject('table tbody', this.obj);
};

ns_dialog.dialogs.world_favorite.draw = function()
{
    ns_button.toggleGroupSingle(ns_button.buttons.tab_suppress_position);
    this.cont_obj.current_count.text('0/30');
    this.drawTab();
};

ns_dialog.dialogs.world_favorite.drawTab = function(_e)
{
    let dialog = ns_dialog.dialogs.world_favorite;

    dialog.cont_obj.tbody.empty();

    // tab processing
    let tab = ns_button.toggleGroupValue('tab_favorite')[0].split('_')[1];
    if (tab === 'favorite') {
        dialog.cont_obj.top_button_area.show();
    } else {
        dialog.cont_obj.top_button_area.hide();
    }

    ns_xhr.post('/api/worldFavorite/list', { type: tab }, dialog.drawRemote);
};

ns_dialog.dialogs.world_favorite.drawRemote  = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.world_favorite;
    let tab = ns_button.toggleGroupValue('tab_favorite')[0].split('_')[1];

    dialog.buttonClear();
    dialog.cont_obj.tbody.empty();

    let current_count = Object.keys(_data).length;
    if (tab === 'suppress') {
        dialog.cont_obj.current_count.text(`${ns_i18n.t('yellow_hideout')} : ${current_count}`);
    } else {
        dialog.cont_obj.current_count.text(current_count + '/30');
    }

    if (_data.length < 1) {
        // 리스트가 없을 때
        let tr = document.createElement('tr');
        let col = document.createElement('td');
        col.colSpan = 2;
        let span = document.createElement('span');
        span.innerHTML = ns_i18n.t('msg_none_shortcut_location'); // 바로가기 목적지가 없습니다.
        col.appendChild(span);

        tr.appendChild(col);

        dialog.cont_obj.tbody.append(tr);
        return;
    }

    let position_text = '';
    for (let [k, d] of Object.entries(_data)) {
        if (d.lord_name && d.type === 'T') {
            position_text = d.lord_name + ' (' + d.posi_pk + ')';
        } else if (d.type === 'D' || d.type === 'NPC_SUPP') {
            position_text = codeset.t('valley', d.type) + ' (' + d.posi_pk + ')';
        } else {
            position_text = codeset.t('valley', d.type) + ' ' + ns_i18n.t('level_word', [d.level]) + ' (' + d.posi_pk + ')';
        }

        let columns = [];
        let tr = document.createElement('tr');
        let td = document.createElement('td');
        let span = document.createElement('span');

        if (tab === 'favorite') {
            span.setAttribute('id', `ns_button_world_favorite_check_${d.posi_favo_pk ?? d.posi_pk}`);
            td.appendChild(span);
            columns.push(td);
            span = document.createElement('span');
        }

        span.innerHTML = position_text;
        span.setAttribute('id', `ns_button_world_favorite_title_${d.posi_favo_pk ?? d.posi_pk}`);
        td.appendChild(span);
        columns.push(td);

        if (tab !== 'suppress') {
            let memo = (tab === 'recent') ? code_set.troop_cmd_type[d.memo] : d.memo;
            if (memo !== '') {
                span = document.createElement('span');
                span.setAttribute('class', 'cont_memo');
                span.innerHTML = ` (${memo})`;
                td.appendChild(span);
                columns.push(td);
            }
        }

        for (let _column of columns) {
            tr.appendChild(_column);
        }
        dialog.cont_obj.tbody.append(tr);

        if (tab === 'favorite') {
            let checkbox_id = `world_favorite_check_${d.posi_favo_pk ?? d.posi_pk}`;
            ns_button.buttons[checkbox_id] = new nsButtonSet(checkbox_id, 'button_checkbox', 'world_favorite');
            ns_button.buttons[checkbox_id].mouseUp = function (e)
            {
                if (this.clicked === false) {
                    this.setClicked();
                } else {
                    this.unsetClicked();
                }
            }
            dialog.buttons.push(ns_button.buttons[checkbox_id]);
        }

        let button_id = `world_favorite_title_${d.posi_favo_pk ?? d.posi_pk}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_text', 'world_favorite');
        ns_button.buttons[button_id].mouseUp = function (e)
        {
            let p = d.posi_pk.split('x');
            let x = p[0], y = p[1];
            if (ns_dialog.dialogs.troop_order.visible) {
                ns_button.buttons.troop_order_dst_posi_x_input.obj.text(x);
                ns_button.buttons.troop_order_dst_posi_y_input.obj.text(y);
                ns_dialog.dialogs.troop_order.updateByInfo();
                return;
            }

            if (ns_engine.game_data.curr_view === 'world') {
                ns_world.goto_map = true;
                ns_world.setPosition(x, y);
            }
        }
        dialog.buttons.push(ns_button.buttons[button_id]);
    }
    dialog.scroll_handle.initScroll();
};

/* ************************* */
ns_button.buttons.world_favorite_close = new nsButtonSet('world_favorite_close', 'button_pop_close', 'world_favorite', { base_class: ns_button.buttons.common_close });
ns_button.buttons.world_favorite_sub_close = new nsButtonSet('world_favorite_sub_close', 'button_full', 'world_favorite', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.tab_suppress_position = new nsButtonSet('tab_suppress_position', 'button_tab_2', 'world_favorite', { toggle_group: 'tab_favorite'});
ns_button.buttons.tab_suppress_position.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_favorite;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.drawTab();
}
ns_button.buttons.tab_favorite_position = new nsButtonSet('tab_favorite_position', 'button_tab_2', 'world_favorite', { base_class: ns_button.buttons.tab_suppress_position, toggle_group: 'tab_favorite' });
ns_button.buttons.tab_recent_position = new nsButtonSet('tab_recent_position', 'button_tab_2', 'world_favorite', { base_class: ns_button.buttons.tab_suppress_position, toggle_group: 'tab_favorite' });

ns_button.buttons.favorite_all_selected = new nsButtonSet('favorite_all_selected', 'button_middle_2', 'world_favorite');
ns_button.buttons.favorite_all_selected.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_favorite;
    let tab = ns_button.toggleGroupValue('tab_favorite')[0].split('_')[1];
    if (tab === 'recent') {
        return;
    }

    for (let _button of dialog.buttons) {
        if (_button.tag_id.search('_check_') !== -1) {
            _button.setClicked();
        }
    }
}

ns_button.buttons.favorite_delete = new nsButtonSet('favorite_delete', 'button_middle_2', 'world_favorite');
ns_button.buttons.favorite_delete.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_favorite;
    let tab = ns_button.toggleGroupValue('tab_favorite')[0].split('_')[1];
    if (tab === 'recent') {
        return;
    }

    let checked_position = [];
    for (let _button of dialog.buttons) {
        if (_button.clicked === true) {
            checked_position.push(_button.tag_id.split('_').pop());
        }
    }
    if (checked_position.length < 1) {
        return;
    }

    let post_data = {};
    post_data['delete_position'] = checked_position.join(',');

    ns_xhr.post('/api/worldFavorite/delete', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.drawTab();
    }, { useProgress: true });
}


/*******************************************************************/
ns_dialog.dialogs.world_favorite_add = new nsDialogSet('world_favorite_add', 'dialog_pop', 'size-small', { do_content_scroll: false, do_close_all: false });

ns_dialog.dialogs.world_favorite_add.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.content_pop_title.text(ns_i18n.t('shortcut_add')); // 바로가기 추가

    this.cont_obj.content_memo = new nsObject('#content_memo', this.obj);
    this.cont_obj.favorite_add_position = new nsObject('.favorite_add_position', this.obj);
}

ns_dialog.dialogs.world_favorite_add.draw = function()
{
    let coords = this.data.coords;
    let position_info = null;
    this.cont_obj.content_memo.value('');

    if (coords._lord_name && coords._type === 'T') {
        position_info = coords._title + ' (' + coords._posi_pk + ')';
    } else if (coords._type === 'D') {
        position_info = codeset.t('valley', coords._type) + ' (' + coords._posi_pk + ')';
    } else {
        position_info = codeset.t('valley', coords._type) + ' ' + ns_i18n.t('level_word', [coords._level]) + ' (' + coords._posi_pk + ')';
    }

    this.cont_obj.favorite_add_position.text(position_info);
}

/* ************************************************** */
ns_button.buttons.world_favorite_add_close = new nsButtonSet('world_favorite_add_close', 'button_pop_close', 'world_favorite_add', { base_class: ns_button.buttons.common_close });
ns_button.buttons.world_favorite_add_sub_close = new nsButtonSet('world_favorite_add_sub_close', 'button_full', 'world_favorite_add', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.world_favorite_add = new nsButtonSet('world_favorite_add', 'button_pop_normal', 'world_favorite_add');
ns_button.buttons.world_favorite_add.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_favorite_add;
    let memo = dialog.cont_obj.content_memo.value();
    let coords = dialog.data.coords;

    if (memo.length > 4) {
        ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_world_favorite_add_err') }); // 메모는 최대 4글자까지 사용할 수 있습니다.
        return;
    }

    let post_data = {};
    post_data['target_posi_pk'] = coords._posi_pk;
    post_data['memo'] = memo;

    ns_xhr.post('/api/worldFavorite/addSave', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_dialog.setDataOpen('message', ns_i18n.t('msg_world_position_favorite_add_end')); // 바로가기 등록이 완료되었습니다.
        ns_dialog.close('world_favorite_add');
    }, { useProgress: true });
}

/* ************************************************** */
ns_dialog.dialogs.world_troop_camp_list = new nsDialogSet('world_troop_camp_list', 'dialog_pop', 'size-medium', { do_close_all: false });
ns_dialog.dialogs.world_troop_camp_list.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.content_pop_title.text(ns_i18n.t('deployed_troop_information')); // 주둔 부대 정보

    this.cont_obj.tbody = new nsObject('table tbody', this.obj);
    this.cont_obj.content_current_count = new nsObject('.content_current_count', this.obj);
}

ns_dialog.dialogs.world_troop_camp_list.draw = function()
{
    this.cont_obj.content_current_count.text('0');
    this.drawTab();
}

ns_dialog.dialogs.world_troop_camp_list.drawTab = function(_e)
{
    let dialog = ns_dialog.dialogs.world_troop_camp_list;

    let post_data = {}
    post_data['camp_posi_pk'] = dialog.data.target_posi_pk;
    ns_xhr.post('/api/troop/campList', post_data, dialog.drawRemote);
}

ns_dialog.dialogs.world_troop_camp_list.drawRemote  = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.world_troop_camp_list;

    let current_count = 0;
    dialog.cont_obj.tbody.empty();

    if (_data.length > 0) {
        for (let d of Object.values(_data)) {
            let tr = document.createElement('tr');

            let col1 = document.createElement('td');
            let col1_span = document.createElement('span');
            col1_span.innerHTML = d.captain_desc;
            col1.appendChild(col1_span);

            let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
            button.setAttribute('id', `ns_button_world_troop_camp_list_view_${d.troo_pk}`);
            col1.appendChild(button);

            let col2 = document.createElement('td');
            let col2_span = document.createElement('span');
            col2_span.innerHTML = ns_text.convertPositionName(d.from_position);
            col2.appendChild(col2_span);

            let col3 = document.createElement('td');
            let col3_span = document.createElement('span');
            col3_span.innerHTML = ns_timer.getDateTimeString(d.withdrawal_dt, true, true, true);
            col3.appendChild(col3_span);

            tr.appendChild(col1);
            tr.appendChild(col2);
            tr.appendChild(col3);

            dialog.cont_obj.tbody.append(tr);

            let button_id = `world_troop_camp_list_view_${d.troo_pk}`;
            ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'world_troop_camp_list');
            ns_button.buttons[button_id].mouseUp = function ()
            {
                ns_dialog.close('world_troop_camp_list');
                ns_dialog.setDataOpen('troop_view', { type: 'camp', 'troo_pk': d.troo_pk });
            }
            dialog.buttons.push(ns_button.buttons[button_id]);
        }
    } else {
        let tr = document.createElement('tr');

        let col1 = document.createElement('td');
        col1.colSpan = 3;
        let col1_span = document.createElement('span');
        col1_span.innerHTML = ns_i18n.t('msg_no_deployed_troop'); // 주둔 중인 부대가 없습니다.
        col1.appendChild(col1_span);

        tr.appendChild(col1);

        dialog.cont_obj.tbody.append(tr);
    }

    dialog.cont_obj.content_current_count.text(_data.length);
}

/* ************************* */
ns_button.buttons.world_troop_camp_list_close = new nsButtonSet('world_troop_camp_list_close', 'button_pop_close', 'world_troop_camp_list', { base_class: ns_button.buttons.common_close });
ns_button.buttons.world_troop_camp_list_sub_close = new nsButtonSet('world_troop_camp_list_sub_close', 'button_full', 'world_troop_camp_list', { base_class: ns_button.buttons.common_sub_close });

/*******************************************************************/
ns_dialog.dialogs.world_fort = new nsDialogSet('world_fort', 'dialog_building', 'size-large', { do_close_all: false });
ns_dialog.dialogs.world_fort.forification = {};
ns_dialog.dialogs.world_fort.sorted = [];
ns_dialog.dialogs.world_fort.timer_arr = [];

ns_dialog.dialogs.world_fort.cacheContents = function()
{
    this.cont_obj.develop_list_skeleton = new nsObject('#develop_list_skeleton');
    this.cont_obj.world_fort_cache_wrap = new nsObject('.world_fort_cache_wrap', this.obj);

    this.cont_obj.content_concurr_body = new nsObject('.content_concurr_body', this.obj);
    this.cont_obj.content_concurr_idle = new nsObject('.content_concurr_idle', this.obj);
    this.cont_obj.content_concurr_title = new nsObject('.content_concurr_title', this.obj);
    this.cont_obj.content_concurr_time = new nsObject('.content_concurr_time', this.obj);

    // this.s.cont_queue = this.cont_obj.find('.cont_queue');
    // this.s.cont_queue_list = this.obj.find('.cont_queue_list');
    // this.s.cont_queue_list_skel = $('#skeleton_cont_queue_list');

    // 한번만 그려줌
    this.drawList();
}

ns_dialog.dialogs.world_fort.draw = function()
{
    this.drawRemote();
}

ns_dialog.dialogs.world_fort.drawRemote = function()
{
    let dialog = ns_dialog.dialogs.world_fort;
    let data = dialog.data;

    let post_data = {};
    post_data['target_posi_pk'] = data.posi_pk;

    ns_xhr.post('/api/world/getFort', post_data, function(_data, _status) {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.forification = _data['fort'];
    });
}

ns_dialog.dialogs.world_fort.drawConCurr = function()
{
    let dialog = ns_dialog.dialogs.world_fort;

    dialog.cont_obj.content_concurr_body.hide();
    dialog.cont_obj.content_concurr_idle.show();

    dialog.buil_fort = false;
    dialog.time_pk = null;

    let buil_fort_pk = null;
    let time_pk = null;
    dialog.draw_remote_data = null;

    for (let [k, d] of Object.entries(ns_cs.d.time)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        if (d.queue_type !== 'W') {
            continue;
        }

        if (d.description.indexOf(dialog.data.posi_pk) < 0) {
            continue;
        }

        buil_fort_pk = d.queue_pk;
        time_pk = k;
        dialog.time_pk = k;
    }

    if (! buil_fort_pk) {
        return;
    }
    dialog.draw_remote_data = ns_util.toInteger(ns_cs.d.time[time_pk].end_dt_ut);
    if (! dialog.draw_remote_data) {
        return;
    }

    dialog.install_timer_end_dt_ut = ns_util.toInteger(ns_cs.d.time[time_pk].end_dt_ut);
    dialog.buil_fort = true;

    dialog.cont_obj.content_concurr_title.text(ns_cs.d.time[time_pk].description);

    dialog.cont_obj.content_concurr_body.show();
    dialog.cont_obj.content_concurr_idle.hide();
}

ns_dialog.dialogs.world_fort.erase = function()
{
    this.forification = {};
    this.data = null;
}

ns_dialog.dialogs.world_fort.drawList = function()
{
    let dialog = ns_dialog.dialogs.world_fort;

    // 목록 리스트 캐싱
    dialog.sorted = [];
    for (let [k, d] of Object.entries(ns_cs.m.fort)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        dialog.sorted.push(d);
    }

    // 목록 그리기
    dialog.cont_obj.world_fort_cache_wrap.empty();

    for (let d of dialog.sorted) {
        let skeleton = dialog.cont_obj.develop_list_skeleton.clone();
        skeleton.find('.develop_list_title').text(d.title);
        skeleton.find('.develop_list_desc').addCss(d.code);
        skeleton.find('.develop_list_desc').text(0);

        skeleton.find('.develop_list_image').setAttribute('id', `ns_button_world_fort_information_${d.code}`);
        skeleton.find('.develop_list_image').addCss(`fort_image_${d.code}`);

        skeleton.find('.develop_list_submit').setAttribute('id', `ns_button_world_fort_${d.code}`);
        skeleton.find('.develop_list_submit').text(ns_i18n.t('set_up')); // 설치

        skeleton.find('.develop_list_cancel').setAttribute('id', `ns_button_world_fort_disperse_${d.code}`);
        skeleton.find('.develop_list_cancel').text(ns_i18n.t('dismantle')); // 해체

        dialog.cont_obj.world_fort_cache_wrap.append(skeleton);

        ns_button.buttons[`world_fort_${d.code}`] = new nsButtonSet(`world_fort_${d.code}`, 'button_small_1', 'world_fort',{ base_class: ns_button.buttons.world_fort_installation });
        ns_button.buttons[`world_fort_information_${d.code}`] = new nsButtonSet(`world_fort_information_${d.code}`, 'button_empty', 'world_fort', { base_class: ns_button.buttons.build_CastleWall_fort_information });
        ns_button.buttons[`world_fort_disperse_${d.code}`] = new nsButtonSet(`world_fort_disperse_${d.code}`, 'button_small_2', 'world_fort', { base_class: ns_button.buttons.world_fort_disperse });
    }
}

ns_dialog.dialogs.world_fort.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.world_fort.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.world_fort.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.world_fort;

    if (! dialog.forification) {
        return;
    }
    dialog.drawConCurr();

    // 현재 보유 중인 방어시설 수
    for (let d of dialog.sorted) {
        dialog.cont_obj.world_fort_cache_wrap.find('.' + d.code).text(dialog.forification[d.code]);
    }

    // 현재 개발 중인 부분 시간 계산
    if (dialog.draw_remote_data) {
        let left = dialog.draw_remote_data - ns_timer.now();
        dialog.cont_obj.content_concurr_time.html((left <= 0) ? ns_i18n.t('in_progress') : ns_util.getCostsTime(left));
    }

    dialog.timerHandlerButtonCondition();
}

ns_dialog.dialogs.world_fort.timerHandlerButtonCondition = function()
{
    let dialog = ns_dialog.dialogs.world_fort;

    // 버튼 처리 - TODO 설치 제한 조건이 뭔지 확인 해봐야 할듯?
    for (let d of dialog.sorted) {
        continue;
        if (ns_button.buttons[`world_fort_${d.code}`]) {
            if (ns_check_condition.checkAll(d.m_cond_pk, 0)) {
                ns_button.buttons[`world_fort_${d.code}`].setEnable();
            } else {
                ns_button.buttons[`world_fort_${d.code}`].setDisable();
            }

            if (ns_util.math(dialog.forification[d.code]).gt(0)) {
                ns_button.buttons[`world_fort_disperse_${d.code}`].setEnable();
            } else {
                ns_button.buttons[`world_fort_disperse_${d.code}`].setDisable();
            }
        }
    }
}

/* ************************* */
ns_button.buttons.world_fort_close = new nsButtonSet('world_fort_close', 'button_back', 'world_fort', { base_class: ns_button.buttons.common_close });
ns_button.buttons.world_fort_sub_close = new nsButtonSet('world_fort_sub_close', 'button_full', 'world_fort', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.world_fort_close_all = new nsButtonSet('world_fort_closeAll', 'button_close_all', 'world_fort', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.concurr_speedup_world_fort = new nsButtonSet('concurr_speedup_world_fort', 'button_small_1', 'world_fort');
ns_button.buttons.concurr_speedup_world_fort.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_fort;
    ns_dialog.setDataOpen('item_quick_use', { type: 'speedup', queue_type: 'W' , time_pk: dialog.time_pk });
}

ns_button.buttons.world_fort_installation = new nsButtonSet('world_fort_installation', 'button_empty', 'world_fort');
ns_button.buttons.world_fort_installation.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_fort;
    let code = this.tag_id.split('_').pop();
    let pk = ns_cs.m.fort[code].m_fort_pk;

    ns_dialog.setDataOpen('world_fort_installation', { type: code, m_pk: pk, posi_pk: dialog.data.posi_pk });
}

ns_button.buttons.world_fort_disperse = new nsButtonSet('world_fort_disperse', 'button_empty', 'world_fort');
ns_button.buttons.world_fort_disperse.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_fort;
    let code = this.tag_id.split('_').pop();
    let pk = ns_cs.m.fort[code].m_fort_pk;

    ns_dialog.setDataOpen('world_fort_disperse', { type: code, m_pk: pk, posi_pk: dialog.data.posi_pk });
}

/*******************************************************************/
ns_dialog.dialogs.world_fort_installation = new nsDialogSet('world_fort_installation', 'dialog_building', 'size-full', { do_close_all: false });
ns_dialog.dialogs.world_fort_installation.devel_amount = 0;
ns_dialog.dialogs.world_fort_installation.condition = {};
ns_dialog.dialogs.world_fort_installation.vacancy_max = 50000; // 공간 최대치 성벽 Lv.10 기준
ns_dialog.dialogs.world_fort_installation.vacancy_curr = 0; // 현재 공간

ns_dialog.dialogs.world_fort_installation.cacheContents = function()
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);

    this.cont_obj.develop_image = new nsObject('.develop_image', this.obj);
    this.cont_obj.develop_description = new nsObject('.develop_description', this.obj);

    this.cont_obj.content_build_time = new nsObject('.content_build_time', this.obj);

    this.cont_obj.table_fort_installation = new nsObject('.table_fort_installation', this.obj);

    this.cont_obj.develop_current = new nsObject('.develop_current', this.obj);
    this.cont_obj.develop_max = new nsObject('.develop_max', this.obj);

    this.cont_obj.fort_vacancy_current = new nsObject('.fort_vacancy_current', this.obj);
    this.cont_obj.fort_vacancy_max = new nsObject('.fort_vacancy_max', this.obj);
    this.cont_obj.fort_vacancy_free = new nsObject('.fort_vacancy_free', this.obj);

    this.cont_obj.fort_installation_amount = new nsObject('.amount_field', this.obj);
    this.cont_obj.fort_installation_amount_slider = new nsObject('input[name="develop_slider"]', this.obj);

    this.cont_obj.fort_installation_amount_slider.setEvent('input', (_e)=>{
        this.cont_obj.fort_installation_amount.element.value = _e.target.value;

        // 갱신 필요!
        this.installFortBuildNumber();
    });

    this.cont_obj.fort_installation_amount.setEvent('input', (_e)=>{
        let regexp = /[^0-9]/g;
        let current_value = _e.target.value;

        let maximum_value = this.cont_obj.fort_installation_amount_slider.element.max,
            minimum_value = this.cont_obj.fort_installation_amount_slider.element.min;

        current_value = (!ns_util.isNumeric(current_value)) ? minimum_value : current_value;
        current_value = current_value.replace(regexp, "");

        let number_value = ns_util.math(current_value).integer;

        number_value = Math.min(Math.max(number_value, minimum_value), maximum_value);

        this.cont_obj.fort_installation_amount_slider.value(number_value);
        this.cont_obj.fort_installation_amount.value(number_value);

        // 갱신 필요!
        this.installFortBuildNumber();
    });
}

ns_dialog.dialogs.world_fort_installation.draw = function()
{
    let data = this.data;
    let m = ns_cs.m.fort[data.m_pk];
    this.condition = ns_cs.m.cond[m.m_cond_pk];
    let install_max = ns_util.math(this.installMax()).integer;

    this.cont_obj.content_title.text(ns_i18n.t('set_up_trap', [m.title]));
    this.cont_obj.develop_image.addCss(`fort_image_${data.type}`);
    this.cont_obj.develop_description.html(m.description_detail);

    this.cont_obj.content_build_time.text("");

    let vacancy_curr = 0;
    for (let [k, d] of Object.entries(ns_dialog.dialogs.world_fort.forification)) {
        vacancy_curr = ns_util.math(d).mul(ns_cs.m.fort[k].need_vacancy).number;
    }
    let vacancy_max = ns_util.toInteger(this.vacancy_max);

    this.cont_obj.fort_vacancy_current.text(ns_util.numberFormat(vacancy_curr));
    this.cont_obj.fort_vacancy_max.text(ns_util.numberFormat(vacancy_max));
    this.cont_obj.fort_vacancy_free.text(ns_util.math(vacancy_max).minus(vacancy_curr).number_format);


    this.cont_obj.fort_installation_amount.value(0);
    this.cont_obj.fort_installation_amount_slider.element.value = 0;
    this.cont_obj.fort_installation_amount_slider.element.min = 0;
    this.cont_obj.fort_installation_amount_slider.element.max = install_max;

    this.cont_obj.develop_current.text(0);
    this.cont_obj.develop_max.text(ns_util.numberFormat(install_max));

    this.cont_obj.table_fort_installation.empty();

    let cond = this.condition;

    if (! ns_check_condition.drawList(cond.m_cond_pk, 0, null, true)) {
        ns_button.buttons.world_fort_installation_submit.setDisable();
    } else {
        ns_button.buttons.world_fort_installation_submit.setEnable();
    }

    ns_button.buttons.world_fort_installation_amount_max.obj.text(install_max);

    ns_check_condition.drawList(cond.m_cond_pk, 0, this.cont_obj.table_fort_installation, true);
}

ns_dialog.dialogs.world_fort_installation.installMax = function()
{
    let dialog = ns_dialog.dialogs.world_fort_installation;
    let m = ns_cs.m.fort[dialog.data.m_pk];
    let cond = ns_cs.m.cond[m.m_cond_pk];
    let install_max = (dialog.vacancy_max - dialog.vacancy_curr) / m.need_vacancy;

    for (let _type of ['food', 'horse', 'lumber', 'iron', 'gold']) {
        if (cond[`build_${_type}`]) {
            let type_max =  ns_util.math((_type === 'gold') ? ns_cs.getTerritoryInfo(`${_type}_curr`) :  ns_cs.getResourceInfo(`${_type}_curr`)).div(cond[`build_${_type}`]).integer;
            if(ns_util.math(type_max).lt(install_max)) {
                install_max = type_max;
            }
        }
    }

    return ns_util.toInteger(install_max);
}

ns_dialog.dialogs.world_fort_installation.installFortBuildNumber = function()
{
    let dialog = ns_dialog.dialogs.world_fort_installation;
    // let obj = dialog.s.cont_build_time;
    let m = ns_cs.m.fort[dialog.data.m_pk];
    let cond = ns_cs.m.cond[m.m_cond_pk];
    let install_max = dialog.installMax();
    let build_number = dialog.cont_obj.fort_installation_amount.value();

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
    let build_time = (ns_util.math(build_number).eq(0)) ? '0' : ns_util.getCostsTime(ns_util.math(cond.build_time).mul(build_number).number);
    dialog.cont_obj.content_build_time.text(build_time);
    if (ns_util.math(build_number).lte(1)) {
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

ns_dialog.dialogs.world_fort_installation.erase = function()
{
    this.cont_obj.develop_image.removeCss(`fort_image_${this.data.type}`);
    this.data = null;
}

ns_dialog.dialogs.world_fort_installation.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.world_fort_installation.timerHandlerReal, 1000, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.world_fort_installation.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.world_fort_installation;

    // 현재 자원
    for (let _type of ['food_curr', 'food_curr', 'horse_curr', 'lumber_curr', 'iron_curr']) {
        let current = (_type === 'gold_curr') ? ns_cs.getTerritoryInfo(_type) : ns_cs.getResourceInfo(_type);
        if (dialog.cont_obj.table_fort_installation.find(`.ns_resource_${_type}`).element) {
            dialog.cont_obj.table_fort_installation.find(`.ns_resource_${_type}`).text(ns_util.numberFormat(ns_util.toInteger(current)))
        }
    }

    dialog.cont_obj.develop_current.text(ns_util.numberFormat(ns_dialog.dialogs.world_fort.forification[dialog.data.type]));
    dialog.cont_obj.develop_max.text(ns_util.numberFormat(dialog.devel_amount));

    // 사용중인 공간
    let vacancy_curr = 0;
    for (let [k, d] of Object.entries(ns_dialog.dialogs.world_fort.forification)) {
        vacancy_curr = ns_util.math(d).mul(ns_cs.m.fort[k].need_vacancy).number;
    }
    let vacancy_max = ns_util.toInteger(dialog.vacancy_max);

    dialog.cont_obj.fort_vacancy_current.text(ns_util.numberFormat(vacancy_curr));
    dialog.cont_obj.fort_vacancy_max.text(ns_util.numberFormat(vacancy_max));
    dialog.cont_obj.fort_vacancy_free.text(ns_util.math(vacancy_max).minus(vacancy_curr).number_format);
}

/* ************************************************** */
ns_button.buttons.world_fort_installation_close = new nsButtonSet('world_fort_installation_close', 'button_back', 'world_fort_installation', { base_class: ns_button.buttons.common_close });
ns_button.buttons.world_fort_installation_sub_close = new nsButtonSet('world_fort_installation_sub_close', 'button_full', 'world_fort_installation', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.world_fort_installation_close_all = new nsButtonSet('world_fort_installation_close_all', 'button_close_all', 'world_fort_installation', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.world_fort_installation_amount_decrease = new nsButtonSet('world_fort_installation_amount_decrease', 'button_decrease', 'world_fort_installation');
ns_button.buttons.world_fort_installation_amount_decrease.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_fort_installation;
    let current_value = ns_util.math(dialog.cont_obj.fort_installation_amount.value()).integer,
        minimum_value = ns_util.math(dialog.cont_obj.fort_installation_amount_slider.element.min).integer;

    current_value = Math.max(--current_value, minimum_value);

    dialog.cont_obj.fort_installation_amount_slider.value(current_value);
    dialog.cont_obj.fort_installation_amount.value(current_value);

    dialog.installFortBuildNumber();
}

ns_button.buttons.world_fort_installation_amount_increase = new nsButtonSet('world_fort_installation_amount_increase', 'button_increase', 'world_fort_installation');
ns_button.buttons.world_fort_installation_amount_increase.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_fort_installation;
    let current_value = ns_util.math(dialog.cont_obj.fort_installation_amount.value()).integer,
        maximum_value = ns_util.math(dialog.cont_obj.fort_installation_amount_slider.element.max).integer;

    current_value = Math.min(++current_value, maximum_value);

    dialog.cont_obj.fort_installation_amount_slider.value(current_value);
    dialog.cont_obj.fort_installation_amount.value(current_value);

    dialog.installFortBuildNumber();
}

ns_button.buttons.world_fort_installation_amount_max = new nsButtonSet('world_fort_installation_amount_max', 'button_middle_2', 'world_fort_installation');
ns_button.buttons.world_fort_installation_amount_max.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_fort_installation;
    let current_value = ns_util.math(dialog.cont_obj.fort_installation_amount.value()).integer,
        maximum_value = ns_util.math(dialog.cont_obj.fort_installation_amount_slider.element.max).integer;

    if ( current_value !== maximum_value ){
        current_value = maximum_value;
    }
    else{
        current_value = 0;
    }

    dialog.cont_obj.fort_installation_amount.value(current_value);
    dialog.cont_obj.fort_installation_amount_slider.value(current_value);

    dialog.installFortBuildNumber();
}

ns_button.buttons.world_fort_installation_submit = new nsButtonSet('world_fort_installation_submit', 'button_default', 'world_fort_installation');
ns_button.buttons.world_fort_installation_submit.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_fort_installation;
    let data = dialog.data;

    let installation_number = dialog.cont_obj.fort_installation_amount.value();
    if (ns_util.math(installation_number).lte(0)) {
        return;
    }
    let post_data = {};
    post_data['posi_pk'] = ns_engine.game_data.cpp;
    post_data['target_posi_pk'] = data.posi_pk;
    post_data['code'] = data.type;
    post_data['build_number'] = ns_util.toInteger(installation_number);

    ns_xhr.post('/api/world/upgrade', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_dialog.close('world_fort_installation');
    }, { useProgress: true });
}

/*******************************************************************/
ns_dialog.dialogs.world_fort_disperse = new nsDialogSet('world_fort_disperse', 'dialog_pop', 'size-medium', { do_close_all: false });
ns_dialog.dialogs.world_fort_disperse.disperse_amount = 0;
ns_dialog.dialogs.world_fort_disperse.vacancy_curr = 0; // 남은 공간

ns_dialog.dialogs.world_fort_disperse.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);

    this.cont_obj.content_disperse_table = new nsObject('.content_disperse_table', this.obj);
    //this.cont_obj.content_fort_current = new nsObject('.content_fort_current', this.obj);

    this.cont_obj.ns_territory_gold_curr = new nsObject('.ns_territory_gold_curr', this.obj);
    this.cont_obj.ns_territory_fort_vacancy_curr = new nsObject('.ns_territory_fort_vacancy_curr', this.obj);

    this.cont_obj.content_world_fort_disperse_amount = new nsObject('.amount_field', this.obj);
    this.cont_obj.content_world_fort_disperse_amount_slider = new nsObject('input[name="develop_slider"]', this.obj);
    this.cont_obj.content_world_fort_disperse_amount_slider.setEvent('input', (_e)=>{
        let current_value = _e.target.value;

        this.cont_obj.content_world_fort_disperse_amount.value(current_value);

        this.disperseWorldFortBuildNumber();
    });

    this.cont_obj.content_world_fort_disperse_amount.setEvent('input', (_e)=>{
        let current_value = _e.target.value;
        let regExp = /[^\d.]|\.(?=.*\.)/g;

        let maximum_value = this.cont_obj.content_world_fort_disperse_amount_slider.element.max,
            minimum_value = this.cont_obj.content_world_fort_disperse_amount_slider.element.min;

        if ( !ns_util.isNumeric(current_value)) { return ; }

        let number_value = current_value.replace(regExp, "");
        number_value = Math.min(maximum_value, Math.max(number_value, minimum_value));

        this.cont_obj.content_world_fort_disperse_amount_slider.value(number_value);
        this.cont_obj.content_world_fort_disperse_amount.value(number_value);

        this.disperseWorldFortBuildNumber();
    });
}

ns_dialog.dialogs.world_fort_disperse.draw = function()
{
    let data = this.data;
    let m = ns_cs.m.fort[data.m_pk];
    let cond = ns_cs.m.cond[m.m_cond_pk];

    this.disperse_amount = ns_dialog.dialogs.world_fort.forification[m.code];

    this.cont_obj.content_pop_title.text(m.title + ' 해체');

    for (let _type of ['food', 'horse', 'lumber', 'iron', 'gold']) {
        this.cont_obj.content_disperse_table.find(`.content_disperse_${_type}`).text(ns_util.numberFormat(cond[`demolish_${_type}`] ? cond[`demolish_${_type}`] : 0));
    }
    this.cont_obj.content_disperse_table.find('.content_disperse_vacancy').text(ns_util.numberFormat(m.need_vacancy));

    this.cont_obj.ns_territory_gold_curr.text(ns_util.numberFormat(ns_cs.d.terr.gold_curr.v));
    this.cont_obj.ns_territory_fort_vacancy_curr.text(ns_util.numberFormat(this.disperse_amount));

    this.cont_obj.content_world_fort_disperse_amount.value(0);
    this.cont_obj.content_world_fort_disperse_amount_slider.value(0);
    this.cont_obj.content_world_fort_disperse_amount_slider.element.min = 0;
    this.cont_obj.content_world_fort_disperse_amount_slider.element.max = this.disperse_amount;

    ns_button.buttons.world_fort_disperse_amount_max.obj.text(ns_util.numberFormat(this.disperse_amount));
}

ns_dialog.dialogs.world_fort_disperse.disperseWorldFortBuildNumber = function()
{
    let dialog = ns_dialog.dialogs.world_fort_disperse;
    let m = ns_cs.m.fort[dialog.data.m_pk];
    let cond = ns_cs.m.cond[m.m_cond_pk];
    let disperse_max = ns_dialog.dialogs.world_fort.forification[m.code];
    let disperse_number = ns_util.math(dialog.cont_obj.content_world_fort_disperse_amount.value()).integer;

    // 1 보다 작거나 숫자가 아닐때는 리턴
    if(! ns_util.isNumeric(disperse_number) || ns_util.math(disperse_number).lt(1)) {
        disperse_number = 0;
    }

    if(ns_util.math(disperse_max).lt(disperse_number)) {
        disperse_number = disperse_max;
    }

    dialog.cont_obj.content_world_fort_disperse_amount.value(disperse_number);
    dialog.cont_obj.content_world_fort_disperse_amount_slider.value(disperse_number);

    //조건 검사
    for (let _type of ['food', 'horse', 'lumber', 'iron', 'gold']) {
        dialog.cont_obj.content_disperse_table.find(`.content_disperse_${_type}`).text(ns_util.math(cond[`demolish_${_type}`] ? cond[`demolish_${_type}`] : 0).mul(disperse_number).number_format);
    }
    dialog.cont_obj.content_disperse_table.find('.content_disperse_vacancy').text(ns_util.math(m.need_vacancy).mul(disperse_number).number_format);
}

ns_dialog.dialogs.world_fort_disperse.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.world_fort_disperse.timerHandlerReal, 1000, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.world_fort_disperse.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.world_fort_disperse;

    for (let _type of ['food_curr', 'horse_curr', 'lumber_curr', 'iron_curr']) {
        dialog.cont_obj.content_disperse_table.find(`.ns_resource_${_type}`).text(ns_util.numberFormat(ns_util.toInteger(ns_cs.getResourceInfo(_type))));
    }
    for (let _type of ['gold_curr', 'wall_vacancy_curr']) {
    }

    dialog.cont_obj.content_disperse_table.find(`.ns_territory_gold_curr`).text(ns_util.numberFormat(ns_util.toInteger(ns_cs.getTerritoryInfo('gold_curr'))));

    let vacancy_curr = 0;
    for (let [k, d] of Object.entries(ns_dialog.dialogs.world_fort.forification)) {
        vacancy_curr += ns_util.math(d).mul(ns_cs.m.fort[k].need_vacancy).number;
    }
    dialog.cont_obj.content_disperse_table.find(`.ns_territory_gold_curr`).text(ns_util.numberFormat(vacancy_curr));
}

/* ************************* */
ns_button.buttons.world_fort_disperse_close = new nsButtonSet('world_fort_disperse_close', 'button_pop_close', 'world_fort_disperse', { base_class: ns_button.buttons.common_close });
ns_button.buttons.world_fort_disperse_sub_close = new nsButtonSet('world_fort_disperse_sub_close', 'button_full', 'world_fort_disperse', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.world_fort_disperse_submit = new nsButtonSet('world_fort_disperse_submit', 'button_default', 'world_fort_disperse');
ns_button.buttons.world_fort_disperse_submit.mouseUp = function(_e)
{
    if (! ns_dialog.dialogs.world_fort.forification) {
        return;
    }

    let dialog = ns_dialog.dialogs.world_fort_disperse;
    let data = dialog.data;

    let disperse_number = ns_util.math(dialog.cont_obj.content_world_fort_disperse_amount.value()).integer;
    if (ns_util.math(disperse_number).lte(0)) {
        return;
    }

    let post_data = {};
    post_data['target_posi_pk'] = data.posi_pk;
    post_data['code'] = data.type;
    post_data['disperse_number'] = ns_util.toInteger(disperse_number);

    ns_xhr.post('/api/world/disperse', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_dialog.dialogs.world_fort.drawRemote();
        ns_dialog.close('world_fort_disperse');
    }, { useProgress: true });
}

ns_button.buttons.world_fort_disperse_amount_decrease = new nsButtonSet('world_fort_disperse_amount_decrease', 'button_decrease', 'world_fort_disperse');
ns_button.buttons.world_fort_disperse_amount_decrease.mouseUp = function (_e)
{
    let dialog = ns_dialog.dialogs.world_fort_disperse;
    let current_value = ns_util.math(dialog.cont_obj.content_world_fort_disperse_amount.value()).integer,
        minimum_value = ns_util.math(dialog.cont_obj.content_world_fort_disperse_amount_slider.element.min).integer;

    current_value = Math.max(--current_value, minimum_value);

    dialog.cont_obj.content_world_fort_disperse_amount_slider.value(current_value);
    dialog.cont_obj.content_world_fort_disperse_amount.value(current_value);

    dialog.disperseWorldFortBuildNumber();
}

ns_button.buttons.world_fort_disperse_amount_increase = new nsButtonSet('world_fort_disperse_amount_increase', 'button_increase', 'world_fort_disperse');
ns_button.buttons.world_fort_disperse_amount_increase.mouseUp = function (_e)
{
    let dialog = ns_dialog.dialogs.world_fort_disperse;
    let current_value = ns_util.math(dialog.cont_obj.content_world_fort_disperse_amount.value()).integer,
        maximum_value = ns_util.math(dialog.cont_obj.content_world_fort_disperse_amount_slider.element.max).integer;

    current_value = Math.min(++current_value, maximum_value);

    dialog.cont_obj.content_world_fort_disperse_amount_slider.value(current_value);
    dialog.cont_obj.content_world_fort_disperse_amount.value(current_value);

    dialog.disperseWorldFortBuildNumber();
}

ns_button.buttons.world_fort_disperse_amount_max = new nsButtonSet('world_fort_disperse_amount_max', 'button_middle_2', 'world_fort_disperse');
ns_button.buttons.world_fort_disperse_amount_max.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.world_fort_disperse;
    let current_value = ns_util.math(dialog.cont_obj.content_world_fort_disperse_amount.value()).integer,
        maximum_value = ns_util.math(dialog.cont_obj.content_world_fort_disperse_amount_slider.element.max).integer;

    if (current_value !== maximum_value){
        current_value = maximum_value;
    } else {
        current_value = 0;
    }

    dialog.cont_obj.content_world_fort_disperse_amount_slider.value(current_value);
    dialog.cont_obj.content_world_fort_disperse_amount.value(current_value);

    dialog.disperseWorldFortBuildNumber();
}