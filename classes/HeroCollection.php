<?php
// TODO 현재 컬렉션 기능 사용안함

class HeroCollection
{
    public Session $Session;
    public Pg $PgGame;
    protected Item $Item;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    function classItem(): void
    {
        if (! isset($this->Item)) {
            $this->Item = new Item($this->Session, $this->PgGame);
        }
    }

    function getHeroCollectionInfo($_lord_pk): array
    {
        $this->PgGame->query('SELECT m_hero_coll_pk, m_hero_pk, m_hero_base_pk FROM hero_collection WHERE lord_pk = $1 ORDER BY m_hero_coll_pk', [$_lord_pk]);
        $this->PgGame->fetchAll();
        $heroes = $this->PgGame->rows;

        $hero_collection = [];
        foreach($heroes AS $v) {
            $hero_collection[$v['m_hero_coll_pk']]['m_hero_pk'][] = $v['m_hero_pk'];
            $hero_collection[$v['m_hero_coll_pk']]['m_hero_base_pk'][] = $v['m_hero_base_pk'];
        }

        $this->PgGame->query('SELECT m_hero_coll_pk, yn_reward, yn_complete FROM hero_collection_reward WHERE lord_pk = $1 ORDER BY m_hero_coll_pk', [$_lord_pk]);
        $this->PgGame->fetchAll();
        $status = $this->PgGame->rows;
        foreach($status AS $v) {
            $hero_collection[$v['m_hero_coll_pk']]['status']['reward'] = $v['yn_reward'] ?: 'N';
            $hero_collection[$v['m_hero_coll_pk']]['status']['complete'] = $v['yn_complete'] ?: 'N';
        }

        $query_params = [$_lord_pk];
        $this->PgGame->query('SELECT m_hero_coll_pk, count(lord_pk) as col_count FROM hero_collection WHERE lord_pk = $1 GROUP BY lord_pk, m_hero_coll_pk', $query_params);
        while($this->PgGame->fetch()) {
            $hero_collection[$this->PgGame->row['m_hero_coll_pk']]['collection_cnt'] = $this->PgGame->row['col_count'];
        }

        $this->PgGame->query('SELECT yn_collection_reward FROM my_event WHERE lord_pk = $1', $query_params);
        $hero_collection['event_reward'] = $this->PgGame->fetchOne();

        return $hero_collection;
    }

    function collectionHero($_lord_pk, $_hero_pk): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_collection', 'hero', 'hero_base']);

        // 군주 카드일 경우 제외함.
        $this->PgGame->query('SELECT yn_lord FROM my_hero WHERE lord_pk = $1 AND hero_pk = $2', [$_lord_pk, $_hero_pk]);
        $yn_lord = $this->PgGame->fetchOne();
        if ($yn_lord == 'Y') {
            return false;
        }

        // 오버랭크 영웅 컬렉션에서 제외함
        $this->PgGame->query('SELECT yn_re_guest, m_hero_pk, yn_trade, yn_del, rare_type FROM hero WHERE hero_pk = $1', [$_hero_pk]);
        $this->PgGame->fetch();
        $m_hero_pk = $this->PgGame->row['m_hero_pk'];

        if ($this->PgGame->row['yn_re_guest'] == 'Y' || $_M['HERO'][$m_hero_pk]['over_type'] == 'Y' || $this->PgGame->row['yn_del'] == 'Y' ||
            $this->PgGame->row['yn_trade'] == 'Y' || $this->PgGame->row['rare_type'] < 2) {
            return false;
        }

        $m_hero_base_pk = $_M['HERO'][$m_hero_pk]['m_hero_base_pk'];

        foreach($_M['HERO_COLLECTION'] AS $v) {
            // 1. 해당 콜렉션 완료 했는지 확인 필요
            // 2. 해당 영웅 콜렉션에 이미 등록되어 있는지 확인
            $this->PgGame->query('SELECT yn_complete FROM hero_collection_reward WHERE lord_pk = $1 AND m_hero_coll_pk = $2', [$_lord_pk, $v['m_hero_coll_pk']]);
            $yn_complete = $this->PgGame->fetchOne();

            // 완료 된게 아니면 체크
            if ($yn_complete != 'Y') {
                if ($v['type'] == 'lord') {
                    $ret = $this->lordHeroCollection($_lord_pk, $m_hero_base_pk, $v['type_value'], $v['m_hero_coll_pk'], $m_hero_pk, $_hero_pk);
                    if ($ret) {
                        // 완료 상태 여부체크
                        $this->completeCollection($_lord_pk, $v['m_hero_coll_pk']);
                    }
                }
            }
        }

        // 퀘스트를 전부 클리어했는지 체크
        $this->PgGame->query('SELECT count(yn_complete) FROM hero_collection_reward WHERE lord_pk = $1 AND yn_complete = $2;', [$_lord_pk, 'Y']);
        $check_count = $this->PgGame->fetchOne();
        if ($check_count > 4) {
            // my_event 체크
            $this->PgGame->query('SELECT last_event_dt FROM my_event WHERE lord_pk = $1 AND last_event_dt > $2', [$_lord_pk, '2012-05-14 00:00:00']);
            $last_event_dt = $this->PgGame->fetchOne();
            if (!$last_event_dt) {
                $this->PgGame->query('SELECT yn_collection_reward FROM my_event WHERE lord_pk = $1', [$_lord_pk]);
                $yn_collection_reward = $this->PgGame->fetchOne();
                if ($yn_collection_reward) {
                    $this->PgGame->query('UPDATE my_event SET last_event_dt = now() WHERE lord_pk = $1', [$_lord_pk]);
                } else {
                    $this->PgGame->query('INSERT INTO my_event (lord_pk, last_event_dt) VALUES ($1, now())', [$_lord_pk]);
                }
            }
        }

        return true;
    }

    function lordHeroCollection($_lord_pk, $_m_hero_base_pk, $_forces, $_m_hero_coll_pk, $_m_hero_pk, $_hero_pk): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_collection', 'hero', 'hero_base']);

        if ($_M['HERO_BASE'][$_m_hero_base_pk]['forces'] == $_forces) {
            $this->PgGame->query('INSERT INTO hero_collection (lord_pk, m_hero_coll_pk, m_hero_base_pk, m_hero_pk, hero_pk) VALUES ($1, $2, $3, $4, $5)', [$_lord_pk, $_m_hero_coll_pk, $_m_hero_base_pk, $_m_hero_pk, $_hero_pk]);
        } else {
            return false;
        }

        return true;
    }

    function completeCollection($_lord_pk, $_m_hero_coll_pk): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_collection']);

        $this->PgGame->query('SELECT count(lord_pk) FROM hero_collection WHERE lord_pk = $1 AND m_hero_coll_pk = $2', [$_lord_pk, $_m_hero_coll_pk]);
        $collection_count = $this->PgGame->fetchOne();

        if ($_M['HERO_COLLECTION'][$_m_hero_coll_pk]['collection_count'] <= $collection_count) {
            $this->PgGame->query('INSERT INTO hero_collection_reward (lord_pk, m_hero_coll_pk, yn_reward, yn_complete) VALUES ($1, $2, $3, $4)', [$_lord_pk, $_m_hero_coll_pk, 'N', 'Y']);
        }
    }

    function rewardCollection($_lord_pk, $_m_hero_coll_pk): false|string
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero_collection', 'hero', 'item']);

        $this->PgGame->query('SELECT yn_reward FROM hero_collection_reward WHERE lord_pk = $1 AND m_hero_coll_pk = $2', [$_lord_pk, $_m_hero_coll_pk]);
        if ($this->PgGame->fetchOne() == 'Y') {
            $NsGlobal->setErrorMessage('이미 보상이 완료된 컬렉션 입니다.');
            return false;
        }

        // 해당 컬렉션 완료 했는지 확인
        $this->PgGame->query('SELECT COUNT(lord_pk) FROM hero_collection WHERE lord_pk = $1 AND m_hero_coll_pk = $2', [$_lord_pk, $_m_hero_coll_pk]);
        if ($this->PgGame->fetchOne() < $_M['HERO_COLLECTION'][$_m_hero_coll_pk]['collection_count']) {
            $NsGlobal->setErrorMessage('해당 컬렉션을 완료하지 못했습니다.<br /><br/ >보상은 해당 컬렉션 완료 후 가능합니다.');
            return false;
        }

        // 기본 보상
        $reward_item_arr = explode(':', $_M['HERO_COLLECTION'][$_m_hero_coll_pk]['reward_item']);

        $this->classItem();
        // 트랜잭션
        try {
            $this->PgGame->begin();
            global $_NS_SQ_REFRESH_FLAG;
            $_NS_SQ_REFRESH_FLAG = true;

            $ret = $this->Item->BuyItem($_lord_pk, $reward_item_arr[0], $reward_item_arr[1], 'hero_collection_reward'); // 보상 아이템 (마스터데이터에서 관리)
            if (!$ret) {
                $NsGlobal->setErrorMessage('아이템 지급 실패<br /><br />잠시 후 다시 시도해 주시기 바랍니다.');
                throw new Exception('아이템 지급 실패');
            }
            $message = $_M['ITEM'][$reward_item_arr[0]]['title'].' (5개)';

            // TODO : event - 내 영웅카드와 같을 경우 추가보상

            // 먼저 이미 군주보상을 받은 기록이 있는지 체크
            /*$query_params = Array($_lord_pk);
            $this->PgGame->query('SELECT yn_collection_lord_reward FROM my_event WHERE lord_pk = $1', $query_params);
            $lord_reward = $this->PgGame->fetchOne();
            if ($lord_reward != 'Y')
            {
                $query_params = Array($this->Session->lord['lord_hero_pk']);
                $this->PgGame->query('SELECT c.forces FROM hero a, m_hero b, m_hero_base c WHERE a.hero_pk = $1 AND a.m_hero_pk = b.m_hero_pk AND b.m_hero_base_pk = c.m_hero_base_pk', $query_params);
                if ($_M['HERO_COLLECTION'][$_m_hero_coll_pk]['type_value'] == $this->PgGame->fetchOne())
                {
                    $ret = $this->Item->BuyItem($_lord_pk, 500247, 5, 'hero_collection_reward_add'); // 특수 영웅 즉시 영입 5개
                    if (!$ret)
                    {
                        $NsGlobal->setErrorMessage('아이템 지급 실패<br /><br />잠시 후 다시 시도해 주시기 바랍니다.');
                        throw new Exception('아이템 지급 실패');
                    }

                    $mesg .= ', 특수 영웅 즉시 영입 (5개)';

                    // 추가 보상이 지급됬으면 군주 변경을 하고 추가보사을 받을 것을 대비해
                    $query_params = Array('Y', $_lord_pk, '2011-08-19 09:00:00');
                    if (!$lord_reward)
                    {
                        $r = $this->PgGame->query('INSERT INTO my_event (lord_pk, yn_collection_lord_reward, last_event_dt) VALUES ($2, $1, $3)', $query_params);
                    } else {
                        $r = $this->PgGame->query('UPDATE my_event SET yn_collection_lord_reward = $1, last_event_dt = $3 WHERE lord_pk = $2', $query_params);
                    }
                    if (!$r)
                    {
                        $NsGlobal->setErrorMessage('아이템 지급 실패<br /><br />잠시 후 다시 시도해 주시기 바랍니다.');
                        throw new Exception('상태변경 실패');
                    }
                }
            }*/
            $this->PgGame->query('SELECT c.forces FROM hero a, m_hero b, m_hero_base c WHERE a.hero_pk = $1 AND a.m_hero_pk = b.m_hero_pk AND b.m_hero_base_pk = c.m_hero_base_pk', [$this->Session->lord['lord_hero_pk']]);
            if ($_M['HERO_COLLECTION'][$_m_hero_coll_pk]['type_value'] == $this->PgGame->fetchOne()) {
                $ret = $this->Item->BuyItem($_lord_pk, 500247, 5, 'hero_collection_reward_add'); // 특수 영웅 즉시 영입 5개
                if (!$ret) {
                    $NsGlobal->setErrorMessage('아이템 지급 실패<br /><br />잠시 후 다시 시도해 주시기 바랍니다.');
                    throw new Exception('아이템 지급 실패');
                }
                $message .= ', 특수 영웅 즉시 영입 (5개)';
            }

            $r = $this->PgGame->query('UPDATE hero_collection_reward SET yn_reward = $1 WHERE lord_pk = $2 AND m_hero_coll_pk = $3', ['Y', $_lord_pk, $_m_hero_coll_pk]);
            if (!$r) {
                $NsGlobal->setErrorMessage('아이템 지급 실패<br /><br />잠시 후 다시 시도해 주시기 바랍니다.');
                throw new Exception('상태변경 실패');
            }

            $this->PgGame->commit();
        } catch (Exception $e) {
            // 실패, sq 무시
            $this->PgGame->query('ROLLBACK');

            //dubug_mesg남기기
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';lord_pk['.$_lord_pk.'];');

            return false;
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        return $message;
    }

    function additionalReward($_lord_pk): bool
    {
        global $NsGlobal;
        $this->PgGame->query('SELECT yn_collection_reward FROM my_event WHERE lord_pk = $1', [$_lord_pk]);
        $yn_collection_reward = $this->PgGame->fetchOne();
        if ($yn_collection_reward == 'Y') {
            $NsGlobal->setErrorMessage('이미 보상이 완료되었습니다.');
            return false;
        }

        $this->PgGame->query('SELECT COUNT(lord_pk) FROM hero_collection_reward WHERE lord_pk = $1 AND yn_complete = $2', [$_lord_pk, 'Y']);
        if ($this->PgGame->fetchOne() < 5) {
            $NsGlobal->setErrorMessage('모든 진영의 영웅 10명씩 획득 했을 경우에만 추가 보상을 받을 수 있습니다.');
            return false;
        }

        $this->classItem();
        // 트랜잭션
        try {
            $this->PgGame->begin();
            global $_NS_SQ_REFRESH_FLAG;
            $_NS_SQ_REFRESH_FLAG = true;
            $ret = $this->Item->BuyItem($_lord_pk, 500247, 5, 'hero_collection_reward_event');
            if (!$ret) {
                $NsGlobal->setErrorMessage('아이템 지급 실패<br /><br />잠시 후 다시 시도해 주시기 바랍니다.');
                throw new Exception('아이템 지급 실패');
            }
            $ret = $this->Item->BuyItem($_lord_pk, 500103, 5, 'hero_collection_reward_event');
            if (!$ret) {
                $NsGlobal->setErrorMessage('아이템 지급 실패<br /><br />잠시 후 다시 시도해 주시기 바랍니다.');
                throw new Exception('아이템 지급 실패');
            }

            if ($yn_collection_reward) {
                $r = $this->PgGame->query('UPDATE my_event SET yn_collection_reward = $1 WHERE lord_pk = $2', ['Y', $_lord_pk]);
            } else {
                $r = $this->PgGame->query('INSERT INTO my_event (lord_pk, yn_collection_reward) VALUES ($2, $1)', ['Y', $_lord_pk]);
            }
            if (!$r) {
                $NsGlobal->setErrorMessage('아이템 지급 실패<br /><br />잠시 후 다시 시도해 주시기 바랍니다.');
                throw new Exception('상태 변경 실패');
            }
        } catch (Exception $e) {
            // 실패, sq 무시
            $this->PgGame->rollback();

            //dubug_mesg남기기
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';lord_pk['.$_lord_pk.'];');

            return false;
        }

        $this->PgGame->commit();

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        return true;
    }

    // 영웅 랭킹 토탈 카운트
    function getHeroCollectionTotalCount(): int
    {
        // 총 갯수 구하기
        $this->PgGame->query('SELECT COUNT(lord_pk) FROM my_event WHERE last_event_dt > $1', ['2012-05-13 00:00:00']);
        return $this->PgGame->fetchOne();
    }

    // 영웅 컬렉션 랭킹
    function getHeroCollectionRanking($_total_count, $_page): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['item']);

        $hero_collection_ranking = [];
        $max_list_count = 15; // 한페이지 랭킹 수

        $total_page = 0;
        $page_num = $_page;
        if ($_total_count > 0) {
            // 응답 - 총 페이지 수
            $total_page = (INT)($_total_count / $max_list_count);
            $total_page += ($_total_count % $max_list_count > 0)? 1 : 0;

            // 페이지 번호 확인
            if ($page_num < 1) {
                $page_num = 1;
            } else if ($page_num > $total_page) {
                $page_num = $total_page;
            }

            // 오프셋 구하기
            $offset_num = (($page_num - 1) * $max_list_count);

            $this->PgGame->query('SELECT t2.lord_name, substr(t1.last_event_dt::timestamp::varchar, 0, 20) as last_event_dt, t2.lord_pk
FROM my_event t1, lord t2 WHERE t1.lord_pk = t2.lord_pk AND last_event_dt > $3
ORDER BY last_event_dt LIMIT $1 OFFSET $2', [$max_list_count, $offset_num, '2012-05-13 00:00:00']);
            $this->PgGame->fetchAll();
            $hero_collection_ranking = $this->PgGame->rows;

            $rank = 1 + $offset_num;

            foreach($hero_collection_ranking as $k => $v) {
                if ($rank == 1) {
                    $hero_collection_ranking[$k]['reward_item'] = $_M['ITEM'][500417]['title']; // 오호대장군 즉시 영입;
                    $hero_collection_ranking[$k]['reward_cnt'] = 1;
                } else if ($rank > 1 && $rank < 21) {
                    $hero_collection_ranking[$k]['reward_item'] = $_M['ITEM'][500247]['title']; // 특수 영웅 즉시 영입;
                    if ($rank < 11)
                    {
                        $hero_collection_ranking[$k]['reward_cnt'] = 20;
                    } else {
                        $hero_collection_ranking[$k]['reward_cnt'] = 10;
                    }
                } else if ($rank > 20 && $rank < 51) {
                    $hero_collection_ranking[$k]['reward_item'] = $_M['ITEM'][500103]['title']; // 우수 영웅 즉시 영입;
                    $hero_collection_ranking[$k]['reward_cnt'] = 10;
                } else if ($rank > 50 && $rank < 61) {
                    $hero_collection_ranking[$k]['reward_item'] = $_M['ITEM'][500385]['title']; // 지휘 영웅 즉시 영입;
                    $hero_collection_ranking[$k]['reward_cnt'] = 5;
                } else if ($rank > 60 && $rank < 71) {
                    $hero_collection_ranking[$k]['reward_item'] = $_M['ITEM'][500386]['title']; // 용맹 영웅 즉시 영입;
                    $hero_collection_ranking[$k]['reward_cnt'] = 5;
                } else if ($rank > 70 && $rank < 81) {
                    $hero_collection_ranking[$k]['reward_item'] = $_M['ITEM'][500387]['title']; // 책략 영웅 즉시 영입;
                    $hero_collection_ranking[$k]['reward_cnt'] = 5;
                } else if ($rank > 80 && $rank < 91) {
                    $hero_collection_ranking[$k]['reward_item'] = $_M['ITEM'][500388]['title']; // 내정 영웅 즉시 영입;
                    $hero_collection_ranking[$k]['reward_cnt'] = 5;
                } else if ($rank > 90 && $rank < 101) {
                    $hero_collection_ranking[$k]['reward_item'] = $_M['ITEM'][500389]['title']; // 매혹 영웅 즉시 영입;
                    $hero_collection_ranking[$k]['reward_cnt'] = 5;
                } else {
                    $hero_collection_ranking[$k]['reward_item'] = '-';
                    $hero_collection_ranking[$k]['reward_cnt'] = '-';
                }

                $hero_collection_ranking[$k]['rank'] = $rank;
                $rank++;
            }
        }

        return ['total_page' => $total_page, 'page' => $page_num, 'ranking' => $hero_collection_ranking];
    }
}