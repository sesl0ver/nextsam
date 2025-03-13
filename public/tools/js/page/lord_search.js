$(document).ready(function(){
	$('#submit').mouseup(function(){
		$.ajax({
			'type' : 'POST',
			'url' : '/admin/gm/api/lordSearch',
			'data' : {view:'gm_event_item',lord_name:$('textarea[name=lord_name]').val(), server_pk:$('select[name=search_server_pk]').val(), search_type:$('select[name=search_type]').val()},
			'success' : function(data){
				if(data.result == false)
				{
					alert(data.msg);
				} else {
					$('#result').prepend('==============================================================');
					$.each(data.d, function(k, v){
						$('#result').prepend('<p style="margin-bottom:3px;">' + v['lord_pk'] + ' - ' + v['lord_name'] + '</p>');
					});
				}
			},
			'error' : function(){
				alert('서버와의 통신 중 에러가 발생하였습니다.');
				return false;
			},
			'dataType' : 'json'
		});
	});
});