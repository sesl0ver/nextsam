ns_dialog.dialogs.terr_info = new nsDialogSet('terr_info', 'dialog_full', 'size-full');
ns_dialog.dialogs.tick_data = {};
ns_dialog.dialogs.tick_count = 0;
ns_dialog.dialogs.terr_info.current_tab = null;
ns_dialog.dialogs.terr_info.cacheContents = function()
{
    this.cont_obj.dialog_title = new nsObject('.content_title', this.obj);
    this.cont_obj.dialog_title.text(ns_i18n.t('buff_resource_information')); // 버프/자원 정보

    this.cont_obj.buff_list_wrap =  new nsObject('.buff_list_wrap', this.obj);
    this.cont_obj.terr_population_description =  new nsObject('.terr_population_description', this.obj);

    this.cont_obj.terr_info_tab_goldpop =  new nsObject('.terr_info_tab_goldpop', this.obj);
    this.cont_obj.terr_info_tab_goldpop.hide();

    this.cont_obj.population_tbody =  new nsObject('.population_tbody', this.obj);
    this.cont_obj.gold_tbody =  new nsObject('.gold_tbody', this.obj);

    this.cont_obj.terr_info_tab_resource =  new nsObject('.terr_info_tab_resource', this.obj);
    this.cont_obj.terr_info_tab_resource.hide();

    this.cont_obj.food_tbody =  new nsObject('.food_tbody', this.obj);
    this.cont_obj.horse_tbody =  new nsObject('.horse_tbody', this.obj);
    this.cont_obj.lumber_tbody =  new nsObject('.lumber_tbody', this.obj);
    this.cont_obj.iron_tbody =  new nsObject('.iron_tbody', this.obj);

    this.cont_obj.terr_info_buff_box_skeleton =  new nsObject('#terr_info_buff_box_skeleton');

    ns_cs.flag['new_time'] = false;
}
ns_dialog.dialogs.terr_info.draw = function()
{
    //도움말 관련하여 추가 - 첫 접속시 무조건 한번은 보여주도록
    /*let help_type = 'TerrInfo';
    if (!window.localStorage.getItem('open_help_' + help_type))
    {
        qbw_dlg.setDataOpen('game_help', {'type':help_type});
        window.localStorage.setItem('open_help_' + help_type, 'Y');
    }*/

    this.drawBuff();
    ns_button.toggleGroupSingle(ns_button.buttons.terr_info_tab_goldpop);
    this.drawTab();
}

ns_dialog.dialogs.terr_info.erase = function()
{
    ns_dialog.close('buff_item_desc');
    this.data = null;
}

ns_dialog.dialogs.terr_info.drawBuff = function()
{
    let dialog = ns_dialog.dialogs.terr_info;

    dialog.tick_data = {};
    dialog.tick_count = 0;
    dialog.buttonClear();
    dialog.cont_obj.buff_list_wrap.empty();

    dialog.cont_obj.terr_population_description.hide();

    // 성문이 열려있다면 버프 추가
    if (ns_cs.d.terr.status_gate.v === 'O') {
        dialog.tick_count++;

        let box = dialog.cont_obj.terr_info_buff_box_skeleton.clone();
        box.find('.buff_icon').addCss('buff_500054');
        box.find('.button_empty').setAttribute('id', 'ns_button_item_buff_gate');

        box.find('.buff_description').text(ns_i18n.t('open_gate_effect')); // 성문 개방 효과
        box.find('.buff_left_time').text(ns_i18n.t('until_gate_closed')); // 성문 폐쇄시 까지

        dialog.cont_obj.buff_list_wrap.append(box);


        if (! ns_button.buttons['item_buff_gate']) {
            let gate_data = {};
            gate_data['build_time'] = null;
            gate_data['build_time_reduce'] = null;
            gate_data['description'] = '500522:gate';
            gate_data['end_dt_ut'] = null;
            gate_data['in_cast_pk'] = null;
            gate_data['out_cast_pk'] = null;
            gate_data['posi_pk'] = ns_engine.game_data.cpp;
            gate_data['queue_action'] = 'B';
            gate_data['queue_pk'] = null;
            gate_data['queue_type'] = 'B';
            gate_data['start_dt_ut'] = null;
            gate_data['status'] = 'P';
            ns_button.buttons.item_buff_gate = new nsButtonSet('item_buff_gate', null, 'terr_info', { base_class: ns_button.buttons.buff_desc, data: gate_data });
            dialog.buttons.push(ns_button.buttons.item_buff_gate);
        }
    }

    for (let [k, d] of Object.entries(ns_cs.d.time)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        if (d.queue_action !== 'B' || d.posi_pk !== ns_engine.game_data.cpp) {
            continue;
        }
        dialog.tick_data[k] = d;
        dialog.tick_count++;

        let z = d.description.split(':');

        let box = dialog.cont_obj.terr_info_buff_box_skeleton.clone();
        box.find('.buff_icon').addCss(`buff_${z[1]}`);
        box.find('.button_empty').setAttribute('id', `ns_button_item_buff_${z[1]}`);

        box.find('.buff_description').text(ns_cs.m.item[z[0]].buff_title);
        box.find('.buff_left_time').text('-');
        dialog.tick_data[k].time_left_obj = box.find('.buff_left_time');

        dialog.cont_obj.buff_list_wrap.append(box);

        let button_id = `item_buff_${z[1]}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, null, 'terr_info', { base_class: ns_button.buttons.buff_desc, data: d });
        dialog.buttons.push(ns_button.buttons[button_id]);
    }
}

ns_dialog.dialogs.terr_info.drawTab = function()
{
    let dialog = ns_dialog.dialogs.terr_info;
    let tab = ns_button.toggleGroupValue('terr_info_list')[0].split('_tab_').pop();
    if (dialog.current_tab) {
        dialog.cont_obj[`terr_info_tab_${dialog.current_tab}`].hide();
    }
    dialog.cont_obj[`terr_info_tab_${tab}`].show();
    dialog.current_tab = tab;
}

ns_dialog.dialogs.terr_info.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.terr_info.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.terr_info.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.terr_info;

    if (ns_cs.flag['new_time']) {
        ns_cs.flag['new_time'] = false;
        dialog.drawTab();
        dialog.drawBuff();
        return;
    }

    for (let [k, d] of Object.entries(dialog.tick_data)) {
        let z = d.end_dt_ut - ns_timer.now();
        d.time_left_obj.html((z < 1) ? ns_i18n.t('in_progress') : ns_i18n.t('time_left', [ns_util.getCostsTime(z)]));
    }

    let tab = ns_button.toggleGroupValue('terr_info_list')[0].split('_').pop();

    if (tab === 'goldpop') {
        // 민심
        dialog.cont_obj.population_tbody.find('.ns_terr_loyalty').text(ns_cs.getTerritoryInfo('loyalty'));

        // 민심변화
        let loyalty_trend_amount = 100 - ns_cs.getTerritoryInfo('tax_rate') - ns_cs.getTerritoryInfo('loyalty');
        let loyalty_trend = dialog.cont_obj.population_tbody.find('.ns_terr_loyalty_trend');
        let trend_description = '';
        if (loyalty_trend_amount > 0) {
            trend_description = ns_i18n.t('growing');
            loyalty_trend.removeCss('text_red');
            loyalty_trend.addCss('text_green');
        } else if (loyalty_trend_amount < 0) {
            trend_description = ns_i18n.t('declining');
            loyalty_trend.addCss('text_red');
            loyalty_trend.removeCss('text_green');
        } else {
            trend_description = ns_i18n.t('stable');
            loyalty_trend.removeCss('text_red');
            loyalty_trend.removeCss('text_green');
        }
        loyalty_trend.text(trend_description);

        dialog.cont_obj.population_tbody.find('.ns_terr_population_curr').text(ns_util.numberFormat(ns_cs.getTerritoryInfo('population_curr')));

        // 유휴 인구 상태에 따라 텍스트 문구 출력
        if (ns_cs.getTerritoryInfo('population_idle') < 1) {
            dialog.cont_obj.terr_population_description.show();
        }

        dialog.cont_obj.population_tbody.find('.ns_terr_population_idle').text(ns_util.numberFormat(ns_cs.getTerritoryInfo('population_idle')));
        dialog.cont_obj.population_tbody.find('.ns_terr_population_labor_force').text(ns_util.numberFormat(ns_cs.getTerritoryInfo('population_labor_force')));

        // 인구 증감 계산
        let population_trend_amount = 0;
        let terr_population_trend = dialog.cont_obj.population_tbody.find('.ns_terr_population_trend');
        let population_trend_type = ns_cs.getTerritoryInfo('population_trend');

        switch (population_trend_type) {
            case 'U': // U
                terr_population_trend.removeCss('text_red');
                terr_population_trend.addCss('text_green');
                if(ns_cs.getTerritoryInfo('population_upward_plus_amount')) {
                    population_trend_amount = ns_util.math(ns_engine.cfg.population_upward_default).plus(ns_cs.getTerritoryInfo('population_upward_plus_amount')).integer;
                }
                break;
            case 'D': // D
                terr_population_trend.addCss('text_red');
                terr_population_trend.removeCss('text_green');
                population_trend_amount = ns_util.math(ns_engine.cfg.population_upward_default).mul(-1).integer;
                break;
            default: // S
                terr_population_trend.removeCss('text_red');
                terr_population_trend.removeCss('text_green');
                population_trend_amount = 0;
                break;
        }
        terr_population_trend.text(code_set.trend[population_trend_type]);
        if (population_trend_amount < 0) {
            population_trend_amount = 0;
        }

        dialog.cont_obj.population_tbody.find('.ns_terr_population_trend_amount').text(ns_util.numberFormat(population_trend_amount));

        dialog.cont_obj.population_tbody.find('.ns_terr_population_max').text(ns_util.numberFormat(ns_cs.getTerritoryInfo('population_max')));
        dialog.cont_obj.population_tbody.find('.ns_terr_loyalty_effect').text(ns_cs.getTerritoryInfo('loyalty'));
        dialog.cont_obj.population_tbody.find('.ns_terr_population_capacity').text(ns_util.numberFormat(ns_util.math(ns_cs.getTerritoryInfo('population_max')).mul(ns_cs.getTerritoryInfo('loyalty')).mul(0.01).integer));

        dialog.cont_obj.population_tbody.find('.ns_terr_population_max').text(ns_util.numberFormat(ns_cs.getTerritoryInfo('population_max')));

        dialog.cont_obj.population_tbody.find('.ns_terr_population_upward_plus_tech').text(ns_util.numberFormat(ns_cs.getTerritoryInfo('population_upward_plus_tech')));
        dialog.cont_obj.population_tbody.find('.ns_terr_population_upward_plus_hero_assign').text(ns_util.numberFormat(ns_cs.getTerritoryInfo('population_upward_plus_hero_assign')));
        dialog.cont_obj.population_tbody.find('.ns_terr_population_upward_plus_hero_skill').text(ns_util.numberFormat(ns_cs.getTerritoryInfo('population_upward_plus_hero_skill')));

        // // 성문 개방 효과 표기를 위해 성문 버프 만큼 빼줌
        let population_upward_plus_gate = 0;
        let population_upward_plus_item = ns_cs.getTerritoryInfo('population_upward_plus_item');
        if (ns_cs.d.terr.status_gate.v === 'O') {
            population_upward_plus_gate = 300;
            population_upward_plus_item = ns_util.math(population_upward_plus_item).minus(300).integer
        }

        dialog.cont_obj.population_tbody.find('.ns_terr_population_upward_plus_item').text(ns_util.numberFormat(population_upward_plus_item));
        dialog.cont_obj.population_tbody.find('.ns_terr_population_upward_plus_gate').text(population_upward_plus_gate);

        // gold
        dialog.cont_obj.gold_tbody.find('.ns_terr_tax_rate').text(ns_cs.getTerritoryInfo('tax_rate') + '%');
        dialog.cont_obj.gold_tbody.find('.ns_terr_tax_rate_plus_hero_skill').text(ns_cs.getTerritoryInfo('tax_rate_plus_hero_skill') + '%');

        // 성문 개방 효과 표기를 위해 성문 버프 만큼 빼줌
        let tax_rate_plus_gate = 0;
        let tax_rate_plus_item = ns_cs.getTerritoryInfo('tax_rate_plus_item');
        if (ns_cs.d.terr.status_gate.v === 'O') {
            tax_rate_plus_gate = 5;
            tax_rate_plus_item = ns_util.math(tax_rate_plus_item).minus(5).integer;
        }
        dialog.cont_obj.gold_tbody.find('.ns_terr_tax_rate_plus_gate').text(`${tax_rate_plus_gate}%`);
        dialog.cont_obj.gold_tbody.find('.ns_terr_tax_rate_plus_item').text(`${tax_rate_plus_item}%`);

        dialog.cont_obj.gold_tbody.find('.ns_terr_gold_curr').text(ns_util.numberFormat(ns_cs.getTerritoryInfo('gold_curr')));
        dialog.cont_obj.gold_tbody.find('.ns_terr_gold_production').text(ns_util.numberFormat(ns_cs.getTerritoryInfo('gold_production')));
    } else {
        for (let _type of ['food', 'horse', 'lumber', 'iron']) {
            let tbody = dialog.cont_obj[`${_type}_tbody`];
            tbody.find(`.ns_reso_${_type}_curr`).text(ns_util.numberFormat(ns_cs.getResourceInfo(`${_type}_curr`)));
            tbody.find(`.ns_reso_${_type}_max`).text(ns_util.numberFormat(ns_cs.getResourceInfo(`${_type}_max`)));
            tbody.find(`.ns_reso_${_type}_production`).text(ns_util.numberFormat(ns_cs.getResourceInfo(`${_type}_production`)));
            tbody.find(`.ns_prod_${_type}_providence`).text(ns_util.numberFormat(ns_cs.getProdInfo(`${_type}_providence`)));
            tbody.find(`.ns_prod_${_type}_production_territory`).text(ns_util.numberFormat(ns_cs.getProdInfo(`${_type}_production_territory`)));
            tbody.find(`.ns_prod_${_type}_production_valley`).text(ns_util.numberFormat(ns_cs.getProdInfo(`${_type}_production_valley`)));
            tbody.find(`.ns_prod_${_type}_pct_plus_tech`).text(ns_util.numberFormat(ns_cs.getProdInfo(`${_type}_pct_plus_tech`)) + '%');
            tbody.find(`.ns_prod_${_type}_pct_plus_hero_assign`).text(ns_util.numberFormat(ns_cs.getProdInfo(`${_type}_pct_plus_hero_assign`)) + '%');
            tbody.find(`.ns_prod_${_type}_pct_plus_hero_skill`).text(ns_util.numberFormat(ns_cs.getProdInfo(`${_type}_pct_plus_hero_skill`)) + '%');
            tbody.find(`.ns_prod_${_type}_pct_plus_item`).text((ns_cs.d.terr.status_gate.v === 'O') ? ns_util.math(ns_cs.getProdInfo(`${_type}_pct_plus_item`)).minus( 10).number_format + '%' : ns_util.numberFormat(ns_cs.getProdInfo(`${_type}_pct_plus_item`)) + '%');
            tbody.find(`.ns_prod_${_type}_pct_plus_gate`).text((ns_cs.d.terr.status_gate.v === 'O' ? 10 : 0) + '%');
        }
    }
}

/* ************************************************** */
ns_button.buttons.terr_info_close = new nsButtonSet('terr_info_close', 'button_back', 'terr_info', { base_class: ns_button.buttons.common_close });
ns_button.buttons.terr_info_sub_close = new nsButtonSet('terr_info_sub_close', 'button_full', 'terr_info', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.terr_info_close_all = new nsButtonSet('terr_info_close_all', 'button_close_all', 'terr_info', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.game_help_TerrInfo = new nsButtonSet('game_help_TerrInfo', 'btn_dlg_help', 'terr_info', {base_class:ns_button.buttons.buil_help});

ns_button.buttons.terr_info_tab_goldpop = new nsButtonSet('terr_info_tab_goldpop', 'button_tab', 'terr_info', { toggle_group: 'terr_info_list' });
ns_button.buttons.terr_info_tab_goldpop.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.terr_info;

    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.drawTab();
}
ns_button.buttons.terr_info_tab_resource = new nsButtonSet('terr_info_tab_resource', 'button_tab', 'terr_info',  { base_class: ns_button.buttons.terr_info_tab_goldpop, toggle_group: 'terr_info_list' });

ns_button.buttons.terr_info_loyalty_item = new nsButtonSet('terr_info_loyalty_item', 'button_use_buff', 'terr_info');
ns_button.buttons.terr_info_loyalty_item.mouseUp = function(_e)
{
    let regexp = new RegExp('terr_info_([a-z]*)_item');
    let arr = this.tag_id.match(regexp);
    ns_dialog.setDataOpen('item_quick_use', { type: arr[1] });
}

ns_button.buttons.terr_info_tax_item = new nsButtonSet('terr_info_tax_item ', 'button_use_buff', 'terr_info', {base_class:ns_button.buttons.terr_info_loyalty_item});
ns_button.buttons.terr_info_population_item = new nsButtonSet('terr_info_population_item ', 'button_use_buff', 'terr_info', {base_class:ns_button.buttons.terr_info_loyalty_item});
ns_button.buttons.terr_info_gold_item = new nsButtonSet('terr_info_gold_item ', 'button_use_buff', 'terr_info', {base_class:ns_button.buttons.terr_info_loyalty_item});
ns_button.buttons.terr_info_food_item = new nsButtonSet('terr_info_food_item ', 'button_use_buff', 'terr_info', {base_class:ns_button.buttons.terr_info_loyalty_item});
ns_button.buttons.terr_info_horse_item = new nsButtonSet('terr_info_horse_item ', 'button_use_buff', 'terr_info', {base_class:ns_button.buttons.terr_info_loyalty_item});
ns_button.buttons.terr_info_lumber_item = new nsButtonSet('terr_info_lumber_item ', 'button_use_buff', 'terr_info', {base_class:ns_button.buttons.terr_info_loyalty_item});
ns_button.buttons.terr_info_iron_item = new nsButtonSet('terr_info_iron_item ', 'button_use_buff', 'terr_info', {base_class:ns_button.buttons.terr_info_loyalty_item});

ns_button.buttons.buff_desc = new nsButtonSet('buff_desc ', 'button_empty', 'terr_info');
ns_button.buttons.buff_desc.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('buff_item_desc', { detail: this.options.data });
}

/*******************************************************************/
ns_dialog.dialogs.buff_item_desc = new nsDialogSet('buff_item_desc', 'dialog_pop', 'size-medium', { do_content_scroll: false, do_close_all: false });
ns_dialog.dialogs.buff_item_desc.tax_rate_value = null;

ns_dialog.dialogs.buff_item_desc.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);

    this.cont_obj.content_buff_item_desc = new nsObject('.content_buff_item_desc', this.obj);
    this.cont_obj.content_left_time = new nsObject('.content_left_time', this.obj);
}

ns_dialog.dialogs.buff_item_desc.draw = function()
{
    let d = this.data.detail;
    let z = d.description.split(':');

    this.cont_obj.content_pop_title.text(ns_cs.m.item[z[0]].buff_title);
    this.cont_obj.content_buff_item_desc.html(ns_cs.m.item[z[0]].description_buff);
}

ns_dialog.dialogs.buff_item_desc.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.buff_item_desc.timerHandlerReal, 1000, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.buff_item_desc.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.buff_item_desc;

    let d = dialog.data.detail;
    let type = d.description.split(':');
    let z = d.end_dt_ut - ns_timer.now();

    let left_time = (type[1] === 'gate') ? ns_i18n.t('until_gate_closed') : ns_i18n.t('time_left', [ns_util.getCostsTime(z)]);
    dialog.cont_obj.content_left_time.html(left_time);
}
/* ************************************************** */
ns_button.buttons.buff_item_desc_close = new nsButtonSet('buff_item_desc_close', 'button_pop_close', 'buff_item_desc', { base_class: ns_button.buttons.common_close });
ns_button.buttons.buff_item_desc_sub_close = new nsButtonSet('buff_item_desc_sub_close', 'button_full', 'buff_item_desc', { base_class: ns_button.buttons.common_sub_close });

/* ************************************************** */
/*ns_dialog.dialogs.resource = new nsDialogSet('resource', 'dlg_resource', 'resourcelpop', {doCloseAll:false});
ns_dialog.dialogs.resource.open_status = false;

ns_dialog.dialogs.resource.cache_contents = function(_recursive)
{
    this.cont_obj.qbw_terr_population_idle = new nsObject('.qbw_terr_population_idle');
    this.cont_obj.qbw_terr_population_trend_amount = new nsObject('.qbw_terr_population_trend_amount');
    this.cont_obj.qbw_terr_gold_curr = new nsObject('.qbw_terr_gold_curr');
    this.cont_obj.qbw_terr_gold_production = new nsObject('.qbw_terr_gold_production');
    this.cont_obj.qbw_reso_food_curr = new nsObject('.qbw_reso_food_curr');
    this.cont_obj.qbw_reso_food_production = new nsObject('.qbw_reso_food_production');
    this.cont_obj.qbw_reso_horse_curr = new nsObject('.qbw_reso_horse_curr');
    this.cont_obj.qbw_reso_horse_production = new nsObject('.qbw_reso_horse_production');
    this.cont_obj.qbw_reso_lumber_curr = new nsObject('.qbw_reso_lumber_curr');
    this.cont_obj.qbw_reso_lumber_production = new nsObject('.qbw_reso_lumber_production');
    this.cont_obj.qbw_reso_iron_curr = new nsObject('.qbw_reso_iron_curr');
    this.cont_obj.qbw_reso_iron_production = new nsObject('.qbw_reso_iron_production');
    this.cont_obj.cont_qibg_amount = new nsObject('.cont_qibg_amount');
}

ns_dialog.dialogs.resource.draw = function()
{
    this.draw_list();

    this.customShow();
}

ns_dialog.dialogs.resource.undraw = function(_recursive)
{
    this.open_status = false;

    this.customHide();
}

ns_dialog.dialogs.resource.draw_list = function()
{
    let dlg = ns_dialog.dialogs.resource;

    dlg.s.qbw_terr_population_idle.text(ns_cs.getTerrInfo('population_idle'));
    // 인구 증감 계산
    let population_trend_amount = 0;
    if(ns_cs.getTerrInfo('population_trend') == 'U')
    {
        if(ns_cs.getTerrInfo('population_upward_plus_amount'))
        {
            population_trend_amount = qbw_e.cfg.population_upward_default + parseInt(ns_cs.getTerrInfo('population_upward_plus_amount'));
        }
    } else if(ns_cs.getTerrInfo('population_trend') == 'D') {
        population_trend_amount = qbw_e.cfg.population_upward_default * -1;
    } else { // Stable
        population_trend_amount = 0;
    }

    if (population_trend_amount < 0)
        population_trend_amount = 0;

    dlg.s.qbw_terr_population_trend_amount.text(population_trend_amount);

    dlg.s.qbw_terr_gold_curr.text(qbw_util_numberFormat(ns_cs.getTerrInfo('gold_curr')));
    dlg.s.qbw_terr_gold_production.text(qbw_util_numberFormat(ns_cs.getTerrInfo('gold_production')));
    dlg.s.qbw_reso_food_curr.text(qbw_util_numberFormat(ns_cs.getResoInfo('food_curr')));
    dlg.s.qbw_reso_food_production.text(qbw_util_numberFormat(ns_cs.getResoInfo('food_production')));
    dlg.s.qbw_reso_horse_curr.text(qbw_util_numberFormat(ns_cs.getResoInfo('horse_curr')));
    dlg.s.qbw_reso_horse_production.text(qbw_util_numberFormat(ns_cs.getResoInfo('horse_production')));
    dlg.s.qbw_reso_lumber_curr.text(qbw_util_numberFormat(ns_cs.getResoInfo('lumber_curr')));
    dlg.s.qbw_reso_lumber_production.text(qbw_util_numberFormat(ns_cs.getResoInfo('lumber_production')));
    dlg.s.qbw_reso_iron_curr.text(qbw_util_numberFormat(ns_cs.getResoInfo('iron_curr')));
    dlg.s.qbw_reso_iron_production.text(qbw_util_numberFormat(ns_cs.getResoInfo('iron_production')));

    dlg.open_status = true;
}

ns_dialog.dialogs.resource.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive)
    {
        let ret = this.base_class.timerHandler.call(this, true);
        this.timerHandle_p = ret;
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.resource.timerHandler_proc_real, 9999, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.resource.timerHandler_proc_real = function()
{
    let dlg = ns_dialog.dialogs.resource;

    dlg.s.qbw_terr_population_idle.text(ns_cs.getTerrInfo('population_idle'));
    dlg.s.qbw_terr_gold_curr.text(qbw_util_numberFormat(ns_cs.getTerrInfo('gold_curr')));
    dlg.s.qbw_reso_food_curr.text(qbw_util_numberFormat(ns_cs.getResoInfo('food_curr')));
    dlg.s.qbw_reso_horse_curr.text(qbw_util_numberFormat(ns_cs.getResoInfo('horse_curr')));
    dlg.s.qbw_reso_lumber_curr.text(qbw_util_numberFormat(ns_cs.getResoInfo('lumber_curr')));
    dlg.s.qbw_reso_iron_curr.text(qbw_util_numberFormat(ns_cs.getResoInfo('iron_curr')));
}

ns_button.buttons.resource_list = new nsButtonSet('resource_list', 'btn_event_listener', 'resource');
ns_button.buttons.resource_list.mouseUp = function(_e)
{
    qbw_dlg.close();
    qbw_dlg.open('terr_info');
}*/