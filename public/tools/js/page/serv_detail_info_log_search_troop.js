$(() => {
	gm_log.init({
		table_title: ['일시', '군주', '좌표', '구분', '목적지', '고유키', '영웅 정보', '병력 정보', '자원 정보'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'M_A': return '공격 출정';
				case 'M_P': return '보급 출정';
				case 'M_R': return '지원 출정';
				case 'M_S': return '정찰 출정';
				case 'M_T': return '수송 출정';
				case 'R_A': return '공격 회군';
				case 'R_P': return '보급 회군';
				case 'R_R': return '지원 회군';
				case 'R_S': return '정찰 회군';
				case 'R_T': return '수송 회군';
				case 'W_A': return '공격 출정 취소';
				case 'W_P': return '보급 출정 취소';
				case 'W_R': return '지원 출정 취소';
				case 'W_S': return '정찰 출정 취소';
				case 'W_T': return '수송 출정 취소';
				case 'StatusRecall': return '회군 명령';
				case 'StatusWithdrawal': return '철수';
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
				case '4':
					let [_posi_pk, _name, _level] = _v.split(':');
					if (ns_i18n.t(_name).substring(0, 2) !== '__') {
						_name = ns_i18n.t(_name);
					}
					return `Lv.${_level} ${_name} (${_posi_pk})`;
				case '6':
					let _str = '';
					let data = JSON.parse(_v);
					for (let _type of ['captain', 'director', 'staff']) {
						if (data[`${_type}_desc`]) {
							let o = data[`${_type}_desc`];
							_str+=`${ns_i18n.t(`troop_${_type}`)} Lv.${ns_cs.m.hero[o].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[o].m_hero_base_pk}`)}<br />`;
						}
					}
					return _str;
				case '7':
					let _str_army = '';
					for (let [_type, _value] of Object.entries(JSON.parse(_v))) {
						if (_type.substring(0, 5) === 'army_') {
							_type = _type.substring(5);
						}
						if (ns_cs.m.army[_type] && ns_util.math(_value).gt(0)) {
							_str_army += ns_i18n.t(`army_title_${ns_cs.m.army[_type].m_army_pk}`) + `: ${ns_util.numberFormat(_value)}<br />`;
						}
					}
					return _str_army;

				case '8':
					let _str_reso = '';
					for (let [_type, _value] of Object.entries(JSON.parse(_v))) {
						switch (_type) {
							case 'food': return `식량: ${_value}`;
							case 'horse': return `우마: ${_value}`;
							case 'lumber': return `목재: ${_value}`;
							case 'iron': return `철강: ${_value}`;
							case 'round_food': return `왕복 소요 식량: ${_value}`;
							case 'round_gold': return `왕복 소요 황금: ${_value}`;
							case 'presence_food': return `주둔 소요 식량: ${_value}`;
							case 'hour_food': return `시간당 식량 소모량: ${_value}`;
						}
					}
					return ns_i18n.t(`build_title_${_v}`);


				/*case '4':
					return ns_i18n.t(`build_title_${_v}`);
				case '6':
					return (! _v) ? '-' : `Lv.${ns_cs.m.hero[_v].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[_v].m_hero_base_pk}`)}`;
				case '7':
					let o = _v.split(':');
					let type = o[0] === 'I' ? '내성' ': return '외성';
					return `${type} ${o[1]}`;
				case '8':
					return gm_log.logType(_v);*/
				default:
					return _v;
			}
		}
	});
});