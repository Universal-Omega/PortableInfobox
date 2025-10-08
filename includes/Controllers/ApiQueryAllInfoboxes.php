<?php

namespace PortableInfobox\Controllers;

use MediaWiki\Api\ApiQuery;
use MediaWiki\Api\ApiQueryBase;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use PortableInfobox\Specials\AllInfoboxesQueryPage;

class ApiQueryAllInfoboxes extends ApiQueryBase {

	private const CACHE_TTL = 86400;

	public const MCACHE_KEY = 'allinfoboxes-list';

	/**
	 * @param ApiQuery $query
	 * @param string $moduleName
	 */
	public function __construct( ApiQuery $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'api' );
	}

	public function execute() {
		$res = $this->getResult();
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$cachekey = $cache->makeKey( self::MCACHE_KEY );

		$data = $cache->getWithSetCallback( $cachekey, self::CACHE_TTL, function () {
			$out = [];

			$res = ( new AllInfoboxesQueryPage() )->doQuery();
			foreach ( $res as $row ) {
				$out[] = [
					'pageid' => $row->value,
					'title' => $row->title,
					'label' => $this->createLabel( $row->title ),
					'ns' => $row->namespace
				];
			}

			return $out;
		} );

		foreach ( $data as $infobox ) {
			$res->addValue( [ 'query', 'allinfoboxes' ], null, $infobox );
		}
		$res->addIndexedTagName( [ 'query', 'allinfoboxes' ], 'i' );
	}

	/**
	 * As a infobox template label we want to return a nice, clean text, without e.g. '_' signs
	 * @param $text infobox template title
	 * @return string
	 */
	private function createLabel( $text ) {
		$title = Title::newFromText( $text, NS_TEMPLATE );

		if ( $title ) {
			return $title->getText();
		}

		return $text;
	}
}
