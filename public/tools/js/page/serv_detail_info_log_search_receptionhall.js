$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '좌표', '구분', '영웅이름', '금액', '고유번호(PK)'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'bidding': return '입찰';
				case 'bid_suc': return '낙찰 성공';
				case 'bid_fal': return '낙찰 실패';
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
				case '5': // 영웅이름
					console.log(_v);
					return (! _v) ? '-' : `Lv.${ns_cs.m.hero[_v].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[_v].m_hero_base_pk}`)}`;
				case '6': // 금액
					return ns_util.numberFormat(_v);
				default:
					return _v;
			}
		}
	});
});