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

require_once(GITPHP_INCLUDEDIR . 'util.date_str.php');
require_once(GITPHP_INCLUDEDIR . 'util.file_type.php');
require_once(GITPHP_INCLUDEDIR . 'util.prep_tmpdir.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_diff_tree.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_read_revlist.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.read_info_ref.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_diff.php');

/**
 * Commitdiff controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Commitdiff extends GitPHP_ControllerBase
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
			throw new GitPHP_MessageException('Project is required for commit diff', true);
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
			return 'diff_plaintext.tpl';
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
		return (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['hashparent']) ? $this->params['hashparent'] : '');
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
			$this->headers[] = 'Content-disposition: inline; filename="git-' . $this->params['hash'] . '.patch"';
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
		$co = $this->project->GetCommit($this->params['hash']);
		if (!isset($this->params['hashparent'])) {
			$parent = $co->GetParent();
			if ($parent)
				$this->params['hashparent'] = $parent->GetHash();
		}
		$diffout = git_diff_tree($this->params['hashparent'] . " " . $this->params['hash']);
		$difftree = explode("\n",$diffout);

		if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
			$refs = read_info_ref('tags');
			$listout = git_read_revlist('HEAD');
			foreach ($listout as $i => $rev) {
				if (isset($refs[$rev]))
					$tagname = $refs[$rev];
				if ($rev == $this->params['hash'])
					break;
			}
			$ad = date_str($co->GetAuthorEpoch(), $co->GetAuthorTimezone());
			$this->tpl->assign("from", $co->GetAuthor());
			$this->tpl->assign("date",$ad['rfc2822']);
			$this->tpl->assign("subject", $co->GetTitle());
			if (isset($tagname))
				$this->tpl->assign("tagname",$tagname);
			$this->tpl->assign("url",script_url() . "?p=" . $this->project->GetProject() . "&a=commitdiff&h=" . $this->params['hash']);
			$this->tpl->assign("comment", $co->GetComment());
			$diffs = array();
			foreach ($difftree as $i => $line) {
				if (preg_match("/^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)\t(.*)$/",$line,$regs)) {
					if ($regs[5] == "A")
						$diffs[] = git_diff(null, "/dev/null", $regs[4], "b/" . $regs[6]);
					else if ($regs[5] == "D")
						$diffs[] = git_diff($regs[3], "a/" . $regs[6], null, "/dev/null");
					else if ($regs[5] == "M")
						$diffs[] = git_diff($regs[3], "a/" . $regs[6], $regs[4], "b/" . $regs[6]);
				}
			}
			$this->tpl->assign("diffs",$diffs);
		} else {
			$refs = read_info_ref();
			$this->tpl->assign("hash",$this->params['hash']);
			$tree = $co->GetTree();
			if ($tree)
				$this->tpl->assign("tree", $tree->GetHash());
			$this->tpl->assign("hashparent",$this->params['hashparent']);
			$this->tpl->assign("title", $co->GetTitle());
			if (isset($refs[$co->GetHash()]))
				$this->tpl->assign("commitref",$refs[$co->GetHash()]);
			$this->tpl->assign("comment",$co->GetComment());
			$difftreelines = array();
			foreach ($difftree as $i => $line) {
				if (preg_match("/^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)\t(.*)$/",$line,$regs)) {
					$difftreeline = array();
					$difftreeline["from_mode"] = $regs[1];
					$difftreeline["to_mode"] = $regs[2];
					$difftreeline["from_id"] = $regs[3];
					$difftreeline["to_id"] = $regs[4];
					$difftreeline["status"] = $regs[5];
					$difftreeline["file"] = $regs[6];
					$difftreeline["from_type"] = file_type($regs[1]);
					$difftreeline["to_type"] = file_type($regs[2]);
					if ($regs[5] == "A")
						$difftreeline['diffout'] = explode("\n",git_diff(null,"/dev/null",$regs[4],"b/" . $regs[6]));
					else if ($regs[5] == "D")
						$difftreeline['diffout'] = explode("\n",git_diff($regs[3],"a/" . $regs[6],null,"/dev/null"));
					else if (($regs[5] == "M") && ($regs[3] != $regs[4]))
						$difftreeline['diffout'] = explode("\n",git_diff($regs[3],"a/" . $regs[6],$regs[4],"b/" . $regs[6]));
					$difftreelines[] = $difftreeline;
				}
			}
			$this->tpl->assign("difftreelines",$difftreelines);
		}
	}

}
