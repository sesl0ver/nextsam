<?php
ini_set('session.cache_expire', 3600); // 1일
ini_set('session.cookie_lifetime', 3600 * 7); // 7일

use Selective\BasePath\BasePathMiddleware;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constant.php';
require_once __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->add(new BasePathMiddleware($app));
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(CONF_DEBUG_MODE, CONF_DEBUG_MODE, CONF_DEBUG_MODE);

$Render = Render::getInstance();
$NsGlobal = NsGlobal::getInstance();
$i18n = i18n::getInstance();

require_once __DIR__ . '/../config/master_data.php';

// 글로벌 변수 설정
$_START_TIME = Useful::microtimeFloat(); // Debug 시간 체크용
$_NS_SQ_REFRESH_FLAG = false;
$_NS_COMMIT_APPEND_SQ_DATA = [];

$app->get('/', $Render->wrap(function (array $params) use ($Render, $i18n) {

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['building', 'hero_base', 'hero_acquired_rare', 'qbig_pack']);
    $dialog_except = NS_DIALOG_EXCEPT; //
    $dialog_template = scandir(__DIR__ . '/../template/dialog/');
    $css_files = scandir(__DIR__ . '/style/');

    $i18n->setLang((isset($_SESSION['lang'])) ? $_SESSION['lang'] : CONF_I18N_DEFAULT_LANGUAGE);

    $template_cache = [];
    foreach ($dialog_template as $template) {
        if (str_ends_with($template, '.twig')) {
            $template_cache[] = str_replace('.twig', '', $template);
        }
    }

    $style_cache = [];
    foreach ($css_files as $css) {
        if (str_ends_with($css, '.css')) {
            $style_cache[] = str_replace('.css', '', $css);
        }
    }

    $cache_files = scandir(__DIR__ . '/m_/cache/');
    $master_cache = [];
    foreach ($cache_files as $cache_file) {
        if (str_ends_with($cache_file, '.js')) {
            $master_cache[] = $cache_file;
        }
    }

    // 플랫폼 모드라면 토큰을 통해 유저 정보를 받아옴.
    if (CONF_ONLY_PLATFORM_MODE === true && isset($params['uuid'])) {
        $PgCommon = new Pg('COMMON');
        $PgCommon->query('SELECT salt_key FROM account WHERE uid = $1', [$params['uuid']]);
        if ($PgCommon->fetch()) {
            $token = $PgCommon->row['salt_key'];
            if ($token) {
                $decoded = JWT::decode($params['token'], new Key($token, 'HS256'));
                $params = array_merge((ARRAY)$decoded, $params);
            }
        }
    }

    // 설정에 따라 템플릿 선택
    $template = (CONF_FILE_PACKING) ? 'index.twig' : 'index_dev.twig';
    $data = [
        'version' => trim(CONF_WEB_VERSION),
        'platform' => $params['platform'] ?? 'TEST',
        'uuid' => $params['uuid'] ?? null,
        'i18n' => $i18n,
        'dialog_except' => $dialog_except, // 제외할 다이어로그 (보통 twig, css, js 묶음 구성인데 그렇지 않은 경우)
        'dialog_template' => $template_cache,
        '_M' => $_M,
        'PLATFORM_MODE' => (CONF_ONLY_PLATFORM_MODE) ? 'true' : 'false',
        'text_resource' => rawurlencode($i18n->getBundle()),
        'default_chat_host' => CONF_DEFAULT_CHAT_HOST,
        'default_chat_port' => CONF_DEFAULT_CHAT_PORT,
        'resources_cdn' => CONF_RESOURCES_CND
    ];
    if (! CONF_FILE_PACKING) {
        $data['master_cache'] = $master_cache;
        $data['style_cache'] = $style_cache;
    }

    return $Render->template($template, $data);
}));

$app->post('/api/lp', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['cpp']);

    $_PROC_TIME = [];
    $_PROC_TIME['srt'] = time();

    // authorize
    $Session = new Session();

    // SQ 유효성 체크
    $cpp = $params['cpp'];
    $nkey = $Session->lord['lord_pk']. '_'. $cpp;
    $sid = $Session->Cache->get($nkey);
    if (!$sid || strlen($sid) != CONF_COOKIE_SID_LEN) {
        return $Render->view('{ "LP_INVA_0": { "mesg":"sq_check" } }');
    }

    $_LP_LOCK = $sid. '_LP_LOCK';
    $_LP_LOCK_FAIL = $sid. '_LP_LOCK_FAIL';
    $_SQ_CNT = $sid. '_SQ_CNT';
    $_SQ_SEQ = $sid. '_SQ_SEQ';
    $_SQ_SEQ_READ = $sid. '_SQ_SEQ_READ';
    $_SQ_GET_CNT = $sid. '_SQ_GET_CNT';
    $_LP_LATEST = $sid. '_LP_LATEST';

    // LP 자원 점유하기
    $retry_cnt = 0;
    do {
        // retry delay
        if ($retry_cnt > 0) {
            usleep(CONF_LP_DELAY_RETRY_LOCK_U);
        }

        $ret = $Session->Cache->add($_LP_LOCK, 'Y');
        $retry_cnt++;
    } while (!$ret && $retry_cnt < 5);

    // LP 자원 점유실패 로깅 후 종료
    if (! $ret) {
        $Session->Cache->incr($_LP_LOCK_FAIL);
        return $Render->view('{ "LP_INVA_0": { "mesg":"lplock_failed" } }');
    }

    // 실행 지연 (SQ 데이터의 팩화) - TODO 기존에는 GET으로만 받던데 의미가 있는 코드인지?
    if (!isset($params['nd']) || $params['nd'] == 0) {
        usleep(CONF_LP_DELAY_PACK_U);
    }

    //
    ob_start();
    echo '{';
    set_time_limit( 10 );
    $_PROC_TIME['loop'] = time();

    // SQ getter
    $read_cnt = 0;
    for ($k = 0; $k < 1; $k++) {
        $tick = $k;

        // 시간 제한
        if ($k > 0 && $k%10 == 0) {
            if (time()-$_PROC_TIME['srt'] > 50) { // 50초
                break;
            }
        }

        // 가져올 SQ가 있나?
        $cnt = $Session->Cache->get($_SQ_CNT);
        if ($cnt > 0) {
            $sq_seq = $Session->Cache->get($_SQ_SEQ); // 현재 SEQ
            $sq_seq_read = $Session->Cache->get($_SQ_SEQ_READ); // 이전까지 읽은 SEQ
            for ($i = $sq_seq_read+1, $read_cnt = 0; $i <= $sq_seq; $i++) {
                // 읽을 SQ 키
                $key = $sid. '_SQ_'. $i;
                $Session->Cache->incr($_SQ_SEQ_READ);
                $Session->Cache->decr($_SQ_CNT);
                $d = $Session->Cache->get($key);
                $Session->Cache->del($key);

                if ($read_cnt >= 1) {
                    echo ',';
                }

                // 키 중복 방지를 위해서 키에 "_순번" 을 삽입힌다.
                $p = strpos($d, ':');
                $k = substr($d, 0, $p);
                $nk = preg_replace('/"([A-Z0-9_]+)"/', '"$1_'. $i. '"', $k, 1, $rCnt); // 이미 더블쿼터로 감싸있는 경우
                if ($rCnt == 0) {
                    $nk = preg_replace('/([A-Z0-9_]+)/', '"$1_'. $i. '"', $k, 1); // 평문으로 온 경우, 강제 감싸기
                }
                echo $nk; // 출력
                echo substr($d, $p); // ':' 이후 끝까지
                $read_cnt++;
            }

            // 무한 LP failover 용
            if ($read_cnt > 0) {
                $Session->Cache->incr($_SQ_GET_CNT); // 지금까지 LP한 횟 수
                // 팩된 데이터를 클라이언트로 전송하기 위해 루핑 종료
                break;
            } else {
                $Session->Cache->set($_SQ_CNT, 0);
            }
        }

        // SQ가 없을 때 재탐색 대기타임
        usleep(CONF_LP_DELAY_RETRY_CHECK_SQ_U);

        // LP 자원 점유 유지여부 체크
        if (!$Session->Cache->get($_LP_LOCK)) {
            $Render->view('"LP_INVA_0": { "mesg":"lplock_failed_in_loop" }');
        }
    }

    $_PROC_TIME['end'] = time();

    if ($read_cnt && $read_cnt >= 1) {
        echo ',';
    }

    $z = $_PROC_TIME['end'] - $_PROC_TIME['loop'];
    if ($z < 10) {
        $tick_avg = 0;
    } else {
        $tick_avg = $z/$tick;
    }
    echo '"PROC_TIME_0": { "srt":"'. $_PROC_TIME['srt']. '", "loop":"'. ($_PROC_TIME['loop']-$_PROC_TIME['srt']). '", "end":"'. ($_PROC_TIME['end']-$_PROC_TIME['srt']). '", "tick":"'. ($tick). '", "tick_avg":"'. $tick_avg. '" }';

    // JSON 종료 태그
    echo '}';
    $cont = ob_get_contents();
    ob_end_clean();

    $Session->Cache->set($_LP_LATEST, time());
    $Session->Cache->set($Session->lord['lord_pk'], $cpp, CONF_LP_EXPIRE_S);

    // LP 자원 점유해제
    $Session->Cache->del($_LP_LOCK);

    $PgGame = new Pg('DEFAULT');
    $PgGame->query('UPDATE lord SET last_lp_dt = now(), is_logon = $1 WHERE lord_pk = $2', ['Y', $Session->lord['lord_pk']]);

    return $Render->view($cont);
}));

// 테스트
$app->get('/redirect', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $homepage_url = match ($params['platform']) {
        'GAMEMANIA' => 'https://3k.gamemania.co.kr',
        'ONGATE' => 'https://3k.ongate.com',
        'HANGAME' => 'https://3k.hangame.com',
        default => 'https://www.3kingdoms.co.kr', // HOME
    };

    $url = match ($params['type']) {
        'purchase' => $homepage_url . "/shop",
        'inquiry' => $homepage_url . "/help/question",
        'notice' => $homepage_url . "/board/1",
        // 'open_message_event' => $homepage_url . "/event/detail/1",
        'event_04' => $homepage_url . "/event/detail/1", // 황건적 보상 2배
        'event_05' => $homepage_url . "/event/detail/1", // 일반 조합 확률업
        default => $homepage_url,
    };
    return $Render->redirect($url);
}));

$app->get('/test', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (CONF_DEBUG_MODE !== true) {
        return $Render->redirect('/', 302);
    }
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Quest = new Quest($Session, $PgGame);

    $lord_pk = 10;

    $Quest->countCheckQuest($lord_pk, 'EVENT_TRAINING', ['value' => 1]);

    return $Render->nsXhrReturn('success');
}));

// 임시 로그인 용 API
$app->get('/dev/login', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (CONF_DEBUG_MODE !== true && CONF_ONLY_PLATFORM_MODE === true) {
        return $Render->redirect('/', 302);
    }

    return $Render->template('login_dev.twig', []);
}));

$app->post('/dev/signature', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (CONF_DEBUG_MODE !== true && CONF_ONLY_PLATFORM_MODE === true) {
        return $Render->redirect('/', 302);
    }

    try {
        $PgCommon = new Pg('COMMON');
        $PgCommon->query('SELECT access_by FROM account WHERE uid = $1', [$params['uuid']]);
        if (! $PgCommon->fetch()) {
            throw new ErrorHandler('error', '해당 유저를 찾지 못하였습니다.');
        }
        // $lc = $PgCommon->row['access_by'];
    } catch (Throwable $e) {
        return $Render->nsXhrReturn('error', $e->getMessage());
    }

    $signature = md5(CONF_PLATFORM_SECRET_KEY . '#'. $params['request_id'] . '#' . $params['uuid']);

    return $Render->nsXhrReturn('success', null, ['signature' => $signature]);
}));




require_once __DIR__ . "/../router/admin/tools.php";
require_once __DIR__ . "/../router/admin/gm_tool.php";

$router_files = scandir(__DIR__ . '/../router/');
foreach ($router_files as $router_file) {
    if (str_ends_with($router_file, '.php')) {
        require_once __DIR__ . "/../router/$router_file";
    }
}

$app->run();