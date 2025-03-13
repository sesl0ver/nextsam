$(document).ready(function(){
	let left = $('#left');
	let center = $('#center');
	// 권한 보여주는 부분 left 맞추기
	$('#gm_permission').mouseover(function(){
		let offset = $('#gm_info').offset();
		$('#gm_permission_info').css({'left' : offset.left}).show();;
	}).mouseout(function(){
		$('#gm_permission_info').hide();
	});



	// 서버 시간 돌리기
	let span_server_time = $('#server_time');
	gm_info.ts = gm_info.loaded_timestamp;
	span_server_time.html('현재 시간: ' + timestamp_to_datetime_format(gm_info.ts));
	setInterval(function(){
		gm_info.ts = parseInt(gm_info.ts) + 1;
		span_server_time.html('현재 시간: ' + timestamp_to_datetime_format(gm_info.ts));
	}, 1000);

	// 왼쪽 메뉴 효과 적용 및 기본 상태
	if (left.length > 0)
	{
		left.find('> ul > li:first > ul:first').show();
		$(left.find('> ul > li > ul')[gm_info.opened_left_menu_idx]).show();
		if (gm_info.opened_left_menu_idx === 2 && gm_info.terr_selected)
		{
			$(left.find('> ul > li > ul')[3]).show();
		} else if (gm_info.opened_left_menu_idx === 3 && gm_info.lord_selected) {
			$(left.find('> ul > li > ul')[2]).show();
		}

		left.find('div.rootchild').each(function(k, v){
			if ($($(v).next()).length > 0)
			{
				$(v).mouseup(function(event){
					if (event.currentTarget.tagName !== event.target.tagName) return false;;
					let ul = $(this).next();
					if (ul.length > 0 && String(ul.css('display')).toLowerCase() === 'none')
					{
						ul.show();
					} else {
						ul.hide();
					}
					if (left.outerHeight() > center.outerHeight())
					{
						center.css({
							'min-height' : left.height(),
							'_height' : left.height()
						});
					}
				})
			}
		});
	}

	// 왼쪽 메뉴 부분과 내용 표시 부분 높이 맞추기
	if (left.length === 1 && center.length === 1)
	{
		if (left.outerHeight() > center.outerHeight())
		{
			center.css({
				'min-height' : left.height(),
				'_height' : left.height()
			});
		}
	}

	let quick_server_select = $('select[name=server_pk]');

	// 왼쪽 메뉴 셀렉트의 서버 선택 이벤트 바인딩
	if (quick_server_select.length > 0)
	{
		quick_server_select.change(function(){
			if (String($(this).val()).length > 0) {
				select_server();
			}
		});
	}

	// 왼쪽 메뉴 셀렉트의 영지 선택 이벤트 바인딩
	if (quick_server_select.length > 0)
	{
		$('#quick_terr_select').change(function(){
			if (String($(this).val()).length > 0)
			{
				select_terr(null, $(this).val());
			}
		});
	}

	// 왼쪽 메뉴 셀렉트의 군주 로그 선택 이벤트 바인딩
	if (quick_server_select.length > 0)
	{
		$('#select_log_view').change(function(){
			if (String($(this).val()).length > 0)
			{
				location.href = String(location.href).replace(/(view=){1}\w+/i, 'view=' + $(this).val());
			}
		});
	}
});

function array_to_jqGrid(id, pagerId, caption, colNames, colModel, array_data, onSelectRowFunc, onLoadCompleteFunc)
{
	if ($('#' + id).length > 0)
	{
		$('#' + id).jqGrid({
			'datatype' : 'local',
			'colNames' : colNames,
			'colModel' : colModel,
			'rowNum' : 20,
			'viewrecords' : true,
			'multiselect' : false,
			'caption' : caption,
			'width' : 1071,
			'height' : '100%',
			'pager' : String('#' + pagerId),
			'onSelectRow' : (onSelectRowFunc instanceof Function) ? onSelectRowFunc : (function(){}),
			'loadComplete' : (onLoadCompleteFunc instanceof Function) ? onLoadCompleteFunc : (function(){})
		});
	}
}

function timestamp_to_datetime_format(timestamp)
{
	let dateobj = new Date();
	dateobj.setTime(timestamp * 1000);
	let year = dateobj.getFullYear();
	let month = parseInt(dateobj.getMonth()) + 1;
	let date = dateobj.getDate();
	let hour = dateobj.getHours();
	let minute = dateobj.getMinutes();
	let second = dateobj.getSeconds();
	return (year + '년 ' + month + '월 ' + date + '일 ' + hour + '시 ' + minute + '분 ' + second + '초');
}