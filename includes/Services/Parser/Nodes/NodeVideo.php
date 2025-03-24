<?php

namespace PortableInfobox\Services\Parser\Nodes;

class NodeVideo extends NodeMedia {

	public function getType() {
		return 'media';
	}

	/*
	 * @return bool
	 */
	protected function allowImage() {
		return false;
	}

	/*
	 * @return bool
	 */
	protected function allowVideo() {
		return true;
	}

	/*
	 * @return bool
	 */
	protected function allowAudio() {
		return false;
	}
}
