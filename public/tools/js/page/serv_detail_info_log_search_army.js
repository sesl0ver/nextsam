$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '좌표', '구분', '병과', '수량', '상세'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'training': return '훈련 시작';
				case 'training_complete': return '훈련 완료';
				case 'army_disperse': return '병력 해산';
				case 'medical': return '부상병 치료';
				case 'medical_complete': return '치료 완료';
				case 'injury_army': return '부상병 발생';
				case 'injury_disperse': return '부상병 해산';
				case 'quest_army_reward': return '퀘스트 병력 보상';
				case 'quest_fort_reward': return '퀘스트 방어시설 보상';
				case 'decrease_troop': return '병력 감소 (출병)';
				case 'increase_troop': return '병력 증가 (복귀)';
				case 'decrease_battle': return '병력 감소 (전투)';
				case 'decrease_battle_ally': return '지원 병력 감소 (전투)';
				case 'increase_army_item': return '병력 증가 (아이템)';
				case 'decrease_army_over': return '병력 감소 (반란)';
				case 'decrease_army_all': return '병력 감소 (전체)';
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			const convertSub = function (_x) {
				let _str;
				let current = /curr\[([\d\[\];]*)\]/g.exec(_x);
				let update = /update\[([\d\[\];]*)\]/g.exec(_x);
				_str = '이전<br />';
				for (let _curr of current[1].split(';')) {
					if (_curr === '') {
						continue;
					}
					let _c = /(\d*)\[(\d*)\]/g.exec(_curr);
					if (_c) {
						_str += ns_i18n.t(`army_title_${_c[1]}`) + `: ${ns_util.numberFormat(Number(_c[2]))}<br />`;
					}
				}
				_str += '변동<br />';
				for (let _update of update[1].split(';')) {
					if (_update === '') {
						continue;
					}
					let _u = /(\d*)\[(\d*)\]/g.exec(_update);
					if (_u) {
						_str += ns_i18n.t(`army_title_${_u[1]}`) + `: ${ns_util.numberFormat(Number(_u[2]))}<br />`;
					}
				}
				return _str;
			}


			switch (_k) {
				case '0':
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '4':
					return gm_log.logType(_v);
				case '5':
					return (! _v) ? '-' : ns_i18n.t(`army_title_${_v}`);
				case '6':
					return (_v < 1) ? '-' : ns_util.numberFormat(_v);
				case '7':
					let _str, __v = _v.split('|');
					if (__v[0] === 'decrease_battle' || __v[0] === 'decrease_battle_ally') {
						_str = convertSub(__v[1]);
					} else if (__v[0] === 'training' || __v[0] === 'medical' || __v[0] === 'injury_army') {
						let ___v = /(\d*)\[(\d*)\]/g.exec(__v[1]);
						_str = ns_i18n.t(`army_title_${___v[1]}`) + `: ${ns_util.numberFormat(Number(___v[2]))}<br />`;
					} else {
						_str = '';
						for (let _x of __v[1].replaceAll('];];', '];];|').split('|')) {
							if (_x === '') {
								continue;
							}
							let __x = /(\d*)\[([\d\w\W]*\]);\];/g.exec(_x);
							if (! __x) {
								continue;
							}
							let current = /curr\[([\d]*)\]/g.exec(__x[2]);
							let update = /update\[([\d]*)\]/g.exec(__x[2]);
							_str+= ns_i18n.t(`army_title_${__x[1]}`) + ` - 이전: ${ns_util.numberFormat(Number(current[1]))} 변동: ${ns_util.numberFormat(Number(update[1]))}<br />`;
						}
					}
					return _str;
				default:
					return _v;
			}
		}
	});
});