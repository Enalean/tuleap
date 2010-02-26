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

require_once(GITPHP_INCLUDEDIR . 'gitutil.git_parse_blame.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.read_info_ref.php');
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
		if ((!isset($this->params['hash'])) && (isset($this->params['file']))) {
			$this->params['hash'] = git_get_hash_by_path($this->params['hashbase'], $this->params['file'], 'blob');
		}
		$head = $this->project->GetHeadCommit()->GetHash();
		$this->tpl->assign("hash", $this->params['hash']);
		$this->tpl->assign("hashbase", $this->params['hashbase']);
		$this->tpl->assign("head", $head);
		$co = $this->project->GetCommit($this->params['hashbase']);
		if ($co) {
			$this->tpl->assign("fullnav",TRUE);
			$refs = read_info_ref();
			$this->tpl->assign("tree",$co->GetTree()->GetHash());
			$this->tpl->assign("title",$co->GetTitle());
			if (isset($this->params['file']))
				$this->tpl->assign("file",$this->params['file']);
			if ($this->params['hashbase'] == "HEAD") {
				if (isset($refs[$head]))
					$this->tpl->assign("hashbaseref",$refs[$head]);
			} else {
				if (isset($refs[$this->params['hashbase']]))
					$this->tpl->assign("hashbaseref",$refs[$this->params['hashbase']]);
			}
		}
		$paths = git_path_trees($this->params['hashbase'], $this->params['file']);
		$this->tpl->assign("paths",$paths);

		$blamedata = git_parse_blame($this->params['file'], $this->params['hashbase']);
		$this->tpl->assign("blamedata",$blamedata);
	}

}
