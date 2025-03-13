const common_td_style = 'text-align:center;';
const common_tr_class = 'ui-widget-content jqgrow ui-row-ltr';

function dataRequest (){

    $.post('/admin/gm/api/terr_own_technique', {}, function(data){
        if ( data ){
            let _tr = null;
            for(let i = 0,length = data.length; i < length ; i++) {
                let _elem = data[i];

                if (i % 2 == 0) {
                    _tr = $('<tr />', {class:common_tr_class})
                };

                $(_tr).append(
                    $('<td />',{ style: common_td_style}).text(_elem['title'])
                ).append(
                    $('<td />', { style: common_td_style}).text(_elem['lord_tech_info'])
                ).append(
                    $('<td />', { style: common_td_style}).text(_elem['tech_info'])
                ).append(
                    $('<td />', { style: common_td_style}).text(_elem['tech_max'])
                );

                if ( i % 2 == 1 ){
                    $('#lord_own_tech_list tbody').append(_tr);
                }
            }

            // 홀수일 경우.
            if ( $(_tr).children().length !== 0) {
                $('#lord_own_tech_list tbody').append(_tr);
            }
        }
    }, 'json');
}

$(document).ready(() => {
    dataRequest();
});