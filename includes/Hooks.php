<?php

namespace PortableInfobox;

use ImageGalleryBase;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWiki\Revision\RenderedRevision;
use MediaWiki\Title\Title;
use Page;
use PortableInfobox\Controllers\ApiQueryAllInfoboxes;
use PortableInfobox\Services\Helpers\PortableInfoboxDataBag;
use PortableInfobox\Services\PortableInfoboxDataService;
use PortableInfobox\Specials\AllInfoboxesQueryPage;

class Hooks {

	public static function onWgQueryPages( array &$queryPages = [] ) {
		$queryPages[] = [ AllInfoboxesQueryPage::class, 'AllInfoboxes' ];

		return true;
	}

	public static function onAfterParserFetchFileAndTitle(
		Parser $parser, ImageGalleryBase $gallery, string &$html
	) {
		PortableInfoboxDataBag::getInstance()->setGallery(
			// @phan-suppress-next-line PhanDeprecatedProperty
			Parser::MARKER_PREFIX . '-gallery-' . sprintf( '%08X', $parser->mMarkerIndex - 1 ) .
				Parser::MARKER_SUFFIX,
			$gallery
		);

		return true;
	}

	public static function onAllInfoboxesQueryRecached() {
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$cache->delete( $cache->makeKey( ApiQueryAllInfoboxes::MCACHE_KEY ) );

		return true;
	}

	/**
	 * Purge memcache before edit
	 *
	 * @param RenderedRevision $renderedRevision
	 */
	public static function onMultiContentSave( RenderedRevision $renderedRevision ) {
		$articleID = $renderedRevision->getRevision()->getPageId();
		$title = Title::newFromId( $articleID );

		if ( $title ) {
			$dataService = PortableInfoboxDataService::newFromTitle( $title );
			$dataService->delete();

			if ( $title->inNamespace( NS_TEMPLATE ) ) {
				$dataService->reparseArticle();
			}
		}
	}

	/**
	 * Purge memcache, this will not rebuild infobox data
	 *
	 * @param Page $article
	 *
	 * @return bool
	 */
	public static function onArticlePurge( Page $article ) {
		PortableInfoboxDataService::newFromTitle( $article->getTitle() )->purge();

		return true;
	}

	public static function onResourceLoaderRegisterModules( ResourceLoader &$resourceLoader ) {
		global $wgResourceModules;

		if ( isset( $wgResourceModules['ext.templateDataGenerator.data'] ) ) {
			$wgResourceModules['ext.templateDataGenerator.data']['scripts'][] =
				'../PortableInfobox/resources/PortableInfoboxParams.js';

			$resourceLoader->register(
				'ext.templateDataGenerator.data',
				$wgResourceModules['ext.templateDataGenerator.data']
			);
		}

		return true;
	}
}
