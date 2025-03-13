class nsCheckCondition
{
    checkBuildMaxLevel (m_buil_pk)
    {
        let m = ns_cs.m.buil[m_buil_pk];
        let bdc = (m.type === 'I') ? ns_cs.d.bdic : ns_cs.d.bdoc;
        let max_level = 0;
        for (let [castle_pk, d] of Object.entries(bdc).filter(d => ns_util.isNumeric(d[0]) && ns_util.math(d[1].m_buil_pk).eq(m_buil_pk))) {
            if (ns_util.math(max_level).lt(d.level)) {
                max_level = d.level;
            }
        }
        return max_level;
    }

    checkType (_m_cond_pk, _in_cast_pk, _type)
    {
        let cond = ns_cs.m.cond[_m_cond_pk],
            ret = true,
            idle;

        if (_type === 'active_buil_level' && _in_cast_pk) {
            ret = !(!ns_cs.d.bdic?.[_in_cast_pk] || ns_util.math(ns_cs.d.bdic[_in_cast_pk].level).lt(cond.active_buil_level));
        } else if (_type === 'yn_hero_assign_required' && _in_cast_pk) {
            ret = !(!ns_cs.d.bdic?.[_in_cast_pk] || !ns_cs.d.bdic[_in_cast_pk]?.assign_hero_pk);
        } else if (_type === 'cmd_hero_stat_type') {
            // TODO 별 다르게 체크할 것 없지만 차후에 추가될 상황을 위해 남겨둠.
        } else if (_type === 'm_buil_pk') {
            ret = !(ns_util.math(this.checkBuildMaxLevel(cond.m_buil_pk)).lt(cond.m_buil_level));
        } else if (_type === 'm_tech_pk') {
            ret = !(ns_util.math(ns_cs.d.tech[ns_cs.m.tech[cond.m_tech_pk].code].v).lt(cond.m_tech_level));
        } else if (_type === 'm_item_pk') {
            ret = !(!ns_cs.d.item[cond.m_item_pk] || ns_util.math(ns_cs.d.item[cond.m_item_pk].item_cnt).lt(cond.m_item_cnt));
        } else if (_type === 'build_food') {
            ret = !(ns_util.math(ns_cs.getResourceInfo('food_curr')).lt(cond.build_food));
        } else if (_type === 'build_horse') {
            ret = !(ns_util.math(ns_cs.getResourceInfo('horse_curr')).lt(cond.build_horse));
        } else if (_type === 'build_lumber') {
            ret = !(ns_util.math(ns_cs.getResourceInfo('lumber_curr')).lt(cond.build_lumber));
        } else if (_type === 'build_iron') {
            ret = !(ns_util.math(ns_cs.getResourceInfo('iron_curr')).lt(cond.build_iron));
        } else if (_type === 'build_gold') {
            ret = !(ns_util.math(ns_cs.d.terr.gold_curr.v).lt(cond.build_gold));
        } else if (_type === 'need_population') {
            ret = !(ns_util.math(ns_cs.d.terr.population_idle.v).lt(cond.need_population));
        } else if (_type === 'need_vacancy') {
            if (ns_util.math(_in_cast_pk).gt(0)) {
                idle = ns_util.math(ns_cs.d.terr.wall_vacancy_max.v).minus(ns_cs.d.terr.wall_vacancy_curr.v).number;
            } else {
                // 사용중인 공간
                let vacancy_curr = 0;
                for (let [k, d] of Object.entries(ns_dialog.dialogs.world_fort.forification)) {
                    vacancy_curr = ns_util.math(d).mul(ns_cs.m.fort[k].need_vacancy).plus(vacancy_curr).number;
                }
                idle = ns_util.math(50000).minus(vacancy_curr).number; // TODO 50000은 상수로 빼는게 낫지 않나?
            }
            if (ns_util.math(idle).lt(cond.need_vacancy)) {
                ret = false;
            }
        }
        else {
            ret = false; // unsupported
        }
        return ret;
    }

    checkAll (_m_cond_pk, _in_cast_pk = 0, _tbody = null, _simplify = false)
    {
        let cond = ns_cs.m.cond[_m_cond_pk],
            pass = true,
            count = 0,
            item = {},
            tag;


        // 영지 건설 - 군주등급, 남은확장영지 - 하드코딩
        if (_m_cond_pk === 100000) {
            // 군주등급
            /*if (ns_util.math(ns_cs.d.lord.level.v).gt(1)) {
                item['need_class'] = 'qbw_text_condition_yes';
            } else {
                item['need_class'] = 'qbw_text_condition_no';
                pass = false;
            }
            if (_tbody) {
                item['own_class'] = '';
                item['title'] = system_text.cond_lord_level;
                item['need'] = 1;
                item['own'] = ns_cs.d.lord.level.v;
                this.drawTable(_tbody, item);
            }
            count++;*/
            if (ns_dialog.dialogs.world_detail.my_troo_pk) {
                item['need_class'] = 'text_condition_yes'; // TODO 오타 있었음 text_condition_yes
                item['own'] = ns_i18n.t('deployed');
            } else {
                item['need_class'] = 'text_condition_no'; // TODO 오타 있었음 text_condition_no
                item['own'] = ns_i18n.t('none');
                pass = false;
            }
            if (_tbody) {
                item['own_class'] = '';
                item['title'] = ns_i18n.t('troop');
                item['need'] = ns_i18n.t('deployed');
                this.drawTable(_tbody, item);
            }
            count++;

            // 남은확장영지
            if (ns_util.math(ns_cs.getPositionCount()).minus(ns_cs.d.lord.position_cnt.v).gte(1)) {
                item['need_class'] = 'text_condition_yes';
            } else {
                item['need_class'] = 'text_condition_no';
                pass = false;
            }

            if (_tbody) {
                item['own_class'] = '';
                item['title'] = ns_i18n.t('territory_slot');
                item['need'] = 1;
                item['own'] = ns_util.math(ns_cs.d.lord.level.v).minus(ns_cs.d.lord.position_cnt.v).number;
                this.drawTable(_tbody, item);
            }
            count++;
        }

        // 활성화 조건
        if (_in_cast_pk !== 0 && cond.active_buil_level) {
            if (this.checkType(_m_cond_pk, _in_cast_pk, 'active_buil_level')) {
                item['need_class'] = 'text_condition_yes';
            } else {
                item['need_class'] = 'text_condition_no';
                pass = false;
            }

            if (_simplify === false && _tbody) {
                item['own_class'] = '';
                item['title'] = ns_i18n.t('activation');
                item['need'] = ns_i18n.t(`build_title_${ns_cs.d.bdic[_in_cast_pk].m_buil_pk}`) + ' ' + ns_util.getLevelStr(cond.active_buil_level);
                item['own'] = ns_util.getLevelStr(ns_cs.d.bdic[_in_cast_pk].level);
                this.drawTable(_tbody, item);
            }
            count++;
        }

        // 배속영웅 조건
        if (_in_cast_pk !== 0 && cond.yn_hero_assign_required === 'Y') {
            if (this.checkType(_m_cond_pk, _in_cast_pk, 'yn_hero_assign_required')) {
                item['need_class'] = 'text_condition_yes';
            } else {
                item['need_class'] = 'text_condition_no';
                pass = false;
            }

            if (_simplify === false && _tbody) {
                item['own_class'] = '';
                item['title'] = ns_i18n.t('hero');
                item['need'] = ns_i18n.t('assign');
                if (item['need_class'].indexOf('_yes') !== -1) {
                    item['own'] = ns_hero.getNameWithLevel(ns_cs.d.bdic[_in_cast_pk].assign_hero_pk);
                } else {
                    item['own'] = ns_i18n.t('none');
                }
                this.drawTable(_tbody, item);
            }
            count++;
        }

        // 명령영웅 조건
        if (_in_cast_pk !== 0 && cond.cmd_hero_stat_type) {
            item['need_class'] = 'text_condition_yes';
            if (_simplify === false && _tbody) {
                item['own_class'] = '';
                item['title'] = ns_i18n.t('hero');
                item['need'] = ns_i18n.t('condition_need_hero_stat', [ns_i18n.t(`stats_${code_set['hero_stat_type'][cond.cmd_hero_stat_type]}`), cond.cmd_hero_stat_value]);
                item['own'] = ns_i18n.t('verifiable_on_command');
                this.drawTable(_tbody, item);
            }
            count++;
        }

        // 선행 건물
        if (cond.m_buil_pk) {
            let need_build = false;
            if (this.checkType(_m_cond_pk, _in_cast_pk, 'm_buil_pk')) {
                item['need_class'] = 'text_condition_yes';
            } else {
                item['need_class'] = 'text_condition_no';
                item['need_buil_pk'] = cond.m_buil_pk;
                item['need_condition'] = cond.m_buil_level;
                pass = false;
                need_build = true;
            }

            if (_tbody) {
                item['own_class'] = '';
                item['title'] = ns_i18n.t('building');
                item['need'] = `${ns_i18n.t(`build_title_${cond.m_buil_pk}`)} ${ns_util.getLevelStr(cond.m_buil_level)}`;
                let max_level = this.checkBuildMaxLevel(cond.m_buil_pk);
                item['own'] = (ns_util.math(max_level).lte(0)) ? ns_i18n.t('none') : ns_util.getLevelStr(max_level);
                if (need_build) {
                    item['button_id'] = 'need_build';
                    item['button_function'] = function ()
                    {
                        let m_buil_pk = cond.m_buil_pk;
                        let need_level = cond.m_buil_level;
                        let m = ns_cs.m.buil[m_buil_pk];
                        let castle_type = (m.type === 'I') ? 'bdic' : 'bdoc';

                        ns_dialog.closeAll();
                        let first_pk = 0;
                        let select_pk = 0;
                        let bdc = ns_cs.d[castle_type];
                        let current_level = ns_util.math(need_level).minus(1).integer;
                        for (let [castle_pk, d] of Object.entries(bdc).filter(d => ns_util.isNumeric(d[0]) && ns_util.math(d[1].m_buil_pk).eq(m_buil_pk))) {
                            if (first_pk === 0) {
                                first_pk = castle_pk;
                            }
                            if (ns_util.math(d.level).eq(current_level)) { // 가장 레벨이 높은 건물 우선
                                select_pk = castle_pk;
                                break;
                            }
                        }
                        if (ns_util.math(select_pk).eq(0)) {
                            select_pk = first_pk;
                        }
                        // 건물이 없는 경우
                        if (! bdc[select_pk]) {
                            ns_dialog.setDataOpen('build_upgrade', {
                                'm_buil_pk': m_buil_pk,
                                'castle_type': castle_type,
                                'castle_pk': ns_cs.getEmptyTile(m.type) // 빈타일 중 최우선 타일
                            });
                            return;
                        }
                        // 건물 존재함. 업그레이드 중인 경우.
                        if (bdc[select_pk].status === 'U') {
                            ns_dialog.setDataOpen('build_' + ns_cs.m.buil[bdc[select_pk].m_buil_pk].alias, { castle_pk: select_pk, castle_type: castle_type });
                        } else {
                            ns_dialog.setDataOpen('build_upgrade', {
                                'm_buil_pk': m_buil_pk,
                                'castle_type': castle_type,
                                'castle_pk': select_pk
                            });
                        }
                    }
                }
                this.drawTable(_tbody, item, need_build);
            }
            count++;
        }

        // 선행 기술
        if (cond.m_tech_pk) {
            if (this.checkType(_m_cond_pk, _in_cast_pk, 'm_tech_pk')) {
                item['need_class'] = 'text_condition_yes';
            } else {
                item['need_class'] = 'text_condition_no';
                pass = false;
            }
            if (_tbody) {
                item['own_class'] = '';
                item['title'] = ns_i18n.t('technical');
                item['need'] = ns_i18n.t(`tech_title_${cond.m_tech_pk}`) + ' ' + ns_util.getLevelStr(cond.m_tech_level);
                item['own'] = ns_util.getLevelStr(ns_cs.d.tech[ns_cs.m.tech[cond.m_tech_pk].code].v);
                this.drawTable(_tbody, item);
            }
            count++;
        }

        // 필요 아이템
        if (cond.m_item_pk) {
            if (this.checkType(_m_cond_pk, _in_cast_pk, 'm_item_pk')) {
                item['need_class'] = 'text_condition_yes';
            } else {
                item['need_class'] = 'text_condition_no';
                pass = false;
            }

            if (_tbody) {
                item['own_class'] = '';
                item['title'] = ns_i18n.t('item');
                item['need'] = ns_cs.m.item[cond.m_item_pk].title;

                item['own'] = ns_i18n.t('none');
                if (ns_cs.d.item[cond.m_item_pk] && ns_cs.d.item[cond.m_item_pk].item_cnt > 0) {
                    item['own'] = ns_i18n.t('item_count', [ns_cs.d.item[cond.m_item_pk].item_cnt]);
                }
                this.drawTable(_tbody, item);
            }
            count++;
        }

        // 영지 건설 - 민병 - 하드코딩
        if (ns_util.math(_m_cond_pk).eq(100000)) {
            // 민병
            if (400 <= ns_cs.d.army.worker.v) {
                item['need_class'] = 'text_condition_yes';
            } else {
                item['need_class'] = 'text_condition_no';
                pass = false;
            }

            if (_tbody) {
                item['own_class'] = '';
                item['title'] = null; // system_text.cond_worker;
                item['need'] = 400;
                item['own'] = ns_util.numberFormat(ns_cs.d.army.worker.v);

                this.drawTable(_tbody, item);
            }
            count++;
        }

        // 필요자원 - 식량
        if (cond.build_food) {
            let need_food = false;
            if (this.checkType(_m_cond_pk, _in_cast_pk, 'build_food'))
            {
                item['need_class'] = 'text_condition_yes';
            } else {
                item['need_class'] = 'text_condition_no';
                pass = false;
                need_food = true;
            }

            if (_tbody) {
                item['develop_class'] = 'develop_build_food';
                item['own_class'] = 'ns_resource_food_curr';
                item['title'] = '<span class="resource_condition food"></span>';
                item['need'] = ns_util.numberFormat(cond.build_food);
                item['own'] = ns_util.numberFormat(ns_cs.getResourceInfo('food_curr'));
                if (need_food) {
                    item['button_id'] = 'need_resource_food';
                    item['button_function'] = function ()
                    {
                        let code = this['tag_id'].split('_').pop();
                        ns_dialog.setDataOpen('resource_manage', { type: code })
                    }
                }
                this.drawTable(_tbody, item, need_food);
            }
            count++;
        }

        // 필요자원 - 우마
        if (cond.build_horse) {
            let need_horse = false;
            if (this.checkType(_m_cond_pk, _in_cast_pk, 'build_horse')) {
                item['need_class'] = 'text_condition_yes';
            } else {
                item['need_class'] = 'text_condition_no';
                pass = false;
                need_horse = true;
            }

            if (_tbody) {
                item['develop_class'] = 'develop_build_horse';
                item['own_class'] = 'ns_resource_horse_curr';
                item['title'] = '<span class="resource_condition horse"></span>';
                item['need'] = ns_util.numberFormat(cond.build_horse);
                item['own'] = ns_util.numberFormat(ns_cs.getResourceInfo('horse_curr'));
                if (need_horse) {
                    item['button_id'] = 'need_resource_horse';
                    item['button_function'] = function ()
                    {
                        let code = this['tag_id'].split('_').pop();
                        ns_dialog.setDataOpen('resource_manage', { type: code })
                    }
                }
                this.drawTable(_tbody, item, need_horse);
            }
            count++;
        }

        // 필요자원 - 목재
        if (cond.build_lumber) {
            let need_lumber = false;
            if (this.checkType(_m_cond_pk, _in_cast_pk, 'build_lumber')) {
                item['need_class'] = 'text_condition_yes';
            } else {
                item['need_class'] = 'text_condition_no';
                pass = false;
                need_lumber = true;
            }

            if (_tbody) {
                item['develop_class'] = 'develop_build_lumber';
                item['own_class'] = 'ns_resource_lumber_curr';
                item['title'] = '<span class="resource_condition lumber"></span>';
                item['need'] = ns_util.numberFormat(cond.build_lumber);
                item['own'] = ns_util.numberFormat(ns_cs.getResourceInfo('lumber_curr'));
                if (need_lumber) {
                    item['button_id'] = 'need_resource_lumber';
                    item['button_function'] = function ()
                    {
                        let code = this['tag_id'].split('_').pop();
                        ns_dialog.setDataOpen('resource_manage', { type: code })
                    }
                }
                this.drawTable(_tbody, item, need_lumber);
            }
            count++;
        }

        // 필요자원 - 철강
        if (cond.build_iron) {
            let need_iron = false;
            if (this.checkType(_m_cond_pk, _in_cast_pk, 'build_iron')) {
                item['need_class'] = 'text_condition_yes';
            } else {
                item['need_class'] = 'text_condition_no';
                pass = false;
                need_iron = true;
            }

            if (_tbody) {
                item['develop_class'] = 'develop_build_iron';
                item['own_class'] = 'ns_resource_iron_curr';
                item['title'] = '<span class="resource_condition iron"></span>';
                item['need'] = ns_util.numberFormat(cond.build_iron);
                item['own'] = ns_util.numberFormat(ns_cs.getResourceInfo('iron_curr'));
                if (need_iron) {
                    item['button_id'] = 'need_resource_iron';
                    item['button_function'] = function ()
                    {
                        let code = this['tag_id'].split('_').pop();
                        ns_dialog.setDataOpen('resource_manage', { type: code })
                    }
                }
                this.drawTable(_tbody, item, need_iron);
            }
            count++;
        }

        // 필요자원 - 황금
        if (cond.build_gold) {
            let need_gold = false;
            if (this.checkType(_m_cond_pk, _in_cast_pk, 'build_gold')) {
                item['need_class'] = 'text_condition_yes';
            } else {
                item['need_class'] = 'text_condition_no';
                pass = false;
                need_gold = true;
            }

            if (_tbody) {
                item['develop_class'] = 'develop_build_gold';
                item['own_class'] = 'ns_resource_gold_curr';
                item['title'] = '<span class="resource_condition gold"></span>';
                item['need'] = ns_util.numberFormat(cond.build_gold);
                item['own'] = ns_util.numberFormat(ns_cs.d.terr.gold_curr.v);
                if (need_gold) {
                    item['button_id'] = 'need_resource_gold';
                    item['button_function'] = function ()
                    {
                        let code = this['tag_id'].split('_').pop();
                        ns_dialog.setDataOpen('resource_manage', { type: code })
                    }
                }
                this.drawTable(_tbody, item, need_gold);
            }
            count++;
        }

        // 필요자원 - 유휴주민
        if (cond.need_population) {
            let need_population = false;
            if (this.checkType(_m_cond_pk, _in_cast_pk, 'need_population')) {
                item['need_class'] = 'text_condition_yes';
            } else {
                item['need_class'] = 'text_condition_no';
                pass = false;
                need_population = true;
            }

            if (_tbody) {
                item['develop_class'] = 'develop_build_population';
                item['own_class'] = 'ns_need_population';
                item['title'] = '<span class="resource_condition population"></span>';
                item['need'] = ns_util.numberFormat(cond.need_population);
                item['own'] = ns_util.numberFormat(ns_cs.d.terr.population_idle.v);
                if (need_population) {
                    item['button_id'] = 'need_population';
                    item['button_function'] = function ()
                    {
                        ns_dialog.setDataOpen('item_quick_use', { type: 'population' });
                    }
                }
                this.drawTable(_tbody, item, need_population);
            }
            count++;
        }

        // 필요자원 - 성벽공간
        if (cond.need_vacancy) {
            if (this.checkType(_m_cond_pk, _in_cast_pk, 'need_vacancy')) {
                item['need_class'] = 'text_condition_yes';
            } else {
                item['need_class'] = 'text_condition_no';
                pass = false;
            }

            if (_tbody) {
                item['develop_class'] = 'develop_build_vacancy';
                item['own_class'] = 'ns_need_vacancy';
                item['title'] = ns_i18n.t('installation_space');
                item['need'] = ns_util.numberFormat(cond.need_vacancy);
                item['own'] = ns_util.numberFormat(parseInt(ns_cs.d.terr.wall_vacancy_max.v) - parseInt(ns_cs.d.terr.wall_vacancy_curr.v));
                this.drawTable(_tbody, item);
            }
            count++;
        }

        //  draw_list 에서는 개 수 리턴, 버튼 체크에서는 boolean 리턴
        return (_tbody) ? count : pass;
    }


    // 리스트 항목 그리기 - _m_cond_pk, _in_cast_pk, _tbody
    drawList (_m_cond_pk, _in_cast_pk, _tbody = null, _simplify = false)
    {
        return this.checkAll(_m_cond_pk, _in_cast_pk, _tbody, _simplify);
    }

    drawTable (_tbody, _item, _need_button = false)
    {
        let tr = document.createElement('tr');

        let col1 = document.createElement('td');
        col1.classList.add('col1');
        let col1_span = document.createElement('span');
        col1_span.innerHTML = _item['title'];
        col1.appendChild(col1_span);

        let col2 = document.createElement('td');
        col2.classList.add('col2');
        let col2_span = document.createElement('span');
        if (_item['develop_class'] && _item['develop_class'] !== '') {
            col2_span.classList.add('class', _item['develop_class']);
        }
        col2_span.classList.add('class', _item['need_class']);
        col2_span.innerHTML = (_need_button) ? `<span id="ns_button_${_item['button_id']}">` + _item['need'] + '</span>' : _item['need'];
        col2.appendChild(col2_span);

        let col3 = document.createElement('td');
        col3.classList.add('col3');
        let col3_span = document.createElement('span');
        if (_item['own_class'] && _item['own_class'] !== '') {
            col3_span.classList.add('class', _item['own_class']);
        }
        col3_span.classList.add('class', _item['need_class']);
        col3_span.innerHTML = _item['own'];
        col3.appendChild(col3_span);

        tr.appendChild(col1);
        tr.appendChild(col2);
        tr.appendChild(col3);

        _tbody.append(tr);

        // 선행 건물이 필요하다면
        if (_need_button === true) {
            ns_button.buttons['ns_button_' + _item['button_id']] = new nsButtonSet(_item['button_id'], 'button_small_2', 'A');
            ns_button.buttons['ns_button_' + _item['button_id']].mouseUp = _item['button_function'];
        }
    }
}
let ns_check_condition = new nsCheckCondition();