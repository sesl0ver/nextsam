$(() => {
	gm_log.init({
		table_title: ['일시', '군주', '좌표', '구분', '고유키', '영웅', '이전 경험치', '증가 경험치', '이후 경험치'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'PrizeMedal': return '공적패 포상';
				case 'ConstructionComplete': return '건물 건설';
				case 'TechniqueComplete': return '기술 개발';
				case 'EncounterComplete': return '탐색';
				case 'HeroBattle': return '일기토';
				case 'Battle': return '전투';
				case 'Cheat': return '치트';
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0': // 로그일시
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '3':
					return gm_log.logType(_v);
				case '5':
					return (! _v) ? '-' : `Lv.${ns_cs.m.hero[_v].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[_v].m_hero_base_pk}`)}`;
				case '6':
				case '7':
				case '8':
					return ns_util.numberFormat(_v);
				default:
					return _v;
			}
		}
	});
});