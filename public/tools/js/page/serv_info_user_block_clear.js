var do_post_data = {};

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

	$( "#dialog-form" ).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"유저 차단 해제": function() {
				var blocked_clear_cause = $('#blocked_clear_cause').val();
				if (blocked_clear_cause.length < 1)
				{
					alert('사유를 적지 않으면 유저의 차단을 해제할 수 없습니다..');
					return false;
				}
				var rthis = this;
				do_post_data['blocked_clear_cause'] = blocked_clear_cause;
				$.post('/admin/gm/api/userBlockClear', do_post_data, function(data) {
					if (data.result == 'ok')
					{
						alert('선택한 유저에게 걸린 차단을 해제하였습니다.');
						$(rthis).dialog("close");
					} else if (data.result == 'fail') {
						alert(data.msg);
					}
				}, 'json');
			},
			"취소": function() {
				$('#blocked_clear_cause').val('');
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#blocked_clear_cause').val('');
			$(this).dialog("close");
		}
	});
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
		'차단된 유저 검색 결과 ',
		['No.', '군주명', '계정 등록일', '마지막 로그인 시간', '차단 여부', '차단 시작', '차단 해제', '차단 이유', '로그인 여부'],
		[
			{'name' : 'lord_pk', 'index' : 'lord_pk', 'width' : 40, 'align' : 'center', 'sortable' : false},
			{'name' : 'lord_name', 'index' : 'lord_name', 'align' : 'center', 'sortable' : false},
			{'name' : 'regist_dt', 'index' : 'regist_dt', 'width' : 110, 'align' : 'center', 'sortable' : false},
			{'name' : 'last_login_dt', 'index' : 'last_login_dt', 'width' : 110, 'align' : 'center', 'sortable' : false},
			{'name' : 'is_user_blocked', 'index' : 'is_user_blocked', 'width' : 50, 'align' : 'center', 'sortable' : false},
			{'name' : 'user_block_start_dt', 'index' : 'user_block_start_dt', 'width' : 110, 'align' : 'center', 'sortable' : false},
			{'name' : 'user_block_end_dt', 'index' : 'user_block_end_dt', 'width' : 110, 'align' : 'center', 'sortable' : false},
			{'name' : 'blocked_cause', 'index' : 'blocked_cause', 'width' : 100, 'align' : 'center', 'sortable' : false},
			{'name' : 'is_login', 'index' : 'is_login', 'width' : 60, 'align' : 'center', 'sortable' : false}
	  	],
	  	post_data,
	  	function(id){
			if (id.search(/[^\d]/g) >= 0)
			{
				alert('선택할 수 없는 군주입니다.');
				return false;
			}
			if (confirm("선택한 유저의 차단을 해제 하시겠습니까?"))
			{
				do_post_data = {};
				do_post_data['lord_pk'] = id;
				$("#dialog-form").dialog("open");
			}
		},
		function (data){
			for (let _data of Object.values(data.rows)) {
				let cell = $(`#${_data['cell'][0]}`);

				let td3 = cell.find('td:nth-child(3)');
				let value = td3.text();
				td3.text(moment(value).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm'));

				let td4 = cell.find('td:nth-child(4)');
				value = td4.text();
				td4.text(moment(value).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm'));

				let td6 = cell.find('td:nth-child(6)');
				value = td6.text();
				td6.text((value === '') ? '-' : moment(value).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm'));

				let td7 = cell.find('td:nth-child(7)');
				value = td7.text();
				td7.text((value === '') ? '-' : moment(value).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm'));
			}
		},
	);
};