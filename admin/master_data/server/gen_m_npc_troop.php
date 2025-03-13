<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_NPC_TROO'] = [];
\$_M_NPC_TROO = &\$GLOBALS['_M_NPC_TROO'];
\$_M['NPC_TROO'] = &\$_M_NPC_TROO;


EOF;

$note = 'm_npc_troo';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_npc_troo
$PgGame->query('SELECT * FROM m_npc_troop');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_NPC_TROO['{$r['type']}'][{$r['level']}] = ['worker' => {$r['worker']},'infantry' => {$r['infantry']},'pikeman' => {$r['pikeman']},'scout' => {$r['scout']},'spearman' => {$r['spearman']},'armed_infantry' => {$r['armed_infantry']},'archer' => {$r['archer']},'horseman' => {$r['horseman']},'armed_horseman' => {$r['armed_horseman']},'transporter' => {$r['transporter']},'bowman' => {$r['bowman']},'battering_ram' => {$r['battering_ram']},'catapult' => {$r['catapult']},'adv_catapult' =>  {$r['adv_catapult']},'move_time' => {$r['move_time']}];

EOF;
}
