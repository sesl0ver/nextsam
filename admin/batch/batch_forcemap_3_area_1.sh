#!/bin/sh

if [ -o $1 ]
then
	echo "specified version"
	exit
fi

#cd /qbe/web/tool/batch

for i in $(seq 1 364)
do

	/qbe/app/bin/php batch_forcemap_3_area.php $1 $i

done

