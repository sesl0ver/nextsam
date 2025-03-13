$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '좌표', '고유키', '영웅', '현재 상태', '명령 구분', '이전 상태', '결과'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'I': return '대기';
				case 'A': return '배속';
				case 'C': return '명령';
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0': // 로그일시
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '5': // 수행영웅
					return (! _v) ? '-' : `Lv.${ns_cs.m.hero[_v].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[_v].m_hero_base_pk}`)}`;
				case '6':
					return gm_log.logType(_v);
				case '7':
					switch (_v) {
						case 'None': return '-';
						case 'Const': return '건설';
						case 'Techn': return '개발';
						case 'Encou': return '탐색';
						case 'Attac': return '공격';
						case 'Invit': return '초빙';
						case 'Preva': return '보급';
						case 'Trans': return '수송';
						case 'Reinf': return '지원';
						case 'Treat': return '치료';
						case 'Scout': return '정찰';
					}
					return _v;
				case '8':
					switch (_v) {
						case 'C_Const': return '건설 명령';
						case 'C_Techn': return '기술 개발 명령';
						case 'C_Attac': return '전투 출병 명령';
						case 'C_Trans': return '수송 명령';
						case 'C_Reinf': return '지원 명령';
						case 'C_Preva': return '보급 명령';
						case 'C_Invit': return '초빙 명령';
						case 'C_Scout': return '정찰 명령';
						case 'C_Encou': return '탐색 명령';
						case 'C_Recal': return '복귀 명령';
						case 'I_None': return '영웅 대기 상태';
						case 'A_None': return '건물 배속 명령';
						case 'T_Treat': return '영웅 치료 상태';
						case 'I_N': return '영웅 대기 상태';
					}
					return _v;
				case '9':
					switch (_v) {
						case 'Command': return '수행/배속';
						case 'UnCommand': return '완료/해제';
					}
					return _v;
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