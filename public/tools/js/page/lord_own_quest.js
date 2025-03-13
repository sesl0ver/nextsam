$(document).ready(function(){
	$('#my_quest_btn1').bind('mouseup', function(){
		var table = $('<table id="lord_own_quest" class="jqGridTable"></table>');
		var div = $('<div id="pager_lord_own_quest"></div>');
		var target = $('#search_result').empty();
		
		target.append(table).append(div);
		
		// GM 로그
		table_to_jqGrid('lord_own_quest', 'pager_lord_own_quest', '일반 퀘스트',
		['PK', '메인 타이틀', '서브 타이틀', '진행', '보상', '시작일', '최종 갱신일', '여백의 미'],
		[
			{'name' : 'm_ques_pk', 'index' : 'm_ques_pk', 'width' : 20, 'align' : 'center', 'sortable' : false},
			{'name' : 'main_title', 'index' : 'main_title', 'width' : 100, 'align' : 'center', 'sortable' : false},
			{'name' : 'sub_title', 'index' : 'sub_title', 'width' : 100, 'align' : 'center', 'sortable' : false},
			{'name' : 'status', 'index' : 'status', 'width' : 20, 'align' : 'center', 'sortable' : false},
			{'name' : 'reward_status', 'index' : 'reward_status', 'width' : 20, 'align' : 'center', 'sortable' : false},
			{'name' : 'start_dt', 'index' : 'start_dt', 'width' : 60, 'align' : 'center', 'sortable' : false},
			{'name' : 'last_up_dt', 'index' : 'last_up_dt', 'width' : 60, 'align' : 'center', 'sortable' : false},
			{'name' : '-', 'index' : '-', 'align' : 'center', 'sortable' : false}
		],{'mode':'general'}, null, null, 10000);
	});
	$('#my_quest_btn2').bind('mouseup', function(){
		var table = $('<table id="lord_own_quest" class="jqGridTable"></table>');
		var div = $('<div id="pager_lord_own_quest"></div>');
		var target = $('#search_result').empty();
		
		target.append(table).append(div);
		
		// GM 로그
		table_to_jqGrid('lord_own_quest', 'pager_lord_own_quest', '일반 퀘스트',
		['PK', '메인 타이틀', '서브 타이틀', '진행', '보상', '시작일', '최종 갱신일', '여백의 미'],
		[
			{'name' : 'm_ques_pk', 'index' : 'm_ques_pk', 'width' : 20, 'align' : 'center', 'sortable' : false},
			{'name' : 'main_title', 'index' : 'main_title', 'width' : 100, 'align' : 'center', 'sortable' : false},
			{'name' : 'sub_title', 'index' : 'sub_title', 'width' : 100, 'align' : 'center', 'sortable' : false},
			{'name' : 'status', 'index' : 'status', 'width' : 20, 'align' : 'center', 'sortable' : false},
			{'name' : 'reward_status', 'index' : 'reward_status', 'width' : 20, 'align' : 'center', 'sortable' : false},
			{'name' : 'start_dt', 'index' : 'start_dt', 'width' : 60, 'align' : 'center', 'sortable' : false},
			{'name' : 'last_up_dt', 'index' : 'last_up_dt', 'width' : 60, 'align' : 'center', 'sortable' : false},
			{'name' : '-', 'index' : '-', 'align' : 'center', 'sortable' : false}
		],{'mode':'making'}, null, null, 10000);
	});
	$('#my_quest_btn3').bind('mouseup', function(){
		var table = $('<table id="lord_own_quest" class="jqGridTable"></table>');
		var div = $('<div id="pager_lord_own_quest"></div>');
		var target = $('#search_result').empty();
		
		target.append(table).append(div);
		
		// GM 로그
		table_to_jqGrid('lord_own_quest', 'pager_lord_own_quest', '일반 퀘스트',
		['PK', '메인 타이틀', '서브 타이틀', '진행', '보상', '시작일', '최종 갱신일', '여백의 미'],
		[
			{'name' : 'm_ques_pk', 'index' : 'm_ques_pk', 'width' : 20, 'align' : 'center', 'sortable' : false},
			{'name' : 'main_title', 'index' : 'main_title', 'width' : 100, 'align' : 'center', 'sortable' : false},
			{'name' : 'sub_title', 'index' : 'sub_title', 'width' : 100, 'align' : 'center', 'sortable' : false},
			{'name' : 'status', 'index' : 'status', 'width' : 20, 'align' : 'center', 'sortable' : false},
			{'name' : 'reward_status', 'index' : 'reward_status', 'width' : 20, 'align' : 'center', 'sortable' : false},
			{'name' : 'start_dt', 'index' : 'start_dt', 'width' : 60, 'align' : 'center', 'sortable' : false},
			{'name' : 'last_up_dt', 'index' : 'last_up_dt', 'width' : 60, 'align' : 'center', 'sortable' : false},
			{'name' : '-', 'index' : '-', 'align' : 'center', 'sortable' : false}
		],{'mode':'clear'}, null, null, 10000);
	});
});