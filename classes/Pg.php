<?php

class Pg
{
    protected string $alias = '';
    protected string $transaction = '';
    protected PgSql\Connection $connection;

    protected string $query_string;
    protected string $duration;

    public Pgsql\Result|bool $query_result;
    public array|bool $row;
    public array $rows;

    public function __construct($_alias, $_options = "")
    {
        $host = constant($_alias. '_PGSQL_IP');
        $port = constant($_alias. '_PGSQL_PORT');
        $database = constant($_alias. '_PGSQL_DB');
        $user = constant($_alias. '_PGSQL_USER');
        $password = constant($_alias. '_PGSQL_PASS');
        $persistent = constant($_alias. '_PGSQL_PERSISTENT');

        $this->connect($host, $port, $database, $user, $password, $persistent, $_options);
        $this->alias = $_alias;
        $this->transaction = false;
    }

    public function connect ($_host, $_port, $_database, $_user, $_password, $_persistent = false, $_options = ''): void
    {
        $connection_string = "host={$_host} port={$_port} dbname={$_database} user={$_user} password={$_password} {$_options}";
        try {
            $this->connection = ($_persistent) ? @pg_pconnect($connection_string) : @pg_connect($connection_string);
        } catch (Throwable $e) {
            throw new ErrorHandler('error', "DB Connect Error. {$e->getMessage()}", true);
        }
    }

    public function connection (): PgSql\Connection
    {
        return $this->connection;
    }

    public function begin(): bool
    {
        if ($this->transaction) {
            return true;
        }
        try {
            $this->query('BEGIN');
            $this->transaction = true;
            return true;
        } catch (Throwable $e) {
            $this->transaction = false;
            return false;
        }
    }

    public function rollback(): void
    {
        if ($this->transaction) {
            $this->query('ROLLBACK');
            $this->transaction = false;
        }
    }

    public function commit(): void
    {
        if ($this->transaction) {
            $this->query('COMMIT');
            $this->transaction = false;
        }
    }

    public function query ($_query_string, $_query_params = null): true
    {
        $this->duration = 0;
        $this->query_string  = $_query_string;

        $start_time = 0;
        if (CONF_DEBUG_SLOW_QUERY) {
            $start_time = Useful::microtimeFloat();
        }

        try {
            $this->query_result = (! $_query_params) ? pg_query($this->connection, $this->query_string) : pg_query_params($this->connection, $this->query_string, $_query_params);
            if ($this->query_result === false) {
                throw new Exception();
            }
        } catch (Throwable $e) {
            throw new ErrorHandler('error', "{$e->getCode()} DB Query Error. {$e->getMessage()}. $this->query_string", true);
        }

        if (CONF_DEBUG_SLOW_QUERY) {
            $end_time = Useful::microtimeFloat();
            $this->duration = $end_time - $start_time;
            if ($this->duration > CONF_DEBUG_SLOW_QUERY_LIMIT) {
                Debug::debugMessage('slow', 'slow query;query['.$this->query_string.']params['. json_encode($_query_params). '];duration['.$this->duration.'];', true);
            }
        }
        return true;
    }

    public function getNumRows (): int
    {
        try {
            return pg_num_rows($this->query_result);
        } catch (Throwable $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']query['.$this->query_string.']', true);
        }
    }

    public function getAffectedRows (): int
    {
        try {
            return pg_affected_rows($this->query_result);
        } catch (Throwable $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']query['.$this->query_string.']', true);
        }
    }

    public function fetch (): bool
    {
        try {
            if (! $this->query_result) {
                Debug::debugLogging('오류난다?');
            }
            $this->row = pg_fetch_assoc($this->query_result);
            return is_array($this->row);
        } catch (Throwable $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']query['.$this->query_string.']', true);
        }
    }

    public function fetchAll (): int
    {
        try {
            $this->rows = [];
            while($row = pg_fetch_assoc($this->query_result)) {
                $this->rows[] = $row;
            }
            return count($this->rows);
        } catch (Throwable $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']query['.$this->query_string.']', true);
        }
    }

    public function fetchOne ()
    {
        try {
            $this->row = pg_fetch_row($this->query_result);
            return $this->row[0] ?? null;
        } catch (Throwable $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']query['.$this->query_string.']', true);
        }
    }

    function currSeq($_sequence_name): int
    {
        try {
            $this->query("SELECT currval('$_sequence_name'::regclass)");
            return $this->fetchOne();
        } catch (Throwable $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']query['.$this->query_string.']', true);
        }
    }

    function nextSeq($_sequence_name): int
    {
        try {
            $this->query("SELECT nextval('$_sequence_name'::regclass)");
            return $this->fetchOne();
        } catch (Throwable $e) {
            throw new ErrorHandler('error', $e->getMessage().'function['.__FUNCTION__.']code['.$e->getCode().']query['.$this->query_string.']', true);
        }
    }

    function getDuration (): string
    {
        return $this->duration;
    }
}