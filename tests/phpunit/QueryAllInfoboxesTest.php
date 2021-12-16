<?php

/**
 * @group PortableInfobox
 * @group Database
 * @group medium
 * @coversDefaultClass ApiQueryAllInfoboxes
 */
class QueryAllInfoboxesTest extends ApiTestCase {

	/**
	 * @covers ::__construct
	 * @covers ::execute
	 * @covers ::createLabel
	 * @covers AllInfoboxesQueryPage::doQuery
	 */
	public function testQueryAllInfoboxes() {
		$this->doApiRequest( [
			'action' => 'query',
			'list' => 'allinfoboxes',
		], null, null, self::getTestUser()->getUser() );
		$this->addToAssertionCount( 1 );
	}

}
