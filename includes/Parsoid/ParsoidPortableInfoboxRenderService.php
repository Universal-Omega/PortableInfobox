<?php

namespace PortableInfobox\Parsoid;

use PortableInfobox\Services\Helpers\InfoboxParamsValidator;
use PortableInfobox\Services\Parser\Nodes\NodeFactory;
use Wikimedia\Parsoid\Core\Sanitizer;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\Element;

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

    private $infoboxParamsValidator = null;

    public function __construct() {
        // no-op
    }

    /**
     * Build a parameter map of field -> value for display
     * @param array $params
     * @return void
     */
    private function buildParamMap( array $params ): void {
        // loop over all of the parameters we received and if they have both a value
        // and a name, then add them to the array - if they do not have a "wt" value, then the value was 
        // empty and therefore we should not render this part of the infobox
        // At this point, we have what we pretty much have what we pass to the legacy function on line
        // 104 of PortableInfoboxParserTagController::class
        foreach ( $params as $param ) {
            if ( isset( $param->k ) && isset( $param->valueWt ) ) {
                $this->paramMap[ $param->k ] = $param->valueWt;
            }
        }
    }

    /**
     * This function is responsible for rendering the actual infobox to the DOM
     * This is the entrypoint which should be called to render the infobox from the 
     * DOMProcessor. This will delegate appropriately
     */
    public function render(
        Element $container,
        Document $doc,
        array $params,
        string $parsoidData
    ): void {
    
        $this->buildParamMap( $params );
        [ $data, $attr ] = $this->prepareInfobox( $parsoidData, $this->paramMap ?: [] );

        $themes = $this->getThemes( $attr );
        $layout = $this->getLayout( $attr );
        $type = $this->getType( $attr );
        // @TODO: we need the accentColors and accentColorsText but I've no idea what the heck the controller
        // for the legacy parser is doing to get that and it's confusing me right now so come back to that later.
    
    }

    public function prepareInfobox(
        string $parsoidData,
        array $params,
    ): array {
        $this->buildParamMap( $params );

        // same as legacy!
        $infoboxNode = NodeFactory::newFromXML( $parsoidData, $this->paramMap ?: [] );
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
			return Sanitizer::escapeClass(
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
		return !empty( $params['type'] ) ? Sanitizer::escapeClass(
				self::INFOBOX_TYPE_PREFIX . preg_replace( '|\s+|s', '-', $params['type'] )
			) : '';
	}
}