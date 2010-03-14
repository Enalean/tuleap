<?php
/**
 * GitPHP Controller History
 *
 * Controller for displaying file history
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

require_once(GITPHP_INCLUDEDIR . 'gitutil.git_path_trees.php');

/**
 * History controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_History extends GitPHP_ControllerBase
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
			throw new GitPHP_MessageException('Project is required for file history', true);
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
		return 'history.tpl';
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
		return (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['file']) ? sha1($this->params['file']) : '');
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
		if (isset($_GET['f']))
			$this->params['file'] = $_GET['f'];
		if (isset($_GET['h'])) {
			$this->params['hash'] = $_GET['h'];
		} else {
			$this->params['hash'] = 'HEAD';
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
		$this->tpl->assign('hash', $co);
		$this->tpl->assign('tree', $co->GetTree());

		$blobhash = $co->PathToHash($this->params['file']);
		$blob = $this->project->GetBlob($blobhash);
		$blob->SetCommit($co);
		$this->tpl->assign('blob', $blob);

		$paths = git_path_trees($this->params['hash'], $this->params['file']);
		$this->tpl->assign("paths",$paths);
	}

}
