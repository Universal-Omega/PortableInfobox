<?php

namespace PortableInfobox\Services\Parser;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\BlockLevelPass;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Tidy\RemexDriver;
use MediaWiki\Title\Title;
use PageImages\Hooks\ParserFileProcessingHookHandlers;

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
			$this->tidyDriver = new RemexDriver(
				new ServiceOptions(
					// @phan-suppress-next-line PhanAccessClassConstantInternal
					RemexDriver::CONSTRUCTOR_OPTIONS,
					[
						MainConfigNames::TidyConfig => [
							'driver' => 'RemexHtml',
							'pwrap' => false,
						],
					],
					// Removed in MediaWiki 1.45, so we don't use MainConfigNames here.
					// Can be removed when we drop backcompat.
					[ 'ParserEnableLegacyMediaDOM' => false ]
				)
			);
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
	 * @param array $sizeParams
	 * @return ?string PageImages markers, if any.
	 */
	public function addImage( $title, array $sizeParams ): ?string {
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
			$handler = new ParserFileProcessingHookHandlers(
				$services->getMainConfig(),
				$repoGroup,
				$services->getMainWANObjectCache(),
				$services->getHttpRequestFactory(),
				$services->getConnectionProvider(),
				$services->getTitleFactory(),
				$services->getLinksMigration()
			);

			$params = [
				'handler' => $sizeParams,
			];
			$html = '';

			$handler->onParserModifyImageHTML(
				$this->parser, $file, $params, $html
			);

			return $html;
		}

		return null;
	}
}
