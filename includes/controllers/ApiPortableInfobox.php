<?php

class ApiPortableInfobox extends ApiBase {

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

	public function execute() {
		$text = $this->getParameter( "text" );
		$title = $this->getParameter( "title" );
		$arguments = $this->getFrameArguments();
		if ( $arguments === null ) {
			$this->addWarning( 'apiwarn-infobox-invalidargs' );
		}

		$parser = MediaWikiServices::getInstance()->getParser();
		$parser->firstCallInit();
		$parser->startExternalParse(
			Title::newFromText( $title ),
			ParserOptions::newFromContext( $this->getContext() ),
			Parser::OT_HTML,
			true
		);

		if ( is_array( $arguments ) ) {
			foreach ( $arguments as $key => &$value ) {
				$value = $parser->replaceVariables( $value );
			}
		}

		$frame = $parser->getPreprocessor()->newCustomFrame( is_array( $arguments ) ? $arguments : [] );

		try {
			$output = PortableInfoboxParserTagController::getInstance()->render( $text, $parser, $frame );
			$this->getResult()->addValue( null, $this->getModuleName(), [ 'text' => [ '*' => $output ] ] );
		} catch ( \PortableInfobox\Parser\Nodes\UnimplementedNodeException $e ) {
			$this->dieUsage(
				wfMessage( 'portable-infobox-unimplemented-infobox-tag', [ $e->getMessage() ] )->escaped(),
				'notimplemented'
			);
		} catch ( \PortableInfobox\Parser\XmlMarkupParseErrorException $e ) {
			$this->dieUsage( wfMessage( 'portable-infobox-xml-parse-error' )->text(), 'badxml' );
		} catch ( \PortableInfobox\Helpers\InvalidInfoboxParamsException $e ) {
			$this->dieUsage(
				wfMessage(
					'portable-infobox-xml-parse-error-infobox-tag-attribute-unsupported',
					[ $e->getMessage() ]
				)->escaped(),
				'invalidparams'
			);
		}
	}

	public function getAllowedParams() {
		return [
			'text' => [
				ApiBase::PARAM_TYPE => 'string'
			],
			'title' => [
				ApiBase::PARAM_TYPE => 'string'
			],
			'args' => [
				ApiBase::PARAM_TYPE => 'string'
			]
		];
	}

	/**
	 * Examples
	 */
	public function getExamples() {
		return [
			'api.php?action=infobox',
			'api.php?action=infobox&text=<infobox><data><default>{{PAGENAME}}</default></data></infobox>' .
				'&title=Test',
			'api.php?action=infobox&text=<infobox><data source="test" /></infobox>' .
				'&args={"test": "test value"}'
		];
	}

	/**
	 * @return mixed
	 */
	protected function getFrameArguments() {
		$arguments = $this->getParameter( "args" );
		return isset( $arguments ) ? json_decode( $arguments, true ) : false;
	}

}
