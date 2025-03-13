#!/bin/sh

if [ -o $1 ]
then
	echo "specified version"
	exit
fi

#cd /qbe/web/tool/batch

for i in $(seq 365 729)
do

	/qbe/app/bin/php batch_forcemap_3_area.php $1 $i

done

