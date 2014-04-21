<?php
/**
 * (Some) functions provided by PECL HTTP extension (v1).
 */

/**
 * Returns a valid HTTP date using given timestamp or current time if none given.
 *
 * @param int $timestamp Unix timestamp
 * @return string Date formatted regarding RFC 1123.
 */
function http_date($timestamp = null) {
	return gmdate('D, d M Y H:i:s \G\M\T', isset($timestamp) ? $timestamp : time());
}

/**
 * Redirects browser via Location header to given URL and exits.
 *
 * @param string $url Redirect URL - used in "Location" header.
 * @param int $status [Optional] HTTP status code to send - should be 201 a 30x.
 * @param boolean $exit [Optional] Whether to 'exit' after sending headers. Default true.
 * @return void
 */
function http_redirect($url, $status = 0, $exit = true) {

	if (headers_sent($filename, $line)) {
		throw new RuntimeException("Cannot redirect to '$url' - Output already started in $filename on line $line</p>");
	}

	if (0 !== $status) {
		if ((300 < $status && $status < 308) || 201 === $status) {
			http_send_status($status);
		}
		// 302 sent automatically unless 201 or 3xx set
	}

	header('Expires: Mon, 12 Dec 1982 06:00:00 GMT');
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	header_remove('Last-Modified');
	// no space is a fix for msie
	header("Location:$url");

	if ($exit) {
		exit ;
	}
}

/**
 * Sends response status code, as well as an additional "Status" header.
 *
 * @param int $code HTTP status code to send
 * @return void
 */
function http_send_status($code) {
	http_response_code($code);
	// don't replace in case we're rfc2616
	header("Status: $code ".http_response_code_desc($code), false);
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

	$header_string = 'Content-Type: '.$content_type;
	if (null !== $charset) {
		$header_string .= '; charset='.strtoupper($charset);
	}

	header($header_string, true);
	return true;
}

/**
 * Send the "Content-Disposition" header.
 * 
 * @param string $disposition Content disposition (e.g. "attachment").
 * @param string $filename [Optional] Populates "filename" in header.
 * @param string $name [Optional] Populates "name" in header.
 */
function http_send_content_disposition($disposition = 'attachment', $filename = null, $name = null) {

	$string = 'Content-Disposition: '.$disposition;

	if (isset($filename)) {
		$string .= '; filename="'.$filename.'"';
	}
	if (isset($name)) {
		$string .= '; name="'.$name.'"';
	}

	header($string, false);
}

/**
 * Sends a file download, invoking the browser's "Save As..." dialog.
 *
 * Exits after sending. Unlike the HTTP extension version, this function
 * also sends Content-Type, Content-Disposition, and "no-cache" headers.
 *
 * @param string $file Filepath to file to send.
 * @param string $filetype File type to send as, default is 
 * 'application/octet-stream'.
 * @param string $filename Optional name to show to user - defaults to
 * basename($file).
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

	http_send_content_disposition('attachment', $filename);

	// invalid without Content-Length
	header('Content-Length: '.filesize($file));
	header('Content-Transfer-Encoding: binary');
	header('Connection: close');

	readfile($file);

	exit ;
}

/**
 * Returns request body string.
 *
 * Stores the string in a static variable, thus providing a way to
 * get php://input more than once. Of course, this function will
 * not work if read before (e.g. via fopen(), etc.).
 *
 * Note: POST requests with "multipart/form-data" will not work with php://input
 *
 * @see \HttpUtil\Request\Body::getContents()
 * 
 * @return string HTTP request body.
 */
function http_get_request_body() {
	return \HttpUtil\Request\Body::getContents();
}

/**
 * Returns a read-only file handle for the request body stream.
 * 
 * Uses fopen("php://input") with binary flag.
 * 
 * @see \HttpUtil\Request\Body::getHandle()
 * 
 * @return resource Read-only file handle for php://input stream.
 */
function http_get_request_body_stream() {
	return \HttpUtil\Request\Body::getHandle();
}

/**
 * Returns array of HTTP request headers.
 *
 * Cross-platform function to retrive the current HTTP request headers.
 * 
 * Uses apache_request_headers() if available, otherwise uses $_SERVER global.
 * Normalizes header labels (array keys) to lowercased values, stripped of
 * any "http" prefix and with underscores converted to dashes (e.g. "accept-language").
 *
 * @return array HTTP request headers, keys stripped of "HTTP_" and lowercase.
 */
function http_get_request_headers() {
	static $headers;
	if (isset($headers)) {
		return $headers;
		// get once per request
	}
	if (function_exists('apache_request_headers')) {
		$_headers = apache_request_headers();
	} else {
		$_headers = array();
		$misfits = array('CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5', 'AUTH_TYPE', 'PHP_AUTH_USER', 'PHP_AUTH_PW', 'PHP_AUTH_DIGEST');
		foreach ( $_SERVER as $key => $value ) {
			if (0 === strpos($key, 'HTTP_')) {
				$_headers[$key] = $value;
			} else if (in_array($key, $misfits, true)) {
				$_headers[$key] = $value;
			}
		}
	}
	$headers = array();
	foreach ( $_headers as $key => $value ) {
		$key = str_replace(array('http_', '_'), array('', '-'), strtolower($key));
		$headers[$key] = $value;
	}
	return $headers;
}

/**
 * Fetches a single HTTP request header.
 *
 * @param string $name		Header name, lowercase, without 'HTTP_' prefix.
 * @return string|null		Header value, if set, otherwise null.
 */
function http_get_request_header($name) {
	$headers = http_get_request_headers();
	return isset($headers[$name]) ? $headers[$name] : null;
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
	if (null === $header = http_get_request_header($name)) {
		return false;
	}
	return $match_case 
		? 0 === strcmp($header, $value) 
		: 0 === strcasecmp($header, $value);
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
	if (null === $header = http_get_request_header('accept')) {
		return $accept[0];
	}
	$object = new \HttpUtil\Header\NegotiatedHeader('accept', $header);
	return $object->negotiate($accept);
}

/**
 * Determines best language from the 'Accept-Language' request header
 * given an array of accepted languages.
 *
 * Tries to find a direct match (e.g. 'en-US' to 'en-US') but if none is
 * found, finds the best match determined by prefix (e.g. "en").
 * 
 * @param array $accept Indexed array of accepted languages.
 * @param array &$result If given an array, will be populated with negotiation results.
 * @return string Best-match language, or first language given if no match.
 */
function http_negotiate_language(array $accept, &$result = null) {
	if (null === $header = http_get_request_header('accept-language')) {
		return $accept[0];
	}
	$object = new \HttpUtil\Header\AcceptLanguage('accept-language', $header);
	return $object->negotiate($accept, $result);
}
