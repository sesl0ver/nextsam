<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constant.php';
require_once __DIR__ . '/../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

$callback_queue = [];
$tick_log_count = 0;
$work_count_total = 0;
function tick (): void
{
    global $PgGame, $callback_queue;

    try {
        $PgGame->query('UPDATE timer SET queue_status = \'F\' WHERE status = \'P\' AND queue_status = \'W\' AND end_dt < now() AND failover < 2 RETURNING time_pk, queue_type, callback');
        $PgGame->fetchAll();

        foreach ($PgGame->rows as $row) {
            $callback_queue[$row['time_pk']] = $row;
        }
    } catch (Throwable $e) {
        print_r(['message' => $e->getMessage(), 'trace' => $e->getTrace()]);
        // 로그 기록
        Debug::debugLogging(['message' => $e->getMessage(), 'trace' => $e->getTrace()]);
        $callback_queue = [];
    }
    callbackStart();
}

function callbackStart (): void
{
    global $callback_queue, $work_count, $work_count_total, $tick_log_count;

    $work_count = 0;

    foreach ($callback_queue as $queue) {
        work($queue);
        $work_count++;
        $work_count_total++;
        if ($work_count > 5 || $work_count_total > 20) {
            break;
        }
    }

    $tick_log_count++;
    if ($tick_log_count > 20) {
        $tick_log_count = 0;
    }
    tick();
}

function finishFailure ($_time_pk): void
{
    global $PgGame;
    $PgGame->query('UPDATE timer SET queue_status = \'W\', failover = failover + 1 WHERE time_pk = $1', [$_time_pk]);
}

function work ($_queue): void
{
    $time_pk = $_queue['time_pk'];

    $z = explode(':', $_queue['callback']);
    if (count($z) != 2) {
        finishFailure($time_pk);
        return;
    }

    // TODO 차후 Queue 타입 단어 형태 변경한 후 업데이트 해야함.
    $api = [];
    $api['C'] = 'construct'; // 건설
    $api['T'] = 'technique'; // 연구
    $api['A'] = 'army'; // 훈련
    $api['F'] = 'fortification'; // 내성 함정
    $api['W'] = 'fortificationValley'; // 외부 자원지 함정
    $api['E'] = 'encounter'; // 탐색
    $api['I'] = 'invitation'; // 초빙
    $api['P'] = 'enchant'; // 강화
    $api['B'] = 'buff'; // Buff (아이템)
    $api['D'] = 'truce'; // Truce (아이템)
    $api['X'] = 'troop'; // 부대 관련
    $api['Y'] = 'troop'; // 부대 관련
    $api['S'] = 'delivery'; // 무역장 - 배송완료
    $api['M'] = 'medical'; // 치료
    $api['O'] = 'occupation'; // 점령선포 TODO queue_action = Y 인 경우에만
    $api['R'] = 'finishOverRank'; // 영웅 무능화

    global $callback_queue, $work_count_total, $tick_log_count;

    $client = new GuzzleHttp\Client();
    $work_count_total--;
    try {
        $res = $client->request('GET', $z[0].':'.$z[1].'/dispatcher/'.$api[$_queue['queue_type']] . '?time_pk=' . $time_pk);
        if ($res->getStatusCode() != 200) {
            throw new Exception("The dispatcher request is not 200 Status. time_pk[$time_pk]queue_type[{$_queue['queue_type']}tick_count[$tick_log_count]");
        }
        $chunk = $res->getBody();
        if ($chunk != '[OK]') {
            throw new Exception("The dispatcher request is not [OK]. time_pk[$time_pk]queue_type[{$_queue['queue_type']}tick_count[$tick_log_count]");
        }
        unset($callback_queue[$time_pk]);
    } catch (Throwable $e) {
        print_r(['message' => $e->getMessage(), 'trace' => $e->getTrace()]);
        Debug::debugLogging(['message' => $e->getMessage(), 'trace' => $e->getTrace()]);
        finishFailure($time_pk);
    }
}

tick();