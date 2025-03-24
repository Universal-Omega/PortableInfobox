<?php

namespace PortableInfobox\Services\Parser;

class SimpleParser implements ExternalParser {
	public function parseRecursive( $text ) {
		return $text;
	}

	public function replaceVariables( $text ) {
		return $text;
	}

	public function addImage( $title ): ?string {
		// do nothing
		return null;
	}
}
