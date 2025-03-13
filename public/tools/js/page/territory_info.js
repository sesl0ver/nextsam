$(document).ready(function(){
	$('.delete_territory').mouseup(function(){	
		var post_data =  {'type' : 'delete_territory', 'posi_pk' : $(this).attr('id')};
		
		$.post('./do/do_action.php', post_data, function(data){
			// 로그인 요청 콜백
			if (data.result == 'ok')
			{
				$('#terr_' + data.posi_pk).remove();
				alert(data.msg);
			} else if (data.result == 'fail') {
				alert(data.msg);
				return false;
			}
		}, 'json');
	});
});