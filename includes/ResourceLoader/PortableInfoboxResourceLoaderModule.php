<?php

namespace PortableInfobox\ResourceLoader;

use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\FileModule;

class PortableInfoboxResourceLoaderModule extends FileModule {

	/** @inheritDoc */
	protected function getLessVars( Context $context ) {
		$lessVars = parent::getLessVars( $context );
		$lessVars[ 'responsibly-open-collapsed'] =
			(bool)$this->getConfig()->get( 'PortableInfoboxResponsiblyOpenCollapsed' );
		return $lessVars;
	}
}
