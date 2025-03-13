ns_dialog.dialogs.troop_view = new nsDialogSet('troop_view', 'dialog_full', 'size-full', { do_close_all: false });
ns_dialog.dialogs.troop_view.dst_posi_pk = null;
ns_dialog.dialogs.troop_view.type = null;

ns_dialog.dialogs.troop_view.cacheContents = function()
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);

    this.cont_obj.content_src_posi_section1 = new nsObject('.content_src_posi_section1', this.obj);
    this.cont_obj.content_dst_posi_section1 = new nsObject('.content_dst_posi_section1', this.obj);
    this.cont_obj.content_origin_posi_section1 = new nsObject('.content_origin_posi_section1', this.obj);
    this.cont_obj.content_triptime_section1 = new nsObject('.content_triptime_section1', this.obj);
    this.cont_obj.content_camptime_section1 = new nsObject('.content_camptime_section1', this.obj);
    this.cont_obj.content_cmd_type_section1 = new nsObject('.content_cmd_type_section1', this.obj);

    this.cont_obj.content_src_posi_section2 = new nsObject('.content_src_posi_section2', this.obj);
    this.cont_obj.content_dst_posi_section2 = new nsObject('.content_dst_posi_section2', this.obj);
    this.cont_obj.content_origin_posi_section2 = new nsObject('.content_origin_posi_section2', this.obj);
    this.cont_obj.content_triptime_section2 = new nsObject('.content_triptime_section2', this.obj);
    this.cont_obj.content_camptime_section2 = new nsObject('.content_camptime_section2', this.obj);
    this.cont_obj.content_cmd_type_section2 = new nsObject('.content_cmd_type_section2', this.obj);

    this.cont_obj.troop_table = new nsObject('.troop_table', this.obj);
    this.cont_obj.army_table = new nsObject('.army_table', this.obj);
    this.cont_obj.resource_table = new nsObject('.resource_table', this.obj);

    this.cont_obj.content_hero = new nsObject('.content_hero', this.obj);

    this.cont_obj.content_friendly_data1 = new nsObject('.content_friendly_data1', this.obj);
    this.cont_obj.content_friendly_data2 = new nsObject('.content_friendly_data2', this.obj);
    this.cont_obj.content_friendly_data3 = new nsObject('.content_friendly_data3', this.obj);
    this.cont_obj.status_tbody_wrap = new nsObject('.status_tbody_wrap', this.obj);
}

ns_dialog.dialogs.troop_view.draw = function()
{
    let data = this.data;
    this.cont_obj.content_title.text((data.type === 'valley') ? ns_i18n.t('valley_information') : ns_i18n.t('troop_information'));
    this.drawRemote();
}

ns_dialog.dialogs.troop_view.viewInit = function()
{
    let dialog = ns_dialog.dialogs.troop_view;

    dialog.cont_obj.content_src_posi_section1.hide();
    dialog.cont_obj.content_dst_posi_section1.hide();
    dialog.cont_obj.content_origin_posi_section1.hide();
    dialog.cont_obj.content_triptime_section1.hide();
    dialog.cont_obj.content_camptime_section1.hide();
    dialog.cont_obj.content_cmd_type_section1.hide();
    dialog.cont_obj.content_src_posi_section2.hide();
    dialog.cont_obj.content_dst_posi_section2.hide();
    dialog.cont_obj.content_origin_posi_section2.hide();
    dialog.cont_obj.content_triptime_section2.hide();
    dialog.cont_obj.content_camptime_section2.hide();
    dialog.cont_obj.content_cmd_type_section2.hide();

    dialog.cont_obj.content_friendly_data1.hide();
    dialog.cont_obj.content_friendly_data2.hide();
    dialog.cont_obj.content_friendly_data3.hide();

    ns_button.buttons.troop_view_recall.obj.hide();
    ns_button.buttons.troop_view_recall_ally.obj.hide();
    ns_button.buttons.troop_view_withdrawal.obj.hide();
    ns_button.buttons.troop_view_speedup.obj.hide();
    ns_button.buttons.troop_view_reinf.obj.hide();
    ns_button.buttons.troop_view_preva.obj.hide();
    ns_button.buttons.troop_view_give_up.obj.hide();

    dialog.cont_obj.content_hero.find('.captain_hero').empty();
    dialog.cont_obj.content_hero.find('.director_hero').empty();
    dialog.cont_obj.content_hero.find('.staff_hero').empty();
}

ns_dialog.dialogs.troop_view.drawRemote = function()
{
    let dialog = ns_dialog.dialogs.troop_view;
    let data = dialog.data;
    // 초기화
    dialog.viewInit();

    let post_data = {};
    post_data['troo_pk'] = (data.type === 'valley') ? data.valley_posi_pk : data.troo_pk;
    if (data.type === 'enemy') {
        ns_xhr.post('/api/troop/enemyView', post_data, dialog.enemyRemote);
    } else if (data.type === 'valley') {
        ns_xhr.post('/api/troop/valleyView', post_data, dialog.valleyRemote);
    } else {
        ns_xhr.post('/api/troop/view', post_data, dialog.viewRemote);
    }
}

ns_dialog.dialogs.troop_view.viewRemote = function(_data, _status)
{
    if (! ns_xhr.returnCheck(_data)) {
        ns_dialog.close('troop_view');
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.troop_view;

    dialog.cont_obj.content_friendly_data1.show();
    dialog.cont_obj.content_friendly_data2.show();
    dialog.cont_obj.content_friendly_data3.show();

    dialog.dst_posi_pk = _data.dst_posi_pk;

    // data = {"src_lord_pk":"28192","dst_lord_pk":"28192","src_posi_pk":"242x221","dst_posi_pk":"242x218","status":"C","cmd_type":"R","from_position":"&#xb3d9;&#xc778;&#xcc9c;&#xad6d; (242x221)","to_position":"&#xad11;&#xc0b0; Lv.10 (242x218)","distance":"3","triptime":"134","camptime":"0","hour_food":"250","captain_hero_pk":"10663254","director_hero_pk":null,"staff_hero_pk":null,"reso_gold":"10","reso_food":"5","reso_horse":"8","reso_lumber":"9","reso_iron":"0","army_worker":"0","army_infantry":"0","army_pikeman":"0","army_scout":"0","army_spearman":"0","army_armed_infantry":"0","army_archer":"0","army_horseman":"10","army_armed_horseman":"0","army_transporter":"0","army_bowman":"0","army_battering_ram":"0","army_catapult":"0","army_adv_catapult":"0","start_dt":"1369734208","move_time":"134","arrival_dt":"1369734342","withdrawal_dt":"1370339142","use_item_pk":null,"herodata":"","type":"M"};
    // data = {"src_lord_pk":"28195","dst_lord_pk":"28192","src_posi_pk":"235x223","dst_posi_pk":"242x221","status":"C","cmd_type":"R","from_position":"&#xc73c;&#xc545;&#xc73c;&#xc545; (235x223)","to_position":"&#xb3d9;&#xc778;&#xcc9c;&#xad6d; (242x221)","distance":"7.4","triptime":"105","camptime":"0","hour_food":"134118","captain_hero_pk":"19593886","director_hero_pk":null,"staff_hero_pk":null,"reso_gold":"0","reso_food":"0","reso_horse":"0","reso_lumber":"0","reso_iron":"0","army_worker":"0","army_infantry":"0","army_pikeman":"0","army_scout":"44706","army_spearman":"0","army_armed_infantry":"0","army_archer":"0","army_horseman":"0","army_armed_horseman":"0","army_transporter":"0","army_bowman":"0","army_battering_ram":"0","army_catapult":"0","army_adv_catapult":"0","start_dt":"1369791957","move_time":"105","arrival_dt":"1369792062","withdrawal_dt":"1370396862","use_item_pk":null,"herodata":{"captain":{"hero_pk":"19593886","m_hero_pk":"131326","level":"7","rare_type":"4","enchant":"10","leadership_basic":"78","leadership_enchant":"30","leadership_plusstat":"0","leadership_skill":"0","mil_force_basic":"67","mil_force_enchant":"0","mil_force_plusstat":"0","mil_force_skill":"0","intellect_basic":"29","intellect_enchant":"0","intellect_plusstat":"0","intellect_skill":"0","politics_basic":"24","politics_enchant":"0","politics_plusstat":"0","politics_skill":"0","charm_basic":"39","charm_enchant":"0","charm_plusstat":"0","charm_skill":"0","m_hero_skil_pk_1":null,"m_hero_skil_pk_2":null,"m_hero_skil_pk_3":null,"m_hero_skil_pk_4":null,"yn_trade":"N","leadership":108,"mil_force":67,"intellect":29,"politics":24,"charm":39}},"type":"T"};

    // let use_item = _data?.use_item_pk ? '<br />' + system_text.troop_use_item : '';
    let troop_status = code_set.troop_status[_data.status];
    if (_data.status === 'M') {
        troop_status += '<br />(' + code_set.troop_cmd_type[_data.cmd_type] + ')';
    }

    dialog.cont_obj.troop_table.find('.content_status').html(troop_status); //  + use_item
    dialog.cont_obj.troop_table.find('.content_src_posi').text(ns_text.convertPositionName(_data.from_position));
    dialog.cont_obj.troop_table.find('.content_dst_posi').text(ns_text.convertPositionName(_data.to_position));
    dialog.cont_obj.troop_table.find('.content_distance').text(_data.distance);
    dialog.cont_obj.troop_table.find('.content_triptime').text(ns_util.getCostsTime(_data.triptime));
    dialog.cont_obj.troop_table.find('.content_cmd_type').text(code_set.troop_cmd_type[_data.cmd_type]);

    if (! ns_util.math(_data.camptime).eq(0)) {
        dialog.cont_obj.troop_table.find('.content_camptime').text(ns_util.getCostsTime(_data.camptime));
    }

    dialog.cont_obj.status_tbody_wrap.empty();

    if (_data.status === 'C') {
        dialog.cont_obj.content_src_posi_section1.show();
        dialog.cont_obj.content_triptime_section1.show();
        dialog.cont_obj.content_src_posi_section2.show();
        dialog.cont_obj.content_triptime_section2.show();

        if (ns_util.math(_data.src_lord_pk).eq(ns_cs.d.lord.lord_pk.v)) {
            ns_button.buttons.troop_view_recall.obj.show();
            ns_button.buttons.troop_view_reinf.obj.show();

            dialog.type = _data.type;

            if (_data.type === 'P') {
                ns_button.buttons.troop_view_preva.obj.hide();
            } else {
                ns_button.buttons.troop_view_preva.obj.show();
            }

            let status_data = {};
            status_data.title1 = ns_i18n.t('army_post'); // 주둔지
            status_data.desc1 = ns_text.convertPositionName(_data.to_position);
            status_data.title2 = ns_i18n.t('consumption_food'); // 식량 소모
            status_data.desc2 = ns_util.numberFormat(_data.hour_food);
            status_data.title3 = ns_i18n.t('deployed_remain_time'); // 주둔 잔여 시간
            status_data.desc3 = ns_util.getCostsTime(ns_util.math(_data.withdrawal_dt).minus(ns_timer.now()).number);
            status_data.title4 = ns_i18n.t('withdrawal_expert_time'); // 철수 예정 시간
            status_data.desc4 = ns_timer.getDateTimeString(_data.withdrawal_dt, true, true, true);

            dialog.drawStatusTable(dialog.cont_obj.status_tbody_wrap, status_data);
        } else {
            ns_button.buttons.troop_view_recall_ally.obj.show();

            let status_data = {};
            status_data.title1 = ns_i18n.t('army_post'); // 주둔지
            status_data.desc1 = ns_text.convertPositionName(_data.to_position);
            status_data.title2 = ns_i18n.t('date_time_arrival'); // 도착 일시
            status_data.desc2 = ns_timer.getDateTimeString(_data.arrival_dt, true, true, true);
            status_data.title3 = ns_i18n.t('deployed_duration_time'); // 주둔 경과 시간
            status_data.desc3 = ns_util.getCostsTime(ns_util.math(ns_timer.now()).minus(_data.arrival_dt).number);
            status_data.title4 = ns_i18n.t('withdrawal_expert_time'); // 철수 예정 시간
            status_data.desc4 = ns_timer.getDateTimeString(_data.withdrawal_dt, true, true, true);

            dialog.drawStatusTable(dialog.cont_obj.status_tbody_wrap, status_data);
        }
    }  else if (_data.status === 'M') {
        dialog.cont_obj.content_dst_posi_section1.show();
        dialog.cont_obj.content_camptime_section1.show();
        dialog.cont_obj.content_dst_posi_section2.show();
        dialog.cont_obj.content_camptime_section2.show();

        if (ns_util.math(_data.src_lord_pk).eq(ns_cs.d.lord.lord_pk.v)) {
            ns_button.buttons.troop_view_withdrawal.obj.show();
        } else {
            ns_button.buttons.troop_view_withdrawal.obj.hide();
        }

        let status_data = {};
        status_data.title1 = ns_i18n.t('departure'); // 출발지
        status_data.desc1 = ns_text.convertPositionName(_data.from_position);
        status_data.title2 = ns_i18n.t('time_to_travel'); // 이동 소요 시간
        status_data.desc2 = ns_util.getCostsTime(_data.move_time);
        status_data.title3 = ns_i18n.t('move_remain_time'); // 이동 잔여 시간
        status_data.desc3 = ns_util.getCostsTime(ns_util.math(_data.arrival_dt).minus(ns_timer.now()).number);
        status_data.title4 = ns_i18n.t('estimated_arrival_time'); // 예상 도착 시간
        status_data.desc4 = ns_timer.getDateTimeString(_data.arrival_dt, true, true, true);

        dialog.drawStatusTable(dialog.cont_obj.status_tbody_wrap, status_data);
    }  else if (_data.status === 'R' || _data.status === 'W') {
        dialog.cont_obj.content_origin_posi_section1.show();
        dialog.cont_obj.content_cmd_type_section1.show();
        dialog.cont_obj.content_origin_posi_section2.show();
        dialog.cont_obj.content_cmd_type_section2.show();

        if (ns_util.math(_data.src_lord_pk).eq(ns_cs.d.lord.lord_pk.v)) {
            ns_button.buttons.troop_view_speedup.obj.show();
        }

        let status_data = {};
        status_data.title1 = ns_i18n.t('departure'); // 출발지
        status_data.desc1 = ns_text.convertPositionName(_data.from_position);
        status_data.title2 = ns_i18n.t('time_to_travel'); // 이동 소요 시간
        status_data.desc2 = ns_util.getCostsTime(_data.move_time);
        status_data.title3 = ns_i18n.t('move_remain_time'); // 이동 잔여 시간
        status_data.desc3 = ns_util.getCostsTime(ns_util.math(_data.arrival_dt).minus(ns_timer.now()).number);
        status_data.title4 = ns_i18n.t('estimated_arrival_time'); // 예상 도착 시간
        status_data.desc4 = ns_timer.getDateTimeString(_data.arrival_dt, true, true, true);

        dialog.drawStatusTable(dialog.cont_obj.status_tbody_wrap, status_data);
    }

    // 영웅
    dialog.cont_obj.content_hero.find('.captain_hero').empty().append(ns_hero.cardDraw(_data.captain_hero_pk, 'N', false, _data.herodata.captain, false, false, true));

    if (_data.director_hero_pk) {
        dialog.cont_obj.content_hero.find('.director_hero').empty().append(ns_hero.cardDraw(_data.director_hero_pk, 'N', false, _data.herodata.director, false, false, true));
    }

    if (_data.staff_hero_pk) {
        dialog.cont_obj.content_hero.find('.staff_hero').empty().append(ns_hero.cardDraw(_data.staff_hero_pk, 'N', false, _data.herodata.staff, false, false, true));
    }

    // 병력
    let total_army = 0;
    for (let _code of code_set.army_code) {
        dialog.cont_obj.army_table.find(`.army_${_code}`).text(ns_util.numberFormat(_data[`army_${_code}`]));
        total_army += ns_util.toInteger(_data[`army_${_code}`]);
    }

    dialog.cont_obj.resource_table.find('.total_army').text(ns_util.numberFormat(total_army)); // 총 병력

    // 자원
    for (let _type of ['gold', 'food', 'horse', 'lumber', 'iron']) {
        dialog.cont_obj.resource_table.find(`.resource_${_type}`).text(ns_util.numberFormat(_data[`reso_${_type}`]));
    }
}

ns_dialog.dialogs.troop_view.valleyRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        ns_dialog.close('troop_view');
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.troop_view;

    dialog.cont_obj.content_friendly_data1.show();
    dialog.cont_obj.content_friendly_data2.show();
    dialog.cont_obj.content_friendly_data3.show();

    dialog.dst_posi_pk = _data.dst_posi_pk;

    let troop_speed = 3000;
    let distance = ns_world.distanceValue(ns_engine.game_data.cpp, dialog.data.valley_posi_pk);
    let trip_time = ns_util.math(distance).div(troop_speed).mul(100000).integer;

    dialog.cont_obj.troop_table.find('.content_status').text(ns_i18n.t('occupation'));
    dialog.cont_obj.troop_table.find('.content_src_posi').text(ns_text.convertPositionName(_data.from_position));
    dialog.cont_obj.troop_table.find('.content_distance').text(distance);
    dialog.cont_obj.troop_table.find('.content_triptime').text(`${ns_util.getCostsTime(trip_time)} (${ns_i18n.t('troop_view_scout')})`); // (정찰병 기준)

    dialog.cont_obj.content_src_posi_section1.show();
    dialog.cont_obj.content_triptime_section1.show();
    dialog.cont_obj.content_src_posi_section2.show();
    dialog.cont_obj.content_triptime_section2.show();

    // 병력
    for (let _code of code_set.army_code) {
        dialog.cont_obj.army_table.find(`.army_${_code}`).text(0);
    }
    dialog.cont_obj.resource_table.find('.total_army').text(0); // 총 병력

    // 자원
    for (let _type of ['gold', 'food', 'horse', 'lumber', 'iron']) {
        dialog.cont_obj.resource_table.find(`.resource_${_type}`).text(0);
    }

    ns_button.buttons.troop_view_give_up.obj.show();
    ns_button.buttons.troop_view_reinf.obj.show();
}

ns_dialog.dialogs.troop_view.enemyRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        ns_dialog.close('troop_view');
        return;
    }
    _data = _data['ns_xhr_return']['add_data']

    // console.log(_data);

    let dialog = ns_dialog.dialogs.troop_view;

    // 영웅
    dialog.cont_obj.content_hero.find('.captain_hero').empty().append(ns_hero.cardDraw(null, 'N', false, {'m_hero_pk':_data.captain}, false, false, true));

    if (_data.director) {
        dialog.cont_obj.content_hero.find('.director_hero').empty().append(ns_hero.cardDraw(null, 'N', false, {'m_hero_pk':_data.director}, false, false, true));
    }

    if (_data.staff) {
        dialog.cont_obj.content_hero.find('.staff_hero').empty().append(ns_hero.cardDraw(null, 'N', false, {'m_hero_pk':_data.staff}, false, false, true));
    }

    // 병력
    for (let _code of code_set.army_code) {
        if (_data.army[`army_${_code}`]) {
            dialog.cont_obj.army_table.find(`.army_${_code}`).text(_data.army[`army_${_code}`]);
        } else {
            dialog.cont_obj.army_table.find(`.army_${_code}`).text('-');
        }
    }
}

ns_dialog.dialogs.troop_view.drawStatusTable = function(tbody, data)
{
    let tr = document.createElement('tr');

    let col1 = document.createElement('td');
    col1.classList.add('width-25');
    let col1_span = document.createElement('span');
    col1_span.innerHTML = data.title1;
    col1.appendChild(col1_span);

    let col2 = document.createElement('td');
    col2.classList.add('width-25');
    let col2_span = document.createElement('span');
    col2_span.innerHTML = data.desc1;
    col2.appendChild(col2_span);

    let col3 = document.createElement('td');
    col3.classList.add('width-25');
    let col3_span = document.createElement('span');
    col3_span.innerHTML = data.title2;
    col3.appendChild(col3_span);

    let col4 = document.createElement('td');
    let col4_span = document.createElement('span');
    col4_span.innerHTML = data.desc2;
    col4.appendChild(col4_span);

    tr.appendChild(col1);
    tr.appendChild(col2);
    tr.appendChild(col3);
    tr.appendChild(col4);

    tbody.append(tr);

    tr = document.createElement('tr');

    col1 = document.createElement('td');
    col1_span = document.createElement('span');
    col1_span.innerHTML = data.title3;
    col1.appendChild(col1_span);

    col2 = document.createElement('td');
    col2_span = document.createElement('span');
    col2_span.innerHTML = data.desc3;
    col2.appendChild(col2_span);

    col3 = document.createElement('td');
    col3_span = document.createElement('span');
    col3_span.innerHTML = data.title4;
    col3.appendChild(col3_span);

    col4 = document.createElement('td');
    col4_span = document.createElement('span');
    col4_span.innerHTML = data.desc4;
    col4.appendChild(col4_span);

    tr.appendChild(col1);
    tr.appendChild(col2);
    tr.appendChild(col3);
    tr.appendChild(col4);

    tbody.append(tr);
}

ns_dialog.dialogs.troop_view.recall = function(_e)
{
    let dialog = ns_dialog.dialogs.troop_view;

    let post_data = {};
    post_data['troo_pk'] = dialog.data.troo_pk;

    ns_xhr.post('/api/troop/recall', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.drawRemote();
        // ns_dialog.dialogs.world_detail.recall_valley();

        // 군사령부가 열려있다면 군사령부 주둔현황도 갱신
        if (ns_dialog.dialogs.build_Military.visible) {
            ns_dialog.dialogs.build_Military.drawTab();
        }

    }, { useProgress: true });
}

/* ************************************************** */

ns_button.buttons.troop_view_close = new nsButtonSet('troop_view_close', 'button_back', 'troop_view', { base_class: ns_button.buttons.common_close });
ns_button.buttons.troop_view_sub_close = new nsButtonSet('troop_view_sub_close', 'button_full', 'troop_view', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.troop_view_close_all = new nsButtonSet('troop_view_close_all', 'button_close_all', 'troop_view', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.troop_view_recall = new nsButtonSet('troop_view_recall', 'button_default', 'troop_view');
ns_button.buttons.troop_view_recall.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.troop_view;
    if (dialog.type === 'P') {
        ns_dialog.setDataOpen('confirm', { text: '요충지를 포기하시면 6시간 동안은<br /><br />해당 요충지로의 공격이 제한됩니다.', // TODO 요충지는 차후 개선
            okFunc: () =>
            {
                dialog.recall();
            }
        });
    } else {
        dialog.recall();
    }
}

ns_button.buttons.troop_view_give_up = new nsButtonSet('troop_view_give_up', 'button_default', 'troop_view');
ns_button.buttons.troop_view_give_up.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.troop_view;

    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_give_up_confirm'),
        okFunc: () =>
        {
            let post_data = {};
            post_data['target_posi_pk'] = dialog.data.valley_posi_pk;
            ns_xhr.post('/api/position/giveUp', post_data, function(_data, _status)
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];

                // dialog.hide();

                ns_dialog.setDataOpen('message', ns_i18n.t('msg_give_up_complete'));
                ns_dialog.dialogs.troop_view.drawRemote(); // 부대 정보 다시 그리기

                // 대륙갱신(view 중일때 즉시 갱신, 외성/내성 view 중일때 갱신 타이밍 조절 - 자동)
                ns_engine.cfg.world_tick = 1;
                ns_timer.worldReloadTick();
            }, { useProgress: true });
        }
    });
}

ns_button.buttons.troop_view_recall_ally = new nsButtonSet('troop_view_recall_ally', 'button_default', 'troop_view');
ns_button.buttons.troop_view_recall_ally.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.troop_view;

    let post_data = {};
    post_data['action'] = '';
    post_data['troo_pk'] = dialog.data.troo_pk;

    ns_xhr.post('/api/troop/recall', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        // dialog.draw_remote();
        ns_dialog.dialogs.troop_view.drawRemote(); // 부대 정보 다시 그리기
        if (ns_dialog.dialogs.build_Embassy.visible) {
            ns_dialog.dialogs.build_Embassy.drawList();
        }
    }, { useProgress: true });
}

ns_button.buttons.troop_view_withdrawal = new nsButtonSet('troop_view_withdrawal', 'button_default', 'troop_view');
ns_button.buttons.troop_view_withdrawal.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.troop_view;

    let post_data = {};
    post_data['troo_pk'] = dialog.data.troo_pk;

    ns_xhr.post('/api/troop/withdrawal', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        // dialog.draw_remote();
        ns_dialog.dialogs.troop_view.drawRemote(); // 부대 정보 다시 그리기
    }, { useProgress: true });
}

ns_button.buttons.troop_view_speedup = new nsButtonSet('troop_view_speedup', 'button_default', 'troop_view');
ns_button.buttons.troop_view_speedup.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.troop_view;
    ns_dialog.setDataOpen('item_quick_use', { type:'recall_speedup', queue_type: 'X', time_pk: ns_cs.getTimerPk('X', dialog.data.troo_pk) });
}

ns_button.buttons.troop_view_reinf = new nsButtonSet('troop_view_reinf', 'button_default', 'troop_view');
ns_button.buttons.troop_view_reinf.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.troop_view;
    ns_dialog.setDataOpen('troop_order', { coords:{ _posi_pk: dialog.dst_posi_pk }, cmd_type:'R' });
}

ns_button.buttons.troop_view_preva = new nsButtonSet('troop_view_preva', 'button_default', 'troop_view');
ns_button.buttons.troop_view_preva.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.troop_view;
    ns_dialog.setDataOpen('troop_order', { coords: { _posi_pk: dialog.dst_posi_pk }, cmd_type:'P' });
}