<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_POIN_REWA'] = [];
\$_M_POIN_REWA = &\$GLOBALS['_M_POIN_REWA'];
\$_M['POIN_REWA'] = &\$_M_POIN_REWA;


EOF;

$note = 'm_POIN_REWA';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_POIN_REWA
$PgGame->query('SELECT * FROM m_point_reward');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_POIN_REWA['{$r['m_rank_pk']}'] = ['hero' => '{$r['hero']}', 'rare' => '{$r['rare']}', 'count' => '{$r['count']}', 'skill' => '{$r['skill']}', 'army' => '{$r['army']}', 'item' => '{$r['item']}', 'reso' => '{$r['reso']}'];

EOF;
}