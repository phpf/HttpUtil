<?php

namespace HttpUtil\Header;

class MultiValueHeader extends BaseHeader {
	
	/**
	 * @var array
	 */
	protected $values = array();
	
	/**
	 * Adds a Value object to the array.
	 * @param Value
	 * @return $this
	 */
	public function addValue(Value $value) {
		$this->values[] = $value;
		return $this;
	}
	
	/**
	 * Returns the array of values.
	 * @return array Indexed array of Value objects.
	 */
	public function getValues() {
		return $this->values;
	}
	
}
