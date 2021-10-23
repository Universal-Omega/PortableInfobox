<?php

class SpecialPortableInfoboxBuilder extends SpecialPage {
	public function __construct() {
		global $wgNamespaceProtection;

		parent::__construct( 'InfoboxBuilder' );
		$this->mRestriction = $wgNamespaceProtection[NS_TEMPLATE];
	}

	public function execute( $par ) {
		global $wgNamespaceProtection;

		$out = $this->getOutput();

		$this->setHeaders();
		$out->enableOOUI();

		if ( $wgNamespaceProtection[NS_TEMPLATE] ) {}

		$out->addModules( [ 'ext.PortableInfobox.styles', 'ext.PortableInfoboxBuilder' ] );
		$out->addHTML(
			'<div id="mw-infoboxbuilder" data-title="' . str_replace( '"', '&quot;', $par ) . '">' .
				new OOUI\ProgressBarWidget( [ 'progress' => false ] ) .
			'</div>'
		);
	}
}
