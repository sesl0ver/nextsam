ns_dialog.dialogs.resource_manage = new nsDialogSet('resource_manage', 'dialog_building', 'size-large');
ns_dialog.dialogs.resource_manage.current_tab = null;

ns_dialog.dialogs.resource_manage.cacheContents = function ()
{
    this.cont_obj.resource_item_list_wrap = new nsObject('.resource_item_list_wrap', this.obj);
    this.cont_obj.resource_item_list = new nsObject('.resource_item_list', this.obj);

    this.cont_obj.resource_manage_current = new nsObject('.resource_manage_current', this.obj);
    this.cont_obj.resource_manage_remaining = new nsObject('.resource_manage_remaining', this.obj);

    this.cont_obj.item_box_skeleton = new nsObject('#item_box_skeleton');
}

ns_dialog.dialogs.resource_manage.draw = function ()
{
    let tab = (! this.data?.type) ? 'food' : this.data.type;
    ns_button.toggleGroupSingle(ns_button.buttons[`resource_manage_tab_${tab}`]);
    this.drawTab(tab);
}

ns_dialog.dialogs.resource_manage.drawTab = function (_type)
{
    let dialog = ns_dialog.dialogs.resource_manage;
    dialog.current_tab = _type;

    dialog.buttonClear();
    dialog.cont_obj.resource_item_list.empty();
    let items = Object.values(ns_cs.m.item).filter(o => [_type].includes(o?.supply_amount?.split(':')[0] ?? '')).map(o => o.m_item_pk);
    let resource_item_list = [];
    for (let [_key, i] of Object.entries(ns_cs.d.item)) {
        if (! ns_util.isNumeric(_key) || ! items.includes(i.item_pk)) {
            continue;
        }
        resource_item_list.push(ns_cs.m.item[i.item_pk]);
    }
    if (resource_item_list.length <= 0) {
        const box = document.createElement('span');
        box.classList.add('empty_list_item');
        box.innerText = ns_i18n.t('msg_not_have_resource_item'); // 사용 가능한 자원 아이템을 보유하고 있지 않습니다.
        dialog.cont_obj.resource_item_list.append(box);
    } else {
        // 정렬!
        resource_item_list.sort((a, b) => a.supply_amount.split(':')[1] - b.supply_amount.split(':')[1]);
        for (let i of resource_item_list) {
            i.item_cnt = ns_cs.d.item[i.m_item_pk]?.item_cnt ?? 0;
            const box = dialog.drawItemBox(i);
            box.setAttribute('id', `ns_button_resource_manage_${i.m_item_pk}`);
            dialog.cont_obj.resource_item_list.append(box);
            let button_id = `resource_manage_${i.m_item_pk}`;
            ns_button.buttons[button_id] = new nsButtonSet(button_id, null, 'resource_manage');
            ns_button.buttons[button_id].mouseUp = function ()
            {
                ns_dialog.setDataOpen('item_use', { m_item_pk: i.m_item_pk });
            }
            dialog.buttons.push(ns_button.buttons[button_id]);
        }
    }

    dialog.scroll_handle.initScroll();
}

ns_dialog.dialogs.resource_manage.drawItemBox = function (_data)
{
    let dialog = ns_dialog.dialogs.resource_manage;
    let box = dialog.cont_obj.item_box_skeleton.clone();
    if (_data) {
        box.find('.item_title').text(_data.title);
        box.find('.item_image').addCss(`item_image_${_data.m_item_pk}`);
        box.find('.item_count').text(`${ns_i18n.t('current_storage')}: ${_data.item_cnt}`);
        box.find('.item_price').remove();
    }
    return box;
}

ns_dialog.dialogs.resource_manage.timerHandler = function (_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.resource_manage.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.resource_manage.timerHandlerReal = function ()
{
    let dialog = ns_dialog.dialogs.resource_manage;
    if (! dialog.current_tab) {
        return;
    }

    let current = (dialog.current_tab === 'gold') ? ns_cs.getTerritoryInfo('gold_curr') : ns_cs.getResourceInfo(`${dialog.current_tab}_curr`);
    dialog.cont_obj.resource_manage_current.text(ns_util.numberFormat(current));

    let max = (dialog.current_tab === 'gold') ? 999999999 : ns_cs.getResourceInfo(`${dialog.current_tab}_max`);
    let remaining = ns_util.math(max).minus(current).number;
    dialog.cont_obj.resource_manage_remaining.text(ns_util.numberFormat(remaining));
}

ns_button.buttons.resource_manage_close = new nsButtonSet('resource_manage_close', 'button_back', 'resource_manage', { base_class: ns_button.buttons.common_close });
ns_button.buttons.resource_manage_sub_close = new nsButtonSet('resource_manage_sub_close', 'button_full', 'resource_manage', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.resource_manage_tab_gold = new nsButtonSet('resource_manage_tab_gold', 'button_tab', 'resource_manage', { toggle_group:'resource_manage_tab' });
ns_button.buttons.resource_manage_tab_gold.mouseUp = function ()
{
    let type = this.tag_id.split('_tab_').pop();
    let dialog = ns_dialog.dialogs.resource_manage;
    ns_button.toggleGroupSingle(this);
    dialog.drawTab(type);
}
ns_button.buttons.resource_manage_tab_food = new nsButtonSet('resource_manage_tab_food', 'button_tab', 'resource_manage', { base_class: ns_button.buttons.resource_manage_tab_gold, toggle_group:'resource_manage_tab' });
ns_button.buttons.resource_manage_tab_horse = new nsButtonSet('resource_manage_tab_horse', 'button_tab', 'resource_manage', { base_class: ns_button.buttons.resource_manage_tab_gold, toggle_group:'resource_manage_tab' });
ns_button.buttons.resource_manage_tab_lumber = new nsButtonSet('resource_manage_tab_lumber', 'button_tab', 'resource_manage', { base_class: ns_button.buttons.resource_manage_tab_gold, toggle_group:'resource_manage_tab' });
ns_button.buttons.resource_manage_tab_iron = new nsButtonSet('resource_manage_tab_iron', 'button_tab', 'resource_manage', { base_class: ns_button.buttons.resource_manage_tab_gold, toggle_group:'resource_manage_tab' });
