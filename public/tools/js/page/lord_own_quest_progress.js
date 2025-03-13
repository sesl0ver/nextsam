$(document).ready(function() {
	var selected_my_quest_pk = 0;
	// GM 로그
	table_to_jqGrid('lord_own_quest_progress', 'pager_lord_own_quest_progress', '진행 중인 퀘스트', ['No.', 'm_ques_pk', '메인타이틀', '서브타이틀', '보상', '등록시간'], [
			{'name' : 'my_ques_pk', 'index' : 'my_ques_pk', 'width' : 60, 'align' : 'center', 'sortable' : false},
			{'name' : 'm_ques_pk', 'index' : 'm_ques_pk', 'width' : 60, 'align' : 'center', 'sortable' : false},
			{'name' : 'main_title', 'index' : 'main_title', 'width' : 140, 'align' : 'center', 'sortable' : false},
			{'name' : 'sub_title', 'index' : 'sub_title', 'width' : 140, 'align' : 'center', 'sortable' : false},
			{'name' : 'description_reward', 'index' : 'description_reward', 'width' : 200, 'align' : 'center', 'sortable' : false},
			{'name' : 'start_dt', 'index' : 'start_dt', 'width' : 120, 'align' : 'center', 'sortable' : false},
		],
		false,
		function(_pk){
			selected_my_quest_pk = _pk;
			$('#selected_quest_title').html( $(this).find(`#${_pk} td:nth-child(4)`).html() );
			$('#selected_quest_reward').html( $(this).find(`#${_pk} td:nth-child(5)`).html() );
			$('#quest_modify_form').dialog('open');
		});

	$('#quest_modify_form').dialog({
		autoOpen: false,
		width: 520,
		modal: true,
		buttons: {
			"진행 중 퀘스트로 바꾸기" : function() {
				if (!selected_my_quest_pk)
				{
					alert('선택된 퀘스트가 없으므로 진행할 수 없습니다.');
					return false;
				}
				var cause = $('#cause').val();
				if (cause.length < 1)
				{
					alert('변경 사유를 입력해주십시오.');
					$('#cause').focus();
					return false;
				}
				var rthis = this;
				var post_data = {'lord_pk' : gm_info.selected_lord_pk, 'selected_my_quest_pk' : selected_my_quest_pk, 'now_state' : 'rewarded', 'change_state' : 'progress', 'cause' : cause};
				$.post('/admin/gm/api/changeQuestState', post_data, function(data){
					if (data.result == 'fail')
					{
						alert(data.msg);
						return false;
					} else {
						alert('퀘스트 상태를 변경하였습니다.');
						$(rthis).dialog("close");
						document.location.replace(document.location.href);
					}
				}, 'json');
			},
			"보상 미완료 퀘스트로 바꾸기" : function() {
				if (!selected_my_quest_pk)
				{
					alert('선택된 퀘스트가 없으므로 진행할 수 없습니다.')
					return false;
				}
				var cause = $('#cause').val();
				if (cause.length < 1)
				{
					alert('변경 사유를 입력해주십시오.');
					$('#cause').focus();
					return false;
				}
				var rthis = this;
				var post_data = {'lord_pk' : gm_info.selected_lord_pk, 'selected_my_quest_pk' : selected_my_quest_pk, 'now_state' : 'rewarded', 'change_state' : 'non_reward', 'cause' : cause};
				$.post('/admin/gm/api/changeQuestState', post_data, function(data){
					if (data.result == 'fail')
					{
						alert(data.msg);
						return false;
					} else {
						alert('퀘스트 상태를 변경하였습니다.');
						$(rthis).dialog("close");
						document.location.replace(document.location.href);
					}
				}, 'json');
			},
			"취소" : function () {
				$(this).dialog("close");
			}
		},
		close: function() {
			$(this).dialog("close");
		}
	});
});