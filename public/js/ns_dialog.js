class nsDialog
{
    constructor ()
    {
        this.tag_prefix = 'ns_dialog_';
        this.open_tag_ids = new Set(); // 열려있는 다이어로그의 ID 배열
        this.dialogs = {};
    }

    init ()
    {
        // TODO 필요한가?
        /*for (var type in this.sizeType) {
            for (var prpt in this.sizeType[type]) {
                this.sizeType[type][prpt] = eval(this.sizeType[type][prpt]);
            }
        }*/
    }

    open (_tag_id)
    {
        if (this.dialogs?.[_tag_id]) {
            let dlg = this.dialogs[_tag_id];
            if (dlg.loaded === false) {
                dlg.init();
            }
            if (dlg.visible !== false) {
                return;
            }
            if (dlg.do_close_all === true) {
                this.closeAll();
            }
            this.open_tag_ids.add(_tag_id);
            dlg.open();
        } else {
            console.log('debug:', `Not Found ${_tag_id} dialog`);
            document.location.reload(); // 임시패치이니 차후 개선해야함
        }
    }

    close (_tag_id)
    {
        if (this.dialogs?.[_tag_id]) {
            let dlg = this.dialogs[_tag_id];
            if (dlg.visible !== true) {
                return;
            }
            // qbw_util.hideKeyboard();
            if (dlg.child_dlalogs)  {
                for (let i = 0, j = dlg.child_dlalogs.length; i < j; i++) {
                    this.close(dlg.child_dlalogs[i].tag_id);
                }
            }
            this.open_tag_ids.delete(_tag_id);
            dlg.close();
        }
    }

    closeAll (_except_tag_ids = [])
    {
        // qbw_util.hideKeyboard();

        // _except_tag_ids = 제외 리스트
        if (this.open_tag_ids.size > 0) {
            for (let _tag_id of Array.from(this.open_tag_ids)) {
                if (! _except_tag_ids.includes(_tag_id)) {
                    this.close(_tag_id);
                }
            }
        }
    }

    setData (_tag_id, _data)
    {
        if (this.dialogs?.[_tag_id]) {
            this.dialogs[_tag_id].setData(_data);
        }
    }

    setDataOpen (_tag_id, _data)
    {
        this.setData(_tag_id, _data);
        this.open(_tag_id);
    }

    getData (_tag_id)
    {
        if (this.dialogs?.[_tag_id]) {
            return this.dialogs[_tag_id].getData();
        }
    }

    setFollower (_tag_id)
    {
        if (this.dialogs?.[_tag_id]) {
            this.dialogs[_tag_id].setFollower();
        }
    }
}

class nsDialogSet
{
    constructor (_tag_id, _dialog_css, _dialog_size_css, _options)
    {
        this.tag_id = _tag_id;
        this.dialog_css = _dialog_css;
        this.dialog_size_css = _dialog_size_css;
        this.options = _options;
        this.dialog_hide = true;

        this.parent_tag_id = null;
        this.parent_dialog = null;
        this.child_dlalogs = [];
        this.base_class = null;
        this.do_close_all = false;
        this.do_screen_page = false;
        this.do_content_scroll = true;

        this.size = {};
        this.loaded = false;
        this.obj = null;
        this.cont_obj = {};
        this.buttons = []; // 1회성으로 생성되는 버튼을 담아두는 배열. 다이어로그를 닫은 후 초기화 되므로 지워지면 안되는 버튼은 담지 말아야함.

        this.first_open = true;
        this.visible = false;
        this.open_cancel = false; // 다이어로그가 열리지 말아야 할 경우를 위해
        this.data = null;
        this.timer_handle = null;
        this.timer_handle_p = null;
        this.scroll_handle = null;
        this.__lud = null;

        if (_options?.parent_tag_id) {
            this.parent_tag_id = _options.parent_tag_id;
            this.parent_dialog = ns_dialog.dialogs[this.parent_tag_id];
            this.parent_dialog.child_dlalogs.push(this);
        }

        if (_options?.base_class) {
            this.base_class = _options.base_class;
        }

        this.do_close_all = _options?.do_close_all ?? false;
        this.do_screen_page = _options?.do_screen_page ?? false;
        this.do_content_scroll = _options?.do_content_scroll ?? true;
        this.do_open_animation = _options?.do_open_animation ?? true;

        this.init();
    }

    init ()
    {
        try {
            if (this.loaded !== true) {
                this.obj = document.querySelector('#' + ns_dialog.tag_prefix + this.tag_id);
                if (! this.obj) {
                    return Promise.reject({ message: `Not Found Dialog. ${this.tag_id}` });
                }
                this.obj.classList.add('hide');
                this.obj.classList.add(this.dialog_css);
                this.obj.classList.add(this.dialog_size_css);
                ns_button.buttonLoad(this.tag_id);

                // scroll
                this.cont_obj.dialog_content = new nsObject(`.dialog_content`, this.obj);
                this.cont_obj.content = new nsObject(`.content`, this.obj);
                if (this.do_content_scroll && this.cont_obj.dialog_content.element && this.cont_obj.content.element) {
                    this.scroll_handle = new nsScroll(this.cont_obj.dialog_content.element, this.cont_obj.content.element);
                } else {
                    this.scroll_handle = null;
                }

                this.loaded = true;
            }
        } catch (e) {
            console.error(e);
        }
    }

    open ()
    {
        if (this.first_open === true) {
            this.cacheContents();
        }
        this.open_cancel = false; // 초기화

        this.__lud = null;

        this.draw();
        this.customShow();

        this.visible = true;

        if (this.first_open !== false) {
            this.first_open = false;
        }
        if (this.open_cancel) { // 열지 말아야 할 경우 (예: counsel)
            this.close(); // 바로 닫기
        }
    }

    close ()
    {
        if (this.timer_handle_p) {
            this.timer_handle_p.clear();
            this.timer_handle_p = null;
        }

        if (this.timer_handle) {
            this.timer_handle.clear();
            this.timer_handle = null;
        }

        this.erase();
        this.customHide();

        this.visible = false;
        this.data = null; // 데이터 지워주기
    }

    cacheContents (_recursive)
    {
        if (this.base_class && !_recursive) {
            this.base_class.cacheContents.call(this, true);
        }
    }

    draw (_recursive)
    {
        if (this.base_class && !_recursive) {
            this.base_class.draw.call(this, true);
        }
    }

    erase (_recursive) // 기존의 undraw
    {
        if (this.base_class && !_recursive) {
            this.base_class.erase.call(this, true);
        }
    }

    customShow ()
    {
        this.obj.classList.remove('hide');
        this.obj.classList.add('show');
        if (this.do_open_animation) {
            this.obj.classList.add('open');
        }
        this.dialog_hide = false;

        // scroll
        if (this.scroll_handle) {
            this.scroll_handle.initScroll();
        }

        // timer
        this.timer_handle = this.timerHandler();
    }

    customHide ()
    {
        if (this.dialog_hide === true) {
            return;
        }

        this.obj.classList.remove('show');
        this.obj.classList.remove('open');
        this.obj.classList.add('hide');
        this.dialog_hide = true;
        this.buttonClear();
    }

    buttonClear ()
    {
        if (this.buttons.length > 0) {
            for (let _button of this.buttons) {
                _button.destroy();
            }
        }
        this.buttons = [];
    }

    contentRefresh ()
    {
        // 스크롤링 재계산.
        /*if (this.doContentScroll && this.scrollHandle) {
            // this.scrollHandle.refresh();
            this.scrollHandle.updateMetrics();
        }*/
    }

    timerHandler (_recursive)
    {
        if (this.base_class && !_recursive) {
            this.timer_handle_p = this.base_class.timerHandler.call(this, true);
            return;
        }
        return null;
    }

    timerHandlerProc (_tag_id, _recursive)
    {
        if (this.base_class && !_recursive) {
            this.base_class.timerHandlerProc.call(this, _tag_id, true);
        }
    }

    setData (_data)
    {
        this.data = (!_data) ? null : _data;
    }

    getData ()
    {
        return this.data;
    }

    setPosition (_x = 0, _y = 0, _is_correction = true)
    {
        // 기본적으로 mouseenter 이벤트를 위한 5px 보정치
        let correction = (! _is_correction) ? 0 : 5;
        _x = (this.obj.offsetWidth + correction > ns_engine.size.width - _x) ? _x -  this.obj.offsetWidth - correction : _x + correction;
        _y = (this.obj.offsetHeight + correction > ns_engine.size.height - _y) ? _y -  this.obj.offsetHeight - correction : _y + correction;
        this.obj.style.top = `${_y}px`;
        this.obj.style.left = `${_x}px`;
    }
}
let ns_dialog = new nsDialog();