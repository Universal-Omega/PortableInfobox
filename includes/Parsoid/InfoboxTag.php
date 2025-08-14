<?php

namespace PortableInfobox\Parsoid;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Ext\DOMUtils;
use Wikimedia\Parsoid\Ext\ExtensionModule;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

class InfoboxTag extends ExtensionTagHandler implements ExtensionModule {

	/**
	 * @inheritDoc
	 */
	public function getConfig(): array
	{
		return [
			'name' => 'PortableInfobox',
			'tags' => [
				[
					'name' => 'infobox',
					'handler' => self::class
				]
			],
			'domProcessors' => [
				'PortableInfobox\\Parsoid\\PortableInfoboxDOMProcessor'
			]
		];
	}

    /**
     * @inheritDoc
     */
    public function sourceToDom( ParsoidExtensionAPI $api, string $src, array $args )
	{
		$domFragments = $api->extTagToDOM( $args, $src, [
			'wrapperTag' => 'aside',
			'parseOpts' => [
				'extTag' => 'infobox',
				'context' => 'inline'
			]
		]);
	

		// this is a bit messed up as these methods are deprecated, but the documentation
		// for the replacement methods doesn't exist or make sense
		// this is commented out at present, as these scripts and styles will be added
		// by the legacy parser (might need it here to stick these in the parser cache also?)
		$api->getMetadata()->addModules( [ 'ext.PortableInfobox.scripts' ] );
		$api->getMetadata()->addModuleStyles( [ 'ext.PortableInfobox.styles' ] );

		// return this back. At this point, we have constructed the outer tag (<aside class=...</aside>)
		// and this function is done with its work. The rest of the work will happen in the DOMProcessor
		return $domFragments;
	}
}