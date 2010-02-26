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

require_once(GITPHP_INCLUDEDIR . 'gitutil.git_get_hash_by_path.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.read_info_ref.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_history_list.php');
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
		if (!isset($this->params['hash']))
			$this->params['hash'] = $this->project->GetHeadCommit()->GetHash();

		$co = $this->project->GetCommit($this->params['hash']);
		$refs = read_info_ref();
		$this->tpl->assign("hash",$this->params['hash']);
		if (isset($refs[$this->params['hash']]))
			$this->tpl->assign("hashbaseref",$refs[$this->params['hash']]);
		$this->tpl->assign("tree", $co->GetTree()->GetHash());
		$this->tpl->assign("title", $co->GetTitle());
		$paths = git_path_trees($this->params['hash'], $this->params['file']);
		$this->tpl->assign("paths",$paths);
		date_default_timezone_set('UTC');
		$cmdout = git_history_list($this->params['hash'], $this->params['file']);
		$lines = explode("\n", $cmdout);
		$historylines = array();
		foreach ($lines as $i => $line) {
			if (preg_match("/^([0-9a-fA-F]{40})/",$line,$regs))
				$commit = $regs[1];
			else if (preg_match("/:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)\t(.*)$/",$line,$regs) && isset($commit)) {
					$historyline = array();
					$co2 = $this->project->GetCommit($commit);
					$age = $co2->GetAge();
					if ($age > 60*60*24*7*2) {
						$historyline['agestringdate'] = date('Y-m-d', $co2->GetCommitterEpoch());
						$historyline['agestringage'] = age_string($age);
					} else {
						$historyline['agestringdate'] = age_string($age);
						$historyline['agestringage'] = date('Y-m-d', $co2->GetCommitterEpoch());
					}
					$historyline["authorname"] = $co2->GetAuthorName();
					$historyline["commit"] = $commit;
					$historyline["file"] = $this->params['file'];
					$historyline["title"] = $co2->GetTitle(GITPHP_TRIM_LENGTH);
					if (isset($refs[$commit]))
						$historyline["commitref"] = $refs[$commit];
					$blob = git_get_hash_by_path($this->params['hash'],$this->params['file']);
					$blob_parent = git_get_hash_by_path($commit,$this->params['file']);
					if ($blob && $blob_parent && ($blob != $blob_parent)) {
						$historyline["blob"] = $blob;
						$historyline["blobparent"] = $blob_parent;
					}
					$historylines[] = $historyline;
					unset($co2);
					unset($commit);
			}
		}
		$this->tpl->assign("historylines",$historylines);
	}

}
