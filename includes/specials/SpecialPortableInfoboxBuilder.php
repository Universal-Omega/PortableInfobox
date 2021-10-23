<?php

class SpecialPortableInfoboxBuilder extends SpecialPage {
	function __construct() {
		parent::__construct( 'InfoboxBuilder' );
		$this->mRestriction = $wgNamespaceProtection[NS_TEMPLATE];
	}

	function execute( $par ) {
		$output = $this->getOutput();
		$this->setHeaders();
		$output->enableOOUI();

		if ( $wgNamespaceProtection[NS_TEMPLATE] ) {}

		$output->addModules( [ 'ext.PortableInfobox.styles', 'ext.PortableInfoboxBuilder' ] );
		$output->addHTML(
			'<div id="mw-infoboxbuilder" data-title="' . str_replace( '"', '&quot;', $par ) . '">' .
				new OOUI\ProgressBarWidget( [ 'progress' => false ] ) .
			'</div>'
		);
	}
}
