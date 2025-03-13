$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '좌표', '구분', '상세'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'acquired_valley': return '자원지 점령';
				case 'loss_valley': return '자원지 포기';
				case 'roamer_lord': return '방랑 군주';
				case 'roamer_lord_create_territory': return '방랑 군주 영지 건설';
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
				case '5':
					_v = _v.replaceAll('increase_power', '영향력 증가');
					_v = _v.replaceAll('decrease_power', '영향력 감소');
					_v = _v.replaceAll('valley_posi_pk', '점령 영지 좌표');
					_v = _v.replaceAll('cancel_info', '포기정보');
					_v = _v.replaceAll('time_info', '시간정보');
					_v = _v.replaceAll('start_dt', '시작');
					_v = _v.replaceAll('end_dt', '끝');
					_v = _v.replaceAll('def_info', '대상');
					_v = _v.replaceAll('posi_pk', '영지');
					_v = _v.replaceAll('src_lord_pk', '공격군주PK');
					_v = _v.replaceAll('dst_lord_pk', '방어군주PK');
					return _v;
				default:
					return _v;
			}
		}
	});
});