<?php

namespace PortableInfobox\Tests\Services\Helpers;

use MediaWikiIntegrationTestCase;
use PortableInfobox\Services\Helpers\InfoboxParamsValidator;
use PortableInfobox\Services\Helpers\InvalidInfoboxParamsException;

/**
 * @group PortableInfobox
 * @covers \PortableInfobox\Services\Helpers\InfoboxParamsValidator
 */
class InfoboxParamsValidatorTest extends MediaWikiIntegrationTestCase {

	/** @var InfoboxParamsValidator $InfoboxParamsValidator */
	private $InfoboxParamsValidator;

	protected function setUp(): void {
		parent::setUp();
		$this->InfoboxParamsValidator = new InfoboxParamsValidator();
	}

	protected function tearDown(): void {
		unset( $this->InfoboxParamsValidator );
		parent::tearDown();
	}

	/**
	 * @param array $params
	 * @dataProvider infoboxParamsFailValidationDataProvider
	 */
	public function testInfoboxParamsFailValidation( $params ) {
		$this->expectException( InvalidInfoboxParamsException::class );

		$this->InfoboxParamsValidator->validateParams( $params );
	}

	/**
	 * @param array $params
	 * @dataProvider infoboxParamsPassValidationDataProvider
	 */
	public function testInfoboxParamsPassValidation( $params ) {
		$this->assertTrue( $this->InfoboxParamsValidator->validateParams( $params ) );
	}

	public function infoboxParamsFailValidationDataProvider() {
		return [
			[
				'params' => [
					'theme' => 'test',
					'abc' => 'def',
					'layout' => 'myLayout'
				]
			],
			[
				'params' => [
					'abc' => 'def',
				]
			],
		];
	}

	public function infoboxParamsPassValidationDataProvider() {
		return [
			[
				'params' => [],
			],
			[
				'params' => [
					'theme' => 'test',
					'theme-source' => 'loremIpsum',
					'layout' => 'myLayout'
				]
			],
			[
				'params' => [
					'theme' => 'test',
				]
			]
		];
	}

	/**
	 * @param string $color
	 * @param bool $addHash
	 * @dataProvider passValidateColorValueDataProvider
	 */
	public function testPassValidateColorValue( $color, $addHash = false ) {
		$this->assertEquals(
			( $addHash ? '#' : '' ) . strtolower( preg_replace( '/\s+/', '', $color ) ),
			$this->InfoboxParamsValidator->validateColorValue( $color )
		);
	}

	public function passValidateColorValueDataProvider() {
		return [
			[ 'color' => '#aaa' ],
			[ 'color' => '#abc' ],
			[ 'color' => '#a12' ],
			[ 'color' => '#12f' ],
			[ 'color' => '#fff' ],
			[ 'color' => '#000' ],
			[ 'color' => '#999' ],
			[ 'color' => '#aaaa' ],
			[ 'color' => '#aaaaaa' ],
			[ 'color' => '#abcabc' ],
			[ 'color' => '#a12acd' ],
			[ 'color' => '#12f126' ],
			[ 'color' => '#adf129' ],
			[ 'color' => '#125fff' ],
			[ 'color' => '#ffffff' ],
			[ 'color' => '#000000' ],
			[ 'color' => '#999999' ],
			[ 'color' => '#ffffffff' ],
			[ 'color' => 'aaa', true ],
			[ 'color' => 'abc', true ],
			[ 'color' => 'a12', true ],
			[ 'color' => '12f', true ],
			[ 'color' => 'fff', true ],
			[ 'color' => '000', true ],
			[ 'color' => '999', true ],
			[ 'color' => 'aaaa', true ],
			[ 'color' => 'a12acd', true ],
			[ 'color' => 'aaaaaa', true ],
			[ 'color' => 'abcabc', true ],
			[ 'color' => 'ffffff', true ],
			[ 'color' => '000000', true ],
			[ 'color' => '999999', true ],
			[ 'color' => '125fff', true ],
			[ 'color' => 'ffffffff', true ],
			[ 'color' => 'rgb(0,0,0)' ],
			[ 'color' => 'RGB(20,3,255)' ],
			[ 'color' => 'rgb( 20, 3, 255 )' ],
			[ 'color' => 'rgb(255,255,255)' ],
			[ 'color' => 'rgb(0%,25%,100%)' ],
			[ 'color' => 'rgb(10%,75%,33%)' ],
			[ 'color' => 'RGB(100%,100%,100%)' ],
			[ 'color' => 'rgba(0,0,0,0)' ],
			[ 'color' => 'rgba(0,0,0,0%)' ],
			[ 'color' => 'rgba(20,3,255,50%)' ],
			[ 'color' => 'RGBA(20, 3, 255,.5)' ],
			[ 'color' => 'rgba( 20, 3, 255, 0.5 )' ],
			[ 'color' => 'rgba(255,255,255, 1)' ],
			[ 'color' => 'rgba(255,255,255, 100%)' ],
			[ 'color' => 'rgba(0%,25%,99% ,25%)' ],
			[ 'color' => 'RGBA(100%,100%,100%, 1)' ],
			[ 'color' => 'rgba(100%,100%,100%, 100%)' ],
			[ 'color' => 'hsl(0,0%,0%)' ],
			[ 'color' => 'hsl(40, 78%, 45%)' ],
			[ 'color' => 'HSL( -80, 58%, 30%)' ],
			[ 'color' => 'hsl(360,100%,100%)' ],
			[ 'color' => 'hsla(0,0%,0%,0)' ],
			[ 'color' => 'hsla(0,0%,0%,0%)' ],
			[ 'color' => 'HSLA(40,78%,45%,.3)' ],
			[ 'color' => 'hsla( 359, 38%,20% ,0.7)' ],
			[ 'color' => 'HSLA(360,100%,100%,100%)' ],
			[ 'color' => 'hsla( 360 , 100% , 100%,1)' ],
			[ 'color' => 'white' ],
			[ 'color' => 'White' ],
			[ 'color' => 'WHITE' ],
		];
	}

	/**
	 * @param array $color
	 * @dataProvider failValidateColorValueDataProvider
	 */
	public function testFailValidateColorValue( $color ) {
		$this->assertSame( '', $this->InfoboxParamsValidator->validateColorValue( $color ) );
	}

	public function failValidateColorValueDataProvider() {
		return [
			[ 'color' => '' ],
			[ 'color' => 'ggg' ],
			[ 'color' => 'asd' ],
			[ 'color' => '12g' ],
			[ 'color' => '1k2' ],
			[ 'color' => 'l34' ],
			[ 'color' => 'aaag' ],
			[ 'color' => '#ggg' ],
			[ 'color' => '#asd' ],
			[ 'color' => '#12g' ],
			[ 'color' => '#1k2' ],
			[ 'color' => '#l34' ],
			[ 'color' => '#aaag' ],
			[ 'color' => 'aaaaa' ],
			[ 'color' => '12fl26' ],
			[ 'color' => 'adfl29' ],
			[ 'color' => 'aaaaaaa' ],
			[ 'color' => '#aaaaaaa' ],
			[ 'color' => '#aaaaa' ],
			[ 'color' => 'fffffffff' ],
			[ 'color' => '#fffffffff' ],
			[ 'color' => 'rgb(0a,0a,0a)' ],
			[ 'color' => 'rgb(20,3,265)' ],
			[ 'color' => 'rgb( -20, 3, 255 )' ],
			[ 'color' => 'rgb(256,355,67)' ],
			[ 'color' => 'rgb(256,355)' ],
			[ 'color' => 'rgb(256,355,)' ],
			[ 'color' => 'rgb(256,355,,63)' ],
			[ 'color' => 'rgb(552,355,,63)' ],
			[ 'color' => 'rgb(0%,25%,102%)' ],
			[ 'color' => 'rgba(255,255,255,1.)' ],
			[ 'color' => 'rgba(100%,100%,100%)' ],
			[ 'color' => 'rgba(100%,100%,100%,.)' ],
			[ 'color' => 'hsl(0,0%,0%,0)' ],
			[ 'color' => 'hsl(0,-78%,45%)' ],
			[ 'color' => 'hsl(0%,85%,12%)' ],
			[ 'color' => 'hsl(356,14%,100)' ],
			[ 'color' => 'hsla(0,0%,0%)' ],
			[ 'color' => 'hsla(0,0%,0%,.)' ],
			[ 'color' => 'hsla(0,0%,0%,0.)' ],
		];
	}

	/**
	 * @param array $layout
	 * @dataProvider passValidateLayoutDataProvider
	 */
	public function testPassValidateLayout( $layout ) {
		$this->assertTrue( $this->InfoboxParamsValidator->validateLayout( $layout ) );
	}

	public function passValidateLayoutDataProvider() {
		return [
			[ 'layout' => 'default' ],
			[ 'layout' => 'stacked' ]
		];
	}

	/**
	 * @param array $layout
	 * @dataProvider failValidateLayoutDataProvider
	 */
	public function testFailValidateLayout( $layout ) {
		$this->assertFalse( $this->InfoboxParamsValidator->validateLayout( $layout ) );
	}

	public function failValidateLayoutDataProvider() {
		return [
			[ 'layout' => '' ],
			[ 'layout' => 'custom' ]
		];
	}
}
