<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO'] = [];
\$_M_HERO = &\$GLOBALS['_M_HERO'];
\$_M['HERO'] = &\$_M_HERO;


EOF;

$note = 'm_hero';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_hero
$PgGame->query('SELECT * FROM m_hero');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_hero_pk'];

	echo <<< EOF
\$_M_HERO['{$k}'] = ['m_hero_pk' => {$r['m_hero_pk']}, 'm_hero_base_pk' => {$r['m_hero_base_pk']}, 'level' => {$r['level']}, 'loyalty' => {$r['loyalty']}, 'hp' => {$r['hp']}, 'leadership' => {$r['leadership']}, 'mil_force' => {$r['mil_force']}, 'intellect' => {$r['intellect']}, 'politics' => {$r['politics']}, 'charm' => {$r['charm']}, 'amount' => {$r['amount']}, 'over_type' => '{$r['over_type']}', 'over_hero_duration' => {$r['over_hero_duration']}, 'acquire_exp' => {$r['acquire_exp']}, 'need_exp' => {$r['need_exp']}];

EOF;
}

