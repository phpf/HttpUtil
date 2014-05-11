<?php
/**
 * HttpUtil - HTTP utility library.
 *
 * @author wells
 * @license MIT
 * @version 0.2
 *
 * If not using Composer, you can register the "HttpUtil" namespace
 * with a PSR-4 autoloader using the "src/" directory as the base path.
 */

namespace HttpUtil {
	
	class Main {
		
		protected static $packages = array(
			'functions' => false,
			'constants' => false,
			'request' => false,
		);
		
		protected static $request_handler;
		
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
		
		public static function getRequestHandler() {
			return isset(static::$request_handler) ? static::$request_handler : null;
		}
		
		protected static function load_functions() {
			
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
			 * Functions based on the HTTP (v1) extension.
			 * @see /src/fn/http.php
			 */
			if (! function_exists('http_get_request_headers')) {
				require __DIR__.'/src/fn/http.php';
			}
			
			/**
			 * Extra functions this library provides.
			 */
			require __DIR__.'/src/fn/extra.php';
			
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
		protected static function load_constants() {
			
			require __DIR__.'/src/status_constants.php';
			
			return true;
		}
		
		/**
		 * Loads the files to support requests.
		 * 
		 * Requires functions package to be loaded, otherwise a user notice is triggered.
		 */
		protected static function load_request() {
				
			if (! static::$packages['functions']) {
				trigger_error("HttpUtil request package requires functions package.", E_USER_NOTICE);
				return false;
			}
			
			require __DIR__.'/src/fn/request.php';
			
			return true;		
		}	
	}

}

namespace {
		
	class HttpUtilException extends \RuntimeException {
		
	}
	
	function httputil_load(/* $package [, $package [, ...]] */) {
		foreach(func_get_args() as $package) {
			\HttpUtil\Main::load($package);
		}
	}

}