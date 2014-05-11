<?php
/**
 * @package Phpf\HttpUtil
 * 
 * HTTP request functions:
 * * http_create_request()
 * * http_request()
 * * http_get()
 * * http_head()
 * * http_post()
 * * http_put()
 * * http_put_file()
 */

/**
 * Set a class to handle HTTP requests through the functional API.
 *
 * @param string $class Name of a class that extends HttpUtil\Client\Adapter.
 * @return void
 */
function http_set_request_handler($class) {
	\HttpUtil\Main::setRequestHandler($class);
}

/**
 * Creates a new instance of the request handler class.
 * 
 * @param string $method HTTP request method.
 * @param string $uri Request URI.
 * @param array $options [Optional] Request options.
 * 
 * @return HttpUtil\Client\Adapter
 * 
 * @throws RuntimeException if request handler class is not defined.
 */
function http_create_request($method, $url, array $options = null) {
	
	if (! $class = \HttpUtil\Main::getRequestHandler()) {
		throw new HttpUtilException("No HTTP request handler set.");
	}
	
	return new $class($method, $url, $options);
}

/**
 * Send a custom HTTP request.
 * 
 * @param string $method HTTP request method (uppercase).
 * @param string $url Request URL.
 * @param string|array $data Request message body data.
 * @param array $options Request options.
 * 
 * @return HttpUtil\Client\Request\Response
 */
function http_request($method, $url, $data = null, array $options = array()) {
	
	$http = http_create_request($method, $url, $options);

	if (! empty($data)) {
		$http->addData($data);
	}
	
	return $http->execute();
}

/**
 * Send a GET request
 * 
 * @param string $url Request URL.
 * @param array $options [Optional] Request options.
 * @return HttpUtil\Client\Request\Response
 */
function http_get($url, array $options = array()) {
	return http_request('GET', $url, null, $options);
}

/**
 * Send a HEAD request
 * 
 * @param string $url Request URL.
 * @param array $options [Optional] Request options.
 * @return HttpUtil\Client\Request\Response
 */
function http_head($url, array $options = array()) {
	return http_request('HEAD', $url, null, $options);
}

/**
 * Send a POST request
 *
 * @param string $url Request URL.
 * @param string|array $data Request message data.
 * @param array $options [Optional] Request options.
 * @return HttpUtil\Client\Request\Response
 */
function http_post($url, $data = null, array $options = array()) {
	return http_request('POST', $url, $data, $options);
}

/**
 * Send a PUT request.
 * 
 * @param string $url Request URL.
 * @param string|array $data Request message data.
 * @param array $options [Optional] Request options.
 * @return HttpUtil\Client\Request\Response
 */
function http_put($url, $data = null, array $options = array()) {
	return http_request('PUT', $url, $data, $options);
}

/**
 * Sends a PUT request with a file's contents as the body.
 * 
 * @param string $url Request URL.
 * @param string $file Path to the readable file to send.
 * @param array $options [Optional] Request options.
 * @return HttpUtil\Client\Request\Response
 */
function http_put_file($url, $filepath, array $options = array()) {
	
	if (! is_readable($filepath)) {
		trigger_error("Cannot send PUT request with an unreadable file.");
		return null;
	}
	
	if (! isset($options['headers'])) {
		$options['headers'] = array();
	}
	
	if (! isset($options['headers']['Content-Type'])) {
		$ext = pathinfo($filepath, PATHINFO_EXTENSION);
		$options['headers']['Content-Type'] = mimetype($ext, 'application/octet-stream');
	}
	
	$data = file_get_contents($filepath);
	
	$options['headers']['Content-Length'] = strlen($data);
	
	return http_request('PUT', $url, $data, $options);
}
	