$(document).ready(function(){
	let target_server_list = $('#target_server_list');
	target_server_list.removeAttr('disabled');
	get_server_list();
	target_server_list.attr('disabled', 'disabled');

	$('#target_server_all').change(function(){
		$('#target_server_list').attr('disabled', 'disabled');
	});

	$('#target_server_select_one').change(function(){
		$('#target_server_list').removeAttr('disabled');
	});

	// 검색 버튼을 누르면
	$('#do_search_button').mouseup(function(){

		let search_query = String($('#search_keyword').val());
		let search_type = String($('#search_type').val());

		// 군주명 필터 /영문(대소문자구별없음),숫자/
		if (search_query.length === 0 || search_query.search(/[\s]/g) >= 0) {
			alert('검색어를 입력하지 않았거나 검색어 사이에 공백이 있습니다.');
			return false;
		} else if (search_type !== 'udid' && search_query.search(/[^\uac00-\ud7a3\u3131-\u314e\u314f-\u3163\w\d]/g) >= 0) {
			// 한글 \uac00-\ud7a3 가-힣 \u3131-\u314e ㄱ-ㅎ \u314f-\u3163 ㅏ-ㅣ
			alert('검색어의 형식은 영문자, 한글, 숫자, _(밑줄)만이 가능합니다.');
			return false;
		} else {
			let target_server_type = $('#target_server_select > input[name=target_server]:checked');
			let target_server_pk = $('#target_server_select > select:first > option:selected');

			if (target_server_type.length < 1) {
				alert('검색할 서버 종류를 선택해주세요.');
				return false;
			}

			if (target_server_type.val() === 'select') {
				if (String(target_server_pk.val()).length < 1) {
					alert('군주를 검색할 대상 서버를 선택해주세요.');
					return false;
				}
			}

			// 결과 요청 시작

			// 시작 전에 결과 보여줄 곳에 있는거 다 지우고
			let result_div = $('#search_result');
			result_div.empty();

			if (target_server_type.val() === 'all') {
				// 모두에게 요청하는 경우...
				let serv_list = [];
				$('#target_server_select > select:first > option').each(function(k, v){
					if (k !== 0) {
						let post_data = {'type' : search_type, 'search' : search_query, 'target_server_pk' : $(v).val(), 'server_name' : $(v).html()};
						get_search_result(search_type, result_div, post_data);
						if (k > 0) {
							result_div.append('<br/>');
						}
					}
				});
			} else {
				// 한개 서버에게 요청하는 경우
				let post_data = {'type' : search_type, 'search' : search_query, 'target_server_pk' : target_server_pk.val(), 'server_name' : target_server_pk.html()};
				get_search_result(search_type, result_div, post_data);
			}
		}
	});
});

function get_server_list ()
{
	$.post('/admin/gm/serverList', {}, function(data){
		// 로그인 요청 콜백
		if (data.result === 'ok') {
			let list = data.data;
			let select = $('#target_server_list');
			select.empty();
			select.append('<option>서버 선택</option>');
			for (let s of Object.values(list)) {
				select.append(`<option value='${s.server_pk}'>${s.server_name}</option>`);
			}
		} else {
			console.error('서버 목록을 가져오지 못했습니다.');
		}
	}, 'json');
}

function get_search_result (_t, _a, _p)
{
	if (_t === 'territory')
	{
		get_terr_search_by_territory_result(_a, _p);
	}
	else if (_t === 'posi_pk')
	{
		get_terr_search_by_offset_result(_a, _p);
	}
	else if (_t === 'device_id')
	{
		get_device_id_search_by_offset_result(_a, _p);
	}
	else
	{
		get_lord_search_result(_a, _p);
	}
}

function get_lord_search_result(appendTarget, post_data)
{
	let id = "lord_search_result_serv_" + String(post_data.target_server_pk);
	let pagerId = "pager_lord_search_result_serv_" + String(post_data.target_server_pk);

	/*
	let h2 = $('<h2>[ ' + post_data.server_name + ' ] 서버</h2>');
	let hr = $('<hr />');
	*/

	let table = $('<table id="' + id + '" class="jqGridTable"></table>');
	let div = $('<div id="' + pagerId + '"></div>');

	appendTarget.append(table).append(div);

	table_to_jqGrid(
		id,
		pagerId,
		'[ ' + post_data.server_name + ' ] 서버 검색 결과 ',
		['군주pk', '계정ID', '군주명', '계정 등록일', '마지막 로그인 시간', '상태', 'UDID'],
		[
	  		{'name' : 'lord_pk', 'index' : 'lord_pk', 'fixed' : true, 'width' : 46, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'user_id', 'index' : 'user_id', 'fixed' : true, 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'lord_name', 'index' : 'lord_name', 'fixed' : true, 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'regist_dt', 'index' : 'regist_dt', 'fixed' : true, 'width' : 130, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'last_login_dt', 'index' : 'last_login_dt', 'fixed' : true, 'width' : 130, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'state', 'index' : 'state', 'fixed' : true, 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'udid', 'index' : 'udid', 'fixed' : true, 'width' : 450, 'align' : 'center', 'sortable' : false}
	  	],
	  	post_data,
	  	function(id){
			// 행을 클릭할 경우 군주 선택하기 위해 해당 군주가 있는 서버의 pk와 lord_pk를 넘겨주기
			if (id.search(/[^\d]/g) >= 0)
			{
				alert('선택할 수 없는 군주입니다.');
				return false;
			}
			select_lord(this.p.postData.target_server_pk, id);
		},
		null // 콜백 없음
	);
}

function get_device_id_search_by_offset_result(appendTarget, post_data)
{
	let id = "lord_search_result_serv_" + String(post_data.target_server_pk);
	let pagerId = "pager_lord_search_result_serv_" + String(post_data.target_server_pk);

	/*
	let h2 = $('<h2>[ ' + post_data.server_name + ' ] 서버</h2>');
	let hr = $('<hr />');
	*/

	let table = $('<table id="' + id + '" class="jqGridTable"></table>');
	let div = $('<div id="' + pagerId + '"></div>');

	appendTarget.append(table).append(div);

	table_to_jqGrid(
		id,
		pagerId,
		'[ ' + post_data.server_name + ' ] 서버 검색 결과 ',
		['군주pk', '계정ID', '군주명', '계정 등록일', '마지막 로그인 시간', '상태', '전화번호'],
		[
	  		{'name' : 'lord_pk', 'index' : 'lord_pk', 'fixed' : true, 'width' : 46, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'user_id', 'index' : 'user_id', 'fixed' : true, 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'lord_name', 'index' : 'lord_name', 'fixed' : true, 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'regist_dt', 'index' : 'regist_dt', 'fixed' : true, 'width' : 130, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'last_login_dt', 'index' : 'last_login_dt', 'fixed' : true, 'width' : 130, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'state', 'index' : 'state', 'fixed' : true, 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'udid', 'index' : 'udid', 'fixed' : true, 'width' : 450, 'align' : 'center', 'sortable' : false}
	  	],
	  	post_data,
	  	function(id){
			// 행을 클릭할 경우 군주 선택하기 위해 해당 군주가 있는 서버의 pk와 lord_pk를 넘겨주기
			if (id.search(/[^\d]/g) >= 0)
			{
				alert('선택할 수 없는 군주입니다.');
				return false;
			}
			select_lord(this.p.postData.target_server_pk, id);
		},
		null // 콜백 없음
	);
}

function get_terr_search_by_offset_result(appendTarget, post_data)
{
	let id = "terr_search_result_serv_" + String(post_data.target_server_pk);
	let pagerId = "pager_terr_search_result_serv_" + String(post_data.target_server_pk);

	/*
	let h2 = $('<h2>[ ' + post_data.server_name + ' ] 서버</h2>');
	let hr = $('<hr />');
	*/
	let table = $('<table id="' + id + '" class="jqGridTable"></table>');
	let div = $('<div id="' + pagerId + '"></div>');

	appendTarget.append(table).append(div);

	table_to_jqGrid(
		id,
		pagerId,
		'[ ' + post_data.server_name + ' ] 서버 검색 결과 ',
		['No.', 'ID', '군주명', '영지명', '좌표', '상태', 'UDID'],
		[
	  		{'name' : 'lord_pk', 'index' : 'lord_pk', 'fixed' : true, 'width' : 40, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'user_id', 'index' : 'user_id', 'fixed' : true, 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'lord_name', 'index' : 'lord_name', 'fixed' : true, 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'terr_title', 'index' : 'terr_title', 'fixed' : true, 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'posi_pk', 'index' : 'posi_pk', 'fixed' : true, 'width' : 130, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'state', 'index' : 'state', 'fixed' : true, 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'udid', 'index' : 'udid', 'fixed' : true, 'width' : 450, 'align' : 'center', 'sortable' : false}
	  	],
	  	post_data,
	  	function(id){
			// 행을 클릭할 경우 영지 선택하기 위해 해당 영지가 있는 서버의 pk와 posi_pk를 넘겨주기
			// if (!/^[\d]{1,3}x{1}[\d]{1,3}$/.test(String(id)))
			if (id.search(/[^\d]/g) >= 0)
			{
				// id가 정상적인 posi_pk의 형태가 아니면 (황건적 영지, 자원지인 경우) 선택할 수 없음. 오로지 군주의 영지만 가능
				// alert("황건적의 영지 또는 자원지인 경우 선택할 수 없습니다.\n군주의 영지를 선택해주세요.");
				alert('선택할 수 없는 군주입니다.');
				return false;
			}
			// select_terr(this.p.postData.target_server_pk, id);
			select_lord(this.p.postData.target_server_pk, id);
		},
		null // 콜백 없음
	);
}

function get_terr_search_by_territory_result(appendTarget, post_data)
{
	let id = "terr_search_result_serv_" + String(post_data.target_server_pk);
	let pagerId = "pager_terr_search_result_serv_" + String(post_data.target_server_pk);

	/*
	let h2 = $('<h2>[ ' + post_data.server_name + ' ] 서버</h2>');
	let hr = $('<hr />');
	*/

	let table = $('<table id="' + id + '" class="jqGridTable"></table>');
	let div = $('<div id="' + pagerId + '"></div>');

	appendTarget.append(table).append(div);

	table_to_jqGrid(
		id,
		pagerId,
		'[ ' + post_data.server_name + ' ] 서버 검색 결과 ',
		['No.', 'ID', '군주명', '영지명', '좌표', '상태', 'UDID'],
		[
	  		{'name' : 'lord_pk', 'index' : 'lord_pk', 'fixed' : true, 'width' : 40, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'user_id', 'index' : 'user_id', 'fixed' : true, 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'lord_name', 'index' : 'lord_name', 'fixed' : true, 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'terr_title', 'index' : 'terr_title', 'fixed' : true, 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'posi_pk', 'index' : 'posi_pk', 'fixed' : true, 'width' : 130, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'state', 'index' : 'state', 'fixed' : true, 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'udid', 'index' : 'udid', 'fixed' : true, 'width' : 450, 'align' : 'center', 'sortable' : false}
	  	],
	  	post_data,
	  	function(id){
			// 행을 클릭할 경우 영지 선택하기 위해 해당 영지가 있는 서버의 pk와 posi_pk를 넘겨주기
			// if (!String(id).match(/[\d]{1,3}x[\d]{1,3}/))
			if (id.search(/[^\d]/g) >= 0)
			{
				// id가 정상적인 posi_pk의 형태가 아니면 (황건적 영지, 자원지인 경우) 선택할 수 없음. 오로지 군주의 영지만 가능
				// alert("황건적의 영지 또는 자원지인 경우 선택할 수 없습니다.\n군주의 영지를 선택해주세요.");
				alert('선택할 수 없는 군주입니다.');
				return false;
			}
			// select_terr(this.p.postData.target_server_pk, id);
			select_lord(this.p.postData.target_server_pk, id);
		},
		null // 콜백 없음
	);
}