<?php
/**
 * @group PortableInfobox
 * @covers PortableInfobox\Parser\XmlParser
 */
class XmlParserTest extends MediaWikiTestCase {

	/** @dataProvider contentTagsDataProvider */
	public function testXHTMLParsing( $tag, $content ) {
		$markup = "<data source=\"asdfd\"><{$tag}>{$content}</{$tag}></data>";
		$result = PortableInfobox\Parser\XmlParser::parseXmlString( $markup );

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
		$result = PortableInfobox\Parser\XmlParser::parseXmlString( $markup );
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
