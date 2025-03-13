// 다이얼로그
ns_dialog.dialogs.server_select = new nsDialogSet('server_select', 'dialog_full', 'size-full', { do_open_animation: false, do_content_scroll: false, do_close_all: false });
ns_dialog.dialogs.server_select.server_list = [];
ns_dialog.dialogs.server_select.server_pks = [];
ns_dialog.dialogs.server_select.select_server_pk = null;
ns_dialog.dialogs.server_select.delegate = null;
ns_dialog.dialogs.server_select.lord_name = null;
ns_dialog.dialogs.server_select.backup_cfg = null;

ns_dialog.dialogs.server_select.cacheContents = function()
{
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);

    this.cont_obj.server_objects = {};
    this.cont_obj.content_server_bg = new nsObject('.content_server_bg', this.obj);
    this.cont_obj.content_server = new nsObject('.content_server', this.obj);
    this.cont_obj.content_server_lord = new nsObject('.content_server_lord', this.obj);

    this.cont_obj.content_member_id = new nsObject('.content_member_id', this.obj);
    this.cont_obj.content_delegate = new nsObject('.content_delegate', this.obj);

    // 대표서버가 없다면
    this.delegate = window.localStorage.getItem('delegate');
    this.select_server_pk = (! this.delegate) ? 's1' : this.delegate;

    this.backup_cfg = ns_engine.cfg.xhr_url_prefix; // ?
}

ns_dialog.dialogs.server_select.draw = function (_e) {
    this.cont_obj.content_server_bg.hide();
    this.cont_obj.content.hide();

    // 게임 접속 전 유저에게 필요한 사항들 정리
    let params = {};
    window.location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(str, key, value) { params[key] = value; });
    ns_auth.token = params.token;
    ns_auth.anonymous = params.anonymous;

    /*if (window.location.href.indexOf('auto=true') !== -1) {
        start_session(this.sspk);
        return false;
    }*/

    switch (ns_auth.status) {
        case 'member':
            ns_button.buttons.server_select_login.obj.hide();
            ns_button.buttons.server_select_modify.obj.hide();
            if (ns_auth.only_platform_mode === true) {
                ns_button.buttons.server_select_logout.obj.hide();
            } else {
                ns_button.buttons.server_select_logout.obj.show();
            }
            break;
        default:
            if (ns_auth.only_guest_mode === true) {
                ns_button.buttons.server_select_login.obj.hide();
            } else {
                ns_button.buttons.server_select_login.obj.show();
            }
            ns_button.buttons.server_select_logout.obj.hide();
            ns_button.buttons.server_select_modify.obj.hide();
            break;
    }

    ns_button.buttons.server_select_language_change.obj.hide(); // TODO 작업 진행 중인 사항으로 버튼을 숨겨둠.

    this.drawRemote();
}

ns_dialog.dialogs.server_select.drawLocale = function()
{
    let dialog = ns_dialog.dialogs.server_select;

    /*try {
        ns_select_box.set('server_select_language_change', ns_i18n.getLang());
        ns_button.buttons.server_select_language_change.obj.text(ns_select_box.getText('server_select_language_change'));
    } catch (e) {
        document.location.reload();
    }*/
    ns_button.buttons.server_select_game_start.obj.text(ns_i18n.t('game_on'));
    ns_button.buttons.server_select_login.obj.text(ns_i18n.t('login_and_membership'));
    ns_button.buttons.server_select_logout.obj.text(ns_i18n.t('logout'));
    ns_button.buttons.server_select_modify.obj.text(ns_i18n.t('member_modify'));
    // ns_button.buttons.server_select_inquiry.obj.text(ns_i18n.t('inquiry'));

    let id = ns_auth.id;
    switch (ns_auth.status) {
        case 'member':
            break;
        default:
            id = 'Guest' + id;
            break;
    }
    dialog.cont_obj.content_member_id.text(`${ns_i18n.t('logging_in')}`);
    if (dialog.select_server_pk === dialog.delegate) {
        dialog.cont_obj.content_delegate.text(`${ns_i18n.t('representative_server')} : ${codeset.t('server_name', dialog.select_server_pk)}`);
    } else {
        dialog.cont_obj.content_delegate.text(`${ns_i18n.t('representative_server')} : ${ns_i18n.t('none')}`);
    }
    dialog.cont_obj.content.show();
    if (ns_auth.only_platform_mode === true) {
        ns_button.buttons.server_select_game_start.obj.hide();
        // ns_button.buttons.server_select_inquiry.obj.hide();
        setTimeout(() => {
            ns_button.buttons.server_select_game_start.mouseUp();
        }, 2000);
    }
}


ns_dialog.dialogs.server_select.drawRemote = function(_select_server_pk)
{
    let post_data = ns_auth.getAll();
    if (_select_server_pk) {
        post_data['select_server_pk'] = _select_server_pk;
    } else if (this.select_server_pk) {
        post_data['select_server_pk'] = this.select_server_pk;
    }
    ns_engine.cfg.cmd_url_prefix = this.backup_cfg; // ?

    ns_xhr.get('/api/server/list', post_data, this.drawList);
}

ns_dialog.dialogs.server_select.drawList = function(_response)
{
    if (! ns_xhr.returnCheck(_response)) {
        return;
    }

    let data = _response['ns_xhr_return']['add_data'];
    let dialog = ns_dialog.dialogs.server_select;

    dialog.cont_obj.content_server_bg.show();

    ns_button.buttons.server_select_left.obj.hide();
    ns_button.buttons.server_select_right.obj.hide();
    ns_button.buttons.server_select_star.obj.hide();

    if (dialog.server_list.length < 1) {
        for (let _server of data.list) {
            dialog.server_list.push(_server);
            dialog.server_pks.push(_server['server_pk']);

            if (dialog.server_list.length === 1 && !dialog.delegate) {
                dialog.delegate = _server['server_pk'];
                window.localStorage.setItem('delegate', dialog.delegate);
            }

            let div = document.createElement('div');
            div.classList.add(`content_server_${_server['server_pk']}`);
            let flag = document.createElement('div');
            if (_server['flag_recommend'] === 'Y') {
                flag.classList.add('content_flag_recommend');
            } else if (_server['flag_new'] === 'Y') {
                flag.classList.add('content_flag_new');
            }
            div.appendChild(flag);
            dialog.cont_obj.content_server.append(div);
            dialog.cont_obj.server_objects[_server['server_pk']] = new nsObject(div);
            dialog.cont_obj.server_objects[_server['server_pk']].hide();
        }
    }
    if (dialog.server_list.length > 1) {
        ns_button.buttons.server_select_left.obj.show();
        ns_button.buttons.server_select_right.obj.show();
        ns_button.buttons.server_select_star.obj.show();
    }

    if (ns_auth.status === 'guest') {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_warning_guest_play'));
    }

    dialog.lord_name = (! data.lord) ? null : data.lord.lord_name;
    dialog.cont_obj.content_server_lord.text((! data.lord) ? ns_i18n.t('msg_new_lord_possible') : `${ns_i18n.t('lord_name')} : ${data.lord.lord_name}`);

    ns_engine.cfg.game_server_pk = data.select_server_pk;
    dialog.select_server_pk = data.select_server_pk;
    dialog.cont_obj.server_objects[data.select_server_pk].show();

    let i = dialog.server_pks.indexOf(dialog.select_server_pk);
    if (i === 0) {
        ns_button.buttons.server_select_left.setDisable();
        ns_button.buttons.server_select_right.setEnable();
    } else if (i === dialog.server_pks.length - 1) {
        ns_button.buttons.server_select_left.setEnable();
        ns_button.buttons.server_select_right.setDisable();
    } else {
        ns_button.buttons.server_select_left.setEnable();
        ns_button.buttons.server_select_right.setEnable();
    }

    if (dialog.select_server_pk === dialog.delegate) {
        ns_button.buttons.server_select_star.setClicked();
    } else {
        ns_button.buttons.server_select_star.unsetClicked();
    }
    dialog.drawLocale();
}

// 다이얼로그 버튼
ns_button.buttons.server_select_left = new nsButtonSet('server_select_left', 'button_arrow_left', 'server_select');
ns_button.buttons.server_select_left.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.server_select;
    let i = dialog.server_pks.indexOf(dialog.select_server_pk);
    if (i < 1) {
        return;
    }
    dialog.cont_obj.server_objects[dialog.select_server_pk].hide();
    let next_server_pk = dialog.server_pks[( i - 1 )];
    dialog.drawRemote(next_server_pk);
};

ns_button.buttons.server_select_right = new nsButtonSet('server_select_right', 'button_arrow_right', 'server_select');
ns_button.buttons.server_select_right.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.server_select;
    let i = dialog.server_pks.indexOf(dialog.select_server_pk);
    let j = dialog.server_pks.length - 1;
    if (i >= j) {
        return;
    }
    dialog.cont_obj.server_objects[dialog.select_server_pk].hide();
    let next_server_pk = dialog.server_pks[( i + 1 )];
    dialog.drawRemote(next_server_pk);
};

ns_button.buttons.server_select_star = new nsButtonSet('server_select_star', 'button_star', 'server_select');
ns_button.buttons.server_select_star.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.server_select;
    if (! dialog.lord_name) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_only_select_lord_recommend_server'));
        return;
    }
    dialog.delegate = dialog.select_server_pk;
    window.localStorage.setItem('delegate', dialog.delegate);
};

ns_button.buttons.server_select_game_start = new nsButtonSet('server_select_game_start', 'button_connect_1', 'server_select');
ns_button.buttons.server_select_game_start.mouseUp = function (_e)
{
    let dialog = ns_dialog.dialogs.server_select;
    if (dialog.server_list.length < 1) {
        dialog.drawRemote();
    }

    let i = dialog.server_pks.indexOf(dialog.select_server_pk);
    ns_engine.cfg.cmd_url_prefix = dialog.server_list[i].server_pre_url;
    ns_cs.startSession(dialog.select_server_pk);
}

ns_button.buttons.server_select_login = new nsButtonSet('server_select_login', 'button_connect_2', 'server_select');
ns_button.buttons.server_select_login.mouseUp = function (_e)
{
    ns_dialog.open('member_login');
}

ns_button.buttons.server_select_logout = new nsButtonSet('server_select_logout', 'button_connect_2', 'server_select');
ns_button.buttons.server_select_logout.mouseUp = function (_e)
{
    ns_auth.removeAuth();
    ns_dialog.closeAll();
    ns_dialog.open('connect');
}

ns_button.buttons.server_select_modify = new nsButtonSet('server_select_modify', 'button_connect_2', 'server_select');
ns_button.buttons.server_select_modify.mouseUp = function (_e)
{
}

ns_button.buttons.server_select_inquiry = new nsButtonSet('server_select_inquiry', 'button_connect_2', 'server_select');
ns_button.buttons.server_select_inquiry.mouseUp = function (_e)
{
    ns_engine.inquiry();
}

ns_button.buttons.server_select_language_change = new nsButtonSet('server_select_language_change', 'button_select_box', 'server_select');
ns_button.buttons.server_select_language_change.mouseUp = function ()
{
    ns_dialog.setDataOpen('select_box', { select_box_id: 'server_select_language_change' });
}
