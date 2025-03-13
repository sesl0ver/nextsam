// 다이얼로그
ns_dialog.dialogs.message = new nsDialogSet('message', 'dialog_pop', 'size-medium', {
    do_content_scroll: false,
    do_close_all: false
});
ns_dialog.dialogs.message.show_use_again_button = false;
ns_dialog.dialogs.message.close_game_over = false;

ns_dialog.dialogs.message.cacheContents = function () {
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);

    this.cont_obj.message_container = new nsObject('.message_container', this.obj);
    this.cont_obj.content_package_container = new nsObject('.content_package_container', this.obj);
    this.cont_obj.content_item_container = new nsObject('.content_item_container', this.obj);

    this.cont_obj.content_message = new nsObject('.content_message', this.obj);
    this.cont_obj.content_package = new nsObject('.content_package', this.obj);
    this.cont_obj.content_item = new nsObject('.content_item', this.obj);

    this.cont_obj.content_package_massage = new nsObject('.content_package_massage', this.obj);
    this.cont_obj.content_item_massage = new nsObject('.content_item_massage', this.obj);

    this.cont_obj.item_use_list_skeleton = new nsObject('#item_use_list_skeleton');
}

ns_dialog.dialogs.message.draw = function () {
    this.nextDraw(this.data);
}

ns_dialog.dialogs.message.nextDraw = function (_data) {
    let dialog = ns_dialog.dialogs.message;
    let message;

    dialog.cont_obj.content_message.hide();
    dialog.cont_obj.content_package.hide();
    dialog.cont_obj.content_item.hide();

    ns_button.buttons.message_close_item.obj.hide();
    ns_button.buttons.item_use_again.obj.hide();
    ns_button.buttons.package_use_again.obj.hide();

    if (typeof _data === 'string' || _data?.text) {
        dialog.cont_obj.content_pop_title.text(ns_i18n.t('information'));
        message = (_data && _data.text) ? _data.text : _data;
        if (message) {
            let span = document.createElement('span');
            span.innerHTML = message;
            dialog.cont_obj.message_container.empty().append(span);
        }
        dialog.cont_obj.content_message.show();
    } else if (_data?.package_type) {
        let m = ns_cs.m.item[_data.m_item_pk];
        let item_cnt = ns_cs.d.item[_data.m_item_pk]?.item_cnt ?? 0;

        let show_use_again_button = false;
        if (m.yn_use_again === 'Y' && _data.package_type !== 'hero') {
            if (m.yn_sell === 'Y' && ns_util.math(m.limit_buy).lt(1)) {
                // 캐시샵에서 파는 아이템이라면 그냥 보임
                show_use_again_button = true;
            } else {
                if (ns_util.math(item_cnt).gte(1)) {
                    // 캐시샵에서 파는 아이템이 아니면 수량이 남아있어야만 보임
                    show_use_again_button = true;
                }
            }
        }

        dialog.cont_obj.content_item_container.empty();
        dialog.cont_obj.content_package_container.empty();

        if (['army', 'item'].includes(_data.package_type)) {
            let _title = {item: ns_i18n.t('item_acquisition'), army: ns_i18n.t('army_increase')}
            dialog.cont_obj.content_pop_title.text(_title[_data.package_type]);

            let list = {};
            if (_data.package_type === 'army') {
                list = Object.entries(_data.army);
            } else if (_data.package_type === 'item') {
                list = Object.entries(_data.item);
            }
            for (let [k, d] of list) {
                dialog.cont_obj.content_package_container.append(dialog.generateBox(_data.package_type, k, d));
            }

            //dialog.cont_obj.content_package_massage.text(ns_cs.m.item[_data.m_item_pk].title);

            if (show_use_again_button) {
                ns_button.buttons.package_use_again.obj.show();
            }

            dialog.cont_obj.content_package.show();
        } else if (['skill_pocket', 'skill_box'].includes(_data.package_type)) {
            dialog.cont_obj.content_pop_title.text(ns_i18n.t('skill_acquisition')); // 기술 획득

            let skill_id = String(_data.m_skil_pk).substring(0, 4);
            let rare_type = ns_cs.m.hero_skil[_data.m_skil_pk].rare;
            let title = ns_i18n.t(`hero_skill_title_${skill_id}`) + ' Lv.' + ns_cs.m.hero_skil[_data.m_skil_pk].rare;

            dialog.cont_obj.content_item_container.append(dialog.generateBox('skill', skill_id, title, rare_type));

            let message = ns_i18n.t('msg_item_use_hero_skill_acquisition', [ns_i18n.t(`item_title_${_data.m_item_pk}`), title]);
            dialog.cont_obj.content_item_massage.text(message);

            if (show_use_again_button) {
                ns_button.buttons.item_use_again.obj.show();
            } else {
                ns_button.buttons.message_close_item.obj.show();
            }

            dialog.cont_obj.content_item.show();
        }
    } else if (_data?.m_item_pk) {
        let m = ns_cs.m.item[_data.m_item_pk];
        let item_cnt = ns_cs.d.item[_data.m_item_pk]?.item_cnt ?? 0;

        let show_use_again_button = false;
        if (m.yn_use_again === 'Y') {
            if (m.yn_sell === 'Y') { // 캐시샵에서 파는 아이템이라면 그냥 보임
                show_use_again_button = true;
            } else {
                if (ns_util.math(item_cnt).gte(1)) {  // 캐시샵에서 파는 아이템이 아니면 수량이 남아있어야만 보임
                    show_use_again_button = true;
                }
            }
        }

        if (!_data?.acquire && show_use_again_button) {
            ns_button.buttons.item_use_again.obj.show();
        } else {
            ns_button.buttons.message_close_item.obj.show();
        }

        dialog.cont_obj.content_pop_title.text((_data?.acquire) ? ns_i18n.t('item_acquisition') : ns_i18n.t('apply_items'));

        dialog.cont_obj.content_item_container.empty();

        dialog.cont_obj.content_item_container.append(dialog.generateBox('item', _data.m_item_pk, ns_i18n.t(`item_title_${_data.m_item_pk}`)));

        dialog.cont_obj.content_item_massage.text((_data?.acquire) ? ns_i18n.t('msg_item_received', [ns_i18n.t(`item_title_${_data.m_item_pk}`)]) : ns_i18n.t('msg_item_use_apply', [ns_i18n.t(`item_title_${_data.m_item_pk}`)]));

        dialog.cont_obj.content_item.show();
    } else {
        console.log('처리 불가. (확인 필요)', _data);
    }
}

ns_dialog.dialogs.message.generateBox = function (_type, _m_pk, _title, _rare_type = null) {
    let dialog = ns_dialog.dialogs.message;
    let _class = {item: 'item_image_', army: 'army_image_', skill: 'hero_skill_'}

    let skeleton = dialog.cont_obj.item_use_list_skeleton.clone();

    if (_type === 'item') {
        skeleton.find('.item_use_image').addCss(`${_class[_type]}${_m_pk}`);
        skeleton.find('.item_use_title').text(ns_i18n.t(`item_title_${_m_pk}`));
        skeleton.find('.item_use_count').text(_title.item_count);
    } else if (_type === 'army') {
        skeleton.find('.item_use_image').addCss(`${_class[_type]}${ns_cs.m.army[_m_pk].code}`);
        skeleton.find('.item_use_title').text(ns_i18n.t(`army_title_${_m_pk}`));
        skeleton.find('.item_use_count').text(_title);
    } else if (_type === 'skill') {
        skeleton.find('.item_use_image').addCss(`${_class[_type]}${_m_pk}`);
        skeleton.find('.item_use_title').text(_title);
        skeleton.find('.item_use_count').text('');
    }

    if (_rare_type) {
        skeleton.find('.item_use_rare_type').addCss(`hero_skill_rare${_rare_type}`);
    }
    return skeleton;
}

ns_button.buttons.message_close = new nsButtonSet('message_close', 'button_pop_close', 'message');
ns_button.buttons.message_close.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.message;
    dialog.data = null;
    if (dialog.close_game_over) {
        document.location.reload();
    } else {
        ns_dialog.close('message');
    }
}

ns_button.buttons.message_content_close = new nsButtonSet('message_content_close', 'button_pop_normal', 'message');
ns_button.buttons.message_content_close.mouseUp = function (_e) {
    ns_button.buttons.message_close.mouseUp();
}

ns_button.buttons.message_sub_close = new nsButtonSet('message_sub_close', 'button_full', 'message');
ns_button.buttons.message_sub_close.mouseUp = function (_e) {
    ns_button.buttons.message_close.mouseUp();
}
// ns_button.buttons.message_close_item = new nsButtonSet('message_close_item', 'button_pop_normal', 'message', { base_class: ns_button.buttons.message_close_package });

ns_button.buttons.message_close_item = new nsButtonSet('message_close_item', 'button_pop_normal', 'message');
ns_button.buttons.message_close_item.mouseUp = function (_e) {
    ns_button.buttons.message_close.mouseUp();
}

ns_button.buttons.item_use_again = new nsButtonSet('item_use_again', 'button_pop_normal', 'message');
ns_button.buttons.item_use_again.mouseUp = function (_e) {
    let m_item_pk = ns_dialog.dialogs.message.data.m_item_pk;
    ns_dialog.setDataOpen('item_use', {m_item_pk: m_item_pk});
    if (ns_cs.m.item[m_item_pk].yn_use_duplication_item !== 'Y') {
        ns_button.buttons.item_use_ok.mouseUp(_e); // 여러개 사용이 아닌 경우에만 바로 사용
    }
    ns_dialog.close('message');
}

ns_button.buttons.package_use_again = new nsButtonSet('package_use_again', 'button_pop_normal', 'message', {base_class: ns_button.buttons.item_use_again});

/* message_mt */
ns_dialog.dialogs.message_mt = new nsDialogSet('message_mt', 'dialog_pop', 'size-medium', {
    do_content_scroll: false,
    do_close_all: false
});

ns_dialog.dialogs.message_mt.draw = function () {
    if (ns_engine.handler.lp_timer_handle) {
        clearInterval(ns_engine.handler.lp_timer_handle);
        ns_engine.handler.lp_timer_handle = null;
    }
    ns_sound.play('popup');
}

ns_button.buttons.message_mt_ok = new nsButtonSet('message_mt_ok', 'button_pop_normal', 'message_mt');
ns_button.buttons.message_mt_ok.mouseUp = function (_e) {
    try {
        close();
    } catch (_e) {
        console.error(_e);
    }
}

/* message_update */
ns_dialog.dialogs.message_update = new nsDialogSet('message_update', 'dialog_pop', 'smallpop', {
    do_content_scroll: false,
    do_close_all: false
});

ns_dialog.dialogs.message_update.draw = function () {
    if (ns_engine.cfg.lp_timer_handle) {
        clearInterval(ns_engine.cfg.lp_timer_handle);
        ns_engine.cfg.lp_timer_handle = null;
    }

    // let desc = '서버 버전 : v' + this.data.version;
    // desc += ', 클라이언트 버전 : v' + ns_web_version;

    // this.cont_obj.find('.cont_version_desc').html(desc);

    ns_sound.play('popup');
}

ns_button.buttons.message_update_ok = new nsButtonSet('message_update_ok', 'button_pop_normal', 'message_update');
ns_button.buttons.message_update_ok.mouseUp = function (_e) {
    try {
        if (ns_engine.cfg.wrapping && window.localStorage) {
            let z = window.localStorage.getItem('assets_www');
            if (z) {
                document.location.href = z;
                return;
            }
        }
        document.location.reload();
    } catch (_e) {
        console.error(_e);
    }
}

/* message_charge */
ns_dialog.dialogs.message_charge = new nsDialogSet('message_charge', 'dialog_pop', 'smallpop', {
    do_content_scroll: false,
    do_close_all: false
});

ns_dialog.dialogs.message_charge.draw = function () {
    $('#wrap_trans').show();

    ns_sound.play('popup');
    this.customShow();
}

/* ********** */

ns_dialog.dialogs.message_charge.erase = function () {
    $('#wrap_trans').hide();

    this.customHide();
}

ns_button.buttons.message_charge_ok = new nsButtonSet('message_charge_ok', 'button_pop_normal', 'message_charge');
ns_button.buttons.message_charge_ok.mouseUp = function (_e) {
    ns_dialog.close('message_charge');
}

/* select_box */
ns_dialog.dialogs.select_box = new nsDialogSet('select_box', 'dialog_select_box', 'size-small', {do_close_all: false});
ns_dialog.dialogs.select_box.cacheContents = function (_e) {
    // this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.select_box_list_wrap = new nsObject('.select_box_list_wrap', this.obj);

    this.cont_obj.hr_division_brown = new nsObject('#hr_division_brown_skeleton');
}

ns_dialog.dialogs.select_box.draw = function (_e) {
    let dialog = ns_dialog.dialogs.select_box;
    let select_box = ns_select_box.boxs[this.data.select_box_id];
    if (!select_box) {
        this.close();
        return;
    }

    let rect_id = (this.data.button_id) ? this.data.button_id : this.data.select_box_id;
    let rect = ns_button.buttons[rect_id].obj.element.getBoundingClientRect();
    let main_stage = ns_engine.game_object.main_stage.element.getBoundingClientRect();
    let wrap = this.obj.querySelector('.dialog_wrap');
    wrap.style.top = (rect.y + rect.height) + 'px';
    wrap.style.left = (rect.x - main_stage.x) + 'px';
    wrap.style.width = rect.width + 'px';

    if (this.data === 'hero_manage_territory') {
        /*let dlg_this = this;
        let post_data = 'action=list';
        qbw_cmd('/a/position.php', post_data, function(data, status)
        {
            if (!qbw_cmd_return_check(data))
                return;
            $.each(data.qbw_cmd_return.add_data, function(k, d){
                select_box.data[d.posi_pk] = d.title;
            });

            dlg_this.obj.find('.cont_pop_title').text(select_box.title);

            let list_wrap = dlg_this.cont_obj.find('.select_box_list_wrap');
            list_wrap.hide();
            list_wrap.empty();

            if (!select_box.use_sort)
                list_wrap.append('<div class="hr_division_brown"></div>');

            $.each(select_box.data, function(k, d)
            {
                let obj = $('<div class="cont_select_box_list_item"></div>');

                if (select_box.use_sort)
                    obj = $('<div class="cont_select_box_list_sort_item"></div>'); // sort로 변경

                if (select_box.val == k)
                    d = '<strong>' + d + '</strong>';

                if (select_box.use_sort)
                    obj.append('<span class="cont_selebox_list_title">' + d + '</span>');
                else
                    obj.append('<span>' + d + '</span><div class="hr_division_brown"></div>');

                let dialog = ns_dialog.dialogs.select_box;
                let key = k;
                qbw_btn.newButtonEvent(obj[0], function(){
                    qbw_select_box.set(dialog.data, key);
                    select_box.func();
                    dialog.close();
                });

                list_wrap.append(obj);
            });

            list_wrap.show();

            dlg_this.customShow();
        }, _e);*/
    } else {

        // this.cont_obj.content_pop_title.text(select_box.title);

        /* TODO use_sort 사용안함
        this.cont_obj.select_box_list_wrap.empty().removeCss('use_sort');

        if (! select_box.use_sort) {
            this.cont_obj.select_box_list_wrap.append(this.cont_obj.hr_division_brown.clone());
        } else {
            this.cont_obj.select_box_list_wrap.addCss('use_sort');
        }*/
        this.cont_obj.select_box_list_wrap.append(this.cont_obj.hr_division_brown.clone());

        let i = 0;
        for (let [k, d] of Object.entries(select_box.data)) {
            let _item = document.createElement('div');
            _item.classList.add((!select_box.use_sort) ? 'select_box_list_item' : 'select_box_list_sort_item');
            if (select_box.val === k) {
                d = `<strong>${d}</strong>`;
            }

            let span = document.createElement('span');
            span.innerHTML = `${d}`;

            span.innerHTML = `${d}`;
            span.setAttribute('id', `ns_button_select_box_item_${k}`);
            span.dataset.key = k;
            _item.appendChild(span);
            _item.appendChild(this.cont_obj.hr_division_brown.clone().element);

            /* TODO use_sort 사용안함.
            if (select_box.use_sort) {
                span.classList.add('select_box_list_title');
                _item.appendChild(span);

                let button_down = document.createElement('span');
                button_down.setAttribute('id', `ns_button_select_box_arrow_down_${k}`);
                _item.appendChild(button_down);

                let button_up = document.createElement('span');
                button_up.setAttribute('id', `ns_button_select_box_arrow_up_${k}`);
                _item.appendChild(button_up);
            } else {
                span.innerHTML = `${d}`;
                span.setAttribute('id', `ns_button_select_box_item_${k}`);
                span.dataset.key = k;
                _item.appendChild(span);
                _item.appendChild(this.cont_obj.hr_division_brown.clone().element);
            }*/

            this.cont_obj.select_box_list_wrap.append(_item);
            i++;
        }
        // 버튼 설정
        for (let _key of Object.keys(select_box.data)) {
            ns_button.buttons[`select_box_item_${_key}`] = new nsButtonSet(`select_box_item_${_key}`, 'button_box_list_item', 'select_box_item');
            ns_button.buttons[`select_box_item_${_key}`].mouseUp = function (_e) {
                ns_select_box.set(dialog.data.select_box_id, _key, (select_box.reverse_sort.includes(_key) ? 'asc' : 'desc'));
                select_box.func();
                dialog.close();
            }
        }

        /* TODO use_sort 사용안함.
        if (! select_box.use_sort) {
            for (let _key of Object.keys(select_box.data)) {
                ns_button.buttons[`select_box_item_${_key}`] = new nsButtonSet(`select_box_item_${_key}`, 'button_box_list_item', 'select_box_item');
                ns_button.buttons[`select_box_item_${_key}`].mouseUp = function (_e)
                {
                    ns_select_box.set(dialog.data, _key);
                    select_box.func();
                    dialog.close();
                }
            }
        } else {
            for (let _key of Object.keys(select_box.data)) {
                ns_button.buttons[`select_box_arrow_down_${_key}`] = new nsButtonSet(`select_box_arrow_down_${_key}`, 'button_arrow_down', 'select_box_item');
                ns_button.buttons[`select_box_arrow_down_${_key}`].mouseUp = function (_e)
                {
                    ns_select_box.set(dialog.data, _key, 'desc');
                    select_box.func();
                    dialog.close();
                }

                if (select_box.val === _key && select_box.sort === 'desc') {
                    ns_button.buttons[`select_box_arrow_down_${_key}`].setDisable();
                }
                ns_button.buttons[`select_box_arrow_up_${_key}`] = new nsButtonSet(`select_box_arrow_up_${_key}`, 'button_arrow_up', 'select_box_item');
                ns_button.buttons[`select_box_arrow_up_${_key}`].mouseUp = function (_e)
                {
                    ns_select_box.set(dialog.data, _key, 'asc');
                    select_box.func();
                    dialog.close();
                }
                if (select_box.val === _key && select_box.sort === 'asc') {
                    ns_button.buttons[`select_box_arrow_up_${_key}`].setDisable();
                }
            }
        }*/
    }
};

ns_dialog.dialogs.select_box.erase = function () {
    // $('#wrap_trans').hide();

    // 리스트에서 생성하여 사용했었던 버튼 삭제
    let dialog = ns_dialog.dialogs.select_box;
    dialog.cont_obj.select_box_list_wrap.empty();
    let select_box = ns_select_box.boxs[this.data.select_box_id];
    if (!select_box.use_sort) {
        for (let _key of Object.keys(select_box.data)) {
            ns_button.buttons[`select_box_item_${_key}`]?.destroy();
        }
    } else {
        for (let _key of Object.keys(select_box.data)) {
            ns_button.buttons[`select_box_arrow_down_${_key}`]?.destroy();
            ns_button.buttons[`select_box_arrow_up_${_key}`]?.destroy();
        }
    }
};

/* ********** */

ns_button.buttons.select_box_close = new nsButtonSet('select_box_close', 'button_pop_close', 'select_box', {base_class: ns_button.buttons.common_close});
ns_button.buttons.select_box_sub_close = new nsButtonSet('select_box_sub_close', 'button_full', 'select_box');
ns_button.buttons.select_box_sub_close.mouseUp = function () {
    ns_dialog.close('select_box');
}


// confirm
ns_dialog.dialogs.confirm = new nsDialogSet('confirm', 'dialog_pop', 'size-small', {
    do_content_scroll: false,
    do_close_all: false
});
ns_dialog.dialogs.confirm.cacheContents = function () {
    this.cont_obj.message = new nsObject('.message', this.obj);
}

ns_dialog.dialogs.confirm.draw = function () {
    let message = (this.data && this.data?.text) ? this.data.text : this.data;
    if (message) {
        this.cont_obj.message.empty().html(message);
    }
    ns_sound.play('popup');
}

ns_button.buttons.confirm_ok = new nsButtonSet('confirm_ok', 'button_pop_normal_2', 'confirm');
ns_button.buttons.confirm_ok.mouseUp = function (_e) {
    let _data = ns_dialog.getData('confirm');
    ns_dialog.close('confirm');
    if (_data?.okFunc) {
        _data.okFunc();
    }
}

ns_button.buttons.confirm_no = new nsButtonSet('confirm_no', 'button_pop_normal', 'confirm');
ns_button.buttons.confirm_no.mouseUp = function (_e) {
    let _data = ns_dialog.getData('confirm');
    ns_dialog.close('confirm');
    if (_data?.noFunc) {
        _data.noFunc();
    }
}
ns_button.buttons.confirm_sub_close = new nsButtonSet('confirm_sub_close', 'button_full', 'confirm', {base_class: ns_button.buttons.confirm_no});

// keypad
ns_dialog.dialogs.keypad = new nsDialogSet('keypad', 'dialog_pop', 'size-keypad', {
    do_content_scroll: false,
    do_close_all: false
});
ns_dialog.dialogs.keypad.number_width = 85;
ns_dialog.dialogs.keypad.number_height = 51;
ns_dialog.dialogs.keypad.prev_number = '';
ns_dialog.dialogs.keypad.max = 100000000;
ns_dialog.dialogs.keypad.min = 0;
ns_dialog.dialogs.keypad.float_status = false;
ns_dialog.dialogs.keypad.float_length = 1;

ns_dialog.dialogs.keypad.cacheContents = function () {
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.content_pop_title.text(ns_i18n.t('enter'));

    this.cont_obj.content_input_number = new nsObject('.content_input_number', this.obj);
    this.cont_obj.develop_slider = new nsObject('input[name=develop_slider]', this.obj);
    this.cont_obj.develop_slider.setEvent('input', (_e) => {
        let number_value = _e.target.value;
        if (this.float_status === true) {
            number_value = ns_util.math(number_value).toFixed(this.float_length);
        }
        this.cont_obj.content_input_number.value(number_value);
    });

    this.cont_obj.content_input_number.setEvent('input', (_e) => {
        let regexp = /[^\d.]|\.(?=.*\.)/g;

        let current_value = _e.target.value;
        let minimum_value = this.cont_obj.develop_slider.element.min,
            maximum_value = this.cont_obj.develop_slider.element.max;

        current_value = (!ns_util.isNumeric(current_value)) ? minimum_value : current_value;
        let number_value = current_value.replace(regexp, "");
        number_value = Number(Math.max(Math.min(number_value, maximum_value), minimum_value));

        if (this.float_status === true) {
            number_value = ns_util.math(number_value).toFixed(this.float_length);
        }

        this.cont_obj.develop_slider.value(number_value);
        this.cont_obj.content_input_number.value(number_value);
    });

    this.cont_obj.max_amount_button = new nsObject('#ns_button_amount_max_count', this.obj);
}

ns_dialog.dialogs.keypad.draw = function () {
    this.prev_number = this.data?.current ?? '0';
    this.max = 100000000;
    this.min = 0;
    this.cont_obj.content_input_number.value(this.prev_number);
    this.float_status = false;
    this.float_length = 1;

    if (this.data) {
        if (ns_util.isNumeric(this.data.max)) {
            this.max = (!this.data.float_status) ? parseInt(this.data.max) : this.data.max;
        }

        if (ns_util.isNumeric(this.data.min)) {
            this.min = (!this.data.float_status) ? parseInt(this.data.min) : this.data.min;
        }

        if (this.data.float_status) {
            this.float_status = this.data.float_status;
        }

        this.cont_obj.develop_slider.setAttribute('min', this.min);
        this.cont_obj.develop_slider.setAttribute('max', this.max);
        this.cont_obj.develop_slider.setAttribute('step', (!this.data.float_status) ? 1 : 0.1);
        this.cont_obj.develop_slider.value(this.prev_number);
    }
    this.cont_obj.max_amount_button.text(this.max);
}

ns_button.buttons.keypad_close = new nsButtonSet('keypad_close', 'button_pop_close', 'keypad', {base_class: ns_button.buttons.common_close});
ns_button.buttons.keypad_sub_close = new nsButtonSet('keypad_sub_close', 'button_full', 'keypad', {base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.amount_max_count = new nsButtonSet('amount_max_count', 'button_middle_2', 'keypad');
ns_button.buttons.amount_max_count.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.keypad;
    let maximum_value = Number(dialog.cont_obj.develop_slider.element.max),
        minimum_value = Number(dialog.cont_obj.develop_slider.element.min);
    let current_value = Number(dialog.cont_obj.content_input_number.value());

    if (ns_util.math(maximum_value).eq(current_value) === true) {
        maximum_value = minimum_value;
    }

    dialog.cont_obj.develop_slider.value(maximum_value);
    dialog.cont_obj.content_input_number.value(maximum_value);
}

ns_button.buttons.amount_descrease = new nsButtonSet('amount_decrease', 'button_decrease', 'keypad');
ns_button.buttons.amount_descrease.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.keypad;
    let current_value = Number(dialog.cont_obj.develop_slider.value()),
        minimum_value = Number(dialog.cont_obj.develop_slider.element.min);

    if (dialog.float_status === true) {
        current_value = ns_util.math(current_value).minus(0.1).number;
        current_value = Math.max(current_value, minimum_value);
    } else {
        current_value = Math.max(--current_value, minimum_value);
    }

    if (this.float_status === true) {
        current_value = ns_util.math(current_value).toFixed(this.float_length);
    }

    dialog.cont_obj.develop_slider.value(current_value);
    dialog.cont_obj.content_input_number.value(current_value);
}

ns_button.buttons.amount_inscrease = new nsButtonSet('amount_increase', 'button_increase', 'keypad');
ns_button.buttons.amount_inscrease.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.keypad;
    let current_value = Number(dialog.cont_obj.develop_slider.value()),
        maximum_value = dialog.cont_obj.develop_slider.element.max;

    if (dialog.float_status === true) {
        current_value = ns_util.math(current_value).plus(0.1).number;
        current_value = Math.min(current_value, maximum_value);
    } else {
        current_value = Math.min(++current_value, maximum_value);
    }

    if (this.float_status === true) {
        current_value = ns_util.math(current_value).toFixed(this.float_length);
    }

    dialog.cont_obj.develop_slider.value(current_value);
    dialog.cont_obj.content_input_number.value(current_value);
}

ns_button.buttons.amount_submit = new nsButtonSet('amount_submit', 'button_pop_normal', 'keypad');
ns_button.buttons.amount_submit.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.keypad;
    let current_value = Number(dialog.cont_obj.develop_slider.value());

    if (dialog.data.callback) {
        dialog.data.callback(ns_util.math(current_value).number);
    }

    ns_dialog.close('keypad');
}

// development
ns_dialog.dialogs.development = new nsDialogSet('development', 'dialog_building', 'size-large', {do_close_all: false});
ns_dialog.dialogs.development.mode = 'army';
ns_dialog.dialogs.development.devel_amount = 0;
ns_dialog.dialogs.development.max_value = 0;
ns_dialog.dialogs.development.condition = {};

ns_dialog.dialogs.development.cacheContents = function () {
    this.cont_obj.content_title = new nsObject('.content_title', this.obj);

    this.cont_obj.develop_image = new nsObject('.develop_image', this.obj);
    this.cont_obj.develop_description = new nsObject('.develop_description', this.obj);

    this.cont_obj.content_build_time = new nsObject('.content_build_time', this.obj);

    this.cont_obj.develop_current = new nsObject('.develop_current', this.obj);

    this.cont_obj.develop_amount = new nsObject('.amount_field', this.obj);
    this.cont_obj.develop_slider = new nsObject('input[name=develop_slider]', this.obj);
    this.cont_obj.develop_slider.setEvent('input', (_e) => {
        this.cont_obj.develop_slider.value(_e.target.value);
        this.cont_obj.develop_amount.value(_e.target.value);

        this.armyBuildNumber(_e.target.value);
    });

    this.cont_obj.develop_amount.setEvent('input', (_e) => {
        let regexp = /[^\d.]|\.(?=.*\.)/g;
        let current_value = _e.target.value;

        let min = this.cont_obj.develop_slider.element.min, max = this.cont_obj.develop_slider.element.max;
        current_value = (!ns_util.isNumeric(current_value)) ? min : current_value;
        current_value = parseInt(current_value.replace(regexp, ""));
        current_value = (!current_value) ? min : current_value;

        current_value = Math.min(Math.max(min, current_value), max);

        this.cont_obj.develop_amount.value(current_value);
        this.cont_obj.develop_slider.value(current_value);
        this.armyBuildNumber(current_value);
    });

    this.cont_obj.content_development_table = new nsObject('.content_development_table', this.obj);
};

ns_dialog.dialogs.development.draw = function () {
    let data = this.data;
    let m = ns_cs.m.army[data.m_pk];
    this.condition = ns_cs.m.cond[m.m_cond_pk];
    let army_curr = 0;
    this.mode = data.title;

    let main_title = ns_i18n.t(`army_title_${data.m_pk}`), sub_title = '';
    if (this.mode === 'medi') {
        sub_title = 'army_cure';
        army_curr = ns_cs.d.army_medi[m.code].v;
        this.condition = ns_cs.m.cond[m.m_medi_cond_pk];
        let recruit_max = ns_util.toInteger(this.recruitMax());
        this.devel_amount = recruit_max > army_curr ? army_curr : recruit_max;
    } else {
        this.condition = ns_cs.m.cond[m.m_cond_pk];
        sub_title = 'army_training';
        army_curr = ns_cs.d.army[m.code].v;
        this.devel_amount = ns_util.toInteger(this.recruitMax());
    }

    let dialog = ns_dialog.dialogs.development;
    this.cont_obj.content_build_time.text("");
    this.cont_obj.develop_slider.element.min = 0;
    this.cont_obj.develop_slider.element.max = this.devel_amount;
    this.cont_obj.develop_slider.value(0);

    this.cont_obj.content_title.text(ns_i18n.t(sub_title, [main_title]));

    this.cont_obj.develop_image.addCss(`army_image_${this.data.type}`);
    this.cont_obj.develop_description.html(m.description_detail);

    this.cont_obj.develop_amount.value(0);

    this.cont_obj.develop_current.text(ns_util.numberFormat(army_curr));

    ns_button.buttons.development_updown.obj.html(ns_util.numberFormat(this.devel_amount));

    let tbody = this.cont_obj.content_development_table;
    tbody.empty();

    dialog.drawNowButton();

    let cond = this.condition;

    ns_button.buttons.development_submit_now.setDisable();
    if (!ns_check_condition.drawList(cond.m_cond_pk, this.data.castle_pk)) {
        ns_button.buttons.development_submit.setDisable();
    } else {
        ns_button.buttons.development_submit.setEnable();
        ns_button.buttons.development_submit_now.setEnable();
    }

    ns_check_condition.drawList(cond.m_cond_pk, this.data.castle_pk, tbody, true);
}

ns_dialog.dialogs.development.erase = function () {
    this.cont_obj.develop_image.removeCss(`army_image_${this.data.type}`);
};

ns_dialog.dialogs.development.armyBuildNumber = function (build_number) {
    let dialog = ns_dialog.dialogs.development;

    let m = ns_cs.m.army[dialog.data.m_pk];
    let cond = dialog.condition;
    let recruit_max = dialog.devel_amount;

    if (ns_util.math(recruit_max).lt(build_number)) {
        build_number = ns_util.toInteger(recruit_max);
        dialog.cont_obj.develop_amount.value(build_number);
    }

    //소요시간
    this.cont_obj.content_build_time.html(build_number <= 0 ? '0' : ns_util.getCostsTime(ns_util.math(cond.build_time).mul(build_number).integer));

    if (build_number <= 1) {
        build_number = 1;
    }

    dialog.drawNowButton();

    //조건 검사
    for (let _type of ['food', 'horse', 'lumber', 'iron', 'gold']) {
        if (cond[`build_${_type}`] && dialog.cont_obj.content.find(`.develop_build_${_type}`).element) {
            dialog.cont_obj.content.find(`.develop_build_${_type}`).text(ns_util.math(cond[`build_${_type}`]).mul(build_number).number_format);
        }
    }
    if (cond.need_population && dialog.cont_obj.content.find('.develop_build_population').element) {
        dialog.cont_obj.content.find('.develop_build_population').text(ns_util.math(cond.need_population).mul(build_number).number_format);
    }
};

ns_dialog.dialogs.development.implement = function () {
    let dialog = ns_dialog.dialogs.development;
    let data = dialog.data;

    // 필요한 데이터
    let in_cast_pk = data.castle_pk;
    let build_number = ns_util.toInteger(dialog.cont_obj.develop_amount.value());

    // 1 보다 작거나 숫자가 아닐때는 리턴
    if (build_number < 1 || !ns_util.isNumeric(build_number)) {
        return;
    }

    let post_data = {};
    post_data['in_cast_pk'] = in_cast_pk;
    post_data['code'] = data.type;
    post_data['build_number'] = build_number;

    if (dialog.mode === 'army') {
        ns_xhr.post('/api/army/upgrade', post_data, (_data, _status) => {
            if (!ns_xhr.returnCheck(_data)) {
                return;
            }
            ns_dialog.dialogs.build_Army.drawRemote();
            ns_dialog.close('development');
        }, { useProgress: true });
    } else if (dialog.mode === 'medi') {
        ns_xhr.post('/api/medical/treatment', post_data, (_data, _status) => {
            if (!ns_xhr.returnCheck(_data)) {
                return;
            }
            ns_dialog.dialogs.build_Medical.drawRemote();
            ns_dialog.close('development');
        }, { useProgress: true });
    }
};


ns_dialog.dialogs.development.recruitMax = function () {
    let dialog = ns_dialog.dialogs.development;
    let data = dialog.data;
    let bd = ns_cs.d.bdic[data.castle_pk];
    let level = ns_cs.m.buil[bd.m_buil_pk].level[bd.level];
    let m = ns_cs.m.army[data.m_pk], cond = dialog.condition;

    let recruit_max = (dialog.mode === 'medi') ? ns_cs.d.army_medi[m.code].v : ns_util.math(ns_cs.getTerritoryInfo('population_idle')).div(m.need_population).integer;

    if (cond.build_food && !ns_util.math(cond.build_food).eq(0)) {
        let food_max = ns_util.math(ns_cs.getResourceInfo('food_curr')).div(cond.build_food).integer;
        if (food_max < recruit_max) {
            recruit_max = food_max;
        }
    }

    if (cond.build_horse && !ns_util.math(cond.build_horse).eq(0)) {
        let horse_max = ns_util.math(ns_cs.getResourceInfo('horse_curr')).div(cond.build_horse).integer;
        if (horse_max < recruit_max) {
            recruit_max = horse_max;
        }
    }

    if (cond.build_lumber && !ns_util.math(cond.build_lumber).eq(0)) {
        let lumber_max = ns_util.math(ns_cs.getResourceInfo('lumber_curr')).div(cond.build_lumber).integer;
        if (lumber_max < recruit_max) {
            recruit_max = lumber_max;
        }
    }

    if (cond.build_iron && !ns_util.math(cond.build_iron).eq(0)) {
        let iron_max = ns_util.math(ns_cs.getResourceInfo('iron_curr')).div(cond.build_iron).integer;
        if (iron_max < recruit_max) {
            recruit_max = iron_max;
        }
    }

    if (cond.build_gold && !ns_util.math(cond.build_gold).eq(0)) {
        let gold_max = ns_util.math(ns_cs.getTerritoryInfo('gold_curr')).div(cond.build_gold).integer;
        if (gold_max < recruit_max) {
            recruit_max = gold_max;
        }
    }

    if (cond.need_population && dialog.mode !== 'medi') {
        let need_population_max = ns_util.math(ns_cs.getTerritoryInfo('population_idle')).div(cond.need_population).integer;
        if (need_population_max < recruit_max) {
            recruit_max = need_population_max;
        }
    }

    // 훈련손는 최대치 적용
    if (ns_util.math(bd.m_buil_pk).eq(200500) && ns_util.math(recruit_max).gt(level.variation_2)) {
        recruit_max = ns_util.toInteger(level.variation_2);
    }

    return ns_util.toInteger(recruit_max);
}

ns_dialog.dialogs.development.timerHandler = function (_recursive) {
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';
    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.development.timerHandlerReal, 1000, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.development.timerHandlerReal = function () {
    let dialog = ns_dialog.dialogs.development;

    // 현재 자원 갱신
    dialog.cont_obj.content.find('.ns_resource_gold_curr').text(ns_util.numberFormat(ns_util.toInteger(ns_cs.getTerritoryInfo('gold_curr'))));
    for (let _resource of ['food', 'horse', 'lumber', 'iron']) {
        if (dialog.cont_obj.content.find(`.ns_resource_${_resource}_curr`).element) {
            dialog.cont_obj.content.find(`.ns_resource_${_resource}_curr`).text(ns_util.numberFormat(ns_util.toInteger(ns_cs.getResourceInfo(`${_resource}_curr`))));
        }
    }
    // 현재 인구 갱신
    if (dialog.cont_obj.content.find('.ns_need_population').element) {
        dialog.cont_obj.content.find('.ns_need_population').text(ns_util.numberFormat(ns_util.toInteger(ns_cs.getTerritoryInfo('population_idle'))));
    }
};

ns_dialog.dialogs.development.drawNowButton = function () {
    let dialog = ns_dialog.dialogs.development;
    let cond = dialog.condition;

    let build_number = ns_util.toInteger(dialog.cont_obj.develop_amount.value());
    let build_time = ns_util.math(cond.build_time).mul(build_number).integer;
    if (!ns_util.isNumeric(build_time)) {
        build_time = 0;
    }
    let need_qbig = ns_util.getNeedQbig(build_time);

    let div = document.createElement('div');
    div.classList.add('content_item_qbig_amount');
    div.innerHTML = need_qbig;

    let text = (dialog.mode === 'medi') ? ns_i18n.t('medical_immediately_cash', [div.outerHTML]) : ns_i18n.t('training_immediately_cash', [div.outerHTML])
    ns_button.buttons.development_submit_now.obj.html(text);
};

/* ********** */

ns_button.buttons.development_close = new nsButtonSet('development_close', 'button_back', 'development', {base_class: ns_button.buttons.common_close});
ns_button.buttons.development_sub_close = new nsButtonSet('development_sub_close', 'button_full', 'development', {base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.development_close_all = new nsButtonSet('development_close_all', 'button_close_all', 'development', {base_class: ns_button.buttons.common_close_all});

ns_button.buttons.development_updown = new nsButtonSet('development_updown', 'button_middle_2', 'development');
ns_button.buttons.development_updown.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.development;

    let development_value = ns_util.toInteger(dialog.cont_obj.develop_slider.value());
    let recruit_max = dialog.devel_amount;

    if (!ns_util.isNumeric(development_value) || ns_util.math(development_value).lt(recruit_max)) {
        dialog.cont_obj.develop_slider.value(recruit_max);
        dialog.cont_obj.develop_amount.value(recruit_max);
        dialog.armyBuildNumber(recruit_max);
    } else {
        dialog.cont_obj.develop_slider.value(0);
        dialog.cont_obj.develop_amount.value(0);
        dialog.armyBuildNumber(0);
    }

};

ns_button.buttons.development_amount_decrease = new nsButtonSet('development_amount_decrease', 'button_decrease', 'development');
ns_button.buttons.development_amount_decrease.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.development;

    let current_value = ns_util.math(dialog.cont_obj.develop_slider.value()).integer,
        minimum_value = ns_util.math(dialog.cont_obj.develop_slider.element.min).integer;

    current_value = Math.max(--current_value, minimum_value);

    dialog.cont_obj.develop_slider.value(current_value);
    dialog.cont_obj.develop_amount.value(current_value);
    dialog.armyBuildNumber(current_value);
}

ns_button.buttons.development_amount_increase = new nsButtonSet('development_amount_increase', 'button_increase', 'development');
ns_button.buttons.development_amount_increase.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.development;

    let current_value = ns_util.math(dialog.cont_obj.develop_slider.value()).integer,
        maximum_value = ns_util.math(dialog.cont_obj.develop_slider.element.max).integer;

    current_value = Math.min(++current_value, maximum_value);

    dialog.cont_obj.develop_slider.value(current_value);
    dialog.cont_obj.develop_amount.value(current_value);
    dialog.armyBuildNumber(current_value);
}

ns_button.buttons.development_submit = new nsButtonSet('development_submit', 'button_special', 'development');
ns_button.buttons.development_submit.mouseUp = function (_e) {
    ns_dialog.dialogs.development.implement();
};

ns_button.buttons.development_submit_now = new nsButtonSet('development_submit_now', 'button_special', 'development');
ns_button.buttons.development_submit_now.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.development;
    let data = dialog.data;

    // 필요한 데이터
    let in_cast_pk = data.castle_pk;
    let build_number = ns_util.toInteger(dialog.cont_obj.develop_amount.value());

    // 1 보다 작거나 숫자가 아닐때는 리턴
    if (build_number < 1 || !ns_util.isNumeric(build_number)) {
        return;
    }

    let post_data = {};
    post_data['in_cast_pk'] = in_cast_pk;
    post_data['code'] = data.type;
    post_data['build_number'] = build_number;

    if (dialog.mode === 'army') {
        ns_xhr.post('/api/army/now', post_data, (_data, _status) => {
            if (!ns_xhr.returnCheck(_data)) {
                return;
            }
            ns_dialog.dialogs.build_Army.drawRemote();
            ns_dialog.close('development');
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_training_immediately_finish'));
        }, { useProgress: true });
    } else if (dialog.mode === 'medi') {
        ns_xhr.post('/api/medical/now', post_data, (_data, _status) => {
            if (!ns_xhr.returnCheck(_data)) {
                return;
            }
            ns_dialog.dialogs.build_Medical.drawRemote();
            ns_dialog.close('development');
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_medical_immediately_finish'));
        }, { useProgress: true });
    }
};

// information
ns_dialog.dialogs.information = new nsDialogSet('information', 'dialog_pop', 'size-medium', {do_close_all: false});

ns_dialog.dialogs.information.cacheContents = function () {
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);

    this.cont_obj.content_spec_table = new nsObject('.content_spec_table', this.obj);
    this.cont_obj.content_condition_table = new nsObject('.content_condition_table', this.obj);
}

ns_dialog.dialogs.information.draw = function () {
    let data = this.data;
    let m = null, title = '';

    if (data.title === 'army') {
        m = ns_cs.m.army[data.m_pk];
        title = ns_i18n.t(`army_title_${data.m_pk}`);
        this.condition = ns_cs.m.cond[m.m_cond_pk];
        this.cont_obj.content_spec_table.find('.need_population_title').text(ns_i18n.t('required_population_amount'));
    } else if (data.title === 'medi') {
        m = ns_cs.m.army[data.m_pk];
        title = ns_i18n.t(`army_title_${data.m_pk}`);
        this.condition = ns_cs.m.cond[m.m_medi_cond_pk];
        this.cont_obj.content_spec_table.find('.need_population_title').text('');
    } else if (data.title === 'fort') {
        m = ns_cs.m.fort[data.m_pk];
        title = ns_i18n.t(`fort_title_${data.m_pk}`);
        this.condition = ns_cs.m.cond[m.m_cond_pk];
        this.cont_obj.content_spec_table.find('.need_population_title').text(ns_i18n.t('required_space_amount'));
    }

    if (!m) {
        return;
    }
    this.cont_obj.content_pop_title.text(title);
    this.mode = data.title;

    // 방어시설 관련 처리
    this.cont_obj.content_spec_table.find('.spec_capacity').text((data.title === 'fort') ? '-' : m.spec_capacity);
    this.cont_obj.content_spec_table.find('.spec_speed').text((data.title === 'fort') ? '-' : m.spec_speed);
    this.cont_obj.content_spec_table.find('.need_food').text((data.title === 'fort') ? '-' : m.need_food);
    this.cont_obj.content_spec_table.find('.need_population').text((data.title === 'fort') ? m.need_vacancy : m.need_population);

    // 취약병과 처리
    let weaker_type = '-';
    if (m.weak_type && m.weaker_type) {
        weaker_type = m.weak_type.split(',').map(_w => ns_i18n.t(`army_title_${ns_cs.m.army[_w].m_army_pk}`)).join(',');
        weaker_type += '/' + m.weaker_type.split(',').map(_w => ns_i18n.t(`fort_title_${ns_cs.m.fort[_w].m_fort_pk}`)).join(',');
    } else if (m.weak_type_title && !m.weaker_type_title) {
        weaker_type = m.weak_type.split(',').map(_w => ns_i18n.t(`army_title_${ns_cs.m.army[_w].m_army_pk}`)).join(',');
    }

    this.cont_obj.content_spec_table.find('.weak_type').text(weaker_type);
    this.cont_obj.content_spec_table.find('.spec_attack').text(m.spec_attack);
    this.cont_obj.content_spec_table.find('.spec_defence').text(m.spec_defence);
    this.cont_obj.content_spec_table.find('.spec_energy').text(m.spec_energy);
    this.cont_obj.content_spec_table.find('.category').text(m.category);
    this.cont_obj.content_spec_table.find('.spec_target_range').text(m.spec_target_range);
    this.cont_obj.content_spec_table.find('.spec_attack_range').text(m.spec_attack_range);
    this.cont_obj.content_spec_table.find('.spec_attack_efficiency').text(m.spec_attack_efficiency);
    this.cont_obj.content_spec_table.find('.attack_line').text(m.attack_line);
    this.cont_obj.content_spec_table.find('.defence_line').text(m.defence_line);
}

ns_dialog.dialogs.information.timerHandler = function ()
{
    let dialog = ns_dialog.dialogs.information;
    let timer_id = 'information_real';
    ns_timer.timers[timer_id] = new nsTimerSet(dialog.timerHandlerReal, 1000, true);
    ns_timer.timers[timer_id].init();
    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.information.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.information;

    let tbody = dialog.cont_obj.content_condition_table;
    tbody.empty();
    ns_check_condition.drawList(dialog.condition.m_cond_pk, dialog.data.castle_pk, tbody.element, false);
}

/* ********** */

ns_button.buttons.information_close = new nsButtonSet('information_close', 'button_pop_close', 'information', {base_class: ns_button.buttons.common_close});
ns_button.buttons.information_sub_close = new nsButtonSet('information_sub_close', 'button_full', 'information', {base_class: ns_button.buttons.common_sub_close});

/* tech_infomation */
ns_dialog.dialogs.tech_information = new nsDialogSet('tech_information', 'dialog_pop', 'size-medium', {do_close_all: false});

ns_dialog.dialogs.tech_information.cacheContents = function () {
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.technique_infomation_description = new nsObject('.technique_infomation_description', this.obj);
    this.cont_obj.content_information_wrapper = new nsObject('.content_information_wrapper', this.obj);
    this.cont_obj.content_table_information = new nsObject('.content_table_information', this.obj);
}

ns_dialog.dialogs.tech_information.draw = function () {
    let data = this.data;
    this.mode = data.title;

    let m = ns_cs.m.tech[data.m_pk];
    let level = ns_util.toInteger(ns_cs.d.tech[m.code].v);
    let max_level = ns_util.toInteger(m.max_level);
    let key_level = (max_level > (level + 1)) ? level + 1 : max_level;

    // this.cont_obj.content_pop_title.text(m.title);
    this.cont_obj.content_pop_title.text(ns_i18n.t(`tech_title_${m.m_tech_pk}`));
    this.cont_obj.technique_infomation_description.text(ns_i18n.t(`tech_description_${m.m_tech_pk}`));

    if (key_level > level) {
        this.cont_obj.content_information_wrapper.show();

        this.condition = ns_cs.m.cond[m.level[key_level].m_cond_pk];

        this.cont_obj.content_table_information.empty();

        ns_check_condition.drawList(this.condition.m_cond_pk, this.data.castle_pk, this.cont_obj.content_table_information);
    } else {
        this.cont_obj.content_information_wrapper.hide();
    }
}

ns_dialog.dialogs.tech_information.timerHandler = function ()
{
    let dialog = ns_dialog.dialogs.tech_information;
    let timer_id = 'tech_information_real';
    ns_timer.timers[timer_id] = new nsTimerSet(dialog.timerHandlerReal, 1000, true);
    ns_timer.timers[timer_id].init();
    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.tech_information.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.tech_information;

    dialog.cont_obj.content_table_information.empty();
    ns_check_condition.drawList(dialog.condition.m_cond_pk, dialog.data.castle_pk, dialog.cont_obj.content_table_information);
}

/* ********** */

ns_button.buttons.tech_information_close = new nsButtonSet('tech_information_close', 'button_pop_close', 'tech_information', {base_class: ns_button.buttons.common_close});
ns_button.buttons.tech_information_sub_close = new nsButtonSet('tech_information_sub_close', 'button_full', 'tech_information', {base_class: ns_button.buttons.common_sub_close});

/* disperse */
ns_dialog.dialogs.disperse = new nsDialogSet('disperse', 'dialog_pop', 'size-medium', {do_close_all: false});
ns_dialog.dialogs.disperse.mode = 'army';
ns_dialog.dialogs.disperse_amount = 0;

ns_dialog.dialogs.disperse.cacheContents = function () {
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.content_disperse_resource = new nsObject('.content_disperse_resource', this.obj);

    this.cont_obj.disperse_amount = new nsObject('.amount_field', this.obj);
    this.cont_obj.disperse_amount_slide = new nsObject('[name="develop_slide"]', this.obj);
    this.cont_obj.disperse_amount_slide.setEvent('input', (_e) => {
        _e.preventDefault();

        this.cont_obj.disperse_amount.value(_e.target.value);
        this.disperseNumber();
    });

    this.cont_obj.disperse_amount.setEvent('input', (_e) => {
        let regexp = /[^\d.]|\.(?=.*\.)/g;
        let current_value = _e.target.value;

        let minimum_value = this.cont_obj.disperse_amount_slide.element.min,
            maximum_value = this.cont_obj.disperse_amount_slide.element.max;

        current_value = (!ns_util.isNumeric(current_value)) ? minimum_value : current_value;
        current_value = parseInt(current_value.replace(regexp, ""));
        current_value = (!current_value) ? minimum_value : current_value;

        current_value = Math.min(Math.max(current_value, minimum_value), maximum_value);

        this.cont_obj.disperse_amount.value(current_value);
        this.cont_obj.disperse_amount_slide.value(current_value);
        this.disperseNumber();
    });
}

ns_dialog.dialogs.disperse.draw = function () {
    let data = this.data;
    let m = ns_cs.m.army[data.m_pk];
    let main_title = ns_i18n.t('army_disperse', [ns_i18n.t(`army_title_${data.m_pk}`)]);
    let cond = ns_cs.m.cond[m.m_cond_pk];
    let army_curr = ns_cs.d.army[m.code].v; // 보유

    this.mode = data.title;
    this.disperse_amount = army_curr;

    if (this.mode === 'medi') {
        // 의료원에서 접근시 의료원 데이터로 덮어씀.
        main_title = ns_i18n.t('injury_army_disperse', [ns_i18n.t(`army_title_${data.m_pk}`)]);
        cond = ns_cs.m.cond[m.m_medi_cond_pk];
        army_curr = ns_cs.d.army_medi[data.type].v; // 임시
        this.disperse_amount = army_curr;
    }

    this.cont_obj.content_pop_title.text(main_title);

    for (let _type of ['demolish_food', 'demolish_horse', 'demolish_lumber', 'demolish_iron', 'demolish_gold']) {
        this.cont_obj.content_disperse_resource.find(`.content_${_type}`).text(ns_util.numberFormat(cond[_type] ? ns_util.toInteger(cond[_type]) : 0));
    }
    this.cont_obj.content_disperse_resource.find(`.content_disperse_population`).text(ns_util.numberFormat(cond.need_population ? ns_util.toInteger(cond.need_population) : 0));

    let max = this.disperse_amount;

    this.cont_obj.disperse_amount.value(1);

    this.cont_obj.disperse_amount_slide.value(1);
    this.cont_obj.disperse_amount_slide.element.min = 1;
    this.cont_obj.disperse_amount_slide.element.max = max;

    ns_button.buttons.disperse_max_count.obj.text(max);

}

ns_dialog.dialogs.disperse.timerHandler = function (_recursive) {
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.disperse.timerHandlerReal, 1000, true, 1000);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.disperse.timerHandlerReal = function () {
    let dialog = ns_dialog.dialogs.disperse;
    for (let _type of ['food_curr', 'horse_curr', 'lumber_curr', 'iron_curr']) {
        dialog.cont_obj.content_disperse_resource.find(`.ns_resource_${_type}`).text(ns_util.numberFormat(ns_util.toInteger(ns_cs.d.reso[_type].v)));
    }
    for (let _type of ['gold_curr', 'population_idle']) {
        dialog.cont_obj.content_disperse_resource.find(`.ns_territory_${_type}`).text(ns_util.numberFormat(ns_util.toInteger(ns_cs.d.terr[_type].v)));
    }
}

ns_dialog.dialogs.disperse.disperseNumber = function () {
    let dialog = ns_dialog.dialogs.disperse;
    let m = ns_cs.m.army[dialog.data.m_pk];
    let cond = ns_cs.m.cond[m.m_cond_pk];
    let disperse_max = ns_cs.d.army[m.code].v;
    if (dialog.mode === 'medi') {
        disperse_max = dialog.disperse_amount;
        cond = ns_cs.m.cond[m.m_medi_cond_pk];
    }

    let disperse_number = dialog.cont_obj.disperse_amount
    if (!ns_util.isNumeric(disperse_number.value())) {
        disperse_number.value(1);
    }
    disperse_number = disperse_number.value();

    if (ns_util.math(disperse_max).lt(disperse_number)) {
        disperse_number = ns_util.toInteger(disperse_max);
        dialog.cont_obj.disperse_amount.value(1);
        dialog.cont_obj.disperse_amount_slide.value(disperse_number);
    }

    if (ns_util.math(disperse_number).lt(1)) {
        disperse_number = 1;
    }

    //조건 검사
    for (let _type of ['demolish_food', 'demolish_horse', 'demolish_lumber', 'demolish_iron', 'demolish_gold']) {
        dialog.cont_obj.content_disperse_resource.find(`.content_${_type}`).text(ns_util.math(cond[_type]).mul(disperse_number).number_format);
    }
    this.cont_obj.content_disperse_resource.find(`.content_disperse_population`).text(ns_util.math(cond.need_population).mul(disperse_number).number_format);

    /*for (let _type of ['gold_curr', 'population_idle']) {
        dialog.cont_obj.content_disperse_resource.find(`.ns_territory_${_type}`).text(ns_util.math(ns_cs.d.terr[_type].v).mul(disperse_number).number_format);
    }*/
}

ns_dialog.dialogs.disperse.implement = function () {
    let dialog = ns_dialog.dialogs.disperse;
    let data = dialog.data;

    let disperse_number = dialog.cont_obj.disperse_amount;
    if (!ns_util.isNumeric(disperse_number.value()) || ns_util.math(disperse_number.value()).lt(1)) {
        return;
    }

    let post_data = {};
    post_data['in_cast_pk'] = data.castle_pk;
    post_data['code'] = data.type;
    post_data['disperse_number'] = ns_util.toInteger(disperse_number.value());

    let api_url = (dialog.mode === 'medi') ? 'medical' : 'army';
    ns_xhr.post(`/api/${api_url}/disperse`, post_data, (_data) => {
        if (!ns_xhr.returnCheck(_data)) {
            return;
        }

        _data = _data['ns_xhr_return']['add_data'];

        if (dialog.mode === 'medi') {
            ns_dialog.dialogs.build_Medical.drawRemote();
        }

        dialog.close('disperse');
    }, { useProgress: true });
}

/* ********** */
ns_button.buttons.disperse_close = new nsButtonSet('disperse_close', 'button_pop_close', 'disperse', {base_class: ns_button.buttons.common_close});
ns_button.buttons.disperse_sub_close = new nsButtonSet('disperse_sub_close', 'button_full', 'disperse', {base_class: ns_button.buttons.common_sub_close});

ns_button.buttons.disperse_max_count = new nsButtonSet('disperse_amount_max_count', 'button_middle_2', 'disperse');
ns_button.buttons.disperse_max_count.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.disperse;

    let maximum_value = ns_util.math(dialog.cont_obj.disperse_amount_slide.element.max).integer;
    let current_value = ns_util.math(dialog.cont_obj.disperse_amount.value()).integer;

    if (current_value === maximum_value) current_value = 0;
    else current_value = maximum_value;


    dialog.cont_obj.disperse_amount_slide.value(current_value);
    dialog.cont_obj.disperse_amount.value(current_value);
    dialog.disperseNumber();
}

ns_button.buttons.disperse_amount_decrease = new nsButtonSet('disperse_decrease', 'button_decrease', 'disperse');
ns_button.buttons.disperse_amount_decrease.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.disperse;

    let minimum_value = ns_util.math(dialog.cont_obj.disperse_amount_slide.element.min).integer;
    let current_value = ns_util.math(dialog.cont_obj.disperse_amount.value()).integer;

    current_value = Math.max(--current_value, minimum_value);

    dialog.cont_obj.disperse_amount_slide.value(current_value);
    dialog.cont_obj.disperse_amount.value(current_value);
    dialog.disperseNumber();
}

ns_button.buttons.disperse_amount_increase = new nsButtonSet('disperse_increase', 'button_increase', 'disperse');
ns_button.buttons.disperse_amount_increase.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.disperse;

    let maximum_value = ns_util.math(dialog.cont_obj.disperse_amount_slide.element.max).integer;
    let current_value = ns_util.math(dialog.cont_obj.disperse_amount.value()).integer;

    current_value = Math.min(++current_value, maximum_value);

    dialog.cont_obj.disperse_amount_slide.value(current_value);
    dialog.cont_obj.disperse_amount.value(current_value);
    dialog.disperseNumber();
}

ns_button.buttons.disperse_submit = new nsButtonSet('disperse_submit', 'button_default', 'disperse');
ns_button.buttons.disperse_submit.mouseUp = function (_e) {
    ns_dialog.dialogs.disperse.implement();
}

/* lord_upgrade */
ns_dialog.dialogs.lord_upgrade = new nsDialogSet('lord_upgrade', 'dialog_pop', 'size-medium', {
    do_content_scroll: false,
    do_close_all: false
});

ns_dialog.dialogs.lord_upgrade.cacheContents = function () {
    this.cont_obj.lord_upgrade_reward = new nsObject('.lord_upgrade_reward', this.obj);
}

ns_dialog.dialogs.lord_upgrade.draw = function () {
    this.cont_obj.lord_upgrade_reward.removeCss().addCss('lord_upgrade_bg' + this.data.level);
}

/* ********** */

ns_button.buttons.lord_upgrade_close = new nsButtonSet('lord_upgrade_close', 'button_default', 'lord_upgrade', {base_class: ns_button.buttons.common_close});
ns_button.buttons.lord_upgrade_sub_close = new nsButtonSet('lord_upgrade_sub_close', 'button_full', 'lord_upgrade', {base_class: ns_button.buttons.common_sub_close});

/* limit_buy
ns_dialog.dialogs.limit_buy = new nsDialogSet('limit_buy', 'dialog_pop', 'bigpop', {
    do_content_scroll: false,
    do_close_all: false
});
ns_dialog.dialogs.limit_buy.do_not_close = false;

ns_dialog.dialogs.limit_buy.cache_contents = function () {
    this.cont_obj.cont_limit_buy = this.cont_obj.find('.cont_limit_buy');
    this.cont_obj.cont_lord_up_buy = this.cont_obj.find('.cont_lord_up_buy');
}

ns_dialog.dialogs.limit_buy.draw = function () {
    ns_button.buttons.limit_buy_keep.obj.hide();
    this.cont_obj.cont_limit_buy.hide();
    this.cont_obj.cont_lord_up_buy.hide();

    if (ns_util.math(this.data.level).eq(2)) {
        this.cont_obj.cont_limit_buy.show();
        ns_button.buttons.limit_buy_keep.obj.show();
    } else {
        this.cont_obj.cont_lord_up_buy.show();
    }

    this.do_not_close = true;

    this.customShow();
}

ns_dialog.dialogs.limit_buy.erase = function () {
    this.customHide();
}

ns_button.buttons.limit_buy_close = new nsButtonSet('limit_buy_close', 'button_default', 'limit_buy');
ns_button.buttons.limit_buy_close.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.limit_buy;

    if (ns_util.math(dialog.data.level).eq(2)) {
        let okFunc_proc = function () {
            dialog.do_not_close = false;
            window.localStorage.removeItem(ns_cs.d.lord.lord_pk.v + '_limit_buy');
            ns_dialog.close('limit_buy');
        };
        let noFunc_proc = function () {
        };

        ns_dialog.setDataOpen('confirm', {
            text: '<spen style="color:red;">승급 기념 한정 아이템은 2등급 승급시에만<br />구매가능한 아이템입니다.<br />그래도 닫으시겠습니까?</span><br /><br />지금 바로 구매가 불가능할 경우 나중에 구매를<br />선택하시면 재접속시 다시 팝업됩니다.',
            okFunc: okFunc_proc,
            noFunc: noFunc_proc
        });
    } else {
        let okFunc_proc = function () {
            dialog.do_not_close = false;
            ns_dialog.close('limit_buy');
        };
        let noFunc_proc = function () {
        };


        ns_dialog.setDataOpen('confirm', {
            text: '<spen style="color:red;">해당 아이템은 군주등급 승급시에만<br />구매 가능한 아이템입니다.<br />그래도 닫으시겠습니까?</span>',
            okFunc: okFunc_proc,
            noFunc: noFunc_proc
        });

    }
}

ns_button.buttons.limit_buy_keep = new nsButtonSet('limit_buy_keep', 'button_default', 'limit_buy');
ns_button.buttons.limit_buy_keep.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.limit_buy;

    if (dialog.data.level == 2) {
        dialog.do_not_close = false;
        ns_dialog.setDataOpen('message', '게임을 재접속하시면 다시 팝업됩니다.');
        window.localStorage.setItem(ns_cs.d.lord.lord_pk.v + '_limit_buy', 'Y');
        ns_dialog.close('limit_buy');
    }
}

ns_button.buttons.limit_buy_button = new nsButtonSet('limit_buy_button', 'button_event_listener', 'limit_buy');
ns_button.buttons.limit_buy_button.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.limit_buy;
    let offset = $('#qbw_button_limit_buy_button').offset();

    let fakeOffsetY = qbw_btn.lastPageY - offset['top'];

    if (fakeOffsetY < 0)
        return;

    if (dlg.data.level == 2) {
        ns_dialog.setDataOpen('item_buy', {m_item_pk: 500744});
    } else {
        ns_dialog.setDataOpen('item_buy', {m_item_pk: 500498});
    }
} */


/* game_help */
ns_dialog.dialogs.game_help = new nsDialogSet('game_help', 'dialog_pop', 'helppop', {do_close_all: false});

ns_dialog.dialogs.game_help.cache_contents = function () {
    this.cont_obj.cont_help_content = this.cont_obj.find('.cont_help_content');
};

ns_dialog.dialogs.game_help.draw = function () {
    $('#wrap_trans').show();

    this.draw_help();

    this.customShow();
    this.contentRefresh();
};

ns_dialog.dialogs.game_help.draw_help = function () {
    let dialog = ns_dialog.dialogs.game_help;
    let type = dialog.data.type;

    dialog.cont_obj.cont_help_content.empty(); // 비우기

    // 이미지가 존재하는 것만
    if (type != 'report' && type != 'HeroCombi' && type != 'Alliance' && type != 'band') {
        let img = document.createElement('div');
        img.setAttribute('class', 'cont_help_image_' + type);

        dialog.cont_obj.cont_help_content.append(img);
    }

    let text = document.createElement('div');
    text.setAttribute('class', 'cont_help_text');
    text.innerHTML = system_text['game_help_' + type];

    dialog.cont_obj.cont_help_content.append(text);
};

ns_dialog.dialogs.game_help.erase = function () {
    $('#wrap_trans').hide();

    this.customHide();
};

// ns_button.buttons.game_help_close = new nsButtonSet('game_help_close', 'button_pop_close', 'game_help', {base_class:ns_button.buttons.common_close});
ns_button.buttons.game_help_close2 = new nsButtonSet('game_help_close2', 'button_default', 'game_help');
ns_button.buttons.game_help_close2.mouseUp = function (_e) {
    ns_dialog.close('game_help');
};


/* account_link */
ns_dialog.dialogs.account_link = new nsDialogSet('account_link', 'dialog_full', 'size-full', { do_close_all: false });

ns_button.buttons.account_link_close = new nsButtonSet('account_link_close', 'button_back', 'account_link', {base_class: ns_button.buttons.common_close});
ns_button.buttons.account_link_sub_close = new nsButtonSet('account_link_sub_close', 'button_full', 'account_link', {base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.account_link_close_all = new nsButtonSet('account_link_close_all', 'button_close_all', 'account_link', {base_class: ns_button.buttons.common_close_all});

/* push_agree */
ns_dialog.dialogs.push_agree = new nsDialogSet('push_agree', 'dialog_pop', 'bigpop', {
    do_content_scroll: false,
    do_close_all: false
});
ns_dialog.dialogs.push_agree.draw = function () {
    ns_button.buttons.setting_all_push_agree.setClicked();
    ns_button.buttons.setting_pn_event_t1_agree.setClicked();
};

/* ********** */

ns_button.buttons.setting_pn_event_t1_agree = new nsButtonSet('setting_pn_event_t1_agree', 'button_onoff', 'push_agree');
ns_button.buttons.setting_pn_event_t1_agree.mouseUp = function (_e) {
    this.toggleClicked();
    if (!ns_button.buttons.setting_pn_event_t1_agree.clicked && !ns_button.buttons.setting_pn_event_t2_agree.clicked) {
        ns_button.buttons.setting_all_push_agree.unsetClicked();
    } else {
        ns_button.buttons.setting_all_push_agree.setClicked();
    }
};
ns_button.buttons.setting_pn_event_t2_agree = new nsButtonSet('setting_pn_event_t2_agree', 'button_onoff', 'push_agree', {base_class: ns_button.buttons.setting_pn_event_t1_agree});

ns_button.buttons.setting_all_push_agree = new nsButtonSet('setting_all_push_agree', 'button_onoff', 'push_agree');
ns_button.buttons.setting_all_push_agree.mouseUp = function (_e) {
    this.toggleClicked();
    if (this.clicked) {
        ns_button.buttons.setting_pn_event_t1_agree.setClicked();
        ns_button.buttons.setting_pn_event_t2_agree.unsetClicked();
    } else {
        ns_button.buttons.setting_pn_event_t1_agree.unsetClicked();
        ns_button.buttons.setting_pn_event_t2_agree.unsetClicked();
    }
};

ns_button.buttons.push_agree_submit = new nsButtonSet('push_agree_submit', 'button_special', 'push_agree');
ns_button.buttons.push_agree_submit.mouseUp = function (_e) {
    let dialog = ns_dialog.dialogs.setting;

    let post_data = 'action=set&counsel_action=Y&counsel_connect=Y&sound_bgm=Y&sound_effect=Y';
    if (ns_button.buttons.setting_pn_event_t1_agree.clicked) {
        post_data += '&pn_event_t1=Y';
    }
    if (ns_button.buttons.setting_pn_event_t2_agree.clicked) {
        post_data += '&pn_event_t2=Y';
    }
    if (ns_button.buttons.setting_all_push_agree.clicked) {
        post_data += '&pn_report_t1=Y&pn_advice_t1=Y&pn_terr_t1=Y';
    }

    qbw_cmd('/a/setting.php', post_data, function (_data, _status) {
        if (!qbw_cmd_return_check(_data))
            return;
        _data = _data.qbw_cmd_return.add_data;

        let d = new Date();
        let date_time = ' (' + d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate() + ')'; //  + ' ' + d.getHours() + ':' + d.getMinutes() + ':' + d.getSeconds()
        let push_message = '';

        if (ns_button.buttons.setting_pn_event_t1_agree.clicked && ns_button.buttons.setting_pn_event_t2_agree.clicked) {
            push_message += '주간 광고성 푸시 수신에 동의하였습니다.<br />야간 광고성 푸시 수신에 동의하였습니다.';
        } else if (ns_button.buttons.setting_pn_event_t1_agree.clicked && !ns_button.buttons.setting_pn_event_t2_agree.clicked) {
            push_message += '주간 광고성 푸시 수신에 동의하였습니다.<br />야간 광고성 푸시 수신에 거부하셨습니다.';
        } else if (!ns_button.buttons.setting_pn_event_t1_agree.clicked && ns_button.buttons.setting_pn_event_t2_agree.clicked) {
            push_message += '야간 광고성 푸시 수신에 거부하셨습니다.<br />야간 광고성 푸시 수신에 동의하였습니다.';
        } else {
            push_message += '주간 광고성 푸시 수신에 거부하셨습니다.<br />야간 광고성 푸시 수신에 거부하셨습니다.';
        }
        if (push_message !== '') {
            push_message = '[소셜삼국 리부트]<br />' + push_message + '<br />' + date_time;
            ns_dialog.setDataOpen('message', push_message);
        }
        ns_dialog.close('push_agree');
    });
};

ns_dialog.dialogs.menu = new nsDialogSet('menu', 'dialog_full', 'size-full', { do_content_scroll: false });
ns_dialog.dialogs.menu.cacheContents = function (_recursive) {
    // this.s.cont_no_ios_object = this.cont_obj.find('.no_ios');
    // this.s.cont_guest_only_object = this.cont_obj.find('.guest_only');
};

ns_dialog.dialogs.menu.draw = function () {
    // Adbrix('retention', 'menu');
    // if (window.isMobile.apple.device) {
    //     this.s.cont_no_ios_object.css('display', 'none');
    // } else {
    //     this.s.cont_no_ios_object.css('display', 'block');
    // }
//
    // if (ns_engine.auth.anonymous === '1') {
    //     this.s.cont_guest_only_object.css('display', 'block');
    // } else {
    //     this.s.cont_guest_only_object.css('display', 'none');
    // }
};

/* ************************************************** */

ns_button.buttons.menu_close = new nsButtonSet('menu_close', 'button_back', 'menu', {base_class: ns_button.buttons.common_close});
ns_button.buttons.menu_sub_close = new nsButtonSet('menu_sub_close', 'button_full', 'menu', {base_class: ns_button.buttons.common_sub_close});
ns_button.buttons.menu_close_all = new nsButtonSet('menu_close_all', 'button_close_all', 'menu', {base_class: ns_button.buttons.common_close_all});

ns_button.buttons.menu_notice = new nsButtonSet('menu_notice', 'button_menu', 'menu');
ns_button.buttons.menu_notice.mouseUp = function (_e)
{
    // ns_dialog.open('notice');
    window.open(`/redirect?type=notice&platform=${ns_engine.cfg.app_platform}`, '_blank');
}

ns_button.buttons.menu_event = new nsButtonSet('menu_event', 'button_menu', 'menu');
ns_button.buttons.menu_event.mouseUp = function (_e)
{
    ns_dialog.open('main_event');
}

ns_button.buttons.menu_account_link = new nsButtonSet('menu_account_link', 'button_menu', 'menu');
ns_button.buttons.menu_account_link.mouseUp = function (_e)
{
    ns_dialog.open('account_link');
};

ns_button.buttons.menu_inform = new nsButtonSet('menu_inform', 'button_menu', 'menu');
ns_button.buttons.menu_inform.mouseUp = function (_e)
{
    ns_dialog.open('inform');
}

ns_button.buttons.menu_setting = new nsButtonSet('menu_setting', 'button_menu', 'menu');
ns_button.buttons.menu_setting.mouseUp = function (_e)
{
    ns_dialog.open('setting');
}

ns_button.buttons.menu_inquiry = new nsButtonSet('menu_inquiry', 'button_menu', 'menu');
ns_button.buttons.menu_inquiry.mouseUp = function (_e)
{
    ns_engine.inquiry();
};

ns_button.buttons.menu_qbig = new nsButtonSet('menu_qbig', 'button_menu', 'menu');
ns_button.buttons.menu_qbig.mouseUp = function (_e)
{
    ns_engine.buyQbig();
}

ns_button.buttons.menu_coupon = new nsButtonSet('menu_coupon', 'button_menu', 'menu');
ns_button.buttons.menu_coupon.mouseUp = function (_e)
{
    ns_dialog.open('coupon');
}

ns_button.buttons.menu_server_select = new nsButtonSet('menu_server_select', 'button_menu', 'menu');
ns_button.buttons.menu_server_select.mouseUp = function (_e)
{
    ns_dialog.setDataOpen('confirm', {
        text: ns_i18n.t('msg_move_server_select'), // 서버 선택 화면으로 이동하시겠습니까?<br /><br />현재 진행 중인 게임은 종료됩니다.
        okFunc: () => {
            document.location.reload();
        }
    });
}

ns_button.buttons.menu_game_rating = new nsButtonSet('menu_game_rating', 'button_menu', 'menu');
ns_button.buttons.menu_game_rating.mouseUp = function (_e)
{
    ns_dialog.open('game_rating');
}

ns_button.buttons.menu_logout = new nsButtonSet('menu_logout', 'button_menu', 'menu');
ns_button.buttons.menu_logout.mouseUp = function (_e) {
    ns_dialog.setDataOpen('confirm', {
        text: ns_i18n.t('msg_logout_exit_game'), // 게임에서 로그아웃 후 첫 화면으로 돌아갑니다.<br /><br />현재 진행 중인 게임은 종료됩니다.
        okFunc: () => {
            ns_auth.removeAuth();
            document.location.reload();
        }
    });
}


// 영지 이동 주단위
ns_dialog.dialogs.terr_move_item_state = new nsDialogSet('terr_move_item_state', 'dialog_pop', 'size-medium');

ns_dialog.dialogs.terr_move_item_state.draw = function () {
    for (let i of [1, 2, 3, 4, 5, 6, 7, 8, 9]) {
        ns_button.buttons[`terr_move_state_${i}`].obj.text(code_set.world.state[`name0${i}`]);
    }
}

ns_dialog.dialogs.terr_move_item_state.drawRemote = function (_index) {
    ns_xhr.post('/api/position/terrMoveState', {posi_stat_pk: _index}, (_data, _status) => {
        if (!ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        let avail_area_count = _data.avail_area_count;
        let agree_area_count = _data.agree_area_count;
        let state_name = code_set.world.state[`name0${_index}`];

        if (agree_area_count > 71 || avail_area_count >= 10) {
            let status_text = (agree_area_count > 71) ? ns_i18n.t('pleasant') : ns_i18n.t('average'); // '쾌적' : '보통';
            let text_class = (agree_area_count > 71) ? 'text_condition_yes' : 'text_yellow';
            let message = ns_i18n.t('msg_territory_move_state_confirm', [state_name, text_class, status_text]);

            ns_dialog.setDataOpen('confirm', {
                text: message,
                okFunc: () => {
                    let m_item_pk = 500122;
                    let post_data = {
                        action: 'use_item',
                        item_pk: m_item_pk,
                        state: _index
                    };
                    ns_xhr.post('/api/item/use', post_data, (_data, _status) => {
                        if (!ns_xhr.returnCheck(_data)) {
                            return;
                        }
                        _data = _data['ns_xhr_return']['add_data'];
                        ns_dialog.dialogs.item_use.useItemResult(_data);
                    }, { useProgress: true });
                }
            });
        } else {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_territory_move_state_failed', [state_name]));
        }
    });
}

ns_button.buttons.terr_move_item_state_close = new nsButtonSet('terr_move_item_state_close', 'button_pop_close', 'terr_move_item_state', {base_class: ns_button.buttons.common_close});
ns_button.buttons.terr_move_item_state_sub_close = new nsButtonSet('terr_move_item_state_sub_close', 'button_full', 'terr_move_item_state', {base_class: ns_button.buttons.common_sub_close});

ns_button.buttons.terr_move_state_1 = new nsButtonSet('terr_move_state_1', 'button_default', 'terr_move_item_state');
ns_button.buttons.terr_move_state_1.mouseUp = function () {
    let index = this.tag_id.split('_state_').pop();
    let dialog = ns_dialog.dialogs.terr_move_item_state;
    dialog.drawRemote(index);
}

ns_button.buttons.terr_move_state_2 = new nsButtonSet('terr_move_state_2', 'button_default', 'terr_move_item_state', {base_class: ns_button.buttons.terr_move_state_1});
ns_button.buttons.terr_move_state_3 = new nsButtonSet('terr_move_state_3', 'button_default', 'terr_move_item_state', {base_class: ns_button.buttons.terr_move_state_1});
ns_button.buttons.terr_move_state_4 = new nsButtonSet('terr_move_state_4', 'button_default', 'terr_move_item_state', {base_class: ns_button.buttons.terr_move_state_1});
ns_button.buttons.terr_move_state_5 = new nsButtonSet('terr_move_state_5', 'button_default', 'terr_move_item_state', {base_class: ns_button.buttons.terr_move_state_1});
ns_button.buttons.terr_move_state_6 = new nsButtonSet('terr_move_state_6', 'button_default', 'terr_move_item_state', {base_class: ns_button.buttons.terr_move_state_1});
ns_button.buttons.terr_move_state_7 = new nsButtonSet('terr_move_state_7', 'button_default', 'terr_move_item_state', {base_class: ns_button.buttons.terr_move_state_1});
ns_button.buttons.terr_move_state_8 = new nsButtonSet('terr_move_state_8', 'button_default', 'terr_move_item_state', {base_class: ns_button.buttons.terr_move_state_1});
ns_button.buttons.terr_move_state_9 = new nsButtonSet('terr_move_state_9', 'button_default', 'terr_move_item_state', {base_class: ns_button.buttons.terr_move_state_1});

// coupon
ns_dialog.dialogs.coupon = new nsDialogSet('coupon', 'dialog_pop', 'size-medium');

ns_dialog.dialogs.coupon.cacheContents = function ()
{
    this.cont_obj.coupon_code = new nsObject('input[name=coupon_code]', this.obj);
};

ns_dialog.dialogs.coupon.draw = function ()
{
    this.cont_obj.coupon_code.value('');
};

ns_dialog.dialogs.coupon.erase = function ()
{
    this.data = {};
    this.cont_obj.coupon_code.value('');
}

ns_button.buttons.coupon_close = new nsButtonSet('coupon_close', 'button_pop_close', 'coupon', {base_class: ns_button.buttons.common_close});
ns_button.buttons.coupon_sub_close = new nsButtonSet('coupon_sub_close', 'button_full', 'coupon', {base_class: ns_button.buttons.common_sub_close});

ns_button.buttons.coupon_submit = new nsButtonSet('coupon_submit', 'button_pop_normal', 'coupon');
ns_button.buttons.coupon_submit.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.coupon;
    let code = dialog.cont_obj.coupon_code.value();
    if (code === '') {
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_need_coupon_code')); // 쿠폰 코드를 입력해주세요.
        return;
    }

    ns_xhr.post('/api/coupon/use', { code: code }, (_data, _status) =>
    {
        if (!ns_xhr.returnCheck(_data)) {
            return;
        }
        // _data = _data['ns_xhr_return']['add_data'];
        ns_dialog.setDataOpen('message', ns_i18n.t('msg_coupon_use_get_item')); // 쿠폰을 사용하여 아이템을 획득하였습니다.
        ns_dialog.close('coupon');
    }, { useProgress: true });
}

/* ********************** */
ns_dialog.dialogs.package_popup = new nsDialogSet('package_popup', 'dialog_package', 'size-medium', { do_content_scroll: false });
ns_dialog.dialogs.package_popup.list = [];
ns_dialog.dialogs.package_popup.page = 0;
ns_dialog.dialogs.package_popup.package_data = null;

ns_dialog.dialogs.package_popup.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.package_page_wrap = new nsObject('.package_page_wrap', this.obj);
    this.cont_obj.package_page = new nsObject('.package_page', this.obj);
    this.cont_obj.package_wrap_skeleton = new nsObject('#package_wrap_skeleton');
}

ns_dialog.dialogs.package_popup.draw = function()
{
    this.buttonClear();
    this.setPage();
    this.drawPackage();
}

ns_dialog.dialogs.package_popup.drawPackage = function ()
{
    let dialog = ns_dialog.dialogs.package_popup;
    dialog.package_data = ns_engine.game_data.package_data[dialog.data.m_package_pk];
    let m = ns_cs.m.package[dialog.data.m_package_pk];
    if (! m?.reward_item) {
        console.log(m);
        return;
    }
    let m_item = ns_cs.m.item[m.reward_item];

    let wrap = dialog.cont_obj.package_wrap_skeleton.clone();
    dialog.cont_obj.content.find('.main_content').append(wrap);
    let item_wrap = wrap.find('.package_sub_item_wrap');

    dialog.cont_obj.content_pop_title.text(m.title);

    wrap.find('.item_image').addCss(`item_image_${m.reward_item}`);
    wrap.find('.item_title').text(m.title);
    wrap.find('.item_description').html(m.description);

    item_wrap.empty();
    let sub_list = m_item.supply_amount.split(',');
    let _i = 0;
    for (let o of Object.values(sub_list)) {
        let i = o.split(':');
        let empty = document.createElement('span');
        empty.classList.add('item_empty');
        let item = document.createElement('span');
        item.classList.add('item_image');
        item.classList.add(`item_image_${i[0]}`);
        item.setAttribute('id', `ns_button_package_sub_item_${i[0]}`);
        let count = document.createElement('span');
        count.classList.add('item_count');
        count.innerText = 'x' + i[1];
        empty.appendChild(count);
        empty.appendChild(item);
        item_wrap.append(empty);

        let button_id = `package_sub_item_${i[0]}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, null, 'package_popup');
        ns_button.buttons[button_id].mouseUp = function ()
        {
            ns_dialog.setDataOpen('reward_information', { m_item_pk: i[0] });
        }
        dialog.buttons.push(ns_button.buttons[button_id]);
        _i++;
    }
    for (;_i < 8; _i++) {
        let empty = document.createElement('span');
        empty.classList.add('item_empty');
        item_wrap.append(empty);
    }
    ns_button.buttons.buy_package.obj.find('.qbig').text(ns_util.numberFormat(m.price));

    ns_engine.game_data.first_popup_package = null; // 비우기
}

ns_dialog.dialogs.package_popup.setPage = function ()
{
    let dialog = ns_dialog.dialogs.package_popup;
    dialog.cont_obj.package_page.empty();
    dialog.list = [];
    for (let o of Object.values(ns_engine.game_data.package_data)) {
        let span = document.createElement('span');
        dialog.cont_obj.package_page.append(span);
        if (ns_util.math(o.m_package_pk).eq(dialog.data.m_package_pk)) {
            span.classList.add('selected');
            // continue;
        }
        span.setAttribute('id', `ns_button_package_view_${o.m_package_pk}`);
        let button_id = `package_view_${o.m_package_pk}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, null, 'package_popup');
        ns_button.buttons[button_id].mouseUp = function ()
        {
            ns_dialog.close('package_popup');
            setTimeout(() => {
                ns_dialog.setDataOpen('package_popup', { m_package_pk: o.m_package_pk });
            }, 30);
        }
        dialog.buttons.push(ns_button.buttons[button_id]);
        dialog.list.push(o.m_package_pk);
    }
    dialog.cont_obj.package_page_wrap.hide();
    if (dialog.list.length > 1) {
        dialog.cont_obj.package_page_wrap.show();
    }
}

ns_dialog.dialogs.package_popup.erase = function ()
{
    this.data = null;
    this.package_data = null;
    this.list = [];
    this.buttonClear();
    this.cont_obj.content.find('.main_content').empty();
}

ns_dialog.dialogs.package_popup.timerHandler = function (_recursive) {
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.package_popup.timerHandlerReal, 1000, true, 1000);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.package_popup.timerHandlerReal = function () {
    let dialog = ns_dialog.dialogs.package_popup;
    if (dialog?.package_data?.end_date) {
        let remain_time = ns_util.math(ns_util.toInteger(dialog.package_data.end_date)).minus(ns_timer.now()).number;
        if (dialog.cont_obj.content.find('.package_limit_time').element) {
            dialog.cont_obj.content.find('.package_limit_time .limit_time').text(ns_util.secondToDateTime(remain_time));
        }
    }
}

ns_button.buttons.package_popup_close = new nsButtonSet('package_popup_close', 'button_package_close', 'package_popup', {base_class: ns_button.buttons.common_close});
ns_button.buttons.package_popup_sub_close = new nsButtonSet('package_popup_sub_close', 'button_full', 'package_popup', {base_class: ns_button.buttons.common_sub_close});

ns_button.buttons.package_page_prev = new nsButtonSet('package_page_prev', 'button_package_page_prev', 'package_popup');
ns_button.buttons.package_page_prev.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.package_popup;
    dialog.page--;
    if (dialog.page < 0) {
        dialog.page = dialog.list.length - 1;
    }
    let m_package_pk = dialog.list[dialog.page];
    ns_dialog.close('package_popup');
    setTimeout(() => {
        ns_dialog.setDataOpen('package_popup', { m_package_pk: m_package_pk });
    }, 30);
}

ns_button.buttons.package_page_next = new nsButtonSet('package_page_next', 'button_package_page_next', 'package_popup');
ns_button.buttons.package_page_next.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.package_popup;
    dialog.page++;
    if (dialog.page > dialog.list.length - 1) {
        dialog.page = 0;
    }
    let m_package_pk = dialog.list[dialog.page];
    ns_dialog.close('package_popup');
    setTimeout(() => {
        ns_dialog.setDataOpen('package_popup', { m_package_pk: m_package_pk });
    }, 30);

}

ns_button.buttons.buy_package = new nsButtonSet('buy_package', 'button_default', 'package_popup');
ns_button.buttons.buy_package.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.package_popup;
    let m = ns_cs.m.package[dialog.data.m_package_pk];
    let qbig = ns_cs.d.cash['qbig']?.v ?? 0;

    let okFunc = {}, message = '';
    if (ns_util.math(m.price).gt(qbig)) {
        message = ns_i18n.t('msg_buy_qbig_confirm'); // 큐빅이 부족합니다.<br /><br />큐빅을 구매하시겠습니까?
        okFunc = () =>
        {
            ns_dialog.close('package_popup');
            ns_engine.buyQbig();
        }
    } else {
        message = ns_i18n.t('msg_limit_package_buy_confirm', [ns_util.numberFormat(m.price)]); // 한정 패키지를 {{1}}에 구매하시겠습니까?
        okFunc = () =>
        {
            ns_xhr.post('/api/package/buy', { m_package_pk: dialog.data.m_package_pk }, (_data, _status) =>
            {
                if(! ns_xhr.returnCheck(_data)) {
                    return;
                }
                ns_dialog.close('package_popup');

                let _text = ns_i18n.t('msg_item_buy_with_qbig', [
                    ns_i18n.t(`item_title_${m.reward_item}`), 1, ns_util.numberFormat(m.price)
                ]);
                ns_dialog.setDataOpen('message', { text : _text });
            }, { useProgress: true });
        }
    }

    ns_dialog.setDataOpen('confirm', { text: message, okFunc: okFunc });
}

/* ********************** */
ns_dialog.dialogs.toast_message = new nsDialogSet('toast_message', 'dialog_toast_message', 'size-action', { do_close_all: false });
ns_dialog.dialogs.toast_message.type = null;

ns_dialog.dialogs.toast_message.cacheContents = function()
{
    this.cont_obj.dialog_wrap = new nsObject('.dialog_wrap', this.obj);
    this.cont_obj.content_action_wrap = new nsObject('.content_action_wrap', this.obj);
    this.cont_obj.content_message = new nsObject('.content_message', this.obj);
    this.cont_obj.toast_icon = new nsObject('.toast_icon', this.obj);
}

ns_dialog.dialogs.toast_message.draw = function()
{
    let type = this.data.type;
    let message = '';

    switch (type) {
        case 'construction':
            message = ns_i18n.t('toast_construction', [ns_i18n.t(`build_title_${this.data.pk}`)]); // {{1}} 건설이 완료되었습니다.
            break;
        case 'technique':
            message = ns_i18n.t('toast_technique', [ns_i18n.t(`tech_title_${this.data.pk}`), this.data.level]); // `[{{1}} Lv.{{2}}] 기술이 개발되었습니다.`;
            break;
        case 'explorer':
            if (this.data['reward']['type'] === 'none') {
                message = ns_i18n.t('toast_explorer_none'); // 탐색을 통해 아무것도 발견하지 못했습니다.
            } else if (this.data['reward']['type'] === 'hero') {
                message = ns_i18n.t('toast_explorer_hero'); // 탐색을 통해 영웅을 발견하였습니다.
            } else if (this.data['reward']['type'] === 'item') {
                message = ns_i18n.t('toast_explorer_item'); // 탐색을 통해 아이템을 발견하였습니다.
            } else {
                message = ns_i18n.t('toast_explorer_resource'); // 탐색을 통해 자원을 발견하였습니다.
            }
            break;
        case 'invite':
            type = 'explorer';
            message = (this.data['result'] === true) ? ns_i18n.t('toast_invite_success') : ns_i18n.t('toast_invite_fail'); // 영웅 초빙에 성공하였습니다. : 영웅 초빙에 실패하였습니다.
            break;
        case 'army':
            message = ns_i18n.t('toast_army_training', [ns_i18n.t(`army_title_${this.data.pk}`)]); // [{{1}}] 훈련이 완료되었습니다.`;
            break;
        case 'medical':
            type = 'army';
            message = ns_i18n.t('toast_army_medical', [ns_i18n.t(`army_title_${this.data.pk}`)]); // [{{1}}] 치료가 완료되었습니다.
            break;
        case 'fortification':
            message = ns_i18n.t('toast_fortification', [ns_i18n.t(`fort_title_${this.data.pk}`)]); // [{{1}}] 방어시설 설치가 완료되었습니다.
            break;
        case 'fortification_valley':
            type = 'fortification';
            message = ns_i18n.t('toast_fortification', [ns_i18n.t(`fort_title_${this.data.pk}`)]); // [{{1}}] 방어시설 설치가 완료되었습니다.
            break;
        case 'army_decr':
            message = ns_i18n.t('toast_rebel_warning', [this.data.posi_pk]); // [{{1}}]에서 반란이 일어났습니다.
            break;
        case 'battle':
            switch (this.data.result) {
                case 'battle_attack_victory': message = ns_i18n.t('toast_battle_attack_victory', [this.data.posi_pk]); break; // [{{1}}] 공격 전투에서 승리하였습니다.
                case 'battle_attack_defeat': message = ns_i18n.t('toast_battle_attack_defeat', [this.data.posi_pk]); break; // [{{1}}] 공격 전투에서 패배하였습니다.
                case 'battle_defence_victory': message = ns_i18n.t('toast_battle_defense_victory', [this.data.posi_pk]); break; // [{{1}}] 방어 전투에서 승리하였습니다.
                case 'battle_defence_defeat': message = ns_i18n.t('toast_battle_defense_defeat', [this.data.posi_pk]); break; // [{{1}}] 방어 전투에서 패배하였습니다.
            }
            break;
        case 'return_troop':
            if (this.data['result'] === 'return_finish_4') { // 수송
                message = ns_i18n.t('toast_return_troop'); // 출병했던 부대가 복귀했습니다.
            } else if (this.data['result'] === 'return_finish_5') { // 지원
                message = ns_i18n.t('toast_return_troop'); // 출병했던 부대가 복귀했습니다.
            } else if (this.data['result'] === 'return_finish_6') { // 보급
                message = ns_i18n.t('toast_return_troop'); // 출병했던 부대가 복귀했습니다.
            } else if (this.data['result'] === 'return_finish_7') { // 정찰
                message = ns_i18n.t('toast_return_troop'); // 출병했던 부대가 복귀했습니다.
            } else {
                message = ns_i18n.t('toast_return_troop'); // 출병했던 부대가 복귀했습니다.
            }
            break;
        case 'hero_bid_success':
            message = ns_i18n.t('toast_hero_bid_success', [this.data.name]); // [{{1}}] 영웅 입찰에 성공했습니다.
            break;
        case 'hero_bid_fail':
            message = ns_i18n.t('toast_hero_bid_fail', [this.data.name]); // [{{1}}] 영웅 입찰에 실패했습니다.
            break;
        /*case 'raid':
            message = '황건요새가 발견되었습니다.';
            break;*/
        case 'scout':
            if (this.data['result'] === 'success') {
                message = ns_i18n.t('toast_scout_success', [this.data.posi_pk]);
            } else if (this.data['result'] === 'failure') {
                message = ns_i18n.t('toast_scout_failure', [this.data.posi_pk]);
            }
            break;
    }

    this.cont_obj.toast_icon.addCss(type).addCss('icon_animation');
    this.cont_obj.content_action_wrap.addCss('wrap_open_animation');
    this.cont_obj.content_message.text(message);
    ns_sound.play('toast');

    this.close_event = setTimeout(() => {
        this.closeEvent();
    }, 5000);
}

ns_dialog.dialogs.toast_message.closeEvent = function ()
{
    let dialog = ns_dialog.dialogs.toast_message;
    dialog.cont_obj.content_message.text('');
    dialog.cont_obj.content_action_wrap.addCss('wrap_close_animation');
    dialog.cont_obj.toast_icon.addCss('icon_animation_close');
    setTimeout(() => {
        ns_toast.close();
    }, 300);
}

ns_dialog.dialogs.toast_message.erase = function()
{
    let type = this.data.type;
    this.cont_obj.toast_icon.removeCss([type, 'icon_animation', 'icon_animation_close']);
    this.cont_obj.content_action_wrap.removeCss(['wrap_open_animation', 'wrap_close_animation']);
    this.data = null;
}

ns_button.buttons.toast_action = new nsButtonSet('toast_action', 'button_full', 'toast_message');
ns_button.buttons.toast_action.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.toast_message;
    let data = dialog.data;
    clearTimeout(dialog.close_event);
    dialog.closeEvent();
    // 후처리 이벤트
    switch (data.type) {
        case 'construction':
            if (ns_engine.game_data.curr_view === 'world') {
                ns_button.buttons.main_view_world.mouseUp();
            }
            ns_dialog.setDataOpen('build_' + ns_cs.m.buil[data.pk].alias, { castle_pk: data.castle_pk, castle_type: data.castle_type });
            break;
        case 'army':
        case 'medical':
        case 'technique':
            if (ns_engine.game_data.curr_view === 'world') {
                ns_button.buttons.main_view_world.mouseUp();
            }
            ns_dialog.setDataOpen('build_' + ns_cs.m.buil[data.m_buil_pk].alias, { castle_pk: data.castle_pk, castle_type: data.castle_type });
            break;
        case 'fortification':
            if (ns_engine.game_data.curr_view === 'world') {
                ns_button.buttons.main_view_world.mouseUp();
            }
            ns_dialog.setDataOpen('build_' + ns_cs.m.buil[data.m_buil_pk].alias, { castle_pk: data.castle_pk, castle_type: data.castle_type });
            ns_button.buttons.build_CastleWall_tab_fortification.mouseUp();
            break;
        case 'fortification_valley':
            if (ns_engine.game_data.curr_view !== 'world') {
                ns_button.buttons.main_view_world.mouseUp();
            }
            const [x, y] = data.posi_pk.split('x');
            ns_world.setPosition(x, y);
            // 서버 요청 시간 때문에 딜레이를 두는데 더 좋은 방법은 없나
            setTimeout(() => {
                let coords = ns_world.coords.get(data.posi_pk);
                ns_dialog.setDataOpen('world_detail', { coords: coords });
                ns_button.buttons.world_detail_fort.mouseUp();
            }, 300);
            break;
        case 'battle':
        case 'return_troop':
            ns_dialog.open('report');
            ns_button.buttons.report_tab2_battle.mouseUp();
            ns_dialog.dialogs.report.drawDetailView(data.pk);
            break;
        case 'hero_bid_success':
        case 'hero_bid_fail':
            ns_dialog.open('report');
            ns_button.buttons.report_tab2_misc.mouseUp();
            ns_dialog.dialogs.report.drawDetailView(data.pk);
            break;
        case 'explorer':
        case 'invite':
            if (ns_engine.game_data.curr_view === 'world') {
                ns_button.buttons.main_view_world.mouseUp();
            }
            ns_dialog.setDataOpen('build_' + ns_cs.m.buil[data.m_buil_pk].alias, { castle_pk: data.castle_pk, castle_type: data.castle_type });
            ns_button.buttons.build_ReceptionHall_tab_hero_encounter.mouseUp();
            break;
    }
}

ns_dialog.dialogs.world_goto_search = new nsDialogSet('world_goto_search', 'dialog_goto_search', 'size-goto', { do_close_all: false, do_content_scroll: false });

ns_dialog.dialogs.world_goto_search.cacheContents = function()
{
    this.cont_obj.goto_x = new nsObject('input[name=goto_x]', this.obj);
    this.cont_obj.goto_y = new nsObject('input[name=goto_y]', this.obj);
    this.cont_obj.goto_x.setEvent('input', (_e) => {
        let regexp = /[^\d.]|\.(?=.*\.)/g;
        let current_value = _e.target.value;
        let number_value = current_value.replace(regexp, "");
        this.cont_obj.goto_x.value(number_value);
    });
    this.cont_obj.goto_y.setEvent('input', (_e) => {
        let regexp = /[^\d.]|\.(?=.*\.)/g;
        let current_value = _e.target.value;
        let number_value = current_value.replace(regexp, "");
        this.cont_obj.goto_y.value(number_value);
    });
}

ns_dialog.dialogs.world_goto_search.draw = function()
{
    let [x, y] = ns_world.current_posi_pk.split('x');
    this.cont_obj.goto_x.value(x);
    this.cont_obj.goto_y.value(y);


}

ns_button.buttons.world_goto_search_close = new nsButtonSet('world_goto_search_close', 'button_round_close', 'world_goto_search', {base_class: ns_button.buttons.common_close});
ns_button.buttons.world_goto_search_sub_close = new nsButtonSet('world_goto_search_sub_close', 'button_full', 'world_goto_search', {base_class: ns_button.buttons.common_sub_close});

ns_button.buttons.goto_search_ok = new nsButtonSet('goto_search_ok', 'button_round_search', 'world_goto_search');
ns_button.buttons.goto_search_ok.mouseUp = function ()
{
    let dialog = ns_dialog.dialogs.world_goto_search;
    let x = dialog.cont_obj.goto_x.value();
    let y = dialog.cont_obj.goto_y.value();
    if (isNaN(x) || isNaN(y)) {
        return;
    }

    x = (ns_util.math(x).lt(1)) ? 1 : x;
    y = (ns_util.math(y).lt(1)) ? 1 : y;
    x = (ns_util.math(x).gt(486)) ? 486 : x;
    y = (ns_util.math(y).gt(486)) ? 486 : y;

    ns_world.goto_map = true;
    ns_world.setPosition(x, y);
    ns_dialog.close('world_goto_search');
}

// 게임 이용등급
ns_dialog.dialogs.game_rating = new nsDialogSet('game_rating', 'dialog_pop', 'size-medium');

ns_dialog.dialogs.game_rating.cacheContents = function () {

}

ns_dialog.dialogs.game_rating.draw = function () {

}

ns_button.buttons.game_rating_close = new nsButtonSet('game_rating_close', 'button_pop_close', 'game_rating', { base_class: ns_button.buttons.common_close });
ns_button.buttons.game_rating_sub_close = new nsButtonSet('game_rating_sub_close', 'button_full', 'game_rating', { base_class: ns_button.buttons.common_sub_close });
