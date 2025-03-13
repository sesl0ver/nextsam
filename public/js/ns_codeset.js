const codeset = {
    resources: {
        server_name: {
            s1: 'luoyang',
            s2: 'chang_an',
        },
        lord_description: {
            1: 'lord_description_liu_bei',
            2: 'lord_description_cao_cao',
            3: 'lord_description_sun_quan',
            4: 'lord_description_yuan_shao',
            5: 'lord_description_dong_zhuo',
        },
        valley: {
            R: 'farm',
            L: 'reservoir',
            M: 'mine',
            F: 'forest',
            G: 'grassland',
            N: 'yellow_turban',
            T: 'territory',
            A: 'field',
            E: 'field',
            D: 'wasteland',
            NPC_SUPP: 'yellow_hideout',
            P: 'strategic_point',
            NPC_SUPP_EVENT: 'assembly_point'
        },
        none_counter_description: {
            C: 'none_counter_construct', // '건설중이거나 업그레이드 중인 건물이 없습니다.',
            T: 'none_counter_technique', // '개발 중인 기술이 없습니다.',
            H: 'none_counter_scout', // '진행 중인 탐색 및 초빙이 없습니다.',
            A: 'none_counter_army', // '진행 중인 훈련/치료가 없습니다.',
            F: 'none_counter_fort', // '설치 중인 방어시설이 없습니다.',
            X: 'none_counter_troop', // '출정 중인 아군 부대가 없습니다.',
            Y: 'none_counter_enemy', // '공격해오는 적 부대가 없습니다.',
        },
        relation: {
            F: 'friendship',
            H: 'hostile',
            N: 'neutrality'
        },
        ally_grade: {
            1:'alliance_captain',
            2:'alliance_vice_captain',
            3:'alliance_inspection',
            4:'alliance_executive',
            5:'alliance_member'
        },
        battle_type: {
            A: 'attack',
            D: 'defense'
        },
        resource: {
            F: 'food',
            L: 'lumber',
            H: 'horse',
            I: 'iron',
            G: 'gold'
        }
    },
    t: (key, code) => {
        // 텍스트 변환이 필요한 경우
        return ns_i18n.t(codeset.resources[key][code]);
    },
    c: (key, code) => {
        // 코드를 그대로 사용하는 경우
        return codeset.resources[key][code];
    }
};