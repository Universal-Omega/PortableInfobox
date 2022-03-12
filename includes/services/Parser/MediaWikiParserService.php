<?php

namespace PortableInfobox\Parser;

use BlockLevelPass;
use MediaWiki\MediaWikiServices;
use MediaWiki\Tidy\RemexCompatFormatter;
use MediaWiki\Tidy\RemexDriver;
use PageImages\Hooks\ParserFileProcessingHookHandlers;
use Parser;
use PPFrame;
use Title;
use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Serializer\Serializer;
use Wikimedia\RemexHtml\Tokenizer\Tokenizer;
use Wikimedia\RemexHtml\TreeBuilder\Dispatcher;
use Wikimedia\RemexHtml\TreeBuilder\TreeBuilder;

class MediaWikiParserService implements ExternalParser {

	protected $parser;
	protected $frame;
	protected $localParser;
	protected $cache = [];

	public function __construct( Parser $parser, PPFrame $frame ) {
		$this->parser = $parser;
		$this->frame = $frame;
	}

	/**
	 * Method used for parsing wikitext provided in infobox that might contain variables
	 *
	 * @param string $wikitext
	 *
	 * @return string HTML outcome
	 */
	public function parseRecursive( $wikitext ) {
		global $wgPortableInfoboxUseTidy;

		if ( isset( $this->cache[$wikitext] ) ) {
			return $this->cache[$wikitext];
		}

		$parsed = $this->parser->recursiveTagParse( $wikitext, $this->frame );
		if ( in_array( substr( $parsed, 0, 1 ), [ '*', '#' ] ) ) {
			// fix for first item list elements
			$parsed = "\n" . $parsed;
		}

		// @phan-suppress-next-line PhanAccessMethodInternal
		$output = BlockLevelPass::doBlockLevels( $parsed, false );
		$ready = $this->parser->getStripState()->unstripBoth( $output );

		// @phan-suppress-next-line PhanDeprecatedFunction
		$this->parser->replaceLinkHolders( $ready );

		if ( $wgPortableInfoboxUseTidy && class_exists( RemexDriver::class ) ) {
			$ready = self::tidy( $ready );
		}

		$newlinesstripped = preg_replace( '|[\n\r]|Us', '', $ready );
		$marksstripped = preg_replace( '|{{{.*}}}|Us', '', $newlinesstripped );

		$this->cache[$wikitext] = $marksstripped;

		return $marksstripped;
	}

	public function replaceVariables( $wikitext ) {
		$output = $this->parser->replaceVariables( $wikitext, $this->frame );

		return $output;
	}

	/**
	 * Add image to parser output for later usage
	 *
	 * @param Title $title
	 */
	public function addImage( $title ) {
		$repoGroup = MediaWikiServices::getInstance()->getRepoGroup();

		$file = $repoGroup->findFile( $title );
		$tmstmp = $file ? $file->getTimestamp() : null;
		$sha1 = $file ? $file->getSha1() : null;
		$this->parser->getOutput()->addImage( $title->getDBkey(), $tmstmp, $sha1 );

		// Pass PI images to PageImages extension if available (Popups and og:image)
		if ( method_exists(
			ParserFileProcessingHookHandlers::class, 'onParserMakeImageParams'
		) ) {
			$params = [];
			ParserFileProcessingHookHandlers::onParserMakeImageParams(
				$title, $file, $params, $this->parser
			);
		} elseif ( method_exists(
			ParserFileProcessingHookHandlers::class, 'onParserModifyImageHTML'
		) ) {
			// 1.38+
			$params = [];
			$html = '';

			// @phan-suppress-next-line PhanUndeclaredStaticMethod
			ParserFileProcessingHookHandlers::onParserModifyImageHTML(
				$this->parser, $file, $params, $html
			);
		}
	}

	private static function tidy( $text ) {
		$formatter = new RemexCompatFormatter( [ 'textProcessor' => null ] );
		$serializer = new Serializer( $formatter );

		$treeBuilder = new TreeBuilder( $serializer, [
			'ignoreErrors' => true,
			'ignoreNulls' => true,
		] );

		$dispatcher = new Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer( $dispatcher, $text, [
			'ignoreErrors' => true,
			'ignoreCharRefs' => true,
			'ignoreNulls' => true,
			'skipPreprocess' => true,
		] );

		$tokenizer->execute( [
			'fragmentNamespace' => HTMLData::NS_HTML,
			'fragmentName' => 'body'
		] );

		return $serializer->getResult();
	}
}
