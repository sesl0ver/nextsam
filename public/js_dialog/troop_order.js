// 다이얼로그
ns_dialog.dialogs.troop_order = new nsDialogSet('troop_order', 'dialog_building', 'size-large');
ns_dialog.dialogs.troop_order.order_data = {};
ns_dialog.dialogs.troop_order.current_shortcut = 0;

ns_dialog.dialogs.troop_order.cacheContents = function ()
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);

    this.cont_obj.troop_order_preset_wrap = new nsObject('.troop_order_preset_wrap', this.obj);

    this.cont_obj.content_captain_area = new nsObject('.content_captain_area', this.obj);
    this.cont_obj.content_director_area = new nsObject('.content_director_area', this.obj);
    this.cont_obj.content_staff_area = new nsObject('.content_staff_area', this.obj);

    this.cont_obj.troop_order_army_list_wrap = new nsObject('.troop_order_army_list_wrap', this.obj);
    this.cont_obj.troop_order_army_list = new nsObject('.troop_order_army_list', this.obj);

    this.cont_obj.content_army_population_suggest = new nsObject('.content_army_population_suggest', this.obj);
    this.cont_obj.content_army_population_curr = new nsObject('.content_army_population_curr', this.obj);
    this.cont_obj.content_army_fighting_spirit = new nsObject('.content_army_fighting_spirit', this.obj);
    this.cont_obj.content_troop_capacity_possible = new nsObject('.content_troop_capacity_possible', this.obj);

    this.cont_obj.content_move_time = new nsObject('.content_move_time', this.obj);
    this.cont_obj.content_arrival_dt = new nsObject('.content_arrival_dt', this.obj);

    this.cont_obj.troop_order_resource_wrap = new nsObject('.troop_order_resource_wrap', this.obj);

    this.cont_obj.slot_page_1 = new nsObject('.slot_page_1', this.obj);
    this.cont_obj.slot_page_2 = new nsObject('.slot_page_2', this.obj);


    this.scroll_handle = new nsScroll(this.cont_obj.troop_order_army_list_wrap.element, this.cont_obj.troop_order_army_list_wrap.find('table').element);

    this.cont_obj.skeleton_troop_order_army = new nsObject('#skeleton_troop_order_army').find('tr').clone();
    this.cont_obj.skeleton_troop_order_resource_attack = new nsObject('#skeleton_troop_order_resource_attack').clone();
    this.cont_obj.skeleton_troop_order_resource_transport = new nsObject('#skeleton_troop_order_resource_transport').clone();
}

ns_dialog.dialogs.troop_order.draw = function ()
{
    let data = this.data;
    let posi_pk = data.coords._posi_pk
    // 타이틀 설정
    if (data.cmd_type === 'transport') {
        this.cont_obj.content_title.text(`${posi_pk} ${ns_i18n.t('transport')}`);
    } else if (data.cmd_type === 'supply') {
        this.cont_obj.content_title.text(`${posi_pk} ${ns_i18n.t('supply')}`);
    } else if (data.cmd_type === 'scout') {
        this.cont_obj.content_title.text(`${posi_pk} ${ns_i18n.t('reconnaissance')}`);
    } else if (data.cmd_type === 'reinforce') {
        this.cont_obj.content_title.text(`${posi_pk} ${ns_i18n.t('support')}`);
    } else {
        this.cont_obj.content_title.text(`${posi_pk} ${ns_i18n.t('attack')}`);
    }

    this.initData();
    if (['A', 'S'].includes(this.order_data.cmd_type)) {
        this.cont_obj.troop_order_preset_wrap.show();
    } else {
        this.cont_obj.troop_order_preset_wrap.hide();
    }

    this.resourceButtons();

    this.updateByInfo();

    ns_dialog.dialogs.troop_order_preset.presetList();
}

ns_dialog.dialogs.troop_order.erase = function ()
{
    // 현재 선택된 영웅 전체
    if (this.order_data.troop_do_status !== true) {
        this.unsetHero();
    }
    if (! ns_util.math(this.current_shortcut).eq(0)) {
        ns_button.buttons[`preset_shortcut_${this.current_shortcut}`].unsetClicked();
    }
    this.current_shortcut = 0;
    this.data = null;
    this.order_data = {};
}

ns_dialog.dialogs.troop_order.initData = function()
{
    let dialog = ns_dialog.dialogs.troop_order;
    let data = dialog.data;
    let posi_pk = data.coords._posi_pk

    dialog.order_data = {
        hero: {},
        army: {},
        dst_posi_pk: '',
        dst_posi_info: null,
        cmd_type: null,
        cmd_type_possible: false,
        distance: 0,
        need_population: 0,
        population: 0,
        need_food: 0,
        capacity: 0, // 부대의 수송력
        use_capacity: 0, // 수송량
        troop_speed: 3000, // 부대의 스피드
        triptime: 0,
        camp_time: 0,
        round_food: 0,
        round_gold: 0,
        presence_food: 0,
        sum_hero_rare: 0,
        select_item_pk: null,
        army_population_suggest: 0,
        hero_type: null,
        army_type: null,
        resource_type: null,
        troop_do_status: false,
        alli_status: false,
    }
    ns_button.buttons.troop_order_camp_time_h_input.obj.text(0);
    ns_button.buttons.troop_order_camp_time_m_input.obj.text(0);

    // 타이틀 설정
    if (data.cmd_type === 'transport') {
        dialog.order_data.cmd_type = 'T';
        dialog.cont_obj.content_title.text(`${posi_pk} ${ns_i18n.t('transport')}`);
    } else if (data.cmd_type === 'supply') {
        dialog.order_data.cmd_type = 'P';
        dialog.cont_obj.content_title.text(`${posi_pk} ${ns_i18n.t('supply')}`);
    } else if (data.cmd_type === 'scout') {
        dialog.order_data.cmd_type = 'S';
        dialog.cont_obj.content_title.text(`${posi_pk} ${ns_i18n.t('reconnaissance')}`);
    } else if (data.cmd_type === 'reinforce') {
        dialog.order_data.cmd_type = 'R';
        dialog.cont_obj.content_title.text(`${posi_pk} ${ns_i18n.t('support')}`);
    } else {
        dialog.order_data.cmd_type = 'A';
        dialog.cont_obj.content_title.text(`${posi_pk} ${ns_i18n.t('attack')}`);
    }

    // 영웅 기본
    dialog.cont_obj.content_captain_area.find('.content_hero_sm').empty().append(ns_hero.cardSmEmpty('captain'));
    dialog.cont_obj.content_director_area.find('.content_hero_sm').empty().append(ns_hero.cardSmEmpty('director'));
    dialog.cont_obj.content_staff_area.find('.content_hero_sm').empty().append(ns_hero.cardSmEmpty('staff'));

    if (['S', 'T', 'P'].includes(dialog.order_data.cmd_type)) {
        dialog.cont_obj.content_director_area.addCss('disable_status');
        dialog.cont_obj.content_staff_area.addCss('disable_status');
    } else {
        dialog.cont_obj.content_director_area.removeCss('disable_status');
        dialog.cont_obj.content_staff_area.removeCss('disable_status');
    }

    dialog.cont_obj.content_captain_area.find('.content_hero_empty_effect_sm').show();
    dialog.cont_obj.content_director_area.find('.content_hero_empty_effect_sm').show();
    dialog.cont_obj.content_staff_area.find('.content_hero_empty_effect_sm').show();
    dialog.cont_obj.content_captain_area.find('.content_hero_effect_sm').hide();
    dialog.cont_obj.content_director_area.find('.content_hero_effect_sm').hide();
    dialog.cont_obj.content_staff_area.find('.content_hero_effect_sm').hide();
    // 부대사기
    dialog.cont_obj.content_army_fighting_spirit.text(100);

    dialog.drawArmyList();
}

ns_dialog.dialogs.troop_order.unsetHero = function ()
{
    let dialog = ns_dialog.dialogs.troop_order;
    for (let _type of ['captain', 'director', 'staff']) {
        if (dialog.order_data.hero[_type]) {
            ns_cs.d.hero.set(this.order_data.hero[_type], { status_cmd: 'I', cmd_type: 'None' } );
            dialog.order_data.hero[_type] = null;
        }
    }
}

ns_dialog.dialogs.troop_order.drawArmyList = function ()
{
    let dialog = ns_dialog.dialogs.troop_order;
    dialog.cont_obj.troop_order_army_list.empty();
    let cnt = 0;
    for (let [m_army_pk, m] of Object.entries(ns_cs.m.army)) {
        if (! ns_util.isNumeric(m_army_pk) || ns_util.math(ns_cs.d.army[m.code].v).lte(0)) {
            continue;
        }
        let amount = ns_cs.d.army[m.code].v;
        let tr = dialog.cont_obj.skeleton_troop_order_army.clone();
        tr.dataSet('code', m.code);
        tr.find('.item_image').addCss(`army_image_${m.code}`);
        tr.find('.army_title').text(ns_i18n.t(`army_title_${m_army_pk}`));
        tr.find('.army_amount').text(ns_util.numberFormat(amount));

        tr.find('.content_category').addCss(`content_category_${m.category_code}`);

        let slider = tr.find('input[name=develop_slider]');
        slider.setAttribute('min', 0);
        slider.setAttribute('max', amount);
        slider.value(0);
        let number = tr.find('.content_input_number');
        number.setEvent('input', (_e) => {
            let number_value = _e.target.value;
            if (! number_value) {
                number_value = 0;
            }
            if (ns_util.math(number_value).gt(amount)) {
                number_value = amount;
                number.value(amount);
            }
            slider.value(number_value);
            dialog.order_data.army[m.code] = number_value;
            dialog.updateByArmy();
        });
        slider.setEvent('input', (_e) => {
            let number_value = _e.target.value;
            if (this.float_status === true) {
                number_value = ns_util.math(number_value).toFixed(this.float_length);
            }
            number.value(number_value);
            dialog.order_data.army[m.code] = number_value;
            dialog.updateByArmy();
        });
        number.value(0);
        tr.find('.button_set_max').setAttribute('id', `ns_button_troop_order_max_${m.code}`);
        dialog.cont_obj.troop_order_army_list.append(tr);
        cnt++;
        let button_id = `troop_order_max_${m.code}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, null, 'troop_order_max');
        ns_button.buttons[button_id].mouseUp = function ()
        {
            number.value(amount);
            slider.value(amount);
            dialog.order_data.army[m.code] = amount;
            dialog.updateByArmy();
        }
        dialog.buttons.push(ns_button.buttons[button_id]);
    }
    if (cnt < 1) {
        let tr = document.createElement('tr');
        let td = document.createElement('td');
        td.classList.add('empty_list');
        td.innerText = ns_i18n.t('msg_no_possession_army'); // 보유한 병력이 없습니다.
        tr.appendChild(td);
        dialog.cont_obj.troop_order_army_list.append(tr);
    }
    dialog.scroll_handle.initScroll();
}

ns_dialog.dialogs.troop_order.setHeroCallback = function(_hero_pk)
{
    let dialog = ns_dialog.dialogs.troop_order;

    let hero_type = dialog.order_data.hero_type;

    // 이전에 선택했던 영웅상태 변경
    if (dialog.order_data.hero?.[hero_type]) {
        ns_cs.d.hero.set(dialog.order_data.hero[hero_type], { status_cmd:'I', cmd_type:'None' });
    }
    dialog.cont_obj[`content_${hero_type}_area`].find('.content_hero_sm').empty().append(ns_hero.cardSmDraw(_hero_pk));
    dialog.order_data.hero[hero_type] = _hero_pk;

    // 현재 선택된 영웅상태 변경
    if (dialog.order_data.hero[hero_type]) {
        ns_cs.d.hero.set(dialog.order_data.hero[hero_type], { status_cmd:'C', cmd_type:'Order' });
    }

    if(hero_type === 'captain') {
        let leadership = ns_cs.d.hero[_hero_pk].leadership;
        let value = 0;
        let curr_value = 0;

        for (let [k, d] of Object.entries(ns_cs.m.troop['ATTACK_LEAD_POPULATION'])) {
            if (ns_util.math(curr_value).lt(k)) {
                curr_value = ns_util.toInteger(k);
            }
            if (ns_util.math(leadership).lt(k)) {
                value = d['value'];
                break;
            }
        }

        if (! value) {
            value = ns_cs.m.troop['ATTACK_LEAD_POPULATION'][curr_value]['value'];
        }
        let post_data = {};
        post_data['captain_hero_pk'] = _hero_pk;
        post_data['lead_population'] = value;

        ns_xhr.post('/api/troopOrder/info/effectTypes', post_data, function(_data, _status)
        {
            if(! ns_xhr.returnCheck(_data)) {
                return;
            }
            _data = _data['ns_xhr_return']['add_data'];

            dialog.order_data.army_population_suggest = ns_util.toInteger(_data);
            dialog.order_data.default_population_suggest = ns_util.toInteger(_data);

            // 영웅효과
            dialog.cont_obj.content_captain_area.find('.content_hero_empty_effect_sm').hide();
            dialog.cont_obj.content_captain_area.find('.content_hero_effect_sm').show();

            dialog.cont_obj.content_captain_area.find('.content_applied_capa').html(ns_i18n.t('troop_order_army_amount', [String(dialog.order_data.army_population_suggest)]))
            let effects = ns_hero.getEffect(_hero_pk, null, 'assign_troop_order', 'captain');
            dialog.cont_obj.content_captain_area.find('.content_applied_skill').html('');
            if (effects.skill) {
                dialog.cont_obj.content_captain_area.find('.content_applied_skill').html(effects.skill);
            }
            dialog.update();
        });
    } else {
        dialog.update();
        let hero_type = dialog.order_data.hero_type;
        let effects = ns_hero.getEffect(_hero_pk, null, 'assign_troop_order', hero_type);
        if (effects) {
            dialog.cont_obj[`content_${hero_type}_area`].find('.content_hero_empty_effect_sm').hide();
            dialog.cont_obj[`content_${hero_type}_area`].find('.content_hero_effect_sm').show();

            if (effects.capa) {
                dialog.cont_obj[`content_${hero_type}_area`].find('.content_applied_capa').html(effects.capa);
            }
            dialog.cont_obj[`content_${hero_type}_area`].find('.content_applied_skill').html('');
            if (effects.skill) {
                dialog.cont_obj[`content_${hero_type}_area`].find('.content_applied_skill').html(effects.skill);
            }
        }
    }
}

ns_dialog.dialogs.troop_order.setArmyPopulationSuggest = function()
{
    let dialog = ns_dialog.dialogs.troop_order;
    if (! dialog.order_data.hero?.['captain']) {
        return 0;
    }
    // let _default = dialog.order_data.default_population_suggest;
    // let add_population = 0;
    // dialog.order_data.army_population_suggest = ns_util.math(_default).plus(add_population).number;

    return ns_util.numberFormat(dialog.order_data.army_population_suggest);
}

ns_dialog.dialogs.troop_order.updateByArmy = function()
{
    let dialog = ns_dialog.dialogs.troop_order;

    // 감시대상 : 병력 (부대스피드)
    // 갱신대상 : 출동병력, 부대사기, 소요/도착시간, 필요식량, 수송여력 - 병과에 따른 부대의 스피드로 인해서...

    dialog.order_data.population = 0;
    dialog.order_data.need_population = 0;
    dialog.order_data.need_food = 0;
    dialog.order_data.capacity = 0;
    dialog.order_data.troop_speed = 3000;

    for (let [code, amount] of Object.entries(dialog.order_data.army)) {
        let m = ns_cs.m.army[code];
        let value = ns_util.toInteger(amount);
        if (ns_util.math(value).gt(0)) {
            dialog.order_data.need_population += ns_util.math(value).mul(m.need_population).number;
            dialog.order_data.population += value;
            dialog.order_data.need_food += ns_util.math(value).mul(m.need_food).number;
            dialog.order_data.capacity += ns_util.math(value).mul(m.spec_capacity).number;

            // 부대 속도 - 제일 느린 병과 기준
            if (ns_util.math(m.spec_speed).lt(dialog.order_data.troop_speed)) {
                dialog.order_data.troop_speed = ns_util.toInteger(m.spec_speed);
            }
        }
    }

    // 최대 수송력
    let level = ns_cs.d.tech['logistics'].v;
    let m_tech_pk = ns_cs.m.tech['logistics'].m_tech_pk;
    let capacity_value = 0;

    // 태학기술 (병참학 - logistics)
    if (ns_util.math(level).gt(0)) {
        capacity_value = ns_util.math(ns_cs.m.tech_effe[m_tech_pk].level[level]['effect_value']).mul(0.01).number;
    }

    if (ns_util.math(capacity_value).gt(0)) {
        dialog.order_data.capacity += ns_util.math(dialog.order_data.capacity).mul(capacity_value).integer;
    }

    dialog.update();
}

ns_dialog.dialogs.troop_order.updateByInfo = function ()
{
    let dialog = ns_dialog.dialogs.troop_order;
    let [x, y] = dialog.data.coords._posi_pk.split('x');
    let h = ns_button.buttons.troop_order_camp_time_h_input.obj;
    let m = ns_button.buttons.troop_order_camp_time_m_input.obj;
    let target_posi_pk = `${x}x${y}`;

    if (!(! ns_util.math(x).eq(0) && ! ns_util.math(y).eq(0))) {
        ns_dialog.close('troop_order');
        return;
    }
    dialog.order_data.distance = ns_world.distanceValue(ns_engine.game_data.cpp,  target_posi_pk).substring(1);

    if (dialog.order_data?.dst_posi_pk !== target_posi_pk) {
        // 목적지 정보 갱신
        let post_data = {};
        post_data['dst_posi_x'] = x;
        post_data['dst_posi_y'] = y;
        ns_xhr.post('/api/troopOrder/dstPosition', post_data, function (_data, _status) {
            if (!ns_xhr.returnCheck(_data)) {
                return;
            }
            _data = _data['ns_xhr_return']['add_data'];
            dialog.order_data.alli_status = _data['alli_status'];
            dialog.order_data.dst_posi_info = _data;
        });
    }
    dialog.order_data.dst_posi_pk = target_posi_pk;

    if (h.text() || m.text()) {
        dialog.order_data.camp_time = ns_util.math(h.text()).mul(3600).plus(ns_util.math(m.text()).mul(60).number).integer;
    } else {
        dialog.order_data.camp_time = 0; // 값이 없어 졌을 때
    }

    dialog.update();
}

ns_dialog.dialogs.troop_order.update = function()
{
    let dialog = ns_dialog.dialogs.troop_order;
    let move_time;
    // 병과적성 표시
    let army_category = {'infantry':{}, 'spearman':{}, 'pikeman':{}, 'archer':{}, 'horseman':{}, 'siege':{}};

    let captain_hero_pk = dialog.order_data.hero?.['captain'];
    let director_hero_pk = dialog.order_data.hero?.['director'];
    let staff_hero_pk = dialog.order_data.hero?.['staff'];

    if (captain_hero_pk) {
        let mil_aptitude = ns_hero_select.getMilAptitudeArmy(captain_hero_pk);
        for (let [k, d] of Object.entries(army_category)) {
            if (mil_aptitude[k] < d[k] || typeof d === 'object') {
                army_category[k] = mil_aptitude[k];
            }
        }
    }

    if (director_hero_pk) {
        let mil_aptitude = ns_hero_select.getMilAptitudeArmy(director_hero_pk);
        for (let [k, d] of Object.entries(army_category)) {
            if (mil_aptitude[k] < d[k] || typeof d === 'object') {
                army_category[k] = mil_aptitude[k];
            }
        }
    }

    if (staff_hero_pk) {
        let mil_aptitude = ns_hero_select.getMilAptitudeArmy(staff_hero_pk);
        for (let [k, d] of Object.entries(army_category)) {
            if (mil_aptitude[k] < d[k] || typeof d === 'object') {
                army_category[k] = mil_aptitude[k];
            }
        }
    }

    if (captain_hero_pk || director_hero_pk || staff_hero_pk) {
        for (let [k, d] of Object.entries(army_category)) {
            let mil_aptitude = code_set.mil_aptitude_value[d];
            for (let _cate of code_set.army_category[k]) {
                let objs = dialog.cont_obj.troop_order_army_list.findAll(`.content_category_${_cate}`);
                for (let o of objs) {
                    if (o.element) {
                        o.removeCss(['content_mil_text_S', 'content_mil_text_A', 'content_mil_text_B', 'content_mil_text_C', 'content_mil_text_D']).addCss(`content_mil_text_${mil_aptitude}`).text(mil_aptitude);
                    }
                }
            }
        }
    } else {
        for (let _army of ['infantry', 'pikeman', 'spearman', 'archer', 'horseman', 'siege']){
            for (let _cate of code_set.army_category[_army]) {
                let objs = dialog.cont_obj.troop_order_army_list.findAll(`.content_category_${_cate}`);
                for (let o of objs) {
                    if (o.element) {
                        o.removeCss(['content_mil_text_S', 'content_mil_text_A', 'content_mil_text_B', 'content_mil_text_C', 'content_mil_text_D']).addCss('content_mil_text_D').text('D');
                    }
                }
            }
        }
    }

    // 적정병력
    dialog.cont_obj.content_army_population_suggest.text(dialog.setArmyPopulationSuggest());

    // 출동병력
    if (dialog.order_data.population > dialog.order_data.army_population_suggest) {
        dialog.cont_obj.content_army_population_curr.addCss('text_red');
    } else {
        dialog.cont_obj.content_army_population_curr.removeCss('text_red');
    }
    dialog.cont_obj.content_army_population_curr.text(ns_util.numberFormat(dialog.order_data.population));

    // 부대사기 저하량 계산
    let fighting_spirit_down = 0;
    let att_lead_limit_population = dialog.order_data.population - dialog.order_data.army_population_suggest;
    for (let [k, d] of Object.entries(ns_cs.m.troop['FIGHTING_ATTACK_SPIRIT_DOWN'])) {
        if (att_lead_limit_population <= 0) {
            break;
        } else if (att_lead_limit_population < k) {
            fighting_spirit_down = d['value'];
            break;
        }
    }

    // 윤리도
    let power_value = 10;
    let morals = 100;

    if (dialog.order_data.dst_posi_info && dialog.order_data.dst_posi_info.relation !== 'NPC') {
        let def_power = ns_util.toInteger(dialog.order_data.dst_posi_info.power ?? 0);
        if (def_power < 1) {
            def_power = 1;
        }

        if (def_power && ns_cs.d.lord.power.v) {
            let morals_value = ns_util.math(ns_cs.d.lord.power.v).div(def_power).toFixed(3);
            morals = 100 - ((morals_value * power_value) - 10);
        }

        if (morals < 60) {
            morals = 60;
        } else if (morals > 100) {
            morals = 100;
        }

        if (dialog.order_data.dst_posi_info.type ==='P') {
            morals = 100;
        }

        let fighting_spirit = morals - fighting_spirit_down;
        if (fighting_spirit < 1) {
            fighting_spirit = 1;
        }

        dialog.cont_obj.content_army_fighting_spirit.text(ns_util.numberFormat(fighting_spirit));
    } else	{
        let fighting_spirit = 100 - fighting_spirit_down;
        if (fighting_spirit < 1) {
            fighting_spirit = 1;
        }
        dialog.cont_obj.content_army_fighting_spirit.text(ns_util.numberFormat(fighting_spirit));
    }

    // 부대의 수송력
    dialog.cont_obj.content_troop_capacity_possible.text(ns_util.numberFormat(dialog.order_data.capacity));

    // 수송력 여유
    if (dialog.order_data.capacity - dialog.order_data.use_capacity < 0) {
        dialog.cont_obj.content_troop_capacity_possible.addCss('text_red');
    } else {
        dialog.cont_obj.content_troop_capacity_possible.removeCss('text_red');
    }

    // 거리
    // dialog.cont_obj.content_distance.text(dialog.distance);

    // 거리관련 값 초기화
    dialog.order_data.triptime = 0;
    dialog.order_data.round_food = 0;
    dialog.order_data.presence_food = 0;
    dialog.order_data.round_gold = 0;
    dialog.cont_obj.content_move_time.text('');
    dialog.cont_obj.content_arrival_dt.text('');
    if (dialog.cont_obj.troop_order_resource_wrap.find('.content_troop_food').element) {
        dialog.cont_obj.troop_order_resource_wrap.find('.content_troop_food').text(0);
    }

    if (! dialog.order_data.distance) {
        return;
    }

    let troop_speed = dialog.order_data.troop_speed;
    dialog.order_data.triptime = ns_util.math(dialog.order_data.distance).div(troop_speed).mul(100000).integer;

    // 거리 단축 효과 적용
    // 태학 독도법
    let level = ns_cs.d.tech['compass'].v;
    let m_tech_pk = ns_cs.m.tech['compass'].m_tech_pk;
    let value = 0;
    let assign_hero_pk = null;

    if (level > 0) {
        value = ns_util.math(ns_cs.m.tech_effe[m_tech_pk].level[level]['effect_value']).mul(0.01).mul(dialog.order_data.triptime).number;
    }

    // 군사령부 영웅 배속
    let _castle_pk = ns_cs.getCastlePk('I', 200400);
    if (_castle_pk) {
        assign_hero_pk = ns_cs.d.bdic[_castle_pk].assign_hero_pk;
    }

    if (assign_hero_pk) {
        let leadership = ns_cs.d.hero[assign_hero_pk].leadership;
        for (let [k, d] of Object.entries(ns_cs.m.troop['MILITARY_ASSIGN_TROOP_TIME_DECREASE'])) {
            if (ns_util.math(leadership).div(10).lte(k)) {
                value += ns_util.math(d['value']).mul(0.01).mul(dialog.order_data.triptime).number;
                return false;
            }
        }

        // 군사령부 배속 스킬
        let m_cmd_pk, effect_type;
        if (dialog.order_data.cmd_type ==='T') {
            m_cmd_pk = ns_cs.m.cmd['trans'].m_cmd_pk;
        } else {
            m_cmd_pk = ns_cs.m.cmd['troop_captain'].m_cmd_pk;
        }
        effect_type = 'troop_time_decrease';

        let capacity_hero_skil_pks = ns_hero_select.getCapacityHeroSkill(assign_hero_pk, null);
        let applied_hero_skil_pks = ns_hero_select.getAppliedBuilHeroSkill(capacity_hero_skil_pks, 200400);
        let effect_value = ns_hero_select.getAppliedSkillEffectValue(applied_hero_skil_pks, effect_type);
        value += ns_util.math(effect_value).mul(0.01).mul(dialog.order_data.triptime).number;
    }

    // 스킬 적용
    if (dialog.order_data.hero['captain']) {
        let m_cmd_pk, effect_type;
        if (dialog.order_data.cmd_type ==='T') {
            m_cmd_pk = ns_cs.m.cmd['trans'].m_cmd_pk;
        } else {
            m_cmd_pk = ns_cs.m.cmd['troop_captain'].m_cmd_pk;
        }
        effect_type = 'troop_time_decrease';

        let capacity_hero_skil_pks = ns_hero_select.getCapacityHeroSkill(dialog.order_data.hero['captain'], null);
        let applied_hero_skil_pks = ns_hero_select.getAppliedCmdHeroSkill(capacity_hero_skil_pks, m_cmd_pk);
        let effect_value = ns_hero_select.getAppliedSkillEffectValue(applied_hero_skil_pks, effect_type);
        value += ns_util.math(effect_value).mul(0.01).mul(dialog.order_data.triptime).number;
    }

    // 이벤트 아이템 적용
    for (let [k, d] of Object.entries(ns_cs.d.time)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        if (typeof d.description !=='undefined' && String(d.description).substring(7) ==='event_troop' && d.status ==='P') {
            value += ns_util.math(dialog.order_data.triptime).mul(0.2).number;
        }
    }

    let ret = dialog.order_data.triptime - Math.ceil(value);
    if (ns_util.math(dialog.order_data.triptime).mul(0.1).gt(ret)) {
        dialog.order_data.triptime = Math.ceil(ns_util.math(dialog.order_data.triptime).mul(0.1).number);
    } else {
        dialog.order_data.triptime -= Math.ceil(value);
    }

    if (['R', 'T', 'P'].includes(dialog.order_data.cmd_type)) {
        dialog.order_data.triptime = ns_util.math(dialog.order_data.triptime).div(2).integer;
    }

    // 필요 식량
    for (let [code, amount] of Object.entries(dialog.order_data.army)) {
        let d = ns_cs.m.army[code];
        let value = ns_util.toInteger(amount);
        if (ns_util.math(amount).gt(0)) {
            dialog.order_data.round_food += ns_util.math(dialog.order_data.triptime).mul(2).plus(dialog.order_data.camp_time).div(3600).mul(d.need_food).mul(value).number;
        }
    }

    if (ns_util.math(dialog.order_data.round_food).lt(0)) {
        dialog.order_data.round_food = 1;
    }
    dialog.order_data.presence_food = ns_util.math(dialog.order_data.need_food).div(2).number; // hour_food 가 비상식량 일때는 7일치임.
    dialog.order_data.round_gold = 0;

    move_time = ns_util.math(dialog.order_data.triptime).plus(dialog.order_data.camp_time).number;
    // 소요시간
    dialog.cont_obj.content_move_time.text(ns_util.getCostsTime(move_time));
    // 도착시간
    dialog.cont_obj.content_arrival_dt.text(ns_timer.getDateTimeString(ns_util.math(ns_timer.now()).plus(move_time).number, false, true, true));

    // 필요식량
    if (dialog.cont_obj.troop_order_resource_wrap.find('.content_troop_food').element) {
        dialog.cont_obj.troop_order_resource_wrap.find('.content_troop_food').text(ns_util.numberFormat(ns_util.math(dialog.order_data.round_food).plus(dialog.order_data.presence_food).integer));
    }
}

ns_dialog.dialogs.troop_order.checkPossibleCmd = function ()
{
    // 명령수행 가능여부 검사
    let cmd_type_possible = false;
    let dialog = ns_dialog.dialogs.troop_order;
    let order_data = dialog.order_data;
    let cmd_type = order_data.cmd_type;
    let dst_posi_info = order_data?.dst_posi_info;

    if (cmd_type && dst_posi_info) {
        if (ns_util.math(order_data.distance).eq(0)) {
            // curr_posi_pk 로는 아무것도 못함.
        } else if (cmd_type === 'T') {
            // 내/동맹 영지 -> 타유저영지는 무조건 가능
            if (dst_posi_info.type === 'T' && dst_posi_info.relation !== 'NPC') {
                cmd_type_possible = true;
            }
            if (dst_posi_info.type === 'P') {
                cmd_type_possible = false;
            }
        } else if (cmd_type ==='P') {
            // 내 주둔군
            if (dst_posi_info.my_camp_troop === 'Y') {
                cmd_type_possible = true;
            }
            if (dst_posi_info.type ==='P') {
                cmd_type_possible = false;
            }
        } else if (cmd_type ==='R') {
            // 내 영지/자원지
            if (dst_posi_info.relation ==='MIME') {
                cmd_type_possible = true;
            }

            // 동맹/우호 영지 -> 타유저영지는 무조건 가능
            if (dst_posi_info.type ==='T' && dst_posi_info.relation !== 'NPC') {
                cmd_type_possible = true;
            }
        } else if (cmd_type ==='S' || cmd_type ==='A') {
            // NPC/LORD, 보호모드 제외
            if (dst_posi_info.truce ==='N' && (dst_posi_info.relation ==='NPC' || dst_posi_info.relation ==='LORD')) {
                cmd_type_possible = true;
            }

            if (dst_posi_info.type ==='P') {
                cmd_type_possible = true;
            }

            // 내 영지/자원지
            if (dst_posi_info.relation ==='MIME') {
                cmd_type_possible = false;
            }
        }
        if (dst_posi_info.type === 'P') {
            cmd_type_possible = false;
        }
        if (dst_posi_info.name === codeset.t('valley', 'D')) { // TODO 이거 왜 이걸로 체크하냐?
            cmd_type_possible = false;
        }
    }
    return cmd_type_possible;
}

ns_dialog.dialogs.troop_order.updateByResource = function(_e)
{
    let dialog = ns_dialog.dialogs.troop_order;
    if (! dialog.order_data?.resource_type)	{
        return;
    }
    let resources = ['food', 'horse', 'lumber', 'iron', 'gold'];
    if (['A', 'S'].includes(dialog.order_data.cmd_type)) {
        resources = ['food'];
    }

    let total_capacity = 0;
    for (let _type of resources) {
        if (ns_button.buttons[`troop_order_resource_${_type}`].obj?.element) {
            total_capacity += ns_util.toInteger(ns_button.buttons[`troop_order_resource_${_type}`].obj.text());
        }
    }

    let target_button = ns_button.buttons[`troop_order_resource_${dialog.order_data.resource_type}`].obj;
    let limit_capacity = ns_util.math(dialog.order_data.capacity).minus(ns_util.math(total_capacity).minus(target_button.text()).number).integer;
    if (ns_util.math(limit_capacity).lt(1)) {
        target_button.text(0);
    } else if (ns_util.math(target_button.text()).gt(limit_capacity)) {
        target_button.text(limit_capacity);
    }

    // 감시대상 : 자원
    // 갱신대상 : 수송여력

    dialog.order_data.use_capacity = 0;
    for (let _type of resources) {
        // TODO c,d 사용하는 곳이 없는데?
        // let c = (_type === 'food') ? ns_util.math(ns_dialog.dialogs.troop_order.round_food).plus(ns_dialog.dialogs.troop_order.presence_food).number : 0;
        // let d = (_type === 'gold') ? ns_util.toInteger(ns_cs.getTerritoryInfo('gold_curr')) : ns_util.toInteger(ns_cs.getResourceInfo(`${_type}_curr`));
        let value = ns_util.toInteger(ns_button.buttons[`troop_order_resource_${_type}`].obj.text());
        if (ns_util.math(value).gt(0)) {
            dialog.order_data.use_capacity += value;
        }
    }

    dialog.timerHandlerReal();
    dialog.update();
}

ns_dialog.dialogs.troop_order.resourceButtons = function()
{
    let dialog = ns_dialog.dialogs.troop_order;

    let resources = ['food', 'horse', 'lumber', 'iron', 'gold'];
    if (['A', 'S'].includes(dialog.order_data.cmd_type)) {
        resources = ['food'];
    }

    let resource_table;
    dialog.cont_obj.troop_order_resource_wrap.empty();
    if (['A', 'S'].includes(dialog.order_data.cmd_type)) {
        resource_table = dialog.cont_obj.skeleton_troop_order_resource_attack.clone();
    } else {
        resource_table = dialog.cont_obj.skeleton_troop_order_resource_transport.clone();
    }
    dialog.cont_obj.troop_order_resource_wrap.append(resource_table);
    for (let code of resources) {
        let button = dialog.cont_obj.troop_order_resource_wrap.find(`span[data-button="troop_order_resource_${code}"]`);
        let button_id = button.dataSet('button');
        button.setAttribute('id', `ns_button_${button_id}`);
        ns_button.buttons[button_id] = new nsButtonSet(button_id, null, 'troop_order', { base_class: ns_button.buttons.troop_order_resource });

        let updown = dialog.cont_obj.troop_order_resource_wrap.find(`span[data-button="troop_order_resource_${code}_updown"]`);
        let updown_id = updown.dataSet('button');
        updown.setAttribute('id', `ns_button_${updown_id}_updown`);
        ns_button.buttons[`${updown_id}_updown`] = new nsButtonSet(`${updown_id}_updown`, null, 'troop_order', { base_class: ns_button.buttons.troop_order_resource } );
    }
}

ns_dialog.dialogs.troop_order.loadPreset = function(_slot_number)
{
    let dialog = ns_dialog.dialogs.troop_order;
    let troop_order_preset = ns_dialog.dialogs.troop_order_preset;
    let _data = troop_order_preset.preset_list.find(o => ns_util.math(o.slot_number).eq(_slot_number));
    if (! _data) {
        return;
    }
    _data = _data.order_data;
    if (! ns_util.math(dialog.current_shortcut).eq(0)) {
        ns_button.buttons[`preset_shortcut_${dialog.current_shortcut}`].unsetClicked();
    }
    ns_button.buttons[`preset_shortcut_${_slot_number}`].setClicked();

    // 초기화
    dialog.unsetHero();
    dialog.initData();

    // 적용
    if (_data?.hero) {
        for (let [hero_type, hero_pk] of Object.entries(_data.hero)) {
            if (hero_type !== 'captain' && ['S', 'T'].includes(dialog.order_data.cmd_type)) {
                continue;
            }
            dialog.order_data.hero_type = hero_type;
            if (ns_cs.d.hero[hero_pk].status_cmd === 'I' && ns_cs.d.hero[hero_pk].cmd_type === 'None') {
                dialog.setHeroCallback(hero_pk);
            }
        }
    }
    if (_data?.army) {
        for (let [code, amount] of Object.entries(_data.army)) {
            let tr = dialog.cont_obj.troop_order_army_list.find(`tr[data-code=${code}]`, true);
            let slider = tr.find('input[name=develop_slider]');
            let number = tr.find('.content_input_number');
            amount = (ns_util.math(amount).gt(ns_cs.d.army[code].v)) ? ns_cs.d.army[code].v : amount;
            number.value(amount);
            slider.value(amount);
            dialog.order_data.army[code] = amount;
        }
        dialog.updateByArmy();
    }
    if (_data?.use_capacity) {
        let value = (ns_util.math(ns_cs.getResourceInfo('food_curr')).lt(_data.use_capacity)) ? ns_cs.getResourceInfo('food_curr') : _data.use_capacity;
        value = (ns_util.math(value).gt(dialog.order_data.capacity)) ? dialog.order_data.capacity : value;
        ns_button.buttons[`troop_order_resource_food`].obj.text(value);
        dialog.order_data.use_capacity = _data.use_capacity;
    }

    dialog.updateByInfo();
    dialog.current_shortcut = _slot_number;
}

ns_dialog.dialogs.troop_order.drawShortcut = function ()
{
    let dialog = ns_dialog.dialogs.troop_order_preset;

    for (let _i of Array.from({length: 10}, (_, i) => i + 1)) {
        if (dialog.preset_list.some(o => ns_util.math(o.slot_number).eq(_i))) {
            ns_button.buttons[`preset_shortcut_${_i}`].setEnable();
        } else {
            ns_button.buttons[`preset_shortcut_${_i}`].setDisable();
        }
    }
}

ns_dialog.dialogs.troop_order.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.troop_order.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.troop_order.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.troop_order;
    let resources = ['food', 'horse', 'lumber', 'iron', 'gold'];
    if (['A', 'S'].includes(dialog.order_data.cmd_type)) {
        resources = ['food'];
    }

    for (let _type of resources) {
        let c, p;
        if (_type === 'food') {
            c = ns_util.math(ns_button.buttons.troop_order_resource_food.obj.text()).plus(dialog.order_data.round_food).plus(dialog.order_data.presence_food).integer;
            p = ns_util.math(ns_cs.getResourceInfo('food_curr')).integer;
        } else if (_type === 'gold') {
            c = ns_util.math(ns_button.buttons.troop_order_resource_gold.obj.text()).plus(dialog.order_data.round_gold).integer;
            p = ns_util.math(ns_cs.getTerritoryInfo('gold_curr')).minus(c).integer;
        } else {
            c = ns_util.toInteger(ns_button.buttons[`troop_order_resource_${_type}`].obj.text());
            p = ns_util.math(ns_cs.getResourceInfo(`${_type}_curr`)).minus(c).integer;
        }
        let target = dialog.cont_obj.dialog_content.find(`.ns_resource_${_type}_curr`);
        if (target.element) {
            if (p < 0) {
                target.addCss('text_red').text(ns_util.numberFormat(p));
            } else {
                target.removeCss('text_red').text(ns_util.numberFormat(p));
            }
        }
    }

    // 도착시간 갱신
    let move_time = ns_util.math(dialog.order_data.triptime).plus(dialog.order_data.camp_time).number;
    dialog.cont_obj.content_arrival_dt.text(ns_timer.getDateTimeString(ns_util.math(ns_timer.now()).plus(move_time).number, false, true, true));
}

ns_button.buttons.troop_order_close = new nsButtonSet('troop_order_close', 'button_back', 'troop_order', { base_class: ns_button.buttons.common_close } );
ns_button.buttons.troop_order_sub_close = new nsButtonSet('troop_order_sub_close', 'button_full', 'troop_order', { base_class: ns_button.buttons.common_sub_close } );
ns_button.buttons.troop_order_close_all = new nsButtonSet('troop_order_close_all', 'button_close_all', 'troop_order', { base_class: ns_button.buttons.common_close_all  } );

ns_button.buttons.troop_order_hero_captain = new nsButtonSet('troop_order_hero_captain', 'button_full', 'troop_order');
ns_button.buttons.troop_order_hero_captain.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.troop_order;
    let data = dialog.data;
    dialog.order_data.hero_type = this.tag_id.split('_').pop();
    let hero_type = dialog.order_data.hero_type;
    let cmd_type;
    if (data.cmd_type === 'transport') {
        cmd_type = 'T';
    } else if (data.cmd_type === 'supply') {
        cmd_type = 'P';
    } else if (data.cmd_type === 'scout') {
        cmd_type = 'S';
    } else if (data.cmd_type === 'reinforce') {
        cmd_type = 'R';
    } else {
        cmd_type = 'A';
    }
    if (['S', 'T', 'P'].includes(cmd_type) && hero_type !== 'captain') {
        return;
    }
    let hero_sel_data = {
        type: 'assign_troop_order',
        type_data: hero_type,
        do_callback: dialog.setHeroCallback,
        nosel_title: code_set.troop_hero[hero_type],
        nosel_desc: (['S', 'T', 'P'].includes(cmd_type)) ? ns_i18n.t(`cmd_troop_hero_${cmd_type}_description`) : ns_i18n.t(`troop_hero_${hero_type}_description`),
        prev_hero_pk: (dialog.order_data.hero?.[hero_type]) ? dialog.order_data.hero[hero_type] : null,
        prev_hero_undo: !!dialog.order_data.hero?.[hero_type],
        selector_use: !dialog.order_data.hero?.[hero_type]
    };
    if (hero_type === 'captain') {
        if (cmd_type === 'S') {
            hero_sel_data.sort_stat_type = 'intellect';
        } else {
            hero_sel_data.sort_stat_type = 'leadership';
        }
    } else {
        hero_sel_data.sort_stat_type = (hero_type === 'director') ? 'mil_force' : 'intellect';
    }
    hero_sel_data.limit_stat_type = 'leadership';
    hero_sel_data.limit_stat_value = 1;

    hero_sel_data.undo_callback = function(_data)
    {
        ns_cs.d.hero.set(dialog.order_data.hero[hero_type], { status_cmd:'I', cmd_type:'None' } );
        dialog.order_data.hero[hero_type] = null;

        dialog.cont_obj[`content_${hero_type}_area`].find('.content_hero_sm').empty().append(ns_hero.cardSmEmpty(hero_type));
        dialog.cont_obj[`content_${hero_type}_area`].find('.content_hero_empty_effect_sm').show();
        dialog.cont_obj[`content_${hero_type}_area`].find('.content_hero_effect_sm').hide();

        if (hero_type === 'captain') {
            dialog.order_data.army_population_suggest = 0;
        }
        dialog.update();
        dialog.updateByInfo();
    };
    ns_dialog.setDataOpen('hero_select', hero_sel_data);
}
ns_button.buttons.troop_order_hero_director = new nsButtonSet('troop_order_hero_director', 'button_full', 'troop_order', { base_class: ns_button.buttons.troop_order_hero_captain } );
ns_button.buttons.troop_order_hero_staff = new nsButtonSet('troop_order_hero_staff', 'button_full', 'troop_order', { base_class: ns_button.buttons.troop_order_hero_captain } );

ns_button.buttons.troop_order_camp_time_h_input = new nsButtonSet('troop_order_camp_time_h_input', 'button_input', 'troop_order');
ns_button.buttons.troop_order_camp_time_h_input.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('keypad', { max: 99, min: 0,
        callback: function (data)
        {
            let dialog = ns_dialog.dialogs.troop_order;
            ns_button.buttons.troop_order_camp_time_h_input.obj.text(data);
            dialog.updateByInfo();
        }
    });
}

ns_button.buttons.troop_order_camp_time_m_input = new nsButtonSet('troop_order_camp_time_m_input', 'button_input', 'troop_order');
ns_button.buttons.troop_order_camp_time_m_input.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('keypad', { max: 59, min: 0,
        callback: function(data)
        {
            let dialog = ns_dialog.dialogs.troop_order;
            ns_button.buttons.troop_order_camp_time_m_input.obj.text(data);
            dialog.updateByInfo();
        }
    });
}

ns_button.buttons.troop_order_resource = new nsButtonSet('troop_order_resource', null, 'troop_order');
ns_button.buttons.troop_order_resource.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.troop_order;
    let arr = this.tag_id.split('_');
    let resource_type = arr.pop();
    let clicked_type = 'input';

    if (resource_type === 'updown') {
        clicked_type = 'arrow';
        resource_type = arr[arr.length - 2];
    }

    dialog.order_data.resource_type = resource_type;
    /*if (dialog.order_data.cmd_type === 'P' && resource_type !== 'food') {
        return;
    }*/

    if (dialog.order_data.cmd_type === 'S') {
        return;
    }

    let resources = ['food', 'horse', 'lumber', 'iron', 'gold'];
    if (['A', 'S'].includes(dialog.order_data.cmd_type)) {
        resources = ['food'];
    }

    let resource_value = (dialog.order_data.resource_type === 'gold') ? ns_util.toInteger(ns_cs.getTerritoryInfo('gold_curr')) : ns_util.toInteger(ns_cs.getResourceInfo(`${resource_type}_curr`));
    if (ns_util.math(resource_value).lt(1)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_no_possession_resource')); // 해당 자원이 없습니다.
        return;
    }


    // dialog.order_data.capacity = 부대의 최대 수송력
    let resource_max = (ns_util.math(resource_value).lt(dialog.order_data.capacity)) ? resource_value : dialog.order_data.capacity; // 보유 자원

    let current_capacity = 0;
    for (let _type of resources) {
        current_capacity += ns_util.toInteger(ns_button.buttons[`troop_order_resource_${_type}`].obj.text());
    }
    resource_max = ns_util.math(resource_max).minus(current_capacity).integer;

    if (clicked_type === 'input') {
        ns_dialog.setDataOpen('keypad', {max: resource_max, min: 0,
            callback: function(data)
            {
                ns_button.buttons[`troop_order_resource_${resource_type}`].obj.text(data);
                dialog.updateByResource();
            }
        });
    } else if (clicked_type === 'arrow') {
        let current_value = ns_button.buttons[`troop_order_resource_${resource_type}`].obj;
        if (ns_util.math(current_value.text()).lt(resource_max)) {
            current_value.text(resource_max);
        } else {
            current_value.text(0);
        }
        dialog.updateByResource();
    }
}

ns_button.buttons.troop_order_do = new nsButtonSet('troop_order_do', 'button_default', 'troop_order');
ns_button.buttons.troop_order_do.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.troop_order;
    let pass = true;
    let invalid_fields = [];

    // 체크 - 기본정보
    let order_data = dialog.order_data;

    // 명령유형
    if (! order_data?.cmd_type) {
        pass = false;
        invalid_fields[invalid_fields.length] = ns_i18n.t('msg_troop_order_invalid_field_cmd_type'); // 수행할 명령 유형이 선택되지 않았습니다.
    } else {
        if (dialog.checkPossibleCmd() === false) {
            if (!(!order_data.dst_posi_pk || ns_util.math(order_data.distance).eq(0))) {
                pass = false;
                invalid_fields[invalid_fields.length] = ns_i18n.t('msg_troop_order_invalid_position_error'); // 해당 좌표로 수행할 수 없는 명령입니다.
            }
        }
    }

    // 목적지 - 이동거리 0.0 는 안됨!
    if ((! order_data.dst_posi_pk || ns_util.math(order_data.distance).eq(0))) {
        pass = false;
        invalid_fields[invalid_fields.length] = ns_i18n.t('msg_troop_order_invalid_position_empty'); // 목적지가 입력되지 않았습니다.
    }

    // 체크 - 주장
    if (! order_data.hero?.['captain']) {
        pass = false;
        invalid_fields[invalid_fields.length] = ns_i18n.t('msg_troop_order_invalid_captain_empty'); // 작전을 수행할 주장이 선택되지 않았습니다.
    }

    // 체크 - 병력
    if (! order_data.capacity) {
        pass = false;
        invalid_fields[invalid_fields.length] = ns_i18n.t('msg_troop_order_invalid_army_error'); // 작전을 수행할 병력이 지정되지 않았습니다.
    } else {
        for (let [code, amount] of Object.entries(order_data.army)) {
            let d = ns_cs.d.army[code].v;
            let m = ns_cs.m.army[code];
            if (ns_util.math(amount).gt(d)) {
                pass = false;
                invalid_fields[invalid_fields.length] = ns_i18n.t('msg_troop_order_invalid_army_over', [m.title]); // 병력이 보유 병력보다 많습니다.
            }
        }
    }

    // 출병 병력 제한
    if (ns_util.math(order_data.population).gt(300000)) {
        pass = false;
        invalid_fields[invalid_fields.length] = ns_i18n.t('msg_troop_order_invalid_army_max'); // 출정 병력수는 최대 300,000까지 가능 합니다.
    }

    // 체크 - 자원
    let resources = ['food', 'horse', 'lumber', 'iron', 'gold'];
    if (['A', 'S'].includes(order_data.cmd_type)) {
        resources = ['food'];
    }
    for (let _type of resources) {
        let s;
        if (_type === 'food') {
            s = ns_util.math(ns_button.buttons.troop_order_resource_food.obj.text()).plus(order_data.round_food).plus(order_data.presence_food).integer;
        } else {
            s = ns_button.buttons[`troop_order_resource_${_type}`].obj.text()
        }

        if (ns_util.math(s).gt((_type === 'gold') ? ns_cs.getTerritoryInfo('gold_curr') : ns_cs.getResourceInfo(`${_type}_curr`))) {
            pass = false;
            invalid_fields[invalid_fields.length] = ns_i18n.t('msg_troop_order_invalid_resource_lack', [ns_i18n.t(`resource_${_type}`)]); // 명령 수행을 위해 필요한 $1이 보유한 수량보다 많습니다.
        }
    }

    // 체크 - 수송력 여유
    if (order_data.capacity < order_data.use_capacity) {
        pass = false;
        invalid_fields[invalid_fields.length] = ns_i18n.t('msg_troop_order_invalid_use_capacity'); // 가용한 수송력보다 수송할 자원량이 많습니다.
    }

    if (! pass) {
        let error_message = ns_i18n.t('msg_error_notice') + '<br /><br/>'; // 아래와 같은 오류로 수행이 불가능 합니다.
        for (let i = 0; i < invalid_fields.length; i++) {
            error_message += invalid_fields[i] + '<br/>';
        }

        ns_dialog.setDataOpen('message', error_message);
        return false;
    }

    const _troopOrder = () =>
    {
        let [x, y] = order_data.dst_posi_pk.split('x');

        // serialize & field addition
        let post_data = {};
        post_data['src_posi_pk'] = ns_engine.game_data.cpp;
        post_data['dst_posi_pk'] = order_data.dst_posi_pk;
        post_data['captain_hero_pk'] = order_data.hero['captain'];
        post_data['director_hero_pk'] = order_data.hero['director'];
        post_data['staff_hero_pk'] = order_data.hero['staff'];
        post_data['select_item_pk'] = null; // order_data.select_item_pk;
        post_data['cmd_type'] = order_data.cmd_type;
        post_data['dst_posi_x'] = x;
        post_data['dst_posi_y'] = y;
        post_data['camp_time_h'] = ns_button.buttons.troop_order_camp_time_h_input.obj.text();
        post_data['camp_time_m'] = ns_button.buttons.troop_order_camp_time_m_input.obj.text();
        for (let _type of code_set.army_code) {
            post_data[`army_${_type}`] = order_data.army[_type] ?? 0;
        }
        for (let _type of ['food', 'horse', 'lumber', 'iron', 'gold']) {
            if (ns_button.buttons[`troop_order_resource_${_type}`]) {
                post_data[`reso_${_type}`] = ns_button.buttons[`troop_order_resource_${_type}`].obj.text();
            } else {
                post_data[`reso_${_type}`] = 0;
            }
        }
        dialog.order_data.troop_do_status = true;

        ns_xhr.post('/api/troopOrder', post_data, function(_data, _status)
        {
            if(! ns_xhr.returnCheck(_data)) {
                dialog.troop_do_status = false;
                return;
            }
            _data = _data['ns_xhr_return']['add_data'];

            //ns_dialog.setDataOpen('message', system_text.message.troop_order_go_battle); /* 부대가 출진 하였습니다. */
            // counsel
            ns_dialog.setDataOpen('counsel', { type: 'action', counsel_type: code_set.troop_long_cmd_type[order_data.cmd_type], hero_pk: order_data.hero['captain'], cost: _data.move_time });
            ns_dialog.close(dialog.tag_id);
        }, { useProgress: true });
    }

    // 보호 모드 확인
    let is_beginner_truce = Object.values(ns_cs.d.time).some(o => o?.queue_pk && [500015, 500105, 500108].includes(Number(o.queue_pk)) && o.queue_action === 'B' && o.queue_type === 'D');
    if (is_beginner_truce && order_data.dst_posi_info.relation === 'LORD') {
        ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_attack_end_truce_status_confirm'), okFunc : _troopOrder }); // 출병 명령시 보호 모드가 즉시 종료됩니다.<br />계속 진행하시겠습니까?
    } else {
        _troopOrder();
    }
}

ns_button.buttons.preset_slot_manage = new nsButtonSet('preset_slot_manage', 'button_set_slot', 'troop_order');
ns_button.buttons.preset_slot_manage.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.troop_order;
    let order_data = dialog.order_data;

    // 저장에 필요한 정보만 넘겨줌.
    ns_dialog.setDataOpen('troop_order_preset', {
        use_capacity: order_data.use_capacity,
        hero: order_data.hero,
        army: order_data.army
    });
}

ns_button.buttons.preset_slot_change = new nsButtonSet('preset_slot_change', 'button_change_slot', 'troop_order');
ns_button.buttons.preset_slot_change.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.troop_order;

    if (dialog.cont_obj.slot_page_2.hasCss('hide')) {
        dialog.cont_obj.slot_page_1.hide();
        dialog.cont_obj.slot_page_2.show();
    } else {
        dialog.cont_obj.slot_page_1.show();
        dialog.cont_obj.slot_page_2.hide();
    }
}

ns_button.buttons.preset_shortcut_1 = new nsButtonSet('preset_shortcut_1', 'button_preset_slot', 'troop_order');
ns_button.buttons.preset_shortcut_1.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.troop_order;
    let slot_number = this.tag_id.split('_').pop();

    dialog.loadPreset(slot_number);
}
ns_button.buttons.preset_shortcut_2 = new nsButtonSet('preset_shortcut_2', 'button_preset_slot', 'troop_order', { base_class: ns_button.buttons.preset_shortcut_1 });
ns_button.buttons.preset_shortcut_3 = new nsButtonSet('preset_shortcut_3', 'button_preset_slot', 'troop_order', { base_class: ns_button.buttons.preset_shortcut_1 });
ns_button.buttons.preset_shortcut_4 = new nsButtonSet('preset_shortcut_4', 'button_preset_slot', 'troop_order', { base_class: ns_button.buttons.preset_shortcut_1 });
ns_button.buttons.preset_shortcut_5 = new nsButtonSet('preset_shortcut_5', 'button_preset_slot', 'troop_order', { base_class: ns_button.buttons.preset_shortcut_1 });
ns_button.buttons.preset_shortcut_6 = new nsButtonSet('preset_shortcut_6', 'button_preset_slot', 'troop_order', { base_class: ns_button.buttons.preset_shortcut_1 });
ns_button.buttons.preset_shortcut_7 = new nsButtonSet('preset_shortcut_7', 'button_preset_slot', 'troop_order', { base_class: ns_button.buttons.preset_shortcut_1 });
ns_button.buttons.preset_shortcut_8 = new nsButtonSet('preset_shortcut_8', 'button_preset_slot', 'troop_order', { base_class: ns_button.buttons.preset_shortcut_1 });
ns_button.buttons.preset_shortcut_9 = new nsButtonSet('preset_shortcut_9', 'button_preset_slot', 'troop_order', { base_class: ns_button.buttons.preset_shortcut_1 });
ns_button.buttons.preset_shortcut_10 = new nsButtonSet('preset_shortcut_10', 'button_preset_slot', 'troop_order', { base_class: ns_button.buttons.preset_shortcut_1 });

ns_dialog.dialogs.troop_order_preset = new nsDialogSet('troop_order_preset', 'dialog_pop', 'size-medium', { do_content_scroll: false });
ns_dialog.dialogs.troop_order_preset.slot_number = 1;
ns_dialog.dialogs.troop_order_preset.preset_list = [];

ns_dialog.dialogs.troop_order_preset.cacheContents = function ()
{
    for (let _i of Array.from({length: 10}, (_, i) => i + 1)) {
        this.cont_obj[`preset_slot_${_i}`] = new nsObject(`.preset_slot.slot_${_i}`, this.obj);
    }

    this.cont_obj.preset_title = new nsObject('input[name=preset_title]', this.obj);

    this.cont_obj.preset_slot_page_1 = new nsObject('.preset_slot_page_1', this.obj);
    this.cont_obj.preset_slot_page_2 = new nsObject('.preset_slot_page_2', this.obj);
}

ns_dialog.dialogs.troop_order_preset.draw = function ()
{
    this.cont_obj.preset_slot_page_1.show();
    this.cont_obj.preset_slot_page_2.hide();
    this.presetList();
}

ns_dialog.dialogs.troop_order_preset.selectSlot = function (_slot_number)
{
    let dialog = ns_dialog.dialogs.troop_order_preset;

    for (let _i of Array.from({length: 10}, (_, i) => i + 1)) {
        if (ns_util.math(_i).eq(_slot_number)) {
            dialog.cont_obj[`preset_slot_${_i}`].addCss('selected');

            let preset = dialog.preset_list.find(o => ns_util.math(o.slot_number).eq(_i));
            let title = (! preset || ! preset['preset_title']) ? `${_i}번_슬롯` : preset['preset_title'];
            dialog.cont_obj.preset_title.value(title);

            if (dialog.cont_obj[`preset_slot_${_i}`].hasCss('disable')) {
                ns_button.buttons.preset_load.setDisable();
            } else {
                ns_button.buttons.preset_load.setEnable();
            }
            dialog.slot_number = _slot_number;
        } else {
            dialog.cont_obj[`preset_slot_${_i}`].removeCss('selected');
        }
    }
}

ns_dialog.dialogs.troop_order_preset.drawPresetList = function ()
{
    let dialog = ns_dialog.dialogs.troop_order_preset;

    for (let _i of Array.from({length: 10}, (_, i) => i + 1)) {
        if (dialog.preset_list.some(o => ns_util.math(o.slot_number).eq(_i))) {
            dialog.cont_obj[`preset_slot_${_i}`].removeCss('disable');
            let preset = dialog.preset_list.find(o => ns_util.math(o.slot_number).eq(_i));
            let title = (! preset['preset_title']) ? ns_i18n.t('number_preset', [_i]) : preset['preset_title'];
            dialog.cont_obj[`preset_slot_${_i}`].find('.preset_title').text(title);
            dialog.preset_list.push(preset);
        } else {
            dialog.cont_obj[`preset_slot_${_i}`].addCss('disable');
            dialog.cont_obj[`preset_slot_${_i}`].find('.preset_title').text(ns_i18n.t('empty_preset'));
        }
    }
    dialog.selectSlot(1);
}

ns_dialog.dialogs.troop_order_preset.presetList = function ()
{
    let dialog = ns_dialog.dialogs.troop_order_preset;

    dialog.preset_list = [];
    ns_xhr.post('/api/troopOrder/preset', {}, (_data, _status) => {
        if (!ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];
    });
}

ns_button.buttons.troop_order_preset_close = new nsButtonSet('troop_order_preset_close', 'button_pop_close', 'troop_order_preset', {base_class: ns_button.buttons.common_close});
ns_button.buttons.troop_order_preset_sub_close = new nsButtonSet('troop_order_preset_sub_close', 'button_full', 'troop_order_preset', {base_class: ns_button.buttons.common_sub_close});

ns_button.buttons.preset_slot_1 = new nsButtonSet('preset_slot_1', 'button_full', 'troop_order_preset');
ns_button.buttons.preset_slot_1.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.troop_order_preset;
    let slot_number = this.tag_id.split('_').pop();
    dialog.selectSlot(slot_number);
}
ns_button.buttons.preset_slot_2 = new nsButtonSet('preset_slot_2', 'button_full', 'troop_order_preset', {base_class: ns_button.buttons.preset_slot_1});
ns_button.buttons.preset_slot_3 = new nsButtonSet('preset_slot_3', 'button_full', 'troop_order_preset', {base_class: ns_button.buttons.preset_slot_1});
ns_button.buttons.preset_slot_4 = new nsButtonSet('preset_slot_4', 'button_full', 'troop_order_preset', {base_class: ns_button.buttons.preset_slot_1});
ns_button.buttons.preset_slot_5 = new nsButtonSet('preset_slot_5', 'button_full', 'troop_order_preset', {base_class: ns_button.buttons.preset_slot_1});
ns_button.buttons.preset_slot_6 = new nsButtonSet('preset_slot_6', 'button_full', 'troop_order_preset', {base_class: ns_button.buttons.preset_slot_1});
ns_button.buttons.preset_slot_7 = new nsButtonSet('preset_slot_7', 'button_full', 'troop_order_preset', {base_class: ns_button.buttons.preset_slot_1});
ns_button.buttons.preset_slot_8 = new nsButtonSet('preset_slot_8', 'button_full', 'troop_order_preset', {base_class: ns_button.buttons.preset_slot_1});
ns_button.buttons.preset_slot_9 = new nsButtonSet('preset_slot_9', 'button_full', 'troop_order_preset', {base_class: ns_button.buttons.preset_slot_1});
ns_button.buttons.preset_slot_10 = new nsButtonSet('preset_slot_10', 'button_full', 'troop_order_preset', {base_class: ns_button.buttons.preset_slot_1});

ns_button.buttons.preset_save = new nsButtonSet('preset_save', 'button_pop_normal', 'troop_order_preset');
ns_button.buttons.preset_save.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.troop_order_preset;

    let title = dialog.cont_obj.preset_title.value();
    if (title.search(/[^\uac00-\ud7a3\u3131-\u314e\u314f-\u3163\w\d]/g) >= 0) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_troop_preset_restrictions')); // 저장명은 영문자, 한글, 숫자, _(밑줄)만이 가능합니다.
        return;
    }
    if (ns_util.math(title.length).gt(10)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_troop_preset_max_length')); // 저장명은 최대 10자까지 입력 가능합니다.
        return;
    }

    const okFunc = function ()
    {
        let post_data = {};
        post_data['slot_number'] = dialog.slot_number;
        post_data['order_data'] = JSON.stringify(dialog.data);
        post_data['preset_title'] = (title && title !== '') ? title : null;

        ns_xhr.post('/api/troopOrder/presetSave', post_data, (_data, _status) => {
            if (!ns_xhr.returnCheck(_data)) {
                return;
            }
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_troop_preset_save_complete')); // 부대 프리셋을 저장하였습니다.
            ns_dialog.close('troop_order_preset');
        }, { useProgress: true });
    }

    if (dialog.preset_list.find(o => ns_util.math(o.slot_number).eq(dialog.slot_number))) {
        ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_troop_preset_overwrite_confirm'), okFunc: okFunc }); // 이미 저장된 프리셋이 있습니다. 프리셋을 덮어쓰시겠습니까?
    } else {
        okFunc();
    }
}

ns_button.buttons.preset_load = new nsButtonSet('preset_load', 'button_pop_normal', 'troop_order_preset');
ns_button.buttons.preset_load.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.troop_order_preset;
    let troop_order = ns_dialog.dialogs.troop_order;

    troop_order.loadPreset(dialog.slot_number);
    ns_dialog.close('troop_order_preset');
}

ns_button.buttons.preset_page_prev = new nsButtonSet('preset_page_prev', 'button_package_page_prev', 'troop_order_preset');
ns_button.buttons.preset_page_prev.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.troop_order_preset;
    if (dialog.cont_obj.preset_slot_page_2.hasCss('hide')) {
        dialog.cont_obj.preset_slot_page_1.hide();
        dialog.cont_obj.preset_slot_page_2.show();
    } else {
        dialog.cont_obj.preset_slot_page_1.show();
        dialog.cont_obj.preset_slot_page_2.hide();
    }
}
ns_button.buttons.preset_page_next = new nsButtonSet('preset_page_next', 'button_package_page_next', 'troop_order_preset', {base_class: ns_button.buttons.preset_page_prev});