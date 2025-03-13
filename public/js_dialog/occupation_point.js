ns_dialog.dialogs.occupation_point = new nsDialogSet('occupation_point', 'dialog_full', 'size-full', {do_close_all: false});
ns_dialog.dialogs.occupation_point.my_rank = null;
ns_dialog.dialogs.occupation_point.limit_dt = null;
ns_dialog.dialogs.occupation_point.end_time = null;

ns_dialog.dialogs.occupation_point.cacheContents = function () {
    this.cont_obj.occupation_rank_time = new nsObject('.occupation_rank_time', this.obj);
    this.cont_obj.occupation_goal_time = new nsObject('.occupation_goal_time', this.obj);

    this.cont_obj.my_alliance_point = new nsObject('.my_alliance_point', this.obj);
    this.cont_obj.no_alliance_point = new nsObject('.no_alliance_point', this.obj);

    this.cont_obj.my_rank = new nsObject('.my_rank', this.obj);
    this.cont_obj.my_ally_name = new nsObject('.my_ally_name', this.obj);
    this.cont_obj.my_ally_lord = new nsObject('.my_ally_lord', this.obj);
    this.cont_obj.my_ally_point = new nsObject('.my_ally_point', this.obj);

    this.cont_obj.my_reward_rank = new nsObject('.my_reward_rank', this.obj);
    this.cont_obj.my_reward_item = new nsObject('.my_reward_item', this.obj);

    this.cont_obj.ranking_list = new nsObject('.ranking_list', this.obj);
    this.cont_obj.reward_list = new nsObject('.reward_list', this.obj);

    this.cont_obj.ranking_tab_wrap = new nsObject('.ranking_tab_wrap', this.obj);
    this.cont_obj.goal_tab_wrap = new nsObject('.goal_tab_wrap', this.obj);

    this.cont_obj.ranking_wrap = new nsObject('.ranking_wrap', this.obj);
    this.cont_obj.reward_wrap = new nsObject('.reward_wrap', this.obj);

    this.cont_obj.occupation_rule_wrap = new nsObject('.occupation_rule_wrap', this.obj);

    this.cont_obj.my_occupation_point = new nsObject('.my_occupation_point', this.obj);
    this.cont_obj.goal_list = new nsObject('.goal_list', this.obj);
}

ns_dialog.dialogs.occupation_point.draw = function () {
    ns_button.toggleGroupSingle(ns_button.buttons.occupation_point_tab_ranking);
    this.drawTab('ranking');
}

ns_dialog.dialogs.occupation_point.erase = function () {
    this.my_rank = null;
}

ns_dialog.dialogs.occupation_point.drawTab = function (_tab) {
    let dialog = ns_dialog.dialogs.occupation_point;
    dialog.cont_obj[`${_tab}_tab_wrap`].show();
    dialog.cont_obj.occupation_rule_wrap.hide();
    dialog.cont_obj.reward_wrap.hide();
    dialog.cont_obj.ranking_wrap.show();
    dialog.scroll_handle.resume();
    if (_tab === 'ranking') {
        this.cont_obj['goal_tab_wrap'].hide();
        dialog.drawRanking();
        dialog.drawRankingReward();
    } else {
        this.cont_obj['ranking_tab_wrap'].hide();
        dialog.drawGoal();
    }
}

ns_dialog.dialogs.occupation_point.drawRanking = function () {
    let dialog = ns_dialog.dialogs.occupation_point;
    ns_xhr.post('/api/occupation/rank', {}, (_data, _status) => {
        if (!ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.end_time = _data.end_time;

        let rankings = Object.values(_data['rankings']);

        dialog.cont_obj.no_alliance_point.hide();
        dialog.cont_obj.my_alliance_point.hide();

        if (! ns_cs.d.lord?.alli_pk?.v) {
            dialog.cont_obj.no_alliance_point.show();
            dialog.cont_obj.no_alliance_point.find('span').text( ns_i18n.t('no_affiliated_alliance_exists'));
        } else {
            let my_ranking = rankings.find(o => ns_util.math(o.alli_pk).eq(_data.my.alli_pk));
            let my_rank = my_ranking?.['point_rank'] ?? '-';
            dialog.my_rank = my_rank;
            dialog.cont_obj.my_rank.text(my_rank);
            dialog.cont_obj.my_reward_rank.text(my_rank);
            dialog.cont_obj.my_ally_name.text(_data.my.title);
            dialog.cont_obj.my_ally_lord.text(_data.my.lord_name);
            dialog.cont_obj.my_ally_point.text(_data.my['ally_point'] ?? 0);

            dialog.cont_obj.my_alliance_point.show();
        }

        dialog.cont_obj.ranking_list.empty();
        for (let rank of Object.values(rankings)) {
            let tr = document.createElement('tr');

            let td = document.createElement('td');
            let span = document.createElement('span');
            span.innerText = String(rank['point_rank']);
            td.appendChild(span);

            let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
            button.setAttribute('id', `ns_button_ally_information_${rank.alli_pk}`);
            td.append(button);

            tr.appendChild(td);

            td = document.createElement('td');
            span = document.createElement('span');
            span.innerText = String(rank['title']);
            td.appendChild(span);
            tr.appendChild(td);

            td = document.createElement('td');
            span = document.createElement('span');
            span.innerText = String(rank['lord_name']);
            td.appendChild(span);
            tr.appendChild(td);

            td = document.createElement('td');
            span = document.createElement('span');
            span.innerText = String(rank['now_member_count']);
            td.appendChild(span);
            tr.appendChild(td);

            td = document.createElement('td');
            span = document.createElement('span');
            span.innerText = ns_util.numberFormat(rank['power']);
            td.appendChild(span);
            tr.appendChild(td);

            td = document.createElement('td');
            span = document.createElement('span');
            span.innerText = ns_util.numberFormat(rank['ally_point']);
            td.appendChild(span);
            tr.appendChild(td);

            dialog.cont_obj.ranking_list.append(tr);

            let button_id = `ally_information_${rank.alli_pk}`;
            ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'occupation_point');
            ns_button.buttons[button_id].mouseUp = () => {
                let post_data = {};
                post_data['alli_pk'] = rank.alli_pk;
                ns_xhr.post('/api/alliance/otherInfo', post_data, function (__data, __status) {
                    if (!ns_xhr.returnCheck(__data)) {
                        return;
                    }
                    __data = __data['ns_xhr_return']['add_data'];
                    ns_dialog.setDataOpen('alliance_other_info', __data);
                });
            }
            dialog.buttons.push(ns_button.buttons[button_id]);
        }
        dialog.drawMyReward();
    });
}

ns_dialog.dialogs.occupation_point.drawRankingReward = function ()
{
    let dialog = ns_dialog.dialogs.occupation_point;
    let m = ns_cs.m.occupation_reward.alliance;
    dialog.cont_obj.reward_list.empty();
    for (let [_rank, _m] of Object.entries(m)) {
        let _next_rank = 0;
        for (let _n of Array.from({length: 100}, (_, i) => i + 1)) {
            if (ns_util.math(_n).gt(_rank)) {
                if (Object.values(m).find(o => ns_util.math(o.rank).eq(_n))) {
                    break;
                }
                _next_rank = _n;
            }
        }
        let tr = document.createElement('tr');

        let td = document.createElement('td');
        if (ns_util.math(_next_rank).gt(0)) {
            td.innerText = String(_rank) + ' ~ ' + String(_next_rank);
        } else {
            td.innerText = String(_rank);
        }
        tr.appendChild(td);

        td = document.createElement('td');
        let _item_area = new nsObject(td);
        tr.appendChild(td);
        dialog.cont_obj.reward_list.append(tr);
        dialog.drawItem(_m.reward_item, _item_area, `occupation_reward_${_rank}`);
    }
}

ns_dialog.dialogs.occupation_point.drawMyReward = function ()
{
    let dialog = ns_dialog.dialogs.occupation_point;
    if (! dialog?.my_rank || dialog.my_rank === '-' || ns_util.math(dialog.my_rank).gt(100)) {
        dialog.cont_obj.my_reward_item.empty().text(ns_i18n.t('no_remuneration'));
        return;
    }
    let m = ns_cs.m.occupation_reward.alliance;
    let _reward = null, _text_rank = 0;
    for (let [_rank, _m] of Object.entries(m)) {
        if (ns_util.math(_rank).gt(dialog.my_rank)) {
            break;
        } else if (ns_util.math(_rank).eq(dialog.my_rank)) {
            _reward = _m.reward_item;
        } else {
            _reward = _m.reward_item;
        }
    }
    dialog.cont_obj.my_reward_item.empty();
    dialog.drawItem(_reward, dialog.cont_obj.my_reward_item, `occupation_reward_my`);
}

ns_dialog.dialogs.occupation_point.drawGoal = function ()
{
    let dialog = ns_dialog.dialogs.occupation_point;
    ns_xhr.post('/api/occupation/my', {}, (_data, _status) => {
        if (!ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];
        dialog.limit_dt = _data['limit_dt'];
        dialog.end_time = _data.end_time;
        dialog.cont_obj.my_occupation_point.text(ns_util.numberFormat(_data['point']));
        dialog.drawGoalReward(_data['point'], _data['reward']);
    });
}

ns_dialog.dialogs.occupation_point.drawGoalReward = function (_point, _reward)
{
    let dialog = ns_dialog.dialogs.occupation_point;
    let m = ns_cs.m.occupation_reward.personal;
    dialog.cont_obj.goal_list.empty();
    for (let _rank of Object.values(m)) {
        let tr = document.createElement('tr');

        let td = document.createElement('td');
        td.innerText = ns_util.numberFormat(_rank.need_point);
        tr.appendChild(td);

        td = document.createElement('td');
        let _item_area = new nsObject(td);
        tr.appendChild(td);

        td = document.createElement('td');
        td.classList.add('width-20');

        let button_id = `occupation_personal_reward_${_rank.rank}`;
        let span = document.createElement('span');
        span.setAttribute('id', `ns_button_${button_id}`);
        td.appendChild(span);

        tr.appendChild(td);

        dialog.cont_obj.goal_list.append(tr);
        dialog.drawItem(_rank.reward_item, _item_area, `occupation_goal_${_rank.need_point}`);
        // 획득 버튼
        ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_small_1', 'occupation_point');
        ns_button.buttons[button_id].obj.text(!(_reward.includes(_rank.rank)) ? ns_i18n.t('receive') : ns_i18n.t('received'));
        ns_button.buttons[button_id].mouseUp = function ()
        {
            let post_data = {};
            post_data['rank'] = _rank.rank;
            ns_xhr.post('/api/occupation/personalReward', post_data, function (_data, _status) {
                if (!ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];
                dialog.drawGoalReward(_point, _data['reward']);
                let message = _rank.reward_item.split(',').map((o) => {
                    let [m_item_pk, item_count] = o.split(':');
                    return `${ns_i18n.t(`item_title_${m_item_pk}`)}x${item_count}`;
                }).join(', ');
                ns_dialog.setDataOpen('message', ns_i18n.t('msg_item_received', [message]));
            }, { useProgress: true });
        }
        dialog.buttons.push(ns_button.buttons[button_id]);
        if (ns_util.math(_point).gte(_rank.need_point) && !_reward.includes(_rank.rank)) {
            ns_button.buttons[button_id].setEnable();
        } else {
            ns_button.buttons[button_id].setDisable();
        }
    }
}

ns_dialog.dialogs.occupation_point.drawItem = function (_reward_item, _area, _button_id)
{
    let dialog = ns_dialog.dialogs.occupation_point;
    for (let _reward of _reward_item.split(',')) {
        let [m_item_pk, item_count] = _reward.split(':');
        let span = document.createElement('span');
        span.classList.add('item_image');
        span.classList.add(`item_image_${m_item_pk}`);
        span.setAttribute('id', `ns_button_${_button_id}_${m_item_pk}`);

        let count = document.createElement('span');
        count.classList.add('item_count');
        count.innerText = 'x' + item_count;

        span.appendChild(count);
        _area.append(span);

        let _id = `${_button_id}_${m_item_pk}`;
        ns_button.buttons[_id] = new nsButtonSet(_id, null, 'occupation_point');
        ns_button.buttons[_id].mouseUp = function ()
        {
            let _m_item_pk = this.tag_id.split('_').pop();
            ns_dialog.setDataOpen('reward_information', { m_item_pk: _m_item_pk });
        }
        dialog.buttons.push(ns_button.buttons[_id]);
    }
}

ns_dialog.dialogs.occupation_point.timerHandler = function (_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.occupation_point.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.occupation_point.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.occupation_point;
    if (dialog.limit_dt) {
        if (ns_util.math(dialog.limit_dt).minus(ns_timer.now()).lte(0)) {
            ns_button.buttons.occupation_point_refresh.obj.text(ns_i18n.t('to_update'));
            ns_button.buttons.occupation_point_refresh.setEnable();
        } else {
            ns_button.buttons.occupation_point_refresh.obj.text(ns_i18n.t('time_left', [ns_util.getCostsTime(ns_util.math(dialog.limit_dt).minus(ns_timer.now()).number)]));
            ns_button.buttons.occupation_point_refresh.setDisable();
        }
    }
    if (dialog.end_time) {
        dialog.cont_obj.occupation_rank_time.text(ns_util.getCostsTime(dialog.end_time));
        dialog.cont_obj.occupation_goal_time.text(ns_util.getCostsTime(dialog.end_time));
    }
}


ns_button.buttons.occupation_point_close = new nsButtonSet('occupation_point_close', 'button_back', 'occupation_point', {base_class: ns_button.buttons.common_close});
ns_button.buttons.occupation_point_sub_close = new nsButtonSet('occupation_point_sub_close', 'button_full', 'occupation_point', {base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.occupation_point_close_all = new nsButtonSet('occupation_point_close_all', 'button_close_all', 'occupation_point', {base_class: ns_button.buttons.common_close_all});

ns_button.buttons.occupation_point_tab_ranking = new nsButtonSet('occupation_point_tab_ranking', 'button_tab', 'occupation_point', {toggle_group: 'occupation_point_tab'});
ns_button.buttons.occupation_point_tab_ranking.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.occupation_point;
    let current_tab = ns_button.toggleGroupValue('occupation_point_tab')[0].split('_tab_').pop();
    let tab = this.tag_id.split('_tab_').pop();
    dialog.scroll_handle.initScroll();
    if (tab === current_tab) {
        return;
    }
    ns_button.toggleGroupSingle(this);
    dialog.drawTab(tab);
};
ns_button.buttons.occupation_point_tab_goal = new nsButtonSet('occupation_point_tab_goal', 'button_tab', 'occupation_point', { toggle_group: 'occupation_point_tab', base_class: ns_button.buttons.occupation_point_tab_ranking });

ns_button.buttons.occupation_reward = new nsButtonSet('occupation_reward', 'button_occupation_reward', 'occupation_point');
ns_button.buttons.occupation_reward.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.occupation_point;

    if (dialog.cont_obj.reward_wrap.hasCss('hide')) {
        dialog.scroll_handle.initScroll();
        dialog.scroll_handle.pause();
        dialog.cont_obj.ranking_wrap.hide();
        dialog.cont_obj.reward_wrap.show();
        this.setClicked();
    } else {
        dialog.scroll_handle.resume();
        dialog.cont_obj.ranking_wrap.show();
        dialog.cont_obj.reward_wrap.hide();
        this.unsetClicked()
    }
}

ns_button.buttons.occupation_rule = new nsButtonSet('occupation_rule', 'button_tooltip_rule', 'occupation_point');
ns_button.buttons.occupation_rule.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.occupation_point;
    let tab = ns_button.toggleGroupValue('occupation_point_tab')[0].split('_tab_').pop();

    if (dialog.cont_obj.occupation_rule_wrap.hasCss('hide')) {
        dialog.scroll_handle.initScroll();
        dialog.scroll_handle.pause();
        dialog.cont_obj.occupation_rule_wrap.show();
        if (tab === 'ranking') {
            dialog.cont_obj.occupation_rule_wrap.find('.alliance_rule').show();
            dialog.cont_obj.occupation_rule_wrap.find('.personal_rule').hide();
        } else {
            dialog.cont_obj.occupation_rule_wrap.find('.personal_rule').show();
            dialog.cont_obj.occupation_rule_wrap.find('.alliance_rule').hide();
        }
    } else {
        dialog.scroll_handle.resume();
        dialog.cont_obj.occupation_rule_wrap.hide();
    }
}
ns_button.buttons.occupation_goal_rule = new nsButtonSet('occupation_goal_rule', 'button_tooltip_rule', 'occupation_point', { base_class: ns_button.buttons.occupation_rule });
ns_button.buttons.occupation_tooltip_close = new nsButtonSet('occupation_tooltip_close', 'button_tooltip_close', 'occupation_point', { base_class: ns_button.buttons.occupation_rule });

ns_button.buttons.ally_information_my = new nsButtonSet('ally_information_my', 'button_table', 'occupation_point');
ns_button.buttons.ally_information_my.mouseUp = function ()
{
    let post_data = {};
    post_data['alli_pk'] = ns_cs.d.lord['alli_pk'].v;
    ns_xhr.post('/api/alliance/otherInfo', post_data, function (_data, _status) {
        if (!ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];
        ns_dialog.setDataOpen('alliance_other_info', _data);
    });
}

ns_button.buttons.occupation_point_refresh = new nsButtonSet('occupation_point_refresh', 'button_middle_2', 'occupation_point');
ns_button.buttons.occupation_point_refresh.mouseUp = function ()
{
    ns_dialog.dialogs.occupation_point.drawGoal();
}