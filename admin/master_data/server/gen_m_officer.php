<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_OFFI'] = [];
\$_M_OFFI = &\$GLOBALS['_M_OFFI'];
\$_M['OFFI'] = &\$_M_OFFI;


EOF;

$note = 'm_offi';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_officer
$PgGame->query('SELECT * FROM m_officer ORDER BY orderno');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_offi_pk'];

	echo <<< EOF
\$_M_OFFI['{$k}'] = ['m_offi_pk' => {$r['m_offi_pk']}, 'title' => '{$r['title']}', 'title_hanja' => '{$r['title_hanja']}', 'employment_fee' => '{$r['employment_fee']}', 'active_level' => {$r['active_level']}, 'orderno' => {$r['orderno']}, 'stat_plus_leadership' => {$r['stat_plus_leadership']}, 'stat_plus_mil_force' => {$r['stat_plus_mil_force']}, 'stat_plus_intellect' => {$r['stat_plus_intellect']}, 'stat_plus_politics' => {$r['stat_plus_politics']}, 'stat_plus_charm' => {$r['stat_plus_charm']}, 'restriction_level' => {$r['restriction_level']}, 'restriction_leadership' => {$r['restriction_leadership']}, 'restriction_mil_force' => {$r['restriction_mil_force']}, 'restriction_intellect' => {$r['restriction_intellect']}, 'restriction_politics' => {$r['restriction_politics']}, 'restriction_charm' => {$r['restriction_charm']}, 'description' => '{$r['description']}', 'description_detail' => '{$r['description_detail']}'];

EOF;
}