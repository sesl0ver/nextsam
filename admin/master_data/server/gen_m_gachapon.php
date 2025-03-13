<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_GACH'] = [];
\$_M_GACH = &\$GLOBALS['_M_GACH'];
\$_M['GACH'] = &\$_M_GACH;


EOF;

$note = 'm_gachapon';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_GACH
$PgGame->query('SELECT m_item_pk, m_hero_base_pk, orderno FROM m_gachapon ORDER BY m_item_pk, orderno');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_GACH['{$r['m_item_pk']}']['{$r['m_hero_base_pk']}'] = ['m_hero_base_pk' => '{$r['m_hero_base_pk']}', 'orderno' => '{$r['orderno']}'];

EOF;
}


