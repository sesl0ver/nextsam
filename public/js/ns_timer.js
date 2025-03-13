class nsTimer
{
    constructor ()
    {
        this._active = true;
        this._server_time = 0;
        this._server_time_get_interval = 10000;
        this._tick = 100;
        this._reduce_tick = 0; // run interval tick reduce
        this._reduce_time = 0; // server/client time reduce
        this._affected_count = 0; // 마지막 run 된 timer 수
        this.timers = {};
    }

    init ()
    {
        this.run();
        setTimeout(this.getServerTime, this._server_time_get_interval);

        this.timers.foreground_lp_request = new nsTimerSet(() => { this.foregroundLpRequestTick(); }, 1000, false); // 1초 마다
        this.timers.foreground_lp_request.init();

        this.timers.resource = new nsTimerSet(() => { this.resourceRecalcTick(); }, 60 * 1000, false); // 1분 마다 업데이트
        this.timers.resource.init();

        this.timers.gold_pop = new nsTimerSet(() => { this.goldPopTick(); }, 60 * 1000, false); // 1분 마다 업데이트
        this.timers.gold_pop.init();

        this.timers.check_chat = new nsTimerSet(() => { this.checkChatTick(); }, 3 * 60 * 1000, false); //  3분 마다 업데이트
        this.timers.check_chat.init();

        //qbw_timer.timers.loyalty = new qbw_timer_class(loyalty_recalc_tick, -1, 313*1000, false); // 313초 5분 13초 범위
        //qbw_timer.timers.loyalty.init();

        this.timers.world = new nsTimerSet(() => { this.worldReloadTick() }, 30 * 1000, false);
        this.timers.world.init();

        this.timers.quest = new nsTimerSet(() => { this.eventCheckTick(); }, 5 * 60 * 1000, false); // 5분 마다 확인
        this.timers.quest.init();

        this.timers.troop_move = new nsTimerSet(() => { this.worldTroopMoveTick(); }, 500, false); // 0.5초 마다 확인
        this.timers.troop_move.init();

        this.timers.progress_bar = new nsTimerSet(() => { this.progressBarTick(); }, 500, true); // 0.5초 마다 확인
        this.timers.progress_bar.init();

        this.timers.check_enemy = new nsTimerSet(() => { this.checkEnemyTroopTick(); }, 500, true); // 0.5초 마다 확인
        this.timers.check_enemy.init();

        //qbw_timer.timers.hero_skill_exp = new qbw_timer_class(hero_skill_exp_reaclc_tick, -1, 600*1000, true);  // 10분주기
        //qbw_timer.timers.hero_skill_exp.init();

        //qbw_timer.timers.ranking = new qbw_timer_class(ranking_reaclc_tick, -1, 317*1000, false); // 317초 5분 17초 범위
        //qbw_timer.timers.ranking.init();

        //qbw_timer.timers.game_time_tick = new qbw_timer_class(game_time_tick, -1, 30000, false);
        //qbw_timer.timers.game_time_tick.init();
    }

    run ()
    {
        if (this._active === false) {
            return;
        }
        let start_at = 0, end_at = 0;
        start_at = new Date().getTime();

        for (let [_key, _data] of Object.entries(this.timers)) {
            if (_data?._loaded && _data._loaded === false) {
                this.timers[_key] = null; // loaded 되지 않은 타이머 삭제
                delete this.timers[_key];
            } else {
                // 마지막 시행 이 후 대기시간이 초과하고 실행대기 중이 아닐때 [재 실행]
                if (ns_util.math(start_at).minus(_data._loop_latest).gte(_data._loop_delay) && _data._running === false) {
                    this._affected_count++;
                    _data._running = true;
                    _data.run();
                }
            }
        }

        end_at = new Date().getTime();

        // run interval 오차 교정
        this._reduce_tick = ns_util.math(end_at).minus(start_at).number;
        if (this._active === true) {
            setTimeout(() => { this.run(); }, ns_util.math(this._tick).minus(this._reduce_tick).number);
        }
    }

    getServerTime ()
    {
        ns_xhr.get('/api/server/time'); // 서버에서 Push 전송으로 갱신함.
    }

    now ()
    {
        let v = new Date().getTime() / 1000 + this._reduce_time * -1;
        return parseInt(v.toString());
    }

    nowMs ()
    {
        let v = new Date().getTime() + this._reduce_time * -1000;
        return parseInt(v.toString());
    }


    reduce ()
    {
        let current_time = new Date().getTime() / 1000;
        if (typeof this._server_time == 'number') {
            this._reduce_time = current_time - this._server_time;
        }
    }
    reduceAgain (_server_time) // 시간 차 재보정
    {
        this._server_time = _server_time;
        this.reduce();
    }

    getDateTimeString (_timestamp, _with_year, _with_minute, _with_second)
    {
        if (_timestamp === 0) {
            return '';
        }

        if (! _timestamp) {
            _timestamp = this.now();
        }

        let dt = new Date(_timestamp*1000);
        let str = '';

        if (_with_year) {
            str += dt.getFullYear() + '/';
        }

        str += this.getFillZeroLeft(dt.getMonth() + 1) + '/';
        str += this.getFillZeroLeft(dt.getDate());
        str += ' ';
        str += this.getFillZeroLeft(dt.getHours());

        if (_with_minute) {
            str += ':' + this.getFillZeroLeft(dt.getMinutes());
        }

        if (_with_second) {
            str += ':' + this.getFillZeroLeft(dt.getSeconds());
        }

        return str;
    }

    getFillZeroLeft  (_value, _length = 2)
    {
        if (! ns_util.isNumeric(_value)) {
            return _value;
        }
        _value = (typeof _value !== 'string') ? _value.toString() : _value;
        let z = '';
        for (let i = _value.length; i < _length; i++) {
            z += '0';
        }
        return z + _value;
    }

    goldPopTick ()
    {
        let ut = this.now();
        let curr_tick = ut / 300; // 5분 주기
        if (ns_engine.game_data.gold_pop_tick == null) {
            ns_engine.game_data.gold_pop_tick = curr_tick;
            return;
        }
        if (ns_engine.game_data.gold_pop_tick >= curr_tick) {
            return;
        }
        if (ns_cs.getTerritoryInfo('population_trend') === 'S') {
            if (ns_cs.getTerritoryInfo('gold_curr') >= 999999999) {
                return;
            }
            let new_val = ns_util.math(ns_cs.getTerritoryInfo('gold_curr')).plus(ns_util.math(ns_cs.getTerritoryInfo('gold_production')).div(12).number).number; // 5분 증가량
            ns_cs.d.terr.set('gold_curr', ns_util.math(new_val).integer);
        } else {
            if (ns_engine.xhr.fg_xhr_use) {
                return;
            }
            ns_xhr.post('/api/recalc/goldPop');
        }
        ns_engine.game_data.gold_pop_tick = curr_tick;
    }

    eventCheckTick ()
    {
        ns_xhr.post('/api/quest/check');
    }

    foregroundLpRequestTick ()
    {
        let now = this.now();
        let lp_request = false;

        for (let [k, d] of Object.entries(ns_cs.d.time)) {
            if (! ns_util.isNumeric(k)) {
                continue;
            }
            if (d.end_dt_ut < now && ! d.lp_requested) {
                // $('#terr_ui_cbottom').prepend('<p>' + qbw_timer.getDateTimeString(null, true, true, true) + '</p>');
                d.lp_requested = true;
                lp_request = true;
                if (d.lp_requested_count === undefined) {
                    d.lp_requested_count = 1;
                } else {
                    d.lp_requested_count++;
                }
                this.foregroundLpRequestFailOver(k);
            }
        }

        if (lp_request) {
            ns_engine.lpRequest();
        }
    }

    foregroundLpRequestFailOver (_k)
    {
        _k = ns_util.toInteger(_k);
        setTimeout(() =>
        {
            if (ns_cs.d.time && ns_cs.d.time[_k] && ns_cs.d.time[_k].lp_requested) {
                if (ns_cs.d.time[_k].lp_requested_count < 9) {
                    ns_cs.d.time[_k].lp_requested = false;
                    console.log(_k, `lp_requested requeue (${ns_cs.d.time[_k].lp_requested_count})`);
                } else
                    console.error(_k, 'lp_requested requeue exceed');
            }
        }, 1250);
    }

    resourceRecalcTick ()
    {
        if (!ns_cs.d.reso || ! ns_cs.d.reso?.food_curr) {
            return;
        }

        let now = ns_timer.now(), passed_sec, incr_val;
        let resource_list = ['food', 'horse', 'lumber', 'iron'];
        for (let _type of resource_list) {
            if (ns_cs[`${_type}_curr`] >= ns_cs.d.reso[`${_type}_max`].v) {
                ns_cs[`${_type}_curr`] = ns_cs.d.reso[`${_type}_max`].v;
                // $('#ui_terr_food').css('color', '#575A62');
            } else {
                // $('#ui_terr_food').css('color', '');
                incr_val = 0;
                passed_sec = now - ns_cs.d.reso[`${_type}_curr`].__lud;
                if (passed_sec > 0) {
                    incr_val = ns_util.math(ns_cs.d.reso[`${_type}_production`].v).div(3600).mul(passed_sec).number;
                    incr_val = ns_util.toInteger(incr_val);
                }
                ns_cs[`${_type}_curr`] = ns_cs.d.reso[`${_type}_curr`].v + incr_val;
                if (ns_cs[`${_type}_curr`] >= ns_cs.d.reso[`${_type}_max`].v) {
                    ns_cs[`${_type}_curr`] = ns_cs.d.reso[`${_type}_max`].v;
                }
            }
            ns_cs.d.reso.s[`main_top_reso_${_type}_curr`].text((! ns_cs[`${_type}_curr`]) ? 0 : ns_util.numberFormat(ns_cs[`${_type}_curr`]));
        }

        // 만약 자원 정보 팝업창이 열려있다면
        /*if (qbw_dlg.dlgs.pop_terr_resource.opened)
        {
            var dlg = qbw_dlg.dlgs.pop_terr_resource;
            var _key = dlg.data;

            dlg.cache_cont_curr.html(qbw_util_numberFormat(qbw_cs[_key + '_curr']));
            //dlg.obj.find('.cont_max').html(qbw_util_numberFormat(qbw_cs.d.reso[_key + '_max'].v));
        }*/
    }

    worldReloadTick ()
    {
        if (ns_engine.game_data.curr_view !== 'world') {
            return;
        }
        let [x, y] = ns_world.current_posi_pk.split('x');
        ns_world.positionUpdate(x, y);
    }

    worldTroopMoveTick ()
    {
        if (ns_engine.game_data.curr_view !== 'world') {
            return;
        }
        if (ns_world.troop_move.length < 1) {
            return;
        }
        for (let [pk, o] of Object.entries(ns_world.troop_move)) {
            let total_time = ns_util.math(o.triptime).plus(o.camptime).integer; // 실제 총 이동 시간
            let remain_time = ns_util.math(o.end_dt).minus(ns_timer.now()).integer; // 남은 도착 시간
            if (ns_util.math(remain_time).lte(0)) {
                ns_world.lineRemove(pk, 2);
                continue;
            }

            let line_rect = o.line.getBoundingClientRect();

            let _x = 0;
            let average = 1 - ((remain_time / total_time * 100) / 100);
            if (ns_util.math(o.src.y).minus(o.dst.y).abs().number > ns_util.math(o.src.x).minus(o.dst.x).abs().number) {
                _x = Math.floor(line_rect.height * average);
            } else {
                _x = Math.floor(line_rect.width * average);
            }
            o.unit.style.left = ns_util.math(_x).minus(49).number + 'px';
        }
    }

    progressBarTick ()
    {
        let queue_types = ['C', 'T', 'A', 'M', 'F', 'E'];
        let timers = Object.entries(ns_cs.d.time).filter(o => ns_util.isNumeric(o[0]) && queue_types.includes(o[1].queue_type) && o[1].status === 'P' && (o[1].in_cast_pk !== null || o[1].out_cast_pk !== null)).map(o => o[1])
        /*if (timers.length < 1) {
            return;
        }*/
        timers.sort((a, b) => queue_types.findIndex(o => o === a.queue_type) - queue_types.findIndex(o => o === b.queue_type));
        let queue = { bdic: new Set(), bdoc: new Set() }
        for (let timer of timers) {
            let bd_c = (!ns_util.math(timer.in_cast_pk).eq(0)) ? 'bdic' : 'bdoc';
            let cast_pk = (!ns_util.math(timer.in_cast_pk).eq(0)) ? timer.in_cast_pk : timer.out_cast_pk;
            if (queue[bd_c].has(cast_pk)) { // 이미 사용중이라면 패스
                continue;
            }
            queue[bd_c].add(cast_pk);
            let tile = new nsObject(`#ns_${bd_c}_${cast_pk}`);

            let _remain_time = ns_util.math(timer.end_dt_ut).minus(ns_timer.now()).number;

            if (ns_util.math(_remain_time).lte(0)) {
                if (tile.find('.ns_tile_progress').element) {
                    tile.find('.ns_tile_progress').remove();
                    if (queue[bd_c]) {
                        queue[bd_c].delete(cast_pk);
                    }
                }
                continue;
            }

            let _rate = ns_util.math(timer.build_time - _remain_time).div(timer.build_time).mul(100).toFixed(1);
            if (! tile.find('.ns_tile_progress').element) {
                let progress_bar = document.createElement('span');
                progress_bar.classList.add('ns_tile_progress');
                let progress_title = document.createElement('span');
                progress_title.classList.add('progress_title');
                progress_title.classList.add(timer.queue_type);

                let progress_sub = document.createElement('span');
                progress_sub.classList.add('progress_sub');

                progress_title.innerHTML = ns_util.getCostsTime(_remain_time);
                progress_sub.style.width = _rate + '%';

                progress_bar.appendChild(progress_title);
                progress_bar.appendChild(progress_sub);

                tile.append(progress_bar);
            } else {
                tile.find('.progress_title').html(ns_util.getCostsTime(_remain_time));
                tile.find('.progress_title').removeCss(queue_types).addCss(timer.queue_type);
                tile.find('.progress_sub').element.style.width = _rate + '%';
            }
        }
    }

    checkEnemyTroopTick ()
    {
        if (! ns_cs.d.lord['setting']) {
            return;
        }
        let _enemy_set = ns_cs.d.lord['setting']['alert_effect_enemy'] === 'Y';
        let _ally_set = ns_cs.d.lord['setting']['alert_effect_ally'] === 'Y';

        ns_engine.game_object.warning_wrap.hide().removeCss('ally').removeCss('enemy').removeCss('toggle');
        ns_button.buttons.main_counter_troop_list.obj.removeCss('ally').removeCss('enemy').removeCss('toggle');

        let _enemy = false, _ally = false
        for (let [k, d] of Object.entries(ns_cs.d.time)) {
            if (!ns_util.isNumeric(k)) {
                continue;
            }
            let npc_attacker = /999x999/g.test(d.description);
            if (! npc_attacker && (! ns_cs.d.troop[d.queue_pk] || ns_cs.d.troop[d.queue_pk].src_posi_pk === ns_cs.d.lord.main_posi_pk.v)) {
                continue;
            }
            if ((npc_attacker || d.queue_type === 'H' || d.queue_type === 'Y')) {
                _enemy = true;
            }
            if (d.queue_type === 'X') {
                _ally = true;
            }
        }

        // 버튼은 무조건 연출
        if (_enemy && _ally) {
            ns_button.buttons.main_counter_troop_list.obj.addCss('toggle');
            if (_enemy_set && _ally_set) {
                ns_engine.game_object.warning_wrap.show().addCss('toggle');
            }
        } else if (_enemy) {
            ns_button.buttons.main_counter_troop_list.obj.addCss('enemy');
            if (_enemy_set) {
                ns_engine.game_object.warning_wrap.show().addCss('enemy');
            }
        } else if (_ally) {
            ns_button.buttons.main_counter_troop_list.obj.addCss('ally');
            if (_ally_set) {
                ns_engine.game_object.warning_wrap.show().addCss('ally');
            }
        }
    }

    checkChatTick ()
    {
        if (ns_chat.io) {
            ns_chat.io.emit('pong', { pong: 'pong!' });
        }
    }

    checkFreeSpeedup (_time_pk)
    {
        try {
            if (! ns_cs.d.time[_time_pk]) {
                return false;
            }
            let queue_type = ns_cs.d.time[_time_pk].queue_type;
            if (queue_type !== 'X') {
                let left_dt = ns_util.math(ns_cs.d.time[_time_pk].end_dt_ut).minus(ns_timer.now()).integer;
                if (left_dt < ns_engine.cfg.free_speedup_time && ['C', 'T'].includes(queue_type)) {
                    return true;
                }
            }
            return false;
        } catch (e) {
            console.error(e);
            return false;
        }
    }

    convertDescription (_type, _description)
    {
        let description = '';
        if (_type === 'C') {
            let data = _description.split('|').map(o => o.split(':'));
            description = `${ns_i18n.t(`build_title_${data[0][0]}`)} ${ns_i18n.t('level_word', [data[0][1]])}↑ - ${ns_i18n.t('level_word', [data[1][1]])} ${data[1][0]}`;
        } else if (_type === 'A' || _type === 'M') {
            let data = _description.split(':');
            description = `${ns_i18n.t(`army_title_${data[0]}`)} (${data[1]})`;
        } else {
            return _description;
        }
        return description;
    }
}
let ns_timer = new nsTimer();

class nsTimerSet
{
    // _func, _loopCount, _loopDelay, _onTimeRun
    constructor (_function, _loop_delay, _on_time_run = false, _loop_count = -1) {
        this._running = false; // 실행 대기중
        this._loaded = false;
        this._function = _function;
        this._loop_count = _loop_count;
        this._loop_delay = _loop_delay; // millisecond
        this._on_time_run = _on_time_run; // initialize 때 실행여부
        this._loop_latest = 0;
        this._loop_run_count = 0;
    }

    init ()
    {
        if (this._running) {
            return;
        }

        if (this._loop_count === -1) {
            this._loop_count = 604800; // 일주일
        }

        if (this._loop_run_count > this._loop_count) {
            return;
        }

        if (typeof this._function !== 'function') {
            return;
        }

        this._loop_latest = new Date().getTime();
        this._loaded = true;

        if (this._on_time_run) {
            this._running = true;
            this.run();
        }
    }

    run ()
    {
        if (! this._running) {
            return;
        }

        this._loop_run_count++;

        if (this._loop_run_count > this._loop_count) {
            this.clear();
        } else {
            // 실행
            this._function.call(null);

            this._running = false; // 실행 대기중에서 탈출
            this._loop_latest = new Date().getTime();
        }
    }

    clear ()
    {
        this._running = true; // run 막음
        this._loaded = false; // 삭제 플래그 셋
    }
}