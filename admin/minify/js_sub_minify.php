<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $path = __DIR__ . '/../../public/js/';
    $files = ['ns_button_common.js',
            'ns_button_etc.js',
            'ns_cs_instance.js',
            'ns_tile.js',
            'ns_toast.js',
            'ns_quest.js',
            'ns_text.js',
            'ns_select_box.js',];
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
