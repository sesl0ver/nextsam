<?php

class Cache
{
    protected Memcached $memcached;

    public function __construct($_alias = 'SESSION')
    {
        $ip = constant($_alias. '_MEMCACHED_IP');
        $port = constant($_alias. '_MEMCACHED_PORT');

        $this->connect($ip, $port);
    }

    function connect ($_ip, $_port): void
    {
        $this->memcached = new Memcached();
        $this->memcached->addServer($_ip, $_port);
    }

    // setter
    function set ($_key, $_value, $_expire = 0): bool
    {
        return $this->memcached->set($_key, $_value, $_expire);
    }

    // getter
    function get ($_key): mixed
    {
        return $this->memcached->get($_key);
    }

    // add (키가 존재하면 오류 발생)
    function add ($_key, $_value): bool
    {
        return $this->memcached->add($_key, $_value);
    }

    // delete
    function del ($_key): bool
    {
        return $this->memcached->delete($_key);
    }

    // incr
    function incr ($_key): false|int
    {
        return $this->memcached->increment($_key);
    }

    // decr
    function decr ($_key): false|int
    {
        return $this->memcached->decrement($_key);
    }

    function getAllKeys (): false|array
    {
        return $this->memcached->getAllKeys();
    }

    function flush (): bool
    {
        return $this->memcached->flush();
    }
}