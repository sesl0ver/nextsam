// 다이얼로그
ns_dialog.dialogs.quest = new nsDialogSet('quest', 'dialog_full', 'size-full');
ns_dialog.dialogs.quest.current_type = null;
ns_dialog.dialogs.quest.sorted = null;
ns_dialog.dialogs.quest.quest_list = [];
ns_dialog.dialogs.quest.reward_list = [];
ns_dialog.dialogs.quest.main_buttons = [];
ns_dialog.dialogs.quest.sub_buttons = [];
ns_dialog.dialogs.quest.current_main_quest = null;

ns_dialog.dialogs.quest.cacheContents = function (e)
{
    this.cont_obj.content = new nsObject('.content', this.obj);
    this.cont_obj.content_main_quest_list = new nsObject('.content_main_quest_list', this.obj);
}

ns_dialog.dialogs.quest.draw = function()
{
    if (! this.visible) {
        ns_button.toggleGroupSingle(ns_button.buttons.quest_tab_general);
    }
    this.current_type = 'general';
    this.drawTab();
}

ns_dialog.dialogs.quest.drawTab = function()
{
    this.current_main_quest = null;
    this.drawList();
}

ns_dialog.dialogs.quest.drawList = function()
{
    let dialog = ns_dialog.dialogs.quest;
    let main_title = {};
    let _type = ns_dialog.dialogs.quest.current_type;
    let quest_cnt = {}; // TODO ?

    if (dialog.main_buttons.length > 0) {
        for (let _button of dialog.main_buttons) {
            _button.destroy();
        }
    }

    // 목록 리스트 캐싱
    dialog.sorted = [];
    dialog.quest_list = {};
    dialog.main_buttons = [];

    for (let [k, d] of Object.entries(ns_cs.d.ques)) {
        if (! ns_util.isNumeric(k)) {
            continue;
        }
        try {
            let m = ns_cs.m.ques[k];
            let type = (m.type !== 'making') ? 'general' : m.type;
            if (_type !== type) {
                continue;
            }
            if (m.type !== 'making' && d.reward_status === 'Y') {
                continue;
            }
            if (m.type === 'making' && d.reward_status === 'Y' && k.substring(4, 2) !== '10') {
                continue;
            }

            dialog.sorted.push({'k': k, 'd': d, 'orderno': ns_cs.m.ques[d.m_ques_pk]?.orderno ?? 0});
        } catch (e) {
            console.error(e);
        }
    }

    dialog.sorted = ns_util.arraySort(dialog.sorted, 1, 'orderno');

    dialog.cont_obj.content_main_quest_list.empty();

    for (let _d of Object.values(dialog.sorted)) {
        try {
            let m_ques_pk = _d.k, d = _d.d, font_color_class = '';
            let m = ns_cs.m.ques[m_ques_pk];

            let main_ques_pk = m_ques_pk.substring(0, 4);
            if (! main_title[main_ques_pk]) {
                let list = document.createElement('div');
                list.setAttribute('id', `ns_button_main_quest_${main_ques_pk}`);
                list.classList.add('ns_panel_trans');

                dialog.cont_obj.content_main_quest_list.append(list);

                // 서브퀘스트 리스트 추가
                dialog.quest_list = [];

                main_title[main_ques_pk] = { m_ques_pk: m_ques_pk, cnt: 0, status: false };
                if (['daily', 'daily_event'].includes(m.type)) { //일일
                    font_color_class = 'text_daily_quest';
                } else if (m.type === 'lord_upgrade') {	//승급
                    font_color_class = 'text_lord_upgrade_quest';
                } else {
                    font_color_class = null;
                }

                let title = document.createElement('span');
                title.classList.add('content_quest_title');
                if (font_color_class) {
                    title.classList.add(font_color_class);
                }
                if (d.status === 'C') {
                    if (main_title[main_ques_pk].status !== 'C') {
                        main_title[main_ques_pk].status = 'C';
                        if (m.type !== 'making') {
                            title.classList.add('complete');
                        }
                    }
                }
                title.innerHTML = ns_cs.m.ques[m_ques_pk].main_title;

                let count = document.createElement('span');
                count.classList.add('content_quest_count');
                count.innerText = dialog.sorted.filter(q => q.k.substring(0, 4) === main_ques_pk).length.toString();

                list.appendChild(title);
                list.appendChild(count);

                // let line = document.createElement('div');
                // line.classList.add('hr_division_brown');
                //
                // dialog.cont_obj.content_main_quest_list.append(line);

                let sub = document.createElement('div');
                sub.setAttribute('class', 'content_sub_quest_' + main_ques_pk);

                dialog.cont_obj.content_main_quest_list.append(sub);

                // 버튼 생성
                ns_button.buttons[`main_quest_${main_ques_pk}`] = new nsButtonSet(`main_quest_${main_ques_pk}`, 'content_main_quest', 'quest');
                ns_button.buttons[`main_quest_${main_ques_pk}`].mouseUp = function (_e)
                {
                    this.setClicked();
                    if (dialog.current_main_quest === main_ques_pk) {
                        this.unsetClicked();
                    } else {
                        if (dialog.current_main_quest !== null) {
                            ns_button.buttons[`main_quest_${dialog.current_main_quest}`].unsetClicked();
                        }
                    }
                    dialog.drawSub(main_ques_pk);
                }
                dialog.main_buttons.push(ns_button.buttons[`main_quest_${main_ques_pk}`]);
            } else {
                // 이미 메인퀘스트가 있다면 클리어체크만 함.
                if (d.status === 'C') {
                    if (main_title[main_ques_pk].status !== 'C') {
                        main_title[main_ques_pk].status = 'C';
                        if (m.type !== 'making') {
                            ns_button.buttons[`main_quest_${main_ques_pk}`].obj.find('.content_quest_title').addCss('complete');
                        }
                    }
                }
            }
        } catch (e) {
            console.error(e);
        }
    }
    if (dialog.data) {
        let _main_ques_pk = dialog.data.substring(0, 4);
        this.drawSub(_main_ques_pk);
        ns_dialog.setDataOpen('quest_viewer', dialog.data);
        dialog.data = null; // 다 띄워주고 지워줌.
    }
}

ns_dialog.dialogs.quest.drawSub = function(_main_quest_pk)
{
    let dialog = ns_dialog.dialogs.quest;
    if (dialog.current_main_quest !== null) {
        dialog.cont_obj.content_main_quest_list.find(`.content_sub_quest_${dialog.current_main_quest}`).empty();
        // 이전에 보여주던 버튼 destroy
        for (let _button of dialog.sub_buttons) {
            _button.destroy();
        }
    }
    dialog.sub_buttons = [];
    let sub_list = dialog.cont_obj.content_main_quest_list.find(`.content_sub_quest_${_main_quest_pk}`);
    if (dialog.current_main_quest === _main_quest_pk) {
        dialog.current_main_quest = null;
        sub_list.empty();
        return;
    }
    let sub_quest_list = dialog.sorted.filter(q => q.k.substring(0, 4) === _main_quest_pk);
    for (let _d of Object.values(sub_quest_list)) {
        try {
            let m_ques_pk = _d.k, d = _d.d;
            let m = ns_cs.m.ques[m_ques_pk];

            let sub = document.createElement('div');
            sub.setAttribute('id', `ns_button_sub_quest_${m_ques_pk}`);
            sub.classList.add('ns_panel_trans');

            let title = document.createElement('span');
            if (d.status === 'C' && m.type !== 'making') {
                title.classList.add('complete');
            }
            title.innerText = ` - ${m.sub_title}`;

            sub.appendChild(title);

            sub_list.append(sub);

            // let line = document.createElement('div');
            // line.classList.add('hr_division_brown');
            //
            // sub_list.append(line);

            // 버튼 생성
            ns_button.buttons[`sub_quest_${m_ques_pk}`] = new nsButtonSet(`sub_quest_${m_ques_pk}`, 'content_sub_quest', 'quest');
            ns_button.buttons[`sub_quest_${m_ques_pk}`].mouseUp = function (_e)
            {
                ns_dialog.setDataOpen('quest_viewer', m_ques_pk);
            }
            dialog.sub_buttons.push(ns_button.buttons[`sub_quest_${m_ques_pk}`]); // 버튼 파괴를 위해
        } catch (e) {
            console.error(e);
        }
    }

    dialog.current_main_quest = _main_quest_pk;
}

ns_dialog.dialogs.quest.unreadQuestCount = function(_count)
{
    let dialog = ns_dialog.dialogs.quest;
    _count = ns_util.toInteger(_count);
    if (_count > 99) {
        _count = 99;
    }

    if (_count < 1) {
        // $('#main_quest .cont_cnt_quest').text(0).hide();
        dialog.unread_cnt = 0;
    } else {
        // $('#main_quest .cont_cnt_quest').text(_cnt).show();
        dialog.unread_cnt = _count;
    }
}

ns_dialog.dialogs.quest.erase = function()
{
    if (this.main_buttons.length > 0) {
        for (let _button of this.main_buttons) {
            _button.destroy();
        }
    }
    if (this.sub_buttons.length > 0) {
        for (let _button of this.sub_buttons) {
            _button.destroy();
        }
    }
}

ns_button.buttons.quest_close = new nsButtonSet('quest_close', 'button_back', 'quest', { base_class: ns_button.buttons.common_close});
ns_button.buttons.quest_sub_close = new nsButtonSet('quest_sub_close', 'button_full', 'quest', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.quest_close_all = new nsButtonSet('quest_close_all', 'button_close_all', 'quest', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.quest_tab_general = new nsButtonSet('quest_tab_general', 'button_tab', 'quest', { toggle_group: 'quest_tab' });
ns_button.buttons.quest_tab_general.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.quest;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    let z_arr = ns_button.toggleGroupValue('quest_tab')[0].split('_');
    if (dialog.current_type === z_arr[2]) {
        return;
    }
    dialog.current_type = z_arr[2];
    dialog.drawTab();
}
ns_button.buttons.quest_tab_making = new nsButtonSet('quest_tab_making', 'button_tab', 'quest', { base_class: ns_button.buttons.quest_tab_general,  toggle_group: 'quest_tab' });

// 퀘스트 뷰어
ns_dialog.dialogs.quest_viewer = new nsDialogSet('quest_viewer', 'dialog_full', 'size-full');
ns_dialog.dialogs.quest_viewer.reward_buttons = [];
ns_dialog.dialogs.quest_viewer.cacheContents = function ()
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);
    this.cont_obj.content_sub_title = new nsObject('.content_sub_title', this.obj);
    this.cont_obj.quest_description_wrap = new nsObject('.quest_description_wrap', this.obj);
    this.cont_obj.content_quest_description = new nsObject('.content_quest_description', this.obj);

    this.cont_obj.content_goal_warp = new nsObject('.content_goal_warp', this.obj);
    this.cont_obj.content_tip_warp = new nsObject('.content_tip_warp', this.obj);
    this.cont_obj.content_reward_warp = new nsObject('.content_reward_warp', this.obj);

    this.cont_obj.reward_box_skeleton = new nsObject('#reward_box_skeleton');
}

ns_dialog.dialogs.quest_viewer.draw = function ()
{
    let m_ques_pk = this.data;
    let m = ns_cs.m.ques[m_ques_pk], d = ns_cs.d.ques[m_ques_pk];
    if (! d) {
        ns_dialog.close('quest_viewer');
    }

    if (this.reward_buttons.length > 0) {
        for (let _button of this.reward_buttons) {
            _button.destroy();
        }
    }

    this.cont_obj.content_title.text(m.main_title);
    this.cont_obj.content_sub_title.text(m.sub_title);
    this.cont_obj.content_quest_description.html(m.description);

    this.cont_obj.quest_description_wrap.hide();

    ns_button.buttons.quest_reward_submit.setDisable();
    // 바로가기 버튼
    ns_button.buttons.quest_shortcut.obj.hide();
    if (m.main_dlg) {
        ns_button.buttons.quest_shortcut.obj.show();
        ns_button.buttons.quest_shortcut.obj.text(m.btn_name);
    }

    this.cont_obj.content_goal_warp.empty();
    if (m.type !== 'making') {
        if (ns_util.math(m.condition_count).eq(1)) {
            let description_goal = m.description_goal;
            if (['event', 'daily_event'].includes(m.type)) {
                let condition_value = ns_cs.d.ques[m_ques_pk]?.condition_value ?? 0;
                if (ns_util.isNumeric(m.condition_1)) {
                    if (ns_util.math(condition_value).gt(m.condition_1)) {
                        condition_value = ns_util.toNumber(m.condition_1); // 최대치는 넘지 않게
                    }
                } else { // 숫자가 아닐때 처리 필요.

                }
                description_goal = description_goal.replace('{{1}}', condition_value);
            }
            let _condition = this.drawGoalTitle(d.status === 'C', description_goal);
            this.cont_obj.content_goal_warp.append(_condition);
            if (d.status === 'C' && d.reward_status === 'N') {
                ns_button.buttons.quest_reward_submit.setEnable();
            } else {
                ns_button.buttons.quest_reward_submit.setDisable();
            }
        } else {
            this.goalCheck();
        }
    } else {
        let post_data = {};
        post_data['m_ques_pk'] = m_ques_pk;
        ns_xhr.post('/api/quest/making', post_data, function (_data, _status)
        {
            if(! ns_xhr.returnCheck(_data)) {
                return;
            }
            _data = _data['ns_xhr_return']['add_data'];
            if (_data) {
                ns_dialog.dialogs.quest_viewer.goalCheck(_data); //완료 체크 그리기
            }
        });
    }

    this.drawRewardBoxList(m_ques_pk);
    this.cont_obj.content_tip_warp.html(m.description_tip);
}

ns_dialog.dialogs.quest_viewer.drawGoalTitle = function (_goal, _title)
{
    let condition_description = document.createElement('span');
    let condition_wrap = document.createElement('p');
    condition_description.innerHTML = _title;
    if (_goal === true) {
        condition_description.classList.add('text_quest_yes');
    } else {
        condition_description.classList.add('text_quest_no');
    }
    condition_wrap.appendChild(condition_description);
    return condition_wrap;
}

ns_dialog.dialogs.quest_viewer.goalCheck = function(_data = {})
{
    let dialog = ns_dialog.dialogs.quest_viewer;
    let m_ques_pk = (! _data?.m_ques_pk) ? dialog.data : _data.m_ques_pk;
    let m = ns_cs.m.ques[m_ques_pk];
    let array_condition = m.description_goal.split('<br>');
    let d, my_data = 0, m_data = 0, result = true;

    ns_button.buttons.quest_reward_submit.setDisable();

    dialog.cont_obj.content_goal_warp.empty();

    let i = 0, condition_check = 0;
    for (let c of array_condition) {
        let _clear = false;
        if (m.goal_type === 'GIVE_ITEM') {
            let cond = m[`condition_${(i + 1)}`].split(':');
            d = ns_cs.d.item[cond[0]];
            my_data = d ? d.item_cnt : 0;
            m_data = cond[1];
        } else if (m.goal_type === 'ARMY_RECRUIT') {
            let cond = m[`condition_${(i + 1)}`].split(':');
            d = ns_cs.d.army[cond[0]];
            my_data = d.v;
            m_data = cond[1];
        } else if (m.goal_type === 'BUIL_FORTIFICATION') {
            let cond = m[`condition_${(i + 1)}`].split(':');
            d = ns_cs.d.fort[cond[0]];
            my_data = d.v;
            m_data = cond[1];
        } else if (m.goal_type === 'MAKING') {
            d = true;
            let _resource = ['gold', 'food', 'horse', 'lumber', 'iron'];
            if (_resource[i] === 'gold') {
                my_data = ns_cs.getTerritoryInfo(`${_resource[i]}_curr`);
            } else {
                my_data = ns_cs.getResourceInfo(`${_resource[i]}_curr`);
            }
            m_data = m[`condition_${(i + 1)}`];
        } else if (m.goal_type === 'MAKING_COIN') {
            d = true;
            my_data = _data.point_coin;
            m_data = m[`condition_${(i + 1)}`];
        } else if (m.goal_type === 'MAKING_ARMY') {
            let cond = m[`condition_${(i + 1)}`].split(':');
            d = true;
            my_data = ns_cs.d.army[cond[0]].v;
            m_data = cond[1];
        } else if (m.goal_type === 'MAKING_ITEM') {
            let cond = m[`condition_${(i + 1)}`].split(':');
            d = true;
            my_data = (! ns_cs?.d?.item?.[cond[0]]) ? 0 : ns_cs.d.item[cond[0]].item_cnt;
            m_data = cond[1];
        }  else if (m.goal_type === 'TERRITORY_SEVERAL') {
            d = true;
            let type = ['food', 'horse', 'lumber'];
            my_data = ns_cs.d.reso[`${type[i]}_production`].v;
            m_data =  m['condition_1'];
        } else if (m.goal_type === 'ARMY_POINT') {  // TODO 이거 사용하는건가?
            d = true;
            my_data = ns_engine.cfg.army_point;
            m_data = m['condition_1'];
        }
        if (d) {
            _clear = ns_util.math(my_data).gte(m_data);
            if (_clear) {
                condition_check++;
            }
        }
        if (ns_util.math(m_data).gt(0)) {
            let _condition = this.drawGoalTitle(_clear, c);
            this.cont_obj.content_goal_warp.append(_condition);
        }
        i++;
    }

    /*if (['MAKING', 'MAKING_ITEM', 'MAKING_COIN', 'MAKING_ARMY'].includes(m.goal_type)) {
    }*/
    if (ns_util.math(condition_check).eq(m.condition_count)) {
        ns_button.buttons.quest_reward_submit.setEnable();
    } else {
        ns_button.buttons.quest_reward_submit.setDisable();
    }
}

ns_dialog.dialogs.quest_viewer.drawRewardBox = function (_data)
{
    let type = _data.type, qty = _data.qty, detail = _data.detail;

    // 이걸로 그려줄 수 있는 타입인지 체크
    let dic = ['power', 'item', 'population', 'food', 'horse', 'lumber', 'iron', 'gold', 'army', 'fortification', 'lord_upgrade'];

    let reward_blank = document.createElement('span');
    reward_blank.classList.add('reward_box_blank');

    let is_valid = false;
    for (let i = 0; i < dic.length; i++) {
        is_valid = (dic[i] === type) ? true : is_valid;
    }
    if (! is_valid) return reward_blank;

    let skeleton = this.cont_obj.reward_box_skeleton.clone();
    let reward_qty = (ns_util.isNumeric(qty)) ? qty : 0;
    reward_qty = (reward_qty > 999999) ? 999999 : reward_qty;
    let reward_title = '', reward_img_class = '';
    if (['power', 'population', 'lord_upgrade'].includes(type)) {
        if (type === 'population') {
            reward_title = ns_i18n.t('population_increase'); // 인구 증가
        } else if (type === 'power') {
            reward_title = ns_i18n.t('lord_power'); // 영향력
        } else if (type === 'lord_upgrade') {
            reward_title = ns_i18n.t('lord_level_upgrade'); // 군주 등급 상승
        }
        reward_img_class = 'reward_box_' + type;
    } else {
        if (type === 'item') {
            if (ns_cs.m.item?.[detail]) {
                reward_img_class = 'item_image_' + ns_cs.m.item[detail].m_item_pk;
                reward_title = ns_cs.m.item[detail].title;
            } else {
                return reward_blank;
            }
        } else if (['gold', 'food', 'horse', 'lumber', 'iron'].includes(type)) {
            reward_img_class = 'resource_image_' + type;
            reward_title = ns_i18n.t(`resource_${type}`);
        } else if (type === 'army') {
            if (ns_cs.m.army?.[detail]) {
                reward_title = ns_cs.m.army[detail].title;
                reward_img_class = 'army_image_' + ns_cs.m.army[detail].code;
            } else {
                return reward_blank;
            }
        } else if (type === 'fortification') {
            if (ns_cs.m.fort?.[detail]) {
                reward_title = ns_cs.m.fort[detail].title;
                reward_img_class = 'fort_image_' + ns_cs.m.fort[detail].code;
            } else {
                return reward_blank;
            }
        }
    }
    skeleton.find('.reward_title').text(reward_title);
    if (reward_img_class !== '') {
        skeleton.find('.reward_image').addCss(reward_img_class);
    }
    skeleton.find('.reward_count').text(`지급: ${reward_qty}`);
    return skeleton;
}

ns_dialog.dialogs.quest_viewer.drawRewardBoxList = function()
{
    let dialog = ns_dialog.dialogs.quest_viewer;
    let m_ques_pk = dialog.data;

    let list = [];
    let reward_dic = ['lord_upgrade', 'power', 'item', 'population', 'food', 'horse', 'lumber', 'iron', 'gold', 'army', 'fortification'];
    if (! ns_cs.m.ques?.[m_ques_pk]) {
        return;
    }

    dialog.reward_list = []; // 초기화

    let quest = ns_cs.m.ques[m_ques_pk];
    for (let dic of reward_dic) {
        if (quest?.[dic]) {
            if (['army', 'item', 'fortification'].includes(dic)) {
                // m_pk가 필요한 것들
                let arr = String(quest[dic]).replace(/\s/g, '').split(',');
                if (String(quest[dic]).length > 0 && arr.length > 0) {
                    for(let i = 0; i < arr.length; i++) {
                        let v = String(arr[i]).split(':');
                        list.push({ 'type' : dic, 'qty' : v[1], 'detail' : v[0] });
                        dialog.reward_list.push({ type : dic, code : v[0] });
                    }
                }
            } else {
                // m_pk가 필요 없는 것들
                list.push({ 'type' : dic, 'qty' : quest[dic] });
                dialog.reward_list.push({ type : dic });
            }
        }
    }

    dialog.cont_obj.content_reward_warp.empty();
    for (let i = 0; i < list.length; i++) {
        let reward_box = dialog.drawRewardBox(list[i]);
        let data = dialog.reward_list[i];
        if (['army', 'fortification', 'item'].includes(data.type)) {
            reward_box.setAttribute('id', `ns_button_reward_box_${i}`);
        }
        dialog.cont_obj.content_reward_warp.append(reward_box);
        try {
            if (['army', 'fortification', 'item'].includes(data.type)) {
                ns_button.buttons[`reward_box_${i}`] = new nsButtonSet(`reward_box_${i}`, 'button_empty', 'quest_viewer');
                ns_button.buttons[`reward_box_${i}`].mouseUp = function ()
                {
                    if (data.type === 'item') {
                        let m = ns_cs.m.item[data.code];
                        ns_dialog.setDataOpen('reward_information', { m_item_pk: m.m_item_pk });
                    } else if (data.type === 'fortification') {
                        let m = ns_cs.m.fort[data.code];
                        ns_dialog.setDataOpen('information', { title: 'fort', type: m.code, m_pk: m.m_fort_pk, castle_pk: 2 });
                    } else if (data.type === 'army') {
                        let m = ns_cs.m.army[data.code];
                        let m_buil = ns_cs.m.buil.Army;
                        ns_dialog.setDataOpen('information', { title: 'army', type: m.code, m_pk: m.m_army_pk, castle_pk: ns_cs.getCastlePk(m_buil.type, m_buil.m_buil_pk, 1) });
                    }
                }
                dialog.reward_buttons.push(ns_button.buttons[`reward_box_${i}`]);
            }
        } catch (e) {
            console.error(e);
        }
    }
}

ns_dialog.dialogs.quest_viewer.erase = function ()
{
    if (this.reward_buttons.length > 0) {
        for (let _button of this.reward_buttons) {
            _button.destroy();
        }
    }
    ns_button.buttons.toggle_quest_description.unsetClicked();
}

ns_button.buttons.quest_viewer_close = new nsButtonSet('quest_viewer_close', 'button_back', 'quest_viewer', { base_class: ns_button.buttons.common_close});
ns_button.buttons.quest_viewer_sub_close = new nsButtonSet('quest_viewer_sub_close', 'button_full', 'quest_viewer', { base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.quest_viewer_close_all = new nsButtonSet('quest_viewer_close_all', 'button_close_all', 'quest_viewer', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.quest_reward_submit = new nsButtonSet('quest_reward_submit', 'button_default', 'quest_viewer');
ns_button.buttons.quest_reward_submit.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.quest_viewer;
    let m_ques_pk = dialog.data;

    const okFunction = function()
    {
        let post_data = {};
        post_data['m_ques_pk'] = m_ques_pk;
        ns_xhr.post('/api/quest/reward', post_data, function (_data, _status)
        {
            if(! ns_xhr.returnCheck(_data)) {
                return;
            }
            _data = _data['ns_xhr_return']['add_data'];
            if (ns_cs.m.ques[m_ques_pk].type === 'lord_upgrade') {
                // TODO 군주 등급 상승 이벤트 처리가 필요하나 개인동맹이 연맹단위로 변경되었고 바뀌어야 할 부분이 많으므로 일단 메세지 처리로...
                let _level = ns_cs.m.ques[m_ques_pk].lord_upgrade;
                ns_dialog.setDataOpen('message', ns_i18n.t('msg_quest_reward_lord_level', [_level]));
                // ns_dialog.setDataOpen('lord_upgrade', {level: _level});
                /*if (ns_cs.d.lord.last_limit_buy.v < _level) {
                    ns_dialog.setDataOpen('limit_buy', {level: _level});
                }*/
            } else {
                // 보상 지급 그려주기 필요함
                ns_dialog.setDataOpen('message', ns_i18n.t('msg_quest_reward_complete')); // 퀘스트 보상 지급이 완료되었습니다.
                ns_sound.play('quest_receive');
            }
            ns_dialog.close('quest_viewer');
            ns_dialog.dialogs.quest.drawTab(); // 퀘스트 리스트 새로 그리기
            // qbw_quest_view.draw(); // 퀘스트 뷰 새로 그리기

            if (ns_cs.m.ques[m_ques_pk]?.next_ques_pk) {
                ns_dialog.setDataOpen('quest_viewer', ns_cs.m.ques[m_ques_pk].next_ques_pk);
            }
        }, { useProgress: true });
    }

    try {
        // 자원 체크
        let m = ns_cs.m.ques[m_ques_pk];
        let over_resource = false, alert_message = '';
        let types = ['food', 'horse', 'lumber', 'iron'];
        for (let _type of types) {
            if (m[_type] !== null && ns_util.math(ns_cs.getResourceInfo(`${_type}_curr`)).plus(m[_type]).gt(ns_cs.d.reso[`${_type}_max`].v) ) {
                over_resource = true;
                let loss_amount = ns_util.math(ns_cs.getResourceInfo(`${_type}_curr`)).plus(m[_type]).minus(ns_cs.d.reso[`${_type}_max`].v).number_format;
                alert_message += ns_i18n.t(`resource_${_type}`) + ` ${ns_i18n.t('amount_of_loss')}: ${loss_amount}<br />`;
            }
        }
        if (over_resource === true) {
            // 자원의 최대 보유량을 초과하여<br />자원 손실이 발생 할 수 있습니다.<br />창고를 업그레이드하거나 추가 건설을 하십시오.<br /><br /><span class="text_red">$1</span><br />퀘스트 보상을 받으시겠습니까?
            ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_reward_quest', [alert_message]), okFunc : okFunction });
        } else {
            okFunction();
        }
    } finally {

    }
}

ns_button.buttons.quest_shortcut = new nsButtonSet('quest_shortcut', 'button_default', 'quest_viewer');
ns_button.buttons.quest_shortcut.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.quest_viewer;
    let m_ques_pk = (! dialog?.data?.m_ques_pk) ? dialog.data : dialog.data.m_ques_pk;
    let m = ns_cs.m.ques[m_ques_pk];
    ns_dialog.closeAll();
    if (m.main_dlg) {
        let regexp = new RegExp(/^build_\D*/g), dialog_id;
        if (regexp.test(m.main_dlg)) {
            dialog_id = m.main_dlg;
            let code = dialog_id.split('_').pop();
            let bd_m = ns_cs.m.buil[code];
            if (bd_m) {
                // let bd_c = (bd_m.type === 'O') ? ns_cs.d.bdoc : ns_cs.d.bdic;
                let castle_type = (bd_m.type === 'O') ? 'bdoc' : 'bdic';
                let buildings = ns_cs.getBuildList(bd_m.m_buil_pk);
                if (buildings.length < 1) {
                    ns_dialog.setDataOpen('build_upgrade', {
                        m_buil_pk: bd_m.m_buil_pk,
                        castle_type: castle_type,
                        castle_pk: ns_cs.getEmptyTile(bd_m.type)
                    });
                } else {
                    buildings.sort((a, b) => (ns_util.math(a[1].level).gt(b[1].level)) ? -1 : (ns_util.math(a[1].level).lt(b[1].level)) ? 1 : 0);
                    ns_dialog.setDataOpen(dialog_id, {castle_pk: buildings[0][0], castle_type: castle_type});
                }
            }
        } else if ('qbig' === m.main_dlg) {
            window.open(`/redirect?type=purchase&platform=${ns_engine.cfg.app_platform}`, '_blank');
        } else {
            ns_dialog.open(m.main_dlg);
        }
    }

    if (m.btn_id && ns_button.buttons[m.btn_id]) {
        ns_button.buttons[m.btn_id].mouseUp();
    }
}

ns_button.buttons.toggle_quest_description = new nsButtonSet('toggle_quest_description', 'button_toggle_description', 'quest_viewer');
ns_button.buttons.toggle_quest_description.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.quest_viewer;
    dialog.cont_obj.quest_description_wrap.toggle();
    this.toggleClicked();
}

// reward_information
ns_dialog.dialogs.reward_information = new nsDialogSet('reward_information', 'dialog_pop', 'size-medium', { do_content_scroll: false, do_close_all: false });

ns_dialog.dialogs.reward_information.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.item_image = new nsObject('.item_image', this.obj);
    this.cont_obj.item_description = new nsObject('.item_description', this.obj);
}

ns_dialog.dialogs.reward_information.draw = function()
{
    if (!this.data.m_item_pk) {
        return;
    }
    let m_item_pk = this.data.m_item_pk;
    let m = ns_cs.m.item[m_item_pk];

    this.cont_obj.content_pop_title.text(m.title);
    this.cont_obj.item_image.addCss(`item_image_${m_item_pk}`);
    let description_detail = m.description_detail;
    if (m.use_type === 'package' && m.supply_amount !== '') {
        description_detail = description_detail.replace(/\{\{item\}\}/g, ns_util.convertPackageDescription(m_item_pk));
    }
    this.cont_obj.item_description.html(description_detail);
}

ns_dialog.dialogs.reward_information.erase = function()
{
    this.cont_obj.item_image.removeCss(`item_image_${this.data.m_item_pk}`);
    this.data = null;
}

/* ************************************************** */
ns_button.buttons.reward_information_close = new nsButtonSet('reward_information_close', 'button_pop_close', 'reward_information', { base_class: ns_button.buttons.common_close });
ns_button.buttons.reward_information_sub_close = new nsButtonSet('reward_information_sub_close', 'button_full', 'reward_information', { base_class: ns_button.buttons.common_sub_close });