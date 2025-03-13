$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '좌표', '구분', '배속 영웅', '건물', '건물위치'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'Assign': return '건물 배속';
				case 'Unassign': return '배속 해제';
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
				case '5': // 수행영웅
					return (! _v) ? '-' : `Lv.${ns_cs.m.hero[_v].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[_v].m_hero_base_pk}`)}`;
				case '6': // 건물
					return (! _v) ? '-' : ns_i18n.t(`build_title_${_v}`);
				default:
					return _v;
			}
		}
	});
});