<?php
/**
 * GitPHP Controller Project
 *
 * Controller for displaying a project summary
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

/**
 * Project controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Project extends GitPHP_ControllerBase
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
			throw new GitPHP_MessageException('Project is required for project summary', true);
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
		return 'project.tpl';
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
	 * ReadQuery
	 *
	 * Read query into parameters
	 *
	 * @access protected
	 */
	protected function ReadQuery()
	{
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
		$this->tpl->assign('head', $this->project->GetHeadCommit());

		if (GitPHP_Config::GetInstance()->HasKey('cloneurl'))
			$this->tpl->assign('cloneurl', GitPHP_Config::GetInstance()->GetValue('cloneurl') . $this->project->GetProject());
		if (GitPHP_Config::GetInstance()->HasKey('pushurl'))
			$this->tpl->assign('pushurl', GitPHP_Config::GetInstance()->GetValue('pushurl') . $this->project->GetProject());

		$revlist = $this->project->GetLog('HEAD', 17);
		if ($revlist) {
			if (count($revlist) > 16) {
				$this->tpl->assign('hasmorerevs', true);
				$revlist = array_slice($revlist, 0, 16);
			}
			$this->tpl->assign('revlist', $revlist);
		}

		$taglist = $this->project->GetTags();
		if (isset($taglist) && (count($taglist) > 0)) {
			$this->tpl->assign("taglist",$taglist);
		}

		$headlist = $this->project->GetHeads();
		if (isset($headlist) && (count($headlist) > 0)) {
			$this->tpl->assign("headlist",$headlist);
		}
	}

}
