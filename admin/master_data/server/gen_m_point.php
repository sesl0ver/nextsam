<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_POIN'] = [];
\$_M_POIN = &\$GLOBALS['_M_POIN'];
\$_M['POIN'] = &\$_M_POIN;


EOF;

$note = 'm_poin';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_POIN
$PgGame->query('SELECT * FROM m_point');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_POIN['{$r['m_posi_pk']}'] = ['state' => {$r['state']}, 'level' => {$r['level']}];

EOF;
}