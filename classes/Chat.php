<?php
/*
 * TODO 채팅관련 코드는 아예 새로 짜자. Socket.io 최신버전으로.
 */
class Chat
{
    protected Session $Session;
    protected Pg $PgGame;
    protected RedisCache $Redis;
    protected Cache $Cache;
    protected Lord $Lord;
    protected array $keys;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->Redis = new RedisCache(); // redis
        $this->Cache = new Cache(); // memcached
        $this->keys = [
            'token' => "socket:token:"
        ];
    }

    protected function classLord(): void
    {
        if (! isset($this->Lord)) {
            $this->Lord = new Lord($this->Session, $this->PgGame);
        }
    }

    public function findSid ($_lord_pk, $_posi_pk = null): string
    {
        if (! isset($_posi_pk)) {
            $this->PgGame->query('SELECT main_posi_pk FROM lord WHERE lord_pk = $1', [$_lord_pk]);
            $this->PgGame->fetch();
            $_posi_pk = $this->PgGame->row['main_posi_pk'];
        }
        $_sid_key = $_lord_pk. '_'. $_posi_pk;
        return $this->Cache->get($_sid_key);
    }

    public function setChatSession ($_lord_info): void
    {
        try {
            $sid = $this->findSid($_lord_info['lord_pk'], $_lord_info['main_posi_pk']);
            $redis_key = "{$this->keys['token']}$sid";
            $this->Redis->hSet($redis_key, 'lord_pk', $_lord_info['lord_pk']);
            $this->Redis->hSet($redis_key, 'alli_pk', $_lord_info['alli_pk']);
            $this->Redis->hSet($redis_key, 'lord_name', $_lord_info['lord_name']);
            $this->Redis->hSet($redis_key, 'is_chat_blocked', $_lord_info['is_chat_blocked']);
            $this->Redis->hSet($redis_key, 'server_pk', GAME_SERVER_PK);
            $this->Redis->expire($redis_key, 300);
        } catch (Throwable $e) {
            throw new ErrorHandler('error', $e->getMessage(), true);
        }
    }

    public function updateChatSession ($_lord_info, $hash_key, $_value): bool
    {
        try {
            $sid = $this->findSid($_lord_info['lord_pk'], $_lord_info['main_posi_pk']);
            $redis_key = "{$this->keys['token']}$sid";
            $this->Redis->hSet($redis_key, $hash_key, $_value);
            $this->Redis->expire($redis_key, 300);
        } catch (Throwable $e) {
            return false;
        }
        return true;
    }
}