// 다이얼로그
ns_dialog.dialogs.connect = new nsDialogSet('connect', 'dialog_full', 'size-full', { do_open_animation: false, do_content_scroll: false, do_close_all: false });
ns_dialog.dialogs.connect.errorTimeoutHandle = null;
ns_dialog.dialogs.connect.retry_connect = 0;

ns_dialog.dialogs.connect.cacheContents = function()
{
    this.cont_obj.content_message = new nsObject('.content_message', this.obj);
    this.cont_obj.content_button_area = new nsObject('.content_button_area', this.obj);

    this.cont_obj.content_button_area.show();
}

ns_dialog.dialogs.connect.draw = function (_e) {
    this.cont_obj.content_message.text(ns_i18n.t('msg_server_connecting'));

    ns_button.buttons.connect_signup_guest.obj.hide();
    ns_button.buttons.connect_login.obj.hide();
    ns_button.buttons.connect_password_forgot.obj.hide();
    ns_button.buttons.connect_retry.obj.hide();

    // ns_button.buttons.hero_deck_list.obj.hide();
    // ns_button.buttons.main_npc_point.obj.hide();
    ns_button.buttons.connect_language_change.obj.hide(); // TODO 작업 진행 중인 사항으로 버튼을 숨겨둠.

    if (ns_auth.only_platform_mode === false && ns_auth.params.get('uuid')) {
        document.location.href = document.location.origin;
        return;
    }
    this.drawRemote();
}

ns_dialog.dialogs.connect.drawLocale = function ()
{
    let dialog = ns_dialog.dialogs.connect;

    ns_select_box.set('connect_language_change', ns_i18n.getLang());
    ns_button.buttons.connect_language_change.obj.text(ns_select_box.getText('connect_language_change'));

    ns_button.buttons.connect_signup_guest.obj.text(ns_i18n.t('guest_game_play'));
    ns_button.buttons.connect_login.obj.text(ns_i18n.t('login_and_membership'));
    ns_button.buttons.connect_password_forgot.obj.text(ns_i18n.t('find_the_password'));
    // ns_button.buttons.connect_inquiry.obj.text(ns_i18n.t('inquiry'));
    ns_button.buttons.connect_retry.obj.text(ns_i18n.t('retrying'));
}

ns_dialog.dialogs.connect.drawRemote = function ()
{
    let dialog = ns_dialog.dialogs.connect;

    ns_auth.status = null;
    ns_auth.id = null;

    const errorFunction = () =>
    {
        if (ns_engine.xhr.fg_xhr_use) {
            dialog.errorTimeoutHandle = setTimeout(errorFunction, 500);
            return;
        }

        dialog.cont_obj.content_message.text(ns_i18n.t('msg_error_server_communication'));

        ns_button.buttons.connect_retry.obj.show();
    }

    dialog.errorTimeoutHandle = setTimeout(errorFunction, 1000);

    ns_xhr.post('/api/auth/connect', ns_auth.getAll(), this.connected, {
        errorFunction: errorFunction
    }, { useProgress: true });
}

ns_dialog.dialogs.connect.connected = function (_data, _status)
{
    if(! ns_xhr.returnCheck(_data)) {
        return;
    }
    _data = _data['ns_xhr_return']['add_data'];

    let dialog = ns_dialog.dialogs.connect;

    if (dialog.errorTimeoutHandle) {
        clearTimeout(dialog.errorTimeoutHandle);
    }

    switch (_data.status) {
        case 'not_signup':
            // ns_dialog.open('qb_member_provision');
            if (ns_auth.only_guest_mode === true) {
                dialog.cont_obj.content_message.html(ns_i18n.t('msg_first_connect_with_guest'));
            } else {
                dialog.cont_obj.content_message.html(ns_i18n.t('msg_first_connect_with_member'));
            }
            ns_button.buttons.connect_signup_guest.obj.show();
            if (ns_auth.only_guest_mode !== true) {
                ns_button.buttons.connect_login.obj.show();
            }
            break;
        case 'signup':
            ns_auth.removeAuth();
            dialog.cont_obj.content_message.html(ns_i18n.t('msg_need_refresh_game'));
            ns_button.buttons.connect_login.obj.hide();
            ns_button.buttons.connect_password_forgot.obj.hide();
            break;
        case 'guest':
        case 'member':
            ns_auth.status = _data.status;
            ns_auth.id = _data.id;
            ns_dialog.open('server_select');
            ns_dialog.close('connect');
            break;
        case 'redraw':
            dialog.draw();
            break;
        case 'invalid':
            ns_auth.removeAuth();
            if (dialog.retry_connect > 5) {
                dialog.cont_obj.content_message.text(ns_i18n.t('msg_error_server_communication'));
                ns_button.buttons.connect_retry.obj.show();
            } else {
                setTimeout(() => {
                    dialog.draw();
                    dialog.retry_connect++;
                }, 3000);
            }
            break;
        default:
            dialog.cont_obj.content_message.text(ns_i18n.t('msg_error_server_communication'));
            ns_button.buttons.connect_retry.obj.show();
            break;
    }
    // dialog.drawLocale();
}

// 다이얼로그 버튼
ns_button.buttons.connect_signup_guest = new nsButtonSet('connect_signup_guest', 'button_connect_1', 'connect');
ns_button.buttons.connect_signup_guest.mouseUp = function (_e)
{
    ns_xhr.post('/api/auth/signupGuest', ns_auth.getAll(), function (_data, _status) {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        ns_dialog.dialogs.connect.draw();
    }, { useProgress: true });
}

ns_button.buttons.connect_login = new nsButtonSet('connect_login', 'button_connect_2', 'connect');
ns_button.buttons.connect_login.mouseUp = function (_e)
{
    ns_dialog.open('member_login');
}

ns_button.buttons.connect_password_forgot = new nsButtonSet('connect_password_forgot', 'button_connect_2', 'connect');
ns_button.buttons.connect_password_forgot.mouseUp = function (_e)
{
    // ns_dialog.open('ns_member_password_forgot');
}

ns_button.buttons.connect_inquiry = new nsButtonSet('connect_inquiry', 'button_connect_2', 'connect');
ns_button.buttons.connect_inquiry.mouseUp = function (_e)
{
    ns_engine.inquiry();
}

ns_button.buttons.connect_retry = new nsButtonSet('connect_retry', 'button_connect_2', 'connect');
ns_button.buttons.connect_retry.mouseUp = function (_e)
{
    ns_dialog.dialogs.connect.draw();
}

ns_button.buttons.connect_language_change = new nsButtonSet('connect_language_change', 'button_select_box', 'connect');
ns_button.buttons.connect_language_change.mouseUp = function ()
{
    ns_dialog.setDataOpen('select_box', { select_box_id: 'connect_language_change' });
}