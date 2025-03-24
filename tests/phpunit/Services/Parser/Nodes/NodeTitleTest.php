<?php

namespace PortableInfobox\Tests\Services\Parser\Nodes;

use MediaWikiIntegrationTestCase;
use PortableInfobox\Services\Parser\Nodes\NodeFactory;

/**
 * @group PortableInfobox
 * @covers \PortableInfobox\Services\Parser\Nodes\NodeTitle
 * @coversDefaultClass \PortableInfobox\Services\Parser\Nodes\NodeTitle
 */
class NodeTitleTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers ::getData
	 * @dataProvider dataProvider
	 *
	 * @param $markup
	 * @param $params
	 * @param $expected
	 */
	public function testData( $markup, $params, $expected ) {
		$node = NodeFactory::newFromXML( $markup, $params );

		$this->assertEquals( $expected, $node->getData() );
	}

	public function dataProvider() {
		return [
			[
				'<title source="test"/>',
				[ 'test' => 'test' ],
				[ 'value' => 'test', 'source' => 'test', 'item-name' => null ]
			],
			[
				'<title source="test"><default>def</default></title>',
				[],
				[ 'value' => 'def', 'source' => 'test', 'item-name' => null ]
			],
			[
				'<title source="test"><default>def</default></title>',
				[],
				[ 'value' => 'def', 'source' => 'test', 'item-name' => null ]
			],
			[
				'<title source="test"><default>def</default></title>',
				[ 'l' => 1 ],
				[ 'value' => 'def', 'source' => 'test', 'item-name' => null ]
			],
			[
				'<title source="test"><default>def</default></title>',
				[ 'l' => 1 ],
				[ 'value' => 'def', 'source' => 'test', 'item-name' => null ]
			],
			[
				'<title source="test"><default>def</default></title>',
				[ 'test' => 1 ],
				[ 'value' => 1, 'source' => 'test', 'item-name' => null ]
			],
			[
				'<title></title>',
				[],
				[ 'value' => null, 'source' => null, 'item-name' => null ]
			],
			[
				'<title source="test"><format>{{{test}}}%</format><default>def</default></title>',
				[ 'test' => 1 ],
				[ 'value' => '{{{test}}}%', 'source' => 'test', 'item-name' => null ]
			],
			[
				'<title source="test"><format>{{{not_defined_var}}}%</format><default>def</default></title>',
				[ 'test' => 1 ],
				[ 'value' => '{{{not_defined_var}}}%', 'source' => 'test', 'item-name' => null ]
			],
			[
				'<title source="test"><format>{{{test}}}%</format><default>def</default></title>',
				[],
				[ 'value' => 'def', 'source' => 'test', 'item-name' => null ]
			],
			[
				'<title source="test"><format>{{{test}}}%</format></title>',
				[ 'test' => 0 ],
				[ 'value' => '{{{test}}}%', 'source' => 'test', 'item-name' => null ]
			],
			[
				'<title source="test" name="nametest" />',
				[ 'test' => 'abc' ],
				[ 'value' => 'abc', 'source' => 'test', 'item-name' => 'nametest' ]
			],
			[
				'<title name="nametest"><default>def</default></title>',
				[],
				[ 'value' => 'def', 'source' => null, 'item-name' => 'nametest' ]
			]
		];
	}
}
