<?php
global $app, $Render, $i18n;

use Shuchkin\SimpleXLSXGen;

$app->post('/admin/gm/api/createCoupon', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    if ($params['coupon_title'] == '') {
        return $Render->view(json_encode(['result' => 'error', 'msg' => '쿠폰명을 입력하세요.']));
    }
    if ($params['coupon_count'] < 1) {
        return $Render->view(json_encode(['result' => 'error', 'msg' => '쿠폰은 최소 1장 이상 발급되어야 합니다.']));
    }
    if ($params['start_time'] == '' || $params['end_time'] == '') {
        return $Render->view(json_encode(['result' => 'error', 'msg' => '기간 설정을 확인하세요.']));
    }

    try {
        $PgCommon = new Pg('COMMON');
        $PgCommon->query('INSERT INTO coupon (coupon_title, coupon_code, coupon_count, duplicate, start_date, end_date, item_data) VALUES ($1, $2, $3, $4, $5, $6, $7)',
            [$params['coupon_title'], $params['coupon_code'], $params['coupon_count'], $params['duplicate'], date('Y-m-d H:i:s', $params['start_time']), date('Y-m-d H:i:s', $params['end_time']), $params['coupon_item_list']]);

        if ($params['coupon_code'] == '') {
            $coupon_pk = $PgCommon->currSeq('coupon_coupon_pk_seq');
            $values_strings = [];
            for ($i = 0; $i < $params['coupon_count']; $i++) {
                $code = strtoupper(Useful::uniqId(16));
                $values_strings[] = "($coupon_pk, '$code')";
            }
            $values_string = join(',', $values_strings);
            $PgCommon->query("INSERT INTO coupon_use (coupon_pk, coupon_code) VALUES $values_string");
        }

    } catch (Throwable $e) {
        return $Render->view(json_encode(['result' => 'error', 'msg' => $e->getMessage()]));
    }

    return $Render->view(json_encode(['result' => 'ok']));
}));

$app->post('/admin/gm/api/couponList', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    try {
        $PgCommon = new Pg('COMMON');

        $PgCommon->query('SELECT coupon_pk, coupon_title, coupon_code, coupon_count, coupon_use, duplicate, create_date, start_date, end_date, item_data FROM coupon ORDER BY coupon_pk DESC');
        $PgCommon->fetchAll();
        foreach ($PgCommon->rows as &$v) {
            $v['start_date'] = strtotime($v['start_date']) * 1000;
            $v['end_date'] = strtotime($v['end_date']) * 1000;
            $v['create_date'] = strtotime($v['create_date']) * 1000;
        }
    } catch (Throwable $e) {
        return $Render->view(json_encode(['result' => 'error', 'msg' => $e->getMessage()]));
    }

    return $Render->view(json_encode(['result' => 'ok', 'rows' => $PgCommon->rows]));
}));

$app->post('/admin/gm/api/removeCoupon', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    try {
        $PgCommon = new Pg('COMMON');
        $PgCommon->query('DELETE FROM coupon WHERE coupon_pk = $1', [$params['pk']]);
        $PgCommon->query('DELETE FROM coupon_use WHERE coupon_pk = $1', [$params['pk']]);
    } catch (Throwable $e) {
        return $Render->view(json_encode(['result' => 'error', 'msg' => $e->getMessage()]));
    }

    return $Render->view(json_encode(['result' => 'ok']));
}));

$app->post('/admin/gm/api/modifyCoupon', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $PgCommon = new Pg('COMMON');
    $PgCommon->query('SELECT coupon_title, coupon_code, coupon_count, coupon_use, duplicate, create_date, start_date, end_date, item_data FROM coupon WHERE coupon_pk = $1', [$params['pk']]);
    $PgCommon->fetch();

    return $Render->view(json_encode(['result' => 'ok', 'row' => $PgCommon->row]));
}));

$app->post('/admin/gm/api/updateCoupon', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    if ($params['coupon_title'] == '') {
        return $Render->view(json_encode(['result' => 'error', 'msg' => '쿠폰명을 입력하세요.']));
    }
    if ($params['coupon_count'] < 1) {
        return $Render->view(json_encode(['result' => 'error', 'msg' => '쿠폰은 최소 1장 이상 발급되어야 합니다.']));
    }
    if ($params['start_time'] == '' || $params['end_time'] == '') {
        return $Render->view(json_encode(['result' => 'error', 'msg' => '기간 설정을 확인하세요.']));
    }

    try {
        $PgCommon = new Pg('COMMON');
        $PgCommon->query('UPDATE coupon SET coupon_title = $1, coupon_count = $2, duplicate = $3, start_date = $4, end_date = $5, item_data = $6 WHERE coupon_pk = $7',
            [$params['coupon_title'], $params['coupon_count'], $params['duplicate'], date('Y-m-d H:i:s', $params['start_time']), date('Y-m-d H:i:s', $params['end_time']), $params['coupon_item_list'], $params['modify_pk']]);
    } catch (Throwable $e) {
        return $Render->view(json_encode(['result' => 'error', 'msg' => $e->getMessage()]));
    }

    return $Render->view(json_encode(['result' => 'ok']));
}));


$app->post('/admin/gm/api/exportCoupon', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }


    $data = [];
    $PgCommon = new Pg('COMMON');
    $PgCommon->query('SELECT coupon_code, account_pk, use_date  FROM coupon_use WHERE coupon_pk = $1', [$params['pk']]);
    $PgCommon->fetchAll();

    // 컬럼명
    $data[] = ['coupon_code', 'account_pk', 'use_date'];
    foreach ($PgCommon->rows as $row) {
        $data[] = [$row['coupon_code'], !$row['account_pk'] ? '' : $row['account_pk'], is_null($row['use_date']) ? '' : date('Y-d-m H:i', strtotime($row['use_date']))];
    }

    SimpleXLSXGen::fromArray($data)->downloadAs('test.xlsx');

    return $Render->view();
}));