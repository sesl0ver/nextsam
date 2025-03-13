ns_dialog.dialogs.report = new nsDialogSet('report', 'dialog_full', 'size-full');
ns_dialog.dialogs.report.current_tab = null;
ns_dialog.dialogs.report.current_page = 0;
ns_dialog.dialogs.report.total_page = 0;
ns_dialog.dialogs.report.receiver_list = null;
ns_dialog.dialogs.report.list = null;
ns_dialog.dialogs.report.read_pk = null;
ns_dialog.dialogs.report.report_data = null;
ns_dialog.dialogs.report.alliance_troop_request = false;

ns_dialog.dialogs.report.tab = null;
ns_dialog.dialogs.report.sub_tab = null; // tab2와 tab3 가 혼재하기 때문에 easy use 를 위해 캐싱

ns_dialog.dialogs.report.column = {};
ns_dialog.dialogs.report.column['scout'] = {'title': ns_i18n.t('subject'), 'to_posi_name': ns_i18n.t('our_forces'), 'from_posi_name': ns_i18n.t('enemy_forces'), 'send_dt': ns_i18n.t('report_datetime') };
ns_dialog.dialogs.report.column['battle'] = ns_dialog.dialogs.report.column['scout'];
ns_dialog.dialogs.report.column['recall'] = {'title': ns_i18n.t('subject'), 'to_posi_name': ns_i18n.t('destination'), 'from_posi_name': ns_i18n.t('departure'), 'send_dt': ns_i18n.t('report_datetime') };
ns_dialog.dialogs.report.column['move'] = ns_dialog.dialogs.report.column['recall'];
ns_dialog.dialogs.report.column['misc'] = {'title': ns_i18n.t('subject'), 'to_posi_name': ns_i18n.t('belong'), 'from_posi_name': ns_i18n.t('target'), 'send_dt': ns_i18n.t('report_datetime') };
ns_dialog.dialogs.report.column['receive'] = {'title': ns_i18n.t('subject'), 'from_lord_name': ns_i18n.t('sender'), 'send_dt': ns_i18n.t('receive_datetime') };
ns_dialog.dialogs.report.column['system'] = ns_dialog.dialogs.report.column['receive'];
ns_dialog.dialogs.report.column['send'] = {'title': ns_i18n.t('subject'), 'to_lord_name': ns_i18n.t('recipients'), 'send_dt': ns_i18n.t('send_datetime') };

ns_dialog.dialogs.report.cacheContents = function()
{
    // this.cont_obj.content_current_page = new nsObject('.content_current_page', this.obj);
    // this.cont_obj.content_total_page = new nsObject('.content_total_page', this.obj);

    this.cont_obj.tab_report_wrap = new nsObject('.tab_report_wrap', this.obj);
    this.cont_obj.tab_letter_wrap = new nsObject('.tab_letter_wrap', this.obj);

    this.cont_obj.text_notice = new nsObject('.text_notice', this.obj);

    this.cont_obj.content_list_wrap = new nsObject('.content_list_wrap', this.obj);
    this.cont_obj.content_write_wrap = new nsObject('.content_write_wrap', this.obj);
    this.cont_obj.content_read_wrap = new nsObject('.content_read_wrap', this.obj);

    // write
    this.cont_obj.letter_from = new nsObject('.letter_from', this.obj);
    this.cont_obj.letter_title = new nsObject('.letter_title', this.obj);
    this.cont_obj.letter_content = new nsObject('.letter_content', this.obj);
}

ns_dialog.dialogs.report.draw = function()
{
    if (! this.visible) {
        ns_button.toggleGroupSingle(ns_button.buttons.report_tab_report);
        ns_button.toggleGroupSingle(ns_button.buttons.report_tab2_scout);
        ns_button.toggleGroupSingle(ns_button.buttons.report_tab3_receive);

        this.current_tab = 'report';
        this.sub_tab = 'scout';
        this.cont_obj.tab_report_wrap.show();
        this.cont_obj.tab_letter_wrap.hide();

        this.current_page = 1;
    }

    // this.flag_init();
    this.drawCol();
    this.drawList();
}

ns_dialog.dialogs.report.erase = function()
{
    if (ns_dialog.dialogs.letter_add_receiver.visible) {
        ns_dialog.close('letter_add_receiver');
    }
    this.data = null;
}

ns_dialog.dialogs.report.flag_init = function()
{
    let dialog = ns_dialog.dialogs.report;

    // dialog.cont_obj.cont_report_flag_report.hide();
    // dialog.cont_obj.cont_report_flag_letter.hide();
    // dialog.cont_obj.cont_report_flag_scout.hide();
    // dialog.cont_obj.cont_report_flag_battle.hide();
    // dialog.cont_obj.cont_report_flag_recall.hide();
    // dialog.cont_obj.cont_report_flag_move.hide();
    // dialog.cont_obj.cont_report_flag_misc.hide();
    // dialog.cont_obj.cont_report_flag_receive.hide();
    // dialog.cont_obj.cont_report_flag_system.hide();
}

ns_dialog.dialogs.report.drawCol = function()
{
    let dialog = ns_dialog.dialogs.report;

    dialog.cont_obj.content_list_wrap.hide();
    dialog.cont_obj.content_write_wrap.hide();
    dialog.cont_obj.content_read_wrap.hide();
    ns_button.buttons.report_set_read.setEnable();
    ns_button.buttons.report_remove_list.setEnable();

    if (dialog.sub_tab === 'write') {
        this.receiver_list = null; // 초기화
        this.alliance_troop_request = false;
        this.writeInit(); // 초기화
        dialog.cont_obj.content_write_wrap.show();
        return false;
    } else {
        dialog.cont_obj.content_list_wrap.show();
    }

    // dialog.cont_obj.content_current_page.text(1);
    // dialog.cont_obj.content_total_page.text(1);

    if (dialog.current_tab === 'report') {
        dialog.cont_obj.content_list_wrap.find('th.col3').show();
    } else {
        dialog.cont_obj.content_list_wrap.find('th.col3').hide();
    }

    let cnt = 1;
    for (let [k, d] of Object.entries(dialog.column[dialog.sub_tab])) {
        if (['receive', 'system', 'send'].includes(dialog.sub_tab)) {
            if (cnt === 3) {
                dialog.cont_obj.content_list_wrap.find('th.col4').text(d);
                continue;
            }
        }
        dialog.cont_obj.content_list_wrap.find(`th.col${cnt}`).text(d);
        cnt++;
    }
    return true;
}

ns_dialog.dialogs.report.drawList = function()
{
    let dialog = ns_dialog.dialogs.report;

    dialog.read_pk = null;
    dialog.report_data = null;

    if (dialog.sub_tab === 'write') {
        return;
    }

    let post_data = {}, text_notice = '';
    let action = '';
    if (dialog.current_tab === 'report') {
        text_notice = ns_i18n.t('report_storage_period_description'); // 보고서는 5일간 보관되며 기간이 지나면 삭제됩니다.
        action = 'list';
        post_data['type'] = dialog.sub_tab;
    } else {
        text_notice = ns_i18n.t('letter_storage_period_description'); // 외교서신은 30일간 보관되며 기간이 지나면 삭제됩니다.
        action = `${dialog.sub_tab}List`;
    }
    dialog.cont_obj.text_notice.text(text_notice);

    post_data['page_num'] = dialog.current_page;

    ns_xhr.post(`/api/${dialog.current_tab}/${action}`, post_data, dialog.drawListRemote);
}

ns_dialog.dialogs.report.drawListRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.report;

    dialog.drawReportUnread();
    let list = _data?.list ?? _data?.letter_list ?? {};

    dialog.current_page = _data.curr_page;
    dialog.total_page = _data.total_page;

    // dialog.cont_obj.content_current_page.text(dialog.current_page);
    // dialog.cont_obj.content_total_page.text(dialog.total_page);

    let tbody = dialog.cont_obj.content_list_wrap.find('tbody');
    dialog.buttonClear();

    tbody.empty();
    if (list.length < 1) {
        let tr = document.createElement('tr');
        let td = document.createElement('td');
        td.colSpan = 5;
        if (dialog.current_tab === 'report') {
            td.innerText = ns_i18n.t('no_report_storage_description'); // 보관 중인 보고서가 없습니다.
        } else {
            td.innerText = ns_i18n.t('no_letter_storage_description'); // 보관 중인 외교 서신이 없습니다.
        }
        tr.appendChild(td);
        tbody.append(tr);
        return;
    }

    for (let d of Object.values(list)) {
        let tr = document.createElement('tr');
        if (d.yn_read.toLowerCase() === 'n') {
            tr.classList.add('new_letter_glow');
        }

        let span = document.createElement('span');
        let checkbox = document.createElement('span');
        checkbox.setAttribute('id', `ns_button_list_check_${d.repo_pk ?? d.lett_pk}`);
        span.appendChild(checkbox);

        let button = document.createElement('span');
        button.setAttribute('id', `ns_button_${dialog.current_tab}_read_${d.repo_pk ?? d.lett_pk}`);
        span.appendChild(button);

        let col = dialog.drawColumn(span, true);
        col.classList.add('vertical_super');
        tr.appendChild(col);

        let _title ;
        if (dialog.current_tab === 'report') {
            _title = code_set.report[d.report_type]?.subject ?? d.report_type;
        } else {
            _title = ns_util.forbiddenWordCheck(d.title);
        }

        col = dialog.drawColumn(_title);
        col.classList.add('text_align_left');
        tr.appendChild(col);

        let _text;
        if (dialog.current_tab === 'report') {
            if (['hero_bid_success', 'hero_bid_fail', 'hero_enchant_suc', 'hero_enchant_fal'].includes(d.report_type)) {
                _text = d.from_lord_name;
            } else if (['shipping_finish', 'shipping_sale', 'return_finish_1', 'return_finish_2', 'return_finish_3', 'return_finish_4', 'return_finish_5',
                'return_finish_6', 'return_finish_7', 'return_finish_8', 'trans_finish', 'preva_finish'].includes(d.report_type)) {
                _text = ns_text.convertPositionName(d.to_posi_name, true, true, false);
            } else {
                _text = ns_text.convertPositionName(d.from_posi_name, true, true, false);
            }
        } else {
            _text = (dialog.sub_tab !== 'send') ? d.from_lord_name : d.to_lord_name;
        }
        col = dialog.drawColumn(_text);
        tr.appendChild(col);

        if (dialog.current_tab === 'report') {
            if (['hero_bid_success', 'hero_bid_fail', 'hero_enchant_suc', 'hero_enchant_fal'].includes(d.report_type)) {
                _text = d.to_lord_name;
            } else if (['shipping_finish', 'shipping_sale', 'return_finish_1', 'return_finish_2', 'return_finish_3', 'return_finish_4', 'return_finish_5',
                'return_finish_6', 'return_finish_7', 'return_finish_8', 'trans_finish', 'preva_finish'].includes(d.report_type)) {
                _text = ns_text.convertPositionName(d.from_posi_name, true, true, false);
            } else if (['hero_skill_slot_expand'].includes(d.report_type)) {
                _text = d.to_posi_name;
            } else {
                _text = ns_text.convertPositionName(d.to_posi_name, true, true, false);
            }
            col = dialog.drawColumn(_text);
            tr.appendChild(col);
        }

        col = dialog.drawColumn(dialog.convertDate(d.send_dt));
        tr.appendChild(col);

        tbody.append(tr);

        let checkbox_id = `list_check_${d.repo_pk ?? d.lett_pk}`;
        ns_button.buttons[checkbox_id] = new nsButtonSet(checkbox_id, 'button_checkbox', 'report');
        ns_button.buttons[checkbox_id].mouseUp = function (e)
        {
            if (this.clicked === false) {
                this.setClicked();
                let is_exist_not_clicked = false;
                for( let d  of dialog.buttons ){
                    if ( !d.tag_id.includes('list_check_')) continue;
                    if ( d.clicked === false ){
                        is_exist_not_clicked = true;
                        break;
                    }
                }

                if ( is_exist_not_clicked === false ) {
                    ns_button.buttons.report_select_all.setClicked();
                }
            } else {
                this.unsetClicked();
                ns_button.buttons.report_select_all.unsetClicked();
            }
        }
        dialog.buttons.push(ns_button.buttons[checkbox_id]);

        let button_id = `${dialog.current_tab}_read_${d.repo_pk ?? d.lett_pk}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'report');
        ns_button.buttons[button_id].mouseUp = function (e)
        {
            dialog.drawDetailView(this.tag_id.split('_').pop());
        }
        dialog.buttons.push(ns_button.buttons[button_id]);
    }
};

ns_dialog.dialogs.report.drawColumn = function (_data, _append = false)
{
    let col = document.createElement('td');
    if (! _append) {
        let span = document.createElement('span');
        span.innerHTML = _data;
        col.appendChild(span);
    } else {
        col.appendChild(_data);
    }
    return col;
}

ns_dialog.dialogs.report.receiverAdd = function(_lord_pk, _lord_name)
{
    let dialog = ns_dialog.dialogs.report;

    if (! dialog.receiver_list) {
        dialog.receiver_list = {};
    }

    if (ns_util.math(ns_cs.d.lord.lord_pk.v).eq(_lord_pk)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_self_error')); // 자기 자신에게 발송할 수 없습니다.
        return;
    }

    if (dialog.receiver_list[_lord_pk]) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_same_user_error')); // 중복된 대상에게 발송할 수 없습니다.
        return;
    }

    if (!_lord_pk) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_incorrect_error')); // 올바르지 않은 대상에게 발송할 수 없습니다.
        return;
    }

    let count = Object.keys(dialog.receiver_list).length;
    if (count > 4) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_max_user_error')); // 동시에 5명을 초과해서 발송할 수 없습니다.
        return;
    }
    dialog.receiver_list[_lord_pk] = _lord_name;

    let div = document.createElement('div');
    div.classList.add('letter_from_lord');
    div.classList.add(`letter_receiver_lord_${_lord_pk}`);

    let span1 = document.createElement('span');
    span1.innerHTML = _lord_name;

    let span2 = document.createElement('span');
    span2.setAttribute('id', `ns_button_delete_letter_receiver_lord_${_lord_pk}`);

    div.appendChild(span1);
    div.appendChild(span2);

    dialog.cont_obj.letter_from.append(div);

    ns_button.buttons[`delete_letter_receiver_lord_${_lord_pk}`] = new nsButtonSet(`delete_letter_receiver_lord_${_lord_pk}`, 'button_cancel', 'report', { base_class: ns_button.buttons.delete_receiver_lord });
}

ns_dialog.dialogs.report.writeInit = function()
{
    let dialog = ns_dialog.dialogs.report;
    dialog.cont_obj.letter_from.empty();
    dialog.cont_obj.letter_title.value('');
    dialog.cont_obj.letter_content.value('');
}

ns_dialog.dialogs.report.sendLetter = function(_letter_title, _letter_content, _receiver_pk_array)
{
    // 수신인 목록은 반드시 군주 pk의 배열로 들어야됨
    if (!_receiver_pk_array instanceof Array) {
        return false;
    }
    let dialog = ns_dialog.dialogs.report;
    let post_data = {};
    post_data['receiver_lord_pk_list'] = _receiver_pk_array.join(',');
    post_data['title'] = _letter_title;
    post_data['content'] = _letter_content;
    post_data['alliance'] = dialog.alliance_troop_request;

    ns_xhr.post('/api/letter/sendLetter', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        // 발송하였으므로 발송 탭으로 이동
        ns_button.buttons.report_tab3_send.mouseUp();

        ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_send')); // 외교 서신을 발송하였습니다.
        dialog.alliance_troop_request = false;
    }, { useProgress: true });
}

ns_dialog.dialogs.report.convertDate = function(timestamp)
{
    // 목록에 표시할 시간의 형식을 반환 // 인자는 그냥 타임스탬프
    let _date = new Date();
    _date.setTime(timestamp * 1000);

    let month = ((String(_date.getMonth()).length < 2 && _date.getMonth() + 1 < 10)?'0':'') + (_date.getMonth() + 1);
    let day = ((String(_date.getDate()).length < 2 && _date.getDate() < 10)?'0':'') + (_date.getDate());
    let hour = ((String(_date.getHours()).length < 2 && _date.getHours() < 10)?'0':'') + (_date.getHours());
    let minute = ((String(_date.getMinutes()).length < 2 && _date.getMinutes() < 10)?'0':'') + (_date.getMinutes());
    let second = ((String(_date.getSeconds()).length < 2 && _date.getSeconds() < 10)?'0':'') + (_date.getSeconds());

    return String(month + '/' + day + ' ' + hour + ':' + minute + ':' + second);
}

ns_dialog.dialogs.report.drawDetailView = function(_pk)
{
    let dialog = ns_dialog.dialogs.report;

    dialog.cont_obj.content_write_wrap.hide();
    dialog.cont_obj.content_list_wrap.hide();
    dialog.cont_obj.content_read_wrap.show();
    ns_button.buttons.report_set_read.setDisable();
    ns_button.buttons.report_remove_list.setDisable();

    // 초기화
    dialog.cont_obj.content_read_wrap.find('.read_title').text('');
    dialog.cont_obj.content_read_wrap.find('.read_from_name').text('');
    dialog.cont_obj.content_read_wrap.find('.read_send_dt').text('');
    dialog.cont_obj.content_read_wrap.find('.read_content').empty();

    let post_data = {};
    ns_button.buttons.get_reward.obj.hide();
    if (dialog.current_tab === 'report') {
        // dialog.cont_obj.cont_read_info.hide();
        // dialog.cont_obj.cont_item_button_area.hide();

        ns_button.buttons.report_forward.obj.hide();
        ns_button.buttons.report_reply.obj.hide();

        dialog.read_pk = _pk;

        post_data['repo_pk'] = _pk;
        ns_xhr.post('/api/report/view', post_data, function(_data, _status)
        {
            if(! ns_xhr.returnCheck(_data)) {
                return;
            }
            _data = _data['ns_xhr_return']['add_data'];

            let title = code_set.report[_data.report_type]?.subject ?? _data.report_type;
            dialog.cont_obj.content_read_wrap.find('.read_title').text(title);

            dialog.drawReportType(_data);

            dialog.drawReportUnread();
        });
    }
    else if (dialog.current_tab === 'letter') {
        // dialog.cont_obj.cont_read_info.show();

        if (dialog.sub_tab !== 'receive') {
            ns_button.buttons.report_forward.obj.hide();
            ns_button.buttons.report_reply.obj.hide();
        } else {
            ns_button.buttons.report_forward.obj.show();
            ns_button.buttons.report_reply.obj.show();
        }
        dialog.read_pk = _pk;

        post_data['lett_pk'] = _pk;
        post_data['lett_type'] = dialog.sub_tab;

        ns_xhr.post('/api/letter/read', post_data, function(_data, _status)
        {
            if(! ns_xhr.returnCheck(_data)) {
                return;
            }
            _data = _data['ns_xhr_return']['add_data'];

            // 운영자가 보낸 외교 서신은 금칙어 검사를 하지 않음
            let title = (_data[_pk].type === 'S') ? _data[_pk].title : ns_util.forbiddenWordCheck(_data[_pk].title);
            let content = (_data[_pk].type === 'S') ? _data[_pk].content : ns_util.forbiddenWordCheck(_data[_pk].content);

            // let item_area = dialog.cont_obj.content_read_wrap.find('tfoot');

            dialog.cont_obj.content_read_wrap.find('.read_title').text(title);
            dialog.cont_obj.content_read_wrap.find('.read_from_name').text(_data[_pk].from_lord_name);
            dialog.cont_obj.content_read_wrap.find('.read_send_dt').text(dialog.convertDate(_data[_pk].send_dt));
            dialog.cont_obj.content_read_wrap.find('.read_content').html(ns_util.positionLink(content));
            /* TODO 당장은 서신에서 아이템을 사용하지 않으므로 주석처리 후 차후 사용시 다시 작업하기로...

            if (typeof _data[_pk].item_data === 'object') {
                if (Object.keys(_data[_pk].item_data).length < 1) {
                    return false;
                }

                Object.keys().forEach(function(d, k){
                    Object.values(_data[_pk].item_data[d]).forEach(function(reward, k2) {
                        let data = reward.split(':');
                        let data_pk = data[0];
                        let data_str = data[1];

                        let item_div = document.createElement('div');
                        let item_obj = document.createElement('div');
                        let item_cnt_obj = document.createElement('div');

                        item_div.classList.add('item_div');
                        item_obj.classList.add('item_img');
                        switch (d) {
                            case 'resource': // 자원
                                if (data_pk === 'population') {
                                    item_obj.classList.add('reward_box_population');
                                } else {
                                    item_obj.classList.add('reso_img_' + data_pk);
                                }
                                item_cnt_obj.innerHTML = data_str;
                                break;
                            case 'qbig': // 큐빅
                                item_obj.classList.add('item_img_' + 500495); // TODO 임시 리소스 이미지. 차후 사용시 수정 필요.
                                item_cnt_obj.innerHTML = data_str;
                                break;
                            case 'skill': // 기술
                                item_obj.classList.add('hero_skill_img_' + data_pk.substring(0, 4));
                                item_cnt_obj.innerHTML = data_str;

                                let rare_div = document.createElement('div');
                                rare_div.classList.add('skill_rare_img');
                                rare_div.classList.add('hero_skill_rare' + ns_cs.m.hero_skil[data_pk].rare);
                                item_div.appendChild(rare_div);

                                ns_button.newButtonEvent(item_div, function(){
                                    ns_dialog.setDataOpen('hero_skill_detail', {m_hero_skil_pk: data_pk, 'mode': 'simple'});
                                });
                                break;
                            case 'army': // 병력
                                item_obj.classList.add('buil_Army_desc_' + data_pk);
                                item_cnt_obj.innerHTML = data_str;

                                ns_button.newButtonEvent(item_div, function(){
                                    ns_dialog.setDataOpen('information', {title:'army', type:data_pk, m_pk:ns_cs.m.army[data_pk].m_army_pk, match_pk:null});
                                });
                                break;
                            case 'hero': // 영웅
                                item_obj.classList.add('hero_img');
                                item_obj.classList.add('herocard_' + data_pk);
                                item_cnt_obj.innerHTML = ns_cs.m.hero_base[data_pk].name;
                                break;
                            case 'fort': // 함정
                                item_obj.classList.add('buil_CastleWall_fort_desc_' + data_pk);
                                item_cnt_obj.innerHTML = data_str;

                                ns_button.newButtonEvent(item_div, function(){
                                    ns_dialog.setDataOpen('information', {title:'fort', type:data_pk, m_pk:ns_cs.m.fort[data_pk].m_fort_pk, match_pk:null});
                                });
                                break;
                            default :
                                item_obj.classList.add('item_img_' + data_pk);
                                item_cnt_obj.innerHTML = data_str;

                                ns_button.newButtonEvent(item_div, function(){
                                    ns_dialog.setDataOpen('reward_information', {m_item_pk: data_pk});
                                });
                                break;
                        }

                        item_div.appendChild(item_obj);
                        item_div.appendChild(item_cnt_obj);

                        dialog.cont_obj.cont_letter_item_list.append($(item_div));
                    });
                });
                dialog.contentRefresh();

                if (! _data[_pk].item_dt) {
                    ns_button.buttons.get_reward.setEnable();
                } else {
                    ns_button.buttons.get_reward.setDisable();
                }
            }

             */

            /*if (lett_type === 'receive') {
                dialog.list[_pk].yn_read = 'Y';
            }*/

            dialog.drawReportUnread();
        });
    }
};

ns_dialog.dialogs.report.drawReward = function(_rewards)
{
    console.log(_rewards);
}

ns_dialog.dialogs.report.findToAdd = function(lord_pk, lord_name)
{
    // 회신 시에 서신 pk에 있는 정보를 가지고 자동으로 수신인 추가까지 하기
    let dialog = ns_dialog.dialogs.report;
    if (! ns_util.isNumeric(lord_pk) || ns_util.math(lord_pk).lte(2)) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_incorrect_lord_error')); // 올바르지 않은 군주에게 회신할 수 없습니다.
        return;
    }

    let post_data = { };
    post_data['lord_pk'] = lord_pk;
    ns_xhr.post('/api/letter/findLordPk', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        for (let d of Object.values(_data)) {
            if (lord_name === d.lord_name) {
                dialog.receiverAdd(d.lord_pk, d.lord_name);
                break;
            }
        }
    });
}

ns_dialog.dialogs.report.deleteReport = function(_pk_array)
{
    // 인자로는 반드시 서신 pk의 배열이 와야됨
    if (!_pk_array instanceof Array) {
        return false;
    }

    let dialog = ns_dialog.dialogs.report;
    let post_data = {};
    if (dialog.current_tab === 'report') {
        post_data['repo_pk_list'] = _pk_array.join(',');
    } else if (dialog.current_tab === 'letter') {
        post_data['lett_pk_list'] = _pk_array.join(',');
        post_data['lett_type'] = (dialog.sub_tab !== 'send') ? 'receive' : dialog.sub_tab;
    }

    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_report_delete_confirm'), // 선택한 항목을 삭제합니다.<br />계속 진행하시겠습니까?
        okFunc: () =>
        {
            ns_xhr.post(`/api/${dialog.current_tab}/remove`, post_data, function(_data, _status)
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];

                // 삭제 시에는 기존에 보고 있던 탭을 다시 그려줘야됨
                dialog.drawCol();
                dialog.drawList();
                ns_button.buttons.report_select_all.unsetClicked();
            }, { useProgress: true });
        }
    });
};

ns_dialog.dialogs.report.drawReportType = function(_data)
{
    let dialog = ns_dialog.dialogs.report;

    dialog.cont_obj.content_read_wrap.find('.read_from_name').text(ns_i18n.t('sender') + ' : ' + _data?.to_lord_name);
    dialog.cont_obj.content_read_wrap.find('.read_send_dt').text(dialog.convertDate(_data.send_dt));

    let read_content = dialog.cont_obj.content_read_wrap.find('.read_content');

    dialog.report_data = _data;

    read_content.empty();

    let content_json = _data.content_json;
    let ns_report = new nsReport(read_content);
    let report_summary = code_set.report[_data.report_type]?.summary ?? _data.summary;
    let summary_data;

    switch (_data.report_type) {
        case 'scout_failure': // 정찰 실패
            ns_report.setSummary(ns_text.convertReportTitle(report_summary, [ns_util.numberFormat(content_json.scout_amount), ns_util.numberFormat(content_json.scout_dead)]));
            ns_report.drawBoxList(ns_i18n.t('army_information'), 'army', { scout: { amount: content_json.scout_amount, remain: ns_util.math(content_json.scout_amount).minus(content_json.scout_dead).number } });
            break;
        case 'scout_success': // 정찰 성공
            summary_data = ns_text.convertReportSummary(_data.report_type, _data.summary);
            ns_report.setSummary(ns_text.convertReportTitle(report_summary, summary_data));

            if (content_json?.hero_skill && content_json.hero_skill?.m_hero_skil_pk) {
                let name = ns_cs.m.hero_base[ns_cs.m.hero[content_json.hero_skill.hero_pk].m_hero_base_pk].name;
                for (let d of Object.values(content_json.hero_skill.m_hero_skil_pk)) {
                    let m = ns_cs.m.hero_skil[d];
                    ns_report.drawDescription(ns_i18n.t('activation_hero_skill'), name + ' : ' + m.title + ' Lv.' + m.rare);
                }
            }
            let scout_level = ns_util.toInteger(content_json.scout_level);

            // 방어시설. 정찰등급 1.
            ns_report.drawBoxList(ns_i18n.t('defense_facilities_size'), 'fort', { ...content_json.fort, wall: content_json.wall_level });

            // 보유자원. 정찰등급 2
            if (scout_level >= 2) {
                ns_report.drawBoxList(ns_i18n.t('amount_resources'), 'resource', content_json.reso);
            }

            // 총 병력 규모. 정찰등급 3
            if (scout_level >= 3) {
                ns_report.drawDescription(ns_i18n.t('total_troop_size'), (! content_json?.army_scale) ? ns_i18n.t('none') : ns_i18n.t('scout_success_scale', [ns_util.numberFormat(content_json.army_scale)]));
            }

            // 병력 규모. 정찰등급 4
            if (scout_level >= 4) {
                ns_report.drawBoxList(ns_i18n.t('troop_size'), 'army', content_json.army);
            }

            // 성벽 정보. 정찰등급 5
            if (scout_level >= 5) {
                // 성문 상태
                if (! content_json.yn_valley) {
                    ns_report.drawDescription(ns_i18n.t('gate_status'), (content_json.wall_open || content_json.yn_npc) ? ns_i18n.t('gate_open') : ns_i18n.t('gate_close'));
                }
                if (content_json.hero) {
                    ns_report.drawHeroList(ns_i18n.t('hero_information'), content_json.hero); // 영웅 정보
                }
            }
            break;
        case 'injury_army_trans': // 부상병 이송
            ns_report.setSummary(ns_text.convertReportTitle(report_summary, [ns_text.convertPositionName(content_json.battle_position_name), ns_text.convertPositionName(content_json.army_position_name)]));

            ns_report.drawBoxList(ns_i18n.t('transported_injury_army'), 'army', content_json.injury_army); // 이송된 부상병
            break;
        case 'return_finish_1': // 부대 복귀
        case 'return_finish_2':
        case 'return_finish_3':
        case 'return_finish_4':
        case 'return_finish_5':
        case 'return_finish_6':
        case 'return_finish_7':
        case 'return_finish_8':
            ns_report.setSummary(ns_text.convertReportTitle(report_summary, [ns_text.convertPositionName(content_json.from_position), ns_text.convertPositionName(content_json.to_position)]));

            if (content_json.hero) {
                ns_report.drawHeroList(ns_i18n.t('leading_hero'), content_json.hero); // 인솔 영웅
            }

            if (content_json.army) {
                ns_report.drawBoxList(ns_i18n.t('returning_troops'), 'army', content_json.army); // 복귀 병력
            }

            if (content_json.reso) {
                ns_report.drawBoxList(ns_i18n.t('resources_acquired'), 'resource', content_json.reso); // 가지고 온 자원
            }
            break;
        case 'reinforce_finish_1': // 지원 도착
        case 'reinforce_finish_2':
        case 'reinforce_finish_3':
        case 'reinforce_finish_4':
        case 'reinforce_finish_5':
        case 'ally_troop_arrival': // 동맹 지원 도착
            ns_report.setSummary(ns_text.convertReportTitle(report_summary, [ns_text.convertPositionName(_data.to_posi_name)]));

            if (content_json.recall) {
                ns_report.drawRecall(content_json.recall);
            }

            if (content_json.hero) {
                ns_report.drawHeroList(ns_i18n.t('leading_hero'), content_json.hero); // 인솔 영웅
            }

            if (content_json.army) {
                ns_report.drawBoxList(ns_i18n.t('support_number_of_troops'), 'army', content_json.army); // 지원군 병력
            }

            if (content_json.reso) {
                ns_report.drawBoxList(ns_i18n.t('resources_acquired'), 'resource', content_json.reso); // 가지고 온 자원
            }
            break;
        case 'trans_finish': // 수송 도착
            ns_report.setSummary(ns_text.convertReportTitle(report_summary, [ns_text.convertPositionName(_data.to_posi_name)]));

            if (content_json.recall) {
                ns_report.drawRecall(content_json.recall);
            }

            ns_report.setSummary(_data.summary);

            if (content_json.hero) {
                ns_report.drawHeroList(ns_i18n.t('leading_hero'), content_json.hero); // 인솔 영웅
            }

            if (content_json.army) {
                ns_report.drawBoxList(ns_i18n.t('transportation_troops'), 'army', content_json.army); // 수송 병력
            }

            if (content_json.reso) {
                ns_report.drawBoxList(ns_i18n.t('transport_resources'), 'resource', content_json.reso); // 수송 자원
            }
            break;
        case 'preva_finish': // 보급 도착
            ns_report.setSummary(ns_text.convertReportTitle(report_summary, [ns_text.convertPositionName(_data.to_posi_name)]));

            if (content_json.hero) {
                ns_report.drawHeroList(ns_i18n.t('leading_hero'), content_json.hero); // 인솔 영웅
            }

            if (content_json.army) {
                ns_report.drawBoxList(ns_i18n.t('supply_troops'), 'army', content_json.army); // 보급 병력
            }

            if (content_json.reso) {
                ns_report.drawBoxList(ns_i18n.t('supply_resources'), 'resource', content_json.reso); // 보급 자원
            }

            if (content_json.recall) {
                ns_report.drawRecall(content_json.recall);
            }
            break;
        case 'hero_skill_slot_expand': // 영웅 기술 슬롯 오픈
            summary_data = ns_text.convertReportSummary(_data.report_type, _data.summary);
            ns_report.setSummary(ns_text.convertReportTitle(report_summary, summary_data));

            if (content_json.hero) {
                ns_report.drawHeroList(ns_i18n.t('hero_information'), content_json.hero);
            }

            ns_report.drawDescription(ns_i18n.t('details_information'), `${ns_i18n.t('previous_open_slot')} : ` + content_json['prev_slot_cnt'] + `<br />${ns_i18n.t('current_open_slot')} : ` + content_json['curr_slot_cnt']);
            break;
        case 'hero_bid_success': // 영웅 입찰
        case 'hero_bid_fail': // 영웅 입찰
            summary_data = ns_text.convertReportSummary(_data.report_type, _data.summary);
            ns_report.setSummary(ns_text.convertReportTitle(report_summary, summary_data));

            if (content_json.hero) {
                ns_report.drawHeroList(ns_i18n.t('bid_information'), { 0: { pk: content_json.hero.hero_pk, m_pk: content_json.hero.m_pk }});
            }
            ns_report.drawDescription(ns_i18n.t('details_information'), ((content_json.success === true) ? ns_i18n.t('success_bid') : ns_i18n.t('failure_bid')) + `<br />${ns_i18n.t('bidding_gold')} : ` + ns_util.numberFormat(content_json['bid_gold']) + `<br />${ns_i18n.t('successful_bid_price')} : ` + ns_util.numberFormat(content_json['sold_gold']));
            break;
        case 'hero_enchant_suc': // 영웅 강화 성공
        case 'hero_enchant_fal': // 영웅 강화 실패
            summary_data = ns_text.convertReportSummary(_data.report_type, _data.summary);
            ns_report.setSummary(ns_text.convertReportTitle(report_summary, summary_data));

            let enchant_result = (_data.report_type === 'hero_enchant_suc') ? ns_i18n.t('success_enhance') : ns_i18n.t('failure_enhance'); // '강화 성공' : '강화 실패';
            let plus_stat_text = ns_i18n.t('no_change_hero_status'); // 능력치 변화 없음
            let enchant_count = (! content_json['enchant_count']) ? 0 : content_json['enchant_count'];

            if (_data.report_type === 'hero_enchant_suc' && content_json?.plusstat) {
                let stat_desc_arr = {
                    'leadership' : code_set.hero_enchant.leadership, // 통솔력
                    'mil_force' : code_set.hero_enchant.mil_force, // 무력
                    'intellect' : code_set.hero_enchant.intellect, // 지력
                    'politics' : code_set.hero_enchant.politics, // 정치력
                    'charm' : code_set.hero_enchant.charm // 매력
                }
                for (let [k, d] of content_json.plusstat) {
                    if (! ns_util.isNumeric(d)) {
                        let t = ns_util.toInteger(d);
                        if (t > 0) {
                            plus_stat_text += ns_i18n.t('hero_enchant_add', [stat_desc_arr[k], t]); // {{1}} +{{2}} 증가
                        }
                    }
                }
            }

            if (ns_cs.m.hero[content_json['m_hero_pk']]) {
                ns_report.drawHeroList(ns_i18n.t('hero_information'), { 0: { pk: null, m_pk: content_json['m_hero_pk'] } });
                ns_report.drawDescription(ns_i18n.t('details_information'), `${enchant_result}<br />${ns_i18n.t('enhance_result')} : ${plus_stat_text}<br />${ns_i18n.t('number_of_enhance')} : ${enchant_count}`);
            }
            break;
        case 'enemy_march': // 적 부대 습격
            summary_data = ns_text.convertReportSummary(_data.report_type, _data.summary);
            ns_report.setSummary(ns_text.convertReportTitle(report_summary, summary_data));

            ns_report.drawDescription(ns_i18n.t('total_troop_size'), ns_i18n.t('scout_success_scale', [content_json['army_scale']])); // 명 규모

            if (content_json.army) {
                ns_report.drawBoxList(ns_i18n.t('troop_size'), 'army', content_json.army);
            }

            if (content_json.hero) {
                ns_report.drawHeroList(ns_i18n.t('enemy_hero'), content_json.hero);
            }
            break;
        case 'shipping_finish': // 구매한 물품
            summary_data = ns_text.convertReportSummary(_data.report_type, _data.summary);
            ns_report.setSummary(ns_text.convertReportTitle(report_summary, summary_data));

            let resource_data = {};
            resource_data[content_json.reso_type] = content_json.reso_amount;
            ns_report.drawBoxList(ns_i18n.t('delivered_goods'), 'resource', resource_data); // 배송된 물품
            break;
        case 'shipping_sale': // 판매한 물품
            summary_data = ns_text.convertReportSummary(_data.report_type, _data.summary);
            ns_report.setSummary(ns_text.convertReportTitle(report_summary, summary_data));

            let resource_data2 = {};
            resource_data2[content_json.reso_type] = content_json.reso_amount;

            ns_report.drawBoxList(ns_i18n.t('sell_goods'), 'resource', resource_data2); // 판매된 물품

            resource_data2 = {};
            resource_data2['gold'] = ns_util.math(content_json.gold_amount).minus(ns_util.math(content_json.gold_amount).mul(0.1).number).number; // 수수료 제외
            ns_report.drawBoxList(ns_i18n.t('amount_deposited'), 'resource', resource_data2); // 입금된 금액
            break;
        case 'army_loss': // 반란
            summary_data = ns_text.convertReportSummary(_data.report_type, _data.summary);
            ns_report.setSummary(ns_text.convertReportTitle(report_summary, summary_data));

            if (content_json.loss_army_info) {
                let loss_army = {}
                for (let [k, d] of Object.entries(content_json.loss_army_info)) {
                    loss_army[k] = { amount: content_json.army_before_loss[k], remain: ns_util.math(content_json.army_before_loss[k]).minus(d).number };
                }
                ns_report.drawBoxList(ns_i18n.t('dead_army'), 'army', loss_army);
            }
            break;
        case 'hero_strike': // 태업
            summary_data = ns_text.convertReportSummary(_data.report_type, _data.summary);
            ns_report.setSummary(ns_text.convertReportTitle(report_summary, summary_data));

            if (content_json.strike_heroes) {
                ns_report.drawHeroList(ns_i18n.t('strike_hero'), content_json.strike_heroes);
            }
            break;
        case 'ally_troop_recall': // 동맹 지원 후 복귀
            ns_report.setSummary(_data.to_posi_name + _data.summary);

            if (content_json.hero) {
                ns_report.drawHeroList(ns_i18n.t('leading_hero'), content_json.hero);
            }

            if (content_json.army) {
                ns_report.drawBoxList(ns_i18n.t('returning_troops'), 'army', content_json.army);
            }
            break;
        case 'battle_attack_victory': // 전투 공격
        case 'battle_defence_victory': // 전투 방어
        case 'battle_attack_defeat': // 전투 공격
        case 'battle_defence_defeat': // 전투 방어
            // 전투 보고서인 경우 전투 위치를 표기해주기 위해
            let _position_name = (['battle_attack_victory', 'battle_attack_defeat'].includes(_data.report_type)) ? _data.to_posi_name : _data.from_posi_name;
            _position_name = ns_i18n.t('location') + ' : ' + ns_text.convertPositionName(_position_name, false, true, true);
            dialog.cont_obj.content_read_wrap.find('.read_from_name').html(ns_util.positionLink(_position_name));

            ns_report.battleAppend(_data.report_type, _data);
            break;
        default:
            ns_report.setSummary(ns_i18n.t('none'));
            break;
    }
}

ns_dialog.dialogs.report.lordLetterWrite = function(_lord_pk, _lord_name)
{
    let dialog = ns_dialog.dialogs.report;
    if (dialog.visible === false) {
        ns_dialog.open('report');
    }
    ns_button.buttons.report_tab_letter.mouseUp()
    ns_button.buttons.report_tab3_write.mouseUp();

    dialog.cont_obj.letter_from.html('&nbsp;');
    dialog.cont_obj.letter_title.value('');
    dialog.cont_obj.letter_content.value('');

    dialog.findToAdd(_lord_pk, _lord_name);
}

ns_dialog.dialogs.report.drawReportUnread = function()
{
    let dialog = ns_dialog.dialogs.report;

    // 서브 탭
    if (dialog.current_tab === 'report') {
        for (let [k, d] of Object.entries(ns_cs.d.lord.unread_report_desc)) {
            if (ns_button.buttons[`report_tab2_${k}`]) {
                if (ns_util.math(d).gt(0)) {
                    ns_button.buttons[`report_tab2_${k}`].obj.element.dataset.count = (d > 99) ? '99' : String(d);
                    ns_button.buttons[`report_tab2_${k}`].obj.addCss('tab_flag_count');
                } else {
                    ns_button.buttons[`report_tab2_${k}`].obj.removeCss('tab_flag_count');
                }
            }
        }
    } else if (dialog.current_tab === 'letter') {
        for (let [k, d] of Object.entries(ns_cs.d.lord.unread_letter_desc)) {
            let _k = (k === 'S') ? 'system' : (k === 'N') ? 'receive' : null;
            if (_k && ns_button.buttons[`report_tab3_${_k}`]) {
                if (ns_util.math(d).gt(0)) {
                    ns_button.buttons[`report_tab3_${_k}`].obj.element.dataset.count = (d > 99) ? '99' : String(d);
                    ns_button.buttons[`report_tab3_${_k}`].obj.addCss('tab_flag_count');
                } else {
                    ns_button.buttons[`report_tab3_${_k}`].obj.removeCss('tab_flag_count');
                }
            }
        }
    }

    // 상단 탭
    if (ns_util.math(ns_cs.d.lord.unread_report_cnt.v).gt(0)) {
        ns_button.buttons.report_tab_report.obj.element.dataset.count = (ns_cs.d.lord.unread_report_cnt.v > 99) ? 99 : ns_cs.d.lord.unread_report_cnt.v;
        ns_button.buttons.report_tab_report.obj.addCss('tab_flag_count');
    } else {
        delete ns_button.buttons.report_tab_report.obj.element.dataset.count;
        ns_button.buttons.report_tab_report.obj.removeCss('tab_flag_count');
    }

    if (ns_util.math(ns_cs.d.lord.unread_letter_cnt.v).gt(0)) {
        ns_button.buttons.report_tab_letter.obj.element.dataset.count = (ns_cs.d.lord.unread_letter_cnt.v > 99) ? 99 : ns_cs.d.lord.unread_letter_cnt.v;
        ns_button.buttons.report_tab_letter.obj.addCss('tab_flag_count');
    } else {
        delete ns_button.buttons.report_tab_letter.obj.element.dataset.count;
        ns_button.buttons.report_tab_letter.obj.removeCss('tab_flag_count');
    }

    // 메인 체크
    dialog.drawMainUnread();
}

ns_dialog.dialogs.report.drawMainUnread = function()
{
    let report_count = (! ns_cs.d.lord?.unread_report_cnt) ? 0 : ns_cs.d.lord.unread_report_cnt.v;
    let letter_count = (! ns_cs.d.lord?.unread_letter_cnt) ? 0 : ns_cs.d.lord.unread_letter_cnt.v;

    // main_flag_new
    let count = ns_util.math(report_count).plus(letter_count).number;
    if (ns_util.math(count).gt(99)) {
        count = 99;
    }
    if (ns_util.math(count).gt(0)) {
        ns_button.buttons.main_report.obj.element.dataset.count = String(count);
        ns_button.buttons.main_report.obj.addCss('main_flag_new');
    } else {
        delete ns_button.buttons.main_report.obj.element.dataset.count;
        ns_button.buttons.main_report.obj.removeCss('main_flag_new');
    }
};

/* ************************************************** */

ns_button.buttons.report_close = new nsButtonSet('report_close', 'button_back', 'report', { base_class: ns_button.buttons.common_close });
ns_button.buttons.report_sub_close = new nsButtonSet('report_sub_close', 'button_full', 'report', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.report_close_all = new nsButtonSet('report_close_all', 'button_close_all', 'report', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.report_tab_report = new nsButtonSet('report_tab_report', 'button_tab', 'report', { toggle_group: 'report_tab' });
ns_button.buttons.report_tab_report.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    let tab = this.tag_id.split('_').pop();
    dialog.drawCol();

    dialog.cont_obj[`tab_${dialog.current_tab}_wrap`].hide();
    dialog.cont_obj[`tab_${tab}_wrap`].show();

    dialog.current_page = 1;
    dialog.current_tab = tab;
    if (tab === 'report') {
        ns_button.buttons.report_tab2_scout.mouseUp();
    } else {
        ns_button.buttons.report_tab3_receive.mouseUp();
    }

    ns_button.buttons.report_select_all.unsetClicked();
}
ns_button.buttons.report_tab_letter = new nsButtonSet('report_tab_letter', 'button_tab', 'report', { base_class:ns_button.buttons.report_tab_report, toggle_group:'report_tab'});

ns_button.buttons.report_tab2_scout = new nsButtonSet('report_tab2_scout', 'button_tab_sub', 'report', {toggle_group:'report_tab2'});
ns_button.buttons.report_tab2_scout.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.sub_tab = this.tag_id.split('_').pop();
    let ret = dialog.drawCol();
    if (ret === false) {
        return;
    }

    dialog.current_page = 1;
    dialog.drawList();

    ns_button.buttons.report_select_all.unsetClicked();
}

ns_button.buttons.report_tab2_battle = new nsButtonSet('report_tab2_battle', 'button_tab_sub', 'report', {base_class:ns_button.buttons.report_tab2_scout, toggle_group:'report_tab2'});
ns_button.buttons.report_tab2_recall = new nsButtonSet('report_tab2_recall', 'button_tab_sub', 'report', {base_class:ns_button.buttons.report_tab2_scout, toggle_group:'report_tab2'});
ns_button.buttons.report_tab2_move = new nsButtonSet('report_tab2_move', 'button_tab_sub', 'report', {base_class:ns_button.buttons.report_tab2_scout, toggle_group:'report_tab2'});
ns_button.buttons.report_tab2_misc = new nsButtonSet('report_tab2_misc', 'button_tab_sub', 'report', {base_class:ns_button.buttons.report_tab2_scout, toggle_group:'report_tab2'});

ns_button.buttons.report_tab3_receive = new nsButtonSet('report_tab3_receive', 'button_tab_sub', 'report', {base_class:ns_button.buttons.report_tab2_scout, toggle_group:'report_tab3'});
ns_button.buttons.report_tab3_system = new nsButtonSet('report_tab3_system', 'button_tab_sub', 'report', {base_class:ns_button.buttons.report_tab2_scout, toggle_group:'report_tab3'});
ns_button.buttons.report_tab3_write = new nsButtonSet('report_tab3_write', 'button_tab_sub', 'report', {base_class:ns_button.buttons.report_tab2_scout, toggle_group:'report_tab3'});
ns_button.buttons.report_tab3_send = new nsButtonSet('report_tab3_send', 'button_tab_sub', 'report', {base_class:ns_button.buttons.report_tab2_scout, toggle_group:'report_tab3'});

/*ns_button.buttons.report_prev = new nsButtonSet('report_prev', 'button_page_prev', 'report');
ns_button.buttons.report_prev.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;
    dialog.current_page--;
    if (dialog.current_page < 1) {
        dialog.current_page = dialog.total_page;
    }
    dialog.drawList();
    ns_button.buttons.report_select_all.unsetClicked();
}

ns_button.buttons.report_next = new nsButtonSet('report_next', 'button_page_next', 'report');
ns_button.buttons.report_next.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;
    dialog.current_page++;
    if (dialog.current_page > dialog.total_page) {
        dialog.current_page = 1;
    }
    dialog.drawList();
    ns_button.buttons.report_select_all.unsetClicked();
}*/


ns_button.buttons.letter_receiver_add = new nsButtonSet('letter_receiver_add', 'button_middle_2', 'report');
ns_button.buttons.letter_receiver_add.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;

    if (dialog.receiver_list) {
        let count = Object.keys(dialog.receiver_list).length;
        if (count > 5 && ! dialog.alliance_troop_request) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_max_user_error')); // 동시에 5명을 초과해서 발송할 수 없습니다.
            return;
        }
    }

    ns_dialog.open('letter_add_receiver');
}

ns_button.buttons.delete_receiver_lord = new nsButtonSet('delete_receiver_lord', 'button_cancel', 'report');
ns_button.buttons.delete_receiver_lord.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;
    let lord_pk = _e.target.id.split('_').pop();
    delete dialog.receiver_list[lord_pk];
    dialog.cont_obj.letter_from.find('.letter_receiver_lord_' + lord_pk).remove();
}

ns_button.buttons.send_letter = new nsButtonSet('send_letter', 'button_default', 'report');
ns_button.buttons.send_letter.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;

    if (! dialog.receiver_list) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_add_receiver_please')); // 수신인을 추가해주세요.
        return;
    } else {
        if (Object.keys(dialog.receiver_list).some(k => ns_util.math(k).eq(ns_cs.d.lord.lord_pk.v))) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_self_error')); // 자기 자신에게 발송할 수 없습니다.
            return;
        }
        let count = Object.keys(dialog.receiver_list).length;
        if (count > 5 && ! dialog.alliance_troop_request) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_max_user_error')); // 동시에 5명을 초과해서 발송할 수 없습니다.
            return;
        } else if (count < 1) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_add_receiver_please')); // 수신인을 추가해주세요.
            return;
        }
    }

    let letter_title = dialog.cont_obj.letter_title.value();
    let letter_content = dialog.cont_obj.letter_content.value();
    if (letter_title.replace(/\s/gi, '').length < 1) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_empty_title_error')); // 제목을 입력해주십시오.
        return;
    } else if (letter_title.length > 25) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_max_title_error', [letter_title.length])); // 제목은 한글 기준 25자를 초과할 수 없습니다.<br/>사용한 글자수 :
        return;
    }

    if (letter_content.replace(/\s/gi, '').length < 1) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_empty_content_error')); // 내용을 입력해주십시오.
        return;
    } else if (letter_content.length > 500) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_max_content_error', [letter_content.length])); // 내용은 한글 기준 500자를 초과할 수 없습니다.<br/>사용한 글자수 :
        return;
    }

    dialog.sendLetter(letter_title, letter_content, Object.keys(dialog.receiver_list));
}

ns_button.buttons.report_move_prev = new nsButtonSet('report_move_prev', 'button_middle_2', 'report');
ns_button.buttons.report_move_prev.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;
    dialog.drawCol();
    dialog.drawList();
    ns_button.buttons.report_select_all.unsetClicked();
}

ns_button.buttons.report_forward = new nsButtonSet('report_forward', 'button_middle_2', 'report');
ns_button.buttons.report_forward.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;
    let lett_pk = dialog.read_pk;

    ns_button.buttons.report_tab3_write.mouseUp();

    dialog.cont_obj.letter_from.text('');
    dialog.cont_obj.letter_title.value('Fw : ' + dialog.list[lett_pk].title);
    dialog.cont_obj.letter_content.value('\n\n-------------------------------------\n' + dialog.list[lett_pk].content.replace(/<br\s*\/>/gi, '\n'));
}

ns_button.buttons.report_reply = new nsButtonSet('report_reply', 'button_middle_2', 'report');
ns_button.buttons.report_reply.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;
    let lett_pk = dialog.read_pk;

    ns_button.buttons.report_tab3_write.mouseUp();

    dialog.cont_obj.letter_from.html('&nbsp;');
    dialog.cont_obj.letter_title.value('Re : ' + dialog.list[lett_pk].title);
    dialog.cont_obj.letter_content.value('\n\n-------------------------------------\n' + dialog.list[lett_pk].content.replace(/<br\s*\/>/gi, '\n'));

    dialog.findToAdd(dialog.list[lett_pk].from_lord_pk, dialog.list[lett_pk].from_lord_name);
}

ns_button.buttons.report_delete_one = new nsButtonSet('report_delete_one', 'button_middle_2', 'report');
ns_button.buttons.report_delete_one.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;
    dialog.deleteReport([dialog.read_pk]);
    ns_button.buttons.report_select_all.unsetClicked();
}

//ns_button.buttons.report_select_all = new nsButtonSet('report_select_all', 'button_middle_2', 'report');
ns_button.buttons.report_select_all = new nsButtonSet('report_select_all', 'button_checkbox', 'report');
ns_button.buttons.report_select_all.mouseUp = function()
{
    let dialog = ns_dialog.dialogs.report;

    if ( this.clicked == false )
    {
        for (let _button of dialog.buttons) {
            if (_button.tag_id.search('_check_') !== -1) {
                _button.setClicked();
            }
        }
    }
    else{
        for (let _button of dialog.buttons) {
            if (_button.tag_id.search('_check_') !== -1) {
                _button.unsetClicked();
            }
        }
    }

    this.toggleClicked();
}

ns_button.buttons.report_set_read = new nsButtonSet('report_set_read', 'button_middle_2', 'report');
ns_button.buttons.report_set_read.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;

    let checked_items = [];
    for (let _button of dialog.buttons) {
        if (_button.clicked === true) {
            checked_items.push(_button.tag_id.split('_').pop());
        }
    }
    if (checked_items.length < 1) {
        return;
    }

    let action = '';
    let post_data = {};
    if (dialog.current_tab === 'report') {
        action = 'setRead';
        post_data['repo_pk_list'] = checked_items.join(',');
    } else if (dialog.current_tab === 'letter') {
        action = 'readCheck';
        post_data['lett_pk_list'] = checked_items.join(',');
    }
    if (action !== '') {
        ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_report_read_confirm'), // 선택한 항목을 읽음 처리합니다.<br />계속 진행하시겠습니까?
            okFunc: () =>
            {
                ns_xhr.post(`/api/${dialog.current_tab}/${action}`, post_data, function(_data, _status)
                {
                    if(! ns_xhr.returnCheck(_data)) {
                        return;
                    }
                    _data = _data['ns_xhr_return']['add_data'];

                    // 기존에 보고 있던 탭을 다시 그려줘야됨
                    dialog.drawList();
                    ns_button.buttons.report_select_all.unsetClicked();
                });
            }
        });
    }
}

ns_button.buttons.report_remove_list = new nsButtonSet('report_remove_list', 'button_middle_2', 'report');
ns_button.buttons.report_remove_list.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;

    let checked_items = [];
    for (let _button of dialog.buttons) {
        if (_button.clicked === true) {
            checked_items.push(_button.tag_id.split('_').pop());
        }
    }
    if (checked_items.length < 1) {
        return;
    }

    dialog.deleteReport(checked_items);
}

ns_button.buttons.hero_battle_result_view = new nsButtonSet('hero_battle_result_view', 'button_empty', 'report');
ns_button.buttons.hero_battle_result_view.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;

    ns_dialog.setDataOpen('message', 'Content is being prepared.');
    // ns_dialog.setDataOpen('hero_battle_result_viewer', {});
}

ns_button.buttons.battle_result_view = new nsButtonSet('battle_result_view', 'button_empty', 'report');
ns_button.buttons.battle_result_view.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;

    ns_dialog.setDataOpen('message', 'Content is being prepared.');
    // ns_dialog.setDataOpen('battle_result_viewer', {data:dialog.report_data});
}

ns_button.buttons.toggle_hero_skill_view = new nsButtonSet('toggle_hero_skill_view', 'button_empty', 'report');
ns_button.buttons.toggle_hero_skill_view.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;
    let hero_skill_list = dialog.obj.find('.cont_hero_skill_list');
    let hero_skill_wrap = dialog.obj.find('.cont_report_hero_skill_wrap');

    hero_skill_list.toggle();
    hero_skill_wrap.toggle();

    dialog.contentRefresh(); // 세로 사이즈가 변경 되므로
};

// 서신 보상 수령
ns_button.buttons.get_reward = new nsButtonSet('get_reward', 'button_middle_2', 'report');
ns_button.buttons.get_reward.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.report;

    let post_data = {};
    if (dialog.current_tab === 'report') {
        post_data['repo_pk'] = dialog.read_pk;
    } else if (dialog.current_tab === 'letter') {
        post_data['lett_pk'] = dialog.read_pk;
    }

    ns_xhr.post(`/api/${dialog.current_tab}/getReward`, post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_dialog.setDataOpen('message', _data.message);
        ns_button.buttons.get_reward.setDisable();
    }, { useProgress: true });
};

/*******************************************************************/
ns_dialog.dialogs.report_battle_detail = new nsDialogSet('report_battle_detail', 'dialog_full', 'size-full');

ns_dialog.dialogs.report_battle_detail.cacheContents = function ()
{
    this.cont_obj.report_detail = new nsObject('.report_detail', this.obj);
}

ns_dialog.dialogs.report_battle_detail.draw = function ()
{
    let _data = this.data;

    this.cont_obj.report_detail.empty();
    let ns_report = new nsReport(this.cont_obj.report_detail);

    let c = _data['content_json'];
    let scene = {},
        battle_info = {};
    if (_data?.['content_battle_json']) {
        battle_info = _data['content_battle_json']['battle_info'];
        scene = _data['content_battle_json']['scene'];
    }
    let resource_types = ['gold', 'food', 'horse', 'lumber', 'iron'];
    let unit_types = ['trap', 'abatis', 'tower', 'worker', 'infantry', 'armed_infantry', 'pikeman', 'spearman', 'battering_ram', 'horseman', 'armed_horseman', 'archer', 'bowman', 'adv_catapult', 'scout', 'transporter', 'catapult', 'wall'];
    let hero_types = ['captain', 'director', 'staff'];
    let battle_types = ['att', 'def'];

    let attacker_result = false,
        defender_result = false,
        is_defeated = false;

    if (c['outcome']['cunit_amount'] && ns_util.math(c['outcome']['cunit_amount']['att']).gte(0) && ns_util.math(c['outcome']['cunit_amount']['def']).gte(0)) {
        let att_cunit_amount = ns_util.toInteger(c['outcome']['cunit_amount']['att']);
        let def_cunit_amount = ns_util.toInteger(c['outcome']['cunit_amount']['def']);

        // 환산 병력이 양측 모두 0보다 클때만 한다. - 어느 쪽이 승자건 상관 없음
        if (_data['report_type'] === 'battle_attack' && ns_util.math(att_cunit_amount).mul(100).lte(def_cunit_amount)) {
            is_defeated = true;
        }
        if (_data['report_type'] === 'battle_defence' && ns_util.math(def_cunit_amount).mul(100).lte(att_cunit_amount)) {
            is_defeated = true;
        }

        // summary 및 cont_outcome_final_result 생성
        let summary = '', hero_result = '', unit_result = '';

        if (c['battle_info']['def_type'] === 'suppress') { // 황건적 토벌
            if (c['outcome']['winner'] === 'att') {
                summary += ns_i18n.t('msg_yellow_turban_suppression_victory'); // 황건적 토벌에 성공하였습니다. 불모지에 주둔 중이던 황건적은 모두 괴멸되었습니다.
            } else {
                summary += ns_i18n.t('msg_yellow_turban_suppression_defeat'); // 황건적 토벌에 실패하였습니다.
            }
        } else if (c['battle_info']['def_type'] === 'valley') { // 자원지
            if (['battle_attack_victory', 'battle_attack_defeat'].includes(_data['report_type'])) {
                if (c['outcome']['winner'] === 'att') {
                    if (c['outcome']['acquiredpwnership'] === true) {
                        summary += ns_i18n.t('msg_enemy_valley_attack_victory_deployed'); // 적 자원지 공격에 성공하였습니다. 공격 병력은 자원지 및 평지를 점령하고 주둔을 시작합니다.
                    } else {
                        if (c['outcome']['valley_cnt_not']) {
                            summary += ns_i18n.t('msg_enemy_valley_attack_victory_return'); // <br />보유 가능 자원지 갯수가  부족하여 자원지 점령에는 실패하였습니다. 공격 병력은 소속 영지로 회군 중입니다.
                        }
                    }
                } else {
                    summary += ns_i18n.t('msg_enemy_valley_attack_defeat'); // 적 자원지 공격에 실패하였습니다.
                }
            } else { // defence
                if (c['outcome']['winner'] === 'def') {
                    if (c['outcome']['lossownership']) {
                        if (c['type'] === 'P') {
                            summary += '요충지 방어에 성공하였습니다. 주장이 존재하지 않아 부대는 회군하고 요충지는 황건적이 점령합니다.<br />방어 병력은 소속 영지로 회군 중입니다.'; // TODO 요충지는 차후 추가
                        } else {
                            summary += ns_i18n.t('msg_valley_defense_victory_return'); // 자원지 방어에 성공하였습니다. 주장이 존재하지 않아 부대는 회군하고 자원지는 황건적이 점령합니다.<br />방어 병력은 소속 영지로 회군 중입니다.
                        }
                    } else {
                        if (c['type'] === 'P') {
                            summary += '요충지 방어에 성공하였습니다. 방어 병력은 요충지에 주둔 중 입니다.'; // TODO 요충지는 차후 추가
                        } else {
                            summary += ns_i18n.t('msg_valley_defense_victory_deployed'); // 자원지 방어에 성공하였습니다. 방어 병력은 자원지에 주둔 중 입니다.
                        }
                    }
                } else {
                    if (c['type'] === 'P') {
                        summary += '요충지 방어에 패배하여 요충지를 적에게 빼았겼습니다.<br />방어 병력은 소속 영지로 회군 중입니다.'; // TODO 요충지는 차후 추가
                    } else {
                        summary += ns_i18n.t('msg_valley_defense_defeat'); // 자원지 방어에 패배하여 자원지를 적에게 빼았겼습니다.<br />방어 병력은 소속 영지로 회군 중입니다.
                    }
                }
            }
        } else { // 영지
            if (['battle_attack_victory', 'battle_attack_defeat'].includes(_data['report_type'])) {
                if (c['outcome']['winner'] === 'att') {
                    if (c['outcome']['plunder']) {
                        // 적 영지({{1}}) 공격에
                        // code_set.wall_desc[c['battle_info']['def_wall']]

                        if (c['outcome']['hero_skill_special']) {
                            if (c['outcome']['hero_skill_special']['loyalty']) {
                                summary += ns_i18n.t('report_battle_loyalty_1_summary', [c['plunder']['loyalty_final']]); // "민심방어" 기술이 발동되어 민심(현재 <strong>{{1}}</strong>)이 감소되지 않았습니다.
                            } else {
                                summary += ns_i18n.t('report_battle_loyalty_3_summary', [c['plunder']['loyalty_decrease'], c['plunder']['loyalty_final']]); // ' 적 영지의 민심이 <strong>{{1}}</span> 만큼 감소 (현재 <strong>{{2}}</strong>) 되었습니다.';
                                /*if (c['outcome'].use_item) {
                                    summary += system_text.battle_hero_skill_special6; // '<br />(황제의 조서 효과로 50%추가 하락)'
                                }*/
                            }
                        } else {
                            summary += ns_i18n.t('report_battle_loyalty_3_summary', [c['plunder']['loyalty_decrease'], c['plunder']['loyalty_final']]);
                            /*if (c['outcome'].use_item) {
                                summary += system_text.battle_hero_skill_special6; // '<br />(황제의 조서 효과로 50%추가 하락)'
                            }*/
                        }

                        /*if (ns_util.math(c['plunder']['loyalty_decrease']).lte(1) && ns_util.math(c['plunder']['loyalty_final']).lte(1)) {
                            summary += system_text.battle_emperor_report; // '<br />점령은 부대가 황제의 조서를 소지한 후 민심이 1인 영지를 공격했을때 점령할 수 있습니다.'
                        }*/

                        if (c['outcome']['hero_skill_special']?.['plunder']) {
                            summary += ns_i18n.t('report_battle_loyalty_4_summary'); // "약탈감소" 기술이 발동되어 자원약탈이 이루어지지 않았습니다.
                        }

                        summary += `<br />${ns_i18n.t('report_return_troops_summary')}`; // 공격 병력은 소속 영지로 회군 중입니다.
                    } else if (c['outcome']?.['occupation']) {
                        summary += '점령선포 상태에서 황제의 조서 효과로 적 영지 점령에 성공하였습니다.<br />공격 병력은 해당 영지에 주둔합니다.'; // TODO 점령 선포 제거됨.
                    }
                } else {
                    summary += ns_i18n.t('msg_enemy_territory_attack_defeat', [ns_text.convertPositionName(_data.to_posi_name, false, true, true)]); // 적 영지({{1}}) 공격에 실패하였습니다.<br />공격 병력은 소속 영지로 회군 중입니다.

                    /*if (c['outcome']['loss_item']) {
                        summary += system_text.battle_loss_item; // ' 이 과정에서 공격 병력이 보유한 황제의 조서가 손실 되었습니다.'
                    }*/

                    // unit_result += '<br />' + system_text.battle_attack_loss_item;
                }
            } else { // defence
                if (c['outcome']['winner'] === 'def') {
                    summary += ns_i18n.t('report_defense_battle_victory', code_set.wall_desc[c['battle_info']['def_wall']]); //   system_text.battle_success; // 성공하였습니다.

                    attacker_result = ns_i18n.t('defeat');
                    defender_result = ns_i18n.t('victory');
                } else {
                    summary += ns_i18n.t('report_defense_battle_defeat', code_set.wall_desc[c['battle_info']['def_wall']]);

                    attacker_result = ns_i18n.t('victory');
                    defender_result = ns_i18n.t('defeat');

                    if (c['outcome']['plunder']) {
                        if (c['outcome']['hero_skill_special']) {
                            if (c['outcome']['hero_skill_special']['loyalty']) {
                                summary += `<br />${ns_i18n.t('report_battle_loyalty_1_summary', [c['plunder']['loyalty_final']])}`; // "민심방어" 기술이 발동되어 민심(현재 <strong>{{1}}</strong>)이 감소되지 않았습니다.
                            } else {
                                summary += `<br />${ns_i18n.t('report_battle_loyalty_2_summary', [c['plunder']['loyalty_decrease'], c['plunder']['loyalty_final']])}`; //
                            }

                            if (c['outcome']['hero_skill_special']['plunder']) {
                                summary += ns_i18n.t('report_battle_loyalty_4_summary');
                            }
                        } else {
                            summary += ns_i18n.t('report_battle_loyalty_2_summary', [c['plunder']['loyalty_decrease'], c['plunder']['loyalty_final']]);
                        }
                    }
                }
            }
        }

        if (is_defeated === true) {
            summary += `<br />${ns_i18n.t('report_battle_is_defeated_summary')}`; // <br />압도적인 병력 차로 인해 적의 상세한 병력은 파악 할 수 없었습니다.
        }

        ns_report.setSummary(summary);

        if (is_defeated) {
            return false;
        }

        if (!battle_info) {
            return false;
        }

        if (c['hero_battle']) {
            if (c['hero_battle']['win'] === 'att') {
                hero_result = ns_i18n.t('attack_victory'); // 공격측 승리
            } else if (c['hero_battle']['win'] === 'def') {
                hero_result = ns_i18n.t('defense_victory'); // 방어측 승리
            } else {
                hero_result = ns_i18n.t('battle_draw'); // 무승부
            }

            let result_cnt = 0;
            if (c['hero_battle']['battle_turn']) {
                result_cnt = c['hero_battle']['battle_turn'];
            }

            if (!c['hero_battle']['win'] && !c['hero_battle']['battle_turn']) {
                result_cnt = '-';
            }

            hero_result += ' (' + ns_i18n.t('duel_turn', [result_cnt]) + ')';

            // 추가 경험치 텍스트
            if ((['battle_attack_victory', 'battle_attack_defeat'].includes(_data['report_type']) && c['hero_battle']['win'] === 'att') || (['battle_defence_victory', 'battle_defence_defeat'].includes(_data['report_type']) && c['hero_battle']['win'] === 'def')) {
                hero_result += `<br />${ns_i18n.t('report_hero_duel_exp_summary')}`; // 일기토 참여 영웅 1 기술 경험치 획득
            } // TODO 일단 여기까지

            // 일기토에 의한 사기 변동
            if (c['spirit_info']) {
                if (c['spirit_info']['prev_att'] !== c['spirit_info']['att']) {
                    let att_spirit = ns_util.math(c['spirit_info']['att']).minus(c['spirit_info']['prev_att']).number;
                    if (att_spirit > 0) {
                        hero_result += '<br />' + ns_i18n.t('report_attack_spirit_increase', [att_spirit]);
                    } else {
                        hero_result += '<br />' + ns_i18n.t('report_attack_spirit_decrease', [ns_util.math(att_spirit).mul(-1).number]);
                    }
                }

                if (c['spirit_info']['prev_def'] !== c['spirit_info']['def']) {
                    let def_spirit = ns_util.math(c['spirit_info']['def']).minus(c['spirit_info']['prev_def']).number;
                    if (def_spirit > 0) {
                        hero_result += '<br />' + ns_i18n.t('report_defense_spirit_increase', [def_spirit]);
                    } else {
                        hero_result += '<br />' + ns_i18n.t('report_defense_spirit_decrease', [ns_util.math(def_spirit).mul(-1).number]);
                    }
                }
            }

            ns_report.drawDescription(ns_i18n.t('hero_duel_result'), hero_result);

            // ns_button.buttons.hero_battle_result = new nsButtonSet('hero_battle_result', 'button_buil', 'report', {base_class:ns_button.buttons.hero_battle_result_view});
        }

        // 사용된 아이템
        // ns_report.drawDescription(ns_i18n.t('use_item'), (!c['use_item_pk']) ? ns_i18n.t('report_not_use_battle_item') : ns_i18n.t('report_use_battle_item', [ns_cs.m.item[c['use_item_pk']].title]));

        // 발동 기술
        let skill_use = false;
        if (c['outcome']['hero_skill']) {
            let hero_skill_wrap = new nsObject('#report_hero_skill_skeleton').clone();

            let att_hero_skill = c['outcome']['hero_skill']['att_hero_skill'];
            let def_hero_skill = c['outcome']['hero_skill']['def_hero_skill'];

            // 발동 전투 기술
            try {
                // 공격측
                for (let [k, d] of Object.entries(_data['att_battle_skill'])) {
                    if (d?.hero_type && d?.['skill_name']) {
                        hero_skill_wrap.find('.att_' + d.hero_type).text(d['skill_name'] + ' ');
                        skill_use = true;
                    }
                }
                for (let [k, d] of Object.entries(_data['def_battle_skill'])) {
                    if (d?.hero_type && d?.['skill_name']) {
                        hero_skill_wrap.find('.def_' + d.hero_type).text(d['skill_name'] + ' ');
                        skill_use = true;
                    }
                }

                // 발동 기술
                // 공격측
                if (att_hero_skill) {
                    for (let [k, d] of Object.entries(att_hero_skill)) {
                        if (d['m_hero_skil_pk']) {
                            for (let [k2, d2] of Object.entries(d['m_hero_skil_pk'])) {
                                if (d2) {
                                    let m = ns_cs.m.hero_skil[d2];
                                    if (m) {
                                        hero_skill_wrap.find('.att_' + k).text(m.title + ' Lv.' + m.rare + ' ');
                                        skill_use = true;
                                    }
                                }
                            }
                        }
                    }
                }

                // 방어측
                if (def_hero_skill) {
                    for (let [k, d] of Object.entries(def_hero_skill)) {
                        if (d['m_hero_skil_pk']) {
                            for (let [k2, d2] of Object.entries(d['m_hero_skil_pk'])) {
                                if (d2) {
                                    let m = ns_cs.m.hero_skil[d2];
                                    if (m) {
                                        hero_skill_wrap.find('.def_' + k).text(m.title + ' Lv.' + m.rare + ' ');
                                        skill_use = true;
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (e) {
                console.error(e);
            }

            if (skill_use) {
                ns_report.drawAppend(ns_i18n.t('activation_hero_skill'), hero_skill_wrap);
            }
        }

        if (!skill_use) {
            ns_report.drawDescription(ns_i18n.t('activation_hero_skill'), ns_i18n.t('report_not_active_hero_skill'));
        }

        unit_result = code_set.winner_desc[c['battle_info']['unit_battle_winner']] + ' ' + ns_i18n.t('victory') + ns_i18n.t('duel_turn', [(scene.length - 1)]) + unit_result;

        // 추가 경험치 텍스트
        if (['battle_attack_victory', 'battle_attack_defeat'].includes(_data['report_type']) && battle_info['exp_info'] && ns_util.math(battle_info['exp_info']['att']).gt(0)) {
            unit_result += `<br />${ns_i18n.t('report_battle_hero_exp_summary', [battle_info['exp_info']['att']])}`; // '<br />전투 참여 영웅 {{1}} 기술 경험치 획득';
        } else if (['battle_defence_victory', 'battle_defence_defeat'].includes(_data['report_type']) && battle_info['exp_info'] && ns_util.math(battle_info['exp_info']['def']).gt(0)) {
            unit_result += `<br />${ns_i18n.t('report_battle_hero_exp_summary', [battle_info['exp_info']['def']])}`;
        }
        ns_report.drawDescription('전투 결과', unit_result);


        // 전투 상세 정보 부분
        let battle_type = '';
        if (['battle_attack_victory', 'battle_attack_defeat'].includes(_data['report_type'])) {
            ns_report.drawDescription(ns_i18n.t('lord_information'), `${_data.from_lord_name} vs ${_data.to_lord_name}`);
            ns_report.drawDescription(ns_i18n.t('territory_information'), ns_util.positionLink(`${ns_text.convertPositionName(_data.from_posi_name)} vs ${ns_text.convertPositionName(_data.to_posi_name)}`));
        } else {
            ns_report.drawDescription(ns_i18n.t('lord_information'), `${_data.to_lord_name} vs ${_data.from_lord_name}`);
            ns_report.drawDescription(ns_i18n.t('territory_information'), ns_util.positionLink(`${ns_text.convertPositionName(_data.to_posi_name)} vs ${ns_text.convertPositionName(_data.from_posi_name)}`));
        }

        // 영웅 정보
        if (c['outcome_hero']) {
            let attack_heroes = {};
            let defense_heroes = {};
            for (let _type of hero_types) {
                if (c['outcome_hero']['att'][`${_type}_hero_pk`]) {
                    attack_heroes[_type] = { pk: c['outcome_hero']['att'][`${_type}_hero_pk`], m_pk: c['outcome_hero']['att'][`${_type}_m_hero_pk`] };
                }
                if (c['outcome_hero']['def'][`${_type}_hero_pk`]) {
                    defense_heroes[_type] = { pk: c['outcome_hero']['def'][`${_type}_hero_pk`], m_pk: c['outcome_hero']['def'][`${_type}_m_hero_pk`] };
                }
            }
            if (Object.keys(attack_heroes).length > 0) {
                ns_report.drawHeroList(ns_i18n.t('attack_hero'), attack_heroes); // 공격측 영웅
            }
            if (Object.keys(defense_heroes).length > 0) {
                ns_report.drawHeroList(ns_i18n.t('defense_hero'), defense_heroes); // 방어측 영웅
            }
        }

        // 영웅 능력 효과
        let att_hero_info = battle_info['att']['hero_info'];
        let def_hero_info = battle_info['def']['hero_info'];

        let attack_hero_info = '';
        if (att_hero_info['leadership']) {
            attack_hero_info += `${ns_i18n.t('lead')} : ${ns_util.numberFormat(att_hero_info['leadership'])} `;
        }
        if (att_hero_info['attack']) {
            attack_hero_info += `${ns_i18n.t('attack')} : ${att_hero_info['attack']}% `;
        }
        if (att_hero_info['defence']) {
            attack_hero_info += `${ns_i18n.t('defense')} : ${att_hero_info['defence']}% `;
        }

        if (attack_hero_info !== '') {
            ns_report.drawDescription(ns_i18n.t('attack_hero_effect'), attack_hero_info); // 공격측 영웅 능력 효과
        }

        let defense_hero_info = '';
        if (def_hero_info['leadership']) {
            defense_hero_info += `${ns_i18n.t('lead')} : ${ns_util.numberFormat(def_hero_info['leadership'])} `;
        }
        if (def_hero_info['attack']) {
            defense_hero_info += `${ns_i18n.t('attack')} : ${def_hero_info['attack']}% `;
        }
        if (def_hero_info['defence']) {
            defense_hero_info += `${ns_i18n.t('defense')} : ${def_hero_info['defence']}% `;
        }

        if (defense_hero_info !== '') {
            ns_report.drawDescription(ns_i18n.t('defense_hero_effect'), defense_hero_info); // 방어측 용사 능력 효과
        }

        // 병력 정보
        if (c['outcome_unit']) {
            // 공격측
            let attack_army = {}, attack_injury = {}, attack_fort = {};
            for (let [k, d] of Object.entries(c['outcome_unit']['att'])) {
                if (k !== 'injury_army' && k !== 'abandon_army') {
                    d['dead'] = ns_util.math(d?.amount ?? 0).minus(d?.injury ?? 0).minus(d?.remain ?? 0).number;
                    if (ns_cs.m.army[k]) {
                        attack_army[k] = d;
                    } else if (ns_cs.m.fort[k]) {
                        attack_fort[k] = d;
                    } else if (k === 'wall') {
                        attack_fort[k] = d;
                    }
                }

                if (k === 'abandon_army') {
                    if (d === true && battle_info.att.lord_info.lord_name === ns_cs.d.lord.lord_name.v) {
                        ns_report.drawDescription(ns_i18n.t('injury_army_accept'), ns_i18n.t('injury_army_accept_description')); // 의료원의 부상병 수용 공간이 부족하여 부상병을 모두 수용할 수 없었습니다.
                    }
                }
            }
            let defense_army = {}, defense_injury = {}, defense_fort = {};
            for (let [k, d] of Object.entries(c['outcome_unit']['def'])) {
                if (k !== 'injury_army' && k !== 'abandon_army') {
                    d['dead'] = ns_util.math(d?.amount ?? 0).minus(d?.injury ?? 0).minus(d?.remain ?? 0).number;
                    if (ns_cs.m.army[k]) {
                        defense_army[k] = d;
                    } else if (ns_cs.m.fort[k]) {
                        defense_fort[k] = d;
                    } else if (k === 'wall') {
                        defense_fort[k] = d;
                    }
                }

                if (k === 'abandon_army') {
                    if (d === true && battle_info.def.lord_info.lord_name === ns_cs.d.lord.lord_name.v) {
                        ns_report.drawDescription(ns_i18n.t('injury_army_accept'), ns_i18n.t('injury_army_accept_description')); // 의료원의 부상병 수용 공간이 부족하여 부상병을 모두 수용할 수 없었습니다.
                    }
                }
            }

            if (Object.keys(attack_army).length > 0) {
                ns_report.drawBoxList(ns_i18n.t('attack_army_information'), 'army', attack_army); // 공격측 병력 정보
            }
            if (Object.keys(attack_fort).length > 0) {
                ns_report.drawBoxList(ns_i18n.t('attack_fort_information'), 'fort', attack_fort); // 공격측 방어시설 정보
            }
            if (Object.keys(attack_injury).length > 0) {
                ns_report.drawBoxList(ns_i18n.t('attack_injury_information'), 'army', attack_army); // 공격측 부상 정보
            }

            if (Object.keys(defense_army).length > 0) {
                ns_report.drawBoxList(ns_i18n.t('defense_army_information'), 'army', defense_army); // 방어측 병력 정보
            }
            if (Object.keys(defense_fort).length > 0) {
                ns_report.drawBoxList(ns_i18n.t('defense_fort_information'), 'fort', defense_fort); // 방어측 방어시설 정보
            }
            if (Object.keys(defense_injury).length > 0) {
                ns_report.drawBoxList(ns_i18n.t('defense_injury_information'), 'army', attack_army); // 방어측 부상 정보
            }
        }

        // 기존 자원
        if (c['outcome']['plunder']) {
            let resource_info = {};
            for (let [k, d] of Object.entries(c['plunder']['own'])) {
                resource_info[k] = d;
            }
            if (Object.keys(resource_info).length > 0) {
                ns_report.drawBoxList(ns_i18n.t('defense_resources'), 'resource', resource_info); // 방어측 자원
            }
        }

        // 약탈 자원
        if (c['outcome']['plunder']) {
            let resource_info = {};
            for (let [k, d] of Object.entries(c['plunder'].get)) {
                resource_info[k] = d;
            }
            if (Object.keys(resource_info).length > 0) {
                ns_report.drawBoxList(ns_i18n.t('predatory_resources'), 'resource', resource_info); // 약탈 자원
            }
        }

        // 획득 아이템
        if (c['outcome']['reward'] && c['outcome']['reward']?.['item_desc']) {
            let _reward = c['outcome']['reward'];
            let reward_item = {};
            reward_item[_reward.item_pk] = _reward.item_cnt;
            let box = ns_report.drawBoxList(ns_i18n.t('acquired_item'), 'item', reward_item); // 획득 아이템
            if (_reward?.double_event === true) {
                box.find('div.item_image').addCss('double_event');
            }
        }

        // 토벌령 이벤트 아이템 추가
        /*if (c['battle_info'].def_type == 'suppress') {
            c['outcome'].reward.item_cnt = c['outcome'].reward.item_cnt || 1;
            reward_item_array.push({item_cnt: 1, item_desc: ns_cs.m.item[500001].title, item_pk: 500001});
        }*/

        // turn_description
        /*if (content_json.turn_description) {
            let div = document.createElement('div');
            div.setAttribute('class', 'cont_report_turn_info');
            tpl.append(div);
            tpl.find('.cont_report_turn_info').append(content_json.turn_description);
        }*/
    }
}

/* ************************************************** */

ns_button.buttons.report_battle_detail_close = new nsButtonSet('report_battle_detail_close', 'button_back', 'report_battle_detail', { base_class: ns_button.buttons.common_close });
ns_button.buttons.report_battle_detail_sub_close = new nsButtonSet('report_battle_detail_sub_close', 'button_full', 'report_battle_detail', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.report_battle_detail_close_all = new nsButtonSet('report_battle_detail_close_all', 'button_close_all', 'report_battle_detail', { base_class: ns_button.buttons.common_close_all });


/*******************************************************************/
ns_dialog.dialogs.letter_add_receiver = new nsDialogSet('letter_add_receiver', 'dialog_pop', 'size-medium', { do_close_all: false });

ns_dialog.dialogs.letter_add_receiver.cacheContents = function()
{
    this.cont_obj.letter_find_lord = new nsObject('.letter_find_lord', this.obj);
    this.cont_obj.tbody = new nsObject('tbody.search_list', this.obj);
    this.cont_obj.lord_search_result = new nsObject('#lord_search_result',this.obj);
}

ns_dialog.dialogs.letter_add_receiver.draw = function()
{
    this.cont_obj.lord_search_result.hide();
    this.cont_obj.tbody.empty();
}

ns_dialog.dialogs.letter_add_receiver.findLordName = function(_lord_name)
{
    let dialog = ns_dialog.dialogs.letter_add_receiver;
    if (_lord_name.length < 1) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_plz_input_lord_name'));
        this.cont_obj.lord_search_result.hide();
        return;
    } else if (_lord_name.length > 20) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_lord_name_error'));
        this.cont_obj.lord_search_result.hide();
        return;
    }

    let post_data = { };
    post_data['lord_name'] = _lord_name;

    ns_xhr.post('/api/letter/findLordName', post_data, function(_data, _status)
    {
        dialog.cont_obj.letter_find_lord.value('');
        if(! ns_xhr.returnCheck(_data)) {
            dialog.cont_obj.lord_search_result.hide();
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];
        dialog.cont_obj.lord_search_result.show();

        for (let d of Object.values(_data)) {
            let tr = document.createElement('tr');

            let col1 = document.createElement('td');
            let col1_span = document.createElement('span');
            col1_span.innerHTML = d.lord_name;
            col1.appendChild(col1_span);

            let button = document.createElement('span'); // tr에는 버튼 이벤트가 먹질 않아 꼼수로 처리
            button.setAttribute('id', `ns_button_add_receiver_${d.lord_pk}`);
            col1.appendChild(button);

            let col2 = document.createElement('td');
            let col2_span = document.createElement('span');
            col2_span.innerHTML = d.power;
            col2.appendChild(col2_span);

            let col3 = document.createElement('td');
            let col3_span = document.createElement('span');
            col3_span.innerHTML = d.rank_power;
            col3.appendChild(col3_span);

            tr.appendChild(col1);
            tr.appendChild(col2);
            tr.appendChild(col3);

            dialog.cont_obj.tbody.append(tr);

            let button_id = `add_receiver_${d.lord_pk}`;
            ns_button.buttons[button_id] = new nsButtonSet(button_id, 'button_table', 'ranking');
            ns_button.buttons[button_id].mouseUp = function ()
            {
                ns_dialog.dialogs.report.receiverAdd(d.lord_pk, d.lord_name);
                ns_dialog.close('letter_add_receiver');
            }
            dialog.buttons.push(ns_button.buttons[button_id]);
        }
    });
}

/* ************************************************** */

ns_button.buttons.letter_add_receiver_close = new nsButtonSet('letter_add_receiver_close', 'button_pop_close', 'letter_add_receiver', { base_class: ns_button.buttons.common_close });
ns_button.buttons.letter_add_receiver_sub_close = new nsButtonSet('letter_add_receiver_sub_close', 'button_full', 'letter_add_receiver', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.letter_add_receiver_find_lord= new nsButtonSet('letter_add_receiver_find_lord', 'button_pop_normal', 'letter_add_receiver');
ns_button.buttons.letter_add_receiver_find_lord.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.letter_add_receiver;
    let lord_name = dialog.cont_obj.letter_find_lord.value();
    if (lord_name.length < 1) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_plz_input_lord_name'));
        return;
    } else if (lord_name.length > 20) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_letter_lord_name_error'));
        return;
    }
    dialog.findLordName(lord_name);
}

ns_button.buttons.letter_add_receiver_find_alliance= new nsButtonSet('letter_add_receiver_find_alliance', 'button_pop_normal', 'letter_add_receiver');
ns_button.buttons.letter_add_receiver_find_alliance.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.letter_add_receiver;
    ns_dialog.open('letter_receiver_alliance');
}

/*******************************************************************/
ns_dialog.dialogs.hero_battle_result_viewer = new nsDialogSet('hero_battle_result_viewer', 'dlg_sub', 'fullsub', {do_close_all:false});

ns_dialog.dialogs.hero_battle_result_viewer.cacheContents = function()
{
}

ns_dialog.dialogs.hero_battle_result_viewer.draw = function()
{
    this.customShow();
}

/* ************************************************** */

ns_button.buttons.hero_battle_result_viewer_close = new nsButtonSet('hero_battle_result_viewer_close', 'button_back', 'hero_battle_result_viewer', {base_class:ns_button.buttons.common_close});
ns_button.buttons.hero_battle_result_viewer_close_all = new nsButtonSet('hero_battle_result_viewer_close_all', 'button_close_all', 'hero_battle_result_viewer', {base_class:ns_button.buttons.common_close_all});

/* ********** */

/*******************************************************************/
/*
ns_dialog.dialogs.battle_result_viewer = new nsDialogSet('battle_result_viewer', 'dlg_sub', 'fullsub', {do_close_all:false});
ns_dialog.dialogs.battle_result_viewer.curr_turn = 0;
ns_dialog.dialogs.battle_result_viewer.final_turn = 0;
ns_dialog.dialogs.battle_result_viewer.att_before_skill = null;
ns_dialog.dialogs.battle_result_viewer.def_before_skill = null;

ns_dialog.dialogs.battle_result_viewer.cacheContents = function()
{
    this.cont_obj.cont_turn = this.cont_obj.find('.cont_turn');
    this.cont_obj.cont_curr_turn = this.cont_obj.find('.cont_curr_turn');
    this.cont_obj.cont_max_turn = this.cont_obj.find('.cont_max_turn');

    this.cont_obj.cont_attack_fighting_spirit = this.cont_obj.find('.cont_attack_fighting_spirit');
    this.cont_obj.cont_defence_fighting_spirit = this.cont_obj.find('.cont_defence_fighting_spirit');

    this.cont_obj.cont_report_battle_att_skill = this.cont_obj.find('.cont_report_battle_att_skill');
    this.cont_obj.cont_report_battle_def_skill = this.cont_obj.find('.cont_report_battle_def_skill');

    this.cont_obj.cont_battle_att_skill = this.cont_obj.find('.cont_battle_att_skill');
    this.cont_obj.cont_battle_def_skill = this.cont_obj.find('.cont_battle_def_skill');

    this.cont_obj.cont_battle_att_scene = this.cont_obj.find('.cont_battle_att_scene');
    this.cont_obj.cont_battle_def_scene = this.cont_obj.find('.cont_battle_def_scene');

    this.cont_obj.cont_battle_winner = this.cont_obj.find('.cont_battle_winner');
}

ns_dialog.dialogs.battle_result_viewer.draw = function()
{
    this.init_battle();

    //도움말 관련하여 추가 - 첫 접속시 무조건 한번은 보여주도록
    // let help_type = 'BattleResultViewer';
    // if (!window.localStorage.getItem('open_help_' + help_type))
    // {
    //     ns_dialog.setDataOpen('game_help', {'type':help_type});
    //     window.localStorage.setItem('open_help_' + help_type, 'Y');
    // }

    qbw_sound.play('battle', {loop:true});

    this.draw_battle(this.turn);

    this.customShow();
}

ns_dialog.dialogs.battle_result_viewer.init_battle = function()
{
    let dialog = ns_dialog.dialogs.battle_result_viewer;

    dialog.turn = 0;
    dialog.att_before_skill = null;
    dialog.def_before_skill = null;
}

ns_dialog.dialogs.battle_result_viewer.draw_battle = function(_curr_turn)
{
    let dialog = ns_dialog.dialogs.battle_result_viewer;
    let m_skil = ns_cs.m.hero_skil;
    let data = this.data.data
    let battle_json = data.content_battle_json;
    let content_json = data.content_json;

    let battle_info = battle_json.battle_info;

    let scene = battle_json.scene;

    dialog.cont_obj.cont_max_turn.text(parseInt(scene.length)-1);
    dialog.final_turn = parseInt(scene.length);

    dialog.cont_obj.cont_curr_turn.text(_curr_turn);
    dialog.curr_turn = _curr_turn;
    dialog.cont_obj.cont_turn.html(_curr_turn + '합');

    // 버튼
    dialog.cont_obj.cont_battle_winner.html('&nbsp;');
    if (parseInt(_curr_turn) == (parseInt(dialog.final_turn) - 1))
    {
        ns_button.buttons.battle_turn_prev.setEnable();
        ns_button.buttons.battle_turn_next.setDisable();

        // 마지막 턴에 승리측 표기
        if (content_json.battle_info.unit_battle_winner == 'att')
            dialog.cont_obj.cont_battle_winner.html('공격측 승리');
        else
            dialog.cont_obj.cont_battle_winner.html('방어측 승리');
    }
    else if (_curr_turn == 0)
    {
        ns_button.buttons.battle_turn_prev.setDisable();
        ns_button.buttons.battle_turn_next.setEnable();
    }
    else
    {
        ns_button.buttons.battle_turn_prev.setEnable();
        ns_button.buttons.battle_turn_next.setEnable();
    }


    // 공격측 사기
    if (battle_info.att.lord_info.fighting_spirit)
    {
        dialog.cont_obj.cont_attack_fighting_spirit.html(parseInt(battle_info.att.lord_info.fighting_spirit));
    }
    // 방어측 사기
    if (battle_info.def.lord_info.fighting_spirit)
    {
        dialog.cont_obj.cont_defence_fighting_spirit.html(parseInt(battle_info.def.lord_info.fighting_spirit));
    }

    let cnt = 0;

    // 공격측 버프
    let att_buff_use = false;
    dialog.cont_obj.cont_report_battle_att_skill.empty();
    if (battle_info.att.item)
    {
        $.each(battle_info.att.item, function(k, d)
        {
            if (d > 0)
            {
                let span = document.createElement('span');
                span.setAttribute('class', 'cont_item_buff cont_item_buff_' + k);

                dialog.cont_obj.cont_report_battle_att_skill.append(span);
                att_buff_use = true;
            }
        });
    }

    // 방어측 버프
    let def_buff_use = false;
    dialog.cont_obj.cont_report_battle_def_skill.empty();
    if (battle_info.def.item)
    {
        $.each(battle_info.def.item, function(k, d)
        {
            if (d > 0)
            {
                let span = document.createElement('span');
                span.setAttribute('class', 'cont_item_buff cont_item_buff_' + k);

                dialog.cont_obj.cont_report_battle_def_skill.append(span);
                def_buff_use = true;
            }
        });
    }

    let att_fire = false;
    let def_fire = false;

    dialog.cont_obj.cont_battle_att_skill.text(ns_i18n.t('no_hero_battle_skill_description')); // 전투 스킬 발동 없음
    dialog.cont_obj.cont_battle_def_skill.text(ns_i18n.t('no_hero_battle_skill_description'));
    if (_curr_turn == 0)
    {
        // 사전 발동 스킬
        if (battle_json.hero_battle.att.before_battle_skill)
        {
            if (battle_json.hero_battle.att.before_battle_skill.pk)
            {
                let m_skill = ns_cs.m.hero_skil[battle_json.hero_battle.att.before_battle_skill.pk];
                let m_hero = ns_cs.m.hero[content_json.outcome_hero.att.staff_m_hero_pk];
                let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];

                let  battle_skill_icon = document.createElement('span');
                battle_skill_icon.setAttribute('class', 'cont_hero_skill_icon cont_hero_skill_icon_' + m_skill.m_hero_skil_pk.substr(0, 4));

                let  battle_skill_title = document.createElement('span');
                battle_skill_title.innerHTML = m_hero_base.name + ' Lv.' + m_skill.rare + ' ' + m_skill.title;

                dialog.cont_obj.cont_battle_att_skill.empty().append(battle_skill_icon).append(battle_skill_title);
                if (String(battle_json.hero_battle.att.before_battle_skill.pk).substr(0, 4))
                {
                    att_skill = true;
                }
                // 버프 스킬인지 체크
                if (String(battle_json.hero_battle.att.before_battle_skill.pk).substr(0, 4) != '1573')
                {
                    dialog.att_before_skill = String(battle_json.hero_battle.att.before_battle_skill.pk).substr(0, 4);
                }
            }
        }

        if (battle_json.hero_battle.def.before_battle_skill)
        {
            if (battle_json.hero_battle.def.before_battle_skill.pk)
            {
                let m_skill = ns_cs.m.hero_skil[battle_json.hero_battle.def.before_battle_skill.pk];
                let m_hero = ns_cs.m.hero[content_json.outcome_hero.def.staff_m_hero_pk];
                let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];

                let  battle_skill_icon = document.createElement('span');
                battle_skill_icon.setAttribute('class', 'cont_hero_skill_icon cont_hero_skill_icon_' + m_skill.m_hero_skil_pk.substr(0, 4));

                let  battle_skill_title = document.createElement('span');
                battle_skill_title.innerHTML = m_hero_base.name + ' Lv.' + m_skill.rare + ' ' + m_skill.title;

                dialog.cont_obj.cont_battle_def_skill.empty().append(battle_skill_icon).append(battle_skill_title);
                if (String(battle_json.hero_battle.def.before_battle_skill.pk).substr(0, 4))
                {
                    def_skill = true;
                }
                // 버프 스킬인지 체크
                if (String(battle_json.hero_battle.def.before_battle_skill.pk).substr(0, 4) != '1573')
                {
                    dialog.def_before_skill = String(battle_json.hero_battle.def.before_battle_skill.pk).substr(0, 4);
                }
            }
        }
    }

    // 사전 스킬이 버프라면 그려주기
    if (dialog.att_before_skill && dialog.att_before_skill != '1573')
    {
        let span = document.createElement('span');
        span.setAttribute('class', 'cont_item_buff cont_hero_skill_icon_' + dialog.att_before_skill);

        dialog.cont_obj.cont_report_battle_att_skill.append(span);
        att_buff_use = true;
    }
    if (dialog.def_before_skill && dialog.def_before_skill != '1573')
    {
        let span = document.createElement('span');
        span.setAttribute('class', 'cont_item_buff cont_hero_skill_icon_' + dialog.def_before_skill);

        dialog.cont_obj.cont_report_battle_def_skill.append(span);
        def_buff_use = true;
    }

    if (att_buff_use == false)
        dialog.cont_obj.cont_report_battle_att_skill.text(ns_i18n.t('no_buff_description')); // 버프 없음

    if (def_buff_use == false)
        dialog.cont_obj.cont_report_battle_def_skill.text(ns_i18n.t('no_buff_description'));

    // 병력 그려주기
    dialog.cont_obj.cont_battle_att_scene.empty();
    dialog.cont_obj.cont_battle_def_scene.empty();
    if (parseInt(_curr_turn) < parseInt(scene.length))
    {
        // 현재 턴 정보
        let turn_info = scene[_curr_turn];
        let battle_unit = turn_info['battle_unit'];

        // 스킬 발동 체크
        if (turn_info.att_battle_skill)
        {
            if (turn_info.att_battle_skill.pk)
            {
                let m_skill = ns_cs.m.hero_skil[turn_info.att_battle_skill.pk];
                let m_hero = ns_cs.m.hero[turn_info.att_battle_skill.hero_pk];
                let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];

                let  battle_skill_icon = document.createElement('span');
                battle_skill_icon.setAttribute('class', 'cont_hero_skill_icon cont_hero_skill_icon_' + m_skill.m_hero_skil_pk.substr(0, 4));

                let  battle_skill_title = document.createElement('span');
                battle_skill_title.innerHTML = m_hero_base.name + ' Lv.' + m_skill.rare + ' ' + m_skill.title;

                dialog.cont_obj.cont_battle_att_skill.empty().append(battle_skill_icon).append(battle_skill_title);
            }
        }

        // 스킬 발동 체크
        if (turn_info.def_battle_skill)
        {
            if (turn_info.def_battle_skill.pk)
            {
                let m_skill = ns_cs.m.hero_skil[turn_info.def_battle_skill.pk];
                let m_hero = ns_cs.m.hero[turn_info.def_battle_skill.hero_pk];
                let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];

                let  battle_skill_icon = document.createElement('span');
                battle_skill_icon.setAttribute('class', 'cont_hero_skill_icon cont_hero_skill_icon_' + m_skill.m_hero_skil_pk.substr(0, 4));

                let  battle_skill_title = document.createElement('span');
                battle_skill_title.innerHTML = m_hero_base.name + ' Lv.' + m_skill.rare + ' ' + m_skill.title;

                dialog.cont_obj.cont_battle_def_skill.empty().append(battle_skill_icon).append(battle_skill_title);
            }
        }

        let sorted = [];

        // 정렬
        $.each(turn_info['att_pos'], function(k, d) // att_pos
        {
            if (isNaN(k) == true)
                return true;
            sorted.push({'k':k,'d':d});
        });
        if (sorted.length > 1)
            sorted.objSort('k', 1);

        // 공격측 병력 정보
        let att_army = turn_info['att_unit'];

        // 공격측
        for (let i = 0, i_l = sorted.length; i < i_l; i++)
        {
            let k = sorted[i]['k'];
            let d = sorted[i]['d'];

            // 한 라인에 위치하는 병과 갯수에 따른 top 위치
            let div = document.createElement('div');
            div.setAttribute('class', 'cont_battle_line_att');
            let army_class = '';

            // 해당 라인에 병력 넣기
            $.each(d, function(key, data)
            {
                let army = document.createElement('div');

                if (data == 'trap' || data == 'abatis' || data == 'tower' || data == 'wall')
                    army_class = 'buil_CastleWall_fort_desc_' + data;
                else
                    army_class = 'buil_Army_desc_' + data;

                army.setAttribute('class', 'cont_battle_unit_att ' + army_class);

                // 테두리
                let  wrap = document.createElement('div');
                wrap.setAttribute('class', 'cont_battle_unit_att_wrap');

                if (att_army[data])
                {
                    // 방어측 화계가 발동 되었다면
                    if (_curr_turn == 0)
                    {
                        if (battle_json.hero_battle.def.before_battle_skill && battle_json.hero_battle.def.before_battle_skill.pk && String(battle_json.hero_battle.def.before_battle_skill.pk).substr(0, 4) == '1573')
                        {
                            // 화계
                            let  skill_icon = document.createElement('div');
                            skill_icon.setAttribute('class', 'cont_battle_skill_icon cont_hero_skill_icon_' + String(battle_json.hero_battle.def.before_battle_skill.pk).substr(0, 4));

                            wrap.appendChild(skill_icon);
                        }
                        else if (battle_json.hero_battle.att.before_battle_skill && battle_json.hero_battle.att.before_battle_skill.pk && String(battle_json.hero_battle.att.before_battle_skill.pk).substr(0, 4) != '1573'  && (data != 'trap' && data != 'abatis' && data != 'tower' && data != 'wall'))
                        {
                            // 버프
                            let  skill_icon = document.createElement('div');
                            skill_icon.setAttribute('class', 'cont_battle_skill_icon cont_hero_skill_icon_' + String(battle_json.hero_battle.att.before_battle_skill.pk).substr(0, 4));

                            wrap.appendChild(skill_icon);
                        }
                    }
                    else
                    {
                        // 스킬 사용 시
                        if (scene[_curr_turn].def_battle_skill && scene[_curr_turn].att_battle_skill.pk)
                        {
                            let add_skill_icon = false;
                            if (ns_cs.m.hero_skil[scene[_curr_turn].att_battle_skill.pk].battle_type == 'E')
                            {
                                add_skill_icon = true;
                            }
                            else if (ns_cs.m.hero_skil[scene[_curr_turn].att_battle_skill.pk].battle_type == 'D')
                            {
                                if (data != 'trap' && data != 'abatis' && data != 'tower' && data != 'wall')
                                {
                                    add_skill_icon = true;
                                }
                            }
                            else if (ns_cs.m.hero_skil[scene[_curr_turn].att_battle_skill.pk].battle_type == 'A')
                            {
                                if (data != 'wall')
                                {
                                    add_skill_icon = true;
                                }
                            }

                            if (add_skill_icon)
                            {
                                let  skill_icon = document.createElement('div');
                                skill_icon.setAttribute('class', 'cont_battle_skill_icon cont_hero_skill_icon_' + String(scene[_curr_turn].att_battle_skill.pk).substr(0, 4));

                                wrap.appendChild(skill_icon);
                            }
                        }

                        // 데미지
                        if (parseInt(att_army[data].dead) > 0)
                        {
                            let  daamge = document.createElement('div');
                            daamge.setAttribute('class', 'cont_unit_daamge');
                            daamge.innerHTML = -att_army[data].dead;

                            wrap.appendChild(daamge);
                        }
                    }

                    // 현재 병력
                    let  remain = document.createElement('div');
                    remain.setAttribute('class', 'cont_unit_remain');
                    remain.innerHTML = att_army[data].remain;

                    wrap.appendChild(remain);
                }

                // 공격
                if (battle_unit['att'] && battle_unit['att'].indexOf(data) >= 0 )
                {
                    let  attacker = document.createElement('div');
                    attacker.setAttribute('class', 'cont_battle_unit_attacker');

                    army.appendChild(attacker);
                }

                // 병과 이름
                let  title = document.createElement('div');
                title.setAttribute('class', 'cont_unit_title');
                if (data === 'trap' || data === 'abatis' || data === 'tower')
                {
                    title.innerHTML =ns_i18n.t(`fort_title_${data}`);
                }
                else if (data === 'wall')
                {
                    title.innerHTML = ns_i18n.t('castle_wall'); // 성벽
                }
                else
                {
                    title.innerHTML = ns_i18n.t(`army_title_${data}`);
                }

                army.appendChild(title);

                army.appendChild(wrap);
                div.appendChild(army);
            });

            dialog.cont_obj.cont_battle_att_scene.prepend(div); // 라인 넣기

        }

        // 정렬
        sorted = []; // 초기화

        $.each(turn_info['def_pos'], function(k, d)
        {
            if (isNaN(k) == true)
                return true;
            sorted.push({'k':k,'d':d});
        });
        if (sorted.length > 1)
            sorted.objSort('k', 1);

        // 방어측 병력 정보
        let def_army = turn_info['def_unit'];

        // 방어측
        for (let i = 0, i_l = sorted.length; i < i_l; i++)
        {
            let k = sorted[i]['k'];
            let d = sorted[i]['d'];

            // 한 라인에 위치하는 병과 갯수에 따른 top 위치
            let div = document.createElement('div');
            div.setAttribute('class', 'cont_battle_line_def');

            // 해당 라인에 병력 넣기
            $.each(d, function(key, data)
            {
                let army = document.createElement('div');

                if (data == 'trap' || data == 'abatis' || data == 'tower' || data == 'wall')
                    army_class = 'buil_CastleWall_fort_desc_' + data;
                else
                    army_class = 'buil_Army_desc_' + data;

                army.setAttribute('class', 'cont_battle_unit_def ' + army_class);

                // 테두리
                let  wrap = document.createElement('div');
                wrap.setAttribute('class', 'cont_battle_unit_def_wrap');

                if (def_army[data])
                {
                    // 공격측 화계가 발동 되었다면
                    if (_curr_turn == 0)
                    {
                        // 스킬 사용시
                        if (battle_json.hero_battle.att.before_battle_skill && battle_json.hero_battle.att.before_battle_skill.pk && String(battle_json.hero_battle.att.before_battle_skill.pk).substr(0, 4) == '1573')
                        {
                            // 화계
                            let  skill_icon = document.createElement('div');
                            skill_icon.setAttribute('class', 'cont_battle_skill_icon cont_hero_skill_icon_' + String(battle_json.hero_battle.att.before_battle_skill.pk).substr(0, 4));

                            wrap.appendChild(skill_icon);
                        }
                        else if (battle_json.hero_battle.def.before_battle_skill && battle_json.hero_battle.def.before_battle_skill.pk && String(battle_json.hero_battle.def.before_battle_skill.pk).substr(0, 4) != '1573' && (data != 'trap' && data != 'abatis' && data != 'tower' && data != 'wall'))
                        {
                            // 버프
                            let  skill_icon = document.createElement('div');
                            skill_icon.setAttribute('class', 'cont_battle_skill_icon cont_hero_skill_icon_' + String(battle_json.hero_battle.def.before_battle_skill.pk).substr(0, 4));

                            wrap.appendChild(skill_icon);
                        }

                        let damage_value = battle_info.unit_info.att[data].unit_amount - def_army[data].remain;
                        if (damage_value > 0)
                        {
                            let  daamge = document.createElement('div');
                            daamge.setAttribute('class', 'cont_unit_daamge');
                            daamge.innerHTML = -damage_value;

                            wrap.appendChild(daamge);
                        }
                    }
                    else
                    {
                        // 스킬 사용 시
                        if (scene[_curr_turn].def_battle_skill && scene[_curr_turn].def_battle_skill.pk)
                        {
                            let add_skill_icon = false;
                            if (ns_cs.m.hero_skil[scene[_curr_turn].def_battle_skill.pk].battle_type == 'E')
                            {
                                add_skill_icon = true;
                            }
                            else if (ns_cs.m.hero_skil[scene[_curr_turn].def_battle_skill.pk].battle_type == 'D')
                            {
                                if (data != 'trap' && data != 'abatis' && data != 'tower' && data != 'wall')
                                {
                                    add_skill_icon = true;
                                }
                            }
                            else if (ns_cs.m.hero_skil[scene[_curr_turn].def_battle_skill.pk].battle_type == 'A')
                            {
                                if (data != 'wall')
                                {
                                    add_skill_icon = true;
                                }
                            }

                            if (add_skill_icon)
                            {
                                let  skill_icon = document.createElement('div');
                                skill_icon.setAttribute('class', 'cont_battle_skill_icon cont_hero_skill_icon_' + String(scene[_curr_turn].def_battle_skill.pk).substr(0, 4));

                                wrap.appendChild(skill_icon);
                            }
                        }

                        // 데미지
                        if (parseInt(def_army[data].dead) > 0)
                        {
                            let  daamge = document.createElement('div');
                            daamge.setAttribute('class', 'cont_unit_daamge');
                            daamge.innerHTML = -def_army[data].dead;

                            wrap.appendChild(daamge);

                        }
                    }

                    // 현재 병력
                    let  remain = document.createElement('div');
                    remain.setAttribute('class', 'cont_unit_remain');
                    remain.innerHTML = def_army[data].remain;

                    wrap.appendChild(remain);
                }

                // 공격
                if (battle_unit['def'] && battle_unit['def'].indexOf(data) >= 0 )
                {
                    let  attacker = document.createElement('div');
                    attacker.setAttribute('class', 'cont_battle_unit_attacker');

                    army.appendChild(attacker);
                }

                // 병과 이름
                let  title = document.createElement('div');
                title.setAttribute('class', 'cont_unit_title');

                if (data == 'trap' || data == 'abatis' || data == 'tower')
                {
                    title.innerHTML = ns_cs.m.fort[data].title;
                }
                else if (data == 'wall')
                {
                    title.innerHTML = '성벽';
                }
                else
                {
                    title.innerHTML = ns_cs.m.army[data].title;
                }

                army.appendChild(title);

                army.appendChild(wrap);
                div.appendChild(army);
            });

            dialog.cont_obj.cont_battle_def_scene.append(div); // 라인 넣기

        }
    }
}

ns_dialog.dialogs.battle_result_viewer.erase = function()
{
    qbw_sound.stop('battle');
    qbw_sound.stop('victory');
    qbw_sound.stop('defeat');

    this.customHide();
}

ns_button.buttons.battle_result_viewer_close = new nsButtonSet('battle_result_viewer_close', 'button_back', 'battle_result_viewer', {base_class:ns_button.buttons.common_close});
ns_button.buttons.battle_result_viewer_close_all = new nsButtonSet('battle_result_viewer_close_all', 'button_close_all', 'battle_result_viewer', {base_class:ns_button.buttons.common_close_all});

ns_button.buttons.game_help_BattleResultViewer = new nsButtonSet('game_help_BattleResultViewer', 'button_dlg_help', 'battle_result_viewer', {base_class:ns_button.buttons.buil_help});


ns_button.buttons.battle_turn_prev = new nsButtonSet('battle_turn_prev', 'button_page_prev', 'battle_result_viewer');
ns_button.buttons.battle_turn_prev.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.battle_result_viewer;

    let next_turn = parseInt(dialog.curr_turn)-1;

    if (next_turn >= (parseInt(dialog.final_turn)-1))
    {
        next_turn = (parseInt(dialog.final_turn)-1);
    }
    else if (next_turn < 0)
    {
        next_turn = 0;
    }

    dialog.cont_obj.cont_curr_turn.text(next_turn);
    dialog.cont_obj.cont_max_turn.text(parseInt(dialog.final_turn)-1);

    dialog.draw_battle(next_turn);
}

ns_button.buttons.battle_turn_next = new nsButtonSet('battle_turn_next', 'button_page_next', 'battle_result_viewer');
ns_button.buttons.battle_turn_next.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.battle_result_viewer;

    let next_turn = parseInt(dialog.curr_turn)+1;

    if (next_turn >= (parseInt(dialog.final_turn)-1))
    {
        next_turn = parseInt(dialog.final_turn)-1;
    }
    else if (next_turn < 0)
    {
        next_turn = 0;
    }

    dialog.cont_obj.cont_curr_turn.text(next_turn);
    dialog.cont_obj.cont_max_turn.text(parseInt(dialog.final_turn)-1);

    dialog.draw_battle(next_turn)
};*/
