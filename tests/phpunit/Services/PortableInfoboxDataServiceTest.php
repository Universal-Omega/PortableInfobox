<?php

namespace PortableInfobox\Tests\Services;

use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use PortableInfobox\Controllers\PortableInfoboxParserTagController;
use PortableInfobox\Services\Parser\Nodes\NodeFactory;
use PortableInfobox\Services\PortableInfoboxDataService;

/**
 * @group PortableInfobox
 * @group Database
 * @covers \PortableInfobox\Services\PortableInfoboxDataService
 */
class PortableInfoboxDataServiceTest extends MediaWikiIntegrationTestCase {

	/**
	 * @param $id
	 * @param int $ns
	 *
	 * @return Title
	 */
	protected function prepareTitle( $id = 0, $ns = NS_MAIN ) {
		$title = Title::newFromText( 'Test', $ns );
		$title = $this->getExistingTestPage( $title )->getTitle();
		$title->mArticleID = $id;

		return $title;
	}

	public function testEmptyData() {
		$result = PortableInfoboxDataService::newFromTitle( $this->prepareTitle() )
			// empty page props
			->setPagePropsProxy( new PagePropsProxyDummy() )
			->getData();

		$this->assertEquals( [], $result );
	}

	public function testLoadFromProps() {
		$data = '[{"parser_tag_version": ' .
			PortableInfoboxParserTagController::PARSER_TAG_VERSION .
			', "data": [], "metadata": []}]';
		$result = PortableInfoboxDataService::newFromTitle( $this->prepareTitle( 1 ) )
			// purge memc so we can rerun tests
			->purge()
			->setPagePropsProxy( new PagePropsProxyDummy( [ '1infoboxes' => $data ] ) )
			->getData();

		$this->assertEquals( json_decode( $data, true ), $result );
	}

	public function testSave() {
		$markup = '<infobox><data source="test"><default>{{{test2}}}</default></data></infobox>';
		$infoboxNode = NodeFactory::newFromXML( $markup, [ 'test' => 1 ] );

		$result = PortableInfoboxDataService::newFromTitle( $this->prepareTitle( 1 ) )
			->purge()
			->setPagePropsProxy( new PagePropsProxyDummy() )
			->save( $infoboxNode )
			->getData();

		$this->assertEquals(
			[
				[
					'parser_tag_version' => PortableInfoboxParserTagController::PARSER_TAG_VERSION,
					'data' => [
						[
							'type' => 'data',
							'data' => [
								'label' => null,
								'value' => 1,
								'layout' => null,
								'span' => 1,
								'source' => 'test',
								'item-name' => null
							]
						]
					],
					'metadata' => [
						[
							'type' => 'data',
							'sources' => [
								'test' => [
									'label' => '',
									'primary' => true
								],
								'test2' => [
									'label' => ''
								]
							]
						]
					]
				]
			],
			$result
		);
	}

	public function testTemplate() {
		$data = [
			[
				'parser_tag_version' => PortableInfoboxParserTagController::PARSER_TAG_VERSION,
				'data' => [],
				'metadata' => []
			]
		];
		$result = PortableInfoboxDataService::newFromTitle( $this->prepareTitle( 1, NS_TEMPLATE ) )
			->purge()
			->setPagePropsProxy( new PagePropsProxyDummy() )
			->setParsingHelper( new ParsingHelperDummy( null, $data ) )
			->reparseArticle();

		$this->assertEquals( $data, $result );
	}

	public function testReparse() {
		$oldData = '[{"parser_tag_version": 0, "data": [], "metadata": []}]';
		$newData = [
			[
				'parser_tag_version' => PortableInfoboxParserTagController::PARSER_TAG_VERSION,
				'data' => [],
				'metadata' => []
			]
		];

		$result = PortableInfoboxDataService::newFromTitle( $this->prepareTitle( 1 ) )
			// purge memc so we can rerun tests
			->purge()
			->setPagePropsProxy( new PagePropsProxyDummy( [ '1infoboxes' => $oldData ] ) )
			->setParsingHelper( new ParsingHelperDummy( $newData ) )
			->getData();

		$this->assertEquals( $newData, $result );
	}

	public function testDelete() {
		$data = '[{"parser_tag_version": ' .
			PortableInfoboxParserTagController::PARSER_TAG_VERSION .
			', "data": [], "metadata": []}]';
		$result = PortableInfoboxDataService::newFromTitle( $this->prepareTitle( 1 ) )
			// purge memc so we can rerun tests
			->purge()
			->setPagePropsProxy( new PagePropsProxyDummy( [ '1infoboxes' => $data ] ) )
			->delete()
			->getData();

		$this->assertEquals( [], $result );
	}

	/* public function testPurge() {
		$data = '[{"parser_tag_version": ' .
			PortableInfoboxParserTagController::PARSER_TAG_VERSION .
			', "data": [], "metadata": []}]';
		$service = PortableInfoboxDataService::newFromTitle( $this->prepareTitle( 1 ) )
			// purge memc so we can rerun tests
			->purge()
			->setPagePropsProxy( new PagePropsProxyDummy( [ '1infoboxes' => $data ] ) );

		// this should load data from props to memc
		$result = $service->getData();

		$service->purge()
			->setPagePropsProxy( new PagePropsProxyDummy() );
		$purged = $service->getData();

		$this->assertEquals( [ json_decode( $data, true ), [] ], [ $result, $purged ] );
	} */

	public function testImageListRemoveDuplicates() {
		$images = PortableInfoboxDataService::newFromTitle( $this->prepareTitle( 1 ) )
			->purge()
			->setPagePropsProxy(
				new PagePropsProxyDummy( [ '1infoboxes' => json_encode( $this->getInfoboxPageProps() ) ] )
			)
			->getImages();

		$this->assertCount( 2, $images );
	}

	public function testImageListFetchImages() {
		$images = PortableInfoboxDataService::newFromTitle( $this->prepareTitle( 1 ) )
			->purge()
			->setPagePropsProxy(
				new PagePropsProxyDummy( [ '1infoboxes' => json_encode( $this->getInfoboxPageProps() ) ] )
			)
			->getImages();

		$this->assertEquals( [ 'Test.jpg', 'Test2.jpg' ], $images );
	}

	protected function getInfoboxPageProps() {
		return [
			[
				'parser_tag_version' => PortableInfoboxParserTagController::PARSER_TAG_VERSION,
				'data' => [
					[
						'type' => 'data',
						'data' => [
							'value' => 'AAAA',
							'label' => 'BBBB'
						]
					],
					[
						'type' => 'image',
						'data' => [
							[
								'key' => 'Test.jpg',
								'alt' => null,
								'caption' => null,
							]
						]
					],
					[
						'type' => 'image',
						'data' => [
							[
								'key' => 'Test2.jpg',
								'alt' => null,
								'caption' => null
							]
						]
					]
				],
				'metadata' => []
			],
			[
				'parser_tag_version' => PortableInfoboxParserTagController::PARSER_TAG_VERSION,
				'data' => [
					[
						'type' => 'image',
						'data' => [
							[
								'key' => 'Test2.jpg',
								'alt' => null,
								'caption' => null
							]
						]
					]
				],
				'metadata' => []
			]
		];
	}

	public function testGetInfoboxes() {
		$result = PortableInfoboxDataService::newFromTitle( $this->prepareTitle( 1 ) )
			->setParsingHelper( new ParsingHelperDummy() )
			->getInfoboxes();

		$this->assertEquals( [ "markup" ], $result );
	}
}

// phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
class ParsingHelperDummy {

	public function __construct( $infoboxesData = null, $includeonlyInfoboxesData = null ) {
		$this->infoboxesData = $infoboxesData;
		$this->includeonlyInfoboxesData = $includeonlyInfoboxesData;
	}

	public function parseIncludeonlyInfoboxes( $title ) {
		return $this->includeonlyInfoboxesData;
	}

	public function reparseArticle( $title ) {
		return $this->infoboxesData;
	}

	public function getMarkup( Title $title ) {
		return [ "markup" ];
	}
}

// phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
class PagePropsProxyDummy {

	public function __construct( $data = [] ) {
		$this->data = $data;
	}

	public function get( $id, $property ) {
		return $this->data[ $id . $property ] ?? '';
	}

	public function set( $id, $data ) {
		foreach ( $data as $property => $value ) {
			$this->data[ $id . $property ] = $value;
		}
	}
}
