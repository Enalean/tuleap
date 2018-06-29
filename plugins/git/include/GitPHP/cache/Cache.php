<?php


namespace Tuleap\Git\GitPHP;

/**
 * GitPHP Cache
 *
 * Class to store arbitrary data objects in smarty cache
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Cache
 */
/**
 * Cache class
 *
 * @package GitPHP
 * @subpackage Cache
 */
class Cache
{
	/**
	 * Template
	 *
	 * Cache template
	 */
	const Template = 'data.tpl';

	/**
	 * objectCacheInstance
	 *
	 * Stores the singleton instance of the object cache
	 *
	 * @access protected
	 * @static
	 */
	protected static $objectCacheInstance;

	/**
	 * GetObjectCacheInstance
	 *
	 * Return the singleton instance of the object cache
	 *
	 * @access public
	 * @static
	 * @return mixed instance of cache class
	 */
	public static function GetObjectCacheInstance()
	{
		if (!self::$objectCacheInstance) {
			self::$objectCacheInstance = new Cache();
			if (Config::GetInstance()->GetValue('objectcache', false)) {
				self::$objectCacheInstance->SetEnabled(true);
				self::$objectCacheInstance->SetLifetime(Config::GetInstance()->GetValue('objectcachelifetime', 86400));
			}
		}
		return self::$objectCacheInstance;
	}

	/**
	 * tpl
	 *
	 * Smarty instance
	 *
	 * @access protected
	 */
	protected $tpl = null;

	/**
	 * enabled
	 *
	 * Stores whether the cache is enabled
	 *
	 * @access protected
	 */
	protected $enabled = false;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 * @return mixed cache object
	 */
	public function __construct()
	{
	}

	/**
	 * GetEnabled
	 *
	 * Gets whether the cache is enabled
	 *
	 * @access public
	 * @return boolean true if enabled
	 */
	public function GetEnabled()
	{
		return $this->enabled;
	}

	/**
	 * SetEnabled
	 *
	 * Sets whether the cache is enabled
	 *
	 * @access public
	 * @param boolean $enable true to enable, false to disable
	 */
	public function SetEnabled($enable)
	{
		if ($enable == $this->enabled)
			return;

		$this->enabled = $enable;

		if ($this->enabled)
			$this->CreateSmarty();
		else
			$this->DestroySmarty();
	}

	/**
	 * GetLifetime
	 *
	 * Gets the cache lifetime
	 *
	 * @access public
	 * @return int cache lifetime in seconds
	 */
	public function GetLifetime()
	{
		if (!$this->enabled)
			return false;

		return $this->tpl->cache_lifetime;
	}

	/**
	 * SetLifetime
	 *
	 * Sets the cache lifetime
	 *
	 * @access public
	 * @param int $lifetime cache lifetime in seconds
	 */
	public function SetLifetime($lifetime)
	{
		if (!$this->enabled)
			return;

		$this->tpl->cache_lifetime = $lifetime;
	}

	/**
	 * Get
	 *
	 * Get an item from the cache
	 *
	 * @access public
	 * @param string $key cache key
	 * @return the cached object, or false
	 */
	public function Get($key = null)
	{
		if (empty($key))
			return false;

		if (!$this->enabled)
			return false;

		if (!$this->tpl->is_cached(Cache::Template, $key))
			return false;

		$data = $this->tpl->fetch(Cache::Template, $key);

		return unserialize($data);
	}

	/**
	 * Set
	 *
	 * Set an item in the cache
	 *
	 * @access public
	 * @param string $key cache key
	 * @param mixed $value value
	 * @param int $lifetime override the lifetime for this data
	 */
	public function Set($key = null, $value = null, $lifetime = null)
	{
		if (empty($key) || empty($value))
			return;

		if (!$this->enabled)
			return;

		$oldLifetime = null;
		if ($lifetime !== null) {
			$oldLifetime = $this->tpl->cache_lifetime;
			$this->tpl->cache_lifetime = $lifetime;
		}

		$this->Delete($key);
		$this->tpl->clear_all_assign();
		$this->tpl->assign('data', serialize($value));

		// Force it into smarty's cache
		$tmp = $this->tpl->fetch(Cache::Template, $key);
		unset($tmp);

		if ($lifetime !== null) {
			$this->tpl->cache_lifetime = $oldLifetime;
		}
	}

	/**
	 * Exists
	 *
	 * Tests if a key is cached
	 *
	 * @access public
	 * @param string $key cache key
	 * @return boolean true if cached, false otherwise
	 */
	public function Exists($key = null)
	{
		if (empty($key))
			return false;

		if (!$this->enabled)
			return false;

		return $this->tpl->is_cached(Cache::Template, $key);
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
		if (empty($key))
			return;

		if (!$this->enabled)
			return;

		$this->tpl->clear_cache(Cache::Template, $key);
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
		if (!$this->enabled)
			return;

		$this->tpl->clear_all_cache();
	}

	/**
	 * CreateSmarty
	 *
	 * Instantiates Smarty cache
	 *
	 * @access private
	 */
	private function CreateSmarty()
	{
		if ($this->tpl)
			return;

		$this->tpl = new \Smarty;
		$this->tpl->plugins_dir[] = GITPHP_INCLUDEDIR . 'smartyplugins';

		$this->tpl->caching = 2;

		$servers = Config::GetInstance()->GetValue('memcache', null);
		if (isset($servers) && is_array($servers) && (count($servers) > 0)) {
			Memcache::GetInstance()->AddServers($servers);
			require_once(GITPHP_CACHEDIR . 'memcache_cache_handler.php');
			$this->tpl->cache_handler_func = 'memcache_cache_handler';
		}

		// Init a dedicated space for templates (copy/paste from ControllerBase).
		if (Config::GetInstance()->HasKey('smarty_tmp')) {
		    $smarty_tmp = Config::GetInstance()->GetValue('smarty_tmp');
		    if (!is_dir($smarty_tmp)) {
			mkdir($smarty_tmp, 0755, true);
		    }

		    $templates_c = $smarty_tmp.'/templates_c';
		    if (!is_dir($templates_c)) {
			mkdir($templates_c, 0755, true);
		    }
		    $this->tpl->compile_dir = $templates_c;

		    $cache = $smarty_tmp.'/cache';
		    if (!is_dir($cache)) {
			mkdir($cache, 0755, true);
		    }
		    $this->tpl->cache_dir = $cache;
		}
	}

	/**
	 * DestroySmarty
	 *
	 * Destroys Smarty cache
	 *
	 * @access private
	 */
	private function DestroySmarty()
	{
		if (!$this->tpl)
			return;

		$this->tpl = null;
	}

}