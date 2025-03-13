<?php

class Push
{
    public Session $Session;
    public Pg $PgGame;
    public Cache $Cache;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->Cache = new Cache();
    }

    // TODO 사실 이건 이제는 안쓰는 클래스인데...
    function send($_type, $_info = ' ', $_lord_pk = null, $_posi_pk = null): bool
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }
        $this->PgGame->query('SELECT t1.pn_token, t1.pn_report_t1, t1.pn_report_t2, t1.pn_advice_t1, t1.pn_advice_t2, t1.pn_terr_t1, t1.pn_terr_t2, t1.pn_event_t1, t1.pn_event_t2, t1.pn_night_srt, t1.pn_night_end, t2.platform FROM game_option t1, lord t2 WHERE t1.lord_pk = t2.lord_pk AND t1.lord_pk = $1', [$_lord_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }

        // 옵션에 대한 처리
        $ts = 21;
        $te = 8;
        $hour = date('H', strtotime('+9 Hours')); // UTC 대응
        $col = 't1';

        if ($ts < $te) {
            if ($hour >= $ts && $hour < $te) {
                $col = 't2';
            }
        } else if ($ts > $te) {
            if ($hour >= $ts || $hour < $te) {
                $col = 't2';
            }
        }
        $col = 'pn_'. $this->getSubType($_type) . '_'. $col;
        if ($this->PgGame->row[$col] == 'N') {
            return false;
        }

        // 군주에 대입하는 ftoken 모두에게 Push
        /*$this->PgGame->query('SELECT ftoken FROM lord_fToken WHERE lord_pk = $1', [$_lord_pk]);
        $this->PgGame->fetchAll();
        $this->getRedisClass();
        foreach ($this->PgGame->rows AS $row) {
            $push_data = [
                'fToken' => $row['ftoken'],
                'title' => '소셜삼국 리부트',
                'body' => sprintf($this->getMessage($_type), $_info),
                'push_key' => md5(uniqid())
            ];
            $this->Redis->sAdd('ssr:push', json_encode($push_data));
        }*/

        return true;
    }

    function getMessage($_type): string
    {
        return match ($_type) {
            'build' => '%s 완료되었습니다.',
            'tech' => '%s 기술 연구가 완료되었습니다.',
            'army' => '%s의 훈련이 완료되었습니다.',
            'enco' => '영빈관에 영웅 탐색이 완료되었습니다.',
            'treat' => '부상을 당한 %s의 치료가 완료되었습니다.',
            'raid' => '황건적 요새가 발견되었습니다.',
            'letter' => '다른 군주로부터 서신이 도착했습니다.',
            'newbie' => '초보자 보호 쉴드가 해제되었습니다.',
            'friend' => '추천코드를 입력한 군주가 있다고 합니다.',
            'alliance' => '봉황의구슬이 동맹 선물로 도착했습니다.',
            'detect' => '[긴급] 공격해오는 적 부대가 있습니다.',
            'scout' => '출정 부대의 정찰 보고서가 도착했습니다.',
            'scout2' => '[긴급] 잠입한 적 정찰병이 발견되었습니다.',
            'attack' => '출정 부대의 전투 보고서가 도착했습니다.',
            'defence' => '방어 전투 결과를 확인해주십시오.',
            default => '',
        };
    }

    function getSubType($_type): string
    {
        return match ($_type) {
            'build', 'tech', 'army', 'enco', 'treat', 'raid' => 'terr',
            'letter', 'newbie', 'friend', 'alliance' => 'advice',
            'detect', 'scout', 'scout2', 'attack', 'defence' => 'report',
            default => '',
        };
    }
}