<?php

namespace PortableInfobox\Parsoid;

use DOMDocument;
use PortableInfobox\Services\Helpers\InfoboxParamsValidator;
use PortableInfobox\Services\Helpers\PortableInfoboxTemplateEngine;
use PortableInfobox\Services\Parser\Nodes\NodeFactory;
use Wikimedia\Parsoid\Core\Sanitizer;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Utils\DOMCompat;

class ParsoidPortableInfoboxRenderService {

    public const PARSER_TAG_VERSION = 2;
	public const DEFAULT_THEME_NAME = 'default';
	public const INFOBOX_THEME_PREFIX = 'pi-theme-';

	private const PARSER_TAG_NAME = 'infobox';
	private const DEFAULT_LAYOUT_NAME = 'default';
	private const INFOBOX_LAYOUT_PREFIX = 'pi-layout-';
	private const INFOBOX_TYPE_PREFIX = 'pi-type-';
	private const ACCENT_COLOR = 'accent-color';
	private const ACCENT_COLOR_TEXT = 'accent-color-text';
	private const ERR_UNIMPLEMENTEDNODE = 'portable-infobox-unimplemented-infobox-tag';
	private const ERR_UNSUPPORTEDATTR = 'portable-infobox-xml-parse-error-infobox-tag-attribute-unsupported';

	private ?InfoboxParamsValidator $infoboxParamsValidator = null;

    private array $paramMap = [];

    private ?PortableInfoboxTemplateEngine $templateEngine = null;

    public function __construct() {
        // no-op
    }

    /**
     * Build a parameter map of field -> value for display
     * @param array $params
     * @return void
     */
    private function buildParamMap( ParsoidExtensionAPI $extApi, array $params ): void {
		// loop over all of the parameters we received and if they have both a value
		// and a name, then add them to the array - if they do not have a "valueWt" value, then the value was 
		// empty and therefore we should not render this part of the infobox
		// At this point, we have what we pretty much have what we pass to the legacy function on line
		// 104 of PortableInfoboxParserTagController::class
		foreach ( $params as $param ) {
			if ( isset( $param->k ) && isset( $param->valueWt ) ) {
				$htmlValue = $this->processWikitextToHtml( $extApi, $param->valueWt );
				$this->paramMap[ $param->k ] = $htmlValue;
			}
		}
	}
	

    /**
     * This function is responsible for rendering the actual infobox to the DOM
     * This is the entrypoint which should be called to render the infobox from the 
     * DOMProcessor. This will delegate appropriately
     */
    public function render(
		ParsoidExtensionAPI $extApi,
        Element $container,
        Document $doc,
        array $params,
        string $parsoidData
    ): void {
        $this->buildParamMap( $extApi, $params );
        [ $data, $attr ] = $this->prepareInfobox( $extApi, $parsoidData, $this->paramMap ?: [] );
    
        $themes = $this->getThemes( $attr );
        $layout = $this->getLayout( $attr );
        $type = $this->getType( $attr );
        $itemName = $this->getItemName( $attr );
        
        // This is a slight change from the legacy, we only use the templates to render the children
        // since Parsoid will have generated an <aside> wrapper tag before we reach this function, so
        // lets get our classes and add them to this <aside> wrapper which is the $container
        $classes = [
            'portable-infobox',
            'noexcerpt', 
            'searchaux',
            'pi-background'
        ];
        
        $classes = array_merge( $classes, $themes );
        $classes[] = $layout;  
        $classes[] = $type;
        
        $container->setAttribute('class', implode(' ', $classes ) );
        
        $result = $this->renderInfobox(
            $data, 
        );
    
		DOMCompat::setInnerHTML( $container, $result );
    }

    public function prepareInfobox(
		ParsoidExtensionAPI $extApi,
        string $parsoidData,
        array $params,
    ): array {

        // same as legacy!
        $infoboxNode = NodeFactory::newFromXML( $parsoidData, $this->paramMap ?: [] );
		$infoboxNode->setExternalParser( new ParsoidMediaWikiParser( $extApi ) );
        $data = $infoboxNode->getRenderData();
        $attr = $infoboxNode->getParams();
        return [ $data, $attr ];
    }

    /**
     * Get the themes for this infobox as an array, falling back to the default if needed
     * @param $attr the attributes
     * @return array
     */
    private function getThemes( array $attr ): array {
        $themes = [];

        if ( isset( $attr['theme'] ) ) {
            $staticTheme = trim( $attr['theme'] );
            if ( !empty( $staticTheme ) ) {
                $themes[] = $staticTheme;
            }
        }

        // we don't have access to the frame here, but what we do have is the params and their values we got earlier, so 
        // we can get the value for that - I think this works but I'm a bit confused alas!
        if ( !empty( $attr['theme-source' ] ) ) {
            $variableTheme = $this->paramMap[ $attr['theme-source'] ];
            if ( !empty( $variableTheme ) ) {
                $themes[] = $variableTheme;
            }
        }

        $themes = !empty( $themes ) ? $themes : [ self::DEFAULT_THEME_NAME ];

        return array_map( static function ( $name ) {
			return Sanitizer::escapeIdForAttribute(
				self::INFOBOX_THEME_PREFIX . preg_replace( '|\s+|s', '-', $name )
			);
		}, $themes );
    }

    /**
     * Get the layout for this infobox from the attributes 
     * @param array $attr the attributes
     */
    private function getLayout( array $attr ): string {
		$layoutName = $attr['layout'] ?? '';
		if ( $this->getParamsValidator()->validateLayout( $layoutName ) ) {
			return self::INFOBOX_LAYOUT_PREFIX . $layoutName;
		}

		return self::INFOBOX_LAYOUT_PREFIX . self::DEFAULT_LAYOUT_NAME;
	}

    /**
     * Get an instance of the InfoboxParamsValidator::class
     * @return \PortableInfobox\Services\Helpers\InfoboxParamsValidator;
     */
    private function getParamsValidator() {
		if ( empty( $this->infoboxParamsValidator ) ) {
			$this->infoboxParamsValidator = new InfoboxParamsValidator();
		}

		return $this->infoboxParamsValidator;
	}

    /**
     * Get the type of infobox that this is, ie "character" "place"
     * @param $attr the attributes
     * @return string
     */
    private function getType( array $attr ): string {
		return !empty( $attr['type'] ) ? Sanitizer::escapeIdForAttribute(
				self::INFOBOX_TYPE_PREFIX . preg_replace( '|\s+|s', '-', $attr['type'] )
			) : '';
	}

    /**
     * Get the name for this infobox
     * @TODO: needs to go through the sanitizer
     * @param array $attr 
     * @return @string
     */
    private function getItemName( $attr ) {
		return !empty( $attr['name'] ) ? $attr['name'] : '';
	}

    private function renderInfobox(
        array $data,
    ) {
        $this->templateEngine = new PortableInfoboxTemplateEngine();

        $infoboxHtmlContent = $this->renderChildren( $data );

		return $infoboxHtmlContent;
    }

    /**
     * Render the children of the infobox
     * @param $children our param -> value data (may be nested and this will be recursively called)
     * @return string the output
     */
    private function renderChildren( array $children ): string {
        $result = '';
		foreach ( $children as $child ) {
			$type = $child['type'];
			if ( $this->templateEngine->isSupportedType( $type ) ) {
				$result .= $this->renderItem( $type, $child['data'] );
			}
		}
		return $result;
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
				$result = $this->renderTemplate( $type, $data );
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

		return $this->renderTemplate( 'group', [
			'content' => $groupHTMLContent,
			'cssClasses' => implode( ' ', $cssClasses ),
			'item-name' => $groupData['item-name']
		] );
	}

    protected function renderHeader( array $data ) {
		// $data['inlineStyles'] = $this->inlineStyles;

		return $this->renderTemplate( 'header', $data );
	}

    protected function renderTemplate( $type, array $data ) {
		return $this->templateEngine->render( $type, $data );
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

		return $this->renderTemplate( $templateName, $data );
	}

    protected function renderTitle( array $data ) {
		// $data['inlineStyles'] = $this->inlineStyles;

		return $this->renderTemplate( 'title', $data );
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

		return $this->renderTemplate( $type, [
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
				// $horizontalGroupData['inlineStyles'] = $this->inlineStyles;
			}
		}

		return $horizontalGroupData;
	}

	/**
	 * A utility function to parse wikitext to HTML which will be passed into the infobox
	 * template
	 * @param \Wikimedia\Parsoid\Ext\ParsoidExtensionAPI $extApi
	 * @param string $wikitext
	 * @return string
	 */
	private function processWikitextToHtml( ParsoidExtensionAPI $extApi, string $wikitext ): string {

		$paramParsed = $extApi->wikitextToDOM( $wikitext, [
			'processInNewFrame' => true,
			'parseOpts' => [ 'context' => 'inline' ]
		], true );
		

		$htmlContent = '';
		// this feels really hacky?!
		if ( $paramParsed->hasChildNodes() ) {

			$tempDoc = $paramParsed->ownerDocument;
			
			foreach ( $paramParsed->childNodes as $childNode ) {
				$htmlContent .= $tempDoc->saveHTML( $childNode );
			}
		}
		
		return $htmlContent;
	}

}