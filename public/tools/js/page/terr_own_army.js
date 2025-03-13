const common_tr_class = 'ui-widget-content jqgrow ui-row-ltr';
const common_th_class = 'ui-state-default ui-th-column ui-th-ltr text-center';
const td_style_text_center = 'text-align:center;';

function dataRequest() {
	$.post('/admin/gm/api/terr_own_army', {}, function(data){
		// detail row
		if ( data['detail'] ){
			for(let elem of data['detail'] ){
				let _tr = $('<tr />', {class: common_tr_class}).append(
					$('<td />',{style: td_style_text_center}).text(elem['title'])
				).append(
					$('<td />',{style: td_style_text_center}).text(elem['sum'])
				).append(
					$('<td />',{style: td_style_text_center}).text(elem['terr'])
				).append(
					$('<td />',{style: td_style_text_center}).text(elem['order'])
				).append(
					$('<td />',{style: td_style_text_center}).text(elem['medical'])
				);

				$('#lord_army_detail_statistics_list tbody').append(_tr);
			}
		}

		if ( data['total'] ){
			let _tr = $('<tr />', {class: common_tr_class}).append(
				$('<th />',{class: common_th_class, style: td_style_text_center}).text('합계(환산)')
			).append(
				$('<th />',{class: common_th_class, style: td_style_text_center}).text(data['total']['total'])
			).append(
				$('<th />',{class: common_th_class, style: td_style_text_center}).text(data['total'] ['terr'])
			).append(
				$('<th />',{class: common_th_class, style: td_style_text_center}).text(data['total'] ['order'])
			).append(
				$('<th />',{class: common_th_class, style: td_style_text_center}).text(data['total'] ['medical'])
			);

			$('#lord_army_detail_statistics_list tbody').append(_tr);
		}

		if ( data['fort'] ){
			let fort_data_key = ['trap', 'abatis', 'tower', 'wall_vacancy_max', 'wall_vacancy_curr', 'wall_vacancy_remain' ];

			for (let type of fort_data_key){
				$(`#fort_${type}`).text(`${data['fort'][type]}`);
			}
		}
	}, 'json');
}

$(document).ready(function(){
	dataRequest();
});