<?php
/**
 * GitPHP Controller Tag
 *
 * Controller for displaying a tag
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

/**
 * Tag controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Tag extends GitPHP_ControllerBase
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
			throw new GitPHP_MessageException(GitPHP_Resource::GetInstance()->GetResource('Project is required'), true);
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
		return 'tag.tpl';
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
		return isset($this->params['hash']) ? sha1($this->params['hash']) : '';
	}

	/**
	 * GetName
	 *
	 * Gets the name of this controller's action
	 *
	 * @access public
	 * @return string action name
	 */
	public function GetName()
	{
		return 'tag';
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
		if (isset($_GET['h'])) {
			$this->params['hash'] = $_GET['h'];
		}
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
		$head = $this->project->GetHeadCommit()->GetHash();
		$this->tpl->assign('head', $head);

		$tag = new GitPHP_Tag($this->project, $this->params['hash']);

		$this->tpl->assign("tag", $tag);
	}

}
