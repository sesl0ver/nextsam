ns_dialog.dialogs.hero_manage = new nsDialogSet('hero_manage', 'dialog_full', 'size-full');
ns_dialog.dialogs.hero_manage.current_tab = null;
ns_dialog.dialogs.hero_manage.current_tab_sub = null;
// ns_dialog.dialogs.hero_manage.total_page = null;

ns_dialog.dialogs.hero_manage.cacheContents = function()
{
    // this.cont_obj.content_current_page = new nsObject('.content_current_page', this.obj);
    // this.cont_obj.content_total_page = new nsObject('.content_total_page', this.obj);

    // this.cont_obj.content_hero_manage_table = new nsObject('.content_hero_manage_table', this.obj);
    this.cont_obj.hero_manage_list_wrap = new nsObject('.hero_manage_list_wrap', this.obj);

    this.cont_obj.hero_officer_appoint = new nsObject('.hero_officer_appoint', this.obj);
    this.cont_obj.hero_officer_amount = new nsObject('.hero_officer_amount', this.obj);
    this.cont_obj.hero_slot_used = new nsObject('.hero_slot_used', this.obj);
    this.cont_obj.hero_slot_amount = new nsObject('.hero_slot_amount', this.obj);

    this.cont_obj.hero_manage_item_skeleton = new nsObject('#hero_manage_item_skeleton');
}

ns_dialog.dialogs.hero_manage.draw = function()
{
    if (! this.visible) {
        ns_button.toggleGroupSingle(ns_button.buttons.hero_manage_tab_all);
        // ns_button.toggleGroupSingle(ns_button.buttons.hero_manage_tab_sub_stat);

        this.current_tab = 'all';

        ns_select_box.set('hero_manage_sort', 'rare', 'desc');
        ns_button.buttons.hero_manage_sort.obj.text(ns_select_box.getText('hero_manage_sort'));

        ns_button.buttons.hero_manage_territory.obj.hide();

        // ns_dialog.dialogs.card.reset_flip = true;
    }

    //도움말 관련하여 추가 - 첫 접속시 무조건 한번은 보여주도록
    /*let help_type = 'HeroManage';
    if (!window.localStorage.getItem('open_help_' + help_type))
    {
        qbw_dlg.setDataOpen('game_help', {'type':help_type});
        window.localStorage.setItem('open_help_' + help_type, 'Y');
    }*/
    this.drawList();
}

ns_dialog.dialogs.hero_manage.erase = function ()
{
    ns_dialog.dialogs.hero_card.card_list = []; // 닫을때 초기화 필요
}

ns_dialog.dialogs.hero_manage.drawColumn = function()
{
    let dialog = ns_dialog.dialogs.hero_manage;
    let tab_sub = ns_button.toggleGroupValue('hero_manage_tab_sub')[0].split('_tab_sub_').pop();
    if (tab_sub !== dialog.current_tab_sub) {
        /*if (dialog.current_tab_sub) {
            dialog.cont_obj.content_hero_manage_table.removeCss(dialog.current_tab_sub);
        }
        dialog.cont_obj.content_hero_manage_table.addCss(tab_sub);*/
        dialog.current_tab_sub = tab_sub;
    }
}

ns_dialog.dialogs.hero_manage.drawList = function()
{
    let tab = ns_button.toggleGroupValue('hero_manage_tab')[0].split('_tab_').pop();
    let post_data = {};

    post_data['type'] = 'list_' + tab;
    // post_data['page_num'] = this.current_page;

    let select_box = ns_select_box.get('hero_manage_sort');
    if (select_box) {
        post_data['order_by'] = select_box.val;
        post_data['order_type'] = select_box.sort;
    }
    let select_box_sub = ns_select_box.get('hero_manage_territory');
    if (tab === 'all' && select_box_sub) {
        if (select_box_sub.val !== 'all') {
            post_data['type'] = 'list_territory';
            post_data['sel_posi_pk'] = select_box_sub.val;
        }
    }

    ns_xhr.post('/api/heroManage/list', post_data, (_data, _status) => {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        let dialog = ns_dialog.dialogs.hero_manage;
        let tab = ns_button.toggleGroupValue('hero_manage_tab')[0].split('_tab_').pop();

        // dialog.current_page = _data.curr_page;
        // dialog.total_page = _data.total_page;

        // dialog.cont_obj.content_current_page.text(dialog.current_page);
        // dialog.cont_obj.content_total_page.text(dialog.total_page);

        dialog.cont_obj.hero_slot_used.text(_data.used_slot);

        // 내가 가진 영웅들의 m_hero_pk 목록을 m_hero_base_pk 목록으로 바꿈
        if (_data?.appo_heroes_m && Array.isArray(_data.appo_heroes_m) && _data.appo_heroes_m.length > 0) {
            for (let i in _data.appo_heroes_m) {
                if (typeof ns_cs.m.hero[_data.appo_heroes_m[i]] == 'object') {
                    _data.appo_heroes_m[i] = ns_cs.m.hero[_data.appo_heroes_m[i]].m_hero_base_pk;
                } else {
                    _data.appo_heroes_m[i] = -1;
                }
            }
        }


        ns_dialog.dialogs.hero_card.card_list = [];
        // let tbody = dialog.cont_obj.content_hero_manage_table.find('tbody');
        let list = dialog.cont_obj.hero_manage_list_wrap;
        dialog.buttonClear();
        list.empty();

        if (! _data?.hero_list || _data.hero_list.length < 1) {
            // 리스트가 없을 때
            // let tr = document.createElement('tr');

            // let col1 = document.createElement('td');
            // col1.colSpan = 18;
            let col1_span = document.createElement('span');
            col1_span.classList.add('empty_list_item');

            if (tab === 'visit') {
                col1_span.innerHTML = ns_i18n.t('empty_hero_list_visit_description'); // 영입할 영웅이 없습니다.<br />영입할 영웅이 없을 경우 보물창고의 영웅 영입 아이템을 사용하거나<br />아이템샵에서 영웅 영입 아이템 구입하여 영웅을 영입해 보십시오.
            } else if (tab === 'guest') {
                col1_span.innerHTML = ns_i18n.t('empty_hero_list_guest_description'); // 등용할 영웅이 없습니다.<br />등용할 영웅이 없을 경우 보물창고의 영웅 영입 아이템을 사용하거나<br />아이템샵에서 영웅 영입 아이템 구입하여 영웅을 영입 후 등용해 보십시오.
            }/* else if (tab === 'over_rank') {
                col1_span.innerHTML = '오버랭크 영웅이 없습니다.<br />오버랭크 영웅은 영입 후 24시간 동안 사용할 수 있게 되며,<br />사용이 종료되면 무능화가 됩니다. (모든 능력치 1 상태)';
            }*/

            // col1.appendChild(col1_span);
            // tr.appendChild(col1);
            list.append(col1_span);
            return;
        }

        dialog.sorted = _data.hero_list;

        dialog.drawListNext();

        // dialog.drawColumn();
        ns_hero.deckReload();
        dialog.drawHeroUnread();
    });
};

ns_dialog.dialogs.hero_manage.drawListNext = function ()
{
    let dialog = ns_dialog.dialogs.hero_manage;
    let list = dialog.cont_obj.hero_manage_list_wrap;

    for (let d of Object.values(dialog.sorted)) {
        let m_hero = ns_cs.m.hero[d.m_hero_pk];
        let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];

        let skeleton = dialog.cont_obj.hero_manage_item_skeleton.clone();

        let card = ns_hero.cardDraw(d.hero_pk, 'N', false, d, false, false, true);
        card.setAttribute('id', `ns_button_hero_manage_card_open_${d.hero_pk}`);

        skeleton.find('.card_slot').append(card);

        // status 가 card 내에서 초기회 되나, _is_trans 를 true로 하고 사용할 경우
        // 표시되지 않아야할 텍스트가 같이 표시되는 문제가 있어
        // 해당 status 부분만 다시 적용되도록 수정.
        let span = document.createElement('span');
        if (d.status === 'G') {
            if (ns_hero.checkSameHero(ns_cs.m.hero[d.m_hero_pk].m_hero_base_pk)) {
                card.addCss('same_hero');
                span.innerText = ns_i18n.t('possession_hero'); // 영웅 보유
            } else {
                card.removeCss('same_hero');
                span.innerText = ns_i18n.t('before_appoint'); // 등용 대기
            }
        } else if (d.status === 'A') {
            span.innerText = d.status_text;
        } else {
            span.innerHTML = `${ns_i18n.t('before_recruitment')}<br />`; // 영입 대기
            let remain_time = d.timedjob_dt_ut - ns_timer.now();
            span.innerHTML += (remain_time <= 0) ? ns_i18n.t('in_progress') : ns_util.getCostsTime(remain_time);
        }
        skeleton.find('.hero_state').append(span);

        //status 별도 초기화
        let _card_status = new nsObject('.hero_card_status', card);
        if ( d.status ==='A') {
            if (d.status_cmd === 'A') {
                _card_status.addCss('hero_card_status_assigned');
            } else if (d.status_cmd === 'C')  {
                _card_status.addCss('hero_card_status_cmd_ing');
            } else if (d.status_cmd === 'T') {
                _card_status.addCss('hero_card_status_treatment');
            } else if (d.status_cmd === 'P') {
                _card_status.addCss('hero_card_status_enchant');
            }
        } else {
            // 'I' 대기 - 선택모드일 경우 not_condition 체크
            if (ns_util.math(d.loyalty).lt(1)) {
                // 여기서 그냥 불만족 인지 태업인지 체크 가능
                if (ns_util.math(d.loyalty).lt(1)) {
                    _card_status.addCss('hero_card_status_strike');
                } else {
                    // 조건 불만족
                    _card_status.addCss('hero_card_status_not_condition');
                }
            } // 추천은 이쪽에서 체크할 필요는 없어서 따로 작성 안함.
        }

        list.append(skeleton);

        /*let tr = document.createElement('tr');

        let span1 = document.createElement('span');
        if (d.group_type) {
            span1.classList.add('content_group');
            span1.classList.add(`hero_manage_group_${d.group_type}`);
        }
        let columns = [];
        let col = document.createElement('td');
        col.classList.add('content_hero_name');
        col.appendChild(span1);
        col.innerHTML += `${m_hero_base.name}<br />Lv.${m_hero.level}`;

        let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
        button.setAttribute('id', `ns_button_hero_manage_card_open_${d.hero_pk}`);
        col.append(button);

        columns.push(col);

        col = document.createElement('td');
        col.innerText = m_hero_base.rare_type;
        columns.push(col);

        // filter_stat
        for (let _stat of ['leadership', 'mil_force', 'intellect', 'politics', 'charm']) {
            col = document.createElement('td');
            col.classList.add('filter_stat');
            col.classList.add(_stat);
            col.innerText = d[_stat];
            columns.push(col);
        }

        // filter_officer
        col = document.createElement('td');
        col.classList.add('filter_officer');
        col.innerText = (d.m_offi_pk != null && typeof ns_cs.m.offi[d.m_offi_pk] == 'object') ? ns_cs.m.offi[d.m_offi_pk].title : '-';
        columns.push(col);

        col = document.createElement('td');
        col.classList.add('filter_officer');
        col.innerText = d.loyalty;
        columns.push(col);

        col = document.createElement('td');
        col.classList.add('filter_officer');
        let skill_data = new Set();
        for (let i of ['1', '2', '3', '4', '5', '6']) {
            let pk = d[`main_slot_pk${i}`];
            if (pk) {
                skill_data.add(pk);
            }
        }
        col.innerText = String(skill_data.size);
        columns.push(col);

        // filter_army
        for (let _army of ['infantry', 'spearman', 'pikeman', 'archer', 'horseman', 'siege']) {
            col = document.createElement('td');
            col.classList.add('filter_army');
            col.innerText = m_hero_base[`mil_aptitude_${_army}`];
            columns.push(col);
        }

        // status
        col = document.createElement('td');
        if (d.status === 'G') {
            col.innerText = 'Wait Appoint'; // '등용대기 중';
            if (dialog.isOwnHero(_data.appo_heroes_m, ns_cs.m.hero[d.m_hero_pk].m_hero_base_pk)) {
                tr.classList.remove(...tr.classList);
                tr.classList.add('text_gray');
            }
        } else if (d.status === 'A') {
            col.innerText = d.status_text;
        } else {
            col.innerText = 'Wait Recruit'; // '영입대기 중';
            // left_time - TODO timer 돌려야 하지 않을까?
            let remain_time = d.timedjob_dt_ut - ns_timer.now();
            col.innerText = (remain_time <= 0) ? system_text.progress : ns_util.getCostsTime(remain_time);
        }
        if (m_hero.over_type === 'Y') {
            col.innerHTML += '<br />오버랭크';
        }
        columns.push(col);

        // 현황
        col = document.createElement('td');
        col.innerText = (! d?.territory_title) ? '-' : d.territory_title;

        let text_over_rank = false;
        if (m_hero.over_type === 'Y') {
            if(d.status === 'A' || d.status === 'G') {
                // 오버랭크 남은 시간
                let now = ns_timer.now();
                if(! d?.overrank_end_dt || d.overrank_end_dt < now) {
                    col.innerHTML += '<br />' + system_text.overrank_end;
                    text_over_rank = true;
                } else {
                    let over_rank_time = d.overrank_end_dt - now;
                    over_rank_time = (over_rank_time <= 0) ? system_text.overrank_end : ns_util.getCostsTime(over_rank_time);
                    col.innerHTML += '<br />' + over_rank_time;
                }
            }
        }
        columns.push(col);

        if (text_over_rank) {
            tr.classList.add('text_over_rank');
        }

        for (let col of columns) {
            tr.appendChild(col);
        }

        tbody.append(tr);*/
        ns_dialog.dialogs.hero_card.card_list.push(d);

        let button_id = `hero_manage_card_open_${d.hero_pk}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, null, 'hero_manage');
        ns_button.buttons[button_id].mouseUp = function ()
        {
            ns_dialog.setDataOpen('hero_card', d);
        }
        dialog.buttons.push(ns_button.buttons[button_id]);
    }
    dialog.scroll_handle.refreshScroll();
}

ns_dialog.dialogs.hero_manage.drawHeroUnread = function()
{
    let main_flag = false;
    if (ns_cs.d.lord?.unread_hero_cnt && ns_util.math(ns_cs.d.lord.unread_hero_cnt.v).gt(0)) {
        ns_button.buttons.hero_manage_tab_visit.obj.addCss('tab_flag_new');
        main_flag = true;
    } else {
        ns_button.buttons.hero_manage_tab_visit.obj.removeCss('tab_flag_new');
    }

    if (ns_cs.d.lord?.unread_guest_cnt && ns_util.math(ns_cs.d.lord.unread_guest_cnt.v).gt(0)) {

        ns_button.buttons.hero_manage_tab_guest.obj.addCss('tab_flag_new');
        main_flag = true;
    } else {
        ns_button.buttons.hero_manage_tab_guest.obj.removeCss('tab_flag_new');
    }

    /*if (ns_cs.d.lord?.unread_overrank_cnt && ns_util.math(ns_cs.d.lord.unread_overrank_cnt.v).gt(0)) {
        ns_button.buttons.hero_manage_tab_over_rank.obj.addCss('tab_flag_new');
        main_flag = true;
    } else {
        ns_button.buttons.hero_manage_tab_over_rank.obj.removeCss('tab_flag_new');
    }*/

    if (main_flag) {
        ns_button.buttons.main_hero_manage.obj.element.dataset.count = 'N';
        ns_button.buttons.main_hero_manage.obj.addCss('main_flag_new');
    } else {
        delete ns_button.buttons.main_hero_manage.obj.element.dataset.count;
        ns_button.buttons.main_hero_manage.obj.removeCss('main_flag_new');
    }
}

ns_dialog.dialogs.hero_manage.isOwnHero = function(m_hero_base_pk_list, target_m_hero_base_pk)
{
    let len = m_hero_base_pk_list.length;
    for (let i = 0; i < len; i++) {
        if (m_hero_base_pk_list[i] === target_m_hero_base_pk) {
            return true;
        }
        let appoint = m_hero_base_pk_list[i];
        let target = ns_cs.m.hero_base[target_m_hero_base_pk];

        if(appoint.name === target.name) {
            if (appoint.yn_modifier === 'N' && target.over_type === 'Y') {
                return true;
            } else if (appoint.over_type === 'Y' && target.yn_modifier === 'N') {
                return true;
            }	else if (appoint.over_type === target.over_type && appoint.yn_modifier === target.yn_modifier) {
                return true;
            }
        }
    }
    return false;
};

ns_dialog.dialogs.hero_manage.timerHandler = function()
{
    let timer_id = this.tag_id;

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.hero_manage.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.hero_manage.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.hero_manage;
    let available_officer_count = ns_util.toInteger(ns_cs.d.lord.m_offi_pk.v);
    available_officer_count = available_officer_count < 110121 ? 110120 : available_officer_count;
    let slot_count = ns_util.math(ns_cs.d.lord.level.v).mul(12).integer;
    available_officer_count = ns_util.math(available_officer_count).minus(110120).plus(slot_count).integer;

    dialog.cont_obj.hero_officer_appoint.text(ns_cs.d.lord.num_appoint_hero.v);
    dialog.cont_obj.hero_officer_amount.text(available_officer_count);
    dialog.cont_obj.hero_slot_amount.text(ns_cs.d.lord.num_slot_guest_hero.v);
}

/* ************************************************** */

ns_button.buttons.hero_manage_close = new nsButtonSet('hero_manage_close', 'button_back', 'hero_manage', { base_class: ns_button.buttons.common_close });
ns_button.buttons.hero_manage_sub_close = new nsButtonSet('hero_manage_sub_close', 'button_full', 'hero_manage', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.hero_manage_close_all = new nsButtonSet('hero_manage_close_all', 'button_close_all', 'hero_manage', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.game_help_HeroManage = new nsButtonSet('game_help_HeroManage', 'btn_dlg_help', 'hero_manage', {base_class:ns_button.buttons.buil_help});

/* ********** */

ns_button.buttons.hero_manage_tab_all = new nsButtonSet('hero_manage_tab_all', 'button_tab', 'hero_manage', { toggle_group: 'hero_manage_tab' });
ns_button.buttons.hero_manage_tab_all.mouseUp = function(_e)
{
    ns_select_box.set('hero_manage_territory', 'all');
    ns_button.buttons.hero_manage_territory.obj.text(ns_select_box.getText('hero_manage_territory'));
    let arr = this.tag_id.split('_tab_');
    let tab = arr.pop();

    let dialog = ns_dialog.dialogs.hero_manage;
    dialog.scroll_handle.initScroll();
    if (tab === dialog.current_tab) {
        return;
    }

    ns_button.buttons.hero_manage_territory.obj.hide(); // TODO 영지가 1개로 고정되면서 필요없어지는 버튼
    /*if (tab === 'all') {
        ns_button.buttons.hero_manage_territory.obj.show();
    } else {
        ns_button.buttons.hero_manage_territory.obj.hide();
    }*/

    ns_button.toggleGroupSingle(this);
    dialog.current_page = 1;
    dialog.current_tab = tab;
    dialog.drawList();
}
ns_button.buttons.hero_manage_tab_visit = new nsButtonSet('hero_manage_tab_visit', 'button_tab', 'hero_manage', { base_class:ns_button.buttons.hero_manage_tab_all, toggle_group: 'hero_manage_tab' });
ns_button.buttons.hero_manage_tab_guest = new nsButtonSet('hero_manage_tab_guest', 'button_tab', 'hero_manage', { base_class:ns_button.buttons.hero_manage_tab_all, toggle_group: 'hero_manage_tab' });
ns_button.buttons.hero_manage_tab_appoint = new nsButtonSet('hero_manage_tab_appoint', 'button_tab', 'hero_manage', { base_class:ns_button.buttons.hero_manage_tab_all, toggle_group: 'hero_manage_tab' });
// ns_button.buttons.hero_manage_tab_territory = new nsButtonSet('hero_manage_tab_territory', 'button_tab', 'hero_manage', { base_class:ns_button.buttons.hero_manage_tab_all, toggle_group: 'hero_manage_tab' });
// ns_button.buttons.hero_manage_tab_over_rank = new nsButtonSet('hero_manage_tab_over_rank', 'button_tab', 'hero_manage', { base_class:ns_button.buttons.hero_manage_tab_all, toggle_group: 'hero_manage_tab' });

/*ns_button.buttons.hero_manage_tab_sub_stat = new nsButtonSet('hero_manage_tab_sub_stat', 'button_tab_sub', 'hero_manage', { toggle_group: 'hero_manage_tab_sub' });
ns_button.buttons.hero_manage_tab_sub_stat.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_manage;

    ns_button.toggleGroupSingle(this);
    dialog.drawColumn();
}
ns_button.buttons.hero_manage_tab_sub_officer = new nsButtonSet('hero_manage_tab_sub_officer', 'button_tab_sub', 'hero_manage', { base_class: ns_button.buttons.hero_manage_tab_sub_stat, toggle_group: 'hero_manage_tab_sub' });
ns_button.buttons.hero_manage_tab_sub_army = new nsButtonSet('hero_manage_tab_sub_army', 'button_tab_sub', 'hero_manage', { base_class: ns_button.buttons.hero_manage_tab_sub_stat, toggle_group: 'hero_manage_tab_sub' });*/

ns_button.buttons.hero_manage_list_top = new nsButtonSet('hero_manage_list_top', null, 'hero_manage');
ns_button.buttons.hero_manage_list_top.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_manage;
    dialog.scroll_handle.initScrollTo('top');
}

ns_button.buttons.hero_manage_list_bottom = new nsButtonSet('hero_manage_list_bottom', null, 'hero_manage');
ns_button.buttons.hero_manage_list_bottom.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_manage;
    dialog.scroll_handle.initScrollTo('bottom');
}


ns_button.buttons.hero_manage_open = new nsButtonSet('hero_manage_open', null, 'hero_manage');
ns_button.buttons.hero_manage_open.mouseUp = function(_e)
{
    let hero_pk = this.tag_id.split('_').pop();

    ns_dialog.setDataOpen('hero_card', hero_pk);

    ns_sound.play('button_4');
}

/*ns_button.buttons.hero_manage_prev = new nsButtonSet('hero_manage_prev', 'button_page_prev', 'hero_manage');
ns_button.buttons.hero_manage_prev.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_manage;

    dialog.current_page--;
    if (dialog.current_page < 1) {
        dialog.current_page = dialog.total_page;
    }

    dialog.drawList();
}

ns_button.buttons.hero_manage_next = new nsButtonSet('hero_manage_next', 'button_page_next', 'hero_manage');
ns_button.buttons.hero_manage_next.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_manage;

    dialog.current_page++;
    if (dialog.current_page > dialog.page_total) {
        dialog.current_page = 1;
    }

    dialog.drawList();
}*/

ns_button.buttons.hero_manage_sort = new nsButtonSet('hero_manage_sort', 'button_select_box', 'hero_manage');
ns_button.buttons.hero_manage_sort.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('select_box', {select_box_id: 'hero_manage_sort'});
}


ns_button.buttons.hero_manage_territory = new nsButtonSet('hero_manage_territory', 'button_select_box', 'hero_manage');
ns_button.buttons.hero_manage_territory.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('select_box', {select_box_id: 'hero_manage_territory'});
};

ns_button.buttons.hero_manage_officer_deal = new nsButtonSet('hero_manage_officer_deal', 'button_middle_2', 'hero_manage');
ns_button.buttons.hero_manage_officer_deal.mouseUp = function(_e)
{
    // 매관매직
    ns_dialog.setDataOpen('item_use', { m_item_pk: 500163,
        callback: function(data)
        {
            // [$1] 아이템을 사용하였습니다.<br/><br/>현재 $2 / 최대 $3
            ns_dialog.setDataOpen('message', { text : ns_i18n.t('msg_item_use_slot_open', [ns_i18n.t(`item_title_500163`), data['officer_count'], ns_engine.cfg.max_officer_count]) });
        }
    });
}

ns_button.buttons.hero_manage_hero_slot = new nsButtonSet('hero_manage_hero_slot', 'button_middle_2', 'hero_manage');
ns_button.buttons.hero_manage_hero_slot.mouseUp = function(_e)
{
    // 슬롯확장
    ns_dialog.setDataOpen('item_use', { m_item_pk: 500019,
        callback: function(data)
        {
            // [$1] 아이템을 사용하였습니다.<br/><br/>현재 $2 / 최대 $3
            ns_dialog.setDataOpen('message', { text : ns_i18n.t('msg_item_use_slot_open', [ns_i18n.t(`item_title_500019`), data['num_slot_guest_hero'], ns_engine.cfg.max_num_slot_guest_hero]) });
        }
    });
}
