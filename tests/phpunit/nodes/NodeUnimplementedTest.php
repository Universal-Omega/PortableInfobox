<?php

use PortableInfobox\Parser\Nodes\NodeUnimplemented;
use PortableInfobox\Parser\Nodes\UnimplementedNodeException;
use PortableInfobox\Parser\XmlParser;

/**
 * @group PortableInfobox
 * @covers \PortableInfobox\Parser\Nodes\NodeUnimplemented
 */
class NodeUnimplementedTest extends MediaWikiIntegrationTestCase {

	public function testNewFromXML() {
		$this->expectException( UnimplementedNodeException::class );

		( new NodeUnimplemented(
			XmlParser::parseXmlString( "<foo/>" ),
			[]
		) )->getData();
	}

}
