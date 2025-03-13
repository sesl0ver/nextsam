ns_dialog.dialogs.member_login = new nsDialogSet('member_login', 'dialog_full', 'size-full', { do_content_scroll: false, do_close_all: false });

ns_dialog.dialogs.member_login.cacheContents = function ()
{
    this.cont_obj.login_id = new nsObject('#login_id', this.obj);
    this.cont_obj.login_pw = new nsObject('#login_pw', this.obj);
}

ns_dialog.dialogs.member_login.draw = function ()
{
    this.cont_obj.login_id.value('');
    this.cont_obj.login_pw.value('');
}

ns_button.buttons.member_login_submit = new nsButtonSet('member_login_submit', 'button_special', 'member_login');
ns_button.buttons.member_login_submit.mouseUp = function (_e)
{
    let dialog = ns_dialog.dialogs.member_login;

    let id = dialog.cont_obj.login_id.value();
    let pw = dialog.cont_obj.login_pw.value();

    if (! id || ! pw) {
        ns_dialog.setDataOpen('message', '아이디 또는 패스워드를 입력해주세요.');
    }

    let post_data = {};
    post_data['id'] = id;
    post_data['pw'] = pw;
    post_data['platform'] = ns_engine.cfg.app_platform;

    ns_xhr.post('/api/member/login', post_data, (_data, _status) => {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_auth.setAuth(_data['lc'], _data['tk']);

        ns_dialog.closeAll(['connect']);
        ns_dialog.dialogs.connect.drawRemote();
    }, { useProgress: true });
}

ns_button.buttons.member_login_email_save = new nsButtonSet('member_login_email_save', 'button_special', 'member_login');
ns_button.buttons.member_login_email_save.mouseUp = function (_e)
{
}

ns_button.buttons.member_login_create = new nsButtonSet('member_login_create', 'button_special', 'member_login');
ns_button.buttons.member_login_create.mouseUp = function (_e)
{
    ns_dialog.open('member_create');
}

ns_dialog.dialogs.member_create = new nsDialogSet('member_create', 'dialog_full', 'size-full', { do_content_scroll: false, do_close_all: false });

ns_dialog.dialogs.member_create.cacheContents = function ()
{
    this.cont_obj.create_email = new nsObject('#create_email', this.obj);
    this.cont_obj.create_password = new nsObject('#create_password', this.obj);
    this.cont_obj.create_password_verify = new nsObject('#create_password_verify', this.obj);
}

ns_button.buttons.member_create_submit = new nsButtonSet('member_create_submit', 'button_special', 'member_create');
ns_button.buttons.member_create_submit.mouseUp = function (_e)
{
    let dialog = ns_dialog.dialogs.member_create;

    let email = dialog.cont_obj.create_email.value();
    let pw = dialog.cont_obj.create_password.value();
    let pw_verify = dialog.cont_obj.create_password_verify.value();

    if (email.length < 6) {
        ns_dialog.setDataOpen('message', '아이디를 입력하세요.');
        return;
    }

    if (pw.length < 1 || pw_verify.length < 1) {
        ns_dialog.setDataOpen('message', '비밀번호를 입력하세요.')
        return;
    }

    if (pw !== pw_verify) {
        ns_dialog.setDataOpen('message', '비밀번호가 일치하지 않습니다.')
        return;
    }

    let post_data = {};
    post_data['id'] = email;
    post_data['pw'] = pw;
    post_data['pw_verify'] = pw_verify;
    ns_xhr.post('/api/member/create', post_data, (_data, _status) => {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        ns_auth.setAuth(_data['lc'], _data['tk']);

        ns_dialog.closeAll(['connect']);
        ns_dialog.dialogs.connect.drawRemote();
    }, { useProgress: true });
}

ns_button.buttons.member_create_cancel = new nsButtonSet('member_create_cancel', 'button_special', 'member_create');
ns_button.buttons.member_create_cancel.mouseUp = function (_e)
{
    let dialog = ns_dialog.dialogs.member_create;

    dialog.cont_obj.create_email.value('');
    dialog.cont_obj.create_password.value('');
    dialog.cont_obj.create_password_verify.value('');

    ns_dialog.closeAll(['connect']);
    ns_dialog.dialogs.connect.drawRemote();
}