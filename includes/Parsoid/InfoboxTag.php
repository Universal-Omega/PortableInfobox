<?php

namespace PortableInfobox\Parsoid;

use Wikimedia\Parsoid\Core\ContentMetadataCollectorStringSets as CMCSS;
use Wikimedia\Parsoid\Ext\ExtensionModule;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

class InfoboxTag extends ExtensionTagHandler implements ExtensionModule {

	/**
	 * @inheritDoc
	 */
	public function getConfig(): array {
		return [
			'name' => 'PortableInfobox',
			'tags' => [
				[
					'name' => 'infobox',
					'handler' => self::class,
				],
			],
			'domProcessors' => [
				'PortableInfobox\\Parsoid\\PortableInfoboxDOMProcessor',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function sourceToDom( ParsoidExtensionAPI $api, string $src, array $args ) {
		$domFragments = $api->extTagToDOM( $args, $src, [
			'wrapperTag' => 'aside',
			'parseOpts' => [
				'extTag' => 'infobox',
				'context' => 'inline',
			],
		] );

		$api->getMetadata()->appendOutputStrings( CMCSS::MODULE_STYLE, [ 'ext.PortableInfobox.styles' ] );
		$api->getMetadata()->appendOutputStrings( CMCSS::MODULE, [ 'ext.PortableInfobox.scripts' ] );

		// return this back. At this point, we have constructed the outer tag (<aside class=...</aside>)
		// and this function is done with its work. The rest of the work will happen in the DOMProcessor
		return $domFragments;
	}
}
