<?php
const I18N_LOCALE_LIST = ['ko', 'en', 'jp'];
const AUTH_STATUS_GUEST = 'G';

const LORD_SESSION_COLUMN = 'lord_pk, lord_name, lord_pic, status, level, power, morals, fame, num_slot_guest_hero, lord_hero_pk, main_posi_pk, alli_pk, num_appoint_hero, position_cnt, last_visit_club_dt, m_offi_pk, lord_enchant, date_part(\'epoch\', last_daily_hero_dt)::integer as last_daily_hero_dt, date_part(\'epoch\', last_login_dt)::integer as last_login_dt, is_chat_blocked, chat_block_end_dt, withdraw_dt';
const LORD_UNREAD_COLUMN = 'on_enemy_march_troops, unread_report_cnt, unread_letter_cnt, unread_hero_cnt, unread_quest_cnt, date_part(\'epoch\', unread_item_last_up_dt)::integer as unread_item_last_up_dt, unread_alliance_cnt, date_part(\'epoch\', lord_name_up_dt)::integer as lord_name_up_dt, date_part(\'epoch\', lord_card_up_dt)::integer as lord_card_up_dt';


const NUM_SLOT_GUEST_HERO = 500;

const NPC_TROOP_LORD_PK = 1;
const NPC_TROOP_POSI_PK = '999x999';


const DELIMITER_WORLD_COORDS = 'x';
const QBIG_TO_SECONDS = 60; // 1큐빅당 필요 시간
const ON_CASHSHOP_NEW_DISPLAY = 'block'; // 'block' or 'none'
const POSITION_NPC_INCREASE_TICK = 360; // 황건적 영지의 증가 필요시 증가 기준 최소틱 (초단위)


const LETTER_DELETE_PERIOD = 2592000;    // 30일
const REPORT_DELETE_PERIOD = 432000;    // 5일
const FAVORITE_DELETE_PERIOD = 604800;    // 7일

const ADMIN_LORD_PK = 2;    // 운영자 pk
const EMPEROR_LORD_PK = 3;    // 운영자 pk

const DEFAULT_PAGE_TYPE = 'default';
const DEFAULT_PAGE_NUM = 1000;  //  리스트 한페이지당 갯수
const ALLIANCE_ACTIVE_PAGE_NUM = 1000;    // 동맹 활동 리스트 한페이지당 갯수
const REPORT_LETTER_PAGE_NUM = 1000;    //  리스트 한페이지당 갯수
const HERO_LIST_PAGE_NUM = 1000;    // 영웅 관리 리스트 한페이지당 갯수

const CONF_ALLIANCE = true;


/*
 * 스크립트용 공용 define
 */
const PK_LORD_liubei = 130041;
const PK_LORD_caocao = 130001;
const PK_LORD_sunquan = 130021;
const PK_LORD_yuanshao = 130061;
const PK_LORD_dongzhuo = 130081;

const PK_BUILDING_CITYHALL = '200100';
const PK_BUILDING_STORAGE = '201100';
const PK_BUILDING_COTTAGE = '201200';
const PK_BUILDING_FOOD = '201300';
const PK_BUILDING_HORSE = '201400';
const PK_BUILDING_LUMBER = '201500';
const PK_BUILDING_WALL = '201600';
const PK_BUILDING_ARMY = '200500';
const PK_BUILDING_TECHNIQUE = '200600';
const PK_BUILDING_ADMINISTRATION = '200200';
const PK_BUILDING_RECEPTIONHALL = '200300';
const PK_BUILDING_MILITARY = '200400';
const PK_BUILDING_EMBASSY = '200800';
const PK_BUILDING_MARKET = '201000';
const PK_BUILDING_TRADEDEPT = '200900';
const PK_BUILDING_MEDICAL = '200700';

const PK_CMD_CONST = 140001;
const PK_CMD_TECHN = 140002;
const PK_CMD_ENCOU = 140003;
const PK_CMD_INVIT = 140004;
const PK_CMD_TROOP_CAPTAIN = 140005;
const PK_CMD_TROOP_DIRECTOR = 140006;
const PK_CMD_TROOP_STAFF = 140007;
const PK_CMD_SCOUT = 140008;
const PK_CMD_TRANS = 140009;
const PK_CMD_TREAT = 140010;

// 요충지
const POSITION_POINT_OCCU_POINT = 10;
const POSITION_POINT_OCCU_DURATION = 604800;
const POSITION_POINT_EFFECT_ITEM = 500221;
const POSITION_POINT_ATTACK_LIMIT = 6;

const POINT_BATTLE_TIME = 300; // 5분
const POINT_OCCUPATION_BONUS_TIME_24 = 86400; // 24시간
const POINT_OCCUPATION_BONUS_TIME_48 = 172800; // 48시간
const POINT_OCCUPATION_BONUS_TIME_72 = 259200; // 72시간
const POINT_OCCUPATION_BONUS_TIME_96 = 345600; // 96시간

const TROOP_ARMY_LIMIT = 300000;

// 섬멸전
const RAID_LIST_PAGE_NUM = 5;    // 영웅 관리 리스트 한페이지당 갯수
const RAID_SUPPRESS_RATE = 500;    // 토벌령 섬멸전 발견 확률. 1000분율.
const RAID_NPC_RATE = 20;    // 토벌령 섬멸전 발견 확률. 1000분율.

// 외부 자원지 방어시설
const MAX_VALLEY_FORT = 50000; // 방어시설 공간 최대치. 성벽 레벨 10 기준.

const BUILD_TIME_HERO_ENCOUNTER = 3600; // 기본 3600 - 1시간
const BUILD_TIME_HERO_INVITATION = 30;

const BATTLE_MAX_TURN = 10;
const RAID_MAX_TURN = 5;
const BATTLE_DAMAGE_NOWEAK = 10; // 보통 상성
const BATTLE_DAMAGE_PLUS_WEAK = 20; // 취약병과로 공격
const BATTLE_DAMAGE_PLUS_WEAKER = 30; // 매우취약병과로 공격
const BATTLE_DAMAGE_MINUS_WEAK_R = 5; // 취약병과로 부터 방어
const BATTLE_DAMAGE_MINUS_WEAKER_R = 3; // 매우취약병과로 부터 방어
// 운영자 pk

const FREE_SPEEDUP_TIME = 300;    // 5분
const PLUNDER_REWARD_PERIOD = 300; // 5분, 3600 1시간

const GACHAPON_LIST_PAGE_NUM = 8;    // 이벤츠 가챠폰 상세보기 리스트 한페이지당 갯수
const GACHAPON_BUY_LIMIT_MAX_CNT = 400;    // 이벤츠 가챠폰 7성 판매 허용 판매 갯수
const GACHAPON_BUY_LIMIT_MIN_CNT = 50;    // 이벤츠 가챠폰 7성 판매 허용 판매 갯수
const GACHAPON_BUY_LIMIT_CNT = 400;    // 이벤츠 가챠폰 7성 판매 허용 판매 갯수
const GACHAPON_LORD_BUY_LIMIT_CNT = 20;    // 이벤츠 가챠폰 1인 허용 판매 갯수

const NEW_GACHAPON_LIST_PAGE_NUM = 8;    // 이벤츠 신규 가챠폰 상세보기 리스트 한페이지당 갯수
const NEW_GACHAPON_BUY_LIMIT_MAX_CNT = 400;    // 이벤츠 신규 가챠폰 7성 판매 허용 판매 갯수
const NEW_GACHAPON_BUY_LIMIT_MIN_CNT = 50;    // 이벤츠 신규 가챠폰 7성 판매 허용 판매 갯수
const NEW_GACHAPON_LORD_BUY_LIMIT_CNT = 20;    // 이벤츠 신규 가챠폰 1인 허용 판매 갯수

const BUILD_QUEUE_INCREASE_ITEM = 500102;	//3개짜리 건설허가서
const BUILD_QUEUE_MAX_COUNT = 3;
const BUILD_QUEUE_INCREASE_COUNT = 2;
const BUILD_QUEUE_DEFAULT_COUNT = 1;

const BUILD_QUEUE2_INCREASE_ITEM = 500535;	//5개짜리 건설허가서
const BUILD_QUEUE2_MAX_COUNT = 5;

// 영웅 스킬 아이템
const HERO_SKILL_POCKET = 500114;
const HERO_SKILL_COPPER_BOX = 500115;
const HERO_SKILL_SILVER_BOX = 500116;
const HERO_SKILL_GOLD_BOX = 500117;
const HERO_SKILL_COPPER_KEY = 500118;
const HERO_SKILL_SILVER_KEY = 500119;
const HERO_SKILL_GOLD_KEY = 500120;
const HERO_SKILL_UNEQUIP = 500121;

// 영웅 전투 스킬 아이템
const HERO_SKILL_BATTLE_POCKET = 500441;
const HERO_SKILL_BATTLE_COPPER_BOX = 500442;
const HERO_SKILL_BATTLE_SILVER_BOX = 500443;
const HERO_SKILL_BATTLE_GOLD_BOX = 500444;
const HERO_SKILL_BATTLE_COPPER_KEY = 500445;
const HERO_SKILL_BATTLE_SILVER_KEY = 500446;
const HERO_SKILL_BATTLE_GOLD_KEY = 500447;

const HERO_SKILL_BATTLE_EXCELLENT_POCKET = 500462;

// 명장 전용 스킬 아이템
const HERO_SKILL_NAMED_BOX = 500751;

// 영웅 스킬 아이템 오픈시 나올수 있는 개수
const HERO_SKILL_COPPER_BOX_SKILL_COUNT = 3;
const HERO_SKILL_SILVER_BOX_SKILL_COUNT = 5;
const HERO_SKILL_GOLD_BOX_SKILL_COUNT = 7;
const HERO_SKILL_BATTLE_BOX_SKILL_COUNT = 1;

// 이벤트 영웅 스킬 아이템
const HERO_SKILL_HIGH_POCKET = 500439; // 고급 기술 주머니
const HERO_SKILL_HIGH_POCKET_SKILL_COUNT = 5; // 고급 기술 주머니 사용시 나올 수 있는 개수

// 보물창고에서 보여지는 개수
const HERO_SKILL_MY_ITEM_LIST_NUM = 12;
// 기술관리에서 보여지는 개수
const HERO_SKILL_LIST_NUM = 8;
// 기술조합에서 보여지는 개수
const HERO_SKILL_COMBINATION_LIST_NUM = 9;

// 영웅 기술 획득
const HERO_SKILL_ACQUIRE_TIME = 3600;	// 한시간
const HERO_SKILL_ACQUIRE_BATTLE_POPULATION = 2000;	// 2000명당
// 영웅 기술 획득량
const HERO_SKILL_ACQUIRE_ASSIGN = 3;
const HERO_SKILL_ACQUIRE_HERO_BATTLE = 1;
const HERO_SKILL_ACQUIRE_OUTSIDE_CAMP = 3;
const HERO_SKILL_ACQUIRE_TECHNIQUE_COMPLETE = 4;
const HERO_SKILL_ACQUIRE_CONSTRUCTION_COMPLETE = 4;
const HERO_SKILL_ACQUIRE_ENCOUNTER_COMPLETE = 4;
const HERO_SKILL_ACQUIRE_BATTLE = 1;
const HERO_SKILL_BATTLE_MAX = 20;
const HERO_SKILL_ACQUIRE_SALARY = 20;

const MAKE_ALLIANCE_GOLD = 10000;

const INVALID_ARGS ='Invalid request';

const TROOP_OCCUPATION_TRIPTIME = 3600;	// 1시간
const TROOP_WITHDRAWAL_TIME = 604800;	// 1주일

const BATTLE_MANTOMAN_TIE = 0;
const BATTLE_MANTOMAN_ATTACK_WIN = 1;
const BATTLE_MANTOMAN_DEFENCE_WIN = 2;
const BATTLE_MANTOMAN_MAX_TURN = 5;
const BATTLE_MANTOMAN_LIMIT_STAT = 50;	// 일기토 출전할수 있는 제한 스탯
const BATTLE_MANTOMAN_CRITICAL_RATE = 25;
const BATTLE_MANTOMAN_MISS_RATE = 1;
const BATTLE_MANTOMAN_LIMIT_ENERGY = 50;

const OFFICER_PK_MAX = 110130;

const OVER_ARMY_DESCREASE = 0.03; // 병력 한도 넘어선 후 batch에서 깎아버릴 양 (3%)

const OCCUPATION_INFORM_PERIOD = 259200;	//72시간
const OCCUPATION_INFORM_READY = 43200;	//12시간
const LORD_MIN_POWER =1;

const HERO_TRADE_SELL_LIST_COUNT = 13;
const HERO_TRADE_LIST_COUNT = 10;
const HERO_TRADE_MAX_BID_COUNT = 30;

const FORCE_RELATION_SAME = 20;
const FORCE_RELATION_GOOD = 10;
const FORCE_RELATION_BAD = -20;
const FORCE_RELATION_OTHER = 0;

const HERO_COMBI_FORCE_RELATION_SAME = 6;
const HERO_COMBI_FORCE_RELATION_GOOD = 3;
const HERO_COMBI_FORCE_RELATION_BAD = -6;
const HERO_COMBI_FORCE_RELATION_OTHER = 0;

const FRIEND_INVITE = 30; // 친구 초대 최대 횟수
const FRIEND_GIFT = 30; // 친구 선물하기 최대 횟수
const FRIEND_REQUEST = 30; // 친구 조르기 최대 횟수
const FRIEND_UPDATE_TERM = 10800; // 친구 목록 동기화 간격 (3시간 = 10800)
const ACCO_LORD_INFO_UPDATE_TERM = 10800; // 계정 대표 서버 군주 정보 동기화 간격 (3시간 = 10800)
const INVITED_CASH = 100;
const OFFICER_COUNT_MAX = 110130;
const WEEKLY_EVENT_VER = '120104';
const SENDCONNECT_TOAST_MSG_TERM = 3600; // 접속 알림 토스트 메시지 간격

const RESOURCE_LIMIT_GOLD = 999999999; // 황금 자원 최대 상한

//요청 현황 리스트
const REQUEST_LIST_PAGE_NUM = 5;    //  리스트 한페이지당 갯수

const GACHAPON_INFINITY_MODE = false;
const NEW_GACHAPON_INFINITY_MODE = false;

const NS_DIALOG_EXCEPT = ['build_Template']; // 제외가 필요한 다이어로그

// const CONF_CHECK_PATH_URL = ['/', '/api/i18n', '/api/auth/requestToken', '/api/auth/requestToken', '/api/qbig/payment', '/dev/login', '/dev/signature', '/redirect'];
const CONF_CHECK_PATH_URL = ['/api/auth/connect', '/api/server/list', '/api/start/session', '/api/start/lordCreate'];

// API용 Secret Key
const API_SECRET_KEY = '6gUvPcNQuXatvzDpaHhCPzgNUcahqD2c'; // TODO 차후 API는 토큰 방식으로?