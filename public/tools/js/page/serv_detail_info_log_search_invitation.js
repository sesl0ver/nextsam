$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '좌표', '구분', '수행영웅', '초빙비용', '초빙결과', '우호점수', '초빙횟수', '대상고유키'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'success': return '초빙성공';
				case 'failure': return '초빙실패';
				case 'cancel': return '초빙취소';
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
					return ns_util.numberFormat(_v);
				case '7':
					return (_v === 'Y') ? '성공' : '실패';
				default:
					return _v;
			}
		}
	});
});