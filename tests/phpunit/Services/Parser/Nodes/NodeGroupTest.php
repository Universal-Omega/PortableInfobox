<?php

namespace PortableInfobox\Tests\Services\Parser\Nodes;

use MediaWikiIntegrationTestCase;
use PortableInfobox\Services\Parser\Nodes\NodeFactory;

/**
 * @group PortableInfobox
 * @covers \PortableInfobox\Services\Parser\Nodes\NodeGroup
 * @coversDefaultClass \PortableInfobox\Services\Parser\Nodes\NodeGroup
 */
class NodeGroupTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers ::getData
	 * @dataProvider groupNodeCollapseTestProvider
	 *
	 * @param $markup
	 * @param $expected
	 */
	public function testNodeGroupCollapse( $markup, $expected ) {
		$node = NodeFactory::newFromXML( $markup );
		$this->assertEquals( $expected, $node->getData()['collapse'] );
	}

	public function groupNodeCollapseTestProvider() {
		return [
			[ '<group></group>', null ],
			[ '<group collapse="wrong"></group>', null ],
			[ '<group collapse="open"></group>', 'open' ],
			[ '<group collapse="closed"></group>', 'closed' ]
		];
	}

	/**
	 * @covers ::getData
	 * @covers ::getRenderData
	 * @dataProvider groupNodeRowItemsTestProvider
	 *
	 * @param $markup
	 * @param $expected
	 */
	public function testNodeGroupRowItems( $markup, $expected ) {
		$node = NodeFactory::newFromXML( $markup );
		$this->assertEquals( $expected, $node->getData()['row-items'] );
		$this->assertEquals( $expected, $node->getRenderData()['data']['row-items'] );
	}

	public function groupNodeRowItemsTestProvider() {
		return [
			[ '<group></group>', null ],
			[ '<group row-items="not a number"></group>', null ],
			[ '<group row-items="5.5"></group>', null ],
			[ '<group row-items="5"></group>', '5' ],
			[ '<group row-items="50"></group>', '50' ],
		];
	}

	/**
	 * @covers ::getData
	 * @dataProvider groupNodeTestProvider
	 *
	 * @param $markup
	 * @param $params
	 * @param $expected
	 */
	public function testNodeGroup( $markup, $params, $expected ) {
		$node = NodeFactory::newFromXML( $markup, $params );

		$this->assertEquals( $expected, $node->getData() );
	}

	public function groupNodeTestProvider() {
		return [
			[
				'<group>' .
				'<data source="elem1"><label>l1</label><default>def1</default></data>' .
				'<data source="elem2"><label>l2</label><default>def2</default></data>' .
				'<data source="elem3"><label>l2</label></data>' .
				'</group>',
				[ 'elem1' => 1, 'elem2' => 2 ],
				[
					'value' => [
						[
							'type' => 'data',
							'isEmpty' => false,
							'data' => [
								'label' => 'l1',
								'value' => 1,
								'span' => 1,
								'layout' => null,
								'source' => 'elem1',
								'item-name' => null
							],
							'source' => [ 'elem1' ]
						],
						[
							'type' => 'data',
							'isEmpty' => false,
							'data' => [
								'label' => 'l2',
								'value' => 2,
								'span' => 1,
								'layout' => null,
								'source' => 'elem2',
								'item-name' => null
							],
							'source' => [ 'elem2' ]
						],
						[
							'type' => 'data',
							'isEmpty' => true,
							'data' => [
								'label' => 'l2',
								'value' => null,
								'span' => 1,
								'layout' => null,
								'source' => 'elem3',
								'item-name' => null
							],
							'source' => [ 'elem3' ]
						]
					],
					'layout' => 'default',
					'collapse' => null,
					'row-items' => null,
					'item-name' => null
				]
			],
			[
				'<group layout="horizontal">' .
				'<data source="elem1"><label>l1</label><default>def1</default></data>' .
				'<data source="elem2"><label>l2</label><default>def2</default></data>' .
				'<data source="elem3"><label>l2</label></data>' .
				'</group>',
				[ 'elem1' => 1, 'elem2' => 2 ],
				[
					'value' => [
						[
							'type' => 'data',
							'isEmpty' => false,
							'data' => [
								'label' => 'l1',
								'value' => 1,
								'span' => 1,
								'layout' => null,
								'source' => 'elem1',
								'item-name' => null
							],
							'source' => [ 'elem1' ]
						],
						[
							'type' => 'data',
							'isEmpty' => false,
							'data' => [
								'label' => 'l2',
								'value' => 2,
								'span' => 1,
								'layout' => null,
								'source' => 'elem2',
								'item-name' => null
							],
							'source' => [ 'elem2' ]
						],
						[
							'type' => 'data',
							'isEmpty' => true,
							'data' => [
								'label' => 'l2',
								'value' => null,
								'span' => 1,
								'layout' => null,
								'source' => 'elem3',
								'item-name' => null
							],
							'source' => [ 'elem3' ]
						],
					],
					'layout' => 'horizontal',
					'collapse' => null,
					'row-items' => null,
					'item-name' => null
				]
			],
			[
				'<group  layout="loool">' .
				'<data source="elem1"><label>l1</label><default>def1</default></data>' .
				'<data source="elem2"><label>l2</label><default>def2</default></data>' .
				'<data source="elem3"><label>l2</label></data>' .
				'</group>',
				[ 'elem1' => 1, 'elem2' => 2 ],
				[
					'value' => [
						[
							'type' => 'data',
							'isEmpty' => false,
							'data' => [
								'label' => 'l1',
								'value' => 1,
								'span' => 1,
								'layout' => null,
								'source' => 'elem1',
								'item-name' => null
							],
							'source' => [ 'elem1' ]
						],
						[
							'type' => 'data',
							'isEmpty' => false,
							'data' => [
								'label' => 'l2',
								'value' => 2,
								'span' => 1,
								'layout' => null,
								'source' => 'elem2',
								'item-name' => null
							],
							'source' => [ 'elem2' ]
						],
						[
							'type' => 'data',
							'isEmpty' => true,
							'data' => [
								'label' => 'l2',
								'value' => null,
								'span' => 1,
								'layout' => null,
								'source' => 'elem3',
								'item-name' => null
							],
							'source' => [ 'elem3' ]
						]
					],
					'layout' => 'default',
					'collapse' => null,
					'row-items' => null,
					'item-name' => null
				]
			],
			[
				'<group show="incomplete"><header>h</header><data source="1"/><data source="2"/></group>',
				[ '1' => 'one', '2' => 'two' ],
				[
					'value' => [
						[
							'type' => 'header',
							'data' => [
								'value' => 'h',
								'item-name' => null
							],
							'isEmpty' => false,
							'source' => []
						],
						[
							'type' => 'data',
							'data' => [
								'value' => 'one',
								'label' => '',
								'span' => 1,
								'layout' => null,
								'source' => '1',
								'item-name' => null
							],
							'isEmpty' => false,
							'source' => [ '1' ]
						],
						[
							'type' => 'data',
							'data' => [
								'value' => 'two',
								'label' => '',
								'span' => 1,
								'layout' => null,
								'source' => '2',
								'item-name' => null
							],
							'isEmpty' => false,
							'source' => [ '2' ]
						]
					],
					'layout' => 'default',
					'collapse' => null,
					'row-items' => null,
					'item-name' => null
				]
			],
			[
				'<group show="incomplete"><header>h</header><data source="1"/><data source="2"/></group>',
				[ '1' => 'one' ],
				[
					'value' => [
						[
							'type' => 'header',
							'data' => [ 'value' => 'h', 'item-name' => null ],
							'isEmpty' => false,
							'source' => []
						],
						[
							'type' => 'data',
							'data' => [
								'value' => 'one',
								'label' => '',
								'span' => 1,
								'layout' => null,
								'source' => '1',
								'item-name' => null
							],
							'isEmpty' => false,
							'source' => [ '1' ]
						],
						[
							'type' => 'data',
							'data' => [
								'value' => null,
								'label' => '',
								'span' => 1,
								'layout' => null,
								'source' => '2',
								'item-name' => null
							],
							'isEmpty' => true,
							'source' => [ '2' ]
						]
					],
					'layout' => 'default',
					'collapse' => null,
					'row-items' => null,
					'item-name' => null
				]
			],
			[
				'<group show="incomplete"><header>h</header><data source="1"/><data source="2"/></group>',
				[],
				[
					'value' => [
						[
							'type' => 'header',
							'data' => [
								'value' => 'h',
								'item-name' => null
							],
							'isEmpty' => false,
							'source' => []
						],
						[
							'type' => 'data',
							'data' => [
								'value' => null,
								'label' => '',
								'span' => 1,
								'layout' => null,
								'source' => '1',
								'item-name' => null
							],
							'isEmpty' => true,
							'source' => [ '1' ]
						],
						[
							'type' => 'data',
							'data' => [
								'value' => null,
								'label' => '',
								'span' => 1,
								'layout' => null,
								'source' => '2',
								'item-name' => null
							],
							'isEmpty' => true,
							'source' => [ '2' ]
						]
					],
					'layout' => 'default',
					'collapse' => null,
					'row-items' => null,
					'item-name' => null
				]
			],
			[
				'<group name="g"><header name="h">h</header><data source="d" name="d"/></group>',
				[ 'd' => 'data' ],
				[
					'value' => [
						[
							'type' => 'header',
							'data' => [
								'value' => 'h',
								'item-name' => 'h'
							],
							'isEmpty' => false,
							'source' => []
						],
						[
							'type' => 'data',
							'data' => [
								'value' => 'data',
								'label' => '',
								'span' => 1,
								'layout' => null,
								'source' => 'd',
								'item-name' => 'd'
							],
							'isEmpty' => false,
							'source' => [ 'd' ]
						]
					],
					'layout' => 'default',
					'collapse' => null,
					'row-items' => null,
					'item-name' => 'g'
				]
			]
		];
	}
}
