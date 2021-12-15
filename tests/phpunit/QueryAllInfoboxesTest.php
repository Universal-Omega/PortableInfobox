<?php

/**
 * @group medium
 * @covers ApiQueryAllInfoboxes
 */
class QueryAllInfoboxesTest extends ApiTestCase {

	/**
	 * @covers ApiQueryAllInfoboxes::__construct
	 * @covers ApiQueryAllInfoboxes::execute
	 */
	public function testQueryAllInfoboxes() {
		$this->doApiRequest( [
			'action' => 'query',
			'list' => 'allinfoboxes',
		], null, null, self::getTestUser()->getUser() );
		$this->addToAssertionCount( 1 );
	}

}
