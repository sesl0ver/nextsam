#!/bin/sh

dir=$(pwd)
ver=$(cat $dir/../../version)
i1=$(echo $ver | cut -d '.' -f1)
i2=$(echo $ver | cut -d '.' -f2)
i3=$(echo $ver | cut -d '.' -f3)

if [ $# -eq 0 ]; then
    echo "No version update."
elif [ "p" = "$1" ]; then
    i3=$(($i3+1))
elif [ "m" = "$1" ]; then
    i2=$(($i2+1))
    i3=0
elif [ "u" = "$1" ]; then
    i1=$(($i1+1))
    i2=0
    i3=0
else
    echo "No version update."
fi

echo $i1.$i2.$i3 > $(pwd)/../version
echo $(cat $dir/../version)