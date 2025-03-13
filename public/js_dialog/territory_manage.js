
ns_dialog.dialogs.territory_manage = new nsDialogSet('territory_manage', 'dialog_full', 'size-full');
ns_dialog.dialogs.territory_manage.tab_list = ['terr', 'reso', 'prod', 'army', 'fort'];
ns_dialog.dialogs.territory_manage.sorted = [];
ns_dialog.dialogs.territory_manage.now_info = null;
ns_dialog.dialogs.territory_manage.current_tab = null;
ns_dialog.dialogs.territory_manage.current_posi_pk = null;

ns_dialog.dialogs.territory_manage.cacheContents = function()
{
    for (let _tab of this.tab_list) {
        this.cont_obj[`tab_${_tab}`] = new nsObject(`.tab_${_tab}`, this.obj);
    }
}

ns_dialog.dialogs.territory_manage.draw = function()
{
    if (! this.visible) {
        ns_button.toggleGroupSingle(ns_button.buttons.territory_manage_tab_terr);
    }

    //도움말 관련하여 추가 - 첫 접속시 무조건 한번은 보여주도록
    /*let help_type = 'TerritoryManage';
    if (!window.localStorage.getItem('open_help_' + help_type))
    {
        qbw_dialog.setDataOpen('game_help', {'type':help_type});
        window.localStorage.setItem('open_help_' + help_type, 'Y');
    }*/

    this.drawTab();
}

ns_dialog.dialogs.territory_manage.drawTab = function()
{
    let dialog = ns_dialog.dialogs.territory_manage;
    let tab = ns_button.toggleGroupValue('territory_manage_tab')[0].split('_').pop();

    for(let _tab of dialog.tab_list) {
        if (tab === _tab) {
            dialog.cont_obj[`tab_${_tab}`].show();
        } else {
            dialog.cont_obj[`tab_${_tab}`].hide();
        }
    }

    let post_data = { };
    post_data['type'] = tab;
    post_data['page_num'] = 1;
    post_data['order_by'] = 'title';

    ns_xhr.post('/api/territoryManage/list', post_data, dialog.drawList);
}

ns_dialog.dialogs.territory_manage.drawList = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.territory_manage;
    let tab = ns_button.toggleGroupValue('territory_manage_tab')[0].split('_').pop();
    let main_tbody = dialog.cont_obj[`tab_${tab}`].find('tbody.main');
    let sub_tbody = dialog.cont_obj[`tab_${tab}`].find('tbody.sub');

    dialog.sorted = [];

    for (let d of Object.values(_data)) {
        dialog.sorted.push(d);
    }

    main_tbody.empty();
    if (tab !== 'reso') {
        sub_tbody.empty();
    }

    for (let d of dialog.sorted) {
        let tr = dialog.drawMain(tab, d);
        main_tbody.append(tr);

        if (tab === 'army') {
            for (let code of code_set.army_code) {
                tr = dialog.drawSub(tab, {
                    title: ns_cs.m.army[code].title,
                    my: d[code],
                    ally:  d[`alli_${code}`]
                });
                sub_tbody.append(tr);
            }
        } else {
            tr = dialog.drawSub(tab, d);
            sub_tbody.append(tr);
        }
    }

}

ns_dialog.dialogs.territory_manage.drawMain = function(_tab, _data)
{
    let dialog = ns_dialog.dialogs.territory_manage;

    let columns = [];
    let tr = document.createElement('tr');

    if (_tab === 'terr') {
        columns.push(dialog.drawColumn(`${_data['title']}<br />(${_data['posi_pk']})`));
        columns.push(dialog.drawColumn(ns_util.numberFormat(_data['population_curr']) + ' / ' + ns_util.numberFormat(_data['population_curr'] - _data['population_labor_force'])));
        columns.push(dialog.drawColumn(_data['loyalty']));
        columns.push(dialog.drawColumn(_data['tax_rate']));
        // 관리 버튼은 필요 없으므로 제외시킴 20230714 송누리
    } else if (_tab === 'reso') {
        columns.push(dialog.drawColumn(`${_data['title']}<br />(${_data['posi_pk']})`));
        columns.push(dialog.drawColumn(ns_util.numberFormat(_data['gold_curr']) + '<br/>' + '(' + ns_util.numberFormat(999999999) + ')'));
    } else if (_tab === 'prod') {
        columns.push(dialog.drawColumn(`${_data['title']}<br />(${_data['posi_pk']})`));
        columns.push(dialog.drawColumn(ns_util.numberFormat(_data['gold_production'])));
    } else if (_tab === 'army') {
        columns.push(dialog.drawColumn(`${_data['title']}<br />(${_data['posi_pk']})`));
        columns.push(dialog.drawColumn(ns_util.numberFormat(_data['total_army'])));
        columns.push(dialog.drawColumn(ns_util.numberFormat(_data['my_army'])));
        columns.push(dialog.drawColumn(ns_util.numberFormat(_data['alli_army'])));
    } else if (_tab === 'fort') {
        columns.push(dialog.drawColumn(`${_data['title']}<br />(${_data['posi_pk']})`));
        columns.push(dialog.drawColumn(`Lv.${_data['level']} / ` + ns_util.numberFormat(ns_cs.m.buil[201600].level[_data['level']].variation_1)));
    }

    for (let _col of columns) {
        tr.append(_col);
    }

    return tr;
}
ns_dialog.dialogs.territory_manage.drawSub = function(_tab, _data)
{
    let dialog = ns_dialog.dialogs.territory_manage;

    let columns = [];
    if (_tab === 'terr') {
        let concurr = _data['concurr'];
        columns.push(dialog.drawColumn(`${concurr['C']?.concurr_curr ?? 0} / ${concurr['C']?.concurr_max ?? 0}`));
        columns.push(dialog.drawColumn(`${concurr['T']?.concurr_curr ?? 0} / ${concurr['T']?.concurr_max ?? 0}`));
        columns.push(dialog.drawColumn((!_data['encounter']) ? '대기 중' : '탐색 중'));
        columns.push(dialog.drawColumn(`${concurr['A']?.concurr_curr ?? 0} / ${concurr['A']?.concurr_max ?? 0}`));
        columns.push(dialog.drawColumn(`${concurr['F']?.concurr_curr ?? 0} / ${concurr['F']?.concurr_max ?? 0}`));
    } else if (_tab === 'reso') {
        for (let _type of ['food', 'horse', 'lumber', 'iron']) {
            // columns.push(dialog.drawColumn(ns_util.numberFormat(_data[`${_type}_curr`]) + '/' + ns_util.numberFormat(_data[`${_type}_max`])));
            dialog.cont_obj[`tab_${_tab}`].find(`.current_resource.${_type}`).text(ns_util.numberFormat(_data[`${_type}_curr`]));
            dialog.cont_obj[`tab_${_tab}`].find(`.storage_limit.${_type}`).text(ns_util.numberFormat(_data[`${_type}_max`]));
        }
    } else if (_tab === 'prod') {
        for (let _type of ['food', 'horse', 'lumber', 'iron']) {
            columns.push(dialog.drawColumn(ns_util.numberFormat(_data[`${_type}_production`])));
        }
    } else if (_tab === 'army') {
        columns.push(dialog.drawColumn(_data.title));
        columns.push(dialog.drawColumn(ns_util.numberFormat(_data.my)));
        columns.push(dialog.drawColumn(ns_util.numberFormat(_data.ally)));
    } else if (_tab === 'fort') {
        columns.push(dialog.drawColumn(ns_util.math(_data['wall_vacancy_max']).minus(_data['wall_vacancy_curr']).number_format));
        columns.push(dialog.drawColumn(ns_util.numberFormat(_data['trap'])));
        columns.push(dialog.drawColumn(ns_util.numberFormat(_data['abatis'])));
        columns.push(dialog.drawColumn(ns_util.numberFormat(_data['tower'])));
    }

    let tr = document.createElement('tr');
    for (let _col of columns) {
        tr.append(_col);
    }

    return tr;

}

ns_dialog.dialogs.territory_manage.drawColumn = function (_data)
{
    let col = document.createElement('td');
    let span = document.createElement('span');
    span.innerHTML = _data;
    col.appendChild(span);
    return col;
}

/* ************************************************** */
ns_button.buttons.territory_manage_close = new nsButtonSet('territory_manage_close', 'button_back', 'territory_manage', {base_class:ns_button.buttons.common_close});
ns_button.buttons.territory_manage_sub_close = new nsButtonSet('territory_manage_sub_close', 'button_full', 'territory_manage', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.territory_manage_close_all = new nsButtonSet('territory_manage_close_all', 'button_close_all', 'territory_manage', {base_class:ns_button.buttons.common_close_all});

// ns_button.buttons.game_help_TerritoryManage = new nsButtonSet('game_help_TerritoryManage', 'button_dialog_help', 'territory_manage', {base_class:ns_button.buttons.buil_help});

ns_button.buttons.territory_manage_tab_terr = new nsButtonSet('territory_manage_tab_terr', 'button_tab', 'territory_manage', {toggle_group:'territory_manage_tab'});
ns_button.buttons.territory_manage_tab_terr.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.territory_manage;

    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.current_tab = null;
    dialog.current_posi_pk = null;
    dialog.drawTab();
}

ns_button.buttons.territory_manage_tab_reso = new nsButtonSet('territory_manage_tab_reso', 'button_tab', 'territory_manage', {base_class:ns_button.buttons.territory_manage_tab_terr, toggle_group:'territory_manage_tab'});
ns_button.buttons.territory_manage_tab_prod = new nsButtonSet('territory_manage_tab_prod', 'button_tab', 'territory_manage', {base_class:ns_button.buttons.territory_manage_tab_terr, toggle_group:'territory_manage_tab'});
ns_button.buttons.territory_manage_tab_army = new nsButtonSet('territory_manage_tab_army', 'button_tab', 'territory_manage', {base_class:ns_button.buttons.territory_manage_tab_terr, toggle_group:'territory_manage_tab'});
ns_button.buttons.territory_manage_tab_fort = new nsButtonSet('territory_manage_tab_fort', 'button_tab', 'territory_manage', {base_class:ns_button.buttons.territory_manage_tab_terr, toggle_group:'territory_manage_tab'});

/*ns_button.buttons.territory_infomation = new nsButtonSet('territory_infomation', 'button_empty', 'territory_manage');
ns_button.buttons.territory_infomation.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.territory_manage;

    let zArr = _e.target.id.split('_');
    let tab = zArr[4];
    let posi_pk = zArr[5];

    dialog.open_sub(tab, posi_pk);
}*/

/*ns_button.buttons.territory_manage_change = new nsButtonSet('territory_manage_change', 'button_small_2', 'territory_manage');
ns_button.buttons.territory_manage_change.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.territory_manage;
    let zArr = _e.target.id.split('_');

    if (qbw_e.cfg.cpp == zArr[6])
        return;

    qbw_e.changeTerritory(zArr[6]);

    qbw_world.curr_posi_pk = zArr[6];
    qbw_world.goto_map = true;
    qbw_world.init(_e);

    ns_dialog.dialogs.main_event.change_territory = true;
}*/