<?php

namespace HttpUtil\Client\Request;

/**
 * Response from a HTTP client request.
 */
class Response {
	
	/**
	 * @var int
	 */
	protected $status;
	
	/**
	 * @var string
	 */
	protected $body;
	
	/**
	 * @var array
	 */
	protected $headers;
	
	/**
	 * @var array
	 */
	protected $cookies;
	
	/**
	 * @var object
	 */
	protected $body_object;
	
	/**
	 * Construct object from status, body, headers, and cookies.
	 */
	final public function __construct($status, $body, array $headers, array $cookies = array()) {
		$this->status = (int) $status;
		$this->body = $body;
		$this->headers = $headers;
		$this->cookies = $cookies;
	}
	
	/**
	 * Returns response status code.
	 * @return int
	 */
	final public function getStatus() {
		return $this->status;
	}
	
	/**
	 * Returns response body content.
	 * @return string
	 */
	final public function getBody() {
		return $this->body;
	}
	
	/**
	 * Returns response headers.
	 * @return array
	 */
	final public function getHeaders() {
		return $this->headers;
	}
	
	/**
	 * Returns response cookies.
	 * @return array
	 */
	final public function getCookies() {
		return $this->cookies;
	}
	
	/**
	 * Attempts to return the response body as an object using the 'Content-Type' header.
	 * 
	 * If body is empty, returns an empty stdClass object.
	 * If content-type is JSON, returns nested stdClass objects using json_decode().
	 * If content-type is XML, returns nested SimpleXMLElement objects.
	 * If content-type is any other, returns stdClass object with the 'content' property set as the body type-casted to an object.
	 * If none of the above conditions are met, a RuntimeException is thrown.
	 * 
	 * @throws RuntimeException if Content-Type header is missing and body has content.
	 * 
	 * @return object
	 */
	final public function getBodyObject() {
		
		if (isset($this->body_object)) {
			return $this->body_object;
		}
				
		if (empty($this->body)) {
			$this->body_object = new stdClass;
		} else if (isset($this->headers['content-type'])) {
				
			$content_type = $this->headers['content-type'];
			
			if (false !== stripos($content_type, 'json')) {
				$this->body_object = json_decode($this->body);
			} else if (false !== stripos($content_type, 'xml')) {
				$this->body_object = simplexml_load_string($this->body);
			} else {
				$this->body_object = new stdClass;
				$this->body_object->content = (object) $this->body;
			}
		} else {
			throw new \RuntimeException("Cannot convert body to object.");
		}
		
		return $this->body_object;
	}
	
}
