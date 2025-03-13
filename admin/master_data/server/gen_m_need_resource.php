<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_NEED_RESO'] = [];
\$_M_NEED_RESO = &\$GLOBALS['_M_NEED_RESO'];
\$_M['NEED_RESO'] = &\$_M_NEED_RESO;


EOF;

$note = 'm_need_resource';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_need_resource
$PgGame->query('SELECT * FROM m_need_resource ORDER BY need_type, rare_type, level');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	if (!$r['need_item'])
		$r['need_item'] = 'none';

	echo <<< EOF
\$_M_NEED_RESO['{$r['need_type']}']['{$r['rare_type']}']['{$r['level']}'] = ['need_item' => '{$r['need_item']}', 'need_gold' => {$r['need_gold']}];

EOF;
}