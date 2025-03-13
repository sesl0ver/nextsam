class nsChat {
    static count_message_limit = 3;
    constructor() {
        this.host = null;
        this.port = null;
        this.io = null;
        this.last_chat_message = '';
        this.last_check_message = '';
        this.same_message_check = '';
        this.current_message_box = null;
        // count 체크
        this.count_message = 0;
    }

    init(_host, _port) {
        this.host = _host;
        this.port = _port;
    }

    connect() {
        try {
            this.io = io.connect('//' + this.host + ':' + this.port);

            this.io.on('connect', () => {
                this.io.emit('auth', { sid: ns_engine.game_data.sid });
            });
        } catch (e) {
            console.error(e);
        }

        // message
        this.io.on('message', (data) => {
            let message = `(${code_set.chat_room[data.room]}) [${data.username}]: ${data.message}`;
            if (['public', 'alliance', 'whisper'].includes(data.room)) {
                this.receiveMessage(data.room, message);
            }
        });

        // notification
        this.io.on('notification', (data) => {
            let message = `(${code_set.chat_room[data.room]}) ${data.message}`;
            this.receiveMessage(data.room, message);
        });

        // message_blocked
        this.io.on('message_blocked', () => {
            ns_dialog.dialogs.chat.warning('blocked');
        });

        // alliance
        this.io.on('alliance', (data) => {
            let message;
            if (data.type === 'alliance_connect') {
                message = ns_i18n.t('msg_chat_alliance_game_start', [data.username]); // 동맹원 {{1}}님이 게임에 접속하였습니다.
            } else if (data.type === 'alliance_disconnect') {
                message = ns_i18n.t('msg_chat_alliance_game_exit', [data.username]); // 동맹원 {{1}}님이 게임을 종료하였습니다.
            } else if (data.type === 'alliance_join') {
                message = ns_i18n.t('msg_chat_alliance_join', [data.username]); //  '{{1}}님이 동맹에 가입하였습니다.
            }  else if (data.type === 'alliance_expulsion') {
                message = ns_i18n.t('msg_chat_alliance_expulsion', [data.username]); // {{1}}님이 동맹에서 제명되었습니다.
            }  else if (data.type === 'alliance_dropout') {
                message = ns_i18n.t('msg_chat_alliance_dropout', [data.username]); // {{1}}님이 동맹에서 탈퇴하었습니다.
            } else if (data.type === 'alliance_level_change') {
                message = ns_i18n.t('msg_chat_alliance_level_change', [data.username, codeset.t('ally_grade', data.level)]); //  '{{1}}님의 직책이 {{2}}(으)로 변경되었습니다.
            } else if (data.type === 'alliance_transfer') {
                message = ns_i18n.t('msg_chat_alliance_transfer', [data.prev_lord_namem, data.lord_name]); // 맹주 $1님이 $2님에게 동맹을 양도하였습니다.
            }

            if (message) {
                this.receiveMessage('notification', message);
            }
            if (data.data?.lord_pk && ns_util.math(data.data.lord_pk).eq(ns_cs.d.lord.lord_pk.v)) {
                ns_engine.lpRequest(); // Push 받는 당사자인 경우 LP 갱신 요청
            }
        });

        // LP request
        this.io.on('lp_request', () => {
            ns_engine.lpRequest();
        });

        // ping pong
        this.io.on('ping', () => {
            this.io.emit('pong', { pong: 'pong!'});
        });

        this.io.on('pong', () => {
            console.log('pong!');
        });

        // Last Chat Message
        setInterval(() => {
            this.updateLastMessage()
        }, 500);
    }

    submitMessage(message, room = 'public') {
        if (ns_cs.d.lord.is_chat_blocked.v === 'Y') {
            ns_dialog.dialogs.chat.warning('blocked');
            return;
        }
        if (this.count_message > nsChat.count_message_limit) {
            ns_dialog.dialogs.chat.warning('flood');
            return;
        }
        if (message === this.same_message_check) {
            ns_dialog.dialogs.chat.warning('same');
            return;
        }
        if (['public', 'alliance', 'whisper'].includes(room)) {
            this.io.emit('message', { room: room, message: message, server_pk: ns_engine.cfg.game_server_pk });
            this.same_message_check = message;
            this.count_message++;
        }
    }

    updateLastMessage() {
        if (this.current_message_box === null) {
            this.current_message_box = ns_dialog.dialogs.chat.createMessageBox('public', this.last_chat_message);
        }
        // Update Check
        if (typeof this.last_chat_message === 'string' && this.last_check_message !== this.last_chat_message) {
            let last_chat = ns_util.forbiddenWordCheck(this.last_chat_message);
            ns_engine.game_object.main_top_chat_message.empty().append(this.current_message_box);
            this.last_check_message = last_chat;
        }
    }

    receiveMessage (room, message) {
        this.last_chat_message = message;
        ns_dialog.dialogs.chat.receiveMessage(room, message);
    }

    allianceNotice (_type, _data)
    {
        this.io.emit('notification', { room: 'notification', 'type': _type, ..._data, server_pk: ns_engine.cfg.game_server_pk });
    }

    updateCount ()
    {
        if (ns_chat.count_message < 0) ns_chat.count_message = 0;
        if (ns_chat.count_message > 0) ns_chat.count_message--;
    }
}

let ns_chat = new nsChat();