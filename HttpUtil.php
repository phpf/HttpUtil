<?php
/**
 * HttpUtil - HTTP utility library.
 *
 * @author wells
 * @license MIT
 * @version 0.1.0
 */

/* If not using Composer, you can use the autoloader below
 spl_autoload_register(function ($class) {
 if (0 === strpos($class, 'HttpUtil')) {
 include __DIR__. '/src/' . str_replace(array('HttpUtil\\', '\\'), array('',
'/'), $class) .'.php';
 }
 });
 */

/**
 * Use in http_env() to return whether the current environment is set up for SSL.
 *
 * Returns true for any of the following:
 * * $_SERVER['HTTPS'] == ('on' || 1)
 * * $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
 * * $_SERVER['SERVER_PORT'] == '443'
 *
 * @var int
 */
define('HTTP_ENV_SSL', 1);

/**
 * Use in http_env() to return the current host.
 *
 * The host is built from $_SERVER['HTTP_HOST'] and the
 * dirname of $_SERVER['SCRIPT_NAME'].
 *
 * Returned without scheme or trailing slash.
 *
 * @var int
 */
define('HTTP_ENV_HOST', 2);

/**
 * Use in http_env() to return the current domain.
 *
 * The domain is built using the HTTP_ENV_SSL and HTTP_ENV_HOST
 * environment variables with http(s) scheme.
 *
 * Returned without trailing slash.
 *
 * @var int
 */
define('HTTP_ENV_DOMAIN', 4);

/**
 * Retrieve information about the current environment.
 *
 * Returns one of:
 *  * (bool) Whether SSL is enabled on the server (@see HTTP_ENV_SSL)
 *  * (string) Server host name, useful for cookies (@see HTTP_ENV_HOST)
 *  * (string) Server domain, including 'http' scheme and host (@see
 * HTTP_ENV_DOMAIN)
 */
function http_env($id) {

	switch($id) {

		case HTTP_ENV_SSL :
			static $sslEnabled;
			if (! isset($sslEnabled)) {
				// @format:off
				$sslEnabled = (
					(isset($_SERVER['HTTPS']) && ('on' === strtolower($_SERVER['HTTPS']) || '1' == $_SERVER['HTTPS']))
					|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'])
					|| (isset($_SERVER['SERVER_PORT']) && '443' == $_SERVER['SERVER_PORT'])
				);
				// @format:on
			}
			return $sslEnabled;

		case HTTP_ENV_HOST :
			static $host;
			if (! isset($host)) {
				$host = rtrim($_SERVER['HTTP_HOST'], '/\\').rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
			}
			return $host;

		case HTTP_ENV_DOMAIN :
			return 'http'.(http_env(HTTP_ENV_SSL) ? 's' : '').'://'.ltrim(http_env(HTTP_ENV_HOST), '/');

		default :
			trigger_error("Unknown HTTP environment ID.", E_USER_NOTICE);
	}
}

/**
 * http_response_code() for PHP < 5.4
 */
if (! function_exists('http_response_code')) {
	require __DIR__.'/src/fn/http_response_code.php';
}

/**
 * Functions and constants that share names with the HTTP (v1) extension.
 * @see /src/fn/http.php
 */
if (! function_exists('http_get_request_headers')) :

	/**
	 * HTTP method GET
	 * @var string
	 */
	define("HTTP_METH_GET", 'GET');

	/**
	 * HTTP method POST
	 * @var string
	 */
	define("HTTP_METH_POST", 'POST');

	/**
	 * HTTP method HEAD
	 * @var string
	 */
	define("HTTP_METH_HEAD", 'HEAD');

	/**
	 * HTTP method PUT
	 * @var string
	 */
	define("HTTP_METH_PUT", 'PUT');

	/**
	 * HTTP method DELETE
	 * @var string
	 */
	define("HTTP_METH_DELETE", 'DELETE');

	/**
	 * HTTP method OPTIONS
	 * @var string
	 */
	define("HTTP_METH_OPTIONS", 'OPTIONS');

	/**
	 * Functions, generally close to their extension counterparts.
	 */
	require __DIR__.'/src/fn/http.php';

endif;

/**
 * Checking for this function explicitly because others may provide
 * a fallback (e.g. FuelPHP).
 */
if (! function_exists('http_build_url')) :

	/**
	 * Replace every part of the first URL when there's one of the second URL.
	 * @var int
	 */
	define('HTTP_URL_REPLACE', 1);

	/**
	 * Join relative paths.
	 * @var int
	 */
	define('HTTP_URL_JOIN_PATH', 2);

	/**
	 * Join query strings.
	 * @var int
	 */
	define('HTTP_URL_JOIN_QUERY', 4);

	/**
	 * Strip any user authentication information.
	 * @var int
	 */
	define('HTTP_URL_STRIP_USER', 8);

	/**
	 * Strip any password authentication information.
	 * @var int
	 */
	define('HTTP_URL_STRIP_PASS', 16);

	/**
	 * Strip any authentication information.
	 * @var int
	 */
	define('HTTP_URL_STRIP_AUTH', 32);

	/**
	 * Strip explicit port numbers.
	 * @var int
	 */
	define('HTTP_URL_STRIP_PORT', 64);

	/**
	 * Strip complete path.
	 * @var int
	 */
	define('HTTP_URL_STRIP_PATH', 128);

	/**
	 * Strip query string.
	 * @var int
	 */
	define('HTTP_URL_STRIP_QUERY', 256);

	/**
	 * Strip any fragments (#identifier).
	 * @var int
	 */
	define('HTTP_URL_STRIP_FRAGMENT', 512);

	/**
	 * Strip anything but scheme and host.
	 * @var int
	 */
	define('HTTP_URL_STRIP_ALL', 1024);

	/**
	 * Takes an associative array in the layout of parse_url, and constructs a URL
	 * from it.
	 *
	 * @author FuelPHP
	 * @see http://www.php.net/manual/en/function.http-build-url.php
	 *
	 * @param mixed (Part(s) of) an URL in form of a string or associative array like
	 * parse_url() returns
	 * @param mixed Same as the first argument
	 * @param int A bitmask of binary or'ed HTTP_URL constants (Optional)
	 * HTTP_URL_REPLACE is the default
	 * @param array If set, it will be filled with the parts of the composed url like
	 * parse_url() would return
	 * @return string constructed URL
	 */
	function http_build_url($url, $parts = array(), $flags = HTTP_URL_REPLACE, &$new_url = false) {

		$keys = array('user', 'pass', 'port', 'path', 'query', 'fragment');

		if ($flags & HTTP_URL_STRIP_ALL) {
			// HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
			$flags |= HTTP_URL_STRIP_USER;
			$flags |= HTTP_URL_STRIP_PASS;
			$flags |= HTTP_URL_STRIP_PORT;
			$flags |= HTTP_URL_STRIP_PATH;
			$flags |= HTTP_URL_STRIP_QUERY;
			$flags |= HTTP_URL_STRIP_FRAGMENT;
		} else if ($flags & HTTP_URL_STRIP_AUTH) {
			// HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
			$flags |= HTTP_URL_STRIP_USER;
			$flags |= HTTP_URL_STRIP_PASS;
		}

		// parse the original URL
		$parsed = is_array($url) ? $url : parse_url($url);

		// make sure we always have a scheme, host and path
		if (empty($parsed['scheme']))
			$parsed['scheme'] = 'http'.(http_env(HTTP_ENV_SSL) ? 's' : '');
		if (empty($parsed['host']))
			$parsed['host'] = http_env(HTTP_ENV_HOST);
		if (! isset($parsed['path']))
			$parsed['path'] = '';

		// make the path absolute if needed
		if (! empty($parsed['path']) && '/' !== substr($parsed['path'], 0, 1)) {
			$parsed['path'] = '/'.$parsed['path'];
		}

		// scheme and host are always replaced
		if (isset($parts['scheme']))
			$parsed['scheme'] = $parts['scheme'];
		if (isset($parts['host']))
			$parsed['host'] = $parts['host'];

		// replace the original URL with it's new parts (if applicable)
		if ($flags & HTTP_URL_REPLACE) {
			foreach ( $keys as $key ) {
				if (isset($parts[$key])) {
					$parsed[$key] = $parts[$key];
				}
			}
		} else {
			// join the original URL path with the new path
			if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH)) {
				if (isset($parsed['path'])) {
					$parsed['path'] = rtrim(str_replace(basename($parsed['path']), '', $parsed['path']), '/').'/'.ltrim($parts['path'], '/');
				} else {
					$parsed['path'] = $parts['path'];
				}
			}

			// join the original query string with the new query string
			if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY)) {
				if (isset($parsed['query'])) {
					$parsed['query'] .= '&'.$parts['query'];
				} else {
					$parsed['query'] = $parts['query'];
				}
			}
		}

		// strips all the applicable sections of the URL
		// note: scheme and host are never stripped
		foreach ( $keys as $key ) {
			if ($flags & (int)constant('HTTP_URL_STRIP_'.strtoupper($key))) {
				unset($parsed[$key]);
			}
		}

		$new_url = $parsed;

		$url = isset($parsed['scheme']) ? $parsed['scheme'].'://' : '';
		if (isset($parsed['user'])) {
			$pass = isset($parsed['pass']) ? ':'.$parsed['pass'] : '';
			$url .= $parsed['user'].$pass.'@';
		}
		$url .= isset($parsed['host']) ? $parsed['host'] : '';
		$url .= isset($parsed['port']) ? ':'.$parsed['port'] : '';
		$url .= isset($parsed['path']) ? $parsed['path'] : '';
		$url .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
		$url .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

		return $url;
	}

endif;

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
 * content-type
 * and language negotiation.
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

	if (defined('HTTP_STATUS_OK')) {
		return;
	}

	/**
	 * Status: OK (200)
	 * @var int
	 */
	define('HTTP_STATUS_OK', 200);

	/**
	 * Status: Created (201)
	 * @var int
	 */
	define('HTTP_STATUS_CREATED', 201);

	/**
	 * Permanent Redirect (301)
	 * @var int
	 */
	define('HTTP_STATUS_PERM_REDIRECT', 301);

	/**
	 * Found (302)
	 * @var int
	 */
	define('HTTP_STATUS_FOUND', 302);

	/**
	 * See Other (303)
	 * @var int
	 */
	define('HTTP_STATUS_SEE_OTHER', 303);

	/**
	 * Temporary Redirect (307)
	 * @var int
	 */
	define('HTTP_STATUS_TEMP_REDIRECT', 307);

	/**
	 * Status: Bad Request (400)
	 * @var int
	 */
	define('HTTP_STATUS_BAD_REQUEST', 400);

	/**
	 * Status: Unauthorized (401)
	 * @var int
	 */
	define('HTTP_STATUS_UNAUTHORIZED', 401);

	/**
	 * Status: Forbidden (403)
	 * @var int
	 */
	define('HTTP_STATUS_FORBIDDEN', 403);

	/**
	 * Status: Not Found (404)
	 * @var int
	 */
	define('HTTP_STATUS_NOT_FOUND', 404);

	/**
	 * Status: Method Not Allowed (405)
	 * @var int
	 */
	define('HTTP_STATUS_METHOD_NOT_ALLOWED', 405);

	/**
	 * Status: Not Acceptable (406)
	 * @var int
	 */
	define('HTTP_STATUS_NOT_ACCEPTABLE', 406);

}
