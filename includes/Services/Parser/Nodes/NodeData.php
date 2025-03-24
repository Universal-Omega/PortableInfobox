<?php

namespace PortableInfobox\Services\Parser\Nodes;

class NodeData extends Node {
	private const SPAN_ATTR_NAME = 'span';
	private const SPAN_DEFAULT_VALUE = 1;

	private const LAYOUT_ATTR_NAME = 'layout';
	private const LAYOUT_DEFAULT_VALUE = 'default';

	public function getData() {
		if ( !isset( $this->data ) ) {
			$this->data = [
				'label' => $this->getInnerValue( $this->xmlNode->{self::LABEL_TAG_NAME} ),
				'value' => $this->getValueWithDefault( $this->xmlNode ),
				'span' => $this->getSpan(),
				'layout' => $this->getLayout(),
				'source' => $this->getPrimarySource(),
				'item-name' => $this->getItemName()
			];
		}

		return $this->data;
	}

	protected function getSpan() {
		$span = $this->getXmlAttribute( $this->xmlNode, self::SPAN_ATTR_NAME );

		return ( isset( $span ) && ctype_digit( $span ) ) ? intval( $span ) : self::SPAN_DEFAULT_VALUE;
	}

	protected function getLayout() {
		$layout = $this->getXmlAttribute( $this->xmlNode, self::LAYOUT_ATTR_NAME );

		return ( isset( $layout ) && $layout == self::LAYOUT_DEFAULT_VALUE ) ? $layout : null;
	}
}
