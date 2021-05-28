<?php

namespace PortableInfobox\Helpers;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

class PortableInfoboxParsingHelper {

	protected $parserTagController;
	protected $logger;

	public function __construct() {
		$this->parserTagController = \PortableInfoboxParserTagController::getInstance();
		$this->logger = LoggerFactory::getInstance( 'PortableInfobox' );
	}

	/**
	 * Try to find out if infobox got "hidden" inside includeonly tag. Parse it if that's the case.
	 *
	 * @param \Title $title
	 *
	 * @return mixed false when no infoboxes found, Array with infoboxes on success
	 */
	public function parseIncludeonlyInfoboxes( $title ) {
		// for templates we need to check for include tags
		$templateText = $this->fetchArticleContent( $title );

		if ( $templateText ) {
			$parser = MediaWikiServices::getInstance()->getParser();
			$parser->setTitle( $title );
			$parserOptions = new \ParserOptions();
			$frame = $parser->getPreprocessor()->newFrame();

			$includeonlyText = $parser->getPreloadText( $templateText, $title, $parserOptions );
			$infoboxes = $this->getInfoboxes( $this->removeNowikiPre( $includeonlyText ) );

			if ( $infoboxes ) {
				foreach ( $infoboxes as $infobox ) {
					try {
						$this->parserTagController->prepareInfobox( $infobox, $parser, $frame );
					} catch ( \Exception $e ) {
						$this->logger->info( 'Invalid infobox syntax' );
					}
				}

				return json_decode(
					$parser->getOutput()->getProperty( \PortableInfoboxDataService::INFOBOXES_PROPERTY_NAME ),
					true
				);
			}
		}
		return false;
	}

	public function reparseArticle( \Title $title ) {
		$parser = new \Parser();
		$parserOptions = new \ParserOptions();
		$parser->parse( $this->fetchArticleContent( $title ), $title, $parserOptions );

		return json_decode(
			$parser->getOutput()->getProperty( \PortableInfoboxDataService::INFOBOXES_PROPERTY_NAME ),
			true
		);
	}

	/**
	 * @param \Title $title
	 *
	 * @return string
	 */
	protected function fetchArticleContent( \Title $title ) {
		if ( $title && $title->exists() ) {
			$content = \WikiPage::factory( $title )
				->getContent( \Revision::FOR_PUBLIC )
				->getNativeData();
		}

		return isset( $content ) && $content ? $content : '';
	}

	/**
	 * @param \Title $title
	 * @return string[] array of strings (infobox markups)
	 */
	public function getMarkup( \Title $title ) {
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
