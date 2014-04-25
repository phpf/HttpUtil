<?php
/**
 * HttpUtil - HTTP utility library.
 *
 * @author wells
 * @license MIT
 * @version 0.1.1
 *
 * If not using Composer, you can register the "HttpUtil" namespace
 * with a PSR-4 autoloader using the "src/" directory as the base path.
 */

/**
 * http_response_code() for PHP < 5.4
 */
if (! function_exists('http_response_code')) {
	require __DIR__.'/src/fn/http_response_code.php';
}

/**
 * Checking for this function explicitly because other libraries may
 * provide their own implementation.
 */
if (! function_exists('http_build_url')) {
	require __DIR__.'/src/fn/http_build_url.php';
}

/**
 * Functions and constants that share names with the HTTP (v1) extension.
 * @see /src/fn/http.php
 */
if (! function_exists('http_get_request_headers')) {
	require __DIR__.'/src/fn/http.php';
}

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
function http_env($id) {

	switch($id) {

		case 'ssl' :
			static $sslEnabled;
			if (! isset($sslEnabled)) {
				// @format:off
				$sslEnabled = (isset($_SERVER['HTTPS']) && ('on' === strtolower($_SERVER['HTTPS']) || '1' == $_SERVER['HTTPS']))
					|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'])
					|| (isset($_SERVER['SERVER_PORT']) && '443' == $_SERVER['SERVER_PORT']);
				// @format:on
			}
			return $sslEnabled;

		case 'host' :
			static $host;
			if (! isset($host)) {
				$host = rtrim($_SERVER['HTTP_HOST'], '/\\').rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
			}
			return $host;

		case 'domain' :
			return 'http'.(http_env('ssl') ? 's' : '').'://'.ltrim(http_env('host'), '/');

		default :
			trigger_error("Unknown HTTP environment ID.", E_USER_NOTICE);
	}
}

/**
 * Set a class to handle HTTP requests through the functional API.
 *
 * @param string $class Name of a class that extends HttpUtil\Request\Adapter.
 * @return void
 */
function http_set_request_handler($class) {

	/**
	 * User class to handle HTTP requests.
	 * @var string
	 */
	define('HTTP_REQUEST_HANDLER', $class);

	require_once __DIR__.'/src/fn/request.php';

	$class::initialize();
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
	$headers = array();
	if (empty($expires_offset) || '0' === $expires_offset) {
		$headers['Cache-Control'] = 'no-cache, must-revalidate, max-age=0';
		$headers['Expires'] = 'Thu, 19 Nov 1981 08:52:00 GMT';
		$headers['Pragma'] = 'no-cache';
	} else {
		$headers['Cache-Control'] = "Public, max-age=$expires_offset";
		$headers['Expires'] = http_date(time() + $expires_offset);
		$headers['Pragma'] = 'Public';
	}
	return $headers;
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
	if (null === $header = http_get_request_header($name)) {
		return $accept[0];
	}
	$object = new \HttpUtil\Header\NegotiatedHeader($name, $header);
	return $object->negotiate($accept);
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
	if (null === $header = http_get_request_header($name)) {
		return false;
	}
	return $match_case ? false !== strpos($header, $value) : false !== stripos($header, $value);
}

/**
 * Returns a HTTP status header description.
 *
 * @param int $code		HTTP response status code.
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

/**
 * Defines some "HTTP_STATUS_*" constants whose values are their
 * respective HTTP status code integers.
 *
 * e.g.
 * HTTP_STATUS_OK => 200
 * HTTP_STATUS_TEMP_REDIRECT => 407
 *
 * @return void
 */
function httputil_define_status_constants() {
	if (! defined('HTTP_STATUS_OK')) {
		include_once __DIR__.'/src/status_constants.php';
	}
}
