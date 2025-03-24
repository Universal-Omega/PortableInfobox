<?php

namespace PortableInfobox\Services\Parser\Nodes;

class NodeTitle extends Node {

	public function getData() {
		if ( !isset( $this->data ) ) {
			$title = $this->getValueWithDefault( $this->xmlNode );
			$this->data = [
				'value' => $title,
				'source' => $this->getXmlAttribute( $this->xmlNode, self::DATA_SRC_ATTR_NAME ),
				'item-name' => $this->getItemName()
			];
		}

		return $this->data;
	}
}
