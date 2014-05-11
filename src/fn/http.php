<?php
/**
 * @package HttpUtil
 * 
 * (Some) functions provided by PECL HTTP extension (v1):
 * * http_date()
 * * http_redirect()
 * * http_send_status()
 * * http_send_content_type()
 * * http_send_content_disposition()
 * * http_send_file()
 * * http_get_request_body()
 * * http_get_request_body_stream()
 * * http_get_request_headers()
 * * http_get_request_header()
 * * http_match_request_header()
 * * http_negotiate_content_type()
 * * http_negotiate_language()
 */
 
use HttpUtil\HTTP;

/**
 * Returns a valid HTTP date using given timestamp or current time if none given.
 *
 * @param int $timestamp Unix timestamp
 * @return string Date formatted regarding RFC 1123.
 */
function http_date($timestamp = null) {
	return HTTP::instance()->date($timestamp);
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
	HTTP::instance()->redirect($url, $status, $exit);
}

/**
 * Sends response status code, as well as an additional "Status" header.
 *
 * @param int $code HTTP status code to send
 * @return void
 */
function http_send_status($code) {
	HTTP::instance()->sendStatus($code);
}

/**
 * Sends the content-type header.
 *
 * @param string $content_type Content-type, must contain both primary/secondary.
 * @param string|null $charset Optional charset to send.
 * @return boolean True if sent, false/warning error if missing a part.
 */
function http_send_content_type($content_type = 'application/x-octetstream', $charset = null) {
	return HTTP::instance()->sendContentType($content_type, $charset);
}

/**
 * Send the "Content-Disposition" header.
 * 
 * @param string $disposition Content disposition (e.g. "attachment").
 * @param string $filename [Optional] Populates "filename" in header.
 * @param string $name [Optional] Populates "name" in header.
 */
function http_send_content_disposition($disposition = 'attachment', $filename = null, $name = null) {
	HTTP::instance()->sendContentDisposition($disposition, $filename, $name);
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
	HTTP::instance()->sendFile($file, $filetype, $filename);
}

/**
 * Returns request body string.
 *
 * This function will not work if php://input is read before calling
 * (e.g. via file_get_contents(), fopen(), etc.).
 * 
 * Also, POST requests with "multipart/form-data" will not work with this
 * function, as it relies on php://input.
 *
 * @see \HttpUtil\Request\Body::contents()
 * 
 * @return string HTTP request body.
 */
function http_get_request_body() {
	return HTTP::instance()->getRequestBody();
}

/**
 * Returns a read-only file handle for the request body stream.
 * 
 * Uses fopen("php://input") with binary flag.
 * 
 * @see \HttpUtil\Request\Body::handle()
 * 
 * @return resource Read-only file handle for php://input stream.
 */
function http_get_request_body_stream() {
	return HTTP::instance()->getRequestBodyStream();
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
	return HTTP::instance()->getRequestHeaders();
}

/**
 * Fetches a single HTTP request header.
 *
 * @param string $name		Header name, lowercase, without 'HTTP_' prefix.
 * @return string|null		Header value, if set, otherwise null.
 */
function http_get_request_header($name) {
	return HTTP::instance()->getRequestHeader($name);
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
	return HTTP::instance()->matchRequestHeader($name, $value, $match_case);
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
	return HTTP::instance()->negotiateContentType($accept);
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
	return HTTP::instance()->negotiateLanguage($accept, $result);
}
