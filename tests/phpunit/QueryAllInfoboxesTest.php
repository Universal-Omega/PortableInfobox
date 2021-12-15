<?php

use ApiTestCase;

/**
 * @group PortableInfobox
 * @covers ApiQueryAllInfoboxes
 */
class QueryAllInfoboxesTest extends ApiTestCase {

	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$this->doApiRequest( [
			'action' => 'query',
			'list' => 'allinfoboxes',
		], null, null, self::getTestUser()->getUser() );
		$this->addToAssertionCount( 1 );
	}

}
