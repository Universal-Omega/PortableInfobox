<?php

namespace PortableInfobox\Parser;

use MediaWiki\MediaWikiServices;

class MediaWikiParserService implements ExternalParser {

	protected $parser;
	protected $frame;
	protected $localParser;
	protected $tidyDriver;
	protected $cache = [];

	public function __construct( \Parser $parser, \PPFrame $frame ) {
		global $wgPortableInfoboxUseTidy;

		$this->parser = $parser;
		$this->frame = $frame;

		if ( $wgPortableInfoboxUseTidy && class_exists( '\MediaWiki\Tidy\RemexDriver' ) ) {
			if ( version_compare( MW_VERSION, '1.36', '>=' ) ) {
				$this->tidyDriver = MediaWikiServices::getInstance()->getTidy();
			} else {
				$this->tidyDriver = \MWTidy::factory( [
					'driver' => 'RemexHtml',
					'pwrap' => false
				] );
			}
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

		$parsed = $wikitext ? $this->parser->internalParse( $wikitext, false, $this->frame ) : null;
		if ( in_array( substr( $parsed, 0, 1 ), [ '*', '#' ] ) ) {
			//fix for first item list elements
			$parsed = "\n" . $parsed;
		}
		$output = $this->parser->doBlockLevels( $parsed, false );
		$ready = $this->parser->mStripState->unstripBoth( $output );
		$this->parser->replaceLinkHolders( $ready );
		if ( isset( $this->tidyDriver ) ) {
			$ready = $this->tidyDriver->tidy( $ready );
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
	 * @param \Title $title
	 */
	public function addImage( $title ) {
		$file = wfFindFile( $title );
		$tmstmp = $file ? $file->getTimestamp() : null;
		$sha1 = $file ? $file->getSha1() : null;
		$this->parser->getOutput()->addImage( $title->getDBkey(), $tmstmp, $sha1 );

		// Pass PI images to PageImages extension if available (Popups and og:image)
		if ( \method_exists(
			'\PageImages\Hooks\ParserFileProcessingHookHandlers', 'onParserMakeImageParams'
		) ) {
			$params = [];
			\PageImages\Hooks\ParserFileProcessingHookHandlers::onParserMakeImageParams(
				$title, $file, $params, $this->parser
			);
		}
	}
}
