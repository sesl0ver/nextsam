<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_TABLE_HERO_ACQUIRED'] = [];
\$_M_TABLE_HERO_ACQUIRED = &\$GLOBALS['_M_TABLE_HERO_ACQUIRED'];
\$_M['TABLE_HERO_ACQUIRED'] = &\$_M_TABLE_HERO_ACQUIRED;


EOF;

$note = 'm_table_hero_acquired';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// 뽑기의 카드결정 관련 테이블
$PgGame->query('SELECT * FROM m_hero');

// 컬럼들 처리를 위해서...
$types= ['regist', 'free', 'encounter', 'combination', 'quest'];

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_hero_pk'];

	$table = [];
	$rate = [];

	for ($i = 0, $i_l = COUNT($types); $i < $i_l; $i++)
	{
		$type = &$types[$i];

		$col = 'yn_acquired_'. $type;

		if (isset($r[$col]) && $r[$col] == 'Y')
		{
			$table[] = $type;
			$rate[] = $r['acquired_rate_'. $type];
		}
	}

	for ($i = 0, $i_l = COUNT($table); $i < $i_l; $i++)
	{
		echo <<< EOF
\$_M_TABLE_HERO_ACQUIRED['{$table[$i]}'][{$r['level']}]['{$r['rare_type']}'][] = [{$r['m_hero_pk']}, $rate[$i]];

EOF;
	}
}