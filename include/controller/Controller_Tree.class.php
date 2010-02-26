<?php
/**
 * GitPHP Controller Tree
 *
 * Controller for displaying a tree
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

require_once(GITPHP_INCLUDEDIR . 'util.mode_str.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_get_hash_by_path.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_ls_tree.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.read_info_ref.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_path_trees.php');

/**
 * Tree controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Tree extends GitPHP_ControllerBase
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
			throw new GitPHP_MessageException('Project is required for tree', true);
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
		return 'tree.tpl';
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
		if (isset($_GET['f']))
			$this->params['file'] = $_GET['f'];
		if (isset($_GET['h']))
			$this->params['hash'] = $_GET['h'];
		if (isset($_GET['hb']))
			$this->params['hashbase'] = $_GET['hb'];
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
		if (!isset($this->params['hash'])) {
			$this->params['hash'] = $this->project->GetHeadCommit()->GetHash();
			if (isset($this->params['file']))
				$this->params['hash'] = git_get_hash_by_path((isset($this->params['hashbase']) ? $this->params['hashbase'] : $this->params['hash']), $this->params['file'], 'tree');
		}
		if (!isset($this->params['hashbase'])) {
			$this->params['hashbase'] = $this->params['hash'];
		}
		$lsout = git_ls_tree($this->params['hash'], TRUE);
		$refs = read_info_ref();
		$this->tpl->assign("hash",$this->params['hash']);
		if (isset($this->params['hashbase']))
			$this->tpl->assign("hashbase",$this->params['hashbase']);
		if (isset($this->params['hashbase'])) {
			$co = $this->project->GetCommit($this->params['hashbase']);
			if ($co) {
				$this->tpl->assign("fullnav",TRUE);
				$this->tpl->assign("title",$co->GetTitle());
				if (isset($refs[$this->params['hashbase']]))
					$this->tpl->assign("hashbaseref",$refs[$this->params['hashbase']]);
			}
		}
		$paths = git_path_trees($this->params['hashbase'], $this->params['file']);
		$this->tpl->assign("paths",$paths);

		if (isset($this->params['file']))
			$this->tpl->assign("base",$this->params['file'] . "/");

		$treelines = array();
		$tok = strtok($lsout,"\0");
		while ($tok !== false) {
			if (preg_match("/^([0-9]+) (.+) ([0-9a-fA-F]{40})\t(.+)$/",$tok,$regs)) {
				$treeline = array();
				$treeline["filemode"] = mode_str($regs[1]);
				$treeline["type"] = $regs[2];
				$treeline["hash"] = $regs[3];
				$treeline["name"] = $regs[4];
				$treelines[] = $treeline;
			}
			$tok = strtok("\0");
		}
		$this->tpl->assign("treelines",$treelines);
	}

}
