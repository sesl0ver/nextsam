#!/bin/sh

php gen_m_condition.php > ../../../master_data/cache/m_condition.cache.php

php gen_m_building.php > ../../../master_data/cache/m_building.cache.php
php gen_m_technique.php > ../../../master_data/cache/m_technique.cache.php
php gen_m_army.php > ../../../master_data/cache/m_army.cache.php
php gen_m_fortification.php > ../../../master_data/cache/m_fortification.cache.php

php gen_m_productivity_building.php > ../../../master_data/cache/m_productivity_building.cache.php
php gen_m_productivity_valley.php > ../../../master_data/cache/m_productivity_valley.cache.php

php gen_m_officer.php > ../../../master_data/cache/m_officer.cache.php
php gen_m_hero_base.php > ../../../master_data/cache/m_hero_base.cache.php
php gen_m_hero.php > ../../../master_data/cache/m_hero.cache.php
php gen_m_table_hero_acquired.php > ../../../master_data/cache/m_table_hero_acquired.cache.php
php gen_m_hero_collection.php > ../../../master_data/cache/m_hero_collection.cache.php
php gen_m_hero_exp.php > ../../../master_data/cache/m_hero_exp.cache.php

php gen_m_item.php > ../../../master_data/cache/m_item.cache.php
php gen_m_item_magiccube.php > ../../../master_data/cache/m_item_magiccube.cache.php
php gen_m_item_magiccube_evt.php > ../../../master_data/cache/m_item_magiccube_evt.cache.php

php gen_m_item_random_rate.php > ../../../master_data/cache/m_item_rand_rate.cache.php
php gen_m_quest.php > ../../../master_data/cache/m_quest.cache.php

php gen_m_providence.php > ../../../master_data/cache/m_providence.cache.php

php gen_m_hero_acquired_level.php > ../../../master_data/cache/m_hero_acquired_level.cache.php
php gen_m_hero_acquired_rare.php > ../../../master_data/cache/m_hero_acquired_rare.cache.php
php gen_m_hero_acquired_plusstat.php > ../../../master_data/cache/m_hero_acquired_plusstat.cache.php

php gen_m_hero_encounter_hero_level.php > ../../../master_data/cache/m_hero_encounter_hero_level.cache.php

php gen_m_hero_acquired_enchant_plusstat.php > ../../../master_data/cache/m_hero_acquired_enchant_plusstat.cache.php

php gen_m_hero_skill.php > ../../../master_data/cache/m_hero_skill.cache.php
php gen_m_hero_skill_cmd_rate.php > ../../../master_data/cache/m_hero_skill_cmd_rate.cache.php
php gen_m_hero_acquired_skill.php > ../../../master_data/cache/m_hero_acquired_skill.cache.php
php gen_m_hero_skill_exp.php > ../../../master_data/cache/m_hero_skill_exp.cache.php

php gen_m_table_effect.php > ../../../master_data/cache/m_table_effect.cache.php

php gen_m_npc_hero.php > ../../../master_data/cache/m_npc_hero.cache.php
php gen_m_npc_troop.php > ../../../master_data/cache/m_npc_troop.cache.php

php gen_m_npc_territory.php > ../../../master_data/cache/m_npc_territory.cache.php

php gen_m_npc_reward.php > ../../../master_data/cache/m_npc_reward.cache.php

php gen_m_encounter_range.php > ../../../master_data/cache/m_encounter_range.cache.php

php gen_m_reserved_word.php > ../../../master_data/cache/m_reserved_word.cache.php
php gen_m_forbidden_word.php > ../../../master_data/cache/m_forbidden_word.cache.php

php gen_m_troop.php > ../../../master_data/cache/m_troop.cache.php

php gen_m_social_gift.php > ../../../master_data/cache/m_social_gift.cache.php
php gen_m_social_request.php > ../../../master_data/cache/m_social_request.cache.php
php gen_m_social_invite_reward.php > ../../../master_data/cache/m_social_invite_reward.cache.php

php gen_m_point.php > ../../../master_data/cache/m_point.cache.php
php gen_m_point_npc_troop.php > ../../../master_data/cache/m_point_npc_troop.cache.php
php gen_m_point_reward.php > ../../../master_data/cache/m_point_reward.cache.php
php gen_m_point_reward_item.php > ../../../master_data/cache/m_point_reward_item.cache.php

php gen_m_gachapon.php > ../../../master_data/cache/m_gachapon.cache.php
php gen_m_qbig_pack.php > ../../../master_data/cache/m_qbig_pack.cache.php

php gen_m_hero_collection_combi.php > ../../../master_data/cache/m_hero_collection_combi.cache.php
php gen_m_hero_collection_combi_acquired_level.php > ../../../master_data/cache/m_hero_collection_combi_acquired_level.cache.php
php gen_m_hero_collection_combi_item.php > ../../../master_data/cache/m_hero_collection_combi_item.cache.php

php gen_m_hero_combination_rare.php > ../../../master_data/cache/m_hero_combination_rare.cache.php
php gen_m_hero_combination_level.php > ../../../master_data/cache/m_hero_combination_level.cache.php

php gen_m_alliance_member.php > ../../../master_data/cache/m_alliance_member.cache.php

php gen_m_need_resource.php > ../../../master_data/cache/m_need_resource.cache.php

php gen_m_npc_ann_reward.php > ../../../master_data/cache/m_npc_ann_reward.cache.php

php gen_m_pickup.php > ../../../master_data/cache/m_pickup.cache.php
php gen_m_package.php > ../../../master_data/cache/m_package.cache.php
php gen_m_occupation_reward.php > ../../../master_data/cache/m_occupation_reward.cache.php

echo "Update Server Master Data."