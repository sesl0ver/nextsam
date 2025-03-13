<?php
const CONFIG_TEMPLATES_PATH = __DIR__ . '/../template/'; // 템플릿 폴더 위치
define("CONF_WEB_VERSION", trim(file_get_contents(__DIR__ . '/../version')));
const CONF_CDN_VERSION = CONF_WEB_VERSION;
const CONF_WEB_VERSION_CHECK = true;
const CONF_FILE_PACKING = true; // JS 및 CSS 파일 패킹을 사용 할 것인지
const CONF_DEBUG_MODE = true; // 디버깅 모드 ON/OFF
const CONF_DEBUG_WRITE = true; // 디버깅 로그 파일로 저장
const CONF_DEBUG_ERROR_DISPLAY = false; // 오류 메세지 보기 (개발서버 전용)
const CONF_DEBUG_SLOW_QUERY = true; // 느린 쿼리 로깅
const CONF_DEBUG_SLOW_QUERY_LIMIT = 0.5; // second
const CONF_DEBUG_FILE_ERROR = 'ns_debug_error.log';
const CONF_DEBUG_FILE_SLOW = 'ns_debug_slow.log';
const CONF_DEBUG_FILE_WARNING = 'ns_debug_warning.log';
const CONF_DEBUG_PATH = '/service/log/'; // /service/www 기준
const CONF_CACHE_PATH = __DIR__ . '/../master_data/cache/';
const CONF_TEST_SERVER_PK_ONLY = false;
const CONF_ONLY_PLATFORM_MODE = true;
const CONF_PLATFORM_HOMEPAGE = 'https://platform.homepage';

// 통합 DB 연결 정보
const COMMON_PGSQL_IP = '127.0.0.1';
const COMMON_PGSQL_PORT = 5432;
const COMMON_PGSQL_DB = 'qbecommon';
const COMMON_PGSQL_USER = '###USERID###';
const COMMON_PGSQL_PASS = '###PASSWORD###';
const COMMON_PGSQL_PERSISTENT = true;

// 기본 게임 DB 연결 정보
const DEFAULT_PGSQL_IP = '127.0.0.1';
const DEFAULT_PGSQL_PORT = 5432;
const DEFAULT_PGSQL_DB = 'qbegame';
const DEFAULT_PGSQL_USER = '###USERID###';
const DEFAULT_PGSQL_PASS = '###PASSWORD###';
const DEFAULT_PGSQL_PERSISTENT = true;

// 기본 게임 DB 연결 정보
const LOG_PGSQL_IP = '127.0.0.1';
const LOG_PGSQL_PORT = 5432;
const LOG_PGSQL_DB = 'qbelog';
const LOG_PGSQL_USER = '###USERID###';
const LOG_PGSQL_PASS = '###PASSWORD###';
const LOG_PGSQL_PERSISTENT = true;

// Redis
const REDIS_HOST = '127.0.0.1';
const REDIS_PORT = 6379;
const REDIS_PASS = '###PASSWORD###';
const REDIS_DB = 0;

// 세션 memcached 서버
const SESSION_MEMCACHED_IP = '127.0.0.1';
const SESSION_MEMCACHED_PORT = 11211;
const SESSION_MEMCACHED_PERSISTENT = true;

// 타이머 콜백
const CONF_TIMER_CALLBACK = '127.0.0.1:80';

// 서버 설정
const SERVER_INDEX = 100; // 로그용
const GAME_SERVER_PK = 1;
const GAME_SERVER_NAME = '낙양'; // TODO 차후 Locale에 맞춰 수정필요.
const THIS_SERVER_URL = 'http://192.168.56.102';
const CONF_URL_LP = 'api/lp';

const CONF_PNS_GATEWAY_URL = 'http://192.168.0.24/pns_gateway.php'; // TODO 지금은 사용안함

// LP 및 쿠키 설정 (실제로는 쿠키가 아니라 post data 로 넘어옴)
const CONF_COOKIE_SID_NAME = 'sid';
const CONF_COOKIE_SID_LEN = 32; // LP Lock 불가능시 재시도 대기타임
const CONF_LP_EXPIRE_S = 300; // LP 종료 대기타임 (초단위)
const CONF_LP_DELAY_PACK_U = 750 * 1000; // LP 대기타임
const CONF_LP_DELAY_RETRY_CHECK_SQ_U = 150 * 1000; // LP 재탐색 대기타임
const CONF_LP_DELAY_RETRY_LOCK_U = 200 * 1000; // LP Lock 불가능시 재시도 대기타임

// NPC 요충지 컨텐츠 OPEN 여부
const CONF_NPC_POINT_ENABLE = false;
const CONF_POINT_BATTLE_ALWAYS_POSSIBLE = 'N'; // 요충지 항상 활성화 = Y or N

// 점령치 이벤트 트리거
const CONF_OCCUPATION_POINT_ENABLE = false;

// 플랫폼 Secret Key
const CONF_PLATFORM_SECRET_KEY = 'SECRET_KEY';

// i18n 기본 설정
const CONF_I18N_DEFAULT_LANGUAGE = 'ko';

// 채팅 설정
const CONF_DEFAULT_CHAT_HOST = '127.0.0.1';
const CONF_DEFAULT_CHAT_PORT = 3000;

const CONF_RESOURCES_CND = ''; // CDN URL

const CONF_EVENT_NPC_REWARD_ENABLE = true; // NPC 이벤트 보상 증가 이벤트
const CONF_EVENT_NPC_REWARD_VALUE = 2; // 증가 배수. 기본 2배.