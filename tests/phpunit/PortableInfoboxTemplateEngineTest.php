<?php
/**
 * @group PortableInfobox
 * @covers PortableInfobox\Helpers\PortableInfoboxTemplateEngine
 */
class PortableInfoboxTemplateEngineTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers PortableInfobox\Helpers\PortableInfoboxTemplateEngine::isSupportedType
	 * @dataProvider isTypeSupportedInTemplatesDataProvider
	 */
	public function testIsTypeSupportedInTemplates( $type, $result, $description ) {
		$this->assertEquals(
			$result,
			PortableInfobox\Helpers\PortableInfoboxTemplateEngine::isSupportedType( $type ),
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
