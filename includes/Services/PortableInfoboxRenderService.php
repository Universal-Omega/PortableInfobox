<?php

namespace PortableInfobox\Services;

use PortableInfobox\Services\Helpers\PortableInfoboxTemplateEngine;

class PortableInfoboxRenderService {
	// keep synced with css variables (--pi-width)
	public const DEFAULT_DESKTOP_INFOBOX_WIDTH = 270;
	public const DEFAULT_DESKTOP_THUMBNAIL_WIDTH = 350;

	protected $templateEngine;
	protected $inlineStyles;

	public function __construct() {
		$this->templateEngine = new PortableInfoboxTemplateEngine();
	}

	/**
	 * renders infobox
	 *
	 * @param array $infoboxdata
	 *
	 * @param string $theme
	 * @param string $layout
	 * @param string $accentColor
	 * @param string $accentColorText
	 * @param string $type
	 * @param string $itemName
	 * @return string - infobox HTML
	 */
	public function renderInfobox(
		array $infoboxdata, $theme, $layout, $accentColor, $accentColorText, $type, $itemName
	) {
		$this->inlineStyles = $this->getInlineStyles( $accentColor, $accentColorText );

		$infoboxHtmlContent = $this->renderChildren( $infoboxdata );

		if ( !empty( $infoboxHtmlContent ) ) {
			$output = $this->renderItem( 'wrapper', [
				'content' => $infoboxHtmlContent,
				'theme' => $theme,
				'layout' => $layout,
				'type' => $type,
				'item-name' => $itemName
			] );
		} else {
			$output = '';
		}

		return $output;
	}

	/**
	 * Produces HTML output for item type and data
	 *
	 * @param string $type
	 * @param array $data
	 * @return string
	 */
	protected function render( $type, array $data ) {
		return $this->templateEngine->render( $type, $data );
	}

	/**
	 * renders part of infobox
	 *
	 * @param string $type
	 * @param array $data
	 *
	 * @return string - HTML
	 */
	protected function renderItem( $type, array $data ) {
		switch ( $type ) {
			case 'group':
				$result = $this->renderGroup( $data );
				break;
			case 'header':
				$result = $this->renderHeader( $data );
				break;
			case 'media':
				$result = $this->renderMedia( $data );
				break;
			case 'title':
				$result = $this->renderTitle( $data );
				break;
			case 'panel':
				$result = $this->renderPanel( $data );
				break;
			case 'section':
				$result = '';
				break;
			default:
				$result = $this->render( $type, $data );
				break;
		}

		return $result;
	}

	/**
	 * renders group infobox component
	 *
	 * @param array $groupData
	 *
	 * @return string - group HTML markup
	 */
	protected function renderGroup( array $groupData ) {
		$cssClasses = [];
		$groupHTMLContent = '';
		$children = $groupData['value'];
		$layout = $groupData['layout'];
		$collapse = $groupData['collapse'];
		$rowItems = $groupData['row-items'];

		if ( $rowItems > 0 ) {
			$items = $this->createSmartGroups( $children, $rowItems );
			$groupHTMLContent .= $this->renderChildren( $items );
		} elseif ( $layout === 'horizontal' ) {
			$groupHTMLContent .= $this->renderItem(
				'horizontal-group-content',
				$this->createHorizontalGroupData( $children )
			);
		} else {
			$groupHTMLContent .= $this->renderChildren( $children );
		}

		if ( $collapse !== null && count( $children ) > 0 && $children[0]['type'] === 'header' ) {
			$cssClasses[] = 'pi-collapse';
			$cssClasses[] = 'pi-collapse-' . $collapse;
		}

		return $this->render( 'group', [
			'content' => $groupHTMLContent,
			'cssClasses' => implode( ' ', $cssClasses ),
			'item-name' => $groupData['item-name']
		] );
	}

	/**
	 * If image element has invalid thumbnail, doesn't render this element at all.
	 *
	 * @param array $data
	 * @return string
	 */
	protected function renderMedia( array $data ) {
		if ( count( $data ) === 0 || !$data[0] ) {
			return '';
		}

		if ( count( $data ) === 1 ) {
			$data = $data[0];
			$templateName = 'media';
		} else {
			// More than one image means image collection
			$data = [
				'images' => $data,
				'source' => $data[0]['source'],
				'item-name' => $data[0]['item-name']
			];
			$templateName = 'media-collection';
		}

		return $this->render( $templateName, $data );
	}

	protected function renderTitle( array $data ) {
		$data['inlineStyles'] = $this->inlineStyles;

		return $this->render( 'title', $data );
	}

	protected function renderHeader( array $data ) {
		$data['inlineStyles'] = $this->inlineStyles;

		return $this->render( 'header', $data );
	}

	protected function renderChildren( array $children ) {
		$result = '';
		foreach ( $children as $child ) {
			$type = $child['type'];
			if ( $this->templateEngine->isSupportedType( $type ) ) {
				$result .= $this->renderItem( $type, $child['data'] );
			}
		}

		return $result;
	}

	protected function renderPanel( $data, $type = 'panel' ) {
		$cssClasses = [];
		$sections = [];
		$collapse = $data['collapse'];
		$header = '';
		$shouldShowToggles = false;

		foreach ( $data['value'] as $index => $child ) {
			switch ( $child['type'] ) {
				case 'header':
					if ( empty( $header ) ) {
						$header = $this->renderHeader( $child['data'] );
					}
					break;
				case 'section':
					$sectionData = $this->getSectionData( $child, $index );
					// section needs to have content in order to render it
					if ( !empty( $sectionData['content'] ) ) {
						$sections[] = $sectionData;
						if ( !empty( $sectionData['label'] ) ) {
							$shouldShowToggles = true;
						}
					}
					break;
				default:
					// we do not support any other tags than section and header inside panel
					break;
			}
		}
		if ( $collapse !== null && count( $sections ) > 0 && !empty( $header ) ) {
			$cssClasses[] = 'pi-collapse';
			$cssClasses[] = 'pi-collapse-' . $collapse;
		}
		if ( count( $sections ) > 0 ) {
			$sections[0]['active'] = true;
		} else {
			// do not render empty panel
			return '';
		}
		if ( !$shouldShowToggles ) {
			$sections = array_map( static function ( $content ) {
				$content['active'] = true;
				return $content;
			}, $sections );
		}

		return $this->render( $type, [
			'item-name' => $data['item-name'],
			'cssClasses' => implode( ' ', $cssClasses ),
			'header' => $header,
			'sections' => $sections,
			'shouldShowToggles' => $shouldShowToggles,
		] );
	}

	private function getSectionData( $section, $index ) {
		$content = $this->renderChildren( $section['data']['value'] );
		return [
			'index' => $index,
			'item-name' => $section['data']['item-name'],
			'label' => $section['data']['label'],
			'content' => !empty( $content ) ? $content : null
		];
	}

	private function getInlineStyles( $accentColor, $accentColorText ) {
		$backgroundColor = empty( $accentColor ) ? '' : "background-color:{$accentColor};";
		$color = empty( $accentColorText ) ? '' : "color:{$accentColorText};";

		return "{$backgroundColor}{$color}";
	}

	private function createHorizontalGroupData( array $groupData ) {
		$horizontalGroupData = [
			'data' => [],
			'renderLabels' => false
		];

		foreach ( $groupData as $item ) {
			$data = $item['data'];

			if ( $item['type'] === 'data' ) {
				$horizontalGroupData['data'][] = [
					'label' => $data['label'],
					'value' => $data['value'],
					'source' => $item['data']['source'] ?? "",
					'item-name' => $item['data']['item-name']
				];

				if ( !empty( $data['label'] ) ) {
					$horizontalGroupData['renderLabels'] = true;
				}
			} elseif ( $item['type'] === 'header' ) {
				$horizontalGroupData['header'] = $data['value'];
				$horizontalGroupData['inlineStyles'] = $this->inlineStyles;
			}
		}

		return $horizontalGroupData;
	}

	private function createSmartGroups( array $groupData, $rowCapacity ) {
		$result = [];
		$rowSpan = 0;
		$rowItems = [];

		foreach ( $groupData as $item ) {
			$data = $item['data'];

			if ( $item['type'] === 'data' && $data['layout'] !== 'default' ) {

				if ( !empty( $rowItems ) && $rowSpan + $data['span'] > $rowCapacity ) {
					$result[] = $this->createSmartGroupItem( $rowItems, $rowSpan );
					$rowSpan = 0;
					$rowItems = [];
				}
				$rowSpan += $data['span'];
				$rowItems[] = $item;
			} else {
				// smart wrapping works only for data tags
				if ( !empty( $rowItems ) ) {
					$result[] = $this->createSmartGroupItem( $rowItems, $rowSpan );
					$rowSpan = 0;
					$rowItems = [];
				}
				$result[] = $item;
			}
		}
		if ( !empty( $rowItems ) ) {
			$result[] = $this->createSmartGroupItem( $rowItems, $rowSpan );
		}

		return $result;
	}

	private function createSmartGroupItem( array $rowItems, $rowSpan ) {
		return [
			'type' => 'smart-group',
			'data' => $this->createSmartGroupSections( $rowItems, $rowSpan )
		];
	}

	private function createSmartGroupSections( array $rowItems, $capacity ) {
		return array_reduce( $rowItems, static function ( $result, $item ) use ( $capacity ) {
			$width = $item['data']['span'] / $capacity * 100;
			$styles = "width: {$width}%";

			$label = $item['data']['label'] ?? "";
			if ( !empty( $label ) ) {
				$result['renderLabels'] = true;
			}
			$result['data'][] = [
				'label' => $label,
				'value' => $item['data']['value'],
				'inlineStyles' => $styles,
				'source' => $item['data']['source'] ?? "",
				'item-name' => $item['data']['item-name']
			];

			return $result;
		}, [ 'data' => [], 'renderLabels' => false ] );
	}
}
