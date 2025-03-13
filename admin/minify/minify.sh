#!/bin/sh

php js_minify.php > ../../public/minify/main.min.js
php js_sub_minify.php > ../../public/minify/sub.min.js
php dialog_minify.php > ../../public/minify/dialog.min.js
php master_data_minify.php > ../../public/minify/m_.min.js
php ext_minify.php > ../../public/minify/ext.min.js
php css_minify.php > ../../public/minify/style.min.css

