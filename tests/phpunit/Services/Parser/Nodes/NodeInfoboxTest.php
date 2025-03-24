<?php

namespace PortableInfobox\Tests\Services\Parser\Nodes;

use MediaWikiIntegrationTestCase;
use PortableInfobox\Services\Parser\Nodes\NodeFactory;

/**
 * @group PortableInfobox
 * @covers \PortableInfobox\Services\Parser\Nodes\NodeInfobox
 * @coversDefaultClass \PortableInfobox\Services\Parser\Nodes\NodeInfobox
 */
class NodeInfoboxTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers ::getParams
	 * @dataProvider paramsProvider
	 *
	 * @param $markup
	 * @param $expected
	 */
	public function testParams( $markup, $expected ) {
		$node = NodeFactory::newFromXML( $markup, [] );

		$this->assertEquals( $expected, $node->getParams() );
	}

	public function paramsProvider() {
		return [
			[ '<infobox></infobox>', [] ],
			[ '<infobox theme="abs"></infobox>', [ 'theme' => 'abs' ] ],
			[ '<infobox theme="abs" more="sdf"></infobox>', [ 'theme' => 'abs', 'more' => 'sdf' ] ],
		];
	}

}
