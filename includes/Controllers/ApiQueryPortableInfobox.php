<?php

namespace PortableInfobox\Controllers;

use MediaWiki\Api\ApiPageSet;
use MediaWiki\Api\ApiQueryBase;
use MediaWiki\Api\ApiResult;
use MediaWiki\Title\Title;
use PortableInfobox\Services\Helpers\PagePropsProxy;
use PortableInfobox\Services\PortableInfoboxDataService;

class ApiQueryPortableInfobox extends ApiQueryBase {

	protected $propsProxy;

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'ib' );
		$this->propsProxy = new PagePropsProxy( true );
	}

	public function execute() {
		$this->runOnPageSet( $this->getPageSet() );
	}

	protected function runOnPageSet( ApiPageSet $pageSet ) {
		$articles = $pageSet->getGoodPages();
		$res = $pageSet->getResult();

		foreach ( $articles as $id => $articleTitle ) {
			$title = Title::castFromPageIdentity( $articleTitle );

			if ( $title === null ) {
				continue;
			}

			$parsedInfoboxes = PortableInfoboxDataService::newFromTitle( $title )
				->setPagePropsProxy( $this->propsProxy )
				->getData();

			if ( is_array( $parsedInfoboxes ) && count( $parsedInfoboxes ) ) {
				$inf = [];
				foreach ( array_keys( $parsedInfoboxes ) as $k => $_ ) {
					$inf[$k] = [];
				}

				$res->setIndexedTagName( $inf, 'infobox' );
				$res->addValue( [ 'query', 'pages', $id ], 'infoboxes', $inf );

				foreach ( $parsedInfoboxes as $count => $infobox ) {
					$res->addValue( [ 'query', 'pages', $id, 'infoboxes', $count ], 'id', $count );
					$res->addValue(
						[ 'query', 'pages', $id, 'infoboxes', $count ],
						'parser_tag_version',
						$infobox['parser_tag_version']
					);

					$metadata = $infobox['metadata'];

					$res->addValue(
						[ 'query', 'pages', $id, 'infoboxes', $count ], 'metadata', $metadata
					);
					$res->addIndexedTagName(
						[ 'query', 'pages', $id, 'infoboxes', $count, 'metadata' ],
						'metadata'
					);
					$this->setIndexedTagNamesForGroupMetadata(
						$metadata,
						[ 'query', 'pages', $id, 'infoboxes', $count, 'metadata' ],
						$res
					);
				}
			}
		}

		$this->propsProxy->write();
	}

	/**
	 * XML format requires all indexed arrays to have _element defined
	 * This method adds it recursively for all groups
	 *
	 * @param array $metadata
	 * @param array $rootPath
	 * @param ApiResult $result
	 */
	private function setIndexedTagNamesForGroupMetadata(
		array $metadata, array $rootPath, ApiResult $result
	) {
		foreach ( $metadata as $nodeCount => $node ) {
			if ( $node['type'] === 'group' ) {
				$path = array_merge( $rootPath, [ $nodeCount, 'metadata' ] );
				$result->addIndexedTagName( $path, 'metadata' );
				$this->setIndexedTagNamesForGroupMetadata( $node['metadata'], $path, $result );
			}
		}
	}
}
