<?php
/**
 * GitPHP Controller Commitdiff
 *
 * Controller for displaying a commitdiff
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

require_once(GITPHP_CONTROLLERDIR . 'Controller_DiffBase.class.php');

/**
 * Commitdiff controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Commitdiff extends GitPHP_Controller_DiffBase
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
		if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
			return 'commitdiffplain.tpl';
		}
		return 'commitdiff.tpl';
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
		$key = (isset($this->params['hash']) ? $this->params['hash'] : '')
		. '|' . (isset($this->params['hashparent']) ? $this->params['hashparent'] : '')
		. '|' . (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true) ? '1' : '');

		return $key;
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
			return __('commitdiff');
		}
		return 'commitdiff';
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
		parent::ReadQuery();

		if (isset($_GET['h']))
			$this->params['hash'] = $_GET['h'];
		if (isset($_GET['hp']))
			$this->params['hashparent'] = $_GET['hp'];
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
		parent::LoadHeaders();

		if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
			$this->headers[] = 'Content-disposition: inline; filename="git-' . $this->params['hash'] . '.patch"';
			$this->preserveWhitespace = true;
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
		$co = $this->project->GetCommit($this->params['hash']);
		$this->tpl->assign('commit', $co);

		if (isset($this->params['hashparent'])) {
			$this->tpl->assign("hashparent", $this->params['hashparent']);
		}

		if (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true)) {
			$this->tpl->assign('sidebyside', true);
		}

		$treediff = new GitPHP_TreeDiff($this->project, $this->params['hash'], (isset($this->params['hashparent']) ? $this->params['hashparent'] : ''));
		$this->tpl->assign('treediff', $treediff);
	}

}
