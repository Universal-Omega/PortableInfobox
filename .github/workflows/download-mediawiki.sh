#! /bin/bash
set -ex

MW_BRANCH=$1

git clone https://github.com/wikimedia/mediawiki.git --depth=1 --branch="$MW_BRANCH"

cd mediawiki

git clone https://github.com/wikimedia/Vector.git skins/Vector --depth=1 --branch="$MW_BRANCH"

composer update --prefer-dist --no-progress
