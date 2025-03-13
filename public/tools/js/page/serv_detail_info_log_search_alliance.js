$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '좌표', '구분', '상세'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'alliance_make': return '동맹 창설';
				case 'add_member': return '동맹원 추가';
				case 'invite_member': return '동맹원 초대';
				case 'join_accept': return '가입 요청 수락';
				case 'join_refuse': return '가입 요청 거절';
				case 'change_level': return '직책 변경';
				case 'transfer_master': return '동맹 양도';
				case 'member_expulsion': return '동맹원 제명';
				case 'member_resignation': return '직책 사직';
				case 'member_dropout': return '동맹원 탈퇴';
				case 'alliance_close': return '동맹 폐쇄';
				case 'join_request': return '동맹 가입 요청';
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
					_v = _v.replaceAll('lord_pk', '군주PK');
					_v = _v.replaceAll('lord_name', '군주명');
					_v = _v.replaceAll('admin_pk', '실행군주PK');
					_v = _v.replaceAll('admin_name', '실행군주');
					_v = _v.replaceAll('alli_pk', '동맹PK');
					_v = _v.replaceAll(';', '<br />');
					return _v;
				default:
					return _v;
			}
		}
	});
});