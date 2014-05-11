<?php

namespace HttpUtil;

class Env {
	
	protected $sslEnabled;
	protected $host;
	protected $domain;
	
	protected static $instance;
	
	public static function instance() {
		if (! isset(static::$instance))
			static::$instance = new static();
		return static::$instance;
	}
	
	/**
	 * Whether SSL is enabled.
	 */
	public function isSsl() {
		if (! isset($this->sslEnabled)) {
			if ($https = getenv('https')) {
				$this->sslEnabled = ('on' === strtolower($https) || 1 == $https);
			} else if ('https' === getenv('http_x_forwarded_proto') || 443 == getenv('server_port')) {
				$this->sslEnabled = true;
			} else {
				$this->sslEnabled = false;
			}
		}
		return $this->sslEnabled;
	}
	
	/**
	 * Host = {HTTP host}/{Script directory}
	 */
	public function getHost() {
		if (! isset($this->host)) {
			$this->host = rtrim(getenv('http_host'), '/\\').rtrim(dirname(getenv('script_name')), '/\\');
		}
		return $this->host;
	}
	
	/**
	 * Domain = http[s]://{Host}
	 */
	public function getDomain() {
		if (! isset($this->domain)) {
			$this->domain = 'http'.($this->isSsl() ? 's' : '').'://'.ltrim($this->getHost(), '/');
		}
		return $this->domain;
	}
	
}
