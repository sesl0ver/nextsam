$(document).ready(function(){
	// 서버 목록
	table_to_jqGrid(
		'serv_info_list',
		'pager_serv_info_list',
		'서버 목록',
		['No.', '서버명', 'DB IP', 'DB Port', 'Memcached ip', 'Memcached port', 'chat serv. ip', 'chat serv. start port'],
		[
	  		{'name' : 'serv_pk', 'index' : 'serv_pk', 'width' : 16, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'serv_name', 'index' : 'serv_name', 'width' : 40, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'db_ip', 'index' : 'db_ip', 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'db_port', 'index' : 'db_port', 'width' : 20, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'memcached_server_ip', 'index' : 'memcached_server_ip', 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'memcached_server_port', 'index' : 'memcached_server_port', 'width' : 20, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'chat_server_ip', 'index' : 'chat_server_ip', 'width' : 80, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'chat_server_start_port', 'index' : 'chat_server_start_port', 'width' : 20, 'align' : 'center', 'sortable' : false}
	  	],
	  	null, // 전송할 건 없음
	  	function(id){
			// 행을 클릭할 경우
			new Promise((resolve, reject) => {
				$('select[name=server_pk]').val(id);
				resolve();
			}).then(() => {
				select_server();
			});
		},
		null // 콜백 없음
	);
	// table_to_jqGrid 함수의 상세는 request_func.js 파일 참조
});