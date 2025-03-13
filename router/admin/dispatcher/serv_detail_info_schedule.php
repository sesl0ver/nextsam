<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_schedule', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    return $Render->view(json_encode([]));
}));