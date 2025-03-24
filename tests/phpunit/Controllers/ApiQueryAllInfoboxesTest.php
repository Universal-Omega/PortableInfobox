<?php

namespace PortableInfobox\Tests\Controllers;

use MediaWiki\Tests\Api\ApiTestCase;

/**
 * @group PortableInfobox
 * @group Database
 * @group medium
 * @coversDefaultClass \PortableInfobox\Controllers\ApiQueryAllInfoboxes
 */
class ApiQueryAllInfoboxesTest extends ApiTestCase {

	/**
	 * @covers ::__construct
	 * @covers ::execute
	 * @covers ::createLabel
	 * @covers \PortableInfobox\Specials\AllInfoboxesQueryPage::doQuery
	 */
	public function testQueryAllInfoboxes() {
		$this->doApiRequest( [
			'action' => 'query',
			'list' => 'allinfoboxes',
		], null, null, self::getTestUser()->getUser() );
		$this->addToAssertionCount( 1 );
	}
}
