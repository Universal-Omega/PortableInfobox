<?php

namespace PortableInfobox\Parsoid;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Ext\DOMDataUtils;
use Wikimedia\Parsoid\Ext\DOMProcessor;
use Wikimedia\Parsoid\Ext\DOMUtils;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

class PortableInfoboxDOMProcessor extends DOMProcessor {

	/**
	 * Note, this is a WIP at present, see comments throughout.
	 * Will probably need to be revised at a later stage when Parsoid API is more mature
	 * @since 1.0
	 */
	public function wtPostprocess(
		ParsoidExtensionAPI $extApi, Node $node, array $options
	): void {
		$child = $node->firstChild;

		// insipiration taken from Ext:Cite
		// and also <gallery>
		while ( $child !== null ) {
			$nextChild = $child->nextSibling;
			if ( $child instanceof Element ) {
				// we're only interested in PIs in this function
				if ( DOMUtils::hasTypeOf( $child, 'mw:Extension/infobox' ) ) {
					$dataMw = DOMDataUtils::getDataMw( $child );
					$parsoidData = DOMDataUtils::getDataParsoid( $child )->src;

					$parts = $dataMw->parts;

					// remove the existing stuff that is generated on the first pass of Parsoid
					// Note: this is probably a bit of a hacky solution, since we will have already
					// processed the parser tag at this point and ended up with the mangled HTML
					// ideally, we would do all of this in the main parse, but Parsoid does not currently
					// grant us the ability to do that, so just remove whatever gobbldy-guck was generated
					// in the first pass. This is probably a bit of a performance hog, but it will be cached in the
					// parser cache for subsequent reads so is a one-shot-pony until the cache expires.
					while ( $child->firstChild ) {
						$child->removeChild( $child->firstChild );
					}

					$doc = $child->ownerDocument;

					foreach ( $parts as $part ) {
						// add our parts to the params info
						// this will get us the key => value of the parameters that the
						// user passed to the template from the article,
						// ie we might get something like
						// params['name'] = [ 'k' => 'name', 'valueWt' => 'John Doe' ]
						// which is something akin to what PortableInfoboxParserTagController::renderInfobox()
						// expects to be passed, albeit we'll need to fudge it a bit!
						$params = $part->paramInfos ?? [];

						$portableInfoboxRenderService = new ParsoidPortableInfoboxRenderService();

						$portableInfoboxRenderService->renderPI( $extApi, $child, $doc, $params, $parsoidData );
					}

				}
			}
			$child = $nextChild;
		}
	}
}
