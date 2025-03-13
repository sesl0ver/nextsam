$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '좌표', '구분', '상세'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'tax_rate': return '세율 변경';
				case 'comforting': return '복지';
				case 'requisition': return '징발';
				case 'change_terr_name': return '영지명 변경';
				case 'gm_change_terr_name': return '영지명 변경 (GM툴)';
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
					_v = _v.replaceAll('requisition', '징발');
					_v = _v.replaceAll('redress', '구휼');
					_v = _v.replaceAll('ritual', '천제');
					_v = _v.replaceAll('prevention_disasters', '재해예방');
					_v = _v.replaceAll('food', '식량');
					_v = _v.replaceAll('lumber', '목재');
					_v = _v.replaceAll('horse', '우마');
					_v = _v.replaceAll('gold', '황금');
					_v = _v.replaceAll('iron', '철강');
					return _v;
				default:
					return _v;
			}
		}
	});
});