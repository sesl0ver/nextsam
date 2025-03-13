// codeset
let code_set = {};
code_set.lord_name = ['', ns_i18n.t('liu_bei'), ns_i18n.t('cao_cao'), ns_i18n.t('sun_quan'), ns_i18n.t('yuan_shao'), ns_i18n.t('dong_zhuo')];
code_set.forces_lord_name = {
    'UB': ns_i18n.t('liu_bei'),
    'JJ': ns_i18n.t('cao_cao'),
    'SK': ns_i18n.t('sun_quan'),
    'WS': ns_i18n.t('yuan_shao'),
    'DT': ns_i18n.t('dong_zhuo'),
    'PC': ns_i18n.t('yellow_turban'), // 황건적
    'NN': ns_i18n.t('other'), // 기타
}
code_set.coord_desc = {
    suppress: ns_i18n.t('yellow_hideout'), // 황건적 거점
    territory: ns_i18n.t('territory'), // 영지
    valley: ns_i18n.t('resource_valley') // 자원지
};
code_set.wall_desc = {
    open: ns_i18n.t('gate_open'), // 성문 개방
    close: ns_i18n.t('gate_close') // 성문 폐쇄
};
code_set.winner_desc = {
    att: ns_i18n.t('attack_side'), // 공격측
    def: ns_i18n.t('defense_side') // 방어측
};
code_set.forces = {
    DT: ns_i18n.t('dong_zhuo'), // 동탁
    JJ: ns_i18n.t('cao_cao'), // 조조
    NN: ns_i18n.t('none'), // 없음
    PC: ns_i18n.t('yellow_turban'), // 황건적
    SK: ns_i18n.t('sun_quan'), // 손권
    UB: ns_i18n.t('liu_bei'), // 유비
    WS: ns_i18n.t('yuan_shao') // 원소
};
code_set.hero_type = {
    C: ns_i18n.t('civil_officer'), // 문관
    F: ns_i18n.t('female'), // 여성
    K: ns_i18n.t('lord'), // 군주
    M: ns_i18n.t('military_officer') // military
};
code_set.rare_type = {
    1: 'Common',
    2: 'Normal',
    3: 'Special',
    4: 'Rare',
    5: 'Elite',
    6: 'Legend',
    7: 'Unique'
};
code_set.build_status = {
    N: ns_i18n.t('normal_status'), // 평상
    C: ns_i18n.t('construction_underway'), // 건설 중
    U: ns_i18n.t('construction_upgrading'), // 업그레이드 중
    D: ns_i18n.t('construction_downgrading') // 다운그레이드 중
};
code_set.valley_type = ns_i18n.t('resource_valley');
code_set.valley_type_prod = {
    M: ns_i18n.t('resource_iron'), // 철강
    F: ns_i18n.t('resource_lumber'), // 목재
    R: `${ns_i18n.t('resource_food')},${ns_i18n.t('resource_horse')}`, // 식량,우마
    G: `${ns_i18n.t('resource_food')},${ns_i18n.t('resource_horse')}`, // 식량,우마
    L: `${ns_i18n.t('resource_food')},${ns_i18n.t('resource_horse')}`, // 식량,우마
    E: ns_i18n.t('none'), // 없음
    A: ns_i18n.t('none') // 없음
};
code_set.hero_status = {
    C: ns_i18n.t('capture'), // 포획
    S: ns_i18n.t('surrender'), // 투항
    V: ns_i18n.t('standby'), // 대기
    G: ns_i18n.t('recruitment'), // 영입
    A: ns_i18n.t('appoint'), // 등용
    R: ns_i18n.t('abandon'), // 방출
};
code_set.hero_status_cmd = {
    I: ns_i18n.t('standby'), // 대기
    A: ns_i18n.t('assign'), // 배속
    C: ns_i18n.t('command'), // 명령
    P: ns_i18n.t('enhance'), // 강화
    S: ns_i18n.t('sabotage'), // 태업
}; // '무능',해임'?
code_set.hero_cmd_type = {
    None: ns_i18n.t('none'), // '없음',
    Const: ns_i18n.t('construction'), // '건설',
    Encou: ns_i18n.t('exploration'), // '탐색',
    Invit: ns_i18n.t('invitation'), // '초빙',
    Techn: ns_i18n.t('development'), // '개발',
    Scout: ns_i18n.t('reconnaissance'), // '정찰',
    Trans: ns_i18n.t('transport'), // '수송',
    Reinf: ns_i18n.t('support'), // '지원',
    Attac: ns_i18n.t('attack'), // '공격',
    Preva: ns_i18n.t('supply'), // '보급',
    Camp: ns_i18n.t('deployed'), // '주둔',
    Recal: ns_i18n.t('withdrawal'), // '회군',
    Treat: ns_i18n.t('treatment') // '치료'
};
code_set.hero_stat_type = {
    L: 'leadership',
    M: 'mil_force',
    I: 'intellect',
    P: 'politics',
    C: 'charm'
};
/*code_set.counter = {
    C: '건설 진행 현황',
    T: '기술 개발 진행 현황',
    H: '탐색/초빙 진행 현황',
    A: '부대 훈련 현황',
    F: '방어 시설 설치 현황',
    X: '아군 부대 이동 현황',
    Y: '적 부대 진군 현황'
};*/
code_set.troop_status = {
    M: ns_i18n.t('going_to_war'), // 출진
    B: ns_i18n.t('battle'), // 전투
    C: ns_i18n.t('deployed'), // 주둔
    R: ns_i18n.t('withdrawal'), // 회군
    W: ns_i18n.t('cancel') // 취소
};
code_set.troop_cmd_type = {
    T: ns_i18n.t('transport'), // 수송
    R: ns_i18n.t('support'), // 지원
    P: ns_i18n.t('supply'), // 보급
    S: ns_i18n.t('reconnaissance'), // 정찰,
    A:  ns_i18n.t('attack') // 공격
};
code_set.troop_long_cmd_type = {
    C: 'const',
    S: 'scout',
    T: 'trans',
    R: 'reinf',
    A: 'attac',
    P: 'preva'
};
code_set.troop_hero = {
    captain: ns_i18n.t('troop_captain'), // 주장
    director: ns_i18n.t('troop_director'), // 부장
    staff: ns_i18n.t('troop_staff') // 참모
};
code_set.encount_type = {
    distance: ns_i18n.t('street_find'), // 거리탐문
    in_castle: ns_i18n.t('castle_find'), // 내성탐색
    territory: ns_i18n.t('territory_find'), // 영지탐색
    world: ns_i18n.t('continent_find'), // 대륙탐색
    walkabout: ns_i18n.t('walkabout'), // 민정시찰
    around_world: ns_i18n.t('world_find'), // 주유천하
};
code_set.encount_type_costtime = {
    distance: ns_i18n.t('street_find_time'), // 20분
    in_castle: ns_i18n.t('castle_find_time'), // 1시간
    territory: ns_i18n.t('territory_find_time'), // 2시간
    world: ns_i18n.t('continent_find_time'), // 4시간
    walkabout: ns_i18n.t('walkabout_time'), // 6시간
    around_world: ns_i18n.t('world_find_time'), // 12시간
};
/*code_set.medical_hero_status_health = {
    N:'정상',
    W:'경상',
    E:'중상',
    F:'치명상'
};*/
/*code_set.reso_unit = {
    food:'석',
    horse:'필',
    lumber:'재',
    iron:'근',
    gold:'냥'
};*/
/*code_set.fort_unit = {
    trap:'개',
    abatis:'채',
    tower:'채'
};*/
code_set.army_unit = { // 직접 사용안함
    worker:'명',
    infantry:'명',
    pikeman:'명',
    scout:'명',
    spearman:'명',
    armed_infantry:'명',
    archer:'명',
    horseman:'기',
    armed_horseman:'기',
    transporter:'대',
    bowman:'명',
    battering_ram:'대',
    catapult:'대',
    adv_catapult:'대'
};
code_set.army_code = Object.keys(code_set.army_unit);
/*code_set.army_pk_code = {
    worker:'명',
    infantry:'명',
    pikeman:'명',
    scout:'명',
    spearman:'명',
    armed_infantry:'명',
    archer:'명',
    horseman:'기',
    armed_horseman:'기',
    transporter:'대',
    bowman:'명',
    battering_ram:'대',
    catapult:'대',
    adv_catapult:'대'
};*/
code_set.army_category = {
    infantry: ['worker', 'infantry', 'scout', 'armed_infantry'],
    pikeman: ['pikeman'],
    spearman: ['spearman'],
    archer: ['archer', 'bowman'],
    horseman: ['horseman', 'armed_horseman', 'transporter'],
    siege: ['battering_ram', 'catapult', 'adv_catapult']
};
code_set.mil_aptitude = {
    S: 20,
    A: 15,
    B: 10,
    C: 5,
    D: 0
};
code_set.mil_aptitude_value = {
    20: 'S',
    15: 'A',
    10: 'B',
    5: 'C',
    0: 'D'
};
code_set.pk_cmd = {
    PK_CMD_TROOP_CAPTAIN: 140005,
    PK_CMD_TROOP_DIRECTOR: 140006,
    PK_CMD_TROOP_STAFF: 140007,
    TROOP_DEFENCE_CAPTAIN: 140009
};
code_set.world = {
    state: {
        name00: '병주',
        name01: '기주',
        name02: '유주',
        name03: '사예주',
        name04: '예주/연주',
        name05: '서주/청주',
        name06: '익주',
        name07: '형주',
        name08: '양주',
    }
};
/*code_set.lord_level = {
    '1' : '의용장',
    '2' : '태사승',
    '3' : '현장',
    '4' : '현령',
    '5' : '대현령',
    '6' : '태수',
    '7' : '구향',
    '8' : '대장군',
    '9' : '삼공',
    '10' : '왕'
};*/

//------- 2012.05 ~ code_set START
/*code_set.hero_manage = {
    all: '현황',
    visit: '남은시간',
    guest: '소속영지'
};*/
code_set.hero_enchant = {
    leadership: ns_i18n.t('stats_leadership'), // 통솔
    mil_force: ns_i18n.t('stats_mil_force'), // 무력
    intellect: ns_i18n.t('stats_intellect'), // 지력
    politics: ns_i18n.t('stats_politics'), // 정치
    charm: ns_i18n.t('stats_charm') // 매력
};
//------- 2012.05 ~ code_set END
/*code_set.alliance_troop = {
    A: '공격',
    D: '방어',
    S_A: '정찰',
    S_D: '정찰',
    R: '지원',
    T: '수송',
    P: '보급'
};*/
code_set.position_npc_point_list = [
    '27x28', '80x29', '134x29', '188x29', '243x29', '296x29', '350x29', '404x29', '459x28',
    '27x83', '81x83', '134x83', '189x82', '242x83', '297x83', '351x82', '404x83', '459x83',
    '27x137', '80x137', '135x137', '189x137', '243x137', '297x136', '351x137', '405x136', '459x136',
    '26x191', '80x191', '135x191', '189x191', '243x191', '297x191', '351x191', '405x191', '459x191',
    '27x245', '81x244', '135x245', '189x245', '243x245', '297x245', '351x245', '405x245', '459x245',
    '27x298', '80x299', '135x298', '189x299', '243x299', '297x299', '351x299', '405x299', '459x299',
    '27x353', '81x353', '135x353', '189x353', '243x353', '297x353', '351x353', '405x353', '459x353',
    '27x407', '81x407', '135x407', '189x407', '243x407', '297x407', '351x407', '405x407', '459x407',
    '27x461', '81x461', '135x461', '189x461', '243x461', '297x461', '351x461', '405x461', '459x461',
];
code_set.position_name = {
    wasteland: ns_i18n.t('wasteland'), // '불모지',
    field: ns_i18n.t('field'), // '평지',
    farm: ns_i18n.t('farm'), // '농경지',
    grassland: ns_i18n.t('grassland'), // '초원',
    reservoir: ns_i18n.t('reservoir'), // '저수지',
    mine: ns_i18n.t('mine'), // '광산',
    yellow_turban: ns_i18n.t('yellow_turban'), // '황건적',
    territory: ns_i18n.t('territory'), // '영지',
    strategic_point: ns_i18n.t('strategic_point'), // '요충지',
    hideout: ns_i18n.t('hideout'), // '황건적 거점', // 거점은 황건적만
    assembly_point: ns_i18n.t('assembly_point'), // '황건적 집결지', // 집결지는 황건적만
    forest: ns_i18n.t('forest'), // '산림'
}

code_set.troop_cmd_type_text = {
    transport: ns_i18n.t('transport'), // 수송
    support: ns_i18n.t('support'), // 지원
    supply: ns_i18n.t('supply'), // 보급
    scout: ns_i18n.t('reconnaissance'), // 정찰
    attack: ns_i18n.t('attack') // 공격
};

code_set.trend = {
    U: ns_i18n.t('growing'), // 증가 중
    S: ns_i18n.t('stable'), // 안정
    D: ns_i18n.t('declining') // 감소 중
};

code_set.troop_status_text = {
    dispatch: ns_i18n.t('going_to_war'), // '출진',
    battle: ns_i18n.t('battle'), // '전투',
    camp: ns_i18n.t('deployed'), // '주둔',
    return: ns_i18n.t('withdrawal'), // '회군',
    withdraw: ns_i18n.t('cancel'), // '취소'
};

code_set.chat_room = {
    public: ns_i18n.t('chat_public'), // 전체
    alliance: ns_i18n.t('chat_alliance'), // 동맹
    alert: ns_i18n.t('chat_alert'), // 알림
    notification: ns_i18n.t('chat_notification'), // 알림
    notice: ns_i18n.t('chat_notice'), // 공지
}

code_set.cmd_description = {
    Const: ns_i18n.t('construction'), // '건설',
    Encou: ns_i18n.t('exploration'), // '탐색',
    Invit: ns_i18n.t('invitation'), // '초빙',
    Techn: ns_i18n.t('research'), // '연구',
    Scout: ns_i18n.t('reconnaissance'), // '정찰',
    Trans: ns_i18n.t('transport'), // '수송',
    Reinf: ns_i18n.t('support'), // '지원',
    Attac: ns_i18n.t('attack'), // '공격',
    Preva: ns_i18n.t('supply'), // '보급',
    Camp: ns_i18n.t('deployed'), // '주둔',
    Recal: ns_i18n.t('withdrawal'), // '회군',
    None: ns_i18n.t('none'), // '없음'
}

// 보고서 텍스트 정리
code_set.report = {
    hero_bid_success: {
        subject: ns_i18n.t('report_hero_bid_success_subject'), // 영웅 영입 입찰 결과 보고
        summary: ns_i18n.t('report_hero_bid_success_summary'), // $1 영입에 성공 하였습니다.
    },
    hero_bid_fail: {
        subject: ns_i18n.t('report_hero_bid_fail_subject'), // 영웅 영입 입찰 결과 보고
        summary: ns_i18n.t('report_hero_bid_fail_summary'), // $1 영입에 실패 하였습니다.
    },
    army_loss: {
        subject: ns_i18n.t('report_army_loss_subject'), // 반란 진압 보고
        summary: ns_i18n.t('report_army_loss_summary'), // 성에서 불만을 품은 일부 병사들이 반란을 일으켰습니다. 지나치게 많은 병력이 영지에 주둔한게 문제였던 것 같습니다. 반란은 곧 진압되었지만 이 과정에서 일부 병사들이 죽임을 당했습니다.
    },
    injury_army_trans: {
        subject: ns_i18n.t('report_injury_army_trans_subject'), // 부상병 이송 완료 보고
        summary: ns_i18n.t('report_injury_army_trans_summary'), // $1에서 발생한 전투에서 발생한 부상병이 $2에 있는 의료원으로 이송 완료되었습니다. 전투로 발생한 부상병들은 의료원에서 치료를 해야지만 출정이 가능하며, 의료원의 부상병 수용 가능수를 초과할 경우 초과된 부상병은 자동으로 소멸됩니다.
    },
    hero_strike: {
        subject: ns_i18n.t('report_hero_strike_subject'), // 태업 영웅 발생 보고
        summary: ns_i18n.t('report_hero_strike_summary'), // 충성도가 0 이 되어 영웅 $1명이 태업 상태로 전환되었습니다. 태업 상태에서는 명령을 내릴 수 없으니 우선 해당 영웅에게 포상을 통해 충성도를 상승시켜 주십시오.
    },
    hero_skill_slot_expand: {
        subject: ns_i18n.t('report_hero_skill_slot_expand_subject'), // 영웅 기술 슬롯 오픈 보고
        summary: ns_i18n.t('report_hero_skill_slot_expand_summary'), // 영웅의 경험치가 누적되어 $1의 기술 슬롯이 추가로 오픈되었습니다. 대기중인 영웅은 기술관리 창에서 신규 기술을 장착할 수 있습니다.
    },
    hero_trade_sale_success: {
        subject: ns_i18n.t('report_hero_trade_sale_success_subject'), // 영웅 판매 결과 보고
        summary: ns_i18n.t('report_hero_trade_sale_success_summary'), // $1 판매에 성공하였습니다.
    },
    hero_trade_sale_fail: {
        subject: ns_i18n.t('report_hero_trade_sale_fail_subject'), // 영웅 판매 결과 보고
        summary: ns_i18n.t('report_hero_trade_sale_fail_summary'), // $1 판매에 실패하였습니다.
    },
    hero_trade_bid_success: {
        subject: ns_i18n.t('report_hero_trade_bid_success_subject'), // 영웅 거래 입찰 결과 보고
        summary: ns_i18n.t('report_hero_trade_bid_success_summary'), // $1 영입에 성공하였습니다.
    },
    hero_trade_bid_fail: {
        subject: ns_i18n.t('report_hero_trade_bid_success_subject'), // 영웅 거래 입찰 결과 보고
        summary: ns_i18n.t('report_hero_trade_bid_success_summary'), // $1 영입에 실패하였습니다.
    },
    hero_treatment_finish: {
        subject: ns_i18n.t('report_hero_treatment_finish_subject'), // 영웅 치료 완료 보고
        summary: ns_i18n.t('report_hero_treatment_finish_summary'), // $1의 부상이 완치되어 명령 대기 중 입니다.
    },
    shipping_sale: {
        subject: ns_i18n.t('report_shipping_sale_subject'), // 무역장 물품의 판매 보고
        summary: ns_i18n.t('report_shipping_sale_summary'), // 거래 하신 물품이 판매되었습니다. 수수료 10%를 제외한 금액이 입금되었습니다.
    },
    shipping_finish: {
        subject: ns_i18n.t('report_shipping_finish_subject'), // 무역장 물품의 구매 보고
        summary: ns_i18n.t('report_shipping_finish_summary'), // 무역장에서 구매 하신 물품의 배송이 완료되었습니다.
    },
    return_finish_1: {
        subject: ns_i18n.t('report_return_finish_1_subject'), // 회군 명령에 의한 복귀
        summary: ns_i18n.t('report_return_finish_1_summary'), // $1에서 $2로 부대가 복귀 하였습니다.
    },
    return_finish_2: {
        subject: ns_i18n.t('report_return_finish_2_subject'), // 주둔 부대의 자동 복귀
        summary: ns_i18n.t('report_return_finish_2_summary'), // $1에서 $2로 부대가 복귀 하였습니다.
    },
    return_finish_3: {
        subject: ns_i18n.t('report_return_finish_3_subject'), // 주둔 부대의 복귀
        summary: ns_i18n.t('report_return_finish_3_summary'), // $1에서 $2로 부대가 복귀 하였습니다.
    },
    return_finish_4: {
        subject: ns_i18n.t('report_return_finish_4_subject'), // 수송 후 복귀
        summary: ns_i18n.t('report_return_finish_4_summary'), // $1에서 $2로 부대가 복귀 하였습니다.
    },
    return_finish_5: {
        subject: ns_i18n.t('report_return_finish_5_subject'), // 지원 후 복귀
        summary: ns_i18n.t('report_return_finish_5_summary'), // $1에서 $2로 부대가 복귀 하였습니다.
    },
    return_finish_6: {
        subject: ns_i18n.t('report_return_finish_6_subject'), // 보급 후 복귀
        summary: ns_i18n.t('report_return_finish_6_summary'), // $1에서 $2로 부대가 복귀 하였습니다.
    },
    return_finish_7: {
        subject: ns_i18n.t('report_return_finish_7_subject'), // 정찰 후 복귀
        summary: ns_i18n.t('report_return_finish_7_summary'), // $1에서 $2로 부대가 복귀 하였습니다.
    },
    return_finish_8: {
        subject: ns_i18n.t('report_return_finish_8_subject'), // 전투 후 복귀
        summary: ns_i18n.t('report_return_finish_8_summary'), // $1에서 $2로 부대가 복귀 하였습니다.
    },
    ally_troop_recall: {
        subject: ns_i18n.t('report_ally_troop_recall_subject'), // 동맹국 지원군의 복귀
        summary: ns_i18n.t('report_ally_troop_recall_summary'), // 동맹국의 지원군이 소속 영지로 복귀하였습니다.
    },
    battle_attack_victory: {
        subject: ns_i18n.t('report_battle_attack_victory_subject'), // 원정 전투 승리 보고
        summary: ns_i18n.t('report_battle_attack_victory_summary'), // 원정 전투에서 승리하였습니다.
    },
    battle_attack_defeat: {
        subject: ns_i18n.t('report_battle_attack_defeat_subject'), // 원정 전투 패배 보고
        summary: ns_i18n.t('report_battle_attack_defeat_summary'), // 원정 전투에서 패배하였습니다.
    },
    battle_defence_victory: {
        subject: ns_i18n.t('report_battle_defence_victory_subject'), // 방어 전투 승리 보고
        summary: ns_i18n.t('report_battle_defence_victory_summary'), // 방어 전투에서 승리하였습니다.
    },
    battle_defence_defeat: {
        subject: ns_i18n.t('report_battle_defence_defeat_subject'), // 방어 전투 패배 보고
        summary: ns_i18n.t('report_battle_defence_defeat_summary'), // 방어 전투에서 패배하였습니다.
    },
    enemy_march: {
        subject: ns_i18n.t('report_enemy_march_subject'), // 공격해오는 적 정보 보고
        summary: ns_i18n.t('report_enemy_march_summary'), // 적이 아군 영지 $1를 향해 진군하고 있습니다.
    },
    trans_finish: {
        subject: ns_i18n.t('report_trans_finish_subject'), // 수송 완료 보고
        summary: ns_i18n.t('report_trans_finish_summary'), // $1 목적지에 무사히 도착하여 가져간 자원을 전달 하였습니다.
    },
    preva_finish: {
        subject: ns_i18n.t('report_preva_finish_subject'), // 보급 완료 보고
        summary: ns_i18n.t('report_preva_finish_summary'), // 목적지에 주둔중인 부대에게 추가 보급을 완료했습니다.
    },
    ally_troop_arrival: {
        subject: ns_i18n.t('report_ally_troop_arrival_subject'), // 동맹국 지원군의 도착 보고
        summary: ns_i18n.t('report_ally_troop_arrival_summary'), // 동맹국의 지원군이 도착하여 주둔을 시작했습니다.
    },
    reinforce_finish_1: {
        subject: ns_i18n.t('report_reinforce_finish_1_subject'), // 지원 부대 도착 완료 보고
        summary: ns_i18n.t('report_reinforce_finish_1_summary'), // $1 목적지에 가져간 자원을 전달하고 병력과 영웅은 편입되었습니다.
    },
    reinforce_finish_2: {
        subject: ns_i18n.t('report_reinforce_finish_2_subject'), // 지원 부대 도착 완료 보고
        summary: ns_i18n.t('report_reinforce_finish_2_summary'), // $1 목적지에 가져간 자원은 전달하고 병력과 영웅은 주둔을 시작하였습니다.
    },
    reinforce_finish_3: {
        subject: ns_i18n.t('report_reinforce_finish_3_subject'), // 지원 부대 도착 완료 보고
        summary: ns_i18n.t('report_reinforce_finish_3_summary'), // $1 목적지에 가져간 자원은 전달하고 병력과 영웅은 목적지의 주둔 불가로 출발지로 복귀 합니다.
    },
    reinforce_finish_4: {
        subject: ns_i18n.t('report_reinforce_finish_4_subject'), // 지원 부대 도착 완료 보고'
        summary: ns_i18n.t('report_reinforce_finish_4_summary'), // $1 목적지에 가져간 자원은 전달하고 병력의 추가 주둔이 불가능하여 출발지로 복귀합니다.
    },
    reinforce_finish_5: {
        subject: ns_i18n.t('report_reinforce_finish_5_subject'), // 지원 부대 도착 완료 보고
        summary: ns_i18n.t('report_reinforce_finish_5_summary'), // $1 목적지에 가져간 자원을 전달하고 병력 $1과 $2영웅은 편입되었습니다. 추가 주둔이 불가능한 영웅은 출발지로 복귀합니다.
    },
    scout_failure: {
        subject: ns_i18n.t('report_scout_failure_subject'), // 정찰 실패 보고
        summary: ns_i18n.t('report_scout_failure_summary'), // 파견한 정찰병이 적에게 발각되어 정찰에 실패하고 $1명 중 $2명이 사망했습니다.
    },
    scout_find: {
        subject: ns_i18n.t('report_scout_find_subject'), // 적 정찰병 색출 보고
        summary: ns_i18n.t('report_scout_find_summary'), // 침입한 적 정찰병 $1명을 색출하여 적 정찰병 $2명을 처치했습니다.<br />다행히  적들은 아무런 정보도 얻어가지 못했습니다.<br />정찰병을 좀 더 훈련시켜 보유한다면 앞으로도 적들의 정찰은 쉽지 않을 것입니다.
    },
    scout_success: {
        subject: ns_i18n.t('report_scout_success_subject'), // 정찰 성공 보고
        summary: ns_i18n.t('report_scout_success_summary'), // $1 정찰을 성공하였습니다. ($2 등급)
    },
    battle_none: {
        subject: ns_i18n.t('report_battle_none_subject'), // 전투 미발생 보고
        summary: ns_i18n.t('report_battle_none_summary'), // 전투가 발생하지 않았습니다.
    },
};