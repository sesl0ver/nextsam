<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $path = __DIR__ . '/../../public/js_dialog/';
    $dialogs = scandir($path);
    $minifier = new MatthiasMullie\Minify\JS($path . 'build_Template.js'); // 템플릿 우선
    $template_cache = [];
    foreach ($dialogs as $file) {
        if (str_ends_with($file, '.js')) {
            if ($file !== 'build_Template.js') {
                $minifier->add($path . $file);
            }
        }
    }
    echo $minifier->minify();
} catch (Exception $e) {
    echo $e->getMessage();
}
