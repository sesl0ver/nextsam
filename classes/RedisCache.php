<?php

class RedisCache
{
    protected Redis $redis;

    public function __construct()
    {
        try {
            $this->redis = new Redis();
            $this->redis->connect(REDIS_HOST, REDIS_PORT);
            $this->redis->auth(REDIS_PASS);
            $this->redis->select(REDIS_DB);
        } catch (RedisException $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']', true);
        }
    }

    public function set($_key, $_value, $_option = null): bool|Redis
    {
        try {
            return $this->redis->set($_key, $_value, $_option);
        } catch (RedisException $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']', true);
        }
    }

    public function get($_key): mixed
    {
        try {
            return $this->redis->get($_key);
        } catch (RedisException $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']', true);
        }
    }

    public function del($_key): false|int|Redis
    {
        try {
            return $this->redis->del($_key);
        } catch (RedisException $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']', true);
        }
    }

    public function expire($_key, $_ttl): bool|Redis
    {
        try {
            return $this->redis->expire($_key, $_ttl);
        } catch (RedisException $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']', true);
        }
    }

    public function hSet ($key, $hash_key, $_value): bool|int|Redis
    {
        try {
            return $this->redis->hSet($key, $hash_key, $_value);
        } catch (RedisException $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']', true);
        }

    }

    public function hGet ($key, $hash_key): false|Redis|string
    {
        try {
            return $this->redis->hGet($key, $hash_key);
        } catch (RedisException $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']', true);
        }

    }

    public function hDel ($key, $hash_key): bool|Redis|int
    {
        try {
            return $this->redis->hDel($key, $hash_key);
        } catch (RedisException $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']', true);
        }

    }

    public function hGetAll ($key): false|Redis|array
    {
        try {
            return $this->redis->hGetAll($key);
        } catch (RedisException $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']', true);
        }
    }

    public function incr(string $_key, int $_by = 1): int
    {
        try {
            return $this->redis->incr($_key, $_by);
        } catch (RedisException $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']', true);
        }
    }

    public function decr($_key, $_by = 1): int
    {
        try {
            return $this->redis->decr($_key, $_by);
        } catch (RedisException $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']', true);
        }
    }

    public function zAdd($_key, $_score, $_name): int
    {
        try {
            return $this->redis->zAdd($_key, $_score, json_encode($_name));
        } catch (RedisException $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']', true);
        }
    }

    public function zRange($_key, $_start = 0, $_end = -1): array
    {
        try {
            return $this->redis->zRange($_key, $_start, $_end);
        } catch (RedisException $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']', true);
        }
    }

    public function lPush ($_key, $_value): false|Redis|int
    {
        try {
            return $this->redis->lPush($_key, $_value);
        } catch (RedisException $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']', true);
        }
    }

    public function publish ($channels, $message): int|false|Redis
    {
        try {
            return $this->redis->publish($channels, $message);
        } catch (RedisException $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']', true);
        }
    }
}