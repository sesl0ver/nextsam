<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_SKILL'] = [];
\$_M_HERO_SKILL = &\$GLOBALS['_M_HERO_SKILL'];
\$_M['HERO_SKILL'] = &\$_M_HERO_SKILL;


EOF;

$note = 'm_hero_skill';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_hero
$PgGame->query('SELECT * FROM m_hero_skill');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_hero_skil_pk'];

	echo <<< EOF
\$_M_HERO_SKILL['{$k}'] = ['m_hero_skil_pk' => {$r['m_hero_skil_pk']}, 'type' => '{$r['type']}', 'rare' => {$r['rare']}, 'title' => '{$r['title']}', 'condition' => '{$r['condition']}', 'use_slot_count' => {$r['use_slot_count']}, 'exericised_rate' => '{$r['exericised_rate']}', 'leadership' => '{$r['leadership']}', 'mil_force' => '{$r['mil_force']}', 'intellect' => '{$r['intellect']}', 'politics' => '{$r['politics']}', 'charm' => '{$r['charm']}', 'description' => '{$r['description']}', 'description_detail' => '{$r['description_detail']}', 'set_count' => {$r['set_count']}, 'left_count' => {$r['left_count']}, 'yn_trade' => '{$r['yn_trade']}', 'description_quickuse' => '{$r['description_quickuse']}', 'm_cmd_pk' => '{$r['m_cmd_pk']}', 'effect_type' => '{$r['effect_type']}', 'effect_value' => '{$r['effect_value']}', 'exercise_type' => '{$r['exercise_type']}', 'm_hero_skil_cmd_rate_pk' => '{$r['m_hero_skil_cmd_rate_pk']}', 'stat_type' => '{$r['stat_type']}', 'battle_type' => '{$r['battle_type']}', 'yn_lord_skill' => '{$r['yn_lord_skill']}', 'skill_type' => '{$r['skill_type']}'];

EOF;
}

