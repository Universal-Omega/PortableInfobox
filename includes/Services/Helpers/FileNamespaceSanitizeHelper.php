<?php

namespace PortableInfobox\Services\Helpers;

use MediaWiki\Language\Language;
use MediaWiki\MediaWikiServices;

// original class & authors:
// https://github.com/Wikia/app/blob/dev/includes/wikia/helpers/FileNamespaceSanitizeHelper.php
class FileNamespaceSanitizeHelper {

	private static $instance = null;
	private $filePrefixRegex = [];

	private function __construct() {
	}

	/**
	 * @return null|self
	 */
	public static function getInstance() {
		if ( self::$instance === null ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * @param Language $contLang
	 * Used as local cache for getting string to remove
	 */
	private function getFilePrefixRegex( $contLang ) {
		global $wgNamespaceAliases;
		$langCode = $contLang->getCode();
		if ( empty( $this->filePrefixRegex[$langCode] ) ) {
			$fileNamespaces = [
				MediaWikiServices::getInstance()
					->getNamespaceInfo()
					->getCanonicalName( NS_FILE ),
				$contLang->getNamespaces()[NS_FILE],
			];

			$aliases = array_merge( $contLang->getNamespaceAliases(), $wgNamespaceAliases );
			foreach ( $aliases as $alias => $namespaceId ) {
				if ( $namespaceId == NS_FILE ) {
					$fileNamespaces[] = $alias;
				}
			}

			// be able to match user-provided file namespaces that may contain both underscores and spaces
			$fileNamespaces = array_map( static function ( $namespace ) {
				return mb_ereg_replace( '_', '(_|\ )', $namespace );
			}, $fileNamespaces );

			// be able to match both upper- and lowercase first letters of the namespace
			$lowercaseFileNamespaces = array_map( static function ( $namespace ) {
				return mb_convert_case( $namespace, MB_CASE_LOWER, "UTF-8" );
			}, $fileNamespaces );

			$namespaces = array_merge( $fileNamespaces, $lowercaseFileNamespaces );
			$this->filePrefixRegex[$langCode] = '^(' . implode( '|', $namespaces ) . '):';
		}

		return $this->filePrefixRegex[$langCode];
	}

	/**
	 * @param string|null $filename
	 * @param Language $contLang
	 *
	 * @return mixed
	 */
	public function sanitizeImageFileName( $filename, $contLang ) {
		$plainText = $this->convertToPlainText( $filename ?? '' );
		$filePrefixRegex = $this->getFilePrefixRegex( $contLang );
		$textLines = explode( PHP_EOL, $plainText );

		foreach ( $textLines as $potentialFilename ) {
			$filename = $this->extractFilename( $potentialFilename, $filePrefixRegex );
			if ( $filename ) {
				return $filename;
			}

		}

		return $plainText;
	}

	/**
	 * @param string $filename
	 *
	 * @return string
	 */
	private function convertToPlainText( $filename ) {
		// strip HTML tags
		$filename = strip_tags( $filename );
		// replace the surrounding whitespace
		$filename = trim( $filename );

		return $filename;
	}

	/**
	 * @param string $potentialFilename
	 * @param string $filePrefixRegex
	 *
	 * @return string|null
	 */
	private function extractFilename( $potentialFilename, $filePrefixRegex ) {
		$trimmedFilename = trim( $potentialFilename, '[]' );
		$unprefixedFilename = mb_ereg_replace( $filePrefixRegex, '', $trimmedFilename );
		$filenameParts = explode( '|', $unprefixedFilename );

		if ( !empty( $filenameParts[0] ) ) {
			return rawurldecode( $filenameParts[0] );
		}

		return self::removeImageParams( $unprefixedFilename );
	}

	/**
	 * For given file wikitext without brackets, return it without any params
	 * or null if empty string
	 *
	 * @param string $fileWikitext
	 * @return string | null
	 */
	public function removeImageParams( $fileWikitext ) {
		$filenameParts = explode( '|', $fileWikitext );
		if ( empty( $filenameParts[0] ) ) {
			return null;
		}

		return urldecode( $filenameParts[0] );
	}
}
