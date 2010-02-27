<?php
/**
 * GitPHP Controller RSS
 *
 * Controller for displaying a project's RSS
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

require_once(GITPHP_INCLUDEDIR . 'gitutil.git_read_revlist.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_diff_tree.php');

/**
 * RSS controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Rss extends GitPHP_ControllerBase
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
			throw new GitPHP_MessageException('Project is required for RSS', true);
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
		return 'rss.tpl';
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
		return '';
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
		$this->headers[] = "Content-type: text/xml; charset=UTF-8";
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
		$head = $this->project->GetHeadCommit();;
		$revlist = git_read_revlist($head->GetHash(), GITPHP_RSS_ITEMS);

		$commitlines = array();
		$revlistcount = count($revlist);
		for ($i = 0; $i < $revlistcount; ++$i) {
			$commit = $revlist[$i];
			$co = $this->project->GetCommit($commit);
			if (($i >= 20) && ((time() - $co->GetCommitterEpoch()) > 48*60*60))
				break;
			$commitline = array();
			$commitline["committerepoch"] = $co->GetCommitterEpoch();
			$commitline["title"] = $co->GetTitle();
			$commitline["author"] = $co->GetAuthor();
			$commitline["commit"] = $commit;
			$commitline["comment"] = $co->GetComment();

			$parent = $co->GetParent();
			if ($parent) {
				$difftree = array();
				$diffout = git_diff_tree($parent->GetHash() . " " . $co->GetHash());
				$tok = strtok($diffout,"\n");
				while ($tok !== false) {
					if (preg_match("/^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)([0-9]{0,3})\t(.*)$/",$tok,$regs))
						$difftree[] = $regs[7];
					$tok = strtok("\n");
				}
				$commitline["difftree"] = $difftree;
			}

			$commitlines[] = $commitline;
			unset($co);
		}
		$this->tpl->assign("commitlines",$commitlines);
	}

}
