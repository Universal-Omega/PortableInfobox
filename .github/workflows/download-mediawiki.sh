#! /bin/bash
set -ex

MW_BRANCH=$1

wget https://github.com/wikimedia/mediawiki/archive/"$MW_BRANCH".tar.gz -nv

tar -zxf "$MW_BRANCH".tar.gz
mv mediawiki-"$MW_BRANCH" mediawiki

cd mediawiki
composer update --prefer-dist --no-progress
