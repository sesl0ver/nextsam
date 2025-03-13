class nsCS
{
    constructor ()
    {
        this.d = {}; // 클라이언트용 데이터
        this.m = {}; // 마스터데이터용 데이터
        this.flag = {};
        this.text = {};
        this.code_set = {};

        this.preLoading();
    }

    initLocale (_after_function)
    {
    }

    preLoading ()
    {
        // 게임 시작전 사전에 로딩이 필요한 처리
        ns_util.loadScript(`/locale/ko/code_set.js`).then(r => {
            ns_util.loadScript(`/js/ns_codeset.js`).then(r => {
                ns_engine.init();
                ns_i18n.init({
                    after: () => {
                        ns_dialog.open('connect');
                    }
                });
            });
        });
    }

    startSession (_game_server_name, _clear_for_new_session, _change_posi_pk)
    {
        if (_game_server_name) {
            ns_engine.cfg.game_server_alias = _game_server_name;
            ns_engine.cfg.game_server_name = _game_server_name;
        }

        if (! ns_engine.cfg.game_server_name) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_please_select_server'));
            return;
        }

        // /server/list 에서 받은 정보에서 ns_engine.cfg.game_server_name 를 찾아 필요한 정보를 ns_engine.cfg 이하에 set
        let post_data = ns_auth.getAll();
        post_data['lang'] = ns_i18n.getLang();
        if (_change_posi_pk) {
            post_data['change'] = 1;
            post_data['cpp'] = _change_posi_pk;
        } else if (ns_engine.game_data.cpp) {
            // post_data += '&cpp=' + ns_engine.game_data.cpp;
            post_data['cpp'] = ns_engine.game_data.cpp;
        }

        let remote_proc_prepare = function(_data, _status)
        {
            // TODO 이럴 필요가 있나?
            if (_clear_for_new_session && _data.ns_xhr_return.code === 'success' && _data.ns_xhr_return.add_data.state === 'game_start') {
                ns_util.loadScript('/js/ns_cs_instance.js').then(r => { remote_proc(_data, _status); });
            } else {
                remote_proc(_data, _status);
            }
        }

        let remote_proc = function (_data, _status)
        {
            if (_clear_for_new_session && _data.ns_xhr_return.code === 'success' && _data.ns_xhr_return.add_data.state === 'game_start') {
                ns_engine.clearForNewSession();
            }

            if(! ns_xhr.returnCheck(_data)) {
                return;
            }
            _data = _data.ns_xhr_return.add_data;

            switch (_data.state) {
                case 'block':
                    ns_dialog.setDataOpen('message', _data.message);
                    return;
                case 'intro': // 인트로 및 군주생성
                    ns_cs.startIntro();
                    break;
                case 'roamer': // 방랑 군주
                    ns_dialog.setDataOpen('lord_roamer', _data);
                    break;
                case 'game_start':
                    ns_engine.cfg.game_server_name = _data.cfg.game_server_name;
                    ns_engine.cfg.app_platform = _data.cfg.platform;

                    // $('#game_server_name').html(qbw_e.cfg.game_server_name);
                    ns_engine.cfg.max_num_slot_guest_hero = _data.cfg.max_num_slot_guest_hero;
                    if (_data.cfg.sid) {
                        ns_engine.game_data.sid = _data.cfg.sid; // TODO 이게 맞나?
                    }

                    if (_data.cfg.curr_posi_pk) {
                        ns_engine.game_data.cpp = _data.cfg.curr_posi_pk;
                        ns_cs.d.terr.set('position', ns_engine.game_data.cpp);
                    }

                    // 이 통신 시작 시점에서 이미 가지고 있긴함.
                    if (_data.cfg.cmd_url_prefix) {
                        ns_engine.cfg.cmd_url_prefix = _data.cfg.cmd_url_prefix;
                    }

                    if (_data.cfg.lp_url) {
                        ns_engine.xhr.lp_url = _data.cfg.lp_url;
                    }

                    // start with fg game data (auto start)
                    // ns_dialog.closeAll();
                    if (_clear_for_new_session) {
                        ns_engine.game_data.curr_view = 'world';
                        // ns_button.buttons.main_world.clickFire();
                    }

                    // ns_cs.d.time.renderPost(); // 카운터 그려주기
                    // qbw_quest_view.draw(); // 게임시작과 동시에 퀘스트뷰 그리기
                    // ns_button.buttons.main_event_gift.obj.hide(); // 일단 버튼은 숨김
                    ns_timer.eventCheckTick(); // 이벤트 체크를 위해 한번 실행

                    /*let event_dialog = ns_dialog.dialogs.main_event;
                    // 메인 이벤트
                    if (event_dialog.check_cookie(ns_cs.d.lord.lord_pk.v + '_' + 'main_event')) {
                        ns_dialog.open('main_event');
                    }

                    if (window.localStorage.getItem(ns_cs.d.lord.lord_pk.v + '_limit_buy')) {
                        // 접속과 동시에 localStorage 데이터 삭제
                        window.localStorage.removeItem(ns_cs.d.lord.lord_pk.v + '_limit_buy');

                        // 2등급만 다시 구매할 수 있으므로 2등급만 체크
                        if (ns_util.math(ns_cs.d.lord.lord_pk.v).eq(2) && ns_util.math(ns_cs.d.lord.last_limit_buy.v).lt(2)) {
                            ns_dialog.setDataOpen('limit_buy', { level: 2 });
                        }
                    }*/

                    ns_world.current_posi_pk = ns_engine.game_data.cpp;
                    ns_world.init();

                    ns_quest.init();

                    ns_chat.connect();
                    // 한번 열었다 닫아줘야함.
                    ns_dialog.open('chat'); // 채팅
                    ns_dialog.open('magic_cube'); // 매직큐브
                    ns_dialog.open('troop_order_preset') // 프리셋
                    // ns_dialog.open('setting'); // 설정
                    ns_dialog.closeAll();

                    // 출석 이벤트
                    if (ns_util.checkExpireCookie(`${ns_cs.d.lord.lord_pk.v}_attendance`)) {
                        ns_dialog.open('attendance_event');
                    } else {
                        // 게임 최초 접속 시 진언창
                        if (ns_cs.d.lord['setting']['counsel_connect'] !== 'N') {
                            ns_dialog.setDataOpen('counsel', { type: 'connect' });
                        } else {
                            if (ns_engine.game_data.first_popup_package !== null) {
                                if (Object.keys(ns_engine.game_data.package_data).length > 0) {
                                    // 게임 최초 접속시 1회 띄워주기 위해
                                    ns_dialog.setDataOpen('package_popup', { m_package_pk: ns_engine.game_data.first_popup_package });
                                }
                            }
                        }
                    }

                    ns_button.buttons.hero_deck_list.obj.show();
                    ns_button.buttons.main_build_move_cancel.obj.hide();
                    ns_button.buttons.main_valley_manage.obj.hide();
                    ns_button.buttons.main_territory_manage.obj.show();
                    ns_button.buttons.main_time_event.obj.hide();

                    ns_sound.update();
                    ns_sound.play('bgm_castle', true);
                    break;
                default:
                    ns_dialog.setDataOpen('message', ns_i18n.t('msg_need_refresh_game'));
                    return;
            }
        };

        ns_xhr.post('/api/start/session', post_data, remote_proc_prepare, { useProgress: true });
    }

    startIntro ()
    {
        // 현재 인트로 없으므로 바로 군주 생성으로 넘어감
        ns_dialog.open('lord_create');
    }

    getResourceInfo (_type)
    {
        let arr = _type.split('_');
        let return_value = 0;
        if (arr.length !== 2 || arr[1] !== 'curr') {
            return this.d.reso[_type].v;
        }
        if (! this.d.reso?.[_type]) {
            return false;
        }
        let now = ns_timer.now();
        let passed_sec = now - ns_cs.d.reso[_type].__lud;
        let incr_val = 0;
        if (passed_sec > 0) {
            incr_val = ns_util.math(ns_cs.d.reso[arr[0] + '_production'].v).div(3600).mul(passed_sec).number;
            incr_val = ns_util.toInteger(incr_val);
        }
        return_value = ns_util.math(ns_cs.d.reso[_type].v).plus(incr_val).integer;
        if (ns_util.math(ns_cs.d.reso[arr[0] + '_max'].v).lte(return_value)) {
            return_value = ns_cs.d.reso[arr[0] + '_max'].v;
        }
        return return_value;
    }

    getProdInfo (_type)
    {
        return (! ns_cs.d?.prod?.[_type]) ? null : ns_cs.d.prod[_type].v;
    }

    getTerritoryInfo (_type)
    {
        return (! ns_cs.d?.terr?.[_type]) ? null : ns_cs.d.terr[_type].v;
    }

    getBuildingLeftTime (_dialog_left_time_obj, _status, _time_pk)
    {
        if (_status !== 'N') {
            let d = ns_cs.d.time[_time_pk];
            let left = ns_util.math(d.end_dt_ut).minus(ns_timer.now()).number;
            if (ns_util.math(left).gt(0)) {
                _dialog_left_time_obj.text(ns_i18n.t('time_left', [ns_util.getCostsTime(left)])).show();
            } else {
                _dialog_left_time_obj.text(ns_util.getCostsTime(left)).show();
            }
        } else {
             _dialog_left_time_obj.hide();
        }
    }

    // 중복 건설 건물에 대한 번호 받아오기 - castle_pk 번호가 빠를 수록 앞번호
    getBuildNumber (_cast_type, _cast_pk)
    {
        let bd_c = (_cast_type === 'I') ? ns_cs.d.bdic : ns_cs.d.bdoc;
        let b = bd_c[_cast_pk];
        let m = ns_cs.m.buil[b.m_buil_pk];
        if (m.yn_duplication !== 'Y') {
            return 1; // 무조건 1번
        } else {
            let _index = Object.entries(bd_c).filter(o => ns_util.isNumeric(o[0]) && ns_util.math(o[1].m_buil_pk).eq(b.m_buil_pk)).findIndex(o => ns_util.math(o[0]).eq(_cast_pk));
            return ns_util.math(_index).plus(1).integer;
        }
    }

    getBuildLimitCount (m_pk)
    {
        if (! ns_cs.m.buil[m_pk]) {
            return 0; // 건설 불가
        }
        switch (String(m_pk)) {
            case '200500': // 훈련소
                return 5;
            case '201100': // 창고
            case '201200': // 민가
                return 9;
            case '201300': // 전답
            case '201400': // 목장
            case '201500': // 벌목장
                return ns_util.math(ns_cs.m.buil['200100'].level[ns_cs.d.bdic[1].level].variation_1).div(3).number; // 대전 레벨에 따른 건설수 제한. 자원지 3개에 대한 건설수 제한이므로 3을 나눔.
            default:
                return 1; // 나머지는 모두 1개만 건설
        }
    }

    getBuildList(_m_buil_pk, _length = false)
    {
        let m = ns_cs.m.buil[_m_buil_pk];
        let bd_c = (m.type === 'I') ? ns_cs.d.bdic : ns_cs.d.bdoc;
        let buildings = Object.entries(bd_c).filter(b => ns_util.isNumeric(b[0]) && b[1]?.m_buil_pk && ns_util.math(b[1].m_buil_pk).eq(_m_buil_pk));
        return (! _length) ? buildings : buildings.length;
    }

    getCastlePk (_cast_type, _m_buil_pk, _page = 1)
    {
        let bd_c = (_cast_type === 'I') ? ns_cs.d.bdic : ns_cs.d.bdoc;
        let b = Object.entries(bd_c).filter(o => ns_util.isNumeric(o[0]) && ns_util.math(o[1].m_buil_pk).eq(_m_buil_pk))[_page - 1];
        return b?.[0] ?? false;
    }

    getTimerPk (_queue_type, _queue_pk, _cast_type, _cast_pk)
    {
        let o = null;
        let col = _cast_type === 'O' ? 'out' : 'in';
        if (_queue_type === 'C') {
            o = Object.entries(ns_cs.d.time).find(o => ns_util.isNumeric(o[0]) && o[1].queue_type === _queue_type && ns_util.math(o[1][col + '_cast_pk']).eq(_cast_pk));
        } else {
            if ( !_queue_pk ) {
                o = Object.entries(ns_cs.d.time).find(o => ns_util.isNumeric(o[0]) && o[1].queue_type === _queue_type && ns_util.math(o[1][col + '_cast_pk']).eq(_cast_pk) );
            }
            else {
                o = Object.entries(ns_cs.d.time).find(o => ns_util.isNumeric(o[0]) && o[1].queue_type === _queue_type && ns_util.math(o[1].queue_pk).eq(_queue_pk))
            }
        }
        return (! o) ? null : o[0];
    }

    getPositionCount ()
    {
        switch(ns_util.toNumber(ns_cs.d.lord['level'].v)) {
            case 1:
            case 2:
                return 1;
            case 3:
                return 2;
            case 4:
                return 3;
            case 5:
                return 4;
            case 6:
                return 5;
            case 7:
                return 6;
            case 8:
                return 7;
            case 9:
                return 8;
            case 10:
                return 10;
            default:
                return 1;
        }
    }

    getEmptyTile (_castle_type = 'I')
    {
        let bd_c = (_castle_type === 'I') ? ns_cs.d.bdic : ns_cs.d.bdoc;
        if (! bd_c?.tile) {
            return false;
        }
        let empty_tiles = Object.values(bd_c.tile).filter(t => !t._data?.m_buil_pk).map(t => t._index);
        if (empty_tiles.length < 1) {
            return false;
        }
        return empty_tiles[0]; // 가장 앞에 있는 빈 타일을 반환
    }

    getPackage (_m_package_pk = null, _first_popup = false)
    {
        ns_xhr.post('/api/package/list', { m_package_pk: _m_package_pk });
    }

    drawPackageButton (_data, _m_package_pk = null, _first_popup = false)
    {
        new Promise((resolve, reject) => {
            ns_engine.game_data.package_data = _data;
            resolve();
        }).then(() => {
            if (_first_popup) {
                ns_engine.game_data.first_popup_package = Object.keys(ns_engine.game_data.package_data).shift();
            } else {
                if (_m_package_pk) {
                    let m = ns_cs.m.package[_m_package_pk];
                    if (m.target_type === 'construction') { // 건물 건설에 경우
                        ns_dialog.setDataOpen('package_popup', { m_package_pk: _m_package_pk }); // 즉시 보여주기
                    } else if (m.target_type === 'hero') { // 영웅 모집에 경우
                        ns_dialog.dialogs.hero_pickup_result.m_package_pk = _m_package_pk;
                    }
                }
            }
            this.checkPackageButton();
        });
    }

    checkPackageButton ()
    {
        if (Object.keys(ns_engine.game_data.package_data).length > 0) {
            ns_button.buttons.main_package.obj.show();
            let o = Object.values(ns_engine.game_data.package_data).shift();
            ns_timer.timers.package_limit_timer = new nsTimerSet(() => {
                let remain_time = ns_util.math(ns_util.toInteger(o.end_date)).minus(ns_timer.now()).number;
                if (ns_util.math(remain_time).lte(0)) {
                    this.getPackage();
                    return;
                }
                ns_button.buttons.main_package.obj.find('.package_time').text(ns_util.secondToDateTime(remain_time));
            }, 1000, false); // 1초 마다
            ns_timer.timers.package_limit_timer.init();
        } else {
            ns_button.buttons.main_package.obj.hide();
            if (ns_timer.timers.package_limit_timer) {
                ns_timer.timers.package_limit_timer.clear();
                ns_timer.timers.package_limit_timer = null;
            }
        }
    }
}

class nsCsSet
{
    constructor(_options)
    {
        this.first_render = true;
        this.s = {};
        this.options = _options;
        this.base_class = null;
        if (_options && typeof _options === 'object') {
            if (_options?.base_class) {
                this.base_class = _options.base_class;
            }
        }
    }

    init ()
    {
        // empty
    }

    set (_key, _data, _render = true)
    {
        let lud = ns_timer.now();
        let lud_ms = ns_timer.nowMs();

        if (_data && typeof _data === 'object') {
            if (this?.[_key]) {
                for (let [k, d] of Object.entries(_data)) {
                    this[_key][k] = d;
                }
                this[_key]['__lud'] = lud;
                this[_key]['__lud_ms'] = lud_ms;
            } else {
                this[_key] = _data;
                this[_key]['__lud'] = lud;
                this[_key]['__lud_ms'] = lud_ms;
            }
        } else {
            this[_key] = { v: _data, __lud: lud, __lud_ms: lud_ms };
        }

        if (_render) {
            this.render(_key);
            if (this.first_render) {
                this.first_render = false;
            }
        }
    }

    render (_key, _recursive)
    {
        if (this.base_class && !_recursive) {
            this.base_class.render.call(this, _key, true);
        }
    }

    renderPost (_recursive)
    {
        if (this.base_class && !_recursive) {
            this.base_class.renderPost.call(this, true);
        }
    }

    clear ()
    {
        let this_cs = this;
        for (let [k, d] of Object.entries(this_cs)) {
            if (typeof d === 'object') {
                delete this_cs[k];
            }
        }
    }
}