$(() => {
	gm_log.init({
		table_title: ['일시', '군주', '좌표', '구분', '획득 분류', '아이템명', '수량', '상세'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'buy': return '획득';
				case 'use': return '사용';
				case 'speedup_time': return '시간 가속';

				/*
                    use : '사용',
                    event : '이벤트',
                    use_magiccube : '사용 - 행운의 주화',
                    buy_magiccube : '획득 - 매직큐브',
                    buy_buy_item : '획득 - 구매',
                    buy_now_use : '획득 - 구매 후 즉시 사용',
                    buy_quest_reward : '획득 - 퀘스트 보상',
                    buy_hero_get : '획득 - 탐색',
                    buy_market : '획득 - 시장',
                    buy_troop : '획득 - 전리품',
                    invite_reward : '친구 선물',
                    article_post : '사진첩 보상 아이템',
                    package_item : '획득 - 패키지 아이템',
                    roamer_lord_support : '획득 - 방랑 군주 지원',
                    gm_give : 'GM 지급',
                    gm_del : 'GM 회수',
                    attendance_event : '출석패키지'
				*/
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0':
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '3':
					return gm_log.logType(_v);
				case '4':
					switch (_v) {
						case 'magiccube': return '매직큐브';
						case 'now_use': return '구매 후 즉시사용';
						case 'troop': return '전리품';
						case 'buy_item': return '아이템 구매';
						case 'attendance_event': return '출석 이벤트';
						case 'gm_give': return 'GM툴 지급';
					}
					if (/usePackageItem\[([\d]*)\]/g.test(_v)) {
						let __v = /usePackageItem\[([\d]*)\]/g.exec(_v);
						return `${ns_i18n.t(`item_title_${__v[1]}`)} (패키지)`
					}
					return _v;
				case '5':
					return (! _v || _v === '0') ? '' : ns_i18n.t(`item_title_${_v}`);
				case '7':
					_v = _v.replaceAll(';', '<br />');
					_v = _v.replaceAll('before_count', '이전수량');
					_v = _v.replaceAll('after_count', '이후수량');
					_v = _v.replaceAll('before_lord_name', '이전군주명');
					_v = _v.replaceAll('lord_name', '현재군주명');
					_v = _v.replaceAll('use_type', '사용구분');
					_v = _v.replaceAll('speedup_time', '가속 시간');
					return _v;
				default:
					return _v;
			}
		}
	});
});