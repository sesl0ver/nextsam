ns_dialog.dialogs.chat = new nsDialogSet('chat', 'dialog_full', 'size-full', { do_content_scroll: false });
ns_dialog.dialogs.chat.prev_chat_message = '';
ns_dialog.dialogs.chat.current_tab = 'public';
ns_dialog.dialogs.chat.redraw = false;

ns_dialog.dialogs.chat.cacheContents = function()
{
    this.cont_obj.chat_message = new nsObject('input[name=chat_message]', this.obj);
    // 엔터키 이벤트 추가해주기
    if (ns_cs.d.lord.is_chat_blocked.v !== 'Y') {
        this.cont_obj.chat_message.setEvent('keyup', (_e) =>{
            if(_e.keyCode === 13 && this.cont_obj.chat_message.value().length > 0) {
                ns_button.buttons.chat_submit.mouseUp();
            }
        });
    }

    // ns_engine.game_object.main_top_chat_message

    this.cont_obj.chat_list_wrap = new nsObject('.chat_list_wrap', this.obj);
    this.cont_obj.chat_list_body = new nsObject('.chat_list_body', this.obj);

    // 스크롤 이벤트 주기
    this.scroll_handle = new nsScroll(this.cont_obj.chat_list_wrap.element, this.cont_obj.chat_list_body.element);

    setInterval(ns_chat.updateCount, 3000); // 3초에 한번씩 카운트
}

ns_dialog.dialogs.chat.draw = function()
{
    if (!this.visible) {
        ns_button.toggleGroupSingle(ns_button.buttons[`chat_tab_${this.current_tab}`]);
    }

    ns_button.buttons.chat_tab_whisper.setDisable();
    ns_button.buttons.chat_tab_alliance.setDisable();
    if (ns_cs.d.lord.alli_pk.v && ns_cs.d.lord.alli_pk.v !== 0) {
        ns_button.buttons.chat_tab_alliance.setEnable();
    }

    if (this.redraw === true) {
        ns_button.buttons.chat_tab_public.mouseUp();
        this.redraw = false
    }

    this.toggleChat();
};

ns_dialog.dialogs.chat.toggleChat = function()
{
    let dialog = ns_dialog.dialogs.chat;
    let tab = ns_button.toggleGroupValue('chat_tab')[0].split('_').pop();

    dialog.cont_obj.chat_list_body.removeCss(dialog.current_tab);
    dialog.cont_obj.chat_list_body.addCss(tab);

    setTimeout(() => {
        dialog.scrollCheck();
    }, 10);

    dialog.current_tab = tab;
};

ns_dialog.dialogs.chat.createMessageBox = function(room, message)
{
    let message_box = document.createElement('p');
    message_box.classList.add('message_box');
    message = ns_util.forbiddenWordCheck(message); // 비속어 필터링
    if (['alliance', 'notice', 'alert', 'notification', 'whisper', 'public'].includes(room)) {
        message_box.classList.add(room);
    }
    message_box.innerHTML = message;
    ns_chat.current_message_box = message_box.cloneNode(true);
    return message_box;
};

ns_dialog.dialogs.chat.receiveMessage = function(room, message)
{
    let dialog = ns_dialog.dialogs.chat;
    dialog.appendBox(dialog.createMessageBox(room, message));
};

ns_dialog.dialogs.chat.appendBox = function(box)
{
    let dialog = ns_dialog.dialogs.chat;
    dialog.cont_obj.chat_list_body.append(box);
    dialog.clearChatList();
    dialog.scrollCheck();
};

ns_dialog.dialogs.chat.scrollCheck = function()
{
    let dialog = ns_dialog.dialogs.chat;
    let scroll = ns_util.math(dialog.cont_obj.chat_list_wrap.element.offsetHeight).minus(dialog.cont_obj.chat_list_body.element.offsetHeight).number;
    dialog.scroll_handle.set(scroll - 20);
}

ns_dialog.dialogs.chat.warning = function(type)
{
    let dialog = ns_dialog.dialogs.chat;
    let message = '';

    if (['same', 'flood'].includes(type)) {
        message = ns_i18n.t(`msg_chat_${type}_message_warning`);
    } else if (['blocked'].includes(type)) {
        message = ns_i18n.t('msg_chat_block_notice', [moment(ns_cs.d.lord.chat_block_end_dt.v).tz('Asia/Seoul').format('YYYY년 MM월 DD일 HH시 mm분')]);
    }
    let box = dialog.createMessageBox('alert', message);
    dialog.appendBox(box);
};

ns_dialog.dialogs.chat.clearChatList = function()
{
    let dialog = ns_dialog.dialogs.chat;
    const total_line = 50;
    if (dialog.cont_obj.chat_list_body.count('.message_box') > total_line) {
        dialog.cont_obj.chat_list_body.find('.message_box:first-child').remove();
    }
};

/* ************************************************** */

ns_button.buttons.chat_close = new nsButtonSet('chat_close', 'button_back', 'chat', { base_class: ns_button.buttons.common_close });
ns_button.buttons.chat_sub_close = new nsButtonSet('chat_sub_close', 'button_full', 'chat', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.chat_close_all = new nsButtonSet('chat_close_all', 'button_close_all', 'chat', { base_class: ns_button.buttons.common_close_all });

/* ************************************************** */

ns_button.buttons.chat_tab_public = new nsButtonSet('chat_tab_public', 'button_tab', 'chat', {toggle_group: 'chat_tab'});
ns_button.buttons.chat_tab_public.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.chat;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    ns_dialog.dialogs.chat.toggleChat();
};

ns_button.buttons.chat_tab_alliance = new nsButtonSet('chat_tab_alliance', 'button_tab', 'chat', {toggle_group: 'chat_tab', base_class: ns_button.buttons.chat_tab_public});
ns_button.buttons.chat_tab_whisper = new nsButtonSet('chat_tab_whisper', 'button_tab', 'chat', {toggle_group: 'chat_tab', base_class: ns_button.buttons.chat_tab_public});
ns_button.buttons.chat_tab_notification = new nsButtonSet('chat_tab_notification', 'button_tab', 'chat', {toggle_group: 'chat_tab', base_class: ns_button.buttons.chat_tab_public});

ns_button.buttons.chat_submit = new nsButtonSet('chat_submit', 'button_middle_2', 'chat');
ns_button.buttons.chat_submit.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.chat;
    let room = (typeof dialog.current_tab === "string") ? dialog.current_tab : 'public';
    let message = dialog.cont_obj.chat_message.value();
    dialog.cont_obj.chat_message.value('');
    if (typeof message !== "string" || message === '') {
        return;
    }

    ns_chat.submitMessage(message, room);
    dialog.prev_chat_message = message;
};
