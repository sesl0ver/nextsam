class nsCastle
{
    constructor () {
        this.init_castle = false;
        this.castle_mode = 0; // 0: 일반, 1: 건물이동, 2: 튜토리얼

        // 건물 이동사 사용
        this.move_data = {};

        this.obj_width = 150;
        this.obj_height = 92;

        this.objs = {};

        this.scroll = {
            is_down: false,
            start_x: 0,
            start_y: 0,
            current_x: 0,
            current_y: 0,
            transform_x: 0,
            transform_y: 0
        };
    }

    init ()
    {
        if (this.init_castle !== false) {
            return;
        }
        this.objs.wrap_terr = new nsObject('#wrap_terr');
        this.objs.terr_bg = new nsObject('#terr');

        this.objs.main_top_left = new nsObject('.main_top_left');
        this.objs.main_top_right = new nsObject('.main_top_right');
        this.objs.main_bottom_left = new nsObject('.main_bottom_left');
        this.objs.main_bottom_right = new nsObject('.main_bottom_right');

        // TODO 모바일 디바이스에서 터치 버그 있음.
        this.objs.wrap_terr.setEvent([
            ns_engine.cfg.mouse_down_event_type,
            ns_engine.cfg.mouse_up_event_type,
            ns_engine.cfg.mouse_move_event_type,
            ns_engine.cfg.mouse_leave_event_type], (_e) =>
        {
            this.dispatcher(_e);
        });

        let w_limit = ns_util.math(this.objs.terr_bg.element.clientWidth).minus(ns_engine.game_object.main_stage.element.clientWidth).mul(-1).number;

        this.scrollSet(w_limit, 0); // 초기 위치

        this.init_castle = true;
    }

    dispatcher (_e)
    {
        if (! ns_engine.trigger.is_touch_device && _e.button !== 0) { // 좌클릭인 경우에만 동작
            return;
        }
        if (_e.type === ns_engine.cfg.mouse_down_event_type) {
            this.scroll.is_down = true;
            this.scroll.start_x = ns_engine.trigger.is_touch_device ? _e.changedTouches[0].pageX : _e.pageX;
            this.scroll.start_y = ns_engine.trigger.is_touch_device ? _e.changedTouches[0].pageY : _e.pageY;
            this.scroll.transform_x = this.scrollCurrent().x;
            this.scroll.transform_y = this.scrollCurrent().y;
        } else if (_e.type === ns_engine.cfg.mouse_up_event_type) {
            this.scroll.is_down = false;
        } else if (_e.type === ns_engine.cfg.mouse_move_event_type) {
            this.scrollMove(_e);
        } else if (_e.type === ns_engine.cfg.mouse_leave_event_type) {
            this.scroll.is_down = false;
        }
    }

    scrollCurrent ()
    {
        let matrix = new WebKitCSSMatrix(getComputedStyle(this.objs.terr_bg.element).getPropertyValue("transform"));
        return { x: parseInt(String(matrix.m41)) ?? 0, y: parseInt(String(matrix.m42)) ?? 0 }
    }

    scrollSet (_x, _y)
    {
        this.objs.terr_bg.element.style.transform = `translate(${_x}px, ${_y}px)`;
    }

    scrollMove (_e)
    {
        if (! this.scroll.is_down) {
            return;
        }
        _e.stopPropagation();
        _e.preventDefault();
        const x = ns_engine.trigger.is_touch_device ? _e.changedTouches[0].pageX : _e.pageX;
        const y = ns_engine.trigger.is_touch_device ? _e.changedTouches[0].pageY : _e.pageY;
        const w_x = x - this.scroll.start_x;
        const w_y = y - this.scroll.start_y;
        this.scroll.current_x = this.scroll.transform_x + w_x;
        this.scroll.current_y = this.scroll.transform_y + w_y;

        let w_limit = ns_util.math(this.objs.terr_bg.element.clientWidth).minus(ns_engine.game_object.main_stage.element.clientWidth).mul(-1).number;
        let h_limit = ns_util.math(this.objs.terr_bg.element.clientHeight).minus(ns_engine.game_object.main_stage.element.clientHeight).mul(-1).number;

        if (this.scroll.current_x > 0) {
            this.scroll.current_x = 0;
        }
        if (this.scroll.current_y > 0) {
            this.scroll.current_y = 0;
        }
        if (ns_util.math(this.scroll.current_x).lt(w_limit)) {
            this.scroll.current_x = w_limit;
        }
        if (ns_util.math(this.scroll.current_y).lt(h_limit)) {
            this.scroll.current_y = h_limit;
        }

        this.scrollSet(this.scroll.current_x, this.scroll.current_y);
    }

    getPosition (width, height)
    {
        return {
            x: ns_util.toInteger(((width * -1) + (ns_castle.obj_width / 2) + (ns_engine.game_object.main_stage.element.clientWidth / 2)) / ns_castle.obj_width),
            y: ns_util.toInteger(((height * -1) + (ns_castle.obj_height / 2) + (ns_engine.game_object.main_stage.element.clientHeight / 2) - ns_castle.obj_height) / ns_castle.obj_height)
        }
    }

    checkBuildMove (_castle_type, _cast_pk, _main = false)
    {
        if ((_main !== true && this.move_data['castle_type'] !== _castle_type) || (_castle_type === 'bdic' && ['1', '2'].includes(_cast_pk.toString()))) {
            return false;
        }
        let bd_c = ns_cs.d[_castle_type];
        if (! bd_c[_cast_pk]) {
            return (_main !== true);
        }
        if (bd_c[_cast_pk]['assign_hero_pk']) {
            // 영웅이 배속 중에 훈련, 연구, 탐색, 초빙, 치료를 진행하면 배속 해제가 불가능함.
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_building_move_assign_hero_error')); // 영웅이 배속이 되어있는 건물은 이동 할 수 없습니다.
            return false;
        }
        if (bd_c[_cast_pk]['status'] !== 'N') {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_building_move_status_error')); // 건설/업그레이드 중인 건물은 이동 할 수 없습니다.
            return false;
        }
        return true;
    }

    setBuildMove (_castle_type, _main_cast_pk)
    {
        if (! this.checkBuildMove(_castle_type, _main_cast_pk, true)) {
            return;
        }
        this.castle_mode = 1;

        this.setMoveButtons(true);

        this.move_data['castle_type'] = _castle_type;
        this.move_data['main_cast_pk'] = _main_cast_pk;
        ns_engine.game_object.wrap_terr.addCss(`${_castle_type}_move`);
        new nsObject(`#ns_${_castle_type}_${_main_cast_pk}`).addCss('move_selected');
    }

    unsetBuildMove ()
    {
        new nsObject(`#ns_${this.move_data['castle_type']}_${this.move_data['sub_cast_pk']}`).removeCss('move_selected');
        this.move_data['sub_cast_pk'] = null;
        ns_castle.setTileTitle(ns_cs.d.lord['setting']['building_title'] === 'Y');
    }

    cancelBuildMove ()
    {
        new nsObject(`#ns_${this.move_data['castle_type']}_${this.move_data['main_cast_pk']}`).removeCss('move_selected');
        if (this.move_data['sub_cast_pk']) {
            new nsObject(`#ns_${this.move_data['castle_type']}_${this.move_data['sub_cast_pk']}`).removeCss('move_selected');
        }
        this.setMoveButtons(false);
        ns_engine.game_object.wrap_terr.removeCss(`${this.move_data['castle_type']}_move`);
        this.castle_mode = 0;
        this.move_data = {};
        ns_castle.setTileTitle(ns_cs.d.lord['setting']['building_title'] === 'Y');
    }

    updateBuildMove (_castle_type, _sub_cast_pk)
    {
        if (! this.checkBuildMove(_castle_type, _sub_cast_pk)) {
            return;
        }
        // 영역(영내, 영외) 확인, 대전, 성벽은 이동불가.
        if (this.move_data['castle_type'] !== _castle_type || (_castle_type === 'bdic' && ['1', '2'].includes(_sub_cast_pk.toString()))) {
            return;
        }
        // main 값과 같으면 취소
        if (this.move_data['main_cast_pk'] === _sub_cast_pk) {
            this.cancelBuildMove();
            return;
        }
        new nsObject(`#ns_${_castle_type}_${_sub_cast_pk}`).addCss('move_selected');
        this.move_data['sub_cast_pk'] = _sub_cast_pk;
        ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_building_move_confirm'), // 선택한 건물의 위치를 서로 변경하시겠습니까?
            okFunc: () =>
            {
                ns_xhr.post('/api/build/move', this.move_data, (_data, _status) => {
                    if(! ns_xhr.returnCheck(_data)) {
                        this.unsetBuildMove();
                        return;
                    }
                    this.cancelBuildMove();
                });
            },
            noFunc: () =>
            {
                this.unsetBuildMove();
            }
        });
    }

    setMoveButtons(_hide_buttons = false)
    {
        if (_hide_buttons === true) {
            this.objs.main_top_left.hide();
            this.objs.main_top_right.hide();
            this.objs.main_bottom_left.hide();
            this.objs.main_bottom_right.hide();
            ns_engine.game_object.main_bottom_wrap.hide();
            // ns_button.buttons.main_toggle_title.obj.hide();
            ns_button.buttons.hero_deck_list.obj.hide();
            ns_button.buttons.main_build_move_cancel.obj.show();
            ns_button.buttons.main_lord_info.setDisable();
            ns_button.buttons.main_buy_qbig.setDisable();
            if (! ns_engine.game_object.wrap_terr.hasCss('hide_tile_title')) {
                ns_engine.game_object.wrap_terr.addCss('hide_tile_title');
            }
        } else {
            this.objs.main_top_left.show();
            this.objs.main_top_right.show();
            this.objs.main_bottom_left.show();
            this.objs.main_bottom_right.show();
            ns_engine.game_object.main_bottom_wrap.show();
            // ns_button.buttons.main_toggle_title.obj.show();
            ns_button.buttons.hero_deck_list.obj.show();
            ns_button.buttons.main_build_move_cancel.obj.hide();
            ns_button.buttons.main_lord_info.setEnable();
            ns_button.buttons.main_buy_qbig.setEnable();
            if (ns_engine.cfg.tile_title === true && ns_engine.game_object.wrap_terr.hasCss('hide_tile_title')) {
                ns_engine.game_object.wrap_terr.removeCss('hide_tile_title');
            }
        }
    }

    setTileTitle (_show = false)
    {
        if (_show === true) {
            ns_engine.game_object.wrap_terr.removeCss('hide_tile_title');
        } else {
            ns_engine.game_object.wrap_terr.addCss('hide_tile_title');
        }
    }
}

let ns_castle = new nsCastle();