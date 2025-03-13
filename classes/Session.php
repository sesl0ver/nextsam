<?php

class Session
{
    public Cache $Cache;
    protected Pg $PgGame;
    protected bool $need_session = false;
    protected bool $is_cookie = false;
    protected string|null $sid = null;
    protected string $latest;
    protected string $posi_pk;
    public string $web_channel = '1'; // TODO 차후 함수 get 으로 빼자
    protected string $last_chat_conn;
    protected array $push_data = [];

    public array|bool|null|string $lord; // TODO 차후 함수 get 으로 빼자
    public bool $is_login = false; // TODO 차후 함수 get 으로 빼자
    public bool $is_valid = true; // TODO 차후 함수 get 으로 빼자

    public function __construct($_need_session = true, $_pass_sid_check = false) // $_pass_posi_check = false
    {
        $this->need_session = $_need_session;
        $this->Cache = new Cache('SESSION');

        $mt = $this->Cache->get('__SERVER_MAINTENANCE');

        if (trim($mt) === 'Y') {
            $allow = $this->Cache->get('__SERVER_MAINTENANCE_ACCESS_ALLOW_IP');
            $allow_ip_arr = explode(';', trim($allow));
            foreach ($allow_ip_arr as $k => $v) {
                if (is_null($v) || $v === "") {
                    unset($allow_ip_arr[$k]);
                }
            }
            $is_allow = false;
            if (count($allow_ip_arr) > 0) {
                $user_ip_addr = $this->getRealClientIp();
                foreach($allow_ip_arr as $v) {
                    if (preg_match('/'.str_replace('*', '[\d]{1,3}', $v).'/', $user_ip_addr)) {
                        $is_allow = true;
                    }
                }
            }

            if ($is_allow !== true) {
                $this->is_valid = false;
                throw new ErrorHandler('error_mt', 'Server maintenance is currently in progress.');
            }
        }

        // dispatcher 등에서 사용시
        if ($_need_session === false) {
            return;
        }

        global $NsGlobal, $Render;
        $Render->setSession($this); // sq 처리를 위해 추가.
        $_params = $NsGlobal->getParamsData();

        if (CONF_WEB_VERSION_CHECK && isset($_params['ns_web_version']) && $_params['ns_web_version'] !== CONF_WEB_VERSION) {
            $this->is_valid = false;
            throw new ErrorHandler('error_update', 'Client version update is required, please refresh the webpage.');
        }

        // TODO 여기에 sid를 체크하는 의미가 있나?
        $_sid = $this->getSid();

        if ($_sid === false) {
            $this->setSID();
            $this->is_cookie = true;
            $this->is_valid = false;
            if ($_pass_sid_check !== true) {
                throw new ErrorHandler('error', 'Not Found Session.');
            } else {
                return;
            }
        }
        $this->is_cookie = true;
        $this->sid = $_sid;
        $this->lord = $this->Cache->get($this->sid);
        $duplication = $this->Cache->get($this->sid . '_DUPLICATION') ?? 0;

        $posi_pk = (isset($_params['posi_pk'])) ? $_params['posi_pk'] : null;
        $posi_pk = (isset($_params['cpp'])) ? $_params['cpp'] : $posi_pk;

        // 로그인한 유저
        try {
            if ($duplication) {
                $this->Cache->del($this->sid . '_DUPLICATION'); // 중복 접속 확인키 제거
                throw new Exception('duplication');
            }
            if (! $this->lord) {
                // $NsGlobal->setErrorMessage('Not Found Lord Session.');
                throw new Exception('ign');
            }
            $this->is_login = true;
            $this->latest = $this->Cache->get($this->sid. '_LATEST');
            $this->Cache->set($this->sid. '_LATEST', time()); // 즉시 갱신
            $this->posi_pk = $this->Cache->get($this->sid. '_POSI_PK');
            $this->web_channel = $this->Cache->get($this->sid. '_WEB_CHANNEL');
            $this->last_chat_conn = $this->Cache->get($this->sid. '_LAST_CHAT_CONN');
            /*if ($this->posi_pk != $posi_pk && !$_pass_posi_check) {
                $this->is_valid = false;
                $NsGlobal->setErrorMessage('Invalid territory information.');
                throw new Exception('error_update');
            }*/
        } catch (Throwable $e) {
            $this->is_valid = false;
            throw new ErrorHandler($e->getMessage(), $NsGlobal->getErrorMessage(), $e->getMessage() != 'ign' && $e->getMessage() != 'duplication');
        }
    }

    function getRealClientIp() {
        $user_ip_addr = $_SERVER['REMOTE_ADDR'];
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $user_ip_addr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $user_ip_addr = trim($user_ip_addr[0]);
            $user_ip_addr = (!$user_ip_addr) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $user_ip_addr;
        }
        return $user_ip_addr;
    }

    // setREFER
    function setREFER()
    {
        // TODO 사용하지 않는 함수...
        //setCookie('REFER', $_SERVER['HTTP_REFERER']);
    }

    // getREFER
    function getREFER(): null
    {
        // TODO 얘도 안쓰는네...
        // return $_COOKIE['REFER'];
        return null;
    }

    function setSid($_sid = null): void
    {
        if (! $_sid) {
            $this->sid = md5(Useful::microtimeFloat());
        } else {
            $this->sid = $_sid;
        }
    }

    function getSid (): string|bool
    {
        global $NsGlobal;
        $params = $NsGlobal->getParamsData();
        if (isset($params[CONF_COOKIE_SID_NAME]) && strlen($params[CONF_COOKIE_SID_NAME]) === CONF_COOKIE_SID_LEN) {
            return $params[CONF_COOKIE_SID_NAME];
        } else if (isset($this->sid) && strlen($this->sid) === CONF_COOKIE_SID_LEN) {
            return $this->sid; // 이미 sid를 발급 받은 경우.
        } else {
            return false;
        }
    }

    public function getPosiPk (): string
    {
        return $this->posi_pk ?? '';
    }

    // setLogin
    function setLogin ($_lord_data): bool|Redis
    {
        if (! isset($this->sid)) {
            return false;
        }
        $r = $this->Cache->set($this->sid, $_lord_data);
        if ($r) {
            $this->lord = $_lord_data;
            if ($this->need_session) {
                $this->is_login = true;
            }
        }
        return $r;
    }

    public function setLoginReload (): bool
    {
        if (! isset($this->PgGame)) {
            $this->PgGame = new Pg('DEFAULT');
        }
        $this->PgGame->query('SELECT '. LORD_SESSION_COLUMN. ' FROM lord WHERE lord_pk = $1', [$this->lord['lord_pk']]);
        $this->PgGame->fetch();
        $r = $this->setLogin($this->PgGame->row);
        if ($r) {
            $this->sqAppend('LORD', $this->PgGame->row);
        }
        return true;
    }

    // delLogin()
    function delLogin(): false|int|Redis
    {
        return $this->Cache->del($this->sid);
    }

    // sqInit
    function sqInit($_posi_pk, $_change = false): bool
    {
        if (! isset($this->sid)) {
            return false;
        }
        $_LP_LOCK = $this->sid. '_LP_LOCK';
        $_LP_LOCK_FAIL = $this->sid. '_LP_LOCK_FAIL';
        $_SQ_CNT = $this->sid. '_SQ_CNT';
        $_SQ_SEQ = $this->sid. '_SQ_SEQ';
        $_SQ_LAST = $this->sid. '_SQ_LAST';
        $_SQ_SEQ_READ = $this->sid. '_SQ_SEQ_READ';
        $_SQ_GET_CNT = $this->sid. '_SQ_GET_CNT';
        $_LP_LATEST = $this->sid. '_LP_LATEST';

        // SQ가 활성화 되어 있으면 종료 ** 반드시 찾은 $sid 에 대해서 종료 해야 한다 **
        $posi_pk = $this->Cache->get($this->lord['lord_pk']);
        $user_key = $this->lord['lord_pk']. '_'. $posi_pk;
        $old_sid = $this->Cache->get($user_key);
        if ($posi_pk) {
            // sid 이미 존재하는 경우
            if ($old_sid && strlen($old_sid) == CONF_COOKIE_SID_LEN) {
                // LP_DUPL 날리기 (받으면 접속 종료함)
                /* 영지 변경은 없어졌으므로 제거.
                 * */
                if (! $_change) {
                    $this->Cache->set($old_sid . '_DUPLICATION', 1, 300); // 5분 후 제거
                }
                usleep(CONF_LP_DELAY_PACK_U);

                // SID_LP_LOCK 삭제 / SID와 매칭하는 lord_pk 키 삭제
                $this->sqClear($old_sid);

                if (!$_change) {
                    usleep(CONF_LP_DELAY_PACK_U);
                }
            }
        }

        $user_key = $this->lord['lord_pk']. '_'. $_posi_pk;

        $r = [];
        $this->Cache->del($_LP_LOCK); // 이거는 없으면 실패하는게 당연해서 제외
        $r[] = $this->Cache->set($_SQ_GET_CNT, 0);
        $r[] = $this->Cache->set($_SQ_SEQ_READ, 0);
        $r[] = $this->Cache->set($_SQ_SEQ, 0);
        $r[] = $this->Cache->set($_SQ_LAST, 0);
        $r[] = $this->Cache->set($_SQ_CNT, 0);
        $r[] = $this->Cache->set($this->lord['lord_pk'], $_posi_pk); // 로딩 시간을 고려
        $r[] = $this->Cache->set($user_key, $this->sid);
        $r[] = $this->Cache->set($_LP_LOCK_FAIL, 0);
        $r[] = $this->Cache->set($_LP_LATEST, time());

        for ($i = 0, $i_l = COUNT($r); $i < $i_l; $i++) {
            if (!$r[$i]) {
                return false;
            }
        }

        // 잠시 쉬기
        if ($old_sid && strlen($old_sid) == CONF_COOKIE_SID_LEN) {
            usleep(CONF_LP_DELAY_PACK_U);
        }

        return true;
    }

    function sqClear ($_sid): void
    {
        if (! $_sid) {
            return;
        }
        $lord_info = $this->Cache->get($_sid);
        $user_key = $this->Cache->get($lord_info['lord_pk'] . '_' . $lord_info['main_posi_pk']);

        $_LP_LOCK = $_sid. '_LP_LOCK';
        $_LP_LOCK_FAIL = $_sid. '_LP_LOCK_FAIL';
        $_SQ_CNT = $_sid . '_SQ_CNT';
        $_SQ_SEQ = $_sid . '_SQ_SEQ';
        $_SQ_LAST = $_sid . '_SQ_LAST';
        $_SQ_SEQ_READ = $_sid . '_SQ_SEQ_READ';
        $_SQ_GET_CNT = $_sid . '_SQ_GET_CNT';
        $_LP_LATEST = $_sid . '_LP_LATEST';
        $_LATEST = $_sid . '_LATEST';
        $_POSI_PK = $_sid . '_POSI_PK';

        $this->Cache->del($this->lord['lord_pk']);
        $this->Cache->del($_POSI_PK);
        $this->Cache->del($_LATEST);

        $this->Cache->del($_LP_LOCK);
        $this->Cache->del($_SQ_GET_CNT);
        $this->Cache->del($_SQ_SEQ_READ);
        $this->Cache->del($_SQ_SEQ);
        $this->Cache->del($_SQ_LAST);
        $this->Cache->del($_SQ_CNT);
        $this->Cache->del($user_key);
        $this->Cache->del($_LP_LOCK_FAIL);
        $this->Cache->del($_LP_LATEST);
        if (! isset($this->PgGame)) {
            $this->PgGame = new Pg('DEFAULT');
        }
        $this->PgGame->query('UPDATE lord SET is_logon = $1, last_sid = $2, last_logout_dt = now() WHERE lord_pk = $3', ['N', null, $lord_info['lord_pk']]);
        $this->PgGame->query('UPDATE lord_login SET logout_dt = now() WHERE lord_pk = $1 AND login_sid = $2', [$lord_info['lord_pk'], $_sid]);
    }

    function sqAppend($_key, $_data, $_sid = null, $_lord_pk = null, $_posi_pk = null): bool
    {
        /*
         * sqAppend
         * sqAppend('REPORT', 'ok', null, 7)
         *  - lord_pk 7의 posi_pk 를 구하기 없으면 return false
         * sqAppend('REPORT', 'ok', null, 7, '244x163')
         *  - lord_pk 7의 posi_pk 를 구하고 $_posi_pk 매개변수와 비교 틀리면 return false
         */
        global $_NS_SQ_REFRESH_FLAG, $NsGlobal;

        $other = false;
        if ($_lord_pk && isset($this->lord['lord_pk']) && $this->lord['lord_pk'] != $_lord_pk) {
            $other = true;
        }
        if (! $other) {
            // commit 완료 후 sq 보내기
            if ($_NS_SQ_REFRESH_FLAG && $_key != 'PUSH') {
                $NsGlobal->commitAppendSqData($this, 'sqAppend', [$_key, $_data, $_sid, $_lord_pk, $_posi_pk]);
                return true;
            }

            if (! $_lord_pk && ! $_sid && (! $this->is_login && $this->need_session)) {
                return false;
            }

            /* ************************************************** */
            if ($this->is_login) {
                $this->addPushData($_key, $_data);
                return true;
            }
        }

        // lord_pk 로 sid 찾기
        if ($_lord_pk) {
            $z = $this->Cache->get($_lord_pk);
            if ($_posi_pk) {
                if ($_posi_pk != $z) {
                    return false;
                }
            } else {
                $_posi_pk = $z;
            }

            $nkey = $_lord_pk. '_'. $_posi_pk;
            $_sid = $this->Cache->get($nkey);

            if ($_sid && strlen($_sid) == CONF_COOKIE_SID_LEN) {
                // TODO 왜 비워뒀냐...
            } else {
                return false;
            }
        }

        if (! $_sid) {
            $_sid = $this->sid;
        }
        if (! $_sid) {
            return false;
        }

        $_SQ_SEQ = $_sid. '_SQ_SEQ';
        $_SQ_CNT = $_sid. '_SQ_CNT';

        $seq = $this->Cache->incr($_SQ_SEQ);
        $this->Cache->set($_sid. '_SQ_'. $seq, '"'. $_key. '":'. json_encode($_data));
        $this->Cache->incr($_SQ_CNT);

        return true;
    }

    function setCurrentPosition($_posi_pk): true
    {
        $this->Cache->set($this->sid . '_POSI_PK', $_posi_pk);
        return true;
    }

    function setLoginWebChannel($_lord_pk): true
    {
        if (! isset($this->PgGame)) {
            $this->PgGame = new Pg('DEFAULT');
        }
        $this->PgGame->query('SELECT web_channel FROM lord_web WHERE lord_pk = $1', [$_lord_pk]);
        $web_channel = $this->PgGame->fetchOne();
        $this->Cache->set($this->sid . '_WEB_CHANNEL', $web_channel);
        return true;
    }

    function addPushData($_key, $_val): void
    {
        $this->push_data[] = ['k' => $_key, 'v' => $_val];
    }

    function getPushData($_format = 'raw'): false|array|string
    {
        $res_cnt = 1;
        $res = [];
        $res['PROC_TIME_0'] = Useful::microtimeFloat(); // - $this->time_str TODO 여기 왜 이러냐..
        foreach($this->push_data AS $v) {
            $res[$v['k']. '_' .$res_cnt] = $v['v'];
            $res_cnt++;
        }
        return ($_format == 'json') ? json_encode($res) : $res;
    }

    public function checkLpData(): bool
    {
        $_SQ_CNT = $this->getSid() . '_SQ_CNT';
        $count = $this->Cache->get($_SQ_CNT);
        return ($count > 0);
    }

    public function existsPushData(): bool
    {
        return (COUNT($this->push_data) > 0);
    }

    // posi_pk 를 이용하여 세션내 군주 정보를 업데이트 해야 할 때 사용 (Dispatcher)
    function positionToLord($_posi_pk): bool
    {
        try {
            if (! isset($this->PgGame)) {
                $this->PgGame = new Pg('DEFAULT');
            }
            // posi_pk 로 lord_pk 뽑기
            $this->PgGame->query('SELECT t3.lord_pk, t3.level, t3.position_cnt, t3.lord_enchant FROM position t1, territory t2, lord t3 WHERE t1.posi_pk = t2.posi_pk AND t1.lord_pk = t3.lord_pk AND t1.posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $this->lord = $this->PgGame->row;
            $this->PgGame->query('SELECT web_channel FROM lord_web WHERE lord_pk = $1', [$this->lord['lord_pk']]);
            $this->web_channel = $this->PgGame->fetchOne();

            // 세션 SQ push를 위해서 SID 구하기 (접속 중 일 때 처리를 위해...) - Session 클래스에서 사용
            $sid = $this->Cache->get($this->lord['lord_pk']. '_'. $_posi_pk);
            $this->setSid($sid);

            // 웹 채널 캐싱
            $this->Cache->set($this->sid . '_WEB_CHANNEL', $this->web_channel);
            return true;
        } catch (Throwable $e) {
            // TODO 오류 로그 남기기
            return false;
        }
    }
}