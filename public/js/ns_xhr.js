class nsXhr {
    constructor () {
        this._instance = axios.create({
            baseURL: `//${document.location.host}`,
            timeout: 30000, // TODO 원래는 1000 디버깅을 위해 늘려놓음.
            headers: {
                // 'X-Custom-Header': 'foobar',
            }
        });
        this._indicator_object = null; // 인디케이터 오브젝트 설정
    }

    // qbw_cmd
    get (_url, _data = {}, _success_function = null, _options = null)
    {
        let options = (_success_function !== null) ? { successFunction: _success_function } : {};
        options = (_options !== null) ? { ...options, ..._options } : options;
        this.use('get', _url, _data, options);
    }

    post (_url, _data = {}, _success_function = null, _options = null)
    {
        let options = (typeof _success_function === 'function') ? { successFunction: _success_function } : {
            // 기본적으로 returnCheck 만 필요한 경우
            successFunction: (_data, _status) =>
            {
                if(! this.returnCheck(_data)) {
                    return;
                }
                _data = _data['ns_xhr_return']['add_data'];
                // console.log(_data, _status);
            }
        };
        options = (_options !== null) ? { ...options, ..._options } : options;
        this.use('post', _url, _data, options);
    }
    use (_method, _url, _data = {}, _options = {})
    {
        _data = (typeof _data !== 'object') ? {} : _data;
        _options.successFunction = (typeof _options?.successFunction === 'function') ? _options.successFunction : () => {}; // 따로 설정안하면 빈함수
        _options.errorFunction = (typeof _options?.errorFunction === 'function') ? _options.errorFunction : (error) => {
            console.error(error.code, error.message, error);
            this.unset();
            ns_dialog.setDataOpen('message', { text: `${ns_i18n.t('msg_xhr_request_timeout')}<br />(code: ${error.code})` }); // ${_status}
        };
        _options.useProgress = (!_options?.useProgress) ? false : _options.useProgress;
        _options.ignoreError = (!_options?.ignoreError) ? false : _options.ignoreError;

        ns_engine.xhr.fg_xhr_use = true;

        if (_options.useProgress === true) {
            this.xhrProgress(true);
        }

        if (!_data?.sid && ns_engine.game_data.sid) {
            _data.sid = ns_engine.game_data.sid;
        }

        if (!_data?.posi_pk && ns_engine.game_data.cpp) {
            _data.posi_pk = ns_engine.game_data.cpp;
        }

        _data.ns_web_version = ns_web_version;
        _data.server_pk = ns_engine.cfg.game_server_pk;
        // _data.chat_max = 0; // qbw_chat.getMax() TODO 아마 채팅 제한 때문에 사용했던 것으로 보이는데 이젠 소켓으로 처리 할꺼라 필요 없음.

        let instance_data = {
            method: _method,
            url: _url,
        };
        if (ns_auth.only_platform_mode === true) {
            _data['uuid'] = ns_auth.getUuid();
        }
        if (_method === 'get') {
            instance_data['params'] = new URLSearchParams(_data);
        } else {
            instance_data['data'] = _data;
        }
        if (ns_auth.only_platform_mode === true && ns_auth.getToken()) {
            instance_data['headers'] = {
                Authorization: `Bearer ${ns_auth.getToken()}`,
            }
        }
        this._instance(instance_data).then(_response => {
            if (_response.data?.['ns_xhr_return']) {
                try {
                    _options?.successFunction(_response.data, _response.status);
                } catch (e) {
                    console.error(e);
                }
            }
        }).catch((error) => {
            if (_options?.ignoreError && _options.ignoreError === true) {
                return;
            }
            if (typeof _options?.errorFunction === 'function') {
                try {
                    _options.errorFunction(error);
                } catch (e) {
                    console.error(e);
                }
            }
        }).finally(() => {
            this.unset();
        });
    }

    // qbw_cmd_unset
    unset ()
    {
        ns_engine.xhr.fg_xhr_use = false;
        ns_engine.xhr.fg_xhr_latest = ns_timer.now();
        ns_engine.xhr.fg_xhr_count++;
        this.xhrProgress(false);
    }

    // qbw_cmd_inprogress
    xhrProgress (_status = false)
    {
        if (ns_engine.xhr.fg_xhr_progress === _status) {
            return;
        }
        ns_engine.xhr.fg_xhr_progress = _status;
        if (_status) {
            ns_engine.game_object?.loading?.show();
        } else {
            ns_engine.game_object?.loading?.hide();
        }
    }

    // qbw_cmd_return_check
    returnCheck (_data)
    {
        this.xhrProgress(false);
        let _return = _data['ns_xhr_return']; // _data.qbw_cmd_return TODO 리턴값 정리 필요.
        switch (_return.code) {
            case 'error':
                ns_dialog.setDataOpen('message', { error_msg: 'error', text: _return.message });
                return false;
            case 'error_mt':  // maintenance
                ns_dialog.setDataOpen('message_mt');
                return false;
            case 'error_update': // web update
                ns_dialog.dialogs.message.close_game_over = true;
                ns_dialog.setDataOpen('message', { error_msg: 'error', text: _return.message });
                return false;
            case 'ign':
                ns_dialog.dialogs.message.close_game_over = true;
                ns_dialog.setDataOpen('message', { error_msg:'error', text: ns_i18n.t('msg_server_disconnect') });
                return false;
            case 'duplication':
                ns_dialog.closeAll();
                ns_dialog.dialogs.message.close_game_over = true;
                ns_dialog.setDataOpen('message', { error_msg:'error', text: ns_i18n.t('msg_concurrent_connection_error') }); // <strong>다른 곳에서 접속하여 게임을 종료 합니다.</strong><br /><br />다시 시작하여 주십시오.
                return false;
            default:
                // 두번째 매개변수가 real lp 여부를 결정
                if (_return?.['lp']) {
                    ns_engine.lpRequest();
                }
                if (_return?.['push_data']) {
                    ns_engine.lpCallback(_return['push_data'], false);
                }
                return true;
        }
    }
}
let ns_xhr = new nsXhr(); // 이전의 qbw_cmd