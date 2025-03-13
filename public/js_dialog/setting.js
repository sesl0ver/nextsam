// 게임 설정
ns_dialog.dialogs.setting = new nsDialogSet('setting', 'dialog_full', 'size-full', { do_close_all: false });
ns_dialog.dialogs.setting.game_start_trigger = true;

ns_dialog.dialogs.setting.cacheContents = function ()
{
    this.cont_obj.sound_bgm_value = new nsObject('.sound_bgm_value', this.obj);
    this.cont_obj.sound_effect_value = new nsObject('.sound_effect_value', this.obj);
    this.cont_obj.sound_bgm_slide = new nsObject('input[name=sound_bgm_slide]', this.obj);
    this.cont_obj.sound_bgm_slide.setAttribute('min', 0);
    this.cont_obj.sound_bgm_slide.setAttribute('max', 100);
    this.cont_obj.sound_bgm_slide.setEvent('input', (_e) => {
        _e.preventDefault();
        this.cont_obj.sound_bgm_value.text(_e.target.value);
        for (let o of Object.values(ns_sound.sound_object)) {
            if (o.bgm === true) {
                o.audio.volume = Number(_e.target.value) * 0.01;
            }
        }
    });
    this.cont_obj.sound_effect_slide = new nsObject('input[name=sound_effect_slide]', this.obj);
    this.cont_obj.sound_effect_slide.setAttribute('min', 0);
    this.cont_obj.sound_effect_slide.setAttribute('max', 100);
    this.cont_obj.sound_effect_slide.setEvent('input', (_e) => {
        _e.preventDefault();
        this.cont_obj.sound_effect_value.text(_e.target.value);
        ns_sound.volume.effect = Number(_e.target.value) * 0.01;
    });
}

ns_dialog.dialogs.setting.draw = function ()
{
    this.get();
}

ns_dialog.dialogs.setting.erase = function ()
{
    this.data = null;
    ns_sound.update();
}

ns_dialog.dialogs.setting.get = function ()
{
    ns_xhr.post('/api/setting/get', {}, (_data, _status) => {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        this.updateSetting(_data);
    });
}

ns_dialog.dialogs.setting.updateSetting = function (_data)
{
    let dialog = ns_dialog.dialogs.setting;

    // TODO 작업 진행 중인 사항으로 버튼을 숨겨둠.
    ns_button.buttons.setting_language_change.obj.hide();
    ns_select_box.set('setting_language_change', ns_i18n.getLang());
    ns_button.buttons.setting_language_change.obj.text(ns_select_box.getText('setting_language_change'));

    let buttons = ['sound_bgm', 'sound_effect', 'building_title', 'counsel_action', 'counsel_connect'];
    for (let button of buttons) {
        if (_data[button] === 'N') {
            ns_button.buttons[`setting_${button}`].unsetClicked();
        } else {
            ns_button.buttons[`setting_${button}`].setClicked();
        }
    }

    dialog.cont_obj.sound_bgm_value.text(_data['volume_bgm']);
    dialog.cont_obj.sound_bgm_slide.value(_data['volume_bgm']);
    dialog.cont_obj.sound_effect_value.text(_data['volume_effect']);
    dialog.cont_obj.sound_effect_slide.value(_data['volume_effect']);

    ns_sound.update();
}

ns_button.buttons.setting_close = new nsButtonSet('setting_close', 'button_back', 'setting', { base_class: ns_button.buttons.common_close });
ns_button.buttons.setting_sub_close = new nsButtonSet('setting_sub_close', 'button_full', 'setting', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.setting_close_all = new nsButtonSet('setting_close_all', 'button_close_all', 'setting', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.setting_language_change = new nsButtonSet('setting_language_change', 'button_select_box', 'setting');
ns_button.buttons.setting_language_change.mouseUp = function ()
{
    ns_dialog.setDataOpen('select_box', { select_box_id: 'setting_language_change' });
}

ns_button.buttons.setting_sound_bgm = new nsButtonSet('setting_sound_bgm', 'button_toggle', 'setting');
ns_button.buttons.setting_sound_bgm.mouseUp = function ()
{
    if (this.clicked === true) {
        this.unsetClicked();
    } else {
        this.setClicked();
    }
}
ns_button.buttons.setting_sound_effect = new nsButtonSet('setting_sound_effect', 'button_toggle', 'setting', { base_class: ns_button.buttons.setting_sound_bgm });
ns_button.buttons.setting_counsel_connect = new nsButtonSet('setting_counsel_connect', 'button_toggle', 'setting', { base_class: ns_button.buttons.setting_sound_bgm });
ns_button.buttons.setting_counsel_action = new nsButtonSet('setting_counsel_action', 'button_toggle', 'setting', { base_class: ns_button.buttons.setting_sound_bgm });
ns_button.buttons.setting_building_title = new nsButtonSet('setting_building_title', 'button_toggle', 'setting', { base_class: ns_button.buttons.setting_sound_bgm });

ns_button.buttons.setting_submit = new nsButtonSet('setting_submit', 'button_special', 'setting');
ns_button.buttons.setting_submit.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.setting;

    let post_data = {};
    post_data['lang'] = ns_select_box.get('setting_language_change').val;
    post_data['volume_bgm'] = dialog.cont_obj.sound_bgm_slide.value();
    post_data['volume_effect'] = dialog.cont_obj.sound_effect_slide.value();
    let buttons = ['sound_bgm', 'sound_effect', 'building_title', 'counsel_action', 'counsel_connect'];
    for (let button of buttons) {
        post_data[button] = ns_button.buttons[`setting_${button}`].clicked === true ? 'Y' : 'N';
    }
    ns_xhr.post('/api/setting/set', post_data, (_data, _status) => {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        let data = _data['ns_xhr_return']['add_data'];
        if (data.restart === true) {
            let lang = ns_select_box.get('setting_language_change').val;
            ns_button.buttons.setting_language_change.obj.text(ns_select_box.getText('setting_language_change'));
            ns_i18n.setLang(lang);
            if (lang === 'none') {
                return;
            }
            ns_dialog.dialogs.message.close_game_over = true;
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_need_game_restart')); // 게임을 재시작해야 적용되는 변경사항이 있습니다.
            return;
        }
        dialog.updateSetting(data);
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_setting_save')); // 설정을 저장했습니다.
    }, { useProgress: true });
}

