$(document).ready(function(){
	let troop_infos = {};
	$.post('/admin/gm/api/terr_camp_army', {}, function(data) {
		if (data.result !== 'ok') {
			alert(data.msg);
			return;
		}
		let tbody = $('#outer_occupied_list tbody');
		let detail_view = $('#detail_view');
		let detail_template = $('#detail_template');

		tbody.empty();
		troop_infos = {};
		for (let [i, row] of Object.entries(data.rows)) {
			let tr = document.createElement('tr');
			tr.setAttribute('title', `detail_${row['dst_posi_pk']}`)
			tr.classList.add('ui-widget-content');
			tr.classList.add('jqgrow');
			tr.classList.add('ui-row-ltr');

			let td = document.createElement('td');
			td.setAttribute('style', 'width:70px; text-align:center;');
			td.innerText = String(parseInt(i)+1);
			tr.appendChild(td);

			td = document.createElement('td');
			td.setAttribute('style', ' text-align:center;');
			td.innerText = row.troop_type;
			tr.appendChild(td);

			td = document.createElement('td');
			td.setAttribute('style', ' text-align:center;');
			td.innerText = row.to_position;
			tr.appendChild(td);

			let clone = detail_template.clone();
			clone.attr('id', `detail_${row['dst_posi_pk']}`);
			for (let [k, v] of Object.entries(row)) {
				clone.find('.' + k).text(v ?? '');
			}
			detail_view.append(clone);

			troop_infos[row.dst_posi_pk] = row;
			tbody.append(tr);
		}

		let outer_tr = $('#outer_occupied_list > tbody > tr');
		if (outer_tr.length > 0) {
			outer_tr.mouseenter(function(){
				$(this).find('> td').css('background-color', '#FAD42E');
			}).mouseleave(function(){
				$(this).find('> td').css('background-color', 'inherit');
			}).mouseup(function(){
				$('#detail_view > div').hide();
				$('#' + this.title).show();
			});
		}
	}, 'json');
});