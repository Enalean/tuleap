<?php
/**
 * GitPHP Memcache
 *
 * Memcache wrapper class to support both memcache extensions
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Cache
 */

/**
 * Memcache class
 *
 * @package GitPHP
 * @subpackage Cache
 */
class GitPHP_Memcache
{
	/**
	 * Memcache extension type constants
	 */
	const Memcache = 1;
	const Memcached = 2;

	/**
	 * instance
	 *
	 * Stores the singleton instance
	 *
	 * @access protected
	 * @static
	 */
	protected static $instance;

	/**
	 * GetInstance
	 *
	 * Returns the singleton instance
	 *
	 * @access public
	 * @static
	 * @return mixed instance of config class
	 */
	public static function GetInstance()
	{
		if (!self::$instance) {
			self::$instance = new GitPHP_Memcache();
		}
		return self::$instance;
	}

	/**
	 * Supported
	 *
	 * Returns whether memcache is supported by this PHP
	 *
	 * @access public
	 * @static
	 * @return true if memcache functions exist
	 */
	public static function Supported()
	{
		return (class_exists('Memcached') || class_exists('Memcache'));
	}

	/**
	 * memcacheObj
	 *
	 * Memcache object
	 *
	 * @access protected
	 */
	protected $memcacheObj = null;

	/**
	 * memcacheType
	 *
	 * Memcache extension type
	 *
	 * @access protected
	 */
	protected $memcacheType = 0;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 * @return Memcache object
	 */
	public function __construct()
	{
		if (class_exists('Memcached')) {
			$this->memcacheObj = new Memcached();
			$this->memcacheType = GitPHP_Memcache::Memcached;
		} else if (class_exists('Memcache')) {
			$this->memcacheObj = new Memcache();
			$this->memcacheType = GitPHP_Memcache::Memcache;
		} else {
			throw new GitPHP_MessageException(__('The Memcached or Memcache PHP extension is required for Memcache support'), true, 500);
		}
	}

	/**
	 * AddServers
	 *
	 * Add servers to memcache
	 *
	 * @access public 
	 * @param array $servers array of servers
	 */
	public function AddServers($servers)
	{
		if ((!$servers) || (!is_array($servers)) || (count($servers) < 1)) {
			return;
		}

		if ($this->memcacheType == GitPHP_Memcache::Memcached) {
			$this->memcacheObj->addServers($servers);
		} else if ($this->memcacheType == GitPHP_Memcache::Memcache) {
			foreach ($servers as $server) {
				if (is_array($server)) {
					$host = $server[0];
					$port = 11211;
					if (isset($server[1]))
						$port = $server[1];
					$weight = 1;
					if (isset($server[2]))
						$weight = $server[2];
					$this->memcacheObj->addServer($host, $port, true, $weight);
				}
			}
		}
	}

	/**
	 * Get
	 *
	 * Get an item from memcache
	 *
	 * @access public
	 * @param string $key cache key
	 * @return the cached object, or false
	 */
	public function Get($key = null)
	{
		return $this->memcacheObj->Get($key);
	}

	/**
	 * Set
	 *
	 * Set an item in memcache
	 *
	 * @access public
	 * @param string $key cache key
	 * @param mixed $value value
	 * @param int $expiration expiration time
	 */
	public function Set($key = null, $value = null, $expiration = 0)
	{
		if ($this->memcacheType == GitPHP_Memcache::Memcached)
			return $this->memcacheObj->set($key, $value, $expiration);
		else if ($this->memcacheType == GitPHP_Memcache::Memcache)
			return $this->memcacheObj->set($key, $value, 0, $expiration);
		return false;
	}

	/**
	 * Delete
	 *
	 * Delete an item from the cache
	 *
	 * @access public
	 * @param string $key cache key
	 */
	public function Delete($key = null)
	{
		return $this->memcacheObj->delete($key);
	}

	/**
	 * Clear
	 *
	 * Clear the cache
	 *
	 * @access public
	 */
	public function Clear()
	{
		return $this->memcacheObj->flush();
	}

	/**
	 * GetType
	 *
	 * Get the type of this memcache
	 *
	 * @access public
	 * @return int memcache type
	 */
	public function GetType()
	{
		return $this->memcacheType;
	}

}
