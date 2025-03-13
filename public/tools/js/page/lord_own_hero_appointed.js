$(document).ready(function(){
	var hero_slot_info = {};

	// GM 로그
	table_to_jqGrid('lord_own_hero', 'pager_lord_own_hero', '군주 보유 등용 중 영웅 목록', ['No.', '관직', '영웅명', '소속영지', '레어', '소켓', '강화', '상태', '상세', '충성', '급여'], // , '거래 여부'
	[
		{'name' : 'no', 'index' : 'no', 'width' : 100, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		{'name' : 'offi_title', 'index' : 'offi_title', 'width' : 120, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		{'name' : 'hero_name', 'index' : 'hero_name', 'width' : 120, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		{'name' : 'terr_title', 'index' : 'terr_title', 'width' : 140, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		{'name' : 'rare_type', 'index' : 'rare_type', 'width' : 40, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		{'name' : 'socket', 'index' : 'socket', 'width' : 60, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		{'name' : 'enchant', 'index' : 'enchant', 'width' : 40, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		{'name' : 'status_cmd', 'index' : 'status_cmd', 'width' : 40, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		{'name' : 'status_detail', 'index' : 'status_detail', 'width' : 70, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		{'name' : 'loyalty', 'index' : 'loyalty', 'width' : 45, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		{'name' : 'employment_fee', 'index' : 'employment_fee', 'width' : 60, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		// {'name' : 'yn_trade', 'index' : 'yn_trade', 'width' : 60, 'align' : 'center', 'fixed' : true, 'sortable' : false}
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
		width: 600,
		modal: true,
		buttons: {
			'닫기' : function(){ $(this).dialog("close"); },
			'강화 에디트' : function() {
				$(this).dialog("close");
				$('#edit_hero_enchant').dialog('open');
			},
			'충성 에디트' : function() {
				if (hero_slot_info['yn_lord'] != 'N')
				{
					alert('군주는 충성 에디터가 불가능합니다.');
					return false;
				}

				$(this).dialog("close");
				$('#edit_hero_loyalty').dialog('open');
			},
			'기술 해제' : function() {
				if (hero_slot_info['status'] != 'A')
				{
					alert('등용 중 영웅만 기술 장착 에디트가 가능합니다.');
					return false;
				}
				if (hero_slot_info['status_cmd'] != 'I')
				{
					alert('대기 중인 영웅만 기술 장착 에디트가 가능합니다.');
					return false;
				}
				$(this).dialog("close");
				$('#unset_hero_skill').dialog('open');
			},
			'기술 장착' : function() {
				if (hero_slot_info['status'] != 'A')
				{
					alert('등용 중 영웅만 기술 장착 에디트가 가능합니다.');
					return false;
				}
				if (hero_slot_info['status_cmd'] != 'I')
				{
					alert('대기 중인 영웅만 기술 장착 에디트가 가능합니다.');
					return false;
				}
				$(this).dialog("close");
				$('#set_hero_skill').dialog('open');
			}
		},
		open: function() {
			var table = $('#hero_slot_info');
			table.find('td.hero_name').html(hero_slot_info['name']);
			table.find('td.hero_exp').html(hero_slot_info['skill_exp']);
			table.find('td.hero_slot_count').html(hero_slot_info['slot_info']);
			table.find('td.hero_offi').html(hero_slot_info['officer']);
			table.find('td.hero_loya').html(hero_slot_info['loyalty']);
			table.find('td.lord_ench').html(hero_slot_info['lord_enchant']);

			$.each(hero_slot_info, function(k, v){
				if (k == 'lord_enchant')
					v = v * 3;

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
			if (hero_slot_info['status_cmd'] == 'A') {
				$('#hero_manage_dismiss').hide().unbind();
				$('#hero_manage_unassign').show().unbind().bind('mouseup', function(){
					var post_data = {};
					post_data['hero_pk'] = hero_slot_info['hero_pk'];

					$.post('/admin/gm/api/changeHeroStatus/unAssign', post_data, function(data) {
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
			} else if (hero_slot_info['status_cmd'] == 'I') {
				$('#hero_manage_dismiss').show().unbind().bind('mouseup', function(){
					var post_data = {};
					post_data['hero_pk'] = hero_slot_info['hero_pk'];

					$.post('/admin/gm/api/changeHeroStatus/dismiss', post_data, function(data) {
						if (data.result == 'ok')
						{
							$('#hero_slot_info').dialog("close");
							$('#lord_own_hero').trigger( 'reloadGrid' );
						} else if (data.result == 'fail') {
							alert(data.msg);
						}
					}, 'json');
				});
				$('#hero_manage_unassign').hide().unbind();
				$('#hero_manage_assign_CityHall').show().unbind().bind('mouseup', function(){
					var post_data = {};
					post_data['hero_pk'] = hero_slot_info['hero_pk'];

					$.post('/admin/gm/api/changeHeroStatus/assignCityHall', post_data, function(data) {
						if (data.result === 'ok') {
							$('#hero_slot_info').dialog("close");
							$('#lord_own_hero').trigger( 'reloadGrid' );
						} else {
							alert(data.msg);
						}
					}, 'json');
				});
			} else {
				$('#hero_manage_dismiss').hide().unbind();
				$('#hero_manage_unassign').hide().unbind();
				$('#hero_manage_assign_CityHall').hide().unbind();
			}
		}
	});

	$('#edit_hero_enchant').dialog({
		autoOpen: false,
		height: 500,
		width: 400,
		modal: true,
		buttons: {
			'닫기' : function(){ $(this).dialog("close"); },
			'저장' : function(){
				var enchant_leadership = parseInt($('#leadership_ehchant').val(), 10);
				var enchant_mil_force = parseInt($('#mil_force_ehchant').val(), 10);
				var enchant_intellect = parseInt($('#intellect_ehchant').val(), 10);
				var enchant_politics = parseInt($('#politics_ehchant').val(), 10);
				var enchant_charm = parseInt($('#charm_ehchant').val(), 10);
				var hero_enchant = parseInt($('#hero_enchant_count').val(), 10);
				var lord_enchant = parseInt($('#lord_enchant_count').val(), 10);

				var valid = true;

				valid = valid && enchant_leadership >= 0;
				valid = valid && enchant_mil_force >= 0;
				valid = valid && enchant_intellect >= 0;
				valid = valid && enchant_politics >= 0;
				valid = valid && enchant_charm >= 0;

				valid = valid && enchant_leadership <= 50;
				valid = valid && enchant_mil_force <= 50;
				valid = valid && enchant_intellect <= 50;
				valid = valid && enchant_politics <= 50;
				valid = valid && enchant_charm <= 50;

				if (!valid)
				{
					alert('올바르지 않은 수치가 입력되어 있으므로 강화 수치를 수정할 수 없습니다.');
					return;
				}

				/*valid = false;
				valid = valid || (parseInt(hero_slot_info['leadership_enchant'], 10) / 3) != enchant_leadership;
				valid = valid || (parseInt(hero_slot_info['mil_force_enchant'], 10) / 3) != enchant_mil_force;
				valid = valid || (parseInt(hero_slot_info['intellect_enchant'], 10) / 3) != enchant_intellect;
				valid = valid || (parseInt(hero_slot_info['politics_enchant'], 10) / 3) != enchant_politics;
				valid = valid || (parseInt(hero_slot_info['charm_enchant'], 10) / 3) != enchant_charm;
				valid = valid || parseInt(hero_slot_info['lord_enchant'], 10) != lord_enchant;

				if (!valid)
				{
					alert('변경된 강화 수치가 없습니다.');
					return;
				}*/

				var edit_cause = $('#edit_enchant_cause').val();
				if (edit_cause.length < 1)
				{
					alert('강화 수치 수정 사유를 입력하여주십시오.');
					$('#edit_enchant_cause').focus();
					return false;
				}

				var post_data = {
					'lord_pk' : gm_info['selected_lord_pk'],
					'hero_pk' : hero_slot_info['hero_pk'],
					'leadership' : enchant_leadership,
					'mil_force' : enchant_mil_force,
					'intellect' : enchant_intellect,
					'politics' : enchant_politics,
					'charm' : enchant_charm,
					'hero_enchant' : hero_enchant,
					'lord_enchant' : lord_enchant,
					'cause' : edit_cause
				};

				var rthis = this;

				$.post('/admin/gm/api/changeHeroEnchant', post_data, function(data) {
					if (data.result == 'ok')
					{
						alert('강화 수치를 수정하였습니다.');
						$(rthis).dialog("close");
					} else if (data.result == 'fail') {
						alert(data.msg);
					}
				}, 'json');
			}
		},
		open: function() {
			$('#leadership_ehchant').val(parseInt(hero_slot_info['leadership_enchant'], 10));
			$('#mil_force_ehchant').val(parseInt(hero_slot_info['mil_force_enchant'], 10));
			$('#intellect_ehchant').val(parseInt(hero_slot_info['intellect_enchant'], 10));
			$('#politics_ehchant').val(parseInt(hero_slot_info['politics_enchant'], 10));
			$('#charm_ehchant').val(parseInt(hero_slot_info['charm_enchant'], 10));
			$('#hero_enchant_count').val(parseInt(hero_slot_info['enchant'], 10));

			if (hero_slot_info['yn_lord'] != 'Y')
			{
				$('#lord_enchant_count').val(0).attr('readonly','readonly');
			} else {
				$('#lord_enchant_count').removeAttr('readonly').val(hero_slot_info['lord_enchant']);
			}
		},
		close: function() {
		}
	});

	$('#edit_hero_loyalty').dialog({
		autoOpen: false,
		height: 250,
		width: 400,
		modal: true,
		buttons: {
			'닫기' : function(){ $(this).dialog("close"); },
			'저장' : function(){
				var hero_loyalty = $('#hero_loyalty').val();
				var valid = true;

				if (isNaN(hero_loyalty))
				{
					alert('숫자만 입력 가능합니다.');
					return false;
				}

				if (hero_loyalty < 0 || hero_loyalty > 100)
				{
					alert('잘못된 충성도 값을 입력하였습니다.');
					return false;
				}

				var edit_cause = $('#edit_loyalty_cause').val();
				if (edit_cause.length < 1)
				{
					alert('강화 수치 수정 사유를 입력하여주십시오.');
					$('#edit_loyalty_cause').focus();
					return false;
				}

				var post_data = {
					'action' : 'hero_loyalty',
					'hero_pk' : hero_slot_info['hero_pk'],
					'hero_loyalty' : hero_loyalty,
					'change_cause' : edit_cause
				};

				var rthis = this;

				$.post('/admin/gm/api/changeHeroLoyalty', post_data, function(data) {
					if (data.result == 'fail')
					{
						alert(data.msg);
						return false;
					} else {
						alert('충성도를 변경하였습니다.');
						$(rthis).dialog("close");
					}
				}, 'json');
			}
		},
		open: function() {
			$('#hero_loyalty').val(hero_slot_info['loyalty']);
			$('#edit_loyalty_cause').val('');
		},
		close: function() {
		}
	});

	$('select[depth="1"]').change(function() {
		$('select[depth="2"]').hide();
		$('select[depth="3"]').hide();
		var type = $(this).find('option:selected').val();
		$('select[depth="2"].type_' + type).show();
	});
	$('select[depth="2"]').change(function() {
		$('select[depth="3"]').hide();
		var selected_option = $(this).find('option:selected');
		var skill_base = selected_option.val();
		$('select[depth="3"].skill_base_' + skill_base).show();
		$('#set_hero_skill span.need_slot').html(selected_option.attr('slotuse'));
	});

	$('#set_hero_skill').dialog({
		autoOpen: false,
		height: 420,
		width: 400,
		modal: true,
		buttons: {
			'닫기' : function(){ $(this).dialog("close"); },
			'장착' : function(){
				var edit_cause = $('#add_skill_cause').val();
				if (edit_cause.length < 1)
				{
					alert('영웅 기술 장착 사유를 입력하여주십시오.');
					$('#add_skill_cause').focus();
					return false;
				}

				var type = $('select[depth="1"] option:selected').val();;
				var selected_skil_pk = $('#select_hero_skill').val();
				if (!selected_skil_pk)
				{
					alert('장착할 영웅 기술을 선택하여 주십시오.');
					return false;
				}

				var lord_pk = gm_info.selected_lord_pk;
				var hero_pk = hero_slot_info['hero_pk'];
				var post_data = {
					'lord_pk' : lord_pk,
					'hero_pk' : hero_pk,
					'm_hero_skil_pk' : selected_skil_pk,
					'cause' : edit_cause
				};

				var rthis = this;

				$.post('/admin/gm/api/setHeroSkill/Equip', post_data, function(data) {
					if (! data.result) {
						alert(data.msg);
					} else {
						alert('영웅 스킬을 장착하였습니다.');
						$(rthis).dialog("close");
					}
				}, 'json');
			}
		},
		open: function() {
			var total_slot = $('#set_hero_skill span.total_slot');
			var used_slot = $('#set_hero_skill span.used_slot');
			var avail_slot = $('#set_hero_skill span.avail_slot');
			var need_slot = $('#set_hero_skill span.need_slot');
			total_slot.html(parseInt(hero_slot_info['opened_slot'], 10));
			used_slot.html(parseInt(hero_slot_info['used_slot'], 10));
			avail_slot.html(parseInt(hero_slot_info['opened_slot'], 10) - parseInt(hero_slot_info['used_slot'], 10));
			need_slot.html(0);
		},
		close: function() {
			selected_skill_list = false;
			$('select[depth="3"]').hide();
			$('select[depth="2"]').hide();
			$('select option:selected').removeAttr('selected');
		}
	});

	$('#unset_hero_skill').dialog({
		autoOpen: false,
		height: 520,
		width: 400,
		modal: true,
		buttons: {
			'닫기' : function(){ $(this).dialog("close"); }
		},
		open: function() {
			var total_slot = $('#unset_hero_skill span.total_slot');
			var used_slot = $('#unset_hero_skill span.used_slot');
			var avail_slot = $('#unset_hero_skill span.avail_slot');
			var need_slot = $('#unset_hero_skill span.need_slot');
			total_slot.html(parseInt(hero_slot_info['opened_slot'], 10));
			used_slot.html(parseInt(hero_slot_info['used_slot'], 10));
			avail_slot.html(parseInt(hero_slot_info['opened_slot'], 10) - parseInt(hero_slot_info['used_slot'], 10));
			need_slot.html(0);

			var table = $('#unset_hero_skill');

			for(var i = 1; i <= 6; i++)
			{
				var str = '-';
				if (hero_slot_info['m_hero_skil_pk' + i] && hero_slot_info['m_hero_skil_pk' + i] > 0)
				{
					str = hero_slot_info['m_hero_skil_title_' + i];
					if (hero_slot_info['main_slot_pk' + i] != i)
					{
						str = hero_slot_info['main_slot_pk' + i] + '번 슬롯에서 사용 중입니다.';
						table.find('button.hero_slot_btn' + i).attr('disabled', 'disabled').unbind();
					} else {
						table.find('button.hero_slot_btn' + i).removeAttr('disabled').unbind().bind('mouseup', function(){
							var slot_num = $(this).attr('class').substring(13);
							if (confirm(slot_num + '슬롯에 장착되어있는 기술을 해제하시겠습니까?'))
							{
								var edit_cause = $('#add_unskill_cause').val();
								if (edit_cause.length < 1)
								{
									alert('영웅 기술 해제 사유를 입력하여주십시오.');
									$('#add_unskill_cause').focus();
									return false;
								}

								var lord_pk = gm_info.selected_lord_pk;
								var hero_pk = hero_slot_info['hero_pk'];
								var posi_pk = hero_slot_info['posi_pk'];
								var selected_skil_pk = hero_slot_info['m_hero_skil_pk' + slot_num];
								var post_data = {
									'lord_pk' : lord_pk,
									'hero_pk' : hero_pk,
									'slot_pk' : slot_num,
									'm_hero_skil_pk' : selected_skil_pk,
									'cause' : edit_cause
								};

								$.post('/admin/gm/api/setHeroSkill/unEquip', post_data, function(data) {
									if (! data.result) {
										alert(data.msg);
									} else {
										alert('영웅 기술을 해제하였습니다.');
										$('#unset_hero_skill').dialog("close");
									}
								}, 'json');
							}
						});
					}
				}
				table.find('td.hero_slot_' + i).html(str);
			}
		},
		close: function() {
			selected_skill_list = false;
		}
	});
});