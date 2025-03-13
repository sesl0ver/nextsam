$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '좌표', '구분', '탐색영웅', '탐색유형', '사용아이템', '탐색결과', '초빙횟수', '초빙여부'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'start': return '탐색 시작';
				case 'cancel': return '탐색 취소';
				case 'complete': return '탐색 완료';
				case 'get': return '결과 수령';
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0': // 로그일시
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '4':
					return gm_log.logType(_v);
				case '5':
					return (! _v) ? '-' : `Lv.${ns_cs.m.hero[_v].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[_v].m_hero_base_pk}`)}`;
				case '6':
					switch (_v) {
						case 'distance': return '거리탐문';
						case 'in_castle': return '내성탐색';
						case 'territory': return '영지탐색';
						case 'world': return '대륙탐색';
						case 'walkabout': return '민정시찰';
						case 'around_world': return '주유천하';
						default: return _v;
					}
				case '7':
					return (_v === '0') ? '-' : ns_i18n.t(`item_title_${_v}`);
				case '8':
					let __v = _v.split(':');
					switch (__v[0]) {
						case 'gold':
						case 'food':
						case 'horse':
						case 'lumber':
						case 'iron':
							return ns_i18n.t(`resource_${__v[0]}`) + ' ' + ns_util.numberFormat(__v[1]);
						case 'item':
							return ns_i18n.t(`item_title_${__v[1]}`);
						case 'hero':
							return `영웅 (${__v[1]})`;
						case '':
							return '-';
						case 'none':
							return '없음';
						default:
							return _v;
					}
				/*case '4': // 수행건물
					return ns_i18n.t(`build_title_${_v}`);
				case '6': // 수행영웅
					return (! _v) ? '-' : `Lv.${ns_cs.m.hero[_v].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[_v].m_hero_base_pk}`)}`;
				case '7': // 건물위치
					let o = _v.split(':');
					let type = o[0] === 'I' ? '내성' : '외성';
					return `${type} ${o[1]}`;
				case '8':
					return gm_log.logType(_v);*/
				default:
					return _v;
			}
		}
	});
});