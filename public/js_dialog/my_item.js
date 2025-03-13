// 다이얼로그
ns_dialog.dialogs.my_item = new nsDialogSet('my_item', 'dialog_full', 'size-full');
ns_dialog.dialogs.my_item.current_tab = null;
ns_dialog.dialogs.my_item.current_type = null;
// ns_dialog.dialogs.my_item.item_list_array = { production: [], speedup: [], lord: [], special: [], package: [], hero: [], skill: [] }
ns_dialog.dialogs.my_item.item_list_array = []
ns_dialog.dialogs.my_item.item_buttons = [];
ns_dialog.dialogs.my_item.tab_types = { production: 'P', speedup: 'S', lord: 'L', special: 'D', package: 'B', hero: 'H' };
ns_dialog.dialogs.my_item.tab_first_clicked = { production: false, speedup: false, lord: false, special: false, package: false, hero: false, skill: false };
ns_dialog.dialogs.my_item.new_item_flag = { 'P': false, 'S': false, 'L': false, 'D': false, 'B': false, 'H': false };

ns_dialog.dialogs.my_item.cacheContents = function (e)
{
    this.cont_obj.content_item_list_wrap = new nsObject('.content_item_list_wrap', this.obj);

    this.cont_obj.cont_my_item_flag_production = new nsObject('.cont_my_item_flag_production', this.obj);
    this.cont_obj.cont_my_item_flag_speedup = new nsObject('.cont_my_item_flag_speedup', this.obj);
    this.cont_obj.cont_my_item_flag_lord = new nsObject('.cont_my_item_flag_lord', this.obj);
    this.cont_obj.cont_my_item_flag_special = new nsObject('.cont_my_item_flag_special', this.obj);
    this.cont_obj.cont_my_item_flag_package = new nsObject('.cont_my_item_flag_package', this.obj);
    this.cont_obj.cont_my_item_flag_hero = new nsObject('.cont_my_item_flag_hero', this.obj);

    this.cont_obj.item_box_skeleton = new nsObject('#item_box_skeleton');
}

ns_dialog.dialogs.my_item.draw = function()
{
    ns_button.toggleGroupSingle(ns_button.buttons.my_item_tab_production);
    this.drawTab();
}

ns_dialog.dialogs.my_item.erase = function()
{
    let dialog = ns_dialog.dialogs.my_item;
    for (let _button of dialog.item_buttons) {
        _button.destroy();
    }
    if (ns_dialog.dialogs.item_use.visible) {
        ns_dialog.close('item_use');
    }
}

ns_dialog.dialogs.my_item.drawTab = function()
{
    let dialog = ns_dialog.dialogs.my_item;
    // 열려있는 아이템 사용 창 닫기
    if (ns_dialog.dialogs.item_use.visible) {
        ns_dialog.close('item_use');
    }

    let tab = ns_button.toggleGroupValue('my_item_tab')[0].split('_').pop();
    let type = dialog.tab_types[tab];


    ns_button.buttons[`my_item_tab_${tab}`].obj.removeCss('tab_flag_new');
    window.localStorage.removeItem(`unread_item_${type}`);

    dialog.current_tab = tab;
    dialog.current_type = type;

    dialog.drawList(tab, type);

    dialog.checkNew();
    // 소지 갯수 갱신
    dialog.timerHandlerReal();
}

ns_dialog.dialogs.my_item.checkNew = function()
{
    let unread_count = 0;
    let __tabs = { 'P': 'production', 'S': 'speedup', 'L': 'lord', 'D': 'special', 'B': 'package', 'H': 'hero' };
    for (let [_type, _tab] of Object.entries(__tabs)){
        if (! window.localStorage.getItem(`unread_item_${_type}`)) {
            ns_button.buttons[`my_item_tab_${_tab}`].obj.removeCss('tab_flag_new');
        } else {
            ns_button.buttons[`my_item_tab_${_tab}`].obj.addCss('tab_flag_new');
            unread_count++;
        }
    }

    if (unread_count > 0) {
        ns_button.buttons.main_my_item.obj.addCss('main_flag_new');
    } else {
        ns_button.buttons.main_my_item.obj.removeCss('main_flag_new');
    }
}

ns_dialog.dialogs.my_item.drawItemBox = function (_data)
{
    let dialog = ns_dialog.dialogs.my_item;
    let box = dialog.cont_obj.item_box_skeleton.clone();
    if (_data) {
        box.find('.item_title').text(_data.title);
        box.find('.item_image').addCss(`item_image_${_data.m_item_pk}`);
        box.find('.item_count').html(`${ns_i18n.t('current_storage')}: <span class="item_amount">${_data.item_cnt}</span>`);
    } else {
        box.text('');
        box.addCss('empty');
    }
    return box;
}

ns_dialog.dialogs.my_item.drawList = function(_tab, _type)
{
    let dialog = ns_dialog.dialogs.my_item;
    // if (dialog[`tab_first_clicked_${_tab}`]) {
    //     return;
    // }

    for (let _button of dialog.item_buttons) {
        _button.destroy();
    }

    dialog.item_list_array = [];
    dialog.item_buttons = [];


    // 아이템 정렬
    for (let [k, d] of Object.entries(ns_cs.d.item)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        try {
            let m = ns_cs.m.item[k];
            if (_type === m.display_type && d.item_cnt > 0) {
                dialog.item_list_array.push( { m_item_pk: k, item_cnt: d.item_cnt, orderno: m.orderno, title: m.title} );
            }
        } catch (e) {
            console.error(e);
        }
    }

    dialog.item_list_array = ns_util.arraySort(dialog.item_list_array, 1, 'orderno');

    dialog.cont_obj.content_item_list_wrap.empty();

    for (let [k, d] of Object.entries(dialog.item_list_array)) {
        const box = dialog.drawItemBox(d);
        box.setAttribute('id', `ns_button_my_item_${d.m_item_pk}`);
        dialog.cont_obj.content_item_list_wrap.append(box);

        ns_button.buttons[`my_item_${d.m_item_pk}`] = new nsButtonSet(`my_item_${d.m_item_pk}`, 'button_empty', 'my_item');
        ns_button.buttons[`my_item_${d.m_item_pk}`].mouseUp = function ()
        {
            ns_dialog.setDataOpen('item_use', { m_item_pk: d.m_item_pk });
        }
        dialog.item_buttons.push(ns_button.buttons[`my_item_${d.m_item_pk}`]);
    }

    let max_list = 8, item_cnt = dialog.item_list_array.length;
    let dummy_count = max_list - item_cnt;
    if (dummy_count > 0) {
        for (let i = 0; i < dummy_count; i++) {
            const box = dialog.drawItemBox(false);
            dialog.cont_obj.content_item_list_wrap.append(box);
        }
    }

    dialog.tab_first_clicked[_tab] = true;
}

ns_dialog.dialogs.my_item.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';
    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.my_item.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();
    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.my_item.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.my_item;

    for (let _item of dialog.item_list_array) {
        let item = ns_cs.d.item[_item.m_item_pk];
        if(item && item.item_pk === _item.m_item_pk) {
            if (ns_button.buttons[`my_item_${item.item_pk}`]) {
                ns_button.buttons[`my_item_${item.item_pk}`].obj.find('.item_amount').text(item.item_cnt);
            }
        }
    }
}

ns_button.buttons.my_item_close = new nsButtonSet('my_item_close', 'button_back', 'my_item', { base_class: ns_button.buttons.common_close});
ns_button.buttons.my_item_sub_close = new nsButtonSet('my_item_sub_close', 'button_full', 'my_item', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.my_item_close_all = new nsButtonSet('my_item_close_all', 'button_close_all', 'my_item', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.my_item_tab_production = new nsButtonSet('my_item_tab_production', 'button_tab', 'my_item', { toggle_group: 'my_item_tab' });
ns_button.buttons.my_item_tab_production.mouseUp = function(_e)
{
    ns_button.toggleGroupSingle(this);
    ns_dialog.dialogs.my_item.scroll_handle.initScroll();
    ns_dialog.dialogs.my_item.drawTab();
}
ns_button.buttons.my_item_tab_speedup = new nsButtonSet('my_item_tab_speedup', 'button_tab', 'my_item', { base_class: ns_button.buttons.my_item_tab_production, toggle_group: 'my_item_tab' });
ns_button.buttons.my_item_tab_lord = new nsButtonSet('my_item_tab_lord', 'button_tab', 'my_item', { base_class: ns_button.buttons.my_item_tab_production, toggle_group: 'my_item_tab' });
ns_button.buttons.my_item_tab_special = new nsButtonSet('my_item_tab_special', 'button_tab', 'my_item', { base_class: ns_button.buttons.my_item_tab_production, toggle_group: 'my_item_tab' });
ns_button.buttons.my_item_tab_package = new nsButtonSet('my_item_tab_package', 'button_tab', 'my_item', { base_class: ns_button.buttons.my_item_tab_production, toggle_group: 'my_item_tab' });
ns_button.buttons.my_item_tab_hero = new nsButtonSet('my_item_tab_hero', 'button_tab', 'my_item', { base_class: ns_button.buttons.my_item_tab_production, toggle_group: 'my_item_tab' });
// ns_button.buttons.my_item_tab_skill = new nsButtonSet('my_item_tab_skill', 'button_tab', 'my_item', { base_class: ns_button.buttons.my_item_tab_production, toggle_group: 'my_item_tab' });

ns_button.buttons.my_item_item = new nsButtonSet('my_item_item', 'button_middle_2', 'my_item');
ns_button.buttons.my_item_item.mouseUp = function(_e)
{
    ns_dialog.close('my_item');
    ns_dialog.open('item');
}

ns_button.buttons.my_item_qbig = new nsButtonSet('my_item_qbig', 'button_empty', 'my_item');
ns_button.buttons.my_item_qbig.mouseUp = function(_e)
{
    ns_engine.buyQbig();
}

ns_button.buttons.my_item_hero_skill = new nsButtonSet('my_item_hero_skill', 'button_middle_2', 'my_item');
ns_button.buttons.my_item_hero_skill.mouseUp = function(_e)
{
    ns_xhr.post('/api/heroSkill/mySkillBoxList', {}, function (_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];
        if (_data) {
            ns_dialog.setDataOpen('hero_skill_box_list', { my_hero_skil_box_pk: _data.my_hero_skil_box_pk, m_item_pk: _data.m_item_pk, skill_list: _data.skill_list });
        } else {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_hero_skill_box_not_exist')); // 미선택 기술 상자가 존재하지 않습니다.
        }
    })
}