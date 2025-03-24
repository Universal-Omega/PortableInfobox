<?php

namespace PortableInfobox\Controllers;

use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Parser\Sanitizer;
use PortableInfobox\Services\Helpers\InfoboxParamsValidator;
use PortableInfobox\Services\Helpers\InvalidInfoboxParamsException;
use PortableInfobox\Services\Parser\MediaWikiParserService;
use PortableInfobox\Services\Parser\Nodes\NodeFactory;
use PortableInfobox\Services\Parser\Nodes\NodeInfobox;
use PortableInfobox\Services\Parser\Nodes\UnimplementedNodeException;
use PortableInfobox\Services\Parser\XmlMarkupParseErrorException;
use PortableInfobox\Services\PortableInfoboxDataService;
use PortableInfobox\Services\PortableInfoboxErrorRenderService;
use PortableInfobox\Services\PortableInfoboxRenderService;

class PortableInfoboxParserTagController {
	public const PARSER_TAG_VERSION = 2;
	public const DEFAULT_THEME_NAME = 'default';
	public const INFOBOX_THEME_PREFIX = 'pi-theme-';

	private const PARSER_TAG_NAME = 'infobox';
	private const DEFAULT_LAYOUT_NAME = 'default';
	private const INFOBOX_LAYOUT_PREFIX = 'pi-layout-';
	private const INFOBOX_TYPE_PREFIX = 'pi-type-';
	private const ACCENT_COLOR = 'accent-color';
	private const ACCENT_COLOR_TEXT = 'accent-color-text';
	private const ERR_UNIMPLEMENTEDNODE = 'portable-infobox-unimplemented-infobox-tag';
	private const ERR_UNSUPPORTEDATTR = 'portable-infobox-xml-parse-error-infobox-tag-attribute-unsupported';

	private $infoboxParamsValidator = null;

	protected static $instance;

	/**
	 * @return self
	 */
	public static function getInstance() {
		if ( !isset( static::$instance ) ) {
			static::$instance = new self();
		}

		return static::$instance;
	}

	/**
	 * Parser hook: used to register parser tag in MW
	 *
	 * @param Parser $parser
	 *
	 * @return bool
	 */
	public static function parserTagInit( Parser $parser ) {
		$parser->setHook( self::PARSER_TAG_NAME, [ static::getInstance(), 'renderInfobox' ] );

		return true;
	}

	/**
	 * @param string $markup
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array|null $params
	 *
	 * @return string
	 * @throws UnimplementedNodeException when node used in markup does not exists
	 * @throws XmlMarkupParseErrorException xml not well formatted
	 * @throws InvalidInfoboxParamsException when unsupported attributes exist in params array
	 */
	public function render( $markup, Parser $parser, PPFrame $frame, $params = null ) {
		$data = $this->prepareInfobox( $markup, $parser, $frame, $params );

		$themeList = $this->getThemes( $params, $frame );
		$layout = $this->getLayout( $params );
		$accentColor = $this->getColor( self::ACCENT_COLOR, $params, $frame );
		$accentColorText = $this->getColor( self::ACCENT_COLOR_TEXT, $params, $frame );
		$type = $this->getType( $params );
		$itemName = $this->getItemName( $params );

		$renderService = new PortableInfoboxRenderService();
		return $renderService->renderInfobox(
			$data, implode( ' ', $themeList ), $layout, $accentColor, $accentColorText, $type, $itemName
		);
	}

	/**
	 * @param string $markup
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array|null $params
	 *
	 * @return array
	 * @throws UnimplementedNodeException when node used in markup does not exists
	 * @throws XmlMarkupParseErrorException xml not well formatted
	 * @throws InvalidInfoboxParamsException when unsupported attributes exist in params array
	 */
	public function prepareInfobox( $markup, Parser $parser, PPFrame $frame, $params = null ) {
		$frameArguments = $frame->getArguments();
		$infoboxNode = NodeFactory::newFromXML( $markup, $frameArguments ?: [] );
		$infoboxNode->setExternalParser(
			new MediaWikiParserService( $parser, $frame )
		);

		// get params if not overridden
		if ( !isset( $params ) ) {
			$params = ( $infoboxNode instanceof NodeInfobox ) ? $infoboxNode->getParams() : [];
		}

		$this->getParamsValidator()->validateParams( $params );

		$data = $infoboxNode->getRenderData();
		// save for later api usage
		$this->saveToParserOutput( $parser->getOutput(), $infoboxNode );

		return $data;
	}

	/**
	 * Renders Infobox
	 *
	 * @param string $text
	 * @param Array $params
	 * @param Parser $parser
	 * @param PPFrame $frame
	 *
	 * @return string|array
	 */
	public function renderInfobox( $text, $params, $parser, $frame ) {
		$markup = '<' . self::PARSER_TAG_NAME . '>' . $text . '</' . self::PARSER_TAG_NAME . '>';
		$parserOutput = $parser->getOutput();

		$parserOutput->addModuleStyles( [ 'ext.PortableInfobox.styles' ] );
		$parserOutput->addModules( [ 'ext.PortableInfobox.scripts' ] );

		try {
			$renderedValue = $this->render( $markup, $parser, $frame, $params );
		} catch ( UnimplementedNodeException $e ) {
			return $this->handleError(
				wfMessage( self::ERR_UNIMPLEMENTEDNODE, [ $e->getMessage() ] )->escaped()
			);
		} catch ( XmlMarkupParseErrorException $e ) {
			return $this->handleXmlParseError( $e->getErrors(), $text );
		} catch ( InvalidInfoboxParamsException $e ) {
			return $this->handleError(
				wfMessage( self::ERR_UNSUPPORTEDATTR, [ $e->getMessage() ] )->escaped()
			);
		}

		// Convert text to language variant
		$renderedValue = MediaWikiServices::getInstance()
			->getLanguageConverterFactory()
			->getLanguageConverter( $parser->getTargetLanguage() )
			->convert( $renderedValue );

		return [ $renderedValue, 'markerType' => 'nowiki' ];
	}

	protected function saveToParserOutput( ParserOutput $parserOutput, NodeInfobox $raw ) {
		// parser output stores this in page_props table,
		// therefore we can reuse the data in data provider service
		// (see: PortableInfoboxDataService.class.php)

		$infoboxes = json_decode(
			self::parserOutputGetPageProperty( $parserOutput, PortableInfoboxDataService::INFOBOXES_PROPERTY_NAME ) ?? '',
			true
		);

		// When you modify this structure, remember to bump the version
		// Version is checked in PortableInfoboxDataService::load()
		$infoboxes[] = [
			'parser_tag_version' => self::PARSER_TAG_VERSION,
			'data' => $raw->getRenderData(),
			'metadata' => $raw->getMetadata()
		];

		self::parserOutputSetPageProperty(
			$parserOutput,
			PortableInfoboxDataService::INFOBOXES_PROPERTY_NAME,
			json_encode( $infoboxes )
		);
	}

	private static function parserOutputGetPageProperty( ParserOutput $parserOutput, string $name ) {
		return $parserOutput->getPageProperty( $name );
	}

	private static function parserOutputSetPageProperty( ParserOutput $parserOutput, string $name, $value ) {
		$parserOutput->setPageProperty( $name, $value );
	}

	private function handleError( $message ) {
		$renderedValue = '<strong class="error"> ' . $message . '</strong>';

		return [ $renderedValue, 'markerType' => 'nowiki' ];
	}

	private function handleXmlParseError( $errors, $xmlMarkup ) {
		$errorRenderer = new PortableInfoboxErrorRenderService( $errors );

		$title = RequestContext::getMain()->getTitle();
		if ( $title && $title->getNamespace() == NS_TEMPLATE ) {
			$renderedValue = $errorRenderer->renderMarkupDebugView( $xmlMarkup );
		} else {
			$renderedValue = $errorRenderer->renderArticleMsgView();
		}

		return [ $renderedValue, 'markerType' => 'nowiki' ];
	}

	private function getThemes( $params, PPFrame $frame ) {
		$themes = [];

		if ( isset( $params['theme'] ) ) {
			$staticTheme = trim( $params['theme'] );
			if ( !empty( $staticTheme ) ) {
				$themes[] = $staticTheme;
			}
		}
		if ( !empty( $params['theme-source'] ) ) {
			$variableTheme = trim( $frame->getArgument( $params['theme-source'] ) );
			if ( !empty( $variableTheme ) ) {
				$themes[] = $variableTheme;
			}
		}

		// use default global theme if not present
		$themes = !empty( $themes ) ? $themes : [ self::DEFAULT_THEME_NAME ];

		return array_map( static function ( $name ) {
			return Sanitizer::escapeClass(
				self::INFOBOX_THEME_PREFIX . preg_replace( '|\s+|s', '-', $name )
			);
		}, $themes );
	}

	private function getLayout( $params ) {
		$layoutName = $params['layout'] ?? '';
		if ( $this->getParamsValidator()->validateLayout( $layoutName ) ) {
			// make sure no whitespaces, prevents side effects
			return self::INFOBOX_LAYOUT_PREFIX . $layoutName;
		}

		return self::INFOBOX_LAYOUT_PREFIX . self::DEFAULT_LAYOUT_NAME;
	}

	private function getColor( $colorParam, $params, PPFrame $frame ) {
		$sourceParam = $colorParam . '-source';
		$defaultParam = $colorParam . '-default';

		$color = '';

		if ( isset( $params[$sourceParam] ) && !empty( $frame->getArgument( $params[$sourceParam] ) ) ) {
			$color = trim( $frame->getArgument( $params[$sourceParam] ) );
			$color = $this->sanitizeColor( $color );
		}

		if ( empty( $color ) && isset( $params[$defaultParam] ) ) {
			$color = trim( $params[$defaultParam] );
			$color = $this->sanitizeColor( $color );
		}

		return $color;
	}

	private function getType( $params ) {
		return !empty( $params['type'] ) ? Sanitizer::escapeClass(
				self::INFOBOX_TYPE_PREFIX . preg_replace( '|\s+|s', '-', $params['type'] )
			) : '';
	}

	private function getItemName( $params ) {
		return !empty( $params['name'] ) ? Sanitizer::encodeAttribute( $params['name'] ) : '';
	}

	private function sanitizeColor( $color ) {
		return $this->getParamsValidator()->validateColorValue( $color );
	}

	private function getParamsValidator() {
		if ( empty( $this->infoboxParamsValidator ) ) {
			$this->infoboxParamsValidator = new InfoboxParamsValidator();
		}

		return $this->infoboxParamsValidator;
	}
}
