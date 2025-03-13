
$(() => {
	gm_log.init({
		table_title: ['로그일시', '웹ID', '군주', '좌표', '구분', '상세'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'storage_rate': return '할당 비율 변경';
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
					let __v = _v.split(';');
					return `식량:${__v[0]}, 우마:${__v[1]}, 목재:${__v[2]}, 철강:${__v[3]}`;
				default:
					return _v;
			}
		}
	});
});