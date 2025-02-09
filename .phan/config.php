<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'], [
		'../../extensions/PageImages',
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'], [
		'../../extensions/PageImages',
	]
);

$cfg['suppress_issue_types'] = [
	'MediaWikiNoEmptyIfDefined',
	'MediaWikiNoIssetIfDefined',
	'PhanAccessMethodInternal',
	'PhanPluginMixedKeyNoKey',
	'SecurityCheck-LikelyFalsePositive',
];

$cfg['scalar_implicit_cast'] = true;

return $cfg;
