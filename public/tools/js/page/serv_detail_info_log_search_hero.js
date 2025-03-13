$(() => {
	gm_log.init({
		table_title: ['일시', '웹ID', '군주', '좌표', '구분', '고유키', '영웅', '상태', '진행 명령', '명령 상태', '상세'],
		logType: function (_log_type)
		{
			switch (_log_type)
			{
				case 'Regist': return '획득';
				case 'Guest': return '영입';
				case 'Dismiss': return '해임';
				case 'Abandon': return '방출';
				case 'Appoint': return '등용';
				case 'Prize': return '포상';
				case 'Officer': return '관직 교체';
				case 'gm_loyalty': return '충성 치트(GM툴)';
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
				case '6':
					return (! _v) ? '-' : `Lv.${ns_cs.m.hero[_v].level} ${ns_i18n.t(`hero_name_${ns_cs.m.hero[_v].m_hero_base_pk}`)}`;
				case '7':
					switch (_v) {
						case 'A': return '등용';
						case 'G': return '영입';
						case 'Y': return '방출';
						case 'C': return '관직교체';
						case 'S': return '태업'; // TODO 태업은 사용안함.
						case 'V': return '영입대기';
						default: return _v;
					}
				case '8':
					switch (_v) {
						case 'I': return '대기';
						case 'A': return '배속';
						case 'C': return '명령';
						case 'T': return '부상';
						case 'P': return '강화';
						default: return _v;
					}
				case '9':
					switch (_v) {
						case 'None': return '없음';
						case 'Const': return '건설';
						case 'Encou': return '탐색';
						case 'Invit': return '초빙';
						case 'Techn': return '개발';
						case 'Scout': return '정찰';
						case 'Trans': return '수송';
						case 'Reinf': return '지원';
						case 'Attac': return '공격';
						case 'Preva': return '보급';
						case 'Camp': return '주둔';
						case 'Recal': return '회군';
						default: return _v;
					}
				case '10':
					if (/([a-z_])*\[([\d]*)\];/g.test(_v)) {
						let str = '';
						for (let o of _v.split(';')) {
							if (!o) {
								continue;
							}
							let __v = /([a-z_]*)\[([\d]*)\]/g.exec(o);
							let title = __v[1];
							title = title.replaceAll('loyalty', '충성증가');
							title = title.replaceAll('gold', '황금차감');
							title = title.replaceAll('prev', '이전');
							title = title.replaceAll('change', '변경');
							str += `${title}: ${__v[2]}<br />`;
						}
						return str;
					} else if (/hero_pickup\[([\d\w\W]*)\];/g.test(_v)) {
						let str = '';
						let __v = /hero_pickup\[([\d\w\W]*)\];/g.exec(_v);
						for (let o of __v[1].split(',')) {
							let _o = /([\w\W]*)\[([\d\w\W]*)\]/g.exec(o);
							let _title = _o[1];
							let value = '';
							if (_o[1] === 'pickup_type') {
								_title = '픽업키';
								value = _o[2];
							} else if (_o[1] === 'type') {
								_title = '픽업횟수';
								value = (_o[2] === 'single') ? '단차' : '10연차';
							} else if (_o[1] === 'pickup_count') {
								_title = '누적획수';
								value = `${_o[2]}회`;
							} else if (_o[1] === 'pickup_pity') {
								_title = '천장여부';
								value = (_o[2] === 'none') ? '일반' : '천장';
							} else if (_o[1] === 'log_qbig_use') {
								_title = '큐빅사용';
								value = _o[2];
							} else {
								value = _o[2];
							}
							str+= `${_title}: ${value}<br />`;
						}
						return str;
					} else if (/([\d]*):\[([\d\w\W]*)\];/g.test(_v)) {
						let str = '';
						for (let o of _v.split(';')) {
							if (! o) {
								continue;
							}
							let __v = /([\d]*):\[([\d\w\W]*)\]/g.exec(o);
							let title = `고유키: ${__v[1]}<br />`;
							let _str = '';
							for (let x of __v[2].split(',')) {
								let _x = x.split(':');
								let _title = _x[0].replaceAll('m_hero_pk', '마스터PK');
								_title = _title.replaceAll('m_offi_pk', '관직');
								_title = _title.replaceAll('desc', '상태');
								let value = _x[1];
								if (_x[0] === 'm_offi_pk') {
									value = ns_i18n.t(`office_title_${_x[1]}`);
								} else if (_x[0] === 'desc') {
									value = _x[1];
									value = value.replaceAll('now', '부여');
									value = value.replaceAll('change', '교체');
								}

								_str+= `${_title}: ${value}<br />`;
							}
							if (str !== '') {
								str += '<br />';
							}
							str += `${title}${_str}`;
						}
						return str;
					} else if (/gm_hero_give\[([\d\w\W]*)\];/g.test(_v)) {
						let __v = /gm_hero_give\[([\d\w\W]*)\];/g.exec(_v);
						return `GM툴 지급<br />사유 : ${__v[1]}`;
					} else if (_v === 'combination') {
						return '일반 조합';
					} else if (_v === 'special_combination') {
						return '특수 조합';
					} else if (_v === 'bid') {
						return '재야영웅 입찰';
					} else if (_v === 'gmtool_dismiss') {
						return 'GM툴 해임';
					} else if (_v === 'attendance_event') {
						return '출석 이벤트 지급';
					}
					return _v;
				default:
					return _v;
			}
		}
	});
});