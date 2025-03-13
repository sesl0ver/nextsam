$(() => {
	let do_search_hero = $('#do_search_hero');
	let hero_pk = new nsObject('#hero_pk');
	let search_result = new nsObject('#search_result');

	// search_result.hide();

	do_search_hero.click(() => {
		if (! hero_pk.value()) {
			return;
		}

		let params = new URLSearchParams();
		params.append('target_server_pk', gm_info.selected_server_pk);
		params.append('hero_pk', hero_pk.value());

		ns_xhr.post('/admin/gm/api/heroSearch', params, (_data, _status) => {
			if (! gm_log.returnCheck(_data)) {
				alert(_data.message);
				return;
			}
			_data = _data['ns_xhr_return']['add_data'];

			let m_hero = ns_cs.m.hero[_data.m_hero_pk];
			let m_base = ns_cs.m.hero_base[m_hero.m_hero_base_pk];

			let image = document.createElement('img');
			image.src = `/image/m_/${m_hero.m_hero_base_pk}.png`;
			search_result.find('.result_hero_image').empty().append(image);

			search_result.find('.result_hero_name').text(m_base.name);
			search_result.find('.result_hero_m_hero_base_pk').text(m_hero.m_hero_base_pk);
			search_result.find('.result_hero_m_hero_pk').text(_data.m_hero_pk);
			search_result.find('.result_hero_rare_type').text(_data.rare_type);
			search_result.find('.result_hero_lord_pk').text(_data.lord_pk);
			search_result.find('.result_hero_posi_pk').text(_data.posi_pk ?? '-');
			search_result.find('.result_hero_level').text(`Lv.${_data.level}`);

			let status = '';
			switch (_data.status) {
				case 'A': status = '등용'; break;
				case 'G': status = '영입'; break;
				case 'Y': status = '방출'; break;
				case 'C': status = '관직교체'; break;
				case 'S': status = '태업'; break;
				case 'V': status = '영입대기'; break;
				default: status = _data.status; break;
			}
			search_result.find('.result_hero_status').text(status);


			// search_result.show();
		});
	});
});