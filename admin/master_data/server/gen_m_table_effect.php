<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_EFFE'] = [];
\$_M_EFFE = &\$GLOBALS['_M_EFFE'];
\$_M['EFFE'] = &\$_M_EFFE;


EOF;

$note = 'm_effe';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// all effect data
$sql = <<< EOF
SELECT 'hero_assign'::text AS src, m_hero_assi_pk::text AS pk, effect_type AS e_t, effect_value AS e_v, value_type AS v_t, calc_type AS c_t FROM m_hero_assign_effect
UNION ALL
SELECT 'hero_skill'::text AS src, m_hero_skil_pk::text AS pk, effect_type AS e_t, effect_value AS e_v, value_type AS v_t, calc_type AS c_t FROM m_hero_skill_effect
UNION ALL
SELECT 'item'::text AS src, m_item_pk::text AS pk, effect_type AS e_t, effect_value AS e_v, value_type AS v_t, calc_type AS c_t FROM m_item_effect
UNION ALL
SELECT 'tech'::text AS src, m_tech_pk||'_'||lpad(level::text, 2, '0')::text AS pk, effect_type AS e_t, effect_value AS e_v, value_type AS v_t, calc_type AS c_t FROM m_technique_effect
ORDER BY e_t, pk

EOF;

$PgGame->query($sql);

$arr = [];

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	$arr[$r['e_t']][$r['pk']] = $r;
}

foreach ($arr AS $k => $v)
{
	echo "\$_M_EFFE['{$k}'] =\n[\n";

	$arr2 = [];
	foreach ($v AS $k2 => $v2)
	{
		$arr2[] = "'{$k2}' => ['src' => '{$v2['src']}', 'e_v' => {$v2['e_v']}, 'v_t' => '{$v2['v_t']}', 'c_t' => '{$v2['c_t']}']";
	}
	echo implode(',', $arr2);

	echo "\n];\n";
}
