$(document).ready(function(){
	var hero_slot_info = {};

	// GM 로그
	table_to_jqGrid('lord_own_hero', 'pager_lord_own_hero', '군주 보유 영입 대기 중 영웅 목록', ['No.', '영웅명', '레어도', '소켓', '강화', '상태', '충성', '거래 여부'],
	[
		{'name' : 'no', 'index' : 'no', 'width' : 60, 'align' : 'center', 'sortable' : false},
		{'name' : 'hero_name', 'index' : 'hero_name', 'width' : 80, 'align' : 'center', 'sortable' : false},
		{'name' : 'rare_type', 'index' : 'rare_type', 'width' : 36, 'align' : 'center', 'sortable' : false},
		{'name' : 'socket', 'index' : 'socket', 'width' : 80, 'align' : 'center', 'sortable' : false},
		{'name' : 'enchant', 'index' : 'enchant', 'width' : 30, 'align' : 'center', 'sortable' : false},
		{'name' : 'status_cmd', 'index' : 'status_cmd', 'width' : 30, 'align' : 'center', 'sortable' : false},
		{'name' : 'loyalty', 'index' : 'loyalty', 'width' : 30, 'align' : 'center', 'sortable' : false},
		{'name' : 'yn_trade', 'index' : 'yn_trade', 'width' : 40, 'align' : 'center', 'sortable' : false}
	],
	false,
	function(hero_pk){
		$.ajax({
			'type' : 'POST',
			'url' : '/admin/gm/api/getHeroSlotInfo',
			'data' : {'hero_pk':hero_pk},
			'success' : function(data, textStatus, XMLHttpReq){
				hero_slot_info = data;
				$('#hero_slot_info').dialog('open');
			},
			'error' : function(){
				alert('서버와의 통신 중 에러가 발생하였습니다.');
				return false;
			},
			'dataType' : 'json'
		});
	});
	// table_to_jqGrid 함수의 상세는 request_func.js 파일 참조
	$('#hero_slot_info').dialog({
		autoOpen: false,
		height: 560,
		width: 400,
		modal: true,
		buttons: {
			'닫기' : function(){ $(this).dialog("close"); }
		},
		open: function() {
			var table = $('#hero_slot_info');
			table.find('td.hero_name').html(hero_slot_info['name']);
			table.find('td.hero_exp').html(hero_slot_info['skill_exp']);
			table.find('td.hero_slot_count').html(hero_slot_info['slot_info']);
			table.find('td.hero_offi').html(hero_slot_info['officer']);

			$.each(hero_slot_info, function(k, v){
				table.find('td.' + k).html(v);
			});

			for(var i = 1; i <= 6; i++)
			{
				var str = '-';
				if (hero_slot_info['m_hero_skil_pk' + i] && hero_slot_info['m_hero_skil_pk' + i] > 0)
				{
					str = hero_slot_info['m_hero_skil_title_' + i];
					if (hero_slot_info['main_slot_pk' + i] != i)
					{
						str = hero_slot_info['main_slot_pk' + i] + '번 슬롯에서 사용 중입니다.';
					}
					table.find('td.hero_slot_' + i).html(str);
				}
				table.find('td.hero_slot_' + i).html(str);
			}

			// 현재 상태에 따라 관리 여부 체크
			if (hero_slot_info['status'] == 'V') {
				$('#hero_manage_guest').show().unbind().bind('mouseup', function(){
					var post_data = {};
					post_data['action'] = 'guest';
					post_data['hero_pk'] = hero_slot_info['hero_pk'];

					$.post('./do/do_change_hero_status.php', post_data, function(data) {
						if (data.result == 'ok')
						{
							$('#hero_slot_info').dialog("close");
							$('#lord_own_hero').trigger( 'reloadGrid' );
						} else if (data.result == 'fail') {
							alert(data.msg);
						}
					}, 'json');
				});
				$('#hero_manage_assign_CityHall').hide().unbind();
			}
		}
	});
});