<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.offi={};';

// m_officer
$PgGame->query('SELECT * FROM m_officer ORDER BY orderno ASC');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_offi_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.offi['{$k}'] = {$json};";
}

echo '}';
