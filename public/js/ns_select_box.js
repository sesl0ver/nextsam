class nsSelectBox
{
    constructor() {
        this.boxs = {
            hero_manage_sort:
                {
                    title: ns_i18n.t('select_box_hero_sort'), // 영웅정렬 선택
                    use_sort: false,	// 오름차순, 내림차순 정렬 사용여부 TODO UI 변경으로 사용안함
                    reverse_sort: ['name'],
                    val: null,
                    sort: null,
                    data:
                        {
                            rare: ns_i18n.t('hero_grade'), // 레어
                            name: ns_i18n.t('hero_name'), // 영웅명
                            leadership: ns_i18n.t('stats_leadership'), // 통솔
                            mil_force: ns_i18n.t('stats_mil_force'), // 무력
                            intellect: ns_i18n.t('stats_intellect'), // 지력
                            politics: ns_i18n.t('stats_politics'), // 정치
                            charm: ns_i18n.t('stats_charm'), // 매력
                            infantry: ns_i18n.t('infantry'), // 보병
                            spearman: ns_i18n.t('spearmen'), // 창병
                            pikeman: ns_i18n.t('pikeman'), // 극병
                            archer: ns_i18n.t('archer'), // 궁병
                            horseman: ns_i18n.t('cavalry'), // 기병
                            siege: ns_i18n.t('siege'), // 공성
                        },
                    func: () =>
                    {
                        ns_button.buttons.hero_manage_sort.obj.text(this.getText('hero_manage_sort'));
                        ns_dialog.dialogs.hero_manage.drawList();
                    }
                },
            hero_select_filter:
                {
                    title: ns_i18n.t('select_box_hero_sort'),
                    use_sort: false,
                    reverse_sort: [],
                    val: null,
                    sort: null,
                    data: {
                        level: ns_i18n.t('level'), // 레벨
                        rare_type: ns_i18n.t('grade'), // 등급
                        leadership: ns_i18n.t('stats_leadership'), // 통솔
                        mil_force: ns_i18n.t('stats_mil_force'), // 무력
                        intellect: ns_i18n.t('stats_intellect'), // 지력
                        politics: ns_i18n.t('stats_politics'), // 정치
                        charm: ns_i18n.t('stats_charm'), // 매력
                    },
                    func : () =>
                    {
                        ns_button.buttons.hero_select_filter.obj.text(this.getText('hero_select_filter'));
                        ns_hero.on_select_sort_stat_type = this.boxs.hero_select_filter.val;
                        ns_hero.deckReload();
                    }
                },
            build_Administration_comforting_filter:
                {
                    title: ns_i18n.t('select_box_comforting'), // 복지 정책 선택,
                    use_sort:false,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: [],
                    val:null,
                    sort:null,
                    data:
                        {
                            redress: ns_i18n.t('redress'), // 구휼
                            ritual: ns_i18n.t('ritual'), // 천제
                            prevention_disasters: ns_i18n.t('prevention_disasters'), // 재해예방
                        },
                    func: () =>
                    {
                        ns_button.buttons.build_Administration_comforting_filter.obj.text(this.getText('build_Administration_comforting_filter'));
                        ns_dialog.dialogs.build_Administration_comforting.drawSelectFilter();
                    }
                },
            build_Administration_requisition_filter:
                {
                    title: ns_i18n.t('select_box_requisition'), // 징발 항목 선택
                    use_sort: false, // 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: [],
                    val: null,
                    sort: null,
                    data:
                        {
                            gold: ns_i18n.t('resource_gold'),
                            food: ns_i18n.t('resource_food'),
                            horse: ns_i18n.t('resource_horse'),
                            lumber: ns_i18n.t('resource_lumber'),
                            iron: ns_i18n.t('resource_iron')
                        },
                    func: () =>
                    {
                        ns_button.buttons.build_Administration_requisition_filter.obj.text(this.getText('build_Administration_requisition_filter'));
                        ns_dialog.dialogs.build_Administration_requisition.drawSelectFilter();
                    }
                },
            build_ReceptionHall_hero_encounter_filter:
                {
                    title: ns_i18n.t('select_box_hero_encounter'), // 탐색 유형 선택
                    use_sort:false,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: [],
                    val:null,
                    sort:null,
                    data:
                        {
                            distance: ns_i18n.t('street_find'), // 거리탐문
                            in_castle: ns_i18n.t('castle_find'), // 내성탐색
                            territory: ns_i18n.t('territory_find'), // 영지탐색
                            world: ns_i18n.t('continent_find'), // 대륙탐색
                            walkabout: ns_i18n.t('walkabout'), // 민정시찰
                            around_world: ns_i18n.t('world_find'), // 주유천하
                        },
                    func: () =>
                    {
                        ns_button.buttons.build_ReceptionHall_hero_encounter_filter.obj.text(this.getText('build_ReceptionHall_hero_encounter_filter'));
                        ns_dialog.dialogs.build_ReceptionHall.drawSelectFilter();
                    }
                },
            hero_skill_manage_sort:
                {
                    title: ns_i18n.t('select_box_sort'), // 정렬 선택
                    use_sort: true,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: [],
                    val:null,
                    sort:null,
                    data:
                        {
                            title: ns_i18n.t('skill_title'), // 이름
                            rare: ns_i18n.t('skill_grade') // 레어도
                        },
                    func: () =>
                    {
                        ns_button.buttons.hero_skill_manage_sort.obj.text(this.getText('hero_skill_manage_sort'));
                        ns_dialog.dialogs.hero_skill_manage.drawTab();
                    }
                },
            ranking_lord_sort:
                {
                    title: ns_i18n.t('select_box_sort'), // 정렬 선택
                    use_sort:null,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: [],
                    val:null,
                    sort:null,
                    data:
                        {
                            power: ns_i18n.t('lord_power'), // 영향력
                            attack_point: ns_i18n.t('attack_point'), // 공격 포인트
                            defence_point: ns_i18n.t('defense_point'), // 방어 포인트
                            army_point: ns_i18n.t('army_point'), // 병력 포인트
                        },
                    func: () =>
                    {
                        ns_button.buttons.ranking_lord_sort.obj.text(this.getText('ranking_lord_sort'));
                        ns_dialog.dialogs.ranking.drawTab();
                    }
                },
            ranking_hero_sort:
                {
                    title: ns_i18n.t('select_box_sort'), // 정렬 선택
                    use_sort:null,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: [],
                    val:null,
                    sort:null,
                    data:
                        {
                            leadership: ns_i18n.t('stats_leadership'), // 통솔
                            mil_force: ns_i18n.t('stats_mil_force'), // 무력
                            intellect: ns_i18n.t('stats_intellect'), // 지력
                            politics: ns_i18n.t('stats_politics'), // 정치
                            charm: ns_i18n.t('stats_charm') // 매력
                        },
                    func: () =>
                    {
                        ns_button.buttons.ranking_hero_sort.obj.text(this.getText('ranking_hero_sort'));
                        ns_dialog.dialogs.ranking.drawTab();
                    }
                },
            hero_skill_manage_list_sort:
                {
                    title: ns_i18n.t('select_box_hero_sort'), // 영웅 정렬 선택
                    use_sort:true,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: ['name'],
                    val:null,
                    sort:null,
                    data:
                        {
                            rare: ns_i18n.t('hero_grade'), // 레어
                            name: ns_i18n.t('hero_name'), // 영웅명
                            leadership: ns_i18n.t('stats_leadership'), // 통솔
                            mil_force: ns_i18n.t('stats_mil_force'), // 무력
                            intellect: ns_i18n.t('stats_intellect'), // 지력
                            politics: ns_i18n.t('stats_politics'), // 정치
                            charm: ns_i18n.t('stats_charm'), // 매력
                            infantry: ns_i18n.t('infantry'), // 보병
                            spearman: ns_i18n.t('spearmen'), // 창병
                            pikeman: ns_i18n.t('pikeman'), // 극병
                            archer: ns_i18n.t('archer'), // 궁병
                            horseman: ns_i18n.t('cavalry'), // 기병
                            siege: ns_i18n.t('siege'), // 공성
                        },
                    func: () =>
                    {
                        ns_button.buttons.hero_skill_manage_list_sort.obj.text(this.getText('hero_skill_manage_list_sort'));
                        // ns_dialog.dialogs.hero_skill_manage_list.draw_tab();
                    }
                },
            hero_manage_territory: // TODO 사용하지 않지만 일단 남겨둠.
                {
                    title:'영지 선택',
                    use_sort: false,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: [],
                    val:'all',
                    sort:null,
                    data:{
                        all: '전체보기'
                    },
                    func: () =>
                    {
                        ns_button.buttons.hero_manage_territory.obj.text(this.getText('hero_manage_territory'));
                        ns_dialog.dialogs.hero_manage.drawList();
                    }
                },
            alliance_change_level:
                {
                    title: ns_i18n.t('select_box_alliance_level'), // 동맹원 등급 선택
                    use_sort: false,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: [],
                    val: null,
                    sort: null,
                    data: {
                        1: ns_i18n.t('alliance_captain'), // 맹주
                        2: ns_i18n.t('alliance_vice_captain'), // 부맹주
                        3: ns_i18n.t('alliance_inspection'), // 감찰
                        4: ns_i18n.t('alliance_executive'), // 임원
                        5: ns_i18n.t('alliance_member'), // 동맹원
                    },
                    func: () =>
                    {
                        // ns_button.buttons.alliance_diplomacy_sort.obj.text(this.getText('alliance_diplomacy_sort'));
                        // ns_dialog.dialogs.alliance_diplomacy.proc_diplomacy_list();
                        let select_level = this.get('alliance_change_level').val;
                        ns_dialog.dialogs.alliance_manage_grade.changeGrade(select_level);
                    }
                },
            setting_language_change:
                {
                    title: 'Language',
                    use_sort: false,
                    reverse_sort: [],
                    val: null,
                    sort: null,
                    data: { ko: 'Language: 한국어', en: 'Language: English', jp: 'Language: 日本語' },
                    func: () =>
                    {
                        ns_button.buttons.setting_language_change.obj.text(this.getText('setting_language_change'));
                    }
                },
            connect_language_change:
                {
                    title: 'Language',
                    use_sort: false,
                    reverse_sort: [],
                    val: null,
                    sort: null,
                    data: { ko: 'Language: 한국어', en: 'Language: English', jp: 'Language: 日本語' },
                    func: () =>
                    {
                        let lang = this.get('connect_language_change').val;
                        ns_button.buttons.connect_language_change.obj.text(this.getText('connect_language_change'));
                        ns_i18n.setLang(lang);
                        if (lang === 'none') {
                            return;
                        }
                        ns_dialog.dialogs.connect.drawLocale();
                    }
                },
            server_select_language_change:
                {
                    title: 'Language',
                    use_sort: false,
                    reverse_sort: [],
                    val: null,
                    sort: null,
                    data: { ko: 'Language: 한국어', en: 'Language: English', jp: 'Language: 日本語' },
                    func: () =>
                    {
                        let lang = this.get('server_select_language_change').val;
                        ns_button.buttons.server_select_language_change.obj.text(this.getText('server_select_language_change'));
                        ns_i18n.setLang(lang);
                        if (lang === 'none') {
                            return;
                        }
                        ns_dialog.dialogs.server_select.drawLocale();
                    }
                }
        }
    }

    initVal (_name)
    {
        this.boxs[_name].val = null;
    }

    set (_name, _val, _sort)
    {
        let box = this.boxs[_name];
        if (! box || ! box.data[_val]) {
            return false;
        }
        box.val = _val;
        if (_sort) {
            box.sort = _sort;
        }
    }

    get (_name)
    {
        let box = this.boxs[_name];
        if (! box) {
            return false;
        }
        return {
            val: box.val,
            sort: box.sort,
            text: this.getText(_name)
        };
    }

    getText (_name)
    {
        let box = this.boxs[_name];
        if (! box) {
            return false;
        }
        let text = box.data[box.val];
        if (box.use_sort) {
            text += (box.sort === 'asc') ? '▲' : '▼';
        }
        return text;
    }
}

let ns_select_box = new nsSelectBox();

/* 코드 백업
            hero_manage_combination_selector_sort:
                {
                    title: ns_i18n.t('select_box_hero_sort'), // 영웅정렬 선택
                    use_sort:true,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: ['name'],
                    val:null,
                    sort:null,
                    data: this.boxs.hero_manage_sort.data,
                    func: () =>
                    {
                        ns_button.buttons.hero_manage_combination_selector_sort.obj.text(this.getText('hero_manage_combination_selector_sort'));
                        // ns_dialog.dialogs.hero_manage_combination_selector.draw_list();
                    }
                },
            troop_order_cmd_type_filter:
                {
                    title: ns_i18n.t('select_box_command'), // 명령 선택
                    use_sort:false,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: [],
                    val:null,
                    sort:null,
                    data:
                        {
                            A: ns_i18n.t('attack'), // 공격
                            S: ns_i18n.t('reconnaissance'), // 정찰
                            T: ns_i18n.t('transport'), // 수송
                            R: ns_i18n.t('support'), // 지원
                            P: ns_i18n.t('supply'), // 보급
                        },
                    func: () =>
                    {
                        ns_button.buttons.troop_order_cmd_type_filter.obj.text(this.getText('troop_order_cmd_type_filter'));
                        // ns_dialog.dialogs.troop_order.draw();
                    }
                },
            hero_manage_combination_skill_sort:
                {
                    title: ns_i18n.t('select_box_sort'), // 정렬 선택
                    use_sort:true,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: [],
                    val:null,
                    sort:null,
                    data:
                        {
                            rare:'레벨',
                            use_slot:'필요슬롯'
                        },
                    func: () =>
                    {
                        ns_button.buttons.hero_manage_combination_skill_sort.obj.text(this.getText('hero_manage_combination_skill_sort'));
                        ns_dialog.dialogs.hero_manage_combination.drawSkillList();
                    }
                },
            inquiry_type_select:
                {
                    title:'문의유형',
                    use_sort:false,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: [],
                    val:null,
                    sort:null,
                    data:
                        {
                            game:'게임문의',
                            connect:'접속문의',
                            account:'계정문의',
                            payment:'결제문의',
                            bug:'버그신고',
                            hacking:'사기/해킹',
                            abuse:'욕설/비매너',
                            etc:'기타문의'
                        },
                    func: () =>
                    {
                        ns_button.buttons.inquiry_type_select.obj.text(this.getText('inquiry_type_select'));
                    }
                },
            alliance_lord_sort:
                {
                    title: ns_i18n.t('select_box_alliance_sort'), // 동맹 정렬 선택
                    use_sort:true,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: ['lord_name'],
                    val:null,
                    sort:null,
                    data:
                        {
                            power: ns_i18n.t('lord_power'), // 영향력
                            lord_name: ns_i18n.t('lord_name'), // 군주명
                            level: ns_i18n.t('alliance_grade'), // 등급
                            position_cnt: ns_i18n.t('territory') // 영지
                        },
                    func: () =>
                    {
                        ns_button.buttons.alliance_lord_sort.obj.text(this.getText('alliance_lord_sort'));
                        // ns_dialog.dialogs.alliance.draw_list();
                    }
                },
            alliance_army_sort:
                {
                    title:'동맹정렬 선택',
                    use_sort:true,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: [],
                    val:null,
                    sort:null,
                    data:
                        {
                            army_point:'병력포인트',
                            attack_point:'공격포인트',
                            defence_point:'수비포인트'
                        },
                    func: () =>
                    {
                        ns_button.buttons.alliance_army_sort.obj.text(this.getText('alliance_army_sort'));
                        ns_dialog.dialogs.alliance.draw_list();
                    }
                },
            letter_receiver_alliance_sort:
                {
                    title: ns_i18n.t('동맹 정렬 선택'), // 동맹정렬 선택
                    use_sort:true,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: ['lord_name'],
                    val:null,
                    sort:null,
                    data:
                        {
                            power: ns_i18n.t('lord_power'), // 영향력
                            lord_name: ns_i18n.t('lord_name'), // '군주명',
                            level: ns_i18n.t('alliance_grade'), // '등급',
                            // position_cnt: ns_i18n.t('territory') // '영지'
                        },
                    func: () =>
                    {
                        ns_button.buttons.letter_receiver_alliance_sort.obj.text(this.getText('letter_receiver_alliance_sort'));
                        // ns_dialog.dialogs.letter_receiver_alliance.draw_list();
                    }
                },
            alliance_request_sort:
                {
                    title: ns_i18n.t('select_box_alliance_request_sort'), // 동맹신청정렬 선택
                    use_sort:true,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: ['lord_name'],
                    val:null,
                    sort:null,
                    data:
                        {
                            request_dt: ns_i18n.t('request_date'), // 신청날짜
                            power: ns_i18n.t('request_date'), // 영향력
                            lord_name:'군주명',
                            level:'등급',
                            position_cnt:'영지'
                        },
                    func: () =>
                    {
                        ns_button.buttons.alliance_request_sort.obj.text(this.getText('alliance_request_sort'));
                        // ns_dialog.dialogs.alliance.draw_list();
                    }
                },
            alliance_gift_send_sort:
                {
                    title:'동맹신청정렬 선택',
                    use_sort:true,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: ['lord_name'],
                    val:null,
                    sort:null,
                    data:
                        {
                            power:'영향력',
                            level:'등급',
                            lord_name:'군주명',
                            army_point:'병력포인트'
                        },
                    func: () =>
                    {
                        ns_button.buttons.alliance_gift_send_sort.obj.text(this.getText('alliance_gift_send_sort'));
                        ns_dialog.dialogs.alliance.draw_list();
                    }
                },
            raid_list_sort:
                {
                    title:'황건섬멸정렬 선택',
                    use_sort:true,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: [],
                    val:null,
                    sort:null,
                    data:
                        {
                            last_up_dt:'최근변경',
                            regist_dt:'잔여시간'	,
                            ramain_army:'잔여병력',
                            target_level:'요새등급'
                        },
                    func: () =>
                    {
                        ns_button.buttons.raid_list_sort.obj.text(this.getText('raid_list_sort'));
                        ns_dialog.dialogs.raid_list.draw_tab();
                    }
                },
            alliance_diplomacy_sort:
                {
                    title:'외교 타입 선택',
                    use_sort:false,	// 오름차순, 내림차순 정렬 사용여부
                    reverse_sort: [],
                    val:null,
                    sort:null,
                    data:
                        {
                            cont_Friendship:'우호',
                            cont_Hostile:'적대'	,
                            cont_Neutrality:'중립'
                        },
                    func: () =>
                    {
                        ns_button.buttons.alliance_diplomacy_sort.obj.text(this.getText('alliance_diplomacy_sort'));
                        ns_dialog.dialogs.alliance_diplomacy.proc_diplomacy_list();
                    }
                },

 */