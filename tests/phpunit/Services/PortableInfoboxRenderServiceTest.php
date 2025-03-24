<?php

namespace PortableInfobox\Tests\Services;

use DOMDocument;
use MediaWikiIntegrationTestCase;
use PortableInfobox\Services\PortableInfoboxRenderService;

/**
 * @group PortableInfobox
 * @covers \PortableInfobox\Services\PortableInfoboxRenderService
 * @coversDefaultClass \PortableInfobox\Services\PortableInfoboxRenderService
 */
class PortableInfoboxRenderServiceTest extends MediaWikiIntegrationTestCase {

	/**
	 * @param $html
	 * @return string
	 */
	private function normalizeHTML( $html ) {
		if ( empty( $html ) ) {
			return '';
		}

		$DOM = new DOMDocument( '1.0' );
		$DOM->formatOutput = true;
		$DOM->preserveWhiteSpace = false;
		$DOM->loadXML( $html );

		return $DOM->saveXML();
	}

	/**
	 * @covers ::renderInfobox
	 * @covers \PortableInfobox\Services\Helpers\PortableInfoboxTemplateEngine
	 * @param $input
	 * @param $expectedOutput
	 * @param $description
	 * @param $accentColor
	 * @param $accentColorText
	 * @dataProvider renderInfoboxDataProvider
	 */
	public function testRenderInfobox(
		$input, $expectedOutput, $description, $accentColor, $accentColorText
	) {
		$infoboxRenderService = new PortableInfoboxRenderService();

		$actualOutput = $infoboxRenderService->renderInfobox(
			$input, '', '', $accentColor, $accentColorText, '', ''
		);
		$expectedHtml = $this->normalizeHTML( $expectedOutput );
		$actualHtml = $this->normalizeHTML( $actualOutput );

		$this->assertEquals( $expectedHtml, $actualHtml, $description );
	}

	// phpcs:disable Generic.Files.LineLength
	public function renderInfoboxDataProvider() {
		return [
			[
				'input' => [],
				'output' => '',
				'description' => 'Empty data should yield no infobox markup',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title',
							'source' => null,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<h2 class="pi-item pi-item-spacing pi-title">Test Title</h2>
					</aside>',
				'description' => 'Only title',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title',
							'source' => null,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<h2 class="pi-item pi-item-spacing pi-title" style="background-color:#FFF;color:#000;">Test Title</h2>
					</aside>',
				'description' => 'Only title with custom colors',
				'accentColor' => '#FFF',
				'accentColorText' => '#000'
			],
			[
				'input' => [
					[
						'type' => 'media',
						'data' => [
							[
								'alt' => 'image alt',
								'url' => 'http://image.jpg',
								'caption' => 'Lorem ipsum dolor',
								'width' => '400',
								'height' => '200',
								'thumbnail' => 'http://thumbnail.jpg',
								'thumbnail2x' => 'http://thumbnail2x.jpg',
								'isImage' => true,
								'source' => null,
								'item-name' => null
							]
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<figure class="pi-item pi-media pi-image">
							<a href="http://image.jpg" class="image image-thumbnail" title="image alt">
								<img src="http://thumbnail.jpg" srcset="http://thumbnail.jpg 1x, http://thumbnail2x.jpg 2x"
									class="pi-image-thumbnail" alt="image alt" width="400" height="200"/>
							</a>
							<figcaption class="pi-item-spacing pi-caption">Lorem ipsum dolor</figcaption>
						</figure>
					</aside>',
				'description' => 'Only image',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'media',
						'data' => [
							[
								'alt' => 'image alt',
								'url' => 'http://image.jpg',
								'caption' => 'Lorem ipsum dolor',
								'isVideo' => true,
								'source' => null,
								'item-name' => null
							]
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<figure class="pi-item pi-media pi-video">
							<a href="http://image.jpg" class="video" title="image alt">
								<video src="http://image.jpg" class="pi-video-player" controls="true"
									controlsList="nodownload" preload="metadata">image alt</video>
							</a>
							<figcaption class="pi-item-spacing pi-caption">Lorem ipsum dolor</figcaption>
						</figure>
					</aside>',
				'description' => 'Only video',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'navigation',
						'data' => [
							'value' => 'navigation value',
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<nav class="pi-navigation pi-item-spacing pi-secondary-background pi-secondary-font">navigation value</nav>
					</aside>',
				'description' => 'navigation only',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'data',
						'data' => [
							'label' => 'test label',
							'value' => 'test value',
							'source' => null,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<div class="pi-item pi-data pi-item-spacing pi-border-color">
							<h3 class="pi-data-label pi-secondary-font">test label</h3>
							<div class="pi-data-value pi-font">test value</div>
						</div>
					</aside>',
				'description' => 'Only pair',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title',
							'source' => null,
							'item-name' => null
						]
					],
					[
						'type' => 'media',
						'data' => [
							[
								'alt' => 'image alt',
								'url' => 'http://image.jpg',
								'width' => '400',
								'height' => '200',
								'thumbnail' => 'http://thumbnail.jpg',
								'thumbnail2x' => 'http://thumbnail2x.jpg',
								'isImage' => true,
								'source' => null,
								'item-name' => null
							]
						]
					],
					[
						'type' => 'data',
						'data' => [
							'label' => 'test label',
							'value' => 'test value',
							'source' => 'test',
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<h2 class="pi-item pi-item-spacing pi-title">Test Title</h2>
						<figure class="pi-item pi-media pi-image">
							<a href="http://image.jpg" class="image image-thumbnail" title="image alt">
								<img src="http://thumbnail.jpg" srcset="http://thumbnail.jpg 1x, http://thumbnail2x.jpg 2x"
									class="pi-image-thumbnail" alt="image alt" width="400" height="200"/>
							</a>
						</figure>
						<div class="pi-item pi-data pi-item-spacing pi-border-color" data-source="test">
							<h3 class="pi-data-label pi-secondary-font">test label</h3>
							<div class="pi-data-value pi-font">test value</div>
						</div>
					</aside>',
				'description' => 'Simple infobox with title, image and key-value pair',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title',
							'source' => null,
							'item-name' => null
						]
					],
					[
						'type' => 'media',
						'data' => []
					],
					[
						'type' => 'data',
						'data' => [
							'label' => 'test label',
							'value' => 'test value',
							'source' => null,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<h2 class="pi-item pi-item-spacing pi-title">Test Title</h2>
						<div class="pi-item pi-data pi-item-spacing pi-border-color">
							<h3 class="pi-data-label pi-secondary-font">test label</h3>
							<div class="pi-data-value pi-font">test value</div>
						</div>
					</aside>',
				'description' => 'Simple infobox with title, INVALID image and key-value pair',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title',
							'item-name' => null
						]
					],
					[
						'type' => 'data',
						'data' => [
							'label' => 'test label',
							'value' => 'test value',
							'source' => null,
							'item-name' => 'testname'
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<h2 class="pi-item pi-item-spacing pi-title">Test Title</h2>
						<div class="pi-item pi-data pi-item-spacing pi-border-color" data-item-name="testname">
							<h3 class="pi-data-label pi-secondary-font">test label</h3>
							<div class="pi-data-value pi-font">test value</div>
						</div>
					</aside>',
				'description' => 'Simple infobox with title, empty image and key-value pair',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title',
							'source' => null,
							'item-name' => null
						]
					],
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'header',
									'data' => [
										'value' => 'Test Header',
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value',
										'source' => null,
										'item-name' => 'dataname'
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value',
										'source' => 'datasrc',
										'item-name' => null
									]
								]
							],
							'layout' => 'default',
							'collapse' => null,
							'row-items' => null,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<h2 class="pi-item pi-item-spacing pi-title">Test Title</h2>
						<section class="pi-item pi-group pi-border-color">
							<h2 class="pi-item pi-header pi-secondary-font pi-item-spacing pi-secondary-background">Test Header</h2>
							<div class="pi-item pi-data pi-item-spacing pi-border-color" data-item-name="dataname">
								<h3 class="pi-data-label pi-secondary-font">test label</h3>
								<div class="pi-data-value pi-font">test value</div>
							</div>
							<div class="pi-item pi-data pi-item-spacing pi-border-color" data-source="datasrc">
								<h3 class="pi-data-label pi-secondary-font">test label</h3>
								<div class="pi-data-value pi-font">test value</div>
							</div>
						</section>
					</aside>',
				'description' => 'Infobox with title, group with header and two key-value pairs',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title',
							'source' => null,
							'item-name' => null
						]
					],
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'header',
									'data' => [
										'value' => 'Test Header',
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value',
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value',
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => 'default',
							'collapse' => null,
							'row-items' => null,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<h2 class="pi-item pi-item-spacing pi-title" style="background-color:#FFF;color:#000;">Test Title</h2>
						<section class="pi-item pi-group pi-border-color">
							<h2 class="pi-item pi-header pi-secondary-font pi-item-spacing pi-secondary-background" style="background-color:#FFF;color:#000;">Test Header</h2>
							<div class="pi-item pi-data pi-item-spacing pi-border-color">
								<h3 class="pi-data-label pi-secondary-font">test label</h3>
								<div class="pi-data-value pi-font">test value</div>
							</div>
							<div class="pi-item pi-data pi-item-spacing pi-border-color">
								<h3 class="pi-data-label pi-secondary-font">test label</h3>
								<div class="pi-data-value pi-font">test value</div>
							</div>
						</section>
					</aside>',
				'description' => 'Infobox with title, group with header and two key-value pairs, custom' .
					'accent color and accent text color',
				'accentColor' => '#FFF',
				'accentColorText' => '#000'
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'header',
									'data' => [
										'value' => 'Test header',
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value',
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value',
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => 'horizontal',
							'collapse' => null,
							'row-items' => null,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<section class="pi-item pi-group pi-border-color">
							<table class="pi-horizontal-group">
								<caption class="pi-header pi-secondary-font pi-secondary-background pi-item-spacing">Test header</caption>
								<thead>
									<tr>
										<th class="pi-horizontal-group-item pi-data-label pi-secondary-font pi-border-color pi-item-spacing">test label</th>
										<th class="pi-horizontal-group-item pi-data-label pi-secondary-font pi-border-color pi-item-spacing">test label</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td class="pi-horizontal-group-item pi-data-value pi-font pi-border-color pi-item-spacing">test value</td>
										<td class="pi-horizontal-group-item pi-data-value pi-font pi-border-color pi-item-spacing">test value</td>
									</tr>
								</tbody>
							</table>
						</section>
					</aside>',
				'description' => 'Infobox with horizontal group',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => '',
										'value' => 'test value',
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => '',
										'value' => 'test value',
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => 'horizontal',
							'collapse' => null,
							'row-items' => null,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<section class="pi-item pi-group pi-border-color">
							<table class="pi-horizontal-group pi-horizontal-group-no-labels">
								<tbody>
									<tr>
										<td class="pi-horizontal-group-item pi-data-value pi-font pi-border-color pi-item-spacing">test value</td>
										<td class="pi-horizontal-group-item pi-data-value pi-font pi-border-color pi-item-spacing">test value</td>
									</tr>
								</tbody>
							</table>
						</section>
					</aside>',
				'description' => 'Infobox with horizontal group without header and labels',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'navigation',
						'data' => [
							'value' => '<p>Links</p>',
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<nav class="pi-navigation pi-item-spacing pi-secondary-background pi-secondary-font">
							<p>Links</p>
						</nav>
					</aside>',
				'description' => 'Infobox with navigation',
				'accentColor' => '',
				'accentColorText' => ''
			],
			// horizontal group tests
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label 1',
										'value' => 'test value 1',
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label 2',
										'value' => 'test value 2',
										'source' => 'src',
										'item-name' => 'name'
									]
								]
							],
							'layout' => 'horizontal',
							'collapse' => null,
							'row-items' => null,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<section class="pi-item pi-group pi-border-color">
							<table class="pi-horizontal-group">
								<thead>
									<tr>
										<th class="pi-horizontal-group-item pi-data-label pi-secondary-font pi-border-color pi-item-spacing">test label 1</th>
										<th class="pi-horizontal-group-item pi-data-label pi-secondary-font pi-border-color pi-item-spacing" data-source="src" data-item-name="name">test label 2</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td class="pi-horizontal-group-item pi-data-value pi-font pi-border-color pi-item-spacing">test value 1</td>
										<td class="pi-horizontal-group-item pi-data-value pi-font pi-border-color pi-item-spacing" data-source="src" data-item-name="name">test value 2</td>
									</tr>
								</tbody>
							</table>
						</section>
					</aside>',
				'description' => 'Horizontal group data without header',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'header',
									'data' => [
										'value' => 'test header',
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => '',
										'value' => 'test value 1',
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label 2',
										'value' => 'test value 2',
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => 'horizontal',
							'collapse' => null,
							'row-items' => null,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<section class="pi-item pi-group pi-border-color">
							<table class="pi-horizontal-group">
								<caption class="pi-header pi-secondary-font pi-secondary-background pi-item-spacing">test header</caption>
								<thead>
									<tr>
										<th class="pi-horizontal-group-item pi-data-label pi-secondary-font pi-border-color pi-item-spacing"/>
										<th class="pi-horizontal-group-item pi-data-label pi-secondary-font pi-border-color pi-item-spacing">test label 2</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td class="pi-horizontal-group-item pi-data-value pi-font pi-border-color pi-item-spacing">test value 1</td>
										<td class="pi-horizontal-group-item pi-data-value pi-font pi-border-color pi-item-spacing">test value 2</td>
									</tr>
								</tbody>
							</table>
						</section>
					</aside>',
				'description' => 'Horizontal group data with empty label',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => '',
										'value' => 'test value 1',
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => '',
										'value' => 'test value 2',
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => 'horizontal',
							'collapse' => null,
							'row-items' => null,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<section class="pi-item pi-group pi-border-color">
							<table class="pi-horizontal-group pi-horizontal-group-no-labels">
								<tbody>
									<tr>
										<td class="pi-horizontal-group-item pi-data-value pi-font pi-border-color pi-item-spacing">test value 1</td>
										<td class="pi-horizontal-group-item pi-data-value pi-font pi-border-color pi-item-spacing">test value 2</td>
									</tr>
								</tbody>
							</table>
						</section>
					</aside>',
				'description' => 'Horizontal group data with empty label',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 1',
										'value' => 'test value 1',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 2',
										'value' => 'test value 2',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 3',
										'value' => 'test value 3',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 4',
										'value' => 'test value 4',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 5',
										'value' => 'test value 5',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => null,
							'collapse' => null,
							'row-items' => 3,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<section class="pi-item pi-group pi-border-color">
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 33.333333333333%">Test 1</h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 33.333333333333%">Test 2</h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 33.333333333333%">Test 3</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 33.333333333333%">test value 1</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 33.333333333333%">test value 2</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 33.333333333333%">test value 3</div>
								</section>
							</section>
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 50%">Test 4</h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 50%">Test 5</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 50%">test value 4</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 50%">test value 5</div>
								</section>
							</section>
						</section>
					</aside>',
				'description' => 'Flex wrapped group of 5 elements with row size 3',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 1',
										'value' => 'test value 1',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 2',
										'value' => 'test value 2',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => 'test2'
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 3',
										'value' => 'test value 3',
										'span' => 1,
										'layout' => null,
										'source' => 'test3',
										'item-name' => 'test3'
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 4',
										'value' => 'test value 4',
										'span' => 1,
										'layout' => null,
										'source' => 'test4',
										'item-name' => 'test4'
									]
								]
							],
							'layout' => null,
							'collapse' => null,
							'row-items' => 3,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<section class="pi-item pi-group pi-border-color">
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 33.333333333333%">Test 1</h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 33.333333333333%" data-item-name="test2">Test 2</h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 33.333333333333%" data-source="test3" data-item-name="test3">Test 3</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 33.333333333333%">test value 1</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 33.333333333333%" data-item-name="test2">test value 2</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 33.333333333333%" data-source="test3" data-item-name="test3">test value 3</div>
								</section>
							</section>
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 100%" data-source="test4" data-item-name="test4">Test 4</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 100%" data-source="test4" data-item-name="test4">test value 4</div>
								</section>
							</section>
						</section>
					</aside>',
				'description' => 'Flex wrapped group of 4 elements with row size 3',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 1',
										'value' => 'test value 1',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => null,
							'collapse' => null,
							'row-items' => 3,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
					  <section class="pi-item pi-group pi-border-color">
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 100%">Test 1</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 100%">test value 1</div>
								</section>
							</section>
						</section>
					</aside>',
				'description' => 'Flex wrapped group of a single element with row size 3',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 1',
										'value' => 'test value 1',
										'span' => 2,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 2',
										'value' => 'test value 2',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => null,
							'collapse' => null,
							'row-items' => 3,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
					  <section class="pi-item pi-group pi-border-color">
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 66.666666666667%">Test 1</h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 33.333333333333%">Test 2</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 66.666666666667%">test value 1</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 33.333333333333%">test value 2</div>
								</section>
							</section>
						</section>
					</aside>',
				'description' => 'Flex wrapped group of 2 + 1 with row size 3',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 1',
										'value' => 'test value 1',
										'span' => 2,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 2',
										'value' => 'test value 2',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => null,
							'collapse' => null,
							'row-items' => 7,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<section class="pi-item pi-group pi-border-color">
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 66.666666666667%">Test 1</h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 33.333333333333%">Test 2</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 66.666666666667%">test value 1</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 33.333333333333%">test value 2</div>
								</section>
							</section>
						</section>
					</aside>',
				'description' => 'Flex wrapped group of 2 + 1 with row size 7',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 1',
										'value' => 'test value 1',
										'span' => 2,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 2',
										'value' => 'test value 2',
										'span' => 2,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 3',
										'value' => 'test value 3',
										'span' => 2,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => null,
							'collapse' => null,
							'row-items' => 3,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<section class="pi-item pi-group pi-border-color">
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 100%">Test 1</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 100%">test value 1</div>
								</section>
							</section>
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 100%">Test 2</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 100%">test value 2</div>
								</section>
							</section>
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 100%">Test 3</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 100%">test value 3</div>
								</section>
							</section>
						</section>
					</aside>',
				'description' => 'Flex wrapped group of 2 + 2 + 2 with row size 3',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 1',
										'value' => 'test value 1',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 2',
										'value' => 'test value 2',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 3',
										'value' => 'test value 3',
										'span' => null,
										'layout' => 'default',
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 4',
										'value' => 'test value 4',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 5',
										'value' => 'test value 5',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 6',
										'value' => 'test value 6',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => null,
							'collapse' => null,
							'row-items' => 3,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<section class="pi-item pi-group pi-border-color">
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 50%">Test 1</h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 50%">Test 2</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 50%">test value 1</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 50%">test value 2</div>
								</section>
							</section>
							<div class="pi-item pi-data pi-item-spacing pi-border-color">
								<h3 class="pi-data-label pi-secondary-font">Test 3</h3>
								<div class="pi-data-value pi-font">test value 3</div>
							</div>
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 33.333333333333%">Test 4</h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 33.333333333333%">Test 5</h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 33.333333333333%">Test 6</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 33.333333333333%">test value 4</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 33.333333333333%">test value 5</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 33.333333333333%">test value 6</div>
								</section>
							</section>
						</section>
					</aside>',
				'description' => 'Flex wrapped group of 1 + 1 + default + 1 + 1 + 1 with row size 3',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 1',
										'value' => 'test value 1',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 2',
										'value' => 'test value 2',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 3',
										'value' => 'test value 3',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 4',
										'value' => 'test value 4',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 5',
										'value' => 'test value 5',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 6',
										'value' => 'test value 6',
										'span' => null,
										'layout' => 'default',
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => null,
							'collapse' => null,
							'row-items' => 3,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<section class="pi-item pi-group pi-border-color">
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 33.333333333333%">Test 1</h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 33.333333333333%">Test 2</h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 33.333333333333%">Test 3</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 33.333333333333%">test value 1</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 33.333333333333%">test value 2</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 33.333333333333%">test value 3</div>
								</section>
							</section>
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 50%">Test 4</h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 50%">Test 5</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 50%">test value 4</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 50%">test value 5</div>
								</section>
							</section>
							<div class="pi-item pi-data pi-item-spacing pi-border-color">
								<h3 class="pi-data-label pi-secondary-font">Test 6</h3>
								<div class="pi-data-value pi-font">test value 6</div>
							</div>
						</section>
					</aside>',
				'description' => 'Flex wrapped group of 1 + 1 + 1 + 1 + 1 + default with row size 3',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 1',
										'value' => 'test value 1',
										'span' => 40,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 2',
										'value' => 'test value 2',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 3',
										'value' => 'test value 3',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => null,
							'collapse' => null,
							'row-items' => 3,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<section class="pi-item pi-group pi-border-color">
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 100%">Test 1</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 100%">test value 1</div>
								</section>
							</section>
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 50%">Test 2</h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 50%">Test 3</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 50%">test value 2</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 50%">test value 3</div>
								</section>
							</section>
						</section>
					</aside>',
				'description' => 'Flex wrapped group of 40 + 1 + 1 with row size 3',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 1',
										'value' => 'test value 1',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 2',
										'value' => 'test value 2',
										'span' => 40,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 3',
										'value' => 'test value 3',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => null,
							'collapse' => null,
							'row-items' => 3,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<section class="pi-item pi-group pi-border-color">
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 100%">Test 1</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 100%">test value 1</div>
								</section>
							</section>
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 100%">Test 2</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 100%">test value 2</div>
								</section>
							</section>
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 100%">Test 3</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 100%">test value 3</div>
								</section>
							</section>
						</section>
					</aside>',
				'description' => 'Flex wrapped group of 1 + 40 + 1 with row size 3',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 1',
										'value' => 'test value 1',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 2',
										'value' => 'test value 2',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'title',
									'data' => [
										'value' => 'title value',
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 3',
										'value' => 'test value 3',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => null,
							'collapse' => null,
							'row-items' => 3,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<section class="pi-item pi-group pi-border-color">
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 50%">Test 1</h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 50%">Test 2</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 50%">test value 1</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 50%">test value 2</div>
								 </section>
							</section>
							<h2 class="pi-item pi-item-spacing pi-title">title value</h2>
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 100%">Test 3</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 100%">test value 3</div>
								</section>
							</section>
						</section>
					</aside>',
				'description' => 'Flex wrapped group of 1 + 1 + title + 1 with row size 3',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'data',
									'data' => [
										'label' => null,
										'value' => 'test value 1',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => null,
										'value' => 'test value 2',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'title',
									'data' => [
										'value' => 'title value',
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => null,
										'value' => 'test value 3',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'Test 4',
										'value' => 'test value 4',
										'span' => 1,
										'layout' => null,
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => null,
							'collapse' => null,
							'row-items' => 3,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<section class="pi-item pi-group pi-border-color">
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 50%">test value 1</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 50%">test value 2</div>
								</section>
							</section>
							<h2 class="pi-item pi-item-spacing pi-title">title value</h2>
							<section class="pi-item pi-smart-group pi-border-color">
								<section class="pi-smart-group-head">
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 50%"></h3>
									<h3 class="pi-smart-data-label pi-data-label pi-secondary-font pi-item-spacing" style="width: 50%">Test 4</h3>
								</section>
								<section class="pi-smart-group-body">
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 50%">test value 3</div>
									<div class="pi-smart-data-value pi-data-value pi-font pi-item-spacing" style="width: 50%">test value 4</div>
								</section>
							</section>
						</section>
					</aside>',
				'description' => 'Flex wrapped group of 1 (no label) + 1 (no label) + title + 1 (no label) + 1 with row size 3',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title',
							'source' => null,
							'item-name' => null
						]
					],
					[
						'type' => 'media',
						'data' => [
							[
								'alt' => 'image alt',
								'url' => 'http://image.jpg',
								'caption' => 'caption',
								'ref' => 1,
								'width' => '400',
								'height' => '200',
								'thumbnail' => 'http://thumbnail.jpg',
								'thumbnail2x' => 'http://thumbnail2x.jpg',
								'isImage' => true,
								'isFirst' => true,
								'source' => 'src',
								'item-name' => 'img'
							],
							[
								'alt' => 'image alt',
								'url' => 'http://image.jpg',
								'caption' => 'caption',
								'ref' => 2,
								'width' => '400',
								'height' => '200',
								'thumbnail' => 'http://thumbnail.jpg',
								'thumbnail2x' => 'http://thumbnail2x.jpg',
								'isImage' => true,
								'source' => 'src',
								'item-name' => 'img'
							]
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<h2 class="pi-item pi-item-spacing pi-title">Test Title</h2>
						<div class="pi-media-collection" data-source="src" data-item-name="img">
							<ul class="pi-media-collection-tabs">
								<li class="pi-tab-link pi-item-spacing current" data-pi-tab="pi-tab-1">caption</li>
								<li class="pi-tab-link pi-item-spacing" data-pi-tab="pi-tab-2">caption</li>
							</ul>
							<div class="pi-media-collection-tab-content current" id="pi-tab-1">
								<figure class="pi-item pi-media pi-image" data-source="src" data-item-name="img">
									<a href="http://image.jpg" class="image image-thumbnail" title="image alt">
										<img src="http://thumbnail.jpg" srcset="http://thumbnail.jpg 1x, http://thumbnail2x.jpg 2x"
											class="pi-image-thumbnail" alt="image alt" width="400" height="200"/>
									</a>
								</figure>
							</div>
							<div class="pi-media-collection-tab-content" id="pi-tab-2">
								<figure class="pi-item pi-media pi-image" data-source="src" data-item-name="img">
									<a href="http://image.jpg" class="image image-thumbnail" title="image alt">
										<img src="http://thumbnail.jpg" srcset="http://thumbnail.jpg 1x, http://thumbnail2x.jpg 2x"
											class="pi-image-thumbnail" alt="image alt" width="400" height="200"/>
									</a>
								</figure>
							</div>
						</div>
					</aside>',
				'description' => 'Simple infobox with title and image collection',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title',
							'source' => null,
							'item-name' => null
						]
					],
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'header',
									'data' => [
										'value' => 'Test Header',
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value',
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value',
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => 'default',
							'collapse' => 'open',
							'row-items' => null,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<h2 class="pi-item pi-item-spacing pi-title">Test Title</h2>
						<section class="pi-item pi-group pi-border-color pi-collapse pi-collapse-open">
							<h2 class="pi-item pi-header pi-secondary-font pi-item-spacing pi-secondary-background">Test Header</h2>
							<div class="pi-item pi-data pi-item-spacing pi-border-color">
								<h3 class="pi-data-label pi-secondary-font">test label</h3>
								<div class="pi-data-value pi-font">test value</div>
							</div>
							<div class="pi-item pi-data pi-item-spacing pi-border-color">
								<h3 class="pi-data-label pi-secondary-font">test label</h3>
								<div class="pi-data-value pi-font">test value</div>
							</div>
						</section>
					</aside>',
				'description' => 'Infobox with title, collapsible group with header and two key-value pairs',
				'accentColor' => '',
				'accentColorText' => ''
			],
			[
				'input' => [
					[
						'type' => 'title',
						'data' => [
							'value' => 'Test Title',
							'source' => null,
							'item-name' => null
						]
					],
					[
						'type' => 'group',
						'data' => [
							'value' => [
								[
									'type' => 'header',
									'data' => [
										'value' => 'Test Header',
										'item-name' => 'header'
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value',
										'source' => null,
										'item-name' => null
									]
								],
								[
									'type' => 'data',
									'data' => [
										'label' => 'test label',
										'value' => 'test value',
										'source' => null,
										'item-name' => null
									]
								]
							],
							'layout' => 'default',
							'collapse' => 'closed',
							'row-items' => null,
							'item-name' => null
						]
					]
				],
				'output' => '<aside class="portable-infobox noexcerpt searchaux pi-background">
						<h2 class="pi-item pi-item-spacing pi-title">Test Title</h2>
						<section class="pi-item pi-group pi-border-color pi-collapse pi-collapse-closed">
							<h2 class="pi-item pi-header pi-secondary-font pi-item-spacing pi-secondary-background" data-item-name="header">Test Header</h2>
							<div class="pi-item pi-data pi-item-spacing pi-border-color">
								<h3 class="pi-data-label pi-secondary-font">test label</h3>
								<div class="pi-data-value pi-font">test value</div>
							</div>
							<div class="pi-item pi-data pi-item-spacing pi-border-color">
								<h3 class="pi-data-label pi-secondary-font">test label</h3>
								<div class="pi-data-value pi-font">test value</div>
							</div>
						</section>
					</aside>',
				'description' => 'Infobox with title, collapsed group with header and two key-value pairs',
				'accentColor' => '',
				'accentColorText' => ''
			]
		];
	}

	// phpcs:enable
}
