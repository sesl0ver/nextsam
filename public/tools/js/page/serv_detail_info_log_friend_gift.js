$(document).ready(function(){
	$("#search_time_start").datetimepicker({
		dateFormat : 'yy-mm-dd',
		currentText : 'Now',
		changeYear : true,
		chageMonth : true,
		hour : new Date().getHours(),
		minute : new Date().getMinutes()
	});

	$("#search_time_end").datetimepicker({
		dateFormat : 'yy-mm-dd',
		currentText : 'Now',
		changeYear : true,
		chageMonth : true,
		hour : new Date().getHours(),
		minute : new Date().getMinutes()
	});

	var search_type = $('#search_type');
	$.each(search_type.find('input[type=checkbox]'), function(k, v){
		$(v).change(function(){
			var checked_count = search_type.find('input:checked').length;
			$('#selected_search_type_count').html( (checked_count > 0)?String(checked_count + '개'):'없음' );
		});
	});

	$('#search_type_view').css('cursor', 'pointer').click(function(){
		if ($('#search_type_detail').css('display') != 'none')
		{
			$('#search_type_detail').hide();
		}
		else
		{
			$('#search_type_detail').show();
		}
	});

	let list_offset = 1;

	$('#search_log').mouseup(function(){
		list_offset = 1;
		grid_request(search_type, list_offset);
	});

	$('#search_log_add').mouseup(function(){
		list_offset = list_offset + 1;
		grid_request(search_type, list_offset, true);
	});

});

function grid_request(_search_type, _list_offset, _add = false )
{
	// 검색 버튼 누르면
	let post_data = {};

	// 검색 기간 // 입력 없으면 넘김
	var search_start = convert_datetime_text_to_timestamp(String($('#search_time_start').val()));
	var search_end = convert_datetime_text_to_timestamp(String($('#search_time_end').val()));

	if (search_start != false && search_end != false)
	{
		post_data['search_start'] = String(search_start);
		post_data['search_end'] = String(search_end);
	}

	// 계정 // 입력 없으면 넘김
	var lord_name = String($('#lord_name').val());
	if (lord_name.length > 0)
	{
		if (lord_name.search(/[\s]/g) >= 0)
		{
			alert('검색할 군주명을 사이에 공백을 제거해주십시오.');
			return false;
		} else {
			post_data['lord_name'] = String(lord_name);
		}
	} else {
		alert('검색할 군주명을 입력해주세요.');
		return false;
	}

	if (_search_type.find('input[type=checkbox]:checked').length > 0)
	{
		post_data['search_type'] = [];
		$.each(_search_type.find('input[type=checkbox]:checked'), function(k, v){ post_data['search_type'].push( $(v).val() ); });
		post_data['search_type'] = post_data['search_type'].toString();
	}

	// 시작 전에 결과 보여줄 곳에 있는거 다 지우고
	var result_div = $('#search_result');
	if ( !_add ){
		result_div.empty();
	}

	post_data['list_offset'] = String(_list_offset);
	post_data['gift_type'] = $('#gift_type option:selected').val();
	post_data['target_server_pk'] = gm_info.selected_server_pk;

	if ( !_add ){
		get_log_search_result(result_div, post_data);
	}
	else{
		get_log_search_result_add(post_data);
	}
}

jqGrid_templete = {
	'build' : {
		'header' : ['일시', '보낸군주', '받은군주', '아이템', '수량', '기타', '구분'],
		'body' : [
			{'name' : 'log_date', 'index' : 'log_date', 'fixed' : true, 'width' : 140, 'align' : 'center', 'sortable' : false},
			{'name' : 'from_acco_pk', 'index' : 'from_acco_pk', 'fixed' : true, 'width' : 140, 'align' : 'center', 'sortable' : false},
			{'name' : 'to_acco_pk', 'index' : 'to_acco_pk', 'fixed' : true, 'width' : 140, 'align' : 'center', 'sortable' : false},
			{'name' : 'm_item_pk', 'index' : 'm_item_pk', 'fixed' : true, 'width' : 140, 'align' : 'center', 'sortable' : false},
			{'name' : 'item_cnt', 'item_cnt' : 'item_cnt', 'fixed' : true, 'width' : 140, 'align' : 'center', 'sortable' : false},
			{'name' : 'description', 'item_cnt' : 'description', 'fixed' : true, 'width' : 140, 'align' : 'center', 'sortable' : false},
			{'name' : 'type', 'index' : 'type', 'align' : 'center', 'sortable' : false}

		]
	}
}

function get_log_search_result(appendTarget, post_data)
{
	var id = "log_search_result_serv_" + String(post_data.target_server_pk);
	var pagerId = "pager_log_search_result_serv_" + String(post_data.target_server_pk);

	var table = $('<table id="' + id + '" class="jqGridTable"></table>');
	var div = $('<div id="' + pagerId + '"></div>');

	appendTarget.append(table).append(div);

	var templete = jqGrid_templete['build'];

	if (!templete)
	{
		alert('등록되지 않은 로그 형식입니다. 요청을 중지합니다.');
		return false;
	}

	table_to_jqGrid(
		id,
		null,
		'친구 선물 로그 검색 결과 ',
		templete.header,
		templete.body,
		post_data,
		null,
		null,
		500
	);
}

function get_log_search_result_add(post_data)
{
	post_data['view'] = gm_info.view_name;
	$.post('./do/get_grid_response_dispatcher.php', post_data, function(data){
		//리스트 받았음
		var id = "log_search_result_serv_" + String(post_data.target_server_pk);
		var len = data.rows.length;
		var tbody = $('#' + id).find('tbody:first');

		if (data.rows.length > 0)
		{
			for (var i = 0; i < len; i++)
			{
				var elm = data.rows[i];

				var tr = $('<tr id="' + elm.id + '" class="ui-widget-content jqgrow ui-row-ltr"></tr>');

				for(e in elm.cell)
				{
					tr.append('<td style="text-align:center;">' + elm.cell[e] + '</td>');
				}
				tbody.append(tr);
			}
		}
	}, 'json');
}

function convert_datetime_text_to_timestamp(datetime)
{
	if (/^[\d]{4}\-[0-1][\d]\-[0-3][\d]\s[0-2][\d]\:[0-5][\d]$/.test(datetime))
	{
		datetime = String(datetime).split(/[\-\:\ ]/g);
		var dt = new Date();
		dt.setFullYear(datetime[0], (parseInt(datetime[1], 10) - 1), datetime[2]);
		dt.setHours(datetime[3], datetime[4], 0, 0);
		return parseInt((dt.getTime() / 1000), 10);
	}
	return false;
}