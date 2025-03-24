<?php

namespace PortableInfobox\Tests\Services\Helpers;

use MediaWikiIntegrationTestCase;
use PortableInfobox\Services\Helpers\PortableInfoboxTemplateEngine;

/**
 * @group PortableInfobox
 * @covers \PortableInfobox\Services\Helpers\PortableInfoboxTemplateEngine
 * @coversDefaultClass \PortableInfobox\Services\Helpers\PortableInfoboxTemplateEngine
 */
class PortableInfoboxTemplateEngineTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers ::isSupportedType
	 * @dataProvider isTypeSupportedInTemplatesDataProvider
	 */
	public function testIsTypeSupportedInTemplates( $type, $result, $description ) {
		$this->assertEquals(
			$result,
			PortableInfoboxTemplateEngine::isSupportedType( $type ),
			$description
		);
	}

	public function isTypeSupportedInTemplatesDataProvider() {
		return [
			[
				'type' => 'title',
				'result' => true,
				'description' => 'valid data type'
			],
			[
				'type' => 'invalidTestType',
				'result' => false,
				'description' => 'invalid data type'
			]
		];
	}

}
