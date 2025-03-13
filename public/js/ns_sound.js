class nsSound
{
    constructor ()
    {
        this.sound_path = '/sound/';
        this.file_postfix = '.mp3';
        this.volume = { bgm: 0.6, effect: 0.8 }
        this.muted = { bgm: false, effect: false }
        this.sound_object = {};
        this.init();
    }

    init()
    {
        this.sound_list = {
            bgm_castle: 'bgm_castle',
            bgm_world: 'bgm_world',
            button_1: 'button01',
            button_2: 'button02',
            button_3: 'button03',
            button_4: 'button04',
            select: 'select',
            magic_cube: 'magiccube',
            magic_cube_spin: 'magiccube_spin',
            magic_cube_finish: 'magiccube_finish',
            quest_receive: 'quest_receive',
            hero_open: 'heroopen',
            report_arrival: 'report_arrival',
            search_complete: 'search_comp',
            build_force_complete: 'buildforce_comp',
            move_complete: 'move_comp',
            alert: 'alert',
            move: 'move',
            buff: 'buff',
            card_next: 'card_next',
            card_open: 'card_open',
            card_open_start: 'card_open_start',
            card_open_end: 'card_open_end',
            card_turn: 'card_turn',
            dialog_popup: 'dialog_popup',
            popup: 'popup',
            special_combination: 'special_combi',
            shovel: 'shovel',
            hammer: 'hammer',
            rollover: 'rollover',
            page: 'page',
            toast: 'toast',
            construct_complete: 'construct_complete',
            hero_combination: 'hero_combination',
            hero_level_up: 'hero_level_up',
            research_complete: 'research_complete',
            training_complete: 'training_complete',
        }

        for (let [_key, _file] of Object.entries(this.sound_list)) {
            let _regexp = new RegExp('^bgm', 'g');
            this.add((_regexp.test(_key)) ? 'bgm' : 'effect', _key, _file);
        }
    }

    add (_type, _id, _file)
    {
        if (! this.sound_object?.[_id]) {
            this.sound_object[_id] = {
                bgm: (_type === 'bgm'),
                audio: new Audio(`${this.sound_path}${_file}${this.file_postfix}`),
                loop: false
            }
            this.sound_object[_id].audio.addEventListener('ended', () => {
                // TODO 사운드가 끝난 이후 이벤트가 필요하면 이곳에서 처리
                if (this.sound_object[_id].loop === true) {
                    this.play(_id, true); // 루프라면 계속 재생함.
                }
            });
        }
    }

    play (_id, _loop = false)
    {
        // TODO 차후 군주 옵션 확인 후 사운드 재생 여부 확인하도록 수정 필요.
        /*if (! this?.[_type]?.[_id] || ! ns_cs.d.lord?.sound_effect || ns_cs.d.lord.sound_effect.v !== 'Y') {
            return;
        }*/
        // BGM인 경우에는 어쩌지?
        let _regexp = new RegExp('^bgm', 'g');
        if (! _regexp.test(_id)) {
            let _s = this.sound_object?.[_id];
            let _sound = _s.audio.cloneNode(true);
            new Promise((resolve, reject) => {
                resolve();
            }).then(() => {
                _sound.play();
                _sound.muted = this.muted.effect;
                _sound.volume = this.volume.effect;
            }).catch((e) => {
                console.error(e);
            }).finally(() => {
                _sound = null;
            });
        } else {
            let _sound = this.sound_object?.[_id];
            new Promise((resolve, reject) => {
                if (this.isPlaying(_id) && _sound.bgm !== true) {
                    this.stop(_id);
                }
                resolve();
            }).then(() => {
                _sound.audio.play();
                _sound.muted = this.muted.bgm;
                _sound.volume = this.volume.bgm;
                _sound.loop = _loop;
            }).catch((e) => {
                console.error(e);
            });
        }
    }

    stop (_id)
    {
        try {
            let _sound = this.sound_object?.[_id];
            _sound.audio.pause();
            _sound.audio.currentTime = 0;
            _sound.loop = false;
        } catch (_e) {
            console.error(_e);
        }
    }

    isPlaying (_id)
    {
        return !this.sound_object?.[_id].audio.paused;
    }

    update ()
    {
        let bgm_volume = ns_cs.d.lord['setting']['volume_bgm'];
        let effect_volume = ns_cs.d.lord['setting']['volume_effect'];

        ns_sound.volume.bgm = ns_util.math(bgm_volume).mul(0.01).number;
        ns_sound.volume.effect = ns_util.math(effect_volume).mul(0.01).number;

        // BGM 볼륨 적용
        for (let [id, o] of Object.entries(this.sound_object)) {
            if (o.bgm === true) {
                o.audio.volume = ns_sound.volume.bgm;
                o.audio.muted = ns_cs.d.lord['setting']['sound_bgm'] !== 'Y';
                if (o.audio.muted === true) {
                    ns_sound.stop(id);
                }
            } else {
                o.audio.volume = ns_sound.volume.effect;
                o.audio.muted = ns_cs.d.lord['setting']['sound_effect'] !== 'Y';
            }
        }

        // BGM Mute 확인
        if (ns_cs.d.lord['setting']['sound_bgm'] === 'Y') {
            if (ns_engine.game_data.curr_view === 'world') {
                ns_sound.play('bgm_world', true);
                ns_sound.stop('bgm_castle');
            } else {
                ns_sound.play('bgm_castle', true);
                ns_sound.stop('bgm_world');
            }
        }
    }
}


let ns_sound = new nsSound();