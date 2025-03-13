<?php

class NsGlobal
{
    private static NsGlobal|null $self_cast = null;
    protected array $except_hero_base = [120000, 120001, 120002, 120003, 120004];
    protected array $params_data = [];
    protected string|null $error_message;
    protected array $error_data = [];
    protected bool $error_logging = false;

    public function __construct()
    {
    }

    public static function getInstance (): self
    {
        if (self::$self_cast != null) {
            return self::$self_cast;
        }
        self::$self_cast = new self();
        return self::$self_cast;
    }

    function requireMasterData($_name_array): void
    {
        foreach ($_name_array AS $cache_name) {
            require_once(CONF_CACHE_PATH. 'm_'. $cache_name. '.cache.php');
        }
    }

    public function getExceptHeroBase (): array
    {
        return $this->except_hero_base;
    }

    public function startBatch (Session $Session): void
    {
        $PgGame = new Pg('DEFAULT');
        $lord_pk = $Session->lord['lord_pk'];

        // 바로가기 최근 목적지 30개 이상일 경우 삭제하기
        $PgGame->query('DELETE FROM position_favorite WHERE lord_pk = $1 AND type = $2 AND posi_favo_pk NOT IN 
(SELECT posi_favo_pk FROM position_favorite WHERE lord_pk = $1 AND type = $2 ORDER BY posi_favo_pk DESC LIMIT 30)', [$lord_pk, 'R']);

        // 보관기간 지난 보고서 삭제
        $Report = new Report($Session, $PgGame);
        $Report->init($lord_pk);

        // 보관기간 지난 외교서신 삭제
        $Letter = new Letter($Session, $PgGame);
        $Letter->init($lord_pk);

        // 보관기간이 지난 최근 목적지 삭제
        $Troop = new Troop($Session, $PgGame);
        $Troop->favoriteInit($lord_pk);

        $Alliance = new Alliance($Session, $PgGame);
        $Alliance->getRelation($lord_pk);
        // $Alliance->init($lord_pk); // 보관기간 지난 개인동맹 신청 삭제 - TODO 필요시 추가
        // $Alliance->getAcceptGiftCount($lord_pk); // 동맹 선물 받은것 있는지 체크 - TODO 필요시 추가

        // unread hero count 갱신
        $Hero = new Hero($Session, $PgGame);
        $Hero->setUnreadHeroCnt($lord_pk);
        // $Hero->setUnreadGuestHeroCnt($lord_pk);
        // $Hero->setUnreadOverrankHeroCnt($lord_pk);
        $Hero->checkStrikeHeroes($lord_pk); // 태업 영웅 발생 보고서

        // 토벌령
        $Troop->setNpcSuppress($lord_pk, $Session->lord['level'], $Session->lord['main_posi_pk']);

        // 공동공략 토벌령 (요충지) 서신
        if (CONF_NPC_POINT_ENABLE === true) {
            $npcPoint = new npcPoint($Session, $PgGame);
            $npcPoint->batchLetter();
        }

        // recalc production (valley 포함)
        $Production = new Production($Session, $PgGame);
        $Production->recalculation();

        // 성문 개방 여부에 따른 버프 관리
        $Territory = new Territory($Session, $PgGame);
        $Territory->checkGate();
    }

    public function setErrorMessage (string $_message = '', array $_data = []): void
    {
        $this->error_message = $_message;
        $this->error_data = $_data;
    }

    public function getErrorMessage (): string|null
    {
        return $this->error_message;
    }

    public function setErrorLogging ($_logging): void
    {
        $this->error_logging = $_logging;
    }

    public function getErrorLogging (): bool
    {
        return $this->error_logging;
    }

    public function getErrorData (): array
    {
        return $this->error_data;
    }

    public function setParamsData ($_params = []): array
    {
        return $this->params_data = $_params;
    }

    public function getParamsData (): array
    {
        return $this->params_data;
    }

    public function commitAppendSqData ($_class, $_function, $_param): void
    {
        global $_NS_COMMIT_APPEND_SQ_DATA;
        $_NS_COMMIT_APPEND_SQ_DATA[] = ['class' => $_class, 'function' => $_function, 'param' => $_param];
    }

    public function commitComplete (): void
    {
        //sq append에 대한 처리
        global $_NS_COMMIT_APPEND_SQ_DATA;
        if (isset($_NS_COMMIT_APPEND_SQ_DATA)) {
            foreach($_NS_COMMIT_APPEND_SQ_DATA AS $v) {
                call_user_func_array([$v['class'], $v['function']], $v['param']);
            }
            unset($_NS_COMMIT_APPEND_SQ_DATA);
        }
        // 전역변수 초기화
        global $_NS_SQ_REFRESH_FLAG;
        unset($_NS_SQ_REFRESH_FLAG);
    }
}