<?php

namespace PortableInfobox\Services\Parser;

interface ExternalParser {

	public function parseRecursive( $text );

	public function replaceVariables( $text );

	public function addImage( $title, array $sizeParams ): ?string;
}
