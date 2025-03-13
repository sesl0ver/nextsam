class nsCard
{
    constructor (_hero_pk, _options = {}) {
        this.obj = {};
        this.hero_pk = _hero_pk;
        this.m_hero = null
        this.m_hero_base = null;
        this.data = (_options?.data) ? _options.data : null;
        this.current_front = true; // 앞면인지
        this.first_draw = true;

        // options
        this.pickup_mode = (_options?.pickup_mode) ? _options.pickup_mode : false;
        this.pickup_cover = false;
        this.on_pickup_animation = false;

        this.flippable = true; // 뒤집을 수 있는지?
        this.is_flipping = false; // 뒤집는 중인지

        this.obj.card = new nsObject('#hero_card_skeleton').clone();
        this.obj.hero_card_wrap = new nsObject('.hero_card_wrap', this.obj.card.element);
        this.obj.card_border = new nsObject('.hero_card_border', this.obj.card.element);
        this.obj.card_effect = new nsObject('.hero_card_effect', this.obj.card.element);
        this.obj.card_cover = new nsObject('.hero_card_cover', this.obj.card.element);
        this.obj.card_front_side = new nsObject('.hero_card_front', this.obj.card.element);
        this.obj.card_back_side = new nsObject('.hero_card_back', this.obj.card.element);

        // bottom button
        this.obj.card_use_item_amount = new nsObject('.content_hero_card_use_item_amount');

        // front
        this.obj.card_adv_emblem = new nsObject('.hero_card_adv_emblem', this.obj.card.element);
        this.obj.card_level = new nsObject('.hero_card_level', this.obj.card.element);
        this.obj.card_named = new nsObject('.hero_card_named', this.obj.card.element);
        this.obj.card_name = new nsObject('.hero_card_front .hero_card_name', this.obj.card.element);
        this.obj.card_pic = new nsObject('.hero_card_pic', this.obj.card.element);
        this.obj.card_rare = new nsObject('.hero_card_rare', this.obj.card.element);
        this.obj.card_forces = new nsObject('.hero_card_forces', this.obj.card.element);
        this.obj.card_group = new nsObject('.hero_card_front .hero_card_group', this.obj.card.element);
        this.obj.card_status = new nsObject('.hero_card_front .hero_card_status', this.obj.card.element);
        this.obj.card_small_skill_slot = new nsObject('.hero_card_front .hero_card_small_skill_slot', this.obj.card.element);

        this.obj.stat_val_leadership = new nsObject('.hero_card_stat_val.leadership', this.obj.card.element);
        this.obj.stat_val_mil_force = new nsObject('.hero_card_stat_val.mil_force', this.obj.card.element);
        this.obj.stat_val_intellect = new nsObject('.hero_card_stat_val.intellect', this.obj.card.element);
        this.obj.stat_val_politics = new nsObject('.hero_card_stat_val.politics', this.obj.card.element);
        this.obj.stat_val_charm = new nsObject('.hero_card_stat_val.charm', this.obj.card.element);

        this.obj.stat_gauge_leadership = new nsObject('.hero_card_gauge.leadership > .status_gauge', this.obj.card.element);
        this.obj.stat_gauge_mil_force = new nsObject('.hero_card_gauge.mil_force > .status_gauge', this.obj.card.element);
        this.obj.stat_gauge_intellect = new nsObject('.hero_card_gauge.intellect > .status_gauge', this.obj.card.element);
        this.obj.stat_gauge_politics = new nsObject('.hero_card_gauge.politics > .status_gauge', this.obj.card.element);
        this.obj.stat_gauge_charm = new nsObject('.hero_card_gauge.charm > .status_gauge', this.obj.card.element);

        this.obj.stat_detail_leadership = new nsObject('.hero_card_gauge.leadership > .status_value > .default', this.obj.card.element);
        this.obj.stat_detail_mil_force = new nsObject('.hero_card_gauge.mil_force > .status_value > .default', this.obj.card.element);
        this.obj.stat_detail_intellect = new nsObject('.hero_card_gauge.intellect > .status_value > .default', this.obj.card.element);
        this.obj.stat_detail_politics = new nsObject('.hero_card_gauge.politics > .status_value > .default', this.obj.card.element);
        this.obj.stat_detail_charm = new nsObject('.hero_card_gauge.charm > .status_value > .default', this.obj.card.element);

        this.obj.hero_card_detail_status_wrap = new nsObject('.hero_card_detail_status_wrap', this.obj.card.element);

        // back
        this.obj.card_pic_small = new nsObject('.hero_card_pic_small', this.obj.card.element);
        this.obj.back_card_name = new nsObject('.hero_card_back .hero_card_name', this.obj.card.element);
        this.obj.back_card_group = new nsObject('.hero_card_back .hero_card_group', this.obj.card.element);

        this.obj.back_buttons = new nsObject('.hero_card_back .hero_card_back_buttons', this.obj.card.element);

        this.obj.back_mil_aptitude_infantry = new nsObject('.hero_card_back .hero_card_mil_aptitude .text_card_back_value.infantry', this.obj.card.element);
        this.obj.back_mil_aptitude_spearman = new nsObject('.hero_card_back .hero_card_mil_aptitude .text_card_back_value.spearman', this.obj.card.element);
        this.obj.back_mil_aptitude_pikeman = new nsObject('.hero_card_back .hero_card_mil_aptitude .text_card_back_value.pikeman', this.obj.card.element);
        this.obj.back_mil_aptitude_archer = new nsObject('.hero_card_back .hero_card_mil_aptitude .text_card_back_value.archer', this.obj.card.element);
        this.obj.back_mil_aptitude_horseman = new nsObject('.hero_card_back .hero_card_mil_aptitude .text_card_back_value.horseman', this.obj.card.element);
        this.obj.back_mil_aptitude_siege = new nsObject('.hero_card_back .hero_card_mil_aptitude .text_card_back_value.siege', this.obj.card.element);

        this.obj.card_officer = new nsObject('.hero_card_back .hero_card_officer_value', this.obj.card.element);
        this.obj.card_loyalty = new nsObject('.hero_card_back .hero_card_loyalty_value', this.obj.card.element);
        this.obj.card_enchant = new nsObject('.hero_card_back .hero_card_enchant_value', this.obj.card.element);
        this.obj.card_status_text = new nsObject('.hero_card_back .hero_card_status_value', this.obj.card.element);
        this.obj.hero_card_skill_list = new nsObject('.hero_card_back .hero_card_skill_list', this.obj.card.element);

        this.obj.card_description_wrap = new nsObject('.hero_card_back .hero_card_description_wrap', this.obj.card.element);
        this.obj.card_description = new nsObject('.hero_card_back .hero_card_description', this.obj.card.element);

        this.scroll_handle = new nsScroll(this.obj.card_description_wrap.element, this.obj.card_description.element);

        new Promise((resolve, reject) => {
            try {
                if (this.pickup_mode !== true) {
                    this.buttonInit();
                }
                resolve();
            } catch (e) {
                reject();
            }
        }).then(() => {
            this.draw();
        });
    }

    getCard ()
    {
        return this.obj.card.element;
    }

    isBlocking ()
    {
        return this.is_flipping;
    }

    enableFlipping ()
    {
        this.flippable = true;
    }

    disableFlipping ()
    {
        this.flippable = false;
    }

    draw ()
    {
        let d = (this.data) ? this.data : ns_cs.d.hero[this.hero_pk];
        this.m_hero = ns_cs.m.hero[d.m_hero_pk];
        this.m_hero_base = ns_cs.m.hero_base[this.m_hero.m_hero_base_pk];

        // 이벤트 설정
        if (this.first_draw) {
            if (this.pickup_mode === true) {
                this.pickup_cover = true;
                this.obj.card_cover.setEvent(ns_engine.cfg.mouse_up_event_type, (_e) => {
                    this.openPickup();
                });
            }

            this.obj.card_front_side.setEvent(ns_engine.cfg.mouse_up_event_type, (_e) => {
                // 마우스인 경우 좌클릭만 동작 하도록
                if (! ns_engine.trigger.is_touch_device && _e.button !== 0) {
                    return false;
                }
                // 매직큐브 애니메이션 처리 중 버튼 중단
                if (ns_dialog.dialogs.magic_cube?.is_start_animate === true) {
                    return false;
                }

                if (this.pickup_mode !== true) {
                    if (this.obj.hero_card_detail_status_wrap.hasCss('hide')) {
                        this.obj.hero_card_detail_status_wrap.show();
                    } else {
                        this.obj.hero_card_detail_status_wrap.hide();
                    }
                } else {
                    ns_dialog.dialogs.hero_pickup_card.nextPickup();
                }``
            });

            if (this.pickup_mode !== true) {
                this.obj.card_front_side.setEvent('transitionend', (_e) => { this.animationDispatcher(_e) });
            }
        }

        // 사전 설정
        if (this.pickup_mode !== true) {
            this.obj.card_border.removeCss().hide();
            this.obj.card_effect.hide();
            this.obj.card_cover.removeCss().hide();
        } else {
            this.obj.card_cover.show(); // 커버
            this.obj.card_border.show().addCss(['border', `rare_${this.m_hero_base.rare_type}`]);
            this.obj.card_front_side.addCss(['flipping', 'hide_face']);
        }

        this.obj.hero_card_detail_status_wrap.hide();
        this.obj.card_adv_emblem.hide();
        this.obj.card_group.hide();
        this.obj.back_card_group.hide();

        // 하단 버튼
        this.obj.card_use_item_amount.hide();
        ns_button.buttons.hero_card_tobe_guest.obj.hide();
        ns_button.buttons.hero_card_use_again.obj.hide();
        ns_button.buttons.hero_card_tobe_appoint.obj.hide();
        ns_button.buttons.hero_card_tobe_abandon.obj.hide();
        ns_button.buttons.hero_card_enchant.obj.hide();
        ns_button.buttons.hero_card_skill.obj.hide();
        ns_button.buttons.hero_card_group.setDisable();

        // 카드 레벨
        this.obj.card_level.addCss(`lv${d.level}`);

        // 레어도
        this.obj.card_rare.empty();
        let _star = new nsObject(document.createElement('span'));
        for (let i = 0; i < ns_util.math(this.m_hero_base.rare_type).integer; i++) {
            this.obj.card_rare.append(_star.clone());
        }
        _star = null;

        // 카드 이름
        let name_class = null;
        if (this.m_hero_base.type === 'K') {
            this.obj.card_named.text(ns_i18n.t('hero_card_title_lord')); // [군주]
        } else {
            this.obj.card_named.text('');
        }
        if (this.m_hero.over_type === 'Y') {
            this.obj.card_named.text(ns_i18n.t('hero_card_title_over')); // [오버]
        }
        this.obj.card_name.text(ns_i18n.t(`hero_name_${this.m_hero_base.m_hero_base_pk}`));
        this.obj.back_card_name.text(ns_i18n.t(`hero_name_${this.m_hero_base.m_hero_base_pk}`));

        // 진영
        this.obj.card_forces.addCss(this.m_hero_base.forces);

        // 그룹
        if (d.group_type) {
            this.obj.card_group.show().addCss(d.group_type);
            this.obj.back_card_group.show().addCss(d.group_type);
        } else {
            this.obj.card_group.hide();
            this.obj.back_card_group.hide();
        }

        // 스텟 입력
        for (let _stat of ['leadership', 'mil_force', 'intellect', 'politics', 'charm']) {
            this.obj[`stat_val_${_stat}`].text(d[_stat]);
            for (let _prefix of ['basic', 'plusstat', 'enchant', 'skill']) {
                let _type = `${_stat}_${_prefix}`;
                this.obj.hero_card_detail_status_wrap.find(`.${_stat}.${_prefix}`).text(d[_type] ?? 0);
            }
            this.obj.hero_card_detail_status_wrap.find(`.${_stat}.officer`).text(ns_cs.m.offi?.[d.m_offi_pk]?.[`stat_plus_${_stat}`] ?? 0);
        }

        // 병과 적성
        for (let _aptitude of ['infantry', 'spearman', 'pikeman', 'archer', 'horseman', 'siege']) {
            this.obj[`back_mil_aptitude_${_aptitude}`].addCss(this.m_hero_base[`mil_aptitude_${_aptitude}`]).text(this.m_hero_base[`mil_aptitude_${_aptitude}`]);
        }

        // 카드 사진
        this.obj.card_pic.addCss(`card_face_${this.m_hero.m_hero_base_pk}`);
        this.obj.card_pic_small.addCss(`card_face_${this.m_hero.m_hero_base_pk}`);

        // officer
        this.obj.card_officer.text('-');
        if (d.m_offi_pk != null && typeof ns_cs.m.offi[d.m_offi_pk] == 'object') {
            this.obj.card_officer.text(ns_cs.m.offi[d.m_offi_pk].title);
        }
        this.obj.card_loyalty.text(d.loyalty ?? 70);
        this.obj.card_enchant.text(d.enchant);

        // 영웅 정보
        this.obj.card_description.html(this.m_hero_base.description);

        // 상태 메세지 및 하단 버튼
        this.obj.card_status.hide();
        if (d.status === 'G') {
            this.obj.card_status_text.text(ns_i18n.t('after_appoint'));

            ns_button.buttons.hero_card_tobe_appoint.obj.show();
            ns_button.buttons.hero_card_tobe_abandon.obj.show();

            if (ns_hero.checkSameHero(ns_cs.m.hero[d.m_hero_pk].m_hero_base_pk)) {
                ns_button.buttons.hero_card_tobe_appoint.setDisable();
            } else {
                ns_button.buttons.hero_card_tobe_appoint.setEnable();
            }
        } else if (d.status === 'A') {
            this.obj.card_status.show();
            if (d.status_cmd === 'C') {
                let _status_cmd = `${code_set.hero_status_cmd[d.status_cmd]}<small>(${code_set.hero_cmd_type[d.cmd_type]})</small>`;
                this.obj.card_status_text.html(_status_cmd);
            } else {
                this.obj.card_status_text.text(code_set.hero_status_cmd[d.status_cmd]);
            }

            ns_button.buttons.hero_card_group.setEnable();
            if (d.yn_lord !== 'Y') {
                ns_button.buttons['hero_card_prize']?.setEnable();
            }

            if (d.status_cmd === 'I') {
                ns_button.buttons.hero_card_enchant.obj.show();
                ns_button.buttons.hero_card_skill.obj.show();
                ns_button.buttons.hero_card_enchant.setEnable();
                ns_button.buttons.hero_card_skill.setEnable();

                ns_button.buttons['hero_card_change_officer']?.setEnable();
                if (d.yn_lord === 'N') {
                    ns_button.buttons['hero_card_dismiss']?.setEnable();
                }
            }
        } else {
            this.obj.card_status.show();
            this.obj.card_status_text.text(ns_i18n.t('before_recruitment'));
            if (ns_dialog.dialogs.hero_card.hide_button !== true) {
                ns_button.buttons.hero_card_tobe_guest.obj.show();
            }
        }

        this.obj.card_status.addCss(d.status_cmd);

        // 오버랭크 무능화 표기를 위해 추가
        if (ns_cs.m.hero[d.m_hero_pk].over_type === 'Y') {
            if(['A', 'G'].includes(d.status)) {
                // 오버랭크 남은 시간
                let now = ns_timer.now();
                if(! d.overrank_end_dt || d.overrank_end_dt < now) {
                    // this.s.cont_status_val.text(system_text.overrank_end);
                } else {
                    // let overrank_time = d.overrank_end_dt - now;
                    // overrank_time = (overrank_time <= 0) ? system_text.overrank_end : ns_util.getCostsTime(overrank_time);
                    // this.s.cont_status_val.text(overrank_time);
                }
            }
            // this.s.cont_card_overrank_desc.show();
        }

        // 스킬
        // 슬롯 초기화
        for(let i = 1; i < 7; i++) {
            // dlg.s['cont_slot_' + i].removeClass();
        }

        // 오픈된 슬롯 개수
        if (! d?.skill_exp) {
            d.skill_exp = 0;
        }

        let m_skill_exp = Object.values(ns_cs.m.hero_skil_exp);
        let max_level = m_skill_exp.length;
        let m = m_skill_exp.filter(m => ns_util.math(d.skill_exp).gte(m.exp));
        let open_slot_cnt = m.length;
        let next_level = (open_slot_cnt + 1 > max_level) ? max_level : open_slot_cnt + 1;
        let equip_slot_cnt = 0;

        // 장착 스킬 확인
        let hero_skill_list = [];
        for(let i = 1; i <= 6; i++) {
            hero_skill_list.push(d[`m_hero_skil_pk${i}`]);
        }

        for (let i in hero_skill_list) {
            let skill_pk = hero_skill_list[i], slot_num = ns_util.math(i).plus(1).number;
            let slot = this.obj.hero_card_skill_list.find(`.hero_card_skill_slot:nth-child(${slot_num})`);
            slot.removeCss().addCss('hero_card_skill_slot');
            if (! skill_pk) { // 장착 스킬이 없는 경우
                if (ns_util.math(slot_num).eq(next_level)) { // 오픈되지 않은 슬롯의 경우
                    slot.addCss(`lock${this.checkSkillSlot(next_level, d.skill_exp, open_slot_cnt)}`);
                } else if (ns_util.math(slot_num).gt(open_slot_cnt)) {
                    slot.addCss('lock1');
                }
                this.obj.card_small_skill_slot.find(`.hero_card_slot_${slot_num}`).removeCss('on');
            } else { // 장착 스킬이 있는 경우
                let m_skill = ns_cs.m.hero_skil[skill_pk];
                slot.addCss(`hero_skill_${skill_pk.substring(0, 4)}`);
                slot.find('div').addCss('hero_skill_rare' + m_skill.rare);
                equip_slot_cnt++;
                this.obj.card_small_skill_slot.find(`.hero_card_slot_${slot_num}`).addCss('on');
            }
        }

        this.first_draw = false;
    }

    erase ()
    {
        this.scroll_handle = null;
        this.buttonDestroy();
    }

    update (_data)
    {
        this.data = _data;
        this.draw();
    }

    getData ()
    {
        return this.data;
    }

    flipping (_play_sound = true)
    {
        if (! this.flippable) {
            return;
        }
        this.is_flipping = true;
        if (_play_sound) {
            ns_sound.play('card_turn');
        }
        if (this.current_front === true) {
            // 앞면인 경우
            this.obj.card_front_side.addCss('flipping');
            this.obj.card_back_side.removeCss('flipping');
        } else {
            // 뒷면인 경우
            this.obj.card_front_side.removeCss('flipping');
            this.obj.card_back_side.addCss('flipping');
        }
    }

    animationDispatcher (_e)
    {
        if (_e.type !== 'transitionend') {
            return;
        }
        this.current_front = this.current_front !== true;
        if (this.is_flipping === true) {
            this.is_flipping = false;
            // 카드 뒤집기 후 처리
        }
    }

    buttonInit ()
    {
        for (let _name of ['prize', 'change_officer', 'dismiss', 'skill_manage']) {
            new Promise((resolve) => {
                let _button = document.createElement('span');
                _button.setAttribute('id', `ns_button_hero_card_${_name}`);
                if (_name === 'skill_manage') {
                    this.obj.hero_card_skill_list.append(_button);
                } else {
                    if (_name === 'prize') {
                        _button.innerText = ns_i18n.t('prize');
                    } else if (_name === 'change_officer') {
                        _button.innerText = ns_i18n.t('officer');
                    } else if (_name === 'dismiss') {
                        _button.innerText = ns_i18n.t('dismiss');
                    }
                    this.obj.back_buttons.append(_button);
                }
                resolve();
            }).then(() => {
                let button_css = (_name !== 'skill_manage') ? 'button_card_action' : 'button_full';
                ns_button.buttons[`hero_card_${_name}`] = new nsButtonSet(`hero_card_${_name}`, button_css, 'hero_card');
                ns_button.buttons[`hero_card_${_name}`].mouseUp = (_e) =>
                {
                    this.backButtonDispatcher(_name);
                }
                if (_name !== 'skill_manage') {
                    ns_button.buttons[`hero_card_${_name}`].setDisable(); // 기본적으로는 비활성화
                }
            })
        }
    }

    checkSkillSlot (_next_level, _skill_exp, _open_slot_cnt)
    {
        let need_exp = ns_util.math(ns_cs.m.hero_skil_exp[_next_level].exp).minus(ns_cs.m.hero_skil_exp[_open_slot_cnt].exp).number;
        let progress_rate = ns_util.math(need_exp).div(5).integer;
        let progress_exp = ns_util.math(_skill_exp).minus(ns_cs.m.hero_skil_exp[_open_slot_cnt].exp).number;
        let type = Math.ceil(progress_exp / progress_rate);
        return type > 0 ? type : 1;
    }

    backButtonDispatcher (_name)
    {
        let dialog = ns_dialog.dialogs.hero_card;
        let d = dialog.getHeroData();
        if (!d) {
            dialog.close();
        }
        let post_data = {};
        post_data['hero_pk'] = d.hero_pk;

        if (_name === 'prize') {
            if (d.yn_lord === 'Y') {
                ns_dialog.setDataOpen('message', ns_i18n.t('msg_hero_prize_lord_error')); // 군주는 포상할 수 없습니다.
                return;
            }
            ns_dialog.setDataOpen('card_prize', d);
        } else if (_name === 'change_officer') {
            const callbackFunction = function (_m_offi_pk, _chan_hero_pk)
            {
                post_data['m_offi_pk'] = _m_offi_pk;
                if (_chan_hero_pk) {
                    post_data['chan_hero_pk'] = _chan_hero_pk;
                }
                ns_xhr.post('/api/heroManage/changeOfficer', post_data, dialog.forRedraw, { useProgress: true });
            }
            ns_dialog.setDataOpen('hero_officer', { m_offi_pk: d.m_offi_pk, callbackFunc: callbackFunction });
        } else if (_name === 'dismiss') {
            // 해임한 영웅은 영웅관리 메뉴에서<br /><br />해임 24시간 후 부터 재등용 할 수 있습니다.<br /><br />해임 하시겠습니까? (충성도 10감소)
            ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_hero_dismiss_confirm'), okFunc: () =>
            {
                ns_xhr.post('/api/hero/dismiss', post_data, dialog.forRedraw, { useProgress: true });
            }});
        } else if (_name === 'skill_manage') {
            ns_dialog.setDataOpen('hero_skill_manage', d);
        }
    }

    buttonDestroy ()
    {
        for (let _name of ['prize', 'change_officer', 'dismiss']) {
            ns_button.buttons[`hero_card_${_name}`].destroy();
        }
    }

    // pickup 관련
    openPickup ()
    {
        if (this.checkPickupAnimation() === true) {
            return;
        }
        this.on_pickup_animation = true;
        this.obj.card_cover.removeEvent(ns_engine.cfg.mouse_up_event_type);
        this.pickup_cover = false;
        setTimeout(() => {
            this.obj.card_cover.addCss('flipping').show();
            this.obj.card_front_side.removeCss('flipping');
        }, 125);

        this.obj.card_cover.setEvent('transitionend', () => {
            this.obj.card_cover.removeEvent('transitionend');
            this.obj.card_effect.addCss('neon');
            ns_sound.play('card_open_end');
            this.obj.card_cover.removeCss('flipping');
            this.obj.card_cover.hide();
            this.obj.card_effect.setEvent('animationend', () => {
                this.obj.card_effect.removeEvent('animationend');
                this.obj.card_effect.removeCss('neon');
                this.obj.card_front_side.removeCss('hide_face');
                this.obj.card_effect.addCss('shine');
                this.obj.card_effect.setEvent('animationend', () => {
                    this.obj.card_effect.removeEvent('animationend');
                    this.obj.card_effect.removeCss('shine');
                    this.on_pickup_animation = false;
                });
            });
        });
    }

    checkPickupCover ()
    {
        return this.pickup_cover;
    }

    checkPickupAnimation ()
    {
        return this.on_pickup_animation
    }
}