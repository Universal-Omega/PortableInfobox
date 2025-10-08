<?php

namespace PortableInfobox\Services\Parser\Nodes;

use DOMDocument;
use DOMXpath;
use File;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Title\Title;
use PortableInfobox\Services\Helpers\FileNamespaceSanitizeHelper;
use PortableInfobox\Services\Helpers\PortableInfoboxDataBag;
use PortableInfobox\Services\Helpers\PortableInfoboxImagesHelper;
use PortableInfobox\Services\PortableInfoboxRenderService;

class NodeMedia extends Node {
	private const GALLERY = 'GALLERY';
	private const TABBER = 'TABBER';

	private const ALLOWIMAGE_ATTR_NAME = 'image';
	private const ALLOWVIDEO_ATTR_NAME = 'video';
	private const ALLOWAUDIO_ATTR_NAME = 'audio';

	private const ALT_TAG_NAME = 'alt';
	private const CAPTION_TAG_NAME = 'caption';

	private $helper;

	public static function getMarkers( $value, $ext ) {
		$regex = '/' . Parser::MARKER_PREFIX . "-$ext-[A-F0-9]{8}" . Parser::MARKER_SUFFIX . '/i';
		if ( preg_match_all( $regex, $value ?? '', $out ) ) {
			return $out[0];
		} else {
			return [];
		}
	}

	public static function getGalleryData( $marker ) {
		$gallery = PortableInfoboxDataBag::getInstance()->getGallery( $marker );
		return isset( $gallery ) ? array_map( static function ( $image ) {
			return [
				'label' => $image[1],
				'title' => $image[0]
			];
		}, $gallery->getimages() ) : [];
	}

	public static function getTabberData( $html ) {
		$data = [];

		$doc = new DOMDocument();
		$libXmlErrorSetting = libxml_use_internal_errors( true );

		// encode for correct load
		$doc->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );

		libxml_clear_errors();
		libxml_use_internal_errors( $libXmlErrorSetting );

		$xpath = new DOMXpath( $doc );
		$divs = $xpath->query( '//div[@class=\'tabbertab\']' );
		foreach ( $divs as $div ) {
			if ( preg_match( '# src="(?:[^"]*/)?(?:\d+px-)?([^"]*?)"#', $doc->saveXml( $div ), $out ) ) {
				$data[] = [
					'label' => $div->getAttribute( 'title' ),
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
	 * Checks if parser preprocessed string containg Tabber or Gallery extension
	 * @param string $str String to check
	 * @return bool
	 */
	private function containsTabberOrGallery( $str ) {
		return !empty( self::getMarkers( $str, self::TABBER ) ) ||
			!empty( self::getMarkers( $str, self::GALLERY ) );
	}

	private function getImagesData( $value ) {
		$helper = $this->getImageHelper();
		$data = [];
		$items = array_merge( $this->getGalleryItems( $value ), $this->getTabberItems( $value ) );
		foreach ( $items as $item ) {
			$mediaItem = $this->getImageData( $item['title'], $item['label'], $item['label'] );
			if ( (bool)$mediaItem ) {
				$data[] = $mediaItem;
			}
		}
		return count( $data ) > 1 ? $helper->extendImageCollectionData( $data ) : $data;
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
	 * Prepare infobox image node data.
	 *
	 * @param $title
	 * @param $alt
	 * @param $caption
	 * @return array
	 */
	private function getImageData( $title, $alt, $caption ) {
		$helper = $this->getImageHelper();
		$titleObj = $title instanceof Title ? $title : $this->getImageAsTitleObject( $title );
		$fileObj = $helper->getFile( $titleObj );

		if ( !isset( $fileObj ) || !$this->isTypeAllowed( $fileObj->getMediaType() ) ) {
			return [];
		}

		$mediatype = $fileObj->getMediaType();
		$image = [
			'url' => $this->resolveImageUrl( $fileObj, $titleObj ),
			'name' => $titleObj ? $titleObj->getText() : '',
			'alt' => $alt ?? ( $titleObj ? $titleObj->getText() : null ),
			'caption' => $caption ?: null,
			'isImage' => in_array( $mediatype, [ MEDIATYPE_BITMAP, MEDIATYPE_DRAWING ] ),
			'isVideo' => $mediatype === MEDIATYPE_VIDEO,
			'isAudio' => $mediatype === MEDIATYPE_AUDIO,
			'source' => $this->getPrimarySource(),
			'item-name' => $this->getItemName(),
			'htmlAfter' => null,
		];

		if ( $image['isImage'] ) {
			$image = array_merge( $image, $helper->extendImageData(
				$fileObj,
				PortableInfoboxRenderService::DEFAULT_DESKTOP_THUMBNAIL_WIDTH,
				PortableInfoboxRenderService::DEFAULT_DESKTOP_INFOBOX_WIDTH
			) );
		}

		if ( $titleObj instanceof Title ) {
			// This call may produce extra HTML from, for example, the PageImages integration
			$image['htmlAfter'] = $this->getExternalParser()->addImage( $titleObj, [
				'width' => $image['width'] ?? null,
				'height' => $image['height'] ?? null,
			] );
		}

		return $image;
	}

	public function isEmpty() {
		$data = $this->getData();
		foreach ( $data as $dataItem ) {
			if ( !empty( $dataItem['url'] ) ) {
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
		$contLang = MediaWikiServices::getInstance()->getContentLanguage();

		$title = Title::makeTitleSafe(
			NS_FILE,
			FileNamespaceSanitizeHelper::getInstance()->sanitizeImageFileName( $imageName, $contLang )
		);

		return $title;
	}

	protected function getImageHelper() {
		if ( !isset( $this->helper ) ) {
			$this->helper = new PortableInfoboxImagesHelper();
		}
		return $this->helper;
	}

	/**
	 * Returns image url for given image title
	 * @param File|null $file
	 * @param Title|null $title
	 * @return string url or '' if image doesn't exist
	 */
	public function resolveImageUrl( $file, $title ) {
		global $wgPortableInfoboxUseFileDescriptionPage;

		if ( $wgPortableInfoboxUseFileDescriptionPage && $title ) {
			return $title->getLocalURL();
		}

		return $file ? $file->getUrl() : '';
	}

	/**
	 * Checks if file media type is allowed
	 * @param string $type
	 * @return bool
	 */
	private function isTypeAllowed( $type ) {
		switch ( $type ) {
			case MEDIATYPE_BITMAP:
			case MEDIATYPE_DRAWING:
				return $this->allowImage();
			case MEDIATYPE_VIDEO:
				return $this->allowVideo();
			case MEDIATYPE_AUDIO:
				return $this->allowAudio();
			default:
				return false;
		}
	}

	/**
	 * @return bool
	 */
	protected function allowImage() {
		$attr = $this->getXmlAttribute( $this->xmlNode, self::ALLOWIMAGE_ATTR_NAME );

		return !( isset( $attr ) && strtolower( $attr ) === 'false' );
	}

	/**
	 * @return bool
	 */
	protected function allowVideo() {
		$attr = $this->getXmlAttribute( $this->xmlNode, self::ALLOWVIDEO_ATTR_NAME );

		return !( isset( $attr ) && strtolower( $attr ) === 'false' );
	}

	/*
	 * @return bool
	 */
	protected function allowAudio() {
		$attr = $this->getXmlAttribute( $this->xmlNode, self::ALLOWAUDIO_ATTR_NAME );

		return !( isset( $attr ) && strtolower( $attr ) === 'false' );
	}
}
