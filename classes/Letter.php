<?php

class Letter
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Push $Push;
    protected Quest $Quest;
    protected NsGlobal $NsGlobal;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->NsGlobal = NsGlobal::getInstance();
    }

    public function classPush (): void
    {
        if (! isset($this->Push)) {
            $this->Push = new Push($this->Session, $this->PgGame);
        }
    }

    public function classQuest (): void
    {
        if (! isset($this->Quest)) {
            $this->Quest = new Quest($this->Session, $this->PgGame);
        }
    }

    // 게임 시작할때 5일 이상된 서신 삭제
    function init($_lord_pk): true
    {
        $interval = LETTER_DELETE_PERIOD;
        $this->PgGame->query("DELETE FROM letter WHERE (from_lord_pk = $1 OR to_lord_pk = $1) AND send_dt <= now() - interval '$interval second'", [$_lord_pk]);
        $this->setUnreadCnt($_lord_pk);
        return true;
    }

    function setUnreadCnt($_lord_pk): void
    {
        $this->PgGame->query('SELECT COUNT(lett_pk) FROM letter WHERE to_lord_pk = $1 AND yn_to_delete = $2 AND yn_read = $3', [$_lord_pk, 'N', 'N']);
        $cnt = $this->PgGame->fetchOne();
        // unread cnt 처리용
        $this->PgGame->query('UPDATE lord SET unread_letter_cnt = $1, unread_letter_last_up_dt = now() WHERE lord_pk = $2', [$cnt, $_lord_pk]);
        $this->getUnreadCount($_lord_pk);
        // LP 입력
        $this->Session->sqAppend('LORD',['unread_letter_cnt' => $cnt], null, $_lord_pk);
    }

    // 받은서신 리스트 불러오기
    function getReceiveLetter($_lord_pk, $_letter_type, $_page_num = 1): array
    {
        return $this->getLetter('receive', $_lord_pk, $_letter_type, $_page_num);
    }

    // 보낸 서신 리스트 불러오기
    function getSendLetter($_lord_pk, $_letter_type, $_page_num = 1): array
    {
        return $this->getLetter('send', $_lord_pk, $_letter_type, $_page_num);
    }

    // 서신 리스트 불러오기 - 굳이 둘로 나눌 필요가 없어서 통합하여 처리
    function getLetter($_type, $_lord_pk, $_letter_type, $_page_num = 1): array
    {
        $_append_string = ($_type === 'send') ? 'from_lord_pk = $1 AND type = $2 AND yn_from_delete = $4' : 'to_lord_pk = $1 AND type = $2 AND yn_to_delete = $4';
        $offset_num = (($_page_num - 1) * REPORT_LETTER_PAGE_NUM);
        $this->PgGame->query("SELECT lett_pk, from_lord_pk, to_lord_pk, from_lord_name, to_lord_name, yn_read, title, date_part('epoch', send_dt)::integer as send_dt FROM letter WHERE {$_append_string} ORDER BY send_dt DESC LIMIT $5 OFFSET $3", [$_lord_pk, $_letter_type, $offset_num, 'N', REPORT_LETTER_PAGE_NUM]);
        $this->PgGame->fetchAll();
        $letter_list = (!$this->PgGame->rows || !count($this->PgGame->rows)) ? [] : $this->PgGame->rows;
        foreach ($letter_list AS $k => $v) {
            $letter_list[$k]['title'] = Useful::forbiddenWordReplace($v['title']);
        }
        return $letter_list;
    }

    // 서신 보내기(보내는 lord_pk, 받는 사람(배열로...여러명일수 있음), 서신내용(제목, 내용, 서신타입...)
    function sendLetter($_lord_pk, $_arr_to_lord_pk, $_arr_letter, $_is_html = false, $_yn_from_delete = 'N', $_push = true): true
    {
        if ($_arr_letter['type'] == 'S') {
            $from_lord_name = '운영자';
            if ($_lord_pk === EMPEROR_LORD_PK) {
                $from_lord_name = '황제';
                $_lord_pk = ADMIN_LORD_PK;
            }
            // 시스템 서신으로 보낼 시 보낸 쪽은 삭제 상태로 강제해야함 , 안하면 영원히 삭제 안함
            $_yn_from_delete = 'Y';
        } else {
            $from_lord_name = $this->Session->lord['lord_name'];
        }

        if (! $_is_html) {
            // _is_html = false html 제거
            $_arr_letter['title'] = Useful::forbiddenWordReplace(strip_tags($_arr_letter['title']));
            $_arr_letter['content'] = Useful::forbiddenWordReplace(strip_tags($_arr_letter['content']));
        }

        $letter_title = $_arr_letter['title'];
        $letter_content = $_arr_letter['content'];
        $letter_type = $_arr_letter['type'];

        foreach($_arr_to_lord_pk AS $v) {
            $this->PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$v]);
            $to_lord_name = $this->PgGame->fetchOne();
            $this->PgGame->query('INSERT INTO letter (from_lord_pk, to_lord_pk, type, from_lord_name, to_lord_name, yn_read, title, content, send_dt, yn_to_delete, yn_from_delete) 
VALUES ($1, $2, $3, $4, $5, $6, $7, $8, now(), $9, $10)', [$_lord_pk, $v, $letter_type, $from_lord_name, $to_lord_name, 'N', $letter_title, $letter_content, 'N', $_yn_from_delete]);

            $this->Session->sqAppend('PUSH', ['PLAY_SOUND' => 'report_arrival'], null, $v);
            if ($_push) {
                $this->classPush();
                $this->Push->send('letter', '', $v);
            }
        }

        $this->getUnreadCount($_lord_pk);

        // 퀘스트 체크
        $this->classQuest();
        $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'alliance', 'type' => 'letter']);

        return true;
    }

    // 서신 보내기
    function sendLetterNew($_lord_pk, $_to_lords, $_arr_letter, $_is_html = false, $_yn_from_delete = 'N', $_push = true): bool
    {
        if ($_arr_letter['type'] == 'S') {
            $from_lord_name = '운영자';
            if ($_lord_pk === EMPEROR_LORD_PK) {
                $from_lord_name = '황제';
                $_lord_pk = ADMIN_LORD_PK;
            }
            // 시스템 서신으로 보낼 시 보낸 쪽은 삭제 상태로 강제해야함 , 안하면 영원히 삭제 안함
            $_yn_from_delete = 'Y';
        } else {
            $from_lord_name = $this->Session->lord['lord_name'];
        }

        if (! $_is_html) {
            // _is_html = false html 제거
            $_arr_letter['title'] = Useful::forbiddenWordReplace(strip_tags($_arr_letter['title']));
            $_arr_letter['content'] = Useful::forbiddenWordReplace(strip_tags($_arr_letter['content']));
        }

        try {
            $letter_title = $_arr_letter['title'];
            $letter_content = $_arr_letter['content'];
            $letter_type = $_arr_letter['type'];

            $query_values = [];
            foreach($_to_lords AS $_to) {
                $query_values[] = "($_lord_pk, {$_to['lord_pk']}, '$letter_type', '$from_lord_name', '{$_to['lord_name']}', 'N', '$letter_title', '$letter_content', now(), 'N', '$_yn_from_delete')";

                // 서신을 받는 군주들에게 Push 데이터를 전송
                $this->Session->sqAppend('PUSH', ['PLAY_SOUND' => 'report_arrival'], null, $_to['lord_pk']);
                if ($_push) {
                    $this->classPush();
                    $this->Push->send('letter', '', $_to['lord_pk']);
                }
            }
            $this->PgGame->query('INSERT INTO letter (from_lord_pk, to_lord_pk, type, from_lord_name, to_lord_name, yn_read, title, content, send_dt, yn_to_delete, yn_from_delete) VALUES '. join(',', $query_values));

            if ($_lord_pk > ADMIN_LORD_PK) {
                $this->getUnreadCount($_lord_pk);

                // 퀘스트 체크
                $this->classQuest();
                $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'alliance', 'type' => 'letter']);
            }
        } catch (Throwable $e) {
            new ErrorHandler('error', $e->getMessage());
        }

        return true;
    }

    // 서신 읽기
    function readLetter($_lett_pk, $_lett_type): false|array
    {
        $arr_letter = [];
        $this->PgGame->query('SELECT lett_pk, from_lord_pk, to_lord_pk, from_lord_name, to_lord_name, title, content, type, date_part(\'epoch\', send_dt)::integer as send_dt, item_data, item_dt FROM letter WHERE lett_pk = $1', [$_lett_pk]);
        if($this->PgGame->fetch()) {
            $arr_letter[$this->PgGame->row['lett_pk']] = $this->PgGame->row;
            $arr_letter[$this->PgGame->row['lett_pk']]['title'] = Useful::forbiddenWordReplace($arr_letter[$this->PgGame->row['lett_pk']]['title']);
            if ($this->PgGame->row['type'] === 'S') {
                $arr_letter[$this->PgGame->row['lett_pk']]['content'] = nl2br($arr_letter[$this->PgGame->row['lett_pk']]['content']);
            } else {
                $arr_letter[$this->PgGame->row['lett_pk']]['content'] = Useful::forbiddenWordReplace(nl2br($arr_letter[$this->PgGame->row['lett_pk']]['content']));
            }
            $arr_letter[$this->PgGame->row['lett_pk']]['item_data'] = $this->rewardSort($arr_letter[$this->PgGame->row['lett_pk']]['item_data']);
        } else {
            throw new ErrorHandler('error', 'Error Occurred. [21001]');
        }
        if (in_array($_lett_type, ['receive', 'system', 'system_list', 'receive_list'])) {
            $this->setReadLetter([$_lett_pk]);
        }
        return $arr_letter;
    }

    function rewardSort($_item_data): array
    {
        if (! isset($_item_data)) {
            return [];
        }
        $item_data = explode('|', $_item_data);
        $item_list = [];
        foreach($item_data AS $k => $item_set) {
            if ($item_set) {
                $type = explode('=', $item_set);
                switch ($type[0]) {
                    case 'i': // 아이템
                        $item_list['item'] = explode(';', $type[1]);
                        break;
                    case 'r': // 자원
                        $item_list['resource'] = explode(';', $type[1]);
                        break;
                    case 'q': // 큐빅
                        $item_list['qbig'] = explode(';', $type[1]);
                        break;
                    case 's': // 영웅 기술
                        $item_list['skill'] = explode(';', $type[1]);
                        break;
                    case 'a': // 병력
                        $item_list['army'] = explode(';', $type[1]);
                        break;
                    case 'p': // 인구
                        $item_list['population'] = explode(';', $type[1]);
                        break;
                    case 'f': // 함정
                        $item_list['fort'] = explode(';', $type[1]);
                        break;
                    case 'h': // 영웅
                        $item_list['hero'] = explode(';', $type[1]);
                        break;
                }
            }
        }
        return $item_list;
    }

    function getRewardList($_lett_pk): false|array
    {
        global $NsGlobal, $i18n;
        $this->PgGame->query('SELECT item_data, item_dt FROM letter WHERE lett_pk = $1', [$_lett_pk]);
        $this->PgGame->fetch();
        if (! isset($this->PgGame->row['item_data'])) {
            $NsGlobal->setErrorMessage($i18n->t('msg_no_reward')); // 수령 가능한 보상이 없습니다.
            return false;
        }
        if (isset($this->PgGame->row['item_dt'])) {
            $NsGlobal->setErrorMessage($i18n->t('msg_already_received_reward')); // 이미 수령한 보상입니다.
            return false;
        }
        return $this->rewardSort($this->PgGame->row['item_data']);
    }

    // 서신 삭제하기
    function deleteLetter($_arr_lett_pk, $_letter_type): true
    {
        if ($_letter_type == 'send') {
            foreach ($_arr_lett_pk AS $v) {
                $this->PgGame->query('SELECT yn_to_delete FROM letter WHERE lett_pk = $1', [$v]);
                $delete_state = $this->PgGame->fetchOne();
                if ($delete_state == 'Y') {
                    $this->PgGame->query('DELETE FROM letter WHERE lett_pk = $1 AND from_lord_pk = $2', [$v, $this->Session->lord['lord_pk']]);
                } else {
                    $this->PgGame->query('UPDATE letter SET yn_from_delete = $1 WHERE lett_pk = $2 AND from_lord_pk = $3', ['Y', $v, $this->Session->lord['lord_pk']]);
                }
            }
        } else if ($_letter_type == 'receive') {
            foreach ($_arr_lett_pk AS $v) {
                $this->PgGame->query('SELECT yn_from_delete FROM letter WHERE lett_pk = $1', [$v]);
                $delete_state = $this->PgGame->fetchOne();
                if ($delete_state == 'Y') {
                    $this->PgGame->query('DELETE FROM letter WHERE lett_pk = $1 AND to_lord_pk = $2', [$v, $this->Session->lord['lord_pk']]);
                } else {
                    $this->PgGame->query('UPDATE letter SET yn_to_delete = $1 WHERE lett_pk = $2 AND to_lord_pk = $3', ['Y', $v, $this->Session->lord['lord_pk']]);
                }
            }
        }
        if ($_letter_type == 'receive' || $_letter_type == 'system') {
            $this->setUnreadCnt($this->Session->lord['lord_pk']);
        }
        return true;
    }

    // 군주명으로 군주 pk와 이름 얻기
    function getLordInfoByLordName($_lord_name): array|bool
    {
        $this->PgGame->query('SELECT lord_pk, lord_name, power FROM lord WHERE lord_name = $1', [$_lord_name]);
        $this->PgGame->fetch();
        return $this->PgGame->row ?? false;
    }

    // 군주pk로 군주 pk와 이름얻기
    function getLordInfoByLordPK($_lord_pk): false|array
    {
        global $i18n;
        $this->PgGame->query('SELECT lord_pk, lord_name FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $result = [];
        if($this->PgGame->fetch()) {
            $result[$this->PgGame->row['lord_pk']] = $this->PgGame->row;
        } else {
            throw new ErrorHandler('error', $i18n->t('msg_not_exist_lord')); // 존재하지 않는 군주입니다.
        }
        return $result;
    }

    // 서신 총 갯수 얻기
    function getLetterTotalCount($_lord_pk, $_letter_type): int
    {
        if ($_letter_type == 'send') {
            $this->PgGame->query('SELECT COUNT(lett_pk) FROM letter WHERE from_lord_pk = $1 AND yn_from_delete = $2', [$_lord_pk, 'N']);
        } else if ($_letter_type == 'receive' || $_letter_type == 'system') {
            $this->PgGame->query('SELECT COUNT(lett_pk) FROM letter WHERE to_lord_pk = $1 AND type = $2 AND yn_to_delete = $3', [$_lord_pk, ($_letter_type == 'receive') ? 'N' : 'S', 'N']);
        }
        return $this->PgGame->fetchOne();
    }

    // 서신 읽음 상태로 바꾸기
    function setReadLetter($_lett_pk): true
    {
        // 서신을 읽었음을 전달해줄 보낸 군주들의 pk 모을 배열
        $from_lord_pk_array = [];
        for ($i = 0; $i < COUNT($_lett_pk); $i++) {
            $this->PgGame->query('UPDATE letter SET yn_read = $1, read_dt = now() WHERE lett_pk = $2 AND to_lord_pk = $3', ['Y', $_lett_pk[$i], $this->Session->lord['lord_pk']]);
            $this->PgGame->query('SELECT from_lord_pk FROM letter WHERE lett_pk = $1', [$_lett_pk[$i]]);
            $from_lord_pk = $this->PgGame->fetchOne();
            if ($from_lord_pk != 2) {
                $from_lord_pk_array[] = $this->PgGame->fetchOne();
            }
        }
        $this->setUnreadCnt($this->Session->lord['lord_pk']);
        // pk들 중에 중복 제거하고, 보낸 군주들에게 현재 외교서신 발신함을 보고 있다면 새로 목록을 얻어서 읽었음을 알리도록 push 보냄
        $from_lord_pk_array = array_unique($from_lord_pk_array);
        for($i = 0; $i < COUNT($from_lord_pk_array); $i++) {
            if ($from_lord_pk_array[$i] > 0) {
                $this->Session->sqAppend('PUSH',['RECEIVER_READ_LETTER' => true], null, $from_lord_pk_array[$i]);
            }
        }
        return true;
    }

    /*
     * 읽지 않은 서신 종류별로 갯수 가져오기
     */
    function getUnreadCount($_lord_pk): void
    {
        $this->PgGame->query('SELECT type, count(lett_pk) as count FROM letter WHERE to_lord_pk = $1 AND yn_read = $2 AND yn_to_delete = $3 GROUP BY type', [$_lord_pk, 'N', 'N']);
        $this->PgGame->fetchAll();
        $result = [];
        foreach($this->PgGame->rows as $row) {
            $result[$row['type']] = $row['count'];
        }
        // TODO 굳이 이렇게까지 해야하나;; 차후 개선하자.
        foreach(['N', 'S'] as $tab_type) {
            if (! isset($result[$tab_type])) {
                $result[$tab_type] = 0;
            }
        }
        $this->Session->sqAppend('LORD', ['unread_letter_desc' => $result], null, $_lord_pk);

    }
}