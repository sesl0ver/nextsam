$(document).ready(function(){
	getRegionList('Byeongju');
});

let info_tb = $('#preference_info');
let state_tb = $('#position_state_info');
let region_tb = $('#position_region_info');

function getRegionList(state)
{
	$.post('/admin/gm/api/preferenceInfo', {stats:state}, function(data){
		if (data.info) {
			info_tb.find('tbody').empty();
			$.each(data.info,function(k, v){
				let tr = document.createElement('tr');

				tr.classList.add('ui-widget-content');
				tr.classList.add('jqgrow');
				tr.classList.add('ui-row-ltr');
				tr.setAttribute('style', 'text-align:center;');

				let td = document.createElement('td');
				td.setAttribute('style', 'text-align:center; width:40px;');
				td.innerText = v['m_pref_pk'];
				tr.appendChild(td);

				td = document.createElement('td');
				td.setAttribute('style', 'text-align:center; width:150px;');
				td.innerText = v['state_name'];
				tr.appendChild(td);

				td = document.createElement('td');
				td.setAttribute('style', 'text-align:center; width:150px;');
				td.innerText = v['posi_regi_pk'];
				tr.appendChild(td);

				td = document.createElement('td');
				td.setAttribute('style', 'text-align:center; width:150px;');
				td.innerText = v['inspect'];
				tr.appendChild(td);

				info_tb.find('tbody').append(tr);
			});

		}

		if (data.state) {
			state_tb.find('tbody').empty();
			$.each(data.state,function(k, v){
				let tr = document.createElement('tr');

				tr.classList.add('ui-widget-content');
				tr.classList.add('jqgrow');
				tr.classList.add('ui-row-ltr');
				tr.setAttribute('style', 'text-align:center; cursor: pointer;');

				$(tr).bind('click', () => {
					getRegionList(v['state_name']);
				});

				let r1 = Math.round(v['ru_curr'] / v['ru_max'] * 100);
				let r2 = Math.floor(Math.round((v['ru_max']/2) - v['ru_curr']));
				let r3 = Math.round(v['ru_curr'] / (v['ru_max'] / 2) *100);

				let td = document.createElement('td');
				td.setAttribute('style', 'text-align:center; width:40px;');
				td.innerText = v['posi_stat_pk'];
				tr.appendChild(td);

				td = document.createElement('td');
				td.setAttribute('style', 'text-align:center; width:150px;');
				td.innerText = v['state_name'];
				tr.appendChild(td);

				td = document.createElement('td');
				td.setAttribute('style', 'text-align:center; width:150px;');
				td.innerText = v['open_orderno'];
				tr.appendChild(td);

				td = document.createElement('td');
				td.setAttribute('style', 'text-align:center; width:150px;');
				td.innerText = v['ru_curr'] + '/' + v['ru_max'] + '-' + r1 + '%';
				tr.appendChild(td);

				td = document.createElement('td');
				td.setAttribute('style', 'text-align:center; width:150px;');
				td.innerText = String(r2 - r3);
				tr.appendChild(td);

				state_tb.find('tbody').append(tr);
			});
		}

		if (data.region) {
			region_tb.find('tbody').empty();
			$.each(data.region,function(k, v){
				let tr = document.createElement('tr');

				tr.classList.add('ui-widget-content');
				tr.classList.add('jqgrow');
				tr.classList.add('ui-row-ltr');
				tr.setAttribute('style', 'text-align:center;');

				let td = document.createElement('td');
				td.setAttribute('style', 'text-align:center; width:40px;');
				td.innerText = v['posi_regi_pk'];
				tr.appendChild(td);

				td = document.createElement('td');
				td.setAttribute('style', 'text-align:center; width:150px;');
				td.innerText = v['state_name'];
				tr.appendChild(td);

				td = document.createElement('td');
				td.setAttribute('style', 'text-align:center; width:150px;');
				td.innerText = v['open_orderno'];
				tr.appendChild(td);

				td = document.createElement('td');
				td.setAttribute('style', 'text-align:center; width:150px;');
				let t1 = v['ru_curr'] / v['ru_max'] * 100;
				td.innerText = v['ru_curr'] + '/' + v['ru_max'] + " - " + roundXL(t1, 2) + '%';
				tr.appendChild(td);

				td = document.createElement('td');
				let t2 = Math.floor((v['ru_max'] / 2) - v['ru_curr']);
				let t3 = v['ru_curr'] / (v['ru_max']/2) * 100;
				td.setAttribute('style', 'text-align:center; width:150px;');
				td.innerText = t2 + " - " + roundXL(t3, 2) + '%';
				tr.appendChild(td);

				region_tb.find('tbody').append(tr);
			});
		}

	}, 'json');
}

function roundXL(n, digits) {
  if (digits >= 0) return parseFloat(n.toFixed(digits)); // 소수부 반올림

  digits = Math.pow(10, digits); // 정수부 반올림
  var t = Math.round(n * digits) / digits;

  return parseFloat(t.toFixed(0));
}