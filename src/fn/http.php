<?php

/**
 * Returns a valid HTTP date.
 * If no timestamp is given, uses current time.
 * 
 * @param int $timestamp Unix timestamp
 * @return string Date formatted regarding RFC 1123.
 */
function http_date($timestamp = null) {
	return gmdate('D, d M Y H:i:s', isset($timestamp) ? $timestamp : time()).' GMT';
}

/**
 * Redirects browser via Location header to given URL and exits.
 * 
 * @param string $url	URL to redirect to. Used in "Location:" header.
 * @return void
 */
function http_redirect($url, array $params = null, $session = false, $status = 0) {
		
	if (headers_sent($filename, $line)) {
		throw new RuntimeException("Cannot redirect to '$url' - Output already started in $filename on line $line</p>");
	}
	
	if (isset($params)) {
		$url .= '?'.http_build_query($params, null, '&') . ($session ? '&'.SID : '');
	} else if ($session) {
		$url .= '?'.SID;
	}
	
	if (0 !== $status) {
		if ((300 < $status && $status < 308) || 201 === $status) {
			http_send_status($status);
		}
		// status sent automatically unless 201 or 3xx set
	}
	
	header_remove('Last-Modified');
	header('Expires: Mon, 12 Dec 1982 06:00:00 GMT');
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	header("Location: $url");
	exit;
}

/**
 * Sends response status code, as well as an additional "Status" header.
 *
 * @param int $code HTTP status code to send
 * @return void
 */
function http_send_status($code) {
	http_response_code($code);
	header("Status: $code ". http_response_code_desc($code));
}

/**
 * Sends the content-type header.
 * 
 * @param string $content_type Content-type, must contain both primary/secondary.
 * @param string|null $charset Optional charset to send.
 * @return boolean True if sent, false/warning error if missing a part.
 */
function http_send_content_type($content_type = 'application/x-octetstream', $charset = null) {
		
	if (false === strpos($content_type, '/')) {
		if (null === $content_type = mimetype($content_type)) {
			$msg = 'Content type should contain primary and secondary parts like "primary/secondary".';
			trigger_error($msg, E_USER_WARNING);
			return false;
		}
	}
	
	$header_string = 'Content-Type: '. $content_type;
	if (null !== $charset) {
		$header_string .= '; charset='. strtoupper($charset);
	}
	
	header($header_string, true);
	return true;
}

/**
 * Sends a file download, invoking the browser's "Save As..." dialog.
 * 
 * Exits after sending. Unlike the HTTP extension version, this function
 * also sends Content-Type, Content-Disposition, and "no-cache" headers.
 * 
 * @param string $file Filepath to file to send.
 * @param string $filetype File type to send as, default is 'application/octet-stream'.
 * @param string $filename Optional name to show to user - defaults to basename($file).
 * @return void
 */
function http_send_file($file, $filetype = 'download', $filename = null) {
		
	if (! file_exists($file) || ! is_readable($file)) {
		throw new RuntimeException("Cannot send unknown or unreadable file $file.");
	}
	
	if (headers_sent($_file, $_line)) {
		throw new RuntimeException("Cannot send file - output started in '$_file' on line '$_line'.");
	}
	
	if (! isset($filename)) {
		$filename = basename($file);
	}
	
	header('Expires: 0');
	header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	
	http_send_content_type(mimetype($filetype, 'application/octet-stream'));
	
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	// request is invalid without Content-Length
	header('Content-Length: '.filesize($file));
	header('Content-Transfer-Encoding: binary');
	header('Connection: close');
	
	readfile($file);
	
	exit;
}

/**
 * Matches the contents of a given HTTP request header.
 * 
 * @param string $name		Header name, lowercase, without 'HTTP_'.
 * @param string $value		Value to match.
 * @param bool $match_case	Whether to match case-sensitively, default false.
 * @return boolean			True if match, otherwise false.
 */
function http_match_request_header($name, $value, $match_case = false) {
	if (null === $header = http_request_header($name)) {
		return false;
	}
	return $match_case
		? (0 === strcmp($header, $value))
		: (0 === strcasecmp($header, $value));
}

/**
 * Determines response content-type by matching the 'Accept' 
 * request header to an accepted content-type.
 * 
 * Returns the first content-type in the header that matches
 * one of the given types. If none is matched, returns the
 * default content-type (first array item).
 * 
 * @param array $accept	Indexed array of accepted content-types.
 * @return string		Matched content-type, or first array item if no match.
 */
function http_negotiate_content_type(array $accept) {
	if (null === $header = http_request_header('accept')) {
		return $accept[0];
	}
	$object = new \HttpUtil\Header\NegotiatedHeader('accept', $header);
	return $object->negotiate($accept);
}
