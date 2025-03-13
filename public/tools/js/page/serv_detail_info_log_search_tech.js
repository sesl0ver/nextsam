$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '좌표', '수행 영웅', '기술 구분', '기술 레벨', '구분'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'upgrade': return '연구 시작';
				case 'complete': return '연구 완료';
				case 'instant': return '연구 즉시 완료';
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0': // 로그일시
					return moment(_v * 1000).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '4': // 수행 영웅
					return (! _v) ? '-' : `Lv.${ns_cs.m.hero[_v].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[_v].m_hero_base_pk}`)}`;
				case '5': // 기술 구분
					return ns_i18n.t(`tech_title_${_v}`);
				case '7':
					return gm_log.logType(_v);
				default:
					return _v;
			}
		}
	});
});