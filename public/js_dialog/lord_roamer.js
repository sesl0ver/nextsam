ns_dialog.dialogs.lord_roamer = new nsDialogSet('lord_roamer', 'dialog_full', 'size-full');

ns_dialog.dialogs.lord_roamer.cacheContents = function()
{
    this.cont_obj.content_hero_list = new nsObject('.content_hero_list', this.obj);
    this.cont_obj.content_hero_count = new nsObject('.content_hero_count', this.obj);

    this.cont_obj.content_last_territory = new nsObject('.content_last_territory', this.obj);
    this.cont_obj.content_defeated_datetime = new nsObject('.content_defeated_datetime', this.obj);
    this.cont_obj.content_src_lord_name = new nsObject('.content_src_lord_name', this.obj);
    this.cont_obj.content_power = new nsObject('.content_power', this.obj);

    this.cont_obj.content_item_kind = new nsObject('.content_item_kind', this.obj);
    this.cont_obj.content_total_count = new nsObject('.content_total_count', this.obj);
    this.cont_obj.content_qbig = new nsObject('.content_qbig', this.obj);
}

ns_dialog.dialogs.lord_roamer.draw = function()
{
    /*if (this.data.lord_power <= 0) {
        ns_button.buttons.roamer_lord_decision.mouseUp();
        return;
    }*/

    ns_hero.init();
    this.cont_obj.content_hero_list.empty();
    this.sorted = this.data.hero_list;
    let hero_cnt = 0;
    for (let [k, d] of Object.entries(this.sorted)) {
        if (hero_cnt >= 4) {
            break;
        }
        let card = ns_hero.cardDraw(d.hero_pk, 'N', false, d, false, false, true);
        this.cont_obj.content_hero_list.append(card);
        hero_cnt++;
    }
    let hero_count = this.data.hero_list.length - hero_cnt;
    this.cont_obj.content_hero_count.text(hero_count);

    this.cont_obj.content_last_territory.text(this.data.aggression.roamer_last_my_territory);
    this.cont_obj.content_defeated_datetime.text(this.data.aggression.roamer_aggression_dt);
    this.cont_obj.content_src_lord_name.text((this.data.is_inactive === 'Y') ? '휴면 계정 정리' : this.data.aggression.roamer_aggression_lord);
    this.cont_obj.content_power.text(this.data.lord_power);

    this.cont_obj.content_item_kind.text(this.data.lord_item.kind || '0');
    this.cont_obj.content_total_count.text(this.data.lord_item.total_count || '0');
    this.cont_obj.content_qbig.text(this.data.lord_cash);
}

ns_button.buttons.lord_roamer_close = new nsButtonSet('lord_roamer_close', 'button_back', 'lord_roamer', { base_class: ns_button.buttons.common_close });

ns_button.buttons.roamer_lord_decision = new nsButtonSet('roamer_lord_decision', 'button_special', 'lord_roamer');
ns_button.buttons.roamer_lord_decision.mouseUp = function(_e)
{
    ns_xhr.post('/api/lord/roamerLoad', ns_auth.getAll(), (_data, _status) => {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];
        ns_cs.startSession();
    }, { useProgress: true });
}