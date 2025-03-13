$(document).ready(function(){

	$.post('/admin/gm/api/serv_info_position_checker', {}, function(data){
		$('span.ghost_count').text(data.count);
		for (let v of data.rows) {
			let span = $('<span></span>');
			span.text(v.posi_pk);
			$('span.ghost_list').append(span);
		}
	}, 'json');
});