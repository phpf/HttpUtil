<?php
/**
 * @package phpf\HttpUtil
 */

/**
 * Retrieve information about the current environment.
 *
 * Returns one of:
 *  * (bool) Whether SSL is enabled on the server
 *  * (string) Server host name, useful for cookies
 *  * (string) Server domain, including 'http' scheme and host
 *
 * * "domain" returns a string built using the 'ssl' and 'host' environment
 * variables with http(s) scheme. Returned without trailing slash.
 * * "host" returns a string built from $_SERVER['HTTP_HOST'] and the dirname of
 * $_SERVER['SCRIPT_NAME']. Returned without scheme or trailing slash.
 * * "ssl" returns true for any of the following:
 * *	* $_SERVER['HTTPS'] == ('on' || 1)
 * *	* $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
 * *	* $_SERVER['SERVER_PORT'] == '443'
 */
function http_env($name) {
	
	$i = \HttpUtil\Env::instance();
	
	switch(strtolower($name)) {
		case 'ssl' :
			return $i->isSsl();
		case 'host' :
			return $i->getHost();
		case 'domain' :
			return $i->getDomain();
		default :
			throw new InvalidArgumentException("Unknown HTTP environment ID.");
			return false;
	}
}

/**
 * Returns an associative array of cache headers suitable for use in header().
 *
 * Returns the 'Cache-Control', 'Expires', and 'Pragma' headers given the
 * expiration offset in seconds (from current time). If '0' or a value which
 * evaluates to empty is given, returns "no-cache" headers, with Cache-Control
 * set to 'no-cache, must-revalidate, max-age=0', 'Expires' set to a date in the
 * past, and 'Pragma' set to 'no-cache'.
 *
 * @param int $expires_offset Expiration in seconds from now.
 * @return array Associative array of cache headers.
 */
function http_build_cache_headers($expires_offset = 86400) {
	return \HttpUtil\HTTP::instance()->buildCacheHeaders($expires_offset);
}

/**
 * Parses an arbitrary request header to determine which value to use in
 * response.
 *
 * This is a general-use function; specific implementations exist for
 * content-type and language negotiation.
 *
 * @see http_negotiate_content_type()
 * @see http_negotiate_language()
 *
 * @param string $name	Request header name, lowercase.
 * @param array $accept Indexed array of accepted values.
 * @return string 		Matched value (selected by quality, then position),
 * 						or first array value if no match found.
 */
function http_negotiate_request_header($name, array $accept) {
	return \HttpUtil\HTTP::instance()->negotiateRequestHeader($name, $accept);
}

/**
 * Determines if $value is in the contents of $name request header.
 *
 * @param string $name		Header name, lowercase, without 'HTTP_'.
 * @param string $value		Value to search for.
 * @param bool $match_case	Whether to search case-sensitive, default false.
 * @return boolean			True if found, otherwise false.
 */
function http_in_request_header($name, $value, $match_case = false) {
	return \HttpUtil\HTTP::instance()->inRequestHeader($name, $value, $match_case);
}

/**
 * Returns a HTTP status header description.
 *
 * @param int $code		HTTP status code.
 * @return string		Status description string, or empty string if invalid.
 */
function http_response_code_desc($code) {
	return \HttpUtil\StatusDescription::get(intval($code), '');
}

/**
 * Returns Internet Media Type (MIME) for given filetype.
 *
 * @param string $filetype	Filetype (e.g. 'js', 'xls', 'ogg').
 * @param string $default	Value to return if mime not found.
 * @return string			MIME, if found, otherwise default.
 */
function mimetype($filetype, $default = 'application/octet-stream') {
	return \HttpUtil\MIME::get(strtolower($filetype), $default);
}

/**
 * Returns a filetype from MIME.
 *
 * @param string $mimetype MIME
 * @param mixed $default Default value. default null
 * @return string Filetype for MIME, or default if not found.
 */
function mime2filetype($mimetype, $default = null) {
	return \HttpUtil\MIME::lookup($mimetype, $default);
}
