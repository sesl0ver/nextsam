const common_td_style = 'text-align:center;';

function dataRequest (){

    $.post('/admin/gm/api/terr_outer_occupied', {}, function(data){

        for(let i = 0,length = data.length; i < length ; i++) {
            let _elem = data[i];

            let _tr = $('<tr />',{ class: 'ui-widget-content jqgrow ui-row-ltr'}).append(
                $('<td />',{ style:'width:180px; text-align:center;' }).text(_elem['valley_posi_pk'])
            ).append(
                $('<td />', { style: 'width:80px; text-align:center;'}).text(_elem['valley_name'])
            ).append(
                $('<td />', { style: 'width:50px; text-align:center;'}).text(_elem['level']['level'])
            ).append(
                $('<td />', { style: 'width:133px; text-align:center;'}).text(_elem['regist_dt'])
            ).append(
                $('<td />', { class: common_td_style}).text(_elem['level']['food'])
            ).append(
                $('<td />', { class: common_td_style}).text(_elem['level']['horse'])
            ).append(
                $('<td />', { class: common_td_style}).text(_elem['level']['lumber'])
            ).append(
                $('<td />', { class: common_td_style}).text(_elem['level']['iron'])
            );
            $('#lord_own_terr_list tbody').append(_tr);
        }
    }, 'json');
}

$(document).ready(() => {
    dataRequest();
});