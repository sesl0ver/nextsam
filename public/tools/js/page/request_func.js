// 서버로 ajax 요청을 보낸다던지 하는 모든 함수는 여기에 모임

function table_to_jqGrid(id, pagerId, caption, colNames, colModel, postParams, onSelectRowFunc, onLoadCompleteFunc, gridComplete, rownum)
{
	let selector = $('#' + id);
	if (selector.length > 0) {
		let post_data = { 'view' : gm_info['view_name']} ;

		if (typeof postParams == 'object') {
			for(e in postParams) {
				if (typeof e == 'string' && typeof postParams[e] == 'string') {
					post_data[e] = postParams[e];
				}
			}
		}

		let height = (gm_info['view_name'] === 'user_search') ? 113 : '100%';
		rownum = rownum || 30;

		selector.jqGrid({
			'url' : `/admin/gm/api/${gm_info['view_name']}`,
			'datatype' : 'json',
			'mtype' : 'POST',
			'autowidth' : false,
			'forceFit' : false,
			'postData' : post_data,
			'colNames' : colNames,
			'colModel' : colModel,
			'rowNum' : rownum,
			'viewrecords' : true,
			'multiselect' : false,
			'caption' : caption,
			'width' : $(document).find('h2:first').width(),
			'height' : height,
			'pager' : String('#' + pagerId),
			'onSelectRow' : (onSelectRowFunc instanceof Function) ? onSelectRowFunc : (function(){}),
			'loadComplete' : (onLoadCompleteFunc instanceof Function) ? onLoadCompleteFunc : (function(){}),
			'gridComplete' : (gridComplete instanceof Function) ? gridComplete : (function(){}),
			'userDataOnFooter' : true,
			'resizable': false,
		});
	}
}

function select_server()
{
	let form = $('form[name=quick_server_select]');
	form.submit();
}

function select_lord(server_pk, lord_pk)
{
	$.post('/admin/gm/api/selectLord', { 'server_pk' : server_pk, 'lord_pk' : lord_pk }, function(data){
		// 로그인 요청 콜백
		if (data.result === 'ok') {
			location.href = String(location.href).replace(/(view\=){1}[\w]+/i, 'view=lord_info');
		} else if (data.result === 'fail') {
			alert('군주 선택에 실패하였습니다.');
		}
	}, 'json');
}

function select_terr(server_pk, posi_pk)
{
	var post_data =  {'type' : 'select_terr', 'server_pk' : server_pk, 'posi_pk' : posi_pk};
	$.post('./do/do_action.php', post_data, function(data){
		// 로그인 요청 콜백
		if (data.result == 'ok')
		{
			location.href = String(location.href).replace(/(view\=){1}[\w]+/i, 'view=terr_info');
			return;
		} else if (data.result == 'fail') {
			alert('영지 선택에 실패하였습니다.');
			return false;
		}
	}, 'json');
}

function userkick(lord_pk)
{
	var post_data = {'lord_pk' : lord_pk};
	$.post('/admin/gm/api/userKick', post_data, function(data) {
		if (data.result == 'ok')
		{
			alert('선택한 유저를 킥하였습니다.');
		} else if (data.result == 'fail') {
			alert(data.msg);
		}
	}, 'json');
}