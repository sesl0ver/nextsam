<?php
use Shuchkin\SimpleXLSX;

global $app, $Render, $i18n;

$app->get('/admin/tools/masterData', $Render->wrap(function (array $params) use ($Render, $i18n) {
    global $_M;

    return $Render->template('/tools/master_data.twig', [
        '_M' => $_M
    ]);
}));


$app->post('/admin/tools/api/uploadFile', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $master_data = $params['master_data'];
    $target_table = $params['target_table'];

    if (!strlen($target_table)) {
        return $Render->view('테이블명을 적어주세요!!');
    }

    if (!strlen($master_data)) {
        return $Render->view('데이터를 추가해 주세요!!');
    }

    $fp = fopen("/tmp/$target_table.txt", 'w+');
    fwrite($fp, $master_data);

    // temp table 만들기
    $pp = popen("PGPASSWORD=".DEFAULT_PGSQL_PASS." psql -h ".DEFAULT_PGSQL_IP." -p ".DEFAULT_PGSQL_PORT." -U qbe qbegame", "w");
    $cmd = "CREATE TABLE " . $target_table . "_temp AS SELECT * FROM " . $target_table . " LIMIT 0\n";

    fwrite($pp, $cmd);

    pclose($pp);

    // 데이터 insert를 위해서 슈퍼유저로 접속해야 함.
    $pp = popen("PGPASSWORD=".DEFAULT_PGSQL_PASS." psql -h ".DEFAULT_PGSQL_IP." -p ".DEFAULT_PGSQL_PORT." -U qbe qbegame", "w");
    $cmd = "\COPY {$target_table}_temp FROM '/tmp/$target_table.txt' WITH DELIMITER E'\t' CSV HEADER;";
    fwrite($pp, $cmd);

    pclose($pp);

    $Db = new Pg('DEFAULT');

    // 컬럼명 저장
    $arr_column = [];	// 원래 마스터 테이블

    $query_params = [$target_table];
    $Db->query('SELECT oid FROM pg_class WHERE relname = $1', $query_params);
    $oid = $Db->fetchOne();

    $query_params = [$oid];
    $Db->query('SELECT attnum, attname, atttypid FROM pg_attribute WHERE attrelid = $1 AND attstattarget = -1 ORDER BY attnum ASC', $query_params);
    while($Db->fetch()) {
        $arr_column[$Db->row['attnum']] = $Db->row;
    }

    $query_params = [$target_table . '_temp'];
    $Db->query('SELECT oid FROM pg_class WHERE relname = $1', $query_params);
    $oid = $Db->fetchOne();

    // pk값 찾기
    $query_params = [$target_table . '_pkey'];
    $Db->query('SELECT conkey FROM pg_constraint WHERE conname = $1', $query_params);
    $pk_str = $Db->fetchOne();
    //$pk_str = '{1,2}';
    $pk_str = str_replace('{', '', $pk_str);
    $pk_str = str_replace('}', '', $pk_str);
    $pk_arr = explode(',', $pk_str);

    $pk_column_arr = [];
    for ($i = 0; $i < COUNT($pk_arr); $i++)  {
        $pk_column_arr[$i] = $arr_column[$pk_arr[$i]]['attname'];
    }

    // 원래 테이블
    $Db->query('SELECT * FROM ' . $target_table);
    $arr_original = [];
    while($Db->fetch()) {
        $index = null;
        for ($i = 0; $i < COUNT($pk_column_arr); $i++) {
            $index = $index. $Db->row[$pk_column_arr[$i]];
        }
        $arr_original[$index] = $Db->row;
    }

    // 업데이트할 테이블
    $arr_temp = [];
    $Db->query("SELECT * FROM {$target_table}_temp");
    while($Db->fetch()) {
        $index = null;
        for ($i = 0; $i < COUNT($pk_column_arr); $i++) {
            $index = $index. $Db->row[$pk_column_arr[$i]];
        }
        $arr_temp[$index] = $Db->row;
    }

    // 삽입
    echo '<br />---------------------------INSERT-------------------------------<br />';
    foreach ($arr_temp AS $k => $v) {
        $pk_str2 = $arr_column[$pk_arr[0]]['attname'] .'='.$v[$arr_column[$pk_arr[0]]['attname']];

        if (COUNT($pk_arr) >  1) {
            for ($i = 1; $i < COUNT($pk_arr); $i++) {
                $pk_str2 = $pk_str2 . ' AND ' . $arr_column[$pk_arr[$i]]['attname'] .'='.$v[$arr_column[$pk_arr[$i]]['attname']];
            }
        }

        if (! isset($arr_original[$k])) {
            $cnt = 0;
            $column_str = '';
            $column_values_str = '';

            foreach($arr_column AS $k1 => $v1) {
                if ($cnt == 0) {
                    $column_str = $v1['attname'];

                    if ($v1['atttypid'] == 23 || $v1['atttypid'] == 21 || $v1['atttypid'] == 701)
                    {
                        if ($v[$v1['attname']] == null)
                            $column_values_str = 'null';
                        else
                            $column_values_str = $v[$v1['attname']];
                    } else {
                        if ($v[$v1['attname']] == null)
                            $column_values_str = 'null';
                        else
                            $column_values_str = '\'' . pg_escape_string($Db->connection(), $v[$v1['attname']]) .'\'';
                    }
                } else {
                    $column_str = $column_str . ', ' . $v1['attname'];

                    if ($v1['atttypid'] == 23 || $v1['atttypid'] == 21 || $v1['atttypid'] == 701)
                    {
                        if ($v[$v1['attname']] == null)
                            $values = 'null';
                        else
                            $values = $v[$v1['attname']];
                    } else {
                        if (! isset($v[$v1['attname']]))
                            $values = 'null';
                        else {
                            $values = '\'' . pg_escape_string($Db->connection(), $v[$v1['attname']]) .'\'';
                            $values = htmlspecialchars($values);
                        }
                    }

                    $column_values_str = $column_values_str . ', ' . $values;
                }

                $cnt++;
            }

            //echo 'INSERT INTO ' . $target_table . ' SELECT * FROM '.$target_table.'_temp WHERE ' . $pk_str2 . ';<br />';
            echo 'INSERT INTO ' . $target_table . '(' . $column_str . ') VALUES (' . $column_values_str . ');' . '<br />';
        }
    }

    // 삭제
    echo '---------------------------DELETE-------------------------------<br />';
    if (isset($arr_original)) {
        foreach ($arr_original AS $k => $v) {
            if ($arr_column[$pk_arr[0]]['atttypid'] == 23 || $arr_column[$pk_arr[0]]['atttypid'] == 21 || $arr_column[$pk_arr[0]]['atttypid'] == 701) {
                $pk_str2 = $arr_column[$pk_arr[0]]['attname'] .'='.$v[$arr_column[$pk_arr[0]]['attname']];
            } else {
                $pk_str2 = $arr_column[$pk_arr[0]]['attname'] .'= \''.$v[$arr_column[$pk_arr[0]]['attname']] . '\'';
            }

            if (COUNT($pk_arr) >  1) {
                for ($i = 1; $i < COUNT($pk_arr); $i++) {
                    if ($arr_column[$pk_arr[$i]]['atttypid'] == 23 || $arr_column[$pk_arr[$i]]['atttypid'] == 21 || $arr_column[$pk_arr[$i]]['atttypid'] == 701) {
                        $value = $v[$arr_column[$pk_arr[$i]]['attname']];
                    } else {
                        $value = '\'' . $v[$arr_column[$pk_arr[$i]]['attname']] . '\'';
                    }
                    $pk_str2 = $pk_str2 . ' AND ' . $arr_column[$pk_arr[$i]]['attname'] .'='. $value;
                }
            }

            if (! isset($arr_temp[$k])) {
                echo 'DELETE FROM ' . $target_table . ' WHERE ' . $pk_str2 . ';<br />';
            }
        }
    }

    // 업데이트
    echo '---------------------------UPDATE-------------------------------<br />';
    foreach ($arr_temp AS $k => $v)
    {
        $update_str = null;

        foreach($arr_column AS $k1 => $v1) {
            if (isset($arr_original[$k])) {
                if (isset($arr_original[$k][$v1['attname']])) {
                    $arr_original[$k][$v1['attname']] = str_replace("\r\n", " ", $arr_original[$k][$v1['attname']]);
                    $arr_original[$k][$v1['attname']] = str_replace("\r", "", $arr_original[$k][$v1['attname']]);
                    $arr_original[$k][$v1['attname']] = str_replace("\n", "", $arr_original[$k][$v1['attname']]);
                    $arr_original[$k][$v1['attname']] = str_replace("\\\\", "\\", $arr_original[$k][$v1['attname']]);

                    while(strpos($arr_original[$k][$v1['attname']], "  ") !== false) {
                        $arr_original[$k][$v1['attname']] = str_replace("  ", " ", $arr_original[$k][$v1['attname']]);
                    }
                }

                if (isset($v[$v1['attname']])) {
                    $v[$v1['attname']] = str_replace("\r\n", " ", $v[$v1['attname']]);
                    $v[$v1['attname']] = str_replace("\r", "", $v[$v1['attname']]);
                    $v[$v1['attname']] = str_replace("\n", "", $v[$v1['attname']]);
                }

                if (isset($v[$v1['attname']])) {
                    while(str_contains($v[$v1['attname']], "  ")) {
                        $v[$v1['attname']] = str_replace("  ", " ", $v[$v1['attname']]);
                    }
                }

                $isDifferent = false;
                $val1 = $arr_original[$k][$v1['attname']];
                $val2 = $v[$v1['attname']];
                // $val1_trim = trim($val1,' ');;
                if (isset($val2)) {
                    $val2_trim = trim($val2,chr(0xC2).chr(0xA0));
                }

                // if ($val1 != $val2 && $val1_trim != $val2 && $val1 != $val2_trim && $val1_trim != $val2_trim) {
                if ($val1 != $val2 && $val1 != $val2_trim) {
                    $isDifferent = true;
                }

                if ($v1['attname'] != 'set_count' && $v1['attname'] != 'left_count' && $v1['attname'] != 'trade_total_count' && $v1['attname'] != 'trade_total_value' && $v1['attname'] != 'magiccube_left_count' &&  $isDifferent)
                {
                    if ($v1['atttypid'] == 23 || $v1['atttypid'] == 21 || $v1['atttypid'] == 701)
                    {
                        if ($v[$v1['attname']] == null)
                            $values = 'null';
                        else
                            $values = $v[$v1['attname']];
                    } else {
                        if ($v[$v1['attname']] == null)
                            $values = 'null';
                        else {
                            $values = '\'' . pg_escape_string($Db->connection(), $v[$v1['attname']]) .'\'';
                            $values = htmlspecialchars($values);
                        }
                    }

                    if (!$update_str) {
                        $update_str = $v1['attname'] . ' = ' . $values;
                    } else {
                        $update_str = $update_str . ',' . $v1['attname'] . ' = ' . $values;
                    }
                }
            }
        }

        if ($arr_column[$pk_arr[0]]['atttypid'] == 23 || $arr_column[$pk_arr[0]]['atttypid'] == 21 || $arr_column[$pk_arr[0]]['atttypid'] == 701) {
            if ($v[$arr_column[$pk_arr[0]]['attname']] == null)
            {
                $pk_str2 = $arr_column[$pk_arr[0]]['attname'] .'= null';
            } else
                $pk_str2 = $arr_column[$pk_arr[0]]['attname'] .'='.$v[$arr_column[$pk_arr[0]]['attname']];
        } else {
            if ($v[$arr_column[$pk_arr[0]]['attname']] == null)
            {
                $pk_str2 = $arr_column[$pk_arr[0]]['attname'] .'= null';
            } else
                $pk_str2 = $arr_column[$pk_arr[0]]['attname'] .'= \''.$v[$arr_column[$pk_arr[0]]['attname']] . '\'';
        }

        if (COUNT($pk_arr) >  1)
        {
            for ($i = 1; $i < COUNT($pk_arr); $i++)
            {

                if ($arr_column[$pk_arr[$i]]['atttypid'] == 23 || $arr_column[$pk_arr[$i]]['atttypid'] == 21 || $arr_column[$pk_arr[$i]]['atttypid'] == 701)
                {
                    if ($v[$arr_column[$pk_arr[$i]]['attname']] == null)
                    {
                        $value = 'null';
                    } else
                        $value = $v[$arr_column[$pk_arr[$i]]['attname']];
                } else {
                    if ($v[$arr_column[$pk_arr[$i]]['attname']] == null)
                    {
                        $value = 'null';
                    } else
                        $value = '\'' . $v[$arr_column[$pk_arr[$i]]['attname']] . '\'';
                }

                $pk_str2 = $pk_str2 . ' AND ' . $arr_column[$pk_arr[$i]]['attname'] .'='.$value;
            }
        }

        if ($update_str)
            print 'UPDATE ' . $target_table . ' SET ' . $update_str . ' WHERE ' . $pk_str2 . ';<br />';
    }

    // 작업 완료 후 temp 테이블 삭제
    $sql = <<< EOF
DROP TABLE {$target_table}_temp;
EOF;
    $Db->query($sql);

    return $Render->view();
}));

$app->post('/admin/tools/api/queryTest', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $filenames = $params['filenames'];
    $query = $params['query'];

    $ret = [
        'status' => 'OK',
        'message' => '',
        'success' => [],
        'failed' => []
    ];

    if (count($filenames) != count($query)) {
        $ret['status'] = 'ERROR';
        $ret['message'] = '입력값이 올바르지 않습니다.';
        echo json_encode($ret);
        exit;
    }

    $Db = new Pg('DEFAULT');

    $i = 0;
    try {
        $Db->begin();

        for (; $i < count($query); $i++) {
            if (! $Db->query($query[$i])) {
                throw new Exception('query error');
            }
            $ret['success'][] = $filenames[$i];
        }

        $Db->rollback();
    } catch (Exception $e) {
        $ret['status'] = 'ERROR';
        $ret['message'] = '테스트에 실패하였습니다.';
        $ret['reason'] = $e->getMessage();
        $ret['failed'][] = $filenames[$i];
        $Db->rollback();
    }

    return $Render->view(json_encode($ret));
}));

$app->post('/admin/tools/api/queryCreate', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $filenames = $params['filenames'];
    $query = $params['query'];
    $comment = $params['comment'];

    $ret = [
        'status' => 'OK',
        'message' => '',
        'success' => [],
        'failed' => []
    ];

    if (count($filenames) != count($query) || !$comment || strlen(trim($comment)) == 0) {
        $ret['status'] = 'ERROR';
        $ret['message'] = '입력값이 올바르지 않습니다.';
        echo json_encode($ret);

        exit;
    }

    /*if (LANGUAGE_PACK == 'TW'){
        $dir = '../../tool/sql/update/build_tw';
    } else {
        $dir = '../../tool/sql/update/build';
    }
    if (strlen($lang) > 0) {
        $dir .= '_';
        $dir .= $lang;
    }*/
    $dir = '/tmp/';
    $now = date('ymd_His');
    $stage_dir = $dir. '/.not_run_'. $now;
    if (!mkdir($stage_dir)) {
        $ret = ['status'=>'error','dir'=>$stage_dir];
        echo json_encode($ret);
        exit;
    }
    chmod($stage_dir, 0777);

    /* $sql = sprintf("INSERT INTO update_history(note) VALUES('%s');", $comment);
    $fp = fopen($stage_dir.'/00.comment.sql', 'w+');
    fwrite($fp, $sql);
    fclose($fp);
    chmod($stage_dir.'/00.comment.sql', 0777); */

    for ($i = 0; $i < count($filenames); $i++) {
        $file = $filenames[$i];
        $file = str_replace('.txt', '.sql', $file);
        $addQuery = sprintf("INSERT INTO update_history(note) VALUES('%s > %02d.%s');", $now, $i+1, $file);

        $file = sprintf("%s/%02d.%s", $stage_dir, $i+1, $file);

        $fp = fopen($file, 'w+');

        fwrite($fp, $query[$i]);
        fwrite($fp, "\n\n");
        fwrite($fp, $addQuery);

        fclose($fp);

        chmod($file, 0777);
    }

    $ret = ['status'=>'OK','now'=>$now];
    echo json_encode($ret);


    return $Render->view();
}));

$app->post('/admin/tools/api/queryRun', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $now = $params['target'];

    $dir = '/tmp';
    $stage_dir = $dir . '/.not_run_'. $now;
    if (! is_dir($stage_dir)) {
        exit;
    }

    $files = [];
    $dh = opendir($stage_dir);
    while (($file = readdir($dh)) !== false) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if ($ext != 'sql') {
            continue;
        }
        $files[] = $file;
    }
    closedir($dh);

    sort($files);

    $ret = [
        'status' => 'ERROR',
        'message' => '',
        'files' => $files
    ];

    $Db = new Pg('DEFAULT');
    try {
        $Db->begin();

        for($i = 0; $i < count($files); $i++) {
            $file = $stage_dir. '/'. $files[$i];
            $size = filesize($file);
            $fp = fopen($file, 'rb');
            $sql = fread($fp, $size);
            fclose($fp);

            if (!$Db->query($sql)) {
                throw new Exception("쿼리 적용 실패\n".$file."\n".$sql);
            }
        }

        $ret['status'] = 'OK';
        rename($stage_dir, $dir.'/'.$now);
        $Db->commit();
    }
    catch (Exception $e) {
        $ret['message'] = $e->getMessage();
        $Db->rollback();
    }

    return $Render->view(json_encode($ret));
}));


// 신규 마스터데이터 툴


$app->get('/admin/tools/masterDataNew', $Render->wrap(function (array $params) use ($Render, $i18n) {
    global $_M;

    return $Render->template('/tools/master_tools.twig', [
        '_M' => $_M
    ]);
}));

$app->post('/admin/tools/api/check', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $PgGame = new Pg('DEFAULT');
    global $_M;

    $TEMP_PATH = '/service/data/';

    $result = [];
    $count = 0;
    foreach ($params['upload'] as $file) {
        $filename = Useful::uniqId();
        $filepath = $TEMP_PATH . $filename;
        $file->moveTo($filepath); // 읽어 오기위해 캐싱

        try {
            $xlsx = SimpleXLSX::parseFile($filepath);
            $table_name = $xlsx->sheetName(0);

            // 컬럼명 저장
            $arr_column = [];	// 원래 마스터 테이블

            $PgGame->query('SELECT oid FROM pg_class WHERE relname = $1', [$table_name]);
            $oid = $PgGame->fetchOne();

            $query_params = [$oid];
            $PgGame->query('SELECT attnum, attname, atttypid FROM pg_attribute WHERE attrelid = $1 AND attstattarget = -1 ORDER BY attnum', $query_params);
            while($PgGame->fetch()) {
                $arr_column[$PgGame->row['attnum']] = $PgGame->row;
            }

            // pk값 찾기
            $PgGame->query('SELECT conkey FROM pg_constraint WHERE conname = $1', [$table_name . '_pkey']);
            $pk_str = $PgGame->fetchOne();
            $pk_str = str_replace('{', '', $pk_str);
            $pk_str = str_replace('}', '', $pk_str);
            $pk_arr = explode(',', $pk_str);

            $pk_column_arr = [];
            foreach ($pk_arr as $i => $pk) {
                $pk_column_arr[$i] = $arr_column[$pk]['attname'];
            }

            $PgGame->query("SELECT * FROM $table_name");
            $arr_original = [];
            while($PgGame->fetch()) {
                $index = null;
                foreach ($pk_column_arr as $pk_column) {
                    $index = $index. $PgGame->row[$pk_column];
                }
                $arr_original[$index] = $PgGame->row;
            }

            $set_column = false;
            $columns = [];
            $new_data = [];
            $i = 0;

            foreach ($xlsx->rows() as $row) {
                if (! $set_column) {
                    foreach ($row as $_column) {
                        $columns[] = $_column;
                    }
                    $set_column = true;
                    continue;
                } else {
                    $index_column = 0;
                    foreach ($row as $_new) {
                        if (! isset($new_data[$i])) {
                            $new_data[$i] = [];
                        }
                        $new_data[$i][$columns[$index_column]] = $_new;
                        $index_column++;
                    }
                }
                $i++;
            }

            $arr_changed = [];
            foreach ($new_data as $row) {
                $index = null;
                foreach ($pk_column_arr as $pk_column) {
                    $index = $index. $row[$pk_column];
                }
                $arr_changed[$index] = $row;
            }

            $result = Useful::diff($arr_original, $arr_changed);

            foreach ($result as $_count) {
                $count+= count($_count);
            }

            // 결과 캐싱 하기
            if ($count > 0) {
                $fp = fopen("{$TEMP_PATH}master_result.txt", "w"); // 더미 파일이 생기지 않게 단일 파일로
                fwrite($fp, json_encode(['table_name' => $table_name, 'index' => $pk_column_arr, 'result' => $result]));
                fclose($fp);
            }
        } catch (Throwable $e) {
            return $Render->nsXhrReturn('error', $e->getMessage());
        }

        unlink($filepath); // 캐시파일 삭제
    }

    return $Render->nsXhrReturn('success', null, ['count' => $count, 'result' => $result]);
}));

$app->post('/admin/tools/api/valid', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $TEMP_PATH = '/service/data/';

    $result = '';
    try {
        // 캐싱 파일 읽기
        $fp = fopen("{$TEMP_PATH}master_result.txt", "r");
        while (!feof($fp)) {
            $result .= fgets($fp,1024);
        }
        $result = json_decode($result, true);
        fclose($fp);

        $table_name = $result['table_name'];
        $result_data = $result['result'];

        $fp = fopen("{$TEMP_PATH}master_query.sql", "a"); // 더미 파일이 생기지 않게 단일 파일로
        foreach ($result_data as $type => $rows) {
            if ($type === 'added') { // INSERT
                $insert_values = [];
                foreach ($rows as $insert) {
                    foreach ($insert as $value) {
                        $value = is_null($value) ? 'null' : trim($value);
                        $value = is_numeric($value) ? $value : "'$value'";
                        $insert_values[] = $value;
                    }
                    fwrite($fp, "INSERT INTO " . $table_name . " VALUES(" . implode(',', $insert_values) . ");\n");
                }
            } else if ($type === 'removed') { // DELETE
                foreach ($rows as $pk => $delete) {
                    $index_sql = '';
                    foreach ($result['index'] as $idx) {
                        if ($index_sql !== '') {
                            $index_sql.= ' AND ';
                        }
                        $index_sql.= $idx . " = " . $pk;
                    }
                    fwrite($fp, "DELETE FROM " . $table_name . " WHERE " . $index_sql . ";\n");
                }
            } else if ($type === 'changed') { // UPDATE
                foreach ($rows as $pk => $update) {
                    $update_sql = '';
                    $index_sql = '';
                    foreach ($result['index'] as $idx) {
                        if ($index_sql !== '') {
                            $index_sql.= ' AND ';
                        }
                        $index_sql.= $idx . " = " . $pk;
                    }
                    // 업데이트 항목은 changed 만 있을 것. (툴을 통해 컬럼을 추가할 일은 없으니)
                    foreach ($update['changed'] as $column => $values) {
                        if ($update_sql != '') {
                            $update_sql.= ", ";
                        }
                        $new = is_null($values['new']) ? 'NULL' : trim($values['new']);
                        $val = is_numeric($new) ? $new : "'$new'";
                        $update_sql.= $column . " = " . $val;
                    }
                    fwrite($fp, "UPDATE " . $table_name . " SET " . $update_sql . " WHERE " . $index_sql . ";\n");
                }
            }
        }
        fclose($fp);

        unlink("{$TEMP_PATH}master_result.txt"); // 캐시파일 삭제
    } catch (Throwable $e) {
        return $Render->nsXhrReturn('error', $e->getMessage());
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/admin/tools/api/update', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $PgGame = new Pg('DEFAULT');
    $TEMP_PATH = '/service/data/';

    $query_strings = '';
    try {
        // 캐싱 파일 읽기
        $fp = fopen("{$TEMP_PATH}master_query.sql", "r");
        while (!feof($fp)) {
            $query_strings .= fgets($fp,1024);
        }
        fclose($fp);
        $PgGame->query($query_strings);

        // TODO 임시로 패킹여부에 따라 서버 체크
        if (CONF_FILE_PACKING) {
            unlink("{$TEMP_PATH}master_query.sql");
        } else {
            // 개발 서버에서만 쿼리문 생성
            $time = time();
            rename("{$TEMP_PATH}master_query.sql", "{$TEMP_PATH}/master_query_{$time}.sql");
        }
    } catch (Throwable $e) {
        return $Render->nsXhrReturn('error', $e->getMessage());
    }

    return $Render->nsXhrReturn('success');
}));

