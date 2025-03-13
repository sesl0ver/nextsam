$(document).ready(function(){
	var report_info = {};

	// GM 로그
	table_to_jqGrid('lord_own_report', 'pager_lord_own_report', '보고서 현황', ['No.', '타입', '제목', '소속/출발/아군', '대상/목적/적군', '수신시간', '확인'],
		[
			{'name' : 'no', 'index' : 'no', 'width' : 25, 'align' : 'center', 'sortable' : false},
			{'name' : 'type', 'index' : 'type', 'width' : 40, 'align' : 'center', 'sortable' : false},
			{'name' : 'title', 'index' : 'title', 'align' : 'left', 'sortable' : false},
			{'name' : 'from_name', 'index' : 'from_name', 'width' : 50, 'align' : 'center', 'sortable' : false},
			{'name' : 'to_name', 'index' : 'to_name', 'width' : 50, 'align' : 'center', 'sortable' : false},
			{'name' : 'date', 'index' : 'date', 'width' : 30, 'align' : 'center', 'sortable' : false},
			{'name' : 'read', 'index' : 'read', 'width' : 20, 'align' : 'center', 'sortable' : false}
		],
		false,
		function(repo_pk){
			$.ajax({
				'type' : 'POST',
				'url' : '/admin/gm/api/viewLordReport',
				'data' : {'repo_pk':repo_pk},
				'success' : function(data, textStatus, XMLHttpReq){
					report_info = data;
					$('#report_info').dialog('open');
				},
				'error' : function(){
					alert('서버와의 통신 중 에러가 발생하였습니다.');
					return false;
				},
				'dataType' : 'json'
			});
		},
		function (data){
			if (! data?.rows) {
				return;
			}
			for (let _data of Object.values(data.rows)) {
				let cell = $(`#${_data['cell'][0]}`);
				let report_subject = cell.find('td:nth-child(3)');
				let report_type = report_subject.text();
				report_subject.text(ns_i18n.t(`report_${report_type}_subject`));

				let from = cell.find('td:nth-child(4)');
				let value = from.text();
				from.text(gm_log.convertPositionName(value));

				let to = cell.find('td:nth-child(5)');
				value = to.text();
				if (report_type === 'hero_skill_slot_expand') {
					to.text(value);
				} else {
					to.text(gm_log.convertPositionName(value));
				}

				let send_dt = cell.find('td:nth-child(6)');
				value = send_dt.text();
				send_dt.text(moment(value).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm'));
			}
 		},
	);
	// table_to_jqGrid 함수의 상세는 request_func.js 파일 참조
	$('#report_info').dialog({
		autoOpen: false,
		height: 600,
		width: 800,
		modal: true,
		buttons: {
			'닫기' : function(){ $(this).dialog("close"); }
		},
		open: function() {
			let table = $('#report_info');
			table.find('td.title').html(ns_i18n.t(`report_${report_info['report_type']}_subject`));
			table.find('td.to_name').html(report_info['to_lord_name']);
			table.find('td.from_name').html(report_info['from_lord_name']);

			let content_json = JSON.parse(report_info['content_json']);
			let report_summary = ns_i18n.t(`report_${report_info['report_type']}_summary`);
			let summary_data;

			let report_content = report_info['content'];
			switch (report_info['report_type']) {
				case 'scout_failure': // 정찰 실패
					report_content = ns_text.convertReportTitle(report_summary, [ns_util.numberFormat(content_json.scout_amount), ns_util.numberFormat(content_json.scout_dead)]);
					break;
				case 'scout_success': // 정찰 성공
					summary_data = ns_text.convertReportSummary(report_info.report_type, report_info.summary);
					report_content = ns_text.convertReportTitle(report_summary, summary_data);
					break;
				case 'injury_army_trans': // 부상병 이송
					report_content = ns_text.convertReportTitle(report_summary, [gm_log.convertPositionName(content_json.battle_position_name), gm_log.convertPositionName(content_json.army_position_name)]);
					break;
				case 'return_finish_1': // 부대 복귀
				case 'return_finish_2':
				case 'return_finish_3':
				case 'return_finish_4':
				case 'return_finish_5':
				case 'return_finish_6':
				case 'return_finish_7':
				case 'return_finish_8':
					report_content = ns_text.convertReportTitle(report_summary, [gm_log.convertPositionName(content_json.from_position), gm_log.convertPositionName(content_json.to_position)])
					break;
				case 'reinforce_finish_1': // 지원 도착
				case 'reinforce_finish_2':
				case 'reinforce_finish_3':
				case 'reinforce_finish_4':
				case 'reinforce_finish_5':
				case 'ally_troop_arrival': // 동맹 지원 도착
				case 'trans_finish': // 수송 도착
				case 'preva_finish': // 보급 도착
					report_content = ns_text.convertReportTitle(report_summary, [gm_log.convertPositionName(report_info.to_posi_name)]);
					break;
				case 'hero_skill_slot_expand': // 영웅 기술 슬롯 오픈
					summary_data = ns_text.convertReportSummary(report_info.report_type, report_info.summary);
					report_content = ns_text.convertReportTitle(report_summary, summary_data);
					break;
				case 'hero_bid_success': // 영웅 입찰
				case 'hero_bid_fail': // 영웅 입찰
				case 'hero_enchant_suc': // 영웅 강화 성공
				case 'hero_enchant_fal': // 영웅 강화 실패
				case 'enemy_march': // 적 부대 습격
				case 'shipping_finish': // 구매한 물품
				case 'shipping_sale': // 판매한 물품
				case 'army_loss': // 반란
				case 'hero_strike': // 태업
					summary_data = ns_text.convertReportSummary(report_info.report_type, report_info.summary);
					report_content = ns_text.convertReportTitle(report_summary, summary_data);
					break;
				case 'ally_troop_recall': // 동맹 지원 후 복귀
					report_content = report_info.to_posi_name + report_info.summary;
					break;
				default:
					break;
			}

			table.find('td.content').html(report_content);
		}
	});
});