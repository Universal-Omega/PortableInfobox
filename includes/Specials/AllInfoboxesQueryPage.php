<?php

namespace PortableInfobox\Specials;

use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\PageQueryPage;

class AllInfoboxesQueryPage extends PageQueryPage {

	private const ALL_INFOBOXES_TYPE = 'AllInfoboxes';

	public function __construct() {
		parent::__construct( self::ALL_INFOBOXES_TYPE );
	}

	public function getQueryInfo() {
		$query = [
			'tables' => [ 'page', 'page_props' ],
			'fields' => [
				'namespace' => 'page_namespace',
				'title' => 'page_title',
				'value' => 'page_id',
				'infoboxes' => 'pp_value'
			],
			'conds' => [
				'page_is_redirect' => 0,
				'page_namespace' => NS_TEMPLATE,
				'pp_value IS NOT NULL',
				'pp_value != \'\''
			],
			'join_conds' => [
				'page_props' => [
					'INNER JOIN',
					'page_id = pp_page AND pp_propname = "infoboxes"'
				]
			]
		];

		$dbr = $this->getDatabaseProvider()->getReplicaDatabase();

		$excludedSubpages = $this->getConfig()->get( 'AllInfoboxesExcludedSubpages' );
		foreach ( $excludedSubpages as $subpage ) {
			$query['conds'][] = 'page_title NOT ' . $dbr->buildLike( "/{$subpage}" );
		}

		return $query;
	}

	/**
	 * Update the querycache table
	 *
	 * @see QueryPage::recache
	 *
	 * @param int|false $limit Limit for SQL statement or false for no limit
	 * @param bool $obsolete @phan-unused-param
	 *
	 * @return int number of rows updated
	 */
	public function recache( $limit = false, $obsolete = true ) {
		$res = parent::recache( $limit );

		$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		$hookContainer->run( 'AllInfoboxesQueryRecached' );

		return $res;
	}

	public function isExpensive() {
		return true;
	}

	protected function getOrderFields() {
		return [ 'title' ];
	}

	protected function getCacheOrderFields() {
		return $this->getOrderFields();
	}

	protected function sortDescending() {
		return false;
	}

	protected function getGroupName() {
		return 'pages';
	}
}
