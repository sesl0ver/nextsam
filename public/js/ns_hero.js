class nsHero
{
    constructor() {
        this.obj = {};
        this.init_deck = false;
        this.current_view = false;
        this.scroll_handle = null;

        this.filter_sort = 'level';
        this.filter_show_work = true;
        this.filter_show_idle = true;

        this.filter_sorted_array = [];
        this.current_sorted_array = [];
        this.card_deck_count_total = 0;
        this.card_deck_count_show = 0;
        this.timed_jobs = {}; // 강화 중 시간 표시용

        this.current_scroll_y = 0;

        this.on_select = false;
        this.on_select_callback = null;
        this.on_select_sort_stat_type = null;
        this.on_select_limit_stat_type = null;
        this.on_select_limit_stat_value = 0;
        this.on_select_possible_count = 0;
        this.on_select_recom_hero_pk = null;
        this.on_select_selector_use = null;
        this.on_select_selector_hero_pk = null;
        this.on_select_view_group = false;
    }

    init ()
    {
        // 오브젝트 정의
        this.obj.hero_deck_list_wrap = new nsObject('.hero_deck_list_wrap');
        this.obj.hero_deck_list = new nsObject('#hero_deck_list');
        this.obj.hero_skeleton_sm = new nsObject('#hero_card_sm_skeleton');
        this.obj.hero_card_skeleton = new nsObject('#hero_card_small_skeleton');
        this.obj.hero_card_detail_skeleton = new nsObject('#hero_card_detail_skeleton');

        ns_button.buttons.hero_select_filter_show_work.setClicked();
        ns_button.buttons.hero_select_filter_show_idle.setClicked();

        ns_select_box.set('hero_select_filter', this.filter_sort, true);
        ns_button.buttons.hero_select_filter.obj.text(ns_select_box.getText('hero_select_filter'));

        // 필터링
        this.deckFilter();

        // 덱 그리기
        this.deckDraw();

        // ns_cs.d.hero.render 작동 허용
        this.init_deck = true;

        // 스크롤
        this.scroll_handle = new nsScroll(this.obj.hero_deck_list_wrap.element, this.obj.hero_deck_list.element);

        // 전용 타이머핸들러 등록
        // qbw_timer.timers.qbw_hero = new qbw_timer_class(ns_hero.timerHandler_proc, -1, 1000, true);
        // qbw_timer.timers.ns_hero.init();
    }

    toggleDeckList ()
    {
        if (! this.obj.hero_deck_list_wrap.hasCss('hide_list')) {
            this.hideDeckList();
        } else {
            this.showDeckList();
        }
    }

    showDeckList ()
    {
        if (this.current_view) {
            return;
        }
        this.scroll_handle.initScroll();
        this.obj.hero_deck_list_wrap.removeCss('hide_list');
        this.current_view = true;
    }

    hideDeckList ()
    {
        if (! this.current_view) {
            return;
        }
        this.obj.hero_deck_list_wrap.addCss('hide_list');
        this.current_view = false;
    }

    deckReload ()
    {
        if (! this.init_deck) {
            return;
        }
        this.filter_sort  = ns_select_box.get('hero_select_filter').val;

        this.filter_show_work = ns_button.buttons.hero_select_filter_show_work.clicked;
        this.filter_show_idle = ns_button.buttons.hero_select_filter_show_idle.clicked;

        this.deckListInit();
        this.deckFilter();
        this.deckDraw();
    }

    deckFilter ()
    {
        this.filter_sorted_array = [];

        // 정렬을 위한 기초데이터
        for (let [k, d] of Object.entries(ns_cs.d.hero)) {
            if (! ns_util.isNumeric(k)) {
                continue;
            }
            this.filter_sorted_array.push({
                rare_type: d.rare_type,
                level: d.level,
                officer: d.m_offi_pk,
                leadership: d.leadership,
                mil_force: d.mil_force,
                intellect: d.intellect,
                politics: d.politics,
                charm: d.charm,
                loyalty: d.loyalty,
                hero_pk: d.hero_pk,
                group_type: d.group_type,
                group_order: d.group_order
            });
        }
        // 정렬 (선택 데이터로 정렬하고 관직으로 마무리 - 관직 없는 영웅은 없다)
        new Promise((resolve, reject) => {
            this.filter_sorted_array = ns_util.arraySort(this.filter_sorted_array, (this.filter_sort !== 'officer') ? 1 : -1, this.filter_sort);
            resolve();
        }).then(() => {
            if (this.filter_sort !== 'officer') {
                this.filter_sorted_array = ns_util.arraySort(this.filter_sorted_array, -1, 'officer');
            }
        });
    }

    deckDraw ()
    {
        this.card_deck_count_total = 0;
        this.card_deck_count_show = 0;
        let card_deck_count_total = 0,
            card_deck_count_idle = 0,
            card_deck_count_assign = 0,
            card_deck_count_command = 0,
            card_deck_count_treatment = 0;

        this.obj.hero_deck_list.empty();
        this.timed_jobs = {};

        this.current_sorted_array = [];
        for (let [k, v] of Object.entries(this.filter_sorted_array)) {
            this.card_deck_count_total++;
            let d = ns_cs.d.hero[v.hero_pk];
            try {
                if (d && d.status_cmd) {
                    card_deck_count_total++;
                    switch (d.status_cmd) {
                        case 'I': card_deck_count_idle++; break;
                        case 'A': card_deck_count_assign++; break;
                        case 'C': card_deck_count_command++; break;
                        case 'T': card_deck_count_treatment++; break;
                    }
                    if ((d.status_cmd === 'I' && ns_hero.filter_show_idle) || (d.status_cmd !== 'I' && ns_hero.filter_show_work)) {
                        let card = this.cardDraw(d.hero_pk, true, true, null, false, false, false, false, true, ns_hero.on_select_view_group);
                        this.obj.hero_deck_list.append(card);
                        this.current_sorted_array.push(d.hero_pk);
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }

    }

    deckListInit () {
        ns_hero.scroll_handle.initScroll();
    }

    deckSelectionSet (_on_select_callback, _on_select_sort_stat_type, _on_select_limit_stat_type, _on_select_limit_stat_value, _on_select_selector_use)
    {
        if (! this.init_deck) {
            return;
        }

        // 선택모드 활성화
        this.on_select = true;
        this.on_select_callback = _on_select_callback;
        this.on_select_sort_stat_type = _on_select_sort_stat_type;
        this.on_select_limit_stat_type = _on_select_limit_stat_type;
        this.on_select_limit_stat_value = _on_select_limit_stat_value;
        this.on_select_possible_count = 0;
        this.on_select_recom_hero_pk = null;
        this.on_select_selector_use = _on_select_selector_use ? _on_select_selector_use : true;
        this.on_select_selector_hero_pk = null;
        this.on_select_view_group = true;

        this.savecfgs = {
            filter_sort: ns_hero.filter_sort,
            filter_show_work: ns_hero.filter_show_work,
            filter_show_idle: ns_hero.filter_show_idle,
            // deck_list_left: this.obj.hero_deck_list.css('left'),
            card_deck_open: ns_engine.game_data.card_deck_open
        };

        // $('#map_wrap_trans').show();
        // $('#ui_wrap_trans').show();
        // $('#deck_wrap').css('z-index', 1400);
        // this.deckObj.css('z-index', 1400);
        ns_dialog.dialogs.select_box.obj.classList.add('always_on_top');
        this.obj.hero_deck_list_wrap.addCss('always_on_top');
        this.showDeckList();

        // 카드덱 닫혀 있으면 열기
        if (! ns_engine.game_data.card_deck_open) {
            this.bottomUiToggle(true);
        }

        // 검색 필터 적용
        ns_select_box.set('hero_select_filter', _on_select_sort_stat_type ? _on_select_sort_stat_type : 'level');
        ns_button.buttons.hero_select_filter.obj.text(ns_select_box.getText('hero_select_filter'));
        ns_button.buttons.hero_select_filter_show_work.unsetClicked();
        ns_button.buttons.hero_select_filter_show_idle.setClicked();

        this.deckReload();

        // 카드 추천 들어가기
        if (this.on_select_selector_hero_pk) {
            //  onSelect_callback
            if (this.on_select_callback) {
                this.on_select_callback.call(null, this.on_select_selector_hero_pk);
            }
        }
    }

    deckSelectionUnset ()
    {
        if (!ns_hero.init_deck)
            return;

        // 선택모드 비활성화
        ns_hero.on_select = false;
        ns_hero.on_select_possible_count = 0;
        ns_hero.on_select_recom_hero_pk = null; // 사실 별 필요는...
        ns_hero.on_select_selector_hero_pk = null; // 사실 별 필요는...

        ns_hero.on_select_view_group = false;

        // 검색 필터 복구
        ns_select_box.set('hero_select_filter', ns_hero.savecfgs.filter_sort);
        ns_button.buttons.hero_select_filter.obj.text(ns_select_box.getText('hero_select_filter'));
        if (ns_hero.savecfgs.filter_show_work) {
            ns_button.buttons.hero_select_filter_show_work.setClicked();
        } else {
            ns_button.buttons.hero_select_filter_show_work.unsetClicked();
        }
        if (ns_hero.savecfgs.filter_show_idle) {
            ns_button.buttons.hero_select_filter_show_idle.setClicked();
        } else {
            ns_button.buttons.hero_select_filter_show_idle.unsetClicked();
        }

        ns_hero.deckReload();

        // ns_hero.deckListInit(ns_hero.savecfgs.deck_list_left);

        // 카드덱 오픈상태 복구
        if (! ns_hero.savecfgs.card_deck_open) {
            this.bottomUiToggle();
        }

        // $('#map_wrap_trans').hide();
        // $('#ui_wrap_trans').hide();
        // $('#deck_wrap').css('z-index', 1140);
        // ns_hero.deckObj.css('z-index', 1140);
        ns_dialog.dialogs.select_box.obj.classList.remove('always_on_top');
        this.obj.hero_deck_list_wrap.removeCss('always_on_top');
        this.hideDeckList();
    }

    cardDraw (_hero_pk, _is_rollover, _is_trans, _hero_data, _is_un_assign, _is_troop_order, _is_non_clicked, _is_wall, _is_warn, _is_group, _is_combine, _is_cancel)
    {
        let new_card;
        try {
            _is_rollover = (! _is_rollover) ? true : _is_rollover;
            _is_trans = (! _is_trans) ? false : _is_trans;
            _hero_data = (! _hero_data) ? null : _hero_data;
            _is_un_assign = (! _is_un_assign) ? false : _is_un_assign;
            _is_troop_order = (! _is_troop_order) ? false : _is_troop_order;
            _is_non_clicked = (! _is_non_clicked) ? false : _is_non_clicked;
            _is_wall = (! _is_wall) ? false : _is_wall;
            _is_warn = (! _is_warn) ? false : _is_warn;
            _is_group = (! _is_group) ? false : _is_group;
            _is_combine = (! _is_combine) ? false : _is_combine;
            _is_cancel = (! _is_cancel) ? false : _is_cancel;

            // 소스 데이터 선택 - ns_cs.d.hero or onetime object
            let d = (_hero_data === null) ? ns_cs.d.hero[_hero_pk] : _hero_data;
            let m_hero = ns_cs.m.hero[d.m_hero_pk];
            let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];

            new_card = this.obj.hero_card_skeleton.clone();
            new_card.dataSet('hero_pk', d.hero_pk);
            new_card.dataSet('rare_type', m_hero_base.rare_type);

            // new_card.addCss(`ns_hero_${d.hero_pk}`); // TODO 이거 대신 dataset 을 사용하는게 더 나을듯 = document.querySelector('[data-hero_pk="30806696"]')
            new_card.addCss(`hero_card_${m_hero_base.m_hero_base_pk}`);

            let _card_opacity = new nsObject('.hero_card_opacity', new_card);
            let _card_frame = new nsObject('.hero_card_frame', new_card);
            let _card_name = new nsObject('.hero_card_name', new_card);
            let _card_level = new nsObject('.hero_card_level', new_card);
            let _card_status = new nsObject('.hero_card_status', new_card); // TODO before, after 로 처리해도 될듯?
            let _card_group_display = new nsObject('.hero_card_group_display', new_card); // TODO before, after 로 처리해도 될듯?
            let _card_selector = new nsObject('.hero_card_selector', new_card); // TODO before, after 로 처리해도 될듯?
            let _card_warning = new nsObject('.hero_card_warning', new_card);
            let _card_status_desc = new nsObject('.hero_card_status_desc', new_card);

            _card_opacity.hide();
            _card_frame.addCss(`hero_rare${m_hero_base.rare_type}`);
            _card_name.text(ns_i18n.t(`hero_name_${m_hero.m_hero_base_pk}`));
            _card_level.text(m_hero.level);

            if (m_hero_base.yn_modifier === 'Y') {
                _card_name.addCss('hero_card_name_modifier');
            }

            _card_status_desc.hide();
            _card_group_display.hide();

            if (_is_trans) {
                if (d.status === 'A') {
                    // 카드덱
                    if (d.status_cmd === 'A') {
                        _card_status.addCss('hero_card_status_assigned');
                        _card_status_desc.show().find('span').text(this.findAssignDescription(_hero_pk));
                    } else if (d.status_cmd === 'C') {
                        _card_status.addCss('hero_card_status_cmd_ing');
                        _card_status_desc.show().find('span').text(this.findCmdDescription(d.cmd_type));
                    } else if (d.status_cmd === 'T') {
                        _card_status.addCss('hero_card_status_treatment');
                    } else if (d.status_cmd === 'P') {
                        _card_status.addCss('hero_card_status_enchant');

                        _card_status_desc.show();
                        _card_status_desc.find('strong').text(ns_i18n.t('hero_enchanting'));
                        _card_status_desc.find('.cont_cost_time').text('.');

                        // 강화 시간 계산용
                        this.timed_jobs[_hero_pk] = d.timedjob_dt_ut;
                    } else {
                        // 'I' 대기 - 선택모드일 경우 not_condition 체크
                        if (this.on_select === true && this.on_select_limit_stat_type != null) {
                            if (ns_util.math(d[this.on_select_limit_stat_type]).lt(this.on_select_limit_stat_value) || ns_util.math(d.loyalty).lt(1)) {
                                // 여기서 그냥 불만족 인지 태업인지 체크 가능
                                if (ns_util.math(d.loyalty).lt(1)) {
                                    _card_status.addCss('hero_card_status_strike');
                                } else {
                                    // 조건 불만족
                                    _card_status.addCss('hero_card_status_not_condition');
                                }

                                _card_opacity.show();
                            } else {
                                // 조건 만족
                                this.on_select_possible_count++;

                                // 추천 카드 선택
                                if (this.on_select_recom_hero_pk == null) {
                                    this.on_select_recom_hero_pk = _hero_pk;
                                    if (this.on_select_selector_use === true) {
                                        this.on_select_selector_hero_pk = _hero_pk;
                                    }
                                }

                                // 추천 마크
                                if (this.on_select_recom_hero_pk === _hero_pk) {
                                    _card_status.addCss('hero_card_status_recommend');
                                    _card_status_desc.show().find('span').text(ns_i18n.t(`stats_${this.on_select_limit_stat_type}`));
                                }

                                // 선택액자
                                if (this.on_select_selector_hero_pk === _hero_pk) {
                                    _card_selector.addCss('hero_card_selector_frame');
                                }
                            }
                        } // end of if - notcond 체크
                    } // end of if - d.status_cmd

                    if (d.status_cmd !== 'I') {
                        _card_opacity.show();
                    }
                } else if (d.status === 'C') {
                    // 군주 카드 변경
                    if (d.status_cmd === 'S') {
                        _card_opacity.show();
                    }
                } else {
                    // 영웅 관리
                    if (d.status === 'G' && d.status_cmd === 'P') {
                        _card_status_desc.show();
                        _card_status_desc.find('strong').text(ns_i18n.t('hero_enchanting'));
                        _card_status_desc.find('.cont_cost_time').text('.');

                        _card_opacity.show();
                    } else {
                        let z;
                        if (d.status === 'C') {
                            z = '포획 중'; // 사용안함
                        } else if (d.status === 'S') {
                            z = '투항 중'; // 사용안함
                        } else {  // 'V'
                            z = ns_i18n.t('hero_waiting'); // 대기중
                        }

                        _card_status_desc.show();
                        _card_status_desc.find('strong').text(z);
                        _card_status_desc.find('.cont_cost_time').text('.');

                        _card_opacity.show();
                    }
                }
            } else if (_is_un_assign && d.status === 'A' && d.status_cmd === 'A') {
                let _button_name = 'hero_un_assign_' + _is_un_assign; // _is_un_assign = castle_pk
                // unique button
                if (_is_wall) {
                    _button_name += _is_wall;
                }

                _card_status.append(`<span id="ns_button_${_button_name}"></span>`);

                ns_button.buttons[_button_name] = new nsButtonSet(_button_name, 'button_un_assign', 'A', { base_class: ns_button.buttons.hero_un_assign });
                if (_is_wall) {
                    ns_button.buttons[_button_name].wall = _is_wall;
                }
            } else if (_is_un_assign && _is_troop_order) {
                let _button_name = 'hero_un_assign_' + _is_troop_order; // _isTroopOrder 에는 captain, director, staff 3가지 값이 온다.

                _card_status.append(`<span id="ns_button_${_button_name}"></span>`);

                ns_button.buttons[_button_name] = new nsButtonSet(_button_name, 'button_un_assign', 'A', { base_class: ns_button.buttons.hero_un_assign });
                ns_button.buttons[_button_name].troop_order = true;
            } else if (_is_combine) {
                let _button_name = 'hero_combine_cancel_' + _hero_pk;

                _card_status.append(`<span id="ns_button_${_button_name}"></span>`);

                ns_button.buttons[_button_name] = new nsButtonSet(_button_name, 'button_un_assign', 'A', { base_class: ns_button.buttons.hero_un_assign });
            } else if (_is_cancel) {
                let _button_name = 'hero_trade_cancel_' + _hero_pk;

                _card_status.append(`<span id="ns_button_${_button_name}"></span>`);

                ns_button.buttons[_button_name] = new nsButtonSet(_button_name, 'button_un_assign', 'A', { base_class: ns_button.buttons.hero_un_assign });
            }

            if (_is_rollover === true) {
                // new_card.setEvent([ns_engine.cfg.mouse_enter_event_type, ns_engine.cfg.mouse_move_event_type, ns_engine.cfg.mouse_leave_event_type], (_e) => { this.dispatcher(d, _e); });
                if (_is_non_clicked !== true) {
                    new_card.setEvent([ns_engine.cfg.mouse_down_event_type, ns_engine.cfg.mouse_up_event_type], (_e) => { this.dispatcher(d, _e); });
                }
            }

            if (_is_warn === true) {
                if (typeof d == 'object' && d.loyalty < 60) {
                    _card_warning.addCss('hero_card_warnning_on');
                }
            }

            if (_is_group === true) {
                _card_group_display.show();
                if (typeof d === 'object' && typeof d['group_type'] === 'string') {
                    _card_group_display.addCss([`hero_card_group_${d['group_type']}`, `hero_card_group_order_${d['group_order']}`]);
                }
            }

            return new_card;
        } finally {
            new_card = null;
        }
    }

    cardDetailDraw (_hero_pk, _is_rollover, _is_trans, _hero_data, _is_un_assign, _is_troop_order, _is_clicked, _is_wall, _is_warn)
    {
        try {
            _is_rollover = (! _is_rollover) ? true : _is_rollover;
            _is_trans = (! _is_trans) ? false : _is_trans;
            _hero_data = (! _hero_data) ? null : _hero_data;
            _is_un_assign = (! _is_un_assign) ? false : _is_un_assign;
            _is_troop_order = (! _is_troop_order) ? false : _is_troop_order;
            _is_wall = (! _is_wall) ? false : _is_wall;
            _is_warn = (! _is_warn) ? false : _is_warn;

            let d = (_hero_data === null) ? ns_cs.d.hero[_hero_pk] : _hero_data;

            let m_hero = ns_cs.m.hero[d.m_hero_pk], m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];

            let new_card_detail = this.obj.hero_card_detail_skeleton.clone();
            let detail_object = new nsObject(new_card_detail);
            let new_card = this.cardDraw(_hero_pk, _is_rollover, _is_trans, _hero_data, _is_un_assign, _is_troop_order, _is_clicked, _is_wall, _is_warn);
            detail_object.find('.content_card').empty().append(new_card);

            let stat_limit = 100; // 능력치 100 기준

            // 스텟 입력
            for (let _stat of ['leadership', 'mil_force', 'intellect', 'politics', 'charm']) {
                detail_object.find(`.hero_card_gauge.${_stat} > .status_value`).html(this.getPlusStat(d, _stat));
                detail_object.find(`.hero_card_gauge.${_stat} > .status_gauge`).element.style.width = ((d[_stat] > stat_limit) ? stat_limit : d[_stat]) + '%';
            }

            return new_card_detail;
        } catch (e) {
            console.error(e);
        }
    }

    cardSmDraw (_hero_pk)
    {
        let sm_card = this.obj.hero_skeleton_sm.clone();
        try {
            let d = ns_cs.d.hero[_hero_pk];
            let m_hero = ns_cs.m.hero[d.m_hero_pk];
            let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];

            sm_card.find('.hero_card_sm_image').addCss(`hero_card_${m_hero_base.m_hero_base_pk}`);
            sm_card.find('.hero_card_sm_name').text(ns_i18n.t(`hero_name_${m_hero.m_hero_base_pk}`));
            if (m_hero_base.yn_modifier === 'Y') {
                sm_card.find('.hero_card_sm_name').addCss('hero_card_modifier');
            }
            sm_card.find('.hero_card_sm_rare').text('★' + m_hero_base.rare_type);
            //sm_card.find('.hero_card_sm_level > span:last-child').text(d.level);
            sm_card.find('.hero_card_sm_level').text(`Lv. ${d.level}`);
        } catch (e) {
            console.error(e);
        }
        return sm_card;
    }

    getPlusStat (d, _type)
    {
        let _stat = ns_util.toInteger(d[_type]);
        let _stat_plus = document.createElement('div');
        if (ns_util.math(d[`${_type}_plusstat`]).gt(0)) {
            _stat_plus.innerHTML += this.makeHeroStat(d[`${_type}_plusstat`], 'plus');
        }
        if (ns_util.math(d[`${_type}_enchant`]).gt(0)) {
            _stat_plus.innerHTML += this.makeHeroStat(d[`${_type}_enchant`], 'enchant');
        }
        if (d?.m_offi_pk && ns_util.math(d.m_offi_pk).gt(0) && ns_util.math(ns_cs.m.offi[d.m_offi_pk][`stat_plus_${_type}`]).gt(0)) {
            _stat_plus.innerHTML += this.makeHeroStat(ns_cs.m.offi[d.m_offi_pk][`stat_plus_${_type}`], 'officer');
        }
        if (ns_util.math(d[`${_type}_skill`]).gt(0)) {
            _stat_plus.innerHTML += this.makeHeroStat(d[`${_type}_skill`], 'skill');
        }
        return (_stat_plus.innerHTML === '') ? _stat : `${_stat} (${_stat_plus.innerHTML})`;
    }

    makeHeroStat (_stat, _type)
    {
        let span = document.createElement('span');
        span.classList.add(`text_hero_stat_${_type}`);
        span.innerText = `+${_stat}`;
        return span.outerHTML;
    }

    cardEmpty ()
    {
    }

    cardDetailEmpty ()
    {
    }

    cardSmEmpty (_hero_type)
    {
        let hero_text = ns_i18n.t('none');
        if(_hero_type) {
            hero_text = ns_i18n.t(`troop_${_hero_type}`) + '<br />' + hero_text;
        }
        let sm_card = this.obj.hero_skeleton_sm.clone();
        try {
            sm_card.find('.hero_card_sm_image').addCss('hero_card_sm_image');
            sm_card.find('.hero_card_sm_name').html(hero_text);
            sm_card.find('.hero_card_sm_rare').remove();
            sm_card.find('.hero_card_sm_level').remove();
        } catch (e) {
            console.error(e);
        }
        return sm_card;
    }

    dispatcher (_d, _e)
    {
        // 마우스인 경우 좌클릭만 동작 하도록
        if (! ns_engine.trigger.is_touch_device && _e.button !== 0) {
            return false;
        }
        // 매직큐브 애니메이션 처리 중 버튼 중단
        if (ns_dialog.dialogs.magic_cube?.is_start_animate === true) {
            return false;
        }
        // 명령어 실행 중 검사
        if (ns_engine.xhr.bg_xhr_progress === true) {
            return;
        }

        let hero_pk = _d.hero_pk;
        let _card = this.obj.hero_deck_list.find(`[data-hero_pk="${hero_pk}"]`);

        if (_e.type === ns_engine.cfg.mouse_up_event_type) {
            let _y = (! ns_engine.trigger.is_touch_device) ? _e.clientY : _e.changedTouches[0].clientY;
            if (ns_util.math(this.current_scroll_y).minus(_y).abs().gte(5)) {
                return;
            }
            if (ns_hero.on_select) {
                // 선택가능 여부 검사
                let _card_opacity = new nsObject('.hero_card_opacity', _card.element);
                if (_card_opacity.hasCss('show')) {
                    ns_dialog.setDataOpen('message', ns_i18n.t('msg_hero_nonconformity')); // 부적합한 영웅 입니다.
                } else {
                    // 이미 선택한 영웅의 프레임 해제
                    if (this.on_select_selector_hero_pk) {
                        let _prev_card = this.obj.hero_deck_list.find(`[data-hero_pk="${this.on_select_selector_hero_pk}"]`);
                        _prev_card.find('.hero_card_selector').removeCss('hero_card_selector_frame');
                    }
                    this.on_select_selector_hero_pk = hero_pk;
                    _card.find('.hero_card_selector').addCss('hero_card_selector_frame');

                    // on_select_callback
                    if (typeof this.on_select_callback === 'function') {
                        this.on_select_callback.call(null, this.on_select_selector_hero_pk);
                    }
                }
            } else {
                // 클릭시에는 영웅 상세 정보 창이 따로 뜨므로 팝업창이 나오면 안됨
                // ns_dialog.close('pop_hero_preview');

                ns_sound.play('button_4');

                // 영웅 상세 정보창 띄우기
                ns_dialog.setData('hero_card', hero_pk);
                ns_dialog.open('hero_card');
            }
        } else if (_e.type === ns_engine.cfg.mouse_down_event_type) {
            this.current_scroll_y = (! ns_engine.trigger.is_touch_device) ? _e.clientY : _e.changedTouches[0].clientY;
        }
    }

    getStatus (_hero_pk, _status_cmd, _cmd_type)
    {
        let hero_status_text = code_set['hero_status_cmd'][_status_cmd];

        let _o;
        if (_status_cmd === 'A') { // 배속 상태이면 배속된 건물명을 가져옴
            if (ns_cs.d.terr['wall_director_hero_pk'].v === _hero_pk || ns_cs.d.terr['wall_staff_hero_pk'].v === _hero_pk) {
                hero_status_text += ' (' + ns_i18n.t(`build_title_201600`) + ')';
            } else {
                _o = Object.entries(ns_cs.d.bdic).filter(o => ns_util.isNumeric(o[0])).map(o => o[1]).find(o => o.assign_hero_pk === 30806696);
                if (_o) {
                    hero_status_text += ` (${ns_i18n.t(`build_title_${_o.m_buil_pk}`)})`;
                }
            }
        } else if (_status_cmd === 'C') {
            if (_cmd_type === 'Const') {
                hero_status_text = ns_i18n.t('construction');
                _o = Object.entries(ns_cs.d.bdic).filter(o => ns_util.isNumeric(o[0])).map(o => o[1]).find(o => o.buil_hero_pk === 30806696);
                if (_o) {
                    hero_status_text += ` (${ns_i18n.t(`build_title_${_o.m_buil_pk}`)})`;
                }
                _o = Object.entries(ns_cs.d.bdoc).filter(o => ns_util.isNumeric(o[0])).map(o => o[1]).find(o => o.buil_hero_pk === 30806696);
                if (_o) {
                    hero_status_text += ` (${ns_i18n.t(`build_title_${_o.m_buil_pk}`)})`;
                }
            } else {
                hero_status_text += ` (${code_set['hero_cmd_type'][_cmd_type]})`;
            }
        }

        return hero_status_text;
    }
    getEffect (_hero_pk, _hero_data, _type, _type_data)
    {
        let applied_hero_assign_pks = {}, applied_hero_skill_pks = {};

        // 능력
        let capacity_hero_assi_pks = ns_hero_select.getCapacityHeroAssign(_hero_pk, _hero_data);
        if (_type === 'assign') {
            applied_hero_assign_pks = ns_hero_select.getAppliedBuildHeroAssign(capacity_hero_assi_pks, _type_data);
        } else if (_type === 'assign_wall' || _type === 'assign_troop_order') {
            applied_hero_assign_pks = ns_hero_select.getAppliedCmdHeroAssign(capacity_hero_assi_pks, ns_cs.m.cmd[`troop_${_type_data}`].m_cmd_pk);
        } else {
            applied_hero_assign_pks = ns_hero_select.getAppliedCmdHeroAssign(capacity_hero_assi_pks, ns_cs.m.cmd[_type].m_cmd_pk);
        }

        // 기술
        let capacity_hero_skill_pks = ns_hero_select.getCapacityHeroSkill(_hero_pk, _hero_data);
        if (_type === 'assign') {
            applied_hero_skill_pks = ns_hero_select.getAppliedBuilHeroSkill(capacity_hero_skill_pks, _type_data);
        } else if (['assign_wall', 'assign_troop_order'].includes(_type)) {
            applied_hero_skill_pks = ns_hero_select.getAppliedCmdHeroSkill(capacity_hero_skill_pks, ns_cs.m.cmd['troop_' + _type_data].m_cmd_pk);
        } else {
            applied_hero_skill_pks = ns_hero_select.getAppliedCmdHeroSkill(capacity_hero_skill_pks, ns_cs.m.cmd[_type].m_cmd_pk);
        }

        let ret = {};
        ret['capa'] = ns_hero_select.getAppliedAssignDesc(applied_hero_assign_pks);
        ret['skill'] = ns_hero_select.getAppliedSkillTitles(applied_hero_skill_pks);

        if (_type === 'assign_troop_order' && _type_data === 'captain') {
            let leadership = ns_cs.d.hero[_hero_pk].leadership;
            let value = 0, curr_value = 0;
            for (let [k, d] of Object.entries(ns_cs.m.troop['ATTACK_LEAD_POPULATION'])) {
                if (!ns_util.isNumeric(k)) {
                    continue;
                }
                if (parseInt(curr_value) < parseInt(k)) {
                    curr_value = parseInt(k);
                }
                if (parseInt(leadership) < parseInt(k)) {
                    value = d['value'];
                    break; // 이거 배열 find 로 찾는게 낫지 않나?
                }
            }

            if (!value) {
                value = ns_cs.m.troop['ATTACK_LEAD_POPULATION'][curr_value]['value'];
            }

            ret['capa'] = ns_i18n.t('troop_order_proper_army_amount', [value]); // <span>통솔 가능 적정 병력 : {{1}}</span>
        }

        return ret;
    }

    getName (_hero_pk, _m_hero_pk = null)
    {
        try {
            let d;
            if (! _m_hero_pk) {
                d = ns_cs.d.hero[_hero_pk];
                _m_hero_pk = d.m_hero_pk;
            }

            let m_hero = ns_cs.m.hero[_m_hero_pk];
            // let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];
            // return m_hero_base.name;
            return ns_i18n.t(`hero_name_${m_hero.m_hero_base_pk}`);
        } catch (e) {
            console.error(e);
        }
    }

    getNameWithLevel (_hero_pk, _m_hero_pk = null, _level = null, _br = false)
    {
        try {
            let d, _lev;
            if (! _m_hero_pk) {
                d = ns_cs.d.hero[_hero_pk];
                _m_hero_pk = d.m_hero_pk;
                _lev = d.level;
            }

            let m_hero = ns_cs.m.hero[_m_hero_pk];
            // let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];

            if (! _lev) {
                _lev = m_hero.level;
            }
            let _space = (! _br) ? ' ' : '<br />';

            return ns_i18n.t(`hero_name_${m_hero.m_hero_base_pk}`) + _space + ns_util.getLevelStr(_level ? _level : _lev);
        } catch (e) {
            console.error(e);
        }
    }

    deckAlarm ()
    {
        // TODO 덱이 열려있을때 덱 변화가 있는 경우 애니메이션 효과
    }

    // 강화중 시간 처리용 타임핸들러
    timerHandlerProc ()
    {
        let now = ns_timer.now(), z;
        for (let [k, v] of Object.entries(ns_hero.timed_jobs)) {
            z = v - now;
            z = (z <= 0) ? ns_i18n.t('timer_progress') : ns_util.getCostsTime(z);
            ns_hero.obj.hero_deck_list.find(`.ns_hero_${k} .cont_coststime`).text(z);
        }
    }


    bottomUiToggle (_on_select)
    {
        if (ns_engine.game_data.card_deck_busy && ! _on_select) {
            return;
        }
        ns_engine.game_data.card_deck_busy = true;

        // TODO 덱 쪽이 열린 상태이고 채팅을 보고 있으며 그 크기가 늘어난 상태면 일단 다시 줄여놓는다.
        /*if (ns_cs.cfg.carddeck_open) {
            if (qbw_btn.btns.deckTabChat.enabled && !qbw_chat.now_size_small) {
                qbw_chat.chat_size_toggle();
                if (ns_cs.cfg.carddeck_open != false || qbw_chat.now_view != 'chat') {
                    qbw_chat.chat_size_toggle();
                }
            }
        }*/

        /*if (ns_cs.cfg.carddeck_open)
        {
            move = '+=150px';
            height = '-=150px';
            $('#deck_bottom_left').find('select[name], input[name]').hide();
            $('#chat_input').find('select, input').hide();
        } else {
            move = '-=150px';
            height = '+=150px';
            $('#deck_bottom_left').find('select[name], input[name]').show();
            $('#chat_input').find('select, input').show();
        }*/

        // ns_cs.cfg.cmd_inprogress = true;

        /*if (! ns_engine.game_object.main_bottom_wrap.hasCss('toggle_down')) {
            ns_button.buttons.bottom_toggle.obj.addCss('toggle_down');
            ns_engine.game_object.main_bottom_wrap.addCss('toggle_down');
            // qbw_counter.position('T');
        } else {
            ns_button.buttons.bottom_toggle.obj.removeCss('toggle_down');
            ns_engine.game_object.main_bottom_wrap.removeCss('toggle_down');
            // qbw_counter.position('B');
        }*/

        // qbw_counter.hide();

        ns_engine.game_data.card_deck_open = !ns_engine.game_data.card_deck_open;

        ns_engine.game_data.card_deck_busy = false;
    }

    checkSameHero (_m_hero_base_pk)
    {
        return Object.entries(ns_cs.d.hero).filter(d => ns_util.isNumeric(d[0]) && ns_util.math(ns_cs.m.hero[d[1].m_hero_pk].m_hero_base_pk).eq(_m_hero_base_pk)).map(d => d[1].m_hero_pk).length > 0;
    }

    getRareType (_pk, _m_hero_base_pk = false)
    {
        try {
            return (_m_hero_base_pk) ? ns_cs.m.hero_base[_pk].rare_type : ns_cs.m.hero_base[ns_cs.m.hero[_pk].m_hero_base_pk].rare_type;
        } catch (e) {
            return '0';
        }
    }

    findAssignDescription (_hero_pk)
    {
        let _bdic = Object.values(ns_cs.d.bdic).find(o => ns_util.math(o?.['assign_hero_pk'] ?? 0).eq(_hero_pk));
        return (_bdic?.m_buil_pk) ? ns_i18n.t(`build_title_${_bdic.m_buil_pk}`) : '';
    }

    findCmdDescription (_cmd_type)
    {
        return code_set.cmd_description[_cmd_type];
    }

    dummyData (_hero_base_pk)
    {
        let m_hero = Object.values(ns_cs.m.hero).find(m => ns_util.math(m.m_hero_base_pk).eq(_hero_base_pk) && ns_util.math(m.level).eq(1));
        return {
            hero_pk: 0,
            m_hero_pk: m_hero.m_hero_pk,
            level: 1,
            leadership: m_hero.leadership,
            mil_force: m_hero.mil_force,
            intellect: m_hero.intellect,
            politics: m_hero.politics,
            charm: m_hero.charm,
            leadership_basic: m_hero.leadership,
            mil_force_basic: m_hero.mil_force,
            intellect_basic: m_hero.intellect,
            politics_basic: m_hero.politics,
            charm_basic: m_hero.charm,
            leadership_enchant: 0,
            mil_force_enchant: 0,
            intellect_enchant: 0,
            politics_enchant: 0,
            charm_enchant: 0,
            leadership_plusstat: 0,
            mil_force_plusstat: 0,
            intellect_plusstat: 0,
            politics_plusstat: 0,
            charm_plusstat: 0,
            leadership_skill: 0,
            mil_force_skill: 0,
            intellect_skill: 0,
            politics_skill: 0,
            charm_skill: 0
        }
    }
}

let ns_hero = new nsHero();