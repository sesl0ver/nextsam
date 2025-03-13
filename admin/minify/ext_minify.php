<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $path = __DIR__ . '/../../public/js_ext/';
    // ext 파일은 그냥 뭉치기만 하면됨. 주석은 직접 지우자. 해당 파일은 갱신할 일이 거의 없으므로 차후 패킹관리시 필요시에만 재갱신 하는 것으로
    readfile($path . 'big.min.js');
    readfile($path . 'axios.min.js');
    readfile($path . 'moment.min.js');
    readfile($path . 'moment-timezone-with-data.js');
    readfile($path . 'socket.io.min.js');
} catch (Exception $e) {
    echo $e->getMessage();
}
