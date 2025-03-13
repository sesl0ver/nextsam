<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.hero_combination_rare = new Object();';

// m_hero_combination_rare
$PgGame->query('SELECT * FROM m_hero_combination_rare order by combi_score, rare_type');

$already = [];
while ($PgGame->fetch()) {
    $r = &$PgGame->row;
    $k = &$r['combi_score'];
    $k2 = &$r['rare_type'];

    $json = json_encode($r);

    if (! array_key_exists($k, $already)) {
        echo "ns_cs.m.hero_combination_rare['$k'] = {};";
        $already[$k] = true;
    }

    echo "ns_cs.m.hero_combination_rare['$k']['$k2'] = $json;";
}

echo '}';
