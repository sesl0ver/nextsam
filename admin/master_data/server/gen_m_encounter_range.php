<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_ENCO'] = [];
\$_M_ENCO = &\$GLOBALS['_M_ENCO'];
\$_M['ENCO'] = &\$_M_ENCO;


EOF;

$note = 'm_enco';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_encounter_range
$PgGame->query('SELECT type, encounter_type, yn_item, recalc_rate, rate FROM m_encounter_range');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_ENCO['{$r['type']}']['{$r['yn_item']}']['{$r['encounter_type']}'] = ['recalc_rate' => {$r['recalc_rate']}, 'rate' => {$r['rate']}];

EOF;
}

