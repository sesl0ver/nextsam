var selected_pk = null;
var yn_refund = null;
let list_offset = 1;

$(document).ready(function(){

	// 군주 계정명(ID)로 검색하는 부분
	if ($('#lord_search_by_lord_name_form').length > 0)
	{
		// 검색 버튼을 누르면
		$('#do_search_lord_name').mouseup(function(){
			var search_query = String($('#lord_name').val());
			if (search_query.length == 0 || search_query.search(/[\s]/g) >= 0)
			{
				alert('검색할 계정명을 입력하지 않았거나 계정명 사이에 공백이 있습니다.');
				return false;
			} else {
				var result_div = $('#search_result');
				result_div.empty();
				var post_data = {'action' : 'search_lord_name', 'search' : search_query, 'target_server_pk' : gm_info.selected_server_pk};
				if ($('#common_mode:checked').length > 0)
				{
					post_data['common_mode'] = 'Y';
				}
				get_lord_search_by_lord_name_result(result_div, post_data);
			}
		});
	}

	// 검색 버튼을 누르면
	$('#do_search_bill_chargeno').mouseup(function(){
		var search_query = String($('#bill_chargeno').val());
		if (search_query.length == 0 || search_query.search(/[\s]/g) >= 0)
		{
			alert('검색할 구매번호를 입력하지 않았거나  공백이 있습니다.');
			return false;
		} else {
			var result_div = $('#search_result');
			result_div.empty();
			var post_data = {};
			post_data['action'] = 'search_bill_chargeno';
			post_data['search'] = search_query;
			post_data['target_server_pk'] = gm_info.selected_server_pk;
			post_data['lord_name'] = String($('#lord_name').val());

			if ($('#common_mode:checked').length > 0)
			{
				post_data['common_mode'] = 'Y';
			}
			get_lord_search_by_lord_name_result(result_div, post_data);
		}
	});

	$('#confirm_qb_pack_refund').dialog({
		autoOpen: false,
		height: 600,
		width: 640,
		modal: true,
		buttons: {
			'결제 환불' : function() {
				if (yn_refund == 'Y')
				{
					alert('이미 환불 처리된 패키지 입니다.');
					return false;
				}

				if (confirm('정말로 환불을 진행 하시겠습니까?'))
				{
					var that = $(this);

					var post_data = {};
					post_data['action'] = 'do_refund';
					post_data['qbi_pac_pk'] = selected_pk;
					post_data['refund_type'] = $('#qb_pack_refund_type').val();
					post_data['refund_info'] = $('#qb_pack_refund_info').val();
					post_data['refund_description'] = $('#qb_pack_refund_description').val();

					$.post('./do/do_qbigpack_info.php', post_data, function(data, status) {
						if (data.result == 'fail')
						{
							alert(data.msg);
							return true;
						} else {
							that.dialog("close");
							top.document.location.reload();
						}
					}, 'json');
				}
			},
			'환불 취소' : function() {
				if (yn_refund == 'N')
				{
					alert('환불 처리되지 않은 패키지입니다.');
					return false;
				}

				if (confirm('정말로 환불 취소를 진행 하시겠습니까?'))
				{
					var that = $(this);

					var post_data = {};
					post_data['action'] = 'do_cancel_refund';
					post_data['qbi_pac_pk'] = selected_pk;

					$.post('./do/do_qbigpack_info.php', post_data, function(data, status) {
						if (data.result == 'fail')
						{
							alert(data.msg);
							return true;
						} else {
							that.dialog("close");
							top.document.location.reload();
						}
					}, 'json');
				}
			},
			'닫기' : function() {
				$(this).dialog("close");
				selected_pk = null;
				yn_refund = null;
			}
		},
		open: function() {
			// form 초기화
			$('#qb_pack_refund_type').val('');
			$('#qb_pack_refund_info').val('');
			$('#qb_pack_refund_description').val('');

			var post_data = {};
			post_data['action'] = 'get_qbigpack_data';
			post_data['qbi_pac_pk'] = selected_pk;

			$.post('./do/do_qbigpack_info.php', post_data, function(data, status) {
				if (data.result == 'fail')
				{
					alert(data.msg);
					return true;
				} else {
					var d = data.data;

					$('#qb_pack_qbi_pac_pk').text(d.qbi_pac_pk);
					$('#qb_pack_buy_dt').text(d.buy_dt);
					$('#qb_pack_store').text(d.store_type);
					$('#qb_pack_type').text(d.pack_type);
					$('#qb_pack_bill_chargeno').text(d.bill_chargeno);
					$('#qb_pack_yn_refund').text(d.yn_refund);
					$('#qb_pack_refund_dt').text(d.refund_dt);

					yn_refund = d.yn_refund;

					if (d.yn_refund == 'Y')
						$('#qb_pack_refund_data').show();
					else
						$('#qb_pack_refund_data').hide();

					if (d.refund_type)
						$('#qb_pack_refund_type').val(d.refund_type);

					if (d.refund_info)
						$('#qb_pack_refund_info').val(d.refund_info);

					if (d.refund_description)
						$('#qb_pack_refund_description').val(d.refund_description);

				}
			}, 'json');
		},
		close: function() {
			selected_pk = null;
			yn_refund = null;
		}
	});
});

function get_lord_search_by_lord_name_result(appendTarget, post_data)
{
	var id = "lord_search_result_serv_" + String(post_data.target_server_pk);
	var pagerId = "pager_lord_search_result_serv_" + String(post_data.target_server_pk);

	var table = $('<table id="' + id + '" class="jqGridTable"></table>');
	var div = $('<div id="' + pagerId + '"></div>');

	appendTarget.append(table).append(div);

	post_data['list_offset'] = String(list_offset);

	table_to_jqGrid(
		id,
		pagerId,
		'검색 결과 ',
		['서버', '군주명', '스토어 타입', '큐빅팩 타입', '구매 큐빅', '구매 시간', '구매 번호', '환불 상태'],
		[
			{'name' : 'server_name', 'index' : 'server_name', 'width' : 60, 'align' : 'center', 'sortable' : false},
			{'name' : 'lord_name', 'index' : 'lord_name', 'width' : 80, 'align' : 'center', 'sortable' : false},
			{'name' : 'store_type', 'index' : 'store_type', 'width' : 60, 'align' : 'center', 'sortable' : false},
			{'name' : 'pack_type', 'index' : 'pack_type', 'width' : 60, 'align' : 'center', 'sortable' : false},
			{'name' : 'buy_qbig', 'index' : 'buy_qbig', 'width' : 60, 'align' : 'center', 'sortable' : false},
			{'name' : 'buy_dt', 'index' : 'buy_dt', 'width' : 113, 'align' : 'center', 'sortable' : false},
			{'name' : 'bill_chargeno', 'index' : 'bill_chargeno', 'align' : 'center', 'sortable' : false},
			{'name' : 'yn_refund', 'index' : 'yn_refund', 'width' : 60, 'align' : 'center', 'sortable' : false}
		],
		post_data,
		function(id){
			selected_pk = id;
			$('#confirm_qb_pack_refund').dialog("open");
		},
		null,
		9999,
		function() {
			// jqGrid 그리고 나면 실행
			$('#gbox_lord_qbig_flow').hide();
			var b_post_data = {'view' : gm_info.view_name, 'action' : 'get_qbig_flow', 'search' : post_data.search};
			if ($('#common_mode:checked').length > 0)
			{
				b_post_data['common_mode'] = 'Y';
			}
			$.post(`/admin/gm/api/${gm_info['view_name']}`, b_post_data, function(data){
				if (typeof data.info == 'object')
				{
					for(e in data.info)
					{
						if ($('#qbig_flow_' + e).length > 0)
							$('#qbig_flow_' + e).html(data.info[e]);
					}
					$('#gbox_lord_qbig_flow').show();
				}
			}, 'json');
		}
	);
}