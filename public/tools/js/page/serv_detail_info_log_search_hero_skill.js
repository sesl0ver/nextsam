$(() => {
	gm_log.init({
		table_title: ['일시', '군주', '좌표', '고유키', '영웅', '구분', '스킬', '아이템', '슬롯 위치', '상세'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'SkillEquip': return '기술 장착';
				case 'UnequipSkill': return '기술 장착 해제';
				case 'DeleteEquipSkill': return '장착 기술 제거';
				case 'SkillMinusStat': return '스탯 감소';
				case 'SkillPlusStat': return '스탯 증가';
				case 'SkillRegist[get_pocket]': return '기술 획득 (기술 주머니)';
				case 'SkillRegist[get_box]': return '기술 획득 (기술 상자)';
				case 'SkillRegist[unequip]': return '기술 획득 (장착 해제)';
				case 'SkillRegist[lord_upgrade]': return '기술 획득 (군주 등급 상승)';
				case 'SkillRegist[skill_combination]': return '기술 획득 (기술 조합)';
				// case 'SkillRegist[get_quest]': return '기술 획득 (친구퀘스트)';
				case 'SkillBoxListRegist': return '미선택 상자 등록';
				case 'DeleteCombiSkill': return '기술 소비 (기술 조합)';
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0':
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				case '4':
					return (! _v) ? '' : `Lv.${ns_cs.m.hero[_v].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[_v].m_hero_base_pk}`)}`;
				case '5':
					return gm_log.logType(_v);
				case '6':
					if (! _v) {
						return _v;
					}
					return  ns_i18n.t(`hero_skill_title_${String(_v).substring(0, 4)}`) + ' Lv.' + ns_cs.m.hero_skil[_v].rare;
				case '7':
					return (! _v) ? '' : ns_i18n.t(`item_title_${_v}`);
				case '9':
					if (! _v) {
						return _v;
					}
					_v = _v.replaceAll('my_hero_skil_box_pk', '상자키');
					_v = _v.replaceAll('remain', '수량');
					return _v;
				default:
					return _v;
			}
		}
	});
});