<?php

namespace PortableInfobox\Helpers;

class PortableInfoboxDataBag {
	private static $instance = null;
	private $galleries = [];

	private function __construct() {
	}

	/**
	 * @return null|PortableInfoboxDataBag
	 */
	public static function getInstance() {
		if ( self::$instance === null ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function setGallery( $marker, $content ) {
		$this->galleries[$marker] = $content;
	}

	/**
	 * Retrieve source content of a gallery identified by Parser marker id
	 */
	public function getGallery( $marker ) {
		if ( isset( $this->galleries[$marker] ) ) {
			return $this->galleries[$marker];
		}

		return null;
	}
}
