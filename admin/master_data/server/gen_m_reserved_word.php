<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_RESE'] = [];
\$_M_RESE = &\$GLOBALS['_M_RESE'];
\$_M['RESE'] = &\$_M_RESE;


EOF;

$note = 'm_rese';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_reserved_word
$PgGame->query('SELECT word FROM m_reserved_word');
$cnt = 0;
while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_RESE[{$cnt}] = ['word' => '{$r['word']}'];

EOF;

	$cnt++;
}