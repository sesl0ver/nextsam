-- DB 서버만 켜고 시작
-- 이 값이 0인지 확인하고 작업 진행하기
select count(lord_pk) from lord where is_logon = 'Y';

/*
 * 새로운 대륙맵 타일 적용
 * -- m_position_area 데이터 업데이트
alter TABLE "m_position" DROP CONSTRAINT "m_position_area";
delete from m_position_area;
insert into m_position_area (select * from m_position_area_new);
alter TABLE "m_position" ADD CONSTRAINT "m_posi_area_pk_fkey" FOREIGN KEY (m_posi_area_pk) REFERENCES m_position_area (m_posi_area_pk);
*/

begin;
TRUNCATE TABLE update_history, md_update_history RESTART IDENTITY;

TRUNCATE TABLE alliance_war_history, alliance_relation, alliance_gift_list, alliance_member, alliance_join_list, alliance_history, alliance, alliance_request_list, report RESTART IDENTITY;
TRUNCATE TABLE letter RESTART IDENTITY;

TRUNCATE TABLE lord_point RESTART IDENTITY;

TRUNCATE TABLE market RESTART IDENTITY;

TRUNCATE TABLE position_favorite RESTART IDENTITY;

TRUNCATE TABLE ranking_alliance, ranking_hero, ranking_lord, ranking_point RESTART IDENTITY;

TRUNCATE TABLE trade_bid, trade_delivery, trade_offer, trade_price_list RESTART IDENTITY;

TRUNCATE TABLE medical_army, medical_hero, troop, timer RESTART IDENTITY;
TRUNCATE TABLE build_army, build_construction, build_fortification, build_technique, build_medical, build RESTART IDENTITY;

TRUNCATE TABLE building_in_castle RESTART IDENTITY;
TRUNCATE TABLE building_out_castle RESTART IDENTITY;

TRUNCATE TABLE fortification RESTART IDENTITY;
TRUNCATE TABLE army RESTART IDENTITY;
TRUNCATE TABLE technique RESTART IDENTITY;

TRUNCATE TABLE lord_technique RESTART IDENTITY;
TRUNCATE TABLE hero_trade_bid, hero_trade, hero_collection, hero_collection_reward RESTART IDENTITY;

TRUNCATE TABLE my_hero_skill, my_hero_skill_box, my_hero_skill_slot, hero_free, hero_free_bid, hero_free_backup, hero_free_bid_backup, hero_encounter, hero_invitation, my_hero, territory_hero_skill, territory_hero_assign, m_npc_hero, medical_hero, position_npc, suppress_position, suppress, troop, position_point, hero RESTART IDENTITY;

TRUNCATE TABLE production, resource, territory_valley, territory_item_buff RESTART IDENTITY;

TRUNCATE TABLE hero_trade_gold, gold RESTART IDENTITY;

TRUNCATE TABLE troop_order RESTART IDENTITY;

TRUNCATE TABLE occupation_inform RESTART IDENTITY;

TRUNCATE TABLE forcemap_all RESTART IDENTITY;
TRUNCATE TABLE forcemap_alli_rel RESTART IDENTITY;

-- 신규 추가 사항
TRUNCATE TABLE my_package, my_pickup, my_item_buy, occupation_point RESTART IDENTITY;

delete from territory;

update position set lord_pk = null where lord_pk is not null;
update position a set type = c.type, level = c.level, posi_area_pk = b.posi_area_pk
from m_position b, m_position_area c
where a.posi_pk = b.m_posi_pk and b.m_posi_area_pk = c.m_posi_area_pk;

/*
position table는 posi_pk가 '999x999'인것은 삭제하면 안됨.
TRUNCATE 필요시, m_position 입력 후 999x999 입력 필요.
INSERT INTO "public"."position" ("posi_pk","lord_pk","state","type","status","level","last_levelup_dt","last_update_dt","posi_area_pk","level_default")
VALUES ('999x999',NULL,'-         ','N','-','1', now(), now(),NULL,'1');
*/

TRUNCATE TABLE my_item, my_quest, game_option, chat, pns_prepare RESTART IDENTITY;

TRUNCATE TABLE lord_login RESTART IDENTITY;
TRUNCATE TABLE lord_web RESTART IDENTITY;
TRUNCATE TABLE qbig_pack RESTART IDENTITY;
TRUNCATE TABLE my_event RESTART IDENTITY;

-- 이벤트 테이블
TRUNCATE TABLE enter_event, enter_event_data, event_gift_lord_info, event_limited_date, my_event_npc_troop RESTART IDENTITY;
TRUNCATE TABLE my_enter_event RESTART IDENTITY;
TRUNCATE TABLE my_enter_event_2 RESTART IDENTITY;
TRUNCATE TABLE gachapon_event, new_gachapon_event RESTART IDENTITY;
/*
DROP TABLE tmp_giftitem_user;
DROP TABLE tmp_my_event;
*/
INSERT INTO enter_event (period, type_1, type_2, type_3) VALUES (1, 0, 0, 0);

TRUNCATE TABLE recommend_qbig, lord_recommended, lord_recommend RESTART IDENTITY;

--delete from lord where lord_pk > 2;
-- 아래와 같이 진행함.

--------------------------------------------------------------------------------------

alter TABLE "alliance" DROP CONSTRAINT "alliance_lord_pk_fkey";
alter TABLE "hero_free_bid" DROP CONSTRAINT "hero_free_bid_lord_pk_fkey";
alter TABLE "letter" DROP CONSTRAINT "letter_from_lord_pk_fkey" ;
alter TABLE "letter" DROP CONSTRAINT "letter_to_lord_pk_fkey" ;
alter TABLE "lord_point" DROP CONSTRAINT "lord_point_lord_pk_fkey" ;
alter TABLE "lord_technique" DROP CONSTRAINT "lord_technique_lord_pk_fkey" ;
alter TABLE "lord_web" DROP CONSTRAINT "lord_web_lord_pk_fkey" ;
alter TABLE "my_hero" DROP CONSTRAINT "my_hero_lord_pk_fkey" ;
alter TABLE "my_item" DROP CONSTRAINT "my_item_lord_pk_fkey" ;
alter TABLE "my_quest" DROP CONSTRAINT "my_quest_lord_pk_fkey" ;
alter TABLE "game_option" DROP CONSTRAINT "game_option_lord_pk_fkey" ;
alter TABLE "position_favorite" DROP CONSTRAINT "position_favorite_lord_pk_fkey" ;
alter TABLE "position" DROP CONSTRAINT "position_lord_pk_fkey" ;
alter TABLE "report" DROP CONSTRAINT "report_lord_pk_fkey" ;
alter TABLE "suppress" DROP CONSTRAINT "suppress_lord_pk_fkey" ;
alter TABLE "troop" DROP CONSTRAINT "troop_dst_lord_pk_fkey" ;
alter TABLE "troop_order" DROP CONSTRAINT "troop_order_lord_pk_fkey" ;
alter TABLE "troop" DROP CONSTRAINT "troop_src_lord_pk_fkey" ;
alter TABLE "qbig_pack" DROP CONSTRAINT "qbig_pack_lord_pk_fkey";
alter TABLE "my_hero_skill" DROP CONSTRAINT "my_hero_skill_pkey_lord_pk_fkey";
alter TABLE "my_hero_skill_box" DROP CONSTRAINT "my_hero_skill_box_pkey_lord_pk_fkey";
alter TABLE "hero_trade" DROP CONSTRAINT "hero_trade_lord_pk_fkey";
alter TABLE "hero_trade_bid" DROP CONSTRAINT "hero_trade_bid_lord_pk_fkey";
alter TABLE "hero_trade_gold" DROP CONSTRAINT "hero_trade_gold_lord_pk_fkey";
alter TABLE "my_enter_event" DROP CONSTRAINT "my_enter_event_lord_pk_fkey";
alter TABLE "my_enter_event_2" DROP CONSTRAINT "my_enter_event_2_lord_pk_fkey";
alter TABLE "ranking_point" DROP CONSTRAINT "ranking_point_lord_pk_fkey";
alter TABLE "hero_collection" DROP CONSTRAINT "hero_collection_lord_pk_fkey";
alter TABLE "hero_collection_reward" DROP CONSTRAINT "hero_collection_reward_lord_pk_fkey";
alter TABLE "lord_recommend" DROP CONSTRAINT "lord_recommend_lord_pk_fkey";
--alter TABLE "lord_recommend" DROP CONSTRAINT "lord_recommend_pkey";

-- added by turbojet
-- alter TABLE "raid_troop" DROP CONSTRAINT "raid_troo_lord_pk_fkey";
-- TRUNCATE TABLE raid_troop RESTART IDENTITY;

TRUNCATE TABLE lord, troop_preset RESTART IDENTITY;

alter TABLE "alliance" ADD CONSTRAINT "alliance_lord_pk_fkey" FOREIGN KEY (master_lord_pk) REFERENCES lord(lord_pk);
alter TABLE "hero_free_bid" ADD CONSTRAINT "hero_free_bid_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "letter" ADD CONSTRAINT "letter_from_lord_pk_fkey" FOREIGN KEY (from_lord_pk) REFERENCES lord(lord_pk);
alter TABLE "letter" ADD CONSTRAINT "letter_to_lord_pk_fkey" FOREIGN KEY (to_lord_pk) REFERENCES lord(lord_pk);
alter TABLE "lord_point" ADD CONSTRAINT "lord_point_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "lord_technique" ADD CONSTRAINT "lord_technique_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "lord_web" ADD CONSTRAINT "lord_web_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "my_hero" ADD CONSTRAINT "my_hero_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "my_item" ADD CONSTRAINT "my_item_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "my_quest" ADD CONSTRAINT "my_quest_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "game_option" ADD CONSTRAINT "game_option_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "position_favorite" ADD CONSTRAINT "position_favorite_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "position" ADD CONSTRAINT "position_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "report" ADD CONSTRAINT "report_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "suppress" ADD CONSTRAINT "suppress_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "troop" ADD CONSTRAINT "troop_dst_lord_pk_fkey" FOREIGN KEY (dst_lord_pk) REFERENCES lord(lord_pk);
alter TABLE "troop_order" ADD CONSTRAINT "troop_order_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "troop" ADD CONSTRAINT "troop_src_lord_pk_fkey" FOREIGN KEY (src_lord_pk) REFERENCES lord(lord_pk);
alter TABLE "qbig_pack" ADD CONSTRAINT "qbig_pack_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "my_hero_skill" ADD CONSTRAINT  "my_hero_skill_pkey_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "my_hero_skill_box" ADD CONSTRAINT  "my_hero_skill_box_pkey_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "hero_trade" ADD CONSTRAINT  "hero_trade_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "hero_trade_bid" ADD CONSTRAINT  "hero_trade_bid_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "hero_trade_gold" ADD CONSTRAINT  "hero_trade_gold_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "my_enter_event" ADD CONSTRAINT  "my_enter_event_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "my_enter_event_2" ADD CONSTRAINT  "my_enter_event_2_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "ranking_point" ADD CONSTRAINT "ranking_point_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "hero_collection" ADD CONSTRAINT "hero_collection_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "hero_collection_reward" ADD CONSTRAINT "hero_collection_reward_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
alter TABLE "lord_recommend" ADD CONSTRAINT "lord_recommend_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);

-- added by turbojet
-- alter TABLE "raid_troop" ADD CONSTRAINT "raid_troo_lord_pk_fkey" FOREIGN KEY (lord_pk) REFERENCES lord(lord_pk);
TRUNCATE TABLE raid_point, raid_request RESTART IDENTITY;

INSERT INTO lord (lord_pk, lord_name, lord_pic, status, level, num_slot_guest_hero, regist_dt, last_login_dt, lord_name_lower)
VALUES (1, '장각', 6, 'Y', 1, 1, NOW(), NOW(), '장각');

INSERT INTO lord (lord_pk, lord_name, lord_pic, status, level, num_slot_guest_hero, regist_dt, last_login_dt, lord_name_lower)
VALUES (2, '운영자', 6, 'Y', 1, 1, NOW(), NOW(), '운영자');

ALTER SEQUENCE lord_lord_pk_seq RESTART WITH 10;

--------------------------------------------------------------------------------------

update position_area set ru_curr = 0 where ru_curr > 0;
update position_region set ru_curr = 0 where ru_curr > 0;
update position_state set ru_curr = 0 where ru_curr > 0;

update m_preference set posi_stat_pk = 5, posi_regi_pk = 41;

-- 매직큐브 초기화
UPDATE m_item SET magiccube_left_count = 100000000 WHERE magiccube_default_count > 0;

-- 로그성 데이터 삭제
truncate table gmtool_ccu, troop_order RESTART IDENTITY;

--------------------------------------------------------------------------------------

-- 영웅 마스터데이터 삭제 (필요시)
--DELETE FROM m_hero;
--DELETE FROM m_hero_base;

-- 영웅 등록 (필요시)

-- 영웅 발급 갯수
update m_hero set set_count = 9999999, left_count = 9999999;

-- 초기화
UPDATE m_hero SET set_count = c.set_count, left_count = c.left_count
FROM m_hero_base as b, m_hero_acquired_restriction as c
WHERE m_hero.m_hero_base_pk = b.m_hero_base_pk AND b.rare_type = c.rare_type AND m_hero.level = c.level;

-- 군주는 가입시외 추출 불가 처리 (군주는 CHero::getNewLord 메소드 이외에서는 절대 추출 불가)
update m_hero set set_count = 0, left_count = 0 where m_hero_pk IN
(
  select m_hero_pk from m_hero where m_hero_base_pk IN
  (
    select m_hero_base_pk from m_hero_base where name IN ('유비', '원소', '손권', '동탁', '조조')
  )
);

--------------------------------------------------------------------------------------

--TRUNCATE TABLE lord_account;

TRUNCATE TABLE pgAgent.pga_joblog, pgAgent.pga_jobsteplog;

-- 요충지 스케쥴 정지
/*
UPDATE pgagent.pga_job SET jobenabled = false
 WHERE jobid=28;

UPDATE pgagent.pga_job SET jobenabled = false
 WHERE jobid=30;

UPDATE pgagent.pga_job SET jobenabled = false
 WHERE jobid=27;
*/
INSERT INTO update_history (note) VALUES('GAME DB INIT');

commit;
/*
vacuum full;
*/

--------------------------------------------------------------------------------------
/* DB 초기화 후 생성해야 할 데이터(s1gs)

cd /qbe/web
sh build_master_data.sh

cd /qbe/web/tool/bulkload

1. m_npc_hero / position_npc 테이블 생성 (문제가 없는 한 DB를 기준으로 해당 세트에 중심이 되는 장비에서만 1회 수행)
/qbe/app/bin/php bulkload_m_npc_hero.php
/qbe/app/bin/php bulkload_position_npc.php

// npc 생성 확
-- select count(*) from hero where hero_pk in (select hero_pk from m_npc_hero);
==> 1040개

2. php 및 js cache 생성
cd /qbe/web
sh build_master_data.sh

3. 재야영웅 생성
cd /qbe/web/tool/batch
/qbe/app/bin/php  batch_hero_free_random.php

*/

/*
vacuum full;
*/
--------------------------------------------------------------------------------------
/* 최종 테스트 완료 후 랭킹 초기화
truncate table ranking_alliance;
truncate table ranking_hero;
truncate table ranking_lord;
truncate table ranking_point;

*/
