<?php

namespace PortableInfobox\Services\Helpers;

use Exception;
use MediaWiki\Content\TextContent;
use MediaWiki\Context\RequestContext;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use PortableInfobox\Controllers\PortableInfoboxParserTagController;
use PortableInfobox\Services\PortableInfoboxDataService;

class PortableInfoboxParsingHelper {

	protected $parserTagController;
	protected $logger;

	public function __construct() {
		$this->parserTagController = PortableInfoboxParserTagController::getInstance();
		$this->logger = LoggerFactory::getInstance( 'PortableInfobox' );
	}

	/**
	 * Try to find out if infobox got "hidden" inside includeonly tag. Parse it if that's the case.
	 *
	 * @param Title $title
	 *
	 * @return mixed false when no infoboxes found, Array with infoboxes on success
	 */
	public function parseIncludeonlyInfoboxes( Title $title ) {
		// for templates we need to check for include tags
		$templateText = $this->fetchArticleContent( $title );

		if ( $templateText ) {
			$parser = MediaWikiServices::getInstance()->getParser();
			$parser->setPage( $title );
			$parserOptions = ParserOptions::newFromAnon();
			$parser->setOptions( $parserOptions );
			$frame = $parser->getPreprocessor()->newFrame();

			$includeonlyText = $parser->getPreloadText( $templateText, $title, $parserOptions );
			$infoboxes = $this->getInfoboxes( $this->removeNowikiPre( $includeonlyText ) );

			if ( $infoboxes ) {
				foreach ( $infoboxes as $infobox ) {
					try {
						$this->parserTagController->prepareInfobox( $infobox, $parser, $frame );
					} catch ( Exception ) {
						$this->logger->info( 'Invalid infobox syntax' );
					}
				}

				return json_decode(
					self::parserOutputGetPageProperty( $parser->getOutput(), PortableInfoboxDataService::INFOBOXES_PROPERTY_NAME ),
					true
				);
			}
		}
		return false;
	}

	public function reparseArticle( Title $title ) {
		$parser = MediaWikiServices::getInstance()->getParser();
		$user = RequestContext::getMain()->getUser();

		$parserOptions = new ParserOptions( $user );
		$parser->parse( $this->fetchArticleContent( $title ), $title, $parserOptions );

		return json_decode(
			self::parserOutputGetPageProperty( $parser->getOutput(), PortableInfoboxDataService::INFOBOXES_PROPERTY_NAME ),
			true
		);
	}

	private static function parserOutputGetPageProperty( ParserOutput $parserOutput, string $name ): string {
		$property = $parserOutput->getPageProperty( $name );
		if ( is_string( $property ) ) {
			return $property;
		}

		return '';
	}

	/**
	 * @param Title $title
	 * @return string
	 */
	protected function fetchArticleContent( Title $title ): string {
		if ( $title->exists() ) {
			$content = MediaWikiServices::getInstance()->getWikiPageFactory()
				->newFromTitle( $title )->getContent();

			if ( $content instanceof TextContent ) {
				return $content->getText();
			}
		}

		return '';
	}

	/**
	 * @param Title $title
	 * @return string[] array of strings (infobox markups)
	 */
	public function getMarkup( Title $title ) {
		$content = $this->fetchArticleContent( $title );
		return $this->getInfoboxes( $content );
	}

	/**
	 * For given template text returns it without text in <nowiki> and <pre> tags
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	protected function removeNowikiPre( $text ) {
		$text = preg_replace( '/<(nowiki|pre)>.+<\/\g1>/sU', '', $text );

		return $text;
	}

	/**
	 * From the template without <includeonly> tags, creates an array of
	 * strings containing only infoboxes. All template content which is not an infobox is removed.
	 *
	 * @param string $text Content of template which uses the <includeonly> tags
	 *
	 * @return array of striped infoboxes ready to parse
	 */
	protected function getInfoboxes( $text ) {
		preg_match_all( '/<infobox(?:[^>]*\/>|.+<\/infobox>)/sU', $text, $result );
		return $result[0];
	}
}
