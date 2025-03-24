<?php

namespace PortableInfobox\Services\Helpers;

use File;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class PortableInfoboxImagesHelper {
	private const MAX_DESKTOP_THUMBNAIL_HEIGHT = 500;

	/**
	 * extends image data
	 *
	 * @param File|Title|string $file image
	 * @param int $thumbnailFileWidth preferred thumbnail file width
	 * @param int|null $thumbnailImgTagWidth preferred thumbnail img tag width
	 * @return array|bool false on failure
	 */
	public function extendImageData( $file, $thumbnailFileWidth, $thumbnailImgTagWidth = null ) {
		global $wgPortableInfoboxCustomImageWidth;

		$file = $this->getFile( $file );

		if ( !$file || !in_array( $file->getMediaType(), [ MEDIATYPE_BITMAP, MEDIATYPE_DRAWING ] ) ) {
			return [];
		}

		// get dimensions
		$originalWidth = $file->getWidth();
		// we need to have different thumbnail file dimensions to support (not to have pixelated images)
		// wider infoboxes than default width
		$fileDimensions = $this->getThumbnailSizes(
			$thumbnailFileWidth,
			self::MAX_DESKTOP_THUMBNAIL_HEIGHT,
			$originalWidth,
			$file->getHeight()
		);
		$imgTagDimensions =
			empty( $thumbnailImgTagWidth )
				? $fileDimensions
				: $this->getThumbnailSizes( $thumbnailImgTagWidth,
				self::MAX_DESKTOP_THUMBNAIL_HEIGHT, $originalWidth, $file->getHeight() );

		// if custom and big enough, scale thumbnail size
		$ratio =
			!empty( $wgPortableInfoboxCustomImageWidth ) &&
			$originalWidth > $wgPortableInfoboxCustomImageWidth
				? $wgPortableInfoboxCustomImageWidth / $fileDimensions['width'] : 1;
		// get thumbnail
		$thumbnail = $file->transform( [
			'width' => round( $fileDimensions['width'] * $ratio ),
			'height' => round( $fileDimensions['height'] * $ratio ),
		] );
		$thumbnail2x = $file->transform( [
			'width' => round( $fileDimensions['width'] * $ratio * 2 ),
			'height' => round( $fileDimensions['height'] * $ratio * 2 ),
		] );
		if ( !$thumbnail || $thumbnail->isError() || !$thumbnail2x || $thumbnail2x->isError() ) {
			return [];
		}

		return [
			'height' => intval( $imgTagDimensions['height'] ),
			'width' => intval( $imgTagDimensions['width'] ),
			'thumbnail' => $thumbnail->getUrl(),
			'thumbnail2x' => $thumbnail2x->getUrl()
		];
	}

	/**
	 * @param array $images
	 * @return array
	 */
	public function extendImageCollectionData( $images ) {
		$images = array_map(
			static function ( $image, $index ) {
				$image['ref'] = $index + 1;

				if ( empty( $image['caption'] ) ) {
					$image['caption'] = $image['name'];
				}

				return $image;
			},
			$images,
			array_keys( $images )
		);

		$images[0]['isFirst'] = true;
		return $images;
	}

	/**
	 * Calculates image dimensions based on preferred width and max acceptable height
	 *
	 * @param int $preferredWidth
	 * @param int $maxHeight
	 * @param int $originalWidth
	 * @param int $originalHeight
	 * @return array [ 'width' => int, 'height' => int ]
	 */
	public function getThumbnailSizes( $preferredWidth, $maxHeight, $originalWidth, $originalHeight ) {
		// Prevent division by zero by ensuring width and height are valid
		if ( $originalWidth == 0 && $originalHeight == 0 ) {
			// Return default sizes if both original dimensions are zero
			return [ 'height' => 0, 'width' => 0 ];
		}

		// Handle case where only the original width is zero
		if ( $originalWidth == 0 ) {
			$height = min( $maxHeight, $originalHeight );
			// No width can be calculated
			return [ 'height' => round( $height ), 'width' => 0 ];
		}

		// Handle case where only the original height is zero
		if ( $originalHeight == 0 ) {
			$width = min( $preferredWidth, $originalWidth );
			// No height can be calculated
			return [ 'height' => 0, 'width' => round( $width ) ];
		}

		// Prevent issues with invalid maxHeight or preferredWidth
		if ( $preferredWidth == 0 || $maxHeight == 0 ) {
			// If either maxHeight or preferredWidth is zero, return the original dimensions
			return [ 'height' => $originalHeight, 'width' => $originalWidth ];
		}

		// Standard aspect ratio handling if all dimensions are valid
		if ( ( $originalHeight / $originalWidth ) > ( $maxHeight / $preferredWidth ) ) {
			$height = min( $maxHeight, $originalHeight );
			$width = min( $preferredWidth, $height * $originalWidth / $originalHeight );
		} else {
			$width = min( $preferredWidth, $originalWidth );
			$height = min( $maxHeight, $width * $originalHeight / $originalWidth );
		}

		return [ 'height' => round( $height ), 'width' => round( $width ) ];
	}

	/**
	 * Get file
	 *
	 * Note: this method turns a string $file into an object, affecting the calling code version
	 * of this variable
	 *
	 * @param File|Title|string $file
	 * @return File|null file
	 */
	public function getFile( $file ) {
		if ( is_string( $file ) ) {
			$file = Title::newFromText( $file, NS_FILE );
		}

		if ( $file instanceof Title ) {
			$repoGroup = MediaWikiServices::getInstance()->getRepoGroup();

			$file = $repoGroup->findFile( $file );
		}

		if ( $file instanceof File && $file->exists() ) {
			return $file;
		}

		return null;
	}
}
