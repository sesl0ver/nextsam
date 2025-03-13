// 요충지 관련
ns_dialog.dialogs.npc_point = new nsDialogSet('npc_point', 'dialog_full', 'size-full');

ns_dialog.dialogs.npc_point.cacheContents = function ()
{
    this.cont_obj.npc_point_position_wrap = new nsObject('.npc_point_position_wrap', this.obj);
    this.cont_obj.npc_point_ranking_wrap = new nsObject('.npc_point_ranking_wrap', this.obj);

    this.cont_obj.my_npc_point_rank = new nsObject('.my_npc_point_rank', this.obj);
    this.cont_obj.my_npc_point_score = new nsObject('.my_npc_point_score', this.obj);
    this.cont_obj.my_npc_point_coin = new nsObject('.my_npc_point_coin', this.obj);
}

ns_dialog.dialogs.npc_point.draw= function ()
{
    ns_xhr.post('/api/worldPoint/rank', {}, (_data, _status) =>
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];
        this.drawRank(_data);
    })
}

ns_dialog.dialogs.npc_point.drawRank = function (_data)
{
    let dialog = ns_dialog.dialogs.npc_point;

    const tbody = dialog.cont_obj.npc_point_position_wrap.find('tbody');
    tbody.empty();

    dialog.sorted = [];
    let cpp = ns_engine.game_data.cpp;
    for (let d of Object.values(_data.point)) {
        d.distance = ns_world.distanceValue(cpp, d._posi_pk, true);
        dialog.sorted.push(d);
    }

    dialog.cont_obj.my_npc_point_rank.text(_data.my_rank);
    dialog.cont_obj.my_npc_point_score.text(ns_util.numberFormat(_data.my_point));
    dialog.cont_obj.my_npc_point_coin.text(ns_util.numberFormat(_data.my_coin));

    // 정렬 : 내 점령 > 타군주 점령 > 내 영지에서 가까운순으로
    dialog.sorted = dialog.sorted.sort((a, b) => (ns_util.math(a.distance.length).eq(b.distance.length)) ? 0 : (ns_util.math(a.distance.length).gt(b.distance.length)) ? 1 : -1);
    let _s = { Y: 1, N: -1 }
    dialog.sorted = dialog.sorted.sort((a, b) => (_s[b.my_point] + _s[b.occu]) - (_s[a.my_point] + _s[a.occu]));

    let tr, td, span;
    for (let d of Object.values(dialog.sorted)) {
        tr = document.createElement('tr');

        td = document.createElement('td');
        span = document.createElement('span');
        span.setAttribute('id', `ns_button_npc_point_detail_${d._posi_pk}`);
        span.innerText = d._posi_pk;
        td.appendChild(span);
        tr.appendChild(td);

        td = document.createElement('td');
        span = document.createElement('span');
        span.innerText = `${d.distance.direction}${d.distance.length}리`;
        td.appendChild(span);
        tr.appendChild(td);

        td = document.createElement('td');
        span = document.createElement('span');
        span.innerText = 'Lv.' + d._level;
        td.appendChild(span);
        tr.appendChild(td);

        td = document.createElement('td');
        span = document.createElement('span');
        if (! d?.occu || d?.occu !== 'Y') {
            span.classList.add('text_condition_no');
            span.innerText = '미점령 상태';
        } else {
            if (d?.my_point === 'Y') {
                span.classList.add('text_condition_yes');
                span.innerText = '내가 점령 중';
            } else {
                span.classList.add('text_condition_other');
                span.innerText = '타 군주 점령 중' ;
            }
        }

        td.appendChild(span);
        tr.appendChild(td);

        tbody.append(tr);

        let button_id = `npc_point_detail_${d._posi_pk}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_middle_2', 'npc_point');
        ns_button.buttons[button_id].mouseUp = function ()
        {
            if (ns_engine.game_data.curr_view !== 'world') {
                ns_engine.toggleWorld(false);
            } else {
                ns_dialog.closeAll();
            }
            let p = d._posi_pk.split('x');
            ns_world.goto_map = true;
            ns_world.clicked_coords = true;
            ns_world.setPosition(p[0], p[1]);
        }
    }

    dialog.cont_obj.npc_point_ranking_wrap.empty();
    if (_data?.rank) {
        for (let [rank, d] of Object.entries(_data.rank)) {

            tr = document.createElement('tr');

            td = document.createElement('td');
            span = document.createElement('span');
            span.innerText = rank;
            td.appendChild(span);
            tr.appendChild(td);

            td = document.createElement('td');
            span = document.createElement('span');
            span.innerText = ns_util.numberFormat(d.sum_point);
            td.appendChild(span);
            tr.appendChild(td);

            dialog.cont_obj.npc_point_ranking_wrap.append(tr);
        }
    } else {
        tr = document.createElement('tr');

        td = document.createElement('td');
        td.colSpan = 2;
        span = document.createElement('span');
        span.innerText = '집계된 랭킹 정보가 없습니다.';
        td.appendChild(span);
        tr.appendChild(td);

        dialog.cont_obj.npc_point_ranking_wrap.append(tr);
    }
}

/* ***************************************** */
ns_button.buttons.npc_point_close = new nsButtonSet('npc_point_close', 'button_back', 'npc_point', { base_class: ns_button.buttons.common_close });
ns_button.buttons.npc_point_sub_close = new nsButtonSet('npc_point_sub_close', 'button_full', 'npc_point', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.npc_point_close_all = new nsButtonSet('npc_point_close_all', 'button_close_all', 'npc_point', { base_class: ns_button.buttons.common_close_all });