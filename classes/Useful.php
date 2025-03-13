<?php

class Useful
{
    public function __construct()
    {
    }

    public static function nowServerTime (Pg|null $PgGame = null): mixed
    {
        if (! $PgGame) {
            $PgGame = new Pg('DEFAULT');
        }
        $PgGame->query('SELECT date_part(\'epoch\', now())::integer');
        return $PgGame->fetchOne();
    }

    public static function microTimeFloat (): string
    {
        list($u_sec, $sec) = explode(" ", microtime());
        return Decimal::set((float)$u_sec)->plus((float)$sec)->getValue();
    }

    public static function gzipOut (string $content): string
    {
        // TODO nginx 에서 직접 gzip 압축을 수행하도록 설정하여서 사용하지 않는 함수
        $HTTP_ACCEPT_ENCODING = $_SERVER["HTTP_ACCEPT_ENCODING"];

        if(str_contains($HTTP_ACCEPT_ENCODING, 'deflate')) {
            $encoding = 'deflate';
        } else if(str_contains($HTTP_ACCEPT_ENCODING, 'x-gzip')) {
            $encoding = 'x-gzip';
        } else if(str_contains($HTTP_ACCEPT_ENCODING, 'gzip')) {
            $encoding = 'gzip';
        } else {
            $encoding = false;
        }
        $size = strlen($content);
        if ($encoding && $size >= 1536) {
            header('Content-Encoding: '.$encoding);
            if ($encoding == 'deflate') {
                $ret_cont =  gzdeflate($content, 2);
            } else {
                $ret_cont = "\x1f\x8b\x08\x00\x00\x00\x00\x00";
                $ret_cont .= substr(gzcompress($content, 2), 0, $size);
            }
            $content =  $ret_cont;
        }
        return $content;
    }

    public static function reservedWord($_str): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['reserved_word']);
        foreach($_M['RESE'] AS $v) {
            if (!strcasecmp($v['word'], $_str)) {
                return false;
            }
        }
        return true;
    }

    public static function forbiddenWord($_str): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['forbidden_word']);
        foreach($_M['FORB'] AS $v) {
            preg_replace('/'.$v['word'].'/i', '', $_str, -1 , $count);
            if ($count) {
                return ['ret' => false, 'str' => $v['word']];
            }
        }
        return ['ret' => true];
    }

    public static function forbiddenWordReplace (string $_str): array|string
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['forbidden_word']);
        foreach($_M['FORB'] AS $v) {
            $str_length = (! function_exists('mb_strlen')) ? strlen($v['word']) : mb_strlen($v['word'], "utf-8");
            $x = str_repeat('*', $str_length);
            $_str = str_replace($v['word'], $x, $_str);
        }
        return $_str;
    }

    public static function readableTime($_time): string
    {
        // TODO 차후에 텍스트는 코드화 하자.
        if ($_time <= 60) {
            $z = '00:' . sprintf('%02d',$_time);
        } else if ($_time <= 3600) {
            $z = sprintf('%02d', floor($_time/60)) . ':' . sprintf('%02d', floor($_time%60));
        } else if ($_time <= 86400) {
            $z = sprintf('%02d', floor($_time/3600)) . ':' . sprintf('%02d', floor($_time%3600/60));
        } else {
            $z = sprintf('%02d', floor($_time/86400)) . 'D ' . sprintf('%02d', floor($_time%86400/3600));
        }
        return $z;
    }

    public static function convertNotAllowHtmlChar($msg): string
    {
        $msg = rawurldecode($msg);
        $msg = str_replace('"', "“", $msg);
        $msg = str_replace("'", "‘", $msg);
        $msg = str_replace("\\", "＼", $msg);
        $msg = str_replace('<', "＜", $msg);
        return str_replace('>', "＞", $msg);
    }


    public static function uniqId($_length = 13): string
    {
        try {
            $bytes = '';
            if (function_exists("random_bytes")) {
                $bytes = random_bytes(ceil($_length / 2));
            } elseif (function_exists("openssl_random_pseudo_bytes")) {
                $bytes = openssl_random_pseudo_bytes(ceil($_length / 2));
            }
            return substr(bin2hex($bytes), 0, $_length);
        } catch (Throwable $e) {
            return '';
        }
    }

    public static function diff(array $old, array $new): array
    {
        $added = Useful::findAddedKeys($old, $new);
        $removed = Useful::findRemovedKeys($old, $new);
        $changed = Useful::findChangedKeys($old, $new);

        return compact('added', 'removed', 'changed');
    }

    private static function findAddedKeys(array $old, array $new): array
    {
        return array_filter($new, function ($key) use ($old) {
            return !array_key_exists($key, $old);
        }, ARRAY_FILTER_USE_KEY);
    }

    private static function findRemovedKeys(array $old, array $new): array
    {
        return array_filter($old, function ($key) use ($new) {
            return !array_key_exists($key, $new);
        }, ARRAY_FILTER_USE_KEY);
    }

    private static function findChangedKeys(array $old, array $new): array
    {
        $changed = array_filter($new, function ($newItem, $key) use ($old) {
            return array_key_exists($key, $old) && $old[$key] != $newItem;
        }, ARRAY_FILTER_USE_BOTH);

        array_walk($changed, function (&$changedItem, $key) use ($old) {
            if (is_array($changedItem) && !is_null($old[$key])) {
                $changedItem = Useful::diff($old[$key], $changedItem);
            } else {
                $changedItem = [
                    'old' => $old[$key],
                    'new' => $changedItem
                ];
            }
        });

        return $changed;
    }

    public static function arrayFind( array $array, callable $callback )
    {
        foreach ( $array as $key => $value ) {
            if ( $callback( $value, $key, $array ) ) {
                return $value;
            }
        }
        return null;
    }

    public static function arrayFindAll( array $array, callable $callback ): array
    {
        $find = [];
        foreach ( $array as $key => $value ) {
            if ( $callback( $value, $key, $array ) ) {
                $find[] = $value;
            }
        }
        return $find;
    }

    public static function getRealClientIp(): string
    {
        $user_ip_addr = $_SERVER['REMOTE_ADDR'];
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $user_ip_addr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $user_ip_addr = trim($user_ip_addr[0]);
            $user_ip_addr = (!$user_ip_addr) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $user_ip_addr;
        }
        return $user_ip_addr;
    }

    public static function getNeedQbig($_remain_time): int
    {
        $minutes = Decimal::set($_remain_time)->mod(3600)->getInt();
        $minute_qbig = Decimal::set($_remain_time)->div(QBIG_TO_SECONDS)->getInt();
        $seconds = Decimal::set($minutes)->mod(QBIG_TO_SECONDS)->getInt();
        if (Decimal::set($seconds)->gt(0)) {
            $minute_qbig++;
        }

        return $minute_qbig;
    }
}