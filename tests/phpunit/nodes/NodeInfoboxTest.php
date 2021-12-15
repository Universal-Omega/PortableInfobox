<?php

/**
 * @group PortableInfobox
 * @covers PortableInfobox\Parser\Nodes\NodeInfobox
 */
class NodeInfoboxTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers       PortableInfobox\Parser\Nodes\NodeInfobox::getParams
	 * @dataProvider paramsProvider
	 *
	 * @param $markup
	 * @param $expected
	 */
	public function testParams( $markup, $expected ) {
		$node = PortableInfobox\Parser\Nodes\NodeFactory::newFromXML( $markup, [] );

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
