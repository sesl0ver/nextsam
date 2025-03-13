$(() => {
	gm_log.init({
		table_title: ['로그일시', '웹ID', '군주', '좌표', '수행 건물', '건물 레벨', '수행 영웅', '건물 위치', '구분'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'upgrade': return '건설/업그레이드 시작';
				case 'complete': return '건설/업그레이드 완료';
				case 'instant': return '건설/업그레이드 즉시 완료';
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0':
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '4':
					return ns_i18n.t(`build_title_${_v}`);
				case '6':
					return (! _v) ? '-' : `Lv.${ns_cs.m.hero[_v].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[_v].m_hero_base_pk}`)}`;
				case '7':
					let o = _v.split(':');
					let type = o[0] === 'I' ? '내성' : '외성';
					return `${type} ${o[1]}`;
				case '8':
					return gm_log.logType(_v);
				default:
					return _v;
			}
		}
	});
});


