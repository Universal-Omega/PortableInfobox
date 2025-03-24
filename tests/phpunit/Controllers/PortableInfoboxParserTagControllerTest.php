<?php

namespace PortableInfobox\Tests\Controllers;

use DOMDocument;
use DOMXPath;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use PortableInfobox\Controllers\PortableInfoboxParserTagController;

/**
 * @group PortableInfobox
 * @group Database
 * @covers \PortableInfobox\Controllers\PortableInfoboxParserTagController
 */
class PortableInfoboxParserTagControllerTest extends MediaWikiIntegrationTestCase {

	private const THEME_PREFIX = PortableInfoboxParserTagController::INFOBOX_THEME_PREFIX;
	private const THEME_DEFAULT = self::THEME_PREFIX . PortableInfoboxParserTagController::DEFAULT_THEME_NAME;

	/** @var Parser */
	protected $parser;

	/** @var PortableInfoboxParserTagController */
	protected $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->parser = $this->setUpParser();
		$this->controller = new PortableInfoboxParserTagController();
	}

	protected function tearDown(): void {
		// we use libxml only for tests here
		libxml_clear_errors();
		parent::tearDown();
	}

	protected function setUpParser() {
		$parser = MediaWikiServices::getInstance()->getParser();
		$user = $this->getTestUser()->getUser();
		$options = new ParserOptions( $user );
		$title = Title::newFromText( 'Test' );
		$parser->setOptions( $options );
		$parser->startExternalParse( $title, $options, Parser::OT_PLAIN, true );

		return $parser;
	}

	/**
	 * @param $html
	 * @return string
	 */
	private function normalizeHTML( $html ) {
		$DOM = new DOMDocument( '1.0' );
		$DOM->formatOutput = true;
		$DOM->preserveWhiteSpace = false;
		$DOM->loadXML( $html );

		return $DOM->saveXML();
	}

	protected function containsClassName( $output, $class ) {
		$xpath = $this->getXPath( $output );

		return $xpath->query( '//aside[contains(@class, \'' . $class . '\')]' )->length > 0;
	}

	protected function getXPath( $output ) {
		$result = new DOMDocument();

		// Surpress `Warning: DOMDocument::loadHTML(): Tag aside invalid in Entity`
		// http://stackoverflow.com/questions/9149180/domdocumentloadhtml-error
		$setting = libxml_use_internal_errors( true );
		$result->loadHTML( $output );
		libxml_use_internal_errors( $setting );

		return new DOMXPath( $result );
	}

	public function testEmptyInfobox() {
		$text = '';

		$output = $this->controller->renderInfobox( $text, [], $this->parser,
			$this->parser->getPreprocessor()->newFrame() )[0];

		$this->assertSame( '', $output, 'Should be empty' );
	}

	/**
	 * @dataProvider themeNamesProvider
	 */
	public function testThemes( $staticTheme, $variableTheme, $classes, $message ) {
		$text = '<data><default>test</default></data>';

		$output = $this->controller->renderInfobox( $text,
			[ 'theme' => $staticTheme, 'theme-source' => 'testVar' ],
			$this->parser,
			$this->parser->getPreprocessor()->newCustomFrame( [ 'testVar' => $variableTheme ] ) )[0];

		$this->assertTrue( array_reduce( $classes, function ( $result, $class ) use ( $output ) {
			return $result && $this->containsClassName( $output, $class );
		}, true ), $message );
	}

	public function themeNamesProvider() {
		return [
			// static theme, variable theme, [ classes ], message
			[
				' ',
				'',
				[ self::THEME_DEFAULT ],
				'Should use default when theme names are invalid'
			],
			[
				'test',
				null,
				[ self::THEME_PREFIX . 'test' ],
				'Should contain static theme'
			],
			[
				null,
				'variable',
				[ self::THEME_PREFIX . 'variable' ],
				'Should contain theme from params'
			],
			[
				'default',
				'variable',
				[ self::THEME_PREFIX . 'default', self::THEME_PREFIX . 'variable' ],
				'Should contain static and param themes'
			],
			[
				null,
				null,
				[ self::THEME_DEFAULT ],
				'Should contain default theme'
			],
			[
				' test test',
				null,
				[ self::THEME_PREFIX . 'test-test' ],
				'Should sanitize infobox theme name'
			],
			[
				"test    test\n test\ttest",
				null,
				[ self::THEME_PREFIX . 'test-test-test-test' ],
				'Should sanitize multiline infobox theme name'
			]
		];
	}

	/**
	 * @dataProvider getLayoutDataProvider
	 */
	public function testGetLayout( $layout, $expectedOutput, $text, $message ) {
		$output = $this->controller->renderInfobox(
			$text, $layout, $this->parser,
			$this->parser->getPreprocessor()->newFrame()
		)[0];

		$this->assertTrue( $this->containsClassName(
			$output,
			$expectedOutput
		), $message );
	}

	public function getLayoutDataProvider() {
		return [
			[
				'layout' => [ 'layout' => 'stacked' ],
				'expectedOutput' => 'pi-layout-stacked',
				'text' => '<data><default>test</default></data>',
				'message' => 'set stacked layout'
			],
			[
				'layout' => [ 'layout' => 'looool' ],
				'expectedOutput' => 'pi-layout-default',
				'text' => '<data><default>test</default></data>',
				'message' => 'invalid layout name'
			],
			[
				'layout' => [ 'layout' => '' ],
				'expectedOutput' => 'pi-layout-default',
				'text' => '<data><default>test</default></data>',
				'message' => 'layout is empty string'
			],
			[
				'layout' => [ 'layout' => 5 ],
				'expectedOutput' => 'pi-layout-default',
				'text' => '<data><default>test</default></data>',
				'message' => 'layout is an integer'
			],
			[
				'layout' => [ 'layout' => [] ],
				'expectedOutput' => 'pi-layout-default',
				'text' => '<data><default>test</default></data>',
				'message' => 'layout an empty table'
			],
			[
				'layout' => [],
				'expectedOutput' => 'pi-layout-default',
				'text' => '<data><default>test</default></data>',
				'message' => 'layout is not set'
			]
		];
	}

	/**
	 * @dataProvider getColorDataProvider
	 */
	public function testGetColor( $params, $expectedOutput, $text, $templateInvocation, $message ) {
		$output = $this->controller->renderInfobox(
			$text, $params, $this->parser,
			$this->parser->getPreprocessor()->newCustomFrame( $templateInvocation )
		)[0];

		$this->assertEquals(
			$this->normalizeHTML( $expectedOutput ),
			$this->normalizeHTML( $output ),
			$message
		);
	}

	public function getColorDataProvider() {
		return [
			[
				[ 'accent-color-default' => '#fff' ],
				'<aside class="portable-infobox noexcerpt searchaux pi-background pi-theme-default pi-layout-default">
					<h2 class="pi-item pi-item-spacing pi-title" style="background-color:#fff;">test</h2>
				</aside>',
				'<title><default>test</default></title>',
				[],
				'accent-color-default set'
			],
			[
				[ 'accent-color-source' => 'color-source' ],
				'<aside class="portable-infobox noexcerpt searchaux pi-background pi-theme-default pi-layout-default">
					<h2 class="pi-item pi-item-spacing pi-title" style="background-color:#000;">test</h2>
				</aside>',
				'<title><default>test</default></title>',
				[ 'color-source' => '#000' ],
				'accent-color-source set'
			],
			[
				[
					'accent-color-default' => '#fff',
					'accent-color-source' => 'color-source'
				],
				'<aside class="portable-infobox noexcerpt searchaux pi-background pi-theme-default pi-layout-default">
					<h2 class="pi-item pi-item-spacing pi-title" style="background-color:#000;">test</h2>
				</aside>',
				'<title><default>test</default></title>',
				[ 'color-source' => '#000' ],
				'accent-color-default and accent-color-source set'
			],
			[
				[ 'accent-color-text-default' => '#fff' ],
				'<aside class="portable-infobox noexcerpt searchaux pi-background pi-theme-default pi-layout-default">
					<h2 class="pi-item pi-item-spacing pi-title" style="color:#fff;">test</h2>
				</aside>',
				'<title><default>test</default></title>',
				[],
				'accent-color-text-default set'
			],
			[
				[
					'accent-color-text-default' => '#fff',
					'accent-color-text-source' => 'color-source'
				],
				'<aside class="portable-infobox noexcerpt searchaux pi-background pi-theme-default pi-layout-default">
					<h2 class="pi-item pi-item-spacing pi-title" style="color:#000;">test</h2>
				</aside>',
				'<title><default>test</default></title>',
				[ 'color-source' => '#000' ],
				'accent-color-text-source set'
			],
			[
				[
					'accent-color-text-default' => '#fff',
					'accent-color-text-source' => 'color-source'
				],
				'<aside class="portable-infobox noexcerpt searchaux pi-background pi-theme-default pi-layout-default">
					<h2 class="pi-item pi-item-spacing pi-title" style="color:#000;">test</h2>
				</aside>',
				'<title><default>test</default></title>',
				[ 'color-source' => '#000' ],
				'accent-color-text-default and accent-color-text-source set'
			],
			[
				[
					'accent-color-text-default' => '#fff',
					'accent-color-text-source' => 'color-source',
					'accent-color-default' => '#fff',
					'accent-color-source' => 'color-source2'
				],
				'<aside class="portable-infobox noexcerpt searchaux pi-background pi-theme-default pi-layout-default">
					<h2 class="pi-item pi-item-spacing pi-title"
						style="background-color:#001;color:#000;">test</h2>
				</aside>',
				'<title><default>test</default></title>',
				[
					'color-source' => '#000',
					'color-source2' => '#001'
				],
				'accent-color-text-default and accent-color-text-source, accent-color-default, ' .
					'accent-color-source set'
			],
			[
				[
					'accent-color-text-default' => 'fff',
					'accent-color-text-source' => 'color-source',
					'accent-color-default' => 'fff',
					'accent-color-source' => 'color-source2'
				],
				'<aside class="portable-infobox noexcerpt searchaux pi-background pi-theme-default pi-layout-default">
					<h2 class="pi-item pi-item-spacing pi-title" 
						style="background-color:#001;color:#000;">test</h2>
				</aside>',
				'<title><default>test</default></title>',
				'templateInvocation' => [
					'color-source' => '000',
					'color-source2' => '001'
				],
				'colors without #'
			],
		];
	}

	/**
	 * @dataProvider paramsDataProvider
	 */
	public function testParamsParsing( $expected, $params ) {
		$text = '<data source="0"><label>0</label></data>
    <data source="1"><label>1</label></data>
    <data source="2"><label>2</label></data>
    <data source="3"><label>3</label></data>';

		$output = $this->controller->renderInfobox( $text, [], $this->parser,
			$this->parser->getPreprocessor()->newCustomFrame( $params ) )[0];

		$result = [];
		$xpath = $this->getXPath( $output );
		// get all data nodes from parsed infobox
		$dataNodes = $xpath->query( '//aside/div[contains(@class,\'pi-data\')]' );
		for ( $i = 0; $i < $dataNodes->length; $i++ ) {
			// get map of label => value from parsed data node
			$result[$xpath->query( 'h3[contains(@class, \'pi-data-label\')]', $dataNodes->item( $i ) )
				->item( 0 )->nodeValue] =
				$xpath->query( 'div[contains(@class, \'pi-data-value\')]', $dataNodes->item( $i ) )
					->item( 0 )->nodeValue;
		}

		$this->assertEquals( $expected, $result );
	}

	public function paramsDataProvider() {
		return [
			[ [ 0 => 'zero', 1 => 'one', 2 => 'two' ], [ 'zero', 'one', 'two' ] ],
			[ [ 1 => 'three', 2 => 'four', 3 => 'five' ],
				// this is actual mw way of handling params provided as "1=one|2=two|three|four|five"
				[ '1' => 'one', '2' => 'two', 1 => 'three', 2 => 'four', 3 => 'five' ] ],
			[ [ 1 => 'one', 2 => 'two', 3 => 'three' ], [ '1' => 'one', '2' => 'two', '3' => 'three' ] ],
			[ [ 0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three' ],
				[ '-1' => 'minus one', '0' => 'zero', '1' => 'one', '2' => 'two', '3' => 'three' ] ],
			[ [ 0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three' ],
				[ 'abc' => 'minus one', '0' => 'zero', '1' => 'one', '2' => 'two', '3' => 'three' ] ],
		];
	}
}
