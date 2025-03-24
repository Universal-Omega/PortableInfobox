<?php

namespace PortableInfobox\Services\Parser\Nodes;

class NodeNavigation extends Node {

	public function getData() {
		if ( !isset( $this->data ) ) {
			$this->data = [
				'value' => $this->getInnerValue( $this->xmlNode ),
				'item-name' => $this->getItemName()
			];
		}

		return $this->data;
	}

	public function isEmpty() {
		$data = $this->getData();
		$links = trim( $data['value'] );

		return empty( $links ) && $links != '0';
	}
}
