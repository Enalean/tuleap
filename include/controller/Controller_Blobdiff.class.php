<?php
/**
 * GitPHP Controller Blobdiff
 *
 * Controller for displaying a blobdiff
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

require_once(GITPHP_INCLUDEDIR . 'util.prep_tmpdir.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.read_info_ref.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_path_trees.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_diff.php');

/**
 * Blobdiff controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Blobdiff extends GitPHP_ControllerBase
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
			throw new GitPHP_MessageException('Project is required for blob diff', true);
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
			return 'blobdiffplain.tpl';
		}
		return 'blobdiff.tpl';
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
	 * LoadHeaders
	 *
	 * Loads headers for this template
	 *
	 * @access protected
	 */
	protected function LoadHeaders()
	{
		if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
			$this->headers[] = 'Content-type: text/plain; charset=UTF-8';
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
		$ret = prep_tmpdir();
		if ($ret !== TRUE) {
			echo $ret;
			return;
		}

		if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
			$this->tpl->assign("blobdiff",git_diff($this->params['hashparent'],($this->params['file']?"a/".$this->params['file']:$this->params['hashparent']),$this->params['hash'],($this->params['file']?"b/".$this->params['file']:$this->params['hash'])));
			return;
		}

		$this->tpl->assign("hash",$this->params['hash']);
		$this->tpl->assign("hashparent",$this->params['hashparent']);
		$this->tpl->assign("hashbase",$this->params['hashbase']);
		if (isset($this->params['file']))
			$this->tpl->assign("file",$this->params['file']);
		$co = $this->project->GetCommit($this->params['hashbase']);
		if ($co) {
			$this->tpl->assign("fullnav",TRUE);
			$this->tpl->assign("tree",$co->GetTree()->GetHash());
			$this->tpl->assign("title",$co->GetTitle());
			$refs = read_info_ref();
			if (isset($refs[$this->params['hashbase']]))
				$this->tpl->assign("hashbaseref",$refs[$this->params['hashbase']]);
		}
		$paths = git_path_trees($this->params['hashbase'], $this->params['file']);
		$this->tpl->assign("paths",$paths);
		$diffout = explode("\n",git_diff($this->params['hashparent'],($this->params['file']?$this->params['file']:$this->params['hashparent']),$this->params['hash'],($this->params['file']?$this->params['file']:$this->params['hash'])));
		$this->tpl->assign("diff",$diffout);
	}

}
