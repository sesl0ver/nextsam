class nsEngine {
    constructor() {
        this.trigger = {
            is_touch_device: /Android|iPhone/i.test(navigator.userAgent),
        }
        this.cfg = {
            wrapping: false,
            init_view: false,
            init_world: false,
            app_platform: null, // TODO 차후 플렛폼 정리 필요.
            conf_web_version: 0,

            small_delay: 750, // 밀리세컨드
            normal_delay: 250,
            big_delay: 2500,
            extra_Delay: 5000,

            lp_interval_ms: 60000,
            session_timeout_ms:280000,
            free_speedup_time: 300,	// 무료 독려시간 (초)

            population_upward_default: 200,

            world_tick: null,
            quest_tick: null,

            game_server_pk: null,
            game_server_alias: null,
            game_server_name: null,
            max_num_slot_guest_hero: 500, // 영웅 슬롯 최대치
            max_officer_count: 10, // 매관매직 최대 수

            tile_title: true,

            mouse_down_event_type: (! this.trigger.is_touch_device) ? 'mousedown' : 'touchstart',
            mouse_move_event_type: (! this.trigger.is_touch_device) ? 'mousemove' : 'touchmove',
            mouse_up_event_type: (! this.trigger.is_touch_device) ? 'mouseup' : 'touchend',
            mouse_enter_event_type: (! this.trigger.is_touch_device) ? 'mouseenter' : 'touchenter',
            mouse_leave_event_type: (! this.trigger.is_touch_device) ? 'mouseleave' : 'touchleave',
            mouse_over_event_type: (! this.trigger.is_touch_device) ? 'mouseover' : 'touchenter',
            mouse_out_event_type: (! this.trigger.is_touch_device) ? 'mouseout' : 'touchcancel',
        }
        this.game_object = {}
        this.game_data = {
            sid: null,
            cpp: null,
            curr_view: 'terr',
            alliance_status: false,
            point_server_check: false,
            attack_warning_id: null,
            raid_warning_id: null,
            cursor: {
                x: 0,
                y: 0
            },
            card_deck_open: true,
            card_deck_busy: false,
            card_deck_open_prev: false,
            cache_obj: null,
            package_data: null,
            unread_item_last_up_dt: 0,
            gold_pop_tick: null,
            first_popup_package: null,
            options: {}
        }
        this.xhr = {
            xhr_url_prefix: '',

            bg_xhr: null,
            bg_xhr_abort: null,
            bg_xhr_use: false,
            bg_xhr_progress: false,
            bg_xhr_latest: 0,
            bg_xhr_count: 0,

            fg_xhr: null,
            fg_xhr_abort: null,
            fg_xhr_use: false,
            fg_xhr_progress: false,
            fg_xhr_latest: 0,
            fg_xhr_count: 0,

            lp_url: '/api/lp',
            last_lp_sequence: 0,
            lp_latest: 0, // fg call 도 처리하기 때문에 bgCmdLastest 와는 다름
        }
        this.handler = {
            lp_timer_handle: null,
        }
        this.interval = {
            attack_warning: null,
        }
        this.setData();
    }

    setData ()
    {
        this.size = {
            width: 0,
            height: 0,

            uiTopPosition: 0,
            uiTopHeight: 134, // 62
            uiMiddlePosition: 134,
            uiMiddleHeight: 0,
            uiBottomPosition: 0,
            uiBottomHeight: 53,

            uiChatHeight:32,

            terrWidth: 500,
            terrHeight: 550,
            terrMarginLeft: 0,
            bounceTerrLeftAdjust: 10,
            bounceTerrTop: 0,
            bounceTerrBottom: 0,

            worldUiUnitMargin: 45,
            terrUiUnitMargin: 5,
            terrUiUnitTopMargin: 23,
            terrUiUnitBottomMargin: 35,
            terrUiUnitWidth: 45,
            terrUiUnitHeight: 29,

            terrHeroListUiUnitMargin: 5,
            terrHeroListUiUnitWidth: 62,
            terrHeroListBtnRight: 0,

            set: (_width, _height) => {
                //alert(_width + 'x' + _height);
                this.size.width = _width;
                this.size.height = _height;
                this.size.recalculation();
            },
            recalculation: () => {
                this.size.uiMiddleHeight = ns_util.math(this.size.height).minus(this.size.uiTopHeight).plus(this.size.uiBottomHeight).number;
                this.size.uiBottomPosition = ns_util.math(this.size.uiTopHeight).plus(this.size.uiMiddleHeight).minus(4).number; // png 투명도 처리했기 때문에 4픽셀 hack
            }
        }
    }

    init ()
    {
        // device resolution
        let main_stage = document.querySelector('#main_stage');
        this.size.set(main_stage.clientWidth, main_stage.clientHeight);

        // device description
        this.cfg.is_touch_device = ns_util.isTouchDevice();
        this.game_data.curr_view = 'terr';

        // game object
        this.game_object.main_stage = new nsObject('#main_stage', document.querySelector('body'));
        this.game_object.wrap_world = new nsObject('#wrap_world');
        this.game_object.wrap_terr = new nsObject('#wrap_terr');
        this.game_object.main_top_wrap = new nsObject('.main_top_wrap');
        this.game_object.main_bottom_wrap = new nsObject('.main_bottom_wrap');
        this.game_object.main_top_button_wrap = new nsObject('.main_top_button_wrap');
        this.game_object.main_top_chat_message = new nsObject('.main_top_chat_message');
        this.game_object.warning_wrap = new nsObject('.warning_wrap');
        this.game_object.loading = new nsObject('.loading_progress');

        this.game_object.wrap_world.hide();
        this.game_object.main_top_wrap.hide();
        this.game_object.main_bottom_wrap.hide();
        this.game_object.warning_wrap.hide();
        this.game_object.loading.hide();
    }

    initView ()
    {
        this.cfg.init_view = true;

        this.game_object.main_top_wrap.show();
        this.game_object.main_bottom_wrap.show();

        ns_dialog.closeAll();
        ns_timer.init();
        ns_hero.init();
        ns_castle.init();
    }

    toggleWorld (_set_position = true)
    {
        ns_dialog.closeAll(['toast_message']);

        // world = 플로팅 아이콘 숨김
        if(this.game_data.curr_view !== 'world') {
            ns_button.buttons.main_valley_manage.obj.show();
            ns_button.buttons.main_territory_manage.obj.hide();
            this.game_object.main_top_button_wrap.hide();
            this.game_object.wrap_world.show();
            this.game_object.wrap_terr.hide();

            if (ns_world.init_world === false) {
                ns_world.init();
            } else {
                if (_set_position) {
                    const [x, y] = this.game_data.cpp.split('x');
                    ns_world.setPosition(x, y);
                }
            }
            ns_button.buttons.main_view_world.setClicked();
        } else {
            ns_button.buttons.main_valley_manage.obj.hide();
            ns_button.buttons.main_territory_manage.obj.show();
            this.game_object.main_top_button_wrap.show();
            this.game_object.wrap_world.hide();
            this.game_object.wrap_terr.show();
            ns_button.buttons.main_view_world.unsetClicked();
        }

        this.game_data.curr_view = (this.game_data.curr_view === 'world') ? 'terr' : 'world';
        ns_sound.update();
        ns_world.drawMoveTroop();
    }

    castleMove ()
    {
        if (this.game_data.curr_view === 'world') {
            // 세계 지도를 보고 있는 경우는 막음.
            return;
        }
        let territory = new nsObject('#wrap_terr #terr');
        if (! territory.hasCss('in_castle')) {
            territory.addCss('in_castle');
            territory.removeCss('out_castle');
        } else {
            territory.addCss('out_castle');
            territory.removeCss('in_castle');
        }
    }

    castleMoveSide ()
    {
        let territory = new nsObject('#wrap_terr #terr');
        if (! territory.hasCss('move_side')) {
            territory.addCss('move_side');
        } else {
            territory.removeCss('move_side');
        }
    }

    changeView ()
    {
        // TODO 영지 이동 등?
    }

    clearForNewSession ()
    {
        ns_dialog.closeAll();

        if (ns_engine.game_data.curr_view !== 'world') {
            ns_engine.toggleWorld();
        }

        ns_cs.d.time.renderPost();
    }

    changeTerritory (_posi_pk)
    {
        ns_cs.startSession(false, true, _posi_pk);
    }

    lpTimer ()
    {
        ns_engine.lpRequest();
    }

    lpRequest ()
    {
        if (this.xhr.bg_xhr_use === true) {
            return;
        }
        this.xhr.bg_xhr_use = true;

        if (this.xhr.bg_xhr !== null) {
            try {
                this.xhr.bg_xhr_abort.abort();
            } catch (_e) {
                console.error(_e);
            }
            delete this.xhr.bg_xhr;
            delete this.xhr.bg_xhr_abort;
            this.xhr.bg_xhr = null;
            this.xhr.bg_xhr_abort = null;
        }

        let post_data = {};
        post_data.sid = this.game_data.sid;
        post_data.cpp = this.game_data.cpp;
        post_data.l = this.xhr.last_lp_sequence;
        post_data.ns_web_version = ns_web_version;
        if (ns_auth.only_platform_mode === true) {
            post_data.uuid = ns_auth.getUuid();
        }
        // post_data.chat_max = 0; // qbw_chat.getMax()
        let headers = {};
        if (ns_auth.only_platform_mode === true) {
            headers = {
                Authorization: `Bearer ${ns_auth.getToken()}`,
            }
        }
        this.xhr.bg_xhr_abort = new AbortController();

        let xhr_instance = axios.create({
            headers: headers,
            method: 'post',
            baseURL: `//${document.location.host}/${this.xhr.xhr_url_prefix}${this.xhr.lp_url}`,
            timeout: 30000,
            responseType: 'json',
            signal: this.xhr.bg_xhr_abort.signal
        });
        this.xhr.bg_xhr = xhr_instance({ data: post_data }).then((response) => { // success
            this.lpCallback(response.data, true);
        }).catch(function (error) { // error
            console.error(error);
        })
        .finally(() => { // complete
            this.xhr.bg_xhr_use = false;
            this.xhr.bg_xhr_latest = ns_timer.now();
            this.xhr.bg_xhr_count++;
        });
    }

    lpCallback (_data, _real_lp = false)
    {
        let ll = 0, deck_redraw = false;
        for (let [__key, __data] of Object.entries(_data)) {
            let p = __key.lastIndexOf("_");
            if (p >= -1) {
                let _len = __key.substring(p + 1);
                if (ns_util.isNumeric(_len)) {
                    let l = ns_util.toInteger(_len);
                    if (ll < l) {
                        ll = l;
                    }
                    __key = __key.substring(0, p);
                } else {
                    // 임시 처리
                    if (__data?.code && __data?.code !== 'success') {
                        ns_dialog.dialogs.message.close_game_over = true;
                        let _message = ns_i18n.t('msg_server_disconnect');
                        if (__data?.code === 'duplication') {
                            _message = ns_i18n.t('msg_duplicate_connection_error');
                        }
                        ns_dialog.closeAll();
                        ns_dialog.setDataOpen('message', { error_msg:'error', text: _message });
                        return;
                    }
                }
            }

            /* ************************** 명령 처리 시작 ************************ */
            switch (__key) {
                case 'LORD': // 군주
                    this.eachUpdate('lord', __data, true);
                    break;
                case 'RESO':
                    this.eachUpdate('reso', __data);
                    break;
                case 'PROD':
                    this.eachUpdate('prod', __data);
                    break;
                case 'GOLDPOP':
                case 'TERR':
                    this.eachUpdate('terr', __data);
                    break;
                case 'BUIL_IN_CAST':
                    this.eachUpdate('bdic', __data, true);
                    break;
                case 'BUIL_OUT_CAST':
                    this.eachUpdate('bdoc', __data, true);
                    break;
                case 'TIME':
                    this.eachUpdate('time', __data, true);
                    break;
                case 'TECH':
                    this.eachUpdate('tech', __data);
                    break;
                case 'LORD_TECH':
                    this.eachUpdate('lord_tech', __data);
                    break;
                case 'HERO':
                    this.eachUpdate('hero', __data);
                    deck_redraw = true;
                    break;
                case 'ARMY':
                    this.eachUpdate('army', __data);
                    break;
                case 'ARMY_MEDI':
                    this.eachUpdate('army_medi', __data);
                    break;
                case 'FORT':
                    this.eachUpdate('fort', __data);
                    break;
                case 'ITEM':
                    this.eachUpdate('item', __data);
                    break;
                case 'ITEM_BUY':
                    this.eachUpdate('item_buy', __data);
                    break;
                case 'CASH':
                    this.eachUpdate('cash', __data);
                    break;
                case 'QUES':
                    this.eachUpdate('ques', __data, true);
                    break;
                case 'QUEUE':
                    this.eachUpdate('queue', __data, true);
                    break;
                case 'EVENT':
                    this.eachUpdate('event', __data, true);
                    break;
                case 'PICKUP':
                    this.eachUpdate('pickup', __data, true);
                    break;
                case 'NPC_SUPP':
                    ns_cs.d.npc_supp.clear();
                    this.eachUpdate('npc_supp', __data);
                    break;
                case 'PUSH':
                    this.eachUpdate('push', __data);
                    break;
                case 'ALLI':
                    this.eachUpdate('alli', __data);
                    break;
                case 'ALLY_RELA':
                    ns_cs.d.ally_rela.clear();
                    this.eachUpdate('ally_rela', __data);
                    break;
                case 'MOVE_TROOP_ALL':
                    // ns_cs.d.move_troop.set('list', __data);
                    break;
                case 'MOVE_TROOP':
                    // ns_cs.d.move_troop.list.push(__data);
                    break;
                case 'MOVE_TROOP_UPDATE':
                    // ns_world.update_troop_line(d);
                    break;
                case 'MOVE_TROOP_END':
                    // ns_world.remove_troop_line(d);
                    break; // break가 빠져있었는데 의도인가?
                case 'TROOP':
                    ns_cs.d.troop.clear();
                    this.eachUpdate('troop', __data, true);
                    break;
                case 'UPDATE_ALLIANCE':
                    ns_dialog.dialogs.alliance.updateAlliance(__data);
                    break;
                case 'REDUCE':
                    ns_timer.reduceAgain(ns_util.toNumber(__data.sTime));
                    break;
                case 'PROC_TIME':
                    break;
                case 'LP_DUPL': // TODO 동시접속 제한. 현재 버전에서는 제대로 동작하지 않음.
                    // ns_dialog.closeAll();
                    // ns_dialog.dialogs.message.close_game_over = true;
                    // ns_dialog.setDataOpen('message', { text: system_text.message.e_ending });
                    break;
                case 'ERR_MT':
                    ns_dialog.setDataOpen('message_mt');
                    return;
                case 'ERR_UPDATE':
                    ns_dialog.setDataOpen('message_update', { error_msg:'error', text: __data.message });
                    return;
                case 'KICK':
                    ns_dialog.dialogs.message.close_game_over = true;
                    ns_dialog.setDataOpen('message', { error_msg:'error', text: ns_i18n.t('msg_server_disconnect') });
                    return;
                case 'WORLD':
                    let coords = ns_world.coords.get(__data.posi_pk);
                    if (typeof __data === 'object') {
                        for (let [k, d] of Object.entries(__data)) {
                            coords.update(k, d);
                        }
                    }
                    return;
                case 'NEXT_DAY':
                    ns_dialog.dialogs.message.close_game_over = true;
                    ns_dialog.setDataOpen('message', { error_msg:'error', text: ns_i18n.t('msg_next_day_refresh_game') });
                    setTimeout(() => {
                        document.location.reload();
                    }, 60000);
                    return;
                default:
                    console.log(`"${__key}" is not implementation`, __data);
                    break;
            }
            /* ************************** 명령 처리 종료 ************************ */

            if (ll > this.xhr.last_lp_sequence && _real_lp) {
                this.xhr.last_lp_sequence = ll;
            }

            this.xhr.lp_latest = ns_timer.now();
        }

        if (this.cfg.init_view !== true) {
            this.initView();
            this.handler.lp_timer_handle = setInterval(ns_engine.lpTimer, this.cfg.lp_interval_ms);
        }

        if (deck_redraw === true) {
            ns_hero.deckReload();
        }
    }

    eachUpdate (_target, _data, _render_post = false)
    {
        if (typeof _data === 'object') {
            for (let [__key, __data] of Object.entries(_data)) {
                ns_cs.d[_target].set(__key, __data);
            }
        }
        if (_render_post === true) {
            ns_cs.d?.[_target]?.renderPost();
        }
    }

    buyQbig ()
    {
        window.open(`/redirect?type=purchase&platform=${ns_engine.cfg.app_platform}`, '_blank');
    }

    inquiry ()
    {
        window.open(`/redirect?type=inquiry&platform=${ns_engine.cfg.app_platform}`, '_blank');
    }
}
let ns_engine = new nsEngine();