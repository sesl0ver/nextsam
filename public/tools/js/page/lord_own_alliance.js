$(document).ready(function(){
	$('#alliance_buttons').hide();
	$.post('/admin/gm/api/lord_own_alliance', {}, function(data) {
		if (data.result !== 'ok') {
			alert(data.msg);
			return;
		}
		let alliance_info_wrap = $('#alliance_info_wrap');
		if (! data?.alliance_info) {
			alliance_info_wrap.text('가입된 동맹이 없습니다.');
		} else {
			let info = data.alliance_info;

			$('#gm_alli_pk').val(info['alli_pk']);

			alliance_info_wrap.html(`
동맹명 : ${info['title']}<br />
맹주명 : ${info['master_lord_name']}<br />
가입자수 : ${info['now_member_count']} / ${info['max_member_count']}<br />
공격포인트 : ${info['attack_point']}<br />
방어포인트 : ${info['defence_point']}<br />
영향력 : ${info['power']}<br />
개설일 : ${info['regist_dt']}`);
			$('#alliance_buttons').show();
		}
	}, 'json');

	$('#alli_info1').bind('mouseup', function(){
		var table = $('<table id="lord_own_alliance" class="jqGridTable"></table>');
		var div = $('<div id="pager_lord_own_alliance"></div>');
		var target = $('#search_result').empty();

		target.append(table).append(div);

		// GM 로그
		table_to_jqGrid('lord_own_alliance', 'pager_lord_own_alliance', '동맹원 리스트', ['군주명', '직책', ''],
		[
			{'name' : 'lord_name', 'index' : 'lord_name', 'width' : 50, 'align' : 'center', 'sortable' : false},
			{'name' : 'type', 'index' : 'type', 'width' : 50, 'align' : 'center', 'sortable' : false},
			{'name' : 'dummy', 'index' : 'dummy', 'align' : 'center', 'sortable' : false}
		],{'mode':'member_list', 'alli_pk':$('#gm_alli_pk').val()});
	});
	$('#alli_info2').bind('mouseup', function(){
		var table = $('<table id="lord_own_alliance" class="jqGridTable"></table>');
		var div = $('<div id="pager_lord_own_alliance"></div>');
		var target = $('#search_result').empty();

		target.append(table).append(div);
		// GM 로그
		table_to_jqGrid('lord_own_alliance', 'pager_lord_own_alliance', '가입 현황 리스트', ['군주명', '타입', '가입일', ''],
		[
			{'name' : 'lord_name', 'index' : 'lord_name', 'width' : 50, 'align' : 'center', 'sortable' : false},
			{'name' : 'type', 'index' : 'type', 'width' : 50, 'align' : 'center', 'sortable' : false},
			{'name' : 'date', 'index' : 'date', 'width' : 50, 'align' : 'center', 'sortable' : false},
			{'name' : 'dummy', 'index' : 'dummy', 'align' : 'center', 'sortable' : false}
		],{'mode':'join_list', 'alli_pk':$('#gm_alli_pk').val()});
	});
	$('#alli_info3').bind('mouseup', function(){
		var table = $('<table id="lord_own_alliance" class="jqGridTable"></table>');
		var div = $('<div id="pager_lord_own_alliance"></div>');
		var target = $('#search_result').empty();

		target.append(table).append(div);
		// GM 로그
		table_to_jqGrid('lord_own_alliance', 'pager_lord_own_alliance', '외교관계 리스트', ['일자', '관계','동맹명', '맹주명', ''],
		[
			{'name' : 'date', 'index' : 'date', 'width' : 50, 'align' : 'center', 'sortable' : false},
			{'name' : 'type', 'index' : 'type', 'width' : 50, 'align' : 'center', 'sortable' : false},
			{'name' : 'name', 'index' : 'name', 'width' : 50, 'align' : 'center', 'sortable' : false},
			{'name' : 'lord_name', 'index' : 'lord_name', 'width' : 50, 'align' : 'center', 'sortable' : false},
			{'name' : 'dummy', 'index' : 'dummy', 'align' : 'center', 'sortable' : false}
		],{'mode':'alliance_relation', 'alli_pk':$('#gm_alli_pk').val()});
	});
	$('#alli_info4').bind('mouseup', function(){
		var table = $('<table id="lord_own_alliance" class="jqGridTable"></table>');
		var div = $('<div id="pager_lord_own_alliance"></div>');
		var target = $('#search_result').empty();

		target.append(table).append(div);
		// GM 로그
		table_to_jqGrid('lord_own_alliance', 'pager_lord_own_alliance', '동맹 히스토리', ['일자', '타입','상세'],
		[
			{'name' : 'date', 'index' : 'date', 'width' : 30, 'align' : 'center', 'sortable' : false},
			{'name' : 'type', 'index' : 'type', 'width' : 10, 'align' : 'center', 'sortable' : false},
			{'name' : 'desc', 'index' : 'desc', 'align' : 'left', 'sortable' : false}
		],{'mode':'history', 'alli_pk':$('#gm_alli_pk').val()});
	});
	$('#alli_info5').bind('mouseup', function(){
		var table = $('<table id="lord_own_alliance" class="jqGridTable"></table>');
		var div = $('<div id="pager_lord_own_alliance"></div>');
		var target = $('#search_result').empty();

		target.append(table).append(div);
		// GM 로그
		table_to_jqGrid('lord_own_alliance', 'pager_lord_own_alliance', '동맹 전투 히스토리', ['일자', '동맹', '타동맹', '타입', '보고서번호', '상세'],
		[
			{'name' : 'date', 'index' : 'date', 'width' : 30, 'align' : 'center', 'sortable' : false},
			{'name' : 'alli', 'index' : 'alli', 'width' : 30, 'align' : 'center', 'sortable' : false},
			{'name' : 'aalli', 'index' : 'aalli', 'width' : 30, 'align' : 'center', 'sortable' : false},
			{'name' : 'type', 'index' : 'type', 'width' : 30, 'align' : 'center', 'sortable' : false},
			{'name' : 'repo', 'index' : 'repo', 'width' : 30, 'align' : 'center', 'sortable' : false},
			{'name' : 'desc', 'index' : 'desc', 'align' : 'left', 'sortable' : false}
		],{'mode':'war_history', 'alli_pk':$('#gm_alli_pk').val()});
	});
});