<?php
/**
 * GitPHP Controller RSS
 *
 * Controller for displaying a project's RSS
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

define('GITPHP_RSS_ITEMS', 150);

/**
 * RSS controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Rss extends GitPHP_ControllerBase
{
	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 * @return controller
	 */
	public function __construct()
	{
		parent::__construct();
		if (!$this->project) {
			throw new GitPHP_MessageException(__('Project is required'), true);
		}
	}

	/**
	 * GetTemplate
	 *
	 * Gets the template for this controller
	 *
	 * @access protected
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		return 'rss.tpl';
	}

	/**
	 * GetCacheKey
	 *
	 * Gets the cache key for this controller
	 *
	 * @access protected
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return '';
	}

	/**
	 * GetName
	 *
	 * Gets the name of this controller's action
	 *
	 * @access public
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public function GetName($local = false)
	{
		if ($local) {
			return __('rss');
		}
		return 'rss';
	}

	/**
	 * ReadQuery
	 *
	 * Read query into parameters
	 *
	 * @access protected
	 */
	protected function ReadQuery()
	{
		GitPHP_Log::GetInstance()->SetEnabled(false);
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
		$this->headers[] = "Content-type: text/xml; charset=UTF-8";
	}

	/**
	 * LoadData
	 *
	 * Loads data for this template
	 *
	 * @access protected
	 */
	protected function LoadData()
	{
		$log = $this->project->GetLog('HEAD', GITPHP_RSS_ITEMS);

		$entries = count($log);

		if ($entries > 20) {
			/*
			 * Don't show commits older than 48 hours,
			 * but show a minimum of 20 entries
			 */
			for ($i = 20; $i < $entries; ++$i) {
				if ((time() - $log[$i]->GetCommitterEpoch()) > 48*60*60) {
					$log = array_slice($log, 0, $i);
					break;
				}
			}
		}

		$this->tpl->assign('log', $log);
	}

}
