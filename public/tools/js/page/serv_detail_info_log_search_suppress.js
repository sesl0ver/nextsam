$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '좌표', '구분', '타겟 레벨', '타겟 좌표', '타겟수', '토벌수'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'setNpcSuppress': return '황제 토벌령';
				case 'success': return '토벌 성공';
				case 'complete': return '전체 토벌 성공';
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0':
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '4':
					return gm_log.logType(_v);
				case '6':
					let str = '';
					for (let p of _v.split('|')) {
						if (p) {
							if (str !== '') {
								str += ', ';
							}
							str += p;
						}
					}
					return str;
				default:
					return _v;
			}
		}
	});
});