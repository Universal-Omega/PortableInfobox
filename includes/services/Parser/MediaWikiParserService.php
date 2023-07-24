<?php

namespace PortableInfobox\Parser;

use BlockLevelPass;
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
			global $wgTidyConfig;

			$wgTidyConfig = [
				'driver' => 'RemexHtml',
				'pwrap' => false
			];

			$this->tidyDriver = MediaWikiServices::getInstance()->getTidy();
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

		$parsed = $this->parser->recursiveTagParse( $wikitext ?? '', $this->frame );
		if ( in_array( substr( $parsed, 0, 1 ), [ '*', '#' ] ) ) {
			// fix for first item list elements
			$parsed = "\n" . $parsed;
		}

		// @phan-suppress-next-line PhanAccessMethodInternal
		$output = BlockLevelPass::doBlockLevels( $parsed, false );
		$ready = $this->parser->getStripState()->unstripBoth( $output );

		// @phan-suppress-next-line PhanDeprecatedFunction
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
	 * @param Title $title
	 * @return ?string PageImages markers, if any.
	 */
	public function addImage( $title ): ?string {
		$services = MediaWikiServices::getInstance();

		$repoGroup = $services->getRepoGroup();

		$file = $repoGroup->findFile( $title );
		$tmstmp = $file ? $file->getTimestamp() : null;
		$sha1 = $file ? $file->getSha1() : null;
		$this->parser->getOutput()->addImage( $title->getDBkey(), $tmstmp, $sha1 );

		// Pass PI images to PageImages extension if available (Popups and og:image). Since 1.38, this produces an HTML
		// comment that must be present in the rendered HTML for the image to qualify for selection.
		if ( method_exists(
			ParserFileProcessingHookHandlers::class, 'onParserModifyImageHTML'
		) ) {
			// @phan-suppress-next-line PhanParamTooMany
			$handler = new ParserFileProcessingHookHandlers(
				$repoGroup,
				$services->getMainWANObjectCache(),
				$services->getHttpRequestFactory(),
				$services->getDBLoadBalancerFactory(),
				$services->getTitleFactory()
			);

			$params = [];
			$html = '';

			$handler->onParserModifyImageHTML(
				$this->parser, $file, $params, $html
			);

			return $html;
		}

		return null;
	}
}
