<?php

use PortableInfobox\Parser\Nodes\NodeFactory;

/**
 * @group PortableInfobox
 * @covers PortableInfobox\Parser\Nodes\NodeFactory
 */
class NodeFactoryTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider newFromXMLProvider
	 * @param $markup
	 * @param $expected
	 * @throws PortableInfobox\Parser\XmlMarkupParseErrorException
	 */
	public function testNewFromXML( $markup, $expected ) {
		$node = NodeFactory::newFromXML( $markup, [] );
		$this->assertEquals( $expected, get_class( $node ) );
	}

	/**
	 * @dataProvider newFromXMLProvider
	 * @param $markup
	 * @param $expected
	 * @throws PortableInfobox\Parser\XmlMarkupParseErrorException
	 */
	public function testNewFromSimpleXml( $markup, $expected ) {
		$xmlObj = PortableInfobox\Parser\XmlParser::parseXmlString( $markup );
		$node = NodeFactory::newFromSimpleXml( $xmlObj, [] );
		$this->assertEquals( $expected, get_class( $node ) );
	}

	public function newFromXMLProvider() {
		return [
			[
				'<infobox />',
				PortableInfobox\Parser\Nodes\NodeInfobox::class
			],
			[
				'<data />',
				PortableInfobox\Parser\Nodes\NodeData::class
			],
			[
				'<MEDIA />',
				PortableInfobox\Parser\Nodes\NodeMedia::class
			],
			[
				'<image><default></default><othertag></othertag></image>',
				PortableInfobox\Parser\Nodes\NodeImage::class
			],
			[
				'<idonotexist />',
				PortableInfobox\Parser\Nodes\NodeUnimplemented::class
			]
		];
	}
}
