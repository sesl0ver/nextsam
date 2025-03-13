<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');

// TODO 이것만으로는 맴캐시에 남은 잔류 데이터를 모두 청소하진 못함. (메모리 기반이라 크게 상관없지만 그래도 방법을 고민해보자)
$PgGame->query('SELECT lord_pk, last_sid FROM lord WHERE is_logon = $1 AND last_lp_dt < now() - Interval \'+290 second\'', ['Y']); // 원래 290 디버깅을 위해 10으로
$session_list_count = $PgGame->fetchAll();

if ($session_list_count > 0) {
	foreach ($PgGame->rows AS $_session) {
		$sid = $_session['last_sid'];
        $lord_pk = $_session['lord_pk'];
		if ($sid) {
			$_LP_LOCK = $sid. '_LP_LOCK';
			$_LP_LOCK_FAIL = $sid. '_LP_LOCK_FAIL';
			$_SQ_CNT = $sid. '_SQ_CNT';
			$_SQ_SEQ = $sid. '_SQ_SEQ';
            $_SQ_LAST = $sid. '_SQ_LAST';
			$_SQ_SEQ_READ = $sid. '_SQ_SEQ_READ';
			$_SQ_GET_CNT = $sid. '_SQ_GET_CNT';
			$_LP_LATEST = $sid. '_LP_LATEST';

			// 가져올 SQ가 있으면 삭제
			$cnt = $Session->Cache->get($_SQ_CNT);

			if ($cnt > 0) {
				$sq_seq = $Session->Cache->get($_SQ_SEQ); // 현재 SEQ
				$sq_seq_read = $Session->Cache->get($_SQ_SEQ_READ); // 이전까지 읽은 SEQ

				for ($i = $sq_seq_read+1, $read_cnt = 0; $i <= $sq_seq; $i++) {
					// 읽을 SQ 키
					$key = $sid. '_SQ_'. $i;
					$Session->Cache->del($key);
				}
			}

			// 쓰레기 SQ 삭제
			$key = $sid. '_SQ_';
			$Session->Cache->del($key);
			$key = $sid. '_SQ_None';
			$Session->Cache->del($key);

			// 세션 삭제
			$key = $sid;
			$Session->Cache->del($key);
			$key = $sid. '_LATEST';
			$Session->Cache->del($key);

			$key = $sid. '_POSI_PK';
			$posi_pk = $Session->Cache->get($key); // 현재 posi_pk
			$Session->Cache->del($key);

			$Session->Cache->del($_session['lord_pk']);
			$Session->Cache->del($_session['lord_pk']. '_'. $posi_pk);

			$Session->Cache->del($_LP_LOCK);
			$Session->Cache->del($_LP_LOCK_FAIL);
			$Session->Cache->del($_SQ_CNT);
			$Session->Cache->del($_SQ_SEQ);
			$Session->Cache->del($_SQ_LAST); // TODO 안지워주고 있길래 임의로 추가한건데... 문제가되면 삭제하자.
			$Session->Cache->del($_SQ_SEQ_READ);
			$Session->Cache->del($_SQ_GET_CNT);
			$Session->Cache->del($_LP_LATEST);
		}

		$PgGame->query('UPDATE lord SET is_logon = $1, last_sid = $2, last_logout_dt = now() WHERE lord_pk = $3', ['N', null, $_session['lord_pk']]);
        $PgGame->query('UPDATE lord_login SET logout_dt = now() WHERE lord_pk = $1 AND login_sid = $2', [$_session['lord_pk'], $_session['last_sid']]);
	}
}
echo "Destroy Session Count: {$session_list_count}\r\n";

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['session_destroyer']);