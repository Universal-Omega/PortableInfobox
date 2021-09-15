<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'], [
		'mediawiki',
		'src',
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'], [
		'mediawiki',
		'src',
	]
);

$cfg['suppress_issue_types'] = [
	'PhanPluginMixedKeyNoKey',
	'SecurityCheck-LikelyFalsePositive',
];

$cfg['scalar_implicit_cast'] = true;

return $cfg;
