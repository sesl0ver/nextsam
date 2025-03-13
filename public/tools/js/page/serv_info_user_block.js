$(document).ready(function(){
	let do_post_data = {};
	let post_lord_pk;
	if ($('#lord_search_by_lord_name_form').length > 0) {
		// 검색 버튼을 누르면
		$('#do_search_lord_name').mouseup(function(){
			let search_query = String($('#lord_name').val());
			if (search_query.length === 0 || search_query.search(/[\s]/g) >= 0) {
				alert('검색할 군주명을 입력하지 않았거나 군주명 사이에 공백이 있습니다.');
				return false;
			} else {
				let result_div = $('#search_result');
				result_div.empty();
				let post_data = {'search' : search_query};
				get_lord_search_by_account_result(result_div, post_data);
			}
		});
	}


	let limit_block_date = new nsObject('#limit_block_date');
	$(limit_block_date.element).datetimepicker({
		step: 60,
		format:'Y-m-d H:i',
		minDate: 0,
	});
	limit_block_date.value(moment().add(+1, 'days').format('YYYY-MM-DD HH:mm'));


	$( "#dialog-form" ).dialog({
		autoOpen: false,
		height: 400,
		width: 500,
		modal: true,
		buttons: {
			"유저 차단하기": () => {
				let blocked_cause = $('#blocked_cause').val();
				if (blocked_cause.length < 1)
				{
					alert('사유를 적지 않으면 유저를 차단할 수 없습니다.');
					return false;
				}
				do_post_data['lord_pk'] = post_lord_pk;
				do_post_data['limit_block_date'] = moment(limit_block_date.value()).format('X');
				do_post_data['blocked_cause'] = blocked_cause;
				$.post('/admin/gm/api/userBlock', do_post_data, (data) => {
					if (data.result === 'ok') {
						userkick(post_lord_pk);
						alert('선택한 유저를 차단하였습니다.');
						$(this).dialog("close");
					} else if (data.result === 'fail') {
						alert(data.msg);
					}
				}, 'json');
			},
			"취소": function() {
				$('#block_cause').val('');
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#block_cause').val('');
			$(this).dialog("close");
		}
	});

	function get_lord_search_by_account_result(appendTarget, post_data)
	{
		let id = "lord_search_result_serv_" + String(post_data.target_server_pk);
		let pagerId = "pager_lord_search_result_serv_" + String(post_data.target_server_pk);

		let table = $('<table id="' + id + '" class="jqGridTable"></table>');
		let div = $('<div id="' + pagerId + '"></div>');

		appendTarget.append(table).append(div);

		table_to_jqGrid(
			id,
			pagerId,
			'검색 결과 ',
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
				// 행을 클릭하면 확인받고 userkick 호출
				if (id.search(/[^\d]/g) >= 0) {
					alert('선택할 수 없는 군주입니다.');
					return false;
				}
				if (confirm("선택한 유저를 차단 하시겠습니까?")) {
					post_lord_pk = id;
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
					td6.text(moment(value).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm'));

					let td7 = cell.find('td:nth-child(7)');
					value = td7.text();
					td7.text(moment(value).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm'));
				}
			},
		);
	};
});