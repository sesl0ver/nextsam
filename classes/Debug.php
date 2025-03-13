<?php
class Debug
{
    public static array $result = [];

    public static function debugMessage ($_type, $_message, bool $_logging = false): void
    {
        if (! CONF_DEBUG_MODE) {
            return;
        }
        $trace = debug_backtrace();

        $debug_data = [];
        foreach ($trace as $_trace) {
            if (! str_contains($_trace['file'], '/vendor/')) {
                $args = [];
                foreach ($_trace['args'] as $_arg) {
                    if (gettype($_arg) === 'string' || gettype($_arg) === 'integer') {
                        $args[] = $_arg;
                    } else if (gettype($_arg) === 'object') {
                        $args[] = get_class($_arg);
                    } else if (gettype($_arg) === 'array') {
                        $args[] = json_encode($_arg);
                    }
                }

                $debug_data[] = [
                    'file' => $_trace['file'],
                    'line' => $_trace['line'],
                    'class' => $_trace['class'] ?? '',
                    'function' => $_trace['function'] ?? '',
                    'args' => $args ?? [],
                ];
            }
        }

        if (CONF_DEBUG_WRITE && $_logging === true) {
            if ($_type === 'slow' && !strpos($_message, 'query')) {
                return;
            }
            $write_file = match($_type) { 'error' => CONF_DEBUG_FILE_ERROR, 'slow' => CONF_DEBUG_FILE_SLOW, default => CONF_DEBUG_FILE_WARNING };
            $f = sprintf('%s%d_%s', CONF_DEBUG_PATH, date('YmdH'), $write_file);
            $new = !file_exists($f);
            $fp = fopen($f, 'a');
            if ($new) {
                chmod($f, 0666);
            }
            if ($fp) {
                fprintf($fp,'%s'. "\r\n", "$_message");
                foreach ($debug_data as $_debug) {
                    fprintf($fp, '%s|%s|%s|%s|%s|%d|%s'. "\r\n", date('Y/m/d H:i:s'), $_type, $_debug['file'] ?? __FILE__, $_debug['class'], $_debug['function'], $_debug['line'], json_encode($_debug['args']));
                }
                fprintf($fp,'%s'. "\r\n", '');
                fclose($fp);
            }
        }
    }

    // 디버그에 사용
    public static function debugLogging ($_log): void
    {
        $_log = (is_array($_log)) ? json_encode($_log, JSON_UNESCAPED_UNICODE) : $_log;
        $f = sprintf('%s%d_%s', CONF_DEBUG_PATH, date('YmdH'), CONF_DEBUG_FILE_WARNING);
        $new = !file_exists($f);
        $fp = fopen($f, 'a');
        if ($new) {
            chmod($f, 0666);
        }
        if ($fp) {
            fprintf($fp,'%s'. "\r\n", $_log);
            fprintf($fp,'%s'. "\r\n", '');
            fclose($fp);
        }
    }

}