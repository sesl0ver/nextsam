#!/bin/sh

# 폴더 유무 검사. 없으면 생성
if ! [ -d "../../master_data/cache" ]
then
  echo "Directory does not exists."
  mkdir ../../master_data/cache
fi

cd ./server
sh gen.sh
cd ..

# 폴더 유무 검사. 없으면 생성
if ! [ -d "../../public/m_/cache" ]
then
  echo "Directory does not exists."
  mkdir ../../public/m_
  mkdir ../../public/m_/cache
fi

cd ./client
sh js_gen.sh
cd ..