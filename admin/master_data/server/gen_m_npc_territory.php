<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_NPC_TERR'] = [];
\$_M_NPC_TERR = &\$GLOBALS['_M_NPC_TERR'];
\$_M['NPC_TERR'] = &\$_M_NPC_TERR;


EOF;

$note = 'm_npc_terr';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_npc_terr
$PgGame->query('SELECT * FROM m_npc_territory');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	$zArr = [];

	foreach ($r AS $k => $v)
	{
		if ($k != 'regist_dt') {
			if (is_int($v)) {
				$zArr[] = "'$k' => $v";
			} else {
				$zArr[] = "'$k' => '$v'";
			}
		}
	}

	$z = implode(',', $zArr);

	echo <<< EOF
\$_M_NPC_TERR['{$r['level']}'] = [$z];

EOF;
}