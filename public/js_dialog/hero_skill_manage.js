ns_dialog.dialogs.hero_skill_manage = new nsDialogSet('hero_skill_manage', 'dialog_full', 'size-full');
ns_dialog.dialogs.hero_skill_manage.buttons = [];
// ns_dialog.dialogs.hero_skill_manage.current_page = null;
// ns_dialog.dialogs.hero_skill_manage.total_page = null;
ns_dialog.dialogs.hero_skill_manage.current_tab_type = null;
ns_dialog.dialogs.hero_skill_manage.open_slot_cnt = 0;
ns_dialog.dialogs.hero_skill_manage.remain_slot_cnt = 0;
ns_dialog.dialogs.hero_skill_manage.lock_class = {};
ns_dialog.dialogs.hero_skill_manage.equip_class = {};

ns_dialog.dialogs.hero_skill_manage.new_skill_flag = {'D':false, 'A':false, 'P':false, 'B':false, 'S':false};

ns_dialog.dialogs.hero_skill_manage.cacheContents = function()
{
    // this.s.cont_background = this.obj.find('.cont_background').css({'min-height': parseInt(qbw_e.size.uiMiddleHeight+17) + 'px'}); // min-height 처음에 한번만 설정하기

    this.cont_obj.content_hero_card = new nsObject('.content_hero_card', this.obj);
    this.cont_obj.content_hero_skill_wrap = new nsObject('.content_hero_skill_wrap', this.obj);

    // this.cont_obj.content_current_page = new nsObject('.content_current_page', this.obj);
    // this.cont_obj.content_total_page = new nsObject('.content_total_page', this.obj);

    this.cont_obj.hero_skill_manage_list_wrap = new nsObject('.hero_skill_manage_list_wrap', this.obj);

    this.cont_obj.skill_list_skeleton = new nsObject('#skill_list_skeleton');
}

ns_dialog.dialogs.hero_skill_manage.draw = function()
{
    if (! this.visible)
    {
        this.prev_lud = 0;
        ns_button.toggleGroupSingle(ns_button.buttons.hero_skill_manage_tab_battle);

        ns_select_box.set('hero_skill_manage_sort', 'title', 'desc');
        ns_button.buttons.hero_skill_manage_sort.obj.text(ns_select_box.getText('hero_skill_manage_sort'));
    }

    //도움말 관련하여 추가 - 첫 접속시 무조건 한번은 보여주도록
    /*let help_type = 'HeroSkillManage';
    if (!window.localStorage.getItem('open_help_' + help_type))
    {
        ns_dialog.setDataOpen('game_help', {'type':help_type});
        window.localStorage.setItem('open_help_' + help_type, 'Y');
    }*/

    // this.current_page = 1;
    ns_button.buttons.hero_skill_medal_prize.setDisable();

    this.drawTab();
}

ns_dialog.dialogs.hero_skill_manage.erase = function()
{
    this.data = null;
    this.prev_lud = null;
}

ns_dialog.dialogs.hero_skill_manage.drawTab = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_skill_manage;

    // 영웅 카드 그리기
    dialog.cont_obj.content_hero_card.empty();
    if (dialog.data) {
        dialog.cont_obj.content_hero_card.append(ns_hero.cardDraw(dialog.data.hero_pk, 'N', false, dialog.data, false, false, true))
    }

    let tab = ns_button.toggleGroupValue('hero_skill_manage_tab')[0].split('_tab_').pop();
    let type;
    if (tab === 'develop') {
        type = 'D';
    } else if (tab === 'assign') {
        type = 'A';
    } else if (tab === 'perform') {
        type = 'P';
    } else if (tab === 'battle') {
        type = 'B';
    } else if (tab === 'special') {
        type = 'S';
    }
    if (! type) {
        return;
    }

    // flag 체크
    // this.s['cont_hero_skill_manage_flag_' + tab].hide();
    // this.new_skill_flag[type] = false;

    // 일단 창을 열면 숨기고
    /*$('#main_hero_skill > .cont_cnt_hero_skill').hide();

    $.each(this.new_skill_flag, function(k, v){
        // 하나라도 true 라면 메인쪽은 무조건 보여줌
        if (v == true)
            $('#main_hero_skill > .cont_cnt_hero_skill').show();
    });*/

    dialog.current_tab_type = type;
    dialog.drawList();
}

ns_dialog.dialogs.hero_skill_manage.drawList = function()
{
    let dialog = ns_dialog.dialogs.hero_skill_manage;
    let order_type = ns_select_box.get('hero_skill_manage_sort');

    let post_data = { };
    post_data['open_type'] = 'skill_manage';
    post_data['type'] = dialog.current_tab_type;
    post_data['order_type'] = order_type.val;
    post_data['order_by'] = order_type.sort;

    ns_xhr.post('/api/heroSkill/heroSkillList', post_data, dialog.drawRemote);
}

ns_dialog.dialogs.hero_skill_manage.drawRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.hero_skill_manage;
    // let order_type = ns_select_box.get('hero_skill_manage_sort');

    dialog.sorted = [];
    for (let [k, d] of Object.entries(_data.hero_skill_list)) {
        dialog.sorted.push({ ...d, 'rare': ns_cs.m.hero_skil[d.m_hero_skil_pk].rare, 'use_slot': ns_cs.m.hero_skil[d.m_hero_skil_pk].use_slot_count });
    }

    /*if(dialog.sorted.length > 1) {
        dialog.sorted = ns_util.arraySort(dialog.sorted, -1, order_type.val);
    }*/
    // dialog.current_page = _data.curr_page;
    // dialog.setPageView(_data.total_count);

    dialog.buttonClear();
    dialog.cont_obj.hero_skill_manage_list_wrap.empty();
    let max_list = 8;
    for (let d of dialog.sorted) {
        let skeleton = dialog.cont_obj.skill_list_skeleton.clone();
        skeleton.setAttribute('id', `ns_button_hero_skill_manage_${d.my_hero_skil_pk}`);
        let m = ns_cs.m.hero_skil[d.m_hero_skil_pk];

        skeleton.find('.skill_image').addCss('hero_skill_' + d.m_hero_skil_pk.substring(0, 4));
        skeleton.find('.skill_rare_type').addCss('hero_skill_rare' + m.rare);
        skeleton.find('.skill_title').text(m.title + ' Lv.' + m.rare);
        skeleton.find('.skill_count').text(d.skill_cnt);
        skeleton.find('.skill_use_slot').text(m.use_slot_count);

        dialog.cont_obj.hero_skill_manage_list_wrap.append(skeleton);

        ns_button.buttons[`hero_skill_manage_${d.my_hero_skil_pk}`] = new nsButtonSet(`hero_skill_manage_${d.my_hero_skil_pk}`, null, 'hero_skill_manage');
        ns_button.buttons[`hero_skill_manage_${d.my_hero_skil_pk}`].mouseUp = function (_e)
        {
            let set_data = {};
            set_data['my_hero_skil_pk'] = d.my_hero_skil_pk;
            set_data['m_hero_skil_pk'] = d.m_hero_skil_pk;
            set_data['skill_cnt'] = d.skill_cnt;
            set_data['remain_slot_cnt'] = dialog.remain_slot_cnt;
            if (dialog.data) {
                set_data['hero_pk'] = dialog.data.hero_pk;
            }
            ns_dialog.setDataOpen('hero_skill_detail', set_data);
        }
        dialog.buttons.push(ns_button.buttons[`hero_skill_manage_${d.my_hero_skil_pk}`]);
        max_list--;
    }

    // 빈칸 채우기
    /*if (max_list <= 8) {
        for (let i = 0, j = max_list; i < j; i++) {
            let div = document.createElement('div');
            div.classList.add('skill_box');
            div.classList.add('empty');
            dialog.cont_obj.hero_skill_manage_list_wrap.append(div);
        }
    }*/
}

ns_dialog.dialogs.hero_skill_manage.setPageView = function(total_count)
{
    let dialog = ns_dialog.dialogs.hero_skill_manage;

    dialog.total_page = 1;
    dialog.current_page = 1;
    if (ns_util.math(total_count).gt(0)) {
        dialog.total_page = ns_util.math(total_count).div(8).integer;
        dialog.total_page = ns_util.math(dialog.total_page).plus(ns_util.math(8).mod(8).gt(0) ? 1 : 0).integer;
    }

    if (dialog.visible) {
        dialog.cont_obj.content_current_page.text(dialog.current_page);
        dialog.cont_obj.content_total_page.text(dialog.total_page);
    }
}

ns_dialog.dialogs.hero_skill_manage.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.hero_skill_manage.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.hero_skill_manage.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.hero_skill_manage;

    if (typeof dialog.data != 'object' || ! dialog.data) {
        ns_button.buttons.hero_skill_medal_prize.setDisable();

        dialog.cont_obj.content_hero_skill_wrap.empty();
        for (let [k, d] of Object.entries(ns_cs.m.hero_skil_exp)) {
            let div = document.createElement('div');
            div.classList.add(`hero_skill_slot_${k}`);

            let image = document.createElement('div');
            image.classList.add('hero_skill_image');
            image.classList.add('lock1');

            div.appendChild(image);
            dialog.cont_obj.content_hero_skill_wrap.append(div);
        }
        return;
    }

    ns_button.buttons.hero_skill_medal_prize.setEnable();

    let data = dialog.data;
    let hero_pk = data.hero_pk;

    // 필요없는 갱신을 막기 위해 추가된 부분
    if (ns_cs.d.hero[hero_pk]) {
        if (dialog.prev_hero_pk === hero_pk && dialog.prev_lud === ns_cs.d.hero[hero_pk]['__lud'])
        {
            return;
        } else {
            dialog.prev_hero_pk = hero_pk;
            dialog.prev_lud = ns_cs.d.hero[hero_pk]['__lud'];
        }
    } else {
        if (ns_util.math(dialog.prev_lud).lt(0) && ns_util.math(dialog.prev_hero_pk).eq(hero_pk) && dialog.prev_lud === data['__lud']) {
            return false;
        } else {
            data['__lud'] = ns_timer.now();
            dialog.prev_hero_pk = hero_pk;
            dialog.prev_lud = data['__lud'];
        }
    }

    // 오픈된 슬롯 개수
    let open_slot_cnt = 0, equip_slot_cnt = 0;

    // 슬롯 초기화
    dialog.cont_obj.content_hero_skill_wrap.empty();

    let lock = false, skill_exp = data.skill_exp;
    dialog.open_slot_cnt = 0;
    for (let [k, d] of Object.entries(ns_cs.m.hero_skil_exp)) {
        let div = document.createElement('div');
        div.classList.add(`hero_skill_slot_${k}`);
        let image = document.createElement('div');
        image.classList.add('hero_skill_image');

        let base_button = ns_button.buttons.hero_skill_medal_prize;
        if (ns_util.math(skill_exp).lt(d.exp)) { // 닫힘
            if (! lock) {
                let open_exp = ns_cs.m.hero_skil_exp[open_slot_cnt].exp;
                let need_exp = ns_util.math(ns_cs.m.hero_skil_exp[k].exp).minus(open_exp).integer;
                let progress_rate = ns_util.math(need_exp).div(5).integer;
                let progress_exp = ns_util.math(skill_exp).minus(open_exp).integer;
                let type = Math.ceil(ns_util.math(progress_exp).div(progress_rate).number);
                image.classList.add(`lock${type > 0 ? type : 1}`);
                lock = true;
            } else {
                image.classList.add('lock1');
            }
        } else { // 열림 - 장착 여부 확인
            if (data[`m_hero_skil_pk${k}`] > 0) {
                image.classList.add(`hero_skill_${data[`m_hero_skil_pk${k}`].substring(0, 4)}`);
                image.classList.add(`rare_border_${data[`m_hero_skil_pk${k}`].substring(5, 6)}`);
                base_button = ns_button.buttons.hero_skill_detail;
                equip_slot_cnt++;
            }
            open_slot_cnt++;
        }
        image.setAttribute('id', `ns_button_hero_skill_slot_${k}`);
        div.appendChild(image);
        dialog.cont_obj.content_hero_skill_wrap.append(div);
        ns_button.buttons[`hero_skill_slot_${k}`] = new nsButtonSet(`hero_skill_slot_${k}`, null, 'hero_skill_manage', { base_class: base_button });
    }
    dialog.open_slot_cnt = open_slot_cnt;

    dialog.remain_slot_cnt = ns_util.math(open_slot_cnt).minus(equip_slot_cnt).integer;
}

ns_dialog.dialogs.hero_skill_manage.drawNewIcon = function(display_type)
{
    let dialog = ns_dialog.dialogs.hero_skill_manage;

    // 기술 관리에는 5개의 탭, display_type으로 구분
    let l = {'D':'develop', 'A':'assign', 'P':'perform', 'B':'battle', 'S':'special'};

    if (! dialog.cont_obj) {
        // 게임 시작 후 기술 관리를 열어본 적이 없으면 초기화 후 캐싱
        ns_dialog.dialogs.hero_skill_manage.init();
        ns_dialog.dialogs.hero_skill_manage.cacheContents();
    }

    // 타입에 맞는 flag 보여주기
    dialog.cont_obj['cont_hero_skill_manage_flag_' + l[display_type]].show();

    // 타입에 맞는 것 new 상태 체크를 위해 추가
    dialog.new_skill_flag[display_type] = true;
}

/* ************************************************** */

ns_button.buttons.hero_skill_manage_close = new nsButtonSet('hero_skill_manage_close', 'button_back', 'hero_skill_manage', { base_class: ns_button.buttons.common_close });
ns_button.buttons.hero_skill_manage_sub_close = new nsButtonSet('hero_skill_manage_sub_close', 'button_full', 'hero_skill_manage', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.hero_skill_manage_close_all = new nsButtonSet('hero_skill_manage_close_all', 'button_close_all', 'hero_skill_manage', { base_class:ns_button.buttons.common_close_all });

// ns_button.buttons.game_help_HeroSkillManage = new nsButtonSet('game_help_HeroSkillManage', 'btn_dlg_help', 'hero_skill_manage', {base_class:ns_button.buttons.build_help});

ns_button.buttons.hero_skill_manage_tab_battle = new nsButtonSet('hero_skill_manage_tab_battle', 'button_tab', 'hero_skill_manage', { toggle_group: 'hero_skill_manage_tab' });
ns_button.buttons.hero_skill_manage_tab_battle.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_skill_manage;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.drawTab();
}
ns_button.buttons.hero_skill_manage_tab_perform = new nsButtonSet('hero_skill_manage_tab_perform', 'button_tab', 'hero_skill_manage', { base_class: ns_button.buttons.hero_skill_manage_tab_battle, toggle_group: 'hero_skill_manage_tab' });
ns_button.buttons.hero_skill_manage_tab_assign = new nsButtonSet('hero_skill_manage_tab_assign', 'button_tab', 'hero_skill_manage', { base_class: ns_button.buttons.hero_skill_manage_tab_battle, toggle_group: 'hero_skill_manage_tab' });
ns_button.buttons.hero_skill_manage_tab_develop = new nsButtonSet('hero_skill_manage_tab_develop', 'button_tab', 'hero_skill_manage', { base_class: ns_button.buttons.hero_skill_manage_tab_battle, toggle_group: 'hero_skill_manage_tab' });
ns_button.buttons.hero_skill_manage_tab_special = new nsButtonSet('hero_skill_manage_tab_special', 'button_tab', 'hero_skill_manage', { base_class: ns_button.buttons.hero_skill_manage_tab_battle, toggle_group: 'hero_skill_manage_tab' });

ns_button.buttons.hero_skill_manage_prev = new nsButtonSet('hero_skill_manage_prev', 'button_page_prev', 'hero_skill_manage');
ns_button.buttons.hero_skill_manage_prev.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_skill_manage;

    dialog.current_page--;
    if (ns_util.math(dialog.current_page).lt(1)) {
        dialog.current_page = ns_util.toInteger(dialog.total_page);
    }
    dialog.drawTab();
}

ns_button.buttons.hero_skill_manage_next = new nsButtonSet('hero_skill_manage_next', 'button_page_next', 'hero_skill_manage');
ns_button.buttons.hero_skill_manage_next.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_skill_manage;

    dialog.current_page++;
    if (ns_util.math(dialog.current_page).lt(dialog.total_page)) {
        dialog.current_page = 1;
    }
    dialog.drawTab();
}

ns_button.buttons.hero_skill_manage_sort = new nsButtonSet('hero_skill_manage_sort', 'button_select_box', 'hero_skill_manage');
ns_button.buttons.hero_skill_manage_sort.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('select_box', {select_box_id: 'hero_skill_manage_sort'});
}

ns_button.buttons.hero_skill_manage_hero_list = new nsButtonSet('hero_skill_manage_hero_list', 'button_full', 'hero_skill_manage');
ns_button.buttons.hero_skill_manage_hero_list.mouseUp = function(_e)
{
    ns_dialog.open('hero_skill_manage_list');
}

ns_button.buttons.hero_skill_detail = new nsButtonSet('hero_skill_detail', 'button_empty', 'hero_skill_manage');
ns_button.buttons.hero_skill_detail.mouseUp = function(_e)
{
    let dlg = ns_dialog.dialogs.hero_skill_manage;
    let arr = _e.target.id.split('_');
    let slot = arr.pop();
    let data = dlg.data;

    if (! data) {
        return;
    }

    let set_data = {};
    set_data['slot_pk'] = slot;
    set_data['hero_pk'] = data.hero_pk;
    set_data['m_hero_skil_pk'] = data['m_hero_skil_pk' + slot];
    ns_dialog.setDataOpen('hero_skill_detail', set_data);
}

ns_button.buttons.hero_skill_medal_prize = new nsButtonSet('hero_skill_medal_prize', 'button_middle_2', 'hero_skill_manage');
ns_button.buttons.hero_skill_medal_prize.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_skill_manage;
    let arr = _e.target.id.split('_');
    let slot = arr.pop();
    let hero_pk = null;

    if (ns_util.isNumeric(slot) && ns_util.math(dialog.open_slot_cnt).gte(slot)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_plz_hero_skill_select')); // 기술을 선택하여 장착해주세요.
        return;
    }

    if (ns_util.math(ns_cs.d.lord.level.v).lt(2)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_lord_prize_need_level')); // 군주 2등급 이상 공적패 포상이 가능합니다.
        return;
    }

    if (dialog.data) {
        if (ns_util.isNumeric(dialog.data)) {
            hero_pk = dialog.data;
        } else {
            hero_pk = dialog.data.hero_pk;
        }
    }

    if (! hero_pk) {
        return false;
    }

    ns_dialog.setDataOpen('hero_medal_prize_list', { hero_pk: hero_pk });
}

/*******************************************************************/
ns_dialog.dialogs.hero_skill_detail = new nsDialogSet('hero_skill_detail', 'dialog_pop', 'size-small', { do_content_scroll: false, do_close_all: false});

ns_dialog.dialogs.hero_skill_detail.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);

    this.cont_obj.skill_image = new nsObject('.skill_image', this.obj);
    this.cont_obj.skill_description = new nsObject('.skill_description', this.obj);

    this.cont_obj.hero_skill_detail_table = new nsObject('.ns_table_common', this.obj);
};

ns_dialog.dialogs.hero_skill_detail.draw = function()
{
   this.drawDetail();

   if (this.data.slot_pk) {
       ns_button.buttons.hero_skill_detail_delete_equip.obj.show();
       ns_button.buttons.hero_skill_detail_un_equip.obj.show();
       ns_button.buttons.hero_skill_detail_equip.obj.hide();
       this.cont_obj.hero_skill_detail_table.addCss('hide_col');
   } else {
       ns_button.buttons.hero_skill_detail_delete_equip.obj.hide();
       ns_button.buttons.hero_skill_detail_un_equip.obj.hide();
       ns_button.buttons.hero_skill_detail_equip.obj.show();
       this.cont_obj.hero_skill_detail_table.removeCss('hide_col');
   }
};

ns_dialog.dialogs.hero_skill_detail.drawDetail = function()
{
    let dialog = ns_dialog.dialogs.hero_skill_detail;

    let data = dialog.data;
    let m = ns_cs.m.hero_skil[data.m_hero_skil_pk];
    dialog.cont_obj.content_pop_title.text(m.title + ' Lv.' + m.rare);

    dialog.cont_obj.skill_image.addCss(`hero_skill_${data.m_hero_skil_pk.substring(0,4)}`);
    dialog.cont_obj.skill_image.addCss('rare_border_' + m.rare);
    dialog.cont_obj.skill_description.html(m.description);

    if (data.mode === 'simple') {
        dialog.cont_obj.hero_skill_detail_table.hide();
    } else {
        dialog.cont_obj.hero_skill_detail_table.show();
        dialog.cont_obj.hero_skill_detail_table.find('.skill_count').text(data.skill_cnt);
        dialog.cont_obj.hero_skill_detail_table.find('.skill_level').text(m.rare);
        if(! this.data.hero_pk || this.data.remain_slot_cnt < m.use_slot_count) {
            ns_button.buttons.hero_skill_detail_equip.setDisable();
        } else {
            ns_button.buttons.hero_skill_detail_equip.setEnable();
        }
    }
};

ns_dialog.dialogs.hero_skill_detail.erase = function()
{
    let dialog = ns_dialog.dialogs.hero_skill_detail;
    let data = dialog.data;
    let m = ns_cs.m.hero_skil[this.data.m_hero_skil_pk];

    dialog.cont_obj.skill_image.removeCss(`hero_skill_${data.m_hero_skil_pk.substring(0,4)}`);
    dialog.cont_obj.skill_image.removeCss('rare_border_' + m.rare);
    this.data = null;
};


/* ************************************************** */

ns_button.buttons.hero_skill_detail_close = new nsButtonSet('hero_skill_detail_close', 'button_pop_close', 'hero_skill_detail', {base_class:ns_button.buttons.common_close});
ns_button.buttons.hero_skill_detail_sub_close = new nsButtonSet('hero_skill_detail_sub_close', 'button_full', 'hero_skill_detail', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.hero_skill_detail_equip = new nsButtonSet('hero_skill_detail_equip', 'button_pop_normal', 'hero_skill_detail');
ns_button.buttons.hero_skill_detail_equip.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_skill_detail;
    let data = dialog.data;
    let m = ns_cs.m.hero_skil[data.m_hero_skil_pk];

    try {
        ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_hero_skill_equip', [m.title, m.rare]),
            okFunc: () => {
                let posi_pk = '';
                if (ns_cs.d.hero[data.hero_pk]) {
                    posi_pk = ns_engine.game_data.cpp;
                }

                let post_data = { };
                post_data['hero_pk'] = data.hero_pk;
                post_data['my_hero_skil_pk'] = data.my_hero_skil_pk;
                post_data['curr_posi_pk'] = posi_pk;

                ns_xhr.post('/api/heroSkill/equip', post_data, function(_data, _status)
                {
                    if(! ns_xhr.returnCheck(_data)) {
                        return;
                    }
                    _data = _data['ns_xhr_return']['add_data'];

                    ns_dialog.close('hero_skill_detail');

                    ns_dialog.dialogs.hero_skill_manage.data = _data;
                    ns_dialog.dialogs.hero_skill_manage.drawList();

                    if (ns_dialog.dialogs.hero_card.visible) {
                        ns_dialog.dialogs.hero_card.main_card.update(_data);
                    }

                    if (ns_dialog.dialogs.hero_manage.visible) {
                        ns_dialog.dialogs.hero_manage.drawList();
                    }
                }, { useProgress: true });
            }
        });
    } catch (e) {
        console.error(e);
    }
}

ns_button.buttons.hero_skill_detail_un_equip = new nsButtonSet('hero_skill_detail_un_equip', 'button_pop_normal', 'hero_skill_detail');
ns_button.buttons.hero_skill_detail_un_equip.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_skill_detail;
    let data = dialog.data;

    try {
        ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_hero_skill_unequip'), // 영웅 기술 해제 시 [장착기술해제] 아이템이 소모됩니다.<br /><br />영웅 기술을 해제 하시겠습니까?
            okFunc: () => {
                // 장착 해제 아이템이 있는지 확인
                let check_item_pk = 500121;
                if (! ns_cs.d.item?.[check_item_pk] || ns_util.math(ns_cs.d.item[check_item_pk].item_cnt).lt(1)) {
                    // 아이템 구매 진행
                    let _confirm_text = ns_i18n.t('msg_need_buy_item', [ns_cs.m.item[check_item_pk].title, `<span class="content_item_qbig_amount">${ns_util.numberFormat(ns_cs.m.item[check_item_pk].price)}</span>`, `<span class="content_item_qbig_amount">${ns_util.numberFormat(ns_cs.d.cash.qbig.v)}</span>`])
                    ns_dialog.setDataOpen('confirm', { text : _confirm_text,
                        okFunc : () => {
                            // 큐빅이 장착 기술 해제의 가격 보다 적은지 확인
                            if (ns_util.math(ns_cs.d.cash.qbig.v).lt(ns_cs.m.item[check_item_pk].price)) {
                                ns_dialog.setDataOpen('confirm', { text : ns_i18n.t('msg_buy_qbig_confirm'), // 큐빅이 부족합니다.<br /><br />큐빅을 구매하시겠습니까?
                                    okFunc : () => {
                                        ns_engine.buyQbig();
                                    }
                                });
                            } else {
                                // 장착 해제 아이템 구매창
                                ns_dialog.setDataOpen('item_buy', { m_item_pk: check_item_pk });
                            }
                        }
                    });
                } else {
                    let posi_pk = '';
                    if (ns_cs.d.hero[data.hero_pk]) {
                        posi_pk = ns_engine.game_data.cpp;
                    }

                    let post_data = {};
                    post_data['hero_pk'] = data.hero_pk;
                    post_data['slot_pk'] = data.slot_pk;
                    post_data['curr_posi_pk'] = posi_pk;

                    ns_xhr.post('/api/heroSkill/unEquip', post_data, function(_data, _status)
                    {
                        if(! ns_xhr.returnCheck(_data)) {
                            return;
                        }
                        _data = _data['ns_xhr_return']['add_data'];

                        if (ns_dialog.dialogs.hero_skill_manage.visible) {
                            ns_dialog.dialogs.hero_skill_manage.data = _data;
                            ns_dialog.dialogs.hero_skill_manage.drawList();
                        }
                        ns_dialog.close('hero_skill_detail');

                        if (ns_dialog.dialogs.hero_card.visible) {
                            ns_dialog.dialogs.hero_card.main_card.update(_data);
                        }

                        if (ns_dialog.dialogs.hero_manage.visible) {
                            ns_dialog.dialogs.hero_manage.drawList();
                        }
                    }, { useProgress: true });
                }
            }
        });
    } catch (e) {
        console.error(e);
    }
}

ns_button.buttons.hero_skill_detail_delete_equip = new nsButtonSet('hero_skill_detail_delete_equip', 'button_pop_normal', 'hero_skill_detail');
ns_button.buttons.hero_skill_detail_delete_equip.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_skill_detail;

    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_hero_skill_delete'),
        okFunc: function()
        {
            let posi_pk = (ns_cs.d.hero[dialog.data.hero_pk]) ? ns_engine.game_data.cpp : '';
            let post_data = {};
            post_data['hero_pk'] = dialog.data.hero_pk;
            post_data['slot_pk'] = dialog.data.slot_pk;
            post_data['action'] = 'delete';
            post_data['curr_posi_pk'] = posi_pk;

            ns_xhr.post('/api/heroSkill/delete', post_data, function(_data, _status)
            {
                if(!ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];

                ns_dialog.close('hero_skill_detail');

                if (Object.keys(ns_dialog.dialogs.hero_skill_manage.cont_obj).length < 1) {
                    ns_dialog.dialogs.hero_skill_manage.cacheContents();
                }
                ns_dialog.dialogs.hero_skill_manage.data = _data;
                ns_dialog.dialogs.hero_skill_manage.drawList();

                if (ns_dialog.dialogs.hero_card.visible) {
                    ns_dialog.dialogs.hero_card.main_card.update(_data);
                }

                if (ns_dialog.dialogs.hero_manage.visible) {
                    ns_dialog.dialogs.hero_manage.drawList();
                }
            }, { useProgress: true });
        }
    });
}

/*******************************************************************/
ns_dialog.dialogs.hero_medal_prize_list = new nsDialogSet('hero_medal_prize_list', 'dialog_pop', 'size-medium', { do_close_all: false });
ns_dialog.dialogs.hero_medal_prize_list.sorted = null;

ns_dialog.dialogs.hero_medal_prize_list.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.hero_medal_prize_list_wrap = new nsObject('.hero_medal_prize_list_wrap', this.obj);

    this.cont_obj.item_quick_use_list_skeleton = new nsObject('#item_quick_use_list_skeleton');

    this.drawList(); // 1회만 그려주기
}

ns_dialog.dialogs.hero_medal_prize_list.drawList = function()
{
    let dialog = ns_dialog.dialogs.hero_medal_prize_list;
    dialog.cont_obj.hero_medal_prize_list_wrap.empty();
    dialog.sorted = Object.values(ns_cs.m.item).filter(i => i.type === 'M');
    dialog.sorted = ns_util.arraySort(dialog.sorted, -1, 'orderno');
    for (let m of dialog.sorted) {
        let skeleton = dialog.cont_obj.item_quick_use_list_skeleton.clone();
        let d = ns_cs.d.item[m.m_item_pk];

        skeleton.addCss('item_list_' + m.m_item_pk);
        skeleton.find('.use_item_image').addCss('item_image_' + m.m_item_pk);

        let item_count = (d && d.item_pk === m.m_item_pk) ? d.item_cnt : 0;
        skeleton.find('.use_item_title').text(m.title);
        skeleton.find('.use_item_amount').text(item_count);

        skeleton.find('.use_item_qbig_amount').remove();
        skeleton.find('.quick_use_desc').text(m.description_quickuse);

        skeleton.setAttribute('id', `ns_button_hero_medal_prize_selected_${m.m_item_pk}`);

        dialog.cont_obj.hero_medal_prize_list_wrap.append(skeleton);

        ns_button.buttons[`hero_medal_prize_selected_${m.m_item_pk}`] = new nsButtonSet(`hero_medal_prize_selected_${m.m_item_pk}`, null, 'hero_medal_prize_list', { base_class: ns_button.buttons.hero_medal_prize_selected });
    }
}

ns_dialog.dialogs.hero_medal_prize_list.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.hero_medal_prize_list.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.hero_medal_prize_list.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.hero_medal_prize_list;
    for (let m of dialog.sorted) {
        let d = ns_cs.d.item[m.m_item_pk];
        let item_count = (d && d.item_pk === m.m_item_pk) ? d.item_cnt : 0;
        dialog.cont_obj.hero_medal_prize_list_wrap.find(`.item_list_${m.m_item_pk}`).find('.use_item_amount').text(item_count);
    }
}

/* ************************************************** */

ns_button.buttons.hero_medal_prize_list_close = new nsButtonSet('hero_medal_prize_list_close', 'button_pop_close', 'hero_medal_prize_list', { base_class: ns_button.buttons.common_close });
ns_button.buttons.hero_medal_prize_list_sub_close = new nsButtonSet('hero_medal_prize_list_sub_close', 'button_full', 'hero_medal_prize_list', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.hero_medal_prize_selected = new nsButtonSet('hero_medal_prize_selected', 'button_empty', 'hero_medal_prize_list');
ns_button.buttons.hero_medal_prize_selected.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_medal_prize_list;
    let m_item_pk = _e.target.id.split('_').pop();
    let d = ns_cs.d.item[m_item_pk];
    let m = ns_cs.m.item[m_item_pk];

    if (! d) {
        return;
    }

    try {
        ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_use_medal_item_confirm', [m.title]), // 공적패를 사용하면 사용한 공적패는 사라지며<br />해당 영웅의 슬롯 경험치를 소량 획득할 수 있습니다.<br />등급이 높은 공적패를 사용할 수록<br />획득할 수 있는 경험치는 증가 합니다.<br /><br />[$1]
            okFunc: () => {
                let post_data = { };
                post_data['m_item_pk'] = m_item_pk;
                post_data['hero_pk'] = dialog.data.hero_pk;

                ns_xhr.post('/api/heroSkill/useMedal', post_data, function(_data, _status)
                {
                    if(! ns_xhr.returnCheck(_data)) {
                        return;
                    }
                    _data = _data['ns_xhr_return']['add_data'];

                    post_data = {};
                    post_data['hero_pk'] = dialog.data.hero_pk;
                    ns_xhr.post('/api/heroSkill/getMyHeroData', post_data, function(_data, _status)
                    {
                        if(! ns_xhr.returnCheck(_data)) {
                            return;
                        }
                        _data = _data['ns_xhr_return']['add_data'];

                        ns_dialog.dialogs.hero_skill_manage.data = _data;
                        ns_dialog.dialogs.hero_skill_manage.drawList();
                    });

                    // ns_dialog.setDataOpen('message', system_text.message.use_medal_result);
                    ns_dialog.setDataOpen('message', ns_i18n.t('msg_use_medal_complete', [_data])); // 해당 영웅의 기술 경험치를 {{1}} 획득하였습니다.
                    ns_dialog.close('hero_medal_prize_list');
                }, { useProgress: true });
            }
        });
    } catch (e) {
        console.log(e);
    }

}

/*******************************************************************/
ns_dialog.dialogs.hero_skill_manage_list = new nsDialogSet('hero_skill_manage_list', 'dialog_full', 'size-full', { do_close_all: false });
ns_dialog.dialogs.hero_skill_manage_list.current_page = null;
ns_dialog.dialogs.hero_skill_manage_list.total_page = null;
ns_dialog.dialogs.hero_skill_manage_list.sorted = null;
ns_dialog.dialogs.hero_skill_manage_list.pdata = null;

ns_dialog.dialogs.hero_skill_manage_list.cacheContents = function()
{
    this.cont_obj.content_current_page = new nsObject('.content_current_page', this.obj);
    this.cont_obj.content_total_page = new nsObject('.content_total_page', this.obj);

    this.cont_obj.content_hero_manage_table = new nsObject('.content_hero_manage_table', this.obj);

    this.cont_obj.content_selected_hero_count = new nsObject('.content_selected_hero_count', this.obj);
    this.cont_obj.content_max_selected_hero_count = new nsObject('.content_max_selected_hero_count', this.obj);
}

ns_dialog.dialogs.hero_skill_manage_list.draw = function()
{
    if (! this.visible) {
        ns_button.toggleGroupSingle(ns_button.buttons.hero_skill_manage_list_tab_officer);

        this.current_page = 1;

        ns_select_box.set('hero_skill_manage_list_sort', 'rare', 'desc');
        ns_button.buttons.hero_skill_manage_list_sort.obj.text(ns_select_box.getText('hero_skill_manage_list_sort'));

        this.drawColumn();
    }

    this.drawTab();
}

ns_dialog.dialogs.hero_skill_manage_list.drawColumn = function()
{

    let dialog = ns_dialog.dialogs.hero_skill_manage_list;
    let tab_sub = ns_button.toggleGroupValue('hero_skill_manage_list_tab')[0].split('_tab_').pop();
    if (tab_sub !== dialog.current_tab_sub) {
        dialog.cont_obj.content_hero_manage_table.removeCss(dialog.current_tab_sub);
        dialog.cont_obj.content_hero_manage_table.addCss('content_hero_manage_table');
        dialog.cont_obj.content_hero_manage_table.addCss('ns_table_common');
        dialog.cont_obj.content_hero_manage_table.addCss(tab_sub);
        dialog.current_tab_sub = tab_sub;
    }
}

ns_dialog.dialogs.hero_skill_manage_list.drawTab = function()
{
    if (ns_dialog.dialogs.hero_skill_manage.data) {
        this.cont_obj.content_selected_hero_count.text(1); // 데이터가 존재하면 영웅이 선택된 것
    }

    this.drawList();
}

ns_dialog.dialogs.hero_skill_manage_list.drawList = function()
{
    let dialog = ns_dialog.dialogs.hero_skill_manage_list;
    let post_data = { };
    post_data['action'] = 'my_hero_idle';
    post_data['page_num'] = dialog.current_page;
    let select_box = ns_select_box.get('hero_skill_manage_list_sort');
    if (select_box) {
        post_data['order_by'] = select_box.val;
        post_data['order_type'] = select_box.sort;
    }
    ns_xhr.post('/api/heroSkill/myHeroIdle', post_data, dialog.drawListRemote);
}

ns_dialog.dialogs.hero_skill_manage_list.drawListRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.hero_skill_manage_list;

    dialog.current_page = _data.curr_page;
    dialog.total_page = _data.total_page;
    dialog.cont_obj.content_current_page.text(dialog.current_page);
    dialog.cont_obj.content_total_page.text(dialog.total_page);

    // 목록 리스트 캐싱
    dialog.sorted = [];

    dialog.setPageView(_data.total_count);

    let tbody = dialog.cont_obj.content_hero_manage_table.find('tbody');
    dialog.buttonClear();
    tbody.empty();

    for (let d of Object.values(_data.hero_list)) {
        let m_hero = ns_cs.m.hero[d.m_hero_pk];
        let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];
        let selected_class = '';

        let tr = document.createElement('tr');
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
        button.setAttribute('id', `ns_button_hero_skill_manage_list_select_${d.hero_pk}`);
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


        let col9 = document.createElement('td');
        col9.setAttribute('class', 'cont_filter_officer');

        let col10 = document.createElement('td');
        col10.setAttribute('class', 'cont_filter_officer');

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

        col = document.createElement('td');
        col.classList.add('filter_officer');
        let open_slot_cnt = 0;
        for (let [_k, _d] of Object.entries(ns_cs.m.hero_skil_exp)) {
            if (ns_util.math(d.skill_exp).lt(_d.exp)) {
                break;
            }
            open_slot_cnt++;
        }
        col.innerText = String(open_slot_cnt);
        columns.push(col);

        // 현황
        col = document.createElement('td');
        col.classList.add('filter_officer');
        col.innerText = (! d?.territory_title) ? '-' : d.territory_title;
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
            col.innerText = ns_i18n.t('before_appoint'); // 등용 대기
            if (ns_dialog.dialogs.hero_manage.isOwnHero(_data.appo_heroes_m, ns_cs.m.hero[d.m_hero_pk].m_hero_base_pk)) {
                tr.classList.remove(...tr.classList);
                tr.classList.add('text_gray');
            }
        } else if (d.status === 'A') {
            col.innerText = d.status_text;
        } else {
            col.innerText = ns_i18n.t('before_recruitment'); // 영입 대기
            // left_time - TODO timer 돌려야 하지 않을까?
            let remain_time = d.timedjob_dt_ut - ns_timer.now();
            col.innerText = (remain_time <= 0) ? ns_i18n.t('in_progress') : ns_util.getCostsTime(remain_time);
        }
        /*if (m_hero.over_type === 'Y') {
            col.innerHTML += '<br />오버랭크';
        }*/
        columns.push(col);

        for (let col of columns) {
            tr.appendChild(col);
        }

        tbody.append(tr);

        let button_id = `hero_skill_manage_list_select_${d.hero_pk}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'hero_skill_manage_list');
        ns_button.buttons[button_id].mouseUp = function ()
        {
            ns_dialog.setData('hero_skill_manage', d);
            ns_dialog.dialogs.hero_skill_manage.drawTab();
            ns_dialog.close('hero_skill_manage_list');
        }
        dialog.buttons.push(ns_button.buttons[button_id]);
    }
}

ns_dialog.dialogs.hero_skill_manage_list.setPageView = function(total_count)
{
    let dialog = ns_dialog.dialogs.hero_skill_manage_list;
    dialog.total_page = 0;
    if (ns_util.math(total_count).gt(0)) {
        dialog.total_page = ns_util.math(total_count).div(15).integer;
        dialog.total_page = ns_util.math(dialog.total_page).plus(ns_util.math(total_count).mod(8).gt(0) ? 1 : 0).integer
    } else {
        dialog.total_page = 1;
        dialog.current_page = 1;
    }
    dialog.cont_obj.content_current_page.text(dialog.current_page);
    dialog.cont_obj.content_total_page.text(dialog.total_page);
}

/* ************************************************** */

ns_button.buttons.hero_skill_manage_list_close = new nsButtonSet('hero_skill_manage_list_close', 'button_back', 'hero_skill_manage_list', {base_class:ns_button.buttons.common_close});
ns_button.buttons.hero_skill_manage_list_sub_close = new nsButtonSet('hero_skill_manage_list_sub_close', 'button_full', 'hero_skill_manage_list', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.hero_skill_manage_list_close_all = new nsButtonSet('hero_skill_manage_list_close_all', 'button_close_all', 'hero_skill_manage_list', {base_class:ns_button.buttons.common_close_all});

/* ********** */

ns_button.buttons.hero_skill_manage_list_tab_stat = new nsButtonSet('hero_skill_manage_list_tab_stat', 'button_tab_sub', 'hero_skill_manage_list', { toggle_group: 'hero_skill_manage_list_tab'});
ns_button.buttons.hero_skill_manage_list_tab_stat.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_skill_manage_list;

    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);

    dialog.drawColumn();

    dialog.page_curr = 1;
    dialog.drawTab();
}
ns_button.buttons.hero_skill_manage_list_tab_officer = new nsButtonSet('hero_skill_manage_list_tab_officer', 'button_tab_sub', 'hero_skill_manage_list', { base_class: ns_button.buttons.hero_skill_manage_list_tab_stat, toggle_group: 'hero_skill_manage_list_tab'});
ns_button.buttons.hero_skill_manage_list_tab_army = new nsButtonSet('hero_skill_manage_list_tab_army', 'button_tab_sub', 'hero_skill_manage_list', { base_class: ns_button.buttons.hero_skill_manage_list_tab_stat, toggle_group: 'hero_skill_manage_list_tab'});

ns_button.buttons.hero_skill_manage_list_prev = new nsButtonSet('hero_skill_manage_list_prev', 'button_page_prev', 'hero_skill_manage_list');
ns_button.buttons.hero_skill_manage_list_prev.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_skill_manage_list;

    dialog.current_page--;
    if (dialog.current_page < 1) {
        dialog.current_page = dialog.total_page;
    }

    dialog.drawTab();
}

ns_button.buttons.hero_skill_manage_list_next = new nsButtonSet('hero_skill_manage_list_next', 'button_page_next', 'hero_skill_manage_list');
ns_button.buttons.hero_skill_manage_list_next.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_skill_manage_list;

    dialog.current_page++;
    if (dialog.current_page > dialog.total_page) {
        dialog.current_page = 1;
    }

    dialog.drawTab();
}

ns_button.buttons.hero_skill_manage_list_sort = new nsButtonSet('hero_skill_manage_list_sort', 'button_select_box', 'hero_skill_manage_list');
ns_button.buttons.hero_skill_manage_list_sort.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('select_box', {select_box_id: 'hero_skill_manage_list_sort'});
}