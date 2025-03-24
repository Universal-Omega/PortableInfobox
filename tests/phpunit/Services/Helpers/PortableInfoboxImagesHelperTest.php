<?php

namespace PortableInfobox\Tests\Services\Helpers;

use MediaWikiIntegrationTestCase;
use PortableInfobox\Services\Helpers\PortableInfoboxImagesHelper;

/**
 * @group PortableInfobox
 * @covers \PortableInfobox\Services\Helpers\PortableInfoboxImagesHelper
 */
class PortableInfoboxImagesHelperTest extends MediaWikiIntegrationTestCase {

	private $helper;

	protected function setUp(): void {
		parent::setUp();
		$this->helper = new PortableInfoboxImagesHelper();
	}

	protected function tearDown(): void {
		unset( $this->helper );
		parent::tearDown();
	}

	/**
	 * @param $width
	 * @param $max
	 * @param $imageWidth
	 * @param $imageHeight
	 * @param $expected
	 * @dataProvider thumbnailSizesDataProvider
	 */
	public function testGetThumbnailSizes( $width, $max, $imageWidth, $imageHeight, $expected ) {
		$helper = new PortableInfoboxImagesHelper();
		$result = $helper->getThumbnailSizes( $width, $max, $imageWidth, $imageHeight );

		$this->assertEquals( $expected, $result );
	}

	public function thumbnailSizesDataProvider() {
		return [
			[
				'preferredWidth' => 270,
				'maxHeight' => 500,
				'originalWidth' => 270,
				'originalHeight' => 250,
				'expected' => [ 'width' => 270, 'height' => 250 ]
			],
			[
				'preferredWidth' => 300,
				'maxHeight' => 500,
				'originalWidth' => 350,
				'originalHeight' => 250,
				'expected' => [ 'width' => 300, 'height' => 214 ]
			],
			[
				'preferredWidth' => 300,
				'maxHeight' => 500,
				'originalWidth' => 300,
				'originalHeight' => 550,
				'expected' => [ 'width' => 273, 'height' => 500 ]
			],
			[
				'preferredWidth' => 200,
				'maxHeight' => 500,
				'originalWidth' => 300,
				'originalHeight' => 400,
				'expected' => [ 'width' => 200, 'height' => 267 ]
			],
			[
				'preferredWidth' => 270,
				'maxHeight' => 500,
				'originalWidth' => 100,
				'originalHeight' => 300,
				'expected' => [ 'width' => 100, 'height' => 300 ]
			],
			[
				'preferredWidth' => 270,
				'maxHeight' => 500,
				'originalWidth' => 800,
				'originalHeight' => 600,
				'expected' => [ 'width' => 270, 'height' => 203 ]
			],
		];
	}

	/**
	 * @param $customWidth
	 * @param $preferredWidth
	 * @param $resultDimensions
	 * @param $thumbnailDimensions
	 * @param $thumbnail2xDimensions
	 * @param $originalDimension
	 * @dataProvider customWidthProvider
	 */
	public function testCustomWidthLogic(
		$customWidth, $preferredWidth, $resultDimensions, $thumbnailDimensions, $thumbnail2xDimensions,
		$originalDimension
	) {
		$expected = [
			'thumbnail' => null,
			'thumbnail2x' => null,
			'width' => $resultDimensions['width'],
			'height' => $resultDimensions['height']
		];
		$thumb = $this->getMockBuilder( 'ThumbnailImage' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'isError', 'getUrl' ] )
			->getMock();
		$file = $this->getMockBuilder( 'File' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'exists', 'transform', 'getWidth', 'getHeight', 'getMediaType' ] )
			->getMock();
		$file->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );
		$file->expects( $this->once() )
			->method( 'getWidth' )
			->willReturn( $originalDimension['width'] );
		$file->expects( $this->once() )
			->method( 'getHeight' )
			->willReturn( $originalDimension['height'] );
		$file->expects( $this->once() )
			->method( 'getMediaType' )
			->willReturn( MEDIATYPE_BITMAP );

		$file->expects( $this->any() )
			->method( 'transform' )
			->with( $this->logicalOr(
				$this->equalTo( $thumbnailDimensions ),
				$this->equalTo( $thumbnail2xDimensions )
			) )
			->willReturn( $thumb );

		global $wgPortableInfoboxCustomImageWidth;
		$wgPortableInfoboxCustomImageWidth = $customWidth;

		$result = $this->helper->extendImageData( $file, $preferredWidth );

		$this->assertEquals( $expected, $result );
	}

	public function customWidthProvider() {
		return [
			[
				'custom' => false,
				'preferred' => 300,
				'result' => [ 'width' => 300, 'height' => 200 ],
				'thumbnail' => [ 'width' => 300, 'height' => 200 ],
				'thumbnail2x' => [ 'width' => 600, 'height' => 400 ],
				'original' => [ 'width' => 300, 'height' => 200 ]
			],
			[
				'custom' => 400,
				'preferred' => 300,
				'result' => [ 'width' => 300, 'height' => 200 ],
				'thumbnail' => [ 'width' => 300, 'height' => 200 ],
				'thumbnail2x' => [ 'width' => 600, 'height' => 400 ],
				'original' => [ 'width' => 300, 'height' => 200 ]
			],
			[
				'custom' => 400,
				'preferred' => 300,
				'result' => [ 'width' => 300, 'height' => 180 ],
				'thumbnail' => [ 'width' => 400, 'height' => 240 ],
				'thumbnail2x' => [ 'width' => 800, 'height' => 480 ],
				'original' => [ 'width' => 500, 'height' => 300 ]
			],
			[
				'custom' => 600,
				'preferred' => 300,
				'result' => [ 'width' => 300, 'height' => 500 ],
				'thumbnail' => [ 'width' => 300, 'height' => 500 ],
				'thumbnail2x' => [ 'width' => 600, 'height' => 1000 ],
				'original' => [ 'width' => 300, 'height' => 500 ]
			],
			[
				'custom' => 600,
				'preferred' => 300,
				'result' => [ 'width' => 188, 'height' => 500 ],
				'thumbnail' => [ 'width' => 188, 'height' => 500 ],
				'thumbnail2x' => [ 'width' => 376, 'height' => 1000 ],
				'original' => [ 'width' => 300, 'height' => 800 ]
			],
			[
				'custom' => 600,
				'preferred' => 300,
				'result' => [ 'width' => 300, 'height' => 375 ],
				'thumbnail' => [ 'width' => 600, 'height' => 750 ],
				'thumbnail2x' => [ 'width' => 1200, 'height' => 1500 ],
				'original' => [ 'width' => 1200, 'height' => 1500 ]
			],
		];
	}
}
