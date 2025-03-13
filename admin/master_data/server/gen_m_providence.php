<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_PROV'] = [];
\$_M_PROV = &\$GLOBALS['_M_PROV'];
\$_M['PROV'] = &\$_M_PROV;


EOF;

$note = 'm_prov';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_providence
$PgGame->query('SELECT * FROM m_providence WHERE m_prov_pk = 1');
$PgGame->fetch();
$r = &$PgGame->row;

echo <<< EOF
\$_M_PROV = ['tax' => {$r['tax']} ,'tax_min' => {$r['tax_min']} ,'tax_max' => {$r['tax_max']} ,'loyalty' => {$r['loyalty']} ,'population_max' => {$r['population_max']} ,'population_curr' => {$r['population_curr']} ,'gold_curr' => {$r['gold_curr']} ,'gold_providence' => {$r['gold_providence']} ,'food_curr' => {$r['food_curr']} ,'food_providence' => {$r['food_providence']} ,'horse_curr' => {$r['horse_curr']} ,'horse_providence' => {$r['horse_providence']} ,'lumber_curr' => {$r['lumber_curr']} ,'lumber_providence' => {$r['lumber_providence']} ,'iron_curr' => {$r['iron_curr']} ,'iron_providence' => {$r['iron_providence']} ,'storage_max' => {$r['storage_max']} ,'storage_food_pct' => {$r['storage_food_pct']} ,'storage_horse_pct' => {$r['storage_horse_pct']} ,'storage_lumber_pct' => {$r['storage_lumber_pct']} ,'storage_iron_pct' => {$r['storage_iron_pct']}];

EOF;