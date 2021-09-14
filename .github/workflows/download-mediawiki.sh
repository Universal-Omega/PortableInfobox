#! /bin/bash
set -ex

MW_BRANCH=$1

wget https://github.com/wikimedia/mediawiki/archive/"$MW_BRANCH".tar.gz -nv

tar -zxf "$MW_BRANCH".tar.gz
mv mediawiki-"$MW_BRANCH" mediawiki

cd mediawiki/skins

wget https://github.com/wikimedia/Vector/archive/"$MW_BRANCH".tar.gz -nv
tar -zxf "$MW_BRANCH".tar.gz
mv Vector-"$MW_BRANCH" Vector

cd ..

composer update --prefer-dist --no-progress
