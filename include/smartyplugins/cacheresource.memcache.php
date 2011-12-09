<?php
/**
 * Smarty_CacheResource_Memcache
 *
 * Memcache and memcached cache handler
 * based on example memcache resource
 * included with smarty
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Cache
 */

/**
 * Smarty Memcache CacheResource
 */
class Smarty_CacheResource_Memcache extends Smarty_CacheResource_KeyValueStore
{

	/**
	 * Memcache extension type constants
	 */
	const Memcache = 1;
	const Memcached = 2;

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
		$servers = GitPHP_Config::GetInstance()->GetValue('memcache', null);
		if ((!$servers) || (!is_array($servers)) || (count($servers) < 1)) {
			throw new GitPHP_MessageException('No Memcache servers defined', true, 500);
		}

		if (class_exists('Memcached')) {

			$this->memcacheObj = new Memcached();
			$this->memcacheType = Smarty_CacheResource_Memcache::Memcached;
			$this->memcacheObj->addServers($servers);


		} else if (class_exists('Memcache')) {

			$this->memcacheObj = new Memcache();
			$this->memcacheType = Smarty_CacheResource_Memcache::Memcache;
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

		} else {
			throw new GitPHP_MessageException(__('The Memcached or Memcache PHP extension is required for Memcache support'), true, 500);
		}
	}

	/**
	 * read
	 *
	 * Read cached data
	 *
	 * @access protected
	 * @param array $keys array of keys to load
	 * @return array key/value cached data
	 */
	protected function read(array $keys)
	{
		$keymap = array();
		$hashedkeys = array();
		foreach ($keys as $key) {
			$newkey = sha1($key);
			$keymap[$newkey] = $key;
			$hashedkeys[] = $newkey;
		}

		$data = false;
		$cachedata = array();

		if ($this->memcacheType == Smarty_CacheResource_Memcache::Memcache) {

			$cachedata = $this->memcacheObj->get($hashedkeys);

		} else if ($this->memcacheType == Smarty_CacheResource_Memcache::Memcached) {

			$cachedata = $this->memcacheObj->getMulti($hashedkeys);
		}

		if ($cachedata) {
			foreach ($cachedata as $key => $value) {
				$origkey = $keymap[$key];
				if (!empty($origkey)) {
					$data[$origkey] = $value;
				}
			}
		}

		return $data;
	}

	/**
	 * write
	 *
	 * Write data to cache
	 *
	 * @access protected
	 * @param array $keys array of key/value data to store
	 * @param int $expire expiration time
	 * @return boolean true on success
	 */
	protected function write(array $keys, $expire = null)
	{
		if ($this->memcacheType == Smarty_CacheResource_Memcache::Memcache) {

			foreach ($keys as $key => $value) {
				$this->memcacheObj->set(sha1($key), $value, 0, $expire);
			}

			return true;

		} else if ($this->memcacheType == Smarty_CacheResource_Memcache::Memcached) {

			$mapped = array();
			foreach ($keys as $key => $value) {
				$mapped[sha1($key)] = $value;
			}
			$this->memcacheObj->setMulti($mapped, $expire);
			
			return true;
		}

		return false;
	}

	/**
	 * delete
	 *
	 * Delete data from cache
	 *
	 * @access protected
	 * @param array $keys array of keys to delete
	 * @return boolean true on success
	 */
	protected function delete(array $keys)
	{
		foreach ($keys as $key) {
			$this->memcacheObj->delete(sha1($key));
		}
		return true;
	}

	/**
	 * purge
	 *
	 * Delete all data from cache
	 *
	 * @access protected
	 * @return boolean true on success
	 */
	protected function purge()
	{
		return $this->memcacheObj->flush();
	}

}
