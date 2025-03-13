<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/lord_own_report', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $page = $params['page']; // get the requested page
    $limit = $params['rows'];

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $PgGame->query('SELECT COUNT(repo_pk) FROM report WHERE lord_pk = $1', [$_SESSION['selected_lord']['lord_pk']]);
    $count = $PgGame->fetchOne();

    if ($count < 1) {
        return $Render->view(json_encode([]));
    }

    $total_page = ($count > 0) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;
    $offset_start = $limit * $page - $limit;

    $PgGame->query("SELECT repo_pk, report_type, title, from_lord_name, from_posi_name, to_lord_name, to_posi_name, send_dt, yn_read
FROM report WHERE lord_pk = $1 ORDER BY send_dt DESC LIMIT $2 OFFSET $3", [$_SESSION['selected_lord']['lord_pk'], $limit, $offset_start]);
    $PgGame->fetchAll();
    $report_list = $PgGame->rows;

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    $i = 0;
    foreach ($report_list as $v)
    {
        $response->rows[$i] = [];
        $response->rows[$i]['id'] = $v['repo_pk'];
        $response->rows[$i]['cell'] = [$v['repo_pk'], $v['report_type'], $v['report_type'], $v['from_posi_name'], $v['to_posi_name'], $v['send_dt'], $v['yn_read']];
        $i++;
    }

    return $Render->view(json_encode($response));
}));

$app->post('/admin/gm/api/viewLordReport', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['hero', 'hero_base', 'hero_skill']);

    $PgGame->query('SELECT report_type, summary, to_lord_name, from_lord_name, to_posi_name, from_posi_name, content_json, content_battle_json FROM report WHERE repo_pk = $1', [$params['repo_pk']]);
    if (! $PgGame->fetch()) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '선택된 보고서가 없습니다.']));
    }
    $report_info = $PgGame->row;
    if (isset($report_info['content_battle_json']))
    {
        $content = json_decode($report_info['content_json'], true);
        $data = json_decode($report_info['content_battle_json'], true);

        $hero_battle = '';

        if ($data['hero_battle']['result'] > 0)
        {
            $hero_battle.= '<strong>※ 일기토</strong><br />';
            $hero_battle.= '<strong>총 합수</strong> : '.$data['hero_battle']['battle_turn'].'<br />';
            $hero_battle.= '<strong>공격측 무장</strong><br />';

            if (is_array($data['hero_battle']['att_hero']))
            {
                foreach($data['hero_battle']['att_hero'] as $heroes)
                {
                    $hero = $_M['HERO'][$heroes['m_hero_pk']];
                    $hero_battle.= 'Lv.'.$_M['HERO'][$hero['m_hero_pk']]['level'].' '.$_M['HERO_BASE'][$hero['m_hero_base_pk']]['name'].' [무력:'.$heroes['mil_force'].'],[HP:'.$heroes['energy'].']<br />';
                }
            }

            $hero_battle.= '<strong>방어측 무장</strong><br />';

            if (is_array($data['hero_battle']['def_hero']))
            {
                foreach($data['hero_battle']['def_hero'] as $heroes)
                {
                    $hero = $_M['HERO'][$heroes['m_hero_pk']];
                    $hero_battle.= 'Lv.'.$_M['HERO'][$hero['m_hero_pk']]['level'].' '.$_M['HERO_BASE'][$hero['m_hero_base_pk']]['name'].' [무력:'.$heroes['mil_force'].'],[HP:'.$heroes['energy'].']<br />';
                }
            }

            $hero_battle.= '<strong>일기토 결과</strong><br />';
            $hero_battle.= $content['hero_battle']['win'].' 승리<br /><br />';
        }

        $hero_battle.= '<strong>※ 무장 정보</strong><br />';

        $hero_battle.= '<strong>공격측 무장</strong><br />';

        if (isset($content['outcome_hero']['att']['captain_desc']))
        {
            $hero_battle.= $content['outcome_hero']['att']['captain_desc'].'<br />';
        }

        if (isset($content['outcome_hero']['att']['director_desc']))
        {
            $hero_battle.= $content['outcome_hero']['att']['director_desc'].'<br />';
        }

        if (isset($content['outcome_hero']['att']['staff_desc']))
        {
            $hero_battle.= $content['outcome_hero']['att']['staff_desc'].'<br />';
        }

        $hero_battle.= '<strong>방어측 무장</strong><br />';

        if (isset($content['outcome_hero']['def']['captain_desc']))
        {
            $hero_battle.= $content['outcome_hero']['def']['captain_desc'].'<br />';
        }

        if (isset($content['outcome_hero']['def']['director_desc']))
        {
            $hero_battle.= $content['outcome_hero']['def']['director_desc'].'<br />';
        }

        if (isset($content['outcome_hero']['def']['staff_desc']))
        {
            $hero_battle.= $content['outcome_hero']['def']['staff_desc'].'<br />';
        }

        $hero_battle.= '<br /><strong>※ 전투 상황 정보</strong><br />';

        $hero_battle.= '<strong>전투 승리</strong> : '.$content['outcome']['winner'].'<br />';

        $hero_battle.= '<strong>- 사전 발동 스킬</strong><br />';

        $att_skill = $_M['HERO_SKILL'][$data['hero_battle']['att']['before_battle_skill']['pk']] ?? '';
        $def_skill = $_M['HERO_SKILL'][$data['hero_battle']['def']['before_battle_skill']['pk']] ?? '';

        if (isset($att_skill['title']))
        {
            $hero_battle.= '<strong>공격측</strong> : Lv.'.$att_skill['rare'].' '.$att_skill['title'].'<br />';
        }

        if (isset($def_skill['title']))
        {
            $hero_battle.= '<strong>방어측</strong> : Lv.'.$def_skill['rare'].' '.$def_skill['title'].'<br />';
        }

        $hero_battle.= '<strong>- 전투 내용</strong><br />';
        $hero_battle.= '<strong>총 합수</strong> : '.(count($data['scene'])-1).'<br />';

        foreach($data['scene'] as $k => $v)
        {
            $hero_battle.= $k.'턴<br />';
            if (isset($v['att_battle_skill']['pk']))
            {
                $att_skill = $_M['HERO_SKILL'][$v['att_battle_skill']['pk']];
                $hero_battle.= '<strong>공격측 스킬</strong> : Lv.'.$att_skill['rare'].' '.$att_skill['title'].'<br />';
            }

            $hero_battle.= '<strong>공격측  병력</strong><br />';

            foreach($v['att_unit'] as $k2 => $v2)
            {
                $hero_battle.= $k2.' : '.number_format($v2['remain']);
                if ((INT)$v2['dead'] > 0)
                {
                    $hero_battle.= ' <span style="color:red">(-'.number_format($v2['dead']).')</span>';
                }
                $hero_battle.= '<br />';
            }

            if (isset($v['def_battle_skill']['pk']))
            {
                $def_skill = $_M['HERO_SKILL'][$v['def_battle_skill']['pk']];
                $hero_battle.= '<strong>방어측 스킬</strong> : Lv.'.$def_skill['rare'].' '.$def_skill['title'].'<br />';
            }

            $hero_battle.= '<strong>방어측  병력</strong><br />';

            foreach($v['def_unit'] as $k2 => $v2) {
                $hero_battle.= $k2.' : '.number_format($v2['remain']);
                if ($v2['dead'] > 0)
                {
                    $hero_battle.= ' <span style="color:red">(-'.number_format($v2['dead']).')</span>';
                }
                $hero_battle.= '<br />';
            }

            $hero_battle.= '<br />';
        }

        $report_info['content'] = $hero_battle;
    } else {
        $report_info['content'] = strip_tags($report_info['summary']);
    }

    return $Render->view(json_encode($report_info));
}));




