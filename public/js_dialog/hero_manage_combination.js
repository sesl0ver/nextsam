ns_dialog.dialogs.hero_manage_combination = new nsDialogSet('hero_manage_combination', 'dialog_full', 'size-full', { do_content_scroll: false });
ns_dialog.dialogs.hero_manage_combination.current_tab = null;
ns_dialog.dialogs.hero_manage_combination.sorted = [];
ns_dialog.dialogs.hero_manage_combination.material_list = new Set();
ns_dialog.dialogs.hero_manage_combination.special_main_hero = new Set();
ns_dialog.dialogs.hero_manage_combination.special_m_main_hero = null;
ns_dialog.dialogs.hero_manage_combination.need_item = 0;
ns_dialog.dialogs.hero_manage_combination.need_gold = 0;

ns_dialog.dialogs.hero_manage_combination.cacheContents = function()
{
    this.cont_obj.tab_common_wrap = new nsObject('.tab_common_wrap', this.obj);
    this.cont_obj.tab_special_wrap = new nsObject('.tab_special_wrap', this.obj);
    this.cont_obj.tab_skill_wrap = new nsObject('.tab_skill_wrap', this.obj);

    this.cont_obj.combination_list_scroll = new nsObject('.combination_list_scroll', this.obj);
    this.cont_obj.combination_list_wrap = new nsObject('.combination_list_wrap', this.obj);

    this.cont_obj.text_result = new nsObject('.text_result', this.obj);

    this.cont_obj.special_main_wrap = new nsObject('.special_main_wrap', this.obj);

    this.cont_obj.unselect_main_hero_wrap = new nsObject('.unselect_main_hero_wrap', this.obj);
    this.cont_obj.selected_main_hero_wrap = new nsObject('.selected_main_hero_wrap', this.obj);

    this.cont_obj.select_material_count = new nsObject('.select_material_count', this.obj);
    this.cont_obj.select_material_total_acquire_exp = new nsObject('.select_material_total_acquire_exp', this.obj);

    this.cont_obj.skill_list_skeleton = new nsObject('#skill_list_skeleton');
    this.cont_obj.hero_manage_item_skeleton = new nsObject('#hero_manage_item_skeleton');

    // 스크롤 설정
    this.scroll_handle = new nsScroll(this.cont_obj.combination_list_scroll.element, this.cont_obj.combination_list_wrap.element);
}

ns_dialog.dialogs.hero_manage_combination.draw = function()
{
    ns_button.toggleGroupSingle(ns_button.buttons.hero_manage_combination_tab_special);
    this.drawTab();
}

ns_dialog.dialogs.hero_manage_combination.erase = function()
{
    this.data = null;
    ns_dialog.dialogs.hero_card.card_list = [];
}

ns_dialog.dialogs.hero_manage_combination.drawTab = function()
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    let tab = ns_button.toggleGroupValue('hero_manage_combination_tab')[0].split('_').pop();

    dialog.cont_obj.tab_common_wrap.hide();
    dialog.cont_obj.tab_special_wrap.hide();
    dialog.cont_obj.tab_skill_wrap.hide();

    dialog.cont_obj.unselect_main_hero_wrap.hide();
    dialog.cont_obj.selected_main_hero_wrap.hide();

    ns_button.buttons.hero_manage_combination_do_combination.setDisable();
    ns_button.buttons.hero_manage_combination_clear.setDisable();

    dialog.cont_obj.combination_list_wrap.removeCss('ns_panel_grid_4');
    dialog.cont_obj.combination_list_wrap.removeCss('ns_panel_grid_2');
    if (tab === 'common') {
        dialog.cont_obj.combination_list_wrap.addCss('ns_panel_grid_4');
        dialog.materialClear();
        dialog.drawHeroList();
    } else if (tab === 'special') {
        dialog.cont_obj.combination_list_wrap.addCss('ns_panel_grid_4');
        dialog.materialClear();
        dialog.drawHeroList();
        dialog.reloadMainHero();
    } else if (tab === 'skill') {
        dialog.cont_obj.combination_list_wrap.addCss('ns_panel_grid_2');
        dialog.materialClear();
        dialog.drawSkillList();
    }
    dialog.cont_obj[`tab_${tab}_wrap`].show();
    dialog.current_tab = tab;
}

ns_dialog.dialogs.hero_manage_combination.drawHeroList =  function()
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    dialog.cont_obj.combination_list_wrap.empty();

    let post_data = {};
    post_data['type'] = 'list_all';
    post_data['page_num'] = 1;
    post_data['order_by'] = 'rare';
    post_data['order_type'] = 'desc';
    ns_xhr.post('/api/heroManage/list', post_data, (_data, _status) => {
        if (!ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.sorted = [];
        for (let d of Object.values(_data.hero_list)) {
            dialog.sorted.push(d);
        }

        // 선택 가능한 영웅들을 우선 배치.
        dialog.cont_obj.combination_list_wrap.empty();
        dialog.scroll_handle.initScroll();
        dialog.drawMaterialList();
    });
}

ns_dialog.dialogs.hero_manage_combination.drawMaterialList = function ()
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    ns_dialog.dialogs.hero_card.card_list = [];
    dialog.buttonClear();
    dialog.sortHeroList();
    for (let d of dialog.sorted) {
        let _exp = 0, x = 1.0;
        if (dialog.special_main_hero.size > 0) {
            let main_hero = ns_cs.m.hero[dialog.special_m_main_hero.m_hero_pk];
            let main_hero_base = ns_cs.m.hero_base[main_hero.m_hero_base_pk];
            let mate_hero = ns_cs.m.hero[d.m_hero_pk];
            let mate_hero_base = ns_cs.m.hero_base[mate_hero.m_hero_base_pk];
            let material_exp = ns_cs.m.hero_exp[mate_hero_base.rare_type][d.level];
            if (main_hero.m_hero_base_pk === mate_hero.m_hero_base_pk) {
                x = 3.0;
            } else {
                x = dialog.checkRelationship(main_hero_base.forces, mate_hero_base.forces);
            }
            _exp = Math.floor(ns_util.math(material_exp.acquire_exp).mul(x).number);
        }

        let skeleton = dialog.cont_obj.hero_manage_item_skeleton.clone();

        let card_wrap = new nsObject(document.createElement('div'));
        card_wrap.setAttribute('id', `ns_button_combination_select_material_${d.hero_pk}`);

        let is_select_blocked = false;
        if (dialog.current_tab === 'common') {
            if (d.yn_lord === 'Y') {
                continue;
            }
            if (d.status !== 'G' || d.status_cmd !== 'I') {
                continue;
                // card_wrap = dialog.addCardList(card_wrap, d);
                // is_select_blocked = true;
            }
        } else if (dialog.current_tab === 'special') {
            if (dialog.hasMainHero(d)) {
                continue;
            } else if (dialog.hasMaterial(d)) {
                card_wrap.addCss('hero_card_selected');
            } else {
                if (dialog.special_main_hero.size > 0) {
                    if (d.yn_lord === 'Y' || ns_util.math(d.level).gt(20)) {
                        continue;
                    }
                    if (d.status !== 'G' || d.status_cmd !== 'I') {
                        continue;
                        // card_wrap = dialog.addCardList(card_wrap, d);
                        // is_select_blocked = true;
                    }
                } else {
                    if (ns_util.math(d.level).gte(20)) {
                        continue;
                    }
                    if (!['G', 'A'].includes(d.status) || d.status_cmd !== 'I') {
                        continue;
                        // card_wrap = dialog.addCardList(card_wrap, d);
                        // is_select_blocked = true;
                    }
                }
            }
        }


        let card = ns_hero.cardDraw(d.hero_pk, 'N', false, d, false, false, true);
        skeleton.find('.card_slot').append(card);
        if (ns_util.math(_exp).gt(0)) {
            skeleton.find('.hero_state').removeCss('text_light_green');
            skeleton.find('.hero_state').removeCss('text_yellow');
            if (ns_util.math(x).gt(1)) {
                skeleton.find('.hero_state').addCss('text_light_green');
            } else if (ns_util.math(x).lt(1)) {
                skeleton.find('.hero_state').addCss('text_yellow');
            }
            skeleton.find('.hero_state').text('+' + ns_util.numberFormat(_exp));
        } else {
            skeleton.find('.hero_state').text((d.status === 'G') ? ns_i18n.t('idle_condition') : ns_i18n.t('appoint_condition')); // 대기 상태 : 등용 상태
        }
        card_wrap.append(skeleton);
        dialog.cont_obj.combination_list_wrap.append(card_wrap);

        let button_id = `combination_select_material_${d.hero_pk}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, 'material_wrap', 'hero_manage_combination');
        ns_button.buttons[button_id].mouseUp = function ()
        {
            if (is_select_blocked) {
                ns_dialog.setDataOpen('hero_card', d);
            } else {
                dialog.selectMaterial(d);
            }
        }
        dialog.buttons.push(ns_button.buttons[button_id]);
    }
}

ns_dialog.dialogs.hero_manage_combination.addCardList = function (card_wrap, d)
{
    card_wrap.addCss('material_wrap');
    card_wrap.addCss('card_wrap_filter');
    ns_dialog.dialogs.hero_card.card_list.push(d);
    return card_wrap;
}

ns_dialog.dialogs.hero_manage_combination.sortHeroList =  function()
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    if (dialog.sorted.length < 1) {
        return;
    }
    if (dialog.current_tab === 'common') {
        // 일반 조합인 경우 하위 레어도 우선.
        dialog.sorted.sort((a, b) => {
            let _a = (a.status === 'G' && a.status_cmd === 'I') ? 1 : -1;
            let _b = (b.status === 'G' && b.status_cmd === 'I') ? 1 : -1;
            if (_a === _b) {
                return (ns_util.math(a.rare_type).eq(b.rare_type)) ? 0 : (ns_util.math(a.rare_type).gt(b.rare_type)) ? 1 : -1;
            } else {
                return (_a < _b) ? 1 : -1;
            }
        });
    } else if (dialog.current_tab === 'special') {
        // 특수 조합인 경우 상위 레벨 우선.
        if (dialog.special_main_hero.size < 1) { // 메인 영웅 선택 전이면
            dialog.sorted.sort((a, b) => {
                let _a = (['G', 'A'].includes(a.status) && a.status_cmd === 'I') ? 1 : -1;
                let _b = (['G', 'A'].includes(b.status) && b.status_cmd === 'I') ? 1 : -1;
                if (_a === _b) {
                    if (ns_util.math(a.level).eq(b.level)) {
                        if (ns_util.math(a.rare_type).eq(b.rare_type)) {
                            return 0
                        } else {
                            return (ns_util.math(a.rare_type).lt(b.rare_type)) ? 1 : -1; // 고레어 우선
                        }
                    } else {
                        return (ns_util.math(a.level).lt(b.level)) ? 1 : -1; // 고레벨 우선
                    }
                    /*if (ns_util.math(a.rare_type).eq(b.rare_type)) {
                        if (ns_util.math(a.level).eq(b.level)) {
                            return 0;
                        } else {
                            return (ns_util.math(a.level).lt(b.level)) ? 1 : -1; // 고레벨 우선
                        }
                    } else {
                        return (ns_util.math(a.rare_type).lt(b.rare_type)) ? 1 : -1; // 고레어 우선
                    }*/
                } else {
                    return (_a < _b) ? 1 : -1;
                }
            });
        } else { // 메인 영웅이 선택되어 있다면
            dialog.sorted.sort((a, b) => {
                let _a = (a.status === 'G' && a.status_cmd === 'I') ? 1 : -1;
                let _b = (b.status === 'G' && b.status_cmd === 'I') ? 1 : -1;
                if (_a === _b) {
                    if (ns_util.math(a.rare_type).eq(b.rare_type)) {
                        if (ns_util.math(a.level).eq(b.level)) {
                            return 0;
                        } else {
                            return (ns_util.math(a.level).gt(b.level)) ? 1 : -1; // 저레벨 우선
                        }
                    } else {
                        return (ns_util.math(a.rare_type).gt(b.rare_type)) ? 1 : -1; // 저레어 우선
                    }
                } else {
                    return (_a < _b) ? 1 : -1;
                }
            });
        }
    }
}

ns_dialog.dialogs.hero_manage_combination.drawSkillList =  function()
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    dialog.cont_obj.combination_list_wrap.empty();

    ns_xhr.post('/api/heroSkill/heroSkillAll', {}, (_data, _status) => {
        if (!ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.sorted = [];
        for (let d of Object.values(_data)) {
            if (typeof d === 'object') {
                Object.assign(d, ns_cs.m.hero_skil[d.m_hero_skil_pk]);
                dialog.sorted.push(d);
            }
        }
        dialog.drawSkillMaterialList();
    });
}

ns_dialog.dialogs.hero_manage_combination.drawSkillMaterialList = function ()
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    dialog.buttonClear();
    dialog.sortSkillList();
    for (let d of dialog.sorted) {
        let skeleton = this.cont_obj.skill_list_skeleton.clone();
        skeleton.setAttribute('id', `ns_button_combination_select_material_${d.m_hero_skil_pk}`);
        let m = ns_cs.m.hero_skil[d.m_hero_skil_pk];

        skeleton.find('.skill_image').addCss('hero_skill_' + d.m_hero_skil_pk.substring(0, 4));
        skeleton.find('.skill_rare_type').addCss('hero_skill_rare' + m.rare);
        skeleton.find('.skill_title').text(m.title + ' Lv.' + m.rare);
        skeleton.find('.skill_count').text(d.skill_cnt);
        skeleton.find('.skill_use_slot').text(m.use_slot_count);

        dialog.cont_obj.combination_list_wrap.append(skeleton);

        let button_id = `combination_select_material_${d.m_hero_skil_pk}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, null, 'hero_manage_combination');
        ns_button.buttons[button_id].mouseUp = function ()
        {
            dialog.selectMaterial(d);
        }
        dialog.buttons.push(ns_button.buttons[button_id]);
    }
}

ns_dialog.dialogs.hero_manage_combination.sortSkillList =  function()
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    if (dialog.sorted.length < 1) {
        return;
    }
    if (dialog.current_tab === 'skill') {
        // 일반 조합인 경우 하위 레어도 우선.
        dialog.sorted.sort((a, b) => {
            if (ns_util.math(a.skill_cnt).eq(a.skill_cnt)) {
                return (ns_util.math(a.rare).eq(b.rare)) ? 0 : (ns_util.math(a.rare).gt(b.rare)) ? 1 : -1;
            } else {
                return (ns_util.math(a.skill_cnt).lt(a.skill_cnt)) ? 1 : -1;
            }
        });
    }
}

ns_dialog.dialogs.hero_manage_combination.selectMaterial = function (d)
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    if (dialog.current_tab === 'common') {
        if (d.status !== 'G' || d.status_cmd !== 'I') {
            return; // 등용 대기 중이 아니면 재료카드 불가.
        }

        if (d.yn_lord === 'Y') {
            return; // 군주 카드는 재료 카드가 될 수 없음.
        }

        if (dialog.hasMaterial(d)) {
            ns_dialog.dialogs.hero_manage_combination.unSelectMaterial(d);
        } else {
            if (dialog.material_list.size >= 4) {
                return; // 최대 4장까지만 선택 가능.
            }
            // 차례로 밀어 넣는게 아닌 비어있는 곳을 목표로.
            for (let i of ['1', '2', '3', '4']) {
                if (ns_button.buttons[`common_material_${i}`].obj.isEmpty()) {
                    let card = ns_hero.cardDraw(d.hero_pk, 'N', false, d, false, false, true);
                    ns_button.buttons[`common_material_${i}`].obj.append(card);
                    dialog.material_list.add(d.hero_pk);
                    ns_button.buttons[`combination_select_material_${d.hero_pk}`].obj.addCss('hero_card_selected');
                    break;
                }
            }
        }
        if (dialog.material_list.size === 4) {
            ns_button.buttons.hero_manage_combination_do_combination.setEnable();
        } else {
            ns_button.buttons.hero_manage_combination_do_combination.setDisable();
        }
        dialog.checkResult();

        if (dialog.material_list.size > 0) {
            ns_button.buttons.hero_manage_combination_clear.setEnable();
        } else {
            ns_button.buttons.hero_manage_combination_clear.setDisable();
        }
    } else if (dialog.current_tab === 'special') {
        // 선택한 영웅이 메인 영웅으로 선택된 영웅이라면?
        if (dialog.hasMainHero(d)) {
            ns_dialog.dialogs.hero_manage_combination.unSelectMaterial(d);
            dialog.cont_obj.combination_list_wrap.empty();
            dialog.scroll_handle.initScroll();
            dialog.materialClear();
            dialog.drawMaterialList();
        } else if (dialog.hasMaterial(d)) {
            ns_dialog.dialogs.hero_manage_combination.unSelectMaterial(d);
        } else {
            if (dialog.special_main_hero.size < 1) { // 메인 영웅 선택
                // 최대 레벨이라면
                if (ns_util.math(d.level).gte(20)) {
                    ns_dialog.setDataOpen('message', ns_i18n.t('msg_hero_level_max')); // 이미 최대 레벨인 영웅입니다.
                    return;
                }
                let card = ns_hero.cardDraw(d.hero_pk, 'N', false, d, false, false, true);
                ns_button.buttons.special_main_hero.obj.append(card);
                dialog.special_main_hero.add(d.hero_pk);
                // ns_button.buttons[`combination_select_material_${d.hero_pk}`].obj.addCss('hero_card_selected');
                // 메인 영웅 정보창 갱신
                dialog.reloadMainHero(d);
                // 재료 리스트 다시 그리기
                dialog.cont_obj.combination_list_wrap.empty();
                dialog.scroll_handle.initScroll();
                dialog.drawMaterialList();
            } else { // 재료 영웅 선택
                if (d.yn_lord === 'Y') {
                    return; // 군주 카드는 재료 카드가 될 수 없음.
                }
                if (dialog.material_list.size >= 10) {
                    return; // 재료는 최대 10개
                }
                if (! dialog.material_list.has(d.hero_pk)) {
                    dialog.material_list.add(d.hero_pk);
                    ns_button.buttons[`combination_select_material_${d.hero_pk}`].obj.addCss('hero_card_selected');
                    dialog.drawMainHeroExp('next');
                }
            }
            if (dialog.special_main_hero.size > 0 || dialog.material_list.size > 0) {
                ns_button.buttons.hero_manage_combination_do_combination.setEnable();
                ns_button.buttons.hero_manage_combination_clear.setEnable();
            } else {
                ns_button.buttons.hero_manage_combination_do_combination.setDisable();
                ns_button.buttons.hero_manage_combination_clear.setDisable();
            }
        }
    } else if (dialog.current_tab === 'skill') {
        let m = ns_cs.m.hero_skil[d.m_hero_skil_pk];
        for (let i of ['1', '2', '3', '4', '5']) {
            if (ns_button.buttons[`skill_material_${i}`].obj.isEmpty()) {
                let skill_count = ns_button.buttons[`combination_select_material_${d.m_hero_skil_pk}`].obj.find('.skill_count');
                if (ns_util.math(skill_count.text()).gt(0)) {
                    let skill = document.createElement('div');
                    let rare_type = document.createElement('div');
                    rare_type.classList.add('skill_rare_type');
                    rare_type.classList.add('hero_skill_rare' + m.rare);
                    skill.classList.add('skill_image');
                    skill.classList.add('hero_skill_' + d.m_hero_skil_pk.substring(0, 4));
                    skill.classList.add('hero_skill_rare' + m.rare);
                    skill.dataset.skill_pk = d.m_hero_skil_pk;

                    skill.appendChild(rare_type);
                    ns_button.buttons[`skill_material_${i}`].obj.append(skill);
                    dialog.material_list.add(`${i}_${d.m_hero_skil_pk}`);
                    let next_count = ns_util.math(skill_count.text()).minus(1).number;
                    skill_count.text(next_count);
                    if (ns_util.math(next_count).eq(0)) {
                        ns_button.buttons[`combination_select_material_${d.m_hero_skil_pk}`].obj.addCss('skill_selected');
                    }
                    break;
                }
            }
        }

        if (dialog.material_list.size === 5) {
            ns_button.buttons.hero_manage_combination_do_combination.setEnable();
        } else {
            ns_button.buttons.hero_manage_combination_do_combination.setDisable();
        }
        if (dialog.material_list.size > 0) {
            ns_button.buttons.hero_manage_combination_clear.setEnable();
        } else {
            ns_button.buttons.hero_manage_combination_clear.setDisable();
        }
    }
}

ns_dialog.dialogs.hero_manage_combination.unSelectMaterial = function (d)
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    if (dialog.current_tab === 'common') {
        if (dialog.hasMaterial(d)) {
            for (let i of ['1', '2', '3', '4']) {
                // 대상 재료 카드를 가지고 있는지?
                if (ns_button.buttons[`common_material_${i}`].obj.find(`[data-hero_pk="${d.hero_pk}"]`).element !== null) {
                    ns_button.buttons[`common_material_${i}`].obj.empty();
                    break;
                }
            }
            ns_button.buttons[`combination_select_material_${d.hero_pk}`].obj.removeCss('hero_card_selected');
            dialog.material_list.delete(d.hero_pk);
        }
    } else if (dialog.current_tab === 'special') {
        if (dialog.hasMainHero(d)) {
            ns_button.buttons.special_main_hero.obj.empty();
            // ns_button.buttons[`combination_select_material_${d.hero_pk}`].obj.removeCss('hero_card_selected');
            dialog.special_main_hero.delete(d.hero_pk);
            dialog.reloadMainHero();
            dialog.initGauge();
            dialog.scroll_handle.initScroll();
        } else if (dialog.hasMaterial(d)) {
            ns_button.buttons[`combination_select_material_${d.hero_pk}`].obj.removeCss('hero_card_selected');
            dialog.material_list.delete(d.hero_pk);
            dialog.drawMainHeroExp('next');
        }
    }
}

ns_dialog.dialogs.hero_manage_combination.hasMainHero = function (hero_info)
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    return dialog.special_main_hero.has(hero_info.hero_pk);
}

ns_dialog.dialogs.hero_manage_combination.reloadMainHero = function (hero_info)
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    let special_main_wrap = dialog.cont_obj.special_main_wrap;
    let selected_main_hero_wrap = dialog.cont_obj.selected_main_hero_wrap;
    if (! hero_info) {
        dialog.cont_obj.unselect_main_hero_wrap.show();
        selected_main_hero_wrap.hide();
        special_main_wrap.find('.main_hero_level').text(0);
        dialog.need_item = 0;
        dialog.need_gold = 0;
        dialog.special_m_main_hero = null;
    } else {
        dialog.cont_obj.unselect_main_hero_wrap.hide();
        selected_main_hero_wrap.show();
        // 기본 능력치 (+스탯 제외)
        for (let _stat of ['leadership', 'mil_force', 'intellect', 'politics', 'charm']) {
            selected_main_hero_wrap.find(`.hero_${_stat}`).text(hero_info[`${_stat}_basic`]);
        }
        // 아이템 및 조합 횟수
        dialog.need_item = dialog.combinationNeedItem(hero_info['level'], hero_info['rare_type']);
        dialog.need_gold = dialog.combinationNeedGold(hero_info['level'], hero_info['rare_type']);

        // ns_util.numberFormat(dialog.need_item)
        selected_main_hero_wrap.find('.hero_item').text(0);
        selected_main_hero_wrap.find('.hero_item_have').text(ns_cs.d.item['500708']?.item_cnt ?? 0);
        selected_main_hero_wrap.find('.hero_gold').text(ns_util.numberFormat(dialog.need_gold));
        selected_main_hero_wrap.find('.hero_gold_have').text(ns_util.numberFormat(ns_cs.getTerritoryInfo('gold_curr')));
        selected_main_hero_wrap.find('.hero_special_count').text(ns_util.numberFormat(hero_info['special_combi_cnt']));

        // 메인 영웅 레벨 및 경험치 게이지
        special_main_wrap.find('.main_hero_level').removeCss('text_green').text(hero_info['level']);
        selected_main_hero_wrap.find('.hero_item').removeCss('text_green')
        dialog.drawMainHeroExp('current', hero_info);
        dialog.special_m_main_hero = ns_cs.m.hero[hero_info.m_hero_pk];
    }
}

ns_dialog.dialogs.hero_manage_combination.drawMainHeroExp = function (target, hero_info = null)
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    let selected_main_hero_wrap = dialog.cont_obj.selected_main_hero_wrap;
    let special_main_wrap = dialog.cont_obj.special_main_wrap;

    let gauge = 0;
    if (target === 'current') {
        let main_rare = hero_info?.yn_lord === 'Y' ? '0' : hero_info.rare_type;
        let hero_exp = ns_cs.m.hero_exp[main_rare][hero_info.level];
        if (ns_util.math(hero_exp.need_exp).lte(0)) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_hero_combination_max')); // 더 이상 조합 할 수 없는 영웅입니다.
            ns_button.buttons.hero_manage_combination_clear.mouseUp();
            return;
        }
        if (ns_util.math(hero_info['hero_exp']).gt(0)) {
            gauge = ns_util.math(hero_info['hero_exp']).div(hero_exp.need_exp).mul(100).integer;
        }
        for (let _stat of ['leadership', 'mil_force', 'intellect', 'politics', 'charm']) {
            selected_main_hero_wrap.find(`.bonus_${_stat}`).text('');
        }
        special_main_wrap.find('.hero_exp_text').text(`${hero_info['hero_exp']}/${hero_exp.need_exp}`);
    } else if (target === 'next') {
        if (dialog.special_main_hero.size < 1) {
            return;
        }
        let main_hero_pk = Array.from(dialog.special_main_hero)[0];
        let main_hero = dialog.sorted.filter(h => ns_util.math(h.hero_pk).eq(main_hero_pk))[0];
        let m_hero = ns_cs.m.hero[main_hero.m_hero_pk];
        let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];

        let x = 1.0, total_acquire_exp = 0;
        for (let hero_pk of Array.from(dialog.material_list)) {
            let material_hero = dialog.sorted.filter(h => ns_util.math(h.hero_pk).eq(hero_pk))[0];
            let material_m = ns_cs.m.hero[material_hero.m_hero_pk];
            let material_base = ns_cs.m.hero_base[material_m.m_hero_base_pk];
            let material_exp = ns_cs.m.hero_exp[material_hero.rare_type][material_hero.level];

            if (m_hero.m_hero_base_pk === material_m.m_hero_base_pk) {
                x = 3.0;
            } else {
                x = dialog.checkRelationship(m_hero_base.forces, material_base.forces);
            }
            let add_exp = Math.floor(ns_util.math(material_exp.acquire_exp).mul(x).number);
            gauge += add_exp;
            total_acquire_exp += add_exp;
        }
        dialog.cont_obj.select_material_total_acquire_exp.text(total_acquire_exp);
        total_acquire_exp = ns_util.math(total_acquire_exp).plus(main_hero.hero_exp).number;

        let main_rare = main_hero?.yn_lord === 'Y' ? '0' : main_hero.rare_type;
        let current_level = Number(main_hero.level);
        let level_table = Object.values(ns_cs.m.hero).filter(m => ns_util.math(m.m_hero_base_pk).eq(m_hero.m_hero_base_pk) && ns_util.math(m.level).gte(m_hero.level));
        let current_need = 0, last_exp = 0;
        for (let lv of level_table) {
            let exp_table = ns_cs.m.hero_exp[main_rare][lv.level];
            if (ns_util.math(exp_table.need_exp).gt(0)) {
                current_need = Number(exp_table.need_exp);
            }
            current_level = Number(lv.level);
            if (ns_util.math(total_acquire_exp).lt(current_need)) {
                if (ns_util.math(exp_table.need_exp).eq(0)) {
                    total_acquire_exp = ns_util.math(total_acquire_exp).plus(last_exp).number;
                }
                break;
            } else {
                if (ns_util.math(exp_table.need_exp).eq(0)) {
                    total_acquire_exp = ns_util.math(total_acquire_exp).plus(last_exp).number;
                } else {
                    total_acquire_exp = ns_util.math(total_acquire_exp).minus(exp_table.need_exp).number;
                    last_exp = Number(exp_table.need_exp);
                }
            }
        }

        let status_table = level_table.find(m => ns_util.math(m.level).eq(current_level));
        for (let _stat of ['leadership', 'mil_force', 'intellect', 'politics', 'charm']) {
            if (ns_util.math(status_table[_stat]).gt(main_hero[_stat])) {
                let bonus_stat = ns_util.math(status_table[_stat]).minus(main_hero[_stat]).number;
                selected_main_hero_wrap.find(`.bonus_${_stat}`).text(`+${bonus_stat}`);
            } else {
                selected_main_hero_wrap.find(`.bonus_${_stat}`).text('');
            }
        }

        // 필요 조합 아이템
        let need_item_count = 0;
        if (ns_util.math(current_level).gt(main_hero.level)) {
            let x = ns_util.math(current_level).minus(main_hero.level).number;
            for (let i = 1; i <= x; i++) {
                let _next = ns_util.math(main_hero.level).plus(i).minus(1).number; // 실제로는 1레벨 전 아이템 테이블것이 필요.
                let _lv = level_table.find(o => ns_util.math(o.level).eq(_next));
                need_item_count += dialog.combinationNeedItem(_lv.level, main_rare);
            }
        }

        gauge = Math.floor(ns_util.math(total_acquire_exp).div(current_need).mul(100).number);
        special_main_wrap.find('.hero_exp_text').text(`${total_acquire_exp}/${current_need}`);
        if (target === 'next' && ns_util.math(current_level).gt(main_hero.level)) {
            special_main_wrap.find('.hero_exp_bar.current').element.style.width = '0%';
            special_main_wrap.find('.main_hero_level').addCss('text_green').text(current_level);
            selected_main_hero_wrap.find('.hero_item').addCss('text_green').text(need_item_count);
        } else {
            special_main_wrap.find('.hero_exp_bar.current').element.style.width = gauge + '%';
            special_main_wrap.find('.main_hero_level').removeCss('text_green').text(current_level);
            selected_main_hero_wrap.find('.hero_item').removeCss('text_green').text(need_item_count);
        }
    }
    gauge = (gauge > 100) ? 100 : gauge;
    special_main_wrap.find(`.hero_exp_bar.${target}`).element.style.width = gauge + '%';
    dialog.cont_obj.select_material_count.text(dialog.material_list.size);
}


ns_dialog.dialogs.hero_manage_combination.hasMaterial = function (d)
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    if (dialog.current_tab === 'skill') {
        return dialog.material_list.has(d.m_hero_skil_pk);
    } else {
        return dialog.material_list.has(d.hero_pk);
    }
}

ns_dialog.dialogs.hero_manage_combination.materialClear = function ()
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    if (dialog.current_tab === 'common') {
        for (let i of ['1', '2', '3', '4']) {
            if (! ns_button.buttons[`common_material_${i}`].obj.isEmpty()) {
                ns_button.buttons[`common_material_${i}`].obj.empty();
            }
        }
        dialog.material_list = new Set(); // 선택 재료 초기화.
    } else if (dialog.current_tab === 'special') {
        ns_button.buttons.special_main_hero.obj.empty();
        dialog.special_main_hero = new Set(); // 메인 영웅 초기화.
        dialog.material_list = new Set(); // 선택 재료 초기화.

        dialog.need_item = 0;
        dialog.need_gold = 0;

        dialog.initGauge();
        dialog.cont_obj.select_material_count.text(dialog.material_list.size);
    } else if (dialog.current_tab === 'skill') {
        for (let i of ['1', '2', '3', '4', '5']) {
            if (! ns_button.buttons[`skill_material_${i}`].obj.isEmpty()) {
                ns_button.buttons[`skill_material_${i}`].obj.empty();
            }
        }
        dialog.material_list = new Set(); // 선택 재료 초기화.
    }
}

ns_dialog.dialogs.hero_manage_combination.initGauge = function ()
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    let special_main_wrap = dialog.cont_obj.special_main_wrap;
    special_main_wrap.find('.hero_exp_text').text('');
    special_main_wrap.find('.hero_exp_bar.current').element.style.width = '0%';
    special_main_wrap.find('.hero_exp_bar.next').element.style.width = '0%';
}

ns_dialog.dialogs.hero_manage_combination.checkRelationship = function(_my, _other)
{
    // 우호 : F 적대 : E 없음 : N
    let relationship = 'N';
    switch(_my) {
        case 'UB'  :
        case 'WS'  :
        case 'SK'  :
            switch(_other) {
                case 'UB' :  relationship = 'F'; break;
                case 'JJ' :  relationship = 'E'; break;
                case 'SK' :  relationship = 'F'; break;
                case 'WS' :  relationship = 'F'; break;
                case 'DT' :  relationship = 'E'; break;
                case 'PC' :  relationship = 'E'; break;
                case 'NN' :  relationship = 'N'; break;
            }
            break;
        case 'JJ'  :
            switch(_other) {
                case 'UB' :  relationship = 'E'; break;
                case 'JJ' :  relationship = 'F'; break;
                case 'SK' :  relationship = 'E'; break;
                case 'WS' :  relationship = 'E'; break;
                case 'DT' :  relationship = 'F'; break;
                case 'PC' :  relationship = 'E'; break;
                case 'NN' :  relationship = 'N'; break;
            }
            break;
        case 'DT'  :
            switch(_other) {
                case 'UB' :  relationship = 'E'; break;
                case 'JJ' :  relationship = 'F'; break;
                case 'SK' :  relationship = 'E'; break;
                case 'WS' :  relationship = 'E'; break;
                case 'DT' :  relationship = 'F'; break;
                case 'PC' :  relationship = 'F'; break;
                case 'NN' :  relationship = 'N'; break;
            }
            break;
        case 'PC'  :
            switch(_other) {
                case 'UB' :  relationship = 'E'; break;
                case 'JJ' :  relationship = 'E'; break;
                case 'SK' :  relationship = 'E'; break;
                case 'WS' :  relationship = 'E'; break;
                case 'DT' :  relationship = 'F'; break;
                case 'PC' :  relationship = 'F'; break;
                case 'NN' :  relationship = 'F'; break;
            }
            break;
        case 'NN'  :
            switch(_other) {
                case 'UB' :  relationship = 'N'; break;
                case 'JJ' :  relationship = 'N'; break;
                case 'SK' :  relationship = 'N'; break;
                case 'WS' :  relationship = 'N'; break;
                case 'DT' :  relationship = 'N'; break;
                case 'PC' :  relationship = 'F'; break;
                case 'NN' :  relationship = 'F'; break;
            }
            break;
    }
    // return (relationship === 'E') ? 0.9 : (relationship === 'F') ? 1.1 : 1.0;
    return (relationship === 'E') ? 0.9 : (relationship === 'F') ? 2.0 : 1.0;
}

ns_dialog.dialogs.hero_manage_combination.combinationNeedGold = function(_level, _rare_type)
{
    let combination_gold = 0;
    let m = false;
    if (!ns_cs.m.need_reso['combination']) {
        return combination_gold;
    }
    if (!ns_cs.m.need_reso['combination'][_rare_type]) {
        return combination_gold;
    }
    if (!ns_cs.m.need_reso['combination'][_rare_type][_level]) {
        return combination_gold;
    }
    m = ns_cs.m.need_reso['combination'][_rare_type][_level];
    if (m && ns_util.math(m['need_gold']).gt(0)) {
        combination_gold = parseInt(m['need_gold']);
    }
    return combination_gold;
}

ns_dialog.dialogs.hero_manage_combination.combinationNeedItem = function(_level, _rare_type)
{
    let combination_item = 0;
    let m = false;
    if (! ns_cs.m.need_reso['combination']) {
        return combination_item;
    }
    if (!ns_cs.m.need_reso['combination'][_rare_type]) {
        return combination_item;
    }
    if (!ns_cs.m.need_reso['combination'][_rare_type][_level]) {
        return combination_item;
    }
    m = ns_cs.m.need_reso['combination'][_rare_type][_level];
    if (m && m['need_item']) {
        let arr = m['need_item'].split(':');
        if (ns_util.math(arr[1]).gt(0)) {
            combination_item = ns_util.toInteger(arr[1]);
        }
    }
    return combination_item;
}

ns_dialog.dialogs.hero_manage_combination.checkWarning = function ()
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    let is_high = false, is_skill = false;
    for (let pk of dialog.material_list) {
        let d = dialog.sorted.filter(h => h.hero_pk === pk)[0];
        if (! is_high) {
            // 1. 레벨이 10 이상인 영웅이 재료인지?
            if (ns_util.math(d.level).gte(10)) {
                is_high = true;
            }
        }

        if (! is_skill) {
            // 2 스킬을 장착하고 있는지?
            for (let i of [1, 2, 3, 4, 5, 6]) {
                if (! is_skill && d[`m_hero_skil_pk${i}`]) {
                    is_skill = true;
                    break;
                }
            }
        }

        // 둘 다 포함하고 있으면 더 확인할 필요도 없음.
        if (is_high && is_skill) {
            break;
        }
    }
    return [is_high, is_skill];
}

// 기대 결과 표기를 위해 추가. 마스터데이터가 변동된다면 해당 함수도 수정해줘야함.
ns_dialog.dialogs.hero_manage_combination.checkResult = function ()
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    if (ns_util.math(dialog.material_list.size).lt(4)) {
        dialog.cont_obj.text_result.text('-');
        return;
    }
    let min_rare = 7, max_rare = 1;
    let total = 0, count = 0, score = 0;
    for (let pk of dialog.material_list) {
        let d = dialog.sorted.filter(h => h.hero_pk === pk)[0];
        total += Number(d.rare_type); // 레어도
        score += ns_util.math(d.rare_type).pow(2).plus(d.rare_type).mul(d.level).number; // 레벨
        count++;
    }
    let average = ns_util.math(total).div(count).toFixed(2);
    for (let [k, v] of Object.entries(ns_cs.m.hero_combination_rare[average])) {
        if (ns_util.math(v.rate).gt(0)) {
            min_rare = (ns_util.math(min_rare).gt(k)) ? Number(k) : min_rare;
            max_rare = (ns_util.math(k).gt(max_rare)) ? Number(k) : max_rare;
        }
    }

    dialog.cont_obj.text_result.text(ns_i18n.t('hero_combination_common_description', [min_rare, max_rare]));
}



/* ************************************************** */

ns_button.buttons.hero_manage_combination_close = new nsButtonSet('hero_manage_combination_close', 'button_back', 'hero_manage_combination', {base_class:ns_button.buttons.common_close});
ns_button.buttons.hero_manage_combination_sub_close = new nsButtonSet('hero_manage_combination_sub_close', 'button_full', 'hero_manage_combination', {base_class:ns_button.buttons.common_sub_close});
ns_button.buttons.hero_manage_combination_close_all = new nsButtonSet('hero_manage_combination_close_all', 'button_close_all', 'hero_manage_combination', {base_class:ns_button.buttons.common_close_all});

// ns_button.buttons.game_help_HeroCombi = new nsButtonSet('game_help_HeroCombi', 'button_dlg_help', 'hero_manage_combination', {base_class:ns_button.buttons.buil_help});

ns_button.buttons.hero_manage_combination_tab_special = new nsButtonSet('hero_manage_combination_tab_special', 'button_tab', 'hero_manage_combination', { toggle_group: 'hero_manage_combination_tab' });
ns_button.buttons.hero_manage_combination_tab_special.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    ns_button.toggleGroupSingle(this);
    dialog.drawTab();
}

ns_button.buttons.hero_manage_combination_tab_skill = new nsButtonSet('hero_manage_combination_tab_skill', 'button_tab', 'hero_manage_combination', { base_class:ns_button.buttons.hero_manage_combination_tab_special, toggle_group: 'hero_manage_combination_tab' });
ns_button.buttons.hero_manage_combination_tab_common = new nsButtonSet('hero_manage_combination_tab_common', 'button_tab', 'hero_manage_combination', { base_class:ns_button.buttons.hero_manage_combination_tab_special, toggle_group: 'hero_manage_combination_tab'});

// 일반 조합 버튼
ns_button.buttons.common_material_1 = new nsButtonSet('common_material_1', 'content_hero_no_select', 'hero_manage_combination');
ns_button.buttons.common_material_1.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    if (! this.obj.isEmpty()) {
        let hero_pk = this.obj.find('.hero_card_small').element.dataset.hero_pk;
        ns_dialog.dialogs.hero_manage_combination.selectMaterial(dialog.sorted.filter(h => h.hero_pk === hero_pk)[0]);
    }
}

ns_button.buttons.common_material_2 = new nsButtonSet('common_material_2', 'content_hero_no_select', 'hero_manage_combination', {base_class:ns_button.buttons.common_material_1});
ns_button.buttons.common_material_3 = new nsButtonSet('common_material_3', 'content_hero_no_select', 'hero_manage_combination', {base_class:ns_button.buttons.common_material_1});
ns_button.buttons.common_material_4 = new nsButtonSet('common_material_4', 'content_hero_no_select', 'hero_manage_combination', {base_class:ns_button.buttons.common_material_1});

// 특수 조합 버튼
ns_button.buttons.special_main_hero = new nsButtonSet('special_main_hero', 'content_hero_no_select', 'hero_manage_combination', {base_class:ns_button.buttons.common_material_1});
ns_button.buttons.special_material_1 = new nsButtonSet('special_material_1', 'content_hero_no_select', 'hero_manage_combination', {base_class:ns_button.buttons.common_material_1});
ns_button.buttons.special_material_2 = new nsButtonSet('special_material_2', 'content_hero_no_select', 'hero_manage_combination', {base_class:ns_button.buttons.common_material_1});
ns_button.buttons.special_material_3 = new nsButtonSet('special_material_3', 'content_hero_no_select', 'hero_manage_combination', {base_class:ns_button.buttons.common_material_1});
ns_button.buttons.special_material_4 = new nsButtonSet('special_material_4', 'content_hero_no_select', 'hero_manage_combination', {base_class:ns_button.buttons.common_material_1});
ns_button.buttons.special_material_5 = new nsButtonSet('special_material_5', 'content_hero_no_select', 'hero_manage_combination', {base_class:ns_button.buttons.common_material_1});
ns_button.buttons.special_material_6 = new nsButtonSet('special_material_6', 'content_hero_no_select', 'hero_manage_combination', {base_class:ns_button.buttons.common_material_1});
ns_button.buttons.special_material_7 = new nsButtonSet('special_material_7', 'content_hero_no_select', 'hero_manage_combination', {base_class:ns_button.buttons.common_material_1});
ns_button.buttons.special_material_8 = new nsButtonSet('special_material_8', 'content_hero_no_select', 'hero_manage_combination', {base_class:ns_button.buttons.common_material_1});
ns_button.buttons.special_material_9 = new nsButtonSet('special_material_9', 'content_hero_no_select', 'hero_manage_combination', {base_class:ns_button.buttons.common_material_1});
ns_button.buttons.special_material_10 = new nsButtonSet('special_material_10', 'content_hero_no_select', 'hero_manage_combination', {base_class:ns_button.buttons.common_material_1});

// 기술 조합 관련 버튼
ns_button.buttons.unselect_skill = new nsButtonSet('unselect_skill', 'button_normal', 'hero_manage_combination');
ns_button.buttons.unselect_skill.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    let slot = this.tag_id.split('_').pop();
    if (! this.obj.isEmpty()) {
        let skill_pk = this.obj.find('.skill_image').element.dataset.skill_pk;
        dialog.material_list.delete(`${slot}_${skill_pk}`);
        this.obj.empty();

        let skill_count = ns_button.buttons[`combination_select_material_${skill_pk}`].obj.find('.skill_count');
        let next_count = ns_util.math(skill_count.text()).plus(1).number;
        skill_count.text(next_count);
        ns_button.buttons[`combination_select_material_${skill_pk}`].obj.removeCss('skill_selected');
    }
}

ns_button.buttons.skill_material_1 = new nsButtonSet('skill_material_1', 'hero_card_skill_slot', 'hero_manage_combination', {base_class:ns_button.buttons.unselect_skill});
ns_button.buttons.skill_material_2 = new nsButtonSet('skill_material_2', 'hero_card_skill_slot', 'hero_manage_combination', {base_class:ns_button.buttons.unselect_skill});
ns_button.buttons.skill_material_3 = new nsButtonSet('skill_material_3', 'hero_card_skill_slot', 'hero_manage_combination', {base_class:ns_button.buttons.unselect_skill});
ns_button.buttons.skill_material_4 = new nsButtonSet('skill_material_4', 'hero_card_skill_slot', 'hero_manage_combination', {base_class:ns_button.buttons.unselect_skill});
ns_button.buttons.skill_material_5 = new nsButtonSet('skill_material_5', 'hero_card_skill_slot', 'hero_manage_combination', {base_class:ns_button.buttons.unselect_skill});

// 공통 버튼 (조합, 초기화)
ns_button.buttons.hero_manage_combination_do_combination = new nsButtonSet('hero_manage_combination_do_combination', 'button_special', 'hero_manage_combination');
ns_button.buttons.hero_manage_combination_do_combination.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    let tab = dialog.current_tab;

    let combination_message = ns_i18n.t('msg_proceed_combination_confirm') + '<br />'; // 조합을 진행 하시겠습니까?

    if (tab === 'common') {
        if (dialog.material_list.size < 4) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_plz_combination_material_choose')); // 조합을 하기위해서는 재료 카드<br />4장이 선택되어야 합니다.
            return;
        }

        let [is_high, is_skill] = dialog.checkWarning();
        if (is_high) {
            combination_message += `<br /><span class="text_red">${ns_i18n.t('msg_plz_combination_material_level')}</span><br />`;
        }
        if (is_skill) {
            combination_message += `<br /><span class="text_red">${ns_i18n.t('msg_combination_material_skill_warning')}</span><br />`;
        }
        ns_dialog.setDataOpen('confirm', { text: combination_message,
            okFunc: () =>
            {
                let post_data = {};
                post_data['type'] = 'common';
                post_data['selected_hero'] = Array.from(dialog.material_list).join(':');

                ns_xhr.post('/api/heroManage/combination', post_data, function(_data, _status)
                {
                    if(! ns_xhr.returnCheck(_data)) {
                        return;
                    }
                    _data = _data['ns_xhr_return']['add_data'];

                    // 영웅 획득
                    ns_dialog.setDataOpen('hero_card', _data.hero_info);
                    ns_sound.play('hero_combination');

                    // 다시 그리기
                    ns_dialog.dialogs.hero_manage_combination.materialClear();
                    dialog.drawTab();
                });
            }
        }, { useProgress: true });
    } else if (tab === 'special') {
        if (dialog.special_main_hero.size < 1) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_need_combination_main_hero')); // 조합을 하기위해서는 메인 영웅 카드가 선택되어야 합니다.
            return;
        }
        if (dialog.material_list.size < 1) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_need_combination_material_hero')); // 조합을 하기위해서는 재료 영웅 카드가 선택되어야 합니다.
            return;
        }

        // 황금 체크
        if (ns_util.math(ns_cs.getTerritoryInfo('gold_curr')).lt(dialog.need_gold)) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_resource_gold_lack')); // 황금이 부족합니다.
            return;
        }

        let item_check = true;

        // 특수 조합석 체크
        if (dialog.need_item > 0 && ns_cs.d && ns_cs.d.item) {
            if (!ns_cs.d.item[500708]) {
                item_check = false;
            } else {
                if (ns_cs.d.item[500708].item_cnt < dialog.need_item) {
                    item_check = false;
                }
            }
            if (! item_check) {
                ns_dialog.setDataOpen('message', ns_i18n.t('msg_need_item', [ns_i18n.t(`item_title_500708`)])); // 특수 조합석이 부족합니다.
                return;
            }
        }

        let [is_high, is_skill] = dialog.checkWarning();
        if (is_high) {
            combination_message += `<br /><span class="text_red">${ns_i18n.t('msg_combination_material_level_warning')}</span><br />`;
        }
        if (is_skill) {
            combination_message += `<br /><span class="text_red">${ns_i18n.t('msg_combination_material_skill_warning')}</span><br />`;
        }
        ns_dialog.setDataOpen('confirm', { text: combination_message,
            okFunc: () =>
            {
                let main_hero_pk = Array.from(dialog.special_main_hero)[0];
                let post_data = {};
                post_data['type'] = 'special';
                post_data['selected_star_hero'] = main_hero_pk;
                post_data['selected_hero'] = Array.from(dialog.material_list).join(':');

                ns_xhr.post('/api/heroManage/combination', post_data, function(_data, _status)
                {
                    if(! ns_xhr.returnCheck(_data)) {
                        return;
                    }
                    _data = _data['ns_xhr_return']['add_data'];
                    const result = _data['combi_result'];
                    let result_message = ns_i18n.t('msg_combination_complete_exp', [ns_util.numberFormat(result['exp_info']['material_exp'])]); // 경험치를 {{1}}만큼 증가했습니다.
                    if (result['exp_info']['yn_bonus'] === 'Y') {
                        result_message += `<br /><br />${ns_i18n.t('msg_combination_complete_exp_bonus', [result['exp_info']['bonus_exp']])}`; // 보너스 경험치를 {{1}}만큼 추가로 획득했습니다.
                    }
                    if (ns_util.math(result['after']['level']).gt(result['before']['level'])) {
                        let m_base = ns_cs.m.hero_base[result['before']['m_hero_base_pk']];
                        result_message += `<br /><br />${ns_i18n.t('msg_combination_complete_level_up', [m_base.name, result['before']['level'], result['after']['level']])}`; // [{{1}}]의 레벨이 상승하였습니다.<br /><br />Lv.{{2}} → Lv.${{3}}
                        ns_sound.play('hero_level_up');
                    }
                    ns_dialog.setDataOpen('message', result_message);

                    // 다시 그리기
                    let main_hero = dialog.sorted.filter(h => ns_util.math(h.hero_pk).eq(main_hero_pk))[0];
                    Object.assign(main_hero, _data.hero_info); // 업데이트된 정보 덮어쓰기.
                    new Promise((resolve, reject) => {
                        try {
                            dialog.drawTab();
                            resolve();
                        } catch (e) {
                            console.error(e);
                            reject();
                        }
                    }).then(() => {
                        dialog.selectMaterial(main_hero); // 메인 영웅 다시 선택하기
                        dialog.drawHeroList();
                    });
                }, { useProgress: true });
            }
        });

    } else if (tab === 'skill') {
        if (dialog.special_main_hero.size === 5) {
            ns_dialog.setDataOpen('message', ns_i18n.t('msg_plz_combination_choose_skill')); // 조합을 하기위해서는 스킬이 5개 선택되어야 합니다.
            return;
        }
        combination_message += `<br /><span class="text_red">${ns_i18n.t('msg_combination_skill_warning')}</span><br />`;

        ns_dialog.setDataOpen('confirm', { text: combination_message,
            okFunc: () =>
            {
                let post_data = {};
                post_data['selected_skill'] = Array.from(dialog.material_list).map(pk => pk.split('_').pop()).join(',');

                ns_xhr.post('/api/heroManage/skillCombination', post_data, function(_data, _status)
                {
                    if(! ns_xhr.returnCheck(_data)) {
                        return;
                    }
                    _data = _data['ns_xhr_return']['add_data'];

                    let m = ns_cs.m.hero_skil[_data.comb_skil_pk];
                    ns_dialog.setDataOpen('message', `Lv.${m.rare} ${m.title}`);
                    ns_dialog.dialogs.hero_manage_combination.materialClear();
                    dialog.drawTab();
                }, { useProgress: true });
            }
        });
    }
}

ns_button.buttons.hero_manage_combination_clear = new nsButtonSet('hero_manage_combination_clear','button_special', 'hero_manage_combination');
ns_button.buttons.hero_manage_combination_clear.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_manage_combination;
    dialog.drawTab();
}