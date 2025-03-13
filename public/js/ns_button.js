class nsButton
{
    constructor ()
    {
        // 기본 설정
        this.buttons = {}; // 전체 버튼
        this.default_init_group = 'unknown';
        this.default_bounding_box_type = 'normal';
        this.default_play_effect = 'button';

        // 그룹 설정
        this.init_group = {};
        this.init_group_count = 0;
        this.init_obj_count = 0;

        this.toggle_group = {};
        this.toggle_group_count = 0;

        // 버튼 값
        this.current_button = null;
        this.current_button_fire = true;
        this.web_init = false;

        this.start_page_x = 0;
        this.start_page_y = 0;
        this.start_client_x = 0;
        this.start_client_y = 0;

        this.last_page_x = 0;
        this.last_page_y = 0;
        this.last_client_x = 0;
        this.last_client_y = 0;

        this.bounding_box_type = { big: [30, 30], normal: [20, 20], small: [14, 14] }
        this.current_bounding_box = { top: 0, bottom: 0, left: 0, right: 0 }

        this.tag_prefix = 'ns_button_';
    }

    init (_e)
    {
        this.buttonLoad(this.default_init_group);
    }

    buttonLoad (_group)
    {
        let ref_group = this.init_group[_group], load_count = 0;
        if (ref_group && typeof ref_group === 'object' && ref_group?.length) {
            for (let i = 0, j = ref_group.length; i < j; i++) {
                if (ref_group[i].loaded === false) {
                    ref_group[i].init();
                    load_count++;
                    this.init_obj_count++;
                }
            }
        }

        return load_count;
    }

    isMove ()
    {
        return Math.abs(ns_button.start_client_x - ns_button.last_client_x) > 5 && Math.abs(ns_button.start_client_y - ns_button.last_client_y) > 5;
    }

    dispatcher (_e)
    {
        // 마우스인 경우 좌클릭만 동작 하도록
        if (! ns_engine.trigger.is_touch_device && _e.button !== 0) {
            return false;
        }
        // 매직큐브 애니메이션 처리 중 버튼 중단
        if (ns_dialog.dialogs.magic_cube?.is_start_animate === true) {
            return false;
        }
        // xhr 호출 중 버튼 중단
        if (ns_engine.xhr.fg_xhr_progress) {
            let limit_fire = true;
            try {
                let button_name = _e.currentTarget.id.substring(ns_button.tag_prefix.length);
                if (ns_button.buttons[button_name] && ns_button.buttons[button_name].unlimit_fire) {
                    limit_fire = false;
                }
            } catch (error) {
                console.error(error);
            }

            if (limit_fire) {
                return;
            }
        }

        if (_e.target !== _e.currentTarget) {
            if (_e.stopImmediatePropagation) {
                _e.stopImmediatePropagation();
            }
            return;
        }

        let page_x = (! ns_engine.trigger.is_touch_device) ? _e.pageX : _e.changedTouches[0].pageX;
        let page_y = (! ns_engine.trigger.is_touch_device) ? _e.pageY : _e.changedTouches[0].pageY;
        let client_x = (! ns_engine.trigger.is_touch_device) ? _e.clientX : _e.changedTouches[0].clientX;
        let client_y = (! ns_engine.trigger.is_touch_device) ? _e.clientY : _e.changedTouches[0].clientY;

        ns_button.last_page_x = page_x;
        ns_button.last_page_y = page_y;
        ns_button.last_client_x = client_x;
        ns_button.last_client_y = client_y;

        let id = _e.currentTarget.id;
        let button_name = id.substring(ns_button.tag_prefix.length);

        ns_button.current_button = ns_button.buttons[button_name];
        if (ns_button?.current_button && ns_button.current_button.enable !== true && ![ns_engine.cfg.mouse_enter_event_type, ns_engine.cfg.mouse_leave_event_type].includes(_e.type)) {
            return;
        }

        if (_e.type === ns_engine.cfg.mouse_down_event_type) {
            if (! ns_button.buttons[button_name]) {
                return;
            }
            ns_button.current_button_fire = true;

            // 버블링 해결을 위해
            _e.preventDefault();
            // _e.stopPropagation();
            // _e.stopImmediatePropagation();

            ns_button.start_page_x = page_x;
            ns_button.start_page_y = page_y;
            ns_button.start_client_x = client_x;
            ns_button.start_client_y = client_y;

            switch (ns_button.current_button.bounding_box_type) {
                case 'fit':
                    ns_button.current_bounding_box.top = _e.currentTarget.offsetTop;
                    ns_button.current_bounding_box.right = ns_button.current_bounding_box.left + _e.currentTarget.offsetWidth;
                    ns_button.current_bounding_box.bottom = ns_button.current_bounding_box.top + _e.currentTarget.offsetHeight;
                    ns_button.current_bounding_box.left = _e.currentTarget.offsetLeft;
                    break;
                default :
                    let size = ns_button.bounding_box_type[ns_button.current_button.bounding_box_type];
                    ns_button.current_bounding_box.l = page_x - (parseInt(size[0])/2);
                    ns_button.current_bounding_box.t = page_y - (parseInt(size[1])/2);
                    ns_button.current_bounding_box.r = ns_button.current_bounding_box.left + size[0];
                    ns_button.current_bounding_box.b = ns_button.current_bounding_box.top + size[1];
                    break;
            }

            if (ns_button.current_button.event_loaded === false) {
                ns_button.current_button.event_loaded = true;
                ns_button.current_button.obj.element.addEventListener(ns_engine.cfg.mouse_down_event_type, ns_button.dispatcher);
                ns_button.current_button.obj.element.addEventListener(ns_engine.cfg.mouse_move_event_type, ns_button.dispatcher);
                ns_button.current_button.obj.element.addEventListener(ns_engine.cfg.mouse_up_event_type, ns_button.dispatcher);
                ns_button.current_button.obj.element.addEventListener(ns_engine.cfg.mouse_out_event_type, ns_button.dispatcher);
            }

            ns_button.current_button.mouseDown(_e);
        } else if (_e.type === ns_engine.cfg.mouse_move_event_type) {
            if (ns_button.current_button_fire !== true) {
                return;
            }
            _e.preventDefault();

            let out = '';
            let x_adjust = client_x - ns_button.start_client_x;
            let y_adjust = client_y - ns_button.start_client_y;

            // adjust 가 이동거리가 되기 때문에 최초 위치가 기준이 된다.
            page_x = ns_button.start_client_x + x_adjust;
            page_y = ns_button.start_client_y + y_adjust;

            ns_button.last_client_x = page_x;
            ns_button.last_client_y = page_y;

            if (page_x < ns_button.current_bounding_box.left) {
                out += 'left(' + page_x + '),';
            } else if (page_x > ns_button.current_bounding_box.right) {
                out += 'right(' + page_x + '),';
            }

            if (page_y < ns_button.current_bounding_box.top) {
                out += 'top(' + page_y + '),';
            } else if (page_y > ns_button.current_bounding_box.bottom) {
                out += 'bottom(' + page_y + '),';
            }

            /*if (page_x < ns_button.current_bounding_box.left || page_x > ns_button.current_bounding_box.right || page_y < ns_button.current_bounding_box.top || page_y > ns_button.current_bounding_box.bottom) {
                if (ns_engine.trigger.is_touch_device !== true) {
                    ns_button.current_button_fire = false;
                }
            }*/

            if (ns_engine.trigger.is_touch_device === true) {
                let touch = _e.changedTouches[0];
                if (Math.abs(ns_button.start_client_x - touch.clientX) > 8 || Math.abs(ns_button.start_client_y - touch.clientY) > 8) {
                    ns_button.current_button_fire = false;
                }
            }

            ns_button.current_button.mouseMove(_e);
        } else if (_e.type === ns_engine.cfg.mouse_up_event_type) {
            _e.preventDefault();

            if (ns_button.current_button_fire === true) {
                if (ns_button.isMove()) {
                    return;
                }
                if (ns_button.current_button_fire.hide_keyboard) {
                    // qbw_util.hideKeyboard();
                }
                ns_button.current_button.mouseUp(_e);
                if (! ns_button.current_button.sound_mute && ns_button.current_button.sound_button) {
                    ns_sound.play(ns_button.current_button.sound_button);
                }
            }
        } else if (_e.type === ns_engine.cfg.mouse_out_event_type) {
            ns_button.current_button_fire = false;
        } else if (_e.type === ns_engine.cfg.mouse_enter_event_type) {
            _e.preventDefault();

            ns_button.current_button.mouseEnter(_e);
            if (! ns_button.current_button.sound_mute && ns_button.current_button.sound_rollover) {
                ns_sound.play(ns_button.current_button.sound_rollover);
            }
        } else if (_e.type === ns_engine.cfg.mouse_leave_event_type) {
            _e.preventDefault();

            ns_button.current_button.mouseLeave(_e);
        } else if (_e.type === ns_engine.cfg.mouse_out_event_type) {
            ns_button.current_button_fire = false;
            _e.preventDefault();

            ns_button.current_button.mouseOut(_e);
        } else {
            console.error('unknown event type : ' + _e.type);
        }
    }

    toggleGroupSingle (_button)
    {
        let select_tag_id = [];
        select_tag_id.push(_button.tag_id);

        for (let o of Object.values(ns_button.toggle_group[_button.toggle_group])) {
            if (o.tag_id === _button.tag_id) {
                o.setClicked();
            } else if (o.clicked) {
                o.unsetClicked();
            }
        }
        return select_tag_id;
    }

    toggleGroupValue (_group_name)
    {
        return Object.values(ns_button.toggle_group[_group_name]).filter(b => b.clicked).map(b => b.tag_id);
    }
}

class nsButtonSet
{
    constructor (_tag_id, _button_css, _init_group, _options)
    {
        this.tag_id = _tag_id;
        this.button_css = _button_css;
        this.init_group = ns_button.default_init_group;
        this.options = _options;
        this.data = null;

        this.button_icon_css = null;
        this.bounding_box_type = ns_button.default_bounding_box_type;
        this.toggle_group = null;
        this.toggle_group_type = 'single'; // or multi
        this.base_class = null;
        this.do_stop_propagation = false;
        this.do_prevent_default = true;
        this.unlimit_fire = false;
        this.play_effect = ns_button.default_play_effect;
        this.hide_keyboard = true;

        this.loaded = false;
        this.event_loaded = false;
        this.obj = null;

        this.sound_mute = false;
        this.sound_button = 'button_4';
        this.sound_rollover = null;

        this.enable = false;
        this.clicked = false;

        if (_init_group) {
            this.init_group = _init_group;
        }

        if (_options && typeof _options === 'object') {
            if (_options?.button_icon_css) {
                this.button_icon_css = _options.button_icon_css;
            }

            if (_options?.bounding_box_type) {
                this.bounding_box_type = _options.bounding_box_type;
            }

            if (_options?.toggle_group) {
                this.toggle_group = _options.toggle_group;
            }

            if (_options?.toggle_group_type) {
                this.toggle_group_type = _options.toggle_group_type;
            }

            if (_options?.base_class) {
                this.base_class = _options.base_class;
            }

            if (_options?.do_stop_propagation) {
                this.do_stop_propagation = _options.do_stop_propagation;
            }

            if (_options?.do_prevent_default) {
                this.do_prevent_default = _options.do_prevent_default;
            }

            if (_options?.unlimit_fire) {
                this.unlimit_fire = _options.unlimit_fire;
            }

            if (_options?.play_effect) {
                this.play_effect = _options.play_effect;
            }

            if (_options?.hide_keyboard) {
                this.hide_keyboard = _options.hide_keyboard;
            }

            if (_options?.sound_mute) {
                this.sound_mute = _options.sound_mute;
            }

            if (_options?.sound_button) {
                this.sound_button = _options.sound_button;
            }

            if (_options?.sound_rollover) {
                this.sound_rollover = _options.sound_rollover;
            }

            if (_options?.data) {
                this.data = _options.data;
            }
        }

        // initGroup
        if (! ns_button.init_group?.[this.init_group]) {
            ns_button.init_group[this.init_group] = [];
            ns_button.init_group_count++;
        }
        ns_button.init_group[this.init_group].push(this);

        // toggleGroup
        if (this.toggle_group) {
            if (! ns_button.toggle_group?.[this.toggle_group]) {
                ns_button.toggle_group[this.toggle_group] = [];
                ns_button.toggle_group_count++;
            }
            ns_button.toggle_group[this.toggle_group].push(this);
        }

        ns_button.buttons[_tag_id] = this;
        this.init();
    }

    init (_e, _parent_object)
    {
        let button_selector = '#' + ns_button.tag_prefix + this.tag_id;
        this.obj = new nsObject(button_selector, _parent_object);
        if (! this.obj.element) {
            return;
        }
        this.addCss();
        // 이벤트 설정
        this.obj.element.addEventListener(ns_engine.cfg.mouse_down_event_type, ns_button.dispatcher);
        this.obj.element.addEventListener(ns_engine.cfg.mouse_up_event_type, ns_button.dispatcher);
        this.obj.element.addEventListener(ns_engine.cfg.mouse_move_event_type, ns_button.dispatcher);
        this.obj.element.addEventListener(ns_engine.cfg.mouse_out_event_type, ns_button.dispatcher);
        this.obj.element.addEventListener(ns_engine.cfg.mouse_enter_event_type, ns_button.dispatcher);
        this.obj.element.addEventListener(ns_engine.cfg.mouse_leave_event_type, ns_button.dispatcher);
    }

    addCss (_postfix)
    {
        _postfix = (! _postfix) ? '' : '_' + _postfix;
        if (this.button_css) {
            this.obj.element.classList.add(this.button_css + _postfix);
        }

        if (this.button_icon_css) {
            this.obj.element.classList.add(this.button_icon_css + _postfix);
        }

        this.loaded = true;
        this.enable = true;
    }

    removeCss (_postfix)
    {
        _postfix = (! _postfix) ? '' : '_' + _postfix;
        this.obj.element.classList.remove(this.button_css + _postfix);

        if (this.button_icon_css) {
            this.obj.element.classList.remove(this.button_icon_css + _postfix);
        }
    }

    mouseUp (_e, _recursive)
    {
        if (this.enable === false) {
            return;
        }
        if (this.base_class && !_recursive) {
            this.base_class.mouseUp.call(this, _e, true);
        }
    }

    mouseMove (_e, _recursive)
    {
        if (this.base_class && !_recursive) {
            this.base_class.mouseMove.call(this, _e, true);
        }
    }

    mouseEnter (_e, _recursive)
    {
        if (this.base_class && !_recursive) {
            this.base_class.mouseEnter.call(this, _e, true);
        }
    }

    mouseLeave (_e, _recursive)
    {
        if (this.base_class && !_recursive) {
            this.base_class.mouseLeave.call(this, _e, true);
        }
    }

    mouseDown (_e, _recursive)
    {
        if (this.base_class && !_recursive) {
            this.base_class.mouseDown.call(this, _e, true);
        }
    }

    mouseOut (_e, _recursive)
    {
        if (this.base_class && !_recursive) {
            this.base_class.mouseOut.call(this, _e, true);
        }
    }

    setEnable ()
    {
        if (this.enable !== true) {
            this.obj.element.classList.remove('disable');
            this.enable = true;
        }
    }

    setDisable ()
    {
        if (this.enable !== false) {
            this.obj.element.classList.add('disable');
            this.enable = false;
        }
    }

    setClicked ()
    {
        if (this.clicked !== true) {
            this.obj.element.classList.add('clicked');
            this.clicked = true;
        }
    }

    unsetClicked ()
    {
        if (this.clicked !== false) {
            this.obj.element.classList.remove('clicked');
            this.clicked = false;
        }
    }

    toggleClicked ()
    {
        if (this.clicked) {
            this.unsetClicked();
        } else {
            this.setClicked();
        }
    }

    destroy ()
    {
        if (ns_button.buttons[this.tag_id]) {
            this.obj.element.removeEventListener(ns_engine.cfg.mouse_down_event_type, ns_button.dispatcher);
            this.obj.element.removeEventListener(ns_engine.cfg.mouse_up_event_type, ns_button.dispatcher);
            this.obj.element.removeEventListener(ns_engine.cfg.mouse_move_event_type, ns_button.dispatcher);
            this.obj.element.removeEventListener(ns_engine.cfg.mouse_out_event_type, ns_button.dispatcher);
            this.obj.element.removeEventListener(ns_engine.cfg.mouse_enter_event_type, ns_button.dispatcher);
            this.obj.element.removeEventListener(ns_engine.cfg.mouse_leave_event_type, ns_button.dispatcher);
            ns_button.buttons[this.tag_id] = null;
            delete ns_button.buttons[this.tag_id];
        }
    }
}
let ns_button = new nsButton();