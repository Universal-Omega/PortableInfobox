<?php

namespace PortableInfobox\Parsoid; 

use PortableInfobox\Services\Parser\ExternalParser;
use ReflectionClass;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Fragments\WikitextPFragment;
use Wikimedia\Parsoid\Utils\DOMCompat;

class ParsoidMediaWikiParser implements ExternalParser {

    public ParsoidExtensionAPI $api;

    public function __construct(
        ParsoidExtensionAPI $api
    ) {
        $this->api = $api;
    }

    public function parseRecursive( $wikitext ) 
    {
        if ( $wikitext === null ) {
            return null; 
        }

        $paramParsed = $this->api->wikitextToDOM( $wikitext, [
			// this differs from earlier as we need the frame to be able to grab the 
            // params the user passed - parsoid handles this internally it appears
            'processInNewFrame' => false,
			'parseOpts' => [ 'context' => 'inline' ]
		], true );

        // we don't want Parsoid to wrap in a span or add a typeof here, 
        // just interested in the content
        return DOMCompat::getOuterHTML( $paramParsed );
    }

    public function replaceVariables( $wikitext ) {
		// no-op - I think handled by ->wikiTextToDOM? 
	}

    public function addImage($title, array $sizeParams): ?string
    {
        // no-op at present. Used to extend the image tag with specific information for the PageImages extension. 
        // that extension relies on Parser hooks and therefore is not safe to assume that will work indefinitely.
        // could potentially just do it for now whilst the hooks still exist, and maybe remove at a later date if
        // PageImages is not made Parsoid-compat. 
        return '';
    }

    public function getParsoidExtensionApi(): ParsoidExtensionAPI {
        return $this->api;
    }

    /**
     * Extract the gallery and return the filename -> captions. PortableInfobox currently does this
     * a lot cleaner as it piggybacks on onAfterParserFetchFileAndTitle hook to set the images into the data bag.
     * This hook is NOT available on Parsoid, and we have no other way to get the resultant class which PortableInfobox
     * currently relies on. So we need to fake it as best we can and hope WMF comes up with something later down
     * the line.
     * @param mixed $wikitext
     * @return array an array of filenames -> captions
     */
    public function extractGallery( $wikitext ): array 
    {
        if ( $wikitext === null ) {
            return [];
        }

        // no-op at present
        return [];
    }
}