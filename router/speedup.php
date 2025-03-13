<?php
global $app, $Render, $i18n;

// TODO 제대로 쓰는 클래스가 없네... 차후 정리 필요할듯

$app->post('/api/speedup', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Hero = new Hero($Session, $PgGame);
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Timer = new Timer($Session, $PgGame);

    $type = (! isset($params['type'])) ? null : $params['type'];

    $queue_pk = 0;
    if ($type == 'C') {
        $BuildConstruction = new BuildConstruction($Session, $PgGame);
        if ($params['position_type'] == 'I') {
            $Bd_c = new Bdic($Session, $PgGame, $Resource, $GoldPop);
        } else if ($params['position_type'] == 'O') {
            $Bd_c = new Bdoc($Session, $PgGame, $Resource, $GoldPop);
        } else {
            throw new ErrorHandler('error', 'Error Occurred. [36001]'); // speedup 불가 - not found position_type
        }

        $PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND type = $2', [$params['posi_pk'], 'C']);
        $buil_pk = $PgGame->FetchOne();

        if (!$buil_pk) {
            throw new ErrorHandler('error', 'Error Occurred. [36002]'); // speedup 불가 - not found buil_pk
        }

        $PgGame->query('SELECT buil_cons_pk FROM build_construction WHERE buil_pk = $1 AND position_type = $2 AND position = $3 AND status = \'P\'', [$buil_pk, $params['position_type'], $params['in_cast_pk']]);
        $queue_pk = $PgGame->FetchOne();

        if (!$queue_pk) {
            throw new ErrorHandler('error', 'Error Occurred. [36003]'); // speedup 불가 - not found queue_pk
        }
    } else if ($type == 'T') {
        $BuildTechnique = new BuildTechnique($Session, $PgGame, $Hero);
        $Technique = new Technique($Session, $PgGame, $Resource, $GoldPop);

        $PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND in_cast_pk = $2', [$params['posi_pk'], $params['in_cast_pk']]);
        $buil_pk = $PgGame->FetchOne();

        if (!$buil_pk) {
            throw new ErrorHandler('error', 'Error Occurred. [36004]'); // speedup 불가 - not found buil_pk
        }

        $PgGame->query('SELECT buil_tech_pk FROM build_technique WHERE buil_pk = $1 AND status = $2', [$buil_pk, 'P']);
        $queue_pk = $PgGame->FetchOne();

        if (!$queue_pk) {
            throw new ErrorHandler('error', 'Error Occurred. [36005]'); // speedup 불가 - not found queue_pk
        }
    } else if ($type == 'F') {
        $Terr = new Territory($Session, $PgGame);
        $BuildFortification = new BuildFortification($Session, $PgGame, $Timer);
        $Fortification = new Fortification($Session, $PgGame, $Resource, $GoldPop, $Terr);

        $PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND in_cast_pk = $2', [$params['posi_pk'], $params['in_cast_pk']]);
        $buil_pk = $PgGame->FetchOne();

        if (!$buil_pk) {
            throw new ErrorHandler('error', 'Error Occurred. [36006]'); // speedup 불가 - not found buil_pk
        }

        $PgGame->query('SELECT buil_fort_pk FROM build_fortification WHERE buil_pk = $1 AND status = $2', [$buil_pk, 'P']);
        $queue_pk = $PgGame->FetchOne();

        if (!$queue_pk) {
            throw new ErrorHandler('error', 'Error Occurred. [36007]'); // speedup 불가 - not found queue_pk
        }
    } else if ($type == 'W') {	// 자원지 방어시설
        $Terr = new Territory($Session, $PgGame);
        $BuildFortificationValley = new BuildFortificationValley($Session, $PgGame, $Timer);
        $FortificationValley = new FortificationValley($Session, $PgGame, $Resource, $GoldPop, $Terr);

        $PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND type = $2', [$params['posi_pk'], 'W']);
        $buil_pk = $PgGame->FetchOne();

        if (! $buil_pk) {
            throw new ErrorHandler('error', 'Error Occurred. [36008]'); // speedup 불가 - not found buil_pk
        }

        $PgGame->query('SELECT buil_fort_vall_pk FROM build_fortification_valley WHERE buil_pk = $1 AND status = $2', [$buil_pk, 'P']);
        $queue_pk = $PgGame->FetchOne();

        if (! $queue_pk) {
            throw new ErrorHandler('error', 'Error Occurred. [36009]'); // speedup 불가 - not found queue_pk
        }
    } else if ($type == 'A') {
        $BuildArmy = new BuildArmy($Session, $PgGame, $Timer);
        $Army = new Army($Session, $PgGame, $Resource, $GoldPop);

        $PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND in_cast_pk = $2', [$params['posi_pk'], $params['in_cast_pk']]);
        $buil_pk = $PgGame->FetchOne();

        if (!$buil_pk) {
            throw new ErrorHandler('error', 'Error Occurred. [36010]'); // speedup 불가 - not found buil_pk
        }

        $PgGame->query('SELECT buil_army_pk FROM build_army WHERE buil_pk = $1 AND status = $2', [$buil_pk, 'P']);
        $queue_pk = $PgGame->FetchOne();

        if (!$queue_pk) {
            throw new ErrorHandler('error', 'Error Occurred. [36011]'); // speedup 불가 - not found queue_pk
        }
    } else if ($type == 'M') {
        $BuildMedical = new BuildMedical($Session, $PgGame, $Timer);
        $Medical = new Medical($Session, $PgGame, $Resource, $GoldPop);

        $PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND in_cast_pk = $2', [$params['posi_pk'], $params['in_cast_pk']]);
        $buil_pk = $PgGame->FetchOne();

        if (!$buil_pk) {
            throw new ErrorHandler('error', 'Error Occurred. [36012]'); // speedup 불가 - not found buil_pk
        }

        $PgGame->query('SELECT buil_medi_pk FROM build_medical WHERE buil_pk = $1 AND status = $2', [$buil_pk, 'P']);
        $queue_pk = $PgGame->FetchOne();

        if (!$queue_pk) {
            throw new ErrorHandler('error', 'Error Occurred. [36013]'); // speedup 불가 - not found queue_pk
        }

        /*
         * 영웅 부상 삭제로 주석처리
         * $query_params = Array($params['medi_hero_pk'], 'T');
        $PgGame->query('SELECT medi_hero_pk FROM medical_hero WHERE medi_hero_pk = $1 AND status = $2', $query_params);
        $queue_pk = $PgGame->FetchOne();

        if (!$queue_pk)
        {
            throw new ErrorHandler('error', 'speedup 불가 - not found queue_pk');
            exit;
        }*/
    } else if ($type == 'X') {
        $PgGame->query('SELECT troo_pk FROM troop WHERE troo_pk = $1 AND status IN ($2, $3)', [$params['troo_pk'], 'R', 'W']);
        $queue_pk = $PgGame->FetchOne();

        if (!$queue_pk) {
            throw new ErrorHandler('error', 'Error Occurred. [36014]'); // speedup 불가 - not found queue_pk
        }
    }

    // 공통
    $time_pk = null;
    $reduce_time = 0;
    if (isset($params['time_pk'])) {
        $PgGame->Query('SELECT time_pk, queue_type FROM timer WHERE time_pk = $1', [$params['time_pk']]);
        if ($PgGame->Fetch()) {
            $time_pk = $PgGame->row['time_pk'];
            $queue_type = $PgGame->row['queue_type'];
        }
    } else {
        $PgGame->Query('SELECT time_pk FROM timer WHERE queue_type = $1 AND queue_pk = $2', [$type, $queue_pk]);
        if ($PgGame->Fetch()) {
            $time_pk = $PgGame->row['time_pk'];
        }
        $queue_type = $type;
    }

    if (! $time_pk) {
        throw new ErrorHandler('error', 'Error Occurred. [36015]'); // speedup 불가 - not found time_pk
    }

    // 5분이내 무료 독려
    if ($queue_type == 'C' || $queue_type == 'T') {
        if (isset($params['free']) && $params['free'] == 'Y') {
            $PgGame->query('SELECT date_part(\'epoch\', end_dt)::integer FROM timer WHERE time_pk = $1', [$time_pk]);
            $end_dt = $PgGame->FetchOne();
            $remain_dt = $end_dt - time();

            if ($remain_dt > FREE_SPEEDUP_TIME) {
                throw new ErrorHandler('error', 'Error Occurred. [36016]'); // 아이템을 사용해 주세요.
            }

            $reduce_time = FREE_SPEEDUP_TIME;

            // 퀘스트 체크
            $Quest = new Quest($Session, $PgGame);
            $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'speedup']);
        }
    }

    // 아이템 소모 등 처리
    global $NsGlobal, $_NS_SQ_REFRESH_FLAG, $_M;

    $m_item_pk = (! isset($params['m_item_pk'])) ? null : $params['m_item_pk'];

    $Item = new Item($Session, $PgGame);
    $Cash = new Cash($Session, $PgGame);
    $Log = new Log($Session, $PgGame);
    try {
        $PgGame->begin();
        $_NS_SQ_REFRESH_FLAG = true;

        if ($m_item_pk) {
            $PgGame->query('SELECT date_part(\'epoch\', end_dt)::integer - date_part(\'epoch\', now())::integer FROM timer WHERE time_pk = $1', [$time_pk]);
            $remain_dt = $PgGame->FetchOne();
            if (!$remain_dt) {
                throw new Exception('Error Occurred. [36017]'); // 아이템을 사용해도 효과가 없습니다.
            }

            // 즉시완료
            if ($m_item_pk == 500078 || $m_item_pk == 500081 || $m_item_pk == 500082) {
                $remain_qbig = Useful::getNeedQbig($remain_dt);

                $qbig = $Cash->decreaseCash($Session->lord['lord_pk'], $remain_qbig, 'speedup now');
                if (!$qbig) {
                    throw new Exception($i18n->t('msg_qbig_lack')); // 큐빅이 부족합니다.
                }

                $reduce_time = $remain_dt;

                // Log
                $Log->setItem($Session->lord['lord_pk'], $Session->getPosiPk(), 'use', null, $m_item_pk, $remain_qbig);
            } else {
                //buyuse 아이템일 경우와 use 아이템일 경우 분리처리
                $item_cnt = 0;

                $NsGlobal->requireMasterData(['item']);

                if ($params['action'] == 'buy_use_item') {
                    //캐쉬 차감하기
                    $ret = $Cash->decreaseCash($Session->lord['lord_pk'], $_M['ITEM'][$m_item_pk]['price'], 'speedup_item');
                    if(!$ret) {
                        throw new Exception($i18n->t('msg_qbig_lack')); // 큐빅이 부족합니다.
                    }
                } else if ($params['action'] == 'use_item') {
                    $item_cnt = $Item->getITemCount($Session->lord['lord_pk'], $m_item_pk);
                    if (!$item_cnt) {
                        throw new Exception('Error Occurred. [36018]'); // 해당 아이템이 없습니다.
                    }
                    $item_cnt = 1;
                }

                // 아이템 지급
                $Item->BuyItem($Session->lord['lord_pk'], $m_item_pk, $item_cnt, 'now_use');

                $ret = $Item->useItem($params['posi_pk'], $Session->lord['lord_pk'], $m_item_pk, $item_cnt, ['_yn_quest' => true]);
                if (!$ret) {
                    throw new Exception('Error Occurred. [36019]'); // 아이템 사용에 실패
                }

                if ($m_item_pk == 500802) { // 위조된 지원령
                    $reduce_time = 300;
                } else if ($m_item_pk == 500036) { // 일반 지원령
                    $reduce_time = 900;
                } else if ($m_item_pk == 500038) {	//삼급 지원령
                    $reduce_time = 3600;
                } else if ($m_item_pk == 500039) {	//이급 지원령
                    $reduce_time = 9000;
                } else if ($m_item_pk == 500041) {	//일급 지원령
                    $reduce_time = 28800;
                } else if ($m_item_pk == 500042) {	//특급 지원령
                    $rand_time = rand(10, 30);
                    $reduce_time = $rand_time * 3600;
                } else if ($m_item_pk == 500043 || $m_item_pk == 500040 || $m_item_pk == 500037) {	//총 지원령, 긴급 충원령, 긴급 방비령
                    $reduce_time = (int)($remain_dt * 0.3);
                } else if ($m_item_pk == 500035) {	// 역참지원
                    $reduce_time = 1800;
                } else if ($m_item_pk == 500060) {	// 화타의 치료
                    $reduce_time = 36000;
                } else if ($m_item_pk == 500058) {	// 금창약
                    $reduce_time = 3600;
                } else if ($m_item_pk == 500059) {	// 속명단
                    $reduce_time = 18000;
                } else if ($m_item_pk == 500057) {	// 지혈산
                    $reduce_time = 900;
                } else if ($m_item_pk == 500056) {	// 흑옥고
                    $reduce_time = 7200;
                } else if ($m_item_pk == 500164) {	// 즉시회군
                    $reduce_time = $remain_dt;
                }
            }
        }

        $Timer = new Timer($Session, $PgGame);
        $result_arr = $Timer->speedup($time_pk, $reduce_time, $m_item_pk);
        if (! $result_arr) {
            throw new Exception('Error Occurred. [36020]'); // 아이템 사용에 실패
        }

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success');
}));
