$(() => {
	gm_log.init({
		table_title: ['일시', '군주', '좌표', '상세'],
		logType: function (_log_type)
		{
			switch (_log_type) {
				case 'food_pct_plus_tech': return '태학 식량 증산 효과';
				case 'food_pct_plus_hero_assign': return '영웅 배속 식량 증산 효과';
				case 'foods_pct_plus_hero_skill': return '영웅 스킬 식량 증산 효과';
				case 'food_pct_plus_item': return '아이템 식량 증산 효과';
				case 'food_productivity': return '총 식량 생산량';
				case 'food_labor_force_max': return '최대 식량 생산 노동 인구';
				case 'food_labor_force_curr': return '현재 식량 생산 노동 인구';
				case 'food_production_territory': return '영지 식량 생산량';
				case 'food_production_valley': return '식량 외부자원지 유입량';
				case 'horse_pct_plus_tech': return '태학 우마 증산 효과';
				case 'horse_pct_plus_hero_assign': return '영웅 배속 우마 증산 효과';
				case 'horses_pct_plus_hero_skill': return '영웅 스킬 우마 증산 효과';
				case 'horse_pct_plus_item': return '아이템 우마 증산 효과';
				case 'horse_productivity': return '총 우마 생산량';
				case 'horse_labor_force_max': return '최대 우마 생산 노동 인구';
				case 'horse_labor_force_curr': return '현재 우마 생산 노동 인구';
				case 'horse_production_territory': return '영지 우마 생산량';
				case 'horse_production_valley': return '우마 외부자원지 유입량';
				case 'lumber_pct_plus_tech': return '태학 목재 증산 효과';
				case 'lumber_pct_plus_hero_assign': return '영웅 배속 목재 증산 효과';
				case 'lumbers_pct_plus_hero_skill': return '영웅 스킬 목재 증산 효과';
				case 'lumber_pct_plus_item': return '아이템 목재 증산 효과';
				case 'lumber_productivity': return '총 목재 생산량';
				case 'lumber_labor_force_max': return '최대 목재 생산 노동 인구';
				case 'lumber_labor_force_curr': return '현재 목재 생산 노동 인구';
				case 'lumber_production_territory': return '영지 목재 생산량';
				case 'lumber_production_valley': return '목재 외부자원지 유입량';
				case 'iron_pct_plus_tech': return '태학 철강 증산 효과';
				case 'iron_pct_plus_hero_assign': return '영웅 배속 철강 증산 효과';
				case 'irons_pct_plus_hero_skill': return '영웅 스킬 철강 증산 효과';
				case 'iron_pct_plus_item': return '아이템 철강 증산 효과';
				case 'iron_productivity': return '총 철강 생산량';
				case 'iron_labor_force_max': return '최대 철강 생산 노동 인구';
				case 'iron_labor_force_curr': return '현재 철강 생산 노동 인구';
				case 'iron_production_territory': return '영지 철강 생산량';
				case 'iron_production_valley': return '철강 외부자원지 유입량';
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0': // 로그일시
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '3': // 상세
					let _str = '';
					for (let __v of _v.split(';')) {
						let regexp = new RegExp(/([a-z_]*)\[([\d]*)\]/, "g");
						let ___v = regexp.exec(__v);
						if (! ___v) {
							break;
						}
						_str+= gm_log.logType(___v?.[1] ?? '');
						_str+= ': '+ ___v?.[2] ?? '0';
						_str+= '<br />';
					}
					return _str;
				default:
					return _v;
			}
		}
	});
});