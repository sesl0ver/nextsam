$(document).ready(function(){
	// GM 로그
	table_to_jqGrid('gm_log', 'pager_gm_log', 'GM 로그', ['일자', '계정명', '기록'], [
		{'name' : 'regist_dt', 'index' : 'regist_dt', 'fixed' : true, 'width' : 200, 'align' : 'center', 'sortable' : false},
		{'name' : 'gm_id', 'index' : 'gm_id', 'fixed' : true, 'width' : 100, 'align' : 'center', 'sortable' : false},
		{'name' : 'description', 'index' : 'description', 'sortable' : false}
	]);
	// table_to_jqGrid 함수의 상세는 request_func.js 파일 참조
});