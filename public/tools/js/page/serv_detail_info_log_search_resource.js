$(() => {
	gm_log.init({
		table_title: ['일시', '군주', '좌표', '구분', '증감량'],
		logType: function (_log_type)
		{
			if (/incr_gold/g.test(_log_type)) {
				_log_type = _log_type.replace('incr_gold', '황금 증가');
				_log_type = _log_type.replace('army_cancel', '(병력 생산 취소)');
				_log_type = _log_type.replace('cancel_bid', '(무역장 구매 취소)');
				_log_type = _log_type.replace('bid_fail', '(영빈관 입찰 실패)');
				_log_type = _log_type.replace('bid', '(무역장 구매)');
				_log_type = _log_type.replace('cancel_fort', '(방어시설 생산 취소)');
				_log_type = _log_type.replace('cancel_tech', '(기술 개발 취소)');
				_log_type = _log_type.replace('encounter', '(탐색)');
				_log_type = _log_type.replace('invitation_cancel', '(초빙 취소)');
				_log_type = _log_type.replace('market_buy', '(시장 상품 구매)');
				_log_type = _log_type.replace('offer', '(무역장 판매)');
				_log_type = _log_type.replace('quest_reward', '(퀘스트 보상)');
				_log_type = _log_type.replace('requisition', '(징발)');
				_log_type = _log_type.replace('terr_founding', '(신규 영지)');
				_log_type = _log_type.replace('trade_remind_price', '(무역장 단가 차액)');
				_log_type = _log_type.replace('troop_Recall', '(회군)');
				_log_type = _log_type.replace('troop_R', '(지원)');
				_log_type = _log_type.replace('troop_T', '(수송)');
				_log_type = _log_type.replace('cheat', '(치트툴)');
				_log_type = _log_type.replace('hero_trad_gold_get', '(황금 창고 인출)');
				_log_type = _log_type.replace('fort_disperse', '(방어 시설 해체)');
				_log_type = _log_type.replace('army_disperse', '(병력 해산)');
				_log_type = _log_type.replace('make_ally_fail', '(동매 창설 실패)');
				if (/_item_/g.test(_log_type)) {
					_log_type = _log_type.replace('item', '(아이템 사용:');
					let pk = _log_type.split('_').pop();
					_log_type = _log_type.replace(pk, ns_i18n.t(`item_title_${pk}`) + `[${pk}])`);
				}
			} else if (/decr_gold/g.test(_log_type)) {
				_log_type = _log_type.replace('decr_gold', '황금 감소');
				_log_type = _log_type.replace('army_pre', '(병력 생산)');
				_log_type = _log_type.replace('army_treatment', '(병력 치료)');
				_log_type = _log_type.replace('bid', '(무역장 구매)');
				_log_type = _log_type.replace('bidding', '(영빈관 영웅 입찰)');
				_log_type = _log_type.replace('comforting', '(복지-천제)');
				_log_type = _log_type.replace('fort_pre', '(방어시설 생산)');
				_log_type = _log_type.replace('hero_combination', '(영웅 조합)');
				_log_type = _log_type.replace('hero_enchant', '(영웅 강화)');
				_log_type = _log_type.replace('hero_invitation', '(영웅 초빙)');
				_log_type = _log_type.replace('hero_prize', '(영웅 포상)');
				_log_type = _log_type.replace('hero_salary', '(영웅 급여 지급)');
				_log_type = _log_type.replace('make_ally', '(동맹 창설)');
				_log_type = _log_type.replace('market_buy', '(시장 상품 구매)');
				_log_type = _log_type.replace('offer_commission', '(무역장 판매 수수료)');
				_log_type = _log_type.replace('quest_making', '(제작 퀘스트)');
				_log_type = _log_type.replace('tech_pre', '(기술 개발)');
				_log_type = _log_type.replace('terr_founding', '(영지 건설)');
				_log_type = _log_type.replace('troop_order', '(출병)');
				_log_type = _log_type.replace('troop_plunder', '(약탈)');
				_log_type = _log_type.replace('trade_bid', '(영웅 거래 - 입찰)');
				_log_type = _log_type.replace('hero_combination_common', '(일반 조합)');
				_log_type = _log_type.replace('hero_combination_special', '(특수 조합)');
				_log_type = _log_type.replace('cheat', '(치트툴)');
			} else if (/incr_reso/g.test(_log_type)) {
				_log_type = _log_type.replace('incr_reso', '자원 증가');
				_log_type = _log_type.replace('army_cancel', '(병력 생산 취소)');
				_log_type = _log_type.replace('build_cancel', '(건설 취소)');
				_log_type = _log_type.replace('build_demolish', '(건물 다운그레이드)');
				_log_type = _log_type.replace('cancel_fort', '(방어시설 생산 취소)');
				_log_type = _log_type.replace('cancel_offer', '(판매 취소)');
				_log_type = _log_type.replace('cancel_tech', '(기술 개발 취소)');
				_log_type = _log_type.replace('encounter', '(탐색)');
				_log_type = _log_type.replace('market_buy', '(시장 상품 구매)');
				_log_type = _log_type.replace('quest_reward', '(퀘스트 보상)');
				_log_type = _log_type.replace('requisition', '(징발');
				_log_type = _log_type.replace('terr_founding', '(신규영지)');
				_log_type = _log_type.replace('terr_occupation', '(점령)');
				_log_type = _log_type.replace('trade_delivery', '(무역장 배송)');
				_log_type = _log_type.replace('troop_Recall', '(부대 회군)');
				_log_type = _log_type.replace('troop_R', '(지원)');
				_log_type = _log_type.replace('troop_T', '(수송)');
				_log_type = _log_type.replace('cheat', '(치트툴)');
				_log_type = _log_type.replace('fort_disperse', '(방어 시설 해체)');
				_log_type = _log_type.replace('army_disperse', '(병력 해산)');
				if (/_item_/g.test(_log_type)) {
					_log_type = _log_type.replace('item', '(아이템 사용:');
					let pk = _log_type.split('_').pop();
					_log_type = _log_type.replace(pk, ns_i18n.t(`item_title_${pk}`) + `[${pk}])`);
				}
			} else if (/decr_reso/g.test(_log_type)) {
				_log_type = _log_type.replace('decr_reso', '자원 감소');
				_log_type = _log_type.replace('army_pre', '(병력 생산)');
				_log_type = _log_type.replace('build_pre', '(건설)');
				_log_type = _log_type.replace('comforting', '(복지)');
				_log_type = _log_type.replace('fort_pre', '(방어시설 생산)');
				_log_type = _log_type.replace('market_buy', '(시장 상품 구매)');
				_log_type = _log_type.replace('offer', '(무역장 판매)');
				_log_type = _log_type.replace('quest_making', '(제작 퀘스트)');
				_log_type = _log_type.replace('tech_pre', '(기술 개발)');
				_log_type = _log_type.replace('terr_founding', '(영지 건설)');
				_log_type = _log_type.replace('troop_order', '(부대 출정)');
				_log_type = _log_type.replace('troop_plunder', '(약탈)');
				_log_type = _log_type.replace('cheat', '(치트툴)');
			}
			return _log_type.replaceAll('_', ' ');
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0': // 로그일시
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '3': // 구분
					return gm_log.logType(_v);
				case '4': // 증감량
					_v = _v.replaceAll(';', ', ');
					_v = _v.replaceAll('before', '이전<br />');
					_v = _v.replaceAll('update', '<br />증감<br />');
					_v = _v.replaceAll('after', '<br />이후<br />');
					_v = _v.replaceAll('food', '식량');
					_v = _v.replaceAll('horse', '우마');
					_v = _v.replaceAll('lumber', '목재');
					_v = _v.replaceAll('iron', '철강');
					return _v;
				default:
					return _v;
			}
		}
	});
});

/*
    function getLogResourceDesc($str, $type)
    {
        $patterns = array();
        $replacements = array();
        $patterns[] = '/curr/';
        $replacements[] = '현재';
        $patterns[] = '/incr/';
        $replacements[] = '증가';
        $patterns[] = '/desc/';
        $replacements[] = '감소';
        $patterns[] = '/decr/';
        $replacements[] = '감소';
        $patterns[] = '/update/';
        $replacements[] = '<br />변경 ';
        $patterns[] = '/after/';
        $replacements[] = '<br />이후 ';
        $patterns[] = '/storage/';
        $replacements[] = '<br />창고잔액 ';
        $patterns[] = '/before/';
        $replacements[] = '이전 ';
        $patterns[] = '/food/';
        $replacements[] = '식량';
        $patterns[] = '/horse/';
        $replacements[] = '우마';
        $patterns[] = '/lumber/';
        $replacements[] = '목재';
        $patterns[] = '/iron/';
        $replacements[] = '철강';

        return preg_replace($patterns, $replacements, $str);;
    }
 */