<?php

use MediaWiki\MediaWikiServices;

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

		$dbr = $this->getDBLoadBalancer()->getConnection( DB_REPLICA );

		$subpagesBlacklist = $this->getConfig()->get( 'AllInfoboxesSubpagesBlacklist' );
		foreach ( $subpagesBlacklist as $subpage ) {
			$query['conds'][] = 'page_title NOT ' . $dbr->buildLike( "/{$subpage}" );
		}

		return $query;
	}

	/**
	 * Update the querycache table
	 *
	 * @see QueryPage::recache
	 *
	 * @param bool $limit Limit for SQL statement
	 * @param bool $ignoreErrors Whether to ignore database errors
	 *
	 * @return int number of rows updated
	 */
	public function recache( $limit = false, $ignoreErrors = true ) {
		$res = parent::recache( $limit, $ignoreErrors );

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
