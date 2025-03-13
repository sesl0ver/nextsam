// 이벤트 및 공지
ns_dialog.dialogs.notice = new nsDialogSet('notice', 'dialog_full', 'size-full', { do_close_all: false });

ns_button.buttons.notice_close = new nsButtonSet('notice_close', 'button_back', 'notice', { base_class: ns_button.buttons.common_close });
ns_button.buttons.notice_sub_close = new nsButtonSet('notice_sub_close', 'button_full', 'notice', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.notice_close_all = new nsButtonSet('notice_close_all', 'button_close_all', 'notice', { base_class: ns_button.buttons.common_close_all });

ns_dialog.dialogs.main_event = new nsDialogSet('main_event', 'dialog_full', 'size-full', { do_close_all: false });
ns_dialog.dialogs.main_event.open_flag = true;

ns_dialog.dialogs.main_event.cacheContents = function ()
{
}

ns_dialog.dialogs.main_event.draw = function ()
{
    // 영웅 모집 배너 갯수에 따라 length 값을 조정해주어야함.
    for (let _i of Array.from({length: 7}, (_, i) => i + 1)) {
        ns_button.buttons.banner_hero_pickup_event.obj.removeCss(`type_0${_i}`);
    }
}

ns_dialog.dialogs.main_event.checkDot = function ()
{
    let total = 0;

    let time_buff_end = ns_util.math(ns_cs.d.event['time_buff_end'].v).minus(ns_timer.now()).number;
    let treasure_end = ns_util.math(ns_cs.d.event['treasure_end'].v).minus(ns_timer.now()).number;

    if (ns_util.math(time_buff_end).lte(0)) {
        ns_button.buttons.banner_time_buff_event.obj.removeCss('main_flag_new');
    } else if (ns_util.math(ns_cs.d.event['time_buff_max'].v).gt(ns_cs.d.event['time_buff_count'].v)) {
        ns_button.buttons.banner_time_buff_event.obj.addCss('main_flag_new');
        total++;
    } else {
        ns_button.buttons.banner_time_buff_event.obj.removeCss('main_flag_new');
    }

    if (! window.localStorage.getItem('event_dot_hero_pickup_event')) {
        ns_button.buttons.banner_hero_pickup_event.obj.addCss('main_flag_new');
        total++;
    } else {
        ns_button.buttons.banner_hero_pickup_event.obj.removeCss('main_flag_new');
    }

    if (ns_util.math(treasure_end).lte(0)) {
        ns_button.buttons.banner_treasure_event.obj.removeCss('main_flag_new');
    } else if (ns_dialog.dialogs.treasure_event.checkMaterial()) {
        ns_button.buttons.banner_treasure_event.obj.addCss('main_flag_new');
        total++;
    } else {
        ns_button.buttons.banner_treasure_event.obj.removeCss('main_flag_new');
    }

    // 황건적 보상 2배
    if (! window.localStorage.getItem('event_dot_04')) {
        ns_button.buttons.banner_event_04.obj.addCss('main_flag_new');
        total++;
    } else {
        ns_button.buttons.banner_event_04.obj.removeCss('main_flag_new');
    }

    // 일반 조합 확률업
    if (! window.localStorage.getItem('event_dot_05')) {
        ns_button.buttons.banner_event_05.obj.addCss('main_flag_new');
        total++;
    } else {
        ns_button.buttons.banner_event_05.obj.removeCss('main_flag_new');
    }

    // 한정 퀘스트
    if (! window.localStorage.getItem('event_dot_06')) {
        ns_button.buttons.banner_event_06.obj.addCss('main_flag_new');
        total++;
    } else {
        ns_button.buttons.banner_event_06.obj.removeCss('main_flag_new');
    }

    if (ns_util.math(total).gt(0)) {
        ns_button.buttons.main_menu.obj.addCss('main_flag_new');
        ns_button.buttons.menu_event.obj.addCss('main_flag_new');
    } else {
        ns_button.buttons.main_menu.obj.removeCss('main_flag_new');
        ns_button.buttons.menu_event.obj.removeCss('main_flag_new');
    }
}

ns_dialog.dialogs.main_event.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';
    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.main_event.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.main_event.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.main_event;

    let today = ns_util.getDateFormatMs(Date.now(), 'YYYY-MM-DD HH:mm:ss', true);

    let m_pick = ns_cs.m.pick;
    let pickup_type = '0';
    for (let m of Object.values(m_pick)) {
        let now_time = ns_util.getTimestamp(today);
        if (m.start_date !== null) {
            let start_time = ns_util.getTimestamp(`${m.start_date} 00:00:00`);
            let end_time = ns_util.getTimestamp(`${m.end_date} 00:00:00`);
            if (ns_util.math(start_time).lte(now_time) && ns_util.math(end_time).gt(now_time)) {
                pickup_type = m.pickup_type;
            }
        }
    }
    if (pickup_type === '0') { // 모두 종료
        ns_button.buttons.banner_hero_pickup_event.obj.hide();
    } else { // 진행 중
        ns_button.buttons.banner_hero_pickup_event.obj.show();
        if (['2', '3'].includes(pickup_type)) {
            ns_button.buttons.banner_hero_pickup_event.obj.addCss('type_01');
        } else if (['4', '5'].includes(pickup_type)) {
            ns_button.buttons.banner_hero_pickup_event.obj.addCss('type_02');
        } else if (['6', '7'].includes(pickup_type)) {
            ns_button.buttons.banner_hero_pickup_event.obj.addCss('type_03');
        } else if (['8', '9'].includes(pickup_type)) {
            ns_button.buttons.banner_hero_pickup_event.obj.addCss('type_04');
        } else if (['10', '11'].includes(pickup_type)) {
            ns_button.buttons.banner_hero_pickup_event.obj.addCss('type_05');
        } else if (['12', '13'].includes(pickup_type)) {
            ns_button.buttons.banner_hero_pickup_event.obj.addCss('type_06');
        } else if (['14', '15'].includes(pickup_type)) {
            ns_button.buttons.banner_hero_pickup_event.obj.addCss('type_07');
        }
    }
}

ns_button.buttons.main_event_close = new nsButtonSet('main_event_close', 'button_back', 'main_event', { base_class: ns_button.buttons.common_close });
ns_button.buttons.main_event_sub_close = new nsButtonSet('main_event_sub_close', 'button_full', 'main_event', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.main_event_close_all = new nsButtonSet('main_event_close_all', 'button_close_all', 'main_event');
ns_button.buttons.main_event_close_all.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.main_event;

    // 최초 오픈일 경우 모두 닫기 하면 안됨
    if (dialog.open_flag === true) {
        ns_dialog.close('main_event');
    } else {
        ns_dialog.closeAll();
    }
}

ns_button.buttons.event_banner_open = new nsButtonSet('event_banner_open', null, 'main_event');
ns_button.buttons.event_banner_open.mouseUp = function ()
{
    let id = this.tag_id.split('banner_').pop();
    switch (id) {
        case 'hero_pickup_event':
            window.localStorage.setItem('event_dot_hero_pickup_event', String(ns_timer.now()));
            ns_button.buttons.banner_hero_pickup_event.obj.removeCss('main_flag_new');
            ns_dialog.open('hero_pickup');
            break;
        case 'event_04': // 황건적 보상 2배
            window.localStorage.setItem('event_dot_04', String(ns_timer.now()));
            ns_button.buttons.banner_event_04.obj.removeCss('main_flag_new');
            window.open(`/redirect?type=event_04&platform=${ns_engine.cfg.app_platform}`, '_blank');
            break;
        case 'event_05': // 일반 조합 확률업
            window.localStorage.setItem('event_dot_06', String(ns_timer.now()));
            ns_button.buttons.banner_event_06.obj.removeCss('main_flag_new');
            window.open(`/redirect?type=event_06&platform=${ns_engine.cfg.app_platform}`, '_blank');
            break;
        case 'event_06': // 한정 퀘스트
            ns_dialog.open('quest');
            break;
        default:
            ns_dialog.open(id);
            break;
    }
    ns_dialog.dialogs.main_event.checkDot();
}
ns_button.buttons.banner_time_buff_event = new nsButtonSet('banner_time_buff_event', null, 'main_event', { base_class: ns_button.buttons.event_banner_open });
ns_button.buttons.banner_hero_pickup_event = new nsButtonSet('banner_hero_pickup_event', null, 'main_event', { base_class: ns_button.buttons.event_banner_open });
ns_button.buttons.banner_treasure_event = new nsButtonSet('banner_treasure_event', null, 'main_event', { base_class: ns_button.buttons.event_banner_open });
ns_button.buttons.banner_event_04 = new nsButtonSet('banner_event_04', null, 'main_event', { base_class: ns_button.buttons.event_banner_open });
ns_button.buttons.banner_event_05 = new nsButtonSet('banner_event_05', null, 'main_event', { base_class: ns_button.buttons.event_banner_open });
ns_button.buttons.banner_event_06 = new nsButtonSet('banner_event_06', null, 'main_event', { base_class: ns_button.buttons.event_banner_open });

// 출석 이벤트
ns_dialog.dialogs.attendance_event = new nsDialogSet('attendance_event', 'dialog_building', 'size-large', { do_close_all: false });
ns_dialog.dialogs.attendance_event.sorted = null;
ns_dialog.dialogs.attendance_event.open_flag = true;

ns_dialog.dialogs.attendance_event.cacheContents = function()
{
    this.cont_obj.attendance_box_skeleton = new nsObject('#attendance_box_skeleton');
    this.cont_obj.content_message = new nsObject('.content_message', this.obj);
    this.cont_obj.reward_tbody_wrap = new nsObject('.reward_tbody_wrap', this.obj);
}

ns_dialog.dialogs.attendance_event.draw = function()
{
    ns_xhr.post('/api/event/attendance', {}, this.drawAttendance, { useProgress: true });
}

ns_dialog.dialogs.attendance_event.erase = function()
{
    this.data = null;
    // 진언창
    if (this.open_flag) {
        // 게임 최초 접속 시 진언창
        if (ns_cs.d.lord['setting']['counsel_connect'] !== 'N') {
            ns_dialog.setDataOpen('counsel', { type: 'connect' });
        } else {
            if (ns_engine.game_data.first_popup_package !== null) {
                if (Object.keys(ns_engine.game_data.package_data).length > 0) {
                    // 게임 최초 접속시 1회 띄워주기 위해
                    ns_dialog.setDataOpen('package_popup', { m_package_pk: ns_engine.game_data.first_popup_package });
                }
            }
        }
        this.open_flag = false;
    }
}

ns_dialog.dialogs.attendance_event.drawAttendance = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.attendance_event;
    // let data = {};
    // data['m_hero_pk'] = _data.hero_msg.m_hero_pk;

    dialog.buttonClear();
    dialog.cont_obj.reward_tbody_wrap.empty();
    for (let [k, d] of Object.entries(_data.reward_list)) {
        if (! d) {
            continue;
        }
        let buttonEvent = null;
        if (ns_util.math(20).lte(_data.cnt)) {
            dialog.scroll_handle.set(-200);
        }
        if (d.reward_type === 'hero') {
            let box = dialog.cont_obj.attendance_box_skeleton.clone();
            box.find('.attendance_title').text(`${k}`);
            box.find('.item_image').addCss(`hero_reward_${d.pk}`).setAttribute('id', `ns_button_attendance_reward_${k}`);
            if (ns_util.math(k).lte(_data.cnt)) {
                box.addCss('check');
            }
            box.find('.reward_count').text(ns_i18n.t(`hero_name_${d.pk}`));
            dialog.cont_obj.reward_tbody_wrap.append(box);
            buttonEvent = () =>
            {
                ns_dialog.dialogs.hero_card.hide_button = true;
                ns_dialog.setDataOpen('hero_card', ns_hero.dummyData(d.pk));
            }
        } else {
            let box = dialog.cont_obj.attendance_box_skeleton.clone();
            box.find('.attendance_title').text(`${k}`);
            box.find('.item_image').addCss(`item_image_${d.pk}`).setAttribute('id', `ns_button_attendance_reward_${k}`);
            if (ns_util.math(k).lte(_data.cnt)) {
                box.addCss('check');
            }
            box.find('.reward_count').text(ns_i18n.t('item_count', [d.value]));
            dialog.cont_obj.reward_tbody_wrap.append(box);
            buttonEvent = () =>
            {
                ns_dialog.setDataOpen('reward_information', { m_item_pk: d.pk })
            }
        }

        let button_id = `attendance_reward_${k}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, null, 'attendance_event');
        ns_button.buttons[button_id].mouseUp = buttonEvent
    }
};

ns_button.buttons.attendance_event_close = new nsButtonSet('attendance_event_close', 'button_back', 'attendance_event', { base_class: ns_button.buttons.common_close });
ns_button.buttons.attendance_event_sub_close = new nsButtonSet('attendance_event_sub_close', 'button_full', 'attendance_event', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.attendance_event_close_all = new nsButtonSet('attendance_event_close_all', 'button_close_all', 'attendance_event');
ns_button.buttons.attendance_event_close_all.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.attendance_event;

    // 최초 오픈일 경우 모두 닫기 하면 안됨
    if (dialog.open_flag === true) {
        ns_dialog.close('attendance_event');
    } else {
        ns_dialog.closeAll();
    }
}

ns_button.buttons.attendance_event_close_sub = new nsButtonSet('attendance_event_close_sub', 'button_special', 'attendance_event');
ns_button.buttons.attendance_event_close_sub.mouseUp = function()
{
    // 닫을때 쿠키 입력
    ns_util.setCookie(`${ns_cs.d.lord.lord_pk.v}_attendance`);
    ns_dialog.close('attendance_event');
}

/* 터임 버프 이벤트 */
ns_dialog.dialogs.time_buff_event = new nsDialogSet('time_buff_event', 'dialog_full', 'size-full', { do_close_all: false });
ns_dialog.dialogs.time_buff_event.types = ['event_enchant', 'event_cure', 'event_troop', 'event_army_build', 'event_cons_build', 'event_encounter', 'event_tech_build'];
ns_dialog.dialogs.time_buff_event.column = [2, 3, 2];

ns_dialog.dialogs.time_buff_event.cacheContents = function()
{
    // 버프 이벤트 아이템 정리
    this.event_item = Object.values(ns_cs.m.item).filter(o => this.types.includes(o.use_type));
    this.event_item_pk = this.event_item.map(o => o.m_item_pk);

    this.cont_obj.time_buff_rule_wrap = new nsObject('.time_buff_rule_wrap', this.obj);

    this.cont_obj.time_buff_wrap = new nsObject('.time_buff_wrap', this.obj);
    this.cont_obj.remain_time = new nsObject('.remain_time', this.obj);
    this.cont_obj.use_count = new nsObject('.use_count', this.obj);
    this.cont_obj.refresh_description = new nsObject('.refresh_description', this.obj);
    this.cont_obj.use_description = new nsObject('.use_description', this.obj);

    this.cont_obj.skeleton_time_buff_item = new nsObject('#skeleton_time_buff_item');

    this.drawItem();
}

ns_dialog.dialogs.time_buff_event.draw = function ()
{
    this.cont_obj.refresh_description.text(ns_i18n.t('time_buff_refresh_description', ['09']));
    this.cont_obj.use_description.text(ns_i18n.t('time_buff_use_description', [ns_cs.d.event['time_buff_max'].v]));
}

ns_dialog.dialogs.time_buff_event.drawItem = function()
{
    let dialog = ns_dialog.dialogs.time_buff_event;
    let row = 0;
    let column = 0;
    for (let o of dialog.cont_obj.time_buff_wrap.findAll('.item_wrap')) {
        o.empty();
    }
    for (let pk of dialog.event_item_pk) {
        let button_id = `use_tim_buff_${pk}`;
        let button_info_id = `info_tim_buff_${pk}`;
        let item = dialog.cont_obj.skeleton_time_buff_item.clone();
        item.find('.item_image').addCss(`item_image_${pk}`).setAttribute('id', `ns_button_${button_info_id}`);
        item.find('.time_buff_title').text(ns_cs.m.item[pk].title);
        item.find('.time_buff_button').setAttribute('id', `ns_button_${button_id}`);
        dialog.cont_obj.time_buff_wrap.find(`.item_wrap.row${(row + 1)}`).append(item);

        ns_button.buttons[button_info_id] = new nsButtonSet(button_info_id, null, 'time_buff_event');
        ns_button.buttons[button_info_id].mouseUp = function()
        {
            ns_dialog.setDataOpen('reward_information', { m_item_pk: pk });
        }

        ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_middle_2', 'time_buff_event');
        ns_button.buttons[button_id].mouseUp = function()
        {
            let message = ns_i18n.t('msg_use_time_buff_confirm', [ns_cs.m.item[pk].title]); // 선택한 [{{1}}]를 사용 하시겠습니까?
            let check = Object.entries(ns_cs.d.time).some(o => ns_util.isNumeric(o[0]) && o[1].queue_type === 'B'  && ns_util.math(o[1].description.split(':')[0]).eq(pk));
            if (check) {
                message = ns_i18n.t('msg_same_time_buff_confirm', [ns_cs.m.item[pk].title]);
            }
            ns_dialog.setDataOpen('confirm', { text: message,
                okFunc: () =>
                {
                    let post_data = {};
                    post_data['action'] = 'time_event';
                    post_data['item_pk'] = pk;
                    ns_xhr.post('/api/item/use', post_data, (_data, _status) => {
                        if (!ns_xhr.returnCheck(_data)) {
                            return;
                        }
                        // _data = _data['ns_xhr_return']['add_data'];
                        ns_dialog.setDataOpen('message', ns_i18n.t('msg_use_time_buff_event', [ns_cs.m.item[pk].title])); // [{{1}}]를 적용하였습니다.
                    }, { useProgress: true });
                }
            });
        }

        // 버튼 배치를 위해
        column++;
        if (dialog.column[row] === column) {
            row++;
            column = 0;
        }
    }
}

ns_dialog.dialogs.time_buff_event.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';
    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.time_buff_event.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.time_buff_event.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.time_buff_event;

    let end_date = ns_util.math(ns_cs.d.event['time_buff_end'].v).minus(ns_timer.now()).number;

    dialog.cont_obj.remain_time.text(ns_util.getCostsTime(end_date, 'event_end'));
    dialog.cont_obj.use_count.text(ns_i18n.t('number_count', [ns_util.math(ns_cs.d.event['time_buff_max'].v).minus(ns_cs.d.event['time_buff_count'].v).number]));

    for (let pk of dialog.event_item_pk) {
        let button_id = `use_tim_buff_${pk}`;
        if (ns_util.math(end_date).lte(0)) {
            ns_button.buttons[button_id].setDisable();
        } else if (ns_util.math(ns_cs.d.event['time_buff_max'].v).lte(ns_cs.d.event['time_buff_count'].v)) {
            ns_button.buttons[button_id].setDisable();
        } else {
            ns_button.buttons[button_id].setEnable();
        }
    }
}

ns_button.buttons.time_buff_event_close = new nsButtonSet('time_buff_event_close', 'button_back', 'main_event', { base_class: ns_button.buttons.common_close });
ns_button.buttons.time_buff_event_sub_close = new nsButtonSet('time_buff_event_sub_close', 'button_full', 'main_event', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.time_buff_event_close_all = new nsButtonSet('time_buff_event_close_all', 'button_close_all', 'main_event', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.time_buff_tooltip = new nsButtonSet('time_buff_tooltip', 'button_tooltip_rule', 'main_event');
ns_button.buttons.time_buff_tooltip.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.time_buff_event;

    if (dialog.cont_obj.time_buff_rule_wrap.hasCss('hide')) {
        dialog.scroll_handle.initScroll();
        dialog.scroll_handle.pause();
        dialog.cont_obj.time_buff_rule_wrap.show();
    } else {
        dialog.scroll_handle.resume();
        dialog.cont_obj.time_buff_rule_wrap.hide();
    }
}

ns_button.buttons.time_buff_tooltip_close = new nsButtonSet('time_buff_tooltip_close', 'button_tooltip_close', 'main_event', { base_class: ns_button.buttons.time_buff_tooltip });

// 보물 찾기 이벤트
ns_dialog.dialogs.treasure_event = new nsDialogSet('treasure_event', 'dialog_full', 'size-full', { do_close_all: false });
ns_dialog.dialogs.treasure_event.materials = [500803, 500804, 500805, 500806, 500807];

ns_dialog.dialogs.treasure_event.cacheContents = function()
{
    this.cont_obj.treasure_event_rule_wrap = new nsObject('.treasure_event_rule_wrap', this.obj);

    this.cont_obj.remain_time = new nsObject('.remain_time', this.obj);
    this.cont_obj.material_list = new nsObject('.material_list', this.obj);

    this.cont_obj.skeleton_treasure_item = new nsObject('#skeleton_treasure_item');
}

ns_dialog.dialogs.treasure_event.draw = function()
{
    this.drawMaterial();
}

ns_dialog.dialogs.treasure_event.drawMaterial = function()
{
    this.cont_obj.material_list.empty();
    for (let pk of this.materials) {
        let item = this.cont_obj.skeleton_treasure_item.clone();
        item.find('.item_title').text(this.getMaterialTitle(pk));
        let count = ns_cs.d.item[pk]?.item_cnt ?? 0;
        item.find('.item_count').text('x' + count);
        this.cont_obj.material_list.append(item);
    }
}

ns_dialog.dialogs.treasure_event.getMaterialTitle = function(_pk)
{
    switch (_pk) {
        case 500803:
            return 'Ⅰ';
        case 500804:
            return 'Ⅱ';
        case 500805:
            return 'Ⅲ';
        case 500806:
            return 'Ⅳ';
        case 500807:
            return 'Ⅴ';
    }
}

ns_dialog.dialogs.treasure_event.checkMaterial = function()
{
    let dialog = ns_dialog.dialogs.treasure_event;
    let enable = true;
    for (let pk of dialog.materials) {
        if (! ns_cs.d.item[pk] || ns_util.math(ns_cs.d.item[pk]?.item_cnt ?? 0).lt(1)) {
            enable = false;
        }
    }
    return enable;
}

ns_dialog.dialogs.treasure_event.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';
    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.treasure_event.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.treasure_event.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.treasure_event;

    let end_date = ns_util.math(ns_cs.d.event['treasure_end'].v).minus(ns_timer.now()).number;
    dialog.cont_obj.remain_time.text(ns_util.getCostsTime(end_date, 'event_end'));

    dialog.drawMaterial();

    if (dialog.checkMaterial()) {
        ns_button.buttons.treasure_reward.setEnable();
    } else {
        ns_button.buttons.treasure_reward.setDisable();
    }
}

ns_button.buttons.treasure_event_close = new nsButtonSet('treasure_event_close', 'button_back', 'main_event', { base_class: ns_button.buttons.common_close });
ns_button.buttons.treasure_event_sub_close = new nsButtonSet('treasure_event_sub_close', 'button_full', 'main_event', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.treasure_event_close_all = new nsButtonSet('treasure_event_close_all', 'button_close_all', 'main_event', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.treasure_event_tooltip = new nsButtonSet('treasure_event_tooltip', 'button_tooltip_rule', 'main_event');
ns_button.buttons.treasure_event_tooltip.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.treasure_event;

    if (dialog.cont_obj.treasure_event_rule_wrap.hasCss('hide')) {
        dialog.scroll_handle.initScroll();
        dialog.scroll_handle.pause();
        dialog.cont_obj.treasure_event_rule_wrap.show();
    } else {
        dialog.scroll_handle.resume();
        dialog.cont_obj.treasure_event_rule_wrap.hide();
    }
}

ns_button.buttons.treasure_event_tooltip_close = new nsButtonSet('treasure_event_tooltip_close', 'button_tooltip_close', 'main_event', { base_class: ns_button.buttons.treasure_event_tooltip });

ns_button.buttons.treasure_reward = new nsButtonSet('treasure_reward', 'button_event', 'main_event');
ns_button.buttons.treasure_reward.mouseUp = function ()
{
    ns_xhr.post('/api/event/treasure', {} , (_data, _status) => {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];
        ns_dialog.setDataOpen('message', _data);
    }, { useProgress: true });
}