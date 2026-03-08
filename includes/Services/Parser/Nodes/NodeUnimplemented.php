<?php

namespace PortableInfobox\Services\Parser\Nodes;

use Exception;

class NodeUnimplemented extends Node {

	/** @throws UnimplementedNodeException */
	public function getData(): never {
		throw new UnimplementedNodeException( $this->getType() );
	}
}

// phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
class UnimplementedNodeException extends Exception {
}
