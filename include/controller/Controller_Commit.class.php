<?php
/**
 * GitPHP Controller Commit
 *
 * Controller for displaying a commit
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

require_once(GITPHP_INCLUDEDIR . 'util.file_type.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_diff_tree.php');

/**
 * Commit controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Commit extends GitPHP_ControllerBase
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
			throw new GitPHP_MessageException('Project is required for commit', true);
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
		return 'commit.tpl';
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
		return $this->params['hash'];
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
		if (isset($_GET['h']))
			$this->params['hash'] = $_GET['h'];
		else
			$this->params['hash'] = 'HEAD';
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
		$commit = $this->project->GetCommit($this->params['hash']);
		$parentObj = $commit->GetParent();
		if ($parentObj) {
			$root = "";
			$parent = $parentObj->GetHash();
		} else {
			$root = "--root";
			$parent = "";
		}
		$diffout = git_diff_tree($root . " " . $parent . " " . $hash, TRUE);
		$difftree = explode("\n",$diffout);
		$treeObj = $commit->GetTree();
		if ($treeObj)
			$this->tpl->assign("tree", $treeObj->GetHash());
		if ($parentObj)
			$this->tpl->assign("parent", $parentObj->GetHash());
		$this->tpl->assign("commit", $commit);
		$this->tpl->assign("difftreesize",count($difftree)+1);
		$difftreelines = array();
		foreach ($difftree as $i => $line) {
			if (preg_match("/^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)([0-9]{0,3})\t(.*)$/",$line,$regs)) {
				$difftreeline = array();
				$difftreeline["from_mode"] = $regs[1];
				$difftreeline["to_mode"] = $regs[2];
				$difftreeline["from_mode_cut"] = substr($regs[1],-4);
				$difftreeline["to_mode_cut"] = substr($regs[2],-4);
				$difftreeline["from_id"] = $regs[3];
				$difftreeline["to_id"] = $regs[4];
				$difftreeline["status"] = $regs[5];
				$difftreeline["similarity"] = ltrim($regs[6],"0");
				$difftreeline["file"] = $regs[7];
				$difftreeline["from_file"] = strtok($regs[7],"\t");
				$difftreeline["from_filetype"] = file_type($regs[1]);
				$difftreeline["to_file"] = strtok("\t");
				$difftreeline["to_filetype"] = file_type($regs[2]);
				if ((octdec($regs[2]) & 0x8000) == 0x8000)
					$difftreeline["isreg"] = TRUE;
				$modestr = "";
				if ((octdec($regs[1]) & 0x17000) != (octdec($regs[2]) & 0x17000))
					$modestr .= " from " . file_type($regs[1]) . " to " . file_type($regs[2]);
				if ((octdec($regs[1]) & 0777) != (octdec($regs[2]) & 0777)) {
					if ((octdec($regs[1]) & 0x8000) && (octdec($regs[2]) & 0x8000))
						$modestr .= " mode: " . (octdec($regs[1]) & 0777) . "->" . (octdec($regs[2]) & 0777);
					else if (octdec($regs[2]) & 0x8000)
						$modestr .= " mode: " . (octdec($regs[2]) & 0777);
				}
				$difftreeline["modechange"] = $modestr;
				$simmodechg = "";
				if ($regs[1] != $regs[2])
					$simmodechg .= ", mode: " . (octdec($regs[2]) & 0777);
				$difftreeline["simmodechg"] = $simmodechg;
				$difftreelines[] = $difftreeline;
			}
		}
		$this->tpl->assign("difftreelines",$difftreelines);
	}

}
