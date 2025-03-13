<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_NPC_HERO'] = [];
\$_M_NPC_HERO = &\$GLOBALS['_M_NPC_HERO'];
\$_M['NPC_HERO'] = &\$_M_NPC_HERO;


EOF;

$note = 'm_npc_hero';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_npc_hero
$PgGame->query('SELECT * FROM m_npc_hero');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_NPC_HERO['{$r['type']}'][{$r['level']}][] = {$r['hero_pk']};

EOF;
}