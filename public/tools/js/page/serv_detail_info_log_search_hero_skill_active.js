$(() => {
	gm_log.init({
		table_title: ['일시', '군주', '좌표', '영웅', '구분', '랜덤 값', '스킬 값', '상세'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'decrease_loyalty': return '민심 방어';
				case 'none_battle': return '호통';
				case 'protect': return '약탈 보호';
				default: return _log_type;
			}
		},
		convertValue: function (_k, _v)
		{
			switch (_k) {
				case '0':
					return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
				/*case '4':
					return ns_i18n.t(`build_title_${_v}`);
				case '6':
					return (! _v) ? '-' : `Lv.${ns_cs.m.hero[_v].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[_v].m_hero_base_pk}`)}`;
				case '7':
					let o = _v.split(':');
					let type = o[0] === 'I' ? '내성' : '외성';
					return `${type} ${o[1]}`;
				case '8':
					return gm_log.logType(_v);*/
				default:
					return _v;
			}
		}
	});
});