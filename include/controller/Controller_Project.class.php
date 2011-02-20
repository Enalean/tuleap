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
			return __('summary');
		}
		return 'summary';
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

		$revlist = $this->project->GetLog('HEAD', 17);
		if ($revlist) {
			if (count($revlist) > 16) {
				$this->tpl->assign('hasmorerevs', true);
				$revlist = array_slice($revlist, 0, 16);
			}
			$this->tpl->assign('revlist', $revlist);
		}

		$taglist = $this->project->GetTags(17);
		if ($taglist) {
			if (count($taglist) > 16) {
				$this->tpl->assign('hasmoretags', true);
				$taglist = array_slice($taglist, 0, 16);
			}
			$this->tpl->assign('taglist', $taglist);
		}

		$headlist = $this->project->GetHeads(17);
		if ($headlist) {
			if (count($headlist) > 17) {
				$this->tpl->assign('hasmoreheads', true);
				$headlist = array_slice($headlist, 0, 16);
			}
			$this->tpl->assign('headlist', $headlist);
		}
	}

}
