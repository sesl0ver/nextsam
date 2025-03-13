<?php
global $app, $Render, $i18n;

// X, Y
$app->get('/dispatcher/troop', $Render->wrap(function (array $params) use ($Render) {
    $Render->existParams(['time_pk']);
    /* 타이머 공통 */
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $t = $Timer->getRecord($params['time_pk']); // 타이머 확인
    if ($t === false || $t['status'] != 'P' || ! $Session->positionToLord($t['posi_pk'])) {
        // TODO 오류 로그
        return $Render->view();
    }
    try {
        // TODO 디버깅을 위해 임의로 트랜잭션을 걸어두었으나 거의 매번 호출이되는 명령이기때문에 트랜잭션을 걸경우 DB점유 문제가 있을 수 있다. 그래서 기존에는 트랜잭션을 걸어두지 않았음.
        //  차후 디버깅이 끝나면 트랜잭션은 걷어내야함.
        // $PgGame->begin();
        $result_timer = $Timer->finish($params['time_pk']);
        // Queue 처리 하면 안되는 타이머. 부대 Y
        if ($result_timer['queue_action'] == 'N') {
            echo "[OK]";
            $PgGame->commit(); // 이 시점에서 커밋하여야함.
            exit;
        }
        /* 타이머 공통 끝 */

        // Array ( [queue_type] => Y [queue_pk] => 17263 [queue_action] => Y [description] => 공격(출정) 황건적 습격부대 [start_dt] => 2023-06-28 06:27:48.343112+00 [end_dt] => 2023-06-28 06:30:48.343112+00 [build_time] => 180 )
        $_troo_pk = $result_timer['queue_pk'];

        global $NsGlobal, $_M;
        $Troop = new Troop($Session, $PgGame);
        $Resource = new Resource($Session, $PgGame);
        $GoldPop = new GoldPop($Session, $PgGame);
        $Item = new Item($Session, $PgGame);
        $Army = new Army($Session, $PgGame, $Resource, $GoldPop);
        $Alliance = new Alliance($Session, $PgGame);
        $Hero = new Hero($Session, $PgGame);
        $HeroSkill = new HeroSkill($Session, $PgGame);
        $Report = new Report($Session, $PgGame);
        $Battle = new Battle($Session, $PgGame, $Troop);
        $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
        $Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);
        $Quest = new Quest($Session, $PgGame);
        $Territory = new Territory($Session, $PgGame);
        $Log = new Log($Session, $PgGame);

        $row = $Troop->getTroop($_troo_pk); // 부대를 찾지 못하면 따로 처리 없이 타이머만 종료하도록 처리

        // Withdrawal, Recall
        if (is_array($row) && ($row['status'] == 'W' || $row['status'] == 'R'))
        {
            $PgGame->query('SELECT type, lord_pk FROM position WHERE posi_pk = $1', [$row['dst_posi_pk']]);
            $PgGame->fetch();
            $dts_posi_type = $PgGame->row['type'];
            $curr_lord_pk = $PgGame->row['lord_pk'];

            // 부대 소유주와 dst_posi_pk의 소유주가 불일치 (영지상실의 경우)
            if ($row['src_lord_pk'] != $curr_lord_pk) {
                // 발생시 에러메시지 추가
                // TODO - 영지 점령전 때 추가 예정
            }


            $food = 0;
            $gold = 0;
            if ($row['status'] == 'W') {
                // 취소에 따른 round_*, presence_* 30% 반환
                $food = intval(($row['round_food']+$row['presence_food'])*0.3);
                $gold = intval($row['round_gold']*0.3);
            }

            // 각종 리소스 반환 - 자원, 아이템, 영웅, 병력
            // 자원
            $food += $row['reso_food'];
            $r = $Resource->increase($row['src_posi_pk'], ['food' => $food, 'horse' => $row['reso_horse'], 'lumber' => $row['reso_lumber'], 'iron' => $row['reso_iron']], $row['src_lord_pk'], 'troop_Recall');
            if (!$r) {
                Debug::debugMessage('ERROR', '부대 복귀(취소포함) 시 자원 반환 오류');
            }

            // 황금
            $gold += $row['reso_gold'];

            if ($gold > 0) {
                $r = $GoldPop->increaseGold($row['src_posi_pk'], $gold, $row['src_lord_pk'], 'troop_Recall');
                if (!$r) {
                    Debug::debugMessage('ERROR', '부대 복귀(취소포함) 시 황금 반환 오류');
                }
            }

            // 아이템
            $Item = new Item($Session, $PgGame);
            if (isset($row['use_item_pk']) && $row['use_item_pk'] == 500084) {
                $Item->BuyItem($row['src_lord_pk'], $row['use_item_pk'], 1, 'troop', false);
            }

            // 영웅
            $Hero = new Hero($Session, $PgGame);
            if (isset($row['captain_hero_pk'])) {
                $ret = $Hero->unsetCommand($row['captain_hero_pk']);
                if (!$ret) {
                    Debug::debugMessage('ERROR', '부대 복귀(취소포함) 시 영웅(주장) unsetCommand 오류');
                }
            }

            if (isset($row['director_hero_pk'])) {
                $ret = $Hero->unsetCommand($row['director_hero_pk']);
                if (!$ret) {
                    Debug::debugMessage('ERROR', '부대 복귀(취소포함) 시 영웅(부장) unsetCommand 오류');
                }
            }

            if (isset($row['staff_hero_pk'])) {
                $ret = $Hero->unsetCommand($row['staff_hero_pk']);
                if (!$ret) {
                    Debug::debugMessage('ERROR', '부대 복귀(취소포함) 시 영웅(참모) Uncomamnd 오류');
                }
            }

            // 병력
            $army_arr = [];
            foreach ($row AS $k => $v) {
                if (str_starts_with($k, 'army_')) {
                    $army_arr[substr($k, 5)] = $v;
                }
            }
            $Army->returnArmy($row['src_posi_pk'], $army_arr);
            $Army->get($row['src_posi_pk'], null, $row['src_lord_pk']);


            // 보고서
            $z_content = [];

            $z_content['from_position'] = $row['from_position'];
            $z_content['to_position'] = $row['to_position'];

            // hero
            $z_content['hero'][] = ['pk' => $row['captain_hero_pk'], 'm_pk' => $Troop->getHeroMasterDataPK($row['captain_hero_pk'])];
            if ($row['director_desc']) {
                $z_content['hero'][] = ['pk' => $row['director_hero_pk'], 'm_pk' => $Troop->getHeroMasterDataPK($row['director_hero_pk'])];
            }
            if ($row['staff_desc']) {
                $z_content['hero'][] = ['pk' => $row['staff_hero_pk'], 'm_pk' => $Troop->getHeroMasterDataPK($row['staff_hero_pk'])];
            }

            // army
            $z_content['army'] = $army_arr;

            // reso
            $z_content['reso']['gold'] = $gold;
            $z_content['reso']['food'] = $food;
            $z_content['reso']['horse'] = $row['reso_horse'];
            $z_content['reso']['lumber'] = $row['reso_lumber'];
            $z_content['reso']['iron'] = $row['reso_iron'];

            if ($dts_posi_type == 'P') {
                $z_content['type'] = $dts_posi_type;
            }

            // from & to
            $z_from = ['posi_pk' => $row['src_posi_pk'], 'posi_name' => $row['from_position']];
            $z_to = ['posi_pk' => $row['dst_posi_pk'], 'posi_name' => $row['to_position']];

            // title & summary
            $report_type = '';
            if ($row['status'] == 'W') {
                $report_type = 'return_finish_1'; // 회군
            } else {
                // 주둔을 수행함.
                if (isset($row['withdrawal_dt'])) {
                    // batch_troop_withdrawal.php 에서 수행
                    if (isset($row['withdrawal_auto'])) {
                        $report_type = 'return_finish_2'; // 자동 복귀
                    } else {
                        $report_type = 'return_finish_3'; // 복귀
                    }
                } else if ($row['cmd_type'] == 'T') {
                    $report_type = 'return_finish_4'; // 수송
                } else if ($row['cmd_type'] == 'R') {
                    $report_type = 'return_finish_5'; // 지원
                } else if ($row['cmd_type'] == 'P') {
                    $report_type = 'return_finish_6'; // 보급
                } else if ($row['cmd_type'] == 'S') {
                    $report_type = 'return_finish_7'; // 정찰
                } else if ($row['cmd_type'] == 'A') {
                    $report_type = 'return_finish_8'; // 전투
                }
            }

            $repo_pk = $Report->setReport($row['src_lord_pk'], 'recall', $report_type, $z_from, $z_to, '', '', json_encode($z_content));
            $Session->sqAppend('PUSH', ['TOAST' => [
                'type' => 'return_troop',
                'result' => $report_type,
                'posi_pk' => $row['dst_posi_pk'],
                'pk' => $repo_pk
            ]], null, $row['src_lord_pk']);

            // Log
            $str = 'src_time_pk:['.$row['src_time_pk'].'];';
            $hero = ['captain_hero_pk' => $row['captain_hero_pk'], 'director_hero_pk' => $row['director_hero_pk'], 'staff_hero_pk' => $row['staff_hero_pk']];
            $army = ['army_worker' => $row['army_worker'], 'army_infantry' => $row['army_infantry'], 'army_pikeman' => $row['army_pikeman'], 'army_scout' => $row['army_scout'], 'army_spearman' => $row['army_spearman'], 'army_armed_infantry' => $row['army_armed_infantry'], 'army_archer' => $row['army_archer'], 'army_horseman' => $row['army_horseman'], 'army_armed_horseman' => $row['army_armed_horseman'], 'army_transporter' => $row['army_transporter'], 'army_bowman' => $row['army_bowman'], 'army_battering_ram' => $row['army_battering_ram'], 'army_catapult' => $row['army_catapult'], 'army_adv_catapult' => $row['army_adv_catapult']];
            $reso = ['round_food' => $row['round_food'], 'round_gold' => $row['round_gold'], 'presence_food' => $row['presence_food'], 'hour_food' => $row['hour_food'], 'timer_info' => $str];
            $Log->setTroop($row['src_lord_pk'], $row['src_posi_pk'], $row['status'].'_'.$row['cmd_type'], $row['dst_lord_pk'], null, $row['dst_posi_pk'], $row['to_position'], json_encode($hero), json_encode($army), json_encode($reso), $_troo_pk);

            // 부대삭제
            $Troop->removeTroop($_troo_pk);
        }
        else if (is_array($row) && $row['status'] == 'M')
        {
            if ($row['src_lord_pk'] == NPC_TROOP_LORD_PK) {
                $alli_pk = null;
                $lord_name = '황건적';
                $lord_name_withLevel = '황건적';
                $lord_level = null;
            } else {
                $PgGame->query('SELECT alli_pk, lord_name, level FROM lord WHERE lord_pk = $1', [$row['src_lord_pk']]);
                $PgGame->fetch();
                $alli_pk = $PgGame->row['alli_pk'];
                $lord_name = $PgGame->row['lord_name'];
                $lord_level = $PgGame->row['level'];
                $lord_name_withLevel = $lord_name. ' Lv.'. $PgGame->row['level'];
            }

            if (! $row['raid_troo_pk']) {
                $dst_posi = $Troop->getPositionRelation($row['dst_posi_pk'], ['lord_pk' => $row['src_lord_pk'], 'alli_pk' => $alli_pk]);
            } else {
                // 섬멸전의 경우. 현재 사용 안함.
                $raid_troop = $Troop->getRaidNpcTroop($row['raid_troo_pk']);

                $dst_posi = [];
                $dst_posi['lord_pk'] = NPC_TROOP_LORD_PK;
                $dst_posi['alli_pk'] = null;
                $dst_posi['name'] = '황건적 요새';
                $dst_posi['lord_name'] = '황건적';
                $dst_posi['lord_level'] = null;
                $dst_posi['lord_position_cnt'] = null;
                $dst_posi['lord_name_withLevel'] = '황건적';
                $dst_posi['type'] = 'S';
                $dst_posi['level'] = (! $raid_troop) ? null : $raid_troop['target_level'];
                $dst_posi['relation'] = 'NPC';
                $dst_posi['my_camp_troop'] = 'N';
                $dst_posi['truce'] = 'N';
                $dst_posi['truce_type'] = '';
                $dst_posi['my_troo_pk'] = null;
                $dst_posi['power'] = null;
            }

            $cmd_type = $row['cmd_type'];

            // 명령 가능여부 검사 (truce 체크 포함)
            $cmd_type_possible = false;

            // 내/동맹 영지 -> 타유저영지는 무조건 가능
            if ($cmd_type == 'T') {
                //if ($dst_posi['type'] == 'T' && ($dst_posi['relation'] == 'MIME' || $dst_posi['relation'] == 'ALLY' || $dst_posi['relation'] == 'ALLY_F'))
                if ($dst_posi['type'] == 'T' && $dst_posi['relation'] != 'NPC') {
                    $cmd_type_possible = true;
                }
            } else if ($cmd_type == 'P') {
                // 내 주둔군
                if ($dst_posi['my_camp_troop'] =='Y') {
                    $cmd_type_possible = true;
                }
            } else if ($cmd_type == 'R') {
                // 내 영지/자원지
                if ($dst_posi['relation'] == 'MIME') {
                    $cmd_type_possible = true;
                }

                // 동맹/우호 영지 -> 타유저영지는 무조건 가능
                //if ($dst_posi['type'] == 'T' && ($dst_posi['relation'] == 'ALLY' || $dst_posi['relation'] == 'ALLY_F'))
                if ($dst_posi['type'] == 'T' && $dst_posi['relation'] != 'NPC') {
                    $cmd_type_possible = true;
                }
            } else if ($cmd_type == 'S') {
                // NPC/LORD/ALLY_H , 보호모드 제외
                if ($dst_posi['truce'] == 'N' && ($dst_posi['relation'] == 'NPC' || $dst_posi['relation'] == 'LORD' || $dst_posi['relation'] == 'ALLY_H')) {
                    $cmd_type_possible = true;
                }

                if ($dst_posi['relation'] != 'NPC') {
                    // 평화의날
                    $z_today = date('YmdHis');
                    if ($z_today >= 20110503070000 && $z_today <= 20110503235959) {
                        $cmd_type_possible = false;
                    }

                    // 테스트용
                    if ($z_today >= 20110426150000 && $z_today <= 20110427235959) {
                        $cmd_type_possible = false;
                    }
                }

                if ($dst_posi['type'] == 'P' && $row['src_lord_pk'] != $dst_posi['lord_pk']) {
                    $cmd_type_possible = true;
                    // 시간 제한
                    if (CONF_POINT_BATTLE_ALWAYS_POSSIBLE != 'Y') {
                        if (date('N') == 2 || date('N') == 3) { // 화요일과 수요일은 전투 안됨
                            $cmd_type_possible = false;
                        } else {
                            $z_today = date('G');
                            if ($z_today >= 0 && $z_today < 10) {
                                $cmd_type_possible = false;
                            }
                        }
                    }
                }

            } else if ($cmd_type == 'A') {
                // NPC/LORD/ALLY_H , 보호모드 제외
                if (($row['troop_type'] == 'N' || $dst_posi['truce'] == 'N') && ($dst_posi['relation'] == 'NPC' || $dst_posi['relation'] == 'LORD' || $dst_posi['relation'] == 'ALLY_H')) {
                    $cmd_type_possible = true;
                }

                if ($dst_posi['relation'] != 'NPC') {
                    // 평화의날
                    $z_today = date('YmdHis');
                    if ($z_today >= 20110503070000 && $z_today <= 20110503235959) {
                        $cmd_type_possible = false;
                    }

                    // 테스트용
                    if ($z_today >= 20110426150000 && $z_today <= 20110427235959) {
                        $cmd_type_possible = false;
                    }
                }

                if ($row['src_lord_pk'] == NPC_TROOP_LORD_PK) {
                    $cmd_type_possible = true;
                }

                if ($dst_posi['type'] == 'P' && $row['src_lord_pk'] != $dst_posi['lord_pk']) {
                    $cmd_type_possible = true;
                    // 시간 제한
                    if (CONF_POINT_BATTLE_ALWAYS_POSSIBLE != 'Y') {
                        if (date('N') == 2 || date('N') == 3) { // 화요일과 수요일은 전투 안됨
                            $cmd_type_possible = false;
                        } else {
                            $z_today = date('G');
                            if ($z_today >= 0 && $z_today < 10) {
                                $cmd_type_possible = false;
                            }
                        }
                    }
                }
            }

            // 대상 좌표의 변화로 명령수행 불가 -> 자동회군 처리
            if (! $cmd_type_possible) {
                $Troop->setStatusRecall($_troo_pk, $row);
                Debug::debugMessage('DEBUG', '대상 좌표의 변화로 명령수행 불가 -> 자동회군 처리');
                echo "[OK]";
                $PgGame->commit(); // 이 시점에서 커밋하여야함.
                exit;

                // TODO - 명령은 수행가능 하더라도 dst 의 정보가 변경되었을 수도 있다.
                //  큰 것은 dst_lord_pk 와 dst_alli_pk 정도가 되겠다.
            }

            if ($cmd_type == 'T') { // 수송
                $Troop->setTransport($_troo_pk, $row, $dst_posi, $lord_name);
            } else if ($cmd_type == 'P') { // 보급
                $Troop->setPrevalence($_troo_pk, $row, $lord_name, $dst_posi['lord_name']);
            } else if ($cmd_type == 'R') { // 지원
                $Troop->setReinforce($_troo_pk, $row, $dst_posi, $lord_name);
            } else if ($cmd_type == 'S') { // 정찰
                $Troop->setScout($_troo_pk, $row, $dst_posi);
            } else if ($cmd_type == 'A') { // 공격
                // 출정 : 영웅 + 병력 + 자원 + 황금
                // 저장 : 자원지나 영지 획득시 전체 저장
                // 복귀 : 미회득시 전체 or nothing
                //  - troop 의 경우 즉시 전투 (자원지)
                //  - territory 의 경우 가상 troop 생성 후 전투 (영지)
                //  - npc 의 경우 가상 troop 생성 후 첩보전 (거점/자원지/영지)
                $NsGlobal->requireMasterData(['hero', 'hero_base']);

                // 보고서
                $z_content = [];
                $z_content['outcome'] = [];
                $z_content['battle_info'] = [];
                $z_content['plunder'] = [];
                $z_content['outcome_hero'] = [];
                $z_content['outcome_unit'] = [];
                $z_content_battle = [];
                $z_content['hero_battle'] = [];
                $z_content['outcome']['hero_skill_special']['loyalty'] = false;
                $z_content['outcome']['hero_skill_special']['plunder'] = false;
                $z_content['type'] = $dst_posi['type'];
                $z_content['use_item_pk'] = (! isset($row['use_item_pk'])) ? false : $row['use_item_pk'];

                // 전투타입 (실제로는 아래에서 결정)
                $battle_type = 'valley';

                if ($dst_posi['type'] == 'P') {
                    // 해당 요충지 상태 업데이트
                    $PgGame->query('UPDATE position_point SET last_batt_dt = now() WHERE posi_pk = $1', [$row['dst_posi_pk']]);
                }

                // 부대정보 추출
                if ($dst_posi['relation'] == 'NPC') {
                    if ($dst_posi['type'] == 'D') { // 황건 토벌지역
                        // 이벤트 인지 확인
                        /*$query_params = Array($row['dst_posi_pk'], 'N', $row['src_lord_pk']);
                        $PgGame->query('SELECT t1.supp_pk FROM suppress_position t1, my_event t2 WHERE t2.lord_pk = $3 AND t1.supp_pk = t2.event_supp_pk AND t1.posi_pk = $1 AND t2.event_supp_success = $2', $query_params);
                        $supp_pk = $PgGame->fetchOne();

                        if ($supp_pk) {
                            $dst_troop = $Troop->getDstTroopFromNpcSuppressEvent($row['src_lord_pk'], $row['dst_posi_pk']);
                        } else {
                            $dst_troop = $Troop->getDstTroopFromNpcSuppress($row['src_lord_pk'], $row['dst_posi_pk']);
                        }*/
                        $dst_troop = $Troop->getDstTroopFromNpcSuppress($row['src_lord_pk'], $row['dst_posi_pk']);
                        $z_content['battle_info']['def_type'] = 'suppress';
                    } else if ($dst_posi['type'] == 'N') { // 황건 성
                        $dst_troop = $Troop->getDstTroopFromNpcTerritory($row['dst_posi_pk']);
                        $battle_type = 'territory';
                    } else if ($dst_posi['type'] == 'P') {
                        $dst_troop = $Troop->getDstTroopFromNpcPoint($row['dst_posi_pk']);
                    } else if ($dst_posi['type'] == 'S') {
                        // 섬멸전 부대 정보 가져오기
                        $dst_troop = $raid_troop;
                        $battle_type = 'raid';

                        $buff_item_pk = 500730;

                        // 섬멸전 전용 버프 입력
                        if ($row['src_posi_pk'] !== NPC_TROOP_POSI_PK) {
                            $Item->setItemBuff($row['src_lord_pk'], $row['src_posi_pk'], $buff_item_pk);
                        }

                        // 사용된 아이템이 있을 경우 버프 입력
                        if (isset($row['use_item_pk'])) {
                            $Item->setItemBuff($row['src_lord_pk'], $row['src_posi_pk'], $row['use_item_pk']);
                        }
                    } else { // 황건 자원지
                        $dst_troop = $Troop->getDstTroopFromNpcValley($row['dst_posi_pk']);
                    }
                } else {
                    if ($dst_posi['type'] == 'T') { // 타 군주 영지
                        $dst_troop = $Troop->getDstTroopFromLordTerritory($row['dst_posi_pk']);
                        $battle_type = 'territory';

                        // 방어전투 발생 않는 스킬
                        $r = $Troop->possibleBattle($_troo_pk, $row, $dst_troop);
                        if (!$r) {
                            echo "[OK]";
                            $PgGame->commit(); // 이 시점에서 커밋하여야함.
                            exit;
                        }
                    } else { // 타 군주 자원지
                        $dst_troop = $Troop->getDstTroopFromLordValley($row['dst_posi_pk']);
                        if ($dst_troop) {
                            $row['dst_lord_pk'] = $dst_troop['src_lord_pk'];
                        } else {
                            // 타군주의 자원지내 부대 정보가 없고(회군 및 철수) 황건적이 아니며 이전 부대정보의 자원지 소유주가 현 자원지 소유주와 다를때 소유주를 맞춰 줌.
                            if ($dst_posi['lord_pk'] != NPC_TROOP_LORD_PK && $row['dst_lord_pk'] != $dst_posi['lord_pk']) {
                                $row['dst_lord_pk'] = $dst_posi['lord_pk'];
                            }
                        }
                    }
                }

                if (! isset($z_content['battle_info']['def_type'])) {
                    $z_content['battle_info']['def_type'] = $battle_type;
                }

                if (! $dst_troop) {
                    if ($battle_type == 'valley') {
                        $Troop->setAttackVally($_troo_pk, $row);
                    } else {
                        // NPC 군주 회군 등록 실패 처리 - 20230707 송누리
                        if ($row['src_lord_pk'] != NPC_TROOP_LORD_PK) {
                            $Troop->setStatusRecall($_troo_pk, $row);
                        }
                        Debug::debugMessage('ERROR', '대상 부대를 생성할 수 없습니다.');
                    }
                    echo "[OK]";
                    $PgGame->commit(); // 이 시점에서 커밋하여야함.
                    exit;
                }

                // NPC 자원지일 때 자원지 갱신을 대비하여 정보를 다시 가져와 입력
                if ($dst_posi['relation'] == 'NPC' && $battle_type == 'valley') {
                    $row['to_position'] = $Troop->getPositionName($row['dst_posi_pk'], null, true);
                }

                // 공/방 진영 생성

                // 공격 진영 선택
                global $attack_position_line1, $attack_position_line2, $attack_position_line3;
                $attack_position = [$attack_position_line1, $attack_position_line2, $attack_position_line3];

                // 방어 진영 선택
                if ($battle_type == 'valley') {
                    // 자원지
                    global $defence_position_line1, $defence_position_line2, $defence_position_line3;
                    $defence_position = [$defence_position_line1, $defence_position_line2, $defence_position_line3];
                } else {
                    if (isset($dst_troop['wall_open']) && $dst_troop['wall_open']) {
                        // 영지 - 성문개방
                        global $defence_position_wall_open_lineFort1, $defence_position_wall_open_lineFort2, $defence_position_wall_open_line1, $defence_position_wall_open_line2, $defence_position_wall_open_line3, $defence_position_wall_open_lineWall;
                        $defence_position = [$defence_position_wall_open_lineFort1, $defence_position_wall_open_lineFort2, $defence_position_wall_open_line1, $defence_position_wall_open_line2, $defence_position_wall_open_line3, $defence_position_wall_open_lineWall];
                    } else {
                        // 영지 - 성문폐쇄
                        //$defence_position = Array($defence_position_wall_close_lineFort1, $defence_position_wall_close_lineFort2, $defence_position_wall_close_lineWall, $defence_position_wall_close_line1, $defence_position_wall_close_line2, $defence_position_wall_close_line3);
                        global $defence_position_wall_close_lineFort1, $defence_position_wall_close_lineFort2, $defence_position_wall_close_lineWall;
                        $defence_position = [$defence_position_wall_close_lineFort1, $defence_position_wall_close_lineFort2, $defence_position_wall_close_lineWall];
                    }
                }

                $z_content['battle_info']['def_wall'] = (isset($dst_troop['wall_open']) && $dst_troop['wall_open']) ? 'open' : 'close';

                // 공격측
                $attack_army_converted_amount = 0;
                $attack['army'] = [];
                $attack['position'] = $attack_position;
                //$attack_army_converted_amount = $Troop->armyConvertedAmount($row, $battle_type, &$attack['army']);
                // 위 함수가 제대로 동작하지 않아 원본 소스로 대체.
                foreach ($row AS $k => $v) {
                    $z = substr($k, 0, 5);
                    if ($z == 'army_' || $z == 'fort_') {
                        if ($v > 0) {
                            $unit_type = substr($k, 5);
                            //$Battle->addUnit($battle_type, &$attack['army'], $unit_type, $v);
                            $Battle->addUnit($battle_type, $attack['army'], $unit_type, $v);
                            if ($z == 'army_' && isset($_M['ARMY_C'][$unit_type])) {
                                $attack_army_converted_amount += $_M['ARMY_C'][$unit_type]['need_population'] * $v;
                            }
                        }
                    }
                }
                //$Battle->positionAdjust(&$attack['position'], &$attack['army']);
                $Battle->positionAdjust($attack['position'], $attack['army']);

                // 방어측 병력
                $defence_army_converted_amount = 0;
                $defence['army'] = [];
                $defence['position'] = $defence_position;

                //$Battle->addUnit($battle_type, &$defence['army'], 'wall', $dst_troop['wall_level']); // level
                if (isset($dst_troop['wall_level'])) {
                    $Battle->addUnit($battle_type, $defence['army'], 'wall', $dst_troop['wall_level']); // level
                }
                //$defence_army_converted_amount = $Troop->armyConvertedAmount($dst_troop, $battle_type, &$defence['army']);
                // 위 함수가 제대로 동작하지 않아 원본 소스로 대체.
                foreach ($dst_troop AS $k => $v) {
                    $z = substr($k, 0, 5);
                    if ($z == 'army_' || $z == 'fort_') {
                        if ($v > 0) {
                            $unit_type = substr($k, 5);
                            //$Battle->addUnit($battle_type, &$defence['army'], $unit_type, $v);
                            $Battle->addUnit($battle_type, $defence['army'], $unit_type, $v);
                            if ($z == 'army_' && isset($_M['ARMY_C'][$unit_type])) {
                                $defence_army_converted_amount += $_M['ARMY_C'][$unit_type]['need_population'] * $v;
                            }
                        }
                    }
                }

                //$Battle->positionAdjust(&$defence['position'], &$defence['army']);
                $Battle->positionAdjust($defence['position'], $defence['army']);

                // 일기토
                $captain_m_hero_pk = null;
                $director_m_hero_pk = null;
                $staff_m_hero_pk = null;
                if (isset($row['captain_hero_pk'])) {
                    $captain_m_hero_pk = $Troop->getHeroMasterDataPK($row['captain_hero_pk']);
                }
                if (isset($row['director_hero_pk'])) {
                    $director_m_hero_pk = $Troop->getHeroMasterDataPK($row['director_hero_pk']);
                }
                if (isset($row['staff_hero_pk'])) {
                    $staff_m_hero_pk = $Troop->getHeroMasterDataPK($row['staff_hero_pk']);
                }

                $att_hero_arr = [
                    'captain_hero_pk' => $row['captain_hero_pk'],
                    'captain_m_hero_pk' => $captain_m_hero_pk,
                    'captain_desc' => $row['captain_desc'],
                    'director_hero_pk' => $row['director_hero_pk'],
                    'director_desc' => (isset($row['director_desc'])) ? $row['director_desc'] : null,
                    'director_m_hero_pk' => $director_m_hero_pk,
                    'staff_hero_pk' => $row['staff_hero_pk'],
                    'staff_m_hero_pk' => $staff_m_hero_pk,
                    'staff_desc' => (isset($row['staff_desc'])) ? $row['staff_desc'] : null
                ];

                $captain_m_hero_pk = null;
                $director_m_hero_pk = null;
                $staff_m_hero_pk = null;
                if (isset($dst_troop['captain_hero_pk'])) {
                    $captain_m_hero_pk = $Troop->getHeroMasterDataPK($dst_troop['captain_hero_pk']);
                }
                if (isset($dst_troop['director_hero_pk'])) {
                    $director_m_hero_pk = $Troop->getHeroMasterDataPK($dst_troop['director_hero_pk']);
                }
                if (isset($dst_troop['staff_hero_pk'])) {
                    $staff_m_hero_pk = $Troop->getHeroMasterDataPK($dst_troop['staff_hero_pk']);
                }

                $def_hero_arr = [
                    'captain_hero_pk' => $dst_troop['captain_hero_pk'] ?? null,
                    'captain_m_hero_pk' => $captain_m_hero_pk ?? null,
                    'captain_desc' => $dst_troop['captain_desc'] ?? null,
                    'director_hero_pk' => $dst_troop['director_hero_pk'] ?? null,
                    'director_desc' => (isset($dst_troop['director_desc'])) ? $dst_troop['director_desc'] : null,
                    'director_m_hero_pk' => $director_m_hero_pk ?? null,
                    'staff_hero_pk' => $dst_troop['staff_hero_pk'] ?? null,
                    'staff_m_hero_pk' => $staff_m_hero_pk ?? null,
                    'staff_desc' => (isset($dst_troop['staff_desc'])) ? $dst_troop['staff_desc'] : null
                ];

                $man_to_man_ret = $Battle->setBattleManToMan($att_hero_arr, $row['src_posi_pk'], $def_hero_arr, $row['dst_posi_pk']);


                // 일기토후 정상, 경상만 전투 참여.(중상, 치명상은 전투 참여 안함 - 영웅 수치 전투에작용 안됨)
                /*if ($man_to_man_ret['battle_turn'] > 0) {
                    if ($man_to_man_ret['att_hero']) {
                        //$Troop->heroBattleResult($man_to_man_ret['att_hero'], &$row, &$att_hero_arr, $_troo_pk);
                        $Troop->heroBattleResult($man_to_man_ret['att_hero'], $row, $att_hero_arr, $_troo_pk);
                    }

                    if ($man_to_man_ret['def_hero']) {
                        //$Troop->heroBattleResult($man_to_man_ret['def_hero'], &$dst_troop, &$def_hero_arr, $dst_troop['troo_pk']);
                        $Troop->heroBattleResult($man_to_man_ret['def_hero'], $dst_troop, $def_hero_arr, $dst_troop['troo_pk']);
                    }
                }*/


                $result = $man_to_man_ret['result'];

                $att_figth_spirit_up = 0;
                $def_figth_spirit_down = 0;
                $def_figth_spirit_up = 0;
                $att_figth_spirit_down = 0;

                // 일기토 승패여부
                $win = 'att';
                if ($result == BATTLE_MANTOMAN_ATTACK_WIN) {
                    // 공격측 승리
                    $att_figth_spirit_up = 0.1;
                    $def_figth_spirit_down = 0.05;

                    // 일기토 참여한 영웅 스킬 경험치 증가
                    if ($man_to_man_ret['battle_turn'] > 0) {
                        $HeroSkill->updateHeroBattleSkillExp($row['src_lord_pk'], $row['src_posi_pk'], $man_to_man_ret['att_hero']);
                    }
                } else if ($result == BATTLE_MANTOMAN_DEFENCE_WIN) {
                    // 방어측 승리
                    $win = 'def';
                    $def_figth_spirit_up = 0.1;
                    $att_figth_spirit_down = 0.05;

                    // 일기토 참여한 영웅 스킬 경험치 증가
                    if ($man_to_man_ret['battle_turn'] > 0) {
                        $HeroSkill->updateHeroBattleSkillExp($row['dst_lord_pk'], $dst_troop['src_posi_pk'], $man_to_man_ret['def_hero']);
                    }
                }

                // 보고서 - 일기토
                $z_content['hero_battle']['win'] = $win;
                $z_content['hero_battle']['battle_turn'] = $man_to_man_ret['battle_turn'];
                // 영웅
                $z_content['outcome_hero']['att'] = $att_hero_arr;
                $z_content['outcome_hero']['def'] = $def_hero_arr;

                // 부대 사기 정보
                $z_content['spirit_info'] = [];

                // 일기토 과정
                $z_content_battle['hero_battle'] = $man_to_man_ret;

                // 적용될 효과 추출

                // 적정 통솔 인원
                // 주장의 통솔력
                $z_content_battle['hero_battle']['att']['npc_type'] = false;

                $prev_att_fighting_spirit = 0;

                $att_army_population = 0;
                $att_maintain_army = 0;

                $def_army_population = 0;
                $def_maintain_army = 0;

                if ($row['src_lord_pk'] == NPC_TROOP_LORD_PK) {
                    $fighting_spirit = 100;

                    foreach ($attack['army'] AS $k => $v) {
                        // 공격력
                        $attack['army'][$k]['attack'] *= $fighting_spirit;

                        // 방어력
                        $attack['army'][$k]['defence'] *= $fighting_spirit;

                        $attack['army'][$k]['max_values'] = 0; // 오류 대응 20230707 송누리

                        $z_content_battle['hero_battle']['att']['npc_type'] = true;
                    }
                } else {
                    $Effect->initEffects();
                    $att_lead_population = $Troop->getHeroLeadership($row['captain_hero_pk'], 'ATTACK');

                    // 통솔력 증가
                    $capacities = $Effect->getHeroCapacityEffects($row['captain_hero_pk']);
                    $applies = $Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_CAPTAIN, $capacities);

                    $ret = $Effect->getEffectedValue($row['src_posi_pk'], ['troop_leadership_increase'], $att_lead_population, $applies['all']);

                    $att_lead_population = $ret['value'];
                    $att_hero_arr['leadership'] = $att_lead_population;

                    // 윤리도
                    $morals = $Troop->getMorals($row['src_lord_pk'], $row['dst_lord_pk'], $dst_posi['relation'], $dst_posi['type']);

                    // 부대 사기
                    $fighting_spirit_down = 0;

                    $armyPop_population = $Troop->getArmyPopulation($row);
                    $att_lead_limit_population = $armyPop_population - $att_lead_population;
                    $att_army_population = $armyPop_population;
                    $att_maintain_army = $att_lead_population;
                    //$fighting_spirit_down = $Troop->getFightingSpiritDown($att_lead_limit_population);
                    // 통솔한계효과
                    foreach ($_M['TROOP']['FIGHTING_ATTACK_SPIRIT_DOWN'] AS $k => $v) {
                        if ($att_lead_limit_population <= 0) {
                            break;
                        } else if($att_lead_limit_population <= $k) {
                            $fighting_spirit_down = $v;
                            break;
                        }
                    }
                    $fighting_spirit = $morals - $fighting_spirit_down;
                    // 2012.06 :: $Troop->getFightingSpirit()를 사용하지 않고 바로 위에서 처리
                    //$fighting_spirit = $Troop->getFightingSpirit($row['captain_hero_pk'], $row['src_posi_pk'], $fighting_spirit, $att_figth_spirit_up, $att_figth_spirit_down);

                    $prev_att_fighting_spirit = $fighting_spirit;

                    // 공격측 영웅 명령 효과(태학포함)
                    $capacities = $Effect->getHeroCapacityEffects($row['captain_hero_pk']);
                    $applies = $Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_CAPTAIN, $capacities);
                    $ret = $Effect->getEffectedValue($row['src_posi_pk'], ['troop_fighting_spirit_increase'], $fighting_spirit, $applies['all']);
                    $fighting_spirit = $ret['value'];

                    //echo '윤리도:'.$morals .'부대사기:'.$fighting_spirit;
                    if ($att_figth_spirit_up) {
                        $att_figth_spirit_up *= $fighting_spirit;
                        $fighting_spirit += $att_figth_spirit_up;
                    }

                    if ($att_figth_spirit_down) {
                        $att_figth_spirit_down *= $fighting_spirit;
                        $fighting_spirit -= $att_figth_spirit_down;
                    }

                    if ($fighting_spirit > 110) {
                        $fighting_spirit = 110;
                    } else if ($fighting_spirit < 1) {
                        $fighting_spirit = 1;
                    }

                    // 병과별 기본 공격력
                    $src_army_category = $Troop->getArmyCategory($row);

                    // 이펙트 적용
                    $att_applies = [];
                    $def_applies = [];
                    list($att_hero_arr['attack'], $att_hero_arr['defence'], $att_applies, $def_applies) = $Troop->getHeroEffect($row['director_hero_pk'], $row['staff_hero_pk'], $row['src_posi_pk']);

                    // 부대 효과 적용
                    $att_effect_types = [];
                    $def_effect_types = [];

                    //list($att_effect_types, $def_effect_types) = $Troop->getArmyEffect(&$attack['army'], $fighting_spirit, $src_army_category, $row['src_posi_pk'], null, $att_applies, $def_applies);
                    list($att_effect_types, $def_effect_types) = $Troop->getArmyEffect($attack['army'], $fighting_spirit, $src_army_category, $row['src_posi_pk'], null, $att_applies, $def_applies, $row);

                    // 아이템 효과
                    $att_item = $Troop->getItemEffect($row['src_posi_pk']);

                    // 영웅 스킬 효과
                    $z_content['outcome']['hero_skill']['att_hero_skill'] = $Troop->getHeroSkillEffect($row['src_posi_pk'], $row['captain_hero_pk'], $row['director_hero_pk'], $row['staff_hero_pk'], $att_hero_arr, $att_effect_types, $def_effect_types);

                    $fighting_spirit = intval($fighting_spirit);

                    // TODO 임시 공방 영향력
                    $_powers = $Troop->getPowers($row['src_lord_pk'], $row['dst_lord_pk'], $dst_posi['relation']);

                    // $Log->setTemp($row['src_lord_pk'], $row['src_posi_pk'], 'FightingSpirit', $armyPop_population, $att_lead_population, $_powers['att_power'], $_powers['def_power'], $dst_posi['relation'], $morals, $att_lead_limit_population, $fighting_spirit_down, $ret['value'], $win, $fighting_spirit);

                }

                // 공격측 부대 사기
                $z_content['spirit_info']['prev_att'] = $prev_att_fighting_spirit;
                $z_content['spirit_info']['att'] = $fighting_spirit;

                // 공격측 군주 정보
                $att_lord_info = [];
                $att_lord_info['lord_name'] = $lord_name;
                $att_lord_info['lord_level'] = $lord_level;
                if ($alli_pk !== null) {
                    $att_lord_info['alli_title'] = $Alliance->getAllianceTitle($alli_pk);
                }
                $att_lord_info['fighting_spirit'] = $fighting_spirit;

                // 적정 통솔 인원
                // 주장의 통솔력
                $z_content_battle['hero_battle']['def']['npc_type'] = false;
                if ($dst_posi['relation'] == 'NPC') {
                    $fighting_spirit = 100;
                    $prev_def_fightingSpirit = $fighting_spirit;

                    foreach ($defence['army'] AS $k => $v) {
                        // 공격력
                        $defence['army'][$k]['attack'] *= $fighting_spirit;
                        // 방어력
                        $defence['army'][$k]['defence'] *= $fighting_spirit;
                    }

                    $z_content_battle['hero_battle']['def']['npc_type'] = true;
                } else {
                    $Effect->initEffects();

                    $att_lead_population = $Troop->getHeroLeadership($dst_troop['captain_hero_pk'], 'DEFENCE');

                    // 통솔력 증가
                    $applies = [];
                    $capacities = $Effect->getHeroCapacityEffects($dst_troop['captain_hero_pk']);
                    $applies = $Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_CAPTAIN, $capacities);
                    $ret = $Effect->getEffectedValue($dst_troop['src_posi_pk'], ['troop_leadership_increase'], $att_lead_population, $applies['all']);
                    $leadership_attack = $ret['value'];
                    $def_hero_arr['leadership'] = $leadership_attack;

                    // 환산병력수
                    $army_arr = [];
                    foreach ($_M['ARMY_C'] AS $k => $v) {
                        $army_arr[$k] = $dst_troop['army_'. $k];
                    }

                    $armyPop = $Troop->getArmyPop($army_arr);
                    //환산병력수 = $armyPop['need_population'];

                    $def_army_population = $armyPop['population'];
                    $def_maintain_army = $leadership_attack;

                    // 윤리도
                    $morals = 100;

                    // 부대 사기
                    $fighting_spirit_down = 0;
                    $att_lead_limit_population = $Troop->getArmyPopulation($dst_troop) - $leadership_attack;
                    //$fighting_spirit_down = $Troop->getFightingSpiritDown($att_lead_limit_population);
                    // 통솔한계효과
                    foreach ($_M['TROOP']['FIGHTING_DEFENCE_SPIRIT_DOWN'] AS $k => $v) {
                        if ($att_lead_limit_population <= 0) {
                            break;
                        } else if($att_lead_limit_population <= $k) {
                            $fighting_spirit_down = $v;
                            break;
                        }
                    }
                    $fighting_spirit = $morals - $fighting_spirit_down;
                    //$fighting_spirit = $Troop->getFightingSpirit($dst_troop['captain_hero_pk'], $dst_troop['src_posi_pk'], $fighting_spirit, $def_figth_spirit_up, $def_figth_spirit_down);

                    $prev_def_fightingSpirit = $fighting_spirit;

                    // 방어측 영웅 명령 효과(태학포함)
                    $capacities = $Effect->getHeroCapacityEffects($dst_troop['captain_hero_pk']);
                    $applies = $Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_CAPTAIN, $capacities);
                    $ret = $Effect->getEffectedValue($dst_troop['src_posi_pk'], ['troop_fighting_spirit_increase'], $fighting_spirit, $applies['all']);
                    $fighting_spirit = $ret['value'];

                    if ($def_figth_spirit_up) {
                        $def_figth_spirit_up *= $fighting_spirit;
                        $fighting_spirit += $def_figth_spirit_up;
                    }

                    if ($def_figth_spirit_down) {
                        $def_figth_spirit_down *= $fighting_spirit;
                        $fighting_spirit -= $def_figth_spirit_down;
                    }

                    if ($fighting_spirit > 110) {
                        $fighting_spirit = 110;
                    }

                    // $fighting_spirit = intval($fighting_spirit);

                    // 병과별 기본 공격력
                    $dst_army_category = $Troop->getArmyCategory($dst_troop);

                    // 이펙트 적용
                    $_t_dst_posi_pk = $row['dst_posi_pk'];
                    if ($dst_posi['type'] != 'T') {
                        $PgGame->query('SELECT posi_pk FROM territory_valley WHERE valley_posi_pk = $1', [$row['dst_posi_pk']]);
                        if ($PgGame->getNumRows() == 1) {
                            $_t_dst_posi_pk = $PgGame->fetchOne();
                        }
                    }

                    $att_applies = [];
                    $def_applies = [];
                    list($def_hero_arr['attack'], $def_hero_arr['defence'], $att_applies, $def_applies) = $Troop->getHeroEffect($dst_troop['director_hero_pk'], $dst_troop['staff_hero_pk'], $_t_dst_posi_pk);

                    // 부대 효과 적용
                    $att_effect_types = [];
                    $def_effect_types = [];
                    //list($att_effect_types, $def_effect_types) = $Troop->getArmyEffect(&$defence['army'], $fighting_spirit, $dst_army_category, $_t_dst_posi_pk, 'defence', $att_applies, $def_applies);
                    list($att_effect_types, $def_effect_types) = $Troop->getArmyEffect($defence['army'], $fighting_spirit, $dst_army_category, $_t_dst_posi_pk, 'defence', $att_applies, $def_applies, $dst_troop);

                    // 아이템 효과
                    $def_item = $Troop->getItemEffect($_t_dst_posi_pk);

                    // 영웅 스킬 효과
                    $z_content['outcome']['hero_skill']['def_hero_skill'] = $Troop->getHeroSkillEffect($dst_troop['src_posi_pk'], $dst_troop['captain_hero_pk'], $dst_troop['director_hero_pk'], $dst_troop['staff_hero_pk'], $def_hero_arr, $att_effect_types, $def_effect_types);
                }

                // 방어측 부대 사기
                $z_content['spirit_info']['prev_def'] = $prev_def_fightingSpirit;
                $z_content['spirit_info']['def'] = $fighting_spirit;


                // 전투 스킬(사전 발동) -- 현재는 참모만 처리
                // 공격측
                $att_battle_skil_arr = [];

                // 참모
                $skill_sql = 'SELECT a.hero_pk, b.m_hero_skil_pk FROM hero a, my_hero_skill_slot b, m_hero_skill c WHERE a.hero_pk = $1 AND a.hero_pk = b.hero_pk AND b.m_hero_skil_pk = c.m_hero_skil_pk AND c.m_cmd_pk = $2 AND type = $3 AND exercise_type = $4 GROUP BY a.hero_pk, b.m_hero_skil_pk';
                $PgGame->query($skill_sql, [$row['staff_hero_pk'], PK_CMD_TROOP_STAFF, 'B', 'B']);
                while($PgGame->fetch()) {
                    $att_battle_skil_arr[] = $PgGame->row;
                }

                $att_amount = $defence['army'];
                $z_content_battle['hero_battle']['att']['before_battle_skill'] = [];
                $z_content_battle['hero_battle']['att']['before_battle_skill']['pk'] = null;
                $z_content_battle['hero_battle']['att']['before_battle_skill']['hero_pk'] = null;
                if ($att_battle_skil_arr) {
                    $m_att_hero_skil_pk = $HeroSkill->getBattleHeroSkill($att_battle_skil_arr, 'B');

                    if (substr($m_att_hero_skil_pk['m_pk'] ?? '', 0, 4) == 1573) {
                        if ($att_army_population < ($att_maintain_army / 2)) {
                            $m_att_hero_skil_pk = null;
                        }

                        // 화계 밸런스 패치
                        if ($attack_army_converted_amount < ($defence_army_converted_amount / 2)) {
                            $m_att_hero_skil_pk = null;
                        }
                    }

                    if ($m_att_hero_skil_pk !== null) {
                        $z_content_battle['hero_battle']['att']['before_battle_skill']['pk'] = $m_att_hero_skil_pk['m_pk'];
                        $z_content_battle['hero_battle']['att']['before_battle_skill']['hero_pk'] = $Troop->getHeroMasterDataPK($m_att_hero_skil_pk['hero_pk']);
                        list($attack['army'], $defence['army']) = $HeroSkill->setBattleBeforeSkill($m_att_hero_skil_pk['m_pk'], $attack['army'], $defence['army'], $dst_troop['wall_open']);
                        if ($m_att_hero_skil_pk['m_pk'] && $row['src_lord_pk'] != NPC_TROOP_LORD_PK) {
                            $Log->setHeroSkillActive($row['src_lord_pk'], $row['src_posi_pk'], $z_content_battle['hero_battle']['att']['before_battle_skill']['hero_pk'], 'before_battle_skill:'.$m_att_hero_skil_pk['m_pk'], 0, 0, $_troo_pk);
                        }
                    }
                }
                // 방어측
                $def_battle_skil_arr = [];
                if ($dst_posi['relation'] == 'NPC') {
                    $def_battle_skil_arr = $HeroSkill->setNPCHeroBattleSkill($dst_posi, $dst_troop);
                } else {
                    // 참모
                    $query_params = [$dst_troop['staff_hero_pk'], PK_CMD_TROOP_STAFF, 'B', 'B'];
                    $PgGame->query($skill_sql, $query_params);
                    while($PgGame->fetch()) {
                        $def_battle_skil_arr[] = $PgGame->row;
                    }
                }
                $def_amount = $attack['army'];
                $z_content_battle['hero_battle']['def']['before_battle_skill'] = [];
                $z_content_battle['hero_battle']['def']['before_battle_skill']['pk'] = null;
                $z_content_battle['hero_battle']['def']['before_battle_skill']['hero_pk'] = null;
                if ($def_battle_skil_arr) {
                    $m_def_hero_skil_pk = $HeroSkill->getBattleHeroSkill($def_battle_skil_arr, 'B');

                    if (isset($m_def_hero_skil_pk)) {
                        if (substr($m_def_hero_skil_pk['m_pk'] ?? '', 0, 4) == 1573) {
                            if ($def_army_population < ($def_maintain_army / 2)) {
                                $m_def_hero_skil_pk = null;
                            }

                            // 화계 밸런스 패치
                            if ($defence_army_converted_amount < ($attack_army_converted_amount / 2)) {
                                $m_att_hero_skil_pk = null;
                            }
                        }
                    }

                    if ($m_def_hero_skil_pk !== null) {
                        $z_content_battle['hero_battle']['def']['before_battle_skill']['pk'] = $m_def_hero_skil_pk['m_pk'];
                        $z_content_battle['hero_battle']['def']['before_battle_skill']['hero_pk'] = $Troop->getHeroMasterDataPK($m_def_hero_skil_pk['hero_pk']);
                        list($defence['army'], $attack['army']) = $HeroSkill->setBattleBeforeSkill($m_def_hero_skil_pk['m_pk'], $defence['army'], $attack['army']);
                        if ($m_def_hero_skil_pk['m_pk'] && $row['dst_lord_pk'] != NPC_TROOP_LORD_PK) {
                            $Log->setHeroSkillActive($row['dst_lord_pk'], $row['dst_posi_pk'], $z_content_battle['hero_battle']['def']['before_battle_skill']['hero_pk'], 'before_battle_skill:'.$m_def_hero_skil_pk['m_pk'], 0, 0, $_troo_pk);
                        }
                    }
                }

                // 전투 스킬(합 진행시 발동)

                // 공격측
                $att_battle_skil_arr = [];

                // 주장
                $PgGame->query($skill_sql, [$row['captain_hero_pk'], PK_CMD_TROOP_CAPTAIN, 'B', 'P']);
                while($PgGame->fetch()) {
                    $att_battle_skil_arr[] = $PgGame->row;
                }
                // 부장
                $PgGame->query($skill_sql, [$row['director_hero_pk'], PK_CMD_TROOP_DIRECTOR, 'B', 'P']);
                while($PgGame->fetch()) {
                    $att_battle_skil_arr[] = $PgGame->row;
                }
                // 참모
                $PgGame->query($skill_sql, [$row['staff_hero_pk'], PK_CMD_TROOP_STAFF, 'B', 'P']);
                while($PgGame->fetch()) {
                    $att_battle_skil_arr[] = $PgGame->row;
                }
                // 방어측
                if ($dst_posi['relation'] != 'NPC') {
                    // 주장
                    $PgGame->query($skill_sql, [$dst_troop['captain_hero_pk'], PK_CMD_TROOP_CAPTAIN, 'B', 'P']);
                    while($PgGame->fetch()) {
                        $def_battle_skil_arr[] = $PgGame->row;
                    }
                    // 부장
                    $PgGame->query($skill_sql, [$dst_troop['director_hero_pk'], PK_CMD_TROOP_DIRECTOR, 'B', 'P']);
                    while($PgGame->fetch()) {
                        $def_battle_skil_arr[] = $PgGame->row;
                    }
                    // 참모
                    $PgGame->query($skill_sql, [$dst_troop['staff_hero_pk'], PK_CMD_TROOP_STAFF, 'B', 'P']);
                    while($PgGame->fetch()) {
                        $def_battle_skil_arr[] = $PgGame->row;
                    }
                }

                // 방어측 군주 정보
                $def_lord_info = [];
                $def_lord_info['lord_name'] = $dst_posi['lord_name'];
                $def_lord_info['lord_level'] = $dst_posi['lord_level'];
                if (isset($dst_posi['alli_pk'])) {
                    $def_lord_info['alli_title'] = $Alliance->getAllianceTitle($dst_posi['alli_pk']);
                }
                $def_lord_info['fighting_spirit'] = $fighting_spirit;

                // 전투 참여 정보
                $att_remain_captain = true;
                $def_remain_captain = true;

                $z_content_battle['battle_info']['att']['hero_info'] = $att_hero_arr;
                $z_content_battle['battle_info']['att']['lord_info'] = $att_lord_info;
                $z_content_battle['battle_info']['att']['item'] = $att_item ?? null;
                $z_content_battle['battle_info']['def']['hero_info'] = $def_hero_arr;
                $z_content_battle['battle_info']['def']['lord_info'] = $def_lord_info;
                $z_content_battle['battle_info']['def']['item'] = $def_item ?? null;

                /* 영웅 부상 제거로 주석 처리
                if ($man_to_man_ret['battle_turn'] > 0) {
                    if (isset($man_to_man_ret['att_hero'])) {
                        // $att_remain_captain = $Troop->setHeroBattleResult(&$row, $man_to_man_ret['att_hero'], $_troo_pk, 'att');
                        $att_remain_captain = $Troop->setHeroBattleResult($row, $man_to_man_ret['att_hero'], $_troo_pk, 'att');
                    }

                    if (isset($man_to_man_ret['def_hero'])) {
                        // $def_remain_captain = $Troop->setHeroBattleResult(&$dst_troop, $man_to_man_ret['def_hero'], $dst_troop['troo_pk'], 'def', $battle_type);
                        $def_remain_captain = $Troop->setHeroBattleResult($dst_troop, $man_to_man_ret['def_hero'], $dst_troop['troo_pk'], 'def', $battle_type);
                    }
                }*/

                $z_content_battle['battle_info']['unit_info']['att'] = $att_amount;
                $z_content_battle['battle_info']['unit_info']['def'] = $def_amount;

                $att =& $attack['position'];
                $att_data =& $attack['army'];
                $att_result = [];
                $def =& $defence['position'];
                $def_data =& $defence['army'];
                $def_result = [];

                // 전투
                //list($turn_description, $turn_count, $att_success, $def_success) = $Battle->doBattleMaxTurn($battle_type, &$att, &$att_data, &$def, &$def_data, &$z_content_battle);
                //list($turn_description, $turn_count, $att_success, $def_success) = $Battle->doBattleMaxTurn($battle_type, &$att, &$att_data, &$def, &$def_data, &$z_content_battle, $att_battle_skil_arr, $def_battle_skil_arr);
                list($turn_description, $turn_count, $att_success, $def_success) = $Battle->doBattleMaxTurn($battle_type, $att, $att_data, $def, $def_data, $z_content_battle, $att_battle_skil_arr, $def_battle_skil_arr);

                $z_content['outcome']['winner'] = $att_success ? 'att' : 'def';

                $z_content['battle_info']['unit_battle_final_scene'] = $turn_count;
                $z_content['battle_info']['unit_battle_winner'] = $att_success ? 'att' : 'def';
                //$z_content['turn_description'] = $turn_description;

                $z_content['outcome_unit']['att'] = [];
                foreach ($att_data AS $k => $v) {
                    if ($v) {
                        $z_content['outcome_unit']['att'][$k] = ['amount' => $v['unit_amount'], 'remain' => $v['unit_remain']];
                    }
                }

                $z_content['outcome_unit']['def'] = [];
                foreach ($def_data AS $k => $v) {
                    if ($v) {
                        $z_content['outcome_unit']['def'][$k] = ['amount' => $v['unit_amount'], 'remain' => $v['unit_remain']];
                    }
                }

                $att_remain_army = false;
                $def_remain_army = false;

                foreach ($att_data AS $v) {
                    if ($v['unit_remain']) {
                        $att_remain_army = true;
                        break;
                    }
                }

                foreach ($def_data AS $v) {
                    if ($v['unit_remain']) {
                        $def_remain_army = true;
                        break;
                    }
                }

                $def_dead_army = [];
                $att_dead_army = [];

                // 방어측 피해적용 (영웅,병사,자원)
                if ($dst_posi['relation'] == 'NPC') {
                    // NPC영지
                    if($dst_posi['type'] == 'N') {
                        $ret = $Battle->doApplyDamage('territory_npc', $def_data, $row['dst_posi_pk']);
                    } else if ($dst_posi['type'] == 'P'){
                        $ret = $Battle->doApplyDamage('point_npc', $def_data, $row['dst_posi_pk']);
                    } else if ($dst_posi['type'] == 'S'){
                        $ret = $Battle->doApplyDamage('raid_npc', $def_data, null, null, $row['raid_troo_pk']);
                    }
                    // 사망 병사
                    $def_dead_army = $ret['dead_army'] ?? null;
                } else {
                    $injury_army = [];
                    $injury_heroes = [];

                    // 군주영지
                    if ($dst_posi['type'] == 'T') {
                        // 사병 부상 처리
                        $ally_info = [];
                        $PgGame->query('SELECT troo_pk, src_posi_pk, army_worker, army_infantry, army_pikeman, army_scout, army_spearman, army_armed_infantry, army_archer, army_horseman, army_armed_horseman, army_transporter, army_bowman, army_battering_ram, army_catapult, army_adv_catapult FROM troop WHERE dst_posi_pk = $1 AND status = $2 ORDER BY arrival_dt', [$row['dst_posi_pk'], 'C']);
                        $PgGame->fetchAll();
                        $ally_info = $PgGame->rows;

                        //$ret = doApplyDamage('territory_lord', $def_data, $row['dst_posi_pk'], $dst_posi['lord_pk'], null, null, null, $ally_info);
                        $ret = $Battle->doApplyDamage('territory_lord', $def_data, $row['dst_posi_pk'], $dst_posi['lord_pk'], null, $_null, $_null, $ally_info);
                        $injury_army = $ret['injury_army'] ?? false;
                        $army_posi_pk = $row['dst_posi_pk'];
                        $army_position_name = $row['to_position'];

                        // 사망 병사
                        $def_dead_army = $ret['dead_army'] ?? false;

                        // 영웅 부상 처리
                        $injury_heroes = [];

                        /* TODO 개인동맹제거로 일단 주석처리
                         * if ($row['src_lord_pk'] != NPC_TROOP_LORD_PK) {
                            // 동맹 전투 정보 처리
                            $query_params = Array($row['dst_lord_pk']);
                            $PgGame->query('UPDATE alliance_member SET attack_up_dt = now() WHERE memb_lord_pk = $1', $query_params);
                        }*/
                    } else {
                        // 군주자원지

                        // 사병 부상 처리
                        // TODO : 정상적으로 처리되는지 확인 필요
                        //$ret = doApplyDamage('troop', $def_data, $dst_troop['src_posi_pk'], null, $dst_troop['troo_pk'], &$dst_troop, &$def_result);
                        $ret = $Battle->doApplyDamage('troop', $def_data, $dst_troop['src_posi_pk'], null, $dst_troop['troo_pk'], $dst_troop, $def_result);
                        $injury_army = $ret['injury_army'];
                        $army_posi_pk = $dst_troop['src_posi_pk'];
                        $army_position_name = $dst_troop['from_position'];

                        // 사망 병사
                        $def_dead_army = $ret['dead_army'];

                        // 영웅 부상 처리 - 재임명 프로세스
                        $injury_heroes = [];
                    }

                    $abandon_army = $ret['abandon_army'] ?? false;

                    // 부상병 리포트
                    if ($injury_army !== false || $abandon_army !== false) {
                        if (count($injury_army) > 0) {
                            $z_content['outcome_unit']['def']['injury_army'] = true;
                            foreach ($injury_army AS $k => $v) {
                                $z_content['outcome_unit']['def'][$k]['injury'] = $v;
                            }
                        }

                        if ($abandon_army !== false) {
                            $z_content['outcome_unit']['def']['abandon_army'] = true;
                        }

                        $Battle->injuryReport($dst_posi['lord_pk'], $row['dst_posi_pk'], $dst_posi['name'], $army_posi_pk, $army_position_name, $injury_army, $injury_heroes);
                    }
                }

                // 공격측 피해적용 (영웅,병사,자원)
                if ($row['src_lord_pk'] != NPC_TROOP_LORD_PK) {
                    $injury_army = [];
                    $injury_heroes = [];

                    // 사병 부상 처리
                    //$ret = doApplyDamage('troop', $att_data, $row['src_posi_pk'], null, $_troo_pk, &$row, &$att_result);
                    $ret = $Battle->doApplyDamage('troop', $att_data, $row['src_posi_pk'], null, $_troo_pk, $row, $att_result);
                    $injury_army = $ret['injury_army'];

                    // 사망 병사
                    $att_dead_army = $ret['dead_army'];

                    // 영웅 부상 처리 - 재임명 프로세스
                    $injury_heroes = [];

                    $abandon_army = $ret['abandon_army'] ?? false;

                    // 부상병 리포트
                    if (isset($injury_army)) { //  || isset($injury_heroes)
                        if (is_array($injury_army)) {
                            $z_content['outcome_unit']['att']['injury_army'] = true;

                            foreach ($injury_army AS $k => $v) {
                                $z_content['outcome_unit']['att'][$k]['injury'] = $v;
                            }
                        }

                        if ($abandon_army !== false) {
                            $z_content['outcome_unit']['att']['abandon_army'] = true;
                        }

                        $Battle->injuryReport($row['src_lord_pk'], $row['dst_posi_pk'], $dst_posi['name'], $row['src_posi_pk'], $row['from_position'], $injury_army, $injury_heroes);
                    }
                } else {
                    if ($dst_posi['type'] == 'P'){
                        $ret = $Battle->doApplyDamage('point_npc', $def_data, $row['dst_posi_pk']);
                    } else {
                        $ret = $Battle->doApplyDamage('npc', $att_data, $row['src_posi_pk']);
                    }

                    // 사망 병사
                    $att_dead_army = $ret['dead_army'] ?? null;
                }

                // 스킬 포인트 update
                $z_content_battle['battle_info']['exp_info'] = [];

                $incr_point = 0;
                $armyPop = $Troop->getArmyPop($def_dead_army);
                $incr_point = $armyPop['need_population'];

                // 공격측 스킬 경험치
                $hero_arr = [$row['captain_hero_pk'], $row['director_hero_pk'], $row['staff_hero_pk']];
                $attack_exp = $HeroSkill->updateBattleSkillExp($row['src_lord_pk'], $row['src_posi_pk'], $incr_point, $hero_arr);

                $z_content_battle['battle_info']['exp_info']['att'] = $attack_exp;

                // 공격측 공격 포인트
                //if ($row['troop_type'] != 'N')
                if ($dst_posi['relation'] != 'NPC' && $dst_posi['type'] != 'P') {
                    $PgGame->query('UPDATE lord_point SET attack_point = attack_point + $1 WHERE lord_pk = $2', [$incr_point, $row['src_lord_pk']]);

                    // $ret = $Troop->checkEventPoint($row['src_lord_pk'], $row['dst_lord_pk']);
                    // if ($ret) {
                    //     $Troop->setEventPoint('att', $incr_point, $row['src_lord_pk']);
                    // }
                }

                // 요충지 공격 포인트
                $incr_point = 0;
                if ($dst_posi['type'] == 'P' && $dst_posi['lord_pk'] == NPC_TROOP_LORD_PK) {
                    // NPC kill point
                    $incr_point = floor($armyPop['population'] / 100);

                    // bonus point
                    // 1. 레벨에 따른 총 병력수
                    // 2. 남은 병력수
                    // 3. 남은 병력수가 70%되는 시점이면 보너스 점수

                    $troop_info = $Troop->getDstTroopFromNpcPoint($row['dst_posi_pk']);
                    $bonus_point = 0;
                    if ($troop_info['npc_bonus'] == 'N') {
                        $NsGlobal->requireMasterData(['point_npc_troop']);
                        $army_arr = [];
                        foreach ($_M['ARMY_C'] AS $k => $v) {
                            $army_arr[$k] = $_M['POIN_NPC_TROO'][$dst_posi['level']][$troop_info['type']][$k];
                        }
                        $armyPop = $Troop->getArmyPop($army_arr);
                        $defualt_army = $armyPop['population'];

                        $army_arr = [];
                        foreach ($_M['ARMY_C'] AS $k => $v) {
                            $army_arr[$k] = $troop_info['army_'. $k];
                        }

                        $armyPop = $Troop->getArmyPop($army_arr);
                        $now_army = $armyPop['population'];

                        if (($defualt_army * 0.7) >= $now_army) {
                            $bonus_point = 1000;
                            $PgGame->query('UPDATE position_point SET npc_bonus = $1 WHERE posi_pk = $2', ['Y', $row['dst_posi_pk']]);

                            // npc kill bonus log
                            $Log->setPoint($row['src_lord_pk'], $row['src_posi_pk'], 'npc_bonus', $row['dst_posi_pk'], 'Y');
                        }
                    }

                    $prev_point = 0;

                    $PgGame->query('SELECT lord_pk FROM ranking_point WHERE posi_pk = $1 AND lord_pk = $2', [$row['dst_posi_pk'], $row['src_lord_pk']]);
                    if ($PgGame->getNumRows()) {
                        // 이전 포인트
                        $PgGame->query('SELECT occu_point, bonus_point FROM ranking_point WHERE posi_pk = $1 AND lord_pk = $2', [$row['dst_posi_pk'], $row['src_lord_pk']]);
                        $PgGame->fetch();
                        $prev_point = $PgGame->row['occu_point'] + $PgGame->row['bonus_point'];

                        // update
                        $PgGame->query('UPDATE ranking_point SET bonus_point = bonus_point + $1 WHERE posi_pk = $2 AND lord_pk = $3', [$bonus_point, $row['dst_posi_pk'], $row['src_lord_pk']]);
                    } else {
                        // insert
                        $PgGame->query('INSERT INTO ranking_point (posi_pk, lord_pk, bonus_point) VALUES ($1, $2, $3)', [$row['dst_posi_pk'], $row['src_lord_pk'], $bonus_point]);
                    }
                    if ($PgGame->getAffectedRows() != 1) {
                        Debug::debugMessage('ERROR', '요충지 포인트 획득 실패;lord_pk['.$row['src_lord_pk'].'];posi_pk['.$dst_posi['posi_pk'].'];kill_point['.$armyPop['population'].'];');
                    }

                    if ($bonus_point) {
                        // npc kill bonus log
                        $Log->setPoint($row['src_lord_pk'], $row['src_posi_pk'], 'first_attack_bonus', $row['dst_posi_pk'], 'prev:['.$prev_point.'];after:['.$prev_point + $bonus_point .'];');
                    }
                } else if ($dst_posi['type'] == 'S' && $dst_posi['lord_pk'] == NPC_TROOP_LORD_PK) {
                    // 황건적 섬멸전 랭크 기록
                    $raid_point = 0;
                    foreach($def_dead_army AS $k => $v) {
                        $raid_point += $v; // 병력 1명당 1점
                    }

                    // 100점 미만은 기록하지 않음. (마지막 공격 제외)
                    $Troop->setRaidRanking($row['raid_troo_pk'], $row['src_lord_pk'] ,$raid_point, $att_success);
                }

                // 방어측
                $incr_point = 0;
                $armyPop = $Troop->getArmyPop($att_dead_army);
                $incr_point = $armyPop['need_population'];

                // 방어측  스킬 경험치
                $hero_arr = [$dst_troop['captain_hero_pk'] ?? null, $dst_troop['director_hero_pk'] ?? null, $dst_troop['staff_hero_pk'] ?? null];

                $defence_exp = $HeroSkill->updateBattleSkillExp($row['dst_lord_pk'], $row['src_posi_pk'], $incr_point, $hero_arr);

                $z_content_battle['battle_info']['exp_info']['def'] = $defence_exp;

                // 방어측 공격 포인트
                if ($dst_posi['relation'] != 'NPC' && $dst_posi['type'] != 'P') {
                    $PgGame->query('UPDATE lord_point SET defence_point = defence_point + $1 WHERE lord_pk = $2', [$incr_point, $row['dst_lord_pk']]);

                    // $ret = $Troop->checkEventPoint($row['src_lord_pk'], $row['dst_lord_pk']);
                    // if ($ret)
                    // {
                    //     $Troop->setEventPoint('def', $incr_point, $row['dst_lord_pk']);
                    // }
                }

                // 방어측 전투결과 적용 - 방어측은 군주의 자원지일 경우만 적용하고 나머지는 공격측에서 처리
                if ($dst_posi['relation'] != 'NPC' && $dst_posi['type'] != 'T') {
                    // 방어성공
                    if ($def_success) {
                        // 주장부재
                        if (!$def_remain_captain) {
                            if ($dst_posi['type'] == 'P') {
                                $Troop->lossOwnershipPoint($dst_troop['src_lord_pk'], $dst_troop['dst_posi_pk']);
                                $Troop->setNpcPoint($dst_troop['dst_posi_pk']);
                            } else {
                                // 점령지 상실
                                $Troop->lossOwnershipValley($dst_troop['src_lord_pk'], $dst_troop['dst_posi_pk']);
                            }

                            // 회군
                            $Troop->setStatusRecall($dst_troop['troo_pk']);

                            $z_content['outcome']['lossownership'] = true;
                        } // else 주장잔존 - 처리사항 無
                    } else {
                        // 방어실패
                        if ($dst_posi['type'] == 'P') {
                            $Troop->lossOwnershipPoint($dst_troop['src_lord_pk'], $dst_troop['dst_posi_pk']);
                        } else {
                            if ($dst_troop['src_lord_pk'] && $dst_troop['dst_posi_pk']) {
                                // 점령지 상실
                                $Troop->lossOwnershipValley($dst_troop['src_lord_pk'], $dst_troop['dst_posi_pk']);
                            } else if ($dst_troop['valley_posi_pk']) {
                                // 부대 정보가 존재하지 않으면
                                $PgGame->query('SELECT lord_pk FROM position WHERE posi_pk = $1', [$dst_troop['valley_posi_pk']]);
                                $valley_lord_pk = $PgGame->fetchOne();

                                $Troop->lossOwnershipValley($valley_lord_pk, $dst_troop['valley_posi_pk']);
                            }
                        }

                        $z_content['outcome']['lossownership'] = true;

                        // 주장잔존
                        if ($def_remain_captain) {
                            // 회군
                            $Troop->setStatusRecall($dst_troop['troo_pk']);
                        } else {
                            // 방어측 패배 기준이 전멸이기 때문에 주장부재시 부대삭제
                            $Troop->removeTroop($dst_troop['troo_pk']);
                        }
                    }
                }

                // 공격측 전투결과 적용

                // 영웅이 부상 상태인지 확인하고 부상상태라면 회군 부대에서 제외하기 - TODO 영웅부상 사용 안하지 않나? 20230707 송누리
                //$Troop->checkInjuryHero($_troo_pk, &$row);
                //$Troop->checkInjuryHero($dst_troop['troo_pk'], &$dst_troop);
                // $Troop->checkInjuryHero($_troo_pk, $row);
                // $Troop->checkInjuryHero($dst_troop['troo_pk'], $dst_troop);

                // 공격성공
                $occupation = false;
                if ($att_success) {
                    // 황건적 습격부대
                    if ($row['troop_type'] == 'N') {
                        // 습격 방어 실패 처리를 위해 추가
                        $PgGame->query('SELECT npc_troo_pk, m_ques_pk, buff_pk, yn_quest_reward FROM my_event_npc_troop WHERE lord_pk = $1 AND npc_troo_pk = $2',  [$Session->lord['lord_pk'], $_troo_pk]);
                        $PgGame->fetch();
                        $event_npc_troop = $PgGame->row;
                        if ($event_npc_troop['npc_troo_pk']) {
                            $PgGame->query('DELETE FROM my_event_npc_troop WHERE lord_pk = $1 AND npc_troo_pk = $2',  [$Session->lord['lord_pk'], $event_npc_troop['npc_troo_pk']]);

                            if ($event_npc_troop['yn_quest_reward'] == 'Y') {
                                $Quest->conditionCheckQuest($row['dst_lord_pk'], ['quest_type' => 'daily_dispatch', 'm_ques_pk' => $event_npc_troop['m_ques_pk']]);
                                $PgGame->query('UPDATE my_event SET event_att_point = 0 WHERE lord_pk = $1',  [$Session->lord['lord_pk']]);
                            }
                        }

                        // 부대삭제
                        $Troop->removeTroop($_troo_pk);
                    } else if ($dst_posi['relation'] != 'NPC' && $dst_posi['type'] == 'T') {
                        // 군주영지

                        // 민심 내리기
                        $PgGame->query('SELECT loyalty, date_part(\'epoch\', last_plunder_dt)::integer as last_plunder_dt FROM territory WHERE posi_pk = $1', [$row['dst_posi_pk']]);
                        if ($PgGame->fetch()) {
                            $r = $PgGame->row;

                            // 민심 내리기
                            // 기준 수치
                            $decrease_loyalty = $r['loyalty']*0.05;

                            // 대상지 "민심" 영웅배속 기술효과 (행정부) - 감소
                            /*if (false) {
                                $decrease_loyalty *= 0.5;
                            }*/

                            // 황제의 조서 - 증가
                            if ($row['use_item_pk']) {
                                $decrease_loyalty *= 1.5;
                                $z_content['outcome']['use_item'] = true;
                            }

                            // 공격빈도 (20분 이후면) - 증가
                            if (time() -$r['last_plunder_dt'] > 1200) {
                                $decrease_loyalty *= 1.5;
                            }

                            // 적용될 민심 계산
                            $decrease_loyalty = ceil($decrease_loyalty);
                            if ($decrease_loyalty < 1) {
                                $decrease_loyalty = 1;
                            }

                            // 민심 감소 없음 스킬
                            $Effect->initEffects();
                            $ret = $Effect->getEffectedValue($row['dst_posi_pk'], ['none_loyalty_increase'], 1);
                            if (isset($ret['effected_values']['hero_skill'])) {
                                $rand = rand(1,100);
                                //$rand = 1;
                                if ($rand < $ret['effected_values']['hero_skill']) {
                                    $decrease_loyalty = 0;

                                    // 스킬 발동됨
                                    $PgGame->query('SELECT assign_hero_pk FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$row['dst_posi_pk'], PK_BUILDING_ADMINISTRATION]);
                                    $assign_hero_pk = $PgGame->fetchOne();

                                    $z_content['outcome']['hero_skill_special']['loyalty'] = true;;
                                    $Log->setHeroSkillActive($row['dst_lord_pk'], $row['dst_posi_pk'], $assign_hero_pk, 'decrease_loyalty', $rand, $ret['effected_values']['hero_skill'], $_troo_pk);
                                }
                            }

                            $loyalty = $r['loyalty']-$decrease_loyalty;

                            // 점령가능 여부 검사
                            $occupation = false;
                            // 민심0
                            if ($loyalty <= 0) {
                                $loyalty = 1;

                                // 황제의조서
                                if (isset($row['use_item_pk'])) {
                                    // 점령선포시간 확인
                                    /* $query_params = Array($row['src_posi_pk'], $row['dst_posi_pk']);
                                    $PgGame->query('SELECT regist_dt::abstime::integer FROM occupation_inform WHERE att_posi_pk = $1 AND def_posi_pk = $2', $query_params);
                                    $regist_dt = $PgGame->fetchOne();
                                    if ($regist_dt && $regist_dt + OCCUPATION_INFORM_READY < mktime()) */
                                    {
                                        // 주장잔존
                                        if ($att_remain_captain) {
                                            // 상대방 등급 및 남은 영지 개수
                                            if ($dst_posi['lord_level'] >= 3 && $dst_posi['lord_position_cnt'] <= 1) {
                                                $occupation = false;
                                            } else {
                                                // 확장영지슬롯남음 - TODO 확장 영지슬롯이 없어질 에정이므로 차후 처리 필요.
                                                if ($Session->lord['position_cnt'] < $_M['LORD_GRADE_TERRITORY_COUNT'][$Session->lord['level']]) {
                                                    $PgGame->query('SELECT count(lord_pk) FROM position WHERE lord_pk = $1 AND type = $2', [$row['src_lord_pk'], 'T']);
                                                    $position_cnt = $PgGame->fetchOne();
                                                    if ($position_cnt < $_M['LORD_GRADE_TERRITORY_COUNT'][$Session->lord['level']]) {
                                                        $loyalty = 0;
                                                        $occupation = true;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // 타군주 영지점령 - TODO 확장영지 슬롯이 없어질 예정이므로 차후 처리 필요.
                            if ($occupation) {
                                $z_content['outcome']['occupation'] = true;

                                // sqappen 사용안함
                                global $_NS_SQ_REFRESH_FLAG;
                                $_NS_SQ_REFRESH_FLAG = true;

                                // 점령선포 삭제
                                //$Territory->cancelOccupationInform($row['dst_posi_pk'], $row['dst_lord_pk']);

                                // 영지 액션 cancellation
                                $Territory->cancellation($row['dst_posi_pk'], $row['dst_lord_pk']);

                                // dst_lord_pk 점령상실 처리
                                $Territory->lossOwnershipLordTerritory($row['dst_lord_pk'], $row['dst_posi_pk'], $row['src_lord_pk']);

                                $_NS_SQ_REFRESH_FLAG = false;

                                // src_lord_pk 의 확장영지로 할당
                                // 확장영지 할당
                                $Territory->occupationLordTerritory($row);

                                // 자원 append
                                $z_arr = ['food' => $row['reso_food'], 'horse' => $row['reso_horse'], 'lumber' => $row['reso_lumber'], 'iron' => $row['reso_iron']];
                                $Territory->appendResourceTerritory($row['src_lord_pk'], $z_arr, $row['dst_posi_pk'], $row['reso_gold']);

                                // 병력 append
                                $Territory->appendArmyTerritory($row['dst_posi_pk'], $att_data);

                                // PUSH(카드덱 다시 그리기)
                                $Session->sqAppend('PUSH', ['MULTI' => true], null, $row['src_lord_pk']);
                                $Session->sqAppend('PUSH', ['MULTI' => true], null, $row['dst_lord_pk']);

                                // 부대삭제
                                $Troop->removeTroop($_troo_pk);
                            } else {
                                $z_content['outcome']['plunder'] = true;

                                // 환산병력 기준 50% 이상 유실시 아이템도 유실
                                // if ($row['use_item_pk'] && $att_result['halfdamage'])
                                if (isset($row['use_item_pk']) && $row['use_item_pk'] == 500084) {
                                    // 무조건 유실됨
                                    $z_content['outcome']['loss_item'] = true;
                                    $PgGame->query('UPDATE troop SET use_item_pk = NULL WHERE troo_pk = $1', [$_troo_pk]);
                                }

                                $bind_str_attack = null;
                                $plunder_gold = false;
                                $plunder_reso = false;

                                // 보유자원 (황금 포함)
                                $dst_reso = $dst_troop['reso_gold']+$dst_troop['reso_food']+$dst_troop['reso_horse']+$dst_troop['reso_lumber']+$dst_troop['reso_iron'];

                                // 약탈 보호량
                                $protect_pct = null;
                                if ($att_result['capacity'] > 0 && $dst_reso > 0) {
                                    //$ret = $Effect->getEffectedValue($row['dst_posi_pk'], Array('storage_protect'), 1);
                                    //$protect_pct = $ret['effected_values']['hero_assign']+$ret['effected_values']['hero_skill']+$ret['effected_values']['item']+$ret['effected_values']['tech'];

                                    $PgGame->query('SELECT hero_pk, m_hero_assi_pk FROM territory_hero_assign WHERE posi_pk = $1', [$row['dst_posi_pk']]);
                                    $PgGame->fetchAll();
                                    $m_hero_assi_pk = $PgGame->rows;
                                    foreach ($m_hero_assi_pk AS $k => $v) {
                                        foreach ($_M['EFFE']['storage_protect'] AS $k1 => $v1) {
                                            if ($v['m_hero_assi_pk'] == $k1) {
                                                // 해당 창고에 영웅 기술이 있는지 확인
                                                $protect_pct += $v1['e_v'];
                                            }
                                        }
                                    }

                                    if ($protect_pct !== null) {
                                        // 창고 수 구하기
                                        $PgGame->query('SELECT COUNT(*) FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$row['dst_posi_pk'], PK_BUILDING_STORAGE]);
                                        $storage_cnt = $PgGame->fetchOne();

                                        if (!$storage_cnt) {
                                            Debug::debugMessage('ERROR', '약탈 보호량 계산 버그로 일단, 약탈 못하게 함.');
                                            $protect_pct = 100;
                                        } else {
                                            $protect_pct = round($protect_pct / $storage_cnt, 2);
                                        }

                                        // 약탈 방지 스킬
                                        $ret = $Effect->getEffectedValue($row['dst_posi_pk'], ['none_plunder_increase'], 1);
                                        if ($ret['effected_values']['hero_skill']) {
                                            $rand = rand(1,10000);
                                            if ($rand < ceil($ret['effected_values']['hero_skill'] / $storage_cnt * 100)) {
                                                $protect_pct = 100;
                                                $z_content['outcome']['hero_skill_special']['plunder'] = true;;
                                                // 스킬 발동됨
                                                $Log->setHeroSkillActive($row['dst_lord_pk'], $row['dst_posi_pk'], null, 'protect', $rand, ceil($ret['effected_values']['hero_skill'] / $storage_cnt * 100), $_troo_pk);
                                            }
                                        }

                                        if ($protect_pct >= 100) {
                                            $dst_reso = 0;
                                        } else if ($protect_pct > 0) {
                                            $protect_max = intval($dst_troop['storage_max']*$protect_pct*0.01);
                                            $dst_reso = $dst_reso - $protect_max;
                                            if ($dst_reso < 0) {
                                                $dst_reso = 0;
                                            }
                                        }
                                    }
                                }
                                // 약탈
                                if ($att_result['capacity'] > 0 && $dst_reso > 0) {
                                    if ($att_result['capacity'] > $dst_reso) {
                                        $remain = $dst_reso;
                                    } else {
                                        $remain = $att_result['capacity'];
                                    }

                                    $plunder_order = ['gold', 'iron', 'lumber', 'horse', 'food'];
                                    $plunder_source = ['gold' => $dst_troop['reso_gold'], 'iron' => $dst_troop['reso_iron'], 'lumber' => $dst_troop['reso_lumber'], 'horse' => $dst_troop['reso_horse'], 'food' => $dst_troop['reso_food']];
                                    $plunder_value = ['gold' => 0, 'iron' => 0, 'lumber' => 0, 'horse' => 0, 'food' => 0];
                                    $plunder_target = ['gold' => true, 'iron' => true, 'lumber' => true, 'horse' => true, 'food' => true];
                                    $plunder_target_cnt = 5;

                                    for ($i = 0; $i < 10 && $plunder_target_cnt > 0 && $remain > 0; $i++) {
                                        // 할당
                                        $unit = $remain/$plunder_target_cnt;
                                        if ($unit < 1) {
                                            break;
                                        }
                                        $unit = intval($unit);

                                        $remain = 0;

                                        foreach ($plunder_target AS $k => $v) {
                                            if (!$v) {
                                                continue;
                                            }

                                            // 약탈
                                            $this_value = intval($plunder_source[$k]);
                                            if ($unit >= $this_value) {
                                                $plunder_value[$k] += $this_value;
                                                $plunder_target[$k] = false;
                                                $plunder_target_cnt--;

                                                $remain += $unit-$this_value;
                                                $plunder_source[$k] -= $this_value;
                                            } else {
                                                $plunder_value[$k] += $unit;
                                                $plunder_source[$k] -= $unit;
                                            }
                                        }
                                    }

                                    foreach ($plunder_value AS $k => $v) {
                                        if ($v == 0) {
                                            continue;
                                        }

                                        // 자원 약탈 체크
                                        if ($k != 'gold') {
                                            //echo 'set plunder_reso';
                                            $plunder_reso = true;
                                        }

                                        //$bindStr_defence .= sprintf(', reso_%s = reso_%s - %d', $k, $k, $v);
                                        $bind_str_attack .= sprintf(', reso_%s = reso_%s + %d', $k, $k, $v);
                                    }

                                    $plunder_gold = $plunder_value['gold'];
                                } else {
                                    $plunder_value = ['gold' => 0, 'iron' => 0, 'lumber' => 0, 'horse' => 0, 'food' => 0];
                                }

                                $z_content['plunder']['own'] = ['gold' => $dst_troop['reso_gold'], 'food' => $dst_troop['reso_food'], 'horse' => $dst_troop['reso_horse'], 'lumber' => $dst_troop['reso_lumber'], 'iron' => $dst_troop['reso_iron']];
                                $z_content['plunder']['get'] = $plunder_value;

                                // 민심 decrease
                                $query_params = [$loyalty, $row['dst_posi_pk']];
                                $PgGame->query('UPDATE territory SET loyalty = $1, last_plunder_dt = now() WHERE posi_pk = $2', $query_params);

                                // 방어측 민심 noti
                                $Session->sqAppend('TERR', ['loyalty' => $loyalty], null, $dst_posi['lord_pk'], $row['dst_posi_pk']);

                                // 약탈감소 처리 (금)
                                if ($plunder_gold) {
                                    // 방어측 - 감소
                                    $GoldPop->decreaseGold($row['dst_posi_pk'], $plunder_gold, $dst_posi['lord_pk'], 'troop_plunder');
                                } else {
                                    // 금 약탈이 없어 충성도 변화로 인한 현재 인구 save를 별도로 진행
                                    $GoldPop->save($row['dst_posi_pk'], $dst_posi['lord_pk']);
                                }

                                // 약탈감소 처리 (자원)
                                if ($plunder_reso) {
                                    // 방어측 - 감소
                                    $Resource->decrease($row['dst_posi_pk'], $plunder_value, $dst_posi['lord_pk'], 'troop_plunder');
                                }

                                // 약탈증가 처리 (금,자원 모두)
                                if ($bind_str_attack) {
                                    $PgGame->query('UPDATE troop SET regist_dt = regist_dt'. $bind_str_attack. ' WHERE troo_pk = $1', [$_troo_pk]);
                                }

                                $Quest->countCheckQuest($row['src_lord_pk'], 'EVENT_LORD_PLUNDER', ['value' => 1]);

                                // 회군
                                $Troop->setStatusRecall($_troo_pk);

                                $z_content['plunder']['loyalty_decrease'] = $decrease_loyalty;
                                $z_content['plunder']['loyalty_final'] = $loyalty;
                            }
                        }
                    }
                    else if ($dst_posi['relation'] == 'NPC' && $dst_posi['type'] == 'N')
                    {
                        // NPC 영지

                        // 민심 내리기

                        $PgGame->query('SELECT loyalty, date_part(\'epoch\', last_plunder_dt)::integer as last_plunder_dt, date_part(\'epoch\', last_reward_dt)::integer as last_reward_dt FROM position_npc WHERE posi_pk = $1', Array($row['dst_posi_pk']));
                        if ($PgGame->fetch()) {
                            $r = $PgGame->row;

                            // 민심 내리기
                            {
                                // 기준 수치
                                $decrease_loyalty = $r['loyalty']*0.05;

                                // 대상지 "민심" 영웅배속 기술효과 (행정부) - 감소
                                if (false) {
                                    $decrease_loyalty *= 0.5;
                                }

                                // 황제의 조서 - 증가
                                if (isset($row['use_item_pk'])) {
                                    $decrease_loyalty *= 1.5;
                                    $z_content['outcome']['use_item'] = true;
                                }

                                // 공격빈도 (20분 이후면) - 증가
                                if (time()-$r['last_plunder_dt'] > 1200) {
                                    $decrease_loyalty *= 1.5;
                                }

                                // 적용될 민심 계산
                                $decrease_loyalty = ceil($decrease_loyalty);

                                if ($decrease_loyalty < 1)
                                    $decrease_loyalty = 1;

                                $loyalty = $r['loyalty']-$decrease_loyalty;

                                // 점령가능 여부 검사
                                $occupation = false;
                                // 민심0
                                if ($loyalty <= 0) {
                                    //echo "loyalty is zero";
                                    $loyalty = 1;

                                    // 황제의조서
                                    if (isset($row['use_item_pk'])) {
                                        //echo "king paper";
                                        // 주장잔존
                                        if ($att_remain_captain) {
                                            //echo "remain captain";
                                            // 확장영지슬롯남음
                                            if ($Session->lord['position_cnt'] < $_M['LORD_GRADE_TERRITORY_COUNT'][$Session->lord['level']]) {
                                                $PgGame->query('SELECT count(lord_pk) FROM position WHERE lord_pk = $1 AND type = $2', [$row['src_lord_pk'], 'T']);
                                                $position_cnt = $PgGame->fetchOne();
                                                if ($position_cnt < $_M['LORD_GRADE_TERRITORY_COUNT'][$Session->lord['level']]) {
                                                    //echo "remain slot";
                                                    $loyalty = 0;
                                                    $occupation = true; // TODO
                                                }
                                            }
                                        }
                                    }
                                }
                            } // empty wrapper

                            // 퀘스트
                            $Quest->conditionCheckQuest($row['src_lord_pk'], ['quest_type' => 'battle', 'type' => 'attack_territory_npc', 'level' => $dst_posi['level']]);

                            //2012.01 이벤트 퀘스트 (전투신)
                            $Quest->conditionCheckQuest($row['src_lord_pk'], ['quest_type' => 'battle', 'type' => 'attack_territory_npc_event']);

                            // 점령
                            if ($occupation) {
                                //echo "occupation";
                                $z_content['outcome']['occupation'] = true;

                                //$PgGame->query('SELECT level_default FROM position WHERE posi_pk = $1', Array($row['dst_posi_pk']));
                                $PgGame->query('SELECT c.level FROM position a, m_position b, m_position_area c WHERE a.posi_pk = $1 AND a.posi_pk = b.m_posi_pk AND b.m_posi_area_pk = c.m_posi_area_pk', [$row['dst_posi_pk']]);
                                $level_default = $PgGame->fetchOne();

                                $PgGame->query('SELECT flag FROM territory WHERE posi_pk = $1', [$row['src_posi_pk']]);
                                $flag = $PgGame->fetchOne();

                                // 정해진 영지 건설
                                $Territory->occupationNpcTerritory($row['src_lord_pk'], $row['dst_posi_pk'], $level_default, $flag);

                                // 성주 할당
                                $Territory->setAssignedLord($row);

                                // 자원
                                $z_arr = ['food' => $row['reso_food'], 'horse' => $row['reso_horse'], 'lumber' => $row['reso_lumber'], 'iron' => $row['reso_iron']];
                                $Territory->appendResourceTerritory($row['src_lord_pk'], $z_arr, $row['dst_posi_pk'], $row['reso_gold']);

                                // 황제의조서 아이템은 사용됨.
                                //  - 부대가 삭제되기 때문에 별도 처리 안함.

                                // 병력
                                $Territory->appendArmyTerritory($row['dst_posi_pk'], $att_data);

                                // 보상 (점령)
                                $ret = $Troop->setBattleReward('occupation', $dst_posi['level'], $row['src_lord_pk']);
                                if (isset($ret['item_pk'])) {
                                    $z_content['outcome']['reward'] = $ret;
                                    if ($_M['ITEM'][$ret['item_pk']]['notice_common'] == 'Y') {
                                        /*global $Chat;
                                        if (!$Chat)
                                        {
                                            require_once_classes(Array('CChat'));
                                            $Chat = new CChat();
                                        }

                                        $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', Array($row['src_lord_pk']));
                                        $lord_name = $PgGame->fetchOne();

                                        $Chat->send_announce_system_about_item($lord_name."님이 황건적성을 공략해 전리품으로  ".$_M['ITEM'][$ret['item_pk']]['title']." 아이템을 획득하였습니다.");*/
                                    }

                                    // 황건적 성 공격 성공하고 아이템이 발견되면 섬멸전 체크
                                    // $Troop->setRaidTroop(RAID_NPC_RATE, $row['src_lord_pk'], 1, 'territory_npc');
                                }

                                // PUSH
                                $Session->sqAppend('PUSH', ['MULTI' => true]);

                                // 부대삭제
                                $Troop->removeTroop($_troo_pk);
                            } else {
                                //echo "plunder";
                                $z_content['outcome']['plunder'] = true;

                                // 환산병력 기준 50% 이상 유실시 아이템도 유실
                                //if ($row['use_item_pk'] && $att_result['halfdamage'])
                                if (isset($row['use_item_pk']) && $row['use_item_pk'] == 500084) {
                                    // 무조건 유실
                                    $z_content['outcome']['loss_item'] = true;
                                    $PgGame->query('UPDATE troop SET use_item_pk = NULL WHERE troo_pk = $1', [$_troo_pk]);
                                }

                                $bindStr_defence = '';
                                $bind_str_attack = '';

                                // 약탈
                                if ($att_result['capacity'] > 0 && $dst_troop['reso_gold']+$dst_troop['reso_food']+$dst_troop['reso_horse']+$dst_troop['reso_lumber']+$dst_troop['reso_iron'] > 0) {
                                    $remain = $att_result['capacity'];

                                    $plunder_order = ['gold', 'iron', 'lumber', 'horse', 'food'];
                                    $plunder_source = ['gold' => $dst_troop['reso_gold'], 'iron' => $dst_troop['reso_iron'], 'lumber' => $dst_troop['reso_lumber'], 'horse' => $dst_troop['reso_horse'], 'food' => $dst_troop['reso_food']];
                                    $plunder_value = ['gold' => 0, 'iron' => 0, 'lumber' => 0, 'horse' => 0, 'food' => 0];
                                    $plunder_target = ['gold' => true, 'iron' => true, 'lumber' => true, 'horse' => true, 'food' => true];
                                    $plunder_target_cnt = 5;

                                    for ($i = 0; $i < 10 && $plunder_target_cnt > 0 && $remain > 0; $i++) {
                                        // 할당
                                        $unit = $remain/$plunder_target_cnt;
                                        if ($unit < 1) {
                                            break;
                                        }
                                        $unit = intval($unit);

                                        $remain = 0;

                                        foreach ($plunder_target AS $k => $v) {
                                            if (!$v) {
                                                continue;
                                            }

                                            // 약탈
                                            $this_value = intval($plunder_source[$k]);

                                            if ($unit >= $this_value) {
                                                $plunder_value[$k] += $this_value;
                                                $plunder_target[$k] = false;
                                                $plunder_target_cnt--;

                                                $remain += $unit-$this_value;
                                                $plunder_source[$k] -= $this_value;
                                            } else {
                                                $plunder_value[$k] += $unit;
                                                $plunder_source[$k] -= $unit;
                                            }
                                        }
                                    }

                                    foreach ($plunder_value AS $k => $v) {
                                        if ($v == 0) {
                                            continue;
                                        }

                                        // 자원 약탈 체크
                                        if ($k != 'gold') {
                                            //echo 'set plunder_reso';
                                            $plunder_reso = true;
                                        }

                                        $bindStr_defence .= sprintf(', reso_%s = reso_%s - %d', $k, $k, $v);
                                        $bind_str_attack .= sprintf(', reso_%s = reso_%s + %d', $k, $k, $v);
                                    }

                                    $plunder_gold = $plunder_value['gold'];
                                } else {
                                    $plunder_value = ['gold' => 0, 'iron' => 0, 'lumber' => 0, 'horse' => 0, 'food' => 0];
                                }

                                $z_content['plunder']['own'] = ['gold' => $dst_troop['reso_gold'], 'food' => $dst_troop['reso_food'], 'horse' => $dst_troop['reso_horse'], 'lumber' => $dst_troop['reso_lumber'], 'iron' => $dst_troop['reso_iron']];
                                $z_content['plunder']['get'] = $plunder_value;

                                // 민심 decrease (약탈감소 포함처리)
                                $query_params = [$loyalty, $row['dst_posi_pk']];
                                $PgGame->query('UPDATE position_npc SET loyalty = $1, last_plunder_dt = now()'. $bindStr_defence. ' WHERE posi_pk = $2', $query_params);

                                // 약탈증가 처리
                                if ($bind_str_attack !== null) {
                                    $PgGame->query('UPDATE troop SET regist_dt = regist_dt'. $bind_str_attack. ' WHERE troo_pk = $1', [$_troo_pk]);
                                }

                                $Quest->countCheckQuest($row['src_lord_pk'], 'EVENT_NPC_PLUNDER', ['value' => 1]);

                                // 회군
                                $Troop->setStatusRecall($_troo_pk);

                                $z_content['plunder']['loyalty_decrease'] = $decrease_loyalty;
                                $z_content['plunder']['loyalty_final'] = $loyalty;

                                // 보상 (약탈)
                                if ($r['last_reward_dt']+PLUNDER_REWARD_PERIOD < time()) {
                                    $ret = $Troop->setBattleReward('plunder', $dst_posi['level'], $row['src_lord_pk']);
                                    if ($ret['item_pk']) {
                                        $z_content['outcome']['reward'] = $ret;

                                        // 황건적 성 공격 성공하고 아이템이 발견되면 섬멸전 체크
                                        // $Troop->setRaidTroop(RAID_NPC_RATE, $row['src_lord_pk'], 1, 'territory_npc');

                                        $PgGame->query('UPDATE position_npc SET last_reward_dt = now() WHERE posi_pk = $1', [$row['dst_posi_pk']]);
                                    }
                                }
                            }
                        }
                    } else if ($dst_posi['relation'] == 'NPC' && $dst_posi['type'] == 'D') {
                        // 이벤트 인지 확인
                        // $query_params = Array($row['dst_posi_pk'], 'N', $row['src_lord_pk']);
                        // $PgGame->query('SELECT t1.supp_pk FROM suppress_position t1, my_event t2 WHERE t2.lord_pk = $3 AND t1.supp_pk = t2.event_supp_pk AND t1.posi_pk = $1 AND t2.event_supp_success = $2', $query_params);
                        // $supp_pk = $PgGame->fetchOne();

                        // if ($supp_pk)
                        // {
                        //     // 이벤트 토벌령
                        //     $reward_arr = $Troop->doNpcSuppressEvent($row['src_lord_pk'], $row['dst_posi_pk']);
                        // } else {
                        //     // 토벌령
                        //     $reward_arr = $Troop->doNpcSuppress($row['src_lord_pk'], $row['dst_posi_pk']);
                        // }
                        $reward_arr = $Troop->doNpcSuppress($row['src_lord_pk'], $row['dst_posi_pk']);

                        if (isset($reward_arr['item_pk'])) {
                            $z_content['outcome']['reward'] = $reward_arr;
                            // if ($_M['ITEM'][$reward_arr['item_pk']]['notice_common'] == 'Y') {
                            //     global $Chat;
                            //     if (!$Chat)
                            //     {
                            //         require_once_classes(Array('CChat'));
                            //         $Chat = new CChat();
                            //     }
                            //     $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', Array($row['src_lord_pk']));
                            //     $lord_name = $PgGame->fetchOne();
                            //     $Chat->send_announce_system_about_item($lord_name."님이 토벌령을 완수해 보상으로 ".$_M['ITEM'][$reward_arr['item_pk']]['title']." 아이템을 획득하였습니다.");
                            // }
                        }

                        // 회군
                        $Troop->setStatusRecall($_troo_pk);
                    } else if ($dst_posi['type'] == 'P') {
                        // 요충지
                        // 주장잔존
                        if ($att_remain_captain) {
                            // 신규점령
                            $ret = $Troop->acquiredOwnershipPoint($row['src_lord_pk'], $row['dst_posi_pk'], $row['dst_lord_pk'], $row['src_posi_pk']);
                            if (isset($ret['ret'])) {
                                // 주둔
                                $Troop->setStatusCampValley($_troo_pk, $row);
                                $z_content['outcome']['acquiredpwnership'] = true;
                            } else {
                                $z_content['outcome']['acquiredpwnership'] = false;
                                // 회군
                                $Troop->setStatusRecall($_troo_pk);
                            }
                        } else {
                            // 회군 처리
                            $z_content['outcome']['acquiredpwnership'] = false;
                            $Troop->setStatusRecall($_troo_pk);

                            // 유저가 점령하고 있을 경우 해당
                            if ($dst_troop['src_lord_pk'] > 1) {
                                // NPC 생성
                                $Troop->setNpcPoint($dst_troop['dst_posi_pk']);
                            }
                        }
                    } else if ($dst_posi['type'] == 'S') { // 섬멸전
                        // 주장잔존
                        $z_content['outcome']['acquiredpwnership'] = false;
                        // 회군
                        $Troop->setStatusRecall($_troo_pk);
                        // 공격성공에 따른 황건적 요새 처리
                        $Troop->clearRaidTroop($dst_troop['raid_troo_pk'], $row['src_lord_pk']);
                        // 섬멸전 전용 버프 삭제
                        $Item->delItemBuff($row['src_lord_pk'], $row['src_posi_pk'], $buff_item_pk);
                        // 사용된 아이템이 있을 경우 버프 삭제
                        if ($row['use_item_pk'])
                            $Item->delItemBuff($row['src_lord_pk'], $row['src_posi_pk'], $row['use_item_pk']);
                    } else {// 자원지
                        // 주장잔존
                        if ($att_remain_captain) {
                            // 신규점령
                            $ret = $Troop->acquiredOwnershipValley($row['src_lord_pk'], $row['src_posi_pk'], $row['dst_posi_pk'], $dst_posi['lord_pk']);
                            if ($ret['ret']) {
                                // 주둔
                                $Troop->setStatusCampValley($_troo_pk, $row);
                                $z_content['outcome']['acquiredpwnership'] = true;
                                $occupation = true;

                                $Quest->countCheckQuest($row['src_lord_pk'], 'EVENT_OCCUPATION', ['value' => 1]);
                            } else {
                                $z_content['outcome']['acquiredpwnership'] = false;
                                if (isset($ret['valley_cnt_not'])) {
                                    $z_content['outcome']['valley_cnt_not'] = true;
                                }
                                // 회군
                                $Troop->setStatusRecall($_troo_pk);
                            }
                        } else {
                            // 회군 처리
                            $z_content['outcome']['acquiredpwnership'] = false;
                            $Troop->setStatusRecall($_troo_pk);
                        }

                        // 회군(외부 자원지에선 무조건 다 회군)
                        // $Troop->setStatusRecall($_troo_pk);
                    }
                } else {
                    // 공격실패
                    // 영웅 충성도 5감소
                    if (isset($row['captain_hero_pk'])) {
                        $Hero->setMyHeroLoyalty($row['src_lord_pk'], $row['src_posi_pk'], $row['captain_hero_pk'], 5);
                    }
                    if (isset($row['director_hero_pk'])) {
                        $Hero->setMyHeroLoyalty($row['src_lord_pk'], $row['src_posi_pk'], $row['director_hero_pk'], 5);
                    }
                    if (isset($row['staff_hero_pk'])) {
                        $Hero->setMyHeroLoyalty($row['src_lord_pk'], $row['src_posi_pk'], $row['staff_hero_pk'], 5);
                    }


                    // 병과 또는 영웅 잔존 체크
                    if ($row['troop_type'] == 'U' && ($att_remain_army || $att_remain_captain)) {
                        // 환산병력 기준 50% 이상 유실시 아이템도 유실
                        //if ($row['use_item_pk'] && $att_result['halfdamage'])
                        if (isset($row['use_item_pk']) && $row['use_item_pk'] == 500084) {
                            // 무조건 유실
                            $z_content['outcome']['loss_item'] = true;
                            $PgGame->query('UPDATE troop SET use_item_pk = NULL WHERE troo_pk = $1', [$_troo_pk]);
                        }

                        // 회군
                        $Troop->setStatusRecall($_troo_pk);
                    } else if ($row['troop_type'] == 'N') { // 습격
                        // 방어에 성공했으므로 포인트 1점 추가
                        //$PgGame->query('UPDATE my_event SET event_att_point = event_att_point + 1 WHERE lord_pk = $1',Array($Session->lord['lord_pk']));

                        // npc_troo_pk 존재한다면 이벤트 습격부대 이므로 이벤트 처리 진행
                        $PgGame->query('SELECT npc_troo_pk, m_ques_pk, buff_pk, yn_quest_reward FROM my_event_npc_troop WHERE lord_pk = $1 AND npc_troo_pk = $2',  [$Session->lord['lord_pk'], $_troo_pk]);
                        $PgGame->fetch();
                        $event_npc_troop = $PgGame->row;

                        if (isset($event_npc_troop['npc_troo_pk'])) {
                            // 아이템 보상은 없음
                            // $ret = $Troop->setBattleReward('dispatch', $row['troop_quest_npc_attack'], $row['dst_lord_pk']);
                            // if ($ret['item_pk']) {
                            //     $z_content['outcome']['reward'] = $ret;
                            // }


                            $PgGame->query('DELETE FROM my_event_npc_troop WHERE lord_pk = $1 AND npc_troo_pk = $2',  [$Session->lord['lord_pk'], $event_npc_troop['npc_troo_pk']]);

                            // 포인트 받아오기
                            $PgGame->query('SELECT event_att_point FROM my_event WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
                            $att_point = $PgGame->fetchOne();

                            $buff_check = false;
                            if ($event_npc_troop['m_ques_pk'] == 608002) {
                                if ($att_point == 1) {
                                    $buff_check = true;
                                }
                            } else if ($event_npc_troop['m_ques_pk'] == 608003) {
                                if ($att_point == 2) {
                                    $buff_check = true;
                                }
                            } else if ($event_npc_troop['m_ques_pk'] == 608004) {
                                if ($att_point == 3) {
                                    $buff_check = true;
                                }
                            }

                            // 버프 보상
                            if ($event_npc_troop['yn_quest_reward'] == 'Y') {
                                $Quest->conditionCheckQuest($row['dst_lord_pk'], ['quest_type' => 'daily_dispatch', 'm_ques_pk' => $event_npc_troop['m_ques_pk']]);

                                if ($buff_check) {
                                    $Item->useBuffItem($row['dst_posi_pk'], $event_npc_troop['buff_pk']);
                                    // 자원 버프일 경우 다른 버프까지 한번에 넣어줌
                                    if ($event_npc_troop['buff_pk'] == 500506) { // 식량 생산량 증가
                                        $Item->useBuffItem($row['dst_posi_pk'], 500511); // 우마 생산량 증가
                                        $Item->useBuffItem($row['dst_posi_pk'], 500512); // 목재 생산량 증가
                                        $Item->useBuffItem($row['dst_posi_pk'], 500513); // 철강 생산량 증가
                                    }
                                    $Battle->eventBuffLetter($row['dst_lord_pk'], $event_npc_troop['buff_pk']);
                                }
                                // 포인트 초기화
                                $PgGame->query('UPDATE my_event SET event_att_point = 0 WHERE lord_pk = $1',  [$Session->lord['lord_pk']]);
                            }
                        } else {
                            $ret = $Troop->setBattleReward('attack', $row['troop_quest_npc_attack'], $row['dst_lord_pk']);
                            if ($ret['item_pk']) {
                                $z_content['outcome']['reward'] = $ret;
                            }
                            // 퀘스트
                            $Quest->conditionCheckQuest($row['dst_lord_pk'], ['quest_type' => 'battle', 'type' => 'marchnpctroop']);
                        }

                        // 부대삭제
                        $Troop->removeTroop($_troo_pk);

                        // Debug::debugMessage('I', 'npc_attack defenced');
                    } else {
                        // 부대삭제
                        $Troop->removeTroop($_troo_pk);
                    }

                    // 공격에 실패 했는데 방어측도 전멸했다면 NPC 주둔 처리
                    if ($dst_posi['type'] == 'P' && !$def_remain_captain) {
                        // 요충지 포기 처리
                        $Troop->lossOwnershipPoint($row['dst_lord_pk'], $row['dst_posi_pk']);
                        // NPC 생성
                        $Troop->setNpcPoint($row['dst_posi_pk']);
                    }

                    if ($dst_posi['type'] == 'S') {
                        // 섬멸전 전용 버프 삭제
                        $Item->delItemBuff($row['src_lord_pk'], $row['src_posi_pk'], $buff_item_pk);

                        // 사용된 아이템이 있을 경우 버프 삭제
                        if (isset($row['use_item_pk'])) {
                            $Item->delItemBuff($row['src_lord_pk'], $row['src_posi_pk'], $row['use_item_pk']);
                        }

                    }
                }

                // from & to
                $z_from = ['posi_pk' => $row['src_posi_pk'], 'posi_name' => $row['from_position'], 'lord_name' => $lord_name_withLevel];
                $z_to = ['posi_pk' => $row['dst_posi_pk'], 'posi_name' => $row['to_position'], 'lord_name' => $dst_posi['lord_name_withLevel']];
                $z_content_battle_att = $z_content_battle;

                if ($dst_posi['type'] == 'P') {
                    if ($row['src_lord_pk'] != $Session->lord['lord_pk']) {
                        if ($row['src_lord_pk'] != NPC_TROOP_LORD_PK) {
                            $z_from['posi_pk'] = '-';
                            $z_from['posi_name'] = '타  군주';
                            $z_from['lord_name'] = '적 부대';
                        }
                    }
                    if ($dst_posi['lord_pk'] != $Session->lord['lord_pk']) {
                        if ($dst_posi['lord_pk'] != NPC_TROOP_LORD_PK) {
                            $z_to['posi_pk'] = '-';
                            $z_to['posi_name'] = '타  군주';
                            $z_to['lord_name'] = '적 부대';
                        }
                    }

                    $def_lord_info['lord_name'] = '적 부대';
                    $def_lord_info['lord_level'] = '-';
                    $def_lord_info['alli_title'] = '-';
                    $z_content_battle_att['battle_info']['def']['lord_info'] = $def_lord_info;
                }

                // Debug::debugMessage('I', 'war report');

                $z_content['outcome']['cunit_amount']['att'] = $attack_army_converted_amount;
                $z_content['outcome']['cunit_amount']['def'] = $defence_army_converted_amount;

                // title & summary
                if ($row['src_lord_pk'] != NPC_TROOP_LORD_PK) {
                    // Debug::debugMessage('I', 'attack away');

                    $report_type = $att_success ? 'battle_attack_victory' : 'battle_attack_defeat';
                    $_sound_type = $att_success ? 'bgm_victory' : 'bgm_defeat';
                    $repo_pk = $Report->setReport($row['src_lord_pk'], 'battle', $report_type, $z_from, $z_to, '', '', json_encode($z_content), json_encode($z_content_battle_att), $_sound_type);

                    $Session->sqAppend('PUSH', [
                        'TOAST' => [
                            'type' => 'battle',
                            'result' => $report_type,
                            'posi_pk' => $row['dst_posi_pk'],
                            'pk' => $repo_pk
                        ],
                        'PLAY_EFFECT' => [
                            'type' => 'battle',
                            'posi_pk' => $row['dst_posi_pk'],
                        ]
                    ], null, $row['src_lord_pk']);


                    // Log
                    $battle_result = $att_success ? '승리' : '패배';

                    // 요새의 경우 요새 번호 표기를 위해
                    if ($dst_posi['type'] == 'S') {
                        $Log_dst_posi_withLevel = $dst_posi['lord_name_withLevel'].'('.$row['raid_troo_pk'].')';
                    } else {
                        $Log_dst_posi_withLevel = $dst_posi['lord_name_withLevel'];
                    }

                    $Log->setBattle($row['src_lord_pk'], $row['src_posi_pk'], 'battle_attack', $dst_posi['lord_pk'], $Log_dst_posi_withLevel, $row['dst_posi_pk'], $row['to_position'], $report_type, '', json_encode($z_content), $row['troop_type'], $battle_result, $occupation, $z_content['outcome']['plunder'] ?? null, $_troo_pk);

                    if ($dst_posi['type'] == 'P') {
                        $Log->setPoint($row['src_lord_pk'], $row['src_posi_pk'], 'battle_attack', $row['dst_posi_pk'], $report_type.';dst_lord_pk:['.$dst_posi['lord_pk'].'];lord_name:['.$dst_posi['lord_name_withLevel'].'];');
                    }

                    // 공격시
                    if ($dst_posi['lord_pk'] != NPC_TROOP_LORD_PK) {
                        // $Alliance->setAllianceWarHistory($row['src_lord_pk'], $repo_pk, $lord_name, 'A', $dst_posi['lord_name'], $row['dst_posi_pk']);
                        $Alliance->setAllianceWarHistory($repo_pk, $alli_pk, $lord_name, 'A', $dst_posi['lord_name'], $row['dst_posi_pk'], $dst_posi['alli_pk']);
                    }
                }

                // from & to
                $z_from = ['posi_pk' => $row['src_posi_pk'], 'posi_name' => $row['from_position'], 'lord_name' => $lord_name_withLevel];
                $z_to = ['posi_pk' => $row['dst_posi_pk'], 'posi_name' => $row['to_position'], 'lord_name' => $dst_posi['lord_name_withLevel']];
                $z_content_battle_def = $z_content_battle;

                if ($dst_posi['type'] == 'P') {
                    if ($row['src_lord_pk'] != $Session->lord['lord_pk']) {
                        if ($row['src_lord_pk'] != NPC_TROOP_LORD_PK) {
                            $z_to['posi_pk'] = '-';
                            $z_to['posi_name'] = '타  군주';
                            $z_to['lord_name'] = '적 부대';
                        }
                    }
                    if ($dst_posi['lord_pk'] != $Session->lord['lord_pk']) {
                        if ($dst_posi['lord_pk'] != NPC_TROOP_LORD_PK) {
                            $z_from['posi_pk'] = '-';
                            $z_from['posi_name'] = '타  군주';
                            $z_from['lord_name'] = '적 부대';
                        }
                    }

                    $att_lord_info['lord_name'] = '적 부대';
                    $att_lord_info['lord_level'] = '-';
                    $att_lord_info['alli_title'] = '-';

                    $z_content_battle_def['battle_info']['att']['lord_info'] = $att_lord_info;
                }

                if ($dst_posi['lord_pk'] != NPC_TROOP_LORD_PK) {
                    // Debug::debugMessage('I', 'defence home');

                    $report_type = $def_success ? 'battle_defence_victory' : 'battle_defence_defeat';
                    $_sound_type = $def_success ? 'bgm_victory' : 'bgm_defeat';
                    $repo_pk = $Report->setReport($dst_posi['lord_pk'], 'battle', $report_type, $z_to, $z_from, '', '', json_encode($z_content), json_encode($z_content_battle_def), $_sound_type);

                    $Session->sqAppend('PUSH', [
                        'TOAST' => [
                            'type' => 'battle',
                            'result' => $report_type,
                            'posi_pk' => $row['dst_posi_pk'],
                            'pk' => $repo_pk
                        ],
                        'PLAY_EFFECT' => [
                            'type' => 'battle',
                            'posi_pk' => $row['dst_posi_pk'],
                        ]
                    ], null, $dst_posi['lord_pk']);

                    // Log
                    $battle_result = $def_success ? '승리' : '패배';
                    $Log->setBattle($dst_posi['lord_pk'], $row['dst_posi_pk'], 'battle_defence', $row['src_lord_pk'], $lord_name_withLevel, $row['src_posi_pk'], $row['from_position'], $report_type, '', json_encode($z_content), $row['troop_type'], $battle_result, $occupation, $z_content['outcome']['plunder'] ?? null, $_troo_pk);

                    if ($dst_posi['type'] == 'P') {
                        $Log->setPoint($dst_posi['lord_pk'], $dst_posi['posi_pk'], 'battle_defence', $row['dst_posi_pk'], $report_type.';dst_lord_pk:['.$row['src_lord_pk'].'];lord_name:['.$lord_name_withLevel.'];');
                    }

                    // 방어시
                    if ($row['src_lord_pk'] != NPC_TROOP_LORD_PK) {
                        // $Alliance->setAllianceWarHistory($dst_posi['lord_pk'], $repo_pk, $dst_posi['lord_name'], 'D', $lord_name, $row['dst_posi_pk']);
                        $Alliance->setAllianceWarHistory($repo_pk, $dst_posi['alli_pk'], $dst_posi['lord_name'], 'D', $lord_name, $row['dst_posi_pk'], $alli_pk);
                    }
                }

                /*if ($row['src_lord_pk'] != NPC_TROOP_LORD_PK && $dst_posi['lord_pk'] != NPC_TROOP_LORD_PK) {
                    // 공격/방어 측 모두 NPC가 아닐 경우 네이트 토스트 메시지 보내도록 PUSH
                    $att_toast_msg = ['title' => '전투', 'battle_type' => '원정전투'];
                    $att_toast_msg['result'] = $att_success ? '승리' : '패배';
                    $Session->sqAppend('PUSH', ['BATTLE_RESULT_TOAST' => $att_toast_msg], null, $row['src_lord_pk']);

                    $def_toast_msg = ['title' => '방어', 'battle_type' => '방어전투'];
                    $def_toast_msg['result'] = $def_success ? '승리' : '패배';
                    $Session->sqAppend('PUSH', ['BATTLE_RESULT_TOAST' => $def_toast_msg], null, $dst_posi['lord_pk']);
                }*/
            }
        }

        // $PgGame->commit();
    } catch (Throwable $error) {
        // $PgGame->rollback();
        Debug::debugLogging($error);
        return $Render->view('[실패]');
    }

    return $Render->view('[OK]');
}));