<?php

namespace PortableInfobox\Services\Parser\Nodes;

class NodeAudio extends NodeMedia {
	/*
	 * @return string
	 */
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
		return false;
	}

	/*
	 * @return bool
	 */
	protected function allowAudio() {
		return true;
	}
}
