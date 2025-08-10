<?php

namespace PortableInfobox\Parsoid; 

use PortableInfobox\Services\Parser\ExternalParser;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Utils\DOMCompat;

class ParsoidMediaWikiParser implements ExternalParser {

    private ParsoidExtensionAPI $api;

    public function __construct(
        ParsoidExtensionAPI $api
    ) {
        $this->api = $api;
    }

    public function parseRecursive( $wikitext ) 
    {
        $paramParsed = $this->api->wikitextToDOM( $wikitext, [
			// this differs from earlier as we need the frame to be able to grab the 
            // params the user passed - parsoid handles this internally it appears
            'processInNewFrame' => false,
			'parseOpts' => [ 'context' => 'inline' ]
		], true );

        // we don't want Parsoid to wrap in a span or add a typeof here, 
        // just interested in the content
        $res = DOMCompat::getOuterHTML( $paramParsed );
    }

    public function replaceVariables( $wikitext ) {
		// no-op - I think handled by ->wikiTextToDOM? 
	}

    public function addImage($title, array $sizeParams): ?string
    {
        // no-op 
        return '';
    }
}