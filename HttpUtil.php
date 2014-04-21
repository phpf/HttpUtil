<?php
/**
 * HttpUtil - HTTP utility library.
 * 
 * @license MIT
 * @author wells
 * @version 0.0.3
 */

/* If not using Composer, you can use the autoloader below
spl_autoload_register(function ($class) {
	if (0 === strpos($class, 'HttpUtil')) {
		include __DIR__. '/src/' . str_replace(array('HttpUtil\\', '\\'), array('', '/'), $class) .'.php';
	}
});
*/

if (! defined('HTTP_HOST')) {
	/**
	 * Domain/host
	 * @var string
	 */
	define('HTTP_HOST', rtrim($_SERVER['HTTP_HOST'], '/\\').rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));
}

if (! defined('HTTP_SSL_ENABLED')) {
	/**
	 * Using SSL?
	 * @var boolean
	 */
	define('HTTP_SSL_ENABLED', (int)
		(isset($_SERVER['HTTPS']) && ('on' === strtolower($_SERVER['HTTPS']) || '1' == $_SERVER['HTTPS']))
		|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'])
		|| (isset($_SERVER['SERVER_PORT']) && '443' == $_SERVER['SERVER_PORT'])
	);
}

/** 
 * http_response_code() for PHP < 5.4 
 */
if (! function_exists('http_response_code')) {
	require __DIR__ . '/src/fn/http_response_code.php';
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
	require __DIR__ . '/src/fn/http.php';

endif;

/**
 * Checking for this function explicitly because others may provide
 * a fallback (e.g. FuelPHP).
 */
if (! function_exists('http_build_url')) :
		
	/** 
	 * Replace every part of the first URL when there's one of the second URL.
	 */
	define('HTTP_URL_REPLACE', 1);
	
	/** 
	 * Join relative paths
	 */
	define('HTTP_URL_JOIN_PATH', 2);
	
	/**
	 * Join query strings
	 */
	define('HTTP_URL_JOIN_QUERY', 4);
	
	/** 
	 * Strip any user authentication information
	 */
	define('HTTP_URL_STRIP_USER', 8);
	
	/** 
	 * Strip any password authentication information
	 */
	define('HTTP_URL_STRIP_PASS', 16);
	
	/** 
	 * Strip any authentication information
	 */
	define('HTTP_URL_STRIP_AUTH', 32);
	
	/** 
	 * Strip explicit port numbers
	 */
	define('HTTP_URL_STRIP_PORT', 64);
	
	/** 
	 * Strip complete path
	 */
	define('HTTP_URL_STRIP_PATH', 128);
	
	/** 
	 * Strip query string
	 */
	define('HTTP_URL_STRIP_QUERY', 256);
	
	/** 
	 * Strip any fragments (#identifier)
	 */
	define('HTTP_URL_STRIP_FRAGMENT', 512);
	
	/**
	 * Strip anything but scheme and host
	 */
	define('HTTP_URL_STRIP_ALL', 1024);
	
	/**
	 * Takes an associative array in the layout of parse_url, and constructs a URL from it.
	 *
	 * @author FuelPHP
	 * 
	 * @see http://www.php.net/manual/en/function.http-build-url.php
	 *
	 * @param mixed (Part(s) of) an URL in form of a string or associative array like parse_url() returns
	 * @param mixed Same as the first argument
	 * @param int A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
	 * @param array If set, it will be filled with the parts of the composed url like parse_url() would return
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
		empty($parsed['scheme']) and $parsed['scheme'] = 'http'.(1 === HTTP_SSL_ENABLED ? 's' : '');
		empty($parsed['host']) and $parsed['host'] = HTTP_HOST;
		isset($parsed['path']) or $parsed['path'] = '';
	
		// make the path absolute if needed
		if (! empty($parsed['path']) and substr($parsed['path'], 0, 1) != '/') {
			$parsed['path'] = '/'.$parsed['path'];
		}
	
		// scheme and host are always replaced
		isset($parts['scheme']) and $parsed['scheme'] = $parts['scheme'];
		isset($parts['host']) and $parsed['host'] = $parts['host'];
	
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
		
		// this ugly but more readable than concatenating
		$url = isset($parsed['scheme'])		? $parsed['scheme'].'://'	: '';
		
		if (isset($parsed['user'])) {
			$url .= $parsed['user'] 
				.(isset($parsed['pass'])	? ':'.$parsed['pass']		: '') 
				.'@';
		}
		
		$url .= isset($parsed['host']) 		? $parsed['host']			: '';
		$url .= isset($parsed['port'])		? ':'.$parsed['port']		: '';
		$url .= isset($parsed['path'])		? $parsed['path']			: '';
		$url .= isset($parsed['query'])		? '?'.$parsed['query']		: '';
		$url .= isset($parsed['fragment'])	? '#'.$parsed['fragment']	: '';
		
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
 * Parses an arbitrary request header to determine which value to use in response.
 * 
 * This is a general-use function; specific implementations exist for content-type
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
	return  $match_case
		? false !== strpos($header, $value)
		: false !== stripos($header, $value);
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

/**
 * Returns a URL from a given string or array. 
 * 
 * If 1st param is a full URL (with scheme, host, and path) as string or array, 
 * behavior is to merge the query parameters given by $params.
 * 
 * If given a relative file path or incomplete array, behavior is convert the
 * path to a full URL, with scheme, using the HTTP_SSL and HTTP_DOMAIN constants.
 * 
 * @param string|array $path A URI path, possibily with scheme, host, path, and/or query.
 * @param array $params Associative array of query parameters to merge into URL.
 * @param boolean $as_array Whether to return URL as an array, like parse_url(). Default false.
 * @return string|array New URL, or base URL if no path given. Assoc. array if $as_array = true.
 */
function http_url($path = '', array $params = null, $as_array = false) {
	
	if (is_string($path)) {
		$path = parse_url($path);
	}
	
	if (! isset($path['scheme'])) {
		$path['scheme'] = 'http'.(1 === HTTP_SSL_ENABLED ? 's' : '');
	}
	
	if (! isset($path['host'])) {
		$path['host'] = HTTP_HOST;
	}
	
	$url = $path['scheme'] .'://'. $path['host'] .'/'. ltrim($path['path'], '/');
	
	if (isset($path['query'])) {
		if (is_string($path['query'])) {
			parse_str(urldecode($path['query']), $query);
		} else {
			$query = $path['query'];
		}
	}
	
	if (isset($params)) {
		$query = isset($query) ? array_merge($query, $params) : $params;
	}
	
	if (true === $as_array) {
		if (isset($query)) {
			$path['query'] = $query;
		}
		return $path;
	}
	
	if (isset($query)) {
		$path['query'] = http_build_query($query, null, '&');
		$url .= '?' . $path['query'];
	}
	
	return $url;
}
