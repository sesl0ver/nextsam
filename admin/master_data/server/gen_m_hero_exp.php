<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_EXP'] = [];
\$_M_HERO_SKILL_EXP = &\$GLOBALS['_M_HERO_EXP'];
\$_M['HERO_EXP'] = &\$_M_HERO_EXP;


EOF;

$note = 'm_hero_exp';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_HERO_SKILL_EXP
$PgGame->query('SELECT rare_type, level, need_exp, acquire_exp FROM m_hero_exp ORDER BY rare_type, level');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
    if ($r['level'] == 1) {
        echo "\$_M_HERO_EXP['{$r['rare_type']}'] = [];";
    }
	echo "\$_M_HERO_EXP['{$r['rare_type']}']['{$r['level']}'] = ['level' => '{$r['level']}', 'need_exp' => '{$r['need_exp']}', 'acquire_exp' => '{$r['acquire_exp']}'];";
}

