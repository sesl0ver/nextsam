<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_PROD_VALL'] = [];
\$_M_PROD_VALL = &\$GLOBALS['_M_PROD_VALL'];
\$_M['PROD_VALL'] = &\$_M_PROD_VALL;


EOF;

$note = 'm_prod_vall';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_productivity_valley
$PgGame->query('SELECT * FROM m_productivity_valley');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['valley_type'];

	echo <<< EOF
\$_M_PROD_VALL['{$k}']['{$r['level']}'] = ['labor_force' => {$r['labor_force']}, 'food' => {$r['food']}, 'horse' => {$r['horse']}, 'lumber' => {$r['lumber']}, 'iron' => {$r['iron']}, 'occupation_point' => {$r['occupation_point']}];

EOF;
}