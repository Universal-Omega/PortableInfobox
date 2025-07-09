<?php

namespace PortableInfobox\Services\Parser;

class SimpleParser implements ExternalParser {

	public function parseRecursive( $text ) {
		return $text;
	}

	public function replaceVariables( $text ) {
		return $text;
	}

	public function addImage( $title, array $sizeParams ): ?string {
		// do nothing
		return null;
	}
}
