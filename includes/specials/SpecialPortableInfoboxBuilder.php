<?php

class SpecialPortableInfoboxBuilder extends SpecialPage {
	public function __construct() {
		parent::__construct( 'InfoboxBuilder' );

		$this->mRestriction = $this->getConfig()->get( 'NamespaceProtection' )[NS_TEMPLATE];
	}

	public function execute( $par ) {
		$out = $this->getOutput();

		$this->setHeaders();
		$out->enableOOUI();

		if ( $this->getConfig()->get( 'NamespaceProtection' )[NS_TEMPLATE] ) {}

		$out->addModules( [ 'ext.PortableInfobox.styles', 'ext.PortableInfoboxBuilder' ] );
		$out->addHTML(
			'<div id="mw-infoboxbuilder" data-title="' . str_replace( '"', '&quot;', $par ) . '">' .
				new OOUI\ProgressBarWidget( [ 'progress' => false ] ) .
			'</div>'
		);
	}
}
