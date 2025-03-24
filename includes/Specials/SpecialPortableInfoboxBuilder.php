<?php

namespace PortableInfobox\Specials;

use MediaWiki\SpecialPage\SpecialPage;
use OOUI\ProgressBarWidget;

class SpecialPortableInfoboxBuilder extends SpecialPage {

	public function __construct() {
		$restriction = $this->getConfig()->get( 'NamespaceProtection' )[NS_TEMPLATE][0] ?? '';
		parent::__construct( 'PortableInfoboxBuilder', $restriction );
	}

	public function execute( $par ) {
		$out = $this->getOutput();

		$this->setHeaders();
		$out->enableOOUI();

		$this->checkPermissions();

		$out->addModules( [ 'ext.PortableInfobox.styles', 'ext.PortableInfoboxBuilder' ] );
		$out->addHTML(
			'<div id="mw-infoboxbuilder" data-title="' . str_replace( '"', '&quot;', $par ?? '' ) . '">' .
				new ProgressBarWidget( [ 'progress' => false ] ) .
			'</div>'
		);
	}
}
