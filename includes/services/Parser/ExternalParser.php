<?php

namespace PortableInfobox\Parser;

interface ExternalParser {
	public function parseRecursive( $text );

	public function replaceVariables( $text );

	public function addImage( $title ): ?string;
}
