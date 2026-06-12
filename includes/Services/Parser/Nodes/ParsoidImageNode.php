<?php

namespace PortableInfobox\Services\Parser\Nodes;

class ParsoidImageNode extends ParsoidMediaNode {
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
		return true;
	}

	/*
	 * @return bool
	 */
	protected function allowAudio() {
		return false;
	}
}
