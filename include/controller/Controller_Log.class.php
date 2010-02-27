<?php
/**
 * GitPHP Controller Log
 *
 * Controller for displaying a log
 *
 * @author Christopher Han
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

require_once(GITPHP_INCLUDEDIR . 'util.age_string.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_read_revlist.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.read_info_ref.php');

/**
 * Log controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Log extends GitPHP_ControllerBase
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
			throw new GitPHP_MessageException('Project is required for log', true);
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
		if (isset($this->params['short']) && ($this->params['short'] === true)) {
			return 'shortlog.tpl';
		}
		return 'log.tpl';
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
		return $this->params['hash'] . '|' . $this->params['page'];
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
		if (isset($_GET['pg']))
			$this->params['page'] = $_GET['pg'];
		else
			$this->params['page'] = 0;
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
		if (isset($this->params['short']) && ($this->params['short'] === true)) {
			$this->LoadDataShort();
			return;
		}
		$head = $this->project->GetHeadCommit()->GetHash();
		$refs = read_info_ref();
		$this->tpl->assign("hash",$this->params['hash']);
		$this->tpl->assign("head",$head);

		if ($this->params['page'])
			$this->tpl->assign("page",$this->params['page']);

		$revlist = git_read_revlist($this->params['hash'], 101, ($this->params['page'] * 100));

		$revlistcount = count($revlist);
		$this->tpl->assign("revlistcount",$revlistcount);

		if (!$revlist) {
			$this->tpl->assign("norevlist",TRUE);
			$co = $this->project->GetCommit($this->params['hash']);
			$this->tpl->assign("lastchange", age_string($co->GetAge()));
		}

		$commitlines = array();
		$commitcount = min(100,$revlistcount);
		for ($i = 0; $i < $commitcount; ++$i) {
			$commit = $revlist[$i];
			if (isset($commit) && strlen($commit) > 1) {
				$commitline = array();
				$co = $this->project->GetCommit($commit);
				$commitline["project"] = $this->project->GetProject();
				$commitline["commit"] = $commit;
				if (isset($refs[$commit]))
					$commitline["commitref"] = $refs[$commit];
				$commitline["agestring"] = age_string($co->GetAge());
				$commitline["title"] = $co->GetTitle();
				$commitline["authorname"] = $co->GetAuthorName();
				$commitline["authorepoch"] = $co->GetAuthorEpoch();
				$commitline["comment"] = $co->GetComment();
				$commitlines[] = $commitline;
				unset($co);
			}
		}
		$this->tpl->assign("commitlines",$commitlines);
	}

	/**
	 * LoadDataShort
	 *
	 * Load data for shortlog
	 * TODO: temporary until templates get cleaned up more
	 */
	private function LoadDataShort()
	{
		$head = $this->project->GetHeadCommit();;
		$refs = read_info_ref();
		$this->tpl->assign("hash",$this->params['hash']);
		$this->tpl->assign("head",$head->GetHash());

		if ($page)
			$this->tpl->assign("page",$page);

		$revlist = git_read_revlist($this->params['hash'], 101, ($page * 100));

		$revlistcount = count($revlist);
		$this->tpl->assign("revlistcount",$revlistcount);

		$commitlines = array();
		$commitcount = min(100,count($revlist));
		for ($i = 0; $i < $commitcount; ++$i) {
			$commit = $revlist[$i];
			if (strlen(trim($commit)) > 0) {
				$commitline = array();
				if (isset($refs[$commit]))
					$commitline["commitref"] = $refs[$commit];
				$co = $this->project->GetCommit($commit);
				$commitline["commit"] = $commit;
				$age = $co->GetAge();
				if ($age > 60*60*24*7*2) {
					$commitline["agestringdate"] = date('Y-m-d', $co->GetCommitterEpoch());
					$commitline["agestringage"] = age_string($age);
				} else {
					$commitline["agestringdate"] = age_string($age);
					$commitline["agestringage"] = date('Y-m-d', $co->GetCommitterEpoch());
				}
				$commitline["authorname"] = $co->GetAuthorName();
				$titleshort = $co->GetTitle(GITPHP_TRIM_LENGTH);
				$title = $co->GetTitle();
				$commitline["title_short"] = $titleshort;
				if (strlen($titleshort) < strlen($title))
					$commitline["title"] = $title;
				$commitlines[] = $commitline;
				unset($co);
			}
		}
		$this->tpl->assign("commitlines",$commitlines);
	}

}
