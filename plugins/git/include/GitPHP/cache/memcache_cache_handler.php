<?php
/**
 * memcache_cache_handler
 *
 * Memcache smarty cache handler, with hacks to
 * support cache groups and template ages
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Cache
 */

/**
 * Cache key for the cache contents / age map array
 */
define('MEMCACHE_OBJECT_MAP', 'memcache_objectmap');

/**
 * memcache cache handler function
 *
 * @param string $action cache action
 * @param mixed $smarty_obj smarty object
 * @param string $cache_content content to store/load
 * @param string $tpl_file template file
 * @param string $cache_id cache id
 * @param string $compile_id compile id
 * @param int $exp_time expiration time
 */
function memcache_cache_handler($action, &$smarty_obj, &$cache_content, $tpl_file = null, $cache_id = null, $compile_id = null, $exp_time = null)
{
	$memObj = GitPHP_Memcache::GetInstance();

	$namespace = getenv('SERVER_NAME') . '_gitphp_';

	$fullKey = $cache_id . '^' . $compile_id . '^' . $tpl_file;

	switch ($action) {

		case 'read':
			$cache_content = $memObj->Get($namespace . $fullKey);
			return true;
			break;

		case 'write':
			/*
			 * Keep a map of keys we have stored, and
			 * their expiration times
			 */
			$map = $memObj->Get($namespace . MEMCACHE_OBJECT_MAP);
			if (!(isset($map) && is_array($map)))
				$map = array();

			if (!isset($exp_time))
				$exp_time = 0;
			$map[$fullKey] = time();

			$memObj->Set($namespace . $fullKey, $cache_content, $exp_time);
			$memObj->Set($namespace . MEMCACHE_OBJECT_MAP, $map);
			break;

		case 'clear':

			if (empty($cache_id) && empty($compile_id) && empty($tpl_file)) {
				/*
				 * Clear entire cache
				 */
				return $memObj->Clear();
			}


			$cachePrefix = '';
			if (!empty($cache_id))
				$cachePrefix = $cache_id;
			if (!empty($compile_id))
				$cachePrefix .= '^' . $compile_id;

			$map = $memObj->Get($namespace . MEMCACHE_OBJECT_MAP);
			if (isset($map) && is_array($map)) {
				$now = time();
				/*
				 * Search through our stored map of keys
				 */
				foreach ($map as $key => $age) {
					if (
					    /*
					     * If we have a prefix (group),
					     * match any keys that start with
					     * this group
					     */
					    (empty($cachePrefix) || (substr($key, 0, strlen($cachePrefix)) == $cachePrefix)) &&
					    /*
					     * If we have a template, match
					     * any keys that end with this
					     * template
					     */
					    (empty($tpl_file) || (substr($key, strlen($tpl_file) * -1) == $tpl_file)) &&
					    /*
					     * If we have an expiration time,
					     * match any keys older than that
					     */
					    ((!isset($exp_time)) || (($now - $age) > $exp_time))
					) {
						$memObj->Delete($namespace . $key);
						unset($map[$key]);
					}
				}

				/*
				 * Update the key map
				 */
				$memObj->Set($namespace . MEMCACHE_OBJECT_MAP, $map);
			}
			return true;
			break;

		default:
			$smarty_obj->trigger_error('memcache_cache_handler: unknown action "' . $action . '"');
			return false;
			break;

	}
}
