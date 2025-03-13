$(() => {
    let date_input = $('#date');
    let yesterday = moment().subtract(1, 'day').format("YYYY-MM-DD");

    date_input.val(yesterday);

    function exportStatistics(_api, _title) {
        let _date = date_input.val();
        if (! /^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/g.test(_date)) {
            alert('정확한 조회일을 입력하세요.');
            return;
        }
        let request = new XMLHttpRequest();
        request.onload = function () {
            if (this.status === 200) {
                let file = new Blob([this.response], {type: this.getResponseHeader('Content-Type')});
                let file_url = URL.createObjectURL(file);
                let a = document.createElement("a");
                if (typeof a.download === 'undefined') {
                    window.location = file_url;
                } else {
                    a.href = file_url;
                    a.download = `${_title}_${_date}.xlsx`;
                    document.body.appendChild(a);
                    a.click();
                }
            } else {
                alert("Error: " + this.status + "  " + this.statusText);
            }
        }

        request.open('POST', `/admin/gm/api/statistics/${_api}`);
        request.responseType = "blob";
        request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        request.send("date=" + _date);
    }

    $('#dau_export').mouseup(() => {
        exportStatistics('dau', 'DAU');
    });

    $('#nru_export').mouseup(() => {
        exportStatistics('nru', 'NRU');
    });

    $('#paid_export').mouseup(() => {
        exportStatistics('paid', 'Paid Users');
    });

    $('#lord_export').mouseup(() => {
        exportStatistics('lord', '전체 군주 수 (누적)');
    });

    $('#level_export').mouseup(() => {
        exportStatistics('level', '군주 등급 분포 (누적)');
    });

    $('#build_export').mouseup(() => {
        exportStatistics('build', '대전 레벨 분포 (누적)');
    });
});