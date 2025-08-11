<?php

namespace PortableInfobox\Services\Parser\Nodes;

use PortableInfobox\Parsoid\ParsoidMediaWikiParser;
use PortableInfobox\Services\Parser\XmlParser;
use SimpleXMLElement;

class NodeFactory {

	public static function newFromXML( $text, array $data = [], $externalParser = null ) {
		return self::getInstance( XmlParser::parseXmlString( $text ), $data, $externalParser );
	}

	public static function newFromSimpleXml( SimpleXMLElement $xmlNode, array $data = [], $externalParser = null ) {
		return self::getInstance( $xmlNode, $data, $externalParser );
	}

	/**
	 * @param SimpleXMLElement $xmlNode
	 * @param array $data
	 *
	 * @return Node|NodeUnimplemented
	 */
	protected static function getInstance( SimpleXMLElement $xmlNode, array $data, $externalParser ) {
		$tagType = $xmlNode->getName();

		// a bit messed up, but we want to do some special stuff here with images
		// that differs from the legacy implementation (which also calls out to Parser.php)
		// so lets load a different class if we have an image
		if ( $externalParser instanceof ParsoidMediaWikiParser ) {
			if ( $tagType === 'image' ) {
				return new ParsoidImageNode( $xmlNode, $data );
			} elseif ( $tagType === 'media' ) {
				return new ParsoidMediaNode( $xmlNode, $data );
			}
		}
		
		$className = Node::class . mb_convert_case( mb_strtolower( $tagType ), MB_CASE_TITLE );
		if ( class_exists( $className ) ) {
			/* @var $instance Node */
			$instance = new $className( $xmlNode, $data );

			if ( $instance instanceof Node ) {
				return $instance;
			}
		}

		return new NodeUnimplemented( $xmlNode, $data );
	}

}
