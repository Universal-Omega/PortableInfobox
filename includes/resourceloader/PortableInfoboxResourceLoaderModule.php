<?php

class PortableInfoboxResourceLoaderModule extends ResourceLoaderFileModule {
	/** @inheritDoc */
	protected function getLessVars( ResourceLoaderContext $context ) {
		$lessVars = parent::getLessVars( $context );
		$lessVars[ 'responsibly-open-collapsed'] = $this->getConfig()->get( 'PortableInfoboxResponsiblyOpenCollapsed' );
		return $lessVars;
	}
}
