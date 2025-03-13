
ns_dialog.dialogs.ranking = new nsDialogSet('ranking', 'dialog_full', 'size-full');
ns_dialog.dialogs.ranking.tab_list = ['lord', 'hero', 'ally', 'area'];
ns_dialog.dialogs.ranking.current_tab = null;
ns_dialog.dialogs.ranking.sorted = null;

ns_dialog.dialogs.ranking.cacheContents = function()
{
    // this.s.cont_curr_page = this.cont_obj.find('.cont_curr_page');
    // this.s.cont_total_page = this.cont_obj.find('.cont_total_page');
//
    // this.s.cont_ranking_search_title = this.cont_obj.find('.cont_ranking_search_title');
    // this.s.cont_ranking_search = this.cont_obj.find('#cont_ranking_search');
//
    // this.s.cont_list = this.cont_obj.find('.cont_list');
    // this.s.col1 = this.cont_obj.find('.col1');
    // this.s.col2 = this.cont_obj.find('.col2');
    // this.s.col3 = this.cont_obj.find('.col3');
    // this.s.col4 = this.cont_obj.find('.col4');
//
    // this.s.cont_tbody = this.cont_obj.find('.cont_tbody');

    this.cont_obj.my_rank = new nsObject('.tab_ranking_lord .my_rank', this.obj);
    this.cont_obj.my_rank_name = new nsObject('.tab_ranking_lord .my_rank_name', this.obj);
    this.cont_obj.my_rank_power = new nsObject('.tab_ranking_lord .my_rank_power', this.obj);

    for (let _tab of this.tab_list) {
        this.cont_obj[`tab_ranking_${_tab}`] = new nsObject(`.tab_ranking_${_tab}`, this.obj);
        this.cont_obj[`${_tab}_tbody`] = new nsObject(`.tab_ranking_${_tab} tbody.${_tab}_list`, this.obj);
    }
}

ns_dialog.dialogs.ranking.draw = function()
{
    if (! this.visible) {
        ns_button.toggleGroupSingle(ns_button.buttons.ranking_tab_lord);

        ns_select_box.set('ranking_lord_sort', 'power', 'desc');
        ns_button.buttons.ranking_lord_sort.obj.text(ns_select_box.getText('ranking_lord_sort'));

        ns_select_box.set('ranking_hero_sort', 'leadership', 'desc');
        ns_button.buttons.ranking_hero_sort.obj.text(ns_select_box.getText('ranking_hero_sort'));
    }

    this.current_page = 1;
    this.drawTab();
}

ns_dialog.dialogs.ranking.drawTab = function()
{
    let dialog = ns_dialog.dialogs.ranking;
    let tab = ns_button.toggleGroupValue('ranking_tab')[0].split('_').pop();

    for (let _tab of dialog.tab_list) {
        if (_tab === tab) {
            dialog.cont_obj[`tab_ranking_${_tab}`].show();
        } else {
            dialog.cont_obj[`tab_ranking_${_tab}`].hide();
        }
    }

    if (tab === 'lord') {
        ns_button.buttons.ranking_lord_sort.obj.show();
        ns_button.buttons.ranking_hero_sort.obj.hide();
    } else if (tab === 'hero') {
        ns_button.buttons.ranking_lord_sort.obj.hide();
        ns_button.buttons.ranking_hero_sort.obj.show();
    } else {
        ns_button.buttons.ranking_lord_sort.obj.hide();
        ns_button.buttons.ranking_hero_sort.obj.hide();
    }


    let order_type = ns_select_box.get('ranking_lord_sort').val;
    dialog.current_tab = tab;
    if (tab === 'hero') {
        order_type = ns_select_box.get('ranking_hero_sort').val;
    }

    let post_data = { };
    post_data['order'] = order_type;
    ns_xhr.post(`/api/ranking/${tab}List`, post_data, dialog.drawRemote);
}

ns_dialog.dialogs.ranking.drawRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.ranking;

    // dialog.current_page = _data.curr_page;
    // dialog.set_page_view(_data.total_count);
    // let search_word = ns_button.buttons.ranking_search.obj.text();

    let tab = ns_button.toggleGroupValue('ranking_tab')[0].split('_').pop();

    dialog.buttonClear();
    dialog.cont_obj[`${tab}_tbody`].empty();
    // ns_button.buttons.ranking_my_lord.obj.hide();

    dialog.sorted = [];

    for (let d of Object.values(_data.list)) {
        dialog.sorted.push(d);
    }

    if (tab === 'lord') {
        if (_data?.my_rank !== null) {
            dialog.cont_obj.my_rank.text((_data.my_rank === 0) ? ns_i18n.t('out_of_ranking') : _data.my_rank);
            dialog.cont_obj.my_rank_name.text(ns_cs.d.lord.lord_name.v);
            dialog.cont_obj.my_rank_power.text(ns_util.numberFormat(ns_cs.d.lord.power.v));
        }

        // ns_button.buttons.ranking_my_lord.obj.show();
        for (let d of dialog.sorted) {
            let columns = [];
            let tr = document.createElement('tr');

            let col = dialog.drawColumn(d.rank_power);
            let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
            button.setAttribute('id', `ns_button_ranking_${tab}_${d.lord_pk}`);
            col.append(button);
            columns.push(col);

            col = dialog.drawColumn(d.lord_name);
            columns.push(col);

            col = dialog.drawColumn(d.alliance_title ?? '-');
            columns.push(col);

            col = dialog.drawColumn(ns_util.numberFormat(d.power));
            columns.push(col);

            col = dialog.drawColumn(ns_util.numberFormat(d.attack_point));
            columns.push(col);
            col = dialog.drawColumn(ns_util.numberFormat(d.defence_point));
            columns.push(col);
            col = dialog.drawColumn(ns_util.numberFormat(d.army_point));
            columns.push(col);

            for (let _col of columns) {
                tr.appendChild(_col);
            }

            dialog.cont_obj[`${tab}_tbody`].append(tr);

            let button_id = `ranking_${tab}_${d.lord_pk}`;
            ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'ranking');
            ns_button.buttons[button_id].mouseUp = function ()
            {
                ns_dialog.setDataOpen('lord_info', d);
            }
            dialog.buttons.push(ns_button.buttons[button_id]);
        }
    } else if (tab === 'hero') {
        let current_rank = 0, max_stats = 1000;
        let order_type = ns_select_box.get('ranking_hero_sort').val;
        for (let d of dialog.sorted) {
            let columns = [];
            let tr = document.createElement('tr');

            let col = dialog.drawColumn(d.rank);
            let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
            button.setAttribute('id', `ns_button_ranking_${tab}_${d.hero_pk}`);
            col.append(button);
            columns.push(col);

            col = dialog.drawColumn(`${d.lord_name}`);
            columns.push(col);

            let m = ns_cs.m.hero[d.m_hero_pk];
            let m_base = ns_cs.m.hero_base[m.m_hero_base_pk];

            col = dialog.drawColumn(`Lv.${m.level} ${m_base.name}`);
            columns.push(col);

            col = dialog.drawColumn(d.leadership);
            columns.push(col);
            col = dialog.drawColumn(d.mil_force);
            columns.push(col);
            col = dialog.drawColumn(d.intellect);
            columns.push(col);
            col = dialog.drawColumn(d.politics);
            columns.push(col);
            col = dialog.drawColumn(d.charm);
            columns.push(col);

            for (let _col of columns) {
                tr.appendChild(_col);
            }

            dialog.cont_obj[`${tab}_tbody`].append(tr);

            let button_id = `ranking_${tab}_${d.hero_pk}`;
            ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'ranking');
            ns_button.buttons[button_id].mouseUp = function ()
            {
                ns_dialog.dialogs.hero_card.hide_button = true;
                ns_dialog.setDataOpen('hero_card', d);
            }
            dialog.buttons.push(ns_button.buttons[button_id]);
        }
    } else if (tab === 'ally') {
        for (let d of dialog.sorted) {
            let columns = [];
            let tr = document.createElement('tr');

            let col = dialog.drawColumn(d.power_rank);
            let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
            col.append(button);
            columns.push(col);

            col = dialog.drawColumn(d.title ?? '-');
            columns.push(col);

            col = dialog.drawColumn(d.lord_name);
            columns.push(col);

            // col = dialog.drawColumn(ns_util.numberFormat(d.attack_point));
            // col = dialog.drawColumn(ns_util.numberFormat(d.defence_point));
            col = dialog.drawColumn(ns_util.numberFormat(d.power));
            columns.push(col);

            for (let _col of columns) {
                tr.appendChild(_col);
            }

            dialog.cont_obj[`${tab}_tbody`].append(tr);
        }
    } else if (tab === 'area') {
        let tr = document.createElement('tr');
        let col = dialog.drawColumn(ns_i18n.t('in_ready'));
        col.colSpan = 4;
        tr.appendChild(col);
        dialog.cont_obj[`${tab}_tbody`].append(tr);
    }
}

ns_dialog.dialogs.ranking.drawColumn = function (_text)
{
    let col = document.createElement('td');
    let span = document.createElement('span');
    span.innerText = _text;
    col.appendChild(span);
    return col;
}

/* ************************************************** */

ns_button.buttons.ranking_close = new nsButtonSet('ranking_close', 'button_back', 'ranking', {base_class:ns_button.buttons.common_close});
ns_button.buttons.ranking_sub_close = new nsButtonSet('ranking_sub_close', 'button_full', 'ranking', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.ranking_close_all = new nsButtonSet('ranking_close_all', 'button_close_all', 'ranking', {base_class:ns_button.buttons.common_close_all});

/* ************************* */

ns_button.buttons.ranking_tab_lord = new nsButtonSet('ranking_tab_lord', 'button_tab', 'ranking', {toggle_group:'ranking_tab'});
ns_button.buttons.ranking_tab_lord.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.ranking;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.drawTab();
}
ns_button.buttons.ranking_tab_hero = new nsButtonSet('ranking_tab_hero', 'button_tab', 'ranking', {base_class:ns_button.buttons.ranking_tab_lord, toggle_group:'ranking_tab'});
ns_button.buttons.ranking_tab_area = new nsButtonSet('ranking_tab_area', 'button_tab', 'ranking', {base_class:ns_button.buttons.ranking_tab_lord, toggle_group:'ranking_tab'});
ns_button.buttons.ranking_tab_ally = new nsButtonSet('ranking_tab_ally', 'button_tab', 'ranking', {base_class:ns_button.buttons.ranking_tab_lord, toggle_group:'ranking_tab'});

ns_button.buttons.ranking_lord_sort = new nsButtonSet('ranking_lord_sort', 'button_select_box', 'ranking');
ns_button.buttons.ranking_lord_sort.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('select_box', {select_box_id: 'ranking_lord_sort'});
}

ns_button.buttons.ranking_hero_sort = new nsButtonSet('ranking_hero_sort', 'button_select_box', 'ranking');
ns_button.buttons.ranking_hero_sort.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('select_box', {select_box_id: 'ranking_hero_sort'});
}

/*ns_button.buttons.ranking_my_lord.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.ranking;
    let order_type = ns_select_box.get('ranking_lord_sort');

    let post_data = { };
    post_data['action'] = 'my_lord_ranking';
    post_data['order'] = order_type;

    qbw_cmd('/a/ranking.php', post_data, dialog.draw_tab_proc, null, true);
}*/