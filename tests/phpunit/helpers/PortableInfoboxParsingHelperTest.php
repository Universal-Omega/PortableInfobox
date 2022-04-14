<?php

use PortableInfobox\Helpers\PortableInfoboxParsingHelper;

/**
 * @group PortableInfobox
 * @group Database
 * @covers \PortableInfobox\Helpers\PortableInfoboxParsingHelper
 */
class PortableInfoboxParsingHelperTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider parsingIncludeonlyInfoboxesDataProvider
	 */
	public function testParsingIncludeonlyInfoboxes( $markup, $expected ) {
		$helper = $this->getMockBuilder( PortableInfoboxParsingHelper::class )
			->setMethods( [ 'fetchArticleContent' ] )
			->getMock();
		$helper->expects( $this->once() )
			->method( 'fetchArticleContent' )
			->will( $this->returnValue( $markup ) );

		$result = $helper->parseIncludeonlyInfoboxes( $this->getExistingTestPage( 'Test' )->getTitle() );

		$this->assertEquals( $expected, $result );
	}

	public function parsingIncludeonlyInfoboxesDataProvider() {
		return [
			[ 'test', false ],
			[
				'<includeonly><infobox><data source="test"><label>1</label></data></infobox></includeonly>',
				[
					[
						'parser_tag_version' => PortableInfoboxParserTagController::PARSER_TAG_VERSION,
						'data' => [],
						'metadata' => [
							[
								'type' => 'data',
								'sources' => [
									'test' => [
										'label' => '1',
										'primary' => true
									]
								]
							]
						]
					]
				]
			],
			[ '<noinclude><infobox></infobox></noinclude>', false ],
			[ '<onlyinclude></onlyinclude><infobox></infobox>', false ],
			[
				'<includeonly></includeonly><infobox></infobox>',
				[
					[
						'parser_tag_version' => PortableInfoboxParserTagController::PARSER_TAG_VERSION,
						'data' => [],
						'metadata' => []
					]
				]
			],
			[ '<nowiki><includeonly><infobox></infobox></includeonly></nowiki>', false ],
			[ '<includeonly><nowiki><infobox></infobox></nowiki></includeonly>', false ],
		];
	}
}
