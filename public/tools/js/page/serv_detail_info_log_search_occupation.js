$(() => {
	gm_log.init({
		table_title: ['일시', '군주', '구분', '점령지', '점수'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'update_point': return '최종 점수';
				case 'self_earn': return '일반 포인트 획득';
				case 'regular_earn': return '정기 포인트 획득';
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0':
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '2':
					return gm_log.logType(_v);
				default:
					return _v;
			}
		}
	});
});