<?php

namespace PortableInfobox\Services\Parser\Nodes;

class NodeNavigation extends Node {

	public function getData() {
		$this->data ??= [
			'value' => $this->getInnerValue( $this->xmlNode ),
			'item-name' => $this->getItemName(),
		];

		return $this->data;
	}

	public function isEmpty() {
		$links = trim( $this->getData()['value'] );
		return $links === '';
	}
}
