$(() => {
	gm_log.init({
		table_title: ['고유키', 'uuid', '군주명', '로그인 시간', '로그아웃 시간', '플랫폼', 'IP', 'Agent정보'],
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '2':
				case '4':
					if (! _v) {
						return _v;
					}
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				default:
					return _v;
			}
		}
	});
});