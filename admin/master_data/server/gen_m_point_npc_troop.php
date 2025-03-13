<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_POIN_NPC_TROO'] = [];
\$_M_POIN_NPC_TROO = &\$GLOBALS['_M_POIN_NPC_TROO'];
\$_M['POIN_NPC_TROO'] = &\$_M_POIN_NPC_TROO;


EOF;

$note = 'm_poin_npc_troop';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_POIN_NPC_TROO
$PgGame->query('SELECT * FROM m_point_npc_troop');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_POIN_NPC_TROO['{$r['level']}'][{$r['type']}] = ['worker' => {$r['worker']},'infantry' => {$r['infantry']},'pikeman' => {$r['pikeman']},'scout' => {$r['scout']},'spearman' => {$r['spearman']},'armed_infantry' => {$r['armed_infantry']},'archer' => {$r['archer']},'horseman' => {$r['horseman']},'armed_horseman' => {$r['armed_horseman']},'transporter' => {$r['transporter']},'bowman' => {$r['bowman']},'battering_ram' => {$r['battering_ram']},'catapult' => {$r['catapult']},'adv_catapult' =>  {$r['adv_catapult']}];

EOF;
}