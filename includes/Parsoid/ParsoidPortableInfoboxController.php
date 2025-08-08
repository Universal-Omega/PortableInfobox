<?php

namespace PortableInfobox\Parsoid;

use PortableInfobox\Services\Helpers\InfoboxParamsValidator;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Ext\DOMUtils;

class ParsoidPortableInfoboxController
{
    public const PARSER_TAG_VERSION = 2;
    public const DEFAULT_THEME_NAME = 'default';
    public const INFOBOX_THEME_PREFIX = 'pi-theme-';

    private const DEFAULT_LAYOUT_NAME = 'default';
    private const INFOBOX_LAYOUT_PREFIX = 'pi-layout-';
    private const INFOBOX_TYPE_PREFIX = 'pi-type-';
    private const ACCENT_COLOR = 'accent-color';
    private const ACCENT_COLOR_TEXT = 'accent-color-text';
    private const ERR_UNIMPLEMENTEDNODE = 'portable-infobox-unimplemented-infobox-tag';
    private const ERR_UNSUPPORTEDATTR = 'portable-infobox-xml-parse-error-infobox-tag-attribute-unsupported';

    private $infoboxParamsValidator = null;

    /**
     * Get a new instance of this class
     */
    public static function newInstance(): self {
        return new self();
    }

    /**
     * Handles rendering the infobox; 
     * This is a parser implementation of PortableInfoboxParserTagController::class
     * Variables have been renamed to be more in-line with what they actually are.
     * @since 1.0
     * @param string $template the contents of the template ($text in the parser version)
     * @param array $params a key => value of the parameters the user passed to the template
     * @return void [Write to DOM]
     */
    public function renderOuterInfobox( string $template, array $params ): void {

    }

    /**
     * Prepares the infobox by adding the classes that we need to the tag, such as
     * the theme, layout, etc. This does not create the aside tag, which is created earlier 
     * in the Parsoid process in InfoboxTag::class
     * For the moment, this doesn't use the template engine like the legacy parser tag does,
     * just because I can't deal to copy all of that and I need quick iteration for testing so yeah
     * @since 1.0
     * @param Element $el the infobox
     * @param array $args the arguments in the template such <infobox theme="yellow"></infobox>
     */
    public function prepareInfobox( Element $el, array $args ): void {
       
        // static classes that are always added
        $classes = [
			'portable-infobox',
			'noexcerpt',
			'searchaux',
			'pi-background'
		];

        foreach ( $this->getThemes( $args ) as $theme ) {
            $classes[] = self::INFOBOX_THEME_PREFIX . $theme;
        }

        $classes[] = $this->getLayout( $args );
		$classes[] = $this->getType( $args );
		$classes[] = $this->getItemName( $args );

        DOMUtils::addAttributes( $el, [
			'class' => implode( ' ', $classes )
		] );
    }

    private function getThemes( array $args ): array {
        $themes = [];

		if ( isset( $params['theme'] ) ) {
			$staticTheme = trim( $params['theme'] );
			if ( !empty( $staticTheme ) ) {
				$themes[] = $staticTheme;
			}
		}

        // Further investigation needed to whether we can support this
        // we potentially need to add it later when we have access to the user provided KV's
		// if ( !empty( $params['theme-source'] ) ) {
		// 	$variableTheme = trim( $frame->getArgument( $params['theme-source'] ) );
		// 	if ( !empty( $variableTheme ) ) {
		// 		$themes[] = $variableTheme;
		// 	}
		// }

		// use default global theme if not present
		$themes = !empty( $themes ) ? $themes : [ self::DEFAULT_THEME_NAME ];

        // Test for the moment; might need to pass this through the sanitizer like
        // done in the legacy implementation
		return $themes;
    }

    /**
     * Get the layout for this infobox, passing it through
     * the validator and returning it as a string
     * @since 1.0
     * @param array $params
     * @reuturn string
     */
    private function getLayout( $args ): string {
		$layoutName = $args['layout'] ?? '';
		if ( $this->getParamsValidator()->validateLayout( $layoutName ) ) {
			return self::INFOBOX_LAYOUT_PREFIX . $layoutName;
		}

		return self::INFOBOX_LAYOUT_PREFIX . self::DEFAULT_LAYOUT_NAME;
	}

    /**
     * Get an instance of the params validator
     * @since 1.0
     * @return InfoboxParamsValidator
     */
    private function getParamsValidator(): InfoboxParamsValidator {
		if ( empty( $this->infoboxParamsValidator ) ) {
			$this->infoboxParamsValidator = new InfoboxParamsValidator();
		}

		return $this->infoboxParamsValidator;
	}

    /**
     * Return the type of infobox.
     * @since 1.0 
     * @TODO: needs to go through the sanitizer
     * @param $args 
     * @return string the type
     */
    private function getType( array $args ): string {
		return $args['type'] ?? '';
	}

    /**
     * Return the name for this infobox 
     * @since 1.0
     * @TODO: needs to go through the sanitizer when I figure out what the 
     * Parsoid implementation of that is!
     */
    private function getItemName( array $args ) {
		return $args['name'] ?? '';
	}
}
