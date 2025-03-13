$(document).ready(function(){
	$('.btn_buffend').mouseup(function(){
		if (confirm('모든 영지에 같은 버프가 종료됩니다. 삭제하시겠습니까?'))
		{
			let post_data = {};
			post_data['time_pk'] = $(this).data('pk');
			post_data['buff_pk'] = $(this).attr('id');
			post_data['target_server_pk'] = gm_info.selected_server_pk;
			$.post('/admin/gm/api/buffDelete', post_data, function(data){
				// 로그인 요청 콜백
				if (data.rstate === 'fail') {
					alert(data.msg);
					return false;
				} else {
					document.location.reload();
				}
			}, 'json');
		}
	});
});