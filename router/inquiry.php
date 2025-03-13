<?php
global $app, $Render, $i18n;

// TODO 차후 확인 바람.
$app->post('/api/inquiry/send', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');


    $conv_name = ['type' => '문의 유형', 'subject' => '제목', 'content' => '문의 내용', 'useragent' => '사용자 브라우저', 'email' => '이메일주소'];

    $need_dic = ['type', 'subject', 'content', 'useragent', 'resolution'];
    $counsel_info = [];
    foreach($need_dic as $key)
    {
        if (!isset($_POST[$key]) || iconv_strlen($_POST[$key], 'UTF-8') < 1)
        {
            echo json_encode(['state' => 'fail', 'msg' => '문의를 접수하기 위해 필요한 정보가 전송되지 않았습니다.']);
            exit(1);
        }
        $counsel_info[$key] = $_POST[$key];
    }

    $length_dic = ['type' => 32, 'subject' => 256, 'content' => 2048, 'useragent' => 512];
    foreach($length_dic as $key => $length)
    {
        if (iconv_strlen($counsel_info[$key]) > $length)
        {
            echo json_encode(['state' => 'fail', 'msg' => $conv_name[$key] . '은(는) 최대 ' . $length . '자 만큼 입력할 수 있습니다.']);
            exit(1);
        }
    }


    $blank_check_dic = ['type', 'subject', 'content', 'useragent'];
    foreach($blank_check_dic as $key)
    {
        if (strlen(preg_replace('/\s+/u', '', strip_tags($counsel_info[$key]))) < 1)
        {
            echo json_encode(['state' => 'fail', 'msg' => $conv_name[$key] . '이(가) 입력되지 않았습니다.']);
            exit(1);
        }
        $counsel_info[$key] = strip_tags($counsel_info[$key]);
    }

    if (isset($_POST['email']))
    {
        $counsel_info['email'] = $_POST['email'];
        if (iconv_strlen($counsel_info['email'], 'UTF-8') < 1)
        {
            echo json_encode(['state' => 'fail', 'msg' => '이메일 주소를 입력하여주십시오.']);
            exit(1);
        }
        else if (strlen(preg_replace('/\s+/u', '', strip_tags($counsel_info['email']))) < 1)
        {
            echo json_encode(['state' => 'fail', 'msg' => $conv_name['email'] . '이(가) 입력되지 않았습니다.']);
            exit(1);
        }
        else if (iconv_strlen($_POST['email'], 'UTF-8') > 256)
        {
            echo json_encode(['state' => 'fail', 'msg' => $conv_name['email'] . '은(는) 최대 256자까지 입력할 수 있습니다.']);
            exit(1);
        }
        else if (preg_match("/^[a-zA-Z0-9\-\_]+\@{1}[a-zA-Z0-9\-\_]+[\.]{1}[a-zA-Z0-9]+$/ui", $counsel_info['email']) < 1)
        {
            echo json_encode(['state' => 'fail', 'msg' => $conv_name['email'] . '가 올바르지 않습니다.']);
            exit(1);
        }
        $counsel_info['email'] = $_POST['email'];
    }

    $CommonDb = new CPgsql('COMMON');

// sid 유무
    if ($_POST['sid'])
    {

        $Db = new CPgsql('DEF');
        $sql = <<< EOF
SELECT
  t1.web_channel, t1.web_id
FROM
  lord_web t1,
  lord t2
WHERE
  t1.lord_pk = t2.lord_pk AND
  t2.last_sid = $1
EOF;
        $Db->query($sql, Array($_POST['sid']));
        if (!$Db->fetch())
        {
            echo qbw_cmd_return('ERR', '사용자 인증에 실패하였습니다.');
            exit(1);
        }
        $web_channel = $Db->row['web_channel'];
        $account_id = $Db->row['web_id'];

        $sql = <<< EOF
SELECT
  acco_pk
FROM
  account
WHERE
  acco_pk = $1
EOF;

        $CommonDb->query($sql, Array($account_id));
        if (!$CommonDb->fetch())
        {
            echo qbw_cmd_return('ERR', '사용자 정보를 찾을 수 없습니다.');
            exit(1);
        }
        $acco_pk = $CommonDb->row['acco_pk'];
    }
    else
    {
        $acco_pk = 0;
    }

    $update_sql = <<< EOF
INSERT INTO
  counsel
  (acco_pk, type, subject, content, user_agent, email, resolution)
VALUES
  ($1, $2, $3, $4, $5, $6, $7)
EOF;

    $CommonDb->query($update_sql, Array($acco_pk, $counsel_info['type'], $counsel_info['subject'], $counsel_info['content'], $counsel_info['useragent'], $counsel_info['email'], $counsel_info['resolution']));
    if ($CommonDb->getAffectedRows() < 1)
    {
        echo qbw_cmd_return('ERR', '문의 내용을 저장하는 중에 문제가 생겼습니다.');
        exit(1);
    }

    echo qbw_cmd_return('OK', null, Array('state' => 'ok'));


    return $Render->nsXhrReturn('success');
}));
