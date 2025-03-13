ns_button.buttons.common_open_dialog_word5 = new nsButtonSet('common_open_dialog_word5', 'button_empty', null, { bounding_box_type: 'fit' });
ns_button.buttons.common_open_dialog_word5.mouseUp = function(_e)
{
    ns_dialog.open(this.tag_id.substring(5));
};

ns_button.buttons.common_close = new nsButtonSet('common_close', 'button_empty', null, { bounding_box_type: 'fit' });
ns_button.buttons.common_close.mouseUp = function(_e)
{
    // 규칙성에 의한 id 탐색
    let match = this.tag_id.matchAll(/^([a-zA-Z0-9_]*)_close$/g);
    ns_dialog.close(match.next().value[1]);
}

ns_button.buttons.common_sub_close = new nsButtonSet('common_sub_close', 'button_empty', null, { bounding_box_type: 'fit' });
ns_button.buttons.common_sub_close.mouseUp = function(_e)
{
    // 규칙성에 의한 id 탐색
    let match = this.tag_id.matchAll(/^([a-zA-Z0-9_]*)_sub_close$/g);
    ns_dialog.close(match.next().value[1]);
}

ns_button.buttons.common_close_all = new nsButtonSet('common_close_all', 'button_empty', null, { bounding_box_type: 'fit' });
ns_button.buttons.common_close_all.mouseUp = function(_e)
{
    ns_dialog.closeAll();
}

/* ************************************************** */

/*ns_button.buttons.build_desc = new nsButtonSet('build_desc', 'button_empty', null);
ns_button.buttons.build_desc.mouseUp = function(_e)
{
    let z_arr = this.tag_id.split('_');
    let tag_id = z_arr[0] + '_' + z_arr[2];
    let dialog = ns_dialog.dialogs[tag_id];
    let alias = z_arr[2];
    let m = ns_cs.m.buil[alias];
    let _button = ns_button.buttons[this.tag_id];

    if (_button.clicked) {
        // TODO - 글자 제어가 좋지 못함.
        //ns_button.buttons[this.tag_id].obj.text('▼ 설명표시');

        dialog.cont_obj.content_build_desc.hide();

        if (m.yn_hero_assign === 'Y') {
            dialog.cont_obj.content_build_desc_sm.show();
        } else {
            dialog.cont_obj.content_build_desc_wrap.hide();
        }
    } else {
        //ns_button.buttons[this.tag_id].obj.text('▼ 설명숨김');

        dialog.cont_obj.content_build_desc.show();

        if (m.yn_hero_assign === 'Y') {
            dialog.cont_obj.content_build_desc_sm.hide();
        } else {
            dialog.cont_obj.content_build_desc_wrap.show();
        }
    }

    _button.toggleClicked();
    // dlg.contentRefresh();
}*/

ns_button.buttons.build_assign = new nsButtonSet('build_assign', 'button_empty', null);
ns_button.buttons.build_assign.mouseUp = function(_e)
{
    let z_arr = this.tag_id.split('_');
    let alias = z_arr.pop();
    let tag_id = `build_${alias}`;
    let dialog = ns_dialog.dialogs[tag_id];
    let _castle_pk = dialog.data.castle_pk;
    let m = ns_cs.m.buil[alias];
    let d = ns_cs.d.bdic[_castle_pk];

    if (d.level < 1) {
        ns_dialog.setDataOpen('message', '영웅 배속은 건설 완료 후 가능 합니다.');
        return;
    }
    let hero_select_data = {};
    hero_select_data.type = 'assign';
    hero_select_data.type_data = m.m_buil_pk;
    hero_select_data.nosel_title = m.title;
    hero_select_data.nosel_desc = m.description;

    // TODO - hero_select_data 는 instant data 역할을 확실히 수행하나?
    if (d.assign_hero_pk) {
        hero_select_data.prev_hero_pk = d.assign_hero_pk;
        hero_select_data.prev_hero_undo = (m.yn_hero_assign_required !== 'Y');
        hero_select_data.selector_use = false;
    } else {
        hero_select_data.selector_use = true;
    }

    hero_select_data.sort_stat_type = code_set['hero_stat_type'][m.sort_hero_stat_type];
    hero_select_data.limit_stat_type = code_set['hero_stat_type'][m.sort_hero_stat_type];
    hero_select_data.limit_stat_value = 1;

    let post_data = { };
    post_data['castle_pk'] = _castle_pk;

    let remoteProc = function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.draw();
    };

    hero_select_data.do_callback = function(_data)
    {
        post_data['hero_pk'] = _data;
        ns_xhr.post('/api/hero/assign', post_data, remoteProc, { useProgress: true });
    };

    hero_select_data.undo_callback = function(_data)
    {
        ns_xhr.post('/api/hero/unAssign', post_data, remoteProc, { useProgress: true });
    };

    ns_dialog.setDataOpen('hero_select', hero_select_data);
}

ns_button.buttons.build_cons =  new nsButtonSet('build_cons', 'button_empty', null);
ns_button.buttons.build_cons.mouseUp = function(_e)
{
    let z_arr = this.tag_id.split('_');
    let alias = z_arr[2];
    let m = ns_cs.m.buil[alias];
    let castle_type = (m.type === 'O') ? 'bdoc' : 'bdic';
    if (ns_cs.getBuildLimitCount(m.m_buil_pk) <= ns_cs.getBuildList(m.m_buil_pk, true)) {
        ns_dialog.setDataOpen('message', '추가 건설이 불가능한 건물입니다.')
        return;
    }
    ns_dialog.setDataOpen('build_upgrade', { m_buil_pk: m.m_buil_pk, castle_type: castle_type, castle_pk: ns_cs.getEmptyTile(m.type) });
}

ns_button.buttons.build_upgrade = new nsButtonSet('build_upgrade', 'button_empty', null);
ns_button.buttons.build_upgrade.mouseUp = function(_e)
{
    let z_arr = this.tag_id.split('_');
    let tag_id = z_arr[0] + '_' + z_arr[2];
    let dialog = ns_dialog.dialogs[tag_id];
    let alias = z_arr[2];
    let m = ns_cs.m.buil[alias];

    ns_dialog.setDataOpen('build_upgrade', { m_buil_pk: m.m_buil_pk, castle_type: dialog.data.castle_type, castle_pk: dialog.data.castle_pk });
}

ns_button.buttons.build_move = new nsButtonSet('build_move', 'button_empty', null);
ns_button.buttons.build_move.mouseUp = function(_e)
{
    let z_arr = this.tag_id.split('_');
    let tag_id = z_arr[0] + '_' + z_arr[2];
    let dialog = ns_dialog.dialogs[tag_id];
    ns_castle.setBuildMove(dialog.data.castle_type, dialog.data.castle_pk);
    ns_dialog.close(tag_id);
}

ns_button.buttons.build_prev = new nsButtonSet('build_prev', 'button_empty', null);
ns_button.buttons.build_prev.mouseUp = function(_e)
{
    let z_arr = this.tag_id.split('_');
    let tag_id = z_arr[0] + '_' + z_arr[2];
    let dialog = ns_dialog.dialogs[tag_id];
    let alias = z_arr[2];
    let m = ns_cs.m.buil[alias];

    dialog.current_page = dialog.current_page - 1;
    if (dialog.current_page < 1) {
        dialog.current_page = dialog.total_page;
    }
    dialog.data.castle_pk = ns_cs.getCastlePk(m.type, m.m_buil_pk, dialog.current_page);
    dialog.draw();
}

ns_button.buttons.build_next = new nsButtonSet('build_next', 'button_empty', null);
ns_button.buttons.build_next.mouseUp = function(_e)
{
    let z_arr = this.tag_id.split('_');
    let tag_id = z_arr[0] + '_' + z_arr[2];
    let dialog = ns_dialog.dialogs[tag_id];
    let alias = z_arr[2];
    let m = ns_cs.m.buil[alias];

    dialog.current_page = dialog.current_page + 1;
    if (dialog.current_page > dialog.total_page) {
        dialog.current_page = 1;
    }
    dialog.data.castle_pk = ns_cs.getCastlePk(m.type, m.m_buil_pk, dialog.current_page);
    dialog.draw();
}

ns_button.buttons.build_speedup = new nsButtonSet('build_speedup', 'button_empty', null);
ns_button.buttons.build_speedup.mouseUp = function(_e)
{
    let z_arr = this.tag_id.split('_');
    let tag_id = z_arr[0] + '_' + z_arr[2];
    let dialog = ns_dialog.dialogs[tag_id];
    let alias = z_arr[2];
    let m = ns_cs.m.buil[alias];
    let queue_type = 'C';
    let time_pk  = ns_cs.getTimerPk(queue_type, null, m.type, dialog.data.castle_pk);
    if (ns_timer.checkFreeSpeedup(time_pk)) {
        ns_xhr.post('/api/speedup', { time_pk: time_pk, free: 'Y' }, function (_data) {
            ns_xhr.returnCheck(_data);
        }, { useProgress: true });
    } else {
        ns_dialog.setDataOpen('item_quick_use', { type:'speedup', queue_type: queue_type, position_type: m.type, in_cast_pk: dialog.data.castle_pk, need_qbig: 0 });
    }
}

ns_button.buttons.build_cancel = new nsButtonSet('build_cancel', 'button_empty', null);
ns_button.buttons.build_cancel.mouseUp = function(_e)
{
    try
    {
        const okFunction = function()
        {
            let z_arr = _e.target.id.split('_');
            let tag_id = z_arr[2] + '_' + z_arr[4];
            let dialog = ns_dialog.dialogs[tag_id];
            let alias = z_arr[4];
            let m = ns_cs.m.buil[alias];

            let post_data = {};
            post_data['posi_pk'] = ns_engine.game_data.cpp;
            post_data['type'] = 'C';
            post_data['position_type'] = m.type;
            post_data['castle_pk'] = dialog.data.castle_pk;

            ns_xhr.post('/api/cancel', post_data);
        };

        /* 다운그레이드가 없으므로 주석처리
        var msg = '';
        if(qbw_cs.d[qbw_cs.cfg.curr_view][qbw_dlg.getData(dlgName)].status == 'D')
        {
            msg = qbw_cs.text.mesg.demolish_cancellation;
        } else if(qbw_cs.d[qbw_cs.cfg.curr_view][qbw_dlg.getData(dlgName)].status == 'U') {
            msg = qbw_cs.text.mesg.cancellation;
        }*/

        // ns_dialog.setDataOpen('confirm', { text: system_text.message.cancellation, okFunc: okFunction, evt: _e });
    } catch (e) {
        console.error(e);
    }
}

ns_button.buttons.build_help = new nsButtonSet('build_help', 'button_empty', null);
ns_button.buttons.build_help.mouseUp = function(_e)
{
    // TODO 도움말 기능 사용안함.
    /*let z_arr = _e.target.id.split('_');
    let type = z_arr[4];
    ns_dialog.setDataOpen('game_help', { 'type': type });*/
}

/* ************************************************** */

ns_button.buttons.counter_speed_up =  new nsButtonSet('counter_speed_up', 'button_empty', null);
ns_button.buttons.counter_speed_up.mouseUp = function(_e)
{
    let arr = this.tag_id.split('_');
    let time_pk = arr.pop();
    if (! ns_cs.d.time[time_pk] || ns_cs.d.time[time_pk].free_speedup) {
        return;
    }
    let queue_type = ns_cs.d.time[time_pk].queue_type;
    let position_type = (ns_cs.d.time[time_pk].in_cast_pk) ? "I" : "O";
    let cast_pk = (ns_cs.d.time[time_pk].in_cast_pk && ns_cs.d.time[time_pk].in_cast_pk !== "0") ? ns_cs.d.time[time_pk].in_cast_pk : ns_cs.d.time[time_pk].out_cast_pk;

    if (ns_timer.checkFreeSpeedup(time_pk)) {
        ns_cs.d.time[time_pk].free_speedup = true;
        ns_xhr.post('/api/speedup', { time_pk: time_pk, free: 'Y' }, function (_data) {
            if(! ns_xhr.returnCheck(_data)) {
                ns_cs.d.time[time_pk].free_speedup = false; // 오류가 발생했다면 제거
            }
        }, { useProgress: true });
    } else {
        ns_dialog.setDataOpen('item_quick_use', { type:'speedup', time_pk: time_pk, queue_type: queue_type, position_type: position_type,  in_cast_pk:  cast_pk });
    }
}

/* ************************************************** */

ns_button.buttons.concurr_speedup =  new nsButtonSet('concurr_speedup', 'button_empty', null);
ns_button.buttons.concurr_speedup.mouseUp = function(_e)
{
    let arr = this.tag_id.split('_');
    let tag_id = arr[2] + '_' + arr[3];
    let alias = arr[3];
    let type = null;
    let free_speedup = false;
    let dialog = ns_dialog.dialogs[tag_id];

    let quick_type = 'speedup';

    if (alias === 'Medical') {
        type = 'M';
    } else if (alias === 'Technique') {
        type = 'T';
    } else if (alias === 'Army') {
        type = 'A';
    } else if (alias === 'CastleWall') {
        type = 'F';
        quick_type = 'fort_speedup';
    }

    if (type === 'T') {
        let time = Object.entries(ns_cs.d.time).filter(o => ns_util.isNumeric(o[0]) && o[1].queue_type === 'T').map(o => o[1]);
        if (time.length < 1) {
            return;
        }
        let time_pk = time[0].time_pk;
        if (ns_timer.checkFreeSpeedup(time_pk)) {
            ns_xhr.post('/api/speedup', { time_pk: time_pk, free: 'Y' }, function (_data) {
                ns_xhr.returnCheck(_data);
            }, { useProgress: true });
        } else {
            ns_dialog.setDataOpen('item_quick_use', { type: quick_type, queue_type: type, position_type: 'I', in_cast_pk: dialog.data.castle_pk, time_pk: dialog.data.time_pk });
        }
    } else {
        ns_dialog.setDataOpen('item_quick_use', { type: quick_type, queue_type: type, position_type: 'I', in_cast_pk: dialog.data.castle_pk, time_pk: dialog.data.time_pk });
    }
}

ns_button.buttons.concurr_cancel =  new nsButtonSet('concurr_cancel', null, null);
ns_button.buttons.concurr_cancel.mouseUp = function(_e)
{
    // 사용안함
}

ns_button.buttons.queue_cancel =  new nsButtonSet('queue_cancel', null, null);
ns_button.buttons.queue_cancel.mouseUp = function(_e)
{
    // 사용안함
}

// world
ns_button.buttons.goto_my_map = new nsButtonSet('goto_my_map', 'button_full', null);
ns_button.buttons.goto_my_map.mouseUp = function(_e)
{
    let p = ns_engine.game_data.cpp.split('x');
    let x = p[0], y = p[1];

    ns_world.goto_map = true;

    ns_world.setPosition(x, y);
}

ns_button.buttons.goto_search = new nsButtonSet('goto_search', 'button_empty', null);
ns_button.buttons.goto_search.mouseUp = function(_e)
{
    ns_dialog.closeAll();
    ns_dialog.open('world_goto_search');
}

ns_button.buttons.goto_map = new nsButtonSet('goto_map', 'button_empty', null);
ns_button.buttons.goto_map.mouseUp = function(_e)
{
    ns_dialog.closeAll();
    ns_dialog.open('world_goto_search');
}


ns_button.buttons.favorite = new nsButtonSet('favorite', 'button_empty', null);
ns_button.buttons.favorite.mouseUp = function(_e)
{
    ns_dialog.open('world_favorite');
}