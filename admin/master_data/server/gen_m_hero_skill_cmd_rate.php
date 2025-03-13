<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_SKILL_CMD_RATE'] = [];
\$_M_HERO_SKILL_CMD_RATE = &\$GLOBALS['_M_HERO_SKILL_CMD_RATE'];
\$_M['HERO_SKILL_CMD_RATE'] = &\$_M_HERO_SKILL_CMD_RATE;


EOF;

$note = 'm_hero_skill_cmd_rate';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_HERO_ACQUIRED_LEVEL
$PgGame->query('SELECT m_hero_skil_cmd_rate_pk, stat_step, stat_type, (add_rate * 1000) as add_rate FROM m_hero_skill_cmd_rate ORDER BY m_hero_skil_cmd_rate_pk, stat_step');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_HERO_SKILL_CMD_RATE['{$r['m_hero_skil_cmd_rate_pk']}']['{$r['stat_step']}'] = {$r['add_rate']};

EOF;
}

