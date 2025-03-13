<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_SKILL_EXP'] = [];
\$_M_HERO_SKILL_EXP = &\$GLOBALS['_M_HERO_SKILL_EXP'];
\$_M['HERO_SKILL_EXP'] = &\$_M_HERO_SKILL_EXP;


EOF;

$note = 'm_hero_skill_exp';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_HERO_SKILL_EXP
$PgGame->query('SELECT level, exp FROM m_hero_skill_exp ORDER BY level');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_HERO_SKILL_EXP['{$r['level']}'] = ['level' => '{$r['level']}', 'exp' => '{$r['exp']}'];

EOF;
}

