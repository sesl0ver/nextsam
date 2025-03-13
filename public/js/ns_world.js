class nsWorld
{
    constructor () {
        this.init_world = false;

        this.current_posi_pk = null;
        this.last_update_posi_pk = null;
        this.last_update_count = 0;

        this.lud_max = 0;
        this.coords_count = 0;
        this.goto_map = false;
        this.previous_arrow = null;
        this.clicked_coords = false; // setPosition 이후 영지 정보를 바로 보여줘야 하는 경우
        this.coords = new Map();

        this.obj_width = 150;
        this.obj_height = 92;

        this.objs = {};
        this.troop_move = {};

        this.scroll = {
            is_down: false,
            start_x: 0,
            start_y: 0,
            current_x: 0,
            current_y: 0,
            transform_x: 0,
            transform_y: 0,
            client_x: 0,
            client_y: 0
        };
    }

    init ()
    {
        if (this.init_world !== false) {
            return;
        }
        this.objs.wrap_world = new nsObject('#wrap_world');
        this.objs.world_bg = new nsObject('#wrap_world .world_bg');
        this.objs.world_map = new nsObject('#wrap_world .world_map');
        this.objs.world_troop = new nsObject('#wrap_world .world_troop');
        this.objs.world_position_compass = new nsObject('#wrap_world .world_position_compass');
        this.objs.world_position_distance = new nsObject('#wrap_world .world_position_distance');
        this.objs.world_position_arrow = new nsObject('#wrap_world .world_position_arrow');

        this.objs.wrap_world.setEvent([
            ns_engine.cfg.mouse_down_event_type,
            ns_engine.cfg.mouse_up_event_type,
            ns_engine.cfg.mouse_move_event_type,
            ns_engine.cfg.mouse_leave_event_type], (_e) =>
        {
            this.dispatcher(_e);
        });

        this.initData();

        // 기본 위치
        let p = this.current_posi_pk.split('x');
        let x = p[0], y = p[1];

        this.setPosition(x, y);

        this.init_world = true;
    }

    initData ()
    {
        this.world_coords_array = null;
        this.init_world = false;

        this.coords_count = 0;
        this.coords = new Map();
    }

    positionUpdate (_x, _y)
    {
        let current_position;
        if (! _x || !_y) {
            current_position = this.getPosition(this.scrollCurrent().x, this.scrollCurrent().y);
        } else {
            current_position = { x: _x, y: _y };
        }

        this.current_posi_pk = current_position.x + 'x' + current_position.y;
        ns_button.buttons.goto_map.obj.text(`X:${current_position.x} Y:${current_position.y}`);

        let distance = ns_world.distanceValue(ns_engine.game_data.cpp, this.current_posi_pk, true);
        this.objs.world_position_distance.text(distance.length + ns_i18n.t('distance'));

        let update_coords = false;
        if (! this.last_update_posi_pk) {
            update_coords = true;
        } else {
            let [__x, __y] = this.last_update_posi_pk.split('x');
            if (ns_util.math(__x).minus(current_position.x).abs().gte(2) || ns_util.math(__y).minus(current_position.y).abs().gte(3)) {
                update_coords = true;
            }
        }
        if (this.last_update_count > 2) {
            update_coords = true;
        }

        if (update_coords === true) {
            let post_data = {}
            post_data['current_posi_pk'] = this.current_posi_pk;
            post_data['near'] = 'Y';
            post_data['detail'] = 'Y';
            // Math.ceil(480 / 150) * 3;
            post_data['xcount'] = Math.ceil(ns_util.math(ns_engine.game_object.main_stage.element.clientWidth).div(this.obj_width).plus(2).number);
            post_data['ycount'] = Math.ceil(ns_util.math(ns_engine.game_object.main_stage.element.clientHeight).div(this.obj_height).plus(4).number);

            ns_xhr.post('/api/world/coords', post_data, (_data) => {
                this.coordsCallback(_data);
                this.last_update_count = 0;
            });
        } else {
            this.last_update_count++;
        }
    }

    setPosition (_x, _y)
    {
        // ns_world.objs.goto_map_x.text(_x);
        // ns_world.objs.goto_map_y.text(_y);

        let center = this.getPositionCenter(_x, _y);
        this.world_bg_x = center.x;
        this.world_bg_y = center.y;

        this.scrollSet(this.world_bg_x , this.world_bg_y);

        this.goto_map = false;
        this.positionUpdate(_x, _y);

        let distance = ns_world.distanceValue(ns_engine.game_data.cpp, this.current_posi_pk, true);
        this.objs.world_position_distance.text(distance.length + ns_i18n.t('distance'));
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
            this.scroll.client_x = (! ns_engine.trigger.is_touch_device) ? _e.clientX : _e.changedTouches[0].clientX;
            this.scroll.client_y = (! ns_engine.trigger.is_touch_device) ? _e.clientY : _e.changedTouches[0].clientY;
            this.scroll.transform_x = this.scrollCurrent().x;
            this.scroll.transform_y = this.scrollCurrent().y;
        } else if (_e.type === ns_engine.cfg.mouse_up_event_type) {
            let _x = (! ns_engine.trigger.is_touch_device) ? _e.clientX : _e.changedTouches[0].clientX;
            let _y = (! ns_engine.trigger.is_touch_device) ? _e.clientY : _e.changedTouches[0].clientY;
            this.scroll.is_down = false;
            if (ns_util.math(this.scroll.client_y).minus(_y).abs().gte(5) || ns_util.math(this.scroll.client_x).minus(_x).abs().gte(5)) {
                this.positionUpdate();
            } else {
                let _posi_pk = _e.target.id.split('_xy_').pop();
                if (! ns_world.coords.has(_posi_pk)) {
                    return;
                }
                let coords = ns_world.coords.get(_posi_pk);
                if (coords._type === 'D' && !ns_cs.d.npc_supp[coords._posi_pk]) {
                    return;
                }
                ns_sound.play('button_4');
                if (coords._type === 'P') {
                    ns_dialog.setDataOpen('message', 'It will be updated later.');
                    return;
                }
                ns_dialog.setDataOpen('world_detail', { coords: coords });
                // ns_sound.play('button_3');
            }
        } else if (_e.type === ns_engine.cfg.mouse_move_event_type) {
            this.scrollMove(_e);
        } else if (_e.type === ns_engine.cfg.mouse_leave_event_type) {
            if (this.scroll.is_down === true) {
                this.positionUpdate();
            }
            this.scroll.is_down = false;
        }
    }

    scrollCurrent ()
    {
        let matrix = new WebKitCSSMatrix(getComputedStyle(this.objs.world_map.element).getPropertyValue("transform"));
        return { x: parseInt(String(matrix.m41)) ?? 0, y: parseInt(String(matrix.m42)) ?? 0 }
    }

    scrollSet (_x, _y)
    {
        _x = (_x > 0) ? 0 : _x;
        _y = (_y > 0) ? 0 : _y;
        let w_count = ns_util.math(this.objs.world_map.element.clientWidth).div(this.obj_width).number;
        let h_count = ns_util.math(this.objs.world_map.element.clientHeight).div(this.obj_height).plus(1).number;
        let w_limit = ns_util.math(w_count).mul(this.obj_width).minus(ns_engine.game_object.main_stage.element.clientWidth).mul(-1).number;
        let h_limit = ns_util.math(h_count).mul(this.obj_height).minus(ns_engine.game_object.main_stage.element.clientHeight).mul(-1).number;
        _x = (_x < w_limit) ? w_limit : _x;
        _y = (_y < h_limit) ? h_limit : _y;

        this.objs.world_map.element.style.transform = `translate(${_x}px, ${_y}px)`;
        this.objs.world_troop.element.style.transform = `translate(${_x}px, ${_y}px)`;
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


        // TODO 월드맵 상황에 따라 제한을 변경해줘야함. 차후 486x486 이상 크기가 될수 있음.
        this.scrollSet(this.scroll.current_x, this.scroll.current_y);
    }

    distanceValue (_originally_posi_pk, _move_posi_pk, _divide = false)
    {
        let distance = 0.0;
        let p = _originally_posi_pk.split('x');
        let x = p[0], y = p[1];
        let move_p = _move_posi_pk.split('x');
        let move_x = move_p[0], move_y = move_p[1];
        let abs_x = ns_util.math(x).minus(move_x).abs().number;
        let abs_y = ns_util.math(y).minus(move_y).abs().number;
        let min_value = Math.min(abs_x, abs_y) * 1.2;
        let abs_value = ns_util.math(abs_x).minus(abs_y).abs().number;
        distance = ns_util.math(min_value).plus(abs_value).number;
        distance = distance.toFixed(1);
        let arrow = this.directionArrow(ns_engine.game_data.cpp, _move_posi_pk);
        return (_divide === false) ? arrow + distance : { direction: arrow, length: distance };
    }

    directionArrow (originally_posi_pk, move_posi_pk)
    {
        let p = originally_posi_pk.split('x');
        let x = p[0], y = p[1];
        let move_p = move_posi_pk.split('x');
        let move_x = move_p[0], move_y = move_p[1];
        let arrow = '';
        if (this.previous_arrow) {
            this.objs.world_position_arrow.removeCss(this.previous_arrow)
        }
        this.objs.world_position_compass.show();
        if (move_x < x && move_y === y) {
            arrow = '←';
            this.previous_arrow = 'right';
        } else if (move_x === x && move_y < y) {
            arrow = '↑';
            this.previous_arrow = 'bottom';
        } else if (move_x > x && move_y === y) {
            arrow = '→';
            this.previous_arrow = 'left';
        } else if (move_x === x && move_y > y) {
            arrow = '↓';
            this.previous_arrow = 'top';
        } else if (move_x < x && move_y < y) {
            arrow = '↖';
            this.previous_arrow = 'right_bottom';
        } else if (move_x > x && move_y < y) {
            arrow = '↗';
            this.previous_arrow = 'left_bottom';
        } else if (move_x > x && move_y > y) {
            arrow = '↘';
            this.previous_arrow = 'left_top';
        } else if (move_x < x && move_y > y) {
            arrow = '↙';
            this.previous_arrow = 'right_top';
        } else {
            this.objs.world_position_compass.hide();
            this.previous_arrow = null;
        }
        if (this.previous_arrow) {
            this.objs.world_position_arrow.addCss(this.previous_arrow);
        }
        return arrow;
    }

    coordsCallback (_data)
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        try {
            this.coords = new Map();
            ns_world.objs.world_map.empty();

            let world_list = _data['WORLD'];
            for (let [k, d] of Object.entries(world_list)) {
                let posi_pk = d.posi_pk;
                if (this.coords.has(posi_pk)) {
                    this.coordsRemove(posi_pk);
                }
                let _coords = new nsWorldSet(k, d);
                let _coords_element = _coords.init();
                if (_coords_element) {
                    ns_world.objs.world_map.append(_coords_element);
                    this.coords.set(posi_pk, _coords);
                }
            }
            this.coords_count = this.coords.size;
            if (this.clicked_coords === true) {
                let coords = this.coords.get(this.current_posi_pk);
                ns_dialog.setDataOpen('world_detail', { coords: coords });
                // ns_sound.play('button_3');
                ns_sound.play('button_4');
                this.clicked_coords = false;
            }
            this.last_update_posi_pk = this.current_posi_pk;
        } catch (e) {
            console.error(e);
        }
    }

    coordsRemove (_posi_pk)
    {
        this.coords.delete(_posi_pk);
        delete ns_world.objs.world_map.find(`ns_world_xy_${_posi_pk}`).element;
    }

    getPositionCenter (x, y)
    {
        return {
            x: Math.floor((x * this.obj_width - (ns_engine.game_object.main_stage.element.clientWidth / 2) + (ns_world.obj_width / 2 - 1)) * -1),
            y: Math.floor((y * this.obj_height - (ns_engine.game_object.main_stage.element.clientHeight / 2) + ns_world.obj_height + (ns_world.obj_height / 2 - 1)) * -1)
        }
    }

    getPosition (width, height)
    {
        return {
            x: ns_util.toInteger(((width * -1) + (ns_world.obj_width / 2) + (ns_engine.game_object.main_stage.element.clientWidth / 2)) / ns_world.obj_width),
            y: ns_util.toInteger(((height * -1) + (ns_world.obj_height / 2) + (ns_engine.game_object.main_stage.element.clientHeight / 2) - ns_world.obj_height) / ns_world.obj_height)
        }
    }

    getPositionObject (x, y)
    {
        return {
            x: Math.floor((x * this.obj_width + (this.obj_width / 2))),
            y: Math.floor((y * this.obj_height + (this.obj_height / 2)))
        }
    }

    drawMoveTroop ()
    {
        for (let [troo_pk, row] of Object.entries(ns_cs.d.troop)) {
            if (! ns_util.isNumeric(troo_pk)) {
                return;
            }
            if (row.status === 'M') {
                ns_world.lineDraw(troo_pk, row);
            } else if (row.status === 'W' || row.status === 'R') {
                ns_world.lineDraw(troo_pk, row, true);
            }
        }
    }

    lineDraw (_key, _row, _return = false)
    {
        // 이미 존재하면 그리지 않음
        if (!_return && this.objs?.world_troop?.find(`#move_${_key}`).element) {
            return;
        }
        if (_row.src_posi_pk === '999x999' || _row.dst_posi_pk === '999x999') {
            return;
        }
        if (_return && this.objs?.world_troop?.find(`#move_${_key}`).element) {
            this.objs.world_troop.find(`#move_${_key}`).remove();
        }

        let remain_time = ns_util.math(_row.arrival_dt_ut).minus(ns_timer.now()).integer; // 남은 도착 시간
        if (ns_util.math(remain_time).lte(0)) {
            return;
        }
        let unit = document.createElement('div');
        unit.classList.add('unit');

        let line = document.createElement('div');
        line.setAttribute('id', `move_${_key}`);
        line.classList.add('line');

        let line_color = 'my';
        if (ns_cs.d.lord.alli_pk.v !== null) {
            if (! ns_util.math(_row.src_lord_pk ?? 0).eq(ns_cs.d.lord.lord_pk.v)) {
                line_color = (ns_util.math(_row.src_alli_pk ?? 0).eq(ns_cs.d.lord.alli_pk.v)) ? 'ally' : 'enemy';
            }
        } else {
            if (! ns_util.math(_row.src_lord_pk ?? 0).eq(ns_cs.d.lord.lord_pk.v)) {
                line_color = 'enemy';
            }
        }
        line.classList.add(line_color);

        let src_position = (! _return) ? _row.src_posi_pk : _row.dst_posi_pk;
        let dst_position = (! _return) ? _row.dst_posi_pk : _row.src_posi_pk;

        let [src_x, src_y] = src_position.split('x');
        let [dst_x, dst_y] = dst_position.split('x');

        let s = this.getPositionObject(src_x, src_y);
        let e = this.getPositionObject(dst_x, dst_y);

        let a = s.x - e.x;
        let b = s.y - e.y;
        let length = Math.sqrt( a * a + b * b );

        let angle_deg = Math.atan2(e.y - s.y, e.x - s.x) * 180 / Math.PI;

        line.style.width = length + 'px';
        line.style.left = s.x + 'px';
        line.style.top = s.y + 'px';

        line.style.transform = "rotate(" + angle_deg + "deg)";
        unit.style.transform = "rotate(" + -angle_deg + "deg)";
        if (ns_util.math(src_x).gt(dst_x)) {
            unit.style.transform+= " scaleX(-1)";
        }

        line.appendChild(unit);
        this.objs?.world_troop?.append(line);

        ns_world.troop_move[_key] = {
            src: { x: src_x, y: src_y },
            dst: { x: dst_x, y: dst_y },
            status: _row.status,
            cmd_type: _row.cmd_type,
            triptime: _row.triptime,
            camptime: _row.camptime,
            move_time: _row.move_time,
            end_dt: _row.arrival_dt_ut,
            line: line,
            unit: unit
        };
    }
    lineRemove (_key)
    {
        if (! ns_world.troop_move[_key]) {
            return;
        }
        ns_world.troop_move[_key].line.remove();
        ns_world.troop_move[_key].unit.remove();
        delete ns_world.troop_move[_key];
    }
}

let ns_world = new nsWorld();

class nsWorldSet
{
    constructor (_tag_id, _data) {
        this.loaded = false;
        this.obj = null;
        this.tag_prefix = 'ns_world_';
        this.tag_id = _tag_id;

        this._posi_pk = _data.posi_pk;
        this._type = _data.type;
        this._level = _data.level;
        this._lord_pk = _data.lord_pk;
        this._state	= _data.state;
        this._status_truce = _data.status_truce;

        this._current_point	= ns_util.toNumber(_data.current_point);
        this._update_point_dt = ns_util.toNumber(_data.update_point_dt);

        this._lud = _data.last_update_dt;
        this._flag = _data.flag;

        this._alli_pk = _data.alli_pk;
        this._alli_title = _data.alli_title;

        this._detail = _data.hasOwnProperty('detail');

        this._data = {
            color: null
        }

        this._lord_name = (this._detail !== true) ? null : _data.detail.lord_name;
        this._title = (this._detail !== true) ? null : _data.detail.title;
        this._power = (this._detail !== true) ? null : _data.detail.power;
        this._fame = (this._detail !== true) ? null : _data.detail.fame;

        this._detail_ut_dt = null;
        if(this._detail === true) {
            this._detail_ut_dt = ns_timer.now() + 30;
        }
    }

    init ()
    {
        this.loaded = true;

        // 위치
        let p = this._posi_pk.split('x');
        let x = p[0], y = p[1];
        this.posi_x = x;
        this.posi_y = y;

        if (x < 1 || y < 1 || x > 487 || y > 487) {
            return;
        }

        this.obj = document.createElement('div');
        this.obj.setAttribute('id', this.tag_prefix + this.tag_id);
        this.obj.classList.add('world_coords'); // world_coord

        this.obj.style.left = ns_util.math(x).mul(ns_world.obj_width).integer + 'px';
        this.obj.style.top = ns_util.math(y).mul(ns_world.obj_height).integer + 'px';

        if (this._lud > ns_world.lud_max) {
            // 데이터 업데이트한  최종시간 저장
            ns_world.lud_max = this._lud;
        }

        let image_type;
        let image_level = (this._lord_pk) ? this._fame : this._level;
        if (this._type === 'N') {
            image_type = 'T';
        } else if (this._type === 'A' || this._type === 'E') {
            // Empty 타일
            image_type = 'empty'
            image_level = 0;
        } else if (this._type === 'D' && ns_cs.d.npc_supp[this._posi_pk]) {
            image_type = 'npc_supp';
            image_level = 0;
        } else {
            image_type = this._type ?? '';
        }

        this.obj.classList.add(`tile_${image_type}_${image_level}`);

        let title = document.createElement('span');
        title.classList.add('tile_title');
        if (ns_cs.d.event?.['occupation_point_enable']?.v === true && ['F', 'G', 'L', 'M', 'R', 'A', 'E'].includes(this._type) && ns_util.math(this._current_point).lte(0)) {
            title.classList.add('text_cadet_gray');
        }
        if (image_type === 'T') {
            this.obj.classList.add('world_flag');
            this.obj.setAttribute('data-flag', this._level);
            if (this._lord_pk === ns_cs.d.lord.lord_pk.v) {
                this.obj.classList.add('green_flag');
                title.innerText = this._title;
                this.areaColor('my');
            } else if (this._lord_pk) {
                if (this._alli_pk > 0 && this._alli_pk === ns_cs.d.lord.alli_pk.v) {
                    this.obj.classList.add('blue_flag');
                    this.areaColor('ally');
                } else if (ns_cs.d.ally_rela[this._alli_pk]) {
                    if (ns_cs.d.ally_rela[this._alli_pk].v === 'N') { // 중립
                        // coords.classList.add('purple_flag');
                        this.obj.classList.add('black_flag');
                    } else if (ns_cs.d.ally_rela[this._alli_pk].v === 'H') { // 적대
                        // coords.classList.add('red_flag');
                        this.obj.classList.add('black_flag');
                    } else if (ns_cs.d.ally_rela[this._alli_pk].v === 'F') { // 우호
                        // coords.classList.add('sky_flag');
                        this.obj.classList.add('black_flag');
                    }
                    this.areaColor('enemy');
                } else {
                    this.obj.classList.add('black_flag');
                    title.innerText = this._title;
                    this.areaColor('enemy');
                }
            } else {
                this.obj.classList.add('npc_flag');
                title.innerText = codeset.t('valley', 'N');
                // this.areaColor('npc');
            }

            // Truce
            if (this._status_truce === 'Y') {
                this.obj.classList.add('world_truce');
            }

            // TODO 점령선포 처리 필요!
            /*var target_posi_pk = this._posi_pk;
            var my_type = null;
            var target_type = null;
            $.each(qbw_cs.d.time, function(k,d)
            {
                if (d.queue_type == 'O')
                {
                    var p = d.description.indexOf(':');
                    var type = d.description.substr(0,p);
                    var posi_pk = d.description.substr(p+1);

                    if (target_posi_pk == qbw_cs.cfg.curr_posi_pk && my_type != type)
                    {
                        my_type = type;
                        var duration = qbw_timer.now() - d.start_dt_ut;
                        if (duration < 43200)
                        {
                            type += '_disable';
                        }

                        addObj = document.createElement('span');
                        addObj.setAttribute('className', 'world_' + type);
                        addObj.setAttribute('class', 'world_' + type);
                        obj.appendChild(addObj);
                    }

                    if (type == 'attack')
                        type = 'defence';
                    else
                        type = 'attack';

                    if (target_posi_pk == posi_pk && target_type != type)
                    {
                        target_type = type;
                        var duration = qbw_timer.now() - d.start_dt_ut;
                        if (duration < 43200)
                        {
                            type += '_disable';
                        }

                        addObj = document.createElement('span');
                        addObj.setAttribute('className', 'world_' + type);
                        addObj.setAttribute('class', 'world_' + type);
                        obj.appendChild(addObj);
                    }
                }
            });*/

            this.obj.appendChild(title);
        } else if (image_type === 'P') {
            title.innerText = codeset.t('valley', image_type.toUpperCase());
            this.obj.appendChild(title);
        } else if (['npc_supp', 'npc_supp_event'].includes(image_type)) {
            title.innerText = codeset.t('valley', image_type.toUpperCase());
            this.obj.appendChild(title);
        } else if (image_type !== 'D') {
            // 내 자원지
            if (this._lord_pk === ns_cs.d.lord.lord_pk.v) {
                this.obj.classList.add('world_my_valley');
                this.areaColor('my');
            }  else if (this._lord_pk) {
                if (this._alli_pk > 0 && this._alli_pk === ns_cs.d.lord.alli_pk.v) {
                    this.areaColor('ally');
                } else if (ns_cs.d.ally_rela[this._alli_pk]) {
                    this.areaColor('enemy');
                } else {
                    this.areaColor('enemy');
                }
            }
            if (!['A', 'E'].includes(this._type)) {
                title.innerText = `Lv.${this._level} ${codeset.t('valley', this._type)}`;
                this.obj.appendChild(title);
            }
        }

        this._obj = new nsObject(this.obj);
        return this.obj;
    }

    areaColor (_type)
    {
        // 테스트
        let color = document.createElement('div');
        color.classList.add('color');
        color.classList.add(_type);
        this.obj.appendChild(color);
        this._data.color = _type;
    }

    battleEffect ()
    {
        let _p = document.createElement('p');
        _p = new nsObject(_p);
        _p.addCss('world_battle_effect');
        this.obj.appendChild(_p.element);
        _p.setEvent('animationend', (_e) => {
            if (_e.animationName === 'battle_effect_y') {
                _p.remove();
            }
        });
    }

    update (_target, _value)
    {
        if (_target === 'color') {
            if (this._obj.find('.color').element) {
                this._obj.find('.color').remove();
            }
            if (_value) {
                this.areaColor(_value);
            }
            this._data.color = _value;
        }
    }
}