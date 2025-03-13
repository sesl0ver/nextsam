// 다이얼로그
ns_dialog.dialogs.lord_create = new nsDialogSet('lord_create', 'dialog_full', 'size-full', { do_content_scroll: false, do_close_all: false });
ns_dialog.dialogs.lord_create.lord_object = [];
ns_dialog.dialogs.lord_create.selected_lord = null;

ns_dialog.dialogs.lord_create.cacheContents = function (_e)
{
    for (let i of [1, 2, 3, 4, 5]) {
        this.lord_object.push(new nsObject(`.content_lord_create_select > .content_lord_create_face:nth-child(${i})`, this.obj)
            .setEvent(ns_engine.cfg.mouse_up_event_type, this.selectLord));
            // .setEvent(ns_engine.cfg.mouse_enter_event_type, this.enterLord)
            // .setEvent(ns_engine.cfg.mouse_leave_event_type, this.leaveLord));
    }
    this.cont_obj.lord_create_status = new nsObject('.content_lord_create_status', this.obj);
    this.cont_obj.lord_create_description_root = new nsObject('.content_lord_create_info', this.obj);
    this.cont_obj.lord_create_description = new nsObject('.content_lord_create_description', this.obj);
}

ns_dialog.dialogs.lord_create.draw = function()
{
    this.drawDescription(null);
}

ns_dialog.dialogs.lord_create.enterLord = function (_e)
{
    let dialog = ns_dialog.dialogs.lord_create;
    let lord_number = _e.target.dataset.lord;
    dialog.cont_obj.lord_create_description.text(codeset.t('lord_description', lord_number));
    for (let i of [1, 2, 3, 4, 5]) {
        dialog.cont_obj.lord_create_status.removeCss(`lord_status_${i}`);
    }
    dialog.cont_obj.lord_create_status.addCss('lord_status_' + lord_number);
}

ns_dialog.dialogs.lord_create.leaveLord = function (_e)
{
    let dialog = ns_dialog.dialogs.lord_create;
    let lord_number = dialog.selected_lord;
    dialog.cont_obj.lord_create_description.text('');
    dialog.drawDescription(lord_number);
}

ns_dialog.dialogs.lord_create.selectLord = function (_e)
{
    let dialog = ns_dialog.dialogs.lord_create;
    let lord_number = _e.target.dataset.lord;
    for (let obj of dialog.lord_object) {
        obj.removeCss('selected');
    }
    _e.target.classList.add('selected');

    dialog.cont_obj.lord_create_description_root.show();

    dialog.selected_lord = lord_number;
    dialog.drawDescription(lord_number);
}

ns_dialog.dialogs.lord_create.drawDescription = function (lord_number)
{
    let dialog = ns_dialog.dialogs.lord_create;
    for (let i of [1, 2, 3, 4, 5]) {
        dialog.cont_obj.lord_create_status.removeCss(`lord_status_${i}`);
    }
    if (lord_number) {
        dialog.cont_obj.lord_create_status.addCss('lord_status_' + lord_number);
        dialog.cont_obj.lord_create_description.text(codeset.t('lord_description', lord_number));
        dialog.cont_obj.lord_create_description_root.show();
    } else {
        dialog.cont_obj.lord_create_description_root.hide();
    }
}

// 다이얼로그 버튼
ns_button.buttons.lord_create_submit = new nsButtonSet('lord_create_submit', 'button_special', 'lord_create');
ns_button.buttons.lord_create_submit.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.lord_create;
    if (! dialog.selected_lord) {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_please_choose_lord'));
        return;
    }

    let post_data = ns_auth.getAll();
    post_data['lord_card'] = dialog.selected_lord;

    ns_xhr.post('/api/start/lordCreate', post_data, function (_data, _status) {
        if(!ns_xhr.returnCheck(_data)) {
            return;
        }
        ns_cs.startSession();
    }, { useProgress: true });
};
