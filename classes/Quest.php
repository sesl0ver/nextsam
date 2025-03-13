<?php

class Quest
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Pg $PgCommon;
    protected Lord $Lord;
    protected Army $Army;
    protected Territory $Territory;
    protected Fortification $Fortification;
    protected Item $Item;
    protected Resource $Resource;
    protected GoldPop $GoldPop;
    protected Troop $Troop;
    protected Log $Log;

    protected mixed $ret = [];

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    function classLord(): void
    {
        if (! isset($this->Lord)) {
            $this->Lord = new Lord($this->Session, $this->PgGame);
        }
    }

    function classArmy(): void
    {
        if (! isset($this->Army)) {
            $this->classResource();
            $this->classGoldPop();
            $this->Army = new Army($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classFortification(): void
    {
        if (! isset($this->Fortification)) {
            $this->classResource();
            $this->classGoldPop();
            $this->classTerritory();
            $this->Fortification = new Fortification($this->Session, $this->PgGame, $this->Resource, $this->GoldPop, $this->Territory);
        }
    }

    function classTerritory(): void
    {
        if (! isset($this->Territory)) {
            $this->Territory = new Territory($this->Session, $this->PgGame);
        }
    }

    function classTroop(): void
    {
        if (! isset($this->Troop)) {
            $this->Troop = new Troop($this->Session, $this->PgGame);
        }
    }


    function classResource(): void
    {
        if (! isset($this->Resource)) {
            $this->Resource = new Resource($this->Session, $this->PgGame);
        }
    }

    function classGoldPop(): void
    {
        if (! isset($this->GoldPop)) {
            $this->GoldPop = new GoldPop($this->Session, $this->PgGame);
        }
    }

    function classItem(): void
    {
        if (! isset($this->Item)) {
            $this->Item = new Item($this->Session, $this->PgGame);
        }
    }

    function classPgCommon(): void
    {
        if (! isset($this->PgCommon)) {
            $this->PgCommon = new Pg('COMMON');
        }
    }

    protected function classLog (): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    function get($_lord_pk, $_account_id = null): void
    {
        //my_quest 데이터 가져오기
        $this->PgGame->query('SELECT m_ques_pk, status, reward_status, condition_value FROM my_quest WHERE lord_pk = $1 AND invisible = $2 order by m_ques_pk', [$_lord_pk, 'N']);
        $this->PgGame->fetchAll();
        $quest = $this->PgGame->rows;

        //만약 값이 없으면 초기데이터값 생성
        if ($this->PgGame->getNumRows() == 0) {
            $this->initQuest($_lord_pk);
        }

        // 퀘스트 추가가 필요할 때 아래 $quest_arr 에 m_ques_pk를 추가해줌.
        $quest_arr = [];

        $quest_arr[] = 607501; // 공적패(청)1
        $quest_arr[] = 607506; // 공적패(적)1
        $quest_arr[] = 607511; // 공적패(자)1
        $quest_arr[] = 607601; // 행운의 주화 제작
        $quest_arr[] = 607701; // 건설허가서 제작 퀘스트
        $quest_arr[] = 607801; // 봉황의 상자 제작 퀘스트
        $quest_arr[] = 600736; // 평가하기
        $quest_arr[] = 603321; // 11레벨 영웅 퀘스트
        $quest_arr[] = 607901; // 철강3,000 / 식량1,000 교환1
        $quest_arr[] = 607902; // 철강5,000 / 식량2,000 교환2

        $quest_arr[] = 608005; // 수수께끼의 보물지도 조각 /위조된 지원령 교환
        $quest_arr[] = 608006; // 보물지도 조각 I /위조된 지원령 교환
        $quest_arr[] = 608007; // 보물지도 조각 II /위조된 지원령 교환
        $quest_arr[] = 608008; // 보물지도 조각 III /위조된 지원령 교환
        $quest_arr[] = 608009; // 보물지도 조각 IV /위조된 지원령 교환
        $quest_arr[] = 608010; // 보물지도 조각 V /위조된 지원령 교환

        $quest_arr[] = 608101; // 공적패 분해	공적패(청)1
        $quest_arr[] = 608104; // 공적패 분해	공적패(적)1

        $quest_arr[] = 608201; // 이벤트퀘스트
        $quest_arr[] = 608202; // 이벤트퀘스트
        $quest_arr[] = 608203; // 이벤트퀘스트
        $quest_arr[] = 608204; // 이벤트퀘스트
        $quest_arr[] = 608205; // 이벤트퀘스트
        $quest_arr[] = 608206; // 이벤트퀘스트
        $quest_arr[] = 608301; // 이벤트퀘스트
        $quest_arr[] = 608302; // 이벤트퀘스트
        $quest_arr[] = 608303; // 이벤트퀘스트
        $quest_arr[] = 608304; // 이벤트퀘스트
        $quest_arr[] = 608305; // 이벤트퀘스트
        $quest_arr[] = 608306; // 이벤트퀘스트
        $quest_arr[] = 608307; // 이벤트퀘스트
        $quest_arr[] = 608308; // 이벤트퀘스트
        $quest_arr[] = 608309; // 이벤트퀘스트
        $quest_arr[] = 608310; // 이벤트퀘스트
        $quest_arr[] = 608311; // 이벤트퀘스트
        $quest_arr[] = 608312; // 이벤트퀘스트
        $quest_arr[] = 608313; // 이벤트퀘스트

        foreach($quest_arr as $k => $v) {
            // 특정 퀘스트가 종료되어 있는 유저에게 퀘스트 입력
            if ($v == 600917 || $v == 603321) {
                $check_quest = 603312; // 2레벨 영웅 퀘스트
                if ($v == 603321) {
                    $check_quest = 603320; // 10레벨 영웅 퀘스트
                }

                // 클리어 하고 보상받은 기록이 있는지 확인
                $this->PgGame->query('SELECT m_ques_pk FROM my_quest WHERE lord_pk = $1 AND m_ques_pk = $2 AND status = $3 AND reward_status = $4', [$_lord_pk, $check_quest, 'C', 'Y']);

                if ($this->PgGame->getNumRows() == 0) {
                    // 클리어 하고 보상받은 기록이 없다면 다음으로
                    continue;
                }
            }

            $this->PgGame->query('SELECT m_ques_pk FROM my_quest WHERE lord_pk = $1 and m_ques_pk = $2', [$_lord_pk, $v]);
            if ($this->PgGame->getNumRows() == 0) {
                // 퀘스트가 없다면 퀘스트 추가.
                $this->PgGame->query('INSERT INTO my_quest (lord_pk, m_ques_pk, status, reward_status, start_dt, last_up_dt) VALUES ($1, $2, \'P\', \'N\', now(), now())', [$_lord_pk, $v]);
            }
        }

        $this->PgGame->query('SELECT m_ques_pk, status, reward_status, condition_value FROM my_quest WHERE lord_pk = $1 AND invisible = $2', [$_lord_pk, 'N']);

        // quest 데이터 넣기
        $quests = [];
        while ($this->PgGame->fetch()) {
            $quests[$this->PgGame->row['m_ques_pk']] = $this->PgGame->row;
        }

        /*$this->classPgCommon();

        if (! $_account_id) {
            $this->PgGame->query('SELECT web_id FROM lord_web WHERE lord_pk = $1', [$_lord_pk]);
            $_account_id = $this->PgGame->fetchOne();
        }

        $this->PgCommon->query('SELECT COUNT(m_ques_pk) FROM my_quest WHERE acco_pk = $1', [$_account_id]);
        if (!$this->PgCommon->fetchOne()) {
            $this->initCommonQuest($_lord_pk, $_account_id);
        }

        $this->PgCommon->query('SELECT m_ques_pk, status, reward_status FROM my_quest WHERE acco_pk = $1 AND invisible = $2', [$_account_id, 'N']);
        while ($this->PgCommon->fetch()) {
            $quests[$this->PgCommon->row['m_ques_pk']] = $this->PgCommon->row;
        }*/

        $this->Session->sqAppend('QUES', $quests);
    }

    /*function checkFriendQuest($_acco_pk): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        $this->classPgCommon();
        $this->PgCommon->query('SELECT count(a.acco_pk) FROM friend a, account b, account_lord_info c WHERE a.acco_pk = $1 AND a.apps_id = b.main_apps_id AND b.acco_pk = c.acco_pk', [$_acco_pk]);
        $my_friend_recruit_cnt = $this->PgCommon->fetchOne();

        $this->PgCommon->query('SELECT count(a.acco_pk) FROM friend a, account b, account_lord_info c WHERE a.acco_pk = $1 AND a.invite_count > 0 AND a.apps_id = b.main_apps_id AND b.acco_pk = c.acco_pk', [$_acco_pk]);
        $my_friend_invite_cnt = $this->PgCommon->fetchOne();
        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'FRIEND_RECRUIT') {
                // 내친구 수
                if ($v['condition_1'] <= $my_friend_recruit_cnt) {
                    $this->completeCommonQuest($_acco_pk, $k);
                }
            } else if ($v['goal_type'] == 'FRIEND_INVITE') {
                // 내가 초대한 친구 수
                if ($v['condition_1'] <= $my_friend_invite_cnt) {
                    $this->completeCommonQuest($_acco_pk, $k);
                }
            }
        }
    }*/

    // 퀘스트 완료
    /*function completeCommonQuest($_acco_pk, $_m_ques_pk): bool
    {
        if (!$_acco_pk) {
            return false;
        }

        $this->classPgCommon();

        $this->PgCommon->query('SELECT status FROM my_quest WHERE acco_pk = $1 AND m_ques_pk = $2', [$_acco_pk, $_m_ques_pk]);
        if(!$this->PgCommon->fetch()) {
            //퀘스트 insert 해줌.
            if ($_m_ques_pk) {
                $this->PgCommon->query('INSERT INTO my_quest (acco_pk, m_ques_pk, status, reward_status, start_dt, last_up_dt, invisible) VALUES ($1, $2, $3, $4, now(), now(), $5)', [$_acco_pk, $_m_ques_pk, 'C', 'N', 'Y']);
            }
        } else {
            if ($this->PgCommon->row['status'] == 'P') {
                // 퀘스트 조건 완료로 변경, 보상은 N로 변경
                $this->PgCommon->query('UPDATE my_quest SET status = $1, reward_status = $2, last_up_dt = now() WHERE acco_pk = $3 AND m_ques_pk = $4', ['C', 'N', $_acco_pk, $_m_ques_pk]);

                $this->Session->sqAppend('PUSH', ['QUEST_COMPLETE' => $_m_ques_pk], null, $this->Session->lord['lord_pk']);

                $this->setChanged($this->Session->lord['lord_pk']);
            }
        }
        return true;
    }*/

    function getCompleteQuestCount($_lord_pk): int
    {
        $this->PgGame->query('SELECT count(my_ques_pk) FROM my_quest WHERE lord_pk = $1 AND status = \'C\' AND reward_status = \'N\' AND invisible = \'N\'', [$_lord_pk]);
        $quest_cnt = $this->PgGame->fetchOne();

        // 통합 DB 퀘스트 개수 체크
        /*$this->PgGame->query('SELECT web_id FROM lord_web WHERE lord_pk = $1', [$_lord_pk]);
        $acco_pk = $this->PgGame->fetchOne();

        $this->classPgCommon();
        $this->PgCommon->query('SELECT count(acco_pk) FROM my_quest WHERE acco_pk = $1 AND status = \'C\' AND reward_status = \'N\' AND invisible = \'N\'', [$acco_pk]);
        $quest_cnt += $this->PgCommon->fetchOne();*/

        return $quest_cnt;
    }

    // 처음 로그인할때 퀘스트 초기화
    function initQuest($_lord_pk): void
    {
        //추가해야할 퀘스트. 마스터데이터중 sub_precondition 0인 것들 추가
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['sub_precondition'] == 0 && $v['type'] != 'friend') {
                // 친구 초대 일일 퀘스트는 다음에서만 진행
                $this->PgGame->query('SELECT web_channel FROM lord_web WHERE lord_pk = $1', [$_lord_pk]);
                $web_channel = $this->PgGame->fetchOne();
                // TODO 이젠 의미 없는 퀘스트가 아닌지?
                if ($web_channel != 'DAUM' && $web_channel != 'NAVER_BLOG' && $web_channel != 'NAVER_CAFE' && $web_channel != 'NAVER_ME2DAY' && $web_channel != 'FACEBOOK') {
                    if ($k == 600105) {
                        continue;
                    }
                }

                /*$this->PgGame->query('SELECT jobenabled FROM pgagent.pga_job WHERE jobid = 27');
                if ($this->PgGame->fetchOne() != 't') {
                    if ($k == 605401 || $k == 605501 || $k == 605601) {
                        continue;
                    }
                }*/

                $this->PgGame->query('INSERT INTO my_quest (lord_pk, m_ques_pk, status, reward_status, start_dt, last_up_dt) VALUES ($1, $2, \'P\', \'N\', now(), now())', [$_lord_pk, $v['m_ques_pk']]);
            }
        }

        $this->PgGame->query('UPDATE my_quest set status = $2 WHERE lord_pk = $1 AND m_ques_pk = $3', [$_lord_pk, 'C', 600708]);

        $this->setChanged($_lord_pk);
    }

    /*function initCommonQuest($_lord_pk, $_acco_pk): void
    {
        //추가해야할 퀘스트. 마스터데이터중 sub_precondition이 0인 것들 추가
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        $this->classPgCommon();

        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['sub_precondition'] == 0 && $v['type'] == 'friend')
            {
                // 친구 만들기 퀘스트 다음제외
                $query_params = [$_lord_pk];
                $this->PgGame->query('SELECT web_channel FROM lord_web WHERE lord_pk = $1', $query_params);
                $web_channel = $this->PgGame->fetchOne();

                if ($web_channel == 'DAUM') {
                    if ($k == 604401) {
                        continue;
                    }
                }

                if ($web_channel == 'DAUM' || $web_channel == 'NAVER_BLOG' || $web_channel == 'NAVER_CAFE' || $web_channel == 'NAVER_ME2DAY') {
                    if ($k == 604414) {
                        continue;
                    }
                }

                $this->PgCommon->query('INSERT INTO my_quest (acco_pk, m_ques_pk, status, reward_status, start_dt, last_up_dt) VALUES ($1, $2, \'P\', \'N\', now(), now())', [$_acco_pk, $v['m_ques_pk']]);
            }
        }
    }*/

    function initDailyQuest($_lord_pk): void
    {
        $this->PgGame->query('SELECT date_part(\'epoch\', last_up_dt)::integer AS last_up_dt, m_ques_pk FROM my_quest WHERE lord_pk = $1 AND m_ques_pk = $2 AND status = $3 AND invisible = $4', [$_lord_pk, 600103, 'P', 'N']); // , 600104, 600105, 600106, 600107
        $last_up_dt = $this->PgGame->fetchOne();

        $today = mktime(0, 5, 1, date('m'), date('d'), date('Y'));
        if ($last_up_dt < $today) {
            $this->PgGame->query('UPDATE my_quest SET status = $3, reward_status = $4, start_dt = now(), last_up_dt = now(), invisible = $4 WHERE lord_pk = $1 AND m_ques_pk = $2', [$_lord_pk, 600103, 'P', 'N']);
        }
    }

    // 퀘스트 타입별 체크하기
    function conditionCheckQuest($_lord_pk, $_ret = null): bool
    {
        // 타입에 따라 가능한 퀘스트들 조건 검사. 각각 타입에 따라 조건 검사하는 함수 추가 필요.
        if (! isset($_ret['quest_type'])) {
            return false;
        }

        $start_time = Useful::microTimeFloat();
        if ($_ret['quest_type'] == 'hero_salary') {
            $this->checkHeroSalary($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'daily_gift') {
            $this->checkDailyGift($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'daily_request') {
            $this->checkDailyRequest($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'daily_army') {
            $this->checkDailyArmy($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'buil_upgrade') {
            $this->checkBuilUpgrade($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'use_item') {
            $this->checkUseItem($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'devel_tech') {
            $this->checkDevelTechnique($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'administration') {
            $this->checkAdministration($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'hero_assign') {
            $this->checkHeroAssign($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'lord_power') {
            $this->checkLordPower($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'hero') {
            $this->checkHero($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'army_recruit') {
            $this->checkArmyRecruit($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'fortification') {
            $this->checkFortification($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'territory') {
            $this->checkTerritoryResource($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'give_item') {
            $this->checkGiveItem($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'making') {
            $this->checkMakingItem($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'tradeDept') {
            $this->checkTradeDepartment($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'alliance') {
            $this->checkAlliance($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'castle_gate') {
            $this->checkCastleGate($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'market') {
            $this->checkMarket($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'magic_cube') {
            $this->checkUseMagicCube($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'battle') {
            $this->checkBattle($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'medical') {
            $this->checkMedical($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'build_duplication') {
            $this->checkBuildDuplication($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'storage_rate') {
            $this->checkStorageRate($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'speedup') {
            $this->checkSpeedup($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'buy_item') {
            $this->checkBuyItem($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'lord_introduce_change') {
            $this->checkLordIntroduceChange($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'invite_club') {
            $this->checkInviteClub($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'invite_friend') {
            //$this->checkInviteFriend($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'option_sound') {
            $this->checkOptionSound($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'hero_boast') {
            $this->checkHeroBoast($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'buy_qbig') {
            $this->checkBuyQbig($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'daily_invite') {
            $this->checkDailyInvite($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'daily_dispatch') {
            $this->checkDailyDispatch($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'army_point') {
            $this->checkArmyPoint($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'timer') {
            $this->checkHeroSalary($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'lord_name_change') {
            $this->checkLordNameChange($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'exchange_item') {
            $this->checkExchangeItem($_lord_pk, $_ret);
        } else if ($_ret['quest_type'] == 'game_evaluate') {
            $this->checkGameEvaluate($_lord_pk, $_ret);
        }

        $end_time = Useful::microTimeFloat();
        // debug_mesg('D', __CLASS__, __FUNCTION__, __LINE__, 'quest;time['. ($end_time - $start_time) .'];quest_type['.$_ret['quest_type'].'];');
        return true;
    }

    // 신규 퀘스트(병력 퀘스트)
    function checkArmyPoint($_lord_pk, $_ret): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'ARMY_POINT') {
                if ($v['condition_1'] <= $_ret['army_point']) {
                    $this->completeQuest($_lord_pk, $k);
                }
            }
        }
    }

    // daily 퀘스트
    function checkHeroSalary($_lord_pk, $_ret): void
    {
        /*
         * TODO 전체적으로 시간 확인 필요. 기존 서비스시 한국 기준시로 맞춰 놓았기 때문.
         */
        $today = mktime(0, 5, 1);
        $now_hour = date('G');
        $now_minutes = date('i');
        if ($now_hour == 0 && $now_minutes <= 10) {
            return;
        }
        $this->completeQuest($_lord_pk, $_ret['m_ques_pk']);
    }

    function checkDailyGift($_lord_pk, $_ret): void
    {
        /*
         * TODO 전체적으로 시간 확인 필요. 기존 서비스시 한국 기준시로 맞춰 놓았기 때문.
         */
        $today = mktime(0, 5, 1);
        $now_hour = date('G');
        $now_minutes = date('i');

        if ($now_hour == 0 && $now_minutes <= 10) {
            return;
        }
        $this->completeQuest($_lord_pk, $_ret['m_ques_pk']);
    }

    function checkDailyRequest($_lord_pk, $_ret): void
    {
        /*
         * TODO 전체적으로 시간 확인 필요. 기존 서비스시 한국 기준시로 맞춰 놓았기 때문.
         */
        $today = mktime(0, 5, 1);
        $now_hour = date('G');
        $now_minutes = date('i');

        if ($now_hour == 0 && $now_minutes <= 10) {
            return;
        }

        $this->completeQuest($_lord_pk, $_ret['m_ques_pk']);
    }

    function checkDailyArmy($_lord_pk, $_ret): void
    {
        /*
         * TODO 전체적으로 시간 확인 필요. 기존 서비스시 한국 기준시로 맞춰 놓았기 때문.
         */
        $today = mktime(0, 5, 1);
        $now_hour = date('G');
        $now_minutes = date('i');

        if ($now_hour == 0 && $now_minutes <= 10) {
            return;
        }

        $this->completeQuest($_lord_pk, $_ret['m_ques_pk']);
    }

    function checkDailyInvite($_lord_pk, $_ret): void
    {
        /*
         * TODO 전체적으로 시간 확인 필요. 기존 서비스시 한국 기준시로 맞춰 놓았기 때문.
         */
        $today = mktime(0, 5, 1);
        $now_hour = date('G');
        $now_minutes = date('i');

        if ($now_hour == 0 && $now_minutes <= 10) {
            return;
        }

        $this->completeQuest($_lord_pk, $_ret['m_ques_pk']);
    }

    // 일일 황건적 퀘스트
    function checkDailyDispatch($_lord_pk, $_ret): void
    {
        $this->completeQuest($_lord_pk, $_ret['m_ques_pk']);
    }

    // 5분 무료 독려
    function checkSpeedup($_lord_pk, $_ret): void
    {
        $this->completeQuest($_lord_pk, 600419);
    }

    // 아이템 구입
    function checkBuyItem($_lord_pk, $_ret): void
    {
        $this->completeQuest($_lord_pk, 600724);
    }

    // 군주 소개 변경
    function checkLordIntroduceChange($_lord_pk, $_ret): void
    {
        $this->completeQuest($_lord_pk, 600725);
    }

    // 클럽 방문
    function checkInviteClub($_lord_pk, $_ret): void
    {
        $this->PgGame->query('UPDATE lord SET last_visit_club_dt = now() WHERE lord_pk = $1', [$this->Session->lord['lord_pk']]);
        $this->completeQuest($_lord_pk, 600726);
    }

    // 친구초대하기
    function checkInviteFriend($_lord_pk, $_ret): void
    {
        $this->completeQuest($_lord_pk, 600727);
    }

    // 동시 건설 진행
    function checkBuildDuplication($_lord_pk, $ret): void
    {
        $this->completeQuest($_lord_pk, 600723);
    }

    // 영웅 자랑하기
    function checkHeroBoast($_lord_pk, $_ret): void
    {
        $this->completeQuest($_lord_pk, 600730);
    }

    // 사운드 끄기, 켜기
    function checkOptionSound($_lord_pk, $_ret): void
    {
        if ($_ret['value'] == 'off') {
            $this->completeQuest($_lord_pk, 600728);
        } else if($_ret['value'] == 'on') {
            $this->completeQuest($_lord_pk, 600729);
        }
    }

    // 게임 옵션 설정
    function checkGameOption($_lord_pk, $_ret): void
    {
        $this->completeQuest($_lord_pk, 600728);
    }

    // 평가하기
    function checkGameEvaluate($_lord_pk, $_ret): void
    {
        $this->completeQuest($_lord_pk, 600736);
    }

    // 큐빅패키지 구매
    function checkBuyQbig($_lord_pk, $_ret): void
    {
        $this->completeQuest($_lord_pk, 600731);
    }

    // 창고 저장 비율 조정
    function checkStorageRate($_lord_pk, $ret): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        if ($ret['food'] == $_M['QUES'][600410]['condition_1'] && $ret['horse'] == $_M['QUES'][600410]['condition_2']
            && $ret['lumber'] == $_M['QUES'][600410]['condition_3'] &&$ret['iron'] == $_M['QUES'][600410]['condition_4'] ) {
            $this->completeQuest($_lord_pk, 600410);
        }
    }

    // 전투 퀘스트
    function checkBattle($_lord_pk, $_ret): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'BATTLE') {
                if ($v['condition_1'] == $_ret['type']) {
                    if ($v['condition_1'] == 'occupation_valley' || $v['condition_1'] == 'attack_territory_npc') {
                        if ($v['condition_2'] == $_ret['level']) {
                            $this->completeQuest($_lord_pk, $k);
                        }
                    } else if ($v['condition_1'] == 'attack_territory_npc_event' || $v['condition_1'] == 'attack_suppress_npc_event'){
                        $this->completeQuest($_lord_pk, $k);
                    } else {
                        if ($v['condition_1'] == 'new_territory' && $v['condition_2'] > 2) {
                            $this->PgGame->query('SELECT COUNT(lord_pk) FROM position WHERE lord_pk = $1 AND type = $2', [$_lord_pk, 'T']);
                            if ($this->PgGame->fetchOne() >= $v['condition_2']) {
                                $this->completeQuest($_lord_pk, $k);
                            }
                        } else {
                            $this->completeQuest($_lord_pk, $k);
                        }
                    }
                }
            }
        }
    }

    // 치료 퀘스트
    function checkMedical($_lord_pk, $_ret): void
    {
        if ($_ret['type'] == 'army') {
            $this->completeQuest($_lord_pk, 601709);
        }
    }

    // 건물 업그레이드 퀘스트
    function checkBuilUpgrade($_lord_pk, $_ret): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        // m_buil_pk, level
        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'BUILDING_UPGRADE') {
                if ($v['condition_1'] == $_ret['m_buil_pk'] && $v['condition_2'] <= $_ret['level'] + 1) {
                    $this->completeQuest($_lord_pk, $k);
                }
            }
        }
        $this->checkBuildingConstruction($_lord_pk, $_ret);

        $this->ret = $_ret;
    }

    // 건물 건설 완료 퀘스트
    function checkBuildingConstruction($_lord_pk, $_ret): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        // m_buil_pk, level
        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'BUILDING_MAKE') {
                if ($v['condition_1'] == $_ret['m_buil_pk']) {
                    if ($_ret['position_type'] == 'I') {
                        $this->PgGame->query('SELECT COUNT(posi_pk) FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2 AND level > 0', [$_ret['posi_pk'], $_ret['m_buil_pk']]);
                    } else {
                        $this->PgGame->query('SELECT COUNT(posi_pk) FROM building_out_castle WHERE posi_pk = $1 AND m_buil_pk = $2 AND level > 0', [$_ret['posi_pk'], $_ret['m_buil_pk']]);
                    }
                    $build_count = $this->PgGame->fetchOne();

                    if ($build_count >= $v['condition_2']) {
                        $this->completeQuest($_lord_pk, $k);
                    }
                }
            }
        }

        $this->ret = $_ret;
    }

    // 아이템 사용 퀘스트
    function checkUseItem($_lord_pk, $_ret): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest', 'item']);

        // m_item_pk, level
        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'USE_ITEM') {
                $result = in_array($_ret['m_item_pk'], [$v['condition_1'], $v['condition_2'], $v['condition_3'], $v['condition_4'], $v['condition_5']]);
                if ($result) {
                    $this->completeQuest($_lord_pk, $k);

                    // 자원 증가 아이템 체크
                    $use_type = $_M['ITEM'][$_ret['m_item_pk']]['use_type'];
                    if ($use_type == 'population' || $use_type == 'food' || $use_type == 'horse' || $use_type == 'lumber') {
                        $this->conditionCheckQuest($_lord_pk, ['quest_type' => 'territory', 'posi_pk' => $_ret['posi_pk']]);
                    }
                }
            }
        }

        $this->ret = $_ret;
    }

    //  태학 기술 개발 퀘스트
    function checkDevelTechnique($_lord_pk, $_ret): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'DEVEL_TECHNIQUE') {
                if ($v['condition_1'] == $_ret['m_tech_pk'] && $v['condition_2'] <= $_ret['level'] + 1) {
                    $this->completeQuest($_lord_pk, $k);
                }
            }
        }

        $this->ret = $_ret;
    }

    // 행정부 관련 퀘스트(영지관리)
    function checkAdministration($_lord_pk, $_ret): void
    {
        $m_ques_pk = 0;

        if ($_ret['quest_kind'] == 'tax_rate')  {
            $m_ques_pk = 600504;
        } else if ($_ret['quest_kind'] == 'redress') {	// 구휼
            $m_ques_pk = 600510;
        } else if ($_ret['quest_kind'] == 'gold') {		// 황금 징발
            $m_ques_pk = 600505;
        } else if ($_ret['quest_kind'] == 'terr_title') { //영지명 변경
            $m_ques_pk = 600513;
        }

        if ($m_ques_pk > 0) {
            $this->completeQuest($_lord_pk, $m_ques_pk);
        }
    }

    // 영웅 배속 퀘스트
    function checkHeroAssign($_lord_pk, $_ret): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'HERO_ASSIGN') {
                if ($v['condition_1'] == $_ret['m_buil_pk'] && $v['condition_2'] == $_ret['assign']) {
                    $this->completeQuest($_lord_pk, $k);
                }
            }
        }

        $this->ret = $_ret;
    }

    // 영향력 퀘스트
    function checkLordPower($_lord_pk, $_ret): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'POWER') {
                if ($v['condition_1'] <= $_ret['power']) {
                    $this->completeQuest($_lord_pk, $k);
                }
            }
        }
    }

    // 영웅관련 퀘스트
    function checkHero($_lord_pk, $_ret): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        // TODO ??
        if ($_ret['hero_type'] == 'enchant_suc_8') {
            //$this->completeQuest($_lord_pk, 603321);
        }

        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'HERO') {
                if ($v['condition_1'] == $_ret['hero_type']) {
                    $this->completeQuest($_lord_pk, $k);
                    if ($_ret['hero_type'] == 'appoint' || $_ret['hero_type'] == 'enchant') {
                        $this->checkSecureHeroAppoint($_lord_pk, $_ret);
                    }
                }
            }
        }
    }

    // 영웅관련 배속 퀘스트
    function checkSecureHeroAppoint($_lord_pk, $_ret): void
    {
        // 영웅 능력, 레어도, 레벨에 따른 퀘스트
        $hero_info = null;
        $this->PgGame->query('SELECT level, rare_type FROM hero WHERE hero_pk = $1', [$_ret['hero_pk']]);
        if ($this->PgGame->fetch()) {
            $hero_info = $this->PgGame->row;
        }

        $hero_stat = null;
        $this->PgGame->query('SELECT leadership, mil_force, intellect, politics, charm FROM my_hero WHERE hero_pk = $1', [$_ret['hero_pk']]);
        if ($this->PgGame->fetch()) {
            $hero_stat = $this->PgGame->row;
        }

        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'HERO_APPOINT') {
                if (isset($hero_info[$v['condition_1']])) {
                    if ($hero_info[$v['condition_1']] == $v['condition_2']) {
                        $this->completeQuest($_lord_pk, $k);
                    }
                }

                if (isset($hero_stat[$v['condition_1']])) {
                    if ($hero_stat[$v['condition_1']] >= $v['condition_2']) {
                        $this->completeQuest($_lord_pk, $k);
                    }
                }
            }
        }
    }

    // 훈련 퀘스트
    function checkArmyRecruit($_lord_pk, $_ret): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'ARMY_RECRUIT') {
                $result = false;
                // 5개의 조건중에 해당 아이템이 존재하는가..
                for ($i = 0; $i < 5; $i++) {
                    if ($v['condition_' . ($i + 1)]) {
                        if (strstr($v['condition_' . ($i + 1)], $_ret['army_code'])) {
                            $result = true;
                        }
                    }
                }

                if ($result) {
                    $z = '';

                    if ($v['condition_1']) {
                        $army = explode(':', $v['condition_1']);
                        $z = $z. ' AND ' . $army[0] . ' >= ' . $army[1];
                    }

                    if ($v['condition_2']) {
                        $army = explode(':', $v['condition_2']);
                        $z = $z. ' AND ' . $army[0] . ' >= ' . $army[1];
                    }

                    if ($v['condition_3']) {
                        $army = explode(':', $v['condition_3']);
                        $z = $z. ' AND ' . $army[0] . ' >= ' . $army[1];
                    }

                    if ($v['condition_4']) {
                        $army = explode(':', $v['condition_4']);
                        $z = $z. ' AND ' . $army[0] . ' >= ' . $army[1];
                    }

                    if ($v['condition_5']) {
                        $army = explode(':', $v['condition_5']);
                        $z = $z. ' AND ' . $army[0] . ' >= ' . $army[1];
                    }

                    $this->PgGame->query("SELECT posi_pk FROM army WHERE posi_pk = $1 {$z}", [$_ret['posi_pk']]);
                    if($this->PgGame->fetch()) {
                        $this->completeQuest($_lord_pk, $k);
                    }
                }
            }
        }

        $this->ret = $_ret;
    }

    // 방어시설 설치 퀘스트
    function checkFortification($_lord_pk, $_ret): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'BUIL_FORTIFICATION') {
                $result = false;
                // 5개의 조건중에 해당 아이템이 존재하는가..
                for ($i = 0; $i < 5; $i++) {
                    if ($v['condition_' . ($i + 1)]) {
                        if (strstr($v['condition_' . ($i + 1)], $_ret['fort_code'])) {
                            $result = true;
                        }
                    }
                }

                if ($result) {
                    $z = '';

                    if ($v['condition_1']) {
                        $fortification = explode(':', $v['condition_1']);
                        $z = $z . ' AND ' . $fortification[0] . ' >= ' . $fortification[1];
                    }

                    if ($v['condition_2']) {
                        $fortification = explode(':', $v['condition_2']);
                        $z = $z . ' AND ' . $fortification[0] . ' >= ' . $fortification[1];
                    }

                    if ($v['condition_3']) {
                        $fortification = explode(':', $v['condition_3']);
                        $z = $z . ' AND ' . $fortification[0] . ' >= ' . $fortification[1];
                    }

                    if ($v['condition_4']) {
                        $fortification = explode(':', $v['condition_4']);
                        $z = $z . ' AND ' . $fortification[0] . ' >= ' . $fortification[1];
                    }

                    if ($v['condition_5']) {
                        $fortification = explode(':', $v['condition_5']);
                        $z = $z . ' AND ' . $fortification[0] . ' >= ' . $fortification[1];
                    }

                    $this->PgGame->query("SELECT posi_pk FROM fortification WHERE posi_pk = $1 {$z}", [$_ret['posi_pk']]);
                    if($this->PgGame->fetch()) {
                        $this->completeQuest($_lord_pk, $k);
                    }
                }
            }
        }

        $this->ret = $_ret;
    }

    // 인구, 자원 증가 퀘스트
    function checkTerritoryResource($_lord_pk, $_ret): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'TERRITORY') {
                if ($v['condition_1'] == 'population') {
                    $this->PgGame->query('SELECT population_curr FROM territory WHERE posi_pk = $1', [$_ret['posi_pk']]);
                } else {
                    $this->PgGame->query("SELECT {$v['condition_1']}_production FROM resource WHERE posi_pk = $1", [$_ret['posi_pk']]);
                }
                $curr_value = $this->PgGame->fetchOne();

                if ($curr_value >= (INT)$v['condition_2']) {
                    $this->completeQuest($_lord_pk, $k);
                }
            } else if ($v['goal_type'] == 'TERRITORY_SEVERAL') {
                $this->PgGame->query('SELECT food_production, horse_production, lumber_production FROM resource WHERE posi_pk = $1', [$_ret['posi_pk']]);
                $this->PgGame->fetch();
                if ($this->PgGame->row['food_production'] >= (INT)$v['condition_1'] && $this->PgGame->row['horse_production'] >= (INT)$v['condition_1'] && $this->PgGame->row['lumber_production'] >= (INT)$v['condition_1']) {
                    $this->completeQuest($_lord_pk, $k);
                }
            }
        }
    }

    // 소지하고 있는 아이템 관련 퀘스트
    function checkGiveItem($_lord_pk, $_ret = null): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        $this->classItem();
        $result = true;
        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'GIVE_ITEM') {
                // 완료한것은 검사 안함.
                $this->PgGame->query('SELECT m_ques_pk FROM my_quest WHERE lord_pk = $1 AND m_ques_pk = $2 AND status = $3 AND reward_status = $4', [$_lord_pk, $k, 'C', 'Y']);
                $m_ques_pk = $this->PgGame->fetchOne();

                if (!$m_ques_pk) {
                    if ($v['condition_1']) {
                        $item = explode(':', $v['condition_1']);
                        $item_cnt = $this->Item->getItemCount($_lord_pk, $item[0]);
                        if ($item[1] > $item_cnt) {
                            $result = false;
                        }
                    }

                    if ($v['condition_2'] && $result) {
                        $item = explode(':', $v['condition_2']);
                        $item_cnt = $this->Item->getItemCount($_lord_pk, $item[0]);
                        if ($item[1] > $item_cnt) {
                            $result = false;
                        }
                    }

                    if ($v['condition_3'] && $result) {
                        $item = explode(':', $v['condition_3']);
                        $item_cnt = $this->Item->getItemCount($_lord_pk, $item[0]);
                        if ($item[1] > $item_cnt) {
                            $result = false;
                        }
                    }

                    if ($v['condition_4'] && $result) {
                        $item = explode(':', $v['condition_4']);
                        $item_cnt = $this->Item->getItemCount($_lord_pk, $item[0]);
                        if ($item[1] > $item_cnt) {
                            $result = false;
                        }
                    }

                    if ($v['condition_5'] && $result) {
                        $item = explode(':', $v['condition_5']);
                        $item_cnt = $this->Item->getItemCount($_lord_pk, $item[0]);
                        if ($item[1] > $item_cnt) {
                            $result = false;
                        }
                    }

                    if ($result) {
                        $this->completeQuest($_lord_pk, $k);
                        $this->ret = $_ret;
                    } else {
                        $this->unCompleteQuest($_lord_pk, $k);
                    }
                }
            }
        }
    }

    // 아이템 교환 퀘스트
    function checkExchangeItem($_lord_pk, $_ret = null): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        $result = true;
        $this->classItem();
        foreach ($_M['QUES'] AS $k => $v) {
            if ($v['goal_type'] == 'EXCHANGE_ITEM') {
                // 완료한것은 검사 안함.
                $query_param = [$_lord_pk, $k, 'C', 'Y'];
                $this->PgGame->query('SELECT m_ques_pk FROM my_quest WHERE lord_pk = $1 AND m_ques_pk = $2 AND status = $3 AND reward_status = $4', $query_param);
                $m_ques_pk = $this->PgGame->fetchOne();

                if (!$m_ques_pk) {
                    if ($v['condition_1']) {
                        $item = explode(':', $v['condition_1']);
                        $item_cnt = $this->Item->getItemCount($_lord_pk, $item[0]);
                        if ($item[1] > $item_cnt) {
                            $result = false;
                        }
                    }

                    if ($v['condition_2'] && $result) {
                        $item = explode(':', $v['condition_2']);
                        $item_cnt = $this->Item->getItemCount($_lord_pk, $item[0]);
                        if ($item[1] > $item_cnt) {
                            $result = false;
                        }
                    }

                    if ($v['condition_3'] && $result) {
                        $item = explode(':', $v['condition_3']);
                        $item_cnt = $this->Item->getItemCount($_lord_pk, $item[0]);
                        if ($item[1] > $item_cnt) {
                            $result = false;
                        }
                    }

                    if ($v['condition_4'] && $result) {
                        $item = explode(':', $v['condition_4']);
                        $item_cnt = $this->Item->getItemCount($_lord_pk, $item[0]);
                        if ($item[1] > $item_cnt) {
                            $result = false;
                        }
                    }

                    if ($v['condition_5'] && $result) {
                        $item = explode(':', $v['condition_5']);
                        $item_cnt = $this->Item->getItemCount($_lord_pk, $item[0]);
                        if ($item[1] > $item_cnt) {
                            $result = false;
                        }
                    }

                    if ($result) {
                        $this->completeQuest($_lord_pk, $k);
                        $this->ret = $_ret;
                    }
                }
            }
        }
    }

    // 제작 퀘스트
    function checkMakingItem($_lord_pk, $_ret): bool
    {
        global $_M;

        $this->PgGame->query('SELECT status FROM my_quest WHERE lord_pk = $1 AND m_ques_pk = $2', [$_lord_pk, $_ret['m_ques_pk']]);
        $status = $this->PgGame->fetchOne();

        if ($status == 'P') {
            if ($_M['QUES'][$_ret['m_ques_pk']]['goal_type'] == 'MAKING') {
                $this->classGoldPop();
                $r = $this->GoldPop->get($_ret['posi_pk'], $_lord_pk);
                $gold_curr = $r['gold_curr'];

                if ($_M['QUES'][$_ret['m_ques_pk']]['condition_1'] > $gold_curr) {
                    return false;
                }

                $this->PgGame->query("SELECT food_curr, horse_curr, lumber_curr, iron_curr FROM GetResourceDetail('{$_ret['posi_pk']}')");
                $this->PgGame->fetch();
                $r = $this->PgGame->row;
                if ($r['food_curr'] < $_M['QUES'][$_ret['m_ques_pk']]['condition_2'] && $r['horse_curr'] < $_M['QUES'][$_ret['m_ques_pk']]['condition_3'] && $r['lumber_curr'] < $_M['QUES'][$_ret['m_ques_pk']]['condition_4'] && $r['iron_curr'] < $_M['QUES'][$_ret['m_ques_pk']]['condition_5']) {
                    return false;
                }

                $this->completeQuest($_lord_pk, $_ret['m_ques_pk']);
            } else if ($_M['QUES'][$_ret['m_ques_pk']]['goal_type'] == 'MAKING_ITEM') {
                $this->classItem();
                $item = explode(':', $_M['QUES'][$_ret['m_ques_pk']]['condition_1']);

                $item_cnt = $this->Item->getItemCount($_lord_pk, $item[0]);
                if ($item[1] > $item_cnt) {
                    return false;
                }

                $this->completeQuest($_lord_pk, $_ret['m_ques_pk']);
            } else if ($_M['QUES'][$_ret['m_ques_pk']]['goal_type'] == 'MAKING_COIN') {
                $this->classItem();
                $coin = $_M['QUES'][$_ret['m_ques_pk']]['condition_1'];

                $this->PgGame->query('SELECT point_coin FROM lord WHERE lord_pk = $1', [$_lord_pk]);
                $point_coin = $this->PgGame->fetchOne();

                if ($coin > $point_coin) {
                    return false;
                }

                $this->completeQuest($_lord_pk, $_ret['m_ques_pk']);
            } else if ($_M['QUES'][$_ret['m_ques_pk']]['goal_type'] == 'MAKING_ARMY') {
                $this->classItem();
                $army_arr = explode(':', $_M['QUES'][$_ret['m_ques_pk']]['condition_1']);

                $this->PgGame->query('SELECT ' .$army_arr[0] .' FROM army WHERE posi_pk = $1', [$_ret['posi_pk']]);
                if ($this->PgGame->fetchOne() < $army_arr[1]) {
                    return false;
                }

                $this->completeQuest($_lord_pk, $_ret['m_ques_pk']);
            }
        }

        $this->ret = $_ret;
        return true;
    }

    // 성문 열기 퀘스트
    function checkCastleGate($_lord_pk, $_ret): void
    {
        if($_ret['open'] == 'O') {
            $this->completeQuest($_lord_pk, 602102);
        } else if($_ret['open'] == 'C')	{
            $this->completeQuest($_lord_pk, 602103);
        }
    }

    // 무역 퀘스트
    function checkTradeDepartment($_lord_pk, $_ret): void
    {
        if ($_ret['type'] == 'sell') {
            $this->completeQuest($_lord_pk, 601702);
        } else if ($_ret['type'] == 'buy') {
            $this->completeQuest($_lord_pk, 601703);
        }
    }

    // 시장 퀘스트
    function checkMarket($_lord_pk, $_ret): void
    {
        if ($_ret['type'] == 'buy') {
            $this->completeQuest($_lord_pk, 601708);
        }
    }

    // 외교 퀘스트
    function checkAlliance($_lord_pk, $_ret): void
    {
        // TODO 일부 퀘스트 확인 필요.
        if ($_ret['type'] == 'letter') {
            $this->completeQuest($_lord_pk, 601704);
        } else if ($_ret['type'] == 'join') {
            // $this->completeQuest($_lord_pk, 601705);
        } else if ($_ret['type'] == 'create') {
            // $this->completeQuest($_lord_pk, 601707);
        } else if ($_ret['type'] == 'reinforce') {
            $this->completeQuest($_lord_pk, 601706);
        }
    }

    // 매직큐브 이용
    function checkUseMagicCube($_lord_pk, $_ret): void
    {
        if ($_ret['type'] == 'use') {
            $this->completeQuest($_lord_pk, 600909); // 일일 퀘스트로 변경 - 이전 600106
        }
    }

    // 군주명 변경 퀘스트
    function checkLordNameChange($_lord_pk, $_ret): void
    {
        $this->completeQuest($_lord_pk, 600709);
    }

    function unCompleteQuest ($_lord_pk, $_m_ques_pk): bool
    {
        if (!$_lord_pk) {
            return false;
        }
        $this->PgGame->query('SELECT my_ques_pk, status FROM my_quest WHERE lord_pk = $1 AND m_ques_pk = $2', [$_lord_pk, $_m_ques_pk]);
        if($this->PgGame->fetch()) {
            if ($this->PgGame->row['status'] == 'C') {
                // 퀘스트 조건 완료로 변경, 보상은 N로 변경
                $this->PgGame->query('UPDATE my_quest SET status = $1, reward_status = $2, last_up_dt = now() WHERE lord_pk = $3 AND m_ques_pk = $4', ['P', 'N', $_lord_pk, $_m_ques_pk]);

                $this->Session->sqAppend('PUSH', ['QUEST_PROGRESS' => $_m_ques_pk], null, $_lord_pk);

                $this->setChanged($_lord_pk);
            }
        }
        return true;
    }

    // 퀘스트 완료
    function completeQuest($_lord_pk, $_m_ques_pk): bool
    {
        if (!$_lord_pk) {
            return false;
        }
        $this->PgGame->query('SELECT my_ques_pk, status FROM my_quest WHERE lord_pk = $1 AND m_ques_pk = $2', [$_lord_pk, $_m_ques_pk]);
        if(!$this->PgGame->fetch()) {
            //퀘스트 insert 해줌.
            if ($_m_ques_pk) {
                $this->PgGame->query('INSERT INTO my_quest (lord_pk, m_ques_pk, status, reward_status, start_dt, last_up_dt, invisible) VALUES ($1, $2, $3, $4, now(), now(), $5)', [$_lord_pk, $_m_ques_pk, 'C', 'N', 'Y']);
            }
        } else {
            if ($this->PgGame->row['status'] == 'P') {
                // 퀘스트 조건 완료로 변경, 보상은 N로 변경
                $this->PgGame->query('UPDATE my_quest SET status = $1, reward_status = $2, last_up_dt = now() WHERE lord_pk = $3 AND m_ques_pk = $4', ['C', 'N', $_lord_pk, $_m_ques_pk]);

                $this->Session->sqAppend('PUSH', ['QUEST_COMPLETE' => $_m_ques_pk], null, $_lord_pk);

                $this->setChanged($_lord_pk);
            }
        }
        return true;
    }

    // 퀘스트 완료인 경우 취소가 필요할 때 사용
    function progressQuest($_lord_pk, $_m_ques_pk): bool
    {
        if (!$_lord_pk) {
            return false;
        }
        $this->PgGame->query('SELECT my_ques_pk, status FROM my_quest WHERE lord_pk = $1 AND m_ques_pk = $2', [$_lord_pk, $_m_ques_pk]);
        if(!$this->PgGame->fetch()) {
            $this->PgGame->query('INSERT INTO my_quest (lord_pk, m_ques_pk, status, reward_status, start_dt, last_up_dt, invisible) VALUES ($1, $2, $3, $4, now(), now(), $5)', [$_lord_pk, $_m_ques_pk, 'C', 'N', 'Y']);
        } else {
            if ($this->PgGame->row['status'] == 'C') {
                // 퀘스트 조건 완료로 변경, 보상은 N로 변경
                $this->PgGame->query('UPDATE my_quest SET status = $1, reward_status = $2, last_up_dt = now() WHERE lord_pk = $3 AND m_ques_pk = $4', ['P', 'N', $_lord_pk, $_m_ques_pk]);

                $this->Session->sqAppend('PUSH', ['QUEST_PROGRESS' => $_m_ques_pk], null, $_lord_pk);

                $this->setChanged($_lord_pk);
            }
        }
        return true;
    }

    // 퀘스트 보상
    function rewardQuest($_lord_pk, $_posi_pk, $_m_ques_pk): bool
    {
        global $_M, $_M_ARMY_C, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['army', 'quest', 'package']);

        // 자원 생산량 퀘스트인 경우 보상 전 한번 더 확인 진행.
        if ($_M['QUES'][$_m_ques_pk]['goal_type'] == 'TERRITORY_SEVERAL') {
            $this->conditionCheckQuest($_lord_pk, ['quest_type' => 'territory', 'posi_pk' => $_posi_pk]);
        }

        //보상 받은 후 수행 가능한 퀘스트 update(해당 퀘스트 precondition 만족하는 퀘스트 추가)
        if ($_M['QUES'][$_m_ques_pk]['type'] != 'friend') {
            $this->PgGame->query('SELECT status, reward_status FROM my_quest WHERE lord_pk = $1 AND m_ques_pk = $2', [$_lord_pk, $_m_ques_pk]);
            $this->PgGame->fetch();
            $row = $this->PgGame->row;
        } else {
            // 통합 DB 퀘스트 개수 체크
            /*$this->PgGame->query('SELECT web_id FROM lord_web WHERE lord_pk = $1', [$_lord_pk]);
            $acco_pk = $this->PgGame->fetchOne();

            $this->classPgCommon();
            $this->PgCommon->query('SELECT status, reward_status FROM my_quest WHERE acco_pk = $1 AND m_ques_pk = $2', [$acco_pk, $_m_ques_pk]);
            $this->PgCommon->fetch();
            $row = $this->PgCommon->row;*/
        }

        if (!$row) {
            $NsGlobal->setErrorMessage($i18n->t('msg_not_progress_quest')); // 진행 중인 퀘스트가 아닙니다.
            return false;
        }

        // 퀘스트 미완료
        if ($row['status'] == 'P') {
            $NsGlobal->setErrorMessage($i18n->t('msg_progress_quest')); // 퀘스트가 진행 중 입니다.
            return false;
        }

        // 이미 보상 받음
        if ($row['reward_status'] == 'Y') {
            $NsGlobal->setErrorMessage($i18n->t('msg_already_received_quest')); // 이미 보상 받은 퀘스트입니다.
            return false;
        }

        // making에 필요한 재료 소비하기
        if ($_M['QUES'][$_m_ques_pk]['type'] == 'making') {
            $ret = false;
            if($_M['QUES'][$_m_ques_pk]['goal_type'] == 'MAKING') {
                $ret = $this->spendMaterialMaking($_posi_pk, $_m_ques_pk);
            } else if($_M['QUES'][$_m_ques_pk]['goal_type'] == 'MAKING_ITEM') {
                $item_info = explode(':', $_M['QUES'][$_m_ques_pk]['condition_1']);
                $this->classItem();
                $item_curr_cnt = $this->Item->getItemCount($_lord_pk, $item_info[0]);
                if ($item_info[1] > $item_curr_cnt) {
                    $NsGlobal->setErrorMessage($i18n->t('msg_not_enough_item_require')); // 필요 아이템 개수가 부족합니다.
                    return false;
                }
                $ret = $this->decreaseItem($_posi_pk, $_lord_pk, $_m_ques_pk);
            } else if ($_M['QUES'][$_m_ques_pk]['goal_type'] == 'MAKING_COIN') {
                $ret = $this->decreaseCoin($_posi_pk, $_lord_pk, $_m_ques_pk);
            } else if ($_M['QUES'][$_m_ques_pk]['goal_type'] == 'MAKING_ARMY') {
                $ret = $this->decreaseArmy($_posi_pk, $_lord_pk, $_m_ques_pk);
            }

            if (! $ret) {
                $this->PgGame->query('UPDATE my_quest SET status = \'P\', reward_status = \'N\', last_up_dt = now() WHERE lord_pk = $1 AND m_ques_pk = $2', [$_lord_pk, $_m_ques_pk]);

                // debug_mesg('D', __CLASS__, __FUNCTION__, __LINE__, 'rewardQuest making my_quest reward_status update;time['. ($end_time - $start_time) .'];');
                $this->Session->sqAppend('QUES', [$_m_ques_pk => ['status' => 'P', 'reward_status' => 'N']]);

                return false;
            }
        }

        // 보상
        if ($_M['QUES'][$_m_ques_pk]['lord_upgrade']) {
            for ($i = 1; $i <= 5; $i++) {
                if ($_M['QUES'][$_m_ques_pk]['condition_' . $i]) {
                    $item_info = explode(':', $_M['QUES'][$_m_ques_pk]['condition_' . $i]);
                    $this->classItem();
                    $ret = $this->Item->getItemCount($_lord_pk, $item_info[0]);
                    if ($item_info[1] > $ret) {
                        $NsGlobal->setErrorMessage($i18n->t('msg_not_enough_item_require')); // 필요 아이템 개수가 부족합니다.
                        return false;
                    }
                }
            }
            // 아이템 차감
            $ret = $this->decreaseItem($_posi_pk, $_lord_pk, $_m_ques_pk);
            if (!$ret) {
                return false;
            }

            $lord_level = $_M['QUES'][$_m_ques_pk]['lord_upgrade'];
            $this->PgGame->query('UPDATE lord SET level = $2 WHERE lord_pk = $1', [$_lord_pk, $lord_level]);

            // reload
            $this->Session->setLoginReload();
        }

        if ($_M['QUES'][$_m_ques_pk]['power']) {
            $this->classLord();
            $this->Lord->increasePower($_lord_pk, $_M['QUES'][$_m_ques_pk]['power']);
        }

        // TODO 이거 없는 함수인데?
        /*if ($_M['QUES'][$_m_ques_pk]['fame']) {
            $this->classLord();
            $this->Lord->increaseFame($_lord_pk, $_M['QUES'][$_m_ques_pk]['fame']);
        }*/

        if ($_M['QUES'][$_m_ques_pk]['item']) {
            $reward_item = $_M['QUES'][$_m_ques_pk]['item'];
            $item = explode(',', $reward_item);

            for ($i = 0; $i < COUNT($item); $i++) {
                $item_info = explode(':', $item[$i]);

                $this->classItem();
                if ($_m_ques_pk == 600108) {
                    // 600108 동맹군주 선물 매일 퀘스트로 인해 봉활의 구슬 퀘스트가 클리어 되면 안되므로
                    $this->Item->BuyItem($_lord_pk, $item_info[0], $item_info[1], 'alli_gift_quest_reward');
                } else {
                    $this->Item->BuyItem($_lord_pk, $item_info[0], $item_info[1], 'quest_reward');
                }
            }
        }

        // 나이트워치 프로모션 쿠폰 추가
        /*if ($_m_ques_pk == 600604)
        {
            $query_params = Array($_lord_pk);
            $this->PgGame->query('SELECT platform FROM lord WHERE lord_pk = $1', $query_params);
            $platform = $this->PgGame->fetchOne();

            if ($platform == 'NB' || $platform == 'PB' || $platform == 'AB')
            {
                // 대전 레벨 2 달성 퀘스트 클리어시 쿠폰 아이템 추가 지급.
                $this->getItemClass();
                $this->Item->BuyItem($_lord_pk, 500747, 1, 'quest_reward');
            }
        }*/

        if ($_M['QUES'][$_m_ques_pk]['population']) {
            $this->classGoldPop();
            $r = $this->GoldPop->increasePopulation($_posi_pk, $_M['QUES'][$_m_ques_pk]['population']);
            if (!$r) {
                $NsGlobal->setErrorMessage('Error Occurred. [23001]'); // 인구 증가 실패
                return false;
            }
        }

        $res = [];

        if ($_M['QUES'][$_m_ques_pk]['food']) {
            $res['food'] = $_M['QUES'][$_m_ques_pk]['food'];
        }

        if ($_M['QUES'][$_m_ques_pk]['horse']) {
            $res['horse'] = $_M['QUES'][$_m_ques_pk]['horse'];
        }

        if ($_M['QUES'][$_m_ques_pk]['lumber']) {
            $res['lumber'] = $_M['QUES'][$_m_ques_pk]['lumber'];
        }

        if ($_M['QUES'][$_m_ques_pk]['iron']) {
            $res['iron'] = $_M['QUES'][$_m_ques_pk]['iron'];
        }

        if (count($res) > 0) {
            $this->classResource();
            $r = $this->Resource->increase($_posi_pk, $res, null, 'quest_reward');
            if (!$r) {
                $NsGlobal->setErrorMessage('Error Occurred. [23002]'); // 자원 증가 실패
                return false;
            }
        }

        if ($_M['QUES'][$_m_ques_pk]['gold']) {
            $this->classGoldPop();
            $r = $this->GoldPop->increaseGold($_posi_pk, $_M['QUES'][$_m_ques_pk]['gold'], null, 'quest_reward');
            if (!$r) {
                $NsGlobal->setErrorMessage('Error Occurred.  [23003]');
                return false;
            }
        }

        if ($_M['QUES'][$_m_ques_pk]['army']) {
            $this->classArmy();

            $reward_army = $_M['QUES'][$_m_ques_pk]['army'];

            $army = explode(',', $reward_army);
            $get_army = [];
            $log_description = "";
            $army_cnt = 0;
            $z = '';

            for($i = 0; $i < COUNT($army); $i++) {
                $army_info = explode(':', $army[$i]);

                if ($i == 0) {
                    $z = $army_info[0] . ' = '. $army_info[0] . '+ ' . $army_info[1];
                } else {
                    $z = $z . ',' . $army_info[0] . ' = '. $army_info[0] . ' + ' . $army_info[1];
                }

                $get_army[$i] = $army_info[0];
                $log_description.= "{$_M_ARMY_C[$army_info[0]]['m_army_pk']}[$army_info[1]];";

                $army_cnt += (int)$army_info[1];
            }

            if ($this->Army->getPositionArmy($_posi_pk) + $army_cnt > TROOP_ARMY_LIMIT) {
                $NsGlobal->setErrorMessage($i18n->t('msg_territory_army_limit', [TROOP_ARMY_LIMIT])); // '영지당 보유할 수 있는 총 병력수 {{1}}이 초과되어 보상 받을 수 없습니다.'
                return false;
            }

            if (! isset($z)) {
                return false;
            }
            $this->PgGame->query('UPDATE army SET '. $z . ' WHERE posi_pk = $1', [$_posi_pk]);

            // 로그
            $this->classLog();
            $this->Log->setArmy(null, $_posi_pk, 'quest_army_reward', $log_description);

            $this->Army->get($_posi_pk, $get_army);
        }

        if ($_M['QUES'][$_m_ques_pk]['fortification']) {
            $reward_fortification = $_M['QUES'][$_m_ques_pk]['fortification'];

            $fortification = explode(':', $reward_fortification);

            $this->PgGame->query('UPDATE fortification SET '. $fortification[0] . ' = '. $fortification[0] . '+ ' . $fortification[1] . ' WHERE posi_pk = $1', [$_posi_pk]);

            // 로그
            $this->classLog();
            $this->Log->setArmy(null, $_posi_pk, 'quest_fort_reward', $fortification[0] . '[' . $fortification[1] . '];');

            $this->classFortification();
            $this->Fortification->get($_posi_pk, [$fortification[0]]);
        }

        // 보상이 습격부대일 경우 처리
        if ($_M['QUES'][$_m_ques_pk]['attack_npc']) {
            $this->classTroop();
            $this->Troop->marchNpcTroop($_M['QUES'][$_m_ques_pk]['attack_npc'], $_lord_pk);
        }

        $next_m_ques_pk = null;
        $buff_pk = null;
        // 골 타입이 DAILY_DISPATCH일 경우 처리
        if ($_M['QUES'][$_m_ques_pk]['goal_type'] == 'DAILY_DISPATCH') {
            $npc_troo_cnt = 0;

            if ($_m_ques_pk == 608001) {
                $npc_troo_cnt = 1;
                $next_m_ques_pk = 608002;
                $buff_pk = 500505; // 훈련 단축
            } else if ($_m_ques_pk == 608002) {
                $npc_troo_cnt = 2;
                $next_m_ques_pk = 608003;
                $buff_pk = 500506; // 자원 증가
            } else if ($_m_ques_pk == 608003) {
                $npc_troo_cnt = 3;
                $next_m_ques_pk = 608004;
                $buff_pk = 500507; // 사망자 치료
            }

            if ($npc_troo_cnt > 0) {
                $this->classTroop();
                $_npc_troop_array = [];
                for ($i = 0; $i < $npc_troo_cnt; $i++) {
                    if ($i != 0) {
                        $_npc_troop_array['move_time'] = 60 * (5 * $i);
                    } else {
                        $_npc_troop_array['move_time'] = 60;
                    }
                    $_npc_troop_array['m_ques_pk'] = $next_m_ques_pk;
                    $_npc_troop_array['yn_reward'] = 'N';
                    $_npc_troop_array['buff_pk'] = $buff_pk;
                    if (($i+1) == $npc_troo_cnt) {
                        $_npc_troop_array['yn_reward'] = 'Y';
                    }
                    $this->Troop->marchNpcTroop($this->Session->lord['level'], $_lord_pk, 'dispatch', $_npc_troop_array);
                }
            }
        }

        if (! $this->rewardStatusChange($_lord_pk, $_m_ques_pk)) {
            return false;
        }
        $this->addQuest($_lord_pk, $_m_ques_pk);

        // 퀘스트 완료 가능한 조건인지 다시 한번 검사
        $this->conditionCheckQuest($_lord_pk, $this->ret);
        if ($_M['QUES'][$_m_ques_pk]['lord_upgrade']) {
            $this->checkGiveItem($_lord_pk);
        }

        // 퀘스트 목록 업데이트
        /*if (!($_M['QUES'][$_m_ques_pk]['type'] == 'making' && substr($_m_ques_pk, 4) == '10'))
            $this->Session->sqAppend('QUES', Array($_m_ques_pk => Array('reward_status' => 'Y')));*/

        $this->setChanged($_lord_pk);
        return true;
    }

    // making에 필요한 자원 차감
    function spendMaterialMaking($_posi_pk, $_m_ques_pk): bool
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['quest']);

        $this->classGoldPop();
        $r = $this->GoldPop->decreaseGold($_posi_pk, $_M['QUES'][$_m_ques_pk]['condition_1'], null, 'quest_making');
        if (!$r) {
            $NsGlobal->setErrorMessage($i18n->t('msg_resource_gold_lack')); // 제조에 필요한 황금이 부족합니다.
            return false;
        }
        $res = [
            'food' => $_M['QUES'][$_m_ques_pk]['condition_2'],
            'horse' => $_M['QUES'][$_m_ques_pk]['condition_3'],
            'lumber' => $_M['QUES'][$_m_ques_pk]['condition_4'],
            'iron' =>$_M['QUES'][$_m_ques_pk]['condition_5']
        ];
        $this->classResource();
        $r = $this->Resource->decrease($_posi_pk, $res, null, 'quest_making');
        if (!$r) {
            $NsGlobal->setErrorMessage($i18n->t('msg_resource_lack')); // 제조에 필요한 자원이 부족합니다.
            return false;
        }
        return true;
    }

    // 퀘스트 상태 변경하기.
    function rewardStatusChange($_lord_pk, $_m_ques_pk): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        // daily는 예외 임.
        if ($_M['QUES'][$_m_ques_pk]['type'] == 'daily') {
            //하루가 지났을 경우엔 보상 못받음.
            $this->PgGame->query('SELECT date_part(\'epoch\', now() + INTERVAL \'86400\')::integer');
            $today = strtotime(date('Y-m-d 00:00:01', $this->PgGame->fetchOne()));
            $this->PgGame->query('SELECT date_part(\'epoch\', last_up_dt)::integer AS last_up_dt FROM my_quest WHERE lord_pk = $1 and m_ques_pk = $2', [$_lord_pk, $_m_ques_pk]);
            $last_up_dt = $this->PgGame->fetchOne();

            if ($today < $last_up_dt) {
                $this->Session->sqAppend('QUES', [$_m_ques_pk => ['status' => 'P']]);
                $NsGlobal->setErrorMessage(''); // TODO 문구 추가 필요
                return false;
            }
            $this->PgGame->query('UPDATE my_quest SET status = $4, reward_status = $1, last_up_dt = now(), invisible = \'Y\' WHERE lord_pk = $2 AND m_ques_pk = $3', ['N', $_lord_pk, $_m_ques_pk, 'P']);
        } else {
            if ($_M['QUES'][$_m_ques_pk]['type'] == 'making' && $_M['QUES'][$_m_ques_pk]['yn_repeat'] == 'Y') {
                $this->PgGame->query('UPDATE my_quest SET status = \'P\', reward_status = \'N\', start_dt = now(), last_up_dt = now() WHERE lord_pk = $1 AND m_ques_pk = $2', [$_lord_pk, $_m_ques_pk]);
            } else {
                if ($_M['QUES'][$_m_ques_pk]['type'] != 'friend') {
                    $this->PgGame->query('UPDATE my_quest SET reward_status = \'Y\', last_up_dt = now(), invisible = \'Y\' WHERE lord_pk = $1 AND m_ques_pk = $2', [$_lord_pk, $_m_ques_pk]);
                } else {
                    /*$this->PgGame->query('SELECT web_id FROM lord_web WHERE lord_pk = $1', [$_lord_pk]);
                    $acco_pk = $this->PgGame->fetchOne();

                    $this->classPgCommon();

                    $this->PgCommon->query('UPDATE my_quest SET reward_status = \'Y\', last_up_dt = now(), invisible = \'Y\' WHERE acco_pk = $1 AND m_ques_pk = $2', [$acco_pk, $_m_ques_pk]);*/
                }
            }
        }
        return true;
    }

    // 퀘스트 추가하기
    function addQuest($_lord_pk, $_m_ques_pk): true
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        // 다음 퀘스트 추가해 주기(현재 퀘스트가 Precondition인것 찾아 insert 해줌
        foreach($_M['QUES'] AS $v) {
            if ($v['sub_precondition'] == $_m_ques_pk) {
                if ($v['type'] != 'friend') {
                    $this->PgGame->query('SELECT my_ques_pk FROM my_quest WHERE lord_pk = $1 AND m_ques_pk = $2', [$_lord_pk, $v['m_ques_pk']]);
                    if ($this->PgGame->fetchOne()) {
                        $this->PgGame->query('UPDATE my_quest SET invisible = $3 WHERE lord_pk = $1 AND m_ques_pk = $2 RETURNING  m_ques_pk, status, reward_status, condition_value', [$_lord_pk, $v['m_ques_pk'], 'N']);
                        $this->PgGame->fetch();
                        $this->Session->sqAppend('QUES', [$v['m_ques_pk'] => $this->PgGame->row], null, $_lord_pk);
                    } else {
                        if ($v['m_ques_pk']) {
                            $this->PgGame->query('INSERT INTO my_quest (lord_pk, m_ques_pk, status, reward_status, start_dt, last_up_dt) VALUES ($1, $2, $3, $4, now(), now()) RETURNING  m_ques_pk, status, reward_status, condition_value', [$_lord_pk, $v['m_ques_pk'], 'P', 'N']);
                            $this->PgGame->fetch();
                            $this->Session->sqAppend('QUES', [$v['m_ques_pk'] => $this->PgGame->row], null, $_lord_pk);
                        }
                    }
                } else {
                    /*$this->PgGame->query('SELECT web_id FROM lord_web WHERE lord_pk = $1', [$_lord_pk]);
                    $acco_pk = $this->PgGame->fetchOne();

                    $this->classPgCommon();
                    $this->PgCommon->query('SELECT acco_pk FROM my_quest WHERE acco_pk = $1 AND m_ques_pk = $2', [$acco_pk, $v['m_ques_pk']]);
                    if ($this->PgCommon->fetchOne()) {
                        $this->PgCommon->query('UPDATE my_quest SET invisible = $3 WHERE acco_pk = $1 AND m_ques_pk = $2', [$acco_pk, $v['m_ques_pk'], 'N']);
                    } else {
                        if ($v['m_ques_pk']) {
                            $this->PgCommon->query('INSERT INTO my_quest (acco_pk, m_ques_pk, status, reward_status, start_dt, last_up_dt) VALUES ($1, $2, $3, $4, now(), now())', [$acco_pk, $v['m_ques_pk'], 'P', 'N']);
                        }
                    }*/
                }
            }
        }
        return true;
    }

    // 보상수령 가능한 퀘스트 계산
    function setChanged($_lord_pk): void
    {
        $this->PgGame->query('SELECT COUNT(my_ques_pk) FROM my_quest WHERE lord_pk = $1 AND m_ques_pk NOT IN (SELECT m_ques_pk FROM m_quest WHERE type = $2) AND status = $3 AND reward_status = $4 AND invisible = $5', [$_lord_pk, 'making', 'C', 'N', 'N']);
        $cnt = $this->PgGame->fetchOne();

        // $this->PgGame->query('SELECT web_id FROM lord_web WHERE lord_pk = $1', [$_lord_pk]);
        // $acco_pk = $this->PgGame->fetchOne();

        // $this->classPgCommon();

        // $this->PgCommon->query('SELECT COUNT(acco_pk) FROM my_quest WHERE acco_pk = $1 AND status = $2 AND reward_status = $3 AND invisible = $4', [$acco_pk, 'C', 'N', 'N']);
        // $cnt += $this->PgCommon->fetchOne();

        // unread cnt 처리용
        $this->PgGame->query('UPDATE lord SET unread_quest_cnt = $1, unread_quest_last_up_dt = now() WHERE lord_pk = $2', [$cnt, $_lord_pk]);

        // LP 입력
        $this->Session->sqAppend('LORD', ['unread_quest_cnt' => $cnt], null, $_lord_pk);
    }

    // 아이템 차감
    function decreaseItem($_posi_pk, $_lord_pk, $_m_ques_pk): bool|array
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['quest']);

        $ret = false;
        try {
            $this->PgGame->begin();
            global $_NS_SQ_REFRESH_FLAG;
            $_NS_SQ_REFRESH_FLAG = true;

            $this->classItem();
            for ($i = 1; $i <= 5; $i++) {
                if ($_M['QUES'][$_m_ques_pk]['condition_' . $i]) {
                    $item_info = explode(':', $_M['QUES'][$_m_ques_pk]['condition_' . $i]);

                    if ($item_info[0] > 0) {
                        $ret = $this->Item->useItem($_posi_pk, $_lord_pk, $item_info[0], $item_info[1], ['_yn_quest' => true]);
                        if (! $ret) {
                            throw new Exception($i18n->t('msg_not_enough_item_require')); // 필요 아이템 개수가 부족합니다.
                        }
                    }
                }
            }
            $this->PgGame->commit();
        } catch (Exception $e){
            // 실패, sq 무시
            $this->PgGame->rollback();

            //dubug_mesg남기기
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, 'posi_pk['.$_posi_pk.'];');

            return false;
        }

        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        return $ret;
    }

    // 코인차감
    function decreaseCoin($_posi_pk, $_lord_pk, $_m_ques_pk): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        $coin = $_M['QUES'][$_m_ques_pk]['condition_1'];

        $this->PgGame->query('SELECT point_coin FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $point_coin = $this->PgGame->fetchOne();

        if ($coin > $point_coin) {
            return false;
        }

        $r = $this->PgGame->query('UPDATE lord SET point_coin = point_coin - $2 WHERE lord_pk = $1', [$_lord_pk, $coin]);
        return !!$r;
    }

    // 추천 군주 체크 - TODO 코드가 영 시원찮은거 같다.
    function checkRecommend($_m_ques_pk, $_lord_pk): false|array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['item']);

        if ($_m_ques_pk == 600201) {
            $query_params = [$_lord_pk, 1];
        } else if ($_m_ques_pk == 600202) {
            $query_params = [$_lord_pk, 2];
        } else {
            return false;
        }
        // 먼저 지급 대상인지 체크
        $this->PgGame->query('SELECT reco_lord_pk FROM lord_recommend WHERE lord_pk = $1 AND level = $2', $query_params);
        $ret = $this->PgGame->fetchOne();

        if (!$ret) {
            return false;
        }

        // TODO 같은 쿼리인데;;
        $reco_lord_pk = null;
        $reco_serv_pk = null;
        if ($_m_ques_pk == 600201) {
            $this->PgGame->query('SELECT reco_lord_pk, reco_serv_pk FROM lord_recommend WHERE lord_pk = $1', [$_lord_pk]);
            $this->PgGame->fetch();
            $reco_lord_pk = $this->PgGame->row['reco_lord_pk'];
            $reco_serv_pk = $this->PgGame->row['reco_serv_pk'];
        } else if ($_m_ques_pk == 600202) {
            $this->PgGame->query('SELECT reco_lord_pk, reco_serv_pk FROM lord_recommend WHERE lord_pk = $1', [$_lord_pk]);
            $this->PgGame->fetch();
            $reco_lord_pk = $this->PgGame->row['reco_lord_pk'];
            $reco_serv_pk = $this->PgGame->row['reco_serv_pk'];
        }

        if (!$reco_lord_pk || !$reco_serv_pk) {
            // 존재하지 않는 다면 false;
            return false;
        }

        // 추천군주 서버선택 - TODO 이부분은 다시 확인 필요.
        $recommend_DB = null;
        if ($reco_serv_pk == 's1') {
            define('DEF1_PGSQL_IP', 's1gdb');
            //define('DEF1_PGSQL_IP', '192.168.1.160');
            define('DEF1_PGSQL_PORT', 5433);
            define('DEF1_PGSQL_DB', 'qbegame');
            define('DEF1_PGSQL_USER', 'qbe');
            define('DEF1_PGSQL_PASS', '');
            define('DEF1_PGSQL_PERSISTENT', false);

            $recommend_DB = new Pg('DEF1');
            $server_name = '낙양';
        } elseif ($reco_serv_pk == 's2') {
            define('DEF2_PGSQL_IP', 's2gdb'); // 192.168.1.161
            define('DEF2_PGSQL_PORT', 5433);
            define('DEF2_PGSQL_DB', 'qbegame');
            define('DEF2_PGSQL_USER', 'qbe');
            define('DEF2_PGSQL_PASS', '');
            define('DEF2_PGSQL_PERSISTENT', false);

            $recommend_DB = new Pg('DEF2');
            $server_name = '장안';
        } elseif ($reco_serv_pk == 's3') {
            define('DEF3_PGSQL_IP', 's3gdb');
            define('DEF3_PGSQL_PORT', 5433);
            define('DEF3_PGSQL_DB', 'qbegame');
            define('DEF3_PGSQL_USER', 'qbe');
            define('DEF3_PGSQL_PASS', '');
            define('DEF3_PGSQL_PERSISTENT', false);

            $recommend_DB = new Pg('DEF3');
            $server_name = '양양';
        }

        if ($recommend_DB) {
            $recommend_DB->query('SELECT t2.lord_name FROM lord_recommended t1, lord t2 WHERE t1.lord_pk = t2.lord_pk AND t1.reco_lord_pk = $1 AND t1.lord_pk = $2', [$_lord_pk, $reco_lord_pk]);
            $reco_lord_name = $recommend_DB->fetchOne();
        }

        $quest = $_M['QUES'][$_m_ques_pk];

        if ($quest['lord_upgrade'] == 2) {
            $m_item_pk = 500419; // 2등급 달성 상자
        } else if ($quest['lord_upgrade'] == 3) {
            $m_item_pk = 500420; // 3등급 달성 상자
        }

        if ($m_item_pk) {
            $item_title = $_M['ITEM'][$m_item_pk]['title'];
            $this->classItem();
            $ret = $this->Item->BuyItem($_lord_pk, $m_item_pk, 1, 'recommend_event');
        }

        if ($ret) {
            // 신규 군주 2등급 및 3등급 체크하여 추천 테이블을 업데이트 해줘야함.
            if ($_m_ques_pk == 600201) {
                $this->PgGame->query('UPDATE lord_recommend SET level = level + 1, level_up2_dt = now() WHERE lord_pk = $1', [$_lord_pk]);
                // 추천 군주 업데이트
                $recommend_DB->query('UPDATE lord_recommended SET level = 2 WHERE lord_pk = $1 AND reco_lord_pk = $2', [$reco_lord_pk, $_lord_pk]);
            } else if ($_m_ques_pk == 600202) {
                $this->PgGame->query('UPDATE lord_recommend SET level = level + 1, level_up3_dt = now() WHERE lord_pk = $1', [$_lord_pk]);
                // 추천 군주 업데이트
                $recommend_DB->query('UPDATE lord_recommended SET level = 3 WHERE lord_pk = $1 AND reco_lord_pk = $2', [$reco_lord_pk, $_lord_pk]);
            }

            return ['server_name' => $server_name ?? null, 'reco_lord_name' => $reco_lord_name, 'item_title' => $item_title, 'level' => $quest['lord_upgrade']];
        } else {
            return false;
        }
    }

    // 병력 차감
    function decreaseArmy($_posi_pk, $_lord_pk, $_m_ques_pk): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);

        $this->classArmy();

        $army_arr = explode(':', $_M['QUES'][$_m_ques_pk]['condition_1']);
        $army_arr = [$army_arr[0] => $army_arr[1]];

        $ret = $this->Army->useArmy($_posi_pk, $army_arr, 'making_quest');
        if (!$ret) {
            return false;
        }

        $this->Army->get($_posi_pk);

        return true;
    }

    // 신규 누적 퀘스트 추가 2024-11-07 송누리
    public function countCheckQuest ($_lord_pk, $_goal_type, $_data): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['quest']);
        try {
            $this->PgGame->query('SELECT my.m_ques_pk, my.condition_value FROM my_quest as my left join m_quest as m on my.m_ques_pk = m.m_ques_pk WHERE my.lord_pk = $1 AND m.goal_type = $2 AND my.status = $3', [$_lord_pk, $_goal_type, 'P']);
            $this->PgGame->fetchAll();
            $rows = $this->PgGame->rows;
            foreach ($rows as $row) {
                $condition_value = $row['condition_value'] + $_data['value'];
                if ($_M['QUES'][$row['m_ques_pk']]['condition_1'] <= $condition_value) {
                    $condition_value = $_M['QUES'][$row['m_ques_pk']]['condition_1'];
                    $this->PgGame->query("UPDATE my_quest SET status = $1, condition_value = $4 WHERE lord_pk = $5 AND m_ques_pk = $3 AND status = $2 RETURNING m_ques_pk, status, reward_status, condition_value", ['C', 'P', $row['m_ques_pk'], $condition_value, $_lord_pk]);
                } else {
                    $this->PgGame->query("UPDATE my_quest SET condition_value = $1 WHERE lord_pk = $4 AND m_ques_pk = $3 AND status = $2 RETURNING m_ques_pk, status, reward_status, condition_value", [$condition_value, 'P', $row['m_ques_pk'], $_lord_pk]);
                }
                $this->PgGame->fetch();
                $this->Session->sqAppend('QUES', [$row['m_ques_pk'] => $this->PgGame->row], null, $_lord_pk);
            }
        } catch (Throwable $e) {
            throw new ErrorHandler('error', $e->getMessage());
        }
    }
}