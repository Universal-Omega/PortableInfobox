<?php

namespace PortableInfobox\Services\Parser\Nodes;

use PortableInfobox\Services\Parser\XmlParser;
use SimpleXMLElement;

class NodeFactory {

	public static function newFromXML( $text, array $data = [] ) {
		return self::getInstance( XmlParser::parseXmlString( $text ), $data );
	}

	public static function newFromSimpleXml( SimpleXMLElement $xmlNode, array $data = [] ) {
		return self::getInstance( $xmlNode, $data );
	}

	/**
	 * @param SimpleXMLElement $xmlNode
	 * @param array $data
	 *
	 * @return Node|NodeUnimplemented
	 */
	protected static function getInstance( SimpleXMLElement $xmlNode, array $data ) {
		$tagType = $xmlNode->getName();
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
