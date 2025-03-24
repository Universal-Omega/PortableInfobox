<?php

namespace PortableInfobox\Tests\Services\Parser;

use MediaWikiIntegrationTestCase;
use PortableInfobox\Services\Parser\XmlParser;

/**
 * @group PortableInfobox
 * @covers \PortableInfobox\Services\Parser\XmlParser
 */
class XmlParserTest extends MediaWikiIntegrationTestCase {

	/** @dataProvider contentTagsDataProvider */
	public function testXHTMLParsing( $tag, $content ) {
		$markup = "<data source=\"asdfd\"><{$tag}>{$content}</{$tag}></data>";
		$result = XmlParser::parseXmlString( $markup );

		$this->assertEquals( $content, (string)$result->{$tag} );
	}

	public function contentTagsDataProvider() {
		return [
			[ 'default', 'sadf <br> sakdjfl' ],
			[ 'format', '<>' ],
			[ 'label', '' ]
		];
	}

	/**
	 * @dataProvider entitiesTestDataProvider
	 */
	public function testHTMLEntities( $markup, $expectedResult ) {
		$result = XmlParser::parseXmlString( $markup );
		$this->assertEquals( $expectedResult, $result[ 0 ] );
	}

	public function entitiesTestDataProvider() {
		return [
			[ '<data></data>', '' ],
			[ '<data>&aksjdf;</data>', '&aksjdf;' ],
			[ '<data>&amp;</data>', '&' ],
			[ '<data>&middot;</data>', '·' ],
			[ '<data>&Uuml;</data>', 'Ü' ],
			[ '<data>&Delta;</data>', 'Δ' ],
			[ '<data>&amp;amp;</data>', '&amp;' ],
			[ '<data>&amp</data>', '&amp' ]
		];
	}
}
