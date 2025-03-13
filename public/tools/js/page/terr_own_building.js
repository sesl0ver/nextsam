const common_tr_class = 'ui-widget-content jqgrow ui-row-ltr';
const common_td_style = 'text-align:center;';


function dataRequest(){
	$.post(`/admin/gm/api/terr_own_building`, {}, function(data){

		if ( data['in']) {
			for(let elem of data['in']) {
				let _tr = $('<tr />', {class: common_tr_class}).append(
					$('<td />', {style: common_td_style}).text(elem['title'])
				).append(
					$('<td />', {style: common_td_style}).text(elem['level'])
				).append(
					$('<td />', {style: common_td_style}).text(elem['in_castle_pk'])
				).append(
					$('<td />', {style: common_td_style}).text(elem['status'])
				);

				$('#in_castle_building_list tbody').append(_tr);
			}
		}


		if ( data['out']) {
			for(let elem of data['out']) {
				let _tr = $('<tr />', {class: common_tr_class}).append(
					$('<td />', {style: common_td_style}).text(elem['title'])
				).append(
					$('<td />', {style: common_td_style}).text(elem['level'])
				).append(
					$('<td />', {style: common_td_style}).text(elem['out_castle_pk'])
				).append(
					$('<td />', {style: common_td_style}).text(elem['status'])
				);

				$('#out_castle_building_list tbody').append(_tr);
			}
		}
	},'json');
}

$(document).ready(function(){
	dataRequest();
});