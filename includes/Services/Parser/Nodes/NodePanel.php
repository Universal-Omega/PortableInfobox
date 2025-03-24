<?php

namespace PortableInfobox\Services\Parser\Nodes;

class NodePanel extends Node {
	private const COLLAPSE_ATTR_NAME = 'collapse';
	private const COLLAPSE_OPEN_OPTION = 'open';
	private const COLLAPSE_CLOSED_OPTION = 'closed';

	private $supportedPanelCollapses = [
		self::COLLAPSE_OPEN_OPTION,
		self::COLLAPSE_CLOSED_OPTION
	];

	public function getData() {
		if ( !isset( $this->data ) ) {
			$this->data = [
				'value' => $this->getRenderDataForChildren(),
				'collapse' => $this->getCollapse(),
				'item-name' => $this->getItemName(),
			];
		}
		return $this->data;
	}

	protected function getChildNodes() {
		if ( !isset( $this->children ) ) {
			$this->children = [];
			$hasHeader = false;

			foreach ( $this->xmlNode as $child ) {
				$name = $child->getName();

				if ( $name === 'section' || ( $name === 'header' && !$hasHeader ) ) {
					if ( $name === 'header' ) {
						$hasHeader = true;
					}

					$this->children[] = NodeFactory::newFromSimpleXml( $child, $this->infoboxData )
						->setExternalParser( $this->externalParser );
				}
			}
		}
		return $this->children;
	}

	protected function getCollapse() {
		$collapse = $this->getXmlAttribute( $this->xmlNode, self::COLLAPSE_ATTR_NAME );
		return ( isset( $collapse ) && in_array( $collapse, $this->supportedPanelCollapses ) ) ?
			$collapse : null;
	}
}
