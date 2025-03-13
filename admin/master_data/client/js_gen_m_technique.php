<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.tech={};';

// m_technique
$PgGame->query('SELECT m_tech_pk, code, title, max_level, description, description_detail, orderno FROM m_technique ORDER BY orderno DESC');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_tech_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.tech['{$k}'] = {$json};";
	echo "ns_cs.m.tech['{$k}'].level = {};";
	echo "ns_cs.m.tech['{$r['code']}'] = ns_cs.m.tech['{$k}'];";
}

// m_technique_level
$PgGame->query('SELECT m_tech_pk, level, m_cond_pk, description, description_effect FROM m_technique_level');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	$json = json_encode($r);

	echo "ns_cs.m.tech['{$r['m_tech_pk']}'].level['{$r['level']}'] = {$json};";
}

echo '}';
