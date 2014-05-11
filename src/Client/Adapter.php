<?php

namespace HttpUtil\Client;

/**
 * Base adapter class for the user's HTTP client library.
 */
abstract class Adapter {
	
	/**
	 * HTTP request method.
	 * @var string
	 */
	protected $method;
	
	/**
	 * Request URL.
	 * @var string
	 */
	protected $url;
	
	/**
	 * Request options.
	 * @var array
	 */
	protected $options = array();
	
	/**
	 * Request headers.
	 * @var array
	 */
	protected $headers = array();
	
	/**
	 * Request body data.
	 * @var mixed
	 */
	protected $data;
	
	/**
	 * Request response.
	 * @var \HttpUtil\Client\Request\Response
	 */
	protected $response;
	
	/**
	 * Set method, URL, and options on construct.
	 * 
	 * @param string $method HTTP method
	 * @param string $url Request URL
	 * @param array $options [Optional] Options
	 */
	final public function __construct($method, $url, array $options = null) {
		$this->method = strtoupper($method);
		$this->url = $url;
		if (! empty($options)) {
			$this->setOptions($options);
		}
	}
	
	/**
	 * Sends the response and returns a HttpUtil\Request\Response object.
	 * 
	 * @return \HttpUtil\Request\Response
	 */
	final public function execute() {
		
		$this->buildResponse($this->send());
		
		return $this->getResponse();
	}
	
	/**
	 * Allows implementations to perform one-time actions, for example,
	 * registering an autoloader.
	 * 
	 * @return void
	 */
	public static function initialize() {
		// subclass setup actions
	}
	
	/**
	 * Sends the request and returns response.
	 * 
	 * @return mixed
	 */
	abstract protected function send();
	
	/**
	 * Builds the response object from request results. 
	 * 
	 * Must call setRespose() with built Response object.
	 * 
	 * @param mixed $results Response from request adapter's send() method.
	 */
	abstract protected function buildResponse($results);
	
	/**
	 * Allows the adapter to alter the options array before being set.
	 * 
	 * This enables implementations to differ in how options are handled
	 * (e.g. naming), but retain a common user-facing API.
	 * 
	 * @param array $options Options as given by the user.
	 * @return array Options adapted for the client implementation.
	 */
	protected function adaptOptions(array $options) {
		return $options;
	}
	
	/**
	 * Creates the Response object.
	 * 
	 * Called from adapter's buildResponse() method.
	 * 
	 * @return $this
	 */
	final protected function setResponse(Request\Response $response) {
		$this->response = $response;
		return $this;
	}
	
	/**
	 * Returns the Response object.
	 * 
	 * @return \HttpUtil\Request\Response
	 */
	final public function getResponse() {
		return isset($this->response) ? $this->response : null;
	}
	
	/**
	 * Sets the HTTP method.
	 * @param string $method Method
	 * @return $this
	 */
	final public function setMethod($method) {
		$this->method = strtoupper($method);
		return $this;
	}
	
	/**
	 * Returns the request method.
	 * 
	 * @return string HTTP method.
	 */
	final public function getMethod() {
		return $this->method;
	}
	
	/**
	 * Sets the request URL.
	 * 
	 * @param string $url
	 * @return $this
	 */
	final public function setUrl($url) {
		$this->url = $url;
		return $this;
	}
	
	/**
	 * Returns the request URL.
	 * 
	 * @return string URL
	 */
	final public function getUrl() {
		return $this->url;
	}
	
	/**
	 * Set the request headers, overriding any already set.
	 * 
	 * @param array $headers Associative array of headers to send.
	 * @return $this
	 */
	final public function setHeaders(array $headers) {
		$this->headers = $headers;
		return $this;
	}
	
	/**
	 * Adds headers to existing.
	 * @param array $headers Associative array of additional headers to send.
	 * @return $this
	 */
	final public function addHeaders(array $headers) {
		$this->headers = array_merge($this->headers, $headers);
		return $this;
	}
	
	/**
	 * Returns all currently set headers.
	 * @return array Associative array of headers.
	 */
	final public function getHeaders() {
		return $this->headers;
	}
	
	/**
	 * Sets a single header, replacing existing.
	 * 
	 * @param string $name Header name.
	 * @param string $value Header value.
	 * @return $this
	 */
	final public function setHeader($name, $value) {
		$this->headers[$name] = $value;
		return $this;
	}
	
	/**
	 * Returns a header value if set, otherwise null.
	 * 
	 * @param string $name Header name.
	 * @return string|null Header value if set, otherwise null.
	 */
	final public function getHeader($name) {
		return isset($this->headers[$name]) ? $this->headers[$name] : null;
	}
	
	/**
	 * Sets request message body data.
	 * 
	 * If data is not a string or array, it will be cast to an array.
	 * 
	 * @param array|string $data
	 * @return $this
	 */
	public function setData($data) {
		
		if (! is_string($data) && ! is_array($data)) {
			$data = (array) $data;
		}
		
		$this->data = $data;
		
		return $this;
	}
	
	/**
	 * Returns request message data.
	 * 
	 * @return array|string Body data.
	 */
	public function getData() {
		return $this->data;
	}
	
	/**
	 * Adds request message data.
	 * 
	 * If given an array and the current $data property is also an array, data is merged.
	 * If given a string and current $data is also a string, appended with "\r\n".
	 * If given type does not match exiting data type, a user notice is triggered and data is not added.
	 * 
	 * @param mixed $data Request message data to add.
	 * @return $this
	 */
	public function addData($data) {
			
		if (empty($this->data)) {
			$this->data = $data;
		} else if (is_string($this->data)) {
			if (is_string($data)) {
				$this->data .= "\r\n".$data;
			} else {
				trigger_error("Cannot add request data - existing data is a string.", E_USER_NOTICE);
			}
		} else if (is_array($this->data)) {
			if (is_array($data)) {
				$this->data = array_merge($this->data, $data);
			} else {
				trigger_error("Cannot add request data - existing data is an array.", E_USER_NOTICE);
			}
		}
		
		return $this;
	}
	
	/**
	 * Sets a single option.
	 * 
	 * Will be adapted, as if added through setOptions() (it actually is).
	 * 
	 * @param string $name Option name
	 * @param mixed $value Option value
	 * @return $this
	 */
	final public function setOption($name, $value) {
		return $this->setOptions(array($name => $value));
	}
	
	/**
	 * Returns an option value if set.
	 * 
	 * @param string $name Option name.
	 * @return mixed Option value if set, otherwise null.
	 */
	final public function getOption($name) {
		return isset($this->options[$name]) ? $this->options[$name] : null;
	}
	
	/**
	 * Sets the options to use for this request.
	 * 
	 * Passes options to adaptOptions() before being set.
	 * 
	 * @param array $options Associative array of request options.
	 * @return $this
	 */
	final public function setOptions(array $options) {
		
		if (isset($options['headers'])) {
			$this->addHeaders($options['headers']);
			unset($options['headers']);
		}
		
		if (isset($options['data'])) {
			$this->addData($options['data']);
			unset($options['data']);
		}
		
		$this->options = $this->adaptOptions($options);
		
		return $this;
	}
	
}