$(document).ready(function(){
	$('#submit').mouseup(function(){
		$.ajax({
			'type' : 'POST',
			'url' : '/admin/gm/api/giveEventItem',
			'data' : {view:'gm_event_item',lord_name:$('textarea[name=lord_name]').val(), server_pk:$('select[name=target_server_pk]').val(), m_item_pk:$('select[name=m_item_pk]').val(), item_count:$('input[name=item_count]').val()},
			'success' : function(data){
				if(data.result == false)
				{
					alert(data.msg);
				} else {
					$('#result').prepend('==============================================================');
					$.each(data.d, function(k, v){
						$('#result').prepend('<p style="margin-bottom:3px;">' + v['name'] + ' : ' + v['text'] + ' - ' + v['date'] + '</p>');
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

	$('#search_item_btn').mouseup(function(){
		var keyword = $('input[name=search_item]').val();
		var item_pk = null;

		if(keyword.length < 1)
		{
			alert('검색어를 입력해주세요.');
		} else {
			$.each($('select[name=m_item_pk]').find('option'), function(i){
				if($(this).text().replace(keyword, '') != $(this).text())
				{
					item_pk = $(this).val();
					return false;
				}
			});
			if(item_pk)
			{
				$('select[name=m_item_pk]').val(item_pk);
			}
		}
	});
});