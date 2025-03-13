// 다이얼로그
ns_dialog.dialogs.build_construct = new nsDialogSet('build_construct', 'dialog_building', 'size-large');
ns_dialog.dialogs.build_construct.cacheContents = function()
{
    for (let m_buil_pk = 200200; m_buil_pk <= 201500; m_buil_pk = m_buil_pk + 100) {
        let div = document.createElement('div');
        div.classList.add('ns_panel_white');
        div.classList.add('build_construct_item');
        div.classList.add(`item_${m_buil_pk}`);

        let button = document.createElement('span');
        button.setAttribute('id', `ns_button_build_construct_desc_${m_buil_pk}`);

        let sub = document.createElement('div');
        sub.classList.add('build_construct_item_bottom');
        sub.classList.add('ns_panel_flex');

        let title = document.createElement('span');
        title.classList.add('build_construct_item_title');
        title.classList.add('ns_panel_flex_item_auto');
        title.innerText = ns_i18n.t(`build_title_${m_buil_pk}`);

        let count = document.createElement('span');
        count.classList.add('build_construct_item_count');
        count.classList.add(`build_${m_buil_pk}`);
        count.classList.add('ns_panel_flex_right');
        count.innerHTML = `${ns_i18n.t('max')}: 0/0&nbsp;`;

        sub.appendChild(title);
        sub.appendChild(count);

        div.appendChild(button);
        div.appendChild(sub);

        this.cont_obj.content.append(div);

        ns_button.buttons[`build_construct_desc_${m_buil_pk}`] = new nsButtonSet(`build_construct_desc_${m_buil_pk}`, 'button_build_construct_desc', 'build_construct', { base_class: ns_button.buttons.build_construct_desc })
        this.cont_obj[`build_construct_item_${m_buil_pk}`] = new nsObject(`.build_construct_item.item_${m_buil_pk}`, this.obj);
    }
}

ns_dialog.dialogs.build_construct.draw = function (_e)
{
    ns_dialog.closeAll();
    this.visibles = [];
    for (let i = 200200; i < 201600; i = i + 100) {
        let m = ns_cs.m.buil[i];
        this.cont_obj[`build_construct_item_${i}`].hide();
        if ((this.data.castle_type === 'bdic' && m.type === 'I') || (this.data.castle_type === 'bdoc' && m.type === 'O')) {
            let count = ns_cs.getBuildList(m.m_buil_pk, true);
            if (m.yn_duplication === 'N') { // 여러개 건설 불가능한 건물인 경우
                if (count < 1) { // 값이 있는지만 확인
                    this.visibles.push(i);
                    this.cont_obj[`build_construct_item_${i}`].show();
                }
            } else {
                if (count < ns_cs.getBuildLimitCount(i)) { // 최대 갯수를 확인
                    this.visibles.push(i);
                    this.cont_obj[`build_construct_item_${i}`].show();
                }
            }
        }
    }

    // 최초에 1회 수동으로 검사 수행
    this.timerHandlerProc();
}

ns_dialog.dialogs.build_construct.erase = function (_e)
{
    ns_dialog.close('pop_building_desc');
}

ns_dialog.dialogs.build_construct.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    ns_timer.timers.build_construct = new nsTimerSet(ns_dialog.dialogs.build_construct.timerHandlerProc, ns_engine.cfg.big_delay, false);
    ns_timer.timers.build_construct.init();
    return ns_timer.timers.build_construct;
}

ns_dialog.dialogs.build_construct.timerHandlerProc = function ()
{
    let dialog = ns_dialog.dialogs.build_construct;
    /*let visible = dialog.visibles, z;
    for (let i = 0; i < visible.length; i++) {
        z = ns_cs.m.buil[visible[i]]['level']['1']['m_cond_pk'];
        if (ns_check_condition.checkAll(z)) {
            // qbw_btn.btns['buil_cons_build_' + visibles[i]].setEnable();
        } else {
            // qbw_btn.btns['buil_cons_build_' + visibles[i]].setDisable();
        }
    }*/

    for (let m_buil_pk = 200200; m_buil_pk <= 201500; m_buil_pk = m_buil_pk + 100) {
        let count = dialog.cont_obj.content.find(`.build_${m_buil_pk}`);
        let max = ns_cs.getBuildLimitCount(m_buil_pk);
        let current = ns_cs.getBuildList(m_buil_pk, true);
        count.html(`${ns_i18n.t('max')}: ${current}/${max}&nbsp;`);
    }
}

ns_dialog.dialogs.build_construct.checkBuildConcurrOver = function ()
{
    let is_used_ext_queue = false,
        buil_curr_count = 0,
        buil_max_count = 3;

    for (let [k, d] of Object.entries(ns_cs.d.time)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        if(d.description.substring(0, 6) === 500535) {
            buil_max_count = 5;
        }

        if (typeof d.description != 'undefined' && d.description.substring(7) === 'queue' && d.status === 'P') {
            is_used_ext_queue = true;
        }
        if (d.queue_type === 'C' && (d.queue_action === 'U' || d.queue_action === 'D')) {
            buil_curr_count++;
        }
    }
    let max = (is_used_ext_queue === true) ? buil_max_count : 1;
    if (buil_curr_count < max) {
        return false;
    } else {
        if (is_used_ext_queue === true) {
            // 건설 제한 초과 메시지
            let msg_buil_cons_once = ns_i18n.t('msg_construction_max_queue_error', [buil_max_count]);
            ns_dialog.setDataOpen('message', { error_msg: 'error', text: msg_buil_cons_once });
        } else {
            // 건설은 기본적으로 1개가 가능하며<br />"건설허가서" 아이템을 사용하여 건설을<br />동시에 3개까지 진행할 수 있습니다.<br /><br />"건설허가서" 를 사용하시겠습니까?
            ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_construction_queue_item_confirm'),
                okFunc: () =>
                {
                    ns_dialog.setDataOpen('item_use', { m_item_pk: 500102 });
                },
                noFunc: () => {}
            });
        }
        return true;
    }
}


/* button */
ns_button.buttons.build_construct_desc = new nsButtonSet('build_construct_desc', null, 'build_construct');
ns_button.buttons.build_construct_desc.mouseUp = function (_e)
{
    let dialog = ns_dialog.dialogs.build_construct;
    let code = _e.target.id.split('_');

    // 명령 창 띄우기
    if (! dialog.checkBuildConcurrOver()) {
        ns_dialog.setDataOpen('build_upgrade', {
            'm_buil_pk': code[5],
            'castle_type': dialog.data.castle_type,
            'castle_pk': dialog.data.castle_pk
        });
    }
}

// ns_button.buttons.build_construct_desc_200200 = new nsButtonSet('build_construct_desc_200200', 'button_build_construct_desc', 'build_construct', { base_class: ns_button.buttons.build_construct_desc });
// ns_button.buttons.build_construct_desc_200300 = new nsButtonSet('build_construct_desc_200300', 'button_build_construct_desc', 'build_construct', { base_class: ns_button.buttons.build_construct_desc });
// ns_button.buttons.build_construct_desc_200400 = new nsButtonSet('build_construct_desc_200400', 'button_build_construct_desc', 'build_construct', { base_class: ns_button.buttons.build_construct_desc });
// ns_button.buttons.build_construct_desc_200500 = new nsButtonSet('build_construct_desc_200500', 'button_build_construct_desc', 'build_construct', { base_class: ns_button.buttons.build_construct_desc });
// ns_button.buttons.build_construct_desc_200600 = new nsButtonSet('build_construct_desc_200600', 'button_build_construct_desc', 'build_construct', { base_class: ns_button.buttons.build_construct_desc });
// ns_button.buttons.build_construct_desc_200700 = new nsButtonSet('build_construct_desc_200700', 'button_build_construct_desc', 'build_construct', { base_class: ns_button.buttons.build_construct_desc });
// ns_button.buttons.build_construct_desc_200800 = new nsButtonSet('build_construct_desc_200800', 'button_build_construct_desc', 'build_construct', { base_class: ns_button.buttons.build_construct_desc });
// ns_button.buttons.build_construct_desc_200900 = new nsButtonSet('build_construct_desc_200900', 'button_build_construct_desc', 'build_construct', { base_class: ns_button.buttons.build_construct_desc });
// ns_button.buttons.build_construct_desc_201000 = new nsButtonSet('build_construct_desc_201000', 'button_build_construct_desc', 'build_construct', { base_class: ns_button.buttons.build_construct_desc });
// ns_button.buttons.build_construct_desc_201100 = new nsButtonSet('build_construct_desc_201100', 'button_build_construct_desc', 'build_construct', { base_class: ns_button.buttons.build_construct_desc });
// ns_button.buttons.build_construct_desc_201200 = new nsButtonSet('build_construct_desc_201200', 'button_build_construct_desc', 'build_construct', { base_class: ns_button.buttons.build_construct_desc });
// ns_button.buttons.build_construct_desc_201300 = new nsButtonSet('build_construct_desc_201300', 'button_build_construct_desc', 'build_construct', { base_class: ns_button.buttons.build_construct_desc });
// ns_button.buttons.build_construct_desc_201400 = new nsButtonSet('build_construct_desc_201400', 'button_build_construct_desc', 'build_construct', { base_class: ns_button.buttons.build_construct_desc });
// ns_button.buttons.build_construct_desc_201500 = new nsButtonSet('build_construct_desc_201500', 'button_build_construct_desc', 'build_construct', { base_class: ns_button.buttons.build_construct_desc });

ns_button.buttons.build_construct_close = new nsButtonSet('build_construct_close', 'button_back', 'build_construct', { base_class: ns_button.buttons.common_close });
ns_button.buttons.build_construct_sub_close = new nsButtonSet('build_construct_sub_close', 'button_full', 'build_construct', { base_class: ns_button.buttons.common_sub_close });