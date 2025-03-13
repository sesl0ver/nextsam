class nsReport {
    constructor (_template) {
        try {
            this._template = _template;
        } catch (e) {
            console.error(e);
        }
    }

    setSummary (_summary, _data = {})
    {
        try {
            let summary = document.createElement('p');
            summary.classList.add('report_summary');
            summary.innerHTML = ns_util.positionLink(_summary);
            this._template.append(summary);
            for (let [selector, d] of Object.entries(_data)) {
                this._template.find(selector).html(d);
            }
        } catch (e) {
            console.error(e);
        }
    }

    drawDescription (_title, _description)
    {
        try {
            let info_wrap = new nsObject('#report_info_wrap_skeleton').clone();
            info_wrap.find('.report_sub_title').text(_title);
            let p = document.createElement('p');
            p.innerHTML = _description;
            info_wrap.find('.report_list_info').append(p);
            this._template.append(info_wrap);
        } catch (e) {
            console.error(e);
        }
    }

    drawAppend (_title, _element)
    {
        try {
            let info_wrap = new nsObject('#report_info_wrap_skeleton').clone();
            info_wrap.find('.report_sub_title').text(_title);
            info_wrap.find('.report_list_info').append(_element);
            this._template.append(info_wrap);
        } catch (e) {
            console.error(e);
        }
    }

    battleAppend (_report_type, _data)
    {
        let _c = _data['content_json'];
        try {
            let _result;
            if (['battle_attack_victory', 'battle_attack_defeat'].includes(_data['report_type'])) {
                _result = (_c['outcome']['winner'] === 'att') ? 'victory' : 'defeat';
            } else {
                _result = (_c['outcome']['winner'] === 'def') ? 'victory' : 'defeat';
            }

            let info_wrap = new nsObject('#skeleton_report_battle').clone();
            info_wrap.find('.battle_result_image').addCss(_result);

            let att_result_class = (_c['outcome']['winner'] === 'att') ? 'victory' : 'defeat';
            let def_result_class = (_c['outcome']['winner'] === 'def') ? 'victory' : 'defeat';
            info_wrap.find('.area_attack > .result').addCss(att_result_class);
            info_wrap.find('.area_defence > .result').addCss(def_result_class);

            let att_posi_name = (['battle_attack_victory', 'battle_attack_defeat'].includes(_report_type)) ? _data.from_posi_name : _data.to_posi_name;
            let def_posi_name = (['battle_attack_victory', 'battle_attack_defeat'].includes(_report_type)) ? _data.to_posi_name : _data.from_posi_name;
            info_wrap.find('.area_attack > .position').html(ns_util.positionLink(ns_text.convertPositionName(att_posi_name, false, true, true)));
            info_wrap.find('.area_defence > .position').html(ns_util.positionLink(ns_text.convertPositionName(def_posi_name, false, true, true)));

            let hero_types = ['captain', 'director', 'staff'];
            let attack_heroes = {};
            let defence_heroes = {};

            // 영웅 정보
            if (_c['outcome_hero']) {
                let att_hero = _c['outcome_hero'].att;
                let def_hero = _c['outcome_hero'].def;

                for (let _type of hero_types) {
                    if (att_hero[`${_type}_hero_pk`]) {
                        attack_heroes[_type] = { pk: att_hero[`${_type}_hero_pk`], m_pk: att_hero[`${_type}_m_hero_pk`] };
                    }
                    if (def_hero[`${_type}_hero_pk`]) {
                        defence_heroes[_type] = { pk: def_hero[`${_type}_hero_pk`], m_pk: def_hero[`${_type}_m_hero_pk`] };
                    }
                }

                if (Object.keys(attack_heroes).length > 0) {
                    for (let d of Object.values(attack_heroes)) {
                        let data = { hero_pk: d.pk ?? d.hero_pk ?? null, m_hero_pk: d.m_pk ?? d.m_hero_pk };
                        info_wrap.find('.area_attack > .hero').append(ns_hero.cardDraw(data.hero_pk, 'N', false, data, false, false, true));
                    }
                }
                if (Object.keys(defence_heroes).length > 0) {
                    for (let d of Object.values(defence_heroes)) {
                        let data = { hero_pk: d.pk ?? d.hero_pk ?? null, m_hero_pk: d.m_pk ?? d.m_hero_pk };
                        info_wrap.find('.area_defence > .hero').append(ns_hero.cardDraw(data.hero_pk, 'N', false, data, false, false, true));
                    }
                }
            } else {
                info_wrap.find('.area_attack > .hero').remove();
                info_wrap.find('.area_defence > .hero').remove();
            }

            // 병력 정보
            let att_army = { total: 0, dead: 0, injury: 0, remain: 0 };
            let def_army = { total: 0, dead: 0, injury: 0, remain: 0 };
            if (_c['outcome_unit']) {
                let unit_att = _c['outcome_unit'].att;
                let unit_def = _c['outcome_unit'].def;
                if (! unit_att) {
                    info_wrap.find(`.area_attack > .army`).remove();
                } else {
                    for (let [k, d] of Object.entries(unit_att)) {
                        att_army.total += parseInt(d?.amount ?? '0');
                        att_army.injury += parseInt(d?.injury ?? '0');
                        att_army.remain += parseInt(d?.remain ?? '0');
                        /*if (k === 'abandon_army') {
                            if (d === true && battle_info.att.lord_info.lord_name === ns_cs.d.lord.lord_name.v) {
                                ns_report.drawDescription('부상병 수용', '의료원의 부상병 수용 공간이 부족하여 부상병을 모두 수용할 수 없었습니다.');
                            }
                        }*/
                    }
                    att_army.dead = ns_util.math(att_army.total).minus(att_army.injury).minus(att_army.remain).number;

                    for (let [k, d] of Object.entries(att_army)) {
                        info_wrap.find(`.area_attack > .army .${k}`).text(ns_util.numberFormat(d));
                    }
                }
                if (! unit_def) {
                    info_wrap.find(`.area_defence > .army`).remove();
                } else {
                    for (let [k, d] of Object.entries(unit_def)) {
                        def_army.total += parseInt(d?.amount ?? '0');
                        def_army.injury += parseInt(d?.injury ?? '0');
                        def_army.remain += parseInt(d?.remain ?? '0');
                        /*if (k === 'abandon_army') {
                            if (d === true && battle_info.def.lord_info.lord_name === ns_cs.d.lord.lord_name.v) {
                                ns_report.drawDescription('부상병 수용', '의료원의 부상병 수용 공간이 부족하여 부상병을 모두 수용할 수 없었습니다.');
                            }
                        }*/
                    }
                    def_army.dead = ns_util.math(def_army.total).minus(def_army.injury).minus(def_army.remain).number;

                    for (let [k, d] of Object.entries(def_army)) {
                        info_wrap.find(`.area_defence > .army .${k}`).text(ns_util.numberFormat(d));
                    }
                }
            }

            this._template.append(info_wrap);

            // 방어측 자원
            let resource_info = {};
            if (_c['outcome']['plunder']) {
                for (let [k, d] of Object.entries(_c['plunder'].own)) {
                    resource_info[k] = d;
                }
            }
            if (Object.keys(resource_info).length > 0) {
                this.drawBoxList(ns_i18n.t('defense_resources'), 'resource', resource_info);
            }

            // 약탈 자원
            resource_info = {};
            if (_c['outcome']['plunder']) {
                for (let [k, d] of Object.entries(_c['plunder'].get)) {
                    resource_info[k] = d;
                }
            }
            this.drawBoxList(ns_i18n.t('predatory_resources'), 'resource', resource_info);

            // 획득 아이템
            if (_c['outcome']['reward'] && _c['outcome']['reward']?.['item_desc']) {
                let _reward = _c['outcome']['reward'];
                let reward_item = {};
                reward_item[_reward.item_pk] = _reward.item_cnt;
                let box = this.drawBoxList(ns_i18n.t('acquired_item'), 'item', reward_item);
                if (_reward?.double_event === true) {
                    box.find('div.item_image').addCss('double_event');
                }
            }

            // 상세 보기 버튼 생성
            if (_data.content_battle_json) {
                let button_id = `report_battle_detail`;
                this.drawDetailButton(button_id);
                ns_button.buttons[button_id].mouseUp = function ()
                {
                    ns_dialog.setDataOpen('report_battle_detail', _data);
                }
            } else {
                let info_wrap = new nsObject('#report_info_wrap_skeleton').clone();
                info_wrap.find('.report_sub_title').text('상세 정보');
                let span = document.createElement('span');
                span.innerText = '전투가 발생하지 않아 표기할 상세정보가 없습니다.'
                info_wrap.find('.report_list_info').append(span);

                this._template.append(info_wrap);
            }
        } catch (e) {
            console.error(e);
        }
    }

    drawDetailButton (_button_id)
    {
        let button_wrap = document.createElement('div');
        button_wrap.classList.add('detail_button_wrap');
        let button = document.createElement('span');
        button.setAttribute('id', `ns_button_${_button_id}`);
        button.innerText = '상세 정보';
        button_wrap.appendChild(button);
        this._template.append(button_wrap);
        ns_button.buttons[_button_id] = new nsButtonSet(_button_id, 'button_default', 'report');
    }

    drawBoxList (_title, _type, _data = {})
    {
        try {
            let info_wrap = new nsObject('#report_info_wrap_skeleton').clone();
            info_wrap.find('.report_sub_title').text(_title);
            let is_empty = true;
            for (let [k, d] of Object.entries(_data)) {
                let box;
                if (ns_util.isNumeric(d)) {
                    if (ns_util.math(d).gt(0)) {
                        box = this.resourceBox(_type, k, d);
                    }
                } else if (typeof d === "string") {
                    if (d !== '') {
                        box = this.resourceBox(_type, k, d);
                    }
                } else if (typeof d === "object") {
                    box = this.resourceBox(_type, k, d);
                }
                if (box) {
                    info_wrap.find('.report_list_info').append(box);
                    is_empty = false;
                }
            }
            if (is_empty) {
                let span = document.createElement('span');
                span.innerText = ns_i18n.t('none');
                info_wrap.find('.report_list_info').append(span);
            }
            this._template.append(info_wrap);
            return info_wrap;
        } catch (e) {
            console.error(e);
        }
    }

    resourceBox (_type, _code, _prev, _loss = null)
    {
        let title = '',
            resource_box = (['army', 'fort'].includes(_type)) ? new nsObject('#report_army_box_skeleton').clone() : new nsObject('#report_box_skeleton').clone();
        if (['army', 'fort'].includes(_type)) {
            resource_box.find('div.item_image').addCss(`${_type}_image_${_code}`);
            if (_type === 'fort') {
                title = (_code === 'wall') ? ns_i18n.t('castle_wall') : ns_cs.m.fort[_code].title;
            } else {
                title = ns_cs.m.army[_code].title;
            }
            resource_box.find('span.report_box_title').text(title);
            if (ns_util.isNumeric(_prev)) {
                resource_box.find('span.num_total').text(ns_util.numberFormat(_prev));
            } else if (typeof _prev === 'string') {
                resource_box.find('span.num_total').text(_prev);
            } else {
                resource_box.find('span.num_total').text(ns_util.numberFormat(_prev?.amount ?? 0));
            }
            if (! _prev?.remain) {
                resource_box.find('span.num_remain').remove();
            } else {
                resource_box.find('span.num_remain').text(ns_util.numberFormat(_prev.remain));
            }

            if (_type === 'fort') {
                resource_box.find('span.num_injury').remove();
            } else {
                if (! _prev?.injury) {
                    resource_box.find('span.num_injury').remove();
                } else {
                    resource_box.find('span.num_injury').text(ns_util.numberFormat(_prev.injury));
                }
            }
            if (! _prev?.dead) {
                resource_box.find('span.num_dead').remove();
            } else {
                resource_box.find('span.num_dead').text(ns_util.numberFormat(_prev.dead));
            }
        } else {
            _prev = (! ns_util.isNumeric(_prev)) ? ((! _prev) ? null : _prev) : ns_util.numberFormat(_prev);
            _loss = (! ns_util.isNumeric(_loss)) ? ((! _loss) ? null : _loss) : ns_util.numberFormat(_loss);

            if (_type === 'resource') {
                _code = (['F', 'L', 'H', 'I', 'G'].includes(_code)) ? codeset.c('resource', _code) : _code;
                resource_box.find('div.item_image').addCss(`resource_image_${_code}`);
                title = ns_i18n.t(`resource_${_code}`);
            } else if (_type === 'skill') {
                resource_box.classList.add(`hero_skill_${_code.substring(0, 4)}`);
                resource_box.classList.add(`rare_border_${_code.substring(5, 6)}`);
                title = ns_cs.m.hero_skil[_code].title;
            } else if (_type === 'item') {
                resource_box.find('div.item_image').addCss(`item_image_${_code}`);
                title = ns_cs.m.item[_code].title;
            }
            resource_box.find('span.report_box_title').text(title);
            if (_prev) {
                resource_box.find('span.num_prev').text(_prev);
            } else {
                resource_box.find('span.num_prev').remove();
            }
            if (_loss) {
                resource_box.find('span.num_loss').text(_loss);
            } else {
                resource_box.find('span.num_loss').remove();
            }
        }
        return resource_box;
    }

    drawHeroList (_title, _heroes)
    {
        try {
            let info_wrap = new nsObject('#report_info_wrap_skeleton').clone();
            info_wrap.find('.report_sub_title').text(_title);
            let is_empty = true;
            for (let d of Object.values(_heroes)) {
                let data = { hero_pk: d.pk ?? d.hero_pk ?? null, m_hero_pk: d.m_pk ?? d.m_hero_pk };
                info_wrap.find('.report_list_info').append(ns_hero.cardDraw(data.hero_pk, 'N', false, data, false, false, true));
                is_empty = false;
            }
            if (is_empty) {
                let span = document.createElement('span');
                span.innerText = ns_i18n.t('none');
                info_wrap.find('.report_list_info').append(span);
            }
            this._template.append(info_wrap);
        } catch (e) {
            console.error(e);
        }
    }

    drawRecall (_recall)
    {
        if (_recall?.script) {
            this._template.html(_recall.script, true);
        }
        if (_recall?.move_time && this._template.find('.cont_recall_move_time').element) {
            this._template.find('.cont_recall_move_time').html(ns_util.getCostsTime(_recall.move_time));
        }
        if (_recall?.end_dt && this._template.find('.cont_recall_end_dt').element) {
            this._template.find('.cont_recall_end_dt').html(ns_timer.getDateTimeString(_recall.end_dt, true, true, true));
        }
        if (_recall?.withdrawal_dt && this._template.find('.cont_recall_withdrawal_dt').element) {
            this._template.find('.cont_recall_withdrawal_dt').html(ns_timer.getDateTimeString(_recall.withdrawal_dt, true, true, true));
        }
    }
}