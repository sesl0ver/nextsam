ns_dialog.dialogs.magic_cube = new nsDialogSet('magic_cube', 'dialog_full', 'size-full', { do_content_scroll: false });
ns_dialog.dialogs.magic_cube.is_start_animate = false;
ns_dialog.dialogs.magic_cube.action = 'start';
ns_dialog.dialogs.magic_cube.selected_item_pk = null;
ns_dialog.dialogs.magic_cube.ref_interval_id = null;
ns_dialog.dialogs.magic_cube.prev_double_chance = null;
ns_dialog.dialogs.magic_cube.double_chance = null;
ns_dialog.dialogs.magic_cube.effect_call_cnt = 0;
ns_dialog.dialogs.magic_cube.previous_show = true;

ns_dialog.dialogs.magic_cube.cacheContents = function()
{
    this.cont_obj.item_list_wrap = new nsObject('.item_list_wrap', this.obj);
    this.cont_obj.item_list = [];
    for (let i of Array.from({length: 16}, (_, i) => i + 1)) {
        this.cont_obj.item_list.push(ns_button.buttons[`magic_cube_item_${i}`].obj);
    }
    this.cont_obj.own_item_count = new nsObject('.own_item_count', this.obj);
    this.cont_obj.double_chance = new nsObject('.double_chance', this.obj);
}

ns_dialog.dialogs.magic_cube.draw = function()
{
    this.startInit();
    this.drawInit('start');
}

ns_dialog.dialogs.magic_cube.erase = function()
{
    if (this.is_start_animate === true) {
        this.is_start_animate = false;
    }
    this.data = null;
    this.startInit();
}

ns_dialog.dialogs.magic_cube.startInit = function()
{
    let dialog = ns_dialog.dialogs.magic_cube;

    dialog.action = 'start';

    dialog.prev_double_chance = null;
    dialog.double_chance = null;

    ns_button.buttons.magic_cube_select_item.obj.removeCss().addCss('selected_item');

    if (dialog.ref_interval_id) {
        clearInterval(dialog.ref_interval_id);
    }

    for (let item of dialog.cont_obj.item_list) {
        item.removeCss();
    }

    dialog.cont_obj.own_item_count.text((! ns_cs.d.item[500061]) ? 0 : ns_util.numberFormat(ns_cs.d.item[500061].item_cnt));
}

ns_dialog.dialogs.magic_cube.drawInit = function(action)
{
    let dialog = ns_dialog.dialogs.magic_cube;
    if (dialog.is_start_animate) {
        return;
    }

    dialog.effect_call_cnt = 0;
    dialog.is_start_animate = false;

    // 버튼 설정
    if (action === 'start') {
        if (! dialog.data) {
            ns_button.buttons.magic_cube_start.obj.text(ns_i18n.t('magic_cube_start'));
        } else {
            if (dialog.data.autostart) {
                ns_button.buttons.magic_cube_start.mouseUp();
            }
        }
    } else if (action === 'continuously') {
        if (dialog.double_chance === 'Y') {
            ns_button.buttons.magic_cube_start.obj.text(ns_i18n.t('magic_cube_double_chance'));
        } else {
            ns_button.buttons.magic_cube_start.obj.text(ns_i18n.t('magic_cube_continue'));
        }
    }

    if (action === 'start') {
        ns_button.buttons.magic_cube_select_item.obj.removeCss().addCss('selected_item');
    }

    dialog.cont_obj.double_chance.hide();
    if (dialog.double_chance === 'Y') {
        dialog.cont_obj.double_chance.show();
    }

    dialog.action = action;
}

ns_dialog.dialogs.magic_cube.drawStart = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.magic_cube;

    dialog.cont_obj.double_chance.hide();
    if (dialog.ref_interval_id) {
        clearInterval(dialog.ref_interval_id);
    }

    dialog.cont_obj.own_item_count.text((! _data?.item_cnt) ? 0 : ns_util.numberFormat(_data.item_cnt));

    dialog.selected_item_pk = _data.select_item; // 선택된 아이템
    dialog.prev_double_chance = dialog.double_chance; // 이전 더블 찬스
    dialog.item_list = _data.item_list; // 리스트용 아이템
    dialog.double_chance = _data.double_chance; // 현재 더블 찬스

    ns_button.buttons.magic_cube_select_item.obj.removeCss().addCss('selected_item');
    ns_button.buttons.magic_cube_start.setDisable(); // 버튼 비활성화
    dialog.drawItemList();
    dialog.drawAnimate();
}

ns_dialog.dialogs.magic_cube.drawItemList = function()
{
    let dialog = ns_dialog.dialogs.magic_cube;

    // dialog.s.cont_selected_effect.hide();

    let i = 0;
    for (let item of dialog.cont_obj.item_list) {
        item.removeCss();
        item.addCss(`item_image_${dialog.item_list[i]}`);
        i++;
    }
}

ns_dialog.dialogs.magic_cube.drawAnimate = function()
{
    let dialog = ns_dialog.dialogs.magic_cube;
    dialog.is_start_animate = true;


    let move_one_time = 60; // 한개 움직일 기본 속도는 60ms
    let total_move_time = (dialog.prev_double_chance === 'Y')? 4000 : 2500; // 전체 움직일 시간은 2초(더블 찬스 일때)
    let one_cycle_time = move_one_time * 16; // 한바퀴 도는 시간 960ms

    let end_item_index = -1;
    do {
        end_item_index++; // 포커스가 멈출 아이템은 당첨 아이템
    }  while (dialog.item_list[end_item_index] !== dialog.selected_item_pk);

    let start_item_index = Math.floor(Math.random() * 16); // 포커스가 시작하는 아이템은 완전 랜덤
    let distance = (start_item_index > end_item_index) ? (16 + end_item_index - start_item_index) : (end_item_index - start_item_index);

    if (distance < 5) {
        distance = distance + 16; // 남은 거리가 너무 짧거나 시작 위치가 당첨 아이템인 경우 바로 멈출 수 없으므로 한바퀴 더 도는 걸로 계산
    }
    if (dialog.prev_double_chance === 'Y') {
        distance = distance + 16; // 더블찬스일때는 2바퀴를 돌아야함
    }

    let base_move_time = move_one_time * distance;
    let remain_move_time = total_move_time - one_cycle_time - base_move_time; // 이 값으로

    // 감속 시작
    // dialog.move_focus(start_item_index, 0, distance, 700);
    dialog.moveFocus(start_item_index, 0, distance, remain_move_time); // 재귀 호출
}

ns_dialog.dialogs.magic_cube.moveFocus = function(_now_index, _run_count, _limit, _remain_time)
{
    let dialog = ns_dialog.dialogs.magic_cube;
    let prev_index = _now_index - 1;
    prev_index = (prev_index < 0) ? 15 : prev_index;
    let unselect_index = prev_index - 1;
    unselect_index = (unselect_index < 0) ? 15 : unselect_index;

    // dialog.s.magic_cube_item_select_wrap.show();

    let current = dialog.cont_obj.item_list[_now_index];
    let previous = dialog.cont_obj.item_list[prev_index];
    let unselect = dialog.cont_obj.item_list[unselect_index];

    current.addCss('current');
    previous.removeCss('current');
    if (dialog.previous_show) {
        previous.addCss('previous');
    }
    unselect.removeCss('previous');

    let default_cycle = 16;
    if (_run_count < default_cycle) {
        ns_sound.play('magic_cube_spin');
        setTimeout("ns_dialog.dialogs.magic_cube.moveFocus(" + ((_now_index + 1) % 16) + "," + (_run_count + 1) + "," + _limit + "," + (_remain_time) + ")", 60);
    } else {
        // 남은 거리로 감속 시작
        if (_limit > 0) {
            let decrease_second = _remain_time;
            let i = 0;
            while (i++ < _limit) {
                let accelerate = (dialog.prev_double_chance === 'Y')? 0.7 : 0.65;
                decrease_second = Math.ceil(decrease_second * accelerate);
            }

            if (decrease_second + 60 > 100) {
                dialog.previous_show = false;
            }

            ns_sound.play('magic_cube_spin');
            setTimeout("ns_dialog.dialogs.magic_cube.moveFocus(" + ((_now_index + 1) % 16) + "," + (_run_count + 1) + "," + (_limit - 1) + "," + (_remain_time) + ")", (decrease_second + 60));
        } else {
            ns_sound.play('magic_cube_finish');
            dialog.drawFinish();
        }
    }
}

ns_dialog.dialogs.magic_cube.drawFinish = function()
{
    let dialog = ns_dialog.dialogs.magic_cube;
    dialog.is_start_animate = false;
    ns_button.buttons.magic_cube_select_item.obj.addCss(`item_image_${dialog.selected_item_pk}`).addCss('selector_effect');
    if (dialog.prev_double_chance === 'Y') {
        ns_button.buttons.magic_cube_select_item.obj.addCss('double_chance_x2');
    }
    ns_button.buttons.magic_cube_start.setEnable();
    dialog.drawInit('continuously');
}

ns_dialog.dialogs.magic_cube.startMagicCube = function()
{
    let dialog = ns_dialog.dialogs.magic_cube;
    // 이미 시작됬으면 중지
    if (dialog.is_start_animate !== false) {
        return false;
    }

    let post_data = {}
    post_data['action'] = dialog.action;
    post_data['autostart'] = (!dialog.data || !dialog.data?.autostart) ? 'N' : 'Y';

    ns_xhr.post('/api/magicCube/roll', post_data, dialog.drawStart, { useProgress: true });
}

/* ************************************************** */

ns_button.buttons.magic_cube_close = new nsButtonSet('magic_cube_close', 'button_back', 'magic_cube', {base_class:ns_button.buttons.common_close});
ns_button.buttons.magic_cube_sub_close = new nsButtonSet('magic_cube_sub_close', 'button_full', 'magic_cube', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.magic_cube_close_all = new nsButtonSet('magic_cube_close_all', 'button_close_all', 'magic_cube', {base_class:ns_button.buttons.common_close_all});

// ns_button.buttons.game_help_magic_cube = new nsButtonSet('game_help_magic_cube', 'button_dlg_help', 'magic_cube', {base_class:ns_button.buttons.buil_help});

/* ********** */

ns_button.buttons.magic_cube_cash = new nsButtonSet('magic_cube_cash', 'button_empty', 'magic_cube');
ns_button.buttons.magic_cube_cash.mouseUp = function(_e)
{
    ns_engine.buyQbig();
}

ns_button.buttons.magic_cube_charge_button = new nsButtonSet('magic_cube_charge_button', null, 'magic_cube');
ns_button.buttons.magic_cube_charge_button.mouseUp = function(_e)
{
    //ns_engine.buyQbig();

    console.log('clicked!');
}

ns_button.buttons.magic_cube_start = new nsButtonSet('magic_cube_start', 'button_magic_cube_start', 'magic_cube');
ns_button.buttons.magic_cube_start.mouseUp = function(_e)
{
    try {
        let dialog = ns_dialog.dialogs.magic_cube;

        // 이미 시작됬으면 중지
        if (dialog.is_start_animate !== false) {
            return;
        }

        if ((dialog.data && dialog.data.autostart === true) || (ns_cs.d.item['500061'] && ns_cs.d.item['500061'].item_cnt > 0)) {
            dialog.startMagicCube();
        } else {
            if(ns_util.math(ns_cs.d.cash.qbig.v).gt(0)) {
                let _confirm_text = ns_i18n.t('msg_need_buy_item', [ns_cs.m.item[500061].title, `<span class="content_item_qbig_amount">${ns_util.numberFormat(ns_cs.m.item[500061].price)}</span>`, `<span class="content_item_qbig_amount">${ns_util.numberFormat(ns_cs.d.cash.qbig.v)}</span>`])
                ns_dialog.setDataOpen('confirm', { text: _confirm_text,
                    okFunc: () =>
                    {
                        // 행운의 주화 (500061) 가격보다 캐시가 적은지 체크
                        if (ns_util.math(ns_cs.d.cash.qbig.v).lt(ns_cs.m.item[500061].price)) {
                            ns_dialog.setDataOpen('confirm', { text : ns_i18n.t('msg_buy_qbig_confirm'), // 큐빅이 부족합니다.<br /><br />큐빅을 구매하시겠습니까?
                                okFunc : () =>
                                {
                                    ns_engine.buyQbig();
                                }
                            });
                        } else {
                            dialog.startMagicCube();
                        }
                    }
                });
            } else {
                ns_dialog.setDataOpen('confirm', { text : ns_i18n.t('msg_buy_qbig_confirm'), // 큐빅이 부족합니다.<br /><br />큐빅을 구매하시겠습니까?
                    okFunc : () =>
                    {
                        ns_engine.buyQbig();
                    }
                });
            }
        }
    } catch (e) {
        console.error(e);
    }
}

ns_button.buttons.magic_cube_select_item = new nsButtonSet('magic_cube_select_item', null, 'magic_cube');
ns_button.buttons.magic_cube_select_item.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.magic_cube;
    ns_dialog.setDataOpen('reward_information', { m_item_pk: dialog.selected_item_pk })
}

ns_button.buttons.magic_cube_item_1 = new nsButtonSet('magic_cube_item_1', null, 'magic_cube');
ns_button.buttons.magic_cube_item_1.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.magic_cube;
    let index = ns_util.math(this.tag_id.split('_').pop()).minus(1).number;
    ns_dialog.setDataOpen('reward_information', { m_item_pk: dialog.item_list[index] })
}

ns_button.buttons.magic_cube_item_2 = new nsButtonSet('magic_cube_item_2', null, 'magic_cube', { base_class: ns_button.buttons.magic_cube_item_1 });
ns_button.buttons.magic_cube_item_3 = new nsButtonSet('magic_cube_item_3', null, 'magic_cube', { base_class: ns_button.buttons.magic_cube_item_1 });
ns_button.buttons.magic_cube_item_4 = new nsButtonSet('magic_cube_item_4', null, 'magic_cube', { base_class: ns_button.buttons.magic_cube_item_1 });
ns_button.buttons.magic_cube_item_5 = new nsButtonSet('magic_cube_item_5', null, 'magic_cube', { base_class: ns_button.buttons.magic_cube_item_1 });
ns_button.buttons.magic_cube_item_6 = new nsButtonSet('magic_cube_item_6', null, 'magic_cube', { base_class: ns_button.buttons.magic_cube_item_1 });
ns_button.buttons.magic_cube_item_7 = new nsButtonSet('magic_cube_item_7', null, 'magic_cube', { base_class: ns_button.buttons.magic_cube_item_1 });
ns_button.buttons.magic_cube_item_8 = new nsButtonSet('magic_cube_item_8', null, 'magic_cube', { base_class: ns_button.buttons.magic_cube_item_1 });
ns_button.buttons.magic_cube_item_9 = new nsButtonSet('magic_cube_item_9', null, 'magic_cube', { base_class: ns_button.buttons.magic_cube_item_1 });
ns_button.buttons.magic_cube_item_10 = new nsButtonSet('magic_cube_item_10', null, 'magic_cube', { base_class: ns_button.buttons.magic_cube_item_1 });
ns_button.buttons.magic_cube_item_11 = new nsButtonSet('magic_cube_item_11', null, 'magic_cube', { base_class: ns_button.buttons.magic_cube_item_1 });
ns_button.buttons.magic_cube_item_12 = new nsButtonSet('magic_cube_item_12', null, 'magic_cube', { base_class: ns_button.buttons.magic_cube_item_1 });
ns_button.buttons.magic_cube_item_13 = new nsButtonSet('magic_cube_item_13', null, 'magic_cube', { base_class: ns_button.buttons.magic_cube_item_1 });
ns_button.buttons.magic_cube_item_14 = new nsButtonSet('magic_cube_item_14', null, 'magic_cube', { base_class: ns_button.buttons.magic_cube_item_1 });
ns_button.buttons.magic_cube_item_15 = new nsButtonSet('magic_cube_item_15', null, 'magic_cube', { base_class: ns_button.buttons.magic_cube_item_1 });
ns_button.buttons.magic_cube_item_16 = new nsButtonSet('magic_cube_item_16', null, 'magic_cube', { base_class: ns_button.buttons.magic_cube_item_1 });

