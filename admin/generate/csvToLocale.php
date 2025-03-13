<?php
require_once __DIR__ . '/../../config/constant.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Shuchkin\SimpleXLSX;

try {
    $language = I18N_LOCALE_LIST;
    foreach ($language as $lang) {
        $xlsx = SimpleXLSX::parse(__DIR__ . "/data/$lang.xlsx");
        $data = [];
        for ($i = 0; $i < $xlsx->sheetsCount(); $i++) {
            $rows = $xlsx->rows($i);
            $keys = array_shift($rows);
            foreach ($rows as $index => $row) {
                if ($row[0] != '') {
                    $data[$row[0]] = $row[1];
                }
            }
            echo "$lang Sheet: \"{$xlsx->sheetName($i)}\" Done.\n";
        }
        file_put_contents(__DIR__ . "/../../i18n/locales/$lang.json", json_encode($data, JSON_UNESCAPED_UNICODE));
        echo "$lang files have been created.\n";
    }
    echo "All files have been created.\n";
}
catch (Throwable $e) {
    echo SimpleXLSX::parseError();
    printf('%s : %s', 'Generate Error!', $e->getMessage());
}