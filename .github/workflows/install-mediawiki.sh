#!/bin/bash
set -ex

cd mediawiki

# actions/cache doesn't seem to honor excluding it, so just get rid of it
rm LocalSettings.php || echo 'no LocalSettings.php file to remove'

# MySQL might still be initializing so give it some time
php -- <<'EOPHP'
<?php
$user = getenv( 'MYSQL_USER' );
$pass = getenv( 'MYSQL_PASSWORD' );
$db = getenv( 'MYSQL_DATABASE' );
$stderr = fopen('php://stderr', 'w');
$maxTries = 10;
do {
	$mysql = new mysqli( '127.0.0.1', $user, $pass, $db, 3306 );
	if ($mysql->connect_error) {
		fwrite($stderr, "\n" . 'MySQL Connection Error: (' . $mysql->connect_errno . ') ' . $mysql->connect_error . "\n");
		--$maxTries;
		if ($maxTries <= 0) {
			exit(1);
		}
		sleep(3);
	}
} while ($mysql->connect_error);
$mysql->close();
EOPHP

php maintenance/install.php \
	--dbtype mysql \
	--dbserver 127.0.0.1 \
    --dbuser "$MYSQL_USER" \
    --dbpass "$MYSQL_PASSWORD" \
    --dbname "$MYSQL_DATABASE" \
    --pass AdminPassword \
    WikiName \
    AdminUser
{
	echo 'error_reporting( E_ALL | E_STRICT );'
	echo 'ini_set("display_errors", 1);'
	echo '$wgShowExceptionDetails = true;'
	echo '$wgShowDBErrorBacktrace = true;'
	echo '$wgDevelopmentWarnings = true;'
	echo 'wfLoadExtension( "PortableInfobox" );'
} >> LocalSettings.php
