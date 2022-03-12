<?php

namespace PortableInfobox\Parser;

use BlockLevelPass;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\MediaWikiServices;
use MediaWiki\Tidy\RemexDriver;
use PageImages\Hooks\ParserFileProcessingHookHandlers;
use Parser;
use PPFrame;
use Title;

class MediaWikiParserService implements ExternalParser {

	protected $parser;
	protected $frame;
	protected $localParser;
	protected $tidyDriver;
	protected $cache = [];

	public function __construct( Parser $parser, PPFrame $frame ) {
		global $wgPortableInfoboxUseTidy;

		$this->parser = $parser;
		$this->frame = $frame;

		if ( $wgPortableInfoboxUseTidy && class_exists( RemexDriver::class ) ) {
			$this->tidyDriver = new RemexDriver( new ServiceOptions( [ 'TidyConfig' ], [
				'TidyConfig' => [ 'pwrap' => false ],
			] ) );
		}
	}

	/**
	 * Method used for parsing wikitext provided in infobox that might contain variables
	 *
	 * @param string $wikitext
	 *
	 * @return string HTML outcome
	 */
	public function parseRecursive( $wikitext ) {
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

		/* if ( isset( $this->tidyDriver ) ) {
			$ready = $this->tidyDriver->tidy( $ready );
		} */

		$tidy = static function ( $ready ) {
			return static function () use ( $ready ) {
				$tidy = new RemexDriver( new ServiceOptions( [ 'TidyConfig' ], [
					'TidyConfig' => [ 'pwrap' => false ],
				] ) );

				$tidy->tidy( $ready );
			};
		};

		$ready = $tidy( $ready );

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
}
