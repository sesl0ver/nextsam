$(() => {
	gm_log.init({
		table_title: ['일시', '군주', '좌표', '버프 번호', '버프 종류', '버프 시간', '구분'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'P': return '사용';
				case 'C': return '취소';
				case 'I': return '추가';
				case 'F': return '종료';
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0':
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '4':
					return (_v === '0') ? '-' : ns_i18n.t(`item_title_${_v}`) + ` [${_v}]`
				case '5':
					return (_v === '0') ? '-' : ns_util.getCostsTime(_v);
				case '6':
					return gm_log.logType(_v);
				default:
					return _v;
			}
		}
	});
});