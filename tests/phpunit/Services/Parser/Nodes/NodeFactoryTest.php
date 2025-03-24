<?php

namespace PortableInfobox\Tests\Services\Parser\Nodes;

use MediaWikiIntegrationTestCase;
use PortableInfobox\Services\Parser\Nodes\NodeData;
use PortableInfobox\Services\Parser\Nodes\NodeFactory;
use PortableInfobox\Services\Parser\Nodes\NodeImage;
use PortableInfobox\Services\Parser\Nodes\NodeInfobox;
use PortableInfobox\Services\Parser\Nodes\NodeMedia;
use PortableInfobox\Services\Parser\Nodes\NodeUnimplemented;
use PortableInfobox\Services\Parser\XmlMarkupParseErrorException;
use PortableInfobox\Services\Parser\XmlParser;

/**
 * @group PortableInfobox
 * @covers \PortableInfobox\Services\Parser\Nodes\NodeFactory
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
