<?php
/**
 * GitPHP Controller Blobdiff
 *
 * Controller for displaying a blobdiff
 *
 * @author Mattias Ulbrich
 * @package GitPHP
 * @subpackage Controller
 */


/**
 * Blobdiff controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Sidediff extends GitPHP_ControllerBase
{
	private $gitexe;

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
		$this->gitexe = new GitPHP_GitExe($this->project);

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
		return 'sidebyside.tpl';
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
		return (isset($this->params['hashbase']) ? $this->params['hashbase'] : '') . '|' . (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['hashparent']) ? $this->params['hashparent'] : '') . '|' . (isset($this->params['file']) ? sha1($this->params['file']) : '');
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
			return __('blobdiff_sidebyside');
		}
		return 'blobdiff_sidebyside';
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
		if (isset($_GET['h']))
			$this->params['hash'] = $_GET['h'];
		if (isset($_GET['hb']))
			$this->params['hashbase'] = $_GET['hb'];
		if (isset($_GET['hp']))
			$this->params['hashparent'] = $_GET['hp'];
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
		if (isset($this->params['file']))
			$this->tpl->assign('file', $this->params['file']);

		$filediff = new GitPHP_FileDiff($this->project, $this->params['hashparent'], $this->params['hash']);
		$this->tpl->assign('filediff', $filediff);

		$commit = $this->project->GetCommit($this->params['hashbase']);
		$this->tpl->assign('commit', $commit);

		$blobparent = $this->project->GetBlob($this->params['hashparent']);
		$blobparent->SetCommit($commit);
		$blobparent->SetPath($this->params['file']);
		$this->tpl->assign('blobparent', $blobparent);

		$blob = $this->project->GetBlob($this->params['hash']);
		$blob->SetPath($this->params['file']);
		$this->tpl->assign('blob', $blob);

		$tree = $commit->GetTree();
		$this->tpl->assign('tree', $tree);
	}

}
