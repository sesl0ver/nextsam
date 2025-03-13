$(document).ready(function(){
	$.post('/admin/gm/api/terr_troop_condition', {}, function(data) {
		if (data.result !== 'ok') {
			alert(data.msg);
			return;
		}
		let tbody = $('#lord_own_terr_list tbody');
		tbody.empty();
		for (let row of data.rows) {
			let tr = document.createElement('tr');
			tr.classList.add('ui-widget-content');
			tr.classList.add('jqgrow');
			tr.classList.add('ui-row-ltr');

			let td = document.createElement('td');
			td.setAttribute('style', 'width:80px; text-align:center;');
			td.innerText = row.troo_pk;
			tr.appendChild(td);

			td = document.createElement('td');
			td.setAttribute('style', 'width:80px; text-align:center;');
			td.innerText = row.lord_name;
			tr.appendChild(td);

			td = document.createElement('td');
			td.setAttribute('style', 'width:180px; text-align:center;');
			td.innerText = row.src_posi_pk + '/' + row.from_position;
			tr.appendChild(td);

			td = document.createElement('td');
			td.setAttribute('style', 'width:180px; text-align:center;');
			td.innerText = row.dst_posi_pk + '/' + row.to_position;
			tr.appendChild(td);

			td = document.createElement('td');
			td.setAttribute('style', 'width:80px; text-align:center;');
			td.innerText = row.camptime;
			tr.appendChild(td);

			td = document.createElement('td');
			td.setAttribute('style', 'width:180px; text-align:center;');
			td.innerText = row.start_dt;

			tr.appendChild(td);
			td = document.createElement('td');
			td.setAttribute('style', 'width:180px; text-align:center;');
			td.innerText = row.arrival_dt;
			tr.appendChild(td);

			td = document.createElement('td');
			td.setAttribute('style', 'text-align:center;');
			td.innerText = '-';
			tr.appendChild(td);

			tbody.append(tr);
		}

	}, 'json');
});