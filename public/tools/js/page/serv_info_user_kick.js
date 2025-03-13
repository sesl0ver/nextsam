$(document).ready(function(){
	if ($('#lord_search_by_lord_name_form').length > 0)
	{
		// 검색 버튼을 누르면
		$('#do_search_lord_name').mouseup(function(){
			var search_query = String($('#lord_name').val());
			if (search_query.length == 0 || search_query.search(/[\s]/g) >= 0)
			{
				alert('검색할 군주명을 입력하지 않았거나 군주명 사이에 공백이 있습니다.');
				return false;
			} else {
				var result_div = $('#search_result');
				result_div.empty();
				var post_data = {'search' : search_query};
				get_lord_search_by_account_result(result_div, post_data);
			}
		});
	}
});

function get_lord_search_by_account_result(appendTarget, post_data)
{
	var id = "lord_search_result_serv_" + String(post_data.target_server_pk);
	var pagerId = "pager_lord_search_result_serv_" + String(post_data.target_server_pk);
	
	/*
	var h2 = $('<h2>[ ' + post_data.server_name + ' ] 서버</h2>');
	var hr = $('<hr />');
	*/
	
	var table = $('<table id="' + id + '" class="jqGridTable"></table>');
	var div = $('<div id="' + pagerId + '"></div>');
	
	appendTarget.append(table).append(div);
	
	table_to_jqGrid(
		id,
		pagerId,
		'검색 결과 ',
		['No.', '군주명', '계정 등록일', '마지막 로그인 시간', '블럭 여부', '로그인 여부'],
		[
	  		{'name' : 'lord_pk', 'index' : 'lord_pk', 'width' : 40, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'lord_name', 'index' : 'lord_name', 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'regist_dt', 'index' : 'regist_dt', 'width' : 130, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'last_login_dt', 'index' : 'last_login_dt', 'width' : 130, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'is_user_blocked', 'index' : 'is_user_blocked', 'width' : 40, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'is_login', 'index' : 'is_login', 'width' : 60, 'align' : 'center', 'sortable' : false}
	  	],
	  	post_data,
	  	function(id){
			// 행을 클릭하면 확인받고 userkick 호출
			if (id.search(/[^\d]/g) >= 0)
			{
				alert('선택할 수 없는 군주입니다.');
				return false;
			}
			if (confirm("선택한 유저를 킥 하시겠습니까?"))
			{
				userkick(id);
			}
		},
		null // 콜백 없음
	);
};