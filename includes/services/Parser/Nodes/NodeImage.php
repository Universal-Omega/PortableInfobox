<?php
namespace PortableInfobox\Parser\Nodes;

use PortableInfobox\Helpers\FileNamespaceSanitizeHelper;
use PortableInfobox\Helpers\HtmlHelper;
use PortableInfobox\Helpers\PortableInfoboxDataBag;
use PortableInfobox\Sanitizers\SanitizerBuilder;

class NodeImage extends Node {
	const GALLERY = 'GALLERY';
	const TABBER = 'TABBER';

	const ALT_TAG_NAME = 'alt';
	const CAPTION_TAG_NAME = 'caption';

	public static function getMarkers( $value, $ext ) {
		if ( preg_match_all('/' . \Parser::MARKER_PREFIX . '-' . $ext . '-[A-F0-9]{8}' . \Parser::MARKER_SUFFIX . '/is', $value, $out ) ) {
			return $out[0];
		} else {
			return [];
		}
	}

	public static function getGalleryData( $marker ) {
		$gallery = PortableInfoboxDataBag::getInstance()->getGallery( $marker );
		return isset( $gallery ) ? array_map( function ( $image ) {
			return [
				'label' => $image[1] ?: $image[0]->getText(),
				'title' => $image[0]
			];
		}, $gallery->getimages() ) : [];
	}

	public static function getTabberData( $html ) {
		$data = [];
		$doc = HtmlHelper::createDOMDocumentFromText( $html );
		$sxml = simplexml_import_dom( $doc );
		$divs = $sxml->xpath( '//div[@class=\'tabbertab\']' );
		foreach ( $divs as $div ) {
			if ( preg_match( '/ src="(?:[^"]*\/)?([^"]*?)"/', $div->asXML(), $out ) ) {
				$data[] = [
					'label' => (string) $div['title'],
					'title' => $out[1]
				];
			}
		}
		return $data;
	}

	public function getData() {
		if ( !isset( $this->data ) ) {
			$this->data = [];

			// value passed to source parameter (or default)
			$value = $this->getRawValueWithDefault( $this->xmlNode );

			if ( $this->containsTabberOrGallery( $value ) ) {
				$this->data = $this->getImagesData( $value );
			} else {
				$this->data = [ $this->getImageData(
					$value,
					$this->getValueWithDefault( $this->xmlNode->{self::ALT_TAG_NAME} ),
					$this->getValueWithDefault( $this->xmlNode->{self::CAPTION_TAG_NAME} )
				) ];
			}
		}
		return $this->data;
	}

	/**
	 * @desc Checks if parser preprocessed string containg Tabber or Gallery extension
	 * @param string $str String to check
	 * @return bool
	 */
	private function containsTabberOrGallery( $str ) {
		return !empty( self::getMarkers( $str, self::TABBER ) ) || !empty( self::getMarkers( $str, self::GALLERY ) );
	}

	private function getImagesData( $value ) {
		$data = [];
		$items = array_merge( $this->getGalleryItems( $value ), $this->getTabberItems( $value ) );
		foreach( $items as $item ) {
			$data[] = $this->getImageData( $item['title'], $item['label'], $item['label'] );
		}
		return $data;
	}

	private function getGalleryItems( $value ) {
		$galleryItems = [];
		$galleryMarkers = self::getMarkers( $value, self::GALLERY );
		foreach ( $galleryMarkers as $marker ) {
			$galleryItems = array_merge( $galleryItems, self::getGalleryData( $marker ) );
		}
		return $galleryItems;
	}

	private function getTabberItems( $value ) {
		$tabberItems = [];
		$tabberMarkers = self::getMarkers( $value, self::TABBER );
		foreach ( $tabberMarkers as $marker ) {
			$tabberHtml = $this->getExternalParser()->parseRecursive( $marker );
			$tabberItems = array_merge( $tabberItems, self::getTabberData( $tabberHtml ) );
		}
		return $tabberItems;
	}

	/**
	 * @desc prepare infobox image node data.
	 *
	 * @param $title
	 * @param $alt
	 * @param $caption
	 * @return array
	 */
	private function getImageData( $title, $alt, $caption ) {
		$titleObj = $title instanceof \Title ? $title : $this->getImageAsTitleObject( $title );
		$fileObj = $this->getFileFromTitle( $titleObj );

		if ( $titleObj instanceof \Title ) {
			$this->getExternalParser()->addImage( $titleObj->getDBkey() );
		}

		$image = [
			'url' => $this->resolveImageUrl( $fileObj ),
			'name' => $titleObj ? $titleObj->getText() : '',
			'key' => $titleObj ? $titleObj->getDBKey() : '',
			'alt' => $alt ?? ( $titleObj ? $titleObj->getText() : null ),
			'caption' => SanitizerBuilder::createFromType( 'image' )
				->sanitize( [ 'caption' => $caption ] )['caption'] ?: null,
			'isVideo' => $this->isVideo( $fileObj )
		];

		return $image;
	}

	public function isEmpty() {
		$data = $this->getData();
		foreach ( $data as $dataItem ) {
			if ( !empty( $dataItem[ 'url' ] ) ) {
				return false;
			}
		}
		return true;
	}

	public function getSources() {
		$sources = $this->extractSourcesFromNode( $this->xmlNode );
		if ( $this->xmlNode->{self::ALT_TAG_NAME} ) {
			$sources = array_merge( $sources,
				$this->extractSourcesFromNode( $this->xmlNode->{self::ALT_TAG_NAME} ) );
		}
		if ( $this->xmlNode->{self::CAPTION_TAG_NAME} ) {
			$sources = array_merge( $sources,
				$this->extractSourcesFromNode( $this->xmlNode->{self::CAPTION_TAG_NAME} ) );
		}

		return array_unique( $sources );
	}

	private function getImageAsTitleObject( $imageName ) {
		global $wgContLang;
		$title = \Title::makeTitleSafe(
			NS_FILE,
			FileNamespaceSanitizeHelper::getInstance()->sanitizeImageFileName( $imageName, $wgContLang )
		);

		return $title;
	}

	/**
	 * NOTE: Protected to override in unit tests
	 *
	 * @desc get file object from title object
	 * @param Title|null $title
	 * @return File|null
	 */
	protected function getFileFromTitle( $title ) {
		if( is_string( $title ) ) {
			$title = \Title::newFromText( $title, NS_FILE );
		}

		if( $title instanceof \Title ) {
			$file = wfFindFile( $title );
			if( $file instanceof \File && $file->exists() ) {
				return $file;
			}
		}

		return null;
	}

	/**
	 * @desc returns image url for given image title
	 * @param File|null $file
	 * @return string url or '' if image doesn't exist
	 */
	public function resolveImageUrl( $file ) {
		return $file ? $file->getUrl() : '';
	}

	/**
	 * @desc checks if file media type is VIDEO
	 * @param File|null $file
	 * @return bool
	 */
	private function isVideo( $file ) {
		return $file ? $file->getMediaType() === MEDIATYPE_VIDEO : false;
	}
}
