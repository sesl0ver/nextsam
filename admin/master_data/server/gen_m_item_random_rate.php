<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_ITEM_RAND_RATE'] = [];
\$_M_ITEM_RAND_RATE = &\$GLOBALS['_M_ITEM_RAND_RATE'];
\$_M['ITEM_RAND_RATE'] = &\$_M_ITEM_RAND_RATE;


EOF;

$note = 'm_item_rand_rate';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_item_random_rate
$PgGame->query('SELECT m_item_pk, result_m_item_pk, result_item_quantity, recalc_rate, rate FROM m_item_random_rate');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_ITEM_RAND_RATE['{$r['m_item_pk']}']['{$r['result_m_item_pk']}']= ['result_item_quantity' => {$r['result_item_quantity']}, 'recalc_rate' => {$r['recalc_rate']}, 'rate' => {$r['rate']}];

EOF;
}

