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

class HttpUtil {
	
	const ENV_SSL = 3;
	const ENV_HOST = 5;
	const ENV_DOMAIN = 7;
	
	protected static $env = array();
	protected static $host;
	protected static $domain;
	
	protected static $instance;
	
	protected $request_handler;
	
	public static function instance() {
		if (! isset(static::$instance)) {
			static::$instance = new static();
		}
		return static::$instance;
	}
	
	public static function getenv($id) {
		
		$env = static::instance();
		
		switch($id) {
			
			case static::ENV_SSL :
				
				if (! isset(static::$env['ssl'])) {
					$ssl = false;
					if (false !== $https = getenv('https')) {
						$ssl = ('on' === strtolower($https) || '1' == $https);
					} else if ('https' === getenv('http_x_forwarded_proto') || '443' === (string) getenv('server_port')) {
						$ssl = true;
					}
					static::$env['ssl'] = $ssl;
				}
				
				return static::$env['ssl'];
			
			case static::ENV_HOST :
				
				if (! isset(static::$env['host'])) {
					static::$env['host'] = rtrim(getenv('http_host'), '/\\').rtrim(dirname(getenv('script_name')), '/\\');
				}
				
				return static::$env['host'];
			
			case static::ENV_DOMAIN :
				
				return 'http'.(static::env(static::ENV_SSL) ? 's' : '').'://'.ltrim(static::env(static::ENV_HOST), '/');
				
			default :
				
				trigger_error("Unknown HTTP environment ID.", E_USER_WARNING);
		}
	}
	
	public function getRequestHandler() {
		return isset($this->request_handler) ? $this->request_handler : null;
	}
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
			return HttpUtil::getenv(HttpUtil::ENV_SSL);
		case 'host' :
			return HttpUtil::getenv(HttpUtil::ENV_HOST);
		case 'domain' :
			return HttpUtil::getenv(HttpUtil::ENV_DOMAIN);
		default :
			trigger_error("Unknown HTTP environment ID.", E_USER_NOTICE);
	}
}

/**
 * Set a class to handle HTTP requests through the functional API.
 *
 * @param string $class Name of a class that extends HttpUtil\Client\Adapter.
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
 
/**
 * Decode a chunked body as per RFC 2616
 * 
 * @author rmccue/Requests
 * 
 * @see http://tools.ietf.org/html/rfc2616#section-3.6.1
 * @param string $data Chunked body
 * @return string Decoded body
 */
function http_chunked_decode($data) {
	if (! preg_match('/^([0-9a-f]+)[^\r\n]*\r\n/i', trim($data))) {
		return $data;
	}

	$decoded = '';
	$encoded = $data;

	while (true) {
		$is_chunked = (bool) preg_match('/^([0-9a-f]+)[^\r\n]*\r\n/i', $encoded, $matches);
		if (!$is_chunked) {
			return $data;
		}

		$length = hexdec(trim($matches[1]));
		if ($length === 0) {
			// Ignore trailer headers
			return $decoded;
		}

		$chunk_length = strlen($matches[0]);
		$decoded .= $part = substr($encoded, $chunk_length, $length);
		$encoded = substr($encoded, $chunk_length + $length + 2);

		if (trim($encoded) === '0' || empty($encoded)) {
			return $decoded;
		}
	}
}

/**
 * Decompress an encoded body
 * 
 * Implements gzip, compress and deflate. Guesses which it is by attempting
 * to decode.
 * 
 * @author rmccue/Requests
 *  
 * @param string $data Compressed data as string.
 * @return string Decompressed data, or original if not compressed or decompression failed. 
 */
function http_inflate($data) {
	
	if (substr($data, 0, 2) !== "\x1f\x8b" && substr($data, 0, 2) !== "\x78\x9c") {
		// Not actually compressed. Probably cURL ruining this for us.
		return $data;
	}

	if (function_exists('gzdecode') && ($decoded = @gzdecode($data)) !== false) {
		return $decoded;
	} else if (function_exists('gzinflate') && ($decoded = @gzinflate($data)) !== false) {
		return $decoded;
	} else if (($decoded = http_inflate_compat($data)) !== false) {
		return $decoded;
	} else if (function_exists('gzuncompress') && ($decoded = @gzuncompress($data)) !== false) {
		return $decoded;
	}

	return $data;
}

/**
 * Decompression of deflated string while staying compatible with the majority of servers.
 *
 * Certain Servers will return deflated data with headers which PHP's gzinflate()
 * function cannot handle out of the box. The following function has been created from
 * various snippets on the gzinflate() PHP documentation.
 *
 * Warning: Magic numbers within. Due to the potential different formats that the compressed
 * data may be returned in, some "magic offsets" are needed to ensure proper decompression
 * takes place. For a simple progmatic way to determine the magic offset in use, see:
 * http://core.trac.wordpress.org/ticket/18273
 * 
 * @author rmccue/Requests
 * 
 * @param string $gzData String of compressed data.
 * @return string|boolean Decompressed data or false on failure.
 */
function http_inflate_compat($gzData) {
	
	// Compressed data might contain a full zlib header, if so strip it for
	// gzinflate()
	if (substr($gzData, 0, 3) == "\x1f\x8b\x08") {
		$i = 10;
		$flg = ord(substr($gzData, 3, 1));
		if ($flg > 0) {
			if ($flg & 4) {
				list($xlen) = unpack('v', substr($gzData, $i, 2));
				$i = $i + 2 + $xlen;
			}
			if ($flg & 8)
				$i = strpos($gzData, "\0", $i) + 1;
			if ($flg & 16)
				$i = strpos($gzData, "\0", $i) + 1;
			if ($flg & 2)
				$i = $i + 2;
		}
		
		$decompressed = http_inflate_compat(substr($gzData, $i));
		
		if (false !== $decompressed) {
			return $decompressed;
		}
	}

	// If the data is Huffman Encoded, we must first strip the leading 2
	// byte Huffman marker for gzinflate()
	// The response is Huffman coded by many compressors such as
	// java.util.zip.Deflater, Ruby’s Zlib::Deflate, and .NET's
	// System.IO.Compression.DeflateStream.
	//
	// See http://decompres.blogspot.com/ for a quick explanation of this
	// data type
	$huffman_encoded = false;

	// low nibble of first byte should be 0x08
	list(, $first_nibble)    = unpack('h', $gzData);

	// First 2 bytes should be divisible by 0x1F
	list(, $first_two_bytes) = unpack('n', $gzData);

	if (0x08 == $first_nibble && 0 == ($first_two_bytes % 0x1F))
		$huffman_encoded = true;

	if ($huffman_encoded) {
		if (false !== ($decompressed = @gzinflate(substr($gzData, 2))))
			return $decompressed;
	}

	if ("\x50\x4b\x03\x04" == substr($gzData, 0, 4)) {
		// ZIP file format header
		// Offset 6: 2 bytes, General-purpose field
		// Offset 26: 2 bytes, filename length
		// Offset 28: 2 bytes, optional field length
		// Offset 30: Filename field, followed by optional field, followed
		// immediately by data
		list(, $general_purpose_flag) = unpack('v', substr($gzData, 6, 2));

		// If the file has been compressed on the fly, 0x08 bit is set of
		// the general purpose field. We can use this to differentiate
		// between a compressed document, and a ZIP file
		$zip_compressed_on_the_fly = (0x08 == (0x08 & $general_purpose_flag));

		if (! $zip_compressed_on_the_fly) {
			// Don't attempt to decode a compressed zip file
			return $gzData;
		}

		// Determine the first byte of data, based on the above ZIP header
		// offsets:
		$first_file_start = array_sum(unpack('v2', substr($gzData, 26, 4)));
		if (false !== ($decompressed = @gzinflate(substr($gzData, 30 + $first_file_start)))) {
			return $decompressed;
		}
		return false;
	}

	// Finally fall back to straight gzinflate
	if (false !== ($decompressed = @gzinflate($gzData))) {
		return $decompressed;
	}

	// Fallback for all above failing, not expected, but included for
	// debugging and preventing regressions and to track stats
	if (false !== ($decompressed = @gzinflate(substr($gzData, 2)))) {
		return $decompressed;
	}

	return false;
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
