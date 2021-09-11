<?php

use PortableInfobox\Parser\Nodes\NodeUnimplemented;

/**
 * @group PortableInfobox
 * @covers PortableInfobox\Parser\Nodes\NodeUnimplemented
 */
class NodeUnimplementedTest extends MediaWikiTestCase {

	public function testNewFromXML() {
		$this->expectException( PortableInfobox\Parser\Nodes\UnimplementedNodeException::class );

		( new NodeUnimplemented(
			PortableInfobox\Parser\XmlParser::parseXmlString( "<foo/>" ),
			[]
		) )->getData();
	}

}
