<?php

namespace PortableInfobox\Tests\Services\Parser\Nodes;

use MediaWikiIntegrationTestCase;
use PortableInfobox\Services\Parser\Nodes\NodeFactory;

/**
 * @group PortableInfobox
 * @covers \PortableInfobox\Services\Parser\Nodes\NodeHeader
 * @coversDefaultClass \PortableInfobox\Services\Parser\Nodes\NodeHeader
 */
class NodeHeaderTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers ::getData
	 * @covers \PortableInfobox\Services\Parser\Nodes\Node::getInnerValue
	 * @dataProvider dataProvider
	 *
	 * @param $markup
	 * @param $expected
	 */
	public function testData( $markup, $expected ) {
		$node = NodeFactory::newFromXML( $markup );

		$this->assertEquals( $expected, $node->getData() );
	}

	public function dataProvider() {
		return [
			[
				'<header></header>',
				[ 'value' => '', 'item-name' => null ]
			],
			[
				'<header>kjdflkja dafkjlsdkfj</header>',
				[ 'value' => 'kjdflkja dafkjlsdkfj', 'item-name' => null ]
			],
			[
				'<header>kjdflkja<ref>dafkjlsdkfj</ref></header>',
				[ 'value' => 'kjdflkja<ref>dafkjlsdkfj</ref>', 'item-name' => null ]
			],
			[
				'<header name="headertest">kjdflkja dafkjlsdkfj</header>',
				[ 'value' => 'kjdflkja dafkjlsdkfj', 'item-name' => 'headertest' ]
			]
		];
	}

}
