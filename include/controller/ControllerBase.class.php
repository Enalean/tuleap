<?php
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
abstract class GitPHP_ControllerBase
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
	 * preserveWhitespace
	 *
	 * Flag to preserve whitespace in output
	 * (for non-html output)
	 *
	 * @access protected
	 */
	protected $preserveWhitespace = false;

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
		require_once(GitPHP_Util::AddSlash(GitPHP_Config::GetInstance()->GetValue('smarty_prefix', 'lib/smarty/libs/')) . 'Smarty.class.php');
		$this->tpl = new Smarty;
		$this->tpl->error_reporting = E_ALL & ~E_NOTICE;
		$this->tpl->addPluginsDir(GITPHP_INCLUDEDIR . 'smartyplugins');

		if (GitPHP_Config::GetInstance()->GetValue('cache', false)) {
			$this->tpl->caching = Smarty::CACHING_LIFETIME_SAVED;
			if (GitPHP_Config::GetInstance()->HasKey('cachelifetime')) {
				$this->tpl->cache_lifetime = GitPHP_Config::GetInstance()->GetValue('cachelifetime');
			}

			$servers = GitPHP_Config::GetInstance()->GetValue('memcache', null);
			if (isset($servers) && is_array($servers) && (count($servers) > 0)) {
				$this->tpl->caching_type = 'memcache';
			}

		}

		if (isset($_GET['p'])) {
			$this->project = GitPHP_ProjectList::GetInstance()->GetProject(str_replace(chr(0), '', $_GET['p']));
			if (!$this->project) {
				throw new GitPHP_MessageException(sprintf(__('Invalid project %1$s'), $_GET['p']), true);
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
		$cacheKeyPrefix = GitPHP_Resource::GetLocale();

		$projList = GitPHP_ProjectList::GetInstance();
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

		if (strlen($cacheKey) > 100) {
			$cacheKey = sha1($cacheKey);
		}

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

		$stylesheet = GitPHP_Config::GetInstance()->GetValue('stylesheet', 'gitphpskin.css');
		if ($stylesheet == 'gitphp.css') {
			// backwards compatibility
			$stylesheet = 'gitphpskin.css';
		}
		$this->tpl->assign('stylesheet', preg_replace('/\.css$/', '', $stylesheet));

		$this->tpl->assign('javascript', GitPHP_Config::GetInstance()->GetValue('javascript', true));
		$this->tpl->assign('googlejs', GitPHP_Config::GetInstance()->GetValue('googlejs', false));
		$this->tpl->assign('pagetitle', GitPHP_Config::GetInstance()->GetValue('title', $gitphp_appstring));
		$this->tpl->assign('homelink', GitPHP_Config::GetInstance()->GetValue('homelink', __('projects')));
		$this->tpl->assign('action', $this->GetName());
		$this->tpl->assign('actionlocal', $this->GetName(true));
		if ($this->project)
			$this->tpl->assign('project', $this->project);
		if (GitPHP_Config::GetInstance()->GetValue('search', true))
			$this->tpl->assign('enablesearch', true);
		if (GitPHP_Config::GetInstance()->GetValue('filesearch', true))
			$this->tpl->assign('filesearch', true);
		if (isset($this->params['search']))
			$this->tpl->assign('search', $this->params['search']);
		if (isset($this->params['searchtype']))
			$this->tpl->assign('searchtype', $this->params['searchtype']);
		$this->tpl->assign('currentlocale', GitPHP_Resource::GetLocale());
		$this->tpl->assign('supportedlocales', GitPHP_Resource::SupportedLocales());

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

		$this->tpl->assign('snapshotformats', GitPHP_Archive::SupportedFormats());
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
		if ((GitPHP_Config::GetInstance()->GetValue('cache', false) == true) && (GitPHP_Config::GetInstance()->GetValue('cacheexpire', true) === true))
			$this->CacheExpire();

		if (!$this->tpl->isCached($this->GetTemplate(), $this->GetFullCacheKey())) {
			$this->tpl->clearAllAssign();
			$this->LoadCommonData();
			$this->LoadData();
		}

		if (!$this->preserveWhitespace) {
			$this->tpl->loadFilter('output', 'trimwhitespace');
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
			$this->tpl->clearAllCache();
			return;
		}

		if (!$this->project)
			return;

		$epoch = $this->project->GetEpoch();
		if (empty($epoch))
			return;

		$age = $this->project->GetAge();

		$this->tpl->clearCache(null, $this->GetCacheKeyPrefix(), null, $age);
		$this->tpl->clearCache('projectlist.tpl', $this->GetCacheKeyPrefix(false), null, $age);
	}

}
