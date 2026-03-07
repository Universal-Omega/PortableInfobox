<?php

namespace PortableInfobox\Services\Parser\Nodes;

class NodeTitle extends Node {

	public function getData() {
		$this->data ??= [
			'value' => $this->getValueWithDefault( $this->xmlNode ),
			'source' => $this->getXmlAttribute( $this->xmlNode, self::DATA_SRC_ATTR_NAME ),
			'item-name' => $this->getItemName(),
		];

		return $this->data;
	}
}
