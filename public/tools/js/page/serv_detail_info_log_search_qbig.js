$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '등급', '구분', '수량', '이전', '이후', '충전액', '상세'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'incr_cash': return '<span class="text_green">큐빅 증가</span>';
				case 'desc_cash': return '<span class="text_red">큐빅 감소</span>';
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
				case '6':
				case '7':
					return ns_util.numberFormat(_v);
				case '9':
					switch (_v)
					{
						case 'speedup now': return '즉시 가속';
						case 'build now': return '즉시 건설';
						case 'technique now': return '즉시 연구';
						case 'army now': return '즉시 훈련';
						case 'secret_package_pack': return '시크릿 패키지 구매';
						case 'item buy': return '아이템 구매';
						case 'speedup_item': return '독려 아이템 구매 사용';
						case 'item_buy_use': return '아이템 구매 사용';
						case 'market': return '시장 구매 사용';
						case 'magiccube': return '매직큐브';
						case 'gm_give_cash': return 'GM 큐빅 지급';
						case 'gm_withdraw_cash': return 'GM 큐빅 회수';
						case 'charge': return '큐빅 충전';
						case 'refund': return '환불';
						case 'refund_cancel': return '환불 취소';
					}
					if (/pickup/g.test(_v)) {
						return '영웅 영입';
					} else if (/package_buy\[([\d]*)\]/g.test(_v)) {
						let __v = /package_buy\[([\d]*)\]/g.exec(_v);
						return ns_cs.m.package[__v[1]].title + ' 구매';
					}
					return _v;
				default:
					return _v;
			}
		}
	});
});