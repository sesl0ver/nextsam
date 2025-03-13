ns_dialog.dialogs.hero_skill_box_list = new nsDialogSet('hero_skill_box_list', 'dialog_pop', 'size-medium', { do_close_all: false });

ns_dialog.dialogs.hero_skill_box_list.cacheContents = function()
{
    this.cont_obj.content_pop_title = new nsObject('.content_pop_title', this.obj);
    this.cont_obj.hero_skill_box_list_wrap = new nsObject('.hero_skill_box_list_wrap', this.obj);
    this.cont_obj.skeleton_hero_skill_box_list = new nsObject('#skeleton_hero_skill_box_list');
}

ns_dialog.dialogs.hero_skill_box_list.draw = function()
{
    this.cont_obj.content_pop_title.text(ns_i18n.t('hero_skill_select'));

    this.drawList();
}

ns_dialog.dialogs.hero_skill_box_list.drawList = function()
{
    let dialog = ns_dialog.dialogs.hero_skill_box_list;
    let list_count = 0;

    dialog.buttonClear();
    dialog.cont_obj.hero_skill_box_list_wrap.empty();

    for (let [k, d] of Object.entries(dialog.data.skill_list)) {
        if (! d) {
            break;
        }
        let m = ns_cs.m.hero_skil[d];
        let o = dialog.cont_obj.skeleton_hero_skill_box_list.clone();
        o.setAttribute('id', `ns_button_hero_skill_box_list_selected_${d}`);
        o.find('.skill_image').addCss(`hero_skill_${String(d).substring(0,4)}`);
        o.find('.skill_rare_type').addCss(`hero_skill_rare${m.rare}`);
        o.find('.hero_skill_title').text(`${m.title} Lv.${m.rare}`);
        o.find('.hero_skill_use_slot').text(`${ns_i18n.t('hero_skill_need_slot')}: ${m.use_slot_count}`);
        o.find('.hero_skill_description').html(m.description_quickuse);

        dialog.cont_obj.hero_skill_box_list_wrap.append(o);

        let button_id = `hero_skill_box_list_selected_${d}`;
        ns_button.buttons[button_id] = new nsButtonSet(button_id, null, 'hero_skill_box_list', { base_class: ns_button.buttons.hero_skill_box_list_selected });
        dialog.buttons.push(ns_button.buttons[button_id]);
    }
};

ns_dialog.dialogs.hero_skill_box_list.boxSelect = function(_m_hero_skil_pk, _m)
{
    let dialog = ns_dialog.dialogs.hero_skill_box_list;
    let data = dialog.data; // 닫히기 전에 처리를 위하여

    let post_data = {};
    post_data['my_hero_skil_box_pk'] = dialog.data.my_hero_skil_box_pk;
    post_data['m_hero_skil_pk'] = _m_hero_skil_pk;

    ns_xhr.post('/api/heroSkill/skillSelected', post_data, function(_data, _status)
    {
        // 에러 여부와 상관없이 창을 먼저 닫기
        ns_dialog.close('hero_skill_box_list');
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        // ns_dialog.setDataOpen('message', `Lv.${_m.rare} ${_m.title} ${system_text.hero_skill_selected}`);
        ns_dialog.setDataOpen('message', { m_skil_pk: _m_hero_skil_pk, package_type: "skill_box", m_item_pk: data.m_item_pk });
    }, { useProgress: true });
};

/* ************************************************** */
ns_button.buttons.hero_skill_box_list_close = new nsButtonSet('hero_skill_box_list_close', 'button_pop_close', 'hero_skill_box_list', { base_class: ns_button.buttons.common_close });
ns_button.buttons.hero_skill_box_list_sub_close = new nsButtonSet('hero_skill_box_list_sub_close', 'button_full', 'hero_skill_box_list', { base_class: ns_button.buttons.common_sub_close });

ns_button.buttons.hero_skill_box_list_selected = new nsButtonSet('hero_skill_box_list_selected', 'button_empty', 'hero_skill_box_list');
ns_button.buttons.hero_skill_box_list_selected.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.hero_skill_box_list;
    let m_hero_skil_pk = this.tag_id.split('_').pop();
    let m = ns_cs.m.hero_skil[m_hero_skil_pk];

    try {
        ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_hero_skill_box_select_confirm'), // 해당 기술을 선택하시겠습니까?
            okFunc: () => {
                dialog.boxSelect(m_hero_skil_pk, m);
            }
        });
    } catch (e) {
        console.error(e);
    }
};
