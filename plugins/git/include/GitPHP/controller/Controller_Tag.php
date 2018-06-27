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
		if (isset($this->params['jstip']) && $this->params['jstip']) {
			return 'tagtip.tpl';
		}
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
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public function GetName($local = false)
	{
		if ($local) {
			return __('tag');
		}
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

		if (isset($_GET['o']) && ($_GET['o'] == 'jstip')) {
			$this->params['jstip'] = true;
			GitPHP_Log::GetInstance()->SetEnabled(false);
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
		$head = $this->project->GetHeadCommit();
		$this->tpl->assign('head', $head);

		$tag = $this->project->GetTag($this->params['hash']);

		$this->tpl->assign("tag", $tag);
	}

}
