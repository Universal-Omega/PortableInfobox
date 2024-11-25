<?php

namespace PageImages\Hooks {
	use File;
	use MediaWiki\Http\HttpRequestFactory;
	use Parser;
	use RepoGroup;
	use TitleFactory;
	use WANObjectCache;
	use Wikimedia\Rdbms\ILBFactory;

	class ParserFileProcessingHookHandlers {

		public function __construct(
			RepoGroup $repoGroup,
			WANObjectCache $mainWANObjectCache,
			HttpRequestFactory $httpRequestFactory,
			ILBFactory $dbLoadBalancerFactory,
			TitleFactory $titleFactory
		) {
		}

		public function onParserModifyImageHTML(
			Parser $parser,
			File $file,
			array $params,
			string &$html
		): void {
		}
	}
}
