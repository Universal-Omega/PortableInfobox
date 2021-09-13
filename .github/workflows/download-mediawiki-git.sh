#! /bin/bash
set -ex

MW_BRANCH=$1

git clone https://github.com/wikimedia/mediawiki.git --depth=2 --branch="$MW_BRANCH"

cd mediawiki
composer update --prefer-dist --no-progress
