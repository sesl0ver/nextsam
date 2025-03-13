<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_RESE'] = [];
\$_M_FORB = &\$GLOBALS['_M_FORB'];
\$_M['FORB'] = &\$_M_FORB;


EOF;

$note = 'm_forb';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_forbidden_word
$PgGame->query('SELECT word FROM m_forbidden_word ORDER BY length(word) DESC, word');
$cnt = 0;
while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_FORB[{$cnt}] = ['word' => '{$r['word']}'];
EOF;

	$cnt++;
}

