<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// TODO 차후 minify 갱신시 ns_version 까지 자동으로 업데이트 되도록 코드 개선이 필요.

try {
    $path = __DIR__ . '/../../public/js/';
    $files = ['ns_math.js',
            'ns_util.js',
            'ns_sound.js',
            'ns_auth.js',
            'ns_engine.js',
            'ns_scroll.js',
            'ns_global.js',
            'ns_button.js',
            'ns_xhr.js',
            'ns_i18n.js',
            'ns_check_condition.js',
            'ns_cs.js',
            'ns_hero.js',
            'ns_card.js',
            'ns_hero_select.js',
            'ns_timer.js',
            'ns_dialog.js',
            'ns_castle.js',
            'ns_world.js',
            'ns_report.js',
            'ns_chat.js',];
    $minifier = null;
    $template_cache = [];
    foreach ($files as $file) {
        if (str_ends_with($file, '.js')) {
            if (! $minifier) {
                $minifier = new MatthiasMullie\Minify\JS($path . $file);
            } else {
                $minifier->add($path . $file);
            }
        }
    }
    echo $minifier->minify();
} catch (Exception $e) {
    echo $e->getMessage();
}
