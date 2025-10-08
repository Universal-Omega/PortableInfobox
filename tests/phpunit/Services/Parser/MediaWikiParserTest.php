<?php

namespace PortableInfobox\Tests\Services\Parser;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\BlockLevelPass;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use PortableInfobox\Services\Parser\MediaWikiParserService;

/**
 * @group PortableInfobox
 * @group Database
 * @covers \PortableInfobox\Services\Parser\MediaWikiParserService
 */
class MediaWikiParserTest extends MediaWikiIntegrationTestCase {

	/** @var Parser */
	protected $parser;

	public function setUp(): void {
		$this->setMwGlobals( 'wgParserEnableLegacyMediaDOM', false );
		$this->setMwGlobals( 'wgTidyConfig', [
			'driver' => 'RemexHtml',
			'pwrap' => false,
		] );

		$this->parser = MediaWikiServices::getInstance()->getParser();
		$title = Title::newFromText( 'test' );
		$user = $this->getTestUser()->getUser();
		$options = new ParserOptions( $user );
		$options->setOption( 'wrapclass', false );
		$this->parser->startExternalParse( $title, $options, Parser::OT_PLAIN, true );
		parent::setUp();
	}

	public function tearDown(): void {
		unset( $this->parser );
		parent::tearDown();
	}

	protected function parse( $wikitext, $params, $newline = false ) {
		$withVars = $this->parser->replaceVariables(
			$wikitext, $this->parser->getPreprocessor()->newCustomFrame( $params )
		);
		$parserOutput = $this->parser->parse(
			$withVars, $this->parser->getTitle(), $this->parser->getOptions(), $newline
		);

		return preg_replace(
			'|{{{.*}}}|Us', '', preg_replace( '|[\n\r]|Us', '', $parserOutput->getContentHolderText() )
		);
	}

	public function testAsideTagPWrappedDuringParsing() {
		$aside = "<aside></aside>";
		$result = BlockLevelPass::doBlockLevels( $aside, true );

		$this->assertEquals( $aside, $result );
	}

	/**
	 * @dataProvider mwParserWrapperDataProvider
	 *
	 * @param $wikitext
	 * @param $params
	 */
	public function testWrapper( $wikitext, $params, $newline ) {
		$frame = $this->parser->getPreprocessor()->newCustomFrame( $params );
		$wrapper = new MediaWikiParserService( $this->parser, $frame );

		$output = $wrapper->parseRecursive( $wikitext );

		$this->assertEquals( $this->parse( $wikitext, $params, $newline ), $output );
	}

	public function mwParserWrapperDataProvider() {
		return [
			[ "*1\n*2\n*3", [], true ],
			[ "''d''", [], false ],
			[ "'''dd'''", [], false ],
			[ "#1\n#2\n#3 ksajdlk", [], true ],
			[ "{{{test}}}", [ 'test' => 1 ], false ],
			[ " :asdf", [], false ],
			[ "\n:asdf", [], false ],
			[ "\n;asdf", [], false ],
			[ "[[asdf]]", [], false ]
		];
	}
}
