<?php


namespace Tuleap\Git\GitPHP;

/**
 * GitPHP ControllerBase
 *
 * Base class that all controllers extend
 *
 * @author Christopher Han <xiphux@gmail.com
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
/**
 * ControllerBase class
 *
 * @package GitPHP
 * @subpackage Controller
 * @abstract
 */
abstract class ControllerBase
{

	/**
	 * tpl
	 *
	 * Smarty instance
	 *
	 * @access protected
	 */
	protected $tpl;

	/**
	 * project
	 *
	 * Current project
	 *
	 * @access protected
	 */
	protected $project;

	/**
	 * params
	 *
	 * Parameters
	 *
	 * @access protected
	 */
	protected $params = array();

	/**
	 * headers
	 *
	 * Headers
	 *
	 * @access protected
	 */
	protected $headers = array();

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 * @return mixed controller object
	 * @throws Exception on invalid project
	 */
	public function __construct()
	{
		$this->tpl = new \Smarty;
		$this->tpl->plugins_dir[] = GITPHP_INCLUDEDIR . 'smartyplugins';
		$this->tpl->template_dir  = __DIR__ . '/../../../templates/gitphp/';

		// Use a dedicated directory for smarty temporary files if needed.
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

		if (Config::GetInstance()->GetValue('cache', false)) {
			$this->tpl->caching = 2;
			if (Config::GetInstance()->HasKey('cachelifetime')) {
				$this->tpl->cache_lifetime = Config::GetInstance()->GetValue('cachelifetime');
			}

			$servers = Config::GetInstance()->GetValue('memcache', null);
			if (isset($servers) && is_array($servers) && (count($servers) > 0)) {
				Memcache::GetInstance()->AddServers($servers);
				require_once(GITPHP_CACHEDIR . 'memcache_cache_handler.php');
				$this->tpl->cache_handler_func = 'memcache_cache_handler';
			}

		}

		if (isset($_GET['p'])) {
			$this->project = ProjectList::GetInstance()->GetProject(str_replace(chr(0), '', $_GET['p']));
			if (!$this->project) {
				throw new MessageException(sprintf(__('Invalid project %1$s'), $_GET['p']), true);
			}
		}

		if (isset($_GET['s']))
			$this->params['search'] = $_GET['s'];
		if (isset($_GET['st']))
			$this->params['searchtype'] = $_GET['st'];

		$this->ReadQuery();
	}

	/**
	 * GetTemplate
	 *
	 * Gets the template for this controller
	 *
	 * @access protected
	 * @abstract
	 * @return string template filename
	 */
	protected abstract function GetTemplate();

	/**
	 * GetCacheKey
	 *
	 * Gets the cache key for this controller
	 *
	 * @access protected
	 * @abstract
	 * @return string cache key
	 */
	protected abstract function GetCacheKey();

	/**
	 * GetCacheKeyPrefix
	 *
	 * Get the prefix for all cache keys
	 *
	 * @access private
	 * @param string $projectKeys include project-specific key pieces
	 * @return string cache key prefix
	 */
	private function GetCacheKeyPrefix($projectKeys = true)
	{
		$cacheKeyPrefix = Resource::GetLocale();

		$projList = ProjectList::GetInstance();
		if ($projList) {
			$cacheKeyPrefix .= '|' . sha1(serialize($projList->GetConfig())) . '|' . sha1(serialize($projList->GetSettings()));
			unset($projList);
		}
		if ($this->project && $projectKeys) {
			$cacheKeyPrefix .= '|' . sha1($this->project->GetProject());
		}
		
		return $cacheKeyPrefix;
	}

	/** 
	 * GetFullCacheKey
	 *
	 * Get the full cache key
	 *
	 * @access protected
	 * @return string full cache key
	 */
	protected function GetFullCacheKey()
	{
		$cacheKey = $this->GetCacheKeyPrefix();

		$subCacheKey = $this->GetCacheKey();

		if (!empty($subCacheKey))
			$cacheKey .= '|' . $subCacheKey;

		return $cacheKey;
	}

	/**
	 * GetName
	 *
	 * Gets the name of this controller's action
	 *
	 * @abstract
	 * @access public
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public abstract function GetName($local = false);

	/**
	 * ReadQuery
	 *
	 * Read query into parameters
	 *
	 * @abstract
	 * @access protected
	 */
	protected abstract function ReadQuery();

	/**
	 * SetParam
	 *
	 * Set a parameter
	 *
	 * @access protected
	 * @param string $key key to set
	 * @param mixed $value value to set
	 */
	public function SetParam($key, $value)
	{
		if (empty($key))
			return;

		if (empty($value))
			unset($this->params[$key]);

		$this->params[$key] = $value;
	}

	/**
	 * LoadHeaders
	 *
	 * Loads headers for this template
	 *
	 * @access protected
	 */
	protected function LoadHeaders()
	{
	}

	/**
	 * LoadData
	 *
	 * Loads data for this template
	 *
	 * @access protected
	 * @abstract
	 */
	protected abstract function LoadData();

	/**
	 * LoadCommonData
	 *
	 * Loads common data used by all templates
	 *
	 * @access private
	 */
	private function LoadCommonData()
	{
		global $gitphp_version, $gitphp_appstring;

		$this->tpl->assign('version', $gitphp_version);

		$stylesheet = Config::GetInstance()->GetValue('stylesheet', 'gitphpskin.css');
		if ($stylesheet == 'gitphp.css') {
			// backwards compatibility
			$stylesheet = 'gitphpskin.css';
		}
		$this->tpl->assign('stylesheet', preg_replace('/\.css$/', '', $stylesheet));

		$this->tpl->assign('javascript', Config::GetInstance()->GetValue('javascript', true));
		$this->tpl->assign('pagetitle', Config::GetInstance()->GetValue('title', $gitphp_appstring));
		$this->tpl->assign('homelink', Config::GetInstance()->GetValue('homelink', __('projects')));
		$this->tpl->assign('action', $this->GetName());
		$this->tpl->assign('actionlocal', $this->GetName(true));
		if ($this->project)
			$this->tpl->assign('project', $this->project);
		if (Config::GetInstance()->GetValue('search', true))
			$this->tpl->assign('enablesearch', true);
		if (Config::GetInstance()->GetValue('filesearch', true))
			$this->tpl->assign('filesearch', true);
		if (isset($this->params['search']))
			$this->tpl->assign('search', $this->params['search']);
		if (isset($this->params['searchtype']))
			$this->tpl->assign('searchtype', $this->params['searchtype']);
		$this->tpl->assign('currentlocale', Resource::GetLocale());
		//$this->tpl->assign('supportedlocales', GitPHP_Resource::SupportedLocales());

		$getvars = explode('&', $_SERVER['QUERY_STRING']);
		$getvarsmapped = array();
		foreach ($getvars as $varstr) {
			$eqpos = strpos($varstr, '=');
			if ($eqpos > 0) {
				$var = substr($varstr, 0, $eqpos);
				$val = substr($varstr, $eqpos + 1);
				if (!(empty($var) || empty($val))) {
					$getvarsmapped[$var] = urldecode($val);
				}
			}
		}
		$this->tpl->assign('requestvars', $getvarsmapped);

		$this->tpl->assign('snapshotformats', Archive::SupportedFormats());
	}

	/**
	 * RenderHeaders
	 *
	 * Renders any special headers
	 *
	 * @access public
	 */
	public function RenderHeaders()
	{
		$this->LoadHeaders();

		if (count($this->headers) > 0) {
			foreach ($this->headers as $hdr) {
				header($hdr);
			}
		}
	}

	/**
	 * Render
	 *
	 * Renders the output
	 *
	 * @access public
	 */
	public function Render()
	{
		if ((Config::GetInstance()->GetValue('cache', false) == true) && (Config::GetInstance()->GetValue('cacheexpire', true) === true))
			$this->CacheExpire();

		if (!$this->tpl->is_cached($this->GetTemplate(), $this->GetFullCacheKey())) {
			$this->tpl->clear_all_assign();
			$this->LoadCommonData();
			$this->LoadData();
		}

		$this->tpl->display($this->GetTemplate(), $this->GetFullCacheKey());
	}

	/**
	 * CacheExpire
	 *
	 * Expires the cache
	 *
	 * @access public
	 * @param boolean $expireAll expire the whole cache
	 */
	public function CacheExpire($expireAll = false)
	{
		if ($expireAll) {
			$this->tpl->clear_all_cache();
			return;
		}

		if (!$this->project)
			return;

		$epoch = $this->project->GetEpoch();
		if (empty($epoch))
			return;

		$age = $this->project->GetAge();

		$this->tpl->clear_cache(null, $this->GetCacheKeyPrefix(), null, $age);
		$this->tpl->clear_cache('projectlist.tpl', $this->GetCacheKeyPrefix(false), null, $age);
	}

}