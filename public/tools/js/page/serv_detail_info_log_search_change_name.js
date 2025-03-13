$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '상세'],
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0':
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '3':
					_v = _v.replace(';', ' → ');
					return _v.replace(';', '');
				default:
					return _v;
			}
		}
	});
});