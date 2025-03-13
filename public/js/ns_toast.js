class nsToast {
    constructor() {
        this.toast_queue = [];
        this.toast_closed = true;
        this.check();
    }

    add (_data)
    {
        this.toast_queue.push(_data);
    }

    open (_data)
    {
        this.toast_closed = false;
        ns_dialog.setDataOpen('toast_message', _data);
    }

    close ()
    {
        this.toast_closed = true;
        ns_dialog.close('toast_message');
    }

    clear ()
    {
        this.toast_queue = [];
    }

    check ()
    {
        setInterval(() => {
            if (this.toast_closed === true) {
                let _toast = this.toast_queue.shift();
                if (_toast) {
                    this.open(_toast);
                }
            }
        }, 500);
    }
}
let ns_toast = new nsToast();