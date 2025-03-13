$(() => {
	let content_json_info = new nsObject('#content_json_info');
	let scout_json_info = new nsObject('#scout_json_info');
	gm_log.init({
		table_title: ['일시', '군주', '좌표', '구분', '목적지 군주', '목적지 영지명', '목적지 좌표', '결과', '점령', '약탈', '상세'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'battle_attack': return '공격 전투';
				case 'battle_defence': return '방어 전투';
				case 'scout': return '정찰';
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0':
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '3':
					return gm_log.logType(_v);
				case '5':
					return gm_log.convertPositionName(_v);
				case '7':
					switch (_v) {
						case 'battle_attack_victory': return '공격 전투 승리';
						case 'battle_defence_victory': return '방어 전투 승리';
						case 'battle_attack_defeat': return '공격 전투 패배';
						case 'battle_defence_defeat': return '방어 전투 패배';
						case 'scout_success': return '정찰 성공';
						case 'scout_failure': return '정찰 실패';
						case 'scout_find': return '정찰 방어 성공';
						default: return _v;
					}
				case '8':
				case '9':
					return (_v === '1') ? '발생' : '-';
				case '10':
					_v = _v.split('||');
					if (_v[0] === 'scout_find') {
						return '-';
					}
					let button = new nsObject(document.createElement('button'));
					button.text('상세보기');
					button.setEvent('mouseup', (_e) => {
						_e.preventDefault();
						_e.stopPropagation();
						if (_v[0] === 'scout_success' || _v[0] === 'scout_failure') {
							view_scout_detail(_v[1]);
							scout_json_info.show();
						} else {
							view_battle_detail(_v[1]);
							content_json_info.show();
						}
					})
					return button;
				default:
					return _v;
			}
		}
	});
	content_json_info.hide();
	scout_json_info.hide();
	content_json_info.find('.background').setEvent('click', (_e) => {
		_e.preventDefault();
		_e.stopPropagation();
		content_json_info.hide();
	});
	scout_json_info.find('.background').setEvent('click', (_e) => {
		_e.preventDefault();
		_e.stopPropagation();
		scout_json_info.hide();
	});


	function view_battle_detail(_json){

		let content_json = $.parseJSON(_json);

		let army_arr = ['worker', 'infantry', 'pikeman', 'spearman', 'scout', 'archer', 'horseman', 'transporter', 'armed_infantry', 'armed_horseman', 'bowman', 'battering_ram', 'catapult', 'adv_catapult'];
		let fort_arr = ['trap', 'abatis', 'tower', 'wall'];
		let hero_arr = ['captain', 'director', 'staff'];
		let type_arr = ['att', 'def'];
		let reso_arr = ['gold', 'food', 'horse', 'lumber', 'iron'];

		if (typeof content_json.outcome_hero == 'object')
		{
			let hero_info = content_json.outcome_hero;
			$.each(hero_arr, function(k, v){
				$.each(type_arr, function(key, value){
					let t = '-';
					if (typeof hero_info[value] == 'object' && typeof hero_info[value][v + '_desc'] == 'string')
					{
						t = hero_info[value][v + '_desc'];
					}
					$('#' + value + '_' + v + '_desc').html(t);
				});
			});
		}

		if (typeof content_json.outcome_unit == 'object')
		{
			let unit_info = content_json.outcome_unit;
			$.each(army_arr, function(k, v){
				$.each(type_arr, function(key, value){
					let amount = '-';
					let die = '-';
					let remain = '-';
					let injury = '-';
					if (typeof unit_info[value] == 'object' && typeof unit_info[value][v] == 'object')
					{
						amount = unit_info[value][v]['amount'];
						die = unit_info[value][v]['amount'] - unit_info[value][v]['remain'];
						remain = unit_info[value][v]['remain'];
						if(unit_info[value][v]['injury'])
						{
							injury = unit_info[value][v]['injury'];
						}
					}
					$('#' + value + '_' + v + '_die').html(amount + ' 중 ' + die);
					$('#' + value + '_' + v + '_remain').html(remain + ' (' + injury + ')');
				});
			});
			$.each(fort_arr, function(k, v){
				$.each(type_arr, function(key, value){
					let die = '-';
					let remain = '-';
					if (typeof unit_info[value] == 'object' && typeof unit_info[value][v] == 'object')
					{
						die = unit_info[value][v]['amount'] - unit_info[value][v]['remain'];
						remain = unit_info[value][v]['remain'];
					}
					$('#' + value + '_' + v + '_die').html(die);
					$('#' + value + '_' + v + '_remain').html(remain);
				});
			});
		}

		if (typeof content_json.outcome == 'object')
		{
			let outcome = content_json.outcome;
			let type = ['get', 'own'];
			$.each(reso_arr, function(k, v){
				$.each(type, function(key, value){
					let t = '-';
					if (typeof outcome.plunder == 'boolean' && outcome.plunder == true)
					{
						let plunder = content_json.plunder;
						t = plunder[value][v];
					}
					$('#plunder_' + v + '_' + value).html(t);
				});
			});

			if (typeof outcome.reward == 'object')
			{
				$('#reward').html(outcome.reward.item_desc);
			} else {
				$('#reward').html('-');
			}

			let is_occupation = '-';
			if (typeof outcome.occupation == 'boolean' && outcome.occupation == true)
			{
				is_occupation = '영지 점령 성공';
			} else if (typeof outcome.acquiredpwnership == 'boolean') {
				if (!outcome.acquiredpwnership)
				{
					is_occupation = '자원지 주둔 실패 , 회군 시작';
				} else {
					is_occupation = '자원지 주둔 시작';
				}
			}
			$('#is_occupation').html(is_occupation);
		}

		if (typeof content_json.hero_battle == 'object')
		{
			let hero_battle = content_json.hero_battle;
			let hero_battle_battle_turn = '-';
			if (typeof hero_battle.battle_turn == 'number')
			{
				$('#hero_battle_battle_turn').html(hero_battle.battle_turn);
			}
			if (typeof hero_battle.win == 'string')
			{
				let win = '-';
				if (hero_battle.win == 'att')
				{
					win = '공격측 승리';
				} else if (hero_battle.win == 'def') {
					win = '수비측 승리';
				}
				$('#hero_battle_win').html(win);
			}
		}

		if (typeof content_json.battle_info == 'object')
		{
			let battle_info = content_json.battle_info;

			let def_type = '-';
			if (typeof battle_info.def_type == 'string')
			{
				if (battle_info.def_type == 'territory')
				{
					def_type = '영지 전투';
				}
				else if (battle_info.def_type == 'valley')
				{
					def_type = '부대 전투';
				}
			}
			$('#battle_def_type').html(def_type);

			let def_wall = '-';
			if (typeof battle_info.def_wall == 'string')
			{
				if (battle_info.def_wall == 'open')
				{
					def_wall = '개방';
				}
				else if (battle_info.def_wall == 'close')
				{
					def_wall = '폐쇄';
				}
			}
			$('#battle_def_wall').html(def_wall);

			let battle_winner = '-';
			if (typeof battle_info.unit_battle_winner == 'string')
			{
				if (battle_info.unit_battle_winner == 'def')
				{
					battle_winner = '수비측 승리';
				}
				else if (battle_info.unit_battle_winner == 'att')
				{
					battle_winner = '공격측 승리';
				}
			}
			$('#unit_battle_winner').html(battle_winner);

			let unit_battle_final_scene = '-';
			if (!isNaN(battle_info.unit_battle_final_scene))
			{
				unit_battle_final_scene = battle_info.unit_battle_final_scene;
			}
			$('#unit_battle_final_scene').html(unit_battle_final_scene)
		}

		if (typeof content_json.plunder == 'object')
		{
			let plunder = content_json.plunder;

			let loyalty_decrease = '-';
			if (typeof plunder.loyalty_decrease == 'number')
			{
				loyalty_decrease = plunder.loyalty_decrease;
			}
			$('#loyalty_desc').html(loyalty_decrease);

			let loyalty_final = '-';
			if (typeof plunder.loyalty_final == 'number')
			{
				loyalty_final = plunder.loyalty_final;
			}
			$('#loyalty_final').html(loyalty_final);
		}
	}

	const view_scout_detail = (_json) =>{
		let content_json = $.parseJSON(_json);

		scout_json_info.find('.cover').empty();

		for (let [k, v] of Object.entries(content_json)) {
			let title;
			switch (k) {
				case 'intelligence': title = '첩보력'; break;
				case 'scout_type': title = '정찰 구분'; break;
				case 'valley': title = '자원지 정찰'; break;
				case 'territory_lord': title = '타군주 정찰'; break;
				case 'territory_npc': title = 'NPC 정찰'; break;
				case 'scout_value': title = '첩보력 차'; break;
				case 'scout_level_table': title = '정찰 레벨 테이블'; break;
				case 'scout_level': title = '정찰 레벨'; break;
				case 'hero_skill': title = '영웅 스킬'; break;
				case 'fort': title = '방어시설'; break;
				case 'wall_level': title = '성벽 레벨'; break;
				case 'reso': title = '자원'; break;
				case 'army_scale': title = '총 병력 규모'; break;
				case 'army': title = '병력 정보'; break;
				case 'hero': title = '영웅 정보'; break;
				case 'scout_amount': title = '정찰병 수'; break;
				case 'scout_dead': title = '정찰병 사망 수'; break;
				case 'type': title = '정찰지 타입'; break;
				case 'yn_npc': title = 'NPC 여부'; break;
				case 'yn_valley': title = '자원지 여부'; break;
				default: title = k; break;
			}

			let value = '';
			if (ns_util.isArray(v)) {
				for (let [k2, v2] of Object.entries(v)) {
					if (['worker', 'infantry', 'pikeman', 'scout', 'spearman', 'armed_infantry', 'archer', 'horseman', 'armed_horseman', 'transporter', 'bowman', 'battering_ram', 'catapult', 'adv_catapult'].includes(k2)) {
						if (ns_util.math(v2).gt(0)) {
							value += '<br />' + ns_i18n.t(`army_title_${ns_cs.m.army[k2].m_army_pk}`) + `: ${v2}`;
						}
					} else if (['trap', 'abatis', 'tower'].includes(k2)) {
						if (ns_util.math(v2).gt(0)) {
							value += '<br />' + ns_i18n.t(`fort_title_${ns_cs.m.fort[k2].m_fort_pk}`) + `: ${v2}`;
						}
					} else if (['gold', 'food', 'horse', 'lumber', 'iron'].includes(k2)) {
						if (ns_util.math(v2).gt(0)) {
							value += '<br />' + ns_i18n.t(`resource_${k2}`) + `: ${v2}`
						}
					} else if (ns_util.isArray(v2)) {
						for (let [k3, v3] of Object.entries(v2)) {
							if(k3 === 'm_pk') {
								value = `<br />Lv.${ns_cs.m.hero[v3].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[v3].m_hero_base_pk}`)}`;
							}
						}
					}
				}
			} else {
				value = v;
			}
			value += '<br /><br />';
			let scout_log = `${title}: ${value}`;

			scout_json_info.find('.cover').html(scout_log, true);
		}
	}
});