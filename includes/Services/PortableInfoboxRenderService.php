<?php

namespace PortableInfobox\Services;

use PortableInfobox\Services\Helpers\PortableInfoboxTemplateEngine;

class PortableInfoboxRenderService extends AbstractPortableInfoboxRenderService {

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

	protected function renderTitle( array $data ) {
		$data['inlineStyles'] = $this->inlineStyles;

		return $this->render( 'title', $data );
	}

	protected function renderHeader( array $data ) {
		$data['inlineStyles'] = $this->inlineStyles;

		return $this->render( 'header', $data );
	}

	private function getInlineStyles( $accentColor, $accentColorText ) {
		$backgroundColor = empty( $accentColor ) ? '' : "background-color:{$accentColor};";
		$color = empty( $accentColorText ) ? '' : "color:{$accentColorText};";

		return "{$backgroundColor}{$color}";
	}
}
