<?php

class AllinfoboxesQueryPage extends PageQueryPage {
	const ALL_INFOBOXES_TYPE = 'AllInfoboxes';

	function __construct() {
		parent::__construct( self::ALL_INFOBOXES_TYPE );
	}

	function getGroupName() {
		return 'pages';
	}

	public function sortDescending() {
		return false;
	}

	public function isExpensive() {
		return true;
	}

	public function getOrderFields() {
		return [ 'title' ];
	}

	public function getCacheOrderFields() {
		return $this->getOrderFields();
	}

	function getQueryInfo() {
		$query = [
			'tables' => [ 'page', 'page_props' ],
			'fields' => [
				'namespace' => 'page.page_namespace',
				'title' => 'page.page_title',
				'value' => 'page.page_id',
				'infoboxes' => 'page_props.pp_value'
			],
			'conds' => [
				'page.page_is_redirect' => 0,
				'page.page_namespace' => NS_TEMPLATE,
				'page_props.pp_value IS NOT NULL',
				'page_props.pp_value != \'\''
			],
			'join_conds' => [
				'page_props' => [
					'INNER JOIN',
					'page.page_id = page_props.pp_page AND page_props.pp_propname = "infoboxes"'
				]
			]
		];

		$subpagesBlacklist = $this->getConfig( 'AllInfoboxesSubpagesBlacklist' );
		foreach ( $subpagesBlacklist as $subpage ) {
			$query['conds'][] = 'page.page_title NOT LIKE %/' . mysql_real_escape_string( $subpage );
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

		Hooks::run( 'AllInfoboxesQueryRecached' );
		return $res;
	}
}
