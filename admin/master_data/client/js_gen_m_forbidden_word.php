<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.forb={};';

// m_forbidden_word
$PgGame->query('SELECT word, regist_by FROM m_forbidden_word ORDER BY length(word) DESC, word ASC');

$already = [];

$cnt = 0;
while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = $cnt++;

	$json = json_encode($r);

	echo "ns_cs.m.forb['{$k}'] = {$json};";
}

echo '}';
