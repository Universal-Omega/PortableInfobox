<?php

namespace PortableInfobox\Services\Parser;

use MediaWiki\Title\Title;

class SimpleParser implements ExternalParser {

	public function parseRecursive( $text ) {
		return $text;
	}

	public function replaceVariables( $text ) {
		return $text;
	}

	/**
	 * @param Title $title @phan-unused-param
	 * @param array $sizeParams @phan-unused-param
	 */
	public function addImage( $title, array $sizeParams ): ?string {
		// do nothing
		return null;
	}
}
