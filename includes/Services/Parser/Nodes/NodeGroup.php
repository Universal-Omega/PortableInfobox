<?php

namespace PortableInfobox\Services\Parser\Nodes;

class NodeGroup extends Node {
	private const LAYOUT_ATTR_NAME = 'layout';
	private const SHOW_ATTR_NAME = 'show';
	private const LAYOUT_DEFAULT_OPTION = 'default';
	private const LAYOUT_HORIZONTAL_OPTION = 'horizontal';
	private const SHOW_DEFAULT_OPTION = 'default';
	private const SHOW_INCOMPLETE_OPTION = 'incomplete';
	private const COLLAPSE_ATTR_NAME = 'collapse';
	private const COLLAPSE_OPEN_OPTION = 'open';
	private const COLLAPSE_CLOSED_OPTION = 'closed';
	private const ROW_ITEMS_ATTR_NAME = 'row-items';

	private $supportedGroupLayouts = [
		self::LAYOUT_DEFAULT_OPTION,
		self::LAYOUT_HORIZONTAL_OPTION
	];

	private $supportedGroupDisplays = [
		self::SHOW_DEFAULT_OPTION,
		self::SHOW_INCOMPLETE_OPTION
	];

	private $supportedGroupCollapses = [
		self::COLLAPSE_OPEN_OPTION,
		self::COLLAPSE_CLOSED_OPTION
	];

	public function getData() {
		if ( !isset( $this->data ) ) {
			$this->data = [
				'value' => $this->getDataForChildren(),
				'layout' => $this->getLayout(),
				'collapse' => $this->getCollapse(),
				'row-items' => $this->getRowItems(),
				'item-name' => $this->getItemName()
			];
		}

		return $this->data;
	}

	public function getRenderData() {
		$value = $this->showIncomplete() ?
			array_map(
				static function ( Node $item ) {
					return $item->getRenderData();
				},
				$this->getChildNodes()
			)
			: $this->getRenderDataForChildren();

		return [
			'type' => $this->getType(),
			'data' => [
				'value' => $value,
				'layout' => $this->getLayout(),
				'collapse' => $this->getCollapse(),
				'row-items' => $this->getRowItems(),
				'item-name' => $this->getItemName()
			],
		];
	}

	public function isEmpty() {
		/** @var Node $item */
		foreach ( $this->getChildNodes() as $item ) {
			if ( !$item->isType( 'header' ) && !$item->isEmpty() ) {
				return false;
			}
		}

		return true;
	}

	public function getSources() {
		return $this->getSourcesForChildren();
	}

	public function getMetadata() {
		return [
			'type' => $this->getType(),
			'metadata' => $this->getMetadataForChildren()
		];
	}

	protected function showIncomplete() {
		return strcasecmp( $this->getDisplay(), self::SHOW_INCOMPLETE_OPTION ) === 0;
	}

	protected function getDisplay() {
		$show = $this->getXmlAttribute( $this->xmlNode, self::SHOW_ATTR_NAME );

		return ( isset( $show ) && in_array( strtolower( $show ), $this->supportedGroupDisplays ) ) ?
			$show : self::SHOW_DEFAULT_OPTION;
	}

	protected function getCollapse() {
		$collapse = $this->getXmlAttribute( $this->xmlNode, self::COLLAPSE_ATTR_NAME );
		return ( isset( $collapse ) && in_array( $collapse, $this->supportedGroupCollapses ) ) ?
			$collapse : null;
	}

	protected function getLayout() {
		$layout = $this->getXmlAttribute( $this->xmlNode, self::LAYOUT_ATTR_NAME );

		return ( isset( $layout ) && in_array( $layout, $this->supportedGroupLayouts ) ) ?
			$layout : self::LAYOUT_DEFAULT_OPTION;
	}

	protected function getRowItems() {
		$rowItems = $this->getXmlAttribute( $this->xmlNode, self::ROW_ITEMS_ATTR_NAME );

		return ( isset( $rowItems ) && ctype_digit( $rowItems ) ) ? intval( $rowItems ) : null;
	}
}
