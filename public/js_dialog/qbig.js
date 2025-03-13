// 큐빅 구매
ns_dialog.dialogs.qbig = new nsDialogSet('qbig', 'dialog_full', 'size-full', { do_close_all: false });
ns_dialog.dialogs.qbig.check_pack = null;

ns_dialog.dialogs.qbig.cacheContents = function ()
{
    this.cont_obj.qbig_pack_wrap = new nsObject('.qbig_pack_wrap', this.obj);
}

ns_dialog.dialogs.qbig.draw = function ()
{
    let pack_list = this.cont_obj.qbig_pack_wrap.findAll('.button_checkbox');
    for (let pack of pack_list) {
        let button_id = pack.element.id.replace(/^ns_button_/, '');
        // 버튼은 1회만 생성
        if (! ns_button.buttons[button_id]) {
            ns_button.buttons[button_id] = new nsButtonSet(button_id, null, 'qbig', { base_class: ns_button.buttons.qbig_pack_checkbox });
        } else {
            ns_button.buttons[button_id].unsetClicked();
        }
        if (! this.check_pack) {
            this.check_pack = button_id.replace(/^qbig_pack_/, '');
        }
    }
    if (this.check_pack) {
        ns_button.buttons[`qbig_pack_${this.check_pack}`].setClicked();
    }
}

ns_dialog.dialogs.qbig.erase = function ()
{
    this.data = null;
    this.check_pack = null;
}

ns_button.buttons.qbig_close = new nsButtonSet('qbig_close', 'button_back', 'qbig', { base_class: ns_button.buttons.common_close });
ns_button.buttons.qbig_sub_close = new nsButtonSet('qbig_sub_close', 'button_full', 'qbig', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.qbig_close_all = new nsButtonSet('qbig_close_all', 'button_close_all', 'qbig', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.qbig_pack_checkbox = new nsButtonSet('qbig_pack_checkbox', null, 'qbig');
ns_button.buttons.qbig_pack_checkbox.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.qbig;
    let prod_id = this.tag_id.replace(/^qbig_pack_/, '');
    if (dialog.check_pack) {
        if (dialog.check_pack === prod_id) {
            return false;
        }
        ns_button.buttons[`qbig_pack_${dialog.check_pack}`].unsetClicked();
    }
    this.setClicked();
    dialog.check_pack = prod_id;
}