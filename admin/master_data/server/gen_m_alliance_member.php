<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_ALLI_MEMB'] = [];
\$_M_ALLI_MEMB = &\$GLOBALS['_M_ALLI_MEMB'];
\$_M['ALLI_MEMB'] = &\$_M_ALLI_MEMB;


EOF;

$note = 'm_alli_memb';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_ALLI_MEMB
$PgGame->query('SELECT level, member FROM m_alliance_member ORDER BY level');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_ALLI_MEMB['{$r['level']}'] = ['level' => '{$r['level']}', 'member' => '{$r['member']}'];

EOF;
}


