class nsHeroSelect
{
    // 영웅의 스탯이 가지는 모든 능력 효과
    getCapacityHeroAssign (_hero_pk, _m)
    {
        let pks = {};
        // 등용여부에 따른 소스 구분 (덱에 없는 영웅은 _m 변수에 데이터가 옮)
        let h = (_m) ? _m : ns_cs.d.hero[_hero_pk];
        // 전체 능력 효과와 비교
        for (let [k, d] of Object.entries(ns_cs.m.hero_assi)) {
            if (! ns_util.isNumeric(k)) {
                continue;
            }
            if ((d.stat_type === 'L' && ns_util.math(d.stat_step).eq(ns_util.math(h.leadership).div(10).integer)) ||
                (d.stat_type === 'M' && ns_util.math(d.stat_step).eq(ns_util.math(h.mil_force).div(10).integer)) ||
                (d.stat_type === 'I' && ns_util.math(d.stat_step).eq(ns_util.math(h.intellect).div(10).integer)) ||
                (d.stat_type === 'P' && ns_util.math(d.stat_step).eq(ns_util.math(h.politics).div(10).integer)) ||
                (d.stat_type === 'C' && ns_util.math(d.stat_step).eq(ns_util.math(h.charm).div(10).integer))) {
                pks[k] = true;
            }
        }
        return pks;
    }

    // 건물에 배속시 적용 가능한 능력 효과
    getAppliedBuildHeroAssign (_capacity_hero_assi_pks, _m_buil_pk)
    {
        let pks = {};
        for (let [k, d] of Object.entries(ns_cs.m.buil_hero_assi[_m_buil_pk])) {
            if (_capacity_hero_assi_pks[k] === true) {
                pks[k] = true;
            }
        }
        return pks;
    }

    // 명령에 배속시 적용 가능한 능력 효과 - (건설, 기술개발, 탐색, 초빙, 전투등등)
    getAppliedCmdHeroAssign (_capacity_hero_assi_pks, _m_cmd_pk)
    {
        let pks = {};
        if (ns_cs.m.cmd_hero_assi[_m_cmd_pk]) {
            for (let [k, d] of Object.entries(ns_cs.m.cmd_hero_assi[_m_cmd_pk])) {
                if (_capacity_hero_assi_pks[k] === true) {
                    pks[k] = true;
                }
            }
        }
        return pks;
    }

    // 영웅이 가지는 모든 기술 효과
    getCapacityHeroSkill (_hero_pk, _m)
    {
        let pks = [];
        // 등용여부에 따른 소스 구분 (덱에 없는 영웅은 _m 변수에 데이터가 옮)
        let h = (_m) ? _m : ns_cs.d.hero[_hero_pk];
        if (h.m_hero_skil_pk1 && h.main_slot_pk1 === h.slot_pk1) {
            pks.push(h.m_hero_skil_pk1);
        }
        if (h.m_hero_skil_pk2 && h.main_slot_pk2 === h.slot_pk1) {
            pks.push(h.m_hero_skil_pk2);
        }
        if (h.m_hero_skil_pk3 && h.main_slot_pk3 === h.slot_pk1) {
            pks.push(h.m_hero_skil_pk3);
        }
        if (h.m_hero_skil_pk4 && h.main_slot_pk4 === h.slot_pk1) {
            pks.push(h.m_hero_skil_pk4);
        }
        if (h.m_hero_skil_pk5 && h.main_slot_pk5 === h.slot_pk1) {
            pks.push(h.m_hero_skil_pk5);
        }
        if (h.m_hero_skil_pk6 && h.main_slot_pk6 === h.slot_pk1) {
            pks.push(h.m_hero_skil_pk6);
        }
        return pks;
    }

    // 건물에 배속시 적용 가능한 기술 효과
    getAppliedBuilHeroSkill (_capacity_hero_skil_pks, _m_buil_pk)
    {
        let pks = [];
        for (let [k, d] of Object.entries(ns_cs.m.buil_hero_skil[_m_buil_pk])) {
            if (_capacity_hero_skil_pks.includes(k)) {
                pks.push(k);
            }
        }
        return pks;
    }

    // 명령에 배속시 적용 가능한 기술 효과 (건설, 기술개발, 탐색, 초빙, 전투등등)
    getAppliedCmdHeroSkill (_capacity_hero_skill_pks, _m_cmd_pk)
    {
        let pks = [];
        if (ns_cs.m.cmd_hero_skil[_m_cmd_pk]) {
            for (let [k, d] of Object.entries(ns_cs.m.cmd_hero_skil[_m_cmd_pk])) {
                if (_capacity_hero_skill_pks.includes(k)) {
                    pks.push(k);
                }
            }
        }
        return pks;
    }

    // 영웅이 가지는 병과 특성
    getMilAptitudeArmy (_hero_pk)
    {
        let mil_aptitude = {};
        let d = ns_cs.d.hero[_hero_pk];
        let m_hero = ns_cs.m.hero[d.m_hero_pk];
        let m_hero_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];
        for (let [k, d] of Object.entries(m_hero_base)) {
            if (k.search(/^mil_aptitude/) !== -1) {
                mil_aptitude[k.split('_').pop()] = code_set.mil_aptitude[d];
            }
        }
        return mil_aptitude;
    }

    // 적용된 능력 효과의 설명 (배속이던 명령이던 한번에 하나)
    getAppliedAssignDesc (_applied_pks)
    {
        let str = '';
        for (let [k, d] of Object.entries(_applied_pks)) {
            if (str) {
                str += ', ';
            }
            str += '<span>' + ns_cs.m.hero_assi[k].description_effect + '</span>';
        }
        return str;
    }

    // 적용된 기술 효과의 명칭 (다중 적용 가능)
    getAppliedSkillTitles (_applied_pks)
    {
        let str = '';
        for (let [k, d] of Object.entries(_applied_pks)) {
            if (str) {
                str += ', ';
            }
            str += '<span>' + ns_cs.m.hero_skil[d].title + ' Lv.'+ ns_cs.m.hero_skil[d].rare + '</span>';
        }
        return str;
    }

    getAppliedSkillTitle (_pk)
    {
        if (!_pk || ! ns_cs.m.hero_skil[_pk]) {
            return '-';
        }
        let dummy = [_pk];
        return this.getAppliedSkillTitles(dummy);
    }

    // TODO - troop_order.js 에서 사용 하는데 뭔지 모르겠음.
    getAppliedSkillEffectValue (_applied_pks, _effect_type)
    {
        let type_value = 0;
        for (let [k, d] of Object.entries(_applied_pks)) {
            if (ns_cs.m.hero_skil[d].effect_type === _effect_type) {
                type_value += parseInt(ns_cs.m.hero_skil[d].effect_value, 10);
            }
        }
        return type_value;
    }
}

let ns_hero_select = new nsHeroSelect();