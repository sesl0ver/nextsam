bulkload_m_npc_hero.php
와
bulkload_position_npc.php
는

문제가 없는 한 DB를 기준으로 해당 세트에 중심이 되는 장비에서만 1회 수행

그리고 모든 웹서버 노드들은 동일한 캐쉬파일을 가지고 있어야 한다.

DB hero 테이블에 장수가 등록되고 해당 장수를 re-use 하기 때문.
