<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// 게임 최초 설치시 1회 실행해주어 값을 생성함.

try {
    // 게임 초기 설정에 필요한 값들
    $Cache = new Cache();

    $Cache->flush();
    $Cache->add('__SERVER_MAINTENANCE', 'N');
    $Cache->add('__SERVER_MAINTENANCE_ACCESS_ALLOW_IP', '');
    $keys = $Cache->getAllKeys();
    foreach ($keys as $key) {
        echo "$key\n";
    }
} catch (Throwable $e) {
    print_r($e);
}