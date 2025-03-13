ns_dialog.dialogs.inquiry = new nsDialogSet('inquiry', 'dialog_full', 'size-full', {do_close_all: false});

ns_dialog.dialogs.inquiry.cacheContents = function ()
{
    this.cont_obj.inquiry_faq_wrap = new nsObject('.inquiry_faq_wrap', this.obj);
    this.cont_obj.inquiry_write_wrap = new nsObject('.inquiry_write_wrap', this.obj);
    this.cont_obj.inquiry_list_wrap = new nsObject('.inquiry_list_wrap', this.obj);
}

ns_dialog.dialogs.inquiry.draw = function ()
{
    ns_button.toggleGroupSingle(ns_button.buttons.inquiry_tab_faq);
    this.drawTab('faq'); // 첫 화면은 faq
}

ns_dialog.dialogs.inquiry.drawTab = function (_tab)
{
    let dialog = ns_dialog.dialogs.inquiry;
    for (let tab of ['faq', 'write', 'list']) {
        if (_tab !== tab) {
            dialog.cont_obj[`inquiry_${tab}_wrap`].hide();
        }
    }
    dialog.cont_obj[`inquiry_${_tab}_wrap`].show();
}

ns_button.buttons.inquiry_close = new nsButtonSet('inquiry_close', 'button_back', 'inquiry', {base_class: ns_button.buttons.common_close});
ns_button.buttons.inquiry_sub_close = new nsButtonSet('inquiry_sub_close', 'button_full', 'inquiry', {base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.inquiry_close_all = new nsButtonSet('inquiry_close_all', 'button_close_all', 'inquiry', {base_class: ns_button.buttons.common_close_all});

ns_button.buttons.inquiry_tab_faq = new nsButtonSet('inquiry_tab_faq', 'button_tab', 'inquiry', { toggle_group: 'inquiry_tab' });
ns_button.buttons.inquiry_tab_faq.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.inquiry;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    let tab = this.tag_id.split('_tab_').pop();
    dialog.drawTab(tab);
}
ns_button.buttons.inquiry_tab_write = new nsButtonSet('inquiry_tab_write', 'button_tab', 'inquiry', { base_class: ns_button.buttons.inquiry_tab_faq, toggle_group: 'inquiry_tab' });
ns_button.buttons.inquiry_tab_list = new nsButtonSet('inquiry_tab_list', 'button_tab', 'inquiry', { base_class: ns_button.buttons.inquiry_tab_faq, toggle_group: 'inquiry_tab' });