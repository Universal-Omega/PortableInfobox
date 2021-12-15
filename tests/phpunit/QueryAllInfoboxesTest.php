<?php

/**
 * @group medium
 * @coversDefaultClass ApiQueryAllInfoboxes
 */
class QueryAllInfoboxesTest extends ApiTestCase {

	/**
	 * @covers ::__construct
	 * @covers ::execute
	 */
	public function testQueryAllInfoboxes() {
		$this->doApiRequest( [
			'action' => 'query',
			'list' => 'allinfoboxes',
		], null, null, self::getTestUser()->getUser() );
		$this->addToAssertionCount( 1 );
	}

}
