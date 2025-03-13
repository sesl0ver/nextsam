-- Common DB

/*
 * 삭제하면 안됨
TRUNCATE TABLE server;
*/

/* 사용하지 않는 테이블  */
TRUNCATE TABLE enter_event RESTART IDENTITY;
TRUNCATE TABLE account_lord_info, facebook_request, gift, lord_account, request, my_quest, my_event RESTART IDENTITY;

--alter TABLE "facebook_request" DROP CONSTRAINT "gift_from_acco_pk_fkey";

/*********************************************************/

TRUNCATE TABLE device RESTART IDENTITY;
TRUNCATE TABLE ban RESTART IDENTITY;
TRUNCATE TABLE counsel RESTART IDENTITY;
TRUNCATE TABLE notice RESTART IDENTITY;

TRUNCATE TABLE qb_member_login, qb_member_logout, ns_member RESTART IDENTITY;

TRUNCATE TABLE account RESTART IDENTITY;

TRUNCATE TABLE update_history RESTART IDENTITY;

-- 이벤트 테이블
--INSERT INTO enter_event (period, type_1, type_2, type_3) VALUES (1, 0, 0, 0);

INSERT INTO update_history (note) VALUES('COMMON DB INIT');

--------------------------------------------------------------------------------------
/* 테스트 완료 후 초기화시 common 초기화

-- 네이트
update account set all_server = 's1,s2' where acco_pk IN (select acco_pk from account where all_server = 's1,s2,s3');
update account set all_server = 's2' where acco_pk IN (select acco_pk from account where all_server = 's2,s3');
update account set all_server = 's1' where acco_pk IN (select acco_pk from account where all_server = 's1,s3');

-- 네이버
update account set all_server = 's1' where acco_pk IN (select acco_pk from account where all_server = 's1,s2');


-- 다음
update account set all_server = 's1' where acco_pk IN (select acco_pk from account where all_server = 's1,s2');

*/

/*
 * server 셋팅
 */
--insert into server values ('s1', '낙양', '14.63.222.153', 'Y', 'Y', 'Y','N', 'Y', 'N', 1000, 0, 0, 'O', 's1gdb', 5433, 'qbe', '', 's1gs', 11211, 'ldb', 5433, 'qbe', '', 'qbelog_nakyang');