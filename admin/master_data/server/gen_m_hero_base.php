<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_BASE'] = [];
\$_M_HERO_BASE = &\$GLOBALS['_M_HERO_BASE'];
\$_M['HERO_BASE'] = &\$_M_HERO_BASE;


EOF;

$note = 'm_hero_base';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_hero_base
$PgGame->query('SELECT * FROM m_hero_base');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_hero_base_pk'];

	echo <<< EOF
\$_M_HERO_BASE['{$k}'] = ['m_hero_base_pk' => {$r['m_hero_base_pk']}, 'name' => '{$r['name']}', 'type' => '{$r['type']}', 'rare_type' => '{$r['rare_type']}', 'gender' => '{$r['gender']}', 'forces' => '{$r['forces']}', 'mil_aptitude_infantry' => '{$r['mil_aptitude_infantry']}', 'mil_aptitude_spearman' => '{$r['mil_aptitude_spearman']}', 'mil_aptitude_pikeman' => '{$r['mil_aptitude_pikeman']}', 'mil_aptitude_archer' => '{$r['mil_aptitude_archer']}', 'mil_aptitude_horseman' => '{$r['mil_aptitude_horseman']}', 'mil_aptitude_siege' => '{$r['mil_aptitude_siege']}', 'm_hero_skil_pk_1' => '{$r['m_hero_skil_pk_1']}', 'm_hero_skil_pk_2' => '{$r['m_hero_skil_pk_2']}', 'm_hero_skil_pk_3' => '{$r['m_hero_skil_pk_3']}', 'm_hero_skil_pk_4' => '{$r['m_hero_skil_pk_4']}', 'm_hero_skil_pk_5' => '{$r['m_hero_skil_pk_5']}', 'm_hero_skil_pk_6' => '{$r['m_hero_skil_pk_6']}', 'm_hero_skil_pk_7' => '{$r['m_hero_skil_pk_7']}', 'm_hero_skil_pk_8' => '{$r['m_hero_skil_pk_8']}', 'over_type' => '{$r['over_type']}', 'over_hero_duration' => {$r['over_hero_duration']}, 'yn_new_gacha' => '{$r['yn_new_gacha']}', 'yn_modifier' => '{$r['yn_modifier']}'];

EOF;
}


