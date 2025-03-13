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
			alert('검색할 계정명 사이에 공백을 제거해주십시오.');
			return false;
		} else {
			post_data['lord_name'] = String(lord_name);
		}
	}

	// 좌표 // 입력 없으면 넘김
	var search_offset_x = String($('#offset_x').val());
	var search_offset_y = String($('#offset_y').val());
	if (search_offset_x.length > 0 && search_offset_y.length > 0)
	{
		// 검증 시작
		if (search_offset_x.search(/[\s]/g) >= 0 || search_offset_y.search(/[\s]/g) >= 0)
		{
			alert('검색할  좌표명 사이에 공백이 있습니다.');
			return false;
		} else if (!search_offset_x.match(/^[0-9]{1,3}$/) || !search_offset_y.match(/^[0-9]{1,3}$/)) {
			alert('좌표의 형식은 숫자 3자리입니다.');
			return false;
		} else if (search_offset_x < 1 || search_offset_x > 486 || search_offset_y < 1 || search_offset_y > 486) {
			alert('좌표의 범위는 1~486 까지 입니다.');
			return false;
		} else {
			post_data['offset'] = String(search_offset_x + 'x' + search_offset_y);
		}
	}

	if (_search_type.find('input[type=checkbox]:checked').length > 0)
	{
		post_data['search_type'] = [];
		$.each(_search_type.find('input[type=checkbox]:checked'), function(k, v){ post_data['search_type'].push( $(v).val() ); });
		post_data['search_type'] = post_data['search_type'].toString();
	}

	var result_div = $('#search_result');

	if ( !_add ){
		result_div.empty();
	}

	post_data['list_offset'] = String(_list_offset);
	post_data['target_server_pk'] = gm_info.selected_server_pk;

	if ( !_add ){
		get_log_search_result(result_div, post_data);
	}
	else{
		get_log_search_result_add(post_data);
	}

}

jqGrid_templete = {
	'sit' : {
		'header' : ['일시', '요충지 좌표', '군주', '좌표', '구분', '상세'],
		'body' : [
			{'name' : 'log_date', 'index' : 'log_date', 'fixed' : true, 'width' : 120, 'align' : 'center', 'sortable' : false},
			{'name' : 'point_posi_pk', 'index' : 'point_posi_pk', 'fixed' : true, 'width' : 120, 'align' : 'center', 'sortable' : false},
			{'name' : 'lord_name', 'index' : 'lord_name', 'fixed' : true, 'width' : 120, 'align' : 'center', 'sortable' : false},
			{'name' : 'posi_title', 'index' : 'posi_title', 'fixed' : true, 'width' : 70, 'align' : 'center', 'sortable' : false},
			{'name' : 'type', 'index' : 'type', 'fixed' : true, 'width' : 120, 'align' : 'center', 'sortable' : false},
			{'name' : 'description', 'index' : 'description', 'fixed' : false, 'align' : 'center', 'sortable' : false}
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

	var templete = jqGrid_templete['sit'];

	if (!templete)
	{
		alert('등록되지 않은 로그 형식입니다. 요청을 중지합니다.');
		return false;
	}

	table_to_jqGrid(
		id,
		null,
		'요충지 로그 검색 결과 ',
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
	$.post(`/admin/gm/api/${gm_info['view_name']}`, post_data, function(data){
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