$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '좌표', '고유키', '영웅', '아이템', '강화 횟수', '강화 결과', '상세'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'enchant_init': return '강화 초기화';
				case 'Success': return '강화 성공';
				case 'Failure': return '강화 실패';
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0': // 로그일시
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '5':
					return (! _v) ? '-' : `Lv.${ns_cs.m.hero[_v].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[_v].m_hero_base_pk}`)}`;
				case '6':
					return (_v === '0') ? '-' : ns_i18n.t(`item_title_${_v}`);
				case '8':
					return gm_log.logType(_v);
				case '9':
					if (! _v) {
						return;
					}
					for (let o of _v.split(',')) {
						let _o = o.split(':');
						if (ns_util.math(_o[1]).gt(0)) {
							return ns_i18n.t(`stats_${_o[0]}`) + `: +${_o[1]}`;
						}
					}
					break;
				default:
					return _v;
			}
		}
	});
});