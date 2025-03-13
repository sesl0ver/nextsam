ns_dialog.dialogs.lord_info = new nsDialogSet('lord_info', 'dialog_full', 'size-full', { do_close_all:false });

ns_dialog.dialogs.lord_info.cacheContents = function()
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);

    this.cont_obj.lord_info_basic_panel = new nsObject('#lord_info_basic_panel', this.obj);
    this.cont_obj.lord_info_table = new nsObject('.lord_info_table', this.obj);
    this.cont_obj.lord_info_intro = new nsObject('.lord_info_intro', this.obj);

    this.cont_obj.lord_info_desc_panel = new nsObject('#lord_info_desc_panel', this.obj);

    this.cont_obj.alliance_info_panel = new nsObject('#alliance_info_panel', this.obj);
    this.cont_obj.alliance_info_intro = new nsObject('.alliance_info_intro', this.obj);

    this.cont_obj.lord_info_alliance_position = new nsObject('.lord_info_alliance_position', this.obj);
}

ns_dialog.dialogs.lord_info.draw = function()
{

    //도움말 관련하여 추가 - 첫 접속시 무조건 한번은 보여주도록
    /*let help_type = 'LordInfo';
    if (!window.localStorage.getItem('open_help_' + help_type))
    {
        ns_dialog.setDataOpen('game_help', {'type':help_type});
        window.localStorage.setItem('open_help_' + help_type, 'Y');
    }*/

    this.drawLordInfo();
}

ns_dialog.dialogs.lord_info.erase = function()
{
    ns_dialog.close('lord_info_lord_name_change');
    this.data = null;
}

ns_dialog.dialogs.lord_info.drawLordInfo = function()
{
    let dialog = ns_dialog.dialogs.lord_info;

    // 군주 버튼관련 설정
    ns_button.buttons.alliance_info_intro_change.obj.show();
    if (! dialog.data) {
        ns_button.buttons.lord_info_intro_change.obj.show();
        ns_button.buttons.lord_info_card_change.obj.show();
        ns_button.buttons.lord_info_lord_name_change.obj.show();
        ns_button.buttons.lord_info_letter.obj.hide();
    } else {
        if (ns_util.math(ns_cs.d.lord.lord_pk.v).eq(dialog.data.lord_pk)) {
            ns_button.buttons.lord_info_intro_change.obj.show();
            ns_button.buttons.lord_info_card_change.obj.show();
            ns_button.buttons.lord_info_lord_name_change.obj.show();
            ns_button.buttons.lord_info_letter.obj.hide();
            ns_button.buttons.alliance_info_intro_change.obj.show();
        } else {
            ns_button.buttons.lord_info_intro_change.obj.hide();
            ns_button.buttons.lord_info_card_change.obj.hide();
            ns_button.buttons.lord_info_lord_name_change.obj.hide();
            ns_button.buttons.lord_info_letter.obj.show();
            ns_button.buttons.alliance_info_intro_change.obj.hide();
        }

        if (dialog.data.alli_pk) {
            dialog.cont_obj.alliance_info_panel.show();
            ns_button.buttons.lord_info_alliance_info.obj.show();
        }
    }

    // 군주소개 비우기
    this.cont_obj.lord_info_intro.empty();
    this.cont_obj.alliance_info_intro.empty();
    this.cont_obj.lord_info_alliance_position.hide();

    let post_data = { };
    if (dialog.data) {
        post_data['lord_pk'] = dialog.data.lord_pk;
    }

    ns_xhr.post('/api/lord/getLordInfo', post_data, dialog.drawRemote);
}

ns_dialog.dialogs.lord_info.drawRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.lord_info;
    dialog.data = _data;

    // 군주 정보
    dialog.cont_obj.content_title.text((! dialog.data) ? ns_cs.d.lord.lord_name.v : _data.lord_name);

    dialog.cont_obj.lord_info_table.find('.lord_info_card').text((! dialog.data) ? code_set.lord_name[ns_cs.d.lord.lord_pic.v] : code_set.lord_name[_data.lord_pic]);
    dialog.cont_obj.lord_info_table.find('.lord_info_level').text((! dialog.data) ? ns_cs.d.lord.level.v : _data.level);
    dialog.cont_obj.lord_info_table.find('.lord_info_alliance').text('-'); // 일단 초기화

    dialog.cont_obj.lord_info_table.find('.lord_info_territory').text(_data.posi_pk); // 상대의 좌표는 숨김.
    // ns_cs.d.lord.position_cnt.v + ' / ' + dialog.get_max_territory_cnt(ns_cs.d.lord.level.v) // _data.position_cnt + ' / ' + dialog.get_max_territory_cnt(_data.level)

    dialog.cont_obj.lord_info_table.find('.lord_info_ranking').text((! dialog.data) ? ns_cs.d.lord?.main_rank.v ?? '-' : _data.rank);
    dialog.cont_obj.lord_info_table.find('.lord_info_power').text((! dialog.data) ? ns_util.numberFormat(ns_cs.d.lord?.power.v) : ns_util.numberFormat(_data.power));

    let lord_info_intro = ns_util.forbiddenWordCheck(String(_data.lord_intro).replace(/\n/g, '<br/>'));
    if (! lord_info_intro || lord_info_intro === 'undefined' || lord_info_intro === '' ){
        lord_info_intro = ns_i18n.t('msg_no_lord_intro'); // 동맹 인사말이 없습니다.
        dialog.cont_obj.lord_info_intro.removeCss('text_align_left');
    } else {
        dialog.cont_obj.lord_info_intro.addCss('text_align_left');
    }
    dialog.cont_obj.lord_info_intro.html(lord_info_intro);
    dialog.cont_obj.lord_info_table.find('.lord_info_army_point').text(ns_util.numberFormat(_data.army_point));
    dialog.cont_obj.lord_info_table.find('.lord_info_att_point').text(ns_util.numberFormat(_data.attack_point));
    dialog.cont_obj.lord_info_table.find('.lord_info_def_point').text(ns_util.numberFormat(_data.defence_point));

    // dialog.s.cont_lord_info_alliance_member.text((_data.alliance_member != null) ? qbw_util_numberFormat(parseInt(_data.alliance_member, 10)) : '-');

    ns_button.buttons.lord_info_alliance_request.obj.hide();
    ns_button.buttons.lord_info_manage_transfer.obj.hide();
    ns_button.buttons.lord_info_manage_expulsion.obj.hide();


    if (_data.alliance) {
        let alli_intro = ns_util.forbiddenWordCheck(String(_data.alliance.alli_intro).replace(/\n/g, '<br/>'));
        if (! alli_intro || alli_intro === 'undefined' || alli_intro === '' ){
            alli_intro = ns_i18n.t('msg_no_alliance_intro'); // 동맹 인사말이 없습니다.
            dialog.cont_obj.alliance_info_intro.removeCss('text_align_left');
        } else {
            dialog.cont_obj.alliance_info_intro.addCss('text_align_left');
        }
        dialog.cont_obj.alliance_info_intro.html(alli_intro);
        dialog.cont_obj.alliance_info_panel.show();
        dialog.cont_obj.lord_info_table.find('.lord_info_alliance').text(_data.alliance.title);
        if (ns_cs.d.lord?.alli_pk.v && ns_util.math(_data.alliance.alli_pk).eq(ns_cs.d.lord.alli_pk.v)) { // 같은 동맹인지?
            if (! ns_util.math(_data.lord_pk).eq(ns_cs.d.lord.lord_pk.v)) { // 본인인지?
                ns_button.buttons.lord_info_manage_transfer.obj.show();
                ns_button.buttons.lord_info_manage_expulsion.obj.show();

                // 등급 체크
                ns_button.buttons.lord_info_manage_transfer.setDisable();
                ns_button.buttons.lord_info_manage_expulsion.setDisable();
                if (_data.my_alliance_grade < 4) {
                    ns_button.buttons.lord_info_manage_expulsion.setEnable();
                }
                if (ns_util.math(_data.my_alliance_grade).eq(1)) {
                    ns_button.buttons.lord_info_manage_transfer.setEnable();
                }
            }
        }

        if ( _data.alliance.master_lord) {
            ns_button.buttons.alliance_info_intro_change.obj.show();
            ns_button.buttons.alliance_info_intro_change.setEnable();
        }
        else{
            ns_button.buttons.alliance_info_intro_change.obj.hide();
            ns_button.buttons.alliance_info_intro_change.setDisable();
        }
    } else {
        dialog.cont_obj.alliance_info_panel.hide();
        dialog.cont_obj.lord_info_table.find('.lord_info_alliance').html('-');
        ns_button.buttons.lord_info_alliance_request.obj.show();
        ns_button.buttons.alliance_info_intro_change.obj.hide();
        ns_button.buttons.alliance_info_intro_change.setDisable();
    }

    // 동맹원 리스트 TODO 개인동맹 시스템인 경우 사용하던 것인데 같은 동맹의 군주들을 보여주는데 사용해도 될듯.
    /*ns_button.buttons.lord_info_alliance_delete.obj.show();
    dialog.s.cont_lord_info_alliance_position.show();

    dialog.s.cont_alliance_info_intro.html(forbidden_word_check(unescape_unicode(String(_data.alli_intro).replace(/\n/g, '<br/>'))));

    // 영지 정보
    let dialog_tbody = dialog.s.cont_lord_info_alliance_position.find('tbody');
    let tr;

    dialog_tbody.empty();
    $.each(_data.alli_position, function(k, d) {
        tr = document.createElement('tr');

        let col1 = document.createElement('td');
        col1.setAttribute('class', 'col1');
        col1.innerHTML = d.title;

        let col2 = document.createElement('td');
        col2.setAttribute('class', 'col2');
        col2.innerHTML = d.posi_pk;

        let col3 = document.createElement('td');
        col3.setAttribute('class', 'col3');
        col3.innerHTML = qbw_world.distance_value(qbw_e.cfg.cpp, d.posi_pk);

        tr.appendChild(col1);
        tr.appendChild(col2);
        tr.appendChild(col3);

        dialog_tbody.append(tr);
    });
    } else {
        if (parseInt(ns_cs.d.lord.lord_pk.v) != parseInt(dialog.data.lord_pk))
            ns_button.buttons.lord_info_alliance_request.obj.show();
    }*/
    // }
    // else {
    //      dialog.cont_obj.alliance_info_panel.hide();
    //      ns_button.buttons.alliance_info_intro_change.setDisable();
    // }
    // } else {
    //     dialog.cont_obj.alliance_info_panel.hide();
    //     ns_button.buttons.alliance_info_intro_change.setDisable();
    // }
};

/*ns_dialog.dialogs.lord_info.get_max_territory_cnt = function(_level)
{
    let cnt = 0;
    _level = parseInt(_level);

    switch(_level)
    {
        case 1 :  cnt = 1; break;
        case 2 :  cnt = 1; break;
        case 3 :  cnt = 2; break;
        case 4 :  cnt = 3; break;
        case 5 :  cnt = 4; break;
        case 6 :  cnt = 5; break;
        case 7 :  cnt = 6; break;
        case 8 :  cnt = 7; break;
        case 9 :  cnt = 8; break;
        case 10 :  cnt = 10; break;
    }

    return cnt;
}*/

/* ************************************************** */
ns_button.buttons.lord_info_close = new nsButtonSet('lord_info_close', 'button_back', 'lord_info', {base_class:ns_button.buttons.common_close});
ns_button.buttons.lord_info_sub_close = new nsButtonSet('lord_info_sub_close', 'button_full', 'lord_info', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.lord_info_close_all = new nsButtonSet('lord_info_close_all', 'button_close_all', 'lord_info', {base_class:ns_button.buttons.common_close_all});

// ns_button.buttons.game_help_LordInfo = new nsButtonSet('game_help_LordInfo', 'button_dialog_help', 'lord_info', {base_class:ns_button.buttons.buil_help});

ns_button.buttons.lord_info_letter = new nsButtonSet('lord_info_letter', 'button_default', 'lord_info');
ns_button.buttons.lord_info_letter.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.lord_info;
    ns_dialog.dialogs.report.lordLetterWrite(dialog.data.lord_pk, dialog.data.lord_name);
    ns_dialog.close('lord_info');
}

ns_button.buttons.lord_info_alliance_info = new nsButtonSet('lord_info_alliance_info', 'button_default', 'lord_info');
ns_button.buttons.lord_info_alliance_info.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.lord_info;
    let post_data = {};
    post_data['lord_pk'] = dialog.data.lord_pk;
    ns_xhr.post('/api/alliance/otherInfo', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];
        ns_dialog.setDataOpen('alliance_other_info', _data);
    });
}

ns_button.buttons.lord_info_card_change = new nsButtonSet('lord_info_card_change', 'button_default', 'lord_info');
ns_button.buttons.lord_info_card_change.mouseUp = function(_e)
{
    if (ns_cs.d.hero[ns_cs.d.lord.lord_hero_pk.v].status_cmd !== 'I') {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_lord_card_change_idle_error')); // 군주가 대기 상태일때만 변경할 수 있습니다.
        return;
    }
    ns_dialog.setDataOpen('item_use', { m_item_pk: 500096 });
}

ns_button.buttons.lord_info_lord_name_change = new nsButtonSet('lord_info_lord_name_change', 'button_default', 'lord_info');
ns_button.buttons.lord_info_lord_name_change.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('item_use', { m_item_pk: 500014 });
}

ns_button.buttons.lord_info_intro_change = new nsButtonSet('lord_info_intro_change', 'button_default', 'lord_info');
ns_button.buttons.lord_info_intro_change.mouseUp = function(_e)
{
    ns_dialog.open('lord_intro_change');
}

ns_button.buttons.alliance_info_intro_change = new nsButtonSet('alliance_info_intro_change', 'button_default', 'lord_info');
ns_button.buttons.alliance_info_intro_change.mouseUp = function(_e)
{
    ns_dialog.open('alliance_intro_change');
}

ns_button.buttons.lord_info_alliance_request = new nsButtonSet('lord_info_alliance_request', 'button_default', 'lord_info');
ns_button.buttons.lord_info_alliance_request.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.lord_info;
    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_alliance_invite_member', [dialog.data.lord_name]),
        okFunc: function()
        {
            ns_xhr.post('/api/alliance/invite', { lord_pk: dialog.data.lord_pk }, function(_data, _status)
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];

                // qbw_chat.alliance_update_request();
                ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_invite_result', [dialog.data.lord_name])); // 님을 동맹에 초대 하였습니다.
            }, { useProgress: true });
        }
    });

    /*let okFunc_proc = function()
    {
        let post_data = { };

        post_data['action'] = 'request';
        post_data['lord_pk'] = dialog.data.lord_pk;

        ns_xhr.post('/a/alliance.php', post_data, function(_data, _status)
        {
            if(!ns_xhr.post_return_check(_data))
                return;
            _data = _data.ns_xhr.post_return.add_data;

            ns_dialog.setDataOpen('message', '신청이 정상적으로 완료되었습니다.');
        });
    };

    let noFunc_proc = function()
    {
    };

    ns_dialog.setDataOpen('confirm', { text:'개인 동맹 신청을 하시겠습니까?', okFunc:okFunc_proc, noFunc:noFunc_proc });*/
};

ns_button.buttons.lord_info_manage_transfer = new nsButtonSet('lord_info_manage_transfer', 'button_default', 'lord_info');
ns_button.buttons.lord_info_manage_transfer.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.lord_info;
    let lord_name = dialog.data.lord_name;

    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_alliance_manage_transfer', [lord_name]), // {{1}} 님에게 동맹을 양도합니다.
        okFunc: function()
        {
            ns_xhr.post('/api/alliance/masterTransfer', { lord_pk: dialog.data.lord_pk }, function(_data, _status)
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];

                ns_chat.allianceNotice('alliance_expulsion', {username: ns_cs.d.lord.lord_name.v, next: lord_name});
                ns_dialog.setDataOpen('message', ns_i18n.t('msg_embassy_alliance_member_transfer',[lord_name])); // 님께 동맹을 양도하였습니다.<br />이제부터 동맹원으로 활동합니다.
                ns_button.buttons.lord_info_close.mouseUp();
                ns_dialog.dialogs.alliance.drawMemberList();

                ns_world.lud_max = 0;
            }, { useProgress: true });
        }
    });
}
ns_button.buttons.lord_info_manage_expulsion = new nsButtonSet('lord_info_manage_expulsion', 'button_default', 'lord_info');
ns_button.buttons.lord_info_manage_expulsion.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.lord_info;
    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_alliance_expulsion', [dialog.data.lord_name]), // {{1}} 님을 동맹에서 제명합니다.
        okFunc: function()
        {
            ns_xhr.post('/api/alliance/expulsion', { lord_pk: dialog.data.lord_pk }, function(_data, _status)
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];

                ns_dialog.close('lord_info');
                ns_chat.allianceNotice('alliance_expulsion', {username: _data});
                ns_dialog.setDataOpen('message', ns_i18n.t('msg_embassy_alliance_member_expulsion', [_data])); // $1님이 동맹에서 제명되었습니다.
                ns_dialog.dialogs.alliance.drawMemberList();

                ns_world.lud_max = 0;
            }, { useProgress: true });
        }
    });
}

/*ns_button.buttons.lord_info_alliance_delete = new nsButtonSet('lord_info_alliance_delete', 'button_default', 'lord_info');
ns_button.buttons.lord_info_alliance_delete.mouseUp = function(_e)
{
	let dialog = ns_dialog.dialogs.lord_info;

	let okFunc_proc = function()
	{
		let post_data = { };

		post_data['action'] = 'delete';
		post_data['lord_pk'] = dialog.data.lord_pk;

		ns_xhr.post('/a/alliance.php', post_data, function(_data, _status)
		{
			if(!ns_xhr.post_return_check(_data))
				return;
			_data = _data.ns_xhr.post_return.add_data;

			ns_dialog.setDataOpen('message', '정상적으로 개인 동맹 파기가 완료되었습니다.');
			ns_dialog.close('lord_info');
			ns_dialog.dialogs.alliance.draw_list();
		});
	};

	let noFunc_proc = function()
	{
	};

	ns_dialog.setDataOpen('confirm', { text:'개인동맹 파기 시 해당 군주와는 15일동안 개인동맹을 맺을수 없게됩니다.<br /><br />개인동맹을 파기하시겠습니까?', okFunc:okFunc_proc, noFunc:noFunc_proc });
}*/

/*******************************************************************/

ns_dialog.dialogs.lord_intro_change = new nsDialogSet('lord_intro_change', 'dialog_full', 'size-full', { do_close_all: false });

ns_dialog.dialogs.lord_intro_change.cacheContents = function()
{
    this.cont_obj.lord_intro_text = new nsObject('textarea[name=lord_intro_text]', this.obj);
}

ns_dialog.dialogs.lord_intro_change.draw = function()
{
    ns_xhr.post('/api/lord/getLordIntro', {}, this.drawRemote);
}

ns_dialog.dialogs.lord_intro_change.drawRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.lord_intro_change;
    dialog.cont_obj.lord_intro_text.value(ns_util.forbiddenWordCheck(_data['lord_intro']));
}

/* ************************************************** */
ns_button.buttons.lord_intro_change_close = new nsButtonSet('lord_intro_change_close', 'button_back', 'lord_intro_change', {base_class:ns_button.buttons.common_close});
ns_button.buttons.lord_intro_change_sub_close = new nsButtonSet('lord_intro_change_sub_close', 'button_full', 'lord_intro_change', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.lord_intro_change_close_all = new nsButtonSet('lord_intro_change_close_all', 'button_close_all', 'lord_intro_change', {base_class:ns_button.buttons.common_close_all});


ns_button.buttons.lord_intro_change = new nsButtonSet('lord_intro_change', 'button_default', 'lord_intro_change');
ns_button.buttons.lord_intro_change.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.lord_intro_change;
    let lord_intro = dialog.cont_obj.lord_intro_text.value();

    if (lord_intro.length > 200) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_lord_intro_length_alert', [lord_intro.length]));
        return false;
    }

    let post_data = { };
    post_data['lord_intro'] = lord_intro;

    ns_xhr.post('/api/lord/setLordIntro', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_dialog.setDataOpen('message', ns_i18n.t('msg_update_finish')); // 변경이 완료되었습니다.
        ns_dialog.dialogs.lord_info.draw();
        ns_dialog.close('lord_intro_change');
    }, { useProgress: true });
}

/*******************************************************************/
ns_dialog.dialogs.lord_info_card_change = new nsDialogSet('lord_info_card_change', 'dialog_full', 'size-full', {do_close_all:false});
ns_dialog.dialogs.lord_info_card_change.lord_object = [];
ns_dialog.dialogs.lord_info_card_change.selected_lord = null;

ns_dialog.dialogs.lord_info_card_change.cacheContents = function()
{
    for (let i of [1, 2, 3, 4, 5]) {
        this.lord_object.push(new nsObject(`.content_lord_change_select > .content_lord_change_face:nth-child(${i})`, this.obj)
            .setEvent(ns_engine.cfg.mouse_up_event_type, this.selectLord)
            .setEvent(ns_engine.cfg.mouse_enter_event_type, this.enterLord)
            .setEvent(ns_engine.cfg.mouse_leave_event_type, this.leaveLord));
    }
    this.cont_obj.lord_card_change_status = new nsObject('.content_lord_card_change_status', this.obj);
    this.cont_obj.lord_card_change_description = new nsObject('.content_lord_card_change_description', this.obj);
}

ns_dialog.dialogs.lord_info_card_change.enterLord = function (_e)
{
    let dialog = ns_dialog.dialogs.lord_info_card_change;
    let lord_number = _e.target.dataset.lord;
    dialog.cont_obj.lord_card_change_description.text(codeset.t('lord_description', lord_number));
    for (let i of [1, 2, 3, 4, 5]) {
        dialog.cont_obj.lord_card_change_status.removeCss(`lord_status_${i}`);
    }
    dialog.cont_obj.lord_card_change_status.addCss('lord_status_' + lord_number);
}

ns_dialog.dialogs.lord_info_card_change.leaveLord = function (_e)
{
    let dialog = ns_dialog.dialogs.lord_info_card_change;
    dialog.cont_obj.lord_card_change_description.text('');
    for (let i of [1, 2, 3, 4, 5]) {
        dialog.cont_obj.lord_card_change_status.removeCss(`lord_status_${i}`);
    }
    if (dialog.selected_lord) {
        dialog.cont_obj.lord_card_change_status.addCss('lord_status_' + dialog.selected_lord);
        dialog.cont_obj.lord_card_change_description.text(codeset.t('lord_description', dialog.selected_lord));
    }
}

ns_dialog.dialogs.lord_info_card_change.selectLord = function (_e)
{
    let dialog = ns_dialog.dialogs.lord_info_card_change;
    let lord_number = _e.target.dataset.lord;
    for (let obj of dialog.lord_object) {
        obj.removeCss('selected');
    }
    _e.target.classList.add('selected');

    dialog.selected_lord = lord_number;
}

ns_dialog.dialogs.lord_info_card_change.initLord = function (_lord_number)
{
    let dialog = ns_dialog.dialogs.lord_info_card_change;
    for (let obj of dialog.lord_object) {
        obj.removeCss('selected');
    }
    dialog.lord_object[_lord_number - 1].addCss('selected');
    dialog.cont_obj.lord_card_change_status.addCss('lord_status_' + _lord_number);
    dialog.cont_obj.lord_card_change_description.text(codeset.t('lord_description', _lord_number));
    dialog.selected_lord = _lord_number;
}

ns_dialog.dialogs.lord_info_card_change.draw = function()
{
    this.initLord(ns_cs.d.lord.lord_pic.v);
}

ns_dialog.dialogs.lord_info_card_change.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.lord_info_card_change.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.lord_info_card_change.timerHandlerReal = function()
{
    if (! ns_cs.d.lord?.['lord_card_up_dt']) {
        return false;
    }
    let lord_card_up_dt = ns_cs.d.lord?.['lord_card_up_dt'].v ?? 0;
    let limit_time = ns_util.math(lord_card_up_dt).plus(86400).number;
    if (ns_util.math(limit_time).lte(ns_timer.now())) {
        ns_button.buttons.lord_card_change_submit.setEnable();
        ns_button.buttons.lord_card_change_submit.obj.text(ns_i18n.t('lord_hero_change'));
    } else {
        ns_button.buttons.lord_card_change_submit.setDisable();
        ns_button.buttons.lord_card_change_submit.obj.text(ns_i18n.t('time_left', [ns_util.getCostsTime(ns_util.math(limit_time).minus(ns_timer.now()).number)]));
    }
}

/* ************************************************** */
ns_button.buttons.lord_info_card_change_close = new nsButtonSet('lord_info_card_change_close', 'button_back', 'lord_info_card_change', { base_class: ns_button.buttons.common_close });
ns_button.buttons.lord_info_card_change_sub_close = new nsButtonSet('lord_info_card_change_sub_close', 'button_full', 'lord_info_card_change', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.lord_info_card_change_close_all = new nsButtonSet('lord_info_card_change_close_all', 'button_close_all', 'lord_info_card_change', {base_class:ns_button.buttons.common_close_all});

ns_button.buttons.lord_card_change_submit = new nsButtonSet('lord_card_change_submit', 'button_special', 'lord_info_card_change');
ns_button.buttons.lord_card_change_submit.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.lord_info_card_change;
    let lord_info = ns_cs.d.hero[ns_cs.d.lord.lord_hero_pk.v];

    if (lord_info && lord_info.status_cmd !== 'I') {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_lord_card_change_idle_error')); // 군주가 대기 상태일때만 변경할 수 있습니다.
        return;
    }

    if (! dialog.selected_lord) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_lord_card_change_other_error')); // 다른 군주 카드를 선택해주세요.
        return;
    }

    ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_change_lord_card_confirm'), // 군주 카드를 변경하게되면 기존 군주카드의<br/>강화, 기술들은 모두 사라지게됩니다.<br/><br/>단, 군주 카드의 레벨은 유지됩니다.<br/><br/>군주 카드를 변경하시겠습니까?
        okFunc: () =>
        {
            let post_data = { };
            post_data['action'] = 'use_item';
            post_data['item_pk'] = '500096';
            post_data['card_type'] = dialog.selected_lord;

            ns_xhr.post('/api/item/use', post_data, function(_data, _status)
            {
                ns_dialog.close('lord_info_card_change');
                let remain_dt = 0;

                if (_data['ns_xhr_return']['code'] === 'error') {
                    if (_data['ns_xhr_return']['add_data']) {
                        remain_dt = _data['ns_xhr_return']['add_data'];
                    } else {
                        if(! ns_xhr.returnCheck(_data)) {
                            return;
                        }
                    }
                } else {
                    if (ns_xhr.returnCheck(_data)) {
                        _data = _data['ns_xhr_return']['add_data'];
                    }
                }

                if(remain_dt < 86400 && remain_dt > 0) {
                    ns_dialog.setDataOpen('message', ns_i18n.t('msg_change_lord_card_remain', [ns_util.getCostsTime(ns_util.math(86400).minus(remain_dt).number)]));
                } else {
                    if (ns_cs.d.hero[_data.prev_lord_hero_pk]) {
                        delete ns_cs.d.hero[_data.prev_lord_hero_pk];
                    } else {
                        delete ns_cs.d.hero[_data.lord_hero_pk];
                    }

                    // 새군주 카드 등록
                    // ns_cs.d.hero[_data.lord_hero_pk] = _data.lord_info;

                    // 메인 군주 얼굴 아이콘 변경
                    for (let i of [1, 2, 3, 4, 5]) {
                        ns_cs.d.lord.s.lord_face.removeCss(`lord_face_${i}`);
                    }
                    ns_cs.d.lord.s.lord_face.addCss('lord_face_' + _data.lord_pic);

                    // 메인 군주 pic 번호 변경
                    ns_cs.d.lord.lord_pic.v = ns_util.toInteger(_data.lord_pic);
                    if (ns_dialog.dialogs.lord_info.visible) {
                        ns_dialog.dialogs.lord_info.cont_obj.lord_info_table.find('.lord_info_card').text(code_set.lord_name[ns_cs.d.lord.lord_pic.v]);
                    }

                    ns_dialog.setDataOpen('message', ns_i18n.t('msg_change_lord_card_complete')); // 군주 카드가 변경되었습니다.
                }
            }, { useProgress: true });
        }
    });
}
/*******************************************************************/
ns_dialog.dialogs.lord_info_lord_name_change = new nsDialogSet('lord_info_lord_name_change', 'dialog_pop', 'size-medium', { do_close_all: false });

ns_dialog.dialogs.lord_info_lord_name_change.cacheContents = function()
{
    this.cont_obj.lord_name = new nsObject('.lord_name', this.obj);
    this.cont_obj.change_lord_name = new nsObject('input[name=change_lord_name]', this.obj);
}

ns_dialog.dialogs.lord_info_lord_name_change.draw = function()
{
    this.cont_obj.lord_name.text(ns_cs.d.lord.lord_name.v);
    this.cont_obj.change_lord_name.value('');
}

ns_dialog.dialogs.lord_info_lord_name_change.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.lord_info_lord_name_change.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.lord_info_lord_name_change.timerHandlerReal = function()
{
    if (! ns_cs.d.lord?.['lord_name_up_dt']) {
        return false;
    }
    let lord_name_up_dt = ns_cs.d.lord?.['lord_name_up_dt'].v ?? 0;
    let limit_time = ns_util.math(lord_name_up_dt).plus(604800).number;
    if (ns_util.math(limit_time).lte(ns_timer.now())) {
        ns_button.buttons.change_lord_name.setEnable();
        ns_button.buttons.change_lord_name.obj.text(ns_i18n.t('to_change')); // 변경하기
    } else {
        ns_button.buttons.change_lord_name.setDisable();
        ns_button.buttons.change_lord_name.obj.text(ns_i18n.t('time_left', [ns_util.getCostsTime(ns_util.math(limit_time).minus(ns_timer.now()).number)]));
    }
}

/* ************************************************** */
ns_button.buttons.lord_info_lord_name_change_close = new nsButtonSet('lord_info_lord_name_change_close', 'button_pop_close', 'lord_info_lord_name_change', {base_class:ns_button.buttons.common_close});
ns_button.buttons.lord_info_lord_name_change_close_sub = new nsButtonSet('lord_info_lord_name_change_close_sub', 'button_full', 'lord_info_lord_name_change');
ns_button.buttons.lord_info_lord_name_change_close_sub.mouseUp = function()
{
    ns_dialog.close('lord_info_lord_name_change');
}

ns_button.buttons.change_lord_name = new nsButtonSet('change_lord_name', 'button_default', 'lord_info_lord_name_change');
ns_button.buttons.change_lord_name.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.lord_info_lord_name_change;
    let change_lord_name = dialog.cont_obj.change_lord_name.value();

    if (change_lord_name === ns_cs.d.lord.lord_name.v) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_change_lord_name_same')); // 현재 군주명과 다른 군주명을 입력해주십시오.
        return;
    }

    if (change_lord_name.length < 1) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_change_lord_name_empty')); // 변경할 군주명을 입력해 주십시오.
        return;
    } else if (change_lord_name.length < 2) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_change_lord_name_min')); // 군주명은 최소 2글자를 사용해야합니다.
        return;
    } else if (change_lord_name.length > 6) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_change_lord_name_max')); // 군주명은 최대 6글자까지 사용할 수 있습니다.
        return;
    }

    let post_data = {};
    post_data['action'] = 'use_item';
    post_data['item_pk'] = '500014';
    post_data['lord_name'] = change_lord_name;
    ns_xhr.post('/api/item/use', post_data, function(_data, _status)
    {
        let remain_dt = 0;

        if (_data['ns_xhr_return']['code'] === 'error') {
            if (_data['ns_xhr_return']['add_data']) {
                remain_dt = _data['ns_xhr_return']['add_data'];
            } else {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
            }
        } else {
            if (ns_xhr.returnCheck(_data)) {
                _data = _data['ns_xhr_return']['add_data'];
            }
        }

        if(remain_dt < 604800 && remain_dt > 0) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_change_lord_name_remain', [ns_util.getCostsTime(ns_util.math(604800).minus(remain_dt).number)]));
            ns_dialog.close('lord_info_lord_name_change');
        } else {
            if ( _data['ns_xhr_return']?.code === 'error'){
                ns_dialog.setDataOpen('message', _data['ns_xhr_return'].message);
            } else {
                ns_dialog.setDataOpen('message', ns_i18n.t('msg_change_lord_name_complete', [_data.lord_name]));
                ns_dialog.close('lord_info_lord_name_change');
            }
        }

        // ns_dialog.dialogs.lord_info.s.cont_title.text(changing_lordname);
    }, { useProgress: true });
}

/*******************************************************************/

ns_dialog.dialogs.alliance_intro_change = new nsDialogSet('alliance_intro_change', 'dialog_full', 'size-full', {do_close_all:false});

ns_dialog.dialogs.alliance_intro_change.cacheContents = function()
{
    this.cont_obj.alliance_intro_text = new nsObject('textarea[name=alliance_intro_text]', this.obj);
}

ns_dialog.dialogs.alliance_intro_change.draw = function()
{
    let dialog = ns_dialog.dialogs.alliance_intro_change;

    ns_xhr.post('/api/lord/getAllianceIntro', {}, this.drawRemote);
}

ns_dialog.dialogs.alliance_intro_change.drawRemote = function(_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.alliance_intro_change;
    dialog.cont_obj.alliance_intro_text.value(ns_util.forbiddenWordCheck(_data['alli_intro']));
}

/* ************************************************** */
ns_button.buttons.alliance_intro_change_close = new nsButtonSet('alliance_intro_change_close', 'button_back', 'alliance_intro_change', { base_class: ns_button.buttons.common_close });
ns_button.buttons.alliance_intro_change_sub_close = new nsButtonSet('alliance_intro_change_sub_close', 'button_full', 'alliance_intro_change', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.alliance_intro_change_close_all = new nsButtonSet('alliance_intro_change_close_all', 'button_close_all', 'alliance_intro_change', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.alliance_intro_change = new nsButtonSet('alliance_intro_change', 'button_default', 'alliance_intro_change');
ns_button.buttons.alliance_intro_change.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.alliance_intro_change;
    let alliance_intro = dialog.cont_obj.alliance_intro_text.value();

    if (alliance_intro.length > 200) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_alliance_intro_length_alert', [alliance_intro.length])); // 동맹 인사말은 200자 이내로 작성해야 합니다.<br/>사용한 글자수 : {{1}}
        return false;
    }

    let post_data = {};
    post_data['alli_intro'] = alliance_intro;

    ns_xhr.post('/api/lord/setAllianceIntro', post_data, function(_data, _status)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_dialog.setDataOpen('message', ns_i18n.t('msg_update_finish')); // 변경이 완료되었습니다.
        ns_dialog.dialogs.lord_info.draw();
        ns_dialog.close('alliance_intro_change');
    }, { useProgress: true });
}
