<?php
/**
 * HttpUtil - HTTP utility library.
 * 
 * @license MIT
 * @author wells
 * @version 0.0.2
 */

/* If not using Composer, you can use the autoloader below
spl_autoload_register(function ($class) {
	if (0 === strpos($class, 'HttpUtil')) {
		include __DIR__. '/src/' . str_replace(array('HttpUtil\\', '\\'), array('', '/'), $class) .'.php';
	}
});
*/

if (! defined('HTTP_DOMAIN')) :
	/**
	 * Domain/host
	 * @var string
	 */
	define('HTTP_DOMAIN', rtrim($_SERVER['HTTP_HOST'], '/\\').rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));
endif;

/**
 * Using SSL?
 * @var boolean
 */
define('HTTP_SSL', (int)
	(isset($_SERVER['HTTPS']) && ('on' === strtolower($_SERVER['HTTPS']) || '1' == $_SERVER['HTTPS']))
	|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'])
	|| (isset($_SERVER['SERVER_PORT']) && '443' == $_SERVER['SERVER_PORT'])
);

/**
 * Status: OK (200)
 * @var int
 */
define('HTTP_OK', 200);

/**
 * Status: Created (201)
 * @var int
 */
define('HTTP_CREATED', 201);

/**
 * Permanent Redirect (301)
 * @var int
 */
define('HTTP_PERM_REDIRECT', 301);

/**
 * Found (302)
 * @var int
 */
define('HTTP_FOUND', 302);

/**
 * See Other (303)
 * @var int
 */
define('HTTP_SEE_OTHER', 303);

/**
 * Temporary Redirect (307)
 * @var int
 */
define('HTTP_TEMP_REDIRECT', 307);

/**
 * Status: Bad Request (400)
 * @var int
 */
define('HTTP_BAD_REQUEST', 400);

/**
 * Status: Unauthorized (401)
 * @var int
 */
define('HTTP_UNAUTHORIZED', 401);

/**
 * Status: Forbidden (403)
 * @var int
 */
define('HTTP_FORBIDDEN', 403);

/**
 * Status: Not Found (404)
 * @var int
 */
define('HTTP_NOT_FOUND', 404);

/**
 * Status: Method Not Allowed (405)
 * @var int
 */
define('HTTP_METHOD_NOT_ALLOWED', 405);

/**
 * Status: Not Acceptable (406)
 * @var int
 */
define('HTTP_NOT_ACCEPTABLE', 406);

/** 
 * http_response_code() for PHP < 5.4 
 */
if (! function_exists('http_response_code')) :
	require __DIR__ . '/src/HttpUtil/fn/http_response_code.php';
endif;

/**
 * Functions and constants that share names with the HTTP (v1) extension.
 */
if (! extension_loaded('http')) :
	
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
	require __DIR__ . '/src/HttpUtil/fn/http.php';

endif;

/**
 * Retrieve the value of a cookie. genious
 *
 * @author https://github.com/yeroon/codebase
 * 
 * @param string $name Name of cookie.
 * @return mixed Returns the value if cookie exists, otherwise false.
 */
function getcookie($name) {
	return array_key_exists($name, $_COOKIE) ? $_COOKIE[$name] : false;
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
 * Returns a URL, complete with scheme (https if SSL enabled).
 * 
 * If given a full URL (scheme, host, etc.), will add/replace query parameters.
 * If given a relative path, it will return a full URL using the current env settings.
 * If given an array, will use like an array returned from parse_url()
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
		$path['scheme'] = 'http'.(1 === HTTP_SSL ? 's' : '');
	}
	
	if (! isset($path['host'])) {
		$path['host'] = HTTP_DOMAIN;
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

/**
 * Returns an array of cache headers suitable for use in header().
 * 
 * @param int $expires_offset Expiration offset, given in seconds from current time.
 * @param boolean $names_as_keys If true, header values will be returned array keys.
 * 								 Otherwise, values are string of "Name: Value".
 * @return array Indexed or associative array of cache headers.
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
 * Returns request body string.
 * 
 * Stores the string in a static variable, thus providing a way to
 * get php://input more than once. Of course, this function will
 * not work if read before (e.g. fopen, file_get_contents, etc.).
 * 
 * NOTE: $_POST requests with "multipart/form-data" will not work with php://input
 * 
 * @return string HTTP request body.
 */
function http_request_body() {
	static $rawbody;
	if (! isset($rawbody)) {
		$rawbody = file_get_contents('php://input');
	}
	return $rawbody;
}

/**
 * Returns array of HTTP request headers.
 * 
 * Cross-platform function to access current HTTP request headers.
 * 
 * @param array|null $server	Array or null to use $_SERVER
 * @return array 				HTTP request headers, keys stripped of "HTTP_" and lowercase.
 */
function http_request_headers() {
	static $headers;
	if (isset($headers)) {
		return $headers; // get once per request
	}
	if (function_exists('apache_request_headers')) {
		$_headers = apache_request_headers();
	} else {
		$_headers = array();
		$misfits = array('CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5', 'AUTH_TYPE', 'PHP_AUTH_USER', 'PHP_AUTH_PW', 'PHP_AUTH_DIGEST');
		foreach ( $_SERVER as $key => $value ) {
			if (0 === strpos($key, 'HTTP_')) {
				$_headers[ $normalize($key) ] = $value;
			} else if (in_array($key, $misfits, true)) {
				$_headers[ $normalize($key) ] = $value;
			}
		}
	}
	$headers = array();
	foreach($_headers as $key => $value) {
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
function http_request_header($name) {
	$headers = http_request_headers();
	return isset($headers[$name]) ? $headers[$name] : null;
}

/**
 * Parses a request header to determine which value to use in response.
 *  
 * @param string $name	Request header name, lowercase.
 * @param array $accept Indexed array of accepted values.
 * @return string 		Matched value (selected by quality, then position), 
 * 						or first array value if no match found.
 */
function http_negotiate_request_header($name, array $accept) {
	if (null === $header = http_request_header($name)) {
		return $accept[0];
	}
	$object = new \HttpUtil\Header\NegotiatedHeader($name, $header);
	return $object->negotiate($accept);
}

/**
 * Determines if $value is in the contents of $name request header.
 * 
 * @param string $name		Header name, lowercase, without 'HTTP_'.
 * @param string $value		Value to match.
 * @param bool $match_case	Whether to match case-sensitively, default false.
 * @return boolean			True if match, otherwise false.
 */
function http_in_request_header($name, $value, $match_case = false) {
	if (null === $header = http_request_header($name)) {
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
