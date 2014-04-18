<?php

namespace HttpUtil\Header;

class AcceptLanguage extends NegotiatedHeader {
	
	protected function nomatch(array &$results, array $accepted) {
		
		$accept = array();
		
		foreach($accepted as $key => $val) {
			$accept[substr($key, 0, 2)] = $key;
		}
		
		$found = array();
		
		foreach($this->values as $value) {
			list($prefix,) = explode('-', $value->value);
			if (isset($accept[$prefix])) {
				$i = ($value->quality*90);
				$found[$i] = $prefix;
				$results[$i] = $accept[$prefix];
			}
		}
		
		if (! empty($found)) {
			ksort($found);
			$first = array_shift($found);
			return $accept[$first];
		}
	}
	
}
