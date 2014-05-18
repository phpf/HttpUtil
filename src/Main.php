<?php
/**
 * @package Phpf\HttpUtil
 */

namespace HttpUtil;

class Main {
	
	/**
	 * Packages and whether they are loaded.
	 * 
	 * @var array
	 */
	protected static $packages = array(
		'functions' => false,
		'constants' => false,
		'request' => false,
	);
	
	/**
	 * Class name of the HTTP request adapter.
	 * 
	 * Allows the use of 3rd party HTTP clients with the
	 * included request-related functions, such as http_get().
	 * 
	 * @var string 
	 */
	protected static $request_handler;
	
	/**
	 * Load a package - one of "functions", "constants", or "request".
	 * 
	 * The request package requires the functions package to be
	 * loaded before loading.
	 * 
	 * @param string $package Package name.
	 * @return boolean|null True if package was loaded, false if failed, or null if it does not exist.
	 */
	public static function load($package) {
			
		if (! isset(static::$packages[$package])) {
			return null;
		}
		
		if (true !== static::$packages[$package]) {
				
			$method = 'load_'.$package;
			
			if (! static::$method()) {
				return false;
			}
			
			static::$packages[$package] = true;
		}
		
		return true;
	}
	
	/**
	 * Sets the request adapter class.
	 * 
	 * @param string $class Request adapter class name.
	 * @return void
	 */
	public static function setRequestHandler($class) {
		
		if (! static::$packages['request']) {
			trigger_error("HttpUtil 'request' package must be loaded to set handler.", E_USER_NOTICE);
			return false;
		}
		
		if (! class_exists($class, true)) {
			throw new \HttpUtilException("Trying to use unknown class '$class' as request handler.");
		}
		
		static::$request_handler = $class;
		
		$class::initialize();
	}
	
	/**
	 * Returns the request adapter class name, if set.
	 * 
	 * @return string|null
	 */
	public static function getRequestHandler() {
		return isset(static::$request_handler) ? static::$request_handler : null;
	}
	
	/**
	 * Loads the library's functions.
	 */
	private static function load_functions() {
		
		/**
		 * http_response_code() for PHP < 5.4
		 */
		if (! function_exists('http_response_code')) {
			require __DIR__.'/fn/http_response_code.php';
		}
		
		/**
		 * Checking for this function explicitly because other libraries may
		 * provide their own implementation.
		 */
		if (! function_exists('http_build_url')) {
			require __DIR__.'/fn/http_build_url.php';
		}
		
		/**
		 * Functions based on the HTTP (v1) extension.
		 * @see fn/http.php
		 */
		if (! function_exists('http_get_request_headers')) {
			require __DIR__.'/fn/http.php';
		}
		
		/**
		 * Extra functions this library provides.
		 * @see fn/extra.php
		 */
		require __DIR__.'/fn/extra.php';
		
		return true;
	}
			
	/**
	 * Defines some "HTTP_STATUS_*" constants whose values are their
	 * respective HTTP status code integers.
	 *
	 * e.g.
	 * HTTP_STATUS_OK => 200
	 * HTTP_STATUS_TEMP_REDIRECT => 407
	 * ...
	 */
	private static function load_constants() {
		
		require __DIR__.'/status_constants.php';
		
		return true;
	}
	
	/**
	 * Loads the files to support requests.
	 * 
	 * Requires functions package to be loaded, otherwise a user notice is triggered.
	 */
	private static function load_request() {
			
		if (! static::$packages['functions']) {
			trigger_error("HttpUtil request package requires functions package.", E_USER_NOTICE);
			return false;
		}
		
		require __DIR__.'/fn/request.php';
		
		return true;		
	}	
}
