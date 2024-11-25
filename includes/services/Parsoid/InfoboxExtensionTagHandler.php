<?php

declare( strict_types=1 );

namespace PortableInfobox\Parsoid;

use Closure;
use PortableInfobox\Parser\Nodes\NodeFactory;
use PortableInfoboxRenderService;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Ext\DiffDOMUtils;
use Wikimedia\Parsoid\Ext\DOMDataUtils;
use Wikimedia\Parsoid\Ext\DOMUtils;
use Wikimedia\Parsoid\Ext\ExtensionModule;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Utils\DOMCompat;

class InfoboxExtensionTagHandler extends ExtensionTagHandler implements ExtensionModule {

	public function getConfig(): array {
		return [
			'name' => 'Infobox',
			'tags' => [
				[
					'name' => 'infobox',
					'handler' => self::class,
					'options' => [
						'wt2html' => [
							'embedsHTMLInAttributes' => true,
							'customizesDataMw' => true,
						],
						'outputHasCoreMwDomSpecMarkup' => true
					],
				]
			],
		];
	}

	/** @inheritDoc */
	public function processAttributeEmbeddedHTML(
		ParsoidExtensionAPI $extApi, Element $elt, Closure $proc
	): void {
		$dmw = DOMDataUtils::getDataMw( $elt );
		if ( isset( $dmw->html ) ) {
			$dmw->html = $proc( $dmw->html );
		}
	}

	/** @inheritDoc */
	public function sourceToDom(
		ParsoidExtensionAPI $extApi, string $content, array $args
	): DocumentFragment {
		$dataMw = $extApi->extTag->getDefaultDataMw();
		$kvArgs = $extApi->extArgsToArray( $args );

		$layout = $kvArgs['layout'] ?? 'default';
		$theme = $kvArgs['theme'] ?? 'default';
		$accentColor = $kvArgs['accent-color'] ?? 'default';
		$accentColorText = $kvArgs['accent-color-text'] ?? 'default';

		// Parse the content and convert it to a DOM fragment
		$domFragment = $extApi->extTagToDOM( [], $content, [
			'parseOpts' => [ 'extTag' => 'infobox' ],
		] );

		$contentNode = DiffDOMUtils::firstNonSepChild( $domFragment );
		if ( $contentNode && DOMCompat::nodeName( $contentNode ) === 'p' &&
			DiffDOMUtils::nextNonSepSibling( $contentNode ) === null &&
			$contentNode instanceof Element &&
			DOMDataUtils::noAttrs( $contentNode )
		   ) {
			DOMUtils::migrateChildren( $contentNode, $domFragment, $contentNode->nextSibling );
			$domFragment->removeChild( $contentNode );
		}

		$infoboxNode = NodeFactory::newFromXML( $content, $kvArgs );
		$data = $infoboxNode->getRenderData();

		$renderService = new PortableInfoboxRenderService();
		$infoboxData = $renderService->renderInfobox(
			$data, $theme, $layout, $accentColor, $accentColorText, '', ''
		);

		$dataMw->html = $extApi->domToHtml( $infoboxData, true );

		$meta = $domFragment->ownerDocument->createElement( 'meta' );
		DOMDataUtils::setDataMw( $meta, $dataMw );

		DOMCompat::replaceChildren( $domFragment, $meta );

		return $domFragment;
	}
}