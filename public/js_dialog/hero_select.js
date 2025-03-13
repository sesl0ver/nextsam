// 다이얼로그
ns_dialog.dialogs.hero_select = new nsDialogSet('hero_select', 'dialog_building', 'size-large');
ns_dialog.dialogs.hero_select.opened = false;
ns_dialog.dialogs.hero_select.select_hero_pk = null;
ns_dialog.dialogs.hero_select.cacheContents = function()
{
    this.cont_obj.content_card_wrap = new nsObject('.content_card_wrap', this.obj);

    // this.content_obj.content_card_wrap.css('min-height', (this.size.height-117) + 'px');

    this.cont_obj.content_previous_card = new nsObject('.content_previous_card', this.obj);
    this.cont_obj.content_previous_card_title = new nsObject('.content_previous_card_title', this.obj);
    this.cont_obj.content_previous_card_message = new nsObject('.content_previous_card_message', this.obj);
    this.cont_obj.content_previous_card_button = new nsObject('.content_previous_card_button', this.obj);

    this.cont_obj.content_previous_hero_detail = new nsObject('.content_previous_hero_detail', this.obj);
    this.cont_obj.content_previous_applied_capa = new nsObject('.content_previous_applied_capa', this.obj);
    this.cont_obj.content_previous_applied_skill = new nsObject('.content_previous_applied_skill', this.obj);

    this.cont_obj.content_card = new nsObject('.content_card', this.obj);
    this.cont_obj.content_card_title = new nsObject('.content_next_card_title', this.obj);
    this.cont_obj.content_card_message = new nsObject('.content_next_card_message', this.obj);
    this.cont_obj.content_card_button = new nsObject('.content_next_card_button', this.obj);

    this.cont_obj.content_hero_detail = new nsObject('.content_hero_detail', this.obj);
    this.cont_obj.content_applied_capa = new nsObject('.content_applied_capa', this.obj);
    this.cont_obj.content_applied_skill = new nsObject('.content_applied_skill', this.obj);

    this.cont_obj.content_card_no_select = new nsObject('.content_card_no_select', this.cont_obj.content_card.element);
    this.cont_obj.content_card_no_select_title = new nsObject('.content_card_no_select_title', this.cont_obj.content_card.element);
    this.cont_obj.content_card_no_select_desc = new nsObject('.content_card_no_select_desc', this.cont_obj.content_card.element);
    this.cont_obj.content_card_select = new nsObject('.content_card_select', this.cont_obj.content_card.element);
}

ns_dialog.dialogs.hero_select.draw = function (_e)
{
    let do_type, undo_type, prev_card_title, prev_card_message, next_card_title, next_card_message, next_card_message_selected;

    if (['const', 'techn', 'encou', 'invit'].includes(this.data.type)) {
        // 건설, 연구, 탐색, 초빙은 이전 영웅 없음.
        next_card_title = ns_i18n.t('hero_in_action'); // 수행 중인 영웅
        next_card_message = ns_i18n.t('hero_in_action_plz_choose'); // 수행할 영웅을 선택하세요.
        next_card_message_selected = ns_i18n.t('hero_in_action_choose_confirm'); // 선택된 영웅으로 수행하시겠습니까?

        do_type = ns_i18n.t('performance'); // 수행
    } else if (['assign', 'assign_wall'].includes(this.data.type)) {
        prev_card_title = ns_i18n.t('hero_assigned'); // 배속 중인 영웅
        prev_card_message = (this.data.prev_hero_undo) ? ns_i18n.t('hero_unassign_ok') : ns_i18n.t('hero_unassign_no') ; // 배속된 영웅을 해제할 수 있습니다. : 배속된 영웅을 해제할 수 없습니다.;

        next_card_title = ns_i18n.t('hero_assign_choose'); // 배속할 영웅
        next_card_message = ns_i18n.t('hero_assign_plz_choose'); // 배속할 영웅을 선택하세요.
        next_card_message_selected = ns_i18n.t('hero_assign_choose_confirm'); // 선택한 영웅으로 배속하시겠습니까?

        do_type = ns_i18n.t('assign'); // 배속
        undo_type = ns_i18n.t('unassign'); // 배속 해제
    } else {
        prev_card_title = ns_i18n.t('hero_selected'); // 선택된 영웅
        prev_card_message = (this.data.prev_hero_undo) ? ns_i18n.t('hero_unselect_ok') : ns_i18n.t('hero_unselect_no') ; // 선택된 영웅을 해제할 수 있습니다. : 선택된 영웅을 해제할 수 없습니다.;

        next_card_title = ns_i18n.t('hero_choose_change'); // 교체할 영웅
        next_card_message = ns_i18n.t('hero_plz_choose_change'); // 교체할 영웅을 선택하세요.
        next_card_message_selected = ns_i18n.t('hero_plz_choose_change_confirm'); // 선택된 영웅으로 교체하시겠습니까?

        do_type = ns_i18n.t('choose'); // 선택
        undo_type = ns_i18n.t('choose_cancel'); // 선택 취소
    }

    this.hero_pk = null;

    if (! this.data?.prev_hero_pk) {
        this.cont_obj.content_previous_card.hide();
        this.cont_obj.content_previous_card_message.hide();
        this.cont_obj.content_previous_card_button.hide();
    } else {
        this.cont_obj.content_previous_card_title.text(prev_card_title);
        this.cont_obj.content_previous_card_message.text(prev_card_message);
        ns_button.buttons.hero_select_undo.obj.text(undo_type);

        if (this.data.prev_hero_undo) {
            ns_button.buttons.hero_select_undo.setEnable();
        } else {
            ns_button.buttons.hero_select_undo.setDisable();
        }

        this.cont_obj.content_previous_hero_detail.empty();
        this.cont_obj.content_previous_hero_detail.append(ns_hero.cardDetailDraw(this.data.prev_hero_pk, 'N'));

        this.cont_obj.content_previous_applied_capa.text('-');
        this.cont_obj.content_previous_applied_skill.text('-');

        let effects = ns_hero.getEffect(this.data.prev_hero_pk, null, this.data.type, this.data.type_data);
        if (effects.capa) {
            this.cont_obj.content_previous_applied_capa.html(effects.capa);
        }
        if (effects.skill) {
            this.cont_obj.content_previous_applied_skill.html(effects.skill);
        }

        this.cont_obj.content_previous_card.show();
        this.cont_obj.content_previous_card_message.show();
        this.cont_obj.content_previous_card_button.show();
    }

    this.cont_obj.content_card_title.text(next_card_title);
    this.cont_obj.content_card_message.text(next_card_message);
    ns_button.buttons.hero_select_do.obj.text(do_type);
    ns_button.buttons.hero_select_do.setDisable();

    this.cont_obj.content_card_no_select.show();
    this.cont_obj.content_card_no_select_title.html(this.data.nosel_title);
    this.cont_obj.content_card_no_select_desc.html(this.data.nosel_desc);
    this.cont_obj.content_card_select.hide();

    let callback = function (_hero_pk)
    {
        let dialog = ns_dialog.dialogs.hero_select;
        dialog.hero_pk = _hero_pk;

        dialog.cont_obj.content_card_message.text(next_card_message_selected);
        ns_button.buttons.hero_select_do.setEnable();

        dialog.cont_obj.content_hero_detail.empty().append(ns_hero.cardDetailDraw(dialog.hero_pk, 'N'));

        dialog.cont_obj.content_applied_capa.text('-');
        dialog.cont_obj.content_applied_skill.text('-');

        let effects = ns_hero.getEffect(_hero_pk, null, dialog.data.type, dialog.data.type_data);
        if (effects.capa) {
            dialog.cont_obj.content_applied_capa.html(effects.capa);
        }
        if (effects.skill) {
            dialog.cont_obj.content_applied_skill.html(effects.skill);
        }

        dialog.cont_obj.content_card_no_select.hide();
        dialog.cont_obj.content_card_select.show();
    }

    /*
     * this.data structure
     *
     * type
     *  - const : build_time
     *  - encou : build_time
     *  - invit : build_time
     *  - techn : build_time
     *  - assign : no_sel_title, no_sel_desc
     *  - assign_wall : no_sel_title, no_sel_desc
     *  - assign_troop_order : no_sel_title, no_sel_desc
     * sort_stat_type : leadership ...
     * limit_stat_type : L ...
     * limit_stat_value : 1 이상
     * selector_use : 'N' or true
     */

    ns_select_box.set('hero_select_filter', this.data.sort_stat_type);
    ns_button.buttons.hero_select_filter.obj.text(ns_select_box.getText('hero_sel_filter'));

    ns_hero.deckSelectionSet(callback, this.data.sort_stat_type, this.data.limit_stat_type, this.data.limit_stat_value, this.data.selector_use);

    if (ns_hero.on_select_possible_count === 0) {
        ns_hero.hideDeckList();
        if (!this.data.limit_stat_value || this.data.limit_stat_value === 1) {
            this.cont_obj.content_card_no_select_title.text(ns_i18n.t('information')); // 안내
        } else {
            this.cont_obj.content_card_no_select_title.html(`${ns_i18n.t('hero_choose_need_status', [ns_i18n.t(`stats_${this.data.limit_stat_type}`), this.data.limit_stat_value])}<br />&nbsp;`); // {{1}} {{2}} 이상의 영웅이 필요 합니다.
        }
        // 대기 중 이거나 조건에 만족하는 영웅이 없습니다. 영지 내 영웅이 부족할 경우 영웅 모집을 수행하거나 영빈관을 통해서 등용해 보세요.
        this.cont_obj.content_card_no_select_desc.html(`<span>${ns_i18n.t('no_hero_idle_or_condition_ok')}</span><br><br><span>${ns_i18n.t('need_hero_pickup_information')}</span>`);
    } else {
        // 영웅 자동선택
        if (this.data && this.data.auto && this.data.do_callback) {
            new Promise((resolve, reject) => {
                this.data.do_callback.call(null, this.hero_pk);
                resolve();
            }).then(() => {
                ns_dialog.close('hero_select');
            });
        }
    }
}

ns_dialog.dialogs.hero_select.erase = function()
{
    ns_hero.deckSelectionUnset();
}

/* 버튼 */
ns_button.buttons.hero_select_close = new nsButtonSet('hero_select_close', 'button_back', 'hero_select', { base_class: ns_button.buttons.common_close });
ns_button.buttons.hero_select_sub_close = new nsButtonSet('hero_select_sub_close', 'button_full', 'hero_select', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.hero_select_close_all = new nsButtonSet('hero_select_close_all', 'button_close_all', 'hero_select', { base_class: ns_button.buttons.common_close_all });

ns_button.buttons.hero_select_undo = new nsButtonSet('hero_select_undo', 'button_special', 'hero_select');
ns_button.buttons.hero_select_undo.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_select;
    if (dialog.data.undo_callback) {
        dialog.data.undo_callback.call(null, dialog.hero_pk);
    }
    dialog.close();
}

ns_button.buttons.hero_select_do = new nsButtonSet('hero_select_do', 'button_special', 'hero_sel');
ns_button.buttons.hero_select_do.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_select;

    if (! dialog.hero_pk) {
        ns_dialog.setDataOpen('message', ns_i18n.t('plz_hero_choose')); // 영웅을 선택하세요.
        return;
    }

    if (dialog.data.do_callback) {
        dialog.data.do_callback.call(null, dialog.hero_pk);
    }

    dialog.close();
}