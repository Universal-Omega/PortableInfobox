<?php

use PortableInfobox\Parser\Nodes\NodeData;
use PortableInfobox\Parser\Nodes\NodeFactory;
use PortableInfobox\Parser\Nodes\NodeImage;
use PortableInfobox\Parser\Nodes\NodeInfobox;
use PortableInfobox\Parser\Nodes\NodeMedia;
use PortableInfobox\Parser\Nodes\NodeUnimplemented;
use PortableInfobox\Parser\XmlMarkupParseErrorException;
use PortableInfobox\Parser\XmlParser;

/**
 * @group PortableInfobox
 * @covers \PortableInfobox\Parser\Nodes\NodeFactory
 */
class NodeFactoryTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider newFromXMLProvider
	 * @param $markup
	 * @param $expected
	 * @throws XmlMarkupParseErrorException
	 */
	public function testNewFromXML( $markup, $expected ) {
		$node = NodeFactory::newFromXML( $markup, [] );
		$this->assertEquals( $expected, get_class( $node ) );
	}

	/**
	 * @dataProvider newFromXMLProvider
	 * @param $markup
	 * @param $expected
	 * @throws XmlMarkupParseErrorException
	 */
	public function testNewFromSimpleXml( $markup, $expected ) {
		$xmlObj = XmlParser::parseXmlString( $markup );
		$node = NodeFactory::newFromSimpleXml( $xmlObj, [] );
		$this->assertEquals( $expected, get_class( $node ) );
	}

	public function newFromXMLProvider() {
		return [
			[
				'<infobox />',
				NodeInfobox::class
			],
			[
				'<data />',
				NodeData::class
			],
			[
				'<MEDIA />',
				NodeMedia::class
			],
			[
				'<image><default></default><othertag></othertag></image>',
				NodeImage::class
			],
			[
				'<idonotexist />',
				NodeUnimplemented::class
			]
		];
	}
}
