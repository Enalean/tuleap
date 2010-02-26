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

require_once(GITPHP_INCLUDEDIR . 'util.script_url.php');

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
		require_once(GitPHP_Config::GetInstance()->GetValue('smarty_prefix', 'lib/smarty/libs/') . 'Smarty.class.php');
		$this->tpl = new Smarty;

		require_once(GITPHP_INCLUDEDIR . 'util.age_string.php');
		$this->tpl->register_modifier('agestring', 'age_string');

		if (GitPHP_Config::GetInstance()->GetValue('cache', false)) {
			$this->tpl->caching = 2;
			if (GitPHP_Config::GetInstance()->HasKey('cachelifetime')) {
				$this->tpl->cache_lifetime = GitPHP_Config::GetInstance()->GetValue('cachelifetime');
			}
		}

		if (isset($_GET['p'])) {
			$this->project = GitPHP_ProjectList::GetInstance()->GetProject(str_replace(chr(0), '', $_GET['p']));
			if (!$this->project) {
				throw new GitPHP_MessageException('Invalid project ' . $_GET['p'], true);
			}
		}

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
	 * @return string cache key prefix
	 */
	private function GetCacheKeyPrefix()
	{
		$cacheKeyPrefix = sha1(serialize(GitPHP_ProjectList::GetInstance()->GetConfig));
		if ($this->project) {
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
	 * ReadQuery
	 *
	 * Read query into parameters
	 *
	 * @abstract
	 * @access prtected
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
		$this->tpl->assign('stylesheet', GitPHP_Config::GetInstance()->GetValue('stylesheet', 'gitphp.css'));
		$this->tpl->assign('pagetitle', GitPHP_Config::GetInstance()->GetValue('title', $gitphp_appstring));
		$this->tpl->assign("self",script_url());
		if ($this->project) {
			$this->tpl->assign('validproject', true);
			$this->tpl->assign('project', $this->project->GetProject());
			$this->tpl->assign('projectdescription', $this->project->GetDescription());
		}
		if (GitPHP_Config::GetInstance()->GetValue('search', true))
			$this->tpl->assign('enablesearch', true);
		if (GitPHP_Config::GetInstance()->GetValue('filesearch', true))
			$this->tpl->assign('filesearch', true);
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

		$headList = $this->project->GetHeads();

		if (count($headlist) > 0) {
			$age = $headlist[0]->GetCommit()->GetAge();

			$this->tpl->clear_cache(null, $this->GetCacheKeyPrefix(), null, $age);
			$this->tpl->clear_cache('projectlist.tpl', sha1(serialize(GitPHP_ProjectList::GetInstance()->GetConfig())), null, $age);
		}
	}

}
