<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['target_php_version'] = '7.3';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'], [
		'mediawiki',
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'], [
		'mediawiki',
	]
);

$cfg['suppress_issue_types'] = array_merge(
	$cfg['suppress_issue_types'], []
);

$cfg['scalar_implicit_cast'] = true;

return $cfg;
