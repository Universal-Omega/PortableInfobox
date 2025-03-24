<?php

namespace PortableInfobox\Tests\Services\Parser\Nodes;

use MediaWikiIntegrationTestCase;
use PortableInfobox\Services\Parser\Nodes\NodeUnimplemented;
use PortableInfobox\Services\Parser\Nodes\UnimplementedNodeException;
use PortableInfobox\Services\Parser\XmlParser;

/**
 * @group PortableInfobox
 * @covers \PortableInfobox\Services\Parser\Nodes\NodeUnimplemented
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
