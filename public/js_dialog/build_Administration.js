// 다이얼로그
ns_dialog.dialogs.build_Administration = new nsDialogSet('build_Administration', 'dialog_building', 'size-large', { base_class: ns_dialog.dialogs.build_Template });
ns_dialog.dialogs.build_Administration.current_tab = null;
ns_dialog.dialogs.build_Administration.opened = false;
ns_dialog.dialogs.build_Administration.valley_open = false;
ns_dialog.dialogs.build_Administration.sorted = [];

ns_dialog.dialogs.build_Administration.cacheContents = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.cacheContents.call(this, true);
    }

    this.cont_obj.build_Administration_tab_build = new nsObject('.build_Administration_tab_build', this.obj);
    this.cont_obj.build_Administration_tab_prod = new nsObject('.build_Administration_tab_prod', this.obj);
    this.cont_obj.build_Administration_tab_valley = new nsObject('.build_Administration_tab_valley', this.obj);
    this.cont_obj.build_Administration_tab_terr = new nsObject('.build_Administration_tab_terr', this.obj);

    this.cont_obj.in_castle_tbody = new nsObject('.in_castle_tbody', this.obj);
    this.cont_obj.out_castle_tbody = new nsObject('.out_castle_tbody', this.obj);
}

ns_dialog.dialogs.build_Administration.draw = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.base_class.draw.call(this, true);
    }

    ns_button.toggleGroupSingle(ns_button.buttons.build_Administration_tab_build);
    this.drawTab();
}

ns_dialog.dialogs.build_Administration.erase = function(_recursive)
{
    ns_dialog.close('build_Administration_tax_rate');
    ns_dialog.close('build_Administration_comforting');
    ns_dialog.close('build_Administration_requisition');
    ns_dialog.close('build_Administration_terr_title');
}

ns_dialog.dialogs.build_Administration.drawTab = function ()
{
    let dialog = ns_dialog.dialogs.build_Administration;
    // 상단 버튼 체크
    let arr = this.tag_id.split('_');
    let alias = arr.pop();
    let m = ns_cs.m.buil[alias];
    let bd_c = m.type === 'I' ? ns_cs.d.bdic : ns_cs.d.bdoc;
    let d = bd_c[dialog.data.castle_pk];

    let _enable = ns_util.math(d.level).lt(1);
    for (let _button_type of ['terr_title', 'requisition', 'comforting', 'tax_rate']) {
        if (_enable) {
            ns_button.buttons[`build_Administration_view_${_button_type}`].setDisable();
        } else {
            ns_button.buttons[`build_Administration_view_${_button_type}`].setEnable();
        }
    }

    let current_tab = ns_button.toggleGroupValue('build_Administration_tab');

    for (let _type of ['build', 'prod', 'valley', 'terr']) {
        if (current_tab[0] === `build_Administration_tab_${_type}`) {
            dialog.cont_obj[`build_Administration_tab_${_type}`].show();
        } else {
            dialog.cont_obj[`build_Administration_tab_${_type}`].hide();
        }
    }
    let tab = current_tab[0].split('_').pop();

    if (tab === 'build') {
        this.drawTabBuild();
    } else {
        ns_xhr.post(`/api/administration/${tab}`, {}, (_data, _status) => {
            let function_name = tab.replace(/^[a-z]/, char => char.toUpperCase());
            dialog['drawTab' + function_name](_data, _status);
        });
    }
}

ns_dialog.dialogs.build_Administration.drawTabBuild = function()
{
    let dialog = ns_dialog.dialogs.build_Administration ;
    dialog.cont_obj.in_castle_tbody.empty();
    dialog.cont_obj.out_castle_tbody.empty();

    // 목록 리스트 캐싱
    dialog.sorted = [];
    for (let [k, d] of Object.entries(ns_cs.d.bdic)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        d.castle_type = 'in_castle';
        dialog.sorted.push(d);
    }
    for (let [k, d] of Object.entries(ns_cs.d.bdoc)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        d.castle_type = 'out_castle';
        dialog.sorted.push(d);
    }

    dialog.sorted = ns_util.arraySort(dialog.sorted, -1, 'm_buil_pk');
    let prev_m_pk = 0, prev_count = 0;
    for (let d of dialog.sorted) {
        let status = (d.level < 1) ? 'C' : d.status;
        let tr = document.createElement('tr');

        let col1 = document.createElement('td');
        col1.classList.add('col1');

        if (ns_util.math(prev_m_pk).eq(d.m_buil_pk)) {
            prev_count++;
        } else {
            prev_count = 1;
        }
        prev_m_pk = d.m_buil_pk;

        col1.innerHTML = ns_i18n.t(`build_title_${d.m_buil_pk}`);
        if (ns_cs.m.buil[d.m_buil_pk].yn_duplication === 'Y') {
            col1.innerHTML += ' ' + prev_count;
        }

        let col2 = document.createElement('td');
        col2.innerHTML = d.level;

        let col3 = document.createElement('td');
        col2.classList.add('col3');
        col3.innerHTML = code_set.build_status[status];

        tr.appendChild(col1);
        tr.appendChild(col2);
        tr.appendChild(col3);

        dialog.cont_obj[`${d.castle_type}_tbody`].append(tr);
    }
}

ns_dialog.dialogs.build_Administration.drawTabProd = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.build_Administration;
    let tbody = dialog.cont_obj.build_Administration_tab_prod.find('tbody');

    for (let [k, d] of Object.entries(_data)) {
        if (k.indexOf('_pct_plus_item') !== -1) {
            let gate_pct = '0%';
            if (ns_cs.d.terr.status_gate.v === 'O') {
                d = ns_util.math(d).minus(10);
                gate_pct = '10%';
            }
            tbody.find(`.content_${k.split('_')[0]}_pct_plus_gate`).text(gate_pct);
        }
        tbody.find(`.content_${k}`).text(ns_util.numberFormat(d));

        if (k.indexOf('_pct_plus_') !== -1) {
            tbody.find(`.content_${k}`).text('%', true);
        }
    }

    // 생산현황 탭의 총 생산량 표시
    for (let resource_type of ['food',  'horse', 'lumber' ,'iron']) {
        tbody.find(`.ns_resource_${resource_type}_production`).text(ns_util.numberFormat(ns_util.toInteger(ns_cs.d.reso[`${resource_type}_production`].v)));
    }
}

ns_dialog.dialogs.build_Administration.drawTabValley = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.build_Administration;
    let tbody = dialog.cont_obj.build_Administration_tab_valley.find('tbody');

    tbody.empty();

    if (_data.length === 0) {
        let tr = document.createElement('tr');
        let td = document.createElement('td');
        td.classList.add('text_align_center');
        td.colSpan = 5;

        let span = document.createElement('span');
        span.innerHTML = ns_i18n.t('msg_not_possess_external_valley');

        td.appendChild(span);
        tr.appendChild(td);

        tbody.append(tr);
    } else {
        for (let [k, d] of Object.entries(_data)) {
            if(typeof d === 'object') {
                let tr = document.createElement('tr');

                let col1 = document.createElement('td');
                col1.classList.add('col1');
                col1.innerHTML = codeset.t('valley', d.type) + ns_i18n.t('level_word', [d.level]);

                let col2 = document.createElement('td');
                col2.classList.add('col2');
                let valley_posi_pk = d.valley_posi_pk;
                col2.innerHTML = valley_posi_pk;

                let col3 = document.createElement('td');
                col3.classList.add('col3');
                let _text = '';
                if (ns_cs.m.prod_vall[d.type]) {
                    let m = ns_cs.m.prod_vall[d.type][d.level];
                    for (let _type of ['food', 'horse', 'lumber', 'iron']) {
                        if (ns_util.math(m[_type]).gt(0)) {
                            if (_text.length > 0) {
                                _text += ', ';
                            }
                            _text += ns_i18n.t(`resource_${_type}`) + ' (' + m[_type] + ')';
                        }
                    }
                } else {
                    _text = code_set.valley_type_prod[d.type];
                }
                col3.innerHTML = _text;

                let col4 = document.createElement('td');
                col4.classList.add('col4');
                // 수식어 영웅 네임 색상
                if(d.m_hero_pk && ns_cs.m.hero_base[ns_cs.m.hero[d.m_hero_pk].m_hero_base_pk].yn_modifier === 'Y') {
                    col4.classList.add('modifier');
                }
                col4.innerHTML = d.captain_desc ? d.captain_desc : '-';

                let _button_id = `build_Administration_give_up_${valley_posi_pk}`;
                let col5 = document.createElement('td');
                col5.classList.add('col5');
                let col5_span = document.createElement('span');
                col5_span.setAttribute('id', `ns_button_${_button_id}`);
                col5_span.innerHTML = ns_i18n.t('give_up'); // 포기
                col5.appendChild(col5_span);

                tr.appendChild(col1);
                tr.appendChild(col2);
                tr.appendChild(col3);
                tr.appendChild(col4);
                tr.appendChild(col5);

                tbody.append(tr);

                ns_button.buttons[_button_id] = new nsButtonSet(_button_id, 'button_small_2', 'build_Administration', { base_class: ns_button.buttons.build_Administration_give_up });
            }
        }
    }
}

ns_dialog.dialogs.build_Administration.drawTabTerr = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.build_Administration;
    let tbody = dialog.cont_obj.build_Administration_tab_terr.find('tbody');

    tbody.empty();

    for (let [k, d] of Object.entries(_data)) {
        if (typeof d === 'object') {
            let m_hero = ns_cs.m.hero[d.m_hero_pk];
            let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];
            let abandon_button_id = `build_Administration_abandon_${d.posi_pk}`;

            let tr = document.createElement('tr');

            let col1 = document.createElement('td');
            col1.classList.add('col1');
            col1.innerHTML = d.title;

            let col2 = document.createElement('td');
            col2.classList.add('col2');
            col2.innerHTML = d.posi_pk;

            let col3 = document.createElement('td');
            col3.classList.add('col3');
            col3.innerHTML = m_hero_base.name + ' ' + ns_util.getLevelStr(d.level);

            let col4 = document.createElement('td');
            col4.classList.add('col4');
            col4.innerHTML = ns_util.numberFormat(d.population_curr);

            let col5 = document.createElement('td');
            col5.classList.add('col5');
            let col5_span = document.createElement('span');
            col5_span.setAttribute('id', `ns_button_${abandon_button_id}`);
            col5_span.innerHTML = ns_i18n.t('give_up');
            col5.appendChild(col5_span);

            tr.appendChild(col1);
            tr.appendChild(col2);
            tr.appendChild(col3);
            tr.appendChild(col4);
            tr.appendChild(col5);

            tbody.append(tr);

            ns_button.buttons[abandon_button_id] = new nsButtonSet(abandon_button_id, 'button_small_2', 'build_Administration', { base_class: ns_button.buttons.build_Administration_abandon });
        }
    }
}


ns_button.buttons.build_Administration_close = new nsButtonSet('build_Administration_close', 'button_back', 'build_Administration', { base_class: ns_button.buttons.common_close});
ns_button.buttons.build_Administration_sub_close = new nsButtonSet('build_Administration_sub_close', 'button_full', 'build_Administration', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.build_Administration_close_all = new nsButtonSet('build_Administration_close_all', 'button_close_all', 'build_Administration', { base_class: ns_button.buttons.common_close_all });

// ns_button.buttons.build_desc_Administration = new nsButtonSet('build_desc_Administration', 'button_text_style_desc', 'build_Administration', { base_class: ns_button.buttons.build_desc });
ns_button.buttons.build_move_Administration = new nsButtonSet('build_move_Administration', 'button_middle_2', 'build_Administration', { base_class: ns_button.buttons.build_move });
ns_button.buttons.build_cons_Administration = new nsButtonSet('build_cons_Administration', 'button_multi', 'build_Administration', { base_class: ns_button.buttons.build_cons });
ns_button.buttons.build_upgrade_Administration = new nsButtonSet('build_upgrade_Administration', 'button_hero_action', 'build_Administration', { base_class: ns_button.buttons.build_upgrade });

ns_button.buttons.build_assign_Administration = new nsButtonSet('build_assign_Administration', 'button_full', 'build_Administration', { base_class: ns_button.buttons.build_assign });
ns_button.buttons.build_no_assign_Administration = new nsButtonSet('build_no_assign_Administration', 'button_full', 'build_Administration', { base_class: ns_button.buttons.build_assign });

ns_button.buttons.build_prev_Administration = new nsButtonSet('build_prev_Administration', 'button_multi_prev', 'build_Administration', { base_class: ns_button.buttons.build_prev });
ns_button.buttons.build_next_Administration = new nsButtonSet('build_next_Administration', 'button_multi_next', 'build_Administration', { base_class: ns_button.buttons.build_next });

ns_button.buttons.build_speedup_Administration = new nsButtonSet('build_speedup_Administration', 'button_encourage', 'build_Administration', { base_class: ns_button.buttons.build_speedup });
// ns_button.buttons.build_cancel_Administration = new nsButtonSet('build_cancel_Administration', 'button_build', 'build_Administration', { base_class: ns_button.buttons.build_cancel });

ns_button.buttons.build_Administration_view_tax_rate = new nsButtonSet('build_Administration_view_tax_rate', 'button_middle_2', 'build_Administration');
ns_button.buttons.build_Administration_view_tax_rate.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Administration;
    let arr = dialog.tag_id.split('_');
    let alias = arr.pop();
    let m = ns_cs.m.buil[alias];
    let bd_c = m.type === 'I' ? ns_cs.d.bdic : ns_cs.d.bdoc;
    let d = bd_c[dialog.data.castle_pk];
    if (ns_util.math(d.level).lt(1)) {
        return;
    }

    let target_name = this.tag_id.replace('_view_', '_');
    for (let dialog_name of ['comforting', 'requisition', 'terr_title', 'tax_rate']) {
        if (`build_Administration_${dialog_name}` === target_name) {
            ns_dialog.open(`build_Administration_${dialog_name}`);
        } else {
            ns_dialog.close(`build_Administration_${dialog_name}`);
        }
    }
}

ns_button.buttons.build_Administration_view_comforting = new nsButtonSet('build_Administration_view_comforting', 'button_middle_2', 'build_Administration', { base_class: ns_button.buttons.build_Administration_view_tax_rate });
ns_button.buttons.build_Administration_view_requisition = new nsButtonSet('build_Administration_view_requisition', 'button_middle_2', 'build_Administration', { base_class: ns_button.buttons.build_Administration_view_tax_rate });
ns_button.buttons.build_Administration_view_terr_title = new nsButtonSet('build_Administration_view_terr_title', 'button_middle_2', 'build_Administration', { base_class: ns_button.buttons.build_Administration_view_tax_rate });

ns_button.buttons.build_Administration_tab_build = new nsButtonSet('build_Administration_tab_build', 'button_tab', 'build_Administration', { toggle_group: 'build_Administration_tab' });
ns_button.buttons.build_Administration_tab_build.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Administration;

    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.drawTab();
}

ns_button.buttons.build_Administration_tab_prod = new nsButtonSet('build_Administration_tab_prod', 'button_tab', 'build_Administration',  { base_class: ns_button.buttons.build_Administration_tab_build, toggle_group: 'build_Administration_tab'});
ns_button.buttons.build_Administration_tab_valley = new nsButtonSet('build_Administration_tab_valley', 'button_tab', 'build_Administration',  { base_class: ns_button.buttons.build_Administration_tab_build, toggle_group: 'build_Administration_tab'});
ns_button.buttons.build_Administration_tab_terr = new nsButtonSet('build_Administration_tab_terr', 'button_tab', 'build_Administration',  { base_class: ns_button.buttons.build_Administration_tab_build, toggle_group: 'build_Administration_tab'});


ns_button.buttons.build_Administration_abandon = new nsButtonSet('build_Administration_abandon', 'button_middle_1', 'build_Administration');
ns_button.buttons.build_Administration_abandon.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Administration;
    let arr = _e.target.id.split('_');
    let target_posi_pk = arr.pop();

    // 포기제한
    let _message = '';
    if (target_posi_pk === ns_cs.d.lord.main_posi_pk.v) {
        _message = ns_i18n.t('msg_main_territory_abandon_fail');
    } else if (target_posi_pk === ns_engine.game_data.cpp) {
        _message = ns_i18n.t('msg_current_territory_abandon_fail');
    }
    if (_message !== '') {
        ns_dialog.setDataOpen('message', _message);
        return;
    }

    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_abandon_confirm'),
        okFunc: () =>
        {
            let post_data = {};
            post_data['target_posi_pk'] = target_posi_pk;
            ns_xhr.post('/api/position/abandon', post_data, (_data, _status) =>
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];

                ns_dialog.setDataOpen('message', ns_i18n.t('msg_abandon_complete'));

                if (ns_button.buttons[`build_Administration_abandon_${target_posi_pk}`]) {
                    ns_button.buttons[`build_Administration_abandon_${target_posi_pk}`].destroy();
                }

                dialog.drawTab();
                // ns_button.buttons.build_Administration_tab_terr.mouseUp(_e);

                // 대륙갱신(view 중일때 즉시 갱신, 외성/내성 view 중일때 갱신 타이밍 조절 - 자동)
                ns_engine.cfg.world_tick = 1;
                ns_timer.worldReloadTick();
            }, { useProgress: true });
        }
    });
}

ns_button.buttons.build_Administration_give_up = new nsButtonSet('build_Administration_give_up', 'button_middle_2', 'build_Administration');
ns_button.buttons.build_Administration_give_up.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Administration;
    let arr = _e.target.id.split('_');
    let target_posi_pk = arr.pop();

    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_give_up_confirm'),
        okFunc: () => {
            let post_data = {};
            post_data['target_posi_pk'] = target_posi_pk;
            ns_xhr.post('/api/position/giveUp', post_data, (_data, _status) =>
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];

                ns_dialog.setDataOpen('message', ns_i18n.t('msg_give_up_complete'));

                if (ns_button.buttons[`build_Administration_give_up_${target_posi_pk}`]) {
                    ns_button.buttons[`build_Administration_give_up_${target_posi_pk}`].destroy();
                }

                if (dialog.visible) {
                    dialog.drawTab();
                }
                if (ns_dialog.dialogs.valley_manage.visible) {
                    ns_dialog.dialogs.valley_manage.drawList();
                }

                // 대륙갱신(view 중일때 즉시 갱신, 외성/내성 view 중일때 갱신 타이밍 조절 - 자동)
                ns_engine.cfg.world_tick = 1;
                ns_timer.worldReloadTick();
            }, { useProgress: true });
        }
    });
}


// 세율
ns_dialog.dialogs.build_Administration_tax_rate = new nsDialogSet('build_Administration_tax_rate', 'dialog_pop', 'size-medium', { do_content_scroll: false, do_close_all: false });
ns_dialog.dialogs.build_Administration_tax_rate.tax_rate_value = null;

ns_dialog.dialogs.build_Administration_tax_rate.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);

    this.cont_obj.tax_rate = new nsObject('#ns_button_build_Administration_tax_rate_input', this.obj);
    this.cont_obj.content_population_curr = new nsObject('.content_population_curr', this.obj);
    this.cont_obj.content_loyalty = new nsObject('.content_loyalty', this.obj);
    this.cont_obj.content_per_hour_rate = new nsObject('.content_per_hour_rate', this.obj);
}

ns_dialog.dialogs.build_Administration_tax_rate.draw = function()
{
    this.cont_obj.content_pop_title.text('세율');

    this.cont_obj.content_population_curr.text(ns_util.numberFormat(ns_cs.d.terr.population_curr.v));
    this.cont_obj.content_loyalty.text(ns_util.numberFormat(ns_cs.d.terr.loyalty.v));
    this.cont_obj.content_per_hour_rate.text(ns_util.math(ns_cs.d.terr.tax_rate.v).mul(0.01).mul(ns_cs.d.terr.population_curr.v).plus(100).number_format);
    this.cont_obj.tax_rate.text(ns_cs.d.terr.tax_rate.v);
}

/* ************************************************** */
ns_button.buttons.build_Administration_tax_rate_close = new nsButtonSet('build_Administration_tax_rate_close', 'button_pop_close', 'build_Administration_tax_rate', { base_class: ns_button.buttons.common_close });
ns_button.buttons.build_Administration_tax_rate_sub_close = new nsButtonSet('build_Administration_tax_rate_sub_close', 'button_full', 'build_Administration_tax_rate', { base_class: ns_button.buttons.common_sub_close });

/* ************************* */
ns_button.buttons.build_Administration_tax_rate_input = new nsButtonSet('build_Administration_tax_rate_input', 'button_input', 'build_Administration_tax_rate');
ns_button.buttons.build_Administration_tax_rate_input.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Administration_tax_rate;

    ns_dialog.setDataOpen('keypad', { max: 50, min: 0, current: dialog.cont_obj.tax_rate.text() ,callback: function(_data){
            dialog.cont_obj.tax_rate.text(_data);
            dialog.tax_rate_value = _data;
            dialog.cont_obj.content_per_hour_rate.text(ns_util.math(dialog.tax_rate_value).mul(0.01).mul(ns_cs.d.terr.population_curr.v).plus(100).number_format);
        }
    });
}

ns_button.buttons.build_Administration_tax_rate_Adjustment = new nsButtonSet('build_Administration_tax_rate_Adjustment', 'button_pop_normal', 'build_Administration_tax_rate');
ns_button.buttons.build_Administration_tax_rate_Adjustment.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Administration_tax_rate;
    let tax_rate = dialog.tax_rate_value;

    if (ns_util.math(ns_cs.d.terr.tax_rate.v).eq(tax_rate)) {
        ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_tax_rate_change_equal') });
        return;
    }

    let post_data = {};
    post_data['tax_rate'] = tax_rate;
    ns_xhr.post('/api/administration/adjustment', post_data, function(_data)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_tax_rate_change_complete') });

        ns_dialog.close('build_Administration_tax_rate');
    }, { useProgress: true });
}

// 복지
ns_dialog.dialogs.build_Administration_comforting = new nsDialogSet('build_Administration_comforting', 'dialog_pop', 'size-medium', { do_content_scroll: false, do_close_all: false });

ns_dialog.dialogs.build_Administration_comforting.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.content_population_curr = new nsObject('.content_population_curr', this.obj);
    this.cont_obj.content_loyalty = new nsObject('.content_loyalty', this.obj);

    this.cont_obj.content_select_redress = new nsObject('.content_select_redress', this.obj);
    this.cont_obj.content_select_ritual = new nsObject('.content_select_ritual', this.obj);
    this.cont_obj.content_select_prevention_disasters = new nsObject('.content_select_prevention_disasters', this.obj);
}

ns_dialog.dialogs.build_Administration_comforting.draw = function()
{
    this.cont_obj.content_pop_title.text(ns_i18n.t('comforting'));

    this.cont_obj.content_population_curr.text(ns_util.numberFormat(ns_cs.d.terr.population_curr.v));
    this.cont_obj.content_loyalty.text(ns_util.numberFormat(ns_cs.d.terr.loyalty.v));

    this.cont_obj.content_select_redress.show();
    this.cont_obj.content_select_ritual.hide();
    this.cont_obj.content_select_prevention_disasters.hide();

    this.cont_obj.content_select_redress.find('.content_need_food').text(ns_util.math(ns_cs.d.terr.population_curr.v).mul(2.5).number_format);

    ns_select_box.set('build_Administration_comforting_filter', 'redress');
    ns_button.buttons.build_Administration_comforting_filter.obj.text(ns_select_box.getText('build_Administration_comforting_filter'));

    this.drawSelectFilter();
}

ns_dialog.dialogs.build_Administration_comforting.drawSelectFilter = function()
{
    let dialog = ns_dialog.dialogs.build_Administration_comforting;
    let select_box = ns_select_box.get('build_Administration_comforting_filter');

    let resource_type = { redress: ['food'], ritual: ['gold', 'food'], prevention_disasters: ['food', 'lumber'] }
    let per = { redress: { food: 2.5 }, ritual: { gold: 2, food: 1.5 }, prevention_disasters: { food: 2.3, lumber: 3 } }
    for (let _type of ['redress', 'ritual', 'prevention_disasters']) {
        if (select_box.val === _type) {
            dialog.cont_obj[`content_select_${_type}`].show();
            for (let _resource of Object.values(resource_type[_type])) {
                this.cont_obj[`content_select_${_type}`].find(`.content_need_${_resource}`).text(ns_util.math(ns_cs.d.terr.population_curr.v).mul(per[_type][_resource]).number_format);
            }
        } else {
            dialog.cont_obj[`content_select_${_type}`].hide();
        }
    }
}

ns_dialog.dialogs.build_Administration_comforting.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_Administration_comforting.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_Administration_comforting.timerHandlerReal = function()
{
    if (! ns_cs.d.terr?.['comforting_up_dt']) {
        return false;
    }
    let comforting_up_dt = ns_cs.d.terr?.['comforting_up_dt'].v ?? 0;
    let limit_time = ns_util.math(comforting_up_dt).plus(900).number;
    if (ns_util.math(limit_time).lte(ns_timer.now())) {
        ns_button.buttons.build_Administration_comforting_Adjustment.setEnable();
        ns_button.buttons.build_Administration_comforting_Adjustment.obj.text(ns_i18n.t('execution'));
    } else {
        ns_button.buttons.build_Administration_comforting_Adjustment.setDisable();
        ns_button.buttons.build_Administration_comforting_Adjustment.obj.text(ns_i18n.t('time_left', [ns_util.getCostsTime(ns_util.math(limit_time).minus(ns_timer.now()).number)]));
    }
}

/* ************************************************** */
ns_button.buttons.build_Administration_comforting_close = new nsButtonSet('build_Administration_comforting_close', 'button_pop_close', 'build_Administration_comforting', { base_class: ns_button.buttons.common_close });
ns_button.buttons.build_Administration_comforting_sub_close = new nsButtonSet('build_Administration_comforting_sub_close', 'button_full', 'build_Administration_comforting', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.build_Administration_comforting_filter = new nsButtonSet('build_Administration_comforting_filter', 'button_select_box', 'build_Administration_comforting');
ns_button.buttons.build_Administration_comforting_filter.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('select_box', {select_box_id: 'build_Administration_comforting_filter'});
}

ns_button.buttons.build_Administration_comforting_Adjustment = new nsButtonSet('build_Administration_comforting_Adjustment', 'button_pop_normal', 'build_Administration_comforting');
ns_button.buttons.build_Administration_comforting_Adjustment.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Administration_comforting;
    let select_box = ns_select_box.get('build_Administration_comforting_filter');

    let post_data = {};
    post_data['selected'] = select_box.val;
    ns_xhr.post('/api/administration/comforting', post_data, (_data)=>
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        if(ns_util.math(_data.remain_dt).lt(900) && ns_util.math(_data.remain_dt).gt(0)) {
            ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_comforting_remain_time', [ns_util.getCostsTime(ns_util.math(900).minus(_data.remain_dt).number)]) })
        } else {
            let comforting_ok = {
                redress: ns_i18n.t('msg_redress_complete'), // '굶주린 백성을 구하여<br /><br /><strong>민심이 5만큼 상승하였습니다.</strong>'
                ritual: ns_i18n.t('msg_ritual_complete'), // '천제를 지내서 <br /><br /><strong>민심이 10만큼 상승하였습니다.</strong>'
                prevention_disasters: ns_i18n.t('msg_prevention_disasters_complete'), // '재해 예방을 하여 <br /><br /><strong>민심이 15만큼 상승하였습니다.</strong>'
            };
            ns_dialog.setDataOpen('message', comforting_ok[select_box.val]);
            ns_dialog.close('build_Administration_comforting');
        }
    }, { useProgress: true });
}

// 징발
ns_dialog.dialogs.build_Administration_requisition = new nsDialogSet('build_Administration_requisition', 'dialog_pop', 'size-medium', { do_content_scroll: false, do_close_all: false });
ns_dialog.dialogs.build_Administration_requisition.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.content_population_curr = new nsObject('.content_population_curr', this.obj);
    this.cont_obj.content_loyalty = new nsObject('.content_loyalty', this.obj);
    this.cont_obj.content_requisition = new nsObject('.content_requisition', this.obj);
}

ns_dialog.dialogs.build_Administration_requisition.draw = function()
{
    this.cont_obj.content_pop_title.text(ns_i18n.t('requisition'));

    this.cont_obj.content_population_curr.text(ns_util.numberFormat(ns_cs.d.terr.population_curr.v));
    this.cont_obj.content_loyalty.text(ns_util.numberFormat(ns_cs.d.terr.loyalty.v));

    this.cont_obj.content_requisition.text(ns_util.numberFormat(ns_util.toInteger(ns_cs.d.terr.population_curr.v)));

    ns_select_box.set('build_Administration_requisition_filter', 'gold');
    ns_button.buttons.build_Administration_requisition_filter.obj.text(ns_select_box.getText('build_Administration_requisition_filter'));

    this.drawSelectFilter();
}

ns_dialog.dialogs.build_Administration_requisition.drawSelectFilter = function()
{
    let dialog = ns_dialog.dialogs.build_Administration_requisition;
    let select_box = ns_select_box.get('build_Administration_requisition_filter');

    let per = { gold: 1, food: 2, lumber: 1.5, horse: 1.3, iron: 0.5 }
    dialog.cont_obj.content_requisition.text(ns_util.math(ns_cs.d.terr.population_curr.v).mul(per[select_box.val]).number_format);
}

ns_dialog.dialogs.build_Administration_requisition.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_Administration_requisition.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_Administration_requisition.timerHandlerReal = function()
{
    if (! ns_cs.d.terr?.['requisition_up_dt']) {
        return false;
    }
    let requisition_up_dt = ns_cs.d.terr?.['requisition_up_dt'].v ?? 0;
    let limit_time = ns_util.math(requisition_up_dt).plus(900).number;
    if (ns_util.math(limit_time).lte(ns_timer.now())) {
        ns_button.buttons.build_Administration_requisition_Adjustment.setEnable();
        ns_button.buttons.build_Administration_requisition_Adjustment.obj.text(ns_i18n.t('execution'));
    } else {
        ns_button.buttons.build_Administration_requisition_Adjustment.setDisable();
        ns_button.buttons.build_Administration_requisition_Adjustment.obj.text(ns_i18n.t('time_left', [ns_util.getCostsTime(ns_util.math(limit_time).minus(ns_timer.now()).number)]));
    }
}

/* ************************************************** */
ns_button.buttons.build_Administration_requisition_close = new nsButtonSet('build_Administration_requisition_close', 'button_pop_close', 'build_Administration_requisition', { base_class: ns_button.buttons.common_close });
ns_button.buttons.build_Administration_requisition_sub_close = new nsButtonSet('build_Administration_requisition_sub_close', 'button_full', 'build_Administration_requisition', { base_class: ns_button.buttons.common_sub_close });

/* ************************* */
ns_button.buttons.build_Administration_requisition_filter = new nsButtonSet('build_Administration_requisition_filter', 'button_select_box', 'build_Administration_requisition');
ns_button.buttons.build_Administration_requisition_filter.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('select_box', {select_box_id: 'build_Administration_requisition_filter'});
}

ns_button.buttons.build_Administration_requisition_Adjustment = new nsButtonSet('build_Administration_requisition_Adjustment', 'button_pop_normal', 'build_Administration_requisition');
ns_button.buttons.build_Administration_requisition_Adjustment.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Administration_requisition;
    let select_box = ns_select_box.get('build_Administration_requisition_filter');

    let post_data = {};
    post_data['code'] = select_box.val;
    ns_xhr.post('/api/administration/requisition', post_data, (_data) =>
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        if(ns_util.math(_data.remain_dt).lt(900) && ns_util.math(_data.remain_dt).gt(0)) {
            ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_requisition_remain_time', [ns_util.getCostsTime(ns_util.math(900).minus(_data.remain_dt).integer)]) });
        } else {
            ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_requisition_complete') });
        }

        ns_dialog.close('build_Administration_requisition');
    }, { useProgress: true });
}

// 영지명 변경
ns_dialog.dialogs.build_Administration_terr_title = new nsDialogSet('build_Administration_terr_title', 'dialog_pop', 'size-medium', { do_content_scroll: false, do_close_all: false });
ns_dialog.dialogs.build_Administration_terr_title.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.administration_terr_title = new nsObject('#administration_terr_title', this.obj);
    this.cont_obj.current_terr_title = new nsObject('.current_terr_title', this.obj);
}

ns_dialog.dialogs.build_Administration_terr_title.draw = function()
{
    this.cont_obj.content_pop_title.html(ns_i18n.t('territory_title_change')); // 영지명 변경
    this.cont_obj.administration_terr_title.value('');
    this.cont_obj.current_terr_title.text(ns_cs.d.terr.title.v);
}

ns_dialog.dialogs.build_Administration_terr_title.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.build_Administration_terr_title.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.build_Administration_terr_title.timerHandlerReal = function()
{
    if (! ns_cs.d.terr?.['title_change_up_dt']) {
        return false;
    }
    let title_change_up_dt = ns_cs.d.terr?.['title_change_up_dt'].v ?? 0;
    let limit_time = ns_util.math(title_change_up_dt).plus(86400).number;
    if (ns_util.math(limit_time).lte(ns_timer.now())) {
        ns_button.buttons.build_Administration_terr_title_Adjustment.setEnable();
        ns_button.buttons.build_Administration_terr_title_Adjustment.obj.text(ns_i18n.t('to_change')); // 변경하기
    } else {
        ns_button.buttons.build_Administration_terr_title_Adjustment.setDisable();
        ns_button.buttons.build_Administration_terr_title_Adjustment.obj.text(ns_i18n.t('time_left', [ns_util.getCostsTime(ns_util.math(limit_time).minus(ns_timer.now()).number)]));
    }
}

ns_button.buttons.build_Administration_terr_title_close = new nsButtonSet('build_Administration_terr_title_close', 'button_pop_close', 'build_Administration_terr_title', { base_class: ns_button.buttons.common_close });
ns_button.buttons.build_Administration_terr_title_sub_close = new nsButtonSet('build_Administration_terr_title_sub_close', 'button_full', 'build_Administration_terr_title', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.build_Administration_terr_title_filter = new nsButtonSet('build_Administration_terr_title_filter', 'button_select_box', 'build_Administration_terr_title');

ns_button.buttons.build_Administration_terr_title_Adjustment = new nsButtonSet('build_Administration_terr_title_Adjustment', 'button_pop_normal', 'build_Administration_terr_title');
ns_button.buttons.build_Administration_terr_title_Adjustment.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.build_Administration_terr_title;
    let terr_title = dialog.cont_obj.administration_terr_title.value();

    if (terr_title === ns_cs.d.terr.title.v) {
        ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_change_territory_title_same_error') }); // 현재 영지명과 다른 영지명을 입력해주십시오.
        return;
    }

    if (terr_title.length < 1) {
        ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_change_territory_title_empty_error') }); // 변경할 영지명을 입력해 주십시오.
        return;
    } else if (terr_title.length < 2) {
        ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_change_territory_title_min_error') }); // 영지명은 최소 2글자를 사용해야합니다.
        return;
    } else if (terr_title.length > 4) {
        ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_change_territory_title_max_error') }); // 영지명은 최대 4글자까지 사용할 수 있습니다.
        return;
    }

    let post_data = {};
    post_data['terr_title'] = terr_title;
    post_data['now_dt'] = ns_timer.now();
    ns_xhr.post('/api/administration/terrTitle', post_data,  (_data) =>
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        if (_data['ns_xhr_return']['code'].toLowerCase() === 'error'){
            if(_data['ns_xhr_return']['add_data'] !== null) {
                let remain_dt = _data['ns_xhr_return']['add_data']['remain_dt'] ?? 86400;
                if(ns_util.math(remain_dt).lt(86400)) {
                    // 영지명을 변경할 수 있는 시간 조건이 충족되지 않으면 닫음
                    ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_change_territory_title_remain', [ns_util.getCostsTime(ns_util.math(86400).minus(remain_dt).integer)]) });
                    ns_dialog.close('build_Administration_terr_title');
                }
            } else {
                // 입력에 대한 문제로 에러가 났다면 닫지 않음
                ns_dialog.setDataOpen('message', { text: _data['ns_xhr_return']['message']});
            }
        } else {
            _data = _data['ns_xhr_return']['add_data'];

            ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_change_territory_title_complete') }); // 영지명이 변경되었습니다.
            ns_dialog.close('build_Administration_terr_title'); // 영지명 변경에 성공하면 닫음
        }
    }, { useProgress: true });
}