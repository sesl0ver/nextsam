<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $path = __DIR__ . '/../../public/m_/cache/';
    $master_data = scandir($path);
    $minifier = null;
    $template_cache = [];
    foreach ($master_data as $file) {
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
