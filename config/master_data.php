<?php
global $i18n;

// 공격측 진영 가능 정보
$attack_position_line1[0] = 'worker';
$attack_position_line1[1] = 'infantry';
$attack_position_line1[2] = 'armed_infantry';
$attack_position_line1[3] = 'pikeman';
$attack_position_line1[4] = 'spearman';
$attack_position_line1[5] = 'battering_ram';
$attack_position_line2[0] = 'horseman';
$attack_position_line2[1] = 'armed_horseman';
$attack_position_line2[2] = 'archer';
$attack_position_line2[3] = 'bowman';
$attack_position_line2[4] = 'adv_catapult';
$attack_position_line3[0] = 'catapult';
$attack_position_line3[1] = 'scout';
$attack_position_line3[2] = 'transporter';

// 방어측 진영 가능 정보
// wall 오픈시
$defence_position_wall_open_line1[0] = 'catapult';
$defence_position_wall_open_line2[0] = 'battering_ram';
$defence_position_wall_open_line2[1] = 'adv_catapult';
$defence_position_wall_open_line3[0] = 'scout';
$defence_position_wall_open_line3[1] = 'transporter';
$defence_position_wall_open_lineFort1[0] = 'trap';
$defence_position_wall_open_lineFort1[1] = 'worker';
$defence_position_wall_open_lineFort1[2] = 'infantry';
$defence_position_wall_open_lineFort1[3] = 'armed_infantry';
$defence_position_wall_open_lineFort1[4] = 'pikeman';
$defence_position_wall_open_lineFort1[5] = 'spearman';
$defence_position_wall_open_lineFort2[0] = 'abatis';
$defence_position_wall_open_lineFort2[1] = 'tower';
$defence_position_wall_open_lineFort2[2] = 'horseman';
$defence_position_wall_open_lineFort2[3] = 'armed_horseman';
$defence_position_wall_open_lineFort2[4] = 'archer';
$defence_position_wall_open_lineFort2[5] = 'bowman';
$defence_position_wall_open_lineWall[0] = 'wall';

// wall 폐쇄시
$defence_position_wall_close_line1[0] = 'worker';
$defence_position_wall_close_line1[1] = 'infantry';
$defence_position_wall_close_line1[2] = 'armed_infantry';
$defence_position_wall_close_line1[3] = 'pikeman';
$defence_position_wall_close_line1[4] = 'spearman';
$defence_position_wall_close_line1[5] = 'battering_ram';
$defence_position_wall_close_line2[0] = 'horseman';
$defence_position_wall_close_line2[1] = 'armed_horseman';
$defence_position_wall_close_line2[2] = 'archer';
$defence_position_wall_close_line2[3] = 'bowman';
$defence_position_wall_close_line2[4] = 'adv_catapult';
$defence_position_wall_close_line3[0] = 'catapult';
$defence_position_wall_close_line3[1] = 'scout';
$defence_position_wall_close_line3[2] = 'transporter';
$defence_position_wall_close_lineFort1[0] = 'trap';
$defence_position_wall_close_lineFort2[0] = 'abatis';
$defence_position_wall_close_lineFort2[1] = 'tower';
$defence_position_wall_close_lineWall[0] = 'wall';

// 자원지 전투
$defence_position_line1[0] = 'trap';
$defence_position_line1[1] = 'worker';
$defence_position_line1[2] = 'infantry';
$defence_position_line1[3] = 'armed_infantry';
$defence_position_line1[4] = 'pikeman';
$defence_position_line1[5] = 'spearman';
$defence_position_line1[6] = 'battering_ram';
$defence_position_line2[0] = 'abatis';
$defence_position_line2[1] = 'tower';
$defence_position_line2[2] = 'horseman';
$defence_position_line2[3] = 'armed_horseman';
$defence_position_line2[4] = 'archer';
$defence_position_line2[5] = 'bowman';
$defence_position_line3[0] = 'adv_catapult';
$defence_position_line3[1] = 'scout';
$defence_position_line3[2] = 'transporter';
$defence_position_line3[3] = 'catapult';

// 위 값들은 따로 다른 곳으로 빼는게 나을 듯?

// 서버데이터스토리지 로딩 분 참조 (클래스나 함수에서 $_M 만 global 선언하면 타 자료에 접근 가능)
$_M = [];
$_M['CODESET'] = [];

// 영웅 치료 기준시간
$_M['CODESET']['HERO_TREATMENT_TIME'] = ['W' => 7200, 'E' => 43200, 'F' => 172800];

// $_M['CODESET']['TROOP_STATUS'] = ['M' => 'March', 'B' => 'Battle', 'C' => 'Station', 'R' => 'Return', 'W' => 'Cancel'];
// $_M['CODESET']['TROOP_CMD_TYPE'] = ['T' => 'Transport', 'R' => 'Reinforce', 'P' => 'Supply', 'S' => 'Scout', 'A' => 'Attack'];
$_M['CODESET']['TROOP_STATUS'] = [
    'M' => $i18n->t('going_to_war'), // 출진
    'B' => $i18n->t('battle'), // 전투
    'C' => $i18n->t('deployed'), // 주둔
    'R' => $i18n->t('withdrawal'), // 회군
    'W' => $i18n->t('cancel'), // 취소
];
$_M['CODESET']['TROOP_CMD_TYPE'] = [
    'T' => $i18n->t('transport'), // 수송
    'R' => $i18n->t('support'), // 지원
    'P' => $i18n->t('supply'), // 보급
    'S' => $i18n->t('reconnaissance'), // 정찰
    'A' => $i18n->t('attack'), // 공격
];


// 성벽 정보
$_M['CODESET']['CASTLE_WALL']['SPEC']['spec_attack'] = 0;
$_M['CODESET']['CASTLE_WALL']['SPEC']['spec_defence'] = 10;
$_M['CODESET']['CASTLE_WALL']['SPEC']['spec_energy'] = 100;
$_M['CODESET']['CASTLE_WALL']['SPEC']['weak_type'] = 'catapult,adv_catapult';
$_M['CODESET']['CASTLE_WALL']['SPEC']['weaker_type'] = 'battering_ram';

// 정찰 등급 선택 테이블
$_M['CODESET']['SCOUT_LEVEL_TABLE']['territory']['1'] = [95, 05, 00, 00, 00, 00];
$_M['CODESET']['SCOUT_LEVEL_TABLE']['territory']['2'] = [00, 50, 50, 00, 00, 00];
$_M['CODESET']['SCOUT_LEVEL_TABLE']['territory']['3'] = [00, 00, 50, 50, 00, 00];
$_M['CODESET']['SCOUT_LEVEL_TABLE']['territory']['4'] = [00, 00, 00, 50, 50, 00];
$_M['CODESET']['SCOUT_LEVEL_TABLE']['territory']['5'] = [00, 00, 00, 00, 50, 50];
$_M['CODESET']['SCOUT_LEVEL_TABLE']['territory']['6'] = [00, 00, 00, 00, 00, 100];

$_M['CODESET']['SCOUT_LEVEL_TABLE']['valley']['1'] = [95, 00, 00, 05, 00, 00];
$_M['CODESET']['SCOUT_LEVEL_TABLE']['valley']['2'] = [00, 00, 00,100, 00, 00];
$_M['CODESET']['SCOUT_LEVEL_TABLE']['valley']['3'] = [00, 00, 00, 50, 50, 00];
$_M['CODESET']['SCOUT_LEVEL_TABLE']['valley']['4'] = [00, 00, 00, 00, 50, 50];
$_M['CODESET']['SCOUT_LEVEL_TABLE']['valley']['5'] = [00, 00, 00, 00, 00,100];
$_M['CODESET']['SCOUT_LEVEL_TABLE']['valley']['6'] = [00, 00, 00, 00, 00,100];

// qbw_master_data.js 에서도 변경이 필요함.
$_M['CODESET']['READY_FOR_BATTLE']['A'][100] = 120;
$_M['CODESET']['READY_FOR_BATTLE']['A'][500] = 130;
$_M['CODESET']['READY_FOR_BATTLE']['A'][1000] = 140;
$_M['CODESET']['READY_FOR_BATTLE']['A'][5000] = 150;
$_M['CODESET']['READY_FOR_BATTLE']['A'][10000] = 160;
$_M['CODESET']['READY_FOR_BATTLE']['A'][50000] = 170;
$_M['CODESET']['READY_FOR_BATTLE']['A'][100000] = 180;
$_M['CODESET']['READY_FOR_BATTLE']['A'][99999999] = 190;

$_M['CODESET']['READY_FOR_BATTLE']['R'][100] = 40;
$_M['CODESET']['READY_FOR_BATTLE']['R'][500] = 50;
$_M['CODESET']['READY_FOR_BATTLE']['R'][1000] = 60;
$_M['CODESET']['READY_FOR_BATTLE']['R'][5000] = 70;
$_M['CODESET']['READY_FOR_BATTLE']['R'][10000] = 80;
$_M['CODESET']['READY_FOR_BATTLE']['R'][50000] = 90;
$_M['CODESET']['READY_FOR_BATTLE']['R'][100000] = 100;
$_M['CODESET']['READY_FOR_BATTLE']['R'][99999999] = 110;

$_M['CODESET']['READY_FOR_BATTLE']['T'][100] = 20;
$_M['CODESET']['READY_FOR_BATTLE']['T'][500] = 30;
$_M['CODESET']['READY_FOR_BATTLE']['T'][1000] = 40;
$_M['CODESET']['READY_FOR_BATTLE']['T'][5000] = 50;
$_M['CODESET']['READY_FOR_BATTLE']['T'][10000] = 60;
$_M['CODESET']['READY_FOR_BATTLE']['T'][50000] = 70;
$_M['CODESET']['READY_FOR_BATTLE']['T'][100000] = 80;
$_M['CODESET']['READY_FOR_BATTLE']['T'][99999999] = 90;

$_M['CODESET']['READY_FOR_BATTLE']['P'][100] = 20;
$_M['CODESET']['READY_FOR_BATTLE']['P'][500] = 30;
$_M['CODESET']['READY_FOR_BATTLE']['P'][1000] = 40;
$_M['CODESET']['READY_FOR_BATTLE']['P'][5000] = 50;
$_M['CODESET']['READY_FOR_BATTLE']['P'][10000] = 60;
$_M['CODESET']['READY_FOR_BATTLE']['P'][50000] = 70;
$_M['CODESET']['READY_FOR_BATTLE']['P'][100000] = 80;
$_M['CODESET']['READY_FOR_BATTLE']['P'][99999999] = 90;

$_M['CODESET']['READY_FOR_BATTLE']['S'][100] = 10;
$_M['CODESET']['READY_FOR_BATTLE']['S'][500] = 20;
$_M['CODESET']['READY_FOR_BATTLE']['S'][1000] = 30;
$_M['CODESET']['READY_FOR_BATTLE']['S'][5000] = 40;
$_M['CODESET']['READY_FOR_BATTLE']['S'][10000] = 50;
$_M['CODESET']['READY_FOR_BATTLE']['S'][50000] = 60;
$_M['CODESET']['READY_FOR_BATTLE']['S'][100000] = 70;
$_M['CODESET']['READY_FOR_BATTLE']['S'][99999999] = 80;

//////////////////////////////////////////////////////////////////////

$_M['CODESET']['READY_FOR_BATTLE']['A'][100] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['A'][500] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['A'][1000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['A'][5000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['A'][10000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['A'][50000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['A'][100000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['A'][99999999] = 0;

$_M['CODESET']['READY_FOR_BATTLE']['R'][100] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['R'][500] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['R'][1000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['R'][5000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['R'][10000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['R'][50000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['R'][100000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['R'][99999999] = 0;

$_M['CODESET']['READY_FOR_BATTLE']['T'][100] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['T'][500] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['T'][1000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['T'][5000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['T'][10000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['T'][50000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['T'][100000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['T'][99999999] = 0;

$_M['CODESET']['READY_FOR_BATTLE']['P'][100] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['P'][500] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['P'][1000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['P'][5000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['P'][10000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['P'][50000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['P'][100000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['P'][99999999] = 0;

$_M['CODESET']['READY_FOR_BATTLE']['S'][100] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['S'][500] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['S'][1000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['S'][5000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['S'][10000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['S'][50000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['S'][100000] = 0;
$_M['CODESET']['READY_FOR_BATTLE']['S'][99999999] = 0;

// 병과 적성 등급에 따른 공격력 추가
$_M['CODESET']['ATTACK_INCREASE']['S'] = 20;
$_M['CODESET']['ATTACK_INCREASE']['A'] = 15;
$_M['CODESET']['ATTACK_INCREASE']['B'] = 10;
$_M['CODESET']['ATTACK_INCREASE']['C'] = 5;
$_M['CODESET']['ATTACK_INCREASE']['D'] = 0;

// 병과 적성 등급에 따른 공격력 추가
$_M['CODESET']['HERO_STAT'] = ['L' => 'leadership', 'M' => 'mil_force', 'I' => 'intellect', 'P' => 'politics', 'C' => 'charm'];

// 강화
/*$_M['HERO_ACQUIRED_ENCHANT_COST']['0'] = Array('item_cnt' => '1', 'cost_time' => '10', 'success_per' => '800', 'lost_hero_per' => '0');
$_M['HERO_ACQUIRED_ENCHANT_COST']['1'] = Array('item_cnt' => '1', 'cost_time' => '20', 'success_per' => '700', 'lost_hero_per' => '0');
$_M['HERO_ACQUIRED_ENCHANT_COST']['2'] = Array('item_cnt' => '1', 'cost_time' => '30', 'success_per' => '600', 'lost_hero_per' => '0');
$_M['HERO_ACQUIRED_ENCHANT_COST']['3'] = Array('item_cnt' => '2', 'cost_time' => '60', 'success_per' => '500', 'lost_hero_per' => '0');
$_M['HERO_ACQUIRED_ENCHANT_COST']['4'] = Array('item_cnt' => '2', 'cost_time' => '120', 'success_per' => '400', 'lost_hero_per' => '0');
$_M['HERO_ACQUIRED_ENCHANT_COST']['5'] = Array('item_cnt' => '2', 'cost_time' => '240', 'success_per' => '300', 'lost_hero_per' => '10');
$_M['HERO_ACQUIRED_ENCHANT_COST']['6'] = Array('item_cnt' => '4', 'cost_time' => '560', 'success_per' => '200', 'lost_hero_per' => '20');
$_M['HERO_ACQUIRED_ENCHANT_COST']['7'] = Array('item_cnt' => '4', 'cost_time' => '960', 'success_per' => '100', 'lost_hero_per' => '30');
$_M['HERO_ACQUIRED_ENCHANT_COST']['8'] = Array('item_cnt' => '4', 'cost_time' => '1920', 'success_per' => '10', 'lost_hero_per' => '40');
$_M['HERO_ACQUIRED_ENCHANT_COST']['9'] = Array('item_cnt' => '8', 'cost_time' => '3600', 'success_per' => '1', 'lost_hero_per' => '50');*/

$_M['HERO_ACQUIRED_ENCHANT_COST']['0'] = ['item_cnt' => '1', 'cost_time' => '10', 'success_per' => '900', 'lost_hero_per' => '0'];
$_M['HERO_ACQUIRED_ENCHANT_COST']['1'] = ['item_cnt' => '1', 'cost_time' => '10', 'success_per' => '800', 'lost_hero_per' => '0'];
$_M['HERO_ACQUIRED_ENCHANT_COST']['2'] = ['item_cnt' => '1', 'cost_time' => '10', 'success_per' => '700', 'lost_hero_per' => '0'];
$_M['HERO_ACQUIRED_ENCHANT_COST']['3'] = ['item_cnt' => '1', 'cost_time' => '10', 'success_per' => '500', 'lost_hero_per' => '0'];
$_M['HERO_ACQUIRED_ENCHANT_COST']['4'] = ['item_cnt' => '1', 'cost_time' => '10', 'success_per' => '450', 'lost_hero_per' => '0'];
$_M['HERO_ACQUIRED_ENCHANT_COST']['5'] = ['item_cnt' => '1', 'cost_time' => '10', 'success_per' => '400', 'lost_hero_per' => '0'];
$_M['HERO_ACQUIRED_ENCHANT_COST']['6'] = ['item_cnt' => '1', 'cost_time' => '10', 'success_per' => '200', 'lost_hero_per' => '0'];
$_M['HERO_ACQUIRED_ENCHANT_COST']['7'] = ['item_cnt' => '1', 'cost_time' => '10', 'success_per' => '150', 'lost_hero_per' => '0'];
$_M['HERO_ACQUIRED_ENCHANT_COST']['8'] = ['item_cnt' => '1', 'cost_time' => '10', 'success_per' => '100', 'lost_hero_per' => '0'];
$_M['HERO_ACQUIRED_ENCHANT_COST']['9'] = ['item_cnt' => '1', 'cost_time' => '10', 'success_per' => '50',  'lost_hero_per' => '0'];

// 영향력 - 보유 영지와 외부 자원지 보유는 일정 값씩 증가하므로 따로 master_data만들지 않음(영지보유시 500, 외부 자원지 보유시 50)

// 등급별 영웅수
$_M['HERO_APPOINT_POWER'] = [];
$_M['HERO_APPOINT_POWER']['1'] = ['total_power' => '5', 'increase_power' => '5'];
$_M['HERO_APPOINT_POWER']['2'] = ['total_power' => '10', 'increase_power' => '5'];
$_M['HERO_APPOINT_POWER']['3'] = ['total_power' => '20', 'increase_power' => '10'];
$_M['HERO_APPOINT_POWER']['4'] = ['total_power' => '40', 'increase_power' => '20'];
$_M['HERO_APPOINT_POWER']['5'] = ['total_power' => '80', 'increase_power' => '40'];
$_M['HERO_APPOINT_POWER']['6'] = ['total_power' => '160', 'increase_power' => '80'];
$_M['HERO_APPOINT_POWER']['7'] = ['total_power' => '320', 'increase_power' => '160'];
$_M['HERO_APPOINT_POWER']['8'] = ['total_power' => '640', 'increase_power' => '320'];
$_M['HERO_APPOINT_POWER']['9'] = ['total_power' => '1280', 'increase_power' => '640'];
$_M['HERO_APPOINT_POWER']['10'] = ['total_power' => '2560', 'increase_power' => '1280'];

// 1큐빅당 자원량
$_M['QBIG_TRANSRATE_VALUE'] = ['gold' => 10000, 'food' => 12500, 'horse' => 12500, 'lumber' => 12500, 'iron' => 12500];

// 시장 판매 상품 수량
$_M['MARKET_SALE_AMOUNT'] = [];
$_M['MARKET_SALE_AMOUNT']['1'] = ['gold' => 13000, 'food' => 16250, 'horse' => 16250, 'lumber' => 16250, 'iron' => 16250, 'cashitem' => 1];
$_M['MARKET_SALE_AMOUNT']['2'] = ['gold' => 20000, 'food' => 25000, 'horse' => 25000, 'lumber' => 25000, 'iron' => 25000, 'cashitem' => 1];
$_M['MARKET_SALE_AMOUNT']['3'] = ['gold' => 30000, 'food' => 37500, 'horse' => 37500, 'lumber' => 37500, 'iron' => 37500, 'cashitem' => 1];
$_M['MARKET_SALE_AMOUNT']['4'] = ['gold' => 45000, 'food' => 56250, 'horse' => 56250, 'lumber' => 56250, 'iron' => 56250, 'cashitem' => 1];
$_M['MARKET_SALE_AMOUNT']['5'] = ['gold' => 68000, 'food' => 85000, 'horse' => 85000, 'lumber' => 85000, 'iron' => 85000, 'cashitem' => 1];
$_M['MARKET_SALE_AMOUNT']['6'] = ['gold' => 100000, 'food' => 125000, 'horse' => 125000, 'lumber' => 125000, 'iron' => 125000, 'cashitem' => 1];
$_M['MARKET_SALE_AMOUNT']['7'] = ['gold' => 150000, 'food' => 187500, 'horse' => 187500, 'lumber' => 187500, 'iron' => 187500, 'cashitem' => 1];
$_M['MARKET_SALE_AMOUNT']['8'] = ['gold' => 230000, 'food' => 287500, 'horse' => 287500, 'lumber' => 287500, 'iron' => 287500, 'cashitem' => 1];
$_M['MARKET_SALE_AMOUNT']['9'] = ['gold' => 350000, 'food' => 437500, 'horse' => 437500, 'lumber' => 437500, 'iron' => 437500, 'cashitem' => 1];
$_M['MARKET_SALE_AMOUNT']['10'] = ['gold' => 520000, 'food' => 650000, 'horse' => 650000, 'lumber' => 650000, 'iron' => 650000, 'cashitem' => 1];

// 탐색
//$_M['ENCOUNTER_ACQUIRED_ITEM'] = Array(500013,500014,500015,500021,500023,500051,500056,500096);
$_M['ENCOUNTER_ACQUIRED_ITEM'] = [500051, 500001, 500002, 500003, 500004, 500005, 500055, 500085, 500022, 500036, 500045, 500046, 500047, 500104, 500061, 500024, 500025, 500026, 500027, 500708];

$_M['ENCOUNTER_SUCCESS_RATE'] = ['distance' => 60, 'in_castle' => 65, 'territory' => 70, 'world' => 75, 'walkabout' => 90, 'around_world' => 100];
$_M['ENCOUNTER_RAID_RATE'] = ['distance' => 3, 'in_castle' => 5, 'territory' => 10, 'world' => 20, 'walkabout' => 30, 'around_world' => 50];

$_M['ENCOUNTER_REWARD_VALUE'] = [];
$_M['ENCOUNTER_REWARD_VALUE']['gold'] = ['reward_min' => 100, 'reward_unit' => 1000];
$_M['ENCOUNTER_REWARD_VALUE']['food'] = ['reward_min' => 1000, 'reward_unit' => 10000];
$_M['ENCOUNTER_REWARD_VALUE']['horse'] = ['reward_min' => 500, 'reward_unit' => 2000];
$_M['ENCOUNTER_REWARD_VALUE']['lumber'] = ['reward_min' => 500, 'reward_unit' => 4000];
$_M['ENCOUNTER_REWARD_VALUE']['iron'] = ['reward_min' => 500, 'reward_unit' => 4000];

$_M['ENCOUNTER_REWARD_ITEM_VALUE'] = [];
$_M['ENCOUNTER_REWARD_ITEM_VALUE']['gold'] = ['reward_min' => 5000, 'reward_unit' => 8000];
$_M['ENCOUNTER_REWARD_ITEM_VALUE']['food'] = ['reward_min' => 10000, 'reward_unit' => 20000];
$_M['ENCOUNTER_REWARD_ITEM_VALUE']['horse'] = ['reward_min' => 5000, 'reward_unit' => 8000];
$_M['ENCOUNTER_REWARD_ITEM_VALUE']['lumber'] = ['reward_min' => 5000, 'reward_unit' => 8000];
$_M['ENCOUNTER_REWARD_ITEM_VALUE']['iron'] = ['reward_min' => 5000, 'reward_unit' => 8000];

$_M['ENCOUNTER_TYPE_BUILD_TIME'] = ['distance' => 1200, 'in_castle' => 3600, 'territory' => 7200, 'world' => 14400, 'walkabout' => 21600, 'around_world' => 43200];

// 공적패 영웅 경험치
$_M['HERO_SKILL_EXP_MEDAL'][500007] = 70;
$_M['HERO_SKILL_EXP_MEDAL'][500008] = 110;
$_M['HERO_SKILL_EXP_MEDAL'][500009] = 250;
$_M['HERO_SKILL_EXP_MEDAL'][500010] = 700;
$_M['HERO_SKILL_EXP_MEDAL'][500011] = 2000;
$_M['HERO_SKILL_EXP_MEDAL'][500012] = 4500;
// $_M['HERO_SKILL_EXP_MEDAL'][500525] = 700; // 이벤트 추가

$_M['FORCE_RELATION'] = [];
$_M['FORCE_RELATION']['UB'] = [];
$_M['FORCE_RELATION']['JJ'] = [];
$_M['FORCE_RELATION']['SK'] = [];
$_M['FORCE_RELATION']['WS'] = [];
$_M['FORCE_RELATION']['DT'] = [];
$_M['FORCE_RELATION']['PC'] = [];
$_M['FORCE_RELATION']['NN'] = [];
$_M['FORCE_RELATION']['UB']['GOOD'] = ['SK', 'WS'];
$_M['FORCE_RELATION']['UB']['BAD'] = ['JJ', 'DT', 'PC'];
$_M['FORCE_RELATION']['JJ']['GOOD'] = ['DT'];
$_M['FORCE_RELATION']['JJ']['BAD'] = ['UB', 'SK', 'WS', 'PC'];
$_M['FORCE_RELATION']['SK']['GOOD'] = ['UB', 'WS'];
$_M['FORCE_RELATION']['SK']['BAD'] = ['JJ', 'DT', 'PC'];
$_M['FORCE_RELATION']['WS']['GOOD'] = ['UB', 'SK'];
$_M['FORCE_RELATION']['WS']['BAD'] = ['JJ', 'DT', 'PC'];
$_M['FORCE_RELATION']['DT']['GOOD'] = ['JJ', 'PC'];
$_M['FORCE_RELATION']['DT']['BAD'] = ['UB', 'SK', 'WS'];
$_M['FORCE_RELATION']['PC']['GOOD'] = ['DT', 'NN'];
$_M['FORCE_RELATION']['PC']['BAD'] = ['UB', 'JJ', 'SK', 'WS'];
$_M['FORCE_RELATION']['NN']['GOOD'] = ['PC'];
$_M['FORCE_RELATION']['NN']['BAD'] = [];

$_M['ARTICLE_POST_REWARD'][] = ['m_item_pk' => 500001, 'item_cnt' => 1, 'rate' => 20 * 1000];
$_M['ARTICLE_POST_REWARD'][] = ['m_item_pk' => 500002, 'item_cnt' => 1, 'rate' => 20 * 1000];
$_M['ARTICLE_POST_REWARD'][] = ['m_item_pk' => 500003, 'item_cnt' => 1, 'rate' => 20 * 1000];
$_M['ARTICLE_POST_REWARD'][] = ['m_item_pk' => 500004, 'item_cnt' => 1, 'rate' => 20 * 1000];
$_M['ARTICLE_POST_REWARD'][] = ['m_item_pk' => 500005, 'item_cnt' => 1, 'rate' => 20 * 1000];

// 영빈관 개선으로 추가
$_M['HERO_FREE_BID_GOLD_UNIT'] = [0, 1000, 10000, 20000, 40000, 100000];

// 응모권 이벤트
$_M['ENTER_EVENT_INFO'] = [];

/*$_M['ENTER_EVENT_INFO'][] = ['need_item_count' => 6, 'type' => 1, 'period' => 1];
$_M['ENTER_EVENT_INFO'][] = ['need_item_count' => 3, 'type' => 2, 'period' => 1];
$_M['ENTER_EVENT_INFO'][] = ['need_item_count' => 1, 'type' => 3, 'period' => 1];
$_M['ENTER_EVENT_INFO'][] = ['need_item_count' => 6, 'type' => 1, 'period' => 2];
$_M['ENTER_EVENT_INFO'][] = ['need_item_count' => 3, 'type' => 2, 'period' => 2];
$_M['ENTER_EVENT_INFO'][] = ['need_item_count' => 2, 'type' => 3, 'period' => 2];
$_M['ENTER_EVENT_INFO'][] = ['need_item_count' => 6, 'type' => 1, 'period' => 3];
$_M['ENTER_EVENT_INFO'][] = ['need_item_count' => 3, 'type' => 2, 'period' => 3];
$_M['ENTER_EVENT_INFO'][] = ['need_item_count' => 2, 'type' => 3, 'period' => 3];*/

// 교환권 이벤트
$_M['ENTER_EVENT_INFO']['need_item_count'] = [];
$_M['ENTER_EVENT_INFO']['need_item_count']['1'] = 15;
$_M['ENTER_EVENT_INFO']['need_item_count']['2'] = 7;
$_M['ENTER_EVENT_INFO']['need_item_count']['3'] = 3;

// 요일 별
/*$_M['ENTER_EVENT_INFO'][0] = [];
$_M['ENTER_EVENT_INFO'][1] = ['period' => 1, 'item_type' => ['1' => '500022', '2' => '500165', '3' => '500104']];
$_M['ENTER_EVENT_INFO'][2] = ['period' => 2, 'item_type' => ['1' => '500017', '2' => '500118', '3' => '500028']];
$_M['ENTER_EVENT_INFO'][3] = ['period' => 3, 'item_type' => ['1' => '500103', '2' => '500004', '3' => '500164']];
$_M['ENTER_EVENT_INFO'][4] = ['period' => 4, 'item_type' => ['1' => '500041', '2' => '500005', '3' => '500016']];
$_M['ENTER_EVENT_INFO'][5] = ['period' => 5, 'item_type' => ['1' => '500055', '2' => '500001', '3' => '500035']];
$_M['ENTER_EVENT_INFO'][6] = ['period' => 6, 'item_type' => ['1' => '500102', '2' => '500003', '3' => '500038']];
$_M['ENTER_EVENT_INFO'][7] = ['period' => 7, 'item_type' => ['1' => '500122', '2' => '500002', '3' => '500033']];*/

// 날짜 별 - TODO 필요한 날짜만 있으면 되어서 하나만 남겨둠.
/*$_M['ENTER_EVENT_INFO']['20120217'] = ['period' => 1, 'item_type' => ['1' => '500102', '2' => '500003', '3' => '500038']];*/

// 공동공략 토벌령 도표
$_M['POSITION_NPC_POINT_LIST'] = [
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

// 군주 등급별 멀티 영지 생성수
$_M['LORD_GRADE_TERRITORY_COUNT'] = [];
$_M['LORD_GRADE_TERRITORY_COUNT'][1] = 1;
$_M['LORD_GRADE_TERRITORY_COUNT'][2] = 1;
$_M['LORD_GRADE_TERRITORY_COUNT'][3] = 2;
$_M['LORD_GRADE_TERRITORY_COUNT'][4] = 3;
$_M['LORD_GRADE_TERRITORY_COUNT'][5] = 4;
$_M['LORD_GRADE_TERRITORY_COUNT'][6] = 5;
$_M['LORD_GRADE_TERRITORY_COUNT'][7] = 6;
$_M['LORD_GRADE_TERRITORY_COUNT'][8] = 7;
$_M['LORD_GRADE_TERRITORY_COUNT'][9] = 8;
$_M['LORD_GRADE_TERRITORY_COUNT'][10] = 10;

// 건물별 최대 건설 가능수
$_M['BUILD_LIMIT_COUNT'] = [];
$_M['BUILD_LIMIT_COUNT']['200100'] = 1; // 대전
$_M['BUILD_LIMIT_COUNT']['200200'] = 1; // 행정부
$_M['BUILD_LIMIT_COUNT']['200300'] = 1; // 영빈관
$_M['BUILD_LIMIT_COUNT']['200400'] = 1; // 군사령부
$_M['BUILD_LIMIT_COUNT']['200500'] = 5; // 훈련소
$_M['BUILD_LIMIT_COUNT']['200600'] = 1; // 태학
$_M['BUILD_LIMIT_COUNT']['200700'] = 1; // 의료원
$_M['BUILD_LIMIT_COUNT']['200800'] = 1; // 대사관
$_M['BUILD_LIMIT_COUNT']['200900'] = 1; // 무역장
$_M['BUILD_LIMIT_COUNT']['201000'] = 1; // 시장
$_M['BUILD_LIMIT_COUNT']['201100'] = 9; // 창고
$_M['BUILD_LIMIT_COUNT']['201200'] = 9; // 민가
$_M['BUILD_LIMIT_COUNT']['201300'] = 11; // 전답
$_M['BUILD_LIMIT_COUNT']['201400'] = 11; // 목장
$_M['BUILD_LIMIT_COUNT']['201500'] = 11; // 벌목장
$_M['BUILD_LIMIT_COUNT']['201600'] = 1; // 성벽

// 코드에도 대응
$_M['BUILD_CODE_LIMIT_COUNT'] = [];
$_M['BUILD_CODE_LIMIT_COUNT']['CityHall'] = 1; // 대전
$_M['BUILD_CODE_LIMIT_COUNT']['Administration'] = 1; // 행정부
$_M['BUILD_CODE_LIMIT_COUNT']['ReceptionHall'] = 1; // 영빈관
$_M['BUILD_CODE_LIMIT_COUNT']['Military'] = 1; // 군사령부
$_M['BUILD_CODE_LIMIT_COUNT']['Army'] = 5; // 훈련소
$_M['BUILD_CODE_LIMIT_COUNT']['Technique'] = 1; // 태학
$_M['BUILD_CODE_LIMIT_COUNT']['Medical'] = 1; // 의료원
$_M['BUILD_CODE_LIMIT_COUNT']['Embassy'] = 1; // 대사관
$_M['BUILD_CODE_LIMIT_COUNT']['TradeDept'] = 1; // 무역장
$_M['BUILD_CODE_LIMIT_COUNT']['Market'] = 1; // 시장
$_M['BUILD_CODE_LIMIT_COUNT']['Storage'] = 9; // 창고
$_M['BUILD_CODE_LIMIT_COUNT']['Cottage'] = 9; // 민가
$_M['BUILD_CODE_LIMIT_COUNT']['Farmland'] = 11; // 전답
$_M['BUILD_CODE_LIMIT_COUNT']['StockFarm'] = 11; // 목장
$_M['BUILD_CODE_LIMIT_COUNT']['Logging'] = 11; // 벌목장
$_M['BUILD_CODE_LIMIT_COUNT']['CastleWall'] = 1; // 성벽

// 타임 버프용 데이터
$_M['TIME_BUFF'] = [];
$_M['TIME_BUFF']['start_date'] = '2024-10-20 00:00:00'; // 타임버프 시작일
$_M['TIME_BUFF']['end_date'] = '2024-11-31 00:00:00'; // 타임버프 종료일
$_M['TIME_BUFF']['max_count'] = (date('w') == 0 || date('w') == 6) ? 3 : 2; // 군주당 하루 최대 사용 횟수

// 접속 보상 이벤트
$_M['ACCESS_REWARD'] = [];
$_M['ACCESS_REWARD']['start_date'] = '2024-10-19';
$_M['ACCESS_REWARD']['end_date'] = '2024-10-20';
$_M['ACCESS_REWARD']['event_time'] = [
    ['start_time' => 3, 'end_time' => 7], // 한국 시간 12시 ~ 16시
    ['start_time' => 9, 'end_time' => 13], // 한국 시간 18시 ~ 22시
];
$_M['ACCESS_REWARD']['reward_item'] = 500801;
$_M['ACCESS_REWARD']['reward_count'] = 1;

// 보물 찾기 이벤트
$_M['TREASURE_EVENT'] = [];
$_M['TREASURE_EVENT']['start_date'] = '2024-10-20 00:00:00'; // 보물 찾기 시작일
$_M['TREASURE_EVENT']['end_date'] = '2024-11-31 00:00:00'; // 보물 찾기 종료일
$_M['TREASURE_EVENT']['material_item'] = [500803, 500804, 500805, 500806, 500807];
$_M['TREASURE_EVENT']['reward_item'] = 500808;
$_M['TREASURE_EVENT']['reward_count'] = 1;
