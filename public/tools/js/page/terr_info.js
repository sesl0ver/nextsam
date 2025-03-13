const resources = [ "food", "horse", "lumber", "iron"];
const _common_td_style = 'text-align:center;';
let temp = null;

function dataRequest(){
	$.post('/admin/gm/api/terr_info', {}, function(data) {

		if ( data['terr_info_base'] )
		{
			$('#terr_info_base_status_gate').text(data['terr_info_base']['status_gate'] === '0' ? "개방" : "폐쇄");
			$('#terr_info_base_yn_alliance_camp').text(data['terr_info_base']['yn_alliance_camp'] === 'y' ? "허용함": "허용하지않음");
			$('#terr_info_base_loyalty').text(data['terr_info_base']['loyalty']);
			$('#terr_info_base_tax_rate').text(data['terr_info_base']['tax_rate']+" %");
			$('#lord_hero_name').text(data['lord_hero_name']);

			$('#terr_info_base_gold_providence').text(Math.floor(data['terr_info_base']['population_curr']/data['terr_info_base']['tax_rate']));
			$('#terr_info_base_tax_rate_plus_tech').text(data['terr_info_base']['tax_rate_plus_tech']);
			$('#terr_info_base_tax_rate_plus_hero_assign').text(data['terr_info_base']['tax_rate_plus_hero_assign']);
			$('#terr_info_base_tax_rate_plus_hero_skill').text(data['terr_info_base']['tax_rate_plus_hero_skill']);
			$('#terr_info_base_tax_rate_plus_item').text(data['terr_info_base']['tax_rate_plus_item']);

			for (let type of resources ){
				$(`#terr_info_base_storage_${type}_pct_limit`).text(`${data['terr_info_base']['storage_max'] * data['terr_info_base'][`storage_${type}_pct`] / 100}`);
				$(`#terr_info_base_${type}_rate`).text(`${data['terr_info_base'][`storage_${type}_pct`]}`);
			}

		}

		if ( data['production_per_hour']){
			for(let type of resources) {
				$(`#terr_info_base_${type}_per_hour`).text(`${data['production_per_hour'][`${type}`]}`);
			}
		}

		if ( data['gold_info']) {
			$('#gold_info_gold_curr').text(Math.floor(data['gold_info']['gold_curr']));
			$('#terr_info_base_gold_per_hour').text(data['gold_info']['gold_production']);
			$('#gold_info_gold_max').text(data['gold_info']['gold_max']);
		}

		if(data['terr_info_detail']) {

			for (let type of resources) {
				$(`#terr_info_detail_${type}_providence`).text(`${data[`terr_info_detail`][`${type}_providence`]}`);
				$(`#terr_info_detail_${type}_production_territory`).text(`${data[`terr_info_detail`][`${type}_production_territory`]}`);
				$(`#terr_info_detail_${type}_production_valley`).text(`${data['terr_info_detail'][`${type}_production_valley`]}`);
				$(`#terr_info_detail_${type}_pct_plus_tech`).text(`${data['terr_info_detail'][`${type}_pct_plus_tech`]} %`);
				$(`#terr_info_detail_${type}_pct_plus_hero_assign`).text(`${data['terr_info_detail'][`${type}_pct_plus_hero_assign`]} %`);
				$(`#terr_info_detail_${type}_pct_plus_hero_skill`).text(`${data['terr_info_detail'][`${type}_pct_plus_hero_skill`]} %`)
				$(`#terr_info_detail_${type}_pct_plus_item`).text(`${data['terr_info_detail'][`${type}_pct_plus_item`]} %`);
			}
		}

		if ( data['population_info']) {
			$('#population_info_population_curr').text(`${data['population_info']['population_curr']}`);
			$('#population_info_population_labor_force').text(`${data['population_info']['population_labor_force']}`);
			$('#population_info_waiting').text(`${Number(data['population_info']['population_curr']) - Number(data['population_info']['population_labor_force'])}`);

			$('#population_info_population_max').text(data['population_info']['population_max']);
			$('#population_info_loyalty').text(data['population_info']['loyalty']);
			$('#population_info_total_max').text(Number(data['population_info']['population_max']) * Number(data['population_info']['loyalty']));

			$('#population_info_population_trend').text(data['population_info']['population_trend']);
			$('#population_info_population_trend_amount').text(data['population_info']['population_trend_amount']);

			$('#population_info_population_upward_plus_tech').text(data['population_info']['population_upward_plus_tech']);
			$('#population_info_population_upward_plus_hero_assign').text(data['population_info']['population_upward_plus_hero_assign']);

			$('#population_info_population_upward_plus_hero_skill').text(data['population_info']['population_upward_plus_hero_skill']);
			$('#population_info_population_upward_plus_item').text(data['population_info']['population_upward_plus_item']);
		}

		if ( data['current_resource_info'] ) {
			$('#current_resource_info_food').text(data['current_resource_info'][1]);
			$('#current_resource_info_horse').text(data['current_resource_info'][2]);
			$('#current_resource_info_lumber').text(data['current_resource_info'][3]);
			$('#current_resource_info_iron').text(data['current_resource_info'][4]);
		}

		if ( data['timer']) {
			for(let i = 0, _length = data['timer'].length ; i < _length ; i++){
				let _elem = data['timer'][i];
				let _tr = $('<tr >', {class: "ui-widget-content jqgrow ui-row-ltr"}).append(
					$('<td />',{style: 'text-align:center; width:60px;', text: _elem['queue_type']})
				).append(
					$('<td />', {style: _common_td_style, text: _elem['description']})
				).append(
					$('<td />', {style: _common_td_style, text: _elem['build_time']})
				).append(
					$('<td />', {style: _common_td_style, text: _elem['start_dt']})
				).append(
					$('<td />', {style: _common_td_style, text: _elem['end_dt']})
				);

				$('#lord_own_timer_list tbody').append(_tr);
			}
		}

		if (data['buff_timer']) {
			for(let i = 0, length = data['buff_timer'].length ; i < length; i++ ){
				let elem = data['buff_timer'][i];

				let _tr = $('<tr />',{class:'ui-widget-content jqgrow ui-row-ltr' })
					.append($('<td />', {style: 'width:60; text-align:center;', text:elem['buff'] }))
					.append($('<td />', {style: _common_td_style, text: elem['start_dt']}))
					.append($('<td />', {style: _common_td_style, text: elem['end_dt']}))
					.append($('<td />', {style: _common_td_style, text: elem['left_time']}));

				$('#lord_own_terr_buff_list tbody').append(_tr);
			}
		}
	}, 'json');
}

$(document).ready(function(){

	dataRequest();

	$("#change_terr_name_form").dialog({
		autoOpen: false,
		height: 240,
		width: 320,
		modal: true,
		buttons: {
			"영지명 변경하기": function() {
				var terr_name = $('#terr_name').val();
				var change_cause = $('#change_terr_name_cause').val();

				if (terr_name.length < 1)
				{
					alert('변경할 영지명을 입력해 주십시오.');
					$('#terr_name').focus();
					return false;
				} else if (terr_name.length < 2) {
					alert('영지명은 최소 2글자를 사용해야 합니다.');
					$('#terr_name').focus();
					return false;
				} else if (terr_name.length > 4) {
					alert('영지명은 최대 4글자까지 사용할 수 있습니다.');
					$('#terr_name').focus();
					return false;
				}
				if (String(terr_name).search(/[^\uAC00-\uD7A3a-zA-Z0-9]/g) >= 0)
				{
					alert("변경할 영지명 중에 사용할 수 없는 글자가 있습니다. 영지명은 한글(초성체 제외), 영문자, 숫자만이 가능합니다.");
					$('#terr_name').focus();
					return false;
				}
				if (change_cause.length < 1)
				{
					alert('변경 사유를 입력해 주십시오.');
					$('#change_terr_name_cause').focus();
					return false;
				}

				var rthis = this;
				var post_data = {'server_pk' : $('#target_server_pk').val(), 'lord_pk' : gm_info.selected_lord_pk, 'terr_name' : terr_name, 'change_cause' : change_cause};
				$.post('/admin/gm/api/territoryNameChange', post_data, function(data){
					if (! data.result) {
						alert(data.msg);
						return false;
					} else {
						alert('영지명을 변경하였습니다.');
						$('#now_terr_name').html(terr_name);
						$(rthis).dialog("close");
					}
				}, 'json');
			},
			"취소": function() {
				$('#terr_name').val('');
				$('#change_terr_name_cause').val('');
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#terr_name').val('');
			$('#change_terr_name_cause').val('');
			$(this).dialog("close");
		}
	});

	$('#change_terr_name').click(function(){ $("#change_terr_name_form").dialog('open'); });
});