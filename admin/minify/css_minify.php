<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $path = __DIR__ . '/../../public/style/';
    $styles = scandir($path);
    $styles_dialog = scandir($path . '/dialog/');
    $minifier = null;
    $template_cache = [];
    foreach ($styles as $file) {
        if (str_ends_with($file, '.css')) {
            if (! $minifier) {
                $minifier = new MatthiasMullie\Minify\CSS($path . $file);
            } else {
                $minifier->add($path . $file);
            }
        }
    }
    foreach ($styles_dialog as $file) {
        if (str_ends_with($file, '.css')) {
            $minifier->add($path . '/dialog/' . $file);
        }
    }
    echo $minifier->minify();
} catch (Exception $e) {
    echo $e->getMessage();
}
