<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_ENCOUNTER_HERO_LEVEL'] = [];
\$_M_HERO_ENCOUNTER_HERO_LEVEL = &\$GLOBALS['_M_HERO_ENCOUNTER_HERO_LEVEL'];
\$_M['HERO_ENCOUNTER_HERO_LEVEL'] = &\$_M_HERO_ENCOUNTER_HERO_LEVEL;


EOF;

$note = 'm_hero_encounter_hero_level';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_HERO_ACQUIRED_LEVEL
$PgGame->query('SELECT yn_item, charm, level, (rate * 1000) as rate FROM m_hero_encounter_hero_level ORDER BY yn_item, charm, level');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_HERO_ENCOUNTER_HERO_LEVEL['{$r['yn_item']}']['{$r['charm']}']['{$r['level']}'] = {$r['rate']};

EOF;
}

