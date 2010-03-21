<?php
/**
 * GitPHP Controller Blame
 *
 * Controller for displaying blame
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

/**
 * Blame controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Blame extends GitPHP_ControllerBase
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
			throw new GitPHP_MessageException('Project is required for blame', true);
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
		return 'blame.tpl';
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
		return (isset($this->params['hashbase']) ? $this->params['hashbase'] : '') . '|' . (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['file']) ? sha1($this->params['file']) : '');
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
		if (isset($_GET['hb']))
			$this->params['hashbase'] = $_GET['hb'];
		else
			$this->params['hashbase'] = 'HEAD';
		if (isset($_GET['f']))
			$this->params['file'] = $_GET['f'];
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
		$head = $this->project->GetHeadCommit();
		$this->tpl->assign('head', $head);

		$commit = $this->project->GetCommit($this->params['hashbase']);
		$this->tpl->assign('commit', $commit);

		if ((!isset($this->params['hash'])) && (isset($this->params['file']))) {
			$this->params['hash'] = $commit->PathToHash($this->params['file']);
		}
		
		$blob = $this->project->GetBlob($this->params['hash']);
		if ($this->params['file'])
			$blob->SetName($this->params['file']);
		$blob->SetCommit($commit);
		$this->tpl->assign('blob', $blob);

		$this->tpl->assign('tree', $commit->GetTree());

		$blame = $blob->GetBlame();
		$this->tpl->assign('blame', $blob->GetBlame());
	}

}
