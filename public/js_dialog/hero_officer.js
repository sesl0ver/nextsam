ns_dialog.dialogs.hero_officer = new nsDialogSet('hero_officer', 'dialog_pop', 'size-medium', { do_close_all: false });
ns_dialog.dialogs.hero_officer.hero_list = null;
ns_dialog.dialogs.hero_officer.action_title = null;

ns_dialog.dialogs.hero_officer.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.content_action_title = new nsObject('.content_action_title', this.obj);
    this.cont_obj.tbody = new nsObject('tbody', this.obj);
}

ns_dialog.dialogs.hero_officer.draw = function()
{
    if (this.data && this.data.m_offi_pk) {
        this.cont_obj.content_pop_title.text(ns_i18n.t(`change_office`));
        this.action_title = ns_i18n.t(`change`);
    } else {
        this.cont_obj.content_pop_title.text(ns_i18n.t(`appoint_office_confer`));
        this.action_title = ns_i18n.t(`appoint`);
    }

    this.cont_obj.content_action_title.text(this.action_title);
    this.drawList();
}

ns_dialog.dialogs.hero_officer.drawList = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_officer;

    ns_xhr.post('/api/heroManage/usedOfficer', {}, dialog.drawRemote);
}

ns_dialog.dialogs.hero_officer.drawRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.hero_officer;

    dialog.hero_list = _data.hero_list;

    let officer_info = {};
    for (let [k, d] of Object.entries(_data.hero_list)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        if (d.m_offi_pk) {
            officer_info[d.m_offi_pk] = { hero_pk: d.hero_pk, m_hero_pk: d.m_hero_pk, status: d.status, status_cmd: d.status_cmd };
        }
    }


    // 초기화
    dialog.hero_list = [];
    dialog.buttonClear();
    dialog.cont_obj.tbody.empty();

    // 목록 리스트 캐싱
    dialog.sorted = [];
    for (let [k, d] of Object.entries(ns_cs.m.offi))  {
        dialog.sorted.push(d);
    }

    if (dialog.sorted.length > 1) {
        dialog.sorted = ns_util.arraySort(dialog.sorted, -1, 'orderno');
    }

    for (let d of dialog.sorted) {
        if (ns_util.math(ns_cs.d.lord.level.v).lt(d.active_level) || ns_util.math(d.active_level).eq(0) && ns_util.math(d.m_offi_pk).gt(ns_cs.d.lord.m_offi_pk.v)) {
            continue;
        }

        let is_used = (! officer_info[d.m_offi_pk]) ? false : officer_info[d.m_offi_pk];
        let data = { pk: d.m_offi_pk, is_used: is_used };

        let columns = [];
        let tr = document.createElement('tr');

        let col = document.createElement('td');
        if (is_used) {
            if (dialog.data && dialog.data.m_offi_pk && d.m_offi_pk !== dialog.data.m_offi_pk && is_used.status_cmd === 'I') {
                col.classList.add('text_condition_yes');
            } else {
                col.classList.add('text_condition_disable');
            }
        } else {
            col.classList.add('text_condition_yes');
        }
        // col.innerHTML = d.title + '<br />(' + d.title_hanja + ')';
        col.innerHTML = ns_i18n.t(`office_title_${d.m_offi_pk}`) + '<br />(' + ns_i18n.t(`office_origin_${d.m_offi_pk}`) + ')';

        let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
        button.setAttribute('id', `ns_button_hero_officer_set_${data.pk}`);
        col.append(button);

        columns.push(col);

        col = document.createElement('td');
        if (is_used) {
            let classname = 'text_condition_disable';
            if (dialog.data && dialog.data.m_offi_pk && d.m_offi_pk !== dialog.data.m_offi_pk && is_used.status_cmd === 'I') {
                classname = '';
            }
            if (classname !== '') {
                col.classList.add(classname);
            }
            col.innerHTML = ns_hero.getNameWithLevel(is_used.hero_pk, is_used.m_hero_pk, null, true);
        } else {
            // col.innerText = dialog.action_title + system_text.possible;
            col.innerText = '-';
        }
        columns.push(col);

        for (let _type of ['leadership', 'mil_force', 'intellect', 'politics', 'charm']) {
            col = document.createElement('td');
            col.innerText = d[`stat_plus_${_type}`];
            columns.push(col);
        }

        // col = document.createElement('td');
        // col.innerText = ns_util.numberFormat(d.employment_fee);
        // columns.push(col);

        for (let column of columns) {
            tr.appendChild(column);
        }
        dialog.cont_obj.tbody.append(tr);

        let button_id = `hero_officer_set_${data.pk}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'hero_officer');
        ns_button.buttons[button_id].mouseUp = function ()
        {
            if (dialog.data && dialog.data.m_offi_pk) {
                if (data.is_used) {
                    if (data.is_used.status_cmd === 'I' && data.pk !== dialog.data.m_offi_pk) {
                        if (dialog.data.callbackFunc) {
                            let _text = ns_i18n.t('msg_officer_change_confirm', [ns_i18n.t(`office_title_${d.m_offi_pk}`), ns_hero.getName(is_used.hero_pk, is_used.m_hero_pk)]); // '{{1}} {{2}}<br />해당 영웅과 관직이 교체됩니다.';
                            ns_dialog.setDataOpen('confirm', { text: _text, okFunc: () =>
                                {
                                    dialog.data.callbackFunc(data.pk, data.is_used.hero_pk);
                                    dialog.close();
                                }
                            });
                        }
                    }
                } else {
                    if (dialog.data.callbackFunc) {
                        dialog.data.callbackFunc(data.pk);
                    }
                    dialog.close();
                }
            } else {
                if (data.is_used) {
                    // ns_dialog.setDataOpen('message', '이미 다른 영웅이 등용되어있습니다. <br>등용 가능한 관직을 선택해 주세요.');
                    return;
                }
                if (dialog.data.callbackFunc) {
                    dialog.data.callbackFunc(data.pk);
                }
                dialog.close();
            }
        }
        dialog.buttons.push(ns_button.buttons[button_id]);
    }

    dialog.appo_hero_pk = null; // 리스트를 다 만들고 나면 다시 비우기
}

/* ************************************************** */

ns_button.buttons.hero_officer_close = new nsButtonSet('hero_officer_close', 'button_pop_close', 'hero_officer', { base_class: ns_button.buttons.common_close});
ns_button.buttons.hero_officer_sub_close = new nsButtonSet('hero_officer_sub_close', 'button_full', 'hero_officer', { base_class: ns_button.buttons.common_sub_close });

