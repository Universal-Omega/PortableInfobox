<?php
namespace PortableInfobox\Parser\Nodes;

class NodeUnimplemented extends Node {

	public function getData() {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod LSP violation.

		throw new UnimplementedNodeException( $this->getType() );
	}
}

// phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
class UnimplementedNodeException extends \Exception {
}
