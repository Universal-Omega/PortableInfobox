<?php

namespace PortableInfobox\Services\Parser\Nodes;

use PortableInfobox\Services\Helpers\PortableInfoboxImagesHelper;

class ParsoidMediaNode extends Node {

    private ?PortableInfoboxImagesHelper $helper;

    /**
     * Return the data for the image
     * return array
     */
    public function getData(): array {
		if ( !isset( $this->data ) ) {
			$this->data = [];

			// value passed to source parameter (or default)
			$value = $this->getRawValueWithDefault( $this->xmlNode );
            if ( $this->containsTabberOrGallery( $value ) ) {
				$this->data = $this->getImagesData( $value );
			} else {
				// $this->data = [ $this->getImageData(
				// 	$value,
				// 	$this->getValueWithDefault( $this->xmlNode->{self::ALT_TAG_NAME} ),
				// 	$this->getValueWithDefault( $this->xmlNode->{self::CAPTION_TAG_NAME} )
				// ) ];
			}
        }
		return $this->data;
	}

    /**
     * Checks if string contains raw <gallery> or <tabber> tags using a hacky regex. With the legacy Parser,
     * by the time this function runs in NodeMedia::class, the intial parse has already replaced the $str with a strip marker
     * we are not in such environment and we will receieve the raw wikitext passed by the user
     * @param string $value wikitext passed in the parameter
     * @return bool
     */
    private function containsTabberOrGallery( string $value ): bool {
        // <gallery></gallery>
        if ( preg_match( '/<gallery\b[^>]*>/i', $value ?? '' ) ) {
            return true;
        }
        
        // <tabber></tabber>
        if ( preg_match( '/<tabber\b[^>]*>/i', $value ?? '' ) ) {
            return true;
        }
        
        return false;
    }

    /**
     * Get the data about the image (or images if tabber/gallery) and return it as an array
     * @TODO: revisit this later, see comment on ParsoidMediaWikiParser::extractGallery for why this
     * is a bad idea - but it works
     * @param string $value the wikitext gallery
     * @param mixed $value
     */
    private function getImagesData( string $value ) {
		$helper = $this->getImageHelper();
		$data = [];
        $parser = $this->getExternalParser();
        $images = $parser->extractGallery( $value );
		
        // no-op right now
        return [];
	}

    /**
     * Get an instance of the PortableInfoboxImageHelper
     * @return PortableInfoboxImagesHelper
     */
    protected function getImageHelper() {
		if ( !isset( $this->helper ) ) {
			$this->helper = new PortableInfoboxImagesHelper();
		}
		return $this->helper;
	}
}