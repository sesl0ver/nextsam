$(document).ready(function(){
	let selected_lord_pk = null;
	// 군주 계정명(ID)로 검색하는 부분
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
				get_lord_search_by_lord_name_result(result_div, post_data);
			}
		});
	}

	$('#hero_give').dialog({
		autoOpen: false,
		height: 320,
		width: 640,
		modal: true,
		buttons: {
			"장수 지급": function() {
				var level = $('#hero_level option:selected').val();
				var hero_base_pk = $('#selected_hero_base_pk').val();
				var cause = $('#cause').val();

				if (cause.length == 0)
				{
					alert('장수 지급 사유를 입력해주십시오.');
					return false;
				}

				if (! selected_lord_pk)
				{
					alert('장수를 지급할 군주를 선택해주십시오.');
					return false;
				}

				if (level < 1 || level > 20)
				{
					alert('지급할 장수 레벨이 올바르지 않습니다.');
					return false;
				}

				if (!hero_base_pk)
				{
					alert('지급할 장수를 선택하여 주십시오.');
					return false;
				}

				var post_data = {'lord_pk' : selected_lord_pk , 'level' : level, 'hero_base_pk' : hero_base_pk, 'cause' : cause};

				$.post('/admin/gm/api/heroGive', post_data, (data) => {
					if (data.result == 'ok')
					{
						alert('해당 유저에게 장수를 지급하였습니다.');
						$(this).dialog("close");
					} else if (data.result == 'fail') {
						alert(data.msg);
					}
				}, 'json');
			},
			"취소": function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			// 선택한거 다 초기화
			$('#hero_level option:selected').removeAttr('selected');
			$('#hero_level option:first').attr('selected', 'selected');
			$('#cause').val('');
			$('#selected_hero_base_pk option:selected').removeAttr('selected');
			$('#selected_hero_base_pk option:first').attr('selected', 'selected');
		}
	});

	const get_lord_search_by_lord_name_result = (appendTarget, post_data) =>
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
			'검색 결과 ',
			['서버명', '군주명'],
			[
				{'name' : 'serv_name', 'index' : 'serv_name', 'align' : 'center', 'sortable' : false},
				{'name' : 'lord_name', 'index' : 'lord_name', 'align' : 'center', 'sortable' : false}
			],
			post_data,
			(id) => {
				// 행을 클릭하면 확인받고 userkick 호출
				if (id.search(/[^\d]/g) >= 0)
				{
					alert('선택할 수 없는 군주입니다.');
					return false;
				}
				selected_lord_pk = id;
				// $('#selected_lord_pk').val(id);
				$('#hero_give').dialog('open');
			},
			null // 콜백 없음
		);
	};
});