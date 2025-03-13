$(() => {
	gm_log.init({
		table_title: ['일시', '군주', '좌표', '구분', '고유키', '영웅', '사용', '소모 황금', '조합 고유 번호'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'common': return '일반 조합';
				case 'special': return '특수 조합';
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
					return (! _v) ? '-' : `Lv.${ns_cs.m.hero[_v].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[_v].m_hero_base_pk}`)}`;
				case '6':
					switch (_v) {
						case 'new_hero': return '조합 결과';
						case 'material': return '재료 영웅';
						case 'star_hero': return '메인 영웅';
					}
					return _v;
				default:
					return _v;
			}
		}
	});
});