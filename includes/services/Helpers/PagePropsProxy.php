<?php

namespace PortableInfobox\Helpers;

class PagePropsProxy {
	protected $atomicStarted;
	protected $manualWrite;

	public function __construct( $manualWrite = false ) {
		$this->manualWrite = $manualWrite;
	}

	public function get( $id, $property ) {
		$dbr = wfGetDB( DB_REPLICA );
		$propValue = $dbr->selectField(
			'page_props',
			'pp_value',
			[
				'pp_page' => $id,
				'pp_propname' => $property
			],
			__METHOD__
		);
		return $propValue;
	}

	public function set( $id, array $props ) {
		$dbw = wfGetDB( DB_MASTER );

		if ( !$this->atomicStarted ) {
			$dbw->startAtomic( __METHOD__ );
			$this->atomicStarted = true;
		}

		foreach ( $props as $sPropName => $sPropValue ) {
			$dbw->replace(
				'page_props',
				[
					'pp_page',
					'pp_propname'
				],
				[
					'pp_page' => $id,
					'pp_propname' => $sPropName,
					'pp_value' => $sPropValue
				],
				__METHOD__
			);
		}

		if ( !$this->manualWrite ) {
			$dbw->endAtomic( __METHOD__ );
			$this->atomicStarted = false;
		}
	}

	public function write() {
		if ( $this->atomicStarted && $this->manualWrite ) {
			wfGetDB( DB_MASTER )->endAtomic( __CLASS__ . '::set' );
			$this->atomicStarted = false;
		}
	}
}
