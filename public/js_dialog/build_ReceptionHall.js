// 다이얼로그
ns_dialog.dialogs.build_ReceptionHall = new nsDialogSet('build_ReceptionHall', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });
ns_dialog.dialogs.build_ReceptionHall.current_tab = null;
ns_dialog.dialogs.build_ReceptionHall.sorted = null;
ns_dialog.dialogs.build_ReceptionHall.prev_level = 0;
ns_dialog.dialogs.build_ReceptionHall.buttons = [];
ns_dialog.dialogs.build_ReceptionHall.list_refresh = false;

ns_dialog.dialogs.build_ReceptionHall.hero_enco_pk = null;
ns_dialog.dialogs.build_ReceptionHall.last_encounter_dt = null;
ns_dialog.dialogs.build_ReceptionHall.selected_item = null;
ns_dialog.dialogs.build_ReceptionHall.hero_sel_data = {};
ns_dialog.dialogs.build_ReceptionHall.select_type = null;
ns_dialog.dialogs.build_ReceptionHall.encounter_timer = false;
ns_dialog.dialogs.build_ReceptionHall.encounter_timer_end_dt_ut = null;
ns_dialog.dialogs.build_ReceptionHall.min_fee = 100;
ns_dialog.dialogs.build_ReceptionHall.hero_data = null;

ns_dialog.dialogs.build_ReceptionHall.cacheContents = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.cacheContents.call(this, true);
    }

    this.cont_obj.tab_hero_free = new nsObject('.tab_hero_free', this.obj);
    this.cont_obj.tab_hero_encounter = new nsObject('.tab_hero_encounter', this.obj);

    this.cont_obj.hero_bid_list_tbody = this.cont_obj.tab_hero_free.find('tbody');
    this.cont_obj.hero_free_count = this.cont_obj.tab_hero_free.find('.hero_free_count');
    this.cont_obj.content_bid_close_time = this.cont_obj.tab_hero_free.find('.content_bid_close_time');
    this.cont_obj.hero_free_bid_off_description = this.cont_obj.tab_hero_free.find('.hero_free_bid_off_description');
    this.cont_obj.hero_free_bid_off_description.hide(); // 기본적으로 숨김.

    this.cont_obj.content_concurr_body = new nsObject('.content_concurr_body', this.obj);
    this.cont_obj.content_concurr_idle = new nsObject('.content_concurr_idle', this.obj);
    this.cont_obj.content_concurr_title = new nsObject('.content_concurr_title', this.obj);
    this.cont_obj.content_concurr_time = new nsObject('.content_concurr_time', this.obj);

    this.cont_obj.result_encounter_notfound = new nsObject('.result_encounter_notfound', this.obj);
    this.cont_obj.encounter_description = this.cont_obj.result_encounter_notfound.find('.encounter_description');

    this.cont_obj.result_encounter_progress = new nsObject('.result_encounter_progress', this.obj);
    this.cont_obj.progress_hero = this.cont_obj.result_encounter_progress.find('.progress_hero');
    this.cont_obj.invitation_hero = this.cont_obj.result_encounter_progress.find('.invitation_hero');
    this.cont_obj.progress_description = this.cont_obj.result_encounter_progress.find('.progress_description');

    this.cont_obj.result_encounter_found = new nsObject('.result_encounter_found', this.obj);

    this.cont_obj.encounter_type_hero = this.cont_obj.result_encounter_found.find('.encounter_type_hero');
    this.cont_obj.encounter_type_hero_info = this.cont_obj.result_encounter_found.find('.encounter_type_hero_info');
    this.cont_obj.encounter_type_hero_invitation = this.cont_obj.result_encounter_found.find('.encounter_type_hero_invitation');
    this.cont_obj.encounter_type_invitation_success = this.cont_obj.result_encounter_found.find('.encounter_type_invitation_success');
    this.cont_obj.encounter_type_item_resource = this.cont_obj.result_encounter_found.find('.encounter_type_item_resource');

}

ns_dialog.dialogs.build_ReceptionHall.draw = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.draw.call(this, true);
    }
    ns_button.toggleGroupSingle(ns_button.buttons.build_ReceptionHall_tab_hero_free);

    this.list_refresh = true;
    this.cont_obj.tab_hero_free.hide();
    this.cont_obj.tab_hero_encounter.hide();

    // this.drawTab(); 처음 열때는 timer 에 의해 list를 호출 하므로
}

ns_dialog.dialogs.build_ReceptionHall.drawTab = function()
{
    let dialog = ns_dialog.dialogs.build_ReceptionHall;
    let tab = ns_button.toggleGroupValue('build_ReceptionHall_tab')[0].split('_tab_').pop();

    let d = ns_cs.d.bdic[dialog.data.castle_pk];
    dialog.current_tab = tab;

    if (d.level <= 0) {
        ns_button.toggleGroupSingle(ns_button.buttons.build_ReceptionHall_tab_hero_free);
        dialog.cont_obj.tab_hero_free.show();

        dialog.cont_obj.hero_bid_list_tbody.empty();

        let tr = document.createElement('tr');
        let col = document.createElement('td');
        col.colSpan = 8;
        col.classList.add('text_align_center');
        let span = document.createElement('span');
        span.innerHTML = ns_i18n.t('plz_wait_construction_reception_hall'); // 건설 중일 경우 재야 영웅을 확인하실 수 없습니다.
        col.appendChild(span);

        tr.appendChild(col);

        dialog.cont_obj.hero_bid_list_tbody.append(tr);
        return;
    }

    let post_data = {};
    if (tab === 'hero_free') {
        dialog.cont_obj.tab_hero_free.show();
        dialog.cont_obj.tab_hero_encounter.hide();
        ns_xhr.post('/api/heroFree/list', post_data, dialog.drawFreeList);
    } else {
        dialog.cont_obj.tab_hero_free.hide();
        dialog.cont_obj.tab_hero_encounter.show();
        ns_xhr.post('/api/heroEncounter/info', post_data, dialog.drawEncounter);
    }
}

ns_dialog.dialogs.build_ReceptionHall.drawFreeList = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.build_ReceptionHall;

    let sorted_data = Object.values(_data.list);

    sorted_data = sorted_data.sort(function(a,b){
        return b.level - a.level;
    });

    sorted_data = sorted_data.sort(function(a,b){
        return b.rare_type - a.rare_type;
    });

    sorted_data = sorted_data.sort(function(a,b){
        return ((b.bidding) ? 1 : 0) - ((a.bidding) ? 1 : 0);
    });

    // 목록 리스트
    dialog.buttonClear();
    dialog.cont_obj.hero_bid_list_tbody.empty();
    for (let d of sorted_data) {
        let columns = [];
        let m_hero = ns_cs.m.hero[d.m_hero_pk];
        let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];
        let tr = document.createElement('tr');
        if (d.bidding) {
            tr.classList.add('text_light_green');
            tr.classList.add('text_weight_bold');
        }

        let col = document.createElement('td');
        col.innerHTML = m_hero_base.name + ' Lv.' + m_hero.level;
        let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
        button.setAttribute('id', `ns_button_build_ReceptionHall_bid_${d.hero_free_pk}`);
        col.append(button);
        columns.push(col);

        for (let _type of ['rare_type', 'leadership', 'mil_force', 'intellect', 'politics', 'charm', 'bid_cnt']) {
            col = document.createElement('td');
            col.innerHTML = d[_type];
            columns.push(col);
        }

        for (let _col of columns) {
            tr.appendChild(_col);
        }
        dialog.cont_obj.hero_bid_list_tbody.append(tr);

        let button_id = `build_ReceptionHall_bid_${d.hero_free_pk}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'build_ReceptionHall');
        ns_button.buttons[button_id].mouseUp = function ()
        {
            if (ns_util.math(_data.my_bid_cnt).gte(_data.total_bid_cnt) && ! d.bidding) {
                ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_limit_bid_count') }) // 입찰 가능 횟수를 초과하였습니다.
            } else {
                ns_dialog.setDataOpen('build_ReceptionHall_bid', d.hero_free_pk);
            }
        }
        dialog.buttons.push(ns_button.buttons[button_id]);
    }
    dialog.cont_obj.hero_free_count.text(`${_data.my_bid_cnt}/${_data.total_bid_cnt}`)

    // 갱신 플래그 리셋
    dialog.list_refresh = false;
}

ns_dialog.dialogs.build_ReceptionHall.drawEncounter = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.build_ReceptionHall;

    // 버튼 초기화
    ns_button.buttons.build_ReceptionHall_encounter_do.obj.hide();
    ns_button.buttons.build_ReceptionHall_encounter_cancel.obj.hide();
    ns_button.buttons.build_ReceptionHall_encounter_invitation.obj.hide();
    ns_button.buttons.build_ReceptionHall_encounter_invitation_cancel.obj.hide();
    ns_button.buttons.build_ReceptionHall_encounter_re.obj.hide();
    ns_button.buttons.build_ReceptionHall_encounter_get.obj.hide();

    dialog.cont_obj.result_encounter_notfound.hide();
    dialog.cont_obj.result_encounter_progress.hide();
    dialog.cont_obj.result_encounter_found.hide();

    dialog.cont_obj.encounter_type_hero.hide();
    dialog.cont_obj.encounter_type_hero_info.hide();
    dialog.cont_obj.encounter_type_hero_invitation.hide();
    dialog.cont_obj.encounter_type_invitation_success.hide();
    dialog.cont_obj.encounter_type_item_resource.hide();


    if (ns_util.math(_data.count).lte(0)) {
        dialog.encounter_timer = false;

        dialog.cont_obj.result_encounter_notfound.show();

        dialog.cont_obj.content_concurr_body.hide();
        dialog.cont_obj.content_concurr_idle.text(ns_i18n.t('not_in_exploration')).show(); // 탐색 중이 아닙니다.

        ns_select_box.set('build_ReceptionHall_hero_encounter_filter', 'distance');
        ns_button.buttons.build_ReceptionHall_hero_encounter_filter.obj.text(ns_select_box.getText('build_ReceptionHall_hero_encounter_filter'));

        dialog.drawSelectFilter();

        dialog.selected_item = null;
        ns_button.buttons.hero_encounter_select_item.obj.html(ns_i18n.t('choose')); // 선택
        ns_button.buttons.build_ReceptionHall_encounter_do.obj.show();
    } else if (_data.data) {
        let cmd_hero_name;

        dialog.selected_item = null;
        ns_button.buttons.hero_invitation_select_item.obj.html(ns_i18n.t('choose')); // 선택

        dialog.cont_obj.content_concurr_body.hide();
        dialog.cont_obj.content_concurr_idle.text(ns_i18n.t('not_in_exploration')).show(); // 탐색 중이 아닙니다.

        if (_data.data.status === 'P') {
            dialog.cont_obj.result_encounter_progress.show();

            if (_data.data.cmd_hero_pk) {
                let d = ns_cs.d.hero[_data.data.cmd_hero_pk];
                let m_hero = ns_cs.m.hero[d.m_hero_pk];
                let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];

                dialog.cont_obj.progress_hero.empty().show().append(ns_hero.cardDraw(_data.data.cmd_hero_pk, 'N', false, d, false, false, true));
                dialog.cont_obj.invitation_hero.empty().hide();

                dialog.cont_obj.content_concurr_title.text(code_set.encount_type[_data.data.type] + ' - ' + m_hero_base.name + ' Lv.' + d.level);
            }

            dialog.encounter_timer = true;
            dialog.encounter_timer_end_dt_ut = ns_util.toInteger(_data.data.end_dt_ut);

            dialog.cont_obj.progress_description.text(ns_i18n.t('reception_hall_encounter_description'));

            dialog.cont_obj.content_concurr_body.show();
            dialog.cont_obj.content_concurr_idle.hide();

            ns_button.buttons.build_ReceptionHall_encounter_cancel.obj.show();
        } else {
            dialog.encounter_timer = false;

            if (_data.data.encounter_type === 'none') {
                // 탐색 실패
                dialog.cont_obj.result_encounter_notfound.show();

                dialog.cont_obj.content_concurr_body.hide();
                dialog.cont_obj.content_concurr_idle.text(ns_i18n.t('exploration_failed')).show(); // 탐색에 실패했습니다.

                ns_select_box.set('build_ReceptionHall_hero_encounter_filter', 'distance');
                ns_button.buttons.build_ReceptionHall_hero_encounter_filter.obj.text(ns_select_box.getText('build_ReceptionHall_hero_encounter_filter'));

                dialog.drawSelectFilter();

                dialog.selected_item = null;
                ns_button.buttons.hero_encounter_select_item.obj.html(ns_i18n.t('choose')); // 선택

                ns_button.buttons.build_ReceptionHall_encounter_do.obj.show();
            } else if (_data.data.encounter_type === 'hero') {
                dialog.hero_data = _data.hero;

                // 최저 초빙 비용 계산
                dialog.min_fee = ns_util.math(100).mul(_data.hero.level).mul(_data.hero.rare_type).integer;
                ns_button.buttons.send_gold_value.obj.text(dialog.min_fee);

                if (ns_util.math(_data.data.invitation_cnt).gt(0) && _data.invitation) {
                    dialog.cont_obj.result_encounter_progress.show();

                    if (_data.data?.cmd_hero_pk) {
                        let d = ns_cs.d.hero[_data.invitation.cmd_hero_pk];
                        let m_hero = ns_cs.m.hero[d.m_hero_pk];
                        let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];

                        dialog.cont_obj.content_concurr_title.text(ns_i18n.t('in_invitation') + ' - ' + m_hero_base.name + ' Lv.' + d.level);
                    }

                    dialog.cont_obj.progress_hero.empty().hide();
                    dialog.cont_obj.invitation_hero.empty().show().append(ns_hero.cardDetailDraw(_data.hero.hero_pk, true, false, _data.hero, false, false, true));

                    dialog.encounter_timer = true;
                    dialog.encounter_timer_end_dt_ut = _data.invitation.end_dt_ut;

                    dialog.cont_obj.progress_description.html(ns_i18n.t('reception_hall_invite_description'));

                    dialog.cont_obj.content_concurr_body.show();
                    dialog.cont_obj.content_concurr_idle.hide();

                    ns_button.buttons.build_ReceptionHall_encounter_invitation_cancel.obj.show();
                } else {
                    dialog.hero_enco_pk = _data.data.hero_enco_pk;

                    dialog.cont_obj.encounter_type_hero.find('.invitation').empty().append(ns_hero.cardDetailDraw(_data.hero.hero_pk, true, false, _data.hero, false, false, true));

                    // 초빙용 자료 캐싱
                    dialog.find_hero_level = _data.hero.level;
                    dialog.find_hero_rare_type = _data.hero.rare_type;

                    // 초빙한 적이 있으면 실패로그 추가
                    if (_data.data.invitation_cnt > 0 && _data.data.yn_invited === 'N') {
                        dialog.cont_obj.encounter_type_hero.show();
                        dialog.cont_obj.encounter_type_hero_info.show();
                        dialog.cont_obj.encounter_type_hero_invitation.show();

                        // 실패 마크 그려주기
                        let div = document.createElement('div');
                        div.setAttribute('class', 'invitation_failed');
                        dialog.cont_obj.encounter_type_hero.find('.content_card').append(div);

                        dialog.cont_obj.encounter_type_hero.find('.invitation_description').text(ns_i18n.t('reception_hall_invite_fail_description', [_data.data.invitation_cnt]));

                        ns_button.buttons.build_ReceptionHall_encounter_invitation.obj.show();
                        ns_button.buttons.build_ReceptionHall_encounter_re.obj.show();
                    } else if (_data.data.yn_invited === 'Y'){
                        dialog.cont_obj.encounter_type_invitation_success.show();

                        dialog.cont_obj.encounter_type_invitation_success.find('.invitation_success_hero').empty().append(ns_hero.cardDetailDraw(_data.hero.hero_pk, true, false, _data.hero, false, false, true));

                        // 성공 마크 그려주기
                        let div = document.createElement('div');
                        div.setAttribute('class', 'invitation_success');
                        dialog.cont_obj.encounter_type_hero.find('.content_card').append(div);

                        dialog.cont_obj.encounter_type_invitation_success.find('.invitation_success_description').text(ns_i18n.t('reception_hall_invite_success_description'));

                        ns_button.buttons.build_ReceptionHall_encounter_get.obj.show();
                    } else {
                        dialog.cont_obj.encounter_type_hero.show();
                        dialog.cont_obj.encounter_type_hero_info.show();
                        dialog.cont_obj.encounter_type_hero_invitation.show();

                        dialog.cont_obj.encounter_type_hero.find('.invitation_description').text(ns_i18n.t('reception_hall_find_hero_description'));

                        ns_button.buttons.build_ReceptionHall_encounter_invitation.obj.show();
                        ns_button.buttons.build_ReceptionHall_encounter_re.obj.show();
                    }

                    dialog.cont_obj.result_encounter_found.show();
                }
            } else if (_data.data.encounter_type === 'item') {
                dialog.cont_obj.result_encounter_found.show();

                dialog.cont_obj.encounter_type_item_resource.show();

                let image = document.createElement('div');
                image.classList.add('item_image');
                image.classList.add(`item_image_${_data.data.encounter_value}`);
                dialog.cont_obj.encounter_type_item_resource.find('.item_resource_image').empty().append(image);
                dialog.cont_obj.encounter_type_item_resource.find('.item_resource_title').text(`${ns_i18n.t('item')} : ` + ns_cs.m.item[_data.data.encounter_value].title);
                dialog.cont_obj.encounter_type_item_resource.find('.item_resource_description').text(ns_i18n.t('reception_hall_item_secure_description'));

                ns_button.buttons.build_ReceptionHall_encounter_get.obj.show();
            } else {
                dialog.cont_obj.result_encounter_found.show();

                dialog.cont_obj.encounter_type_item_resource.show();

                let image = document.createElement('div');
                image.classList.add('item_image');
                image.classList.add(`resource_image_${_data.data.encounter_type}`);
                dialog.cont_obj.encounter_type_item_resource.find('.item_resource_image').empty().append(image);
                dialog.cont_obj.encounter_type_item_resource.find('.item_resource_title').text(ns_i18n.t(`resource_${_data.data.encounter_type}`) + ' ' + _data.data.encounter_value);
                dialog.cont_obj.encounter_type_item_resource.find('.item_resource_description').text(ns_i18n.t('reception_hall_resource_secure_description'));

                ns_button.buttons.build_ReceptionHall_encounter_get.obj.show();
            }
        }
    }
}


ns_dialog.dialogs.build_ReceptionHall.drawSelectFilter = function()
{
    let dialog = ns_dialog.dialogs.build_ReceptionHall;
    let select_box = ns_select_box.get('build_ReceptionHall_hero_encounter_filter');

    dialog.select_type = select_box.val;
    dialog.cont_obj.encounter_description.text(ns_i18n.t(`reception_${select_box.val}_description`));
}

ns_dialog.dialogs.build_ReceptionHall.heroEncounterCallback = function(_sel_hero_pk, _e)
{
    if (!_sel_hero_pk) {
        return;
    }

    let dialog = ns_dialog.dialogs.build_ReceptionHall;

    let post_data = { };
    post_data['action'] = 'do';
    post_data['hero_pk'] = _sel_hero_pk;
    post_data['in_cast_pk'] = dialog.data.castle_pk;
    post_data['encounter_type'] = dialog.select_type;
    post_data['m_item_pk'] = dialog.selected_item;

    ns_xhr.post('/api/heroEncounter/do', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        // 영빈관 탐색영역 갱신
        dialog.drawTab();
        ns_hero.deckReload();

        // counsel
        ns_dialog.setDataOpen('counsel', { type: 'action', counsel_type: 'encou', hero_pk: _sel_hero_pk, cost: _data.build_time });
    }, { useProgress: true });
}

ns_dialog.dialogs.build_ReceptionHall.invitationCallback = function(_sel_hero_pk, _e)
{
    if (!_sel_hero_pk) {
        return;
    }

    let dialog = ns_dialog.dialogs.build_ReceptionHall;
    let send_gold = 0;

    send_gold = ns_util.toInteger(ns_button.buttons.send_gold_value.obj.text());

    if (ns_util.math(send_gold).lt(dialog.min_fee)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_hero_sel_cmd_invitation_min_fee', [ns_util.numberFormat(dialog.min_fee)])); // 초빙비용이 최소 초빙비용 $1보다 작을 수는 없습니다.
        return;
    }

    let post_data = { };
    post_data['action'] = 'invitation';
    post_data['hero_enco_pk'] = dialog.hero_enco_pk;
    post_data['hero_pk'] = _sel_hero_pk;
    post_data['send_gold'] = send_gold;
    post_data['send_item'] = dialog.selected_item;
    post_data['in_cast_pk'] = dialog.data.castle_pk;

    ns_xhr.post('/api/heroEncounter/invitation', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        // 영빈관 탐색영역 갱신
        dialog.drawTab();
        ns_hero.deckReload();

        // counsel
        ns_dialog.setDataOpen('counsel', { type: 'action', counsel_type: 'invit', hero_pk: _sel_hero_pk, cost: dialog.hero_data.m_hero_pk });
    }, { useProgress: true });
}

ns_dialog.dialogs.build_ReceptionHall.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';
    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_ReceptionHall.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_ReceptionHall.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_ReceptionHall;
    let d = ns_cs.d['bdic'][dialog.data.castle_pk];
    let tab = ns_button.toggleGroupValue('build_ReceptionHall_tab')[0].split('_tab_').pop();

    // 레벨 검사
    if(dialog.prev_level !== d.level) {
        dialog.prev_level = d.level;
        dialog.list_refresh = true;
    }

    if (tab === 'hero_free') {
        // 낙찰자 추첨 관련 inactive 처리
        let dt = new Date(ns_timer.now() * 1000);
        let z = dt.getMinutes();

        if (dialog.list_refresh === true) {
            dialog.drawTab();
            dialog.list_refresh = false;
        }

        if (dialog.bidoff_tick == null) {
            dialog.bidoff_tick = z;
        } else {
            if (z >= 0 && z < 5) {
                dialog.cont_obj.hero_free_bid_off_description.show();
                if (dialog.list_refresh === true) {
                    dialog.curr_encounter_stage = 0; // 이건 뭐야;
                }
                dialog.cont_obj.content_bid_close_time.text(ns_i18n.t('reception_hall_time_end_description')); // 입찰 마감시각 : 종료
            } else {
                if (z === 5 && dialog.bidoff_tick === 4) {
                    dialog.list_refresh = true;
                }
                dialog.cont_obj.hero_free_bid_off_description.hide();
                dialog.cont_obj.content_bid_close_time.text(ns_i18n.t('reception_hall_time_description', [(dt.getHours() === 24 ? '1' : (dt.getHours() + 1))])); // 입찰 마감시각 : 종료
            }
            dialog.bidoff_tick = z;
        }
    } else {
        // 탐색 남은시간 계산
        if (dialog?.encounter_timer_end_dt_ut) {
            let left = dialog.encounter_timer_end_dt_ut - ns_timer.now();

            if (left <= 0) {
                dialog.cont_obj.content_concurr_time.text(ns_i18n.t('in_progress'));
            } else {
                dialog.cont_obj.content_concurr_time.text(ns_util.getCostsTime(left));
            }
        }
    }
}

ns_button.buttons.build_ReceptionHall_close = new nsButtonSet('build_ReceptionHall_close', 'button_back', 'build_ReceptionHall', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_ReceptionHall_sub_close = new nsButtonSet('build_ReceptionHall_sub_close', 'button_full', 'build_ReceptionHall', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.build_ReceptionHall_close_all = new nsButtonSet('build_ReceptionHall_close_all', 'button_close_all', 'build_ReceptionHall', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_ReceptionHall = new nsButtonSet('build_desc_ReceptionHall', 'button_text_style_desc', 'build_ReceptionHall', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_move_ReceptionHall = new nsButtonSet('build_move_ReceptionHall', 'button_middle_2', 'build_ReceptionHall', { base_class: ns_button.buttons.build_move });
ns_button.buttons.build_cons_ReceptionHall = new nsButtonSet('build_cons_ReceptionHall', 'button_multi', 'build_ReceptionHall', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_ReceptionHall = new nsButtonSet('build_upgrade_ReceptionHall', 'button_hero_action', 'build_ReceptionHall', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_assign_ReceptionHall = new nsButtonSet('build_assign_ReceptionHall', 'button_full', 'build_ReceptionHall', { base_class: ns_button.buttons.build_assign });
ns_button.buttons.build_no_assign_ReceptionHall = new nsButtonSet('build_no_assign_ReceptionHall', 'button_full', 'build_ReceptionHall', { base_class: ns_button.buttons.build_assign });

ns_button.buttons.build_prev_ReceptionHall = new nsButtonSet('build_prev_ReceptionHall', 'button_multi_prev', 'build_ReceptionHall', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_ReceptionHall = new nsButtonSet('build_next_ReceptionHall', 'button_multi_next', 'build_ReceptionHall', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_ReceptionHall = new nsButtonSet('build_speedup_ReceptionHall', 'button_encourage', 'build_ReceptionHall', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_ReceptionHall = new nsButtonSet('build_cancel_ReceptionHall', 'button_build', 'build_ReceptionHall', { base_class: ns_button.buttons.build_cancel });

ns_button.buttons.build_ReceptionHall_tab_hero_free = new nsButtonSet('build_ReceptionHall_tab_hero_free', 'button_tab', 'build_ReceptionHall', { toggle_group: 'build_ReceptionHall_tab' });
ns_button.buttons.build_ReceptionHall_tab_hero_free.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_ReceptionHall;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.drawTab();
}
ns_button.buttons.build_ReceptionHall_tab_hero_encounter = new nsButtonSet('build_ReceptionHall_tab_hero_encounter', 'button_tab', 'build_ReceptionHall',  { base_class: ns_button.buttons.build_ReceptionHall_tab_hero_free, toggle_group: 'build_ReceptionHall_tab' });

ns_button.buttons.build_ReceptionHall_hero_encounter_filter = new nsButtonSet('build_ReceptionHall_hero_encounter_filter', 'button_select_box', 'build_ReceptionHall');
ns_button.buttons.build_ReceptionHall_hero_encounter_filter.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('select_box', {select_box_id: 'build_ReceptionHall_hero_encounter_filter'});
}

ns_button.buttons.hero_encounter_select_item = new nsButtonSet('hero_encounter_select_item', 'button_select_box', 'build_ReceptionHall');
ns_button.buttons.hero_encounter_select_item.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_ReceptionHall;

    ns_dialog.setDataOpen('item_quick_use', { type:'encounter',
        callbackFunc: (_m_item_pk) =>
        {
            dialog.selected_item = _m_item_pk;
            ns_button.buttons.hero_encounter_select_item.obj.text(ns_cs.m.item[_m_item_pk].title);
        }
    });
}

ns_button.buttons.build_ReceptionHall_encounter_do = new nsButtonSet('build_ReceptionHall_encounter_do', 'button_special', 'build_ReceptionHall');
ns_button.buttons.build_ReceptionHall_encounter_do.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_ReceptionHall;
    let action = 'encou';

    dialog.hero_sel_data.type = action;
    dialog.hero_sel_data.sort_stat_type = code_set.hero_stat_type[ns_cs.m.cmd[action].sort_hero_stat_type];
    dialog.hero_sel_data.selector_use = true;
    dialog.hero_sel_data.do_callback = ns_dialog.dialogs.build_ReceptionHall.heroEncounterCallback;

    // 자동 선택시 필요한 값임 (아래 두개)
    dialog.hero_sel_data.limit_stat_type = dialog.hero_sel_data.sort_stat_type;
    dialog.hero_sel_data.limit_stat_value = 1;

    ns_dialog.setDataOpen('hero_select', dialog.hero_sel_data);
}

ns_button.buttons.build_ReceptionHall_encounter_cancel = new nsButtonSet('build_ReceptionHall_encounter_cancel', 'button_cancel', 'build_ReceptionHall');
ns_button.buttons.build_ReceptionHall_encounter_cancel.mouseUp = function(_e)
{
    try {
        let dialog = ns_dialog.dialogs.build_ReceptionHall;

        if (ns_util.math(dialog.encounter_timer_end_dt_ut).minus(ns_timer.now()).lt(5)) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_limit_cancel_time_warning'));
            return;
        }

        ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_cancel_confirm'), // 취소하시겠습니까?
            okFunc: () =>
            {

                if (ns_util.math(dialog.encounter_timer_end_dt_ut).minus(ns_timer.now()).lt(5)) {
                    ns_dialog.setDataOpen('message', ns_i18n.t('msg_limit_cancel_time_warning'));
                    return;
                }

                ns_xhr.post('/api/heroEncounter/cancel', {}, function(_data, _status)
                {
                    if(! ns_xhr.returnCheck(_data)) {
                        return;
                    }
                    _data = _data['ns_xhr_return']['add_data'];

                    dialog.drawTab();
                    ns_hero.deckReload();
                });
            }
        }, { useProgress: true });
    } catch (e) {
        console.error(e);
    }
}

ns_button.buttons.build_ReceptionHall_encounter_invitation = new nsButtonSet('build_ReceptionHall_encounter_invitation', 'button_special', 'build_ReceptionHall');
ns_button.buttons.build_ReceptionHall_encounter_invitation.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_ReceptionHall;
    let action = 'invit';

    dialog.hero_sel_data.type = action;
    dialog.hero_sel_data.sort_stat_type = code_set.hero_stat_type[ns_cs.m.cmd[action].sort_hero_stat_type];
    dialog.hero_sel_data.selector_use = true;
    dialog.hero_sel_data.do_callback = ns_dialog.dialogs.build_ReceptionHall.invitationCallback;

    // 자동 선택시 필요한 값임 (아래 두개)
    dialog.hero_sel_data.limit_stat_type = dialog.hero_sel_data.sort_stat_type;
    dialog.hero_sel_data.limit_stat_value = 1;

    ns_dialog.setDataOpen('hero_select', dialog.hero_sel_data);
}

ns_button.buttons.build_ReceptionHall_encounter_invitation_cancel = new nsButtonSet('build_ReceptionHall_encounter_invitation_cancel', 'button_default', 'build_ReceptionHall');
ns_button.buttons.build_ReceptionHall_encounter_invitation_cancel.mouseUp = function(_e)
{
    try {
        let dialog = ns_dialog.dialogs.build_ReceptionHall;

        if (ns_util.math(dialog.encounter_timer_end_dt_ut).minus(ns_timer.now()).lt(5)) {

            ns_dialog.setDataOpen('message', ns_i18n.t('msg_limit_cancel_time_warning'));
            return;
        }

        ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_cancel_confirm'), // 취소하시겠습니까?
            okFunc: () => {
                // 5초 이하는 취소 불가
                if (ns_util.math(dialog.encounter_timer_end_dt_ut).minus(ns_timer.now()).lt(5)) {
                    ns_dialog.setDataOpen('message', ns_i18n.t('msg_limit_cancel_time_warning'));
                    return;
                }

                let post_data = {};
                post_data['hero_enco_pk'] = dialog.hero_enco_pk;

                ns_xhr.post('/api/heroEncounter/invitationCancel', post_data, function(_data, _status)
                {
                    if(! ns_xhr.returnCheck(_data)) {
                        return;
                    }
                    _data = _data['ns_xhr_return']['add_data'];

                    dialog.drawTab();
                    ns_hero.deckReload();
                }, { useProgress: true });
            }
        });
    } catch (e) {
        console.error(e);
    }
}

ns_button.buttons.build_ReceptionHall_encounter_re = new nsButtonSet('build_ReceptionHall_encounter_re', 'button_special', 'build_ReceptionHall');
ns_button.buttons.build_ReceptionHall_encounter_re.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_ReceptionHall;
    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_reception_re_encounter_confirm'), // 재탐색 명령을 내리시면 지난 탐색 결과로<br />나타났던 영웅은 자동으로 사라지게 됩니다.<br /><br /><br /><strong>명령을 취소 하시겠습니까?</strong>
        okFunc: function()
        {
            // 명령 창 띄우기
            dialog.encounter_timer = false;

            dialog.cont_obj.result_encounter_found.hide();
            dialog.cont_obj.result_encounter_notfound.show();

            dialog.cont_obj.content_concurr_body.hide();
            dialog.cont_obj.content_concurr_idle.show();

            ns_select_box.set('build_ReceptionHall_hero_encounter_filter', 'distance');
            ns_button.buttons.build_ReceptionHall_hero_encounter_filter.obj.text(ns_select_box.getText('build_ReceptionHall_hero_encounter_filter'));

            dialog.drawSelectFilter();

            dialog.selected_item = null;
            ns_button.buttons.hero_encounter_select_item.obj.html(ns_i18n.t('choose')); // 선택

            ns_button.buttons.build_ReceptionHall_encounter_do.obj.show();
        }
    });
}

ns_button.buttons.build_ReceptionHall_encounter_get = new nsButtonSet('build_ReceptionHall_encounter_get', 'button_special', 'build_ReceptionHall');
ns_button.buttons.build_ReceptionHall_encounter_get.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_ReceptionHall;

    ns_xhr.post('/api/heroEncounter/get', {}, function(_data, _status) {
        if (!ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_dialog.setDataOpen('message', ns_i18n.t('msg_reception_encounter_get')); // <strong>탐색 결과물을 수납 하였습니다.</strong>
        dialog.drawTab();
    });
}

ns_button.buttons.hero_invitation_select_item = new nsButtonSet('hero_invitation_select_item', 'button_select_box', 'build_ReceptionHall');
ns_button.buttons.hero_invitation_select_item.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_ReceptionHall;

    ns_dialog.setDataOpen('item_quick_use', { type:'invitation',
        callbackFunc: (_m_item_pk) =>
        {
            dialog.selected_item = _m_item_pk;
            ns_button.buttons.hero_invitation_select_item.obj.html(ns_cs.m.item[_m_item_pk].title);
        }
    });
}

ns_button.buttons.send_gold_value = new nsButtonSet('send_gold_value', 'button_input', 'build_ReceptionHall');
ns_button.buttons.send_gold_value.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('keypad', {max: ns_cs.d.terr.gold_curr.v, min: 0,
        callback: function(data){
            ns_button.buttons.send_gold_value.obj.text(data);
        }
    });
}

ns_button.buttons.hero_invitation_gold_up = new nsButtonSet('hero_invitation_gold_up', 'button_arrow_up', 'build_ReceptionHall');
ns_button.buttons.hero_invitation_gold_up.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_ReceptionHall;
    let prev_gold = ns_util.toInteger(ns_button.buttons.send_gold_value.obj.text());
    let inv_gold = ns_util.math(prev_gold).div(dialog.min_fee).plus(1).mul(dialog.min_fee).integer;
    inv_gold = (ns_cs.d.terr.gold_curr.v < inv_gold) ? ns_cs.d.terr.gold_curr.v : inv_gold;
    ns_button.buttons.send_gold_value.obj.text(inv_gold);
}

ns_button.buttons.hero_invitation_gold_down = new nsButtonSet('hero_invitation_gold_down', 'button_arrow_down', 'build_ReceptionHall');
ns_button.buttons.hero_invitation_gold_down.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_ReceptionHall;
    ns_button.buttons.send_gold_value.obj.text(dialog.min_fee);
}

// *************************************** 입찰 ***************************************
ns_dialog.dialogs.build_ReceptionHall_bid = new nsDialogSet('build_ReceptionHall_bid', 'dialog_building', 'size-large', { do_close_all: false });
ns_dialog.dialogs.build_ReceptionHall_bid.hero_free_bid_gold_unit = [0, 1000, 8000, 27000, 64000, 125000];
ns_dialog.dialogs.build_ReceptionHall_bid.bidding_best = 0;
ns_dialog.dialogs.build_ReceptionHall_bid.bidding_gold = 0;
ns_dialog.dialogs.build_ReceptionHall_bid.free_bid_gold_unit = 0;
ns_dialog.dialogs.build_ReceptionHall_bid.bid_min_gold = 0;
ns_dialog.dialogs.build_ReceptionHall_bid.hero_free_pk = null;

ns_dialog.dialogs.build_ReceptionHall_bid.cacheContents = function()
{
    this.cont_obj.content_card_wrap = new nsObject('.content_card_wrap', this.obj);

    this.cont_obj.bidding_gold = new nsObject('.bidding_gold', this.obj);

    this.cont_obj.gold_amount = new nsObject('.gold_amount', this.obj);
    this.cont_obj.bidding_best = new nsObject('.bidding_best', this.obj);
    this.cont_obj.bidding_gold = new nsObject('.bidding_gold', this.obj);
    this.cont_obj.bidding_list_wrap = new nsObject('.bidding_list_wrap', this.obj);
}

ns_dialog.dialogs.build_ReceptionHall_bid.draw = function()
{
    let post_data = {};
    post_data['action'] = 'bid';
    post_data['hero_free_pk'] = this.data;
    ns_xhr.post('/api/heroFree/bid', post_data, this.drawRemote, { useProgress: true });
}

ns_dialog.dialogs.build_ReceptionHall_bid.drawRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.build_ReceptionHall_bid;
    dialog.hero_free_pk = dialog.data;

    dialog.cont_obj.gold_amount.text(ns_util.numberFormat(ns_cs.d.terr.gold_curr.v));
    dialog.bidding_best = ns_util.toInteger(_data.bid_best);
    dialog.bidding_gold = ns_util.toInteger(_data.bidding_gold);
    let bidding_best = dialog.bidding_best;
    dialog.free_bid_gold_unit = ns_util.math(_data.hero_info.level).mul(dialog.hero_free_bid_gold_unit[_data.hero_info.rare_type]).integer;

    if (ns_util.math(bidding_best).lt(1)) {
        dialog.bid_min_gold = ns_util.math(_data.hero_info.level).mul(1000).mul(ns_util.math(_data.hero_info.rare_type).pow(3).integer).integer;
        ns_button.buttons.hero_free_bid_value.obj.text(dialog.bid_min_gold);
        dialog.cont_obj.bidding_best.text('-');
    } else {
        dialog.bid_min_gold = ns_util.math(bidding_best).plus(dialog.free_bid_gold_unit).integer;
        ns_button.buttons.hero_free_bid_value.obj.text(dialog.bid_min_gold);
        dialog.cont_obj.bidding_best.text(ns_util.numberFormat(dialog.bidding_best) + ' (' + (_data.bid_best_dt) + ')');
    }

    dialog.cont_obj.bidding_gold.text(! _data?.bidding ? '-' : ns_util.numberFormat(ns_util.toInteger(_data.bidding_gold)));
    dialog.cont_obj.content_card_wrap.empty().append(ns_hero.cardDetailDraw(_data.hero_info.hero_pk, 'N', false, _data.hero_info));

    dialog.cont_obj.bidding_list_wrap.empty();

    if (ns_util.math(bidding_best).gt(0)) {
        for (let [k, d] of Object.entries(_data.bid_list)) {
            let p = document.createElement('p');
            let span = document.createElement('span');
            span.innerHTML = ns_util.numberFormat(ns_util.toInteger(d.gold)) + ' (' + d.bid_dt + ')';
            p.appendChild(span);
            dialog.cont_obj.bidding_list_wrap.append(p);
        }
    }
}

/* ************************************************** */
ns_button.buttons.build_ReceptionHall_bid_close = new nsButtonSet('build_ReceptionHall_bid_close', 'button_back', 'build_ReceptionHall_bid', { base_class: ns_button.buttons.common_close });
ns_button.buttons.build_ReceptionHall_bid_sub_close = new nsButtonSet('build_ReceptionHall_bid_sub_close', 'button_full', 'build_ReceptionHall_bid', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.build_ReceptionHall_bid_close_all = new nsButtonSet('build_ReceptionHall_bid_close_all', 'button_close_all', 'build_ReceptionHall_bid', { base_class:ns_button.buttons.common_close_all });


ns_button.buttons.hero_free_bid_value = new nsButtonSet('hero_free_bid_value', 'button_input', 'build_ReceptionHall_bid');
ns_button.buttons.hero_free_bid_value.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('keypad', {max: ns_cs.d.terr.gold_curr.v, min: 0,
        callback:function(data){
            ns_button.buttons.hero_free_bid_value.obj.text(data);
        }
    });
}

ns_button.buttons.hero_free_bid_up = new nsButtonSet('hero_free_bid_up', 'button_arrow_up', 'build_ReceptionHall_bid');
ns_button.buttons.hero_free_bid_up.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_ReceptionHall_bid;
    let bidding_gold = ns_util.toInteger(dialog.bidding_gold);
    let current_bidding_gold = ns_util.toInteger(ns_button.buttons.hero_free_bid_value.obj.text());
    let current_gold = ns_cs.d.terr.gold_curr.v;

    // 재입찰 시 기존 입찰금 더해주기
    if (bidding_gold) {
        current_gold += bidding_gold;
    }
    current_gold = ns_util.math(current_gold).lt(0) ? 0 : ns_util.math(current_bidding_gold).plus(dialog.free_bid_gold_unit).integer
    ns_button.buttons.hero_free_bid_value.obj.text(current_gold);
}

ns_button.buttons.hero_free_bid_down = new nsButtonSet('hero_free_bid_down', 'button_arrow_down', 'build_ReceptionHall_bid');
ns_button.buttons.hero_free_bid_down.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_ReceptionHall_bid;
    let bidding_best = ns_util.toInteger(dialog.bidding_best);
    let next_gold = ns_util.math(bidding_best).lt(1) ? dialog.bid_min_gold : bidding_best;
    ns_button.buttons.hero_free_bid_value.obj.text(next_gold);
}

ns_button.buttons.hero_free_bid_submit = new nsButtonSet('hero_free_bid_submit', 'button_special', 'build_ReceptionHall_bid');
ns_button.buttons.hero_free_bid_submit.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_ReceptionHall_bid;
    let current_gold = ns_cs.d.terr.gold_curr.v;
    let now_text = ns_i18n.t('bidding_gold'); // 입찰액

    let bidding_best = ns_util.toInteger(dialog.bidding_best);
    let bidding_gold = ns_util.toInteger(dialog.bidding_gold);
    let current_bidding_gold = ns_util.toInteger(ns_button.buttons.hero_free_bid_value.obj.text());

    // 재입찰 시 기존 입찰금 더해주기
    if (bidding_gold) {
        current_gold += bidding_gold;
        now_text = ns_i18n.t('add_bidding_gold'); // 추가 입찰액
    }

    if (ns_util.math(current_gold).lt(0) || ns_util.math(current_gold).lt(current_bidding_gold)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_own_resource_gold_lack')); // 소지한 황금이 부족합니다.
        return;
    }

    if (ns_util.math(current_bidding_gold).lt(dialog.bid_min_gold)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_reception_bidding_gold_min')); // 입찰 가능 금액보다 입찰금이 낮습니다.<br /><br />※최고 입찰금 보다 높은 금액으로<br/>입찰에 참여해 주세요.
        return;
    }

    if (ns_util.math(current_bidding_gold).lte(bidding_best) && ns_util.math(bidding_best).gt(0)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_reception_bidding_gold_warning')); // 최대 입찰금과 같거나 낮습니다.
        return;
    }

    if (ns_util.math(dialog.free_bid_gold_unit).gt(current_bidding_gold)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_reception_bidding_gold_warning')); // 최대 입찰금과 같거나 낮습니다.
        return;
    }

    ns_dialog.setDataOpen('confirm', { text: `<p class="text_weight_bold text_size_large">${now_text}: ${ns_util.math(current_bidding_gold).minus(bidding_gold).number_format}</p><br/><br/>${ns_i18n.t('msg_reception_bidding_gold_confirm')}`, // 재야영웅 입찰은 취소가 불가하니<br/>신중히 결정해주십시오.<br/><br/>입찰 하시겠습니까?
        okFunc: () => {
            let post_data = { };
            post_data['hero_free_pk'] = dialog.hero_free_pk;
            post_data['bidding_gold'] = current_bidding_gold;

            ns_xhr.post('/api/heroFree/bidding', post_data, function(_data, _status)
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];

                // dialog.need_list_reload = true;
                ns_dialog.setDataOpen('message', ns_i18n.t('msg_reception_bidding_complete')); // <strong>재야 영웅 입찰에 참여해 주셔서 감사합니다.</strong><br /><br />입찰 결과는 입찰 마감 후 보고서를 통해 통보되며<br />입찰 마감 전에는 입찰 금액 수정이 가능 합니다.
                ns_dialog.close('build_ReceptionHall_bid');
                ns_dialog.dialogs.build_ReceptionHall.list_refresh = true;
                ns_dialog.dialogs.build_ReceptionHall.drawTab();
            }, { useProgress: true });
        }
    });
}


