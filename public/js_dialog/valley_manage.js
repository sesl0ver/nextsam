ns_dialog.dialogs.valley_manage = new nsDialogSet('valley_manage', 'dialog_full', 'size-full');

ns_dialog.dialogs.valley_manage.cacheContents = function()
{
    this.cont_obj.valley_manage_description = new nsObject('.valley_manage_description', this.obj);
    this.cont_obj.valley_list = new nsObject('.valley_list', this.obj);
    this.cont_obj.skeleton_valley_wrap = new nsObject('#skeleton_valley_wrap');
};

ns_dialog.dialogs.valley_manage.draw = function()
{
    let d = ns_cs.d.bdic[ns_cs.getCastlePk('I', '200200')];
    let description = '';
    if (! d) {
        description = ns_i18n.t('valley_manage_description'); // 행정부를 건설하여 점령지 보유 개수를 늘릴 수 있습니다.
    } else {
        let m = ns_cs.m.buil[200200];
        description = m.level[d.level].variation_description;
    }
    this.cont_obj.valley_manage_description.text(description);

    this.drawList();
}

ns_dialog.dialogs.valley_manage.drawList = function()
{
    let dialog = ns_dialog.dialogs.valley_manage;
    ns_xhr.post('/api/world/occupationValley', {}, (_data, _status) =>
    {
        if(! ns_xhr.returnCheck(_data)) {
            return;
        }
        _data = _data['ns_xhr_return']['add_data'];

        dialog.cont_obj.valley_list.empty();
        for (let o of _data) {
            let valley_wrap = dialog.cont_obj.skeleton_valley_wrap.clone();

            let image_type;
            let image_level = o.level;
            if (o.type === 'A' || o.type === 'E') {
                // Empty 타일
                image_type = 'empty'
                image_level = 0;
            } else {
                image_type = o.type ?? '';
            }
            valley_wrap.find('.tile_image').addCss(`tile_${image_type}_${image_level}`);

            valley_wrap.find('.valley_title').text(`Lv.${o.level} ${codeset.t('valley', o.type)}`);
            valley_wrap.find('.valley_posi_pk').html(ns_util.positionLink(o.valley_posi_pk));

            let production_desc = '';
            if (['F', 'G', 'L', 'M', 'R'].includes(o.type)) {
                let m = ns_cs.m.prod_vall[o.type][o.level];
                for (let _type of ['food', 'horse', 'lumber', 'iron']) {
                    if (ns_util.math(m[_type]).gt(0)) {
                        if (production_desc !== '') {
                            production_desc += ' ';
                        }
                        production_desc += `<span class='resource_${_type}'></span> +${ns_util.numberFormat(m[_type])}/h`;
                    }
                }
            } else {
                // ['A', 'E'].includes(coords._type) "영지 개척 가능" 이었으나 군주성이 1개로 고정되면서 비워둠
                production_desc = '-';
            }

            valley_wrap.find('.production_desc').html(production_desc);

            valley_wrap.find('.current_point').text(`${ns_util.numberFormat(o.current_point)}P`);
            let hours_point = `+${ns_cs.m.prod_vall[o.type][o.level].occupation_point}P/h`;
            valley_wrap.find('.hours_point').text(`${hours_point}`);

            let hero_name = ns_i18n.t('deployed_none'); // 주둔 없음
            if (o.m_hero_pk) {
                let m_hero = ns_cs.m.hero[o.m_hero_pk];
                hero_name = ns_i18n.t(`hero_name_${m_hero.m_hero_base_pk}`) + ` (Lv.${m_hero.level})`;
            }
            valley_wrap.find('.troop_hero').text(hero_name);

            let detail_button = valley_wrap.find('.button_small_1');

            let remove_button = valley_wrap.find('.button_small_2');

            dialog.cont_obj.valley_list.append(valley_wrap);

            // 상세 버튼
            let detail_button_id = `valley_manage_detail_${o.valley_posi_pk}`;
            detail_button.setAttribute('id', `ns_button_${detail_button_id}`);
            ns_button.buttons[detail_button_id] = new nsButtonSet(detail_button_id, null, 'valley_manage');
            ns_button.buttons[detail_button_id].mouseUp = function ()
            {
                ns_dialog.closeAll();
                const [x, y] = o.valley_posi_pk.split('x');
                ns_world.setPosition(x, y);
                // 서버 요청 시간 때문에 딜레이를 두는데 더 좋은 방법은 없나
                setTimeout(() => {
                    let coords = ns_world.coords.get(o.valley_posi_pk);
                    ns_dialog.setDataOpen('world_detail', { coords: coords });
                }, 300);
            }

            dialog.buttons.push(ns_button.buttons[detail_button_id]);

            // 포기 버튼
            let remove_button_id = `valley_manage_remove_${o.valley_posi_pk}`;
            remove_button.setAttribute('id', `ns_button_${remove_button_id}`);
            ns_button.buttons[remove_button_id] = new nsButtonSet(remove_button_id, null, 'valley_manage', { base_class: ns_button.buttons.build_Administration_give_up });

            dialog.buttons.push(ns_button.buttons[remove_button_id]);
        }
    });
}

/* ************************************************** */
ns_button.buttons.valley_manage_close = new nsButtonSet('valley_manage_close', 'button_back', 'valley_manage', { base_class: ns_button.buttons.common_close });
ns_button.buttons.valley_manage_sub_close = new nsButtonSet('valley_manage_sub_close', 'button_full', 'valley_manage', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.valley_manage_close_all = new nsButtonSet('valley_manage_close_all', 'button_close_all', 'valley_manage', { base_class: ns_button.buttons.common_close_all });
