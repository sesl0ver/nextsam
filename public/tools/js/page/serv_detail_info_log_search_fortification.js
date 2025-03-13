$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '좌표', '설치 시설', '상세', '구분'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'upgrade': return '설치 시작';
				case 'complete': return '설치 완료';
				case 'decr_fort_battle': return '전투 소비';
				case 'desc_fort_disperse': return '시설 해체';
				/*case 'incr_fort_cheat': return '방어시설 치트';*/
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0': // 로그일시
					return moment(_v * 1000).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '4': // 수행건물
					return ns_i18n.t(`fort_title_${_v}`);
				case '6':
					return gm_log.logType(_v);
				default:
					return _v;
			}
		}
	});
});