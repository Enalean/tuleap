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

require_once(GITPHP_INCLUDEDIR . 'gitutil.git_path_trees.php');

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

		$hashbase = $this->project->GetCommit($this->params['hashbase']);
		$this->tpl->assign('hashbase', $hashbase);

		if ((!isset($this->params['hash'])) && (isset($this->params['file']))) {
			$this->params['hash'] = $hashbase->PathToHash($this->params['file']);
		}
		
		$hash = $this->project->GetBlob($this->params['hash']);
		$hash->SetCommit($hashbase);
		$this->tpl->assign('hash', $hash);

		$this->tpl->assign('tree', $hashbase->GetTree());

		$paths = git_path_trees($this->params['hashbase'], $this->params['file']);
		$this->tpl->assign("paths",$paths);

		$blame = $hash->GetBlame();
		$this->tpl->assign('blame', $hash->GetBlame());
	}

}
