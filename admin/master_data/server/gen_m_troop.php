<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_TROOP'] = [];
\$_M_TROOP = &\$GLOBALS['_M_TROOP'];
\$_M['TROOP'] = &\$_M_TROOP;


EOF;

$note = 'm_troop';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_troop
$PgGame->query('SELECT type, cond_value, value FROM m_troop ORDER BY type, cond_value');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['type'];

	echo <<< EOF
\$_M_TROOP['{$r['type']}']['{$r['cond_value']}'] = '{$r['value']}';

EOF;
}