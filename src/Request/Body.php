<?php

namespace HttpUtil\Request;

class Body
{
	
	/**
	 * Read-only file handle for php://input stream.
	 * @var resource
	 */
	protected static $handle;
	
	/**
	 * Contents of php://input stream.
	 * @var string
	 */
	protected static $contents;
	
	/**
	 * Gets the request body stream contents as string.
	 * 
	 * Reads the stream opened by getHandle();
	 * 
	 * @return string Contents of request body read from input stream.
	 */
	public static function getContents() {
			
		if (! isset(static::$contents)) {
			static::$contents = stream_get_contents(static::getHandle());
		}
		
		return static::$contents;
	}
	
	/**
	 * Returns a read-only file handle for the request body stream.
	 * 
	 * Stream is opened using fopen("php://input") with the recommended binary flag.
	 * It is automatically closed on shutdown (just for good measure).
	 * 
	 * @return resource Read-only handle for the php://input stream.
	 */
	public static function getHandle() {
		
		if (! isset(static::$handle)) {
			static::open();
		}
		
		return static::$handle;
	}
	
	/**
	 * Opens the stream handle.
	 * 
	 * @return void
	 */
	public static function open() {
			
		static::$handle = fopen('php://input', 'rb');
		
		register_shutdown_function(array(get_called_class(), 'close'));
	}
	
	/**
	 * Closes the stream handle.
	 * 
	 * @return void
	 */
	public static function close() {
			
		if (is_resource(static::$handle)) {
			fclose(static::$handle);
		}
	}

}
