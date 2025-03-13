<?php
// TODO 세력도 삭제로 사용안함.
/*set_time_limit(3600);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

$start = Useful::microtimeFloat();

if (!is_dir(CONF_FORCEMAP)) { mkdir(CONF_FORCEMAP, 0777, true); }

$ver = (!$_GET['ver']) ? $argv[1] : $_GET['ver'];
if (!$ver) { echo "need version # first argument : version\n"; exit(1); }

define('CONF_FORCEMAP_PATH', CONF_FORCEMAP.$ver.'/');

if (!is_dir(CONF_FORCEMAP_PATH)) { mkdir(CONF_FORCEMAP_PATH, 0777, true); }
if (!is_dir(CONF_FORCEMAP_PATH.'map_all/')) { mkdir(CONF_FORCEMAP_PATH.'map_all/', 0777, true); }
if (!is_dir(CONF_FORCEMAP_PATH.'map_alli/')) { mkdir(CONF_FORCEMAP_PATH.'map_alli/', 0777, true); }
if (!is_dir(CONF_FORCEMAP_PATH.'area_map_all/')) { mkdir(CONF_FORCEMAP_PATH.'area_map_all/', 0777, true); }
if (!is_dir(CONF_FORCEMAP_PATH.'area_map_alli/')) { mkdir(CONF_FORCEMAP_PATH.'area_map_alli/', 0777, true); }
if (!is_dir(CONF_FORCEMAP_PATH.'area_map_base/')) { mkdir(CONF_FORCEMAP_PATH.'area_map_base/', 0777, true); }
if (!is_dir(CONF_FORCEMAP_PATH.'area_map_no_alli/')) { mkdir(CONF_FORCEMAP_PATH.'area_map_no_alli/', 0777, true); }
if (!is_dir(CONF_FORCEMAP_PATH.'js/')) { mkdir(CONF_FORCEMAP_PATH.'js/', 0777, true); }

$Session = new CSession(false);
$Db = new CPgsql('DEF');
$Power = new CPower($Session, $Db);

// 세력지도에 쓰일 동맹 관계 사본 저장
$Db->query('TRUNCATE TABLE forcemap_alli_rel');
$Db->query('INSERT INTO forcemap_alli_rel SELECT alli_pk, rel_alli_pk, rel_type FROM alliance_relation');

// 세력지도
// 지배 동맹 : 해당 area에서 영향력 합산이 제일 큰 동맹 (동맹이 없는 군주는 혼자를 하나의 동맹이라고 침)
// 지배 군주 : 지배 동맹에서 영향력이 제일 큰 군주
// 1-3위 군주 : 해당 area에서 영향력 합산이 1-3위인 군주
$sql = <<<EOF
SELECT
	position.posi_pk, position.posi_area_pk, lord.lord_pk, lord.lord_name, lord.alli_pk, alliance.title, territory.power
FROM
	position, territory, lord
	LEFT OUTER JOIN alliance ON lord.alli_pk = alliance.alli_pk
WHERE
	position.type = 'T' AND
	position.lord_pk = lord.lord_pk AND
	position.posi_pk = territory.posi_pk
EOF;

$Db->query($sql);
$Db->fetchAll();
$territory = $Db->rows;

$area_total_power = Array(); // [posi_area_pk] = total_power
$area_alli_total_power = Array(); // [posi_area_pk][alli_pk] = alli_total_power

$area_alli_lord_power = Array(); // [posi_area_pk][alli_pk][lord_pk] = lord_power
$area_lord_power = Array(); // [posi_area_pk][lord_pk] = lord_power

$alli_name_list = Array();
$lord_name_list = Array();
$lord_own_alli = Array(); // [lord_pk] = alli_pk

$result = Array();

$popup_area_info = Array();

if (is_array($territory) && count($territory) > 0)
{
	foreach($territory as &$v)
	{
		$_posi_area_pk = $v['posi_area_pk'];

		if (!isset($area_total_power[$_posi_area_pk])) { $area_total_power[$_posi_area_pk] = 0; }
		if (!isset($area_alli_total_power[$_posi_area_pk])) { $area_alli_total_power[$_posi_area_pk] = Array(); }
		if (!isset($area_alli_lord_power[$_posi_area_pk])) { $area_alli_lord_power[$_posi_area_pk] = Array(); }
		if (!isset($area_lord_power[$_posi_area_pk])) { $area_lord_power[$_posi_area_pk] = Array(); }

		$area_total_power[$_posi_area_pk] += $v['power'];

		$_lord_pk = $v['lord_pk'];
		$_alli_pk = (!$v['alli_pk']) ? 0 : $v['alli_pk'];
		$_uk_alli_pk = (!$v['alli_pk']) ? ('lord_'.$_lord_pk) : ('alli_'.$v['alli_pk']);

		if (!isset($alli_name_list[$_alli_pk])) { $alli_name_list[$_alli_pk] = (!$v['title']) ? '' : $v['title']; }
		if (!isset($lord_name_list[$_lord_pk])) { $lord_name_list[$_lord_pk] = $v['lord_name']; }

		if (!isset($area_alli_total_power[$_posi_area_pk][$_uk_alli_pk])) { $area_alli_total_power[$_posi_area_pk][$_uk_alli_pk] = 0; }
		if (!isset($area_alli_lord_power[$_posi_area_pk][$_uk_alli_pk])) { $area_alli_lord_power[$_posi_area_pk][$_uk_alli_pk] = Array(); }
		if (!isset($area_lord_power[$_posi_area_pk][$_lord_pk])) { $area_lord_power[$_posi_area_pk][$_lord_pk] = 0; }

		$area_alli_total_power[$_posi_area_pk][$_uk_alli_pk] += $v['power'];
		$area_lord_power[$_posi_area_pk][$_lord_pk] += $v['power'];

		$lord_own_alli[$_lord_pk] = $_alli_pk;

		if (!isset($area_alli_lord_power[$_posi_area_pk][$_uk_alli_pk][$_lord_pk])) { $area_alli_lord_power[$_posi_area_pk][$_uk_alli_pk][$_lord_pk] = 0; }

		$area_alli_lord_power[$_posi_area_pk][$_uk_alli_pk][$_lord_pk] += $v['power'];
	}

	foreach($area_total_power as $k => &$v)
	{
		arsort($area_alli_total_power[$k]); // 지배 동맹를 뽑기 위해 정렬
		arsort($area_lord_power[$k]); // 1-3위 군주 뽑기 위해 정렬
	}

	$count = 0;
	foreach($area_total_power as $k => &$v)
	{
		$t = Array();
		$t['posi_area_pk'] = $k;
		$t['total_power'] = $v;

		$j =& $area_alli_total_power[$k];
		reset($j); // 지배 동맹 1위
		$first_dominate_alli_pk = substr(key($j), 5);

		if (substr(key($j), 0, 5) == 'lord_')
		{
			$first_dominate_alli_pk = 0;
		}

		$t['first_dominate_alli_pk'] = $first_dominate_alli_pk;
		$t['first_dominate_alli_title'] = $alli_name_list[$first_dominate_alli_pk];
		$t['first_dominate_alli_power'] = current($j);

		arsort($area_alli_lord_power[$k][key($j)]);
		$l =& $area_alli_lord_power[$k][key($j)];
		reset($l);

		$first_dominate_lord_pk = key($l);
		$t['first_dominate_lord_pk'] = $first_dominate_lord_pk;
		$t['first_dominate_lord_name'] = $lord_name_list[$first_dominate_lord_pk];
		$t['first_dominate_lord_power'] = current($l);

		if (next($j) !== false) // 지배 동맹 2위
		{
			$secon_dominate_alli_pk = substr(key($j), 5);

			if (substr(key($j), 0, 5) == 'lord_')
			{
				$secon_dominate_alli_pk = 0;
			}

			$t['secon_dominate_alli_pk'] = $secon_dominate_alli_pk;
			$t['secon_dominate_alli_title'] = $alli_name_list[$secon_dominate_alli_pk];
			$t['secon_dominate_alli_power'] = current($j);

			arsort($area_alli_lord_power[$k][key($j)]);
			$l =& $area_alli_lord_power[$k][key($j)];
			reset($l);

			$secon_dominate_lord_pk = key($l);
			$t['secon_dominate_lord_pk'] = $secon_dominate_lord_pk;
			$t['secon_dominate_lord_name'] = $lord_name_list[$secon_dominate_lord_pk];
			$t['secon_dominate_lord_power'] = current($l);
		}

		$i =& $area_lord_power[$k];
		reset($i); // 1위 군주

		$first_alli_pk = $lord_own_alli[key($i)];
		$t['first_alli_pk'] = $first_alli_pk;
		$t['first_alli_title'] = $alli_name_list[$first_alli_pk];
		$t['first_power'] = current($i);
		$first_lord_pk = key($i);
		$t['first_lord_pk'] = $first_lord_pk;
		$t['first_lord_name'] = $lord_name_list[$first_lord_pk];

		if (next($i) !== false) // 2위 군주
		{
			$secon_alli_pk = $lord_own_alli[key($i)];
			$t['secon_alli_pk'] = $secon_alli_pk;
			$t['secon_alli_title'] = $alli_name_list[$secon_alli_pk];
			$t['secon_power'] = current($i);
			$secon_lord_pk = key($i);
			$t['secon_lord_pk'] = $secon_lord_pk;
			$t['secon_lord_name'] = $lord_name_list[$secon_lord_pk];

			if (next($i) !== false) // 3위 군주
			{
				$third_alli_pk = $lord_own_alli[key($i)];
				$t['third_alli_pk'] = $third_alli_pk;
				$t['third_alli_title'] = $alli_name_list[$third_alli_pk];
				$t['third_power'] = current($i);
				$third_lord_pk = key($i);
				$t['third_lord_pk'] = $third_lord_pk;
				$t['third_lord_name'] = $lord_name_list[$third_lord_pk];
			}
		}

		$result[$k] = $t;
		flush();
	}


	$Db->query('TRUNCATE TABLE forcemap_all');

	foreach($result as $k => &$v)
	{
		$sql = <<<EOF
INSERT INTO
	forcemap_all
	(
		posi_area_pk, total_power,

		first_dominate_alli_pk, first_dominate_alli_title, first_dominate_alli_power,
		first_dominate_lord_pk, first_dominate_lord_name, first_dominate_lord_power,

		secon_dominate_alli_pk, secon_dominate_alli_title, secon_dominate_alli_power,
		secon_dominate_lord_pk, secon_dominate_lord_name, secon_dominate_lord_power,

		first_lord_pk, first_lord_name, first_alli_pk, first_alli_title, first_power,
		secon_lord_pk, secon_lord_name, secon_alli_pk, secon_alli_title, secon_power,
		third_lord_pk, third_lord_name, third_alli_pk, third_alli_title, third_power
	)
VALUES
(
	$1, $2,

	$3, $4, $5,
	$6, $7, $8,

	$9, $10, $11,
	$12, $13, $14,

	$15, $16, $17, $18, $19,
	$20, $21, $22, $23, $24,
	$25, $26, $27, $28, $29
)
EOF;
		$query_params = Array(
			$v['posi_area_pk'], $v['total_power'],
			$v['first_dominate_alli_pk'], $v['first_dominate_alli_title'], $v['first_dominate_alli_power'],
			$v['first_dominate_lord_pk'], $v['first_dominate_lord_name'], $v['first_dominate_lord_power'],
			$v['secon_dominate_alli_pk'], $v['secon_dominate_alli_title'], $v['secon_dominate_alli_power'],
			$v['secon_dominate_lord_pk'], $v['secon_dominate_lord_name'], $v['secon_dominate_lord_power'],
			$v['first_lord_pk'], $v['first_lord_name'], $v['first_alli_pk'], $v['first_alli_title'], $v['first_power'],
			$v['secon_lord_pk'], $v['secon_lord_name'], $v['secon_alli_pk'], $v['secon_alli_title'], $v['secon_power'],
			$v['third_lord_pk'], $v['third_lord_name'], $v['third_alli_pk'], $v['third_alli_title'], $v['third_power']
		);
		$Db->query($sql, $query_params);
		$popup_area_info[$v['posi_area_pk']] = Array(
			'posi_area_pk' => $v['posi_area_pk'], 'total_power' => $v['total_power'],
			//'first_dominate_alli_pk' => $v['first_dominate_alli_pk'], 'first_dominate_alli_title' => $v['first_dominate_alli_title'], 'first_dominate_alli_power' => $v['first_dominate_alli_power'],
			//'first_dominate_lord_pk' => $v['first_dominate_lord_pk'], 'first_dominate_lord_name' => $v['first_dominate_lord_name'], 'first_dominate_lord_power' => $v['first_dominate_lord_power'],
			'first_lord_pk' => $v['first_lord_pk'], 'first_lord_name' => $v['first_lord_name'], 'first_alli_pk' => $v['first_alli_pk'], 'first_alli_title' => $v['first_alli_title'], 'first_power' => $v['first_power'],
			'secon_lord_pk' => $v['secon_lord_pk'], 'secon_lord_name' => $v['secon_lord_name'], 'secon_alli_pk' => $v['secon_alli_pk'], 'secon_alli_title' => $v['secon_alli_title'], 'secon_power' => $v['secon_power'],
			'third_lord_pk' => $v['third_lord_pk'], 'third_lord_name' => $v['third_lord_name'], 'third_alli_pk' => $v['third_alli_pk'], 'third_alli_title' => $v['third_alli_title'], 'third_power' => $v['third_power'],
		);
	}



	$cv = new CCanvas();
	$fm = new CForceMap($Db, $cv);
	$fa = new CForceArea($Db, $fm->getToRela(), $fm->getColorSet(), $fm->getBorderColorSet());

	$end = microtime_float();

	$res = Array();
	$res['qbw_cmd_return'] = Array();
	$res['qbw_cmd_return']['code'] = 'OK';
	$res['qbw_cmd_return']['mesg'] = null;
	$res['qbw_cmd_return']['add_data'] = Array();
	$res['qbw_cmd_return']['add_data']['map_area_info'] = $popup_area_info;
	$res['qbw_cmd_return']['add_data']['map_all'] = $fm->map_all_data;
	$res['qbw_cmd_return']['add_data']['area_alli'] = $fa->each_alli_in_area;
	$res['qbw_cmd_return']['add_data']['offset'] = $cv->offsetData;
	$fp = fopen(CONF_FORCEMAP_PATH.'js/forcemap_info.js', 'w');
	fwrite($fp, unicode_escape_sequences(json_encode($res)));
	fclose($fp);

	// 동맹 pk , 동맹명 , 동맹 랭킹 , 동맹의 관계 목록(동맹pk, 동맹명, 랭킹 , 관계타입) , 이 동맹이 따로 전체맵 이미지를 가져가야되는지?

	$Db->query('SELECT count(posi_area_pk) AS cnt FROM forcemap_all');
	$tile_count = $Db->fetchOne();

	$Db->query('SELECT alliance.alli_pk, alliance.title, ranking_alliance.power_rank FROM alliance, ranking_alliance WHERE alliance.alli_pk = ranking_alliance.alli_pk ORDER BY ranking_alliance.power_rank');
	$Db->fetchAll();

	$alli_list = Array();

	$from_rela_data = $fm->from_rela_data;

	if (is_array($Db->rows) && count($Db->rows) > 0)
	{
		foreach($Db->rows as $v)
		{
			$alli_list[$v['alli_pk']] = Array('alli_pk' => $v['alli_pk'], 'ranking' => $v['power_rank'], 'title' => $v['title']);
		}

		foreach($alli_list as $alli_pk => $alli)
		{
			$res = Array();
			$res['qbw_cmd_return'] = Array();
			$res['qbw_cmd_return']['code'] = 'OK';
			$res['qbw_cmd_return']['mesg'] = null;
			$res['qbw_cmd_return']['add_data'] = Array();

			$res['qbw_cmd_return']['add_data']['alli_info'] = $alli;
			$res['qbw_cmd_return']['add_data']['relation'] = Array();

			$related_alli = Array();
			array_push($related_alli, $alli_pk);

			if (is_array($from_rela_data[$alli_pk]) && count($from_rela_data[$alli_pk]) > 0)
			{
				foreach($from_rela_data[$alli_pk] as $rel_alli_pk => $rel_type)
				{
					array_push($related_alli, $rel_alli_pk);
					$rel_info = $alli_list[$rel_alli_pk];
					$rel_info['rel_type'] = $rel_type;
					$res['qbw_cmd_return']['add_data']['relation'][$rel_alli_pk] = $rel_info;
				}
			}

			$cnt = 0;

			foreach($result as $_area)
			{
				if (in_array($_area['first_alli_pk'], $related_alli))
				{
					++$cnt;
				}
			}

			$res['qbw_cmd_return']['add_data']['tile_count'] = $tile_count;
			$res['qbw_cmd_return']['add_data']['need_map_cnt'] = $cnt;

			$fp = fopen(CONF_FORCEMAP_PATH.'js/'.$alli_pk.'.js', 'w');
			fwrite($fp, unicode_escape_sequences(json_encode($res)));
			fclose($fp);
		}
	}

	//$fm->drawEachForce();
	//$fm->drawEachAlliForce();
	//$fm->drawNoRelaForce();
	//$fa->drawEachArea(1, 2);
}

$end = microtime_float();

echo 'JSON MAKE : ' . ($end-$start) . ' SEC'."\n";*/
