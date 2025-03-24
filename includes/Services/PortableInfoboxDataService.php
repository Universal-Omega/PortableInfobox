<?php

namespace PortableInfobox\Services;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use PortableInfobox\Controllers\PortableInfoboxParserTagController;
use PortableInfobox\Services\Helpers\PagePropsProxy;
use PortableInfobox\Services\Helpers\PortableInfoboxParsingHelper;
use PortableInfobox\Services\Parser\Nodes\NodeInfobox;

class PortableInfoboxDataService {

	private const CACHE_TTL = 86400;
	private const IMAGE_FIELD_TYPE = 'image';
	public const INFOBOXES_PROPERTY_NAME = 'infoboxes';

	protected $title;
	protected $parsingHelper;
	protected $propsProxy;
	protected $cache;
	protected $memcached;
	protected $cachekey;

	/**
	 * @param Title $title
	 *
	 * @internal param $helper
	 */
	protected function __construct( Title $title ) {
		$this->title = $title;
		$this->parsingHelper = new PortableInfoboxParsingHelper();
		$this->propsProxy = new PagePropsProxy();
		$this->memcached = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$this->cachekey = $this->memcached->makeKey(
			__CLASS__,
			$this->title->getArticleID(),
			self::INFOBOXES_PROPERTY_NAME,
			PortableInfoboxParserTagController::PARSER_TAG_VERSION
		);
	}

	public static function newFromTitle( $title ) {
		return new self( $title );
	}

	public static function newFromPageID( $pageid ) {
		return new self( Title::newFromID( $pageid ) );
	}

	// set internal helpers methods
	public function setParsingHelper( $helper ) {
		$this->parsingHelper = $helper;

		return $this;
	}

	public function setPagePropsProxy( $proxy ) {
		$this->propsProxy = $proxy;

		return $this;
	}

	/**
	 * Returns infobox data, chain terminator method
	 *
	 * @return array in format [ [ 'data' => [], 'metadata' => [] ] or [] will be returned
	 */
	public function getData() {
		$result = $this->get();

		return $result ?: [];
	}

	/**
	 * @return array of strings (infobox markups)
	 */
	public function getInfoboxes() {
		return $this->parsingHelper->getMarkup( $this->title );
	}

	/**
	 * Get image list from multiple infoboxes data
	 *
	 * @return array
	 */
	public function getImages() {
		$images = [];
		foreach ( $this->getData() as $infobox ) {
			if ( is_array( $infobox['data'] ) ) {
				$images = array_merge( $images, $this->getImageFromOneInfoboxData( $infobox['data'] ) );
			}
		}
		return array_unique( $images );
	}

	/**
	 * Get image list from single infobox data
	 *
	 * @return array
	 */
	private function getImageFromOneInfoboxData( $infoboxData ) {
		$images = [];
		foreach ( $infoboxData as $field ) {
			if ( $field['type'] === self::IMAGE_FIELD_TYPE && isset( $field['data'] ) ) {
				$images = array_merge( $images, $this->getImagesFromOneNodeImageData( $field['data'] ) );
			}
		}
		return $images;
	}

	/**
	 * Get image list from single NodeImage data
	 *
	 * @return array
	 */
	private function getImagesFromOneNodeImageData( $nodeImageData ) {
		$images = [];
		foreach ( $nodeImageData as $image ) {
			if ( !empty( $image['key'] ) ) {
				$images[] = $image['key'];
			}
		}
		return $images;
	}

	/**
	 * Save infobox data, permanently
	 * NOTICE: This method isn't currently used anywhere
	 *
	 * @param NodeInfobox $raw infobox parser output
	 *
	 * @return $this
	 */
	public function save( NodeInfobox $raw ) {
		$stored = $this->get();
		$stored[] = [
			'parser_tag_version' => PortableInfoboxParserTagController::PARSER_TAG_VERSION,
			'data' => $raw->getRenderData(),
			'metadata' => $raw->getMetadata()
		];

		$this->set( $stored );

		return $this;
	}

	/**
	 * Remove infobox data from page props and memcache
	 */
	public function delete() {
		$this->clear();
		$this->cache = null;

		return $this;
	}

	/**
	 * Purge mem cache and local cache
	 */
	public function purge() {
		$this->memcached->delete( $this->cachekey );
		$this->cache = null;

		return $this;
	}

	// soft cache handlers
	protected function get() {
		if ( !$this->cache ) {
			$this->cache = $this->load();
		}

		return $this->cache;
	}

	protected function set( $data ) {
		$this->store( $data );
		$this->cache = $data;
	}

	// PageProps handlers with memcache wrappers
	protected function load() {
		$id = $this->title->getArticleID();
		if ( $id ) {
			return $this->memcached->getWithSetCallback(
				$this->cachekey,
				self::CACHE_TTL,
				function () use ( $id ) {
					return $this->reparseArticleIfNeeded(
						json_decode( $this->propsProxy->get( $id, self::INFOBOXES_PROPERTY_NAME ), true )
					);
				}
			);
		}

		return [];
	}

	public function reparseArticle() {
		if ( $this->title->inNamespace( NS_TEMPLATE ) ) {
			$result = $this->parsingHelper->parseIncludeonlyInfoboxes( $this->title );
		} else {
			$result = $this->parsingHelper->reparseArticle( $this->title );
		}

		if ( $result ) {
			$this->set( $result );
		} else {
			$this->delete();
		}

		return $result;
	}

	/**
	 * If PageProps has an old version of infobox data/metadata then reparse the page
	 * and store fresh data. If it doesn't have infoboxes property,
	 * we treat it as a page without infoboxes - there might be false negatives
	 *
	 * @param array $infoboxes
	 *
	 * @return array
	 */
	protected function reparseArticleIfNeeded( $infoboxes ) {
		if ( is_array( $infoboxes ) ) {
			foreach ( $infoboxes as $infobox ) {
				if (
					empty( $infobox ) ||
					!isset( $infobox['parser_tag_version'] ) ||
					$infobox['parser_tag_version'] !== PortableInfoboxParserTagController::PARSER_TAG_VERSION
				) {
					$infoboxes = $this->reparseArticle();
					break;
				}
			}
		}

		return $infoboxes;
	}

	protected function store( $data ) {
		$id = $this->title->getArticleID();
		if ( $id ) {
			$this->memcached->set( $this->cachekey, $data, self::CACHE_TTL );
			$this->propsProxy->set( $id, [ self::INFOBOXES_PROPERTY_NAME => json_encode( $data ) ] );
		}
	}

	protected function clear() {
		$id = $this->title->getArticleID();
		if ( $id ) {
			$this->propsProxy->set( $id, [ self::INFOBOXES_PROPERTY_NAME => '' ] );
			// don't cache clear state
			$this->purge();
		}
	}
}
