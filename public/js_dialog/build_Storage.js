// 다이얼로그
ns_dialog.dialogs.build_Storage = new nsDialogSet('build_Storage', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });

ns_dialog.dialogs.build_Storage.cacheContents = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.cacheContents.call(this, true);
    }

    this.cont_obj.space_capacity = new nsObject('.space_capacity', this.obj);
    this.cont_obj.space_free = new nsObject('.space_free', this.obj);

    this.cont_obj.tbody = new nsObject('.ns_table_common tbody', this.obj);
}

ns_dialog.dialogs.build_Storage.draw = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.draw.call(this, true);
    }

    this.cont_obj.space_capacity.text(0);
    this.cont_obj.space_free.text(0);

    for (let _type of ['food', 'horse', 'lumber', 'iron']) {
        this.cont_obj.tbody.find(`.ns_resource_${_type}_curr`).text(0);
        this.cont_obj.tbody.find(`.ns_resource_${_type}_max`).text(0);
        ns_button.buttons[`build_Storage_${_type}_pct`].obj.text(ns_cs.getTerritoryInfo(`storage_${_type}_pct`));
    }
}

ns_dialog.dialogs.build_Storage.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';
    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_Storage.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_Storage.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.build_Storage;

    dialog.cont_obj.space_capacity.text(ns_util.numberFormat(ns_cs.getTerritoryInfo('storage_max')));
    dialog.cont_obj.space_free.text(ns_util.math(ns_cs.getTerritoryInfo('storage_max')).minus(ns_cs.getResourceInfo('food_curr')).minus(ns_cs.getResourceInfo('horse_curr')).minus(ns_cs.getResourceInfo('lumber_curr')).minus(ns_cs.getResourceInfo('iron_curr')).number_format);

    for (let _type of ['food', 'horse', 'lumber', 'iron']) {
        dialog.cont_obj.tbody.find(`.ns_resource_${_type}_curr`).text(ns_util.numberFormat(ns_cs.getResourceInfo(`${_type}_curr`)));
        dialog.cont_obj.tbody.find(`.ns_resource_${_type}_max`).text(ns_util.numberFormat(ns_cs.getResourceInfo(`${_type}_max`)));
    }
}

ns_button.buttons.build_Storage_close = new nsButtonSet('build_Storage_close', 'button_back', 'build_Storage', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_Storage_sub_close = new nsButtonSet('build_Storage_sub_close', 'button_full', 'build_Storage', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.build_Storage_close_all = new nsButtonSet('build_Storage_close_all', 'button_close_all', 'build_Storage', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_Storage = new nsButtonSet('build_desc_Storage', 'button_text_style_desc', 'build_Storage', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_move_Storage = new nsButtonSet('build_move_Storage', 'button_middle_2', 'build_Storage', { base_class: ns_button.buttons.build_move });
ns_button.buttons.build_cons_Storage = new nsButtonSet('build_cons_Storage', 'button_multi', 'build_Storage', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_Storage = new nsButtonSet('build_upgrade_Storage', 'button_hero_action', 'build_Storage', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_assign_Storage = new nsButtonSet('build_assign_Storage', 'button_full', 'build_Storage', { base_class: ns_button.buttons.build_assign });
ns_button.buttons.build_no_assign_Storage = new nsButtonSet('build_no_assign_Storage', 'button_full', 'build_Storage', { base_class: ns_button.buttons.build_assign });

ns_button.buttons.build_prev_Storage = new nsButtonSet('build_prev_Storage', 'button_multi_prev', 'build_Storage', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_Storage = new nsButtonSet('build_next_Storage', 'button_multi_next', 'build_Storage', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_Storage = new nsButtonSet('build_speedup_Storage', 'button_encourage', 'build_Storage', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_Storage = new nsButtonSet('build_cancel_Storage', 'button_build', 'build_Storage', { base_class: ns_button.buttons.build_cancel });

ns_button.buttons.build_Storage_food_pct =  new nsButtonSet('build_Storage_food_pct', 'button_input', 'build_Storage');
ns_button.buttons.build_Storage_food_pct.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Storage;
    let arr = this.tag_id.split('_');
    let type = arr[2];

    let max_value = ns_util.math(100).minus(ns_button.buttons['build_Storage_food_pct'].obj.text())
        .minus(ns_button.buttons['build_Storage_horse_pct'].obj.text())
        .minus(ns_button.buttons['build_Storage_lumber_pct'].obj.text())
        .minus(ns_button.buttons['build_Storage_iron_pct'].obj.text())
        .plus(ns_button.buttons[`build_Storage_${type}_pct`].obj.text()).integer;

    ns_dialog.setDataOpen('keypad', { max: max_value, min: 0, current:ns_button.buttons[`build_Storage_${type}_pct`].obj.text(),
        callback: function (data){
            ns_button.buttons[`build_Storage_${type}_pct`].obj.text(data);
        }
    });
}
ns_button.buttons.build_Storage_horse_pct =  new nsButtonSet('build_Storage_horse_pct', 'button_input', 'build_Storage', { base_class: ns_button.buttons.build_Storage_food_pct });
ns_button.buttons.build_Storage_lumber_pct =  new nsButtonSet('build_Storage_lumber_pct', 'button_input', 'build_Storage', { base_class: ns_button.buttons.build_Storage_food_pct });
ns_button.buttons.build_Storage_iron_pct =  new nsButtonSet('build_Storage_iron_pct', 'button_input', 'build_Storage', { base_class: ns_button.buttons.build_Storage_food_pct });

ns_button.buttons.build_Storage_rate = new nsButtonSet('build_Storage_rate', 'button_special', 'build_Storage');
ns_button.buttons.build_Storage_rate.mouseUp = function(_e)
{
    let post_data = {};
    post_data['food'] = ns_button.buttons['build_Storage_food_pct'].obj.text();
    post_data['horse'] = ns_button.buttons['build_Storage_horse_pct'].obj.text();
    post_data['lumber'] = ns_button.buttons['build_Storage_lumber_pct'].obj.text();
    post_data['iron'] = ns_button.buttons['build_Storage_iron_pct'].obj.text();

    ns_xhr.post('/api/storage/update', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_storage_save_complete')); // <strong>할당 비율이 변경되었습니다.</strong>
    }, { useProgress: true });
}