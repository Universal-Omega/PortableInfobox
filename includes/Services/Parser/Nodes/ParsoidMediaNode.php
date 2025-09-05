<?php

namespace PortableInfobox\Services\Parser\Nodes;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use PortableInfobox\Parsoid\ParsoidPortableInfoboxRenderService;
use PortableInfobox\Services\Helpers\FileNamespaceSanitizeHelper;
use PortableInfobox\Services\Helpers\PortableInfoboxImagesHelper;

class ParsoidMediaNode extends Node {

    private ?PortableInfoboxImagesHelper $helper;

    private const ALLOWIMAGE_ATTR_NAME = 'image';
	private const ALLOWVIDEO_ATTR_NAME = 'video';
	private const ALLOWAUDIO_ATTR_NAME = 'audio';
	private const ALT_TAG_NAME = 'alt';
	private const CAPTION_TAG_NAME = 'caption';

    /**
     * Return the data for the image
     * return array
     */
    public function getData(): array {
		if ( !isset( $this->data ) ) {
			$this->data = [];

			// value passed to source parameter (or default)
			// force the $value to be a string so that containsTabberOrGallery()
			// doesn't fatal
			$value = $this->getRawValueWithDefault( $this->xmlNode );
            if ( $this->containsTabberOrGallery( (string)$value ) ) {
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
     * Checks if string contains raw <gallery> or <tabber> tags using a hacky regex. With the legacy Parser,
     * by the time this function runs in NodeMedia::class, the intial parse has already replaced the $str with a strip marker
     * we are not in such environment and we will receieve the raw wikitext passed by the user
     * @param string $value wikitext passed in the parameter
     * @return bool
     */
    private function containsTabberOrGallery( string $value ): bool {
        // <gallery></gallery>
        if ( preg_match( '/<gallery\b[^>]*>/i', $value ?? '' ) ) {
            return true;
        }
        
        // <tabber></tabber>
        if ( preg_match( '/<tabber\b[^>]*>/i', $value ?? '' ) ) {
            return true;
        }
        
        return false;
    }

    /**
     * Get the data about the image (or images if tabber/gallery) and return it as an array
     * @TODO: revisit this later, see comment on ParsoidMediaWikiParser::extractGallery for why this
     * is a bad idea - but it works
     * @param string $value the wikitext gallery
     * @param mixed $value
     */
    private function getImagesData( string $value ) {
		$helper = $this->getImageHelper();
		$data = [];
        $parser = $this->getExternalParser();
        $items = $parser->extractGallery( $value );
		// @TODO: do tabbers also 
        foreach ( $items as $item ) {
			$mediaItem = $this->getImageData( $item['title'], $item['label'], $item['label'] );
			if ( (bool)$mediaItem ) {
				$data[] = $mediaItem;
			}
		}

        return count( $data ) > 1 ? $helper->extendImageCollectionData( $data ) : $data;
	}

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
				ParsoidPortableInfoboxRenderService::DEFAULT_DESKTOP_THUMBNAIL_WIDTH,
				ParsoidPortableInfoboxRenderService::DEFAULT_DESKTOP_INFOBOX_WIDTH
			) );
		}

		return $image;
	}

    /**
     * Get the image as a title object
     * @param $imageName the image name 
     * @return Title|null
     */
    private function getImageAsTitleObject( $imageName ): ?Title {
		$contLang = MediaWikiServices::getInstance()->getContentLanguage();

		$title = Title::makeTitleSafe(
			NS_FILE,
			FileNamespaceSanitizeHelper::getInstance()->sanitizeImageFileName( $imageName, $contLang )
		);

		return $title;
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
     * Get an instance of the PortableInfoboxImageHelper
     * @return PortableInfoboxImagesHelper
     */
    protected function getImageHelper() {
		if ( !isset( $this->helper ) ) {
			$this->helper = new PortableInfoboxImagesHelper();
		}
		return $this->helper;
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

    public function isEmpty() {
		$data = $this->getData();
		foreach ( $data as $dataItem ) {
			if ( !empty( $dataItem['url'] ) ) {
				return false;
			}
		}
		return true;
	}

	public function getType() {
		return 'media';
	}
}