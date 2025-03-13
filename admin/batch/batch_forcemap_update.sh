#!/bin/sh

if [ -o $1 ]
then
        echo "specified version"
        exit
fi

#cd /qbe/web/tool/batch

/qbe/app/bin/php batch_forcemap_1_js.php $1
/qbe/app/bin/php batch_forcemap_2_map.php $1

./batch_forcemap_3_area_1.sh $1 &
./batch_forcemap_3_area_2.sh $1 &

wait

#
# rsync
#

#/qbe/app/bin/php batch_forcemap_4_version_change.php $1
