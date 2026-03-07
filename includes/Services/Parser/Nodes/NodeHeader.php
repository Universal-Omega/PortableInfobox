<?php

namespace PortableInfobox\Services\Parser\Nodes;

class NodeHeader extends Node {
	/*
	 * @return array
	 */
	public function getData() {
		$this->data ??= [
			'value' => $this->getInnerValue( $this->xmlNode ),
			'item-name' => $this->getItemName(),
		];

		return $this->data;
	}
}
