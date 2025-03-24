<?php

namespace PortableInfobox\Tests\Services\Helpers;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use PortableInfobox\Services\Helpers\FileNamespaceSanitizeHelper;
use ReflectionClass;

/**
 * @group PortableInfobox
 * @covers \PortableInfobox\Services\Helpers\FileNamespaceSanitizeHelper
 */
class FileNamespaceSanitizeHelperTest extends MediaWikiIntegrationTestCase {

	/** @var FileNamespaceSanitizeHelper $fileNamespaceSanitizeHelper */
	private $fileNamespaceSanitizeHelper;

	protected function setUp(): void {
		parent::setUp();

		$class = new ReflectionClass( FileNamespaceSanitizeHelper::class );
		$instance = $class->getProperty( 'instance' );
		$instance->setAccessible( true );
		$filePrefixRegex = $class->getProperty( 'filePrefixRegex' );
		$filePrefixRegex->setAccessible( true );

		$this->fileNamespaceSanitizeHelper = FileNamespaceSanitizeHelper::getInstance();
		$instance->setValue( $this->fileNamespaceSanitizeHelper, null );
		$filePrefixRegex->setValue( $this->fileNamespaceSanitizeHelper, null );
	}

	/**
	 * @param $inputFileName
	 * @param $contentLanguageCode
	 * @param $fileNamespaceAlias
	 * @param $expectedOutput
	 * @param $description
	 * @dataProvider sanitizeImageFilenameDataProvider
	 */
	public function testSanitizeImageFilename(
		$inputFileName, $contentLanguageCode, $fileNamespaceAlias, $expectedOutput, $description
	) {
		global $wgNamespaceAliases;

		$language = MediaWikiServices::getInstance()->getLanguageFactory()
			->getLanguage( $contentLanguageCode );

		if ( isset( $fileNamespaceAlias ) ) {
			$wgNamespaceAliases[$fileNamespaceAlias] = NS_FILE;
		}

		$actualOutput = $this->fileNamespaceSanitizeHelper->sanitizeImageFileName(
			$inputFileName, $language
		);

		$this->assertEquals( $expectedOutput, $actualOutput, $description );
	}

	public function sanitizeImageFilenameDataProvider() {
		return [
			[
				'filename.jpg',
				'en',
				null,
				'filename.jpg',
				'Plain filename'
			],
			[
				'File:filename.jpg',
				'en',
				null,
				'filename.jpg',
				'Filename with namespace'
			],
			[
				'Plik:filename.jpg',
				'pl',
				null,
				'filename.jpg',
				'Filename with localized namespace'
			],
			[
				'Grafika:filename.jpg',
				'pl',
				null,
				'filename.jpg',
				'Filename with localized namespace alias'
			],
			[
				'File:filename.jpg|300px',
				'en',
				null,
				'filename.jpg',
				'Filename with namespace and width'
			],
			[
				'[[File:filename.jpg|300px|lorem ipsum]]',
				'en',
				null,
				'filename.jpg',
				'Link to filename with namespace, width and caption'
			],
			[
				'[[File:filename.jpg|lorem ipsum]]',
				'en',
				null,
				'filename.jpg',
				'Link to filename with namespace and caption'
			],
			[
				'{{File:filename.jpg|lorem ipsum}}',
				'en',
				null,
				'{{File:filename.jpg',
				'Non-file string; sanitized, though useless'
			],
			[
				'',
				'en',
				null,
				'',
				'Empty file name'
			],
			[
				'[[File:image.jpg|300px|lorem ipsum]]',
				'es',
				null,
				'image.jpg',
				'Link to filename with canonical namespace, width and caption on a non-EN wiki'
			],
			[
				'[[File:image.jpg|lorem ipsum]]',
				'es',
				null,
				'image.jpg',
				'Link to filename with canonical namespace and caption on a non-EN wiki'
			],
			[
				'<gallery>' . PHP_EOL .
				'</gallery>' . PHP_EOL,
				'en',
				null,
				'',
				'Empty gallery'
			],
			[
				'<gallery></gallery>',
				'en',
				null,
				'',
				'Empty gallery'
			],
			[
				'<gallery />',
				'en',
				null,
				'',
				'Empty gallery'
			],
			[
				'<gallery>' . PHP_EOL .
				'image.jpg' . PHP_EOL .
				'</gallery>' . PHP_EOL,
				'en',
				null,
				'image.jpg',
				'Gallery with one image'
			],
			[
				'<gallery>' . PHP_EOL .
				'File:image.jpg' . PHP_EOL .
				'</gallery>' . PHP_EOL,
				'en',
				null,
				'image.jpg',
				'Gallery with one image with canonical namespace',
			],
			[
				'<gallery>' . PHP_EOL .
				'文件名óśłżźćńę?.jpg' . PHP_EOL .
				'Image010.jpg' . PHP_EOL .
				'Image009.jpg' . PHP_EOL .
				'</gallery>' . PHP_EOL,
				'en',
				null,
				'文件名óśłżźćńę?.jpg',
				'Gallery with diacritics and UTF characters'
			],
			[
				PHP_EOL .
				PHP_EOL,
				'en',
				null,
				'',
				'Content of empty gallery with newlines'
			],
			[
				'',
				'en',
				null,
				'',
				'Content of empty gallery'
			],
			[
				PHP_EOL .
				'image.jpg' . PHP_EOL .
				PHP_EOL,
				'en',
				null,
				'image.jpg',
				'Content of gallery with one image'
			],
			[
				PHP_EOL .
				'File:image.jpg' . PHP_EOL .
				PHP_EOL,
				'en',
				null,
				'image.jpg',
				'Content of gallery with one image with canonical namespace',
			],
			[
				PHP_EOL .
				'文件名óśłżźćńę?.jpg' . PHP_EOL .
				'Image010.jpg' . PHP_EOL .
				'Image009.jpg' . PHP_EOL .
				PHP_EOL,
				'en',
				null,
				'文件名óśłżźćńę?.jpg',
				'Content of gallery with diacritics and UTF characters'
			],
			[
				'Image:filename.jpg',
				'en',
				null,
				'filename.jpg',
				'Filename with alias to namespace'
			],
			[
				'[[File:Su-47_-iDOLM%40STER_Miki-EX-.jpg|300px]]',
				'en',
				null,
				'Su-47_-iDOLM@STER_Miki-EX-.jpg',
				'Link to filename with canonical namespace, width urlencoded character in the middle'
			],
			[
				'[[Tập tin:Naruto-Opening01_222.jpg|200px]]',
				'vi',
				null,
				'Naruto-Opening01_222.jpg',
				'File namespace that include a space'
			],
			[
				'[[いくつかのファイ ルテスト:Naruto-Opening01_222.jpg|200px]]',
				'en',
				"いくつかのファイ_ルテスト",
				'Naruto-Opening01_222.jpg',
				'File namespace that include a space'
			],
			[
				'[[File:Blaabla+2plus+.png|300px]]',
				'en',
				null,
				'Blaabla+2plus+.png',
				'Link to filename with canonical namespace, width and plus (+) characters'
			],
			[
				'[[File:Luke+1.jpg]]',
				'en',
				null,
				'Luke+1.jpg',
				'Link to filename with canonical namespace, width and plus (+) characters'
			],
			[
				'[[file:Luke+1.jpg]]',
				'en',
				null,
				'Luke+1.jpg',
				'Link to filename with upper and lower case letters in namespace'
			],
			[
				'[[файл:Luke+1.jpg]]',
				'ru',
				null,
				'Luke+1.jpg',
				'Link to filename with lower case multibyte letter in namespace'
			],
			[
				'[[filE:Luke+1.jpg]]',
				'en',
				null,
				'filE:Luke+1.jpg',
				'Handle case insensivity only for the first char'
			],
			[
				'[[file:luke+1.jpg]]',
				'en',
				null,
				'luke+1.jpg',
				'Link to filename with lower case letters'
			]
		];
	}

	/**
	* @param $wikitext
	* @param $expectedOutput
	*
	* @dataProvider removeImageParamsDataProvider
	*/
	public function testRemoveImageParams( $wikitext, $expectedOutput ) {
		$actualOutput = $this->fileNamespaceSanitizeHelper->removeImageParams( $wikitext );

		$this->assertEquals( $expectedOutput, $actualOutput );
	}

	public function removeImageParamsDataProvider() {
		return [
			[
				'File:image.jpg|300px|lorem ipsum',
				'File:image.jpg',
			],
			[
				'File:image.jpg|300px',
				'File:image.jpg',
			],
		];
	}
}
