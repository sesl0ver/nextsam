<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_PROD_BUIL'] = [];
\$_M_PROD_BUIL = &\$GLOBALS['_M_PROD_BUIL'];
\$_M['PROD_BUIL'] = &\$_M_PROD_BUIL;


EOF;

$note = 'm_prod_buil';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_productivity_building
$PgGame->query('SELECT * FROM m_productivity_building');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_buil_pk'];

	echo <<< EOF
\$_M_PROD_BUIL['{$k}']['{$r['level']}'] = ['labor_force' => {$r['labor_force']}, 'food' => {$r['food']}, 'horse' => {$r['horse']}, 'lumber' => {$r['lumber']}, 'iron' => {$r['iron']}];

EOF;
}
